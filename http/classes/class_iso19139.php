<?php
# http://www.mapbender.org/index.php/class_iso19139.php
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
require_once(dirname(__FILE__)."/../../conf/isoMetadata.conf");
require_once(dirname(__FILE__)."/class_connector.php");
require_once(dirname(__FILE__)."/class_Uuid.php");
require_once(dirname(__FILE__)."/class_crs.php");
require_once(dirname(__FILE__) . "/class_propagateMetadata.php");
require_once dirname(__FILE__) . "/../../tools/wms_extent/extent_service.conf";
class Iso19139 {
	//values for handling apriori ows metadataurls
	var $fileIdentifier;
	var $createDate;
	var $changeDate;
	var $title;
	var $abstract;
	var $metadata;
	var $wgs84Bbox = array(); //minx, miny, maxx, maxy in EPSG:4326
	var $polygonalExtentExterior; //Prototype: Array of polygon exterior rings which are two dimensional arrays of coordinates $polygonalExtentExterior[j][i]['x'],  $polygonalExtentExterior[j][i]['y'] - j is the index of the polygon, i of the coordinate or pos
	//var $polygonalExtentExterior = boolean; //TODO maybe implemented somewhen, now only exterior (GML3) is supported, maybe we can use the GML classes themself?
	var $datasetId;
	var $datasetIdCodeSpace;
	var $keywords = array();
	var $keywordsThesaurusName = array();
	var $isoCategoryKeys = array();
	var $isoCategories = array();
	var $inspireCategories = array();
	var $customCategories = array();
	var $downloadLinks = array();  //store in db as json object!!!!
	var $transferSize;
	var $hierarchyLevel;
	var $tmpExtentBegin;
	var $tmpExtentEnd;
	var $refSystem;
	var $randomId;
	var $href;
	var $format;
	var $type;
	var $origin;
	var $owner;
	var $fkey_mb_group_id;
	var $harvestResult;
	var $harvestException;
	var $lineage;
	var $inspireTopConsistence; //db bool, 't' or 'f'
	var $inspireInteroperability; //db bool, 't' or 'f' - declaration if the provided data should be compliant with the interoperablity implementing rule
	var $inspireResulations; //array of actual inspire regulations which are relevant for this metadata representation (dataset/service) 
	var $spatialResType;
	var $spatialResValue;
	var $export2Csw; //db bool, 't' or 'f'
	var $mdProxy; //db boolean
	var $updateFrequency;
	var $dataFormat;
	var $inspireCharset;
	var $licenseSourceNote;
	var $licenseJson;
	var $previewImage;
	//the following attribute is specific to predefined terms of use as they are managed by the mapbender database - it is the identifier (fkey) of an entry in the termsofuse table
	var $termsOfUseRef;
	var $accessConstraints;
	var $fees;
	//Following two attributes are needed for generating the inspire monitoring information. They are normally not part of the iso19139!
	var $inspireWholeArea;
	var $inspireActualCoverage;
	//following parameter is specific to mapbender registry and steers the automatic generation of an INSPIRE Downloadservice for the given metadata 
	var $inspireDownload;
	var $linkAlreadyInDB; //bool
	var $fileIdentifierAlreadyInDB; //bool
	var $resourceResponsibleParty; //char
	var $resourceContactEmail; //char
    var $codeListUpdateFrequencyArray;
	var $searchable;
	//Following attributes are only for application metadata editor and they are used for managing/publishing metadata for internal applications !
	var $fkeyGuiId;
	var $fkeyWmcSerialId;
	var $fkeyMapviewerId;
	//Following attribute classifies if a minimum of given attributes are set to identify it as metadata
	var $xmlHasMinimalAttributes; //title, description, bbox are good values to be checked

	function __construct() {
		//initialize empty iso19139 object
		$this->fileIdentifier = "";
		$title->title = "empty iso19139 object title";
		$title->abstract = "empty iso19139 object abstract";
		$this->createDate = "1900-01-01";
		$this->changeDate = "1900-01-01";
		$this->metadata = "";
		$this->wgs84Bbox = array(-180.0,-90.0,180.0,90.0); //minx, miny, maxx, maxy in EPSG:4326
		$this->polygonalExtentExterior = null; //initialize as null, cause it may be empty
		$this->datasetId = "";
		$this->datasetIdCodeSpace = "";	
		$this->keywords = array();
		$this->keywordsThesaurusName = array();
		$this->isoCategoryKeys = array();
		//following information is specific to mapbender information model - they are identified by id!
		$this->isoCategories = array();
		$this->inspireCategories = array();
		$this->customCategories = array();
		$this->downloadLinks = array();
		//
		$this->hierarchyLevel = "dataset";
		$this->tmpExtentBegin = "1900-01-01";
		$this->tmpExtentEnd = "1900-01-01";
		$this->randomId = new Uuid();
		$this->owner = 0; //dummy entry for metadata owner - in case of metadataURL entries the owner of the corresponding service
		$this->fkey_mb_group_id = null;
		$this->href = "";
		$this->format = "";
		$this->type = "";
		$this->origin = "";
		$this->refSystem = "";
		$this->harvestResult = 0;
		$this->harvestException = "";
		$this->lineage = "";
		$this->inspireTopConsistence = "f";
		$this->inspireInteroperability = "f";
		$this->spatialResType = "";
		$this->spatialResValue = "";
		$this->export2Csw = "t";
		$this->mdProxy = "f";
		$this->updateFrequency = "";
		$this->dataFormat = "";
		$this->inspireCharset = "";
		$this->inspireWholeArea = 0;
		$this->inspireActualCoverage = 0;
		$this->inspireDownload = 0;
		$this->linkAlreadyInDB = false;
		$this->fileIdentifierAlreadyInDB = false;
		$this->transferSize = null;
		$this->licenseSourceNote = null;
		$this->termsOfUseRef = null;
		$this->accessConstraints = null;
		$this->fees = null;
		$this->licenseJson = null;
		$this->resourceResponsibleParty = null;
		$this->resourceContactEmail = null;
		$this->previewImage = null;
		$this->codeListUpdateFrequencyArray = $codeListUpdateFrequencyArray;
		//read inspire legislation info from json file - enhancement for INSPIRE - maybe to be defined as an extension of the class in further developments!
		//source: http://inspire.ec.europa.eu/inspire-legislation/
		$inspireLegislationConf = realpath(dirname(__FILE__) ."/../../conf/inspire_legislation.json");
		$this->inspireLegislation = json_decode(file_get_contents($inspireLegislationConf));
		$this->inspireRegulations = $this->getRelevantInspireRegulations();
		//default the searchability in the mapbender catalogue to true
		$this->searchable = 't';
		$this->fkeyGuiId = null;
		$this->fkeyWmcSerialId = null;
		$this->fkeyMapviewerId = null;
		$this->xmlHasMinimalAttributes = false;
	}
	
   //TODO: Following function is only needed til php 5.5 - after upgrade to debian 8 it is obsolet - see also class_syncCkan.php!
   public function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

	public function getRelevantInspireRegulations($withAmendmentAndCorrigendum = true) {
		//use $this->hierarchyLevel and give back all relevant regulations (only the newest of each type) with their dates in the requested language
		$language = $this->inspireLegislation->default_language;
		$countInspireRegulations = 0;
		$regulations = array();
		if ($withAmendmentAndCorrigendum == true) {
			//$legislationTypeArray = array("legislation","legislation_amendment","legislation_corrigendum");
			$legislationTypeArray = array("legislation","legislation_amendment");
		} else {
			$legislationTypeArray = array("legislation");
		}
		//iterate over object and array !
		foreach ($this->inspireLegislation as $inspire_rules) {
			foreach ($inspire_rules as $regulation) { 
				if (in_array($regulation->type, $legislationTypeArray) && in_array($regulation->group, array("data_specifications","metadata","network_services"))) {
					if (in_array($this->hierarchyLevel, $regulation->subject)) {
						//check if already a regulation with this name exists in the array - if it is already there, check if the date is newer or older!
						$keyInArray = array_search($regulation->label->{$language}, $this->array_column($regulations, 'name'));//TODO []
						if ($keyInArray !== false) {
							$newDateTime = new dateTime($regulation->date);
							if ($newDateTime < $regulations[$keyInArray]['date']) {
								$regulations[$keyInArray]['date'] = $newDateTime;
							}
						} else {
							$regulations[$countInspireRegulations]['date'] = new dateTime($regulation->date);
							$regulations[$countInspireRegulations]['name'] = $regulation->label->{$language};
							$regulations[$countInspireRegulations]['type'] = $regulation->group;
							$countInspireRegulations++;
						}
					}
				}
			}
		}
		//debug output
		/*foreach ($regulations as $reg) {
			$e = new mb_exception("regulation date: ".$reg['date']->format('Y-m-d'));
			$e = new mb_exception("regulation: ".$reg['name']);
			$e = new mb_exception("regulation type: ".$reg['type']);
		}*/
		return $regulations;
	}

	public function removeGetRecordTag ($xml) {
		$regex = "#<csw:GetRecordByIdResponse .*?>#";
		$xml = preg_replace($regex,"",$xml);
		$regex = "#</csw:GetRecordByIdResponse>#";
		$xml = preg_replace($regex,"",$xml);
		return $xml;
	}
	
