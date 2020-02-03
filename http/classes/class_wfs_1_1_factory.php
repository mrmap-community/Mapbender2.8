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
require_once(dirname(__FILE__)."/../classes/class_wfs_1_1.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_featuretype.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

/**
 * Creates WFS 1.1 objects from a capabilities documents.
 *
 * @return Wfs_1_1
 */
class Wfs_1_1_Factory extends WfsFactory {

	protected function createFeatureTypeFromUrl ($aWfs, $featureTypeName, $featureTypeNsArray) {
		$postData = "<?xml version=\"1.0\"?>\n".
				"<DescribeFeatureType version=\"" . $aWfs->getVersion() . "\" " .
				"service=\"WFS\" xmlns=\"http://www.opengis.net/wfs\" ";

		$nsUrl = $featureTypeNsArray["xmlns:" . $key];
		if (!$nsUrl) {
			$nsUrl = $featureTypeNsArray[$key];
		}

		if ($featuretype_name != $this->sepNameSpace($featureTypeName)) {
			$key = "xmlns:" . $this->getNameSpace($featureTypeName);
			$postData .= $key . "=\"" . $nsUrl . "\" ";
		}
		$postData .= "><TypeName>" . $featureTypeName . "</TypeName>" .
				"</DescribeFeatureType>";

		$xml = $this->post($aWfs->describeFeatureType, $postData);
		return $this->createFeatureTypeFromXml ($xml, $aWfs, $featureTypeName);
	}

	protected function createFeatureTypeFromUrlGet ($aWfs, $featureTypeName, $featureTypeNsArray) {

		$key = $this->getNameSpace($featureTypeName);
		$nsUrl = $featureTypeNsArray["xmlns:" . $key];
		if (!$nsUrl) {
			$nsUrl = $featureTypeNsArray[$key];
		}
		$paramArray = array(
			"SERVICE=WFS",
			"VERSION=1.1.0",
			"REQUEST=DescribeFeatureType",
			"TYPENAME=" . urlencode($featureTypeName),
			"NAMESPACE=" . urlencode(
				"xmlns(" . $key . "=" . $nsUrl . ")"
		));

		$url = $aWfs->describeFeatureType .
			$aWfs->getConjunctionCharacter($aWfs->describeFeatureType) .
			implode("&", $paramArray);
		
		if (!($aWfs->auth)) {
			$e = new mb_notice("class_wfs_1_1_factory.php - createFeatureTypeFromUrlGet: authentication for wfs not given");
		}
		$xml = $this->get($url, $aWfs->auth);
		//parse result to see if it is a real featuretype description
		
		return $this->createFeatureTypeFromXml ($xml, $aWfs, $featureTypeName);
	}

	/**
	 * Given an XSD document (usually from a DescribefeatureType Operation) 
	 * @returns a WfsFeatureType
	 * 

	*/
	protected function createFeatureTypeFromXml ($xml, $myWfs, $featureTypeName) {
		$newFeatureType = new WfsFeatureType($myWfs);
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$e = new mb_notice("class_wfs_1_1_factory.php: Got following FeatureType XML: ".$xml);
		$xpath =  new DOMXpath($doc);
		$xpath->registerNamespace("xs","http://www.w3.org/2001/XMLSchema");

		// populate a Namespaces Hastable where we can use the namesopace as a lookup for the prefix
		// and also keep a 
		$namespaces = array();
		$namespaceList = $xpath->query("//namespace::*");
		$targetNamespace = $doc->documentElement->getAttribute("targetNamespace");
		$targetNamespaceNode = null;
		//add all namespaces to featuretype
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
						$e = new mb_exception("classes/class_wfs_1_1_factory.php: Problem while parsing schema for featuretype ".$featureTypeName);
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
	 * Creates a WFS 1.1 object from a capabilities document.
	 *
	 * @return Wfs_1_1
	 * @param $xml String
	 */
	public function createFromXml ($xml, $auth=false) {
		try {
			$myWfs = new Wfs_1_1();
			$admin = new administration();
			$myWfs->getCapabilitiesDoc = $admin->char_encode($xml);
			$myWfs->id = $this->createId();
			//check for authentication
			if (!$auth) {
				$e = new mb_notice("class_wfs_1_1_factory.php - createFromXml - no authentication info given!");
			}
			$myWfs->auth = $auth; //always!
			$featuretype_crsArray = array();//new for wfs 1.1.0
			try {
				$xml = str_replace('xlink:href', 'xlinkhref', $xml);
				#http://forums.devshed.com/php-development-5/simplexml-namespace-attributes-problem-452278.html
				#http://www.leftontheweb.com/message/A_small_SimpleXML_gotcha_with_namespaces

				$wfs11Cap = new SimpleXMLElement($xml);

				if ($wfs11Cap === false) {
					foreach(libxml_get_errors() as $error) {
        					$e = new mb_exception($error->message);
    					}
					throw new Exception('Cannot parse WFS 1.1.0 Capabilities!');
				}
			}
			catch (Exception $e) {
    				$e = new mb_exception($e->getMessage());
			}	

			if ($wfs11Cap !== false) {
				//read all relevant information an put them into the mapbender wfs object
				//xmlns="http://www.opengis.net/wfs"
				//Setup default namespace

				$wfs11Cap->registerXPathNamespace("wfs", "http://www.opengis.net/wfs");
				$wfs11Cap->registerXPathNamespace("ows", "http://www.opengis.net/ows");
				$wfs11Cap->registerXPathNamespace("gml", "http://www.opengis.net/gml");
				$wfs11Cap->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
				$wfs11Cap->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
				$wfs11Cap->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
				$wfs11Cap->registerXPathNamespace("default", "http://www.opengis.net/wfs");
				//some debug
				//$e = new mb_notice("XML string from memory: ".$wfs11Cap->asXML());
				$myWfs->version = $wfs11Cap->xpath('/wfs:WFS_Capabilities/@version');
				$myWfs->version = $myWfs->version[0];
				//identification part
				$myWfs->name = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Name');
				$myWfs->name = $myWfs->name[0];
				$myWfs->title = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Title');
				$myWfs->title = $this->stripEndlineAndCarriageReturn($myWfs->title[0]);
				$myWfs->summary = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Abstract');
				$myWfs->summary = $this->stripEndlineAndCarriageReturn($myWfs->summary[0]);
				$myWfs->fees = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Fees');
				$myWfs->fees = $myWfs->fees[0];
				$myWfs->accessconstraints = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:AccessConstraints');
				$myWfs->accessconstraints = $myWfs->accessconstraints[0];
				//provider part
				$myWfs->individualName = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:IndividualName');
				$myWfs->individualName = $myWfs->individualName[0];
				
				$myWfs->positionName = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:PositionName');
				$myWfs->positionName = $myWfs->positionName[0];

				$myWfs->providerName = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ProviderName');
				$myWfs->providerName = $myWfs->providerName[0];
				
				$myWfs->city = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:City');
				$myWfs->city =$myWfs->city[0];

				$myWfs->deliveryPoint = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:DeliveryPoint');
				$myWfs->deliveryPoint =$myWfs->deliveryPoint[0];

				$myWfs->administrativeArea = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:AdministrativeArea');
				$myWfs->administrativeArea =$myWfs->administrativeArea[0];

				$myWfs->postalCode = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:PostalCode');
				$myWfs->postalCode =$myWfs->postalCode[0];

				$myWfs->country = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:Country');
				$myWfs->country =$myWfs->country[0];

				$myWfs->voice = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Voice');
				$myWfs->voice =$myWfs->voice[0];

				$myWfs->facsimile = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Facsimile');
				$myWfs->facsimile =$myWfs->facsimile[0];

				$myWfs->electronicMailAddress = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:ElectronicMailAddress');
				$myWfs->electronicMailAddress =$myWfs->electronicMailAddress[0];


				//Operation Metadata Part

				$myWfs->getCapabilities = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Get/@xlinkhref');
				$myWfs->getCapabilities = html_entity_decode($myWfs->getCapabilities[0]);
				$e = new mb_notice($myWfs->getCapabilities);#get
				
				$myWfs->describeFeatureType = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="DescribeFeatureType"]/ows:DCP/ows:HTTP/ows:Get/@xlinkhref');
				$myWfs->describeFeatureType = html_entity_decode($myWfs->describeFeatureType[0]);#get
				
				$myWfs->getFeature = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:DCP/ows:HTTP/ows:Post/@xlinkhref');
				$myWfs->getFeature = html_entity_decode($myWfs->getFeature[0]);#post
				
				$myWfs->transaction = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="Transaction"]/ows:DCP/ows:HTTP/ows:Post/@xlinkhref');
				$myWfs->transaction = html_entity_decode($myWfs->transaction[0]);#post

				//get supported formats [mimetypes]
				$myWfs->wfsOutputFormatArray = $wfs11Cap->xpath('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:Parameter[@name="outputFormat"]/ows:Value');
				//debug:
				/*foreach($myWfs->wfsOutputFormatArray as $outputFormat){
					$e = new mb_exception("class_wfs_1_1_factory.php: wfs outputFormat: ".$outputFormat);
				}*/

				//get list of featuretypes
				$capFeatureTypes = $wfs11Cap->xpath('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType');
				$i = 1; //cause index of xml objects begin with 1
				foreach ($capFeatureTypes as $featureType) {
					//debug
					$e = new mb_notice("ft: ".$featureType->asXML());
					//check if the wfs namespaces are used in the featuretype object - as done by e.g. ArcGIS Server
					$wfsNamespaceUsed = false;
					$ftNamespaces = $featureType->getNameSpaces(true);
					foreach ($ftNamespaces as $key => $value) {
						if ($value == 'http://www.opengis.net/wfs') {
							$wfsNamespaceUsed = true;
						}
					}	
					if ($wfsNamespaceUsed) {
						 $featureType = $featureType->children('http://www.opengis.net/wfs');
					} 
					$featuretype_name = $this->stripEndlineAndCarriageReturn($featureType->Name[0]);
					$featuretype_title = $this->stripEndlineAndCarriageReturn($featureType->Title[0]);
					$featuretype_abstract = $this->stripEndlineAndCarriageReturn($featureType->Abstract[0]);
					//<DefaultSRS>urn:ogc:def:crs:EPSG::4326</DefaultSRS><OtherSRS>urn:ogc:def:crs:EPSG::4269</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::3978</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::3857</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::31466</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::25832</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::4258</OtherSRS>
					$featuretype_srs = $featureType->DefaultSRS[0];
					$otherSRSArray = $featureType->OtherSRS;
					$featuretype_crsArray = array();
					foreach ($otherSRSArray as $otherSRS) {
						$e = new mb_notice("other srs: ".$otherSRS);
						$featuretype_crsArray[] = $otherSRS;
						
					}
					//outputFormats
					/*<OutputFormats><Format>text/xml; subtype=gml/3.1.1</Format></OutputFormats>*/
					$featuretypeOutputFormats = array();
					$outputFormats = $featureType->OutputFormats->Format;
					foreach ($outputFormats as $outputFormat) {
						$featuretypeOutputFormats[] = $outputFormat;
						$e = new mb_exception("class_wfs_1_1_factory.php: add outputFormat for WFS: ".$outputFormat);
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
					//<ows:WGS84BoundingBox dimensions="2"><ows:LowerCorner>-9.16611817848171e+15 -3.4016616708962e+32</ows:LowerCorner><ows:UpperCorner>464605646503609 3.4016616708962e+32</ows:UpperCorner></ows:WGS84BoundingBox>
					$lowerCorner = $wfs11Cap->xpath('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType['.$i.']/ows:WGS84BoundingBox/ows:LowerCorner');
					$lowerCorner = $lowerCorner[0];
					$lowerCorner = explode(" ",$lowerCorner);

					$upperCorner = $wfs11Cap->xpath('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType['.$i.']/ows:WGS84BoundingBox/ows:UpperCorner');
					$upperCorner = $upperCorner[0];	
					$upperCorner = explode(" ",$upperCorner);
					
					$featuretype_latlon_minx = $lowerCorner[0];
					$featuretype_latlon_miny = $lowerCorner[1];
					$featuretype_latlon_maxx = $upperCorner[0];
					$featuretype_latlon_maxy = $upperCorner[1];

					try {
						$currentFeatureType = $this->createFeatureTypeFromUrlGet($myWfs, $featuretype_name, $featureTypeNsArray);
						if ($currentFeatureType !== null) {
							$currentFeatureType->name = $featuretype_name;
							$currentFeatureType->title = $featuretype_title;
							$currentFeatureType->summary = $featuretype_abstract;
							$currentFeatureType->srs = $featuretype_srs;
							$currentFeatureType->latLonBboxArray['minx'] = $featuretype_latlon_minx;
							$currentFeatureType->latLonBboxArray['miny'] = $featuretype_latlon_miny;
							$currentFeatureType->latLonBboxArray['maxx'] = $featuretype_latlon_maxx;
							$currentFeatureType->latLonBboxArray['maxy'] = $featuretype_latlon_maxy;
							$currentFeatureType->crsArray = $featuretype_crsArray;
							$currentFeatureType->featuretypeOutputFormatArray = $featuretypeOutputFormats;
							$currentFeatureType->metadataUrlArray = $featuretype_metadataUrl;
							$myWfs->addFeatureType($currentFeatureType);
						}
					}
					catch (Exception $e) {
						$e = new mb_exception("Failed to load featuretype " . $featuretype_name);
					}
					$i++;

				}
			}
			
			if (!$myWfs->title) {
				$myWfs->title = "Untitled";
			}
			return $myWfs;
		}
		catch (Exception $e) {
			$e = new mb_exception($e);
			return null;
		}
	}

	public function createFromDb ($id) {
		$myWfs = new Wfs_1_1();
		return parent::createFromDb($id, $myWfs);
	}
}
?>
