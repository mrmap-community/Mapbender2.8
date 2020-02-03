<?php
# $Id: class_kml_ows.php 10111 2019-04-18 14:44:40Z armin11 $
# http://www.mapbender.org/index.php/class_wmc.php
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

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_point.php");

require_once(dirname(__FILE__)."/../classes/class_kml_geometry.php");
require_once(dirname(__FILE__)."/../classes/class_kml_placemark.php");
require_once(dirname(__FILE__)."/../classes/class_kml_parser_ows.php");


/**
 * Allows parsing a KML file, extracting the placemarks.
 *
 * @package KML
 */
class KML {

	//
	//
	// ------------------------------- public -----------------------------------------------
	//
	//
	/**
	 * The constructor function, currently empty.
	 */
	public function __construct() {
	}

	public function getGeoJsonSimpleStyleInfo() {
		return json_decode(file_get_contents("../../conf/geoJsonSimpleStyle.json"));
	}

	public function toSingleLineStringKml() {
		//KML 2.2 output
		$doc = new DOMDocument("1.0", CHARSET);
		$doc->preserveWhiteSpace = false;

		// attach kml and Document tag
		$e_kml = $doc->createElementNS("http://earth.google.com/kml/2.2", "kml");
		$e_document = $doc->createElement("Document");
		$e_kml->appendChild($e_document);
		$doc->appendChild($e_kml);

		// attach placemarks
		$e = new mb_notice("to string: #placemarks: " . count($this->placemarkArray));

		$lineStyleNode = $doc->createElement("Style");
		$lineStyleNode->setAttribute("id", "linestyleExample");
		$lineStyleColorNode = $doc->createElement("color", "7f0000ff");
		$lineStyleWidthNode = $doc->createElement("width", 4);
		$lineStyleNode->appendChild($lineStyleColorNode);
		$lineStyleNode->appendChild($lineStyleWidthNode);
		$e_document->appendChild($lineStyleNode);

		//
		// line segments first
		//
		$coordinates = "";

		for ($i = 0; $i < count($this->placemarkArray); $i++) {
			$currentPlacemark = $this->placemarkArray[$i];
			$e = new mb_notice("now: " . $i . " of " . (count($this->placemarkArray)-1) . " (is a " . get_class($currentPlacemark) . ")");

			switch ($currentPlacemark->getGeometryType()) {
				case "KMLLine" :
					$coordinatesArray = $currentPlacemark->getGeometry()->getPointArray();
					for ($j = 0; $j < count($coordinatesArray); $j++) {
						if (!($j == 0 && $i == 0)) {
							$coordinates .= " ";
						}
						$coordinates .= $coordinatesArray[$j]["x"] . "," . $coordinatesArray[$j]["y"] . "," . $coordinatesArray[$j]["z"];

					}
					break;
			}
		}
		// create a placemark tag with a geometry and add it to the document
		$e_coordinates = $doc->createElement("coordinates", $coordinates);
		$e_geometry = $doc->createElement("LineString");
		$e_geometry->appendChild($e_coordinates);
		$e_placemark = $doc->createElement("Placemark");
		$e_placemark->appendChild($e_geometry);
		$e_pl_name = $doc->createElement("name", "Route");
		$e_placemark->appendChild($e_pl_name);
		$e_pl_style = $doc->createElement("styleUrl", "#linestyleExample");
		$e_placemark->appendChild($e_pl_style);
		$e_document->appendChild($e_placemark);

/*
		//
		// now pois
		//
		// attach placemarks
		$e = new mb_notice("to string: #placemarks: " . count($this->placemarkArray));
		for ($i = 0; $i < count($this->placemarkArray); $i++) {
			$currentPlacemark = $this->placemarkArray[$i];

			$e = new mb_notice("now: " . $i . " of " . (count($this->placemarkArray)-1) . " (is a " . get_class($currentPlacemark) . ")");

			$pl_instructions = $currentPlacemark->getProperty("instruction");
			$pl_name_array = array();
			$pl_name = false;
			$pl_description = false;
			if ($pl_instructions != null) {
				$pl_name_array = explode("|", $pl_instructions);
			}

			switch ($currentPlacemark->getGeometryType()) {
				case "KMLPoint" :
					if (count($pl_name_array) > 2) {
						$pl_name = $pl_name_array[0];
						$pl_description = $pl_name_array[1];
					}
					$e_geometry = $doc->createElement("Point");
					$point = $currentPlacemark->getGeometry()->getPoint();
					$coordinates = $point["x"] . "," . $point["y"];
					$e_coordinates = $doc->createElement("coordinates", $coordinates);
					$e_geometry->appendChild($e_coordinates);
					break;

			}
			// create a placemark tag with a geometry and add it to the document
			if ($e_geometry) {
				$e_placemark = $doc->createElement("Placemark");
				$e_placemark->appendChild($e_geometry);
				if ($pl_name) {
					$e_pl_name = $doc->createElement("name", $pl_name);
					$e_placemark->appendChild($e_pl_name);
				}
				if ($pl_description) {
					$e_pl_description = $doc->createElement("description", $pl_description);
					$e_placemark->appendChild($e_pl_description);
				}
				$e_document->appendChild($e_placemark);
			}
		}
*/
		return $doc->saveXML();
	}

