<?php
//server component to pull statistics from geoportal catalogue
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_Uuid.php");
$adminLevel = "NUTS_1";
$registratingDepartments = false;
$categoryType = "iso";
if (isset($_REQUEST["adminLevel"]) & $_REQUEST["adminLevel"] != "") {
	$testMatch = $_REQUEST["adminLevel"];	
 	if (!($testMatch == 'LAU_1' or $testMatch == 'LAU_2' or $testMatch == 'NUTS_3' or $testMatch == 'NUTS_1' or $testMatch == 'NUTS_2' or $testMatch == 'other')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>adminLevel</b> is not valid (NUTS_1, NUTS_2, NUTS_3, LAU_1, LAU_2, other).<br/>'; 
		die(); 		
 	}
	$adminLevel = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["categoryType"]) & $_REQUEST["categoryType"] != "") {
	$testMatch = $_REQUEST["categoryType"];	
 	if (!($testMatch == 'iso' or $testMatch == 'inspire' or $testMatch == 'custom' or $testMatch == 'opendata')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>categoryType</b> is not valid (inspire, iso, custom).<br/>'; 
		die(); 		
 	}
	$categoryType = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["registratingDepartments"]) & $_REQUEST["registratingDepartments"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["registratingDepartments"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'registratingDepartments: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>registratingDepartments</b> is not valid (integer or cs integer list).<br/>';
		die(); 		
 	}
	$registratingDepartments = $testMatch;
	$testMatch = NULL;
}


$jsonString2 = <<<JSON
	[{  "label": "Bushtit", "value": 5 },
		{ "label":"Chickadee", "value": 2 },
		{ "label": "Elephants", "value": 1 },
		{ "label": "Killdeer", "value": 3 },
		{ "label": "Caspian Tern", "value": 2 },
		{ "label": "Blackbird", "value": 1 },
		{ "label": "Song Sparrow", "value": 6 },
		{ "label": "Blue Jay", "value": 5 },
		{ "label": "Black-throated Gray warbler" , "value": 1 },
		{ "label": "Pelican", "value": 6 },
		{ "label": "Bewick's Wren", "value": 5 },
		{ "label": "Cowbird", "value": 1 },
		{ "label": "Fox Sparrow", "value": 6 },
		{ "label": "Common Yellowthroat", "value": 5 },
		{ "label": "Virginia Rail", "value": 6 },
		{ "label": "Sora", "value": 1 },
		{ "label": "Osprey", "value": 1 },
		{ "label": "Merlin", "value": 1 },
		{ "label": "Kestrel", "value": 1 }]
JSON;

