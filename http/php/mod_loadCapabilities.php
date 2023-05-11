<?php
# $Id: mod_loadCapabilities.php 9620 2016-10-21 10:37:57Z armin11 $
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

$e_id = "loadWMS";
require_once dirname(__FILE__) . "/../php/mb_validatePermission.php";
/*
 * @security_patch irv done
 */
//security_patch_log(__FILE__,__LINE__);
$guiList = $_POST["guiList"];
$xml_file = $_POST["xml_file"];
?>
<!DOCTYPE html>
<html>
<head>
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
?>
<title>Load WMS</title>
<?php
include '../include/dyn_css.php';
?>
<link rel="stylesheet" href="../extensions/bootstrap-3.3.6-dist/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="../css/loading.css" type="text/css">
<style type="text/css">
  	<!--
  	body{
      background-color: #ffffff;
  		font-family: Arial, Helvetica, sans-serif;
  		font-size : 14px;
  		color: #303030
  	}
  	.list_guis{
  		font-family: Arial, Helvetica, sans-serif;
  		font-size : 12px;
  		color: #808080;
  	}
  	a:link{
  		font-family: Arial, Helvetica, sans-serif;
  		font-size : 12px;
  		text-decoration : none;
  		color: #808080;
  	}
  	a:visited {
  		font-family: Arial, Helvetica, sans-serif;
  		text-decoration : none;
  		color: #808080;
  		font-size : 12px;
  	}
  	a:active {
  		font-family: Arial, Helvetica, sans-serif;
  		text-decoration : none;
  		color: #808080;
  		font-size : 12px;
  	}
  	-->
	#optionsbox,#newCapabilitiesBox {border: 1px solid #ccc;padding: 15px;border-radius: 4px;background-color: #efefef;margin-top: 30px;margin-bottom: 30px;}
	#authbox {border: 1px solid #ccc;max-width: 300px;padding: 15px;border-radius: 4px;background-color: #efefef;}
	#authbox > .radio {margin: unset;}
</style>
<script language="JavaScript">

function showLoading() {
	document.getElementById("loadingOverlay").style.display="block";
}
function validate(wert){
	if(wert == 'guiList'){
		var listIndex = document.form1.guiList.selectedIndex;
		if(listIndex<0){
			alert("Please select a GUI.");
			return false;
		}
		else{
			var gui_id=document.form1.guiList.options[listIndex].value;
			document.form1.action = '../php/mod_loadwms.php?<?php echo $urlParameters ?>';
			document.form1.submit();
		}
	}
}
function toggleAuthDivVis() { 
	//alert(getRadioValue(document.form1.auth_type)); 
 	if (getRadioValue(document.form1.auth_type) != 'none') { 
 		document.getElementById("imrAuthDiv").style.display = "block"; 
 	} else { 
 		document.getElementById("imrAuthDiv").style.display = "none"; 
 	} 
 } 
 		 
 function getRadioValue(rObj) { 
 	for (var i=0; i<rObj.length; i++) if (rObj[i].checked) return rObj[i].value; 
 		return false; 
 	}

</script>
</head>
<body>
<div id="loadingOverlay" role="alert" aria-busy="true" title="loading..." style="display: none;">
  <div class="loading">
    <div class="bounce1"></div>
    <div class="bounce2"></div>
    <div class="bounce3"></div>
    <div class="bounce4"></div>
  </div>
</div>
<div class="container" style="padding-top:15px;padding-bottom:15px;">
<?php

require_once(dirname(__FILE__)."/../classes/class_administration.php");
$admin = new administration();
$ownguis = $admin->getGuisByOwner(Mapbender::session()->get("mb_user_id"),true);

