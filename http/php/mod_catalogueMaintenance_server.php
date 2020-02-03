<?php
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_json.php");
//classes for monitoring
require_once dirname(__FILE__) ."/../classes/class_administration.php";
require_once dirname(__FILE__) ."/../../tools/mod_monitorCapabilities_defineGetMapBbox.php";
require_once dirname(__FILE__) ."/../classes/class_bbox.php";
require_once(dirname(__FILE__)."/../../lib/class_Monitor.php");

//check for catalogue admin id 
$resultObj['result'] = '';
$resultObj['success'] = false;
$resultObj['message'] = 'no message';

if (DEFINED("CATALOGUE_MAINTENANCE_USER") && CATALOGUE_MAINTENANCE_USER !== "") {
	if (getUserFromSession() == CATALOGUE_MAINTENANCE_USER) {
		$userId = CATALOGUE_MAINTENANCE_USER;
	} else {
		$resultObj['success'] = false;
		$resultObj['message'] = "User not allowed to do maintenance";
		echo json_encode($resultObj);
		die();
	}
} else {
	if (getUserFromSession() !== 1) {
		$resultObj['success'] = false;
		$resultObj['message'] = "User not allowed to do maintenance";
		echo json_encode($resultObj);
		die();
	} else {
		$userId = 1;
	}
}

if (isset($_REQUEST["resourceType"]) & $_REQUEST["resourceType"] != "") {
	$testMatch = $_REQUEST["resourceType"];	
 	if (!($testMatch == 'wms' or $testMatch == 'wfs' or $testMatch == 'dataset' or $testMatch == 'wmc')){ 
		$resultObj['success'] = false;
		$resultObj['message'] = "Parameter <b>resourceType</b> is not valid (wms, wfs, dataset, wmc).<br/>";
		echo json_encode($resultObj);
		die(); 		
 	}
	$resourceType = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["maintenanceFunction"]) & $_REQUEST["maintenanceFunction"] != "") {
	$testMatch = $_REQUEST["maintenanceFunction"];	
 	if (!($testMatch == 'reindex' or $testMatch == 'monitor')){ 
		$resultObj['success'] = false;
		$resultObj['message'] = "Parameter <b>maintenanceFunction</b> is not valid (reindex, monitor).<br/>";
		echo json_encode($resultObj);
		die(); 		
 	}
	$maintenanceFunction = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["resourceIds"]) & $_REQUEST["resourceIds"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["resourceIds"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		$resultObj['success'] = false;
		$resultObj['message'] = "Parameter <b>resourceIds</b> is not valid (integer or cs integer list).";
		echo json_encode($resultObj);
		die(); 		
 	}
	$resourceIdsArray = explode(",",$testMatch);	
	if (count($resourceIdsArray) > 3) {
		$resultObj['success'] = false;
		$resultObj['message'] = "Parameter <b>resourceIds</b> is not allowed to have more than 3 entries (cs integer list).";
		echo json_encode($resultObj);
		die(); 		
	}
	$resourceIds = $testMatch;
	$testMatch = NULL;
}

$allowedFunctions = array(
	'wms' => array('reindex','monitor'),
	'wfs' => array('reindex'),
	'dataset' => array('reindex'),
	'wmc' => array('reindex')
);

$functionThatNeedIdList = array('monitor');

//check for allowedFunction
if (!in_array($maintenanceFunction, $allowedFunctions[$resourceType])) {
	$resultObj['success'] = false;
	$resultObj['message'] = "Maintenance function not allowed for requested resource type.";
	echo json_encode($resultObj);
	die(); 				
}

//check for given id if demanded
if (in_array($maintenanceFunction, $functionThatNeedIdList) && !isset($resourceIds)) {
	$resultObj['success'] = false;
	$resultObj['message'] = "Maintenance function need parameter resourceIds, but this is not given.";
	echo json_encode($resultObj);
	die(); 			
}

$json = new Mapbender_JSON();

