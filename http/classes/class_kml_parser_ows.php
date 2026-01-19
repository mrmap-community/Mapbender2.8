<?php
# $Id: class_kml_parser_ows.php 10111 2019-04-18 14:44:40Z armin11 $
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
require_once(dirname(__FILE__)."/../classes/class_kml_polygon.php");
require_once(dirname(__FILE__)."/../classes/class_kml_linearring.php");
require_once(dirname(__FILE__)."/../classes/class_kml_line.php");
require_once(dirname(__FILE__)."/../classes/class_kml_point.php");
require_once(dirname(__FILE__)."/../classes/class_kml_multigeometry.php");
require_once(dirname(__FILE__)."/../classes/class_kml_placemark.php");

/**
 * @package KML
 */
 class KmlOwsParser {
	var $placemarkArray = array();
	var $featureCollectionMD = array();

	public function __construct() {
	}

	public function parseGeoJSON ($geoJSON, $kmlId) {
		$json = new Mapbender_JSON();
		$geometryFromGeoJSON = $json->decode($geoJSON);
		$id = 0;
		if (gettype($geometryFromGeoJSON) == "object" && $geometryFromGeoJSON->type == "FeatureCollection") {
			if ($geometryFromGeoJSON->crs->type == "EPSG" && $geometryFromGeoJSON->crs->properties->code) {
				$epsg = $geometryFromGeoJSON->crs->properties->code;
			}
			// create Placemarks for each feature object
			for ($i = 0; $i < count($geometryFromGeoJSON->features); $i++) {
				$e = new mb_notice("parsing plm #" . $i . "...length of placemarkArray: " . count($this->placemarkArray));
				$feature = $geometryFromGeoJSON->features[$i];
				if (gettype($feature) == "object" && $feature->type == "Feature") {
					if ($feature->geometry->crs->type == "EPSG") {
						$epsg = $feature->geometry->crs->properties->code;
					}
					if (!$epsg) {
						$e = new mb_notice("EPSG is not set - defaults to 4326!");
						//$currentGeometry = false;
						$epsg = "4326";
					}
					$geometry = $feature->geometry;
					//TODO: missing MultiGeometry
					switch ($geometry->type) {
						case "LineString" :
							$coordinateList = "";
							for ($j = 0; $j < count($geometry->coordinates); $j++) {
								if ($j > 0) {
									$coordinateList .= " ";
								}
								//add zero altitude if only two dimensions are given
								if (count($geometry->coordinates[$j]) == 2) {
									$geometry->coordinates[$j][] = 0;
								}
								$coordinateList .= implode(",", $geometry->coordinates[$j]);
							}
							$currentGeometry = new KMLLine($coordinateList, $epsg);
							break;
						case "Point" :
							//add zero altitude if only two dimensions are given
							if (count($geometry->coordinates) == 2) {
								$geometry->coordinates[] = 0;
							}
							$coordinateList = implode(",", $geometry->coordinates);
							$currentGeometry = new KMLPoint($coordinateList, $epsg);
							break;
						case "Polygon" :
							//
							$coordinateList = "";
							$countLinearRings = count($geometry->coordinates);
							if ($countLinearRings == 0) {

							} elseif ($countLinearRings == 1) {
								for ($j = 0; $j < count($geometry->coordinates[0]); $j++) {
									if ($j > 0) {
										$coordinateList .= " ";
									}
									//add zero altitude if only two dimensions are given
									if (count($geometry->coordinates[0][$j]) == 2) {
										$geometry->coordinates[0][$j][] = 0;
									}
									$coordinateList .= implode(",", $geometry->coordinates[0][$j]);
								}
								$outerRing = new KMLLinearRing($coordinateList, $epsg);
								$currentGeometry = new KMLPolygon($outerRing);
							} elseif ($countLinearRings > 1) {
								for ($j = 0; $j < count($geometry->coordinates[0]); $j++) {
									if ($j > 0) {
										$coordinateList .= " ";
									}
									//add zero altitude if only two dimensions are given
									if (count($geometry->coordinates[0][$j]) == 2) {
										$geometry->coordinates[0][$j][] = 0;
									}
									$coordinateList .= implode(",", $geometry->coordinates[0][$j]);
								}
								$outerRing = new KMLLinearRing($coordinateList, $epsg);
								$currentGeometry = new KMLPolygon($outerRing);
								for ($k = 1; $k < $countLinearRings; $k++) {
									for ($j = 0; $j < count($geometry->coordinates[$k]); $j++) {
										if ($j > 0) {
											$coordinateList .= " ";
										}
										//add zero altitude if only two dimensions are given
										if (count($geometry->coordinates[$k][$j]) == 2) {
											$geometry->coordinates[$k][$j][] = 0;
										}
										$coordinateList .= implode(",", $geometry->coordinates[$k][$j]);
									}
									$innerRing = new KMLLinearRing($coordinateList, $epsg);
									$currentGeometry->appendInnerBoundary($innerRing);
								}
							}							
							break;
					}

					if ($currentGeometry) {
						$currentPlacemark = new KMLPlacemark($currentGeometry);
						if (gettype($feature->properties) == "object") {
							foreach ($feature->properties as $key => $value) {
								$currentPlacemark->setProperty($key, $value);
							}
							//some specific values
							$currentPlacemark->setProperty("Mapbender:kml", true);
							$currentPlacemark->setProperty("Mapbender:name", "unknown");
							$currentPlacemark->setProperty("Mapbender:id", $kmlId);
							$currentPlacemark->setProperty("Mapbender:placemarkId", $id);
							$e = new mb_notice("adding to placemarkArray (current length: " . count($this->placemarkArray) . ")");
							array_push($this->placemarkArray, $currentPlacemark);
							$e = new mb_notice("added...new length: " . count($this->placemarkArray));
							$id ++;
						}
					}
				}
				unset($epsg); //used for each feature!
			}
		}
		return true;
	}

	public function parseKML ($kml, $kmlId) {
		$doc = new DOMDocument("1.0");
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($kml);
		$xpath = new DOMXPath($doc);
		//$xpath->registerNamespace("kml","http://earth.google.com/kml/2.2");
		//$xpath->registerNamespace("http://www.opengis.net/kml/2.2");
		$xpath->registerNamespace("kml","http://www.opengis.net/kml/2.2");
		$styles =array();
		$styleNodes = $xpath->query("/kml:kml/kml:Document/kml:Style");

		//$e = new mb_exception("found this many styles:". $styleNodes->length);
		foreach($styleNodes as $styleNode){
		    $hrefNodes = $styleNode->getElementsByTagName("href");
		    if($hrefNodes->length > 0){
			    $href = $hrefNodes->item(0)->nodeValue;
			    $styles[$styleNode->getAttribute("id")] = array(
			        "href" => $href
			    );
		    }
		}

		$styleMapNodes = $xpath->query("/kml:kml/kml:Document/kml:StyleMap");
		//$e = new mb_exception($styleMapNodes->length);
		foreach($styleMapNodes as $styleMapNode){

		    foreach($styleMapNode->children as $child){
		        $keyNodes = $child->findElementsByTagName("key");
		        if($keyNodes->length > 0){
		            if($keyNodes->item(0)->nodeValue == "normal"){


    		            $styleNodes = $child->findElementsByTagName("Style");
    		            if($styleNodes->length > 0){
        		            $hrefNodes = $styleNodes->item(0)->getElementsByTagName("href");
        		            if($hrefNodes->length > 0){
        			            $href = $hrefNodes->item(0)->nodeValue;
        			            $styles[$styleMapNode->getAttribute("id")] = array(
        			        	"href" => $href
        			            );
        			            continue;
        		            }
    		            }



		                $styleUrlNodes = $child->findElementsByTagName("styleUrl");
		                if($styleUrlNodes->length > 0){
		                    $id = $styleUrlNodes->item(0)->nodeValue;
		                	if(substr($id,0,1) == "#"){
					            $id = substr($id,1);
					            $styles[$styleMapNode->getAttribute("id")] = array(
					                "href" => $styles[$id]["href"]
					            );
					        }else{
					            $e = new mb_exception("External style references not supported in KML parser");
					        }

		                }
		            }
		        }
		    }
		    $styles[$styleMapNode->getAttribute("id")] = array(
				"href" => $href
			);
		}
		/*
		 * Get geometry information only, store it in placemarkArray
		 */
		$placemarkTagArray = $doc->getElementsByTagName("Placemark");

		if (count($placemarkTagArray) > 0) {
			$id = 0;

			foreach ($placemarkTagArray as $node) {
				$geometryArray = $this->getGeometryArrayFromPlacemarkOrMultigeometryNode($node);
				$metadataArray = $this->getMetadataFromPlacemarkNode($node);
				/*
				 * For a placemark, the geometryArray should only contain 1 geometry!
				 */
				for ($i=0; $i < count($geometryArray); $i++) {
				    $currentPlacemark = new KMLPlacemark($geometryArray[$i]);

					$currentPlacemark->setProperty("Mapbender:kml", true);
					$currentPlacemark->setProperty("Mapbender:name", "unknown");
					$currentPlacemark->setProperty("Mapbender:id", $kmlId);
					$currentPlacemark->setProperty("Mapbender:placemarkId", $id);
				    foreach ($metadataArray as $key => $value) {
						$currentPlacemark->setProperty($key, $value);
					}

					// add description and name:
					$namesNode = $node->getElementsByTagName('name');
					if($namesNode->length > 0){
						$name = trim($namesNode->item(0)->nodeValue);
					}
					$descriptionsNode = $node->getElementsByTagName('description');
					if($descriptionsNode->length > 0){
						$description = trim($descriptionsNode->item(0)->nodeValue);
					}
					//TODO: dont add the properties twice!
					// $currentPlacemark->setProperty("name", $name);
					// $currentPlacemark->setProperty("description", $description);

					//get style information for KML point objects
					if(get_class($geometryArray[$i]) == "KMLPoint") {
    					//Inline Styles take precedence over shared styles
    					$styleNodes = $node->getElementsByTagName('Style');
    					if($styleNodes->length > 0) {
    				        $styleNode = $styleNodes->item(0);
    				        $hrefNodes = $styleNode->getElementsByTagName("href");
            			    if($hrefNodes->length > 0){
            				    $href = $hrefNodes->item(0)->nodeValue;
            				    $currentPlacemark->setProperty("Mapbender:icon",$href);
            			    }
    					}else{
    					    $styleUrlNodes = $node->getElementsByTagName('styleUrl');
    					    if($styleUrlNodes->length > 0) {
    					        $styleUrlNode = $styleUrlNodes->item(0);
    					        // cut off leading #
    					        $id = $styleUrlNode->nodeValue;
                                $e = new mb_notice("found style url reference, id is '$id'");
    					        if(substr($id,0,1) == "#"){
    					            $id = substr($id,1);
    					        }else{
    					            $e = new mb_exception("External style references not supported in KML parser");
    					        }
    					        $e = new mb_notice("Looking up style: $id");
    				             $currentPlacemark->setProperty("Mapbender:icon",$styles[$id]["href"]);

    					    }
    					}
					}

					array_push($this->placemarkArray, $currentPlacemark);
				}
				$id ++;
			}
		}
		else {
			$e = new mb_exception("class_kml.php: KMLOWSParser: No placemarks found in KML.");
			return false;
		}



		/**
		 * add metadata for the feature-collection
		 *
		 */
		$extendedData = $xpath->query('//*[local-name() = "kml"]/*[local-name() = "Document"]/*[local-name() = "ExtendedData"]');
		if ($extendedData->length > 0) { // check for metadata

    		foreach ($extendedData as $metaData) {
    			$data = $metaData->getElementsByTagName('Data');
    			foreach ($data as $metadataValue) {


					foreach($metadataValue->attributes as $attribute_name => $attribute_node){
  						// * @var  DOMNode    $attribute_node
  						$this->featureCollectionMD[$attribute_node->nodeValue] = $metadataValue->nodeValue;
					}
				}

			}

		}

		return true;
	}

	/**
	 * Returns an associative array, containing metadata
	 */
	private function getMetadataFromPlacemarkNode ($node) {
	    $children = $node->childNodes;

	    $metadataArray = array();

		// search "ExtendedData" tag
		foreach ($children as $child) {
			if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "EXTENDEDDATA") {
				$extendedDataNode = $child;
				$extDataChildren = $extendedDataNode->childNodes;

				// search "Data" or "SchemaData" tag
				foreach ($extDataChildren as $extDataChild) {
					if (mb_strtoupper($this->sepNameSpace($extDataChild->nodeName)) == "SCHEMADATA") {
						$simpleDataNode = $extDataChild->firstChild;
						while ($simpleDataNode !== NULL) {
							if (mb_strtoupper($this->sepNameSpace($simpleDataNode->nodeName)) == "SIMPLEDATA") {
								$name = $simpleDataNode->getAttribute("name");
								$value = $simpleDataNode->nodeValue;
								$metadataArray[$name] = $value;
							}
							$simpleDataNode = $simpleDataNode->nextSibling;
						}
					}
					if (mb_strtoupper($this->sepNameSpace($extDataChild->nodeName)) == "DATA") {
						$dataNode = $extDataChild;
						$name = $dataNode->getAttribute("name");
						$metadataArray[$name] = $dataNode->nodeValue;
					}
				}
			}
			if(mb_strtoupper($this->sepNameSpace($child->nodeName)) == "STYLE"){
			$hrefNodes = $child->getElementsByTagName("href");
			if($hrefNodes->length > 0){
				$href = $hrefNodes->item(0)->nodeValue;
				$metadataArray["iconurl"] = $href;
			}

			}
		}
		return $metadataArray;
	}

	/**
	 * Given a "Point" node, this function returns the geometry (KMLPoint)
	 * from within the node.
	 */
	private function getGeometryFromPointNode ($node) {
		$coordinatesNode = $this->getCoordinatesNode($node);
		$geomString = $coordinatesNode->nodeValue;
		return new KMLPoint($geomString, 4326);
	}

	/**
	 * Given a "LineString" node, this function returns the geometry (KMLLine)
	 * from within the node.
	 */
	private function getGeometryFromLinestringNode ($node) {
		$coordinatesNode = $this->getCoordinatesNode($node);
		$geomString = $coordinatesNode->nodeValue;
		return new KMLLine($geomString, 4326);
	}

	/**
	 * Given a "Polygon" node, this function returns the geometry (KMLPolygon)
	 * from within the node.
	 */
	private function getGeometryFromPolygonNode ($node) {
		$polygon = null;

	    $children = $node->childNodes;

		// create new KMLPolygon
		foreach ($children as $child) {
			if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "EXTERIOR" ||
				mb_strtoupper($this->sepNameSpace($child->nodeName)) == "OUTERBOUNDARYIS") {
				// create a new Linear Ring
				$outerBoundary = $this->getGeometryFromLinearRingNode($child);
				$polygon = new KMLPolygon($outerBoundary);
			}
		}

		if ($polygon !== null) {
			// append inner boundaries to KMLPolygon
			foreach ($children as $child) {
				if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "INTERIOR" ||
					mb_strtoupper($this->sepNameSpace($child->nodeName)) == "INNERBOUNDARYIS") {
					// create a new Linear Ring
					$innerBoundary = $this->getGeometryFromLinearRingNode($child);
					$polygon->appendInnerBoundary($innerBoundary);
				}
			}
		}
		return $polygon;
	}

	/**
	 * Given a "OuterBoundaryIs" or "InnerBoundaryIs" node, this function
	 * returns the geometry (KMLLinearRing) within the child node named "linearring"
	 */
	private function getGeometryFromLinearRingNode ($node) {
	    $children = $node->childNodes;
		foreach($children as $child) {
			if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "LINEARRING") {
				$coordinatesNode = $this->getCoordinatesNode($child);
				$geomString = $coordinatesNode->nodeValue;
				return new KMLLinearRing($geomString, 4326);
			}
		}
		return null;
	}

	/**
	 * Checks if the child nodes of a given KML node contains any geometries and
	 * returns an array of geometries (KMLPoint, KMLPolygon, KMLLinestring and KMLMultigeometry)
	 */
	private function getGeometryArrayFromPlacemarkOrMultigeometryNode ($node) {
	    $geometryArray = array();

	    $children = $node->childNodes;
		foreach($children as $child) {
		    if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "POINT") {
				array_push($geometryArray, $this->getGeometryFromPointNode($child));
			}
			elseif (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "POLYGON") {
				array_push($geometryArray, $this->getGeometryFromPolygonNode($child));
			}
			elseif (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "LINESTRING") {
				array_push($geometryArray, $this->getGeometryFromLinestringNode($child));
			}
			elseif (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "MULTIGEOMETRY") {
				$geometryArray = $this->getGeometryArrayFromPlacemarkOrMultigeometryNode($child);
				$multigeometry = new KMLMultiGeometry();

				for ($i=0; $i < count($geometryArray); $i++) {
					$multigeometry->append($geometryArray[$i]);
				}
				array_push($geometryArray, $multigeometry);
			}
		}
		return $geometryArray;
	}

	/**
	 * Returns the child node with node name "coordinates" of a given KML node.
	 * If no node is found, null is returned.
	 */
	private function getCoordinatesNode ($node) {
	    $children = $node->childNodes;
		foreach($children as $child) {
			if (mb_strtoupper($this->sepNameSpace($child->nodeName)) == "POSLIST" ||
				mb_strtoupper($this->sepNameSpace($child->nodeName)) == "POS" ||
				mb_strtoupper($this->sepNameSpace($child->nodeName)) == "COORDINATES") {
				return $child;
			}
		}
		return null;
	}

	private function sepNameSpace($s){
		$c = mb_strpos($s,":");
		if($c>0){
			return mb_substr($s,$c+1);
		}
		else{
			return $s;
		}
	}
}
?>
