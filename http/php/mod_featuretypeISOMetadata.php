<?php
//http://www.geoportal.rlp.de/mapbender/php/mod_featuretypeISOMetadata.php?SERVICE=WMS&outputFormat=iso19139&Id=24356
// $Id: mod_layerISOMetadata.php 235
// http://www.mapbender.org/index.php/Inspire_Metadata_Editor
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

/*Script to generate a conformant ISO19139 service metadata record for a wfs featuretype/wfs which is registrated in the mapbender database. It works as a webservice. The contact/orga, license, and conformity parts of the metadata generated otf from the mapbender registry. The xml structure will be read from a xml template file and the relevant elements are exchanged via xpath. Some metadata is exchanged after the xml will be initially generated via dom.
The uuid of the registrated featuretype is used as fileidentifier of the metadata record.  
The record will be fulfill the demands of the INSPIRE metadata regulation from 03.12.2008 and the iso19139:2005.
New 2019-09-30: Also metadata for mapbenders LinkedDataProxy (OGC API Features Interface) is generated.
*/
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_connector.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
require_once(dirname(__FILE__) . "/../php/mod_validateInspire.php");
require_once(dirname(__FILE__) . "/../classes/class_iso19139.php");
require_once(dirname(__FILE__) . "/../classes/class_XmlBuilder.php");
require_once(dirname(__FILE__)."/../classes/class_owsConstraints.php");
require_once(dirname(__FILE__)."/../classes/class_qualityReport.php");

if (file_exists(dirname(__FILE__)."/../../conf/linkedDataProxy.json")) {
     $configObject = json_decode(file_get_contents("../../conf/linkedDataProxy.json"));
}
if (isset($configObject) && isset($configObject->behind_rewrite) && $configObject->behind_rewrite == true) {
    $behindRewrite = true;
} else {
    $behindRewrite = false;
}
if (isset($configObject) && isset($configObject->rewrite_path) && $configObject->rewrite_path != "") {
    $rewritePath = $configObject->rewrite_path;
} else {
    $rewritePath = "linkedDataProxy";
}
$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);
global $serviceType;
global $serviceTypeTitle;
global $ogcApiFeaturesUrl;

if (!empty($_SERVER['HTTPS'])) {
    $schema = "https";
} else {
    $schema = "http";
}

if (DEFINED("MAPBENDER_PATH") && MAPBENDER_PATH !== "") {
	$mapbenderServiceUrl = MAPBENDER_PATH."/php/wfs.php?INSPIRE=1&FEATURETYPE_ID=";
} else {
	$mapbenderServiceUrl = $schema."://".$_SERVER['HTTP_HOST']."/mapbender/php/wfs.php?INSPIRE=1&FEATURETYPE_ID=";
}

$admin = new administration();
$serviceType = "wfs";
$serviceTypeTitle = "OGC WFS Interface";
//define the view or table to use as input for metadata generation if this is wished. If not, the data will be directly read from the database tables
$wmsView = "search_wfs_view";
$wmsView = '';
//parse request parameter
//make all parameters available as upper case
foreach($_REQUEST as $key => $val) {
	$_REQUEST[strtoupper($key)] = $val;
}
//validate request params
if (isset($_REQUEST['ID']) & $_REQUEST['ID'] != "") {
	//validate integer
	$testMatch = $_REQUEST["ID"];
	$pattern = '/^[\d]*$/';		
 	if (!preg_match($pattern,$testMatch)){
		// echo 'Id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Id is not valid (integer).<br/>'; 
		die(); 		
 	}
	$recordId = $testMatch;
	$testMatch = NULL;
}

if ($_REQUEST['SERVICETYPE'] == "wfs" || $_REQUEST['SERVICETYPE'] == "ogcapifeatures" |  $_REQUEST['SERVICETYPE'] == "ogcapifeatures_wfs" ) {
    $serviceType = $_REQUEST['SERVICETYPE'];
    if ($serviceType == 'ogcapifeatures' | $_REQUEST['SERVICETYPE'] == "ogcapifeatures_wfs") {
        $serviceTypeTitle = "OGC API Features";
    }
}

if ($_REQUEST['OUTPUTFORMAT'] == "iso19139" || $_REQUEST['OUTPUTFORMAT'] == "rdf" || $_REQUEST['OUTPUTFORMAT'] == 'html') {
	//Initialize XML document
	$iso19139Doc = new DOMDocument('1.0');
	$iso19139Doc->encoding = 'UTF-8';
	$iso19139Doc->preserveWhiteSpace = false;
	$iso19139Doc->formatOutput = true;
	$outputFormat = $_REQUEST['OUTPUTFORMAT'];
    if (!@$iso19139Doc->load(
            dirname(__FILE__) . "/../geoportal/metadata_templates/srv_wfs_inspire.xml",
            LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT | LIBXML_XINCLUDE)) {
        echo 'A xml template is not found.<br/>'; 
        die();
    }
    $xmlBuilder = new XmlBuilder($iso19139Doc);
} else {
	//echo 'outputFormat: <b>'.$_REQUEST['OUTPUTFORMAT'].'</b> is not set or valid.<br/>'; 
	echo 'Parameter outputFormat is not set or valid (iso19139 | rdf | html).<br/>'; 
	die();
}

