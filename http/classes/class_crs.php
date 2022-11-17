<?php
# http://www.mapbender2.org/index.php/class_crs.php
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
require_once(dirname(__FILE__)."/../../conf/altered_axis_epsg.php");
require_once(dirname(__FILE__)."/class_connector.php");
require_once(dirname(__FILE__)."/class_cache.php");

/**
 * A class to handle information about different CoordinateReferenceSystems. The class tries to call the epsg registry an pulls some
 * information from it. The handling of CRS axis in different ows is not really homogeneous. 
 * Further information:
 * https://themes.jrc.ec.europa.eu/discussion/view/109106/fyi-interpreted-coordinate-order-flipped-in-gml-files-with-uri-format-srsname
 * http://postgis.net/2013/08/18/tip_lon_lat/
 * https://github.com/deegree/deegree3/wiki/Axis-order-handling
 * http://docs.geotools.org/latest/userguide/library/referencing/order.html
 * http://mapserver.org/ogc/wms_server.html
 * http://www.ogcnetwork.net/axisorder
 */

class Crs {
	var $identifierType; //'epsg', 'urn', 'url', 'other'
	var $identifierCode;
	var $identifier;
	var $name;
	var $gmlRepresentation;
	var $epsgType; // false, 'geographic 2D', 'projected', '...'
	var $axisOrder; //different types:'east,north' - ('lon,lat') or 'north,east' - ('lat,lon')
	//preasume, that the old order is always lon/lat for geo and east/north for projected systems
	var $resolveSuccess;
	var $resolveOrigin;
	var $resolveErrorMessage;

	public function __construct ($identifier) {
		$this->resolveSuccess = false;
		$this->resolveErrorMessage = false;
		$this->extractIdentifierType($identifier);
		
		if ($this->resolveCrsInfo() == true) {
			$this->resolveSuccess = true;
		}
	}
	/*
	 * https://www.binarytides.com/php-check-running-cli/
	 */
	public function is_cli() {
	    $e = new mb_notice("classes/class_crs.php: php_api_name() : " . php_sapi_name());
	    if (php_sapi_name() == 'cli') {
	        return true;
	    } else {
	        return false;
	    }
	    //$e = new mb_exception("classes/class_crs.php: " . php_sapi_name());
	    //return (!isset($_SERVER[‘SERVER_SOFTWARE’]) && (php_sapi_name() == ‘cli’ || (is_numeric($_SERVER[‘argc’]) && $_SERVER[‘argc’] > 0)));
	}
	
/**
 * A public function whith a parameter for {owstype}_{version}. If the ows needs a swap of the crs axis order true is returned,
 * otherwise - if the axis are handled as they are defined in the epsg registry - the function returns false
 */
	//handles ows identifier: wms_1.0.0, wms_1.1.1, wms_1.3.0, wfs_1.0.0, wfs_1.1.0, wfs_2.0.0, wfs_2.0.2
	public function alterAxisOrder ($targetOws) {
		$owsWithSpecialOrder = array("wms_1.0.0","wms_1.1.1","wfs_1.0.0","gml_2.0.0","gml_2.1.0","gml_3.1.1","geojson");//lon/lat,east/north 
		$owsWithOrderAsDefined = array("wms_1.3.0","wfs_1.1.0","wfs_2.0.0","wfs_2.0.2","gml_3.2.0");
		//$owsWithOrderAsDefined = array("wms_1.3.0","wfs_2.0.0","wfs_2.0.2");
		$order = "east,north"; //dummy postgis/oracle spatial order
		$e = new mb_notice("classes/class_crs.php: extracted axis order from epsg: " . $this->axisOrder . " target_ows: " . $targetOws);
		if (in_array($targetOws, $owsWithSpecialOrder) && $this->axisOrder !== $order) {
		//if (in_array($targetOws, $owsWithSpecialOrder) && $this->identifierType == 'epsg' && $this->axisOrder !== $order) {
			//return false; //cause it is hardcoded in the specs to user lon/lat as this is so in postgis
			return false;
		} else {
			if (in_array($targetOws, $owsWithOrderAsDefined) && $this->axisOrder !== $order) {
				return true;
			}
			return false;
		}
	}