	/**
	 * @return string the merged KML document.
	 */
	public function mergeKMLDocuments($kmlArray) {
		//parse XML Document elements from KML
		//Merged KML 2.2 output
		$mergedKMLDoc = new DOMDocument("1.0", CHARSET);
		$mergedKMLDoc->preserveWhiteSpace = false;
		$mergedKML = $mergedKMLDoc->createElementNS("http://earth.google.com/kml/2.2", "kml");
		foreach ($kmlArray as $kml) {
			$kmlDomObject = new DOMDocument();
			$kmlDomObject->loadXML($kml);
			$xpathKml = new DOMXpath($kmlDomObject);
			$defaultNamespace = $kmlDomObject->lookupNamespaceURI($kmlDomObject->namespaceURI);
			$xpathKml->registerNamespace('kml', $defaultNamespace);
			$documentNodeList = $xpathKml->query('/kml:kml/kml:Document');
			for ($i = ($documentNodeList->length)-1; $i >= 0; $i--) {
				$mergedKML->appendChild($mergedKMLDoc->importNode($documentNodeList->item($i), true));
			}
		}
		$mergedKMLDoc->appendChild($mergedKML);
		return $mergedKMLDoc->saveXML();
	}

	/**
	 * @return string the KML document.
	 */
	public function __toString() {
		//read configuration for geojson simple style from conf folder - to handle style information from geojson if available
		$geoJsonStyleInfo = $this->getGeoJsonSimpleStyleInfo();
		if (!$this->kml) {
			//KML 2.2 output
			$doc = new DOMDocument("1.0", CHARSET);
			$doc->preserveWhiteSpace = false;

			//attach kml and Document tag
			$e_kml = $doc->createElementNS("http://earth.google.com/kml/2.2", "kml");
			$e_document = $doc->createElement("Document");
			$e_kml->appendChild($e_document);
			$doc->appendChild($e_kml);
			$styleHashArray = array();
			$styleArray = array();
			$placemarkArray = array();
			//attach placemarks
			$e = new mb_notice("to string: #placemarks: " . count($this->placemarkArray));
			for ($i = 0; $i < count($this->placemarkArray); $i++) {
				$currentPlacemark = $this->placemarkArray[$i];

				$e = new mb_notice("now: " . $i . " of " . (count($this->placemarkArray)-1) . " (is a " . get_class($currentPlacemark) . ")");

				$pl_instructions = $currentPlacemark->getProperty("instruction");
				$pl_name_array = array();
				$pl_name = false;
				$pl_description = false;
				if ($pl_instructions != null) {
					$pl_name_array = explode("|", $pl_instructions);
				}

				switch ($currentPlacemark->getGeometryType()) {
					case "KMLPoint" :
						if (count($pl_name_array) > 2) {
							$pl_name = $pl_name_array[0];
							$pl_description = $pl_name_array[1];
						}
						$e_geometry = $doc->createElement("Point");
						$point = $currentPlacemark->getGeometry()->getPoint();
						$coordinates = $point["x"] . "," . $point["y"];
						$e_coordinates = $doc->createElement("coordinates", $coordinates);
						$e_geometry->appendChild($e_coordinates);
						break;

					case "KMLPolygon" :
						//<Polygon><outerBoundaryIs><LinearRing><coordinates>100,0 101,0 101,1 100,1 100,0</coordinates></LinearRing></outerBoundaryIs></Polygon>
						$e_geometry = $doc->createElement("Polygon");
						$e_outer = $doc->createElement("outerBoundaryIs");
						$e_outer_lr = $doc->createElement("LinearRing");
						$coordinatesArray = $currentPlacemark->getGeometry()->getOuterBoundary()->getPointArray();
						$coordinates = "";
						for ($j = 0; $j < count($coordinatesArray); $j++) {
							if ($j > 0) {
								$coordinates .= " ";
							}
							$coordinates .= $coordinatesArray[$j]["x"] . "," . $coordinatesArray[$j]["y"] . "," . $coordinatesArray[$j]["z"];
						}
						$e_coordinates = $doc->createElement("coordinates", $coordinates);
						$e_outer_lr->appendChild($e_coordinates);
						$e_outer->appendChild($e_outer_lr);
						$e_geometry->appendChild($e_outer);
						//TODO - fill out inner rings as holes
						break;

					case "KMLLine" :
						if (count($pl_name_array) > 2) {
							$pl_description = $pl_name_array[1];
						}
						$e_geometry = $doc->createElement("LineString");
						$coordinatesArray = $currentPlacemark->getGeometry()->getPointArray();
						$coordinates = "";
						for ($j = 0; $j < count($coordinatesArray); $j++) {
							if ($j > 0) {
								$coordinates .= " ";
							}
							$coordinates .= $coordinatesArray[$j]["x"] . "," . $coordinatesArray[$j]["y"] . "," . $coordinatesArray[$j]["z"];
						}
						$e_coordinates = $doc->createElement("coordinates", $coordinates);
						$e_geometry->appendChild($e_coordinates);
						break;

/*	TODO: Multigeometries
					case "KMLMultiGeometry" :
						break;
*/
				}
				//check for properties
				$currentProperties = $currentPlacemark->getProperties();
				if (count($currentProperties) > 0) {
					$e_ExtendedData = $doc->createElement("ExtendedData");
				}
				//read out attribute information
				//initial geojson style handling
				$currentPropertiesSimpleStyle = array();
				foreach($currentProperties as $key => $value){
					$e_Data = $doc->createElement("Data");
					$e_value = $doc->createElement("value");
					$e_Data->setAttribute("name", $key);
					if (isset($value)) {
						$e_DataValue = $doc->createTextNode($value);
					} else {
						$e_DataValue = $doc->createTextNode('null');
					}
					$e_value->appendChild($e_DataValue);
					$e_Data->appendChild($e_value);
					$e_ExtendedData->appendChild($e_Data);
					//
				}
				//parse styling information from simplestyle spec to define it in header of kml
				//switch for geometry type
				switch ($currentPlacemark->getGeometryType()) {
					case "KMLPoint" :
						foreach($geoJsonStyleInfo->point->simple_style_spec_attributes as $attributeKey => $attributeValue) {
							//if (array_key_exists($attributeValue, $currentProperties)) {
								if (isset($currentProperties[$attributeValue])) {
									$value = $currentProperties[$attributeValue];
								} else {
									//set default value!
									$value = $geoJsonStyleInfo->point->simple_style_spec_defaults[$attributeKey];
								}
							//}
							$currentPropertiesSimpleStyle[$attributeValue] = $value;
						}
						//build style part
						//points don't have a color, cause the color is part of the icon in simplestyle-spec
						$hash = md5('point'.$currentPropertiesSimpleStyle['marker-size'].$currentPropertiesSimpleStyle['marker-symbol'].$currentPropertiesSimpleStyle['marker-color']);
						if (!in_array($hash, $styleHashArray)) {
							//generate style part
							$e_Style = $doc->createElement("Style");
							$e_Style->setAttribute("id", $hash);
							$e_IconStyle = $doc->createElement("IconStyle");

							$e_Icon = $doc->createElement("Icon");

							$e_href = $doc->createElement("href");
							
							$makiUrl = "https://raw.githubusercontent.com/mapbox/maki/master/icons/";
							switch($currentPropertiesSimpleStyle['marker-size']) {
								case "medium":
									$symbol = $makiUrl.$currentPropertiesSimpleStyle['marker-symbol']."-15.svg";
									break;
								case "small":
									$symbol = $makiUrl.$currentPropertiesSimpleStyle['marker-symbol']."-15.svg";
									break;
								case "large":
									$symbol = $makiUrl.$currentPropertiesSimpleStyle['marker-symbol']."-15.svg";
									break;
								default :
									$symbol = $makiUrl.$currentPropertiesSimpleStyle['marker-symbol']."-15.svg";
									break;
							}
							//test for marker-size as integer - map it to discrete values
							$sizeInt = (integer)$currentPropertiesSimpleStyle['marker-size'];
							if (is_int($sizeInt) && $sizeInt > 0) {
								if ($sizeInt >= 15 && $sizeInt < 25) {
									$currentPropertiesSimpleStyle['marker-size'] = "small";
								} elseif ($sizeInt >= 25 && $sizeInt < 35) {
									$currentPropertiesSimpleStyle['marker-size'] = "medium";
								} else {
									$currentPropertiesSimpleStyle['marker-size'] = "large";
								}
							}							
							//alternative for local maki icons
							$symbolUrl = MAPBENDER_PATH."/php/mod_getSymbolFromRepository.php?";
							$symbolUrl .= "marker-symbol=".$currentPropertiesSimpleStyle['marker-symbol']."&";
							$symbolUrl .= "marker-size=".$currentPropertiesSimpleStyle['marker-size']."&";
							$symbolUrl .= "marker-color=".$currentPropertiesSimpleStyle['marker-color'];
							//with mod_rewrite
							$symbolUrl = MAPBENDER_PATH."/../icons/maki/".ltrim($currentPropertiesSimpleStyle['marker-color'],'#')."/".$currentPropertiesSimpleStyle['marker-size']."/".$currentPropertiesSimpleStyle['marker-symbol'].".png";

							$e_hrefText = $doc->createTextNode($symbolUrl);
							$e_href->appendChild($e_hrefText);
							$e_Icon->appendChild($e_href);
							$e_IconStyle->appendChild($e_Icon);

							$e_hotSpot = $doc->createElement("hotSpot");
							$e_hotSpot->setAttribute("xunits", "fraction");
							$e_hotSpot->setAttribute("yunits", "fraction");
							$e_hotSpot->setAttribute("x", "0.5");
							$e_hotSpot->setAttribute("y", "0.5");

							/*$e_width = $doc->createElement("width");
							$e_widthText = $doc->createTextNode($currentPropertiesSimpleStyle['stroke-width']);
							$e_width->appendChild($e_widthText);
							$e_LineStyle->appendChild($e_width);*/

							$e_Style->appendChild($e_IconStyle);
							$e_Style->appendChild($e_hotSpot);
							//$e_document->appendChild($e_Style);
							$styleArray[] = $e_Style;
							$styleHashArray[] = $hash;							
						}
						$e_styleUrl = $doc->createElement("styleUrl");
						$e_styleUrlText = $doc->createTextNode("#".$hash);
						$e_styleUrl->appendChild($e_styleUrlText);
						break;
					case "KMLLine" :
						foreach($geoJsonStyleInfo->linestring->simple_style_spec_attributes as $attributeKey => $attributeValue) {
							//if (array_key_exists($attributeValue, $currentProperties)) {
								if (isset($currentProperties[$attributeValue])) {
									$value = $currentProperties[$attributeValue];
								} else {
									//set default value!
									$value = $geoJsonStyleInfo->linestring->simple_style_spec_defaults[$attributeKey];
								}
							//}
							$currentPropertiesSimpleStyle[$attributeValue] = $value;
						}
						//build style part
						$hash = md5('linestring'.$currentPropertiesSimpleStyle['stroke'].$currentPropertiesSimpleStyle['stroke-opacity'].$currentPropertiesSimpleStyle['stroke-width']);

						//map html hex color to kml color code
						$colorKml = $this->htmlColor2kmlColor($currentPropertiesSimpleStyle['stroke'], $currentPropertiesSimpleStyle['stroke-opacity']); 
						
						if (!in_array($hash, $styleHashArray)) {
							//generate style part
							$e_Style = $doc->createElement("Style");
							$e_Style->setAttribute("id", $hash);
							$e_LineStyle = $doc->createElement("LineStyle");

							$e_color = $doc->createElement("color");
							$e_colorText = $doc->createTextNode($colorKml);
							$e_color->appendChild($e_colorText);
							$e_LineStyle->appendChild($e_color);

							$e_width = $doc->createElement("width");
							$e_widthText = $doc->createTextNode($currentPropertiesSimpleStyle['stroke-width']);
							$e_width->appendChild($e_widthText);
							$e_LineStyle->appendChild($e_width);

							$e_Style->appendChild($e_LineStyle);
							//$e_document->appendChild($e_Style);
							$styleArray[] = $e_Style;
							$styleHashArray[] = $hash;							
						}
						$e_styleUrl = $doc->createElement("styleUrl");
						$e_styleUrlText = $doc->createTextNode("#".$hash);
						$e_styleUrl->appendChild($e_styleUrlText);
						break;
					case "KMLPolygon":
						foreach($geoJsonStyleInfo->polygon->simple_style_spec_attributes as $attributeKey => $attributeValue) {
							//if (array_key_exists($attributeValue, $currentProperties)) {
								if (isset($currentProperties[$attributeValue])) {
									$value = $currentProperties[$attributeValue];
								} else {
									//set default value!
									$value = $geoJsonStyleInfo->polygon->simple_style_spec_defaults[$attributeKey];
								}
							//}
							$currentPropertiesSimpleStyle[$attributeValue] = $value;
						}
						//build style part
						$hash = md5('polygon'.$currentPropertiesSimpleStyle['stroke'].$currentPropertiesSimpleStyle['stroke-opacity'].$currentPropertiesSimpleStyle['stroke-width']);

						$colorKml = $this->htmlColor2kmlColor($currentPropertiesSimpleStyle['stroke'], $currentPropertiesSimpleStyle['stroke-opacity']); 
						$colorKmlFill = $this->htmlColor2kmlColor($currentPropertiesSimpleStyle['fill'], $currentPropertiesSimpleStyle['fill-opacity']); 
						if (!in_array($hash, $styleHashArray)) {
							//generate style part
							$e_Style = $doc->createElement("Style");
							$e_Style->setAttribute("id", $hash);
							$e_LineStyle = $doc->createElement("LineStyle");

							$e_color = $doc->createElement("color");
							$e_colorText = $doc->createTextNode($colorKml);
							$e_color->appendChild($e_colorText);
							$e_LineStyle->appendChild($e_color);

							$e_width = $doc->createElement("width");
							$e_widthText = $doc->createTextNode($currentPropertiesSimpleStyle['stroke-width']);
							$e_width->appendChild($e_widthText);
							$e_LineStyle->appendChild($e_width);

							$e_Style->appendChild($e_LineStyle);

							//polystyle
							//<PolyStyle><color>7fff44ff</color></PolyStyle>
							$e_PolyStyle = $doc->createElement("PolyStyle");
							$e_color = $doc->createElement("color");
							$e_colorText = $doc->createTextNode($colorKmlFill);
							$e_color->appendChild($e_colorText);
							$e_PolyStyle->appendChild($e_color);
							$e_Style->appendChild($e_PolyStyle);
							//$e_document->appendChild($e_Style);
							$styleArray[] = $e_Style;
							$styleHashArray[] = $hash;							
						}
						$e_styleUrl = $doc->createElement("styleUrl");
						$e_styleUrlText = $doc->createTextNode("#".$hash);
						$e_styleUrl->appendChild($e_styleUrlText);
						break;
				}
				//overwrite name and description with info from geojson simplestyle spec
				//if (!isset($pl_name)) {
					$pl_name = $currentPropertiesSimpleStyle['title'];
				//}
				//if (!isset($pl_description)) {
					$pl_description = $currentPropertiesSimpleStyle['description'];
				//}
				// create a placemark tag with a geometry and add it to the document

				if ($e_geometry) {
					$e_placemark = $doc->createElement("Placemark");					
					if ($pl_name) {
						$e_pl_name = $doc->createElement("name", $pl_name);
						$e_placemark->appendChild($e_pl_name);
					}
					/*if ($pl_description) {
						$e_pl_description = $doc->createElement("description", $pl_description);
						$e_placemark->appendChild($e_pl_description);
					}*/
					if ($e_ExtendedData) {
						$e_placemark->appendChild($e_ExtendedData);
					}
					$e_placemark->appendChild($e_geometry);
					if ($e_styleUrl) {
						$e_placemark->appendChild($e_styleUrl);
					}
					$placemarkArray[] = $e_placemark;
				}
			}
			foreach($styleArray as $style) {
					$e_document->appendChild($style);
			}
			foreach($placemarkArray as $placemark) {
					$e_document->appendChild($placemark);
			}
			$this->kml = $doc->saveXML();
		}
		return $this->kml;
	}

