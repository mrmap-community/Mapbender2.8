<?php
//mod_exportMapbenderLayer2CkanObjects.php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__).'/../../conf/ckan.conf');
$openLicences = OPEN_LICENCES;
//select open data information from mapbenders database
$sql = <<<SQL

select wms_id, layer_id, f_collect_topic_cat_layer(layer_id) as category, f_get_download_options_for_layer(layer_id) as downloadoptions, f_collect_layer_keywords(layer_id) as keywords, layer.uuid as uuid, wms_title || ' - Ebene: ' || layer_title as title, wms_abstract || '\r\n' || layer_abstract as notes, tou_licence_title, tou_licence_url, tou_licence_id, tou_licence_timestamp, isopen, to_timestamp(wms_timestamp) as wms_timestamp, to_timestamp(wms_timestamp_create) as wms_timestamp_create, wms_owner as service_owner,fees,accessconstraints, fkey_mb_group_id as service_group, contactperson, address, city, postcode, contactvoicetelephone, contactelectronicmailaddress from (select termsofuse_id, description as tou_licence_title, descriptionlink as tou_licence_url, wms_tou.name as tou_licence_id, wms_tou.isopen as isopen, wms_tou.timestamp as tou_licence_timestamp, wms_id, wms_title, wms_timestamp, wms_timestamp_create, wms.wms_abstract, wms.wms_owner,fees,accessconstraints,wms.fkey_mb_group_id, contactperson, address, city, postcode, contactvoicetelephone, contactelectronicmailaddress from (select * from termsofuse inner join wms_termsofuse on termsofuse.termsofuse_id = wms_termsofuse.fkey_termsofuse_id and termsofuse.termsofuse_id in ($openLicences)) as wms_tou inner join wms on wms_tou.fkey_wms_id = wms.wms_id) as wms_tou2 inner join layer on wms_tou2.wms_id = layer.fkey_wms_id and layer_searchable=1; 

SQL;
$result = db_query($sql);
//

//initialize result array
$sqlTable = array();
while ($row = db_fetch_array($result)) {
	$sqlTable['name'][] = $row['uuid'];
	$sqlTable['title'][] = $row['title'];
	$sqlTable['service_id'][] = $row['wms_id'];
	$sqlTable['resource_type'][] = "Kartenebene";
	$sqlTable['resource_id'][] = $row['layer_id'];
	$sqlTable['resource_keywords'][] = $row['keywords'];
	$sqlTable['service_group'][] = $row['service_group'];
	$sqlTable['service_owner'][] = $row['service_owner'];
	$sqlTable['service_person'][] = $row['contactperson'];
	$sqlTable['service_address'][] = $row['address'];
	$sqlTable['service_city'][] = $row['city'];
	$sqlTable['service_postcode'][] = $row['postcode'];
	$sqlTable['service_phone'][] = $row['contactvoicetelephone'];
	$sqlTable['service_email'][] = $row['contactelectronicmailaddress'];
	$sqlTable['service_timestamp'][] = $row['wms_timestamp'];
	$sqlTable['notes'][] = $row['notes'];
	$sqlTable['service_fees'][] = $row['fees'];
	$sqlTable['service_accessconstraints'][] = $row['accessconstraints'];
	$sqlTable['tou_licence_title'][] = $row['tou_licence_title'];
	$sqlTable['tou_licence_id'][] = $row['tou_licence_id'];
	$sqlTable['tou_licence_url'][] = $row['tou_licence_url'];
	$sqlTable['tou_licence_timestamp'][] = $row['tou_licence_timestamp'];
	$sqlTable['isopen'][] = $row['isopen'];
	$sqlTable['temporal_coverage_to'][] = $row['wms_timestamp'];
	$sqlTable['temporal_coverage_from'][] = $row['wms_timestamp_create'];
	$sqlTable['downloadoptions'][] = $row['downloadoptions'];
	//build categories array
	if (isset($row['category']) && $row['category'] != '') {
		$categories = explode(",",str_replace("{","",str_replace("}","",str_replace("}{",",",$row['category']))));
		//exchange ids with ckan categories from ckan.conf
		$numberOfCategories = 0;
		for ($i=0; $i < count($categories); $i++){
			if (array_key_exists($categories[$i],$topicCkanCategoryMap)) {
				//check if categories should be exploded
				$newCategories = explode(",",$topicCkanCategoryMap[$categories[$i]]);
				foreach ($newCategories as $cat) {
					//explode categories if 
					$categories[$numberOfCategories] = $cat;
					$numberOfCategories++;
				}
			}
		}
		$e = new mb_notice('mod_exportMapbenderLayer2CkanObjects.php: categories from db: '.$categories[1]);
		$sqlTable['categories'][] = $categories;
	} else {
		$sqlTable['categories'][] = false;
	}
}

