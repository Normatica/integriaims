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
include_once('include/functions_setup.php');

check_login ();
	
if (! dame_admin ($config["id_user"])) {
	audit_db ("ACL Violation", $config["REMOTE_ADDR"], "No administrator access", "Trying to access setup");
	require ("general/noaccess.php");
	exit;
}

$is_enterprise = false;
if (file_exists ("enterprise/load_enterprise.php")) {
	$is_enterprise = true;
}
	
/* Tabs list */
print_setup_tabs('mail', $is_enterprise);

$update = (bool) get_parameter ("update");

if ($update) {
	$config["notification_period"] = (int) get_parameter ("notification_period", 86400);
	$config["FOOTER_EMAIL"] = (string) get_parameter ("footer_email", "");
	$config["HEADER_EMAIL"] = (string) get_parameter ("header_email", "");
	$config["mail_from"] = (string) get_parameter ("mail_from");


	$config["smtp_user"] = (string) get_parameter ("smtp_user");
	$config["smtp_pass"] = (string) get_parameter ("smtp_pass");
	$config["smtp_host"] = (string) get_parameter ("smtp_host");
	$config["smtp_port"] = (string) get_parameter ("smtp_port");

	$config["pop_user"] = (string) get_parameter ("pop_user");
	$config["pop_pass"] = (string) get_parameter ("pop_pass");
	$config["pop_host"] = (string) get_parameter ("pop_host");
	$config["pop_port"] = (string) get_parameter ("pop_port");
	
	update_config_token ("HEADER_EMAIL", $config["HEADER_EMAIL"]);
	update_config_token ("FOOTER_EMAIL", $config["FOOTER_EMAIL"]);
	update_config_token ("notification_period", $config["notification_period"]);
	update_config_token ("mail_from", $config["mail_from"]);
	update_config_token ("smtp_port", $config["smtp_port"]);
	update_config_token ("smtp_host", $config["smtp_host"]);
	update_config_token ("smtp_user", $config["smtp_user"]);
	update_config_token ("smtp_pass", $config["smtp_pass"]);
	update_config_token ("pop_host", $config["pop_host"]);
	update_config_token ("pop_user", $config["pop_user"]);
	update_config_token ("pop_pass", $config["pop_pass"]);
	update_config_token ("pop_port", $config["pop_port"]);
}

$table->width = '99%';
$table->class = 'search-table-button';
$table->colspan = array ();

$table->data = array ();

$table->data[2][0] = print_input_text ("notification_period", $config["notification_period"],
	'', 7, 7, true, __('Notification period'));
$table->data[2][0] .= integria_help ("notification_period", true);

$table->data[2][1] = print_input_text ("mail_from", $config["mail_from"], '',
	30, 50, true, __('System mail from address'));

$table->colspan[3][0] = 2;
$table->data[3][1] = "<h4>".__("SMTP Parameters"). integria_help ("mailsetup", true). "</h4>";

$table->data[4][0] = print_input_text ("smtp_host", $config["smtp_host"],
	'', 25, 30, true, __('SMTP Host'));

$table->data[4][0] .= print_help_tip (__("Left it blank if you want to use your local mail, instead an external SMTP host"), true);


$table->data[4][1] = print_input_text ("smtp_port", $config["smtp_port"],
	'', 5, 10, true, __('SMTP Port'));

$table->data[5][0] = print_input_text ("smtp_user", $config["smtp_user"],
	'', 15, 30, true, __('SMTP User'));

$table->data[5][1] = print_input_text_extended ("smtp_pass", $config["smtp_pass"], 
				'', '', 15, 30, false, false, false, true, true, __('SMTP Password'));

$table->colspan[6][0] = 2;
$table->data[6][1] = "<h4>".__("IMAP Parameters")."</h4>";

$table->data[7][0] = print_input_text ("pop_host", $config["pop_host"],
	'', 25, 30, true, __('IMAP Host'));

$table->data[7][0] .= print_help_tip (__("Use ssl://host.domain.com if want to use IMAP with SSL"), true);


$table->data[7][1] = print_input_text ("pop_port", $config["pop_port"],
	'', 15, 30, true, __('IMAP Port'));	

$table->data[7][1] .= print_help_tip (__("993 for SSL, 110 for unencrypted standard port"), true);

$table->data[8][0] = print_input_text ("pop_user", $config["pop_user"],
	'', 15, 30, true, __('IMAP User'));

$table->data[8][1] = print_input_text_extended ("pop_pass", $config["pop_pass"], 
				'', '', 15, 30, false, false, false, true, true, __('IMAP Password'));
				
$table->data[9][1] = "<h4>".__("Mail general texts")."</h4>";

$table->colspan[11][0] = 2;
$table->colspan[10][0] = 2;
$table->data[10][0] = print_textarea ("header_email", 5, 40, $config["HEADER_EMAIL"],
	'', true, __('Email header'));
$table->data[11][0] = print_textarea ("footer_email", 5, 40, $config["FOOTER_EMAIL"],
	'', true, __('Email footer'));

$button = print_input_hidden ('update', 1, true);
$button .= print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"', true);

$table->data[12][0] = $button;
$table->colspan[12][0] = 2;

echo "<form name='setup' method='post'>";
print_table ($table);
echo '</form>';
?>

<script type="text/javascript">
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});
</script>