	public function createMapbenderMetadataFromXML($xml){
		//$this->metadata = $xml;
		//$this->metadata = $this->removeGetRecordTag($this->metadata);
		//$e = new mb_exception($this->metadata);
		libxml_use_internal_errors(true);
		try {
			$iso19139Xml = simplexml_load_string($xml);
			if ($iso19139Xml === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_Iso19139:".$error->message);
    				}
				throw new Exception("class_Iso19139:".'Cannot parse Metadata XML!');
				$this->metadata = <<<XML
				<mb:ExceptionReport xmlns:mb="http://www.mapbender.org/metadata/exceptionreport">
					<mb:Exception exceptionCode="NoApplicableCode">
						<mb:ExceptionText>ISO Metadata XML could not be parsed!</mb:ExceptionText>
				    </mb:Exception>
				</mb:ExceptionReport>
XML;
				$this->harvestResult = 0;
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_Iso19139:".$e->getMessage());
			return false;
		}
		//if parsing was successful
		if ($iso19139Xml !== false) {
			//built hashes for category mapping
			$topicCatHash = array();
			$sql = "SELECT md_topic_category_id, md_topic_category_code_en FROM md_topic_category";
			$res = db_query($sql);
			while ($row = db_fetch_array($res)){
				$topicCatHash[$row['md_topic_category_code_en']] = (integer)$row['md_topic_category_id'];
				//$e = new mb_exception("topicCatHash: ".$row['md_topic_category_code_en'] ." : ". $topicCatHash[$row['md_topic_category_code_en']] );	
			}
			//inspire
			$inspireCatHash = array();
			$sql = "SELECT inspire_category_id, inspire_category_code_en FROM inspire_category";
			$res = db_query($sql);
			while ($row = db_fetch_array($res)){
				$inspireCatHash[$row['inspire_category_code_en']] = (integer)$row['inspire_category_id'];
				//$e = new mb_exception("inspireCatHash: ".$row['inspire_category_code_en'] ." : ". $row['inspire_category_id'] );	
			}
			//custom
			//keywords - as text i custom category - special keywords of geoportal instance defined as keys!
			$customCatHash = array();
			$sql = "SELECT custom_category_id, custom_category_key FROM custom_category";
			$res = db_query($sql);
			while ($row = db_fetch_array($res)){
				$customCatHash[$row['custom_category_key']] = (integer)$row['custom_category_id'];
				//$e = new mb_exception("customCatHash: ".$row['custom_category_key'] ." : ". $row['custom_category_id'] );	
			}
			//add namespaces to xml if not given - how? - it is to late now - maybe they were given in the csw tag!
			//only parse the MD_Metadata part ;-)
			$iso19139Xml->registerXPathNamespace("gmd", "http://www.isotc211.org/2005/gmd");
			$iso19139Xml->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
			$iso19139Xml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			$iso19139Xml->registerXPathNamespace("srv", "http://www.isotc211.org/2005/srv");
			//$iso19139Xml->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
			$iso19139Xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
			$iso19139Xml->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
			$iso19139Xml->registerXPathNamespace("default", "");
			//TODO: use md_metadata element before xpath
			$e = new mb_notice("Parsing of xml metadata file was successfull"); 
			$this->fileIdentifier = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:fileIdentifier/gco:CharacterString');
			$this->fileIdentifier = $this->fileIdentifier[0];
			//extract createDate - maybe Date or DateTime format
			//http://www.datypic.com/sc/niem21/e-gco_DateTime.html
			$this->createDate = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:dateStamp/gco:Date');
			$tmpCreateDate = $this->createDate[0];
			if ($tmpCreateDate == "" || !isset($tmpCreateDate)) {
			    //try to find DateTime instead
			    $this->createDate = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:dateStamp/gco:DateTime');
			    $tmpCreateDate = $this->createDate[0];
			}
			//default changeDate to createDate
			$this->createDate = $tmpCreateDate;
			$this->changeDate = $tmpCreateDate;
						
			$this->hierarchyLevel = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue');
			$this->hierarchyLevel = $this->hierarchyLevel[0];
			if ($this->hierarchyLevel == 'service') {				
				$identifikationXPath = "srv:SV_ServiceIdentification";	
			} else {
				$identifikationXPath = "gmd:MD_DataIdentification";
			}
			//TODO: check if this is set, maybe DateTime must be searched instead?
			$this->title = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString');
			$this->title = $this->title[0];
			//dataset identifier - howto model into md_metadata?
			//check where datasetid is defined - maybe as RS_Identifier or as MD_Identifier see http://inspire.jrc.ec.europa.eu/documents/Metadata/INSPIRE_MD_IR_and_ISO_v1_2_20100616.pdf page 18
			//First check if MD_Identifier is set, then check if RS_Identifier is used!
			//Initialize datasetid
			$this->datasetId = 'undefined';
			$code = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:MD_Identifier/gmd:code/gco:CharacterString');
			if (isset($code[0]) && $code[0] != '') {
				//new implementation:
				//http://inspire.ec.europa.eu/file/1705/download?token=iSTwpRWd&usg=AOvVaw18y1aTdkoMCBxpIz7tOOgu
				//from 2017-03-02 - the MD_Identifier - see C.2.5 Unique resource identifier - it is separated with a slash - the codespace should be everything after the last slash 
				//now try to check if a single slash is available and if the md_identifier is a url
				$parsedUrl = parse_url($code[0]);

				if (($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') && strpos($parsedUrl['path'],'/') !== false) {
					$explodedUrl = explode('/', $code[0]);
					$this->datasetId = $explodedUrl[count($explodedUrl) - 1];
					$this->datasetIdCodeSpace = rtrim($code[0], $this->datasetId);	
                    //$e = new mb_exception("datasetId: ".$this->datasetId." - datasetIdCodeSpace: ".$this->datasetIdCodeSpace);
				} else {
					if (($parsedUrl['scheme'] == 'http' || $parsedUrl['scheme'] == 'https') && strpos($code[0],'#') !== false) {
						//$e = new mb_exception($code[0]);
						$explodedUrl = explode('#', $code[0]);
						$this->datasetId = $explodedUrl[1];
						$this->datasetIdCodeSpace = $explodedUrl[0];
					} else {
						$this->datasetId = $code[0];
						$this->datasetIdCodeSpace = "";	
					}
				}
			} else { //try to read code from RS_Identifier 		
				$code = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:RS_Identifier/gmd:code/gco:CharacterString');
				$codeSpace = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:citation/gmd:CI_Citation/gmd:identifier/gmd:RS_Identifier/gmd:codeSpace/gco:CharacterString');
				if (isset($codeSpace[0]) && isset($code[0]) && $codeSpace[0] != '' && $code[0] != '') {
					$this->datasetId = $code[0];
					$this->datasetIdCodeSpace = $codeSpace[0];
				} else {
					//neither MD_Identifier nor RS_Identifier are defined in a right way
					$e = new mb_exception("class_iso19139.php: No datasetId found in metadata record!");
				}
			}
			//try another approach - if datasetId == undefined
			//swiss model
			
			//abstract
			$this->abstract = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:abstract/gco:CharacterString');
			$this->abstract = $this->abstract[0];
			$this->keywords = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword/gco:CharacterString');
			//get thesaurus name only for found keywords!
			$iKeyword = 0;
			foreach ($this->keywords as $keyword) {
				$thesaurusName = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:descriptiveKeywords[gmd:MD_Keywords/gmd:keyword/gco:CharacterString="'.$keyword.'"]/gmd:MD_Keywords/gmd:thesaurusName/gmd:CI_Citation/gmd:title/gco:CharacterString');
				$this->keywordsThesaurusName[$iKeyword] = $thesaurusName[0];
				//$e = new mb_exception("Keyword: ".$keyword);
				//$e = new mb_exception("Thesaurus: ".$thesaurusName[0]);
				//check if keyword is inspire thematic key and add it into mapbenders inspire category
				if (is_int($inspireCatHash[trim($keyword)])) {
					$this->inspireCategories[] = $inspireCatHash[trim($keyword)];
				}
				//check if keyword is a key in mapbenders custom keywords and add it to mapbenders custom categories
				if (is_int($customCatHash[trim($keyword)])) {
					//$e = new mb_exception("class_iso19139.php: found entry ".$customCatHash[trim($keyword)]." for custom keyword: ".trim($keyword));
					$this->customCategories[] = $customCatHash[trim($keyword)];
				}
				//default export2Csw to true!
				$this->export2Csw = 't';
				//extract special keywords for mapbenders proxy and inspire monitoring
				switch ($this->keywordsThesaurusName[$iKeyword]) {
					case "mapbender.2.inspireDownload":
						if ($keyword == "1") {
							$this->inspireDownload = 1;
						} else {
							$this->inspireDownload = 0;
						}
						break;
			        case "mapbender.2.noCswExport":
						if ($keyword == "1") {
							$this->export2Csw = 'f';
						} else {
							$this->export2Csw = 't';
						}
						//$e = new mb_exception("inspireDownload: ".$this->inspireDownload);
						break;
					case "mapbender.2.inspireWholeArea":
						$this->inspireWholeArea = $keyword;
						break;
					case "mapbender.2.inspireActualCoverage":
						$this->inspireActualCoverage = $keyword;
						break;
				}
				unset($thesaurusName);
				$iKeyword++;
			}
			//solve problem with identical keywords for areas:
			if ($this->inspireWholeArea == 0 && $this->inspireActualCoverage !== 0) {
					$this->inspireWholeArea = $this->inspireActualCoverage;
			}
			if ($this->inspireWholeArea !== 0 && $this->inspireActualCoverage == 0) {
					$this->inspireActualCoverage = $this->inspireWholeArea;
			}
			$iKeyword = 0;
			$this->isoCategoryKeys = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:topicCategory/gmd:MD_TopicCategoryCode');
			//create mapbenders internal category objects
			//first for topic categories
			foreach ($this->isoCategoryKeys as $isoKey) {
				//$e = new mb_exception("class_iso19139.php: look for iso key: ".$isoKey);
				//test if key is found in hash
				//$e = new mb_exception("class_iso19139.php: found: ".$topicCatHash[trim($isoKey)]);
				if (is_int($topicCatHash[trim($isoKey)])) {
					//$e = new mb_exception("class_iso19139.php: isoCategories entry added: ".$topicCatHash[trim($isoKey)]);
					$this->isoCategories[] = $topicCatHash[trim($isoKey)];
				}
			}
			//debug output:
			/*foreach ($this->isoCategories as $category) {
				$e = new mb_exception("class_iso19139.php: isocat: ".$category);
			}
			foreach ($this->inspireCategories as $category) {
				$e = new mb_exception("class_iso19139.php: inspirecat: ".$category);
			}
			foreach ($this->customCategories as $category) {
				$e = new mb_exception("class_iso19139.php: customcat: ".$category);
			}*/
			$this->downloadLinks = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource[gmd:function/gmd:CI_OnLineFunctionCode/@codeListValue="download"]/gmd:linkage/gmd:URL');
			//volume of dataset when transfered via online access - in megabyte (real) - see iso19139
			/*
			<gmd:transferSize>
   				<gco:Real>1.0</gco:Real>
			</gmd:transferSize>
			*/
			$this->transferSize = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:transferSize/gco:Real');
			if (isset($this->transferSize[0]) && $this->transferSize[0] !=='') {
				$this->transferSize = $this->transferSize[0];
			} else {
				$this->transferSize = null;
			}	
			//preview image
			$this->previewImage = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:graphicOverview/gmd:MD_BrowseGraphic/gmd:fileName/gco:CharacterString');
			if (is_array($this->previewImage) && $this->previewImage[0] !== '') {
				$this->previewImage = $this->previewImage[0];
			} else {
				$this->previewImage = null;
			}	
			//$e = new mb_exception("class_iso19139.php: count of downloadlinks: ".count($this->downloadLinks));
			//$this->downloadLinks = array_values($this->downloadLinks);
			//$dump = var_export($this->downloadLinks, true);
			//$e = new mb_exception("type of this->downloadLinks: ".gettype($this->downloadLinks)." - count: ".count($this->downloadLinks)."- obj: - ".$dump);
			//$this->downloadLinks = (array)$this->downloadLinks[0];
			//$e = new mb_exception("class_iso19139.php: count of downloadlinks: ".count($this->downloadLinks));
			//$e = new mb_exception("class_iso19139.php: downloadlinks[0]: ".$this->downloadLinks[0]);
			/*<gmd:extent><gmd:EX_Extent><gmd:geographicElement><gmd:EX_GeographicBoundingBox><gmd:westBoundLongitude><gco:Decimal>5</gco:Decimal></gmd:westBoundLongitude><gmd:eastBoundLongitude><gco:Decimal>10</gco:Decimal></gmd:eastBoundLongitude><gmd:southBoundLatitude><gco:Decimal>48</gco:Decimal></gmd:southBoundLatitude><gmd:northBoundLatitude><gco:Decimal>52</gco:Decimal></gmd:northBoundLatitude></gmd:EX_GeographicBoundingBox></gmd:geographicElement></gmd:EX_Extent></gmd:extent>*/
			//esri
			/*<gmd:extent><gmd:EX_Extent><gmd:geographicElement><gmd:EX_GeographicBoundingBox><gmd:westBoundLongitude><gco:Decimal>11.1414</gco:Decimal></gmd:westBoundLongitude><gmd:eastBoundLongitude><gco:Decimal>11.7284</gco:Decimal></gmd:eastBoundLongitude><gmd:southBoundLatitude><gco:Decimal>58.8838</gco:Decimal></gmd:southBoundLatitude><gmd:northBoundLatitude><gco:Decimal>59.2562</gco:Decimal></gmd:northBoundLatitude></gmd:EX_GeographicBoundingBox></gmd:geographicElement><gmd:temporalElement><gmd:EX_TemporalExtent><gmd:extent><gml:TimePeriod gml:id="idNummer1"><gml:beginPosition>1999-05-05</gml:beginPosition><gml:endPosition>2012-04-04</gml:endPosition></gml:TimePeriod></gmd:extent></gmd:EX_TemporalExtent></gmd:temporalElement></gmd:EX_Extent></gmd:extent>*/
			//get bbox from xml:
			$minx = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude/gco:Decimal');
			$minx = $minx[0];
			$miny = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude/gco:Decimal');
			$miny = $miny[0];
			$maxx = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude/gco:Decimal');
			$maxx = $maxx[0];
			$maxy = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude/gco:Decimal');
			$maxy = $maxy[0];
			$this->wgs84Bbox = array($minx,$miny,$maxx,$maxy); 
			//more info: https://geo-ide.noaa.gov/wiki/index.php?title=ISO_Extents
			//look for GML3 polygon as exterior ring in two alternative encodings (see: http://www.galdosinc.com/archives/191 - all coords are interpreted as given in EPSG:4326 for the moment!!!):
			//allow multipolygons - multisurface objects
			//test if single polygon or multipolygon is given
			if ($iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/gml:MultiSurface')) {
				//count surfaceMembers
				$this->polygonalExtentExterior = array();
				$numberOfSurfaces = count($iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/gml:MultiSurface/gml:surfaceMember'));
				//$e = new mb_exception("class_iso19139.php: found multisurface element");
				for ($k = 0; $k < $numberOfSurfaces; $k++) {
					$this->polygonalExtentExterior[] = $this->parsePolygon($iso19139Xml, '//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/gml:MultiSurface/gml:surfaceMember['. (string)($k + 1) .']/');
				}
			} else { 
				if ($iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/gml:Polygon')) {
					$this->polygonalExtentExterior = array();
					$this->polygonalExtentExterior[0] = $this->parsePolygon($iso19139Xml, '//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/');
				}
			}
			$this->tmpExtentBegin = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:beginPosition');
			$this->tmpExtentBegin = $this->tmpExtentBegin[0];
			if ($this->tmpExtentBegin == "") {
				$this->tmpExtentBegin = "1900-01-01";
			}
			$this->tmpExtentEnd = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:temporalElement/gmd:EX_TemporalExtent/gmd:extent/gml:TimePeriod/gml:endPosition');		
			$this->tmpExtentEnd = $this->tmpExtentEnd[0];
			if ($this->tmpExtentEnd == "") {
				$this->tmpExtentEnd = "1900-01-01";
			}
			//spatial_res_type/spatial_res_value
			$equivalentScale = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:spatialResolution/gmd:MD_Resolution/gmd:equivalentScale/gmd:MD_RepresentativeFraction/gmd:denominator/gco:Integer');
			$equivalentScale = $equivalentScale[0];
			$groundResolution = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance');
			$groundResolution = $groundResolution[0];
			if (isset($groundResolution) && $groundResolution != "") {
				$this->spatialResValue = $groundResolution;
				$this->spatialResType = "groundDistance";
			} else {
				if (isset($equivalentScale) && $equivalentScale != "") {
					$this->spatialResValue = $equivalentScale;
					$this->spatialResType = "scaleDenominator";
				}
			}
			/*
			if (isset($equivalentScale) && $equivalentScale != "") {
				$this->spatialResValue = $equivalentScale;
				$this->spatialResType = "scaleDenominator";
			} else {
				if (isset($groundResolution) && $groundResolution != "") {
					$this->spatialResValue = $groundResolution;
					$this->spatialResType = "groundDistance";
				}
			}*/
			//ref_system
			$this->refSystem = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/gmd:RS_Identifier/gmd:code/gco:CharacterString');
			$this->refSystem = $this->refSystem[0];
			//parse codes to get EPSG:XXXXX TODO use other function to support other codes
			//get last part of string separated by the colon symbol
			if ($this->hierarchyLevel != 'service' && $this->hierarchyLevel != '') {
				$e = new mb_exception("classes/class_iso19139.php: epsg to lookup:".$this->refSystem);
				try {
			        $crsObject = new Crs($this->refSystem);
				} catch (Exception $e) {
    			    $err = new mb_exception("classes/class_Iso19139.php: - tried to resolve crs via class_crs: ".$e->getMessage());
			        //return "error";
			        $crsObject = false;
				}
				if ($crsObject != false) {
					$e = new mb_exception("classes/class_iso19139.php: resolved epsg id:".$crsObject->identifierCode);
			        $epsgId = $crsObject->identifierCode;
			        $this->refSystem = "EPSG:".$epsgId;
				} 
			}
			//debug output of keywords:
			/*$iKeyword = 0;
			foreach($this->keywords as $keyword) {  
				$e = new mb_exception("Keyword: ".$keyword." - Thesaurus: ".$this->keywordsThesaurusName[$iKeyword]);
				$iKeyword++;
			}
			$iKeyword = 0;*/
			//format
			//lineage
			$this->lineage = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:lineage/gmd:LI_Lineage/gmd:statement/gco:CharacterString');
			$this->lineage = $this->lineage[0];
			//inspire_charset
			//inspire_top_consistence
			//inspire_interoperability
			//$e = new mb_exception(json_encode($this->inspireLegislation));
			//$e = new mb_exception($this->inspireLegislation[0]->label->de);
			//if dataset and ir is defined in the conformance declaration!
			
			//responsible_party
			//fees
			//"constraints"
			//extract standardized licenses from constraints (see http://www.geoportal.de/SharedDocs/Downloads/DE/GDI-DE/Architektur_GDI_DE-Konventionen_%20Metadaten.pdf?__blob=publicationFile)
			$restrictionCodeAttributeValue = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo//gmd:resourceConstraints/gmd:MD_LegalConstraints/gmd:useConstraints/gmd:MD_RestrictionCode/@codeListValue');
			$first = $restrictionCodeAttributeValue[0];
			$second = $restrictionCodeAttributeValue[1];
			// TODO: check the behavior
			//if ($first == 'license' && $second == 'otherRestrictions') {
				//search for json
				$otherConstraints = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo//gmd:resourceConstraints/gmd:MD_LegalConstraints[gmd:useConstraints/gmd:MD_RestrictionCode/@codeListValue="otherRestrictions"]/gmd:otherConstraints/gco:CharacterString');
				$jsonFound = false;
				$otherConstraintsFreeText = "";
				foreach($otherConstraints as $otherConstraint) {
					if (json_decode(stripslashes($otherConstraint)) !== NULL) {
						//parse json
						$standardizedLicense = json_decode(stripslashes($otherConstraint));
						//Look for source
						$this->licenseSourceNote = $standardizedLicense->quelle;
						$this->licenseJson = stripslashes($otherConstraint);
						//$e = new mb_exception("class_iso19139.php: licenseSourceNote: ".$this->licenseSourceNote);
					} else {
						$otherConstraintsFreeText .= $otherConstraint.";";
					}
				} 
				$this->fees = rtrim($otherConstraintsFreeText, ';');
			//}
			$e = new mb_notice("class_iso19139.php: licenseSourceNote: ".$this->licenseSourceNote." - fees: ".$this->fees);		
			$accessConstraints = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo//gmd:resourceConstraints/gmd:MD_LegalConstraints[gmd:accessConstraints/gmd:MD_RestrictionCode/@codeListValue="otherRestrictions"]/gmd:otherConstraints/gco:CharacterString');
			$this->accessConstraints = $accessConstraints[0];
			//$e = new mb_exception("accessConstraints: ".$accessConstraints[0]);
			$this->resourceResponsibleParty = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:organisationName/gco:CharacterString');
			$this->resourceResponsibleParty = $this->resourceResponsibleParty[0];
			$this->resourceContactEmail = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:pointOfContact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString');
			$this->resourceContactEmail = $this->resourceContactEmail[0];
			//parse extension for gmd:resourceMaintenance
			$updateFrequency = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:resourceMaintenance/gmd:MD_MaintenanceInformation/gmd:maintenanceAndUpdateFrequency/gmd:MD_MaintenanceFrequencyCode[@codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#MD_MaintenanceFrequencyCode"]/@codeListValue');
			$updateFrequency = $updateFrequency[0];
			//TODO: push codelists into conf files !
			//http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#MD_MaintenanceFrequencyCode
			$codeListUpdateFrequencyArray = array('continual','daily','weekly','fortnightly','monthly','quarterly','biannually','annually','asNeeded','irregular','notPlanned','unknown');
			if (in_array($updateFrequency, $codeListUpdateFrequencyArray)) {
				$this->updateFrequency = $updateFrequency;
			}
			//check declaration of inspire conformity - only true if true for all relevant regulations is declared!
			$this->inspireInteroperability = 't';
			$interoperabilityArray = array();
			$countInteroperabilityArray = 0;
			foreach ($this->inspireRegulations as $regulation) {
				$interoperabilityArray[$countInteroperabilityArray]['name'] = $regulation['name'];
				$interoperabilityArray[$countInteroperabilityArray]['date'] = $regulation['date']->format('Y-m-d');
				$interoperabilityArray[$countInteroperabilityArray]['pass'] = null;
				//get boolean from metadata
				//string(//gmd:MD_Metadata/gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult[gmd:specification/gmd:CI_Citation/gmd:title/gco:CharacterString="Verordnung (EG) Nr. 1205/2008 der Kommission vom 3. Dezember 2008 zur Durchführung der Richtlinie 2007/2/EG des Europäischen Parlaments und des Rates hinsichtlich Metadaten" and gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date="2008-12-03"]/gmd:pass/gco:Boolean)
				$conformanceStatement = $iso19139Xml->xpath('//gmd:MD_Metadata/gmd:dataQualityInfo/gmd:DQ_DataQuality/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult[gmd:specification/gmd:CI_Citation/gmd:title/gco:CharacterString="'.$regulation['name'].'" and gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date="'.$regulation['date']->format('Y-m-d').'"]/gmd:pass/gco:Boolean');
				//problem: xpath extract this element strange - the first entry is a string object 
				$conformanceStatement = (string)$conformanceStatement[0];
				switch ($conformanceStatement) {
					case "true":
						$interoperabilityArray[$countInteroperabilityArray]['pass'] = "true";
   						break;
					case "false":
						$interoperabilityArray[$countInteroperabilityArray]['pass'] = "false";
						$this->inspireInteroperability = 'f';
					default:
						$interoperabilityArray[$countInteroperabilityArray]['pass'] = "not declared";
						$this->inspireInteroperability = 'f';
						break;
				}
				$countInteroperabilityArray++;
			}
			//for debugging purposes
			foreach ($interoperabilityArray as $declaredSpec) {
				$e = new mb_notice("classes/class_iso19139.php: check conformance declaration: name: ".$declaredSpec['name']. " - date: ".$declaredSpec['date']." - pass: ".$declaredSpec['pass']);
			}
			$e = new mb_notice("classes/class_iso19139.php: sufficient declared inspire conformity: ".$this->inspireInteroperability);
            if (isset($this->fileIdentifier) && $this->fileIdentifier != "" && isset($this->title) && $this->title != "" && isset($this->abstract) && $this->abstract != "") {
            	$this->metadata = $xml;
            } else {
            	$this->metadata = <<<XML
				<mb:ExceptionReport xmlns:mb="http://www.mapbender.org/metadata/exceptionreport">
					<mb:Exception exceptionCode="NoApplicableCode">
						<mb:ExceptionText>ISO Metadata XML has neither a fileIdentifier nor title or abstract</mb:ExceptionText>
				    </mb:Exception>
				</mb:ExceptionReport>
XML;
            }
			$this->qualifyMetadata();
			$this->harvestResult = 1;
			return $this;
		} else {
			return false;
		}
	}
	
	public function qualifyMetadata() {
		//delete 0 entries from integer categories values
		//all categories
		$types = array("md_topic", "inspire", "custom");
		foreach ($types as $cat) {
			switch ($cat) {
				case "md_topic":
					$objectPrefix = 'iso';
				break;
				default:
					$objectPrefix = $cat;
				break;
			}
		}
		//qualify keywords 
		$this->keywords = array_unique($this->keywords);
		/*if (count($this->{$objectPrefix."Categories"}) && $this->{$objectPrefix."Categories"}[0] == 0) {
			$this->{$objectPrefix."Categories"} = array();
		}*/
		//qualify date date/time fields
		$dateFields = array("createDate", "changeDate", "tmpExtentBegin", "tmpExtentEnd");
		foreach ($dateFields as $dateField) {
			$valueToCheck = $this->{$dateField};
			//validate to iso date format YYYY-MM-DD
			$testMatch = $valueToCheck;
			$pattern = '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/';	
			//https://stackoverflow.com/questions/12756159/regex-and-iso8601-formatted-datetime
			$patternDateTime = '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(\.\d+)?(([+-]\d\d:\d\d)|Z)?$/';
			$patternDateTime2 = '/^\d{4}(-\d\d(-\d\d(T\d\d:\d\d(:\d\d)?(\.\d+)?(([+-]\d\d:\d\d)|Z)?)?)?)?$/';
 			if (!preg_match($pattern,$testMatch) && !preg_match($patternDateTime,$testMatch) && !preg_match($patternDateTime2,$testMatch)){ 
				$e = new mb_exception("classes/class_iso19139.php: invalid date format for attribute ".$dateField." - found: ".$valueToCheck.". Set it to 1900-01-01!");
				$this->{$dateField} = "1900-01-01";
				$this->harvestException = $this->harvestException."\nInvalid date format for attribute ".$dateField." - found: ".$valueToCheck.". Set it to 1900-01-01!";	
 			}
		}
	}

	public function parsePolygon($iso19139Xml, $pathToPolygon) {
		$polygonalExtentExterior = array();		
		// //gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/
		// or //gmd:MD_Metadata/gmd:identificationInfo/'.$identifikationXPath.'/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon/gmd:polygon/gml:MultiSurface/surfaceMember[1]/
		if ($iso19139Xml->xpath($pathToPolygon.'gml:Polygon/gml:exterior/gml:LinearRing/gml:posList')) {
		//read posList
			$exteriorRingPoints = $iso19139Xml->xpath($pathToPolygon.'gml:Polygon/gml:exterior/gml:LinearRing/gml:posList');
			if (count($exteriorRingPoints) > 0) {
				//poslist is only space separated
				$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
				for ($i = 0; $i <= count($exteriorRingPointsArray)/2-1; $i++) {
					$polygonalExtentExterior[$i]['x'] = $exteriorRingPointsArray[2*$i];
					$polygonalExtentExterior[$i]['y'] = $exteriorRingPointsArray[(2*$i)+1];
				}
			}
		} else {
			//try to read coordinates
			$exteriorRingPoints = $iso19139Xml->xpath($pathToPolygon.'gml:Polygon/gml:exterior/gml:LinearRing/gml:coordinates');
			if (count($exteriorRingPoints) > 0) {
				//two coordinates of one point are comma separated
				//problematic= ", " or " ," have to be deleted before
				$exteriorRingPoints[0] = str_replace(', ',',',str_replace(' ,',',',$exteriorRingPoints[0]));
				$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
				for ($i = 0; $i <= count($exteriorRingPointsArray)-1;$i++) {
					$coords = explode(",",$exteriorRingPointsArray[$i]);
					$polygonalExtentExterior[$i]['x'] = $coords[0];
					$polygonalExtentExterior[$i]['y'] = $coords[1];
				}
			}
		}
		return $polygonalExtentExterior; 
	}

	public function createFromUrl($url){
		$this->href = $url;
		if ($this->isLinkAlreadyInDB() == false) {
			$this->linkAlreadyInDB = false;
		} else {
			$this->linkAlreadyInDB = true;
		}
		$metadataConnector = new connector();
		$metadataConnector->set("timeOut", "5");
		$metadataConnector->load($url);
		$xml = $metadataConnector->file;
		if ($metadataConnector->timedOut == true) {
			return false;
		}
		$mbMetadata = $this->createMapbenderMetadataFromXML($xml);
		return $mbMetadata;
	}

	public function readFromUrl($url){
		$this->href = $url;
		$metadataConnector = new connector();
		$metadataConnector->set("timeOut", "10");
		$metadataConnector->load($url);
		$xml = $metadataConnector->file;
		$this->metadata = $xml;
	}

	public function transformToRdf() {
		$xslDoc = new DOMDocument();
   		$xslDoc->load(dirname(__FILE__) . "/../geoportal/xslt/iso-19139-to-dcat-ap.xsl");
   		$xmlDoc = new DOMDocument();
   		$xmlDoc->loadXML($this->metadata);
   		$proc = new XSLTProcessor();
		libxml_use_internal_errors(true);
   		$result = $proc->importStylesheet($xslDoc);
		if (!$result) {
    			foreach (libxml_get_errors() as $error) {
        			$e = new mb_exception("Libxml error: {$error->message}\n");
    			}
		}
   		return $proc->transformToXML($xmlDoc);	
	}

	public function transformToHtml2() {
		$dcat = $this->transformToRdf();
		$xslDoc = new DOMDocument();
   		$xslDoc->load(dirname(__FILE__) . "/../geoportal/xslt/dcat-ap-rdf2rdfa.xsl");
   		$xmlDoc = new DOMDocument();
   		$xmlDoc->loadXML($dcat);
   		$proc = new XSLTProcessor();
		libxml_use_internal_errors(true);
   		$result = $proc->importStylesheet($xslDoc);
		if (!$result) {
    			foreach (libxml_get_errors() as $error) {
        			$e = new mb_exception("Libxml error: {$error->message}\n");
    			}
		}
   		return $proc->transformToXML($xmlDoc);	
	}

	public function transformToHtml3($layout,$languageCode){
		
	}

	public function transformToHtml($layout, $languageCode, $serviceInformation=false){
		libxml_use_internal_errors(true);
		//TODO don't parse it again, but change the internal parser function!
		try {
			$iso19139Xml = simplexml_load_string($this->metadata);
			if ($iso19139Xml === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_Iso19139:".$error->message);
    				}
				throw new Exception("class_Iso19139:".'Cannot parse Metadata XML (transformToHtml)!');
				return "error";
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_Iso19139:".$e->getMessage());
			return "error";
		}
		$err = new mb_notice("class_Iso19139: result of iso19139Xml: ". gettype($iso19139Xml));
		//if parsing was successful
		if ($iso19139Xml !== false) {
			$e = new mb_notice("Parsing of xml metadata file was successfull");
			//register namespaces for parsing content
			$iso19139Xml->registerXPathNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");
			$iso19139Xml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			$iso19139Xml->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
			$iso19139Xml->registerXPathNamespace("gmd", "http://www.isotc211.org/2005/gmd");
			$iso19139Xml->registerXPathNamespace("gts", "http://www.isotc211.org/2005/gts");
			$iso19139Xml->registerXPathNamespace("srv", "http://www.isotc211.org/2005/srv");
			$iso19139Xml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
			include(dirname(__FILE__)."/../../conf/isoMetadata.conf");
			for($a = 0; $a < count($iso19139Hash); $a++) {
				$resultOfXpath = $iso19139Xml->xpath("/".$iso19139Hash[$a]['iso19139']);#
				//if array should not be handled as array - handle it as string!
				if ($iso19139Hash[$a]['iso19139explode'] != "true") {
					for ($i = 0; $i < count($resultOfXpath); $i++) {
						$iso19139Hash[$a]['value'] = $iso19139Hash[$a]['value'].",".$resultOfXpath[$i];
					}
					$iso19139Hash[$a]['value'] = ltrim($iso19139Hash[$a]['value'],',');
				} else {
					/*if (is_array($resultOfXpath)) {
						$iso19139Hash[$a]['value'] = $resultOfXpath[0];
					} else {*/
						$iso19139Hash[$a]['value'] = $resultOfXpath;
					//}
				}
			}
			//generate html
			$html = '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xmlns:dcat="http://www.w3.org/ns/dcat#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dctype="http://purl.org/dc/dcmitype/" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" xmlns:vcard="http://www.w3.org/2006/vcard/ns#" xml:lang="'.$languageCode.'">';

			$metadataStr .= '<head>' . 
				'<title>'._mb("Metadata").'</title>' . 
				'<meta name="description" content="'._mb("Metadata").'" xml:lang="'.$languageCode.'" />'.
				'<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0;">' .
				'<meta name="keywords" content="'._mb("Metadata").'" xml:lang="'.$languageCode.'" />'	.	
				'<meta http-equiv="cache-control" content="no-cache">'.
				'<meta http-equiv="pragma" content="no-cache">'.
				'<meta http-equiv="expires" content="0">'.
				'<meta http-equiv="content-language" content="'.$languageCode.'" />'.
				'<meta http-equiv="content-style-type" content="text/css" />'.
				'<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">' . 	
				'<meta http-equiv="X-UA-Compatible" content="IE=edge" />' . 
				'</head>';
			$html .= $metadataStr;
			$html .= '<body>';
			//define the javascripts to include
			$html .= '<link type="text/css" href="../css/metadata_responsiv.css" rel="Stylesheet" />';
			//define main vocabulary
			switch ($iso19139Hash[3]['value']) {
				case "service":
					$mainVocabReference = 'vocab="http://schema.org/" typeof="Map"';
					break;
				default:
					$mainVocabReference = 'vocab="http://schema.org/" typeof="Dataset"';
					break;
			}
			$providerOrganizationCategory = 'property="provider" typeof="Organization"';
			$publisherOrganizationCategory = 'property="publisher" typeof="Organization"';
			$producerOrganizationCategory = 'property="producer" typeof="Organization"';


			switch ($layout) {
				case "tabs":
					$html .= '<link type="text/css" href="../extensions/jquery-ui-1.8.1.custom/css/custom-theme/jquery-ui-1.8.5.custom.css" rel="Stylesheet" />';	
					$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-1.4.2.min.js"></script>';
					$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-ui-1.8.1.custom.min.js"></script>';
					//initialize tabs
					$html .= '<script type="text/javascript">';
					$html .= '$(function() {';
					$html .= '	$("#tabs").tabs();';
					$html .= '});';
					$html .= '</script>';
					//independently define the headers of the parts
					//window close button top
                                	$html .= '<div style="padding:10px;display:block;text-align:center;"><a href="javascript:window.close()">'._mb("Close window").'</a></div>';
					$html .= '<div class="demo">';
					$html .= '<div '.$mainVocabReference.' id="tabs">';
					$html .= '<ul>';
					$html .= 	'<li><a href="#tabs-1">'._mb("Overview").'</a></li>';
					$html .= 	'<li><a href="#tabs-2">'._mb("Properties").'</a></li>';
					$html .= 	'<li><a href="#tabs-3">'._mb("Contact").'</a></li>';
					$html .= 	'<li><a href="#tabs-4">'._mb("Terms of use").'</a></li>';
					$html .= 	'<li><a href="#tabs-5">'._mb("Quality").'</a></li>';
					$html .= 	'<li><a href="#tabs-6">'._mb("Interfaces").'</a></li>';
					$html .= '</ul>';
					break;
				case "accordion":
					$html .= '<link type="text/css" href="../extensions/jquery-ui-1.8.1.custom/css/custom-theme/jquery-ui-1.8.4.custom.css" rel="Stylesheet" />';	
					$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-1.4.2.min.js"></script>';
					$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-ui-1.8.1.custom.min.js"></script>';
					//define the javascript functions
					$html .= '<script type="text/javascript">';
					$html .= '	$(function() {';
					$html .= '		$("#accordion").accordion();';
					//$html .= '		$("#accordion").accordion({ autoHeight: false});';
					//$html .= '		$("#accordion").accordion({ autoHeight: false , clearStyle: true });';
					$html .= '	});';
					$html .= '	</script>';
					//window close button top
                               	 	$html .= '<div style="padding:10px;display:block;text-align:center;"><a href="javascript:window.close()">'._mb("Close window").'</a></div>';
					$html .= '<div class="demo">';
					$html .= '<div '.$mainVocabReference.' id="accordion">';
					break;
				case "plain":
					//window close button top
                                	$html .= '<div style="padding:10px;display:block;text-align:center;"><a href="javascript:window.close()">'._mb("Close window").'</a></div>';
					$html .= '<div class="demo">';
					$html .= '<div '.$mainVocabReference.' id="plain">';
					break;
				default:
					$html .= '<div '.$mainVocabReference.'>';
					break;
			}
			//some placeholders
			$tableBegin =  "<table>\n";
			$t_a = "\t<tr>\n\t\t<th>\n\t\t\t";
			$t_b = "\n\t\t</th>\n\t\t<td>\n\t\t\t";
			$t_c = "\n\t\t</td>\n\t</tr>\n";
			$tableEnd = "</table>\n";
			//**************************overview part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Overview").'</a></h3>';
					$html .= '<div style="height:300px">';
					break;
				case "tabs":
					$html .= '<div id="tabs-1">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Overview").'</h3>';
					$html .= '<div>';
					break;
				default:
					$html .= '<div>';
					break;
			}
			//$html .= '<p>';
			if (count($serviceInformation->service) > 0) {
				//new for coupled services if they exists:
				$html .= '<fieldset><legend>'._mb("Access via services").'</legend>';
				$html .= $tableBegin;
				foreach ($serviceInformation->service as $service) {
					//$e = new mb_exception("accessurl: ".$service->accessUrl);
					//qualify service urls from other sources - maybe serviceTypeVersion was not set by other providers
					if (!in_array($service->serviceTypeVersion, array("predefined ATOM","OGC:WMS 1.1.1","OGC:WMS 1.3.0"))) {
						$serviceUrl = parse_url($service->accessUrl);
						//$e = new mb_exception(json_encode($serviceUrl["query"]));
						if (isset($serviceUrl["query"]) && $serviceUrl["query"] != "") {
							parse_str($serviceUrl["query"], $requestParams);
							//$e = new mb_exception(json_encode($requestParams));
							$upperRequestParams = array_change_key_case($requestParams, CASE_UPPER);
							if (array_key_exists("SERVICE", $upperRequestParams) && array_key_exists("REQUEST", $upperRequestParams)) {
								if (strtoupper($upperRequestParams["SERVICE"]) == "WMS" && strtoupper($upperRequestParams["REQUEST"]) == "GETCAPABILITIES") {
									$service->serviceTypeVersion = "OGC:WMS 1.1.1";
									if (!array_key_exists("VERSION", $upperRequestParams)) {
										$service->accessUrl .= $service->accessUrl."&VERSION=1.1.1";
									}
								}
							}
							//some super ugly services don't have the parameter service itself -very ugly - austria
							//https://inspire.lfrz.gv.at/000802/wms?request=GetCapabilities&version=1.3.0
							//$e = new mb_exception("upperRequestParams: ".json_encode($upperRequestParams));
							if (array_key_exists("REQUEST", $upperRequestParams) && strtoupper($upperRequestParams["REQUEST"]) == "GETCAPABILITIES" && array_key_exists("VERSION", $upperRequestParams) && $upperRequestParams["VERSION"] == "1.3.0") {
								//$e = new mb_exception("Set serviceTypeVersion to OGC:WMS 1.3.0");
								$service->serviceTypeVersion = "OGC:WMS 1.3.0";
							}
						}
					}
					//$e = new mb_exception("accessurl: ".$service->accessUrl);
					//$e = new mb_exception("serviceTypeVersion: ".$service->serviceTypeVersion);
					switch ($service->serviceTypeVersion) {
						case "predefined ATOM":
							//use atom feed client
							//$accessUrl = $service->accessUrl;
							//$accessUrl = "https://www.google.de";
							$accessUrl = MAPBENDER_PATH."/plugins/mb_downloadFeedClient.php?url=".urlencode($service->accessUrl);
							break;
						case "OGC:WMS 1.1.1":
							//invoke geoportal viewer
							$accessUrl = str_replace("mapbender", "map?WMS=", MAPBENDER_PATH);
							$accessUrl .= urlencode($service->accessUrl);
							$accessUrl .= "&DATASETID=";
							//resource identifier
							if ($iso19139Hash[37]['value'] != "") {
								$accessUrl .= urlencode($iso19139Hash[37]['value']); //MD Identifier
							} else {
								$accessUrl .= urlencode($iso19139Hash[5]['value']."".$iso19139Hash[6]['value']);
							}
							break;
						case "OGC:WMS 1.3.0":
							//invoke geoportal viewer test
							$accessUrl = str_replace("mapbender", "map?WMS=", MAPBENDER_PATH);
							$accessUrl .= urlencode($service->accessUrl);
							$accessUrl .= "&DATASETID=";
							//resource identifier
							if ($iso19139Hash[37]['value'] != "") {
								$accessUrl .= urlencode($iso19139Hash[37]['value']); //MD Identifier
							} else {
								$accessUrl .= urlencode($iso19139Hash[5]['value']."".$iso19139Hash[6]['value']);
							}
							break;
						default:
							$accessUrl = $service->accessUrl;
							break;
					}
					if (in_array($service->serviceType, array("view", "download"))) {
						$html .= $t_a."<img src='../img/dj_".$service->serviceType.".png'/> ".$t_b."<a href='".$accessUrl."' target='_blank'>".$service->serviceTitle."</a>".$t_c;
					} else {
						$html .= $t_a."<b>".$service->serviceType."</b>: ".$t_b."<a href='".$_SERVER['PHP_SELF']."?url=".urlencode($service->metadataUrl)."' target='_blank'>".$service->serviceTitle."</a>".$t_c;
			
					}
				}
				$html .= $tableEnd;
				$html .= '</fieldset>';
			}
			$html .= '<fieldset><legend>'._mb("Metadata").'</legend>';
			$html .= $tableBegin;
			#$html .= $t_a."<b>".$iso19139Hash[0]['html']."</b>: ".$t_b.'<p property="'.$iso19139Hash[0]['property'].'" datatype="'.$iso19139Hash[0]['datatype'].'" content="'.$iso19139Hash[0]['value'].'">'.$iso19139Hash[0]['value']."</p>".$t_c;
			$hashIndices = array(0, 31 ,32);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset><legend>'._mb("Identification").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(1, 2, 3); //title, abstract, type
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			if (isset($iso19139Hash[36]['value']) && $iso19139Hash[36]['value'] != "") {
				if (is_array($iso19139Hash[36]['value'])) {
					$iso19139Hash[36]['value'] = $iso19139Hash[36]['value'][0];
				}
				if (!is_null($iso19139Hash[36]['value'])) {
					$html .= $t_a."<b>".$iso19139Hash[36]['html']."</b>: ".$t_b."<img width=\"120\" height=\"120\" src = '".$iso19139Hash[36]['value']."'>".$t_c;//preview
				}
			}
			//resource identifier
			if ($iso19139Hash[37]['value'] != "") {
				//split with # - TODO alter this!
				//$mdIdentifier = explode('#',$iso19139Hash[37]['value']);
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, 37); //MD Identifier
			} else {
				$hashIndices = array(5, 6); //namespace, id
				foreach ($hashIndices as $index) {
					$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
				}
			}
			/*$hashIndices = array(26, 27); //orga name, email
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}*/
			$html .= $tableEnd;
			$html .= '</fieldset>';
		
			$bbox = explode(',',$iso19139Hash[12]['value']);

			if (count($bbox) == 4) {
				$wgs84Bbox = $bbox[0].",".$bbox[2].",".$bbox[1].",".$bbox[3];
				$getMapUrl = $this->getExtentGraphic(explode(",", $wgs84Bbox));
				$html .= '<fieldset><legend>'._mb("Extent").'</legend>';
				if (defined('EXTENTSERVICEURL')) {
					$html .= "<img src='".$getMapUrl."'>";
				} else {
					$html .= _mb('Graphic unavailable');
				}
				$html .= '</fieldset>';	
			}
			$html .= '<fieldset '.$producerOrganizationCategory.'><legend>'._mb("Contact").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(26, 27); //orga name, email
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			//$html .= '</p>';
			$html .= '</div>';//element
			//***************************************************************************
			//**************************properties part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Properties").'</a></h3>';
					$html .= '<div style="height:300px">';
					break;
				case "tabs":
					$html .= '<div id="tabs-2">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Properties").'</h3>';
					$html .= '<div>';
					break;
				default:
					$html .= '<div>';
					break;
			}
			//$html .= '<p>';
			$html .= '<fieldset><legend>'._mb("Common").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(8, 9, 11);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset><legend>'._mb("Geographic extent").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(33, 12);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset><legend>'._mb("Temporal extent").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(38, 39, 14, 15, 16);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			if ($iso19139Hash[3]['value'] == 'dataset' || $iso19139Hash[3]['value'] == 'series') {
				$html .= '<fieldset><legend>'._mb("Format").'</legend>';
				$html .= $tableBegin;
				$hashIndices = array(34, 35);
				foreach ($hashIndices as $index) {
					$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
				}
				$html .= $tableEnd;
				$html .= '</fieldset>';
			}
			if ($iso19139Hash[3]['value'] == 'service') {
				$html .= '<fieldset><legend>'._mb("Service information").'</legend>';
				$html .= $tableBegin;
				$hashIndices = array(10, 7);
				foreach ($hashIndices as $index) {
					$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
				}
				$html .= $tableEnd;
				$html .= '</fieldset>';
			}
			//$html .= '</p>';
			$html .= '</div>';//element
			//***************************************************************************
			//**************************contact part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Contact").'</a></h3>';
					$html .= '<div '.$offersOfferCategory.'style="height:300px">';
					break;
				case "tabs":
					$html .= '<div '.$offersOfferCategory.'id="tabs-3">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Contact").'</h3>';
					$html .= '<div '.$offersOfferCategory.'>';
					break;
				default:
					$html .= '<div '.$offersOfferCategory.'>';
					break;
			}
			//$html .= '<p>';
			$html .= '<fieldset '.$providerOrganizationCategory.'><legend>'._mb("Data/Service provider").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(26, 28, 27);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset '.$publisherOrganizationCategory.'><legend>'._mb("Metadata provider").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(29, 30);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			//$html .= '</p>';
			$html .= '</div>';//element
			//***************************************************************************
			//**************************terms of use part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Terms of use").'</a></h3>';
					$html .= '<div style="height:300px">';
					break;
				case "tabs":
					$html .= '<div id="tabs-4">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Terms of use").'</h3>';
					$html .= '<div>';
					break;
				default:
					$html .= '<div>';
					break;
			}
			//$html .= '<p>';
			$html .= '<fieldset><legend>'._mb("Conditions").'</legend>';
			$html .= $tableBegin;
			$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, 23);
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset><legend>'._mb("Access constraints").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(24, 25, 40);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			//$html .= '</p>';
			$html .= '</div>';//element
			//***************************************************************************
			//**************************quality part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Quality").'</a></h3>';
					$html .= '<div style="height:300px">';
					break;
				case "tabs":
					$html .= '<div id="tabs-5">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Quality").'</h3>';
					$html .= '<div>';
					break;
				default:
					$html .= '<div>';
					break;
			}
			//$html .= '<p>';
			if ($iso19139Hash[3]['value'] == 'dataset' || $iso19139Hash[3]['value'] == 'series') {
				$html .= '<fieldset><legend>'._mb("Lineage").'</legend>';
				$html .= $tableBegin;
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, 17);
				$html .= $tableEnd;
				$html .= '</fieldset>';
				$html .= '<fieldset><legend>'._mb("Resolution").'</legend>';
				$html .= $tableBegin;
				$hashIndices = array(18, 19);
				foreach ($hashIndices as $index) {
					$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
				}
				$html .= $tableEnd;
				$html .= '</fieldset>';
			}
			$html .= '<fieldset><legend>'._mb("Validity").'</legend>';
			$html .= $tableBegin;
			$hashIndices = array(20, 21, 22);
			foreach ($hashIndices as $index) {
				$html .= $this->getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $index);
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			//$html .= '</p>';
			$html .= '</div>';//element
			//***************************************************************************
			//**************************Interfaces part begin******************************
			//generate div tags for the content - the divs are defined in the array
			switch ($layout) {
				case "accordion":
					$html .= '<h3><a href="#">'._mb("Interfaces").'</a></h3>';
					$html .= '<div style="height:300px">';
					break;
				case "tabs":
					$html .= '<div id="tabs-6">';
					break;
				case "plain":
					$html .= '<h3>'._mb("Interfaces").'</h3>';
					$html .= '<div>';
					break;
				default:
					$html .= '<div>';
					break;
			}
			//$html .= '<p>';
			$html .= '<fieldset><legend>'._mb("Online access").'</legend>';
			$html .= $tableBegin;
			$html .= $t_a."<b>".$iso19139Hash[4]['html']."</b>: ".$t_b."<a property=\"url\" href='".$iso19139Hash[4]['value']."' target='_blank'>".$iso19139Hash[4]['value']."</a>".$t_c;
			if ($iso19139Hash[3]['value'] == 'service' && $iso19139Hash[10]['value'] == 'download') {
					//show link to own atom feed download client
					//push ATOM Service feed url to client	
					if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != '' && parse_url($iso19139Hash[4]['value'])) {	
						$html .= $t_a."<b>"._mb("ATOM Feed client")."</b>: ".$t_b."<a href='".MAPBENDER_PATH."/plugins/mb_downloadFeedClient.php?url=".urlencode($iso19139Hash[4]['value'])."' target='_blank'>"._mb("Download")."</a>".$t_c;
					}
					
			}
			$html .= $tableEnd;
			$html .= '</fieldset>';
			$html .= '<fieldset><legend>'._mb("Metadata").'</legend>';
			$html .= $tableBegin;
			//exchange mdtype html with iso19139
			//$queryNew = str_replace("mdtype=html","mdtype=iso19139",$_SERVER['QUERY_STRING']);
			$html .= $t_a."<b>"._mb("ISO19139")."</b>: ".$t_b."<a href='".$_SERVER['PHP_SELF']."/../../php/mod_exportIso19139.php?".$_SERVER['QUERY_STRING']."&outputFormat=iso19139"."' target='_blank'>"._mb("Metadata")."</a>".$t_c;
			$html .= $t_a."<b>"._mb("RDF")."</b>: ".$t_b."<a href='".$_SERVER['PHP_SELF']."/../../php/mod_exportIso19139.php?".$_SERVER['QUERY_STRING']."&outputFormat=rdf"."' target='_blank'>"._mb("Geo-DCAT Metadata")."</a>".$t_c;
			//<a href='".$_SERVER['PHP_SELF']."?".$queryNew."&validate=true' target='_blank'><img style='border: none;' src = '../img/misc/icn_inspire_validate.png' title='"._mb("INSPIRE Validator")."'></a>
			//push xml instead of html? But there is no real url
			$html .= $tableEnd;
			$html .= '</fieldset>';
			if (count($serviceInformation->service) > 0) {
				//new for coupled services if they exists:
				$html .= '<fieldset><legend>'._mb("Available Services").'</legend>';
				$html .= $tableBegin;
				foreach ($serviceInformation->service as $service) {
					$html .= $t_a."<b>".$service->serviceType."</b>: ".$t_b."<a href='".$_SERVER['PHP_SELF']."?url=".urlencode($service->metadataUrl)."' target='_blank'>".$service->serviceTitle."</a>".$t_c;
				}
				$html .= $tableEnd;
				$html .= '</fieldset>';
			}
			//$html .= '</p>';
			$html .= '</div>';//foreach upper category element
			//***************************************************************************
			switch ($layout) {
				case "accordion":
					$html .= '</div>';
					$html .= '</div>';
					break;
				case "tabs":
					$html .= '</div>';
					$html .= '</div>';
					break;
				case "plain":
					$html .= '</div>';
					$html .= '</div>';
					break;
				default:
					break;
			}
			$html .= '</div>'; //demo
			$html .= '</body>';
			$html .= '</html>';
			return $html;
		}
	}
	
	private function getHtmlRow($t_a, $t_b, $t_c, $iso19139Hash, $isoHashIndex){
		$stringToReturn = $t_a."<b>".$iso19139Hash[$isoHashIndex]['html']."</b>: ".$t_b."<span ";
		if (isset($iso19139Hash[$isoHashIndex]['schemaorg_processor'])) {
			switch ($iso19139Hash[$isoHashIndex]['schemaorg_processor']) {
				case "bbox2geo":
					$bboxArray = explode(",", $iso19139Hash[$isoHashIndex]['value']);
					$iso19139Hash[$isoHashIndex]['value'] = $bboxArray[0]." ".$bboxArray[2]." ".$bboxArray[1]." ".$bboxArray[3];
					break;
				case "licenseJson":
					//test and parse json from array 
					$otherConstraints = $iso19139Hash[$isoHashIndex]['value'];
					$licenseFound = false;
					foreach($otherConstraints as $otherConstraint) {
						if ($licenseFound == false) {
							if (json_decode(stripslashes($otherConstraint)) != NULL) {
								$licenseFound = true;
								//parse json
								$standardizedLicense = json_decode(stripslashes($otherConstraint));
								//Look for source
								$URL = $standardizedLicense->url;
								//$this->licenseJson = stripslashes($otherConstraint);
								//$e = new mb_exception("class_iso19139.php: licenseSourceNote: ".$this->licenseSourceNote);
							} else {
								$URL = "No license url found or json not valid!";
							}
						}
					} 
					$iso19139Hash[$isoHashIndex]['value'] = $URL;
					break;
				default:
					break;	
			}
		}
		if (isset($iso19139Hash[$isoHashIndex]['rdfa_content'])){
			//override value with fix content from mapping table
			$iso19139Hash[$isoHashIndex]['value'] = $iso19139Hash[$isoHashIndex]['rdfa_content'];
		}
		//$stringToReturn .= ' vocab="http://schema.org/"';
		if (isset($iso19139Hash[$isoHashIndex]['schemaorg_property'])) {
			$stringToReturn .= ' property="'.$iso19139Hash[$isoHashIndex]['schemaorg_property'].'"';
		}
		if (isset($iso19139Hash[$isoHashIndex]['schemaorg_typeof'])) {
			$stringToReturn .= ' typeof="'.$iso19139Hash[$isoHashIndex]['schemaorg_typeof'].'"';
			if ($iso19139Hash[$isoHashIndex]['schemaorg_typeof'] == "URL") {
				$stringToReturn .= ' href="'.$iso19139Hash[$isoHashIndex]['value'].'"';
			}
		}
		/*if (isset($iso19139Hash[$isoHashIndex]['content'])) {
			$stringToReturn .= ' content="'.$iso19139Hash[$isoHashIndex]['content'].'"';
		}
		if (isset($iso19139Hash[$isoHashIndex]['datatype'])) {
			$stringToReturn .= ' datatype="'.$iso19139Hash[$isoHashIndex]['datatype'].'"';
		}*/
		$stringToReturn .= ">".$iso19139Hash[$isoHashIndex]['value']."</span>".$t_c;
		return $stringToReturn;
	}
	
	private function parseExteriorPolygon($gml3Polygon) {
		//cause postgis gives back polygons without namspace, we have to add a namespace before parsing the xml again :-(
		$gml3Polygon = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>".$gml3Polygon;
		libxml_use_internal_errors(true);
		try {
			$iso19139Xml = simplexml_load_string($gml3Polygon);
			if ($iso19139Xml === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_Iso19139:".$error->message);
    				}
				throw new Exception("class_Iso19139:".'Cannot parse Metadata XML!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_Iso19139:".$e->getMessage());
			return false;
		}			
		//if parsing was successful
		if ($iso19139Xml !== false) {
			//add ns as attribute
			$iso19139Xml->addAttribute('xmlns:xmlns:gml', 'http://www.opengis.net/gml');
			//reloadXML
			$iso19139Xml = simplexml_load_string($iso19139Xml->asXML());
			$iso19139Xml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			$e = new mb_notice("class_iso19139.php: parsing successfull!");
			//<gml:Polygon srsName="EPSG:4326"><gml:exterior><gml:LinearRing><gml:posList srsDimension="2">
			$posListArray = $iso19139Xml->xpath('/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList');
			if (count($posListArray) > 0) {
				$e = new mb_notice("class_iso19139.php: found pos list!");
				//read posList
				$exteriorRingPoints = $posListArray;
				if (count($exteriorRingPoints) > 0) {
					//poslist is only space separated
					$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
					for ($i = 0; $i <= count($exteriorRingPointsArray)/2-1; $i++) {
						$polygonalExtentExterior[$i]['x'] = $exteriorRingPointsArray[2*$i];
						$polygonalExtentExterior[$i]['y'] = $exteriorRingPointsArray[(2*$i)+1];
					}
				$e = new mb_notice("class_iso19139.php: ". count($polygonalExtentExterior) . " point objects!");
				return $polygonalExtentExterior;
				}
			}
		}
		return array();
	} 

	public function createFromDBInternalId($metadataId){
		$sql = "SELECT * , st_xmin(the_geom) || ',' || st_ymin(the_geom) || ',' || st_xmax(the_geom) || ',' || st_ymax(the_geom)  as bbox2d, st_asgml(3,bounding_geom) as bounding_polygon from mb_metadata WHERE metadata_id = $1";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if ($res) {
			$row = db_fetch_assoc($res);
			//fill object with information from mb_metadata table
			//initialize empty iso19139 object
			$this->fileIdentifier = $row['uuid'];
			$this->title = $row['title'];
			$this->abstract = $row['abstract'];
			$this->createDate =  $row['createdate'];//"1900-01-01";
			$this->changeDate = $row['changedate'];//"1900-01-01";
			$this->metadata = $row['data'];
			//some possibilities:
			$this->datasetId = $row['datasetid'];
			$this->datasetIdCodeSpace = $row['datasetid_codespace'];
			if (isset($row['bbox2d']) && $row['bbox2d'] != '') {
				$bbox = $row['bbox2d'];
				//$e = new mb_exception("class_iso19139.php: got bbox for metadata: ".$bbox);
				$this->wgs84Bbox = explode(',',$bbox);
			}
			if (isset($row['bounding_polygon']) && $row['bounding_polygon'] != '') {
				//extract coordinates from gml
				//push them into array
				//store them in object
				//$bbox = str_replace(' ',',',str_replace(')','',str_replace('BOX(','',$row['bbox2d'])));
				//$e = new mb_exception("class_iso19139.php: got bbox for metadata: ".$bbox);
				
				$gml3FromPostgis = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>".$row['bounding_polygon'];
				libxml_use_internal_errors(true);
				try {
					$gml3 = simplexml_load_string($gml3FromPostgis);
					if ($gml3 === false) {
						foreach(libxml_get_errors() as $error) {
        						$err = new mb_exception("class_Iso19139:".$error->message);
    						}
						throw new Exception("class_Iso19139:".'Cannot parse Metadata XML!');
						return false;
					}
				}
				catch (Exception $e) {
    					$err = new mb_exception("class_Iso19139:".$e->getMessage());
					return false;
				}			
				//if parsing was successful
				
				if ($gml3 !== false) {
					$gml3->addAttribute('xmlns:xmlns:gml', 'http://www.opengis.net/gml');
					$gml3->registerXPathNamespace("gml", "http://www.opengis.net/gml");
					$gml3 = simplexml_load_string($gml3->asXML());
					if ($gml3->xpath('//gml:MultiSurface')) {
						$e = new mb_notice("class_Iso19139: MultiSurface found!");
						$this->polygonalExtentExterior = array();
						//count surfaceMembers
						$numberOfSurfaces = count($gml3->xpath('/gml:MultiSurface/gml:surfaceMember'));
						$e = new mb_notice("class_Iso19139: number of polygons: ".$numberOfSurfaces);
						for ($k = 0; $k < $numberOfSurfaces; $k++) {
							$this->polygonalExtentExterior[] = $this->parsePolygon($gml3, '//gml:MultiSurface/gml:surfaceMember/');
						}
					} else { 
						$e = new mb_notice("class_Iso19139: no MultiSurface found!");
						if($gml3->xpath('//gml:Polygon')) {
							$e = new mb_notice("class_Iso19139: number of polygons: 1");
							$this->polygonalExtentExterior = array();
							$this->polygonalExtentExterior[0] = $this->parsePolygon($gml3, '/');
						}
					}
				}
			}
			//fill keywords and categories later cause they are stored in relations!
			/*$this->keywords = array();
			$this->keywordsThesaurusName = array();
			$this->isoCategoryKeys = array();
			//following information is specific to mapbender information model - they are identified by id!
			$this->isoCategories = array();
			$this->inspireCategories = array();
			$this->customCategories = array();
			//*/

			$this->hierarchyLevel = $row['type'];
			$this->tmpExtentBegin = $row['tmp_reference_1'];//"1900-01-01";
			$this->tmpExtentEnd = $row['tmp_reference_2'];//"1900-01-01";
			$this->randomId =  $row['randomid'];
			$this->owner = $row['fkey_mb_user_id']; //dummy entry for metadata owner - in case of metadataURL entries the owner of the corresponding service
                        $this->fkey_mb_group_id = $row['fkey_mb_group_id']; //entry for organization for which this metadata should be published - overwrites metadata point of contact - in case of inheritance by metadata proxy!
			$this->href = $row['link'];// "";
			$this->format = $row['md_format'];//"";
			$this->type = $row['linktype'];//"";
			$this->origin = $row['origin'];//"";
			$this->refSystem = $row['ref_system'];//"";
			$this->harvestResult = $row['harvestresult'];//;0;
			$this->harvestException = $row['harvestexception'];//"";
			$this->lineage = $row['lineage'];//"";
			$this->inspireTopConsistence = $row['inspire_top_consistence'];//"f";
			$this->inspireInteroperability = $row['inspire_interoperability'];//"f";
			$this->searchable = $row['searchable'];//"t";
			$this->spatialResType = $row['spatial_res_type'];//"";
			$this->spatialResValue = $row['spatial_res_value'];//"";
			$this->export2Csw = $row['export2csw'];//"t";
			$this->updateFrequency = $row['update_frequency'];//"";
			$this->dataFormat = $row['format'];//"";
			$this->inspireCharset = $row['inspire_charset'];//"";
			$this->inspireWholeArea = $row['inspire_whole_area'];//"";
			$this->inspireActualCoverage = $row['inspire_actual_coverage'];//"";
			$this->inspireDownload = $row['inspire_download'];//"";
			$this->fees = $row['fees'];
			$this->accessConstraints = $row['constraints'];
			//$test = print_r($row['datalinks'],true);
			//$e = new mb_exception((string)$test);
			$this->downloadLinks  = $this->jsonDecodeDownloadLinks($row['datalinks']);
			//$e = new mb_exception($this->downloadLinks[0]);
			//$this->linkAlreadyInDB = false;
			//$this->fileIdentifierAlreadyInDB = false;
			$this->licenseSourceNote = $row['md_license_source_note'];
			$this->resourceResponsibleParty = $row['responsible_party_name'];
			$this->resourceContactEmail = $row['responsible_party_email'];
			$this->previewImage = $row['preview_image'];
			$this->fkeyGuiId = $row['fkey_gui_id'];
			$this->fkeyWmcSerialId = $row['fkey_wmc_serial_id'];
			$this->fkeyMapviewerId = $row['fkey_mapviewer_id'];
			//get relations from other tables:
			//get categories and keywords
			//get isoCategories
			$sql = <<<SQL
SELECT md_topic_category_id, md_topic_category_code_en FROM mb_metadata_md_topic_category INNER JOIN md_topic_category ON mb_metadata_md_topic_category.fkey_md_topic_category_id = md_topic_category.md_topic_category_id WHERE mb_metadata_md_topic_category.fkey_metadata_id = $1
SQL;
			$v = array($metadataId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			while ($row = db_fetch_assoc($res)) {
				$this->isoCategories[]  = (string)$row['md_topic_category_id'];
				$this->isoCategoriesKeys[] = $row['md_topic_category_code_en'];
			}

			//get custom categories
			$sql = <<<SQL
SELECT custom_category_id FROM mb_metadata_custom_category INNER JOIN custom_category ON mb_metadata_custom_category.fkey_custom_category_id = custom_category.custom_category_id WHERE mb_metadata_custom_category.fkey_metadata_id = $1
SQL;
			$v = array($metadataId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			while ($row = db_fetch_assoc($res)) {
				$this->customCategories[]  = (string)$row['custom_category_id'];
				//generate one keyword entry with special thesaurus 
				$this->keywords[] = $row['fkey_custom_category_id'];
				if (defined("METADATA_DEFAULT_CODESPACE") && METADATA_DEFAULT_CODESPACE != '') {
					$this->keywordsThesaurusName[] = METADATA_DEFAULT_CODESPACE;
				} else {
					$this->keywordsThesaurusName[] = "http://www.mapbender.org";
				}
			}
			//get inspire categories
			$sql = <<<SQL
SELECT inspire_category_id, inspire_category_key FROM mb_metadata_inspire_category INNER JOIN inspire_category ON mb_metadata_inspire_category.fkey_inspire_category_id = inspire_category.inspire_category_id WHERE mb_metadata_inspire_category.fkey_metadata_id = $1
SQL;
			$v = array($metadataId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			while ($row = db_fetch_assoc($res)) {
				$this->inspireCategories[]  = (string)$row['inspire_category_id'];
				$this->keywords[] = $row['inspire_category_key'];
				$this->keywordsThesaurusName[] = "GEMET - INSPIRE themes, version 1.0";
			}
			//get other keywords from keyword table - only fill in, if not already in keywords!
			$sql = <<<SQL
SELECT keyword FROM mb_metadata_keyword INNER JOIN keyword ON mb_metadata_keyword.fkey_keyword_id = keyword.keyword_id WHERE  mb_metadata_keyword.fkey_metadata_id = $1
SQL;
			$v = array($metadataId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			while ($row = db_fetch_assoc($res)) {
				if (!in_array($row['keyword'],$this->keywords)) {
					$this->keywords[] = $row['keyword'];
					$this->keywordsThesaurusName[] = "";
				}
			}
			//get predefined license if given in mapbender database
			$sql = <<<SQL
SELECT fkey_termsofuse_id FROM md_termsofuse WHERE fkey_metadata_id = $1
SQL;
			$v = array($metadataId);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			//pull first entry
			$row = db_fetch_assoc($res);
			$this->termsOfUseRef = $row['fkey_termsofuse_id'];
			if ($this->termsOfUseRef == null) {
				$e = new mb_notice("no predefined license set");
			} else {
				$e = new mb_notice("Predefined license found: ".$this->termsOfUseRef);
			}
		} else {
			$e = new mb_exception("Could not get metadata with id ".$metadataId." from DB!");
		return false;
		}
		//$this->qualifyMetadata();
		return true;
	}

	//the following functions are only for the simple metadata editor
	public function createMetadataAddonFromDB() {
		
	}
	
	public function updateMetadataAddonInDB() {

	}

	//following function is e.g. used by class_wms.php and class_wfsToDb.php
	public function deleteMetadataRelation($resourceType, $resourceId, $relationType){
		//delete all relations which are defined from capabilities - this don't delete the metadata entries themself!
		//all other relations stay alive
		$sql = "DELETE FROM ows_relation_metadata WHERE fkey_".$resourceType."_id = $1 AND relation_type = '".$relationType."'";
		$v = array($resourceId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			$e = new mb_exception("class_Iso19139:"."Cannot delete metadata relations for resource ".$resourceType." with id: ".$resourceId);
			return false;
		} else {
			return true;
		}
	}

	/*public function insertMetadataRelation($resourceType, $resourceId, $relationType){
		//delete all relations which are defined from capabilities - this don't delete the metadata entries themself!
		//all other relations stay alive
		$sql = "DELETE FROM ows_relation_metadata WHERE fkey_".$resourceType."_id = $1 AND relation_type = '".$relationType."'";
		$v = array($resourceId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			$e = new mb_exception("class_Iso19139:"."Cannot delete metadata relations for resource ".$resourceType." with id: ".$resourceId);
			return false;
		} else {
			return true;
		}
	}*/

	public function checkMetadataRelation($resourceType, $resourceId, $metadataId, $origin){
		if ($resourceType !== 'metadata') {
			//check if one relation already exists - if so no new one should be generated!!!
			$sql = "SELECT count(fkey_metadata_id) FROM ows_relation_metadata WHERE fkey_".$resourceType."_id = $1 AND fkey_metadata_id = $2 AND relation_type = $3";
			$v = array($resourceId, $metadataId, $origin);
			$t = array('i','i','s');
			$res = db_prep_query($sql,$v,$t);
			while ($row = db_fetch_array($res)){
				$numberOfRelations = $row['count'];	
			}
			if ($numberOfRelations > 0) {
				return true;
			} else {	
				return false;
			}
		} else {
			return false;
		}
	}

	public function setInternalMetadataLinkage($metadataId,$resourceType, $resourceId){
		//check if internal linkage already exists
		if ($this->checkMetadataRelation($resourceType, $resourceId, $metadataId, 'internal')) {
			$returnObject['success'] = false;
			$returnObject['message'] = _mb("Internal link already exists in database - it will not be created twice!");
			return $returnObject;
		} else {
		//if not create it
		$returnObject = array();
		$sql = "INSERT INTO ows_relation_metadata (fkey_metadata_id, fkey_".$resourceType."_id, internal, relation_type) VALUES ( $1, $2, $3, 'internal')";
		$v = array($metadataId,$resourceId, 1);
		$t = array('i','i','i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $E){
			$returnObject['success'] = false;
			$returnObject['message'] = _mb("Could not insert internal metadatalink into database!");
			return $returnObject;	
		}
		//bequeath categories from coupled resources
		$this->bequeathCategoriesToCoupledResource($metadataId,$resourceType,$resourceId);
		//$e = new mb_exception("iso19139.php: bequeathed categories to ".$resourceType." : ".$resourceId);
		$returnObject['success'] = true;
		$returnObject['message'] = _mb("Internal metadata linkage inserted!");
		return $returnObject;	
		}
	}

	public function deleteInternalMetadataLinkage($resourceType, $resourceId, $metadataId){
		$returnObject = array();
		$sql = "DELETE FROM ows_relation_metadata WHERE fkey_metadata_id = $1 and fkey_".$resourceType."_id = $2 and relation_type  = 'internal'";
		$v = array($metadataId, $resourceId);
		$t = array('i','i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $E){
			$returnObject['success'] = false;
			$returnObject['message'] = _mb("Could not delete internal metadata linkage from database!");
			return $returnObject;	
		}
		//delete categories from coupled resources
		$this->deleteCategoriesFromCoupledResource($metadataId,$resourceType,$resourceId);
		$returnObject['success'] = true;
		$returnObject['message'] = _mb("Internal metadata linkage deleted!");
		return $returnObject;	
	}

	

	public function deleteMetadataAddon($resourceType, $resourceId, $metadataId){
		$returnObject = array();
		$sql = "SELECT count(*) as count FROM ows_relation_metadata WHERE fkey_metadata_id = $1";		
		$v = array($metadataId);
		$t = array('i');
		try {
			$res = db_prep_query($sql,$v,$t);
		}
		catch (Exception $e){
			$returnObject['success'] = false;
			$returnObject['message'] = _mb("Could not determine a count of metadata relations!");
			return $returnObject;	
		}
		while ($row = db_fetch_assoc($res)) {
			$countMetadataLinkage = (integer)$row['count']; //integer
		}
		if ($countMetadataLinkage == 1) {
			$e = new mb_exception("Metadata has only one reference and will be deleted from database if it was created by upload or link!");
			//delete the metadata itself cause it has no other reference - really - not right for metador files !!! 
			$sql = <<<SQL

DELETE FROM mb_metadata WHERE metadata_id = $1 and origin NOT IN ('metador')

SQL;
			$v = array($metadataId);
			$t = array('i');
			try {
				$res = db_prep_query($sql,$v,$t);
			}
			catch (Exception $e){
				$returnObject['success'] = false;
				$returnObject['message'] = _mb("Could not delete metadata from database!");
				return $returnObject;	
			}
			//delete link if metadata was not deleted cause it has been created by editor!
			$sql = "DELETE FROM ows_relation_metadata WHERE fkey_metadata_id = $1 and fkey_".$resourceType."_id = $2";
			$v = array($metadataId, $resourceId);
			$t = array('i','i');
			try {
				$res = db_prep_query($sql,$v,$t);
			}
			catch (Exception $E){
				$returnObject['success'] = false;
				$returnObject['message'] = _mb("Could not delete internal metadata linkage from database!");
				return $returnObject;	
			}
			//delete categories from coupled resources
			$this->deleteCategoriesFromCoupledResource($metadataId,$resourceType,$resourceId);
			$returnObject['success'] = true;
			$returnObject['message'] = _mb("Metadata and/or linkage was deleted from database!");
			return $returnObject;	
		} else {
			//delete only linkage
			$sql = "DELETE FROM ows_relation_metadata WHERE fkey_metadata_id = $1 and fkey_".$resourceType."_id = $2";
			$v = array($metadataId, $resourceId);
			$t = array('i','i');
			try {
				$res = db_prep_query($sql,$v,$t);
			}
			catch (Exception $E){
				$returnObject['success'] = false;
				$returnObject['message'] = _mb("Could not delete metadata linkage from database!");
				return $returnObject;
			}
			//delete categories from coupled resources
			$this->deleteCategoriesFromCoupledResource($metadataId,$resourceType,$resourceId);
			$returnObject['success'] = true;
			$returnObject['message'] = _mb("Metadata linkage deleted!");
			return $returnObject;
		}
	}

	public function isLinkAlreadyInDB(){
		$sql = <<<SQL
SELECT metadata_id FROM mb_metadata WHERE link = $1 AND link <> '' AND link IS NOT NULL ORDER BY lastchanged DESC
SQL;
		$v = array(
			$this->href
		);
		$t = array('s');
		$res = db_prep_query($sql,$v,$t);
		while ($row = db_fetch_array($res)){
			$metadataId[] = $row['metadata_id'];	
		}
		if (count($metadataId) > 0 && count($metadataId) < 2) {
			return $metadataId[0];
		} else {	
			return false;
		}
	}
	
	public function isFileIdentifierAlreadyInDB(){
		$sql = <<<SQL
SELECT metadata_id, createdate FROM mb_metadata WHERE uuid = $1 AND uuid <> '' AND uuid IS NOT NULL ORDER BY lastchanged DESC LIMIT 1
SQL;
		if (!isset($this->fileIdentifier) || $this->fileIdentifier == '') {
			$e = new mb_exception("class_Iso19139:"."Empty or no fileIdentifier found in the metadata! No metadataset will be updated");
			return false;
		}
		$v = array(
			$this->fileIdentifier
		);
		$t = array('s');
		$res = db_prep_query($sql,$v,$t);
		$metadataId = array();
		while ($row = db_fetch_array($res)){
			$metadataId[] = $row['metadata_id'];	
		}
		if (count($metadataId) > 0 && count($metadataId) < 2) {
			return $metadataId[0];
		} else {	
			return false;
		}
	}
	
	private function createWktBboxFromArray($bboxArray) { //-180 -90 180 90 
		$postGisBbox = "";
		if (count($bboxArray) != 4 || $bboxArray[0] == '') {
			//create dummy bbox
			$bboxArray = array(-180,-90,180,90);
		}
		//"SRID=4326;POLYGON((-140 -80,-140 80,170 80,170 -80,-140 -80))"
		$postGisBbox = "SRID=4326;POLYGON((".$bboxArray[0]." ".$bboxArray[1].",".$bboxArray[0]." ".$bboxArray[3].",".$bboxArray[2]." ".$bboxArray[3].",".$bboxArray[2]." ".$bboxArray[1].",".$bboxArray[0]." ".$bboxArray[1]."))";
		return $postGisBbox;
	}

	public function createWktPolygonFromPointArray($pointArray) { //-180 -90 180 90 
		if ($pointArray == null) {
			return null;
		}
		$postGisPolygon = "";
		if (count($pointArray) > 1) { //multisurface
			$postGisPolygon = "SRID=4326;MULTIPOLYGON(((";
			foreach ($pointArray as $polygon) {
					foreach($polygon as $point) {
						$postGisPolygon .= trim($point['x'])." ".trim($point['y']).",";
					}
					$postGisPolygon = rtrim($postGisPolygon,',')."),(";	
			}
			$postGisPolygon = rtrim($postGisPolygon,'(,')."))";
		} else {
			//"SRID=4326;POLYGON((-140 -80,-140 80,170 80,170 -80,-140 -80))"
			$postGisPolygon = "SRID=4326;POLYGON((";
			if (count($pointArray) == 0) {
				return null;
 			} else {
				//only use first polygon entry
				$pointArray = $pointArray[0];
				foreach ($pointArray as $point) {
					$postGisPolygon .= trim($point['x'])." ".trim($point['y']).",";
				}
				$postGisPolygon = rtrim($postGisPolygon,',');
				$postGisPolygon .= "))";
			}
		}
		//$e = new mb_exception("class_Iso19139.php: wkt: ".$postGisPolygon);
		return $postGisPolygon;
	}

	public function inheritContactAndLicenceInformation($metadataId,$resourceType,$resourceId,$inheritContactInfo,$inheritLicenceInfo){
		//$e = new mb_exception("class_iso19139.php: inherit function invoked!");
		if ($inheritContactInfo == true || $inheritLicenceInfo == true) {
			//get info from coupled service
			switch ($resourceType) {
				case "layer":
					$sql = "SELECT fkey_mb_group_id, wms.wms_id as service_id, fkey_termsofuse_id, wms_license_source_note as service_source_note FROM wms LEFT JOIN wms_termsofuse ON wms.wms_id = wms_termsofuse.fkey_wms_id WHERE wms_id = (SELECT fkey_wms_id FROM layer WHERE layer_id = $resourceId)";
					break;
				case "featuretype":
					$sql = "SELECT fkey_mb_group_id, wfs.wfs_id as service_id, fkey_termsofuse_id, wfs_license_source_note as service_source_note FROM wfs LEFT JOIN wfs_termsofuse ON wfs.wfs_id = wfs_termsofuse.fkey_wfs_id WHERE wfs_id = (SELECT fkey_wfs_id FROM wfs_featuretype WHERE featuretype_id = $resourceId)";
					break;
			}
			$res = db_query($sql);
			while ($row = db_fetch_array($res)){
				$fkey_mb_group_id = $row['fkey_mb_group_id'];
				$service_id = $row['service_id'];
				$fkey_termsofuse_id = $row['fkey_termsofuse_id'];
				$service_source_note = $row['service_source_note'];
			}
			//regexpr for strings which represents integer
			$regExprInt = "/^[0-9]+$/";
		}
		if ($inheritContactInfo == true && preg_match($regExprInt,$fkey_mb_group_id)) {
			$sqlInheritContact = "UPDATE mb_metadata SET fkey_mb_group_id = $fkey_mb_group_id WHERE metadata_id = $metadataId";
			$res = db_query($sqlInheritContact);
			//$e = new mb_exception("Metadata with id ".$metadataId." inherits contact information from ".$resourceType." with resourceId ".$resourceId);
		}
		if ($inheritLicenceInfo == true && preg_match($regExprInt,$fkey_termsofuse_id)) {
			try {
				//delete own termsofuse if exists
				$sqlDeleteTouRelation = "DELETE FROM md_termsofuse WHERE fkey_metadata_id = $metadataId";
				$res = db_query($sqlDeleteTouRelation);
				//insert service termsofuse relation
				$sqlInsertTouRelation = "INSERT INTO md_termsofuse (fkey_termsofuse_id, fkey_metadata_id) VALUES ($fkey_termsofuse_id,$metadataId)";
				$res = db_query($sqlInsertTouRelation); 
				//fill in source note from service
				$sqlUpdateSourceNote = "UPDATE mb_metadata SET md_license_source_note = $1 WHERE metadata_id = $2";
				$t = array('s', 'i');
				$v = array($service_source_note, $metadataId);
				$res = db_prep_query($sqlUpdateSourceNote,$v,$t);
				//$e = new mb_exception("Metadata with id ".$metadataId." inherits licence information from ".$resourceType." with resourceId ".$resourceId);
			}
			catch (Exception $e) {
				$exception = new mb_exception($e->getMessage());
			}
		}
	}

	public function insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId){
		//first delete old classifications - after that create new ones
		//$e = new mb_exception($metadataId." - ".$resourceType." - ".$resourceId);	
		$this->deleteKeywordsAndCategoriesFromDB($metadataId,$resourceType,$resourceId);
		//insert keywords and categories into tables!
		//parse keywords and isoCategories
		//INSERT INTO films (code, title, did, date_prod, kind) VALUES
    		//('B6717', 'Tampopo', 110, '1985-02-10', 'Comedy'),
    		//('HG120', 'The Dinner Game', 140, DEFAULT, 'Comedy');
		//use category name instead of id's!
		//map category name to id before insert them into db!
		//read maptable from db
		//iso - code in xml
		if ($resourceType == "wms" || $resourceType == "wfs" || $resourceType == "inspire_dls_atom" || $resourceType == "inspire_dls_atom_dataset") {
			return false;
		}
		switch ($resourceType) {
			case "featuretype":
				$tablePrefix = 'wfs_featuretype';
			break;
			case "layer":
				$tablePrefix = 'layer';
			break;
		}
		//check if categories are arrays or not, if not parse as integer and safe as array with one element
		if (!is_array($this->isoCategories)) {
			$intCategory = (integer)$this->isoCategories;
			$this->isoCategories = array();
			$this->isoCategories[0] = $intCategory;
		}
		if (!is_array($this->inspireCategories)) {
			$intCategory = (integer)$this->inspireCategories;
			$this->inspireCategories = array();
			$this->inspireCategories[0] = $intCategory;
		}
		if (!is_array($this->customCategories)) {
			$intCategory = (integer)$this->customCategories;
			$this->customCategories = array();
			$this->customCategories[0] = $intCategory;
		}
		//map keys into relevant ids
		$sqlInsert = "";
		if (count($this->isoCategories) > 0 && $this->customCategories[0] !== 0) {
			if (count($this->isoCategories) == 1) {
				$sqlInsert .= "(".(integer)$metadataId.",".(integer)$this->isoCategories[0].")";
			} else {
				foreach ($this->isoCategories as $isoCategory) {
					$sqlInsert .= "(".(integer)$metadataId.",".(integer)$isoCategory."),";
				}
			}
			$sqlInsert = rtrim($sqlInsert,",");
			//$e = new mb_exception("class_Iso19139: insert topic categories: ".$sqlInsert);
			$sql = "INSERT INTO mb_metadata_md_topic_category (fkey_metadata_id, fkey_md_topic_category_id) VALUES ".$sqlInsert;
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_Iso19139:"._mb("Cannot insert iso categories for this metadata!"));
			}
		}
		$sqlInsert = "";
		if (count($this->inspireCategories) > 0 && $this->inspireCategories[0] !== 0) {
			if (count($this->inspireCategories) == 1) {
				$sqlInsert .= "(".(integer)$metadataId.",".(integer)$this->inspireCategories[0].")";
			} else {
				foreach ($this->inspireCategories as $inspireCategory) {
					$sqlInsert .= "(".(integer)$metadataId.",".(integer)$inspireCategory."),";
				}
			}
			$sqlInsert = rtrim($sqlInsert,",");
			$sql = "INSERT INTO mb_metadata_inspire_category (fkey_metadata_id, fkey_inspire_category_id) VALUES ".$sqlInsert;
			//$e = new mb_exception("class_Iso19139: insert inspire categories: ".$sqlInsert);
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_Iso19139:"._mb("Cannot insert inspire categories for this metadata!"));
			}
		}
		$sqlInsert = "";
		if (count($this->customCategories) > 0 && $this->customCategories[0] !== 0) {
			if (count($this->customCategories) == 1) {
				$sqlInsert .= "(".(integer)$metadataId.",".(integer)$this->customCategories[0].")";
			} else {
				foreach ($this->customCategories as $customCategory) {
					$sqlInsert .= "(".(integer)$metadataId.",".(integer)$customCategory."),";
				}
			}	
			$sqlInsert = rtrim($sqlInsert,",");
			$sql = "INSERT INTO mb_metadata_custom_category (fkey_metadata_id, fkey_custom_category_id) VALUES ".$sqlInsert;
			//$e = new mb_exception("class_Iso19139: insert custom categories: ".$sqlInsert);
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_Iso19139:"._mb("Cannot insert custom categories for this metadata!"));
			}
		}
		if ($resourceType == 'layer' || $resourceType == 'featuretype') {
			$this->bequeathCategoriesToCoupledResource($metadataId,$resourceType,$resourceId);	
		}
		$sqlInsert = "";
		//insert keywords into keyword table
		//foreach keyword look for an id or create it newly - made for postgres > 8.2 with returning option for insert statement
		$keyword = "'";
		$keyword .= implode('\',\'',$this->keywords);
		$keyword .= "'";
		$existingKeywords = array();
		$sql = "SELECT keyword, keyword_id from keyword WHERE keyword in ($keyword);";
		$res = db_query($sql);
		if (!$res) {
			$e = new mb_exception("class_Iso19139.php: cannot get keywords from database!");
		} else {
			$countExistingKeywords = 0;
			while ($row = db_fetch_assoc($res)) {
				$existingKeywords[$countExistingKeywords]['keyword'] = $row['keyword'];
				//$e = new mb_exception("existing keyword: ".$row['keyword']);
				$existingKeywords[$countExistingKeywords]['id'] = $row['keyword_id'];
				$existingKeywordsArray[$countExistingKeywords] = $row['keyword'];
				$countExistingKeywords++;
			}
		}
		/*foreach ($existingKeywords as $test) {
			$e = new mb_exception("exists: ".$test['keyword']);
		}*/
		//for each existing keyword add a new relation into relation table
		if ($countExistingKeywords > 0) {
			if ($countexistingKeywords == 1) {
				$sqlInsert .= "(".(integer)$metadataId.",".(integer)$existingKeywords[0]['id'].")";
			} else {
				foreach ($existingKeywords as $existingKeyword) {
					$sqlInsert .= "(".(integer)$metadataId.",".(integer)$existingKeyword['id']."),";
				}
			}	
			$sqlInsert = rtrim($sqlInsert,",");
			$sql = "INSERT INTO mb_metadata_keyword (fkey_metadata_id, fkey_keyword_id) VALUES ".$sqlInsert;
			//$e = new mb_exception("class_Iso19139.php: sql for keywords: ".$sql);
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_Iso19139:"._mb("Cannot insert keyword relations for this metadata!"));
			} else {
				$e = new mb_notice("class_Iso19139:"._mb("Inserted keyword relations for existing keywords!"));
			}
		}
		//insert those keywords, that are not already in the keyword table
		//$this->keywords
		/*$array1 = array("a" => "grün", "rot", "blau", "rot");
		$array2 = array("b" => "grün", "gelb", "rot");
		$result = array_diff($array1, $array2);
		Array
			(
			    [1] => blau
			)
		*/
		$sqlInsert = "";
		//test if $existingKeywordsArray is array!
		if (is_array($existingKeywordsArray)) {
			$otherKeywords = array_values(array_diff($this->keywords,$existingKeywordsArray));
		} else {
			$otherKeywords = $this->keywords;
		} 
		//debug
		/*foreach ($otherKeywords as $test) {
			$e = new mb_exception("otherKeywords: ".$test);
		}
		$e = new mb_exception("otherKeywords: ".$otherKeywords);*/
		if (count($otherKeywords) > 0) {
			if (count($otherKeywords) == 1) {
				//$e = new mb_exception("Only one new keyword found: ".$otherKeywords[0]);
				//keyword table
				$sqlInsert .= "('".$otherKeywords[0]."')";
			} else {
				foreach ($otherKeywords as $otherKeyword) {
					$sqlInsert .= "('".$otherKeyword."'),";
				}
			}	
			$sqlInsert = rtrim($sqlInsert,",");
			$sql = "INSERT INTO keyword (keyword) VALUES ".$sqlInsert." RETURNING keyword_id" ;
			//$e = new mb_exception("class_Iso19139.php: sql for keywords: ".$sql);
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_notice("class_Iso19139:"._mb("Cannot insert new keywords into keyword table - will insert only new relations!"));
			} else {
				//insert relations for keywords
				$sqlInsert = "";
				$insertedKeywords = array();
				while ($row = db_fetch_assoc($res)) {
					$insertedKeywordIds[] = $row['keyword_id'];
				}
				if (count($insertedKeywordIds) == 1) {
					//keyword relation table
					$sqlInsert .= "(".(integer)$metadataId.",".$insertedKeywordIds[0].")";
				} else {
					foreach ($insertedKeywordIds as $insertedKeywordId) {
						$sqlInsert .= "(".(integer)$metadataId.",".(integer)$insertedKeywordId."),";
					}
				}	
				$sqlInsert = rtrim($sqlInsert,",");
				$sql = "INSERT INTO mb_metadata_keyword (fkey_metadata_id, fkey_keyword_id) VALUES ".$sqlInsert;
				//$e = new mb_notice("class_Iso19139.php: sql for keyword relation: ".$sql);
				$res = db_query($sql);
				if (!$res) {
					$e = new mb_exception("class_Iso19139:"._mb("Cannot insert metadata keyword relations into db!"));
				}
			}
		}
		//handle licenses as relations
		//delete relations from database
		//recreate relation to license
		//relation is set via json id
		//check for defined license id via editor or s.th. else
		$licenseId = $this->termsOfUseRef;
		if ($this->termsOfUseRef == null) {
			//search for given json license
			if ($this->licenseJson != null) {
				$licenseName = json_decode($this->licenseJson)->id;
				if ($licenseName != null) {
					//search for same license name in database - if not given create it!
					$sql = <<<SQL
					SELECT termsofuse_id from termsofuse WHERE name = $1
SQL;
					$v = array($licenseName);
					$t = array('s');
					$res = db_prep_query($sql,$v,$t);
					$row = db_fetch_assoc($res);
					$licenseId = $row['termsofuse_id'];
					//if license not found in json string
					if ($licenseId == null) {
						//if all relevant information is given in json - create a new entry in termsofuse table (id, name, url
						$licenseJson = json_decode($this->licenseJson);
						//check if all fields are there
						if ($licenseJson->id != null && $licenseJson->id != "" && $licenseJson->name != null && $licenseJson->name != "" && $licenseJson->url != null && $licenseJson->url != "") {
							//insert entry into db - license should be open
							$sql = <<<SQL
							INSERT INTO termsofuse (name, description, descriptionlink) VALUES ($1, $2, $3);
SQL;
							$v = array($licenseJson->id,$licenseJson->name,$licenseJson->url);
							$t = array('s','s','s');
							$res = db_prep_query($sql,$v,$t);
							if (!$res) {
								$e = new mb_exception("classes/class_iso19139.php: Cannot create termsofuse entry from given json license in metadata!");
								$licenseId = null;
							} else {
								$sql = <<<SQL
					SELECT termsofuse_id from termsofuse WHERE name = $1
SQL;
								$v = array($licenseJson->id);
								$t = array('s');
								$res = db_prep_query($sql,$v,$t);
								$row = db_fetch_assoc($res);
								$licenseId = $row['termsofuse_id'];
							}
						}
					}

				}
			}	
		}
		//$e = new mb_exception("iso19139 license id: ".$this->termsOfUseRef);
		if ($licenseId !== null) {
			$sql = <<<SQL
			INSERT INTO md_termsofuse (fkey_termsofuse_id, fkey_metadata_id) VALUES ($1, $2);
SQL;
			$v = array((integer)$licenseId,(integer)$metadataId);
			$t = array('i','i');
			$res = db_prep_query($sql,$v,$t);
			if (!$res){
				$e = new mb_exception("classes/class_Iso19139.php: "._mb("Cannot insert termsofuse relation!"));
			}
		} else {
			$e = new mb_notice("classes/class_Iso19139.php: license id is null!");
		}
		
	}

	public function deleteCategoriesFromCoupledResource($metadataId,$resourceType,$resourceId) {
		if ($resourceType == 'inspire_dls_atom' || $resourceType == 'inspire_dls_atom_dataset' || $resourceType == 'metadata' || $resourceType == 'application') {
			return false;
		}
		//delete inherited categories from coupled resources: layer/featuretype
		switch ($resourceType) {
			case "featuretype":
				$tablePrefix = 'wfs_featuretype';
			break;
			case "layer":
				$tablePrefix = 'layer';
			break;
		}
		$types = array("md_topic", "inspire", "custom");
		foreach ($types as $cat) {
			$sql = "DELETE FROM ".$tablePrefix."_{$cat}_category WHERE fkey_metadata_id = $1 AND fkey_".$resourceType."_id = $2";
			$v = array($metadataId,$resourceId);
			$t = array('i','i');
			$res = db_prep_query($sql,$v,$t);
			if(!$res){
				$e = new mb_exception("class_Iso19139:"._mb("Cannot delete categories from ".$resourceType." with id ".$resourceId));
			}
		}
	}

	public function bequeathCategoriesToCoupledResource($metadataId,$resourceType,$resourceId) {
		//delete inherited categories from coupled resources: layer/featuretype
		switch ($resourceType) {
			case "featuretype":
				$tablePrefix = 'wfs_featuretype';
			break;
			case "layer":
				$tablePrefix = 'layer';
			break;
		}
		//all categories
		$types = array("md_topic", "inspire", "custom");
		
		foreach ($types as $cat) {
			switch ($cat) {
				case "md_topic":
					$objectPrefix = 'iso';
				break;
				default:
					$objectPrefix = $cat;
				break;
			}
			$sqlInsertCoupledResource = "";
			if (count($this->{$objectPrefix."Categories"}) > 0 && $this->{$objectPrefix."Categories"}[0] !== 0) {
				if (count($this->{$objectPrefix."Categories"}) == 1) {
					$sqlInsertCoupledResource .= "(".(integer)$metadataId.",".(integer)$this->{$objectPrefix."Categories"}[0].",".$resourceId.")";
				} else {
					foreach ($this->{$objectPrefix."Categories"} as ${$objectPrefix."Category"}) {
						$sqlInsertCoupledResource .= "(".(integer)$metadataId.",".(integer)${$objectPrefix."Category"}.",".$resourceId."),";
					
					}
				}	
				$sqlInsertCoupledResource = rtrim($sqlInsertCoupledResource,",");
				$sqlCoupledResource = "INSERT INTO ".$tablePrefix."_{$cat}_category (fkey_metadata_id, fkey_{$cat}_category_id, fkey_".$resourceType."_id) VALUES ".$sqlInsertCoupledResource;
				//$e = new mb_exception("class_Iso19139: sql to bequeath categories: ".$sqlCoupledResource);
				$res = db_query($sqlCoupledResource);
				if (!$res) {
					$e = new mb_exception("class_Iso19139:"._mb("Cannot insert $cat categories to coupled resource!"));
				}
			}
		}
	}

	public function deleteKeywordsAndCategoriesFromDB($metadataId,$resourceType,$resourceId) {
		$sql = "DELETE FROM mb_metadata_md_topic_category where fkey_metadata_id = $1 ";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("class_Iso19139:"._mb("Cannot delete topic category relations for metadata with id ".$metadataId));
		} else {
			$e = new mb_notice("class_Iso19139: topic category relations deleted from database!");
		}
		$sql = "DELETE FROM mb_metadata_inspire_category where fkey_metadata_id = $1 ";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("class_Iso19139:"._mb("Cannot delete inspire category relations for metadata with id ".$metadataId));
		} else {
			$e = new mb_notice("class_Iso19139: inspire category relations deleted from database!");
		}
		$sql = "DELETE FROM mb_metadata_custom_category where fkey_metadata_id = $1 ";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("class_Iso19139:"._mb("Cannot delete custom category relations for metadata with id ".$metadataId));
		} else {
			$e = new mb_notice("class_Iso19139: custom category relations deleted from database!");
		}
		//delete keyword relations - problem, that keywords are referenced from more than one table. We can only delete the relations but there may be orphaned keywords, which have to be deleted by cronjob - maybe - TODO
		$sql = "DELETE FROM mb_metadata_keyword where fkey_metadata_id = $1 ";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("class_Iso19139:"._mb("Cannot delete keyword relations for metadata with id ".$metadataId));
		} else {
			$e = new mb_notice("class_Iso19139: keyword relations deleted from database!");
		}
		//delete license relation from db
		$sql = "DELETE FROM md_termsofuse where fkey_metadata_id = $1 ";
		$v = array($metadataId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("class_Iso19139.php:"._mb("Cannot delete termsofuse relation for metadata with id ".$metadataId));
		} else {
			$e = new mb_notice("class_Iso19139.php: termsofuse relation deleted from database!");
		}
		$this->deleteCategoriesFromCoupledResource($metadataId,$resourceType,$resourceId);
	}

	public function insertMetadataIntoDB() {
		//insert an instance for iso19139 into mapbenders database
		$e = new mb_notice("class_iso19139.php: insert metadata with title: ".$this->title);
		$sql = <<<SQL
INSERT INTO mb_metadata (lastchanged, link, origin, md_format, data, linktype, uuid, title, createdate, changedate, abstract, searchtext, type, tmp_reference_1, tmp_reference_2, export2csw, datasetid, datasetid_codespace, randomid, fkey_mb_user_id, harvestresult, harvestexception, lineage, inspire_top_consistence, spatial_res_type, spatial_res_value, update_frequency, format, inspire_charset, ref_system, the_geom, datalinks, inspire_whole_area, inspire_actual_coverage, inspire_download, bounding_geom, transfer_size, fees, md_license_source_note, constraints, responsible_party_name, responsible_party_email, preview_image, fkey_mb_group_id, md_proxy, inspire_interoperability, searchable, fkey_gui_id, fkey_wmc_serial_id, fkey_mapviewer_id)  VALUES(now(), $1, $18, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32, $33, $34, $35, $36, $37, $38, $39, $40, $41, $42, $43, $44, $45, $46, $47, $48, $49)
SQL;
		$v = array(
			$this->href,
			$this->format,
			$this->metadata,
			$this->type,
			$this->fileIdentifier,
			$this->title,
			$this->createDate,
			$this->changeDate,
			$this->abstract,
			$this->keywords[0],
			$this->hierarchyLevel,
			$this->tmpExtentBegin,
			$this->tmpExtentEnd,
			$this->export2Csw,
			$this->datasetId,
			$this->datasetIdCodeSpace,
			$this->randomId,
			$this->origin,
			$this->owner,				
			$this->harvestResult,
			$this->harvestException,
			$this->lineage,
			$this->inspireTopConsistence,
			$this->spatialResType,
			$this->spatialResValue,
			$this->updateFrequency,
			$this->dataFormat,
			$this->inspireCharset,
			$this->refSystem,
			$this->createWktBboxFromArray($this->wgs84Bbox),
			$this->jsonEncodeDownloadLinks($this->downloadLinks),
			$this->inspireWholeArea,
			$this->inspireActualCoverage,
			$this->inspireDownload,
			$this->createWktPolygonFromPointArray($this->polygonalExtentExterior),
			$this->transferSize,
			$this->fees,
			$this->licenseSourceNote,
			$this->accessConstraints,
			$this->resourceResponsibleParty,
			$this->resourceContactEmail,
			$this->previewImage,
			$this->fkey_mb_group_id,
			$this->mdProxy,
			$this->inspireInteroperability,
			$this->searchable,
			$this->fkeyGuiId,
			$this->fkeyWmcSerialId,
			$this->fkeyMapviewerId
		);
			//$e = new mb_exception($this->tmpExtentBegin);
			//$e = new mb_exception($this->tmpExtentEnd);
			//$e = new mb_exception($this->createDate);
			//$e = new mb_exception($this->changeDate);
			$t = array('s','s','s','s','s','s','s','s','s','s','s','s','s','b','s','s','s','s','i','i','s','s','b','s','s','s','s','s','s','POLYGON','s','s','s','i','POLYGON','d','s','s','s','s','s','s','i','b','b','b','i','i','i');
			$res = db_prep_query($sql,$v,$t);
			return $res;
	}

	private function jsonEncodeDownloadLinks($dlArray) {
		$dummy = new stdClass();
		//TODO - check array conversion of json_encode under php > 5.4
		//$dummy->downloadLinks = (string)$dlArray[0];
		//return json_encode($dummy, JSON_FORCE_OBJECT);
		$json = '{"downloadLinks":[';
		$i = 0;
		//TODO solve the php 5.4 json_encode array problem
		foreach ($dlArray as $link) {
			$json .= '{"'.$i.'":'.json_encode((string)$link).'},';
			$i++;
		}
		$json = rtrim($json,",");
		$json .= "]}";
		return $json;
	}

	private function getWfsVersionForFeaturetype($featuretypeId) {
		$sql = "SELECT wfs.wfs_version FROM wfs_featuretype INNER JOIN wfs ON wfs_featuretype.fkey_wfs_id = wfs.wfs_id WHERE wfs_featuretype.featuretype_id = $1";
		$v = array($featuretypeId);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("No wfs version found for featuretype with id:".$featuretypeId);
			return false;
		} else {
			$row = db_fetch_assoc($res);
			return $row['wfs_version'];
		}
	}

	//TODO - maybe we will encode more things than only the url ;-)
	private function jsonDecodeDownloadLinks($dlJson) {
		$downloadLinks = json_decode($dlJson);
		//new for php 5.4
		$dlArray = array();
		$i = 0;
		foreach ($downloadLinks->downloadLinks as $downloadLink) {
			$dlArray[] = urldecode($downloadLink->{$i});
			$i++;
		}
		//$dummy = $dummy->downloadLinks;
		return $dlArray;
	}

	public function updateMetadataById($metadataId, $changeAuthorization=true) {
		$e = new mb_notice("update metadata id: ".$metadataId);
		//check if the timestamp of an existing metadata element is not newer than the timestamp of the current metadata object!!!!
		//TODO
		//problem: <<<SQL have a limited number of chars!
		if ($changeAuthorization == true) {
		    $sql = "UPDATE mb_metadata SET link = $1, origin = $18, md_format = $2, data = $3, ";
		    $sql .= "linktype = $4, uuid = $5, title = $6, createdate = $7, changedate = $8, lastchanged = now(), ";
		    $sql .= "abstract = $9, searchtext = $10, type = $11, tmp_reference_1 = $12, tmp_reference_2 = $13, export2csw = $14, datasetid = $15, ";
		    $sql .= "datasetid_codespace = $16, randomid = $17, harvestresult = $20, harvestexception = $21, lineage = $22, inspire_top_consistence = $23, ";
		    $sql .= "spatial_res_type = $24, spatial_res_value = $25, update_frequency = $26, format = $27, inspire_charset = $28, ref_system = $29, the_geom = $30, datalinks = $31, inspire_whole_area = $32, inspire_actual_coverage = $33, inspire_download = $34, bounding_geom = $35, transfer_size = $36, fees = $37, md_license_source_note = $38, constraints = $39, responsible_party_name = $40, responsible_party_email = $41, preview_image = $42, fkey_mb_group_id = $43, md_proxy = $44, inspire_interoperability = $45, searchable = $46, fkey_gui_id = $47, fkey_wmc_serial_id = $48, fkey_mapviewer_id = $49 WHERE metadata_id = $19";
		    //$e= new mb_exception("class_iso19139.php: downloadLinks json".$this->jsonEncodeDownloadLinks($this->downloadLinks));
		    //$e= new mb_exception("class_iso19139.php: downloadLinks[0]".$this->downloadLinks[0]);
		    $v = array(
			$this->href,
			$this->format,
			$this->metadata,
			$this->type,
			$this->fileIdentifier, // is the old one! or not?
			$this->title,
			$this->createDate,
			$this->changeDate,
			$this->abstract,
			$this->keywords[0],
			$this->hierarchyLevel,
			$this->tmpExtentBegin,
			$this->tmpExtentEnd,
			$this->export2Csw,
			$this->datasetId,
			$this->datasetIdCodeSpace,
			$this->randomId,
			$this->origin,
			//$this->owner, //owner is the old one - maybe here we have something TODO!
			$metadataId, //The first metadataId which was found will be selected!
			$this->harvestResult,
			$this->harvestException,
			$this->lineage,
			$this->inspireTopConsistence,
			$this->spatialResType,
			$this->spatialResValue,
			$this->updateFrequency,
			$this->dataFormat,
			$this->inspireCharset,
			$this->refSystem,
			$this->createWktBboxFromArray($this->wgs84Bbox),
			$this->jsonEncodeDownloadLinks($this->downloadLinks),
			$this->inspireWholeArea,
			$this->inspireActualCoverage,
			$this->inspireDownload,
			$this->createWktPolygonFromPointArray($this->polygonalExtentExterior),
			$this->transferSize,
			$this->fees,
			$this->licenseSourceNote,
			$this->accessConstraints,
			$this->resourceResponsibleParty,
			$this->resourceContactEmail,
			$this->previewImage,
			$this->fkey_mb_group_id,
			$this->mdProxy,
			$this->inspireInteroperability,
			$this->searchable,
			$this->fkeyGuiId,
			$this->fkeyWmcSerialId,
			$this->fkeyMapviewerId
		    );
		    //$e = new mb_exception("class_iso19139: ".$this->createWktBboxFromArray($this->wgs84Bbox));
		    $t = array('s','s','s','s','s','s','s','s','s','s','s','s','s','b','s','s','s','s','i','i','s','s','b','s','s','s','s','s','s','POLYGON','s','s','s','i','POLYGON','d','s','s','s','s','s','s','i','b','b','b','i','i','i');
		    $res = db_prep_query($sql,$v,$t);
		} else { //do the update without changing owner and fkey_mb_group_id!
		    $sql = "UPDATE mb_metadata SET link = $1, origin = $18, md_format = $2, data = $3, ";
		    $sql .= "linktype = $4, uuid = $5, title = $6, createdate = $7, changedate = $8, lastchanged = now(), ";
		    $sql .= "abstract = $9, searchtext = $10, type = $11, tmp_reference_1 = $12, tmp_reference_2 = $13, export2csw = $14, datasetid = $15, ";
		    $sql .= "datasetid_codespace = $16, randomid = $17, harvestresult = $20, harvestexception = $21, lineage = $22, inspire_top_consistence = $23, ";
		    $sql .= "spatial_res_type = $24, spatial_res_value = $25, update_frequency = $26, format = $27, inspire_charset = $28, ref_system = $29, the_geom = $30, datalinks = $31, inspire_whole_area = $32, inspire_actual_coverage = $33, inspire_download = $34, bounding_geom = $35, transfer_size = $36, fees = $37, md_license_source_note = $38, constraints = $39, responsible_party_name = $40, responsible_party_email = $41, preview_image = $42, md_proxy = $43, inspire_interoperability = $44, searchable = $45, fkey_gui_id = $46, fkey_wmc_serial_id = $47, fkey_mapviewer_id = $48 WHERE metadata_id = $19";
		    //$e= new mb_exception("class_iso19139.php: downloadLinks json".$this->jsonEncodeDownloadLinks($this->downloadLinks));
		    //$e= new mb_exception("class_iso19139.php: downloadLinks[0]".$this->downloadLinks[0]);
		    $v = array(
			$this->href,
			$this->format,
			$this->metadata,
			$this->type,
			$this->fileIdentifier, // is the old one! or not?
			$this->title,
			$this->createDate,
			$this->changeDate,
			$this->abstract,
			$this->keywords[0],
			$this->hierarchyLevel,
			$this->tmpExtentBegin,
			$this->tmpExtentEnd,
			$this->export2Csw,
			$this->datasetId,
			$this->datasetIdCodeSpace,
			$this->randomId,
			$this->origin,
			//$this->owner, //owner is the old one - maybe here we have something TODO!
			$metadataId, //The first metadataId which was found will be selected!
			$this->harvestResult,
			$this->harvestException,
			$this->lineage,
			$this->inspireTopConsistence,
			$this->spatialResType,
			$this->spatialResValue,
			$this->updateFrequency,
			$this->dataFormat,
			$this->inspireCharset,
			$this->refSystem,
			$this->createWktBboxFromArray($this->wgs84Bbox),
			$this->jsonEncodeDownloadLinks($this->downloadLinks),
			$this->inspireWholeArea,
			$this->inspireActualCoverage,
			$this->inspireDownload,
			$this->createWktPolygonFromPointArray($this->polygonalExtentExterior),
			$this->transferSize,
			$this->fees,
			$this->licenseSourceNote,
			$this->accessConstraints,
			$this->resourceResponsibleParty,
			$this->resourceContactEmail,
			$this->previewImage,
			$this->mdProxy,
			$this->inspireInteroperability,
			$this->searchable,			
			$this->fkeyGuiId,
			$this->fkeyWmcSerialId,
			$this->fkeyMapviewerId
		    );
		    //$e = new mb_exception("class_iso19139: ".$this->createWktBboxFromArray($this->wgs84Bbox));
		    $t = array('s','s','s','s','s','s','s','s','s','s','s','s','s','b','s','s','s','s','i','i','s','s','b','s','s','s','s','s','s','POLYGON','s','s','s','i','POLYGON','d','s','s','s','s','s','s','b','b','b','i','i','i');
		    $res = db_prep_query($sql,$v,$t);
		}
		return $res;
	}

	public function insertMetadataUrlToDB($resourceType, $resourceId){
		//check if metadata record already exists, if not create a new one else insert relation only!
		$metadataId = $this->isLinkAlreadyInDB();
		if ($metadataId != false) {
			//update the metadataURL entry
			$e = new mb_notice("class_Iso19139:"."existing metadata link(s) found: ".$metadataId." - update will be performed");
			$sql = "UPDATE mb_metadata SET link = $1, origin = $2, md_format = $3, linktype = $4, changedate = now(), export2csw = $5, randomid = $6, harvestresult = $8, harvestexception = $9 WHERE metadata_id = $7";
			$v = array(
				$this->href,
				$this->origin,
				$this->format,
				$this->type,
				'f',
				$this->randomId,
				$metadataId,
				$this->harvestResult,
				$this->harvestException
			);
			$t = array('s','s','s','s','b','s','i','i','s');
		} else {
			$sql = "INSERT INTO mb_metadata (link, origin, md_format, linktype, createdate, changedate, export2csw, randomid, fkey_mb_user_id, harvestresult, harvestexception) ";
			$sql .= "VALUES($1, $2, $3, $4, now(), now(), $5, $6, $7, $8, $9)";
			$v = array(
				$this->href,
				$this->origin,
				$this->format,
				$this->type,
				'f',
				$this->randomId,
				$this->owner,
				$this->harvestResult,
				$this->harvestException
			);
			$t = array('s','s','s','s','b','s','i','i','s');
		}	
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			db_rollback();
			$e = new mb_exception("class_Iso19139:"._mb("Cannot insert or update metadataUrl in database!"));
			return false;
		} else {
			//insert relation into db
			//get inserted metadata_id
			$sql = <<<SQL
SELECT metadata_id FROM mb_metadata WHERE randomid = $1
SQL;
			//maybe there are more than one results - which should be used??? case of creating new linkage with old metadata TODO TODO
			$v = array($this->randomId);
			$t = array('s');
			try {
				$res = db_prep_query($sql,$v,$t);
			}
			catch (Exception $e){
				$e = new mb_exception("class_Iso19139:"._mb("Cannot get metadata record with following random id from database: ".$this->randomId));
			}
			if (!$res) {
				//do nothing
				$e = new mb_exception("class_Iso19139:"._mb("Cannot find inserted metadata entry to store relation."));
				return false;
			} else {
				//insert relation
				$row = db_fetch_assoc($res);
				$metadataId = $row['metadata_id'];
				if ($resourceType !== 'metadata') {
					//insert relation to layer/featuretype
					$sql = "INSERT INTO ows_relation_metadata (fkey_".$resourceType."_id, fkey_metadata_id, relation_type) values ($1, $2, $3);";	
					$v = array($resourceId, $metadataId, $this->origin);
					$t = array('i','i', 's');
					$res = db_prep_query($sql,$v,$t);
				} else {
					$res = false;
				}
				if(!$res){
					db_rollback();
					$e = new mb_exception("class_Iso19139:"._mb("Cannot insert metadata relation!"));
					return false;
				} else {
					$sql = "UPDATE mb_metadata SET harvestresult = 0, harvestexception = 'Linked metadata could not be interpreted, only linkage is stored to mb_metadata!' where metadata_id = $1";
					$v = array($metadataId);
					$t = array('i');
					$res = db_prep_query($sql,$v,$t);
					if(!$res){
						db_rollback();
						$e = new mb_exception("class_Iso19139:"._mb("Cannot update mb_metadata table!"));
						return false;
					}
				}		
			}
			return true;
		}
		return true;
	}

	public function getExtentGraphic($layer_4326_box) {
		$area_4326_box = explode(',',EXTENTSERVICEBBOX);
		if ($layer_4326_box[0] <= $area_4326_box[0] || $layer_4326_box[2] >= $area_4326_box[2] || $layer_4326_box[1] <= $area_4326_box[1] || $layer_4326_box[3] >= $area_4326_box[3]) {
			if ($layer_4326_box[0] < $area_4326_box[0]) {
				$area_4326_box[0] = $layer_4326_box[0]; 
			}
			if ($layer_4326_box[2] > $area_4326_box[2]) {
				$area_4326_box[2] = $layer_4326_box[2]; 
			}
			if ($layer_4326_box[1] < $area_4326_box[1]) {
				$area_4326_box[1] = $layer_4326_box[1]; 
			}
			if ($layer_4326_box[3] > $area_4326_box[3]) {
				$area_4326_box[3] = $layer_4326_box[3]; 
			}

			$d_x = $area_4326_box[2] - $area_4326_box[0]; 
			$d_y = $area_4326_box[3] - $area_4326_box[1];
			
			$new_minx = $area_4326_box[0] - 0.05*($d_x);
			$new_maxx = $area_4326_box[2] + 0.05*($d_x);
			$new_miny = $area_4326_box[1] - 0.05*($d_y);
			$new_maxy = $area_4326_box[3] + 0.05*($d_y);

			if ($new_minx < -180) $area_4326_box[0] = -180; else $area_4326_box[0] = $new_minx;
			if ($new_maxx > 180) $area_4326_box[2] = 180; else $area_4326_box[2] = $new_maxx;
			if ($new_miny < -90) $area_4326_box[1] = -90; else $area_4326_box[1] = $new_miny;
			if ($new_maxy > 90) $area_4326_box[3] = 90; else $area_4326_box[3] = $new_maxy;
		}
		$getMapUrl = EXTENTSERVICEURL."VERSION=1.1.1&REQUEST=GetMap&SERVICE=WMS&LAYERS=".EXTENTSERVICELAYER."&STYLES=&SRS=EPSG:4326&BBOX=".$area_4326_box[0].",".$area_4326_box[1].",".$area_4326_box[2].",".$area_4326_box[3]."&WIDTH=120&HEIGHT=120&FORMAT=image/png&BGCOLOR=0xffffff&TRANSPARENT=TRUE&EXCEPTIONS=application/vnd.ogc.se_inimage&minx=".$layer_4326_box[0]."&miny=".$layer_4326_box[1]."&maxx=".$layer_4326_box[2]."&maxy=".$layer_4326_box[3]."&metadata_uuid=".$this->fileIdentifier;
		return $getMapUrl;
	}

	public function insertToDB($resourceType, $resourceId, $inheritContactInfo = false, $inheritLicenceInfo = false, $resolveRemote = true){
		$result = array(); //value/message
		
		switch ($this->origin) {
			case "capabilities":
				//check if href is given and resource can be parsed
				//following information must be given:
				//randomId, href, format, type, origin, owner
				//TODO: that empty identifier not identified - see comparing linkage

				//check wfs version - if 2.0.0 is format and type attributes
				$wfs20 = false;
				if ($resourceType == 'featuretype') {
					if ($this->getWfsVersionForFeaturetype($resourceId) == '2.0.0') {
						$wfs20 = true;
						//$e = new mb_exception("wfs 2.0.0 found!");
					}
				}
				
				if ((($this->type == 'ISO19115:2003' || $this->type == 'ISO 19115:2003') && $this->format =='text/xml') || ($this->type == 'TC211' && $this->format =='text/xml') || (($this->type == 'ISO19115:2003' || $this->type == 'ISO 19115:2003') && ($this->format =='application/vnd.iso.19139+xml' || $this->format =='application/xml')) || $wfs20 == true) {
					$e = new mb_notice("class_Iso19139:"."try to parse: ".$this->href);
					if ($resolveRemote == true) {
						$metadata = $this->createFromUrl($this->href); //will alter object itself
					} else {
						$metadata == false;
						$e = new mb_exception("MetadataURL harvesting is excluded by conf!");
					}
					$e = new mb_notice("class_Iso19139:"."Metadata found: ".$this->metadata);
					if ($metadata == false) {
						//try to insert only MetadataURL elements
						if (!$this->insertMetadataUrlToDB($resourceType, $resourceId)) {
							$e = new mb_exception("class_Iso19139:"."Problem while storing MetadataURL entry from wms capabilities to mb_metadata table!");
							$result['value'] = false;
							$result['message'] = "Problem while storing MetadataURL entry from wms capabilities to mb_metadata table!";
							return $result;
						} else {
							
							$e = new mb_exception("class_Iso19139:"."Storing only MetadataURL ".$metadata->href." from capabilities to mb database cause the target could not be accessed or parsed!");
							$result['value'] = true;
							$result['message'] = "Storing only MetadataURL ".$metadata->href." from capabilities to mb database cause the target could not be accessed or parsed!";
							return $result;
						}
					} 
					$this->harvestResult = 1;
				} else {
					$e = new mb_exception("class_Iso19139:"."MetadataURL format or type of following url is not supported: Format: ".$this->format." | type: ".$this->type);
					$result['value'] = false;
					$result['message'] = "Metadata attributes are not set properly. Please check this configuration in the service!";
					return $result;
				}
				break;
			case "external":
				//don't check format and type -cause they are not given. Otherwise the same as in case of capabilities
				$metadata = $this->createFromUrl($this->href); //will alter object itself
				if ($metadata == false) {
					//try to insert only MetadataURL elements
					if (!$this->insertMetadataUrlToDB($resourceType, $resourceId)) {
						$e = new mb_exception("class_Iso19139:"."Problem while storing metadata source to mb_metadata table!");
						$result['value'] = false;
						$result['message'] = "Problem while storing metadata source to mb_metadata table!";
						return $result;
					} else {
						$e = new mb_exception("class_Iso19139:"."Storing only MetadataURL ".$metadata->href." from external source to mb database cause the target could not be accessed or parsed!");
						$result['value'] = true;
						$result['message'] = "Storing only MetadataURL ".$metadata->href." from external source to mb database cause the target could not be accessed or parsed!";
						return $result;
					}
				} 
				$this->harvestResult = 1;
				break;
			case "metador":
				//nothing to do at all?
				break;
			case "upload":
				//nothing to do at all?
				$e = new mb_notice("class_Iso19139:"."upload found");
				//$e = new mb_exception($this->metadata);
				$metadata = $this->createMapbenderMetadataFromXML($this->metadata);
				
				if ($metadata == false) {
					//xml could not be parsed
					$result['value'] = false;
					$result['message'] = "Metadata object could not be created!";
					return $result;
				}
				//else save it into database
				$this->harvestResult = 1;
				break;
			case "internal":
				//only set relation to existing record - return true;
				//is actually handled thru plugins/mb_metadata_server* 
				break;
			default:
       				$e = new mb_exception("class_Iso19139:"."Metadata origin is not known - please set it before storing values to DB!");
				$result['value'] = false;
				$result['message'] = "Metadata origin is not known - please set it before storing values to DB!";
				return $result;
		}

		//check if metadata record already exists, if not create a new one, else insert relation only and update the metadata itself!
		$metadataId = $this->isFileIdentifierAlreadyInDB();
		//$e = new mb_exception("found metadata_id: ".$metadataId);
		//check if some things should be inherited from service metadata
		if ($inheritContactInfo == true || $inheritLicenceInfo == true) {
			$this->mdProxy = "t";
		}
		if ($metadataId != false) {
			//update the metadata - new random id set therefor there is no problem when setting the relation afterwards
			$e = new mb_notice("existing metadata fileIdentifier found at metadata with id: ".$metadataId." - update will be performed");
			//TODO: Check if the timestamp of an existing metadata element is not newer than the timestamp of the current metadata object!!!!
			//for uploaded metadata the harvesting was ok otherwise the function returned before
			$res = $this->updateMetadataById($metadataId);

			$this->insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId);
			$this->inheritContactAndLicenceInformation($metadataId,$resourceType,$resourceId,$inheritContactInfo,$inheritLicenceInfo);
		} else {
			//check if href already exists
			$metadataId = $this->isLinkAlreadyInDB();
			//$e = new mb_exception("found metadata_id by link : ".$metadataId);
			//if so, the metadataset will be the same - (same url same metadataset) - update this as before
			if ($metadataId != false) {
				//the link to an existing metadataset already exists - don't store it again or insert anything
				$e = new mb_notice("existing metadata linkage found at metadata with id: ".$metadataId." - update will be performed");
				//check if the timestamp of an existing metadata element is not newer than the timestamp of the current metadata object!!!!
				$res = $this->updateMetadataById($metadataId);
				$this->insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId);
				$this->inheritContactAndLicenceInformation($metadataId,$resourceType,$resourceId,$inheritContactInfo,$inheritLicenceInfo);
			} else {
				//insert new record
				$e = new mb_notice("class_Iso19139:"."No existing metadata fileIdentifier found in mapbender metadata table. New record will be inserted with uuid: ".$this->fileIdentifier);
				$res = $this->insertMetadataIntoDB();
			}
		}
		//after insert/update metadata do the relational things
		if(!$res){
			db_rollback();
			$e = new mb_exception("class_Iso19139:"._mb("Cannot insert or update metadata record into mapbenders mb_metadata table!"));
			$result['value'] = false;
			$result['message'] = "Cannot insert or update metadata record into mapbenders mb_metadata table!";
			return $result;
		} else {
			//insert relation into db
			//get inserted or updated metadata_id by use the randomid
			$sql = <<<SQL
SELECT metadata_id FROM mb_metadata WHERE randomid = $1
SQL;
			$v = array($this->randomId);
			$t = array('s');
			try {
				$res = db_prep_query($sql,$v,$t);
			}
			catch (Exception $e){
				$e = new mb_exception("class_Iso19139:"._mb("Cannot get metadata record with following uuid from database: ".$this->randomId));
			}
			if (!$res) {
				//do nothing
				$e = new mb_exception("class_Iso19139:"._mb("Cannot get metadata record with following uuid from database: ".$this->randomId));
				$result['value'] = false;
				$result['message'] = "Cannot get metadata record with following uuid from database: ".$uuid;
				return $result;
			} else {
				//update metadata record in connected csw if configured
				//Propagate information for each new layer to csw if configured*******************
				$propagation = new propagateMetadata();
				$uuidArray[0] = $this->fileIdentifier;
				$resultPropagation = $propagation->doPropagation("metadata", false, "push",  $uuidArray);
				//********************************************************************************
				//insert relations
				$row = db_fetch_assoc($res);
				$metadataId = $row['metadata_id'];
				if ($resourceType !== 'metadata') {
					/*************************************/
					//check if current relation already exists in case of upload
					if ($resourceType != 'application') {
						if ($this->checkMetadataRelation($resourceType, $resourceId, $metadataId,$this->origin)) {
							$e = new mb_notice("class_Iso19139:"._mb("Relation already exists - it will not be generated twice!"));
							$result['value'] = true;
							$result['message'] = "Relation already exists - it will not be generated twice!";
							return $result;
						}
						//insert relation to layer/featuretype
						$sql = "INSERT INTO ows_relation_metadata (fkey_".$resourceType."_id, fkey_metadata_id, relation_type) values ($1, $2, $3);";
						$v = array($resourceId, $metadataId, $this->origin);
						$t = array('i','i','s');
						$res = db_prep_query($sql,$v,$t);
					}
 				} else {
					$res = true;
				}
				if(!$res){
					db_rollback();
					$e = new mb_exception("class_Iso19139:"._mb("Cannot insert metadata relation!"));
					$result['value'] = false;
					$result['message'] = "Cannot insert metadata relation!";
					return $result;
				} else {
					//update related view and download service metadata to resolve the coupling and references - operatesOn attribute and so on ...
					//get ids and uuids of searchable layers to pull all service metadata records
					$sql = "SELECT layer_id, layer.uuid from layer INNER JOIN ows_relation_metadata ON ows_relation_metadata.fkey_layer_id = layer.layer_id WHERE fkey_metadata_id = $1 AND layer.layer_searchable = 1";
					$v = array($metadataId);
					$t = array('i');
					$res = db_prep_query($sql,$v,$t);
					
					//
					$sql = "UPDATE mb_metadata SET harvestresult = 1 where metadata_id = $1";
					$v = array($metadataId);
					$t = array('i');
					$res = db_prep_query($sql,$v,$t);
					if(!$res){
						db_rollback();
						$e = new mb_exception("class_Iso19139:"._mb("Cannot update mb_metadata table to fill in harvest result!"));
						$result['value'] = false;
						$result['message'] = "Cannot update mb_metadata table to fill in harvest result!";
						return $result;
					} else {
						$this->insertKeywordsAndCategoriesIntoDB($metadataId,$resourceType,$resourceId);	
						//inherit licence info and contact info from coupled service if this is wished so - that will override and/or extent the original metadata!!! 
						$this->inheritContactAndLicenceInformation($metadataId,$resourceType,$resourceId,$inheritContactInfo,$inheritLicenceInfo);
						//TODO: if this was ok, let the resource (layer/featuretype) inherit the classification from the coupled metadata to support better catalogue search
					}
				} 
			}
			$result['value'] = true;
			$result['message'] = "Insert metadata successfully into database!";
			return $result;
		}	
	}

}
?>