$groupOwnerArray = array();
$groupOwnerArray[0] = $sqlTable['service_group'];
$groupOwnerArray[1] = $sqlTable['service_owner'];

//get orga information
$groupOwnerArray = getOrganizationInfoForServices($groupOwnerArray);

//push information from groupOwnerArray to sqlTable
$sqlTable['organization'] = $groupOwnerArray[3];
$sqlTable['userId'] = $groupOwnerArray[2];
$sqlTable['orgaId'] = $groupOwnerArray[11];
$sqlTable['group_title'] = $groupOwnerArray[4];
$sqlTable['group_address'] = $groupOwnerArray[5];
$sqlTable['group_email'] = $groupOwnerArray[6];
$sqlTable['group_telephone'] = $groupOwnerArray[7];
$sqlTable['group_postcode'] = $groupOwnerArray[8];
$sqlTable['group_city'] = $groupOwnerArray[9];
$sqlTable['group_logo'] = $groupOwnerArray[10];
$sqlTable['group_homepage'] = $groupOwnerArray[12];

$sqlTable['group_timestamp'] = $groupOwnerArray[13];



//test output
/*for ($i=0; $i < count($sqlTable['name']); $i++){
		echo $sqlTable['name'][$i]." - ".$sqlTable['title'][$i]." - ".$sqlTable['service_id'][$i]." - ".$sqlTable['resource_id'][$i]." - ".$sqlTable['organization'][$i]." - ".$sqlTable['orgaId'][$i]."<br>";
	}*/
