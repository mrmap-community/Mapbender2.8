<?php
# $Id: wfs.php 
# http://www.mapbender.org/index.php/wfs.php
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
//http://localhost/mapbender_trunk/php/wfs.php?featuretype_id=21018&INSPIRE=1&REQUEST=GetCapabilities&VERSION=1.0.0&SERVICE=wfs&withChilds=1
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_layer_monitor.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__) . "/../classes/class_owsMetadataUrl.php");
//
$admin = new administration();
//
// make all parameters available as upper case
//
foreach($_GET as $key => $val) {
	$_GET[strtoupper($key)] = $val;
}

$requestType = $_GET["REQUEST"];
$version = $_GET["VERSION"];
$service = strtoupper($_GET["SERVICE"]);

//check for integer value WFS_ID
if (isset($_REQUEST["WFS_ID"]) & $_REQUEST["WFS_ID"] != "") {
    //validate integer 
    $testMatch = $_REQUEST["WFS_ID"];
    //give max 99 entries - more will be to slow
    $pattern = '/[0-9]*+/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo 'Parameter <b>WFS_ID</b> is not valid integer.<br/>';
        die();
    }
    $wfsId = $testMatch;
    $testMatch = NULL;
}

//check for integer value FEATURETYPE_ID
if (isset($_REQUEST["FEATURETYPE_ID"]) & $_REQUEST["FEATURETYPE_ID"] != "") {
    //validate integer
    $testMatch = $_REQUEST["FEATURETYPE_ID"];
    //give max 99 entries - more will be to slow
    $pattern = '/[0-9]*+/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo 'Parameter <b>FEATURETYPE_ID</b> is not valid integer.<br/>';
        die();
    }
    $featuretypeId = $testMatch;
    $testMatch = NULL;
}

$updateSequence = intval($_GET["UPDATESEQUENCE"]);
$inspire = $_GET["INSPIRE"];
$withChilds = false;

if (isset($_REQUEST["withChilds"]) && $_REQUEST["withChilds"] === "1") {
	$withChilds = true;
}
$sessionId = $_GET[strtoupper(session_name())];
//if session id not set, set a dummy id!
if (!isset($sessionId) || $sessionId =="") {
	$sessionId = "00000000000000000000000000000000";
	}
if (isset($inspire) && $inspire === 1 ) {
	$inspire = true;
}

if (isset($_SERVER["HTTPS"])){
	$urlPrefix = "https://";
} else {
	$urlPrefix = "http://";
}

if (DEFINED("MAPBENDER_PATH") && MAPBENDER_PATH !== "") {
	$mapbenderMetadataUrl = MAPBENDER_PATH."/php/mod_showMetadata.php?resource=featuretype&id=";
	$inspireServiceMetadataUrl =  MAPBENDER_PATH."/php/mod_featuretypeISOMetadata.php?SERVICE=WFS&outputFormat=iso19139&Id=";
	$mapbenderMetadataUrlUrl = MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=";
} else {
	$mapbenderMetadataUrl = $_SERVER['HTTP_HOST']."/mapbender/php/mod_showMetadata.php?resource=featuretype&id=";
	$inspireServiceMetadataUrl =  $_SERVER['HTTP_HOST']."/mapbender/php/mod_featuretypeISOMetadata.php?SERVICE=WFS&outputFormat=iso19139&Id=";
	$mapbenderMetadataUrlUrl = $_SERVER['HTTP_HOST']."/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=";
	$mapbenderMetadataUrl = $urlPrefix.$mapbenderMetadataUrl;
	$inspireServiceMetadataUrl = $urlPrefix.$inspireServiceMetadataUrl;
	$mapbenderMetadataUrlUrl = $urlPrefix.$mapbenderMetadataUrlUrl;
}

$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);

/**
 * Creates an XML Exception according to WMS 1.1.1
 * 
 * @return an XML String
 * @param $errorCode String
 * @param $errorMessage String
 */
function createExceptionXml ($errorCode, $errorMessage) {
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
	
	return $doc->saveXML();
}

//
// check if service param is set
//
if (!isset($service) || $service === "" || $service != "WFS") {
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	echo createExceptionXml("", "Parameter SERVICE invalid");
	die;
}

//
// check if request param is set
//
if (!isset($requestType) || $requestType === "" || ($service == "WFS" && $requestType != "GetCapabilities")) {
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	echo createExceptionXml("", "Parameter REQUEST invalid");
	die;
}

