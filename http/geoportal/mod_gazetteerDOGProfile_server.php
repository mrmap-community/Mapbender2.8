<?php
# $Id: mod_gazetteerDOGProfile.php 
# http://www.mapbender.org/GazetteerDOGProfile
# Copyright (C) 2009 OSGeo
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
require_once(dirname(__FILE__)."/../geoportal/class_gml2_DOG.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");

function sendErrorMessage($data) {
	$resObj = array();
	$response = "error";
	$resObj["errorMessage"] = $data;
	$resObj["response"] = $response;
	
	header("Content-Type:application/x-json");
	$json = new Mapbender_JSON();
	echo $json->encode($resObj);
	die;
}

$command = $_POST["command"];
$pattern = "/[a-z]/i";
if (!preg_match($pattern, $command)) {
	sendErrorMessage("Invalid command " . htmlentities($command, ENT_QUOTES, CHARSET));
}

switch ($command) {
	case "getHtml" :
		getHtmlForm();
		break;
	case "searchGemeinden" :
		startSearch($command);
		break;
	case "searchStrassen" :
		startSearch($command);
		break;
	case "searchHauskoordinaten" :
		startSearch($command);
		break;	
	case "searchPlz" :
		startSearch($command);
		break;	
	default :
		getHtmlForm();
		break;
}

function getHtmlForm() {
	$htmlFormString = "<form onsubmit='return startGazetteer()' name='gazetteerDOGForm' id='gazetteerDOGForm' method='post' action=''>" .
					  "<div><b>Adresssuche:</b></div>" .
					
					  "<div id='gazetteerDOGOrtField'>" .
					  
				        "<input onclick='this.value = \"\";gazetteerStep = \"searchGemeinden\";' class='gazetteerDOG' type='text' name='gazetteerDOGOrtPlz' id='gazetteerDOGOrtPlz' value='Ort oder PLZ'>" .
						"<input type='submit' name='DOGGazetteerSearchButton' id='DOGGazetteerSearchButton' value='Suche'>" .
				      "</div>" .
					  "<div id='gazetteerDOGStrasseField'>" .
				        "<input class='gazetteerDOG' disabled type='text' name='gazetteerDOGStrasse' id='gazetteerDOGStrasse' value='Straße'>" .
				      "</div>" .
					  #"<div>Hausnummer</div>" .
	                  "<div>" .
				        "<input type='button' name='DOGGazetteerNewButton' value='Neue Suche' onClick='newSearch();'>" .
				      "</div>" .
					  "<div id='gazetteerDOGHausnummer' style='display:none;color:#808080;'>Hausnummern:</div>" .
				      "<div name='gazetteerProgressWheel' id='gazetteerProgressWheel' style='width:180px;'></div>" .
					"</form>";
	echo $htmlFormString;
}

function startSearch($command) {
	$wfsUrl = $_POST["wfsUrl"];
	$pattern = "/^(http(s?):\/\/{1})((\w+\.){1,})\w{2,}(\/?)$/i";

	$searchString = $_POST["searchFor"];
	$pattern = "/[a-z0-9]/i";
	if (!preg_match($pattern, $searchString)) {
		sendErrorMessage("Invalid searchString " . htmlentities($searchString, ENT_QUOTES, CHARSET));
	}
	
	$searchFeaturetype = $_POST["searchFeaturetype"];
	$pattern = "/[a-z0-9]/i";
	if (!preg_match($pattern, $searchFeaturetype)) {
		sendErrorMessage("Invalid searchFeaturetype " . htmlentities($searchFeaturetype, ENT_QUOTES, CHARSET));
	}
	
	$admin = new administration();
	
	$searchConditions = explode("|", $searchString);
	$propertyConditions = "";
	
	for ($i = 0; $i < count($searchConditions);$i++) {
		$conditionParams = explode("=",$searchConditions[$i]);
		if($command == "searchHauskoordinaten" || $command == "searchPlz") {
			$propertyConditions .= '<PropertyIsEqualTo>
                				<PropertyName>' . $conditionParams[0] . '</PropertyName>
                    			<Literal>' . $conditionParams[1] . '</Literal>
            					</PropertyIsEqualTo>';
		}
		else {
			$propertyConditions .= '<PropertyIsLike wildCard="*" singleChar="?" escape="#">
                				<PropertyName>' . $conditionParams[0] . '</PropertyName>
                    			<Literal>' . $conditionParams[1] . '*</Literal>
            					</PropertyIsLike>';
		}
	}
	if(count($searchConditions) > 1) {
		$propertyConditionsString = "<And>" . $propertyConditions . "</And>";
	}
	else {
		$propertyConditionsString = $propertyConditions;
	}

	$filter = '<wfs:GetFeature version="1.1.0" outputFormat="text/xml; subtype=gml/3.1.1"
        xmlns:wfs="http://www.opengis.net/wfs">
    <wfs:Query xmlns:app="http://www.deegree.org/app" typeName="' . $searchFeaturetype . '"
            xmlns:gml="http://www.opengis.net/gml" xmlns:ogc="http://www.opengis.net/ogc"
            xmlns:wfs="http://www.opengis.net/wfs" xmlns:dog="http://www.lverma.nrw.de/namespaces/dog"
            xmlns:iso19112="http://www.opengis.net/iso19112">
        <Filter xmlns="http://www.opengis.net/ogc">' . utf8_encode($propertyConditionsString) . '</Filter>
    </wfs:Query>
</wfs:GetFeature>';
	
	$admin = new administration();
	
	$filter = $admin->char_decode(stripslashes($filter));
	
	$connection = new connector();
	$connection->set("httpType", "post");
	$connection->set("httpContentType", "xml");
	$connection->set("httpPostData", $filter);
	$data = $connection->load($wfsUrl);
	
	if ($data === null) die('{}');
	
	$gml = new gml2();
	$geoJson = $gml->parseXML($data);
	
	header("Content-type:application/json; charset=utf-8");
	echo $geoJson;
	
	#$e = new mb_exception($filter);
	
	#$wfsUrl = $wfsUrl . "/services?REQUEST=GetFeature&SERVICE=WFS&VERSION=1.1.0&MAXFEATURES=5&TYPENAME=" . $featuretypeGemeinden . "&filter="; #REQUEST=GetFeature&SERVICE=WFS&VERSION=1.1.0&MAXFEATURES=5&TYPENAME=" . $featuretypeGemeinden . "&
	//$wfsUrl = $wfsUrl . "/services&filter=";
	//$req = urldecode($wfsUrl).urlencode($admin->char_decode(stripslashes($filter)));
	//$e = new mb_exception($req);
	//$mygml = new gml2();
	
	#$auth = array();
	#$auth['username'] = $authUserName; 
	#$auth['password'] = $authUserPassword;
	#$auth['auth_type'] = "basic";
	#$mygml->parseFile($req);
	
	#print_r($mygml); die;
	#header("Content-type:application/x-json; charset=utf-8");
	#echo $mygml->toGeoJSON();
}
?>
