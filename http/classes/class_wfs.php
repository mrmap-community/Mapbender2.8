<?php
# $Id: class_wfs.php 10240 2019-09-12 08:52:40Z armin11 $
# http://www.mapbender.org/index.php/class_wfs.php
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
require_once(dirname(__FILE__)."/class_connector.php");
require_once(dirname(__FILE__)."/class_administration.php");
require_once(dirname(__FILE__)."/class_gml.php");
require_once(dirname(__FILE__)."/class_ows.php");
require_once(dirname(__FILE__)."/class_wfsToDb.php");
require_once(dirname(__FILE__)."/class_wfs_configuration.php");
require_once(dirname(__FILE__)."/class_crs.php");

/**
 * An abstract Web Feature Service (WFS) class, modelling for example
 * WFS 1.0.0, WFS 1.1.0, WFS 2.0.0, WFS 2.0.2
 * To instantiate a wfs object, use universal_wfs_factory class instead!
 */
abstract class Wfs extends Ows {
	var $describeFeatureType;
	var $describeFeatureTypeNamespace;
	var $getFeature;
	var $transaction;
	var $overwrite = true;
	var $wfsOutputFormatArray = array();
	var $featureTypeArray = array();
	var $operationsArray = array();
	var $storedQueriesArray = array();
	
	/**
	 * Returns the version of this WFS. Has to be implemented by the subclass.
	 * @return String the version, for example "1.0.0"
	 */
	public function getVersion () {
		return "";
	}
	
	public function addFeatureType ($aFeatureType) {
		array_push($this->featureTypeArray, $aFeatureType);
	}
	
	public function findFeatureTypeByName ($name) {
		foreach ($this->featureTypeArray as $ft) {
			if ($ft->name == $name) {
				return $ft;
			}
		}
		new mb_exception("This WFS doesn't have a featuretype with name " . $name);
		return null;
	}
	
	public function findFeatureTypeById ($id) {
		foreach ($this->featureTypeArray as $ft) {
			if ($ft->id == $id) {
				return $ft;
			}
		}
		new mb_exception("This WFS doesn't have a featuretype with ID " . $id);
		return null;
	}

	public function &findFeatureTypeReferenceById ($id) { //used by metadata editor
		foreach ($this->featureTypeArray as $ft) {
			if ($ft->id == $id) {
				return $ft;
			}
		}
		new mb_exception("This WFS doesn't have a featuretype with ID " . $id);
		return null;
	}

	public static function getWfsIdByFeaturetypeId ($id) {
		$sql = "SELECT DISTINCT fkey_wfs_id FROM wfs_featuretype WHERE featuretype_id = $1";
		$res = db_prep_query($sql, array($id), array("i"));
		$row = db_fetch_assoc($res);
		if ($row) {
			return $row["fkey_wfs_id"];
		}
		return null;
	}

	public static function findGeomColumnNameByFeaturetypeId ($ftId) {
		$geomTypesArray = array('GeometryPropertyType','PointPropertyType','LineStringPropertyType','PolygonPropertyType','MultiPointPropertyType','MultiLineStringPropertyType','MultiPolygonPropertyType','SurfacePropertyType','MultiSurfacePropertyType');
		$sql = "SELECT element_name, element_type FROM wfs_element WHERE fkey_featuretype_id = $1";
		$res = db_prep_query($sql, array($ftId), array("i"));
		//simple uses first supported geom type!
		while($row = db_fetch_array($res)){
			if (in_array($row['element_type'], $geomTypesArray)) {
				return $row['element_name'];
			}
		}
		return null;
	}
	
	public function getElementInfoByIds ($ftId, $ftElementIds) {
	    $v = array();
	    $t = array();
	    $sql = "select element_name, featuretype_name, fkey_wfs_id from wfs_element inner join wfs_featuretype on wfs_element.fkey_featuretype_id = wfs_featuretype.featuretype_id where element_id IN (";
	    for($i=0; $i<count($ftElementIds); $i++){
	        if($i>0){ $sql .= ",";}
	        $sql .= "$".strval($i+1);
	        array_push($v, $ftElementIds[$i]);
	        array_push($t, "i");
	        $j = $i;
	    }
	    $sql .= ") and featuretype_id = $" . (string)($j + 2);
	    array_push($v, $ftId);
	    array_push($t, "i");
	    $e = new mb_exception("classes/class_wfs.php: getElementInfoByIds sql: " . $sql); 
	    $res = db_prep_query($sql, $v, $t);
	    
	    $element_names = array();
	    $i = 0;
	    
	    while($row = db_fetch_array($res)){
	        $element_names[$i] = $row['element_name'];
	        $elementName = $row['element_name'];
	        $featuretypeName = $row['featuretype_name'];
	        $wfsDbId = $row['fkey_wfs_id'];
	        $i++;
	    }
	    if ($i !== 0) {
	        $wfsId = $this->id;
	        if ($wfsDbId != $wfsId) {
	            $e = new mb_exception("classes/class_wfs.php: getElementInfoByIds - no expected element found in this wfs!");
	            return false;
	        }
	        if (strpos($featuretypeName, ":") !== false) {
	            $featuretypeArray = explode(":", $featuretypeName);
	            $namespace = $featuretypeArray[0];
	            //get namespace url from
	            $sql = "select namespace_location from wfs_featuretype_namespace where namespace = $1 and fkey_featuretype_id = $2";
	            $res = db_prep_query($sql, array($namespace, $ftId), array("s", "i"));
	            $row = db_fetch_assoc($res);
	            if ($row) {
	                $namespaceLocation = $row["namespace_location"];
	            } else {
	                $namespaceLocation = false;
	            }
	        } else {
	            $namespace = false;
	        }
	        //build return object
	        $returnObject = new stdClass();
	        $returnObject->element_names = $element_names;
	        $returnObject->featuretype_name = $featuretypeName;
	        $returnObject->namespace = $namespace;
	        $returnObject->namespace_location = $namespaceLocation;
	        return $returnObject;
	    } else {
	       return false;
	    }
	}
	
	protected function getFeatureGet ($featureTypeName, $filter, $maxFeatures=null, $version="2.0.0") {
		switch ($this->getVersion()) {
			case "2.0.2":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			case "2.0.0":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			default:
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				break;
		}
		$url = $this->getFeature .
				$this->getConjunctionCharacter($this->getFeature) . 
				"service=WFS&request=getFeature&version=" . 
				$this->getVersion() . "&".strtolower($typeNameParameterName)."=" . $featureTypeName;
		if ($maxFeatures != null) {
			$url .= "&".$maxFeaturesParameterName."=".$maxFeatures;
		}
		if ($filter != null) {
				$url .= "&filter=" . urlencode($filter);
		}
                $e = new mb_notice("class_wfs.php: getFeatureGet: ".$url);
		return $this->get($url); //from class_ows!
	}
	