//
// check if version param is set
//
if (!isset($version) || $version === "" || ($service == "WFS" && !($version == "1.0.0" || $version == "1.1.0" || strpos($version, '2.0') == 0))) {
	// optional parameter, set to 1.0.0 if not set
	$version = "1.0.0";
}

//
// check if featuretype id is set
//
if (!isset($featuretypeId) || !is_numeric($featuretypeId)) {
    //check if WFS_ID is set instead
    if (isset($wfsId) || is_numeric($wfsId)) {
        $wfs_sql = "SELECT * FROM wfs WHERE wfs_id = $1 LIMIT 1";
        $v = array($wfsId);
        $t = array("i");
        $res_wfs_sql = db_prep_query($wfs_sql, $v, $t);
        $wfs_row = db_fetch_array($res_wfs_sql);
        if (!$wfs_row["wfs_id"]) {
            // TODO: create exception XML
            header("Content-type: application/xhtml+xml; charset=UTF-8");
            echo createExceptionXml("WFS does not exist", "Unknown wfs id ");
            die;
        } else {
            $featuretypeId = false;
        }
    } else {
	    // TO DO: create exception XML
	    header("Content-type: application/xhtml+xml; charset=UTF-8");
	    echo createExceptionXml("Featuretype or wfs not defined", "Unknown featuretype or wfs id ");
	    die;
    }
} else {
    $wfs_sql = "SELECT * FROM wfs AS w, wfs_featuretype AS f " .
        "where f.featuretype_id = $1 AND f.fkey_wfs_id = w.wfs_id LIMIT 1";
    $v = array($featuretypeId);
    $t = array("i");
    $res_wfs_sql = db_prep_query($wfs_sql, $v, $t);
    $wfs_row = db_fetch_array($res_wfs_sql);
    if (!$wfs_row["wfs_id"]) {
        // TODO: create exception XML
        header("Content-type: application/xhtml+xml; charset=UTF-8");
        echo createExceptionXml("Featuretype not defined", "Unknown featuretype id " . $featuretypeId);
        die;
    }
}

//Get Geometry Type if featuretype info was requested
if ($resource == 'featuretype') {
	$getTypeSql = "SELECT element_id, element_type from wfs_element WHERE fkey_featuretype_id = $1 AND element_type LIKE '%PropertyType';";
	$vgetType = array($resourceMetadata['contentid']);
	$tgetType = array('i');
	$resGetType = db_prep_query($getTypeSql,$vgetType,$tgetType);
	$featuretypeElements = db_fetch_array($resGetType);
	$resourceMetadata['featuretype_geomType'] = $featuretypeElements['element_type'];
}

//
// check if update sequence is valid
//
$updateSequenceDb = intval($wfs_row["wfs_timestamp"]);

if ($updateSequence) {
	if ($updateSequence > $updateSequenceDb) {
		// Exception: code=InvalidUpdateSequence
		header("Content-type: application/xhtml+xml; charset=UTF-8");
		echo createExceptionXml("InvalidUpdateSequence", "Invalid update sequence");
		die;
	}
	else if ($updateSequence == $updateSequenceDb) {
		// Exception: code=CurrentUpdateSequence
		header("Content-type: application/xhtml+xml; charset=UTF-8");
		echo createExceptionXml("CurrentUpdateSequence", "Current update sequence");
		die;
	}
}

// ---------------------------------------------------------------------------
//
// START TO CREATE CAPABILITIES DOC
// (return most recent Capabilities XML)
//
// ---------------------------------------------------------------------------

$doc = new DOMDocument('1.0');
$doc->encoding = 'UTF-8';
$doc->standalone = false;


if ($featuretypeId != false) {
    #Load existing XML from database
    $xml_sql = "SELECT w.wfs_getcapabilities_doc as doc,f.featuretype_name as fname FROM wfs AS w, wfs_featuretype AS f " .
    		"WHERE f.featuretype_id = $1 AND f.fkey_wfs_id = w.wfs_id;";
    $v = array($featuretypeId);
    $t = array("i");
    $res_xml_sql = db_prep_query($xml_sql, $v, $t);
    $xml_row = db_fetch_array($res_xml_sql);
} else {
    $xml_row = $wfs_row["wfs_getcapabilities_doc"];
}
//TODO: alter script to allow also wfs capabilities for service not only for featuretype
//echo $xml_row;
//die();

$doc->loadXML($xml_row["doc"]);
$xpath = new DOMXPath($doc);

