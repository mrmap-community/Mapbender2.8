<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_user.php";
require_once dirname(__FILE__) . "/../classes/class_wms.php";
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";

$ajaxResponse = new AjaxResponse($_POST);

function abort ($message) {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die();
};

function getWmsScheduler ($wmsSchedulerId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$e = new mb_exception("plugins/mb_wms_scheduler_server.php: mb_user_id: ".$user);
	$wmsSchedulerIdArray = $user->getOwnedWmsScheduler();

	if (!is_null($wmsSchedulerId) && !in_array($wmsSchedulerId, $wmsSchedulerIdArray)) {
		abort(_mb("You are not allowed to access this schedule settings."));
	}
	return $wmsSchedulerIdArray;
}

//validate user which sends ajax

$user = new User(Mapbender::session()->get("mb_user_id"));

switch ($ajaxResponse->getMethod()) {
	case "getWmsScheduler" :
		$wmsSchedulerIdArray = getWmsScheduler();		
		$wmsSchedulerList = implode(",", $wmsSchedulerIdArray);
		$sql = <<<SQL

SELECT scheduler_id, wms_id, wms_title, to_timestamp(wms_timestamp), last_status, fkey_upload_id,  scheduler_interval,scheduler_mail,scheduler_publish,scheduler_searchable,scheduler_overwrite,scheduler_overwrite_categories, scheduler_status FROM (SELECT scheduler_id, wms_id, fkey_wms_id, wms_title, wms_timestamp, scheduler_interval,scheduler_mail,scheduler_publish,scheduler_searchable,scheduler_overwrite,scheduler_overwrite_categories,scheduler_status FROM scheduler INNER JOIN wms ON scheduler.fkey_wms_id=wms.wms_id WHERE scheduler.scheduler_id IN ($wmsSchedulerList)) AS test LEFT OUTER JOIN mb_wms_availability ON test.fkey_wms_id = mb_wms_availability.fkey_wms_id;

SQL;
		$res = db_query($sql);
		$e = new mb_exception($sql);
		$resultObj = array(
			"header" => array(
				_mb("Scheduler ID"),
				_mb("WMS ID"),
				_mb("WMS title"),
				_mb("last change"),
				_mb("last status"),
				_mb("last monitoring"),
				_mb("update interval"),
				_mb("mail notification"),
				_mb("publish"),
				_mb("searchable"),
				_mb("overwrite"),
				_mb("overwrite categories"),
				_mb("update status"),
				_mb("action")
			), 
			"data" => array()
		);

		while ($row = db_fetch_row($res)) {
		    // convert NULL to '', NULL values cause datatables to crash
			$row = array_map('strval', $row);
			$row[] = "<img style='cursor:pointer;' class='deleteImg' title='löschen' src='../img/cross.png' />";
			//if fkey_upload_id is set, format it to date for dataTables
			$row[5] = $row[5]?date("Y-m-d",$row[5]) : $row[5];
			$resultObj["data"][]= $row;
		}
		
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getWmsSchedulerEdit" :
		$wmsSchedulerId =  $ajaxResponse->getParameter("id");	
		$sql = <<<SQL

SELECT scheduler_id, wms_id, wms_title, wms_owner, scheduler_interval,scheduler_mail,scheduler_publish, scheduler_overwrite, scheduler_overwrite_categories, scheduler_searchable FROM scheduler INNER JOIN wms ON scheduler.fkey_wms_id=wms.wms_id WHERE scheduler.scheduler_id = $1;

SQL;
		$v = array($wmsSchedulerId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		if ($res) {
			$row = db_fetch_assoc($res);
			$resultObj["scheduler_id"] = $row["scheduler_id"];
			$resultObj["wms_id"] = $row["wms_id"];
			$resultObj["wms_title"] = $row["wms_title"];
			if (intval($row["wms_owner"]) !== intval($user->id) ) {
				$ajaxResponse->setSuccess(false);
				$ajaxResponse->setMessage(_mb("The user is not allowed to alter the update scheduler."));
				break;
			}
			$resultObj["wms_owner"] = $row["wms_owner"];
			$resultObj["scheduler_interval"] = $row["scheduler_interval"];
			$resultObj["scheduler_mail"] = $row["scheduler_mail"];
			$resultObj["scheduler_publish"] = $row["scheduler_publish"];
			$resultObj["scheduler_overwrite"] = $row["scheduler_overwrite"];
			$resultObj["scheduler_overwrite_categories"] = $row["scheduler_overwrite_categories"];
			$resultObj["scheduler_searchable"] = $row["scheduler_searchable"];
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
		
	case "insertWmsSchedule" :
		$data = $ajaxResponse->getParameter("data");
		
		$sql = <<<SQL

INSERT INTO scheduler (scheduler_type,fkey_wms_id,scheduler_interval,scheduler_publish,
scheduler_searchable,scheduler_overwrite,scheduler_overwrite_categories,scheduler_mail,scheduler_change) VALUES 
('wms', $1, $2, $3, $4, $5, $6, $7, now());

SQL;
		$v = array($data->wms_id, $data->scheduler_interval, $data->scheduler_publish, $data->scheduler_searchable, $data->scheduler_overwrite, $data->scheduler_overwrite_categories, $data->scheduler_mail);
		$t = array('i','s','i','i','i','i','i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not insert wms scheduling in database!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage("Scheduling inserted!");
		$ajaxResponse->setSuccess(true);
		break;

	case "updateWmsSchedule" :
		$schedulerId =  $ajaxResponse->getParameter("schedulerId");
		$wmsSchedulerIdArray = $user->getOwnedWmsScheduler();	
		if (!in_array($schedulerId, $wmsSchedulerIdArray)) {
			abort(_mb("You are not allowed to access this schedule settings."));
		}
		$data = $ajaxResponse->getParameter("data");
		
		$sql = <<<SQL

UPDATE scheduler SET scheduler_interval = $2, scheduler_publish = $3, scheduler_searchable = $4, scheduler_overwrite = $5, scheduler_mail = $6, scheduler_overwrite_categories = $7, scheduler_change = now() WHERE scheduler_id = $1

SQL;
		$v = array($schedulerId, $data->scheduler_interval, $data->scheduler_publish, $data->scheduler_searchable, $data->scheduler_overwrite, $data->scheduler_mail, $data->scheduler_overwrite_categories);
		$t = array('i','s','i','i','i','i','i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not update wms scheduling in database!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage("Scheduling updated!");
		$ajaxResponse->setSuccess(true);

//		$sql = <<<SQL
//
//SELECT scheduler_id, wms_id, wms_title, wms_owner, scheduler_interval,scheduler_mail,scheduler_publish, scheduler_overwrite, scheduler_overwrite_categories, scheduler_searchable FROM scheduler INNER JOIN wms ON scheduler.fkey_wms_id=wms.wms_id WHERE scheduler.scheduler_id = $1;
//
//SQL;
//		$v = array($schedulerId);
//		$t = array('i');
//		$res = db_prep_query($sql,$v,$t);
//
//		$row = array();
//		if ($res) {
//			$row = db_fetch_assoc($res);
//			$resultObj["scheduler_id"] = $row["scheduler_id"];
//			$resultObj["wms_id"] = $row["wms_id"];
//			$resultObj["wms_title"] = $row["wms_title"];
//			if (intval($row["wms_owner"]) !== intval($user->id) ) {
//				$ajaxResponse->setSuccess(false);
//				$ajaxResponse->setMessage(_mb("The user is not allowed to alter the update scheduler."));
//				break;
//			}
//			$resultObj["wms_owner"] = $row["wms_owner"];
//			$resultObj["scheduler_interval"] = $row["scheduler_interval"];
//			$resultObj["scheduler_mail"] = $row["scheduler_mail"];
//			$resultObj["scheduler_publish"] = $row["scheduler_publish"];
//			$resultObj["scheduler_overwrite"] = $row["scheduler_overwrite"];
//			$resultObj["scheduler_overwrite_categories"] = $row["scheduler_overwrite_categories"];
//			$resultObj["scheduler_searchable"] = $row["scheduler_searchable"];
//		}
//		$ajaxResponse->setResult($resultObj);
//		$ajaxResponse->setSuccess(true);
		break;
		
	case "deleteWmsSchedule" :
		$id = $ajaxResponse->getParameter("id");
		
		$sql = <<<SQL

DELETE FROM scheduler WHERE scheduler_id = $1;

SQL;
		$v = array($id);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("Could not delete wms scheduling in database!"));
			$ajaxResponse->send();
			die;	
		}
		$ajaxResponse->setMessage("Scheduling deleted!");
		$ajaxResponse->setSuccess(true);
		break;
			
	case "getUserWms" :
		$user = new User(Mapbender::session()->get("mb_user_id"));
		$wmsSchedulerIdArray = getWmsScheduler();		
		$wmsSchedulerList = implode(",", $wmsSchedulerIdArray);

	    	$wmsIdArray = $user->getOwnedWms();
	    	//$wmsList = implode(",", $wmsIdArray);
		

$sql = <<<SQL

	SELECT fkey_wms_id FROM scheduler WHERE scheduler.scheduler_id IN ($wmsSchedulerList);

SQL;
		$res = db_query($sql);
		$resultObj = array();
		while ($row = db_fetch_array($res)) {
			$resultObj[] = $row['fkey_wms_id'];
			$e = new mb_exception($wmsList);
    	    	}
		//remove already scheduled elements from wms list
		$wmsIdArray = array_diff($wmsIdArray,$resultObj);
		$wmsList = implode(",", $wmsIdArray);

		$e = new mb_exception($wmsList);
		$e = new mb_exception($wmsSchedulerList);

$sql = <<<SQL
	
SELECT wms.wms_id, wms.wms_title FROM wms LEFT JOIN mb_wms_availability AS m
ON wms.wms_id = m.fkey_wms_id 
WHERE wms_id IN ($wmsList)  ORDER BY wms.wms_id;

SQL;
		$res = db_query($sql);
		$resultObj = array();
		while ($row = db_fetch_array($res)) {
			$resultObj[] = array(
    			"wmsId" 	=> $row['wms_id'],
    			"wmsTitle"  =>  $row['wms_title']
    	    	);
		}
        $ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;

	default: 
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("An unknown error occured."));
		break;
}

$ajaxResponse->send();
?>
