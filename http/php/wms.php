<?php
# $Id: wms.php 
# http://www.mapbender.org/index.php/wms.php
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
//http://localhost/mapbender_trunk/php/wms.php?layer_id=21018&INSPIRE=1&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=wms&withChilds=1
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_layer_monitor.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
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
$layerId = $_GET["LAYER_ID"];
$updateSequence = intval($_GET["UPDATESEQUENCE"]);
$inspire = $_GET["INSPIRE"];
$validateSchema = true;
if (isset($_GET["VALIDATESCHEMA"]) && $_GET["VALIDATESCHEMA"] == 0) {
	$validateSchema = false;
}

$withChilds = false;
//default url prefix
$urlPrefix = "http://";

//switch prefix from request
if (isset($_SERVER["HTTPS"])){
	$urlPrefix = "https://";	
}

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
if (DEFINED("MAPBENDER_PATH") && MAPBENDER_PATH !== "") {
	$mapbenderMetadaUrl = MAPBENDER_PATH."/php/mod_showMetadata.php?resource=layer&id=";
	$inspireServiceMetadataUrl =  MAPBENDER_PATH."/php/mod_layerISOMetadata.php?SERVICE=WMS&outputFormat=iso19139&Id=";
	$mapbenderMetadataUrlUrl = MAPBENDER_PATH."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=";
} else {
	$mapbenderMetadaUrl = $_SERVER['HTTP_HOST']."/mapbender/php/mod_showMetadata.php?resource=layer&id=";
	$inspireServiceMetadataUrl =  $_SERVER['HTTP_HOST']."/mapbender/php/mod_layerISOMetadata.php?SERVICE=WMS&outputFormat=iso19139&Id=";
	$mapbenderMetadataUrlUrl = $_SERVER['HTTP_HOST']."/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=";
	$mapbenderMetadataUrl = $urlPrefix.$mapbenderMetadataUrl;
	$inspireServiceMetadataUrl = $urlPrefix.$inspireServiceMetadataUrl;
	$mapbenderMetadataUrlUrl = $urlPrefix.$mapbenderMetadataUrlUrl;
}

//http://www.geoportal.rlp.de/mapbender/php/mod_layerISOMetadata.php?SERVICE=WMS&outputFormat=iso19139&Id=24615

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
if (!isset($service) || $service === "" || $service != "WMS") {
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	echo createExceptionXml("", "Parameter SERVICE invalid");
	die;
}

//
// check if request param is set
//
if (!isset($requestType) || $requestType === "" || ($service == "WMS" && $requestType != "GetCapabilities")) {
	//header("Content-type: application/xhtml+xml; charset=UTF-8");
	header("Content-type: application/vnd.ogc.wms_xml; charset=UTF-8");
	echo createExceptionXml("", "Parameter REQUEST invalid");
	die;
}

//
// check if version param is set
//
if (!isset($version) || $version === "" || ($service == "WMS" && $version != "1.1.1")) {
	// optional parameter, set to 1.1.1 if not set
	$version = "1.1.1";
}

//
// check if layer id is set
//
if (!isset($layerId) || !is_numeric($layerId)) {
	// TO DO: create exception XML
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	echo createExceptionXml("Layer not defined", "Unknown layer id " . $layerId);
	die;
}

//
// check if layer is stored in database
//
$wms_sql = "SELECT * FROM wms AS w, layer AS l " . 
	"where l.layer_id = $1 AND l.fkey_wms_id = w.wms_id LIMIT 1";
$v = array($layerId);
$t = array("i");
$res_wms_sql = db_prep_query($wms_sql, $v, $t);
$wms_row = db_fetch_array($res_wms_sql);

if (!$wms_row["wms_id"]) {
	// TODO: create exception XML
	header("Content-type: application/xhtml+xml; charset=UTF-8");
	echo createExceptionXml("Layer not defined", "Unknown layer id " . $layerId);
	die;
}
$AuthorityName = "defaultauthority";
/*$metadata_codespace_sql = "SELECT metadata_id, datasetid, datasetid_codespace FROM mb_metadata WHERE metadata_id in (SELECT fkey_metadata_id FROM layer INNER JOIN ows_relation_metadata ON layer.layer_id = ows_relation_metadata.fkey_layer_id WHERE fkey_wms_id = $1)";
$v = array($wms_row["wms_id"]);
$t = array("i");
$res_metadata_codespace_sql = db_prep_query($metadata_codespace_sql, $v, $t);

$metadataNameSpaceArray = array();
while ($row_metadata_codespace_sql = db_fetch_array($res_metadata_codespace_sql)) {
    if (isset($row_metadata_codespace_sql['datasetid_codespace']) && $row_metadata_codespace_sql['datasetid_codespace'] !== '') {
        $metadataNameSpaceArray[] = $row_metadata_codespace_sql['datasetid_codespace'];
    }
}
$metadataNameSpaceArray = array_unique($metadataNameSpaceArray);
*/

//TODO: select all coupled metadata from table and make the namespace unique - one entry for each different namespace - AuthorityName will be name, name_1, name_2, name_3 for each found namespace !!!!!

//infos about the registrating department, check first if a special metadata point of contact is defined in the service table
/*$metadataContactGroup = $admin->getOrgaInfoFromRegistry("wms", $wms_row["wms_id"], $wms_row["wms_owner"]);

$AuthorityName = $metadataContactGroup["mb_group_name"];

//TODO: Problem - there is no single codespace if datasets have different ones !!!! - Find a generic solution  
//$uniqueResourceIdentifierCodespace = $admin->getIdentifierCodespaceFromRegistry($departmentMetadata, $row_metadata);

$metadataArray['datasetid_codespace'] = "";

$AuthorityUrlArray = array();
$AuthorityNameArray = array();
$countMetadataNameSpaceArray = 0;

foreach ($metadataNameSpaceArray as $metadataNameSpace) {
    $metadataArray['datasetid_codespace'] = $metadataNameSpace;
    $AuthorityUrlArray[] = $admin->getIdentifierCodespaceFromRegistry($metadataContactGroup, $metadataArray);
    if ($AuthorityName == '') {
	$AuthorityName = "defaultauthority";
    }
    if ($countMetadataNameSpaceArray > 0) {
	$AuthorityNameArray[] = $AuthorityName."_".$countMetadataNameSpaceArray;
    } else {
        $AuthorityNameArray[] = $AuthorityName;
    }
    $countMetadataNameSpaceArray++;
}

*/
//Get Geometry Type if featuretype info was requested
if ($resource == 'featuretype') {
	$getTypeSql = "SELECT element_id, element_type from wfs_element WHERE fkey_featuretype_id = $1 AND element_type LIKE '%PropertyType';";
	$vgetType = array($resourceMetadata['contentid']);
	$tgetType = array('i');
	$resGetType = db_prep_query($getTypeSql,$vgetType,$tgetType);
	$featuretypeElements = db_fetch_array($resGetType);
	$resourceMetadata['featuretype_geomType'] = $featuretypeElements['element_type'];
}

//$e = new mb_notice("mod_showMetadata: mb_group_name: ".$metadataContactGroup['mb_group_name']);
//
// check if update sequence is valid
//
$updateSequenceDb = intval($wms_row["wms_timestamp"]);

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

//
// increment layer count
//
$monitor = new Layer_load_count();
$monitor->increment($layerId);

// ---------------------------------------------------------------------------
//
// START TO CREATE CAPABILITIES DOC
// (return most recent Capabilities XML)
//
// ---------------------------------------------------------------------------
if ($validateSchema == true) {
	$imp = new DOMImplementation;
	$dtd = $imp->createDocumentType('WMT_MS_Capabilities', '', 'http://schemas.opengis.net/wms/1.1.1/WMS_MS_Capabilities.dtd');
	$doc = $imp->createDocument("", "", $dtd);
} else {
	$doc = new DOMDocument('1.0');
}
$doc->encoding = 'UTF-8';
$doc->standalone = false;

#Check for existing content in database
#to be adopted TODO armin 
function validate ($contactInformation_column) {
    if ($contactInformation_column <> "" AND $contactInformation_column <> NULL) {
             	$contactinformationcheck = true;
    }
    else {
		$contactinformationcheck = false;
	}
	return $contactinformationcheck;
}
	