if (!($_REQUEST['CN'] == "false")) {
	//overwrite outputFormat for special headers:
	switch ($_SERVER["HTTP_ACCEPT"]) {
		case "application/rdf+xml":
			$outputFormat="rdf";
		break;
		case "text/html":
			$outputFormat="html";
		break;
		default:
			$outputFormat="iso19139";
		break;
	}
}

//if validation is requested
//
if (isset($_REQUEST['VALIDATE']) and $_REQUEST['VALIDATE'] != "true") {
	//echo 'validate: <b>'.$_REQUEST['VALIDATE'].'</b> is not valid.<br/>'; 
	echo 'Parameter <b>validate</b> is not valid (true).<br/>'; 
	die();
}

if ($serviceType == "ogcapifeatures" | $serviceType == "ogcapifeatures_wfs") {
	if ($behindRewrite) {
		$ogcApiFeaturesUrl = $schema . "://" . $_SERVER ['HTTP_HOST'] . "/" . $rewritePath;
	} else {
		$ogcApiFeaturesUrl = MAPBENDER_PATH . "/php/mod_linkedDataProxy.php?";
	}
}
//some needfull functions to pull metadata out of the database!
function fillISO19139(XmlBuilder $xmlBuilder, $recordId) {
    	global $wmsView;
	global $mapbenderServiceUrl;
	global $admin;
        global $serviceType;
        global $serviceTypeTitle;
        global $ogcApiFeaturesUrl;
	//read out relevant information from mapbender database:
	if ($wmsView != '') {
		$sql = "SELECT * ";
		$sql .= "FROM ".$wmsView." WHERE layer_id = $1";
	}
	else {
		$sql = "SELECT"
                . " ft.featuretype_id,ft.featuretype_name,ft.featuretype_title,ft.featuretype_abstract,ft.uuid"
                . ",ft.featuretype_latlon_bbox as bbox"
//                . ",ft.layer_pos,ft.layer_parent,ft.layer_minscale,ft.layer_maxscale"                                   ########## ft.layer_pos latlon_bbox ??
//                
                . ",wfs.uuid as wfs_uuid,wfs.wfs_title,wfs.wfs_alternate_title,wfs.wfs_abstract,wfs.wfs_id,wfs.fees,wfs.accessconstraints"
                . ",wfs.individualname,wfs.positionname,wfs.providername,wfs.deliverypoint,wfs.city,wfs.wfs_timestamp"
                . ",wfs.wfs_timestamp_create,wfs.wfs_owner,wfs.administrativearea,wfs.postalcode,wfs.voice"
                . ",wfs.facsimile,wfs.wfs_owsproxy,wfs.electronicmailaddress,wfs.country,wfs.fkey_mb_group_id"
                . ",wfs.wfs_version"
                
//                . ",ft_epsg.minx || ',' || ft_epsg.miny || ',' || ft_epsg.maxx || ',' || ft_epsg.maxy  as bbox"         ########## latlon_bbox
                . " FROM"
                . " wfs"
                . ",wfs_featuretype ft"
//                . ",wfs_featuretype_epsg ft_epsg"
                . " WHERE featuretype_id = $1"
                . " AND ft.fkey_wfs_id = wfs.wfs_id";
	}
        if ($serviceType == "ogcapifeatures_wfs") {
            $sql = "SELECT wfs.uuid as wfs_uuid,wfs.wfs_title,wfs.wfs_alternate_title,wfs.wfs_abstract,wfs.wfs_id,wfs.fees,wfs.accessconstraints"
                . ",wfs.individualname,wfs.positionname,wfs.providername,wfs.deliverypoint,wfs.city,wfs.wfs_timestamp"
                . ",wfs.wfs_timestamp_create,wfs.wfs_owner,wfs.administrativearea,wfs.postalcode,wfs.voice"
                . ",wfs.facsimile,wfs.wfs_owsproxy,wfs.electronicmailaddress,wfs.country,wfs.fkey_mb_group_id"
                . ",wfs.wfs_version FROM wfs WHERE wfs_id = $1";
        }
	$v = array((integer)$recordId);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	$mbMeta = db_fetch_array($res);

	//infos about the registrating department, check first if a special metadata point of contact is defined in the service table
	$departmentMetadata = $admin->getOrgaInfoFromRegistry("wfs", $mbMeta['wfs_id'], $mbMeta['wfs_owner']);
	if (isset($departmentMetadata['mb_group_email']) && $departmentMetadata['mb_group_email'] !== '') {
		$userMetadata['mb_user_email'] = $departmentMetadata['mb_group_email'];
	} else {
		$userMetadata['mb_user_email'] = $departmentMetadata['mb_user_email'];
	}
	//TODO: check if resource is freely available to anonymous user - which are all users who search thru metadata catalogues:
	//$hasPermission=$admin->getLayerPermission($mbMeta['wfs_id'],$mbMeta['layer_name'],PUBLIC_USER); ##################  
    $iso19139 = $xmlBuilder->getDoc();
    $MD_Metadata = $iso19139->documentElement;
    //how to generate fileidentifier 
    switch ($serviceType) {
        case "wfs": 
            $fileIdentifier = isset($mbMeta['uuid']) ? $mbMeta['uuid']: "no id found";
	    break;
	case "ogcapifeatures":
	    $uuidHash = md5($mbMeta['uuid']."ogcapifeatures");
            $fileIdentifier = substr($uuidHash, 0, 8)."-".substr($uuidHash, 7, 4)."-".substr($uuidHash, 11, 4)."-".substr($uuidHash, 15, 4)."-".substr($uuidHash, 19, 12);
	    break;
        case "ogcapifeatures_wfs":
            $uuidHash = md5($mbMeta['uuid']."ogcapifeatures_wfs");
            $fileIdentifier = substr($uuidHash, 0, 8)."-".substr($uuidHash, 7, 4)."-".substr($uuidHash, 11, 4)."-".substr($uuidHash, 15, 4)."-".substr($uuidHash, 19, 12);
            break;   
    }
    $xmlBuilder->addValue($MD_Metadata, './gmd:fileIdentifier/gco:CharacterString', $fileIdentifier);
    
    $xmlBuilder->addValue($MD_Metadata, './gmd:language/gmd:LanguageCode',
            isset($mbMeta['metadata_language']) ? $mbMeta['metadata_language'] : 'ger');
//    $xmlBuilder->addValue($MD_Metadata, './gmd:language/gmd:LanguageCode/@codeList',
//            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode");
    $xmlBuilder->addValue($MD_Metadata, './gmd:language/gmd:LanguageCode/@codeListValue',
            isset($mbMeta['metadata_language']) ? $iso19139->createTextNode($mbMeta['metadata_language']) : 'ger');

    $xmlBuilder->addValue($MD_Metadata, './gmd:characterSet/gmd:MD_CharacterSetCode',
            isset($mbMeta['metadata_language']) ? $mbMeta['metadata_language'] : 'utf8');
//    $xmlBuilder->addValue($MD_Metadata, './gmd:characterSet/gmd:MD_CharacterSetCode/@codeList',
//            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_CharacterSetCode");
    $xmlBuilder->addValue($MD_Metadata, './gmd:characterSet/gmd:MD_CharacterSetCode/@codeListValue',
            isset($mbMeta['metadata_language']) ? $iso19139->createTextNode($mbMeta['metadata_language']) : 'utf8');

    $xmlBuilder->addValue($MD_Metadata, './gmd:hierarchyLevel/gmd:MD_ScopeCode',
            isset($mbMeta['hierarchy_level']) ? $mbMeta['hierarchy_level'] : 'service');
    $xmlBuilder->addValue($MD_Metadata, './gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue',
            isset($mbMeta['hierarchy_level']) ? $mbMeta['hierarchy_level'] : 'service');

    $xmlBuilder->addValue($MD_Metadata, './gmd:contact/gmd:CI_ResponsibleParty/gmd:organisationName/gco:CharacterString',
            isset($departmentMetadata['mb_group_name']) ? $departmentMetadata['mb_group_name'] : 'department not known');

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:contact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/gco:CharacterString',
            isset($mbMeta['deliverypoint']) ? $mbMeta['deliverypoint'] : 'delivery point not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:contact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/gco:CharacterString',
            isset($mbMeta['city']) ? $mbMeta['city'] : 'city not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:contact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/gco:CharacterString',
            isset($mbMeta['postalcode']) ? $mbMeta['postalcode'] : 'postalcode not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:contact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/gco:CharacterString',
            isset($mbMeta['country']) ? $mbMeta['country'] : 'country not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:contact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString',
            isset($mbMeta['electronicmailaddress']) ? $mbMeta['electronicmailaddress'] : 'electronicmailaddress not known');

    $xmlBuilder->addValue($MD_Metadata, './gmd:dateStamp/gco:Date',
            isset($mbMeta['wfs_timestamp']) ? date("Y-m-d",$mbMeta['wfs_timestamp']) : "2000-01-01");

    $xmlBuilder->addValue($MD_Metadata, './gmd:metadataStandardVersion/gco:CharacterString', "2005/PDAM 1");

    /*$xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/@uuid',
            isset($mbMeta['wfs_uuid']) ? $mbMeta['wfs_uuid'] : "wfs uuid not given");*/
    if ($serviceType  == "ogcapifeatures_wfs") {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString',
            isset($mbMeta['wfs_title']) ? $mbMeta['wfs_title']." - ".$serviceTypeTitle : "title not given");
    } else {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString',
            isset($mbMeta['wfs_title']) ? $mbMeta['wfs_title']." - ".$mbMeta['featuretype_title']." - ".$serviceTypeTitle : "title not given");
    }
    
	//Create date elements B5.2-5.4 - format will be only a date - no dateTime given
	//Do things for B 5.2 date of publication
	/*if (isset($mbMeta['wfs_timestamp_create'])) {
        $pos++;
        $chunk = './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:date[' . $pos . ']';
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:date/gco:Date', date('Y-m-d',$mbMeta['wfs_timestamp_create']));
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode', "publication");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeListValue', "publication");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeList',
            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
	}*/
    
    //add optional alternateTitle
    if (isset($mbMeta['wfs_alternate_title']) && $mbMeta['wfs_alternate_title'] !=="") {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:alternateTitle/gco:CharacterString',
            $mbMeta['wfs_alternate_title']);
    }
    $pos = 0;
	//Do things for B 5.3 date of revision
	if (isset($mbMeta['wfs_timestamp'])) {
        $pos++;
        $chunk = './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:date[' . $pos . ']';
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:date/gco:Date', date('Y-m-d',$mbMeta['wfs_timestamp']));
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode', "revision");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeListValue', "revision");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeList',
            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
	}

	//Do things for B 5.4 date of creation
	if (isset($mbMeta['wfs_timestamp_creation'])) {
        $pos++;
        $chunk = './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:date[' . $pos . ']';
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gco:Date', date('Y-m-d',$mbMeta['wfs_timestamp']));
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode', "creation");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeListValue', "creation");
        $xmlBuilder->addValue($MD_Metadata, $chunk . '/gmd:CI_Date/gmd:dateType/gmd:CI_DateTypeCode/@codeList',
            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
	}

    /*$xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/gco:CharacterString',
            isset($mbMeta['uuid']) ? "http://www.geoportal.rlp.de/featuretype/" . $mbMeta['uuid']: "no id found");*/


    if ($serviceType  == "ogcapifeatures_wfs") {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:abstract/gco:CharacterString',
            isset($mbMeta['wfs_abstract']) ? $mbMeta['wfs_abstract'] : "not yet defined");
    } else {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:abstract/gco:CharacterString',
            isset($mbMeta['wfs_abstract']) || isset($mbMeta['featuretype_abstract']) ? $mbMeta['wfs_abstract'].":".$mbMeta['featuretype_abstract'] : "not yet defined");

    }
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:individualName/gco:CharacterString',
            $mbMeta['individualname']);
    
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:organisationName/gco:CharacterString',
            isset($departmentMetadata['mb_group_name']) ? $departmentMetadata['mb_group_name'] : 'department not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/gco:CharacterString',
            isset($mbMeta['deliverypoint']) ? $mbMeta['deliverypoint'] : 'delivery point not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/gco:CharacterString',
            isset($mbMeta['city']) ? $mbMeta['city'] : 'city not known');
    
    //add optional administrativeArea

    if ($serviceType  != "ogcapifeatures_wfs") {
        $sql = "SELECT keyword.keyword FROM keyword, wfs_featuretype_keyword ftk WHERE ftk.fkey_featuretype_id=$1 AND ftk.fkey_keyword_id=keyword.keyword_id";
        $v = array((integer)$recordId);
        $t = array('i');
        $res = db_prep_query($sql, $v, $t);
        $keywordsArray = array();
        while ($row = db_fetch_array($res)) {
                if (isset($row['keyword']) && $row['keyword'] != "") {
                    $keywordsArray[] = $row['keyword'];
                }
        }
    }
    if (defined('ADMINISTRATIVE_AREA') && ADMINISTRATIVE_AREA != '') {
        $adminAreaObj = json_decode(ADMINISTRATIVE_AREA);
        if (in_array($adminAreaObj->keyword, $keywordsArray)) {
            $xmlBuilder->addValue($MD_Metadata,
                './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:administrativeArea/gco:CharacterString', $adminAreaObj->value);
        }
    }
    //	

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/gco:CharacterString',
            isset($mbMeta['postalcode']) ? $mbMeta['postalcode'] : 'postalcode not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/gco:CharacterString',
            isset($mbMeta['country']) ? $mbMeta['country'] : 'country not known');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString',
            isset($mbMeta['electronicmailaddress']) ? $mbMeta['electronicmailaddress'] : "kontakt@geoportal.rlp.de");
    
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL',
            "http://www.mapbender.org");

	//generate keyword part - for services the inspire themes are not applicable!!!
	//read keywords for resource out of the database:
	$sql = "SELECT keyword.keyword FROM keyword, wfs_featuretype_keyword ftk WHERE ftk.fkey_featuretype_id=$1 AND ftk.fkey_keyword_id=keyword.keyword_id";
	$v = array((integer)$recordId);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
        $pos = 1;

	//a special keyword for service type wfs as INSPIRE likes it ;-)
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword['.$pos.']/gco:CharacterString',
            "infoFeatureAccessService");
        if ($serviceType  != "ogcapifeatures_wfs") {
                while ($row = db_fetch_array($res)) {
                        if ($row['keyword'] !== null && $row['keyword'] !== '') {
                                $pos++;
                                $xmlBuilder->addValue($MD_Metadata,
                                './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword['.$pos.']/gco:CharacterString',
                                $row['keyword']);
                        }
                }
        }
	//check opendata license
	if (DEFINED("OPENDATAKEYWORD") && OPENDATAKEYWORD != '') {
	    $sql = "SELECT wfs_id, termsofuse.isopen from wfs LEFT OUTER JOIN";
	    $sql .= "  wfs_termsofuse ON  (wfs.wfs_id = wfs_termsofuse.fkey_wfs_id) LEFT OUTER JOIN termsofuse ON";
	    $sql .= " (wfs_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where wfs.wfs_id = $1";
	    $v = array();
	    $t = array();
	    array_push($t, "i");
	    array_push($v, (int)$mbMeta['wfs_id']);
	    $res = db_prep_query($sql,$v,$t);
	    $row = db_fetch_array($res);
	    if (isset($row['wfs_id'])) {
	        if ($row['isopen'] == "1") {
                    #Ticket #8498: Added position incrementation to prevent overwriting the last keyword
                    $pos++;
	            $xmlBuilder->addValue($MD_Metadata,
	                './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword['.$pos.']/gco:CharacterString',
	                OPENDATAKEYWORD);
	        }
	    }
	}
        if ($serviceType  != "ogcapifeatures_wfs") {
                //pull special keywords from custom categories:	
                $sql = "SELECT custom_category.custom_category_key FROM custom_category, wfs_featuretype_custom_category ftcc WHERE ftcc.fkey_featuretype_id = $1 AND ftcc.fkey_custom_category_id =  custom_category.custom_category_id AND custom_category_hidden = 0";
                $v = array((integer)$recordId);
                $t = array('i');
                $res = db_prep_query($sql,$v,$t);
                $e = new mb_notice("look for custom categories: ");
                while ($row = db_fetch_array($res)) {
                $pos++;
                if ($row['keyword'] !== null && $row['keyword'] !== '') {
                        $xmlBuilder->addValue($MD_Metadata,
                        './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword['.$pos.']/gco:CharacterString',
                        $row['keyword']);
                        }
                }
        }
	//Part B 3 INSPIRE Category
	//do this only if an INSPIRE keyword (Annex I-III) is set
	//Resource Constraints B 8
   /* $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints[1]/gmd:MD_LegalConstraints/gmd:accessConstraints/gmd:MD_RestrictionCode/@codeList',
            "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_RestrictionCode");
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints[1]/gmd:MD_LegalConstraints/gmd:accessConstraints/gmd:MD_RestrictionCode/@codeListValue',
            "otherRestrictions");
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints[1]/gmd:MD_LegalConstraints/gmd:accessConstraints/gmd:MD_RestrictionCode',
            "otherRestrictions");

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints[2]/gmd:MD_LegalConstraints/gmd:useLimitation/gco:CharacterString',
            isset($mbMeta['accessconstraints']) ? $mbMeta['accessconstraints'] : "no conditions apply");
    
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints[2]/gmd:MD_LegalConstraints/gmd:otherConstraints/gco:CharacterString',
            isset($mbMeta['accessconstraints']) & strtoupper($mbMeta['accessconstraints']) != 'NONE' ? $mbMeta['accessconstraints'] : "no constraints");*/

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceType/gco:LocalName',
            'download');
    switch ($serviceType) {
    	case "wfs":
    		$serviceTypeVersion = $mbMeta['wfs_version'];
    		break;
    	case "ogcapifeatures":
    		$serviceTypeVersion = "ogcapifeatures";
    		break;
        case "ogcapifeatures_wfs":
                $serviceTypeVersion = "ogcapifeatures";
                break;    
    	default:
    		$serviceTypeVersion = "undefined";
    		break;
    }
    
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceTypeVersion/gco:CharacterString',
            $serviceTypeVersion);
     
    //Geographical Extent
    $bbox = array("-180.0", "-90.0", "180.0", "90.0");
    if ($serviceType  != "ogcapifeatures_wfs" && isset($mbMeta['bbox']) && $mbMeta['bbox'] != '') {
	$bbox = explode(',',$mbMeta['bbox']);
    }
    //$e = new mb_exception("php/mod_featuretypeISOMetadata.php: bbox: ". json_encode($bbox));
    //$e = new mb_exception("php/mod_featuretypeISOMetadata.php: bbox: ". $bbox[0]);
    //if ($serviceType  != "ogcapifeatures_wfs") {
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude/gco:Decimal',
            $bbox[0]);
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude/gco:Decimal',
            $bbox[2]);
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude/gco:Decimal',
            $bbox[1]);
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude/gco:Decimal',
            $bbox[3]);
    //}
	//read all metadata entries:
        if ($serviceType  != "ogcapifeatures_wfs") {
	$i=0;
	$sql = <<<SQL

SELECT metadata_id, uuid, link, linktype, md_format, origin, datasetid, datasetid_codespace FROM mb_metadata 
INNER JOIN (SELECT * from ows_relation_metadata 
WHERE fkey_featuretype_id = $recordId ) as relation ON 
mb_metadata.metadata_id = relation.fkey_metadata_id WHERE mb_metadata.origin IN ('capabilities','external','metador')

SQL;
        } else {
                $i=0;
                $sql = <<<SQL

                SELECT metadata_id, uuid, link, linktype, md_format, origin, datasetid, datasetid_codespace FROM mb_metadata 
                INNER JOIN (SELECT * from ows_relation_metadata 
                WHERE fkey_featuretype_id IN (SELECT featuretype_id FROM wfs_featuretype WHERE fkey_wfs_id = $recordId) ) as relation ON 
                mb_metadata.metadata_id = relation.fkey_metadata_id WHERE mb_metadata.origin IN ('capabilities','external','metador')
                
                SQL;    
        }
	$res_metadataurl = db_query($sql);
	if ($res_metadataurl != false) {
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:couplingType/srv:SV_CouplingType/@codeList',
            "./resources/codelist/gmxCodelists.xml#SV_CouplingType");
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:couplingType/srv:SV_CouplingType/@codeListValue',
            "tight");
        $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:couplingType/srv:SV_CouplingType',
            "tight");
	}
        
   switch ($serviceType) {
        case "wfs":
	    $url = $mapbenderServiceUrl.$mbMeta['featuretype_id']."&REQUEST=GetCapabilities&SERVICE=WFS&VERSION=".$mbMeta['wfs_version'];
	    $protocol = "OGC:WFS-".$mbMeta['wfs_version']."-http-get-feature";
	    $operation = "GetCapabilities";
            break;
	case "ogcapifeatures":
            $url = $ogcApiFeaturesUrl."/".$mbMeta['wfs_id']."";
	    $protocol = "OGC:API:Features";
	    $operation = "getApiDescription";
            break;
        case "ogcapifeatures_wfs":
            $url = $ogcApiFeaturesUrl."/".$mbMeta['wfs_id']."";
            $protocol = "OGC:API:Features";
            $operation = "getApiDescription";
            break;    
    } 

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:operationName/gco:CharacterString',
            $operation);
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:DCP/srv:DCPList/@codeListValue',
            "WebServices");
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:DCP/srv:DCPList/@codeList',
            "./resources/codelist/gmxCodelists.xml#DCPList");

    //connectPoint **********************************
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint/gmd:CI_OnlineResource/gmd:linkage/gmd:URL',
            $url);

	//fill in operatesOn fields with datasetid if given
	/*INSPIRE example: <srv:operatesOn xlink:href="http://image2000.jrc.it#image2000_1_nl2_multi"/>*/
	/*INSPIRE demands a href for the metadata record!*/
	/*TODO: Exchange HTTP_HOST with other baseurl*/
    $pos = 0;
    while ($row_metadata = db_fetch_array($res_metadataurl)) {
		$uniqueResourceIdentifierCodespace = $admin->getIdentifierCodespaceFromRegistry($departmentMetadata, $row_metadata);
    	if (isset($row_metadata['uuid']) && $row_metadata['uuid'] != "") {
        switch ($row_metadata['origin']) {
            case 'capabilities':
               	$pos++;
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@xlink:href',
                        	"http://" . $_SERVER['HTTP_HOST'] . "/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=" . $row_metadata['uuid']);
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@uuidref',
                        	$uniqueResourceIdentifierCodespace.$row_metadata['datasetid']);
                break;
            case 'metador':
                $pos++;
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@xlink:href',
                        	"http://" . $_SERVER['HTTP_HOST'] . "/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=" . $row_metadata['uuid'].'#spatial_dataset_'.md5($row_metadata['uuid']));
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@uuidref',
                       		$uniqueResourceIdentifierCodespace.$row_metadata['uuid']);
                break;
            case 'external':
                $pos++;
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@xlink:href',
                        	"http://" . $_SERVER['HTTP_HOST'] . "/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=" . $row_metadata['uuid']);
                $xmlBuilder->addValue($MD_Metadata,
                        	'./gmd:identificationInfo/srv:SV_ServiceIdentification/srv:operatesOn['.$pos.']/@uuidref',
                $uniqueResourceIdentifierCodespace.$row_metadata['datasetid']);
                break;
            default:
                break;
        }
    }
    }
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat/gmd:MD_Format/gmd:name/@gco:nilReason',
            'inapplicable');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat/gmd:MD_Format/gmd:version/@gco:nilReason',
            'inapplicable');
    
    //Check if anonymous user has rights to access this featuretype - if not ? which resource should be advertised? TODO
    //initialize url to give back as point of access
    $url = '';
    switch ($serviceType) {
        case "wfs":
	    $url = $mapbenderServiceUrl.$mbMeta['featuretype_id']."&REQUEST=GetCapabilities&SERVICE=WFS&VERSION=".$mbMeta['wfs_version'];
	    $protocol = "OGC:WFS-".$mbMeta['wfs_version']."-http-get-feature";
            break;
	case "ogcapifeatures":
            $url = $ogcApiFeaturesUrl."/".$mbMeta['wfs_id']."/collections/".$mbMeta['featuretype_name'];
	    $protocol = "OGC:API:Features";
            break;
        case "ogcapifeatures_wfs":
            $url = $ogcApiFeaturesUrl."/".$mbMeta['wfs_id'];
            $protocol = "OGC:API:Features";
            break;
    }   
	//GetCapabilities is always available
	//if ($hasPermission) {
		//$url = $mapbenderServiceUrl.$mbMeta['featuretype_id']."&REQUEST=GetCapabilities&SERVICE=WFS&VERSION=".$mbMeta['wfs_version'];
	/*} else {
		$url = "https://".$_SERVER['HTTP_HOST']."/registry/wfs/".$mbMeta['featuretype_id']."?REQUEST=GetCapabilities&SERVICE=WFS&VERSION=".$version;
	}*/
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL',
            $url);

