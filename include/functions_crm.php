<?php

// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

function crm_get_companies_list ($sql_search, $date = false) {
	
	if ($date) {
		$sql = "SELECT tcompany.* FROM tcompany, tcompany_activity
				WHERE tcompany.id = tcompany_activity.id_company $sql_search
				";
	} else {
		$sql = "SELECT tcompany.* FROM tcompany
				WHERE 1=1 $sql_search
				";
	}
		
	$companies = get_db_all_rows_sql($sql);
	
	if ($companies === false) {
		$companies = array();
	}
	
	return $companies;
}

function crm_get_company_name ($id_company) {
	
	$name = get_db_value('name', 'tcompany', 'id', $id_company);
	
	return $name;
}

//CHECK ACLS EXTERNAL USER
function crm_check_acl_external_user ($user, $id_company) {
	
	$user_data = get_db_row ('tusuario', 'id_usuario', $user);
	
	if ($user_data['id_company'] == $id_company) {
		return true;
	}
	return false;
}

// Checks if an invoice is locked. Returns 1 if is locked, 0 if not
// and false in case of error in the query.
function crm_is_invoice_locked ($id_invoice) {
	$locked = get_db_value('locked', 'tinvoice', 'id', $id_invoice);
	
	return $locked;
}

// Checks the id of the user that locked the invoice. Returns the id
// of the user in case of success or false in the case of the invoice
// does not exist or is not locked.
function crm_get_invoice_locked_id_user ($id_invoice) {
	
	if (!crm_is_invoice_locked ($id_invoice))
		return false;
	$user = get_db_value('locked_id_user', 'tinvoice', 'id', $id_invoice);
	
	return $user;
}

/**
 * Function to check if the user can lock the invoice.
 * NOT FULLY IMPLEMENTED IN OPENSOURCE version
 * Please visit http://integriaims.com for more information
*/
function crm_check_lock_permission ($id_user, $id_invoice) {
	
	$return = enterprise_hook ('crm_check_lock_permission_extra', array ($id_user, $id_invoice));
	if ($return !== ENTERPRISE_NOT_HOOK)
		return $return;
	return true;
}

// Changes the lock state of an invoice. Returns -1 if the user have
// not permission to do this or the new lock state in case of success.
function crm_change_invoice_lock ($id_user, $id_invoice) {
	
	if (crm_check_lock_permission ($id_user, $id_invoice)) {
		
		$lock_status = crm_is_invoice_locked ($id_invoice);
		if ($lock_status == 1) {
			
			$values = array ('locked' => 0, 'locked_id_user' => NULL);
			$where = array ('id' => $id_invoice);
			if (process_sql_update ('tinvoice', $values, $where))
				return 0;
			return 1;
		} elseif ($lock_status == 0) {
			
			$values = array ('locked' => 1, 'locked_id_user' => $id_user);
			$where = array ('id' => $id_invoice);
			if (process_sql_update ('tinvoice', $values, $where))
				return 1;
			return 0;
		}
	}
	
	return -1;
}

function crm_get_all_leads ($where_clause) {
	
	$sql = "SELECT * FROM tlead $where_clause ORDER BY creation DESC";
	$leads = get_db_all_rows_sql ($sql);
	
	return $leads;
}

function crm_get_all_contacts ($where_clause) {
	
	$sql = "SELECT * FROM tcompany_contact $where_clause ORDER BY id_company, fullname";

	$contacts = get_db_all_rows_sql ($sql);
	
	return $contacts;
}

function crm_get_all_contracts ($where_clause) {
	$sql = "SELECT * FROM tcontract $where_clause ORDER BY date_end DESC";

	$contracts = get_db_all_rows_sql ($sql);
	
	return $contracts;
}

function crm_get_all_invoices ($where_clause) {
	
	$sql = "SELECT * FROM tinvoice WHERE $where_clause ORDER BY invoice_create_date DESC";
	$invoices_aux =  get_db_all_rows_sql ($sql);
	
	if ($invoices_aux === false) {
		$invoices_aux = array();
		$invoices = false;
	}

	foreach ($invoices_aux as $key=>$invoice) {
		$invoices[$key]['id'] = $invoice['id'];
		$invoices[$key]['id_user'] = $invoice['id_user'];
		$invoices[$key]['id_task'] = $invoice['id_task'];
		$invoices[$key]['id_company'] = $invoice['id_company'];
		$invoices[$key]['bill_id'] = $invoice['bill_id'];
		$invoices[$key]['ammount'] = $invoice['ammount'];
		$invoices[$key]['tax'] = $invoice['tax'];
		$invoices[$key]['description'] = $invoice['description'];
		$invoices[$key]['locked'] = $invoice['locked'];
		$invoices[$key]['locked_id_user'] = $invoice['locked_id_user'];
		$invoices[$key]['invoice_create_date'] = $invoice['invoice_create_date'];
		$invoices[$key]['invoice_payment_date'] = $invoice['invoice_payment_date'];
		$invoices[$key]['status'] = $invoice['status'];
	
	}
	return $invoices;
}

