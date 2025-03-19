<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
//classes for csw handling
require_once(dirname(__FILE__)."/../classes/class_cswClient.php");
require_once(dirname(__FILE__)."/../classes/class_csw.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
//************************************************************************************
//parsing parameters
//************************************************************************************
//initialize request parameters:
//	$searchId = "dummysearch";
$searchText = "*";
//	$isoCategories = NULL;
//	$inspireThemes = NULL;
//	$customCategories = NULL;
//	$timeBegin = NULL;
//	$timeEnd = NULL;
//	$regTimeBegin = NULL;
//	$regTimeEnd = NULL;
if (defined('DEFAULT_MAX_RESULTS_PER_PAGE')){
	$maxResults = DEFAULT_MAX_RESULTS_PER_PAGE;
} else {
	$maxResults = 10;
}
$searchBbox = NULL;
$searchTypeBbox = "intersects"; //outside / inside
$languageCode = "de";
$outputFormat = 'json';
$dummySearchResources = "dataset,series,tile,service,application,nonGeographicDataset";
//$dummySearchPages = "1,1,1,1,1,1";

$searchResources = $dummySearchResources;
$searchPages = $dummySearchPages;
//	$resourceIds = NULL; //resourceIds is used to get a comma separated list with ids of the resources - layer - featuretypes - wmc
//it will be used to filter some results
$resultTarget = "web";
//	$preDefinedMaxResults = array(5,10,15,20,25,30);
//	$searchEPSG = "EPSG:31466";
//	$resolveCoupledResources = false;
$classJSON = new Mapbender_JSON;
if (defined('ABSOLUTE_TMPDIR')){
	$tempFolder = ABSOLUTE_TMPDIR;
} else {
	$tempFolder = TMPDIR;
}
//	$orderBy = "rank"; //rank or title or id or date
$hostName = $_SERVER['HTTP_HOST'];
$headers = apache_request_headers();
$originFromHeader = false;
foreach ($headers as $header => $value) {
    	if ($header === "Origin") {
		//$e = new mb_exception("Origin: ".$value);
		$originFromHeader = $value;
    	}
}
//read the whole query string:
$searchURL = $_SERVER['QUERY_STRING'];
//$e = new mb_exception("mod_callMetadata.php: searchURL".$searchURL);
//decode it !
$searchURL = urldecode($searchURL);
//list of possibly hierarchyLevels - iso 19115 MD_ScopeCode
$MD_ScopeCode = array("attribute","attributeType","collectionHardware","collectionSession","dataset","series","nonGeographicDataset","dimensionGroup","feature","featureType","propertyType","fieldSession","software","service","model","tile");
$MD_ScopeCode[] = "application";
$MD_ScopeCode[] = "spatialData";
#
//control if some request variables are not set and set them explicit to NULL
$checkForNullRequests = array("registratingDepartments","isoCategories","inspireThemes","customCategories","regTimeBegin","regTimeEnd","timeBegin","timeEnd","searchBbox","searchTypeBbox","searchResources","orderBy","hostName","resourceIds","restrictToOpenData");

for($i=0; $i < count($checkForNullRequests); $i++){
	if (!$_REQUEST[$checkForNullRequests[$i]] or $_REQUEST[$checkForNullRequests[$i]] == 'false' or $_REQUEST[$checkForNullRequests[$i]] == 'undefined') {
		$_REQUEST[$checkForNullRequests[$i]] = "";
		$searchURL = delTotalFromQuery($checkForNullRequests[$i],$searchURL);
	}
}

//Read out request Parameter:
if (isset($_REQUEST["searchId"]) & $_REQUEST["searchId"] != "") {
	//generate md5 representation, cause the id is used as a filename later on! - no validation needed
	$searchId = md5($_REQUEST["searchId"]);
}
if (isset($_REQUEST["searchText"]) & $_REQUEST["searchText"] != "") {
	$test="(SELECT\s[\w\*\)\(\,\s]+\sFROM\s[\w]+)| (UPDATE\s[\w]+\sSET\s[\w\,\'\=]+)| (INSERT\sINTO\s[\d\w]+[\s\w\d\)\(\,]*\sVALUES\s\([\d\w\'\,\)]+)| (DELETE\sFROM\s[\d\w\'\=]+)";
	//validate to csv integer list
	$testMatch = $_REQUEST["searchText"];
	$pattern = '/(\%27)|(\')|(\-\-)|(\")|(\%22)/';
 	if (preg_match($pattern,$testMatch)){
		//echo 'searchText: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>searchText</b> is not valid.<br/>';
		die();
 	}
	$searchText = $testMatch;
        $searchText = str_replace('<','{<}',$searchText);
 	$searchText = str_replace('>','{>}',$searchText);
	$testMatch = NULL;
	if ($searchText ==='false') {
		$searchText ='*';
	}
}

/*if (isset($_REQUEST["registratingDepartments"]) & $_REQUEST["registratingDepartments"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["registratingDepartments"];
	$pattern = '/^[\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'registratingDepartments: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>registratingDepartments</b> is not valid (integer or cs integer list).<br/>';
		die();
 	}
	$registratingDepartments = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["resourceIds"]) & $_REQUEST["resourceIds"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["resourceIds"];
	$pattern = '/^[\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'resourceIds: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>resourceIds</b> is not valid (integer or cs integer list).<br/>';
		die();
 	}
	$resourceIds = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["isoCategories"]) & $_REQUEST["isoCategories"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["isoCategories"];
	$pattern = '/^[\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'isoCategories: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>isoCategories</b> is not valid (integer or cs integer list).<br/>';
		die();
 	}
	$isoCategories = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["inspireThemes"]) & $_REQUEST["inspireThemes"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["inspireThemes"];
	$pattern = '/^[\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'inspireThemes: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>inspireThemes</b> is not valid (integer or cs integer list).<br/>';
		die();
 	}
	$inspireThemes = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["customCategories"]) & $_REQUEST["customCategories"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["customCategories"];
	$pattern = '/^[\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
 		//echo 'customCategories: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>customCategories</b> is not valid (integer or cs integer list).<br/>';
		die();
 	}
	$customCategories = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["timeBegin"]) & $_REQUEST["timeBegin"] != "") {
	//validate to iso date format YYYY-MM-DD
	$testMatch = $_REQUEST["timeBegin"];
	$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'timeBegin: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>timeBegin</b> is not valid.<br/>';
		die();
 	}
	$timeBegin = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["timeEnd"]) & $_REQUEST["timeEnd"] != "") {
	$testMatch = $_REQUEST["timeEnd"];
	$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'timeEnd: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>timeEnd</b> is not valid.<br/>';
		die();
 	}
	$timeEnd = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["regTimeBegin"]) & $_REQUEST["regTimeBegin"] != "") {
	//validate to iso date format YYYY-MM-DD
	$testMatch = $_REQUEST["regTimeBegin"];
	$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'regTimeBegin: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>regTimeBegin</b> is not valid.<br/>';
		die();
 	}
	$regTimeBegin = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["regTimeEnd"]) & $_REQUEST["regTimeEnd"] != "") {
	//validate to iso date format YYYY-MM-DD
	$testMatch = $_REQUEST["regTimeEnd"];
	$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'regTimeEnd: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>regTimeEnd</b> is not valid.<br/>';
		die();
 	}
	$regTimeEnd = $testMatch;
	$testMatch = NULL;
}*/

if (isset($_REQUEST["maxResults"]) & $_REQUEST["maxResults"] != "") {
	//validate integer to 100 - not more
	$testMatch = $_REQUEST["maxResults"];
	//give max 99 entries - more will be to slow
	$pattern = '/^([0-9]{0,1})([0-9]{1})$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>maxResults</b> is not valid (integer < 99).<br/>';
		die();
 	}
	$maxResults = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["catalogueId"]) & $_REQUEST["catalogueId"] != "") {
	//validate integer to 100 - not more
	$testMatch = $_REQUEST["catalogueId"];
	//
	$pattern = '/^([0-9]{0,1})([0-9]{1})$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>catalogueId</b> is not valid (integer < 99).<br/>';
		die();
 	}
	$catalogueId = $testMatch;
	$testMatch = NULL;
} else {
	echo 'Mandatory parameter <b>catalogueId</b> not set!<br/>';
	die();
}
//example: &searchBbox=7.18159618172,50.2823608933,7.26750846535,50.3502633407
if (isset($_REQUEST["searchBbox"]) & $_REQUEST["searchBbox"] != "") {
	//validate to float/integer
	$testMatch = $_REQUEST["searchBbox"];
	//$pattern = '/^[-\d,]*$/';
	$pattern = '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)*$/';
	$testMatchArray = explode(',',$testMatch);
 	if (count($testMatchArray) != 4) {
		echo 'Parameter <b>searchBbox</b> has a wrong amount of entries.<br/>';
		die();
	}
	for($i=0; $i<count($testMatchArray);$i++){
		if (!preg_match($pattern,$testMatchArray[$i])){
			echo 'Parameter <b>searchBbox</b> is not a valid coordinate value.<br/>';
			die();
 		}
	}
	$searchBbox = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["searchTypeBbox"]) & $_REQUEST["searchTypeBbox"] != "") {
	//validate to inside / outside - TODO implement other ones than intersects which is default
	$testMatch = $_REQUEST["searchTypeBbox"];
 	if (!($testMatch == 'inside' or $testMatch == 'outside' or $testMatch == 'intersects')){
		//echo 'searchTypeBbox: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>searchTypeBbox</b> is not valid (inside,outside,intersects).<br/>';
		die();
 	}
	$searchTypeBbox = $testMatch;
	$testMatch = NULL;
}
/*if (isset($_REQUEST["accessRestrictions"]) & $_REQUEST["accessRestrictions"] != "") {
	//validate to ?
	//TODO implement me //$accessRestrictions = $_REQUEST["accessRestrictions"];
}*/
if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["languageCode"];
 	if (!($testMatch == 'de' or $testMatch == 'en' or $testMatch == 'fr')){
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>languageCode</b> is not valid (de,fr,en).<br/>';
		die();
 	}
	$languageCode = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	$testMatch = $_REQUEST["outputFormat"];
 	if (!($testMatch == 'json' or $testMatch == 'georss')){
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>outputFormat</b> is not valid (json,georss).<br/>';
		die();
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}

