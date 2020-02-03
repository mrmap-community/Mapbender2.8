<?php require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once dirname(__FILE__) . "/../classes/class_wmc_factory.php";
require_once(dirname(__FILE__) . "/../classes/class_user.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_owsConstraints.php");

//following is needed cause sometimes the service is invoked as a localhost service and then no userId is known but the userId in the session is needed for class_wmc to read from database!!! TODO: check if needed in this class.
$userId = Mapbender::session()->get("mb_user_id");
if (!isset($userId) or $userId =='') {
	$userId = PUBLIC_USER; //or public
	Mapbender::session()->set("mb_user_id",$userId);
}
$languageCode = 'de';
//parameters: 
//id - wmc id
//languageCode - language parameter 'de', 'en', 'fr' 
//$wmsServiceDisclaimerUrl = "";
$admin = new administration();
//initialize variables
$hostName = $_SERVER['HTTP_HOST'];
//$userId = PUBLIC_USER;
$id = 4373; //dummy id
$withHeader = false;
//TODO give requesting hostname to this script
if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
	if ($_REQUEST["id"] == "current") {
		$id = "current";
	} else { 
		//validate to integer 
		$testMatch = $_REQUEST["id"];
		$pattern = '/^[\d]*$/';		
	 	if (!preg_match($pattern,$testMatch)){ 
			echo 'id is not valid.<br/>'; 
			die(); 		
	 	}
		$id = (integer)$testMatch;
		$testMatch = NULL;
	}	
}

if (isset($_REQUEST["withHeader"]) & $_REQUEST["withHeader"] != "") {
	//validate to wms, wfs
	$testMatch = $_REQUEST["withHeader"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		echo 'withHeader is not a valid boolean.<br/>'; 
		die(); 		
 	} else {
		switch ($testMatch) {
			case "true":
				$withHeader = true;
			break;
		}
	}
	$testMatch = NULL;
}

//TODO give requesting hostname to this script
if (isset($_REQUEST["hostName"]) & $_REQUEST["hostName"] != "") {
	//validate to some hosts
	$testMatch = $_REQUEST["hostName"];	
	//look for whitelist in mapbender.conf
	$HOSTNAME_WHITELIST_array = explode(",",HOSTNAME_WHITELIST);
	if (!in_array($testMatch,$HOSTNAME_WHITELIST_array)) {
		echo "Requested hostname not in whitelist! Please control your mapbender.conf.";
		$e = new mb_notice("Whitelist: ".HOSTNAME_WHITELIST);
		$e = new mb_notice($testMatch." not found in whitelist!");
		die(); 	
	}
	$hostName = $testMatch;
	$testMatch = NULL;
}
$e = new mb_notice("mod_getWmcDisclaimer.php: requested wmc id: ".$_REQUEST["id"]);
//
$sessionLang = Mapbender::session()->get("mb_lang");
if (isset($sessionLang) && ($sessionLang!='')) {
	$e = new mb_notice("mod_showMetadata.php: language found in session: ".$sessionLang);
	$language = $sessionLang;
	$langCode = explode("_", $language);
	$langCode = $langCode[0]; # Hopefully de or s.th. else
	$languageCode = $langCode; #overwrite the GET Parameter with the SESSION information
}

if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["languageCode"];
	if (!($testMatch == 'de' or $testMatch == 'fr' or $testMatch == 'en')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>languageCode</b> is not valid (de,fr,en).<br/>'; 
		die(); 		
 	}
	$languageCode = $testMatch;
	$e = new mb_notice("mod_showMetadata.php: languageCode from GET parameter: ".$languageCode);
	$testMatch = NULL;
}

$localeObj->setCurrentLocale($languageCode);

//javascript:openwindow("../php/mod_showMetadata.php?resource=layer&layout=tabs&redirectToMetadataUrl=1&id=20655");
//Generate wmc document by id

