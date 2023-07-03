<?php 
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License
# and Simplified BSD license.
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_administration.php";
/*
 * Simple webservice to resolve spatial_dataset_identifiers for layer_ids from the mapbender registry
 */
$admin = new administration();
if (isset($_REQUEST["layerIds"]) & $_REQUEST["layerIds"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["layerIds"];
    $pattern = '/^[\d,]*$/';
    if (!preg_match($pattern,$testMatch)){
        //echo 'resourceIds: <b>'.$testMatch.'</b> is not valid.<br/>';
        echo 'Parameter <b>layerIds</b> is not valid (integer or cs integer list).<br/>';
        die();
    }
    $layerIds = $testMatch;
    $testMatch = NULL;
}
//transform to array
$layerIdsArray = explode(',' ,$layerIds);
$v = array();
$t = array();
$sql = "SELECT metadata_id, uuid, datasetid, datasetid_codespace from mb_metadata WHERE ";
$sql .= "metadata_id IN (SELECT fkey_metadata_id FROM ows_relation_metadata WHERE fkey_layer_id IN ";
$sql .= "(";
for($i=0; $i<count($layerIdsArray); $i++){
    if($i>0){ $sql .= ",";}
    $sql .= "$".strval($i+1);
    array_push($v, $layerIdsArray[$i]);
    array_push($t, "i");
}
$sql .= ")) AND searchable IS TRUE";
$res = db_prep_query($sql, $v, $t);
$datasetIdentifier = array();
while($row = db_fetch_array($res)){
    $orgaInfo = $admin->getOrgaInfoFromRegistry('metadata', $row['metadata_id'], 0);
    $codespace = $admin->getIdentifierCodespaceFromRegistry($orgaInfo, $row);
    if ($row['datasetid'] != '') {
        $datasetIdentifier[] = $codespace . $row['datasetid'];
    } else {
        $datasetIdentifier[] = $codespace . $row['uuid'];
    }
}
$datasetIdentifier = array_unique($datasetIdentifier);
header('Content-Type: application/json');
echo json_encode($datasetIdentifier);
?>