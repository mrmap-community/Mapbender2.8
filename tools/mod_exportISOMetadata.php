<?php
#http://www.geoportal.rlp.de/mapbender/php/mod_exportISOMetadata.php?
# $Id: mod_exportISOMetadata.php 235
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

//altered to tools folder - 2019-11-06
//require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
//require_once(dirname(__FILE__) . "/../classes/class_connector.php");
require_once(dirname(__FILE__) . "/../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../http/classes/class_connector.php");

if (file_exists(dirname(__FILE__)."/../conf/exportIsoMetadata.json")) {
     $configObject = json_decode(file_get_contents("../conf/exportIsoMetadata.json"));
}

//alter METADATA_DIR from mapbender.conf to right relative path
$metadataDir = str_replace("../../", "../", METADATA_DIR);

$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);

//define the view or table where to read out the layer ids for which metadatafiles should be generated
//$wmsView = "search_wms_view";
$wmsView = "wms_search_table";

if (isset($configObject->metadataGenerationUri) && $configObject->metadataGenerationUri != "") {
    $baseUri = $configObject->metadataGenerationUri;
} else {
    $baseUri = "http://localhost/mapbender";
}

$sql = "update mb_metadata set export2csw = false where position('GetRecordById' in data) <> 0 and position('GetRecordById' in data) is not null";
if (isset($configObject->excludeFromCswExportRule) && count($configObject->excludeFromCswExportRule) > 0) {
    $sql .= " AND metadata_id NOT IN (".implode(",", $configObject->excludeFromCswExportRule).")";
}
$res = db_query($sql);

$sql = "SELECT layer_id ";
$sql .= "FROM ".$wmsView." WHERE export2csw = true";
//$sql .= "FROM layer WHERE layer_id IN (20203,20202)";
$v = array();
$t = array();
$res = db_prep_query($sql,$v,$t);

$generatorScript = '/php/mod_layerISOMetadata.php?';
$generatorScriptMetadata = '/php/mod_dataISOMetadata.php?';
$generatorScriptDlsOption = '/php/mod_getDownloadOptions.php?';
$generatorScriptDls = '/php/mod_inspireAtomFeedISOMetadata.php?';
$generatorScriptDlsWfs2 = '/php/mod_featuretypeISOMetadata.php?'; //SERVICE=WFS&outputFormat=iso19139&Id=2699'

$generatorBaseUrl = $baseUri.$generatorScript;
$generatorBaseUrlMetadata = $baseUri.$generatorScriptMetadata;
$generatorBaseUrlDlsOption = $baseUri.$generatorScriptDlsOption;
$generatorBaseUrlDls = $baseUri.$generatorScriptDls;
$generatorBaseUrlDlsWfs2 = $baseUri.$generatorScriptDlsWfs2;

$countLayer = 0;
$countMetadataURL = 0;
$countDls = 0;
$countApplications = 0;
$countRestInterfaces = 0;

logMessages(date('Y-m-d - H:i:s', time()));
//remove files from $metadataDir!
if ($handle = opendir($metadataDir)) {
	logMessages( "Delete files from temporary metadata folder:");
    	// This is the correct way to loop over the directory. 
    	while (false !== ($file = readdir($handle))) {
		//check if file name begin with "mapbender";
		$pos = strpos($file, "mapbender");
		if ($pos !== false) {
			//delete file with unlink
			unlink($metadataDir."/".$file); 
			logMessages($metadataDir."/".$file." has been deleted!");
		} else {
        		logMessages("$file will not be deleted!");
		}
    	}
   	closedir($handle);
}



