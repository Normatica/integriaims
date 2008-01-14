<?php

// INTEGRIA - OpenSource Management for the Enterprise
// http://integria.sourceforge.net
// ==================================================
// Copyright (c) 2007 Sancho Lerena, slerena@gmail.com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


	echo "<table width=100%>";
	
	// Show Agenda items
	echo "<tr><td>";
	echo "<h1>".$lang_label["agenda"]."</h1>";
	echo "<div align='center' style='height: 160px; width: 130px; padding: 0 0 0 0; margin: 0 0 0 0;'>";
	echo "<a href='index.php?sec=agenda&sec2=operation/agenda/agenda'><img src='images/calendar.png' border=0></A></div>";
	
	// Show Todo items
	echo "<td>";
	echo "<h1>".$lang_label["todo"]."</h1>";
	echo "<div align='center' style='height: 160px; width: 130px; padding: 0 0 0 0; margin: 0 0 0 0;'>";
	echo "<a href='index.php?sec=todo&sec2=operation/todo/todo'><img src='images/todo.png' border=0></a></div>";

	// Show Projects items
	echo "<tr><td>";
	echo "<h1>".$lang_label["projects"]."</h1>";
	echo "<div align='center' style='height: 160px; width: 130px; padding: 0 0 0 0; margin: 0 0 0 0;'>";
	echo "<a href='index.php?sec=projects&sec2=operation/projects/project'><img src='images/project.png' border=0></a></div>";
	
	// Show Incident items
	echo "<td>";
	echo "<h1>".$lang_label["incidents"]."</h1>";
	echo "<div align='center' style='height: 160px; width: 130px; padding: 0 0 0 0; margin: 0 0 0 0;'>";
	echo "<a href='index.php?sec=incidents&sec2=operation/incidents/incident'><img src='images/incidents.png' border=0></A></div>";

	echo "</table>";

?>