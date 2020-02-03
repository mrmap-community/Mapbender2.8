<?php
# http://www.mapbender.org/index.php/Administration
# Copyright (C) 2002 CCGIS
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

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_connector.php");
require_once(dirname(__FILE__) . "/../classes/class_kml_ows.php");
require_once(dirname(__FILE__)."/../classes/class_geojson_style.php");

//FIXME: what about projections?
//header("Content-Type: text/x-json");
if(isset($_REQUEST["url"])){
    $url = $_REQUEST["url"];
}else{
    echo("{}");
    exit;
}
$kml = new KML();
$con = new connector();
$content = $con->load($url);

try {
    if(preg_match('/.kml$/', $url)) {
        header("Content-Type: text/plain");
        if($kml->parseKml($content)){
            $geojson  =  $kml->createGeoJSON();
            echo $geojson;
        }else{
            echo("{}");
        }
    }
    if(preg_match('/.(json|geojson)$/', $url)) {
	//set dummy style attributes if not exits!!!
	$jsonStyle = new geojson_style();
	$content = $jsonStyle->addDefaultStyles($content);
        header("Content-Type: text/plain");
        echo $content;
    }
    if(preg_match('/.(xml|gpx)$/', $url)) {
        header("Content-Type: text/plain");
        echo $content;
    }

} catch (Exception $e) {
    echo($e);
    die;
}

?>
