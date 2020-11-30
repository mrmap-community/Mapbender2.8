<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_group.php");
require_once(dirname(__FILE__)."/../classes/class_Uuid.php");

$uuid = false;
$id = false;
$outputFormat='iso19139';

if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["id"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>id</b> is not valid (integer or cs integer list).<br/>'; 
		die(); 		
 	}
	$id = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["uuid"]) & $_REQUEST["uuid"] != "") {
	if (Uuid::isuuid($_REQUEST["uuid"])) {
		$uuid = $_REQUEST["uuid"];
	} else {
		echo 'Parameter <b>uuid</b> is not a valid mapbender uuid.<br/>'; 
		die();
	}
}

if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["outputFormat"];
	if (!($testMatch == 'iso19139' or $testMatch == 'ckan')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>outputFormat</b> is not valid (iso19139,ckan).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}

if ($uuid !== false) {
	 $group = new Group($uuid);
} else {
	if ($id !== false) {
		$group = new Group($id);
	} else {
		echo 'Neither id nor uuid for requesting an organization was given!<br/>'; 
		die();
	}
}

switch ($outputFormat) {
	case "ckan":
		header("Content-Type: application/json");
		echo $group->export('ckan');
		break;
	case "iso19139":
		header("Content-type: application/xhtml+xml; charset=UTF-8");
		echo $group->export('iso19139');
		break;
}

?>
