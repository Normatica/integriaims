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

include_once ("include/functions_projects.php");

check_login ();

$id_user = $config["id_user"];

$section_permission = get_project_access ($id_user);
if (!$section_permission["write"]) {
	audit_db($id_user, $config["REMOTE_ADDR"], "ACL Violation", "Trying to access to project group management");
	no_permission();
}


echo '<h2>'.__('Projects').'</h2>';
echo '<h4>'.__('Project group management').'</h4>';

$id = (int) get_parameter ('id');
$new_group = (bool) get_parameter ('new_group');
$insert_group = (bool) get_parameter ('insert_group');
$update_group = (bool) get_parameter ('update_group');
$delete_group = (bool) get_parameter ('delete_group');

if ($insert_group) {
	$name = (string) get_parameter ('name');
	$icon= (string) get_parameter ('icon');
	$sql = sprintf ('INSERT INTO tproject_group (name, icon)
		VALUES ("%s", "%s")', $name, $icon);

	$id = process_sql ($sql, 'insert_id');
	if (! $id) {
		echo '<h3 class="error">'.__('Could not be created').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>';
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Project Management", "Created group project $name");
	}
	$id = 0;
}

// UPDATE
if ($update_group) {
	$name = (string) get_parameter ('name');
	$icon= (string) get_parameter ('icon');

	$sql = sprintf ('UPDATE tproject_group
		SET icon = "%s", name = "%s"
		WHERE id = %d',
		$icon, $name, $id);

	$result = process_sql ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('Could not be updated')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Successfully updated')."</h3>";
		audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Project Management", "Updated group project $name");
	}
}

// DELETE
if ($delete_group) {
	$name = get_db_value ('name', 'tproject_group', 'id', $id);
	$sql = sprintf ('DELETE FROM tproject_group WHERE id = %d', $id);
	process_sql ($sql);
	audit_db ($config["id_user"], $config["REMOTE_ADDR"], "Project Management", "Deleted group project $name");
	echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	$id = 0;
}

// FORM (Update / Create)

	if ($new_group) {
		$name = '';
		$icon = '';
		$description = '';
	} else {
		$group = get_db_row ("tproject_group", "id", $id);
		$name = $group["name"];
		$icon = $group["icon"];
	}
	
	$table->width = '99%';
	$table->class = 'search-table';
	$table->data = array ();
	
	$table->data[0][0] = '<b>'.__('Project group name').'</b>';
	$table->data[1][0] = print_input_text ('name', $name, '', 60, 100, true);
	
	$table->data[2][0] = '<b>'.__('Icon').'</b>';
	$icons = list_files ('images/project_groups_small/', "png", 1, 0, 'svn');
	$table->data[3][0] = print_select ($icons, "icon", $icon, '', '', 0, true, false, false);
	$table->data[2][0] .= project_get_icon ($id, true);

	if ($id) {
		$button = print_submit_button (__('Update'), "enviar", false, '', true);
		$button .= print_input_hidden ('update_group', 1, true);
		$button .= print_input_hidden ('id', $id, true);
		$return_a = '<a href="index.php?sec=projects&sec2=operation/projects/project_group_detail" alt="'.__('Return').'">'.__('Return').'</a>';
		$table->data[4][0] = $button;
		$table->data[5][0] = $return_a;
	} else {
		$button .= print_submit_button (__('Create'), "enviar", false, '', true);
		$button .= print_input_hidden ('insert_group', 1, true);
		$table->data[4][0] = $button;
	}

	
	echo '<div class = "divform">';
		echo '<form id="form-project_group_detail" method="post">';
			print_table ($table);
		echo "</form>";
	echo '</div>';
	$groups = get_db_all_rows_in_table ('tproject_group', 'name');
	
	$table->width = "99%";
	
	if ($groups !== false) {
		$table->class = "listing";
		$table->data = array ();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->size = array ();
		$table->size[1] = '40px';
		$table->align = array ();
		$table->align[1] = 'center';
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Delete');
		
		foreach ($groups as $group) {
			$data = array ();
			
			// Name
			$data[0] = '<img src="images/project_groups_small/'.$group["icon"].'" /> ';
			$data[0] .= '<a href=index.php?sec=projects&sec2=operation/projects/project_group_detail&id='.$group["id"]."'>".$group["name"]."</a>";
			
			$data[1] = '<a href="index.php?sec=projects&
						sec2=operation/projects/project_group_detail&
						delete_group=1&id='.$group["id"].'"
						onClick="if (!confirm(\''.__('Are you sure delete group project?').'\'))
						return false;">
						<img src="images/icons/icono_papelera.png" /></a>';
			array_push ($table->data, $data);
		}
		echo '<div class="divresult">';
			print_table ($table);
		echo '</div>';
	}

?>

<script type="text/javascript" src="include/js/jquery.validate.js"></script>
<script type="text/javascript" src="include/js/jquery.validation.functions.js"></script>

<script type="text/javascript">

// Change icon
$(document).ready (function () {
	$("#icon").change (function () {
		data = this.value;
		$("#product-icon").fadeOut ('normal', function () {
			$("#product-icon").attr ("src", "images/project_groups_small/"+data).fadeIn ();
		}).change();
	})
});

// Form validation
trim_element_on_submit('#text-name');

validate_form("#form-project_group_detail");
var rules,messages;
// Rules: #text-name
rules = {
	required: true,
	remote: {
		url: "ajax.php",
        type: "POST",
        data: {
			page: "include/ajax/remote_validations",
			search_existing_project_group: 1,
			group_name: function() { return $('#text-name').val() },
			group_id: "<?php echo $id?>"
        }
	}
};
messages = {
	required: "<?php echo __('Name required')?>",
	remote: "<?php echo __('This group already exists')?>"
};
add_validate_form_element_rules('#text-name', rules, messages);

</script>
