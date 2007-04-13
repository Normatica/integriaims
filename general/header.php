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

echo "<table width=100% border=0>";
echo "<tr>";
if (isset ($_SESSION["id_usuario"])){
	echo "<td width=40%>";
	$id_usuario = entrada_limpia ($_SESSION["id_usuario"]);
	if (dame_admin($id_usuario)==1)
		echo "<img src='images/user_suit.png' class='bot'> ";
	else
		echo "<img src='images/user_green.png' class='bot'> ";
	echo $lang_label["has_connected"]. '
	[<b class="f10">'. $id_usuario. '</b>]';
	echo "<td width=30%>";
	echo "<a href='index.php?sec=main'><img src='images/information.png' class='bot'> ". $lang_label["information"]."</A>";
	echo "<td width=20%>";
	
	echo "<td align='right' width=10%>";
	echo "<a href='index.php?bye=bye'><img src='images/lock.png' class='bot'> ". $lang_label["logout"]."</A>";
}
echo "</table>";
?>