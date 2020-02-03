<?php
// Display errors for demo
//for later development php 5.6+
//https://github.com/GSA/ckan-php-client
//@ini_set('error_reporting', E_ALL);
//@ini_set('display_errors', 'stdout');	
// Include class_ckanApi.php
require_once(dirname(__FILE__).'/../classes/class_connector.php');
require_once(dirname(__FILE__).'/../classes/class_group.php');
require_once(dirname(__FILE__).'/../classes/class_syncCkan.php');
require_once(dirname(__FILE__) . '/../php/mod_getDownloadOptions.php');
require_once(dirname(__FILE__).'/../../conf/ckan.conf');

//TODO: Problem - datestamp for ckan_package - wrong????? Bug in 2.5.3??? Will the index not be updated???
$registratingDepartments = false;
$userId = null;
$outputFormat = "json";
$compareTimestamps = false;
$listAllMetadataInJson = true;
//initiate resultObject to give back as json
$resultObject->success = false;
//$operation = "";
//fix
if (isset($_REQUEST["syncDepartment"]) && $_REQUEST["syncDepartment"] !== "" && $_REQUEST["syncDepartment"] !== null) {
        $testMatch = $_REQUEST["syncDepartment"];
        //$pattern = '/^[0-9]*$/';  	
	$pattern = '/^[\d,]*$/';
        if (!preg_match($pattern,$testMatch)){
                $resultObject->error->message = 'Parameter syncDepartment is not valid (integer or csv integer list).';
                echo json_encode($resultObject);
		die();	
        }
        $syncDepartment = $testMatch;
        $testMatch = NULL;
} else {
	//try to read csv list from localhost webservice
	if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') { 
		$mapbenderUrl = MAPBENDER_PATH;
	} else {
		$mapbenderUrl = "http://www.geoportal.rlp.de/mapbender";
	}
	$orgaConnector = new Connector($mapbenderUrl.'/php/mod_showOpenDataOrganizations.php');
	$orgaList = json_decode($orgaConnector->file);
	$orgaArray = array();
	foreach ($orgaList as $orgaEntry) {
		if (isset($orgaEntry->id)) {
			$orgaArray[] = $orgaEntry->serialId;
		}	
	}
	//$coupledOrgaList
        $syncDepartment = implode(',', $orgaArray);
}

if (isset($_REQUEST["userId"]) & $_REQUEST["userId"] != "") {
        $testMatch = $_REQUEST["userId"];
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                $resultObject->error->message = 'Parameter userId is not valid (integer).';
                echo json_encode($resultObject);
		die();
        }
	if ($testMatch !== Mapbender::session()->get("mb_user_id")) {
		$resultObject->error->message = 'Parameter userId is not equal to the userId from session information - maybe there is no current session!';
		echo json_encode($resultObject);
		die();
	}
        $userId = $testMatch;
        $testMatch = NULL;
} else { 
	$userId = Mapbender::session()->get("mb_user_id");
  	if ($userId == false) {
	  	$userId = PUBLIC_USER;
    	}
}

if ($userId !== "1") {
	$resultObject->error->message = 'Your are not the root user!';
	echo json_encode($resultObject);
	die();
}

//$e = new mb_exception($syncDepartment);
//Test for csv or single value
if (strpos($syncDepartment, ',') === false) {
	$syncDepartmentArray = array($syncDepartment);
	//$e = new mb_exception($syncDepartmentArray[0]);
} else {
	$syncDepartmentArray = explode(',', $syncDepartment);
}
//$e = new mb_exception(count($syncDepartmentArray));
//result
$syncResultArray = array();

foreach($syncDepartmentArray as $syncDepartmentId) {
	$syncDepartmentId = (integer)$syncDepartmentId;
	//$syncDepartment = 35;
	//echo "test";
	//get user which may sync the requested department
	$sql = "SELECT fkey_mb_user_id FROM mb_user_mb_group WHERE fkey_mb_group_id = $1 AND mb_user_mb_group_type IN (2,3) ORDER BY mb_user_mb_group_type DESC LIMIT 1";
	$v = array($syncDepartmentId);
	$t = array('i');
	$res = db_prep_query($sql, $v, $t);
	//$e = new mb_exception("sync department: ".$syncDepartmentId);
	//$e = new mb_exception("res: ".json_encode($res));
	if (!$res || is_null($res) || empty($res)) {
		$resultObject->error->message = 'No user for publishing department data found!';
		echo json_encode($resultObject);
		die();
	} else {
		while($row = db_fetch_array($res)){
			$syncUserId = $row['fkey_mb_user_id'];
			$e = new mb_exception("syncuser:  ".$syncUserId);
			//***************************************************************
			$syncCkanClass = new SyncCkan();
			$syncCkanClass->mapbenderUserId = $syncUserId;
			$syncCkanClass->compareTimestamps = $compareTimestamps;
			$departmentsArray = $syncCkanClass->getMapbenderOrganizations();
			//second parameter is listAllMetadataInJson ( = true) - it is needed if we want to sync afterwards. The syncList includes all necessary information about one organization
			$syncListJson = $syncCkanClass->getSyncListJson($departmentsArray, true);
			//$syncDepartmentId = (string)$syncDepartmentId;
			$syncCkanClass->syncOrgaId = $syncDepartmentId;
			$syncList = json_decode($syncListJson);
			if ($syncList->success = true) {
    				foreach ($syncList->result->geoportal_organization as $orga) {
					/*$e = new mb_exception($orga->id);
					$e = new mb_exception(gettype($orga->id));
					$e = new mb_exception($syncDepartmentId);
					$e = new mb_exception(gettype($syncDepartmentId));*/
        				//try to sync single orga - the class has already set the syncOrgaId if wished!
					if ($syncDepartmentId == $orga->id) {
            					//overwrite result with result from sync process
            					//$syncList = json_decode($syncCkanClass->syncSingleOrga(json_encode($orga)));
	    					$syncList = json_decode($syncCkanClass->syncSingleDataSource(json_encode($orga), "mapbender", true));
					}
    				}
			}
			//add new syncListJson
			$syncResultArray[] = $syncList;
			//$syncListJson = json_encode($syncList);
			//***************************************************************
		}
	}
}
$syncListJson = json_encode($syncResultArray);
header('Content-type:application/json;charset=utf-8');
echo $syncListJson;
?>
