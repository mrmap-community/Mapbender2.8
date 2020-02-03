<?php
# $Id: index.php 8761 2014-01-27 22:24:41Z armin11 $
# http://www.mapbender.org/index.php/cors_proxy
# Module maintainer armin11
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
//http://localhost/cors_proxy/1046?VERSION=1.1.1&REQUEST=GetMap&SERVICE=WMS&LAYERS=BPlan.07141058.1.0,BPlan.07141058.1.1,BPlan.07141058.2.0,BPlan.07141058.2.1,BPlan.07141058.3.0,BPlan.07141058.3.1,BPlan.07141058.3.2,BPlan.07141058.4.0,BPlan.07141058.5.0,BPLAN.07141058.0&STYLES=,,,,,,,,,&SRS=EPSG:25832&BBOX=412771.875,5576280,413428.125,5576700&WIDTH=625&HEIGHT=400&FORMAT=image/png&BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_xml&vendorspecific_oek=1
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_administration.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_connector.php");
require_once(dirname(__FILE__) . "/../../owsproxy/http/classes/class_QueryHandler.php");
$startTime = microtime(true);
$imageformats = array("image/png","image/gif","image/jpeg", "image/jpg");
$width = 400;
$height = 400;
$tmpSession = false;
$corsAllowedFor = false;
//parse url
$query = new QueryHandler();
$reqParams = $query->getRequestParams();
//echo $query->getRequest();
//check request for id
if (isset($_REQUEST["wmsid"]) & $_REQUEST["wmsid"] != "") {
        $testMatch = $_REQUEST["wmsid"];
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
		throwExceptionXml('','Parameter for wmsid is not valid (integer)');
        }
        $wmsId = $testMatch;
        $testMatch = NULL;
}
//check if session has user_id
$e = new mb_exception("Initial session_id: ".session_id());
$e = new mb_exception("userFromSession: ".getUserFromSession());
if (getUserFromSession()) {
	$userId = getUserFromSession();
} else {
	$e = new mb_exception("cors_proxy/http/index.php: No userId found in session - delete session after proxied request!");
	$userId = PUBLIC_USER;
	$tmpSession = session_id();
}
//check header - see invoking server
$headers = apache_request_headers();//from php 5.4 also for php fcgi
/*foreach ($headers as $header => $value) {
    $e = new mb_exception("cors_proxy: http header: $header: $value");
}*/
$originFromHeader = false;
foreach ($headers as $header => $value) {
    	if ($header === "Origin") {
		$originFromHeader = $value;
    	}
}
if ($originFromHeader == false) {
	throwExceptionXml('','CORS Proxy don\'t find Origin header from client!');
}
//check server against whitelist for cors header
if (defined("CORS_WHITELIST") && CORS_WHITELIST != "") {
	//check if server is in cors whitelist
	$CORS_WHITELIST_array = explode(" ",CORS_WHITELIST);
	if (!in_array($originFromHeader,$CORS_WHITELIST_array)) {
		throwExceptionXml('','Server not found in whitelist of cors_proxy, please check your configuration!');
	} else {
		$corsAllowedFor = $originFromHeader;
	}
} else {
	throwExceptionXml('','Mapbenders cors_proxy has no whitelist defined, check your configuration!');
}
//check if wms has open data licence!
//TODO
$n = new administration();
//get authentication infos if they are available in wms table! if not $auth = false
$auth = $n->getAuthInfoOfWMS($wmsId);
if ($auth['auth_type']==''){
	unset($auth);
}
$e = new mb_exception("userId: ".$userId);
//check header - see invoking server
switch (strtolower($reqParams['request'])) {
	case 'getmap':
		$arrayOnlineresources = checkWmsPermission($wmsId, $userId);
		$query->setOnlineResource($arrayOnlineresources['wms_getmap']);
		$layers = checkLayerPermission($arrayOnlineresources['wms_id'],$reqParams['layers'],$userId);
		if($layers===""){
			throwE("Permission denied");
			die();
		}
		$query->setParam("layers",urldecode($layers));//the decoding of layernames dont make problems - but not really good names will be requested also ;-)
		$request = $query->getRequest();
		$startRequestTime = microtime(true);
		if(isset($auth)){
			getImage($request,$auth);
		}
		else {
			getImage($request);
		}
		$endRequestTime = microtime(true);
		break;
	case 'map':
		$arrayOnlineresources = checkWmsPermission($wmsId, $userId);
		$query->setOnlineResource($arrayOnlineresources['wms_getmap']);
		$layers = checkLayerPermission($arrayOnlineresources['wms_id'],$reqParams['layers'], $userId);
		if($layers===""){
			throwE("Permission denied");
			die();
		}
		$query->setParam("layers",urldecode($layers));
		$request = $query->getRequest();
		if(isset($auth)){
			getImage($url,$auth);
		}
		else {
			getImage($url);
		}
		break;	
	default:	
}

