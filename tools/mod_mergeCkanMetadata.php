<?php
//should be invoked from cli!
require_once(dirname(__FILE__)."/../core/globalSettings.php");
require_once(dirname(__FILE__)."/../http/classes/class_connector.php");
$returnObject = new stdClass();
$returnObject->help = "helptext";
$returnObject->success = false;
$returnObject->result = array();
$mergedPackages = 0;
$mergedOrganisations = 0;
$mapbenderBaseUrl = "https://www.geoportal.rlp.de/mapbender/";
//get organisation list from webservice
$connector = new connector();
$orgaListResult = $connector->load($mapbenderBaseUrl . "php/mod_showOrganizationList.php");
$orgaListObject = json_decode($orgaListResult);
//$orgaIdArray = array();
//alter METADATA_DIR from mapbender.conf to right relative path
$metadataDir = str_replace("../../", "../", METADATA_DIR);
foreach ($orgaListObject->organizations as $orga) {
    //open file, read packages and copy them into $returnObject
    $fileName = "ckan_metadata_" . $orga->id . ".json";
    if ($h = fopen($metadataDir . "/" . $fileName, "r")) {
        $json = fread($h, filesize($metadataDir . "/" . $fileName));
        $jsonObject = json_decode($json);
        if ($jsonObject != false) {
            echo "SUCCESS: Successfully read json object for organisation: " . $orga->id . "\n";
            if (count($jsonObject->result) > 0) {
                foreach ($jsonObject->result as $package) {
                    $returnObject->result[] = $package;
                    $mergedPackages++;
                }
                $mergedOrganisations++;
            }
        } else {
            echo "ERROR: Json could not be read for organisation: " . $orga->id . "\n";
        }
        fclose($h);
    } else {
        echo "ERROR: No json file found for organisation: " . $orga->id . "\n";
    }
}
$returnObject->success = true;
$returnObject->numberOfPackages = (integer)$mergedPackages;
echo "Merged " . $mergedPackages . " ckan packages - from " . $mergedOrganisations . " organisations!" . "\n";
$fileNameMerged = "ckan_metadata_merged.json";
//write to merged file
if($h = fopen($metadataDir . "/" . $fileNameMerged, "w")){
    if(!fwrite($h, json_encode($returnObject, true))){
        echo "Could not write result file to " . $metadataDir . "/" . $fileNameMerged;
    }
    fclose($h);
    echo "Ckan metadata for all organisations written to " . $metadataDir . "/" . $fileNameMerged . "\n";
}
?>