//$restrictToOpenData = false;
/*if (isset($_REQUEST["restrictToOpenData"]) & $_REQUEST["restrictToOpenData"] != "") {
	$testMatch = $_REQUEST["restrictToOpenData"];
 	if (!($testMatch == 'true' or $testMatch == 'false')){
		echo 'Parameter <b>restrictToOpenData</b> is not valid (true,false).<br/>';
		die();
 	}
	switch ($testMatch) {
		case "true":
			$restrictToOpenData = "true";
		break;
		case "false":
			$restrictToOpenData = "false";
		break;
	}
	$testMatch = NULL;
}

if (isset($_REQUEST["resolveCoupledResources"]) & $_REQUEST["resolveCoupledResources"] != "") {
	$testMatch = $_REQUEST["resolveCoupledResources"];
 	if (!($testMatch == 'true' or $testMatch == 'false')){
		echo 'Parameter <b>resolveCoupledResources</b> is not valid (true,false (default to false)).<br/>';
		die();
 	}
	switch ($testMatch) {
		case "true":
			$resolveCoupledResources = "true";
		break;
		case "false":
			$resolveCoupledResources = "false";
		break;
	}
	$testMatch = NULL;
}*/

if (isset($_REQUEST["hostName"]) & $_REQUEST["hostName"] != "") {
	//validate to some hosts
	$testMatch = $_REQUEST["hostName"];
	//look for whitelist in mapbender.conf
	$HOSTNAME_WHITELIST_array = explode(",",HOSTNAME_WHITELIST);
	if (!in_array($testMatch,$HOSTNAME_WHITELIST_array)) {
		//echo "Requested hostname <b>".$testMatch."</b> not whitelist! Please control your mapbender.conf.";
		echo "Requested <b>hostName</b> not in whitelist! Please control your mapbender.conf.";

		$e = new mb_notice("Whitelist: ".HOSTNAME_WHITELIST);
		$e = new mb_notice("hostName not found in whitelist!");
		die();
	}
	$hostName = $testMatch;
	$testMatch = NULL;
}
/*if (isset($_REQUEST["orderBy"]) & $_REQUEST["orderBy"] != "") {
	$testMatch = $_REQUEST["orderBy"];
 	if (!($testMatch == 'rank' or $testMatch == 'title' or $testMatch == 'id' or $testMatch == 'date')){
		//echo 'orderBy: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>orderBy</b> is not valid (rank,title,id,date).<br/>';
		die();
 	}
	$orderBy = $testMatch;
	$testMatch = NULL;
}// else {*/
//$orderBy= 'rank';
//}
if (isset($_REQUEST["searchResources"]) & $_REQUEST["searchResources"] != "") {
	//validate to wms,wfs,wmc,georss
	$testMatch = $_REQUEST["searchResources"];
	#$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';
	$countSR = count(explode(',',$testMatch));
 	if (!($countSR >= 1 && $countSR <= 6)){
		//echo 'searchResources: <b>'.$testMatch.'</b> count of requested resources out of sync.<br/>';
		echo 'Parameter <b>searchResources</b> count of requested resource types is more than 5.<br/>';
		die();
 	} else {
		$testArray = explode(',',$testMatch);
		for($i=0; $i<count($testArray);$i++){
			if (!in_array($testArray[$i], $MD_ScopeCode)) {
			//echo 'searchResources: <b>'.$testMatch.'</b>at least one of them does not exists!<br/>';
			echo 'Parameter <b>searchResources</b> at least one of them does not exists!<br>';
			echo implode(',',$MD_ScopeCode);
			echo '<br/>';
			die();
			}
		}
		unset($i);
	}
	$searchResources = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["searchPages"]) & $_REQUEST["searchPages"] != "") {
	//validate to csv integer list with dimension of searchResources list
	$testMatch = $_REQUEST["searchPages"];
	$pattern = '/^[-\d,]*$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'searchPages: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>searchPages</b> is not valid (integer or integer csv).<br/>';
		die();
 	}
	if (count(explode(',',$testMatch)) != count(explode(',',$searchResources))) {
		//echo 'searchPages: <b>'.$testMatch.'</b> has a wrong amount of entries.<br/>';
		echo 'Parameter <b>searchPages</b> has a wrong amount of entries.<br/>';
		die();
	}
	$searchPages = $testMatch;
	$testMatch = NULL;
