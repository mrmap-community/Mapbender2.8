<?php
// Display errors for demo
//for later development php 5.6+
//https://github.com/GSA/ckan-php-client
//@ini_set('error_reporting', E_ALL);
//@ini_set('display_errors', 'stdout');	
// Include class_ckanApi.php
require_once(dirname(__FILE__).'/../classes/class_connector.php');
require_once(dirname(__FILE__).'/../classes/class_group.php');
require_once(dirname(__FILE__).'/../classes/class_syncCkan.php');
require_once(dirname(__FILE__) . '/../php/mod_getDownloadOptions.php');
require_once(dirname(__FILE__).'/../../conf/ckan.conf');

//http://localhost/mb_trunk/php/mod_syncCkan_server.php?userId=1&compareTimestamps=true&syncDepartment=25&operation=syncCkan


//TODO: Problem - datestamp for ckan_package - wrong????? Bug in 2.5.3??? Will the index not be updated???
$registratingDepartments = false;
$userId = false;
$outputFormat = "json";
$compareTimestamps = false;
$listAllMetadataInJson = true;
//initiate resultObject to give back as json
$resultObject->success = false;
$operation = false;

$showOnlyDatasetMetadata = "false";
$showOnlyUnlinkedOrganizations = "false";

//parse request parameter
if (isset($_REQUEST["registratingDepartments"]) & $_REQUEST["registratingDepartments"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["registratingDepartments"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'registratingDepartments: <b>'.$testMatch.'</b> is not valid.<br/>';
		$resultObject->error->message = 'Parameter registratingDepartments is not valid (integer or cs integer list).';
		echo json_encode($resultObject);	
		die();
 	}
	$registratingDepartments = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["syncDepartment"]) && $_REQUEST["syncDepartment"] !== "" && $_REQUEST["syncDepartment"] !== null) {
        $testMatch = $_REQUEST["syncDepartment"];
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                $resultObject->error->message = 'Parameter syncDepartment is not valid (integer).';
                echo json_encode($resultObject);
		die();	
        }
        $syncDepartment = (integer)$testMatch;
        $testMatch = NULL;
}

if (isset($_REQUEST["orgaId"]) && $_REQUEST["orgaId"] !== "" && $_REQUEST["orgaId"] !== null) {
        $testMatch = $_REQUEST["orgaId"];
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                $resultObject->error->message = 'Parameter orgaId is not valid (integer).';
                echo json_encode($resultObject);
		die();	
        }
        $orgaId = (integer)$testMatch;
        $testMatch = NULL;
}

if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] !== "" && $_REQUEST["operation"] !== null) {
        $testMatch = $_REQUEST["operation"];
 	if (!($testMatch == 'listCatalogues' or $testMatch == 'syncCatalogue' or $testMatch == 'syncCsw' or $testMatch == 'syncCkan' or $testMatch == 'syncCkanOrganizations')){ 
	 	$resultObject->error->message = 'Parameter operation is not valid (listCatalogues, syncCatalogue, syncCsw, syncCkan, syncCkanOrganizations).'; 
		echo json_encode($resultObject);	
		die();	
 	}
        $operation = $testMatch;
        $testMatch = NULL;
}

if (isset($_REQUEST["userId"]) & $_REQUEST["userId"] != "") {
        $testMatch = $_REQUEST["userId"];
        $pattern = '/^[0-9]*$/';  
        if (!preg_match($pattern,$testMatch)){
                $resultObject->error->message = 'Parameter userId is not valid (integer).';
                echo json_encode($resultObject);
		die();
        }
	if ($testMatch !== Mapbender::session()->get("mb_user_id")) {
		$resultObject->error->message = 'Parameter userId is not equal to the userId from session information - maybe there is no current session!';
		echo json_encode($resultObject);
		die();
	}
        $userId = $testMatch;
        $testMatch = NULL;
} else { 
	$userId = Mapbender::session()->get("mb_user_id");
  	if ($userId == false) {
	  	$userId = PUBLIC_USER;
    	}
}