	protected function getFeaturePost ($featureTypeName, $filter, $destSrs, $storedQueryId, $storedQueryParams, $maxFeatures) {
		switch ($this->getVersion()) {
			case "2.0.2":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			case "2.0.0":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			default:
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				break;
		}
		if($storedQueryId && $storedQueryId != "") {
			$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
					"<wfs:GetFeature service=\"WFS\" version=\"" . $this->getVersion() . "\" " .
					"xmlns:wfs=\"http://www.opengis.net/wfs/2.0\" " .
					//"xmlns:xlink=\"http://www.w3.org/1999/xlink\" " .
					//"xmlns:ogc=\"http://www.opengis.net/ogc\" ".
					//"xmlns:ows=\"http://www.opengis.net/ows/1.1\" " .
					"xmlns:gml=\"http://www.opengis.net/gml/3.2\" " .
					"xmlns:fes=\"http://www.opengis.net/fes/2.0\" " .
					"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
					"xsi:schemaLocation=\"http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd\">";
			$postData .= "<wfs:StoredQuery id=\"" . $storedQueryId . "\">";
			$postData .= $storedQueryParams;
			$postData .= "</wfs:StoredQuery>";
		}
		else {
			if($this->getVersion() == "2.0.0") {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
					"<wfs:GetFeature service=\"WFS\" version=\"" . $this->getVersion() . "\" " .
					"xmlns:wfs=\"http://www.opengis.net/wfs/2.0\" ".
					"xmlns:fes=\"http://www.opengis.net/fes/2.0\" ".
					"xmlns:gml=\"http://www.opengis.net/gml/3.2\" ".
					"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
					"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
					"xsi:schemaLocation=\"http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd\">";
			}
			else if($this->getVersion() == "1.1.0") {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
						"<wfs:GetFeature service=\"WFS\" version=\"" . $this->getVersion() . "\" " .
						"xmlns:wfs=\"http://www.opengis.net/wfs\" " .
						"xmlns:gml=\"http://www.opengis.net/gml\" " .
						"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
						"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
						"xsi:schemaLocation=\"http://www.opengis.net/wfs ../wfs/1.1.0/WFS.xsd\">";
			}
			else {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
						"<wfs:GetFeature service=\"WFS\" version=\"" . $this->getVersion() . "\" " .
						"xmlns:wfs=\"http://www.opengis.net/wfs\" " .
						"xmlns:gml=\"http://www.opengis.net/gml\" " .
						"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
						"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
						"xsi:schemaLocation=\"http://www.opengis.net/wfs ../wfs/1.0.0/WFS-basic.xsd\">";
			}
			$postData .="<wfs:Query ";
			if($destSrs) {
				$postData .= "srsName=\"" . $destSrs . "\" ";
			}
			// add namespace
			if (strpos($featureTypeName, ":") !== false) {
				$ft = $this->findFeatureTypeByName($featureTypeName);
				$ns = $this->getNamespace($featureTypeName);
				$url = $ft->getNamespace($ns);
				$postData .= "xmlns:" . $ns . "=\"" . $url . "\" ";
					
			}
			if($this->getVersion() == "2.0.0" || $this->getVersion() == "2.0.2") {
				//change filter to fes syntax
				$filter = str_replace("<ogc", "<fes", $filter);
				$filter = str_replace("/ogc", "/fes", $filter);
			        $filter = str_replace("PropertyName", "ValueReference", $filter);
			}
			$postData .= $typeNameParameterName."=\"" . $featureTypeName . "\">" .
					$filter .
					"</wfs:Query>";
		}
		$postData .= "</wfs:GetFeature>";	
        $e = new mb_notice("class_wfs.php: getFeaturePost: ".$postData);
	    if ($filter == null) {
			if ($maxFeatures != null) {
				$e = new mb_notice("maxfeatures: ".$maxFeatures);
				return $this->getFeatureGet($featureTypeName, null, $maxFeatures);
			} else {
				$e = new mb_notice("maxfeatures: ".$maxFeatures);
				return $this->getFeatureGet($featureTypeName, null, null);
			}
		} else {
			if (is_int($maxFeatures)) {
				return $this->post($this->getFeature."&".$maxFeaturesParameterName."=".$maxFeatures, $postData); //from class_ows!
			} else {
				return $this->post($this->getFeature, $postData); //from class_ows!
			}
		}
	}

	public function getFeature ($featureTypeName, $filter, $destSrs=null, $storedQueryId=null, $storedQueryParams=null,  $maxFeatures=null) {

// FOR NOW, WE ONLY DO POST REQUESTS!
// THE FILTERS ARE CONSTRUCTED ON THE CLIENT SIDE,
// SO CHANGING THEM HERE ACCORDING TO GET/POST
// WOULD BE TOO MUCH FOR NOW
//
//		if (strpos($this->getFeature, "?") === false) {
			return $this->getFeaturePost($featureTypeName, $filter, $destSrs, $storedQueryId, $storedQueryParams, $maxFeatures);
//		}
//		return $this->getFeatureGet($featureTypeName, $filter);
	}