//delete tmpSession 
if ($tmpSession) {
	$e = new mb_notice("cors_proxy/http/index.php: temporal generated session will be deleted!");
	Mapbender::session()->storageDestroy($tmpSession);
} 
$endTime = microtime(true);
$e = new mb_exception("cors_proxy/http/index.php: Time of execution: ".(string)($endTime - $startTime). "s - time for getting image: ".($endRequestTime - $startRequestTime)."s - time for script and control: ".(string)(($endTime - $endRequestTime) +  ($startRequestTime - $startTime)));
//following functions came from owsproxy - maybe they will be better defined in a proxy class itself 
//checkWmsPermission
//checkLayerPermission
//throwE
//getImage
function checkLayerPermission($wms_id,$l,$userId){
	global $n;
	$myl = explode(",",$l);
	$r = array();
	foreach($myl as $mysl){
		if($n->getLayerPermission($wms_id, $mysl, $userId) === true){
			array_push($r, $mysl);
		}		
	}
	$ret = implode(",",$r);
	return $ret;
}
/**
 * validated access permission on requested wms
 * 
 * @param integer wms_id
 * @return array array with detailed information about requested wms
 */
function checkWmsPermission($wmsid,$userId){
	global $n;
	$myguis = $n->getGuisByPermission($userId,true);
	$mywms = $n->getWmsByOwnGuis($myguis);
	$sql = "SELECT * FROM wms WHERE wms_id = $1";
	$v = array($wmsid);
	$t = array("i");
	$res = db_prep_query($sql, $v, $t);
	$service = array();
	if($row = db_fetch_array($res)){
		$service["wms_id"] = $row["wms_id"];
		$service["wms_getcapabilities"] = $row["wms_getcapabilities"];	
		$service["wms_getmap"] = $row["wms_getmap"];
		$service["wms_getfeatureinfo"] = $row["wms_getfeatureinfo"];
		$service["wms_getcapabilities_doc"] = $row["wms_getcapabilities_doc"];
	}
	if(!$row || count($mywms) == 0){
		throwE(array("No wms data for this user available."));
		die();	
	}
	if(!in_array($service["wms_id"], $mywms)){
		throwE(array("Permission denied."," -> ".$service["wms_id"], implode(",", $mywms)));
		die();
	}
	return $service;
}
/**
 * fetch and returns an image to client
 * 
 * @param string the original url of the image to send
 */
function getImage($or){
	global $reqParams, $corsAllowedFor;
	header("Content-Type: ".$reqParams['format']);
	//set cors header
	if ($corsAllowedFor != false) {	
		header('Access-Control-Allow-Origin: '.$corsAllowedFor);
	} else {
		header('Access-Control-Allow-Origin: '."");
	}
	//log the image_requests to database
	//log the following to table mb_proxy_log
	//timestamp,user_id,getmaprequest,amount pixel,price - but do this only for wms to log - therefor first get log tag out of wms!
	//
	//
	if (func_num_args() == 2) { //new for HTTP Authentication
		$auth = func_get_arg(1);
		echo getDocumentContent($or,$auth);
	}
	else
	{
		echo getDocumentContent($or);
	}

}
/*********************************************************/
function throwE($e){
	global $reqParams, $imageformats;
	if(in_array($reqParams['format'],$imageformats)){
		throwImage($e);
	}
	else{
		throwText($e);	
	}
}

