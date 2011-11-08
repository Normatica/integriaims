<?php

// Integria IMS - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2011-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;
require_once ('include/functions_incidents.php');

check_login ();

$id_incident = get_parameter('id', 0);
$clean_output = get_parameter('clean_output', 0);

$statuses = get_indicent_status ();
$resolutions = get_incident_resolutions ();

echo '<div style="width: 950px;">';
echo '<h1>'.__("Incident #$id_incident report");

if ($clean_output == 0){
	// link full screen
	echo "&nbsp;&nbsp;<a title='Full screen' href='index.php?sec=incidents&sec2=operation/incidents/incident_report&id=$id_incident&clean_output=1'>";
	echo "<img src='images/html.png'>";
	echo "</a>";

	// link PDF report
	echo "&nbsp;&nbsp;<a title='PDF report' href='index.php?sec=incidents&sec2=operation/incidents/incident_report&id=$id_incident&clean_output=1&pdf_output=1'>";
	echo "<img src='images/page_white_acrobat.png'>";
	echo "</a>";
}
	
echo '</h1>';

$incident = get_incident ($id_incident);
	    
$table->class = 'listing';
$table->width = "95%";
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->head = array ();
$table->head[0] = __('Title');
$table->head[1] = __('Description');
if($incident['epilog'] != '') {
	$table->head[1] = __('Epilog');
}
$table->data = array ();

$table->data[0][0] = '<a href="index.php?sec=incidents&sec2=operation/incidents/incident&id='.$incident['id_incidencia'].'">'.
	$incident['titulo'].'</a>';
	
if($incident['descripcion'] != '') {
	$table->data[0][1] = $incident['descripcion'];
}
else {
	$table->data[0][1] = "<b>".__("No description")."</b>";
}

if($incident['epilog'] != '') {
	$table->data[0][2] = $incident['epilog'];
}

print_table ($table);

unset($table);

$output = '<div style="margin-left: 15px; text-align: left; width: 95%;">';


$output .= '</div>';
echo '<div class="report_info" style="text-align: left; width: 95%">';
if ($output != '') {
	echo $output;
} else {
	echo __('All incidents');
}
echo '</div>';

$table->class = 'listing';
$table->width = "95%";
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->head = array ();
$table->head[0] = __('SLA');
$table->head[1] = __('Group');
$table->head[2] = __('Status')."<br /><em>".__('Resolution')."</em>";
$table->head[3] = __('Priority');
$table->head[4] = __('Updated')."<br /><em>".__('Started')."</em>";
$table->head[5] = __('Work');
$table->head[6] = __('Responsible');
$table->head[7] = __('Most active users');
$table->data = array ();

if ($incident == false) {
	$table->colspan[0][0] = 9;
	$table->data[0][0] = __('Nothing was found');
}

$data = array ();

$data[0] = '';
if ($incident["affected_sla_id"] != 0)
	$data[0] = '<img src="images/exclamation.png" />';
	
$data[1] = get_db_value ("nombre", "tgrupo", "id_grupo", $incident['id_grupo']);

$resolution = isset ($resolutions[$incident['resolution']]) ? $resolutions[$incident['resolution']] : __('None');

$data[2] = '<strong>'.$statuses[$incident['estado']].'</strong><br /><em>'.$resolution.'</em>';
$data[3] = print_priority_flag_image ($incident['prioridad'], true);
$data[4] = human_time_comparation ($incident["actualizacion"]);
$data[4] .= '<br /><em>';
$data[4] .=  human_time_comparation ($incident["inicio"]);
$data[4] .= '</em>';

$data[5] = '';
$workunits = get_incident_count_workunits ($incident["id_incidencia"]);
if ($workunits > 0) {
	$data[5] = '<img src="images/award_star_silver_1.png" />';
	$data[5] .= get_incident_workunit_hours ($incident["id_incidencia"]);
}

$data[6] = $incident['id_usuario'];


// Find the 5 most active users (more hours worked)
$most_active_users = get_most_active_users (5, $id_incident);

$users_label = '';
foreach ($most_active_users as $user) {
	$users_data[$user['id_user']] = $user['worked_hours'];
	$users_label .= '<a href="index.php?sec=users&sec2=operation/users/user_edit&id='.
		$user['id_user'].'">'.$user['id_user']."</a> (".$user['worked_hours'].
		" ".__('Hr').") <br />";
}		

$data[7] = $users_label;

array_push ($table->data, $data);

echo '<h3>'.__('Incident summary').'</h3>';

print_table ($table);

unset($table);

echo '<h3>'.__('Incident workunits').'</h3>';

$workunits = get_incident_workunits ($id_incident);

if ($workunits === false) {
	echo '<h4>'.__('No workunit was done in this incident').'</h4>';
	return;
}

foreach ($workunits as $workunit) {
	$workunit_data = get_workunit_data ($workunit['id_workunit']);
	show_workunit_data ($workunit_data, $title);
}

$generated_str = '<div class="report_info" style="text-align: right; width: 95%;">';
$generated_str .= print_label (__('Generated by').' : ', '', '', true,
	dame_nombre_real ($config['id_user']));
$generated_str .= '<br />';
$generated_str .= print_label (__('Date').' : ', '', '', true, date ('Y-m-d H:i', time ()));
$generated_str .= '</div>';

echo $generated_str;
?>