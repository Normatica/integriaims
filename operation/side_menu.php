<?PHP

if (isset($_GET["sec"]))
	$sec = $_GET["sec"];
else
	$sec = "";

if (isset($_GET["sec2"]))
	$sec2 = $_GET["sec2"];
else
	$sec2 = "";

if ($sec == "projects"){
	echo "<div class='portlet'>";
	echo "<h3>".$lang_label["projects"]."</h3>";
	echo "<ul class='sidemenu'>";

	// Project overview
	if ($sec2 == "operation/projects/project")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project'>".$lang_label["project_overview"]."</a></li>";
	
	// Project create
	if ($sec2 == "operation/projects/project_detail&insert_form")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&insert_form'>".$lang_label["create_project"]."</a></li>";

	echo "</ul>";
	echo "</div>";

	// Dynamic incident sub options menu (PROJECT)
	$id_project = give_parameter_get("id_project",-1);
	if ($id_project != -1){
		echo "<br>";

		echo "<div class='portlet'>";
		echo "<h3>".$lang_label["project"]." # $id_project</h3>";
		echo "<ul class='sidemenu'>";

		// Create task
		if ($sec2 == "operation/projects/task_detail")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&operation=create'>".$lang_label["create_task"]."</a></li>";

		// Tasks
		if ($sec2 == "operation/projects/task")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task&id_project=$id_project'>".$lang_label["task_list"]."</a></li>";


		// Project detail
		if ($sec2 == "operation/projects/project_detail")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/project_detail&id_project=$id_project'>".$lang_label["project_overview"]."</a></li>";

		// People management
		if ($sec2 == "operation/projects/people_manager")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/people_manager&id_task=-1&id_project=$id_project'>".$lang_label["people"]."</a></li>";

		// Workunits 
		$totalhours =  give_hours_project ($id_project);
		if ($totalhours > 0){
			if ($sec2 == "operation/projects/task_workunit")
				echo "<li id='sidesel'>";
			else	
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project'>".$lang_label["workunits"];
			echo " ( $totalhours ".$lang_label["hr"]." )";
			echo "</a></li>";
		}

		echo "</ul>";
		echo "</div>";
	}

	

	// Dynamic incident sub options menu (TASKS)
	$id_task = give_parameter_get("id_task",-1);
	if ($id_task != -1){
		echo "<br>";

		echo "<div class='portlet'>";
		echo "<h3>".$lang_label["task"]." # $id_task</h3>";
		echo "<ul class='sidemenu'>";
		
		// Task detail
		if ($sec2 == "operation/projects/task_detail")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/task_detail&id_project=$id_project&id_task=$id_task&operation=view'>".$lang_label["task_detail"]."</a></li>";

		// Task people_manager
		if ($sec2 == "operation/projects/operation/projects/people_manager")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=projects&sec2=operation/projects/people_manager&id_project=$id_project&id_task=$id_task'>".$lang_label["people"]."</a></li>";

		// Workunits 
		$totalhours =  give_hours_task ($id_task);
		if ($totalhours > 0){
			if ($sec2 == "operation/projects/task_workunit")
				echo "<li id='sidesel'>";
			else	
				echo "<li>";
			echo "<a href='index.php?sec=projects&sec2=operation/projects/task_workunit&id_project=$id_project&id_task=$id_task'>".$lang_label["workunits"];
			echo " ( $totalhours ".$lang_label["hr"]." )";
			echo "</a></li>";
		}

		echo "</ul>";
		echo "</div>";
	}


}		

