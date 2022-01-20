<?php
require_once(dirname(__FILE__)."/../core/globalSettings.php");
require_once(dirname(__FILE__)."/../http/classes/class_syncCkan.php");
/*
 * Sync metadata of each coupled organization ;-) - some things copied from https://github.com/mrmap-community/Mapbender2.8/blob/master/http/php/syncCkanRoot.php
 */
$syncCkan = new syncCkan();
// use default admin ckan api key
$syncCkan->ckanApiKey = API_KEY;
$connector = new Connector();

// load list with groups that have open data classified datasets from mapbender catalogue

$url = "http://localhost/mapbender/php/mod_showOpenDataOrganizations.php?showOnlyDatasetMetadata=false";

$openDataOrgsJson = $connector->load($url);
$openDataOrgs = json_decode($openDataOrgsJson);
$numberPackages = 0;
$countOrgas = 0;
foreach($openDataOrgs as $orga) {
    $countOrgas++;
    $numberPackages = $numberPackages + (integer) $orga->package_count;
    logMessages($orga->serialId . " - " . $orga->title . ": " . $orga->package_count);
    //sync single orga by sync class
    $result = syncSingleOrganizationById($orga->serialId);
    logMessages("Sync result: " . $result);
}

logMessages("Found " . $countOrgas . " orgas with " . $numberPackages ." packages in mapbender registry");

function syncSingleOrganizationById( $orgaId ){
    $compareTimestamps = true;
    //get first user which may sync the requested department
    $sql = "SELECT fkey_mb_user_id FROM mb_user_mb_group WHERE fkey_mb_group_id = $1 AND mb_user_mb_group_type IN (2,3) ORDER BY mb_user_mb_group_type DESC LIMIT 1";
    $v = array($orgaId);
    $t = array('i');
    $res = db_prep_query($sql, $v, $t);
    if (!$res || is_null($res) || empty($res)) {        
        $resultObject->error->message = 'No user for publishing department data found!';
        return json_encode($resultObject);
    } else {
        while($row = db_fetch_array($res)){
            $syncUserId = $row['fkey_mb_user_id'];
            //***************************************************************
            $syncCkanClass = new SyncCkan();
            $syncCkanClass->mapbenderUserId = $syncUserId;
            $syncCkanClass->compareTimestamps = $compareTimestamps;
            $departmentsArray = $syncCkanClass->getMapbenderOrganizations();
            //second parameter is listAllMetadataInJson ( = true) - it is needed if we want to sync afterwards. The syncList includes all necessary information about one organization
            $syncListJson = $syncCkanClass->getSyncListJson($departmentsArray, true);
            //logMessages($syncListJson);
            //$syncDepartmentId = (string)$syncDepartmentId;
            $syncCkanClass->syncOrgaId = $orgaId;
            $syncList = json_decode($syncListJson);
            if ($syncList->success = true) {
                foreach ($syncList->result->geoportal_organization as $orga) {
                    //try to sync single orga - the class has already set the syncOrgaId if wished!
                    if ($orgaId == $orga->id) {
                        //overwrite result with result from sync process
                        //$syncList = json_decode($syncCkanClass->syncSingleOrga(json_encode($orga)));
                        // TODO activate later!
                        $syncList = json_decode($syncCkanClass->syncSingleDataSource(json_encode($orga), "mapbender", true));
                    }
                }
            }
        }
        return json_encode($syncList);
    }
}

function logMessages($message) {
    if (php_sapi_name() === 'cli' OR defined('STDIN')) {
        echo __FILE__.": ".$message."\n";
    } else {
        $e = new mb_exception(__FILE__.": ".$message);
    }
}
?>