	public function bbox2spatialFilter($bbox, $geometryColumn, $srs="EPSG:4326", $version=false){
		//define spatial filter from GET (https://github.com/opengeospatial/WFS_FES) bbox=160.6,-55.95,-170,-25.89 - geojson long/lat - east/north!	
		/*
			
		*/
		$bboxArray = explode(',', $bbox);
		$bboxArrayNew = array();
		foreach ($bboxArray as $coord) {
			if (strpos($coord,'.') !== false) {
				$coordArray = explode('.',$coord);
				$countDigits = (16 - strlen($coordArray[1]));
				//$e = new mb_exception("coord array[1]: ".$coordArray[1]." - digits to add: ".$countDigits);
				//add zeros to coord
				$coord .= str_repeat("0", $countDigits);
			} else {
				$coord .= ".0000000000000000";
			}
			//alter last digits to 42
			$coord = substr($coord, 0, -2).'42';
			$bboxArrayNew[] = $coord;
//$e = new mb_exception("classes/class_wfs.php: ccord: ".$coord." float: ".round(floatval($coord),10));
		}
		$bboxArray = $bboxArrayNew;
		$crs = new Crs($srs);
		$alterAxisOrder = $crs->alterAxisOrder("wfs_".$version);
		if ($alterAxisOrder == true) {
			//switch from geojson to lat/long - north/east
			$bboxArrayNew[0] = $bboxArray[1];
			$bboxArrayNew[1] = $bboxArray[0];
			$bboxArrayNew[2] = $bboxArray[3];
			$bboxArrayNew[3] = $bboxArray[2];
			$bboxArray = $bboxArrayNew;
		}
		$srsId = $crs->identifierCode;
		//add srs to request
		switch ($version) {
			case "2.0.2":
				$targetSrs = "http://www.opengis.net/def/crs/EPSG/0/".$srsId;
				break;
			case "2.0.0":
				$targetSrs = "urn:ogc:def:crs:EPSG::".$srsId;	
				//$targetSrs = "EPSG:".$srsId; - for mapserver?
				break;
			case "1.1.0":
				$targetSrs = "urn:ogc:def:crs:EPSG::".$srsId;
				break;
			case "1.0.0":
				$targetSrs = "EPSG:".$srsId;
				break;
		}
		if ($version == false) {
			$version = $this->getVersion();
		} else {
			$e = new mb_notice("classes/class_wfs.php: wfs version for generating spatial filter forced to ".$version."!");
		}
		$crs = new Crs($srs);
		//geosjon have always long/lat 
		$srsId = $crs->identifierCode;
		$spatialFilter = "";
		switch($version) {
			case "2.0.0":
				//gml2 depends on the namespace in fes:Filter - gml2 and gml3 are possible - we use gml3!
/*
	<fes:Filter>
            <fes:BBOX>
               <fes:ValueReference>/RS1/geometry</fes:ValueReference>
               <gml:Envelope srsName="urn:ogc:def:crs:EPSG::1234">
                  <gml:lowerCorner>10 10</gml:lowerCorner>
                  <gml:upperCorner>20 20</gml:upperCorner>
               </gml:Envelope>
            </fes:BBOX>
	</fes:Filter>
*/
/*
$bboxFilter = '<fes:Filter xmlns:fes="http://www.opengis.net/fes/2.0"><fes:BBOX>';
							$bboxFilter .= '<fes:ValueReference>'.$geometryFieldName.'</fes:ValueReference>';
							//<gml:Envelope srsName="urn:ogc:def:crs:EPSG::1234">
							$bboxFilter .= '<gml:Envelope xmlns:gml="http://www.opengis.net/gml/3.2" srsName="'.$crs.'">';
							//FIX for ESRI? TODO
							$bboxFilter .= '<gml:lowerCorner>'.$currentBboxGetFeature[0].' '.$currentBboxGetFeature[1].'</gml:lowerCorner>';
							$bboxFilter .= '<gml:upperCorner>'.$currentBboxGetFeature[2].' '.$currentBboxGetFeature[3].'</gml:upperCorner>';
              						$bboxFilter .= '</gml:Envelope>';
		            				$bboxFilter .= '</fes:BBOX>';
							$bboxFilter .= '</fes:Filter>';
							$bboxFilter = rawurlencode(utf8_decode($bboxFilter));
*/

				//$spatialFilter .= "<fes:Filter xmlns:fes=\"http://www.opengis.net/fes/2.0\">";
				$spatialFilter .= "<fes:BBOX>";
				$spatialFilter .= "<fes:ValueReference>".$geometryColumn."</fes:ValueReference>";
    				$spatialFilter .= '<gml:Envelope xmlns:gml="http://www.opengis.net/gml/3.2" srsName="'.$targetSrs.'">';                
				$spatialFilter .= '<gml:lowerCorner>'.$bboxArray[0]." ".$bboxArray[1].'</gml:lowerCorner>';  
                  		$spatialFilter .= '<gml:upperCorner>'.$bboxArray[2]." ".$bboxArray[3].'</gml:upperCorner>';  
				$spatialFilter .= '</gml:Envelope>';
				$spatialFilter .= "</fes:BBOX>";
				//$spatialFilter .= "</fes:Filter>";
				
				break;
			case "other_test":
				//gml2
				//$spatialFilter .= "<ogc:Filter>";
				$spatialFilter .= "<ogc:BBOX>";
				$spatialFilter .= "<ogc:PropertyName>".$geometryColumn."</ogc:PropertyName>";
    				$spatialFilter .= '<gml:Box xmlns:gml="http://www.opengis.net/gml" srsName="'.$targetSrs.'">';
				$spatialFilter .= '<gml:coordinates decimal="." cs="," ts=" ">';
				$spatialFilter .= $bboxArray[0].','.$bboxArray[1].' '.$bboxArray[2].','.$bboxArray[3];
				$spatialFilter .= '</gml:coordinates></gml:Box>';
				$spatialFilter .= "</ogc:BBOX>";
				//$spatialFilter .= "</ogc:Filter>";
				break;
			case "1.1.0":
				//gml2
				$spatialFilter .= "<ogc:BBOX>";
				$spatialFilter .= "<ogc:PropertyName>".$geometryColumn."</ogc:PropertyName>";
    				$spatialFilter .= '<gml:Box xmlns:gml="http://www.opengis.net/gml" srsName="'.$targetSrs.'">';
				$spatialFilter .= '<gml:coordinates decimal="." cs="," ts=" ">';
				$spatialFilter .= $bboxArray[0].','.$bboxArray[1].' '.$bboxArray[2].','.$bboxArray[3];
				$spatialFilter .= '</gml:coordinates></gml:Box>';
				$spatialFilter .= "</ogc:BBOX>";
				break;
		}
		if ($spatialFilter == ""){
			$spatialFilter = false;
		}	
		//$e = new mb_exception("classes/class_wfs.php: spatialFilter: ".$spatialFilter);			
		return $spatialFilter;
	}

