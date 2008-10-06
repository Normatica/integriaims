<?php
// Babel Enterprise
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


/* Change to E_ALL for development/debugging */
error_reporting (E_ALL);

/* Database backend, not really tested with other backends, so it's 
 not functional right now */
define ('DB_BACKEND', 'mysql');
define ('FREE_USER', 'BABEL-FREE');
define ('DB_PREFIX', 'um_');

if (! extension_loaded ('mysql'))
	die ('Your PHP installation appears to be missing the MySQL extension which is required.');

require_once ('lib/libupdate_manager.php');

function get_user_key ($settings) {
	if ($settings->customer_key != FREE_USER) {
		if (! file_exists ($settings->keygen_path)) {
			echo '<h3 class="err">';
			echo lang_string('Keygen file does not exists');
			echo '</h3>';
			
			return '';
		}
		if (! is_executable ($settings->keygen_path)) {
			echo '<h3 class="err">';
			echo lang_string('Keygen file is not executable');
			echo '</h3>';
			
			return '';
		}
		
		global $config;
		
		$user_key = exec (escapeshellcmd ($settings->keygen_path.
				' '.$settings->customer_key.' '.$config['dbhost'].
				' '.$config['dbuser'].' '.$config['dbpassword'].
				' '.$config['dbname']));
		return $user_key;
	}
	
	/* Free users.
	   We only want to know this for statistics records.
	   Feel free to disable this extension if you want.
	 */
	$n = (int) give_db_value ('COUNT(`id_agent`)', 'tagent', 'disabled', 0);
	$m = (int) give_db_value ('COUNT(`id_agentmodule`)', 'tagentmodule',
				0, 0);
	$user_key = array ('A' => $n, 'M' => $m);
	
	return json_encode ($user_key);
}

flush ();
?>
