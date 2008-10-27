<?php

// Integria 2.0 - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2007-2008 Esteban Sanchez, estebans@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

if (check_login () != 0) {
 	audit_db ("Noauth", $config["REMOTE_ADDR"], "No authenticated access",
 		"Trying to access inventory viewer");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

if (! give_acl ($config['id_user'], get_inventory_group ($id), 'VR')) {
	audit_db ($config['id_user'], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to inventory ".$id);
	include ("general/noaccess.php");
	return;
}

$inventory = get_db_row ('tinventory', 'id', $id);

echo '<h3>'.__('Contact details on inventory object').' #'.$id.'</h3>';

$contracts = get_inventory_contracts ($id, false);
if ($contracts === false)
	$contracts = array ();

$table->class = 'listing';
$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Company');
$table->head[1] = __('Contact');
$table->head[2] = __('Position');
$table->head[3] = __('Details');
$table->head[4] = __('Edit');
$table->size = array ();
$table->size[3] = '40px';
$table->size[4] = '40px';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->data = array ();

foreach ($contracts as $contract) {
	$company = get_company ($contract['id_company']);
	if ($company === false)
		continue;
	if (! give_acl ($config['id_user'], $contract['id_group'], "IR"))
		continue;
	$contacts = get_company_contacts ($company['id'], false);
	
	foreach ($contacts as $contact) {
		$data = array ();
		
		$data[0] = $company['name'];
		$data[1] = $contact['fullname'];
		$details = '';
		if ($contact['phone'] != '')
			$details .= '<strong>'.__('Phone number').'</strong>: '.$contact['phone'].'<br />';
		if ($contact['mobile'] != '')
			$details .= '<strong>'.__('Mobile phone').'</strong>: '.$contact['mobile'].'<br />';
		$data[2] = $contact['position'];
		$data[3] = print_help_tip ($details, true, 'tip_view');
		$data[4] = '<a href="index.php?sec=inventory&sec2=operation/contacts/contact_detail&id='.$contact['id'].'">'.
				'<img src="images/setup.gif" /></a>';
		array_push ($table->data, $data);
	}
}
print_table ($table);
?>
