<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
$showOnlyDatasetMetadata = "false";
$showOnlyUnlinkedOrganizations = "false";
//params: opendata licenses, only metadata, ....

if (isset($_REQUEST["showOnlyDatasetMetadata"]) & $_REQUEST["showOnlyDatasetMetadata"] != "") {
	$testMatch = $_REQUEST["showOnlyDatasetMetadata"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		echo 'Parameter <b>showOnlyDatasetMetadata</b> is not valid (true,false (default to false)).<br/>'; 
		die(); 		
 	}
	switch ($testMatch) {
		case "true":
			$showOnlyDatasetMetadata = "true";
		break;
		case "false":
			$showOnlyDatasetMetadata = "false";
		break;	
	}
	$testMatch = NULL;
}
if (isset($_REQUEST["showOnlyUnlinkedOrganizations"]) & $_REQUEST["showOnlyUnlinkedOrganizations"] != "") {
	$testMatch = $_REQUEST["showOnlyUnlinkedOrganizations"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		echo 'Parameter <b>showOnlyUnlinkedOrganizations</b> is not valid (true,false (default to false)).<br/>'; 
		die(); 		
 	}
	switch ($testMatch) {
		case "true":
			$showOnlyUnlinkedOrganizations = "true";
		break;
		case "false":
			$showOnlyUnlinkedOrganizations = "false";
		break;	
	}
	$testMatch = NULL;
}




$sql = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count(open_published_layers.layer_id) as count_wms_layer, 0 as count_metadata,open_published_layers.group_id, mb_group_name from (select layer_id, group_id, mb_group_name from layer , (select wms_group.count, mb_group_name,group_id, wms_id from mb_group, (select count(wms_id) ,f_get_responsible_organization_for_ressource(wms_id, 'wms') as group_id,wms_id  from wms where wms_id in (select fkey_wms_id from wms_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id, wms_id) as wms_group where mb_group.mb_group_id = wms_group.group_id AND mb_group.export2ckan = TRUE) as open_published_wms where layer.fkey_wms_id = open_published_wms.wms_id and layer_searchable = 1 and export2csw is true ) as open_published_layers left join ows_relation_metadata on open_published_layers.layer_id = ows_relation_metadata.fkey_layer_id where fkey_layer_id is null group by open_published_layers.group_id, open_published_layers.mb_group_name 

union

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;


$sql2 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

$sql3 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count(open_published_layers.layer_id) as count_wms_layer, 0 as count_metadata,open_published_layers.group_id, mb_group_name from (select layer_id, group_id, mb_group_name from layer , (select wms_group.count, mb_group_name,group_id, wms_id from mb_group, (select count(wms_id) ,f_get_responsible_organization_for_ressource(wms_id, 'wms') as group_id,wms_id  from wms where wms_id in (select fkey_wms_id from wms_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id, wms_id) as wms_group where mb_group.mb_group_id = wms_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS  NULL) as open_published_wms where layer.fkey_wms_id = open_published_wms.wms_id and layer_searchable = 1 and export2csw is true ) as open_published_layers left join ows_relation_metadata on open_published_layers.layer_id = ows_relation_metadata.fkey_layer_id where fkey_layer_id is null group by open_published_layers.group_id, open_published_layers.mb_group_name 

union

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS  NULL

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;


$sql4 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id, mb_group_name from (

select count_wms_layer, metadata_group.count_metadata, group_id, mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS NULL

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

switch ($showOnlyDatasetMetadata) {
	case "true":
		if ($showOnlyUnlinkedOrganizations == "true") {
			$result = db_query($sql4);
		} else {
			$result = db_query($sql2);
		}
		break;
	case "false":
		if ($showOnlyUnlinkedOrganizations == "true") {
			$result = db_query($sql3);
		} else {
			$result = db_query($sql);
		}
		break;
}

$returnObject = array();

while ($row = db_fetch_array($result)) {
        unset($orga);
	$orga->serialId = $row['mb_group_id'];
	$orga->id = $row['mb_group_ckan_uuid'];
	$orga->department_address = $row['mb_group_address'];
	$orga->department_city = $row['mb_group_city'];
	$orga->department_postcode = $row['mb_group_postcode'];
	$orga->department_email = $row['mb_group_email'];
	$orga->title = $row['mb_group_title'];
	$orga->description = $row['mb_group_description'];
	$orga->image_url = $row['mb_group_logo_path'];
	$orga->image_display_url = $row['mb_group_logo_path'];
	$orga->is_organization = true;
	$orga->display_name = $row['mb_group_title'];
	$orga->state = "active";
	$orga->type = "organization";
	$orga->approval_status = "approved";
	$orga->package_count = $row['number_of_opendata_ressources'];
	$returnObject[] = $orga;
}

header('Content-Type: application/json; charset='.CHARSET);
echo json_encode($returnObject, JSON_NUMERIC_CHECK);

?>
