<?php
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
require_once dirname(__FILE__) . "/../classes/class_user.php";
require_once dirname(__FILE__) . "/../classes/class_wms.php";
require_once(dirname(__FILE__) . "/../classes/class_universal_wfs_factory.php");

//give back every result as json
header('Content-Type: application/json; charset=utf-8');

$resultObject->success = false;
//check if invoked from localhost
$whitelist = array(
    '127.0.0.1',
    '::1'
);

$userId = Mapbender::session()->get("mb_user_id");
if ($userId == false) {
    $userId = PUBLIC_USER;
}

if ($userId != '1') {
    if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
        $resultObject->error->message = 'The service was not invoked from localhost!';
        echo json_encode($resultObject);
        die();
    }
}

$time_start = microtime(true);
/*
 * Default values for information from scheduler table
 */
//send mail to subscribers and users who integrates service in some gui
//$schedulerMail = null;
//publish via rss
$schedulerPublish = null;
//make new resources searchable 
$schedulerSearchable = null;
//overwrite metadata with metadata from service
$schedulerOverwrite = null;
//overwrite categories with categories from service - wms only
$schedulerOverwriteCategories = null;

//give back every result as json
header('Content-Type: application/json; charset=utf-8');

//validate parameters
$schedulerParams = array("schedulerPublish", "schedulerSearchable", "schedulerOverwrite", "schedulerOverwriteCategories");
foreach ($schedulerParams as $schedulerParam) {    
    if (isset($_REQUEST[$schedulerParam]) & $_REQUEST[$schedulerParam] != "") {
        $testMatch = $_REQUEST[$schedulerParam];
        if (!($testMatch == 'true' or $testMatch == 'false')){
            $resultObject->error->message =   'Parameter $schedulerParam is not valid (true,false).';
            echo json_encode($resultObject);
            die();
        }
        switch ($testMatch) {
            case "true":
                ${$schedulerParam} = true;
                break;
            case "false":
                ${$schedulerParam} = false;
                break;
        }
        $testMatch = NULL;
    }
}

if (isset($_REQUEST["serviceType"]) & $_REQUEST["serviceType"] != "") {
    $testMatch = $_REQUEST["serviceType"];
    if (!($testMatch == 'wms' || $testMatch == 'wfs')){
        $resultObject->error->message = 'Parameter serviceType is not valid (wms, wfs).';
        echo json_encode($resultObject);
        die();
    }
    $serviceType = $testMatch;
    $testMatch = NULL;
}
$serviceType= strtoupper($serviceType);

if (isset($_REQUEST["serviceId"]) & $_REQUEST["serviceId"] != "") {
    $testMatch = $_REQUEST["serviceId"];
    $pattern = '/^[0-9]*$/';
    if (!preg_match($pattern,$testMatch)){
        $resultObject->error->message = 'Parameter serviceId is not valid (integer).';
        echo json_encode($resultObject);
        die();
    }
    $serviceId = $testMatch;
    $testMatch = NULL; 
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
} 

switch ($serviceType) {
    case "WMS":
        $sql = "SELECT wms_id as \"serviceId\", wms_title as \"serviceTitle\", wms_owner as \"serviceOwner\", wms_upload_url as \"serviceUploadUrl\", ";
        $sql .= "wms_username as \"serviceAuthUser\", wms_password as \"serviceAuthPassword\", wms_auth_type as \"serviceAuthType\", ";
        $sql .= "scheduler.scheduler_publish as \"schedulerPublish\", scheduler.scheduler_overwrite as \"schedulerOverwrite\", ";
        $sql .= "scheduler.scheduler_overwrite_categories as \"schedulerOverwriteCategories\", scheduler.scheduler_searchable as \"schedulerSearchable\" ";
        $sql .= "FROM wms LEFT JOIN scheduler ON wms.wms_id = scheduler.fkey_wms_id WHERE wms_id = $1;";     
        break;
    case "WFS":
        $sql = "SELECT wfs_id as \"serviceId\", wfs_title as \"serviceTitle\", wfs_owner as \"serviceOwner\", wfs_upload_url as \"serviceUploadUrl\", ";
        $sql .= "wfs_username as \"serviceAuthUser\", wfs_password as \"serviceAuthPassword\", wfs_auth_type as \"serviceAuthType\", ";
        $sql .= "scheduler.scheduler_publish as \"schedulerPublish\", scheduler.scheduler_overwrite as \"schedulerOverwrite\", ";
        $sql .= "scheduler.scheduler_overwrite_categories as \"schedulerOverwriteCategories\", scheduler.scheduler_searchable as \"schedulerSearchable\" ";
        $sql .= "FROM wfs LEFT JOIN scheduler ON wfs.wfs_id = scheduler.fkey_wfs_id WHERE wfs_id = $1;";     
        break;
}