//	//append things which geonetwork needs to invoke service/layer or what else? - Here the name of the layer and the protocol seems to be needed?
//	//a problem will occur, if the link to get map is not the same as the link to get caps? So how can we handle this? It seems to be very silly! 

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:protocol/gco:CharacterString',
            $protocol);
    if ($serviceType  != "ogcapifeatures_wfs") {
        $xmlBuilder->addValue($MD_Metadata,
                './gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:name/gco:CharacterString',
                $mbMeta['featuretype_name']);   

        $xmlBuilder->addValue($MD_Metadata,
                './gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:description/gco:CharacterString',
                $mbMeta['featuretype_abstract']);    
    }
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:scope/gmd:DQ_Scope/gmd:level/gmd:MD_ScopeCode/@codeList',
            'http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:scope/gmd:DQ_Scope/gmd:level/gmd:MD_ScopeCode/@codeListValue',
            'service');
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:scope/gmd:DQ_Scope/gmd:level/gmd:MD_ScopeCode',
            'service');

     //TODO put in the inspire test suites from mb database!
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:title/gco:CharacterString',
            'OpenGIS Web Feature Service 2.0 Interface Standard (OGC 09-025r1 and ISO/DIS 19142)');
    //TODO put in the info from database 
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date',
            date('Y-m-d',$mbMeta['wfs_timestamp']));
    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:explanation/gco:CharacterString',
            'No explanation available'); # Der Dienst ist konform zum WFS 2.0 Standard (nicht zertifiziert). ?

    $xmlBuilder->addValue($MD_Metadata,
            './gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:pass/gco:Boolean',
            'true');
    
    return $xmlBuilder->getDoc()->saveXML();

    //TODO exchange specifications from inspire_legislation.json afterwards like it is done in mod_layerISOMetadata.php
    //
}

