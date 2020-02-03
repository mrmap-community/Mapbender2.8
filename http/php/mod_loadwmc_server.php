<?php
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
require_once(dirname(__FILE__) . "/../classes/class_wmcToXml.php");
require_once(dirname(__FILE__) . "/../classes/class_json.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";
require_once(dirname(__FILE__) . "/../extensions/phpqrcode/phpqrcode.php");

$ajaxResponse = new AjaxResponse($_POST);
$json = new Mapbender_JSON();
$userId = Mapbender::session()->get("mb_user_id");
$currentUser = new User($userId);

$wmc = new wmc();

$resultObj = array();

switch ($ajaxResponse->getMethod()) {

	// gets available WMCs
	case "getWmc":
		$showPublic = $ajaxResponse->getParameter("showPublic");
		//$e = new mb_notice("mod_loadwmc_server.php: showPublic: ".$showPublic);
		$resultObj["wmc"] = $wmc->selectByUser($currentUser,$showPublic);
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);		
	break;

	// gets QR code for url 
	case "getQrCode":
		$wmcid = $ajaxResponse->getParameter("wmcid");
		$newWindow = $ajaxResponse->getParameter("newWindow");
		if ($newWindow == "1") {
			$newWindow = true;
		} else {
			$newWindow = false;
		}
		//$e = new mb_notice("mod_loadwmc_server.php: showPublic: ".$showPublic);
		//$resultObj["wmc"] = $wmc->selectByUser($currentUser,$showPublic);
		//show qr for link 
		//create uuid for qr graphic
		$uuid = new Uuid;
		$filename = "qr_wmc_".$uuid.".png";
		//generate qr on the fly in tmp folder
		//link to invoke wmc per get api if wrapper path isset
		if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != "") {
			$invokeLink = MAPBENDER_PATH."/extensions/mobilemap/map.php?wmcid=".$wmcid;
			//$invokeLink = "http://www.geoportal.rlp.de/mapbender/extensions/mobilemap/map.php";
			QRcode::png($invokeLink,TMPDIR."/".$filename);
			if ($newWindow) {
				$html = "<a href = '".$invokeLink."' target='_blank'><img src='".TMPDIR."/".$filename."'></a>";
			} else {
				$html = "<a href = '".$invokeLink."'><img src='".TMPDIR."/".$filename."'></a>";
			}
		}
	
		$resultObj["qrCode"] = $html; 
		$ajaxResponse->setResult($resultObj);
		$ajaxResponse->setSuccess(true);		
	break;

	// gets XML document of a WMC
	case "getWmcDocument":
		$wmcId = $ajaxResponse->getParameter("id");
		$doc = $wmc->getDocument($wmcId);
		if (!$doc) {
			$ajaxResponse->setMessage(_mb("The WMC document could not be found."));
			$ajaxResponse->setSuccess(false);
		}
		else {
			$resultObj["wmc"] = array("document" => $doc);
			$ajaxResponse->setResult($resultObj);
			$ajaxResponse->setSuccess(true);		
		}
	break;

	// deletes a WMC
	case "deleteWmc":
		$wmcId = $ajaxResponse->getParameter("id");
		if ($wmc->delete($wmcId, $userId)) {
			$ajaxResponse->setMessage(_mb("WMC has been deleted from the database."));
			$ajaxResponse->setSuccess(true);
		}
		else {
			$ajaxResponse->setMessage(_mb("WMC could not be deleted."));
			$ajaxResponse->setSuccess(false);
		}
	break;
	
	//loads a WMC (returns array of JS code)
	case 'loadWmc':
		$wmcId = $ajaxResponse->getParameter("id");
		$removeUnaccessableLayers = $ajaxResponse->getParameter("removeUnaccessableLayers");
		if ($wmc->createFromDb($wmcId)) {
			$updatedWMC = $wmc->updateUrlsFromDb();
			if ($removeUnaccessableLayers == 1) {
				//new 2018-09-27 - delete unaccessable layers if wished!
				$accessableWmcXml = $wmc->removeUnaccessableLayers($updatedWMC);
				$wmc->createFromXml($accessableWmcXml);
			} else {
				$wmc->createFromXml($updatedWMC);
			}
			$skipWms = $ajaxResponse->getParameter("skipWms");
			if (is_array($skipWms)) {
				$jsArray = $wmc->toJavaScript($skipWms);
			}
			else {
				$jsArray = $wmc->toJavaScript();
			}
			if ($jsArray) {
				$resultObj["javascript"] = $jsArray;
				//increment load_count
				$wmc->incrementWmcLoadCount();
				$ajaxResponse->setResult($resultObj); 
				$ajaxResponse->setSuccess(true);
				break;
			}
		}
		$ajaxResponse->setMessage(_mb("WMC could not be loaded."));
		$ajaxResponse->setSuccess(false);
	break;

	case 'loadWmcFromFile':
		$serverFilename = $ajaxResponse->getParameter("filename");
		$wmc = new wmc();
		$wmcDoc = file_get_contents($serverFilename->filename);
		if (!$wmcDoc) {
			$ajaxResponse->setMessage(_mb("WMC could not be loaded."));
			$ajaxResponse->setSuccess(false);
		}
		$wmc->createFromXml($wmcDoc);
		/*$e = new mb_exception("value from checkbox: ".json_encode($ajaxResponse->getParameter("checkboxvalue")));
		$e = new mb_exception("value from checkbox: ".gettype($ajaxResponse->getParameter("checkboxvalue")));*/
		//only update urls and layer names if this is explicitly wished! If the wmc is from another mapbender installation, the resource ids are others - they are not bind to the installation til now!
		$e = new mb_notice("php/loadwmc_server.php: Try to update URLs and layer/names: ".json_encode($ajaxResponse->getParameter("checkboxvalue")));
		if ($ajaxResponse->getParameter("checkboxvalue")) {
			$updatedWMC = $wmc->updateUrlsFromDb();
        		$wmc->createFromXml($updatedWMC);
		}
        	$jsArray = $wmc->toJavaScript();
		if ($jsArray) {
			$resultObj["javascript"] = $jsArray;
			$ajaxResponse->setResult($resultObj); 
			$ajaxResponse->setSuccess(true);
		}
		else {
			$ajaxResponse->setMessage(_mb("WMC could not be loaded."));
			$ajaxResponse->setSuccess(false);
		}
	break;

	// merges data with WMC and loads it (returns array of JS code)
	case "mergeWmc":
		// generate a WMC for the current client state
		$currentWmc = new wmc();
		$currentWmc->createFromJs(
			$json->decode($ajaxResponse->getParameter("mapObject")), 
			$ajaxResponse->getParameter("generalTitle"), 
			$ajaxResponse->getParameter("extensionData")
		);

		// get the desired WMC from the database
		$wmcId = $ajaxResponse->getParameter("id");
		$wmcXml = wmc::getDocument($wmcId);

		// merge the two WMCs
		$currentWmc->merge($wmcXml);
		
		// load the merged WMC
		$jsArray = $currentWmc->toJavaScript();

		if (is_array($jsArray) && count($jsArray) > 0) {
			$resultObj["javascript"] = $jsArray;
			$ajaxResponse->setResult($resultObj); 
			$ajaxResponse->setSuccess(true);
		}
		else {
			$ajaxResponse->setMessage(_mb("WMC could not be merged."));
			$ajaxResponse->setSuccess(false);
		}
	break;
	
	// appends a WMC (returns JS code)
	case 'appendWmc':
		// generate a WMC for the current client state
		$currentWmc = new wmc();
		$currentWmc->createFromJs(
			$json->decode($ajaxResponse->getParameter("mapObject")), 
			$ajaxResponse->getParameter("generalTitle"), 
			$ajaxResponse->getParameter("extensionData")
		);

		// get the desired WMC from the database
		$wmcId = $ajaxResponse->getParameter("id");
		$wmcXml = wmc::getDocument($wmcId);

		// merge the two WMCs
		$currentWmc->append($wmcXml);
		
		// load the merged WMC
		$jsArray = $currentWmc->toJavaScript();

		if (is_array($jsArray) && count($jsArray) > 0) {
			$resultObj["javascript"] = $jsArray;
			$ajaxResponse->setResult($resultObj); 
			$ajaxResponse->setSuccess(true);
		}
		else {
			$ajaxResponse->setMessage(_mb("WMC could not be appended."));
			$ajaxResponse->setSuccess(false);		
		}
	break;

    case 'setWMCPublic':
	  $wmcId = $ajaxResponse->getParameter("id");
	  $public = $ajaxResponse->getParameter("isPublic") == 1  ? true : false;
      $wmc = new wmc();
      $wmc->createFromDb($wmcId);
      if($wmc->setPublic($public)){
			$ajaxResponse->setMessage(_mb("Updated public flag"));
			$ajaxResponse->setSuccess(true);		
      }else {
			$ajaxResponse->setMessage(_mb("could not update public flag"));
			$ajaxResponse->setSuccess(false);		

      }
    break;
	
	case 'checkConstraints':
		$checkLayerIdExists = $ajaxResponse->getParameter("checkLayerIdExists");
		$checkLayerIdValid = $ajaxResponse->getParameter("checkLayerIdValid");
		$checkLayerPermission = $ajaxResponse->getParameter("checkLayerPermission");
		$checkLayerAvailability = $ajaxResponse->getParameter("checkLayerAvailability");
		
		$wmcId = $ajaxResponse->getParameter("id");
        $wmc->createFromDb($wmcId);
		$wmsArray = $wmc->mainMap->getWmsArray();
		
		if ($checkLayerIdExists) {
			$withoutIdsArray = $wmc->getWmsWithoutId();
			$withoutIdsTitles = array();
			foreach ($withoutIdsArray as $i) {
				$withoutIdsTitles[]= array(
					"id" => $i["id"],
					"index" => $i["index"],
					"title" => $i["title"]
				);
			}
			$resultObj["withoutId"] = array(
				"message" => "Folgende Layer stammen aus einer dem " .
					"Geoportal.rlp unbekannten Quelle. Es kann daher nicht " . 
					"überprüft werden, ob die Links verwaist sind oder ob " .
					"die Dienste überhaupt Daten liefern.",
				"wms" => $withoutIdsTitles
			);
		}
		if ($checkLayerIdValid) {
			$invalidIdsArray = $wmc->getInvalidWms();
			$invalidIdsTitles = array();
			foreach ($invalidIdsArray as $i) {
				$invalidIdsTitles[]= array(
					"id" => $i["id"],
					"index" => $i["index"],
					"title" => $i["title"]
				);
			}
			$resultObj["invalidId"] = array(
				"message" => "Folgende Dienste/Layer sind aus der " .
					"Registrierungsstelle gelöscht worden. Es kann daher nicht " . 
					"überprüft werden, ob die Links verwaist sind oder ob " . 
					"die Dienste überhaupt Daten liefern.",
				"wms" => $invalidIdsTitles
			);
		}
		if ($checkLayerPermission) {
			$deniedIdsArray = $wmc->getWmsWithoutPermission($currentUser);
			$deniedIdsTitles = array();
			foreach ($deniedIdsArray as $i) {
				$deniedIdsTitles[]= array(
					"id" => $i["id"],
					"index" => $i["index"],
					"title" => $i["title"]
				);
			}
			$resultObj["noPermission"] = array(
				"message" => "Sie als Nutzer '" . 
					Mapbender::session()->get("mb_user_name") . "' " . 
					"haben keine Berechtigung auf folgende Layer zuzugreifen.",
				"wms" => $deniedIdsTitles
			);
		}
		if ($checkLayerAvailability) {
			$unavailableIdsArray = $wmc->getUnavailableWms($currentUser);
			$unavailableIdsTitles = array();
			foreach ($unavailableIdsArray as $i) {
				$unavailableIdsTitles[]= array(
					"id" => $i["id"],
					"index" => $i["index"],
					"title" => $i["title"]
				);
			}
			$resultObj["unavailable"] = array(
				"message" => "Bei folgenden Diensten kam es während " . 
					"des letzten Monitorings zu Problemen. Es ist möglich, dass " . 
					"diese Dienste derzeit keine Informationen zur Verfügung stellen " .
					"können.",
				"wms" => $unavailableIdsTitles
			);
		}
		$ajaxResponse->setResult($resultObj); 
		$ajaxResponse->setSuccess(true);
		break;
	// Invalid command
	default:
		$ajaxResponse->setMessage(_mb("No method specified."));
		$ajaxResponse->setSuccess(false);		
}

$ajaxResponse->send();
?>