if ($registratingDepartments == false) {

	//pull statistics from database
$sql = <<<SQL
		select count, mb_group_name, mb_group_title, mb_group_logo_path, mb_group_id, mb_group_admin_code from ( select count(layer_id), fkey_mb_group_id from (select wms_id, fkey_mb_group_id from wms where fkey_mb_group_id <> 0 AND fkey_mb_group_id is not null group by fkey_mb_group_id, wms_id union select wms_id, fkey_mb_group_id from (select wms_owner, wms_id from wms where fkey_mb_group_id = 0 OR fkey_mb_group_id is null group by  wms_owner, wms_id) as owner_wms inner join mb_user_mb_group on owner_wms.wms_owner = mb_user_mb_group.fkey_mb_user_id where mb_user_mb_group_type = 2) as test inner join (select layer_id, fkey_wms_id from layer where layer_searchable=1) as layer on test.wms_id = layer.fkey_wms_id group by fkey_mb_group_id ) as layer_count inner join mb_group on mb_group.mb_group_id = layer_count.fkey_mb_group_id WHERE mb_group_admin_code = $1
SQL;
/*$sql = <<<SQL
	select count, mb_group_name, mb_group_logo_path, mb_group_admin_code from ( select count(layer_id), fkey_mb_group_id from (select wms_id, fkey_mb_group_id from wms where fkey_mb_group_id <> 0 AND fkey_mb_group_id is not null group by fkey_mb_group_id, wms_id union select wms_id, fkey_mb_group_id from (select wms_owner, wms_id from wms where fkey_mb_group_id = 0 OR fkey_mb_group_id is null group by  wms_owner, wms_id) as owner_wms inner join mb_user_mb_group on owner_wms.wms_owner = mb_user_mb_group.fkey_mb_user_id where mb_user_mb_group_type = 2) as test  inner join layer on test.wms_id = layer.fkey_wms_id group by fkey_mb_group_id ) as layer_count inner join mb_group on mb_group.mb_group_id = layer_count.fkey_mb_group_id
SQL;*/

	$v = array(str_replace('_',' ',$adminLevel));
	$t = array('s');	
	$res = db_prep_query($sql,$v,$t);	
	$row = array();
	$resultObj = array();
	if ($res) {
		$i = 0;
		$dataCount = 0;
		while ($row = db_fetch_assoc($res)) {
			$resultObj[$i]["label"] = $row["mb_group_name"] . " (".(integer)$row["count"].")";
			$resultObj[$i]["value"] = (integer)$row["count"];
			$resultObj[$i]["caption"] = $row["mb_group_title"];
			$resultObj[$i]["id"] = $row["mb_group_id"];
			$dataCount = $dataCount + $resultObj[$i]["value"];
			$i++;
		}
		if ($i == 0) {
			$resultObj = false;
			$e = new mb_exception("no results!");
		} else {
			for ($j=0;$j<count($resultObj);$j++) {
				$resultObj[$j]["value"] = ceil(($resultObj[$j]["value"] / $dataCount) * 100);
			}
		}
	} else {
		$e = new mb_exception("Error while request to database!");
	}
} else {
	switch ($categoryType) {
		case "inspire":
			$catId = 1;
		break;
		case "custom":
			$catId = 2;
		break;
		default:
			$catId = 0;
		break;
	}
	if ($categoryType !== "opendata") {
		//call searchInterface for categories
		$connector = new connector(MAPBENDER_PATH."/php/mod_callMetadata.php?searchText=e&outputFormat=json&resultTarget=categories&searchResources=wms&searchId=test&registratingDepartments=".$registratingDepartments);
		$jsonString = $connector->file;
		$jsonObject = json_decode($jsonString);
		$i = 0;
		$dataCount = 0;
		foreach ($jsonObject->searchMD->category[$catId]->subcat as $cat) {
			$resultObj[$i]["label"] = $cat->title . " (".$cat->count.")";
			$resultObj[$i]["value"] = $cat->count;				
			$resultObj[$i]["caption"] = $cat->title;
			$resultObj[$i]["id"] = $cat->id;
			$dataCount = $dataCount + $resultObj[$i]["value"];
			$i++;
		}
	} else {
		//count twice - first for number of all results, second for number of opendata classified results
		//call searchInterface
		$connector = new connector(MAPBENDER_PATH."/php/mod_callMetadata.php?searchText=e&outputFormat=json&searchResources=wms&searchId=test&registratingDepartments=".$registratingDepartments."&maxResults=1");
		$jsonString = $connector->file;
		$jsonObject = json_decode($jsonString);
		$numberOfResults = $jsonObject->wms->md->nresults;
		$connector = new connector(MAPBENDER_PATH."/php/mod_callMetadata.php?searchText=e&outputFormat=json&searchResources=wms&searchId=test&registratingDepartments=".$registratingDepartments."&maxResults=1&restrictToOpenData=true");
		$jsonString = $connector->file;
		$jsonObject = json_decode($jsonString);
		$numberOfOpenResults = $jsonObject->wms->md->nresults;
		//define values to return
		$dataCount = $numberOfResults;
		if ($numberOfOpenResults == $numberOfResults) {
			$resultObj[0]["label"] = "OpenData (100%)";
			$resultObj[0]["value"] = $numberOfResults;				
			$resultObj[0]["caption"] = "OpenData";
			$resultObj[0]["id"] = 0;
			$i = 1;
		} else {
			$resultObj[0]["label"] = "OpenData";
			$resultObj[0]["value"] = $numberOfOpenResults;				
			$resultObj[0]["caption"] = "OpenData";
			$resultObj[0]["id"] = 1;
			$resultObj[1]["label"] = "Keine freie Lizenz!";
			$resultObj[1]["value"] = $numberOfResults;				
			$resultObj[1]["caption"] = "Keine freie Lizenz!";
			$resultObj[1]["id"] = 0;
			$i = 2;
		}
	}
	if ($i == 0) {
		$resultObj = false;
		$e = new mb_exception("no results!");
	} else {
		for ($j=0;$j<count($resultObj);$j++) {
			$resultObj[$j]["value"] = ceil(($resultObj[$j]["value"] / $dataCount) * 100);
		}
	}
}
$jsonString = json_encode($resultObj);
header('Content-Type: application/json; charset='.CHARSET);
echo $jsonString;
?>
