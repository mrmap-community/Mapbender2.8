<?php
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
$hostName = $_SERVER['HTTP_HOST'];

$resourceType = "layer";
$userId = 2;
$resourceId = "";
$allowedResourceTypes = array("layer", "featuretype");

$resultObj['result'] = '';
$resultObj['success'] = false;
$resultObj['message'] = 'no message';

if (!($hostName == 'localhost' or $hostName == '127.0.0.1')) {
	$resultObj['message'] ='hostName not allowed - only local connections possible (localhost,127.0.0.1)';
	$resultObj['result'] = null;
	echo json_encode($resultObj);
	die();
}

if (isset($_REQUEST["userId"]) & $_REQUEST["userId"] != "") {
 //validate integer to 100 - not more
 $testMatch = $_REQUEST["userId"];
 //give max 99 entries - more will be to slow
 $pattern = '/^[0-9]*$/';
 if (!preg_match($pattern,$testMatch)){
 //echo 'userId: <b>'.$testMatch.'</b> is not valid.<br/>';
 echo 'Parameter <b>userId</b> is not valid (integer).<br/>';
 die();
 }
 $userId = $testMatch;
 $testMatch = NULL;
}

if (!isset($_REQUEST["resourceId"])) {
    $resultObj['message'] ='mandatory parameter resourceId not set!';
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    
}

if (isset($_REQUEST["resourceType"]) & $_REQUEST["resourceType"] != "") {
    //validate to inside / outside - TODO implement other ones than intersects which is default
    $testMatch = $_REQUEST["resourceType"];
    if (!in_array($testMatch, $allowedResourceTypes)){
        echo 'Parameter <b>resourceType</b> is not valid (' . implode(",", $allowedResourceTypes) . ').<br/>';
        die();
    }
    $resourceType = $testMatch;
    $testMatch = NULL;
}

if (isset($_REQUEST["resourceId"]) & $_REQUEST["resourceId"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["resourceId"];
    $pattern = '/^[\d,]*$/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'resourceIds: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo 'Parameter <b>resourceId</b> is not valid (integer or cs integer list).<br/>';
        die();
    }
    $resourceId = $testMatch;
    $testMatch = NULL;
}

//explode resourceIds to array of ids
$resourceId = (integer)$resourceId;

$user = new User($userId);
//$e = new mb_exception($resourceId);
//$e = new mb_exception($user->id);
//$e = new mb_exception($resourceType);
switch ($resourceType) {
    case "layer":
        $accessability = $user->isLayerAccessible($resourceId);
        break;
    case "featuretype":
        $v = array($resourceId);
        $t = array('i');
        $sql = "SELECT fkey_wfs_id, featuretype_name FROM wfs_featuretype WHERE wfs_featuretype.featuretype_id = $1";
        $res = db_prep_query($sql, $v, $t);
        $row = db_fetch_array($res);
        if ($row) {
            $wfsId = $row["fkey_wfs_id"];
            $featuretypeName = $row["featuretype_name"];
            $accessability = $user->areFeaturetypesAccessible ($featuretypeName, $wfsId);
            //$e = new mb_exception($wfsId);
            //$e = new mb_exception($featuretypeName);
        } else {
            $accessability = false;
        }
        break;
}
$resultObj['result'] = $accessability;
$resultObj['success'] = true;
if ($accessability == true){
    $resultObj['message'] = 'Access allowed';
} else {
    $resultObj['message'] = 'Access denied!';
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultObj);
?>