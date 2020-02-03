<?php
/**
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
require_once(dirname(__FILE__) . '/../../core/globalSettings.php');
require_once(dirname(__FILE__).'/../classes/class_connector.php');
require_once(dirname(__FILE__).'/../classes/class_ckanApi.php');
require_once(dirname(__FILE__).'/../classes/class_group.php');
require_once(dirname(__FILE__) . '/../php/mod_getDownloadOptions.php');
require_once(dirname(__FILE__).'/../../conf/ckan.conf');
//classes for csw handling
require_once(dirname(__FILE__)."/../classes/class_cswClient.php");
require_once(dirname(__FILE__)."/../classes/class_csw.php");
/**
 * Class to provide functions to sync a mapbender metadata repository to a ckan instance, tested with ckan 2.5.3 in 2016
 *
 * @return syncCkan object
 */

class syncCkan {
    var $ckanApiKey;
    var $ckanApiUrl;
    var $ckanApiVersion;
    var $syncOrgaId;
    var $mapbenderUserId;
    var $topicCkanCategoryMap;
    var $topicDataThemeCategoryMap;
    var $frequencyMap;
    var $compareTimestamps;
    var $mapbenderUrl;

    public function __construct() {
        if (defined("CKAN_SERVER_PORT") && CKAN_SERVER_PORT !== '') {
	    $this->ckanApiUrl = CKAN_SERVER_IP.":".CKAN_SERVER_PORT;
	} else {
	    $this->ckanApiUrl = CKAN_SERVER_IP;
        }
	if (defined("CKAN_API_VERSION") && CKAN_API_VERSION !== '') {
	    $this->ckanApiVersion = CKAN_API_VERSION;
	} else {
	    $this->ckanApiVersion = 3;
        }
	if (defined("CKAN_API_PROTOCOL") && CKAN_API_PROTOCOL !== '') {
    	    $this->ckanApiProtocol = CKAN_API_PROTOCOL;
	} else {
    	    $this->ckanApiProtocol = 'https';
	}
        if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') { 
	   $this->mapbenderUrl = MAPBENDER_PATH;
        } else {
	    $this->mapbenderUrl = "http://www.geoportal.rlp.de/mapbender";
        }
	$this->mapbenderUserId = 0;
	$this->syncOrgaId = 0;
	$this->topicCkanCategoryMap = $topicCkanCategoryMap; //from ckan.conf
	//Mapping of DCAT-AP categories to iso topic categories
	//$this->topicDataThemeCategoryMap = $topicDataThemeCategoryMap; //from ckan.conf
$this->topicDataThemeCategoryMap = array(
	"1" => "AGRI,ENVI,HEAL",//"1" => "farming",
	"2" => "AGRI,ENVI,HEAL",//"2" => "biota",
	"3" => "GOVE,JUST,SOCI",//"3" => "boundaries",
	"4" => "AGRI,ENVI,HEAL,TECH",//"4" => "climatologyMeteorologyAtmosphere",
	"5" => "ECON,ENER,INTR",//"5" => "economy",
	"6" => "ENVI,TRAN,REGI",//"6" => "elevation",
	"7" => "ENVI",//"7" => "environment",
	"8" => "AGRI,ENVI,ENER,REGI,TECH,TRAN",//"8" => "geoscientificInformation",
	"9" => "HEAL",//"9" => "health",
	"10" => "AGRI,ENVI,TRAN",//"10" => "imageryBaseMapsEarthCover",
	"11" => "AGRI,ECON,GOVE,TRAN",//"11" => "intelligenceMilitary",
	"12" => "ENVI,REGI,AGRI",//"12" => "inlandWaters",
	"13" => "REGI",//"13" => "location",
	"14" => "ENVI",//"14" => "oceans",
	"15" => "TRAN,ENVI,ECON,AGRI,GOVE,SOCI,JUST",//"15" => "planningCadastre",
	"16" => "SOCI,EDUC,JUST",//"16" => "society",
	"17" => "AGRI,REGI,ENER,HEAL,GOVE",//"17" => "structure",
	"18" => "TRAN",//"18" => "transportation",
	"19" => "ECON,EDUC,ENER,TECH"//"19" => "utilitiesCommunication"
);

$this->frequencyMap = array(
	"continual" => "CONT",
	"daily" => "DAILY",
	"weekly" => "WEEKLY",
	"fortnightly" => "WEEKLY_2",
	"monthly" => "MONTHLY",
	"quarterly" => "QUARTERLY",
	"biannually" => "BIENNIAL",
	"annually" => "ANNUAL",
	"asNeeded" => "OTHER",
	"irregular" => "IRREG",
	"notPlanned" => "NEVER",
	"unknown" => "UNKNOWN"
);
	$this->compareTimestamps = false; //default to update each dataset, because the ckan index for metadata_modified may not be up to date !!!
    }

   //TODO: Following function is only needed til php 5.5 - after upgrade to debian 8 it is obsolet - see also class_iso19139.php!
   public function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

    public function getRemoteCkanOrga($orga_id) {
        $ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_organization_show($orga_id);
        return json_encode($result); 
    }

    public function purgeRemoteCkanOrga($orga_id) {
        $ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_organization_purge($orga_id);
        return json_encode($result);
    }

    public function getRemoteCkanOrgaRevList($orga_id) {
        $ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_organization_revision_list($orga_id);
        return json_encode($result);
    }

   public function createRemoteCkanOrga($orgaJson) {
        $ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_organization_create($orgaJson);
        return json_encode($result);
   }

   public function updateRemoteCkanOrga($orgaJson) {
        $ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_organization_update($orgaJson);
        return json_encode($result);
   }

   //function to get user info
   public function getRemoteCkanUser ($userJson) {
	$ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_user_show($userJson);
        return json_encode($result);
   }

   //function to create user in special group/organization
   public function createRemoteCkanUser ($userJson) {
	$ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_user_create($userJson);
        return json_encode($result);
   }

   //function to create user in special group/organization
   public function updateRemoteCkanUser ($userJson) {
	$ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_user_update($userJson);
        return json_encode($result);
   }

   //function to get member info
   public function getRemoteCkanMember ($userJson) {
	$ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_member_list($userJson);
        return json_encode($result);
   }

   //function to create member 
   public function createRemoteCkanMember ($userJson) {
	$ckan = new ckanApi($this->ckanApiKey, CKAN_SERVER_IP);
        $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
        $ckan->api_version = $this->ckanApiVersion;
        $result = $ckan->action_member_create($userJson);
        return json_encode($result);
   }

   public function getInternalOrgaAsCkan($mbGroupId) {
        $localOrgaConnector = new connector(MAPBENDER_PATH."/php/mod_showOrganizationInfo.php?id=".$mbGroupId."&outputFormat=ckan");
        $localOrgaConnector->set("timeOut", "3");
        if ($localOrgaConnector->timedOut == true) {
            return false;
        } else {
	    return $localOrgaConnector->file;
	}
    }

    //TODO - use paging for get record list!!!!!
    public function getRemoteCkanRecordList($orga_filter,$central_filter,$filter,$ckan_api_url,$ckan_api_version) {
        $ckanConnector = new connector($ckan_api_url.$ckan_api_version."/"."action/package_search?q=".$filter."&rows=1000"."&facet=true");
        $ckanConnector->set("timeOut", "3");
        if ($ckanConnector->timedOut == true) {
            return false;
        }
        $listOfFilteredData = json_decode($ckanConnector->file);
        $externalCkanMetadataArray = array();
        $countExternalCkanMetadataArray = 0;
        if ($listOfFilteredData->success == true) {
            foreach($listOfFilteredData->result->results as $dataset) {
                $externalCkanMetadataArray[$countExternalCkanMetadataArray]['uuid'] = $dataset->id;
                //$externalCkanMetadataArray[$countExternalCkanMetadataArray]['name'] = $dataset->name;
                $externalCkanMetadataArray[$countExternalCkanMetadataArray]['changedate'] = $dataset->metadata_modified;
                $countExternalCkanMetadataArray++;
	        //echo $dataset->id." - ".$dataset->metadata_modified."<br>";
            }
	    //$e = new mb_exception("Number of results: ".$countExternalCkanMetadataArray);	
        }
        return $externalCkanMetadataArray;
    }

    public function getCkanRepresentationFromCkan($ckan_api_url, $ckan_api_version, $ckan_package_id, $central_filter, $orga_ident) {
	$ckanConnector = new connector($ckan_api_url.$ckan_api_version."/"."action/package_show?id=".$ckan_package_id);
        $ckanConnector->set("timeOut", "3");
        if ($ckanConnector->timedOut == true) {
            return false;
        }
        $ckanPackageJson = $ckanConnector->file;
//$e = new mb_exception("remote ckan package: ".$ckanPackageJson);
        $ckanPackageRemote = json_decode($ckanPackageJson);
	$ckanPackage->title = "Demo: ".$ckanPackageRemote->result->title;
//use identical name and value!
	$ckanPackage->id = $ckanPackageRemote->result->id;	
$ckanPackage->name = $ckanPackageRemote->result->id;
//."_external_id";
$ckanPackage->owner_org = $orga_ident;	
$ckanPackage->notes = "notes...";

$ckanPackage->groups[0]->name = "transparenzgesetz";
$ckanPackage->state = "active";
$ckanPackage->type = "dataset";
$ckanPackage->license_id = "odc-odbl";
	//pull filter
	if (isset($central_filter) && $central_filter !== "") {
		$central_filterArray = explode(":",$central_filter);
		$ckanPackage->{$central_filterArray[0]} = $central_filterArray[1];
	}
	//$ckanPackage->transparency_category_de_rp = "spatial_data";
	$resourcesArray = array();

	foreach($ckanPackageRemote->result->resources as $resource) {
		$newResource = new stdClass();
		if (isset($resource->name) && $resource->name !=='') {
			$newResource->name = $resource->name;
		} else {
			$newResource->name = "Testtitel (name) für resource";
		}
		if (isset($resource->url) && $resource->url !=='') {
			$newResource->url = $resource->url;
		} else {
			$newResource->url = "http://www.geoportal.rlp.de";
		}
		if (isset($resource->format) && $resource->format !=='') {
			$newResource->format = $resource->format;
		} else {
			$newResource->format = "PDF";
		}	
		$resourcesArray[] = $newResource;
	}

	/*$resourcesArray[0]->url = "http://www.geoportal.rlp.de";
	$resourcesArray[0]->format = "PDF";*/

	$ckanPackage->resources = $resourcesArray;

	//$e = new mb_exception("ckan json object test: ".json_encode($ckanPackage));
        $returnArray = array();
        $returnArray['json'] = json_encode($ckanPackage);	
	return $returnArray;	
    }


    public function getCswRecordList($cswId, $orgaName, $recordType) {
	//function to call external csw with orga filter (filter orga which is responsible for publication!)
	$recordsPerPage = 20;
	$csw = new csw();
        $csw->createCatObjFromDB($cswId);
        $cswClient = new cswClient();
        $cswClient->cswId = $cswId;
	$additionalFilter='<ogc:PropertyIsEqualTo><ogc:PropertyName>apiso:OrganisationName</ogc:PropertyName><ogc:Literal>'.$orgaName.'</ogc:Literal></ogc:PropertyIsEqualTo>';
	$cswResponseObject = $cswClient->doRequest($cswClient->cswId, 'counthits', false, false, $recordType, false, false, $additionalFilter);
        //$e = new mb_exception("Number of type ".$recordType." datasets for orga: ".$orgaName." in portal CSW: ".$cswClient->operationResult);
	$maxRecords = (integer)$cswClient->operationResult;
	$pages = ceil($maxRecords / $recordsPerPage);
	$metadataArray = array();
	$numberOfMetadataRecords = 0;
	for ($i = 0; $i <= $pages-1 ; $i++) {
		$cswClient = new cswClient();
		$cswClient->cswId = $cswId;
		$result = $cswClient->doRequest($cswClient->cswId, 'getrecordspaging', false, false, $recordType, $recordsPerPage, ($i*$recordsPerPage)+1, $additionalFilter);
		$page = $i + 1;
		//$e = new mb_exception("page: ".$page." (".$pages.")");
		if ($cswClient->operationSuccessful == true) {
			//$e = new mb_exception("operation successfull");	
			//$e = new mb_exception(gettype($cswClient->operationResult));
			$metadataRecord = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
			//$e = new mb_exception("number of records: ".count($metadataRecord));
			//what is possible: keywords, categories?, spatial, ...
			for ($k = 1; $k <= count($metadataRecord) ; $k++) {
				$fileIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:fileIdentifier/gco:CharacterString');
				$fileIdentifier = (string)$fileIdentifier[0];
				$mdDateStamp = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:dateStamp/gco:Date');
				$mdDateStamp = (string)$mdDateStamp[0];
				$datasetIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:identificationInfo/gmd:MD_DataIdentification/@uuid');
				$datasetidentifier = (string)$datasetidentifier[0];
				$url =  $cswClient->operationResult->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata['.$k.']/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
				$url = (string)$url[0];
				if (isset($url) && $url !=="") {
					//$metadataArray[$numberOfMetadataRecords]['uuid'] = $datasetIdentifier;
					$metadataArray[$numberOfMetadataRecords]['uuid'] = $fileIdentifier;
					$metadataArray[$numberOfMetadataRecords]['changedate'] = $mdDateStamp;
					$numberOfMetadataRecords++;
				}
			}
		}
	}
	//$e = new mb_exception(json_encode($metadataArray, true));
	return $metadataArray;
    }

    public function getMapbenderOrganizations() {
	//$e = new mb_exception("getMapbenderOrganizations-> this->syncOrgaId: ".$this->syncOrgaId);

        if (isset($this->mapbenderUserId) && (integer)$this->mapbenderUserId > 0) {
            if (isset($this->syncOrgaId) && (integer)$this->syncOrgaId > 0) {
	        $sql = "SELECT DISTINCT mb_group_id, mb_group_title, mb_group_name, mb_group_title, mb_group_email, mb_group_ckan_uuid, mb_group_ckan_api_key, mb_group_csw_catalogues, mb_group_ckan_catalogues, mb_user_mb_group.mb_user_mb_group_type FROM mb_group JOIN mb_user_mb_group ON mb_group_id = fkey_mb_group_id AND fkey_mb_user_id = $1 AND mb_group_id = $2 AND mb_user_mb_group_type IN (2,3)";
	        $v = array($this->mapbenderUserId, $this->syncOrgaId);
	        $t = array('i','i');
            } else {
	        $sql = "SELECT DISTINCT mb_group_id, mb_group_title, mb_group_name, mb_group_title, mb_group_email, mb_group_ckan_uuid, mb_group_ckan_api_key, mb_group_csw_catalogues, mb_group_ckan_catalogues, mb_user_mb_group.mb_user_mb_group_type FROM mb_group JOIN mb_user_mb_group ON mb_group_id = fkey_mb_group_id AND fkey_mb_user_id = $1 AND mb_user_mb_group_type IN (2,3)";
	        $v = array($this->mapbenderUserId);
	        $t = array('i');
            }
            $res = db_prep_query($sql, $v, $t);
            $countDepArray = 0;
            while($row = db_fetch_array($res)){
                $departmentsArray[$countDepArray]["id"] = $row["mb_group_id"];
                $departmentsArray[$countDepArray]["name"] = $row["mb_group_name"];
                $departmentsArray[$countDepArray]["email"] = $row["mb_group_email"];
                $departmentsArray[$countDepArray]["title"] = $row["mb_group_title"];
                if ($departmentsArray[$countDepArray]["email"] == "" || $departmentsArray[$countDepArray]["email"] == null) {
                    $departmentsArray[$countDepArray]["email"] = "dummy@geoportal.rlp.de";
                }
                if ($departmentsArray[$countDepArray]["title"] == "" || $departmentsArray[$countDepArray]["title"] == null) {
                    $departmentsArray[$countDepArray]["title"] = "dummy contact title";
                }
                $departmentsArray[$countDepArray]["ckan_uuid"] = $row["mb_group_ckan_uuid"];
                $departmentsArray[$countDepArray]["ckan_api_key"] = $row["mb_group_ckan_api_key"];
		//if ($row["mb_group_csw_catalogues"] !== null && $row["mb_group_csw_catalogues"] !== '') {
			$departmentsArray[$countDepArray]["csw_catalogues"] = $row["mb_group_csw_catalogues"];
			$departmentsArray[$countDepArray]["ckan_catalogues"] = $row["mb_group_ckan_catalogues"];
		//}
                if ($row["mb_user_mb_group_type"] == 2) {
                    $departmentsArray[$countDepArray]["is_primary_group"] = true;
                } else {
                    $departmentsArray[$countDepArray]["is_primary_group"] = false;
                }
                $countDepArray = $countDepArray + 1; 
            }
            //TODO: get user information about the registrating person for metadata contact!
            if ($countDepArray == 0) {
	        $e = new mb_exception("classes/class_syncCkan.php:No organization found for which the user with id ".$this->mapbenderUserId." is allowed to publish metadata!");
                return false;
            }
            return $departmentsArray;
        } else {
            $e = new mb_exception("classes/class_syncCkan.php: No mapbenderUserId is given for the class!");
            return false;
        }
    }

    //function to give back a mixed upper/lowercase string array from the input of a lowercase array and the haystack which is mixed upper/and lowercase 
    public function searchUpperLowerCase($lowerCaseStringArray, $mixedStringArray) {
	$result = array();
	foreach($lowerCaseStringArray as $lowerCaseString) {
		if (array_search($lowerCaseString, $mixedStringArray) !== false) {
			$result[] = $mixedStringArray[array_search($lowerCaseString, $mixedStringArray)];
		} else {
			if (array_search(strtoupper($lowerCaseString), $mixedStringArray) !== false) {
				$result[] = $mixedStringArray[array_search(strtoupper($lowerCaseString), $mixedStringArray)];
			}
		}
	}
    	return $result;
    }

    public function getExternalSource() {

    }

    public function syncExternalSource() {
    
    }

    public function getSyncListRemoteCkanJson($departmentsArray, $departmentId, $listAllMetadataInJson = false) {
        $syncListRemoteCkan = new stdClass(); //should handle the returned json object
	$syncListResultRemoteCkan = new stdClass();
	$syncListRemoteCkan->help = "helptext";
	$syncListRemoteCkan->success = false;
        $syncListRemoteCkan->function = "getSyncListRemoteCkanJson";
        $numberOfCatalogue = 0;
	//TODO - choose right department!!!!
	//get array index 
	$index = 0;
	$idFound == false;

$e = new mb_exception("classes/class_syncCkan.php: parameter departmentId: ".$departmentId);

	foreach ($departmentsArray as $department) {
		$dId = (integer)$department['id'];
		if ($dId == $departmentId) {	
			$idFound = true;
			break;
		}
		$index++;
	}
	if ($idFound == false) {
	    $e = new mb_exception("classes/class_syncCkan.php: Requested department not found in departmentArray!");
	    return false;
	}
	$organization = $departmentsArray[$index];
	$catalogues = json_decode($departmentsArray[$index]["ckan_catalogues"])->ckan_catalogues;
        foreach ($catalogues as $catalogue) { //only one in this case

	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->name = $catalogue->ckan_name;
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->orga_filter = $catalogue->ckan_organisation_filter;
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_server_ip = $catalogue->ckan_server_ip;

	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->filter = $catalogue->ckan_filter;
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->central_filter = $catalogue->central_ckan_filter;
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_api_url = $catalogue->ckan_api_url;
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_api_version = $catalogue->ckan_api_version;

	    //things from mapbender database
 	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->name = $organization["name"];
$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->id = $organization["id"];
            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->title = $organization["title"];
            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->email = $organization["email"];
	    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_orga_ident = $organization["ckan_uuid"];

$e = new mb_exception("classes/class_syncCkan.php: uuid from departmentArray: ".$organization["ckan_uuid"]);

            if (isset($organization["ckan_uuid"]) && isset($organization["ckan_api_key"])) {
                //Test if organization with the given external uuid exists in the coupled ckan
                //show organizations of the authorized user (from geoportal group table) via action api
                $ckan = new ckanApi($organization["ckan_api_key"], CKAN_SERVER_IP);
                $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';

                $ckan->api_version = $this->ckanApiVersion;
                //$result = $ckan->action_organization_list_for_user();
		//new for ckan 2.7.X
		//$orgaQuery->id = $organization["ckan_api_key"];
		$orgaQuery->permission = "create_dataset";
		$orgaQuery->include_dataset_count = true;
		$result = $ckan->action_organization_list_for_user(json_encode($orgaQuery));
                foreach($result->result as $orga) {
                    if ($orga->id == $organization["ckan_uuid"]) {
			//foreach catalogue entry to sync
			$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_orga_ident = $organization["ckan_uuid"];        
                        //echo "Corresponding ckan organization ".$orga->display_name." found for geoportal group ".$organization["name"]." with id ".$organization["id"]."!<br>";
                        //get list of ids for existing spatial datasets - category spatial should be defined!!!!!!
                        //http://localhost:5000/api/3/action/package_search?fq=extras_transparency_category_de_rp:spatial_data
                        //with org: http://localhost:5000/api/3/action/package_search?fq=extras_transparency_category_de_rp:spatial_data%20AND%20owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32
			
                        //TODO: define standard category filter in ckan.conf!
                        //$queryObject->fq = STANDARD_CKAN_FILTER." AND owner_org:".$organization["ckan_uuid"];
			$queryObject->fq = $catalogue->central_ckan_filter." AND owner_org:".$organization["ckan_uuid"];
			//$queryObject->fq = "transparency_category_de_rp:spatial_data AND owner_org:".$organization["ckan_uuid"];
                        $queryObject->facet = "true";
			//$e = new mb_exception("test");
			$queryObject->rows = "1"; //TODO: maybe an problem somewhen
			//first count number of packages
			$listOfFilteredDataCount = $ckan->action_package_search(json_encode($queryObject));
			$numberOfPackagesPerPage = 200;
			if ($listOfFilteredDataCount->success == true) {
				$numberOfPackages = $listOfFilteredData->result->count;
				$numberOfPages = ceil((integer)$numberOfPackages / $numberOfPackagesPerPage);
			} else {
				$numberOfPages = 1;
			}
			$queryObject->rows = (string)$numberOfPackagesPerPage;
			//$e = new mb_exception("number of packages: ".$numberOfPackages);
			$listOfFilteredDataArray = array();
			for ($nP = 1; $nP <= $numberOfPages ; $nP++) {
				//$e = new mb_exception("page: ".$nP);
				$queryObject->start = ($nP - 1) * $numberOfPackagesPerPage;
				$listOfFilteredDataArray[] = $ckan->action_package_search(json_encode($queryObject));
			}
 			$countCkanMetadataArray = 0;
			$ckanMetadataArray = array();
 			$ckanPackageNames = array();
			$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->count_ckan_packages = $numberOfPackages;
			foreach ($listOfFilteredDataArray as $listOfFilteredData) {
                        	if ($listOfFilteredData->success == true) {
			    		//TODO - why only 10 records are given back when search?
                            		foreach ($listOfFilteredData->result->results as $dataset) {
                                		$ckanMetadataArray[$countCkanMetadataArray]['id'] = $dataset->id;
                                		$ckanMetadataArray[$countCkanMetadataArray]['name'] = $dataset->name;
                                		$ckanMetadataArray[$countCkanMetadataArray]['changedate'] = $dataset->metadata_modified;
                                		if ($listAllMetadataInJson == true) {
                                    			$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_packages[$countCkanMetadataArray]->id = $dataset->name;
                                    			$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_packages[$countCkanMetadataArray]->date_time = $dataset->metadata_modified;
                                		}
                                		//echo $dataset->title." - ".$dataset->name." - ".$dataset->metadata_modified."<br>";
                                		$ckanPackageNames[] = $dataset->name;
                                		$countCkanMetadataArray++;
						//$e = new mb_exception("ckan dataset number: ".$countCkanMetadataArray);
                            		}
                        	} else {
                            		$e = new mb_exception("classes/class_syncCkan.php: A problem while searching for datasets in ckan occured!");
                        	}	
			}			
                        // only list http://localhost:5000/api/3/action/package_list?q=owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32	
                        //pull all relevant information from mapbender database - first pull the resources which are owned by the corresponding group!
                        //only use metadata for which real licenses are defined !!!!!! - what should be done with the other metadata?- DO a left join!!!
			//get list of distributed metadata sets with their relevant arributes from csw search!!!!!

			//TODO: - exchange with ckan reader!!!!!
			//$externalCkanMetadataArray = $this->getCswRecordList((integer)$catalogue->catalogue_id, $catalogue->organisation_filter, 'nonGeographicDataset');
			$externalCkanMetadataArray = $this->getRemoteCkanRecordList($syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->orga_filter,$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->central_filter,$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->filter,$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_api_url,$syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->ckan_api_version);
			$countMetadataArray = count($externalCkanMetadataArray);
                        if ($countMetadataArray == 0) {
                        } else {
                            $numberRemoteCkanMetadata = 0;
                            $cswUuids = array();
                            foreach($externalCkanMetadataArray as $externalCkanMetadata) {
                                if ($listAllMetadataInJson == true) {
                                    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->datasource_metadata[$numberRemoteCkanMetadata]->id = $externalCkanMetadata['uuid'];
                                    $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->datasource_metadata[$numberRemoteCkanMetadata]->date_time = $externalCkanMetadata['changedate'];
                                }
                                $cswUuids[] = $externalCkanMetadata['uuid'];
                                $numberRemoteCkanMetadata++;
                            }
                            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->count_external_ckan_packages = $numberRemoteCkanMetadata;
                            //build diffs for ckan and geoportal
                            //Arrays: ckanPackageNames / cswUuids
                            //Those which are only in ckan: delete them
			    //TODO: Problem is that portalu/ingrid mix lowercase and uppercase uuids - to compare them always use lowercase
			    $cswUuidsLower = array_map('strtolower', $cswUuids);
//$e = new mb_exception(json_encode($cswUuidsLower));
//$e = new mb_exception(json_encode($ckanPackageNames));
                            $onlyInCkan = array_values(array_diff($ckanPackageNames, $cswUuidsLower)); //every time lowercase names!
//$e = new mb_exception(json_encode($onlyInCkan));
                            //Those which are only in csw: create them
                            $onlyInRemoteCkan = array_values(array_diff($cswUuidsLower, $ckanPackageNames));
//$e = new mb_exception(json_encode($onlyInRemoteCkan));
                            //Those which are in both - update them if geoportal metadata is newer than the package in ckan	
                            $inBoth = array_values(array_intersect($ckanPackageNames, $cswUuidsLower));
//$e = new mb_exception(json_encode($inBoth));
			    //rebuild key arrays to mixed case
			    $onlyInRemoteCkan = $this->searchUpperLowerCase($onlyInRemoteCkan, $cswUuids);
			    $inBoth = $this->searchUpperLowerCase($inBoth, $cswUuids);
                            //if the timestamps should be compared before
                            if ($this->compareTimestamps == true) { 
                                foreach ($inBoth as $uuid) {
                                    $e = new mb_notice("classes/class_syncCkan.php: search for uuid: ".$uuid."ckan time : ".$ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']. " - csw time: ".$externalCkanMetadataArray[array_search($uuid, $this->array_column($externalCkanMetadataArray,'uuid'))]['changedate']);
                                    $dateTimeCkan = new DateTime($ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']);
                                    $dateTimeRemoteCkan = new DateTime($externalCkanMetadataArray[array_search($uuid, $this->array_column($externalCkanMetadataArray,'uuid'))]['changedate']);
                                    if ($dateTimeCkan > $dateTimeRemoteCkan) {
                                        //delete from $inBoth!
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package newer than csw metadata!");
                                        $inBoth = array_values(array_diff($inBoth, [$uuid]));
                                    } else {
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package older than csw metadata!");
                                    }
                                }
                            }
                            //$e = new mb_exception("Number of packages which are only in ckan: ".count($onlyInCkan)." - number of packages which are only in geoportal: ".count($onlyInGeoportal)." - number of packages which are in both catalogues: ".count($inBoth));
			    //$e = new mb_exception("both: ".gettype($inBoth)." number: ".count($inBoth)." json: ".json_encode($inBoth));
		            //$e = new mb_exception("only ckan: ".gettype($onlyInCkan)." number: ".count($onlyInCkan)." json: ".json_encode($onlyInCkan));
                            //$e = new mb_exception("only geoportal: ".gettype($onlyInGeoportal)." number: ".count($onlyInGeoportal)." json: ".json_encode($onlyInGeoportal));
                            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->update = $inBoth;
                            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->delete = $onlyInCkan;
                            $syncListResultRemoteCkan->external_ckan[$numberOfCatalogue]->create = $onlyInRemoteCkan;
                        }
                        //then pull the ressources which are owned by the user but have no group defined - only if the primary group of the user is the corresponding group!
                        //maybe the catalogue itself is the best way to pull all relevant data, cause the coupling is resolved already!
                        //filter for datasets:
                        //open data - defined by the licenses
                        //transparency - no filter at all - licenses only of some exists - otherwise none or freetext
                    }
                }
            } else {
                $e = new mb_exception("classes/class_syncCkan.php: Coupled ckan cannot be synchronized, cause the required organization-id and/or API-Key is was not found for ".$organization["name"]);
            }
        $numberOfCatalogue++;
        }
	//TODO DEBUG
	/*	foreach($syncListResultRemoteCkan->datasource_metadata[0]->create as $cswFileIdentifier) {
			$e = new mb_exception($cswFileIdentifier);
			$resultCkanRepresentation = $this->getCkanRepresentationFromRemoteCkan($syncListResultRemoteCkan->datasource_metadata[0]->id, $cswFileIdentifier, "orga_name", "orga_title", "orga_email", $this->topicDataThemeCategoryMap, "transparency_category_de_rp:environmental_information");	
		}
	//
	*/
	if (count($syncListResultRemoteCkan->external_ckan) >= 1) {
	    $syncList->result = $syncListResultRemoteCkan;
            $syncList->success = true;
	}
        return json_encode($syncList);    
    }

    public function getSyncListCswJson($departmentsArray, $orgaId, $listAllMetadataInJson = false) {
        $syncListCsw = new stdClass(); //should handle the returned json object
	$syncListResultCsw = new stdClass();
	$syncListCsw->help = "helptext";
	$syncListCsw->success = false;
        $syncListCsw->function = "getSyncListCswJson";
        $numberOfCatalogue = 0;
	$organization = $departmentsArray[0];
	$catalogues = json_decode($departmentsArray[0]["csw_catalogues"])->csw_catalogues;
        foreach ($catalogues as $catalogue) { //only one in this case
	    $syncListResultCsw->external_csw[$numberOfCatalogue]->id = $catalogue->catalogue_id;
	    $syncListResultCsw->external_csw[$numberOfCatalogue]->orga_filter = $catalogue->organisation_filter;
	    $syncListResultCsw->external_csw[$numberOfCatalogue]->ckan_filter = $catalogue->ckan_filter;
	    //things from mapbender database
 	    $syncListResultCsw->external_csw[$numberOfCatalogue]->name = $organization["name"];
            $syncListResultCsw->external_csw[$numberOfCatalogue]->title = $organization["title"];
            $syncListResultCsw->external_csw[$numberOfCatalogue]->email = $organization["email"];
	    $syncListResultCsw->external_csw[$numberOfCatalogue]->ckan_orga_ident = $organization["ckan_uuid"];

            if (isset($organization["ckan_uuid"]) && isset($organization["ckan_api_key"])) {
                //Test if organization with the given external uuid exists in the coupled ckan
                //show organizations of the authorized user (from geoportal group table) via action api
                $ckan = new ckanApi($organization["ckan_api_key"], CKAN_SERVER_IP);
                $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';

                $ckan->api_version = $this->ckanApiVersion;
		//new for ckan 2.7.X
		//$orgaQuery->id = $organization["ckan_api_key"];
		$orgaQuery->permission = "create_dataset";
		$orgaQuery->include_dataset_count = true;
		$result = $ckan->action_organization_list_for_user(json_encode($orgaQuery));
		//$e = new mb_exception("orga list: ". json_encode($result));
                //$result = $ckan->action_organization_list_for_user();
                foreach($result->result as $orga) {
                    if ($orga->id == $organization["ckan_uuid"]) {
			//foreach catalogue entry to sync
			$syncListResultCsw->external_csw[$numberOfCatalogue]->ckan_orga_ident = $organization["ckan_uuid"];        
                        //echo "Corresponding ckan organization ".$orga->display_name." found for geoportal group ".$organization["name"]." with id ".$organization["id"]."!<br>";
                        //get list of ids for existing spatial datasets - category spatial should be defined!!!!!!
                        //http://localhost:5000/api/3/action/package_search?fq=transparency_category_de_rp:spatial_data
                        //with org: http://localhost:5000/api/3/action/package_search?fq=transparency_category_de_rp:spatial_data%20AND%20owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32
			
                        //TODO: define standard category filter in ckan.conf!
                        //$queryObject->fq = STANDARD_CKAN_FILTER." AND owner_org:".$organization["ckan_uuid"];
			$queryObject->fq = $catalogue->ckan_filter." AND owner_org:".$organization["ckan_uuid"];
			//$queryObject->fq = "transparency_category_de_rp:spatial_data AND owner_org:".$organization["ckan_uuid"];
                       $queryObject->facet = "true";
			$queryObject->rows = "1"; //TODO: maybe an problem somewhen
			//first count number of packages
			$listOfFilteredDataCount = $ckan->action_package_search(json_encode($queryObject));
			$numberOfPackagesPerPage = 200;
			if ($listOfFilteredDataCount->success == true) {
				$numberOfPackages = $listOfFilteredDataCount->result->count;
				$numberOfPages = ceil((integer)$numberOfPackages / $numberOfPackagesPerPage);
			} else {
				$numberOfPages = 1;
			}
			$queryObject->rows = (string)$numberOfPackagesPerPage;
			//$e = new mb_exception("number of packages: ".$numberOfPackages);
			$listOfFilteredDataArray = array();
			for ($nP = 1; $nP <= $numberOfPages ; $nP++) {
				//$e = new mb_exception("page: ".$nP);
				$queryObject->start = ($nP - 1) * $numberOfPackagesPerPage;
				$listOfFilteredDataArray[] = $ckan->action_package_search(json_encode($queryObject));
			}
			$countCkanMetadataArray = 0;
			$ckanMetadataArray = array();
 			$ckanPackageNames = array();
			$syncListResultCsw->external_csw[$numberOfCatalogue]->count_ckan_packages = $numberOfPackages;
			foreach ($listOfFilteredDataArray as $listOfFilteredData) {
                        	if ($listOfFilteredData->success == true) {
			    		//TODO - why only 10 records are given back when search?
                            		//$syncListResultCsw->external_ckan[$numberOfCatalogue]->count_ckan_packages = $numberOfPackages;
                            		//echo json_encode($listOfFilteredData)."<br>";
                            		//$e = new mb_exception("Number of results: ".$listOfFilteredData->result->count);
                            		foreach ($listOfFilteredData->result->results as $dataset) {
                                		$ckanMetadataArray[$countCkanMetadataArray]['id'] = $dataset->id;
                                		$ckanMetadataArray[$countCkanMetadataArray]['name'] = $dataset->name;
                                		$ckanMetadataArray[$countCkanMetadataArray]['changedate'] = $dataset->metadata_modified;
                                		if ($listAllMetadataInJson == true) {
                                    			$syncListResultCsw->external_csw[$numberOfCatalogue]->ckan_packages[$countCkanMetadataArray]->id = $dataset->name;
                                    			$syncListResultCsw->external_csw[$numberOfCatalogue]->ckan_packages[$countCkanMetadataArray]->date_time = $dataset->metadata_modified;
                                		}
                                		//echo $dataset->title." - ".$dataset->name." - ".$dataset->metadata_modified."<br>";
                                		$ckanPackageNames[] = $dataset->name;
                                		$countCkanMetadataArray++;
						//$e = new mb_exception("ckan dataset number: ".$countCkanMetadataArray);
                            		}
                        	} else {
                            		$e = new mb_exception("classes/class_syncCkan.php: A problem while searching for datasets in ckan occured!");
                        	}	
			}			
                        // only list http://localhost:5000/api/3/action/package_list?q=owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32	
                        //pull all relevant information from mapbender database - first pull the resources which are owned by the corresponding group!
                        //only use metadata for which real licenses are defined !!!!!! - what should be done with the other metadata?- DO a left join!!!
			//get list of distributed metadata sets with their relevant arributes from csw search!!!!!
			$cswMetadataArray = $this->getCswRecordList((integer)$catalogue->catalogue_id, $catalogue->organisation_filter, 'nonGeographicDataset');
			$countMetadataArray = count($cswMetadataArray);
                        if ($countMetadataArray == 0) {
                        } else {
                            $numberCswMetadata = 0;
                            $cswUuids = array();
                            foreach($cswMetadataArray as $cswMetadata) {
                                if ($listAllMetadataInJson == true) {
                                    $syncListResultCsw->external_csw[$numberOfCatalogue]->datasource_metadata[$numberCswMetadata]->id = $cswMetadata['uuid'];
                                    $syncListResultCsw->external_csw[$numberOfCatalogue]->datasource_metadata[$numberCswMetadata]->date_time = $cswMetadata['changedate'];
                                }
                                $cswUuids[] = $cswMetadata['uuid'];
                                $numberCswMetadata++;
                            }
                            $syncListResultCsw->external_csw[$numberOfCatalogue]->count_csw_packages = $numberCswMetadata;
                            //build diffs for ckan and geoportal
                            //Arrays: ckanPackageNames / cswUuids
                            //Those which are only in ckan: delete them
			    //TODO: Problem is that portalu/ingrid mix lowercase and uppercase uuids - to compare them always use lowercase
			    $cswUuidsLower = array_map('strtolower', $cswUuids);
//$e = new mb_exception(json_encode($cswUuidsLower));
//$e = new mb_exception(json_encode($ckanPackageNames));
                            $onlyInCkan = array_values(array_diff($ckanPackageNames, $cswUuidsLower)); //every time lowercase names!
//$e = new mb_exception(json_encode($onlyInCkan));
                            //Those which are only in csw: create them
                            $onlyInCsw = array_values(array_diff($cswUuidsLower, $ckanPackageNames));
//$e = new mb_exception(json_encode($onlyInCsw));
                            //Those which are in both - update them if geoportal metadata is newer than the package in ckan	
                            $inBoth = array_values(array_intersect($ckanPackageNames, $cswUuidsLower));
//$e = new mb_exception(json_encode($inBoth));
			    //rebuild key arrays to mixed case
			    $onlyInCsw = $this->searchUpperLowerCase($onlyInCsw, $cswUuids);
			    $inBoth = $this->searchUpperLowerCase($inBoth, $cswUuids);
                            //if the timestamps should be compared before
                            if ($this->compareTimestamps == true) { 
                                foreach ($inBoth as $uuid) {
                                    $e = new mb_notice("classes/class_syncCkan.php: search for uuid: ".$uuid."ckan time : ".$ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']. " - csw time: ".$cswMetadataArray[array_search($uuid, $this->array_column($cswMetadataArray,'uuid'))]['changedate']);
                                    $dateTimeCkan = new DateTime($ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']);
                                    $dateTimeCsw = new DateTime($cswMetadataArray[array_search($uuid, $this->array_column($cswMetadataArray,'uuid'))]['changedate']);
                                    if ($dateTimeCkan > $dateTimeCsw) {
                                        //delete from $inBoth!
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package newer than csw metadata!");
                                        $inBoth = array_values(array_diff($inBoth, [$uuid]));
                                    } else {
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package older than csw metadata!");
                                    }
                                }
                            }
                            //$e = new mb_exception("Number of packages which are only in ckan: ".count($onlyInCkan)." - number of packages which are only in geoportal: ".count($onlyInGeoportal)." - number of packages which are in both catalogues: ".count($inBoth));
			    //$e = new mb_exception("both: ".gettype($inBoth)." number: ".count($inBoth)." json: ".json_encode($inBoth));
		            //$e = new mb_exception("only ckan: ".gettype($onlyInCkan)." number: ".count($onlyInCkan)." json: ".json_encode($onlyInCkan));
                            //$e = new mb_exception("only geoportal: ".gettype($onlyInGeoportal)." number: ".count($onlyInGeoportal)." json: ".json_encode($onlyInGeoportal));
                            $syncListResultCsw->external_csw[$numberOfCatalogue]->update = $inBoth;
                            $syncListResultCsw->external_csw[$numberOfCatalogue]->delete = $onlyInCkan;
                            $syncListResultCsw->external_csw[$numberOfCatalogue]->create = $onlyInCsw;
                        }
                        //then pull the ressources which are owned by the user but have no group defined - only if the primary group of the user is the corresponding group!
                        //maybe the catalogue itself is the best way to pull all relevant data, cause the coupling is resolved already!
                        //filter for datasets:
                        //open data - defined by the licenses
                        //transparency - no filter at all - licenses only of some exists - otherwise none or freetext
                    }
                }
            } else {
                $e = new mb_exception("classes/class_syncCkan.php: Coupled ckan cannot be synchronized, cause the required organization-id and/or API-Key is was not found for ".$organization["name"]);
            }
        $numberOfCatalogue++;
        }
	//TODO DEBUG
	/*	foreach($syncListResultCsw->datasource_metadata[0]->create as $cswFileIdentifier) {
			$e = new mb_exception($cswFileIdentifier);
			$resultCkanRepresentation = $this->getCkanRepresentationFromCsw($syncListResultCsw->datasource_metadata[0]->id, $cswFileIdentifier, "orga_name", "orga_title", "orga_email", $this->topicDataThemeCategoryMap, "transparency_category_de_rp:environmental_information");	
		}
	//
	*/
	if (count($syncListResultCsw->external_csw) >= 1) {
	    $syncList->result = $syncListResultCsw;
            $syncList->success = true;
	}
        return json_encode($syncList);    
    }

    public function getSyncListJson($departmentsArray, $listAllMetadataInJson = false) {
        $syncList = new stdClass(); //should handle the returned json object
	$syncListResult = new stdClass();
	$syncList->help = "helptext";
	$syncList->success = false;
        $syncList->function = "getSyncListJson";
        $numberGeoportalOrga = 0;
        foreach ($departmentsArray as $organization) {
            $syncListResult->geoportal_organization[$numberGeoportalOrga]->name = $organization["name"];
            $syncListResult->geoportal_organization[$numberGeoportalOrga]->id = $organization["id"];
            $syncListResult->geoportal_organization[$numberGeoportalOrga]->title = $organization["title"];
            $syncListResult->geoportal_organization[$numberGeoportalOrga]->email = $organization["email"];
            $syncListResult->geoportal_organization[$numberGeoportalOrga]->ckan_orga_ident = false;
	    $syncListResult->geoportal_organization[$numberGeoportalOrga]->csw_catalogues = json_decode($organization["csw_catalogues"])->csw_catalogues;
            if (isset($organization["ckan_uuid"]) && isset($organization["ckan_api_key"])) {
                //Test if organization with the given external uuid exists in the coupled ckan
                //show organizations of the authorized user (from geoportal group table) via action api
                $ckan = new ckanApi($organization["ckan_api_key"], CKAN_SERVER_IP);
                $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
                $ckan->api_version = $this->ckanApiVersion;
		//new for ckan 2.7.X
		//$orgaQuery->id = $organization["ckan_api_key"];
		$orgaQuery->permission = "create_dataset";
		$orgaQuery->include_dataset_count = true;
		$result = $ckan->action_organization_list_for_user(json_encode($orgaQuery));
		//$e = new mb_exception("orga list: ". json_encode($result));
                // $result = $ckan->action_organization_list_for_user();
                foreach($result->result as $orga) {
                    if ($orga->id == $organization["ckan_uuid"]) {
                        $syncListResult->geoportal_organization[$numberGeoportalOrga]->ckan_orga_ident = $organization["ckan_uuid"];
                        //echo "Corresponding ckan organization ".$orga->display_name." found for geoportal group ".$organization["name"]." with id ".$organization["id"]."!<br>";
                        //get list of ids for existing spatial datasets - category spatial should be defined!!!!!!
                        //http://localhost:5000/api/3/action/package_search?fq=transparency_category_de_rp:spatial_data
                        //with org: http://localhost:5000/api/3/action/package_search?fq=transparency_category_de_rp:spatial_data%20AND%20owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32
                        //TODO: define standard category filter in ckan.conf for spatial data!
                        $queryObject->fq = STANDARD_CKAN_FILTER." AND owner_org:".$organization["ckan_uuid"];
			//
			//$queryObject->fq = "transparency_category_de_rp:spatial_data AND owner_org:".$organization["ckan_uuid"];
                        $queryObject->facet = "true";
			$queryObject->rows = "1"; //TODO: maybe an problem somewhen
			//first count number of packages
			$listOfFilteredDataCount = $ckan->action_package_search(json_encode($queryObject));
			$numberOfPackagesPerPage = 200;
			if ($listOfFilteredDataCount->success == true) {
				$numberOfPackages = $listOfFilteredDataCount->result->count;
				$numberOfPages = ceil((integer)$numberOfPackages / $numberOfPackagesPerPage);
			} else {
				$numberOfPages = 1;
			}
			$queryObject->rows = (string)$numberOfPackagesPerPage;
			//$e = new mb_exception("number of packages: ".$numberOfPackages);
			$listOfFilteredDataArray = array();
			for ($nP = 1; $nP <= $numberOfPages ; $nP++) {
				//$e = new mb_exception("page: ".$nP);
				$queryObject->start = ($nP - 1) * $numberOfPackagesPerPage;
				$listOfFilteredDataArray[] = $ckan->action_package_search(json_encode($queryObject));
			}
			$countCkanMetadataArray = 0;
			$ckanMetadataArray = array();
 			$ckanPackageNames = array();
			$syncListResult->geoportal_organization[$numberGeoportalOrga]->count_ckan_packages = $numberOfPackages;
			foreach ($listOfFilteredDataArray as $listOfFilteredData) {
                        	if ($listOfFilteredData->success == true) {
			    		//TODO - why only 10 records are given back when search?
                            		//$syncListResult->external_ckan[$numberOfCatalogue]->count_ckan_packages = $numberOfPackages;
                            		//echo json_encode($listOfFilteredData)."<br>";
                            		//$e = new mb_exception("Number of results: ".$listOfFilteredData->result->count);
                            		foreach ($listOfFilteredData->result->results as $dataset) {
                                		$ckanMetadataArray[$countCkanMetadataArray]['id'] = $dataset->id;
                                		$ckanMetadataArray[$countCkanMetadataArray]['name'] = $dataset->name;
                                		$ckanMetadataArray[$countCkanMetadataArray]['changedate'] = $dataset->metadata_modified;
                                		if ($listAllMetadataInJson == true) {
                                    			$syncListResult->geoportal_organization[$numberGeoportalOrga]->ckan_packages[$countCkanMetadataArray]->id = $dataset->name;
                                    			$syncListResult->geoportal_organization[$numberGeoportalOrga]->ckan_packages[$countCkanMetadataArray]->date_time = $dataset->metadata_modified;
                                		}
                                		//echo $dataset->title." - ".$dataset->name." - ".$dataset->metadata_modified."<br>";
                                		$ckanPackageNames[] = $dataset->name;
                                		$countCkanMetadataArray++;
						//$e = new mb_exception("ckan dataset number: ".$countCkanMetadataArray);
                            		}
                        	} else {
                            		$e = new mb_exception("classes/class_syncCkan.php: A problem while searching for datasets in ckan occured!");
                        	}	
			}			
                        //only list http://localhost:5000/api/3/action/package_list?q=owner_org:81476cf5-6c52-4e99-8b9f-6150d63fcb32	
                        //pull all relevant information from mapbender database - first pull the resources which are owned by the corresponding group!
                        //only use metadata for which real licenses are defined !!!!!! - what should be done with the other metadata?- DO a left join!!!
			//TODO: test what wents wrong, if fkey_mb_group_id is set in mapbender 
                        if ($organization['is_primary_group']) {
			    $sql = "SELECT metadata_id as ressource_id, 'metadata' as ressource_type, uuid::varchar, title, lastchanged, fkey_termsofuse_id, f_get_coupled_resources(metadata_id) from mb_metadata LEFT OUTER JOIN md_termsofuse ON mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id WHERE fkey_mb_user_id = $1 AND (fkey_mb_group_id is null OR fkey_mb_group_id = 0 OR fkey_mb_group_id = $3) AND export2csw IS true AND md_termsofuse.fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1) ";
			    $sql .= " UNION SELECT layer_metadata.ressource_id, layer_metadata.ressource_type, layer_metadata.uuid::varchar, layer_metadata.title, to_timestamp(layer_metadata.lastchanged), wms_termsofuse.fkey_termsofuse_id, '{\"coupledResources\":{\"layerIds\":[' || layer_metadata.ressource_id || '],\"featuretypeIds\":[]}}' as f_get_coupled_resources FROM ";
			    $sql .= "(SELECT layer_id as ressource_id, 'layer' as ressource_type, layer.uuid::varchar, layer_title as title, wms.wms_timestamp as lastchanged, layer.fkey_wms_id FROM layer INNER JOIN wms on layer.fkey_wms_id = wms.wms_id WHERE wms_owner = $2 AND (fkey_mb_group_id is null OR fkey_mb_group_id = 0 OR fkey_mb_group_id = $3)";
			    $sql .= " AND layer.export2csw IS true AND layer.layer_searchable = 1 AND layer_id NOT IN (SELECT DISTINCT fkey_layer_id FROM ows_relation_metadata WHERE fkey_layer_id IS NOT NULL)) AS layer_metadata INNER JOIN wms_termsofuse ON layer_metadata.fkey_wms_id = wms_termsofuse.fkey_wms_id AND fkey_termsofuse_id IS NOT NULL AND wms_termsofuse.fkey_termsofuse_id IN (SELECT termsofuse_id FROM termsofuse WHERE isopen = 1)";
			    //$e = new mb_exception("class_syncCkan.php: sql: ".$sql);
                            $v = array($this->mapbenderUserId, $this->mapbenderUserId, $syncListResult->geoportal_organization[$numberGeoportalOrga]->id);
                            $t = array('i', 'i', 'i');
                        } else {
			   $sql = "SELECT metadata_id as ressource_id, 'metadata' as ressource_type, uuid::varchar, title, lastchanged, fkey_termsofuse_id, f_get_coupled_resources(metadata_id) from mb_metadata LEFT OUTER JOIN md_termsofuse ON mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id WHERE fkey_mb_group_id = $1 AND export2csw IS true AND md_termsofuse.fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)";
			    $sql .= " UNION SELECT layer_metadata.ressource_id, layer_metadata.ressource_type, layer_metadata.uuid::varchar, layer_metadata.title, to_timestamp(layer_metadata.lastchanged), wms_termsofuse.fkey_termsofuse_id, '{\"coupledResources\":{\"layerIds\":[' || layer_metadata.ressource_id || '],\"featuretypeIds\":[]}}' ";
			    $sql .= "as f_get_coupled_resources FROM (SELECT layer_id as ressource_id, 'layer' as ressource_type, layer.uuid::varchar, layer_title as title, wms.wms_timestamp as lastchanged, layer.fkey_wms_id FROM layer INNER JOIN wms on layer.fkey_wms_id = wms.wms_id WHERE fkey_mb_group_id = $2 AND layer.export2csw IS true AND layer.layer_searchable = 1 AND";
			    $sql .= " layer_id NOT IN (SELECT DISTINCT fkey_layer_id FROM ows_relation_metadata WHERE fkey_layer_id IS NOT NULL)) AS layer_metadata INNER JOIN wms_termsofuse ON layer_metadata.fkey_wms_id = wms_termsofuse.fkey_wms_id AND fkey_termsofuse_id IS NOT NULL AND wms_termsofuse.fkey_termsofuse_id IN (SELECT termsofuse_id FROM termsofuse WHERE isopen = 1)";
                            $v = array($organization['id'], $organization['id']);
                            $t = array('i', 'i');
                        }
                        $res = db_prep_query($sql, $v, $t);
                        $countMetadataArray = 0;
			$metadataArray = array();
                        $featuretypeArray = array();
                        $layerArray = array();
                        //echo "List of datasets in geoportal instance:"."<br>";
                        while($row = db_fetch_array($res)){
                            $metadataArray[$countMetadataArray]["hasResource"] = false;
                            $metadataArray[$countMetadataArray]["id"] = $row["ressource_id"];
                            $metadataArray[$countMetadataArray]["uuid"] = $row["uuid"];
                            $metadataArray[$countMetadataArray]["title"] = $row["title"];
                            $metadataArray[$countMetadataArray]["changedate"] = $row["lastchanged"];
                            $metadataArray[$countMetadataArray]["license_id"] = $row["fkey_termsofuse_id"];
                            $metadataArray[$countMetadataArray]["resources"] = $row["f_get_coupled_resources"];
			    $metadataArray[$countMetadataArray]["resource_type"] = $row["ressource_type"];
                            foreach (json_decode($metadataArray[$countMetadataArray]["resources"])->coupledResources->layerIds as $layerId) {
                                $layerArray[] = $layerId;
                                $metadataArray[$countMetadataArray]["hasResource"] = true;
                            }
                            foreach (json_decode($metadataArray[$countMetadataArray]["resources"])->coupledResources->featuretypeIds as $featuretypeId) {
                                $featuretypeArray[] = $featuretypeId;
                                $metadataArray[$countMetadataArray]["hasResource"] = true;
                            }
                            //echo $metadataArray[$countMetadataArray]["title"]." - ".$metadataArray[$countMetadataArray]["uuid"]." - ".$metadataArray[$countMetadataArray]["changedate"]." - ".$metadataArray[$countMetadataArray]["license_id"]." - ".$metadataArray[$countMetadataArray]["resources"]."<br>";
                            $countMetadataArray = $countMetadataArray + 1; 
                        }
                        if ($countMetadataArray == 0) {
                            //echo "No published metadata found for user with id ".$userId." <br>";
                            //delete all records from external ckan instance
                            /*foreach ($ckanMetadataArray as $ckanPackage) {
                                //echo "Ckan package ".$ckanPackage['name']." should be deleted"."<br>";
                            }*/
                        } else {
                            $numberGeoportalMetadata = 0;
                            $geoportalUuids = array();
                            foreach($metadataArray as $geoportalMetadata) {
                                if (count($layerArray) > 0  || count($featuretypeArray) > 0) {
                                    //use only those that have resources!
                                    if ($geoportalMetadata['hasResource']) {
                                        if ($listAllMetadataInJson == true) {
                                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->datasource_metadata[$numberGeoportalMetadata]->id = $geoportalMetadata['uuid'];
                                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->datasource_metadata[$numberGeoportalMetadata]->date_time = $geoportalMetadata['changedate'];
					    $syncListResult->geoportal_organization[$numberGeoportalOrga]->datasource_metadata[$numberGeoportalMetadata]->resource_type = $geoportalMetadata['resource_type'];
                                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->datasource_metadata[$numberGeoportalMetadata]->resources = json_decode($geoportalMetadata['resources']);
                                        }
                                        $geoportalUuids[] = $geoportalMetadata['uuid'];
                                        $numberGeoportalMetadata++;
                                    }
                                }
                            }
                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->count_geoportal_packages = $numberGeoportalMetadata;
                            //build diffs for ckan and geoportal
                            //Arrays: ckanPackageNames / geoportalUuids
                            //Those which are only in ckan: delete them
                            $onlyInCkan = array_values(array_diff($ckanPackageNames, $geoportalUuids));
                            //Those which are only in geoportal: create them
                            $onlyInGeoportal = array_values(array_diff($geoportalUuids, $ckanPackageNames));
                            //Those which are in both - update them if geoportal metadata is newer than the package in ckan
				
                            $inBoth = array_values(array_intersect($ckanPackageNames, $geoportalUuids));
                            //if the timestamps should be compared before
                            if ($this->compareTimestamps == true) { 
                                foreach ($inBoth as $uuid) {
                                    $e = new mb_notice("classes/class_syncCkan.php: search for uuid: ".$uuid."ckan time : ".$ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']. " - geoportal time: ".$metadataArray[array_search($uuid, $this->array_column($metadataArray,'uuid'))]['changedate']);
                                    $dateTimeCkan = new DateTime($ckanMetadataArray[array_search($uuid, $this->array_column($ckanMetadataArray,'name'))]['changedate']);
                                    $dateTimeGeoportal = new DateTime($metadataArray[array_search($uuid, $this->array_column($metadataArray,'uuid'))]['changedate']);
                                    if ($dateTimeCkan > $dateTimeGeoportal) {
                                        //delete from $inBoth!
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package newer than geoportals metadata!");
                                        $inBoth = array_values(array_diff($inBoth, [$uuid]));
                                    } else {
                                        $e = new mb_notice("classes/class_syncCkan.php: Ckans package older than geoportals metadata!");
                                    }
                                }
                            }
                            //$e = new mb_exception("Number of packages which are only in ckan: ".count($onlyInCkan)." - number of packages which are only in geoportal: ".count($onlyInGeoportal)." - number of packages which are in both catalogues: ".count($inBoth));
			    //$e = new mb_exception("both: ".gettype($inBoth)." number: ".count($inBoth)." json: ".json_encode($inBoth));
		            //$e = new mb_exception("only ckan: ".gettype($onlyInCkan)." number: ".count($onlyInCkan)." json: ".json_encode($onlyInCkan));
                            //$e = new mb_exception("only geoportal: ".gettype($onlyInGeoportal)." number: ".count($onlyInGeoportal)." json: ".json_encode($onlyInGeoportal));
                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->update = $inBoth;
                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->delete = $onlyInCkan;
                            $syncListResult->geoportal_organization[$numberGeoportalOrga]->create = $onlyInGeoportal;
                        }
                        //then pull the ressources which are owned by the user but have no group defined - only if the primary group of the user is the corresponding group!
                        //maybe the catalogue itself is the best way to pull all relevant data, cause the coupling is resolved already!
                        //filter for datasets:
                        //open data - defined by the licenses
                        //transparency - no filter at all - licenses only of some exists - otherwise none or freetext
                    }
                }
            } else {
                $e = new mb_exception("classes/class_syncCkan.php: Coupled ckan cannot be synchronized, cause the required organization-id and/or API-Key is was not found for ".$organization["name"]);
                //echo "<br> - ".$numberGeoportalOrga."<br>";
            }
            $numberGeoportalOrga++;
        }
	if (count($syncListResult->geoportal_organization) >= 1) {
	    $syncList->result = $syncListResult;
            $syncList->success = true;
	}
        return json_encode($syncList);    
    }

    /***
	syncListJson - Format:

    */
    public function syncSingleDataSource($syncListJson, $dataSourceType, $checkOrgaIdentity = false) {
	$dataSourceTypeArrayWithViews = array("portalucsw","mapbender");
        $resultObject = new stdClass();
        $resultObject->help = "Syncing datasource of type ".$dataSourceType." for organization with id: ".$this->syncOrgaId;
	$syncList = json_decode($syncListJson);
        $numberOfDeletedPackages = 0;
        $numberOfCreatedPackages = 0;
        $numberOfUpdatedPackages = 0; 
	if ($checkOrgaIdentity == true && (integer)$syncList->id !== $this->syncOrgaId) {
	    $e = new mb_exception("classes/class_syncCkan.php (syncSingleDataSource): Id from json is not identical to id from class! Sync will not be started!");
            $resultObject->success = false;
            $resultObject->error->message = "Organization id for the invoked sync process could not be obtained - please check the id!";
            return json_encode($resultObject);
	} else {
	    //read ckan api-key from database again, because we wont transfer it via json thru the web ;-)
	    $sql = "SELECT mb_group_ckan_api_key FROM mb_group WHERE mb_group_id = $1"; 
            $v = array($this->syncOrgaId);
	    $t = array('s');
	    $res = db_prep_query($sql, $v, $t);
	    //$e = new mb_exception("classes/class_syncCkan.php (syncSingleDataSource): syncList->update: ".json_encode($syncList->update));
	    //$e = new mb_exception("classes/class_syncCkan.php (syncSingleDataSource): syncList->delete: ".json_encode($syncList->delete));
	    //$e = new mb_exception("classes/class_syncCkan.php (syncSingleDataSource): syncList->create: ".json_encode($syncList->create));

	    if ($res) {
	        $row = db_fetch_assoc($res);
	        $ckanApiKey = $row['mb_group_ckan_api_key'];
	        $ckan = new ckanApi($ckanApiKey, CKAN_SERVER_IP);
                $ckan->base_url = $this->ckanApiProtocol.'://'.$this->ckanApiUrl.'/api/3/';
                $ckan->api_version = $this->ckanApiVersion;
	    } else {
	        $e = new mb_exception("classes/class_syncCkan.php: No api-key found for mapbender group: ".$this->syncOrgaId);
	        return false;
	    }
	    //delete orphaned datasets
            foreach($syncList->delete as $ckanNameToDelete) {
	        switch ($dataSourceType) {
		    case "portalucsw":
		        //TODO - PortalU Problem lowercase/uppercase
		        $ckanNameToDelete = strtolower($ckanNameToDelete);
		        break;
		    case "ckan":
		        break;
	        }		
                $result = $ckan->action_package_delete("{\"id\":\"".$ckanNameToDelete."\"}");
                if ($result->success == true) {
                    $e = new mb_notice("classes/class_syncCkan.php: Ckan package with name ".$ckanNameToDelete." successfully deleted!");
                    $numberOfDeletedPackages++;
                } else {
                    $e = new mb_exception("classes/class_syncCkan.php: A problem occured while trying to delete ckan package with name ".$ckanNameToDelete);
                }
            }
	    //$e = new mb_exception("classes/class_syncCkan.php: complete syncList as json: ".$syncListJson);
            foreach ($syncList->datasource_metadata as $datasetMetadata) {
		//$e = new mb_exception("classes/class_syncCkan.php: try to sync dataset with id: ".$datasetMetadata->id);
                if (in_array($datasetMetadata->id, $syncList->update) || in_array($datasetMetadata->id, $syncList->create)) {
		    //do some special preproccessing 
		    switch ($dataSourceType) {
		        case "mapbender":
                            $layerArrayMetadata = array();
                            $featuretypeArrayMetadata = array();
                            foreach ($datasetMetadata->resources->coupledResources->layerIds as $layerId) {
                                $layerArrayMetadata[] = $layerId;
                            }
                            foreach ($datasetMetadata->resources->coupledResources->featuretypeIds as $featuretypeId) {
                               $featuretypeArrayMetadata[] = $featuretypeId;
                            }
			    $resourceJson->id = $datasetMetadata->id; //find by id and name name:http://docs.ckan.org/en/latest/api/
		            break;
		        case "portalucsw":
			    //override uuid with lowercase representation of uuid - problem of portalu implementation
			    $resourceJson->id = strtolower($datasetMetadata->id);
                            break;
                        default:
			    $resourceJson->id = $datasetMetadata->id;
			    break;
		    }
                    $result = $ckan->action_package_show(json_encode($resourceJson));
                    if ($result->success == true) {
		        //try to do an update
			//first try to read from datasource
		        switch ($dataSourceType) {
		            case "mapbender":
			        $resultCkanRepresentation = $this->getCkanRepresentation($datasetMetadata->id, $layerArrayMetadata, $featuretypeArrayMetadata, $syncList->ckan_orga_ident, $syncList->title, $syncList->email, $this->topicDataThemeCategoryMap, $datasetMetadata->resource_type);
			        break;
		            case "portalucsw":
			        $resultCkanRepresentation = $this->getCkanRepresentationFromCsw($syncList->id, $datasetMetadata->id, $syncList->ckan_orga_ident, $syncList->name, $syncList->email, $this->topicDataThemeCategoryMap, $syncList->ckan_filter);
		                break;
			    default:
                                //TODO: pull ckan json object from remote ckan source and transform it into central object - map attributes!!!!
				//$e = new mb_exception("classes/class_syncCkan.php: try to pull json object from remote ckan - id: ".$datasetMetadata->id);
				$resultCkanRepresentation = $this->getCkanRepresentationFromCkan($syncList->ckan_api_url, $syncList->ckan_api_version, $datasetMetadata->id, $syncList->central_filter, $syncList->ckan_orga_ident);
			
				//$resultCkanRepresentation = false;
			        //$resultCkanRepresentation = $this->getCkanRepresentation($datasetMetadata->id, $layerArrayMetadata, $featuretypeArrayMetadata, $syncList->ckan_orga_ident, $syncList->title, $syncList->email, $this->topicDataThemeCategoryMap, $datasetMetadata->resource_type);
			        break;
		        }
			//if reading was successful
                        if ($resultCkanRepresentation != false) {
			    //try to do an update via api
                            $result = $ckan->action_package_update($resultCkanRepresentation['json']);
			    //try to update views if they exists in this dataSourceType
                            if ($result->success == true) {
                                if (in_array($dataSourceType, $dataSourceTypeArrayWithViews)) {
                                    $viewsUpdateProtocol = $this->recreateResourceViews($ckan, $result, $resultCkanRepresentation);
				}
                                $numberOfUpdatedPackages++;
                            } else {
                                $e = new mb_exception("classes/class_syncCkan.php: An error occured while trying to update ".$resultCkanRepresentation['json']);
                            }
                        } else {
                            $e = new mb_exception("classes/class_syncCkan.php: An error occured while generate json from external source");
                        }
                    } else {
                        //create new package
			//first read from external source 
		        switch ($dataSourceType) {
		            case "mapbender":
			         $resultCkanRepresentation = $this->getCkanRepresentation($datasetMetadata->id, $layerArrayMetadata, $featuretypeArrayMetadata, $syncList->ckan_orga_ident, $syncList->title, $syncList->email, $this->topicDataThemeCategoryMap, $datasetMetadata->resource_type);
			        break;
		            case "portalucsw":
			        $resultCkanRepresentation = $this->getCkanRepresentationFromCsw($syncList->id, $datasetMetadata->id, $syncList->ckan_orga_ident, $syncList->title, $syncList->email, $this->topicDataThemeCategoryMap, $syncList->ckan_filter);
		                break;
			    default:
				//TODO: pull ckan json object from remote ckan source and transform it into central object - map attributes!!!!
				//$e = new mb_exception("classes/class_syncCkan.php: try to pull json object from remote ckan - id: ".$datasetMetadata->id);
				$resultCkanRepresentation = $this->getCkanRepresentationFromCkan($syncList->ckan_api_url, $syncList->ckan_api_version, $datasetMetadata->id, $syncList->central_filter, $syncList->ckan_orga_ident);
				//$resultCkanRepresentation = false;
			        //$resultCkanRepresentation = $this->getCkanRepresentation($datasetMetadata->id, $layerArrayMetadata, $featuretypeArrayMetadata, $syncList->ckan_orga_ident, $syncList->title, $syncList->email, $this->topicDataThemeCategoryMap, $datasetMetadata->resource_type);
			        break;
		        }
			//if read from source was successful
                        if ($resultCkanRepresentation != false) {
                            $result = $ckan->action_package_create($resultCkanRepresentation['json']);
			    //if creation from external source was successful - try to create
                            if ($result->success == true) {
				$numberOfCreatedPackages++;
				if (in_array($dataSourceType, $dataSourceTypeArrayWithViews)) {
                                   $viewsUpdateProtocol = $this->recreateResourceViews($ckan, $result, $resultCkanRepresentation);
                                }
                            } else {
                                $e = new mb_exception("classes/class_syncCkan.php: An error occured while trying to create ".$resultCkanRepresentation['json']." error: ".json_encode($result));
                            }
                        } else {
                            $e = new mb_exception("classes/class_syncCkan.php: A problem occured while trying to create the json object from ".$dataSourceType." metadata!");
                        }
                    }
                }
            }
            $resultObject->success = true;
            $resultObject->result->orga_id = $this->syncOrgaId;
            $resultObject->result->numberOfDeletedPackages = $numberOfDeletedPackages;
            $resultObject->result->numberOfUpdatedPackages = $numberOfUpdatedPackages;
            $resultObject->result->numberOfCreatedPackages = $numberOfCreatedPackages;
            return json_encode($resultObject);
        }
    }

    private function getCkanRepresentationFromCsw($cswId, $fileIdentifier, $ckan_orga_ident, $orgaTitle, $orgaEmail, $topicDataThemeCategoryMap, $ckanCategoryFilter) {
	//getRecordById
	$csw = new csw();
	$csw->createCatObjFromDB($cswId);
	$cswClient = new cswClient();
	$cswClient->cswId = $cswId;
	//$e = new mb_exception("invoke get record by id");
	//$e = new mb_exception("catalogue id = ".$cswId);
	$cswResponseObject = $cswClient->doRequest($cswClient->cswId, 'getrecordbyid', $fileIdentifier, false, false, false, false, false);
	//parse XML
	if ($cswClient->operationSuccessful == true) {
		$metadataTitle = $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString');
		$metadataTitle = (string)$metadataTitle[0];
		/*$fileIdentifier = $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:fileIdentifier/gco:CharacterString');
		$fileIdentifier = $fileIdentifier[0];*/

		$metadataAbstract = $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:abstract/gco:CharacterString');
		$metadataAbstract = (string)$metadataAbstract[0];

		$keywords = $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword/gco:CharacterString');

		$url =  $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
		$url = (string)$url[0];
		$resourceName =  $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:name/gco:CharacterString');
		$resourceName = (string)$resourceName[0];
		$format =  $cswClient->operationResult->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat/gmd:MD_Format/gmd:name/gco:CharacterString');
		$format = (string)$format[0];
		//$e = new mb_exception($metadataTitle);
		//$e = new mb_exception($fileIdentifier);
	        $originalMetadataUrl = $this->mapbenderUrl."/php/mod_exportIso19139.php?url=".urlencode($cswClient->operationUrl."?REQUEST=GetRecordById&VERSION=2.0.2&SERVICE=CSW&id=".$fileIdentifier);
		//TODO
	
        	//write json object
		$ckanPackage->title = $metadataTitle;
        	$ckanPackage->notes = $metadataAbstract;
$ckanPackage->name = strtolower($fileIdentifier);
		$ckanPackage->author = $orgaTitle;
		$ckanPackage->author_email = $orgaEmail;
		$ckanPackage->owner_org = $ckan_orga_ident;
		$ckanPackage->state = "active";
		$ckanPackage->private = false;
		//TODO - define one central category from dcat-ap for environmental data
		$ckanPackage->dcat_ap_eu_data_category = "ENVI";
        	//convert bbox - if available to geojson
		//TODO - use key of ckan category from conf!
		$ckanCategoryFilter = explode(":",$ckanCategoryFilter);
		$ckanPackage->{$ckanCategoryFilter[0]} = $ckanCategoryFilter[1];
		//$ckanPackage->type = "ckan-govdata-full-1-1";
		$ckanPackage->type = "dataset";
		$ckanPackage->tags = array();
		$keywords = array_unique($keywords);
		$keywordIndex = 0;
	        for ($i=0; $i < count($keywords); $i++) {
			if ($keywords[$i] !== "" && isset($keywords[$i]) && strpos($keywords[$i], " ") === false) {
                		$ckanPackage->tags[$keywordIndex]->name = (string)$keywords[$i];
				$keywordIndex++;
			}
            	}
		//Add resources (name/url/format)
		$resourcesArray = array();
		if (isset($resourceName) && $resourceName !=="") {
			$resourcesArray[0]->name = $resourceName;
		} else {
			$resourcesArray[0]->name = "Weitere Infos";
		}
		$resourcesArray[0]->url = $url;
		if (isset($format) && $format !=="") {
			$resourcesArray[0]->format = $format;
		} else {
			$resourcesArray[0]->format = "Unbekannt";
		}
		//$e = new mb_exception("classes/class_syncCkan.php: Original metadata url: ".$originalMetadataUrl);

		//Add further resource (name/id/description/url/format)
		$viewArray = array();

		$resourcesArray[1]->name = "Originäre Metadaten";// für ".$row['layer_title'];
		$resourcesArray[1]->id = $fileIdentifier."_iso19139";
		$resourcesArray[1]->description = $ckanPackage->title." - Anzeige der originären Metadaten";
		$resourcesArray[1]->url = $originalMetadataUrl;
		$resourcesArray[1]->format = "HTML";

		//views to generate
		$viewArray[0]['view_type'] = "webpage_view";
		$viewArray[0]['resource_id'] = $fileIdentifier."_iso19139";

		//build whole json structure
		$viewJson->resource_id = $fileIdentifier."_iso19139";
		$viewJson->title = "Metadaten HTML";
		$viewJson->description = "Metadaten HTML";
		$viewJson->view_type = "webpage_view";

		$viewArray[0]['json'] = json_encode($viewJson);

		//$ckanPackage->resources = array_unique($resourcesArray);
		$ckanPackage->resources = $resourcesArray;
	}

        $returnArray = array();
        $returnArray['json'] = json_encode($ckanPackage);
	//$e = new mb_exception("classes/class_syncCkan.php: package from csw: ".$returnArray['json']);
	$returnArray['views'] = $viewArray;
        return $returnArray;
	/*
        //tags
	$ckanPackage->type
        //categories
	//license - dummy!
	$ckanPackage->license_id = $row['name'];
	//$ckanPackage->license_id = "odc_odbl";
	$ckanPackage->license_title = $row['description'];
	$ckanPackage->license_url = $row['descriptionlink'];
        //build resource:
	$resourcesArray = array();
        $resourcesArray[0]->name = "";
        $resourcesArray[0]->url = "";
        $resourcesArray[0]->format = "";*/
	//$e = new mb_exception(json_encode($ckanPackage, true));
    }

    //function to get ckan representation from mapbender metadata
    private function getCkanRepresentation($uuid, $layerArray, $featuretypeArray, $orgaId, $orgaTitle, $orgaEmail, $topicCkanCategoryMap, $resourceType = 'metadata') {
	//alter protocol to https ;-)
	$this->mapbenderUrl = str_replace("http://", "https://", $this->mapbenderUrl);
	//all or only those which have standardized licenses?
	//$sql = "SELECT *, f_get_coupled_resources(metadata_id) from mb_metadata LEFT JOIN md_termsofuse ON mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id WHERE mb_metadata.uuid = $1";
	//$sql = "SELECT * , st_asgeojson(the_geom) as geojson from mb_metadata JOIN md_termsofuse ON mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id JOIN termsofuse ON md_termsofuse.fkey_termsofuse_id = termsofuse.termsofuse_id WHERE mb_metadata.uuid = $1 AND export2csw IS true";
	switch ($resourceType) {
		case "layer":
			$sql = <<<SQL

SELECT layer.layer_id as metadata_id, layer.uuid, layer.layer_title as title, layer.layer_abstract as abstract ,  f_get_responsible_organization_for_ressource(layer.fkey_wms_id, 'wms') as resp_party_id, termsofuse.* FROM layer LEFT OUTER JOIN wms_termsofuse ON layer.fkey_wms_id = wms_termsofuse.fkey_wms_id LEFT OUTER JOIN termsofuse ON wms_termsofuse.fkey_termsofuse_id = termsofuse.termsofuse_id WHERE layer.uuid = $1 AND layer.export2csw IS true AND layer.layer_searchable = 1

SQL;
			break;
		case "metadata":
			$sql = "SELECT * , st_asgeojson(the_geom) as geojson, f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as resp_party_id from mb_metadata LEFT OUTER JOIN md_termsofuse ON mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id LEFT OUTER JOIN termsofuse ON md_termsofuse.fkey_termsofuse_id = termsofuse.termsofuse_id WHERE mb_metadata.uuid = $1 AND export2csw IS true";
			break;
	}
	$v = array($uuid);
	$t = array('s');
	$res = db_prep_query($sql, $v, $t);
	if ($res) {
	    $row = db_fetch_assoc($res);
	} else {
	    $e = new mb_exception("classes/class_syncCkan.php: No metadata/layer found for uuid: ".$uuid);
	    return false;
	}
	//get responsible organisation information
	if ((!isset($row['responsible_party_name']) || $row['responsible_party_name'] == '') || (!isset($row['responsible_party_email']) || $row['responsible_party_email'] == '')) {
		//get info from mb_group_table
		$sqlGroup = "SELECT mb_group_name, mb_group_email FROM mb_group WHERE mb_group_id = $1";
		$groupId = $row['resp_party_id'];
		$vGroup = array($groupId);
		$tGroup = array('i');
		$resGroup = db_prep_query($sqlGroup, $vGroup, $tGroup);
		if ($resGroup) {
	    		$rowGroup = db_fetch_assoc($resGroup);
		} else {
	    		$e = new mb_exception("classes/class_syncCkan.php: No group found for id: ".$groupId);
	    	return false;
		}
		$respPartyName = $rowGroup['mb_group_name'];
		$respPartyEmail = $rowGroup['mb_group_email'];
	}  else {
		$respPartyName = $rowGroup['responsible_party_name'];
		$respPartyEmail = $rowGroup['responsible_party_email'];
	}
	//title
	$metadataId = $row['metadata_id'];
	$metadataUuid = $row['uuid'];
	$ckanPackage->title = $row['title'];
	$ckanPackage->notes = $row['abstract'];
	//build groups
	//$ckanPackage->groups = ""; //[{"name":"opendatagesetz"},{"name":"transparenzgesetz"}]
	$ckanPackage->groups[0]->name = "transparenzgesetz";
        if ($row['isopen'] == 1) {
	    $ckanPackage->groups[1]->name = "opendata";
	    $ckanPackage->isopen = true;
        } else {
	    $ckanPackage->isopen = false;
	}
	$ckanPackage->name = $row['uuid'];
	$ckanPackage->owner_org = $orgaId;
	$ckanPackage->maintainer = $respPartyName;
	$ckanPackage->dataresponsibleauthorities = $respPartyName;
	$ckanPackage->maintainer_email = str_replace("(at)", "@", $respPartyEmail);
	$ckanPackage->private = false;
	//$ckanPackage->id = $row['uuid'];
	$ckanPackage->author = $orgaTitle;
	$ckanPackage->author_email = str_replace("(at)", "@", $orgaEmail);
	$ckanPackage->state = "active";
	if (isset($row['name']) && $row['name'] !== '') {
		$ckanPackage->license_id = $row['name'];
		//$ckanPackage->license_id = "odc_odbl";
		$ckanPackage->license_title = $row['description'];
		$ckanPackage->license_url = $row['descriptionlink'];
	} else {
		$ckanPackage->license_id = 'notspecified';
		$ckanPackage->license_title = "Keine definierte Lizenz";
	}
	if ($resourceType == 'metadata') {
		$ckanPackage->spatial = $row['geojson'];
	} else {	
		$ckanPackage->spatial = '{"type":"Polygon","coordinates":[[[6.05975,48.934399999999997],[6.05975,50.947499999999998],[8.51291,50.947499999999998],[8.51291,48.934399999999997],[6.05975,48.934399999999997]]]}';
	}
        //$ckanPackage->url = "";
	//special categories
	//$ckanPackage->govdata_categories = [];
	$ckanPackage->transparency_category_de_rp = "spatial_data";
$ckanPackage->registerobject_type = "Par_7_1_9";

//$e = new mb_exception("update_frequency from db: ".$row['update_frequency']." - frequency for dcat: ".$this->frequencyMap[$row['update_frequency']]);
	if ($resourceType == 'metadata') {
		if (array_key_exists($row['update_frequency'],$this->frequencyMap)) {
			$ckanPackage->frequency = $this->frequencyMap[$row['update_frequency']];
		}
	}
	$ckanPackage->type = "geodata";
	//build resources:
	$resourcesArray = array();
	//initialize views - things for which a preview should be available 
	$viewArray = array();
	$indexResourceArray = 0;
	$indexViewArray = 0;
	//add html preview for metadata
	$resourcesArray[$indexResourceArray]->name = "Originäre Metadaten";// für ".$row['layer_title'];
	$resourcesArray[$indexResourceArray]->id = $metadataUuid."_iso19139";
	$resourcesArray[$indexResourceArray]->description = $ckanPackage->title." - Anzeige der originären Metadaten";

	switch ($resourceType) {
		case "layer":
			$resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/php/mod_showMetadata.php?resource=layer&layout=tabs&redirectToMetadataUrl=1&id=".$metadataId;
			break;
		case "metadata":
			$resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/php/mod_exportIso19139.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D".$metadataUuid;
			break;
	}

	$resourcesArray[$indexResourceArray]->format = "HTML";
	$indexResourceArray++;
	//views to generate
	$viewArray[$indexViewArray]['view_type'] = "webpage_view";
	$viewArray[$indexViewArray]['resource_id'] = $metadataUuid."_iso19139";
	//build whole json structure
	$viewJson->resource_id = $metadataUuid."_iso19139";
	$viewJson->title = "Metadaten HTML";
	$viewJson->description = "Metadaten HTML - GeoPortal.rlp Metadaten Plugin";
	$viewJson->view_type = "webpage_view";
	$viewArray[$indexViewArray]['json'] = json_encode($viewJson);
	$indexViewArray++;
	if (count($layerArray) > 0) {
	    //select relevant layer information
	    $sql = "SELECT layer_id, layer_title, uuid from layer WHERE layer_id IN (".implode(",", $layerArray).")";
	    $res = db_query($sql);
	    if ($res) {
	        while($row = db_fetch_array($res)) {
	            //generate "Kartenviewer intern" resource
	            $resourcesArray[$indexResourceArray]->name = "Onlinekarte";//: ".$row['layer_title'];
	            $resourcesArray[$indexResourceArray]->id = $row['uuid']."_geoportalrlp_mobile";
	            $resourcesArray[$indexResourceArray]->description = "Ebene: ".$row['layer_title']." - Vorschau im integrierten Kartenviewer";
	            $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/extensions/mobilemap/map.php?layerid=".$row['layer_id'];
	            $resourcesArray[$indexResourceArray]->format = "Karte";
		    //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
	            $indexResourceArray++;
	            //views to generate
	            $viewArray[$indexViewArray]['view_type'] = "webpage_view";
	            $viewArray[$indexViewArray]['resource_id'] = $row['uuid']."_geoportalrlp_mobile";
	            //build whole json structure
	            $viewJson->resource_id = $row['uuid']."_geoportalrlp_mobile";
	            //$viewJson->id = $row['uuid']."_geoportalrlp_mobile_view";
	            $viewJson->title = "Integrierte Kartenanzeige";
	            $viewJson->description = "GeoPortal.rlp Plugin zur Anzeige von Geodaten";
	            $viewJson->view_type = "webpage_view";
	            $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
	            $indexViewArray++;
	            //generate "Kartenviewer extern" resource
	            $resourcesArray[$indexResourceArray]->name = "GeoPortal.rlp";//: ".$row['layer_title'];
	            $resourcesArray[$indexResourceArray]->id = $row['uuid']."_geoportalrlp";
	            $resourcesArray[$indexResourceArray]->description = "Ebene: ".$row['layer_title']." - Anzeige im GeoPortal.rlp";
	            $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/../portal/karten.html?LAYER[zoom]=1&LAYER[id]=".$row['layer_id'];
//Solve problem - don't use https for invoking mapbender in geoportal 
//$resourcesArray[$indexResourceArray]->url = str_replace("https", "http", $resourcesArray[$indexResourceArray]->url);
	            $resourcesArray[$indexResourceArray]->format = "Webanwendung";				
	            $indexResourceArray++;
	            //generate wms capabilities resource 
	            $resourcesArray[$indexResourceArray]->name = "WMS Schnittstelle";// für ".$row['layer_title'];
	            $resourcesArray[$indexResourceArray]->id = $row['uuid']."_capabilities";
	            $resourcesArray[$indexResourceArray]->description = "Ebene: ".$row['layer_title'];
	            $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/php/wms.php?layer_id=".$row['layer_id']."&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
	            $resourcesArray[$indexResourceArray]->format = "WMS";
	            $indexResourceArray++;
	        }
	    }
        }
        //for INSPIRE ATOM Feed implementations
	//$e = new mb_exception("Download options for: ".$metadataUuid);
        $downloadOptionsMetadataArray = array();
        $downloadOptionsMetadataArray[0] = $metadataUuid;
        $downloadOptionsJson = getDownloadOptions($downloadOptionsMetadataArray);
        $metadataObject = json_decode($downloadOptionsJson)->{$metadataUuid};
        if ($downloadOptionsJson !== null) {
            foreach ($metadataObject->option as $option) {
            //$e = new mb_exception("option: ".json_encode($option));	
            switch ($option->type) {
                case "wmslayergetmap":
                    $resourcesArray[$indexResourceArray]->name = "Download (INSPIRE)";//: ".$metadataObject->title;
                    $resourcesArray[$indexResourceArray]->id = $option->serviceUuid;
                    $resourcesArray[$indexResourceArray]->description = "Download von Rasterdaten über INSPIRE ATOM Feed: ".$metadataObject->title;
                    $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/plugins/mb_downloadFeedClient.php?url=".urlencode($this->mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$metadataUuid."&type=SERVICE&generateFrom=wmslayer&layerid=".$option->resourceId);
                    $resourcesArray[$indexResourceArray]->format = "Diverse";
		    //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
                    $indexResourceArray++;
                    //views to generate
                    //build whole json structure
                    $viewJson->resource_id = $option->serviceUuid;
                    //$viewJson->id = $option->serviceUuid."_view";
                    $viewJson->title = "INSPIRE ATOM Feed Viewer";
                    $viewJson->description = "Integrierter INSPIRE ATOM Feed Viewer - GeoPortal.rlp Plugin";
                    $viewJson->view_type = "webpage_view";
                    $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
                    $indexViewArray++;
                    break;
                case "wmslayerdataurl":
                    $resourcesArray[$indexResourceArray]->name = "Download (INSPIRE)";//: ".$metadataObject->title;
                    $resourcesArray[$indexResourceArray]->id = $option->serviceUuid;
                    $resourcesArray[$indexResourceArray]->description = $metadataObject->title." - Download von verlinkten Daten über INSPIRE ATOM Feed";
                    $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/plugins/mb_downloadFeedClient.php?url=".urlencode($this->mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$metadataUuid."&type=SERVICE&generateFrom=dataurl&layerid=".$option->resourceId);
                    $resourcesArray[$indexResourceArray]->format = "Diverse"; 
                    //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
                    $indexResourceArray++;
                    //views to generate
                    //build whole json structure
                    $viewJson->resource_id = $option->serviceUuid;
                    //$viewJson->id = $option->serviceUuid."_view";
                    $viewJson->title = "Download (INSPIRE)";
                    $viewJson->description = "Integrierter INSPIRE ATOM Feed Viewer dataurl - GeoPortal.rlp Plugin";
                    $viewJson->view_type = "webpage_view";
                    $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
                    $indexViewArray++;
                    break;
                case "wfsrequest":
                    $resourcesArray[$indexResourceArray]->name = "Download (INSPIRE)";//: ".$metadataObject->title;
                    $resourcesArray[$indexResourceArray]->id = $option->serviceUuid;
                    $resourcesArray[$indexResourceArray]->description = $metadataObject->title." - Download von Vektordaten (wfs-basiert) über INSPIRE ATOM Feed";
                    $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/plugins/mb_downloadFeedClient.php?url=".urlencode($this->mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$metadataUuid."&type=SERVICE&generateFrom=wfs&wfsid=".$option->serviceId);
                    $resourcesArray[$indexResourceArray]->format = "Diverse";
                    //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
                    $indexResourceArray++;
                    //views to generate
                    //build whole json structure
                    $viewJson->resource_id = $option->serviceUuid;
                    //$viewJson->id = $option->serviceUuid."_view";
                    $viewJson->title = "INSPIRE ATOM Feed Viewer";
                    $viewJson->description = "Integrierter INSPIRE ATOM Feed Viewer - GeoPortal.rlp Plugin";
                    $viewJson->view_type = "webpage_view";
                    $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
                    $indexViewArray++;
		    //*******************************************************************************************************
		    // add new linked open data proxy uri if wfs is classified open data!
		    // 
		    $sql = "SELECT * FROM (SELECT wfs_id, wfs_version, fkey_termsofuse_id FROM wfs INNER JOIN wfs_termsofuse ON wfs_id = fkey_wfs_id AND wfs_id = $1) AS wfs_tou INNER JOIN termsofuse ON fkey_termsofuse_id = termsofuse_id WHERE isopen = 1";
    		    $v = array($option->serviceId);
    		    $t = array($i);
    		    $res = db_prep_query($sql, $v, $t);	
		    $numberOfServices = 0;
		    while($row = db_fetch_array($res)){
        		$wfsId = $row['wfs_id'];
			$wfsVersion = $row['wfs_version'];
        		$numberOfServices++;
    		    } 
    		    if ($numberOfServices == 1 && ($wfsVersion == "2.0.0" || $wfsVersion == "1.1.0")) {
			    $resourcesArray[$indexResourceArray]->name = "Linked Open Data API (OGC API Features)";//: ".$metadataObject->title;
		            $resourcesArray[$indexResourceArray]->id = $option->serviceUuid."_lod_wfs_api";
		            $resourcesArray[$indexResourceArray]->description = $metadataObject->title." - Zugriff auf Daten über LinkedOpenData REST API (OGC API Features)";
			    //ft id = $option->featureType[0] !
			    $sql = "SELECT featuretype_name from wfs_featuretype WHERE featuretype_id = $1";
    		    	    $v = array($option->featureType[0]);
    		   	    $t = array($i);
    		   	    $res = db_prep_query($sql, $v, $t);
			    while($row = db_fetch_array($res)){
        			$featureTypeName = $row['featuretype_name'];
			    }
		            //$resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/php/mod_linkedDataProxy.php?wfsid=".$option->serviceId."&collection=".$featureTypeName."&items=all";
			    $resourcesArray[$indexResourceArray]->url = "https://www.geoportal.rlp.de/spatial-objects/".$option->serviceId."/collections/".$featureTypeName;
			    //example: https://www.geoportal.rlp.de/mapbender/php/mod_linkedDataProxy.php?wfsid=480&collection=ms%3Aakademie&items=all
		            $resourcesArray[$indexResourceArray]->format = "REST";
		            //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
		            $indexResourceArray++;
		            //views to generate
		            //build whole json structure
		            $viewJson->resource_id = $option->serviceUuid."_lod_wfs_api";
		            //$viewJson->id = $option->serviceUuid."_view";
		            $viewJson->title = "Linked Open Data Zugriff (HTML)";
		            $viewJson->description = "Integrierter LinkedOpenData Client - GeoPortal.rlp OGC API Features Proxy";
		            $viewJson->view_type = "webpage_view";
		            $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
		            $indexViewArray++;
		    }	
		    
		    //*******************************************************************************************************
                    break;
                case "downloadlink":
                    $resourcesArray[$indexResourceArray]->name = "Download (INSPIRE)";//: ".$metadataObject->title;
                    $resourcesArray[$indexResourceArray]->id = $metadataObject->uuid."_downloadlink"; //TODO - no uuid for service known in this case
                    $resourcesArray[$indexResourceArray]->description = $metadataObject->title." - Download von verlinkten Daten über INSPIRE ATOM Feed";
                    $resourcesArray[$indexResourceArray]->url = $this->mapbenderUrl."/plugins/mb_downloadFeedClient.php?url=".urlencode($this->mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$metadataUuid."&type=SERVICE&generateFrom=metadata");
                    $resourcesArray[$indexResourceArray]->format = "Diverse";
                    //$resourcesArray[$indexResourceArray]->res_transparency_document_change_classification = "unaltered";
                    $indexResourceArray++;
                    //views to generate
                    //build whole json structure
                    $viewJson->resource_id = $metadataObject->uuid."_downloadlink";
                    //$viewJson->id = $metadataObject->uuid."_downloadlink"."_view";
                    $viewJson->title = "INSPIRE ATOM Feed Viewer";
                    $viewJson->description = "Integrierter INSPIRE ATOM Feed Viewer - GeoPortal.rlp Plugin";
                    $viewJson->view_type = "webpage_view";
                    $viewArray[$indexViewArray]['json'] = json_encode($viewJson);
                    $indexViewArray++;
                    break;
                }	
            }
        }
        //$e = new mb_exception($downloadOptionsJson);
        //TODO - for all coupled wfs interfaces
        foreach($featuretypeArray as $featuretypeId) {
        
        }
        $ckanPackage->resources = $resourcesArray;
        //arrays
        //$ckanPackage->tags = [];
        //$ckanPackage->govdata_categories[] = "geo";
	$ckanPackage->dcat_ap_eu_data_category[] = "GOVE";
        //and further categories and keywords
        $keywordIdArray = array();
        $topicIdArray = array();
        //TODO: check if it easier to pull aggregated information from search table! Keywords and categories!
        //get iso categories and all keywords from metadata and coupled layers / featuretypes
        //categories
        if (count($layerArray) > 0) {
            $sql = "SELECT fkey_md_topic_category_id FROM layer_md_topic_category WHERE (fkey_metadata_id = ".$metadataId." OR fkey_layer_id IN (".implode(",", $layerArray).") ) UNION SELECT fkey_md_topic_category_id FROM mb_metadata_md_topic_category WHERE fkey_metadata_id = ".$metadataId;
        } else {
            $sql = "SELECT fkey_md_topic_category_id FROM mb_metadata_md_topic_category WHERE fkey_metadata_id = ".$metadataId;
        }
        $res = db_query($sql);
        while($row = db_fetch_array($res)){
            $topicIdArray[] = $row['fkey_md_topic_category_id'];
        }

        //push categories into ckan package
        $numberOfCategories = 0;
        for ($i=0; $i < count($topicIdArray); $i++){
            if (array_key_exists((string)$topicIdArray[$i],$this->topicDataThemeCategoryMap)) {
            //check if categories should be exploded
            //check if one comma is in string
                if (strpos($this->topicDataThemeCategoryMap[$topicIdArray[$i]], ",") !== false) {
                    $newCategories = explode(",",$this->topicDataThemeCategoryMap[$topicIdArray[$i]]);
                } else {
                    //single category
                    $newCategories[0] = $this->topicDataThemeCategoryMap[$topicIdArray[$i]];
                }
                foreach ($newCategories as $cat) {
                    //explode categories if 
                    $categories[$numberOfCategories] = $cat;    
                    $numberOfCategories++;
                }
            } else {
                $e = new mb_notice("classes/class_syncCkan.php: Topic id not found in mapping hash!");
            }
        }
        if (count($categories) > 0) {
            $categories = array_unique($categories);
            for ($i=0; $i < count($categories); $i++){
                if ($categories[$i] !== "GOVE" && $categories[$i] !== null) {
                    //$ckanPackage->govdata_categories[] = $categories[$i];
		    $ckanPackage->dcat_ap_eu_data_category[] = $categories[$i];
                }
            }
        }
        //keywords / tags	
        if (count($layerArray) > 0) {
            $sql = "SELECT fkey_keyword_id FROM layer_keyword WHERE (fkey_layer_id IN (".implode(",", $layerArray).") ) UNION SELECT fkey_keyword_id FROM mb_metadata_keyword WHERE fkey_metadata_id = ".$metadataId;
        } else {
            $sql = "SELECT fkey_keyword_id FROM mb_metadata_keyword WHERE fkey_metadata_id = ".$metadataId;
        }
        $res = db_query($sql);
        while($row = db_fetch_array($res)){
            $keywordIdArray[] = $row['fkey_keyword_id'];
        }
        //make array unique
        $keywordIdArray = array_unique($keywordIdArray);
        $topicIdArray = array_unique($topicIdArray);
        //generate tags TODO - check for one single select above!
        if (count($keywordIdArray) > 0) {
            $keywordArray = array();
            $sql = "SELECT keyword FROM keyword WHERE keyword_id in (".implode(",", $keywordIdArray).")";
            $res = db_query($sql);
            while($row = db_fetch_array($res)) {
                //don't allow blanks in keywords!
                if ($row['keyword'] !== "" && strpos($row['keyword'], " ") === false) {
                    $keywordArray[] = $row['keyword'];
                }
            }
            for ($i=0; $i < count($keywordArray); $i++) {
                $ckanPackage->tags[$i]->name = $keywordArray[$i];
            }
        }
        if (count($layerArray) == 0 && count($featuretypeArray) == 0) {
            return false;
        }
        $returnArray = array();
        $returnArray['json'] = json_encode($ckanPackage);
        //$e = new mb_exception("json: ".$returnArray['json']);
        $returnArray['views'] = $viewArray;
        return $returnArray;
    }
     
    private function recreateResourceViews($ckan, $result, $resultCkanRepresentation) {
        $returnObject = new stdClass();
        //delete all existing resource_views an recreate them afterwards
        $numberOfResources = 0;
        foreach ($result->result->resources as $resource) {
            $returnObject->resources[$numberOfResources]->id = $resource->id;
            $result = $ckan->action_resource_view_list("{\"id\":\"".$resource->id."\"}");
            if ($result->success == true) {
                $returnObject->resources[$numberOfResources]->exists = true;
                //delete each resource view
                $numberOfViews = 0;
                $returnObject->resources[$numberOfResources]->has_view = false;
                foreach ($result->result as $resourceView) {
                    $returnObject->resources[$numberOfResources]->has_view = true;
                    $returnObject->resources[$numberOfResources]->resource_view[$numberOfViews]->id = $resourceView->id;
                    $result = $ckan->action_resource_view_delete("{\"id\":\"".$resourceView->id."\"}");
                    if ($result->success == true) {
                        $e = new mb_notice("classes/class_syncCkan.php: view ".$resourceView->id." successfully deleted!");
                        $returnObject->resources[$numberOfResources]->resource_view[$numberOfViews]->deleted = true;
                    } else {
                        $e = new mb_exception("classes/class_syncCkan.php: An error occured while deleting resource_view!");
                        $returnObject->resources[$numberOfResources]->resource_view[$numberOfViews]->deleted = false;
                    }
                    $numberOfViews++;
                    //$e = new mb_exception("found resource views: ".json_encode($result));
                }
            }
            $numberOfResources++;										
        }			
        //create new views	
        $numberOfViewsToCreate = 0;						
        foreach ($resultCkanRepresentation['views'] as $resourceView) {
            $returnObject->resources[$numberOfResources]->createView->resource_view[$numberOfViewsToCreate]->id = $numberOfViewsToCreate;
            //$e = new mb_exception("try to create view for resource: ".$resourceView['json']);
            $result = $ckan->action_resource_view_create($resourceView['json']);
            if ($result->success == true) {
                $e = new mb_notice("classes/class_syncCkan.php: Resource_view successfully created!");
                $returnObject->resources[$numberOfResources]->createView->resource_view[$numberOfViewsToCreate]->created = true;
                $returnObject->resources[$numberOfResources]->createView->resource_view[$numberOfViewsToCreate]->id = $result->result->id;
            } else {
                $e = new mb_exception("classes/class_syncCkan.php: An error occured while creating resource_view!");
                $returnObject->resources[$numberOfResources]->create->resource_view[$numberOfViewsToCreate]->created = false;
            }
            $numberOfViewsToCreate++;
        }
        return json_encode($returnObject);
    }
}
?>
