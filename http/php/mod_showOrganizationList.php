<?php
//server component to pull statistics FROM geoportal catalogue

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
$orderBy = 'rank';
if (isset($_REQUEST["orderBy"]) & $_REQUEST["orderBy"] != "") {
	$testMatch = $_REQUEST["orderBy"];	
 	if (!($testMatch == 'rank' or $testMatch == 'title' or $testMatch == 'id' or $testMatch == 'date')){ 
		//echo 'orderBy: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>orderBy</b> is not valid (rank,title,id,date).<br/>'; 
		die(); 		
 	}
	$orderBy = $testMatch;
	$testMatch = NULL;
}
$sql = <<<SQL
SELECT published_resources.*, datasets+featuretypes+layers+wmcs as all_resources, mb_group_name, mb_group_description, mb_group_logo_path, mb_group_title, mb_group_homepage, uuid FROM (
SELECT SUM(dataset_count) AS datasets, SUM(featuretype_count) AS featuretypes, SUM(layer_count) AS layers, SUM(wmc_count) AS wmcs, mb_group_id FROM (

SELECT 0 AS dataset_count, 0 AS featuretype_count, resource_count AS layer_count, 0 AS wmc_count, mb_group_id FROM (
	SELECT COUNT(layer_id) AS resource_count, fkey_mb_group_id FROM (
		SELECT wms_id, fkey_mb_group_id FROM wms WHERE fkey_mb_group_id <> 0 AND fkey_mb_group_id IS NOT NULL GROUP BY fkey_mb_group_id, wms_id 

		UNION SELECT wms_id, fkey_mb_group_id FROM (
			SELECT wms_owner, wms_id FROM wms WHERE fkey_mb_group_id = 0 OR fkey_mb_group_id is null GROUP BY  wms_owner, wms_id
		) AS owner_wms INNER JOIN mb_user_mb_group on owner_wms.wms_owner = mb_user_mb_group.fkey_mb_user_id WHERE mb_user_mb_group_type = 2
	) AS published_wms INNER JOIN (
		SELECT layer_id, fkey_wms_id FROM layer WHERE layer_searchable = 1
) AS layer on published_wms.wms_id = layer.fkey_wms_id GROUP BY fkey_mb_group_id) AS layer_count INNER JOIN mb_group on mb_group.mb_group_id = layer_count.fkey_mb_group_id WHERE mb_group.searchable = true

UNION

SELECT  0 AS dataset_count, resource_count AS featuretype_count, 0 AS layer_count, 0 AS wmc_count, mb_group_id FROM (
	SELECT COUNT(featuretype_id) AS resource_count, fkey_mb_group_id FROM (
		SELECT wfs_id, fkey_mb_group_id FROM wfs WHERE fkey_mb_group_id <> 0 AND fkey_mb_group_id IS NOT NULL GROUP BY fkey_mb_group_id, wfs_id 

		UNION SELECT wfs_id, fkey_mb_group_id FROM (
			SELECT wfs_owner, wfs_id FROM wfs WHERE fkey_mb_group_id = 0 OR fkey_mb_group_id is null GROUP BY  wfs_owner, wfs_id
		) AS owner_wfs INNER JOIN mb_user_mb_group on owner_wfs.wfs_owner = mb_user_mb_group.fkey_mb_user_id WHERE mb_user_mb_group_type = 2
	) AS published_wfs INNER JOIN (
		SELECT featuretype_id, fkey_wfs_id FROM wfs_featuretype WHERE featuretype_searchable = 1
) AS featuretype on published_wfs.wfs_id = featuretype.fkey_wfs_id GROUP BY fkey_mb_group_id) AS featuretype_count INNER JOIN mb_group on mb_group.mb_group_id = featuretype_count.fkey_mb_group_id WHERE mb_group.searchable = true

UNION

SELECT COUNT(metadata_id) AS dataset_count, 0 AS featuretype_count, 0 AS layer_count, 0 AS wmc_count, mb_group_id FROM (
SELECT metadata_id, fkey_mb_group_id, searchable FROM mb_metadata WHERE fkey_mb_group_id <> 0 AND fkey_mb_group_id IS NOT NULL GROUP BY fkey_mb_group_id, fkey_mb_group_id, metadata_id, searchable  
UNION SELECT metadata_id, fkey_mb_group_id, searchable FROM (
		SELECT fkey_mb_user_id, metadata_id, searchable FROM mb_metadata WHERE fkey_mb_group_id = 0 OR fkey_mb_group_id is null GROUP BY  fkey_mb_user_id, metadata_id
,searchable) AS owner_metadata INNER JOIN mb_user_mb_group on owner_metadata.fkey_mb_user_id = mb_user_mb_group.fkey_mb_user_id WHERE mb_user_mb_group_type = 2

) AS published_metadata INNER JOIN mb_group on mb_group.mb_group_id = published_metadata.fkey_mb_group_id WHERE published_metadata.searchable = true AND mb_group.searchable = true GROUP BY mb_group_id, fkey_mb_group_id

UNION

SELECT 0 AS dataset_count, 0 AS featuretype_count, 0 AS layer_count, COUNT(wmc_serial_id) AS wmc_count, fkey_mb_group_id AS mb_group_id FROM ( SELECT * FROM (
		SELECT fkey_user_id, wmc_serial_id FROM  mb_user_wmc WHERE wmc_public = 1 GROUP BY fkey_user_id, wmc_serial_id
) AS owner_wmc INNER JOIN mb_user_mb_group on owner_wmc.fkey_user_id = mb_user_mb_group.fkey_mb_user_id WHERE mb_user_mb_group_type = 2 ) AS published_wmc GROUP BY fkey_mb_group_id

) AS resources GROUP BY mb_group_id ) AS published_resources INNER JOIN mb_group on published_resources.mb_group_id = mb_group.mb_group_id WHERE mb_group.searchable = true
SQL;