	/**
	 * @return KML color code from HTML color code and opacity values.
	 * https://stackoverflow.com/questions/15202079/convert-hex-color-to-rgb-values-in-php
	 * https://stackoverflow.com/questions/13034702/google-maps-kml-8-digit-hex-code
	 * https://developers.google.com/kml/documentation/kmlreference#color
	 */
	private function htmlColor2kmlColor($hex, $alpha) {
   		$hex = str_replace('#', '', $hex);
   		$length = strlen($hex);
   		$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
   		$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
   		$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
   		if ( $alpha ) {
      			$rgb['a'] = ceil($alpha * 255);
   		}
			/*$e = new mb_exception("alpha: ".$rgb['a']."hex: ".dechex($rgb['a']));
			$e = new mb_exception("r: ".$rgb['r']."hex: ".dechex($rgb['r']));
			$e = new mb_exception("g: ".$rgb['g']."hex: ".dechex($rgb['g']));
			$e = new mb_exception("b: ".$rgb['b']."hex: ".dechex($rgb['b']));*/
		//encode to kml:
		return dechex($rgb['a']).dechex($rgb['b']).dechex($rgb['g']).dechex($rgb['r']);
	}
 
	/**
	 * @return string the ID of this KML.
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * parses an incoming KML, creates the object,
	 * stores the kml in the object and in the database.
	 *
	 * @param  string  a KML document.
	 * @return boolean true if the parsing succeded, else false.
	 */
	public function parseKml ($kml) {
		$this->kml = $kml;

		if (!$this->storeInDb()) {
			return false;
		}

		$parser = new KmlOwsParser();
		$parser->parseKML($kml, $this->id);
		$this->placemarkArray = $parser->placemarkArray;
		$this->featureCollectionMD = $parser->featureCollectionMD;
		return true;
	}