#$searchPages = $_REQUEST["searchPages"];
	#$searchPages = split(',',$searchPages);

}
if (isset($_REQUEST["resultTarget"]) & $_REQUEST["resultTarget"] != "") {
	//validate to web,debug,file
	$testMatch = $_REQUEST["resultTarget"];
 	if (!($testMatch == 'web' or $testMatch == 'debug' or $testMatch == 'file'  or $testMatch == 'webclient' or $testMatch == 'internal' or $testMatch == "categories")){
		//echo 'resultTarget: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>resultTarget</b> is not valid (file,web,debug,webclient,internal,categories).<br/>';
		die();
 	}
	$resultTarget = $testMatch;
	$testMatch = NULL;
}
//$e = new mb_exception("UserID GET: ".$_REQUEST['userId']);
//$e = new mb_exception("UserID from session (new): ".Mapbender::session()->get("mb_user_id"));
//$e = new mb_exception("UserID from session (old): ".$_SESSION['mb_user_id']);

/*if (isset($_REQUEST["userId"]) & $_REQUEST["userId"] != "") {
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
#
} else { //look for id in session
  $userId = Mapbender::session()->get("mb_user_id");
  if ($userId == false) {
	  $userId = PUBLIC_USER;
    }
}
*/
#$searchResources = array('wms','wfs','wmc','georss');
#$searchPages = array(1,1,1,1);

//TODO: if class is called directly

if ($resultTarget == 'debug') {
	echo "<br>DEBUG: searchURL: ".$searchURL."<br>";
	#echo "<br>DEBUG: languageCode: ".$languageCode."<br>";
}


if ($resultTarget == 'file' or $resultTarget == 'webclient') {
	if (!isset($searchResources) OR ($searchResources == "")) {
		$searchResources = $dummySearchResources;
		$resolveCoupledResources = true;
		$searchPages = $dummySearchPages;
	}

}
if (!isset($searchPages) OR ($searchPages == "") or count(explode(",",$searchPages)) !== count(explode(",",$searchResources))) {
	for($i=0;$i<count(explode(",",$searchResources));$i++) {
		$searchPages[$i] = 1;
	}
	$searchPages = implode(",",$searchPages);
	//$e = new mb_exception("mod_callMetadata.php: set searchPages to :".$searchPages);
}
if ($resultTarget == 'web' or $resultTarget == 'debug') {
	if (!isset($searchResources) OR ($searchResources == "")) {
		$searchResources = "dataset";
		$searchPages = "1";
	}
}

//convert the respources and the pagenumbers into arrays
$searchResourcesArray = explode(",",$searchResources);
$searchPages = explode(",",$searchPages);

//$originFromHeader - maybe alternative to referer $_SERVER['HTTP_REFERER']
if (DEFINED("SEARCH_LOG") && SEARCH_LOG === true) {
    $admin = new administration();
    $admin->logSearchInterfaceUsage ($_SERVER['HTTP_REFERER'], $searchURL, $searchText, $_SERVER['HTTP_USER_AGENT'], $catalogueId);
}

/*
Function to get the right service access url from an array of urls which was found in a metdata record
*/
function getServiceUrl($mdServiceType, $mdServiceTypeVersion, $accessUrls) {
	if (is_array($accessUrls)) {
		if (in_array(strtoupper($mdServiceType), array('VIEW','OGC:WMS','WMS','PREDEFINED ATOM','DOWNLOAD','WFS','ATOM'))) {
			if (in_array(strtoupper($mdServiceType), array('PREDEFINED ATOM','DOWNLOAD','WFS','ATOM')) || in_array(strtoupper($mdServiceTypeVersion), array('PREDEFINED ATOM','DOWNLOAD','WFS','ATOM'))) {
				if (in_array(strtoupper($mdServiceType), array('PREDEFINED ATOM','ATOM')) || in_array(strtoupper($mdServiceTypeVersion), array('PREDEFINED ATOM','ATOM'))) {
					//return first entry as atom feed access url
					return $accessUrls[0];
				} else {
					//check for WFS
					foreach ($accessUrls as $url) {
						$pos = strpos(strtolower($url), 'service=wfs');
						if ($pos !== false) {
							$accessUrl = $url; 
							$accessUrlFound = true;
							break;
						}
					}
					if ($accessUrlFound) {
						return $accessUrl;
					} else {
						return $accessUrls[0];
					}
				}
			} else {
				//check for WMS
				foreach ($accessUrls as $url) {
					$pos = strpos(strtolower($url), 'service=wms');
					if ($pos !== false) {
						$accessUrl = $url; 
						$accessUrlFound = true;
						break;
					}
				}
				if ($accessUrlFound) {
					return correctWmsUrl($accessUrl);
				} else {
					return correctWmsUrl($accessUrls[0]);
				}
			}
		} else {
			if ($accessUrls == "" || count($accessUrls) == 0) {
				return null;
			} else {
				return $accessUrls[0];
			}
		}
	} else {
		//only one option ;-)
		return (string)$accessUrls;
	}
}