#Creating the "WMT_MS_Capabilities" node
$wmt_ms_capabilities = $doc->createElement("WMT_MS_Capabilities");
$wmt_ms_capabilities->setAttribute("updateSequence", $wms_row["wms_timestamp"]);
if ($inspire){
	$wmt_ms_capabilities->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
	$wmt_ms_capabilities->setAttribute("xmlns:inspire_vs", "http://inspire.ec.europa.eu/schemas/inspire_vs/1.0");
}
$wmt_ms_capabilities = $doc->appendChild($wmt_ms_capabilities);
$wmt_ms_capabilities->setAttribute('version', '1.1.1');

#Creatig the "Service" node 
$service = $doc->createElement("Service");
$service = $wmt_ms_capabilities->appendChild($service);

#Creating the "Name" Node
$name = $doc->createElement("Name");
$name = $service->appendChild($name);
$nameText = $doc->createTextNode("OGC:WMS");
$nameText = $name->appendChild($nameText);

#Creating the "Title" node
if($wms_row['wms_title'] <> "" AND $wms_row['wms_title'] <> NULL) {
    $title = $doc->createElement("Title");
	$title = $service->appendChild($title);
	$titleText = $doc->createTextNode($wms_row['wms_title']);
	$titleText = $title->appendChild($titleText);
}

#Creating the "Abstract" node
if($wms_row['wms_abstract'] <> "" AND $wms_row['wms_abstract'] <> NULL) {
	$abstract = $doc->createElement("Abstract");
	$abstract = $service->appendChild($abstract);
	$abstractText = $doc->createTextNode($wms_row['wms_abstract']);
	$abstractText = $abstract->appendChild($abstractText);
}
	
# switch URLs for OWSPROXY

//check if resource is freely available to anonymous user - which are all users who search thru metadata catalogues:
//$publicHasPermission=$admin->getLayerPermission($mapbenderMetadata['wms_id'],$mapbenderMetadata['layer_name'],PUBLIC_USER);
//for the inspire use case:
//url maybe owsproxy, if proxy is active and guest have right to use resource
//url maybe http_auth if proxy is active and guest is not authorized
//url maybe original if proxy is not active

//set new capabilities url:
//$e = new mb_exception($urlPrefix.$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);
$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"];
$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
//$e = new mb_exception($tmpOR);
$wms_row['wms_getcapabilities'] = $tmpOR;

if ($inspire) {
	$wms_row['wms_getcapabilities'] .= "&INSPIRE=1";
}
$e = new mb_notice($wms_row['wms_getcapabilities']);
$publicHasPermission=$admin->getLayerPermission($wms_row['wms_id'],$wms_row['layer_name'],PUBLIC_USER);
if ($wms_row['wms_owsproxy'] <> "" AND $wms_row['wms_owsproxy'] <> NULL) {
	if ($inspire) {
		if ($publicHasPermission) {
			//use owsproxy url
			$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/owsproxy/".$sessionId."/".$wms_row["wms_owsproxy"]."?";
			$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
			$wms_row['wms_getmap'] = $tmpOR;
			$wms_row['wms_getfeatureinfo'] = $tmpOR;
			$wms_row['wms_getlegendurl'] = $tmpOR;
		} else {
			//use http_auth
			$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/http_auth/".$layerId."?";
			$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
			$wms_row['wms_getmap'] = $tmpOR;
			$wms_row['wms_getfeatureinfo'] = $tmpOR;
			$wms_row['wms_getlegendurl'] = $tmpOR;
		}
	}	
	else {
		//use owsproxy url
		$tmpOR = $urlPrefix.$_SERVER["HTTP_HOST"]."/owsproxy/".$sessionId."/".$wms_row["wms_owsproxy"]."?";
		$tmpOR = str_replace(SERVERIP, SERVERNAME, $tmpOR);
		$wms_row['wms_getmap'] = $tmpOR;
		$wms_row['wms_getfeatureinfo'] = $tmpOR;
		$wms_row['wms_getlegendurl'] = $tmpOR;
	}
} 

//Creating the "OnlineResource" node
//if($wms_row['wms_getcapabilities'] <> "" AND $wms_row['wms_getcapabilities'] <> NULL) {
    $onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $service->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getcapabilities']);
/*	$onlRes = $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"] . "?layer_id=" . $layerId."&".session_name()."=".$sessionId;
	if (isset($_SERVER["HTTPS"])) {
		$onlRes = "https://" . $onlRes;
	}
	else {
		$onlRes = "http://" . $onlRes;
	}*/
	//$onlineResource->setAttribute("xlink:href", $onlRes);
	$onlineResource->setAttribute("xlink:type", "simple");
//}

#Insert contact information

#Creating "Contact Information" node
if (validate($wms_row['contactperson']) &&
	validate($wms_row['contactorganization']) &&
	validate($wms_row['contactposition']) && 
	validate($wms_row['address']) && 
	validate($wms_row['city']) && 
	validate($wms_row['stateorprovince']) && 
	validate($wms_row['postcode']) && //AND validate($wms_row['country']) &&
	validate($wms_row['contactvoicetelephone']) && 
	validate($wms_row['contactfacsimiletelephone']) &&
	validate($wms_row['contactelectronicmailaddress']))
{
$contactInformation = $doc->createElement("ContactInformation");
$contactInformation = $service->appendChild($contactInformation);

#Creating "Contact Person Primary" node
if(validate($wms_row['contactperson']) AND validate($wms_row['contactorganization']))
{
$contactPersonPrimary = $doc->createElement("ContactPersonPrimary");
$contactPersonPrimary = $contactInformation->appendChild($contactPersonPrimary); 
}

#Creating the "ContactPerson" node
if(validate($wms_row['contactperson']))
{
    $contactPerson = $doc->createElement("ContactPerson");
    $contactPerson = $contactPersonPrimary->appendChild($contactPerson);
    $contactPersonText = $doc->createTextNode($wms_row['contactperson']);
    $contactPersonText = $contactPerson->appendChild($contactPersonText);
}

#Creating the "ContactOrganization" node
if(validate($wms_row['contactorganization']))
{
    $contactOrganization = $doc->createElement("ContactOrganization");
    $contactOrganization = $contactPersonPrimary->appendChild($contactOrganization);
    $contactOrganizationText = $doc->createTextNode($wms_row['contactorganization']);
    $contactOrganizationText = $contactOrganization->appendChild($contactOrganizationText);
}


#Creating the "ContactPosition" node
if(validate($wms_row['contactposition']))
{
    $contactPosition = $doc->createElement("ContactPosition");
    $contactPosition = $contactInformation->appendChild($contactPosition);
    $contactPositionText = $doc->createTextNode($wms_row['contactposition']);
    $contactPositionText = $contactPosition->appendChild($contactPositionText);    
}

#Creating "ContactAddress" node
if(validate($wms_row['address']) AND validate($wms_row['city']) AND validate($wms_row['stateorprovince']) AND               validate($wms_row['postcode']) /*AND validate($wms_row['country'])*/)
{
$contactAddress = $doc->createElement("ContactAddress");
$contactAddress = $contactInformation->appendChild($contactAddress); 
}

#Creating the "AddressType" and "Address" textnode
if(validate($wms_row['address']))
{
	
    $addressType = $doc->createElement("AddressType");
    $addressType = $contactAddress->appendChild($addressType);
    $addresstypeText = $doc->createTextNode("postal");
    $addresstypeText = $addressType->appendChild($addresstypeText);
    
    $address = $doc->createElement("Address");
    $address = $contactAddress->appendChild($address);
    $addressText = $doc->createTextNode($wms_row['address']);
    $addressText = $address->appendChild($addressText);
}

#Creatig the "City" node  
if(validate($wms_row['city']))
{
    $city = $doc->createElement("City");
    $city = $contactAddress->appendChild($city);
    $cityText = $doc->createTextNode($wms_row['city']);
    $cityText = $city->appendChild($cityText);
}

#Creatig the "StateOrProvince" node    
if(validate($wms_row['stateorprovince']))
{
    $stateOrProvince = $doc->createElement("StateOrProvince");
    $stateOrProvince = $contactAddress->appendChild($stateOrProvince);
    $stateOrProvinceText = $doc->createTextNode($wms_row['stateorprovince']);
    $stateOrProvinceText = $stateOrProvince->appendChild($stateOrProvinceText);
}

#Creatig the "PostCode" node    
if(validate($wms_row['postcode']))
{
    $postCode = $doc->createElement("PostCode");
    $postCode = $contactAddress->appendChild($postCode);
    $postCodeText = $doc->createTextNode($wms_row['postcode']);
    $postCodeText = $postCode->appendChild($postCodeText);
}

 
#Creatig the "Country" node   
if(isset($wms_row['country']) AND validate($wms_row['country']))
{
    $country = $doc->createElement("Country");
    $country = $contactAddress->appendChild($country);
    $countryText = $doc->createTextNode($wms_row['country']);
    $countryText = $country->appendChild($countryText);
}

#Creatig the "ContactVoiceTelephone" node
if(validate($wms_row['contactvoicetelephone']))
{
    $contactVoiceTelephone = $doc->createElement("ContactVoiceTelephone");
    $contactVoiceTelephone = $contactInformation->appendChild($contactVoiceTelephone);
    $contactVoiceTelephoneText = $doc->createTextNode($wms_row['contactvoicetelephone']);
    $contactVoiceTelephoneText = $contactVoiceTelephone->appendChild($contactVoiceTelephoneText);
}

#Creatig the "ContactFacsimileTelephone" node
if(validate($wms_row['contactfacsimiletelephone']))
{
    $contactFacsimileTelephone = $doc->createElement("ContactFacsimileTelephone");
    $contactFacsimileTelephone = $contactInformation->appendChild($contactFacsimileTelephone);
    $contactFacsimileTelephoneText = $doc->createTextNode($wms_row['contactfacsimiletelephone']);
    $contactFacsimileTelephoneText = $contactFacsimileTelephone->appendChild($contactFacsimileTelephoneText);
}

#Creatig the "ContactElectronicMailAddress" node
if(validate($wms_row['contactelectronicmailaddress']))
{
    $contactElectronicMailAddress = $doc->createElement("ContactElectronicMailAddress");
    $contactElectronicMailAddress = $contactInformation->appendChild($contactElectronicMailAddress);
    $contactElectronicMailAddressText = $doc->createTextNode($wms_row['contactelectronicmailaddress']);
    $contactElectronicMailAddressText = $contactElectronicMailAddress->appendChild($contactElectronicMailAddressText);
}
}

