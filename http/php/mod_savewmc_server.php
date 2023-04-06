<?php
/*
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_wmc.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_lzw_decompress.php");

//usefull for debugging purposes
function logit($text){
	$time = microtime();
	if($h = fopen("/tmp/".$time."_savewmc_server.log","a")){
		$content = $text .chr(13).chr(10);
		if(!fwrite($h,$content)){
			#exit;
		}
		fclose($h);
	}
}

//require_once(dirname(__FILE__)."/../classes/class_cache.php");
$admin = new administration();

$ajaxResponse = new AjaxResponse($_POST);
if($ajaxResponse->getMethod() != "saveWMC") {
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage("method invalid");
	$ajaxResponse->send();
	exit;
}

$json = new Mapbender_JSON();

// get data from POST and SESSION
$userId = Mapbender::session()->get("mb_user_id");
$mapObject = $ajaxResponse->getParameter('mapObject');
$lzwCompressed = $ajaxResponse->getParameter('lzwCompressed');
$saveInSession = $ajaxResponse->getParameter('saveInSession');
$extensionData = $json->decode($ajaxResponse->getParameter('extensionData'));
$attributes =  $ajaxResponse->getParameter('attributes');
$overwrite = $ajaxResponse->getParameter('overwrite');
$overwrite = $overwrite == "1" ? true : false;
//for debugging, write mapObject to file
if ($lzwCompressed == 'true') {
	//$e = new mb_exception('mod_savewmc_server.php: mapObject: '.implode(',',$mapObject));
	$mapObject = lzw_decompress($mapObject);
	//$e = new mb_exception('mod_savewmc_server.php: mapObject uncompressed: '.$mapObject);
	//$filename = TMPDIR."/formerly_compressed_json.txt";//will be set to new one cause ?
} else {
	//$filename = TMPDIR."/formerly_uncompressed_json.txt";//will be set to new one cause ?
}
//file_put_contents($filename, $mapObject);

$mapObject = $json->decode($mapObject);
//******for debugging of mapobject you can activate following lines
//store mapObjects as pretty print to tmp folder for debug purpose!!
/*$mapObjLog = json_encode($mapObject, JSON_PRETTY_PRINT);
logit($mapObjLog);*/
//******

$e = new mb_notice('mod_savewmc_server.php: mapObject has been decoded from json');
// create WMC object
$wmc = new wmc();
if($overwrite) {
	$wmc->createFromJs($mapObject, $attributes->title, $extensionData, $attributes->wmc_id);
}
else {
	$wmc->createFromJs($mapObject, $attributes->title, $extensionData);
}

if ($saveInSession === 1) {
	//CLEAN SESSION WMC FILES
	//do this by cronjob or handled by memcached itself!
    	//store XML location, that is defined in mapbender.conf (memcached server, session or file)
        //look for existing referenced wmc in session
    	if(Mapbender::session()->get("mb_wmc")) {
        	$filename = Mapbender::session()->get("mb_wmc");
    	} else {
        	$filename = TMPDIR."/wmc/".time()."_".uniqid();//will be set to new one cause ?
    	}
	//
	$time_start = microtime();
	$wmcXml = $wmc->xml;
	$admin->putToStorage($filename, $wmcXml, TMP_WMC_SAVE_STORAGE, TMP_WMC_MAX_AGE);
	$time_end = microtime();
	$timediff = $time_end - $time_start;
	$e = new mb_notice('mod_savewmc_server.php: time to save wmc to storage: '.$timediff. '('.TMP_WMC_SAVE_STORAGE.')');
    	Mapbender::session()->set("mb_wmc",$filename);
	$epsgString = $wmc->mainMap->extentToJavascript();
	// get epsg code from jquery string
	preg_match('/EPSG:\d{4,5}/',$epsgString, $matches);
	$epsgString = $matches[0];
	if (preg_match('/EPSG:\d{4,5}/',$epsgString, $matches)) {
		$epsgString = $matches[0];
		$e = new mb_notice("epsg: ".$epsgString);
		$epsg = str_replace("EPSG:", "", $epsgString);
		Mapbender::session()->set("epsg",$epsg);
	}
	Mapbender::session()->set("previous_gui", Mapbender::session()->get("mb_user_gui"));
	$e = new mb_notice("mod_savewmc_server: save WMC into storage succeeded.");
	$ajaxResponse->setSuccess(true);
	$ajaxResponse->setResult(_mb("saved wmc document to temporal storage"));
} else {
	// insert WMC into database
	if(isset($attributes->title)) {
		$attributes->title = trim($attributes->title);
		if($attributes->title == "") {
		  $ajaxResponse->setSuccess(false);
		  $ajaxResponse->setMessage(_mb("WMC document must have a title."));
		  $ajaxResponse->send();
		  exit;
		}
	}
	else{
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("WMC document must have a title."));
		$ajaxResponse->send();
		exit;
	}
	foreach($mapObject as $map)	{
		#$e = new mb_exception("php/mod_savewmc_server.php: isOverview: ".$map->isOverview);
		if (isset($map->isOverview) && $map->isOverview == "1") { continue; }
		$wmc->wmc_extent  = $map->extent;
		$wmc->wmc_srs	  = $map->epsg;
	}
	// make a keyword array here
    $kwds = explode(",",$attributes->keywords);
    $wmckwds = $wmc->keyword;
   	if(is_array($wmckwds)) {
         foreach ($wmckwds as &$val) {
             $val = trim($val);
         }
    }
    $wmc->keyword = $wmckwds;
	$wmc->wmc_keyword = $kwds;

	$wmc->wmc_abstract = $attributes->abstract;
	$isoTopicCat = $attributes->isoTopicCat;
	foreach($isoTopicCat as $cat => $val) {
		$parts = explode("_",$cat);
		$wmc->isoTopicCats[] =  $parts[1];
	}
	
	$result = $wmc->insert($overwrite);

	if ($result["success"]) {
		$ajaxResponse->setSuccess(true);
		$ajaxResponse->setMessage(_mb("WMC")." ".$attributes->title." "._mb("has been saved."));
	} else {
		$ajaxResponse->setSuccess($result["success"]);
		$ajaxResponse->setMessage($result["message"]);
	}
}

$ajaxResponse->send();
?>
