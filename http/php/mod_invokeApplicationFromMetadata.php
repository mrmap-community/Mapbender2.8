<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";  
require_once dirname(__FILE__) . "/../classes/class_administration.php"; 
//parse id from application (metadata_id)
if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
	//validate to integer
	$testMatch = $_REQUEST["id"];
	$pattern = '/^[\d]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>id</b> is not valid integer.<br/>'; 
		die(); 		
 	}
	$id = $testMatch;
	$testMatch = NULL;
}

//select application from mb_metadata by id
$sql = "SELECT metadata_id, link, fkey_wmc_serial_id, fkey_gui_id, fkey_mapviewer_id FROM mb_metadata WHERE metadata_id = $1 AND type = 'application'";
$v = array($id);
$t = array('i');
$res = db_prep_query($sql, $v, $t);
if ($res) {
    $row = db_fetch_array($res);
    if (isset($row['metadata_id'])) {
        $admin = new administration();
        $notNullElements = array('fkey_gui_id', 'fkey_mapviewer_id', 'fkey_wmc_serial_id');
        foreach($notNullElements as $notNullElement) {
            if ($row[$notNullElement] == '' || $row[$notNullElement] == null) {
                $row[$notNullElement] = false;
            }
        }
        if (($row['fkey_gui_id'] != false && $row['fkey_mapviewer_id'] != false) || ($row['fkey_wmc_serial_id'] != false && $row['fkey_mapviewer_id'] != false)) {
            $accessUrl = $admin->getMapviewerInvokeUrl($row['fkey_mapviewer_id'], $row['fkey_gui_id'], $row['fkey_wmc_serial_id']);
        } else {
            $accessUrl = $row['link'];
        }
	
        //before redirect to the url - increment load count
        $sql = "SELECT fkey_metadata_id FROM metadata_load_count WHERE fkey_metadata_id = $1";
        $v = array($id);
        $t = array('i');
        $res = db_prep_query($sql, $v, $t);
        if ($res) {
            $row = db_fetch_array($res);
	    if (isset($row['fkey_metadata_id'])) {
	        $sql = "UPDATE metadata_load_count SET load_count = (load_count + 1) WHERE fkey_metadata_id = $1"; 
                $v = array($id);
                $t = array('i');
	        $res = db_prep_query($sql, $v, $t);
	    } else {
                $sql = "INSERT INTO metadata_load_count (fkey_metadata_id, load_count) VALUES ($1, 1)";
                $v = array($id);
                $t = array('i');
	        $res = db_prep_query($sql, $v, $t);
            }
	}
//echo $accessUrl;
        header("Location: ".$accessUrl);
    } else {
        echo "no metadata found with id = ".$id;
    }
} else {
    echo "No accessUrl for the requested application found in database!";
}
?>
