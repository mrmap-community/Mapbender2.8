<?php
//2012-06-15-http://localhost/mapbender/php/mod_inspireDownloadFeed.php?id=70e0c3e5-707c-f8e1-8037-7b38702176d9&type=SERVICE&generateFrom=all
//example: 2013-09-03 http://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=e9d22d13-e045-f0e0-25cc-1f146d681216&type=DATASET&generateFrom=wfs&wfsid=216&OPENSEARCH=true&spatial_dataset_identifier_code=e9d22d13-e045-f0e0-25cc-1f146d681216&spatial_dataset_identifier_namespace=http%3A%2F%2Fwww.geoportal.rlp.de&crs=EPSG:25832&language=de
//20648
// $Id: mod_inspireDownloadFeed.php 235
// http://www.mapbender.org/index.php/
// Copyright (C) 2002 CCGIS 
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

//Script to generate a feed for a predefined dataset download as it is demanded in the INSPIRE Download Service guidance 3.0 from 04.05.2012. It will be generated from given wms layers dataurl attributs which are registrated in the mapbender database. The other possibility is, that the wms are used to built the download links. Therefore the wms must support the generation of image/tiff output format with geotiff tags. Many wms do this. These wms must also support a minimum of 1000x1000 pixel for a single getmap request. It works as a webservice. The requested id is the mapbender layers serial id. 
/*
 * There are 5 options for synthesizing ATOM-Feeds:
 *  * WMS Layer
 *  * WFS Featuretype
 *  * Link in WMS Capabilities (dataURL)
 *  * Downloadlink in ISO-Metadata
 *  * DCAT Distribution object in Iso19139->furtherLinksJson with dcat:accessService.dct:hasPart
 *    attribute to remote html <item> list (since 04/2024)
 */
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_connector.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
require_once dirname(__FILE__) . "/../../core/epsg.php";
require_once(dirname(__FILE__) . "/../classes/class_Uuid.php");
require_once(dirname(__FILE__) . "/../../conf/mimetype.conf");
require_once(dirname(__FILE__) . "/../classes/class_cache.php");
require_once(dirname(__FILE__) . "/../classes/class_iso19139.php");
require_once(dirname(__FILE__) . "/../classes/class_crs.php");
require_once(dirname(__FILE__) . "/../classes/class_metadata_monitor.php");
require_once(dirname(__FILE__) . "/../classes/class_universal_wfs_factory.php");

//check_epsg_wms_13($tmp_epsg)
//http://www.weichand.de/inspire/dls/verwaltungsgrenzen.xml
$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);

$admin = new administration();
$mapbenderPath = MAPBENDER_PATH."/";
$mapbenderPathArray = parse_url($mapbenderPath);
$mapbenderServerUrl = $mapbenderPathArray['scheme']."://".$mapbenderPathArray['host'];

$imageResolution = 300;

$maxImageSize = 1000;

$maxFeatureCount = 100;

$alterAxisOrder = false;

$numberOfTiles = 0;
	
$furtherLink = array();

$featuretypeId = false;
//pull the needed things from tables datalink, md_metadata, layer, wms
 
//parse request parameter
//make all parameters available as upper case
foreach($_REQUEST as $key => $val) {
	$_REQUEST[strtoupper($key)] = $val;
}

//check if opensearchdescription is requested
if (isset($_REQUEST['GETOPENSEARCH']) & $_REQUEST['GETOPENSEARCH'] != "") {
	//validate 
	$testMatch = $_REQUEST["GETOPENSEARCH"];	
 	if ($testMatch != "true" && $testMatch != "false"){ 
		//echo 'GETOPENSEARCH: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>GETOPENSEARCH</b> is not valid (true or false).<br/>'; 
		die(); 		
 	}
	$getOpenSearch = $testMatch;
	if ($getOpenSearch == 'true') {
		$getOpenSearch = true;
	} else {
		$getOpenSearch = false;
	}
	$testMatch = NULL;
}
//check if opensearch should be used
if (isset($_REQUEST['OPENSEARCH']) & $_REQUEST['OPENSEARCH'] != "") {
	//validate 
	$testMatch = $_REQUEST["OPENSEARCH"];	
 	if ($testMatch != "true" && $testMatch != "false"){ 
		//echo 'OPENSEARCH: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>OPENSEARCH</b> is not valid (true or false).<br/>'; 
		die(); 		
 	}
	$openSearch = $testMatch;
	if ($openSearch == 'true') {
		$openSearch = true;
	} else {
		$openSearch = false;
	}
	$testMatch = NULL;
}

//validate request params
if (isset($_REQUEST['ID']) & $_REQUEST['ID'] != "") {
	//validate uuid
	$testMatch = $_REQUEST["ID"];
	$uuid = new Uuid($testMatch);
	$isUuid = $uuid->isValid();
	if (!$isUuid) {
		//echo 'Id: <b>'.$testMatch.'</b> is not a valid mapbender uuid.<br/>'; 
		echo 'Parameter <b>Id</b> is not a valid mapbender uuid.<br/>'; 
		die(); 		
 	}
	$recordId = $testMatch;
	$testMatch = NULL;
}

/*
//validate request params
if (isset($_REQUEST['ID']) & $_REQUEST['ID'] != "") {
	//validate integer
	$testMatch = $_REQUEST["ID"];
	$pattern = '/^[\d]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		echo 'Id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		die(); 		
 	}
	$recordId = $testMatch;
	$testMatch = NULL;
}
*/
if (!isset($_REQUEST['TYPE']) || $_REQUEST['TYPE'] == "") {
	echo '<b>Mandatory parameter type is not set!</b><br>Please set type to <b>DATASET</b> or <b>SERVICE</b>'; 
	die(); 	
}

//validate request params
if (isset($_REQUEST['TYPE']) & $_REQUEST['TYPE'] != "") {
	//validate type
	$testMatch = $_REQUEST["TYPE"];	
 	if ($testMatch != 'SERVICE' && $testMatch != 'DATASET'){ 
		//echo 'type: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>type</b> is not valid (DATASET or SERVICE).<br/>'; 
		die(); 		
 	}
	$type = $testMatch;
	$testMatch = NULL;
}

if (!isset($_REQUEST['GENERATEFROM']) || $_REQUEST['GENERATEFROM'] == "") {
	echo '<b>Mandatory parameter GENERATEFROM is not set!</b><br>Please set GENERATEFROM to <b>wmslayer</b>, <b>dataurl</b>, <b>metadata</b> or <b>wfs</b> or <b>remotelist</b>'; 
	die(); 	
}

//validate request params
if (isset($_REQUEST['GENERATEFROM']) & $_REQUEST['GENERATEFROM'] != "") {
	//validate type
	$testMatch = $_REQUEST["GENERATEFROM"];	
	if ($testMatch != 'wmslayer' && $testMatch != 'dataurl'  && $testMatch != 'wfs' && $testMatch != 'all' && $testMatch != 'metadata'  && $testMatch != 'remotelist'){ 
		//echo 'GENERATEFROM: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>GENERATEFROM</b> is not valid (wmslayer,dataurl,wfs,metadata,all,remotelist).<br/>'; 
		die(); 		
 	}
	$generateFrom = $testMatch;
	$testMatch = NULL;
}

//parse opensearch parameters - used if $openSearch=true
//q, spatial_dataset_identifier_namespace, spatial_dataset_identifier_code, crs, language
if (isset($_REQUEST['q']) & $_REQUEST['q'] != "") {
	//validate type
	$testMatch = $_REQUEST["q"];	
	$osQuery = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST['spatial_dataset_identifier_namespace']) & $_REQUEST['spatial_dataset_identifier_namespace'] != "") {
	//validate type
	$testMatch = $_REQUEST["spatial_dataset_identifier_namespace"];	
	$osDatasetNamespace = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST['spatial_dataset_identifier_code']) & $_REQUEST['spatial_dataset_identifier_code'] != "") {
	//validate type
	$testMatch = $_REQUEST["spatial_dataset_identifier_code"];	
	$osDatasetIdentifier = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST['crs']) & $_REQUEST['crs'] != "") {
	//validate type TODO
	$testMatch = $_REQUEST["crs"];	
 	/*if ($testMatch != 'wmslayer' && $testMatch != 'dataurl'  && $testMatch != 'wfs' && $testMatch != 'all'){ 
		//echo 'GENERATEFROM: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>GENERATEFROM</b> is not valid (wmslayer,dataurl,wfs,all).<br/>'; 
		die(); 		
 	}*/
	$osCrs = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST['request']) & $_REQUEST['request'] != "") {
	//validate type TODO
	$testMatch = $_REQUEST["request"];	
 	if ($testMatch != 'DescribeSpatialDataset' && $testMatch != 'GetSpatialDataset'){ 
		//echo 'GENERATEFROM: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>request</b> is not valid (GetSpatialDataset/DescribeSpatialDataset).<br/>'; 
		die(); 		
 	}
	$osRequest = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST['language']) & $_REQUEST['language'] != "") {
	//validate type TODO
	$testMatch = $_REQUEST["language"];	
 	/*if ($testMatch != 'wmslayer' && $testMatch != 'dataurl'  && $testMatch != 'wfs' && $testMatch != 'all'){ 
		//echo 'GENERATEFROM: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>GENERATEFROM</b> is not valid (wmslayer,dataurl,wfs,all).<br/>'; 
		die(); 		
 	}*/
	$osLanguage = $testMatch;
	$testMatch = NULL;
}

if ($generateFrom == "wmslayer") {
	//check if layerId is set too
	if (isset($_REQUEST['LAYERID']) & $_REQUEST['LAYERID'] != "") {
		$testMatch = $_REQUEST["LAYERID"];
		$pattern = '/^[\d]*$/';		
 		if (!preg_match($pattern,$testMatch)){ 
			//echo 'LAYERID must be an integer: <b>'.$testMatch.'</b> is not valid.<br/>'; 
			echo 'LAYERID must be an integer!<br/>'; 
			die(); 		
 		}
		$layerId = $testMatch;
		$testMatch = NULL;
	} else {
		echo 'Mandatory request parameter <b>layerid</b> must be set if download service should be generated by a wms layer!'; 
		die(); 	
	}
}


if ($generateFrom == "wfs") {
	//check if wfsId is set too
	if (isset($_REQUEST['WFSID']) & $_REQUEST['WFSID'] != "") {
		$testMatch = $_REQUEST["WFSID"];
		$pattern = '/^[\d]*$/';		
 		if (!preg_match($pattern,$testMatch)){ 
			//echo 'WFSID must be an integer: <b>'.$testMatch.'</b> is not valid.<br/>'; 
			echo 'WFSID must be an integer!<br/>'; 
			die(); 		
 		}
		$wfsId = $testMatch;
		$testMatch = NULL;
	} else {
		echo 'Mandatory request parameter <b>WFSID</b> must be set if download service should be generated by using a Web Feature Service!'; 
		die(); 	
	}
	//check if featuretypeid is set too
	if (isset($_REQUEST['featuretypeid']) && $_REQUEST['featuretypeid'] != "") {
		$testMatch = $_REQUEST["featuretypeid"];
		$pattern = '/^[\d]*$/';		
 		if (!preg_match($pattern,$testMatch)){ 
			echo 'FEATURETYPEID must be an integer!<br/>'; 
			die(); 		
 		}
		$featuretypeId = $testMatch;
		$testMatch = NULL;
        }
}

//Initialize XML document
$feedDoc = new DOMDocument('1.0');
$feedDoc->encoding = 'UTF-8';
$feedDoc->preserveWhiteSpace = false;
$feedDoc->formatOutput = true;

/*function addToQuery($paramName,$queryString,$string,$queryList) {
	//test if string was part of query before, if so, don't extent the query
	//TODO: the strings come from json and so they are urlencoded! maybe we have to decode them to find the commata
	$queryListComma = urldecode($queryList);
	$queryListC = ",".$queryListComma.",";
	$pattern = ','.$string.',';	
 	if (!preg_match($pattern, $queryListC)){
		//append the new element
		$queryListNew = $queryListC.$string;
		//echo "query string new: ".$queryListNew."<br>";
		//delete the commatas
		$queryListNew = ltrim($queryListNew,',');
		//generate the new query string
		$queryStringNew = str_replace($paramName.'='.$queryList,$paramName.'='.$queryListNew,$queryString);
		return $queryStringNew;
	} else {
		return $queryString;
	}
}*/

// function to delete one GET parameter totally from a query url 
function delTotalFromQuery($paramName,$queryString) {
	$queryString = "&".$queryString;
	$queryStringNew = preg_replace('/\b'.$paramName.'\=[^&]+&?/',$str2exchange,$queryString);
	$queryStringNew = ltrim($queryStringNew,'&');
	$queryStringNew = rtrim($queryStringNew,'&');
	return $queryStringNew;
}

function answerOpenSearchRequest () {
	global $admin, $type, $mapbenderMetadata, $indexMapbenderMetadata, $layerId, $wfsId, $mapbenderPath, $mapbenderServerUrl, $epsgId, $alterAxisOrder, $departmentMetadata, $userMetadata, $hasPermission, $m, $crs, $crsUpper, $countRessource, $furtherLink, $osDatasetIdentifier, $osQuery, $osDatasetNamespace, $osCrs, $osLanguage, $osRequest;
	//service feed url
	$serviceFeed = delTotalFromQuery("getopensearch",$mapbenderServerUrl.$_SERVER['REQUEST_URI']);//delete GETOPENSEARCH
	$serviceFeed = delTotalFromQuery("OPENSEARCH",$serviceFeed);
	//echo $serviceFeed;
	//die();
	//datasetfeed url
	$datasetFeed = str_replace("=SERVICE&","=DATASET&",$serviceFeed);
	//echo $datasetFeed;
	//die();
	$returnFile = false;
	switch ($generateFrom) {
				case "dataurl":
					$mimetype = $formatsMimetype[$mapbenderMetadata[$m]->format];
				break;
				case "wmslayer":
					$mimetype = "image/tiff";
				break;
				case "wfs":
					$mimetype = "application/gml+xml";
				break;
				case "metadata":
					$mimetype = $formatsMimetype[$mapbenderMetadata[$m]->format];
				break;
	}
	//check correct headers
	foreach (apache_request_headers() as $name => $value) {
		if ($name == "Accept" && $value == $mimetype) {
			$returnFile = true;
		}   	 
	}
	//TODO comment this line in productive environment - see inspire guidance for opensearch script
	$returnFile = true;
	//check for identifier 
	/*$e = new mb_exception("mod_inspireDownloadFeed.php: osDatasetIdentifier: ".$osDatasetIdentifier);
	$e = new mb_exception("mod_inspireDownloadFeed.php: osQuery: ".$osQuery);	
	$e = new mb_exception("mod_inspireDownloadFeed.php: osDatasetNamespace: ".$osDatasetNamespace);	
	$e = new mb_exception("mod_inspireDownloadFeed.php: numberOfTiles: ".$numberOfTiles);*/
	if (!$osDatasetIdentifier) {
		if (!$osQuery) {
			//redirect to service feed
			header("Location: ".$serviceFeed);
			die();
		}
		//the query search for an identifier ?
		$osDatasetIdentifier = $osQuery;
	}
	
	//check namespace
	if (!$osDatasetNamespace) {
			echo "No namespace given!";
			die();
	}
	//check crs and language
	if ($osCrs && $osCrs != $crs) {
		echo "Dataset not available in requested crs!";
		die();
	}
	if (!$osLanguage || $osLanguage == "*") {
		$osLanguage = "de";
	}
	if ($osLanguage != "de") {
		echo "Dataset not available in requested language!";
		die();
	}
	switch ($osRequest) {
		case "DescribeSpatialDataset":	
			//redirect to datasetfeed
			header("Location: ".$datasetFeed);
		break;
		case "GetSpatialDataset":
			//check count of links 
			//redirect to downloadlink
			//parse dataset atom feed and count links - maybe one or multiple!!!!
			$links = getDatasetFeedLinks($datasetFeed);
			$numberOfTiles = count($links);
			//$e = new mb_exception("mod_inspireDownloadFeed.php: numberOfTiles: ".$numberOfTiles);
			if ($numberOfTiles > 1) {
				//redirect to dataset atom feed 
				header("Location: ".$datasetFeed);
			} else {
				if ($returnFile) {
					//echo "links[0]:". $links[0];
					//die();
					header("Location: ".$links[0]);
				} else {
					//redirect to datasetfeed
					header("Location: ".$datasetFeed);
				}
			}	
			die();
		break;
	}
}

function getDatasetFeedLinks($datasetFeedUrl) {
	//$links = array();
	//get feed from remote server
	$feedConnector = new connector($datasetFeedUrl);
	$feedConnector->set("timeOut", "10");
	$feedFile = $feedConnector->file;
	libxml_use_internal_errors(true);
	try {
		$feedXML = simplexml_load_string($feedFile);
		if ($feedXML === false) {
			foreach(libxml_get_errors() as $error) {
        			$err = new mb_exception("mod_inspireDownloadFeed.php:".$error->message);
    			}
			throw new Exception("mod_inspireDownloadFeed.php:".'Cannot parse Feed!');
			return false;
		}
	}
	catch (Exception $e) {
    		$err = new mb_exception("mod_inspireDownloadFeed.php:".$e->getMessage());
		return false;
	}
	if ($feedXML != false) {
		$feedXML->registerXPathNamespace("georss", "http://www.georss.org/georss");
		$feedXML->registerXPathNamespace("inspire_dls", "http://inspire.ec.europa.eu/schemas/inspire_dls/1.0");
		$feedXML->registerXPathNamespace("defaultns", "http://www.w3.org/2005/Atom");
		$title = $feedXML->xpath('/defaultns:feed/defaultns:entry/defaultns:title');
		//TODO: This is only possible, if one entry is available as it is now. Maybe altered later.
		$title = $title[0];
		//for ($i=0; $i<=(count($title)-1); $i++) {
			$links = $feedXML->xpath('/defaultns:feed/defaultns:entry[1]/defaultns:link/@href');
		//}
	}
	return $links;
}

