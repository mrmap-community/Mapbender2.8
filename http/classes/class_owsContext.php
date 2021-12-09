<?php
# $Id: class_owsContext.php armin11 $
# http://www.mapbender2.org/index.php/class_owsContext.php
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
require_once(dirname(__FILE__)."/class_Uuid.php");
require_once(dirname(__FILE__)."/class_wmc.php");
/**
 * An OWS Context (OWS Context) class, based on the OGC OWS Context Conceptual Model
 * Version 1.0 - https://portal.opengeospatial.org/files/?artifact_id=55182
 */

class OwsContext {
	var $specReference; //mandatory
	var $language; //mandatory
	var $id; //mandatory
	var $title; //mandatory
	var $abstract; //[0..1]
	var $updateDate; //[0..1]
	var $author; //[0..*]
	var $publisher; //[0..1]
	var $creator; //[0..1] OwsContextResourceCreator
	var $rights; //[0..1]
	var $areaOfInterest; //[0..1]
	var $timeIntervalOfInterest; //[0..1]
	var $keyword; //[0..*]
	var $extension; //[0..*]
	//relations
	var $resource; //[0..*] OwsContextResource (ordered)
	var $resourceMetadata; //[0..*] MD_Metadata
	//internal
	var $version; //1.0
	
	public function __construct() {
		//mandatory
		$this->specReference = "";
		$this->language = "de";
		$this->id = new uuid();
		$this->title = "dummy title";
		$this->abstract = "dummy abstract";
		//arrays
		$this->author = array();
		$this->keyword = "";
		$this->extensions = array();
		//relations
		$this->resource = array();
		$this->resourceMetadata = array();
		//internal
		$this->version = "1.0";	
	}	
	
	public function setCreator($aCreator) {
		$this->creator = $aCreator;	
	}
	
	public function delCreator() {
		unset($this->creator);	
	}
	
	public function addResource($aResource) {
		array_push($this->resource, $aResource);	
	}
	
	public function updateResource($aResource, $resourceId) {
		$resourcePos = $this->getResourcePosById($resourceId);
		if ($resourcePos !== false) {
			$this->resource[$resourcePos] = $aResource;
			return true;
		} else {
			return false;
		}	
	}
	
	public function getResourceById($resourceId) {
		foreach ($this->resource as $resource) {
			if ($resource->id == $resourceId) {
				return $resource;
			}
		}
		return false;	
	}
	
	public function getResourcePosById($resourceId) {
		$resourceIndex = 0;
		foreach ($this->resource as $resource) {
			if ($resource->id == $resourceId) {
				return $resourceIndex;
			}
			$resourceIndex++;
		}
		return false;	
	}	
	
	public function export($outputFormat) {
		switch ($outputFormat) {
			case "atom": 
				return $this->export2atom();
			break;
			case "json":
			    return $this->export2json();
			break;
		}
	}
	