	public function getFeaturePaging ($featureTypeName, $filter, $destSrs, $storedQueryId, $storedQueryParams, $maxFeatures, $startIndex, $version=false, $outputFormat=false, $method="POST") {
		if ($version == false) {
			$version = $this->getVersion();
		} else {
			$e = new mb_notice("classes/class_wfs.php: wfs version forced to ".$version."!");
		}
		if ($destSrs != false) {
			//check crs representation
			$crs = new Crs($destSrs);
			$alterAxisOrder = $crs->alterAxisOrder("wfs_".$version);
			$srsId = $crs->identifierCode;
			//add srs to request
		}
		//maxfeatures - in newer versions count is used - for paging
		switch ($version) {
			case "2.0.2":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				$srsName = "http://www.opengis.net/def/crs/EPSG/0/".$srsId;
				break;
			case "2.0.0":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				$srsName = "urn:ogc:def:crs:EPSG::".$srsId; //mapserver error when requesting this?
				//$srsName = "EPSG:".$srsId;
				break;
			case "1.1.0":
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				$srsName = "urn:ogc:def:crs:EPSG::".$srsId;
				break;
			default:
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				$srsName = "EPSG:".$srsId;
				break;
		}
		//$e = new mb_exception($filter);
		if($storedQueryId && $storedQueryId != "") {
		    $postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
				"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
				"xmlns:wfs=\"http://www.opengis.net/wfs/2.0\" " .
				//"xmlns:xlink=\"http://www.w3.org/1999/xlink\" " .
				//"xmlns:ogc=\"http://www.opengis.net/ogc\" ".
				//"xmlns:ows=\"http://www.opengis.net/ows/1.1\" " .
				"xmlns:gml=\"http://www.opengis.net/gml/3.2\" " .
				"xmlns:fes=\"http://www.opengis.net/fes/2.0\" " .
				"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
				"xsi:schemaLocation=\"http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd\" ";
		    if (isset($outputFormat) && $outputFormat != '') {
			$postData .= "outputFormat=\"".$outputFormat."\">";
		    } else {
			$postData .= ">";
		    }
		    $postData .= "<wfs:StoredQuery id=\"" . $storedQueryId . "\">";
		    $postData .= $storedQueryParams;
		    $postData .= "</wfs:StoredQuery>";
		} else {
		    switch ($method) {
			case "POST":
			    if($version == "2.0.0" || $version == "2.0.2") {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
					"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
					"xmlns:wfs=\"http://www.opengis.net/wfs/2.0\" ".
					"xmlns:fes=\"http://www.opengis.net/fes/2.0\" ".
					"xmlns:gml=\"http://www.opengis.net/gml/3.2\" ".
					"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
					"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
					"xsi:schemaLocation=\"http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd\" ".
					"startIndex=\"".$startIndex."\" count=\"".$maxFeatures."\" ";
				if (isset($outputFormat) && $outputFormat != '') {
				    $postData .= "outputFormat=\"".$outputFormat."\">";
				} else {
				    $postData .= ">";
				}
				//TODO: not already implemented - maybe usefull for older geoserver/mapserver implementations
			    } else if($version == "1.1.0") {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
						"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
						"xmlns:wfs=\"http://www.opengis.net/wfs\" " .
						"xmlns:gml=\"http://www.opengis.net/gml\" " .
						"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
						"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
						"xsi:schemaLocation=\"http://www.opengis.net/wfs ../wfs/1.1.0/WFS.xsd\" ";
				if (isset($outputFormat) && $outputFormat != '') {
				    $postData .= "outputFormat=\"".$outputFormat."\">";
				} else {
				    $postData .= ">";
				}
			    } else {
				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
						"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
						"xmlns:wfs=\"http://www.opengis.net/wfs\" " .
						"xmlns:gml=\"http://www.opengis.net/gml\" " .
						"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
						"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
						"xsi:schemaLocation=\"http://www.opengis.net/wfs ../wfs/1.0.0/WFS-basic.xsd\" ";
				if (isset($outputFormat) && $outputFormat != '') {
				    $postData .= "outputFormat=\"".$outputFormat."\">";
				} else {
				    $postData .= ">";
				}
			    }
			    $postData .="<wfs:Query ";
			    if($destSrs) {
				$postData .= "srsName=\"" . $srsName . "\" ";
			    }
			    //add namespace
			    if (strpos($featureTypeName, ":") !== false) {
				$ft = $this->findFeatureTypeByName($featureTypeName);
				$ns = $this->getNamespace($featureTypeName);
				$url = $ft->getNamespace($ns);
				$postData .= "xmlns:" . $ns . "=\"" . $url . "\" ";	
			    }
			    $postData .= $typeNameParameterName."=\"".$featureTypeName."\">";
			    $postData .= $filter."</wfs:Query>";
 			    $postData .= "</wfs:GetFeature>";
			    return $this->post($this->getFeature, $postData);
			    break;
		        case "GET":
		            $getRequest = $this->getFeature.$this->getConjunctionCharacter($this->getFeature)."SERVICE=wfs&VERSION=".$version."&REQUEST=GetFeature"."&".$typeNameParameterName."=".$featureTypeName."&".$maxFeaturesParameterName."=".$maxFeatures."&STARTINDEX=".$startIndex."&".$maxFeaturesParameterName."=".$maxFeatures;
		            if ($outputFormat != false) {
			        $getRequest .= "&outputFormat=".$outputFormat;
		            }
		            if ($destSrs != false) {
			        $getRequest .= "&SRSNAME=".$srsName;
		            }
			    if ($filter != null) {
				$getRequest .= "&FILTER=".urlencode($filter);
			    }
//$e = new mb_exception("classes/class_wfs.php: getfeature-GET-url: ".$getRequest);
//$e = new mb_exception("classes/class_wfs.php: getfeature-GET-result: ".$this->get($getRequest));
                            return $this->get($getRequest);
			    break;
		    }
		}
	}

