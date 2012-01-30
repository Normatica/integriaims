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

// Load global vars
global $config;

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access visual setup");
	require ("general/noaccess.php");
	exit;
}

$labels = get_inventory_generic_labels ();

$update = (bool) get_parameter ("update");

if ($update) {
	$config["pandora_url"] = get_parameter ("pandora_url");
	$config["pandora_api_password"] = get_parameter ("pandora_api_password");
	$config["default_contract"] = get_parameter ("default_contract");
	$config["default_product_type"] = get_parameter ("default_product_type");

	foreach($labels as $k => $lab) {
		$config["pandora_$k"] = get_parameter ("pandora_$k");
		update_config_token ("pandora_$k", $config["pandora_$k"]);
	}
	
    update_config_token ("pandora_url", $config["pandora_url"]);
    update_config_token ("pandora_api_password", $config["pandora_api_password"]);
    update_config_token ("default_contract", $config["default_contract"]);
    update_config_token ("default_product_type", $config["default_product_type"]);
}

echo "<h2>".__('Pandora FMS inventory')."</h2>";

$table->width = '90%';
$table->class = 'databox';
$table->colspan = array ();
$table->data = array ();

$table->data[0][0] = print_input_text ("pandora_url", $config["pandora_url"], '',
	30, 100, true, __('Pandora FMS URL'));

$table->data[0][1] = print_input_text ("pandora_api_password", $config["pandora_api_password"], '', 
	30, 100,  true, __('Pandora FMS API password')) ;

$contracts = get_contracts();
$table->data[1][0] = print_select ($contracts, 'default_contract', $config["default_contract"], '', __('Select'), '',  true, 0, true, __('Default Contract')) ;

$products = get_products ();
$table->data[1][1] = print_select ($products, 'default_product_type', $config["default_product_type"], '', __('Select'), '',  true, 0, true, __('Default product type')) ;


$table->data[2][0] = "<h3>".__('Inventories extra fields')."</h3>";
$table->data[2][1] = "" ;

$row = 3;
$col = 0;

$labels = get_inventory_generic_labels ();

$pandora_fields = array('os_name' => __('Operative system'), 'url_address' => __('URL address'));

foreach($labels as $k => $lab) {
	$table->data[$row][$col] = print_select ($pandora_fields, "pandora_$k", $config["pandora_$k"], '', __('Empty'), '',  true, 0, true, $lab) ;
	
	if($col == 1) {
		$row++;
		$col = 0;
	}
	else {
		$col = 1;
	}
}

echo "<form name='setup' method='post'>";

print_table ($table);

echo '<div style="width: '.$table->width.'" class="button">';
print_input_hidden ('update', 1);
print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>