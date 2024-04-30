<?php
//http://localhost/mapbender/php/mod_importSkosCodelist.php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_connector.php";
require_once dirname(__FILE__) . "/../classes/class_skos.php";
//import skos codelists in mapbender custom_category table
//$skosUrl = "http://inspire.ec.europa.eu/registry/res/custompages/PriorityDataset.rdf";
$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/SpatialScope/SpatialScope.de.rdf";


//$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/IACSData/IACSData.de.rdf";
//$skosUrl = "https://inspire.ec.europa.eu/applicationschema/applicationschema.de.rdf";
//$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/TopicCategory/TopicCategory.de.rdf";

#https://inspire.ec.europa.eu/metadata-codelist/PriorityDataset/PriorityDataset.en.rdf
//$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/PriorityDataset/PriorityDataset.de.rdf";
$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/PriorityDataset/PriorityDataset.de.rdf";
#https://inspire.ec.europa.eu/metadata-codelist/PriorityDataset/PriorityDataset.de.rdf
#$skosUrl = "https://finto.fi/rest/v1/ponduskategorier/data?format=application/rdf%2Bxml";
//$skosUrl = "https://www.geoportal.rlp.de/metadata/SpatialScope.rdf";
//$skosUrl = "https://finto.fi/rest/v1/udcs/data?format=application/rdf%2Bxml";

//$skosUrl = "https://inspire.ec.europa.eu/metadata-codelist/Themes/Themes.de.rdf";//
//single hvd theme
//$skosUrl = "http://publications.europa.eu/resource/authority/bna/c_dd313021";
//hvd themes - linked together
$skosUrl = "http://publications.europa.eu/resource/authority/bna/asd487ae75";



//$skosUrl = "https://inspire.ec.europa.eu/theme/theme.de.rdf";
$skos = new Skos($skosUrl);
$skos->languageCodes = array("en", "de");
$skos->importFromSkosRdf();
$skos->exportSkos();
$e = new mb_exception("skos exported!");
$skos->persistSkosToDb();
/*$test = $skos->getIdentifierArray();
$e = new mb_exception(json_encode($test));
$e = new mb_exception(count($test));*/

?>