// sum total invoices
function crm_get_total_invoiced($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company as id, sum(ammount) as total_ammount FROM tinvoice
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			GROUP BY id_company
			ORDER BY total_ammount DESC
			";
	} else {
		$sql = "SELECT id_company as id, sum(ammount) as total_ammount FROM tinvoice
			GROUP BY id_company
			ORDER BY total_ammount DESC
			";
	}
	
	
	$total = process_sql ($sql);

	return $total;
}

//print top 10 invoices
function crm_print_most_invoicing_companies($companies) {
	
	$table->id = 'company_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Company');
	$table->head[1] = __('Invoiced');
	
	$i = 0;
	foreach ($companies as $key=>$company) {
	
		if ($i < 10) {
			$data = array();
			$data[0] = crm_get_company_name ($company['id']);

			$data[1] = $company['total_ammount'];

			array_push ($table->data, $data);
		}
		$i++;
	}
	
	return $table;
}

// count total activity
function crm_get_total_activity($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company as id, count(id) as total_activity FROM tcompany_activity
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			GROUP BY id_company
			ORDER BY total_activity DESC
			";
	} else {
		$sql = "SELECT id_company as id, count(id) as total_activity FROM tcompany_activity
			GROUP BY id_company
			ORDER BY total_activity DESC
			";
	}

	$activity_total = process_sql ($sql);

	return $activity_total;
}

//print top 10 activities
function crm_print_most_activity_companies($companies) {
	
	$table->id = 'company_list';
	$table->class = 'listing';
	$table->width = '90%';
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	
	$table->head[0] = __('Company');
	$table->head[1] = __('Number');
	
	$i = 0;
	foreach ($companies as $key=>$company) {
	
		if ($i < 10) {
			$data = array();
			$data[0] = crm_get_company_name ($company['id']);

			$data[1] = $company['total_activity'];

			array_push ($table->data, $data);
		}
		$i++;
	}
	
	//print_table($table);
	return $table;
}

// count companies per country
function crm_get_total_country($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT country, count(id) as total_companies FROM tcompany
			WHERE id IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			AND country<>''
			GROUP BY country
			ORDER BY total_companies DESC
			";
	} else {
		$sql = "SELECT country, count(id) as total_companies FROM tcompany
			WHERE country<>''
			GROUP BY country
			ORDER BY total_companies DESC
			";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_country_graph($companies) {
	
	global $config;
	
	if ($companies === false) {
		return false;
	}
    
	require_once ("include/functions_graph.php");  
	
	$company_country = array();
	$i = 0;
	foreach ($companies as $key=>$company) {
		if ($i < 10) {
			$company_country[$company['country']] = $company['total_companies'];
		}
	}

	return $company_country;
}

// count users per company
function crm_get_total_user($where_clause = false) {
	
	if ($where_clause) {
		$sql = "SELECT id_company, count(id_company) as total_users FROM tusuario
			WHERE id_company IN (SELECT id FROM tcompany
					WHERE 1=1 $where_clause)
			AND id_company<>0
			GROUP BY id_company
			ORDER BY total_users DESC
			";
	} else {
		$sql = "SELECT id_company, count(id_company) as total_users FROM tusuario
			WHERE id_company<>0
			GROUP BY id_company
			ORDER BY total_users DESC
			";
	}
		
	$total = process_sql ($sql);

	return $total;
}

function crm_get_data_user_graph($companies) {	
	global $config;
    
    if ($companies === false) {
		return false;
	}
	
	require_once ("include/functions_graph.php");  
	
	$company_user = array();
	$i = 0;
	foreach ($companies as $key=>$company) {
		if ($i < 10) {
			$company_name = crm_get_company_name($company['id_company']);
			$company_user[$company_name] = $company['total_users'];
		}
	}
	return $company_user;
}
?>