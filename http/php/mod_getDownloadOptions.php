<?php
//2012-11-20-http://localhost/mapbender_trunk/php/mod_getDownloadOptions.php?id=70e0c3e5-707c-f8e1-8037-7b38702176d9&output=xml
//http://localhost/mapbender_trunk/php/mod_getDownloadOptions.php?id=0a8b7dc8-b198-aac5-9713-79b623a6e651,0395ee4a-f27f-7e71-fc17-65498ffa991c

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
//Script for pulling all download options for one or more metadataset which are identified by their fileidentifier
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_Uuid.php");
global $configObject;
if (file_exists(dirname(__FILE__)."/../../conf/linkedDataProxy.json")) {
     $configObject = json_decode(file_get_contents("../../conf/linkedDataProxy.json"));
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
$e = new mb_notice("mod_showMetadata.php: language in SESSION: ".$sessionLang);
$e = new mb_notice("mod_showMetadata.php: new language: ".$languageCode);

$outputFormat = "json";

if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["languageCode"];
	if (!($testMatch == 'de' or $testMatch == 'fr' or $testMatch == 'en')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>languageCode</b> is not valid (de,fr,en).<br/>'; 
		die(); 		
 	}
	$languageCode = $testMatch;
	$e = new mb_notice("mod_showMetadata.php: languageCode from GET parameter: ".$languageCode);
	$testMatch = NULL;
}

$localeObj->setCurrentLocale($languageCode);

if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'json' or $testMatch == 'html')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>outputFormat</b> is not valid (json,html).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}

function checkUrlInDatalink($url, $datalinkIds) {
	$sql = "SELECT datalink_id FROM datalink WHERE datalink_id in (".explode(",",$datalinkIds).") AND datalink_url = ".urldecode($url);
	$res = db_query($sql);
	//$row = db_fetch_assoc($res)
	$e = new mb_exception("num rows: ".db_numrows($res));
	if (db_numrows($res) > 0) {
		return true;
	} else {
		return false;
	}
}

//make all parameters available as upper case
foreach($_REQUEST as $key => $val) {
	$_REQUEST[strtoupper($key)] = $val;
}
//validate request params
if (isset($_REQUEST['ID']) & $_REQUEST['ID'] != "") {
	//validate cs list of uuids or other identifiers - which?
	$testMatch = $_REQUEST["ID"];
	//$uuid = new Uuid($testMatch);
	//$isUuid = $uuid->isValid();
	$idList = explode(',',$_REQUEST['ID']);
	for ($i = 0; $i < count($idList); $i++) {
		$testMatch = $idList[$i];
		$uuid = new Uuid($testMatch);
		$isUuid = $uuid->isValid();
		if (!$isUuid) {
			//echo 'Id: <b>'.$testMatch.'</b> is not a valid uuid (12-4-4-4-8)!<br/>'; 
			echo 'Parameter <b>Id</b> is not a valid uuid (12-4-4-4-8) or a list of uuids!<br/>'; 
			die(); 		
		}
	}
	$testMatch = NULL;
}

if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') {
	$mapbenderPath = MAPBENDER_PATH."/";
} else {
	$mapbenderPath = "http://www.geoportal.rlp.de/mapbender/";
}
$mapbenderPathArray = parse_url($mapbenderPath);
$mapbenderServerUrl = $mapbenderPathArray['scheme']."://".$mapbenderPathArray['host'];