#Creatig the "Fees" node
if(validate($wms_row['fees']))
{
    $fees = $doc->createElement("Fees");
    $fees = $service->appendChild($fees);
    $feesText = $doc->createTextNode($wms_row['fees']);
    $feesText = $fees->appendChild($feesText);
}
   
#Creating the "AccessConstraints" node
if(validate($wms_row['accessconstraints']))
{
	$accessConstraints = $doc->createElement("AccessConstraints");
    $accessConstraints = $service->appendChild($accessConstraints);
    $accessConstraintsText = $doc->createTextNode($wms_row['accessconstraints']);
    $accessConstraintsText = $accessConstraints->appendChild($accessConstraintsText);
}


 
#Creating the "Capability" node 
$capability = $doc->createElement("Capability");
$capability = $wmt_ms_capabilities->appendChild($capability);

#Creatig the "Request" node 
$request = $doc->createElement("Request");
$request = $capability->appendChild($request);

############################################################
#GetCapabilities
#Creating the "GetCapabilities" node 
$getCapabilities = $doc->createElement("GetCapabilities");
$getCapabilities = $request->appendChild($getCapabilities);

#Creating the "Format" node 
$wms_format_sql ="SELECT data_format FROM wms_format WHERE fkey_wms_id = $1 AND data_type = 'capability'";
$v = array($wms_row['wms_id']);
$t = array("i");
$res_wms_format_sql = db_prep_query($wms_format_sql, $v, $t);
while ($wms_format_row = db_fetch_array($res_wms_format_sql)) {
    $format = $doc->createElement("Format");
    $format = $getCapabilities->appendChild($format);
    $formatText = $doc->createTextNode($wms_format_row['data_format']);
    $formatText = $format->appendChild($formatText);    
}
#case if the format for capabilities is not read :
    $format = $doc->createElement("Format");
    $format = $getCapabilities->appendChild($format);
    $formatText = $doc->createTextNode('application/vnd.ogc.wms_xml');
    $formatText = $format->appendChild($formatText); 



#Creating the "DCPType" node
$DCPType = $doc->createElement("DCPType");
$DCPType = $getCapabilities->appendChild($DCPType);

#Creating the "HTTP" node
$HTTP = $doc->createElement("HTTP");
$HTTP = $DCPType->appendChild($HTTP);

#Creating the "Get" node
$get = $doc->createElement("Get");
$get = $HTTP->appendChild($get);

#Creating the "OnlineResource" node

//if ($wms_row['wms_getcapabilities'] <> "" AND $wms_row['wms_getcapabilities'] <> NULL) {
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $get->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getcapabilities']);
/*	$onlRes = $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"] . "?layer_id=" . $layerId;

	if ($inspire) {
	$onlRes = $onlRes."&INSPIRE=1";
	}

	if (isset($_SERVER["HTTPS"])) {
		$onlRes = "https://" . $onlRes;
	}
	else {
		$onlRes = "http://" . $onlRes;
	}*/
	//$onlineResource->setAttribute("xlink:href", $onlRes);
	$onlineResource->setAttribute("xlink:type", "simple");		
//}

#Creating the "Post" node
$post = $doc->createElement("Post");
$post = $HTTP->appendChild($post);

#Creating the "OnlineResource" node
//if ($wms_row['wms_getcapabilities'] <> "" AND $wms_row['wms_getcapabilities'] <> NULL) {
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $post->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getcapabilities']);
/*	$onlRes = $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"] . "?layer_id=" . $layerId;

	if ($inspire) {
	$onlRes = $onlRes."&INSPIRE=1&";
	}
	
	if (isset($_SERVER["HTTPS"])) {
		$onlRes = "https://" . $onlRes;
	}
	else {
		$onlRes = "http://" . $onlRes;
	}*/
	$onlineResource->setAttribute("xlink:href", $onlRes);
	$onlineResource->setAttribute("xlink:type", "simple");
//}

##########################################################
#GetMap	
#Creatig the "GetMap" node 
$getMap = $doc->createElement("GetMap");
$getMap = $request->appendChild($getMap);

#Creatig the "Format" node 
$wms_format_sql ="SELECT data_format FROM wms_format WHERE fkey_wms_id = $1 AND data_type = 'map'";
$v = array($wms_row['wms_id']);
$t = array("i");
$res_wms_format_sql = db_prep_query($wms_format_sql, $v, $t);

while ($wms_format_row = db_fetch_array($res_wms_format_sql)) {
    $format = $doc->createElement("Format");
    $format = $getMap->appendChild($format);
    $formatText = $doc->createTextNode($wms_format_row['data_format']);
    $formatText = $format->appendChild($formatText);	
}

#Creating the "DCPType" node
$DCPType = $doc->createElement("DCPType");
$DCPType = $getMap->appendChild($DCPType);

#Creating the "HTTP" node
$HTTP = $doc->createElement("HTTP");
$HTTP = $DCPType->appendChild($HTTP);

#Creating the "Get" node
$get = $doc->createElement("Get");
$get = $HTTP->appendChild($get);

#Creating the "OnlineResource" node
if ($wms_row['wms_getmap'] <> "" AND $wms_row['wms_getmap'] <> NULL) {
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $get->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getmap']);
	$onlineResource->setAttribute("xlink:type", "simple");
}

#Creating the "Post" node
$post = $doc->createElement("Post");
$post = $HTTP->appendChild($post);

#Creating the "OnlineResource" node
if($wms_row['wms_getmap'] <> "" AND $wms_row['wms_getmap'] <> NULL) {
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $post->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getmap']);
	$onlineResource->setAttribute("xlink:type", "simple");
}

##########################################################
#GetFeatureInfo	
#Creatig the "GetFeatureInfo" node 
$getFeatureInfo = $doc->createElement("GetFeatureInfo");
$getFeatureInfo = $request->appendChild($getFeatureInfo);

#Creatig the "Format" node 
$wms_format_sql ="SELECT data_format FROM wms_format WHERE fkey_wms_id = $1 AND data_type = 'featureinfo'";
$v = array($wms_row['wms_id']);
$t = array("i");
$res_wms_format_sql = db_prep_query($wms_format_sql, $v, $t);
while ($wms_format_row = db_fetch_array($res_wms_format_sql))
{
    $format = $doc->createElement("Format");
    $format = $getFeatureInfo->appendChild($format);
    $formatText = $doc->createTextNode($wms_format_row['data_format']);
    $formatText = $format->appendChild($formatText);    
}
	
