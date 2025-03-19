<?php
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
//example:
//https://gdk.gdi-de.org/gdi-de/srv/eng/csw?request=GetRecordById&service=CSW&version=2.0.2&Id=71b31668-746b-40a2-975a-172a87318e45&ElementSetName=full&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd

//localhost/mapbender/php/mod_getCoupledResourcesForDataset.php?getRecordByIdUrl=https%3A%2F%2Fgdk.gdi-de.org%2Fgdi-de%2Fsrv%2Feng%2Fcsw%3Frequest%3DGetRecordById%26service%3DCSW%26version%3D2.0.2%26Id%3D71b31668-746b-40a2-975a-172a87318e45%26ElementSetName%3Dfull%26OUTPUTSCHEMA%3Dhttp%3A%2F%2Fwww.isotc211.org%2F2005%2Fgmd

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
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

$languageCode = "de";
$url = urldecode($_REQUEST['getRecordByIdUrl']);
$outputFormat = 'json';
$catalogueId = 1;
$resultObj['result'] = '';
$resultObj['success'] = false;
$resultObj['message'] = 'no message';
$starttime = microtime_float();

$hostName = $_SERVER['HTTP_HOST'];
$headers = apache_request_headers();
$originFromHeader = false;
foreach ($headers as $header => $value) {
    	if ($header === "Origin") {
		//$e = new mb_exception("Origin: ".$value);
		$originFromHeader = $value;
    	}
}
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
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
	$scheme = "https";
} else {
	$scheme = "http";
}
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
/*if (isset($_REQUEST["catalogueId"]) & $_REQUEST["catalogueId"] != "") {
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
}*/
/*if (isset($_REQUEST["datasetId"]) & $_REQUEST["datasetId"] != "") {
	//validate integer to 100 - not more
	$testMatch = $_REQUEST["datasetId"];
	//
	$pattern = '/^([0-9]{0,1})([0-9]{1})$/';
 	if (!preg_match($pattern,$testMatch)){
		//echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>datasetId</b> is not valid (integer < 99).<br/>';
		die();
 	}
	$datasetId = $testMatch;
	$testMatch = NULL;
} else {
	echo 'Mandatory parameter <b>datasetId</b> not set!<br/>';
	die();
}*/
//write languageCode into session!
$localeObj->setCurrentLocale($languageCode);
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'html' or $testMatch == 'json')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>outputFormat</b> is not valid (json,html).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}

//instantiate
$mbMetadata = new Iso19139();
//initialize if resource is based on a request to csw interface
$cswBasedResource = false;
//test if getrecordbyid request was used - then the service data may also be in the same catalogue
if (strpos(strtoupper($url), "GETRECORDBYID") !== false && strpos(strtoupper($url), "SERVICE=CSW") !== false && strpos(strtoupper($url), "VERSION=2.0.2") !== false) {
	$cswBasedResource = true;
} else {
	$resultObj['message'] ='Url dont validate against a getrecordbyid url!'; 
	$resultObj['result'] = null;
	echo json_encode($resultObj);
	die(); 	
}
//build search request for services
//TODO: test if this ok - maybe not everything is parsed by class? instead we could use $mbMetadata->readFromUrl($url);
//$e = new mb_exception("php/mod_getCoupledResourcesForDataset.php: url for getrecordbyid: ".$url);
//$mbMetadata->createFromUrl($url);

if ($mbMetadata->createFromUrl($url) == false) {
	$resultObj['message'] ='Could not get metadata by getrecordbyid request!'; 
	$resultObj['result'] = null;
	echo json_encode($resultObj);
	die(); 
}

