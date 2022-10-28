<?php

# $Id: index.php 10393 2020-01-30 13:46:02Z armin11 $
# http://www.mapbender2.org/index.php/Owsproxy
# Module maintainer Uli
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

require(dirname(__FILE__) . "/../../conf/mapbender.conf");

require_once(dirname(__FILE__) . "/../../http/classes/class_administration.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_connector.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_mb_exception.php");
require_once(dirname(__FILE__) . "/./classes/class_QueryHandler.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_ogr.php");
$urlsToExclude = array();
$postData = false;
if (is_file(dirname(__FILE__) . "/../../conf/excludeproxyurls.conf"))
{
    require_once(dirname(__FILE__) . "/../../conf/excludeproxyurls.conf");
}
//

//database connection
$con = db_connect(DBSERVER, OWNER, PW);
db_select_db(DB, $con);

/* * *** conf **** */
$imageformats = array("image/png", "image/gif", "image/jpeg", "image/jpg");
$width = 400;
$height = 400;
/* * *** conf **** */

$owsproxyService = $_REQUEST['wms']; //ToDo: change this to 'service' in the apache url-rewriting

//test for existing post data
$postData = file_get_contents("php://input");
if (isset($postData) && $postData !== '') {
	
} else {
	$postData = false;
}

$query = new QueryHandler($postData, $_REQUEST, $_SERVER['REQUEST_METHOD']);

// an array with keys and values toLowerCase -> caseinsensitiv
$reqParams = $query->getRequestParams($reqParams);
//$e = new mb_exception(json_encode($reqParams));
if ($reqParams['service'] == 'WFS') {
	//switch for different parameter name - typename for wfs < 2.0 typenames for wfs >= 2.0
	//$typeNameParameter = "typename"; //lowercase
	switch ($reqParams['version']) {
		case "2.0.0":
			if (strtolower($reqParams['request']) == 'describefeaturetype') {
			    $typeNameParameter = "typename";
                        } else {
			    $typeNameParameter = "typenames";
			}
			break;
		case "2.0.2":
			if (strtolower($reqParams['request']) == 'describefeaturetype') {
			    $typeNameParameter = "typename";
                        } else {
			    $typeNameParameter = "typenames";
			}
			break;
		default:
			$typeNameParameter = "typename";
			break;
	}
	//initialize typename parameter with false - not given
	//check for featuretype name
	if (isset($reqParams[$typeNameParameter]) & $reqParams[$typeNameParameter] != "") {
        	//validate featuretype_name
        	$testMatch = $reqParams[$typeNameParameter];
        	//simple pattern - without blanks!
       	 	$pattern = '/^[0-9a-zA-Z\.\-_:,]*$/';  
        	if (!preg_match($pattern,$testMatch)){
             		//echo 'userId: <b>'.$testMatch.'</b> is not valid.<br/>';
                	echo 'Parameter <b>'.$typeNameParameter.'</b> is not valid.<br/>';
                	die();
        	}
		    $reqParams[$typeNameParameter] = $testMatch;
        	$testMatch = NULL;
	}
}
//$e = new mb_exception("l99");
$e = new mb_notice("incoming request: " . OWSPROXY . "/" . $_REQUEST['sid'] . "/" . $_REQUEST['wms'] . $query->getRequest());
$e = new mb_notice("owsproxy requested from: " . $_SERVER["REMOTE_ADDR"]);
$e = new mb_notice("owsproxy requested: " . $query->getRequest());

$sid = $_REQUEST['sid'];
$serviceId =  $_REQUEST['wms'];

if (defined("OWSPROXY_SESSION_GRABBING_WHITELIST")) {
    $whiteListArray = explode(",", OWSPROXY_SESSION_GRABBING_WHITELIST);
    if (in_array($_SERVER["REMOTE_ADDR"], $whiteListArray)) {
        $grabbingAllowed = true;
        $e = new mb_notice("Grabbing allowed for IP: " . $_SERVER["REMOTE_ADDR"]);
    } else {
        $grabbingAllowed = false;
        $e = new mb_notice("Grabbing not allowed for IP: " . $_SERVER["REMOTE_ADDR"] . "!");
    }
} else {
    $grabbingAllowed = false;
}
//$e = new mb_exception("l119");
$e = new mb_notice("Initial session_id: " . session_id());
//The session can be set by a given cookie value or was newly created by core/globalSettings.php
//either empty (without mb_user_id value) - when the corresponding session file was lost or timed out
//or filled, when there was an actual mapbender session before
//check if mb_user_id is given and is an string with an integer:
$e = new mb_notice("userFromSession: " . getUserFromSession());

//Possibility to grap an existing session:
if (defined("OWSPROXY_ALLOW_SESSION_GRABBING") && OWSPROXY_ALLOW_SESSION_GRABBING == true) {
    if ($grabbingAllowed) { //for this ip
        $currentSession = session_id();
        //check for existing session in session storage - maybe the request came from outside - e.g. other browser, other application
        $existSession = Mapbender::session()->storageExists($_REQUEST["sid"]);
        if ($existSession) {
            $e = new mb_notice("storage exists");
        } else {
            $e = new mb_notice("storage does not exist!");
        }
        if ($existSession && $currentSession !== $_REQUEST["sid"]) {
            //there is a current session for the requested url
            $e = new mb_notice("A current session exists for this url and will be used!");
            //$oldsessionId = session_id();
            $tmpSession = session_id();
            //do the following only, if a user is in this session - maybe it is a session which was generated from an external application and therefor it is empty!
            //grab session, cause it is allowed
            session_id($_REQUEST["sid"]);
            $e = new mb_notice("Grabbed session with id: " . session_id());
            //kill dynamical session
            //@unlink($tmpSessionFile);
            $e = new mb_notice("Following user was found and will be used for authorization: " . Mapbender::session()->get('mb_user_id'));
            //$foundUserId = Mapbender::session()->get('mb_user_id');
            if (getUserFromSession() == false || getUserFromSession() <= 0) {
                $e = new mb_notice("No user found in the existing session - switch to the initial old one!");
                session_id($tmpSession);
            } else {
                //delete session as it will not be needed any longer
                $e = new mb_notice("Some reasonable user id found in grabbed session. Following temporary session will be deleted: " . $tmpSession);
                Mapbender::session()->storageDestroy($tmpSession);
                unset($tmpSession);
            }
        } else {
            $e = new mb_notice("Maybe either a session does not exist for the requested SID and/or the current session is equal to the requested SID. No grabbing should be done! The variable tmpSession will not be created.");
        }
    }
}
//$e = new mb_exception("l165");
//After this there maybe the variable $tmpSession or not. If it is not there, an existing session was grabbed and shouldn't be deleted after, because a user is logged in or the logged in user requested the service!!!
//check if current session has the same id as the session which is requested in the owsproxy url
//exchange them, if they differ and redirect to an new one with the current session, they don't differ if the session was grabbed - e.g. when printing secured services via mapbender itself.
if (session_id() !== $_REQUEST["sid"]) {
    //get all request params which are original
    $e = new mb_notice("session_id " . session_id());
    $e = new mb_notice("sid " . $_REQUEST["sid"]);
    //build reuquest
    $redirectUrl = OWSPROXY . "/" . session_id() . "/" . $_REQUEST['wms'] . $query->getRequest();
    $redirectUrl = str_replace(":80:80", ":80", $redirectUrl);
    $e = new mb_notice("IDs differ - redirect to new owsproxy url: " . $redirectUrl);
    header("Location: " . $redirectUrl);
    die();
} else {
    //$e = new mb_exception("Current session_id() identical to requested SID!");
}
//$e = new mb_exception("l180");
//this is the request which may have been redirected
//check for given user session with user_id which can be tested against the authorization
/* $foundUserId = Mapbender::session()->get('mb_user_id');
  $e = new mb_exception("Found user id: ".$foundUserId ." of type: ".gettype($foundUserId));
  $foundUserId = (integer)$_SESSION['mb_user_id'];
  $e = new mb_exception("Found user id: ".$foundUserId ." of type: ".gettype($foundUserId));
  $foundUserId = getUserFromSession(); */

