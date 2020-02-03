<?php
# $Id: mod_showCapDiff.php 3342 2008-12-16 12:31:26Z mschulz $
# http://www.mapbender.org/index.php/Monitor_Capabilities
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

require_once(dirname(__FILE__)."/../../conf/mapbender.conf");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_mb_exception.php");
//validate parameters
if (isset($_REQUEST["serviceType"]) & $_REQUEST["serviceType"] != "") {
	$testMatch = $_REQUEST["serviceType"];	
 	if (!($testMatch == 'wms' or $testMatch == 'wfs')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>serviceType</b> is not valid (wms, wfs).<br/>'; 
		die(); 		
 	}
	$serviceType = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
        //validate integer
        $testMatch = $_REQUEST["id"];
        //give max 99 entries - more will be to slow
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                echo 'Parameter <b>id</b> is not valid (integer).<br/>';
                die();
        }
        $id = $testMatch;
        $testMatch = NULL;
}
switch ($serviceType) {
	case "wms":
		$sql = "SELECT cap_diff FROM mb_wms_availability WHERE fkey_wms_id = $1";
		$v = array($id);
		break;
	case "wfs":
		$sql = "SELECT cap_diff FROM mb_wfs_availability WHERE fkey_wfs_id = $1";
		$v = array($id);
		break;
}
$t = array('i');
$res = db_prep_query($sql,$v,$t);
$cap_diff_row = db_fetch_row($res);
$html = urldecode($cap_diff_row[0]);
echo $html;
?>