	public function getFeatureElementList($featureTypeName, $featureTypeElementName, $namespace, $namespaceLocation, $filter=null, $version=false, $method="GET") {
	    if ($version == false) {
	        $version = $this->getVersion();
	    } else {
	        $e = new mb_exception("classes/class_wfs.php: wfs version forced to " . $version . "!");
	    }
	    if ($destSrs != false) {
	        //check crs representation
	        $crs = new Crs($destSrs);
	        $alterAxisOrder = $crs->alterAxisOrder("wfs_".$version);
	        $srsId = $crs->identifierCode;
	        //add srs to request
	    }
	    switch ($version) {
	        case "2.0.2":
	            $typeNameParameterName = "typeNames";
	            $maxFeaturesParameterName = "COUNT";
	            break;
	        case "2.0.0":
	            $typeNameParameterName = "typeNames";
	            $maxFeaturesParameterName = "COUNT";
	            break;
	        default:
	            $typeNameParameterName = "typeName";
	            $maxFeaturesParameterName = "MAXFEATURES";
	            break;
	    }
	    if ($version == "1.0.0") {
	        $e = new mb_exception("classes/class_wfs.php: Get elements from features not possible in wfs <= 1.0.0!");
	        return false;
	    }
	    switch ($method) {
	        case "POST":
	            $e = new mb_exception("classes/class_wfs.php: getFeatureElementList only implements GET method!");
	            $resultList = False;
	            break;
	        case "GET":
	            if (is_array($featureTypeElementName)) {
	                $e = new mb_exception("is_array!");
	                $url = $this->getFeature.$this->getConjunctionCharacter($this->getFeature)."service=WFS&request=GetFeature&version=".$version."&".strtolower($typeNameParameterName)."=".$featureTypeName."&PropertyName=".implode(',', $featureTypeElementName);
	            
	            } else { 
	               $url = $this->getFeature.$this->getConjunctionCharacter($this->getFeature)."service=WFS&request=GetFeature&version=".$version."&".strtolower($typeNameParameterName)."=".$featureTypeName."&PropertyName=".$featureTypeElementName;
	            }
	            if ($filter != null) {
	                $url .= "&FILTER=".urlencode($filter);
	            }
	            //auth is already integrated in ows class
	            //do request
	            $resultList = $this->get($url); //from class_ows!
	            break;
	    }
	    //parse resultList and give back array of entries
	    //path for elements: "/wfs:FeatureCollection/gml:featureMember/{ft_ns}:{ft_name}/{property_ns}:{property_name}"
	    $e = new mb_notice("classes/class_wfs.php: getFeatureElementList invoked - parse elements");
	    //$e = new mb_exception("classes/class_wfs.php: getelementlist: gml: " . $resultList);
	    if ($resultList != False) {
    	    libxml_use_internal_errors(true);
    	    try {
    	        $featureCollectionXml = simplexml_load_string($resultList);
    	        if ($featureCollectionXml === false) {
    	            foreach (libxml_get_errors() as $error) {
    	                $err = new mb_exception("classes/class_wfs.php:" . $error->message);
    	            }
    	            throw new Exception("classes/class_wfs.php:" . 'Cannot parse featureCollection XML!');
    	            //TODO give error message
    	        }
    	    } catch (Exception $e) {
    	        $err = new mb_exception("classes/class_wfs.php:" . $e->getMessage());
    	        //TODO give error message
    	    }
    	    if ($featureCollectionXml !== false) {
    	        $featureCollectionXml->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
    	        if ($version == '2.0.0' || $version == '2.0.2') {
    	            $featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs/2.0");
    	        } else {
    	            $featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs");
    	        }
    	        $featureCollectionXml->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
    	        $featureCollectionXml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
    	        $featureCollectionXml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
    	        $featureCollectionXml->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
    	        $featureCollectionXml->registerXPathNamespace("default", "");
    	        //register targetNamespace location of featuretype xsd
    	        if ($namespaceLocation != false && $namespace != false) {
    	           $featureCollectionXml->registerXPathNamespace($namespace, $namespaceLocation);
    	        }
    	        //use version from service !
    	        //preg_match('@version=(?P<version>\d\.\d\.\d)&@i', strtolower($url), $version);
    	        if (!$version) {
    	            $e = new mb_notice("classes/class_wfs.php: No version for wfs request given in reqParams!");
    	        }
    	        /*switch ($reqParams['version']) {
    	            case "1.0.0":
    	                //get # of features from counting features
    	                $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/gml:featureMember');
    	                $numberOfFeatures = count($numberOfFeatures);
    	                break;
    	            case "1.1.0":
    	                //get # of features from counting features
    	                $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/gml:featureMember');
    	                $numberOfFeatures = count($numberOfFeatures);
    	                break;
    	                //for wfs 2.0 - don't count features
    	            default:
    	                //get # of features from attribut
    	                $numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/@numberReturned');
    	                $numberOfFeatures = $numberOfFeatures[0];
    	                break;
    	        }*/
    	        //example older wfs versions: /wfs:FeatureCollection/gml:featureMember/vermkv:gemarkungen_rlp/vermkv:gmkgnr
    	        //$e = new mb_exception("classes/class_wfs.php: version neu: " . json_encode($version));
    	        $result = array();
    	        if (is_array($featureTypeElementName)) {
    	            foreach ($featureTypeElementName as $elementName) {
    	                if ($version == '2.0.0' || $version == '2.0.2') {
    	                    $xpathString = '//wfs:FeatureCollection/wfs:member/' . $featureTypeName . '/' . $namespace . ':' . $elementName;
    	                } else {
    	                    $xpathString = '//wfs:FeatureCollection/gml:featureMember/' . $featureTypeName . '/' . $namespace . ':' . $elementName; 
    	                }   	                
    	                $values = $featureCollectionXml->xpath($xpathString);
    	                foreach ($values as $value) {
    	                   $result[$elementName][] = (string)$value[0];
    	                }
    	            }   
    	        } else {
    	            if ($version == '2.0.0' || $version == '2.0.2') {
        	           $xpathString = '//wfs:FeatureCollection/wfs:member/' . $featureTypeName . '/' . $namespace . ':' . $featureTypeElementName;
    	            } else {
    	                $xpathString = '//wfs:FeatureCollection/gml:featureMember/' . $featureTypeName . '/' . $namespace . ':' . $featureTypeElementName;  
    	            }
        	        $values = $featureCollectionXml->xpath($xpathString);
        	        foreach ($values as $value) {
        	            $result[$featureTypeElementName][] = (string)$value[0];
        	        }
    	        }
    	        $e = new mb_notice("classes/class_wfs.php: " . count($result) . " found values.");
    	        return $result;
    	    }
	    }
	}
	
