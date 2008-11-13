<?php
// Integria Enterprise
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");
require_once ('godmode/updatemanager/load_updatemanager.php');

check_login ();

if (! give_acl ($config['id_user'], 0, 'PM')) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	exit;
}

$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);

$settings = um_db_load_settings ();

echo '<h2>'.__('Update manager').'</h2>';

if ($settings->customer_key == FREE_USER) {
	echo '<div class="notify" style="width: 80%; text-align:left;" >';
	echo '<img src="images/information.png" /> ';
	/* Translators: Do not translade Update Manager, it's the name of the program */
	echo __('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with the new Integria Enterprise. It allows systems administrators to do not need to update their Integria Enterprise manually since the Update Manager is the one getting new modules, new plugins and new features (even full migrations tools for future versions) automatically');
	echo '<p />';
	echo __('Update Manager is one of the most advanced features of Integria Enterprise version, for more information visit <a href="http://integria.sourceforge.net">http://integria.sourceforge.net</a>');
	echo '<p />';
	echo __('Update Manager sends anonymous information about Integria Enterprise usage (number of agents and modules running).');
	echo '</div>';
}

$user_key = get_user_key ($settings);
$update_package = (bool) get_parameter ('update_package');

if ($update_package) {
	echo '<h2>Updating...</h2>';
	flush ();
	$force = (bool) get_parameter ('force_update');
	
	um_client_upgrade_to_latest ($user_key, $force);
	/* TODO: Add a new in tnews */
	$settings = um_db_load_settings ();
}

$package = um_client_check_latest_update ($settings, $user_key);

if (is_int ($package) && $package == 1) {
	echo '<h5 class="suc">'.__('Your system is up-to-date').'.</h5>';
} elseif ($package === false) {
	echo '<h5 class="error">'.__('Server connection failed')."</h5>";
} elseif (is_int ($package) && $package == 0) {
	echo '<h5 class="error">'.__('Server authorization rejected')."</h5>";
} else {
	echo '<h5 class="suc">'.__('There\'s a new update for Integria Enterprise')."</h5>";
	
	$table->width = '50%';
	$table->data = array ();
	
	$table->data[0][0] = '<strong>'.__('Id').'</strong>';
	$table->data[0][1] = $package->id;
	
	$table->data[1][0] = '<strong>'.__('Timestamp').'</strong>';
	$table->data[1][1] = $package->timestamp;
	
	$table->data[2][0] = '<strong>'.__('Description').'</strong>';
	$table->data[2][1] = html_entity_decode ($package->description);
	
	print_table ($table);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	echo '<form method="post">';
	echo __('Overwrite local changes');
	print_checkbox ('force_update', '1', false);
	echo '<p />';
	print_input_hidden ('update_package', 1);
	print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
	echo '</form>';
	echo '</div>';
}

echo '<h4>'.__('Your system version number is').': '.$settings->current_update.'</h4>';

?>