function generateOpenSearchDescription($feedDoc, $recordId, $generateFrom) {
	global $admin, $type, $mapbenderMetadata, $indexMapbenderMetadata, $layerId, $wfsId, $mapbenderPath, $mapbenderServerUrl, $epsgId, $alterAxisOrder, $departmentMetadata, $userMetadata, $hasPermission, $m, $crs, $crsUpper, $countRessource, $numberOfTiles;
	switch ($generateFrom) {
				case "dataurl":
					$mimetype = $formatsMimetype[$mapbenderMetadata[$m]->format];
				break;
				case "wmslayer":
					$mimetype = "image/tiff";
				break;
				case "wfs":
					$mimetype = "application/gml+xml";
				break;
				case "metadata":
					$mimetype = $formatsMimetype[$mapbenderMetadata[$m]->format];
				break;
	}
	//part which generates the feed 
	$feed =  $feedDoc->createElementNS('http://a9.com/-/spec/opensearch/1.1/', 'OpenSearchDescription');
	$feed = $feedDoc->appendChild($feed);
	//$feed->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$feed->setAttribute("xmlns:inspire_dls", "http://inspire.ec.europa.eu/schemas/inspire_dls/1.0");
	$feed->setAttribute("xmlns:xsi", "http://www.w3c.org/2001/XMLSchema-instance");
	$feed->setAttribute("xsi:schemaLocation", "http://a9.com/-/spec/opensearch/1.1/ OpenSearch.xsd");
	//ShortName
	$osShortName = $feedDoc->createElement("ShortName");
	$osShortNameText = $feedDoc->createTextNode("INSPIRE DLS");
	$osShortName->appendChild($osShortNameText);
	$feed->appendChild($osShortName);
	//Description
	$osDescription = $feedDoc->createElement("Description");
	$osDescriptionText = $feedDoc->createTextNode("Search Description für INSPIRE Download Service.");
	$osDescription->appendChild($osDescriptionText);
	$feed->appendChild($osDescription);
	//Url - self
	$urlSelf = $feedDoc->createElement("Url");
	$urlSelf->setAttribute("type", "application/opensearchdescription+xml");
	$urlSelf->setAttribute("rel", "self");
	$urlSelf->setAttribute("template", $mapbenderServerUrl.$_SERVER['REQUEST_URI']);
	$feed->appendChild($urlSelf);
	//Url - results Atom
	$urlSelf = $feedDoc->createElement("Url");
	$urlSelf->setAttribute("type", "application/atom+xml");
	$urlSelf->setAttribute("rel", "results");
	$urlSelf->setAttribute("template", str_replace("=SERVICE&","=DATASET&",$mapbenderServerUrl.$_SERVER['REQUEST_URI'])."&OPENSEARCH=true&q={searchTerms}");
	$feed->appendChild($urlSelf);
	//Url - describedby
	$urlDescribedby = $feedDoc->createElement("Url");
	$urlDescribedby->setAttribute("type", "application/atom+xml");
	$urlDescribedby->setAttribute("rel", "describedby");
	//see documentation
	$urlDescribedby->setAttribute("template", str_replace("=SERVICE&","=DATASET&",$mapbenderServerUrl.$_SERVER['REQUEST_URI'])."&OPENSEARCH=true&request=DescribeSpatialDataset&spatial_dataset_identifier_code={inspire_dls:spatial_dataset_identifier_code?}&spatial_dataset_identifier_namespace={inspire_dls:spatial_dataset_identifier_namespace?}&crs={inspire_dls:crs?}&language={language?}&q={searchTerms?}");
	//$urlDescribedby->setAttribute("template", str_replace("=SERVICE&","=DATASET&",$mapbenderServerUrl.$_SERVER['REQUEST_URI']));
	$feed->appendChild($urlDescribedby);
	//Url - results single part (e.g. zipfile)
	$urlResults = $feedDoc->createElement("Url");
	$urlResults->setAttribute("type", $mimetype);//TODO give reasonable format!! - mimetypes??? geotiff/gml/other - see format of metadata
	$urlResults->setAttribute("rel", "results");
	$urlResults->setAttribute("template", str_replace("=SERVICE&","=DATASET&",$mapbenderServerUrl.$_SERVER['REQUEST_URI'])."&OPENSEARCH=true&request=GetSpatialDataset&spatial_dataset_identifier_code={inspire_dls:spatial_dataset_identifier_code?}&spatial_dataset_identifier_namespace={inspire_dls:spatial_dataset_identifier_namespace?}&crs={inspire_dls:crs?}&language={language?}&q={searchTerms?}");
	$feed->appendChild($urlResults);
	//for firefox?
	$urlResults = $feedDoc->createElement("Url");
	$urlResults->setAttribute("type", "text/html");//TODO give reasonable format!! - mimetypes??? geotiff/gml/other - see format of metadata - see https://developer.mozilla.org/de/docs/OpenSearch_Plugin_f%C3%BCr_Firefox_erstellen
	$urlResults->setAttribute("rel", "results");
	$urlResults->setAttribute("template", str_replace("=SERVICE&","=DATASET&",$mapbenderServerUrl.$_SERVER['REQUEST_URI'])."&OPENSEARCH=true&request=GetSpatialDataset&spatial_dataset_identifier_code={inspire_dls:spatial_dataset_identifier_code?}&spatial_dataset_identifier_namespace={inspire_dls:spatial_dataset_identifier_namespace?}&crs={inspire_dls:crs?}&language={language?}&q={searchTerms?}");
	$feed->appendChild($urlResults);

	//Url -results multi part
	/*
	$urlResults = $feedDoc->createElement("Url");
	$urlResults->setAttribute("type", "application/atom");
	$urlResults->setAttribute("rel", "results");
	$urlResults->setAttribute("template", $mapbenderServerUrl.$_SERVER['SCRIPT_NAME']."?spatial_dataset_identifier_code={inspire_dls:spatial_dataset_identifier_code?}&amp;spatial_dataset_identifier_namespace={inspire_dls:spatial_dataset_identifier_namespace?}&amp;crs={inspire_dls:crs?}&amp;language={language?}&amp;q={searchTerms?}");
	$feed->appendChild($urlResults);
	*/
	//Metadata for OpenSearch
	//Contact
	$osContact = $feedDoc->createElement("Contact");
	$osContactText = $feedDoc->createTextNode("kontakt@geoportal.rlp.de");
	$osContact->appendChild($osContactText);
	$feed->appendChild($osContact);
	//Tags
	$osTags = $feedDoc->createElement("Tags");
	$osTagsText = $feedDoc->createTextNode("Tags");
	$osTags->appendChild($osTagsText);
	$feed->appendChild($osTags);
	//LongName
	$osLongName = $feedDoc->createElement("LongName");
	$osLongNameText = $feedDoc->createTextNode("LongName");
	$osLongName->appendChild($osLongNameText);
	$feed->appendChild($osLongName);
	//Image
	$osImage = $feedDoc->createElement("Image");
	$osImage->setAttribute("height", "16");
	$osImage->setAttribute("width", "16");
	$osImage->setAttribute("type", "image/png");
	$osImageText = $feedDoc->createTextNode("http://www.geoportal.rlp.de/fileadmin/design/logo_gdi-rp.png");
	$osImage->appendChild($osImageText);
	$feed->appendChild($osImage);
	//Query
	//example
	//$mapbenderMetadata[$m]->datasetid_codespace."#".$mapbenderMetadata[$m]->datasetid;
	$osQuery = $feedDoc->createElement("Query");
	$osQuery->setAttribute("role", "example");
	$osQuery->setAttribute("inspire_dls:spatial_dataset_identifier_namespace", $mapbenderMetadata[$m]->datasetid_codespace);
	$osQuery->setAttribute("inspire_dls:spatial_dataset_identifier_code", $mapbenderMetadata[$m]->datasetid);
	$osQuery->setAttribute("inspire_dls:crs", "EPSG:".$epsgId);
	$osQuery->setAttribute("language", "de");
	$osQuery->setAttribute("title", $mapbenderMetadata[$m]->metadata_title);
	$osQuery->setAttribute("count", "1");
	//$osQueryText = $feedDoc->createTextNode("Query");
	//$osQuery->appendChild($osQueryText);
	$feed->appendChild($osQuery);
	//Developer
	$osDeveloper = $feedDoc->createElement("Developer");
	$osDeveloperText = $feedDoc->createTextNode("Rheinland-Pfalz, Zentrale Stelle GDI-RP");
	$osDeveloper->appendChild($osDeveloperText);
	$feed->appendChild($osDeveloper);
	//Language
	$osLanguage = $feedDoc->createElement("Language");
	$osLanguageText = $feedDoc->createTextNode("de");
	$osLanguage->appendChild($osLanguageText);
	$feed->appendChild($osLanguage);
	return $feedDoc->saveXML();
}

function addBboxEntry($bboxWfsArray, &$bboxWfs, &$countBbox, &$multiPolygonText, $numberOfObjects, $crs, $wfs, $maxFeatureCount, $featuretypeName, $geometryFieldName) {
    $e = new mb_notice("php/mod_inspireDownloadFeed.php: maxFeatureCount: " . $maxFeatureCount);
    $minxWfs = $bboxWfsArray[0];
    $minyWfs = $bboxWfsArray[1];
    $maxxWfs = $bboxWfsArray[2];
    $maxyWfs = $bboxWfsArray[3];
    if ($numberOfObjects <= $maxFeatureCount) {
        $bboxWfs[$featuretypeName][$countBbox] = $minxWfs.",".$minyWfs.",".$maxxWfs.",".$maxyWfs;
        //NEW 2022-07-11 - use a single sql to calculate the intersections
        $multiPolygonText .= "((" . $minxWfs . " " . $minyWfs . "," . $maxxWfs . " " . $minyWfs . "," . $maxxWfs . " " . $maxyWfs . ",";
        $multiPolygonText .= $minxWfs . " " . $maxyWfs . "," . $minxWfs . " " . $minyWfs . ")),";
        $countBbox++;
        $e = new mb_notice("php/mod_inspireDownloadFeed.php: addBboxEntry direkt - number of objects: " . $numberOfObjects);
    } else {
        //split bbox in two half bboxes and call the function for each of the bboxes
        $e = new mb_notice("php/mod_inspireDownloadFeed.php: split bboxes in two parts");
        //first check which side - use the longer side to split
        if (($maxxWfs - $minxWfs) >= ($maxyWfs - $minyWfs)) {
            $firstBbox = array($minxWfs, $minyWfs, $minxWfs + ($maxxWfs - $minxWfs) / 2, $maxyWfs);
        } else {
            $firstBbox = array($minxWfs, $minyWfs, $maxxWfs, $minyWfs + ($maxyWfs - $minyWfs) / 2);
        }
        $bboxFilter = getBboxFilter($firstBbox, $crs, $wfs->getVersion(), $geometryFieldName, $alterAxisOrder);
        //count features in current bbox
        $featureHitsBbox = $wfs->countFeatures( $featuretypeName, $bboxFilter, false, false, false, 'GET');
        if ($featureHitsBbox <= $maxFeatureCount) {
            $bboxWfs[$featuretypeName][$countBbox] = $firstBbox[0].",".$firstBbox[1].",".$firstBbox[2].",".$firstBbox[3];
            //NEW 2022-07-11 - use a single sql to calculate the intersections
            $multiPolygonText .= "((" . $firstBbox[0] . " " . $firstBbox[1] . "," . $firstBbox[2] . " " . $firstBbox[1] . "," . $firstBbox[2] . " " . $firstBbox[3] . ",";
            $multiPolygonText .=  $firstBbox[0] . " " . $firstBbox[3] . "," . $firstBbox[0] . " " . $firstBbox[1] . ")),";
            $countBbox++;
            $e = new mb_notice("php/mod_inspireDownloadFeed.php: addBboxEntry firstBox - number of objects: " . $featureHitsBbox);
        } else {
            addBboxEntry($firstBbox, $bboxWfs, $countBbox, $multiPolygonText, $featureHitsBbox, $crs, $wfs, $maxFeatureCount, $featuretypeName, $geometryFieldName);
        }
        //second bbox
        if (($maxxWfs - $minxWfs) >= ($maxyWfs - $minyWfs)) {
            $secondBbox = array($minxWfs + ($maxxWfs - $minxWfs) / 2, $minyWfs, $maxxWfs, $maxyWfs);
        } else {
            $secondBbox = array($minxWfs, $minyWfs + ($maxyWfs - $minyWfs) / 2, $maxxWfs, $maxyWfs);
        }
        $bboxFilter = getBboxFilter($secondBbox, $crs, $wfs->getVersion(), $geometryFieldName, $alterAxisOrder);
        //count features in current bbox
        $featureHitsBbox = $wfs->countFeatures( $featuretypeName, $bboxFilter, false, false, false, 'GET');
        if ($featureHitsBbox <= $maxFeatureCount) {
            $bboxWfs[$featuretypeName][$countBbox] = $secondBbox[0].",".$secondBbox[1].",".$secondBbox[2].",".$secondBbox[3];
            //NEW 2022-07-11 - use a single sql to calculate the intersections
            $multiPolygonText .= "((" . $secondBbox[0] . " " . $secondBbox[1] . "," . $secondBbox[2] . " " . $secondBbox[1] . "," . $secondBbox[2] . " " . $secondBbox[3] . ",";
            $multiPolygonText .=  $secondBbox[0] . " " . $secondBbox[3] . "," . $secondBbox[0] . " " . $secondBbox[1] . ")),";
            $countBbox++;
            $e = new mb_notice("php/mod_inspireDownloadFeed.php: addBboxEntry secondBox - number of objects: " . $featureHitsBbox);
        } else {
            addBboxEntry($secondBbox, $bboxWfs, $countBbox, $multiPolygonText, $featureHitsBbox, $crs, $wfs, $maxFeatureCount, $featuretypeName, $geometryFieldName);
        }
    }
}

function getGeometryFieldNameFromMapbenderDb($mapbenderGeoemtryFieldName, $mapbenderFeaturetypeName) {
    if (!isset($mapbenderGeoemtryFieldName) || $mapbenderGeoemtryFieldName == '') {
        $geometryFieldName = 'geometry';
    } else {
        $geometryFieldName = $mapbenderGeoemtryFieldName;
    }
    if (strpos($mapbenderFeaturetypeName, ':') !== false) {
        $ftNamespace = explode(':', $mapbenderFeaturetypeName);
        $ftNamespace = $ftNamespace[0];
        $geometryFieldName = $ftNamespace.':'.$geometryFieldName;
    } else {
        $ftNamespace = false;
        $geometryFieldName = $geometryFieldName;
    }
    return $geometryFieldName;
}

function getBboxFilter($bbox, $crs, $wfs_version, $geometryFieldName, $switchAxisOrder=false) {
    if ($switchAxisOrder) {
        $newBbox[0] = $bbox[1];
        $newBbox[1] = $bbox[0];
        $newBbox[2] = $bbox[3];
        $newBbox[3] = $bbox[2];
        $bbox = $newBbox;
    }
    //if geometry name has an namespace - separate them for wfs 1.1.0
    if (strpos($geometryFieldName, ':') !== false) {
        $ftNamespace = explode(':', $geometryFieldName);
        $ftNamespace = $ftNamespace[0];
        $geometryFieldNameWithoutNamespace = str_replace($ftNamespace . ":", "", $geometryFieldName);
    } else {
        $geometryFieldNameWithoutNamespace = $geometryFieldName;
    }
    switch ($wfs_version) {
        case "2.0.0":
            $bboxFilter = '<fes:Filter xmlns:fes="http://www.opengis.net/fes/2.0"><fes:BBOX>';
            $bboxFilter .= '<fes:ValueReference>'.$geometryFieldName.'</fes:ValueReference>';
            //<gml:Envelope srsName="urn:ogc:def:crs:EPSG::1234">
            $bboxFilter .= '<gml:Envelope xmlns:gml="http://www.opengis.net/gml/3.2" srsName="'.$crs.'">';
            //FIX for ESRI? TODO
            $bboxFilter .= '<gml:lowerCorner>'.$bbox[0].' '.$bbox[1].'</gml:lowerCorner>';
            $bboxFilter .= '<gml:upperCorner>'.$bbox[2].' '.$bbox[3].'</gml:upperCorner>';
            $bboxFilter .= '</gml:Envelope>';
            $bboxFilter .= '</fes:BBOX>';
            $bboxFilter .= '</fes:Filter>';
            //$bboxFilter = rawurlencode(utf8_decode($bboxFilter));
            break;
        case "2.0.2":
            $bboxFilter = '<fes:Filter xmlns:fes="http://www.opengis.net/fes/2.0"><fes:BBOX>';
            $bboxFilter .= '<fes:ValueReference>'.$geometryFieldName.'</fes:ValueReference>';
            //<gml:Envelope srsName="urn:ogc:def:crs:EPSG::1234">
            $bboxFilter .= '<gml:Envelope xmlns:gml="http://www.opengis.net/gml/3.2" srsName="'.$crs.'">';
            //FIX for ESRI? TODO
            $bboxFilter .= '<gml:lowerCorner>'.$bbox[0].' '.$bbox[1].'</gml:lowerCorner>';
            $bboxFilter .= '<gml:upperCorner>'.$bbox[2].' '.$bbox[3].'</gml:upperCorner>';
            $bboxFilter .= '</gml:Envelope>';
            $bboxFilter .= '</fes:BBOX>';
            $bboxFilter .= '</fes:Filter>';
            //$bboxFilter = rawurlencode(utf8_decode($bboxFilter));
            break;
        case "1.1.0":
            $bboxFilter = '<ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:BBOX>';
            //$bboxFilter = '<ogc:Filter xmlns:ogc="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml"><ogc:BBOX>';
            //$bboxFilter .= '<gml:Box srsName="EPSG:'.$epsgId[1].'"';
            //? $mapbenderMetadata[$i]->geometry_field_name[0];
            $bboxFilter .= '<ogc:PropertyName>' . $geometryFieldNameWithoutNamespace . '</ogc:PropertyName>';
            $bboxFilter .= '<gml:Box xmlns:gml="http://www.opengis.net/gml" srsName="'.$crs.'">';
            $bboxFilter .= '<gml:coordinates decimal="." cs="," ts=" ">';
            //$currentBbox = explode(',',$bboxWfs[$mapbenderMetadata[$i]->featuretype_name][$l]);
            //$e = new mb_notice("Bounding box ".$l." : ".$l.$bboxWfs[$mapbenderMetadata[$i]->featuretype_name][$l]);
            //fix for esri????? TODO check crs axes order handling
            //if (strtoupper($mapbenderMetadata[$i]->geometry_field_name[0] == "SHAPE")) {
            //	$bboxFilter .= $currentBboxGetFeature[1].','.$currentBboxGetFeature[0].' '.$currentBboxGetFeature[3].','.$currentBboxGetFeature[2];
                //} else {
                $bboxFilter .= $bbox[0].','.$bbox[1].' '.$bbox[2].','.$bbox[3];
                //}
                $bboxFilter .= '</gml:coordinates></gml:Box></ogc:BBOX></ogc:Filter>';
                //$e = new mb_exception("php/mod_inspireDownloadFeed.php: bbox filter wfs 1.1.0: " . $bboxFilter);
                //$bboxFilter = rawurlencode(utf8_decode($bboxFilter));
                break;
    }
    return $bboxFilter;
}

