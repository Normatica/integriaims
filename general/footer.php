<?PHP
// FRITS - the FRee Incident Tracking System
// =========================================
// Copyright (c) 2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
?>
<p align='center'>

<?PHP
global $config;
global $lang_label;

echo 'FRiTS '.$config["version"].' Build '.$config["build_version"].'<br>';

if (isset($_SESSION['id_usuario'])) {
	echo '<a target="_new" href="general/license/frits_info_'.$config["language_code"].'.html">'.
	$lang_label["gpl_notice"].'</a><br>';
	if (isset($_SERVER['REQUEST_TIME'])) {
		$time = $_SERVER['REQUEST_TIME'];
	} else {
		$time = time();
	}
	echo $lang_label["gen_date"]." ".date("D F d, Y H:i:s", $time)."<br>";
}
?>
</p>