logMessages("Begin to create new metadata: ".date('Y-m-d - H:i:s', time()));
while($row = db_fetch_array($res)){
	$generatorUrl = $generatorBaseUrl."SERVICE=WMS&outputFormat=iso19139&id=".$row['layer_id'];
	logMessages("URL requested : ".$generatorUrl);
	$generatorInterfaceObject = new connector($generatorUrl);
	$ISOFile = $generatorInterfaceObject->file;
	$layerId = $row['layer_id'];
	logMessages("File for layer ".$layerId." will be generated");
	//generate temporary files under tmp
	if($h = fopen($metadataDir."/mapbenderServiceMetadata_".$layerId."_iso19139.xml","w")){
		if(!fwrite($h,$ISOFile)){
			$e = new mb_exception("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/mapbenderLayerMetadata_".$row['layer_id']."_iso19139.xml");
		}
	logMessages("Service metadata file for layer ".$layerId." written to ".$metadataDir);
	fclose($h);
	}

	//get all connected metadata for this layer and save it too	
	$sql = <<<SQL

SELECT metadata_id, uuid, link, linktype, md_format, origin, export2csw FROM mb_metadata 
INNER JOIN (SELECT * from ows_relation_metadata 
WHERE fkey_layer_id = $layerId ) as relation ON 
mb_metadata.metadata_id = relation.fkey_metadata_id

SQL;
	
	$res_metadata = db_query($sql);
	while ($row_metadata = db_fetch_array($res_metadata)) {
		//export only metadata which should be exported to the external csw interface
		if ($row_metadata["export2csw"] == "t") {
			$generatorUrlMetadata = $generatorBaseUrlMetadata."outputFormat=iso19139&id=".$row_metadata['uuid'];
			logMessages("URL requested: ".$generatorUrlMetadata);
			$generatorInterfaceObject = new connector($generatorUrlMetadata);
			$ISOFile = $generatorInterfaceObject->file;
			logMessages("Metadata uuid: ".$row_metadata['uuid']);
			//generate temporary files under tmp
			if($h = fopen($metadataDir."/mapbenderDataMetadata_".$layerId."_".$row_metadata['uuid']."_iso19139.xml","w")){
				if(!fwrite($h,$ISOFile)){
					$e = new mb_exception("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/metadata/mapbenderMetadata_".$layerId."_".$row_metadata['uuid']."_iso19139.xml");
				}
				logMessages("Data metadate file for layer ".$row['layer_id']." and metadata ".$row_metadata['uuid']." written to ".$metadataDir);
				fclose($h);
				$countMetadataURL++;	
			}
		}
		//generate the downloadservice metadata files thru walking over the coupled dls for this metadata uuid!
		//e.g. http://www.geoportal.rlp.de/mapbender/php/mod_inspireAtomFeedISOMetadata.php?Id=1487b57f-f087-d84a-3de3-e341312ecd6e&outputFormat=iso19139&generateFrom=wfs&wfsid=271
		//get download options for specific metadata
		
		$downloadOptionsConnector = new connector($generatorBaseUrlDlsOption."id=".$row_metadata["uuid"]);
		$downloadOptions = json_decode($downloadOptionsConnector->file);
		if ($downloadOptions != null) {
			logMessages("Coupled DLS:");
			foreach ($downloadOptions->{$row_metadata["uuid"]}->option as $option) {
				unset($dlsOption);
				switch ($option->type) {
					case "wmslayergetmap":
						$generatorDlsUrl =  $generatorBaseUrlDls."Id=".$row_metadata["uuid"]."&outputFormat=iso19139&generateFrom=wmslayer&layerid=".$option->resourceId;
						$dlsOption = $option->type;
						$dlsOptionId = $option->resourceId;
						break;
					case "wmslayerdataurl":
						$generatorDlsUrl = $generatorBaseUrlDls."Id=".$row_metadata["uuid"]."&outputFormat=iso19139&generateFrom=dataurl&layerid=".$option->resourceId;
						$dlsOption = $option->type;						
						$dlsOptionId = $option->resourceId;
						break;
					case "wfsrequest":
						$generatorDlsUrl = $generatorBaseUrlDls."Id=".$row_metadata["uuid"]."&outputFormat=iso19139&generateFrom=wfs&wfsid=".$option->serviceId;
						$dlsOption = $option->type;						
						$dlsOptionId = $option->serviceId;
						break;
					case "downloadlink":
						$generatorDlsUrl = $generatorBaseUrlDls."Id=".$row_metadata["uuid"]."&outputFormat=iso19139&generateFrom=metadata";
						$dlsOption = $option->type;
						//generate downloadservice uuid from metadata_uuid and hash of link 		
						$mdPart = explode('-',$row_metadata["uuid"]);
						$linkPart = md5($option->link);
						$dlsOptionId = $mdPart[0]."-".$mdPart[1]."-".$mdPart[2]."-".substr($linkPart, -12, 4)."-".substr($linkPart, -8, 8);
						break;
				}
				logMessages($generatorDlsUrl);
				//load the xml and store it to filesystem
				if (isset($dlsOption) && $dlsOption != '') {
					$generatorInterfaceObject = new connector($generatorDlsUrl);
					$ISOFile = $generatorInterfaceObject->file;
					if($h = fopen($metadataDir."/mapbenderDlsMetadata_".$row_metadata["uuid"]."_".$dlsOption."_".$dlsOptionId."_iso19139.xml","w")){
						if(!fwrite($h,$ISOFile)){
							$e = new mb_exception("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/mapbenderDlsMetadata_".$row_metadata["uuid"]."_".$dlsOption."_".$dlsOptionId."_iso19139.xml");
						}
						fclose($h);
						$countDls++;	
					}
				}
				//test if download option via wfsrequest is based on WFS > 2.0 - first also generate metadata for wfs 1.1.0
				if ($option->type == "wfsrequest") {
					//select featuretypeid, wfs_version, ...
					$sqlWfs2 = <<<SQL

					SELECT wfs_version, fkey_wfs_id, featuretype_id FROM (SELECT fkey_wfs_id, featuretype_id FROM wfs_featuretype WHERE featuretype_id 
					IN (select fkey_featuretype_id from mb_metadata LEFT JOIN ows_relation_metadata ON metadata_id = fkey_metadata_id where 
					 fkey_featuretype_id IS NOT NULL AND metadata_id = $1)) ft LEFT JOIN wfs ON 
					ft.fkey_wfs_id = wfs_id WHERE (wfs_version = '2.0.0' OR wfs_version = '2.0.2');

SQL;
					$v = array($row_metadata["metadata_id"]);
					$t = array('i');
					$res_wfs2 = db_prep_query($sqlWfs2,$v,$t);
					while ($row_wfs2 = db_fetch_array($res_wfs2)) {
						$generatorDlsUrlWfs2 = $generatorBaseUrlDlsWfs2."SERVICE=WFS&outputFormat=iso19139&Id=".$row_wfs2['featuretype_id'];
						$generatorInterfaceObject = new connector($generatorDlsUrlWfs2);
						$ISOFile = $generatorInterfaceObject->file;
						if($h = fopen($metadataDir."/mapbenderDlsWfs2Metadata_".$row_metadata["uuid"]."_".$row_wfs2['featuretype_id']."_iso19139.xml","w")){
							if(!fwrite($h,$ISOFile)){
								$e = new mb_exception("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/mapbenderDlsWfs2Metadata_".$row_metadata["uuid"]."_".$row_wfs2['featuretype_id']."_iso19139.xml");
							}
							fclose($h);
							$countDls++;	
						}
					}
									
				}
			}	
		}
		
	}
	$countLayer++;
}
//export application metadata
$sql_app = "select uuid, export2csw from mb_metadata where type = 'application' and searchable = true and export2csw = true;";
$v = array();
$t = array();
$res_app = db_prep_query($sql_app, $v, $t);
while ($row_app = db_fetch_array($res_app)) {
    $generatorUrlMetadata = $generatorBaseUrlMetadata."outputFormat=iso19139&id=".$row_app['uuid'];
    logMessages("URL for app requested: ".$generatorUrlMetadata);
    $generatorInterfaceObject = new connector($generatorUrlMetadata);
    $ISOFile = $generatorInterfaceObject->file;
    logMessages("Metadata uuid: ".$row_app['uuid']);
    //generate temporary files under tmp
    if($h = fopen($metadataDir."/mapbenderApplicationMetadata_".$row_app['uuid']."_iso19139.xml","w")){
        if(!fwrite($h,$ISOFile)){
            logMessages("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/metadata/mapbenderMetadata_".$row_app['uuid']."_iso19139.xml");
	}
	logMessages("Application metadata file with metadata uuid ".$row_app['uuid']." written to ".$metadataDir);
	fclose($h);
	$countApplications++;
    }
}
//export rest interfaces for wfs2.0.0+ based wfs featuretypes which are searchable and opendata classified