function readInfoFromDatabase($recordId, $generateFrom){
	global $admin, $type, $mapbenderMetadata, $indexMapbenderMetadata, $layerId, $wfsId, $mapbenderPath, $mapbenderServerUrl, $epsgId, $alterAxisOrder, $departmentMetadata, $userMetadata, $hasPermission, $m, $crs, $crsUpper, $countRessource;
	
	switch ($generateFrom) {
		case "dataurl":
			$sql = <<<SQL
select *, 'dataurl' as origin from (select * from (select * from (select * from (select mb_metadata.metadata_id, layer_relation.layer_name,layer_relation.inspire_download, layer_relation.fkey_wms_id, layer_relation.layer_id, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, st_xmin(mb_metadata.the_geom) || ',' || st_ymin(mb_metadata.the_geom) || ',' || st_xmax(mb_metadata.the_geom) || ',' || st_ymax(mb_metadata.the_geom)  as metadata_bbox, mb_metadata.bounding_geom as polygon, layer_relation.layer_title, layer_relation.layer_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.lastchanged as md_timestamp   from (select * from layer inner join ows_relation_metadata on layer.layer_id = ows_relation_metadata.fkey_layer_id) as layer_relation inner join mb_metadata on layer_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) as layer_metadata inner join ows_relation_data on ows_relation_data.fkey_layer_id = layer_metadata.layer_id) as layer_relation_data inner join datalink on layer_relation_data.fkey_datalink_id = datalink.datalink_id) as layer_data inner join wms on layer_data.fkey_wms_id = wms.wms_id)  as layer_wms, layer_epsg where layer_wms.layer_id = layer_epsg.fkey_layer_id and layer_epsg.epsg = 'EPSG:4326';  
SQL;
		$generateFromDataurl = true;
		break;

		case "wmslayer":
			$sql = <<<SQL
select *, 'wmslayer' as origin from (select * from (select mb_metadata.metadata_id, layer_relation.layer_name, layer_relation.fkey_wms_id, layer_relation.layer_id,layer_relation.inspire_download, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, st_xmin(mb_metadata.the_geom) || ',' || st_ymin(mb_metadata.the_geom) || ',' || st_xmax(mb_metadata.the_geom) || ',' || st_ymax(mb_metadata.the_geom)  as metadata_bbox, mb_metadata.bounding_geom as polygon, layer_relation.layer_title, layer_relation.layer_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.lastchanged as md_timestamp   from (select * from layer inner join ows_relation_metadata on layer.layer_id = ows_relation_metadata.fkey_layer_id) as layer_relation inner join mb_metadata on layer_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) layer_data inner join wms on layer_data.fkey_wms_id = wms.wms_id)  as layer_wms, layer_epsg where layer_wms.layer_id = layer_epsg.fkey_layer_id and layer_epsg.epsg = 'EPSG:4326' and layer_wms.layer_id = $2;  
SQL;
		break;

		case "wfs":
			$sql = <<<SQL
select *, 'wfs' as origin from (select mb_metadata.metadata_id, featuretype_relation.featuretype_name, featuretype_relation.fkey_wfs_id, featuretype_relation.featuretype_id,featuretype_relation.inspire_download, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, st_xmin(mb_metadata.the_geom) || ',' || st_ymin(mb_metadata.the_geom) || ',' || st_xmax(mb_metadata.the_geom) || ',' || st_ymax(mb_metadata.the_geom)  as metadata_bbox, mb_metadata.bounding_geom as polygon, featuretype_relation.featuretype_title, featuretype_relation.featuretype_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace,  featuretype_relation.featuretype_latlon_bbox as latlonbbox, featuretype_relation.featuretype_srs, mb_metadata.lastchanged as md_timestamp from (select * from wfs_featuretype inner join ows_relation_metadata on wfs_featuretype.featuretype_id = ows_relation_metadata.fkey_featuretype_id) as featuretype_relation inner join mb_metadata on featuretype_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) as featuretype_data inner join wfs on featuretype_data.fkey_wfs_id = wfs.wfs_id where wfs.wfs_id = $2;
SQL;
		break;
		case "metadata":
			$sql = <<<SQL
select 'metadata' as origin, mb_metadata.metadata_id, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, st_xmin(mb_metadata.the_geom) || ',' || st_ymin(mb_metadata.the_geom) || ',' || st_xmax(mb_metadata.the_geom) || ',' || st_ymax(mb_metadata.the_geom)  as metadata_bbox, mb_metadata.bounding_geom as polygon,  mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.fkey_mb_user_id,  mb_metadata.lastchanged as md_timestamp, st_xmin(the_geom) || ',' || st_ymin(the_geom) || ',' || st_xmax(the_geom) || ',' || st_ymax(the_geom)  as bbox2d, datalinks, transfer_size FROM mb_metadata where uuid = $1 and inspire_download = 1;
SQL;
		break;
		case "remotelist":
		    $sql = <<<SQL
select 'remotelist' as origin, mb_metadata.metadata_id, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, st_xmin(mb_metadata.the_geom) || ',' || st_ymin(mb_metadata.the_geom) || ',' || st_xmax(mb_metadata.the_geom) || ',' || st_ymax(mb_metadata.the_geom)  as metadata_bbox, mb_metadata.bounding_geom as polygon,  mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.fkey_mb_user_id,  mb_metadata.lastchanged as md_timestamp, st_xmin(the_geom) || ',' || st_ymin(the_geom) || ',' || st_xmax(the_geom) || ',' || st_ymax(the_geom)  as bbox2d, further_links_json as datalinks, transfer_size FROM mb_metadata where uuid = $1;
SQL;
		    break;
		case "all":
			$sql = array();
			$sql[0] = <<<SQL
select *, 'dataurl' as origin from (select * from (select * from (select * from (select mb_metadata.metadata_id, layer_relation.layer_name, layer_relation.fkey_wms_id, layer_relation.layer_id,layer_relation.inspire_download, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, box2d(mb_metadata.the_geom) as metadata_bbox, mb_metadata.bounding_geom as polygon, layer_relation.layer_title, layer_relation.layer_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.lastchanged as md_timestamp   from (select * from layer inner join ows_relation_metadata on layer.layer_id = ows_relation_metadata.fkey_layer_id) as layer_relation inner join mb_metadata on layer_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) as layer_metadata inner join ows_relation_data on ows_relation_data.fkey_layer_id = layer_metadata.layer_id) as layer_relation_data inner join datalink on layer_relation_data.fkey_datalink_id = datalink.datalink_id) as layer_data inner join wms on layer_data.fkey_wms_id = wms.wms_id)  as layer_wms, layer_epsg where layer_wms.layer_id = layer_epsg.fkey_layer_id and layer_epsg.epsg = 'EPSG:4326'				
SQL;
			$sql[1] =  <<<SQL
select *, 'wmslayer' as origin from (select * from (select mb_metadata.metadata_id, layer_relation.layer_name,layer_relation.inspire_download, layer_relation.fkey_wms_id, layer_relation.layer_id, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, box2d(mb_metadata.the_geom) as metadata_bbox, mb_metadata.bounding_geom as polygon, layer_relation.layer_title, layer_relation.layer_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, mb_metadata.lastchanged as md_timestamp   from (select * from layer inner join ows_relation_metadata on layer.layer_id = ows_relation_metadata.fkey_layer_id) as layer_relation inner join mb_metadata on layer_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) layer_data inner join wms on layer_data.fkey_wms_id = wms.wms_id)  as layer_wms, layer_epsg where layer_wms.layer_id = layer_epsg.fkey_layer_id and layer_epsg.epsg = 'EPSG:4326';		
		
SQL;
			$sql[2] =  <<<SQL
select *, 'wfs' as origin from (select mb_metadata.metadata_id, featuretype_relation.featuretype_name, featuretype_relation.fkey_wfs_id, featuretype_relation.inspire_download, featuretype_relation.featuretype_id, mb_metadata.uuid as metadata_uuid, mb_metadata.format,mb_metadata.title as metadata_title, mb_metadata.abstract as metadata_abstract, box2d(mb_metadata.the_geom) as metadata_bbox, mb_metadata.bounding_geom as polygon, featuretype_relation.featuretype_title, featuretype_relation.featuretype_abstract, mb_metadata.ref_system as metadata_ref_system, mb_metadata.datasetid, mb_metadata.spatial_res_type, mb_metadata.spatial_res_value, mb_metadata.datasetid_codespace, featuretype_relation.featuretype_latlon_bbox as latlonbbox, featuretype_relation.featuretype_srs, mb_metadata.lastchanged as md_timestamp from (select * from wfs_featuretype inner join ows_relation_metadata on wfs_featuretype.featuretype_id = ows_relation_metadata.fkey_featuretype_id) as featuretype_relation inner join mb_metadata on featuretype_relation.fkey_metadata_id = mb_metadata.metadata_id where mb_metadata.uuid = $1) as featuretype_data inner join wfs on featuretype_data.fkey_wfs_id = wfs.wfs_id;		
SQL;
		break;
	}

	$row = array();
	$mapbenderMetadata = array();
	//initialize number of different download options
	$indexMapbenderMetadata = 0;
	switch ($generateFrom) {
		case "dataurl":
			//only one sql should be done 
			$v = array($recordId);
			$t = array('s');
			$res = db_prep_query($sql,$v,$t);
			//$e = new mb_exception("mod_inspireDownloadFeed: Fill mapbender metadata");
			fillMapbenderMetadata($res, $generateFrom);
		break;
		case "wmslayer":
			//only one sql should be done 
			$v = array($recordId, $layerId);
			$t = array('s','i');
			$res = db_prep_query($sql,$v,$t);
			fillMapbenderMetadata($res, $generateFrom);
		break;
		case "wfs":
			//only one sql should be done 
			$v = array($recordId, $wfsId);
			$t = array('s','i');
			$res = db_prep_query($sql,$v,$t);
			fillMapbenderMetadata($res, $generateFrom);
		break;
		case "metadata":
			//only one sql should be done 
			//$e = new mb_exception("sql metadata: ".$sql);
			//$e = new mb_exception($recordId);
			$v = array($recordId);
			$t = array('s');
			$res = db_prep_query($sql,$v,$t);
			//$e = new mb_exception("num rows: ".db_numrows($res));
			fillMapbenderMetadata($res, $generateFrom);
		break;
		case "remotelist":
		    //only one sql should be done
		    //$e = new mb_exception("sql metadata: ".$sql);
		    //$e = new mb_exception($recordId);
		    $v = array($recordId);
		    $t = array('s');
		    $res = db_prep_query($sql,$v,$t);
		    //$e = new mb_exception("num rows: ".db_numrows($res));
		    fillMapbenderMetadata($res, $generateFrom);
		    break;
		case "all"://TODO: Maybe a union is a better way, but the sql must be harmonized before
			for ($i = 0; $i < 3; $i++) {
				$v = array($recordId);
				$t = array('s');
				$sqlQuery = $sql[$i];
				$res = db_prep_query($sqlQuery,$v,$t);
				fillMapbenderMetadata($res);
			}
		break;

	}
	$e = new mb_exception("mapbenderMetadata id: " . $mapbenderMetadata[0]->id);
	
	$countRessource = count($mapbenderMetadata); //count of coupled featuretypes, layers or dataurls or both!
	//echo "<error>".count($mapbenderMetadata)."</error>";
	//die();
	//$e = new mb_exception("mod_inspireDownloadFeed: uuid ".$recordId);
	//$e = new mb_exception("mod_inspireDownloadFeed: datalink_id ".$mapbenderMetadata[0]->datalink_id);
	//$e = new mb_exception("mod_inspireDownloadFeed: sql ".$sql);

	if ($generateFrom != "wfs") {
		$countRessource = 1;
	}

	//for the first entry - top feed level use the first index - right so or do it on another way?
	$m = 0;
	
	if ($generateFromDataurl) {
		//check if layer_id datalink_id and metadata_id are given and not empty!
		if (!isset($mapbenderMetadata[$m]->datalink_id) || $mapbenderMetadata[$m]->datalink_id == '') {
			return "<error>No dataurl element is given for the requested wms layer</error>";
		}
	}
	//TODO - if the wms is a raster based wms and the output format may be geotiff - the feed entries can be generated automatically. We need following information
	// 1. a raster based wms is given or not - checkbox at metadata editor!
	// 2. geotiff maybe one of the allowed formats! - layer format
	// 3. the maximum of pixel which can be served by the wms - maybe 2000x2000px - metadata editor!
	// 4. The scale hints maybe used to control if a special get map request will produce a picture or not
	//TODO: In this case we have to generate a feed for every single getmap request. This feed have to be called dynamically too! It will be cool to have the bboxes as get parameter or give a index of the bbox with which the bbox can be calculated again!

	if (!isset($mapbenderMetadata[$m]->metadata_id) || $mapbenderMetadata[$m]->metadata_id == '') {
		return "<error>The metadataset with id ".$mapbenderMetadata[$m]->metadata_id." has no coupled ".$generateFrom." ressource ".$m."</error>";
	}

	$crs = $mapbenderMetadata[$m]->metadata_ref_system;
	if (!isset($mapbenderMetadata[$m]->metadata_ref_system) || $mapbenderMetadata[$m]->metadata_ref_system == '') {
		return "<error>For the metadataset with id ".$mapbenderMetadata[$m]->metadata_id." is no reference system defined!</error>";
	}
	
	//Handle CRS - maybe in 'urn:ogc:def:crs:EPSG:6.9:4326' or 'EPSG:4326' or 'epsg:4326' or 'urn:ogc:def:crs:EPSG::4326' format!
	//*********************** New use crs class for checking if the order has to be changed in the filter!!!
	$crsObject = new Crs($crs);
	if ($crsObject->alterAxisOrder("wms"."_".$mapbenderMetadata[$m]->wms_version) == true) {
		$alterAxisOrder = true;
	} else {
		$alterAxisOrder = false;
	}
	//**************************************************************************************
	$epsgId = $crsObject->identifierCode;
	//**************************************************************************************
	switch ($generateFrom) {
		case "wmslayer":
			$serviceType = "wms";
			$serviceId = $mapbenderMetadata[$m]->fkey_wms_id;
			$ownerId = $mapbenderMetadata[$m]->wms_owner;
			break;
		case "wfs":
			$serviceType = "wfs";
			$serviceId = $mapbenderMetadata[$m]->wfs_id;
			$ownerId = $mapbenderMetadata[$m]->wfs_owner;
			break;
		case "dataurl":
			$serviceType = "wms";
			$serviceId = $mapbenderMetadata[$m]->fkey_wms_id;
			$ownerId = $mapbenderMetadata[$m]->wms_owner;
			break;
		case "metadata":
			$serviceType = "metadata";
			$serviceId = $mapbenderMetadata[$m]->metadata_id;
			$ownerId = $mapbenderMetadata[$m]->md_owner;
			break;
		case "remotelist":
		    $serviceType = "metadata";
		    $serviceId = $mapbenderMetadata[$m]->metadata_id;
		    $ownerId = $mapbenderMetadata[$m]->md_owner;
		    break;
		default:
			break;
	}
	$departmentMetadata = $admin->getOrgaInfoFromRegistry($serviceType, $serviceId, $ownerId);
	$userMetadata['mb_user_email'] = $departmentMetadata['mb_user_email'];
	$userMetadata['timestamp'] = $departmentMetadata['mb_user_timestamp'];
	//**************************************************************************************
	switch ($generateFrom) {
		case "wmslayer":
			$v = array($mapbenderMetadata[$m]->wms_owner);
			$hasPermission=$admin->getLayerPermission($mapbenderMetadata[$m]->fkey_wms_id,$mapbenderMetadata[$m]->layer_name,PUBLIC_USER);
		break;
		case "dataurl":
			$v = array($mapbenderMetadata[$m]->wms_owner);
			//$hasPermission=$admin->getLayerPermission($mapbenderMetadata[$m]->fkey_wms_id,$mapbenderMetadata[$m]->layer_name,PUBLIC_USER);
			$hasPermission = true;
		break;
		case "wfs":
			$v = array($mapbenderMetadata[$m]->wfs_owner);
			$hasPermission = true;
		break;
		case "metadata":
			$v = array($mapbenderMetadata[$m]->md_owner);
			$hasPermission = true;
		break;
		case "remotelist":
		    $v = array($mapbenderMetadata[$m]->md_owner);
		    $hasPermission = true;
		    break;
	}
	//check if resource is freely available to anonymous user - which are all users who search thru metadata catalogues:
	/*if ($generateFrom != "wfs") {
		$hasPermission=$admin->getLayerPermission($mapbenderMetadata[$m]->fkey_wms_id,$mapbenderMetadata[$m]->layer_name,PUBLIC_USER);
	} else {
		$hasPermission = true;
	}*/

}

//Some needfull functions to pull metadata out of the database.
//List of data which is needed to build the feed:
//header part ******
// - feed title: Generated from dataset name - either mb_metadata.title or layer.layer_title
// - feed subtitle: Generated from dataset name - either mb_metadata.title or layer.layer_title, organisation name - mapbender group information (metadata contact) - cause it is generated from data of a registrated wms
// - link to ISO19139 service metadata - this will be created dynamically by given layer_id as this script itself
// - link to opensearch description for this download service - as before the layer_id will be used as a parameter
// - id - link to the script itself
// - rights - the access constraints of the view service are used - they should also give information about the access constraints for the usage of the data
// - updated : last date the feed was updated - use current timestamp of the wms as the feed will be generated from dataurl entry of the layer object - wms.wms_timestamp
//datalink.random_id - this is newly created when layers are updated ! - new
//author - use information from mapbender group - metadata point of contact - registrating organization
//entry part ******
// - entry title: Generated from dataset name - either mb_metadata.title or layer.layer_title - in combination e.g. with "Feed for ..."
// - link to the dataset feed - invoked by layer id as this is done before
// - summary -  Generated by some infomation: mb_metadata.format, mb_metadata.ref_sytem, ...., datalink.datalink_format
// - updated - timestamp of wms as done before
// - 