if(strpos($version, '2.0') !== false) {
    $xpath->registerNamespace("wfs", "http://www.opengis.net/wfs/2.0");
} else {
    $xpath->registerNamespace("wfs", "http://www.opengis.net/wfs");
}
$xpath->registerNamespace("ows", "http://www.opengis.net/ows");
$xpath->registerNamespace("gml", "http://www.opengis.net/gml");
$xpath->registerNamespace("ogc", "http://www.opengis.net/ogc");
$xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
$xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
$xpath->registerNamespace("default", "http://www.opengis.net/wfs");

$elements = $xpath->query('/wfs:WFS_Capabilities');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("updateSequence", $wfs_row["wfs_timestamp"]);
	if ($inspire){
		$element->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
		$element->setAttribute("xmlns:inspire_dls", "http://inspire.ec.europa.eu/schemas/inspire_dls/1.0");
	}
}

//delete all unrequested featuretypes from capabilities
$featureTypeList = $xpath->query('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType');//as domnodelist

if($featuretypeId && $featureTypeList->length > 0){
    for ($i = 0; $i < $featureTypeList->length; $i++) {
        $temp = $featureTypeList->item($i);
	$childs = $temp->childNodes;
	foreach($childs as $child) {
	    if ($child->nodeName == "Name") {
	        if ($child->nodeValue !== $xml_row["fname"]) {
	            $child->parentNode->parentNode->removeChild($temp);
	        }
	    }
	}
    }
}

# switch URLs for OWSPROXY
//check if resource is freely available to anonymous user - which are all users who search thru metadata catalogues:
//for the inspire use case:
//url maybe owsproxy, if proxy is active and guest have right to use resource
//url maybe http_auth if proxy is active and guest is not authorized
//url maybe original if proxy is not active

//$e = new mb_exception($urlPrefix.$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);
$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"];
$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
//$e = new mb_exception($tmpOR);
$wfs_row['wfs_getcapabilities'] = $tmpOR;

if ($inspire) {
	$wfs_row['wfs_getcapabilities'] .= "&INSPIRE=1";
}
$e = new mb_notice($wfs_row['wfs_getcapabilities']);
$publicHasPermission=$admin->getWfsConfByPermission(PUBLIC_USER);
if ($wfs_row['wfs_owsproxy'] <> "" AND $wfs_row['wfs_owsproxy'] <> NULL) {
	if ($inspire) {
		if ($publicHasPermission) {
			//use owsproxy url
			$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/owsproxy/".$sessionId."/".$wfs_row["wfs_owsproxy"]."?";
			$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
			$wfs_row['wfs_describefeaturetype'] = $tmpOR;
			$wfs_row['wfs_getfeature'] = $tmpOR;
			$wfs_row['wfs_transaction'] = $tmpOR;
		} else {
			//use http_auth
			$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/http_auth/".$featuretypeId."?";
			$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
			$wfs_row['wfs_describefeaturetype'] = $tmpOR;
			$wfs_row['wfs_getfeature'] = $tmpOR;
			$wfs_row['wfs_transaction'] = $tmpOR;
		}
	} else {
	    //use owsproxy url
	    $tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/owsproxy/".$sessionId."/".$wfs_row["wfs_owsproxy"]."?";
	    $tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
	    $wfs_row['wfs_describefeaturetype'] = $tmpOR;
	    $wfs_row['wfs_getfeature'] = $tmpOR;
	    $wfs_row['wfs_transaction'] = $tmpOR;
	}
}

$elements = $xpath->query('//ows:OnlineResource');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_getcapabilities']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Post');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_getcapabilities']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Get');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_getcapabilities']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="DescribeFeatureType"]/ows:DCP/ows:HTTP/ows:Post');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_describefeaturetype']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="DescribeFeatureType"]/ows:DCP/ows:HTTP/ows:Get');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_describefeaturetype']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:DCP/ows:HTTP/ows:Post');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_getfeature']);
}

$elements = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:DCP/ows:HTTP/ows:Get');
if ($elements->length >= 1) {
	$element = $elements->item(0);
	$element->setAttribute("xlink:href", $wfs_row['wfs_getfeature']);
}

//get wfs id and owner of the wfs from database


//Add MetadataUrl entries from mapbender database
//Creating Metadata nodes
//read out all metadata entries for specific featuretype
$sql = <<<SQL

SELECT metadata_id, uuid, link, linktype, md_format, origin, datasetid, datasetid_codespace, md_proxy FROM mb_metadata 
INNER JOIN (SELECT * from ows_relation_metadata 
WHERE fkey_featuretype_id = $featuretypeId ) as relation ON 
mb_metadata.metadata_id = relation.fkey_metadata_id WHERE mb_metadata.origin IN ('capabilities','external','metador','upload')