$sql_rest = "select wfs_featuretype.featuretype_id from wfs_featuretype where fkey_wfs_id in (select wfs_id from wfs where wfs_id in (select fkey_wfs_id from wfs_termsofuse inner join termsofuse on fkey_termsofuse_id = termsofuse_id where termsofuse.isopen = 1) and wfs_version = '2.0.0') and featuretype_searchable = 1";

$v = array();
$t = array();
$res_rest = db_prep_query($sql_rest, $v, $t);
while ($row_rest = db_fetch_array($res_rest)) {
    //$generatorUrlMetadata = $generatorBaseUrlMetadata."outputFormat=iso19139&id=".$row_app['uuid'];
    $generatorDlsUrlRest = $generatorBaseUrlDlsWfs2."SERVICETYPE=ogcapifeatures&outputFormat=iso19139&Id=".$row_rest['featuretype_id'];
    //logMessages("URL for app requested: ".$generatorUrlMetadata);
    $generatorInterfaceObject = new connector($generatorDlsUrlRest);
    $ISOFile = $generatorInterfaceObject->file;
    logMessages("Featuretype id: ".$row_rest['featuretype_id']);
    //generate temporary files under tmp
    if($h = fopen($metadataDir."/mapbenderRestMetadata_".$row_rest['featuretype_id']."_iso19139.xml","w")){
        if(!fwrite($h,$ISOFile)){
            logMessages("mod_exportISOMetadata.php: cannot write to file: ".$metadataDir."/metadata/mapbenderRestMetadata_".$row_rest['featuretype_id']."_iso19139.xml");
	}
	logMessages("REST metadata file with featuretype_id ".$row_rest['featuretype_id']." written to ".$metadataDir);
	fclose($h);
	$countRestInterfaces++;
    }
}


 
logMessages("Number of generated View Service Metadata Records (one for each layer): ".$countLayer);
logMessages("Number of generated Data Metadata Records (multiple for each layer): ".$countMetadataURL);
logMessages("Number of generated Download Service Metadata Records (multiple for each metadataset): ".$countDls);
logMessages("Number of generated Application Metadata Records: ".$countApplications);
logMessages("Number of generated REST Service Metadata Records: ".$countRestInterfaces);

logMessages(date('Y-m-d - H:i:s', time()));


function logMessages($message) {
    if (php_sapi_name() === 'cli' OR defined('STDIN')) {
        echo __FILE__.": ".$message."\n";
    } else {
        $e = new mb_exception(__FILE__.": ".$message);
    }
}

?>