function generateFeed($feedDoc, $recordId, $generateFrom) {
	global $admin, $type, $imageResolution, $maxImageSize, $maxFeatureCount, $mapbenderMetadata, $indexMapbenderMetadata, $layerId, $wfsId, $featuretypeId, $mapbenderPath, $mapbenderServerUrl, $epsgId, $alterAxisOrder, $departmentMetadata, $userMetadata, $hasPermission, $m, $crs, $crsUpper,$countRessource, $numberOfTiles, $furtherLink;
	$e = new mb_exception("generateFeed: gernerateFrom: " . $generateFrom); 
	
	//caching feeds in apc cache
	//check age of information to allow caching of atom feeds
	/*$e = new mb_exception("mod_inspireDownloadFeed.php: wms_timestamp: ".date("Y-m-d H:i:s",$mapbenderMetadata[$m]->wms_timestamp));
	$e = new mb_exception("mod_inspireDownloadFeed.php: wfs_timestamp: ".date("Y-m-d H:i:s",$mapbenderMetadata[$m]->wfs_timestamp));
	$e = new mb_exception("mod_inspireDownloadFeed.php: md_timestamp: ".date("Y-m-d H:i:s",strtotime($mapbenderMetadata[$m]->md_timestamp)));
	$e = new mb_exception("mod_inspireDownloadFeed.php: group timestamp: ".date("Y-m-d H:i:s",strtotime($departmentMetadata['timestamp'])));
	$e = new mb_exception("mod_inspireDownloadFeed.php: user timestamp: ".date("Y-m-d H:i:s",strtotime($userMetadata['timestamp'])));*/
	if ($type == 'DATASET') {
		if (isset($mapbenderMetadata[$m]->metadata_id)) {
			//log download for resource
			$metadataMonitor = new Metadata_load_count();
			$metadataMonitor->increment($mapbenderMetadata[$m]->metadata_id);
		}
	}
	$timestamps = array(
		date("Y-m-d H:i:s",$mapbenderMetadata[$m]->wms_timestamp),
		date("Y-m-d H:i:s",$mapbenderMetadata[$m]->wfs_timestamp),
		date("Y-m-d H:i:s",strtotime($mapbenderMetadata[$m]->md_timestamp)),
		date("Y-m-d H:i:s",strtotime($departmentMetadata['timestamp'])),
		date("Y-m-d H:i:s",strtotime($userMetadata['timestamp']))
	);
	$maxDate = max($timestamps);
	//$e = new mb_exception("mod_inspireDownloadFeed.php: maxDate: ".$maxDate);
	//instantiate cache if available
	//extract link, format, ... from further_links_json (loaded as datasetlinks)
	if ($generateFrom == "remotelist") {
	    //try to parse all relevant information from json_object
	    /* Example
	     {
	     "dcat:Distribution": [
	     {
	     "dcat:accessUrl": "https://example.com",
	     "dcterms:title": "Link zum Webshop",
	     "dcterms:description": "Beschreibung der Distribution",
	     "dcterms:format": "ZIPFILE",
	     "dcat:mediaType": "application/zip"
	     },
	     {
	     "dcat:accessService": {
	     "dct:hasPart": "https://lintopartofatomfeed.html"
	     },
	     "dcterms:title": "Link zum Webshop",
	     "dcterms:description": "Beschreibung der Distribution",
	     "dcterms:format": "ZIPFILE",
	     "dcat:mediaType": "application/zip",
	     "gdirp:epsgCode": "25832"
	     }
	     ]
	     }
	     */
	    if (json_decode($mapbenderMetadata[$m]->datalinks)) {
	        $distributions = json_decode($mapbenderMetadata[$m]->datalinks);
	        $linkListFound = false;
	        foreach ($distributions->{'dcat:Distribution'} as $dcatDistribution) {
	            if ($dcatDistribution->{'dcat:accessService'}->{'dct:hasPart'}) {
	                $mandatoryFieldsAvailable = true;
	                $mandatoryFields = array('dcterms:format', 'gdirp:epsgCode', 'dcterms:title', 'dcterms:description');
	                foreach ($mandatoryFields as $serviceAttribute) {
	                    //$e = new mb_exception("php/mod_inspireDownloadFeed.php: check: " . $serviceAttribute . " - value found: " . $dcatDistribution->{$serviceAttribute});
	                    if (!$dcatDistribution->{$serviceAttribute}) {
	                        $mandatoryFieldsAvailable = false;
	                        header("Content-Type: text/html");
	                        echo "ATOM-Feed based on remote link list could not be generated, cause mandatory field " . $serviceAttribute . " is missing!";
	                        die();
	                        break;
	                    }
	                }
	                if ($mandatoryFieldsAvailable == false) {
	                    header("Content-Type: text/html");
	                    echo "ATOM-Feed based on remote link list could not be generated, cause some mandatory field (" . implode(",", $mandatoryFields) . ") are missing!";
	                    die();
	                }
	                $linkListFound = true;
	                $atomFeedLinkList = $dcatDistribution->{'dcat:accessService'}->{'dct:hasPart'};
	                $atomFeedTitle = $dcatDistribution->{'dcterms:title'};
	                $atomFeedDescription = $dcatDistribution->{'dcterms:Description'};
	                $atomFeedFormat = $dcatDistribution->{'dcterms:format'};
	                $atomFeedCrs = "EPSG:" . $dcatDistribution->{'gdirp:epsgCode'};
	                break;
	            }
	        }
	        if ($linkListFound) {
	            $furtherLink = urldecode($linkList);
	            
	        } else {
	            header("Content-Type: text/html");
	            echo "ATOM-Feed based on remote link list could not be generated, cause there is no available information about the remote list!";
	            die();
	        }
	    } else {
	        echo "ATOM-Feed based on remote link list could not be generated, cause there is no available information about the remote list!";
	        header("Content-Type: text/html");
	        die();
	    }
	}
	$cache = new Cache();
	//define key name cache
	$atomFeedKey = 'mapbender:atomFeed_'.$type."_".$recordId."_".$generateFrom.'_';
	switch ($generateFrom) {
		case "wmslayer":
			$atomFeedKey .= $layerId;
		break;
		case "dataurl":
			$atomFeedKey .= $layerId;
		break;
		case "wfs":
			$atomFeedKey .= $wfsId;
			switch ($mapbenderMetadata[$m]->wfs_version) {
				case "2.0.2":
					$typeParameterName = "typeNames";
					break;
				case "2.0.0":
					$typeParameterName = "typeNames";
					break;
				default:
					$typeParameterName = "typeName";
					break;
			}
		break;
	}
	$cache->isActive = false; //TODO delete productive
	//$e = new mb_exception("mod_inspireDownloadFeed.php: cachedVariableTimestamp: ".date("Y-m-d H:i:s",$cache->cachedVariableCreationTime($atomFeedKey)));
	if ($cache->isActive && $cache->cachedVariableExists($atomFeedKey) && (date("Y-m-d H:i:s",$cache->cachedVariableCreationTime($atomFeedKey)) > $maxDate)) {
		#$e = new mb_exception("class_map.php: read ".$atomFeedKey." from ".$cache->cacheType." cache!");
		return $cache->cachedVariableFetch($atomFeedKey);
	} else {
    //*****************************************************************
	//compare highestDate with timestamp of cache

	//part which generates the feed 
	$feed =  $feedDoc->createElementNS('http://www.w3.org/2005/Atom', 'feed');
	$feed = $feedDoc->appendChild($feed);
	//$feed->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$feed->setAttribute("xmlns:georss", "http://www.georss.org/georss");
	if ($type == 'SERVICE') {
		$feed->setAttribute("xmlns:inspire_dls", "http://inspire.ec.europa.eu/schemas/inspire_dls/1.0");
	}
	$feed->setAttribute("xmlns:lang", "de");
	//echo "<test>".$mapbenderMetadata['fkey_wms_id'].":".$mapbenderMetadata['wms_owner']."</test>";

	//pull the namespace from database
	$metadataArray['datasetid_codespace'] = $mapbenderMetadata[$m]->datasetid_codespace;
	$uniqueResourceIdentifierCodespace = $admin->getIdentifierCodespaceFromRegistry($departmentMetadata, $metadataArray);

	//overwrite datasetid_codespace
	$mapbenderMetadata[$m]->datasetid_codespace = $uniqueResourceIdentifierCodespace;

	//qualifying id of the referenced ressource: Either dataset id or the id of the metadata record 
	/*if (isset($mapbenderMetadata[$m]->datasetid) && $mapbenderMetadata[$m]->datasetid != '' ) {
		$ressourceId = $mapbenderMetadata[$m]->datasetid_codespace."#".$mapbenderMetadata[$m]->datasetid;
	}*/

	//check if the metadata set has a title and abstract if not given (in case of dataurl, use layer information!)
	//first use metadata title then layer title
	if (isset($mapbenderMetadata[$m]->metadata_title) && $mapbenderMetadata[$m]->metadata_title != '' ) {
		$ressourceTitle = $mapbenderMetadata[$m]->metadata_title;
	} else {
		//TODO check wfs title....
		
		if (isset($mapbenderMetadata[$m]->layer_title) && $mapbenderMetadata[$m]->layer_title != '' ) {
		    $ressourceTitle = $mapbenderMetadata[$m]->layer_title;
		} else {
			$ressourceTitle = "Title of dataset cannot be found!";
		}
	}
		

	//first use metadata abstract then layer abstract
	if (isset($mapbenderMetadata[$m]->metadata_abstract) && $mapbenderMetadata[$m]->metadata_abstract != '' ) {
		$ressourceAbstract = $mapbenderMetadata[$m]->metadata_abstract;
	} else {
		if (isset($mapbenderMetadata[$m]->layer_abstract) && $mapbenderMetadata[$m]->layer_abstract != '' ) {
			$ressourceAbstract = $mapbenderMetadata[$m]->layer_abstract;
		} else {
			$ressourceAbstract = "Abstract of dataset cannot be found!";
		}
	}
	//Begin generation of feed content *********************************************************************************************
	//feed title - 5.1.1 / 5.2
	//<title>XYZ Example INSPIRE Download Service</title>
	//<title>XYZ Example INSPIRE Dataset ABC Download</title>
	$feedTitle = $feedDoc->createElement("title");
	//$feedTitle->setAttribute("xml:lang", "de");
	if ($type == 'SERVICE') {
		$feedTitleText = $feedDoc->createTextNode("INSPIRE Download Service Feed für  ".$ressourceTitle);
	} else { //DATASET
		$feedTitleText = $feedDoc->createTextNode("INSPIRE Datensatz Feed für ".$ressourceTitle);
	}
	$feedTitle->appendChild($feedTitleText);
	$feed->appendChild($feedTitle);
	//feed subtitle - 5.1.2 / 5.2
	//<subtitle xml:lang="en">INSPIRE Download Service of organisation XYZ providing a data set for the Hydrography theme</subtitle>
	//<subtitle>INSPIRE Download Service, of organisation XYZ providing dataset ABC for the Hydrography theme</subtitle>
	$feedSubTitle = $feedDoc->createElement("subtitle");
	//$feedSubTitle->setAttribute("xml:lang", "de");
	$e = new mb_exception("feed: orga: " . $departmentMetadata['mb_group_title']);
	if (isset($departmentMetadata['mb_group_title']) && $departmentMetadata['mb_group_title'] != "") {
	    $organizationTitle = $departmentMetadata['mb_group_title'];
	} else {
	    $organizationTitle = "UNKNOWN ORGANIZATION";
	}
	if ($type == 'SERVICE') {
	    $feedSubTitleText = $feedDoc->createTextNode("INSPIRE Download Service von ".$organizationTitle."");
	} else { //DATASET
	    $feedSubTitleText = $feedDoc->createTextNode("INSPIRE Download Service von: ".$organizationTitle." zur Bereitstellung des Datensatzes: ".$ressourceTitle);
	}
	//TODO maybe add topic
	$feedSubTitle->appendChild($feedSubTitleText);
	$feed->appendChild($feedSubTitle);

	//links
	//metadata

	//service metadata - 5.1.3 / dataset metadata 5.2.1
	//<link href="http://xyz.org/metadata" rel="describedby" type="application/vnd.iso.19139+xml"/>
	$feedLink = $feedDoc->createElement("link");
	if ($type == 'SERVICE') {
		if ($generateFrom == 'wfs') {
			$feedLink->setAttribute("href", $mapbenderPath."php/mod_inspireAtomFeedISOMetadata.php?id=".$recordId."&outputFormat=iso19139&generateFrom=".$generateFrom."&wfsid=".$wfsId);
		} else {
			$feedLink->setAttribute("href", $mapbenderPath."php/mod_inspireAtomFeedISOMetadata.php?id=".$recordId."&outputFormat=iso19139&generateFrom=".$generateFrom."&layerid=".$layerId);
		}
		$feedLink->setAttribute("rel", "describedby");
		$feedLink->setAttribute("type", "application/xml");
		$feed->appendChild($feedLink);
	} else { //DATASET
		switch ($generateFrom) {
			//TODO: set inspire data model reference if conformity is declared and theme is given!
			case "wfs":
				if ($admin->getWFSOWSstring($mapbenderMetadata[$m]->wfs_id) == false) {
					$feedLink->setAttribute("href", $mapbenderMetadata[$m]->wfs_describefeaturetype."SERVICE=WFS&VERSION=".$mapbenderMetadata[$m]->wfs_version."&REQUEST=DescribeFeatureType&".$typeParameterName."=".$mapbenderMetadata[$m]->featuretype_name);
				} else {
					$feedLink->setAttribute("href", $mapbenderServerUrl."/registry/wfs/".$mapbenderMetadata[$m]->wfs_id."?INSPIRE=1&"."SERVICE=WFS&VERSION=".$mapbenderMetadata[$m]->wfs_version."&REQUEST=DescribeFeatureType&".$typeParameterName."=".$mapbenderMetadata[$m]->featuretype_name);
				}
				$feedLink->setAttribute("rel", "describedby");
				$feedLink->setAttribute("type", "application/xml");
				$feed->appendChild($feedLink);
				break;
			case "wms":
				$feedLink->setAttribute("href", "https://en.wikipedia.org/wiki/GeoTIFF");
				$feedLink->setAttribute("rel", "describedby");
				$feedLink->setAttribute("type", "text/html");
				$feed->appendChild($feedLink);
				break;
			case "dataurl":
				$feedLink->setAttribute("href", "https://www.google.com/search?q=".$mapbenderMetadata[$m]->format."+spatial");
				$feedLink->setAttribute("rel", "describedby");
				$feedLink->setAttribute("type", "text/html");
				$feed->appendChild($feedLink);
				break;
			case "metadata":
				$feedLink->setAttribute("href", "https://www.google.com/search?q=".$mapbenderMetadata[$m]->format."+spatial");
				$feedLink->setAttribute("rel", "describedby");
				$feedLink->setAttribute("type", "text/html");
				$feed->appendChild($feedLink);
				break;
			case "remotelist":
			    $feedLink->setAttribute("href", "https://www.google.com/search?q=".$mapbenderMetadata[$m]->format."+spatial");
			    $feedLink->setAttribute("rel", "describedby");
			    $feedLink->setAttribute("type", "text/html");
			    $feed->appendChild($feedLink);
			    break;
		}
	}
	//self reference - 5.1.4 / 5.2
	if ($type == 'SERVICE') {
		$selfReference = $mapbenderServerUrl.$_SERVER['SCRIPT_NAME']."?id=".$recordId."&type=SERVICE&generateFrom=".$generateFrom;
		switch($generateFrom) {
			case "wmslayer":
				$selfReference .= "&layerid=".$mapbenderMetadata[$m]->layer_id;
			break;
			case "wfs":
				$selfReference .= "&wfsid=".$mapbenderMetadata[$m]->wfs_id;
			break;
		}
	} else { //DATASET
		$selfReference = $mapbenderServerUrl.$_SERVER['SCRIPT_NAME']."?id=".$recordId."&type=DATASET&generateFrom=".$generateFrom;
		switch($generateFrom) {
			case "wmslayer":
				$selfReference .= "&layerid=".$mapbenderMetadata[$m]->layer_id;
			break;
			case "wfs":
				$selfReference .= "&wfsid=".$mapbenderMetadata[$m]->wfs_id;
				if ($featuretypeId !== false) {
//$e = new mb_exception("ft_id: ".$featuretypeId);
					$selfReference .= "&featuretypeid=".$featuretypeId;
				} else {
					$selfReference .= "&featuretypeid=".$mapbenderMetadata[$m]->featuretype_id;
				}
			break;
		}
	}

	//<link href="http://xyz.org/data" rel="self" type="application/atom+xml" hreflang="en" title="This document"/>
	$feedLink = $feedDoc->createElement("link");
	$feedLink->setAttribute("href", $selfReference);
	$feedLink->setAttribute("rel", "self");
	$feedLink->setAttribute("type", "application/atom+xml");
	$feedLink->setAttribute("hreflang", "de");
	$feedLink->setAttribute("title", "Selbstreferenz");
	$feed->appendChild($feedLink);
	
	//opensearch descriptionlink 5.1.5
	if ($type == 'SERVICE') {
		$feedLink = $feedDoc->createElement("link");
		$feedLink->setAttribute("href", $mapbenderServerUrl.$_SERVER['SCRIPT_NAME']."?id=".$recordId."&type=".$type."&generateFrom=".$generateFrom."&wfsid=".$wfsId."&layerid=".$layerId."&getopensearch=true");
		$feedLink->setAttribute("rel", "search");
		$feedLink->setAttribute("type", "application/opensearchdescription+xml");
		//$feedLink->setAttribute("hreflang", "de");
		$feedLink->setAttribute("title", "Open Search Beschreibung des INSPIRE Download Dienstes für den Datensatz ".$ressourceTitle);
		$feed->appendChild($feedLink);
	} else { //5.2.2

	//description of datatypes - if given??? What todo when there is no description available - some html page have to be referenced?
		/*$feedLink = $feedDoc->createElement("link");
		$feedLink->setAttribute("href", $mapbenderPath."php/mod_showMetadata.php?languageCode=de&resource=layer&layout=tabs&id=".$recordId); //TODO show metadata in form of html content - switch for each type
		$feedLink->setAttribute("rel", "describedby");
		$feedLink->setAttribute("type", "text/html");
		$feedLink->setAttribute("hreflang", "de");
		$feedLink->setAttribute("title", "Nähere Beschreibung des Datensatzes");
		$feed->appendChild($feedLink);*/
	}
	//5.1.6 - 5.1.7
	//other -- not needed cause only one language is defined
	//<link href="http://xyz.org/data/de" rel="alternate" type="application/atom+xml" hreflang="de" title="The download service information in German"/>
	//<link href="http://xyz.org/data/index.html" rel="alternate" type="text/html" hreflang="en" title="An HTML version of this document"/>
	//<link href="http://xyz.org/data/index.de.html" rel="alternate" type="text/html" hreflang="de"	title="An HTML version of this document in German"/>

	//optional link to wfs capabilities document / see TG 3.1 from 09.08.2013
	if ($type == 'SERVICE' && $generateFrom == "wfs") {
	/*<link rel="related" href="http://xyz.org/wfs?request=GetCapabilities&amp;service=WFS&amp;version=2.0.0" type="application/xml" title="Service implementing Direct Access operations"/>*/
		$feedLink = $feedDoc->createElement("link");
		if ($admin->getWFSOWSstring($mapbenderMetadata[$m]->wfs_id) == false) {
			//$wfsGetCapabilitiesUrl = $mapbenderMetadata[$m]->wfs_getcapabilities;
			if (count($mapbenderMetadata) == 1) {
//$e = new mb_exception(count($mapbenderMetadata));
				$wfsGetCapabilitiesUrl = $mapbenderPath."php/wfs.php?INSPIRE=1&FEATURETYPE_ID=".$mapbenderMetadata[$m]->featuretype_id;
			} else {
				//TODO - set url to wfs proxy - wfs.php has to be adopted to allow more than one featuretype as parameter!
				$wfsGetCapabilitiesUrl = $mapbenderMetadata[$m]->wfs_getcapabilities;
			}
		} else {
			$wfsGetCapabilitiesUrl = $mapbenderServerUrl."/registry/wfs/".$mapbenderMetadata[$m]->wfs_id."?INSPIRE=1";
		}
		$feedLink->setAttribute("href", $wfsGetCapabilitiesUrl."&request=GetCapabilities&VERSION=".$mapbenderMetadata[$m]->wfs_version."&SERVICE=WFS");
		$feedLink->setAttribute("rel", "related");
		$feedLink->setAttribute("type", "application/xml");
		//$feedLink->setAttribute("hreflang", "en");
		$feedLink->setAttribute("title", "Service implementing Direct Access operations");
		$feed->appendChild($feedLink);
	}
	//<!-- identifier -->
	//<id>http://xyz.org/data</id> - also self reference - see 5.1.8 on page 39 of INSPIRE GD for Download Services V 3.0
	// and 5.2.1
	if ($type == 'SERVICE') {
		$feedId = $feedDoc->createElement("id");
		$feedIdText = $feedDoc->createTextNode($selfReference);
		$feedId->appendChild($feedIdText);
		$feed->appendChild($feedId);
	} else { //use inspire resource identifier?
		$feedId = $feedDoc->createElement("id");
		$feedIdText = $feedDoc->createTextNode($selfReference);
		$feedId->appendChild($feedIdText);
		$feed->appendChild($feedId);
	}
	//<!-- rights, access restrictions -->
	//<rights>Copyright (c) 2011, XYZ; all rights reserved</rights> -- see 5.1.9 on page 39 of INSPIRE GD for Download Services V 3.0 - only accessconstraints should be used
	$feedRights = $feedDoc->createElement("rights");
	$feedRightsText = $feedDoc->createTextNode($mapbenderMetadata[$m]->accessconstraints);
	$feedRights->appendChild($feedRightsText);
	$feed->appendChild($feedRights);

	//<!-- date/time of last update of feed--> -- see 5.1.10 on page 40 of INSPIRE GD for Download Services V 3.0 - maybe date of metadata should be used - first we use current date!
	//<updated>2011-09-24T13:45:03Z</updated>
	$feedUpdated = $feedDoc->createElement("updated");
	$feedUpdatedText = $feedDoc->createTextNode(date(DATE_ATOM,time()));
	$feedUpdated->appendChild($feedUpdatedText);
	$feed->appendChild($feedUpdated);

	//<!-- author info --> 5.1.11 
	//<author>
	//	<name>John Doe</name>
	//	<email>doe@xyz.org</email>
	//</author>
	$feedAuthor = $feedDoc->createElement("author");
	$feedAuthorName = $feedDoc->createElement("name");
	$feedAuthorEmail = $feedDoc->createElement("email");
	//check for department, 1. group, 2. from metadata, 3. dummy
	if ($departmentMetadata["mb_group_title"] == "" || empty($departmentMetadata["mb_group_title"])) {
		if ($mapbenderMetadata[$m]->ressource_responsible_party == "" || empty($mapbenderMetadata[$m]->ressource_responsible_party)) {
			$feedAuthorName->appendChild($feedDoc->createTextNode("No responsible department found!"));
		} else {
			$feedAuthorName->appendChild($feedDoc->createTextNode($mapbenderMetadata[$m]->ressource_responsible_party));
		}
	} else {
		$feedAuthorName->appendChild($feedDoc->createTextNode($departmentMetadata["mb_group_title"]));
	}
	if ($departmentMetadata["mb_group_email"] == "" || empty($departmentMetadata["mb_group_email"])) {
		if ($mapbenderMetadata[$m]->ressource_contact_email == "" || empty($mapbenderMetadata[$m]->ressource_contact_email)) {
			$feedAuthorEmail->appendChild($feedDoc->createTextNode("No email for responsible department found!"));
		} else {
			$feedAuthorEmail->appendChild($feedDoc->createTextNode($mapbenderMetadata[$m]->ressource_contact_email));
		}
	} else {
		$feedAuthorEmail->appendChild($feedDoc->createTextNode($departmentMetadata["mb_group_email"]));
	}
	//$feedAuthorEmail->appendChild($feedDoc->createTextNode($departmentMetadata["mb_group_email"]));
	$feedAuthor->appendChild($feedAuthorName);
	$feedAuthor->appendChild($feedAuthorEmail);
	$feed->appendChild($feedAuthor);
	//check for given polygonal extent:
	if (is_null($mapbenderMetadata[$m]->metadata_polygon)) {
		$e = new mb_notice("php/mod_inspireDownloadFeed.php polygonalFilter is not set");
		$polygonalFilter = false;
	} else {
		$e = new mb_notice("php/mod_inspireDownloadFeed.php polygonalFilter was found and will be applied!");
		$polygonalFilter = true;
	}
	$e = new mb_exception("php/mod_inspireDownloadFeed.php metadata_uuid: ".$mapbenderMetadata[$m]->metadata_uuid);
	//<!-- pre-defined dataset - a entry for each pre-defined dataset - in the case of dataURL only one entry is used! -->
	//if dataurl not given and a raster wms is defined - calculate the number of entries
	if ($type == 'DATASET' && $generateFrom == "wmslayer") {
		$numberOfEntries = 1; //only one entry with many links!!
		//calculate number of entries and the bboxes
		if ($mapbenderMetadata[$m]->spatial_res_type != 'groundDistance' & $mapbenderMetadata[$m]->spatial_res_type != 'scaleDenominator') {
			echo "<error>WMS footprints cannot be calculated, cause kind of resolution is not given.</error>";
		} else {
			if (!is_int((integer)$mapbenderMetadata[$m]->spatial_res_value)) {
				echo "<error>WMS footprints cannot be calculated, cause resolution is no integer.</error>";
			} else {
				$maxImageSize = (integer)$mapbenderMetadata[$m]->wms_max_imagesize;
				//calculate the bboxes
				//transform layer_bbox to mb_metadata epsg
				/*$georssPolygon = $mapbenderMetadata["minx"]." ".$mapbenderMetadata["miny"]." ".$mapbenderMetadata["maxx"]." ".$mapbenderMetadata["miny"]." ";
				$georssPolygon .= $mapbenderMetadata["maxx"]." ".$mapbenderMetadata["maxy"]." ".$mapbenderMetadata["minx"]." ".$mapbenderMetadata["maxy"]." ";
				$georssPolygon .= $mapbenderMetadata["minx"]." ".$mapbenderMetadata["miny"];
				echo $georssPolygon;*/
				$crsObject = new Crs($crs);
				if ($crsObject->alterAxisOrder("wms"."_".$mapbenderMetadata[$m]->wms_version) == true) {
				    $alterAxisOrder = true;
				} else {
				    $alterAxisOrder = false;
				}
				//**************************************************************************************
				$e = new mb_notice("Epsg id of layer ".$mapbenderMetadata[$m]->layer_id." : ".$epsgId);
					
				//TODO: check if epsg, and bbox are filled correctly!
				$sqlExtent = "SELECT X(transform(GeometryFromText('POINT(".$mapbenderMetadata[$m]->minx." ".$mapbenderMetadata[$m]->miny.")',4326),".$epsgId.")) as minx, Y(transform(GeometryFromText('POINT(".$mapbenderMetadata[$m]->minx." ".$mapbenderMetadata[$m]->miny.")',4326),".$epsgId.")) as miny, X(transform(GeometryFromText('POINT(".$mapbenderMetadata[$m]->maxx." ".$mapbenderMetadata[$m]->maxy.")',4326),".$epsgId.")) as maxx, Y(transform(GeometryFromText('POINT(".$mapbenderMetadata[$m]->maxx." ".$mapbenderMetadata[$m]->maxy.")',4326),".$epsgId.")) as maxy";
				$resExtent =  db_query($sqlExtent);
				$minx = floatval(db_result($resExtent,0,"minx"));
				$miny = floatval(db_result($resExtent,0,"miny"));
				$maxx = floatval(db_result($resExtent,0,"maxx"));
				$maxy = floatval(db_result($resExtent,0,"maxy"));
	
				//$e = new mb_exception("minx " . $minx . " miny " . $miny . " maxx " . $maxx . " maxy " . $maxy . " epsg " . $epsgId);
				
				$diffX = $maxx - $minx; //in m
				$diffY = $maxy - $miny;	//in m
				//$e = new mb_exception($diffX);
				//echo $diffX .":". $diffY;
				//calculate target number of pixels for x and y
				switch ($mapbenderMetadata[$m]->spatial_res_type) {
					case "scaleDenominator":
						//transform to pixel
						$diffXPx = $diffX / (float)$mapbenderMetadata[$m]->spatial_res_value / (float)0.0254 * floatval($imageResolution);
						$diffYPx = $diffY / (float)$mapbenderMetadata[$m]->spatial_res_value / (float)0.0254 * floatval($imageResolution);
					break;
					case "groundDistance":
						//transform to pixel
						$diffXPx = $diffX / floatval($mapbenderMetadata[$m]->spatial_res_value);
						$diffYPx = $diffY / floatval($mapbenderMetadata[$m]->spatial_res_value);
						
					break;
				}
				$e = new mb_notice($diffXPx.":".$diffYPx);
				$nRows = ceil($diffYPx / floatval($maxImageSize));
				$nCols = ceil($diffXPx / floatval($maxImageSize));
				//$e = new mb_exception($nRows.":".$nCols . ":" . intval($nRows)*intval($nColumns));
				$bboxWms = array();
				$bboxWmsWGS84 = array();
				/*echo $diffXPx.":".$diffYPx.",";
				echo $nRows.":".$nCols.",";
				echo $minx.":".$miny.",";
				echo $maxx.":".$maxy.",";*/
				$incX = $diffX / ($diffXPx / floatval($maxImageSize));
				$incY = $diffY / ($diffYPx / floatval($maxImageSize));
				$multiPolygonText = "'MULTIPOLYGON(";
				for ($j = 0; $j < $nRows; $j++) {
					for ($k = 0; $k < $nCols; $k++) {
					    //TODO build multipolygon 
					    //SELECT ST_GeomFromText('MULTIPOLYGON(...)',4326)
						//echo "j: ".$k.",k: ".$j;
						$minxWms = $minx + $k * $incX;
						//echo "minxWms: ". $minxWms .",";
						$minyWms = $miny + $j * $incY;
						//echo "minyWms: ". $minyWms .",";
						$maxxWms = $minx + ($k+1) * $incX;
						//echo "maxxWms: ". $maxxWms .",";
						$maxyWms = $miny + ($j+1) * $incY;
						//echo "maxyWms: ". $maxyWms .",";
						$bboxWms[] = $minxWms.",".$minyWms.",".$maxxWms.",".$maxyWms;
						//NEW 2022-07-11 - use a single sql to calculate the intersections
						$multiPolygonText .= "((" . $minxWms . " " . $minyWms . "," . $maxxWms . " " . $minyWms . "," . $maxxWms . " " . $maxyWms . ",";
						$multiPolygonText .= $minxWms . " " . $maxyWms . "," . $minxWms . " " . $minyWms . ")),";
					}
				}
				$multiPolygonText = rtrim($multiPolygonText, ",");
				$multiPolygonText .= ")'";
				$geomGeneratorSql = "ST_GeomFromText(" . $multiPolygonText . "," . intval($epsgId) . ")";
				//$e = new mb_exception($geomGeneratorSql);
				//$admin->putToStorage("multipolygon_1.sql", $geomGeneratorSql, "file", 10000, False);
				//new approach - after 2022-07-11:
				$lonLatBboxWms2 = transformMultipolygon($geomGeneratorSql, intval($epsgId), 4326, $mapbenderMetadata[$m]->metadata_uuid, $polygonalFilter);
				//delete entries from original bbox where latlon could not be calculated
				//$e = new mb_exception("number of bbox records: " . count($bboxWms));
				$newBboxWms = array();
				$arrayKeysWgsBox = array_keys($lonLatBboxWms2);
				for ($k = 0; $k < count($arrayKeysWgsBox); $k++) {
				    $newBboxWms[] = $bboxWms[$arrayKeysWgsBox[$k]];
				}
				$bboxWms = $newBboxWms;
				$bboxWmsWGS84 = array_values($lonLatBboxWms2); 	
			}
		}
		//generate wgs84 bbox again - transform it from projected coords
		$numberOfTiles = count($bboxWms);
	} else {
		if ($type == 'DATASET' && $generateFrom == "wfs") {
			$numberOfEntries = 1;
			//generate Download Links for the different featuretypes
			//first calculate the number of tiles for the different featureTypes
			$featureHits = array();
			$getFeatureLink = array();
			$featureTypeName = array();
			$featureTypeBbox = array();
			$featureTypeBboxWGS84 = array();
			$featuretypeIndex = false;
			//For each featuretype which was found! Maybe more than one!
			for ($i = 0; $i < $countRessource; $i++) {
//$e = new mb_exception($featuretypeId);
				if ($featuretypeId !== false && $mapbenderMetadata[$i]->featuretype_id == $featuretypeId) {
				$featuretypeIndex = $i;
				//overwrite feature count with information from database
				$maxFeatureCount = (integer)$mapbenderMetadata[$i]->wfs_max_features;
				
				$crs = $mapbenderMetadata[$i]->metadata_ref_system;
				//use featuretype ref system - to build the bbox filters!!!
				$crs = $mapbenderMetadata[$i]->featuretype_srs;
				//log ref system of metadata - is this a good idea?
				//$e = new mb_exception("Ref system of featuretype ".$mapbenderMetadata[$i]->featuretype_name." : ".$crs);
				//*********************** New use crs class for checking if the order has to be changed in the filter!!!
				$crsObject = new Crs($crs);
				if ($crsObject->alterAxisOrder("wfs"."_".$mapbenderMetadata[$i]->wfs_version) == true) {
					$alterAxisOrder = true;
				} else {
					$alterAxisOrder = false;
				}
				//**************************************************************************************
				$e = new mb_notice("Epsg id of featuretype ".$mapbenderMetadata[$i]->featuretype_name." : ".$epsgId);
				if (!($mapbenderMetadata[$i]->wfs_version) || $mapbenderMetadata[$i]->wfs_version == '') {
					return "<error>Version of WFS : ".$mapbenderMetadata[$i]->wfs_version." is not supported to generate inspire download services for predefined datasets!</error>";
				}
				//count features by class instead of own way
				//instantiate wfs by id
				$myWfsFactory = new UniversalWfsFactory ();
				$wfs = $myWfsFactory->createFromDb ( $mapbenderMetadata[$i]->wfs_id );
				//simple invocation
				$featureHitsTest = $wfs->countFeatures( $mapbenderMetadata[$i]->featuretype_name, false, false, false, false, 'GET');
				if ($featureHitsTest == false) {
				    $message = "counting is not possible";
				    $countTiles = 1;
				} else {
				    $e = new mb_exception("php/mod_inspireDownloadFeed.php: hits 1: " . $featureHitsTest);
				    $featureHits[$i] = (integer)$featureHitsTest;
				    //$e = new mb_exception($featureHits[$i]." hits for featuretype ".$mapbenderMetadata[$i]->featuretype_name);
				    //calculate further bboxes if the # of hits extents some value
				    //minimum number of single tiles:
				    $countTiles = ceil($featureHits[$i]/$maxFeatureCount);
				}
				//calculate number of rows and columns from x / y ratio
				//$e = new mb_exception("http/php/mod_inspireDownloadFeed.php: - Bbox from metadata: minx: ". $mapbenderMetadata[$i]->minx." - miny: ".$mapbenderMetadata[$i]->miny." - maxx: ".$mapbenderMetadata[$i]->maxx." - maxy: ".$mapbenderMetadata[$i]->maxy." - CRS: ".$mapbenderMetadata[$i]->featuretype_srs." - EPSG ID: ".$crsObject->identifierCode);
				//set epsgId;
				//$e = new mb_exception("http/php/mod_inspireDownloadFeed.php: featureHits: " . json_encode($featureHits) );				
				$epsgId = $crsObject->identifierCode;
				/*
				if ($crsObject->alterAxisOrder == true) {
				    $e = new mb_exception("mod_isnpireDownloadFeed.php: change axis order TRUE");
				} else {
				    $e = new mb_exception("mod_isnpireDownloadFeed.php: change axis order FALSE");
				}*/
				//read out in variables 
				$sqlExtent = "SELECT X(transform(GeometryFromText('POINT(".$mapbenderMetadata[$i]->minx." ".$mapbenderMetadata[$i]->miny.")',4326),".$crsObject->identifierCode.")) as minx, Y(transform(GeometryFromText('POINT(".$mapbenderMetadata[$i]->minx." ".$mapbenderMetadata[$i]->miny.")',4326),".$crsObject->identifierCode.")) as miny, X(transform(GeometryFromText('POINT(".$mapbenderMetadata[$i]->maxx." ".$mapbenderMetadata[$i]->maxy.")',4326),".$crsObject->identifierCode.")) as maxx, Y(transform(GeometryFromText('POINT(".$mapbenderMetadata[$i]->maxx." ".$mapbenderMetadata[$i]->maxy.")',4326),".$crsObject->identifierCode.")) as maxy";
				$resExtent =  db_query($sqlExtent);
				//depending on providing service and crs the axis order should be taken into account 
				$minx = floatval(db_result($resExtent,0,"minx"));
				$miny = floatval(db_result($resExtent,0,"miny"));
				$maxx = floatval(db_result($resExtent,0,"maxx"));
				$maxy = floatval(db_result($resExtent,0,"maxy"));
				/*$e = new mb_exception("http/php/mod_inspireDownloadFeed.php: - minx: ".$minx. " - maxx: ".$maxx);	
				$e = new mb_exception("http/php/mod_inspireDownloadFeed.php: - miny: ".$miny. " - maxy: ".$maxy);	
				$e = new mb_exception("http/php/mod_inspireDownloadFeed.php: countTiles: " . $countTiles);*/
				$geometryFieldName = getGeometryFieldNameFromMapbenderDb($mapbenderMetadata[$i]->geometry_field_name[0], $mapbenderMetadata[$i]->featuretype_name);
				//only calculate new boxes if countTiles > 1
				if ($countTiles > 1) {
					$diffX = $maxx - $minx; //in m - depends on given epsg code
					$diffY = $maxy - $miny;	//in m
					$width = sqrt(($diffX * $diffY) / $countTiles);
					$nRows = ceil($diffY / $width);
					$nCols = ceil($diffX / $width);
					$bboxWfs = array();
					$bboxWfs2 = array();
					$countBbox = 0;
					$multiPolygonText = "'MULTIPOLYGON(";
					for ($j = 0; $j < $nRows; $j++) {
						for ($k = 0; $k < $nCols; $k++) {
							//echo "j: ".$k.",k: ".$j;
							$minxWfs = $minx + $k * $width;
							//echo "minxWms: ". $minxWms .",";
							$minyWfs = $miny + $j * $width;
							//echo "minyWms: ". $minyWms .",";
							$maxxWfs = $minx + ($k+1) * $width;
							//echo "maxxWms: ". $maxxWms .",";
							$maxyWfs = $miny + ($j+1) * $width;
							//echo "maxyWms: ". $maxyWms .",";
							//check if bbox don't have more than maxfeatures, the bboxes are given in the
							$bboxFilter = getBboxFilter(array($minxWfs, $minyWfs, $maxxWfs, $maxyWfs), $crs, $wfs->getVersion(), $geometryFieldName, $alterAxisOrder);
							//count features in current bbox
							$featureHitsBbox = $wfs->countFeatures( $mapbenderMetadata[$i]->featuretype_name, $bboxFilter, false, false, false, 'GET');
							$e = new mb_notice("http/php/mod_inspireDownloadFeed.php: - hits for bbox: " . $featureHitsBbox);
							if ($featureHitsBbox > 0) {
							    $e = new mb_notice("http/php/mod_inspireDownloadFeed.php: - add recursively");
							    //recursively add bbox to array 
							    addBboxEntry(array($minxWfs, $minyWfs, $maxxWfs, $maxyWfs), $bboxWfs, $countBbox, $multiPolygonText, $featureHitsBbox, $crs, $wfs, $maxFeatureCount, $mapbenderMetadata[$i]->featuretype_name, $geometryFieldName);
							}
						}
					}
					//new approach since 2022-07-11
					$multiPolygonText = rtrim($multiPolygonText, ",");
					$multiPolygonText .= ")'";
					$geomGeneratorSql = "ST_GeomFromText(" . $multiPolygonText . "," . intval($epsgId) . ")";
					//$admin->putToStorage("multipolygon_1.sql", $geomGeneratorSql, "file", 10000, False);
					//new approach - after 2022-07-11:
					$lonLatBboxWfs2 = transformMultipolygon($geomGeneratorSql, intval($epsgId), 4326, $mapbenderMetadata[$i]->metadata_uuid, $polygonalFilter);
					//delete entries from original bbox where latlon could not be calculated
					$newBboxWfs = array();
				    $arrayKeysWgsBox = array_keys($lonLatBboxWfs2);
				    for ($k = 0; $k < count($arrayKeysWgsBox); $k++) {
				        $newBboxWfs[$mapbenderMetadata[$i]->featuretype_name][] = $bboxWfs[$mapbenderMetadata[$i]->featuretype_name][$arrayKeysWgsBox[$k]];
				    }
				    $bboxWfs[$mapbenderMetadata[$i]->featuretype_name] = $newBboxWfs[$mapbenderMetadata[$i]->featuretype_name];
					$featureTypeBboxWGS84 = array_values($lonLatBboxWfs2); 	
					$countBbox = count($featureTypeBboxWGS84);	
				} else {
					//only normal extent used
					if ($minx == "" || $miny == "" || $maxx == "" || $maxy == "") {
						//set default values
						//EPSG:4326
						//use lon/lat - for postgis!!!
						$minx = "-180";
						$miny = "-90";
						$maxx = "180";
						$maxy = "90";
						/*
							
						*/	
						$epsgId = "4326";
					}
					$bboxWfs[$mapbenderMetadata[$i]->featuretype_name][0] = $minx.",".$miny.",".$maxx.",".$maxy;
					//transform bbox back to geographic coordinates
					$lonLatBbox = transformBbox($minx.",".$miny.",".$maxx.",".$maxy,intval($epsgId),4326);
					$lonLatBbox = explode(',',$lonLatBbox);
					//georss needs latitude longitude
					$featureTypeBboxWGS84[] = $lonLatBbox[1].",".$lonLatBbox[0].",".$lonLatBbox[3].",".$lonLatBbox[2];		
				}
				
				//$getFeatureLink = array();
				/*TODO for ($i = 0; $i < $countRessource-1; $i++) {
					$gFLink = $mapbenderMetadata[$i]->wfs_getfeature."SERVICE=WFS&REQUEST=GetFeature&VERSION=";
					$gFLink .= $mapbenderMetadata[$i]->wfs_version."&typeName=".$mapbenderMetadata[$i]->featuretype_name;
					$gFLink .= "&maxFeatures=".$featureHits[$i]."&srsName=".$mapbenderMetadata[$i]->featuretype_srs;
					$getFeatureLink[] = $gFLink;
				}*/
				//echo count($bboxWfs[$mapbenderMetadata[$i]->featuretype_name]);
				for ($l = 0; $l < count($bboxWfs[$mapbenderMetadata[$i]->featuretype_name]); $l++) {

					//generate bbox Filter:
					//<ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:BBOX><gml:Box xmlns:gml="http://www.opengis.net/gml" srsName="EPSG:3785"><gml:coordinates decimal="." cs="," ts=" ">-8033496.4863128,5677373.0653376 -7988551.5136872,5718801.9346624</gml:coordinates></gml:Box></ogc:BBOX></ogc:Filter>
					/*   fes="http://www.opengis.net/fes/2.0"  xmlns:gml="http://www.opengis.net/gml/3.2"
	<fes:Filter>
            <fes:BBOX>
               <fes:ValueReference>/RS1/geometry</fes:ValueReference>
               <gml:Envelope srsName="urn:ogc:def:crs:EPSG::1234">
                  <gml:lowerCorner>10 10</gml:lowerCorner>
                  <gml:upperCorner>20 20</gml:upperCorner>
               </gml:Envelope>
            </fes:BBOX>
	</fes:Filter>
					*/
				    
				    //				    
					//$e = new mb_exception("mod_inspireDownloadFeed.php: geometryFieldName: ".$geometryFieldName);
					//get bbox from wfs metadata
					$currentBbox = explode(',',$bboxWfs[$mapbenderMetadata[$i]->featuretype_name][$l]);
					//change axis order if crs definition and service needs it
					if ($alterAxisOrder == true) {
						$e = new mb_exception("mod_inspireDownloadFeed.php: axis order should be altered!");
					}
					$currentBboxGetFeature = $currentBbox;
					$geometryFieldName = getGeometryFieldNameFromMapbenderDb($mapbenderMetadata[$i]->geometry_field_name[0], $mapbenderMetadata[$i]->featuretype_name);
					$bboxFilter = getBboxFilter($currentBboxGetFeature, $crs, $wfs->getVersion(), $geometryFieldName, $alterAxisOrder);
					//check if owsproxy is activated for wfs - if so, use absolute url of wfs
					//e.g.: www.geoportal.rlp.de/registry/wfs/{wfs_id}? - important - there has to be one wfsconf defined and assigned!
					if ($admin->getWFSOWSstring($mapbenderMetadata[$i]->wfs_id) == false) {
						$wfsGetFeatureUrl = $mapbenderMetadata[$i]->wfs_getfeature;
					} else {
						$wfsGetFeatureUrl = $mapbenderServerUrl."/registry/wfs/".$mapbenderMetadata[$i]->wfs_id."?";
					}
					$gFLink = $wfsGetFeatureUrl."SERVICE=WFS&REQUEST=GetFeature&VERSION=";
					$gFLink .= $mapbenderMetadata[$i]->wfs_version."&".$typeParameterName."=".$mapbenderMetadata[$i]->featuretype_name;
					$gFLink .= "&srsName=".$mapbenderMetadata[$i]->featuretype_srs;
					//TODO check if other epsg string should be used!
					//$crsObject->identifier;
					if (count($mapbenderMetadata[$i]->output_formats) >= 1 && strtoupper($mapbenderMetadata[$i]->geometry_field_name[0] !== "SHAPE")) {
						//use first output format which have been found - TODO - check if it should be pulled from featuretype instead from wfs 
						$gFLink .= "&outputFormat=".rawurlencode($mapbenderMetadata[$i]->output_formats[0]);
					}
					$gFLink .= "&FILTER=".rawurlencode(utf8_decode($bboxFilter));
					$getFeatureLink[] = $gFLink;
					$featureTypeName[] = $mapbenderMetadata[$i]->featuretype_name;
					$featureTypeBbox[] = $bboxWfs[$mapbenderMetadata[$i]->featuretype_name][$l];
					
				}
				//$numberOfTiles = count($bboxWfs[$mapbenderMetadata[$i]->featuretype_name]);
				//$e = new mb_exception("Number of tiles for wfs predefined download service: ".$numberOfTiles);
			} //end for if filter - if some featuretypeid was given via get parameter
			} //end for each featuretype
			
		} else { //type SERVICE was set - generate one entry for each coupled resource - they are distinguished by names, titles, ids, bbox, type of download! 
			$numberOfEntries = count($mapbenderMetadata);
		}
	}
	$e = new mb_notice("Count of bboxes: ".$numberOfEntries);
	for ($i = 0; $i < $numberOfEntries; $i++) {
		//<entry> 5.1.12 / 5.2.3
		$feedEntry = $feedDoc->createElement("entry");
		//<!-- title for pre-defined dataset -->
		//<title xml:lang="en">Water network ABC</title>
		//<title>Water network in CRS EPSG:4258 (GML)</title>
		$feedEntryTitle = $feedDoc->createElement("title");
		//$feedEntryTitle->setAttribute("xml:lang", "de");
		if ($type == 'SERVICE') {
			//generate one entry for each possible download variant
			switch ($mapbenderMetadata[$i]->origin) {
				case "wmslayer":
					$ressourceServiceFeedEntryTitle = $ressourceTitle." - generiert aus WMS Datenquelle";
				break;
				case "dataurl":
					$ressourceServiceFeedEntryTitle = $ressourceTitle." - generiert aus WMS Capabilities dataURL Element";
				break;
				case "wfs":
					$ressourceServiceFeedEntryTitle = $ressourceTitle." - Objektart: ".$mapbenderMetadata[$i]->featuretype_title." (".$mapbenderMetadata[$i]->featuretype_name.") - generiert über WFS GetFeature Aufrufe";
				break;
				case "metadata":
					$ressourceServiceFeedEntryTitle = $ressourceTitle." - generiert über Downloadlinks aus Metadatensatz";
				break;
				case "metadata":
				    $ressourceServiceFeedEntryTitle = $ressourceTitle." - generiert über externe Datenlinks";
				break;
				case "remotelist":
				    $ressourceServiceFeedEntryTitle = $ressourceTitle." - generiert über remote Dateiliste";
				    break;
			}
			$feedEntryTitle->appendChild($feedDoc->createTextNode("Feed Entry fuer: ".$ressourceServiceFeedEntryTitle)); //TODO: maybe add some category?
		} else {
			switch ($mapbenderMetadata[$i]->origin) {
				case "wmslayer":
					$ressourceDataFeedEntryTitle = $ressourceTitle." - generiert aus WMS Datenquelle";
					$resourceFormat = "image/tiff";
				break;
				case "dataurl":
					$ressourceDataFeedEntryTitle = $ressourceTitle." - generiert aus WMS Capabilities dataURL Element";
					$resourceFormat = $mapbenderMetadata[$i]->format;
				break;
				case "wfs":
					if ($featuretypeIndex !== false) {
						$ressourceDataFeedEntryTitle = $ressourceTitle." - Objektart: ".$mapbenderMetadata[$featuretypeIndex]->featuretype_title." (".$mapbenderMetadata[$featuretypeIndex]->featuretype_name.") - generiert über WFS GetFeature Aufrufe";
					} else {
						$ressourceDataFeedEntryTitle = $ressourceTitle." - Objektart: ".$mapbenderMetadata[$i]->featuretype_title." (".$mapbenderMetadata[$i]->featuretype_name.") - generiert über WFS GetFeature Aufrufe";
					}
					//$resourceFormat = "application/gml+xml";
					//first format from wfs server
					$resourceFormat = $mapbenderMetadata[$i]->output_formats[0];
				break;
				case "metadata":
					$ressourceDataFeedEntryTitle = $ressourceTitle." - generiert über Downloadlinks aus Metadatensatz";
					$resourceFormat = $mapbenderMetadata[$i]->format;
				break;
				case "remotelist":
				    $ressourceDataFeedEntryTitle = $ressourceTitle." - generiert über über externe Datenlinks";
				    //TODO extract format mimetype from json 
				    
				    $resourceFormat = $mapbenderMetadata[$i]->format;
				break;
			}
			if ($mapbenderMetadata[$i]->origin == "remotelist") {
			    $feedEntryTitle->appendChild($feedDoc->createTextNode($ressourceDataFeedEntryTitle . " im CRS " . $atomFeedCrs . " und Format " . $atomFeedFormat)); //TODO: maybe add some category?
			    
			} else {
			    $feedEntryTitle->appendChild($feedDoc->createTextNode($ressourceDataFeedEntryTitle. " im CRS " . $mapbenderMetadata[$i]->metadata_ref_system . " und Format " . $resourceFormat)); //TODO: maybe add some category?	
			}
		}
		$feedEntry->appendChild($feedEntryTitle);

		/*For Service: <!—Spatial Dataset Unique Resourse Identifier for this dataset-->
		<inspire_dls:spatial_dataset_identifier_code>wn_id1</inspire_dls:spatial_dataset_identifier_code>
		<inspire_dls:spatial_dataset_identifier_namespace>http://xyz.org/</inspire_dls:spatial_dataset_identifier_namespace>
		<!-- link to dataset metadata record -->
		<link href="http://xyz.org/metadata/abcISO19139.xml" rel="describedby" type="application/vnd.iso.19139+xml"
		<!-- link to "Dataset Feed" for pre-defined dataset -->
		<link rel="alternate" href="http://xyz.org/data/waternetwork_feed.xml" type="application/atom+xml" hreflang="en" title="Feed containing the pre-defined waternetwork dataset (in one or more downloadable formats)"/>
		<!-- identifier for "Dataset Feed" for pre-defined dataset -->
		<id>http://xyz.org/data/waternetwork_feed.xml</id>*/
		//or link to dataset 5.2.3
		//<link rel="alternate" href="http://xyz.org/data/abc/waternetwork_WGS84.shp" type="application/x-shp" hreflang="en" title="Water Network encoded as a ShapeFile in WGS84geographic coordinates (http://www.opengis.net/def/crs/OGC/1.3/CRS84)"/>
			
		$datasetFeedLink = $mapbenderServerUrl.$_SERVER['SCRIPT_NAME']."?id=".$recordId."&type=DATASET&generateFrom=".$mapbenderMetadata[$i]->origin;
		switch($mapbenderMetadata[$i]->origin) {
			case "wmslayer":
				$datasetFeedLink .= "&layerid=".$mapbenderMetadata[$i]->layer_id;
			break;
			case "wfs":
				$datasetFeedLink .= "&wfsid=".$mapbenderMetadata[$i]->wfs_id;
				if ($featuretypeId !== false) {
//$e = new mb_exception("ft_id: ".$featuretypeId);
					$datasetFeedLink .= "&featuretypeid=".$featuretypeId;
				} else {
					$datasetFeedLink .= "&featuretypeid=".$mapbenderMetadata[$i]->featuretype_id;
				}
			break;
			case "metadata":
				//$datasetFeedLink .= "&wfsid=".$mapbenderMetadata[$i]->wfs_id;
			break;
		}
		$datasetLink = $mapbenderMetadata[$i]->datalink_url;
		if ($type == 'SERVICE') {
			//insert resource identifier for service / dataset coupling!!
			$feedEntryIdCode = $feedDoc->createElement("inspire_dls:spatial_dataset_identifier_code");
			$feedEntryIdCodeText = $feedDoc->createTextNode($mapbenderMetadata[$i]->datasetid);
			$feedEntryIdCode->appendChild($feedEntryIdCodeText);
			$feedEntry->appendChild($feedEntryIdCode);
			$feedEntryIdNamespace = $feedDoc->createElement("inspire_dls:spatial_dataset_identifier_namespace");
			$feedEntryIdNamespaceText = $feedDoc->createTextNode($mapbenderMetadata[$i]->datasetid_codespace);
			$feedEntryIdNamespace->appendChild($feedEntryIdNamespaceText);
			$feedEntry->appendChild($feedEntryIdNamespace);
			$metadataLink = $mapbenderPath."php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$mapbenderMetadata[$m]->metadata_uuid;
			$feedEntryMetadataLink = $feedDoc->createElement("link");
			$feedEntryMetadataLink->setAttribute("href",$metadataLink);
			$feedEntryMetadataLink->setAttribute("rel", "describedby");
			$feedEntryMetadataLink->setAttribute("type", "application/xml");
			$feedEntry->appendChild($feedEntryMetadataLink);
			$furtherLink = $datasetFeedLink;
			$furtherLinkType = "application/atom+xml";
			$furtherLinkTitle = "Feed für den pre-defined Datensatz ".$ressourceTitle;
			$feedEntryLink = $feedDoc->createElement("link");
			$feedEntryLink->setAttribute("rel", "alternate");
			$feedEntryLink->setAttribute("href", $furtherLink);
			$feedEntryLink->setAttribute("type", $furtherLinkType);
			$feedEntryLink->setAttribute("hreflang", "de");
			$feedEntryLink->setAttribute("title", $furtherLinkTitle);
			$feedEntry->appendChild($feedEntryLink);
		} else { //DATASET
			switch ($generateFrom) {
				case "dataurl":
					$furtherLink = $datasetLink;
					$furtherLinkType = $mapbenderMetadata[$i]->datalink_format;
					$furtherLinkTitle = $ressourceTitle." im CRS ".$mapbenderMetadata[$i]->metadata_ref_system."(".$mapbenderMetadata[$i]->format.")";
					//generate content link 
					$feedEntryLink = $feedDoc->createElement("link");
					$feedEntryLink->setAttribute("rel", "alternate");
					$feedEntryLink->setAttribute("href", $furtherLink);
					$feedEntryLink->setAttribute("type", $furtherLinkType);
					$feedEntryLink->setAttribute("hreflang", "de");
					$feedEntryLink->setAttribute("title", $furtherLinkTitle);
					//$feedEntryLink->setAttribute("bbox", $newBox);
					$feedEntry->appendChild($feedEntryLink);	
				break;
				case "metadata":
					$downloadLinks = json_decode($mapbenderMetadata[$i]->datalinks);
					$furtherLink = urldecode($downloadLinks->downloadLinks[0]->{"0"});
					$furtherLinkType = $formatsMimetype[$mapbenderMetadata[$i]->format];
					$furtherLinkTitle = $ressourceTitle." im CRS ".$mapbenderMetadata[$i]->metadata_ref_system."(".$mapbenderMetadata[$i]->format.")";
					//generate content link 
					$feedEntryLink = $feedDoc->createElement("link");
					$feedEntryLink->setAttribute("rel", "alternate");
					$feedEntryLink->setAttribute("href", $furtherLink);
					$feedEntryLink->setAttribute("type", $furtherLinkType);
					$feedEntryLink->setAttribute("hreflang", "de");
					$feedEntryLink->setAttribute("title", $furtherLinkTitle);
					if (isset($mapbenderMetadata[$i]->transfer_size) && $mapbenderMetadata[$i]->transfer_size !== '') {
						$feedEntryLink->setAttribute("length", ceil(((double)$mapbenderMetadata[$i]->transfer_size)*1000000));
					}
					//$feedEntryLink->setAttribute("bbox", $newBox);
					$feedEntry->appendChild($feedEntryLink);	
				break;
				case "remotelist":				    
				    $atomFeedLinkList = $dcatDistribution->{'dcat:accessService'}->{'dct:hasPart'};
				    $atomFeedTitle = $dcatDistribution->{'dcterms:title'};
				    $atomFeedDescription = $dcatDistribution->{'dcterms:Description'};
				    $atomFeedFormat = $dcatDistribution->{'dcterms:format'};
				    $atomFeedCrs = "EPSG:" . $dcatDistribution->{'gdirp:epsgCode'};
				    
				    $furtherLink = urldecode($atomFeedLinkList);
				    /*$furtherLinkType = $atomFeedFormat;
				    $furtherLinkTitle = $atomFeedTitle." im CRS " . $atomFeedCrs . " (" . $atomFeedFormat . ")";

				    //generate content link
                    $feedEntryLink = $feedDoc->createElement("link");
				    $feedEntryLink->setAttribute("rel", "alternate");
				    $feedEntryLink->setAttribute("href", $furtherLink);
				    $feedEntryLink->setAttribute("type", $furtherLinkType);
				    $feedEntryLink->setAttribute("hreflang", "de");
				    $feedEntryLink->setAttribute("title", $furtherLinkTitle);*/
				    //resolve remote link list
				    $listConnector = new Connector();
				    //check if further list came from allowed server
				    $e = new mb_exception("further link: " . $furtherLink);
				    if (strpos($furtherLink, "https://geobasis-rlp.de") !== 0) {
				        header("Content-type: text/html");
				        echo "The url for the link list is not allowed!";
				        die();
				    } 
				    $listConnector->set('timeout', 5);
				    $result = $listConnector->load($furtherLink);
				    $insertXml = "<?xml version='1.0'?><list>" . $result . "</list>";
				    $xmlNodes = simplexml_load_string($insertXml);
				    //insert single nodes as new childs
				    foreach ($xmlNodes as $node) {
				        $dom_sxe = dom_import_simplexml($node);
				        $dom_sxe = $feedDoc->importNode($dom_sxe, true);
				        $feedEntry->appendChild($dom_sxe);
				    }
				    break;
				case "wmslayer":
					//example:
					//http://localhost/cgi-bin/mapserv?map=/data/umn/geoportal/karte_rp/testinspiredownload.map&VERSION=1.1.1&REQUEST=GetMap&SERVICE=WMS&LAYERS=inspirewms&STYLES=&SRS=EPSG:4326&BBOX=6.92134,50.130465,6.93241,50.141535000000005&WIDTH=200&HEIGHT=200&FORMAT=image/png&BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_inimage
					//generate further links, one for each tile which was computed before
					$furtherLink = array();
					$furtherLinkType = array();
					$furtherLinkTitle = array();
					$furtherLinkBbox = array();
					if ($numberOfTiles > 1) {
						$feedEntryContent = $feedDoc->createElement("content");
						$feedEntryContentText = $feedDoc->createTextNode("Datensatz wird in  in ".$numberOfTiles." einzelnen Teilen ausgeliefert.");
						$feedEntryContent->appendChild($feedEntryContentText);
						$feedEntry->appendChild($feedEntryContent);
					}
					for ($m = 0; $m < $numberOfTiles; $m++ ) {
						//check if proxy is used, if so exchange urls with proxy urls
						if ($mapbenderMetadata[$i]->wms_owsproxy <> NULL && $mapbenderMetadata[$i]->wms_getmap != '') {
							$getMapUrl = str_replace(":80","",HTTP_AUTH_PROXY)."/".$mapbenderMetadata[$i]->layer_id."?";
							//TODO check why :80 is part of the http_host - maybe apache rewrite
						} else {
							$getMapUrl = $mapbenderMetadata[$i]->wms_getmap;
						}
						//TODO - define further link for wms 1.1.1 and wms 1.3.0 - SRS Paramter changed and maybe the axis order!!!!!!
						$furtherLink[$m] = $getMapUrl."REQUEST=GetMap&VERSION=".$mapbenderMetadata[$i]->wms_version."&SERVICE=WMS&LAYERS=".$mapbenderMetadata[$i]->layer_name;

						switch ($mapbenderMetadata[$i]->wms_version) {
						    case "1.1.1":
						        $furtherLink[$m] .= "&STYLES=&SRS=".trim($crs)."&BBOX=".$bboxWms[$m]."&WIDTH=".$maxImageSize."&HEIGHT=".$maxImageSize."&FORMAT=image/tiff&";
						        $furtherLink[$m] .= "BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_inimage";
						        break;
						    case "1.3.0":
						        $furtherLink[$m] .= "&STYLES=&CRS=".trim($crs)."&BBOX=".$bboxWms[$m]."&WIDTH=".$maxImageSize."&HEIGHT=".$maxImageSize."&FORMAT=image/tiff&";
						        $furtherLink[$m] .= "BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=INIMAGE";
						        break;
						    default:
						        $furtherLink[$m] .= "&STYLES=&SRS=".trim($crs)."&BBOX=".$bboxWms[$m]."&WIDTH=".$maxImageSize."&HEIGHT=".$maxImageSize."&FORMAT=image/tiff&";
						        $furtherLink[$m] .= "BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_inimage";
						        break;
						}
						$furtherLinkType[$m] = "image/tiff"; //formats from layer_format - geotiff
						$currentTileIndex = $m+1;
						$furtherLinkTitle[$m] = $ressourceTitle." im CRS ".$mapbenderMetadata[$i]->metadata_ref_system." - ".$resourceFormat." - Teil ".$currentTileIndex." von ".$numberOfTiles."";
						//$furtherLinkBbox[$m] = $bboxWms[$m];
						$furtherLinkBbox[$m] = $bboxWmsWGS84[$m];
						//exchange lon lat with lat long for georss
						$newBox = explode(',',$furtherLinkBbox[$m]);
						//georss needs latitude longitude - done before when transform it ;-)
						$newBox = $newBox[0].",".$newBox[1].",".$newBox[2].",".$newBox[3];
						//generate content link 
						$feedEntryLink = $feedDoc->createElement("link");
						if ($numberOfTiles > 1) {
							$feedEntryLink->setAttribute("rel", "section");
						} else {
							$feedEntryLink->setAttribute("rel", "alternate");
						}
						$feedEntryLink->setAttribute("href", $furtherLink[$m]);
						$feedEntryLink->setAttribute("type", $furtherLinkType[$m]);
						$feedEntryLink->setAttribute("hreflang", "de");
						$feedEntryLink->setAttribute("title", $furtherLinkTitle[$m]);
						//if axis order was 
						$feedEntryLink->setAttribute("bbox", str_replace(","," ",$newBox));

						$feedEntry->appendChild($feedEntryLink);	
					}
				break;
				case "wfs":
					//example:
					//http://localhost/cgi-bin/mapserv?map=/data/umn/geoportal/karte_rp/testinspiredownload.map&VERSION=1.1.1&REQUEST=GetMap&SERVICE=WMS&LAYERS=inspirewms&STYLES=&SRS=EPSG:4326&BBOX=6.92134,50.130465,6.93241,50.141535000000005&WIDTH=200&HEIGHT=200&FORMAT=image/png&BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_inimage
					//for each possibly format do following
					//foreach ($mapbenderMetadata[$i]->output_formats as $output_format) {
					$furtherLink = array();
					$furtherLinkType = array();
					$furtherLinkTitle = array();
					$furtherLinkBbox = array();
					//loop for each featuretype
					$e = new mb_notice("Count of wfs links: ".count($getFeatureLink));
					if (count($getFeatureLink) > 1) {
						$feedEntryContent = $feedDoc->createElement("content");
						$feedEntryContentText = $feedDoc->createTextNode("Datensatz wird in  in ".count($getFeatureLink)." einzelnen Teilen ausgeliefert.");
						//TODO exchange text for output_format!
						$feedEntryContent->appendChild($feedEntryContentText);
						$feedEntry->appendChild($feedEntryContent);
					}
					for ($m = 0; $m < count($getFeatureLink); $m++ ) {
						//TODO exchange text for output_format!
						$furtherLink[$m] = $getFeatureLink[$m];//was computed before
						//discard filter if only one request is needed - problem with epsg codes? TODO solve problem
						if (count($getFeatureLink) == 1) {
							$splittedLink = explode('&FILTER=',$furtherLink[$m]);
							$furtherLink[$m] = $splittedLink[0];
						}
						//TODO exchange text for output_format - maybe match them before?
						$furtherLinkType[$m] = "application/gml+xml";//inspire media type registry http://inspire.ec.europa.eu/media-types/
						$currentIndex = $m+1;
						$furtherLinkTitle[$m] = $ressourceTitle." im CRS ".$mapbenderMetadata[$i]->metadata_ref_system." - ".$resourceFormat." - Teil ".$currentIndex." von ".count($getFeatureLink)."";//TODO: set right format for wfs version!
						//$furtherLinkBbox[$m] = $featureTypeBbox[$m];
						$furtherLinkBbox[$m] = $featureTypeBboxWGS84[$m];

						$newBox = explode(',',$furtherLinkBbox[$m]);
						/*if ($alterAxisOrder == true) {
							$newBox = $newBox[1].",".$newBox[0].",".$newBox[3].",".$newBox[2];
						} else {*/
						
							$newBox = $newBox[0].",".$newBox[1].",".$newBox[2].",".$newBox[3];
						/*}*/
						//generate content links
						$feedEntryLink = $feedDoc->createElement("link");
						if (count($getFeatureLink) > 1) {
							$feedEntryLink->setAttribute("rel", "section");
						} else {
							$feedEntryLink->setAttribute("rel", "alternate");
						}
						$feedEntryLink->setAttribute("href", $furtherLink[$m]);
						$feedEntryLink->setAttribute("type", $furtherLinkType[$m]);
						$feedEntryLink->setAttribute("hreflang", "de");
						$feedEntryLink->setAttribute("title", $furtherLinkTitle[$m]);
						$feedEntryLink->setAttribute("bbox", str_replace(","," ",$newBox));
						$feedEntry->appendChild($feedEntryLink);
					}	
					//} end of foreach output_format
				break;//end for service type wfs
			}
		}
		//In the case of dynamically build entries for a raster based wms - not the dataurl but another the dyn ulrs will be used in the next feed
		//5.1.14 / 5.2
		//<!-- identifier for pre-defined dataset -->
		//<id>http://xyz.org/data/waternetwork.gml/id>
		//insert self reference
		$feedEntryId = $feedDoc->createElement("id");
		$feedEntryId->appendChild($feedDoc->createTextNode($datasetFeedLink));
		$feedEntry->appendChild($feedEntryId);

		//<!-- rights, access restrictions -->
		//<rights>Copyright (c) 2011, XYZ; all rights reserved</rights> -- see 5.1.9 on page 39 of INSPIRE GD for Download Services V 3.0 - only accessconstraints should be used
		$feedEntryRights = $feedDoc->createElement("rights");
		$feedEntryRightsText = $feedDoc->createTextNode($mapbenderMetadata[$i]->accessconstraints);
		$feedEntryRights->appendChild($feedEntryRightsText);
		$feedEntry->appendChild($feedEntryRights);
		
		//5.1.14 / 5.2  - updated
		//<!-- last date/time pre-defined dataset was updated -->
		//<updated>2011-06-14T12:22:09Z</updated>
		$feedEntryUpdated = $feedDoc->createElement("updated");
		$feedEntryUpdated->appendChild($feedDoc->createTextNode(date(DATE_ATOM,time())));
		$feedEntry->appendChild($feedEntryUpdated);

		//5.1.15 / 
		//<!-- summary -->
		//<summary>This is the entry for water network ABC Dataset</summary>
		if ($type == 'SERVICE') {
			$feedEntrySummary = $feedDoc->createElement("summary");
			$feedEntrySummary->appendChild($feedDoc->createTextNode("Nähere Beschreibung des Feedinhaltes: ".$ressourceAbstract));
			$feedEntry->appendChild($feedEntrySummary);
		}

		//5.1.16 / 5.2?
		//<!-- optional GeoRSS-Simple bounding box of the pre-defined dataset. Must be lat lon -->
		//<georss:polygon>47.202 5.755 55.183 5.755 55.183 15.253 47.202 15.253 47.202 5.755</georss:polygon>
		//TODO: Get this out of mb_metadata! If not given get it from layer bbox - but normally they should be identical!
		$feedEntryBbox = $feedDoc->createElement("georss:polygon");
		$e = new mb_notice('mapbender minx: '.$mapbenderMetadata[$i]->minx);
		$e = new mb_notice('mapbender i: '.$i);
		$e = new mb_notice('mapbender origin: '.$mapbenderMetadata[$i]->origin);
		$georssPolygon = $mapbenderMetadata[$i]->miny." ".$mapbenderMetadata[$i]->minx." ".$mapbenderMetadata[$i]->maxy." ".$mapbenderMetadata[$i]->minx." ";
		$georssPolygon .= $mapbenderMetadata[$i]->maxy." ".$mapbenderMetadata[$i]->maxx." ".$mapbenderMetadata[$i]->miny." ".$mapbenderMetadata[$i]->maxx." ";
		$georssPolygon .= $mapbenderMetadata[$i]->miny." ".$mapbenderMetadata[$i]->minx;	
		$feedEntryBbox->appendChild($feedDoc->createTextNode($georssPolygon));
		$feedEntry->appendChild($feedEntryBbox);
		//category entry for crs (from mb_metadata) 5.1.17
		/*<!-- CRSs in which the pre-defined Dataset is available --> <category term="EPSG:25832" scheme="http://www.opengis.net/def/crs/" label="EPSG/0/25832"/> <category term="EPSG:4258" scheme="http://www.opengis.net/def/crs/" label="EPSG/0/4258"/>*/
		$feedEntryCategory = $feedDoc->createElement("category");
		$feedEntryCategory->setAttribute("term", "http://www.opengis.net/def/crs/EPSG/".$epsgId);
		$feedEntryCategory->setAttribute("label", "EPSG/0/".$epsgId);
		$feedEntry->appendChild($feedEntryCategory);
		//<!-- INSPIRE Spatial Object Types contained in the pre-defined dataset -->
		//<category term="Watercourse" scheme="http://inspire-registry.jrc.ec.europa.eu/registers/FCD/" label="Watercourse" xml:lang="en"/>
		//only applicable for inspire conformant datasets!
		//Generate List of inspire themes of the given layer!
		/*$sql = "SELECT inspire_category.inspire_category_id, inspire_category.inspire_category_code_en FROM inspire_category, layer_inspire_category WHERE layer_inspire_category.fkey_layer_id=$1 AND layer_inspire_category.fkey_inspire_category_id=inspire_category.inspire_category_id";
		$v = array((integer)$mapbenderMetadata['layer_id']);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		while ($row = db_fetch_array($res)) {
			//part for the name of the inspire category
			$feedEntryCategory = $feedDoc->createElement("category");
			$feedEntryCategory->setAttribute("term", $row['inspire_category_code_en']);
			$feedEntryCategory->setAttribute("scheme", "http://www.eionet.europa.eu/gemet/theme_concepts?langcode=en&ns=5&th=".$row['inspire_category_id']);
			$feedEntryCategory->setAttribute("label", $row['inspire_category_code_en']);
			$feedEntryCategory->setAttribute("xml:lang", "en");
			$feedEntry->appendChild($feedEntryCategory);
		}
		*/
		/*$feedEntryCategory = $feedDoc->createElement("category");
		$feedEntryCategory->setAttribute("term", "Watercourse");
		$feedEntryCategory->setAttribute("scheme", "http://inspire-registry.jrc.ec.europa.eu/registers/FCD/");
		$feedEntryCategory->setAttribute("label", "Watercourse");
		$feedEntryCategory->setAttribute("xml:lang", "en");
		$feedEntry->appendChild($feedEntryCategory);
		//<category term="StandingWater" scheme="http://inspire-registry.jrc.ec.europa.eu/registers/FCD/" label="Standing Water" xml:lang="en"/>
		$feedEntryCategory = $feedDoc->createElement("category");
		$feedEntryCategory->setAttribute("term", "StandingWater");
		$feedEntryCategory->setAttribute("scheme", "http://inspire-registry.jrc.ec.europa.eu/registers/FCD/");
		$feedEntryCategory->setAttribute("label", "Standing Water");
		$feedEntryCategory->setAttribute("xml:lang", "en");
		$feedEntry->appendChild($feedEntryCategory);*/
	//</entry>
		$feed->appendChild($feedEntry);
		//duplicate feed entry for other formats if one is given
		if ($type == 'DATASET' && $generateFrom == 'wfs' && count($mapbenderMetadata[$i]->output_formats) > 1 && strtoupper($mapbenderMetadata[$i]->geometry_field_name[0] !== "SHAPE")) {
			for ($j=1; $j < count($mapbenderMetadata[$i]->output_formats); $j++) {
				$feedEntryCopy = $feedEntry;
				$feedEntryCopyXml = $feedDoc->saveXML($feedEntry);
				//exchange formats
				$feedEntryCopyXml = str_replace($mapbenderMetadata[$i]->output_formats[0],$mapbenderMetadata[$i]->output_formats[$j],$feedEntryCopyXml);
				$feedEntryCopyXml = str_replace(rawurlencode($mapbenderMetadata[$i]->output_formats[0]),rawurlencode($mapbenderMetadata[$i]->output_formats[$j]),$feedEntryCopyXml);
				$feedEntryCopyXml = str_replace("application/gml+xml", $mapbenderMetadata[$i]->output_formats[$j], $feedEntryCopyXml);
				$feedEntryCopyXmlDOM = $feedDoc->createDocumentFragment();
				$feedEntryCopyXmlDOM->appendXML($feedEntryCopyXml);
				$feed->appendChild($feedEntryCopyXmlDOM);
			}
		}
	}
	//}
	//store feed to variable:
	//atom_feed_{mdid}_{type}_{generateFrom}_{resource_id}
	if ($cache->isActive) {
		//delete old variable first - cause the timestamp will remain the old!
		if ($cache->cachedVariableExists($atomFeedKey)) {
			$cache->cachedVariableDelete($atomFeedKey);
			//$e = new mb_exception("mod_inspireDownloadFeed.php: Delete old atom feed in cache!");
		}
		$cache->cachedVariableAdd($atomFeedKey,$feedDoc->saveXML());
		//$e = new mb_exception("mod_inspireDownloadFeed.php: Save atom feed to apc cache!");
	}
	return $feedDoc->saveXML();
	} //loop if no cached variable is available	
}