$transpSqlTable = array_transpose($sqlTable);
$ckanPackages = new stdClass();
$ckanPackages->result = array();
//invoke creation of ckan package objects
for ($i=0; $i < count($sqlTable['name']); $i++){
	$ckanPackages->result[] = buildCkanPackage($transpSqlTable[$i]);
}
header('Content-Type: application/json; charset='.CHARSET);
//give out result object in json format
echo json_encode($ckanPackages);
//functions *******************************************************************************************************************************
//build json objects!
function buildCkanPackage ($mbArray) {
	//use urls from mapbender.conf if available!
	if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') {
		$mapbenderUrl = MAPBENDER_PATH;
	} else {
		$mapbenderUrl = "http://".$_SERVER['HTTP_HOST']."/mapbender";
	}
	if (defined("WRAPPER_PATH") && WRAPPER_PATH != '') {
		$wrapperUrl = "http://".$_SERVER['HTTP_HOST'].WRAPPER_PATH;
	} else {
		$wrapperUrl = "http://".$_SERVER['HTTP_HOST']."/portal/karten.html";
	}
	//example package and mapping
	$package->maintainer = $mbArray['group_title']; //mb_group.mb_group_name
	$package->point_of_contact = $mbArray['group_title'];//"Andreas Becker"; //mb_user.mb_user_name - owner
	$package->point_of_contact_free_address = $mbArray['group_address']."\r\n". $mbArray['group_postcode']." ".$mbArray['group_city']."\r\n".$mbArray['group_telephone'];//"Ferdinand-Sauerbruch-Straße 15"; //mb_group.mb_group_address
	//generate timestamp of last change
	//$e = new mb_exception("mod_exportMapbenderLayer2CkanObjects.php: group timestamp:".$mbArray['group_timestamp']);
	//$e = new mb_exception("mod_exportMapbenderLayer2CkanObjects.php: service timestamp:".date("Y-m-d H:i:s",strtotime($mbArray['service_timestamp'])));
	//$e = new mb_exception("mod_exportMapbenderLayer2CkanObjects.php: tou timestamp:".$mbArray['tou_licence_timestamp']);
	$timestamps = array(
		date("Y-m-d H:i:s",$mbArray['group_timestamp']),
		date("Y-m-d H:i:s",strtotime($mbArray['service_timestamp'])),
		date("Y-m-d H:i:s",$mbArray['tou_licence_timestamp'])
	);
	$maxDate = max($timestamps);
	//
	$package->timestamp = $maxDate;
	$package->point_of_contact_email = str_replace("(at)", "@", $mbArray['group_email']);//"poststelle@lvermgeo.rlp"; //mb_group.mb_group_email
	$package->maintainer_email = str_replace("(at)", "@", $mbArray['group_email']);//"poststelle@lvermgeo.rlp"; //mb_group.mb_group_email
	$package->license = $mbArray['tou_licence_title'];//"Datenlizenz Deutschland – Namensnennung – nicht kommerziell"; // termsofuse.description
	$package->author = $mbArray['mb_user_id'];//""; //mb_group.mb_group_name
	$package->url = 'http://www.geoportal.rlp.de';//""; //mb_group.mb_group_name
	$package->download_url = $mapbenderUrl."/php/wms.php?layer_id=".$mbArray['resource_id']."&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";//$mbArray['mb_user_id'];//"http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //
	$package->version = "";//$mbArray['mb_user_id'];//""; //		
	$package->groups = array();
	//for v1/v2 - only $package->groups[0] = "gdi-rp"; //constant
	//$package->groups[0] = CKAN_GROUP_NAME;
	//for v3:
	$package->groups[0]->name = CKAN_GROUP_NAME; //constant
	$package->groups[1]->name = "geo"; //constant
	if ($mbArray['categories']) {
		//add categories as groups
		$numberOfCategories = 2;
		for ($i=0; $i < count($mbArray['categories']); $i++){
			if ($mbArray['categories'][$i] != "geo") {
				$package->groups[$numberOfCategories]->name = $mbArray['categories'][$i];
				$numberOfCategories++;
			}
		}
	}
	if ($mbArray['resource_keywords'] && $mbArray['resource_keywords'] != '') {
		$package->tags = array();
		$keywordArray = explode(',',$mbArray['resource_keywords']);
		for ($i=0; $i < count($keywordArray); $i++){
			$package->tags[$i]->name = $keywordArray[$i];
		}
	}
	$package->name = $mbArray['name'];// $datasetName; //layer.uuid
	$package->notes = $mbArray['notes'];//"Die (Digitale) Topographische Karte 1:100 000 ermöglicht aufgrund der abgebildeten Fläche die Darstellung großräumige Gebiete."; //wms.wms_abstract || layer.layer_abstract
	$package->title = $mbArray['title'];//"Topographische Karte Rheinland-Pfalz 1:100.000"; //wms.wms_title || layer.layer_title - OK
	//TODO: really needed?
	//not needed, if licence is available in ckan instance!
	$package->other_terms_of_use = "Keine Angaben";//$mbArray['mb_user_id'];// "test tou"; //null or ""
	
	//TODO: problem date format
	$package->temporal_coverage_to = substr($mbArray['temporal_coverage_to'],0,10);// "2012-01-01"; // last update ? wms.wms_timestamp - OK

	if ($mbArray['temporal_coverage_from']) {
		$package->temporal_coverage_from = substr($mbArray['temporal_coverage_from'],0,10);// "2011-01-01"; // last update ? wms.wms_timestamp_create - OK
	} else {
		$package->temporal_coverage_from = "2000-01-01";
	}	
	//$package->temporal_coverage_to = "2012-01-01"; // last update ? wms.wms_timestamp - OK
	//$package->temporal_coverage_from = "2011-01-01"; // last update ? wms.wms_timestamp_create - OK

	$package->content_type = $mbArray['resource_type'];// "testcontenttype"; //constant: Service
	if ($mbArray['isopen'] == 1) {
		$package->isopen = true;//true; //termsofuse.isopen - OK
		$package->is_free_of_charge = true;
	} else {
		$package->isopen = false;
	}
	$package->resources = array();

	$package->resources[0]->description = "Anzeige im GeoPortal.rlp";//$mbArray['mb_user_id'];// "Link zur WMS-Darstellung im GeoPortal.rlp, die Darstellung erfolgt ab einem Maßstab 1:500.000"; //fix: "".id.id?
	$package->resources[0]->format = "Kartenviewer"; //constant
	$package->resources[0]->url = $wrapperUrl."?LAYER[zoom]=1&LAYER[id]=".$mbArray['resource_id'];// "http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //constant .. ids
	$package->resources[0]->resource_type = "visualization"; //constant

	$package->resources[1]->description = "WMS Capabilities Link zur Integration in GIS oder Webapplikationen";//$mbArray['mb_user_id'];// "Link zur WMS-Darstellung im GeoPortal.rlp, die Darstellung erfolgt ab einem Maßstab 1:500.000"; //fix: "".id.id?
	$package->resources[1]->format = "WMS"; //constant
	$package->resources[1]->url = $mapbenderUrl."/php/wms.php?layer_id=".$mbArray['resource_id']."&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";// "http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //constant .. ids
	$package->resources[1]->resource_type = "visualization"; //constant

	if ($mbArray['downloadoptions'] != '') {
		$package->resources[2]->description = "GDI Downloadoptionen (EU Standard)";//$mbArray['mb_user_id'];// "Link zur WMS-Darstellung im GeoPortal.rlp, die Darstellung erfolgt ab einem Maßstab 1:500.000"; //fix: "".id.id?
		$package->resources[2]->format = "GDI Download"; //constant
		$package->resources[2]->url = $mapbenderUrl."/php/mod_getDownloadOptions.php?outputFormat=html&id=".str_replace('{','',str_replace('}','',str_replace('}{',',',$mbArray["downloadoptions"])));
		$package->resources[2]->resource_type = "download"; //constant


		$package->resources[3]->description = "Metadaten zur WMS Kartenebene";//$mbArray['mb_user_id'];// "Link zur WMS-Darstellung im GeoPortal.rlp, die Darstellung erfolgt ab einem Maßstab 1:500.000"; //fix: "".id.id?
		$package->resources[3]->format = "HTML"; //constant
		$package->resources[3]->url = $mapbenderUrl."/php/mod_showMetadata.php?languageCode=de&resource=layer&layout=tabs&id=".$mbArray['resource_id'];// "http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //constant .. ids
		$package->resources[3]->resource_type = "metadata"; //constant
	} else {
		$package->resources[2]->description = "Metadaten zur WMS Kartenebene";//$mbArray['mb_user_id'];// "Link zur WMS-Darstellung im GeoPortal.rlp, die Darstellung erfolgt ab einem Maßstab 1:500.000"; //fix: "".id.id?
		$package->resources[2]->format = "HTML"; //constant
		$package->resources[2]->url = $mapbenderUrl."/php/mod_showMetadata.php?languageCode=de&resource=layer&layout=tabs&id=".$mbArray['resource_id'];// "http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //constant .. ids
		$package->resources[2]->resource_type = "metadata"; //constant
	}
	
	$package->author_address->url = $mbArray['mb_user_id'];// null; //null
	$package->author_address->email = str_replace("(at)", "@", $mbArray['mb_user_id']);// ""; // ""
	$package->maintainer_address->url = $mbArray['group_homepage'];//$mbArray['service_address'];// null; //mb_group.mb_group_homepage
	$package->maintainer_address->email = str_replace("(at)", "@", $mbArray['service_email']);// ""; //mb_group.mb_group_email
	$package->maintainer_address->free_address = $mbArray['service_address']."\r\n".$mbArray['service_postcode']." ".$mbArray['service_city'];// null; //mb_group.mb_group_address - more things with \r\n mb_group_voicetelephone, ...
	$package->terms_of_use->license_id = $mbArray['tou_licence_id'];// "ger-name-nc"; //termsofuse.name - OK
	$package->terms_of_use->license_title = $mbArray['tou_licence_title'];// "Datenlizenz Deutschland – Namensnennung – nicht kommerziell"; //termsofuse.description OK
	$package->terms_of_use->license_url = $mbArray['tou_licence_url'];// null; //termsofuse.descriptionlink OK
	//********************************************
	//new for solving schema problems with govdata 2015-10-02
	//********************************************
	//$package->extras->terms_of_use->license_url = $mbArray['tou_licence_url']; //TODO - test why ogdp rlp don't like extras
	$package->maintainer_email = str_replace("(at)", "@", $mbArray['group_email']);//"poststelle@lvermgeo.rlp"; //mb_group.mb_group_email
	$package->url = $wrapperUrl."?LAYER[zoom]=1&LAYER[id]=".$mbArray['resource_id'];// "http://www.geoportal.rlp.de/portal/karten.html?LAYER[zoom]=1&LAYER[id]=36699"; //constant .. ids
	//$package->extras->dates[0]->date = date("Y-m-d",strtotime($mbArray['service_timestamp']))."T00:00:00";//example from Hamburg: "dates": "[{\"date\": \"2014-09-01T00:00:00\", \"role\": \"veroeffentlicht\"}]"
	//$package->extras->dates[0]->role = "veroeffentlicht";//TODO - test why ogdp rlp don't like extras
	//********************************************
	if ($mbArray['service_fees'] != "" || strtoupper($mbArray['service_fees']) != "NONE" || strtoupper($mbArray['service_fees']) != "KEINE") {
		$package->terms_of_use->other = $mbArray['service_fees'];//$mbArray['mb_user_id'];// null; // null?
	} else {
		//$package->terms_of_use->other = "Keine";
	}
	/*
	$package->extras->sector = "Öffentlicher Sektor";
	$package->extras->is_free_of_charge = false;
	$package->extras->tag_thesauri = array();
	$package->extras->tag_sources = array();
	$package->extras->geographical_coverage = "Rheinland-Pfalz";
	$package->extras->geographical_granularity = "Land";
	$package->extras->content_type = "Datensatz";
	$package->extras->used_datasets = array();*/
	return $package;	
}

