<?php
# http://www.mapbender2.org/index.php/Owsproxy
# Module maintainer armin11
#
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
require(dirname(__FILE__) . "/../../http/classes/class_administration.php");
require(dirname(__FILE__) . "/../../http/classes/class_connector.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_mb_exception.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_user.php");
require_once(dirname(__FILE__) . "/../../http/classes/class_ogr.php");
require(dirname(__FILE__) . "/../../owsproxy/http/classes/class_QueryHandler.php");

//store global variable as local, cause it may be overwritten somewhen ;-)
//$PHP_AUTH_DIGEST = $_SERVER['PHP_AUTH_DIGEST'];
/*$numberOfTest = 0;
$numberOfTest++;
$e = new mb_exception($numberOfTest.". test - index.php PHP_AUTH_DIGEST: ".$_SERVER['PHP_AUTH_DIGEST']);*/

$urlsToExclude = array();
$postData = false;
$authType = 'digest';

//Ticket #4747: Make forceBasicAuth optional - Instead read from header 
$httpAuthHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $httpAuthHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $httpAuthHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

if ($httpAuthHeader && stripos($httpAuthHeader, 'Basic ') === 0) {
    $authType = 'basic';
}

//Ticket #5690: Case-insensitive check for "forceBasicAuth"
if ($authType !== "basic") {
    $forceBasicAuthKey = null;
    foreach ($_REQUEST as $key => $value) {
        if (strcasecmp($key, 'forceBasicAuth') === 0) {
            $forceBasicAuthKey = $key;
            break;
        }
    }
    if ($forceBasicAuthKey !== null && $_REQUEST[$forceBasicAuthKey] !== "") {
        $testMatch = $_REQUEST[$forceBasicAuthKey];
        if (!($testMatch == 'true')) {
            echo 'Parameter <b>forceBasicAuth</b> is not valid (true).<br/>';
            die();
        } else {
            $authType = 'basic';
        }
        $testMatch = NULL;
    }
}

if (is_file(dirname(__FILE__) . "/../../conf/excludeproxyurls.conf")) {
    require_once(dirname(__FILE__) . "/../../conf/excludeproxyurls.conf");
}

$db = db_connect($DBSERVER, $OWNER, $PW);
db_select_db(DB, $db);

$imageformats = array("image/png", "image/gif", "image/jpeg", "image/jpg");
$width = 400;
$height = 400;

//check request params for checking anonymous authorization#########################################
//TODO!!!!!!