#Creating the "DCPType" node
$DCPType = $doc->createElement("DCPType");
$DCPType = $getFeatureInfo->appendChild($DCPType);

#Creating the "HTTP" node
$HTTP = $doc->createElement("HTTP");
$HTTP = $DCPType->appendChild($HTTP);

#Creating the "Get" node
$get = $doc->createElement("Get");
$get = $HTTP->appendChild($get);

#Creating the "OnlineResource" node
if($wms_row['wms_getfeatureinfo'] <> "" AND $wms_row['wms_getfeatureinfo'] <> NULL)
{
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $get->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getfeatureinfo']);
	$onlineResource->setAttribute("xlink:type", "simple");
}
#Creating the "Post" node
$post = $doc->createElement("Post");
$post = $HTTP->appendChild($post);

#Creating the "OnlineResource" node

if($wms_row['wms_getfeatureinfo'] <> "" AND $wms_row['wms_getfeatureinfo'] <> NULL) {
	$onlineResource = $doc->createElement("OnlineResource");
	$onlineResource = $post->appendChild($onlineResource);
	$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
	$onlineResource->setAttribute("xlink:href", $wms_row['wms_getfeatureinfo']);
	$onlineResource->setAttribute("xlink:type", "simple");
}
	
#Creating the "Exeption" node
$exception = $doc->createElement("Exception");
$exception = $capability->appendChild($exception);	

#Creatig the "Format" node 
$wms_format_sql ="SELECT data_format FROM wms_format WHERE fkey_wms_id = $1 AND data_type = 'exception'";
$v = array($wms_row['wms_id']);
$t = array("i");
$res_wms_format_sql = db_prep_query($wms_format_sql, $v, $t);
while ($wms_format_row = db_fetch_array($res_wms_format_sql)) {
    $format = $doc->createElement("Format");
    $format = $exception->appendChild($format);
    $formatText = $doc->createTextNode($wms_format_row['data_format']);
    $formatText = $format->appendChild($formatText); 
} 
################################################################
#INSPIRE
if ($inspire) {
	#generating the vendor specific node
	$vendorSpecificCapabilities = $doc->createElement("VendorSpecificCapabilities");
	$vendorSpecificCapabilities = $capability->appendChild($vendorSpecificCapabilities);
	#generate inspire_vs:ExtendedCapabilities node
	$inspire_vs_ExtendedCapabilities = $doc->createElement("inspire_vs:ExtendedCapabilities");
	$inspire_vs_ExtendedCapabilities->setAttribute("xmlns:inspire_vs", "http://inspire.ec.europa.eu/schemas/inspire_vs/1.0");
	$inspire_vs_ExtendedCapabilities = $vendorSpecificCapabilities->appendChild($inspire_vs_ExtendedCapabilities);
	#generate inspire_vs: node
	#$inspire_vs_ExtendedCapabilities = $doc->createElement("inspire_vs:ExtendedCapabilities");
	#$inspire_vs_ExtendedCapabilities = $vendorSpecificCapabilities->appendChild($inspire_vs_ExtendedCapabilities);
	#MetadataUrl to inspire service metadata
	$inspire_common_MetadataUrl = $doc->createElement("inspire_common:MetadataUrl");
	/*<inspire_common:MetadataUrl xmlns:inspire_common="http://inspire.ec.europa.eu/schemas/common/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="inspire_common:resourceLocatorType">*/
	$inspire_common_MetadataUrl->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
	$inspire_common_MetadataUrl->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$inspire_common_MetadataUrl->setAttribute("xsi:type", "inspire_common:resourceLocatorType");
	$inspire_common_MetadataUrl = $inspire_vs_ExtendedCapabilities->appendChild($inspire_common_MetadataUrl);
	#URL
	$inspire_common_URL = $doc->createElement("inspire_common:URL");
	$inspire_common_URL->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");

	$inspire_common_URLText = $doc->createTextNode($inspireServiceMetadataUrl.$layerId);
	$inspire_common_URL->appendChild($inspire_common_URLText);
	$inspire_common_URL = $inspire_common_MetadataUrl->appendChild($inspire_common_URL);
	#MediaType
	$inspire_common_MediaType = $doc->createElement("inspire_common:MediaType");
	$inspire_common_MediaType->setAttribute("xmlns:inspire_common", "http://inspire.ec.europa.eu/schemas/common/1.0");
	$inspire_common_MediaTypeText = $doc->createTextNode('application/vnd.iso.19139+xml');#from http://inspire.ec.europa.eu/schemas/inspire_vs/1.0/examples/WMS_Image2000GetCapabilities_InspireSchema.xml
	$inspire_common_MediaType->appendChild($inspire_common_MediaTypeText);
	$inspire_common_MediaType = $inspire_common_MetadataUrl->appendChild($inspire_common_MediaType);
	#Language Part
	#SupportedLanguages
	$inspire_common_SupportedLanguages = $doc->createElement("inspire_common:SupportedLanguages");
	$inspire_common_SupportedLanguages = $inspire_vs_ExtendedCapabilities->appendChild($inspire_common_SupportedLanguages);
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
	$inspire_common_ResponseLanguage = $inspire_vs_ExtendedCapabilities->appendChild($inspire_common_ResponseLanguage);
	#Language
	$inspire_common_Language = $doc->createElement("inspire_common:Language");
	$inspire_common_LanguageText = $doc->createTextNode('ger');
	$inspire_common_Language->appendChild($inspire_common_LanguageText);
	$inspire_common_Language = $inspire_common_ResponseLanguage->appendChild($inspire_common_Language);
}
################################################################
#Querying layer table for root layer information!
$layer_sql = "SELECT * FROM layer WHERE layer.fkey_wms_id = $1 AND layer.layer_parent = ''";
$v = array($wms_row['wms_id']);
$t = array("i");

$res_layer_sql = db_prep_query($layer_sql, $v, $t);
$layer_row = db_fetch_array($res_layer_sql);
			
#Creating root layer node
$layer = $doc->createElement("Layer");
$layer = $capability->appendChild($layer);

#Write layer to parent layer array
$parentLayerArray[$layer_row['layer_pos']] = $layer;
		
#Creating Name node
/*if ($layer_row['layer_name'] <> "" AND $layer_row['layer_name'] <> NULL) {
	$name = $doc->createElement("Name");
	$name = $layer->appendChild($name);
	$nameText = $doc->createTextNode($layer_row['layer_name']);
	$nameText = $name->appendChild($nameText);
}*/
//declaring the root layer always to be unnamed! - FIXME 

#Creating Title node
if ($layer_row['layer_title'] <> "" AND $layer_row['layer_title'] <> NULL) {
	$title = $doc->createElement("Title");
	$title = $layer->appendChild($title);
	$titleText = $doc->createTextNode($layer_row['layer_title']);
	$titleText = $title->appendChild($titleText);
}

#Creating the "Abstract" node
if($layer_row['layer_abstract'] <> "" AND $layer_row['layer_abstract'] <> NULL) {
	$layerAbstract = $layer_row['layer_abstract'];
} else {
	$layerAbstract = _mb("Layer abstract not given - please give a description for this layer.");
}
$abstract = $doc->createElement("Abstract");
$abstract = $layer->appendChild($abstract);
$abstractText = $doc->createTextNode($layerAbstract);
$abstractText = $abstract->appendChild($abstractText);
#Request the specific wms- and layerkeywords

$keyword_sql = "SELECT DISTINCT keyword FROM keyword, layer_keyword, layer " . 
	"WHERE keyword.keyword_id = layer_keyword.fkey_keyword_id " . 
	"AND layer_keyword.fkey_layer_id = layer.layer_id " . 
	"AND layer.fkey_wms_id = $1";
$v = array($wms_row['wms_id']);
$t = array("i");
$res_keyword_sql = db_prep_query($keyword_sql, $v, $t);

#Creating list of keyword nodes
#Iterating over a List of Keywords
$keywordlistExist = 0;

