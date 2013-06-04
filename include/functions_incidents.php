<?php 
// INTEGRIA IMS
// http://www.integriaims.com
// ===========================================================
// Copyright (c) 2007-2012 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2012 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.


/**
 * Filter all the incidents and return a list of matching elements.
 *
 * This function only return the incidents that can be accessed for the current
 * user with IR permission.
 *
 * @param array Key-value array of parameters to filter. It can handle this fields:
 *
 * string String to find in incident title.
 * status Status to search.
 * priority Priority to search.
 * id_group Incident group
 * id_product Incident affected product
 * id_company Incident affected company
 * id_inventory Incident affected inventory object
 * serial_number Incident affected inventory object's serial number
 * id_building Incident affected inventory object in a building
 * sla_fired Wheter the SLA was fired or not
 * id_incident_type Incident type
 * id_user Incident risponsable user
 * first_date Begin range date (range start)
 * last_date Begin range date (range end)
 *
 * @return array A list of matching incidents. False if no matches.
 */

// Avoid to mess AJAX with Javascript
if(defined ('AJAX')) {
	require_once ($config["homedir"]."/include/functions_graph.php");
}

include_once ($config["homedir"]."/include/graphs/fgraph.php");

function filter_incidents ($filters) {
	global $config;
	
	/* Set default values if none is set */
	$filters['string'] = isset ($filters['string']) ? $filters['string'] : '';
	$filters['status'] = isset ($filters['status']) ? $filters['status'] : 0;
	$filters['priority'] = isset ($filters['priority']) ? $filters['priority'] : -1;
	$filters['id_group'] = isset ($filters['id_group']) ? $filters['id_group'] : -1;
	$filters['id_company'] = isset ($filters['id_company']) ? $filters['id_company'] : 0;
	$filters['id_inventory'] = isset ($filters['id_inventory']) ? $filters['id_inventory'] : 0;
	$filters['id_incident_type'] = isset ($filters['id_incident_type']) ? $filters['id_incident_type'] : 0;
	$filters['id_user'] = isset ($filters['id_user']) ? $filters['id_user'] : '';
	$filters['id_user_or_creator'] = isset ($filters['id_user_or_creator']) ? $filters['id_user_or_creator'] : '';
	$filters['first_date'] = isset ($filters['first_date']) ? $filters['first_date'] : '';
	$filters['last_date'] = isset ($filters['last_date']) ? $filters['last_date'] : '';
	
	if (empty ($filters['status']))
		$filters['status'] = implode (',', array_keys (get_indicent_status ()));
	
	// Not closed
	if ($filters["status"] == -10)
		$filters['status'] = "1,2,3,4,5,6";

	$resolutions = get_incident_resolutions ();
	
	$sql_clause = '';
	if ($filters['priority'] != -1)
		$sql_clause .= sprintf (' AND prioridad = %d', $filters['priority']);
	if ($filters['id_group'] != 1)
		$sql_clause .= sprintf (' AND id_grupo = %d', $filters['id_group']);
	if (! empty ($filters['id_user']))
		$sql_clause .= sprintf (' AND id_usuario = "%s"', $filters['id_user']);
	if (! empty ($filters['id_user_or_creator']))
		$sql_clause .= sprintf (' AND (id_usuario = "%s" OR id_creator = "%s")', $filters['id_user_or_creator'], $filters['id_user_or_creator']);
	if (! empty ($filters['id_incident_type']))
		$sql_clause .= sprintf (' AND id_incident_type = %d', $filters['id_incident_type']);
	if (! empty ($filters['first_date'])) {
		$time = strtotime ($filters['first_date']);
		//00:00:00 to set date at the beginig of the day
		$sql_clause .= sprintf (' AND inicio >= "%s"', date ("Y-m-d 00:00:00", $time));
	}
	if (! empty ($filters['last_date'])) {
		$time = strtotime ($filters['last_date']);
		if (! empty ($filters['first_date'])) {
			//23:59:59 to set date at the end of day
			$sql_clause .= sprintf (' AND inicio <= "%s"', date ("Y-m-d 23:59:59", $time));
		} else {
			$time_from = strtotime ($filters['first_date']);
			if ($time_from < $time)
				$sql_clause .= sprintf (' AND inicio <= "%s"',
					date ("Y-m-d", $time));
		}
	}

	// Manage external users
	$return = enterprise_hook ('manage_external');
	if ($return !== ENTERPRISE_NOT_HOOK)
		$sql_clause .= $return;
	
	$sql = sprintf ('SELECT * FROM tincidencia
			WHERE estado IN (%s)
			%s
			AND (titulo LIKE "%%%s%%" OR descripcion LIKE "%%%s%%" OR id_creator LIKE "%%%s%%" OR id_usuario LIKE "%%%s%%")
			ORDER BY actualizacion DESC
			LIMIT %d',
			$filters['status'], $sql_clause, $filters['string'], $filters['string'], $filters['string'],$filters['string'],
			$config['limit_size']);

    // DEBUG
    //echo $sql ." <br>";
    
	$incidents = get_db_all_rows_sql ($sql);
	if ($incidents === false)
		return false;

	$result = array ();
	foreach ($incidents as $incident) {
		// ACL pass if IR for this group or if the user is the incident creator
		if (! give_acl ($config['id_user'], $incident['id_grupo'], 'IR')
			&& ($incident['id_creator'] != $config['id_user']) )
			continue;
		
		$inventories = get_inventories_in_incident ($incident['id_incidencia'], false);
		
		if ($filters['id_inventory']) {
			$found = false;
			foreach ($inventories as $inventory) {
				if ($inventory['id'] == $filters['id_inventory']) {
					$found = true;
					break;
				}
			}
		
			if (! $found)
				continue;
		}
	
		if ($filters['id_company']) {
			$found = false;
			$user_creator = $incident['id_creator'];
			$user_company = get_db_value('id_company', 'tusuario', 'id_usuario', $user_creator);

			//If company do no match, dismiss incident
			if ($filters['id_company'] != $user_company) {
				continue;
			}
		}
		
		array_push ($result, $incident);
	}
	
	return $result;
}


/**
 * Copy and insert in database a new file into incident
 *
 * @param int incident id
 * @param string file full path
 * @param string file description
 *
 */
 
function attach_incident_file ($id, $file_temp, $file_description) {
	global $config;
	
	$filesize = filesize($file_temp); // In bytes
	$filename = basename($file_temp);

	$sql = sprintf ('INSERT INTO tattachment (id_incidencia, id_usuario,
			filename, description, size)
			VALUES (%d, "%s", "%s", "%s", %d)',
			$id, $config['id_user'], clean_output($filename), $file_description, $filesize);

	$id_attachment = process_sql ($sql, 'insert_id');
	
	incident_tracking ($id, INCIDENT_FILE_ADDED);
	
	$result_msg = ui_print_success_message(__('File added'), '', true);
	
	// Email notify to all people involved in this incident
/*
	if ($email_notify == 1) {
		if ($config["email_on_incident_update"] == 1){
			mail_incident ($id, $config['id_user'], 0, 0, 2);
		}
	}
*/
	
	// Copy file to directory and change name
	$file_target = $config["homedir"]."attachment/".$id_attachment."_".$filename;

	if (! copy ($file_temp, $file_target)) {
		$result_msg = ui_print_success_message(__('File cannot be saved. Please contact Integria administrator about this error'), '', true);
		$sql = sprintf ('DELETE FROM tattachment
				WHERE id_attachment = %d', $id_attachment);
		process_sql ($sql);
	} else {
		// Delete temporal file
		unlink ($file_temp);

		// Adding a WU noticing about this
		$note = "Automatic WU: Added a file to this issue. Filename uploaded: ". $filename;
		$public = 1;
		$timeused = "0.05";
		
		add_workunit_incident($id, $note, $timeused, $public);
	}
	
	return $result_msg;
}

/**
 * Update the updatetime of a incident with the current timestamp
 *
 * @param int incident id
 *
 */
 
 function update_incident_updatetime($incident_id) {
		$sql = sprintf ('UPDATE tincidencia SET actualizacion = "%s" WHERE id_incidencia = %d', print_mysql_timestamp(), $incident_id);

		process_sql ($sql);
 }
 
 /**
 * Add a workunit to an incident
 *
 * @param int incident id
 * @param string note of the workunit
 * @param string timeused
 * @param string public
 * @param int incident id
 *
 */
 
function add_workunit_incident($incident_id, $note, $timeused, $public = 1) {
	global $config;
	
	$timestamp = print_mysql_timestamp();
	
	$sql = sprintf ('INSERT INTO tworkunit (timestamp, duration, id_user, description, public) VALUES ("%s", %.2f, "%s", "%s", %d)', $timestamp, $timeused, $config['id_user'], $note, $public);

	$id_workunit = process_sql ($sql, "insert_id");
	
	if($id_workunit === false) {
		return false;
	}
	
	$sql = sprintf ('INSERT INTO tworkunit_incident (id_incident, id_workunit) VALUES (%d, %d)', $incident_id, $id_workunit);
	
	
	$result = process_sql ($sql);
	
	if($result === false) {
		$sql = sprintf ('DELETE FROM tworkunit WHERE id = %d',$id_workunit);
		return false;
	}
	
	// Update the updatetime of the incident
	update_incident_updatetime($incident_id);
	
	return true;
}