	public function countFeatures($featureTypeName, $filter=null, $destSrs=false, $version=false, $outputFormat=false, $method="POST") {
        //$e = new mb_exception("testwfs");
		if ($version == false) {
			$version = $this->getVersion();
		} else {
			$e = new mb_notice("classes/class_wfs.php: wfs version forced to ".$version."!");
		}
		if ($destSrs != false) {
			//check crs representation
			$crs = new Crs($destSrs);
			$alterAxisOrder = $crs->alterAxisOrder("wfs_".$version);
			$srsId = $crs->identifierCode;
			//add srs to request
		}
		switch ($version) {
			case "2.0.2":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			case "2.0.0":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				break;
			default:
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				break;
		}
		if ($version == "1.0.0") {
			$e = new mb_exception("Counting features not possible in wfs <= 1.0.0!");
			return false;
		}
		switch ($method) {
		    case "POST":
    			if($version == "2.0.0" || $version == "2.0.2") {
    				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
    					"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
    					"xmlns:wfs=\"http://www.opengis.net/wfs/2.0\" ".
    					"xmlns:fes=\"http://www.opengis.net/fes/2.0\" ".
    					"xmlns:gml=\"http://www.opengis.net/gml/3.2\" ".
    					"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
    					"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
    					"xsi:schemaLocation=\"http://www.opengis.net/wfs/2.0 http://schemas.opengis.net/wfs/2.0/wfs.xsd\" resultType=\"hits\"";
    				if (isset($outputFormat) && $outputFormat != '') {
    					$postData .= "outputFormat=\"".$outputFormat."\" "; //tag ends later	
    				} else {
    					$postData .= ">";
    				}
    			}//TODO: not already implemented - maybe usefull for older geoserver/mapserver implementations
    			else if($version == "1.1.0") {
    				$postData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
    						"<wfs:GetFeature service=\"WFS\" version=\"" . $version . "\" " .
    						"xmlns:wfs=\"http://www.opengis.net/wfs\" " .
    						"xmlns:gml=\"http://www.opengis.net/gml\" " .
    						"xmlns:ogc=\"http://www.opengis.net/ogc\" " .
    						"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" " .
    						"xsi:schemaLocation=\"http://www.opengis.net/wfs ../wfs/1.1.0/WFS.xsd\"";
    				if (isset($outputFormat) && $outputFormat != '') {
    					$postData .= "outputFormat=\"".$outputFormat."\" "; //tag ends later
    				} else {
    					$postData .= ">";
    				}
    			}
    			$postData .="<wfs:Query ";
    			if($destSrs) {
    				$postData .= "srsName=\"" . $srsName . "\" ";
    			}
    			//add namespace
    			if (strpos($featureTypeName, ":") !== false) {
    				$ft = $this->findFeatureTypeByName($featureTypeName);
    				$ns = $this->getNamespace($featureTypeName);
    				$url = $ft->getNamespace($ns);
    				$postData .= "xmlns:" . $ns . "=\"" . $url . "\" ";	
    			}
    			$postData .= $typeNameParameterName."=\"" . $featureTypeName . "\"  >";
    			$postData .= $filter ."</wfs:Query>";
    			$postData .= "</wfs:GetFeature>";		
    			$resultOfCount = $this->post($this->getFeature, $postData); //from class_ows!
                	break;
		    case "GET":
				//add namespace
    			if (strpos($featureTypeName, ":") !== false) {
    				$ft = $this->findFeatureTypeByName($featureTypeName);
    				$ns = $this->getNamespace($featureTypeName);
    				$url = $ft->getNamespace($ns);
					if ($version == "2.0.0" || $version == "2.0.2") {
    					$namespaces = "&NAMESPACES=xmlns(" . $ns . "," . $url . ")";	
					} else {
						$namespaces = "&NAMESPACE=xmlns(" . $ns . "=" . $url . ")";	
					}
    			} else {
					$namespaces = "";
				}
    			$url = $this->getFeature.$this->getConjunctionCharacter($this->getFeature)."service=WFS&request=GetFeature&version=".$version."&".strtolower($typeNameParameterName)."=".$featureTypeName."&resultType=hits".$namespaces;
    			if ($filter != null) {
    			    $url .= "&FILTER=".urlencode($filter);
    			}
    			//auth is already integrated in ows class
    			//do request
    			$resultOfCount = $this->get($url); //from class_ows!
    			break;
		}
//$e = new mb_exception("count: ".$url);
//$e = new mb_exception("count: ".$resultOfCount);
		try {
			$exceptionTest =  new SimpleXMLElement($resultOfCount);
			if ($exceptionTest == false) {
				throw new Exception('Cannot parse WFS number of hits request!');
				return false;
			}
		}
		catch (Exception $e) {
    			$e = new mb_exception($e->getMessage());
		}
		switch ($version) {
			case "2.0.0":
				$errorMessage = $exceptionTest->xpath('/ows:ExceptionReport/ows:Exception/ows:ExceptionText');
				break;
			default:
				$errorMessage = $exceptionTest->xpath('/ows:ExceptionReport/ows:Exception/ows:ExceptionText');
				break;
		}
		if (isset($errorMessage[0])) {
			$e = new mb_exception($errorMessage[0]);
			return false;
		}
		//parse hits
		try {
			$featureTypeHits =  new SimpleXMLElement($resultOfCount);
			if ($featureTypeHits == false) {
				throw new Exception('Cannot parse WFS number of hits request!');
			}
		}
		catch (Exception $e) {
    			$e = new mb_exception($e->getMessage());
		}
		switch ($version) {
			case "2.0.0":
				$hits = $featureTypeHits->xpath('/wfs:FeatureCollection/@numberMatched');
				break;
			case "2.0.2":
				$hits = $featureTypeHits->xpath('/wfs:FeatureCollection/@numberMatched');
				break;
			case "1.1.0":
				$hits = $featureTypeHits->xpath('/wfs:FeatureCollection/@numberOfFeatures');
				break;
		}
/* example 
<?xml version='1.0' encoding='UTF-8'?>
<wfs:FeatureCollection xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/wfs http://schemas.opengis.net/wfs/1.1.0/wfs.xsd" xmlns:wfs="http://www.opengis.net/wfs" timeStamp="2019-07-23T08:07:26Z" numberOfFeatures="183">
</wfs:FeatureCollection>
*/
		return (integer)$hits[0];
	}
	
	/**
	 * Performs a WFS transaction (delete, update or insert).
	 * 
	 * @return String the WFS reply
	 * @param $method String "delete", "update" or "insert"
	 * @param $wfsConf WfsConfiguration
	 * @param $gmlObj Gml
	 */
	public function transaction ($method, $wfsConf, $gmlObj) {
		
		//
		// get feature type and geometry column from WFS configuration
		//
		if (!($wfsConf instanceof WfsConfiguration)) {
			$e = new mb_exception("Invalid WFS configuration.");
			return null;
		}
		$featureType = $this->findFeatureTypeById($wfsConf->featureTypeId);
		$featureTypeName = $featureType->name;
		$geomColumnName = $wfsConf->getGeometryColumnName();
		$authWfsConfElement = $wfsConf->getAuthElement();

		//
		// GML string
		//
		if (!($gmlObj instanceof Gml)) {
			$e = new mb_exception("Not a GML object.");
			return null;
		}
		$gml = $gmlObj->toGml();
		if (is_null($gml)) {
			$e = new mb_exception("GML is not set.");
			return null;
		}
		
		// I assume that only one feature is contained in the GeoJSON,
		// so I just take the first from the collection.
		$feature = $gmlObj->featureCollection->featureArray[0];

		switch (strtolower($method)) {
			case "delete":
				$requestData = $this->transactionDelete($feature, $featureType, $authWfsConfElement);
				break;
			case "update":
				$requestData = $this->transactionUpdate($feature, $featureType, $authWfsConfElement, $gml, $geomColumnName);
				break;
			case "insert":
				$requestData = $this->transactionInsert($feature, $featureType, $authWfsConfElement, $gml, $geomColumnName);
				break;
			default:
				$e = new mb_exception("Invalid transaction method: " . $method);
				return null;
		}		
		$postData = $this->wrapTransaction($featureType, $requestData);
		return $this->post($this->transaction, $postData); //from class_ows!
	}
	
	protected function transactionInsert ($feature, $featureType, $authWfsConfElement, $gml, $geomColumnName) {
		$featureTypeName = $featureType->name;
		$ns = $this->getNamespace($featureTypeName);
		
		// authentication
		$authSegment = "";
		if (!is_null($authWfsConfElement)) {
			$user = eval("return " . $authWfsConfElement->authVarname . ";");
			$authSegment = "<$ns:{$authWfsConfElement->name}>$user</$ns:{$authWfsConfElement->name}>";
		}

		// add properties
		$propertiesSegment = "";
		foreach ($feature->properties as $key => $value) {
			if (isset($value)) {
				if (is_numeric($value) || $value == "" || $value == "NULL") {
					$value = $value;
				} else {
					$value = "<![CDATA[$value]]>";
				}
				if ($value != "NULL") {
					$propertiesSegment .= "<$ns:$key>$value</$ns:$key>";
				}
			}
		}

		// add spatial data
		$geomSegment = "<$ns:$geomColumnName>" . $gml . "</$ns:$geomColumnName>";

		return "<wfs:Insert><$featureTypeName>" . $authSegment . 
				$propertiesSegment . $geomSegment . 
				"</$featureTypeName></wfs:Insert>";
	}
	
