<?php

// FRITS - the FRee Incident Tracking System
// =========================================
// Copyright (c) 2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars

?>

<script language="javascript">

	/* Function to hide/unhide a specific Div id */
	function toggleDiv (divid){
		if (document.getElementById(divid).style.display == 'none'){
			document.getElementById(divid).style.display = 'block';
		} else {
			document.getElementById(divid).style.display = 'none';
		}
	}
</script>

<?PHP

global $config;

if (check_login() != 0) {
 	audit_db("Noauth",$config["REMOTE_ADDR"], "No authenticated access","Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["id_grupo"]))
	$id_grupo = $_GET["id_grupo"];
else
	$id_grupo = 0;

$id_user=$_SESSION['id_usuario'];
if (give_acl($id_user, $id_grupo, "IR") != 1){
 	// Doesn't have access to this page
	audit_db($id_user,$config["REMOTE_ADDR"], "ACL Violation","Trying to access to incident ".$id_inc." '".$titulo."'");
	include ("general/noaccess.php");
	exit;
}

$id_grupo = "";
$creacion_incidente = "";
$result_msg = "";

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// UPDATE incident - Get data from form
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((isset($_GET["action"])) AND ($_GET["action"]=="update")){
	$id_inc = $_POST["id_inc"];
 	$grupo = clean_input ($_POST['grupo_form']);
	$usuario= clean_input ($_POST["usuario_form"]);
	if ((give_acl($config["id_user"], $grupo, "IM")==1) OR ($usuario == $config["id_user"])) { // Only admins (manage incident) or owners can modify incidents
		$id_author_inc = give_incident_author($id_inc);
		$titulo = clean_input ($_POST["titulo"]);
		$descripcion = clean_input ($_POST['descripcion']);
		$origen = give_parameter_post ("incident_origin",1);
		$prioridad = clean_input ($_POST['prioridad_form']);
		$estado = clean_input ($_POST["incident_status"]);
		$ahora=date("Y/m/d H:i:s");
		if (isset($_POST["email_notify"]))
			$email_notify= give_parameter_post ("email_notify");
		else
			$email_notify = 0;
		$epilog = give_parameter_post ("epilog","");
		$descripcion =  give_parameter_post ('descripcion');
		$resolution =  give_parameter_post ("incident_resolution");
		$id_task =  give_parameter_post ("task_user");
		
		incident_tracking ( $id_inc, $config["id_user"], 1);
		$old_prio = give_inc_priority ($id_inc);
		// 0 - Abierta / Sin notas (Open without notes)
		// 2 - Descartada (Not valid)
		// 3 - Caducada (out of date)
		// 13 - Cerrada (closed)
		if ($old_prio != $prioridad)
			incident_tracking ( $id_inc, $id_usuario, 8);		
		if ($estado == 2)
			incident_tracking ( $id_inc, $id_usuario, 4);	
		if ($estado == 3)
			incident_tracking ( $id_inc, $id_usuario, 5);
		if ($estado == 13)
			incident_tracking ( $id_inc, $id_usuario, 10);
			
		$sql = "UPDATE tincidencia 
				SET actualizacion = '$ahora', titulo = '$titulo', 
				origen= '$origen', estado = '$estado', id_grupo = '$grupo', 
				id_usuario = '$usuario', notify_email = $email_notify, 
				prioridad = '$prioridad', descripcion = '$descripcion', 
				epilog = '$epilog', id_task = $id_task, resolution = '$resolution' 
				WHERE id_incidencia = ".$id_inc;

		$result=mysql_query($sql);
		audit_db($id_author_inc,$config["REMOTE_ADDR"],"Incident updated","User ".$id_usuario." deleted updated #".$id_inc);
		if ($result)
			$result_msg = "<h3 class='suc'>".$lang_label["upd_incid_ok"]."</h3>";
		else
			$result_msg = "<h3 class='suc'>".$lang_label["upd_incid_no"]."</h3>";
		$_GET["id"] = $id_inc; // HACK
	} else {
		audit_db($id_usuario,$config["REMOTE_ADDR"],"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to update incident");
		echo "<h3 class='error'>".$lang_label["upd_incid_no"]."</h3>";
		no_permission();
	}
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// INSERT incident - Get data from form
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((isset($_GET["action"])) AND ($_GET["action"]=="insert")){
	$grupo = clean_input ($_POST['grupo_form']);
	$usuario= clean_input ($_POST["usuario_form"]);
	if ((give_acl($id_usuario, $grupo, "IM") == 1) OR ($usuario == $id_usuario)) { // Only admins (manage
		// Read input variables
		$titulo = clean_input ($_POST['titulo']);
		$inicio = date("Y/m/d H:i:s");
		$descripcion = clean_input ($_POST['descripcion']);
		$texto = $descripcion; // to view in textarea after insert
		$origen = give_parameter_post ("incident_origin",1);
		$prioridad = clean_input ($_POST['prioridad_form']);
		$actualizacion = $inicio;
		$id_creator = $id_usuario;
		$estado = clean_input ($_POST["incident_status"]);
		$id_task =  give_parameter_post ("task_user");
		if (isset($_POST["email_notify"]))
			$email_notify=clean_input ($_POST["email_notify"]);
		else
			$email_notify = 0;
		
		$sql = " INSERT INTO tincidencia (inicio, actualizacion, titulo , descripcion, id_usuario, origen, estado, prioridad, id_grupo, id_creator, notify_email, id_task) VALUES ('$inicio','$actualizacion', '$titulo', '$descripcion', '$usuario', '$origen', '$estado', '$prioridad', '$grupo', '$id_creator', $email_notify, $id_task)";
		if (mysql_query($sql)){
			$id_inc=mysql_insert_id();
			$_GET["id"] = $id_inc; // HACK
			$result_msg  = "<h3 class='suc'>".$lang_label["create_incid_ok"]." ( id #$id_inc )</h3>";
			audit_db($config["id_user"],$config["REMOTE_ADDR"],"Incident created","User ".$id_usuario." created incident #".$id_inc);
			incident_tracking ( $id_inc, $config["id_user"], 0);
		}
		
	} else {
		audit_db($id_usuario,$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to create incident");
		no_permission();
	}
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Edit / Visualization MODE - Get data from database
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (isset($_GET["id"])){
	$creacion_incidente = 0;
	$id_inc = $_GET["id"];
	$iduser_temp=$_SESSION['id_usuario'];
	// Obtain group of this incident
	$sql1='SELECT * FROM tincidencia WHERE id_incidencia = '.$id_inc;
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	// Get values
	$titulo = $row["titulo"];
	$texto = $row["descripcion"];
	$inicio = $row["inicio"];
	$actualizacion = $row["actualizacion"];
	$estado = $row["estado"];
	$prioridad = $row["prioridad"];
	$origen = $row["origen"];
	$usuario = $row["id_usuario"];
	$nombre_real = dame_nombre_real($usuario);
	$id_grupo = $row["id_grupo"];
	$id_creator = $row["id_creator"];
	$email_notify=$row["notify_email"];
	$resolution = $row["resolution"];
	$epilog = $row["epilog"];
	$id_task = $row["id_task"];
	$id_incident_linked = $row["id_incident_linked"]; 
	$grupo = dame_nombre_grupo($id_grupo);

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Note add
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if (isset($_GET["insertar_nota"])){
		$id_inc = give_parameter_post ("id_inc");
		$timestamp = give_parameter_post ("timestamp");
		$nota = give_parameter_post ("nota");
		$workunit = give_parameter_post ("workunit",0);
		$timeused = give_parameter_post ("duration",0);
		$id_usuario=$_SESSION["id_usuario"];
		$have_cost = give_parameter_post ("have_cost",0);
		$profile = give_parameter_post ("work_profile",0);
		$sql1 = "INSERT INTO tnota (id_usuario,timestamp,nota) VALUES ('".$id_usuario."','".$timestamp."','".$nota."')";
		$res1=mysql_query($sql1);
		if ($res1) 
			$result_msg = "<h3 class='suc'>".$lang_label["create_note_ok"]."</h3>";
		// get inserted note_number
		$id_nota = mysql_insert_id();
		
		$sql3 = "INSERT INTO tnota_inc (id_incidencia, id_nota) VALUES (".$id_inc.",".$id_nota.")";
		$res3=mysql_query($sql3);

		$sql4 = "UPDATE tincidencia SET actualizacion = '".$timestamp."' WHERE id_incidencia = ".$id_inc;
		$res4 = mysql_query($sql4);
		incident_tracking ( $id_inc, $id_usuario, 2);

		// Add work unit if enabled
		if ($workunit == 1){
			$sql = "INSERT INTO tworkunit (timestamp, duration, id_user, description) VALUES ('$timestamp', '$timeused', '$id_usuario', '$nota')";
			$res5 = mysql_query($sql);

			$id_workunit = mysql_insert_id();
			$sql1 = "INSERT INTO tworkunit_incident (id_incident, id_workunit) VALUES ($id_inc, $id_workunit)";
			$res6 = mysql_query($sql1);

		}
	}
	
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Upload file
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ((give_acl($iduser_temp, $id_grupo, "IW")==1) AND isset($_GET["upload_file"])) {
		if ( $_FILES['userfile']['name'] != "" ){ //if file
			$tipo = $_FILES['userfile']['type'];
			if (isset($_POST["file_description"]))
				$description = $_POST["file_description"];
			else
				$description = "No description available";
			// Insert into database
			$filename= $_FILES['userfile']['name'];
			$filesize = $_FILES['userfile']['size'];

			$sql = " INSERT INTO tattachment (id_incidencia, id_usuario, filename, description, size ) VALUES (".$id_inc.", '".$iduser_temp." ','".$filename."','".$description."',".$filesize.") ";

			mysql_query($sql);
			$id_attachment=mysql_insert_id();
			incident_tracking ( $id_inc, $id_usuario, 3);
			$result_msg="<h3 class='suc'>".$lang_label["file_added"]."</h3>";
			// Copy file to directory and change name
			$nombre_archivo = $config["homedir"]."attachment/pand".$id_attachment."_".$filename;

			if (!(copy($_FILES['userfile']['tmp_name'], $nombre_archivo ))){
					$result_msg = "<h3 class=error>".$lang_label["attach_error"]."</h3>";
				$sql = " DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
				mysql_query($sql);
			} else {
				// Delete temporal file
				unlink ($_FILES['userfile']['tmp_name']);
			}
		}
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// SHOW TABS
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	echo "<div id='menu_tab'><ul class='mn'>";

	// This view
	echo "<li class='nomn'>";
	echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=$id_inc'><img src='images/page_white_text.png' class='top' border=0> ".$lang_label["Incident"]." </a>";
	echo "</li>";

	// Tracking
	echo "<li class='nomn'>";
	echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_tracking&id=$id_inc'><img src='images/eye.png' class='top' border=0> ".$lang_label["tracking"]." </a>";
	echo "</li>";

	// Workunits
	$timeused = give_hours_incident ( $id_inc);
	echo "<li class='nomn'>";
	if ($timeused > 0)
		echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_work&id_inc=$id_inc'><img src='images/award_star_silver_1.png' class='top' border=0> ".$lang_label["workunits"]." ($timeused)</a>";
	else
		echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_work&id_inc=$id_inc'><img src='images/award_star_silver_1.png' class='top' border=0> ".$lang_label["workunits"]."</a>";
	echo "</li>";

	
	// Attach
	$file_number = give_number_files_incident($id_inc);
	if ($file_number > 0){
		echo "<li class='nomn'>";
		echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_files&id=$id_inc'><img src='images/disk.png' class='top' border=0> ".$lang_label["Attachment"]." ($file_number) </a>";
		echo "</li>";
	}

	// Notes
	$note_number = dame_numero_notas($id_inc);
	if ($note_number > 0){
		echo "<li class='nomn'>";
		echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_notes&id=$id_inc'><img src='images/note.png' class='top' border=0> ".$lang_label["Notes"]." ($note_number) </a>";
		echo "</li>";
	}
	
	echo "</ul>";
	echo "</div>";
	echo "<div style='height: 25px'> </div>";

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Prepare the insertion data
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
} elseif (isset($_GET["insert_form"])){
		$iduser_temp=$_SESSION['id_usuario'];
		$titulo = "";
		$titulo = "";
		$descripcion = "";
		$origen = 0;
		$prioridad = 2;
		$id_grupo = 1;
		$grupo = dame_nombre_grupo(1);

		$usuario= $config["id_user"];
		$estado = 1;
		$resolution = 9;
		$id_task = 0;
		$epilog = "";
		$actualizacion=date("Y/m/d H:i:s");
		$inicio = $actualizacion;
		$id_creator = $iduser_temp;
		$creacion_incidente = 1;
		$email_notify = 0;

} else {
	audit_db($id_user,$config["REMOTE_ADDR"], "HACK","Trying to create incident in a unusual way");
	no_permission();
	exit;
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Show the form
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($creacion_incidente == 0)
	echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&action=update'>";
else
	echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&action=insert'>";

if (isset($id_inc)) {
	echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Main incident table
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if (isset($id_inc)) {
	echo "<h1>".$lang_label["incident"]." # $id_inc</h1>";
} else {
	echo "<h2>".$lang_label["create_incident"]."</h2>";
}

echo $result_msg;

echo '<table width=740 class="databox_color" cellpadding=2 cellspacing=2 >';


// Title and email notify
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp))
	echo '<tr><td class="datos"><b>'.$lang_label["incident"].'</b><td colspan=2 class="datos"><input type="text" name="titulo" size=50 value="'.$titulo.'">';
else
	echo '<tr><td class="datos"><b>'.$lang_label["incident"].'</b><td colspan=2 class="datos"><input type="text" name="titulo" size=50 value="'.$titulo.'" readonly>';

if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp))
	$emdis="";
else
	$emdis="DISABLED";

echo '<td class="datos"> ';
if ($email_notify == 1)
	echo "<input $emdis type=checkbox value=1 name='email_notify' CHECKED>";
else
	echo "<input $emdis type=checkbox value=1 name='email_notify'>";

echo "&nbsp;&nbsp;<b>".$lang_label["email_notify"];
echo "</b> <a href='#' class='tip'>&nbsp;<span>";
echo $lang_label["email_notify_help"];
echo "</span></a>";

// Priority combo
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)){
	echo '<tr><td class="datos2"><b>'.$lang_label["priority"].'</b>';
	echo '<td class="datos2"><select name="prioridad_form">';
} else {
	echo '<td class="datos2"><b>'.$lang_label["priority"].'</b>';
	echo '<td class="datos2"><select disabled name="prioridad_form">';
}

switch ( $prioridad ){
	case 0: echo '<option value="0">'.$lang_label["informative"]; break;
	case 1: echo '<option value="1">'.$lang_label["low"]; break;
	case 2: echo '<option value="2">'.$lang_label["medium"]; break;
	case 3: echo '<option value="3">'.$lang_label["serious"]; break;
	case 4: echo '<option value="4">'.$lang_label["very_serious"]; break;
	case 10: echo '<option value="10">'.$lang_label["maintenance"]; break;
}

echo '<option value="0">'.$lang_label["informative"];
echo '<option value="1">'.$lang_label["low"];
echo '<option value="2">'.$lang_label["medium"];
echo '<option value="3">'.$lang_label["serious"];
echo '<option value="4">'.$lang_label["very_serious"];
echo '<option value="10">'.$lang_label["maintenance"];


// Incident STATUS combo
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<td class="datos2"><b>'.$lang_label["status"].'</b><td class="datos2">';
// Status combo
if ((give_acl($config["id_user"], $id_grupo, "IM")==1) OR ($usuario == $config["id_user"]) ){
	if ($creacion_incidente == 0){
		echo combo_incident_status ($estado, 0, 0);
	} else {
		echo combo_incident_status ($estado, 0, 1);
	}
} else {
	echo combo_incident_status ($estado, 1, 0);
}

// User and owner
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<tr><td class="datos"><b>'.$lang_label["assigned_user"].'</b><td class="datos">';
if (give_acl($config["id_user"], $id_grupo, "IM")==1) {
	echo "<select name='usuario_form' class='w200'>";
	echo "<option value='".$usuario."'>".dame_nombre_real($usuario);
	
	// Show users from my groups
	$sql_1="SELECT * FROM tusuario_perfil WHERE id_usuario = '$id_usuario'";
	$result_1=mysql_query($sql_1);
	while ($row_1=mysql_fetch_array($result_1)){
		$sql_2="SELECT * FROM tusuario_perfil WHERE id_grupo = ".$row_1["id_grupo"];
		$result_2=mysql_query($sql_2);
		while ($row_2=mysql_fetch_array($result_2)){
			if (give_acl($row_2["id_usuario"], $row_2["id_grupo"], "IR")==1)
				if ($row_2["id_usuario"] != $usuario)
					echo "<option value='".$row_2["id_usuario"]."'>".dame_nombre_real($row_2["id_usuario"]);
		}
	}
	echo "</select>";
}
else {
	echo "<input type=hidden name='usuario_form' value='".$usuario."'>";
	echo $usuario;
}
echo "<td class='datos'><b>Creator</b><td class='datos'>".$id_creator." ( <i>".dame_nombre_real($id_creator)." </i>)";


// Origin combo
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<tr><td class="datos2"><b>'.$lang_label["source"].'</b><td class="datos2">';
// Only owner could change source or user with Incident management privileges
if ((give_acl($config["id_user"], $id_grupo, "IM")==1) OR ($usuario == $config["id_user"]))
	echo combo_incident_origin ($origen, 0);
else
	echo combo_incident_origin ($origen, 1);
	

// Group combo
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp))
	echo '<td class="datos2"><b>'.$lang_label["group"].'</b><td class="datos2"><select name="grupo_form" class="w135">';