while ($keyword_sql = db_fetch_array($res_keyword_sql))
{
    #Creating the "KeywordList" node
    if ($keywordlistExist == 0) {
        $keywordList = $doc->createElement("KeywordList");
        $keywordList = $layer->appendChild($keywordList);
		$keywordlistExist = 1;	
    }
    
    #Creating the "Keyword" node
	if (trim($keyword_sql['keyword']) <> "" AND $keyword_sql['keyword'] <> NULL) {
    		$keyword_dom = $doc->createElement("Keyword");
    		$keyword_dom = $keywordList->appendChild($keyword_dom); 
    		$keyword_domText = $doc->createTextNode($keyword_sql['keyword']);
    		$keyword_domText = $keyword_dom->appendChild($keyword_domText);
	}
}

//SQL statement to get additional layer information from layer epsg	
$epsg_sql = "SELECT layer_epsg.epsg, layer_epsg.minx, layer_epsg.miny, " . 
	"layer_epsg.maxy, layer_epsg.maxx " . 
	"FROM layer_epsg WHERE layer_epsg.fkey_layer_id = $1";
	
$v = array($layer_row['layer_id']);
$t = array("i");
$res_espg_sql = db_prep_query($epsg_sql, $v, $t);
$res_espg_sql1 = $res_espg_sql;
$latLonBoundingBoxCreated = false;
$BoundingBoxCreated = false;

//pull all other parent layer and their epsg!!


while ($epsg_row = db_fetch_array($res_espg_sql)) {
	#Creating SRS node
	$srs = $doc->createElement("SRS");
	$srs = $layer->appendChild($srs);
	$srsText = $doc->createTextNode(str_replace('epsg','EPSG',$epsg_row['epsg']));
	$srsText = $srs->appendChild($srsText);
}

#SQL statement to get additional layer information from layer epsg	
/*$epsg_sql = "SELECT layer_epsg.epsg, layer_epsg.minx, layer_epsg.miny, " . 
	"layer_epsg.maxy, layer_epsg.maxx " . 
	"FROM layer_epsg WHERE layer_epsg.fkey_layer_id = $1";
	
$v = array($layer_row['layer_id']);
$t = array("i");*/
$res_espg_sql = db_prep_query($epsg_sql, $v, $t);

while ($epsg_row = db_fetch_array($res_espg_sql)) {
	#set only epsg 4326 for latlonbbox
	if ($epsg_row['epsg'] == "EPSG:4326") {
		
		$latlon['minx'] = $epsg_row['minx'];
		$latlon['miny'] = $epsg_row['miny'];
		$latlon['maxx'] = $epsg_row['maxx'];
		$latlon['maxy'] = $epsg_row['maxy'];

		#Creating LatLongBoundingBox node
		$latLonBoundingBox = $doc->createElement("LatLonBoundingBox");
		$latLonBoundingBox = $layer->appendChild($latLonBoundingBox);
		$latLonBoundingBox->setAttribute('minx', $latlon['minx']);
		$latLonBoundingBox->setAttribute('miny', $latlon['miny']);
		$latLonBoundingBox->setAttribute('maxx', $latlon['maxx']);
		$latLonBoundingBox->setAttribute('maxy', $latlon['maxy']);
	    break;
    }	
}

#SQL statement to get additional layer information from layer epsg	
/*$epsg_sql = "SELECT layer_epsg.epsg, layer_epsg.minx, layer_epsg.miny, " . 
	"layer_epsg.maxy, layer_epsg.maxx " . 
	"FROM layer_epsg WHERE layer_epsg.fkey_layer_id = $1";
	
$v = array($layer_row['layer_id']);
$t = array("i");*/
$res_espg_sql = db_prep_query($epsg_sql, $v, $t);

while ($epsg_row = db_fetch_array($res_espg_sql)) {
	#set only first epsg for bbox
	$bbox['epsg'] = str_replace('epsg','EPSG',$epsg_row['epsg']);
	$bbox['minx'] = $epsg_row['minx'];
	$bbox['miny'] = $epsg_row['miny'];
	$bbox['maxx'] = $epsg_row['maxx'];
	$bbox['maxy'] = $epsg_row['maxy'];

	#Creating BoundingBox node
	$boundingBox = $doc->createElement("BoundingBox");
	$boundingBox = $layer->appendChild($boundingBox);
	$boundingBox->setAttribute('SRS', $bbox['epsg']);
	$boundingBox->setAttribute('minx', $bbox['minx']);
	$boundingBox->setAttribute('miny', $bbox['miny']);
	$boundingBox->setAttribute('maxx', $bbox['maxx']);
	$boundingBox->setAttribute('maxy', $bbox['maxy']);
}

#Append epsg string to srs node
$srsText = $doc->createTextNode($epsgText);
$srsText = $srs->appendChild($srsText);

//Create AuthorityUrl Node on root layer level
//<AuthorityURL name="gcmd"><OnlineResource xlink:href="some_url" ... /></AuthorityURL>
//$indexAuthorityUrl = 0;
/*foreach ($AuthorityUrlArray as $AuthorityUrl) {
    $AuthorityURL = $doc->createElement("AuthorityURL");
    $AuthorityURL = $layer->appendChild($AuthorityURL);
    $AuthorityURL->setAttribute('name', $AuthorityNameArray[$indexAuthorityUrl]);
    $AUOnlineResource = $doc->createElement("OnlineResource");
    $AUOnlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
    $AUOnlineResource->setAttribute("xlink:type", "simple");
    $AUOnlineResource->setAttribute("xlink:href", $AuthorityUrl);
    $AUOnlineResource = $AuthorityURL->appendChild($AUOnlineResource);
    $indexAuthorityUrl++;
}*/

//fictive root layer created

####### duplicate root layer 
#if layer is root layer itself!
#<armin>
##if ($layer_row['layer_pos']=='0'){
#</armin>
##$clonedLayer = $layer->cloneNode(true);
##$clonedLayer->setAttribute("queryable", "0");
##$clonedLayer->setAttribute("cascaded", "0");
##$layer->appendChild($clonedLayer);
#<armin>
##}
#</armin>

//call function for sublayer element