function getDownloadOptions($idList, $webPath=false) {
	global $configObject;
	//define query to pull all download options - actually only the inspire download services based on atom feeds
	$sql = "select service_id, service_uuid, resource_id, resource_name, resource_type, datalink, NULL as datalink_text, title,format from (
select service_id, resource_id, resource_name, service_uuid, resource_type, fkey_datalink_id as datalink, title, format from (select fkey_wms_id as service_id, layer_id as resource_id, layer_name as resource_name, 'layer' as resource_type, layer.uuid as service_uuid, metadata_relation.title, format from layer inner join (select metadata_id, title, format, uuid, fkey_layer_id from mb_metadata inner join ows_relation_metadata on ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) ";

	$sql .= "as metadata_relation on metadata_relation.fkey_layer_id = layer.layer_id where layer.inspire_download = 1 and metadata_relation.uuid = $1) as layer_metadata LEFT OUTER JOIN ows_relation_data ON layer_metadata.resource_id = ows_relation_data.fkey_layer_id
) as inspire_layer inner join wms on inspire_layer.service_id = wms.wms_id ";

	//for inspire atom feeds based on wfs ***************************************************************************
	$sql .= "union select fkey_wfs_id as service_id, service_uuid, featuretype_id as resource_id, featuretype_name as resource_name, 'wfs' as resource_type, NULL ";

	$sql .= "as datalink, NULL as datalink_text, title, 'GML' as format from (select wfs_featuretype.featuretype_id, wfs_featuretype.featuretype_name, wfs_featuretype.fkey_wfs_id, wfs.uuid as service_uuid, wfs_featuretype.inspire_download from wfs_featuretype inner join wfs on wfs_featuretype.fkey_wfs_id = wfs.wfs_id WHERE inspire_download = 1 ORDER BY featuretype_id) as featuretype_inspire inner join (select metadata_id, title, format, uuid, fkey_featuretype_id from mb_metadata inner join ows_relation_metadata on ";

	$sql .= "ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) as metadata_relation on metadata_relation.fkey_featuretype_id = featuretype_inspire.featuretype_id and metadata_relation.uuid = $1 ";
	//end for inspire atom feeds based on wfs ***************************************************************************

	//for inspire atom feeds based on links in metadata ***************************************************************************
	$sql .= "union select NULL as service_id, NULL as service_uuid, NULL as resource_id, NULL as resource_name, 'metadata' as resource_type, NULL ";

	$sql .= "as datalink, datalinks as datalink_text, title, format FROM mb_metadata WHERE mb_metadata.uuid = $1 and inspire_download = 1 ";
	//end for inspire atom feeds based on links in metadata ***************************************************************************

	//for wfs 2.0 linked data proxy **********************************************************************************
	$sql .= " union select fkey_wfs_id as service_id, service_uuid, featuretype_id as resource_id, featuretype_name as resource_name, 'rest' as resource_type, NULL as datalink, NULL as datalink_text, title, 'GeoJSON,GML,HTML' as format from ";

	$sql .= " (select wfs_featuretype.featuretype_id, wfs_featuretype.featuretype_name, wfs_featuretype.fkey_wfs_id, open_wfs.uuid as service_uuid, wfs_featuretype.inspire_download from wfs_featuretype inner join ";

	$sql .= " (SELECT * FROM (SELECT wfs_id, wfs_version, uuid, wfs_termsofuse.fkey_termsofuse_id FROM wfs INNER JOIN wfs_termsofuse ON wfs_id = fkey_wfs_id) AS wfs_tou INNER JOIN termsofuse ON fkey_termsofuse_id = termsofuse_id WHERE isopen = 1) as open_wfs ";

	$sql .= " on wfs_featuretype.fkey_wfs_id = open_wfs.wfs_id WHERE (open_wfs.wfs_version = '1.1.0' or open_wfs.wfs_version = '2.0.0' OR open_wfs.wfs_version = '2.0.2') AND wfs_featuretype.featuretype_searchable = 1 ORDER BY featuretype_id) as featuretype_wfs2 inner join ";

	$sql .= " (select metadata_id, title, format, uuid, fkey_featuretype_id from mb_metadata inner join ows_relation_metadata on ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) as metadata_relation on metadata_relation.fkey_featuretype_id = featuretype_wfs2.featuretype_id and metadata_relation.uuid = $1";
	//end for wfs 2.0 linked data proxy **********************************************************************************
	
	//for direct wfs interfaces
	$sqlWfs = " union select fkey_wfs_id as service_id, service_uuid, featuretype_id as resource_id, featuretype_name as resource_name, 'directwfs' as resource_type, NULL as datalink, NULL as datalink_text, title, 'GeoJSON,GML,HTML' as format from ";
	
	$sqlWfs .= " (select wfs_featuretype.featuretype_id, wfs_featuretype.featuretype_name, wfs_featuretype.fkey_wfs_id, open_wfs.uuid as service_uuid, wfs_featuretype.inspire_download from wfs_featuretype inner join ";
	
	$sqlWfs .= " (SELECT wfs_id, wfs_version, uuid FROM wfs) as open_wfs ";
	
	$sqlWfs .= " on wfs_featuretype.fkey_wfs_id = open_wfs.wfs_id where wfs_featuretype.featuretype_searchable = 1 ORDER BY featuretype_id) as featuretype_wfs2 inner join ";
	
	$sqlWfs .= " (select metadata_id, title, format, uuid, fkey_featuretype_id from mb_metadata inner join ows_relation_metadata on ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) as metadata_relation on metadata_relation.fkey_featuretype_id = featuretype_wfs2.featuretype_id and metadata_relation.uuid = $1";
	$e = new mb_exception($sqlWfs);
	//end for wfs interfaces
	$sql .= $sqlWfs;

	/*
	$sql = "select service_id, resource_id, resource_type, fkey_datalink_id as datalink from (select fkey_wms_id as service_id, layer_id as resource_id, 'layer' as resource_type from layer inner join (select metadata_id, uuid, fkey_layer_id from mb_metadata inner join ows_relation_metadata on ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) ";

	$sql .= "as metadata_relation on metadata_relation.fkey_layer_id = layer.layer_id where layer.inspire_download = 1 and metadata_relation.uuid = $1) as layer_metadata LEFT OUTER JOIN ows_relation_data ON layer_metadata.resource_id = ows_relation_data.fkey_layer_id union select fkey_wfs_id as service_id, featuretype_id as resource_id, 'wfs' as resource_type, NULL ";

	$sql .= "as datalink from (select wfs_featuretype.featuretype_id ,wfs_featuretype.fkey_wfs_id,  wfs_featuretype.inspire_download from wfs_featuretype WHERE inspire_download = 1 ORDER BY featuretype_id) as featuretype_inspire inner join (select metadata_id, uuid, fkey_featuretype_id from mb_metadata inner join ows_relation_metadata on ";

	$sql .= "ows_relation_metadata.fkey_metadata_id = mb_metadata.metadata_id) as metadata_relation on metadata_relation.fkey_featuretype_id = featuretype_inspire.featuretype_id and metadata_relation.uuid = $1;";*/

	//initialize array for result
	//$e = new mb_exception($idList);
	//$downloadOptions = new stdClass();
	for ($i = 0; $i < count($idList); $i++) {
		$v = array($idList[$i]);
		$t = array('s');
		$res = db_prep_query($sql,$v,$t);
		//problem, $res don't give back false if it was not successful!
		//push rows into associative array
		$j = 0;

/*while ($row = db_fetch_assoc($res)) {
echo "j: ".$j."<br>";
echo $row['service_id']." - ".$row['resource_type']."<br>";
$j++;
}
die();*/

		while ($row = db_fetch_assoc($res)) {
			//echo "j: ".$j."<br>";
			switch ($row['resource_type']) {		
				case "wfs":
					$serviceIdIndex = false;
					//check existing options - maybe some option for a wfs already exists 
					for ($k = 0; $k < count($downloadOptions->{$idList[$i]}->option); $k++) {
						//echo "k: ".$k."<br>";
						//echo "service_id: ".$row['service_id']." - searched Id: ".$downloadOptions->{$idList[$i]}->option[$k]->serviceId."<br>";
						if ($row['service_id'] == $downloadOptions->{$idList[$i]}->option[$k]->serviceId && $downloadOptions->{$idList[$i]}->option[$k]->serviceSubType != "REST") {
							//service_id found at index $k
							$serviceIdIndex = $k;
							//echo "Service already found on index: ".$serviceIdIndex."<br>";
						} 
					}
					if ($serviceIdIndex !== false) {
						//echo "Add featuretype to given service: ".$serviceIdIndex."<br>";
						//old wfs has been found
						//get count of current fts
						$m = count($downloadOptions->{$idList[$i]}->option[$serviceIdIndex]->featureType);
						//echo "m: ".$m."<br>";
						$downloadOptions->{$idList[$i]}->option[$serviceIdIndex]->featureType[$m] = $row['resource_id'];
$downloadOptions->{$idList[$i]}->option[$serviceIdIndex]->featureType[$m]->name = $row['resource_name'];
					} else {
						$downloadOptions->{$idList[$i]}->option[$j]->type = "wfsrequest";
						$downloadOptions->{$idList[$i]}->option[$j]->serviceId = $row['service_id'];
						$downloadOptions->{$idList[$i]}->option[$j]->serviceUuid = $row['service_uuid'];
						$downloadOptions->{$idList[$i]}->option[$j]->featureType[0] = $row['resource_id'];
$downloadOptions->{$idList[$i]}->option[$j]->featureType[0]->name = $row['resource_name'];

						$downloadOptions->{$idList[$i]}->option[$j]->format = $row['format'];
						//new 2019/07
						$downloadOptions->{$idList[$i]}->option[$j]->serviceType = "download";
						$downloadOptions->{$idList[$i]}->option[$j]->serviceSubType = "ATOM";
						$downloadOptions->{$idList[$i]}->option[$j]->serviceTitle = _mb('INSPIRE Download Service (predefined ATOM) for dataset').": ".$row['title']." - "._mb("based on WFS datasource");
						$downloadOptions->{$idList[$i]}->option[$j]->mdLink = $webPath."php/mod_inspireAtomFeedISOMetadata.php?outputFormat=iso19139&generateFrom=wfs&wfsid=".$row['service_id']."&id=".$idList[$i];
						$downloadOptions->{$idList[$i]}->option[$j]->htmlLink = $webPath."php/mod_exportIso19139.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->mdLink);
						$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $webPath."php/mod_inspireDownloadFeed.php?id=".$idList[$i]."&type=SERVICE&generateFrom=wfs&wfsid=".$row['service_id'];
						$downloadOptions->{$idList[$i]}->option[$j]->accessClient = $webPath."plugins/mb_downloadFeedClient.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->accessUrl);
					}
					$downloadOptions->{$idList[$i]}->title = $row['title'];
					$downloadOptions->{$idList[$i]}->uuid = $idList[$i];
				break;
				case "layer":
					if (!isset($row['datalink'] ) || $row['datalink'] == '') {
						$downloadOptions->{$idList[$i]}->option[$j]->type = "wmslayergetmap";
						$row['format'] = 'GeoTIFF';
					} else {
						$downloadOptions->{$idList[$i]}->option[$j]->type = "wmslayerdataurl";
						//add to array with datalink (ids)
						//$arrayDataLinks[] = $row['datalink'];
					}
					$downloadOptions->{$idList[$i]}->option[$j]->serviceId = $row['service_id'];
					$downloadOptions->{$idList[$i]}->option[$j]->serviceUuid = $row['service_uuid'];//This is a layer uuid - not a service uuid!!!!
					$downloadOptions->{$idList[$i]}->option[$j]->resourceId = $row['resource_id'];
$downloadOptions->{$idList[$i]}->option[$j]->resourceName = $row['resource_name'];
					$downloadOptions->{$idList[$i]}->option[$j]->format = $row['format'];
					$downloadOptions->{$idList[$i]}->option[$j]->dataLink = $row['datalink'];
					//new 2019/07
					$downloadOptions->{$idList[$i]}->option[$j]->serviceType = "download";
					$downloadOptions->{$idList[$i]}->option[$j]->serviceSubType = "ATOM";
					$downloadOptions->{$idList[$i]}->option[$j]->serviceTitle = _mb('INSPIRE Download Service (predefined ATOM) for dataset').": ".$row['title']." - "._mb("based on WMS datasource");
					$downloadOptions->{$idList[$i]}->option[$j]->mdLink = $webPath."php/mod_inspireAtomFeedISOMetadata.php?outputFormat=iso19139&generateFrom=wmslayer&layerid=".$row['resource_id']."&id=".$idList[$i];
					$downloadOptions->{$idList[$i]}->option[$j]->htmlLink = $webPath."php/mod_exportIso19139.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->mdLink);
					$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $webPath."php/mod_inspireDownloadFeed.php?id=".$idList[$i]."&type=SERVICE&generateFrom=wmslayer&layerid=".$row['resource_id'];
					$downloadOptions->{$idList[$i]}->option[$j]->accessClient = $webPath."plugins/mb_downloadFeedClient.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->accessUrl);
					$downloadOptions->{$idList[$i]}->title = $row['title'];
					$downloadOptions->{$idList[$i]}->uuid = $idList[$i];
				break;
				case "rest":
					$downloadOptions->{$idList[$i]}->option[$j]->type = "ogcapifeatures";
				
					$downloadOptions->{$idList[$i]}->option[$j]->serviceId = $row['service_id'];
					$downloadOptions->{$idList[$i]}->option[$j]->serviceUuid = $row['service_uuid'];
					$downloadOptions->{$idList[$i]}->option[$j]->resourceId = $row['resource_id'];
$downloadOptions->{$idList[$i]}->option[$j]->resourceName = $row['resource_name'];
					$downloadOptions->{$idList[$i]}->option[$j]->format = $row['format'];
					//$downloadOptions->{$idList[$i]}->option[$j]->dataLink = $row['datalink'];
					//new 2019/07
					$downloadOptions->{$idList[$i]}->option[$j]->serviceType = "download";
					$downloadOptions->{$idList[$i]}->option[$j]->serviceSubType = "REST";
					$downloadOptions->{$idList[$i]}->option[$j]->serviceTitle = _mb('OGC API - Features (Draft)').": ".$row['title']." - "._mb("based on WFS 2.0.0+ datasource");
					//service metadata:
					$downloadOptions->{$idList[$i]}->option[$j]->mdLink = $webPath."php/mod_featuretypeISOMetadata.php?SERVICETYPE=ogcapifeatures&SERVICE=WFS&outputFormat=iso19139&Id=".$row['resource_id'];
					$downloadOptions->{$idList[$i]}->option[$j]->htmlLink = $webPath."php/mod_exportIso19139.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->mdLink);

					if (isset($configObject) && isset($configObject->behind_rewrite) && $configObject->behind_rewrite == true) {
						if (isset($configObject) && isset($configObject->datasource_url) && $configObject->datasource_url != "") {
							$downloadOptions->{$idList[$i]}->option[$j]->accessClient = $configObject->datasource_url.$configObject->rewrite_path."/".$row['service_id']."/collections/".$row['resource_name'];//."/items?&f=html";
							$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $configObject->datasource_url.$configObject->rewrite_path."/".$row['service_id']."/api";//."/items?&f=html";
						} else {
							$downloadOptions->{$idList[$i]}->option[$j]->accessClient = "http://".$_SERVER['HTTP_HOST']."/".$configObject->rewrite_path."/".$row['service_id']."/collections/".$row['resource_name'];//."/items?&f=html";
							$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = "http://".$_SERVER['HTTP_HOST']."/".$configObject->rewrite_path."/".$row['service_id']."/api";//."/items?&f=html";
						}
                                        } else {
						$downloadOptions->{$idList[$i]}->option[$j]->accessClient = $webPath."php/mod_linkedDataProxy.php?wfsid=".$row['service_id']."&collection=".$row['resource_name'];
						$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $webPath."php/mod_linkedDataProxy.php?wfsid=".$row['service_id']."&collections=api";
					}
					//$downloadOptions->{$idList[$i]}->option[$j]->accessClient = "https://www....";
					$downloadOptions->{$idList[$i]}->title = $row['title'];
					$downloadOptions->{$idList[$i]}->uuid = $idList[$i];
				break;
				case "metadata":
					if (isset($row['datalink_text'] ) || $row['datalink_text'] != '') {
						$downloadLinks = json_decode($row['datalink_text']);
						$downloadOptions->{$idList[$i]}->option[$j]->type = "downloadlink";
						//parse json and add some more info?
						//$downloadLinks = json_decode($row['datalink_text']);
						foreach ($downloadLinks->downloadLinks as $downloadLink) {
							$downloadOptions->{$idList[$i]}->option[$j]->link = $downloadLink->{"0"};
							//check if 
							$downloadOptions->{$idList[$i]}->option[$j]->format = $row['format'];
							$downloadOptions->{$idList[$i]}->option[$j]->serviceType = "download";
							$downloadOptions->{$idList[$i]}->option[$j]->serviceSubType = "ATOM";
							$downloadOptions->{$idList[$i]}->option[$j]->serviceTitle = _mb('INSPIRE Download Service (predefined ATOM) for dataset').": ".$row['title']." - "._mb("based on links from metadata");
							$downloadOptions->{$idList[$i]}->option[$j]->mdLink = $webPath."php/mod_inspireAtomFeedISOMetadata.php?outputFormat=iso19139&generateFrom=metadata&id=".$idList[$i];
							$downloadOptions->{$idList[$i]}->option[$j]->htmlLink = $webPath."php/mod_exportIso19139.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->mdLink);
							$downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $webPath."php/mod_inspireDownloadFeed.php?id=".$idList[$i]."&type=SERVICE&generateFrom=metadata";
							$downloadOptions->{$idList[$i]}->option[$j]->accessClient = $webPath."plugins/mb_downloadFeedClient.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->accessUrl);
						}
					}
					$downloadOptions->{$idList[$i]}->title = $row['title'];
					$downloadOptions->{$idList[$i]}->uuid = $idList[$i];
					break;
				case "directwfs":
				    $downloadOptions->{$idList[$i]}->option[$j]->type = "directwfs";
				    
				    $downloadOptions->{$idList[$i]}->option[$j]->serviceId = $row['service_id'];
				    $downloadOptions->{$idList[$i]}->option[$j]->serviceUuid = $row['service_uuid'];
				    $downloadOptions->{$idList[$i]}->option[$j]->resourceId = $row['resource_id'];
				    $downloadOptions->{$idList[$i]}->option[$j]->resourceName = $row['resource_name'];
				    $downloadOptions->{$idList[$i]}->option[$j]->format = $row['format'];
				    //$downloadOptions->{$idList[$i]}->option[$j]->dataLink = $row['datalink'];
				    //new 2021/10
				    $downloadOptions->{$idList[$i]}->option[$j]->serviceType = "download";
				    $downloadOptions->{$idList[$i]}->option[$j]->serviceSubType = "DIRECTWFS";
				    $downloadOptions->{$idList[$i]}->option[$j]->serviceTitle = _mb("OGC WFS Interface")." - "._mb("Featuretype").": ".$row['resource_name'];
				    //http://localhost/mapbender/php/mod_featuretypeISOMetadata.php?SERVICE=WFS&outputFormat=iso19139&Id=24
				    //service metadata:
				    $downloadOptions->{$idList[$i]}->option[$j]->mdLink = $webPath."php/mod_featuretypeISOMetadata.php?SERVICE=WFS&outputFormat=iso19139&Id=".$row['resource_id'];
				    $downloadOptions->{$idList[$i]}->option[$j]->htmlLink = $webPath."php/mod_exportIso19139.php?url=".urlencode($downloadOptions->{$idList[$i]}->option[$j]->mdLink);
				    //FEATURETYPE_ID=32&REQUEST=GetCapabilities&SERVICE=WFS&INSPIRE=1
				    $downloadOptions->{$idList[$i]}->option[$j]->accessUrl = $webPath."php/wfs.php?FEATURETYPE_ID=".$row['resource_id']."&REQUEST=GetCapabilities&SERVICE=WFS&INSPIRE=1";
				    $downloadOptions->{$idList[$i]}->option[$j]->accessClient = $webPath."php/wfs.php?FEATURETYPE_ID=".$row['resource_id']."&REQUEST=GetCapabilities&SERVICE=WFS&INSPIRE=1";
				    $downloadOptions->{$idList[$i]}->title = $row['title'];
				    $downloadOptions->{$idList[$i]}->uuid = $idList[$i];
				    break;	
			}
			$j++;
			array_splice($downloadOptions->{$idList[$i]}->option, 0, 0);
		}
		//delete double entries - maybe url is given from dataurl - use this 
		//get all dataurlids
		//foreach($downloadOptions->{$idList[$i]}->option as $option) {
			//$option->dataLink;
		//}
	}
	//$e = new mb_exception(json_encode($downloadOptions));
	//add further option from metadata itself - if 
	$result = json_encode($downloadOptions);
	return $result;
}

