<?php
# $Id: class_wfs.php 3094 2008-10-01 13:52:35Z christoph $
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
require_once(dirname(__FILE__)."/../classes/class_wfs_factory.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_1_0.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_featuretype.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

/**
 * Creates WFS 1.0 objects from a capabilities documents.
 * 
 * @return Wfs_1_0
 */
class Wfs_1_0_Factory extends WfsFactory {

	protected function createFeatureTypeFromUrl ($aWfs, $featureTypeName) {
		$url = $aWfs->describeFeatureType . 
			$aWfs->getConjunctionCharacter($aWfs->describeFeatureType) . 
			"&SERVICE=WFS&VERSION=" . $aWfs->getVersion() . 
			"&REQUEST=DescribeFeatureType&TYPENAME=" . $featureTypeName;
		if (isset($aWfs->auth)) {
			$xml = $this->get($url, $aWfs->auth);
		} else {
			$xml = $this->get($url);
		}
		return $this->createFeatureTypeFromXml ($xml, $aWfs,$featureTypeName);
	}
		
	protected function createFeatureTypeFromXml ($xml, $myWfs, $featureTypeName) {
		$newFeatureType = new WfsFeatureType($myWfs);

		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$xpath =  new DOMXpath($doc);
		$xpath->registerNamespace("xs","http://www.w3.org/2001/XMLSchema");

		// populate a Namespaces Hashtable where we can use the namespace as a lookup for the prefix
		// and also keep a 
		$namespaces = array();
		$namespaceList = $xpath->query("//namespace::*");
		$targetNamespace = $doc->documentElement->getAttribute("targetNamespace");
		$targetNamespaceNode = null;

		foreach($namespaceList as $namespaceNode){
			$namespaces[$namespaceNode->nodeValue] = $namespaceNode->localName;
			if($namespaceNode->nodeValue == $targetNamespace){
				$targetNamespaceNode = $namespaceNode;
			}
			$newFeatureType->addNamespace($namespaceNode->localName, $namespaceNode->nodeValue);
		}
	

		list($ftLocalname,$ftTypePrefix) = array_reverse(explode(":",$featureTypeName));
		// for the sake of simplicity we only care about top level elements. Seems to have worked so far
		$query = sprintf("/xs:schema/xs:element[@name='%s']",$ftLocalname);
		$elementList = $xpath->query($query);
		//parse single elements - if the schema is complex, store only the DescribeFeaturetype response
		$newFeatureType->schema_problem = 'f';
		$newFeatureType->schema = $xml;
		foreach ($elementList as $elementNode){
			$elementName = $elementNode->getAttribute("name");
			$elementType = $elementNode->getAttribute("type");
            		//if Type is empty, we assume an anonymousType, else we go looking for the anmed Type
            		if($elementType == ""){
                		//Just querying for complexTypes containing a Sequence - good enough for Simple Features
                		$query = "xs:complexType//xs:element";
                		$subElementList = $xpath->query($query,$elementNode);
            		} else {
                		// The elementType is now bound to a prefix e.g. topp:housType
                		// if the prefix is in the targetNamespace, changces are good it's defined in this very document
                		// if the prefix is not in the targetNamespace, it's likely not defined here, and we bail

                		list($elementTypeLocalname,$elementTypePrefix) = array_reverse(explode(":",$elementType));
                		$elementTypeNamespace = $doc->lookupNamespaceURI($elementTypePrefix);
                		if($elementTypeNamespace !== $targetNamespaceNode->nodeValue){
                    			$e = new mb_warning("Tried to parse FeatureTypeName $featureTypeName : $elementType is not in the targetNamespace");	
                    			break;
                		}

                		// Just querying for complexTypes containing a Sequence - good enough for Simple Features
                		$query = sprintf("//xs:complexType[@name='%s']//xs:element",$elementTypeLocalname);
                		$subElementList = $xpath->query($query);
            		}
	    		foreach ($subElementList as $subElement) {
            			// Since this is a rewrite of the old way, it reproduces it quirks
                		// in this case the namespace of the type was cut off for some reason
				$name = $subElement->getAttribute('name');
                		$typeParts = explode(":",$subElement->getAttribute('type'));
				//$e = new mb_exception("element: ".$name." - type: ".$typeParts[0]);
				if (empty($typeParts[0])) {
					$e = new mb_warning("No type attribute found in xs:element - test for integrated simpleType!");
					//it maybe a simple type
					/*<xs:element name="kennung" nillable="true" minOccurs="0" maxOccurs="1">
						<xs:simpleType>
							<xs:restriction base="string">
								<xs:maxLength value="40"/>
							</xs:restriction>
						</xs:simpleType>
					</xs:element>*/
					$query = "xs:simpleType/xs:restriction";
					$restriction = $xpath->query($query,$subElement);
						
					if (gettype($restriction->item(0)) == 'object') {
						$type = $restriction->item(0)->getAttribute('base');
					} else {
						$e = new mb_exception("classes/class_wfs_1_0_factory.php: Problem while parsing schema for featuretype ".$featureTypeName);
						$newFeatureType->schema_problem = 't';
					}
					//TODO parse further information from xsd like maxLength: <xs:maxLength value="40"/> - add further column in wfs_element!
				} else {
					switch (count($typeParts)) {
						case 1 :
							$type = $typeParts[0];
						break;
						case 2 :
							$type = $typeParts[1];
						break;
					}
				}
	    			$newFeatureType->addElement($name,$type);
	    		}

		}
		return $newFeatureType;
	}

	/**
	 * Creates WFS 1.0 objects from a capabilities documents.
	 * 
	 * @return Wfs_1_0
	 * @param $xml String
	 */
	public function createFromXml ($xml, $auth=false) {
		try {
			$myWfs = new Wfs_1_0();
			//new for parsing with simple xml:
			$admin = new administration();
			$myWfs->getCapabilitiesDoc = $admin->char_encode($xml);
			$myWfs->id = $this->createId();
			//check for authentication
			if (!$auth) {
				$e = new mb_notice("class_wfs_1_0_factory.php - createFromXml - no athentication info given!");
			}
			$myWfs->auth = $auth; //always!
			try {
				$xml = str_replace('xlink:href', 'xlinkhref', $xml);
				#http://forums.devshed.com/php-development-5/simplexml-namespace-attributes-problem-452278.html
				#http://www.leftontheweb.com/message/A_small_SimpleXML_gotcha_with_namespaces
				$wfs10Cap = new SimpleXMLElement($xml);
				//$wfs10Cap =  new SimpleXMLElement($xml);
				if ($wfs10Cap === false) {
					foreach(libxml_get_errors() as $error) {
        					$e = new mb_exception($error->message);
    					}
					throw new Exception('Cannot parse WFS 1.0.0 Capabilities!');
				}
			}
			catch (Exception $e) {
    				$e = new mb_exception($e->getMessage());
			}	
		
			if ($wfs10Cap !== false) {
				//read all relevant information an put them into the mapbender wfs object
				//xmlns="http://www.opengis.net/wfs"
				//Setup default namespace
				$wfs10Cap->registerXPathNamespace("wfs", "http://www.opengis.net/wfs");
				$wfs10Cap->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
				$wfs10Cap->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
				//some debug
				//$e = new mb_notice("XML string from memory: ".$wfs10Cap->asXML());
				$myWfs->version = $wfs10Cap->xpath('/wfs:WFS_Capabilities/@version');
				$myWfs->version = $myWfs->version[0];
				$myWfs->name = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Service/wfs:Name');
				$myWfs->name = $myWfs->name[0];
				$myWfs->title = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Service/wfs:Title');
				$myWfs->title = $this->stripEndlineAndCarriageReturn($myWfs->title[0]);
				$myWfs->summary = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Service/wfs:Abstract');
				$myWfs->summary = $this->stripEndlineAndCarriageReturn($myWfs->summary[0]);
				$myWfs->fees = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Service/wfs:Fees');
				$myWfs->fees = $myWfs->fees[0];
				$myWfs->accessconstraints = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Service/wfs:AccessConstraints');
				$myWfs->accessconstraints = $myWfs->accessconstraints[0];

				$myWfs->getCapabilities = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:GetCapabilities/wfs:DCPType/wfs:HTTP/wfs:Get/@onlineResource');
				$myWfs->getCapabilities = $myWfs->getCapabilities[0];
				$myWfs->describeFeatureType = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:DescribeFeatureType/wfs:DCPType/wfs:HTTP/wfs:Post/@onlineResource');
				$myWfs->describeFeatureType = $myWfs->describeFeatureType[0];
				$myWfs->getFeature = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:GetFeature/wfs:DCPType/wfs:HTTP/wfs:Post/@onlineResource');
				$myWfs->getFeature = $myWfs->getFeature[0];
				$myWfs->transaction = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:Capability/wfs:Request/wfs:Transaction/wfs:DCPType/wfs:HTTP/wfs:Post/@onlineResource');
				$myWfs->transaction = $myWfs->transaction[0];

				$capFeatureTypes = $wfs10Cap->xpath('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType');
				
				foreach ($capFeatureTypes as $featureType) {

					$featuretype_name = $this->stripEndlineAndCarriageReturn($featureType->Name[0]);
					$featuretype_title = $this->stripEndlineAndCarriageReturn($featureType->Title[0]);
					$featuretype_abstract = $this->stripEndlineAndCarriageReturn($featureType->Abstract[0]);
					$featuretype_srs = $featureType->SRS[0];
					$LatLongBoundingBox = $featureType->LatLongBoundingBox[0];
					//read bbox of featuretype
					$featuretype_latlon_minx = $LatLongBoundingBox->xpath('@minx');
					$featuretype_latlon_minx = $featuretype_latlon_minx[0];
					$featuretype_latlon_miny = $LatLongBoundingBox->xpath('@miny');
					$featuretype_latlon_miny = $featuretype_latlon_miny[0];
					$featuretype_latlon_maxx = $LatLongBoundingBox->xpath('@maxx');
					$featuretype_latlon_maxx = $featuretype_latlon_maxx[0];
					$featuretype_latlon_maxy = $LatLongBoundingBox->xpath('@maxy');
					$featuretype_latlon_maxy = $featuretype_latlon_maxy[0];
					//NOTICE: for WFS 1.0.0 latlonbbox is given in featuretypes SRS and not in EPSG:4326 - it has to be reprojected ;-)
					$n = new mb_notice("Calculation of BBOX for EPSG:4326");
					$pointMin = new Mapbender_point($featuretype_latlon_minx, $featuretype_latlon_miny, preg_replace("/EPSG:/", "", $featuretype_srs));
					$pointMax = new Mapbender_point($featuretype_latlon_maxx, $featuretype_latlon_maxy, preg_replace("/EPSG:/", "", $featuretype_srs));
					$pointMin->transform("4326");	
					$pointMax->transform("4326");
					if($pointMin->epsg != '' && $pointMin->x != '' && $pointMin->y != '' 
						&& $pointMax->x != '' && $pointMax->y != '') {
						$featuretype_latlon_minx = $pointMin->x;
						$featuretype_latlon_miny = $pointMin->y;
						$featuretype_latlon_maxx = $pointMax->x;
						$featuretype_latlon_maxy = $pointMax->y;
						$n = new mb_notice("Calculation of BBOX for EPSG:4326 finished successful.");
					}
					else {
						$e = new mb_exception("Could not transform BBOX from ".$featuretype_srs." to EPSG:4326.");
					}
					//<wfs:MetadataURL type="FGDC" format="text/xml">http://www.ogccatservice.com/csw.cgi?service=CSW&amp;version=2.0.0&amp;request=GetRecords&amp;constraintlanguage=CQL&amp;constraint="recordid=urn:uuid:4ee8b2d3-9409-4a1d-b26b-6782e4fa3d59"</wfs:MetadataURL>
					$metadataURLArray = $featureType->MetadataURL;
					$featuretype_metadataUrl = array();
					$i_mdu = 0;
					foreach ($metadataURLArray as $metadataURL) {
						//$e = new mb_exception("other srs: ".$otherSRS);
						$featuretype_metadataUrl[$i_mdu]->href = $metadataURL;
						$e = new mb_notice("metadataurl: ".$metadataURL);
						$featuretype_metadataUrl[$i_mdu]->type = $metadataURL->attributes()->type;
						$e = new mb_notice("type: ".$featuretype_metadataUrl[$i_mdu]->type);
						$featuretype_metadataUrl[$i_mdu]->format = $metadataURL->attributes()->format;
						$e = new mb_notice("format: ".$featuretype_metadataUrl[$i_mdu]->format);
						$i_mdu++;
					}
					//do not add defective featuretypes - first request them via net
					try {
						$currentFeatureType = $this->createFeatureTypeFromUrl($myWfs, $featuretype_name);
						if ($currentFeatureType !== null) {
							$currentFeatureType->name = $featuretype_name;
							$currentFeatureType->title = $featuretype_title;
							$currentFeatureType->summary = $featuretype_abstract;
							$currentFeatureType->srs = $featuretype_srs;
							$currentFeatureType->latLonBboxArray['minx'] = $featuretype_latlon_minx;
							$currentFeatureType->latLonBboxArray['miny'] = $featuretype_latlon_miny;
							$currentFeatureType->latLonBboxArray['maxx'] = $featuretype_latlon_maxx;
							$currentFeatureType->latLonBboxArray['maxy'] = $featuretype_latlon_maxy;
							$currentFeatureType->metadataUrlArray = $featuretype_metadataUrl;
							$myWfs->addFeatureType($currentFeatureType);
						}
					}
					catch (Exception $e) {
						new mb_exception("Failed to load featuretype " . $featuretype_name);
					}		
				} //end for each featuretype
			} //end of parsing wfs capabilities
			return $myWfs;
		}
		catch (Exception $e) {
			$e = new mb_exception($e);
			return null;
		}
	}
	
	public function createFromDb ($id) {
		$myWfs = new Wfs_1_0();
		return parent::createFromDb($id, $myWfs);
	}
}
?>
