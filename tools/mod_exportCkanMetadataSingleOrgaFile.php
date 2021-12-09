<?php
//should be invoked from cli!
require_once(dirname(__FILE__)."/../core/globalSettings.php");
require_once(dirname(__FILE__)."/../http/classes/class_connector.php");

//example: php mod_exportCkanMetadataSingleOrgaFile.php orgaId=1

$arguments = $argv;
array_shift($arguments);
foreach($arguments as $value) {
    $pieces = explode('=',$value);
    if(count($pieces) >= 2) {
        $real_key = $pieces[0];
        array_shift($pieces);
        $real_value = implode('=', $pieces);
        $real_arguments[$real_key] = $real_value;
    }
}
//***************************************************************
//read values
$orgaId = $real_arguments['orgaId'];
//***************************************************************
$mapbenderBaseUrl = "https://www.geoportal.rlp.de/mapbender/";
$mapbenderBaseUrl = "http://127.0.0.1/mapbender/";
//get organisation list from webservice
$connector = new connector();
$generatorUrl = $mapbenderBaseUrl . "php/mod_exportMapbenderMetadata2Ckan.php?cache=false&id=" . $orgaId;
$fileName = "ckan_metadata_" . $orgaId . ".json";

//write file to same tmp folder as iso metadata is written

//alter METADATA_DIR from mapbender.conf to right relative path
$metadataDir = str_replace("../../", "../", METADATA_DIR);

//invoke webservice
$resultJson = $connector->load($generatorUrl);

if($h = fopen($metadataDir . "/" . $fileName, "w")){
    if(!fwrite($h, $resultJson)){
        echo "Could not write result file to " . $metadataDir . "/" . $fileName;
    }
    fclose($h);
    echo "Ckan metadata for organization " . $orgaId . " written to " . $metadataDir . "/" . $fileName;
}
?>
