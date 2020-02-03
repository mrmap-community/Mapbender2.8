<?php
#
# http://www.mapbender.org/index.php/
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//require_once(dirname(__FILE__)."/../../core/globalSettings.php");
//$e_id="reindexWMS";
require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");

//require_once(dirname(__FILE__)."/../../core/globalSettings.php");
//validate parameter values
if (isset($_REQUEST["resourceType"]) & $_REQUEST["resourceType"] != "") {
	$testMatch = $_REQUEST["resourceType"];	
 	if (!($testMatch == 'wms' or $testMatch == 'wfs' or $testMatch == 'dataset' or $testMatch == 'wmc')){ 
		echo 'Parameter <b>resourceType</b> is not valid (wms, wfs, dataset, wmc).<br/>'; 
		die(); 		
 	}
	$resourceType = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["maintenanceFunction"]) & $_REQUEST["maintenanceFunction"] != "") {
	$testMatch = $_REQUEST["maintenanceFunction"];	
 	if (!($testMatch == 'reindex' or $testMatch == 'monitor')){ 
		echo 'Parameter <b>maintenanceFunction</b> is not valid (reindex, monitor).<br/>'; 
		die(); 		
 	}
	$maintenanceFunction = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["resourceIds"]) & $_REQUEST["resourceIds"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["resourceIds"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'resourceIds: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>resourceIds</b> is not valid (integer or cs integer list).<br/>'; 
		die(); 		
 	}
	$resourceIds = $testMatch;
	$testMatch = NULL;
}

$allowedFunctions = array(
	'wms' => array('reindex','monitor'),
	'wfs' => array('reindex'),
	'dataset' => array('reindex'),
	'wmc' => array('reindex')
);

$functionThatNeedIdList = array('monitor');

//check for allowedFunction
if (!in_array($maintenanceFunction, $allowedFunctions[$resourceType])) {
	echo 'Maintenance function not allowed for requested resource type.<br/>'; 
	die(); 			
}

//check for given id if demanded
/*if (in_array($maintenanceFunction, $functionThatNeedIdList) && !isset($resourceIds)) {
	echo 'Maintenance function need parameter resourceIds, but this is not given.<br/>'; 
	die(); 			
}*/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
<title><?php 
switch ($maintenanceFunction) {
	case "reindex":
		$title = _mb('Re-Index search database for ');
	break;
	case "monitor":
		$title = _mb('Start monitoring process for ');
	break;
}

switch ($resourceType) {
	case "wms":
		$title .= _mb('WebMapService(s)');
	break;
	case "wfs":
		$title .= _mb('WebFeatureService(s)');
	break;
	case "dataset":
		$title .= _mb('Dataset(s)');
	break;
	case "wmc":
		$title .= _mb('WebMapContext Document(s)');
	break;
}

echo $title;
//collect number of objects to reindex
$count = 0;

if ($maintenanceFunction == 'reindex') {
	switch ($resourceType) {
		case "wms":
			$sqlCount = "SELECT count(layer_id) from layer WHERE layer_searchable = 1;";
		break;
		case "wfs":
			$sqlCount = "SELECT count(featuretype_id) from wfs_featuretype WHERE featuretype_searchable = 1;";
		break;
		case "dataset":
			$sqlCount = "SELECT count(metadata_id) from mb_metadata WHERE searchable;";
		break;
		case "wmc":
			$sqlCount = "SELECT count(wmc_serial_id) from mb_user_wmc WHERE wmc_public = 1;";
		break;
	}
	$resCount = db_query($sqlCount);
	if (!$resCount) {
		$count = 0;
	} else {
		$row = db_fetch_array($resCount);
		$count = $row['count'];
	}
	$title .= " (".$count." "._mb("searchable resources").")";
}

switch ($maintenanceFunction) {
	case "reindex":
		$sql = file_get_contents(dirname(__FILE__)."/../../resources/db/materialize_".$resourceType."_view.sql"); 
	break;
}
?>
</title>
<script src="../extensions/jquery-1.12.0.min.js"></script>
<script>


/*function callServer(resourceType,maintenanceFunction,resourceIds,id) {
		var req = new Mapbender.Ajax.Request({
			url: "../php/mod_catalogueMaintenance_server.php",
			method: maintenanceFunction,
			parameters: {
				"resourceIds": resourceIds,
				"resourceType": resourceType
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				alert(JSON.stringify(obj));
			}
		});
		req.send();	
}*/
function callServer(resourceType,maintenanceFunction,resourceIds,id) {
	$("#doing_maintenance").css("display","block");
	$.ajax({
  		url: '../php/mod_catalogueMaintenance_server.php',
  		type: "post",
		async: true,
		data: {resourceType: resourceType, maintenanceFunction: maintenanceFunction , resourceIds: resourceIds, id: id},
       		dataType: "json",
  		success: function(result) {
			$("#doing_maintenance").css("display","none");
			//select tab
			//$('#mytabs a[href="#dataset_info"]').tab('show');
			//draw georss polygons from service feed 
			//drawMetadataPolygons(result);
			//show datasets in a dropdown list
			//showDatasetList(result);	
			alert(JSON.stringify(result));
 		}
	});
	return false;
}

</script>
<style type="text/css">
.loading_symbol {
    -webkit-animation:spin 4s linear infinite;
    -moz-animation:spin 4s linear infinite;
    animation:spin 4s linear infinite;

}
@-moz-keyframes spin { 100% { -moz-transform: rotate(360deg); } }
@-webkit-keyframes spin { 100% { -webkit-transform: rotate(360deg); } }
@keyframes spin { 100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } }
</style>
</head>
<?php include '../include/dyn_css.php'; ?>
<body>
<div id="title"><?php echo $title; ?></div>
<div id="doing_maintenance" style="display: none;"><p><img class="loading_symbol" src="../img/loader_lightblue.gif" style="margin-left: auto; margin-right: auto;"/><?php echo _mb("Doing maintenance ...");?></p></div>
<form id="start_maintenance_form">
	<?php
		$form = "";
		if (in_array($maintenanceFunction, $functionThatNeedIdList)) {
        		$form .= "<label for=\"resource_id_list\">"._mb("Resource Ids").":</label><br>";
       	 		$form .= "<input name=\"resource_id_list\" id=\"resource_id_list\" class=\"required\"";
			if (isset($resourceIds)) {
				$form .= " value=\"".htmlspecialchars($resourceIds)."\"";
			} else {
				$form .= " value=\"\"/>";
			}
			$form .= "<img src=\"../img/gnome/process-stop.png\" width=\"20px\" onclick=\"$('#resource_id_list').val('');\"/>";
		}
		$form .= "<button class=\"btn btn-primary\" type=\"button\" id=\"maintenance_button\" onclick=\"callServer('".$resourceType."','".$maintenanceFunction."',$('#resource_id_list').val(),"."1".");\">";
		switch ($maintenanceFunction) {
			case "reindex":
				$form .= _mb("Build search tables");
			break;
			case "monitor":
				$form .= _mb("Start monitoring");
			break;
		}
		$form .= "</button>";
		echo $form;
	?>
</form>
<div id="result" style="display: none;">
</div>
</body>
</html>
