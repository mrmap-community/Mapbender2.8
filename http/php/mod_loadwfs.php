<?php
# $Id: mod_loadwfs.php 8785 2014-02-28 11:51:21Z armin11 $
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
require_once(dirname(__FILE__)."/mb_validateInput.php");
require_once(dirname(__FILE__)."/../classes/class_universal_wfs_factory.php");
require_once(dirname(__FILE__)."/../classes/class_gui.php"); 

echo "file: ".$_REQUEST["xml_file"];
echo "<br>-------------------------------<br>";
$guiList = mb_validateInput($_REQUEST["guiList"]);
$url = mb_validateInput($_REQUEST["xml_file"]);
$myWfsFactory = new UniversalWfsFactory();
if ($_REQUEST["auth_type"] == 'basic' || $_REQUEST["auth_type"] == 'digest') {
	$auth = array();
    	$auth['username'] = $_REQUEST["username"];
    	$auth['password'] = $_REQUEST["password"];
    	$auth['auth_type'] = $_REQUEST["auth_type"];
}
if (isset($auth)) {
	echo "auth set";
	echo $auth['username']."<br>";
	echo $auth['password']."<br>";
	echo $auth['auth_type']."<br>";	
	$myWfs = $myWfsFactory->createFromUrl($url, $auth);  
	//store the authentication information in the object
	$myWfs->auth = $auth;
	if (isset($myWfs->auth)) {
		$e = new mb_exception("ows auth set");
	}
	//TODO
	//$myWfs = $myWfsFactory->createFromUrl($url,$auth);  
	/*$result = $mywms->createObjFromXML($xml, $auth);	
	if ($result['success']) {
		$mywms->writeObjInDB($gui_id, $auth);  
	} else {
		echo $result['message'];
		die();
	}*/
} else {
	$myWfs = $myWfsFactory->createFromUrl($url);      
	if (is_null($myWfs)) {
		echo "WFS could not be uploaded.";
		die;
	}
	/*$result = $mywms->createObjFromXML($xml);
	if ($result['success']) {
		$mywms->writeObjInDB($gui_id);  
	} else {
		echo $result['message'];
		die();
	}*/
}
$e = new mb_exception($myWfs->auth['username']);
$myWfs->insertOrUpdate();
// link WFS to GUIs in $guiList
$guiArray = explode(",", $guiList);
foreach ($guiArray as $appName) {
	$currentApp = new gui($appName);	
	$currentApp->addWfs($myWfs);
}
echo $myWfs;
?>
