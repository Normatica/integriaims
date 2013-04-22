<?php

// Integria IMS - http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2007-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

$id_grupo = "";
$creacion_incidente = "";
$id_incident = (int) get_parameter ('id');
$title = '';

if (!$id_incident) {
	return;
}

// Obtain group of this incident
$incident = get_incident ($id_incident);

$result_msg = '';

//user with IR and incident creator see the information
if (! give_acl ($config['id_user'], $incident['id_grupo'], 'IR')
	&& ($incident['id_creator'] != $config['id_user'])) {
	audit_db ($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to incident #".$id_incident);
	include ("general/noaccess.php");
	exit;
}

// Workunit view
$workunits = get_incident_workunits ($id_incident);

if ($workunits === false) {
	echo '<h4>'.__('No workunit was done in this incident').'</h4>';
	return;
}

foreach ($workunits as $workunit) {
	$workunit_data = get_workunit_data ($workunit['id_workunit']);
	show_workunit_data ($workunit_data, $title);
}
echo "<h3>".__("Incident details")."</h3>";
echo "<div style='margin-left: 10px; margin-top: 20px; width:90%; padding: 10px; border: 1px solid #ccc'><p>";
echo clean_output_breaks ($incident["descripcion"]);
echo "</div>";

?>