//*************************************************
function createLayerElement ($doc, $wmsId, $layerRow, $wmsRow, $AuthorityName, $mapbenderMetadataUrlUrl) {
	$admin = new Administration();
	#Creating single layer node
	$layer = $doc->createElement("Layer");
    	if($layerRow['layer_queryable'] <> "" AND $layerRow['layer_queryable'] <> NULL) {
		$layer->setAttribute('queryable', $layerRow['layer_queryable']);
    	}
	#Creating name node
    	if($layerRow['layer_name'] <> "" AND $layerRow['layer_name'] <> NULL) {
		$name = $doc->createElement("Name");
		$name = $layer->appendChild($name);
		$nameText = $doc->createTextNode($layerRow['layer_name']);
		$nameText = $name->appendChild($nameText);
    	}
	#Creating Title node
    	if($layerRow['layer_title'] <> "" AND $layerRow['layer_title'] <> NULL) {
		$title = $doc->createElement("Title");
		$title = $layer->appendChild($title);
		$titleText = $doc->createTextNode($layerRow['layer_title']);
		$titleText = $title->appendChild($titleText);
    	}
	#Creating the "Abstract" node
    	if($layerRow['layer_abstract'] <> "" AND $layerRow['layer_abstract'] <> NULL) {
		$layerAbstract = $layerRow['layer_abstract'];
	} else {
		$layerAbstract = _mb('No abstract for the specific layer was given, please add a description to your layer ressource.');
	}
    	$abstract = $doc->createElement("Abstract");
    	$abstract = $layer->appendChild($abstract);
    	$abstractText = $doc->createTextNode($layerAbstract);
    	$abstractText = $abstract->appendChild($abstractText);
	#Request the specific wms- and layerkeywords
    	$keyword_sql = "SELECT DISTINCT keyword FROM layer LEFT JOIN layer_keyword ON layer_keyword.fkey_layer_id = layer.layer_id LEFT JOIN keyword ON  keyword.keyword_id = layer_keyword.fkey_keyword_id WHERE layer.fkey_wms_id = ".$wmsRow['wms_id']." AND layer.layer_id = ".$layerRow['layer_id']."";
    	$res_keyword_sql = db_query($keyword_sql);
    	#Creating list of keyword nodes
   	#Iterating over a List of Keywords
  	$keywordlistExist = 0;
    	while ($keyword_sql = db_fetch_array($res_keyword_sql)) {
        	#Initially creating the "KeywordList" node
        	if ($keywordlistExist == 0)
        	{
            		$keywordList = $doc->createElement("KeywordList");
            		$keywordList = $layer->appendChild($keywordList);
			$keywordlistExist = 1;			
        	}
        	#Creating the "Keyword" node
        	$keyword_dom = $doc->createElement("Keyword");
        	$keyword_dom = $keywordList->appendChild($keyword_dom);
		if (trim($keyword_sql['keyword']) <> "" AND $keyword_sql['keyword'] <> NULL) {
			$keyword = $keyword_sql['keyword'];
		} else {
			$keyword = _mb('Empty keyword was given, please add a keyword to your layer ressource.');
		}
        	$keyword_domText = $doc->createTextNode($keyword);
        	$keyword_domText = $keyword_dom->appendChild($keyword_domText);
    	}
	//inherit srs from parent layer TODO - use all srs from parents! Test the sql first
	$layer_srs_sql = "SELECT DISTINCT epsg FROM layer_epsg " . 
			"WHERE fkey_layer_id = ".$layerRow['layer_id'];  
			//" OR fkey_layer_id = " . $layer_row['layer_id'];
	$res_layer_srs_sql = db_query($layer_srs_sql);
	//store srs into array
	$origLayerEpsg = array();
	while ($layer_srs_row = db_fetch_array($res_layer_srs_sql)) {
		//Creating SRS node
		$srs = $doc->createElement("SRS");
		$srs = $layer->appendChild($srs);
		$srsText = $doc->createTextNode(str_replace('epsg','EPSG',$layer_srs_row['epsg']));
		$origLayerEpsg[] = str_replace('epsg','EPSG',$layer_srs_row['epsg']);
		$srsText = $srs->appendChild($srsText);
	}
	//extent this list with srs from all parent layers
	//build tree
	$parent_layer_srs_sql = "SELECT layer_id, layer_pos, layer_parent FROM layer WHERE fkey_wms_id = $1";
	
	$vPL = array($wmsRow['wms_id']);
	$tPL = array('i');
	$resPL = db_prep_query($parent_layer_srs_sql, $vPL, $tPL);
	while ($layerTree = db_fetch_array($resPL)) {
		$layerTreeArray['layer_id'][] = $layerTree['layer_id'];
		$layerTreeArray['layer_pos'][] = $layerTree['layer_pos'];
		$layerTreeArray['layer_parent'][] = $layerTree['layer_parent'];
	}
	$rootLayerFound = false;
	$layerStructure = array();
	$searchLayerId = $layerRow['layer_id'];
	
	//$layerStructure[] = $searchLayerId;
	while ($rootLayerFound == false) {
		$layerStructure[] = $searchLayerId; //pull only parent layerIds
		$key = array_search($searchLayerId, $layerTreeArray['layer_id']);
		if (!$key) {
			$rootLayerFound = true;
		} else {
			$parent = $layerTreeArray['layer_parent'][$key];
			if ($parent == null || $parent == '') {
				$rootLayerFound = true;
			} else {
				$parentId = $layerTreeArray['layer_id'][array_search($parent, $layerTreeArray['layer_pos'])];
				
				$searchLayerId = $parentId;
			}
		}
	}
	//remove first entry 
	unset($layerStructure[0]);
	$layerStructure = array_values($layerStructure);
	//debug*************************************
	/*foreach ($layerStructure as $singleLayer) {
		echo $singleLayer."<br>";
	}
	die();*/
	//debug*************************************
	if (count($layerStructure) > 0) {
		$parent_layer_srs_sql = "SELECT DISTINCT epsg FROM layer_epsg WHERE fkey_layer_id IN  (".implode(',',$layerStructure).")";  
		$res_parent_layer_srs_sql = db_query($parent_layer_srs_sql);
		while ($layer_parent_srs_row = db_fetch_array($res_parent_layer_srs_sql)) {
			//check if already exists
			if (!in_array(str_replace('epsg','EPSG',$layer_parent_srs_row['epsg']), $origLayerEpsg)) {
				//Creating SRS node
				//$e = new mb_exception("add epsg support from parent layer: ".str_replace('epsg','EPSG',$layer_parent_srs_row['epsg']));
				$srs = $doc->createElement("SRS");
				$srs = $layer->appendChild($srs);
				$srsText = $doc->createTextNode(str_replace('epsg','EPSG',$layer_parent_srs_row['epsg']));
				$srsText = $srs->appendChild($srsText);
			} else {
				//$e = new mb_exception("EPSG from parent layers already defined in sublayer: ".str_replace('epsg','EPSG',$layer_parent_srs_row['epsg']));
			}
		}	
	}
	//
	#SQL statement to get additional layer information from layer epsg	
	$epsg_sql = "SELECT layer_epsg.epsg, layer_epsg.minx, layer_epsg.miny, " . 
			"layer_epsg.maxy, layer_epsg.maxx FROM layer_epsg " . 
			"WHERE layer_epsg.fkey_layer_id = ".$layerRow['layer_id'];
	$res_espg_sql = db_query($epsg_sql);
	while ($epsg_row = db_fetch_array($res_espg_sql)) {
		#set epsg 4326 for latlonbbox
		if ($epsg_row['epsg'] == "EPSG:4326" AND $latLonBoundingBoxCreated == false) {
			$latlon['minx'] = $epsg_row['minx'];
			$latlon['miny'] = $epsg_row['miny'];
			$latlon['maxx'] = $epsg_row['maxx'];
			$latlon['maxy'] = $epsg_row['maxy'];
			#Creating LatLongBoundingBox node
		    	$latLonBoundingBox = $doc->createElement("LatLonBoundingBox");
		    	$latLonBoundingBox = $layer->appendChild($latLonBoundingBox);
		    	$latLonBoundingBox->setAttribute('minx', $latlon['minx']);
		   	$latLonBoundingBox->setAttribute('miny', $latlon['miny']);
		   	$latLonBoundingBox->setAttribute('maxx', $latlon['maxx']);
		   	$latLonBoundingBox->setAttribute('maxy', $latlon['maxy']);
	    	}	
	}
	#SQL statement to get additional layer information from layer epsg	
	$epsg_sql = "SELECT layer_epsg.epsg, layer_epsg.minx, layer_epsg.miny, " . 
			"layer_epsg.maxy, layer_epsg.maxx FROM layer_epsg " . 
			"WHERE layer_epsg.fkey_layer_id = ".$layerRow['layer_id'];
	$res_espg_sql = db_query($epsg_sql);
	while ($epsg_row = db_fetch_array($res_espg_sql)) {
		#set only first epsg for bbox
		$bbox['epsg'] = str_replace('epsg','EPSG',$epsg_row['epsg']);
		$bbox['minx'] = $epsg_row['minx'];
		$bbox['miny'] = $epsg_row['miny'];
		$bbox['maxx'] = $epsg_row['maxx'];
		$bbox['maxy'] = $epsg_row['maxy'];
		#Creating BoundingBox node
		$boundingBox = $doc->createElement("BoundingBox");
		$boundingBox = $layer->appendChild($boundingBox);
		$boundingBox->setAttribute('SRS', $bbox['epsg']);
		$boundingBox->setAttribute('minx', $bbox['minx']);
		$boundingBox->setAttribute('miny', $bbox['miny']);
		$boundingBox->setAttribute('maxx', $bbox['maxx']);
		$boundingBox->setAttribute('maxy', $bbox['maxy']);
	}
	// create dimension elements for wms 1.1.1
	// select from database
	$layer_dimension_sql = "SELECT * FROM layer_dimension " . "WHERE fkey_layer_id = " . $layerRow ['layer_id'];
	$res_layer_dimension_sql = db_query ( $layer_dimension_sql );
	// store dimension into array
	$layerDimension = array ();
	while ( $layer_dimension_row = db_fetch_array ( $res_layer_dimension_sql ) ) {
		if ($layer_dimension_row ['name'] == "time" && $layer_dimension_row ['units'] == "ISO8601") {
			// create entry
			$dimension = $doc->createElement ( "Dimension" );
			$dimension->setAttribute ( 'name', "time" );
			$dimension->setAttribute ( 'units', "ISO8601" );
			$extent = $doc->createElement ( "Extent" );
			$extent->setAttribute ( 'name', $layer_dimension_row ['name'] );
			if ($layer_dimension_row ['nearestvalue'] != '') {
				$extent->setAttribute ( 'nearestValue', $layer_dimension_row ['nearestvalue'] );
			}
			if ($layer_dimension_row ['default'] != '') {
				$extent->setAttribute ( 'default', $layer_dimension_row ['default'] );
			}
				
			if ($layer_dimension_row ['extent'] != '') {
				$dimensionText = $doc->createTextNode ( $layer_dimension_row ['extent'] );
				$extent->appendChild ( $dimensionText );
			}
			$dimensionNode = $layer->appendChild ( $dimension );
			$extentNode = $layer->appendChild ( $extent );
			// default
			// multiplevalues
			// nearestvalue
			// current
			// extent
		}
	}
	//switch wms version
	switch ($wmsRow['wms_version']) {
		case "1.1.1":
			$metadataUrlType = "TC211";
			break;
		case "1.3.0":
			$metadataUrlType = "ISO19115:2003";
			break;
		default :
			$metadataUrlType = "TC211";
			break;	
	}
	//
	//Creating Metadata and Identifier nodes
	//read out all metadata entries for specific layer
	$subLayerId = $layerRow['layer_id'];
	$sql = <<<SQL

SELECT metadata_id, uuid, link, linktype, md_format, origin, datasetid, datasetid_codespace, md_proxy FROM mb_metadata 
INNER JOIN (SELECT * from ows_relation_metadata 
WHERE fkey_layer_id = $subLayerId ) as relation ON 
mb_metadata.metadata_id = relation.fkey_metadata_id WHERE mb_metadata.origin IN ('capabilities','external','metador','upload')

SQL;
	//$e = new mb_exception("wms.php: layerid: ".$layerRow['layer_id']);
	$i = 0;
	$res_metadata = db_query($sql);
	$namespaceArray = array();
	$AuthorityURLArray = array();
	$IdentifierArray = array();
	$metadataURLArray = array();
	while ($row_metadata = db_fetch_array($res_metadata)) {
		$respOrga = $admin->getOrgaInfoFromRegistry('metadata', $row_metadata['metadata_id'], $wmsRow['wms_owner']);
		$metadataArray['datasetid_codespace'] = $row_metadata["datasetid_codespace"];
		$namespace = $admin->getIdentifierCodespaceFromRegistry($respOrga, $metadataArray);	
		$namespaceArray[] = $namespace;
		//check id datasetid and codespace and $namespace is not the datasetid_codespace from mb_metadata, if not use metadataid
		if ((($row_metadata["datasetid"] == '' || !isset($row_metadata["datasetid"])) && $row_metadata["datasetid_codespace"] !== $namespace) || $row_metadata["origin"] == 'metador') {
			$datasetId = $row_metadata["uuid"];
		} else {
			$datasetId = $row_metadata["datasetid"]; //really datasetid or only second part?
		}
		//create Identifier tags
		$Identifier = $doc->createElement("Identifier");
		$Identifier->setAttribute('authority', md5($namespace));
		//$Identifier = $layer->appendChild($Identifier);
		$IdentifierText = $doc->createTextNode($namespace.$datasetId);
    		$Identifier->appendChild($IdentifierText);
		$IdentifierArray[] = $Identifier;
		//$e = new mb_exception("i: ".$i);
		//push entries into xml structure	
		//check for kind of link - push the right one into the link field	
		switch ($row_metadata['origin']) {
			case 'capabilities':
				$metadataUrl = $doc->createElement("MetadataURL");
				//$metadataUrl = $layer->appendChild($metadataUrl);
				$metadataUrl->setAttribute('type', $row_metadata['linktype']);
				$format = $doc->createElement("Format");
    				$format = $metadataUrl->appendChild($format);
    				$formatText = $doc->createTextNode($row_metadata['md_format']);
    				$formatText = $format->appendChild($formatText);
				$onlineResource = $doc->createElement("OnlineResource");
	    			$onlineResource = $metadataUrl->appendChild($onlineResource);
				$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
				//check if md_proxy is set
				if ($row_metadata['md_proxy'] == 't' || $row_metadata['md_proxy'] == true) {
					$onlineResource->setAttribute("xlink:href", $mapbenderMetadataUrlUrl.$row_metadata['uuid']);
				} else {
					$onlineResource->setAttribute("xlink:href", $row_metadata['link']);
				}
			break;
			case 'external':
				$metadataUrl = $doc->createElement("MetadataURL");
				//$metadataUrl = $layer->appendChild($metadataUrl);
				$metadataUrl->setAttribute('type', $metadataUrlType);
				$format = $doc->createElement("Format");
    				$format = $metadataUrl->appendChild($format);
    				$formatText = $doc->createTextNode("text/xml");
    				$formatText = $format->appendChild($formatText);
				$onlineResource = $doc->createElement("OnlineResource");
	    			$onlineResource = $metadataUrl->appendChild($onlineResource);
				$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
				//check if md_proxy is set
				if ($row_metadata['md_proxy'] == 't' || $row_metadata['md_proxy'] == true) {
					$onlineResource->setAttribute("xlink:href", $mapbenderMetadataUrlUrl.$row_metadata['uuid']);
				} else {
					$onlineResource->setAttribute("xlink:href", $row_metadata['link']);
				}
			break;
			case 'upload':
				$metadataUrl = $doc->createElement("MetadataURL");
				//$metadataUrl = $layer->appendChild($metadataUrl);
				$metadataUrl->setAttribute('type', $metadataUrlType);
				$format = $doc->createElement("Format");
    				$format = $metadataUrl->appendChild($format);
    				$formatText = $doc->createTextNode("text/xml");
    				$formatText = $format->appendChild($formatText);
				$onlineResource = $doc->createElement("OnlineResource");
	    			$onlineResource = $metadataUrl->appendChild($onlineResource);
				$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
				$onlineResource->setAttribute("xlink:href", $row_metadata['link']);
				$onlineResource->setAttribute("xlink:href", $mapbenderMetadataUrlUrl.$row_metadata['uuid']);
			break;
			case 'metador':
				$metadataUrl = $doc->createElement("MetadataURL");
				//$metadataUrl = $layer->appendChild($metadataUrl);
				$metadataUrl->setAttribute('type', $metadataUrlType);
				$format = $doc->createElement("Format");
    				$format = $metadataUrl->appendChild($format);
    				$formatText = $doc->createTextNode("text/xml");
    				$formatText = $format->appendChild($formatText);
				$onlineResource = $doc->createElement("OnlineResource");
	    			$onlineResource = $metadataUrl->appendChild($onlineResource);
				$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
				$onlineResource->setAttribute("xlink:href", $row_metadata['link']);
				$onlineResource->setAttribute("xlink:href", $mapbenderMetadataUrlUrl.$row_metadata['uuid']);
			break;
			default:
				$metadataUrl = $doc->createElement("MetadataURL");
				//$metadataUrl = $layer->appendChild($metadataUrl);
				$metadataUrl->setAttribute('type', $metadataUrlType);
				$format = $doc->createElement("Format");
    				$format = $metadataUrl->appendChild($format);
    				$formatText = $doc->createTextNode("text/xml");
    				$formatText = $format->appendChild($formatText);
				$onlineResource = $doc->createElement("OnlineResource");
	    			$onlineResource = $metadataUrl->appendChild($onlineResource);
				$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
				$onlineResource->setAttribute("xlink:href", $row['link']);
				$onlineResource->setAttribute("xlink:href", "Url not given - please check your registry!");
			break;

		}
		$onlineResource->setAttribute("xlink:type", "simple");
		$metadataURLArray[] = $metadataUrl;
		//Add linkage to Capabilities
		$i++;
	}
	$namespaceUnique = array_unique($namespaceArray);
	foreach ($namespaceUnique as $namespace) {
    		$AuthorityURL = $doc->createElement("AuthorityURL");
    		//$AuthorityURL = $layer->appendChild($AuthorityURL);
    		$AuthorityURL->setAttribute('name', md5($namespace));
    		$AUOnlineResource = $doc->createElement("OnlineResource");
    		$AUOnlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
    		$AUOnlineResource->setAttribute("xlink:type", "simple");
    		$AUOnlineResource->setAttribute("xlink:href", $namespace);
    		$AUOnlineResource = $AuthorityURL->appendChild($AUOnlineResource);
		$AuthorityURLArray[] = $AuthorityURL;
	}

	foreach ($AuthorityURLArray as $AuthorityURL) {
			$AuthorityURL = $layer->appendChild($AuthorityURL);
	}
	foreach ($IdentifierArray as $Identifier) {
			$Identifier = $layer->appendChild($Identifier);
	}
	foreach ($metadataURLArray as $metadataURL) {
			$metadataURL = $layer->appendChild($metadataURL);
	}
	#Creating DataURL Node - use it from database if it will exist!	
	$dataUrl = $doc->createElement("DataURL");
	$dataUrl = $layer->appendChild($dataUrl);
	$format = $doc->createElement("Format");
    	$format = $dataUrl->appendChild($format);
    	$formatText = $doc->createTextNode('text/html');
   	$formatText = $format->appendChild($formatText); 
	if ($layerRow['layer_dataurl'] <> "" AND $layerRow['layer_dataurl'] <> NULL) {
	    	$onlineResource = $doc->createElement("OnlineResource");
	    	$onlineResource = $dataUrl->appendChild($onlineResource);
		$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
		$onlineResource->setAttribute("xlink:href", $layerRow['layer_dataurl']);
		$onlineResource->setAttribute("xlink:type", "simple");
    	}
	else
	{
 		$onlineResource = $doc->createElement("OnlineResource");
             	$onlineResource = $dataUrl->appendChild($onlineResource);
                $onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
                $onlineResource->setAttribute("xlink:href", $mapbenderMetadataUrl.$layerRow['layer_id']);
		$onlineResource->setAttribute("xlink:type", "simple");
	}	
	//Creating Style Node by pulling style and format from database!
	//for each possible style
	//check for number of styles, check for name 'default'
	//$subLayerId 
$sql = <<<SQL

SELECT * FROM layer_style WHERE fkey_layer_id = $subLayerId 

SQL;
	$res_style = db_query($sql);
	while ($row_style = db_fetch_array($res_style)) {
		$style = $doc->createElement("Style");
		$style = $layer->appendChild($style);
		$name = $doc->createElement("Name");
    		$name = $style->appendChild($name);
		if ($row_style['name'] != '') {
    			$nameText = $doc->createTextNode($row_style['name']);
		} else {
			$nameText = $doc->createTextNode('default');
		}
    		$nameText = $name->appendChild($nameText);
		$title = $doc->createElement("Title");
    		$title = $style->appendChild($title);
		if ($row_style['title'] != '') {
    			$titleText = $doc->createTextNode($row_style['title']);
		} else {
			$titleText = $doc->createTextNode('default');
		}
    		$titleText = $title->appendChild($titleText);
		//if server supports get legend graphic	
		if(($wmsRow['wms_getlegendurl'] <> "" AND $wmsRow['wms_getlegendurl'] <> NULL) || ($row_style['legendurl'] <> "" AND $row_style['legendurl'] <> NULL)){	
			$legendUrl = $doc->createElement("LegendURL");
			$legendUrl = $style->appendChild($legendUrl);
			$legendUrl->setAttribute("width", "10" );
			$legendUrl->setAttribute("height", "8" );
			$format = $doc->createElement("Format");
    			$format = $legendUrl->appendChild($format);
			if ($row_style['format'] != '') {
    				$formatText = $doc->createTextNode($row_style['format']);
			} else {
				$formatText = $doc->createTextNode('image/png');
			}
    			$formatText = $format->appendChild($formatText); 
			$onlineResource = $doc->createElement("OnlineResource");
			$onlineResource = $legendUrl->appendChild($onlineResource);
			$onlineResource->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink" );
			if ($wmsRow['wms_getlegendurl'] <> "" AND $wmsRow['wms_getlegendurl'] <> NULL) {
				$onlineResource->setAttribute("xlink:href", $wmsRow['wms_getlegendurl']."version=1.1.1&service=WMS&request=GetLegendGraphic&layer=".$layerRow['layer_name']."&format=image/png");
			} else {
				//other stupid check - TODO: make it better
				if (strpos($row_style['legendurl'], "http:") === false) {
					$onlineResource->setAttribute("xlink:href", $wmsRow['wms_getlegendurl'].$row_style['legendurl']);
				} else {
					$onlineResource->setAttribute("xlink:href", $row_style['legendurl']);
				}
			}
			$onlineResource->setAttribute("xlink:type", "simple");
		}
	}
	//Creating "ScaleHint" node - as used by wms 1.1.1 - if given, both must exist! wms 1.3.0 demands other logic!
	if (($layerRow['layer_minscale'] <> "" && $layerRow['layer_minscale'] <> NULL && $layerRow['layer_minscale'] !== '0') || ($layerRow['layer_maxscale'] <> "" && $layerRow['layer_maxscale'] <> NULL && $layerRow['layer_maxscale'] !== '0')) {
		$scaleHint = $doc->createElement("ScaleHint");
		$scaleHint = $layer->appendChild($scaleHint);
		if ($layerRow['layer_minscale'] <> "" && $layerRow['layer_minscale'] <> NULL && $layerRow['layer_minscale'] !== '0') {
			$scaleHint->setAttribute('min', (floatval($layerRow['layer_minscale'])/2004.3976484406788493955738891127));
		} else {
			$scaleHint->setAttribute('min', 0);
		}
		if ($layerRow['layer_maxscale'] <> "" && $layerRow['layer_maxscale'] <> NULL && $layerRow['layer_maxscale'] !== '0' && $layerRow['layer_maxscale'] !== 0) {
			$scaleHint->setAttribute('max', (floatval($layerRow['layer_maxscale'])/2004.3976484406788493955738891127));
		} else {
			//set default to 1000000000!
			$scaleHint->setAttribute('max', 1000000000 / 2004.3976484406788493955738891127);
		}
	}
	return $layer;	
} //end of function to create single layer object

