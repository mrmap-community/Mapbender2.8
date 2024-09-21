<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";  
require_once dirname(__FILE__) . "/../classes/class_administration.php"; 
require_once dirname(__FILE__) . "/../classes/class_Uuid.php"; 

//parse id from application (metadata_id)
if (isset($_REQUEST["dcat_orga_id"]) & $_REQUEST["dcat_orga_id"] != "") {
	//validate to integer
	$testMatch = $_REQUEST["dcat_orga_id"];
    $uuid = new Uuid($testMatch);
	$isUuid = $uuid->isValid();
 	if (!$isUuid){ 
		//echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>dcat_orga_id</b> is not valid uuid.<br/>'; 
		die(); 		
 	}
	$ckan_orga_id = $testMatch;
	$testMatch = NULL;
} else {
    echo 'Missing parameter dcat_orga_id.<br/>'; 
	die(); 	
}

//select application from mb_metadata by id
$sql = "SELECT mb_group_id FROM mb_group WHERE mb_group_ckan_uuid = $1";
$v = array($ckan_orga_id);
$t = array('s');
$res = db_prep_query($sql, $v, $t);
if ($res) {
    $row = db_fetch_array($res);
    header("Location: "."https://www.geoportal.rlp.de/search/external/?start=true&registratingDepartments=" . $row['mb_group_id']);
} else {
    echo "No information for requested organization found!";
}
?>