$downloadOptions = getDownloadOptions($idList, $mapbenderPath);

if ($downloadOptions != "null" && $outputFormat == "json") {
	header('Content-Type: application/json; charset='.CHARSET);
	echo $downloadOptions;
}
if ($downloadOptions != "null" && $outputFormat == "html") {
	$options = json_decode($downloadOptions);
	$header = '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$languageCode.'">';
	$header .= '<body>';
	$header .= '<head>' . 
		'<title>'._mb('Download options for dataset(s)').'</title>' . 
		'<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0;">'.
		'<meta name="description" content="'._mb('Download options for datasets').'" xml:lang="'.$languageCode.'" />'.
		'<meta name="keywords" content="'._mb('spatial dataset').'" xml:lang="'.$languageCode.'" />'	.	
		'<meta http-equiv="cache-control" content="no-cache">'.
		'<meta http-equiv="pragma" content="no-cache">'.
		'<meta http-equiv="expires" content="0">'.
		'<meta http-equiv="content-language" content="'.$languageCode.'" />'.
		'<meta http-equiv="content-style-type" content="text/css" />'.
		'<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">' . 	
		'</head>';
	$header .= '<link type="text/css" href="../css/metadata.css" rel="Stylesheet" />';
	$header .= '<link type="text/css" href="../extensions/jquery-ui-1.8.1.custom/css/custom-theme/jquery-ui-1.8.5.custom.css" rel="Stylesheet" />';	
	$header .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-1.4.2.min.js"></script>';
	$header .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-ui-1.8.1.custom.min.js"></script>';
	$header .= '<style type="text/css">a{white-space:normal;}</style>';
//some js for dialog
	echo $header;
	if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') { 
		$mapbenderUrl = MAPBENDER_PATH;
	} else {
		$mapbenderUrl = "http://www.geoportal.rlp.de/mapbender";
	}
	$script .= '<script type="text/javascript">';
	$script .= '$(function() {';
	$script .= '	$("#tabs").tabs();';
	$script .= '});';
	$script .= '</script>';
	echo $script;
	$metadataList = _mb("Used dataset(s)").":<br>";

	//generate one tab for each dataset
	//independently define the headers of the parts
	$metadataList .= '<div class="demo">';
	$metadataList .= '<div id="tabs">';
	$metadataList .= '<ul>';
	$iTabs = 1;
	foreach ($idList as $currentUuid){
		$metadataList .= 	'<li><a href="#tabs-'.$iTabs.'">'.$options->{$currentUuid}->title.'<br>'.$currentUuid.'</a></li>';
		$iTabs++;
	}
	$iTabs = 1;
	$metadataList.= '</ul>';
	foreach ($idList as $currentUuid){
		//$metadataList .= "<a href='../php/mod_iso19139ToHtml.php?url=".urlencode($mapbenderUrl."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$currentUuid)."'>".$options->{$currentUuid}->title."</a> <a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$currentUuid."&validate=true'>".'validate'."</a>";
		$metadataList .= '<div id="tabs-'.$iTabs.'">';
		$metadataList .= "<a href='../php/mod_iso19139ToHtml.php?url=".urlencode($mapbenderUrl."/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=".$currentUuid)."'>"._mb('Metadata')."</a>";
		//echo $options->{$currentUuid}->title;
		$metadataList .= "<br>";
		if ($downloadOptions != null) {
			$iOptions = 1;
			foreach ($options->{$currentUuid}->option as $option) {
				switch ($option->type) {
					case "wmslayergetmap":
						$metadataList .= $iOptions.". "._mb('Download raster data from INSPIRE Download Service').":   <a href='../plugins/mb_downloadFeedClient.php?url=".urlencode($mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$currentUuid."&type=SERVICE&generateFrom=wmslayer&layerid=".$option->resourceId)."'><img src='../img/osgeo_graphics/geosilk/raster_download.png' title='"._mb('Download raster data from INSPIRE Download Service')."'/></a>";
						break;
					case "wmslayerdataurl":
						$metadataList .=  $iOptions.". "._mb('Download linked data from INSPIRE Download Service').":   <a href='../plugins/mb_downloadFeedClient.php?url=".urlencode($mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$currentUuid."&type=SERVICE&generateFrom=dataurl&layerid=".$option->resourceId)."'><img src='../img/osgeo_graphics/geosilk/link_download.png' title='"._mb('Download linked data from INSPIRE Download Service')."'/></a>";
						break;
					case "wfsrequest":
						$metadataList .=  $iOptions.". "._mb('Download GML data from INSPIRE Download Service').":   <a href='../plugins/mb_downloadFeedClient.php?url=".urlencode($mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$currentUuid."&type=SERVICE&generateFrom=wfs&wfsid=".$option->serviceId)."'><img src='../img/osgeo_graphics/geosilk/vector_download.png' title='"._mb('Download GML data from INSPIRE Download Service')."'/></a>";
						break;
					case "downloadlink":
						$metadataList .=  $iOptions.". "._mb('Download linked data from INSPIRE Download Service').":   <a href='../plugins/mb_downloadFeedClient.php?url=".urlencode($mapbenderUrl."/php/mod_inspireDownloadFeed.php?id=".$currentUuid."&type=SERVICE&generateFrom=metadata")."'><img src='../img/osgeo_graphics/geosilk/link_download.png' title='"._mb('Download linked data from INSPIRE Download Service')."'/></a>";
						break;
					case "ogcapifeatures":
						$metadataList .=  $iOptions.". "._mb('OGC API Features')." (".$option->resourceName."):   <a href='".$option->accessClient."'><img src='../img/osgeo_graphics/geosilk/link_download.png' title='"._mb('Linked Open Data via OGC REST API')."'/></a>";
						break;
				}
				$metadataList .= "<br>";	
				$iOptions++;
			}
			
		}	
		$iTabs++;
		$metadataList .= '</div>';		
	}
	$metadataList.= '</div>';
	$metadataList .= '</div>';
	echo $metadataList;
	echo "</Body></HTML>";
}
?>