if (getUserFromSession() == false || getUserFromSession() <= 0) {
    //Define the session to be temporary - it should be deleted afterwards, cause there is no user in it! This file can be deleted after the request was more or less successful. It will be generated every time again.
    $tmpSession = session_id();
    $e = new mb_notice(" session_id(): " . session_id());
    $e = new mb_notice("user_id not found in session!");
    //if configured in mapbender.conf, create guest session so that also proxied service can be watched in external applications when they are available to the anonymous user
    //only possible for webapplications - in case of desktop applications the user have to use his credentials and http_auth module
    if (defined("OWSPROXY_ALLOW_PUBLIC_USER") && OWSPROXY_ALLOW_PUBLIC_USER && defined("PUBLIC_USER") && PUBLIC_USER != "") {
        //setSession();
        Mapbender::session()->set("mb_user_id", PUBLIC_USER);
        Mapbender::session()->set("external_proxy_user", true);
        Mapbender::session()->set("mb_user_ip", $_SERVER['REMOTE_ADDR']);
        $e = new mb_notice("Permission allowed for public user with id: " . PUBLIC_USER);
    } else {
        $e = new mb_notice("Permission denied - public user not allowed to access ressource!");
        //kill actual session  
        $e = new mb_notice("delete temporary session file: " . $tmpSession);
        Mapbender::session()->storageDestroy($tmpSession);
        throwE(array("Permission denied", " - no current session found and ", "public user not allowed to access ressource!"));
        unset($tmpSession);
        die();
    }
} else {
    /* $e = new mb_exception("mb_user_id found in session: ".getUserFromSession());
      if (isset($tmpSession)) {
      $e = new mb_exception("tmpSessionFile: exists! - It was set before grabbing!");
      } else {
      $e = new mb_exception("tmpSessionFile: does not exist!");
      } */
}
//$e = new mb_exception("l218");
//start the session to be able to write urls to it - for 
session_start(); //maybe it was started by globalSettings.php
$n = new administration;
//Extra security - IP check 
if (defined("OWSPROXY_BIND_IP") && OWSPROXY_BIND_IP == true) {
    if (Mapbender::session()->get('mb_user_ip') != $_SERVER['REMOTE_ADDR']) {
        throwE(array("Session not identified.", "Permission denied.", "Please authenticate."));
        die();
    }
}
$e = new mb_notice("user id for authorization test: " . getUserFromSession());
//
if (count($_REQUEST) > 0) {
    foreach ($_REQUEST as $key => $value) {
        if (strtoupper($key) === "SERVICE") {
            $found = true;
	}
    }
    if (!$found && ($reqParams['service'] == '' || !isset($reqParams['service']))) {
        $query->setParam("service", "wms");
	$reqParams = $query->getRequestParams();
	$reqParams['service'] = "wms";
    }
}
//check for kind of service
switch (strtolower($reqParams['service'])) {
	case 'wms':
		$wmsId = $n->getWmsIdFromOwsproxyString($query->getOwsproxyServiceId());
		$owsproxyString = $query->getOwsproxyServiceId();
		//get authentication infos if they are available in wms table! if not $auth = false
		if ($reqParams['request'] !== 'external') {
			$auth = $n->getAuthInfoOfWMS($wmsId);
		}
		if ($auth['auth_type'] == '') {
    			unset($auth);
		}
		//get info about the url exchange management
		$exchangeUrlsWmsFi = $n->getWmsExchangeUrlTag($wmsId);
	break;
		
	case 'wfs':
		$wfsId = $n->getWfsIdFromOwsproxyString($query->getOwsproxyServiceId());
		$owsproxyString = $query->getOwsproxyServiceId();
		//get authentication infos if they are available in wfs table! if not $auth = false
		if ($reqParams['request'] !== 'external') {
			$auth = $n->getAuthInfoOfWFS($wfsId);
		}
		if ($auth['auth_type'] == '') {
    			unset($auth);
		}
	break;
}

//define $userId from session information
$userId = $_SESSION['mb_user_id'];

/* ************ main workflow *********** */