	public function export2json() {
	    $owsContextJsonObject = new stdClass();
	    $owsContextJsonObject->type = "FeatureCollection";
	    $owsContextJsonObject->id = "mapbenderPath/wmc/{id}";
	    $properties = new stdClass();
	    $properties->lang = "de";
	    $properties->title = $this->title;
	    $properties->subtitle = $this->abstract;
	    $properties->updated = "";
	    $properties->authors = array("name" => $this->author[0]['name'], "email" => $this->author[0]['email']);
	    $properties->publisher = "";
	    $properties->generator = array("title" => $this->creator->creatorApplication->title, "uri" => $this->creator->creatorApplication->uri, "version" => $this->creator->creatorApplication->version);
	    $properties->display = array("pixelWidth" => (integer)$this->creator->creatorDisplay->pixelWidth, "pixelHeight" => (integer)$this->creator->creatorDisplay->pixelHeight, "mmPerPixel" => $this->creator->creatorDisplay->mmPerPixel);
	    
	    
	    $properties->links->profiles = array("http://www.opengis.net/spec/owc-geojson/1.0/req/core");
	    $owsContextJsonObject->properties = $properties;
	    $features = array();
	    
	    foreach ($this->resource as $resource) {
	        $feature = new stdClass();
	        $feature->type = "Feature";
	        
	        $properties = new stdClass();
	        $properties->title = (string)$resource->title;
	        $properties->abstract = (string)$resource->abstract;
	        $properties->updated = "";
	        
	        $properties->links->previews = array("href" => $resource->preview[0], "type" => "image/jpeg", "length" => 100 , "title" => "Preview for Layer XY");
	        	        
	        
	        $feature->offerings = array();
	        
	        //kml
	        
	        //wms
	        foreach ($resource->offering as $offering) {
	            $jsonOffering = new stdClass();
	            $jsonOffering->code = $offering->code;
	            foreach ($offering->operation as $operation) {
	                $jsonOffering->operations[] = array("code" => $operation->code, "method" => $operation->method, "type" => $operation->type, "href" => (string)$operation->href);
	                
	            }
	            $feature->offerings[] = $jsonOffering;
	        }
	        
	        $properties->minScaleDenominator = (double)$resource->minScaleDenominator;
	        $properties->maxScaleDenominator = (double)$resource->maxScaleDenominator;
	        
	        $properties->folder = $resource->folder;
	        $feature->properties = $properties;
	        //wms getcapabilities
	        
	        //wms getmap
	        //legend
	        //wfs...
	        //
	        $features[] = $feature;
	        
	        
	    }
	    if (count($features > 1)) {
	        $owsContextJsonObject->features = $features;  
	    }
	    return (json_encode($owsContextJsonObject));
	    
	}
	public function export2atom() {
		//Initialize XML document
		$owsContextDoc = new DOMDocument('1.0');
		$owsContextDoc->encoding = 'UTF-8';
		$owsContextDoc->preserveWhiteSpace = false;
		$owsContextDoc->formatOutput = true;
		$atomFeed = $owsContextDoc->createElementNS('http://www.w3.org/2005/Atom', 'feed');
		$atomFeed = $owsContextDoc->appendChild($atomFeed);
		$atomFeed->setAttribute("xmlns:owc", "http://www.opengis.net/owc/1.0");
		$atomFeed->setAttribute("xmlns:dc", "http://purl.org/dc/elements/1.1/");
		$atomFeed->setAttribute("xmlns:georss", "http://www.georss.org/georss");
		$atomFeed->setAttribute("xmlns:gml", "http://www.opengis.net/gml");
		//$atomFeed->setAttribute("xmlns:gco", "http://www.isotc211.org/2005/gco");
		//$atomFeed->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
		$atomFeed->setAttribute("xml:lang", $this->language);
		//part for feed
		$feedTitle = $owsContextDoc->createElement("title");
		$feedTitleText = $owsContextDoc->createTextNode($this->title);
		$feedTitle->appendChild($feedTitleText);
		$atomFeed->appendChild($feedTitle);
		//mandatory link with reference to profile
		$profileLink = $owsContextDoc->createElement("link");
		$profileLink->setAttribute("rel", "profile");
		$profileLink->setAttribute("href", "http://www.opengis.net/spec/owc-atom/1.0/req/core");
		$profileLink->setAttribute("title", _mb("This file is compliant with version 1.0 of OGC Context"));
		$atomFeed->appendChild($profileLink);
		//mandatory id
		$feedId = $owsContextDoc->createElement("id");
		$feedIdText = $owsContextDoc->createTextNode($this->id);
		$feedId->appendChild($feedIdText);
		$atomFeed->appendChild($feedId);
                //subtitle
		$feedSubTitle = $owsContextDoc->createElement("subtitle");
		$feedSubTitle->setAttribute("type", "html");
		$feedSubTitleText = $owsContextDoc->createTextNode($this->abstract);
		$feedSubTitle->appendChild($feedSubTitleText);
		$atomFeed->appendChild($feedSubTitle);
		//mandatory updateDate
		$feedUpdated = $owsContextDoc->createElement("updated");
		$feedUpdatedText = $owsContextDoc->createTextNode($this->updateDate);
		$feedUpdated->appendChild($feedUpdatedText);
		$atomFeed->appendChild($feedUpdated);
		//mandatory author fields - if not given in each entry
		$feedAuthor = $owsContextDoc->createElement("author");
		$feedAuthorName = $owsContextDoc->createElement("name");
		$feedAuthorNameText = $owsContextDoc->createTextNode($this->author[0]['name']);
		$feedAuthorEmail = $owsContextDoc->createElement("email");
		$feedAuthorEmailText = $owsContextDoc->createTextNode($this->author[0]['email']);
		$feedAuthorName->appendChild($feedAuthorNameText);
		$feedAuthorEmail->appendChild($feedAuthorEmailText);
		$feedAuthor->appendChild($feedAuthorName);
		$feedAuthor->appendChild($feedAuthorEmail);
		$atomFeed->appendChild($feedAuthor);
                //example from http://schemas.opengis.net/owc/1.0/examples/sea_ice_extent_01.atom
                //dc:publisher
                //generator
		$feedGenerator = $owsContextDoc->createElement("generator");
		$feedGenerator->setAttribute("uri", $this->creator->creatorApplication->uri);
		$feedGenerator->setAttribute("version", $this->creator->creatorApplication->version);
                $feedGeneratorText = $owsContextDoc->createTextNode($this->creator->creatorApplication->title);
                $feedGenerator->appendChild($feedGeneratorText);
                $atomFeed->appendChild($feedGenerator);
		//owc:display
		$feedOwcDisplay =  $owsContextDoc->createElement("owc:display");
		//
		$feedOwcDisplayPixelWidth =  $owsContextDoc->createElement("owc:pixelWidth");
		$feedOwcDisplayPixelWidthText =  $owsContextDoc->createTextNode($this->creator->creatorDisplay->pixelWidth);
		$feedOwcDisplayPixelWidth->appendChild($feedOwcDisplayPixelWidthText);
		$feedOwcDisplay->appendChild($feedOwcDisplayPixelWidth);
		//
		$feedOwcDisplayPixelHeight =  $owsContextDoc->createElement("owc:pixelHeight");
		$feedOwcDisplayPixelHeightText =  $owsContextDoc->createTextNode($this->creator->creatorDisplay->pixelHeight);
		$feedOwcDisplayPixelHeight->appendChild($feedOwcDisplayPixelHeightText);
		$feedOwcDisplay->appendChild($feedOwcDisplayPixelHeight);
		//
		$feedOwcDisplayMmPerPixel =  $owsContextDoc->createElement("owc:mmPerPixel");
		$feedOwcDisplayMmPerPixelText =  $owsContextDoc->createTextNode($this->creator->creatorDisplay->mmPerPixel);
		$feedOwcDisplayMmPerPixel->appendChild($feedOwcDisplayMmPerPixelText);
		$feedOwcDisplay->appendChild($feedOwcDisplayMmPerPixel);
                $atomFeed->appendChild($feedOwcDisplay);
		//rights
                //category
		//optional areaOfInterest
		if (isset($this->areaOfInterest) && $this->areaOfInterest !== "") {
			$feedAreaOfInterest = $owsContextDoc->createElement("georss:where");
			//parse xml as simple xml object and add it to dom
			$fragment = $owsContextDoc->createDocumentFragment();
			$fragment->appendXml($this->areaOfInterest);
			$feedAreaOfInterest->appendChild($fragment);
			//$feedAreaOfInterestText = $owsContextDoc->createTextNode($this->areaOfInterest);
			//$feedAreaOfInterest->appendChild($feedAreaOfInterestText);
			$atomFeed->appendChild($feedAreaOfInterest);
		}
		foreach ($this->resource as $resource) {
			$feedEntry = $owsContextDoc->createElement("entry");
			//title
			$feedEntryTitle = $owsContextDoc->createElement("title");
			$feedEntryTitleText = $owsContextDoc->createTextNode($resource->title);
			$feedEntryTitle->appendChild($feedEntryTitleText);
			$feedEntry->appendChild($feedEntryTitle);
			//abstract
			$feedEntryAbstract = $owsContextDoc->createElement("abstract");
			$feedEntryAbstractText = $owsContextDoc->createTextNode($resource->abstract);
			$feedEntryAbstract->appendChild($feedEntryAbstractText);
			$feedEntry->appendChild($feedEntryAbstract);
			if (count($resource->preview) >= 1) {
				$feedPreview = $owsContextDoc->createElement("link");
				$feedPreview->setAttribute("rel", "icon");
				$feedPreview->setAttribute("type", "image/jpeg");
				$feedPreview->setAttribute("length", "12345");
				$feedPreview->setAttribute("title", "Preview for layer X");
				$feedPreview->setAttribute("href", $resource->preview[0]);
				$feedEntry->appendChild($feedPreview);
			}
			foreach ($resource->offering as $offering) {
					$resourceOffering = $owsContextDoc->createElement("offering");
					$resourceOffering->setAttribute("code", $offering->code);
					foreach ($offering->operation as $operation) {
						$offeringOperation = $owsContextDoc->createElement("operation");
						$offeringOperation->setAttribute("method", $operation->method);
						$offeringOperation->setAttribute("code", $operation->code);
						$offeringOperation->setAttribute("href", $operation->href);
						$resourceOffering->appendChild($offeringOperation);
					}
					$feedEntry->appendChild($resourceOffering);
			}
			if ($resource->active == true) {
				$activeCategory = $owsContextDoc->createElement("category");
				$activeCategory->setAttribute("scheme", "http://www.opengis.net/spec/owc/active");
				$activeCategory->setAttribute("term", "true");
				$feedEntry->appendChild($activeCategory);
			}
			if (isset($resource->minScaleDenominator)) {
				$owcMinScaleDenominator = $owsContextDoc->createElement("owc:minScaleDenominator");
				$owcMinScaleDenominatorText = $owsContextDoc->createTextNode($resource->minScaleDenominator);
				$owcMinScaleDenominator->appendChild($owcMinScaleDenominatorText);
				$feedEntry->appendChild($owcMinScaleDenominator);
			}
			if (isset($resource->maxScaleDenominator)) {
				$owcMaxScaleDenominator = $owsContextDoc->createElement("owc:maxScaleDenominator");
				$owcMaxScaleDenominatorText = $owsContextDoc->createTextNode($resource->maxScaleDenominator);
				$owcMaxScaleDenominator->appendChild($owcMaxScaleDenominatorText);
				$feedEntry->appendChild($owcMaxScaleDenominator);
			}
			$atomFeed->appendChild($feedEntry);
		}
		return $owsContextDoc->saveXML();
	}

