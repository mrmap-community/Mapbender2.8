<?php
#http://www.geoportal.rlp.de/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&Id=uuid
#http://localhost/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&ID=cb567df4-57da-449a-be74-821903a59d45
# $Id: mod_dataISOMetadata.php 235
# http://www.mapbender.org/index.php/Inspire_Metadata_Editor
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
//function to pull out information from mapbenders md_metadata table
//the information is pulled out by uuid cause the uuid is not changed
//there are 3 ways to access the information:
//1. read a link out from database and give the link content back to the requesting client
//2. read the metadata addon information and fill the rest from the wms/mb_user/mb_group/layer table
//3. give back the harvested content of the column data - if conform?

require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_connector.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
require_once(dirname(__FILE__) . "/../classes/class_Uuid.php");
require_once(dirname(__FILE__) . "/../php/mod_validateInspire.php");
require_once(dirname(__FILE__) . "/../classes/class_iso19139.php");
require_once(dirname(__FILE__) . "/../classes/class_owsConstraints.php");
require_once(dirname(__FILE__) . "/../classes/class_qualityReport.php");

$con = db_connect(DBSERVER, OWNER, PW);
db_select_db(DB, $con);

$admin = new administration();
$wmsView = '';
//parse request parameter
//make all parameters available as upper case
foreach ($_REQUEST as $key => $val) {
	$_REQUEST[strtoupper($key)] = $val;
}
//example mapbender uuid: 2494d033-ccdd-cdd7-71c6-3e3c195e1d85

//validate request params
if (isset($_REQUEST['ID']) & $_REQUEST['ID'] != "") {
	//validate uuid
	$testMatch = $_REQUEST["ID"];
	$uuid = new Uuid($testMatch);
	$isUuid = $uuid->isValid();
	if (!$isUuid) {
		//echo 'Id: <b>'.$testMatch.'</b> is not a valid mapbender uuid.<br/>'; 
		echo 'Id is not a valid mapbender uuid.<br/>';
		die();
	}
	$recordId = $testMatch;
	$testMatch = NULL;
}

if ($_REQUEST['OUTPUTFORMAT'] == "iso19139" || $_REQUEST['OUTPUTFORMAT'] == "rdf" || $_REQUEST['OUTPUTFORMAT'] == 'html') {
	//Initialize XML document
	$iso19139Doc = new DOMDocument('1.0');
	$iso19139Doc->encoding = 'UTF-8';
	$iso19139Doc->preserveWhiteSpace = false;
	$iso19139Doc->formatOutput = true;
	$outputFormat = $_REQUEST['OUTPUTFORMAT'];
} else {
	//echo 'outputFormat: <b>'.$_REQUEST['OUTPUTFORMAT'].'</b> is not set or valid.<br/>'; 
	echo 'Parameter outputFormat is not set or valid (iso19139 | rdf | html).<br/>';
	die();
}

if (!($_REQUEST['CN'] == "false")) {
	//overwrite outputFormat for special headers:
	switch ($_SERVER["HTTP_ACCEPT"]) {
		case "application/rdf+xml":
			$outputFormat = "rdf";
			break;
		case "text/html":
			$outputFormat = "html";
			break;
		default:
			$outputFormat = "iso19139";
			break;
	}
}
//if validation is requested
//
if (isset($_REQUEST['VALIDATE']) and $_REQUEST['VALIDATE'] != "true") {
	//echo 'validate: <b>'.$_REQUEST['VALIDATE'].'</b> is not valid (true).<br/>'; 
	echo 'Parameter validate is not valid (true).<br/>';
	die();
}
//get record from mb_metadata and prohibit duplicates:
$sql = <<<SQL

SELECT *, st_xmin(the_geom) || ',' || st_ymin(the_geom) || ',' || st_xmax(the_geom) || ',' || st_ymax(the_geom)  as bbox2d, st_asgml(3, bounding_geom) as bounding_polygon FROM mb_metadata WHERE uuid = $1 ORDER BY lastchanged DESC LIMIT 1

SQL;
$v = array($uuid);
$t = array('s');
$res = db_prep_query($sql, $v, $t);
if (!$res) {
	echo "No record with uuid " . $recordId . " found in mapbender database!";
	die();
}
$row = db_fetch_assoc($res);
$mb_metadata = $row;

if (in_array($mb_metadata['origin'], array("external", "capabilities")) && isset($mb_metadata['link']) && $mb_metadata['link'] != "") {
    //try to update metadata from remote resource 
    $newMetadata = new Iso19139();
    $newMetadata->createFromUrl($mb_metadata['link']);
    $newMetadata->origin = $mb_metadata['origin'];
    $e = new mb_notice("classes/class_iso19139.php: try to update dataset metadata from remote!");
    if ($newMetadata->harvestResult == "1") {
        $e = new mb_exception("classes/class_iso19139.php: harvesting was successful - try to update cache!");
        //check if remote metadata date is newer
        $remoteDate = new DateTime($newMetadata->createDate);
        $cachedDate = new DateTime($mb_metadata['createdate']);
        if ($remoteDate > $cachedDate) {
            $e = new mb_notice("classes/class_iso19139.php: remote metadata is newer than cache - update it!");
            $newMetadata->updateMetadataById($mb_metadata['metadata_id'], false);
            $res = db_prep_query($sql, $v, $t);
            if (!$res) {
                echo "No record with uuid " . $recordId . " found in mapbender database!";
                die();
            }
            $row = db_fetch_assoc($res);
            $mb_metadata = $row; 
        } else {
            $e = new mb_notice("classes/class_iso19139.php: cache is up to date give back cache!");
        }
        //$e = new mb_exception("classes/class_iso19139.php: Date remote: ".$remoteDate->format('Y-m-d')." date cache: ".$cachedDate->format('Y-m-d'));      
    }
}

//parse polygon to add needed gml:id attributes for metadata validation
$mb_metadata['boundingGmlMultiPolygon'] = false;
if (isset($row['bounding_polygon']) && $row['bounding_polygon'] != '') {
	$gml3FromPostgis = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>" . $row['bounding_polygon'];
	libxml_use_internal_errors(true);
	try {
		$gml3 = simplexml_load_string($gml3FromPostgis);
		if ($gml3 === false) {
			foreach (libxml_get_errors() as $error) {
				$err = new mb_exception("php/mod_dataISOMetadata.php:" . $error->message);
			}
			throw new Exception("php/mod_dataISOMetadata.php:" . 'Cannot parse Metadata XML!');
			return false;
		}
	} catch (Exception $e) {
		$err = new mb_exception("php/mod_dataISOMetadata.php:" . $e->getMessage());
		return false;
	}
	//if parsing was successful
	if ($gml3 !== false) {
		$gml3->addAttribute('xmlns:xmlns:gml', 'http://www.opengis.net/gml');
		$gml3->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		$gml3 = simplexml_load_string($gml3->asXML());
		if ($gml3->xpath('//gml:MultiSurface')) {
			$e = new mb_notice("php/mod_dataISOMetadata.php: MultiSurface found!");
			$gml3->addAttribute('gml:gml:id', '_' . md5($gml3->asXML()));
			//count surfaceMembers
			$numberOfSurfaces = count($gml3->xpath('//gml:MultiSurface/gml:surfaceMember'));
			$e = new mb_notice("php/mod_dataISOMetadata.php: number of polygons: " . $numberOfSurfaces);
			for ($k = 0; $k < $numberOfSurfaces; $k++) {
				$polygon = $gml3->xpath('//gml:MultiSurface/gml:surfaceMember[' . (int)($k + 1) . ']/gml:Polygon');
				$polygon = $polygon[0];
				$polygon->addAttribute('gml:gml:id', '_' . md5($polygon->asXML()));
			}
			$mb_metadata['boundingGmlMultiPolygon'] = true;
		} else {
			$e = new mb_notice("php/mod_dataISOMetadata.php: no MultiSurface found - search for polygon!");
			if ($gml3->xpath('//gml:Polygon')) {
				$e = new mb_notice("php/mod_dataISOMetadata.php: number of polygons: 1");
				$gml3->addAttribute('gml:gml:id', '_' . md5($gml3->asXML()));
			}
		}
	}
	$mb_metadata['boundingPolygonGml'] = str_replace("<?xml version=\"1.0\" encoding=\"utf-8\"?>", "", $gml3->asXML());
} else {
	$mb_metadata['boundingPolygonGml'] = false;
}
//convert dates to timestamps
$mb_metadata['createdate'] = strtotime($mb_metadata['createdate']);
$mb_metadata['changedate'] = strtotime($mb_metadata['changedate']);
$mb_metadata['lastchanged'] = strtotime($mb_metadata['lastchanged']);
//check which kind of metadata was found:
switch ($mb_metadata['origin']) {
	case 'metador':
		//generate the xml on the fly - there is no need to store it as xml in the database
		//do the things which had to be done ;-)
		if ($_REQUEST['VALIDATE'] == "true") {
			$xml = fillISO19139($iso19139Doc, $recordId);
			validateInspire($xml); //calls fillISO19139 to!
		} else {
			pushISO19139($iso19139Doc, $recordId, $outputFormat); //throw it out!
		}

		break;
	case 'external':
		if ($mb_metadata['export2csw'] == "t") { //the metadata must have been harvested before!
			if ($mb_metadata['harvestresult'] == 1) {
				if ($_REQUEST['VALIDATE'] != "true") {
					proxyFile($mb_metadata['data'], $outputFormat);
					die();
				} else {
					validateInspire($mb_metadata['data']);
					die();
				}
			} else {
				//send error report - metadata has not been harvested - maybe
				$errMsg = "Metadata should have been harvested, but some unkown error occured";
				$errMsg .= "<br>Please use following URL directly: <a href='" . $mb_metadata['link'] . "'>" . $row['link'] . "</a><br>";
				echo $errMsg;
				die();
			}
		} else {
			//load metadata, from url and send it to requesting client
			$metadataUrlObject = new connector($mb_metadata['link']);
			$metadataXml = $metadataUrlObject->file;
			//TODO: exchange contact and licence information if metadata proxy is activated!
			if ($mb_metadata['md_proxy'] == true || $mb_metadata['md_proxy'] == 't') {
				$metadataXml = exchangeLicenceAndContact($metadataXml, $mb_metadata['metadata_id'], $mb_metadata['fkey_mb_group_id'], $mb_metadata['md_license_source_note']);
			}
			if ($_REQUEST['VALIDATE'] != "true") {
				proxyFile($metadataXml, $outputFormat);
				die();
			} else {
				validateInspire($metadataXml);
				die();
			}
		}
		//if xml has been harvested - push this xml from database, if not just harvest it and push the result
		break;
	case 'upload':
		if ($mb_metadata['harvestresult'] == 1) {
			if ($_REQUEST['VALIDATE'] != "true") {
				proxyFile($mb_metadata['data'], $outputFormat);
				die();
			} else {
				validateInspire($row['data']);
				die();
			}
		} else {
			//send error report - metadata has not been harvested - maybe
			$errMsg = "Metadata should have been harvested, but some unkown error occured";
			echo $errMsg;
			die();
		}
		break;
	case 'capabilities':
		//do the same as for the external case but all from caps should be harvested
		if ($mb_metadata['harvestresult'] == 1 || $mb_metadata['harvestresult'] == '1') {
			if ($mb_metadata['md_proxy'] == true || $mb_metadata['md_proxy'] == 't') {
				$mb_metadata['data'] = exchangeLicenceAndContact($mb_metadata['data'], $mb_metadata['metadata_id'], $mb_metadata['fkey_mb_group_id'], $mb_metadata['md_license_source_note']);
			}
			if ($_REQUEST['VALIDATE'] != "true") {
				proxyFile($mb_metadata['data'], $outputFormat);
				die();
			} else {
				validateInspire($mb_metadata['data']);
				die();
			}
		} else {
			//send error report - metadata has not been harvested - maybe
			$errMsg = "Metadata should have been harvested, but some unkown error occured";
			$errMsg .= "<br>Please use following URL directly: <a href='" . $mb_metadata['link'] . "'>" . $mb_metadata['link'] . "</a><br>";
			echo $errMsg;
			die();
		}
	default:
		break;
}