else
	echo '<td class="datos2"><b>'.$lang_label["group"].'</b><td class="datos2"><select disabled name="grupo_form" class="w135">';
if ($id_grupo != 0)
	echo "<option value='".$id_grupo."'>".$grupo;
$sql1='SELECT * FROM tgrupo ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if (give_acl($iduser_temp, $row["id_grupo"], "IR")==1)
		echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
}
echo '</select>';


// Incident Resolution combo
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<tr><td class="datos"><b>'.$lang_label["resolution"].'</b><td class="datos">';
// Status combo
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)){
	echo combo_incident_resolution ($resolution, 0);
} else {
	echo combo_incident_resolution ($resolution, 1);
}

// Incident linked to a task
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<td class="datos"><b>'.$lang_label["task"].'</b><td class="datos">';
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp))
	echo combo_task_user ($id_task, $config["id_user"], 0);
else 
	echo combo_task_user ($id_task, $config["id_user"], 1);



// Description Textarea
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp))
	echo '<tr><td class="datos2" colspan="4"><textarea name="descripcion" rows="15" cols="100">';
else
	echo '<tr><td class="datos2" colspan="4"><textarea readonly name="descripcion" rows="15" cols="100">';
if (isset($texto)) {
	echo $texto;
}
echo "</textarea>";