	public function readFromWmc($wmcXml) {
	
	}
	
	public function getContextLayerPath($WMCDoc, $layerPath, $layerId) {
	
	    $parent = $WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[Extension/mapbender:layer_pos='" . $layerId . "']/Extension/mapbender:layer_parent");
	    
	    $e = new mb_exception("/wmc:ViewContext/wmc:LayerList/wmc:Layer[Extension/mapbender:layer_pos='" . $layerId . "']/Extension/mapbender:layer_parent"." - - "."first parent: ".json_encode($parent)." - type : ".gettype($parent) );
	    
	    /*while ((string)$parent != "") {
	        $layerPath .= $parent . "/" . $layerPath;
	        $parent = $WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[Extension/mapbender:layer_pos='".$parent."']/Extension/mapbender:layer_parent");
	    }*/
	    return $layerPath;
	}
	
	public function readFromInternalWmc($wmcId) {
  		$myWmc = new wmc();
  		$myWmc->createFromDb($wmcId);
		//read title
		$this->title = $myWmc->wmc_title;
		$this->abstract = $myWmc->wmc_abstract;
		$this->id = $myWmc->uuid;
		$this->updateDate = date(DATE_ATOM,$myWmc->timestamp); 
		$this->author[0]['name'] = $myWmc->wmc_contactperson;
		$this->author[0]['email'] = $myWmc->wmc_contactemail;
		//TODO build publisher either from owner or primary group if available for this user!
		//build georss:where from extent given in special srs
		//minx miny, maxx miny, maxx maxy, minx maxy, minx miny
		$minx = $myWmc->wmc_extent->minx;
		$miny = $myWmc->wmc_extent->miny;
		$maxx = $myWmc->wmc_extent->maxx;
		$maxy = $myWmc->wmc_extent->maxy;
		$sql = "SELECT ST_ASGML(3, ST_TRANSFORM(ST_GeomFromText('POLYGON(( $minx $miny , $maxx $miny , $maxx $maxy , $minx $maxy , $minx $miny ))',".str_replace('EPSG:','',$myWmc->wmc_srs)."),4326),15,16);";
		$res = db_query($sql);
		$georssGmlPolygon = db_fetch_row($res);	
		$this->areaOfInterest = $georssGmlPolygon[0];
		//define creator
		$creator = new OwsContextResourceCreator();
		$creator->creatorApplication = new OwsContextResourceCreatorApplication();
$creator->creatorApplication->title = "testgeoportal";
$creator->creatorApplication->uri = "http://localhost/mapbender";
$creator->creatorApplication->version = "2.8_trunk";
                $creatorDisplay = new OwsContextResourceCreatorDisplay();
                $creatorDisplay->pixelWidth = $myWmc->mainMap->getWidth();
                $creatorDisplay->pixelHeight = $myWmc->mainMap->getHeight();
                $creatorDisplay->mmPerPixel = 0.28;
		$creator->creatorDisplay = $creatorDisplay;
		//add creator to object
		$this->setCreator($creator);

		//get the layers as single resources
		libxml_use_internal_errors(true);
		try {
			$WMCDoc = simplexml_load_string($myWmc->toXml());
			//$WMCDoc = simplexml_load_string(str_replace("xlink:href","xlinkhref",$myWmc->toXml()));
			if ($WMCDoc === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_owsContext.php:".$error->message);
    				}
				throw new Exception("class_owsContext.php:".'Cannot parse WMC XML!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_owsContext.php:".$e->getMessage());
			return false;
		}
		//register relevant namespaces
		$WMCDoc->registerXPathNamespace("wmc","http://www.opengis.net/context");
		$WMCDoc->registerXPathNamespace("mapbender","http://www.mapbender.org/context");
		$WMCDoc->registerXPathNamespace("xlink","http://www.w3.org/1999/xlink");
		//pull out List of layer objects
		$layerList = $WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer");
		//pull all available server ids from mapbenders extension

		//get relevant urls from database 
		$e = new mb_notice("classes/class_owsContext.php: number of all layers found in WMC: ".count($layerList));
		$path = "/";
		$pathArray = array();
        $serviceId = 0;
		/*
		* each service has an empty value as layer_parent element
		* at service level, the order extents to max layers 
		* changing order is only possible within its own level
		*/
		
		foreach ($layerList as $layer) {      
			//pull relevant information out of xml snippet
            $version = $layer->Server->attributes()->version;
			$getmap = $layer->Server->OnlineResource->attributes("xlink", true)->href;
			//check if featureInfo active
			$owsContextResource = new OwsContextResource();
			$owsContextResource->title = $layer->Title;
			$owsContextResource->abstract = $layer->Abstract;
			//add offering
			$owsContextResourceOffering = new OwsContextResourceOffering();
			$owsContextResourceOffering->code = "http://www.opengis.net/spec/owc-atom/1.0/req/wms";
			//add operation for getcapabilities
			$owsContextResourceOfferingOperation = new OwsContextResourceOfferingOperation();
			$owsContextResourceOfferingOperation->code = "GetCapabilities";
			$owsContextResourceOfferingOperation->method = "GET";
			$owsContextResourceOfferingOperation->type = "application/xml";
			//TODO: use operations from database if wms id is given in wmc
			
			$owsContextResourceOfferingOperation->href = $getmap;
			$owsContextResourceOffering->addOperation($owsContextResourceOfferingOperation);
			$owsContextResource->addOffering($owsContextResourceOffering);
			//active
			if ($layer->attributes()->hidden == "0") {
				$owsContextResource->active = true;
			}
			//scale
			if (isset($layer->Extension->children('http://www.mapbender.org/context')->gui_minscale)) {
				$owsContextResource->minScaleDenominator = $layer->Extension->children('http://www.mapbender.org/context')->gui_minscale;
				
			}
			if (isset($layer->Extension->children('http://www.mapbender.org/context')->gui_maxscale)) {
				$owsContextResource->maxScaleDenominator = $layer->Extension->children('http://www.mapbender.org/context')->gui_maxscale;
				
			}
			if (isset($layer->Extension->children('http://www.mapbender.org/context')->layer_id)) {
				$owsContextResource->addPreview(MAPBENDER_PATH."/geoportal/mod_showPreview.php?resource=layer&id=".$layer->Extension->children('http://www.mapbender.org/context')->layer_id);
				
			}
			//build path
			/* 
			* Part for extracting the hierarchy path elements from mapbenders wmc extension
			*/
			$e = new mb_notice("classes/class_owsContext.php layer_path (before) = " . '/' . implode('/', $pathArray));
			$e = new mb_notice("classes/class_owsContext.php layer:  " . (string)$layer->Title . "(".(string)$layer->Extension->children('http://www.mapbender.org/context')->layer_pos.")");
			//Begin with index 1 instead of 0 which is used for every wms in mapbender
			if ((string)$layer->Extension->children('http://www.mapbender.org/context')->layer_parent == '') {
 				$serviceId++;
				//layer_pos will be 0 - initialize array again!
				$pathArray = array();
				$pathArray[] = (string)$serviceId;
				$e = new mb_notice("classes/class_owsContext.php: Layer is service layer and will be given an id!");
			} else {
				//if a parent of a layer has the id of the last pathArray element, the path will be simply extended (added)
				if ((string)$layer->Extension->children('http://www.mapbender.org/context')->layer_parent == end($pathArray)) {
					$pathArray[] = (string)$layer->Extension->children('http://www.mapbender.org/context')->layer_pos;
				} else {
					//search index of parent in array for this service and delete all further ones
					$valueToFind = (string)$layer->Extension->children('http://www.mapbender.org/context')->layer_parent;
					//If the parent is a service level layer
					if ($valueToFind == '0') {
						$valueToFind = (string)$serviceId;
						//go up to service level
						$pathArray = array($valueToFind);
					} else {
						//Make a copy of the pathArray for searching
						$e = new mb_notice("classes/class_owsContext.php value to find:  ".$valueToFind);
						$e = new mb_notice("classes/class_owsContext.php in whole path:  " . '/' . implode('/', $pathArray));
						$searchArray = array_slice($pathArray, 1, count($pathArray) - 1 , true);
						$e = new mb_notice("classes/class_owsContext.php searchArray:  " . '/' . implode('/', $searchArray));
						$arrayKey = array_search($valueToFind, $searchArray);
						$e = new mb_notice("classes/class_owsContext.php - found key: " . $arrayKey . " for value " . $valueToFind ."" );
						//Reduce path to found level where to add new layer from parent relation
						array_splice($pathArray, $arrayKey + 1);
					}					
					$pathArray[] = (string)$layer->Extension->children('http://www.mapbender.org/context')->layer_pos;
				}
			}
			$e = new mb_notice("classes/class_owsContext.php layer_path (after) = " . '/' . implode('/', $pathArray));
			/*
			* End of hierarchy extraction part
			*/
			$owsContextResource->folder = '/' . implode('/', $pathArray);

			$this->addResource($owsContextResource);
			unset($owsContextResource);
		}
	}
	
}

class OwsContextResource {
	var $id; //mandatory CharacterString
	var $title; //mandatory CharacterString
	var $abstract; //[0..1] CharacterString
	var $updateDate; //[0..1] - TM_Date
	var $author; //[0..*] ? - really * CharacterString
	var $publisher; //[0..1] CharacterString
	var $rights; //[0..1] CharacterString
	var $geospatialExtent; //[0..1] GM_Envelope
	var $temporalExtent; //[0..1] TM_GeometricPrimitive
	var $contentDescription; //[0..1] Any
	var $preview; //[0..*] URI
	var $contentByRef; //[0..*] URI
	var $offering; //[0..*] OwsContextResourceOffering
	var $active; //[0..1] Boolean
	var $keyword; //[0..*]
	var $maxScaleDenominator; //[0..1] Double
	var $minScaleDenominator; //[0..1] Double
	var $folder; //[0..1]
	var $extension; //[0..*] Any	
	//relations	
	var $resourceMetadata; //[0..*] MD_Metadata		
	