	public function transform($targetEpsg){
	    $numberOfPlacemarks = count($this->placemarkArray);
		if ($numberOfPlacemarks > 0) {
			for ($i=0; $i < $numberOfPlacemarks; $i++) {
			    $this->placemarkArray[$i]->transform($targetEpsg);
			}
		}
		else {
			$e = new mb_exception("KML: toGeoJSON: this placemarkArray is empty!");
		}
		return true;
	}
	/**
	 * parses an incoming GeoJSON, creates the object,
	 * stores the kml in the object and in the database.
	 *
	 * @param string a geoJSON.
	 * @return boolean true if the parsing succeded, else false.
	 */
	public function parseGeoJSON ($geoJSON) {
		$this->kml = "";
		if (!$this->storeInDb()) {
			return false;
		}
		$parser = new KmlOwsParser();
		$parser->parseGeoJSON($geoJSON, $this->id);
		$e = new mb_notice("parsing finished...#placemarks: " . count($this->placemarkArray) . " (" . count($parser->placemarkArray) . ")");
		$this->placemarkArray = $parser->placemarkArray;

		return true;
	}

	/**
	 * @return string the geoJSON representation of the KML.
	 */
	public function toGeoJSON($str) {
		$numberOfPlacemarks = count($this->placemarkArray);

		if ($numberOfPlacemarks > 0) {
			$str .= "\"features\": [";
			for ($i=0; $i < $numberOfPlacemarks; $i++) {
				if ($i > 0) {
					$str .= ",";
				}
				$str .= $this->placemarkArray[$i]->toGeoJSON();
			}
			$str .= "]}";
		}
		else {
			$e = new mb_exception("KML: toGeoJSON: this placemarkArray is empty!");
		}
		return $str;
	}