/**
 * Return an array with the incidents with a filter
 *
 * @param array List of incidents to get stats.
 * @param array/string filter for the query
 * @param bool only names or all the incidents
 *

 */
 
function get_incidents ($filter = array(), $only_names = false) {
	
	// Manage external users
	$return = enterprise_hook ('manage_external');
	if ($return !== ENTERPRISE_NOT_HOOK) {
		//Its required to use 1 = 1 because return variable starts with
		//an AND in the firts place.
		$filter_aux = "1 = 1 ".$return;

		//filter is an array so we need to iterate and create the real
		//SQL clause
		if (is_array($filter)) {

			foreach ($filter as $key => $value) {
				$filter_aux .= " AND $key = $value";
			}
		} else {
			$filter_aux .= " AND ".$filter;
		}

		//Restore filter to clause
		$filter = $filter_aux;
	}

	$all_incidents = get_db_all_rows_filter('tincidencia',$filter,'*');

	if ($all_incidents == false)
		return array ();
	
	global $config;
	$incidents = array ();
	foreach ($all_incidents as $incident) {
		// ACL pass if IR for this group or if the user is the incident creator
		if (! give_acl ($config['id_user'], $incident['id_grupo'], 'IR')
			&& ($incident['id_creator'] != $config['id_user']) )
			continue;		
		
		if ($only_names) {
			$incidents[$incident['id_incidencia']] = $incident['titulo'];
		} else {
			array_push ($incidents, $incident);
		}		
	}
	return $incidents;
}

/**
 * Return an array with the incident details, files and workunits
 *
 * @param array List of incidents to get stats.
 *
 */
 
function get_full_incident ($id_incident, $only_names = false) {
	$full_incident['details'] = get_db_row_filter('tincidencia',array('id_incidencia' => $id_incident),'*');
	$full_incident['files'] = get_incident_files ($id_incident, true);
	if($full_incident['files'] === false) {
		$full_incident['files'] = array();
	}
	$full_incident['workunits'] = get_incident_full_workunits ($id_incident);
	if($full_incident['workunits'] === false) {
		$full_incident['workunits'] = array();
	}
	
	return $full_incident;
}

/**
 * Return an array with the workunits (data included) of an incident
 *
 * @param array List of incidents to get stats.
 *
 */