	public function __construct() {
		//mandatory
		$this->id = new uuid();
		$this->id = "dummy title";
		//arrays
		$this->author = array();
		$this->preview = array();
		$this->contentByRef = array();
		$this->offering = array();
		$this->keyword = "";
		$this->extension = array();
		$this->resourceMetadata = array();
	}	
	
	public function addOffering($aOffering) {
		array_push($this->offering, $aOffering);	
	}
	
	public function addPreview($aPreview) {
		array_push($this->preview, $aPreview);	
	}
}

class OwsContextResourceCreator {
	var $creatorApplication; //[0..1] OwsContextResourceCreatorApplication
	var $creatorDisplay; //[0..1] OwsContextResourceCreatorDisplay
	var $extension; //[0..*] Any	
	
	public function __construct() {
		$this->extension = array();
	}
}

class OwsContextResourceCreatorApplication {
	var $title; //[0..1]
	var $uri; //[0..1] URI
	var $version; //[0..1]
	
	public function __construct() {
	}
}

class OwsContextResourceCreatorDisplay {
	var $pixelWidth; //[0..1] integer
	var $pixelHeight; //[0..1] integer
	var $mmPerPixel; //[0..1] double	
	var $extension; //[0..*] Any	
	
	public function __construct() {
		//arrays
		$this->extension = array();
	}
}

class OwsContextResourceOffering {
	var $code; //mandatory URI
	var $operation; //[0..*] OwsContextResourceOfferingOperation
	var $content; //[0..*] OwsContextResourceOfferingContent
	var $styleSet; //[0..*] OwsContextResourceOfferingStyleSet
	var $extension; //[0..*] Any
		
