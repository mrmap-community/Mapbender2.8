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

$maxOrgaCount = 300;
$orgaCount = 1;
foreach ($orgaListObject->organizations as $orga) {
    if ($orgaCount > $maxOrgaCount) {
        continue;
    }
    $orgaIdArray[] = (integer)$orga->id;
    //invoke crawler from shell
    //echo (integer)$orga->id . "\n";
    //$cmd = "nohup nice -n 10 php mod_exportCkanMetadataSingleOrgaFile.php orgaId=" . $orga->id . " > /dev/null & echo $!";
    //$pid = exec($cmd);
    //echo "Generator started for organization " . (integer)$orga->id . " with shell pid " . $pid . "\n";
    //wait 2 seconds between invocation of the scripts - otherwise some firewalls and webservers may become problems
    //echo "Wait 4 seconds ;-) \n";
    //sleep(4);
    echo "Begin for orga: " . $orga->id . "\n";
    $scriptString = "php mod_exportCkanMetadataSingleOrgaFile.php orgaId=".$orga->id;
    $result = shell_exec($scriptString);
    echo $scriptString ."\n";
    echo $result . "\n";

    $orgaCount++;
}

echo "Done ;-) \n";


?>
