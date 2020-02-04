<?php
require_once(dirname(__FILE__)."/../core/system.php");
require_once(dirname(__FILE__)."/../http/classes/class_wms.php"); 

$sql = <<<SQL
SELECT scheduler_id, wms_id, wms_title, wms_auth_type, wms_username, wms_password, to_timestamp(wms_timestamp)::date AS last_change, last_status, fkey_upload_id, wms_upload_url, 
scheduler_interval,scheduler_mail,wms_owner,scheduler_publish,scheduler_searchable,scheduler_overwrite, scheduler_overwrite_categories,
scheduler_status FROM (SELECT scheduler_id, wms_id, fkey_wms_id, wms_title, wms_timestamp, wms_owner, wms_auth_type, wms_username, wms_password,
scheduler_interval,scheduler_mail,scheduler_publish,scheduler_searchable,scheduler_overwrite,scheduler_overwrite_categories,
scheduler_status,wms_upload_url FROM scheduler 
INNER JOIN wms ON scheduler.fkey_wms_id=wms.wms_id ) AS test 
LEFT OUTER JOIN mb_wms_availability ON test.fkey_wms_id = mb_wms_availability.fkey_wms_id;
SQL;
$res = db_query($sql);
//$resultObj = array();
//array containing all wms which should be updated
$wmsToUpdate = array();
echo "Scheduler for WMS start work...\n";
while ($row = db_fetch_array($res)) {
	$resultObj = array(
		"scheduler_id" 	=> $row['scheduler_id'],
		"wms_id"  =>  $row['wms_id'],
		"wms_title"  =>  $row['wms_title'],
	    	"last_change"  =>  $row['last_change'],
	   	"last_status"  =>  $row['last_status'],
		"wms_upload_url"  =>  $row['wms_upload_url'],
	   	"fkey_upload_id"  =>  $row['fkey_upload_id'],
		"wms_owner"  =>  $row['wms_owner'],
	   	"scheduler_interval"  =>  $row['scheduler_interval'],
	   	"scheduler_mail"  =>  $row['scheduler_mail'],
	   	"scheduler_publish"  =>  $row['scheduler_publish'],
	   	"scheduler_searchable"  =>  $row['scheduler_searchable'],
		"scheduler_overwrite"  =>  $row['scheduler_overwrite'],
		"scheduler_overwrite_categories"  =>  $row['scheduler_overwrite_categories'],
	   	"scheduler_status"  =>  $row['scheduler_status'],
	   	"wms_auth_type"  =>  $row['wms_auth_type'],
	   	"wms_username"  =>  $row['wms_username'],
	   	"wms_password"  =>  $row['wms_password']
	);
	
	//echo json_encode($resultObj)."\n";

	if($row['scheduler_interval'] == '1 mon') {
	    $row['scheduler_interval'] = "1 month";
	}
    	//check wms timestamp , schedule interval with current date for update
    	$currentDate = date('Y-m-d',time());
    	$schedulerDateTimestamp = date("Y-m-d",strtotime(date("Y-m-d", strtotime($row['last_change'])) . $row['scheduler_interval']));
    	//last monitoring date
    	$lastMonitorDate = date("Y-m-d",$row['fkey_upload_id']);
    	$schedulerDateMonitoring = date("Y-m-d",strtotime(date("Y-m-d", $row['fkey_upload_id']) . $row['scheduler_interval']));
    
    	//if calculated $schedulerDateTimestamp is in the past 
    	//or last_status value of monitoring does not have the value 1 which means wms has changed!
    	if($currentDate >= $schedulerDateTimestamp) {
        	if ($row['last_status'] == 1) {
			//nothing to change!!!!
            		if($currentDate >= $schedulerDateMonitoring) {
                		array_push($wmsToUpdate, $resultObj);
            		}
        	} else {
            		array_push($wmsToUpdate, $resultObj);
        	}
    	} else {
		//if service is not up to date
        	if ($row['last_status'] == 0 || $row['last_status'] == -1 || $row['last_status'] == -2) {
            		array_push($wmsToUpdate, $resultObj);
        	}
    	}
}
//for debugging purpose:
/*for ($i=0; $i<count($wmsToUpdate); $i++) {
	echo "wms_id: ".$wmsToUpdate[$i]['wms_id']."\n";
	echo "wms_owner: ".$wmsToUpdate[$i]['wms_owner']."\n";
}
die();*/
for ($i=0; $i<count($wmsToUpdate); $i++) {
    	//create new wms object
	echo "\nTry to call wms ".$wmsToUpdate[$i]['wms_id']."\n";
	//echo "auth_type:  ".$wmsToUpdate[$i]['wms_auth_type']."\n";
	//echo "auth_username:  ".$wmsToUpdate[$i]['wms_username']."\n";
	//echo "auth_password:  ".$wmsToUpdate[$i]['wms_password']."\n";
    	$updateWms = new wms();
        $updateWms->harvestCoupledDatasetMetadata = true;
	//check for authentication!
	$auth = null;
	try {
		if ($wmsToUpdate[$i]['wms_auth_type'] == "basic" || $wmsToUpdate[$i]['wms_auth_type'] == "digest"){
			//echo "call with auth array!"."\n";
			$auth = array();
			$auth['auth_type'] = $wmsToUpdate[$i]['wms_auth_type'];
			$auth['username'] = $wmsToUpdate[$i]['wms_username'];
			$auth['password'] = $wmsToUpdate[$i]['wms_password'];
			$createObjFromXml = $updateWms->createObjFromXML($wmsToUpdate[$i]['wms_upload_url'], $auth);
		} else {
    			$createObjFromXml = $updateWms->createObjFromXML($wmsToUpdate[$i]['wms_upload_url']);
		}
	}
	catch(Exception $e) {
  		throw $e;
	}
	$updateWms->owner = $wmsToUpdate[$i]['wms_owner'];
    	if(!$createObjFromXml['success']) {
        	//$errorMsg = "Error while creating object from GetCapabilities XML";
		$errorMsg = $createObjFromXml['message'];
		echo "Error while creating object from GetCapabilities XML";
		continue;
    	}
    	//check scheduler_searchable attribute for layer searchable in class_wms.php
	try {
    		$updateWms->optimizeWMS($wmsToUpdate[$i]['scheduler_searchable']);
    	}
	catch(Exception $e) {
  		throw $e;
	}
    	//check overwrite attribute
    	if (!$wmsToUpdate[$i]['scheduler_overwrite']) {
		echo "overwrite md = false\n";
		$updateWms->overwrite=false;
	}
	//check overwrite categories attribute
    	if (!$wmsToUpdate[$i]['scheduler_overwrite_categories']) {
		echo "overwrite categories = false\n";
	} else {
		echo "overwrite categories = true\n";
		$updateWms->overwriteCategories=true;
	}
	//check publish attribute for geoRss and twitter attribute in class_wms.php
    	if ($wmsToUpdate[$i]['scheduler_publish'] == 1) {
        	require_once dirname(__FILE__) . "/../http/classes/class_twitter.php";
		$updateWms->twitterNews=true;
		$updateWms->setGeoRss=true;
		echo "publish = true\n";
	}
	else {
	    	$updateWms->twitterNews=false;
		$updateWms->setGeoRss=false;
		echo "publish = false\n";
	}
	echo "Start update of ".$wmsToUpdate[$i]['wms_id']."\n";
	try {
   		$updateObjInDb = $updateWms->updateObjInDB($wmsToUpdate[$i]['wms_id']);
	}
	catch(Exception $e) {
  		throw $e;
	}
    	if(!$updateObjInDb) {
    	    	$errorMsg = "Error while updating wms object in database";
		echo "Error while updating wms object in database";
	    	continue;
    	}
	
	if($wmsToUpdate[$i]['scheduler_mail']) {
	    	$admin = new administration();
		//get all users which have the wms integrated in their guis!
		$ownerIds = $admin->getOwnerByWms($wmsToUpdate[$i]['wms_id']);
		if ($ownerIds && count($ownerIds) > 0) {
			$ownerMailAddresses = array();
			$j=0;
			for ($k=0; $k<count($ownerIds); $k++) {
				$adrTmp = $admin->getEmailByUserId($ownerIds[$k]);
				if (!in_array($adrTmp, $ownerMailAddresses) && $adrTmp) {
					$ownerMailAddresses[$j] = $adrTmp;
					$j++;
				} 
			}
			$adrRoot = $admin->getEmailByUserId("1");	
			$from = $adrRoot;
			if($from != "") {
    				$body = "WMS '" . $admin->getWmsTitleByWmsId($wmsToUpdate[$i]['wms_id']) . "' has been updated by the scheduler update. \n\nYou may want to check the changes as you are an owner of this WMS.";
    				$error_msg = "";
    				for ($m=0; $m<count($ownerMailAddresses); $m++) {
					echo $ownerMailAddresses[$m]."\n";
    					if (!$admin->sendEmail($from, $from, $ownerMailAddresses[$m], $ownerMailAddresses[$m], "[Mapbender Update Scheduler] One of your WMS has been updated", $body, $error)) {
    						if ($error){
    							$error_msg .= $error . " ";
    						}
    					}
    				}
			}
		}
	}
	
	if($errorMsg == "") {
	    $status = 1;
	}
	else {
	    $status = -1;
	}
	
    $sql = <<<SQL
UPDATE scheduler SET scheduler_status = $1, scheduler_status_error_message = $2 WHERE fkey_wms_id = $3;
SQL;
	$v = array($status, $errorMsg, $wmsToUpdate[$i]['wms_id']);
	$t = array('i','s','i');
	try {
		$res = db_prep_query($sql,$v,$t);
	}
	catch (Exception $e){
		echo "ERROR";
	}
}
?>
