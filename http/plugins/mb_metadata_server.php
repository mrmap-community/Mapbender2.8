<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_user.php";
require_once dirname(__FILE__) . "/../classes/class_wms.php";//already includes iso19139!
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";
require_once dirname(__FILE__) . "/../classes/class_wfs.php";
require_once dirname(__FILE__) . "/../classes/class_administration.php";
require_once(dirname(__FILE__)."/../classes/class_universal_wfs_factory.php");
require_once dirname(__FILE__) . "/../../tools/wms_extent/extent_service.conf";

$ajaxResponse = new AjaxResponse($_POST);

function abort ($message) {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die();
}

function DOMNodeListObjectValuesToArray($domNodeList) {
	$iterator = 0;
	$array = array();
	foreach ($domNodeList as $item) {
    		$array[$iterator] = $item->nodeValue; // this is a DOMNode instance
    		// you might want to have the textContent of them like this
    		$iterator++;
	}
	return $array;
}

function getWms ($wmsId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$wmsIdArray = $user->getOwnedWms();

	if (!is_null($wmsId) && !in_array($wmsId, $wmsIdArray)) {
		abort(_mb("You are not allowed to access this WMS."));
	}
	return $wmsIdArray;
}

function getWfs ($wfsId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$wfsIdArray = $user->getOwnedWfs();

	if (!is_null($wfsId) && !in_array($wfsId, $wfsIdArray)) {
		abort(_mb("You are not allowed to access this WFS."));
	}
	return $wfsIdArray;
}

function getLayer ($layerId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$wmsIdArray = $user->getOwnedWms();
	if (!is_array($wmsIdArray) || count($wmsIdArray) === 0) {
		abort(_mb("No metadata sets available."));
	}
	$wmsId = wms::getWmsIdByLayerId($layerId);
	if (is_null($wmsId) || !in_array($wmsId, $wmsIdArray)) {
		abort(_mb("You are not allowed to access WMS " . $wmsId));
	}
	return;
}

function getFeaturetype ($featuretypeId = null) {
	$user = new User(Mapbender::session()->get("mb_user_id"));
	$wfsIdArray = $user->getOwnedWfs();
	if (!is_array($wfsIdArray) || count($wfsIdArray) === 0) {
		abort(_mb("No metadata sets available."));
	}
	$wfsId = wfs::getWfsIdByFeaturetypeId($featuretypeId);
	if (is_null($wfsId) || !in_array($wfsId, $wfsIdArray)) {
		abort(_mb("You are not allowed to access this WFS " . $wfsId));
	}
	return;
}