if (isset($_REQUEST["compareTimestamps"]) & $_REQUEST["compareTimestamps"] != "") {
	$testMatch = $_REQUEST["compareTimestamps"];	
 	if (!($testMatch == 'false' or $testMatch == 'true')){ 
	 	$resultObject->error->message = 'Parameter compareTimestamps is not valid (false, true).'; 
		echo json_encode($resultObject);	
		die();	
 	}
	switch ($testMatch) {
		case "false":
			$compareTimestamps = false;
			break;
		case "true":
			$compareTimestamps = true;
			break;
	}
	$testMatch = NULL;
}

if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'json' or $testMatch == 'debug')){ 
		$resultObject->error->message = 'Parameter outputFormat is not valid (json, debug).'; 
		echo json_encode($resultObject);
		die();	
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}

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

/*
Serverside function of the ckan sync tool for metadata / service providers
return values should work similar to ckans api functions
e.g. 
{
    "help": "Creates a package",
    "success": false,
    "error": {
        "message": "Access denied",
        "__type": "Authorization Error"
        }
}
{
    "help": "Creates a package",
    "success": true,
    "result": {
        "resultObj": "s.th."
        }
}
*/


$syncCkanClass = new SyncCkan();
$syncCkanClass->mapbenderUserId = $userId;
$syncCkanClass->compareTimestamps = $compareTimestamps;

if (isset($syncDepartment)) {
        //write requested orga id to class to prohibit invocation of other orga comparism if one orga should be synced
	$syncCkanClass->syncOrgaId = $syncDepartment;
}