if ($sec == "incidents"){
	echo "<div class='portlet'>";
	echo "<h3>".$lang_label["incidents"]."</h3>";
	echo "<ul class='sidemenu'>";
	// Incident overview
	if ($sec2 == "operation/incidents/incident")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident'>".$lang_label["incidents_overview"]."</a></li>";

	if (give_acl($_SESSION["id_usuario"], 0, "IW")==1) {
		// Incident creation
		if ($sec2 == "sec2=operation/incidents/incident_detail&insert_form")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail&insert_form'>".$lang_label["create_incident"]."</a></li";
	}

	// Incident search
	if ($sec2 == "operation/incidents/incident_search")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_search'>Search incident</a></li>";
	
	echo "</ul>";
	echo "</div>";

	// Dynamic incident sub options menu
	$id_incident = give_parameter_get("id",-1);
	if ($id_incident != -1){
		echo "<br>";

		echo "<div class='portlet'>";
		echo "<h3>".$lang_label["incident"]." # $id_incident</h3>";
		echo "<ul class='sidemenu'>";
		// Add workunit to incident
		if ($sec2 == "operation/incidents/incident_create_work")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_create_work&id=$id_incident'>".$lang_label["add_workunit"]."</a></li>";
		
		// Add file to incident
		if ($sec2 == "operation/incidents/incident_attach_file")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_attach_file&id=$id_incident'>".$lang_label["add_file"]."</a></li>";

		// Incident tracking
		if ($sec2 == "operation/incidents/incident_tracking")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_tracking&id=$id_incident'>".$lang_label["tracking"];
		echo "</a></li>";
	
		// Incident workunits
		$timeused = give_hours_incident ($id_incident);
		if ($timeused > 0) {
			if ($sec2 == "operation/incidents/incident_workunits")
				echo "<li id='sidesel'>";
			else	
				echo "<li>";
			
			echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_workunits&id=$id_incident'>".$lang_label["workunits_view"];
			echo " ( $timeused ".$lang_label["hr"]." )";
			echo "</a></li>";
		}

		// Incident files
		$file_number = give_number_files_incident ($id_incident);
		if ($file_number > 0){
			if ($sec2 == "operation/incidents/incident_files")
				echo "<li id='sidesel'>";
			else	
				echo "<li>";
			
			echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_files&id=$id_incident'>".$lang_label["incident_files"];
			echo " ( $file_number )";
			echo "</a></li>";
		}

		// Incident detail
		if ($sec2 == "operation/incidents/incident_detail")
			echo "<li id='sidesel'>";
		else	
			echo "<li>";
		echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident_detail&id=$id_incident'>".$lang_label["incident_detail"]."</a></li>";

		echo "</ul>";
		echo "</div>";
	}

}

if ($sec == "todo"){
	echo "<div class='portlet'>";
	echo "<h3>".$lang_label["todo"]."</h3>";
	echo "<ul class='sidemenu'>";

	// Todo overview
	if ($sec2 == "operation/todo/todo")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=todo&sec2=operation/todo/todo'>".$lang_label["todo"]."</a></li>";
	
	// Todo create
	if ($sec2 == "operation/todo/todo&form_add=1")
		echo "<li id='sidesel'>";
	else	
		echo "<li>";
	echo "<a href='index.php?sec=incidents&sec2=operation/todo/todo&form_add=1'>".$lang_label["add_todo"]."</a></li>";
	echo "</ul>";
	echo "</div>";
}


if ($sec == "users"){
	echo "<h1>Users</h1>";
	echo "<ul class='sidemenu'>";
		echo "<li><a href='index.php?sec=users&amp;sec2=operation/users/user'>View users</a></li>";
		echo "<li><a href='index.php?sec=users&sec2=operation/users/user_edit&ver=".$_SESSION["id_usuario"]."'>Edit my user</a></li>";
		echo "<li><a href='index.php?sec=users&sec2=operation/users/user_spare_workunit'>Spare Workunit</a></li>";
		echo "<li><a href='index.php?sec=users&sec2=operation/users/user_workunit_report'>".$lang_label["work_unit_report"]."</a></li>";
	echo "</ul>";

	if (give_acl($config["id_user"], 0, "UM")){
		echo "<h1>".$lang_label["user_management"]."</h1>";
		echo "<ul class='sidemenu'>";
		echo "<li><a href='index.php?sec=users&sec2=godmode/usuarios/lista_usuarios'>".$lang_label["manage_user"]."</a></li>";
		echo "<li><a href='index.php?sec=users&sec2=godmode/usuarios/role_manager'>".$lang_label["manage_roles"]."</a></li>";
		echo "</ul>";
	}
	
}


/*
// Sponsors

echo "<h1>".$lang_label["links_header"]."</h1>";
echo "<ul class='sidemenu'>";

$sql1='SELECT * FROM tlink ORDER BY name';
$result=mysql_query($sql1);
if ($row=mysql_fetch_array($result)){
	$sql1='SELECT * FROM tlink ORDER BY name';
	$result2=mysql_query($sql1);
	while ($row2=mysql_fetch_array($result2)){
		echo "<li><a href='".$row2["link"]."' target='_new' class='mn'>".$row2["name"]."</a></li>";
	}
}
echo "</ul>";

// Banners

echo "<h1>Our sponsors</h1>";
echo "<p>";
echo "<img src='images/minilogoartica.jpg'>";
echo "<br><br>";
echo "<img src='images/sflogo.png'>";
echo "<br><br>";
echo "</p>";

*/

// Testing boxes for side menus
echo '
 <div class="portlet">
  <a href="javascript:;" onmousedown="toggleDiv(\'userdiv\');"><h2>Info de prueba</h2></a>
  <div class="portletBody" id="userdiv">  
      <b>nil</b>
      (<a href="/~slerena">slerena</a>)<br />
            <b>Preferred address:</b><br />
            &nbsp;&nbsp;&nbsp;
              slerena@gmail.com
            <br />
        <b>Timezone:</b>
        Europe/Madrid
        <br />
        <b>Nosequé:</b>
        <span>10</span><br />
  </div>
</div>

';

?>