//function to give away the xml data
function pushISO19139(XmlBuilder $xmlBuilder, $recordId, $outputFormat) {
	$xml = fillISO19139($xmlBuilder, $recordId);
	//exchange information
	//exchange constraints
	$xml = exchangeConstraintsAndConformity($xml, $recordId);
	proxyFile($xml, $outputFormat);
	die();
}

function xml2rdf($iso19139xml) {
	$iso19139 = new Iso19139();
	$iso19139->createMapbenderMetadataFromXML($iso19139xml);
	return $iso19139->transformToRdf();
}

function xml2html($iso19139xml) {
	$iso19139 = new Iso19139();
	$iso19139->createMapbenderMetadataFromXML($iso19139xml);
	return $iso19139->transformToHtml();
}

function proxyFile($iso19139str,$outputFormat) {
	switch ($outputFormat) {
		case "rdf":
			header("Content-type: application/rdf+xml; charset=UTF-8");
			echo xml2rdf($iso19139str);
		break;
		case "html":
			header("Content-type: text/html; charset=UTF-8");
			echo xml2html($iso19139str);
		break;
		default:
			header("Content-type: text/xml; charset=UTF-8");
			echo $iso19139str;
		break;
	}
	
}

function exchangeConstraintsAndConformity($metadataXml, $recordId) {
	//get wfs_id from database
	$sql = "SELECT fkey_wfs_id FROM wfs_featuretype WHERE featuretype_id = $1 LIMIT 1";
	$v = array((integer)$recordId);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	if ($res !== false) { 
		$row = db_fetch_array($res);
		$serviceId = $row['fkey_wfs_id'];
		//parse XML part
		//echo $metadataXml;
		//die();
		//do parsing with dom, cause we want to alter the xml which have been parsed afterwards
		$metadataDomObject = new DOMDocument();
		libxml_use_internal_errors(true);
		try {
			$metadataDomObject->loadXML($metadataXml);
			if ($metadataDomObject === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("php/mod_featuretypeISOMetadata.php:".$error->message);
    				}
				throw new Exception("php/mod_featuretypeISOMetadata.php:".'Cannot parse metadata with dom!');
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("php/mod_featuretypeISOMetadata.php:".$e->getMessage());
		}
		if ($metadataDomObject !== false) {
			//importing namespaces
			$xpath = new DOMXPath($metadataDomObject);
			$rootNamespace = $metadataDomObject->lookupNamespaceUri($metadataDomObject->namespaceURI);
			$xpath->registerNamespace('defaultns', $rootNamespace); 
			//$xpath->registerNamespace('georss','http://www.georss.org/georss');
			$xpath->registerNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");
			$xpath->registerNamespace("gml", "http://www.opengis.net/gml");
			$xpath->registerNamespace("gco", "http://www.isotc211.org/2005/gco");
			$xpath->registerNamespace("gmd", "http://www.isotc211.org/2005/gmd");
			$xpath->registerNamespace("gts", "http://www.isotc211.org/2005/gts");
			$xpath->registerNamespace("srv", "http://www.isotc211.org/2005/srv");
			$xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
			//licenses
			//pull licence information
			$constraints = new OwsConstraints();
			$constraints->languageCode = "de";
			$constraints->asTable = false;
			$constraints->id = $serviceId;
			$constraints->type = "wfs";
			$constraints->returnDirect = false;
			$constraints->outputFormat='iso19139';
			$tou = $constraints->getDisclaimer();
			//constraints - after descriptive keywords
			if (isset($tou) && $tou !== '' && $tou !== false) {
				//count old resourceConstraints elements
				$resourceConstraintsNodeList = $xpath->query('/gmd:MD_Metadata/gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:resourceConstraints');
				//load xml from constraint generator
				$licenseDomObject = new DOMDocument();
				$licenseDomObject->loadXML($tou);
				$xpathLicense = new DOMXpath($licenseDomObject);
				$licenseNodeList = $xpathLicense->query('/mb:constraints/gmd:resourceConstraints');
				//insert new constraints before first old constraints node
				for ($i = ($licenseNodeList->length)-1; $i >= 0; $i--) {
					$resourceConstraintsNodeList->item(0)->parentNode->insertBefore($metadataDomObject->importNode($licenseNodeList->item($i), true), $resourceConstraintsNodeList->item(0));
				}
				//delete all resourceConstraints from original xml document 
				for ($i = 0; $i <  $resourceConstraintsNodeList->length; $i++) {
    						$temp = $resourceConstraintsNodeList->item($i); //avoid calling a function twice
    						$temp->parentNode->removeChild($temp);
				}
			}
			//exchange conformity declaration if service version is 2.0 and metadata should also be conform TODO filter those featuretypes - not done til now!!!
			$qualityReport = new QualityReport();
			//All services are conform
			$inputXml = $qualityReport->getIso19139Representation("service", "t");
			$reportDomObject = new DOMDocument();
			$reportDomObject->loadXML($inputXml);
			$xpathInput = new DOMXpath($reportDomObject);
			$inputNodeList = $xpathInput->query('/mb:dataqualityreport/gmd:report');
			$conformanceDeclarationNodeList = $xpath->query('/gmd:MD_Metadata/gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report');
			//insert new conformance declaration before first old conformance declaration
			for ($i = ($inputNodeList->length)-1; $i >= 0; $i--) {
				$conformanceDeclarationNodeList->item(0)->parentNode->insertBefore($metadataDomObject->importNode($inputNodeList->item($i), true), $conformanceDeclarationNodeList->item(0));
			}
			//delete all conformance declarations from original xml document 
			for ($i = 0; $i <  $conformanceDeclarationNodeList->length; $i++) {
    					$temp = $conformanceDeclarationNodeList->item($i); //avoid calling a function twice
    					$temp->parentNode->removeChild($temp);
			}
			//test http://localhost/mb_trunk/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=0da11651-aa61-d75a-446c-ea4ea073bc48
		}
		return $metadataDomObject->saveXML();
	}
}

