<?php
/*
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
include(dirname(__FILE__)."/../geoportal/class_gml3.php");
include(dirname(__FILE__) . "/../../conf/gazetteerFlst.conf");

//db connection
$con = db_connect($DBSERVER,$OWNER,$PW) or die ("Error while connecting database $dbname");
db_select_db(DB,$con);

// get data from POST
$command = $_REQUEST['command'];

$checkCommand = array(
					"getGmkNr",
					"getGmkName",
                    "getFluren",
                    "getFlz",
                    "getFln",
                    "getGeomForFlst"
				);

if(!in_array($command, $checkCommand)) {
    echo "Ungültiger Befehl";
    die();
}

$json = new Mapbender_JSON();

function getGeoJson ($featureType, $filter, $srs) {
    global $wfsUrl, $nameSpace, $authUserName, $authUserPassword;
    $admin = new administration();

	if ($srs == NULL) {
	$wfsUrl = $wfsUrl . "&NAMESPACE=" . $nameSpace. "&username=" . $authUserName . "&password=" . $authUserPassword . "&typeName=" . $featureType."&filter=";
	} else {
    $wfsUrl = $wfsUrl . "&NAMESPACE=" . $nameSpace. "&username=" . $authUserName . "&password=" . $authUserPassword . "&typeName=" . $featureType."&srsName=".$srs."&filter=";
	}
	$req = urldecode($wfsUrl).urlencode($admin->char_decode(stripslashes($filter)));
	#echo $req;
	#$e = new mb_exception(urldecode($req));
	
	$mygml = new gml3();
	
	#$auth = array();
	#$auth['username'] = $authUserName; 
	#$auth['password'] = $authUserPassword;
	#$auth['auth_type'] = "basic";
	$mygml->parseFile($req);
	#$mygml->parseGeometry($req);
		
	header("Content-type:application/x-json; charset=utf-8");
	$geoJson = $mygml->toGeoJSON();
	
	$jsonObj = json_decode($geoJson);
	return $jsonObj;
}


if($command == "getGmkNr") {
	$searchString = $_REQUEST['term'];
	$pattern = "/[a-z0-9]/i";
	if (!preg_match($pattern, $searchString)) {
		echo "Ungültiger Suchbegriff";
        die();
	}
	
    $searchFeaturetype = $featuretypeGmkNr;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><PropertyIsLike wildCard="*" singleChar="?" escape="#">
            				<PropertyName>' . $gmkNrAttr . '</PropertyName>
                			<Literal>*' . $searchString . '*</Literal>
        					</PropertyIsLike></Filter>';
	
	
	$resultObj = getGeoJson($searchFeaturetype, $filter);
	$resultArray = array();
	
    foreach($resultObj->features as $feature) {
        $resultArray[] = array (
            "id" => $feature->properties->ID,
            "label" => $feature->properties->ID,
            "value"   => $feature->properties->ID,
            "gmkName" => $feature->properties->NAME
        );
    }
    
    header("Content-type:application/json; charset=utf-8");
    echo json_encode($resultArray);
}

if($command == "getGmkName") {
	$searchString = utf8_encode($_REQUEST['term']);
	//$pattern = "/[a-z0-9]/i";
	//if (!preg_match($pattern, $searchString)) {
	//	echo "Ungültiger Suchbegriff";
        //die();
	//}
	
    $searchFeaturetype = $featuretypeGmkNr;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><PropertyIsLike wildCard="*" singleChar="?" escape="#" matchCase="false">
            				<PropertyName>' . $gmkNameAttr . '</PropertyName>
                			<Literal>*' . $searchString . '*</Literal>
        					</PropertyIsLike></Filter>';
 	$e = new mb_exception($filter);	
	$resultObj = getGeoJson($searchFeaturetype, $filter);
	$resultArray = array();
	
    foreach($resultObj->features as $feature) {
        $resultArray[] = array (
            "id" => $feature->properties->NAME,
            "label" => $feature->properties->NAME,
            "value"   => $feature->properties->NAME,
            "gmkNr" => $feature->properties->ID
        );
    }
    
    header("Content-type:application/json; charset=utf-8");
    echo json_encode($resultArray);
}

if($command == "getFluren") {
	$gmkNr = $_REQUEST['gmkNr'];
	$pattern = "/[0-9]/i";
	if (!preg_match($pattern, $gmkNr)) {
		echo "Ungültige Gemarkungsnr";
        die();
	}
	
    $searchFeaturetype = $featuretypeFlur;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><PropertyIsEqualTo>
            				<PropertyName>' . $gmkNrAttrFlur . '</PropertyName>
                			<Literal>' . $gmkNr . '</Literal>
        					</PropertyIsEqualTo></Filter>';
		
    $resultObj = getGeoJson($searchFeaturetype, $filter);
	$resultArray = array();
    foreach($resultObj->features as $feature) {
        $resultArray[] = $feature->properties->NAME;
    }
    
    header("Content-type:application/json; charset=utf-8");
    sort($resultArray);
    echo json_encode($resultArray);
}

if($command == "getFlz") {
	$gmkNr = $_REQUEST['gmkNr'];
	$flurNr = $_REQUEST['flurNr'];
	$pattern = "/[0-9]/i";
	if (!preg_match($pattern, $gmkNr)) {
		echo "Ungültige Gemarkungsnr";
        die();
	}
    if (!preg_match($pattern, $flurNr)) {
		echo "Ungültige Flurnr";
        die();
	}
	
    $searchFeaturetype = $featuretypeFlz;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><And>
        	<PropertyIsEqualTo>
        		<PropertyName>' . $gmkNrAttrFlz . '</PropertyName>
                <Literal>' . $gmkNr . '</Literal>
            </PropertyIsEqualTo>
            <PropertyIsEqualTo>
        		<PropertyName>' . $flurAttrFlz . '</PropertyName>
                <Literal>' . $flurNr . '</Literal>
            </PropertyIsEqualTo>
        </And></Filter>';
		
    $resultObj = getGeoJson($searchFeaturetype, $filter);
	$resultArray = array();
    foreach($resultObj->features as $feature) {
        $resultArray[] = $feature->properties->ID;
    }
    
    header("Content-type:application/json; charset=utf-8");
    $resultArray = array_unique($resultArray);
    sort($resultArray);
    echo json_encode($resultArray);
}

if($command == "getFln") {
	$gmkNr = $_REQUEST['gmkNr'];
	$flurNr = $_REQUEST['flurNr'];
	$flz = $_REQUEST['flz'];
	$pattern = "/[0-9]/i";
	if (!preg_match($pattern, $gmkNr)) {
		echo "Ungültige Gemarkungsnr";
        die();
	}
    if (!preg_match($pattern, $flurNr)) {
		echo "Ungültige Flurnr";
        die();
	}
    if (!preg_match($pattern, $flz)) {
		echo "Ungültiger Flz";
        die();
	}
	
    $searchFeaturetype = $featuretypeFln;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><And>
        	<PropertyIsEqualTo>
        		<PropertyName>' . $gmkNrAttrFlz . '</PropertyName>
                <Literal>' . $gmkNr . '</Literal>
            </PropertyIsEqualTo>
            <PropertyIsEqualTo>
        		<PropertyName>' . $flurAttrFlz . '</PropertyName>
                <Literal>' . $flurNr . '</Literal>
            </PropertyIsEqualTo>
            <PropertyIsEqualTo>
        		<PropertyName>' . $flzAttr . '</PropertyName>
                <Literal>' . $flz . '</Literal>
            </PropertyIsEqualTo>
        </And></Filter>';
		
    $resultObj = getGeoJson($searchFeaturetype, $filter);
    #print_r($resultObj);
    $resultArray = array();
    foreach($resultObj->features as $feature) {
        $resultArray[] = array("id" => $feature->properties->ID);
        
    }
    
    header("Content-type:application/json; charset=utf-8");
    sort($resultArray);
    echo json_encode($resultArray);
}
if($command == "getGeomForFlst") {
	$gmkNr = $_REQUEST['gmkNr'];
	$flurNr = $_REQUEST['flurNr'];
	$flz = $_REQUEST['flz'];
	$fln = $_REQUEST['fln'];
	$srs = $_REQUEST['srs'];
	$pattern = "/[0-9]/i";
	if (!preg_match($pattern, $gmkNr)) {
		echo "Ungültige Gemarkungsnr";
        die();
	}
    if (!preg_match($pattern, $flurNr)) {
		echo "Ungültige Flurnr";
        die();
	}
    if (!preg_match($pattern, $flz)) {
		echo "Ungültiger Flz";
        die();
	}
    
	if($fln != "") {
    	$flnFilter = '<PropertyIsEqualTo>
            		<PropertyName>' . $flnAttr . '</PropertyName>
                    <Literal>' . $fln . '</Literal>
                </PropertyIsEqualTo>';
	}
	
    $searchFeaturetype = $featuretypeFln;
    $filter = '<Filter xmlns="http://www.opengis.net/ogc" xmlns:app="http://www.deegree.org/app"><And>
        	<PropertyIsEqualTo>
        		<PropertyName>' . $gmkNrAttrFlz . '</PropertyName>
                <Literal>' . $gmkNr . '</Literal>
            </PropertyIsEqualTo>
            <PropertyIsEqualTo>
        		<PropertyName>' . $flurAttrFlz . '</PropertyName>
                <Literal>' . $flurNr . '</Literal>
            </PropertyIsEqualTo>
            <PropertyIsEqualTo>
        		<PropertyName>' . $flzAttr . '</PropertyName>
                <Literal>' . $flz . '</Literal>
            </PropertyIsEqualTo>
            '.$flnFilter.'
        </And></Filter>';
		
    $resultObj = getGeoJSON($searchFeaturetype, $filter,$srs);
    header("Content-type:application/json; charset=utf-8");
    echo json_encode($resultObj);
}
?>