//*************************************************

function createLayerTree($parent, $withChilds, $layerId, &$layer, $wmsId, $doc, $wms_row, $mapbenderMetadataUrlUrl) {
	$sub_layer_sql = "SELECT * FROM layer WHERE fkey_wms_id = $1 AND layer_parent = $2 ORDER BY layer_pos";
	$v = array($wmsId, $parent);
	$t = array("i","s");
	if (!$withChilds) {	
		$sub_layer_sql .= " AND layer_id = $2";
		array_push($v, $layerId);
		array_push($t, "i");
	}
	//$e = new mb_exception($sub_layer_sql);
	$res_sub_layer_sql = db_prep_query($sub_layer_sql, $v, $t);
	while ($sub_layer_row = db_fetch_array($res_sub_layer_sql)) {
		$subLayer = createLayerElement($doc, $wmsId, $sub_layer_row, $wms_row, $AuthorityName, $mapbenderMetadataUrlUrl);
		//recursive creation
		createLayerTree($sub_layer_row['layer_pos'], $withChilds, $layerId, $subLayer, $wmsId, $doc, $wms_row, $mapbenderMetadataUrlUrl);
		$layer->appendChild($subLayer);
	}
}

//get layer_pos for requested layer id before beginning with tree creation
$layerSql = "SELECT * FROM layer WHERE layer_id = $1";
$v = array($layerId);
$t = array("i");
$res_layer = db_prep_query($layerSql, $v, $t);
$row = db_fetch_array($res_layer);
$layerPos = $row['layer_pos'];
//TODO: root layer is always there but the following pulls only the sublayer and not the requested layer by its own!! - todo: don't use the root layer every time!!! pull the requested layer instead but pull all srs and things which are inherited from the root layer or the parents from this layer - it is not always easy if the hirarchy is nested much - pull all parents - maybe a thing for class_administration!
if ($withChilds) {
	//create layer itself first!
	$subLayer = createLayerElement($doc, $wmsId, $row, $wms_row, $AuthorityName, $mapbenderMetadataUrlUrl);
	
	createLayerTree($layerPos, $withChilds, $layerId, $subLayer, $wms_row['wms_id'], $doc, $wms_row, $mapbenderMetadataUrlUrl);
} else {
	//only create one layer
	$subLayer = createLayerElement($doc, $wmsId, $row, $wms_row, $AuthorityName, $mapbenderMetadataUrlUrl);
}
$layer->appendChild($subLayer);
header("Content-type: application/xhtml+xml; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
echo $doc->saveXml();
?>