//$e = new mb_exception("php/mod_getCoupledResourcesForDataset.php: datasetid from getrecordbyid: ".$mbMetadata->datasetIdCodeSpace.$mbMetadata->datasetId);
$serviceMetadata = new stdClass();
$serviceMetadataIndex = 0;
if ($mbMetadata->hierarchyLevel == 'dataset' || $mbMetadata->hierarchyLevel == 'series') {
	//get datasetidentifier to resolve coupled resources
	//$e = new mb_exception("datasetId: ".$mbMetadata->datasetId);
	//$e = new mb_exception("datasetIdCodeSpace: ".$mbMetadata->datasetIdCodeSpace);
	if ($cswBasedResource == true) {
		//$e = new mb_exception("try to resolve coupled resources");
		//createCatObjFromXML($url);
		$csw = new csw();
		//$e = new mb_exception("parse csw capabilities!");
		//parse url
		$urlArray = parse_url($url);
		$urlWithoutRequest = $urlArray['scheme']."://".$urlArray['host'].$urlArray['path'];
		$cswCapUrl = $urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetCapabilities";

		$csw->createCatObjFromXML($cswCapUrl);

		$cswClient = new cswClient();
		//$e = new mb_exception("php/mod_getCoupledResourcesForDataset.php: datasetid from getrecordbyid: ".$mbMetadata->datasetId);
		$operation = "getrecordsresolvecoupling";
		$getrecordId = $mbMetadata->fileIdentifier;
		if ($mbMetadata->datasetIdCodeSpace != '') {
		    $datasetId = str_replace('&','&amp;',rtrim($mbMetadata->datasetIdCodeSpace, '/').'/'.$mbMetadata->datasetId);
		} else {
		    $datasetId = (string)$mbMetadata->datasetId;
		}
		$recordType = 'service';
		$cswResponseObject = $cswClient->doRequest(false, $operation, $getrecordId, false, $recordType, false, false, false, $datasetId, $csw);	
		$serviceMetadataUrls = array();
		if ($cswClient->operationSuccessful == true) {
			//$e = new mb_exception("operation successfull");	
			//$e = new mb_exception(gettype($cswClient->operationResult));
			$metadataRecord = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
			//$e = new mb_exception("number of records: ".count($metadataRecord));
			//what is possible: keywords, categories?, spatial, ...
			if (count($metadataRecord) < 1) {
				$resultObj['message'] ='No coupled services found in csw catalogue!'; 
				$resultObj['result'] = null;
				echo json_encode($resultObj);
				die(); 
			}
			for ($k = 1; $k <= count($metadataRecord) ; $k++) {
				//TODO: check if class metadata can be used instead - read xml write xml - parse metadata from xml? Look for namespaces!
				
				$fileIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:fileIdentifier/gco:CharacterString');
				$fileIdentifier = (string)$fileIdentifier[0];
//$e = new mb_exception("service fileidentifier: ".$fileIdentifier);
				//service date
				$mdDateStamp = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:date/gmd:CI_Date/gmd:date/gco:Date');
				$mdDateStamp = (string)$mdDateStamp[0];
				//service title
				$mdTitle = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString');
				$mdTitle = (string)$mdTitle[0];
				//service type
				$mdServiceType = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceType/gco:LocalName');
				$mdServiceType = (string)$mdServiceType[0];
				//service type version
				$mdServiceTypeVersion = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:serviceTypeVersion/gco:CharacterString');
				$mdServiceTypeVersion = (string)$mdServiceTypeVersion[0];

				//accessUrl
				/*$mdAccessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				$mdAccessUrl = (string)$mdAccessUrl[0];*/
				//first read the inspire kind of implementation of the access to capabilities documents
				$accessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				//try to match best
				$mdAccessUrl = getServiceUrl($mdServiceType, $mdServiceTypeVersion, $accessUrl);
				
				if ($mdAccessUrl == '' || count($mdAccessUrl) == 0 || $mdAccessUrl == null) {
					//search for another accessUrl - as defined in csw ap iso
					$accessUrl = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/srv:SV_ServiceIdentification/srv:containsOperations/srv:SV_OperationMetadata/srv:connectPoint/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				}
				$mdAccessUrl = getServiceUrl($mdServiceType, $mdServiceTypeVersion, $accessUrl);
				
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
				$serviceMetadata->service[$k]->datasetId = $datasetId;
				$serviceMetadata->service[$k]->serviceType = $mdServiceType;
				$serviceMetadata->service[$k]->serviceTitle = $mdTitle;
				$serviceMetadata->service[$k]->serviceDate = $mdDateStamp;
				$serviceMetadata->service[$k]->mdLink = $urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetRecordById&ElementSetName=full&outputSchema=".urlencode('http://www.isotc211.org/2005/gmd')."&id=".$fileIdentifier;
				$serviceMetadata->service[$k]->htmlLink = $scheme.'://'.$hostName.str_replace(basename($_SERVER['SCRIPT_NAME']), "mod_exportIso19139.php", $_SERVER['PHP_SELF'])."?url=".urlencode($urlWithoutRequest."?SERVICE=CSW&VERSION=2.0.2&REQUEST=GetRecordById&ElementSetName=full&outputSchema=".urlencode('http://www.isotc211.org/2005/gmd')."&id=".$fileIdentifier);
				/*if (is_array($mdAccessUrl)) {
					$mdAccessUrl = $mdAccessUrl[0];
				}*/
				
				//$serviceMetadata->service[$k]->accessUrl = $mdAccessUrl;

				if (in_array(strtoupper($mdServiceType), array('VIEW','OGC:WMS','WMS','PREDEFINED ATOM','DOWNLOAD','WFS','ATOM'))) {
					if (in_array(strtoupper($mdServiceType), array('PREDEFINED ATOM','DOWNLOAD','WFS','ATOM')) || in_array(strtoupper($mdServiceTypeVersion), array('PREDEFINED ATOM','DOWNLOAD','WFS','ATOM'))) {
						if (in_array(strtoupper($mdServiceType), array('PREDEFINED ATOM','ATOM')) || in_array(strtoupper($mdServiceTypeVersion), array('PREDEFINED ATOM','ATOM'))) {
							$serviceMetadata->service[$k]->accessClient = $scheme.'://'.$hostName.str_replace("php/".basename($_SERVER['SCRIPT_NAME']), "plugins/mb_downloadFeedClient.php", $_SERVER['PHP_SELF'])."?url=".urlencode($mdAccessUrl);
							$serviceMetadata->service[$k]->serviceSubType = 'ATOM';
							$serviceMetadata->service[$k]->serviceType = "download";
							$serviceMetadata->service[$k]->accessUrl = (string)$mdAccessUrl;
						} else {
							$serviceMetadata->service[$k]->serviceSubType = 'WFS';
							$serviceMetadata->service[$k]->serviceType = "download";						
							$serviceMetadata->service[$k]->accessUrl = (string)$mdAccessUrl;
						}
					} else {
						$serviceMetadata->service[$k]->serviceType = 'view';
						$serviceMetadata->service[$k]->accessUrl = correctWmsUrl($mdAccessUrl);
					}
				} else {
					$serviceMetadata->service[$k]->serviceType = 'other';
					if ($mdAccessUrl == "" || isempty($mdAccessUrl)) {
						$serviceMetadata->service[$k]->accessUrl = null;
					}
				}
			}
		}
		$serviceMetadata->service = array_values($serviceMetadata->service);
		$serviceMetadata->fileIdentifier = (string)$mbMetadata->fileIdentifier;
		$serviceMetadata->resourceResponsibleParty = (string)$mbMetadata->resourceResponsibleParty;
		$serviceMetadata->resourceContactEmail = (string)$mbMetadata->resourceContactEmail;
		$resultObj['message'] = "Coupling resolved successfully!"; 
		$resultObj['success'] = true;
		$serviceMetadata->genTime = microtime_float() - $starttime;
		$resultObj['result'] = $serviceMetadata;
		echo json_encode($resultObj, TRUE);
		die(); 	
	} else {
		$resultObj['message'] ='No csw based resource!!'; 
		$resultObj['result'] = null;
		echo json_encode($resultObj);
		die(); 	
	}
} else {
	$resultObj['message'] ='Hierarchy level of ISO metadata is '.$mbMetadata->hierarchyLevel.' - not dataset or series!'; 
	$resultObj['result'] = null;
	echo json_encode($resultObj);
	die(); 
}
?>