//NOTE: independend
function extractPolygonArray($domXpath, $path) {
	$polygonalExtentExterior = array();
	if ($domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList')) {
		//read posList
		$exteriorRingPoints = $domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList');
		$exteriorRingPoints = DOMNodeListObjectValuesToArray($exteriorRingPoints);
		if (count($exteriorRingPoints) > 0) {
			//poslist is only space separated
			$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
			for ($i = 0; $i <= count($exteriorRingPointsArray)/2-1; $i++) {
				$polygonalExtentExterior[$i]['x'] = $exteriorRingPointsArray[2*$i];
				$polygonalExtentExterior[$i]['y'] = $exteriorRingPointsArray[(2*$i)+1];
			}
		}
	} else {
		//try to read coordinates
		$exteriorRingPoints = $domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:coordinates');
		$exteriorRingPoints = DOMNodeListObjectValuesToArray($exteriorRingPoints);
		if (count($exteriorRingPoints) > 0) {
			//two coordinates of one point are comma separated
			//problematic= ", " or " ," have to be deleted before
			$exteriorRingPoints[0] = str_replace(', ',',',str_replace(' ,',',',$exteriorRingPoints[0]));
			$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
			for ($i = 0; $i <= count($exteriorRingPointsArray)-1;$i++) {
				$coords = explode(",",$exteriorRingPointsArray[$i]);
				$polygonalExtentExterior[$i]['x'] = $coords[0];
				$polygonalExtentExterior[$i]['y'] = $coords[1];
			}
		}
	}
	return $polygonalExtentExterior;
}
//NOTE: independend
function gml2wkt($gml) {
	//function to create wkt from given gml multipolygon
	//DOM
	$polygonalExtentExterior = array();
	$gmlObject = new DOMDocument();
	libxml_use_internal_errors(true);
	try {
		$gmlObject->loadXML($gml);
		if ($gmlObject === false) {
			foreach(libxml_get_errors() as $error) {
        			$err = new mb_exception("mb_metadata_server.php:".$error->message);
    			}
			throw new Exception("mb_metadata_server.php:".'Cannot parse GML!');
			return false;
		}
	}
	catch (Exception $e) {
    		$err = new mb_exception("mb_metadata_server.php:".$e->getMessage());
		return false;
	}
	//if parsing was successful
	if ($gmlObject !== false) {
		//read crs from gml
		$xpath = new DOMXPath($gmlObject);
		$xpath->registerNamespace('gml','http://www.opengis.net/gml');
		$MultiSurface = $xpath->query('/gml:MultiSurface');
		if ($MultiSurface->length == 1) { //test for DOM!
			$crs = $xpath->query('/gml:MultiSurface/@srsName');
			$crsArray = DOMNodeListObjectValuesToArray($crs);
			$crsId = end(explode(":",$crsArray[0]));
			//count surfaceMembers
			$numberOfSurfaces = count(DOMNodeListObjectValuesToArray($xpath->query('/gml:MultiSurface/gml:surfaceMember')));
			for ($k = 0; $k < $numberOfSurfaces; $k++) {
				$polygonalExtentExterior[] = extractPolygonArray($xpath, '/gml:MultiSurface/gml:surfaceMember['. (string)($k + 1) .']');
			}
		} else {
			$polygonalExtentExterior[0] = extractPolygonArray($xpath, '/');
		}
		$crs = $xpath->query('/gml:Polygon/@srsName');
		$crsArray = DOMNodeListObjectValuesToArray($crs);
		$crsId = end(explode(":",$crsArray[0]));
		if (!isset($crsId) || $crsId =="" || $crsId == NULL) {
			//set default to lonlat wgs84
			$crsId = "4326";
		}
		$mbMetadata = new Iso19139();
		$wkt = $mbMetadata->createWktPolygonFromPointArray($polygonalExtentExterior);
		return $wkt;
	}
}
//routines to do the ajax server side things
switch ($ajaxResponse->getMethod()) {
	case "getWms" :
		$wmsIdArray = getWms();
		$wmsList = implode(",", $wmsIdArray);
		$sql = <<<SQL
SELECT wms.wms_id, wms.wms_title, to_timestamp(wms.wms_timestamp),to_timestamp(wms.wms_timestamp_create), wms_version, m.status_comment, wms_id
FROM wms LEFT JOIN mb_wms_availability AS m
ON wms.wms_id = m.fkey_wms_id
WHERE wms_id IN ($wmsList);
SQL;
		$res = db_query($sql);
		$resultObj = array(
			"header" => array(
				_mb("WMS ID"),
				_mb("title"),
				_mb("last change"),
				_mb("creation"),
				_mb("version"),
				_mb("status"),
				_mb("wms id")
			),
			"data" => array()
		);
		while ($row = db_fetch_row($res)) {
			// convert NULL to '', NULL values cause datatables to crash
			$walk = array_walk($row, create_function('&$s', '$s=strval($s);'));
			$resultObj["data"][]= $row;
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getWfs" :
		$wfsIdArray = getWfs();
		$wfsList = implode(",", $wfsIdArray);
		$sql = <<<SQL
SELECT wfs.wfs_id, wfs.wfs_title, wfs.wfs_timestamp, wfs_version
FROM wfs WHERE wfs_id IN ($wfsList);
SQL;
		$res = db_query($sql);
		$resultObj = array(
			"header" => array(
				"WFS ID",
				"Titel",
				"Timestamp",
				"Version"
			), 
			"data" => array()
		);
		while ($row = db_fetch_row($res)) {
			// convert NULL to '', NULL values cause datatables to crash
			$walk = array_walk($row, create_function('&$s', '$s=strval($s);'));
			$resultObj["data"][]= $row;
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getMapviewerUrl" :
		$mapviewer_id = $ajaxResponse->getParameter("mapviewerId");
		$gui_id = $ajaxResponse->getParameter("guiId");
		$wmc_id = $ajaxResponse->getParameter("wmcId");
		//default gui_id and wmc_id to false! - string or boolean?
		$admin = new administration();
		$resultObj['mapviewer_url'] = $admin->getMapviewerInvokeUrl($mapviewer_id, $gui_id, $wmc_id);
		if ($resultObj['mapviewer_url'] == false) {
		    $ajaxResponse->setMessage(_mb("An unknown error occured when trying to generate mapviewer url!"));
		    $ajaxResponse->setSuccess(false);
		} else {
		    $ajaxResponse->setResult($resultObj);
		    $ajaxResponse->setSuccess(true);
		}
		break;
	case "getWmsMetadata" :
		$wmsId = $ajaxResponse->getParameter("id");
		getWms($wmsId);

		$wms = new wms();
		$wms->createObjFromDBNoGui($wmsId);//here the owsproxyurls will be read out - to make previews with proxy urls

		$fields = array(
			"wms_id",
			"wms_abstract",
			"wms_title",
		    "wms_alternate_title",
			"fees",
			"accessconstraints",
			"contactperson",
			"contactposition",
			"contactvoicetelephone",
			"contactfacsimiletelephone",
			"contactorganization",
			"address",
			"city",
			"stateorprovince",
			"postcode",
			"country",
			"contactelectronicmailaddress",
			"wms_timestamp",
			"wms_timestamp_create",
			"wms_network_access",
			"wms_max_imagesize",
			"fkey_mb_group_id",
			"inspire_annual_requests",
			"wms_license_source_note",
			"wms_bequeath_licence_info",
			"wms_bequeath_contact_info"
		);

		$resultObj = array();
		foreach ($fields as $field) {
			if ($field == "wms_timestamp" || $field == "wms_timestamp_create") {
				if ($wms->$field != "") {

					$resultObj[$field] = date('d.m.Y', $wms->$field);

				}
			}
			else {
				$resultObj[$field] = $wms->$field;
				//$e = new mb_exception("mb_metadata_server: resultObject[".$field."]=".$wms->$field);
			}
		}
		// layer searchable
		$resultObj["layer_searchable"] = array();
		foreach ($wms->objLayer as $layer) {
			if (intval($layer->layer_searchable) === 1) {
				$resultObj["layer_searchable"][] = intval($layer->layer_uid);
			}
		}

		$keywordSql = <<<SQL
SELECT DISTINCT keyword FROM keyword, layer_keyword
WHERE keyword_id = fkey_keyword_id AND fkey_layer_id IN (
	SELECT layer_id from layer, wms
	WHERE fkey_wms_id = wms_id AND wms_id = $wmsId
) ORDER BY keyword
SQL;
		$keywordRes = db_query($keywordSql);
		$keywords = array();
		while ($keywordRow = db_fetch_assoc($keywordRes)) {
			$keywords[]= $keywordRow["keyword"];
		}

		$resultObj["wms_keywords"] = implode(", ", $keywords);

		$termsofuseSql = <<<SQL
SELECT fkey_termsofuse_id FROM wms_termsofuse WHERE fkey_wms_id = $wmsId
SQL;
		$termsofuseRes = db_query($termsofuseSql);
		if ($termsofuseRes) {
			$termsofuseRow = db_fetch_assoc($termsofuseRes);
			$resultObj["wms_termsofuse"] = $termsofuseRow["fkey_termsofuse_id"];
		}
		else {
			$resultObj["wms_termsofuse"] = null;
		}
		$resultObj['wms_network_access'] = $resultObj['wms_network_access'] == 1 ? true : false;
		$resultObj['wms_bequeath_licence_info'] = $resultObj['wms_bequeath_licence_info'] == 1 ? true : false;
		$resultObj['wms_bequeath_contact_info'] = $resultObj['wms_bequeath_contact_info'] == 1 ? true : false;
		if (is_null($resultObj['inspire_annual_requests']) || $resultObj['inspire_annual_requests'] == "") {
			$resultObj['inspire_annual_requests'] = "0";
		}
		//get contact information from group relation
		//check if fkey_mb_group_id has been defined before - in service table
		if ($resultObj["fkey_mb_group_id"] == "" || !isset($resultObj["fkey_mb_group_id"])){
			$e = new mb_notice("fkey_mb_group_id is null or empty");
			//check if primary group is set
			$user = new User;
			$userId = $user->id;
			//$e = new mb_exception("user id:".$userId);
			$sql = <<<SQL
SELECT fkey_mb_group_id, mb_group_name, mb_group_title, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_logo_path, mb_group_voicetelephone FROM (SELECT fkey_mb_group_id FROM mb_user_mb_group WHERE fkey_mb_user_id = $1 AND mb_user_mb_group_type = 2) AS a LEFT JOIN mb_group ON a.fkey_mb_group_id = mb_group.mb_group_id
SQL;
			$v = array($userId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$row = array();
			if ($res) {
				$row = db_fetch_assoc($res);
				$resultObj["fkey_mb_group_id"] = $row["fkey_mb_group_id"];
				$resultObj["mb_group_title"] = $row["mb_group_title"];
				$resultObj["mb_group_address"] = $row["mb_group_address"];
				$resultObj["mb_group_email"] = $row["mb_group_email"];
				$resultObj["mb_group_postcode"] = $row["mb_group_postcode"];
				$resultObj["mb_group_city"] = $row["mb_group_city"];
				$resultObj["mb_group_logo_path"] = $row["mb_group_logo_path"];
				$resultObj["mb_group_voicetelephone"] = $row["mb_group_voicetelephone"];
			}
		} else {
			//get current fkey_mb_group_id and the corresponding data
			$sql = <<<SQL
SELECT mb_group_name, mb_group_title, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_logo_path, mb_group_voicetelephone FROM mb_group WHERE mb_group_id = $1
SQL;
			$v = array($resultObj["fkey_mb_group_id"]);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$row = array();
			if ($res) {
				$row = db_fetch_assoc($res);
				$resultObj["mb_group_title"] = $row["mb_group_title"];
				$resultObj["mb_group_address"] = $row["mb_group_address"];
				$resultObj["mb_group_email"] = $row["mb_group_email"];
				$resultObj["mb_group_postcode"] = $row["mb_group_postcode"];
				$resultObj["mb_group_city"] = $row["mb_group_city"];
				$resultObj["mb_group_logo_path"] = $row["mb_group_logo_path"];
				$resultObj["mb_group_voicetelephone"] = $row["mb_group_voicetelephone"];
			}
			else {
				$resultObj["fkey_mb_group_id"] = null;
			}
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
	break;
	//TODO make function get service metadata - easier!!!
	case "getWfsMetadata" :
		$wfsId = $ajaxResponse->getParameter("id");
		getWfs($wfsId);
		$sql = <<<SQL
SELECT wfs_id, wfs_abstract, wfs_title, wfs_alternate_title, fees, accessconstraints, 
individualname, positionname, providername, voice, 
facsimile, deliverypoint, city, 
administrativearea, postalcode, country, electronicmailaddress,
wfs_timestamp, wfs_timestamp_create, wfs_network_access, fkey_mb_group_id, wfs_max_features, inspire_annual_requests, wfs_license_source_note  
FROM wfs WHERE wfs_id = $wfsId;
SQL;
		$res = db_query($sql);
		$resultObj = array();
		$row = db_fetch_assoc($res);
		$resultObj['wfs_id'] = $row['wfs_id'];
		$resultObj['summary'] = $row['wfs_abstract'];
		$resultObj['title'] = $row['wfs_title'];
		$resultObj['alternate_title'] = $row['wfs_alternate_title'];
		$resultObj['fees'] = $row['fees'];
		$resultObj['accessconstraints'] = $row['accessconstraints'];
		$resultObj['individualName'] = $row['individualname'];
		$resultObj['positionName'] = $row['positionname'];
		$resultObj['providerName'] = $row['providername'];
		$resultObj['voice'] = $row['voice'];
		$resultObj['facsimile'] = $row['facsimile'];
		$resultObj['deliveryPoint'] = $row['deliverypoint'];
		$resultObj['city'] = $row['city'];
		$resultObj['administrativeArea'] = $row['administrativearea'];
		$resultObj['postalCode'] = $row['postalcode'];
		$resultObj['country'] = $row['country'];
		$resultObj['electronicMailAddress'] = $row['electronicmailaddress'];
		$resultObj['timestamp'] = $row['wfs_timestamp'] != "" ? date('d.m.Y', $row['wfs_timestamp']) : "";
		$resultObj['timestamp_create'] = $row['wfs_timestamp_create'] != "" ? date('d.m.Y', $row['wfs_timestamp_create']) : "";
		$resultObj['wfs_network_access'] = $row['wfs_network_access'];
		$resultObj['wfs_max_features'] = $row['wfs_max_features'];
		$resultObj['fkey_mb_group_id'] = $row['fkey_mb_group_id'];
		$resultObj['inspire_annual_requests'] = $row['inspire_annual_requests'];
		$resultObj['wfs_license_source_note'] = $row['wfs_license_source_note'];
		$keywordSql = <<<SQL
SELECT DISTINCT keyword FROM keyword, wfs_featuretype_keyword 
WHERE keyword_id = fkey_keyword_id AND fkey_featuretype_id IN (
	SELECT featuretype_id from wfs_featuretype, wfs 
	WHERE fkey_wfs_id = wfs_id AND wfs_id = $wfsId
) ORDER BY keyword
SQL;
		$keywordRes = db_query($keywordSql);
		$keywords = array();
		while ($keywordRow = db_fetch_assoc($keywordRes)) {
			$keywords[]= $keywordRow["keyword"];
		}
		$resultObj["wfs_keywords"] = implode(", ", $keywords);
		$termsofuseSql = <<<SQL
SELECT fkey_termsofuse_id FROM wfs_termsofuse WHERE fkey_wfs_id = $wfsId
SQL;
		$termsofuseRes = db_query($termsofuseSql);
		if ($termsofuseRes) {
			$termsofuseRow = db_fetch_assoc($termsofuseRes);
			$resultObj["wfs_termsofuse"] = $termsofuseRow["fkey_termsofuse_id"];
		}
		else {
			$resultObj["wfs_termsofuse"] = null;
		}
		$resultObj['wfs_network_access'] = $resultObj['wfs_network_access'] == 1 ? true : false;
		if (is_null($resultObj['inspire_annual_requests']) || $resultObj['inspire_annual_requests'] == "") {
			$resultObj['inspire_annual_requests'] = "0";
		}
		//get contact information from group relation
		//check if fkey_mb_group_id has been defined before - in service table
		if ($resultObj["fkey_mb_group_id"] == "" || !isset($resultObj["fkey_mb_group_id"])){
			$e = new mb_notice("fkey_mb_group_id is null or empty");
			//check if primary group is set 
			$user = new User;
			$userId = $user->id;
			$sql = <<<SQL
SELECT fkey_mb_group_id, mb_group_name, mb_group_title, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_logo_path, mb_group_voicetelephone FROM (SELECT fkey_mb_group_id FROM mb_user_mb_group WHERE fkey_mb_user_id = $1 AND mb_user_mb_group_type = 2) AS a LEFT JOIN mb_group ON a.fkey_mb_group_id = mb_group.mb_group_id
SQL;
			$v = array($userId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$row = array();
			if ($res) {
				$row = db_fetch_assoc($res);
				$resultObj["fkey_mb_group_id"] = $row["fkey_mb_group_id"];
				$resultObj["mb_group_title"] = $row["mb_group_title"];
				$resultObj["mb_group_address"] = $row["mb_group_address"];
				$resultObj["mb_group_email"] = $row["mb_group_email"];
				$resultObj["mb_group_postcode"] = $row["mb_group_postcode"];
				$resultObj["mb_group_city"] = $row["mb_group_city"];
				$resultObj["mb_group_logo_path"] = $row["mb_group_logo_path"];
				$resultObj["mb_group_voicetelephone"] = $row["mb_group_voicetelephone"];
			}
		} else {
			//get current fkey_mb_group_id and the corresponding data
			$sql = <<<SQL
SELECT mb_group_name, mb_group_title, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_logo_path, mb_group_voicetelephone FROM mb_group WHERE mb_group_id = $1
SQL;
			$v = array($resultObj["fkey_mb_group_id"]);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$row = array();
			if ($res) {
				$row = db_fetch_assoc($res);
				$resultObj["mb_group_title"] = $row["mb_group_title"];
				$resultObj["mb_group_address"] = $row["mb_group_address"];
				$resultObj["mb_group_email"] = $row["mb_group_email"];
				$resultObj["mb_group_postcode"] = $row["mb_group_postcode"];
				$resultObj["mb_group_city"] = $row["mb_group_city"];
				$resultObj["mb_group_logo_path"] = $row["mb_group_logo_path"];
				$resultObj["mb_group_voicetelephone"] = $row["mb_group_voicetelephone"];
			}
			else {
				$resultObj["fkey_mb_group_id"] = null;
			}
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	//NOTE: independend
	case "getResourceMetadata" :
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		switch ($resourceType) {
			case "layer":
				getLayer($resourceId);
				//new - only layers with latlonbboxes are supported!
				$sql = <<<SQL
SELECT layer_id, layer_name, layer_title, layer_abstract, layer_searchable, inspire_download, fkey_wms_id as wms_id
FROM layer WHERE layer_id = $resourceId;
SQL;
				$tablename = "layer";
				$identierName = "layer";
			break;
			case "featuretype":
				$featuretypeId = $ajaxResponse->getParameter("resourceId");
				getFeaturetype($featuretypeId);
				$sql = <<<SQL
SELECT featuretype_id, featuretype_name, featuretype_title, featuretype_abstract, featuretype_searchable, inspire_download 
FROM wfs_featuretype WHERE featuretype_id = $featuretypeId;
SQL;
				$tablename = "wfs_featuretype";
				$identierName = "featuretype";
			break;
		}
		$res = db_query($sql);
		$resultObj = array();
		while ($row = db_fetch_assoc($res)) {
			foreach ($row as $key => $value) {
				$resultObj[$key] = $value;
				$e = new mb_notice("plugins/mb_metadata_server.php: get ".$value." for ".$key);
			}
		}
		$sql = "SELECT fkey_md_topic_category_id FROM ".$tablename."_md_topic_category WHERE fkey_".$identierName."_id = ".$resourceId." AND fkey_metadata_id IS NULL";
		$res = db_query($sql);
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$identierName."_md_topic_category_id"][]= $row["fkey_md_topic_category_id"];
		}
		$sql = "SELECT fkey_inspire_category_id FROM ".$tablename."_inspire_category WHERE fkey_".$identierName."_id = ".$resourceId." AND fkey_metadata_id IS NULL";
		$res = db_query($sql);
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$identierName."_inspire_category_id"][]= $row["fkey_inspire_category_id"];
		}
		$sql = "SELECT fkey_custom_category_id FROM ".$tablename."_custom_category WHERE fkey_".$identierName."_id = ".$resourceId." AND fkey_metadata_id IS NULL";
		$res = db_query($sql);
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$identierName."_custom_category_id"][]= $row["fkey_custom_category_id"];
		}
		$sql = "SELECT keyword FROM keyword, ".$tablename."_keyword WHERE keyword_id = fkey_keyword_id AND fkey_".$identierName."_id = ".$resourceId;
		$res = db_query($sql);
		$resultObj[$identierName."_keyword"] = array();
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$identierName."_keyword"][]= $row["keyword"];
		}
		$resultObj['inspire_download'] = $resultObj['inspire_download'] == 1 ? true : false;
		//get wgs84Bbox for relevant layer - to be bequeathed to the metadata
		/*$sql = <<<SQL
SELECT minx, miny, maxx, maxy from layer_epsg WHERE fkey_layer_id = $1 AND epsg = 'EPSG:4326'
SQL;
		$res = db_query($sql);*/
		//read out values
		//get coupled MetadataURLs from md_metadata and ows_relation_metadata table
		$sql = "SELECT metadata_id, uuid, link, linktype, md_format, relation.relation_type, origin FROM mb_metadata";
		$sql .= " INNER JOIN (SELECT * from ows_relation_metadata WHERE fkey_".$resourceType."_id = ".$resourceId." ) as relation ON";
		$sql .= " mb_metadata.metadata_id = relation.fkey_metadata_id WHERE relation.relation_type IN"; 
		$sql .= " ('capabilities','external','metador','upload', 'internal') ORDER BY metadata_id DESC";
		$res = db_query($sql);
		$resultObj["md_metadata"]->metadata_id = array();
		$resultObj["md_metadata"]->uuid = array();
		$resultObj["md_metadata"]->origin = array();
		$resultObj["md_metadata"]->linktype = array();
		$resultObj["md_metadata"]->link = array();
		$resultObj["md_metadata"]->internal = array();
		$i = 0;
		while ($row = db_fetch_assoc($res)) {
			$resultObj["md_metadata"]->metadata_id[$i]= $row["metadata_id"];
			$resultObj["md_metadata"]->link[$i]= $row["link"];
			$resultObj["md_metadata"]->uuid[$i]= $row["uuid"];
			$resultObj["md_metadata"]->origin[$i]= $row["origin"];
			$resultObj["md_metadata"]->linktype[$i]= $row["linktype"];
			if ($row["relation_type"] == "internal") {
				$resultObj["md_metadata"]->internal[$i] = 1;
			} else {
				$resultObj["md_metadata"]->internal[$i] = 0;
			}
			$i++;
		}
		if ($resourceType == "layer") {
        		// check for preview image
        		if (file_exists(PREVIEW_DIR."/".$layerId."_layer_map_preview.jpg") || file_exists(PREVIEW_DIR."/".$layerId."_layer_map_preview.png")) {
            			$resultObj['hasPreview']= true;
        		} else {
            			$resultObj['hasPreview']= false;
        		}
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
	break;
	case "getLayerByWms" :
	    // https://www.sitepoint.com/hierarchical-data-database/
		$wmsId = $ajaxResponse->getParameter("id");
		$sql = <<<SQL

SELECT layer_id, f_count_layer_couplings(layer_id) as count_coupling, f_collect_inspire_cat_layer(layer_id) AS inspire_cats, layer_pos, layer_parent, layer_name, layer_title, layer_abstract, layer_searchable, inspire_download
FROM layer WHERE fkey_wms_id = $wmsId ORDER BY layer_pos;

SQL;
		$res = db_query($sql);
		$rows = array();
		while ($row = db_fetch_assoc($res)) {
			$rows[] = $row;
		}
		// initialize left to one
		$left = 1;
		
        /*
         * Functions for converting adjacense model to mptt model
         */
        /*
         * Function to iterate over unclosed parent leafs of the tree. It sets the mptt - rgt values if subtrees are completed.
         *
         * @param integer $parent_id - ID/index of the first parent where the closure should start
         * @param array $adjacense_array - The original adjacense array where pos and parent elements are given. This should be ordered!
         * @param reference &$mptt_array - The reference to the result array with the lft and rgt columns of mptt model
         * @param integer $adjacense_index - The index of the leaf where the closure begin - only higher values (unprocessed) are tested
         * @param reference &$left - The reference to the left value of the mptt leaf where closer of parents will start. This will be incremented.
         */
		function close_parents_recursive($parent_id, $adjacense_array, &$mptt_array, $adjacense_index, &$left) {
		    //get id of the next upper parent leaf
		    $parent_id_new = $adjacense_array[$parent_id]['parent'];
		    //print "Recursive invocation: Parent ID: ".$parent_id . " Title: " . $adjacense_array[$parent_id]['title'] . "\n";
		    //Variable to check, if all childs are already processed - then the right value for the leaf maybe calculated
		    $allChildsProcessed = true;
		    //debug
		    //print json_encode($adjacense_array[$parent_id])."\n";
		    //get all unprocessed entries that has the same parent
		    $reducedArray = array();
		    foreach ($adjacense_array as $entry) {
		        if ($entry['parent'] == $parent_id && $entry['pos'] > $adjacense_index) {
		            $reducedArray[] = $entry;
		        }
		    }
		    //print "Variable reducedArray: ".json_encode($reducedArray)." - parent_id: ".$parent_id." - left (from invocation of function): ".  $left ."\n";
		    if (count($reducedArray) > 0 ) {
		        // Some siblings of parent are not already processed
		        $allChildsProcessed = false;
		    } else {
		        //print "No unprocessed childs found for parent - set right value!"."\n";
		    }
		    // stop condition - if not all
		    if ($allChildsProcessed == false || $parent_id == 0) {
		        //print "Stop condition reached"."\n";
		        //print "Give back for : ". ($left + 1) ."\n";
		        $left = $left + 1;
		        return;
		    } else {
		        $left = $left + 1 ;
		        //print "Set rgt for ". $mptt_array[$parent_id]['title'] . " to ". ($left + 1) ."\n";
		        $mptt_array[$parent_id]['rgt'] = $left + 1;
		        close_parents_recursive($parent_id_new, $adjacense_array, $mptt_array, $adjacense_index, $left);
		    }
		}
		
		/*
		 * Function to built up the mptt lft and rgt values from a given adjacense tree array with pos and parent values.
		 * The function is called recursively and will iterate from top to down. The tree must be ordered!
		 *
		 * @param array $adjacense_array - The original adjacense array where pos and parent elements are given. This should be ordered!
		 * @param reference &$mptt_array - The reference to the result array with the lft and rgt columns of mptt model
		 * @param integer $adjacense_index - The index of the leaf where the closure begin - only higher values (unprocessed) are tested
		 * @param reference &$left - The reference to the left value of the mptt leaf where closer of parents will start. This will be incremented.
		 */
		function built_mptt_elements_recursive($adjacense_index, $adjacense_array, $left, &$mptt_array){
		    while ($adjacense_index < count($adjacense_array)) {
		        $mptt_entry = array();
		        //print "Index: " . $adjacense_index . " - Title: " . $adjacense_array[$adjacense_index]['title'] . "\n";
		        //initialize default values
		        $mptt_array[$adjacense_index]['title'] = $adjacense_array[$adjacense_index]['title'];
		        $mptt_array[$adjacense_index]['pos'] = $adjacense_array[$adjacense_index]['pos'];
		        $mptt_array[$adjacense_index]['parent'] = $adjacense_array[$adjacense_index]['parent'];
		        $mptt_array[$adjacense_index]['lft'] = $left;
		        //begin with root node - set rgt to null
		        if ($adjacense_array[$adjacense_index]['parent'] === null) {
		            $mptt_array[$adjacense_index]['rgt'] = null;
		            $left = $left + 1;
		        } else {
		            //check if leaf is not last leaf - if it is the last one - try to close tree
		            if (($adjacense_index + 1) == count($adjacense_array)) {
		                $mptt_array[$adjacense_index]['rgt']  = $mptt_array[$adjacense_index]['lft'] + 1;
		                $left = $left + 1;
		                //get index of parent leaf
		                close_parents_recursive($adjacense_array[$adjacense_index]['parent'], $adjacense_array, $mptt_array, $adjacense_index, $left);
		            } else {
		                // if the leaf has childs, right is rgt also null, cause first all childs have to bee selected
		                if ($adjacense_array[$adjacense_index + 1]['parent'] == $adjacense_array[$adjacense_index]['pos']) {
		                    //print "Leaf is parent of a next leaf!\n";
		                    $mptt_array[$adjacense_index]['rgt']  = null;
		                } else {
		                    //print "Leaf has no childs - it may have siblings - in all cases the lft and rgt will be simply incremented!\n";
		                    $mptt_array[$adjacense_index]['rgt']  = $mptt_array[$adjacense_index]['lft'] + 1;
		                    //if next leaf is not at the same level (last sibling) close the parent leaf with incremented right value
		                    if ($adjacense_array[$adjacense_index + 1]['parent'] !== $adjacense_array[$adjacense_index]['parent']) {
		                        //print "Next leaf is not at the same level - go up and set rgt of its parent!\n";
		                        //get index of parent leaf
		                        close_parents_recursive($adjacense_array[$adjacense_index]['parent'], $adjacense_array, $mptt_array, $adjacense_index, $left);
		                    }
		                }
		            }
		            $left = $left + 1;
		        }
		        $adjacense_index++;
		        built_mptt_elements_recursive($adjacense_index, $adjacense_array, $left, $mptt_array);
		        return true;
		    }
		}
		
		/*
		 * Main function to transform an adjacense tree array to a mptt tree array
		 */
		
		function adjacense2mptt($adjacense_array) {
		    $left = 1;
		    $adjacense_index = 0;
		    $mptt_array = array();
		    built_mptt_elements_recursive($adjacense_index, $adjacense_array, $left, $mptt_array);
		    $mptt_array[0]['rgt'] = count($mptt_array) * 2;
		    return $mptt_array;
		}
		/*
		 * End of mptt functions
		 */
		
		function createNode ($left, $right, $row) {
			$inspireCatsArray = explode(",",str_replace("}","",str_replace("{","",$row["inspire_cats"])));
			if (count($inspireCatsArray) >= 0) {
				$inspireCats = 1;
			} else {
				$inspireCats = 0;
			}
			return array(
				"left" => $left,
				"right" => $right,
				"parent" => $row["layer_parent"] !== "" ? intval($row["layer_parent"]) : null,
				"pos" => intval($row["layer_pos"]),
				"attr" => array (
					"layer_id" => intval($row["layer_id"]),
					"layer_name" => $row["layer_name"],
					"layer_title" => $row["layer_title"],
					"layer_abstract" => $row["layer_abstract"],
					"layer_searchable" => intval($row["layer_searchable"]),
					"layer_coupling" => intval($row["count_coupling"]),
					"inspire_download" => intval($row["inspire_download"]),
					"inspire_cats" => intval($inspireCats)
				)
			);
		}

		function addSubTree ($rows, $i, $left) {
			$nodeArray = array();
			$addNewNode = true;
			for ($j = $i; $j < count($rows); $j++) {
				$row = $rows[$j];
				$pos = intval($row["layer_pos"]);
				$parent = $row["layer_parent"] !== "" ? intval($row["layer_parent"]) : null;
				// first node of subtree
				if ($addNewNode) {
					$nodeArray[]= createNode($left, null, $row);
					//$e = new mb_exception("plugins/mb_metadata_server.php: root node: ". json_encode($row) ." with left: ". $left);
					//$e = new mb_exception("plugins/mb_metadata_server.php: group node: ". $row['layer_id']. " - " .$row['layer_title'] . " - ".$left);
					$addNewNode = false;
				}
				else {
					// new sub tree
					if ($parent === $nodeArray[count($nodeArray)-1]["pos"]) {
						$addedNodeArray = addSubTree($rows, $j, ++$left);
						$nodeArray[count($nodeArray)-1]["right"] =
							$nodeArray[count($nodeArray)-1]["left"] +
							2 * count($addedNodeArray) + 1;
						$left = $nodeArray[count($nodeArray)-1]["right"] + 1;
						$nodeArray = array_merge($nodeArray, $addedNodeArray);
						$j += count($addedNodeArray) - 1;
						$addNewNode = true;
					}// siblings
					elseif ($parent === $nodeArray[count($nodeArray)-1]["parent"]) {
						$nodeArray[count($nodeArray)-1]["right"] = ++$left;
						$nodeArray[]= createNode(++$left, null, $row);
						//test
						//$addNewNode = false;
					}
				}
			}
			if (is_null($nodeArray[count($nodeArray)-1]["right"])) {
				$nodeArray[count($nodeArray)-1]["right"] = ++$left;
			}
			return $nodeArray;
		}
		
		// initialize function
		$nodeArray = addSubTree($rows, 0, $left);
		
		//alternative approach
		//$e = new mb_exception("plugins/mb_metadata_server.php: count nodeArray: " . count($nodeArray));
		$adjacenseArray = array();
		$entry = array();
		foreach ($nodeArray as $node) {
		    $entry['pos'] =  $node['pos'];
		    $entry['parent'] = $node['parent'];
		    $entry['title'] = $node['attr']['layer_title'];
		    $adjacenseArray[] = $entry;
		}
		/*$e = new mb_exception("plugins/mb_metadata_server.php: count adjacenseArray: " . count($adjacenseArray));
		$e = new mb_exception("plugins/mb_metadata_server.php: adjacenseArray: " . json_encode($adjacenseArray));*/
		$mpttArray = adjacense2mptt($adjacenseArray);
		
		//$e = new mb_exception("plugins/mb_metadata_server.php: mpttArray: " . json_encode($mpttArray));
		for ($j = 0; $j < count($nodeArray); $j++) {
		    $nodeArray[$j]['left'] = $mpttArray[$j]['lft'];
		    $nodeArray[$j]['right'] = $mpttArray[$j]['rgt'];
		}
		//$e = new mb_exception("plugins/mb_metadata_server.php: nodeArray: " . json_encode($nodeArray));
		$resultObj = array(
			"nestedSets" => $nodeArray
			);
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getFeaturetypeByWfs" :
		$wfsId = $ajaxResponse->getParameter("id");
		$sql = <<<SQL
SELECT featuretype_id, featuretype_name, f_count_featuretype_couplings(featuretype_id) as count_coupling, f_collect_inspire_cat_wfs_featuretype(featuretype_id) AS inspire_cats, featuretype_abstract, featuretype_searchable, inspire_download  
FROM wfs_featuretype WHERE fkey_wfs_id = $wfsId ORDER BY featuretype_id;
SQL;
		$res = db_query($sql);
		$rows = array();
		while ($row = db_fetch_assoc($res)) {
			$rows[] = $row;
		}
		$left = 1;
		function createNode ($left, $right, $row) {
			return array(
				"left" => $left,
				"right" => $right,
				#"parent" => $row["featuretype_parent"] !== "" ? intval($row["featuretype_parent"]) : null,
				#"pos" => intval($row["featuretype_pos"]),
				"attr" => array (
					"featuretype_id" => intval($row["featuretype_id"]),
					"featuretype_name" => $row["featuretype_name"],
					"featuretype_title" => $row["featuretype_title"],
					"featuretype_abstract" => $row["featuretype_abstract"],
					"featuretype_searchable" => intval($row["featuretype_searchable"]),
					"inspire_download" => intval($row["inspire_download"]),
					"featuretype_coupling" => intval($row["count_coupling"]),
					"inspire_cats" => intval($inspireCats)
				)
			);
		}

		function addSubTree ($rows, $i, $left) {
			$nodeArray = array();
			$addNewNode = true;
			for ($j = $i; $j < count($rows); $j++) {
				$row = $rows[$j];
				$pos = $j;
				
				// first node of subtree
				if ($addNewNode) {
					$nodeArray[]= createNode($left, null, $row);
					$addNewNode = false;
				}
				else {
					$nodeArray[count($nodeArray)-1]["right"] = ++$left;
					$nodeArray[]= createNode(++$left, null, $row);
				}
			}
			if (is_null($nodeArray[count($nodeArray)-1]["right"])) {
				$nodeArray[count($nodeArray)-1]["right"] = ++$left;
			}
			return $nodeArray;
		}
		$nodeArray = addSubTree($rows, 0, 1);
		$resultObj = array(
			"nestedSets" => $nodeArray
		);
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);	
		break;
	case "save":
		$data = $ajaxResponse->getParameter("data");
		$serviceType = $ajaxResponse->getParameter("serviceType");
		switch ($serviceType) {
			case "wms":
				try {
					$wmsId = intval($data->wms->wms_id);
				}
				catch (Exception $e) {
					$ajaxResponse->setSuccess(false);
					$ajaxResponse->setMessage(_mb("Invalid WMS ID."));
					$ajaxResponse->send();
				}
				getWms($wmsId);
				$wms = new wms();
				$wms->createObjFromDBNoGui($wmsId,false);//here the original urls will be used - cause the object will used to update the wms table
				$columns = array(
					"wms_abstract",
					"wms_title",
				    "wms_alternate_title",
					"fees",
					"accessconstraints",
					"contactperson",
					"contactposition",
					"contactvoicetelephone",
					"contactfacsimiletelephone",
					"contactorganization",
					"address",
					"city",
					"stateorprovince",
					"postcode",
					"country",
					"contactelectronicmailaddress",
					"wms_termsofuse",
					"wms_network_access",
					"wms_max_imagesize",
					"fkey_mb_group_id",
					"inspire_annual_requests",
					"wms_license_source_note",
					"wms_bequeath_licence_info",
					"wms_bequeath_contact_info"
				);
				foreach ($columns as $c) {
					if ($c == 'wms_termsofuse' && $data->wms->$c == "0") {
						$value = null;
					} else {
						if ($c == 'inspire_annual_requests' && $data->wms->$c == "") {
							$value = "0";
						} else {
							$value = $data->wms->$c;
						}
					}
					if (!is_null($value)) {
						$wms->$c = $value;
					}
				}
				if (is_array($data->wms->layer_searchable)) {
					foreach ($wms->objLayer as &$layer) {
						$layer->layer_searchable = 0;//why
						$e = new mb_notice("mb_metadata_server.php: Check layer with id ".$layer->layer_uid." to be searchable");
						for ($i = 0; $i < count($data->wms->layer_searchable); $i++) {
							//$e = new mb_exception("mb_metadata_server.php: Layer with id ".$id." found to be searchable");
							$id = $data->wms->layer_searchable[$i];
							$e = new mb_notice("mb_metadata_server.php: Layer with id ".$id." found to be searchable");
							if ($id == intval($layer->layer_uid)) {
								$e = new mb_notice("mb_metadata_server.php: Layer identical - update it in wms object");
								$layer->layer_searchable = 1;
							} else {
								continue; //with next
							}
							unset($id);
							//$layer->layer_searchable = 1;
							//break;
						}
					}
				}
				try {
					$layerId = intval($data->layer->layer_id);
				}
				catch (Exception $e) {
		  			$ajaxResponse->setSuccess(false);
					$ajaxResponse->setMessage(_mb("Could not read layer ID ".$data->layer->layer_id));
					$ajaxResponse->send();
				}
				if ($layerId) {
					$e = new mb_notice("Got following layer id from wms metadata editor client: ".$layerId);
					try {
						$layer = &$wms->getLayerReferenceById($layerId);
					}
					catch (Exception $e) {
						$ajaxResponse->setSuccess(false);
						$ajaxResponse->setMessage(_mb("Could not get layer with ID ".$layerId." from wms object by reference!"));
						$ajaxResponse->send();
					}
					$columns = array(
						"layer_abstract",
						"layer_title",
						"layer_keyword",
						"inspire_download",
						"layer_md_topic_category_id",
						"layer_inspire_category_id",
						"layer_custom_category_id"
					);
					//extract relevant information from json and fill them into the wms object // both are filled together!!
					foreach ($columns as $c) {
						$value = $data->layer->$c;
						$e = new mb_notice("plugins/mb_metadata_server.php: layer entry for ".$c.": ".$data->layer->$c);
						if ($c === "layer_keyword") {
							$layer->$c = explode(",", $value);
							foreach ($layer->$c as &$val) {
								$val = trim($val);
							}
						}
						elseif ($c === "layer_md_topic_category_id"
							|| $c === "layer_inspire_category_id"
							|| $c === "layer_custom_category_id"
						) {
							if (!is_array($value)) {
								$layer->$c = array($value);
							}
							else {
								$layer->$c = $value;
							}
						}
						elseif ($c === "inspire_download") {
							if ($value == "on") {
								$layer->$c = intval('1');
							} else {
								$layer->$c = intval('0');
							}
						}
						else {
							if (!is_null($value)) {
								$layer->$c = $value;
							}
						}
					}
				}
				//array of checkboxes (integer values in database)
				$checkboxes = array("wms_network_access","wms_bequeath_licence_info","wms_bequeath_contact_info");
				foreach ($checkboxes as $checkbox) {
					if ($wms->{$checkbox} == "on") {
						$wms->{$checkbox} = intval('1');
					} else {
						$wms->{$checkbox} = intval('0');
					}
				}

				if (defined("TWITTER_NEWS") && TWITTER_NEWS == true && $ajaxResponse->getParameter("twitterNews") == true) {
    	    				$wms->twitterNews = true;
					$twitterIsConfigured = true;
					//$e = new mb_exception("twitter configured");
    				} else {
					$wms->twitterNews = false;
					$twitterIsConfigured = false;
					//$e = new mb_exception("twitter not configured");
				}
    				if(defined("GEO_RSS_FILE") && GEO_RSS_FILE != "" && $ajaxResponse->getParameter("setGeoRss") == true) {
        				$wms->setGeoRss = true;
					$rssIsConfigured = true;
					//$e = new mb_exception("rss configured");
    				} else {
					$rssIsConfigured = false;
					$wms->setGeoRss = false;
					//$e = new mb_exception("rss not configured");
				}

				$messResult = "Updated WMS metadata for ID " . $wmsId.". ";
				//Add helpful hint if publishing is demanded, but not configured in mapbender.conf - do this before update object - cause otherwise it will not give back the right attributes
				if (!$wms->twitterNews && ($ajaxResponse->getParameter("twitterNews") == true)) {
					$messResult .= " Publishing via twitter was requested, but this is not configured. Please check your mapbender.conf! ";
				}
				if (!$wms->setGeoRss && ($ajaxResponse->getParameter("setGeoRss") == true)) {
					$messResult .= " Publishing via rss was requested, but this is not configured. Please check your mapbender.conf! ";
				}

				//try {
				$wms->overwriteCategories = true;
				$wms->updateObjInDB($wmsId,true);
				//}
				//catch (Exception $e) {
				//	$ajaxResponse->setSuccess(false);
				//	$ajaxResponse->setMessage(_mb("Could not update wms object in database!"));
				//	$ajaxResponse->send();
				//}
				break;
			case "wfs":
				try {
					$wfsId = intval($data->wfs->wfs_id);
				}
				catch (Exception $e) {
					$ajaxResponse->setSuccess(false);
					$ajaxResponse->setMessage(_mb("Invalid WFS ID."));
					$ajaxResponse->send();						
				}
				getWfs($wfsId);
				$wfsFactory = new UniversalWfsFactory();
				$wfs = $wfsFactory->createFromDb($wfsId);
				if (is_null($wfs)) {
					$ajaxResponse->setSuccess(false);
					$ajaxResponse->setMessage(_mb("Invalid WFS ID."));
					$ajaxResponse->send();	
				}
				$columns = array(
					"summary", 
					"title", 
				    "alternate_title",
					"fees", 
					"accessconstraints", 
					"individualName", 
					"positionName", 
					"voice", 
					"facsimile", 
					"providerName", 
					"deliveryPoint", 
					"city", 
					"administrativeArea", 
					"postalCode", 
					"country", 
					"electronicMailAddress",
					"wfs_termsofuse",
					"timestamp",
					"timestamp_create",
					"wfs_network_access",
					"wfs_max_features",
					"fkey_mb_group_id",
					"inspire_annual_requests",
					"uuid",
					"wfs_license_source_note"
				);
				foreach ($columns as $c) {
					if ($c == 'wfs_termsofuse' && $data->wfs->$c == "0") {
						$value = null;
					} else {
						$value = $data->wfs->$c;
					}
					if (!is_null($value)) {
						$wfs->$c = $value;
					}
				}
				if (is_array($data->wfs->featuretype_searchable)) {
					foreach ($wfs->featureTypeArray as &$featuretype) {//for each existing featuretype
						$featuretype->searchable = 0;//initialize new
						$e = new mb_notice("mb_metadata_server.php: Check ft with id ".$featuretype->id." to be searchable");
						for ($i = 0; $i < count($data->wfs->featuretype_searchable); $i++) {
							$id = $data->wfs->featuretype_searchable[$i];
							$e = new mb_notice("mb_metadata_server.php: ft with id ".$id." found to be searchable");
							if ($id == intval($featuretype->id)) {
								$e = new mb_notice("mb_metadata_server.php: ft identical - update it in wfs object");
								$featuretype->searchable = 1;					
							} else {
								continue; //with next 
							}
							unset($id);
							//$layer->layer_searchable = 1;
							//break;
						}
					}
				}
				try {
					$featuretypeId = intval($data->featuretype->featuretype_id);
				}
				catch (Exception $e) {
					$ajaxResponse->setSuccess(false);
					$ajaxResponse->setMessage(_mb("Could not read featuretype with ID ".$data->featuretype->featuretype_id));
					$ajaxResponse->send();		
				}
				if ($featuretypeId) {
					$e = new mb_notice("Got following featuretype id from wfs metadata editor client: ".$featuretypeId);
					try {
						$featuretype = &$wfs->findFeatureTypeReferenceById($featuretypeId);
					}
					catch (Exception $e) {
						$ajaxResponse->setSuccess(false);
						$ajaxResponse->setMessage(_mb("Could not get featuretype with ID ".$featuretypeId." from wfs object by reference!"));
						$ajaxResponse->send();
					}
					$columns = array(
						"summary", 
						"title",
						"featuretype_keyword",
						"inspire_download",
						"featuretype_md_topic_category_id",
						"featuretype_inspire_category_id",
						"featuretype_custom_category_id"
					);			
					//extract relevant information from json and fill them into the wfs object
					foreach ($columns as $c) {
						if ($c === "summary") {
							$value = $data->featuretype->featuretype_abstract;
						}
						elseif ($c === "title") {
							$value = $data->featuretype->featuretype_title;
						}
						else {
							$value = $data->featuretype->$c;
						}	
						if ($c === "featuretype_keyword") {
							$featuretype->$c = explode(",", $value);
							foreach ($featuretype->$c as &$val) {
								$val = trim($val);
							}
						}
						elseif ($c === "featuretype_md_topic_category_id" 
							|| $c === "featuretype_inspire_category_id"
							|| $c === "featuretype_custom_category_id"
						) {
							if (!is_array($value)) {
								$featuretype->$c = array($value);
							}
							else {
								$featuretype->$c = $value;
							}
						}
						elseif ($c === "inspire_download") {
							if ($value == "on") {
								$featuretype->$c = intval('1');
							} else {
								$featuretype->$c = intval('0');
							}
						}
						else {
							if (!is_null($value)) {
								$featuretype->$c = $value;
							}
						}
					}
				}
				if ($wfs->wfs_network_access == "on") {
					$wfs->wfs_network_access = intval('1');
				} else {
					$wfs->wfs_network_access = intval('0');
				}
		
				if($wfs->wfs_max_features == "") {
					$wfs->wfs_max_features = intval('1000');
				}
				$wfs->update($wfsId, true, true); //parameter for metadata only update - some things are not pulled when creating object from database and some things need not to be updated.
				$messResult = "Updated WFS metadata for ID " . $wfsId.".";
				break;
			}
		$ajaxResponse->setMessage($messResult);
		$ajaxResponse->setSuccess(true);
		break;
	//NOTE: independend
	case "getContactMetadata" :
		$mbGroupId = $ajaxResponse->getParameter("id");
		$sql = <<<SQL
