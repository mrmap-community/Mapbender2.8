<?php

/**
 * @version   Changed: ### 2015-02-12 14:11:41 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
// require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
/**
 * unpublish the the given wmc
 */

$user_id = Mapbender::session()->get("mb_user_id");
$wmc_serial_id = $_POST["wmc_serial_id"];


// $wmc = new wmc();




// $sql = 'UPDATE mb_user_wmc SET wmc_local_data_public=0, wmc_local_data_fkey_termsofuse_id =8 WHERE wmc_serial_id= $1 AND fkey_user_id = $2'; // $sql = 'select wmc from mb_user_wmc where wmc_serial_id = $1 and wmc_has_local_data = 1 and fkey_user_id = $2;';
$sql = 'UPDATE mb_user_wmc SET wmc_local_data_public=0, wmc_local_data_fkey_termsofuse_id = null WHERE wmc_serial_id= $1 AND fkey_user_id = $2'; // $sql = 'select wmc from mb_user_wmc where wmc_serial_id = $1 and wmc_has_local_data = 1 and fkey_user_id = $2;';
// $sql = 'SELECT wmc_local_data_public FROM mb_user_wmc WHERE wmc_serial_id= $1 AND fkey_user_id = $2'; // $sql = 'select wmc from mb_user_wmc where wmc_serial_id = $1 and wmc_has_local_data = 1 and fkey_user_id = $2;';

$v = array($wmc_serial_id, $user_id);
$t = array("i", "i");
$res = db_prep_query($sql, $v, $t);

if (!$res) {
    return false;
}
else {
    echo true;
}

?>