	protected function transactionUpdate ($feature, $featureType, $authWfsConfElement, $gml, $geomColumnName) {
		$featureTypeName = $featureType->name;
		$featureNS = $this->getNameSpace($featureType->name);

		// authentication
		$authSegment = "";
		if (!is_null($authWfsConfElement)) {
			$user = eval("return " . $authWfsConfElement->authVarname . ";");
			$authSegment = "<ogc:PropertyIsEqualTo><ogc:PropertyName>" . 
				$featureNS . ":" . $authWfsConfElement->name . 
				"</ogc:PropertyName><ogc:Literal>" . 
				$user . "</ogc:Literal></ogc:PropertyIsEqualTo>";

		}

		// add properties
		$propertiesSegment = "";
		foreach ($feature->properties as $key => $value) {
			if (isset($value)) {
				if (is_numeric($value) || $value == "" || $value == "NULL") {
					$value = $value;
				}
				else {
					$value = "<![CDATA[$value]]>";
				}
				if ($value != "NULL") {
					$propertiesSegment .= "<wfs:Property><wfs:Name>$featureNS:$key</wfs:Name>" . 
						"<wfs:Value>$value</wfs:Value></wfs:Property>";
				}
			}
		}

		// filter
		if (!isset($feature->fid)) {
			$e = new mb_exception("Feature ID not set.");
			return null;
		}
		$condition = $this->getFeatureIdFilter($feature->fid);
		if ($authSegment !== "") {
			$condition = "<And>" . $condition . $authSegment . "</And>";
		}
		$filterSegment = "<ogc:Filter>" . $condition . "</ogc:Filter>";

		// add geometry
		$geomSegment = "<wfs:Property><wfs:Name>$featureNS:$geomColumnName</wfs:Name>" . 
			"<wfs:Value>" . $gml . "</wfs:Value></wfs:Property>";
					

		return "<wfs:Update typeName=\"$featureTypeName\">" . 
				$propertiesSegment . 
				$geomSegment . 
				$filterSegment . 
				"</wfs:Update>";
	}
	
	public function getFeatureById ($featureTypeName, $outputFormat=false, $id, $version=false, $srsName=false) {
		if ($version == false) {
			$version = $this->getVersion();
		} else {
			$e = new mb_notice("classes/class_wfs.php: wfs version forced to " . $version . "!");
			$version = $version;
		}
		$getFeatureByIdName = false;
		//$e = new mb_exception(json_encode($this->storedQueriesArray));
		switch ($version) {
			case "2.0.2":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				$featureIdParameterName = "featureID";
				if (in_array("GetFeatureById", $this->storedQueriesArray)) {
				    $getFeatureByIdName = "GetFeatureById";
				}
				if (in_array("urn:ogc:def:query:OGC-WFS::GetFeatureById", $this->storedQueriesArray)) {
				    $getFeatureByIdName = "urn:ogc:def:query:OGC-WFS::GetFeatureById";
				}
				break;
			case "2.0.0":
				$typeNameParameterName = "typeNames";
				$maxFeaturesParameterName = "COUNT";
				$featureIdParameterName = "featureID";
				if (in_array("GetFeatureById", $this->storedQueriesArray)) {
				    $getFeatureByIdName = "GetFeatureById";
				}
				if (in_array("urn:ogc:def:query:OGC-WFS::GetFeatureById", $this->storedQueriesArray)) {
				    $getFeatureByIdName = "urn:ogc:def:query:OGC-WFS::GetFeatureById";
				}
				break;
			default:
				$typeNameParameterName = "typeName";
				$maxFeaturesParameterName = "MAXFEATURES";
				$featureIdParameterName = "featureID";
				break;
		}
		if ($getFeatureByIdName != false) {
		    $getRequest = $this->getFeature .
		    $this->getConjunctionCharacter($this->getFeature) .
		    "service=WFS&request=GetFeature&version=" .
		    $this->getVersion() . "&".strtolower($typeNameParameterName)."=" . $featureTypeName."&STOREDQUERY_ID=".$getFeatureByIdName."&ID=".$id;
		} else {		    
		    $getRequest = $this->getFeature .
			$this->getConjunctionCharacter($this->getFeature) . 
			"service=WFS&request=GetFeature&version=" . 
			$version . "&".strtolower($typeNameParameterName)."=" . $featureTypeName."&".$featureIdParameterName."=".$id;
		}
		if ($outputFormat != false) {
			$getRequest .= "&outputFormat=".$outputFormat;
		}
		if ($srsName != false) {
		//check crs representation
			$crs = new Crs($srsName);
			$alterAxisOrder = $crs->alterAxisOrder("wfs_".$version);
			$srsId = $crs->identifierCode;
			//add srs to request
			switch ($version) {
				case "2.0.2":
					$getRequest .= "&SRSNAME="."http://www.opengis.net/def/crs/EPSG/0/".$srsId;
					break;
				case "2.0.0":
					$getRequest .= "&SRSNAME="."urn:ogc:def:crs:EPSG::".$srsId;	
					break;
				case "1.1.0":
					$getRequest .= "&SRSNAME="."urn:ogc:def:crs:EPSG::".$srsId;
					break;
				case "1.0.0":
					$getRequest .= "&SRSNAME="."EPSG:".$srsId;
					break;
			}
		}
		$e = new mb_exception("classes/class_wfs.php - getfeaturebyid - request: ".$getRequest);
		return $this->get($getRequest); //from class_ows!
	}
	
	protected function transactionDelete ($feature, $featureType, $authWfsConfElement) {
		$featureTypeName = $featureType->name;
		$featureNS = $this->getNameSpace($featureType->name);
		
		// authentication
		$authSegment = "";
		if (!is_null($authWfsConfElement)) {
			$user = eval("return " . $authWfsConfElement->authVarname . ";");
			$authSegment = "<ogc:PropertyIsEqualTo><ogc:PropertyName>" . 
				$featureNS . ":" . $authWfsConfElement->name . 
				"</ogc:PropertyName><ogc:Literal>" . 
				$user . "</ogc:Literal></ogc:PropertyIsEqualTo>";
		}
		// filter
		if (!isset($feature->fid)) {
			$e = new mb_exception("Feature ID not set.");
			return null;
		}
		$condition = $this->getFeatureIdFilter($feature->fid);
		if ($authSegment !== "") {
			$condition = "<And>" . $condition . $authSegment . "</And>";
		}

		return "<wfs:Delete typeName=\"$featureTypeName\">" . 
			"<ogc:Filter>" . $condition . "</ogc:Filter>" . 
			"</wfs:Delete>";
	}
	
