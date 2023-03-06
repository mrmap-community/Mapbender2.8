<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_wms.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

function getRootLayerId ($wms_id) {
	$sql = "SELECT layer_id FROM layer, wms " . 
		"WHERE wms.wms_id = layer.fkey_wms_id AND layer_pos='0' " . 
		"AND wms.wms_id = $1";
	$v=array($wms_id);
	$t=array('i');
	$res=db_prep_query($sql,$v,$t);
	$row=db_fetch_array($res);
	return $row ? $row["layer_id"] : null;
}

//instantiate admin
$admin = new administration();

$command = $_REQUEST["command"];
if ($command == "getWmsData") {
	$wms = $_POST["wmsId"];
	$url = $_POST["wmsUrl"];
	$authType = $_POST["authType"];
	$authName = $_POST["authName"];
	$authPassword = $_POST["authPassword"];
	if ($authType !== "none") {
		$useAuthentication = true;
		$auth['username'] = $authName; 
 		$auth['password'] = $authPassword; 
 		$auth['auth_type'] = $authType; 
	} else {
		$useAuthentication = false;
	}
	
	//get existing DB wms information 
    	$sql = "SELECT * from layer WHERE fkey_wms_id = $1 ORDER BY layer_pos";
    	$v = array ($wms);
    	$t = array ('i');
   	$res = db_prep_query($sql, $v, $t);
    
   	$dbObj = array();
    
	while ($row = db_fetch_array($res)) {
        	$dbObj[] = array (
            	//"id" => $row["layer_id"],
            	"pos" => $row["layer_pos"],
            	"parent"   => $row["layer_parent"],
            	"name"   => $row["layer_name"]
            	//"title"   => $row["layer_title"]
        	);
   	}
    
    	//get xml wms information
	$updateWms = new wms();
	if ($useAuthentication) {
		$result = $updateWms->createObjFromXML($url, $auth);
	} else {
		$result = $updateWms->createObjFromXML($url);
	}
	if(!$result['success']) {
	    	echo $result['message'];
	    	die();
	}

	$updateWms->optimizeWMS();
	$xmlObj = $updateWms->getLayerInfo();
	
	$resultObj = array(
	    "dbObj" => $dbObj,
	    "xmlObj" =>  $xmlObj
	);
	
	$layerJson = json_encode($resultObj);
	
    header("Content-type:application/json; charset=utf-8");
    echo $layerJson;
} else if ($command == "updateWMS") {

    	$myWMS = $_POST["wmsId"];
	$url = $_POST["wmsUrl"];
	$authType = $_POST["authType"];
	$authName = $_POST["authName"];
	$authPassword = $_POST["authPassword"];
	if ($authType !== "none") {
		$useAuthentication = true;
		$auth['username'] = $authName; 
 		$auth['password'] = $authPassword; 
 		$auth['auth_type'] = $authType; 
	} else {
		$useAuthentication = false;
	}
	
	$changedLayerArray = array();
	for ($i=0; $i<count($_POST['dbOldNames']); $i++) {
	    $changedLayerArray[] = array(
	        "oldLayerName" => $_POST['dbOldNames'][$i],
	        "newLayerName" => $_POST['dbCurrentNames'][$i]
	    );    
	}
	
	#$changedLayerObj = json_encode($changedLayerArray);
	
    	$mywms = new wms();
    if(empty($_POST['harvestDatasetMetadata']) || $_POST['harvestDatasetMetadata'] == 'false') {
		$mywms->harvestCoupledDatasetMetadata = false;
	}
	if ($useAuthentication) {
		$result = $mywms->createObjFromXML($url, $auth);   
	} else {
		$result = $mywms->createObjFromXML($url);  
	}
	if(!$result['success']) {
	    	echo $result['message'];
	    	die();
	}
	$mywms->optimizeWMS();
	echo "<br />";  
	if (!MD_OVERWRITE) {
		$mywms->overwrite=false;
	} 
	//possibility to see update information in georss and/or twitter channel
	if(empty($_POST['twitter_news'])) {
		$mywms->twitterNews = false;
	}
	if(empty($_POST['rss_news'])) {
		$mywms->setGeoRss = false;
	}
	if(empty($_POST['overwrite_categories'])) {
		$mywms->overwriteCategories = false;
	} else {
		$mywms->overwriteCategories = true;
	}
	if ($useAuthentication) {
		$mywms->updateObjInDB($myWMS,false, $changedLayerArray, $auth);
	} else {
		$mywms->updateObjInDB($myWMS,false, $changedLayerArray);
	}
	$mywms->displayWMS();

	// start (owners and subscribers of the updated wms will be notified by email)
	if (defined("NOTIFY_ON_UPDATE") &&  NOTIFY_ON_UPDATE == true) {
		//collect change information
		$layerChangeInformation = "";
		for ($j=0; $j<count($changedLayerArray); $j++) {
			if ($changedLayerArray[$j]["oldLayerName"] != $changedLayerArray[$j]["newLayerName"]) {
				$e = new mb_notice("Old layer name: ".$changedLayerArray[$j]["oldLayerName"]." - changed to: ".$changedLayerArray[$j]["newLayerName"]);
				$layerChangeInformation .= _mb("Old layer name: ")."'".$changedLayerArray[$j]["oldLayerName"]."'"._mb(" - changed to: ")."'".$changedLayerArray[$j]["newLayerName"]."'"."\n";		
			}
		}
		//get owner of guis with this wms
		$owner_ids = $admin->getOwnerByWms($myWMS);
		//get information for subscribers
		$subscribers_ids = $admin->getSubscribersByWms($myWMS);
		//if some person exists which is interested in changing of wms information ;-)
		if (($owner_ids && count($owner_ids)>0) || ($subscribers_ids && count($subscribers_ids)>0)) {
			$notification_mail_addresses = array();
			$j=0;
			for ($i=0; $i<count($owner_ids); $i++) {
				$adr_tmp = $admin->getEmailByUserId($owner_ids[$i]);
				if (!in_array($adr_tmp, $notification_mail_addresses) && $adr_tmp) {
					$notification_mail_addresses[$j] = $adr_tmp;
					$j++;
				} 
			}
			for ($i=0; $i<count($subscribers_ids); $i++) {
				$adr_tmp = $admin->getEmailByUserId($subscribers_ids[$i]);
				if (!in_array($adr_tmp, $notification_mail_addresses) && $adr_tmp) {
					$notification_mail_addresses[$j] = $adr_tmp;
					$j++;
				} 
			}

			$replyto = $admin->getEmailByUserId(Mapbender::session()->get("mb_user_id"));
			$from = $replyto;
			$rootLayerId = getRootLayerId($myWMS);
			//$e = new mb_exception(MAPBENDER_PATH);
			if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '') {
				$metadataUrl = MAPBENDER_PATH."/php/mod_showMetadata.php?resource=layer&id=".$rootLayerId;
			} else {
				$metadataUrl = preg_replace(
					"/(.*)frames\/login.php/", 
					"$1php/mod_showMetadata.php?resource=layer&id=".$rootLayerId, 
					LOGIN
				);
			}
			$path = $pathArray[0];
			//Build mailbody	
			$body = _mb("WMS")." '" . $admin->getWmsTitleByWmsId($myWMS) . "' "._mb("has been updated").".\n\n".$metadataUrl. "\n\n"._mb("You may want to check the changes as you are an owner or subscriber of this WMS. If you have integrated the service into a gis client, you have to reconfigure the client!")."\n"._mb("Note: This e-mail has been sent automatically because you subscribed " . "to this service. You can unsubscribe by logging in and clicking the " . "unsubscribe button in the Mapbender metadata dialogue by following the given link.");
			if (isset($layerChangeInformation) &&  $layerChangeInformation != "") {
				$body .= "\n\n"._mb("Attention - following layers have been renamed").":\n".$layerChangeInformation;
			}
			$error_msg = "";

			for ($i=0; $i<count($notification_mail_addresses); $i++) {
				if (!$admin->sendEmail($replyto, $from, $notification_mail_addresses[$i], $notification_mail_addresses[$i], _mb("Update of an observed WMS"), utf8_decode($body), $error)) {
					if ($error){
						$error_msg .= $error . " ";
					}
				}
			}
			if (!$error_msg) {
				echo "<script language='javascript'>";
				echo "alert('"._mb("Other owners of this WMS have been informed about the changes!")."');";
				echo "</script>";
			}
			else {
				echo "<script language='javascript'>";
				echo "alert('"._mb("When notifying the owners of this WMS about your changes, an error occured").": ' + '" . $error_msg . "');";
				echo "</script>";
			}
		}
	}
	// end (owners of the updated wms will be notified by email)
}
else {
	echo _mb("please enter a valid command.");
}
?>