switch ($orderBy) {
	case "rank":
		$sql .= " ORDER BY all_resources DESC";
		break;
	case "id":	
		$sql .= " ORDER BY mb_group_id ASC";
		break;
    case "title":
    default:
		$sql .= " ORDER BY mb_group_name ASC";
		break;
}

//...
$v = array();
$t = array();
$res = db_prep_query($sql, $v, $t);
$jsonOutput = new stdClass();
$jsonOutput->organizations = array();
$numberOfOrgas = 0;
while($row = db_fetch_array($res)){
	$jsonOutput->organizations[$numberOfOrgas]->{'id'} = $row['mb_group_id'];
	$jsonOutput->organizations[$numberOfOrgas]->{'uuid'} = $row['uuid'];
	$jsonOutput->organizations[$numberOfOrgas]->{'name'} = $row['mb_group_name'];
	$jsonOutput->organizations[$numberOfOrgas]->{'description'} = $row['mb_group_description'];
	$jsonOutput->organizations[$numberOfOrgas]->{'title_long'} = $row['mb_group_title'];
	$jsonOutput->organizations[$numberOfOrgas]->{'image_display_url'} = $row['mb_group_logo_path'];
	$jsonOutput->organizations[$numberOfOrgas]->{'detail_url'} = '../mod_showOrganizationInfo.php?id='.$row['mb_group_id'].'&outputFormat=ckan';
	$jsonOutput->organizations[$numberOfOrgas]->{'homepage'} = $row['mb_group_homepage'];
	$jsonOutput->organizations[$numberOfOrgas]->{'datasets'} = $row['datasets'];
	$jsonOutput->organizations[$numberOfOrgas]->{'layers'} = $row['layers'];
	$jsonOutput->organizations[$numberOfOrgas]->{'featuretypes'} = $row['featuretypes'];
	$jsonOutput->organizations[$numberOfOrgas]->{'wmcs'} = $row['wmcs'];
	$jsonOutput->organizations[$numberOfOrgas]->{'all_resources'} = $row['all_resources'];
	$numberOfOrgas++;
}
$json = json_encode($jsonOutput);
header('Content-Type: application/json');
echo $json;