	public function createGeoJSON(){
   		$str = "{";
		$str.= "\"type\": \"FeatureCollection\",";

	    if (sizeof($this->featureCollectionMD) > 0) {

			foreach ($this->featureCollectionMD as $key => $value) {

					$str.= "\"".$key."\": \"".$value."\",";
			}

		}

		$completeString = $this->toGeoJSON($str);
		return $completeString;
	}
	private function updateInDb($kmlDoc, $kmlId) {
		$sql = "UPDATE gui_kml SET kml_doc = $1 WHERE kml_id = $2";
		$v = array($kmlDoc, $kmlId);
		$t = array("s", "i");
		$result = db_prep_query($sql, $v, $t);
		if (!$result) {
			$e = new mb_exception("class_kml: kml update failed! " . db_error());
			return false;
		}
	}

	public function updateKml ($kmlId, $placemarkId, $geoJSON) {
		$kmlFromDb = $this->getKmlDocumentFromDB($kmlId);

		if ($kmlFromDb !== NULL) {
			// load the KML from the database in the DOM object
			$kmlDoc_DOM = new DOMDocument("1.0");
			$kmlDoc_DOM->encoding = CHARSET;
			$kmlDoc_DOM->preserveWhiteSpace = false;
			$kmlDoc_DOM->loadXML($kmlFromDb);

			//load the geoJSON
			$json = new Mapbender_JSON();
			$geoObj = $json->decode($geoJSON);

			// construct an array that holds all metadata of the placemark
			$metadataObj = $geoObj->properties;

			// construct an array that holds all geometries of the placemark
			$geometryObj = $geoObj->geometry;
			$geometryType = $geometryObj->type;
			if ($geometryType == "GeometryCollection") {
				$geometryArray = $geometryObj->geometries;
			}
			else if ($geometryType == "Point" || $geometryType == "LineString" || $geometryType == "Polygon") {
				$geometryArray = array($geometryObj);
			}
			else {
				$e = new mb_exception("class_kml: Invalid geometry type " . $geometryType);
				return false;
			}

			//
			// apply the changes
			//

			$currentPlacemarkArray = $kmlDoc_DOM->getElementsByTagName("Placemark");
			$currentPlacemark = $currentPlacemarkArray->item($placemarkId);

			if ($currentPlacemark) {
				$metadataUpdateSuccessful = $this->updateMetadata($currentPlacemark, $metadataObj);
				$geometryUpdateSuccessful = $this->updateGeometries($currentPlacemark, $geometryArray);
			}
			else {
				$e = new mb_exception("class_kml.php: updateKml: placemark " . $placemarkId . " not found in KML " . $kmlId . ".");
				return false;
			}

			if ($metadataUpdateSuccessful && $geometryUpdateSuccessful) {
				$updatedKml = $kmlDoc_DOM->saveXML();

				$this->updateInDb($updatedKml, $kmlId);
			}
			else {
				if (!$metadataUpdateSuccessful) {
					$e = new mb_exception("class_kml: Updating the metadata failed, no database update.");
				}
				if (!$geometryUpdateSuccessful) {
					$e = new mb_exception("class_kml: Updating the geometries failed, no database update.");
				}
				return false;
			}
		}
		else {
			$e = new mb_exception("class_kml: No KML found in database, no database update. " . db_error());
			return false;
		}
		return true;
	}