$resultObj = array();
switch ($maintenanceFunction) {
	case 'reindex':
		//$result = $touObject->check($ajaxResponse->getParameter("serviceType"),$ajaxResponse->getParameter("serviceId"));
		//$e = new mb_exception($ajaxResponse->getParameter("resourceType"));
		//$e = new mb_exception("resourceType from POST: ".$resourceType);
		$sql = file_get_contents(dirname(__FILE__)."/../../resources/db/materialize_".$resourceType."_view.sql"); 
		//$ajaxResponse->setResult($sql); //1 or 0
		//$ajaxResponse->setMessage("cool");
		//$ajaxResponse->setSuccess(true);
		$beginTime = time();
		$res = db_query($sql);
		$endTime = time();
		$diffTime = $endTime-$beginTime;
		if (!$res) {
			$resultObj['success'] = false;
			$resultObj['message'] = "Index could not be builded :-( ";
			echo $json->encode($resultObj);
			die();
		} else {
			$resultObj['success'] = true;
			$resultObj['message'] = "Time to built index: ".$diffTime;
			echo $json->encode($resultObj);
			die();
		}
		break;
	case 'monitor':
		//$e = new mb_exception("found resource ids: ".$resourceIds);
		$admin = new administration();
		$timeLimit = 5;
		$time_array = array();
		$time_array[$userId] = strval(time());
		//sleep(1);
		$time = $time_array[$userId];
		
		$resultObj = monitorWMSResources(explode(',',$resourceIds), $userId, $admin, $time, $timeLimit);
		
		/*$serviceType = $ajaxResponse->getParameter("serviceType");
		$serviceId = $ajaxResponse->getParameter("serviceId");
		$result = $touObject->set($ajaxResponse->getParameter("serviceType"),$ajaxResponse->getParameter("serviceId"));	
		$ajaxResponse->setResult($result['setTou']); //1 or 0
		$ajaxResponse->setMessage(_mb($result['message']));
		$ajaxResponse->setSuccess(true);*/
		//$resultObj['success'] = true;
		//$resultObj['message'] = "found resource ids: ".$resourceIds;
		echo $json->encode($resultObj);
		break;
	//Invalid command
	default:
		$resultObj['success'] = false;
		$resultObj['message'] = "No maintenanceFunction given in REQUEST!";
		echo $json->encode($resultObj);
		die();
		//$ajaxResponse->setMessage(_mb("No method specified."));
		//$ajaxResponse->setSuccess(false);		
}

function getUserFromSession()
{
    if (Mapbender::session()->get('mb_user_id')) {
        if ((integer) Mapbender::session()->get('mb_user_id') >= 0) {
            $foundUserId = (integer) Mapbender::session()->get('mb_user_id');
        } else {
            $foundUserId = false;
        }
    } else {
        $foundUserId = false;
    }
    return $foundUserId;
}

function getTagOutOfXML($reportFile,$tagName) {
	$xml=simplexml_load_file($reportFile);
	$result=(string)$xml->wms->$tagName;
	return $result;
}

