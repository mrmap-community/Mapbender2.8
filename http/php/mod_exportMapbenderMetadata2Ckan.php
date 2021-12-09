<?php
//mod_exportMapbenderMetadata2Ckan.php
//https://mb2wiki.mapbender2.org/SearchInterface
//
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_iso19139.php");
require_once(dirname(__FILE__)."/../classes/class_cache.php");

header('Content-Type: application/json');
/*
 * mapping of categories - hard coded for Rhineland-Palatinate - TODO: should be outsourced to conf folder
 */
$topicCkanCategoryMap = array(
    "1" => "farming",
    "2" => "biota",
    "3" => "boundaries",
    "4" => "climatologyMeteorologyAtmosphere",
    "5" => "economy",
    "6" => "elevation",
    "7" => "environment",
    "8" => "geoscientificInformation",
    "9" => "health",
    "10" => "imageryBaseMapsEarthCover",
    "11" => "intelligenceMilitary",
    "12" => "inlandWaters",
    "13" => "location",
    "14" => "oceans",
    "15" => "planningCadastre",
    "16" => "society",
    "17" => "structure",
    "18" => "transportation",
    "19" => "utilitiesCommunication"
);

/*categories from https://tpp.rlp.de - ckan 2.9
 * [
  {
    "value": "buildings_living",
    "label": {
      "en": "Buildings & Living",
      "de": "Bauen & Wohnen"
    }
  },
  {
    "value": "population_demography_integration",
    "label": {
      "en": "Population, Demography & Integration",
      "de": "Bevölkerung, Demografie & Integration"
    }
  },
  {
    "value": "education_science",
    "label": {
      "en": "Education & Science",
      "de": "Bildung & Wissenschaft"
    }
  },
  {
    "value": "honorary_office",
    "label": {
      "en": "Honorary office",
      "de": "Ehrenamt"
    }
  },
  {
    "value": "energy_climate",
    "label": {
      "en": "Energy & Climate",
      "de": "Energie & Klima"
    }
  },
  {
    "value": "europe_and_external_relations",
    "label": {
      "en": "Europe & external relations",
      "de": "Europa & Außenbeziehungen"
    }
  },
  {
    "value": "community_and_community_associations",
    "label": {
      "en": "Community & community associations",
      "de": "Gemeinden & Gemeindeverbände"
    }
  },
  {
    "value": "geography_geology_spatialdata",
    "label": {
      "en": "Geography, Geology & Spatial data",
      "de": "Geografie, Geologie & Geodaten"
    }
  },
  {
    "value": "law_justice",
    "label": {
      "en": "Law & Justice",
      "de": "Gesetze & Justiz"
    }
  },
  {
    "value": "health_nutrition",
    "label":
    {
      "en": "Health & Nutrition",
      "de": "Gesundheit & Ernährung"
    }
  },
  {
    "value": "infrastructure",
    "label": {
      "en": "Infrastructure",
      "de": "Infrastruktur"
    }
  },
  {
    "value": "culture_sport_tourism",
    "label": {
      "en": "Culture, Sport & Tourism",
      "de": "Kultur, Sport & Tourismus"
    }
  },
  {
    "value": "regional_planning",
    "label": {
      "en": "Regional planning",
      "de": "Landesplanung"
    }
  },
  {
    "value": "agriculture_viniculture_forest",
    "label": {
      "en": "Agriculture, Viniculture & Forest",
      "de": "Landwirtschaft, Weinbau & Forsten"
    }
  },
  {
    "value": "nature_environment",
    "label": {
      "en": "Nature & Environment",
      "de": "Natur & Umwelt"
    }
  },
  {
    "value": "public_administration_budget_taxes",
    "label": {
      "en": "Public administration, Budget & Taxes",
      "de": "Öffentliche Verwaltung, Haushalt & Steuern"
    }
  },
  {
    "value": "politics_elections",
    "label": {
      "en": "Politics & Elections",
      "de": "Politik & Wählen"
    }
  },
  {
    "value": "social_affairs_family_children_youth_women",
    "label": {
      "en": "Social affairs, Family, Children, Youth & Women",
      "de": "Soziales, Familie, Kinder, Jugend & Frauen"
    }
  },
  {
    "value": "transport_traffic",
    "label": {
      "en": "Transport & Traffic",
      "de": "Transport & Verkehr"
    }
  },
  {
    "value": "consumer_proctection",
    "label": {
      "en": "Consumer proctection",
      "de": "Verbraucherschutz"
    }
  },
  {
    "value": "economy_work",
    "label": {
      "en": "Economy & Work",
      "de": "Wirtschaft & Arbeit"
    }
  }
]

["buildings_living","population_demography_integration","education_science","honorary_office","energy_climate",
"europe_and_external_relations","community_and_community_associations","geography_geology_spatialdata","law_justice",
"health_nutrition","infrastructure","culture_sport_tourism","regional_planning","agriculture_viniculture_forest",
"nature_environment","public_administration_budget_taxes","politics_elections","social_affairs_family_children_youth_women",
"transport_traffic","consumer_proctection","economy_work"]
 */

