<?php
/*Central module, that initialize the mapbender client (GUI) mapset. The initilization is done using the OGC WMC standard.
The module first tries to read the actual mapset from session information, if this is defined in the GUI element 'loadWmc'
If no session wmc is found, the module reads the mapset from database default GUI configuration.
The module also handles the management of initial GET-Parameter: https://mb2wiki.mapbender2.org/GET-Parameter
*/
require_once dirname(__FILE__)."/../php/mb_validateSession.php";
require_once dirname(__FILE__)."/../classes/class_wmc.php";
require_once dirname(__FILE__)."/../classes/class_wmc_factory.php";
require_once dirname(__FILE__)."/../classes/class_administration.php";
require_once dirname(__FILE__)."/../../lib/class_GetApi.php";
require_once(dirname(__FILE__) . "/../classes/class_bbox.php");
require_once(dirname(__FILE__) . "/../classes/class_gml2.php");
require_once dirname(__FILE__)."/../classes/class_elementVar.php";
require_once(dirname(__FILE__) . "/../classes/class_tou.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_owsConstraints.php");
require_once(dirname(__FILE__)."/../classes/class_cache.php");
require_once(dirname(__FILE__)."/../classes/class_crs.php");
require_once(dirname(__FILE__)."/../classes/class_iso19139.php");
require_once(dirname(__FILE__)."/../classes/class_group.php");
require_once(dirname(__FILE__)."/../classes/class_geojson_style.php");
//require_once(dirname(__FILE__)."/../classes/class_wms_factory.php");
/*check if key param can be found in SESSION, otherwise take it from $_GET
*/
function getConfiguration ($key) {
	if (Mapbender::session()->exists($key)) {
		return Mapbender::session()->get($key);
	}
	return $_GET[$key];
}
/*for debugging purposes only
*/
function logit($text,$filename,$how){
	 if($h = fopen(LOG_DIR."/".$filename,$how)){
		$content = $text .chr(13).chr(10);
		if(!fwrite($h,$content)){
			//exit;
		}
		fclose($h);
	}
}
//https://www.geeksforgeeks.org/how-to-validate-json-in-php/
function json_validator($data) {
    if (!empty($data)) {
        @json_decode($data);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_decode($data);
        }
    }
    return false;
}
// function to delete one GET parameter totally from a query url
function delTotalFromQuery($paramName, $queryString) {
    $queryString = "&" . $queryString;
    //only delete totally if not searchText itself
    if ($paramName == "searchText") {
        $str2exchange = "searchText=*&";
    } else {
        $str2exchange = "";
    }
    $queryStringNew = preg_replace('/\b' . $paramName . '\=[^&]+&?/', $str2exchange, $queryString);
    $queryStringNew = ltrim($queryStringNew, '&');
    $queryStringNew = rtrim($queryStringNew, '&');
    return $queryStringNew;
}

$admin = new administration();
/*
Initial declaration of the return object, that handles some control of the distributed services
*/
$resultObj = array(
	"noPermission" => array(
		"message" => _mb("You as User")." '" .
			Mapbender::session()->get("mb_user_name") . "' " .
			_mb("have no authorization to access following layers."),
		"wms" => array()
	),
	"withoutId" => array(
		"message" => _mb("Following layers come from an unkown origin. There is no information about the links. They may be broken and the underlaying services may not exist anymore!"),
		"wms" => array(),
	),
	"unavailable" => array(
		"message" => _mb("The last monitoring had problems with the following layers. Maybe the underlying services will not be able to answer the requests for sometime."),
		"wms" => array()
	),
	"invalidId" => array(
		"message" => _mb("Following layers have been removed from the registry. They may be broken and the underlaying services may not exist anymore!"),
		"wms" => array()
	),
	"wmcTou" => array(
		"message" => ""
	),
	"notAccessable" => array(
		"message" => _mb("Following WebMapService is not accessable or could not be invoked").":",
		"wms" => array()
	),
);
/*
Load WMC from session or application (GUI)
*/
$e = new mb_notice("javascript/initWmcObj.php: Initialize first WMC Object");
$wmc = new wmc();
/*
activate for debugging
*/
$e = new mb_notice("javascript/initWmcObj.php: Current user name from session information: ".Mapbender::session()->get("mb_user_name"));
$app = Mapbender::session()->get("mb_user_gui"); // if gui was set!
//$e = new mb_exception("javascript/initWmcObj.php: GUI from session: ".$app);
//$wmcDocSession = Mapbender::session()->get("mb_wmc");
$wmcDocSession = false;
// check if wmc filename is in session - TODO only if should be loaded from session not else! (Module loadWMC)
//$e = new mb_exception("javascript/initWmcObj.php: Filename of WMC from session: ".Mapbender::session()->get("mb_wmc"));
/*check if WMC exists in session
*/
if(Mapbender::session()->get("mb_wmc")) {
    $wmc_filename = Mapbender::session()->get("mb_wmc");
    //$e = new mb_exception("javascript/initWmcObj.php: wmc_filename: ".$wmc_filename);
    //$time_start = microtime();
    // load it from whereever it has been stored
    $wmcDocSession = $admin->getFromStorage($wmc_filename, TMP_WMC_SAVE_STORAGE);
    //$wmcDocSession = getWmcFromStorage($wmc_filename);
    //$time_end = microtime();
    //$timediff = $time_end - $time_start;
    //$e = new mb_exception('javascript/initWmcObj.php: Time to load WMC from storage: '.$timediff. '('.TMP_WMC_SAVE_STORAGE.')');
}
try {
	$loadFromSession = new ElementVar($app, "loadwmc", "loadFromSession");
	if ($wmcDocSession && $loadFromSession->value === "1") {
	// check if session contains a wmc,
	// otherwise create a new wmc from application
		//$e = new mb_exception("javascript/initWmcObj.php: Trying to load session WMC...");
		if (!$wmc->createFromXml($wmcDocSession)) {
			$e = new mb_notice("javascript/initWmcObj.php: Loading session WMC failed.");
			$e = new mb_notice("javascript/initWmcObj.php: Creating WMC from app: ".$app);
			$wmc->createFromApplication($app);
		}
	}
	else {
		$e = new mb_notice("javascript/initWmcObj.php: Loading from session WMC disabled in loadwmc or no session WMC set.");
		$e = new mb_notice("javascript/initWmcObj.php: Creating WMC from app: ".$app);
		//$e = new mb_notice("javascript/initWmcObj.php: Before load from app");
		$wmc->createFromApplication($app);
		//$e = new mb_notice("javascript/initWmcObj.php: After load from app");
	}
}
catch (Exception $e) {
	$e = new mb_exception("javascript/initWmcObj.php: ERROR while loading WMC from session - test creating WMC from app: " . $app);
	$wmc->createFromApplication($app);
}
//*********************************************************************************************************
/*
 Check if savewmc is defined in gui and if element_var saveInSession is set to 0, if it is, delete wmc from session after initializing the client!
 */
//*********************************************************************************************************
$saveInSession = new ElementVar($app, "savewmc", "saveInSession");
//$e = new mb_exception("javascripts/initWmcObj.php: Value of element_var saveInSession of module savewmc: ".$saveInSession->value);