SQL;
$res_metadata = db_query($sql);
$metadataUrl = array();
$linkType = array();
$format = array();
$datasetId = array();
//
$k = 0;
//infos about the registrating department, check first if a special metadata point of contact is defined in the service table
$departmentMetadata = $admin->getOrgaInfoFromRegistry("wfs", $wfs_row["wfs_id"], $wfs_row["wfs_owner"]);

while ($row_metadata = db_fetch_array($res_metadata)) {	
	$uniqueResourceIdentifierCodespace = $admin->getIdentifierCodespaceFromRegistry($departmentMetadata, $row_metadata);
	//First generate all dataset ids because they are needed in the inspire extensions
	if ($row_metadata["datasetid"] == '' || !isset($row_metadata["datasetid"])) {
		//this column is empty, if metador was used and the identifier for dataset id is equal to the identifier of the metadata fileidentifier
		$datasetId[$k] = $uniqueResourceIdentifierCodespace.$row_metadata["uuid"];
	} else {
		$datasetId[$k] = $uniqueResourceIdentifierCodespace.$row_metadata["datasetid"];
	}
	//push entries into xml structure	
	//check for kind of link - push the right one into the link field	
	switch ($row_metadata['origin']) {
		case 'capabilities':
			//check if md_proxy is set
			if ($row_metadata['md_proxy'] == 't' || $row_metadata['md_proxy'] == true) {
				$metadataUrl[$k] = $mapbenderMetadataUrlUrl.$row_metadata['uuid'];
			} else {
				$metadataUrl[$k] = $row_metadata['link'];
			}
			$linkType[$k] = $row_metadata['linktype'];
			$format[$k] = $row_metadata['md_format'];
		break;
		case 'external':
			//check if md_proxy is set
			if ($row_metadata['md_proxy'] == 't' || $row_metadata['md_proxy'] == true) {
				$metadataUrl[$k] = $mapbenderMetadataUrlUrl.$row_metadata['uuid'];
			} else {
				$metadataUrl[$k] = $row_metadata['link'];
			}
			$linkType[$k] = 'ISO19115:2003';
			$format[$k] = "text/xml";
		break;
		case 'upload':
			$metadataUrl[$k] = $mapbenderMetadataUrlUrl.$row_metadata['uuid'];
			$linkType[$k] = 'ISO19115:2003';
			$format[$k] = "text/xml";
		break;
		case 'metador':
			$metadataUrl[$k] = $mapbenderMetadataUrlUrl.$row_metadata['uuid'];
			$linkType[$k] = 'ISO19115:2003';
			$format[$k] = "text/xml";
		break;
		default:
			$metadataUrl[$k] = "Url not given - please check your registry!";
			$linkType[$k] = 'ISO19115:2003';
			$format[$k] = "text/xml";
		break;
	}
	//Add linkage to Capabilities
	$k++;
}
$k = 0;
if (is_array($metadataUrl) && count($metadataUrl) > 0) {
#$e = new mb_exception(gettype($metadataUrl)." - count - ".count($metadataUrl)." url[0]: ".$metadataUrl[0]);
#$e = new mb_exception($version);
#$e = new mb_exception($service);
	$metadataUrlObject = new OwsMetadataUrl();
	//$e = new mb_exception('version: '.$version);
	$metadata_part = $metadataUrlObject->getOwsRepresentation($metadataUrl, $linkType, $format, 'wfs', $version);
#$e = new mb_exception($metadata_part);
}