$v = array($serviceId);
$t = array('i');
$res = db_prep_query($sql,$v,$t);

if (db_fetch_array( $res ) == false) {
    $resultObject->error->message = 'No service with id ' . $serviceId . ' found in registry!';
    echo json_encode($resultObject);
    die();   
} else {
    $res = db_prep_query($sql,$v,$t);
}

while ($row = db_fetch_array( $res )) {
    switch ($serviceType) {
        case "WMS":
            $updateWms = new wms();
            $updateWms->harvestCoupledDatasetMetadata = true;
            break;
        case "WFS":
            $wfsFactory = new UniversalWfsFactory();
            break;
    }
       
    //extract params for scheduler 
    $schedulerConf = array();
    foreach ($schedulerParams as $schedulerParam) {
        //add result from db to array
        $schedulerConf[$schedulerParam] = $row[$schedulerParam];
        //get-params overwrite params from db
        if (!isset(${$schedulerParam})) {
            if ($row[$schedulerParam] == '1') {
                ${$schedulerParam} = true; 
            } else {
                ${$schedulerParam} = false; 
            }
        }
    }
    
    //update service
    if ($row['serviceAuthType'] == "basic" || $row['serviceAuthType'] == "digest"){
        $auth = array();
        $auth['auth_type'] = $row['serviceAuthType'];
        $auth['username'] = $row['serviceAuthUser'];
        $auth['password'] = $row['serviceAuthPassword'];
        switch ($serviceType) {
            case "WMS":
                $createObjFromXml = $updateWms->createObjFromXML($row['serviceUploadUrl'], $auth);
                break;
            case "WFS":
                $updateWfs = $wfsFactory->createFromUrl($row['serviceUploadUrl'], $auth);
                break;
        }  
    } else {
        switch ($serviceType) {
            case "WMS":
                $createObjFromXml = $updateWms->createObjFromXML($row['serviceUploadUrl']);
                break;
            case "WFS":
                $updateWfs = $wfsFactory->createFromUrl($row['serviceUploadUrl'], false);
                break;
        }           
    }
    
    switch ($serviceType) {
        case "WMS":
            $updateWms->overwrite = $schedulerOverwrite;
            $updateWms->overwriteCategories = $schedulerOverwriteCategories;
            $updateWms->setGeoRss = $schedulerPublish;
            $updateWms->twitterNews = false;
            if ($createObjFromXml['success'] == false) {
                $resultObject->error->message = $createObjFromXml['message'];
                echo json_encode($resultObject);
                die();
            }
            $updateWms->owner = $row['serviceOwner'];
            $updateWms->optimizeWMS($schedulerSearchable);
            //do the update
            try {
                $updateWms->updateObjInDB($row['serviceId']);
            }
            catch(Exception $e) {
                $resultObject->error->message = 'WMS could not be updated - check needed!';
                echo json_encode($resultObject);
                die();
            }   
            break;
        case "WFS":
            $updateWfs->overwrite = $schedulerOverwrite;
            $updateWfs->id = $row['serviceId'];
            if (is_null($updateWfs) || !$updateWfs->update()) {
                $resultObject->error->message = 'WFS could not be updated - check needed!';
                echo json_encode($resultObject);
                die();
            }
            break;
    }
}

$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes otherwise seconds
$execution_time = ($time_end - $time_start);

$resultObject->success = true;

switch ($serviceType) {
    case "WMS":
        $resultObject->result->service_title = $updateWms->wms_title;
        $resultObject->result->service_id = $updateWms->wms_id;
        $resultObject->result->overwrite = $updateWms->overwrite;
        $resultObject->result->resources_searchable = $schedulerSearchable;
        $resultObject->result->overwrite_categories = $updateWms->overwriteCategories;
        $resultObject->result->publish = $schedulerPublish;
        $resultObject->result->number_of_resources = count($updateWms->objLayer);
        break;
    case "WFS":
        $resultObject->result->service_title = $updateWfs->title;
        $resultObject->result->service_id = $updateWfs->id;
        $resultObject->result->overwrite = $updateWfs->overwrite;
        $resultObject->result->number_of_resources = count($updateWfs->featureTypeArray);
        break;
}
$resultObject->result->service_type = $serviceType;
$resultObject->result->duration_time = $execution_time;
$resultObject->message = "Service with id " . $serviceId . " updated!";
$resultObject->result->scheduler_conf = $schedulerConf;
echo json_encode($resultObject);
?>