if ($saveInSession->value === "1") {
    $saveInSession = true;
   //$e = new mb_exception("javascripts/initWmcObj.php: wmc doc reference ".$wmc_filename." found in session and GUI: ".$app." will also save its state serverside!");
} else {
	$saveInSession = false;
	//$e = new mb_exception("javascripts/initWmcObj.php: wmc doc reference ".$wmc_filename." found in session. And GUI ".$app." will not save its state to session!");
}
//*********************************************************************************************************
/*
Check if session WMC module is defined in gui - TODO maybe do this before the other things are done!!!
*/
//*********************************************************************************************************
$e = new mb_notice("javascripts/initWmcObj.php: check if disclaimer should be set");
$sql = "SELECT COUNT(e_id) AS i FROM gui_element WHERE fkey_gui_id = $1 AND e_id = $2";
$v = array(Mapbender::session()->get("mb_user_gui"), "sessionWmc");
$t = array("s", "s");
$res = db_prep_query($sql, $v, $t);
$row = db_fetch_assoc($res);
$isSessionWmcModuleLoaded = intval($row["i"]);
$e = new mb_notice("javascripts/initWmcObj.php: check for disclaimer done");
//*********************************************************************************************************
$removeUnaccessableLayers = false;
$removeUnaccessableLayers = new ElementVar($app, "loadwmc", "removeUnaccessableLayers");
if ($removeUnaccessableLayers->success == true){
	if ($wmcDocSession && $removeUnaccessableLayers->value === "1") {
		$removeUnaccessableLayers = true;
	}
}
/* TODO: if no GET API is given then don't do the following things*******************
Create new WMC with services from GET API
https://mb2wiki.mapbender2.org/GET-Parameter
Look in wmc xml ********************************************************************
*/
//$e = new mb_exception("initWmcObj.php - debug - initial wmc xml: ".$wmc->toXml());
//die();
/*
************************************************************************************
*/
$wmcGetApi = WmcFactory::createFromXml($wmc->toXml());
//$e = new mb_exception("javascripts/initWmcObj.php: write initial wmc obj to {TMPDIR}/class_wmc0.json");//
//json_encode($wmcGetApi);//: ".json_encode($wmcGetApi));
/*if($h = fopen(TMPDIR . "/tmp_wmc0.json","w")){
    $content = json_encode($wmcGetApi) .chr(13).chr(10);
    if(!fwrite($h,$content)){
        #exit;
    }
    fclose($h);
}*/
//$e = new mb_notice("javascripts/initWmcObj.php: initial wmc doc: ".$wmc->toXml());
//$e = new mb_exception("javascripts/initWmcObj.php: initial wmc from xml: ".json_encode($wmcGetApi));
//die();
$options = array();
if (Mapbender::session()->exists("addwms_showWMS")) {
	$options["show"] = intval(Mapbender::session()->get("addwms_showWMS"));
}
if (Mapbender::session()->exists("addwms_zoomToExtent")) {
	$options["zoom"] = !!Mapbender::session()->get("addwms_zoomToExtent");
}
$getParams = array(
	"WMC" => getConfiguration("WMC"),
	"WMS" => getConfiguration("WMS"),
	"DATASETID" => getConfiguration("DATASETID"),
	"LAYER" => getConfiguration("LAYER"),
	"FEATURETYPE" => getConfiguration("FEATURETYPE"),
	"GEORSS"=>getConfiguration("GEORSS"),
	"KML"=>getConfiguration("KML"),
	"GEOJSON"=>getConfiguration("GEOJSON"),
	"GEOJSONZOOM"=>getConfiguration("GEOJSONZOOM"),
	"GEOJSONZOOMOFFSET"=>getConfiguration("GEOJSONZOOMOFFSET"),
	"ZOOM"=>getConfiguration("ZOOM"),
    "NONEDEFAULTWMC"=>getConfiguration("NONEDEFAULTWMC")
);
$getApi = new GetApi($getParams);
/*
WMC ID
*/
$startWmcId = false;
$e = new mb_notice("javascript/initWmcObj.php: Check WMC GET API");
$inputWmcArray = $getApi->getWmc();
$noneDefaultWmc = $getApi->getNoneDefaultWmc();
if (is_numeric($noneDefaultWmc)) {
    //some noneDefaultWmc is set - this has presedence for standard wmc!
    try {
        $wmcGetApi = WmcFactory::createFromDb($noneDefaultWmc);
        // update urls from wmc with urls from database if id is given
        //$e = new mb_exception("javascripts/initWmcObj.php: wmc->updateUrlsFromDb");
        $updatedWMC = $wmcGetApi->updateUrlsFromDb();
        $wmcGetApi->createFromXml($updatedWMC);
        // increment load count
        $wmcGetApi->incrementWmcLoadCount();
    }
    catch (Exception $e) {
        new mb_exception("javascripts/initWmcObj.php: Failed to load WMC from DB via ID (nonedefaultwmc). Keeping original WMC.");
    }
    //get wmc id for application metadata
    //set variable to decide if application metadata can be accessed afterwards NEW 2019-11-28
    if ($inputWmcArray) {
        $startWmcId = $inputWmcArray[0]['id'];
    }
    $e = new mb_exception("javascripts/initWmcObj.php: found nonedefaultwmc: " . $noneDefaultWmc . " wmc for starting application: " . $startWmcId);
} else {
    if ($inputWmcArray) {
    	$e = new mb_notice("javascript/initWmcObj.php: some WMC id was set thru Get Api!");
    	foreach ($inputWmcArray as $input) {
    	// Just make it work for a single Wmc
    		try {
    			$wmcGetApi = WmcFactory::createFromDb($input["id"]);
    			// update urls from wmc with urls from database if id is given
    			//$e = new mb_exception("javascripts/initWmcObj.php: wmc->updateUrlsFromDb");
    			$updatedWMC = $wmcGetApi->updateUrlsFromDb();
    	        $wmcGetApi->createFromXml($updatedWMC);
                //set variable to decide if application metadata can be accessed afterwards NEW 2019-11-28
                $startWmcId = $input["id"];
    			// increment load count
    			$wmcGetApi->incrementWmcLoadCount();
    		}
    		catch (Exception $e) {
    			new mb_exception("javascripts/initWmcObj.php: Failed to load WMC from DB via ID. Keeping original WMC.");
    		}
    	}
    }
}
/*
WMS
*/
$e = new mb_notice("javascripts/initWmcObj.php: check WMS API");
//private $datasetid; //new parameter to find a layer with a corresponding identifier element - solves the INSPIRE data service coupling after retrieving the ows from a dataset search via CSW interface! Only relevant, if a WMS is gioven 
if ($getParams['WMS']) {
	// WMS param given as array
	if (is_array($getParams['WMS'])) {
		$inputWmsArray = $getParams['WMS'];
	}
	// WMS param given as comma separated list
	else {
		$inputWmsArray = mbw_split(",",$getParams['WMS']);
	}
	$wmsArray = array();
	$singleAssocArray = array();
	$multipleAssocArray = array();
	foreach ($inputWmsArray as $key=>$val) {
		if (is_array($val)) {
			foreach ($val as $attr=>$value) {
				$multipleAssocArray[$attr] = $value;
			}
			// get WMS by ID with settings of given application
			if (array_key_exists('application', $multipleAssocArray) &&
				array_key_exists('id', $multipleAssocArray)) {
				$currentWms = new wms();
				$currentWms->createObjFromDB($multipleAssocArray['application'], $multipleAssocArray['id']);
			}
			// get WMS by URL
			elseif (array_key_exists('url', $multipleAssocArray)) {
				$currentWms = new wms();
				//try to parse capabilities - if not successful give message to unavailable array!
//$e = new mb_exception("javascripts/initWmcObj.php: begin result of wms parsing: ");
				$resultOfWmsParsing = $currentWms->createObjFromXML($multipleAssocArray['url']);
//$e = new mb_exception("javascripts/initWmcObj.php: result of wms parsing: ".json_encode($resultOfWmsParsing));
			} else {
				continue;
			}
//$e = new mb_exception("javascripts/initWmcObj.php: result of wms parsing: ".json_encode($resultOfWmsParsing));
			if ($resultOfWmsParsing['success'] == true) {
				array_push($wmsArray, $currentWms);
				$options['visible'] = $multipleAssocArray['visible'] === "1" ?
					true : false;
				$options['zoom'] = $multipleAssocArray['zoom'] === "1" ?
					true : false;
				$wmcGetApi->mergeWmsArray($wmsArray, $options);
				$wmsArray = array();
				$multipleAssocArray = array();
			} else {
//$e = new mb_exception("javascripts/initWmcObj.php: wms with problem: ".$multipleAssocArray['url']);
				$resultObj["notAccessable"]["wms"] = array_merge(
					$resultObj["notAccessable"]["wms"],
					$multipleAssocArray['url'].' - '.$resultOfWmsParsing['message']
					);	
			}
		} else {
			//one single WMS capabilities url is given - normal case!
			$currentWms = new wms();
			if(is_numeric($key)) {
				// get WMS by ID
				if (is_numeric($val)) {
					$resultOfWmsParsing = $currentWms->createObjFromDBNoGui($val);
				}
				// get WMS by URL
				else if (is_string($val)) {
					//$e = new mb_exception("javascripts/initWmcObj.php: look for identifier element: ".$getParams['DATASETID']);
					//check wms url for parameter request, service, version
					//else add missing parameter
				     
				    if (!filter_var($val, FILTER_VALIDATE_URL) === false) {
				        //sanitize url
				        //del all wms params from url
				        foreach (array('service','SERVICE','version','VERSION','request','REQUEST') as $paramName) {
				            $val = delTotalFromQuery($paramName, $val);
				        }
				        $val .= "&REQUEST=GetCapabilities&VERSION=1.3.0&SERVICE=wms";
				        $val = str_replace("?&","?", str_replace('&&', '&', $val));
				        //add new ones to url
				        //$e = new mb_exception("javascripts/init.WmcObj.php: wmsurlfrom new: ".$val);
				    }
				  				   
					$resultOfWmsParsing = $currentWms->createObjFromXML($val, false, $getParams['DATASETID']);
					//Set zoom to extent of wms 
//$e = new mb_exception("javascripts/initWmcObj.php: wms object to add: ".json_encode($currentWms));
					//has a layer_identifier array, cause it is created from capabilities!
					//$e = new mb_exception("javascripts/initWmcObj.php: first layer layer_epsg: ".json_encode($currentWms->objLayer[0]->layer_epsg[0]));
					//find layer epsg of service where epsg=EPSG:4326
					foreach ($currentWms->objLayer[0]->layer_epsg as $layerExtent) {
						//$e = new mb_exception("leayer_epsg: ".$layerExtent["epsg"]);
						if ($layerExtent["epsg"] == "EPSG:4326") {
							// overwrite extend from getApi
							//$e = new mb_exception("overwrite extent");
							$bbox = new Mapbender_bbox($layerExtent["minx"],$layerExtent["miny"],$layerExtent["maxx"],$layerExtent["maxy"],"EPSG:4326");
							// check for current epsg and transform if needed
							if ($wmcGetApi->mainMap->getEpsg() !== "EPSG:4326") {
								$bbox->transform($wmcGetApi->mainMap->getEpsg());
							}
							$wmcGetApi->mainMap->setExtent($bbox);
							//overwrite zoom to parameter 
							break;	
						}
					}
					//search for bbox of special layer - overwrite the bbox of the wms if such a layer was found!
					//example: http://localhost/mapbender/frames/index.php?&gui_id=Geoportal-RLP&WMS=https%3A%2F%2Fhaleconnect.com%2Fows%2Fservices%2Forg.789.ffa9f2a0-54d8-4f18-961c-196230496a0a_wms%3FSERVICE%3DWMS%26Request%3DGetCapabilities&DATASETID=haleconnect.com%2F995c10be-a9df-448f-9031-7b35eb0964f6_TN.RoadTransportNetwork.RoadLink
					if (isset($getParams['DATASETID']) && $getParams['DATASETID'] != "") {
						foreach ($currentWms->objLayer as $layerObj) {
						    //$e = new mb_exception("javascripts/initWmcObj.php: iterate over identifier!");
						    //uses the first occurence of a layer with this identifier - not all! TODO: create combined bbox of all layers which have this identifier defined!
						    foreach ($layerObj->layer_identifier as $identifier) {
						        /*$e = new mb_exception("javascripts/initWmcObj.php: layer_identifier: " . $identifier->identifier);
						        $e = new mb_exception("javascripts/initWmcObj.php: DATASETID: " . str_replace('\/', '/', urldecode($getParams['DATASETID'])));
						        $e = new mb_exception("javascripts/initWmcObj.php: DATASETID urlencoded: " . urldecode($getParams['DATASETID']));
						        */
						        if ($identifier->identifier == str_replace('\/', '/', urldecode($getParams['DATASETID']))) {
						            $e = new mb_exception("javascripts/initWmcObj.php: DATASETID found zoom to extent!");
    								foreach ($layerObj->layer_epsg as $subLayerExtent){
    									if ($subLayerExtent["epsg"] == "EPSG:4326") {
    										// overwrite extend from getApi
    										//$e = new mb_exception("overwrite extent");
    										$bbox = new Mapbender_bbox($subLayerExtent["minx"], $subLayerExtent["miny"], $subLayerExtent["maxx"], $subLayerExtent["maxy"], "EPSG:4326");
    										// check for current epsg and transform if needed
    										if ($wmcGetApi->mainMap->getEpsg() !== "EPSG:4326") {
    											$bbox->transform($wmcGetApi->mainMap->getEpsg());
    										}
    										$wmcGetApi->mainMap->setExtent($bbox);
    										//overwrite zoom to parameter
    										break;
    									}
    								}
    								break;
    							}
						    }
						}
					}
				}
				if ($resultOfWmsParsing['success'] == true) {
					array_push($wmsArray, $currentWms);
					//this is only the array of the wms that are given via getapi
					//$e = new mb_exception('javascripts/initWmcObj.php: new wms array(before merging): ' . json_encode($wmsArray));
					$options['visible'] = $multipleAssocArray['visible'] === "1" ?
						true : false;
					$options['zoom'] = $multipleAssocArray['zoom'] === "1" ?
						true : false;
					//write wmc xml before and after merging
					/*$wmc_old = $wmcGetApi->toXml();
					$myfile = fopen(TMPDIR . "/wmc_before.xml", "w") or die("Unable to open file!");
					fwrite($myfile, $wmc_old);
					fclose($myfile);*/
					$wmcGetApi->mergeWmsArray($wmsArray, $options);
					/*$wmc_new = $wmcGetApi->toXml();
					$myfile = fopen(TMPDIR . "/wmc_after.xml", "w") or die("Unable to open file!");
					fwrite($myfile, $wmc_new);
					fclose($myfile);*/
					$wmsArray = array();
					$multipleAssocArray = array();
				} else {
//$e = new mb_exception("javascripts/initWmcObj.php: wms with problem: message: ".(string)$val." - ".$resultOfWmsParsing['message']);
					$resultObj["notAccessable"]["wms"][] = htmlentities($val)." - ("._mb('Notice')."</b>: ".$resultOfWmsParsing['message'].")";
				}
			}
			else {
				$singleAssocArray[$key] = $val;
			}
		}
	}
	//get WMS by ID with settings of given application
	if (array_key_exists('application', $singleAssocArray) &&
		array_key_exists('id', $singleAssocArray)) {
		$currentWms = new wms();
		$currentWms->createObjFromDB(
			$singleAssocArray['application'],
			$singleAssocArray['id']
		);
		array_push($wmsArray, $currentWms);
		$options['visible'] = $singleAssocArray['visible'] === "1" ?
			true : false;

		$options['zoom'] = $singleAssocArray['zoom'] === "1" ? true : false;

		$wmcGetApi->mergeWmsArray($wmsArray, $options);
		$wmsArray = array();
		$singleAssocArray = array();
	}
	// get WMS by URL
	elseif (array_key_exists('url', $singleAssocArray)) {
		$currentWms = new wms();
		$currentWms->createObjFromXML($singleAssocArray['url']);
		array_push($wmsArray, $currentWms);
		if($singleAssocArray['visible']) {
			$options['visible'] = $singleAssocArray['visible'] === "1" ?
				true : false;
		}
		if($singleAssocArray['zoom']) {
			$options['zoom'] = $singleAssocArray['zoom'] === "1" ?
				true : false;
		}
		$wmcGetApi->mergeWmsArray($wmsArray, $options);
		$wmsArray = array();
		$singleAssocArray = array();
	}
}
/*
LAYER
*/
//$e = new mb_notice("javascripts/initWmcObj.php: check LAYER API");
$inputLayerArray = $getApi->getLayers();
if ($inputLayerArray) {
//    $start = microtime(true);
	foreach ($inputLayerArray as $input) {
	    $start_layer = microtime(true);
	    $e = new mb_notice('javascripts/mod_initWmcObj.php: layer: ' . json_encode($input));
		// just make it work for a single layer id
		$wmsFactory = new UniversalWmsFactory();
//		$start_read_layer = microtime(true);
		try {
			if (isset($input["application"])) {
				$wms = $wmsFactory->createLayerFromDb($input["id"], $input["application"]);
			}
			else {
				$wms = $wmsFactory->createLayerFromDb($input["id"]);
				//$e = new mb_exception("javascripts/initWmcObj.php: wms obj: " . json_encode($wms));
			}
		}
		catch (AccessDeniedException $e) {
			$resultObj["noPermission"]["wms"][] = array(
				"title" => $admin->getLayerTitleByLayerId($input["id"]),
				"id" => $input["id"]
			);
		}
//		$time_elapsed_secs_read_layer = microtime(true) - $start_layer;
//		$e = new mb_exception('javascripts/mod_initWmcObj.php: time for read single layer: ' . $time_elapsed_secs_read_layer);
		if (is_a($wms, "wms")) {
			$options = array();
			if ($input["visible"]) {
			// this is a hack for the time being:
			// make WMS visible if it has less than 10000 layers
				$options["show"] = 10000;
			}
			if (isset($input["querylayer"])) {
				$options["querylayer"] = $input["querylayer"];
			}
			//$start = microtime(true);
			$wmcGetApi->mergeWmsArray(array($wms), $options);
			//$time_elapsed_secs = microtime(true) - $start;
			//$e = new mb_exception('javascripts/mod_initWmcObj.php: time for mergeWmsArray: ' . $time_elapsed_secs);
			
			// do not use "zoom" attribute of mergeWmsArray,
			// as this would zoom to the entre WMS.
			// Here we set extent to the layer extent only.
			if ($input["zoom"]) {
				$bboxArray = array();
				try {
					$layer = $wms->getLayerById(intval($input["id"]));
					for ($i = 0; $i < count($layer->layer_epsg); $i++) {
						$bboxArray[]= Mapbender_bbox::createFromLayerEpsg(
							$layer->layer_epsg[$i]
						);
					}
//					$e = new mb_exception("javascripts/initWmcObj.php: bbox array obj: " . json_encode($bboxArray));
					$wmcGetApi->mainMap->mergeExtent($bboxArray);
				}
				catch (Exception $e) {

				}
			}
		}
//		$time_elapsed_secs_layer = microtime(true) - $start_layer;
//		$e = new mb_exception('javascripts/mod_initWmcObj.php: time for single layer: ' . $time_elapsed_secs_layer);
	}
//	$time_elapsed_secs = microtime(true) - $start;
//	$e = new mb_exception('javascripts/mod_initWmcObj.php: time for all layer: ' . $time_elapsed_secs);
	
}
/*
FEATURETYPE
*/
$e = new mb_notice("javascripts/initWmcObj.php: Check FEATURETYPE API");
$inputFeaturetypeArray = $getApi->getFeaturetypes();
if ($inputFeaturetypeArray) {
	$wfsConfIds = array();
	foreach ($inputFeaturetypeArray as $input) {
		array_push($wfsConfIds, $input["id"]);
	}
	$wmcGetApi->generalExtensionArray['WFSCONFIDSTRING'] = implode(",", array_unique(array_merge(
		$wmcGetApi->generalExtensionArray['WFSCONFIDSTRING'] ?
		explode(",", $wmcGetApi->generalExtensionArray['WFSCONFIDSTRING']) :
		array(),
		$wfsConfIds
	)));
}
/*
GEORSS
*/
$inputGeoRSSArray = $getApi->getGeoRSSFeeds();
$e = new mb_notice("javascripts/initWmcObj.php: check GEORSS API");
if($inputGeoRSSArray){
	$wmc->generalExtensionArray['GEORSS'] = $inputGeoRSSArray;
}
/*
KML
*/
$inputKmlArray = $getApi->getKml();
if($inputKmlArray){
	$wmc->generalExtensionArray['KML'] = $inputKmlArray;
}
/*
GEOJSON
*/
$someGeojsonGiven = false;
$inputGeojsonArray = $getApi->getGeojson();
$zoomToExtent = $getApi->getGeojsonZoom();
$offset = $getApi->getGeojsonZoomOffset();
if ($zoomToExtent == 'true') {
	$minx = false;
	$miny = false;
	$maxx = false;
	$maxy = false;
}
if(is_array($inputGeojsonArray) && count($inputGeojsonArray) > 0 && !empty($inputGeojsonArray[0])){
	//create objects
	$kmls = new stdClass();
	unset($wmcGetApi->generalExtensionArray['kmls']);
	unset($wmcGetApi->generalExtensionArray['kmlOrder']);
	unset($wmcGetApi->generalExtensionArray['KMLORDER']);
	unset($wmcGetApi->generalExtensionArray['KMLS']);
	$kmlOrder = array();
	$i = 0;
	foreach ($inputGeojsonArray as $inputGeojson) {
		//$e = new mb_exception($inputGeojson);
		// load json files from distributed locations
		// check if url directly geojson is given
		if ($admin->validateUrl(urldecode($inputGeojson))) {
			//$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON parameter will be interpreted as url - try to resolve external json!");
			// TODO: here there may exists firewall problems which cut the request part after the first ampersand!!!!
			//$e = new mb_exception("javascripts/initWmcObj.php: found url ".urldecode($inputGeojson));
			//$e = new mb_exception("javascripts/initWmcObj.php: found url unencoded ".$inputGeojson);
			$jsonFileConnector = new connector();
			$jsonFileConnector->set('externalHeaders', 'empty');
			//Timeout set to 10 seconds to allow bigger geojson files to be downloaded
			$jsonFileConnector->set('executionTimeOut', 10000); //to prohibit big geojson files, set max download time to 2000 mseconds ;-)
			$jsonFileConnector->load($inputGeojson);
			if ($jsonFileConnector->curlError !== false) {
			    $e = new mb_exception("javascripts/initWmcObj.php: curl error message: " . $jsonFileConnector->curlError);
			}
			$geojson = json_validator($jsonFileConnector->file);
			if ($geojson !== false) {
			    $someGeojsonGiven = true;
			    //$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON is valid");
			} else {
			    //$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON is not valid");
			}
		} else {
			$e = new mb_notice("javascripts/initWmcObj.php: GEOJSON parameter will be interpreted as string!");
			//$geojson = json_decode(urldecode($inputGeojson));
			$geojson = json_validator(urldecode($inputGeojson));
			if ($geojson !== false) {
			    $someGeojsonGiven = true;
			    //$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON is valid");
			} else {
			    //$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON is not valid");
			}
		}
		$isGeojson = false;
		if ($geojson !== null && $geojson !== false) {
		    //build collection if single features are given!
		    //$e = new mb_exception("javascripts/initWmcObj.php: geojson: " . json_encode($geojson));
		    //$e = new mb_exception("javascripts/initWmcObj.php: geojson.type: " . $geojson->type);
		    switch ($geojson->type) {
		        case "Feature":
		            $dummyCollection = new stdClass();
		            $dummyCollection->type = "FeatureCollection";
		            $dummyCollection->features = array();
		            //add feature to dummy collection
		            $dummyCollection->features[] = $geojson;
		            $geojson = $dummyCollection;
		            $isGeojson = true;
		            break;
		        case "FeatureCollection":
		            //all further things are ok 
		            $isGeojson = true;
		            break;
		        default:
		            $e = new mb_exception("javascripts/initWmcObj.php: no known geojson type is given in json!");
		            //TODO - ignore all geojson specific get parameters!
		            break;
		    }
		    if ($isGeojson) {
		        //add default style and title/description tags if they are not given as json properties
		        $geojsonStyle = new Geojson_style();
		        $styledGeojson = $geojsonStyle->addDefaultStyles(json_encode($geojson));
		        $geojson = json_decode($styledGeojson);
		        //add default title for geojson collection, if not given 
			    if (!empty($geojson->title)) {
				    $geojsonTitle = $geojson->title . " " ._mb("Group")." ". $i;
			    } else {
				    $geojsonTitle = "notTitleGivenForCollection " . $i;
			    }
			    $kmlOrder[] = $geojsonTitle;
			    $kmls->{$geojsonTitle}->type = "geojson";
			    $kmls->{$geojsonTitle}->data = $geojson;
			    $kmls->{$geojsonTitle}->url = $geojsonTitle;
			    $kmls->{$geojsonTitle}->display = true;
				//Ticket 8549: In the context of compatibility changes in geojson fileuploads,
				//this logic was fixed to allow for multiple polygons in a geojson file.
				//but also deletes the old multipolygon features
				$indexArrayToDeleteMultiPolygons = array();
				foreach ($kmls->{$geojsonTitle}->data->features as $idx => $feature) {
					//$e = new mb_exception("javascripts/initWmcObj.php: GEOJSON TYPE : ".$feature->geometry->type);
					//explode multipolygons to polygon before further processing, because we don't support multi geometries at this time
					if ($feature->geometry->type == 'MultiPolygon') {
						$numberOfPolygon = 1;
						foreach ($feature->geometry->coordinates as $polygons) {
							$newFeature = new stdClass();
							$newFeature->type = "Feature";
							$newFeature->properties = clone $feature->properties;
							$newFeature->properties->title = $newFeature->properties->title . " - Polygon " . $numberOfPolygon;
							$newFeature->geometry = new stdClass();
							$newFeature->geometry->coordinates = $polygons;
							$newFeature->geometry->type = "Polygon";
							$kmls->{$geojsonTitle}->data->features[] = $newFeature;
							unset($newFeature);
							$numberOfPolygon++;
						}

					}
					$indexArrayToDeleteMultiPolygons[] = $idx;
				}
				foreach ($indexArrayToDeleteMultiPolygons as $idx) {
					//unset the multipolygon feature
					unset($kmls->{$geojsonTitle}->data->features[$idx]);
				}
				//reindex the array
				$kmls->{$geojsonTitle}->data->features = array_values($kmls->{$geojsonTitle}->data->features);

			    if ($zoomToExtent == 'true') {
				    $latitudes = array();
				    $longitudes = array();
				    foreach($kmls->{$geojsonTitle}->data->features as $feature) {
				        $e = new mb_notice("javascripts/initWmcObj.php: GEOJSON TYPE : ".$feature->geometry->type);   
					    switch ($feature->geometry->type) {
					        case "MultiPolygon":
					            //$e = new mb_exception("javascripts/initWmcObj.php: Polygon found!");
					            foreach ($feature->geometry->coordinates as $polygons) {
					                foreach ($polygons as $coordinates1) {
					                   foreach ($coordinates1 as $coordinates2) {
					                       $longitudes[] = $coordinates2[0];
					                       $latitudes[] = $coordinates2[1];
					                   }
					                }
					            }
					            break;
						    case "Polygon":
							    //$e = new mb_exception("javascripts/initWmcObj.php: Polygon found!");
							    foreach ($feature->geometry->coordinates as $coordinates2) {
								    foreach ($coordinates2 as $coordinates1) {
									    $longitudes[] = $coordinates1[0];
									    $latitudes[] = $coordinates1[1];
								    }
							    }
							    break;
						    case "Point":
							    //$e = new mb_exception("javascripts/initWmcObj.php: Point found!");
							    $longitudes[] = $feature->geometry->coordinates[0];
							    $latitudes[] = $feature->geometry->coordinates[1];
							    break;
						    case "LineString":
						    	//$e = new mb_exception("javascripts/initWmcObj.php: LineString found!");
							    foreach ($feature->geometry->coordinates as $coordinates1) {
								    $longitudes[] = $coordinates1[0];
								    $latitudes[] = $coordinates1[1];
							    }
							    break;
					    }
				    }
		        }  
		        $i++; //only add one geojson - if it is somewhat valid
		    } else {
		        $e = new mb_exception("javascripts/initWmcObj.php: some json is not a feature or a featurecollection!");
		    }
		}
		$e = new  mb_exception("javascripts/initWmcObj.php: add " . $i . " geojson objects from GET-Parameters");
	}
	if ($someGeojsonGiven && $zoomToExtent == 'true') {
		$minx = min($longitudes);
		$miny = min($latitudes);
		$maxx = max($longitudes);
		$maxy = max($latitudes);
		if ($minx == $maxx || $miny == $maxy) {
			$offset = 100;
		}
		if ($offset !== false) {
		    $e = new mb_exception("javascripts/initWmcObj.php: calculate new offset! "); 
			$averageLatitude = ($maxy - $miny) / 2;
			$r = 6371000.0;
			$pi = 3.14159265359;
			$rho = 180.0 / $pi;
			$offsetLon = $offset * $rho / $r;
			$offsetLat = $offset * $rho / ($r * cos(($averageLatitude / $rho)));
			$minx = $minx - $offsetLon;
			$miny = $miny - $offsetLat;
			$maxx = $maxx + $offsetLon;
			$maxy = $maxy + $offsetLat;
		}
		// overwrite extend from getApi
		$bbox = new Mapbender_bbox($minx,$miny,$maxx,$maxy,"EPSG:4326");
		//$e = new mb_exception("javascripts/initWmcObj.php: new bbox " . json_encode($bbox)); 
		// check for current epsg and transform if needed
		if ($wmcGetApi->mainMap->getEpsg() !== "EPSG:4326") {
			$bbox->transform($wmcGetApi->mainMap->getEpsg());
		}
		$wmcGetApi->mainMap->setExtent($bbox);
	}
	//$e = new mb_exception("javascripts/initWmcObj.php: after setExtent " . $wmcGetApi->mainMap->getExtent());
	if ($someGeojsonGiven) {  
	    $wmcGetApi->generalExtensionArray['kmls'] = json_encode($kmls);
		$wmcGetApi->generalExtensionArray['kmlOrder'] = json_encode($kmlOrder);
		$e = new mb_notice("javascripts/initWmcObj.php: kmls: ".$wmcGetApi->generalExtensionArray['kmls']);
		$e = new mb_notice("javascripts/initWmcObj.php: kmlOrder: ".$wmcGetApi->generalExtensionArray['kmlOrder']);
	} else {
	    $e = new mb_notice("javascripts/initWmcObj.php: no valid geojson was given");
	}
	$e = new mb_notice("javascripts/initWmcObj.php: write wmc obj to {TMPDIR}/class_wmc1.json");//
}