//function to give away the xml data
function pushISO19139($iso19139Doc, $recordId, $outputFormat)
{
	$xml = fillISO19139($iso19139Doc, $recordId);
	proxyFile($xml, $outputFormat);
	die();
}

function xml2rdf($iso19139xml)
{
	$iso19139 = new Iso19139();
	$iso19139->createMapbenderMetadataFromXML($iso19139xml);
	return $iso19139->transformToRdf();
}

function xml2html($iso19139xml)
{
	$iso19139 = new Iso19139();
	$iso19139->createMapbenderMetadataFromXML($iso19139xml);
	return $iso19139->transformToHtml();
}

function exchangeLicenceAndContact($metadataXml, $metadata_id, $fkeyGroupId, $licenseSourceNote)
{
	//do parsing with dom, cause we want to alter the xml which have been parsed afterwards
	$metadataDomObject = new DOMDocument();
	libxml_use_internal_errors(true);
	try {
		$metadataDomObject->loadXML($metadataXml);
		if ($metadataDomObject === false) {
			foreach (libxml_get_errors() as $error) {
				$err = new mb_exception("php/mod_dataISOMetadata.php:" . $error->message);
			}
			throw new Exception("php/mod_dataISOMetadata.php:" . 'Cannot parse metadata with dom!');
		}
	} catch (Exception $e) {
		$err = new mb_exception("php/mod_dataISOMetadata.php:" . $e->getMessage());
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
		$xpath->registerNamespace("gmx", "http://www.isotc211.org/2005/gmx");
		//$xpath->registerNamespace("srv", "http://www.isotc211.org/2005/srv");
		$xpath->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");

		$xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
		//
		if (isset($fkeyGroupId) && (int)$fkeyGroupId > 0) {
			//select group information
			$sqlDep = "SELECT mb_group_name, mb_group_title, mb_group_id, mb_group_logo_path, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_voicetelephone, mb_group_facsimiletelephone FROM mb_group WHERE mb_group_id = $1 LIMIT 1";
			$vDep = array($fkeyGroupId);
			$tDep = array('i');
			$resDep = db_prep_query($sqlDep, $vDep, $tDep);
			$departmentMetadata = db_fetch_array($resDep);
			//exchange contact information
			$inputXml = '<?xml version="1.0" encoding="UTF-8"?><gmd:contact xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink"><gmd:CI_ResponsibleParty><gmd:organisationName><gco:CharacterString>' . $departmentMetadata['mb_group_title'] . '</gco:CharacterString></gmd:organisationName><gmd:contactInfo><gmd:CI_Contact><gmd:address><gmd:CI_Address><gmd:electronicMailAddress><gco:CharacterString>' . $departmentMetadata['mb_group_email'] . '</gco:CharacterString></gmd:electronicMailAddress></gmd:CI_Address></gmd:address></gmd:CI_Contact></gmd:contactInfo><gmd:role><gmd:CI_RoleCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode" codeListValue="pointOfContact">pointOfContact</gmd:CI_RoleCode></gmd:role></gmd:CI_ResponsibleParty></gmd:contact>';
			$contactDomObject = new DOMDocument();
			$contactDomObject->loadXML($inputXml);
			$xpathInput = new DOMXpath($contactDomObject);
			$inputNodeList = $xpathInput->query('/gmd:contact');
			$inputNode = $inputNodeList->item(0);
			//get contact node or node list
			$contactNodeList = $xpath->query('/gmd:MD_Metadata/gmd:contact');
			//test to delete all contact nodes more than one
			for ($i = 0; $i < $contactNodeList->length; $i++) {
				if ($i == 0) {
					$temp = $contactNodeList->item($i);
					$temp->parentNode->replaceChild($metadataDomObject->importNode($inputNode, true), $temp);
				}
				if ($i > 0) {
					$temp = $contactNodeList->item($i); //avoid calling a function twice
					$temp->parentNode->removeChild($temp);
				}
			}
		}
		//licenses
		//pull licence information
		$constraints = new OwsConstraints();
		$constraints->languageCode = "de";
		$constraints->asTable = false;
		$constraints->id = $metadata_id;
		$constraints->type = "metadata";
		$constraints->returnDirect = false;
		$constraints->outputFormat = 'iso19139';
		$tou = $constraints->getDisclaimer();
		//constraints - after descriptive keywords
		if (isset($tou) && $tou !== '' && $tou !== false) {
			$resourceConstraintsNodeList = $xpath->query('/gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:resourceConstraints');
			$arrayResourceConstraintsNodeList = (array)$resourceConstraintsNodeList;
			//TODO - if this is empty - create a new entry
			if ($resourceConstraintsNodeList->length > 0) {
				//load xml from constraint generator
				$licenseDomObject = new DOMDocument();
				$licenseDomObject->loadXML($tou);
				$xpathLicense = new DOMXpath($licenseDomObject);
				$licenseNodeList = $xpathLicense->query("/mb:constraints/gmd:resourceConstraints");
				//insert new constraints before first old constraints node
				for ($i = ($licenseNodeList->length) - 1; $i >= 0; $i--) {
					$resourceConstraintsNodeList->item(0)->parentNode->insertBefore($metadataDomObject->importNode($licenseNodeList->item($i), true), $resourceConstraintsNodeList->item(0));
				}
				//delete all resourceConstraints from original xml document 
				for ($i = 0; $i <  $resourceConstraintsNodeList->length; $i++) {
					$temp = $resourceConstraintsNodeList->item($i); //avoid calling a function twice
					$temp->parentNode->removeChild($temp);
				}
			} else {
				$e = new mb_exception("constraints list is empty - please check!");
			}
		}
	}
	return $metadataDomObject->saveXML();
}

