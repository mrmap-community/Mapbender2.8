<?php


/**
 * @version   Changed: ### 2015-03-10 12:53:25 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */




//import classes
require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");


/**
 * publish the choosed data
 */

// get data from session and request
$user_id = Mapbender::session()->get("mb_user_id");
$geom_type = $_POST['geom_type'];
$geom_data = $_POST['wkt_geom'];
$sql;
// die('<pre>' . print_r($geom_data, 1) . '</pre>');
// calculate length of a linetring
// var_dump($geom_data);die;
if ($geom_type == 'line') {
    $sql = "SELECT st_length(st_GeogFromText( $1 )) as meter";

} else if ($geom_type == 'polygon') {
    $sql = "SELECT st_area(st_GeogFromText( $1 )) as sqm, st_length(st_GeogFromText( $1 )) as meter ";
}


$v = array($geom_data);
$t = array("c");
$res = db_prep_query($sql, $v, $t);
//fetch the array
$rslt= array();

while ($row = db_fetch_array($res)) {
    $rslt[0] = round(floatval($row[0]), 4);

    if (sizeof($row) > 1) {
        $rslt[1] = round(floatval($row[1]), 4);

    }
}


echo json_encode($rslt);