$layerId = false;
$wfsId = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e = new mb_notice("http_auth/http/index.php: REQUEST METHOD: POST");
} else {
    $e = new mb_notice("http_auth/http/index.php: REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
}
//test for existing post data
$postData = file_get_contents("php://input");

if (isset($postData) && $postData !== '') {
    $e = new mb_notice("http_auth/http/index.php: postdata: " . $postData);
} else {
    $e = new mb_notice("http_auth/http/index.php: postdata (file content) empty!");
    $postData = false;
}

if (isset($_REQUEST["layer_id"]) & $_REQUEST["layer_id"] != "") {
    $testMatch = $_REQUEST["layer_id"];
    $pattern = '/^[0-9]*$/';
    if (!preg_match($pattern, $testMatch)) {
        echo 'Parameter <b>layer_id</b> is not valid (integer).<br/>';
        die();
    }
    $layerId = $testMatch;
    $testMatch = NULL;
}
if (isset($_REQUEST["wfs_id"]) & $_REQUEST["wfs_id"] != "") {
    $testMatch = $_REQUEST["wfs_id"];
    $pattern = '/^[0-9]*$/';
    if (!preg_match($pattern, $testMatch)) {
        echo 'Parameter <b>wfs_id</b> is not valid (integer).<br/>';
        die();
    }
    $wfsId = $testMatch;
    $testMatch = NULL;
}

$query = new QueryHandler($postData, $_REQUEST, $_SERVER['REQUEST_METHOD']);
$reqParams = $query->getRequestParams();

if ($wfsId !== false) {
    $typeNameParameter = "typename";
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

    if (isset($reqParams[$typeNameParameter]) & $reqParams[$typeNameParameter] != "") {
        $testMatch = $reqParams[$typeNameParameter];
        //simple pattern - without blanks!
        $pattern = '/^[0-9a-zA-Z\.\-_:,]*$/';
        if (!preg_match($pattern, $testMatch)) {
            //echo 'userId: <b>'.$testMatch.'</b> is not valid.<br/>';
            echo 'Parameter <b>' . $typeNameParameter . '</b> is not valid.<br/>';
            die();
        }
        $reqParams[$typeNameParameter] = $testMatch;
        $testMatch = NULL;
    }
}
//check authorization - set allowed anonymous access initially to false and check if an anonymous user may have access to the resources
$anonymousAccess = false;

if ($layerId !== false) {
    $user = new user(PUBLIC_USER);
    $anonymousAccess = $user->isLayerAccessible($layerId);
}
if ($wfsId !== false) {
    //$e = new mb_exception("typename: ". $reqParams[$typeNameParameter]);
    if (isset($reqParams[$typeNameParameter]) && $reqParams[$typeNameParameter] !== false && $reqParams[$typeNameParameter] !== '') {
        $user = new user(PUBLIC_USER);
        $anonymousAccess = $user->areFeaturetypesAccessible($reqParams[$typeNameParameter], $wfsId);
    } else {
        /**
          * typename not requested - so check accessability for each featuretype of the service
          * 
          * only if all are accessable, give anonymous access to getcapabilities and other requests, that don't need a typename(s) parameter
         **/
        $sql = "SELECT featuretype_name FROM wfs_featuretype WHERE fkey_wfs_id = $1";
        $v = array($wfsId);
        $t = array("i");
        $res = db_prep_query($sql, $v, $t);
        if (!($row = db_fetch_all($res))) {
            return false;
        } else {
            if (count($row) == 1 && $row[0]['featuretype_name'] == null) {
                return false;
            } else {
                $allTypenames = "";
                foreach ($row as $singleRow) {
                    $allTypenames .= $singleRow['featuretype_name'] . ",";
                }
                $allTypenames = rtrim($allTypenames, ',');
            }
            $user = new user(PUBLIC_USER);
            $anonymousAccess = $user->areFeaturetypesAccessible($allTypenames, $wfsId);
        }
    }
}

/*if ($anonymousAccess == true){
    $numberOfTest++;
    $e = new mb_notice($numberOfTest.". test -  anonymousAccessAllowed is true"); 
} else {
    $numberOfTest++;
    $e = new mb_notice($numberOfTest.". test - anonymousAccessAllowed is false");
}*/

//check if proxy is enabled for requested resource
$layerId = $_REQUEST['layer_id'];
$wfsId = $_REQUEST['wfs_id'];
$withChilds = false;
if (isset($_REQUEST["withChilds"]) && $_REQUEST["withChilds"] === "1") {
    $withChilds = true;
}
//$e = new mb_exception("http_auth/http/index.php: wfsId: ".$wfsId);
$n = new administration();
if (!(isset($reqParams['service'])) and (strtolower($reqParams['request']) == 'getmap' || strtolower($reqParams['request']) == 'getlegendgraphic')) {
    $reqParams['service'] = 'wms';
}
//check for type of ows requested
switch (strtolower($reqParams['service'])) {
    case 'wms':
        $wmsId = getWmsIdByLayerId($layerId);
        $owsproxyString = $n->getWMSOWSstring($wmsId);
        $auth = $n->getAuthInfoOfWMS($wmsId);
        break;
    case 'wfs':
        $owsproxyString = $n->getWFSOWSstring($wfsId);
        $auth = $n->getAuthInfoOfWFS($wfsId);
        break;
}

//check if proxy is activated

if (isset($owsproxyString) && $owsproxyString != "" && $owsproxyString != false) {
    $proxyEnabled = true;
} else {
    $proxyEnabled = false;
}

/*if ($proxyEnabled){
    $numberOfTest++;
    $e = new mb_notice($numberOfTest.". test - index.php proxyEnabled is true");
} else {
    $numberOfTest++;
    $e = new mb_notice($numberOfTest.". test - index.php proxyEnabled is false");
}*/

//next check if anonymous user has rights to access ressource - if so - don't use authentication
if (($anonymousAccess && $proxyEnabled) || ($proxyEnabled == false)) {
    $userId = PUBLIC_USER;
    /*$numberOfTest++;
    $e = new mb_notice($numberOfTest.". test - index.php use public user");*/
} else {
    switch ($authType) {
        case 'digest': 
            /*$numberOfTest++;
            $e = new mb_notice($numberOfTest.". test - index.php don't use public user - force auth digest");*/
            //special for type of authentication ******************************
            //control if digest auth is set, if not set, generate the challenge with getNonce()
            //$e = new mb_exception("test: SERVER vars:".json_encode($_SERVER));
            //$e = new mb_exception("test: getallheaders() vars:".json_encode(getallheaders()));
            if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
            //if (empty($PHP_AUTH_DIGEST)) {
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Digest realm="' . REALM .
                    '",qop="auth",nonce="' . getNonce() . '",opaque="' . md5(REALM) . '"');
                die('Text to send if user hits Cancel button');
            }  
            //read out the header in an array
            $requestHeaderArray = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
            //error if header could not be read
            if (!($requestHeaderArray)) {
                echo 'Following Header information cannot be validated - check your clientsoftware!<br>';
                echo $_SERVER['PHP_AUTH_DIGEST'] . '<br>';
                die();
            }
            //get mb_username and email out of http_auth username string
            $userIdentification = explode(';', $requestHeaderArray['username']);
            $mbUsername = $userIdentification[0];
            $mbEmail = $userIdentification[1]; //not given in all circumstances
            $userInformation = getUserInfo($mbUsername, $mbEmail);

            if ($userInformation[0] == '-1') {
                die('User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' not known to security proxy!');
            }
            if ($userInformation[1] == '') { 
                die('User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' has no digest - please set a new password and try again!');
            }
            //first check the stale!
            if ($requestHeaderArray['nonce'] == getNonce()) {
                // Up-to-date nonce received
                $stale = false;
            } else {
                // Stale nonce received (probably more than x seconds old)
                $stale = true;
                //give another chance to authenticate
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Digest realm="' . REALM . '",qop="auth",nonce="' . getNonce() . '",opaque="' . md5(REALM) . '" ,stale=true');
            }
            // generate the valid response to check the request of the client
            $A1 = $userInformation[1];
            $A2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $requestHeaderArray['uri']);
            $valid_response = $A1 . ':' . getNonce() . ':' . $requestHeaderArray['nc'];
            $valid_response .= ':' . $requestHeaderArray['cnonce'] . ':' . $requestHeaderArray['qop'] . ':' . $A2;
            $valid_response = md5($valid_response);
            if ($requestHeaderArray['response'] != $valid_response) { //the user have to authenticate new - cause something in the authentication went wrong
                die('Authentication failed - sorry, you have to authenticate once more!');
            }
            //if we are here - authentication has been done well!
            //let's do the proxy things (came from owsproxy.php):
            //special for type of authentication ******************************
            //user information
            //define $userId from database information
            $userId = $userInformation[0];
            break;
        case 'basic':
            /*$numberOfTest++;
            $e = new mb_exception($numberOfTest.". test - index.php auth basic");*/
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('WWW-Authenticate: Basic realm="' . REALM . '"');
                header('HTTP/1.1 401 Unauthorized');
                die('Authentication failed - sorry, you have to authenticate once more!');
            } else {
                //get mb_username and email out of http_auth username string
                $userIdentification = explode(';', $_SERVER['PHP_AUTH_USER']);
                $mbUsername = $userIdentification[0];
                $mbEmail = $userIdentification[1]; //not given in all circumstances
                $userInformation = getUserInfo($mbUsername, $mbEmail);

                if ($userInformation[0] == '-1') {
                    die('User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' not known to security proxy!');
                }

                //check password - new since 06/2019 - secure password !!!!!
                if ($userInformation[3] == '' || $userInformation[3] == null) {
                    die('User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' has no password which is stored in a secure way. - Please login at the portal to generate one!');
                }
                if (password_verify($_SERVER['PHP_AUTH_PW'], $userInformation[3])) {
                    $userId = $userInformation[0];
                } else {
                    $userId = $userInformation[0];
                    die('HTTP Authentication failed for user: ' . $mbUsername . '!');
                }
            }
            break;
    }
}

//$e = new mb_exception("http_auth/http/index.php: proxyEnabled: ".$proxyEnabled." - anonymousAccess: ".$anonymousAccess);
//if ($proxyEnabled == false && $anonymousAccess == false) {
//    die('The requested resource does not exists or the routing through mapbenders owsproxy is not activated and anonymous access is not allowed!');
//}

//$e = new mb_exception("user: " . $userId);

//get authentication infos if they are available in wms table! if not $auth = false
if ($auth['auth_type'] == '') {
    unset($auth);
}
/*if ($proxyEnabled) {
    $e = new mb_exception("test - index.php owsproxy active!");
}
$numberOfTest++;
$e = new mb_notice($numberOfTest.". test - index.php userId: ".$userId);*/
/* ************ main workflow *********** */

switch (strtolower($reqParams['request'])) {

    case 'getcapabilities':
        switch (strtolower($reqParams['service'])) {
            case 'wfs':
                if ($proxyEnabled) {
                    $arrayOnlineresources = checkWfsPermission($owsproxyString, false, $userId);
                } else {
                    //get wfs info by id
                    $sql = "SELECT * FROM wfs WHERE wfs_id = $1";
                    $v = array($wfsId);
                    $t = array("i");
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
                    $arrayOnlineresources = $service;
                }
                $query->setOnlineResource($arrayOnlineresources['wfs_getcapabilities']);
                $request = $query->getRequest();
                $request = str_replace('?&', '?', $request);
                //TODO: following is not the standard way because ows has not to handle vsp!!!
                $request = delTotalFromQuery("wfs_id", $request);
                //add force basic to request!!!!! - not for capabilities?
                if ($authType == 'basic') {
                    $extraParameter = "forceBasicAuth=true";
                } else {
                    $extraParameter = false;
                }
                //don't allow get parameters in conjunction with post!
                if ($postData !== false) {
                    $request = $arrayOnlineresources['wfs_getcapabilities'];
                }
                if (isset($auth)) {
                    getWfsCapabilities($request, $extraParameter, $auth);
                } else {
                    //$e = new mb_exception("http_auth/http/index.php: try to load get capabilities");
                    getWfsCapabilities($request, $extraParameter);
                }
                break;
            case 'wms':
                $arrayOnlineresources = checkWmsPermission($owsproxyString, $userId);
                $query->setOnlineResource($arrayOnlineresources['wms_getcapabilities']);
                if (isset($_SERVER["HTTPS"])) {
                    $urlPrefix = "https://";
                } else {
                    $urlPrefix = "http://";
                }
                if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') {
                    $request = MAPBENDER_PATH . "/php/wms.php?layer_id=" . $layerId;
                } else {
                    $request = $urlPrefix . $_SERVER['HTTP_HOST'] . "/mapbender/php/wms.php?layer_id=" . $layerId;
                }
                if ($withChilds) {
                    $requestFull .= $request . '&withChilds=1&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS';
                } else {
                    $requestFull .= $request . '&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS';
                }
                if ($authType == 'basic') {
                    $extraParameter = "&forceBasicAuth=true";
                } else {
                    $extraParameter = false;
                }
                if (isset($auth)) {
                    getCapabilities($request, $requestFull, $extraParameter, $auth);
                } else {
                    getCapabilities($request, $requestFull, $extraParameter);
                }
                break;
        }
        break;

    case 'getfeatureinfo':
        $arrayOnlineresources = checkWmsPermission($owsproxyString, $userId);
        $query->setOnlineResource($arrayOnlineresources['wms_getfeatureinfo']);
        $request = $query->getRequest();
        $layers = checkLayerPermission($wmsId, $reqParams['layers'], $userId);
        if ($layers == '') {
            throwE("GetFeatureInfo permission denied on layer with id" . $layerId);
            die();
        }
        //mask
        $log_id = false;
        if ($n->getWmsfiLogTag($arrayOnlineresources['wms_id']) == 1) {
            $price = intval($n->getWmsfiPrice($arrayOnlineresources['wms_id']));
            //TODO - session is not set!!!!!!!!
            $log_id = $n->logWmsGFIProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price);
        }
        if (!defined("SPATIAL_SECURITY") || (defined("SPATIAL_SECURITY") && SPATIAL_SECURITY == false) || $arrayOnlineresources["wms_spatial_security"] == "f") {
            if (isset($auth)) {
                getFeatureInfo($log_id, $request, $auth);
            } else {
                getFeatureInfo($log_id, $request);
            }
        } else {
            new mb_notice("spatial security: $request");

            $x = empty($reqParams["i"]) ? $reqParams["x"] : $reqParams["i"];
            $y = empty($reqParams["j"]) ? $reqParams["y"] : $reqParams["j"];

            $mask = spatial_security\get_mask($reqParams, $userId);

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
        $arrayOnlineresources = checkWmsPermission($owsproxyString, $userId);
        $query->setOnlineResource($arrayOnlineresources['wms_getmap']);
        $layers = checkLayerPermission($wmsId, $reqParams['layers'], $userId);
        if ($layers == '') {
            throwE("GetMap permission denied on layer with id " . $layerId);
            die();
        }
        $query->setParam("layers", urldecode($layers));
        $request = $query->getRequest();
        $log_id = false;
        if (!defined("SPATIAL_SECURITY") || (defined("SPATIAL_SECURITY") && SPATIAL_SECURITY == false) || $arrayOnlineresources["wms_spatial_security"] == "f") {
            #log proxy requests
            if ($n->getWmsLogTag($arrayOnlineresources['wms_id']) == 1) {
                $price = intval($n->getWmsPrice($arrayOnlineresources['wms_id']));
                $log_id = $n->logFullWmsProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price, 0);
            }
            if (isset($auth)) {
                getImage($log_id, $request, $auth);
            } else {
                getImage($log_id, $request);
            }
        } else {
            new mb_notice("wms {$arrayOnlineresources['wms_id']} is spatially secured");
            if ($n->getWmsLogTag($arrayOnlineresources['wms_id']) == 1) { #log proxy requests
                $price = intval($n->getWmsPrice($arrayOnlineresources['wms_id']));
                //initially create log record with number of all pixels - if image has only one color, the pixel count
                //will be reset later in function getImage
                $log_id = $n->logFullWmsProxyRequest($arrayOnlineresources['wms_id'], $userId, $request, $price, 0, false);
            }
            $mask = spatial_security\get_mask($reqParams, $userId);
            if ($mask === null) {
                throwImage("WMS " . $arrayOnlineresources['wms_id'] . " needs spatial mask!");
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
    case 'getlegendgraphic':
        $url = getLegendUrl($wmsId);
        if (isset($reqParams['sld']) && $reqParams['sld'] != "") {
            $url = $url . getConjunctionCharacter($url) . "SLD=" . $reqParams['sld'];
        }
        if (isset($auth)) {
            getImage(false, $url, $auth);
        } else {
            getImage(false, $url);
        }
        break;
    case 'getfeature':
        if (isset($reqParams['storedquery_id']) && $reqParams['storedquery_id'] !== "") {
            $storedQueryId = $reqParams['storedquery_id'];
            $arrayOnlineresources = checkWfsStoredQueryPermission($wfsId, $storedQueryId, $userId);
        } else {
            $arrayFeatures = array($reqParams[$typeNameParameter]);
            $arrayOnlineresources = checkWfsPermission($owsproxyString, $arrayFeatures, $userId);
        }
        $query->setOnlineResource($arrayOnlineresources['wfs_getfeature']);
        $request = $query->getRequest();
        $request = stripslashes($request);
        
        //TODO - what if storedquery are used ? log storedquery_id?
        if ($n->getWfsLogTag($arrayOnlineresources['wfs_id']) == 1) {
            $price = intval($n->getWfsPrice($arrayOnlineresources['wfs_id']));
            if (isset($reqParams['storedquery_id']) && $reqParams['storedquery_id'] !== "") {
                $log_id = $n->logWfsProxyRequest(
                    $arrayOnlineresources['wfs_id'],
                    $userId,
                    $request,
                    $price,
                    0,
                    $reqParams['storedquery_id']
                );
            } else {
                $log_id = $n->logWfsProxyRequest(
                    $arrayOnlineresources['wfs_id'],
                    $userId,
                    $request,
                    $price,
                    0,
                    $reqParams[$typeNameParameter]
                );
            }
        } else {
            $log_id = false;
        }

        //TODO: following is not the standard way because ows has not to handle vsp!!!
        $request = delTotalFromQuery("wfs_id", $request);
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
        //really crazy: https://github.com/qgis/QGIS/commit/ccb4c80f8a6d2bb179258f1ffec0dc9a447ca465
        $arrayOnlineresources = checkWfsPermission($owsproxyString, $arrayFeatures, $userId);
        $query->setOnlineResource($arrayOnlineresources['wfs_describefeaturetype']);
        $request = $query->getRequest();
        $request = stripslashes($request);

        //TODO: following is not the standard way because ows has not to handle vsp!!!
        $request = delTotalFromQuery("wfs_id", $request);
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
        $request = delTotalFromQuery("wfs_id", $request);
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
        $request = delTotalFromQuery("wfs_id", $request);
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

    case '':
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $arrayFeatures = getWfsFeaturesFromTransaction($HTTP_RAW_POST_DATA);
        } else {
            $rawpostdata = file_get_contents("php://input");
            $arrayFeatures = getWfsFeaturesFromTransaction($rawpostdata);
        }
        $arrayOnlineresources = checkWfsPermission($owsproxyString, $arrayFeatures, $userId);
        $query->setOnlineResource($arrayOnlineresources['wfs_transaction']);
        $request = $query->getRequest();
        //TODO: following is not the standard way because ows has not to handle vsp!!!
        $request = delTotalFromQuery("wfs_id", $request);
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            doTransaction($request, $HTTP_RAW_POST_DATA);
        } else {
            $rawpostdata = file_get_contents("php://input");
            doTransaction($request, $rawpostdata);
        }
        break;

    default:
        echo 'Your are logged in as: <b>' . $requestHeaderArray['username'] . '</b> and requested the layer/featuretype with id=<b>' . $layerId . '</b> but your request is not a valid OWS request';

}