function get_incident_full_workunits ($id_incident) {
	$workunits = get_db_all_rows_sql ("SELECT tworkunit.* FROM tworkunit, tworkunit_incident WHERE
		tworkunit.id = tworkunit_incident.id_workunit AND tworkunit_incident.id_incident = $id_incident
		ORDER BY id_workunit DESC");
	if ($workunits === false)
		return array ();
	return $workunits;
}

/**
 * Return an array with statistics of a given list of incidents.
 *
 * @param array List of incidents to get stats.
 #
 *

 */
function get_incidents_stats ($incidents) {
    global $config;

	$total = sizeof ($incidents);
	$opened = 0;
	$total_hours = 0;
	$total_lifetime = 0;
	$max_lifetime = 0;
	$oldest_incident = false;
    $scoring_sum = 0;
    $scoring_valid = 0;

	if ($incidents === false)
		$incidents = array ();
	foreach ($incidents as $incident) {
		if ($incident['actualizacion'] != '0000-00-00 00:00:00') {
			$lifetime = get_db_value ('UNIX_TIMESTAMP(actualizacion)  - UNIX_TIMESTAMP(inicio)',
				'tincidencia', 'id_incidencia', $incident['id_incidencia']);
			if ($lifetime > $max_lifetime) {
				$oldest_incident = $incident;
				$max_lifetime = $lifetime;
			}
			$total_lifetime += $lifetime;
		}

        // Scoring avg.
        if ($incident["score"] > 0){
            $scoring_valid++;
            $scoring_sum = $scoring_sum + $incident["score"];
        }          
		$hours = get_incident_workunit_hours  ($incident['id_incidencia']);
		$total_hours += $hours;
	}
	$closed = $total - $opened;
	$opened_pct = 0;
	$mean_work = 0;
	$mean_lifetime = 0;
	if ($total != 0) {
		$opened_pct = format_numeric ($opened / $total * 100);
		$mean_work = format_numeric ($total_hours / $total, 2);
	}
	
	if ($closed != 0) {
		$mean_lifetime = (int) ($total_lifetime / $closed) / 60;
	}
	
    // Get avg. scoring
    if ($scoring_valid > 0){
        $scoring_avg = $scoring_sum / $scoring_valid;
    } else 
        $scoring_avg = "N/A";

	// Get incident SLA compliance
	$sla_compliance = get_sla_compliance ($incidents);

    $data = array();

    $data ["total_incidents"] = $total;
    $data ["opened"] = $opened;
    $data ["closed"] = $total - $opened;
    $data ["avg_life"] = $mean_lifetime;
    $data ["avg_worktime"] = $mean_work;
    $data ["sla_compliance"] = $sla_compliance;
    $data ["avg_scoring"] = $scoring_avg;

    return $data;
}

/**
 * Print a table with statistics of a list of incidents.
 *
 * @param array List of incidents to get stats.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return Incidents stats if return parameter is true. Nothing otherwise
 */
function print_incidents_stats ($incidents, $return = false) {

    global $config;
    
	require_once ($config["homedir"]."/include/functions_graph.php");    
    
	$pdf_output = (int)get_parameter('pdf_output', 0);
	$ttl = $pdf_output+1;
	
	// Necessary for flash graphs
	include_flash_chart_script();

	// TODO: Move this function to function_graphs to encapsulate flash
	// chart script inclusion or make calls to functions_graph when want 
	// print a flash chart	

	$output = '';
	
	$total = sizeof ($incidents);
	$opened = 0;
	$total_hours = 0;
	$total_workunits = 0;
	$total_lifetime = 0;
	$max_lifetime = 0;
	$oldest_incident = false;
	$scoring_sum = 0;
	$scoring_valid = 0;

	if ($incidents === false)
		$incidents = array ();
		
		
	$assigned_users = array();
	$creator_users = array();
	
	$submitter_label = "";
	$user_assigned_label = "";
	
	$incident_id_array = array();
	
	//Initialize incident status array
	$incident_status = array();
	$incident_status[STATUS_NEW] = 0;
	$incident_status[STATUS_UNCONFIRMED] = 0;
	$incident_status[STATUS_ASSIGNED] = 0;
	$incident_status[STATUS_REOPENED] = 0;
	$incident_status[STATUS_VERIFIED] = 0;
	$incident_status[STATUS_RESOLVED] = 0;
	$incident_status[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status[STATUS_CLOSED] = 0;
	
	//Initialize priority array
	$incident_priority = array();
	$incident_priority[PRIORITY_INFORMATIVE] = 0;
	$incident_priority[PRIORITY_LOW] = 0;
	$incident_priority[PRIORITY_MEDIUM] = 0;
	$incident_priority[PRIORITY_SERIOUS] = 0;
	$incident_priority[PRIORITY_VERY_SERIOUS] = 0;
	$incident_priority[PRIORITY_MAINTENANCE] = 0;
	
	//Initialize status timing array
	$incident_status_timing = array();
	$incident_status_timing[STATUS_NEW] = 0;
	$incident_status_timing[STATUS_UNCONFIRMED] = 0;
	$incident_status_timing[STATUS_ASSIGNED] = 0;
	$incident_status_timing[STATUS_REOPENED] = 0;
	$incident_status_timing[STATUS_VERIFIED] = 0;
	$incident_status_timing[STATUS_RESOLVED] = 0;
	$incident_status_timing[STATUS_PENDING_THIRD_PERSON] = 0;
	$incident_status_timing[STATUS_CLOSED] = 0;
	
	//Initialize users time array
	$users_time = array();
	
	//Initialize groups time array
	$groups_time = array();
	
	foreach ($incidents as $incident) {
		
		$inc_stats = incidents_get_incident_stats($incident["id_incidencia"]);
		
		if ($incident['actualizacion'] != '0000-00-00 00:00:00') {
			$lifetime = $inc_stats[INCIDENT_METRIC_TOTAL_TIME];
			if ($lifetime > $max_lifetime) {
				$oldest_incident = $incident;
				$max_lifetime = $lifetime;
			}
			$total_lifetime += $lifetime;
		}
		
		//Complete incident status timing array
		foreach ($inc_stats[INCIDENT_METRIC_STATUS] as $key => $value) {
			$incident_status_timing[$key] += $value;
		}
		
		//fill users time array
		foreach ($inc_stats[INCIDENT_METRIC_USER] as $user => $time) {
			if (!isset($users_time[$user])) {
				$users_time[$user] = $time;
			} else {
				$users_time[$user] += $time;
			}
		}
		
		//Inidents by group time
		foreach ($inc_stats[INCIDENT_METRIC_GROUP] as $key => $time) {
			if (!isset($groups_time[$key])) {
				$groups_time[$key] = $time;
			} else {
				$groups_time[$key] += $time;
			}
		}
		
		//Get only id from incident filter array
		//used for filter in some functions
		array_push($incident_id_array, $incident['id_incidencia']);		

		// Take count of assigned / creator users 

		if (isset ($assigned_users[$incident["id_usuario"]]))
			$assigned_users[$incident["id_usuario"]]++;
		else
			$assigned_users[$incident["id_usuario"]] = 1;
			
		if (isset ($creator_users[$incident["id_creator"]]))
			$creator_users[$incident["id_creator"]]++;
		else
			$creator_users[$incident["id_creator"]] = 1;
			
			
    	// Scoring avg.
    	
        if ($incident["score"] > 0){
            $scoring_valid++;
            $scoring_sum = $scoring_sum + $incident["score"];
        }
            
		$hours = get_incident_workunit_hours ($incident['id_incidencia']);

	    $workunits = get_incident_workunits ($incident['id_incidencia']);
	  
		$total_hours += $hours;

		$total_workunits = $total_workunits + sizeof ($workunits);
		
		
		//Open incidents
		if ($incident["estado"] != 7) {
			$opened++;
		}
		
		//Incidents by status
		$incident_status[$incident["estado"]]++;
		
		//Incidents by priority
		$incident_priority[$incident["prioridad"]]++;
		
	}

	$closed = $total - $opened;
	$opened_pct = 0;
	$mean_work = 0;
	$mean_lifetime = 0;

	if ($total != 0) {
		$opened_pct = format_numeric ($opened / $total * 100);
		$mean_work = format_numeric ($total_hours / $total, 2);
	}
	
	$mean_lifetime = $total_lifetime / $total;
	
    // Get avg. scoring
    if ($scoring_valid > 0){
        $scoring_avg = format_numeric($scoring_sum / $scoring_valid);
    } else 
        $scoring_avg = "N/A";

	// Get incident SLA compliance
	$sla_compliance = get_sla_compliance ($incidents);
		
	//Create second table
    	
	// Find the 5 most active users (more hours worked)
	$most_active_users = get_most_active_users (8, $incident_id_array);
	
	$users_label = '';
	foreach ($most_active_users as $user) {
		$users_data[$user['id_user']] = $user['worked_hours'];
	}
	
	if(empty($most_active_users)) {
		$users_label = graphic_error(false);
		$users_label .= "<br/>N/A";
	}
	else {
		$users_label = pie3d_graph ($config['flash_charts'], $users_data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl) . "<br>".$users_label. "<br>";
	}
	
	// Find the 5 most active incidents (more worked hours)
	$most_active_incidents = get_most_active_incidents (5, $incident_id_array);
	$incidents_label = '';
	foreach ($most_active_incidents as $incident) {
		$incidents_data['#'.$incident['id_incidencia']] = $incident['worked_hours'];
		$incidents_label .= '<a class="incident_link" id="incident_link_'.
			$incident['id_incidencia'].'"
			href="index.php?sec=incidents&sec2=operation/incidents/incident&id='.$incident['id_incidencia'].'">'.
			'#'.$incident['id_incidencia'].': '.$incident['titulo']."</a> (".$incident['worked_hours']." ".
			__('Hr').") <br />";
	}
	
	if(empty($most_active_incidents)) {
		$incidents_label = graphic_error(false);
		$incidents_label .= "<br/>N/A";
	}
	else {
		$incidents_label .= "<br/>".pie3d_graph ($config['flash_charts'], $incidents_data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}

	
	// TOP X creator users
	
	$creator_assigned_data = array();

	foreach ($creator_users as $clave => $valor) {
		$creator_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($creator_assigned_data)) {
		$submitter_label .= "<br/>N/A";
	}
	else {
		$submitter_label .= "<br/>".pie3d_graph ($config['flash_charts'], $creator_assigned_data , 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}
	
	$scoring_label ="";
	$top5_scoring = get_best_incident_scoring (5, $incident_id_array);
	
	foreach ($top5_scoring as $submitter){
		$scoring_data[$submitter["id_usuario"]] = $submitter["total"];
//		$scoring_label .= $submitter["id_usuario"]." ( ".$submitter["total"]. " )<br>";
	}
	
	if(empty($top5_scoring)) {
		$scoring_label .= "<br/>N/A";
	}
	else {
		$scoring_label .= "<br/>".pie3d_graph ($config['flash_charts'], $scoring_data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	}
	
	// TOP X assigned users
	
	$user_assigned_data = array();
	
	foreach ($assigned_users as $clave => $valor) {
		$user_assigned_data["$clave ($valor)"] = $valor;
	}	
	
	if(empty($user_assigned_data)) {
		$user_assigned_label .= "<br/>N/A";
	}
	else {
		$user_assigned_label .= "<br/>".pie3d_graph ($config['flash_charts'], $user_assigned_data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);

	}
	
	// Show graph with incidents by group
	foreach ($incidents as $incident) {
			$grupo = substr(safe_output(dame_grupo($incident["id_grupo"])),0,15);

	if (!isset( $incident_group_data[$grupo]))
			 $incident_group_data[$grupo] = 0;

		$incident_group_data[$grupo] = $incident_group_data[$grupo] + 1;                                 

	}

    // Show graph with incidents by source group
	foreach ($incidents as $incident) {
			$grupo_src = substr(safe_output(dame_grupo($incident["id_group_creator"])),0,15);

	if (!isset( $incident_group_data2[$grupo_src]))
			 $incident_group_data2[$grupo_src] = 0;

		$incident_group_data2[$grupo_src] = $incident_group_data2[$grupo_src] + 1;                                 

	}
	
	//Print first table
    $output = "<table class=blank width=80% cellspacing=4 cellpadding=0 border=0 >";
    $output .= "<tr>";
    $output .= "<td valign=top align=left  colspan=2>";
		$output .= "<table width=190px border=1 cellspacing=4 cellpadding=0 border=0 >";
		$output .= "<tr>";
		$output .= "<th align=center>".__('Total incidents')."</th>";
		$output .= "<th align=center>".__('Avg. life time')."</th>";
		$output .= "</tr><tr>";
		$output .= "<td valign=top align=center>";
		$output .= $total;
		$output .= "</td><td valign=top align=center>";
		$output .= format_numeric ($mean_lifetime / 86400 , 2). " ". __("Days");
		$output .= "</td>";
		$output .= "<tr>";
		$output .= "<th align=center>";
		$output .= __('Avg. work time');
		$output .= "</th>";
		$output .= "<th align=center>";
		$output .= __('Avg. Scoring');
		$output .= "</th>";
		$output .= "</tr><tr>";
		$output .= "<tr>";
		$output .= "<td align=center>".$mean_work.' '.__('Hours')."</td>";
		$output .= "<td align=center>".$scoring_avg."</td>";	
		$output .= "<tr>";
		$output .= "<th align=center>";
		$output .= __('Total work time');
		$output .= "</th>";
		$output .= "<th align=center>";
		$output .= __('Total work units');
		$output .= "</th>";
		$output .= "</tr><tr>";
		$output .= "<tr>";
		
		$output .= "<td align=center>".$total_hours . " " . __("Hours")."</td>";
		$output .= "<td align=center>".$total_workunits."</td>";
		$output .= "</tr></table>";
		
		
	$output .= "</td>";
    $output .= "<td valign=top  colspan=2>";
    $output .= print_label (__('Top 5 active incidents'), '', '', true, $incidents_label);
    $output .= "</td>";
	$output .= "<td valign=top>";
	$output .= print_label (__('SLA compliance'), '', '', true, format_numeric ($sla_compliance) .' '.__('%'));
    $output .= graph_incident_statistics_sla_compliance($incidents, 300, 150, $ttl);    
    $output .= "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    
        
    $status_aux = print_label (__('Incident by status'), '', '', true);    
    
    $status_aux .= "<table style='width: 420px; margin: 10px auto;'>";
	$status_aux .= "<tr>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Status")."</strong></th>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Number")."</strong></th>";
	$status_aux .= "<th style='text-align:center;'><strong>".__("Total time")."</strong></th>";
	$status_aux .= "</tr>";
	
		foreach ($incident_status as $key => $value) {
			$name = get_db_value ('name', 'tincident_status', 'id', $key);
			$status_aux .= "<tr>";
			$status_aux .= "<td>".$name."</td>";
			$status_aux .= "<td style='text-align:center;'>".$value."</td>";
			$time = $incident_status_timing[$key];
			$status_aux .= "<td style='text-align:center;'>".give_human_time($time,true,true,true)."</td>";
			$status_aux .= "</tr>";
		}
		
    $status_aux .= "</table>";
    
	$priority_aux = print_label (__('Incidents by priority'), '', '', true);
		
	$priority_aux .= "<table style='width: 420px; margin: 10px auto;'>";
	
	$priority_aux .= "<tr>";
	$priority_aux .= "<th style='text-align:center;'><strong>".__("Priority")."</strong></th>";
	$priority_aux .= "<th style='text-align:center;'><strong>".__("Number")."</strong></th>";
	$priority_aux .= "</tr>";	
	
		foreach ($incident_priority as $key => $value) {
			$priority_aux .= "<tr>";
			$priority_aux .= "<td>".render_priority ($key)."</td>";
			$priority_aux .= "<td style='text-align:center;'>".$value."</td>";
			$priority_aux .= "</tr>";
		}

	$priority_aux .= "</table>";
    
    $output .= "<td colspan=3 valign=top>".$status_aux."</td>";
    $output .= "<td colspan=3 valign=top>".$priority_aux."</td>";
   
	$output .= "<tr>";
	$output .= "<td valign=top colspan=2>";
	$output .= print_label (__('Longest closed incident'), '', '', true);
	if ($oldest_incident) {
		
        $oldest_incident_time = get_incident_workunit_hours  ($oldest_incident["id_incidencia"]);
		$output .= '<strong><a href="index.php?sec=incidents&sec2=operation/incidents/incident&id='.
			$oldest_incident['id_incidencia'].'">Incident #'.$oldest_incident['id_incidencia']. " : ".$oldest_incident['titulo']. "</strong></a>";
        $output .= "<br>".__("Worktime hours"). " : ".$oldest_incident_time. " ". __("Hours");
		$output .= "<br>".__("Lifetime"). " : ".format_numeric($max_lifetime/86400). " ". __("Days");
            
	}	else  {
		$output .= "<em>".__("N/A")."</em>";
	}
	$output .= "</td>"; 
	
	$output .= "<td valign=top colspan=2>";
	$data = array (__('Open') => $opened, __('Closed') => $total - $opened);
	$data = array (__('Close') => $total-$opened, __('Open') => $opened);
    $output .= print_label (__('Open'), '', '', true, $opened.' ('.$opened_pct.'%)');
    $output .= pie3d_graph ($config['flash_charts'], $data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize'], $ttl);
	$output .= "</td>";
	
	$output .= "<td colspan=2></td>";
    $output .= "</tr></table>";
 
	$clean_output = get_parameter("clean_output");
 
	if ($clean_output) {
		echo '<h2>'.__("Incidents statistics").'</h2>';
	} else {
		echo '<h2 onclick="toggleDiv (\'inc-stats\')" class="incident_dashboard">'.__("Incidents statistics").'</h2>';
	}
    echo "<div id='inc-stats'>";
	echo $output;
	echo "</div>";	
	
	//Print second table
    $output = "<table class=blank width=80% cellspacing=4 cellpadding=0 border=0>";
    $output .= "<tr>";	
	$output .= "<td width=33% valign=top colspan=2>";
	$output .= print_label (__('Top active users'), '', '', true, $users_label);
	$output .= "</td>";
	$output .= "<td width=33%  valign=top colspan=2>";
	$output .= print_label (__('Top incident submitters'), '', '', true, $submitter_label );
	
	$output .= "</td>";
	$output .= "<td width=33%  valign=top colspan=2>";
	$output .= print_label (__('Top assigned users'), '', '', true, $user_assigned_label);	
	$output .= "</td></tr>";
	$output .= "<tr><td valign=top colspan=2>";
	$output .= print_label (__('Incidents by group'), '', '', true);
	$output .= "<br/>".pie3d_graph ($config['flash_charts'], $incident_group_data, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
	$output .= "</td>";
	
	$output .= "<td valign=top colspan=2>";
	$output .= print_label (__('Incidents by creator group'), '', '', true);
	$output .= "<br/>".pie3d_graph ($config['flash_charts'], $incident_group_data2, 300, 150, __('others'), "", "", $config['font'], $config['fontsize']-1, $ttl);
	$output .= "</td>";

	$output .= "<td valign=top colspan=2>";
	$output .= print_label (__('Top 5 average scoring by user'), '', '', true, $scoring_label);
	$output .= "</td>";
	
	$output .= "<tr>";
	$output .= "<td style='width: 50%' colspan=3>";
		$output .= print_label (__('Top 5 group by time'), '', '', true);
		$output .="<table style='width: 420px; margin: 10 auto'>";
		
		$output .= "<tr>";
		$output .= "<th style='text-align:center;'><strong>".__("Group")."</strong></th>";
		$output .= "<th style='text-align:center;'><strong>".__("Time")."</strong></th>";
		$output .= "</tr>";
		
		$count = 1;
		arsort($groups_time);
		foreach ($groups_time as $key => $value) {
			
			//Only show first 5
			if ($count == 5) {
				break;
			}
			
			$output .= "<tr>";
			$group_name = get_db_value ('nombre', 'tgrupo', 'id_grupo', $key);
			$output .= "<td>".$group_name."</td>";
			$output .= "<td style='text-align: center'>".give_human_time($value,true,true,true)."</td>";
			$output .= "</tr>";
			$count++;
		}	
		
		$output .= "</table>";
		
	$output .="</td>";

	$output .= "<td valign=top style='width: 50%' colspan=3>";
		$output .= print_label (__('Top 5 users by time'), '', '', true);
		$output .="<table style='width: 420px; margin: 10px auto;'>";
		
		$output .= "<tr>";
		$output .= "<th style='text-align:center;'><strong>".__("User")."</strong></th>";
		$output .= "<th style='text-align:center;'><strong>".__("Time")."</strong></th>";
		$output .= "</tr>";
		
		$count = 1;
		arsort($groups_time);
		foreach ($users_time as $key => $value) {
			
			//Only show first 5
			if ($count == 5) {
				break;
			}
			
			$output .= "<tr>";
			$user_real = get_db_value ('nombre_real', 'tusuario', 'id_usuario', $key);
			$output .= "<td>".$user_real."</td>";
			$output .= "<td style='text-align: center'>".give_human_time($value,true,true,true)."</td>";
			$output .= "</tr>";
			$count++;
		}	
		
		$output .= "</table>";

	$output .= "</td>";
	
	$output .= "</tr></table>";
	
	if ($clean_output) {
		echo '<h2>'.__("Users statistics").'</h2>';
	} else {
		echo '<h2 onclick="toggleDiv (\'user-stats\')" class="incident_dashboard">'.__("Users statistics").'</h2>';
	}
	
    echo "<div id='user-stats'>";
	echo $output;	
	echo "</div>";
}

/**
 * Update affected inventory objects in an incident.
 *
 * @param int Incident id to update.
 * @param array List of affected inventory objects ids.
 */
function update_incident_inventories ($id_incident, $inventories) {
	error_reporting (0);
	$where_clause = '';
	
	if (empty ($inventories)) {
		$inventories = array (0);
	}
	
	$sql = sprintf ('DELETE FROM tincident_inventory
		WHERE id_incident = %d',
		$id_incident);
	
	process_sql ($sql);
	
	foreach ($inventories as $id_inventory) {
		$sql = sprintf ('INSERT INTO tincident_inventory
			VALUES (%d, %d)',
			$id_incident, $id_inventory);
		$tmp = process_sql ($sql);
		
		if ($tmp !== false)
			incident_tracking ($id_incident, INCIDENT_INVENTORY_ADDED,
				$id_inventory);
				
			inventory_tracking ($id_inventory, INVENTORY_INCIDENT_ADDED, $id_incident);
	}
}

/**
 * Update contact reporters in an incident.
 *
 * @param int Incident id to update.
 * @param array List of contacts ids.
 */
function update_incident_contact_reporters ($id_incident, $contacts) {
	error_reporting (0);
	$where_clause = '';
	
	if (empty ($contacts)) {
		$contacts = array (0);
	}
	$where_clause = sprintf ('AND id_contact NOT IN (%s)',
		implode (',', $contacts));
	
	$sql = sprintf ('DELETE FROM tincident_contact_reporters
		WHERE id_incident = %d %s',
		$id_incident, $where_clause);
	process_sql ($sql);
	foreach ($contacts as $id_contact) {
		$sql = sprintf ('INSERT INTO tincident_contact_reporters
			VALUES (%d, %d)',
			$id_incident, $id_contact);
		$tmp = process_sql ($sql);
		if ($tmp !== false)
			incident_tracking ($id_incident, INCIDENT_CONTACT_ADDED,
				$id_contact);
	}
}

/**
 * Get all the contacts who reported a incident
 *
 * @param int Incident id.
 * @param bool Wheter to return only the contact names (indexed by id) or all
 * the data.
 *
 * @return array An array with all the contacts who reported the incident. Empty
 * array if none was set.
 */
function get_incident_contact_reporters ($id_incident, $only_names = false) {
	$sql = sprintf ('SELECT tcompany_contact.*
		FROM tcompany_contact, tincident_contact_reporters
		WHERE tcompany_contact.id = tincident_contact_reporters.id_contact
		AND id_incident = %d', $id_incident);
	$contacts = get_db_all_rows_sql ($sql);
	if ($contacts === false)
		return array ();
	
	if ($only_names) {
		$retval = array ();
		foreach ($contacts as $contact) {
			$retval[$contact['id']] = $contact['fullname'];
		}
		return $retval;
	}
	
	return $contacts;
}


/**
* Return total hours assigned to incident
*
* $id_inc       integer         ID of incident
**/

function get_incident_workunit_hours ($id_incident) {
	global $config;
	$sql = sprintf ('SELECT SUM(tworkunit.duration) 
		FROM tworkunit, tworkunit_incident, tincidencia 
		WHERE tworkunit_incident.id_incident = tincidencia.id_incidencia
		AND tworkunit_incident.id_workunit = tworkunit.id
		AND tincidencia.id_incidencia = %d', $id_incident);
	
	return (float) get_db_sql ($sql);
}


/**
 * Return the last entered WU in a given incident
 *
 * @param int Incident id
 *
 * @return array WU structure
 */

function get_incident_lastworkunit ($id_incident) {
	$workunits = get_incident_workunits ($id_incident);
	if (!isset($workunits[0]['id_workunit']))
		return;
	$workunit_data = get_workunit_data ($workunits[0]['id_workunit']);
	return $workunit_data;
}


function mail_incident ($id_inc, $id_usuario, $nota, $timeused, $mode, $public = 1){
	global $config;
	
	$row = get_db_row ("tincidencia", "id_incidencia", $id_inc);
	$group_name = get_db_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = ".$row["id_grupo"]);
	$titulo =$row["titulo"];
	$description = wordwrap(ascii_output($row["descripcion"]), 70, "\n");
	$prioridad = render_priority($row["prioridad"]);
	$nota = wordwrap($nota, 75, "\n");

	$estado = render_status ( $row["estado"]);
	$resolution = render_resolution ($row["resolution"]);
	$create_timestamp = $row["inicio"];
	$update_timestamp = $row["actualizacion"];
	$usuario = $row["id_usuario"];
	$creator = $row["id_creator"];
    $email_copy = $row["email_copy"];

	// Send email for owner and creator of this incident
	$email_creator = get_user_email ($creator);
	$company_creator = get_user_company ($creator, true);
	if(empty($company_creator)) {
		$company_creator = "";
	}
	else {
		$company_creator = " (".reset($company_creator).")";
	}
	
	$email_owner = get_user_email ($usuario);
	$company_owner = get_user_company ($usuario, true);
	if(empty($company_owner)) {
		$company_owner = "";
	}
	else {
		$company_owner = " (".reset($company_owner).")";
	}
  
	$MACROS["_sitename_"] = $config["sitename"];
	$MACROS["_fullname_"] = dame_nombre_real ($usuario);
	$MACROS["_username_"] = $usuario;
	$MACROS["_incident_id_"] = $id_inc;
	$MACROS["_incident_title_"] = $titulo;
	$MACROS["_creation_timestamp_"] = $create_timestamp;
	$MACROS["_update_timestamp_"] = $update_timestamp;
	$MACROS["_group_"] = $group_name ;
	$MACROS["_author_"] = dame_nombre_real ($creator).$company_creator;
	$MACROS["_owner_"] = dame_nombre_real ($usuario).$company_owner;
	$MACROS["_priority_"] = $prioridad ;
	$MACROS["_status_"] = $estado;
	$MACROS["_resolution_"] = $resolution;
	$MACROS["_time_used_"] = $timeused;
	$MACROS["_incident_main_text_"] = $description;
	$MACROS["_access_url_"] = $config["base_url"]."/index.php?sec=incidents&sec2=operation/incidents/incident&id=$id_inc";

	// Resolve code for its name
	switch ($mode){
	case 10: // Add Workunit
		//$subject = "[".$config["sitename"]."] Incident #$id_inc ($titulo) has a new workunit from [$id_usuario]";
		$company_wu = get_user_company ($id_usuario, true);
		if(empty($company_wu)) {
			$company_wu = "";
		}
		else {
			$company_wu = " (".reset($company_wu).")";
		}
		$MACROS["_wu_user_"] = dame_nombre_real ($id_usuario).$company_wu;
		$MACROS["_wu_text_"] = $nota ;
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update_wu.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_new_wu.tpl", $MACROS);
		break;
	case 0: // Incident update
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_update.tpl", $MACROS);
		break;
	case 1: // Incident creation
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_create.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_create.tpl", $MACROS);
		break;
	case 2: // New attach
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_attach.tpl", $MACROS);
		break;
	case 3: // Incident deleted 
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_update.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_delete.tpl", $MACROS);
		break;
    case 5: // Incident closed
		$text = template_process ($config["homedir"]."/include/mailtemplates/incident_close.tpl", $MACROS);
		$subject = template_process ($config["homedir"]."/include/mailtemplates/incident_subject_close.tpl", $MACROS);
        break;
   }
		
		
	// Create the TicketID for have a secure reference to incident hidden 
	// in the message. Will be used for POP automatic processing to add workunits
	// to the incident automatically.

	$msg_code = "TicketID#$id_inc";
	$msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] . $row["id_usuario"]),0,5);
	$msg_code .= "/" . $row["id_usuario"];;

	integria_sendmail ($email_owner, $subject, $text, false, $msg_code);

    // Send a copy to each address in "email_copy"

    if ($email_copy != ""){
        $emails = explode (",",$email_copy);
        foreach ($emails as $em){
        	integria_sendmail ($em, $subject, $text, false, "");
        }
    }

	// Incident owner
	if ($email_owner != $email_creator){

    	$msg_code = "TicketID#$id_inc";
	$msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] . $row["id_creator"]),0,5);
    	$msg_code .= "/".$row["id_creator"];

	integria_sendmail ($email_creator, $subject, $text, false, $msg_code);
    }	
	if ($public == 1){
		// Send email for all users with workunits for this incident
		$sql1 = "SELECT DISTINCT(tusuario.direccion), tusuario.id_usuario FROM tusuario, tworkunit, tworkunit_incident WHERE tworkunit_incident.id_incident = $id_inc AND tworkunit_incident.id_workunit = tworkunit.id AND tworkunit.id_user = tusuario.id_usuario";
		if ($result=mysql_query($sql1)) {
			while ($row=mysql_fetch_array($result)){
				if (($row[0] != $email_owner) AND ($row[0] != $email_creator)){
                    
                    $msg_code = "TicketID#$id_inc";
            	    $msg_code .= "/".substr(md5($id_inc . $config["smtp_pass"] .  $row[1]),0,5);
                	$msg_code .= "/". $row[1];

					integria_sendmail ( $row[0], $subject, $text, false, $msg_code);
                }
			}
		}

        // Send email to incident reporters associated to this incident
        if ($config['incident_reporter'] == 1){
        	$contacts = get_incident_contact_reporters ($id_inc , true);
			if ($contats)
            foreach ($contacts as $contact) {
                $contact_email = get_db_sql ("SELECT email FROM tcompany_contact WHERE fullname = '$contact'");
                integria_sendmail ($contact_email, $subject, $text, false, $msg_code);
            }
	    }
    }
}

function people_involved_incident ($id_inc){
	global $config;
	$row0 = get_db_row ("tincidencia", "id_incidencia", $id_inc);
	$people = array();
	
	array_push ($people, $row0["id_creator"]);
	 if (!in_array($row0["id_usuario"], $people)) {	
		array_push ($people, $row0["id_usuario"]);
	}
	
	// Take all users with workunits for this incident
	$sql1 = "SELECT DISTINCT(tusuario.id_usuario) FROM tusuario, tworkunit, tworkunit_incident WHERE tworkunit_incident.id_incident = $id_inc AND tworkunit_incident.id_workunit = tworkunit.id AND tworkunit.id_user = tusuario.id_usuario";
	if ($result = mysql_query($sql1)) {
		while ($row = mysql_fetch_array($result)){
			if (!in_array($row[0], $people))
				array_push ($people, $row[0]);
		}
	}
	
	return $people;
}

// Return TRUE if User has access to that incident

function user_belong_incident ($user, $id_inc) {
	return in_array($user, people_involved_incident ($id_inc));
}


/** 
 * Returns the n top creator users (users who create a new incident).
 *
 * @param lim n, number of users to return.
 */
function get_most_incident_creators ($lim, $incident_filter = false) {
	$sql = 'SELECT id_creator, count(*) AS total FROM tincidencia ';
	
	if ($incident_filter) {
		$filter_clause = join(",", $incident_filter);
		$sql .= ' WHERE id_incidencia IN ('.$filter_clause.') ';
	}
	
	$sql .= ' GROUP by id_creator ORDER BY total DESC LIMIT '. $lim;
	
	$most_creators = get_db_all_rows_sql ($sql);
	if ($most_creators === false) {
		return array ();
	}
	
	return $most_creators;
}

/** 
 * Returns the n top incident owner by scoring (users with best scoring).
 *
 * @param lim n, number of users to return.
 */
function get_best_incident_scoring ($lim, $incident_filter=false) {
	$sql = 'SELECT id_usuario, AVG(score) AS total FROM tincidencia';
	
	$filter_clause = '';
		
	if ($incident_filter) {
		
		$filter_clause = join(",", $incident_filter);
		$sql .= ' WHERE id_incidencia IN ('.$filter_clause.')';
	}
	
	$sql .= ' GROUP by id_usuario ORDER BY total DESC LIMIT '. $lim;
	
	$most_creators = get_db_all_rows_sql ($sql);
	
	$all_zero = true;
	
	foreach ($most_creators as $mc) {
		if ($mc['total'] != 0) {
			$all_zero = false;
			break;
		}
	}
	
	if ($most_creators === false || $all_zero) {
		
		return array ();
	}
	
	return $most_creators;
}

/*
 * Returns all incident type fields.
 */ 
function incidents_get_all_type_field ($id_incident_type, $id_incident) {
	
	global $config;
	
	$fields = get_db_all_rows_filter('tincident_type_field', array('id_incident_type' => $id_incident_type));
	
	if ($fields === false) {
		$fields = array();
	}
	
	$all_fields = array();
	foreach ($fields as $id=>$field) {
		foreach ($field as $key=>$f) {

			if ($key == 'label') {
				$all_fields[$id]['label_enco'] = base64_encode($f);
			}
			$all_fields[$id][$key] = safe_output($f);
		}
	}

	foreach ($all_fields as $key => $field) {
		$id_incident_field = $field['id'];
		$data = get_db_value_filter('data', 'tincident_field_data', array('id_incident'=>$id_incident, 'id_incident_field' => $id_incident_field), 'AND');
		if ($data === false) {
			$all_fields[$key]['data'] = '';
		} else {
			$all_fields[$key]['data'] = $data;
		}
	}

	return $all_fields;
	
}

function incidents_metric_to_state($metric) {
	
	$state = "";
	
	switch ($metric) {
		case INCIDENT_METRIC_USER:
			$state = INCIDENT_USER_CHANGED;
			
			break;
		case INCIDENT_METRIC_GROUP:
			$state = INCIDENT_GROUP_CHANGED;
			
			break;
		case INCIDENT_METRIC_STATUS:
			$state = INCIDENT_STATUS_CHANGED;
			
			break;
		default:
			break;
	}
	
	return $state;
}

function incidents_state_to_metric($state) {
	$metric = "";
	
	switch ($state) {
		case INCIDENT_USER_CHANGED:
			$metric = INCIDENT_METRIC_USER;
			
			break;
		case INCIDENT_GROUP_CHANGED:
			$metric = INCIDENT_METRIC_GROUP;
			
			break;
		case INCIDENT_STATUS_CHANGED:
			$metric = INCIDENT_METRIC_STATUS;
			
			break;
		default:
			break;
	}
		
	return $metric;
}

/*Adds incident statistics traces*/
function incidents_add_incident_stat ($id_incident, $metrics_values) {
	
	//Calculate time diff to update stats
	
	$last_incident_update = get_db_value ("last_stat_check", "tincidencia", "id_incidencia", $id_incident);
	
	//Calculate time difference
	if ($last_incident_update = "0000-00-00 00:00:00") {
		//Incident created right now!
		$now = time();
		$last_incident_update_time = $now;
	} else {
		//Incident was updated at least once
		$now = time();
		$last_incident_update_time = strtotime($last_incident_update);
		$diff_time = $now - $last_incident_update_time;
	}
	
	$holidays_seconds = incidents_get_holidays_seconds_by_timerange($last_incident_update_time, $now);
	
	$diff_time = $diff_time - $holidays_seconds;
	
	foreach ($metrics_values as $metric => $value) {
		
		$row_sql = sprintf("SELECT * FROM tincident_stats WHERE id_incident = %d AND metric = '%s' 
							ORDER BY id DESC", $id_incident, $metric);
		$row = get_db_row_sql($row_sql);
		
		//If there is no data in tincident_stats table just insert because is the first data
		if (!$row) {
		
			$values = array("id_incident" => $id_incident,
							"metric" => $metric);
							
			switch ($metric) {
				case INCIDENT_METRIC_USER: 
					$values["id_user"] = $value;
					break;
				case INCIDENT_METRIC_STATUS:
					$values["status"] = $value;
					break;
				case INCIDENT_METRIC_GROUP:
					$values["id_group"] = $value;
					break;	
				default:
					break;
			}
		
			process_sql_insert ("tincident_stats", $values);
		} else {
			//Get last timestamp from track table
			$sql = sprintf("SELECT * FROM tincident_track WHERE id_incident = %d ORDER BY timestamp DESC", $id_incident);
			$timestamp_row = get_db_row_sql($sql);
			
			$track_timestmap = $timestamp_row["timestamp"];
			
			$state = incidents_metric_to_state($metric);
			
			//Get previous state from track table		
			$sql_track_trace = sprintf("SELECT * FROM tincident_track WHERE id_incident = %d AND 
										state = %d AND timestamp < '%s' ORDER BY timestamp DESC", $id_incident, $state, $track_timestmap);
			
			$last_track_trace = get_db_row_sql($sql_track_trace);
			
			$metric = incidents_state_to_metric($last_track_trace["state"]);
			
			//We need to search for statistic data based on track "id_additional"
			$previous_stats_values = array ('id_incident' => $id_incident, "metric" => $metric);
			
			switch ($metric) {
				case INCIDENT_METRIC_USER: 
					$previous_stats_values["id_user"] = $last_track_trace["id_aditional"];
					break;
				case INCIDENT_METRIC_STATUS:
					$previous_stats_values["status"] = $last_track_trace["id_aditional"];
					$previous_status = $last_track_trace["id_aditional"];
					break;
				case INCIDENT_METRIC_GROUP:
					$previous_stats_values["id_group"] = $last_track_trace["id_aditional"];
					break;	
				default:
					break;
			}
			
			$previous_stats_data = get_db_row_filter ("tincident_stats", $previous_stats_values);
			
			if ($previous_stats_data) {
				
				//We have previous data for this stat, so update it
				$val_upd_time = array("seconds" => $previous_stats_data["seconds"]+$diff_time);
				$val_upd_time_where = array("id" => $previous_stats_data["id"]);

				process_sql_update("tincident_stats", $val_upd_time, $val_upd_time_where);
				
				
			} else {
				
				//There isn't previous data for this stat
							
				//Create new stat metric
				$val_new_metric = array("id_incident" => $last_track_trace["id_incident"],
										"seconds" => $diff_time,
										"metric" => $metric);

				switch ($metric) {
					case INCIDENT_METRIC_USER: 
						$val_new_metric["id_user"] = $previous_stats_values["id_user"];
						break;
					case INCIDENT_METRIC_STATUS:
						$val_new_metric["status"] = $previous_stats_values["status"];
						break;
					case INCIDENT_METRIC_GROUP:
						$val_new_metric["id_group"] = $previous_stats_values["id_group"];
						break;	
					default:
						break;
				}
			
				process_sql_insert("tincident_stats", $val_new_metric);
				
			}		
			
		}
		
	}
	
	//Calculate total time for statistics
	$row_sql = sprintf("SELECT * FROM tincident_stats WHERE id_incident = %d AND metric = '%s'", $id_incident, INCIDENT_METRIC_TOTAL_TIME);
	$row = get_db_row_sql($row_sql);
	
	//Check if we have a previous stat metric to update or create it
	if ($row) {
		$val_upd_time = array("seconds" => $row["seconds"]+$diff_time);
		$val_upd_time_where = array("id" => $row["id"]);
		process_sql_update("tincident_stats", $val_upd_time, $val_upd_time_where);	
	} else {
		$val_new_metric = array("seconds" => 0,
								"metric" => INCIDENT_METRIC_TOTAL_TIME,
								"id_incident" => $id_incident);
		process_sql_insert("tincident_stats", $val_new_metric);
	}

	//Calculate total time without waiting for third companies
	$filter = array(
				"metric" => INCIDENT_METRIC_TOTAL_TIME, 
				"id_incident" => $id_incident);
	$total_time = get_db_value_filter ("seconds", "tincident_stats", $filter);
	
	$filter = array(
				"metric" => INCIDENT_METRIC_STATUS, 
				"status" => STATUS_PENDING_THIRD_PERSON, 
				"id_incident" => $id_incident);
	$third_time = get_db_value_filter ("seconds", "tincident_stats", $filter);

	if (!$third_time) {
		$third_time = 0;
	}
	
	$diff_time = $total_time - $third_time;
	$row_sql = sprintf("SELECT * FROM tincident_stats WHERE id_incident = %d AND metric = '%s'", $id_incident, INCIDENT_METRIC_TOTAL_TIME_NO_THIRD);
	$row = get_db_row_sql($row_sql);

	//Check if we have a previous stat metric to update or create it
	if ($row) {
		//Only update for status different from "PEDING ON THIRD PERSON"
		if ($previous_status != STATUS_PENDING_THIRD_PERSON) {
			
			$val_upd_time = array("seconds" => $diff_time);
			$val_upd_time_where = array("id" => $row["id"]);
			process_sql_update("tincident_stats", $val_upd_time, $val_upd_time_where);	
		}
	} else {
		$val_new_metric = array("seconds" => 0,
								"metric" => INCIDENT_METRIC_TOTAL_TIME_NO_THIRD,
								"id_incident" => $id_incident);
		process_sql_insert("tincident_stats", $val_new_metric);
	}

	//Update last_incident_update field from tincidencia
	$now_date = date("Y-m-d H:i:s", time()); 
	$update_values = array("last_stat_check" => $now_date);
	process_sql_update("tincidencia", $update_values, 
						array("id_incidencia" => $id_incident));

}

function incidents_get_incident_status_text ($id) {
	$status = get_db_value ('estado', 'tincidencia', 'id_incidencia', $id);
	
	$name = get_db_value ('name', 'tincident_status', 'id', $status);
	
	return $name;
}

function incidents_get_incident_priority_text ($id) {
	
	
	$priority = get_db_value ('prioridad', 'tincidencia', 'id_incidencia', $id);
	
	$name = render_priority($priority);
	
	return $name;
}

function incidents_get_incident_group_text ($id) {
	$group = get_db_value ('id_grupo', 'tincidencia', 'id_incidencia', $id);
	
	$name = get_db_value ('nombre', 'tgrupo', 'id_grupo', $group);
	
	return $name;	
}

function incidents_get_incident_resolution_text ($id) {
	$resolution = get_db_value ('resolution', 'tincidencia', 'id_incidencia', $id);
	
	if ($resolution == 0) {
		$name = __("None");
	} else {
		$name = get_db_value ('name', 'tincident_resolution', 'id', $resolution);
	}
	
	return $name;	
}

function incident_create_attachment ($id_incident, $user, $filename, $path, $description) {
	
	$filesize = filesize($path); // In bytes
	$now = date ("Y-m-d H:i:s", time());
	
	$sql = sprintf ('INSERT INTO tattachment (id_incidencia, id_usuario,
			filename, description, size, `timestamp`)
			VALUES (%d, "%s", "%s", "%s", %d, "%s")',
			$id_incident, $user, $filename, $description, $filesize, $now);
	
	$id = process_sql ($sql, 'insert_id');
	
	return $id;
}

function incidents_get_incident_type_text ($id) {
	$type = get_db_value ('id_incident_type', 'tincidencia', 'id_incidencia', $id);
	
	if ($type == 0) {
		$name = __("None");
	} else {
		$name = get_db_value ('name', 'tincident_type', 'id', $type);
	}
	
	return $name;	
}

function incident_get_type_field_values($id) {
	$id_type = get_db_value ('id_incident_type', 'tincidencia', 'id_incidencia', $id);
	
	$sql = sprintf("SELECT TF.label, FD.data FROM tincident_field_data FD, tincident_type_field TF 
					WHERE TF.id_incident_type = %d AND TF.id = FD.id_incident_field 
					AND FD.id_incident = %d", $id_type, $id);
					
	$fields = get_db_all_rows_sql($sql);
	
	if (!$fields) {
		$fields = array();
	}
	
	$ret_fields = array();

	foreach ($fields as $f) {
		$ret_fields[$f["label"]] = $f["data"];
	}
	
	return $ret_fields;
}

function incidents_get_incident_task_text ($id) {
	
	$task = get_db_value ('id_task', 'tincidencia', 'id_incidencia', $id);
		
	if ($task) {
		$name = get_db_value ('name', 'ttask', 'id', $task);
	} else {
		$name = __("None");
	}
	
	return $name;
}

function incidents_get_incident_stats ($id) {
	
	//Get all incident
	$raw_stats = get_db_all_rows_filter('tincident_stats', array('id_incident' => $id));
	
	//Sort incident by type and metric into a hash table :)
	$stats = array();
	
	$stats[INCIDENT_METRIC_USER] = array();
	$stats[INCIDENT_METRIC_STATUS] = array(
							STATUS_NEW => 0,
							STATUS_UNCONFIRMED => 0,
							STATUS_ASSIGNED => 0,
							STATUS_REOPENED => 0,
							STATUS_VERIFIED => 0,
							STATUS_RESOLVED => 0,
							STATUS_PENDING_THIRD_PERSON => 0,
							STATUS_CLOSED => 0);
							
	$stats[INCIDENT_METRIC_GROUP] = array();
	$stats[INCIDENT_METRIC_TOTAL_TIME] = 0;
	$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] = 0;

	foreach ($raw_stats as $st) {
		
			switch ($st["metric"]) {
				case INCIDENT_METRIC_USER: 
					$stats[INCIDENT_METRIC_USER][$st["id_user"]] = $st["seconds"];
					break;
				case INCIDENT_METRIC_STATUS:
					$stats[INCIDENT_METRIC_STATUS][$st["status"]] = $st["seconds"];
					break;
				case INCIDENT_METRIC_GROUP:
					$stats[INCIDENT_METRIC_GROUP][$st["id_group"]] =$st["seconds"];
					break;	
				case INCIDENT_METRIC_TOTAL_TIME_NO_THIRD: 
					$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] = $st["seconds"];
					break;	
				case INCIDENT_METRIC_TOTAL_TIME:
					$stats[INCIDENT_METRIC_TOTAL_TIME] = $st["seconds"];
					break;
						
				default:
					break;
			}
	}
	
	//Get last metrics and update times until now
	$now = time();
	
	//Get last incident update check for total time metric
	$time_str = get_db_value_filter("last_stat_check", "tincidencia", array("id_incidencia" => $id));
	$unix_time = strtotime($time_str);
	$global_diff = ($now - $unix_time); //Time diff in seconds
	
	//Get non-working days from last stat update and delete the seconds :)
	$last_stat_check = get_db_value("last_stat_check", "tincidencia", "id_incidencia", $id);
	$last_stat_check_time = strtotime($last_stat_check);
	
	$holidays_seconds = incidents_get_holidays_seconds_by_timerange($last_stat_check_time, $now);
		
	$global_diff = $global_diff - $holidays_seconds;

	$stats[INCIDENT_METRIC_TOTAL_TIME] = $stats[INCIDENT_METRIC_TOTAL_TIME] + $global_diff;	
	
	//Fix last time track per metric	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_USER_CHANGED,$id);
	$last_track_user_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff	
	if (isset($stats[INCIDENT_METRIC_USER][$last_track_user_id])) {	
		$stats[INCIDENT_METRIC_USER][$last_track_user_id] = $stats[INCIDENT_METRIC_USER][$last_track_user_id] + $global_diff;
	} else {
		$stats[INCIDENT_METRIC_USER][$last_track_user_id] = $global_diff;
	}
	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_GROUP_CHANGED,$id);
	$last_track_group_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff
	if (isset($stats[INCIDENT_METRIC_GROUP][$last_track_group_id])) {	
		$stats[INCIDENT_METRIC_GROUP][$last_track_group_id] = $stats[INCIDENT_METRIC_GROUP][$last_track_group_id] + $global_diff;

	} else {
		$stats[INCIDENT_METRIC_GROUP][$last_track_group_id] = $global_diff;
	}
	
	$sql = sprintf("SELECT id_aditional FROM tincident_track WHERE state = %d AND id_incident = %d ORDER BY timestamp DESC LIMIT 1", INCIDENT_STATUS_CHANGED,$id);
	$last_track_status_id = get_db_sql($sql, "id_aditional");

	//If defined sum if not just assign the diff
	if (isset($stats[INCIDENT_METRIC_STATUS][$last_track_status_id])) {
		$stats[INCIDENT_METRIC_STATUS][$last_track_status_id] = $stats[INCIDENT_METRIC_STATUS][$last_track_status_id] + $global_diff;
	} else {
		$stats[INCIDENT_METRIC_STATUS][$last_track_status_id] = $global_diff;
	}
	
	//If status not equal to pending on third person add this time to metric
	if ($last_track_status_id != STATUS_PENDING_THIRD_PERSON) {
		$stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] = $stats[INCIDENT_METRIC_TOTAL_TIME_NO_THIRD] + $global_diff;
	}
		
	return ($stats);
}