if ($operation == 'syncCkanOrganizations') {
	if ($userId !== "1") {
		echo "You are not root user and therefor not allowed to sync organizations with ckan!";
		die();
	}

	
	$sql = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count(open_published_layers.layer_id) as count_wms_layer, 0 as count_metadata,open_published_layers.group_id, mb_group_name from (select layer_id, group_id, mb_group_name from layer , (select wms_group.count, mb_group_name,group_id, wms_id from mb_group, (select count(wms_id) ,f_get_responsible_organization_for_ressource(wms_id, 'wms') as group_id,wms_id  from wms where wms_id in (select fkey_wms_id from wms_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id, wms_id) as wms_group where mb_group.mb_group_id = wms_group.group_id AND mb_group.export2ckan = TRUE) as open_published_wms where layer.fkey_wms_id = open_published_wms.wms_id and layer_searchable = 1 and export2csw is true ) as open_published_layers left join ows_relation_metadata on open_published_layers.layer_id = ows_relation_metadata.fkey_layer_id where fkey_layer_id is null group by open_published_layers.group_id, open_published_layers.mb_group_name 

union

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

if (isset($orgaId)){
	$sql .= " AND mb_group_id = ".$orgaId."";
} 

	$sql2 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

if (isset($orgaId)){
	$sql2 .= " AND mb_group_id = ".$orgaId."";
} 

	$sql3 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id,mb_group_name from (

select count(open_published_layers.layer_id) as count_wms_layer, 0 as count_metadata,open_published_layers.group_id, mb_group_name from (select layer_id, group_id, mb_group_name from layer , (select wms_group.count, mb_group_name,group_id, wms_id from mb_group, (select count(wms_id) ,f_get_responsible_organization_for_ressource(wms_id, 'wms') as group_id,wms_id  from wms where wms_id in (select fkey_wms_id from wms_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id, wms_id) as wms_group where mb_group.mb_group_id = wms_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS  NULL) as open_published_wms where layer.fkey_wms_id = open_published_wms.wms_id and layer_searchable = 1 and export2csw is true ) as open_published_layers left join ows_relation_metadata on open_published_layers.layer_id = ows_relation_metadata.fkey_layer_id where fkey_layer_id is null group by open_published_layers.group_id, open_published_layers.mb_group_name 

union

select count_wms_layer, metadata_group.count_metadata, group_id,mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS  NULL

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

if (isset($orgaId)){
	$sql3 .= " AND mb_group_id = ".$orgaId."";
} 


	$sql4 = <<<SQL

select opendata.*, mb_group.uuid, mb_group_id, mb_group_title, mb_group_description, mb_group_homepage, mb_group_logo_path, mb_group_address, mb_group_postcode, mb_group_city, mb_group_email, timestamp, mb_group.mb_group_ckan_uuid from mb_group, (select (sum(count_wms_layer)+sum(count_metadata)) as number_of_opendata_ressources, group_id, mb_group_name from (

select count_wms_layer, metadata_group.count_metadata, group_id, mb_group_name from mb_group, (select count(metadata_id) as count_metadata, 0 as count_wms_layer , f_get_responsible_organization_for_ressource(metadata_id, 'metadata') as group_id from mb_metadata where searchable is true and export2csw is true and metadata_id in (select fkey_metadata_id from md_termsofuse where fkey_termsofuse_id in (select termsofuse_id from termsofuse where isopen = 1)) group by group_id) as metadata_group where mb_group.mb_group_id = metadata_group.group_id AND mb_group.export2ckan = TRUE AND mb_group.mb_group_ckan_uuid IS NULL

) as opendata_ressources group by group_id, mb_group_name) as opendata 
where opendata.group_id = mb_group.mb_group_id

SQL;

if (isset($orgaId)){
	$sql4 .= " AND mb_group_id = ".$orgaId."";
} 
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
	//initialize ckanApi
	$syncCkanClass->ckanApiKey = API_KEY;
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
		$orga->updated = $row['timestamp'];
		$returnObject[] = $orga;
		//if ($showOnlyUnlinkedOrganizations == "true") {
			//check if organization exists in connected ckan portal
	 		if (isset($orga->id)) {
				//try to get orga from external ckan and show it
				$requestPost = new stdClass();
				//$requestPost->{'name'} = (string)$ckanOrgaPreferredName;
				//$requestPost->{'organizations'} = array((string)$ckanOrgaPreferredName);
				$requestPost->{'id'} = (string)$orga->id;
				$requestPostJson = json_encode($requestPost);
				//try to read orga
				$ckanResult = $syncCkanClass->getRemoteCkanOrga($requestPostJson);
				$ckanResultObject = json_decode($ckanResult);
				$e = new mb_notice("php/mod_syncCkan_server.php: check for existing orga in external ckan instance: ".$requestPostJson);
				if ($ckanResultObject->success == true) {
					//give back ckan id of organization
					$e = new mb_notice("php/mod_syncCkan_server.php: identical orga found in ckan");
					//get revision_list of organization to get last change date!
					$ckanResultOrgaRevList = $syncCkanClass->getRemoteCkanOrgaRevList($requestPostJson);
//$e = new mb_exception("orga rev list: ".$ckanResultOrgaRevList);
					//extract last timestamp:
					$ckanResultOrgaRevListObject = json_decode($ckanResultOrgaRevList);
					//check for update if needed!
					if ($ckanResultOrgaRevListObject->success == true) {
						$dateTimeCkanOrga = new DateTime($ckanResultOrgaRevListObject->result[0]->timestamp);
					} else {
						$dateTimeCkanOrga = new DateTime($ckanResultObject->result->created);
					}
					//$dateTimeCkanOrga = new DateTime($ckanResultObject->result->created);
                                	$dateTimeMapbenderOrga = new DateTime($orga->updated);
					$e = new mb_notice("php/mod_syncCkan_server.php: compare timestamp of organizations: datetime ckan: ".$dateTimeCkanOrga->format('Y-m-d H:i:s')." - datetime mapbender: ".$dateTimeMapbenderOrga->format('Y-m-d H:i:s'));
    					if (($dateTimeCkanOrga < $dateTimeMapbenderOrga) || $ckanResultObject->result->state == "deleted") {
						$ckanOrgaRepresentation = $syncCkanClass->getInternalOrgaAsCkan($orga->serialId);
						$ckanResult = $syncCkanClass->updateRemoteCkanOrga($ckanOrgaRepresentation);
						$e = new mb_notice("php/mod_syncCkan_server.php: update organization! ".$ckanResult); //id needed!!!!
    					} else {
						$e = new mb_notice("php/mod_syncCkan_server.php: no update of organization required!");
					}
				} else {
					$e = new mb_notice("php/mod_syncCkan_server.php: organization not found in ckan - try to create it!");
    					//try to create it
					//forgotten to initialize a new group, when the uuid is alread available, but 
					$ckanOrgaRepresentation = $syncCkanClass->getInternalOrgaAsCkan($orga->serialId);
//$e = new mb_exception("php/mod_syncCkan_server.php: try to create group id: ".$orga->serialId." ".$ckanOrgaRepresentation);
    					$ckanResult = $syncCkanClass->createRemoteCkanOrga($ckanOrgaRepresentation);
//$e = new mb_exception("php/mod_syncCkan_server.php: result of creation: ". $ckanResult);
    					$ckanResultObject = json_decode($ckanResult);
    					if ($ckanResultObject->success == true) {
						$e = new mb_notice("php/mod_syncCkan_server.php: organization successfully created!");
    					} else {
						$e = new mb_notice("php/mod_syncCkan_server.php: an error occured when trying to create organization via ckan api!");
    					}
				}	
			} else {
				//read ckan representation of internal group from registry - sync all ckan orgas with mapbender!!!!
				$ckanOrgaRepresentation = $syncCkanClass->getInternalOrgaAsCkan($orga->serialId);
				//$e = new mb_exception("php/mod_syncCkan_server.php: returned ckan orga from mapbender: ".$ckanOrgaRepresentation);
				$ckanOrgaRepresentationObject = json_decode($ckanOrgaRepresentation);
				$ckanOrgaPreferredName = $ckanOrgaRepresentationObject->name;
				$requestPost = new stdClass();
				$requestPost->{'id'} = (string)$ckanOrgaPreferredName;
				$requestPostJson = json_encode($requestPost);
				//check if already exists and/or state is "deleted"- than update and set to active
				$ckanResult = $syncCkanClass->getRemoteCkanOrga($requestPostJson);
//$e = new mb_exception("php/mod_syncCkan_server.php: remote ckan organization: ".$ckanResult);
				$ckanResultObject = json_decode($ckanResult);
				if ($ckanResultObject->success == false) {
					$e = new mb_notice("php/mod_syncCkan_server.php: organization not found - try to create it!");
    					//try to create it
    					$ckanResult = $syncCkanClass->createRemoteCkanOrga($ckanOrgaRepresentation);
//$e = new mb_exception("php/mod_syncCkan_server.php: result of creation: ". $ckanResult);
    					$ckanResultObject = json_decode($ckanResult);
    					if ($ckanResultObject->success == true) {
//$e = new mb_exception("php/mod_syncCkan_server.php: organization successfully created!: ");
						//store uuid of external created ckan organization into mapbender database as foreign key
						$sql = "UPDATE mb_group SET mb_group_ckan_uuid = $1 WHERE mb_group_id = $2";
						$v = array($ckanResultObject->result->id, $orga->serialId);		
						$t = array('s', 'i');
						$update_result = db_prep_query($sql,$v,$t);
						if(!$update_result)	{
							throw new Exception("Database error updating mb_group table with ckan uuid attribute!");
							return false;
						}
						//set orga->id for further requests
						$orga->id = $ckanResultObject->result->id;
    					} else {
						$e = new mb_exception("an error occured! ");
    					}
				} else {
					//a organization was found with the requested name - get the id from this organisation and fill it into the mapbender database before updating
					$sql = "UPDATE mb_group SET mb_group_ckan_uuid = $1 WHERE mb_group_id = $2";
					$v = array($ckanResultObject->result->id, $orga->serialId);		
					$t = array('s', 'i');
					$update_result = db_prep_query($sql,$v,$t);
					if(!$update_result)	{
						throw new Exception("Database error updating mb_group table with ckan uuid attribute!");
						return false;
					}
					//set orga->id for further requests
					$orga->id = $ckanResultObject->result->id;
					//add the id to the json object from mapbender database
					$ckanOrgaRepresentationObject->id = $ckanResultObject->result->id;
					$ckanOrgaRepresentation = json_encode($ckanOrgaRepresentationObject);
					$ckanResultOrgaRevList = $syncCkanClass->getRemoteCkanOrgaRevList($requestPostJson);
//$e = new mb_exception("orga rev list: ".$ckanResultOrgaRevList);
					//extract last timestamp:
					$ckanResultOrgaRevListObject = json_decode($ckanResultOrgaRevList);
					//check for update if needed!
					if ($ckanResultOrgaRevListObject->success == true) {
						$dateTimeCkanOrga = new DateTime($ckanResultOrgaRevListObject->result[0]->timestamp);
					} else {
						$dateTimeCkanOrga = new DateTime($ckanResultObject->result->created);
					}
    					//check if timestamp older than from mapbender db or state of organization is deleted
					//compare timestamps
					$dateTimeCkanOrga = new DateTime($ckanResultObject->result->created);
                                	$dateTimeMapbenderOrga = new DateTime($orga->updated);
    					if (($dateTimeCkanOrga < $dateTimeMapbenderOrga) || $ckanResultObject->result->state == "deleted") {
						$ckanResult = $syncCkanClass->updateRemoteCkanOrga($ckanOrgaRepresentation);
						//$e = new mb_exception("update organization ! ".$ckanResult); //id needed!!!!
    					}
				}
			}
			//wether ckan orga was identified or created - check editor account !
			//check if user for editing exists - if not create him automagically
//TODO: add other editor for other template! - data_document_editor_{orga_id}
			$editingUserName = "geoportal_editor_".$orga->serialId;
			$requestPost = new stdClass();
			$requestPost->{'id'} = $editingUserName;
			$requestPostJson = json_encode($requestPost);
			$ckanResultUser = $syncCkanClass->getRemoteCkanUser($requestPostJson);
			$ckanResultUserObject = json_decode($ckanResultUser);	
			if ($ckanResultUserObject->success == true) {
				//check if user has already editor role in organization
			        //update user
				$requestPost = new stdClass();
				//get id from user if already exists - is needed for update since ckan 2.8+!
				$requestPost->{'id'} = $ckanResultUserObject->result->id;
				$requestPost->{'name'} = $editingUserName;
				$requestPost->{'email'} = "kontakt@geoportal.rlp.de";
				$requestPost->{'password'} = "1234".$editingUserName."5678";
				//groups for rlp:(transparenzgesetz,opendata), TODO configure this in ckan.conf
				//$requestPost->groups[0]->name = "transparenzgesetz";
				//$requestPost->groups[1]->name = "opendata";
				$requestPostJson = json_encode($requestPost);
				$ckanResultUser = $syncCkanClass->updateRemoteCkanUser($requestPostJson);
				$ckanResultUserObject = json_decode($ckanResultUser);
			} else {
				//create user
				$requestPost = new stdClass();
				$requestPost->{'name'} = $editingUserName;
				$requestPost->{'email'} = "kontakt@geoportal.rlp.de";
				$requestPost->{'password'} = "1234".$editingUserName."5678";
				$requestPostJson = json_encode($requestPost);
				$ckanResultUser = $syncCkanClass->createRemoteCkanUser($requestPostJson);
//$e = new mb_exception("get user:  ".$ckanResultUser);
				$ckanResultUserObject = json_decode($ckanResultUser);

			}
			//read apikey:
			if ($ckanResultUserObject->success == true) {
				$apiKey = $ckanResultUserObject->result->apikey;
				$userId = $ckanResultUserObject->result->id;
				//store key into mapbender group table
				$sql = "UPDATE mb_group SET mb_group_ckan_api_key = $1 WHERE mb_group_id = $2";
				$v = array($apiKey, $orga->serialId);		
				$t = array('s', 'i');
				$update_result = db_prep_query($sql,$v,$t);
				if(!$update_result) {
					throw new Exception("Database error updating mb_group table with ckan api-key attribute!");
					return false;
				}
			}
			//add user with role editor to current organization
			//get membership if exists
			$requestPost = new stdClass();
			$requestPost->{'id'} = $orga->id;
			$requestPost->{'object_type'} = "user";
			$requestPost->{'capacity'} = "editor";
			$requestPostJson = json_encode($requestPost);
			$ckanResultMember = $syncCkanClass->getRemoteCkanMember($requestPostJson);
//$e = new mb_exception("get member:  ".$ckanResultMember);
			$ckanResultMemberObject = json_decode($ckanResultMember);
//$e = new mb_exception("number of editors:  ".count($ckanResultMemberObject->result));
			if (count($ckanResultMemberObject->result == 0)) {
				//add membership for editor
				$requestPost = new stdClass();
				$requestPost->{'id'} = $orga->id;
				$requestPost->{'object'} = $userId;
				$requestPost->{'object_type'} = "user";
				$requestPost->{'capacity'} = "editor";
				$requestPostJson = json_encode($requestPost);
				$ckanResultMember = $syncCkanClass->createRemoteCkanMember($requestPostJson);
//$e = new mb_exception("get member after creating:  ".$ckanResultMember);
			}
			//add user with role editor to groups "transparenzgesetz" and "opendata"
			//add membership for editor
			if (defined("CKAN_EDITOR_DEFAULT_GROUPS") && is_array(CKAN_EDITOR_DEFAULT_GROUPS)) {
			    foreach(CKAN_EDITOR_DEFAULT_GROUPS as $ckanGroup) {
			$requestPost = new stdClass();
			$requestPost->{'id'} = $ckanGroup;
			$requestPost->{'object'} = $userId;
			$requestPost->{'object_type'} = "user";
			$requestPost->{'capacity'} = "editor";
			$requestPostJson = json_encode($requestPost);
			$ckanResultMember = $syncCkanClass->createRemoteCkanMember($requestPostJson);
//$e = new mb_exception("get member after creating:  ".$ckanResultMember);
			    }
			}		
	}
	header('Content-Type: application/json; charset='.CHARSET);
	echo json_encode($returnObject, JSON_NUMERIC_CHECK);
	die();
}


if ($operation == 'listCatalogues') {
	//get orga id from mapbender group!! - where from?
	$departmentsArray = $syncCkanClass->getMapbenderOrganizations();
	//invoke check for csw
	$result = new stdClass();
	//check for datasets - ckan vs. csw
	$result->result = json_decode($syncCkanClass->getSyncListCswJson($departmentsArray, $listAllMetadataInJson = true));
	$result->success = true;
	header('Content-type:application/json;charset=utf-8');
	echo json_encode($result);
	die();
}

if ($operation == 'syncCkan') {
	//check if user is allowed to sync for requested organisation
	//TODO - make code better
	//overwrite organization from cswId with right orgaId
	$test = $syncCkanClass->syncOrgaId;
	$syncCkanClass->syncOrgaId = $orgaId;
	//$e = new mb_exception($syncCkanClass->syncOrgaId);
	$departmentsArray = $syncCkanClass->getMapbenderOrganizations();
	//rewind orgaid to cswid to get right synclist
	$syncCkanClass->syncOrgaId = $test;
	//TODO: alter csw based sync to give orgaId as parameter as this is done here
	$syncListJsonCkan = $syncCkanClass->getSyncListRemoteCkanJson($departmentsArray, $syncCkanClass->syncOrgaId, true);
	//for synching use right orga id for getting apikey for invoking $syncCkanClass->syncSingleCsw

	if (isset($orgaId) && $orgaId !== null) {
		$syncCkanClass->syncOrgaId = $orgaId;
	}
	$syncList = json_decode($syncListJsonCkan);
	if ($syncList->success = true) {
    		foreach ($syncList->result->external_ckan as $orga) {
        		//TODO try to sync single orga - the class has already set the syncOrgaId if wished!
			//if ($syncDepartment == $orga->id) {
            			//overwrite result with result from sync process
            			//$syncList = json_decode($syncCkanClass->syncSingleCsw(json_encode($orga)));
				//new function
//$e = new mb_exception($syncListJsonCkan);
				$syncList = json_decode($syncCkanClass->syncSingleDataSource(json_encode($orga), "ckan"));
			//}
    		}
	}
	//create new syncListJson
	$syncListJson = json_encode($syncList);
	header('Content-type:application/json;charset=utf-8');
	echo $syncListJson;
	die();
}

if ($operation == 'syncCsw') {
	//check if user is allowed to sync for requested organisation
	//TODO - make code better
	//overwrite organization from cswId with right orgaId
	$test = $syncCkanClass->syncOrgaId;
	$syncCkanClass->syncOrgaId = $orgaId;
	//$e = new mb_exception($syncCkanClass->syncOrgaId);
	$departmentsArray = $syncCkanClass->getMapbenderOrganizations();
	//rewind orgaid to cswid to get right synclist
	$syncCkanClass->syncOrgaId = $test;
	$syncListJsonCsw = $syncCkanClass->getSyncListCswJson($departmentsArray, $syncCkanClass->syncOrgaId, true);
	//for synching use right orga id for getting apikey for invoking $syncCkanClass->syncSingleCsw
	$syncCkanClass->syncOrgaId = $orgaId;
	$syncList = json_decode($syncListJsonCsw);
	if ($syncList->success = true) {
    		foreach ($syncList->result->external_csw as $orga) {
        		//TODO try to sync single orga - the class has already set the syncOrgaId if wished!
			//if ($syncDepartment == $orga->id) {
            			//overwrite result with result from sync process
            			//$syncList = json_decode($syncCkanClass->syncSingleCsw(json_encode($orga)));
				//new function
				
				$syncList = json_decode($syncCkanClass->syncSingleDataSource(json_encode($orga), "portalucsw"));
			//}
    		}
	}
	//create new syncListJson
	$syncListJson = json_encode($syncList);
	header('Content-type:application/json;charset=utf-8');
	echo $syncListJson;
	die();
}

$departmentsArray = $syncCkanClass->getMapbenderOrganizations();

//second parameter is listAllMetadataInJson ( = true) - it is needed if we want to sync afterwards. The syncList includes all necessary information about one organization

$syncListJson = $syncCkanClass->getSyncListJson($departmentsArray, true);
$syncList = json_decode($syncListJson);
if ($syncList->success = true) {
    foreach ($syncList->result->geoportal_organization as $orga) {
        //try to sync single orga - the class has already set the syncOrgaId if wished!
	if ($syncDepartment == $orga->id) {
            //overwrite result with result from sync process
            //$syncList = json_decode($syncCkanClass->syncSingleOrga(json_encode($orga)));
	    $syncList = json_decode($syncCkanClass->syncSingleDataSource(json_encode($orga), "mapbender", true));
	}
    }
}
//create new syncListJson
$syncListJson = json_encode($syncList);
header('Content-type:application/json;charset=utf-8');
echo $syncListJson;

?>