//function to give away the xml data
function pushOpenSearch($feedDoc, $recordId, $generateFrom) {
	header("Content-type: application/opensearchdescription+xml; charset=UTF-8");
	$xml = generateOpenSearchDescription($feedDoc, $recordId, $generateFrom);
	echo $xml;
	die();
}

//function to give away the xml data
function pushFeed($feedDoc, $recordId, $generateFrom) {
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	$xml = generateFeed($feedDoc, $recordId, $generateFrom);
	echo $xml;
	die();
}

//parses box2d string from postgis to bbox array [minx, miny, maxx, maxy]
function parseBox2d($box2d) {
	//"BOX(6.9213399887085 50.1331939697266,6.93241024017334 50.138801574707)"
	//delete BOX( and ), replace , with blank
	$bbox = str_replace(","," ",str_replace(")","",str_replace("BOX(", "", $box2d)));
	//explode with blank
	$bbox = explode(" ",$bbox);
	return $bbox;
}

function transformBbox($oldBbox, $fromCRS, $toCRS, $metadataUuid = false ,$polygonFilter = false) {
	//Transform the given BBOX to $toCRS
	$arrayBbox = explode(',',$oldBbox);
	if ($metadataUuid != false && $polygonFilter != false) {
		$sql = "select asewkt(transform(GeometryFromText ( 'LINESTRING ( ".$arrayBbox[0]." ".$arrayBbox[1].",".$arrayBbox[2]." ".$arrayBbox[3]." )', $fromCRS ),".intval($toCRS).")) as bbox, 
CASE 
WHEN st_intersects((select geography(bounding_geom) from mb_metadata where uuid = '".$metadataUuid."'),transform(GeometryFromText ( 'POLYGON (( ".$arrayBbox[0]." ".$arrayBbox[1].",".$arrayBbox[2]." ".$arrayBbox[1].",".$arrayBbox[2]." ".$arrayBbox[3].",".$arrayBbox[0]." ".$arrayBbox[3].",".$arrayBbox[0]." ".$arrayBbox[1]."))', $fromCRS ),".intval($toCRS)." )) = true THEN true
ELSE false
END as inside;";
		$res = db_query($sql,$v,$t);
		$row = db_fetch_assoc($res);
		$textBbox = $row['bbox'];
		$inside = $row['inside'];
		if ($inside == "f") {
			return false;
		} else {	
			$pattern = '~LINESTRING\((.*)\)~i';
			preg_match($pattern, $textBbox, $subpattern);
			$newBbox = str_replace(" ", ",", $subpattern[1]);
			return $newBbox;
		} 
	} else {
		$sql = "select asewkt(transform(GeometryFromText ( 'LINESTRING ( ".$arrayBbox[0]." ".$arrayBbox[1].",".$arrayBbox[2]." ".$arrayBbox[3]." )', $fromCRS ),".intval($toCRS)."))";
		$res = db_query($sql,$v,$t);
		$textBbox = db_fetch_row($res);
		$pattern = '~LINESTRING\((.*)\)~i';
		preg_match($pattern, $textBbox[0], $subpattern);
		$newBbox = str_replace(" ", ",", $subpattern[1]);
		return $newBbox;
	}
}

