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
require_once(dirname(__FILE__)."/../classes/class_wfs_2_0.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_featuretype.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_xml_parser.php");

/**
 * Creates WFS 2.0 objects from a capabilities documents.
 *
 * @return Wfs_2_0
 */
class Wfs_2_0_Factory extends WfsFactory {

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
		if (isset($aWfs->auth)) {
			$xml = $this->post($aWfs->describeFeatureType, $postData, $aWfs->auth);
		} else {
			$xml = $this->post($aWfs->describeFeatureType, $postData);
		}
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
			"VERSION=2.0.0",
			"REQUEST=DescribeFeatureType",
			"TYPENAME=" . urlencode($featureTypeName),
			"NAMESPACE=" . urlencode(
				"xmlns(" . $key . "=" . $nsUrl . ")"
		));

		$url = $aWfs->describeFeatureType .
			$aWfs->getConjunctionCharacter($aWfs->describeFeatureType) .
			implode("&", $paramArray);
		
		$xml = $this->get($url, $aWfs->auth);
		
		//parse result to see if it is a real featuretype description
		return $this->createFeatureTypeFromXml ($xml, $aWfs, $featureTypeName);
	}
	
	protected function createStoredQueryFromUrlGet ($aWfs, $listStoredQueries, $describeStoredQueries) {
		$paramArray = array(
				"SERVICE=WFS",
				"VERSION=2.0.0",
				"REQUEST=ListStoredQueries"
		);
	
		$url = $listStoredQueries .
			$aWfs->getConjunctionCharacter($listStoredQueries) .
			implode("&", $paramArray);
	
		$xml = $this->get($url, $aWfs->auth);
		
		#$e = new mb_notice("class_wfs_2_0_factory.php: Got following StoredQuery XML: ".$xml);
		
		$parser = new XMLParser();
		$parser->loadXMLFromString($xml);
		$parser->loadJsonSchemaFromString('{
		    "cmd": {
		        "addNamespaces": {
		            "wfs": "http://www.opengis.net/wfs/2.0"
		        },
		        "removeEmptyValues" : true
		    },
		    "storedQuery": {
		        "path": "/*/wfs:StoredQuery",
		        "data": {
		            "Id": "@id",
		            "Title" : "wfs:Title/text()",
		            "ReturnFeatureType" : "wfs:ReturnFeatureType/text()"
		        }
		    }
		}');
		
		$array = $parser->parse();
		
		$storedQueryArray = array();
		
		$m = 0;
		foreach($array['storedQuery'] as $storedQuery) {
			$storedQueryArray[$m]->id = $storedQuery['Id'];
			$storedQueryArray[$m]->title = $storedQuery['Title'];
			$storedQueryArray[$m]->returnFeaturetype = $storedQuery['ReturnFeatureType'];
				
			$storedQueryArray[$m]->description = $this->createSingleStoredQuery($describeStoredQueries, $storedQueryArray[$m]->id, $aWfs);
				
			$m++;
		}
		
		return $storedQueryArray;
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
		$e = new mb_notice("class_wfs_2_0_factory.php: Got following FeatureType XML: ".$xml);
		$xpath =  new DOMXpath($doc);
		$xpath->registerNamespace("xs","http://www.w3.org/2001/XMLSchema");

		//get list of all namespaces which are declared in schema element!
		/*$context = $doc->documentElement;
		foreach( $xpath->query('namespace::*', $context) as $node ) {
		    //echo $node->nodeValue, "\n";
		    $e = new mb_exception("classes/class_wfs_2_0_factory.php: namespace: " . $node->nodeValue);
		}*/
		
		// populate a Namespaces Hastable where we can use the namespace as a lookup for the prefix
		// and also keep a 
		$namespaces = array();
		$namespaceList = $xpath->query("//namespace::*");
		$targetNamespace = $doc->documentElement->getAttribute("targetNamespace");
		$targetNamespaceNode = null;
		$namespaceLookupList = [];
		foreach($namespaceList as $namespaceNode){
			$namespaces[$namespaceNode->nodeValue] = $namespaceNode->localName;
			$namespaceLookupList[] = $namespaceNode->localName;
			if($namespaceNode->nodeValue == $targetNamespace){
				$targetNamespaceNode = $namespaceNode;
			}
			//don't allow double entries - this maybe a parsing mistake
			if (!in_array($namespaceNode->localName, $namespaceLookupList)) {
				$newFeatureType->addNamespace($namespaceNode->localName, $namespaceNode->nodeValue);
			}
		}
	
		list($ftLocalname, $ftTypePrefix) = array_reverse(explode(":",$featureTypeName));
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
						$e = new mb_exception("classes/class_wfs_2_0_factory.php: Problem while parsing schema for featuretype ".$featureTypeName);
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
	
	protected function createSingleStoredQuery ($describeStoredQueryUrl, $storedQueryId, $aWfs) {
		$paramArray = array(
				"SERVICE=WFS",
				"VERSION=2.0.0",
				"REQUEST=DescribeStoredQueries",
				"STOREDQUERY_ID=" . $storedQueryId
		);
	
		$url = $describeStoredQueryUrl .
		$aWfs->getConjunctionCharacter($describeStoredQueryUrl) .
		implode("&", $paramArray);
	
		$xml = $this->get($url);
		
		//parse result to get storedQuery attributes
		$e = new mb_notice("class_wfs_2_0_factory.php: Got following StoredQuery DescribeStoredQueries XML: ".$xml);
		
		$parser = new XMLParser();
		$parser->loadXMLFromString($xml);
		$parser->loadJsonSchemaFromString('{
		    "cmd": {
		        "addNamespaces": {
		            "wfs": "http://www.opengis.net/wfs/2.0",
					"fes": "http://www.opengis.net/fes/2.0"
		        },
			"asArray": true,
	        "removeEmptyValues" : true
		    },
		    "StoredQueryDescription": {
		        "path": "/*/wfs:StoredQueryDescription",
		        "data": {
		            "Id": "@id",
		            "Title" : "wfs:Title/text()",
		            "Abstract" : "wfs:Abstract/text()",
				    "Parameter" : {
						"path": "wfs:Parameter",
						"data": {
							"name": "@name",
							"type": "@type"
						}
					},
					"QueryExpressionText" : {
						"path": "wfs:QueryExpressionText",
						"data": {
							"isPrivate": "@isPrivate",
							"language": "@language",
							"returnFeatureTypes": "@returnFeatureTypes",
							"query" : {
								"path": "wfs:Query",
								"data": {
									"srsName": "@srsName",
									"typeNames": "@typeNames",
									"filter": ["fes:Filter", "raw"]
								}
							}
						}
					}
		        }
		    }
		}');
		
		$array = $parser->parse();
		$queryAttr = $array['StoredQueryDescription'];
		
		return $queryAttr;
	}
		
	/**
	 * Creates a WFS 2.0 object from a capabilities document.
	 *
	 * @return Wfs_2_0
	 * @param $xml String
	 */
	public function createFromXml ($xml, $auth=false) {
		try {
			$myWfs = new Wfs_2_0();
			$admin = new administration();
			$myWfs->getCapabilitiesDoc = $admin->char_encode($xml);
			$myWfs->id = $this->createId();
			if (!$auth) {
				$e = new mb_notice("class_wfs_2_0_factory.php - createFromXml - no athentication info given!");
			}
			$myWfs->auth = $auth; //always!
			//Ticket #8491: Fix for otherCRS-Array declaration
			//$featuretype_crsArray = array();
			try {
//				$xml = str_replace('xlink:href', 'xlinkhref', $xml);
				#http://forums.devshed.com/php-development-5/simplexml-namespace-attributes-problem-452278.html
				#http://www.leftontheweb.com/message/A_small_SimpleXML_gotcha_with_namespaces

//				$wfs20Cap = new SimpleXMLElement($xml);
//				if ($wfs20Cap === false) {
//					foreach(libxml_get_errors() as $error) {
//                        $e = new mb_exception($error->message);
//                    }
//					throw new Exception('Cannot parse WFS 2.0.0 Capabilities!');
//				}
                $wfs20Cap = new DOMDocument();
                if (!$wfs20Cap->loadXML($xml)) {
                    throw new Exception("Cannot parse WFS 2.0.0 Capabilities!");
                }
			}
			catch (Exception $e) {
    				$e = new mb_exception($e->getMessage());
			}	

			if ($wfs20Cap !== false) {
//				//read all relevant information an put them into the mapbender wfs object
//				//xmlns="http://www.opengis.net/wfs"
//				//Setup default namespace
//				$namespaces = $wfs20Cap->getNamespaces(true);
//				if(isset($namespaces[""])) { // if you have a default namespace
//					// register a prefix for that default namespace:
//					$wfs20Cap->registerXPathNamespace("wfs", $namespaces[""]);
//				} else {
//					$wfs20Cap->registerXPathNamespace("wfs", "http://www.opengis.net/wfs/2.0");
//				}
//				$wfs20Cap->registerXPathNamespace("ows", "http://www.opengis.net/ows");
//				$wfs20Cap->registerXPathNamespace("gml", "http://www.opengis.net/gml");
//				$wfs20Cap->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
//				$wfs20Cap->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
//				$wfs20Cap->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
//				$wfs20Cap->registerXPathNamespace("default", "http://www.opengis.net/wfs");
				//some debug
				//$e = new mb_notice("XML string from memory: ".$wfs20Cap->asXML());

                $xpath = new \DOMXPath($wfs20Cap);
                $xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
                foreach ($xpath->query('namespace::*', $this->doc->documentElement) as $node) {
                    $nsPrefix = $node->prefix;
                    $nsUri    = $node->nodeValue;
                    if ($nsPrefix == "" && $nsUri == "http://www.opengis.net/wfs/2.0") {
                        $nsPrefix = "wfs";
                    }
                    $xpath->registerNamespace($nsPrefix, $nsUri);
                }

				$myWfs->version = $this->getValue($xpath, '/wfs:WFS_Capabilities/@version', $wfs20Cap);
				$myWfs->name = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Name/text()', $wfs20Cap);
				$myWfs->title = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Title/text()', $wfs20Cap);
				$myWfs->summary = $this->stripEndlineAndCarriageReturn($this->getValue(
                    $xpath, '/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Abstract/text()', $wfs20Cap));
				$myWfs->fees = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:Fees/text()', $wfs20Cap);
				$myWfs->accessconstraints = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceIdentification/ows:AccessConstraints/text()', $wfs20Cap);
				//provider part
				$myWfs->individualName = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:IndividualName/text()', $wfs20Cap);

				$myWfs->positionName = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:PositionName/text()', $wfs20Cap);

				$myWfs->providerName = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ProviderName/text()', $wfs20Cap);

				$myWfs->city = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:City/text()', $wfs20Cap);

				$myWfs->deliveryPoint = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:DeliveryPoint/text()', $wfs20Cap);

				$myWfs->administrativeArea = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:AdministrativeArea/text()', $wfs20Cap);

				$myWfs->postalCode = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:PostalCode/text()', $wfs20Cap);

				$myWfs->country = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:Country/text()', $wfs20Cap);

				$myWfs->voice = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Voice/text()', $wfs20Cap);

				$myWfs->facsimile = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Facsimile/text()', $wfs20Cap);

				$myWfs->electronicMailAddress = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:ServiceProvider/ows:ServiceContact/ows:ContactInfo/ows:Address/ows:ElectronicMailAddress/text()', $wfs20Cap);

				//Operation Metadata Part
				$myWfs->getCapabilities =  html_entity_decode($this->getValue($xpath, '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetCapabilities"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href', $wfs20Cap));

				$myWfs->describeFeatureType =  html_entity_decode($this->getValue($xpath, '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="DescribeFeatureType"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href', $wfs20Cap));
				
				$myWfs->getFeature =  html_entity_decode($this->getValue($xpath, '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="GetFeature"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href', $wfs20Cap));

				$myWfs->transaction =  html_entity_decode($this->getValue($xpath, '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation[@name="Transaction"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href', $wfs20Cap));
//get supported formats [mimetypes]
				$allowedValuesArray = $this->getValue($xpath, '/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Parameter[@name="outputFormat"]/ows:AllowedValues', $wfs20Cap);
				$wfsOutputFormatsArray = $xpath->query('./ows:Value', $allowedValuesArray);
				foreach ($wfsOutputFormatsArray as $allowedValue) {
					$outputFormat1 = $this->getValue($xpath, './text()', $allowedValue);
					$myWfs->wfsOutputFormatArray[] = $outputFormat1;
					$e = new mb_notice("class_wfs_2_0_factory.php: add outputFormat for WFS: ".$outputFormat1);
				}
				//get list of featuretypes
				$capFeatureTypes = $xpath->query('/wfs:WFS_Capabilities/wfs:FeatureTypeList/wfs:FeatureType', $wfs20Cap);
				$i = 1; //cause index of xml objects begin with 1
				foreach ($capFeatureTypes as $featureType) {
					//Ticket #8491: Fix for otherCRS-Array declaration
					$featuretype_crsArray = array();
					$featuretype_name = $this->stripEndlineAndCarriageReturn($this->getValue($xpath, './wfs:Name/text()', $featureType));
					$featuretype_title = $this->stripEndlineAndCarriageReturn($this->getValue($xpath, './wfs:Title/text()', $featureType));
					$featuretype_abstract = $this->stripEndlineAndCarriageReturn($this->getValue($xpath, './wfs:Abstract/text()', $featureType));
					//<DefaultSRS>urn:ogc:def:crs:EPSG::4326</DefaultSRS><OtherSRS>urn:ogc:def:crs:EPSG::4269</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::3978</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::3857</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::31466</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::25832</OtherSRS><OtherSRS>urn:ogc:def:crs:EPSG::4258</OtherSRS>
					$featuretype_srs = $this->getValue($xpath, './wfs:DefaultCRS/text()', $featureType);
					$otherCRSArray =  $xpath->query('./wfs:OtherCRS', $featureType);
					foreach ($otherCRSArray as $otherCRS) {
                        			$crs = $this->getValue($xpath, './text()', $otherCRS);
						//$e = new mb_exception("other crs: ".$crs);
						$featuretype_crsArray[] = $crs;
					}
					$featuretypeOutputFormatsArray = $xpath->query('./wfs:OutputFormats/wfs:Format', $featureType);
					foreach ($featuretypeOutputFormatsArray as $outputFormat) {
						$outputFormat1 = $this->getValue($xpath, './text()', $outputFormat);
						$featuretypeOutputFormats[] = $outputFormat1;
						$e = new mb_notice("class_wfs_2_0_factory.php: add outputFormat for WFS featuretype: ".$featuretype_name. " - format: ".$outputFormat1);
					}
					//<wfs:MetadataURL type="FGDC" format="text/xml">http://www.ogccatservice.com/csw.cgi?service=CSW&amp;version=2.0.0&amp;request=GetRecords&amp;constraintlanguage=CQL&amp;constraint="recordid=urn:uuid:4ee8b2d3-9409-4a1d-b26b-6782e4fa3d59"</wfs:MetadataURL>
					$metadataURLArray = $xpath->query('./wfs:MetadataURL', $featureType);
					$featuretype_metadataUrl = array();
					$i_mdu = 0;
					foreach ($metadataURLArray as $metadataURL) {
						//$e = new mb_exception("other srs: ".$otherSRS);
						$featuretype_metadataUrl[$i_mdu]->href = $this->getValue($xpath, './@xlink:href', $metadataURL);
						$e = new mb_notice("metadataurl: ".$featuretype_metadataUrl[$i_mdu]->href);
						$featuretype_metadataUrl[$i_mdu]->type = $this->getValue($xpath, './@type', $metadataURL);
						$e = new mb_notice("type: ".$featuretype_metadataUrl[$i_mdu]->type);
						$featuretype_metadataUrl[$i_mdu]->format = $this->getValue($xpath, './@format', $metadataURL);
						$e = new mb_notice("format: ".$featuretype_metadataUrl[$i_mdu]->format);
						$i_mdu++;
					}
					//<ows:WGS84BoundingBox dimensions="2"><ows:LowerCorner>-9.16611817848171e+15 -3.4016616708962e+32</ows:LowerCorner><ows:UpperCorner>464605646503609 3.4016616708962e+32</ows:UpperCorner></ows:WGS84BoundingBox>
                    $lowerCorner = explode(" ", $this->getValue($xpath, './ows:WGS84BoundingBox/ows:LowerCorner/text()', $featureType));

					$upperCorner = explode(" ",$this->getValue($xpath, './ows:WGS84BoundingBox/ows:UpperCorner/text()', $featureType));
					
					$featuretype_latlon_minx = $lowerCorner[0];
					$featuretype_latlon_miny = $lowerCorner[1];
					$featuretype_latlon_maxx = $upperCorner[0];
					$featuretype_latlon_maxy = $upperCorner[1];

					try {
						//Hm..hier wird srs und crsArray initialisiert..aber später
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
				
				//get list of wfs operations
				
				$capOperations = $xpath->query('/wfs:WFS_Capabilities/ows:OperationsMetadata/ows:Operation', $wfs20Cap);
				$wfsOperations = array();
				$listStoredQueriesUrl = "";
				$describeStoredQueriesUrl = "";
				$k = 1; //cause index of xml objects begin with 1
				foreach ($capOperations as $operation) {
					//debug
					#$e = new mb_notice("wfs operation: ".$operation->asXML());
					$wfsOperations[$k]->name = html_entity_decode($this->getValue($xpath, './@name', $operation));
					$wfsOperations[$k]->httpGet = html_entity_decode($this->getValue($xpath, './ows:DCP/ows:HTTP/ows:Get/@xlink:href', $operation));
					$wfsOperations[$k]->httpPost = html_entity_decode($this->getValue($xpath, './ows:DCP/ows:HTTP/ows:Post/@xlink:href', $operation));
					
					//get url for ListStoredQueries request to go further on with createStoredQueryListFromUrlGet
					if($wfsOperations[$k]->name == "ListStoredQueries") {
						$listStoredQueriesUrl = $wfsOperations[$k]->httpGet;
					}
					//get url for DescribeStoredQueries request to go further on with createStoredQueryListFromUrlGet
					if($wfsOperations[$k]->name == "DescribeStoredQueries") {
						$describeStoredQueriesUrl = $wfsOperations[$k]->httpGet;
					}
					
					$k++;
				}
				$myWfs->operationsArray = $wfsOperations;
				
				//check for StoredQueries
				try {
					$myWfs->storedQueriesArray = $this->createStoredQueryFromUrlGet($myWfs, $listStoredQueriesUrl, $describeStoredQueriesUrl);
					#if (count($storedQueries) > 0) {
					#	$myWfs->storedQueriesArray = $storedQueries;
						
						
					#}
				}
				catch (Exception $e) {
					$e = new mb_exception("Failed to load StoredQueries.");
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
		$myWfs = new Wfs_2_0();
		return parent::createFromDb($id, $myWfs);
	}

    protected function getValue($xpath, $xpathStr, $contextElm)
    {
        try {
            $elm = $xpath->query($xpathStr, $contextElm)->item(0);
            if(!$elm) {
                return null;
            }
            if ($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } else if ($elm->nodeType == XML_TEXT_NODE) {
                return $elm->wholeText;
            } else if ($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm;
            } else if ($elm->nodeType == XML_CDATA_SECTION_NODE) {
                return $elm->wholeText;
            } else {
                return null;
            }
        } catch (Exception $E) {
            return null;
        }
    }
}
?>