	private function extractIdentifierType ($identifier) {
		//$e = new mb_exception("http/classes/class_crs.php - extract identifier: *".$identifier."*");
		//check for type
		if (substr(strtoupper($identifier), 0, 5) === "EPSG:") {
			//$e = new mb_exception("http/classes/class_crs.php - found EPSG: identifier!");
			$this->identifier = $identifier;
			$this->identifierType = 'epsg';
			$this->identifierCode = explode(':',$identifier)[1];
			//$e = new mb_exception("http/classes/class_crs.php - found code: *".$this->identifierCode."*");
			return;
		} else {
			//check for urn based version - example: urn:ogc:def:crs:EPSG:
			if (substr(strtoupper($identifier), 0, 21) === "URN:OGC:DEF:CRS:EPSG:") {
				//delete this part from original identifier
				$identifierNew = str_replace('URN:OGC:DEF:CRS:EPSG:','',strtoupper($identifier));			
				$this->identifier = $identifier;
				//$e = new mb_exception("http/classes/class_crs.php - urn identifier new: ".$identifierNew);
				$this->identifierType = 'urn';
				if (substr($identifierNew, 0, 1 ) === ":") {
				    $this->identifierCode = ltrim($identifierNew, ':');
				} else {
				    if (strpos($identifierNew, ":") !== false) {
				    	$this->identifierCode = explode(':',$identifierNew)[1];
				    } else {
						$this->identifierCode = $identifierNew;
				    }
				    //$e = new mb_exception("http/classes/class_crs.php - code: ".$this->identifierCode);
				}	
				//$e = new mb_exception("http/classes/class_crs.php - urn identifier code: *".$this->identifierCode."*");
				return;
			} else {
				//case urn:x-ogc:def:crs:EPSG:25832?? - geoserver
                		if (substr(strtoupper($identifier), 0, 23) === "URN:X-OGC:DEF:CRS:EPSG:") {
					//delete this part from original identifier
					$identifierNew = str_replace('URN:X-OGC:DEF:CRS:EPSG:','',strtoupper($identifier));	
					$this->identifier = $identifier;
					//TODO - use url encoding for resolving registry ;-) maybe change this
					$this->identifierType = 'url';
					$this->identifierCode = $identifierNew;	
					return; 
				} else {
					if (substr($identifier, 0, 31) === 'http://www.opengis.net/def/crs/') {
						$identifierNew = str_replace('http://www.opengis.net/def/crs/','',$identifier);
						//remaining string: ({OGC|EPSG}/{0}/{code})
						$this->identifier = $identifier;
						$this->identifierType = 'url';
						$this->identifierCode = explode('/',$identifierNew)[2];
						return;
					} else {
						if (substr($identifier, 0, 40) === 'http://www.opengis.net/gml/srs/epsg.xml#') {
						    $identifierNew = str_replace('http://www.opengis.net/gml/srs/epsg.xml#','',$identifier);
						    //remaining string: ({code})
						    $this->identifier = $identifier;
						    $this->identifierType = 'urlxml';
						    $this->identifierCode = $identifierNew;
						    return;
					        } else {
						    $this->identifier = $identifier;
						    $this->identifierType = 'other';
						    $this->identifierCode = $identifier;
						    return;
						}
					}
				}
			}
		}
	}

