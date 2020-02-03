<?php
require_once(dirname(__FILE__)."/../core/globalSettings.php");
require_once(dirname(__FILE__)."/../http/classes/class_wms.php");
require_once(dirname(__FILE__)."/../http/classes/class_universal_wfs_factory.php");
require_once(dirname(__FILE__)."/../http/classes/class_gui.php"); 

//require_once(dirname(__FILE__)."/../http/classes/class_administration.php");
//require_once dirname(__FILE__) . "/../http/php/mb_validatePermission.php";
//require_once(dirname(__FILE__)."/../http/classes/class_user.php");

//***************************************************************
//script to register ows into mapbenders database from cli - give back the created database id
//***************************************************************
//serviceType - string - examples 'wms', 'wfs'
//serviceAccessUrl - example: ''
//userId - integer - example 3
//guiId - string - example 'service_container1'
//authorization - test if user is allowed to register ows - check modules
//
//example: php registerOwsCli.php userId=1 guiId="Geoportal-RLP" serviceType="wms" serviceAccessUrl="http://www.geoportal.rlp.de/mapbender/php/mod_showMetadata.php/../wms.php?layer_id=61681&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS&withChilds=1"
//example for integration in bash script: wms_id_1=`/usr/bin/php -f registerOwsCli.php userId=1 guiId='Geoportal-RLP' serviceType='wms' serviceAccessUrl='http://www.geoportal.rlp.de/mapbender/php/mod_showMetadata.php/../wms.php?layer_id=61681&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS&withChilds=1'`

//example for wfs:
//php registerOwsCli.php userId=1 guiId="Geoportal-RLP" serviceType="wfs" serviceAccessUrl="http://www.geoportal.rlp.de/mapbender/php/wfs.php?INSPIRE=1&FEATURETYPE_ID=1955&request=GetCapabilities&VERSION=1.1.0&SERVICE=WFS"

//***************************************************************
//parse arguments
$arguments = $argv;
array_shift($arguments);
foreach($arguments as $value) {
    $pieces = explode('=',$value);
    if(count($pieces) >= 2) {
        $real_key = $pieces[0];
        array_shift($pieces);
        $real_value = implode('=', $pieces);
        $real_arguments[$real_key] = $real_value;
    }
}
//***************************************************************
//read values
$userId = $real_arguments['userId'];
$guiId = $real_arguments['guiId'];
$serviceType = $real_arguments['serviceType'];
$serviceAccessUrl = $real_arguments['serviceAccessUrl'];
//***************************************************************
//TBD later
//$admin = new administration();
//$ownguis = $admin->getGuisByOwner(Mapbender::session()->get("mb_user_id"),true);
$authType = "";
$authUser = "";
$authPassword = "";
//test if guiId exists and is owned by user with id userId
//***************************************************************
//register
switch ($serviceType) {
    case "wms":
	$mywms = new wms();
	$mywms->setGeoRss = false;
        $mywms->twitterNews = false;
	$mywms->harvestCoupledDatasetMetadata = true;
	$mywms->owner = $userId;
	$result = $mywms->createObjFromXML($serviceAccessUrl);
	if ($result['success']) {
		$mywms->writeObjInDB($guiId, false, true, (integer)$userId);  
		echo $mywms->wms_id;
		die();
	} else {
		//echo $result['message'];
		echo "error";
		die();
	}
        break;
    case "wfs":
	$myWfsFactory = new UniversalWfsFactory();			
	$mywfs = $myWfsFactory->createFromUrl($serviceAccessUrl); 
	//$mywms->setGeoRss = false;
        //$mywms->twitterNews = false;
	//$mywms->harvestCoupledDatasetMetadata = true;
	$mywfs->owner = $userId;
	if (!is_null($mywfs)) {
		$mywfs->insertOrUpdate($userId);  
		$currentApp = new gui($guiId);
		$currentApp->addWfs($mywfs);
		echo $mywfs->wfs_id;
		die();
	} else {
		//echo $result['message'];
		echo "error";
		die();
	}
        break;
}
//$mywms->displayWMS();
//echo "\n";
//echo $serviceType." registered with id: ".$mywms->wms_id."\n";
echo $mywms->wms_id;
die();
?>