//*******************************************************************************************************
/*
GET information about application metadata if a combination of GUI and WMC is invoked and a special
module for showing this metadata is available in the invoked GUI - NEW 2019-11-28
*/
//*******************************************************************************************************
//
//if ($startWmcId != false) {
if (true) {
    //$e = new mb_exception("Initialize GUI from combination of GUI and WMC: gui_id='".$app."' - WMC='".$startWmcId."'");
    $applicationMetadataResult = $admin->getCombinedApplicationMetadata($app, $startWmcId);
    if ($applicationMetadataResult->success != false) {
        //$e = new mb_exception("Found mapbender application metadata with id ".$applicationMetadataResult->uuid);
        //If metadata was found - get it via class metadata!
        $metadataFileIdentifier = $applicationMetadataResult->uuid;
        if (true && isset($applicationMetadataResult->orgaId)) {
            $group = new Group($applicationMetadataResult->orgaId);
	        //$applicationMetadata->createFromDBInternalId($metadataId);
	        //initialize needed information from XML which will be called via php/mod_dataISOMetadata.php!!!
	        //connector - ....
            //$e = new mb_exception("fileIdentifier: ".$metadataFileIdentifier);
            //metadataUrlGenerator
            //http://localhost/mapbender/php/.... maybe better via invoking direct!!
	        //$e = new mb_exception("url: ".MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$metadataFileIdentifier);
	        /*$appMetadataRemote = new connector(MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$metadataFileIdentifier);
            $applicationMetadata = new Iso19139();
            $applicationMetadata->createMapbenderMetadataFromXML($appMetadataRemote->file);*/
            $applicationMetadata->fileIdentifier = $applicationMetadataResult->uuid;
            $applicationMetadata->title = $applicationMetadataResult->title;
            $applicationMetadata->abstract = $applicationMetadataResult->abstract;
            $applicationMetadata->organization = array();
	        $applicationMetadata->organization['logo_path'] = $group->logo_path;
	        $applicationMetadata->organization['title'] = $group->title;
	        $applicationMetadata->organization['name'] = $group->name;
            $applicationMetadata->organization['address'] = $group->address;
	        $applicationMetadata->organization['postcode'] = $group->postcode;
            $applicationMetadata->organization['city'] = $group->city;
            $applicationMetadata->organization['telephone'] = $group->voicetelephone;
	        $applicationMetadata->organization['email'] = $group->email;
	        $applicationMetadata->metadataUrl = MAPBENDER_PATH."/php/mod_iso19139ToHtml.php?url=".urlencode(MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$applicationMetadata->fileIdentifier);
	        $applicationMetadataJson = json_encode($applicationMetadata);
	        //$e = new mb_exception(json_encode($applicationMetadata));
	        //$jsonFile = new connector("http://localhost/mb_trunk/geoportal/testpolygon.json"); 
	    }
    } else {
        $e = new mb_notice("Found no mapbender application metadata!");
    }
}
//*******************************************************************************************************
// TODO test following
// workaround to have a fully merged WMC for loading
//$e = new mb_exception("javascripts/initWmcObj.php: xml generate - tmp_wmc2.json!");//
//debug
/*$myfile = fopen(TMPDIR . "/wms_array_wmc_merged_2.json", "w") or die("Unable to open file!");
fwrite($myfile, json_encode($wmcGetApi->mainMap->getWmsArray()));
fclose($myfile);*/
//$e = new mb_exception("javascripts/initWmcObj.php: before export to xml");//
//information about layer
$xml = $wmcGetApi->toXml();
//$e = new mb_exception("javascripts/initWmcObj.php: " . $xml);
//$e = new mb_exception("javascripts/initWmcObj.php: after export to xml");//
//debug
/*$myfile = fopen(TMPDIR . "/wmc_merged_2.xml", "w") or die("Unable to open file!");
fwrite($myfile, $xml);
fclose($myfile);*/
/*
if($h = fopen(TMPDIR . "/tmp_wmc2.json","w")){
    $content = json_encode($wmcGetApi) .chr(13).chr(10);
    if(!fwrite($h,$content)){
        #exit;
    }
    fclose($h);
}
*/
//$e = new mb_exception("javascripts/initWmcObj.php: WMC document after reading information from GET-API: ".json_encode($xml));
//$e = new mb_notice("");
//die();
if ($removeUnaccessableLayers == true) {
	$e = new mb_notice("javascripts/initWmcObj.php: Try to remove layers without permission while loading from session!");
	$xml = $wmcGetApi->removeUnaccessableLayers($xml);
}
//$e = new mb_notice("javascripts/initWmcObj.php: WMC document after removing unaccessable layers: ".$xml);
//$e = new mb_notice("");
$wmcGetApi = new wmc();
// For debuggin purposes
// New Object with merged layers and other features - why?? TODO test 
//$e = new mb_exception("javascripts/initWmcObj.php: create from xml");
$wmcGetApi->createFromXml($xml);	
//debug
/*$xml2 = $wmcGetApi->toXml();
$myfile = fopen(TMPDIR . "/wmc_merged_3.xml", "w") or die("Unable to open file!");
fwrite($myfile, $xml2);
fclose($myfile);*/
//$e = new mb_exception("javascripts/initWmcObj.php: new wmc from saved xml generated - tmp_wmc3.json!");//
/*
if($h = fopen(TMPDIR . "/tmp_wmc3.json","w")){
    $content = json_encode($wmcGetApi) .chr(13).chr(10);
    if(!fwrite($h,$content)){
        #exit;
    }
    fclose($h);
} 
*/
/*
CONSTRAINTS
*/
$currentUser = new User();
// remove all WMS with no permission
$e = new mb_notice("javascripts/initWmcObj.php: get wms without permission");
$deniedIdsArray = $wmcGetApi->getWmsWithoutPermission($currentUser);
$deniedIdsTitles = array();
$deniedIdsIndices = array();
foreach ($deniedIdsArray as $i) {
	if ($i["id"] !== 0) {
		$deniedIdsTitles[]= array(
			"id" => $i["id"],
			"index" => $i["index"],
			"title" => $i["title"]
		);
		$deniedIdsIndices[]= $i["index"];
	}
}
$resultObj["noPermission"]["wms"] = array_merge(
	$resultObj["noPermission"]["wms"],
	$deniedIdsTitles
);
$e = new mb_notice("javascripts/initWmcObj.php: list of wms without permission created");
$wmcGetApi->removeWms($deniedIdsIndices);
$e = new mb_notice("javascripts/initWmcObj.php: wms without permission removed from wmc");
// find WMS without ID
$e = new mb_notice("javascripts/initWmcObj.php: find wms without id");
$withoutIdsArray = $wmcGetApi->getWmsWithoutId();
$withoutIdsTitles = array();
foreach ($withoutIdsArray as $i) {
	$withoutIdsTitles[]= array(
		"id" => $i["id"],
		"index" => $i["index"],
		"title" => $i["title"]
	);
}
$resultObj["withoutId"]["wms"] = array_merge(
	$resultObj["withoutId"]["wms"],
	$withoutIdsTitles
);
$e = new mb_notice("javascripts/initWmcObj.php: wms without id list generated");
// find orphaned WMS
$e = new mb_notice("javascripts/initWmcObj.php: find invalid wms");
$invalidIdsArray = $wmcGetApi->getInvalidWms();
$invalidIdsTitles = array();
foreach ($invalidIdsArray as $i) {
	$invalidIdsTitles[]= array(
		"id" => $i["id"],
		"index" => $i["index"],
		"title" => $i["title"]
	);
}
$resultObj["invalidId"]["wms"] = array_merge(
	$resultObj["invalidId"]["wms"],
	$invalidIdsTitles
);
$e = new mb_notice("javascripts/initWmcObj.php: invalid wms list generated");
// find potentially unavailable WMS
$e = new mb_notice("javascripts/initWmcObj.php: find problematic wms - which had problems in last monitoring");
//$unavailableIdsArray = $wmcGetApi->getUnavailableWms($currentUser);
$unavailableIdsArray = $wmcGetApi->getAllUnavailableWms();
$unavailableIdsTitles = array();
foreach ($unavailableIdsArray as $i) {
	$unavailableIdsTitles[]= array(
		"id" => $i["id"],
		"index" => $i["index"],
		"title" => $i["title"]
	);
}
$resultObj["unavailable"]["wms"] = array_merge(
	$resultObj["unavailable"]["wms"],
	$unavailableIdsTitles
);
$e = new mb_notice("javascripts/initWmcObj.php: problematic wms list generated");
// get terms of use from wms objects which are in the remaining wmc and are not already accepted for this session
$e = new mb_notice("javascripts/initWmcObj.php: collect known tou");
$validWMS = $wmcGetApi->getValidWms();
$translation['wms'] = _mb("MapService");
$resourceSymbol = "<img src='../img/osgeo_graphics/geosilk/server_map.png' alt='".$translation['wms']." - picture' title='".$translation['wms']."'>";
$languageCode = 'de';
$hostName = $_SERVER['HTTP_HOST'];
$tou = "";
$classTou = new tou();
$countWMS = count($validWMS);
for ($i = 0; $i < $countWMS; $i++) {
	$WMS = $validWMS[$countWMS - ($i+1)];
	// check if tou has already been read - if not show them in the message
	$resultOfCheck = $classTou->check('wms',$WMS['id']);
	if ($resultOfCheck['accepted'] == 0) {
		$touHeader = $resourceSymbol." <a href='../php/mod_showMetadata.php?resource=wms&layout=tabs&id=".$WMS['id']."&languageCode=".$languageCode."' target='_blank'>".$WMS['title']."</a><br>";
		$constraints = new OwsConstraints();
		$constraints->languageCode = $languageCode;
		$constraints->asTable = true;
		$constraints->id = $WMS['id'];
		$constraints->type = "wms";
		$constraints->returnDirect = false;
		$touForWMS = $constraints->getDisclaimer();
		// add only those who have no special tou defined
		if ($touForWMS != 'free'){
			$tou .= $touHeader.$touForWMS;
		}
		// set the tou to be accepted - TODO maybe do this after the button which deletes the message window - from a ajax request.
		$classTou->set('wms',$WMS['id']);
	}
}
if ($tou != "") {
	$tou = _mb("The configuration, which should be loaded, consists of different services which have the following terms of use:")."<br>".$tou;
}
$resultObj["wmcTou"]["message"] = $tou;
$e = new mb_notice("initWmcObj.php: collect known tou done!");
/*
Output
Check if session WMC module is loaded - TODO maybe do this before the other things are done!!!
*/
/*$e = new mb_notice("javascripts/initWmcObj.php: check if disclaimer should be set");
$sql = "SELECT COUNT(e_id) AS i FROM gui_element WHERE fkey_gui_id = $1 AND e_id = $2";
$v = array(Mapbender::session()->get("mb_user_gui"), "sessionWmc");
$t = array("s", "s");
$res = db_prep_query($sql, $v, $t);
$row = db_fetch_assoc($res);
$isSessionWmcModuleLoaded = intval($row["i"]);
$e = new mb_notice("javascripts/initWmcObj.php: check for disclaimer done");*/
/*
GML in session
check if Session contains a GML, and then zoom to it - same code as in mod_renderGML.php - sync it!
*/
$e = new mb_notice("javascripts/initWmcObj.php: check session for GML to zoom");
//*************************************************************************************************
$gml_string = Mapbender::session()->get("GML");
if ($gml_string) {
	//To parse gml extent header
	$gml2String = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>".$gml_string;
	//$e = new mb_exception("javascripts/initWmcObj.php: ".$gml2String);
	libxml_use_internal_errors(true);
	try {
		$gml2 = simplexml_load_string($gml2String);
		if ($gml2 === false) {
			foreach(libxml_get_errors() as $error) {
				$err = new mb_exception("javascripts/mod_renderGML.php: ".$error->message);
	    	}
			throw new Exception("javascripts/initWmcObj.php: ".'Cannot parse GML from session!');
			return false;
		}
	}
	catch (Exception $e) {
	    	$err = new mb_exception("javascripts/initWmcObj.php: ".$e->getMessage());
		return false;
	}			
	//if parsing was successful			
	if ($gml2 !== false) {
		$gml2->addAttribute('xmlns:gml', 'http://www.opengis.net/gml');
		$gml2->registerXPathNamespace("default", "http://www.opengis.net/gml");
		$gml2->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		if ($gml2->xpath('/FeatureCollection/featureMember/*/*/MultiPolygon')) {
			$e = new mb_notice("javascripts/initWmcObj.php:  MultiPolygon found!");
			$multiPolygon = $gml2->xpath('/FeatureCollection/featureMember/*/*/MultiPolygon');
			$multiPolygonGml = $multiPolygon[0]->asXML();
			//$e = new mb_exception("javascripts/initWmcObj.php: MultiPolygon: ".$multiPolygonGml);
			$currentEpsg = Mapbender::session()->get("epsg");
			//use code not whole uri
			$crs = new crs($currentEpsg);
			$currentEpsg = $crs->identifierCode;
			//$e = new mb_exception("javascripts/initWmcObj.php: currentEpsg code: ".$crs->identifierCode);
			//
			if ($currentEpsg !== '4326') {
				$sql = "SELECT st_box(st_transform(st_geomfromgml($1),$2::INT)) AS geom";
				$v = array($multiPolygonGml, $currentEpsg);
				$t = array('s', 'i');
				$res = db_prep_query($sql,$v,$t);
				db_fetch_row($res);
				$bbox = db_result($res, 0, 'geom');
			} else {
				$sql = "SELECT st_box(st_geomfromgml($1)) AS geom";
				$v = array($multiPolygonGml);
				$t = array('s');
				$res = db_prep_query($sql,$v,$t);
				db_fetch_row($res);
				$bbox = db_result($res, 0, 'geom');
			}
			//do other things:
			//parse bbox values
			$bbox = str_replace(")", "", str_replace("(", "", $bbox));
			$bboxArray = explode(",", $bbox);
			//create mapbender bbox object to zoom to
			$bbox = new Mapbender_bbox(
				$bboxArray[2],
				$bboxArray[3],
				$bboxArray[0],
				$bboxArray[1],
				$epsg = "EPSG:".$currentEpsg
			);
			$wmcGetApi->mainMap->setExtent($bbox);
		} else { //no multipolygon found
			$e = new mb_notice("javascripts/initWmcObj.php: Other geometry than MultiPolygon found in session - try to parse it with mapbenders gml2 class!");
			$gml = new gml2();
            $gml->parse_xml($gml_string);
            $bboxArray = $gml->bbox;
            $currentEpsg = Mapbender::session()->get("epsg");
            //create mapbender bbox object to zoom to
            $bbox = new Mapbender_bbox(
                $bboxArray[0],
                $bboxArray[1],
                $bboxArray[2],
                $bboxArray[3],
                $epsg = "EPSG:".$currentEpsg
            );
            $wmcGetApi->mainMap->setExtent($bbox);
		}
	}
}
$e = new mb_notice("javascripts/initWmcObj.php: session GML zoom done");
//*************************************************************************************************
// overwrite extent of wmc with information from GetApi if given
$e = new mb_notice("javascripts/initWmcObj.php: check ZOOM API");
$zoom = $getApi->getZoom();
if(is_array($zoom)) {
    $e = new mb_notice("javascripts/initWmcObj.php: check ZOOM API: ".implode(',', $zoom));
}
if (count($zoom) == 3) {
    //add zoom[2] to x and y and set bbox
    //calculate new extent from scale -  
    //
    $point = array($zoom[0], $zoom[1]);
    $scale = $zoom[2];
    $newExtent = $wmcGetApi->mainMap->getBboxFromPoiScale($point, $scale);
    //Problem: TODO setExtent does not work properly for geographic EPSGs!!! test line 519 - if a point geometry is given by geojson 
    //$e = new mb_exception(json_encode($newExtent));
    //$e = new mb_exception(json_encode($wmcGetApi->mainMap->getEpsg()));
    $bbox = new Mapbender_bbox(
	   $newExtent[0],
	   $newExtent[1],
	   $newExtent[2],
	   $newExtent[3],
	   $epsg = $wmcGetApi->mainMap->getEpsg()
    );
    $wmcGetApi->mainMap->setExtent($bbox);
    //render point at middle position 
}
if (count($zoom) == 4 || count($zoom) == 5) {
	$e = new mb_notice("javascripts/initWmcObject.php: found EXTENT");
	if (count($zoom) == 5) {
		$bbox = new Mapbender_bbox(
			$zoom[0],
			$zoom[1],
			$zoom[2],
			$zoom[3],
			$epsg = $zoom[4]
		);
		
	} else {
		//check if zoom with scale and epsg is requested 
		if (strpos(strtolower($zoom[3]), "epsg") === 0  && is_numeric($zoom[0]) && is_numeric($zoom[1]) && is_numeric($zoom[2])) {
			$e = new mb_notice("javascripts/initWmcObject.php: SRS found in zoom parameter: ".$zoom[3]);
			$point = array($zoom[0], $zoom[1]);
			$scale = $zoom[2];
			$newExtent = $wmcGetApi->mainMap->getBboxFromPoiScale($point, $scale, $zoom[3]);
			$bbox = new Mapbender_bbox(
			    $newExtent[0],
			    $newExtent[1],
			    $newExtent[2],
			    $newExtent[3],
			    $epsg = $wmcGetApi->mainMap->getEpsg()
			);
			//$wmcGetApi->mainMap->setExtent($bbox);
			//maybe easier to guild geojson before ...
			//calculate bbox from central point with scale (or offset in m) - depends on epsg
		} else {		
			// get current epsg from wmc bounding box
			// ViewContext->General->BoundingBox->SRS
			$e = new mb_notice("javascripts/initWmcObject.php: SRS found in current WMC: ".$wmcGetApi->mainMap->getEpsg());
			$bbox = new Mapbender_bbox(
				$zoom[0],
				$zoom[1],
				$zoom[2],
				$zoom[3],
				$epsg = $wmcGetApi->mainMap->getEpsg()
			);
			//$wmcGetApi->mainMap->setExtent($bbox);
		}
	}
	$e = new mb_notice("javascripts/initWmcObject.php: calculated extent: " . json_encode($bbox));
	$wmcGetApi->mainMap->setExtent($bbox);		
}
// check if something have to be shown in disclaimer
if (
	count($resultObj["withoutId"]["wms"]) === 0 &&
	count($resultObj["invalidId"]["wms"]) === 0 &&
	count($resultObj["unavailable"]["wms"]) === 0 ||
	!$isSessionWmcModuleLoaded
) {
	//put them into the session to pull them later on
	Mapbender::session()->set("wmcConstraints", $resultObj);
//*******************************************************
	//Alternate approach: create map object from xml:
	//$e = new mb_notice("initWmcObj.php: build alternate js!");
	//$alternateWMC = new wmc();
	//$alternateWMC->createFromXml($wmcGetApi->xml);
	//$jsarray = $alternateWMC->toJavaScript();
	//foreach($jsarray as $key => $value){
	//	logit($value,"javascript_new.store","a+");
	//}
	//save to some position
	//$e = new mb_notice("initWmcObj.php: alternate js:".$alternateWMC->toJavaScript());
	//$e = new mb_notice("initWmcObj.php: alternate js build successfully!");
//*******************************************************
	$output = $wmcGetApi->wmsToJavaScript();
	//$e = new mb_notice("javascripts/initWmcObj.php: javascript mapset: ".implode(",",$output));
	$wmcJs = $wmcGetApi->toJavaScript(array());//old way - why give an empty array?
	$wmcJs = implode(";\n", $wmcJs);
//$e = new mb_exception($wmcJs);
//$e = new mb_exception("initWmcObj.php: after wmcJs!****************************");
	$extentJs = $wmcGetApi->extentToJavaScript();
//$e = new mb_exception($extentJs);
//$e = new mb_exception("initWmcObj.php: after extentJs!****************************");
	$output[] = <<<JS
		Mapbender.events.afterInit.register(function () {
			$wmcJs;
		});
		Mapbender.events.beforeInit.register(function () {
			$extentJs
		});
JS;
	Mapbender::session()->delete("wmcGetApi", $wmcGetApi);
} else {
	Mapbender::session()->set("wmcConstraints", $resultObj);
	//$output = $wmc->wmsToJavaScript();
	//$wmcJs = $wmc->toJavaScript(array());
	$output = $wmcGetApi->wmsToJavaScript();
	$wmcJs = $wmcGetApi->toJavaScript(array());
	$wmcJs = implode(";\n",$wmcJs);
	//$extentJs = $wmc->extentToJavaScript();
	$extentJs = $wmcGetApi->extentToJavaScript();
	$output[] = <<<JS
		Mapbender.events.afterInit.register(function () {
			$wmcJs;
		});
		Mapbender.events.beforeInit.register(function () {
			$extentJs
		});
JS;
	Mapbender::session()->set("wmcGetApi", base64_encode($wmcGetApi));
}
$outputString = "";
for ($i = 0; $i < count($output); $i++) {
	$outputString .= administration::convertOutgoingString($output[$i]);
}
//$e = new mb_exception("javascripts/initWmcObj.php: output map: " . $outputString);

