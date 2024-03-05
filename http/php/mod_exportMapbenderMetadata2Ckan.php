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
$baseUrlPortal = "https://www.geoportal.rlp.de";
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

if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
    //validate
    $testMatch = $_REQUEST["outputFormat"];
    if ($testMatch != 'json' && $testMatch != 'rdfxml'){
        echo '{"success": false, "help": "Parameter outputFormat is not valid (json/rdfxml)"}';
        die();
    }
    if ($testMatch == 'false') {
        $outputFormat = 'json';
    } else {
        $outputFormat = $testMatch;
    }
    if ($outputFormat == 'rdfxml') {
        $cacheVariableName = md5($mapbenderBaseUrl. "ckan_metadata_" . $id . "_rdfxml");
    }
    $testMatch = NULL;
}

if ($outputFormat == 'rdfxml') {
    $forceCache = false;
    header("Content-Type: application/rdf+xml");
    //create new rdfxml object 
    // Initialize XML document
    $rdfXmlDoc = new DOMDocument ( '1.0' );
    $rdfXmlDoc->encoding = 'UTF-8';
    $rdfXmlDoc->preserveWhiteSpace = false;
    $rdfXmlDoc->formatOutput = true;
    // Creating the central "RDF" node
    $RDF = $rdfXmlDoc->createElementNS ( 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF' );
    $RDF = $rdfXmlDoc->appendChild ( $RDF );
   
    $RDF->setAttribute ( "xmlns:vcard", "http://www.w3.org/2006/vcard/ns#" );
    $RDF->setAttribute ( "xmlns:dct", "http://purl.org/dc/terms/" );
    $RDF->setAttribute ( "xmlns:dcat", "http://www.w3.org/ns/dcat#" );
    $RDF->setAttribute ( "xmlns:foaf", "http://xmlns.com/foaf/0.1/" );
    $RDF->setAttribute ( "xmlns:locn", "http://www.w3.org/ns/locn#" );
    
    //build catalog part
    $catalog = $rdfXmlDoc->createElement ( "dcat:Catalog" );
    $catalog->setAttribute ( "rdf:about", $baseUrlPortal );
    $catalogTitle = $rdfXmlDoc->createElement ( "dct:title" );
    $catalogTitleText = $rdfXmlDoc->createTextNode ( "GeoPortal.rlp" );
    $catalogTitle->appendChild($catalogTitleText);
    $catalogLanguage = $rdfXmlDoc->createElement ( "dct:language" );
    $catalogLanguageText = $rdfXmlDoc->createTextNode ( "de" );
    $catalogLanguage->appendChild($catalogLanguageText);
    $catalogModified = $rdfXmlDoc->createElement ( "dct:modified" );
    $dt = new DateTime();
    $catalogModifiedText = $rdfXmlDoc->createTextNode ( $dt->format('Y-m-d\TH:i:s.').substr($dt->format('u'),0,3) . 'Z' );
    $catalogModified->appendChild($catalogModifiedText);
    //append information
    $catalog->appendChild($catalogTitle);
    $catalog->appendChild($catalogLanguage);
    $catalog->appendChild($catalogModified);
    //build organization part
    //get organisation list from webservice
    $connector = new connector();   
    $orgaListResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
    $orgaListObject = json_decode($orgaListResult);
    $orgaIdArray = array();
    foreach ($orgaListObject->organizations as $orga) {
        $orgaIdArray[] = (integer)$orga->id;
    }
    if (!in_array($id, $orgaIdArray)) {
        header('Content-Type: application/json');
        echo '{"success": false, "help": "There is no organization with requested id in the catalogue!"}';
        die();
    }
    //get single orga info
    $orgaResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationInfo.php?outputFormat=ckan&id=" . $id);
    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: organization: " . $orgaResult);
    $orgaObject = json_decode($orgaResult);
    /*
    <foaf:Organization rdf:about="https://daten.rlp.de/organization/a7ad2b18-e02c-4492-b244-c2515f697211">
    <foaf:name>
    Landesamt für Vermessung und Geobasisinformationen
    </foaf:name>
    </foaf:Organization>
    */
    //create organization entry
    $organization = $rdfXmlDoc->createElement ( "foaf:Organization" );
    $organization->setAttribute ( "rdf:about", $baseUrlPortal . "/organization/" . $orgaObject->id);
    $organizationName = $rdfXmlDoc->createElement ( "foaf:name" );
    $organizationNameText = $rdfXmlDoc->createTextNode( $orgaObject->title );
    $organizationName->appendChild( $organizationNameText );
    $organization->appendChild( $organizationName );  
    $RDF->appendChild ( $catalog );
    //iterate over datasets
    /*
     * 
     */
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
    /*header('Content-Type: application/json');
    $returnObject = new stdClass();
    $returnObject->help = "helptext";
    $returnObject->success = true;
    $returnObject->result = array();*/
    $j = 0;
    $package = array();
    $e = new mb_exception("Try to load ".$resultObject->dataset->md->nresults." datasets for ".$orgaResult);
    $distributionArray = array();
    for ($i=1; $i <= $maxPages; $i++) {
        //$e = new mb_exception("Use SearchInterface for dataset: Page " . $i . " of ".$maxPages);
        $pageUrl = $baseUrl. "&searchPages=" . $i . "&maxPages=" . $resultsPerPage;
        //echo $pageUrl . "<br>";
        $result = $connector->load($pageUrl);
        $resultObject = json_decode($result);
        foreach($resultObject->dataset->srv as $gpDataset) {
            $e = new mb_exception("Dataset uuid: ".$gpDataset->uuid);
            
            /*$layerArray = array();
            $featuretypeArray = array();
            $downloadArray = array();*/
            
            /*$package[$j] = new stdClass();
            $package[$j]->maintainer = $orgaObject->title;
            $package[$j]->point_of_contact = $orgaObject->title;
            $package[$j]->point_of_contact_email = str_replace(" (at) ", "@", $orgaObject->department_email);
            $package[$j]->maintainer_email = str_replace(" (at) ", "@", $orgaObject->department_email);
            $package[$j]->metadata_modified = $dataset->date;
            $package[$j]->id = $dataset->uuid;
            $package[$j]->title = $dataset->title;
            $package[$j]->description = $dataset->abstract;
            $package[$j]->license_id = $dataset->license_id;*/
            
            $firstDataset = $rdfXmlDoc->createElement ( "dcat:dataset" );
            
            $dataset = $rdfXmlDoc->createElement ( "dcat:Dataset" );
            $dataset->setAttribute ( "rdf:about", $baseUrlPortal ."/dataset/" . $gpDataset->uuid );
            //title
            $title = $rdfXmlDoc->createElement ( "dct:title" );
            $titleText = $rdfXmlDoc->createTextNode( $gpDataset->title );
            $title->appendChild($titleText);
            $dataset->appendChild($title);
            //description
            $description = $rdfXmlDoc->createElement ( "dct:description" );
            $descriptionText = $rdfXmlDoc->createTextNode( $gpDataset->abstract );
            $description->appendChild($descriptionText);
            $dataset->appendChild($description);
            //identifier
            $identifier = $rdfXmlDoc->createElement ( "dct:identifier" );
            $identifierText = $rdfXmlDoc->createTextNode( $gpDataset->uuid );
            $identifier->appendChild($identifierText);
            $dataset->appendChild($identifier);
            //publisher
            $publisher = $rdfXmlDoc->createElement ( "dct:publisher" );
            $publisher->setAttribute ( "rdf:resource", $baseUrlPortal . "/organization/" . $orgaObject->id );
            $dataset->appendChild( $publisher );
            //distribution 1
            $distribution = $rdfXmlDoc->createElement ( "dcat:distribution" );
            $distribution->setAttribute ( "rdf:resource", $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/html_metadata_" .  $gpDataset->uuid);
            $dataset->appendChild( $distribution );
            //<dcat:distribution rdf:resource="https://ckan-demo.webhosting-franken.com/dataset/ce00b0a8-2c1d-44b1-a1ef-ee9e2e0f3263/resource/17b735da-132f-4442-9952-c77f44a521ff"/>
            
            $firstDataset->appendChild ( $dataset );
            $catalog->appendChild ( $firstDataset );
            
            //add distribution 
            /*<dcat:Distribution rdf:about="https://ckan-demo.webhosting-franken.com/dataset/b2810833-7212-42d5-82b5-cc80e27e96fe/resource/28f256f0-3748-407b-abbc-611524cd93c9">
            <dct:title>
            GetCapabilities request for the Location of former Elbe Urstrom Valley - WFS service
            </dct:title>
            <dct:format>WFS</dct:format>
            <dcat:accessURL rdf:resource="https://www.geoseaportal.de/wss/service/SGE_AdditionalInformation/guest?SERVICE=WFS&REQUEST=GetCapabilities&VERSION=2.0.0"/>
            <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2024-02-07T21:36:03.630976</dct:issued>
            <dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2024-02-07T21:36:03.594170</dct:modified>
            </dcat:Distribution>*/
            
            
            $Distribution = $rdfXmlDoc->createElement ( "dcat:Distribution" );
            $Distribution->setAttribute ( "rdf:about", $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/html_metadata_" .  $gpDataset->uuid);
            
            $distributionTitle = $rdfXmlDoc->createElement ( "dct:title" );
            $distributionTitleText = $rdfXmlDoc->createTextNode( "Original Metadaten HTML");
            $distributionTitle->appendChild($distributionTitleText);
            $Distribution->appendChild($distributionTitle);
            
            $distributionFormat = $rdfXmlDoc->createElement ( "dct:format" );
            $distributionFormatText = $rdfXmlDoc->createTextNode( "HTML" );
            $distributionFormat->appendChild($distributionFormatText);
            $Distribution->appendChild($distributionFormat);
            
            $distributionAccessUrl = $rdfXmlDoc->createElement ( "dcat:accessURL" );
            $distributionAccessUrl->setAttribute ( "rdf:resource", $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $gpDataset->uuid );
            $Distribution->appendChild($distributionAccessUrl);
            
            $distributionArray[] = $Distribution;
            
            
            //get resources / distributions
            /*
             * 
             */
            $metadataResolverUrl = $mapbenderWebserviceUrl . "php/mod_dataISOMetadata.php?cache=true&outputFormat=iso19139&id=";
            $metadataUrl = $metadataResolverUrl . $gpDataset->uuid;
            //$metadataResult = $connector->load($metadataUrl);
            $iso19139Md = new Iso19139();
            //$e = new mb_exception("Parse ISO Metadata");
            $iso19139Md->createFromUrl($metadataUrl);
            //echo "Keywords: " . json_encode($iso19139Md->keywords)."<br>";
            //echo "ISO Categories: " . json_encode($iso19139Md->isoCategoryKeys)."<br>";
            //"groups":[{"name":"gdi-rp"},{"name":"geo"},{"name":"infrastruktur_bauen_wohnen"},{"name":"transport_verkehr"},{"name":"gesetze_justiz"}],"tags":[{"name":"Bauleitplan"},{"name":"Bebauungsplan"},{"name":"Bplan"},{"name":"Simmern (Hunsr\u00fcck)"}]
            //TODO - do this before - see above
            /*if (is_array($iso19139Md->keywords) && count($iso19139Md->keywords) > 0) {
                foreach ($iso19139Md->keywords as $key => $value) {
                    $package[$j]->tags[] = array("name" => (string)$value);
                }
            }*/
            
            //add first resource - the original metadata for this package
            /*$resource = array();
            $metadataResource = array("name" => "Originäre Metadaten",
                "description" => $dataset->title . " - Anzeige der originären Metadaten",
                "format" => "HTML",
                "url" => $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $dataset->uuid
            );
            $resource[] = $metadataResource;*/
            //$distribution = $rdfXmlDoc->createElement ( "dcat:Dataset" );
            
            
            /*
             * 
             */
            
            
            
        }
    }
    /*
     * End of iteration over datasets 
     */
    foreach ($distributionArray as $dist) {
        $RDF->appendChild( $dist );
    }
    /*
    <dct:publisher rdf:resource="https://daten.rlp.de/organization/4cf5b906-d4bd-4a01-93a3-2be5d4149318"/>
    */
    //iterate over resources / distributions
    
    $RDF->appendChild ( $organization );
    
    //export domdocument to xml
    $xml = $rdfXmlDoc->saveXML();
    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php dcat xml: " . $xml);
    echo $xml;
    die();
}


if ($forceCache && $cache->isActive && $cache->cachedVariableExists("mapbender:" . $cacheVariableName) && ((date_create($actualDate)->getTimestamp() - date_create(date("Y-m-d H:i:s",$cache->cachedVariableCreationTime("mapbender:" . $cacheVariableName)))->getTimestamp()) < $maxAgeInSeconds)) {
    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: read " . $mapbenderBaseUrl. "ckan_metadata_" . $id . " from ".$cache->cacheType." cache!");
    //parse result and add origin cache
    $cachedObj = json_decode($cache->cachedVariableFetch("mapbender:" . $cacheVariableName));
    $cachedObj->origin = "cache";
    echo json_encode($cachedObj, true);
    die();
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
            //$e = new mb_exception("Parse ISO Metadata");
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
                            //check if id already exists
                            
                            $layerResources = array("layerViewResource_1", "layerViewResource_2", "layerMetadataResource", "layerWMSResource");
                            foreach ($layerResources as $layerResource) {
                                $idArray = [];
                                foreach ($package[$j]->resource as $resource) {
                                    $idArray[] = $resource->id;
                                }
                                if (!in_array(${$layerResource}['id'], $idArray)) {
                                    $package[$j]->resource[] = ${$layerResource};
                                }
                                unset($idArray);
                            }
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
                                        "id" => $package[$j]->id . "_ogc_api_interface_" . $value1->resourceName . "_" . $value1->serviceId
                                    );
                                    //check if id already exists - TODO: don't work as expected !
                                    $idArray = [];
                                    foreach ($package[$j]->resource as $resource) {
                                        $idArray[] = $resource->id;
                                    }
                                    if (!in_array($featuretypeAccessResource_1['id'], $idArray)) {
                                        $package[$j]->resource[] = $featuretypeAccessResource_1;
                                    }
                                    
                                    break;
                                case "wfsrequest":
                                    $atomFeedAccessResource_1 = array("name" => "Vektordownload nach EU-Standard",
                                        "description" => $value1->serviceTitle,
                                        "format" => "ATOM",
                                        "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                        "id" => $package[$j]->id . "_atom_feed_wfs_" . $value1->serviceId
                                    );
                                    //check if id already exists
                                    $idArray = [];
                                    foreach ($package[$j]->resource as $resource) {
                                        $idArray[] = $resource->id;
                                    }
                                    if (!in_array($atomFeedAccessResource_1['id'], $idArray)) {
                                        $package[$j]->resource[] = $atomFeedAccessResource_1;
                                    }
                                    
                                    break;
                                case "wmslayergetmap":
                                    $atomFeedAccessResource_1 = array("name" => "Rasterdownload nach EU-Standard",
                                    "description" => $value1->serviceTitle,
                                    "format" => "ATOM",
                                    "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                    "id" => $package[$j]->id . "_atom_feed_wms_" . $value1->resourceId
                                    );
                                    //check if id already exists
                                    $idArray = [];
                                    foreach ($package[$j]->resource as $resource) {
                                        $idArray[] = $resource->id;
                                    }
                                    if (!in_array($atomFeedAccessResource_1['id'], $idArray)) {
                                        $package[$j]->resource[] = $atomFeedAccessResource_1;
                                    }
                                    
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
        if ($cache->cachedVariableExists("mapbender:" . $cacheVariableName)) {
            $cache->cachedVariableDelete("mapbender:" . $cacheVariableName);
            //$e = new mb_exception(": Delete old json in cache!");
        }
        $cache->cachedVariableAdd("mapbender:" . $cacheVariableName,json_encode($returnObject, true));
        //$e = new mb_exception(": Save json to apc cache!");
    }
    $returnObject->origin = "direct";
    echo json_encode($returnObject, true);
}

?>