function transformMultipolygon($multiPolygonSql, $fromCRS, $toCRS, $metadataUuid = false ,$polygonFilter = false) {
    //returns array of bboxes
    //Transform the given multipolygon to $toCRS
    //flip coordinates, cause georss needs north/east!!!!!
    if ($metadataUuid != false && $polygonFilter != false) {
        //https://gis.stackexchange.com/questions/396367/postgis-find-outers-and-inners-inside-multipolygon-geometries
        $sql = "SELECT identifier, (dumped).geom AS poly, ((dumped).path)[1] AS path_poly, st_box2d(st_flipcoordinates((dumped).geom)) as wgs84bbox FROM (";
        $sql .= "SELECT (ST_Dump (p_geom)) AS dumped, identifier FROM (SELECT 1::integer AS identifier, ST_TRANSFORM(";
        $sql .= $multiPolygonSql;
        $sql .= ", " . intval($toCRS). ")";
        $sql .= " AS p_geom) AS b) AS c where ST_INTERSECTS((dumped).geom, (select bounding_geom from mb_metadata where uuid = '".$metadataUuid."'))";
      
        $res = db_query($sql);
        $wgs84bboxArray = array();
        while ($row = db_fetch_array($res)) {
            $wgs84bboxArray[intval($row['path_poly']) - 1] = implode(",", parseBox2d($row['wgs84bbox']));
        }
        return $wgs84bboxArray;
        
    } else {
        $sql = "SELECT identifier, (dumped).geom AS poly, ((dumped).path)[1] AS path_poly, st_box2d(st_flipcoordinates((dumped).geom)) as wgs84bbox FROM (";
        $sql .= "SELECT (ST_Dump (p_geom)) AS dumped, identifier FROM (SELECT 1::integer AS identifier, ST_TRANSFORM(";
        $sql .= $multiPolygonSql;
        $sql .= ", " . intval($toCRS). ")";
        $sql .= " AS p_geom) AS b) AS c";//where ST_INTERSECTS((dumped).geom, (select bounding_geom from mb_metadata where metadata_id=" . $metadataUuid . "))";
     
        $res = db_query($sql);
        $wgs84bboxArray = array();
        while ($row = db_fetch_array($res)) {
            $wgs84bboxArray[] = implode(",", parseBox2d($row['wgs84bbox']));
        }
        return $wgs84bboxArray;

    }
}


