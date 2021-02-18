<?php
//should be invoked from cli!
require_once(dirname(__FILE__)."/../core/globalSettings.php");
require_once(dirname(__FILE__)."/../http/classes/class_connector.php");

$mapbenderBaseUrl = "https://www.geoportal.rlp.de/mapbender/";

//get organisation list from webservice
$connector = new connector();

$orgaListResult = $connector->load($mapbenderBaseUrl . "php/mod_showOrganizationList.php");

$orgaListObject = json_decode($orgaListResult);
$orgaIdArray = array();

$maxOrgaCount = 2;
$orgaCount = 1;
foreach ($orgaListObject->organizations as $orga) {
    if ($orgaCount > $maxOrgaCount) {
        continue;
    }
    $orgaIdArray[] = (integer)$orga->id;
    //invoke crawler from shell
    //echo (integer)$orga->id . "\n";
    $cmd = "nohup nice -n 10 php mod_exportCkanMetadataSingleOrgaFile.php orgaId=" . $orga->id . " > /dev/null &";
    $pid = exec($cmd);
    echo "Generator started for organization " . (integer)$orga->id . " with shell pid " . $pid . "\n";
    $orgaCount++;
}

echo "Done ;-) \n";


?>