	//
	//
	// ------------------------------- private -----------------------------------------------
	//
	//

	/**
	 * Store this KML in the database, and sets the ID.
	 *
	 * @return boolean true, if the KML could be stored in the database; else false.
	 */
	private function storeInDb () {
		if (Mapbender::session()->get("mb_user_id") && Mapbender::session()->get("mb_user_gui")) {
			$con = db_connect(DBSERVER,OWNER,PW);
			db_select_db(DB,$con);

			$sql  = "INSERT INTO gui_kml ";
			$sql .= "(fkey_mb_user_id, fkey_gui_id, kml_doc, kml_name, kml_description, kml_timestamp) ";
			$sql .= "VALUES ";
			$sql .= "($1, $2, $3, $4, $5, $6)";
			$v = array (Mapbender::session()->get("mb_user_id"), Mapbender::session()->get("mb_user_gui"), $this->kml, "name", "description", time());
			$t = array ("i", "s", "s", "s", "s", "s");
			$res = db_prep_query($sql, $v, $t);
			if (!$res) {
				$e = new mb_exception("class_kml.php: storeInDb: failed to store KML in database: " . db_error());
				return false;
			}

			$this->id = db_insert_id($con, "gui_kml", "kml_id");
			return true;
		}
		else {
			// should be false, but code in caller has to be changed first.
			return true;
		}
	}