$wmcFactory = new WmcFactory;
if ($id !== "current") {
	//$e = new mb_notice("mod_getWmcDisclaimer.php: wmcid: ".$id);
	$wmcObj = $wmcFactory->createFromDb($id);
} else {
	//read wmc from session if available and fill the needed fields from wmc object
	$wmcDocSession = false;
	//check if wmc filename is in session - TODO only if should be loaded from session not else! (Module loadWMC)
	if(Mapbender::session()->get("mb_wmc")) {
    		$wmc_filename = Mapbender::session()->get("mb_wmc");
    		//$time_start = microtime();
    		//load it from whereever it has been stored
    		$wmcDocSession = $admin->getFromStorage($wmc_filename, TMP_WMC_SAVE_STORAGE);
		$wmcObj = $wmcFactory->createFromXml($wmcDocSession);
	}
}
//generate HTML Header
if ($withHeader){
		//e.g. tabs and their content
		$html = '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$languageCode.'">';
		$html .= '<body>';
		$metadataStr .= '<head>' . 
		'<title>'._mb('Use Constraints').'</title>' . 
		'<meta name="description" content="'._mb('Disclaimer').'" xml:lang="'.$languageCode.'" />'.
		'<meta name="keywords" content="'._mb('Use limitations and access constraints').'" xml:lang="'.$languageCode.'" />'	.	
		'<meta http-equiv="cache-control" content="no-cache">'.
		'<meta http-equiv="pragma" content="no-cache">'.
		'<meta http-equiv="expires" content="0">'.
		'<meta http-equiv="content-language" content="'.$languageCode.'" />'.
		'<meta http-equiv="content-style-type" content="text/css" />'.
		'<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">' . 	
		'<link rel="stylesheet" type="text/css" href="../css/copyright.css">' .
		'</head>';
		echo $html.$metadataStr;

}
//generate header for disclaimer:
echo "<div style='padding:10px;display:block;text-align:center;'><a href='javascript:window.close()'>Fenster schliessen</a></div>";
echo "<b>"._mb('The document includes data resources from different organizations. The following parapgraph shows the different terms of use for the includes resources:')."</b><br><br>";#

//Part for wms
$resourceSymbol = "<img src='../img/osgeo_graphics/geosilk/server_map.png' alt='"._mb('Web Map Service')." - picture' title='"._mb('Web Map Service')."'>";
//read out all wms id's
$validWMS = $wmcObj->getValidWms();
if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$mapbenderBaseUrl = "https://".$hostName;
			$mapbenderProtocol = "https://";
		}
		else {
			$mapbenderBaseUrl = "http://".$hostName;
			$mapbenderProtocol = "http://";
}

$countWMS = count($validWMS);
for ($i = 0; $i < $countWMS; $i++) {
	$WMS = $validWMS[$countWMS - ($i+1)];
	echo $resourceSymbol." <a href='".$mapbenderBaseUrl.$_SERVER['SCRIPT_NAME']."/../mod_showMetadata.php?resource=wms&layout=tabs&id=".$WMS['id']."&languageCode=".$languageCode."'>".$WMS['title']."</a><br>";
	$constraints = new OwsConstraints();
	$constraints->languageCode = $languageCode;
	$constraints->asTable = true;
	$constraints->id = $WMS['id'];
	$constraints->type = "wms";
	$constraints->returnDirect = false;
	$touForWMS = $constraints->getDisclaimer();
	if ($touForWMS == 'free'){
		$wmstou = _mb('No informations about terms of use are available!');
	} else {
		$wmstou = $touForWMS;
	}
	echo $wmstou."<br>";
}
if ($withHeader){
		echo "</body></html>";
}
//var_dump($validWMS);
//module to read out all service ids which are stored in mapbender wmc documents and generate a Big Disclaimer for those Docs.
//It integrates all known disclaimers for the used webservices who are stored in the mapbender registry
//read out all wms id's
//read out all wfs id's
//generate the disclaimer part for wms 
//generate the disclaimer part for wfs
//push disclaimer to json or html
?>