//insert metadata url elements into capabilities - either delete existing entries and recreate them or put them after ows:WGS84BoundingBox (WFS 2.0), wfs .....
//test for existing MetadataURL entries
if ($featuretypeId) {
	$metadataUrls = $xpath->query('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType/MetadataURL');
	$wgs84bboxs = $xpath->query('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType/ows:WGS84BoundingBox');
	if ($metadataUrls->length > 0) {
		if (isset($metadata_part) && $metadata_part !== '') {
			//load xml from constraint generator
			$metadataUrlDomObject = new DOMDocument();
			$metadataUrlDomObject->loadXML($metadata_part);
			$xpathMetadataUrl = new DOMXpath($metadataUrlDomObject);
			$metadataUrlNodeList = $xpathMetadataUrl->query('/mb:metadataurl/MetadataURL');
			//insert new MetadataURL entries before first old node
			for ($i = ($metadataUrlNodeList->length)-1; $i >= 0; $i--) {
				$metadataUrls->item(0)->parentNode->insertBefore($doc->importNode($metadataUrlNodeList->item($i), true), $metadataUrls->item(0));
			}
			//delete all old entries from original xml document 
			for ($i = 0; $i <  $metadataUrls->length; $i++) {
    					$temp = $metadataUrls->item($i); //avoid calling a function twice
    					$temp->parentNode->removeChild($temp);
			}
		}
	} else {
		if (isset($metadata_part) && $metadata_part !== '') {
			//load xml from constraint generator
			$metadataUrlDomObject = new DOMDocument();
			$metadataUrlDomObject->loadXML($metadata_part);
			$xpathMetadataUrl = new DOMXpath($metadataUrlDomObject);
			$metadataUrlNodeList = $xpathMetadataUrl->query('/mb:metadataurl/MetadataURL');
			//insert new MetadataURL entries after ows:WGS84BoundingBox for wfs 2.0.x
			for ($i = ($metadataUrlNodeList->length)-1; $i >= 0; $i--) {
				$wgs84bboxs->item(0)->parentNode->insertBefore($doc->importNode($metadataUrlNodeList->item($i), true), $wgs84bboxs->item(0)->nextSibling);
			}
		}
	}
}