	/**
	 * @param  integer  the ID of the KML.
	 * @return string   the KML document with the given ID.
	 */
	public function getKmlDocumentFromDB ($kmlId) {
		$con = db_connect(DBSERVER,OWNER,PW);
		db_select_db(DB,$con);
		//get KML from database (check if user is allowed to access)

# for now, do not restrict access
#		$sql = "SELECT kml_doc FROM gui_kml WHERE kml_id = $1 AND fkey_mb_user_id = $2 AND fkey_gui_id = $3 LIMIT 1";
#		$v = array($kmlId, Mapbender::session()->get("mb_user_id"), Mapbender::session()->get("mb_user_gui"));
#		$t = array("i", "i", "s");

		$sql = "SELECT kml_doc FROM gui_kml WHERE kml_id = $1 LIMIT 1";
		$v = array($kmlId);
		$t = array("i");

		$result = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($result);
		if ($row) {
			return $row["kml_doc"];
		}
		else {
			$e = new mb_exception("class_kml.php: getKMLDocumentFromDB: no KML found for ID " . $kmlId);
		}
		return "";
	}

	/**
	 * @param  string the tag name.
	 * @return string the tag name without its namespace.
	 */
	private function sepNameSpace($s){
		$c = mb_strpos($s, ":");
		if ($c > 0) {
			$s = mb_substr($s, $c+1);
		}
		return $s;
	}

	private function updateGeometries($currentPlacemark, $geometryArray) {
		$cnt = 0;
		$childNodes = $currentPlacemark->childNodes;

		foreach ($childNodes as $childNode) {
			$name = $childNode->nodeName;
			if ( in_array($name, array("Point","LineString","Polygon"))) {
				$returnValue = $this->updateGeometry($childNode, $geometryArray[$cnt]);
				if (!$returnValue) {
					return false;
				}
				$cnt ++;
			}
			else if ($name == "MultiGeometry") {
				return $this->updateGeometries($childNode, $geometryArray);
			}
		}
		return true;
	}