//************************************************************************************
//build query - one solution is combining all textfilter with single <And> tags, other solution maybe like filter as "*searchText1*searchText2*searchText3*"
//single leads to a lower amount of results ! - mmh - how does the filter will be evaluated?
//example: "*wald*baum*" on anytext -> 3 results in 1 sec || <And> combined "*wald*" and "*baum*" -> 102 results in 2.1 sec (geonetwork 3.6.0)
//************************************************************************************
//$queryString  = $searchText;
$searchTextArray = explode(",",$searchText);
$combineTextOption = "multiple"; //"multiple","single"
switch ($combineTextOption) {
    case "single":
        $fullTextFilter .= '<ogc:PropertyIsLike wildCard="*" singleChar="_" escapeChar="/">';
	$fullTextFilter .= '<ogc:PropertyName>AnyText</ogc:PropertyName>';
	if (count($searchTextArray) > 1) {
	    $fullTextFilter .= '<ogc:Literal>*'.implode('*', $searchTextArray).'*</ogc:Literal>';
	} else {
	    $fullTextFilter .= '<ogc:Literal>*'.$searchTextArray[0].'*</ogc:Literal>';
	}
	//$fullTextFilter .= '<ogc:Literal>%'.implode('%', $searchTextArray).'%</ogc:Literal>';
	$fullTextFilter .= '</ogc:PropertyIsLike>';
	break;
    case "multiple":
	if (count($searchTextArray) > 1) {
	    $fullTextFilter = "<ogc:And>";
	} else {
	    $fullTextFilter = "";
	}
	//$e = new mb_exception($searchTextArray[0]."".gettype($searchTextArray));
	foreach($searchTextArray as $queryString) {
	    //$e = new mb_exception("querystring: ".$queryString);
	    $fullTextFilter .= '<ogc:PropertyIsLike wildCard="*" singleChar="_" escapeChar="/">';
	    $fullTextFilter .= '<ogc:PropertyName>AnyText</ogc:PropertyName>';
	    /*if($queryString != '*'){
		$queryString = '*' . explode($queryString) . '*';
	    }*/
	    //$queryString = '*' . implode("*", $searchTextArray) . '*';
	    if ($queryString == "*") {
	        $fullTextFilter .= '<ogc:Literal>*</ogc:Literal>';
	    } else {
	        $fullTextFilter .= '<ogc:Literal>*'.$queryString.'*</ogc:Literal>';
	    }
	    $fullTextFilter .= '</ogc:PropertyIsLike>';
	}
	if (count($searchTextArray) > 1) {
	    $fullTextFilter .= "</ogc:And>";
	}
	break;
}
//$e = new mb_exception("fulltextfilter: ".$fullTextFilter);
$existsSpatialFilter = false;
//$e = new mb_exception("searchBbox: ".$searchBbox);
//$e = new mb_exception("searchTypeBbox: ".$searchTypeBbox);
$searchBboxArray = explode(",", $searchBbox);
//TODO: test the right filter! - inspire has other ones and no logical option!
if ($searchBbox !== NULL) {
    $existsSpatialFilter = true;
    switch ($searchTypeBbox) {
	case "intersects":
		$spatialFilter .= "<ogc:BBOX>";
	break;
	case "inside":
		$spatialFilter .= "<ogc:Within>";
	break;
	case "outside":
		$spatialFilter .= "<ogc:Disjoint>";
	break;
	default:
		$spatialFilter .= "<ogc:BBOX>";
    }
    $spatialFilter .= "<ogc:PropertyName>BoundingBox</ogc:PropertyName>";

    /*$spatialFilter .= '<gml:Box xmlns:gml="http://www.opengis.net/gml" srsName="EPSG:4326">';
    $spatialFilter .= '<gml:coordinates decimal="." cs="," ts=" ">';
    $spatialFilter .= $searchBboxArray[0].','.$searchBboxArray[1].' '.$searchBboxArray[2].','.$searchBboxArray[3];
    $spatialFilter .= '</gml:coordinates></gml:Box>';*/  

    //Exchanged to support INSPIRE Discovery service: https://ies-svn.jrc.ec.europa.eu/issues/3658
    $spatialFilter .= '<gml:Envelope srsName="EPSG:4326">';
    $spatialFilter .= '<gml:lowerCorner>'.$searchBboxArray[0].' '.$searchBboxArray[1].'</gml:lowerCorner>';
    $spatialFilter .= '<gml:upperCorner>'.$searchBboxArray[2].' '.$searchBboxArray[3].'</gml:upperCorner>';
    $spatialFilter .= '</gml:Envelope>';

    switch ($searchTypeBbox) {
	case "intersect":
		$spatialFilter .= "</ogc:BBOX>";
	break;
	case "inside":
		$spatialFilter .= "</ogc:Within>";
	break;
	case "outside":
		$spatialFilter .= "</ogc:Disjoint>";
	break;
	default:
		$spatialFilter .= "</ogc:BBOX>";
    }
}
//combine filter
//$e = new mb_exception("spatialFilter: ".$spatialFilter);
//TODO: inspire spatial filter only supported, if no other filter is set!!!! see https://ies-svn.jrc.ec.europa.eu/issues/3658
//if spatial filter and some text filter is set, combine them with and
if ($existsSpatialFilter == true) {
    //some textfilter is given
    if ($searchText !== '' && $searchText !== '*') {
    	$additionalFilter = "<ogc:And>".$fullTextFilter.$spatialFilter."</ogc:And>";
    } else {
	//no textfilter is given
	$additionalFilter = $spatialFilter;
    }
} else {
    //no spatial filter is given
    if ($searchText !== '' && $searchText !== '*') {
        $additionalFilter = $fullTextFilter;
    } else {
	$additionalFilter = false;
    }
}
//$e = new mb_exception("additionalFilter: ".$additionalFilter);

$csw = new csw();
$csw->createCatObjFromDB($catalogueId);

if (is_null($csw->cat_id)) {
	echo "Catalogue with id ".$catalogueId." not found in mapbender database!";
	die();
}
$cswClient = new cswClient();
$cswClient->cswId = $catalogueId;
//$e = new mb_exception("invoke get record by id");
//$e = new mb_exception("catalogue id = ".$cswId);
//map paging to results

