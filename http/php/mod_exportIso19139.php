<?php
//http://localhost/mb_trunk/php/mod_exportIso19139ToHtml.php?url=http%3A%2F%2Fwww.geoportal.rlp.de%2Fmetadata%2Fdtk5.xml
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_iso19139.php";
require_once(dirname(__FILE__) . "/../classes/class_cswClient.php");
require_once(dirname(__FILE__) . "/../classes/class_csw.php");
//show html from a given url
//default languageCode to de
$languageCode = "de";
$url = urldecode($_REQUEST['url']);
$outputFormat = 'html';
$resolveCoupledResources = false;
//get language parameter out of mapbender session if it is set else set default language to de_DE
$sessionLang = Mapbender::session()->get("mb_lang");
if (isset($sessionLang) && ($sessionLang!='')) {
	$e = new mb_notice("mod_showMetadata.php: language found in session: ".$sessionLang);
	$language = $sessionLang;
	$langCode = explode("_", $language);
	$langCode = $langCode[0]; # Hopefully de or s.th. else
	$languageCode = $langCode; #overwrite the GET Parameter with the SESSION information
}
if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["languageCode"];
	if (!($testMatch == 'de' or $testMatch == 'fr' or $testMatch == 'en')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>languageCode</b> is not valid (de,fr,en).<br/>'; 
		die(); 		
 	}
	$languageCode = $testMatch;
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
			$resolveCoupledResources = true;
		break;
		case "false":
			$resolveCoupledResources = false;
		break;	
	}
	$testMatch = NULL;
}
//write languageCode into session!
$localeObj->setCurrentLocale($languageCode);
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'html' or $testMatch == 'rdf' or $testMatch == 'iso19139'  or $testMatch == 'html2'  or $testMatch == 'html3')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>outputFormat</b> is not valid (html,rdf,iso19139).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}
//initialize if resource is based on a request to csw interface
$cswBasedResource = false;
//instantiate
$mbMetadata = new Iso19139();
//test if getrecordbyid request was used - then the service data may also be in the same catalogue
if (strpos(strtoupper($url), "GETRECORDBYID") !== false && strpos(strtoupper($url), "SERVICE=CSW") !== false && strpos(strtoupper($url), "VERSION=2.0.2") !== false) {
	$cswBasedResource = true;
}


//build search request for services
//TODO: test if this ok - maybe not everything is parsed by class? instead we could use $mbMetadata->readFromUrl($url);
$mbMetadata->createFromUrl($url);

//$e = new mb_exception("php/mod_exportIso19139.php: url for getrecordbyid: ".$url);
//$e = new mb_exception("php/mod_exportIso19139.php: datasetid from getrecordbyid: ".$mbMetadata->datasetIdCodeSpace.$mbMetadata->datasetId);

