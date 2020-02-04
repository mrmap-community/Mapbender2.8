<?php
# http://www.mapbender.org/index.php/Monitor_Capabilities
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
require_once(dirname(__FILE__)."/../classes/class_gml2.php");
define("GML_HIGHLIGHT_Z_INDEX",1000);
$gml_string = Mapbender::session()->get("GML");
if ($gml_string) {
	//To parse gml extent header
	$gml2String = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>".$gml_string;
	//$e = new mb_exception("renderGML:".$gml2String);
	libxml_use_internal_errors(true);
	try {
		$gml2 = simplexml_load_string($gml2String);
		if ($gml2 === false) {
				foreach(libxml_get_errors() as $error) {
					$err = new mb_exception("javascripts/mod_renderGML.php: ".$error->message);
	    			}
				throw new Exception("javascripts/mod_renderGML.php: ".'Cannot parse SESSION GML!');
		}
	}
	catch (Exception $e) {
	    	$err = new mb_exception("javascripts/mod_renderGML.php: ".$e->getMessage());
		return false;
	}			
	//if parsing was successful			
	if ($gml2 !== false) {
		$gml2->addAttribute('xmlns:gml', 'http://www.opengis.net/gml');
		$gml2->registerXPathNamespace("default", "http://www.opengis.net/gml");
		$gml2->registerXPathNamespace("gml", "http://www.opengis.net/gml");
		if ($gml2->xpath('/FeatureCollection/featureMember/*/*/MultiPolygon')) {
			$e = new mb_notice("javascripts/mod_renderGML.php:  MultiPolygon found!");
			$multiPolygon = $gml2->xpath('/FeatureCollection/featureMember/*/*/MultiPolygon');
			$multiPolygonGml = $multiPolygon[0]->asXML();
			$e = new mb_notice("javascripts/mod_renderGML.php: MultiPolygon: ".$multiPolygonGml);
			$currentEpsg = Mapbender::session()->get("epsg");
			$e = new mb_notice("javascripts/mod_renderGML.php: currentEpsg: ".$currentEpsg);
			if ($currentEpsg !== '4326') {
				$sql = "SELECT st_asgml(st_transform(st_geomfromgml($1),$2::INT),2) AS geom";
				$v = array($multiPolygonGml, $currentEpsg);
				$t = array('s', 'i');
				$res = db_prep_query($sql,$v,$t);
				db_fetch_row($res);
				$multiPolygonGml = db_result($res, 0, 'geom');
			}
		}
	}
}
//select asewkt(transform(st_geomfromgml('<MultiPolygon srsName="EPSG:4326"><polygonMember><Polygon><outerBoundaryIs><LinearRing><coordinates>6,48 8,48 8,51 6,51 6,48</coordinates></LinearRing></outerBoundaryIs></Polygon></polygonMember></MultiPolygon>'),25832));
/*<FeatureCollection xmlns:gml="http://www.opengis.net/gml"><boundedBy><Box srsName="EPSG:4326"><coordinates>6,48 8,51</coordinates></Box></boundedBy><featureMember><gemeinde><title>BBOX</title><the_geom>*/
//$e = new mb_exception("renderGml invoked!");
if ($gml_string) {
	$gml = new gml2();
	if (isset($multiPolygonGml)) {
		$gml->parse_xml('<FeatureCollection xmlns:gml="http://www.opengis.net/gml"><featureMember><the_geom>'.$multiPolygonGml.'</the_geom></featureMember></FeatureCollection>');
	} else {
		$gml->parse_xml($gml_string);
	}
	echo "Mapbender.events.afterInit.register(highlight_init);\n";
	echo "function highlight_init() {\n";
	echo "var mf = new Array(";
	for ($i=0; $i<count($e_target); $i++) {
		if ($i>0) echo ", ";
		echo "'".$e_target[$i]."'";
	}
	echo ");\n";
	echo "hl = new Highlight(mf, 'GML_rendering', {'position':'absolute', 'top':'0px', 'left':'0px', 'z-index':" . GML_HIGHLIGHT_Z_INDEX . "});\n";
	echo $gml->exportMemberToJS(0, false);
	echo "hl.add(q);\n";
	echo "hl.paint();\n";
	echo "mb_registerSubFunctions('hl.paint()');\n";
	echo "}\n";
	$e = new mb_notice("renderGML: GML: " . $multiPolygonGml . "; EPSG:" . $currentEpsg);
	Mapbender::session()->set("GML",NULL);
	$e = new mb_notice("renderGML: deleting GML...");
}
else {
	$e = new mb_notice("renderGML: no GML.");
}
?>