$recordType = $searchResources;

//log start time for counting elements via csw
//$countCswStartTime = microtime_float();

//first count all hits for filter:
//$cswResponseObject = $cswClient->doRequest($cswClient->cswId, 'counthits', false, false, $recordType, false, false, $additionalFilter);

//$e = new mb_exception("Number of type ".$recordType." datasets in portal CSW: ".$cswClient->operationResult);
//echo "Number of type ".$recordType." datasets in portal CSW: ".$cswClient->operationResult."<br>";
//$usedCountCswTime = microtime_float() - $countCswStartTime;
//$maxRecords = (integer)$cswClient->operationResult;
//$pages = ceil($maxRecords / $maxResults);
//$e = new mb_exception("pages: ".$pages);
$metadataArray = array();
$numberOfMetadataRecords = 0;
//$cswResponseObject = $cswClient->doRequest($cswClient->cswId, 'getrecords', $fileIdentifier, false, false, false, false, $additionalFilter);
//parse XML

//$e = new mb_exception("maxResults: ".$maxResults);

//log start time of distributed metadatdata search via csw interface
$searchCswStartTime = microtime_float();


$resultObject = new stdClass;
//define object for dynamic filtering - actually only for searchResources

$queryJSON = new stdClass;
$queryJSON->searchFilter = (object) array();
$queryJSON->searchFilter->origURL = $searchURL;


//define where to become the information from - this is relevant for the information which must be pulled out of the database
$classificationElements = array();
$classificationElements[0]['name'] = 'searchText';
$classificationElements[1]['name'] = 'searchBbox';
$classificationElements[2]['name'] = 'searchResources';

$classificationElements[0]['source'] = '';
$classificationElements[1]['source'] = '';
$classificationElements[2]['source'] = '';

$classificationElements[0]['list'] = true;
$classificationElements[1]['list'] = false;
$classificationElements[2]['list'] = true;

switch($languageCode){
        case 'de':
        	$classificationElements[0]['name2show'] = 'Suchbegriff(e):';
		$classificationElements[1]['name2show'] = 'Räumliche Einschränkung:';
		$classificationElements[2]['name2show'] = 'Art der Ressource:';

		$resourceCategories['dataset'] = 'Datensätze';
		$resourceCategories['spatialData'] = 'Geodaten';
		$resourceCategories['series'] = 'Datensatzserien';
		$resourceCategories['tile'] = 'Datensatzteile';
		$resourceCategories['application'] = 'Anwendungen';
		$resourceCategories['service'] = 'Dienste';
		$resourceCategories['nonGeographicDataset'] = 'Daten ohne Raumbezug';

		$orderByTitle['header'] = 'Sortierung nach:';
		$orderByTitle['id'] = 'Identifizierungsnummer';
		$orderByTitle['title'] = 'Alphabetisch';
		$orderByTitle['rank'] = 'Nachfrage';
		$orderByTitle['date'] = 'Letzte Änderung';

		$maxResultsTitle['header'] = 'Treffer pro Seite:';


       	break;
        case 'en':
        	$classificationElements[0]['name2show'] = 'Search Term(s):';
		$classificationElements[1]['name2show'] = 'Spatial Filter:';
		$classificationElements[2]['name2show'] = 'Kind of resource:';

		$resourceCategories['dataset'] = 'Datasets';
		$resourceCategories['spatialData'] = 'Spatial data';
		$resourceCategories['series'] = 'Datasetseries';
		$resourceCategories['tile'] = 'Datasetstiles';
		$resourceCategories['application'] = 'Applications';
		$resourceCategories['service'] = 'Services';
		$resourceCategories['nonGeographicDataset'] = 'Data without spatial dimension';

		$orderByTitle['header'] = 'Sort by:';
		$orderByTitle['id'] = 'identification number';
		$orderByTitle['title'] = 'alphabetically';
		$orderByTitle['rank'] = 'demand';
		$orderByTitle['date'] = 'last change';

		$maxResultsTitle['header'] = 'Results per page:';

        break;
        case 'fr':
        	$classificationElements[0]['name2show'] = 'Mots clés:';
		$classificationElements[1]['name2show'] = 'Requête spatiale:';
		$classificationElements[2]['name2show'] = 'Art der Ressource:';

		$resourceCategories['dataset'] = 'Datasets';
		$resourceCategories['spatialData'] = 'Spatial data';
		$resourceCategories['series'] = 'Datasetseries';
		$resourceCategories['tile'] = 'Datasetstiles';
		$resourceCategories['application'] = 'Applications';
		$resourceCategories['service'] = 'Services';
		$resourceCategories['nonGeographicDataset'] = 'Data without spatial dimension';

		$orderByTitle['header'] = 'classé selon:';
		$orderByTitle['id'] = 'numéro d\'identification';
		$orderByTitle['title'] = 'par ordre alphabétique';
		$orderByTitle['rank'] = 'vue';
		$orderByTitle['date'] = 'mise à jour';

		$maxResultsTitle['header'] = 'Résultat par page:';

       	break;
     	default:
        	$classificationElements[0]['name2show'] = 'Suchbegriff(e):';
		$classificationElements[1]['name2show'] = 'Räumliche Einschränkung:';
		$classificationElements[2]['name2show'] = 'Art der Ressource:';

		$resourceCategories['dataset'] = 'Datasets';
		$resourceCategories['spatialData'] = 'Spatial data';
		$resourceCategories['series'] = 'Datasetseries';
		$resourceCategories['tile'] = 'Datasetstiles';
		$resourceCategories['application'] = 'Applications';
		$resourceCategories['service'] = 'Services';
		$resourceCategories['nonGeographicDataset'] = 'Data without spatial dimension';

		$orderByTitle['header'] = 'Sortierung nach:';
		$orderByTitle['id'] = 'ID';
		$orderByTitle['title'] = 'Titel';
		$orderByTitle['rank'] = 'Relevanz';
		$orderByTitle['date'] = 'Letzte Änderung';

		$maxResultsTitle['header'] = 'Results per page:';

}

