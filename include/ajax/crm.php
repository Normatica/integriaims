<?php

// Integria IMS - http://integriaims.com
// ==================================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

include_once('include/functions_crm.php');
enterprise_include('include/functions_crm.php');


$get_company_search = get_parameter ('get_company_search', 0);
$get_company_name = get_parameter ('get_company_name', 0);

if ($get_company_name) {
	$id_company = get_parameter('id_company');
	$name = crm_get_company_name($id_company);

	echo safe_output($name);
	return;
}

if ($get_company_search) {
	
	$search = get_parameter('search', 0);
	$search_text = (string) get_parameter ('search_text');	
	$search_role = (int) get_parameter ("search_role");
	$search_country = (string) get_parameter ("search_country");
	$search_manager = (string) get_parameter ("search_manager");
	$search_parent = get_parameter ("search_parent");
	$search_date_begin = (string) get_parameter('search_date_begin');
	$search_date_end = (string)get_parameter('search_date_end');
	$date = false;

	if ($search_date_end == 'undefined') {
		$search_date_end = '';
	}
	
	$table->width = '98%';
	$table->class = 'databox';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold;';
	$table->style[2] = 'font-weight: bold;';
	$table->style[4] = 'font-weight: bold;';
	$table->data = array ();
	$table->data[0][0] = __('Search');
	$table->data[0][1] = print_input_text ("search_text", $search_text, "", 15, 100, true);
	$table->data[0][2] = __('Company Role');
	$table->data[0][3] = print_select_from_sql ('SELECT id, name FROM tcompany_role ORDER BY name',
		'search_role', $search_role, '', __('Select'), 0, true, false, false);
	$table->data[0][4] = __('Country');
	$table->data[0][5] = print_input_text ("search_country", $search_country, "", 10, 100, true);
	
	$table->data[0][4] = __('Manager');
	$table->data[0][5] = print_input_text_extended ('search_manager', $search_manager, 'text-user', '', 15, 30, false, '',	array(), true, '', '' )	. print_help_tip (__("Type at least two characters to search"), true);

	$table->data[1][0] = __('Parent');
	$table->data[1][1] = print_select_from_sql ('SELECT id, name FROM tcompany ORDER BY name',
		'search_parent', $search_parent, '', __('Select'), 0, true, false, false);
	
	$table->data[1][2] = __('Date from');
	$table->data[1][3] = print_input_text ('search_date_begin', $search_date_begin, '', 15, 20, true);
	
	$table->data[1][4] = __('Date to');
	$table->data[1][5] = print_input_text ('search_date_end', $search_date_end, '', 15, 20, true);
	
	echo '<form method="post" action="index.php?sec=customers&sec2=operation/companies/company_detail">';
		print_table ($table);
	
	echo '<div style="width:'.$table->width.'" class="action-buttons button">';
		echo "<input type='button' class='sub next' onClick='javascript: loadParamsCompany(\".$search_text.\");' value='".__("Search")."''>";
	echo '</div>';
	echo '</form>';
	
	$where_clause = '';
	
	if ($search) {

		if ($search_text != "") {
			$where_clause .= sprintf (' AND ( name LIKE "%%%s%%" OR country LIKE "%%%s%%")  ', $search_text, $search_text);
		}

		if ($search_role != 0){ 
			$where_clause .= sprintf (' AND id_company_role = %d', $search_role);
		}

		if ($search_country != ""){ 
			$where_clause .= sprintf (' AND country LIKE "%%s%%" ', $search_country);
		}

		if ($search_manager != ""){ 
			$where_clause .= sprintf (' AND manager = "%s" ', $search_manager);
		}
		
		if ($search_parent != 0){ 
			$where_clause .= sprintf (' AND id_parent = %d ', $search_parent);
		}
		
		if ($search_date_begin != "") { 
			$where_clause .= " AND `date` >= $search_date_begin";
			$date = true;
		}

		if ($search_date_end != ""){ 
			$where_clause .= " AND `date` <= $search_date_end";
			$date = true;
		}
	}
	
	$params = "&search_manager=$search_manager&search_text=$search_text&search_role=$search_role&search_country=$search_country&search_parent=$search_parent&search_date_begin=$search_date_begin&search_date_end=$search_date_end";
	
	$companies = crm_get_companies_list($where_clause, $date);
	
	$companies_aux = enterprise_hook ('crm_get_user_companies', array ($config['id_user'], $companies));

	if ($manage_permission !== ENTERPRISE_NOT_HOOK) {	
		$companies = $companies_aux;	
	}

	if ($companies !== false) {
		$table_list->width = "98%";
		$table_list->class = "listing";
		$table_list->data = array ();
		$table_list->style = array ();
		$table_list->colspan = array ();
		$table_list->head[0] = __('Company');
		$table_list->head[1] = __('Role');
		$table_list->head[4] = __('Manager');
		$table_list->head[5] = __('Country');
		$table_list->head[6] = __('Last activity');
		$table_list->head[7] = __('Delete');
		
		foreach ($companies as $company) {

			$data = array ();
			
			$data[0] = "<a href='javascript:loadCompany(" . $company['id'] . ");'>".$company["name"]."</a>";
			$data[1] = get_db_value ('name', 'tcompany_role', 'id', $company["id_company_role"]);

			$sum_leads = get_db_sql ("SELECT COUNT(id) FROM tlead WHERE progress < 100 AND id_company = ".$company["id"]);
			if ($sum_leads > 0) {
				$data[3] .= " ($sum_leads) ";
				$data[3] .= get_db_sql ("SELECT SUM(estimated_sale) FROM tlead WHERE progress < 100 AND id_company = ".$company["id"]);
			}

			$data[4] = $company["manager"];
			$data[5] = $company["country"];
			
			// get last activity date for this company record
			$last_activity = get_db_sql ("SELECT date FROM tcompany_activity WHERE id_company = ". $company["id"]);

			$data[6] = human_time_comparation ($last_activity);

			$data[7] ='<a href="index.php?sec=customers&
							sec2=operation/companies/company_detail'.$params.'&
							delete_company=1&id='.$company['id'].'"
							onClick="if (!confirm(\''.__('Are you sure?').'\'))
							return false;">
							<img src="images/cross.png"></a>';
			
			array_push ($table_list->data, $data);
		}
		print_table ($table_list);
	}
	
	return;
}


?>
