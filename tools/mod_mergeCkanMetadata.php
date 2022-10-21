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
//test if old file exists and read number of packages from old file
$oldMergedPackages = file_get_contents($metadataDir . "/" . $fileNameMerged);

$today = date("Y-m-d"); 
//write to merged file
if($h = fopen($metadataDir . "/" . $today . "_" . $fileNameMerged, "w")){
    if(!fwrite($h, json_encode($returnObject, true))){
        echo "Could not write result file to " . $metadataDir . "/" . $today . "_" . $fileNameMerged . "\n";
    }
    fclose($h);
    echo "Ckan metadata for all organisations written to " . $metadataDir . "/" . $today . "_" . $fileNameMerged . "\n";
}

if($oldMergedPackages !== false){
    //try to count packages
    $oldMergedPackagesObject = json_decode($oldMergedPackages);
    $oldNumberOfPackages = count($oldMergedPackagesObject->result);
    //compare
    echo "New number of packages: " . $mergedPackages . " - Previous number of packages: " . $oldNumberOfPackages. "\n";
    if ((((float)$mergedPackages / (float)$oldNumberOfPackages) < 0.9) || (((float)$mergedPackages / (float)$oldNumberOfPackages) > 1.1)) {
        //keep old file
        echo "keep old file - difference more than 10%" . "\n";
    } else {
        //replace old file 
        echo "replace old file - difference less than 10%" . "\n";
        if($h = fopen($metadataDir . "/" . $fileNameMerged, "w")){
            if(!fwrite($h, json_encode($returnObject, true))){
                echo "Could not write result file to " . $metadataDir . "/" . $fileNameMerged . "\n";
            }
            fclose($h);
            echo "Ckan metadata for all organisations written to " . $metadataDir . "/" . $fileNameMerged . "\n";
        }
    }
} else {
    echo "tools/mod_mergeCkanMetadata.php: could not open old merged file!" . "\n";
}


?>