function getEpsgByLayerId ($layer_id) { // from merge_layer.php
	$epsg_list = "";
	$sql = "SELECT DISTINCT epsg FROM layer_epsg WHERE fkey_layer_id = $1";
	$v = array($layer_id);
	$t = array('i');
	$res = db_prep_query($sql, $v, $t);
	while($row = db_fetch_array($res)){
		$epsg_list .= $row['epsg'] . " ";
	}
	return trim($epsg_list);
}
function getEpsgArrayByLayerId ($layer_id) { // from merge_layer.php
	//$epsg_list = "";
	$epsg_array=array();
	$sql = "SELECT DISTINCT epsg FROM layer_epsg WHERE fkey_layer_id = $1";
	$v = array($layer_id);
	$t = array('i');
	$res = db_prep_query($sql, $v, $t);
	$cnt=0;
	while($row = db_fetch_array($res)){
		$epsg_array[$cnt] = $row['epsg'];
		$cnt++;
	}
	return $epsg_array;
}

function guid(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
        return $uuid;
    }
}

//do all the other things which had to be done ;-)
if ($_REQUEST['VALIDATE'] == "true"){
	$xml = fillISO19139($xmlBuilder, $recordId);
	validateInspire($xml);
} else {
	pushISO19139($xmlBuilder, $recordId, $outputFormat); //throw it out to world!
}

?>