	private function updateGeometry ($currentNode, $geometry) {
		$json = new Mapbender_JSON();
		$currentNode_SimpleXML = simplexml_import_dom($currentNode);

		$currentTypeXml = mb_strtoupper($currentNode->nodeName);
		$currentTypeGeoJson = mb_strtoupper($geometry->type);

		if ($currentTypeGeoJson != $currentTypeXml) {
			$e = new mb_exception("class_kml: geometry type mismatch: geoJSON: " . $currentTypeGeoJson . "; XML: " . $currentTypeXml);
			return false;
		}
		if ($currentTypeXml == "POLYGON") {
			// GML 3
			$gmlNode = $currentNode_SimpleXML->{"exterior"}->{"LinearRing"}->{"posList"};
			$kmlNode = $currentNode_SimpleXML->{"outerBoundaryIs"}->{"LinearRing"}->{"coordinates"};
			if ($gmlNode && $gmlNode->asXML()) {
				$currentNode_SimpleXML->{"exterior"}->{"LinearRing"}->{"posList"} = preg_replace("/,/", " ", preg_replace("/\[|\]/", "", $json->encode($geometry->coordinates)));
			}
			// KML 2.2
			else if ($kmlNode && $kmlNode->asXML()) {
				$currentNode_SimpleXML->{"outerBoundaryIs"}->{"LinearRing"}->{"coordinates"} = preg_replace("/\],/", " ", preg_replace("/\][^,]|\[/", "", $json->encode($geometry->coordinates)));
			}
		}
		elseif ($currentTypeXml == "POINT") {
			$gmlNode = $currentNode_SimpleXML->{"pos"};
			$kmlNode = $currentNode_SimpleXML->{"coordinates"};

			// GML 3
			if ($gmlNode && $gmlNode->asXML()) {
				$currentNode_SimpleXML->{"pos"} = preg_replace("/,/", " ", preg_replace("/\[|\]/", "", $json->encode($geometry->coordinates)));
			}
			// KML 2.2
			else if ($kmlNode && $kmlNode->asXML()) {
				$currentNode_SimpleXML->{"coordinates"} = preg_replace("/\[|\]/", "", $json->encode($geometry->coordinates));
			}
		}
		elseif ($currentTypeXml == "LINESTRING") {
			$gmlNode = $currentNode_SimpleXML->{"posList"};
			$kmlNode = $currentNode_SimpleXML->{"coordinates"};

			// GML 3
			if ($gmlNode && $gmlNode->asXML()) {
				$currentNode_SimpleXML->{"posList"} = preg_replace("/,/", " ", preg_replace("/\[|\]/", "", $json->encode($geometry->coordinates)));
			}
			// KML 2.2
			else if ($kmlNode && $kmlNode->asXML()) {
				$currentNode_SimpleXML->{"coordinates"} = preg_replace("/\[|\]/", "", $json->encode($geometry->coordinates));
			}
		}
		return true;
	}

	private function updateMetadata($currentPlacemark, $metadataObj) {
		$metadataExistsAndUpdateSucceeded = true;

		$currentPlacemark_SimpleXML = simplexml_import_dom($currentPlacemark);
		$extendedDataNode = $currentPlacemark_SimpleXML->{"ExtendedData"};
		if ($extendedDataNode) {
			$metadataExistsAndUpdateSucceeded = false;

			// Either, data is within a SCHEMADATA tag...
			$simpleDataNodes = $extendedDataNode->{"SchemaData"}->{"SimpleData"};
			if ($simpleDataNodes) {
				foreach ($simpleDataNodes as $simpleDataNode) {
					$tmp = dom_import_simplexml($simpleDataNode);
					$name = $tmp->getAttribute("name");
					// if there is a metadata entry, update it
					if (isset($metadataObj->$name)) {
						$tmp->nodeValue = $metadataObj->$name;
					}
				}

				$metadataExistsAndUpdateSucceeded = true;
			}

			// ...or within a DATA tag
			$dataNodes = $extendedDataNode->{"Data"};
			if ($dataNodes && !$metadataExistsAndUpdateSucceeded) {
				foreach ($dataNodes as $dataNode) {
					$tmp = dom_import_simplexml($dataNode);
					$name = $tmp->getAttribute("name");
					// if there is a metadata entry, update it
					if (isset($metadataObj->$name)) {
						$tmp->nodeValue = $metadataObj->$name;
					}
				}
				$metadataExistsAndUpdateSucceeded = true;
			}
		}
		return $metadataExistsAndUpdateSucceeded;
	}

	/**
	 * The KML document.
	 */
	private $kml;

	/**
	 * The ID of this KML in the database.
	 */
	private $id;

	/**
	 * An array of {@link KMLPlacemark}
	 */
	private $placemarkArray = array();
}
?>