function fillMapbenderMetadata($dbResult, $generateFrom) {
	//function increments $indexMapbenderMetadata !!!
	global $mapbenderMetadata, $indexMapbenderMetadata, $admin, $mapbenderPath;
	//echo "<error>fill begins</error>";
	if ($generateFrom == 'metadata' || $generateFrom == 'remotelist') {
		$row = db_fetch_assoc($dbResult);
		//to generate an atom feed from mb_metadata there must be some information avaiable, that is normally used from service metadata
		//owner, group, bbox, ... - the mb_metadata table have to be filled with the geometry from the layer/featuretype - of which it has been coupled with
		$mapbenderMetadata[$indexMapbenderMetadata]->origin = $row['origin']; 
		if (isset($row['bbox2d']) && $row['bbox2d'] != '') {
				$bbox = $row['bbox2d'];
				$mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox = explode(',',$bbox);
				$mapbenderMetadata[$indexMapbenderMetadata]->minx = $mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox[0];
				$mapbenderMetadata[$indexMapbenderMetadata]->miny = $mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox[1];
				$mapbenderMetadata[$indexMapbenderMetadata]->maxx = $mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox[2];
				$mapbenderMetadata[$indexMapbenderMetadata]->maxy = $mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox[3];
		}
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_id = $row['metadata_id']; 
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_ref_system = $row['metadata_ref_system']; 		
		$mapbenderMetadata[$indexMapbenderMetadata]->md_owner = $row['fkey_mb_user_id'];
		$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $row['datasetid'];
		$mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace = $row['datasetid_codespace'];
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid = $row['metadata_uuid'];
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_title = $row['metadata_title'];
		//$e = new mb_exception("title: ".$row['metadata_title']);
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_abstract = $row['metadata_abstract'];
		$mapbenderMetadata[$indexMapbenderMetadata]->accessconstraints = $row['accessconstraints'];//TODO: Let metadata get this from service when created
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid = $row['metadata_uuid'];
		$mapbenderMetadata[$indexMapbenderMetadata]->spatial_res_type = $row['spatial_res_type'];
		$mapbenderMetadata[$indexMapbenderMetadata]->spatial_res_value = $row['spatial_res_value'];
		$mapbenderMetadata[$indexMapbenderMetadata]->metadata_ref_system = $row['metadata_ref_system'];
		$mapbenderMetadata[$indexMapbenderMetadata]->format = $row['format'];
		$mapbenderMetadata[$indexMapbenderMetadata]->datalinks = $row['datalinks'];
		$mapbenderMetadata[$indexMapbenderMetadata]->transfer_size = $row['transfer_size'];
//		$mapbenderMetadata[$indexMapbenderMetadata]->datalink_format = $row['datalink_format'];
		$mapbenderMetadata[$indexMapbenderMetadata]->format = $row['format'];
		$mapbenderMetadata[$indexMapbenderMetadata]->md_timestamp = $row['md_timestamp'];
		//check if codespace was given in metadata or it must be generated from uuid and default codespace
		/*if (($mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace)) or ($mapbenderMetadata[$indexMapbenderMetadata]->datasetid == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid))) {
			//generate one:
			$mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace = METADATA_DEFAULT_CODESPACE;
			$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid;
		}*/
		if ($mapbenderMetadata[$indexMapbenderMetadata]->datasetid == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid)) {
			//generate one:
			$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid;
		}
		//$e = new mb_exception("test");
		//overwrite some elements if the feed should be generated from metadata itself: access constraints, use limitations, ...
		if ($generateFrom == "metadata" || $generateFrom == "remotelist") {
			//get metadata from metadata proxy by uuid
			//http://www.geoportal.rlp.de/mapbender/php/mod_iso19139ToHtml.php?url=http%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D2b009ae4-aa3e-ff21-870b-49846d9561b2
			$iso19139 = new iso19139();
			
			$metadata = $iso19139->createFromUrl($mapbenderPath."php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid);
			//$e = new mb_exception($mapbenderPath."php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid);
			if ($metadata->fees == "" || empty($metadata->fees)) {
				//$e = new mb_exception("fees from metadata: ".$metadata->fees);
				$mapbenderMetadata[$indexMapbenderMetadata]->accessconstraints = "No information about conditions/fees found in original metadata";
			} else {
				$mapbenderMetadata[$indexMapbenderMetadata]->accessconstraints = $metadata->fees;
			}
			if ($metadata->ressourceContactEmail == "" || empty($metadata->ressourceContactEmail)) {
				$mapbenderMetadata[$indexMapbenderMetadata]->ressource_contact_email = "dummy@test.org";
			} else {
				$mapbenderMetadata[$indexMapbenderMetadata]->ressource_contact_email = $metadata->ressourceContactEmail;
			}
			if ($metadata->ressourceResponsibleParty == "" || empty($metadata->ressourceResponsibleParty)) {
				$mapbenderMetadata[$indexMapbenderMetadata]->ressource_responsible_party = "dummy organisation";
			} else {
				$mapbenderMetadata[$indexMapbenderMetadata]->ressource_responsible_party = $metadata->ressourceResponsibleParty;
			}
			//extract relevant fields 
			//overwrite values
		}	
	} else {
		while ($row = db_fetch_assoc($dbResult)) {
			//get relevant information 
			//echo "<error>".$indexMapbenderMetadata."</error>";
			if ($row['inspire_download'] == '1') {
				$mapbenderMetadata[$indexMapbenderMetadata]->origin = $row['origin']; 
				$mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox = $row['latlonbbox']; 
				$mapbenderMetadata[$indexMapbenderMetadata]->datalink_id = $row['datalink_id']; 
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_id = $row['metadata_id']; 
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_ref_system = $row['metadata_ref_system']; 		
				$mapbenderMetadata[$indexMapbenderMetadata]->fkey_mb_group_id = $row['fkey_mb_group_id'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_owner = $row['wms_owner'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_owner = $row['wfs_owner'];
				$mapbenderMetadata[$indexMapbenderMetadata]->fkey_wms_id = $row['fkey_wms_id'];
				$mapbenderMetadata[$indexMapbenderMetadata]->layer_id = $row['layer_id'];
				$mapbenderMetadata[$indexMapbenderMetadata]->layer_name = $row['layer_name'];
				$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $row['datasetid'];
				$mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace = $row['datasetid_codespace'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid = $row['metadata_uuid'];
				$mapbenderMetadata[$indexMapbenderMetadata]->minx = $row['minx'];
				$mapbenderMetadata[$indexMapbenderMetadata]->miny = $row['miny'];
				$mapbenderMetadata[$indexMapbenderMetadata]->maxx = $row['maxx'];
				$mapbenderMetadata[$indexMapbenderMetadata]->maxy = $row['maxy'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_title = $row['metadata_title'];
				$mapbenderMetadata[$indexMapbenderMetadata]->layer_title = $row['layer_title'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_abstract = $row['metadata_abstract'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_bbox = $row['metadata_bbox'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_polygon = $row['polygon'];
				$mapbenderMetadata[$indexMapbenderMetadata]->layer_abstract = $row['layer_abstract'];
				$mapbenderMetadata[$indexMapbenderMetadata]->accessconstraints = $row['accessconstraints'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid = $row['metadata_uuid'];
				$mapbenderMetadata[$indexMapbenderMetadata]->spatial_res_type = $row['spatial_res_type'];
				$mapbenderMetadata[$indexMapbenderMetadata]->spatial_res_value = $row['spatial_res_value'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_ref_system = $row['metadata_ref_system'];
				$mapbenderMetadata[$indexMapbenderMetadata]->format = $row['format'];
				$mapbenderMetadata[$indexMapbenderMetadata]->datalink_url = $row['datalink_url'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_getmap = $row['wms_getmap'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_owsproxy = $row['wms_owsproxy'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_version = $row['wms_version'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_max_imagesize = $row['wms_max_imagesize'];
				$mapbenderMetadata[$indexMapbenderMetadata]->layer_name = $row['layer_name'];
				$mapbenderMetadata[$indexMapbenderMetadata]->datalink_format = $row['datalink_format'];
				$mapbenderMetadata[$indexMapbenderMetadata]->metadata_ref_system = $row['metadata_ref_system'];
				$mapbenderMetadata[$indexMapbenderMetadata]->format = $row['format'];
				$mapbenderMetadata[$indexMapbenderMetadata]->featuretype_name = $row['featuretype_name'];
				$mapbenderMetadata[$indexMapbenderMetadata]->featuretype_srs = $row['featuretype_srs'];
				$mapbenderMetadata[$indexMapbenderMetadata]->featuretype_title = $row['featuretype_title'];
				$mapbenderMetadata[$indexMapbenderMetadata]->featuretype_id = $row['featuretype_id'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_title = $row['wfs_title'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_id = $row['wfs_id'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_abstract = $row['wfs_abstract'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_getfeature = $admin->checkUrl($row['wfs_getfeature']);
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_getcapabilities = $row['wfs_getcapabilities'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_describefeaturetype = $row['wfs_describefeaturetype'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_version = $row['wfs_version'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_max_features = $row['wfs_max_features'];

				$mapbenderMetadata[$indexMapbenderMetadata]->md_timestamp = $row['md_timestamp'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wms_timestamp = $row['wms_timestamp'];
				$mapbenderMetadata[$indexMapbenderMetadata]->wfs_timestamp = $row['wfs_timestamp'];

				//$mapbenderMetadata[$indexMapbenderMetadata]->format = $row['format'];
				//check if codespace was given in metadata or it must be generated from uuid and default codespace
				/*if (($mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace)) or ($mapbenderMetadata[$indexMapbenderMetadata]->datasetid == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid))) {
					//generate one:
					$mapbenderMetadata[$indexMapbenderMetadata]->datasetid_codespace = METADATA_DEFAULT_CODESPACE;
					$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid;
				}*/
				if ($mapbenderMetadata[$indexMapbenderMetadata]->datasetid == '' or !isset($mapbenderMetadata[$indexMapbenderMetadata]->datasetid)) {
					//generate one:
					$mapbenderMetadata[$indexMapbenderMetadata]->datasetid = $mapbenderMetadata[$indexMapbenderMetadata]->metadata_uuid;
				}
				if ($generateFrom == "wfs" or $mapbenderMetadata[$indexMapbenderMetadata]->origin == "wfs") {
					$latlonbbox = explode(",",$mapbenderMetadata[$indexMapbenderMetadata]->latlonbbox);
					$mapbenderMetadata[$indexMapbenderMetadata]->minx = $latlonbbox[0];
					$mapbenderMetadata[$indexMapbenderMetadata]->miny = $latlonbbox[1];
					$mapbenderMetadata[$indexMapbenderMetadata]->maxx = $latlonbbox[2];
					$mapbenderMetadata[$indexMapbenderMetadata]->maxy = $latlonbbox[3];
					//do a special select to get all outputformats from database
					$mapbenderMetadata[$indexMapbenderMetadata]->output_formats = array();
					//$e = new mb_exception("php/mod_inspireDownloadFeed.php: owfsId: ".$mapbenderMetadata[$indexMapbenderMetadata]->wfs_id);
					$sql = "SELECT output_format from wfs_output_formats WHERE fkey_wfs_id = $1 UNION SELECT output_format FROM wfs_featuretype_output_formats WHERE fkey_featuretype_id = $2";			
					$v = array($mapbenderMetadata[$indexMapbenderMetadata]->wfs_id, $mapbenderMetadata[$indexMapbenderMetadata]->featuretype_id);
					$t = array('i', 'i');
					$res = db_prep_query($sql,$v,$t);
					while ($row = db_fetch_array($res)) {
						$mapbenderMetadata[$indexMapbenderMetadata]->output_formats[] = $row['output_format'];
						//$e = new mb_exception("php/mod_inspireDownloadFeed.php: output_format for wfs: ".$row['output_format']);
					}
					if (count($mapbenderMetadata[$indexMapbenderMetadata]->output_formats) < 1) {
						//set default output format to gml2 TODO - check if senseful
						$mapbenderMetadata[$indexMapbenderMetadata]->output_formats[0] = "text/xml; subtype=gml/2.1.2";
					}
					$mapbenderMetadata[$indexMapbenderMetadata]->output_formats = array_unique($mapbenderMetadata[$indexMapbenderMetadata]->output_formats);
					//get geometry field name from featuretype information out of mapbender database
					$sql = "SELECT element_name, element_type from wfs_element WHERE fkey_featuretype_id = $1";			
					$v = array($mapbenderMetadata[$indexMapbenderMetadata]->featuretype_id);
					$t = array('i');
					$res = db_prep_query($sql,$v,$t);
					//pull first element with type is string like "PropertyType"
					$geometryElements = array("GeometryPropertyType","MultiSurfacePropertyType","GeometryPropertyType","CurvePropertyType","PolygonPropertyType","LineStringPropertyType","PointPropertyType","MultiPolygonPropertyType","MultiLineStringPropertyType","MultiPointPropertyType","SurfacePropertyType");
					while ($row = db_fetch_array($res)) {
						//$e = new mb_exception("php/mod_inspireDownloadFeed.php: test element_type: ".$row['element_type']);
						if (in_array($row['element_type'], $geometryElements)) {
							$mapbenderMetadata[$indexMapbenderMetadata]->geometry_field_name[] = $row['element_name'];
							//$e = new mb_exception("php/mod_inspireDownloadFeed.php: element_name of geometry field (type name like PropertyName) found: ".$row['element_name']);
							break;
						}
					}
					if (count($mapbenderMetadata[$indexMapbenderMetadata]->geometry_field_name) < 1) {
						$mapbenderMetadata[$indexMapbenderMetadata]->geometry_field_name[0] = "the_geom";
					}
				}
				//overwrite mapbenderMetadata->minx ... which came from layer/featuretype metadata with bbox of metadata itself, if given
				if (isset($mapbenderMetadata[$indexMapbenderMetadata]->metadata_bbox) && $mapbenderMetadata[$indexMapbenderMetadata]->metadata_bbox !== "") {
					$bbox = explode(",", $mapbenderMetadata[$indexMapbenderMetadata]->metadata_bbox);
					$mapbenderMetadata[$indexMapbenderMetadata]->minx = $bbox[0];
					$mapbenderMetadata[$indexMapbenderMetadata]->miny = $bbox[1];
					$mapbenderMetadata[$indexMapbenderMetadata]->maxx = $bbox[2];
					$mapbenderMetadata[$indexMapbenderMetadata]->maxy = $bbox[3];	
				}
				$indexMapbenderMetadata++;
			}
		}
	}
}

if ($openSearch) {
	readInfoFromDatabase($recordId, $generateFrom);
	//generate rss to get number of tiles!
	//generateFeed($feedDoc, $recordId, $generateFrom);//TODO: maybe call feed from cache first - we have to parse the feed - it is mor simple than generate it !!!!
	answerOpenSearchRequest($feedDoc, $recordId, $generateFrom);
	
} else {
	if ($getOpenSearch) {
		readInfoFromDatabase($recordId, $generateFrom);
		pushOpenSearch($feedDoc, $recordId, $generateFrom);
	} else {
		readInfoFromDatabase($recordId, $generateFrom);
		pushFeed($feedDoc, $recordId, $generateFrom); //throw it out to world!
	}
}

?>