//other functions
function getOrganizationInfoForServices($groupOwnerArray) {
	//split array into two lists which are requested in two separate sqls
	$listGroupIds = array();
	$listOwnerIds = array();
	//echo "<br>count groupOwnerArray: ".count($groupOwnerArray[0]);
	for ($i=0; $i < count($groupOwnerArray[0]); $i++){
		$key = $i;
		if (!isset($groupOwnerArray[0][$i]) || is_null($groupOwnerArray[0][$i]) || $groupOwnerArray[0][$i] == 0){
			$listOwnerIds[$key] = $groupOwnerArray[1][$i];
		} else {
			$listGroupIds[$key] = $groupOwnerArray[0][$i];
		}
	}
	//for ownerList
	$metadataContactArray = array();
	$metadataContact = array();
	$listGroupIdsKeys =  array_keys($listGroupIds);
	$listOwnerIdsKeys =  array_keys($listOwnerIds);
	$listOwnerIdsString = implode(",",$listOwnerIds);
	$listGroupIdsString = implode(",",$listGroupIds);
	//do the database requests
	if ($listOwnerIdsString != "") {
		$sql = "SELECT mb_group_name as metadatapointofcontactorgname, mb_group_title as metadatapointofcontactorgtitle, mb_group_id, mb_group_logo_path  as metadatapointofcontactorglogo, mb_group_address as metadatapointofcontactorgaddress, mb_group_email as metadatapointofcontactorgemail, mb_group_postcode as metadatapointofcontactorgpostcode, mb_group_city as metadatapointofcontactorgcity, mb_group_voicetelephone as metadatapointofcontactorgtelephone, mb_group_facsimiletelephone as metadatapointofcontactorgfax, a.timestamp as metadatapointofcontactorgtime, mb_group_homepage, b.mb_user_id as mb_user_id, b.timestamp as mb_user_time FROM mb_group AS a, mb_user AS b, mb_user_mb_group AS c WHERE b.mb_user_id IN (".$listOwnerIdsString.") AND b.mb_user_id = c.fkey_mb_user_id AND c.fkey_mb_group_id = a.mb_group_id AND c.mb_user_mb_group_type=2";
		$resultOrgaOwner = db_query($sql);
		$index  = 0;
		while ($row = db_fetch_array($resultOrgaOwner)) {
			//push information into metadataContactArray
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgname'] = $row['metadatapointofcontactorgname'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgtitle'] = $row['metadatapointofcontactorgtitle'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgaddress'] = $row['metadatapointofcontactorgaddress'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgemail'] = $row['metadatapointofcontactorgemail'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgtelephone'] = $row['metadatapointofcontactorgtelephone'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgpostcode'] = $row['metadatapointofcontactorgpostcode'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgcity'] = $row['metadatapointofcontactorgcity'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorglogo'] = $row['metadatapointofcontactorglogo'];
			$metadataContactOwnerArray[$index]['mb_group_homepage'] = $row['mb_group_homepage'];
			$metadataContactOwnerArray[$index]['mb_user_id'] = $row['mb_user_id'];
			$metadataContactOwnerArray[$index]['orga_id'] = $row['mb_group_id'];
			$metadataContactOwnerArray[$index]['metadatapointofcontactorgtime'] = $row['metadatapointofcontactorgtime'];
			//$metadataContactOwnerArray[$index]['mb_user_time'] = $row['mb_user_time'];
			$index++;
		}
		$index = 0;
		//push information directly into $groupOwnerArray at indizes from 
		for ($i=0; $i < count($listOwnerIdsKeys); $i++){
			//find index of user with special id in array $metadataContactOwnerArray['user_id']
			$index = findIndexInMultiDimArray($metadataContactOwnerArray, $listOwnerIds[$listOwnerIdsKeys[$i]], 'mb_user_id'); 
			$groupOwnerArray[2][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['user_id']; //user_id - 2
			$groupOwnerArray[3][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgname']; //orga_name - 3	
			$groupOwnerArray[4][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgtitle']; //title - 4	
			$groupOwnerArray[5][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgaddress']; //address - 5	
			$groupOwnerArray[6][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgemail']; //email - 6	
			$groupOwnerArray[7][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgtelephone']; //telephone - 7	
			$groupOwnerArray[8][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgpostcode']; //postcode - 8	
			$groupOwnerArray[9][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgcity']; //city - 9	
			$groupOwnerArray[10][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorglogo']; //logo - 10	
			$groupOwnerArray[11][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['orga_id'];
			$groupOwnerArray[12][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['mb_group_homepage'];
			
			$groupOwnerArray[13][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['metadatapointofcontactorgtime'];
			//$groupOwnerArray[14][$listOwnerIdsKeys[$i]] = $metadataContactOwnerArray[$index]['mb_user_time'];
		}
	}
	//for groupList
	if ($listGroupIdsString != "") {
		$sql = "SELECT mb_group_name as metadatapointofcontactorgname, mb_group_title as metadatapointofcontactorgtitle, mb_group_id, mb_group_logo_path  as metadatapointofcontactorglogo, mb_group_address as metadatapointofcontactorgaddress, mb_group_email as metadatapointofcontactorgemail, mb_group_postcode as metadatapointofcontactorgpostcode, mb_group_city as metadatapointofcontactorgcity, mb_group_voicetelephone as metadatapointofcontactorgtelephone, mb_group_facsimiletelephone as metadatapointofcontactorgfax, mb_group_homepage, mb_group_id, timestamp as metadatapointofcontactorgtime FROM mb_group WHERE mb_group_id IN (".$listGroupIdsString.")";
		$resultOrgaGroup = db_query($sql);
		$index  = 0;
		while ($row = db_fetch_array($resultOrgaGroup)) {
			//push information into metadataContactArray
			$metadataContactGroupArray[$index]['metadatapointofcontactorgname'] = $row['metadatapointofcontactorgname'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgtitle'] = $row['metadatapointofcontactorgtitle'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgaddress'] = $row['metadatapointofcontactorgaddress'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgemail'] = $row['metadatapointofcontactorgemail'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgtelephone'] = $row['metadatapointofcontactorgtelephone'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgpostcode'] = $row['metadatapointofcontactorgpostcode'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorgcity'] = $row['metadatapointofcontactorgcity'];
			$metadataContactGroupArray[$index]['metadatapointofcontactorglogo'] = $row['metadatapointofcontactorglogo'];
			$metadataContactGroupArray[$index]['mb_group_homepage'] = $row['mb_group_homepage'];
			$metadataContactGroupArray[$index]['mb_group_id'] = $row['mb_group_id'];
			$metadataContactGroupArray[$index]['orga_id'] = $row['mb_group_id'];
			
			$metadataContactGroupArray[$index]['metadatapointofcontactorgtime'] = $row['metadatapointofcontactorgtime'];
			$index++;
		}
		$index = 0;
		//push information directly into $groupOwnerArray at indizes from 
		for ($i=0; $i < count($listGroupIdsKeys); $i++){
			//find index of user with special id in array $metadataContactGroupArray['user_id']
			$index = findIndexInMultiDimArray($metadataContactGroupArray, $listGroupIds[$listGroupIdsKeys[$i]], 'mb_group_id');
			$groupOwnerArray[2][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['mb_group_id']; //user_id - 2
			$groupOwnerArray[3][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgname']; //orga_name - 3	
			$groupOwnerArray[4][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgtitle']; //title - 4	
			$groupOwnerArray[5][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgaddress']; //address - 5	
			$groupOwnerArray[6][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgemail']; //email - 6	
			$groupOwnerArray[7][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgtelephone']; //telephone - 7	
			$groupOwnerArray[8][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgpostcode']; //postcode - 8	
			$groupOwnerArray[9][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgcity']; //city - 9	
			$groupOwnerArray[10][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorglogo']; //logo - 10	

			$groupOwnerArray[11][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['orga_id'];
			$groupOwnerArray[12][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['mb_group_homepage'];

			$groupOwnerArray[13][$listGroupIdsKeys[$i]] = $metadataContactGroupArray[$index]['metadatapointofcontactorgtime'];
		}
	}
	return $groupOwnerArray;
} 

function array_transpose($array, $selectKey = false) {
    if (!is_array($array)) return false;
    $return = array();
    foreach($array as $key => $value) {
        if (!is_array($value)) return $array;
        if ($selectKey) {
            if (isset($value[$selectKey])) $return[] = $value[$selectKey];
        } else {
            foreach ($value as $key2 => $value2) {
                $return[$key2][$key] = $value2;
            }
        }
    }
    return $return;
}
function findIndexInMultiDimArray($multiDimArray, $needle, $columnName) {
    foreach($multiDimArray as $index => $object) {
        if($object[$columnName] == $needle) return $index;
    }
    return FALSE;
}


?>