	public function __construct() {
		//mandatory
		$this->code = "dummy code";		
		//arrays
		$this->operation = array();
		$this->content = array();
		$this->styleSet = array();
		$this->extension = array();
	}	

	public function addOperation($aOperation) {
		array_push($this->operation, $aOperation);	
	}

	public function addStyleSet($aStyleSet) {
		array_push($this->styleSet, $aStyleSet);	
	}



}

class OwsContextResourceOfferingOperation {
	var $code; //mandatory
	var $method; //mandatory
	var $type; //mandatory
	var $requestURL; //mandatory URI
	var $request; //[0..1] OwsContextResourceOfferingContent
	var $result; //[0..1] Any
	var $extension; //[0..*] Any
	
	public function __construct() {
		//mandatory
		$this->code = "dummy code";	
		$this->method = "dummy method";	
		$this->type = "dummy type";
		$this->requestURL = "dummy requestURL";
		//arrays
		$this->extension = array();
	}
}

class OwsContextResourceOfferingContent {
	var $type; //mandatory
	var $URL; //[0..1] URI
	var $content; //[0..1] Any
	var $extension; //[0..*] Any

	public function __construct() {
		//mandatory
		$this->type = "dummy type";	
		//arrays
		$this->extension = array();
	}
}

class OwsContextResourceOfferingStyleSet {
	var $name; //mandatory
	var $title; //mandatory
	var $abstract; //[0..1]
	var $default; //[0..1]
	var $legendURL; //[0..*] URI
	var $content; //[0..1] OwsContextResourceOfferingContent
	var $extension; //[0..*] Any

	public function __construct() {
		//mandatory
		$this->name = "dummy name";
		$this->title = "dummy title";
		//arrays
		$this->legendURL = array();
		$this->extension = array();
	}
}

?>