$topicCkanCategoryMap = array(
    "1" => "nature_environment,geography_geology_spatialdata,agriculture_viniculture_forest",//"1" => "farming",
    "2" => "geography_geology_spatialdata,nature_environment,agriculture_viniculture_forest",//"2" => "biota",
    "3" => "geography_geology_spatialdata,community_and_community_associations",//"3" => "boundaries",
    "4" => "geography_geology_spatialdata,nature_environment,energy_climate",//"4" => "climatologyMeteorologyAtmosphere",
    "5" => "geography_geology_spatialdata,population_demography_integration,economy_work",//"5" => "economy",
    "6" => "geography_geology_spatialdata,regional_planning",//"6" => "elevation",
    "7" => "nature_environment",//"7" => "environment",
    "8" => "geography_geology_spatialdata,energy_climate",//"8" => "geoscientificInformation",
    "9" => "health_nutrition",//"9" => "health",
    "10" => "geography_geology_spatialdata,energy_climate",//"10" => "imageryBaseMapsEarthCover",
    "11" => "law_justice",//"11" => "intelligenceMilitary",
    "12" => "geography_geology_spatialdata,transport_traffic,infrastructure",//"12" => "inlandWaters",
    "13" => "geography_geology_spatialdata,infrastructure",//"13" => "location",
    "14" => "geography_geology_spatialdata,nature_environment",//"14" => "oceans",
    "15" => "geography_geology_spatialdata,buildings_living,law_justice,public_administration_budget_taxes,regional_planning",//"15" => "planningCadastre",
    "16" => "geography_geology_spatialdata,population_demography_integration,politics_elections",//"16" => "society",
    "17" => "geography_geology_spatialdata,infrastructure",//"17" => "structure",
    "18" => "geography_geology_spatialdata,infrastructure,transport_traffic",//"18" => "transportation",
    "19" => "geography_geology_spatialdata,infrastructure,transport_traffic,economy_work"//"19" => "utilitiesCommunication"
);

//require_once(dirname(__FILE__)."/../classes/class_syncCkan.php");
$start = microtime(true);

if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["id"];
    $pattern = '/^[\d]*$/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo '{"success": false, "help": "Parameter id is not valid (integer)"}';
        die();
    }
    $id = $testMatch;
    $testMatch = NULL;
}
$forceCache = true;
$mapbenderBaseUrl = "https://www.geoportal.rlp.de/mapbender/";
$mapbenderWebserviceUrl = $mapbenderBaseUrl;
$mapbenderWebserviceUrl = "http://localhost/mapbender/";

$cache = new Cache();

$cacheVariableName = md5($mapbenderBaseUrl. "ckan_metadata_" . $id);

$actualDate = date("Y-m-d H:i:s");
$maxAgeInSeconds = 3600; //1hour
$forceCache = true;


if (isset($_REQUEST["cache"]) & $_REQUEST["cache"] != "") {
    //validate
    $testMatch = $_REQUEST["cache"];
    if ($testMatch != 'true' && $testMatch != 'false'){
        echo '{"success": false, "help": "Parameter cache is not valid (true/false)"}';
        die();
    }
    if ($testMatch == 'false') {
        $forceCache = false;
    }
    $testMatch = NULL;
}

