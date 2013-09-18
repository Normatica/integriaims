<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008-2012 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.




global $config;

$search_users = (bool) get_parameter ('search_users');
$search_users_role = (bool) get_parameter ('search_users_role');
$get_group_info = (bool) get_parameter ('get_group_info');
$get_user_company = (bool) get_parameter ('get_user_company');

if ($search_users) {
	require_once ('include/functions_db.php');

	$id_user = $config['id_user'];
	$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
	$users = get_user_visible_users ($config['id_user'],"IR", false);
	
	if ($users === false)
		return;
		
	$res = array();
	
	foreach ($users as $user) {
		if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
			array_push($res, array("label" => safe_output($user['nombre_real'])." (".$user['id_usuario'].")", "value" => $user['id_usuario']));
		}
	}
	
	echo json_encode($res);
	
	return;
}

if ($search_users_role) {
	require_once ('include/functions_db.php');
	
	$id_project = (int) get_parameter ('id_project');
	$id_user = $config['id_user'];
	$string = (string) get_parameter ('term'); /* term is what autocomplete plugin gives */
	
	$users = get_users_project ($id_project);
	
	if ($users === false)
		return;

	$res = array();
	
	foreach ($users as $user) {
		if(preg_match('/'.$string.'/i', $user['id_usuario']) || preg_match('/'.$string.'/i', $user['nombre_real'])|| preg_match('/'.$string.'/i', $user['num_employee'])) {
			array_push($res, array("label" => safe_output($user['nombre_real'])." (".$user['id_usuario'].")", "value" => $user['id_usuario']));
		}
	}
	
	echo json_encode($res);
	
	return;
}

if ($get_group_info) {
	$group = get_parameter("group");
	
	$res = get_db_row ("tgrupo", "id_grupo", $group);
	
	echo json_encode($res);
}

if ($get_user_company) {
	$id_user = get_parameter("id_user");
	$company = get_user_company ($id_user, false);
	
	echo json_encode($company['id']);
}

?>
 	