function monitorWMSResources($wmsIdList, $userId, $admin, $time, $timeLimit) {
	$lb = "\n";
	$br = "\n";
	$resultObj['success'] = false;
	$resultObj['message'] = "Monitoring for wms resources could not be done!";
	for ($k=0; $k<count($wmsIdList); $k++) {
		//get relevant data out of registry
		$sql = "SELECT wms_upload_url, wms_getcapabilities_doc, " . 
			"wms_version, wms_getcapabilities, wms_getmap FROM wms " . 
			"WHERE wms_id = $1";
		//$e = new mb_exception('wms_id: '.$wmsIdList[0]);
		$v = array($wmsIdList[$k]);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$someArray = db_fetch_array($res);
		$url = $someArray['wms_upload_url'];
		$capDoc = $someArray['wms_getcapabilities_doc'];
		$version = $someArray['wms_version'];
		$capabilities = $someArray['wms_getcapabilities'];
		$getmap = $someArray['wms_getmap'];
		$getMapUrl = getMapRequest($wmsIdList[$k], $version, $getmap);
		// for the case when there is no upload url - however - we need the 
		// url to the capabilities file
   		if (!$url || $url == "") {
			$capabilities=$admin->checkURL($capabilities);
			if ($version == "1.0.0" ) {
				$url = $capabilities . "REQUEST=capabilities&WMTVER=1.0.0";
			}
			else {
				$url = $capabilities . "REQUEST=GetCapabilities&" . 
					"SERVICE=WMS&VERSION=" . $version;
			}
   		}
		//$url is the url to the service which should be monitored in this cycle
		//initialize monitoriung in db (set status=-2)
		//echo "initialize monitoring for user: " . $userId . 
		//	" WMS: " . $wmsIdList[$k] . $br;
		//$e = new mb_notice("mod_monitorCapabilities_main.php: wms: ".$wmsIdList[$k]);
		$sql = "INSERT INTO mb_monitor (upload_id, fkey_wms_id, " . 
				"status, status_comment, timestamp_begin, timestamp_end, " . 
				"upload_url, updated)";
		$sql .= "VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
		$v = array(
			$time,
			$wmsIdList[$k],
			"-2",
			"Monitoring is still in progress...", 
			time(),
			"0",
			$url,
			"0"
		);
		$t = array('s', 'i', 's', 's', 's', 's', 's', 's');
		$res = db_prep_query($sql,$v,$t);
		// Decode orig capabilities out of db cause they are converted before 
		// saving them while upload
		//$capDoc=$admin->char_decode($capDoc);

		// do the next to exchange the update before by another behavior! - 
		// look in class_monitor.php !
		$currentFilename = "wms_monitor_report_" . $time . "_" . 
			$wmsIdList[$k] . "_" . $userId . ".xml";
		//$e = new mb_exception("filename: ".TMPDIR."/".$currentFilename);
 		$report = fopen(TMPDIR."/".$currentFilename,"a");
		$lb = chr(13).chr(10);
		fwrite($report,"<monitorreport>".$lb);
		fwrite($report,"<wms>".$lb);
		fwrite($report,"<wms_id>".$wmsIdList[$k]."</wms_id>".$lb);
		fwrite($report,"<upload_id>".$time."</upload_id>".$lb);
		fwrite($report,"<getcapbegin></getcapbegin>".$lb);
		fwrite($report,"<getcapurl>".urlencode($url)."</getcapurl>".$lb);
		fwrite($report,"<getcapdoclocal>".urlencode($capDoc)."</getcapdoclocal>".$lb);
		fwrite($report,"<getcapdocremote></getcapdocremote>".$lb);
		fwrite($report,"<getcapdiff></getcapdiff>".$lb);
		fwrite($report,"<getcapend></getcapend>".$lb);
		fwrite($report,"<getcapduration></getcapduration>".$lb);
		fwrite($report,"<getmapurl>".urlencode($getMapUrl)."</getmapurl>".$lb);
		fwrite($report,"<status>-2</status>".$lb);
		fwrite($report,"<image></image>".$lb);
		fwrite($report,"<comment>Monitoring in progress...</comment>".$lb);
		fwrite($report,"<timeend></timeend>".$lb);
		fwrite($report,"</wms>".$lb);
		fwrite($report,"</monitorreport>".$lb);
		fclose($report);
		$monitor = new Monitor($currentFilename, 0, TMPDIR."/");
		$monitor->updateInXMLReport();
	}
	set_time_limit($timeLimit);
	// wait until all monitoring processes are finished
	//echo "please wait " . $timeLimit . " seconds for the monitoring to finish...$br";
	sleep($timeLimit);
	$problemOWS = array();//define array with id's of problematic wms
	$commentProblemOWS = array();
	//get the old upload_id from the monitoring to identify it in the database
	//$time = $time_array[$userId];	
	//read sequencialy all user owned xml files from tmp and update the 
	// records in the database 
	for ($k = 0; $k < count($wmsIdList); $k++) {
		$monitorFile = TMPDIR."/"."wms_monitor_report_" . $time . "_" . 
			$wmsIdList[$k] . "_".$userId.".xml";
		$e = new mb_exception("mod_monitorCapabilities_main.php: look for following file: ".$monitorFile);
		$status = getTagOutOfXML($monitorFile,"status");
		$status_comment = getTagOutOfXML($monitorFile,"comment");
		$cap_diff = getTagOutOfXML($monitorFile,"getcapdiff");
		$image = getTagOutOfXML($monitorFile,"image");
		$map_url = rawurldecode(getTagOutOfXML($monitorFile,"getmapurl"));
		$timestamp_begin = getTagOutOfXML($monitorFile,"getcapbegin");
		$timestamp_end = getTagOutOfXML($monitorFile,"getcapend");
		$sql = "UPDATE mb_monitor SET updated = $1, status = $2, " . 
			"image = $3, status_comment = $4, timestamp_end = $5, " . 
			"map_url = $6 , timestamp_begin = $7, cap_diff = $8 " . 
			"WHERE upload_id = $9 AND fkey_wms_id=$10 ";
		// check if status = -2 return new comment and status -1, 
		// push into problematic array
		if ($status == '-1' or $status == '-2') {
			$status_comment = "Monitoring process timed out.";
			$status = '-1';
			array_push($problemOWS,$wmsIdList[$k]);
			array_push($commentProblemOWS,$status_comment);
		} 
		$v = array(
			'0', 
			intval($status), 
			intval($image), 
			$status_comment, 
			(string)intval($timestamp_end), 
			$map_url, 
			(string)intval($timestamp_begin), 
			$cap_diff,
			(string)$time, 
			$wmsIdList[$k]
		);
		$t = array('s', 'i', 'i', 's', 's', 's', 's', 's','s','s');
		$res = db_prep_query($sql,$v,$t);
	}
	$resultObj['success'] = true;
	$resultObj['message'] = "Monitoring done for WMS: ".implode(',',$wmsIdList);
	return $resultObj;
}

?>