	private function resolveCrsInfo () {
		if (!preg_match("/^\d+$/", $this->identifierCode)) {
                    $e = new mb_exception("classes/class_crs.php: identifierCode is not an integer: ".$this->identifierCode);
                    return false;
		}
		if ($this->is_cli()) {
		    $e = new mb_notice("http/classes/class_crs.php - invoked from cli!");
		    // try to read from filesystem cache
		    $admin = new administration();
		    $filename = "/tmp" . "/crsCache_" . md5($this->identifier) . ".cache"; 
		    $cachedObject = $admin->getFromStorage($filename, 'file');
		    if ($cachedObject != false) {
		        $cachedObject = json_decode($cachedObject);
		        $this->gmlRepresentation = $cachedObject->gmlRepresentation;
		        $this->epsgType = $cachedObject->epsgType;
		        $this->axisOrder = $cachedObject->axisOrder;
		        $this->name = $cachedObject->name;
		        $e = new mb_notice("http/classes/class_crs.php - read crs info from cache!");
		        return true;
		    } else {
		        $e = new mb_exception("http/classes/class_crs.php - no filesystem cache found - try to resolve crs info remote!");
		    }
		} else {
		    $e = new mb_notice("http/classes/class_crs.php - invoked from http!");
    		$cache = new Cache();
    		//try to read from cache if already exists
    		if ($cache->isActive && $cache->cachedVariableExists("mapbender:crs:" . md5($this->identifier))) {
    		    $cachedObject = json_decode($cache->cachedVariableFetch("mapbender:crs:" . md5($this->identifier)));
    			$this->gmlRepresentation = $cachedObject->gmlRepresentation;	
    			$this->epsgType = $cachedObject->epsgType;
    			$this->axisOrder = $cachedObject->axisOrder;
    			$this->name = $cachedObject->name;		
    			$e = new mb_notice("http/classes/class_crs.php - read crs info from cache!");
    			return true;
    		} else {
    		    $e = new mb_exception("http/classes/class_crs.php - no variable cache found - try to resolve crs info remote!");
    		}
		}
		//$e = new mb_exception("http/classes/class_crs.php - type: ".$this->identifierType." - code: ".$this->identifierCode);
		//built urls to get information from registries
		switch ($this->identifierType) {
			case "epsg1":
				$registryBaseUrl = "http://www.epsg-registry.org/export.htm?gml=";
				$registryUrl = $registryBaseUrl.urlencode("urn:ogc:def:crs:EPSG::".$this->identifierCode);
				break;
			case "urn1":
				$registryBaseUrl = "http://www.epsg-registry.org/export.htm?gml=";
				$registryUrl = $registryBaseUrl.urlencode($this->identifier);
				/*$xpathCrsType = "";
				$xpathAxis = "";*/
				break;
			case "url1":
				$registryBaseUrl = "http://www.epsg-registry.org/export.htm?gml=";
				$registryUrl = $registryBaseUrl.urlencode("urn:ogc:def:crs:EPSG::".$this->identifierCode);
				/*$xpathCrsType = "";
				$xpathAxis = "";*/
				break;
			default:
				$registryBaseUrl = "https://apps.epsg.org/api/v1/CoordRefSystem/{CRS_IDENTIFIER}/export/?format=gml";
				$registryUrl = str_replace("{CRS_IDENTIFIER}", $this->identifierCode,$registryBaseUrl);
				break;
		}
		//$e = new mb_exception("registry url: ".$registryUrl);
		$crsConnector = new connector();
		$crsConnector->set("timeOut", "2");
		//New Oct 2020
		$crsConnector->set("externalHeaders", array("Accept: application/xml"));
		$crsConnector->load($registryUrl);
		if ($crsConnector->timedOut == true) {
			return false;
		}
		$this->gmlRepresentation = $crsConnector->file;
		//generate separate jsonObject
		$jsonCrsInfo->gmlRepresentation = $this->gmlRepresentation;
		//parse relevant information
		libxml_use_internal_errors(true);
		try {
			$crsXml = simplexml_load_string($this->gmlRepresentation);
			if ($crsXml === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_crs:".$error->message);
    				}
				throw new Exception("class_crs:".'Cannot parse crs gml!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_crs:".$e->getMessage());
			return false;
		}
		//if parsing was successful
		if ($crsXml !== false) {
			//$crsXml->registerXPathNamespace("epsg", "urn:x-ogp:spec:schema-xsd:EPSG:1.0:dataset");
			$crsXml->registerXPathNamespace("epsg", "urn:x-ogp:spec:schema-xsd:EPSG:2.2:dataset");
			$crsXml->registerXPathNamespace("gml", "http://www.opengis.net/gml/3.2");
			$crsXml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
			//switch for type of cs - distinguish between ellipsoidalCS and CartesianCS (begin with lowercase character in crs document)
			$this->epsgType = $crsXml->xpath('//gml:metaDataProperty/epsg:CommonMetaData/epsg:type');
			$this->epsgType = $this->epsgType[0];
			//$e = new mb_exception("http/classes/class_crs.php - epsgType: ".$this->epsgType);
			$this->name = $crsXml->xpath('//gml:name');
			$this->name = $this->name[0];
			//$e = new mb_exception("http/classes/class_crs.php - name of crs: ".$this->name);
			$jsonCrsInfo->name = (string)$this->name;
			$jsonCrsInfo->epsgType = (string)$this->epsgType;
			//echo $this->name;
			//echo $this->epsgType;
			//get attribute for specific system
			switch ($this->epsgType) {
				case "projected":
					$xpathIdentifierCs = "//gml:cartesianCS/@xlink:href";
					break;
				case "geographic 2D":
					$xpathIdentifierCs = "//gml:ellipsoidalCS/@xlink:href";
					break;
				case "geographic 2d":
					$xpathIdentifierCs = "//gml:ellipsoidalCS/@xlink:href";
					break;
			} 
			$csIdentifier = $crsXml->xpath($xpathIdentifierCs);
			$csIdentifier = $csIdentifier[0];
			//get axis order from further xml document
			//$urlToCsDefinition = $registryBaseUrl.urlencode($csIdentifier);
			//New Oct 2020
			$urlToCsDefinition = $csIdentifier;
			$csConnector = new connector($urlToCsDefinition);
			$crsConnector->set("externalHeaders", array("Accept: application/xml"));
			$csConnector->set("timeOut", "2");
			if ($csConnector->timedOut == true) {
				return false;
			}
			$csGmlRepresentation = $csConnector->file;
			try {
				$csXml = simplexml_load_string($csGmlRepresentation);
				if ($csXml === false) {
					foreach(libxml_get_errors() as $error) {
        					$err = new mb_exception("class_crs:".$error->message);
    					}
					throw new Exception("class_crs: Cannot parse cs gml!");
					return false;
				}
			}
			catch (Exception $e) {
    				$err = new mb_exception("class_cs:".$e->getMessage());
				return false;
			}		
			if ($csXml !== false) {
				//$csXml->registerXPathNamespace("epsg", "urn:x-ogp:spec:schema-xsd:EPSG:1.0:dataset");
				$csXml->registerXPathNamespace("epsg", "urn:x-ogp:spec:schema-xsd:EPSG:2.2:dataset");
				$csXml->registerXPathNamespace("gml", "http://www.opengis.net/gml/3.2");
				$csXml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
				//switch for type of cs - distinguish between ellipsoidalCS and CartesianCS (begin with lowercase character in crs document)
				$axis = $csXml->xpath('//gml:axis/gml:CoordinateSystemAxis/gml:axisDirection');
				//TODO - think about it? switch for crs lookup table of crs which axis order was swapped from earlier ogc standards to newer - identify them by the usage og EPSG:XXXX notation - no good idea 
				/*if (DEFINED("OLD_EPSG_AXIS_ORDER_ALTERED") && OLD_EPSG_AXIS_ORDER_ALTERED !== "") {
					$old_epsg_axis_order_altered = explode(",", OLD_EPSG_AXIS_ORDER_ALTERED);
				} else {
					$old_epsg_axis_order_altered = array();
				}
				if ($this->identifierType == 'epsg' && in_array($this->identifierCode, $old_epsg_axis_order_altered)) {
					$this->axisOrder = $axis[1].",".$axis[0];
				} else {
					$this->axisOrder = $axis[0].",".$axis[1];
				}*/
				$this->axisOrder = $axis[0].",".$axis[1];
				$jsonCrsInfo->axisOrder = $this->axisOrder;
			}
		}
		//store information - maybe to cache, if it does not already exists!
		if ($this->is_cli()) {
		    $filename = "/tmp" . "/crsCache_" . md5($this->identifier) . ".cache";
		    $e = new mb_notice("http/classes/class_crs.php - search for file: " . $filename);
		    if (!file_exists($filename)) {
    		    $cachedObject = $admin->putToStorage($filename, json_encode($jsonCrsInfo), 'file', 86400);
    		    $e = new mb_notice("http/classes/class_crs.php - store crs info to file cache!");
		    } else {
		        $e = new mb_notice("http/classes/class_crs.php - crs file already exists!");
		    }
		} else {
		    if ($cache->isActive && $cache->cachedVariableExists("mapbender:crs:" . md5($this->identifier)) == false) {
		        $cache->cachedVariableAdd("mapbender:crs:" . md5($this->identifier), json_encode($jsonCrsInfo), 86400);
		        $e = new mb_notice("http/classes/class_crs.php - store crs info to cache!");
		        return true;
		    }
		}
		
		return true;
	}
}

?>
