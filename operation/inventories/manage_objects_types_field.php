<?php
// INTEGRIA - the ITIL Management System
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2008 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


global $config;

check_login ();

if (! give_acl ($config["id_user"], 0, "PM")) {
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "ACL Violation", "Trying to access Object Management");
	require ("general/noaccess.php");
	exit;
}

include_once("include/functions_objects.php");

//**********************************************************************
// Get actions
//**********************************************************************
$id_object_type = (int) get_parameter ('id');
$id_object_type_field = (int) get_parameter ('id_object_type_field');
$action = get_parameter ('action');

switch ($action) {
	default:
	case "create":
			$label = "";
			$type = "numeric";
			$combo_value = "";
			$external_table_name = "";
			$external_reference_field = "";
			$unique = 0;
			$inherit = 0;
			break;
	case "update":
			$object_type_field = get_db_row_filter('tobject_type_field', array('id' => $id_object_type_field));
			$label = $object_type_field["label"];
			$type = $object_type_field["type"];
			$combo_value = $object_type_field["combo_value"];
			$external_table_name = $object_type_field["external_table_name"];
			$external_reference_field = $object_type_field["external_reference_field"];
			$unique = $object_type_field["unique"];
			$inherit = $object_type_field["inherit"];	
			break;			
}

//**********************************************************************
// Tabs
//**********************************************************************

echo '<div id="tabs">';

/* Tabs list */
echo '<ul class="ui-tabs-nav">';
echo '<li class="ui-tabs"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects"><span>'.__('Objects').'</span></a></li>';
if (!empty($id_object_type)) {
	echo '<li class="ui-tabs-selected"><a href="index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list&id=' . $id_object_type . '"><span>'.__('Fields').'</span></a></li>';
}
echo '</ul>';
echo '</div>';

//**********************************************************************
// Field update form
//**********************************************************************

echo "<h2>".__('Object types management')."</h2>";

$table->width = '90%';
$table->class = 'databox';
$table->colspan = array ();
$table->colspan[0][0] = 2;
$table->colspan[2][0] = 2;
$table->data = array ();

$table->data[0][0] = print_input_text ('label', $label, '', 45, 100, true, __('Label'));
$types = object_get_types();
$table->data[1][0] = print_select ($types, 'type', $type, '', '', "", true, false, false, __('Types')) . print_help_tip(__('Field type to be filled later, if you choose "Combo" you have to select the values bellow. If you select "External" then you have to fill external table name and reference field.'), true);
$table->data[2][0] = print_input_text ('combo_value', $combo_value, '', 100, 100, true, __('Combo values')). print_help_tip(__('If Type selected is "Combo" you have to fill this text with the select values separated by commas. E.g.: foo1,foo2'), true);
$table->data[3][0] = print_input_text ('external_table_name', $external_table_name, '', 45, 100, true, __('External table name'));
$table->data[4][0] = print_input_text ('external_reference_field', $external_reference_field, '', 45, 100, true, __('External reference field'));
$table->data[5][0] = '<label>' . __('Unique') . print_help_tip(__('With this value checked the values in this field will be unique for all the inventory objects that use this field.'), true) . '</label>';
$table->data[6][0] = print_checkbox ('unique', 1, $unique, __('Unique'));
$table->data[7][0] = '<label>' . __('Inherit') . print_help_tip(__('With this value checked this field will inherit the values of owner, users and companies of the parent inventory object (at creation time).'), true) . '</label>';
$table->data[8][0] = print_checkbox ('inherit', 1, $inherit, __('Inherit'));

echo "<form method='post' action='index.php?sec=inventory&sec2=operation/inventories/manage_objects_types_list'>";
print_table ($table);
echo "<div class='button' style='width: ".$table->width."'>";
if (empty($id_object_type_field)) {
	print_submit_button (__('Create'), 'crt_btn', false, 'class="sub next"');
	print_input_hidden ('id', $id_object_type);
	print_input_hidden ('action_db', 'insert');
	print_input_hidden ('action', 'update');
} else {
	print_submit_button (__('Update'), 'upd_btn', false, 'class="sub upd"');
	print_input_hidden ('id', $id_object_type);
	print_input_hidden ('id_object_type_field', $id_object_type_field);
	print_input_hidden ('action_db', 'update');
	print_input_hidden ('action', 'update');
}
echo "</div></form>";


?>
<script type="text/javascript">
$(document).ready (function () {
	var data_default = $("#type").val();
	
	if (data_default == "combo") {
		
		$("#table1-2").show();
	
	} else {
		
		$("#table1-2").hide();
		
	}

	if (data_default == "external") {
		
		$("#table1-3").show();
		$("#table1-4").show();
		
	} else {
		
		$("#table1-3").hide();
		$("#table1-4").hide();
		
	}		
	
	$("#type").change (function () {
		var data = this.value;

		if (data == "combo") {
			
			$("#table1-2").show("slow");
			
		} else {
			
			$("#table1-2").hide("slow");			
		}
		
		if (data == "external") {
			
			$("#table1-3").show("slow");
			$("#table1-4").show("slow");
			
		} else {
			
			$("#table1-3").hide("slow");
			$("#table1-4").hide("slow");
			
		}		
		
	})
});

</script>
