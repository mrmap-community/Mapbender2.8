<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once(dirname(__FILE__)."/../classes/class_owsContext.php");

//set default request parameters
$wmcId = null;
$outputModel = "OWC";
$outputFormat = "atom";

$sessionUserId = Mapbender::session()->get("mb_user_id");
if ($sessionUserId !== false) {
    $userId = $sessionUserId;
} else {
    if (DEFINED("PUBLIC_USER") && PUBLIC_USER !== false && PUBLIC_USER !== "") {
        $userId = PUBLIC_USER;
    }
}
//parse request parameters
if (isset($_REQUEST["wmcId"]) & $_REQUEST["wmcId"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["wmcId"];
    $pattern = '/^[\d,]*$/';		
    if (!preg_match($pattern,$testMatch)){ 
        echo 'Parameter <b>wmcId</b> is not a valid integer.<br/>'; 
        die(); 		
    }
    $wmcId = $testMatch;
    $testMatch = NULL;
}
//parse request parameter
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
    //validate to csv integer list
    $testMatch = $_REQUEST["outputFormat"];
    
    if (!in_array($testMatch, array("atom", "json"))) {
        
        echo 'Parameter <b>outputFormat</b> is not  valid (atom or json).<br/>';
        
        die();
    }
    $outputFormat = $testMatch;
    $testMatch = NULL;
}
$wmcExists = false;
if ($wmcId !== null) {
    //try to test if given wmcId is either owned by the current user or public
    $sql = "SELECT wmc_serial_id FROM mb_user_wmc WHERE (wmc_serial_id = $1 AND fkey_user_id = $2) OR (wmc_serial_id = $3 AND wmc_public = 1)";
    $t = array('i', 'i', 'i');
    $v = array($wmcId, $userId, $wmcId);
    $res = db_prep_query($sql,$v,$t);
    if (!$res) {
        echo "Error while trying to find wmc in database!";
        die();
    } else {
        while($row = db_fetch_array($res)){
            //echo "WMC found with id <b>".$row["wmc_serial_id"]."</b> found in DB<br>";
            $wmcExists = true;
            $existingWmcId = $row["wmc_serial_id"];
        }
    }
} else {
    echo 'Mandatory parameter <b>wmcId</b> not given.<br/>'; 
    die(); 
}
if ($wmcExists) {
    //echo "WMC with id ".$existingWmcId." found and will be exported!";
    $owsContext = new OwsContext();
    //$owsContextResource = new OwsContextResource();
    //$owsContext->addResource($owsContextResource);
    $owsContext->readFromInternalWmc($existingWmcId);
    switch($outputFormat) {
        case "json":
            header("Content-Type: application/json");
            echo $owsContext->export("json");
            break;
        case "atom":
            header("Content-Type: text/xml");
            echo $owsContext->export("atom");
            break;
    }
} else {
    echo "WMC with id ".$wmcId." not found!";
}

?>