function incidents_get_holidays_seconds_by_timerange ($begin, $end) {

	//Get all holidays in this range and convert to seconds 
	$holidays = calendar_get_holidays_by_timerange($begin, $end);

	$day_in_seconds = 3600*24;
	
	$holidays_seconds = count($holidays)*$day_in_seconds;
	
	//We need to tune a bit the amount of seconds calculated before
	
	//1.- If start date was holiday only discount seconds from creation time to next day
	$str_date = date('Y-m-d',$begin);

	if (!is_working_day($str_date)) {
		
		//Calculate seconds to next day
		$start_day = strtotime($str_date);
		$finish_time = $start_day + $day_in_seconds;
		
		$aux_seconds = ($finish_time - $begin);
		
		$holidays_seconds = $holidays_seconds - $aux_seconds;
	}

	//2.- If finish date was holiday only discount seconds from now to begining of the day
	$str_date = date('Y-m-d',$end);
	
	if (!is_working_day($str_date)) {
		
		//Calculate seconds to next day
		$begining_day = strtotime($str_date);
		
		$aux_seconds = ($end - $begining_day);
		
		$holidays_seconds = $holidays_seconds - $aux_seconds;
	}	

	return $holidays_seconds;
}

//Get incident SLA
function incidents_get_incident_slas ($id_incident, $only_names = true) {
	
	$id_group = get_db_value ("id_grupo", "tincidencia", "id_incidencia", $id_incident);
	
	$sql = sprintf ('SELECT tsla.* FROM tgrupo, tsla WHERE tgrupo.id_sla = tsla.id
					AND tgrupo.id_grupo = %d', $id_group);
	$slas = get_db_all_rows_sql ($sql);
	
	if ($slas == false)
		return array ();
	
	if ($only_names) {
		$result = array ();
		foreach ($slas as $sla) {
			$result[$sla['id']] = $sla['name'];
		}
		return $result;
	}
	return $slas;
}

/*Filters and display result for incident search*/
function incidents_search_result ($filter, $ajax=false) {
	global $config;
	
	$incidents = filter_incidents ($filter);

	$params = "";

	foreach ($filter as $key => $value) {
		$params .= "&search_".$key."=".$value;
	}

	//We need this auxiliar variable to use later for footer pagination
	$incidents_aux = $incidents;
	
	$incidents = print_array_pagination ($incidents_aux, "index.php?sec=incidents&sec2=operation/incidents/incident_search".$params);

	$statuses = get_indicent_status ();
	$resolutions = get_incident_resolutions ();

	// ----------------------------------------
	// Here we print the result of the search
	// ----------------------------------------
	echo '<table width="100%" cellpadding="0" cellspacing="0" border="0px" class="result_table listing" id="incident_search_result_table">';

	echo '<thead>';
	echo "<tr>";
	echo "<th>";
	echo "</th>";
	echo "<th>";
	echo __('ID');
	echo "</th>";
	echo "<th>";
	echo __('SLA');
	echo "</th>";
	echo "<th>";
	echo __('Incident');
	echo "</th>";
	echo "<th>";
	echo __('Group')."<br><i>".__('Company')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Status')."<br><i>".__('Resolution')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Priority');
	echo "</th>";
	echo "<th>";
	echo __('Updated')."<br><i>".__('Started')."</i>";
	echo "</th>";
	echo "<th>";
	echo __('Flags');
	echo "</th>";

	if ($config["show_creator_incident"] == 1)
		echo "<th>";
		echo __('Creator');	
		echo "</th>";
	if ($config["show_owner_incident"] == 1)
		echo "<th>";
		echo __('Owner');	
		echo "</th>";

	echo "</tr>";
	echo '</thead>';
	echo "<tbody>";

	if ($incidents == false) {
		echo '<tr><td colspan="11">'.__('Nothing was found').'</td></tr>';
	} else {

		foreach ($incidents as $incident) {
			/* We print the rows directly, because it will be used in a sortable
			   jQuery table and it only needs the rows */

			if ($incident["estado"] < 3 )
				$tr_status = 'class="red"';
			elseif ($incident["estado"] < 7 )
				$tr_status = 'class="yellow"';
			else
				$tr_status = 'class="green"';

			echo '<tr '.$tr_status.' id="incident-'.$incident['id_incidencia'].'"';

			echo " style='border-bottom: 1px solid #ccc;' >";
			echo '<td>';
			print_checkbox_extended ('incidentcb-'.$incident['id_incidencia'], $incident['id_incidencia'], false, '', '', 'class="cb_incident"');
			echo '</td>';
			
			//Print incident link if not ajax, if ajax link to js funtion to replace parent
			$link = "index.php?sec=incidents&sec2=operation/incidents/incident_dashboard_detail&id=".$incident['id_incidencia'];
			
			if ($ajax) {
				$link = "javascript:update_parent('".$incident["id_incidencia"]."')";
			}
			
			
			echo '<td>';
			echo '<strong><a href="'.$link.'">#'.$incident['id_incidencia'].'</a></strong></td>';
			
			// SLA Fired ?? 
			if ($incident["affected_sla_id"] != 0)
				echo '<td width="25"><img src="images/exclamation.png" /></td>';
			else
				echo '<td></td>';
			
			echo '<td>';
							
			echo '<strong><a href="'.$link.'">'.$incident['titulo'].'</a></strong>';
			echo '</td>';
			echo '<td>'.get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);
			if ($config["show_creator_incident"] == 1){	
				$id_creator_company = get_db_value ("id_company", "tusuario", "id_usuario", $incident["id_creator"]);
				if($id_creator_company != 0) {
					$company_name = (string) get_db_value ('name', 'tcompany', 'id', $id_creator_company);	
					echo "<br><span style='font-size:11px;font-style:italic'>$company_name</span>";
				}
			}
			echo '</td>';
			$resolution = isset ($resolutions[$incident['resolution']]) ? $resolutions[$incident['resolution']] : __('None');

			echo '<td class="f9"><strong>'.$statuses[$incident['estado']].'</strong><br /><em>'.$resolution.'</em></td>';

			// priority
			echo '<td>';
			print_priority_flag_image ($incident['prioridad']);
			$last_wu = get_incident_lastworkunit ($incident["id_incidencia"]);
			if ($last_wu["id_user"] == $incident["id_creator"]){
				echo "<br><img src='images/comment.gif'>";
			}

			echo '</td>';
			
			echo '<td class="f9">'.human_time_comparation ($incident["actualizacion"]).'<br /><em>';
			echo human_time_comparation ($incident["inicio"]).'</em></td>';
			
			/* Workunits */
			echo '<td class="f9">';
			if ($incident["id_task"] > 0){
				$id_project = get_db_value ("id_project", "ttask", "id", $incident["id_task"]);
				$id_task = $incident["id_task"] ;
				echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$id_task&operation=view'><img src='images/bricks.png' border=0></a>";
			}
			$timeused = get_incident_workunit_hours ($incident["id_incidencia"]);
			$incident_wu = $in_wu = get_incident_count_workunits ($incident["id_incidencia"]);
			if ($incident_wu > 0) {
				echo '<img src="images/award_star_silver_1.png" title="'.$timeused.' Hr / '.$incident_wu.' WU">';
			}

			/* Files */
				$files = get_number_files_incident ($incident["id_incidencia"]);
				if ($files)
						echo '&nbsp;<img src="images/disk.png"
								title="'.$files.' '.__('Files').'" />';
				
				/* Mail notification */
				$mail_check = get_db_value ('notify_email', 'tincidencia',
										'id_incidencia', $incident["id_incidencia"]);
				if ($mail_check > 0)
						echo '&nbsp;<img src="images/email_go.png"
								title="'.__('Mail notification').'" />';

			echo "&nbsp;";
			/* People involved in the incident  */
				$people = people_involved_incident ($incident["id_incidencia"]);
				print_help_tip (implode ('&nbsp;', $people), false, 'tip_people');


			/* Last WU */
			echo "<br>";
			if ($incident_wu > 0){
				echo "($incident_wu) ";
			}

			if ($last_wu["id_user"] == $incident["id_creator"]){
				echo "<b>".$last_wu["id_user"]."</b>&nbsp;";
			} else {
				echo $last_wu["id_user"];
			}
			echo '</td>';
			
			if ($config["show_creator_incident"] == 1){	
				echo "<td class='f9'>";
				$incident_creator = $incident["id_creator"];
				echo substr($incident_creator,0,12);
				echo "</td>";
			}
			
			if ($config["show_owner_incident"] == 1){	
				echo "<td class='f9'>";
				$incident_owner = $incident["id_usuario"];
				echo substr($incident_owner,0,12);
				echo "</td>";
			}
			
			echo '</tr>';
		}
	}
	echo "</tbody>";
	echo "</table>";

	$incidents = print_array_pagination ($incidents_aux, "index.php?sec=incidents&sec2=operation/incidents/incident_search".$params);

	echo "<br>";
	echo sprintf(__('Max incidents shown: %d'),$config['limit_size']);
	echo print_help_tip (sprintf(__('You can change this value by changing %s parameter in setup'),"<b>".__("Max. Incidents by search")."</b>", true));

}

//Returns color value (hex) for incident priority

function incidents_get_priority_color($incident) {
		
	switch ($incident["prioridad"]) {
		case PRIORITY_INFORMATIVE:
			return PRIORITY_COLOR_INFORMATIVE;
			break;
		case PRIORITY_LOW:
			return PRIORITY_COLOR_LOW;
			break;
		case PRIORITY_MEDIUM:
			return PRIORITY_COLOR_MEDIUM;
			break;
		case PRIORITY_SERIOUS:
			return PRIORITY_COLOR_SERIOUS;
			break;
		case PRIORITY_VERY_SERIOUS:
			return PRIORITY_COLOR_VERY_SERIOUS;
			break;
		case PRIORITY_MAINTENANCE:
		default:
			return PRIORITY_COLOR_MAINTENANCE;
			break;
	}
}

?>