//functions for http_auth 
//**********************************************************************************************

function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));
    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }
    return $needed_parts ? false : $data;
}

function getUserInfo($mbUsername, $mbEmail)
{
    $result = array();
    if (preg_match('#[@]#', $mbEmail)) {
        $sql = "SELECT mb_user_id, mb_user_digest, mb_user_password, password FROM mb_user where mb_user_name = $1 AND mb_user_email = $2";
        $v = array($mbUsername, $mbEmail);
        $t = array("s", "s");
    } else {
        $sql = "SELECT mb_user_id, mb_user_aldigest As mb_user_digest, mb_user_password, password FROM mb_user where mb_user_name = $1";
        $v = array($mbUsername);
        $t = array("s");
    }
    $res = db_prep_query($sql, $v, $t);
    if (!($row = db_fetch_array($res))) {
        $result[0] = "-1";
    } else {
        $result[0] = $row['mb_user_id'];
        $result[1] = $row['mb_user_digest'];
        $result[2] = $row['mb_user_password'];
        $result[3] = $row['password'];
    }
    return $result;
}

function getNonce()
{
    global $nonceLife;
    $time = ceil(time() / $nonceLife) * $nonceLife;
    return md5(date('Y-m-d H:i', $time) . ':' . $_SERVER['REMOTE_ADDR'] . ':' . NONCEKEY);
}