//generate search filter file - if more categories are defined give
//echo "<br> number of filter elements: ".count($classificationElements)."<br>";
for($i=0; $i < count($classificationElements); $i++){
	//echo "<br> filter for element: ".$classificationElements[$i]['name']."<br>";
	//echo "<br> variable for element: ". (string)${$classificationElements[$i]['name']}."<br>";
	if (isset(${$classificationElements[$i]['name']}) & ${$classificationElements[$i]['name']} !='' & ${$classificationElements[$i]['name']} != NULL) {
		//echo "<br> found: ".$classificationElements[$i]['name']."<br>";
		//pull register information out of database in arrays
		$queryJSON->searchFilter->{$classificationElements[$i]['name']}->title = $classificationElements[$i]['name2show'];
		//check if the filter has subfilters - if not delete the whole filter from query
		if ($classificationElements[$i]['list'] == false) { //the object has no subsets - like bbox or time filters
			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->delLink = delTotalFromQuery($classificationElements[$i]['name'],$searchURL);
			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item = array();
			if ($classificationElements[$i]['name'] == 'searchBbox') {
				$sBboxTitle = $searchTypeBbox." ".${$classificationElements[$i]['name']};
				$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[0]->title = $sBboxTitle;
			}
			else {
			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[0]->title = ${$classificationElements[$i]['name']};
			}
			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[0]->delLink = delTotalFromQuery($classificationElements[$i]['name'],$searchURL);
		} else {


			//$e = new mb_exception('mod_callMetadata.php: $classificationElements[$i][name]: '.$classificationElements[$i]['name']);
			//TODO delete all entries of this main category (not for searchText)

			//$queryJSON->searchFilter->{$classificationElements[$i]['name']}->delLink = NULL;
			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->delLink = delTotalFromQuery($classificationElements[$i]['name'],$searchURL);
			//$e = new mb_exception('mod_callMetadata.php: dellink: '.$queryJSON->searchFilter->{$classificationElements[$i]['name']}->delLink);

			$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item = array();

			$queryArray = explode(',', ${$classificationElements[$i]['name']});

			//loop for the subcategories
			for($j=0; $j < count($queryArray); $j++){
				//$e = new mb_exception('mod_callMetadata.php: queryArrayi: '.$queryArray[$j]);

				if ($classificationElements[$i]['name'] != 'searchResources') {
					$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[$j]->title = $queryArray[$j];
				} else {
					$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[$j]->title = $resourceCategories[$queryArray[$j]];
				}

				//generate links to disable filters on a simple way
				if (($classificationElements[$i]['name'] === 'searchText' || $classificationElements[$i]['name'] === 'searchResources') & count(explode(',',${$classificationElements[$i]['name']})) === 1) {
					//$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[$j]->delLink = NULL;
					$newSearchLink = delFromQuery($classificationElements[$i]['name'], $searchURL,$queryArray[$j],$queryArray,${$classificationElements[$i]['name']});
					$newSearchLink = delTotalFromQuery('searchId',$newSearchLink);
					$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[$j]->delLink = $newSearchLink;
				} else {
					$newSearchLink = delFromQuery($classificationElements[$i]['name'], $searchURL,$queryArray[$j],$queryArray,${$classificationElements[$i]['name']});
					$newSearchLink = delTotalFromQuery('searchId',$newSearchLink);
					$queryJSON->searchFilter->{$classificationElements[$i]['name']}->item[$j]->delLink = $newSearchLink;
				}
			}
		}
	}
}