SELECT mb_group_name, mb_group_title, mb_group_address, mb_group_email, mb_group_postcode, mb_group_city, mb_group_logo_path, mb_group_voicetelephone FROM mb_group WHERE mb_group_id = $1
SQL;
		$v = array($mbGroupId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		if ($res) {
			$row = db_fetch_assoc($res);
			$resultObj["fkey_mb_group_id"] = $mbGroupId;
			$resultObj["mb_group_name"] = $row["mb_group_name"];
			$resultObj["mb_group_title"] = $row["mb_group_title"];
			$resultObj["mb_group_address"] = $row["mb_group_address"];
			$resultObj["mb_group_email"] = $row["mb_group_email"];
			$resultObj["mb_group_postcode"] = $row["mb_group_postcode"];
			$resultObj["mb_group_city"] = $row["mb_group_city"];
			$resultObj["mb_group_logo_path"] = $row["mb_group_logo_path"];
			$resultObj["mb_group_voicetelephone"] = $row["mb_group_voicetelephone"];
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	//NOTE: independend
	case "getLicenceInformation" :
		$termsofuseId = $ajaxResponse->getParameter("id");
		$sql = <<<SQL
SELECT name, symbollink, description, descriptionlink, isopen, source_required FROM termsofuse WHERE termsofuse_id = $1
SQL;
		$v = array($termsofuseId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		if ($res) {
			$row = db_fetch_assoc($res);
			$resultObj["termsofuse_id"] = $termsofuseId;
			$resultObj["name"] = $row["name"];
			$resultObj["symbollink"] = $row["symbollink"];
			$resultObj["description"] = $row["description"];
			$resultObj["descriptionlink"] = $row["descriptionlink"];
			$resultObj["isopen"] = $row["isopen"];
			$resultObj["source_required"] = $row["source_required"];
			if ($resultObj["source_required"] !== 't') {
				$resultObj["source_required"] = 0;
			} else {
				$resultObj["source_required"] = 1;
			}
			$ajaxResponse->setResult($resultObj);
			$ajaxResponse->setSuccess(true);
		} else {
			$ajaxResponse->setSuccess(false);
		}
		break;
	case "getWmsIdByLayerId" :
		$layerId = $ajaxResponse->getParameter("layerId");
		$sql = <<<SQL
SELECT fkey_wms_id from layer where layer_id = $1
SQL;
		$v = array($layerId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		if ($res) {
			$row = db_fetch_assoc($res);
			$resultObj["wms_id"]= $row['fkey_wms_id'];
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getMetadata" :
		//TODO - only for application at this time!
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->createFromDBInternalId($metadataId);
		if ($result) {
			//map metadata object to json return object
			$resultObj["metadata_id"]= $metadataId; //is not part of the object TODO!
			$resultObj["uuid"] = $mbMetadata->fileIdentifier; //char
			$resultObj["origin"] = $mbMetadata->origin; //char
			$resultObj["link"] = $mbMetadata->href; //char
			//$resultObj["linktype"] = $mbMetadata->type; //char
			$resultObj["title"] = $mbMetadata->title; //char -- prefill from layer/ft
			$resultObj["alternate_title"] = $mbMetadata->alternate_title; //char -- prefill from layer/ft
			$resultObj["abstract"] = $mbMetadata->abstract; //char - prefill from layer/ft
			//$resultObj["format"] = $mbMetadata->dataFormat; //char
			$resultObj["ref_system"] = $mbMetadata->refSystem; //char
			/*$resultObj["spatial_res_type"] = $mbMetadata->spatialResType; //integer
			$resultObj["spatial_res_value"] = $mbMetadata->spatialResValue; //char
			$resultObj["inspire_charset"] = $mbMetadata->inspireCharset; //char*/
			$resultObj["lineage"] = $mbMetadata->lineage; //text
			$resultObj["tmp_reference_1"] = $mbMetadata->tmpExtentBegin; //text
			$resultObj["tmp_reference_2"] = $mbMetadata->tmpExtentEnd; //text
			$resultObj["west"] = $mbMetadata->wgs84Bbox[0];
			$resultObj["south"] = $mbMetadata->wgs84Bbox[1];
			$resultObj["east"] = $mbMetadata->wgs84Bbox[2];
			$resultObj["north"] = $mbMetadata->wgs84Bbox[3];
			/*$resultObj["downloadlink"] = $mbMetadata->downloadLinks[0]; //only the first link!
			$resultObj["inspire_whole_area"] = $mbMetadata->inspireWholeArea;
			$resultObj["inspire_actual_coverage"] = $mbMetadata->inspireActualCoverage;*/
			$resultObj["preview_image"] = $mbMetadata->previewImage;
			$resultObj["overview_url"] = $mbMetadata->getExtentGraphic($mbMetadata->wgs84Bbox);
			$export2csw = $mbMetadata->export2Csw; //boolean
			$resultObj["update_frequency"] = $mbMetadata->updateFrequency; //text
			//check for existing polygon
			//$e = new mb_exception("mb_metadata_server.php: count of polygon points ".count($mbMetadata->polygonalExtentExterior));
			if (count($mbMetadata->polygonalExtentExterior) >= 1) {
				$e = new mb_notice("mb_metadata_server.php: count of polygon points ".count($mbMetadata->polygonalExtentExterior));
				$resultObj["has_polygon"] = true;
			} else {
				$resultObj["has_polygon"] = false;
			}
			switch ($export2csw) {
				case "t" :
					$resultObj["export2csw"] = true;
					$resultObj["export2csw2"] = true;
					break;
				case "f" :
					$resultObj["export2csw"] = false;
					$resultObj["export2csw2"] = false;
					break;
				default:
				break;
			}
			/*$inspire_top_consistence = $mbMetadata->inspireTopConsistence; //boolean
			switch ($inspire_top_consistence) {
				case "t" :
					$resultObj["inspire_top_consistence"] = true;
					break;
				case "f" :
					$resultObj["inspire_top_consistence"] = false;
					break;
				default:
				break;
			}
			$inspire_interoperability = $mbMetadata->inspireInteroperability; //boolean
			switch ($inspire_interoperability) {
				case "t" :
					$resultObj["inspire_interoperability"] = true;
					break;
				case "f" :
					$resultObj["inspire_interoperability"] = false;
					break;
				default:
				break;
			}*/
			$searchable = $mbMetadata->searchable; //boolean
			switch ($searchable) {
				case "t" :
					$resultObj["searchable"] = true;
					break;
				case "f" :
					$resultObj["searchable"] = false;
					break;
				default:
				break;
			}
			/*switch ($mbMetadata->inspireDownload) {
				case 0 :
					$resultObj["inspire_download"] = false;
					break;
				case 1 :
					$resultObj["inspire_download"] = true;
					break;
				default:
				break;
			}*/
			//things about licences
			$resultObj["fees_md"] = $mbMetadata->fees;
			$resultObj["accessconstraints_md"] = $mbMetadata->accessConstraints;
			$resultObj["md_termsofuse"] = $mbMetadata->termsOfUseRef;
			if ($resultObj["md_termsofuse"] == null || !isset($resultObj["md_termsofuse"])) {
				$resultObj["md_termsofuse"] == '0';
			}
			$resultObj["md_license_source_note"] = $mbMetadata->licenseSourceNote;
			//categories and keywords
			$resultObj["md_md_topic_category_id"] = $mbMetadata->isoCategories;
			$resultObj["md_custom_category_id"] = $mbMetadata->customCategories;
			$resultObj["md_inspire_category_id"] = $mbMetadata->inspireCategories;
			//only pull keywords without a thesaurus name!!
			for ($i = 0; $i < count($mbMetadata->keywords); $i++) {
				if ($mbMetadata->keywordsThesaurusName[$i] == "" or $mbMetadata->keywordsThesaurusName[$i] == "none") {
					$resultObj["keywords"][] = $mbMetadata->keywords[$i];
				}
			}
			$resultObj["keywords"] = implode(",",$resultObj["keywords"]);
			//responsible party information
			$resultObj["responsible_party_name"] = $mbMetadata->resourceResponsibleParty;
			$resultObj["responsible_party_email"] = $mbMetadata->resourceContactEmail;
			if ($resultObj["responsible_party_name"] != null || $resultObj["responsible_party_email"] != null ) {
				$resultObj["check_overwrite_responsible_party"] = true;
			}
			$resultObj["fkey_gui_id"] = $mbMetadata->fkeyGuiId;
			$resultObj["fkey_wmc_serial_id"] = $mbMetadata->fkeyWmcSerialId;
			$resultObj["fkey_mapviewer_id"] = $mbMetadata->fkeyMapviewerId;
			$resultObj["fkey_mb_group_id"] = $mbMetadata->fkey_mb_group_id;
			//give back result:

			$ajaxResponse->setResult($resultObj);
			$ajaxResponse->setSuccess(true);
			break;
		} else {
			//could not read metadata from db
			$ajaxResponse->setMessage(_mb("Could not get metadata object from database!"));
			$ajaxResponse->setSuccess(false);
			break;
		}
	case "getMetadataAddon" :
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->createFromDBInternalId($metadataId);
		if ($result) {
			//map metadata object to json return object
			$resultObj["metadata_id"]= $metadataId; //is not part of the object TODO!
			$resultObj["uuid"] = $mbMetadata->fileIdentifier; //char
			$resultObj["origin"] = $mbMetadata->origin; //char
			$resultObj["link"] = $mbMetadata->href; //char
			$resultObj["linktype"] = $mbMetadata->type; //char
			$resultObj["title"] = $mbMetadata->title; //char -- prefill from layer/ft
			$resultObj["alternate_title"] = $mbMetadata->alternate_title; //char -- prefill from layer/ft
			$resultObj["abstract"] = $mbMetadata->abstract; //char - prefill from layer/ft
			$resultObj["format"] = $mbMetadata->dataFormat; //char
			$resultObj["ref_system"] = $mbMetadata->refSystem; //char
			$resultObj["spatial_res_type"] = $mbMetadata->spatialResType; //integer
			$resultObj["spatial_res_value"] = $mbMetadata->spatialResValue; //char
			$resultObj["inspire_charset"] = $mbMetadata->inspireCharset; //char
			$resultObj["lineage"] = $mbMetadata->lineage; //text
			$resultObj["tmp_reference_1"] = $mbMetadata->tmpExtentBegin; //text
			$resultObj["tmp_reference_2"] = $mbMetadata->tmpExtentEnd; //text
			$resultObj["west"] = $mbMetadata->wgs84Bbox[0];
			$resultObj["south"] = $mbMetadata->wgs84Bbox[1];
			$resultObj["east"] = $mbMetadata->wgs84Bbox[2];
			$resultObj["north"] = $mbMetadata->wgs84Bbox[3];
			$resultObj["downloadlink"] = $mbMetadata->downloadLinks[0]; //only the first link!
			$resultObj["inspire_whole_area"] = $mbMetadata->inspireWholeArea;
			$resultObj["inspire_actual_coverage"] = $mbMetadata->inspireActualCoverage;
			$resultObj["preview_image"] = $mbMetadata->previewImage;
			$resultObj["overview_url"] = $mbMetadata->getExtentGraphic($mbMetadata->wgs84Bbox);
			$export2csw = $mbMetadata->export2Csw; //boolean
			$resultObj["update_frequency"] = $mbMetadata->updateFrequency; //text
			//check for existing polygon
			//$e = new mb_exception("mb_metadata_server.php: count of polygon points ".count($mbMetadata->polygonalExtentExterior));
			if (count($mbMetadata->polygonalExtentExterior) >= 1) {
				$e = new mb_notice("mb_metadata_server.php: count of polygon points ".count($mbMetadata->polygonalExtentExterior));
				$resultObj["has_polygon"] = true;
			} else {
				$resultObj["has_polygon"] = false;
			}
			switch ($export2csw) {
				case "t" :
					$resultObj["export2csw"] = true;
					$resultObj["export2csw2"] = true;
					break;
				case "f" :
					$resultObj["export2csw"] = false;
					$resultObj["export2csw2"] = false;
					break;
				default:
				break;
			}
			$inspire_top_consistence = $mbMetadata->inspireTopConsistence; //boolean
			switch ($inspire_top_consistence) {
				case "t" :
					$resultObj["inspire_top_consistence"] = true;
					break;
				case "f" :
					$resultObj["inspire_top_consistence"] = false;
					break;
				default:
				break;
			}
			$inspire_interoperability = $mbMetadata->inspireInteroperability; //boolean
			switch ($inspire_interoperability) {
				case "t" :
					$resultObj["inspire_interoperability"] = true;
					break;
				case "f" :
					$resultObj["inspire_interoperability"] = false;
					break;
				default:
				break;
			}
			$searchable = $mbMetadata->searchable; //boolean
			switch ($searchable) {
				case "t" :
					$resultObj["searchable"] = true;
					break;
				case "f" :
					$resultObj["searchable"] = false;
					break;
				default:
				break;
			}
			switch ($mbMetadata->inspireDownload) {
				case 0 :
					$resultObj["inspire_download"] = false;
					break;
				case 1 :
					$resultObj["inspire_download"] = true;
					break;
				default:
				break;
			}
			//things about licences
			$resultObj["fees_md"] = $mbMetadata->fees;
			$resultObj["accessconstraints_md"] = $mbMetadata->accessConstraints;
			$resultObj["md_termsofuse"] = $mbMetadata->termsOfUseRef;
			if ($resultObj["md_termsofuse"] == null || !isset($resultObj["md_termsofuse"])) {
				$resultObj["md_termsofuse"] == '0';
			}
			$resultObj["md_license_source_note"] = $mbMetadata->licenseSourceNote;
			//categories and keywords
			$resultObj["md_md_topic_category_id"] = $mbMetadata->isoCategories;
			$resultObj["md_custom_category_id"] = $mbMetadata->customCategories;
			$resultObj["md_inspire_category_id"] = $mbMetadata->inspireCategories;
			//only pull keywords without a thesaurus name!!
			for ($i = 0; $i < count($mbMetadata->keywords); $i++) {
				if ($mbMetadata->keywordsThesaurusName[$i] == "" or $mbMetadata->keywordsThesaurusName[$i] == "none") {
					$resultObj["keywords"][] = $mbMetadata->keywords[$i];
				}
			}
			$resultObj["keywords"] = implode(",",$resultObj["keywords"]);
			//responsible party information
			$resultObj["responsible_party_name"] = $mbMetadata->resourceResponsibleParty;
			$resultObj["responsible_party_email"] = $mbMetadata->resourceContactEmail;
			if ($resultObj["responsible_party_name"] != null || $resultObj["responsible_party_email"] != null ) {
				$resultObj["check_overwrite_responsible_party"] = true;
			}
			//give back result:
			$ajaxResponse->setResult($resultObj);
			$ajaxResponse->setSuccess(true);
			break;
		} else {
			//could not read metadata from db
			$ajaxResponse->setMessage(_mb("Could not get metadata object from database!"));
			$ajaxResponse->setSuccess(false);
			break;
		}
	case "getInitialResourceMetadata" :
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		switch ($resourceType) {
			case "layer":
				$sql = <<<SQL
SELECT layerpart.*, wms.accessconstraints, wms.fees FROM (SELECT layer_title, fkey_wms_id, layer_abstract, minx as west, miny as south, maxx as east, maxy as north FROM layer INNER JOIN layer_epsg ON layer.layer_id = layer_epsg.fkey_layer_id WHERE layer_id = $1 AND epsg = 'EPSG:4326') as layerpart INNER JOIN wms ON layerpart.fkey_wms_id = wms.wms_id
SQL;
				$v = array($resourceId);
				$t = array('i');
				$res = db_prep_query($sql,$v,$t);
				$row = array();
				if ($res) {
					$row = db_fetch_assoc($res);
					$resultObj["title"]= $row['layer_title']; //serial
					$resultObj["abstract"] = $row["layer_abstract"]; //char
					$resultObj["west"]= $row['west']; //double
					$resultObj["south"] = $row["south"]; //double
					$resultObj["east"]= $row['east']; //double
					$resultObj["north"] = $row["north"]; //double
					$resultObj["accessconstraints_md"] = $row["accessconstraints"]; //
					$resultObj["fees_md"] = $row["fees"]; //
				}
				$sql = <<<SQL
SELECT fkey_termsofuse_id FROM wms_termsofuse WHERE fkey_wms_id = $1;
SQL;
				$v = array($row['fkey_wms_id']);
				$t = array('i');
				$res = db_prep_query($sql,$v,$t);
				$row = array();
				if ($res) {
					$row = db_fetch_assoc($res);
					$resultObj["md_termsofuse"]= $row['fkey_termsofuse_id']; //serial
				}
			break;
			case "featuretype":
				$featuretypeId = $resourceId;
				//TODO: check like operator and ambigious wfs_featuretype_epsg - are they not deleted on update? Get rid of the limit 1
				$sql = <<<SQL
SELECT featuretype_title, featuretype_abstract, featuretype_latlon_bbox    
FROM  wfs_featuretype WHERE featuretype_id = $1
SQL;
				$v = array($featuretypeId);
				$t = array('i');
				$res = db_prep_query($sql,$v,$t);
				$row = array();
				if ($res) {
					$row = db_fetch_assoc($res);
					$resultObj["title"]= $row['featuretype_title']; //serial
					$resultObj["abstract"] = $row["featuretype_abstract"]; //char
					if (isset($resultObj["featuretype_latlon_bbox"]) && $resultObj["featuretype_latlon_bbox"] != '') {	
						$bbox = explode(',',$resultObj["featuretype_latlon_bbox"]);
					} else {
						$bbox = array(-180,-90,180,90);
					}
					$resultObj["west"] = $bbox[0]; //double
					$resultObj["south"] = $bbox[1]; //double
					$resultObj["east"]= $bbox[2]; //double
					$resultObj["north"] = $bbox[3]; //double
				}
			break;
		}
		//set export2csw and searchable always initially to true
		$resultObj["export2csw2"] = true; 
		$resultObj["export2csw"] = true; 
		$resultObj["searchable"] = true;
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "updateMetadataAddon" :
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		//get json data from ajax call
		$data = $ajaxResponse->getParameter("data");
		//initialize actual metadata object from db!
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->createFromDBInternalId($metadataId);
		if ($result) {
			if ($data->kindOfMetadataAddOn == 'link') {
				if ($data->export2csw) {
					$mbMetadata->export2Csw = 't';
				} else {
					$mbMetadata->export2Csw = 'f';
				}
			} else {
				if ($data->export2csw2) {
					$mbMetadata->export2Csw = 't';
				} else {
					$mbMetadata->export2Csw = 'f';
				}
			}
			if ($data->inspire_top_consistence) {
				$mbMetadata->inspireTopConsistence = 't';
			} else {
				$mbMetadata->inspireTopConsistence = 'f';
			}
			if ($data->inspire_interoperability) {
				$mbMetadata->inspireInteroperability = 't';
			} else {
				$mbMetadata->inspireInteroperability = 'f';
			}
			if ($data->searchable) {
				$mbMetadata->searchable = 't';
			} else {
				$mbMetadata->searchable = 'f';
			}
			if ($data->inspire_download) {
				$mbMetadata->inspireDownload = 1;
			} else {
				$mbMetadata->inspireDownload = 0;
			}
			//$mbMetadata->fileIdentifier = $metadataId;
			$mbMetadata->href = $data->link;
			$mbMetadata->title = $data->title;
			$mbMetadata->alternate_title = $data->alternate_title;
			$mbMetadata->abstract = $data->abstract;
			$mbMetadata->dataFormat = $data->format;
			$mbMetadata->refSystem = $data->ref_system;
			$mbMetadata->tmpExtentBegin = $data->tmp_reference_1;
			$mbMetadata->tmpExtentEnd = $data->tmp_reference_2;
			$mbMetadata->lineage = $data->lineage;
			//set origin always to metador - even if it was an uploaded record before!
			if ($data->kindOfMetadataAddOn == 'link') {
				$mbMetadata->origin = 'external';
			} else {
				$mbMetadata->origin = 'metador';
			}
			$mbMetadata->spatialResType = $data->spatial_res_type;
			$mbMetadata->spatialResValue = $data->spatial_res_value;
			$mbMetadata->inspireCharset = $data->inspire_charset;
			$mbMetadata->updateFrequency = $data->update_frequency;
			$mbMetadata->downloadLinks = array($data->downloadlink);
			//$mbMetadata->polygonalExtentExterior = null; //this will delete existing polygons!
			if (isset($data->inspire_whole_area) && $data->inspire_whole_area != "") {
				$mbMetadata->inspireWholeArea = $data->inspire_whole_area;
			} else {
				$mbMetadata->inspireWholeArea = 0;
			}
			if (isset($data->inspire_actual_coverage) && $data->inspire_actual_coverage != "") {
				$mbMetadata->inspireActualCoverage = $data->inspire_actual_coverage;
			} else {
				$mbMetadata->inspireActualCoverage = 0;
			}
			//categories ...
			//new for keywords and classifications:
			if (isset($data->keywords) && $data->keywords != "") {
				$mbMetadata->keywords = array_map('trim',explode(',',$data->keywords));
				//for all those keywords don't set a special thesaurus name
				foreach ($mbMetadata->keywords as $keyword) {
					$mbMetadata->keywordsThesaurusName[] = "none";
				}
			}
			if (isset($data->md_md_topic_category_id)) {
				$mbMetadata->isoCategories = $data->md_md_topic_category_id;
			} else {
				$mbMetadata->isoCategories = array();
			}
			if (isset($data->md_inspire_category_id)) {
				$mbMetadata->inspireCategories = $data->md_inspire_category_id;
			} else {
				$mbMetadata->inspireCategories = array();
			}
			if (isset($data->md_custom_category_id)) {
				$mbMetadata->customCategories = $data->md_custom_category_id;
			} else {
				$mbMetadata->customCategories = array();
			}
			//use information from bbox!
			if (isset($data->west)) {
				$mbMetadata->wgs84Bbox[0] = $data->west;
			}
			if (isset($data->east)) {
				$mbMetadata->wgs84Bbox[2] = $data->east;
			}
			if (isset($data->north)) {
				$mbMetadata->wgs84Bbox[3] = $data->north;
			}
			if (isset($data->south)) {
				$mbMetadata->wgs84Bbox[1] = $data->south;
			}
			if (isset($data->fees_md)) {
				$mbMetadata->fees = $data->fees_md;
			}
			if (isset($data->accessconstraints_md)) {
				$mbMetadata->accessConstraints = $data->accessconstraints_md;
			}
			//$e = new mb_exception($data->md_termsofuse);
			if (isset($data->md_termsofuse) && $data->md_termsofuse !=='0' && $data->md_termsofuse !== 0) {
				$mbMetadata->termsOfUseRef = $data->md_termsofuse;
			} else {
				$mbMetadata->termsOfUseRef = null;
			}
			if (isset($data->md_license_source_note)) {
				$mbMetadata->licenseSourceNote = $data->md_license_source_note;
			}
			if (isset($data->preview_image) && $data->preview_image !=='' ) {
				$mbMetadata->previewImage = $data->preview_image;
			}
			//overwrite responsible party info if wished
			if ($data->check_overwrite_responsible_party) {
				if ($data->responsible_party_name !== "") {
					$mbMetadata->resourceResponsibleParty = $data->responsible_party_name;
				} else {
					$mbMetadata->resourceResponsibleParty = "Empty value for responsible party name!";
				}
				if ($data->responsible_party_email !== "") {
					$mbMetadata->resourceContactEmail = $data->responsible_party_email;
				} else {
					$mbMetadata->resourceContactEmail = "Empty value for responsible party email!";
				}
			} else {
				$mbMetadata->resourceResponsibleParty = null;
				$mbMetadata->resourceContactEmail = null;
			}
			//try to update metadata object (only mb_metadata)
			$res = $mbMetadata->updateMetadataById($metadataId);
			if (!$res) {
				//could not update metadata in db
				$ajaxResponse->setMessage(_mb("Could not update metadata object in database!"));
				$ajaxResponse->setSuccess(false);
			} else {
				//update relations for keywords and categories
				$mbMetadata->insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId);
				$ajaxResponse->setMessage(_mb("Edited metadata was updated in the mapbender database!"));
				$ajaxResponse->setSuccess(true);
			}
		} else {
			//could not read metadata from db
			$ajaxResponse->setMessage(_mb("Could not get metadata object from database!"));
			$ajaxResponse->setSuccess(false);
		}
		break;
	case "getOwnedMetadata" :
		$user = new User(Mapbender::session()->get("mb_user_id"));
		$sql = "SELECT metadata_id, title FROM mb_metadata WHERE fkey_mb_user_id = $1 ORDER BY metadata_id DESC";
		$v = array($user->id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		$resultObj = array();
		$i = 0;
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$i]->metadataId = $row['metadata_id']; //integer
			$resultObj[$i]->metadataTitle = $row["title"]; //char
			$i++;
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "getOwnedApplicationMetadata" :
		$user = new User(Mapbender::session()->get("mb_user_id"));
		$sql = "SELECT metadata_id, title FROM mb_metadata WHERE fkey_mb_user_id = $1 & type = 'application' ORDER BY metadata_id DESC";
		$v = array($user->id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$row = array();
		$resultObj = array();
		$i = 0;
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$i]->metadataId = $row['metadata_id']; //integer
			$resultObj[$i]->metadataTitle = $row["title"]; //char
			$i++;
		}
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);
		break;
	case "insertMetadataAddon" :
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		$data = $ajaxResponse->getParameter("data");
		//normaly the link is only set if a link has been created
		//if a record has been created, the link element will be empty
		//use this to distinguish between the to different inserts!
		//this insert should also push one entry in the ows_relation_metadata table! - after the insert into md_metadata
		//origin
		if ($data->kindOfMetadataAddOn == 'internallink') {
			//instantiate existing metadata
			$mbMetadata = new Iso19139();
			$mbMetadata->createFromDBInternalId($data->internal_relation);
			//$e = new mb_exception("plugins/mb_metadata_server.php: created object from db with id: ".$data->internal_relation);
			$result = $mbMetadata->setInternalMetadataLinkage($data->internal_relation,$resourceType,$resourceId);
			//insert a simple relation to an internal metadata entry - but how can this be distinguished?
			//we need a new column with type of relation - maybe called internal
			$ajaxResponse->setMessage($result['message']);
			$ajaxResponse->setSuccess($result['success']);
			//go out here
			break;
		}
		//$e = new mb_exception("outside case");
		if ($data->kindOfMetadataAddOn == 'link') {
			//generate metador entry
			$origin = 'external';
			//export
			if ($data->export2csw == "on") {
				$data->export2csw = 't';
			} else {
				$data->export2csw = 'f';
			}
		} else {
			if ($data->export2csw2 == "on") {
				$data->export2csw = 't';
			} else {
				$data->export2csw = 'f';
			}
			$origin = 'metador';
		}
		//give 
		//consistence
		if ($data->inspire_top_consistence == "on") {
			$data->inspire_top_consistence = 't';
		} else {
			$data->inspire_top_consistence = 'f';
		}
		//interoperability
		if ($data->inspire_interoperability == "on") {
			$data->inspire_interoperability = 't';
		} else {
			$data->inspire_interoperability = 'f';
		}
		//searchable
		if ($data->searchable == "on") {
			$data->searchable = 't';
		} else {
			$data->searchable = 'f';
		}
		//generate a uuid for the record:
		$uuid = new Uuid();
		//initialize database objects
		//are initialized from class_iso19139
		$mbMetadata = new Iso19139();
		$randomid = new Uuid();
		//read out json objects
		if (isset($data->link)) {
			$mbMetadata->href = $data->link;
		}
		if (isset($data->export2csw)) {
			$mbMetadata->export2Csw = $data->export2csw;
		} else {
			$mbMetadata->export2Csw = 'f';
		}
		if (isset($data->title)) {
			$mbMetadata->title = $data->title;
		}
		if (isset($data->alternate_title)) {
		    $mbMetadata->alternate_title = $data->alternate_title;
		}
		if (isset($data->abstract)) {
			$mbMetadata->abstract = $data->abstract;
		}
		if (isset($data->format)) {
			$mbMetadata->dataFormat = $data->format;
		}
		if (isset($data->ref_system)) {
			$mbMetadata->refSystem = $data->ref_system;
		}
		if (isset($data->inspire_top_consistence)) {
			$mbMetadata->inspireTopConsistence = $data->inspire_top_consistence;
		} else {
			$mbMetadata->inspireTopConsistence = "f";
		}
		if (isset($data->inspire_interoperability)) {
			$mbMetadata->inspireInteroperability = $data->inspire_interoperability;
		} else {
			$mbMetadata->inspireInteroperability = "f";
		}
		if (isset($data->searchable)) {
			$mbMetadata->searchable = $data->searchable;
		} else {
			$mbMetadata->searchable = "f";
		}
		if (isset($data->tmp_reference_1)) {
			$mbMetadata->tmpExtentBegin = $data->tmp_reference_1;
		}
		if (isset($data->tmp_reference_2)) {
			$mbMetadata->tmpExtentEnd = $data->tmp_reference_2;
		}
		if (isset($data->lineage)) {
			$mbMetadata->lineage = $data->lineage;
		}
		if (isset($data->spatial_res_type)) {
			$mbMetadata->spatialResType = $data->spatial_res_type;
		}
		if (isset($data->spatial_res_value)) {
			$mbMetadata->spatialResValue = $data->spatial_res_value;
		}
		if (isset($data->inspire_charset)) {
			$mbMetadata->inspireCharset = $data->inspire_charset;
		}
		if (isset($data->update_frequency)) {
			$mbMetadata->updateFrequency = $data->update_frequency;
		}
		if (isset($data->update_frequency)) {
			$mbMetadata->downloadLinks = array($data->downloadlink);
		}
		//new for keywords and classifications:
		if (isset($data->keywords) && $data->keywords != "") {
			$mbMetadata->keywords = array_map('trim',explode(',',$data->keywords));
			//for all those keywords don't set a special thesaurus name
			foreach ($mbMetadata->keywords as $keyword) {
				$mbMetadata->keywordsThesaurusName[] = "none";
			}
		}
		if (isset($data->md_md_topic_category_id)) {
			$mbMetadata->isoCategories = $data->md_md_topic_category_id;
		}
		if (isset($data->md_inspire_category_id)) {
			$mbMetadata->inspireCategories = $data->md_inspire_category_id;
		}
		if (isset($data->md_custom_category_id)) {
			$mbMetadata->customCategories = $data->md_custom_category_id;
		}
		//use information from bbox!
		if (isset($data->west)) {
			$mbMetadata->wgs84Bbox[0] = $data->west;
		}
		if (isset($data->east)) {
			$mbMetadata->wgs84Bbox[2] = $data->east;
		}
		if (isset($data->north)) {
			$mbMetadata->wgs84Bbox[3] = $data->north;
		}
		if (isset($data->south)) {
			$mbMetadata->wgs84Bbox[1] = $data->south;
		}
		$e = new mb_exception("whole area: ".$data->inspire_whole_area);
		if (isset($data->inspire_whole_area) && $data->inspire_whole_area != "") {
			$mbMetadata->inspireWholeArea = $data->inspire_whole_area;
		} else {
			$mbMetadata->inspireWholeArea = 0;
		}
		if (isset($data->inspire_actual_coverage) && $data->inspire_actual_coverage != "") {
			$mbMetadata->inspireActualCoverage = $data->inspire_actual_coverage;
		} else {
			$mbMetadata->inspireActualCoverage = 0;
		}
		if ($data->inspire_download == "on") {
			$mbMetadata->inspireDownload = 1;
		} else {
			$mbMetadata->inspireDownload = 0;
		}
		if (isset($data->fees_md)) {
			$mbMetadata->fees = $data->fees_md;
		}
		if (isset($data->accessconstraints_md)) {
			$mbMetadata->accessConstraints = $data->accessconstraints_md;
		}
		if (isset($data->md_termsofuse) && $data->md_termsofuse !=='0') {
			$mbMetadata->termsOfUseRef = $data->md_termsofuse;
		}
		if (isset($data->preview_image) && $data->preview_image !=='' ) {
			$mbMetadata->previewImage = $data->preview_image;
		}
		//overwrite responsible party info if wished
		if ($data->check_overwrite_responsible_party) {
			if ($data->responsible_party_name !== "") {
				$mbMetadata->resourceResponsibleParty = $data->responsible_party_name;
			} else {
				$mbMetadata->resourceResponsibleParty = "Empty value for responsible party name!";
			}
			if ($data->responsible_party_email !== "") {
				$mbMetadata->resourceContactEmail = $data->responsible_party_email;
			} else {
				$mbMetadata->resourceContactEmail = "Empty value for responsible party email!";
			}
		} else {
			$mbMetadata->resourceResponsibleParty = null;
			$mbMetadata->resourceContactEmail = null;
		}
		//Check if origin is external and export2csw is activated!
		if ($origin == 'external' ) {
			//harvest link from location, parse the content for datasetid and push xml into data column
			$mdOwner = Mapbender::session()->get("mb_user_id");
			$mbMetadata->randomId = $randomid;
			$mbMetadata->format = "text/xml";
			$mbMetadata->type = "ISO19115:2003";
			$mbMetadata->origin = "external";
			$mbMetadata->owner = $mdOwner;
			$result = $mbMetadata->insertToDB($resourceType,$resourceId);
			if ($result['value'] == false){
				$e = new mb_exception("Problem while storing metadata to mb_metadata table!");
				$e = new mb_exception($result['message']);
				abort($result['message']);
			} else {
				$ajaxResponse->setMessage("Stored metadata from external link to mapbender database!");
				$ajaxResponse->setSuccess(true);
				$e = new mb_notice("Stored metadata from external link to mapbender database!");
			}
		} else { //fill thru metador
			$mdOwner = Mapbender::session()->get("mb_user_id");
			$mbMetadata->owner = $mdOwner;
			$mbMetadata->origin = "metador";
			$mbMetadata->fileIdentifier = $uuid;
			$mbMetadata->randomId = $randomid;
			$result = $mbMetadata->insertToDB($resourceType,$resourceId);
			$e = new mb_exception("test to metadata insert/update via metador!");
			if ($result['value'] == false) {
				$e = new mb_exception("Problem while storing metadata from editor to mb_metadata table!");
				$e = new mb_exception($result['message']);
				abort($result['message']);
			} else {
				$e = new mb_notice("Metadata with id ".$randomid." stored from editor to db!");
				$ajaxResponse->setMessage("Metadata with id ".$randomid." stored from editor to db!");
				$ajaxResponse->setSuccess(true);
			}
		}
		break;
	case "deleteInternalMetadataLinkage" :
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->deleteInternalMetadataLinkage($resourceType, $resourceId, $metadataId);
		$ajaxResponse->setSuccess($result['success']);
		$ajaxResponse->setMessage($result['message']);
		break;
	case "deleteMetadataAddon" :
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->deleteMetadataAddon($resourceType, $resourceId, $metadataId); //$contentType = "layer" or "featuretype" or ...
		$ajaxResponse->setSuccess($result['success']);
		$ajaxResponse->setMessage($result['message']);
		break;
	case "importGmlAddon":
		$filename = $ajaxResponse->getParameter("filename");
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$gml = file_get_contents($filename);
		if (!$gml){
			abort(_mb("Reading file ".$filename." failed!"));
		}
		$wktPolygon = gml2wkt($gml);
		if ($wktPolygon) {
			//insert polygon into database
			$sql = <<<SQL
UPDATE mb_metadata SET bounding_geom = $2 WHERE metadata_id = $1
SQL;
			$v = array($metadataId, $wktPolygon);
			//$e = new mb_exception($metadataId);
			$t = array('i','POLYGON');
			$res = db_prep_query($sql,$v,$t);
			if (!$res) {
				abort(_mb("Problem while storing geometry into database!"));
			} else {
				//build new preview url if possible and give it back in ajax response

				$ajaxResponse->setMessage("Stored successfully geometry into database!");
				$ajaxResponse->setSuccess(true);
			}
		} else {
			abort(_mb("Converting GML to WKT failed!"));
		}
		//parse gml and extract multipolygon to wkt representation
		//push multipolygon into database

	break;
	case "importPreview":
		$filename = $ajaxResponse->getParameter("filename");
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$preview = file_get_contents($filename); // store file with random file name in tmp folder
		if (!$preview){
			abort(_mb("Reading file ".$filename." from mapbenders tmp folder failed!"));
		}
		//store image to preview folder
		if (defined('PREVIEW_DIR') && PREVIEW_DIR != '') {
		    $new_name = $metadataId."_metadata_preview.jpg";
		    // get the file informations
		    $info = pathinfo($filename);
		    // get the extension of the file
		    $ext = $info['extension'];
		    $new_image = dirname(__FILE__)."/".PREVIEW_DIR."/".$new_name;
		    // get the ímage
		    $image = $filename;
		    //resize the image to 200px * 200px
		    // get image size
		    $size = Getimagesize($filename);
		    $images_orig;
		    //create an gd-image-object from the source file
		    switch (strtolower($ext)) {
			case 'jpg':
			    $images_orig = ImageCreateFromJPEG($image);
			    break;
			case 'jpeg':
			    $images_orig = ImageCreateFromJPEG($image);
			    break;
			case 'png':
			    $images_orig = ImageCreateFrompng($image);
			    break;
			case 'gif':
			    $images_orig = ImageCreateFromgif($image);
			    break;
			default:
			    return;
			    break;
		    }
		    //create image and resample it
		    if ($size[0] >= 200 || $size[1] >= 200) {
			// width of the origin image
			$photoW = ImagesX($images_orig);
			// height of the origin image
			$photoH = ImagesY($images_orig);
			// create new image with the calculated size
			$images_target = ImageCreateTrueColor(200, 200);
			//fill the new image with transparency background
			$color = imagecolorallocatealpha($images_target, 255, 255, 255, 0); //fill white background
			imagefill($images_target, 0, 0, $color);
			imagealphablending( $images_target, false );
			imagesavealpha($images_target, true);
			//set the new image width and height
			if ($size[0] > $size[1] || $size[0] == $size[1]) {
			    $width = 200;
			    $height = round($width*$size[1]/$size[0]);
			    // calculate the height of the src_image in the target_image
			    $startHeight = round((200-$height)/2);
			    // resize the image:
			    ImageCopyResampled($images_target, $images_orig, 0, $startHeight, 0, 0, $width, $height, $photoW, $photoH);
			} else {
			    $height = 200;
			    $width = round($height*$size[0]/$size[1]);
			    $startWidth = round((200-$width)/2);
			    ImageCopyResampled($images_target, $images_orig, $startWidth, 0, 0, 0, $width, $height, $photoW, $photoH);
			}
			// move File to the new target directory --> always save as png
			imagejpeg($images_target,$new_image);
			// free space
			ImageDestroy($images_orig);
			ImageDestroy($images_target);
		    // if image-width and height are to small
		    } else if($size[0] < 200 && $size[1] < 200) {
			//set the new image width
			$width = $size[0];
			// scale the height
			$height = $size[1];
			// width of the origin image
			$photoW = ImagesX($images_orig);
			// height of the origin image
			$photoH = ImagesY($images_orig);
			// create new image with the calculated size
			$images_target = ImageCreateTrueColor(200, 200);
			//fill the new image with transparency background
			$color = imagecolorallocatealpha($images_target, 255, 255, 255, 0); //fill white background
			imagefill($images_target, 0, 0, $color);
			imagealphablending( $images_target, false );
			imagesavealpha($images_target, true);
			// calculate the height of the src_image in the target_image
			$startHeight = round((200-$height)/2);
			$startWidth = round((200-$width)/2);
			// resize the image
			ImageCopyResampled($images_target, $images_orig, $startWidth, $startHeight, 0, 0, $width, $height, $photoW, $photoH);
			// move File to the new target directory --> always save as png
			imagejpeg($images_target,$new_image);
			// free space
			ImageDestroy($images_orig);
			ImageDestroy($images_target);
		    }
		}
		if (!file_get_contents($new_image)) {
		    $ajaxResponse->setMessage("The preview image could not be stored - some error occured!");
		    $ajaxResponse->setSuccess(false);
		} else {
		    $sql = "UPDATE mb_metadata SET preview_image = '{localstorage}' WHERE metadata_id = $1";
		    $v = array($metadataId);
		    $t = array('i');
		    $res = db_prep_query($sql,$v,$t);
		    if (!$res) {
		    	$ajaxResponse->setMessage("The preview image has been stored under ".$new_image.", but the database record could not be updated!");
		    	$ajaxResponse->setSuccess(false);
		    } else {
		    	$ajaxResponse->setMessage("The preview image has been stored under ".$new_image." and the preview_image column of the database record has been set to '{localstorage}'");
		    	$ajaxResponse->setSuccess(true);
		    }
		}
		//save {localstorage} to database field if image stored successfull
		
		/*if ($wktPolygon) {
			//insert polygon into database
			$sql = <<<SQL
UPDATE mb_metadata SET bounding_geom = $2 WHERE metadata_id = $1
SQL;
			$v = array($metadataId, $wktPolygon);
			//$e = new mb_exception($metadataId);
			$t = array('i','POLYGON');
			$res = db_prep_query($sql,$v,$t);
			if (!$res) {
				abort(_mb("Problem while storing geometry into database!"));
			} else {
				//build new preview url if possible and give it back in ajax response

				$ajaxResponse->setMessage("Stored successfully geometry into database!");
				$ajaxResponse->setSuccess(true);
			}
		} else {
			abort(_mb("Converting GML to WKT failed!"));
		}*/
		//parse gml and extract multipolygon to wkt representation
		//push multipolygon into database
		break;
	case "deletePreview":
		$metadataId = $ajaxResponse->getParameter("metadataId");
		if (defined('PREVIEW_DIR') && PREVIEW_DIR != '') {
		    $previewName = $metadataId."_metadata_preview.jpg";
		    $previewPath =  dirname(__FILE__)."/".PREVIEW_DIR."/".$previewName;
		    if (file_exists($previewPath)) {
		        unlink($previewPath);
			//delete {localstorage} from mb_metadata.preview_image
			$sql = "UPDATE mb_metadata SET preview_image = '' WHERE metadata_id = $1";
			$v = array($metadataId);
			$t = array('i');
			db_prep_query($sql,$v,$t);
			$ajaxResponse->setMessage("Preview for metadata with id ".$metadataId." successfully deleted!");
		        $ajaxResponse->setSuccess(true);
		    } else {
			$ajaxResponse->setMessage("Preview file does not exists in folder!");
		        $ajaxResponse->setSuccess(false);
		    }
		} else {
		    $ajaxResponse->setMessage("No PREVIEW_DIR defined - cannot delete preview!");
		    $ajaxResponse->setSuccess(false);
		}
		break;
	case "getPreviewUrl":		
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$admin = new administration();
		$url = $admin->getMetadataPreviewUrl($metadataId);
		$resultObj['preview_url'] = $url;
		$ajaxResponse->setMessage("Preview url found: ".$url);
		$ajaxResponse->setSuccess(true);
		$ajaxResponse->setResult($resultObj);
		break;
	case "deleteGmlPolygon" :
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$e = new mb_notice("metadataId: ".$metadataId);
		$sql = <<<SQL
UPDATE mb_metadata SET bounding_geom = NULL WHERE metadata_id = $1
SQL;
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			abort(_mb("Problem while deleting geometry from database!"));
		} else {
			$ajaxResponse->setMessage("Deleted surrounding geometry from metadata record!");
			$ajaxResponse->setSuccess(true);
		}
	break;
	case "insertMetadata" :
		//$resourceId = $ajaxResponse->getParameter("resourceId");
//TODO - this function is defined for application metadata first - have to be extended to be used for other types dataset/series ...2019-10-07
		$resourceType = $ajaxResponse->getParameter("resourceType");
		$data = $ajaxResponse->getParameter("data");
		if ($data->export2csw == "on") {
			$data->export2csw = 't';
		} else {
			$data->export2csw = 'f';
		}
		$origin = 'metador';
		//searchable
		if ($data->searchable == "on") {
			$data->searchable = 't';
		} else {
			$data->searchable = 'f';
		}
		//generate a uuid for the record:
		$uuid = new Uuid();
		//initialize database objects
		//are initialized from class_iso19139
		$mbMetadata = new Iso19139();
		$randomid = new Uuid();
$mbMetadata->hierarchyLevel = $resourceType;
		//read out json objects
		if (isset($data->link)) {
			$mbMetadata->href = $data->link;
		}
		if (isset($data->export2csw)) {
			$mbMetadata->export2Csw = $data->export2csw;
		} else {
			$mbMetadata->export2Csw = 'f';
		}

		if (isset($data->title)) {
			$mbMetadata->title = $data->title;
		}
		if (isset($data->abstract)) {
			$mbMetadata->abstract = $data->abstract;
		}
		if (isset($data->ref_system)) {
			$mbMetadata->refSystem = $data->ref_system;
		}
		if (isset($data->searchable)) {
			$mbMetadata->searchable = $data->searchable;
		} else {
			$mbMetadata->searchable = "f";
		}
		if (isset($data->tmp_reference_1)) {
			$mbMetadata->tmpExtentBegin = $data->tmp_reference_1;
		}
		if (isset($data->tmp_reference_2)) {
			$mbMetadata->tmpExtentEnd = $data->tmp_reference_2;
		}
		if (isset($data->lineage)) {
			$mbMetadata->lineage = $data->lineage;
		}
		if (isset($data->update_frequency)) {
			$mbMetadata->updateFrequency = $data->update_frequency;
		}
		if (isset($data->update_frequency)) {
			$mbMetadata->downloadLinks = array($data->downloadlink);
		}
		//new for keywords and classifications:
		if (isset($data->keywords) && $data->keywords != "") {
			$mbMetadata->keywords = array_map('trim',explode(',',$data->keywords));
			//for all those keywords don't set a special thesaurus name
			foreach ($mbMetadata->keywords as $keyword) {
				$mbMetadata->keywordsThesaurusName[] = "none";
			}
		}
		if (isset($data->md_md_topic_category_id)) {
			$mbMetadata->isoCategories = $data->md_md_topic_category_id;
		}
		if (isset($data->md_inspire_category_id)) {
			$mbMetadata->inspireCategories = $data->md_inspire_category_id;
		}
		if (isset($data->md_custom_category_id)) {
			$mbMetadata->customCategories = $data->md_custom_category_id;
		}
		//use information from bbox!
		if (isset($data->west)) {
			$mbMetadata->wgs84Bbox[0] = $data->west;
		}
		if (isset($data->east)) {
			$mbMetadata->wgs84Bbox[2] = $data->east;
		}
		if (isset($data->north)) {
			$mbMetadata->wgs84Bbox[3] = $data->north;
		}
		if (isset($data->south)) {
			$mbMetadata->wgs84Bbox[1] = $data->south;
		}
		if (isset($data->fees_md)) {
			$mbMetadata->fees = $data->fees_md;
		}
		if (isset($data->accessconstraints_md)) {
			$mbMetadata->accessConstraints = $data->accessconstraints_md;
		}
		if (isset($data->md_termsofuse) && $data->md_termsofuse !=='0') {
			$mbMetadata->termsOfUseRef = $data->md_termsofuse;
		}
		if (isset($data->preview_image) && $data->preview_image !=='' ) {
			$mbMetadata->previewImage = $data->preview_image;
		}
		//overwrite responsible party info if wished
		if ($data->check_overwrite_responsible_party) {
			if ($data->responsible_party_name !== "") {
				$mbMetadata->resourceResponsibleParty = $data->responsible_party_name;
			} else {
				$mbMetadata->resourceResponsibleParty = "Empty value for responsible party name!";
			}
			if ($data->responsible_party_email !== "") {
				$mbMetadata->resourceContactEmail = $data->responsible_party_email;
			} else {
				$mbMetadata->resourceContactEmail = "Empty value for responsible party email!";
			}
		} else {
			$mbMetadata->resourceResponsibleParty = null;
			$mbMetadata->resourceContactEmail = null;
		}
		//for foreign keys - how to invoke application 
		if (isset($data->fkey_gui_id) && $data->fkey_gui_id != '') {
			$mbMetadata->fkeyGuiId = $data->fkey_gui_id;
		}
		if (isset($data->fkey_wmc_serial_id) && $data->fkey_wmc_serial_id != '') {
			$mbMetadata->fkeyWmcSerialId = $data->fkey_wmc_serial_id;
		}
		if (isset($data->fkey_mapviewer_id) && $data->fkey_mapviewer_id != '') {
			$mbMetadata->fkeyMapviewerId = $data->fkey_mapviewer_id;
		}
		//fill thru metador
		$mdOwner = Mapbender::session()->get("mb_user_id");
		$mbMetadata->owner = $mdOwner;
		//fkey_mb_group_id
                if (isset($data->fkey_mb_group_id) && $data->fkey_mb_group_id != '') {
                	$mbMetadata->fkey_mb_group_id = $data->fkey_mb_group_id;
                } else {
			$mbMetadata->fkey_mb_group_id = null;
		}
		$mbMetadata->origin = "metador";
		$mbMetadata->fileIdentifier = $uuid;
		$mbMetadata->randomId = $randomid;
		$result = $mbMetadata->insertToDB($resourceType,$resourceId);
		$e = new mb_exception("test to metadata insert/update via metador!");
		if ($result['value'] == false) {
			$e = new mb_exception("Problem while storing metadata from editor to mb_metadata table!");
			$e = new mb_exception($result['message']);
			abort($result['message']);
		} else {
			$e = new mb_notice("Metadata with id ".$randomid." stored from editor to db!");
			$ajaxResponse->setMessage("Metadata with id ".$randomid." stored from editor to db!");
			$ajaxResponse->setSuccess(true);
		}
		break;
	case "updateMetadata" :
		//TODO - only for application metadata at the time 2019-10-07
		$metadataId = $ajaxResponse->getParameter("metadataId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		//get json data from ajax call
		$data = $ajaxResponse->getParameter("data");
		//initialize actual metadata object from db!
		$mbMetadata = new Iso19139();
		$result = $mbMetadata->createFromDBInternalId($metadataId);
		if ($result) {
			if ($data->export2csw) {
				$mbMetadata->export2Csw = 't';
			} else {
				$mbMetadata->export2Csw = 'f';
			}
			if ($data->searchable) {
				$mbMetadata->searchable = 't';
			} else {
				$mbMetadata->searchable = 'f';
			}
			if ($data->inspire_download) {
				$mbMetadata->inspireDownload = 1;
			} else {
				$mbMetadata->inspireDownload = 0;
			}
			$mbMetadata->href = $data->link;
			$mbMetadata->title = $data->title;
			$mbMetadata->abstract = $data->abstract;
			$mbMetadata->refSystem = $data->ref_system;
			$mbMetadata->tmpExtentBegin = $data->tmp_reference_1;
			$mbMetadata->tmpExtentEnd = $data->tmp_reference_2;
			$mbMetadata->lineage = $data->lineage;
			$mbMetadata->origin = 'metador';
			$mbMetadata->hierarchyLevel = $resourceType;
			$mbMetadata->inspireCharset = $data->inspire_charset;
			$mbMetadata->updateFrequency = $data->update_frequency;
			//categories ...
			//new for keywords and classifications:
			if (isset($data->keywords) && $data->keywords != "") {
				$mbMetadata->keywords = array_map('trim',explode(',',$data->keywords));
				//for all those keywords don't set a special thesaurus name
				foreach ($mbMetadata->keywords as $keyword) {
					$mbMetadata->keywordsThesaurusName[] = "none";
				}
			}
			if (isset($data->md_md_topic_category_id)) {
				$mbMetadata->isoCategories = $data->md_md_topic_category_id;
			} else {
				$mbMetadata->isoCategories = array();
			}
			if (isset($data->md_inspire_category_id)) {
				$mbMetadata->inspireCategories = $data->md_inspire_category_id;
			} else {
				$mbMetadata->inspireCategories = array();
			}
			if (isset($data->md_custom_category_id)) {
				$mbMetadata->customCategories = $data->md_custom_category_id;
			} else {
				$mbMetadata->customCategories = array();
			}
			//use information from bbox!
			if (isset($data->west)) {
				$mbMetadata->wgs84Bbox[0] = $data->west;
			}
			if (isset($data->east)) {
				$mbMetadata->wgs84Bbox[2] = $data->east;
			}
			if (isset($data->north)) {
				$mbMetadata->wgs84Bbox[3] = $data->north;
			}
			if (isset($data->south)) {
				$mbMetadata->wgs84Bbox[1] = $data->south;
			}
			if (isset($data->fees_md)) {
				$mbMetadata->fees = $data->fees_md;
			}
			if (isset($data->accessconstraints_md)) {
				$mbMetadata->accessConstraints = $data->accessconstraints_md;
			}
			//$e = new mb_exception($data->md_termsofuse);
			if (isset($data->md_termsofuse) && $data->md_termsofuse !=='0' && $data->md_termsofuse !== 0) {
				$mbMetadata->termsOfUseRef = $data->md_termsofuse;
			} else {
				$mbMetadata->termsOfUseRef = null;
			}
			if (isset($data->md_license_source_note)) {
				$mbMetadata->licenseSourceNote = $data->md_license_source_note;
			}
			if (isset($data->preview_image) && $data->preview_image !=='' ) {
				$mbMetadata->previewImage = $data->preview_image;
			}
			//overwrite responsible party info if wished
			if ($data->check_overwrite_responsible_party) {
				if ($data->responsible_party_name !== "") {
					$mbMetadata->resourceResponsibleParty = $data->responsible_party_name;
				} else {
					$mbMetadata->resourceResponsibleParty = "Empty value for responsible party name!";
				}
				if ($data->responsible_party_email !== "") {
					$mbMetadata->resourceContactEmail = $data->responsible_party_email;
				} else {
					$mbMetadata->resourceContactEmail = "Empty value for responsible party email!";
				}
			} else {
				$mbMetadata->resourceResponsibleParty = null;
				$mbMetadata->resourceContactEmail = null;
			}
			//for foreign keys - how to invoke application 
			if (isset($data->fkey_gui_id) && $data->fkey_gui_id != '') {
				$mbMetadata->fkeyGuiId = $data->fkey_gui_id;
			} else {
				$mbMetadata->fkeyGuiId = null;
			}
			if (isset($data->fkey_wmc_serial_id) && $data->fkey_wmc_serial_id != '') {
				$mbMetadata->fkeyWmcSerialId = $data->fkey_wmc_serial_id;
			} else {
				$mbMetadata->fkeyWmcSerialId = null;
			}
			if (isset($data->fkey_mapviewer_id) && $data->fkey_mapviewer_id != '') {
				$mbMetadata->fkeyMapviewerId = $data->fkey_mapviewer_id;
			} else {
				$mbMetadata->fkeyMapviewerId = null;
			}
                        if (isset($data->fkey_mb_group_id) && $data->fkey_mb_group_id != '' && $data->fkey_mb_group_id != 0  && $data->fkey_mb_group_id != '0') {
                	    $mbMetadata->fkey_mb_group_id = $data->fkey_mb_group_id;
                        } else {
			    $mbMetadata->fkey_mb_group_id = null;
			}

			//try to update metadata object (only mb_metadata)
			$res = $mbMetadata->updateMetadataById($metadataId);
			if (!$res) {
				//could not update metadata in db
				$ajaxResponse->setMessage(_mb("Could not update metadata object in database!"));
				$ajaxResponse->setSuccess(false);
			} else {
				//update relations for keywords and categories
				$mbMetadata->insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId);
				$ajaxResponse->setMessage(_mb("Edited metadata was updated in the mapbender database!"));
				$ajaxResponse->setSuccess(true);
			}
		} else {
			//could not read metadata from db
			$ajaxResponse->setMessage(_mb("Could not get metadata object from database!"));
			$ajaxResponse->setSuccess(false);
		}
		break;
	case "importXmlAddon" :
		//this case is similar to insert the metadata from external link, but came from internal file from tmp folder which has been uploaded before
		$resourceId = $ajaxResponse->getParameter("resourceId");
		$resourceType = $ajaxResponse->getParameter("resourceType");
		$filename = $ajaxResponse->getParameter("filename");
		//normaly the link is only set if a link has been created
		//if a record has been created, the link element will be empty
		//use this to distinguish between the to different inserts!
		//this insert should also push one entry in the ows_relation_metadata table! - after the insert into md_metadata
		$randomid = new Uuid();
		$e = new mb_notice("File to load: ".$filename);
		$metaData = file_get_contents($filename);
		if (!$metaData){
			abort(_mb("Reading file ".$filename." failed!"));
		}
		$mbMetadata = new Iso19139();
		$mdOwner = Mapbender::session()->get("mb_user_id");
		$mbMetadata->randomId = $randomid;
		$mbMetadata->metadata = $metaData;
		$mbMetadata->format = "text/xml";
		$mbMetadata->type = "ISO19115:2003";
		$mbMetadata->origin = "upload";
		$mbMetadata->owner = $mdOwner;
		$result = $mbMetadata->insertToDB($resourceType,$resourceId);
		if ($result['value'] == false){
			$e = new mb_exception("Problem while storing uploaded metadata xml to mb_metadata table!");
			$e = new mb_exception($result['message']);
			abort($result['message']);

		} else {
			$e = new mb_notice("Metadata with random id ".$randomid." stored to db!");

			$ajaxResponse->setMessage("Uploaded metadata object inserted into md_metadata table!");
			$ajaxResponse->setSuccess(true);
		}
		break;
	default:
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("An unknown error occured."));
		break;
}
$ajaxResponse->send();
?>