switch (strtolower($reqParams['request'])) {
    case 'getcapabilities':
	switch (strtolower($reqParams['service'])) {
		case 'wfs':
			$arrayOnlineresources = checkWfsPermission($query->getOwsproxyServiceId(), false, $userId);
        		$query->setOnlineResource($arrayOnlineresources['wfs_getcapabilities']);
        		$request = $query->getRequest();  
			$request = str_replace('?&','?',$request);
			//don't allow get parameters in conjunction with post!
			if ($postData !== false) {
				$request = $arrayOnlineresources['wfs_getcapabilities'];
			}
			if (isset($auth)) {
            			getWfsCapabilities($request, $auth);
        		} else {
            			getWfsCapabilities($request);
        		}		
		break;
		case 'wms':
        		$arrayOnlineresources = checkWmsPermission($query->getOwsproxyServiceId(), $userId);
        		$query->setOnlineResource($arrayOnlineresources['wms_getcapabilities']);
        		$request = $query->getRequest();
        		if (isset($auth)) {
            			getCapabilities($request, $auth);
        		} else {
            			getCapabilities($request);
        		}
		break;
	
	}
        break;
    case 'getfeatureinfo':
        $arrayOnlineresources = checkWmsPermission($query->getOwsproxyServiceId(), $userId);
	    //define following global to use it in matchUrls function**************
	    $featureInfoUrl = $arrayOnlineresources['wms_getfeatureinfo'];
	    //*********************************************************************
        $query->setOnlineResource($arrayOnlineresources['wms_getfeatureinfo']);
        $request = $query->getRequest();
        //Ergaenzungen secured UMN Requests

        $log_id = false;
        if ($n->getWmsfiLogTag($arrayOnlineresources['wms_id']) == 1) {
            #do log to db
            #get price out of db
            $price = intval($n->getWmsfiPrice($arrayOnlineresources['wms_id']));
            $log_id = $n->logWmsGFIProxyRequest($arrayOnlineresources['wms_id'], $userId, $request,
                $price);
        }
        /*if (isset($auth)) {
            getFeatureInfo($log_id, $request, $auth);
        } else {
            getFeatureInfo($log_id, $request);
        }*/
        if(!defined("SPATIAL_SECURITY") || (defined("SPATIAL_SECURITY") && SPATIAL_SECURITY == false) || $arrayOnlineresources["wms_spatial_security"] == "f") {
        	if(isset($auth)){
        		getFeatureInfo($log_id, $request, $auth);
        	} else {
        		getFeatureInfo($log_id, $request);
        	}
        } else {
           	new mb_notice("spatial security: $request");
        				
        	$x = empty($reqParams["i"]) ? $reqParams["x"] : $reqParams["i"];
        	$y = empty($reqParams["j"]) ? $reqParams["y"] : $reqParams["j"];
        				
        	$mask = spatial_security\get_mask($reqParams, Mapbender::session()->get("mb_user_id"));
        				
        	if ($mask === null) {
        		echo "Permission denied";
        		die();
        	}
        					
        	$color = $mask->getImagePixelColor($x, $y);
        	$transparency = $color->getColorValue(Imagick::COLOR_ALPHA);
        					
        	if ($transparency < 1) {
        		echo "Permission denied";
        		die();
        	}
        						
        	if (isset($auth)) {
        		getFeatureInfo($log_id, $request, $auth);
        	} else {
        		getFeatureInfo($log_id, $request);
        	}
        								
        	$mask->destroy();
        }
        break;
    case 'getmap':
        $arrayOnlineresources = checkWmsPermission($owsproxyService, $userId);
        $query->setOnlineResource($arrayOnlineresources['wms_getmap']);
        $layers = checkLayerPermission($arrayOnlineresources['wms_id'], $reqParams['layers'], $userId);
        if ($layers === "") {
            throwE("Permission denied");
            die();
        }
        $query->setParam("layers", urldecode($layers)); //the decoding of layernames dont make problems - but not really good names will be requested also ;-)
        //Following is only needed for high quality print and is vendor specific for mapservers mapfiles!
        if (defined("OWSPROXY_SUPPORT_HQ_PRINTING") && OWSPROXY_SUPPORT_HQ_PRINTING) {
            //if url has integrated mapfile - exchange it
            //$e = new mb_notice("owsproxy/http/index.php: OWSPROXY_SUPPORT_HQ_PRINTING is set");
            if ($reqParams['mapbenderhighqualityprint'] === "true") {
                //exchange mapfiles with high quality ones
                $request = preg_replace("/\.map/", "_4.map", $query->getRequest());
            } else {
                $request = $query->getRequest();
            }
        } else {
            $request = $query->getRequest();
        }
        // Ergaenzungen secured UMN Requests
        /*//log proxy requests
        if ($n->getWmsLogTag($arrayOnlineresources['wms_id']) == 1) {#do log to db
            #get price out of db
            $price = intval($n->getWmsPrice($arrayOnlineresources['wms_id']));
            $log_id = $n->logFullWmsProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price, 0);
        }
        if (isset($auth)) {
            getImage($log_id, $request, $auth);
        } else {
            getImage($log_id, $request);
        }*/
        if(!defined("SPATIAL_SECURITY") || (defined("SPATIAL_SECURITY") && SPATIAL_SECURITY == false) || $arrayOnlineresources["wms_spatial_security"] == "f") {
        	new mb_notice("dont restrict spatially!");
        	//log proxy requests
        	if($n->getWmsLogTag($arrayOnlineresources['wms_id'])==1) {//do log to db
        		//get price out of db
        		$price=intval($n->getWmsPrice($arrayOnlineresources['wms_id']));
        		$log_id = $n->logFullWmsProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price, 0);
        	}
        	if(isset($auth)){
        	    getImage($log_id, $request, $auth);
        	} else {
        		getImage($log_id, $request);
        	}
        } else {
        	new mb_notice("wms {$arrayOnlineresources['wms_id']} is spatially secured");
        	$log_id = false;
        	if ($n->getWmsLogTag($arrayOnlineresources['wms_id']) == 1) {//log proxy requests
        		//do log to db
        		//get price out of db
        		$price = intval($n->getWmsPrice($arrayOnlineresources['wms_id']));
        		//initially create log record with number of all pixels - if image has only one color, the pixel count
        		//will be reset later in function getImage
        		$log_id = $n->logFullWmsProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price, 0, false);
        	}
        						
        	$mask = spatial_security\get_mask($reqParams, Mapbender::session()->get("mb_user_id"));
        						
        	if ($mask === null) {
        		throwImage("WMS ".$arrayOnlineresources['wms_id']." needs spatial mask!");
        		die();
        	}
        							
        	if (isset($auth)) {
        		getImage($log_id, $request, $auth, $mask);
        	} else {
        		getImage($log_id, $request, false, $mask);
        	}
        									
        	$mask->destroy();
        }

        break;
    case 'map':
        $arrayOnlineresources = checkWmsPermission($owsproxyService, $userId);
        $query->setOnlineResource($arrayOnlineresources['wms_getmap']);
        $layers = checkLayerPermission($arrayOnlineresources['wms_id'], $reqParams['layers'], $userId);
        if ($layers === "") {
            throwE("Permission denied");
            die();
        }
        $query->setParam("layers", urldecode($layers));
        $request = $query->getRequest();
        if (isset($auth)) {
            getImage(false, $url, $auth);
        } else {
            getImage(false, $url);
        }
        break;
    case 'getlegendgraphic':
        $url = getLegendUrl($query->getOwsproxyServiceId());
        if (isset($reqParams['sld']) && $reqParams['sld'] != "") {
            $url = $url . getConjunctionCharacter($url) . "SLD=" . $reqParams['sld'];
        }	
        if (isset($auth)) {
	    //$e = new mb_exception("external url: ".$url);
            getImage(false, $url, $auth);
        } else {
            getImage(false, $url);
        }
        break;
    case 'external':
        getExternalRequest($query->getOwsproxyServiceId());
        break;
    case 'getfeature':
        $e = new mb_exception("getfeature wfs");
		if (isset($reqParams['storedquery_id']) && $reqParams['storedquery_id'] !== "") {
	        	$storedQueryId = $reqParams['storedquery_id'];
	        	$arrayOnlineresources = checkWfsStoredQueryPermission($owsproxyString, $storedQueryId, $userId);
		} else {
			$arrayFeatures = array($reqParams[$typeNameParameter]);
	        	$arrayOnlineresources = checkWfsPermission($owsproxyString, $arrayFeatures, $userId);
		}
		$query->setOnlineResource($arrayOnlineresources['wfs_getfeature']);
	    $request = $query->getRequest();
	    $request = stripslashes($request);
		//TODO - what if storedquery are used ? log storedquery_id?
		if ($n->getWfsLogTag($arrayOnlineresources['wfs_id']) == 1) {
	            //get price out of db
	            $price = intval($n->getWfsPrice($arrayOnlineresources['wfs_id']));
		    if (isset($reqParams['storedquery_id']) && $reqParams['storedquery_id'] !== "") {
			$log_id = $n->logWfsProxyRequest($arrayOnlineresources['wfs_id'], $userId, $request,
	                $price, 0, $reqParams['storedquery_id']);
		    } else {
	            	$log_id = $n->logWfsProxyRequest($arrayOnlineresources['wfs_id'], $userId, $request,
	                $price, 0, $reqParams[$typeNameParameter]);
		    }
	        } else {
			$log_id = false;
		}
		//don't allow get parameters in conjunction with post!
		if ($postData !== false) {
			$request = $arrayOnlineresources['wfs_getfeature'];
		}
	    if (isset($auth)) {
	        getFeature($log_id, $request, $auth);
	    } else {
	        getFeature($log_id, $request);
	    }
        break;
    case 'describefeaturetype':
        $arrayFeatures = array($reqParams[$typeNameParameter]);
        $arrayOnlineresources = checkWfsPermission($query->getOwsproxyServiceId(), $arrayFeatures, $userId);
        $query->setOnlineResource($arrayOnlineresources['wfs_describefeaturetype']);
        $request = $query->getRequest();
        $request = stripslashes($request);
		//don't allow get parameters in conjunction with post!
		if ($postData !== false) {
			$request = $arrayOnlineresources['wfs_describefeaturetype'];
		}
        if (isset($auth)) {
            describeFeaturetype($request, $auth);
        } else {
            describeFeaturetype($request);
        }
        break;
    case 'liststoredqueries':
		if ($postData !== false) {
			$operationMethod = "Post";
		} else {
			$operationMethod = "Get";
		}
		$listStoredQueriesUrl = getWfsOperationUrl($owsproxyString, "ListStoredQueries", $operationMethod);
	        $query->setOnlineResource($listStoredQueriesUrl);
	        $request = $query->getRequest();
	        $request = stripslashes($request);
		//TODO: following is not the standard way because ows has not to handle vsp!!!
		$request = delTotalFromQuery("wfs_id",$request);
		//don't allow get parameters in conjunction with post!
		if ($postData !== false) {
			$request = $listStoredQueriesUrl;
		}
	    if (isset($auth)) {
	        listStoredQueries($request, $auth);
	    } else {
	        listStoredQueries($request);
	    }
	    break;
    case 'describestoredqueries':
		if ($postData !== false) {
			$operationMethod = "Post";
		} else {
			$operationMethod = "Get";
		}
		$describeStoredQueriesUrl = getWfsOperationUrl($owsproxyString, "DescribeStoredQueries", $operationMethod);
	    $query->setOnlineResource($describeStoredQueriesUrl);
	    $request = $query->getRequest();
	    $request = stripslashes($request);
		//TODO: following is not the standard way because ows has not to handle vsp!!!
		$request = delTotalFromQuery("wfs_id",$request);
		//don't allow get parameters in conjunction with post!
		if ($postData !== false) {
			$request = $describeStoredQueriesUrl;
		}
        if (isset($auth)) {
            describeStoredQueries($request, $auth);
        } else {
            describeStoredQueries($request);
        }
        break;
    // case wfs transaction (because of raw POST the request param is empty)
    case '':
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $arrayFeatures = getWfsFeaturesFromTransaction($HTTP_RAW_POST_DATA);
        }
        else {
            $rawpostdata = file_get_contents("php://input");
            $arrayFeatures = getWfsFeaturesFromTransaction($rawpostdata);
        }
        $arrayOnlineresources = checkWfsPermission($query->getOwsproxyServiceId(), $arrayFeatures, $userId);
        $query->setOnlineResource($arrayOnlineresources['wfs_transaction']);
        $request = $query->getRequest();
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            doTransaction($request, $HTTP_RAW_POST_DATA);
        }
        else {
            $rawpostdata = file_get_contents("php://input");
            doTransaction($request, $rawpostdata);
        }
        break;
    default:
		throwText(array("Request parameter not known to mapbender security proxy!"));
		break;
}

//why delete session here - only if it was temporary?
if (isset($tmpSession) && Mapbender::session()->storageExists($tmpSession)) {
    $e = new mb_notice("Following temporary session will be deleted: " . $tmpSession);
    Mapbender::session()->storageDestroy($tmpSession);
}

/*********************************************************/

function throwE($e)
{
    global $reqParams, $imageformats;
    if (in_array($reqParams['format'], $imageformats)) {
        throwImage($e);
    } else {
        throwText($e);
    }
}