//get the first page to also count the available records:
$i = 0;
foreach ($searchResourcesArray as $searchResource) {

	//$queryJSON->searchFilter->classes[$i]->title = $resourceCategories[$searchResourcesArray[$i]];
	//$queryJSON->searchFilter->classes[$i]->name = $searchResourcesArray[$i];

//echo $searchResource;
//for ($i = 0; $i < 1 ; $i++) {
//for ($i = 0; $i <= $pages-1 ; $i++) {
	//$cswClient = new cswClient();
	//$cswClient->cswId = $catalogueId;
    $startPos = ($searchPages[$i]-1) * $maxResults + 1;
	$result = $cswClient->doRequest($cswClient->cswId, 'getrecordspaging', false, false, $searchResource, $maxResults, $startPos, $additionalFilter);
	//$page = $i + 1;
	//$e = new mb_exception("page: ".$page." (".$pages.")");
	//$e = new mb_exception("result: ".json_encode($cswClient->operationSuccessful));
	if ($cswClient->operationSuccessful == true) {
		//extract number of records matched - for every request!
		$numberOfRecords = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/@numberOfRecordsMatched');
		$numberOfRecords = $numberOfRecords[0];
		$maxRecords = (integer)$numberOfRecords;
		$maxPages = ceil($maxRecords / $maxResults);
		//$e = new mb_exception("number of records matched: ".$numberOfRecords);
		//$e = new mb_exception("operation successfull");
		//$e = new mb_exception(gettype($cswClient->operationResult));
		$metadataRecord = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
		//$e = new mb_exception("number of records: ".count($metadataRecord));
		//what is possible: keywords, categories?, spatial, ...
	        $usedSearchCswTime = microtime_float() - $searchCswStartTime;

		$resultObject->{$searchResource}->md->nresults = $maxRecords;
		$resultObject->{$searchResource}->md->p = $searchPages[$i];
		$resultObject->{$searchResource}->md->rpp = $maxResults;
		$resultObject->{$searchResource}->md->genTime = $usedSearchCswTime;

		$parsingMetadataStartTime = microtime_float();

		for ($k = 1; $k <= count($metadataRecord) ; $k++) {
			$fileIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:fileIdentifier/gco:CharacterString');
			$fileIdentifier = (string)$fileIdentifier[0];
			//$e = new mb_exception("id: ".$k." - fileIdentifier: ".$fileIdentifier);
			$resultObject->{$searchResource}->srv[$k-1]->id = $fileIdentifier;
			$mdDateStamp = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:dateStamp/gco:Date');
			if(empty($mdDateStamp)){
				$mdDateStamp = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:dateStamp/gco:DateTime');
			}
			$mdDateStamp = (string)$mdDateStamp[0];
			$resultObject->{$searchResource}->srv[$k-1]->date = $mdDateStamp;
			/*	WORKAROUND TO allow wrong metadata :-(
			switch ($searchResource) {
				case "service":
					$identifikationXPath = "srv:SV_ServiceIdentification";
					break;
				default:
					$identifikationXPath = "gmd:MD_DataIdentification";
					break;
			}
			*/
			$identifikationXPath = "";
			if ($searchResource == 'dataset' || $searchResource == 'series') {
			    $datasetId = 'undefined';
			    $datasetIdCodeSpace = 'undefined';
				//dataset identifier
				$code = $cswClient->operationResult->xpath('///gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/gco:CharacterString');
				if (isset($code[0]) && $code[0] != '') {
					//new implementation:
					//http://inspire.ec.europa.eu/file/1705/download?token=iSTwpRWd&usg=AOvVaw18y1aTdkoMCBxpIz7tOOgu
					//from 2017-03-02 - the MD_Identifier - see C.2.5 Unique resource identifier - it is separated with a slash - the codespace should be everything after the last slash 
					//now try to check if a single slash is available and if the md_identifier is a url
					$parsedUrl = parse_url($code[0]);
					if (($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') && strpos($parsedUrl['path'],'/') !== false) {
						$explodedUrl = explode('/', $code[0]);
						$datasetId = $explodedUrl[count($explodedUrl) - 1];
						$datasetIdCodeSpace = rtrim($code[0], $datasetId);	
					} else {
						if (($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') && strpos($code[0],'#') !== false) {
							//$e = new mb_exception($code[0]);
							$explodedUrl = explode('#', $code[0]);
							$datasetId = $explodedUrl[1];
							$datasetIdCodeSpace = $explodedUrl[0];
						} else {
							$datasetId = $code[0];
							$datasetIdCodeSpace = "";	
						}
					}
				} else { //try to read code from RS_Identifier 		
					$code = $cswClient->operationResult->xpath('///gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:RS_Identifier/gmd:code/gco:CharacterString');
					$codeSpace = $cswClient->operationResult->xpath('gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:RS_Identifier/gmd:codeSpace/gco:CharacterString');
					if (isset($codeSpace[0]) && isset($code[0]) && $codeSpace[0] != '' && $code[0] != '') {
						$datasetId = $code[0];
						$datasetIdCodeSpace = $codeSpace[0];
					} else {
					    if (isset($code[0]) && $code[0] != '') {
					        $datasetId = $code[0];
					        $datasetIdCodeSpace = "";
					    }
						//neither MD_Identifier nor RS_Identifier are defined in a right way
						$e = new mb_notice("class_iso19139.php: No datasetId found in metadata record!");
					}
				}
				$resultObject->{$searchResource}->srv[$k-1]->datasetId = $datasetIdCodeSpace.$datasetId;
			}
			//$resultObject->{$searchResource}->srv[$k-1]->datasetId = "test";
			//preview image if available
			$previewImage = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:graphicOverview/gmd:MD_BrowseGraphic/gmd:fileName/gco:CharacterString');

			if (is_array($previewImage) && $previewImage[0] !== '') {
				$previewImage = (string)$previewImage[0];
			} else {
				$previewImage = null;
			}

			$resultObject->{$searchResource}->srv[$k-1]->previewUrl = $previewImage;
			//organization name
			$resourceResponsibleParty = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:organisationName/gco:CharacterString');
			$resourceResponsibleParty = $resourceResponsibleParty[0];
            $resultObject->{$searchResource}->srv[$k-1]->respOrg = (string)$resourceResponsibleParty;
			//box
            $minx = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude/gco:Decimal');
            $minx = $minx[0];
            $miny = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude/gco:Decimal');
            $miny = $miny[0];
            $maxx = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude/gco:Decimal');
            $maxx = $maxx[0];
            $maxy = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude/gco:Decimal');
            $maxy = $maxy[0];
            $resultObject->{$searchResource}->srv[$k-1]->bbox = implode(',', array($minx,$miny,$maxx,$maxy)); 
			//title
			$title = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString');
			$title = $title[0];
            $resultObject->{$searchResource}->srv[$k-1]->title = (string)$title;
			//abstract
			$abstract = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/'.$identifikationXPath.'/gmd:abstract/gco:CharacterString');
			$abstract = $abstract[0];
            $resultObject->{$searchResource}->srv[$k-1]->abstract = substr((string)$abstract,0,250);
			//mdLink
			//geturl for get recordbyid request
			if (isset($csw->cat_op_values['getrecordbyid']['get'])) {
				$resultObject->{$searchResource}->srv[$k-1]->mdLink = $csw->cat_op_values['getrecordbyid']['get']."?request=GetRecordById&service=CSW&version=2.0.2&Id=".str_replace("{","",str_replace("}","",$fileIdentifier))."&ElementSetName=full&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd";
				//$e = new mb_exception("url: ".$k." - getrecordbyid: ".$resultObject->{$searchResource}->srv[$k-1]->mdLink);
				//we have problems with fileIdentifier of from {XXX} from inspire portal - those cannot be pulled via mdLink - the {} have to be removed!
			}
			//service type
			$typeOfService = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceType/gco:LocalName');
			$typeOfService = $typeOfService[0];
			//service type version
			$typeOfServiceVersion = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceTypeVersion/gco:CharacterString');
			$typeOfServiceVersion = $typeOfServiceVersion[0]; //predefined ATOM, ...
			//service access url
			//first read the inspire kind of implementation of the access to capabilities documents
			$accessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
			
			if ($searchResource == 'service') {
				$accessUrl = getServiceUrl($typeOfService, $typeOfServiceVersion, $accessUrl);
			}
			$e = new mb_exception("accessUrl 1: ". json_encode($accessUrl));
			if ($accessUrl == '' || $accessUrl == null) {
				//search for another accessUrl - as defined in csw ap iso
				$accessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				$accessUrl = getServiceUrl($typeOfService, $typeOfServiceVersion, $accessUrl);
			}
			$isViewService = false;
			$isDownloadService = false;
			$typeOfServiceUpper = strtoupper($typeOfService);
			//check for view service type
			if ($typeOfServiceUpper == 'WMS' || $typeOfServiceUpper == 'VIEW'  || strpos($typeOfServiceUpper,'WMS') !== false) {
				$isViewService = true;
				//echo "view service identified<br>";
			}
			if ($typeOfServiceUpper == 'DOWNLOAD' || $typeOfServiceUpper == 'ATOM'  || strpos($typeOfServiceUpper,'PREDEFINED ATOM') !== false) {
				$isDownloadService = true;
				//echo "view service identified<br>";
			}
			if ($isViewService == true) {
				$resultObject->{$searchResource}->srv[$k-1]->showMapUrl = $accessUrl;
			}
			//check accessUrl for dls later

			if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				$scheme = "https";
			} else {
				$scheme = "http";
			}
			if ($isDownloadService == true && strtoupper($typeOfServiceVersion) == 'PREDEFINED ATOM' ) {
				$resultObject->{$searchResource}->srv[$k-1]->downloadFeedClientUrl = $scheme.'://'.$hostName.str_replace("php/".basename($_SERVER['SCRIPT_NAME']), "plugins/mb_downloadFeedClient.php", $_SERVER['PHP_SELF'])."?url=".urlencode($accessUrl);
			}
			//html view
			$resultObject->{$searchResource}->srv[$k-1]->htmlLink = $scheme.'://'.$hostName.str_replace(basename($_SERVER['SCRIPT_NAME']), "mod_exportIso19139.php", $_SERVER['PHP_SELF'])."?url=".urlencode($resultObject->{$searchResource}->srv[$k-1]->mdLink)."&resolveCoupledResources=true";
			//service urls if available
			//type of service
			//inspire url for service
			$url =  $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
			$url = (string)$url[0];
			if (isset($url) && $url !=="") {
				//$metadataArray[$numberOfMetadataRecords]['uuid'] = $datasetIdentifier;
				$metadataArray[$numberOfMetadataRecords]['uuid'] = $fileIdentifier;
				$metadataArray[$numberOfMetadataRecords]['changedate'] = $mdDateStamp;
				$numberOfMetadataRecords++;
			}
		}
		$usedParsingMetadataTime = microtime_float() - $parsingMetadataStartTime;
		//echo "Count time: ".$usedCountCswTime."<br>";
		//echo "Search time: ".$usedSearchCswTime."<br>";
		//echo "Parsing time: ".$usedParsingMetadataTime."<br>";
	}
	if (count($metadataRecord) == 0) {
		$resultObject->{$searchResource}->md->nresults = 0;
		$resultObject->{$searchResource}->md->p = $searchPages[$i];
		$resultObject->{$searchResource}->md->rpp = $maxResults;
		$resultObject->{$searchResource}->md->genTime = $usedSearchCswTime;
		$resultObject->{$searchResource}->srv = array();
	}
        $i++;
}
//Problem portugal metadata json_encode
//https://stackoverflow.com/questions/23652082/json-encode-issue-when-parsing-array-with-portuguese-character-in-php - use: JSON_PARTIAL_OUTPUT_ON_ERROR
//add filter object
$resultObject->searchFilter = $queryJSON->searchFilter;
header('Content-Type: application/json');
echo json_encode($resultObject, JSON_PARTIAL_OUTPUT_ON_ERROR);
//}
//************************************************************************************
//functions
//************************************************************************************
//function to delete one of the comma separated values from one get request
function delFromQuery($paramName,$queryString,$string,$queryArray,$queryList) {
	global $dummySearchResources;
	global $dummySearchPages;
	//check if if count searchArray = 1
	if (count($queryArray) == 1){
		//remove request parameter from url by regexpr or replace
		$str2search = $paramName."=".$queryList;
		if ($paramName == "searchText") {
			$str2exchange = "searchText=*&";
		} else {
			$str2exchange = "";
		}
		/*if ($paramName == "searchResources") {
			$str2exchange = "searchResources=".$dummySearchResources."&";

		} else {
			$str2exchange = "";
		}*/
		$queryStringNew = str_replace($str2search, $str2exchange, $queryString);
		$queryStringNew = str_replace("&&", "&", $queryStringNew);
	} else {
	//there are more than one filter - reduce the filter
		$objectList = "";
		for($i=0; $i < count($queryArray); $i++){
			if ($queryArray[$i] != $string){
				$objectList .= $queryArray[$i].",";
			}
		}
		//remove last comma
		$objectList = rtrim($objectList, ",");
		$str2search = $paramName."=".$queryList;
		//echo "string to search: ".$str2search."<br>";
		$str2exchange = $paramName."=".$objectList;
		//echo "string to exchange: ".$str2exchange."<br>";
		$queryStringNew = str_replace($str2search, $str2exchange, urldecode($queryString));
	}
	return $queryStringNew;
}

//function to remove one complete get param out of the query
function delTotalFromQuery($paramName,$queryString) {
	global $dummySearchResources;
	global $dummySearchPages;
	//echo $paramName ."<br>";
	$queryString = "&".$queryString;
	if ($paramName == "searchText") {
			$str2exchange = "searchText=*&";
		} else {
			$str2exchange = "";
	}
	/*if ($paramName == "searchResources") {
		$str2exchange = "searchResources=".$dummySearchResources."&";
	} */
	$queryStringNew = preg_replace('/\b'.$paramName.'\=[^&]*&?/',$str2exchange,$queryString); //TODO find empty get params
	$queryStringNew = ltrim($queryStringNew,'&');
	$queryStringNew = rtrim($queryStringNew,'&');
	return $queryStringNew;
}
//delete all string entries from array
function deleteEntry($arrayname, $entry) {
	$n = $arrayname.length;
	for($i=0; $i<($n+1); $i++){
		if ($arrayname[$i] == $entry) {
			$arrayname.splice($i, 1);
		}
	}
	return $arrayname;
}
function correctWmsUrl($wms_url) {
	//check if last sign is ? or & or none of them
	$lastChar = substr($wms_url,-1);
	//check if getcapabilities is set as a parameter
	$findme = "getcapabilities";
	$posGetCap = strpos(strtolower($wms_url), $findme);
	if ($posGetCap === false) {
		$posGetAmp = strpos(strtolower($wms_url), "?");
		if ($posGetAmp === false) {
			$wms_url .= "?REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
		} else {
			switch ($lastChar) {
				case "?":
					$wms_url .= "REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
				break;
				case "&":
					$wms_url .= "REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
				break;
				default:
					$wms_url .= "&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
				break;
			 }
		}
	} else {
		//check if version is defined
		$findme1 = "version=";
		$posVersion = strpos(strtolower($wms_url), $findme1);
		if ($posVersion === false) {
			$wms_url .= "&VERSION=1.1.1";
		} else {
			//mapbender only handle 1.1.1
			$wms_url = str_replace('version=1.3.0', 'VERSION=1.1.1', $wms_url);
			$wms_url = str_replace('VERSION=1.3.0', 'VERSION=1.1.1', $wms_url);
		}

	}

	//exchange &? with & and &amp;
	$wms_url = str_replace('&?', '&', $wms_url);
	$wms_url = str_replace('&amp;?', '&', $wms_url);
	$wms_url = str_replace('&amp;', '&', $wms_url);
return $wms_url;
}
function isValidURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}
function microtime_float() {
    	list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
}

?>
