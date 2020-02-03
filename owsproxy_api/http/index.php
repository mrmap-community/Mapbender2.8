<?php
require(dirname(__FILE__) . "/../../conf/mapbender.conf");
//require(dirname(__FILE__) . "/../../http/classes/class_administration.php");
//require(dirname(__FILE__) . "/../../http/classes/class_connector.php");
//require_once(dirname(__FILE__) . "/../../http/classes/class_mb_exception.php");
//require(dirname(__FILE__) . "/../../owsproxy/http/classes/class_QueryHandler.php");
require_once dirname(__FILE__) . "/../../http/classes/class_wms_owsproxy_log.php";

//database connection
$db = db_connect($DBSERVER,$OWNER,$PW);
db_select_db(DB,$db);

//control if digest auth is set, if not set, generate the challenge with getNonce()
if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.REALM.
           '",qop="auth",nonce="'.getNonce().'",opaque="'.md5(REALM).'"');
    die('Text to send if user hits Cancel button');
}

//read out the header in an array
$requestHeaderArray = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);

//error if header could not be read
if (!($requestHeaderArray)) {
	echo 'Following Header information cannot be validated - check your clientsoftware!<br>';
	echo $_SERVER['PHP_AUTH_DIGEST'].'<br>';
	die();
}

//get mb_username and email out of http_auth username string
$userIdentification = explode(';',$requestHeaderArray['username']);
$mbUsername = $userIdentification[0];
$mbEmail = $userIdentification[1];

$userInformation = getUserInfo($mbUsername,$mbEmail);

if ($userInformation[0] == '-1') {
	die('User with name: '.$mbUsername.' and email: '.$mbEmail.' not known to security proxy!');
}

if ($userInformation[1]=='') { //check if digest exists in db - if no digest exists it should be a null string!
	die('User with name: '.$mbUsername.' and email: '.$mbEmail.' has no digest - please set a new password and try again!');
}

//first check the stale!
if($requestHeaderArray['nonce'] == getNonce()) {
    // Up-to-date nonce received
    $stale = false;
} else {
    // Stale nonce received (probably more than x seconds old)
    $stale = true;
    //give another chance to authenticate
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.REALM.'",qop="auth",nonce="'.getNonce().'",opaque="'.md5(REALM).'" ,stale=true');	
}
// generate the valid response to check the request of the client
$A1 = $userInformation[1];
$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$requestHeaderArray['uri']);
$valid_response = $A1.':'.getNonce().':'.$requestHeaderArray['nc'];
$valid_response .= ':'.$requestHeaderArray['cnonce'].':'.$requestHeaderArray['qop'].':'.$A2;

$valid_response=md5($valid_response);

if ($requestHeaderArray['response'] != $valid_response) {//the user have to authenticate new - cause something in the authentication went wrong
    die('Authentication failed - sorry, you have to authenticate once more!'); 
}

//functions for http_auth 
//**********************************************************************************************

// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);	
    $data = array();
    $keys = implode('|', array_keys($needed_parts));
    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }
    return $needed_parts ? false : $data;
}
// function to get relevant user information from mb db
function getUserInfo($mbUsername,$mbEmail) {
	$result = array();
	$sql = "SELECT mb_user_id, mb_user_digest FROM mb_user where mb_user_name = $1 AND mb_user_email= $2";
	$v = array($mbUsername, $mbEmail);
	$t = array("s","s");
	$res = db_prep_query($sql, $v, $t);
	if(!($row = db_fetch_array($res))){
		$result[0] = "-1";
	}
	else {
		$result[0] = $row['mb_user_id'];
		$result[1] = $row['mb_user_digest'];
	}
	return $result;
}

function getNonce() {
	global $nonceLife;
	$time = ceil(time() / $nonceLife) * $nonceLife;
	return md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.NONCEKEY);
}

//if we are here - authentication has been done well!

$function = isset($_REQUEST['function']) ? $_REQUEST['function'] : null;#getServiceLogs,deleteServiceLogs,listServiceLogs
$listType = isset($_REQUEST['listType']) ? $_REQUEST['listType'] : null;#service,user

$serviceType = isset($_REQUEST['serviceType']) ? $_REQUEST['serviceType'] : null;#wms

$userId = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : null;#XXX
$wmsId = isset($_REQUEST['wmsId']) ? $_REQUEST['wmsId'] : null;# XXX
$timeFrom = isset($_REQUEST['timeFrom']) ? $_REQUEST['timeFrom'] : null;#
$timeTo = isset($_REQUEST['timeTo']) ? $_REQUEST['timeTo'] : null;#
$withContactData = isset($_REQUEST['withContactData']) ? $_REQUEST['withContactData'] : null;# 1,

if($serviceType === null){
    die ("Der Parameter 'serviceType' wurde nicht uebergeben.");
}

if($serviceType == "wms"){
    $mb_user_id = $userInformation[0];
    $wmslog = WmsOwsLogCsv::create($mb_user_id, $function, $userId, $wmsId,
        $listType, $timeFrom, $timeTo, $withContactData);
    $wmslog->handle();
    header("Content-Type: text/csv; charset=".CHARSET);
    header("Content-Disposition: attachment; filename=csv_export.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    $csv = $wmslog->getAsCsv();
    print $csv;
    die();
} else {
    die ("Der 'serviceType'".$serviceType." ist nicht unterstuetzt.");
}

?>