//$e = new mb_exception("datasetId: ".$mbMetadata->datasetId);
//$e = new mb_exception("datasetIdCodeSpace: ".$mbMetadata->datasetIdCodeSpace);
//$e = new mb_exception("hierarchyLevel: ".$mbMetadata->hierarchyLevel);
$serviceMetadata = new stdClass();
$serviceMetadataIndex = 0;
if ($mbMetadata->hierarchyLevel == 'dataset' || $mbMetadata->hierarchyLevel == 'series') {
	//get datasetidentifier to resolve coupled resources
	//$e = new mb_exception("datasetId: ".$mbMetadata->datasetId);
	//$e = new mb_exception("datasetIdCodeSpace: ".$mbMetadata->datasetIdCodeSpace);
	if ($resolveCoupledResources == true && $cswBasedResource == true) {
		//$e = new mb_exception("try to resolve coupled resources");
		//createCatObjFromXML($url);
		$csw = new csw();
		//$e = new mb_exception("parse csw capabilities!");
		//parse url
		$urlArray = parse_url($url);
//$e = new mb_exception(json_encode($urlArray));
		//$urlWithoutRequest = $urlArray['scheme']."://".$urlArray['host'].":".$urlArray['port']."/".$urlArray['path'];
		$urlWithoutRequest = $urlArray['scheme']."://".$urlArray['host'].$urlArray['path'];
		$csw->createCatObjFromXML($urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetCapabilities");
		//$e = new mb_exception($urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetCapabilities");
		$cswClient = new cswClient();
		$operation = "getrecordsresolvecoupling";
		$getrecordId = $mbMetadata->fileIdentifier;
		$datasetId = str_replace('&','&amp;',$mbMetadata->datasetIdCodeSpace.$mbMetadata->datasetId);
		$recordType = 'service';
		$cswResponseObject = $cswClient->doRequest(false, $operation, $getrecordId, false, $recordType, false, false, false, $datasetId, $csw);
		//$e = new mb_exception("test1");	
		//$e = new mb_exception($cswClient->operationSuccessful);
		//$e = new mb_exception("test2");	
		$serviceMetadataUrls = array();
		if ($cswClient->operationSuccessful == true) {
			//$e = new mb_exception("operation successfull");	
			//$e = new mb_exception(gettype($cswClient->operationResult));
			$metadataRecord = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
			//$e = new mb_exception("number of records: ".count($metadataRecord));
			//what is possible: keywords, categories?, spatial, ...
			for ($k = 1; $k <= count($metadataRecord) ; $k++) {
				$fileIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:fileIdentifier/gco:CharacterString');
				$fileIdentifier = (string)$fileIdentifier[0];
				//service date
				$mdDateStamp = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:date/gmd:CI_Date/gmd:date/gco:Date');
				$mdDateStamp = (string)$mdDateStamp[0];
				//service title
				$mdTitle = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString');
				$mdTitle = (string)$mdTitle[0];
				//service type
				$mdServiceType = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceType/gco:LocalName');
				$mdServiceType = (string)$mdServiceType[0];
				//accessUrl
				$mdAccessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				$mdAccessUrl = (string)$mdAccessUrl[0];

				//get service type - view / download

				//get service title

				/*$datasetIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/gmd:MD_DataIdentification/@uuid');
				$datasetidentifier = (string)$datasetidentifier[0];
				$url =  $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				$url = (string)$url[0];
				if (isset($url) && $url !=="") {
					//$metadataArray[$numberOfMetadataRecords]['uuid'] = $datasetIdentifier;
					$metadataArray[$numberOfMetadataRecords]['uuid'] = $fileIdentifier;
					$metadataArray[$numberOfMetadataRecords]['changedate'] = $mdDateStamp;
					$numberOfMetadataRecords++;
				}*/
				//$e = new mb_exception("found service with fileIdentifier: ".$fileIdentifier." - date - ".$mdDateStamp);
				$serviceMetadata->service[$k]->serviceType = $mdServiceType;
				$serviceMetadata->service[$k]->serviceTitle = $mdTitle;
				$serviceMetadata->service[$k]->serviceDate = $mdDateStamp;
				$serviceMetadata->service[$k]->metadataUrl = $urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetRecordById&ElementSetName=full&&outputSchema=".urlencode('http://www.isotc211.org/2005/gmd')."&id=".$fileIdentifier;
				$serviceMetadata->service[$k]->accessUrl = $mdAccessUrl;

				
			}
		}
		/*if ($cswClient->operationSuccessful == true) {
			if ($cswResponseObject !== false) {
				$e = new mb_exception("php/mod_exportIso19139.php: returned service records: ".$cswClient->operationResult->asXML());
				
			}
		}*/
	}
}
//resolve operates on
//new - test createFromUrl instead and build the presentations from object! There for a new json configuration is needed!
//html2 / html3 - conf from json!
switch ($outputFormat) {
	case "html3":
		$html = $mbMetadata->transformToHtml3('tabs',$languageCode);
		header("Content-type: text/html; charset=UTF-8");
		echo $html;
	break;
	case "html2":
		$html = $mbMetadata->transformToHtml2();
		header("Content-type: text/html; charset=UTF-8");
		echo $html;
	break;
	case "html":
		$html = $mbMetadata->transformToHtml('tabs', $languageCode, $serviceMetadata);
		header("Content-type: text/html; charset=UTF-8");
		echo $html;
		/*foreach ($serviceMetadata as $serviceMetadatas) {
			echo "<br>".json_encode($serviceMetadata)."<br>";
			
		}*/
	break;
	case "rdf":
		$rdf =  $mbMetadata->transformToRdf();
		header("Content-type: text/xml; charset=UTF-8");
		echo $rdf;
	break;
	case "iso19139":
		$xml =  $mbMetadata->metadata;
		header("Content-type: text/xml; charset=UTF-8");
		echo $xml;
	break;
}
?>