function throwImage($e){
	global $width,$height;
	$image = imagecreate($width,$height);
	$transparent = ImageColorAllocate($image,155,155,155); 
	ImageFilledRectangle($image,0,0,$width,$height,$transparent);
	imagecolortransparent($image, $transparent);
	$text_color = ImageColorAllocate ($image, 233, 14, 91);
	if (count($e) > 1){
		for($i=0; $i<count($e); $i++){
			$imageString = $e[$i];
			ImageString ($image, 3, 5, $i*20, $imageString, $text_color);
		}
	} else {
		if (is_array($e)) {
			$imageString = $e[0];
		} else {
			$imageString = $e;
		}
		if ($imageString == "") {
			$imageString = "An unknown error occured!";
		}
		ImageString ($image, 3, 5, $i*20, $imageString, $text_color);
	}
	responseImage($image);
}

function throwText($e){
	echo join(" ", $e);
}

function responseImage($im){
	global $reqParams, $corsAllowedFor;
	$format = $reqParams['format'];
	$format="image/gif";	
	if ($corsAllowedFor != false) {	
		header('Access-Control-Allow-Origin: '.$corsAllowedFor);
	} else {
		header('Access-Control-Allow-Origin: '."");
	}
	
	if($format == 'image/png'){header("Content-Type: image/png");}
	if($format == 'image/jpeg' || $format == 'image/jpg'){header("Content-Type: image/jpeg");}
	if($format == 'image/gif'){header("Content-Type: image/gif");}
 
	if($format == 'image/png'){imagepng($im);}
	if($format == 'image/jpeg' || $format == 'image/jpg'){imagejpeg($im);}
	if($format == 'image/gif'){imagegif($im);}	
}

function getDocumentContent($url){
	//$e = new mb_exception("cors_proxy/http/index.php: Begin to request external image: ".microtime(true));
	if (func_num_args() == 2) { //new for HTTP Authentication
       		$auth = func_get_arg(1);
		$d = new connector($url, $auth);
	}
	else {
		$d = new connector($url);
	}
	//$e = new mb_exception("cors_proxy/http/index.php: Got image!: ".microtime(true));
	return $d->file;
}

/**
 * Creates an XML Exception according to WMS 1.1.1
 * 
 * @return an XML String
 * @param $errorCode String
 * @param $errorMessage String
 */
function throwExceptionXml ($errorCode, $errorMessage) {
	// see http://de2.php.net/manual/de/domimplementation.createdocumenttype.php
	$imp = new DOMImplementation;
	$dtd = $imp->createDocumentType("ServiceExceptionReport", "", "http://schemas.opengis.net/wms/1.1.1/exception_1_1_1.dtd");
	
	$doc = $imp->createDocument("", "", $dtd);
	$doc->encoding = 'UTF-8';
	$doc->standalone = false;
	
	$el = $doc->createElement("ServiceExceptionReport");
	$exc = $doc->createElement("ServiceException", $errorMessage);
	if ($errorCode) {
		$exc->setAttribute("code", $errorCode);
	}
	$el->appendChild($exc);
	$doc->appendChild($el);
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	header('Access-Control-Allow-Origin: '.CORS_WHITELIST);
	echo $doc->saveXML();
	die;
}

function getUserFromSession() {
	if (Mapbender::session()->get('mb_user_id')) {
		if ((integer)Mapbender::session()->get('mb_user_id') >= 0) {
			$foundUserId = (integer)Mapbender::session()->get('mb_user_id');
		} else {
			$foundUserId = false;
		}
	} else {
		$foundUserId = false;
	}
	return $foundUserId;
}
?>