function throwImage($e)
{
    global $width, $height;
    $image = imagecreate($width, $height);
    $transparent = ImageColorAllocate($image, 155, 155, 155);
    ImageFilledRectangle($image, 0, 0, $width, $height, $transparent);
    imagecolortransparent($image, $transparent);
    $text_color = ImageColorAllocate($image, 233, 14, 91);
    if (count($e) > 1) {
        for ($i = 0; $i < count($e); $i++) {
            $imageString = $e[$i];
            ImageString($image, 3, 5, $i * 20, $imageString, $text_color);
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
        ImageString($image, 3, 5, $i * 20, $imageString, $text_color);
    }
    responseImage($image);
}

function throwText($e)
{
    echo join(" ", $e);
}

function responseImage($im)
{
    global $reqParams;
    global $imageformats;
    if (!in_array($reqParams['format'], $imageformats)) {
        header("Content-Type: image/png");
        imagepng($im);
    } else {
        $format = $reqParams['format'];
        //$format = "image/gif";
        if ($format == 'image/png') {
            header("Content-Type: image/png");
        }
        if ($format == 'image/jpeg' || $format == 'image/jpg') {
            header("Content-Type: image/jpeg");
        }
        if ($format == 'image/gif') {
            header("Content-Type: image/gif");
        }

        if ($format == 'image/png') {
            imagepng($im);
        }
        if ($format == 'image/jpeg' || $format == 'image/jpg') {
            imagejpeg($im);
        }
        if ($format == 'image/gif') {
            imagegif($im);
        }
    }
}

function completeURL($url)
{
    global $reqParams;
    $mykeys = array_keys($reqParams);
    for ($i = 0; $i < count($mykeys); $i++) {
        if ($i > 0) {
            $url .= "&";
        }
        $url .= $mykeys[$i] . "=" . urlencode($reqParams[$mykeys[$i]]);
    }
    return $url;
}

/**
 * fetch and returns an image to client
 * 
 * @param string the original url of the image to send
 */
function getImage($log_id, $or, $auth = false, $mask = false)
{
    global $reqParams;
    global $imageformats;
    if (!in_array($reqParams['format'], $imageformats)) {
        $header = "Content-Type: image/png";
    } else {
        $header = "Content-Type: ".$reqParams['format'];
    }
    //log the image_requests to database
    //log the following to table mb_proxy_log
    //timestamp,user_id,getmaprequest,amount pixel,price - but do this only for wms to log - therefor first get log tag out of wms!
    //
    //
    getDocumentContent($log_id, $or, $header, $auth, $mask);
}

/**
 * fetchs and returns the content of the FeatureInfo Response
 * 
 * @param string the url of the FeatureInfoRequest
 * @return string the content of the FeatureInfo document
 */
function getFeatureInfo($log_id, $url, $auth = false)
{
    global $reqParams;
    if ($auth !== false) { //new for HTTP Authentication
        getDocumentContent($log_id, $url, false, $auth);
    } else {
        getDocumentContent($log_id, $url);
    }
}

/**
 * fetchs and returns the content of WFS GetFeature response
 * 
 * @param string the url of the GetFeature request
 * @return echo the content of the GetFeature document
 */
function getFeature($log_id, $url, $auth = false)
{
    global $reqParams;
    $content = getDocumentContent($log_id, $url, "Content-Type: application/xml", $auth);
}

/**
 * fetchs and returns the content of WFS DescribeFeaturetype response
 * 
 * @param string the url of the DescribeFeaturetype request
 * @return echo the content of the DescribeFeaturetype document
 */
function describeFeaturetype($url, $auth = false)
{
    global $reqParams;
    $content = getDocumentContent(false, $url, "Content-Type: application/xml", $auth);
}

/**
 * fetchs and returns the content of WFS 2.0+ ListStoredQueries response
 * 
 * @param string the url of the ListStoredQueries request
 * @return echo the content of the ListStoredQueries document
 */
function listStoredQueries($url, $auth = false)
{
    global $reqParams;
    $content = getDocumentContent(false, $url, "Content-Type: application/xml", $auth);
}

/**
 * fetchs and returns the content of WFS 2.0+ DescribeStoredQueries response
 * 
 * @param string the url of the DescribeStoredQueries request
 * @return echo the content of the DescribeStoredQueries document
 */
function describeStoredQueries($url, $auth = false)
{
    global $reqParams;
    $content = getDocumentContent(false, $url, "Content-Type: application/xml", $auth);
}

/**
 * simulates a post request to host
 * 
 * @param string host to send the request to
 * @param string port of host to send the request to
 * @param string method to send data (should be "POST")
 * @param string path on host
 * @param string data to send to host
 * @return string hosts response
 */
function sendToHost($host, $port, $method, $path, $data)
{
    $buf = '';
    if (empty($method))
        $method = 'POST';
    $method = mb_strtoupper($method);
    $fp = fsockopen($host, $port);
    fputs($fp, "$method $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Content-type: application/xml\r\n");
    fputs($fp, "Content-length: " . strlen($data) . "\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    if ($method == 'POST')
        fputs($fp, $data);
    while (!feof($fp)) $buf .= fgets($fp, 4096);
    fclose($fp);
    return $buf;
}

/**
 * get wfs featurenames that are touched by a tansaction request defined in XML $data
 * 
 * @param string XML that contains the tansaction request
 * @return array array of touched feature names
 */
function getWfsFeaturesFromTransaction($data)
{
    new mb_notice("owsproxy.getWfsFeaturesFromTransaction.data: " . $data);
    if (!$data || $data == "") {
        return false;
    }
    $features = array();
    $values = NULL;
    $tags = NULL;
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $data, $values, $tags);

    $code = xml_get_error_code($parser);
    if ($code) {
        $line = xml_get_current_line_number($parser);
        $col = xml_get_current_column_number($parser);
        $mb_notice = new mb_notice("OWSPROXY invalid Tansaction XML: " . xml_error_string($code) . " in line " . $line . " at character " . $col);
        die();
    }
    xml_parser_free($parser);

    $insert = false;
    $insertlevel = 0;
    foreach ($values as $element) {
        //features touched by insert
        if (strtoupper($element['tag']) == "WFS:INSERT" && $element['type'] == "open") {
            $insert = true;
            $insertlevel = $element[level];
        }
        if ($insert && $element[level] == $insertlevel + 1 && $element['type'] == "open") {
            array_push($features, $element['tag']);
        }
        if (strtoupper($element['tag']) == "WFS:INSERT" && $element['type'] == "close") {
            $insert = false;
        }
        //updated features - TODO - fix for wfs 2.0+ - typenames instead of typename!
        if (strtoupper($element['tag']) == "WFS:UPDATE" && $element['type'] == "open") {
            array_push($features, $element['attributes']["typeName"]);
        }
        //deleted features
        if (strtoupper($element['tag']) == "WFS:DELETE" && $element['type'] == "open") {

            array_push($features, $element['attributes']["typeName"]);
        }
    }
    return $features;
}

/**
 * sends the data of WFS Transaction and echos the response
 * 
 *  @param string url to send the WFS Transaction to
 *  @param string WFS Transaction data
 */
function doTransaction($url, $data)
{
    $arURL = parse_url($url);
    $host = $arURL["host"];
    $port = $arURL["port"];
    if ($port == '')
        $port = 80;

    $path = $arURL["path"];
    $method = "POST";
    $result = sendToHost($host, $port, $method, html_entity_decode($path), $data);

    //delete header from result
    $result = mb_eregi_replace("^[^<]*", "", $result);
    $result = mb_eregi_replace("[^>]*$", "", $result);

    echo $result;
}

function matchUrls($content)
{
	global $urlsToExclude;
	global $featureInfoUrl;
	global $exchangeUrlsWmsFi;
	//check if isset owsproxyUrls else create
	$owsproxyUrls = Mapbender::session()->get('owsproxyUrls');
	if ($owsproxyUrls == false) {
		$e = new mb_notice("owsproxyUrls does not exist - create it!");
		$owsproxyUrls = array();
		$owsproxyUrls['id'] = array();
		$owsproxyUrls['url'] = array();
		Mapbender::session()->set('owsproxyUrls',$owsproxyUrls);
	}
	$pattern = "/[\"|\'](https*:\/\/[^\"|^\']*)[\"|\']/";
	preg_match_all ( $pattern, $content, $matches );
	for($i = 0; $i < count ( $matches [1] ); $i ++) {
		$req = $matches [1] [$i];
		$notice = new mb_notice ( "owsproxy found URL " . $i . ": " . $req );
		// only register and exchange urls, that should not be excluded!
		if (in_array ( $req, $urlsToExclude )) {
			continue;
		}
		// Generaly urls should not be exchanged. It must be controlled, if it directly come from the same server as the fi request. Those urls have always to be exchanged!
		if ($exchangeUrlsWmsFi === "0") {
			// get servername for featureinfo request
			$url_array = parse_url ( $featureInfoUrl );
			$servername = $url_array ['scheme'] . "://" . $url_array ['host'];
			// test if url came from same server - if not use original urls!
			// switch for localhost/127.0.0.1 - they are the same!
			if ($url_array ['host'] == 'localhost' || $url_array ['host'] == '127.0.0.1') {
				if (strpos ( $req, $url_array ['scheme'] . "://" . "localhost" ) !== 0 && strpos ( $req, $url_array ['scheme'] . "://" . "127.0.0.1" ) !== 0) {
					continue;
				}
			} else {
				if (strpos ( $req, $servername ) !== 0) {
					continue;
				}
			}
		}
		$id = registerURL ( $req );
		$extReq = setExternalRequest ( $id );
		$notice = new mb_notice ( "MD5 URL " . $id . " - external link: " . $extReq );
		$content = str_replace ( $req, $extReq, $content );
	}
	return $content;
}

function setExternalRequest($id)
{
    global $reqParams, $query;
//	$extReq = "http://".$_SESSION['HTTP_HOST'] ."/owsproxy/". $reqParams['sid'] ."/".$id."?request=external";
    $extReq = OWSPROXY . "/" . $reqParams['sid'] . "/" . $id . "?request=external";
    return $extReq;
}

function getExternalRequest($id)
{
	//get owsproxyUrls from session
	$owsproxyUrls = Mapbender::session()->get('owsproxyUrls');
	for ($i = 0; $i < count($owsproxyUrls["url"]); $i++) {
        	if ($id == $owsproxyUrls["id"][$i]) {
            		$cUrl = $owsproxyUrls["url"][$i];
            		$query_string = removeOWSGetParams($_SERVER["QUERY_STRING"]);
            		if ($query_string != '') {
                		$cUrl .= getConjunctionCharacter($cUrl) . $query_string;
            		}
            		$metainfo = get_headers($cUrl, 1);
            		// just for the stupid InternetExplorer
            		header('Pragma: private');
            		header('Cache-control: private, must-revalidate');
           		header("Content-Type: " . $metainfo['Content-Type']);
            		//$content = getDocumentContent(false, $cUrl, headers_list());
			$content = getDocumentContent(false, $cUrl, $metainfo);
            		#$content = matchUrls($content);			
        	} else {
			$e = new mb_exception("owsproxy/http/index.php: No key found for this URL in session!");
		}
    	}
}

function removeOWSGetParams($query_string)
{
    $r = preg_replace("/.*request=external&/", "", $query_string);
    return "";
}

function getConjunctionCharacter($url)
{
    if (strpos($url, "?")) {
        if (strpos($url, "?") == strlen($url)) {
            $cchar = "";
        } else if (strpos($url, "&") == strlen($url)) {
            $cchar = "";
        } else {
            $cchar = "&";
        }
    }
    if (strpos($url, "?") === false) {
        $cchar = "?";
    }
    return $cchar;
}

function registerUrl($url)
{
	//get owsproxy urls from session
	//
	$owsproxyUrls = Mapbender::session()->get('owsproxyUrls');
    	if (!in_array($url, $owsproxyUrls["url"])) {
        	$id = md5($url);
        	array_push($owsproxyUrls["url"], $url);
        	array_push($owsproxyUrls["id"], $id);
    	} else {
       		for ($i = 0; $i < count($owsproxyUrls["url"]); $i++) {
            		if ($url == $owsproxyUrls["url"][$i]) {
                		$id = $owsproxyUrls["id"][$i];
            		}
        	}
    	}
	Mapbender::session()->set('owsproxyUrls',$owsproxyUrls);
    	return $id;
}

function getCapabilities($url)
{
    global $arrayOnlineresources;
    global $sid, $serviceId;
    $t = array(htmlentities($arrayOnlineresources["wms_getcapabilities"]), htmlentities($arrayOnlineresources["wms_getmap"]),
        htmlentities($arrayOnlineresources["wms_getfeatureinfo"]));
    $new = OWSPROXY . "/" . $sid . "/" . $serviceId . "?";
    $r = str_replace($t, $new, $arrayOnlineresources["wms_getcapabilities_doc"]);
    //delete trailing amp; 's
    $r = str_replace('amp;', '', $r);
    header("Content-Type: application/xml");
    echo $r;
}

function getWfsCapabilities($request, $auth = false)
{
    global $arrayOnlineresources, $postData, $query;
    global $sid, $serviceId;
    $t = array(htmlentities($arrayOnlineresources["wfs_getcapabilities"]), htmlentities($arrayOnlineresources["wfs_getmap"]),
        htmlentities($arrayOnlineresources["wfs_getfeatureinfo"]));
    $new = OWSPROXY . "/" . $sid . "/" . $serviceId . "?";
	
    if ($postData == false) {
	//check POST/GET
	if ($query->reqMethod !== 'POST') {
            if ($auth) { //new for HTTP Authentication
                $d = new connector($request, $auth);
            } else {
                $d = new connector($request);
            }
	} else {
	    $d = new connector();
	    $d->set('httpType','POST');
	    //$d->set('curlSendCustomHeaders',true);
	    $d->set('httpPostData', $query->getPostQueryString());//as array
	    //$d->set('httpContentType','text/xml');
	    //TODO maybe delete some params from querystring which are already in post array
            if ($auth) { //new for HTTP Authentication
                $d->load($request, $auth);
            } else {
                $d->load($request);
            }
	}
        $wfsCaps = $d->file;
    } else {
        //$e = new mb_exception("owsproxy/index.php: postData will be send: ".$postData);
        $postInterfaceObject = new connector();
        $postInterfaceObject->set('httpType','POST');
        $postInterfaceObject->set('curlSendCustomHeaders',true);
        $postInterfaceObject->set('httpPostData', $postData);
        $postInterfaceObject->set('httpContentType','text/xml');
        if ($auth !== false) { //new for HTTP Authentication
            $postInterfaceObject->load($request, $auth);
        } else {
            $postInterfaceObject->load($request);
        }
        $wfsCaps = $postInterfaceObject->file;
    }
    $r = str_replace($t, $new, $wfsCaps);
    //delete trailing amp; 's
    $r = str_replace('amp;', '', $r);
    header("Content-Type: application/xml");
    echo $r;
}

/**
 * gets the original url of the requested legend graphic
 * 
 * @param string owsproxy md5
 * @return string url to legend graphic
 */
function getLegendUrl($wms)
{
    global $reqParams;
    //get wms id
    $sql = "SELECT * FROM wms WHERE wms_owsproxy = $1";
    $v = array($wms);
    $t = array("s");
    $res = db_prep_query($sql, $v, $t);
    if ($row = db_fetch_array($res)) {
        $wmsid = $row["wms_id"];
        $getLegendUrl = $row["wms_getlegendurl"];
	//$e = new mb_exception("found : ".$getLegendUrl); //empty
    } else {
        throwE(array("No wms data available."));
        die();
    }
    //get the url
    $sql = "SELECT layer_style.legendurl ";
    $sql .= "FROM layer_style JOIN layer ";
    $sql .= "ON layer_style.fkey_layer_id = layer.layer_id ";
    $sql .= "WHERE layer.layer_name = $2 AND layer.fkey_wms_id = $1 ";
    $sql .= "AND layer_style.name = $3 AND layer_style.legendurlformat = $4";
    if ($reqParams['style'] == '') {
        $style = 'default';
    } else {
        $style = $reqParams['style'];
    }
    //$v = array($wmsid, $reqParams['layer'], $reqParams['style'], $reqParams['format']);
    $v = array($wmsid, $reqParams['layer'], $style, $reqParams['format']);
    $t = array("i", "s", "s", "s");
    $res = db_prep_query($sql, $v, $t);
    if ($row = db_fetch_array($res)) {
        if (strpos($row["legendurl"], 'http') !== 0) {
            return $getLegendUrl . $row["legendurl"];
        }
        return $row["legendurl"];
    } else {
        throwE(array("No legend available."));
        die();
    }
}

/**
 * validated access permission on requested wms
 * 
 * @param string OWSPROXY md5
 * @return array array with detailed information about requested wms
 */
function checkWmsPermission($wmsOws, $userId)
{
    global $con, $n;
    $myguis = $n->getGuisByPermission($userId, true);
    $mywms = $n->getWmsByOwnGuis($myguis);
    $sql = "SELECT * FROM wms WHERE wms_owsproxy = $1";
    $v = array($wmsOws);
    $t = array("s");
    $res = db_prep_query($sql, $v, $t);
    $service = array();
    if ($row = db_fetch_array($res)) {
        $service["wms_id"] = $row["wms_id"];
        $service["wms_getcapabilities"] = $row["wms_getcapabilities"];
        $service["wms_getmap"] = $row["wms_getmap"];
        $service["wms_getfeatureinfo"] = $row["wms_getfeatureinfo"];
        $service["wms_getcapabilities_doc"] = $row["wms_getcapabilities_doc"];
        $service["wms_spatial_security"] = $row["wms_spatial_security"];
    }

    if (!$row || count($mywms) == 0) {
        throwE(array("No wms data available."));
        die();
    }

    if (!in_array($service["wms_id"], $mywms)) {
        throwE(array("Permission denied.", " -> " . $service["wms_id"], implode(",", $mywms)));
        die();
    }
    return $service;
}

/**
 * validates the access permission by getting the appropriate wfs_conf
 * to each feature requested and check the wfs_conf permission
 * 
 * @param string owsproxy md5
 * @param array array of requested featuretype names
 * @return array array with detailed information on reqested wfs
 */
function checkWfsPermission($wfsOws, $features, $userId)
{
    global $con, $n;
    $myconfs = $n->getWfsConfByPermission($userId);
    if ($features !== false) {
	//check if we know the features requested
	if (count($features) == 0) {
	        throwE(array("No wfs_feature data available."));
        	die();
    	}	
    }

    //get wfs
    $sql = "SELECT * FROM wfs WHERE wfs_owsproxy = $1";
    $v = array($wfsOws);
    $t = array("s");
    $res = db_prep_query($sql, $v, $t);
    $service = array();
    if ($row = db_fetch_array($res)) {
        $service["wfs_id"] = $row["wfs_id"];
        $service["wfs_getcapabilities"] = $row["wfs_getcapabilities"];
        $service["wfs_getfeature"] = $row["wfs_getfeature"];
        $service["wfs_describefeaturetype"] = $row["wfs_describefeaturetype"];
        $service["wfs_transaction"] = $row["wfs_transaction"];
        $service["wfs_getcapabilities_doc"] = $row["wfs_getcapabilities_doc"];
    } else {
        throwE(array("No wfs data available."));
        die();
    }

    foreach ($features as $feature) {
        //get appropriate wfs_conf
        $sql = "SELECT wfs_conf.wfs_conf_id FROM wfs_conf ";
        $sql.= "JOIN wfs_featuretype ";
        $sql.= "ON wfs_featuretype.featuretype_id = wfs_conf.fkey_featuretype_id ";
        $sql.= "WHERE wfs_featuretype.featuretype_name = $2 ";
        $sql.= "AND wfs_featuretype.fkey_wfs_id = $1";
        $v = array($service["wfs_id"], $feature);
        $t = array("i", "s");
        $res = db_prep_query($sql, $v, $t);
        if (!($row = db_fetch_array($res))) {
            $notice = new mb_exception("Permissioncheck failed no wfs conf for wfs " . $service["wfs_id"] . " with featuretype " . $feature);
            throwE(array("No wfs_conf data for featuretype " . $feature));
            die();
        }
        $conf_id = $row["wfs_conf_id"];

        //check permission
        if (!in_array($conf_id, $myconfs)) {
            $notice = new mb_exception("Permissioncheck failed:" . $conf_id . " not in " . implode(",", $myconfs));
            throwE(array("Permission denied.", " -> " . $conf_id, implode(",", $myconfs)));
            die();
        }
    }

    return $service;
}

/**
 * validates the access permission by getting the appropriate wfs_conf
 * to each feature requested and check the wfs_conf permission
 * 
 * @param string owsproxy md5
 * @param array array of requested featuretype names
 * @return array array with detailed information on reqested wfs
 */
function checkWfsStoredQueryPermission($wfsOws, $storedQueryId, $userId)
{
    global $con, $n;
    $myconfs = $n->getWfsConfByPermission($userId);
    if ($storedQueryId === false) {
	throwE(array("No storedquery_id data available."));
        die();
    }
    //get wfs
    $sql = "SELECT * FROM wfs WHERE wfs_owsproxy = $1";
    $v = array($wfsOws);
    $t = array("s");
    $res = db_prep_query($sql, $v, $t);
    $service = array();
    if ($row = db_fetch_array($res)) {
        $service["wfs_id"] = $row["wfs_id"];
        $service["wfs_getcapabilities"] = $row["wfs_getcapabilities"];
        $service["wfs_getfeature"] = $row["wfs_getfeature"];
        $service["wfs_describefeaturetype"] = $row["wfs_describefeaturetype"];
        $service["wfs_transaction"] = $row["wfs_transaction"];
        $service["wfs_getcapabilities_doc"] = $row["wfs_getcapabilities_doc"];
    } else {
        throwE(array("No wfs data available."));
        die();
    }
    //get appropriate wfs_conf
    $sql = "SELECT wfs_conf.wfs_conf_id FROM wfs_conf WHERE fkey_wfs_id = $1 AND stored_query_id = $2";
    $v = array($service["wfs_id"], $storedQueryId);
    $t = array("i", "s");
    $res = db_prep_query($sql, $v, $t);
    if (!($row = db_fetch_array($res))) {
    	$notice = new mb_exception("Permissioncheck failed no wfs conf for wfs " . $service["wfs_id"] . " with storedquery_id " . $storedQueryId);
	throwE(array("No wfs_conf data for storedquery_id " . $storedQueryId));
	die();
    }
    $conf_id = $row["wfs_conf_id"];
    //check permission
    if (!in_array($conf_id, $myconfs)) {
        $notice = new mb_exception("Permissioncheck failed:" . $conf_id . " not in " . implode(",", $myconfs));
        throwE(array("Permission denied.", " -> " . $conf_id, implode(",", $myconfs)));
        die();
    }
    return $service;
}

function getWfsOperationUrl($wfsOws, $operationName, $operationMethod) {
	$timeBegin = microtime();
	$sql = "SELECT wfs_getcapabilities_doc FROM wfs WHERE  wfs_owsproxy = $1";
	$v = array($wfsOws);
    	$t = array("s");
    	$res = db_prep_query($sql, $v, $t);
    	if ($row = db_fetch_array($res)) {
        	$capXml = $row["wfs_getcapabilities_doc"];
    	} else {
        	throwE(array("No wfs data available."));
        	die();
    	}
	//parse capabilities
	$wfs20Cap = new DOMDocument();
	try {
		if (!$wfs20Cap->loadXML($capXml)) {
        		throw new Exception("Cannot parse WFS 2.0 Capabilities!");
        	}
	}
	catch (Exception $e) {
    		$e = new mb_exception($e->getMessage());
	}	
	if ($wfs20Cap !== false) {
		$xpath = new DOMXPath($wfs20Cap);
		$rootNamespace = $wfs20Cap->lookupNamespaceUri($wfs20Cap->namespaceURI);
		$e = new mb_notice("rootns: ".$rootNamespace);
		$xpath->registerNamespace('defaultns', $rootNamespace); 
		$xpath->registerNamespace("ows", "http://www.opengis.net/ows");
		$xpath->registerNamespace("gml", "http://www.opengis.net/gml");
		$xpath->registerNamespace("ogc", "http://www.opengis.net/ogc");
		$xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
		$xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
		$urlArray = DOMNodeListObjectValuesToArray($xpath->query('/defaultns:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name=\''.$operationName.'\']/ows:DCP/ows:HTTP/ows:'.$operationMethod.'/@xlink:href'));
		//check for type
		if (is_array($urlArray)) {
			$e = new mb_notice("http_auth/http/index.php: url for operation ".$operationName." : ".$urlArray[0]);
			$timeEnd = microtime();
			$e = new mb_notice("http_auth/http/index.php: time to get url from capabilities: ".($timeEnd-$timeBegin)*1000);
			return $urlArray[0];
		} else {
			$e = new mb_exception("http_auth/http/index.php: no url for operation ".$operationName." and method ".$operationMethod." found in Capabilities. Function returned: ".json_encode($urlArray[0]));
			return false;
		} 
	} else {
		$e = new mb_exception("http_auth/http/index.php: Problem while trying to do xpath on capabilities document!");
		return false;
	}
}

function DOMNodeListObjectValuesToArray($domNodeList) {
	$iterator = 0;
	$array = array();
	foreach ($domNodeList as $item) {
    		$array[$iterator] = $item->nodeValue; // this is a DOMNode instance
    		// you might want to have the textContent of them like this
    		$iterator++;
	}
	return $array;
}


function checkLayerPermission($wms_id, $l, $userId)
{
    global $n, $owsproxyService;
    $myl = explode(",", $l);
    $r = array();
    foreach ($myl as $mysl) {
        if ($n->getLayerPermission($wms_id, $mysl, $userId) === true) {
            array_push($r, $mysl);
        }
    }
    $ret = implode(",", $r);
    return $ret;
}

function getDocumentContent($log_id, $url, $header = false, $auth = false, $mask = null)
{
    global $reqParams, $n, $postData, $query;
    //debug
    $startTime = microtime();
    if ($postData == false) {
        $d = new connector();
        if (strtoupper($reqParams["resulttype"]) == "HITS") {
            $d->set("timeOut", "200");
        }
	    //check POST/GET
	    if ($query->reqMethod !== 'POST') {
            if ($auth) { //new for HTTP Authentication
                #$d = new connector($url, $auth);
                $d->load($url, $auth);
            } else {
                #$d = new connector($url);
                $d->load($url);
            }
	    } else {
	        #$d = new connector();
	        $d->set('httpType','POST');
	        //$d->set('curlSendCustomHeaders',true);
	        $d->set('httpPostData', $query->getPostQueryString());//as array
	        //$d->set('httpContentType','text/xml');
	        //TODO maybe delete some params from querystring which are already in post array
            if ($auth) { //new for HTTP Authentication
                $d->load($url, $auth);
            } else {
                $d->load($url);
            }
	    }
	    $content = $d->file;
	    $httpCode = $d->httpCode;
    } else {
        $postInterfaceObject = new connector();
        $postInterfaceObject->set('httpType','POST');
        $postInterfaceObject->set('curlSendCustomHeaders',true);
        $postInterfaceObject->set('httpPostData', $postData);
        $postInterfaceObject->set('httpContentType','text/xml');
        if ($auth !== false) { //new for HTTP Authentication
            $postInterfaceObject->load($url, $auth);
        } else {
            $postInterfaceObject->load($url);
        }         
        $content = $postInterfaceObject->file;
        $httpCode = $postInterfaceObject->httpCode;
    }
    $endTime = microtime();
    //$e = new mb_exception("owsproxy/http/index.php: Time for getting remote resource: ".(string)($endTime - $startTime));
    if (strtoupper($reqParams["request"]) == "GETMAP") { // getmap
        $pattern_exc = '~EXCEPTION~i';
        preg_match($pattern_exc, $content, $exception);
        if (!$content) {
            if ($log_id != null && is_integer($log_id)) {
                $n->updateWmsLog(0, "Mb2OWSPROXY - unable to load: " . $url, "text/plain", $log_id);
            }
            header("Content-Type: text/plain");
            echo "Mb2OWSPROXY - unable to load external request - for further information please see logfile";
        } else if (count($exception) > 0) {
            if ($log_id != null && is_integer($log_id)) {
                $n->updateWmsLog(0, $content, $reqParams["exceptions"], $log_id);
            }
            header("Content-Type: " . $reqParams["exceptions"]);
            echo $content;
        } else {
            $source = new Imagick();
            //if tiff (geotiff) was requested - read header to temporary file to add it later on
            if (in_array(strtoupper($reqParams["format"]), array("TIFF", "TIF", "IMAGE/TIF", "IMAGE/TIFF"))) {
                //$e = new mb_notice("tiff format requested");
                //Added 2022-09-21 to allow secured access to masked geotiff images via wms
                //write image to tmp folder
                //extract header
                //read header
                //Doc: https://github.com/OSGeo/libgeotiff
                if (defined("ABSOLUTE_TMPDIR") && ABSOLUTE_TMPDIR != "" && defined("LIBGEOTIFF") && LIBGEOTIFF == true) {
                    $uuidGeoTiff = new Uuid();
                    $tmpGeoTiffFilename = ABSOLUTE_TMPDIR . '/' .$uuidGeoTiff . '.tif';
                    $tmpGeoTiffHeaderFilename = ABSOLUTE_TMPDIR . '/' .$uuidGeoTiff . '_header.txt';
                    if ($h = fopen($tmpGeoTiffFilename, "wb")) {
                        if (!fwrite($h, $content)) {
                            $e = new mb_exception("owsproxy/http/index.php: Could not write GeoTIFF cache to " . $tmpGeoTiffFilename);
                        } else {
                            exec('listgeo ' . $tmpGeoTiffFilename . ' > ' . $tmpGeoTiffHeaderFilename, $output);
                        }
                        fclose($h);
                    } else {
                        $e = new mb_exception("owsproxy/http/index.php: Could not open " . $tmpGeoTiffFilename);
                    }
                } else {
                    $e = new mb_exception("owsproxy/http/index.php: Could not cache TIFF image to extract GeoTIFF header, cause ABSOLUTE_TMPDIR is not defined in mapbender.conf and/or libgeotiff is not available!");
                }
            }
            $source->readImageBlob($content);
            /*header("Content-Type: " . $reqParams['format']);
            echo $content;*/
            if ($mask !== null && $mask != false) {
            	new mb_notice("spatial security: applying mask");
            	$source->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);
            }
            $numColors = $source->getImageColors();
            if ($log_id != null && is_integer($log_id)) {
            	$n->updateWmsLog($numColors <= 1 ? -1 : 1, null, null, $log_id);
            }
            header("Content-Type: " . $reqParams['format']);
            if (in_array(strtoupper($reqParams["format"]), array("TIFF", "TIF", "IMAGE/TIF", "IMAGE/TIFF"))) {
                //Added 2022-09-21 to allow secured access to masked geotiff images via wms
                //write tif to tmp folder
                //add header from above
                //read image with header
                //https://github.com/OSGeo/libgeotiff
                if (defined("ABSOLUTE_TMPDIR") && ABSOLUTE_TMPDIR != "" && defined("LIBGEOTIFF") && LIBGEOTIFF == true) {
                    //overwrite old geotiff
                    if ($h = fopen($tmpGeoTiffFilename, "wb")) {
                        $newImage = $source->getImageBlob();
                        if (!fwrite($h, $newImage)) {
                            $e = new mb_exception("owsproxy/http/index.php: Could not write GeoTIFF cache to " . $tmpGeoTiffFilename);
                        } else {
                            $newImageWritten = true;
                            $e = new mb_notice("owsproxy/http/index.php: geotiff written to cache!");
                        }
                        fclose($h);
                        if ($newImageWritten == true) {
                            exec('geotifcp -g ' . $tmpGeoTiffHeaderFilename . ' ' . $tmpGeoTiffFilename . ' ' . $tmpGeoTiffFilename . ".new.tif", $output);
                            //read image from file and simple echo it
                            echo file_get_contents($tmpGeoTiffFilename. ".new.tif");
                            unlink($tmpGeoTiffFilename);
                            unlink($tmpGeoTiffFilename. ".new.tif");
                            unlink($tmpGeoTiffHeaderFilename);
                        } else {
                            unlink($tmpGeoTiffFilename);
                            unlink($tmpGeoTiffHeaderFilename);
                        }
                    } else {
                        $e = new mb_exception("owsproxy/http/index.php: Could not open cached tiff file!");
                    }
                } else {
                    $e = new mb_exception("owsproxy/http/index.php: Could not cache new TIFF image to add GeoTIFF header, cause ABSOLUTE_TMPDIR is not defined in mapbender.conf and/or libgeotiff is not available!");
                }
            } else {
                //default give back image without special headers
                echo $source->getImageBlob();
            }
        }
        return true;
    } else if (strtoupper($reqParams["request"]) == "GETFEATUREINFO") { // getmap
//		header("Content-Type: ".$reqParams['info_format']);
//		$content = matchUrls($content);
//		echo $content;
        $pattern_exc = '~EXCEPTION~i';
        preg_match($pattern_exc, $content, $exception);
        if (!$content) {
            if ($log_id != null) {
                $n->updateWmsFiLog("Mb2OWSPROXY - unable to load: " . $url, "text/plain", $log_id);
            }
            header("Content-Type: text/plain");
            echo "Mb2OWSPROXY - unable to load external request - for further information please see logfile";
        } else if (count($exception) > 0) {
            if ($log_id != null) {
                $n->updateWmsFiLog($content, "application/xml", $log_id);
            }
            header("Content-Type: application/xml");
            echo $content;
        } else {
            header("Content-Type: " . $reqParams['info_format']);
            if ($log_id != null) {
                $n->updateWmsFiLog(null, null, $log_id);
            }
            $content = matchUrls($content);
            echo $content;
        }
        return true;
    } elseif (strtoupper($reqParams["request"]) == "GETFEATURE") {
        $startTime = microtime();
        //parse featureCollection and get number of objects
        //only possible if features should be logged!
        if ($log_id !== false) {
            $e = new mb_notice("http_auth/http/index.php: GetFeature invoked - logging activated!");
            $logWithOgr = true;
            //another approach to count objects - use ogr from cli - features are temporary stored!
            if ($logWithOgr == false) {
                libxml_use_internal_errors(true);
                try {
                    $featureCollectionXml = simplexml_load_string($content);
                    if ($featureCollectionXml === false) {
                        foreach (libxml_get_errors() as $error) {
                            $err = new mb_exception("owsproxy/http/index.php:" . $error->message);
                        }
                        throw new Exception("owsproxy/http/index.php:" . 'Cannot parse featureCollection XML!');
                        //TODO give error message
                    }
                } catch (Exception $e) {
                    $err = new mb_exception("owsproxy/index.php:" . $e->getMessage());
                    //TODO give error message
                }
                if ($featureCollectionXml !== false) {
                    $featureCollectionXml->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
                    if ($reqParams["version"] == '2.0.0' || $reqParams["version"] == '2.0.2') {
                        $featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs/2.0");
                    } else {
                        $featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs");
                    }
                    $featureCollectionXml->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
                    $featureCollectionXml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
                    $featureCollectionXml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
                    $featureCollectionXml->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
                    $featureCollectionXml->registerXPathNamespace("default", "");
                    preg_match('@version=(?P<version>\d\.\d\.\d)&@i', strtolower($url), $version);
                    if (!$reqParams['version']) {
                        $e = new mb_notice("owsproxy/http/index.php: No version for wfs request given in reqParams!");
                    }
                    switch ($reqParams['version']) {
                        case "1.0.0":
                            //get # of features from counting features
                            $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/gml:featureMember');
                            $numberOfFeatures = count($numberOfFeatures);
                            break;
                        case "1.1.0":
                            //get # of features from counting features
                            $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/gml:featureMember');
                            $numberOfFeatures = count($numberOfFeatures);
                            break;
                            //for wfs 2.0 - don't count features
                        default:
                            //get # of features from attribut
                            $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/@numberReturned');
                            $numberOfFeatures = $numberOfFeatures[0];
                            break;
                    }
                    $endTime = microtime();
                    $e = new mb_notice("owsproxy/http/index.php: " . $numberOfFeatures . " delivered features from wfs.");
                    //TODO: enhance error management
                    if ($log_id !== false) {
                        $n->updateWfsLog(1, '', '', $numberOfFeatures, $log_id);
                    }
                    $e = new mb_notice("owsproxy/http/index.php: Time for counting: " . (string)($endTime - $startTime));
                    $e = new mb_notice("owsproxy/http/index.php: Memory used for XML String: " . getVariableUsage($content) / 1000000 . "MB");
                    if ($header != false) {
                        header($header);
                    }
                    echo $content;
                } else {
                    //TODO: no feature xml found ! - give back a good error message
                    if ($header != false) {
                        header($header);
                        $e = new mb_exception("owsproxy/http/index.php: WFS dows not give back GML - parsing was not successfully!");
                    }
                    echo $content;
                }
            } else {
                //count features with ogrinfo
                /*
                 * new 2022-08-04
                 */
                $ogr = new Ogr();
                //$ogr->logRuntime = true;
                if ($reqParams['version'] == '2.0.0' || $reqParams['version'] == '2.0.2') {
                    $typeParameterName = "typenames";
                } else {
                    $typeParameterName = "typename";
                }
                $ogr->logRuntime = true;
                //$e = new mb_exception(json_encode($reqParams));
                //$e = new mb_exception("*".urldecode($reqParams['outputformat'])."*");
                if ($reqParams['resulttype'] == 'hits' || $reqParams['resulttype'] == 'HITS') {
                    header("Content-Type: application/xml");
                    echo $content;
                    die();
                } 
                if ($log_id !== false) {
                    //test for exception and return error for transparency
                    if (strpos($content, ":ExceptionReport") !== false){
                        header("Content-Type: application/xml"); //default to gml
                        echo $content;
                        die();
                    }
                    if ($httpCode == "500"){
                        header("Content-Type: text/html"); //default to gml
                        echo $content;
                        die();
                    }
                    $numberOfObjects = $ogr->ogrCountFeatures($content, urldecode($reqParams['outputformat']), $reqParams[$typeParameterName], true);
                    if ($numberOfObjects == false) {
                        $n->updateWfsLog(0, 'Could not count objects for requested format: ' . urldecode($reqParams['outputformat']), '', 0, $log_id);
                        header("Content-Type: application/json");
                        echo '{"error": true, "message": "Objects should be counted, but requested format could not be parsed by proxy. Please use another format, e.g. GML, Shape or GeoJSON!"}';
                        die();
                    } else {
                        $n->updateWfsLog(1, '', '', $numberOfObjects, $log_id);
                    }
                }
                if ($reqParams['outputformat'] != false) {
                    header("Content-Type: " . $reqParams['outputformat']);
                    switch ($reqParams['outputformat']) {
                        case "application/zip":
                            $dateTime = date("Y-m-d");
                            header("Content-Disposition: attachment; filename=\"" . $dateTime . "_mapbender_featuretype_" . $reqParams['typename'] . ".zip\"");
                            break;
                    }
                } else {
                    header("Content-Type: application/xml"); //default to gml
                }
                
                echo $content;
            }
        } else {
            //no logging of features defined
            if ($header != false) {
                header($header);
            } else {
                //define header as requested outputFormat of wfs - only a workaround!
                if ($reqParams['outputformat'] != false) {
                    header("Content-Type: " . $reqParams['outputformat']);
                    switch ($reqParams['outputformat']) {
                        case "application/zip":
                            $dateTime = date("Y-m-d");
                            header("Content-Disposition: attachment; filename=\"" . $dateTime . "_mapbender_featuretype_" . $reqParams['typename'] . ".zip\"");
                            break;
                    }
                } else {
                    header("Content-Type: application/xml"); //default to gml
                }
            }
            //return content as it is
            echo $content;
        }
        //other operation ...
    } else {
        if ($header !== false) {
            header($header);
        }
        echo $content;
    }
}