/*********************************************************/

function throwE($e)
{
    global $reqParams, $imageformats;
    header ( "Access-Control-Allow-Origin: " . "*");
    if (in_array($reqParams['format'], $imageformats)) {
        throwImage($e);
    } else {
        throwText($e);
    }
}

function throwImage($e)
{
    global $width, $height;
    header ( "Access-Control-Allow-Origin: " . "*");
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
    header ( "Access-Control-Allow-Origin: " . "*");
    if (!in_array($reqParams['format'], $imageformats)) {
        header("Content-Type: image/png");
        imagepng($im);
    } else {
        $format = $reqParams['format'];
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
        $header = "Content-Type: " . $reqParams['format'];
    }
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
    if ($auth) {
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
    if ($log_id != false) {
        $content = getDocumentContent($log_id, $url, "Content-Type: application/xml", $auth);
    } else {
        //allow other formats - add format to request
        $content = getDocumentContent(false, $url, '', $auth);
    }
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
        //updated features
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
    //TODO: problem here, we are stateless and have no information about any session :-( . To allow proxying, we need another temporal storage for the given urls 
    global $urlsToExclude;
    $owsproxyUrls = Mapbender::session()->get('owsproxyUrls');
    if ($owsproxyUrls == false) {
        $e = new mb_notice("owsproxyUrls does not exist - create it!");
        $owsproxyUrls = array();
        $owsproxyUrls['id'] = array();
        $owsproxyUrls['url'] = array();
        Mapbender::session()->set('owsproxyUrls', $owsproxyUrls);
    }
    $pattern = "/[\"|\'](https*:\/\/[^\"|^\']*)[\"|\']/";
    preg_match_all($pattern, $content, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
        $req = $matches[1][$i];
        $e = new mb_notice("Gefundene URL " . $i . ": " . $req);
        if (in_array($req, $urlsToExclude)) {
            continue;
        }
        $id = registerURL($req);
        $extReq = setExternalRequest($id);
        $content = str_replace($req, $extReq, $content);
    }
    return $content;
}

function setExternalRequest($id)
{
    global $reqParams, $query;
    $extReq = "http://" . $_SESSION['HTTP_HOST'] . "/owsproxy/" . $reqParams['sid'] . "/" . $id . "?request=external";
    return $extReq;
}

function getExternalRequest($id)
{
    for ($i = 0; $i < count($_SESSION["owsproxyUrls"]["url"]); $i++) {
        if ($id == $_SESSION["owsproxyUrls"]["id"][$i]) {
            $cUrl = $_SESSION["owsproxyUrls"]["url"][$i];
            $query_string = removeOWSGetParams($_SERVER["QUERY_STRING"]);
            if ($query_string != '') {
                $cUrl .= getConjunctionCharacter($cUrl) . $query_string;
            }
            $metainfo = get_headers($cUrl, 1);
            // just for the stupid InternetExplorer
            // :D
            header('Pragma: private');
            header('Cache-control: private, must-revalidate');
            header("Content-Type: " . $metainfo['Content-Type']);

            $content = getDocumentContent(false, $cUrl, $metainfo);
            echo $content;
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
    if (!in_array($url, $_SESSION["owsproxyUrls"]["url"])) {
        $id = md5($url);
        array_push($_SESSION["owsproxyUrls"]["url"], $url);
        array_push($_SESSION["owsproxyUrls"]["id"], $id);
    } else {
        for ($i = 0; $i < count($_SESSION["owsproxyUrls"]["url"]); $i++) {
            if ($url == $_SESSION["owsproxyUrls"]["url"][$i]) {
                $id = $_SESSION["owsproxyUrls"]["id"][$i];
            }
        }
    }
    return $id;
}

function getCapabilities($request, $requestFull, $extraParameter, $auth = false)
{
    global $arrayOnlineresources;
    global $layerId;
    header("Content-Type: application/xml");    
    header ( "Access-Control-Allow-Origin: " . "*");
    if ($auth) {
        $d = new connector($requestFull, $auth);
    } else {
        $d = new connector($requestFull);
    }
    $content = $d->file;
    //show temporal content fo capabilities
    $e = new mb_notice("content from wms.php fascade after going thru curl: " . $content);
    //loading as xml
    libxml_use_internal_errors(true);
    try {
        $capFromFascadeXmlObject = simplexml_load_string($content);
        if ($capFromFascadeXmlObject === false) {
            foreach (libxml_get_errors() as $error) {
                $err = new mb_exception("http_auth/index.php: " . $error->message);
            }
            throw new Exception("http_auth/index.php: " . 'Cannot parse Metadata XML!');
            echo "<error>http_auth/index.php: Cannot parse Capabilities XML!</error>";
            die();
        }
    } catch (Exception $e) {
        $err = new mb_exception("http_auth/index.php: " . $e->getMessage());
        echo "<error>http_auth/index.php: " . $e->getMessage() . "</error>";
        die();
    }
    //exchanging urls in some special fields
    //
    //GetCapabilities, GetMap, GetFeatureInfo, GetLegendGraphics, ...
    $capFromFascadeXmlObject->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
    //Mapping of urls for wms 1.1.1 which should be exchanged 
    $urlsToChange = array(
        '/WMT_MS_Capabilities/Capability/Request/GetCapabilities/DCPType/HTTP/Get/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Request/GetCapabilities/DCPType/HTTP/Post/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Request/GetMap/DCPType/HTTP/Get/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Request/GetMap/DCPType/HTTP/Post/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Request/GetFeatureInfo/DCPType/HTTP/Get/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Request/GetFeatureInfo/DCPType/HTTP/Post/OnlineResource/@xlink:href',
        '/WMT_MS_Capabilities/Capability/Layer/Layer/Style/LegendURL/OnlineResource/@xlink:href'
    );
    foreach ($urlsToChange as $xpath) {
        $href = $capFromFascadeXmlObject->xpath($xpath);
        //$e = new mb_notice("old href: " . $href[0]);
        //$e = new mb_notice("href replaced: " . replaceOwsUrls($href[0], $layerId));
        $href[0][0] = replaceOwsUrls($href[0], $layerId, $extraParameter);
    }
    echo $capFromFascadeXmlObject->asXML();
}

function replaceOwsUrls($owsUrl, $layerId, $extraParameter)
{
    $new = "http_auth/" . $layerId . "?";
    $pattern = "#owsproxy/[a-z0-9]{32}\/[a-z0-9]{32}\?#m";
    $httpAuthUrl = preg_replace($pattern, $new, $owsUrl);
    //replace 
    //also replace the getcapabilities url with authenticated one ;-)
    if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') {
        $wmsUrl = parse_url(MAPBENDER_PATH);
        $path = $wmsUrl['path'];
        $pattern = "#" . $path . "/php/wms.php\?layer_id=" . $layerId . "&#m";
    } else {
        $pattern = "#mapbender/php/wms.php\?layer_id=" . $layerId . "&#m";
    }
    $httpAuthUrl = preg_replace($pattern, "/" . $new, $httpAuthUrl);
    //use always https for url
    if (defined("HTTP_AUTH_PROXY") && HTTP_AUTH_PROXY != '') {
        $parsed_url = parse_url(HTTP_AUTH_PROXY);
        if ($parsed_url['scheme'] == "https") {
            $httpAuthUrl = preg_replace("#http:#", "https:", $httpAuthUrl);
            $httpAuthUrl = preg_replace("#:80/#", ":443/", $httpAuthUrl);
        }
    }
    if ($extraParameter !== false) {
        $httpAuthUrl .= $extraParameter;
    }
    return $httpAuthUrl;
}

function getWfsCapabilities($request, $extraParameter, $auth = false)
{
    global $arrayOnlineresources, $postData, $query;
    global $sid, $serviceId, $wfsId;
    global $reqParams;
    global $proxyEnabled, $anonymousAccess;
    header ( "Access-Control-Allow-Origin: " . "*");
    //$e = new mb_exception("http_auth/http/index.php: in function getWfsCapabilities - request=".$request);
    $urlsToChange = array();
    switch ($reqParams['version']) {
        case "2.0.0":
            $urlsToChange[] = '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:OnlineResource/@xlink:href';
            $operations = array("GetCapabilities", "DescribeFeatureType", "GetFeature", "Transaction", "GetPropertyValue", "ListStoredQueries", "DescribeStoredQueries", "CreateStoredQuery", "DropStoredQuery");
            foreach ($operations as $operation) {
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href';
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href';
            }
            $namespaces = array(
                "ows" => "http://www.opengis.net/ows/1.1",
                "wfs" => "http://www.opengis.net/wfs/2.0",
                "xlink" => "http://www.w3.org/1999/xlink"
            );
            break;
        case "2.0.2":
            $urlsToChange[] = '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:OnlineResource/@xlink:href';
            $operations = array("GetCapabilities", "DescribeFeatureType", "GetFeature", "Transaction", "GetPropertyValue", "ListStoredQueries", "DescribeStoredQueries", "CreateStoredQuery", "DropStoredQuery");
            foreach ($operations as $operation) {
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href';
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href';
            }
            $namespaces = array(
                "ows" => "http://www.opengis.net/ows/1.1",
                "wfs" => "http://www.opengis.net/wfs/2.0",
                "xlink" => "http://www.w3.org/1999/xlink"
            );
            break;
        case "1.1.0":
            $urlsToChange[] = '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:OnlineResource/@xlink:href';
            $operations = array("GetCapabilities", "DescribeFeatureType", "GetFeature", "GetGmlObject", "Transaction");
            foreach ($operations as $operation) {

                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href';
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href';
            }
            $namespaces = array(
                "ows" => "http://www.opengis.net/ows",
                "wfs" => "http://www.opengis.net/wfs",
                "xlink" => "http://www.w3.org/1999/xlink"
            );
            break;
        case "1.0.0":
            $urlsToChange[] = '/wfs:WFS_Capabilities/wfs:Service/wfs:OnlineResource/text()';
            $operations = array("GetCapabilities", "DescribeFeatureType", "GetFeature", "Transaction");
            foreach ($operations as $operation) {
                $urlsToChange[] = '/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:' . $operation . '/wfs:DCPType/wfs:HTTP/wfs:Get/@onlineResource';
                $urlsToChange[] = '/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:' . $operation . '/wfs:DCPType/wfs:HTTP/wfs:Post/@onlineResource';
            }
            $namespaces = array("wfs" => "http://www.opengis.net/wfs");
            break;
        default:
            //default exchange all like 2.0.0
            $operations = array("GetCapabilities", "DescribeFeatureType", "GetFeature", "Transaction", "GetPropertyValue", "ListStoredQueries", "DescribeStoredQueries", "CreateStoredQuery", "DropStoredQuery");
            foreach ($operations as $operation) {
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href';
                $urlsToChange[] = '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="' . $operation . '"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href';
            }
            $namespaces = array(
                "ows" => "http://www.opengis.net/ows/1.1",
                "wfs" => "http://www.opengis.net/wfs/2.0",
                "xlink" => "http://www.w3.org/1999/xlink"
            );
            break;
    }
    //TODO - set to persistent url
    $owsproxyUrl = parse_url(OWSPROXY);
    if ($owsproxyUrl['port'] == '80' || $owsproxyUrl['port'] == '') {
        $port = "";
    } else {
        $port = ":" . $owsproxyUrl['port'];
    }
    $new = $owsproxyUrl['scheme'] . "://" . $owsproxyUrl['host'] . $port . "/registry/wfs/" . $wfsId; # ."?";
    if ($extraParameter !== false) {
        $new .= '?' . $extraParameter;
        //force https if authType is basic!
        $new = str_replace("http://", "https://", $new);
    }
    if ($postData == false) { //no post_xml was used
        //check POST/GET
        if ($query->reqMethod !== 'POST') {
            if ($auth) { //new for HTTP Authentication
                $d = new connector($request, $auth);
            } else {
                $d = new connector($request);
            }
        } else {
            $d = new connector();
            $d->set('httpType', 'POST');
            $d->set('httpPostData', $query->getPostQueryString()); //as array
            //TODO maybe delete some params from querystring which are already in post array
            if ($auth) {
                $d->load($request, $auth);
            } else {
                $d->load($request);
            }
        }
        $wfsCaps = $d->file;
    } else {
        $postInterfaceObject = new connector();
        $postInterfaceObject->set('httpType', 'POST');
        $postInterfaceObject->set('curlSendCustomHeaders', true);
        $postInterfaceObject->set('httpPostData', $postData);
        $postInterfaceObject->set('httpContentType', 'text/xml');
        if ($auth) {
            $postInterfaceObject->load($request, $auth);
        } else {
            $postInterfaceObject->load($request);
        }
        $wfsCaps = $postInterfaceObject->file;
    }

    libxml_use_internal_errors(true);
    try {
        $capFromFascadeXmlObject = simplexml_load_string($wfsCaps);
        if ($capFromFascadeXmlObject === false) {
            foreach (libxml_get_errors() as $error) {
                $err = new mb_exception("http_auth/index.php: " . $error->message);
            }
            throw new Exception("http_auth/index.php: " . 'Cannot parse Metadata XML!');
            echo "<error>http_auth/index.php: Cannot parse WFS Capabilities XML!</error>";
            die();
        }
    } catch (Exception $e) {
        $err = new mb_exception("http_auth/index.php: " . $e->getMessage());
        echo "<error>http_auth/index.php: " . $e->getMessage() . "</error>";
        die();
    }

    foreach ($namespaces as $key => $value) {
        $capFromFascadeXmlObject->registerXPathNamespace($key, $value);
    }
    $test = $capFromFascadeXmlObject->xpath("");
    if ($proxyEnabled) {
        foreach ($urlsToChange as $xpath) {
            $href = $capFromFascadeXmlObject->xpath($xpath);
            $href[0][0] = $new;
        }
    }
    header("Content-Type: application/xml");
    echo $capFromFascadeXmlObject->asXML();  
}

/**
 * gets the original url of the requested legend graphic
 * 
 * @param string owsproxy md5
 * @return string url to legend graphic
 */
function getLegendUrl($wmsId)
{
    global $reqParams;
    //get wms_getlegendurl
    $sql = "SELECT wms_getlegendurl FROM wms WHERE wms_id = $1";
    $v = array($wmsId);
    $t = array("i");
    $res = db_prep_query($sql, $v, $t);
    if ($row = db_fetch_array($res)) {
        $getLegendUrl = $row["wms_getlegendurl"];
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

    $v = array($wmsId, $reqParams['layer'], $style, $reqParams['format']);
    $t = array("i", "s", "s", "s");
    $res = db_prep_query($sql, $v, $t);
    if ($row = db_fetch_array($res)) {
        if (strpos($row["legendurl"], 'http') !== 0) {
            $e = new mb_notice("combine legendurls!");
            return $getLegendUrl . $row["legendurl"];
        }
        return $row["legendurl"];
    } else {
        throwE(array("No legendurl available."));
        die();
    }
}

/**
 * validated access permission on requested wms
 * 
 * @param wmsId integer, userId - integer
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
    //$e = new mb_exception(json_encode($myconfs));
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
        $sql .= "JOIN wfs_featuretype ";
        $sql .= "ON wfs_featuretype.featuretype_id = wfs_conf.fkey_featuretype_id ";
        $sql .= "WHERE wfs_featuretype.featuretype_name = $2 ";
        $sql .= "AND wfs_featuretype.fkey_wfs_id = $1";
        $v = array($service["wfs_id"], $feature);
        $t = array("i", "s");
        $res = db_prep_query($sql, $v, $t);
        if (!($row = db_fetch_array($res))) {
            $e = new mb_exception("Permissioncheck failed no wfs conf for wfs " . $service["wfs_id"] . " with featuretype " . $feature);
            throwE(array("No wfs_conf data for featuretype " . $feature));
            die();
        }
        $conf_id = $row["wfs_conf_id"];

        //check permission
        if (!in_array($conf_id, $myconfs)) {
            $e= new mb_exception("Permissioncheck failed:" . $conf_id . " not in " . implode(",", $myconfs));
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
 * @param int id of requested wfs
 * @param string requested featuretype name
 * @return array array with detailed information on reqested wfs
 */
function checkWfsStoredQueryPermission($wfsId, $storedQueryId, $userId)
{
    global $con, $n;
    $myconfs = $n->getWfsConfByPermission($userId);
    if ($storedQueryId !== false) {
    } else {
        throwE(array("No storedquery_id data available."));
        die();
    }
    $sql = "SELECT * FROM wfs WHERE wfs_id = $1";
    $v = array($wfsId);
    $t = array("i");
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

function getWfsOperationUrl($wfsOws, $operationName, $operationMethod)
{
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
    } catch (Exception $e) {
        $e = new mb_exception($e->getMessage());
    }
    if ($wfs20Cap !== false) {
        $xpath = new DOMXPath($wfs20Cap);
        $rootNamespace = $wfs20Cap->lookupNamespaceUri($wfs20Cap->namespaceURI);
        $e = new mb_notice("rootns: " . $rootNamespace);
        $xpath->registerNamespace('defaultns', $rootNamespace);
        $xpath->registerNamespace("ows", "http://www.opengis.net/ows");
        $xpath->registerNamespace("gml", "http://www.opengis.net/gml");
        $xpath->registerNamespace("ogc", "http://www.opengis.net/ogc");
        $xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
        $xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $urlArray = DOMNodeListObjectValuesToArray($xpath->query('/defaultns:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name=\'' . $operationName . '\']/ows:DCP/ows:HTTP/ows:' . $operationMethod . '/@xlink:href'));
        //check for type
        if (is_array($urlArray)) {
            $e = new mb_notice("http_auth/http/index.php: url for operation " . $operationName . " : " . $urlArray[0]);
            $timeEnd = microtime();
            $e = new mb_notice("http_auth/http/index.php: time to get url from capabilities: " . ($timeEnd - $timeBegin) * 1000);
            return $urlArray[0];
        } else {
            $e = new mb_exception("http_auth/http/index.php: no url for operation " . $operationName . " and method " . $operationMethod . " found in Capabilities. Function returned: " . json_encode($urlArray[0]));
            return false;
        }
    } else {
        $e = new mb_exception("http_auth/http/index.php: Problem while trying to do xpath on capabilities document!");
        return false;
    }
}

function DOMNodeListObjectValuesToArray($domNodeList)
{
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
    global $reqParams, $n, $postData, $query, $owsproxyString, $wfsId;
    header ( "Access-Control-Allow-Origin: " . "*");
    $startTime = microtime();
    if ($postData == false) {
        $d = new connector();
        if (strtoupper($reqParams["resulttype"] == "HITS")) {
            $d->set("timeOut", "200");
        }
        //check POST/GET
        if ($query->reqMethod !== 'POST') {
            if ($auth) {
                #$d = new connector($url, $auth);
                $d->load($url, $auth);
            } else {
                #$d = new connector($url);
                $d->load($url);
            }
        } else {
            #$d = new connector();
            $d->set('httpType', 'POST');
            $d->set('httpPostData', $query->getPostQueryString()); //as array
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
        $e = new mb_notice("owsproxy/index.php: postData will be send ");
        $postInterfaceObject = new connector();
        $postInterfaceObject->set('httpType', 'POST');
        $postInterfaceObject->set('curlSendCustomHeaders', true);
        $postInterfaceObject->set('httpPostData', $postData);
        $postInterfaceObject->set('httpContentType', 'text/xml');
        if ($auth) {
            $postInterfaceObject->load($url, $auth);
        } else {
            $postInterfaceObject->load($url);
        }
        $content = $postInterfaceObject->file;
        $httpCode = $postInterfaceObject->httpCode;
    }
    $endTime = microtime();
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
            //got some result 
            $source = new Imagick();
            //$e = new mb_notice("format requested: ".$reqParams["format"]);
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
                                $e = new mb_exception("http_auth/http/index.php: Could not write GeoTIFF cache to " . $tmpGeoTiffFilename);
                            } else {
                                exec('listgeo ' . $tmpGeoTiffFilename . ' > ' . $tmpGeoTiffHeaderFilename, $output);
                            }
                            fclose($h);
                        } else {
                            $e = new mb_exception("http_auth/http/index.php: Could not open " . $tmpGeoTiffFilename);
                        }
                } else {
                    $e = new mb_exception("http_auth/http/index.php: Could not cache TIFF image to extract GeoTIFF header, cause ABSOLUTE_TMPDIR is not defined in mapbender.conf and/or libgeotiff is not available!");
                }              
            }
            $source->readImageBlob($content);
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
                            $e = new mb_exception("http_auth/http/index.php: Could not write GeoTIFF cache to " . $tmpGeoTiffFilename);
                        } else {
                            $newImageWritten = true;
                            $e = new mb_notice("http_auth/http/index.php: geotiff written to cache!");
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
                        $e = new mb_exception("http_auth/http/index.php: Could not open cached tiff file!");
                    }
                } else {
                    $e = new mb_exception("http_auth/http/index.php: Could not cache new TIFF image to add GeoTIFF header, cause ABSOLUTE_TMPDIR is not defined in mapbender.conf and/or libgeotiff is not available!");
                }
            } else {
                //default give back image without special headers
                echo $source->getImageBlob();
            }
        }
        return true;
    } else if (strtoupper($reqParams["request"]) == "GETFEATUREINFO") { // getmap
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
        //new 2023-10-11: exchange url of describefeaturetype operation in collection to allow parsing of the schema
        $owsproxyUrl = parse_url(OWSPROXY);
        if ($owsproxyUrl['port'] == '80' || $owsproxyUrl['port'] == '') {
            $port = "";
        } else {
            $port = ":" . $owsproxyUrl['port'];
        }
        $proxyUrl = $owsproxyUrl['scheme'] . "://" . $owsproxyUrl['host'] . $port . "/registry/wfs/" . $wfsId;
        $describeFeaturetypeUrl = getWfsOperationUrl($owsproxyString, 'DescribeFeatureType', 'Get');
        $content = str_replace(rtrim($describeFeaturetypeUrl, '?'), $proxyUrl, $content);
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
                    $e = new mb_notice("http_auth/http/index.php: " . $numberOfFeatures . " delivered features from wfs.");
                    //TODO: enhance error management
                    if ($log_id !== false) {
                        $n->updateWfsLog(1, '', '', $numberOfFeatures, $log_id);
                    }
                    $e = new mb_notice("http_auth/http/index.php: Time for counting: " . (string)($endTime - $startTime));
                    $e = new mb_notice("http_auth/http/index.php: Memory used for XML String: " . getVariableUsage($content) / 1000000 . "MB");
                    if ($header != false) {
                        header($header);
                    }
                    echo $content;
                } else {
                    //TODO: no feature xml found ! - give back a good error message
                    if ($header != false) {
                        header($header);
                        $e = new mb_exception("http_auth/http/index.php: WFS dows not give back GML - parsing was not successfully!");
                    }
                    echo $content;
                }
            } else {
                //count features with ogrinfo
                /*
                 * new 2022-08-04
                 */
                 $ogr = new Ogr();
                 if ($reqParams['version'] == '2.0.0' || $reqParams['version'] == '2.0.2') {
                     $typeParameterName = "typenames"; 
                 } else {
                     $typeParameterName = "typename"; 
                 }
                 //$ogr->logRuntime = true;
                 //$e = new mb_exception(json_encode($reqParams));
                 //$e = new mb_exception("http_auth/http/index.php: got outputformat: " . "*".urldecode($reqParams['outputformat'])."*");
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

//**********************************************************************************************
//extra functions TODO: push them in class_administration.php 

/**
 * selects the wms id for a given layer id.
 *
 * @param <integer> the layer id
 * @return <string|boolean> either the id of the wms as integer or false when none exists
 */
function getWmsIdByLayerId($id)
{
    $sql = "SELECT fkey_wms_id FROM layer WHERE layer_id = $1";
    $v = array($id);
    $t = array('i');
    $res = db_prep_query($sql, $v, $t);
    $row = db_fetch_array($res);
    if ($row)
        return $row["fkey_wms_id"];
    else
        return false;
}

function getVariableUsage($var)
{
    $total_memory = memory_get_usage();
    $tmp = unserialize(serialize($var));
    return memory_get_usage() - $total_memory;
}

//function to remove one complete get param out of the query
function delTotalFromQuery($paramName, $queryString)
{
    $queryString = "&" . $queryString;
    if ($paramName == "searchText") {
        $str2exchange = "searchText=*&";
    } else {
        $str2exchange = "";
    }
    $queryStringNew = preg_replace('/\b' . $paramName . '\=[^&]*&?/', $str2exchange, $queryString); //TODO find empty get params
    $queryStringNew = ltrim($queryStringNew, '&');
    $queryStringNew = rtrim($queryStringNew, '&');
    return $queryStringNew;
}