	protected function wrapTransaction ($featureType, $wfsRequest) {
		$featureNS = $this->getNameSpace($featureType->name);
		
		$ns_gml = 'xmlns:gml="http://www.opengis.net/gml" ';	
		$ns_ogc = 'xmlns:ogc="http://www.opengis.net/ogc" ';	
		$ns_xsi = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		$ns_featureNS = "xmlns:$featureNS=\"http://www.someserver.com/$featureNS\" ";	
		$ns_wfs = "xmlns:wfs=\"http://www.opengis.net/wfs\" ";	
		$strForSchemaLocation = "";
		
		foreach ($featureType->namespaceArray as $namespace) {
			$n = $namespace->name;
			$v = $namespace->value;

			if ($n === "gml") {
				 $ns_gml = "xmlns:$n=\"$v\" ";
			} 
			else if ($n === "ogc") {
				$ns_ogc = "xmlns:$n=\"$v\" ";
			} 
			else if ($n === "xsi") {
				$ns_xsi = "xmlns:$n=\"$v\" ";
			} 
			else if ($n === "wfs") {
				$ns_wfs = "xmlns:$n=\"$v\" ";
			} 
			else if ($n === $featureNS) {
				$ns_featureNS = "xmlns:$n=\"$v\" ";
				$strForSchemaLocation = $v;
			}
		}
		//TODO: There will be a problem with xsi:schemaLocation and geoserver 2.1? See class_wfs.php from branch 2.7! The part may be commented out 
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . 
			"<wfs:Transaction version=\"" . $this->getVersion() . 
			"\" service=\"WFS\" " . $ns_gml . $ns_ogc . $ns_xsi . 
			$ns_featureNS . $ns_wfs . 
			"xsi:schemaLocation=\"http://www.opengis.net/wfs" . 
			" http://schemas.opengis.net/wfs/1.0.0/WFS-transaction.xsd " . 
			$strForSchemaLocation . " " . $this->describeFeatureType . 
			$this->getConjunctionCharacter($this->describeFeatureType) . 
			"typename=" . $featureType->name . "&amp;REQUEST=DescribeFeatureType" .
			"\">" .	$wfsRequest . "</wfs:Transaction>";
	}
	
	
	// -----------------------------------------------------------------------
	//
	// Output formats
	//
	// -----------------------------------------------------------------------
	
	/**
	 * Compiles a string containing HTML formatted information about the WFS.
	 * 
	 * @return String 
	 */
	public function toHtml () {
		$wfsString = "";
		$wfsString .= "id: " . $this->id . " <br>";
		$wfsString .= "version: " . $this->getVersion() . " <br>";
		$wfsString .= "name: " . $this->name . " <br>";
		$wfsString .= "title: " . $this->title . " <br>";
		$wfsString .= "abstract: " . $this->summary . " <br>";
		$wfsString .= "capabilitiesrequest: " . $this->getCapabilities . " <br>";
		$wfsString .= "describefeaturetype: " . $this->describeFeatureType . " <br>";
		$wfsString .= "getfeature: " . $this->getFeature . " <br>";
		$wfsString .= "transaction: " . $this->transaction . " <br>";

		for ($i = 0; $i < count($this->featureTypeArray); $i++) {
			$currentFeatureType = $this->featureTypeArray[$i];
			$wfsString .= $currentFeatureType->toHtml();
		}
		
		for ($i = 0; $i < count($this->storedQueriesArray); $i++) {
			$currentQuery = $this->storedQueriesArray[$i];
			$wfsString .= "<hr>";
			$wfsString .= "Stored Query ID: ". $currentQuery->description['Id'] . " <br>";
			$wfsString .= "title: ".$currentQuery->description['Title']. " <br>";
			$wfsString .= "returnFeaturetype: ".$currentQuery->returnFeaturetype. " <br>";
			if(gettype($aWfsStoredQuery->description['Parameter'][0]) == "array") {
				for ($i = 0; $i < count($currentQuery->description['Parameter']); $i++) {
					$param = $currentQuery->description['Parameter'][$i];
					$name = $param['name'];
					$type = $param['type'];
					$wfsString .= " parameter: " . $name . 
							" - " . $type . "<br>";
				}
			}
			else {
				$param = $currentQuery->description['Parameter'];
			
				if($param) {
					$name = $param['name'];
					$type = $param['type'];
					$wfsString .= " parameter: " . $name .
					" - " . $type . "<br>";
				}
			}
		}
		
		return $wfsString;
	}

	/**
	 * Can be switched to other output format if desired.
	 * 
	 * @return String
	 */
	public function __toString () {
		return $this->toHtml();
	}

	/**
	 * Creates a string of JavaScript code. This code will then 
	 * create a WFS object on the client side.
	 * 
	 * @return String
	 */
	public function toJavaScript () {
		$jsString = "";

		$parent = "";
		if (func_num_args() == 1 && func_get_arg(0) == true) {
			$parent = "parent.";
		}
		
		if(!$this->title || $this->title == ""){
			$e = new mb_exception("Error: no valid capabilities-document !!");
			return null;
		}

		$jsString .= $parent . "add_wfs('". 
			$this->id ."','".
			$this->getVersion() ."','".
			$this->title ."','".
			$this->summary ."','". 
			$this->getCapabilities ."','" .
			$this->describeFeatureType .
			"');";
			
	
		for ($i = 0; $i < count($this->featureTypeArray); $i++) {
			$currentFeatureType = $this->featureTypeArray[$i];
			
			$jsString .= $parent . "wfs_add_featuretype('". 
				$currentFeatureType->name ."','". 
				$currentFeatureType->title . "','".
				$currentFeatureType->summary . "','".  
				$currentFeatureType->srs ."','". 
				$currentFeatureType->geomtype .
				"');";
				
			for ($j = 0; $j < count($currentFeatureType->elementArray); $j++) {
				$currentElement = $currentFeatureType->elementArray[$j];
				
				$jsString .= $parent . "wfs_add_featuretype_element('" . 
					$currentElement->name . "', '" . 
					$currentElement->type . "', " .
					$j . ", " . 
					$i . 
					");";
			}
			
			for ($j = 0; $j < count($currentFeatureType->namespaceArray); $j++) {
				$currentNamespace = $currentFeatureType->namespaceArray[$j];
				
				$jsString .= $parent . "wfs_add_featuretype_namespace('" . 
					$currentNamespace->name . "', '" . 
					$currentNamespace->value . "', " . 
					$j . ", " . 
					$i . 
					");";
			}
			
		}
		return $jsString;
	}

	/**
	 * For backwards compatibility only. Echoes a string directly.
	 * 
	 * @deprecated
	 * @return 
	 * @param $parent Boolean
	 */
	public function createJsObjFromWFS($parent){
		echo $this->toJavaScript($parent);
	}

	// -----------------------------------------------------------------------
	//
	// Database interface
	//
	// -----------------------------------------------------------------------
	
	/**
	 * Database wrapper function
	 * 
	 * @return Boolean
	 */
	public function insertOrUpdate ($owner=false) {
		return WfsToDb::insertOrUpdate($this, $owner);
	}

	/**
	 * Database wrapper function
	 * 
	 * @return Boolean
	 */
	public function insert () {
		return WfsToDb::insert($this);
	}

	/**
	 * Database wrapper function
	 * 
	 * @return Boolean
	 */
	public function update ($updateMetadataOnly=false) {
		return WfsToDb::update($this,$updateMetadataOnly);
	}
	
	/**
	 * Database wrapper function
	 * 
	 * @return Boolean
	 */
	public function delete () {
		return WfsToDb::delete($this);
	}

	/**
	 * Database wrapper function
	 * 
	 * @return Boolean
	 */
	public function exists () {
		return WfsToDb::exists($this);
	}
}
?>