$wmcFeaturetypeJson = $wmc->featuretypeConfToJavaScript();
$wfsConfIdString = $wmcGetApi->generalExtensionArray['WFSCONFIDSTRING'];
if($wfsConfIdString != ""){
	$wmcFeaturetypeStr = <<<JS
		Mapbender.events.afterInit.register(function () {
			$('#body').trigger('addFeaturetypeConfs', [
				{ featuretypeConfObj : $wmcFeaturetypeJson,
					wfsConfIdString: "$wfsConfIdString"}
			]);
		});
JS;
}
$outputString .= $wmcFeaturetypeStr;
// GeoRSS
$GeoRSSStr = " Mapbender.events.afterInit.register(function () {";
foreach($inputGeoRSSArray as $inputGeoRSSUrl){
	$GeoRSSStr .= 'try {$("#mapframe1").georss({url: "'.$inputGeoRSSUrl .'"})} catch(e) {new Mb_warning("GeoRSS module not loaded")}';
}
$GeoRSSStr .="}); ";
$outputString .= $GeoRSSStr;
// KML
$KmlStr = " Mapbender.events.afterInit.register(function () {";
foreach($inputKmlArray as $inputKmlUrl){
	$KmlStr .= 'try {$("#mapframe1").kml({url: "'.$inputKmlUrl .'"})} catch(e) {new Mb_warning("KML module not loaded")}';
}
$KmlStr .="}); ";
$outputString .= $KmlStr;
//applicationMetadata
if (isset($applicationMetadataJson) && $applicationMetadataJson != "") {
    //$e = new mb_exception($applicationMetadataJson);
    $applicationMetadataStr = " Mapbender.events.afterInit.register(function () {";
    $applicationMetadataStr .= 'try {Mapbender.modules.applicationMetadata.initForm('.$applicationMetadataJson.')} catch(e) {new Mb_warning("applicationMetadata module not loaded")}';
    $applicationMetadataStr .="}); ";
    $outputString .= $applicationMetadataStr;
} else {
    $applicationMetadataStr = " Mapbender.events.afterInit.register(function () {";
    $applicationMetadataStr .= 'try {Mapbender.modules.applicationMetadata.initForm(false)} catch(e) {new Mb_warning("applicationMetadata module not loaded")}';
    $applicationMetadataStr .="}); ";
    $outputString .= $applicationMetadataStr;
}
echo $outputString;
// logit($outputString,"javascript_old.store","w");
Mapbender::session()->delete("addwms_showWMS");
Mapbender::session()->delete("addwms_zoomToExtent");
if ($saveInSession == false) {
    //delete wmc in session and session filename
	//$admin->delFromStorage($wmc_filename);#is not defined til now
	//$e = new mb_exception('javascripts/initWmcObj.php: delete wmc from session and storage - cause it may be found again ;-)');
	$admin->delFromStorage($wmc_filename, TMP_WMC_SAVE_STORAGE);
	Mapbender::session()->delete("mb_wmc");
	//delete also the current gui - this is handled by revertGui...
} else {
	//TODO: do the following only if wmc id was given by GET parameter or always - write wmc to session before initialize the client 
	if (isset($wmc_filename) && $wmc_filename != ""){
	    $admin->putToStorage($wmc_filename, $wmcGetApi->toXml(), TMP_WMC_SAVE_STORAGE, 3600);
        //$e = new mb_exception("javascripts/initWmcObj.php: write initial wmc to storage - filename: ".$wmc_filename);
	} else {
		$wmc_filename = TMPDIR."/wmc/".time()."_".uniqid();
		$admin->putToStorage($wmc_filename, $wmcGetApi->toXml(), TMP_WMC_SAVE_STORAGE, 3600);
	}
    //$e = new mb_exception('javascripts/initWmcObj.php: wmc remain in session');
}
//$e = new mb_exception('javascripts/initWmcObj.php: Actual mb_wmc from session: '.Mapbender::session()->get("mb_wmc"));
unset($output);
unset($wmc);
$e = new mb_notice("javascripts/initWmcObj.php: All done!");
?>