################################################################
#INSPIRE
if ($inspire) {
	
	$refNodes = $xpath->query('//ows:OperationsMetadata');
	$refNode = $refNodes->item(0);
		
	#generating the vendor specific node
	$vendorSpecificCapabilities = $doc->createElement("ows:ExtendedCapabilities");
	$vendorSpecificCapabilities = $refNode->appendChild($vendorSpecificCapabilities);
	#generate inspire_dls:ExtendedCapabilities node
	$inspire_dls_ExtendedCapabilities = $doc->createElement("inspire_dls:ExtendedCapabilities");
	$inspire_dls_ExtendedCapabilities->setAttribute("xmlns:inspire_dls", "http://inspire.ec.europa.eu/schemas/inspire_dls/1.0");
	$inspire_dls_ExtendedCapabilities = $vendorSpecificCapabilities->appendChild($inspire_dls_ExtendedCapabilities);
	#generate inspire_dls: node
	#$inspire_dls_ExtendedCapabilities = $doc->createElement("inspire_dls:ExtendedCapabilities");
	#$inspire_dls_ExtendedCapabilities = $vendorSpecificCapabilities->appendChild($inspire_dls_ExtendedCapabilities);
	#MetadataUrl to inspire service metadata
	$inspire_common_MetadataUrl = $doc->createElement("inspire_common:MetadataUrl");
	/*<inspire_common:MetadataUrl xmlns:inspire_common="http://inspire.ec.europa.eu/schemas/common/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="inspire_common:resourceLocatorType">*/
	$inspire_common_MetadataUrl->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
	$inspire_common_MetadataUrl->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$inspire_common_MetadataUrl->setAttribute("xsi:type", "inspire_common:resourceLocatorType");
	$inspire_common_MetadataUrl = $inspire_dls_ExtendedCapabilities->appendChild($inspire_common_MetadataUrl);
	#URL
	$inspire_common_URL = $doc->createElement("inspire_common:URL");
	$inspire_common_URL->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");

	$inspire_common_URLText = $doc->createTextNode($inspireServiceMetadataUrl.$featuretypeId);
	$inspire_common_URL->appendChild($inspire_common_URLText);
	$inspire_common_URL = $inspire_common_MetadataUrl->appendChild($inspire_common_URL);
	#MediaType
	$inspire_common_MediaType = $doc->createElement("inspire_common:MediaType");
	$inspire_common_MediaType->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
	$inspire_common_MediaTypeText = $doc->createTextNode('application/vnd.iso.19139+xml');#from http://inspire.ec.europa.eu/schemas/inspire_dls/1.0/examples/WMS_Image2000GetCapabilities_InspireSchema.xml
	$inspire_common_MediaType->appendChild($inspire_common_MediaTypeText);
	$inspire_common_MediaType = $inspire_common_MetadataUrl->appendChild($inspire_common_MediaType);
	#Language Part
	#SupportedLanguages
	$inspire_common_SupportedLanguages = $doc->createElement("inspire_common:SupportedLanguages");
	$inspire_common_SupportedLanguages = $inspire_dls_ExtendedCapabilities->appendChild($inspire_common_SupportedLanguages);
	#DefaultLanguage
	$inspire_common_DefaultLanguage = $doc->createElement("inspire_common:DefaultLanguage");
	$inspire_common_DefaultLanguage = $inspire_common_SupportedLanguages->appendChild($inspire_common_DefaultLanguage);
	#Language
	$inspire_common_Language = $doc->createElement("inspire_common:Language");
	$inspire_common_LanguageText = $doc->createTextNode('ger');
	$inspire_common_Language->appendChild($inspire_common_LanguageText);
	$inspire_common_Language = $inspire_common_DefaultLanguage->appendChild($inspire_common_Language);
	#SupportedLanguage
	$inspire_common_SupportedLanguage = $doc->createElement("inspire_common:SupportedLanguage");
	$inspire_common_SupportedLanguage = $inspire_common_SupportedLanguages->appendChild($inspire_common_SupportedLanguage);
	#Language
	$inspire_common_Language = $doc->createElement("inspire_common:Language");
	$inspire_common_LanguageText = $doc->createTextNode('ger');
	$inspire_common_Language->appendChild($inspire_common_LanguageText);
	$inspire_common_Language = $inspire_common_SupportedLanguage->appendChild($inspire_common_Language);
	#ResponseLanguage
	$inspire_common_ResponseLanguage = $doc->createElement("inspire_common:ResponseLanguage");
	$inspire_common_ResponseLanguage = $inspire_dls_ExtendedCapabilities->appendChild($inspire_common_ResponseLanguage);
	#Language
	$inspire_common_Language = $doc->createElement("inspire_common:Language");
	$inspire_common_LanguageText = $doc->createTextNode('ger');
	$inspire_common_Language->appendChild($inspire_common_LanguageText);
	$inspire_common_Language = $inspire_common_ResponseLanguage->appendChild($inspire_common_Language);
	# add indentifier foreach existing metadata url entry - see above!
	/*
<ows:ExtendedCapabilities>
<inspire_dls:ExtendedCapabilities>
<!-- Dienste Metadaten -->
<!-- Sprachen -->
<!-- SpatialDatasetIdentifier -->
<inspire_dls:SpatialDataSetIdentifier>
<inspire_common:Code>DEBY_eea97fc0-b6bf-11e1-afa6-
0800200c9a66</inspire_common:Code>
<inspire_common:Namespace>http://www.geodaten.bayern.de</inspire_common:Namespac
e>
</inspire_dls:SpatialDataSetIdentifier>
</inspire_dls:ExtendedCapabilities>
</ows:ExtendedCapabilities>
	*/
	foreach ($datasetId as $singleDatasetId) {
		//$e = new mb_exception("datasetid: ".$singleDatasetId);
		//try to extract local identifier from complete unique resource identifier 
		//now try to check if a single slash is available and if the md_identifier is a url
		$parsedUrl = parse_url($singleDatasetId);
		if (($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') && strpos($parsedUrl['path'],'/') !== false) {
			$explodedUrl = explode('/', $singleDatasetId);
			$codeText = $explodedUrl[count($explodedUrl) - 1];
			$namespaceText = rtrim($singleDatasetId, $codeText);	
		} else {
			//check old way with # as separator
			if (strpos($singleDatasetId, '#') !== false && count(explode('#', $singleDatasetId) == 2) ) {
				$codeText = explode('#', $singleDatasetId)[1];
				$namespaceText = explode('#', $singleDatasetId)[0];
			} else {
				$codeText = $singleDatasetId;
				$namespaceText = "";
			}
		}
		$inspire_dls_SpatialDataSetIdentifier = $doc->createElement("inspire_dls:SpatialDataSetIdentifier");
		$inspire_common_Code = $doc->createElement("inspire_common:Code");
		$inspire_common_Namespace = $doc->createElement("inspire_common:Namespace");

		$inspire_common_CodeText = $doc->createTextNode($codeText);
		$inspire_common_NamespaceText = $doc->createTextNode($namespaceText);

		$inspire_common_Namespace->appendChild($inspire_common_NamespaceText);
		$inspire_common_Code->appendChild($inspire_common_CodeText);
		$inspire_dls_SpatialDataSetIdentifier->appendChild($inspire_common_Code);
		$inspire_dls_SpatialDataSetIdentifier->appendChild($inspire_common_Namespace);
		$inspire_dls_ExtendedCapabilities->appendChild($inspire_dls_SpatialDataSetIdentifier);
	}
}

header("Content-type: application/xhtml+xml; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
echo $doc->saveXml();

?>