echo "<form name='form1' action='" . $self ."' method='post'>";
if (count($ownguis)>0){
	$v = array();
	$t = array();
	$sql = "SELECT * FROM gui WHERE gui_id IN ("; 
	for($i=0; $i<count($ownguis); $i++){
		if($i>0){ $sql .= ",";}
		$sql .= "$".($i+1);
		array_push($v,$ownguis[$i]);
		array_push($t,'s');
	}
	$sql .= ") ORDER BY gui_name";
	$res = db_prep_query($sql,$v,$t);
	echo"<div id='optionsbox' style='margin-top:0'><label for='guiList'>Wählen Sie Ihren Container aus</label><select class='form-control' name='guiList' onchange='submit()'>";
	echo "<option value=''></option>";
	while($row = db_fetch_array($res)){
		echo "<option value='".$row["gui_id"]."' ";
		if($guiList && $guiList == $row["gui_name"]){
			echo "selected";
		}
		echo ">".$row["gui_name"]."</option>";
	} 
	$arrayGUIs = Mapbender::session()->get("mb_user_id");
	echo count($arrayGUIs);
	echo "</select>";
	
	if(isset($guiList) && $guiList!=""){
		$sql = "SELECT DISTINCT wms.wms_title,gui_wms_position from gui_wms JOIN ";
		$sql .= "gui on gui_wms.fkey_gui_id = gui.gui_id JOIN wms ON gui_wms.fkey_wms_id = wms.wms_id ";
		$sql .= "and gui_wms.fkey_gui_id = gui.gui_id where gui.gui_name = $1 order by gui_wms_position";
		$v = array($guiList);
		$t = array('s');
		$res = db_prep_query($sql,$v,$t);
		$count=0;
		echo"<label style='margin-top: 15px;' for='wmsList'>Bereits enthaltene WMS</label><select class='form-control' name='wmsList' size='5' readonly>";
	
		while($row = db_fetch_array($res)){
			if ($row["wms_title"]!=""){
				echo "<option value='' ";
				echo ">".$row["wms_title"]."</option>";
			}
			$count++;
		}
	    echo "</select></div>";
	}
	else{
		echo"<label style='margin-top: 15px;' for='wmsList'>Bereits enthaltene WMS</label><select class='form-control' name='wmsList' on Click='submit()'>";
		echo "</select></div>";
	}
	#echo "Load WMS capabilities URL:<br>"

	echo "<div id='newCapabilitiesBox' class='' ><label for='xml_file'>Neue URL:</label>"; 

	if (isset($xml_file)){
		echo"<input class='form-control' type='text' name='xml_file' value='".$xml_file."' placeholder='https://'>";
		echo "<span id='helpBlock' class='help-block bg-danger' style='padding:10px;margin-top:25px;word-wrap:break-word;border-radius:4px;'>Die URL muss ein valides WMS Capabilities Dokument der Version 1.1.1 liefern. In der Regel sollte folgendes in Ihrer URL enthalten sein:<p style='margin:10px 0 0 0;font-weight:bold;'>REQUEST=GetCapabilities&SERVICE=WMS&VERSION=1.1.1</p></span></div>";
	}else{
		echo"<input class='form-control' type='text' name='xml_file' value='' placeholder='https://'>";
		echo "<span id='helpBlock' class='help-block bg-danger' style='padding:10px;margin-top:25px;word-wrap:break-word;border-radius:4px;'>Die URL muss ein valides WMS Capabilities Dokument der Version 1.1.1 liefern. In der Regel sollte folgendes in Ihrer URL enthalten sein:<p style='margin:10px 0 0 0;font-weight:bold;'>REQUEST=GetCapabilities&SERVICE=WMS&VERSION=1.1.1</p></span></div>";
	}
	//show fields for authentication - only possible if curl is used as connector!
	if (CONNECTION == 'curl') {
		echo"<div id='authbox' class=''><p><b>HTTP Authentication</b></p>";
		echo"<div class='radio'><label><input type='radio' name='auth_type' checked='checked' value='none' onclick='toggleAuthDivVis();' >None</label></div>";
		echo"<div class='radio'><label><input type='radio' name='auth_type' value='digest' onclick='toggleAuthDivVis();' >Digest</label></div>";
    		echo"<div class='radio'><label><input type='radio' name='auth_type' value='basic' onclick='toggleAuthDivVis();' >Basic</label></div>";

		echo "<div id='imrAuthDiv' style='display: none;'>";
        echo "<label for='username'>Benutzername</label><input class='form-control' type='text' name='username' id='username' value=''>";
        echo "<label for='password'>Passwort</label><input class='form-control' type='text' name='password' id='password' value=''>";
        echo "</div>";

	}

	echo "</div><div id='optionsbox' class=''><p><b>Optionen</b></p>";
	echo "<div class='checkbox'><label><input type='checkbox' name='harvest_dataset_metadata' id='harvest_dataset_metadata' checked='checked'>Originär verknüpfte Metadaten harvesten</label></div>";

	if (defined("TWITTER_NEWS") && TWITTER_NEWS == true) {
		echo"<div class='checkbox' style='display:none'><label><input type='checkbox' name='twitter_news' checked='checked'>Publish via Twitter</label></div>";
	}
	if (defined("GEO_RSS_FILE") &&  GEO_RSS_FILE != "") {
		echo"<div class='checkbox' style='display:none'><label><input type='checkbox' name='rss_news' checked='checked'>Publish via RSS</label></div>";
	}
	echo"</div><input class='btn btn-primary' type='button' name='loadCap' value='Load' onClick='validate(\"guiList\");showLoading();'>";	
	echo "</form>";
}
else{
	echo "There are no guis available for this user. Please create a gui first.";
}
?>
</div>
</body>
</html>