function proxyFile($iso19139str, $outputFormat)
{
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

//some needfull functions to pull metadata out of the database!
function fillISO19139($iso19139, $recordId)
{
	global $admin;
	global $mb_metadata;
	//new for 2018 - get all information from mb_metadata instead of crawling it from services
	//infos about the registrating department, check first if a special metadata point of contact is defined in the service table
	$departmentMetadata = $admin->getOrgaInfoFromRegistry("metadata", $mb_metadata['metadata_id'], $mb_metadata['fkey_mb_user_id']);
	if (isset($departmentMetadata['mb_group_email']) && $departmentMetadata['mb_group_email'] !== '') {
		$userMetadata['mb_user_email'] = $departmentMetadata['mb_group_email'];
	} else {
		$userMetadata['mb_user_email'] = $departmentMetadata['mb_user_email'];
	}
	//schemas for metadata:
	/*
	<gmd:MD_Metadata xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd" xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink">
	*/
	//Creating the "MD_Metadata" node
	$MD_Metadata = $iso19139->createElement("gmd:MD_Metadata");
	$MD_Metadata = $iso19139->appendChild($MD_Metadata);
	$MD_Metadata->setAttribute("xsi:schemaLocation", "http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd");
	$MD_Metadata->setAttribute("xmlns:gmd", "http://www.isotc211.org/2005/gmd");
	#$MD_Metadata->setAttribute("xmlns:gmd", "http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd");
	$MD_Metadata->setAttribute("xmlns:gco", "http://www.isotc211.org/2005/gco");
	$MD_Metadata->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	$MD_Metadata->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
	$MD_Metadata->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");

	//generate fileidentifier part (metadata record identification) 
	$identifier = $iso19139->createElement("gmd:fileIdentifier");
	$identifierString = $iso19139->createElement("gco:CharacterString");
	if (isset($mb_metadata['uuid'])) {
		$identifierText = $iso19139->createTextNode($mb_metadata['uuid']);
	} else {
		$identifierText = $iso19139->createTextNode("no id found");
	}
	$identifierString->appendChild($identifierText);
	$identifier->appendChild($identifierString);
	$MD_Metadata->appendChild($identifier);
	//generate language part B 10.3 (if available) of the inspire metadata regulation
	$language = $iso19139->createElement("gmd:language");
	$languagecode = $iso19139->createElement("gmd:LanguageCode");
	if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
		switch (INSPIRE_METADATA_SPEC) {
			case "2.0.1":
				$languagecode->setAttribute("codeList", "http://www.loc.gov/standards/iso639-2/");
				break;
			case "1.3":
				$languagecode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode");
				break;
		}
	} else {
		$languagecode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode");
	}
	if (isset($mb_metadata['metadata_language'])) {
		$languageText = $iso19139->createTextNode($mb_metadata['metadata_language']);
		$languagecode->setAttribute("codeListValue", $mb_metadata['metadata_language']);
	} else {
		$languageText = $iso19139->createTextNode("ger");
		$languagecode->setAttribute("codeListValue", "ger");
	}
	$languagecode->appendChild($languageText);
	$language->appendChild($languagecode);
	$language = $MD_Metadata->appendChild($language);

	//generate Characterset TODO: alter this to utf8 and add new element to data identification
	$characterSet = $iso19139->createElement("gmd:characterSet");
	$characterSetCode = $iso19139->createElement("gmd:MD_CharacterSetCode");
	$characterSetCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_CharacterSetCode");
	$characterSetCode->setAttribute("codeListValue", "utf8");
	$characterSet->appendChild($characterSetCode);
	$characterSet = $MD_Metadata->appendChild($characterSet);

	#generate MD_Scope part B 1.3 (if available)
	$hierarchyLevel = $iso19139->createElement("gmd:hierarchyLevel");
	$scopecode = $iso19139->createElement("gmd:MD_ScopeCode");
	$scopecode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode");
	if (isset($mb_metadata['type'])) {
		$scopecode->setAttribute("codeListValue", $mb_metadata['type']);
		$scopeText = $iso19139->createTextNode($mb_metadata['type']);
	} else {
		$scopecode->setAttribute("codeListValue", "dataset");
		$scopeText = $iso19139->createTextNode("dataset");
	}
	$scopecode->appendChild($scopeText);
	$hierarchyLevel->appendChild($scopecode);
	$hierarchyLevel = $MD_Metadata->appendChild($hierarchyLevel);

	#Part B 10.1 responsible party for the resource
	$contact = $iso19139->createElement("gmd:contact");
	$CI_ResponsibleParty = $iso19139->createElement("gmd:CI_ResponsibleParty");
	$organisationName = $iso19139->createElement("gmd:organisationName");
	$organisationName_cs = $iso19139->createElement("gco:CharacterString");
	if (isset($departmentMetadata['mb_group_name'])) {
		$organisationNameText = $iso19139->createTextNode($departmentMetadata['mb_group_name']);
	} else {
		$organisationNameText = $iso19139->createTextNode('department not known');
	}
	$contactInfo = $iso19139->createElement("gmd:contactInfo");
	$CI_Contact = $iso19139->createElement("gmd:CI_Contact");
	$address = $iso19139->createElement("gmd:address");
	$CI_Address = $iso19139->createElement("gmd:CI_Address");
	$electronicMailAddress = $iso19139->createElement("gmd:electronicMailAddress");
	$electronicMailAddress_cs = $iso19139->createElement("gco:CharacterString");
	if (isset($userMetadata['mb_user_email']) && $userMetadata['mb_user_email'] != '') {
		//get email address from ows service metadata out of mapbender database	
		$electronicMailAddressText = $iso19139->createTextNode($userMetadata['mb_user_email']);
	} else {
		$electronicMailAddressText = $iso19139->createTextNode('kontakt@geoportal.rlp.de');
	}
	$role = $iso19139->createElement("gmd:role");
	$CI_RoleCode = $iso19139->createElement("gmd:CI_RoleCode");
	$CI_RoleCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode");
	$CI_RoleCode->setAttribute("codeListValue", "pointOfContact");
	$CI_RoleCodeText = $iso19139->createTextNode("pointOfContact");
	$organisationName_cs->appendChild($organisationNameText);
	$organisationName->appendChild($organisationName_cs);
	$CI_ResponsibleParty->appendChild($organisationName);
	$electronicMailAddress_cs->appendChild($electronicMailAddressText);
	$electronicMailAddress->appendChild($electronicMailAddress_cs);
	$CI_Address->appendChild($electronicMailAddress);
	$address->appendChild($CI_Address);
	$CI_Contact->appendChild($address);
	$contactInfo->appendChild($CI_Contact);
	$CI_RoleCode->appendChild($CI_RoleCodeText);
	$role->appendChild($CI_RoleCode);
	$CI_ResponsibleParty->appendChild($contactInfo);
	$CI_ResponsibleParty->appendChild($role);
	$contact->appendChild($CI_ResponsibleParty);
	$MD_Metadata->appendChild($contact);

	#generate dateStamp part B 10.2 (if available)
	$dateStamp = $iso19139->createElement("gmd:dateStamp");
	$mddate = $iso19139->createElement("gco:Date");
	if (isset($mb_metadata['lastchanged'])) {
		$mddateText = $iso19139->createTextNode(date("Y-m-d", $mb_metadata['lastchanged']));
	} else {
		$mddateText = $iso19139->createTextNode("2000-01-01");
	}
	$mddate->appendChild($mddateText);
	$dateStamp->appendChild($mddate);
	$dateStamp = $MD_Metadata->appendChild($dateStamp);

	//standard definition - everytime the same
	$metadataStandardName = $iso19139->createElement("gmd:metadataStandardName");
	$metadataStandardVersion = $iso19139->createElement("gmd:metadataStandardVersion");
	$metadataStandardNameText = $iso19139->createElement("gco:CharacterString");
	$metadataStandardVersionText = $iso19139->createElement("gco:CharacterString");
	$metadataStandardNameTextString = $iso19139->createTextNode("ISO19115");
	$metadataStandardVersionTextString = $iso19139->createTextNode("2003/Cor.1:2006");
	$metadataStandardNameText->appendChild($metadataStandardNameTextString);
	$metadataStandardVersionText->appendChild($metadataStandardVersionTextString);
	$metadataStandardName->appendChild($metadataStandardNameText);
	$metadataStandardVersion->appendChild($metadataStandardVersionText);
	$MD_Metadata->appendChild($metadataStandardName);
	$MD_Metadata->appendChild($metadataStandardVersion);
	#fill in reference system info - maybe this is not a good position for it

	$gmd_referenceSystemInfo = $iso19139->createElement("gmd:referenceSystemInfo");
	$gmd_MD_ReferenceSystem = $iso19139->createElement("gmd:MD_ReferenceSystem");
	$gmd_referenceSystemIdentifier = $iso19139->createElement("gmd:referenceSystemIdentifier");
	$gmd_RS_Identifier = $iso19139->createElement("gmd:RS_Identifier");
	$gmd_authority = $iso19139->createElement("gmd:authority");
	$gmd_CI_Citation = $iso19139->createElement("gmd:CI_Citation");
	$gmd_title = $iso19139->createElement("gmd:title");
	$gmd_title_cs = $iso19139->createElement("gco:CharacterString");
	$gmd_title_Text = $iso19139->createTextNode("European Petroleum Survey Group (EPSG) Geodetic Parameter Registry");

	$gmd_title_cs->appendChild($gmd_title_Text);
	$gmd_title->appendChild($gmd_title_cs);
	$gmd_CI_Citation->appendChild($gmd_title);

	$gmd_date = $iso19139->createElement("gmd:date");
	$gmd_CI_Date = $iso19139->createElement("gmd:CI_Date");
	$gmd_date2 = $iso19139->createElement("gmd:date");
	$gco_Date = $iso19139->createElement("gco:Date");
	$gco_DateText = $iso19139->createTextNode("2008-11-12");

	$gmd_dateType = $iso19139->createElement("gmd:dateType");
	$gmd_CI_DateTypeCode = $iso19139->createElement("gmd:CI_DateTypeCode");
	$gmd_CI_DateTypeCode_Text = $iso19139->createTextNode("publication");
	$gmd_CI_DateTypeCode->setAttribute("codeList", "http://www.isotc211.org/2005/resources/codelist/gmxCodelists.xml#CI_DateTypeCode");
	$gmd_CI_DateTypeCode->setAttribute("codeListValue", "publication");

	$gmd_CI_DateTypeCode->appendChild($gmd_CI_DateTypeCode_Text);
	$gmd_dateType->appendChild($gmd_CI_DateTypeCode);

	$gco_Date->appendChild($gco_DateText);
	$gmd_date2->appendChild($gco_Date);

	$gmd_CI_Date->appendChild($gmd_date2);
	$gmd_CI_Date->appendChild($gmd_dateType);

	$gmd_date->appendChild($gmd_CI_Date);
	$gmd_CI_Citation->appendChild($gmd_date);

	$gmd_citedResponsibleParty = $iso19139->createElement("gmd:citedResponsibleParty");
	$gmd_CI_ResponsibleParty = $iso19139->createElement("gmd:CI_ResponsibleParty");
	$gmd_organisationName = $iso19139->createElement("gmd:organisationName");
	$gmd_organisationName_cs = $iso19139->createElement("gco:CharacterString");
	$gmd_organisationName_Text = $iso19139->createTextNode("European Petroleum Survey Group");

	$gmd_organisationName_cs->appendChild($gmd_organisationName_Text);
	$gmd_organisationName->appendChild($gmd_organisationName_cs);
	$gmd_CI_ResponsibleParty->appendChild($gmd_organisationName);

	$gmd_contactInfo = $iso19139->createElement("gmd:contactInfo");
	$gmd_CI_Contact = $iso19139->createElement("gmd:CI_Contact");
	$gmd_onlineResource = $iso19139->createElement("gmd:onlineResource");
	$gmd_CI_OnlineResource = $iso19139->createElement("gmd:CI_OnlineResource");
	$gmd_linkage = $iso19139->createElement("gmd:linkage");
	$gmd_URL = $iso19139->createElement("gmd:URL");
	$gmd_URL_Text = $iso19139->createTextNode("http://www.epsg-registry.org/");

	$gmd_URL->appendChild($gmd_URL_Text);
	$gmd_linkage->appendChild($gmd_URL);
	$gmd_CI_OnlineResource->appendChild($gmd_linkage);
	$gmd_onlineResource->appendChild($gmd_CI_OnlineResource);
	$gmd_CI_Contact->appendChild($gmd_onlineResource);
	$gmd_contactInfo->appendChild($gmd_CI_Contact);

	$gmd_CI_ResponsibleParty->appendChild($gmd_contactInfo);

	$gmd_role = $iso19139->createElement("gmd:role");
	$gmd_role->setAttribute("gco:nilReason", "missing");

	$gmd_CI_ResponsibleParty->appendChild($gmd_role);

	$gmd_citedResponsibleParty->appendChild($gmd_CI_ResponsibleParty);
	$gmd_CI_Citation->appendChild($gmd_citedResponsibleParty);
	$gmd_authority->appendChild($gmd_CI_Citation);

	$gmd_RS_Identifier->appendChild($gmd_authority);

	$gmd_code = $iso19139->createElement("gmd:code");
	$gmd_code_cs = $iso19139->createElement("gco:CharacterString");
	$gmd_code_text = $iso19139->createTextNode("urn:ogc:def:crs:" . $mb_metadata['ref_system']);

	$gmd_code_cs->appendChild($gmd_code_text);
	$gmd_code->appendChild($gmd_code_cs);
	$gmd_RS_Identifier->appendChild($gmd_code);

	$gmd_version = $iso19139->createElement("gmd:version");
	$gmd_version_cs = $iso19139->createElement("gco:CharacterString");
	$gmd_version_text = $iso19139->createTextNode("6.18.3");

	$gmd_version_cs->appendChild($gmd_version_text);
	$gmd_version->appendChild($gmd_version_cs);
	$gmd_RS_Identifier->appendChild($gmd_version);

	$gmd_referenceSystemIdentifier->appendChild($gmd_RS_Identifier);
	$gmd_MD_ReferenceSystem->appendChild($gmd_referenceSystemIdentifier);
	$gmd_referenceSystemInfo->appendChild($gmd_MD_ReferenceSystem);

	$MD_Metadata->appendChild($gmd_referenceSystemInfo);

	#do the things for identification
	$identificationInfo = $iso19139->createElement("gmd:identificationInfo");
	$MD_DataIdentification = $iso19139->createElement("gmd:MD_DataIdentification");

	$MD_DataIdentification->setAttribute("id", "spatial_dataset_" . md5($mb_metadata['uuid']));
	//add http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_SpatialRepresentationTypeCode

	$spatialRepresentationType = $iso19139->createElement("gmd:spatialRepresentationType");
	$MD_SpatialRepresentationTypeCode = $iso19139->createElement("gmd:MD_SpatialRepresentationTypeCode");
	$MD_SpatialRepresentationTypeCode->setAttribute("codeList", "http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_SpatialRepresentationTypeCode");
	if (in_array($mb_metadata['format'], array("GeoTIFF"))) {
		$MD_SpatialRepresentationTypeCode->setAttribute("codeListValue", "grid");
	} else {
		$MD_SpatialRepresentationTypeCode->setAttribute("codeListValue", "vector");
	}
	$spatialRepresentationType->appendChild($MD_SpatialRepresentationTypeCode);
	$citation = $iso19139->createElement("gmd:citation");
	$CI_Citation = $iso19139->createElement("gmd:CI_Citation");

	#create nodes for things which are defined
	#Create Resource title element B 1.1
	$title = $iso19139->createElement("gmd:title");
	$title_cs = $iso19139->createElement("gco:CharacterString");
	if (isset($mb_metadata['title'])) {
		$titleText = $iso19139->createTextNode($mb_metadata['title']);
	} else {
		$titleText = $iso19139->createTextNode("title not given");
	}
	$title_cs->appendChild($titleText);
	$title->appendChild($title_cs);
	$CI_Citation->appendChild($title);

	#Do things for B 5.3 date of revision
	//this should be created from the information of maintenance if available
	//some initialization for the temporal extent:
	$beginPositionValue = date('Y-m-d', strtotime($mb_metadata['tmp_reference_1']));
	$endPositionValue = date('Y-m-d', strtotime($mb_metadata['tmp_reference_2']));
	$dateOfLastRevision = date('Y-m-d');

	if (isset($mb_metadata['update_frequency']) && $mb_metadata['update_frequency'] != "") {
		switch ($mb_metadata['update_frequency']) {
			case ('continual'):
				//set value to now
				$endPositionValue = date('Y-m-d');
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('daily'):
				//set value to now - one day
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 1,   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('weekly'):
				//set value to now - one week
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7,   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('fortnightly'):
				//set value to now - two weeks
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 14,   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('monthly'):
				//set value to now - one month
				$endPositionValue =  date('Y-m-d', mktime(0, 0, 0, date("m") - 1, date("d"),   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('quarterly'):
				//set value to now - 3 months
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m") - 3, date("d"),   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('biannually'):
				//set value to now - half a year
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m") - 6, date("d"),   date("Y")));
				$dateOfLastRevision = $endPositionValue;
				break;
			case ('annually'):
				//set value to now - one year
				$endPositionValue = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"),   date("Y") - 1));
				$dateOfLastRevision = $endPositionValue;
				break;
			default:
				break;
		}

		$date1 = $iso19139->createElement("gmd:date");
		$CI_Date = $iso19139->createElement("gmd:CI_Date");
		$date2 = $iso19139->createElement("gmd:date");
		$gcoDate = $iso19139->createElement("gco:Date");
		$dateType = $iso19139->createElement("gmd:dateType");
		$dateTypeCode = $iso19139->createElement("gmd:CI_DateTypeCode");
		$dateTypeCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
		$dateTypeCode->setAttribute("codeListValue", "revision");
		$dateTypeCodeText = $iso19139->createTextNode('revision');
		$dateText = $iso19139->createTextNode($dateOfLastRevision);
		$dateTypeCode->appendChild($dateTypeCodeText);
		$dateType->appendChild($dateTypeCode);
		$gcoDate->appendChild($dateText);
		$date2->appendChild($gcoDate);
		$CI_Date->appendChild($date2);
		$CI_Date->appendChild($dateType);
		$date1->appendChild($CI_Date);
		$CI_Citation->appendChild($date1);
	}

	$identifier = $iso19139->createElement("gmd:identifier");
	$md_identifier = $iso19139->createElement("gmd:MD_Identifier");

	$code = $iso19139->createElement("gmd:code");
	$code_cs = $iso19139->createElement("gco:CharacterString");

	if (isset($departmentMetadata['mb_group_registry_url']) && $departmentMetadata['mb_group_registry_url'] !== "") {
		if (substr($departmentMetadata['mb_group_registry_url'], -1) !== '/') {
			$uniqueResourceIdentifierCodespace = $departmentMetadata['mb_group_registry_url'] . '/';
		} else {
			$uniqueResourceIdentifierCodespace =  $departmentMetadata['mb_group_registry_url'];
		}
	} else {
		if (isset($departmentMetadata['mb_group_homepage']) && $departmentMetadata['mb_group_homepage'] !== "") {
			if (substr($departmentMetadata['mb_group_homepage'], -1) !== '/') {
				$uniqueResourceIdentifierCodespace = $departmentMetadata['mb_group_homepage'] . '/' . 'registry/spatial/dataset/';
			} else {
				$uniqueResourceIdentifierCodespace =  $departmentMetadata['mb_group_homepage'] . 'registry/spatial/dataset/';
			}
		} else {
			if (defined('METADATA_DEFAULT_CODESPACE')) {
				if (substr($departmentMetadata['mb_group_homepage'], -1) !== '/') {
					$uniqueResourceIdentifierCodespace = METADATA_DEFAULT_CODESPACE . '/' . 'registry/spatial/dataset/';
				} else {
					$uniqueResourceIdentifierCodespace =  METADATA_DEFAULT_CODESPACE . 'registry/spatial/dataset/';
				}
			} else {
				$uniqueResourceIdentifierCodespace = "http://www.mapbender.org/registry/spatial/dataset/";
			}
		}
	}

	$codeText = $iso19139->createTextNode($uniqueResourceIdentifierCodespace . $mb_metadata['uuid']);

	$code_cs->appendChild($codeText);
	$code->appendChild($code_cs);
	$md_identifier->appendChild($code);

	$identifier->appendChild($md_identifier);
	$CI_Citation->appendChild($identifier);

	$citation->appendChild($CI_Citation);
	$MD_DataIdentification->appendChild($citation);

	#Create part for abstract B 1.2
	$abstract = $iso19139->createElement("gmd:abstract");
	$abstract_cs = $iso19139->createElement("gco:CharacterString");
	if (isset($mb_metadata['abstract'])) {
		$abstractText = $iso19139->createTextNode($mb_metadata['abstract']);
	} else {
		$abstractText = $iso19139->createTextNode("not yet defined");
	}
	$abstract_cs->appendChild($abstractText);
	$abstract->appendChild($abstract_cs);
	$MD_DataIdentification->appendChild($abstract);

	#Create part for point of contact for data identification - use contact from service provider! 
	#Define relevant objects
	$pointOfContact = $iso19139->createElement("gmd:pointOfContact");
	$CI_ResponsibleParty = $iso19139->createElement("gmd:CI_ResponsibleParty");
	$organisationName = $iso19139->createElement("gmd:organisationName");
	$orgaName_cs = $iso19139->createElement("gco:CharacterString");
	$contactInfo = $iso19139->createElement("gmd:contactInfo");
	$CI_Contact = $iso19139->createElement("gmd:CI_Contact");
	$address_1 = $iso19139->createElement("gmd:address");
	$CI_Address = $iso19139->createElement("gmd:CI_Address");
	$electronicMailAddress = $iso19139->createElement("gmd:electronicMailAddress");
	$email_cs = $iso19139->createElement("gco:CharacterString");
	$role = $iso19139->createElement("gmd:role");
	$CI_RoleCode = $iso19139->createElement("gmd:CI_RoleCode");
	$CI_RoleCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode");
	$CI_RoleCode->setAttribute("codeListValue", "pointOfContact");
	if (isset($mb_metadata['responsible_party_name']) && $mb_metadata['responsible_party_name'] !== '') {
		$resOrgaText = $iso19139->createTextNode($mb_metadata['responsible_party_name']);
	} else {
		if (isset($departmentMetadata['mb_group_name'])) {
			$resOrgaText = $iso19139->createTextNode($departmentMetadata['mb_group_name']);
		} else {
			$resOrgaText = $iso19139->createTextNode("not yet defined");
		}
	}
	if (isset($mb_metadata['responsible_party_email']) && $mb_metadata['responsible_party_email'] !== '') {
		$resMailText = $iso19139->createTextNode($mb_metadata['responsible_party_email']);
	} else {
		if (isset($userMetadata['mb_user_email'])) {
			$resMailText = $iso19139->createTextNode($userMetadata['mb_user_email']);
		} else {
			$resMailText = $iso19139->createTextNode("kontakt@geoportal.rlp.de");
		}
	}
	$resRoleText = $iso19139->createTextNode("pointOfContact");
	$orgaName_cs->appendChild($resOrgaText);
	$organisationName->appendChild($orgaName_cs);
	$CI_ResponsibleParty->appendChild($organisationName);
	$email_cs->appendChild($resMailText);
	$electronicMailAddress->appendChild($email_cs);
	$CI_Address->appendChild($electronicMailAddress);
	$address_1->appendChild($CI_Address);
	$CI_Contact->appendChild($address_1);
	$contactInfo->appendChild($CI_Contact);
	$CI_ResponsibleParty->appendChild($contactInfo);
	$CI_RoleCode->appendChild($resRoleText);
	$role->appendChild($CI_RoleCode);
	$CI_ResponsibleParty->appendChild($role);
	$pointOfContact->appendChild($CI_ResponsibleParty);
	$MD_DataIdentification->appendChild($pointOfContact);

	if (isset($mb_metadata['update_frequency']) && $mb_metadata['update_frequency'] != "") {
		$resourceMaintenance = $iso19139->createElement("gmd:resourceMaintenance");
		$MD_MaintenanceInformation = $iso19139->createElement("gmd:MD_MaintenanceInformation");
		$maintenanceAndUpdateFrequency = $iso19139->createElement("gmd:maintenanceAndUpdateFrequency");
		$MD_MaintenanceFrequencyCode = $iso19139->createElement("gmd:MD_MaintenanceFrequencyCode");
		$MD_MaintenanceFrequencyCode->setAttribute("codeListValue", $mb_metadata['update_frequency']);
		$MD_MaintenanceFrequencyCode->setAttribute("codeList", "http://www.isotc211.org/2005/resources/codeList.xml#MD_MaintenanceFrequencyCode");
		$maintenanceAndUpdateFrequency->appendChild($MD_MaintenanceFrequencyCode);
		$MD_MaintenanceInformation->appendChild($maintenanceAndUpdateFrequency);
		$resourceMaintenance->appendChild($MD_MaintenanceInformation);
		$MD_DataIdentification->appendChild($resourceMaintenance);
	}
	//generate graphic overview part from preview_image url in mb_metadata table
	/*<gmd:graphicOverview><gmd:MD_BrowseGraphic><gmd:fileName><gco:CharacterString>https://download.bgr.de/bgr/Geologie/IGK1500/Beispielbild/IGK1500.jpg</gco:CharacterString></gmd:fileName></gmd:MD_BrowseGraphic></gmd:graphicOverview>*/
	if (isset($mb_metadata['preview_image']) && $mb_metadata['preview_image'] !== "") {
		$graphicOverview = $iso19139->createElement("gmd:graphicOverview");
		$MD_BrowseGraphic = $iso19139->createElement("gmd:MD_BrowseGraphic");
		$fileName = $iso19139->createElement("gmd:fileName");
		$fileNameCs = $iso19139->createElement("gco:CharacterString");
		$previewPath = $admin->getMetadataPreviewUrl($mb_metadata['metadata_id']);
		$previewUrl = $iso19139->createTextNode($previewPath);
		$fileNameCs->appendChild($previewUrl);
		$fileName->appendChild($fileNameCs);
		$MD_BrowseGraphic->appendChild($fileName);
		$graphicOverview->appendChild($MD_BrowseGraphic);
		$MD_DataIdentification->appendChild($graphicOverview);
	}
	//generate keyword part - for services the inspire themes are not applicable!!!**********
	//read keywords for resource out of the database/not only layer keywords also featuretype keywords if given!
	$sql = "SELECT DISTINCT keyword.keyword FROM keyword, mb_metadata_keyword WHERE mb_metadata_keyword.fkey_metadata_id=$1 AND mb_metadata_keyword.fkey_keyword_id=keyword.keyword_id";
	$v = array((int)$mb_metadata['metadata_id']);
	$t = array('i');
	$res = db_prep_query($sql, $v, $t);
	$descriptiveKeywords = $iso19139->createElement("gmd:descriptiveKeywords");
	$MD_Keywords = $iso19139->createElement("gmd:MD_Keywords");
	//$countNormalKeywords = 0;
	$keywordExist = false;
	while ($row = db_fetch_array($res)) {
		if (isset($row['keyword']) && $row['keyword'] != "") {
			//$countNormalKeywords++;
			$keywordExist = true;
			$keyword = $iso19139->createElement("gmd:keyword");
			$keyword_cs = $iso19139->createElement("gco:CharacterString");
			$keywordText = $iso19139->createTextNode($row['keyword']);
			$keyword_cs->appendChild($keywordText);
			$keyword->appendChild($keyword_cs);
			$MD_Keywords->appendChild($keyword);
		}
	}
	//add dummy keyword, cause it is needed for validation!!!!
	if ($keywordExist == false) {
		$keyword = $iso19139->createElement("gmd:keyword");
		$keyword_cs = $iso19139->createElement("gco:CharacterString");
		$keywordText = $iso19139->createTextNode("DummyKeyword");
		$keyword_cs->appendChild($keywordText);
		$keyword->appendChild($keyword_cs);
		$MD_Keywords->appendChild($keyword);
	}
	//pull special keywords from custom categories:

	$sql = <<<SQL

SELECT custom_category.custom_category_key FROM custom_category WHERE custom_category_id IN (

SELECT DISTINCT fkey_custom_category_id FROM (

SELECT mb_metadata_custom_category.fkey_custom_category_id from mb_metadata_custom_category WHERE mb_metadata_custom_category.fkey_metadata_id = $1

UNION 

SELECT layer_custom_category.fkey_custom_category_id from layer_custom_category WHERE fkey_layer_id IN (SELECT fkey_layer_id FROM ows_relation_metadata WHERE fkey_metadata_id = $2)

UNION

SELECT wfs_featuretype_custom_category.fkey_custom_category_id from wfs_featuretype_custom_category WHERE fkey_featuretype_id IN (SELECT fkey_featuretype_id FROM ows_relation_metadata WHERE fkey_metadata_id = $3)) as custom_category

) AND custom_category_hidden = 0

SQL;
	$v = array((int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id']);
	$t = array('i', 'i', 'i');
	$res = db_prep_query($sql, $v, $t);
	$e = new mb_notice("look for custom categories: ");
	$countCustom = 0;
	while ($row = db_fetch_array($res)) {
		$keyword = $iso19139->createElement("gmd:keyword");
		$keyword_cs = $iso19139->createElement("gco:CharacterString");
		$keywordText = $iso19139->createTextNode($row['custom_category_key']);
		$keyword_cs->appendChild($keywordText);
		$keyword->appendChild($keyword_cs);
		$MD_Keywords->appendChild($keyword);
		$countCustom++;
	}
	$e = new mb_notice("count custom categories: " . $countCustom);
	//close decriptive keywords and generate a new entry for inspire themes:
	$descriptiveKeywords->appendChild($MD_Keywords);
	$MD_DataIdentification->appendChild($descriptiveKeywords);
	$descriptiveKeywords = $iso19139->createElement("gmd:descriptiveKeywords");
	//****************************************************************************************************************************************************************************************
	//keywords for INSPIRE themes:
	$inspireCategoryDefined = false;
	$MD_Keywords = $iso19139->createElement("gmd:MD_Keywords");
	//read out the inspire categories and push them in as controlled keywords
	$sql = <<<SQL

SELECT inspire_category.inspire_category_code_en FROM inspire_category WHERE inspire_category_id IN (

SELECT DISTINCT fkey_inspire_category_id FROM (

SELECT mb_metadata_inspire_category.fkey_inspire_category_id from mb_metadata_inspire_category WHERE mb_metadata_inspire_category.fkey_metadata_id = $1

UNION 

SELECT layer_inspire_category.fkey_inspire_category_id from layer_inspire_category WHERE  fkey_layer_id IN (SELECT fkey_layer_id FROM ows_relation_metadata WHERE fkey_metadata_id = $2)

UNION

SELECT wfs_featuretype_inspire_category.fkey_inspire_category_id from wfs_featuretype_inspire_category WHERE fkey_featuretype_id IN (SELECT fkey_featuretype_id FROM ows_relation_metadata WHERE fkey_metadata_id = $3)) as inspire_category

)

SQL;

	$v = array((int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id']);
	$t = array('i', 'i', 'i');
	$res = db_prep_query($sql, $v, $t);
	while ($row = db_fetch_array($res)) {
		//part for the name of the inspire category
		$keyword = $iso19139->createElement("gmd:keyword");
		$keyword_cs = $iso19139->createElement("gco:CharacterString");
		$keywordText = $iso19139->createTextNode($row['inspire_category_code_en']);
		$keyword_cs->appendChild($keywordText);
		$keyword->appendChild($keyword_cs);
		$MD_Keywords->appendChild($keyword);
		$inspireCategoryDefined = true;
	}
	//part for the vocabulary - is always the same for the inspire themes
	$thesaurusName = $iso19139->createElement("gmd:thesaurusName");
	$CI_Citation = $iso19139->createElement("gmd:CI_Citation");
	$title = $iso19139->createElement("gmd:title");
	$title_cs = $iso19139->createElement("gco:CharacterString");
	$titleText = $iso19139->createTextNode("GEMET - INSPIRE themes, version 1.0");

	$title_cs->appendChild($titleText);
	$title->appendChild($title_cs);
	$CI_Citation->appendChild($title);

	$date1 = $iso19139->createElement("gmd:date");
	$CI_Date = $iso19139->createElement("gmd:CI_Date");
	$date2 = $iso19139->createElement("gmd:date");
	$gcoDate = $iso19139->createElement("gco:Date");
	$dateType = $iso19139->createElement("gmd:dateType");
	$dateTypeCode = $iso19139->createElement("gmd:CI_DateTypeCode");
	$dateTypeCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode");
	$dateTypeCode->setAttribute("codeListValue", "publication");
	$dateTypeCodeText = $iso19139->createTextNode('publication');
	$dateText = $iso19139->createTextNode('2008-06-01');
	$dateTypeCode->appendChild($dateTypeCodeText);
	$dateType->appendChild($dateTypeCode);
	$gcoDate->appendChild($dateText);
	$date2->appendChild($gcoDate);
	$CI_Date->appendChild($date2);
	$CI_Date->appendChild($dateType);
	$date1->appendChild($CI_Date);

	$CI_Citation->appendChild($date1);
	$thesaurusName->appendChild($CI_Citation);

	#$MD_Keywords->appendChild($keyword);
	$MD_Keywords->appendChild($thesaurusName);
	$descriptiveKeywords->appendChild($MD_Keywords);
	//only append child descriptiveKeywords if an INSPIRE category was found!
	if ($inspireCategoryDefined) {
		$MD_DataIdentification->appendChild($descriptiveKeywords);
	}
	//New 2020 - use class to get license information
	//Resource Constraints B 8 - to be handled with xml snippets from constraints class
	//pull licence information
	$constraints = new OwsConstraints();
	$constraints->languageCode = "de";
	$constraints->asTable = false;
	$constraints->id = (int)$mb_metadata['metadata_id'];
	$constraints->type = "metadata";
	$constraints->returnDirect = false;
	$constraints->outputFormat = 'iso19139';
	$tou = $constraints->getDisclaimer();
	//constraints - after descriptive keywords
	if (isset($tou) && $tou !== '' && $tou !== false) {
		//load xml from constraint generator
		$licenseDomObject = new DOMDocument();
		$licenseDomObject->loadXML($tou);
		$xpathLicense = new DOMXpath($licenseDomObject);
		$licenseNodeList = $xpathLicense->query('/mb:constraints/gmd:resourceConstraints');
		for ($i = ($licenseNodeList->length) - 1; $i >= 0; $i--) {
			$MD_DataIdentification->appendChild($iso19139->importNode($licenseNodeList->item($i), true));
		}
	}

	$MD_DataIdentification->appendChild($spatialRepresentationType);
	//Spatial Resolution
	/* Example
<gmd:spatialResolution><gmd:MD_Resolution><gmd:distance><gco:Distance uom="m">3.0</gco:Distance></gmd:distance></gmd:MD_Resolution></gmd:spatialResolution>
*/
	$spatialResolution = $iso19139->createElement("gmd:spatialResolution");
	$MD_Resolution = $iso19139->createElement("gmd:MD_Resolution");

	//Problem if scale is not set properly
	if ($mb_metadata['spatial_res_value'] == '') {
		$mb_metadata['spatial_res_value'] = 0;
	}
	if ($mb_metadata['spatial_res_type'] == 'groundDistance') {
		$distance = $iso19139->createElement("gmd:distance");
		$Distance = $iso19139->createElement("gco:Distance");
		$Distance->setAttribute("uom", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/uom/ML_gmxUom.xml#m");
		$DistanceText = $iso19139->createTextNode($mb_metadata['spatial_res_value']);
		$Distance->appendChild($DistanceText);
		$distance->appendChild($Distance);
		$MD_Resolution->appendChild($distance);
	} else { //will be scaleDenominator
		$equivalentScale = $iso19139->createElement("gmd:equivalentScale");
		$MD_RepresentativeFraction = $iso19139->createElement("gmd:MD_RepresentativeFraction");
		$denominator = $iso19139->createElement("gmd:denominator");
		$Integer = $iso19139->createElement("gco:Integer");
		$IntegerText = $iso19139->createTextNode($mb_metadata['spatial_res_value']);
		$Integer->appendChild($IntegerText);
		$denominator->appendChild($Integer);
		$MD_RepresentativeFraction->appendChild($denominator);
		$equivalentScale->appendChild($MD_RepresentativeFraction);
		$MD_Resolution->appendChild($equivalentScale);
	}
	$spatialResolution->appendChild($MD_Resolution);
	$MD_DataIdentification->appendChild($spatialResolution);

	#Part B 1.7 Dataset Language
	$language = $iso19139->createElement("gmd:language");
	$LanguageCode = $iso19139->createElement("gmd:LanguageCode");
	$LanguageCodeText = $iso19139->createTextNode('ger');
	$LanguageCode->setAttribute("codeListValue", "ger");
	if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
		switch (INSPIRE_METADATA_SPEC) {
			case "2.0.1":
				$LanguageCode->setAttribute("codeList", "http://www.loc.gov/standards/iso639-2/");
				break;
			case "1.3":
				$LanguageCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode");
				break;
		}
	} else {
		$LanguageCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode");
	}
	$LanguageCode->appendChild($LanguageCodeText);
	$language->appendChild($LanguageCode);
	$MD_DataIdentification->appendChild($language);

	#Part B 1.7 Dataset character encoding
	$characterSet = $iso19139->createElement("gmd:characterSet");
	$MD_CharacterSetCode = $iso19139->createElement("gmd:MD_CharacterSetCode");
	$MD_CharacterSetCode->setAttribute("codeListValue", $mb_metadata['inspire_charset']);
	$MD_CharacterSetCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_CharacterSetCode");
	$characterSet->appendChild($MD_CharacterSetCode);
	$MD_DataIdentification->appendChild($characterSet);

	$sql = <<<SQL

SELECT md_topic_category.md_topic_category_code_en FROM md_topic_category WHERE md_topic_category_id IN (

SELECT DISTINCT fkey_md_topic_category_id FROM (

SELECT mb_metadata_md_topic_category.fkey_md_topic_category_id from mb_metadata_md_topic_category WHERE mb_metadata_md_topic_category.fkey_metadata_id = $1

UNION 

SELECT layer_md_topic_category.fkey_md_topic_category_id from layer_md_topic_category WHERE fkey_layer_id IN (SELECT fkey_layer_id FROM ows_relation_metadata WHERE fkey_metadata_id = $2)

UNION

SELECT wfs_featuretype_md_topic_category.fkey_md_topic_category_id from wfs_featuretype_md_topic_category WHERE fkey_featuretype_id IN (SELECT fkey_featuretype_id FROM ows_relation_metadata WHERE fkey_metadata_id = $3)) as md_topic_category
)

SQL;

	$v = array((int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id'], (int)$mb_metadata['metadata_id']);
	$t = array('i', 'i', 'i');
	$res = db_prep_query($sql, $v, $t);
	$e = new mb_notice("look for topic: ");
	$countTopic = 0;
	while ($row = db_fetch_array($res)) {
		$e = new mb_notice("topic cat found: " . $row['md_topic_category_code_en']);
		$topicCategory = $iso19139->createElement("gmd:topicCategory");
		$MD_TopicCategoryCode = $iso19139->createElement("gmd:MD_TopicCategoryCode");
		$MD_TopicCategoryText = $iso19139->createTextNode($row['md_topic_category_code_en']);
		$MD_TopicCategoryCode->appendChild($MD_TopicCategoryText);
		$topicCategory->appendChild($MD_TopicCategoryCode);
		$MD_DataIdentification->appendChild($topicCategory);
		$countTopic++;
	}
	$e = new mb_notice("count topic: " . $countTopic);
	if ($countTopic == 0) {
		$e = new mb_notice("no topic cat found!");
		$topicCategory = $iso19139->createElement("gmd:topicCategory");
		$MD_TopicCategoryCode = $iso19139->createElement("gmd:MD_TopicCategoryCode");
		$MD_TopicCategoryText = $iso19139->createTextNode("no category defined till now!");
		$MD_TopicCategoryCode->appendChild($MD_TopicCategoryText);
		$topicCategory->appendChild($MD_TopicCategoryCode);
		$MD_DataIdentification->appendChild($topicCategory);
	}

	//generate polygonal extent if given:
	if ($mb_metadata['boundingPolygonGml'] !== false) {
		$extent = $iso19139->createElement("gmd:extent");
		$EX_Extent = $iso19139->createElement("gmd:EX_Extent");
		$geographicElement = $iso19139->createElement("gmd:geographicElement");
		$EX_BoundingPolygon = $iso19139->createElement("gmd:EX_BoundingPolygon");
		$polygon = $iso19139->createElement("gmd:polygon");
		//inject polygons
		$gmlDoc = new DOMDocument();
		$gmlDoc->loadXML($mb_metadata['boundingPolygonGml']);
		$xpathGml = new DOMXpath($gmlDoc);
		if ($mb_metadata['boundingGmlMultiPolygon'] == true) {
			$gmlNodeList = $xpathGml->query('/gml:MultiSurface');
		} else {
			$gmlNodeList = $xpathGml->query('/gml:Polygon');
		}
		for ($i = ($gmlNodeList->length) - 1; $i >= 0; $i--) {
			$polygon->appendChild($iso19139->importNode($gmlNodeList->item($i), true));
		}
		$EX_BoundingPolygon->appendChild($polygon);
		$geographicElement->appendChild($EX_BoundingPolygon);
		$EX_Extent->appendChild($geographicElement);
		$extent->appendChild($EX_Extent);
		$MD_DataIdentification->appendChild($extent);
	}
	#Geographical Extent
	$bbox = array();
	//initialize if no extent is defined in the database
	$bbox[0] = -180.00;
	$bbox[1] = -90.00;
	$bbox[2] = 180.00;
	$bbox[3] = 90.00;
	if (isset($mb_metadata['bbox2d']) & ($mb_metadata['bbox2d'] != '')) {
		$bbox = explode(',', $mb_metadata['bbox2d']);
	}
	//simple function to add two digits to value if there is no point in string
	if (strpos($bbox[0], '.') === false) {
		$bbox[0] = $bbox[0] . '.00';
	}
	if (strpos($bbox[1], '.') === false) {
		$bbox[1] = $bbox[1] . '.00';
	}
	if (strpos($bbox[2], '.') === false) {
		$bbox[2] = $bbox[2] . '.00';
	}
	if (strpos($bbox[3], '.') === false) {
		$bbox[3] = $bbox[3] . '.00';
	}
	$extent = $iso19139->createElement("gmd:extent");
	$EX_Extent = $iso19139->createElement("gmd:EX_Extent");
	$geographicElement = $iso19139->createElement("gmd:geographicElement");
	$EX_GeographicBoundingBox = $iso19139->createElement("gmd:EX_GeographicBoundingBox");

	$westBoundLongitude = $iso19139->createElement("gmd:westBoundLongitude");
	$wb_dec = $iso19139->createElement("gco:Decimal");
	$wb_text = $iso19139->createTextNode($bbox[0]);

	$eastBoundLongitude = $iso19139->createElement("gmd:eastBoundLongitude");
	$eb_dec = $iso19139->createElement("gco:Decimal");
	$eb_text = $iso19139->createTextNode($bbox[2]);

	$southBoundLatitude = $iso19139->createElement("gmd:southBoundLatitude");
	$sb_dec = $iso19139->createElement("gco:Decimal");
	$sb_text = $iso19139->createTextNode($bbox[1]);

	$northBoundLatitude = $iso19139->createElement("gmd:northBoundLatitude");
	$nb_dec = $iso19139->createElement("gco:Decimal");
	$nb_text = $iso19139->createTextNode($bbox[3]);

	$wb_dec->appendChild($wb_text);
	$westBoundLongitude->appendChild($wb_dec);
	$EX_GeographicBoundingBox->appendChild($westBoundLongitude);

	$eb_dec->appendChild($eb_text);
	$eastBoundLongitude->appendChild($eb_dec);
	$EX_GeographicBoundingBox->appendChild($eastBoundLongitude);

	$sb_dec->appendChild($sb_text);
	$southBoundLatitude->appendChild($sb_dec);
	$EX_GeographicBoundingBox->appendChild($southBoundLatitude);

	$nb_dec->appendChild($nb_text);
	$northBoundLatitude->appendChild($nb_dec);
	$EX_GeographicBoundingBox->appendChild($northBoundLatitude);

	$geographicElement->appendChild($EX_GeographicBoundingBox);
	$EX_Extent->appendChild($geographicElement);
	$extent->appendChild($EX_Extent);

	$MD_DataIdentification->appendChild($extent);

	//check if maintenance is set and adopt the last time - both times are always set, cause the editor demands this!!!!

	$extent = $iso19139->createElement("gmd:extent");
	$EX_Extent = $iso19139->createElement("gmd:EX_Extent");
	$temporalElement = $iso19139->createElement("gmd:temporalElement");
	$EX_TemporalExtent = $iso19139->createElement("gmd:EX_TemporalExtent");
	$extent2 = $iso19139->createElement("gmd:extent");
	$TimePeriod = $iso19139->createElement("gml:TimePeriod");
	$TimePeriod->setAttribute("gml:id", "temporalextent"); //maybe exchange thru uuid?
	$beginPosition = $iso19139->createElement("gml:beginPosition");
	$beginPositionText = $iso19139->createTextNode($beginPositionValue);
	$endPosition = $iso19139->createElement("gml:endPosition");
	$endPositionText = $iso19139->createTextNode($endPositionValue);
	//generate xml

	$endPosition->appendChild($endPositionText);
	$beginPosition->appendChild($beginPositionText);
	$TimePeriod->appendChild($beginPosition);
	$TimePeriod->appendChild($endPosition);
	$extent2->appendChild($TimePeriod);
	$EX_TemporalExtent->appendChild($extent2);
	$temporalElement->appendChild($EX_TemporalExtent);
	$EX_Extent->appendChild($temporalElement);
	$extent->appendChild($EX_Extent);
	$MD_DataIdentification->appendChild($extent);

	$identificationInfo->appendChild($MD_DataIdentification);

	//distributionInfo
	$gmd_distributionInfo = $iso19139->createElement("gmd:distributionInfo");
	$MD_Distribution = $iso19139->createElement("gmd:MD_Distribution");
	$gmd_distributionFormat = $iso19139->createElement("gmd:distributionFormat");
	$MD_Format = $iso19139->createElement("gmd:MD_Format");
	$gmd_name = $iso19139->createElement("gmd:name");
	$MD_FormatName_cs = $iso19139->createElement("gco:CharacterString");

	//TODO - set format to some other for application 2019-10-17 !!!!

	$MD_FormatNameText = $iso19139->createTextNode($mb_metadata['format']);

	$gmd_version = $iso19139->createElement("gmd:version");
	//add attribute 
	$gmd_version->setAttribute("gco:nilReason", "unknown");

	$gmd_specification = $iso19139->createElement("gmd:specification");
	$MD_FormatSpecification_cs = $iso19139->createElement("gco:CharacterString");
	/*TODO: the following entry should handle the TG data spec (D2.8.I.5 Data Specification on Addresses – Technical
Guidelines) if conformant datasets are published TBD*/
	$MD_FormatSpecificationText = $iso19139->createTextNode("Specification unkown");

	$MD_FormatName_cs->appendChild($MD_FormatNameText);
	$MD_FormatSpecification_cs->appendChild($MD_FormatSpecificationText);

	$gmd_name->appendChild($MD_FormatName_cs);
	$gmd_specification->appendChild($MD_FormatSpecification_cs);
	$gmd_transferOptions = $iso19139->createElement("gmd:transferOptions");
	$MD_DigitalTransferOptions = $iso19139->createElement("gmd:MD_DigitalTransferOptions");
	$gmd_onLine = $iso19139->createElement("gmd:onLine");

	$CI_OnlineResource = $iso19139->createElement("gmd:CI_OnlineResource");

	$gmd_linkage = $iso19139->createElement("gmd:linkage");
	$gmd_URL = $iso19139->createElement("gmd:URL");
	//use downloadurl if given
	$downloadUrls = json_decode($mb_metadata['datalinks']);
	$downloadUrl = $downloadUrls->downloadLinks[0]->{0};
	if ($mb_metadata['type'] == 'application') {
		if (((isset($mb_metadata['fkey_gui_id']) && $mb_metadata['fkey_gui_id'] != '') && isset($mb_metadata['fkey_mapviewer_id'])) || ((isset($mb_metadata['fkey_wmc_serial_id']) && $mb_metadata['fkey_wmc_serial_id'] != '') && isset($mb_metadata['fkey_mapviewer_id']))) {
			$applicationUrl = $admin->getMapviewerInvokeUrl($mb_metadata['fkey_mapviewer_id'], $mb_metadata['fkey_gui_id'], $mb_metadata['fkey_wmc_serial_id']);
		} else {
			$applicationUrl = $mb_metadata['link'];
		}
		$gmd_URLText = $iso19139->createTextNode($applicationUrl);
	} else {
		if ($downloadUrl == "" || !isset($downloadUrl)) {
			$gmd_URLText = $iso19139->createTextNode("https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol");
		} else {
			$gmd_URLText = $iso19139->createTextNode($downloadUrl);
		}
	}
	$gmd_URL->appendChild($gmd_URLText);
	$gmd_linkage->appendChild($gmd_URL);
	$CI_OnlineResource->appendChild($gmd_linkage);
	//***********************************************************************************
	$gmd_onLine->appendChild($CI_OnlineResource);
	$MD_DigitalTransferOptions->appendChild($gmd_onLine);
	//only append transfer option, if $downloadUrl or $applicationUrl was given! This has to be done, because the inspire validator tries to access the referenced resource and throws am error if it is not available! See http://inspire.ec.europa.eu/validator/
	if (($mb_metadata['type'] == 'application' && $applicationUrl != "") || $downloadUrl != "") {
		$gmd_transferOptions->appendChild($MD_DigitalTransferOptions);
	}
	$MD_Format->appendChild($gmd_name);
	$MD_Format->appendChild($gmd_version);
	$MD_Format->appendChild($gmd_specification);

	$gmd_distributionFormat->appendChild($MD_Format);

	$MD_Distribution->appendChild($gmd_distributionFormat);

	$MD_Distribution->appendChild($gmd_transferOptions);
	$gmd_distributionInfo->appendChild($MD_Distribution);
	//dataQualityInfo
	$gmd_dataQualityInfo = $iso19139->createElement("gmd:dataQualityInfo");
	$DQ_DataQuality = $iso19139->createElement("gmd:DQ_DataQuality");

	$gmd_scope = $iso19139->createElement("gmd:scope");
	$DQ_Scope = $iso19139->createElement("gmd:DQ_Scope");
	$gmd_level = $iso19139->createElement("gmd:level");
	$MD_ScopeCode = $iso19139->createElement("gmd:MD_ScopeCode");
	$MD_ScopeCodeText = $iso19139->createTextNode("dataset");
	$MD_ScopeCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode");
	$MD_ScopeCode->setAttribute("codeListValue", "dataset");
	/*
	 * https://github.com/inspire-eu-validation/community/issues/189
	 * gmd:levelDescription/gmd:MD_ScopeDescription/gmd:other/gco:CharacterString>Dienst...
	 */
	if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
		switch (INSPIRE_METADATA_SPEC) {
			case "2.0.1":
				$gmd_levelDescription = $iso19139->createElement("gmd:levelDescription");
				$gmd_MD_ScopeDescription = $iso19139->createElement("gmd:MD_ScopeDescription");
				$gmd_other = $iso19139->createElement("gmd:other");
				$gmd_other_cs = $iso19139->createElement("gco:CharacterString");
				$gmd_otherText = $iso19139->createTextNode("Datensatz");

				$gmd_other_cs->appendChild($gmd_otherText);
				$gmd_other->appendChild($gmd_other_cs);
				$gmd_MD_ScopeDescription->appendChild($gmd_other);
				$gmd_levelDescription->appendChild($gmd_MD_ScopeDescription);
				break;
		}
	}
	$MD_ScopeCode->appendChild($MD_ScopeCodeText);
	$gmd_level->appendChild($MD_ScopeCode);
	$DQ_Scope->appendChild($gmd_level);
	if (isset($gmd_levelDescription)) {
		$DQ_Scope->appendChild($gmd_levelDescription);
	}
	$gmd_scope->appendChild($DQ_Scope);
	$DQ_DataQuality->appendChild($gmd_scope);
	$lineage = $iso19139->createElement("gmd:lineage");
	$LI_Lineage = $iso19139->createElement("gmd:LI_Lineage");
	$statement = $iso19139->createElement("gmd:statement");
	$statement_cs = $iso19139->createElement("gco:CharacterString");
	$statementText = $iso19139->createTextNode($mb_metadata['lineage']);
	$lineage->appendChild($LI_Lineage);
	$LI_Lineage->appendChild($statement);
	$statement->appendChild($statement_cs);
	$statement_cs->appendChild($statementText);

	//new from january 2017 - create conformance table from inspire_legislation config file - for interoperable datasets set all conformancy declarations to true for non interoperable set only the metadata conformance to true
	//get conformancy declarations from class
	$qualityReport = new QualityReport();
	$inputXml = $qualityReport->getIso19139Representation("dataset", $mb_metadata['inspire_interoperability']);
	$reportDomObject = new DOMDocument();
	$reportDomObject->loadXML($inputXml);
	$xpathInput = new DOMXpath($reportDomObject);
	$inputNodeList = $xpathInput->query('/mb:dataqualityreport/gmd:report');
	for ($i = ($inputNodeList->length) - 1; $i >= 0; $i--) {
		$DQ_DataQuality->appendChild($iso19139->importNode($inputNodeList->item($i), true));
	}

	$DQ_DataQuality->appendChild($lineage);
	$gmd_dataQualityInfo->appendChild($DQ_DataQuality);
	$MD_Metadata->appendChild($identificationInfo);
	$MD_Metadata->appendChild($gmd_distributionInfo);
	$MD_Metadata->appendChild($gmd_dataQualityInfo);
	return $iso19139->saveXML();
}
