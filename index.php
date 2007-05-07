<?php

// FRITS - the FRee Incident Tracking System
// =========================================
// Copyright (c) 2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// FRITS 1.x uses icons from famfamfam, licensed under CC Atr. 2.5
// Silk icon set 1.3 (cc) Mark James, http://www.famfamfam.com/lab/icons/silk/
// FRITS 1.x uses Pear Image::Graph code
// FRITS shares much of it's code with project Babel Enterprise and Pandora FMS,
// also a Free Software Project coded by some of the people who makes FRITS.

$develop_bypass = 1;
if ($develop_bypass != 1){
	// If no config file, automatically try to install
	if (! file_exists("include/config.php")){
		include ("install.php");
		exit;
	}
	// Check for installer presence
	if (file_exists("install.php")){
		include "general/error_install.php";
		exit;
	}
	// Check perms for config.php
	if ((substr(sprintf('%o', fileperms('include/config.php')), -4) != "0600") &&
	    (substr(sprintf('%o', fileperms('include/config.php')), -4) != "0660") &&
	    (substr(sprintf('%o', fileperms('include/config.php')), -4) != "0640") &&
	    (substr(sprintf('%o', fileperms('include/config.php')), -4) != "0600"))
	{
		include "general/error_perms.php";
		exit;
	}
}

// Real start
session_start(); 

include "include/config.php";
global $config;
include "include/languages/language_".$config["language_code"].".php";
require "include/functions.php"; // Including funcions.
require "include/functions_db.php";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
// Refresh page
if ($intervalo = give_parameter_get ("refr") != "") {
	// Agent selection filters and refresh
 	if ($ag_group = give_parameter_post ("ag_group" != "")) {
		$query = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . '&ag_group_refresh=' . $ag_group;
		echo '<meta http-equiv="refresh" content="' . $intervalo . '; URL=' . $query . '">';
	} else 
		echo '<meta http-equiv="refresh" content="' . $intervalo . '">';	
}
?>
<title>FRITS - the Free distRibuted enterprIse project Tracking System</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/frits.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/frits.css" type="text/css">
<script type='text/JavaScript' src='include/calendar.js'></script>
</head>

<?php
	// Show custom background
	echo '<body background="images/backgrounds/' . $config["bgimage"] . '">';
	
        // Login process 
   	if ( (! isset ($_SESSION['id_usuario'])) AND (isset ($_GET["login"]))) {
		$nick = give_parameter_post ("nick");
		$pass = give_parameter_post ("pass");
		
		// Connect to Database
		$sql1 = 'SELECT * FROM tusuario WHERE id_usuario = "'.$nick.'"';
		$result = mysql_query ($sql1);
		
		// For every registry
		if ($row = mysql_fetch_array ($result)){
			if ($row["password"] == md5 ($pass)){
				// Login OK
				// Nick could be uppercase or lowercase (select in MySQL
				// is not case sensitive)
				// We get DB nick to put in PHP Session variable,
				// to avoid problems with case-sensitive usernames.
				// Thanks to David Muñiz for Bug discovery :)
				$nick = $row["id_usuario"];
				unset ($_GET["sec2"]);
				$_GET["sec"] = "general/logon_ok";
				update_user_contact ($nick);
				logon_db ($nick, $config["REMOTE_ADDR"]);
				$_SESSION['id_usuario'] = $nick;
				
			} else {
				// Login failed (bad password)
				unset ($_GET["sec2"]);
				include "general/logon_failed.php";
				// change password to do not show all string
				$primera = substr ($pass,0,1);
				$ultima = substr ($pass, strlen ($pass) - 1, 1);
				$pass = $primera . "****" . $ultima;
				audit_db ($nick, $config["REMOTE_ADDR"], "Logon Failed",
					  "Incorrect password: " . $nick . " / " . $pass);
				echo '<div id="foot">';
				include "general/footer.php";
				echo '</div>';
				exit;
			}
		} else {
			// User not known
			unset ($_GET["sec2"]);
			include "general/logon_failed.php";
			$primera = substr ($pass, 0, 1);
			$ultima = substr ($pass, strlen ($pass) - 1, 1);
			$pass = $primera . "****" . $ultima;
			audit_db ($nick, $REMOTE_ADDR, "Logon Failed",
				  "Invalid username: " . $nick . " / " . $pass);
			echo '<div id="foot">';
			include "general/footer.php";
			echo '</div>';
			exit;
		} 
	} elseif (! isset ($_SESSION['id_usuario'])) {
		// There is no user connected
		include "general/login_page.php";
		exit;
	}

	// Log off
	if (isset ($_GET["bye"])) {
		include "general/logoff.php";
		$iduser = $_SESSION["id_usuario"];
		logoff_db ($iduser, $config["REMOTE_ADDR"]);
		session_unregister ("id_usuario");
		exit;
	}
	$pagina = "";
	if (isset ($_GET["sec2"])){
		$sec2 = parametro_limpio ($_GET["sec2"]);
		$pagina = $sec2;
	} else
		$sec2 = "";
		
	if (isset ($_GET["sec"])){
		$sec = parametro_limpio ($_GET["sec"]);
		$pagina = $sec2;
	}
	else
		$sec = "";
	// http://es2.php.net/manual/en/ref.session.php#64525
	// Session locking concurrency speedup!
	session_write_close(); 
?>


<div id="wrap"> 
	<div id="header">	
		<?php require("general/header.php"); ?>	
	</div>	

	<div id="menu">
		<?php require("operation/main_menu.php"); ?>	
	</div>

	<div id="content-wrap">  
		<div id="sidebar">
		<?php require("operation/side_menu.php"); ?>	
		</div>

		<div id="main"> 
		<?php
			// Page loader / selector		
			if ($pagina != ""){
				if (file_exists ($pagina . ".php")) {
					require ($pagina . ".php");
				} else {
					echo "<br><b class='error'>Sorry! I can't find the page!</b>";
				}	
			} else
				require ("general/logon_ok.php");  //default
		?>		
		</div>
	<!-- content-wrap ends here -->	
	</div>
<!-- wrap ends here -->
</div>		

<!-- footer starts here -->		
<div id="footer">
	<?php require("general/footer.php") ?></div>
</div>	
<!-- footer ends here -->	

</body>
</html>