// Epilog
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) AND ($estado > 5)){
	echo "<tr><td class='datos' colspan='4'><b>".$lang_label["resolution_epilog"]."</b>";
	echo '<tr><td class="datos2" colspan="4"><textarea name="epilog" rows="3" cols="100">';
	if (isset($epilog)) {
		echo $epilog;
	}
	echo "</textarea>";
} 

echo "</table>";

// UPDATE / INSERT BUTTON
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ($creacion_incidente == 0){
	if ((give_acl($config["id_user"], $id_grupo, "IM")==1) OR ($usuario == $config["id_user"])){
		echo '<input type="submit" class="sub next" name="accion" value="'.$lang_label["in_modinc"].'" border="0">';
	}
} else {
	if (give_acl($config["id_user"], $id_grupo, "IW")) {
		echo '<input type="submit" class="sub create" name="accion" value="'.$lang_label["create"].'" border="0">';
	}
}
echo "</form>";
echo "</table>";

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ADD NOTE CONTROL
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ($creacion_incidente == 0){
 	if (give_acl($config["id_user"], $id_grupo, "IR")==1){
		?>
			<h3><img src='images/note.png'>&nbsp;&nbsp;
			<a href="javascript:;" onmousedown="toggleDiv('note_control');">
		<?PHP
		echo $lang_label["add_note"]."</A></h3>";
	
		$ahora=date("Y/m/d H:i:s");
		echo "<div id='note_control' style='display:none'>";
		echo "<table cellpadding=3 cellspacing=3 border=0 width='700' class='databox_color' >";
		echo "<form name='nota' method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insertar_nota=1&id=".$id_inc."'>";
		echo "<input type='hidden' name='timestamp' value='".$ahora."'>";
		echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
		echo "<tr><td class='datos' width='140'><b>".$lang_label["date"]."</b></td>";
		echo "<td class='datos'>".$ahora;
	
	
		echo "<tr><td class='datos2'  width='140'>";
		echo "<b>".$lang_label["profile"]."</b>";
		echo "<td class='datos2'>";
		echo "<select name='work_profile'>";
		echo "<option value=0>N/A";
		echo "</select>";
		
		echo "&nbsp;&nbsp;";
		echo "<input type='checkbox' name='have_cost' value=1>";
		echo "&nbsp;&nbsp;";
		echo "<b>".$lang_label["have_cost"]."</b>";
	
		echo "<tr><td class='datos'>";
		echo "<b>".$lang_label["time_used"]."</b>";
		echo "<td class='datos'>";
		echo "<input type='text' name='duration' value='0' size='7'>";
		
		echo "<tr><td class='datos'><b>".$lang_label["add_workunit_inc"]."</b>";
		echo "<td class='datos'><input type='checkbox' value='1' name='workunit'>";
		
	
		echo '<tr><td colspan="2" class="datos2"><textarea name="nota" rows="6" cols="90">';
		echo '</textarea>';
		echo "</tr></table>";
		echo '<input name="addnote" type="submit" class="sub next" value="'.$lang_label["add"].'">';
		echo "</form>";
		echo "<br></div>";
	}
}


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Upload control
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if ($creacion_incidente == 0){
	if (give_acl($config["id_user"], $id_grupo, "IW")==1){
		?>
			<h3><img src='images/disk.png'>&nbsp;&nbsp;
			<a href="javascript:;" onmousedown="toggleDiv('upload_control');">
		<?PHP
		echo $lang_label["upload_file"]."</A></h3>";

		echo "<div id='upload_control' style='display:none'>";
		echo "<table cellpadding=4 cellspacing=4 border=0 width='700' class='databox_color'>";
		echo "<tr>";
		echo '<td class="datos">'.$lang_label["filename"].'</td><td class="datos">';
		echo '<form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&upload_file=1" enctype="multipart/form-data">';
		echo '<input type="file" name="userfile" value="userfile" class="sub" size="40">';
		echo '<tr><td class="datos2">'.$lang_label["description"].'</td><td class="datos2" colspan=3><input type="text" name="file_description" size=47>';
		echo "</td></tr></table>";
		echo '<input type="submit" name="upload" value="'.$lang_label["upload"].'" class="sub next">';
		echo "</form>";
		echo '</div><br>';
	}
	echo "</table>";
} // create mode

?>