if ($forceCache && $cache->isActive && $cache->cachedVariableExists($cacheVariableName) && ((date_create($actualDate)->getTimestamp() - date_create(date("Y-m-d H:i:s",$cache->cachedVariableCreationTime($cacheVariableName)))->getTimestamp()) < $maxAgeInSeconds)) {
    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: read " . $mapbenderBaseUrl. "ckan_metadata_" . $id . " from ".$cache->cacheType." cache!");
    //parse result and add origin cache
    $cachedObj = json_decode($cache->cachedVariableFetch($cacheVariableName));
    $cachedObj->origin = "cache";
    echo json_encode($cachedObj, true);
} else {
    
    //get organisation list from webservice
    $connector = new connector();
    
    $orgaListResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
    
    $orgaListObject = json_decode($orgaListResult);
    $orgaIdArray = array();
    //echo $orgaListResult;
    //die();
    foreach ($orgaListObject->organizations as $orga) {
        $orgaIdArray[] = (integer)$orga->id;
    }
    //echo json_encode($orgaIdArray);
    //die();
    if (!in_array($id, $orgaIdArray)) {
        echo '{"success": false, "help": "There is no organization with requested id in the catalogue!"}';
        die();
    }
    
    //maybe we iterate over all organizations - that need time ;-) - better to do it by spawn processes from shell!
    
    
    //get single orga info
    $orgaResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationInfo.php?outputFormat=ckan&id=" . $id);
    $orgaObject = json_decode($orgaResult);
    //echo $orgaResult;
    //die();
    $resultsPerPage = 10;
    
    $mapbenderBaseSearchInterface = $mapbenderWebserviceUrl . "php/mod_callMetadata.php?";
    $orgaId = $id;
    $baseUrl = $mapbenderBaseSearchInterface . "searchResources=dataset&resolveCoupledResources=true&registratingDepartments=".$orgaId;
    $baseUrlCount = $baseUrl. "&maxResults=1";
    
    $mapbenderMetadataUrl = "";
    //count all resources
    
    //create an array with layer ids that are already published as dataset metadata exists and is coupled
    $coupledLayerArray = array();
    //define if export handler should iterate over layer after metadata is crawled
    $exposeUncoupledLayer = false;
    
    $countResult = $connector->load($baseUrlCount);
    //parse maxResults
    $resultObject = json_decode($countResult);
    $maxPages = ceil($resultObject->dataset->md->nresults / $resultsPerPage);
    header('Content-Type: application/json');
    $returnObject = new stdClass();
    $returnObject->help = "helptext";
    $returnObject->success = true;
    $returnObject->result = array();
    $j = 0;
    $package = array();
    $e = new mb_exception("Try to load ".$resultObject->dataset->md->nresults." datasets for ".$orgaResult);
    for ($i=1; $i <= $maxPages; $i++) {
        $e = new mb_exception("Use SearchInterface for dataset: Page " . $i . " of ".$maxPages);
        $pageUrl = $baseUrl. "&searchPages=" . $i . "&maxPages=" . $resultsPerPage;
        //echo $pageUrl . "<br>";
        $result = $connector->load($pageUrl);
        $resultObject = json_decode($result);
        foreach($resultObject->dataset->srv as $dataset) {
            $e = new mb_exception("Dataset uuid: ".$dataset->uuid);
            $layerArray = array();
            $featuretypeArray = array();
            $downloadArray = array();
            $package[$j] = new stdClass();
            $package[$j]->maintainer = $orgaObject->title;
            $package[$j]->point_of_contact = $orgaObject->title;
            $package[$j]->point_of_contact_email = str_replace(" (at) ", "@", $orgaObject->department_email);
            $package[$j]->maintainer_email = str_replace(" (at) ", "@", $orgaObject->department_email);
            $package[$j]->metadata_modified = $dataset->date;
            $package[$j]->id = $dataset->uuid;
            $package[$j]->title = $dataset->title;
            $package[$j]->description = $dataset->abstract;
            $package[$j]->license_id = $dataset->license_id;
            $package[$j]->registerobject_type = "Par_7_1_9";
            //build categories
            $categoryString = "";
            foreach ($dataset->isoCategories as $isoCategory) {
                $categoryString .= ",".$topicCkanCategoryMap[$isoCategory];
            }
            $categoryString = trim($categoryString, ",");
            $categoryArray = array_unique(explode(",", $categoryString));
            if (count($categoryArray) == 0) {
                $categoryArray = array("geography_geology_spatialdata");
            } 
            $package[$j]->information_category = (array)$categoryArray;
            //TODO bbox, license, ...
            //echo $dataset->id . " - " .$dataset->title. "<br>";
            //parse dataset metadata and extract relevant information - keywords/tags, themes and license info, actuality
            //https://www.geoportal.rlp.de/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=9ec4e052-ebd2-2c44-f258-25557de7a6b7&outputFormat=iso19139
            $metadataResolverUrl = $mapbenderWebserviceUrl . "php/mod_dataISOMetadata.php?cache=true&outputFormat=iso19139&id=";
            $metadataUrl = $metadataResolverUrl.$dataset->uuid;
            //$metadataResult = $connector->load($metadataUrl);
            $iso19139Md = new Iso19139();
            $e = new mb_exception("Parse ISO Metadata");
            $iso19139Md->createFromUrl($metadataUrl);
            //echo "Keywords: " . json_encode($iso19139Md->keywords)."<br>";
            //echo "ISO Categories: " . json_encode($iso19139Md->isoCategoryKeys)."<br>";
            //"groups":[{"name":"gdi-rp"},{"name":"geo"},{"name":"infrastruktur_bauen_wohnen"},{"name":"transport_verkehr"},{"name":"gesetze_justiz"}],"tags":[{"name":"Bauleitplan"},{"name":"Bebauungsplan"},{"name":"Bplan"},{"name":"Simmern (Hunsr\u00fcck)"}]
            if (is_array($iso19139Md->keywords) && count($iso19139Md->keywords) > 0) {
                foreach ($iso19139Md->keywords as $key => $value) {
                    $package[$j]->tags[] = array("name" => (string)$value);
                }
            }
            //add first resource - the original metadata for this package
            $package[$j]->resource = array();
            $metadataResource = array("name" => "Originäre Metadaten",
                                      "description" => $dataset->title . " - Anzeige der originären Metadaten",
                                      "format" => "HTML",
                                      "url" => $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $dataset->uuid
            );
            $package[$j]->resource[] = $metadataResource;
            //TODO the same for categories - map them to tpp categories
            foreach ($dataset->coupledResources as $key => $value) {
                switch($key) {
                    case "layer":
                        //TODO add layer title to coupled resource information!!!!
                        foreach ($value as $key1 => $value1) {
                            $layerArray[] = $value1->id;
                            $coupledLayerArray[] = $value1->id;
                            //extract layer title from hierarchy
                            $layerTitle = $value1->srv->layer[0]->title;
                            //build ckan resource records for the layer. For each layer we have metadata, full viewer, geoportal viewer, wms interface
                            $layerViewResource_1 = array("name" => "Online Karte",
                                "description" => $layerTitle . " - Vorschau im integrierten Kartenviewer",
                                "format" => "Kartenviewer",
                                "url" => $mapbenderBaseUrl . "extensions/mobilemap/map.php?layerid=" . $value1->id,
                                "id" => $package[$j]->id . "_mapviewer_layer_" . $value1->id
                            );
                            $layerViewResource_2 = array("name" => "GeoPortal.rlp",
                                "description" =>  $layerTitle . " - Anzeige im GeoPortal.rlp",
                                "format" => "GeoPortal.rlp",
                                "url" => $mapbenderBaseUrl . "../portal/karten.html?LAYER[zoom]=1&LAYER[id]=" . $value1->id,
                                "id" => $package[$j]->id . "_geoportal_layer_" . $value1->id
                            );
                            $layerMetadataResource = array("name" => "Originäre Metadaten für Kartenebene",
                                "description" => "Kartenebene: " . $layerTitle . " - Anzeige der originären Metadaten",
                                "format" => "HTML",
                                "url" => $mapbenderBaseUrl . "php/mod_showMetadata.php?languageCode=de&resource=layer&layout=tabs&id=" . $value1->id,
                                "id" => $package[$j]->id . "_layer_metadata_" . $value1->id
                            );
                            $layerWMSResource = array("name" => "WMS Schnittstelle",
                                "description" => "Ebene: " . $layerTitle,
                                "format" => "WMS",
                                "url" => $mapbenderBaseUrl . "php/wms.php?layer_id=" . $value1->id . "&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS",
                                "id" => $package[$j]->id . "_wms_interface_" . $value1->id
                            );
                            $package[$j]->resource[] = $layerViewResource_1;
                            $package[$j]->resource[] = $layerViewResource_2;
                            $package[$j]->resource[] = $layerMetadataResource;
                            $package[$j]->resource[] = $layerWMSResource;
                        }
                        break;
                    case "featuretype":
                        foreach ($value as $key1 => $value1) {
                            $featuretypeArray[] = $value1->id;
                            //build ckan resource records for the featuretype. For each featuretype we have metadata, wfs interface, maybe ogc api features interface
                            /*$featuretypeMetadataResource = array("name" => "Originäre Metadaten für Objektart",
                                "description" => "Kartenebene: " . $value1->title . " - Anzeige der originären Metadaten",
                                "format" => "HTML",
                                "url" => $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $dataset->uuid
                            );*/               
                        }
                        break;
                    case "inspireAtomFeeds":
                        foreach ($value as $key1 => $value1) {
                            switch ($value1->type) {
                                case "ogcapifeatures":
                                    $featuretypeAccessResource_1 = array("name" => "OGC API Features (REST)",
                                        "description" =>   "Objektart: " . $value1->resourceName. " - ISO19168-1:20202 API",
                                        "format" => "REST",
                                        "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                        "id" => $package[$j]->id . "_ogc_api_interface_" . $value1->resourceName
                                    );
                                    $package[$j]->resource[] = $featuretypeAccessResource_1;
                                    break;
                                case "wfsrequest":
                                    $atomFeedAccessResource_1 = array("name" => "Vektordownload nach EU-Standard",
                                    "description" => $value1->serviceTitle,
                                    "format" => "ATOM",
                                    "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                    "id" => $package[$j]->id . "_atom_feed_wfs_" . $value1->resourceName
                                    );
                                    $package[$j]->resource[] = $atomFeedAccessResource_1;
                                    break;
                                case "wmslayergetmap":
                                    $atomFeedAccessResource_1 = array("name" => "Rasterdownload nach EU-Standard",
                                    "description" => $value1->serviceTitle,
                                    "format" => "ATOM",
                                    "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                    "id" => $package[$j]->id . "_atom_feed_wms_" . $value1->resourceId
                                    );
                                    $package[$j]->resource[] = $atomFeedAccessResource_1;
                                    break;
                            
                            }                        
                            //build ckan resource records for the atomfeed entries -> atomfeed xml, atomfeed html, maybe ogc api features interface
                        }
                        break;                    
                } 
            }
            $j++;
        } 
    }
    
    if ($exposeUncoupledLayer == true) {
        $baseUrl = $mapbenderBaseSearchInterface . "searchResources=wms&resolveCoupledResources=true&registratingDepartments=".$orgaId;
        $baseUrlCount = $baseUrl. "&maxResults=1";
        $countResult = $connector->load($baseUrlCount);
        //parse maxResults
        $resultObject = json_decode($countResult);
        $maxPages = ceil($resultObject->wms->md->nresults / $resultsPerPage);
        $returnObject->layersToCrawlFurther = (integer)$resultObject->wms->md->nresults;
        for ($i=1; $i <= $maxPages; $i++) {
            $pageUrl = $baseUrl. "&searchPages=" . $i . "&maxPages=" . $resultsPerPage;
            $result = $connector->load($pageUrl);
            $resultObject = json_decode($result);
            foreach($resultObject->wms->srv as $wms) {
                //iterate over all nested layers
                
            }
        }
    }
    
    
    
    
    $time_elapsed_secs = microtime(true) - $start;
    $returnObject->timeToGenerate = $time_elapsed_secs;
    $returnObject->dateTime = $actualDate;
    $returnObject->result = $package;
    if ($cache->isActive) {
        //delete old variable first - cause the timestamp will remain the old!
        if ($cache->cachedVariableExists($cacheVariableName)) {
            $cache->cachedVariableDelete($cacheVariableName);
            //$e = new mb_exception(": Delete old json in cache!");
        }
        $cache->cachedVariableAdd($cacheVariableName,json_encode($returnObject, true));
        //$e = new mb_exception(": Save json to apc cache!");
    }
    $returnObject->origin = "direct";
    echo json_encode($returnObject, true);
}

?>
