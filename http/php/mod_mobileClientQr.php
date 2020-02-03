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
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../extensions/phpqrcode/phpqrcode.php";
$pathExtension = "";
$return = "default";
if (isset($_REQUEST["pathExtension"]) & $_REQUEST["pathExtension"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["pathExtension"];	
 	if (!($testMatch == '../../mapbender/tmp/' or $testMatch == '../mapbender/tmp/')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>pathExtension</b> is not valid ("../../mapbender/tmp/","../mapbender/tmp/").<br/>'; 
		die(); 		
 	}
	$pathExtension = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["return"]) & $_REQUEST["return"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["return"];	
 	if (!($testMatch == 'applicationlink' or $testMatch == 'imagelink')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>return</b> is not valid ("applicationlink","imagelink").<br/>'; 
		die(); 		
 	}
	$return = $testMatch;
	$testMatch = NULL;
}

$filename = "qr_mobileclient.png";
//generate qr on the fly in tmp folder
//link to invoke wmc per get api if wrapper path isset
if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != "") {
    $invokeLink = MAPBENDER_PATH."/extensions/mobilemap2/index.html";
    QRcode::png($invokeLink,TMPDIR."/".$filename);
    $htmlElement = "<a href='".$invokeLink."' target='_blank'><img src='$pathExtension".TMPDIR."/".$filename."'></a>";
} else {
    echo "MAPBENDER_PATH not defined in mapbender.conf - please define it to activate linkage and qr code!";
    die();
}

switch ($return) {
    case "imagelink":
        echo $pathExtension.TMPDIR."/".$filename;
        break;
    case "applicationlink":
        echo $invokeLink;
        break;
    case "default":
        echo $htmlElement;
        break;
}

//echo html image tag with link to mobile client
//echo $htmlElement;
?>
