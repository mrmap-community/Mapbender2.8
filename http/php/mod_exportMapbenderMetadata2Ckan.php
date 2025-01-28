<?php
//mod_exportMapbenderMetadata2Ckan.php
//https://mb2wiki.mapbender2.org/SearchInterface
//
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_iso19139.php");
require_once(dirname(__FILE__)."/../classes/class_cache.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

header('Content-Type: application/json');
$admin = new administration();
if (defined('METADATA_PORTAL_NAME') && METADATA_PORTAL_NAME != "") {
    $portalName = METADATA_PORTAL_NAME;
} else {
    $portalName = "Mapbender Metadata Portal";
}
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
/*
 * Pull inspire categories from database
 */
//inspire
$inspireCatHash = array();
$sql = "SELECT inspire_category_uri, inspire_category_code_en FROM inspire_category";
$res = db_query($sql);
while ($row = db_fetch_array($res)){
    $inspireCatHash[$row['inspire_category_code_en']] = $row['inspire_category_uri'];
    //$e = new mb_exception("inspireCatHash: ".$row['inspire_category_code_en'] ." : ". $row['inspire_category_id'] );
}
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
//http://publications.europa.eu/resource/authority/data-theme/
$inspire_hvd_themes_map = <<<JSON
{"key_namespace": "http://inspire.ec.europa.eu/theme",
  "array_namespace":"",
  "mapping": {
      "http://inspire.ec.europa.eu/theme/rs": [],
      "http://inspire.ec.europa.eu/theme/gg": [],
      "http://inspire.ec.europa.eu/theme/gn": ["GEOSPATIAL"],
      "http://inspire.ec.europa.eu/theme/au": ["GEOSPATIAL"],
      "http://inspire.ec.europa.eu/theme/ad": ["GEOSPATIAL"],
      "http://inspire.ec.europa.eu/theme/cp": ["GEOSPATIAL"],
      "http://inspire.ec.europa.eu/theme/tn": ["MOBILITY"],
      "http://inspire.ec.europa.eu/theme/hy": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/ps": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/el": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/lc": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/oi": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/ge": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/su": [],
      "http://inspire.ec.europa.eu/theme/bu": ["GEOSPATIAL"],
      "http://inspire.ec.europa.eu/theme/so": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/lu": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/hh": [],
      "http://inspire.ec.europa.eu/theme/us": [],
      "http://inspire.ec.europa.eu/theme/ef": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/pf": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/af": [],
      "http://inspire.ec.europa.eu/theme/pd": [],
      "http://inspire.ec.europa.eu/theme/am": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/nz": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/ac": [],
      "http://inspire.ec.europa.eu/theme/mf": [],
      "http://inspire.ec.europa.eu/theme/of": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/sr": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/br": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/hb": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/sd": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/er": ["EARTH OBSERVATION AND ENVIRONMENT"],
      "http://inspire.ec.europa.eu/theme/mr": ["EARTH OBSERVATION AND ENVIRONMENT"]
  }
}
JSON;

/*
 * https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/file-type
 */
$format_mapping = <<<JSON
{
    "WMS": "http://publications.europa.eu/resource/authority/file-type/WMS_SRVC",
    "WFS": "http://publications.europa.eu/resource/authority/file-type/WFS_SRVC",
    "HTML": "http://publications.europa.eu/resource/authority/file-type/HTML",
    "REST": "http://publications.europa.eu/resource/authority/file-type/REST"
}
JSON;

/*
 * https://op.europa.eu/en/web/eu-vocabularies/dataset/-/resource?uri=http://publications.europa.eu/resource/dataset/high-value-dataset-category
 * http://publications.europa.eu/resource/authority/bna/asd487ae75
 * http://data.europa.eu/bna/c_ac64a52d ?
 */

$base_path = "http://publications.europa.eu/resource/authority/";
$base_path = "http://data.europa.eu/";

$hvd_mapping = <<<JSON
{ 
   "GEOSPATIAL": "http://data.europa.eu/bna/c_ac64a52d",
   "EARTH OBSERVATION AND ENVIRONMENT": "http://data.europa.eu/bna/c_dd313021",
   "METEOROLOGICAL": "http://data.europa.eu/bna/c_164e0bf5",
   "STATISTICS": "http://data.europa.eu/bna/c_e1da4e07",
   "COMPANIES AND COMPANY OWNERSHIP": "http://data.europa.eu/bna/c_a9135398",
   "MOBILITY": "http://data.europa.eu/bna/c_b79e35eb"
}
JSON;

/*
 * http://publications.europa.eu/resource/authority/data-theme
 * https://github.com/SEMICeu/iso-19139-to-dcat-ap/blob/master/alignments/inspire-themes-to-mdr-data-themes.rdf
 */
$dcat_category_map = <<<JSON
{ "key_namespace": "http://inspire.ec.europa.eu/theme",
  "array_namespace":"http://publications.europa.eu/resource/authority/data-theme",
  "mapping": {
      "rs": ["REGI"],
      "gg": ["REGI"],
      "gn": ["REGI"],
      "au": ["GOVE"],
      "ad": ["REGI"],
      "cp": ["REGI", "ECON"],
      "tn": ["TRAN"],
      "hy": ["ENVI", "TECH"],
      "ps": ["ENVI"],
      "el": ["REGI"],
      "lc": ["ENVI"],
      "oi": ["REGI", "TECH"],
      "ge": ["REGI", "TECH"],
      "su": ["SOCI"],
      "bu": ["REGI"],
      "so": ["ENVI"],
      "lu": ["ECON", "ENVI"],
      "hh": ["HEAL"],
      "us": ["GOVE"],
      "ef": ["ENVI"],
      "pf": ["ECON"],
      "af": ["AGRI"],
      "pd": ["SOCI"],
      "am": ["ENVI"],
      "nz": ["ENVI"],
      "ac": ["ENVI"],
      "mf": ["ENVI", "TECH"],
      "of": ["ENVI"],
      "sr": ["ENVI"],
      "br": ["ENVI"],
      "hb": ["ENVI"],
      "sd": ["ENVI"],
      "er": ["ENER"],
      "mr": ["ECON", "ENVI", "ENER"]
  }
}
JSON;

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

$license_map = array(
    "cc-zero" => "http://dcat-ap.de/def/licenses/cc-zero",
    "dl-de-by-2.0" => "http://dcat-ap.de/def/licenses/dl-by-de/2.0",
    "dl-de-by-nc-1.0" => "http://dcat-ap.de/def/licenses/dl-by-nc-de/1.0",
    "odc-odbl-1.0" => "http://dcat-ap.de/def/licenses/odbl",
    "dl-de-zero-2.0" => "http://dcat-ap.de/def/licenses/dl-zero-de/2.0",
    "cc-by-sa-4.0" => "http://dcat-ap.de/def/licenses/cc-by-sa/4.0",
    "cc-by-3.0" => "http://dcat-ap.de/def/licenses/cc-by-de/3.0",
    "dl-de-by-1.0" => "http://dcat-ap.de/def/licenses/dl-by-de/1.0",
    "cc-nc-3.0" => "http://dcat-ap.de/def/licenses/cc-by-nc-de/3.0"
);

//TODO add crontributor id? - test for ogdp 
//Siehe: https://www.dcat-ap.de/def/dcatde/2.0/implRules/#konvention-12 

//require_once(dirname(__FILE__)."/../classes/class_syncCkan.php");
$start = microtime(true);
$orig_identifier = true;
$ckanId = false;
$mapbenderUuid = false;
$restrictToOpenData = true;
$forceCache = false;

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

if (isset($_REQUEST["ckanId"]) & $_REQUEST["ckanId"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["ckanId"];
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo '{"success": false, "help": "Parameter ckanId is not valid (uuid)"}';
        die();
    }
    $ckanId = $testMatch;
    $testMatch = NULL;
}

if (isset($_REQUEST["mapbenderUuid"]) & $_REQUEST["mapbenderUuid"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["mapbenderUuid"];
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo '{"success": false, "help": "Parameter mapbenderUuid is not valid (uuid)"}';
        die();
    }
    $mapbenderUuid = $testMatch;
    $testMatch = NULL;
}

if (DEFINED('MAPBENDER_PATH') && MAPBENDER_PATH != '') {
    $baseUrlPortal = str_replace('/mapbender', '', MAPBENDER_PATH);
    $mapbenderBaseUrl = MAPBENDER_PATH . '/';
} else {
    $baseUrlPortal = "https://www.geoportal.rlp.de";
    $mapbenderBaseUrl = "https://www.geoportal.rlp.de/mapbender/";
}
$mapbenderWebserviceUrl = $mapbenderBaseUrl;
//use localhost to invoke search interface - will be much faster
$mapbenderWebserviceUrl = "http://localhost/mapbender/";
//to debug - comment in following
//$mapbenderWebserviceUrl = "https://www.geoportal.rlp.de/mapbender/";
$cache = new Cache();

$cacheVariableName = md5($mapbenderBaseUrl. "ckan_metadata_" . $id);

$actualDate = date("Y-m-d H:i:s");
$maxAgeInSeconds = 3600; //1 hour


if (isset($_REQUEST["cache"]) & $_REQUEST["cache"] != "") {
    //validate
    $testMatch = $_REQUEST["cache"];
    if ($testMatch != 'true' && $testMatch != 'false'){
        echo '{"success": false, "help": "Parameter cache is not valid (true/false)"}';
        die();
    }
    if ($testMatch == 'false') {
        $forceCache = false;
    } else {
        $forceCache = true;
    }
    $testMatch = NULL;
}

if (isset($_REQUEST["orig_identifier"]) & $_REQUEST["orig_identifier"] != "") {
    //validate
    $testMatch = $_REQUEST["orig_identifier"];
    if ($testMatch != 'true' && $testMatch != 'false'){
        echo '{"success": false, "help": "Parameter orig_identifier is not valid (true/false)"}';
        die();
    }
    if ($testMatch == 'false') {
        $orig_identifier = false;
    }
    if ($testMatch == 'true') {
        $orig_identifier = true;
    }
    $testMatch = NULL;
}

if (isset($_REQUEST["restrictToOpenData"]) & $_REQUEST["restrictToOpenData"] != "") {
    //validate
    $testMatch = $_REQUEST["restrictToOpenData"];
    if ($testMatch != 'true' && $testMatch != 'false'){
        echo '{"success": false, "help": "Parameter restrictToOpenData is not valid (true/false)"}';
        die();
    }
    if ($testMatch == 'false') {
        $restrictToOpenData = false;
    }
    if ($testMatch == 'true') {
        $restrictToOpenData = true;
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

function createDistributionElement($rdfXmlDoc, $uri, $title, $description=false, $format, $accessUrl, $originalAccessUrl=false, $license_id, $license_source_note, $format_mapping, $is_hvd) {
    $license_map = array(
        "cc-zero" => "http://dcat-ap.de/def/licenses/cc-zero",
        "dl-de-by-2.0" => "http://dcat-ap.de/def/licenses/dl-by-de/2.0",
        "dl-de-by-nc-1.0" => "http://dcat-ap.de/def/licenses/dl-by-nc-de/1.0",
        "odc-odbl-1.0" => "http://dcat-ap.de/def/licenses/odbl",
        "dl-de-zero-2.0" => "http://dcat-ap.de/def/licenses/dl-zero-de/2.0",
        "cc-by-sa-4.0" => "http://dcat-ap.de/def/licenses/cc-by-sa/4.0",
        "cc-by-3.0" => "http://dcat-ap.de/def/licenses/cc-by-de/3.0",
        "dl-de-by-1.0" => "http://dcat-ap.de/def/licenses/dl-by-de/1.0",
        "cc-nc-3.0" => "http://dcat-ap.de/def/licenses/cc-by-nc-de/3.0",
        "other-closed" => "http://dcat-ap.de/def/licenses/other-closed"
    );
    $Distribution = $rdfXmlDoc->createElement ( "dcat:Distribution" );
    $Distribution->setAttribute ( "rdf:about", $uri);
    
    $distributionTitle = $rdfXmlDoc->createElement ( "dct:title" );
    $distributionTitleText = $rdfXmlDoc->createTextNode( $title );
    $distributionTitle->appendChild($distributionTitleText);
    $Distribution->appendChild($distributionTitle);
    
    if ($description) {
        $distributionDescription = $rdfXmlDoc->createElement ( "dct:description" );
        $distributionDescriptionText = $rdfXmlDoc->createTextNode( $description);
        $distributionDescription->appendChild($distributionDescriptionText);
        $Distribution->appendChild($distributionDescription);
    }
    /*
     * <dct:rights rdf:resource="http://dcat-ap.de/def/licenses/cc-by/4.0"/>
     * <dct:license rdf:resource="http://dcat-ap.de/def/licenses/cc-by/4.0"/>
     * <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2024-03-15T07:06:27.089037</dct:issued>
     * <dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2024-03-14T00:00:00</dct:modified>
     * <dcatde:licenseAttributionByText>© GeoBasis-DE/LVermGeo SH/CC BY 4.0</dcatde:licenseAttributionByText>
     */
    
    if ($license_id && key_exists($license_id, $license_map)) {
        $dctLicense = $rdfXmlDoc->createElement ( "dct:license" );
        $dctLicense->setAttribute('rdf:resource', $license_map[$license_id]);
        $Distribution->appendChild($dctLicense);
        if ($license_source_note && $license_source_note != '') {
            //<dcatde:licenseAttributionByText>© Hanse- und Universitätsstadt Rostock (CC BY 4.0)</dcatde:licenseAttributionByText>
            $dcatdeLicenseAttributionByText = $rdfXmlDoc->createElement ( "dcatde:licenseAttributionByText" );
            $dcatdeLicenseAttributionByTextText = $rdfXmlDoc->createTextNode( $license_source_note);
            $dcatdeLicenseAttributionByText->appendChild($dcatdeLicenseAttributionByTextText);
            $Distribution->appendChild($dcatdeLicenseAttributionByText);
        }
    }
    $distributionFormat = $rdfXmlDoc->createElement ( "dct:format" );
    //$distributionFormatText = $rdfXmlDoc->createTextNode( $format );
    //$distributionFormat->appendChild($distributionFormatText);
    $format_array = json_decode($format_mapping);
    //$e = new mb_exception("format uri: " . $format_array->{$format});
    if ($format_array->{$format} && $format_array->{$format} != "") {
        $distributionFormat->setAttribute ( "rdf:resource", $format_array->{$format});
    }
    $Distribution->appendChild($distributionFormat);
    
    $distributionAccessUrl = $rdfXmlDoc->createElement ( "dcat:accessURL" );
    $distributionAccessUrl->setAttribute ( "rdf:resource", $accessUrl);
    $Distribution->appendChild($distributionAccessUrl);

    if ($originalAccessUrl) {
        $distributionAccessUrl = $rdfXmlDoc->createElement ( "openhessen:accessURL" );
        $distributionAccessUrl->setAttribute ( "rdf:resource", $originalAccessUrl);
        $Distribution->appendChild($distributionAccessUrl);
    }

    if ($is_hvd == true) {
        //<dcatap:applicableLegislation rdf:resource="http://data.europa.eu/eli/reg_impl/2023/138/oj"/>
        $dcatapApplicableLegislation = $rdfXmlDoc->createElement ( "dcatap:applicableLegislation" );
        $dcatapApplicableLegislation->setAttribute ( "rdf:resource", "http://data.europa.eu/eli/reg_impl/2023/138/oj");
        $Distribution->appendChild($dcatapApplicableLegislation);
    }
    return $Distribution;
}

if ($outputFormat == 'rdfxml') {

    if ($forceCache && $cache->isActive && $cache->cachedVariableExists("mapbender:" . $cacheVariableName) && ((date_create($actualDate)->getTimestamp() - date_create(date("Y-m-d H:i:s",$cache->cachedVariableCreationTime("mapbender:" . $cacheVariableName)))->getTimestamp()) < $maxAgeInSeconds)) {
        /*
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: read " . $mapbenderBaseUrl. "ckan_metadata_" . $id . " from ".$cache->cacheType." cache!");
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: " . $cache->cachedVariableExists("mapbender:" . $cacheVariableName));
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: actual timestamp: " . date_create($actualDate)->getTimestamp());
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: variable timestamp: ". date_create(date("Y-m-d H:i:s",$cache->cachedVariableCreationTime("mapbender:" . $cacheVariableName)))->getTimestamp());
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable age: " . (date_create($actualDate)->getTimestamp() - date_create(date("Y-m-d H:i:s",$cache->cachedVariableCreationTime("mapbender:" . $cacheVariableName)))->getTimestamp()) . "seconds");
        */
        //parse result and add origin cache
        $cachedObj = $cache->cachedVariableFetch("mapbender:" . $cacheVariableName);
        header("Content-Type: application/rdf+xml");
        echo $cachedObj;
        die();
    } else {
        /*
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: " . $cache->cachedVariableExists("mapbender:" . $cacheVariableName));
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: actual timestamp: " . date_create($actualDate)->getTimestamp());
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: variable exists: variable timestamp: ". $cache->cachedVariableCreationTime("mapbender:" . $cacheVariableName));
        */
        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: cache variable " . "mapbender:" . $cacheVariableName . " does not exists, is to old or cache was not requested!");
    

        //$forceCache = false;
        //$e = new mb_exception("cache is active: " . $cache->isActive);
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
        $RDF->setAttribute ( "xmlns:dcatap", "http://data.europa.eu/r5r/" );
        $RDF->setAttribute ( "xmlns:dcatde", "http://dcat-ap.de/def/dcatde/" );
        $RDF->setAttribute ( "xmlns:adms", "http://www.w3.org/ns/adms#" );
        $RDF->setAttribute ( "xmlns:skos", "http://www.w3.org/2004/02/skos/core#" );
        $RDF->setAttribute ( "xmlns:openhessen", "http://opendata.hessen.de" );
        //build catalog part
        $catalog = $rdfXmlDoc->createElement ( "dcat:Catalog" );
        $catalog->setAttribute ( "rdf:about", $baseUrlPortal );
        $catalogTitle = $rdfXmlDoc->createElement ( "dct:title" );
        $catalogTitleText = $rdfXmlDoc->createTextNode ( $portalName );
        $catalogTitle->appendChild($catalogTitleText);
        $catalogLanguage = $rdfXmlDoc->createElement ( "dct:language" );
        $catalogLanguage->setAttribute('rdf:resource', 'http://publications.europa.eu/resource/authority/language/DEU');
        //$catalogLanguageText = $rdfXmlDoc->createTextNode ( "de" );
        //$catalogLanguage->appendChild($catalogLanguageText);
        $catalogModified = $rdfXmlDoc->createElement ( "dct:modified" );
        $catalogModified->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime');
        $dt = new DateTime();
        $catalogModifiedText = $rdfXmlDoc->createTextNode ( $dt->format('Y-m-d\TH:i:s.').substr($dt->format('u'),0,3) . 'Z' );
        $catalogModified->appendChild($catalogModifiedText);
        
        //build organization part
        //get organisation list from webservice
        $connector = new connector();   
        if ($mapbenderUuid) {
            $orgaListResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
            //$e = new mb_exception("try to load: " . $mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
            //$e = new mb_exception("result: " . $orgaListResult);
            $orgaListObject = json_decode($orgaListResult);
            $orgaIdArray = array();
            $orgaUuidArray = array();
            foreach ($orgaListObject->organizations as $orga) {
                $orgaIdArray[] = (string)$orga->id;
                $orgaUuidArray[] = (string)$orga->uuid;
            }
            if (!in_array($mapbenderUuid, $orgaUuidArray)) {
                header('Content-Type: application/json');
                echo '{"success": false, "help": "There is no open data organization with requested uuid in the catalogue!"}';
                die();
            } else {
                $key = array_search ($mapbenderUuid, $orgaUuidArray);
                $id = $orgaIdArray[$key];
            }
        } else {
        //load organization list from openDataOrganisations in case of parameter ckanId
        if ($ckanId) {
            $openOrgaListResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOpenDataOrganizations.php?showOnlyDatasetMetadata=true");
            //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: openOrgaListResult: " . $openOrgaListResult);
            $openOrgaListObject = json_decode($openOrgaListResult);
            $openOrgaIdArray = array();
            $openOrgaSerialIdArray = array();
            foreach ($openOrgaListObject as $orga) {
                $openOrgaIdArray[] = (string)$orga->id;
                $openOrgaSerialIdArray[] = (string)$orga->serialId;
            }
            //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: openOrgaIdArray: " . json_encode($openOrgaIdArray));
            //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: ckanId: " . $ckanId);
            if (!in_array($ckanId, $openOrgaIdArray)) {
                header('Content-Type: application/json');
                echo '{"success": false, "help": "There is no open data organization with requested uuid in the catalogue!"}';
                die();
            } else {
                $key = array_search ($ckanId, $openOrgaIdArray);
                $id = $openOrgaSerialIdArray[$key];
            }
        } else {
            $orgaListResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
            //$e = new mb_exception("try to load: " . $mapbenderWebserviceUrl . "php/mod_showOrganizationList.php");
            //$e = new mb_exception("result: " . $orgaListResult);
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
        }
        }
        //get single orga info
        $orgaResult = $connector->load($mapbenderWebserviceUrl . "php/mod_showOrganizationInfo.php?outputFormat=ckan&id=" . $id);
        //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php: organization: " . $orgaResult);
        $orgaObject = json_decode($orgaResult);
        if ($mapbenderUuid) {
            $orgaObject->id = $mapbenderUuid;
        }
        $catalogDescription = $rdfXmlDoc->createElement ( "dct:description" );
        $catalogDescriptionText = $rdfXmlDoc->createTextNode ( "Geo-Metadaten der Organisation " . $orgaObject->title);
        $catalogDescription->appendChild($catalogDescriptionText);
        
        //<dct:publisher rdf:resource="https://daten.rlp.de/organization/7d21ed9f-8013-49a5-9b03-25831f4f9826"/>
        $catalogPublisher = $rdfXmlDoc->createElement ( "dct:publisher" );
        $catalogPublisher->setAttribute('rdf:resource', $baseUrlPortal . "/organization/" . $orgaObject->id);
        
        //append information
        $catalog->appendChild($catalogTitle);
        $catalog->appendChild($catalogDescription);
        $catalog->appendChild($catalogLanguage);
        $catalog->appendChild($catalogModified);
        $catalog->appendChild($catalogPublisher);
        
        /*
        <foaf:Organization rdf:about="https://daten.rlp.de/organization/a7ad2b18-e02c-4492-b244-c2515f697211">
        <foaf:name>
        Landesamt für Vermessung und Geobasisinformationen
        </foaf:name>
        <foaf:mbox>vertrieb-geodienste@vermkv.rlp.de</foaf:mbox>
        </foaf:Organization>
        */
        //create organization entry
        $organization = $rdfXmlDoc->createElement ( "foaf:Organization" );
        $organization->setAttribute ( "rdf:about", $baseUrlPortal . "/organization/" . $orgaObject->id);
        $organizationName = $rdfXmlDoc->createElement ( "foaf:name" );
        $organizationNameText = $rdfXmlDoc->createTextNode( $orgaObject->title );
        $organizationName->appendChild( $organizationNameText );
        $organization->appendChild( $organizationName );  
        $organizationEmail = $rdfXmlDoc->createElement ( "foaf:mbox" );
        $organizationEmailText = $rdfXmlDoc->createTextNode( $orgaObject->department_email );
        $organizationEmail->appendChild( $organizationEmailText );
        $organization->appendChild( $organizationEmail ); 

        $RDF->appendChild ( $catalog );
        //iterate over datasets
        /*
        * 
        */
        $resultsPerPage = 50;
        $mapbenderBaseSearchInterface = $mapbenderWebserviceUrl . "php/mod_callMetadata.php?";
        $orgaId = $id;
        //without open data filter on datasets
        //$baseUrl = $mapbenderBaseSearchInterface . "searchResources=dataset&resolveCoupledResources=true&registratingDepartments=".$orgaId;
        //with open data filter on datasets
        if ($restrictToOpenData) {
            $baseUrl = $mapbenderBaseSearchInterface . "searchResources=dataset&restrictToOpenData=true&resolveCoupledResources=true&registratingDepartments=".$orgaId;
        } else {
            $baseUrl = $mapbenderBaseSearchInterface . "searchResources=dataset&resolveCoupledResources=true&registratingDepartments=".$orgaId;
        }
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
        $e = new mb_exception("number of results: " . $resultObject->dataset->md->nresults);
        $maxPages = ceil($resultObject->dataset->md->nresults / $resultsPerPage);
        /*header('Content-Type: application/json');
        $returnObject = new stdClass();
        $returnObject->help = "helptext";
        $returnObject->success = true;
        $returnObject->result = array();*/
        $j = 0;
        $package = array();
        //$e = new mb_exception("Try to load ".$resultObject->dataset->md->nresults." datasets for ".$orgaResult);
        $distributionArray = array();
        $datasetCount = 0;
        for ($i=1; $i <= $maxPages; $i++) {
            $e = new mb_exception("Use SearchInterface for dataset: Page " . $i . " of ".$maxPages);
            $pageUrl = $baseUrl. "&searchPages=" . $i . "&maxResults=" . $resultsPerPage;
            //echo $pageUrl . "<br>";
            //$e = new mb_exception("search invoked: " . $pageUrl);
            $result = $connector->load($pageUrl);
            $resultObject = json_decode($result);
            foreach($resultObject->dataset->srv as $gpDataset) {
                $e = new mb_notice("Dataset number: ".$datasetCount);
                //$e = new mb_exception("Dataset uuid: ".$gpDataset->uuid);
                $e = new mb_notice("Dataset title: ".$gpDataset->title);
                //extract / generate resource identifier 

                $notEmptyArray = array('uuid', 'title', 'abstract');
                $exportMetadata = true;
                foreach ($notEmptyArray as $mandatoryElement) {
                    if ($gpDataset->{$mandatoryElement} == '' || empty($gpDataset->{$mandatoryElement})) {
                        $exportMetadata = false;
                        $e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php - mandatory element " . $mandatoryElement . " is empty - dataset will not be exported!");
                        break;
                    }
                }
                if ($exportMetadata) {
                    $iso19139Md = new Iso19139();
                    /*
                    * invoke dataset metadata from proxy and get tags/categories/...
                    */
                    $metadataResolverUrl = $mapbenderWebserviceUrl . "php/mod_dataISOMetadata.php?cache=true&outputFormat=iso19139&id=";
                    $metadataUrl = $metadataResolverUrl . $gpDataset->uuid;
                    $iso19139Md->createFromUrl($metadataUrl);
                    $dataset = $rdfXmlDoc->createElement ( "dcat:dataset" );
                    $Dataset = $rdfXmlDoc->createElement ( "dcat:Dataset" );
                    $resourceIdentifier = $iso19139Md->datasetIdCodeSpace . $iso19139Md->datasetId;
                    $Dataset->setAttribute ( "rdf:about", $baseUrlPortal ."/dataset/" . $gpDataset->uuid );
                    $title = $rdfXmlDoc->createElement ( "dct:title" );
                    $titleText = $rdfXmlDoc->createTextNode( $gpDataset->title );
                    $title->appendChild($titleText);
                    $Dataset->appendChild($title);
                    //description
                    $description = $rdfXmlDoc->createElement ( "dct:description" );
                    $descriptionText = $rdfXmlDoc->createTextNode( $gpDataset->abstract );
                    $description->appendChild($descriptionText);
                    $Dataset->appendChild($description);
                    //identifier
                    $identifier = $rdfXmlDoc->createElement ( "dct:identifier" );
                    if ($orig_identifier) {
                        $identifierText = $rdfXmlDoc->createTextNode( $resourceIdentifier );
                    } else {
                        $identifierText = $rdfXmlDoc->createTextNode( $gpDataset->uuid );
                    }
                    $identifier->appendChild($identifierText);
                    $Dataset->appendChild($identifier);
                    //add adms:identifier as demanded in https://www.dcat-ap.de/def/dcatde/2.0/implRules/#example-3-portal-hinzufugen-einer-weiteren-distribution-und-einer-id
                    /*<adms:identifier>
                        <skos:notation>http://data.nordportal.eu/datasets/34567</skos:notation> 
                    </adms:identifier>*/
                    $admsIdentifier = $rdfXmlDoc->createElement ( "adms:identifier" );
                    $skosNotation = $rdfXmlDoc->createElement ( "skos:notation" );
                    $skosNotationText = $rdfXmlDoc->createTextNode( $baseUrlPortal ."/dataset/" . $gpDataset->uuid );
                    $skosNotation->appendChild($skosNotationText);
                    $admsIdentifier->appendChild($skosNotation);
                    $Dataset->appendChild($admsIdentifier);
                    //keywords
                    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php keywords: " . json_encode($iso19139Md->keywords));
                    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php keywordsThesaurusName: " . json_encode($iso19139Md->keywordsThesaurusName));
                    //generate tags
                    $keyword_idx = 0;
                    $is_hvd = false;
                    $dcat_themes_array_unique = array();
                    $hvd_themes_array_unique = array();
                    foreach ($iso19139Md->keywords as $keyword) {
                        //check for keyword without thesaurus
                        $keyword = trim($keyword);
                        if ($iso19139Md->keywordsThesaurusName[$keyword_idx] == null && $keyword != "DummyKeyword") {
                            $keywordE = $rdfXmlDoc->createElement ( "dcat:keyword" );
                            $keywordText = $rdfXmlDoc->createTextNode( $keyword );
                            $keywordE->appendChild($keywordText);
                            $Dataset->appendChild($keywordE);
                        }
                        if ($iso19139Md->keywordsThesaurusName[$keyword_idx] == 'GEMET - INSPIRE themes, version 1.0') {                        
                            $inspire_theme_uri = $inspireCatHash[ $keyword ];  
                            $inspire_dcat_themes_map = json_decode($dcat_category_map);
                            $dcat_themes_array = $inspire_dcat_themes_map->mapping->{end(explode('/', $inspire_theme_uri))};
                            //add dcat theme if exists
                            foreach ($dcat_themes_array as $dcat_theme) {
                                if (!in_array($dcat_theme, $dcat_themes_array_unique)) {
                                    //<dcat:theme rdf:resource="http://publications.europa.eu/resource/authority/data-theme/REGI"/>
                                    $dcatTheme = $rdfXmlDoc->createElement ( "dcat:theme" );
                                    $dcatTheme->setAttribute ( "rdf:resource", "http://publications.europa.eu/resource/authority/data-theme/" . $dcat_theme);
                                    $Dataset->appendChild($dcatTheme);
                                    $dcat_themes_array_unique[] = $dcat_theme;
                                }
                            }
                            //add hvd theme if exists
                            //$inspire_hvd_cat_map
                            $hvd_themes_map = json_decode($inspire_hvd_themes_map);
                            $hvd_themes_array = $hvd_themes_map->mapping->{$inspire_theme_uri};
                            $hvd_mapping_obj = json_decode($hvd_mapping);
                            foreach ($hvd_themes_array as $hvd_theme) {
                                if (!in_array($hvd_theme, $hvd_themes_array_unique)) {
                                    //<dcat:theme rdf:resource="http://publications.europa.eu/resource/authority/data-theme/REGI"/>
                                    $dcatTheme = $rdfXmlDoc->createElement ( "dcatap:hvdCategory" );
                                    //lookup hvd_mapping
                                    $dcatTheme->setAttribute ( "rdf:resource", $hvd_mapping_obj->{$hvd_theme});
                                    $Dataset->appendChild($dcatTheme);
                                    $hvd_themes_array_unique[] = $hvd_theme;
                                }
                                $is_hvd = true;
                            }
                        }
                        $keyword_idx++;
                    }
                    /*<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-08-01T10:46:45.590516</dct:issued>
                    <dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2024-04-02T04:29:37.212449</dct:modified>
                    */
                    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php createDate: " . json_encode($iso19139Md->createDate));
                    //createDate / changeDate
                    $dctCreateDate = $rdfXmlDoc->createElement ( "dct:issued" );
                    $dctCreateDate->setAttribute ( "rdf:datatype", "http://www.w3.org/2001/XMLSchema#date");
                    $dctCreateDateText = $rdfXmlDoc->createTextNode( $iso19139Md->createDate );
                    $dctCreateDate->appendChild($dctCreateDateText);
                    $Dataset->appendChild($dctCreateDate);
                    
                    $dctChangeDate = $rdfXmlDoc->createElement ( "dct:modified" );
                    $dctChangeDate->setAttribute ( "rdf:datatype", "http://www.w3.org/2001/XMLSchema#date");
                    $dctChangeDateText = $rdfXmlDoc->createTextNode( $iso19139Md->changeDate );
                    $dctChangeDate->appendChild($dctChangeDateText);
                    $Dataset->appendChild($dctChangeDate);
                    /*
                    * License information at dataset level
                    */
                    if (isset($gpDataset->license_id) && key_exists($gpDataset->license_id, $license_map)) {
                        $dctLicense = $rdfXmlDoc->createElement ( "dct:license" );
                        $dctLicense->setAttribute('rdf:resource', $license_map[$gpDataset->license_id]);
                        $Dataset->appendChild($dctLicense);
                        //TODO add source_note to search_views!!!!
                    } else {
                        if (is_null($gpDataset->license_id) || ($gpDataset->license_id == false)) {
                            $dctLicense = $rdfXmlDoc->createElement ( "dct:license" );
                            $dctLicense->setAttribute('rdf:resource', $license_map["other-closed"]);
                            $Dataset->appendChild($dctLicense);
                        }
                    }
                    /*
                    * 
                    */
                    if ($is_hvd == true) {
                        //<dcatap:applicableLegislation rdf:resource="http://data.europa.eu/eli/reg_impl/2023/138/oj"/>
                        $dcatapApplicableLegislation = $rdfXmlDoc->createElement ( "dcatap:applicableLegislation" );
                        $dcatapApplicableLegislation->setAttribute ( "rdf:resource", "http://data.europa.eu/eli/reg_impl/2023/138/oj");
                        $Dataset->appendChild($dcatapApplicableLegislation);
                    }
                    //$inspireCatHash
                    /*
                    *
                    */
                    //publisher
                    $publisher = $rdfXmlDoc->createElement ( "dct:publisher" );
                    $publisher->setAttribute ( "rdf:resource", $baseUrlPortal . "/organization/" . $orgaObject->id );
                    $Dataset->appendChild( $publisher );
                    /*
                    * temporal
                    */
                    $dctTemporal = $rdfXmlDoc->createElement ( "dct:temporal" );
                    
                    $dctPeriodOfTime = $rdfXmlDoc->createElement ( "dct:PeriodOfTime" );
                    $dctPeriodOfTime->setAttribute('rdf:nodeID', "N" .md5($iso19139Md->tmpExtentBegin . $iso19139Md->tmpExtentEnd));
                    
                    $dcatStartDate = $rdfXmlDoc->createElement ( "dcat:startDate" );
                    $dcatStartDate->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date');
                    $dcatStartDateText = $rdfXmlDoc->createTextNode($iso19139Md->tmpExtentBegin);
                    $dcatStartDate->appendChild( $dcatStartDateText );
                    $dctPeriodOfTime->appendChild( $dcatStartDate );
                    
                    $dcatEndDate = $rdfXmlDoc->createElement ( "dcat:endDate" );
                    $dcatEndDate->setAttribute('rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date');
                    $dcatEndDateText = $rdfXmlDoc->createTextNode($iso19139Md->tmpExtentEnd);
                    $dcatEndDate->appendChild( $dcatEndDateText );
                    $dctPeriodOfTime->appendChild( $dcatEndDate );
                    
                    $dctTemporal->appendChild( $dctPeriodOfTime );
                    
                    $Dataset->appendChild( $dctTemporal );
                    /*
                    * <dct:temporal>
    <dct:PeriodOfTime rdf:nodeID="N1596db4a49c342d4a2cd7ec2bdf046f8">
    <schema1:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2022-12-05T00:00:00</schema1:startDate>
    </dct:PeriodOfTime>
    </dct:temporal>
    <dct:temporal>
    <dct:PeriodOfTime rdf:nodeID="N0e3395d34d8848cb919392ae23fec76a">
    <dcat:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2022-12-05T00:00:00</dcat:startDate>
    </dct:PeriodOfTime>
    </dct:temporal>
                    */
                    
                    //spatial
                    /*
                    * <dct:spatial>
    <dct:Location rdf:nodeID="N1aaeecd667f2480785404f9eaa1862e0">
    <locn:geometry rdf:datatype="https://www.iana.org/assignments/media-types/application/vnd.geo+json">
    {"type": "Polygon", "coordinates": [[[6.27660990100003, 53.221620321], [9.22712240800007, 53.221620321], [9.22712240800007, 55.3427628060001], [6.27660990100003, 55.3427628060001], [6.27660990100003, 53.221620321]]]}
    </locn:geometry>
    <locn:geometry rdf:datatype="http://www.opengis.net/ont/geosparql#wktLiteral">
    POLYGON ((6.2766 53.2216, 9.2271 53.2216, 9.2271 55.3428, 6.2766 55.3428, 6.2766 53.2216))
    </locn:geometry>
    </dct:Location>
    </dct:spatial>
                    */
                    $geojsonBbox = '{"type": "Polygon", "coordinates": [[[' . $iso19139Md->wgs84Bbox[0] . ', ' . $iso19139Md->wgs84Bbox[1] . '], ';
                    $geojsonBbox .= '[' . $iso19139Md->wgs84Bbox[2] . ', ' . $iso19139Md->wgs84Bbox[1] . '], [' . $iso19139Md->wgs84Bbox[2] . ', ' . $iso19139Md->wgs84Bbox[3] . '], ';
                    $geojsonBbox .= '[' . $iso19139Md->wgs84Bbox[0] . ', ' . $iso19139Md->wgs84Bbox[3] . '], [' . $iso19139Md->wgs84Bbox[0] . ', ' . $iso19139Md->wgs84Bbox[1] . ']]]}';
                    $dctSpatial = $rdfXmlDoc->createElement ( "dct:spatial" );
                    $dctLocation = $rdfXmlDoc->createElement ( "dct:Location" );
                    //add unique identifier - ?
                    $dctLocation->setAttribute("rdf:nodeID", "a" . md5($geojsonBbox . $gpDataset->uuid)); //https://phabricator.wikimedia.org/T252731
                    $locnGeometry = $rdfXmlDoc->createElement ( "locn:geometry" );
                    $locnGeometry->setAttribute("rdf:datatype", "https://www.iana.org/assignments/media-types/application/vnd.geo+json");
                    $locnGeometryText = $rdfXmlDoc->createTextNode( $geojsonBbox );
                    $locnGeometry->appendChild( $locnGeometryText );
                    $dctLocation->appendChild( $locnGeometry );
                    $dctSpatial->appendChild( $dctLocation );
                    $Dataset->appendChild( $dctSpatial );
                    //distribution 1 - original metadata about dataset
                    $distribution = $rdfXmlDoc->createElement ( "dcat:distribution" );
                    $distribution->setAttribute ( "rdf:resource", $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/html_metadata_" .  $gpDataset->uuid);
                    $Dataset->appendChild( $distribution );
                    /*
                    * Get coupled resources and create distributions for them
                    */
                    $resourceArray = array();
                    $resourceIdArray = array();
                    //TODO: add license_source_note ...!
                    foreach ($gpDataset->coupledResources as $key => $value) {
                        switch($key) {
                            case "layer":
                                //TODO add layer title to coupled resource information!!!!
                                foreach ($value as $key1 => $value1) {
                                    $layerArray[] = $value1->id;
                                    $coupledLayerArray[] = $value1->id;
                                    //extract layer title from hierarchy
                                    $layerTitle = $value1->srv->layer[0]->title;
                                    $layerGetCapabilitiesUrl = $value1->srv->originalGetCapabilitiesUrl;
                                    $layerLicenseId = $value1->srv->license_id;
                                    if (is_null($layerLicenseId) || $layerLicenseId == false) {
                                        $layerLicenseId = "other-closed";
                                    }
                                    //build ckan resource records for the layer. For each layer we have metadata, full viewer, geoportal viewer, wms interface
                                    $layerViewResource_1 = array("name" => "Online Karte",
                                        "description" => $layerTitle . " - Vorschau im integrierten Kartenviewer",
                                        "format" => "HTML",
                                        "url" => $mapbenderBaseUrl . "extensions/mobilemap/map.php?layerid=" . $value1->id,
                                        "id" => $gpDataset->uuid . "_mapviewer_layer_" . $value1->id,
                                        "license_id" => $layerLicenseId
                                    );
                                    $resourceArray[] = $layerViewResource_1;
                                    $layerViewResource_2 = array("name" => $portalName,
                                        "description" =>  $layerTitle . " - Anzeige im " . $portalName,
                                        "format" => "HTML",
                                        "url" => $baseUrlPortal . "/map?LAYER[zoom]=1&LAYER[id]=" . $value1->id,
                                        "id" => $gpDataset->uuid . "_geoportal_layer_" . $value1->id,
                                        "license_id" => $layerLicenseId
                                    );
                                    $resourceArray[] = $layerViewResource_2;
                                    $layerMetadataResource = array("name" => "Originäre Metadaten für Kartenebene",
                                        "description" => "Kartenebene: " . $layerTitle . " - Anzeige der originären Metadaten",
                                        "format" => "HTML",
                                        "url" => $mapbenderBaseUrl . "php/mod_showMetadata.php?languageCode=de&resource=layer&layout=tabs&id=" . $value1->id,
                                        "id" => $gpDataset->uuid . "_layer_metadata_" . $value1->id,
                                        "license_id" => "cc-zero"
                                    );
                                    $resourceArray[] = $layerMetadataResource;
                                    //get wms_getcapabilities_url by layer_id

                                    $layerWMSResource = array("name" => "WMS Schnittstelle",
                                        "description" => "Ebene: " . $layerTitle,
                                        "format" => "WMS",
                                        "url" => $mapbenderBaseUrl . "php/wms.php?layer_id=" . $value1->id . "&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS",
                                        "original_url" => str_replace("http://127.0.0.1", $baseUrlPortal, str_replace("http://localhost", $baseUrlPortal, $layerGetCapabilitiesUrl)),
                                        "id" => $gpDataset->uuid . "_wms_interface_" . $value1->id,
                                        "license_id" => $layerLicenseId
                                    );
                                    $resourceArray[] = $layerWMSResource;
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
                                    $inspireAtomFeedsLicenseId = $value1->licenseId;
                                    if (is_null($inspireAtomFeedsLicenseId) || $inspireAtomFeedsLicenseId == false) {
                                        $inspireAtomFeedsLicenseId = "other-closed";
                                    }
                                    switch ($value1->type) {
                                        case "ogcapifeatures":
                                            $featuretypeAccessResource_1 = array("name" => "OGC API Features (REST)",
                                            "description" =>   "Objektart: " . $value1->resourceName. " - ISO19168-1:20202 API",
                                            "format" => "HTML",
                                            "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                            "id" => $gpDataset->uuid . "_ogc_api_interface_" . $value1->resourceName . "_" . $value1->serviceId,
                                            "license_id" => $inspireAtomFeedsLicenseId,
                                            "license_source_note" => $value1->licenseSourceNote
                                            );
                                            $resourceArray[] = $featuretypeAccessResource_1;
                                            break;
                                        case "directwfs":
                                                $featuretypeAccessResource_2 = array("name" => "WFS Schnittstelle",
                                                "description" =>   "Objektart: " . $value1->resourceName. " - WFS",
                                                "format" => "WFS",
                                                "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                                "original_url" => str_replace("http://127.0.0.1", $baseUrlPortal, str_replace("http://localhost", $baseUrlPortal, $value1->originalGetCapabilitiesUrl)),
                                                "id" => $gpDataset->uuid . "_wfs_interface_" . $value1->resourceName . "_" . $value1->serviceId,
                                                "license_id" => $inspireAtomFeedsLicenseId,
                                                "license_source_note" => $value1->licenseSourceNote
                                                );
                                                $resourceArray[] = $featuretypeAccessResource_2;
                                                break; 
                                        case "wfsrequest":
                                            $atomFeedAccessResource_1 = array("name" => "Vektordownload nach EU-Standard",
                                            "description" => $value1->serviceTitle,
                                            "format" => "HTML",
                                            "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                            "id" => $gpDataset->uuid . "_atom_feed_wfs_" . $value1->serviceId,
                                            "license_id" => $inspireAtomFeedsLicenseId,
                                            "license_source_note" => $value1->licenseSourceNote
                                            );
                                            $resourceArray[] = $atomFeedAccessResource_1;
                                            break;
                                        case "wmslayergetmap":
                                            $atomFeedAccessResource_2 = array("name" => "Rasterdownload nach EU-Standard",
                                            "description" => $value1->serviceTitle,
                                            "format" => "HTML",
                                            "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                            "id" => $gpDataset->uuid . "_atom_feed_wms_" . $value1->resourceId,
                                            "license_id" => $inspireAtomFeedsLicenseId,
                                            "license_source_note" => $value1->licenseSourceNote
                                            );
                                            $resourceArray[] = $atomFeedAccessResource_2;
                                            break;
                                        case "remotelist":
                                            $atomFeedAccessResource_3 = array("name" => "Download nach EU-Standard",
                                            "description" => $value1->serviceTitle,
                                            "format" => "HTML",
                                            "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                            "id" => $gpDataset->uuid . "_atom_feed_remotelist_" . $value1->serviceId,
                                            "license_id" => $inspireAtomFeedsLicenseId,
                                            "license_source_note" => $value1->licenseSourceNote
                                            );
                                            $resourceArray[] = $atomFeedAccessResource_3;
                                            break;
                                        case "distribution":
                                            $otherAccessResource_4 = array("name" => "Sonstiger Zugriff",
                                            "description" => $value1->serviceTitle,
                                            "format" => "HTML",
                                            "url" => str_replace($mapbenderWebserviceUrl, $mapbenderBaseUrl, $value1->accessClient),
                                            "id" => $gpDataset->uuid . "_other_distribution_" . md5($value1->accessClient),
                                            "license_id" => $inspireAtomFeedsLicenseId,
                                            "license_source_note" => $value1->licenseSourceNote
                                            );
                                            $resourceArray[] = $otherAccessResource_4;
                                            break;
                                    }
                                    //build ckan resource records for the atomfeed entries -> atomfeed xml, atomfeed html, maybe ogc api features interface
                                }
                                break;//for atom feeds
                        }//end for switch
                        //generate distribution entries
                    }
                    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php - number of resources: for dataset " . $gpDataset->title. ": " . count($resourceArray));
                    //$e = new mb_exception("php/mod_exportMapbenderMetadata2Ckan.php - resources json :" . json_encode($resourceArray));
                    //make them unique - if needed                
                    $resourceArrayNew = array();
                    $resourceIdArrayNew = array();                
                    foreach ($resourceArray as $resource) {
                        /*$e = new mb_exception("php/ - resource id to check: " . md5($resource['id']));
                        $e = new mb_exception("php/ - resource id array: " . json_encode($resourceIdArrayNew));
                        $e = new mb_exception("php/ - type of resourceIdArray: " . gettype($resourceIdArrayNew));*/
                        if (in_array(md5($resource['id']), $resourceIdArrayNew, true)) {
                            //$e = new mb_exception("php/ - resource id is already in array - will not be added again: " . md5($resource['id']));
                        } else {
                            //$e = new mb_exception("php/ - resource id not found in array " . json_encode($resourceIdArrayNew) . " - will be added: " . md5($resource['id']));
                            $resourceArrayNew[] = $resource;
                            $resourceIdArrayNew[] = md5($resource['id']);
                            //$e = new mb_exception("php/ - resource id is already in array - will not be added again: " . $resource['id']);
                        }
                    }
                    //$resourceArray =  $resourceArrayNew;
                    // $e = new mb_exception("php/ - number of resources new: " . count($resourceArrayNew));
                    foreach ($resourceArrayNew as $resource) {
                        //$e = new mb_exception("php/ - resource json: " . json_encode($resource));
                        $distribution = $rdfXmlDoc->createElement ( "dcat:distribution" );
                        //$e = new mb_exception("php/ - resource_id_string: " . $resource->id);
                        //$e = new mb_exception("php/ - resource_id_string 2 : " . $resource['id']);
                        $distribution->setAttribute ( "rdf:resource", $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/" . $resource['id'] );
                        $Dataset->appendChild( $distribution );                   
                    }
                    /*
                    * 
                    */
                    $dataset->appendChild ( $Dataset );
                    $catalog->appendChild ( $dataset );
                    //iterate over distributions
                    
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
                                    
                    $uri = $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/html_metadata_" .  $gpDataset->uuid;
                    $title = "Original Metadaten HTML";
                    $description = false;
                    $format = "HTML";
                    $accessUrl = $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=" . urlencode($mapbenderBaseUrl) . "php%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $gpDataset->uuid;
                    
                    $Distribution = createDistributionElement($rdfXmlDoc, $uri, $title, $description, $format, $accessUrl, false, 'cc-zero', false, $format_mapping, $is_hvd);
                    $distributionArray[] = $Distribution;
                    
                    foreach ($resourceArrayNew as $resource) {
                        if (!key_exists('license_id', $resource)) {
                            $resource['license_id'] = false;
                        }
                        if (!key_exists('license_source_note', $resource)) {
                            $resource['license_source_note'] = false;
                        }
                        if (isset($resource['original_url'])) {
                            $originalAccessUrl = $resource['original_url'];
                        } else {
                            $originalAccessUrl = false;
                        }
                        $Distribution = createDistributionElement($rdfXmlDoc, $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/" . $resource['id'], $resource['name'], $resource['description'], $resource['format'], $resource['url'], $originalAccessUrl, $resource['license_id'], $resource['license_source_note'], $format_mapping, $is_hvd);
                        $distributionArray[] = $Distribution;
                        //$e = new mb_exception("php/ - resource json: " . json_encode($resource));
                        //$distribution = $rdfXmlDoc->createElement ( "dcat:distribution" );
                        //$e = new mb_exception("php/ - resource_id_string: " . $resource->id);
                        //$e = new mb_exception("php/ - resource_id_string 2 : " . $resource['id']);
                        //$resourceUri = $baseUrlPortal . "/dataset/" . $gpDataset->uuid . "/resource/" . $resource['id'];
                        //$distribution->setAttribute ( "rdf:resource", $resourceUri );
                        $Dataset->appendChild( $distribution );
                    }
                    //get resources / distributions
                    /*
                    * 
                    */
                    //$metadataResolverUrl = $mapbenderWebserviceUrl . "php/mod_dataISOMetadata.php?cache=true&outputFormat=iso19139&id=";
                    //$metadataUrl = $metadataResolverUrl . $gpDataset->uuid;
                    //$metadataResult = $connector->load($metadataUrl);
                    //$iso19139Md = new Iso19139();
                    //$e = new mb_exception("Parse ISO Metadata");
                    //$iso19139Md->createFromUrl($metadataUrl);
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
                    $datasetCount++;
                }
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
        if ($cache->isActive) {
            //delete old variable first - cause the timestamp will remain the old!
            if ($cache->cachedVariableExists("mapbender:" . $cacheVariableName)) {

                $cache->cachedVariableDelete("mapbender:" . $cacheVariableName);
                $e = new mb_exception("Delete old xml from cache!");
            }

            $cache->cachedVariableAdd("mapbender:" . $cacheVariableName, $xml, 3600);
            $e = new mb_exception(": Save xml to apc cache: " . "mapbender:" . $cacheVariableName);
        }
        echo $xml;
        die();
    }
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
                                      "url" => $mapbenderBaseUrl . "php/mod_exportIso19139.php?url=" . urlencode($mapbenderBaseUrl) . "php%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D" . $dataset->uuid
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
                            $layerOriginalUrl = $value1->srv->originalGetCapabilitiesUrl;
                            //build ckan resource records for the layer. For each layer we have metadata, full viewer, geoportal viewer, wms interface
                            $layerViewResource_1 = array("name" => "Online Karte",
                                "description" => $layerTitle . " - Vorschau im integrierten Kartenviewer",
                                "format" => "Kartenviewer",
                                "url" => $mapbenderBaseUrl . "extensions/mobilemap/map.php?layerid=" . $value1->id,
                                "id" => $package[$j]->id . "_mapviewer_layer_" . $value1->id
                            );
                            $layerViewResource_2 = array("name" => $portalName,
                                "description" =>  $layerTitle . " - Anzeige im " . $portalName,
                                "format" => $portalName,
                                "url" => $baseUrlPortal . "/map?LAYER[zoom]=1&LAYER[id]=" . $value1->id,
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
                                "original_url" => str_replace("http://127.0.0.1", $baseUrlPortal, str_replace("http://localhost", $baseUrlPortal, $layerOriginalUrl)),
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
                                        //"original_url" => str_replace("http://localhost", $baseUrlPortal, $value1->originalCapabilitiesUrl),
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