function getUserFromSession()
{
    if (Mapbender::session()->get('mb_user_id')) {
        if ((integer) Mapbender::session()->get('mb_user_id') >= 0) {
            $foundUserId = (integer) Mapbender::session()->get('mb_user_id');
            //$e = new mb_exception("user id: ".$foundUserId." found in session");
        } else {
            $foundUserId = false;
            //$e = new mb_exception("user id not found or not casted to integer");
            //$e = new mb_exception("Newly initialized session - no logged in mapbender user for this session!");
        }
    } else {
        $foundUserId = false;
        //$e = new mb_exception("user id not found or not casted to integer");
        //$e = new mb_exception("Newly initialized session - no logged in mapbender user for this session!");
    }
    return $foundUserId;
}

function getVariableUsage($var) {
  $total_memory = memory_get_usage();
  $tmp = unserialize(serialize($var));
  return memory_get_usage() - $total_memory; 
}

//function to remove one complete get param out of the query
function delTotalFromQuery($paramName,$queryString) {
	//echo $paramName ."<br>";
	$queryString = "&".$queryString;
	if ($paramName == "searchText") {
			$str2exchange = "searchText=*&";
		} else {
			$str2exchange = "";
	}
	$queryStringNew = preg_replace('/\b'.$paramName.'\=[^&]*&?/',$str2exchange,$queryString); //TODO find empty get params
	$queryStringNew = ltrim($queryStringNew,'&');
	$queryStringNew = rtrim($queryStringNew,'&');
	return $queryStringNew;
}
?>
