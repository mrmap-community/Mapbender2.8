<?php
# http://www.mapbender.org/index.php/Administration
# Copyright (C) 2002 CCGIS
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_connector.php");

header("Content-Type: application/xml");

if(isset($_REQUEST["url"])){
	$url = $_REQUEST["url"];
}else{
	echo("{}");
	exit;
}

$con = new connector();
$kmlString = $con->load($url);
try {
    echo $kmlString;
} catch (Exception $e) {
	echo($e);
	die;
}

?>
