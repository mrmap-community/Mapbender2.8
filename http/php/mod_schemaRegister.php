<?php
#
# http://www.mapbender2.org/
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

require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";

/* Initial import of json */

//read string from file

/*$polygonSchema = file_get_contents('../../conf/polygonType-Schema.json');
$polylineSchema = file_get_contents('../../conf/polylineType-Schema.json');
$pointSchema = file_get_contents('../../conf/pointType-Schema.json');

$sql = "DELETE from json_schema"; 
$res = db_query($sql);
$uuid = new Uuid();
$sql = "INSERT INTO json_schema (version, uuid, title, geomtype, schema, public, created) VALUES ('draft-04', '".$uuid."', 'Standard Polygon', 3, '".$polygonSchema."', TRUE, EXTRACT(EPOCH FROM NOW())::INTEGER)";
$res = db_query($sql);
$uuid = new Uuid();
$sql = "INSERT INTO json_schema (version, uuid, title, geomtype, schema, public, created) VALUES ('draft-04', '".$uuid."', 'Standard Polyline', 2, '".$polylineSchema."', TRUE, EXTRACT(EPOCH FROM NOW())::INTEGER)";
$res = db_query($sql);
$uuid = new Uuid();
$sql = "INSERT INTO json_schema (version, uuid, title, geomtype, schema, public, created) VALUES ('draft-04', '".$uuid."', 'Standard Point', 1, '".$pointSchema."', TRUE, EXTRACT(EPOCH FROM NOW())::INTEGER)";
$res = db_query($sql);*/

//parameters:
//geometryType : point, linestring, polygon
//id:
//uuid:
//operation: list, show
//
$geometryType = "false";
//Parse REQUEST Parameters
if (isset($_REQUEST["geometryType"]) & $_REQUEST["geometryType"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["geometryType"];
	if (!($testMatch == '1' or $testMatch == '2' or $testMatch == '3')){ 
		//echo 'resource: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>geometryType</b> is not valid (point(1),polyline(2),polygon(3))<br/>'; 
		die(); 		
 	}
	$geometryType = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["id"];
	$pattern = '/^[\d,]*$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>id</b> is not valid (integer or cs integer list).<br/>'; 
		die(); 		
 	}
	$id = $testMatch;
	$testMatch = NULL;
}

if (isset($_REQUEST["operation"]) & $_REQUEST["operation"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["operation"];
	if (!($testMatch == 'list' or $testMatch == 'show')){ 
		//echo 'resource: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>operation</b> is not valid (list,show)<br/>'; 
		die(); 		
 	}
	$operation = $testMatch;
	$testMatch = NULL;
}

header('Content-Type: application/json');
switch($operation) {
	case "list":
		$sql = "SELECT uuid, id, title, updated FROM json_schema WHERE public IS TRUE ";
		switch($geometryType) {
			case "false":
				$v = array();
				$t = array();
				break;
			default:
				$sql .= "AND geomtype = $1";
				$v = array((integer)$geometryType);
				$t = array("i");
				break;	
		}
		break;
	case "show":
		$sql = "SELECT schema FROM json_schema WHERE id = $1 AND public IS TRUE ";
		$v = array($id);
		$t = array("i");
                break;
}

$res = db_prep_query($sql, $v, $t);

switch($operation) {	
	case "list":
		$schemaArray = array();
		$j = 0;
		while ($row = db_fetch_array($res)) {
			$schemaArray[$j] = array();
			$schemaArray[$j]['id'] = $row['id'];
			$schemaArray[$j]['uuid'] = $row['uuid'];
			$schemaArray[$j]['updated'] = date(DATE_ATOM, $row['updated']);
			$schemaArray[$j]['title'] = $row['title'];
			$j++;
		}
		echo json_encode($schemaArray);
		break;
	case "show":
		$row = db_fetch_array($res);
		$schema = $row["schema"];
		echo json_encode(json_decode($schema));
		break;
}


?>
