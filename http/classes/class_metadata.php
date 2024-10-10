<?php
#Script to call this class: http://www.geoportal.rlp.de/mapbender/php/mod_callMetadata.php
#Class for getting results out of the mapbender service registry 
#Resulttypes: WMS, WMS-Layer, (WFS), WFS-Featurtyps, WFS-Conf, WMC, Datasets, ...
#Possible filters: registrating organizations, time, bbox (fully inside, intersects, fully outside), ISO Topic Categories, INSPIRE themes, INSPIRE: keywords, classification of data/service ... - maybe relevant for the german broker not for one instance, quality and actuality (maybe spatial and temporal), bbox, deegree of conformity with ir, access and use constraints, responsible parties - maybe one is enough? We must have a look at the INSPIRE Metadata IR
#Metadata we need to fullfil the demands of INSPIRE:
#1. INSPIRE conformity classification for WMS/WFS/WCS
#2. Temporal Extents at WMS/WMS-Layer/WFS/WFS-Featuretype levels - for datasets if demanded - til now there is no demand defined in the guidance-paper for metadata ir
#3. Classified access and use contraints - which classes? - Check IR Data Sharing and IR Metadata
#4. 
#Every resource which should be send to INSPIRE can be filtered - but is not neccessary for a standardized approach
#Another problem is the ranking of the different ressources. The ranking should be homogeneus. 
#Till now we rank the using of WMS Layers when Caps are requested and when s.o. load one layer into the geoportal.
#TODO: The same things have to be done for the wfs-conf (Modules). Actually the invocation of inspire atom feeds are counted. Also the invocation of WMC documents are monitored. 
#Classes for filtering after the results have been send to the portal:
#1. ISO Topic Categories
#2. INSPIRE Themes
#3. Access and use classification
#4. departments which provides the ressources - we need the new concept for the administration of this departments - store the addresses in the group table and give the relation - originating group in table mb_user_mb_group 
#Cause we have a authorization layer, we need the id of the requesting user which is defined in the session. If no session is set, it should be the anonymous user of the portal.
#We need a parameter for internationalization - it should be send with the search request! Some of the Classes can be provided with different languages.
#WMC and GeoRSS-Feeds have no or a to complex authorization info - maybe we need to test if wmc consists of info which is fully or only partually available to the anonymous user. 

require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/class_administration.php");
require_once(dirname(__FILE__) . "/class_mb_exception.php");
require_once(dirname(__FILE__) . "/class_json.php");
require_once(dirname(__FILE__) . "/../php/mod_getDownloadOptions.php");

class searchMetadata
{
	var $userId;
	var $searchId;
	var $searchText;
	var $registratingDepartments;
	var $isoCategories;
	var $inspireThemes;
	var $customCategories;
	var $timeBegin;
	var $timeEnd;
	var $regTimeBegin;
	var $regTimeEnd;
	var $maxResults;
	var $searchBbox;
	var $searchTypeBbox;
	var $accessRestrictions;
	var $languageCode;
	var $searchStartTime;
	var $searchView;
	var $searchURL;
	var $searchEPSG;
	var $searchResources;
	var $searchPages;
	var $outputFormat;
	var $resultTarget; //web,webclient,file,internal
	var $tempFolder;
	var $orderBy;
	var $hostName;
	var $resourceIds;
	var $restrictToOpenData;
	var $restrictToHvd;
	var $originFromHeader;
	var $resolveCoupledResources; //only for class of dataset metadata - it pulls the coupled ressources (ogc-services : wms-layer/wfs-featuretypes)
	var $https;
	var $protocol;
	var $hvdInspireCats;
	var $hvdCustomCats;
	function __construct($userId, $searchId, $searchText, $registratingDepartments, $isoCategories, $inspireThemes, $timeBegin, $timeEnd, $regTimeBegin, $regTimeEnd, $maxResults, $searchBbox, $searchTypeBbox, $accessRestrictions, $languageCode, $searchEPSG, $searchResources, $searchPages, $outputFormat, $resultTarget, $searchURL, $customCategories, $hostName, $orderBy, $resourceIds, $restrictToOpenData, $originFromHeader, $resolveCoupledResources = false, $https = false, $restrictToHvd)
	{
		$this->userId = (int) $userId;
		$this->searchId = $searchId;
		$this->searchText = $searchText;
		$this->registratingDepartments = $registratingDepartments; //array with ids of the registrating groups in the mb database
		$this->registratingDepartmentsArray = explode(",", $this->registratingDepartments);
		$this->isoCategories = $isoCategories;
		$this->inspireThemes = $inspireThemes;
		$this->customCategories = $customCategories;
		$this->timeBegin = $timeBegin;
		$this->timeEnd = $timeEnd;
		$this->regTimeBegin = $regTimeBegin;
		$this->regTimeEnd = $regTimeEnd;
		$this->maxResults = (int) $maxResults;
		$this->searchBbox = $searchBbox;
		$this->searchTypeBbox = $searchTypeBbox;
		$this->accessRestrictions = $accessRestrictions;
		$this->languageCode = $languageCode;
		$this->searchEPSG = $searchEPSG;
		$this->searchResources = $searchResources;
		$this->searchPages = $searchPages;
		$this->outputFormat = $outputFormat;
		$this->resultTarget = $resultTarget;
		$this->searchURL = $searchURL;
		$this->hostName = $hostName;
		$this->orderBy = $orderBy;
		$this->resourceIds = $resourceIds;
		if ($restrictToOpenData === "true") {
			$this->restrictToOpenData = true;
		} else {
			$this->restrictToOpenData = false;
		}
		if ($restrictToHvd === "true") {
			$this->restrictToHvd = true;
		} else {
			$this->restrictToHvd = false;
		}
		$this->originFromHeader = $originFromHeader;
		$this->internalResult = null; //will only be filled, if resultTarget = 'internal', includes json for wms or wfs
		$this->resolveCoupledResources = $resolveCoupledResources;
		//definitions for generating tagClouds
		$this->https = $https;
		if ($this->https == true) {
			$this->protocol = "https";
		} else {
			$this->protocol = "http";
		}
		$this->maxObjects = 15;
		$this->maxFontSize = 30;
		$this->maxWeight = 0;
		$this->scale = 'linear';
		$this->minFontSize = 10;

		if (file_exists ( dirname ( __FILE__ ) . "/../../conf/hvd_cats.json" )) {
			$configObject = json_decode ( file_get_contents ( "../../conf/hvd_cats.json" ) );
		}
		if (isset ( $configObject ) && isset ( $configObject->hvd_inspire_cat ) && count($configObject->hvd_inspire_cat) > 0 ) {
			$this->hvdInspireCats = $configObject->hvd_inspire_cat;
		}
		if (isset ( $configObject ) && isset ( $configObject->hvd_custom_cat ) && count($configObject->hvd_custom_cat) > 0 ) {
			$this->hvdCustomCats = $configObject->hvd_custom_cat;
		}

		if (defined("ABSOLUTE_TMPDIR")) {
			$this->tempFolder = ABSOLUTE_TMPDIR;
		} else {
			$this->tempFolder = TMPDIR;
		}

		if ($this->outputFormat == 'json') {
			$this->json = new Mapbender_JSON;
		}
		$this->accessableLayers = NULL;
		//set a time to find time consumers
		$this->searchStartTime = $this->microtime_float();
		//Defining of the different database categories		
		$this->resourceClassifications = array();
		$this->resourceClassifications[0]['title'] = "ISO 19115"; //TODO: define the translations somewhere? - This is done in call_metadata.php before. Maybe we can get them from there? - It will be shown in the rightside categories table
		$this->resourceClassifications[0]['tablename'] = 'md_topic_category';
		$this->resourceClassifications[0]['requestName'] = 'isoCategories';
		$this->resourceClassifications[0]['id_wms'] = 'layer_id';
		$this->resourceClassifications[0]['id_wfs'] = 'featuretype_id';
		$this->resourceClassifications[0]['id_wmc'] = 'wmc_serial_id';
		$this->resourceClassifications[0]['id_dataset'] = 'metadata_id';
		$this->resourceClassifications[0]['id_application'] = 'metadata_id';
		$this->resourceClassifications[0]['relation_wms'] = 'layer_md_topic_category';
		$this->resourceClassifications[0]['relation_wfs'] = 'wfs_featuretype_md_topic_category';
		$this->resourceClassifications[0]['relation_wmc'] = 'wmc_md_topic_category';
		$this->resourceClassifications[0]['relation_dataset'] = 'mb_metadata_md_topic_category';
		$this->resourceClassifications[0]['relation_application'] = 'mb_metadata_md_topic_category';
		//TODO: define this in mapbender

		$this->resourceClassifications[1]['title'] = "INSPIRE"; //TODO: define the translations somewhere? - This is done in call_metadata.php before. Maybe we can get them from there? - It will be shown in the rightside categories table
		$this->resourceClassifications[1]['tablename'] = 'inspire_category';
		$this->resourceClassifications[1]['requestName'] = 'inspireThemes';
		$this->resourceClassifications[1]['id_wms'] = 'layer_id';
		$this->resourceClassifications[1]['id_wfs'] = 'featuretype_id';
		$this->resourceClassifications[1]['id_wmc'] = 'wmc_serial_id';
		$this->resourceClassifications[1]['id_dataset'] = 'metadata_id';
		$this->resourceClassifications[1]['id_application'] = 'metadata_id';
		$this->resourceClassifications[1]['relation_wms'] = 'layer_inspire_category';
		$this->resourceClassifications[1]['relation_wfs'] = 'wfs_featuretype_inspire_category';
		$this->resourceClassifications[1]['relation_wmc'] = 'wmc_inspire_category';
		$this->resourceClassifications[1]['relation_dataset'] = 'mb_metadata_inspire_category';
		$this->resourceClassifications[1]['relation_application'] = 'mb_metadata_inspire_category';
		//TODO: define this in mapbender
		switch ($this->languageCode) {
			case "de":
				$this->resourceClassifications[2]['title'] = "Sonstige"; //TODO: define the translations somewhere? - This is done in call_metadata.php before. Maybe we can get them from there? - It will be shown in the rightside categories table
				break;
			case "en":
				$this->resourceClassifications[2]['title'] = "Custom";
				break;
			case "fr":
				$this->resourceClassifications[2]['title'] = "Personnaliser";
				break;
			default:
				$this->resourceClassifications[2]['title'] = "Custom";
				break;
		}
		$this->resourceClassifications[2]['tablename'] = 'custom_category';
		$this->resourceClassifications[2]['requestName'] = 'customCategories';
		$this->resourceClassifications[2]['id_wms'] = 'layer_id';
		$this->resourceClassifications[2]['id_wfs'] = 'featuretype_id';
		$this->resourceClassifications[2]['id_wmc'] = 'wmc_serial_id';
		$this->resourceClassifications[2]['id_dataset'] = 'metadata_id';
		$this->resourceClassifications[2]['id_application'] = 'metadata_id';
		$this->resourceClassifications[2]['relation_wms'] = 'layer_custom_category';
		$this->resourceClassifications[2]['relation_wfs'] = 'wfs_featuretype_custom_category';
		$this->resourceClassifications[2]['relation_wmc'] = 'wmc_custom_category';
		$this->resourceClassifications[2]['relation_dataset'] = 'mb_metadata_custom_category';
		$this->resourceClassifications[2]['relation_application'] = 'mb_metadata_custom_category';
		//TODO: define this in mapbender
		switch ($this->languageCode) {
			case "de":
				$this->resourceClassifications[3]['title'] = "Organisationen";
				break;
			case "en":
				$this->resourceClassifications[3]['title'] = "Organizations";
				break;
			default:
				$this->resourceClassifications[3]['title'] = "Organizations";
				break;
		}
		$this->resourceClassifications[3]['requestName'] = "registratingDepartments";

		//Defining of the different result categories		
		$this->resourceCategories = array();
		$this->resourceCategories[0]['name'] = 'WMS';
		$this->resourceCategories[1]['name'] = 'WFS';
		$this->resourceCategories[2]['name'] = 'WMC';
		$this->resourceCategories[3]['name'] = 'DAD';
		$this->resourceCategories[4]['name'] = 'DATASET';
		$this->resourceCategories[4]['name'] = 'APPLICATION';

		switch ($this->languageCode) {
			case 'de':
				$this->resourceCategories[0]['name2show'] = 'Kartenebenen';
				$this->resourceCategories[1]['name2show'] = 'Such- und Download- und Erfassungsmodule';
				$this->resourceCategories[2]['name2show'] = 'Kartenzusammenstellungen';
				$this->resourceCategories[3]['name2show'] = 'KML/Newsfeeds';
				$this->resourceCategories[4]['name2show'] = 'Datensätze';
				$this->resourceCategories[5]['name2show'] = 'Anwendungen';
				$this->keywordTitle = 'Schlagwortliste';
				break;
			case 'en':
				$this->resourceCategories[0]['name2show'] = 'Maplayers';
				$this->resourceCategories[1]['name2show'] = 'Search- and Downloadservices';
				$this->resourceCategories[2]['name2show'] = 'Combined Maps';
				$this->resourceCategories[3]['name2show'] = 'KML/Newsfeeds';
				$this->resourceCategories[4]['name2show'] = 'Datasets';
				$this->resourceCategories[5]['name2show'] = 'Applications';
				$this->keywordTitle = 'Keywordlist';
				break;
			case 'fr':
				$this->resourceCategories[0]['name2show'] = 'Services de visualisation';
				$this->resourceCategories[1]['name2show'] = 'Services de recherche et de téléchargement';
				$this->resourceCategories[2]['name2show'] = 'Cartes composées';
				$this->resourceCategories[3]['name2show'] = 'KML/Newsfeeds';
				$this->resourceCategories[4]['name2show'] = 'Datasets';
				$this->resourceCategories[5]['name2show'] = 'Applications';
				$this->keywordTitle = 'Keywordlist';
				break;
			default:
				$this->resourceCategories[0]['name2show'] = 'Kartenebenen';
				$this->resourceCategories[1]['name2show'] = 'Such- und Download- und Erfassungsmodule';
				$this->resourceCategories[2]['name2show'] = 'Kartenzusammenstellungen';
				$this->resourceCategories[3]['name2show'] = 'KML/Newsfeeds';
				$this->resourceCategories[4]['name2show'] = 'Datensätze';
				$this->resourceCategories[5]['name2show'] = 'Anwendungen';
				$this->keywordTitle = 'Schlagwortliste';
		}
		//not needed til now - maybe usefull for georss output
		if ($this->outputFormat == "xml") {
			//Initialize XML documents
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wms") {
				$this->wmsDoc = new DOMDocument('1.0');
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wfs") {
				$this->wfsDoc = new DOMDocument('1.0');
				$this->generateWFSMetadata($this->wfsDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wmc") {
				$this->wmcDoc = new DOMDocument('1.0');
				$this->generateWMCMetadata($this->wmcDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "georss") {
				$this->georssDoc = new DOMDocument('1.0');
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "dataset") {
				$this->datasetDoc = new DOMDocument('1.0');
				$this->generateDatasetMetadata($this->datasetDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "application") {
				$this->applicationDoc = new DOMDocument('1.0');
				$this->generateApplicationMetadata($this->applicationDoc);
			}
		}

		if ($this->outputFormat === "json") {
			$this->e = new mb_notice("orderBy old: " . $this->orderBy);
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wfs") {
				$this->databaseIdColumnName = 'featuretype_id';
				$this->databaseTableName = 'wfs_featuretype';
				//$this->keywordRelation = 'wfs_featuretype_keyword';
				$this->searchView = 'wfs_search_table';
				$this->whereStrCatExtension = " AND custom_category.custom_category_hidden = 0";
				switch ($this->orderBy) {
					case "rank":
						$this->orderBy = " ORDER BY wfs_id,featuretype_id,wfs_conf_id ";
						break;
					case "id":
						$this->orderBy = " ORDER BY wfs_id,featuretype_id,wfs_conf_id ";
						break;
					case "title":
						$this->orderBy = " ORDER BY featuretype_title ";
						break;
					case "date":
						$this->orderBy = " ORDER BY wfs_timestamp DESC ";
						break;
					default:
						$this->orderBy = " ORDER BY wfs_id,featuretype_id,wfs_conf_id ";
				}

				$this->resourceClasses = array(0, 1, 2);
				$this->generateWFSMetadata($this->wfsDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wms") {
				$this->databaseIdColumnName = 'layer_id';
				$this->databaseTableName = 'layer';
				//$this->keywordRelation = 'layer_keyword';
				$this->searchView = 'wms_search_table';
				//$this->searchView = 'search_wms_view';
				$this->whereStrCatExtension = " AND custom_category.custom_category_hidden = 0";
				switch ($this->orderBy) {
					case "rank":
						$this->orderBy = " ORDER BY load_count DESC";
						break;
					case "id":
						$this->orderBy = " ORDER BY wms_id,layer_pos ASC";
						break;
					case "title":
						$this->orderBy = " ORDER BY layer_title ";
						break;
					case "date":
						$this->orderBy = " ORDER BY wms_timestamp DESC ";
						break;
					default:
						$this->orderBy = " ORDER BY load_count DESC";
				}

				$this->resourceClasses = array(0, 1, 2);
				$this->generateWMSMetadata($this->wmsDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "wmc") {
				//$this->searchView = 'search_wmc_view';
				$this->searchView = 'wmc_search_table';
				$this->databaseIdColumnName = 'wmc_serial_id';
				$this->databaseTableName = 'wmc';
				//the following is needed to give a special filter to the custom cat table!
				$this->whereStrCatExtension = " AND custom_category.custom_category_hidden = 0";

				switch ($this->orderBy) {
					case "rank":
						$this->orderBy = " ORDER BY load_count DESC ";
						break;
					case "id":
						$this->orderBy = " ORDER BY wmc_id";
						break;
					case "title":
						$this->orderBy = " ORDER BY wmc_title ";
						break;
					case "date":
						$this->orderBy = " ORDER BY wmc_timestamp DESC ";
						break;
					default:
						$this->orderBy = " ORDER BY wmc_title ";
				}

				$this->resourceClasses = array(0, 1, 2); #TODO adopt to count classifications
				$this->generateWMCMetadata($this->wmcDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "dataset") {
				$this->databaseIdColumnName = 'metadata_id'; //not metadata_id as in original table!
				$this->databaseTableName = 'mb_metadata';
				$this->searchView = 'dataset_search_table';
				$this->whereStrCatExtension = " AND custom_category.custom_category_hidden = 0";
				switch ($this->orderBy) {
					case "rank":
						$this->orderBy = " ORDER BY load_count DESC";
						break;
					case "id":
						$this->orderBy = " ORDER BY metadata_id ASC";
						break;
					case "title":
						$this->orderBy = " ORDER BY title ASC";
						break;
					case "date":
						$this->orderBy = " ORDER BY dataset_timestamp DESC ";
						break;
					default:
						$this->orderBy = " ORDER BY title DESC";
				}

				$this->resourceClasses = array(0, 1, 2);
				$this->generateDatasetMetadata($this->datasetDoc);
			}
			if (isset($this->searchResources) & strtolower($this->searchResources) === "application") {
				$this->databaseIdColumnName = 'metadata_id'; //not metadata_id as in original table!
				$this->databaseTableName = 'mb_metadata';
				$this->searchView = 'search_application_view';
				$this->whereStrCatExtension = " AND custom_category.custom_category_hidden = 0";
				switch ($this->orderBy) {
					case "rank":
						$this->orderBy = " ORDER BY load_count DESC";
						break;
					case "id":
						$this->orderBy = " ORDER BY metadata_id ASC";
						break;
					case "title":
						$this->orderBy = " ORDER BY title ASC";
						break;
					case "date":
						$this->orderBy = " ORDER BY dataset_timestamp DESC ";
						break;
					default:
						$this->orderBy = " ORDER BY title DESC";
				}

				$this->resourceClasses = array(0, 1, 2);
				$this->generateApplicationMetadata($this->applicationDoc);
			}
		}
		$this->e = new mb_notice("orderBy new: " . $this->orderBy);
	}

	private function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}
	private function generateXMLHead($xmlDoc)
	{
		$xmlDoc->encoding = CHARSET;
		$result = $xmlDoc->createElement("result");
		$xmlDoc->appendChild($result);
		$overLimit = $xmlDoc->createElement("overLimit");
		$result->appendChild($overLimit);
		$tr_text = $xmlDoc->createTextNode("really?");
		$overLimit->appendChild($tr_text);
		$rd = $xmlDoc->createElement("redirect");
		$result->appendChild($rd);
		$trd = $xmlDoc->createTextNode("not yet ready...");
		$rd->appendChild($trd);
	}

	private function generateXMLFoot($xmlDoc)
	{
		$results = $xmlDoc->getElementsByTagName("result");
		foreach ($results as $result) {
			$result->appendChild($ready);
		}
	}

	private function flipDiagonally($arr)
	{
		$out = array();
		foreach ($arr as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}
		return $out;
	}

	private function generateWFSMetadataJSON($res, $n)
	{
		//initialize object
		$this->wfsJSON = new stdClass;
		$this->wfsJSON->wfs = (object) array(
			'md' => (object) array(
				'nresults' => $n,
				'p' => $this->searchPages,
				'rpp' => $this->maxResults
			),
			'srv' => array()
		);
		//read out records
		$wfsMatrix = db_fetch_all($res);
		//sort result for accessing the right services
		$wfsMatrix = $this->flipDiagonally($wfsMatrix);
		//TODO check if order by db or order by php is faster! 
		#array_multisort($wfsMatrix['wfs_id'], SORT_ASC,$wfsMatrix['featuretype_id'], SORT_ASC,$wfsMatrix['wfs_conf_id'], SORT_ASC); //have some problems - the database version is more stable
		$wfsMatrix = $this->flipDiagonally($wfsMatrix);
		//read out first server entry - maybe this a little bit timeconsuming TODO
		$j = 0; //count identical wfs_id => double featuretype
		$l = 0; //index featuretype and or modul per wfs
		$m = 0; //index modul per featuretype
		for ($i = 0; $i < count($wfsMatrix); $i++) {
			$this->wfsJSON->wfs->srv[$i - $j]->id = $wfsMatrix[$i]['wfs_id'];
			$this->wfsJSON->wfs->srv[$i - $j]->title = $wfsMatrix[$i]['wfs_title'];
			$this->wfsJSON->wfs->srv[$i - $j]->abstract = $wfsMatrix[$i]['wfs_abstract'];
			$this->wfsJSON->wfs->srv[$i - $j]->date = date("d.m.Y", $wfsMatrix[$i]['wfs_timestamp']);
			$this->wfsJSON->wfs->srv[$i - $j]->respOrg = $wfsMatrix[$i]['mb_group_name'];
			$this->wfsJSON->wfs->srv[$i - $j]->logoUrl = $wfsMatrix[$i]['mb_group_logo_path'];
			$this->wfsJSON->wfs->srv[$i - $j]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?resource=wfs&id=" . $wfsMatrix[$i]['wfs_id'];
			//TODO: Capabilities link
			$spatialSource = "";
			$stateOrProvince = $wfsMatrix[$i]['administrativearea'];
			if ($stateOrProvince == "NULL" || $stateOrProvince == "") {
				$spatialSource = $wfsMatrix[$i]['country'];
			} else {
				$spatialSource = $wfsMatrix[$i]['administrativearea'];
			}
			$this->wfsJSON->wfs->srv[$i - $j]->iso3166 = $spatialSource;
			//check if a disclaimer has to be shown and give the relevant symbol
			list($hasConstraints, $symbolLink, $termsOfUseId) = $this->hasConstraints("wfs", $wfsMatrix[$i]['wfs_id']);
			$this->wfsJSON->wfs->srv[$i - $j]->hasConstraints = $hasConstraints;
			$this->wfsJSON->wfs->srv[$i - $j]->symbolLink = $symbolLink;
			$this->wfsJSON->wfs->srv[$i - $j]->license_id = $termsOfUseId;
			//TODO check the field accessconstraints - which should be presented?
			$this->wfsJSON->wfs->srv[$i - $j]->status = $wfsMatrix[$i]['status'];
			$this->wfsJSON->wfs->srv[$i - $j]->avail = $wfsMatrix[$i]['availability'];
			$this->wfsJSON->wfs->srv[$i - $j]->logged = $wfsMatrix[$i]['wfs_proxylog']; //$wfsMatrix[$i][''];
			if ($wfsMatrix[$i]['wfs_pricevolume'] == '0' || $wfsMatrix[$i]['wfs_pricevolume'] == null || $wfsMatrix[$i]['wfs_pricevolume'] == '') {
				$this->wfsJSON->wfs->srv[$i - $j]->price = null;
			} else {
				$this->wfsJSON->wfs->srv[$i - $j]->price = $wfsMatrix[$i]['wfs_pricevolume'];
			}
			$this->wfsJSON->wfs->srv[$i - $j]->nwaccess = $wfsMatrix[$i]['wfs_network_access']; //$wfsMatrix[$i][''];
			$this->wfsJSON->wfs->srv[$i - $j]->bbox = "-180.0,-90.0,180.0,90.0"; //$wfsMatrix[$i][''];
			//if featuretype hasn't been created - do it
			if (!isset($this->wfsJSON->wfs->srv[$i - $j]->ftype)) {
				$this->wfsJSON->wfs->srv[$i - $j]->ftype = array();
			}
			//fill in featuretype infos
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->id = (int) $wfsMatrix[$i]['featuretype_id'];
			//get other infos directly from database
			$otherInformation = $this->getInfofromFeaturetypeId($this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->id);

			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->title = $wfsMatrix[$i]['featuretype_title'];
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->abstract = $wfsMatrix[$i]['featuretype_abstract'];
			//TODO featuretype name
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->name = $otherInformation['featuretypeName'];
			//TODO featuretype schema
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->schema = $otherInformation['describeFeaturetypeUrl'];
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?resource=featuretype&id=" . $wfsMatrix[$i]['featuretype_id'];
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->geomtype = $wfsMatrix[$i]['element_type'];
			$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->bbox = $wfsMatrix[$i]['bbox']; //TODO: $wfsMatrix[$i]['bbox'];
			//wfs capabilities url:
			$this->wfsJSON->wfs->srv[$i - $j]->getCapabilitiesUrl = $otherInformation['getCapabilitiesUrl'];

			//give info for inspire categories - not relevant for other services or instances of mapbender TODO: comment it if the mapbender installation is not used to generate inspire output
			if (isset($wfsMatrix[$i]['md_inspire_cats']) & ($wfsMatrix[$i]['md_inspire_cats'] != '')) {
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->inspire = 1;
			} else {
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->inspire = 0;
			}

			if (isset($wfsMatrix[$i]['wfs_conf_id']) && $wfsMatrix[$i]['wfs_conf_id'] != "") {
				//if modul hasn't been created - do it
				if (!isset($this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul)) {
					$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul = array();
				}
				//fill in modul infos
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->id = $wfsMatrix[$i]['wfs_conf_id'];
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->title = $wfsMatrix[$i]['wfs_conf_description'];
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->abstract = $wfsMatrix[$i]['wfs_conf_abstract'];
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->type = $wfsMatrix[$i]['modultype'];
				$equalEPSG = $wfsMatrix[$i]['featuretype_srs'];
				$isEqual = true;
				//control if EPSG is supported by Client
				if ($equalEPSG == $this->searchEPSG) {
					$isEqual = false;
				}
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->srsProblem = $isEqual;
				//generate Link to show metadata
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?resource=featuretype&id=" . $wfsMatrix[$i]['featuretype_id'];
				$perText = $this->getPermissionValueForWFS($wfsMatrix[$i]['wfs_id'], $wfsMatrix[$i]['wfs_conf_id']);
				$this->wfsJSON->wfs->srv[$i - $j]->ftype[$l - $m]->modul[$m]->permission = $perText;
			}

			//alter ftype to array - not associative array - built new sequence
			$this->wfsJSON->wfs->srv[$i - $j]->ftype = array_values($this->wfsJSON->wfs->srv[$i - $j]->ftype);
			if ($wfsMatrix[$i]['wfs_id'] == $wfsMatrix[$i + 1]['wfs_id']) {
				$j++; //next record is the same service
				$l++;
			} else {
				$l = 0;
			}
			if ($wfsMatrix[$i]['featuretype_id'] == $wfsMatrix[$i + 1]['featuretype_id']) {
				$m++;
			} else {
				$m = 0;
			}
		}
		$this->wfsJSON->wfs->srv = array_values($this->wfsJSON->wfs->srv);
	}

	private function generateWMCMetadataJSON($res, $n)
	{
		//initialize object
		$this->wmcJSON = new stdClass;
		$this->wmcJSON->wmc = (object) array(
			'md' => (object) array(
				'nresults' => $n,
				'p' => $this->searchPages,
				'rpp' => $this->maxResults
			),
			'srv' => array()
		);

		//read out records
		$serverCount = 0;
		$wmcMatrix = db_fetch_all($res);
		//sort result for accessing the right services
		$wmcMatrix = $this->flipDiagonally($wmcMatrix);
		//TODO check if order by db or order by php is faster! 
		#array_multisort($wfsMatrix['wfs_id'], SORT_ASC,$wfsMatrix['featuretype_id'], SORT_ASC,$wfsMatrix['wfs_conf_id'], SORT_ASC); //have some problems - the database version is more stable
		$wmcMatrix = $this->flipDiagonally($wmcMatrix);
		//read out first server entry - maybe this a little bit timeconsuming TODO
		for ($i = 0; $i < count($wmcMatrix); $i++) {
			$this->wmcJSON->wmc->srv[$i]->id = $wmcMatrix[$i]['wmc_id'];
			$this->wmcJSON->wmc->srv[$i]->title = $wmcMatrix[$i]['wmc_title'];
			$this->wmcJSON->wmc->srv[$i]->abstract = $wmcMatrix[$i]['wmc_abstract'];
			$this->wmcJSON->wmc->srv[$i]->date = date("d.m.Y", $wmcMatrix[$i]['wmc_timestamp']);
			$this->wmcJSON->wmc->srv[$i]->loadCount = $wmcMatrix[$i]['load_count'];
			$this->wmcJSON->wmc->srv[$i]->respOrg = $wmcMatrix[$i]['mb_group_name'];
			$this->wmcJSON->wmc->srv[$i]->logoUrl = $wmcMatrix[$i]['mb_group_logo_path'];
			$this->wmcJSON->wmc->srv[$i]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?languageCode=" . $this->languageCode . "&resource=wmc&layout=tabs&id=" . $wmcMatrix[$i]['wmc_id'];
			$this->wmcJSON->wmc->srv[$i]->previewURL = $this->protocol . "://" . $this->hostName . "/mapbender/geoportal/mod_showPreview.php?resource=wmc&id=" . $wmcMatrix[$i]['wmc_id'];
			$spatialSource = "";
			$stateOrProvince = $wmcMatrix[$i]['mb_group_stateorprovince'];
			if ($stateOrProvince == "NULL" || $stateOrProvince == "") {
				$spatialSource = $wmcMatrix[$i]['mb_group_country'];
			} else {
				$spatialSource = $wmcMatrix[$i]['mb_group_stateorprovince'];
			}
			$this->wmcJSON->wmc->srv[$i - $j]->iso3166 = $spatialSource;
			$this->wmcJSON->wmc->srv[$i - $j]->bbox = array($wmcMatrix[$i]['bbox']); //TODO: read out bbox from wmc $wmcMatrix[$i][''];
		}
	}

	private function generateDatasetMetadataJSON($res, $n)
	{
		//initialize object
		$this->datasetJSON = new stdClass;
		$this->datasetJSON->dataset = (object) array(
			'md' => (object) array(
				'nresults' => $n,
				'p' => $this->searchPages,
				'rpp' => $this->maxResults
			),
			'srv' => array()
		);
		$datasetMatrix = db_fetch_all($res);
		//sort result for accessing the right services
		$datasetMatrix = $this->flipDiagonally($datasetMatrix);
		//TODO check if order by db or order by php is faster! 
		#array_multisort($wfsMatrix['wfs_id'], SORT_ASC,$wfsMatrix['featuretype_id'], SORT_ASC,$wfsMatrix['wfs_conf_id'], SORT_ASC); //have some problems - the database version is more stable
		$datasetMatrix = $this->flipDiagonally($datasetMatrix);
		$allCoupledLayers = array();
		$allCoupledFeaturetypes = array();
		//read out first server entry - maybe this a little bit timeconsuming TODO
		for ($i = 0; $i < count($datasetMatrix); $i++) {
			$this->datasetJSON->dataset->srv[$i]->id = $datasetMatrix[$i]['dataset_id'];
			$this->datasetJSON->dataset->srv[$i]->title = $datasetMatrix[$i]['title'];
			$this->datasetJSON->dataset->srv[$i]->uuid = $datasetMatrix[$i]['fileidentifier'];
			$this->datasetJSON->dataset->srv[$i]->abstract = $datasetMatrix[$i]['dataset_abstract'];
			$this->datasetJSON->dataset->srv[$i]->date = date("Y-m-d", strtotime($datasetMatrix[$i]['dataset_timestamp']));
			$this->datasetJSON->dataset->srv[$i]->loadCount = $datasetMatrix[$i]['load_count'];
			$this->datasetJSON->dataset->srv[$i]->respOrg = $datasetMatrix[$i]['mb_group_name'];
			$this->datasetJSON->dataset->srv[$i]->logoUrl = $datasetMatrix[$i]['mb_group_logo_path'];
			list($hasConstraints, $symbolLink, $termsOfUseId) = $this->hasConstraints("dataset", $datasetMatrix[$i]['dataset_id']);
			$this->datasetJSON->dataset->srv[$i]->hasConstraints = $hasConstraints;
			$this->datasetJSON->dataset->srv[$i]->isopen = $datasetMatrix[$i]['isopen'];
			$this->datasetJSON->dataset->srv[$i]->symbolLink = $symbolLink;
			$this->datasetJSON->dataset->srv[$i]->license_id = $termsOfUseId;
			//TODO: other url - to metadata uuid!
			$this->datasetJSON->dataset->srv[$i]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_iso19139ToHtml.php?url=" . urlencode($this->protocol . "://" . $this->hostName . "/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=") . $datasetMatrix[$i]['fileidentifier'];
			//TODO: preview?
			$this->datasetJSON->dataset->srv[$i]->previewURL = $datasetMatrix[$i]['preview_url'];
			$spatialSource = "";
			$stateOrProvince = $datasetMatrix[$i]['mb_group_stateorprovince'];
			if ($stateOrProvince == "NULL" || $stateOrProvince == "") {
				$spatialSource = $datasetMatrix[$i]['mb_group_country'];
			} else {
				$spatialSource = $datasetMatrix[$i]['mb_group_stateorprovince'];
			}
			//create element with iso topic category codes (mapbender ids)
			if (isset($datasetMatrix[$i]['md_topic_cats']) && $datasetMatrix[$i]['md_topic_cats'] != "") {
				$md_topic_categoriesArray = explode(",",trim(str_replace("}{",",",$datasetMatrix[$i]['md_topic_cats']),"{}"));
				$this->datasetJSON->dataset->srv[$i]->isoCategories = $md_topic_categoriesArray;
			}
			$this->datasetJSON->dataset->srv[$i]->iso3166 = $spatialSource;
			$this->datasetJSON->dataset->srv[$i]->bbox = array($datasetMatrix[$i]['bbox']); //TODO: read out bbox from wmc $datasetMatrix[$i][''];
			$this->datasetJSON->dataset->srv[$i]->timeBegin = date("Y-m-d", strtotime($datasetMatrix[$i]['timebegin']));
			$this->datasetJSON->dataset->srv[$i]->timeEnd = date("Y-m-d", strtotime($datasetMatrix[$i]['timeend']));
			//search for coupled resources!!!!
			if ($this->resolveCoupledResources == true) {
				//maybe generate uuid first to find search!!
				//http://localhost/mb_trunk/php/mod_callMetadata.php?searchId=test2&searchText=wald&outputFormat=json&resultTarget=web&searchResources=dataset&resolveCoupledResources=true
				$coupledResources = json_decode($datasetMatrix[$i]['coupled_resources']);
				$layerCount = 0;
				$featuretypeCount = 0;
				foreach ($coupledResources->coupledResources->layerIds as $layer_id) {
					$this->datasetJSON->dataset->srv[$i]->coupledResources->layer[$layerCount]->id = $layer_id;
					$allCoupledLayers[] = $layer_id;
					$layerCount++;
				}
				foreach ($coupledResources->coupledResources->featuretypeIds as $featuretype_id) {
					//$e = new mb_exception("ft found: ".$featuretype_id);
					$this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype[$featuretypeCount]->id = $featuretype_id;
					$allCoupledFeaturetypes[] = $featuretype_id;
					$featuretypeCount++;
				}
			}
		}
		//search for coupled resources and push them into dataset json !
		if ($this->resolveCoupledResources == true) {
			$layerSearchArray = array();
			$featuretypeSearchArray = array();
			$downloadOptionsArray = array();
			$uniqueAllCoupledLayers = array_unique($allCoupledLayers);
			$uniqueAllCoupledFeaturetypes = array_unique($allCoupledFeaturetypes);
			$countUniqueLayers = count($uniqueAllCoupledLayers);
			$countUniqueFeaturetypes = count($uniqueAllCoupledFeaturetypes);
			if ($countUniqueLayers >= 1) {
				$coupledLayers = new self($this->userId, 'dummysearch', '*', null, null, null, null, null, null, null, $countUniqueLayers, null, null, null, $this->languageCode, null, 'wms', 1, 'json', 'internal', null, null, $this->hostName, $this->orderBy, implode(',', $uniqueAllCoupledLayers), $this->restrictToOpenData, $this->originFromHeader, false, $this->https, $this->restrictToHvd);
				$srvCount = 0;
				foreach (json_decode($coupledLayers->internalResult)->wms->srv as $server) {
					foreach ($server->layer as $layer) {
						$layerSearchArray[$layer->id] = $srvCount;
						//pull inspire downloadoptions from layer information
						foreach ($layer->downloadOptions as $downloadOption) {
							if ($downloadOption->uuid != null) {
								$downloadOptionsArray[$downloadOption->uuid] = json_encode($downloadOption->option);
							}
						}
						//TODO!: do this also for the next hierachylevel - maybe invoke it recursive!!!
						foreach ($layer->layer as $sublayer) {
							$layerSearchArray[$sublayer->id] = $srvCount;
							//pull inspire downloadoptions from layer information
							foreach ($sublayer->downloadOptions as $downloadOption) {
								if ($downloadOption->uuid != null) {
									$downloadOptionsArray[$downloadOption->uuid] = json_encode($downloadOption->option);
								}
							}
						}
					}
					$srvCount++;
				}
			}
			if ($countUniqueFeaturetypes >= 1) {
				$coupledFeaturetypes = new self($this->userId, 'dummysearch', '*', null, null, null, null, null, null, null, $countUniqueFeaturetypes, null, null, null, $this->languageCode, null, 'wfs', 1, 'json', 'internal', null, null, $this->hostName, $this->orderBy, implode(',', $uniqueAllCoupledFeaturetypes), $this->restrictToOpenData, $this->originFromHeader, false, $this->https, $this->restrictToHvd);
				$srvCount = 0;
				foreach (json_decode($coupledFeaturetypes->internalResult)->wfs->srv as $server) {
					foreach ($server->ftype as $featuretype) {
						$featuretypeSearchArray[$featuretype->id] = $srvCount;
					}
					$srvCount++;
				}
			}
			//insert objects into dataset result list
			for ($i = 0; $i < count($datasetMatrix); $i++) {
				$layerCount = 0;
				foreach ($this->datasetJSON->dataset->srv[$i]->coupledResources->layer as $layer) {
					$this->datasetJSON->dataset->srv[$i]->coupledResources->layer[$layerCount]->srv = json_decode($coupledLayers->internalResult)->wms->srv[$layerSearchArray[$layer->id]];
					$layerCount++;
				}
				$featuretypeCount = 0;
				foreach ($this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype as $ft) {
					$this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype[$featuretypeCount]->srv = json_decode($coupledFeaturetypes->internalResult, true)->wfs->srv[$featuretypeSearchArray[$ft->id]];
					//TODO check why featuretypes are not shown in the resultset - $this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype[$featuretypeCount]->srv = json_decode($coupledFeaturetypes->internalResult)->wfs->srv[$featuretypeSearchArray[$ft->id]];
					//delete all featuretypes that have not same id as ft->id
					$cntFtype = 0;
					foreach ($this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype[$featuretypeCount]->srv->ftype as $ftype) {
						if ($ftype->id !== $ft->id) {
							unset($this->datasetJSON->dataset->srv[$i]->coupledResources->featuretype[$featuretypeCount]->srv->ftype[$cntFtype]);
						}
						$cntFtype++;
					}
					$featuretypeCount++;
				}
				//check for atom feed entry
				if ($downloadOptionsArray[$datasetMatrix[$i]['fileidentifier']] != null) {
					$this->datasetJSON->dataset->srv[$i]->coupledResources->inspireAtomFeeds = json_decode($downloadOptionsArray[$datasetMatrix[$i]['fileidentifier']]);
				} else {
					$downloadOptionsFromMetadata = json_decode(getDownloadOptions(array($datasetMatrix[$i]['fileidentifier']), $this->protocol . "://" . $this->hostName . "/mapbender/"));
					//try to load coupled atom feeds from mod_getDownloadOptions and add them to result list! (if no wms layer nor wfs featuretype is available)
					foreach ($downloadOptionsFromMetadata->{$datasetMatrix[$i]['fileidentifier']}->option as $dlOption) {
						if ($dlOption->type == "downloadlink" || $dlOption->type == "distribution" || $dlOption->type == "remotelist") {
							$this->datasetJSON->dataset->srv[$i]->coupledResources->inspireAtomFeeds[] = $dlOption;
						}
					}
				}
				/*if ($featuretypeCount == 0 && $layerCount == 0) {
					$downloadOptionsFromMetadata = json_decode(getDownloadOptions(array($datasetMatrix[$i]['fileidentifier']), $this->protocol . "://" . $this->hostName . "/mapbender/"));
					//try to load coupled atom feeds from mod_getDownloadOptions and add them to result list! (if no wms layer nor wfs featuretype is available)
					foreach ($downloadOptionsFromMetadata->{$datasetMatrix[$i]['fileidentifier']}->option as $dlOption) {
						if ($dlOption->type == "downloadlink" || $dlOption->type == "distribution" || $dlOption->type == "remotelist") {
							$this->datasetJSON->dataset->srv[$i]->coupledResources->inspireAtomFeeds[] = $dlOption;
						}
					}
				}*/
			}
		}
	}

	private function generateApplicationMetadataJSON($res, $n)
	{
		//initialize object
		$this->applicationJSON = new stdClass;
		$this->applicationJSON->application = (object) array(
			'md' => (object) array(
				'nresults' => $n,
				'p' => $this->searchPages,
				'rpp' => $this->maxResults
			),
			'srv' => array()
		);

		//read out records
		$serverCount = 0;
		$applicationMatrix = db_fetch_all($res);
		//sort result for accessing the right services
		$applicationMatrix = $this->flipDiagonally($applicationMatrix);
		//TODO check if order by db or order by php is faster! 
		#array_multisort($wfsMatrix['wfs_id'], SORT_ASC,$wfsMatrix['featuretype_id'], SORT_ASC,$wfsMatrix['wfs_conf_id'], SORT_ASC); //have some problems - the database version is more stable
		$applicationMatrix = $this->flipDiagonally($applicationMatrix);
		//read out first server entry - maybe this a little bit timeconsuming TODO
		for ($i = 0; $i < count($applicationMatrix); $i++) {
			$this->applicationJSON->application->srv[$i]->id = $applicationMatrix[$i]['dataset_id'];
			$this->applicationJSON->application->srv[$i]->title = $applicationMatrix[$i]['title'];
			$this->applicationJSON->application->srv[$i]->uuid = $applicationMatrix[$i]['fileidentifier'];
			$this->applicationJSON->application->srv[$i]->abstract = $applicationMatrix[$i]['dataset_abstract'];
			$this->applicationJSON->application->srv[$i]->date = date("Y-m-d", strtotime($applicationMatrix[$i]['dataset_timestamp']));
			$this->applicationJSON->application->srv[$i]->loadCount = $applicationMatrix[$i]['load_count'];
			$this->applicationJSON->application->srv[$i]->respOrg = $applicationMatrix[$i]['mb_group_name'];
			$this->applicationJSON->application->srv[$i]->logoUrl = $applicationMatrix[$i]['mb_group_logo_path'];
			list($hasConstraints, $symbolLink) = $this->hasConstraints("dataset", $applicationMatrix[$i]['dataset_id']);
			$this->applicationJSON->application->srv[$i]->hasConstraints = $hasConstraints;
			$this->applicationJSON->application->srv[$i]->isopen = $applicationMatrix[$i]['isopen'];
			$this->applicationJSON->application->srv[$i]->symbolLink = $symbolLink;
			//TODO: other url - to metadata uuid!
			$this->applicationJSON->application->srv[$i]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_iso19139ToHtml.php?url=" . urlencode($this->protocol . "://" . $this->hostName . "/mapbender/php/mod_dataISOMetadata.php?outputFormat=iso19139&id=") . $applicationMatrix[$i]['fileidentifier'];
			//TODO: preview?
			//TODO: preview and access url should be generated by class_administration.php
			$accessUrl = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_invokeApplicationFromMetadata.php?id=" . $applicationMatrix[$i]['metadata_id'];
			$admin = new administration();
			$notNullElements = array('fkey_gui_id', 'fkey_mapviewer_id', 'fkey_wmc_serial_id');
			foreach ($notNullElements as $notNullElement) {
				if ($applicationMatrix[$i][$notNullElement] == '' || $applicationMatrix[$i][$notNullElement] == null) {
					$applicationMatrix[$i][$notNullElement] = false;
				}
			}
			if (($applicationMatrix[$i]['fkey_gui_id'] != false && $applicationMatrix[$i]['fkey_mapviewer_id'] != false) || ($applicationMatrix[$i]['fkey_wmc_serial_id'] != false && $applicationMatrix[$i]['fkey_mapviewer_id'] != false)) {
				$this->applicationJSON->application->srv[$i]->internal = true;
			} else {
				$this->applicationJSON->application->srv[$i]->internal = false;
			}
			$this->applicationJSON->application->srv[$i]->accessURL = $accessUrl;
			$this->applicationJSON->application->srv[$i]->previewURL = $this->protocol . "://" . $this->hostName . "/mapbender/geoportal/mod_showPreview.php?resource=metadata&id=" . (int) $applicationMatrix[$i]['metadata_id'];
			$spatialSource = "";
			$stateOrProvince = $applicationMatrix[$i]['mb_group_stateorprovince'];
			if ($stateOrProvince == "NULL" || $stateOrProvince == "") {
				$spatialSource = $applicationMatrix[$i]['mb_group_country'];
			} else {
				$spatialSource = $applicationMatrix[$i]['mb_group_stateorprovince'];
			}
			$this->applicationJSON->application->srv[$i]->iso3166 = $spatialSource;

			$this->applicationJSON->application->srv[$i]->bbox = array($applicationMatrix[$i]['bbox']); //TODO: read out bbox from wmc $applicationMatrix[$i][''];
			$this->applicationJSON->application->srv[$i]->timeBegin = date("Y-m-d", strtotime($applicationMatrix[$i]['timebegin']));
			$this->applicationJSON->application->srv[$i]->timeEnd = date("Y-m-d", strtotime($applicationMatrix[$i]['timeend']));
		}
	}

	private function generateWMSMetadataJSON($res, $n)
	{
		//initialize object
		$this->wmsJSON = new stdClass;
		$this->wmsJSON->wms = (object) array(
			'md' => (object) array(
				'nresults' => $n,
				'p' => $this->searchPages,
				'rpp' => $this->maxResults
			),
			'srv' => array()
		);
		//read out records
		$serverCount = 0;
		$wmsMatrix = db_fetch_all($res);
		$layerIdArray = array();
		//read out array with unique wms_ids in wmsMatrix
		$wmsIdArray = array();
		//initialize root layer id;
		$rootLayerId = -1;
		$j = 0;
		//get array with all available layer_id for this user:
		$admin = new administration();
		$this->accessableLayers = $admin->getLayersByPermission($this->userId);
		if ($n != 0) {
			for ($i = 0; $i < count($wmsMatrix); $i++) {
				$layerID = $wmsMatrix[$i]['layer_id'];
				if (!in_array($layerID, $layerIdArray) or !in_array($rootLayerId, $layerIdArray)) {
					$wmsID = $wmsMatrix[$i]['wms_id']; //get first wms id - in the next loop - dont get second, but some else!
					//Select all layers of with this wms_id into new array per WMS - the grouping should be done by wms!
					$subLayers = $this->filter_by_value($wmsMatrix, 'wms_id', $wmsID);
					//Sort array by load_count - problem: maybe there are some groups between where count is to low (they have no load count because you cannot load them by name)? - Therefor we need some ideas - or pull them out of the database and show them greyed out. Another way will be to define a new group (or wms with the same id) for those layers which are more than one integer away from their parents
					$subLayersFlip = $this->flipDiagonally($subLayers);
					$index = array_search($layerID, $subLayersFlip['layer_id']);
					$rootIndex = $this->getLayerParent($subLayersFlip, $index);
					$rootLayerPos = $subLayers[$rootIndex]['layer_pos'];
					$rootLayerId = $subLayers[$rootIndex]['layer_id'];
					array_push($layerIdArray, $rootLayerId);
					//Create object for wms service level
					$this->wmsJSON->wms->srv[$j]->id = (int) $subLayers[$rootIndex]['wms_id'];
					$this->wmsJSON->wms->srv[$j]->title = $subLayers[$rootIndex]['wms_title'];
					$this->wmsJSON->wms->srv[$j]->abstract = $subLayers[$rootIndex]['wms_abstract'];
					$this->wmsJSON->wms->srv[$j]->date = date("d.m.Y", $subLayers[$rootIndex]['wms_timestamp']);
					$this->wmsJSON->wms->srv[$j]->loadCount = (int) $subLayers[$rootIndex]['load_count'];
					$this->wmsJSON->wms->srv[$j]->getMapUrl = $this->getMapUrlfromWMSId((int) $subLayers[$rootIndex]['wms_id']);
					$spatialSource = "";
					$stateOrProvince = $subLayers[$rootIndex]['stateorprovince'];
					if ($stateOrProvince == "NULL" || $stateOrProvince == "") {
						$spatialSource = $subLayers[$rootIndex]['country'];
					} else {
						$spatialSource = $subLayers[$rootIndex]['stateorprovince'];
					}
					$this->wmsJSON->wms->srv[$j]->iso3166 = $spatialSource;
					$this->wmsJSON->wms->srv[$j]->respOrg = $subLayers[$rootIndex]['mb_group_name'];
					$this->wmsJSON->wms->srv[$j]->logoUrl = $subLayers[$rootIndex]['mb_group_logo_path'];
					//check if a disclaimer has to be shown and give the relevant symbol
					list($hasConstraints, $symbolLink, $termsOfUseId) = $this->hasConstraints("wms", $subLayers[$rootIndex]['wms_id']);
					$this->wmsJSON->wms->srv[$j]->hasConstraints = $hasConstraints;
					$this->wmsJSON->wms->srv[$j]->isopen = $subLayers[$rootIndex]['isopen'];
					$this->wmsJSON->wms->srv[$j]->symbolLink = $symbolLink;
					$this->wmsJSON->wms->srv[$j]->license_id = $termsOfUseId;
					//TODO check the field accessconstraints - which should be presented?
					$this->wmsJSON->wms->srv[$j]->status = $subLayers[$rootIndex]['status']; //$wmsMatrix[$i][''];
					$this->wmsJSON->wms->srv[$j]->avail = $subLayers[$rootIndex]['availability']; //$wmsMatrix[$i][''];
					//get info about defined price
					if ($subLayers[$rootIndex]['wms_pricevolume'] == '' or $subLayers[$rootIndex]['wms_pricevolume'] == 0) {
						$this->wmsJSON->wms->srv[$j]->price = NULL;
					} else {
						$this->wmsJSON->wms->srv[$j]->price = $subLayers[$rootIndex]['wms_pricevolume'];
					}
					//get info about logging of resource
					if ($subLayers[$rootIndex]['wms_proxylog'] == NULL or $subLayers[$rootIndex]['wms_proxylog'] == 0) {
						$this->wmsJSON->wms->srv[$j]->logged = false;
					} else {
						$this->wmsJSON->wms->srv[$j]->logged = true;
					}
					//get info about network_accessability
					if ($subLayers[$rootIndex]['wms_network_access'] == NULL or $subLayers[$rootIndex]['wms_network_access'] == 0) {
						$this->wmsJSON->wms->srv[$j]->nwaccess = false;
					} else {
						$this->wmsJSON->wms->srv[$j]->nwaccess = true;
					}
					$this->wmsJSON->wms->srv[$j]->bbox = $subLayers[$rootIndex]['bbox']; //$wmsMatrix[$i][''];
					//Call recursively the child elements, give and pull $layerIdArray to push the done elements in the array to avoid double results
					//generate the layer-entry for the so called root layer - maybe this is only a group layer if there is a gap in the layer hierachy
					$this->wmsJSON->wms->srv[$j]->layer = array();
					$this->wmsJSON->wms->srv[$j]->layer[0]->id = (int) $subLayers[$rootIndex]['layer_id'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->title = $subLayers[$rootIndex]['layer_title'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->name = $subLayers[$rootIndex]['layer_name'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->abstract = $subLayers[$rootIndex]['layer_abstract'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?languageCode=" . $this->languageCode . "&resource=layer&layout=tabs&id=" . (int) $subLayers[$rootIndex]['layer_id'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->previewURL = $this->protocol . "://" . $this->hostName . "/mapbender/geoportal/mod_showPreview.php?resource=layer&id=" . (int) $subLayers[$rootIndex]['layer_id'];
					$legendInfo = $this->getInfofromLayerId($this->wmsJSON->wms->srv[$j]->layer[0]->id);
					$this->wmsJSON->wms->srv[$j]->layer[0]->getLegendGraphicUrl = $legendInfo['getLegendGraphicUrl'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->getLegendGraphicUrlFormat = $legendInfo['getLegendGraphicUrlFormat'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->legendUrl = $legendInfo['legendUrl'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->getCapabilitiesUrl =  $this->protocol . "://" . $this->hostName . "/mapbender/php/wms.php?layer_id=" . (int) $subLayers[$rootIndex]['layer_id'] . "&INSPIRE=1&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
					$this->wmsJSON->wms->srv[$j]->layer[0]->minScale = $legendInfo['minScale'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->maxScale = $legendInfo['maxScale'];
					//pull downloadOptions as json with function from other script: php/mod_getDownloadOptions.php
					$downloadOptionsCs = str_replace("{", "", str_replace("}", "", str_replace("}{", ",", $legendInfo['downloadOptions'])));
					$downloadOptions = json_decode(getDownloadOptions(explode(',', $downloadOptionsCs), $this->protocol . "://" . $this->hostName . "/mapbender/"));
					$this->wmsJSON->wms->srv[$j]->layer[0]->downloadOptions = $downloadOptions;

					if ($subLayers[$rootIndex]['layer_name'] == '') {
						$this->wmsJSON->wms->srv[$j]->layer[0]->loadable = 0;
					} else {
						$this->wmsJSON->wms->srv[$j]->layer[0]->loadable = 1;
					}
					if ($subLayers[$rootIndex]['layer_pos'] == '0') {
						$this->wmsJSON->wms->srv[$j]->layer[0]->isRoot = true;
					} else {
						$this->wmsJSON->wms->srv[$j]->layer[0]->isRoot = false;
					}
					//give info for inspire categories - not relevant for other services or instances of mapbender TODO: comment it if the mapbender installation is not used to generate inspire output
					if ($subLayers[$rootIndex]['md_inspire_cats'] == '') {
						$this->wmsJSON->wms->srv[$j]->layer[0]->inspire = 0;
					} else {
						$this->wmsJSON->wms->srv[$j]->layer[0]->inspire = 1;
					}
					//get info about queryable or not
					if ($subLayers[$rootIndex]['layer_queryable'] == 1) {
						$this->wmsJSON->wms->srv[$j]->layer[0]->queryable = 1;
					} else {
						$this->wmsJSON->wms->srv[$j]->layer[0]->queryable = 0;
					}

					$this->wmsJSON->wms->srv[$j]->layer[0]->loadCount = $subLayers[$rootIndex]['load_count'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->bbox = $subLayers[$rootIndex]['bbox'];
					$this->wmsJSON->wms->srv[$j]->layer[0]->permission = $this->getPermissionValueForLayer($subLayers[$rootIndex]['layer_id'], $subLayers[$rootIndex]['wms_id']); //TODO: Make this much more faster
					//when the entry for the first server has been written, the server entry is fixed and the next one will be a new server or a part of the old one.
					$layerIdArray = $this->writeWMSChilds($layerIdArray, $rootLayerPos, $subLayers, $this->wmsJSON->wms->srv[$j]->layer[0]);
					$j++;
				}
			}
		}
	}

	private function generateWMSMetadata($xmlDoc)
	{
		global $admin;
		$starttime = $this->microtime_float();
		list($sql, $v, $t, $n) = $this->generateSearchSQL();
		//call database search in limits
		$res = db_prep_query($sql, $v, $t);
		if ($this->outputFormat == 'json') {
			//generate json
			$this->generateWMSMetadataJSON($res, $n);
			$usedTime = $this->microtime_float() - $starttime;
			//put in the time to generate the data
			$this->wmsJSON->wms->md->genTime = $usedTime;
			$this->wmsJSON = $this->json->encode($this->wmsJSON);
			if ($this->resultTarget == 'file') {
				$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_" . $this->searchPages . ".json";
				$admin = new administration();
				$admin->putToStorage($filename, $this->wmsJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
			}
			if ($this->resultTarget == 'web' or $this->resultTarget == 'debug') {
				header('Content-Type: application/json');
				echo $this->wmsJSON;
			}
			if ($this->resultTarget == 'internal') {
				$this->internalResult = $this->wmsJSON;
			}
			if ($this->resultTarget == 'categories') {
				header('Content-Type: application/json');
				echo $this->catJSON;
			}
			if ($this->resultTarget == 'webclient') {
				$this->allJSON = new stdClass;
				$this->allJSON->categories = $this->json->decode($this->catJSON);
				$this->allJSON->keywords =  $this->json->decode($this->keyJSON);
				//load filter from file getFromStorage
				$admin = new administration();
				$filename = $this->tempFolder . "/" . $this->searchId . "_filter.json";
				$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
				if ($fileExists == false) {
					$e = new mb_exception("class_metadata.php: No filter json exists!");
				} else {
					$filterJSON = $fileExists;
					$filterJSON = $this->json->decode($filterJSON);
					$this->allJSON->filter = $filterJSON;
				}
				$this->allJSON->wms = $this->json->decode($this->wmsJSON);
				if ($this->originFromHeader != false) {
					header('Access-Control-Allow-Origin: ' . $this->originFromHeader);
				}
				header('Content-Type: application/json');
				echo  $this->json->encode($this->allJSON);
			}
		}
		$usedTime2 = $this->microtime_float() - $starttime;
		$e = new mb_notice("Time to generate WMS-Metadata: " . $usedTime2);
		$e = new mb_notice("Wrote the MD_WMS-File");
	}

	private function generateWFSMetadata($xmlDoc)
	{
		$starttime = $this->microtime_float();
		list($sql, $v, $t, $n) = $this->generateSearchSQL();
		//call database search
		$res = db_prep_query($sql, $v, $t);
		if ($this->outputFormat == 'json') {
			//generate json
			$this->generateWFSMetadataJSON($res, $n);
			$usedTime = $this->microtime_float() - $starttime;
			//put in the time to generate the data
			$this->wfsJSON->wfs->md->genTime = $usedTime;
			$this->wfsJSON = $this->json->encode($this->wfsJSON);
			if ($this->resultTarget == 'file') {
				$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_" . $this->searchPages . ".json";
				$admin = new administration();
				$admin->putToStorage($filename, $this->wfsJSON, TMP_SEARCH_RESULT_STORAGE, TMP_WMC_MAX_AGE);
			}
			if ($this->resultTarget == 'web' or $this->resultTarget == 'debug') {
				header('Content-Type: application/json');
				echo $this->wfsJSON;
			}
			if ($this->resultTarget == 'categories') {
				header('Content-Type: application/json');
				echo $this->catJSON;
			}
			if ($this->resultTarget == 'internal') {
				$this->internalResult = $this->wfsJSON;
			}
			if ($this->resultTarget == 'webclient') {
				$this->allJSON = new stdClass;
				$this->allJSON->categories = $this->json->decode($this->catJSON);
				$this->allJSON->keywords =  $this->json->decode($this->keyJSON);
				//load filter from file getFromStorage
				$admin = new administration();
				$filename = $this->tempFolder . "/" . $this->searchId . "_filter.json";
				$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
				if ($fileExists == false) {
				} else {
					$filterJSON = $fileExists;
					$filterJSON = $this->json->decode($filterJSON);
					$this->allJSON->filter = $filterJSON;
				}
				$this->allJSON->wfs = $this->json->decode($this->wfsJSON);
				if ($this->originFromHeader != false) {
					header('Access-Control-Allow-Origin: ' . $this->originFromHeader);
				}
				header('Content-Type: application/json');
				echo  $this->json->encode($this->allJSON);
			}
		}

		$e = new mb_notice("Time to generate WFS-Metadata: " . $usedTime);
		$e = new mb_notice("Wrote the MD_WFS-File");
	}
	private function generateWMCMetadata($xmlDoc)
	{
		$starttime = $this->microtime_float();
		list($sql, $v, $t, $n) = $this->generateSearchSQL();
		//call database search in limits
		$res = db_prep_query($sql, $v, $t);
		if ($this->outputFormat == 'json') {
			//generate json
			$this->generateWMCMetadataJSON($res, $n);
			$usedTime = $this->microtime_float() - $starttime;
			//put in the time to generate the data
			$this->wmcJSON->wmc->md->genTime = $usedTime;
			$this->wmcJSON = $this->json->encode($this->wmcJSON);
			if ($this->resultTarget == 'file') {
				$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_" . $this->searchPages . ".json";
				$admin = new administration();
				$admin->putToStorage($filename, $this->wmcJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
			}
			if ($this->resultTarget == 'web' or $this->resultTarget == 'debug') {
				header('Content-Type: application/json');
				echo $this->wmcJSON;
			}
			if ($this->resultTarget == 'categories') {
				header('Content-Type: application/json');
				echo $this->catJSON;
			}
			if ($this->resultTarget == 'webclient') {
				$this->allJSON = new stdClass;
				$this->allJSON->categories = $this->json->decode($this->catJSON);
				$this->allJSON->keywords =  $this->json->decode($this->keyJSON);
				//load filter from file getFromStorage
				$admin = new administration();
				$filename = $this->tempFolder . "/" . $this->searchId . "_filter.json";
				$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
				if ($fileExists == false) {
					//$e = new mb_exception("class_metadata.php: No filter json exists!");
				} else {
					$filterJSON = $fileExists;
					$filterJSON = $this->json->decode($filterJSON);
					$this->allJSON->filter = $filterJSON;
				}
				$this->allJSON->wmc = $this->json->decode($this->wmcJSON);
				if ($this->originFromHeader != false) {
					header('Access-Control-Allow-Origin: ' . $this->originFromHeader);
				}
				echo  $this->json->encode($this->allJSON);
			}
		}
		$usedTime2 = $this->microtime_float() - $starttime;
		$e = new mb_notice("Time to generate WMC-Metadata: " . $usedTime2);
		$e = new mb_notice("Wrote the MD_WMC-File");
	}

	private function generateDatasetMetadata($xmlDoc)
	{
		$starttime = $this->microtime_float();
		list($sql, $v, $t, $n) = $this->generateSearchSQL();
		//call database search in limits
		$res = db_prep_query($sql, $v, $t);
		if ($this->outputFormat == 'json') {
			//generate json
			$this->generateDatasetMetadataJSON($res, $n);
			$usedTime = $this->microtime_float() - $starttime;
			//put in the time to generate the data
			$this->datasetJSON->dataset->md->genTime = $usedTime;
			$this->datasetJSON = $this->json->encode($this->datasetJSON);
			if ($this->resultTarget == 'file') {
				$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_" . $this->searchPages . ".json";
				$admin = new administration();
				$admin->putToStorage($filename, $this->datasetJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
			}
			if ($this->resultTarget == 'web' or $this->resultTarget == 'debug') {
				header('Content-Type: application/json');
				echo $this->datasetJSON;
			}
			if ($this->resultTarget == 'categories') {
				header('Content-Type: application/json');
				echo $this->catJSON;
			}
			if ($this->resultTarget == 'webclient') {
				$this->allJSON = new stdClass;
				$this->allJSON->categories = $this->json->decode($this->catJSON);
				$this->allJSON->keywords =  $this->json->decode($this->keyJSON);
				//load filter from file getFromStorage
				$admin = new administration();
				$filename = $this->tempFolder . "/" . $this->searchId . "_filter.json";
				$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
				if ($fileExists == false) {
					//$e = new mb_exception("class_metadata.php: No filter json exists!");
				} else {
					$filterJSON = $fileExists;
					$filterJSON = $this->json->decode($filterJSON);
					$this->allJSON->filter = $filterJSON;
				}
				$this->allJSON->dataset = $this->json->decode($this->datasetJSON);
				if ($this->originFromHeader != false) {
					header('Access-Control-Allow-Origin: ' . $this->originFromHeader);
				}
				header('Content-Type: application/json');
				echo  $this->json->encode($this->allJSON);
			}
		}
		$usedTime2 = $this->microtime_float() - $starttime;
		$e = new mb_notice("Time to generate Dataset-Metadata: " . $usedTime2);
		$e = new mb_notice("Wrote the MD_Dataset-File");
	}

	private function generateApplicationMetadata($xmlDoc)
	{
		$starttime = $this->microtime_float();
		list($sql, $v, $t, $n) = $this->generateSearchSQL();
		//call database search in limits
		$res = db_prep_query($sql, $v, $t);
		if ($this->outputFormat == 'json') {
			//generate json
			$this->generateApplicationMetadataJSON($res, $n);
			$usedTime = $this->microtime_float() - $starttime;
			//put in the time to generate the data
			$this->applicationJSON->application->md->genTime = $usedTime;
			$this->applicationJSON = $this->json->encode($this->applicationJSON);
			if ($this->resultTarget == 'file') {
				$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_" . $this->searchPages . ".json";
				$admin = new administration();
				$admin->putToStorage($filename, $this->applicationJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
			}
			if ($this->resultTarget == 'web' or $this->resultTarget == 'debug') {
				header('Content-Type: application/json');
				echo $this->applicationJSON;
			}
			if ($this->resultTarget == 'categories') {
				header('Content-Type: application/json');
				echo $this->catJSON;
			}
			if ($this->resultTarget == 'webclient') {
				$this->allJSON = new stdClass;
				$this->allJSON->categories = $this->json->decode($this->catJSON);
				$this->allJSON->keywords =  $this->json->decode($this->keyJSON);
				//load filter from file getFromStorage
				$admin = new administration();
				$filename = $this->tempFolder . "/" . $this->searchId . "_filter.json";
				$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
				if ($fileExists == false) {
					//$e = new mb_exception("class_metadata.php: No filter json exists!");
				} else {
					$filterJSON = $fileExists;
					$filterJSON = $this->json->decode($filterJSON);
					$this->allJSON->filter = $filterJSON;
				}
				$this->allJSON->application = $this->json->decode($this->applicationJSON);
				if ($this->originFromHeader != false) {
					header('Access-Control-Allow-Origin: ' . $this->originFromHeader);
				}
				header('Content-Type: application/json');
				echo  $this->json->encode($this->allJSON);
			}
		}
		$usedTime2 = $this->microtime_float() - $starttime;
		$e = new mb_notice("Time to generate Application-Metadata: " . $usedTime2);
		$e = new mb_notice("Wrote the MD_Application-File");
	}

	private function replaceChars_all($text)
	{
		$search = array("ä",  "ö",  "ü",  "Ä",  "Ö",  "Ü",  "ß");
		$repWith = array("ae", "oe", "ue", "AE", "OE", "UE", "ss");
		$replaced = str_replace($search, $repWith, $text);
		return $replaced;
	}

	private function generateSearchSQL()
	{
		//elements needed to exist in mb wfs,wms,wmc view or table:
		//1. textfield - all texts - searchText
		//2. responsible organisations - given id 
		//3. bbox - is not explicit given in the wfs metadata? Since WFS 1.1.0 a latlonbbox is present
		//4. isoTopicCategory - is not been saved til now
		//5. ...
		//parse searchText into different array elements to allow an AND search
		$searchStringArray = $this->generateSearchStringArray();
		$v = array();
		$t = array();
		$sql = "SELECT * from " . $this->searchView . " ";
		$whereStr = "";
		$whereCondArray = array();
		$isTextSearch = "false";
		$e = new mb_notice("Number of used searchstrings: " . count($searchStringArray));
		if ($this->searchText != NULL && trim($this->searchText) != '*') {
			for ($i = 0; $i < count($searchStringArray); $i++) {
				$isTextSearch = "true";
				if ($i > 0) {
					$whereStr .= " AND ";
				}
				$whereStr .= "searchtext LIKE $" . ($i + 1);
				//output for debugging
				$e = new mb_notice("Part of string" . $i . ": " . $searchStringArray[$i]);
				$e = new mb_notice("converted: " . $this->replaceChars_all($searchStringArray[$i]));
				$va = "%" . trim(strtoupper($this->replaceChars_all($searchStringArray[$i]))) . "%";
				$e = new mb_notice($this->searchResources . " Searchtext in SQL: " . $va);
				array_push($v, $va);
				array_push($t, "s");
			}
		}

		// This is only for the later postgis versions. The within and disjoint is to slow, cause there is no usage of the geometrical index in the old versions!
		//check for postgis version
		//sql for get version string
		//get version number
		if ((strtolower($this->searchResources) === "wms" or strtolower($this->searchResources) === "wmc" or strtolower($this->searchResources) === "dataset" or strtolower($this->searchResources) === "wfs" or strtolower($this->searchResources) === "application") & $this->searchBbox != NULL) {
			//decide which type of search should be done
			//check for postgis version cause postgis versions < 1.4 have problems when doing disjoint and inside
			$sqlPostgisVersion = "SELECT postgis_version();";
			$vPostgisVersion = array();
			$tPostgisVersion = array();
			$resPostgisVersion = db_prep_query($sqlPostgisVersion, $vPostgisVersion, $tPostgisVersion);
			// get version string
			while ($row = db_fetch_array($resPostgisVersion)) {
				$postgisVersion = $row['postgis_version'];
				$postgisVersionArray = explode(" ", $postgisVersion);
				$postgisVersionSmall = explode(".", $postgisVersionArray[0]);
				$postgisSubNumber = $postgisVersionSmall[1];
				$e = new mb_notice("class_metadata.php: postgis sub number = " . $postgisSubNumber);
			}
			$e = new mb_notice("class_metadata.php: spatial operator: " . $this->searchTypeBbox);
			if ((int) $postgisSubNumber >= 3) {
				#$spatialFilter = "(the_geom ";	
				$e = new mb_notice("class_metadata.php: spatial operator: " . $this->searchTypeBbox);
				if ($this->searchTypeBbox == 'outside') {
					$spatialFilter = ' disjoint(';
				} elseif ($this->searchTypeBbox == 'inside') {
					$spatialFilter = ' within(';
				} else {
					$spatialFilter = ' intersects(';
				}
				//define spatial filter
				if (count(explode(',', $this->searchBbox)) == 4) {   //if searchBbox has 4 entries
					$spatialFilterCoords = explode(',', $this->searchBbox); //read out searchBbox
					//definition of the spatial filter
					$spatialFilter .= 'the_geom,GeomFromText(\'POLYGON((' . $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . ','; //miny
					$spatialFilter .= $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[3] . ','; //maxy
					$spatialFilter .= $spatialFilterCoords[2]; //maxx
					$spatialFilter .= ' ' . $spatialFilterCoords[3] . ','; //maxy
					$spatialFilter .= $spatialFilterCoords[2]; //maxx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . ','; //miny
					$spatialFilter .= $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . '))\',4326)'; //miny
					$spatialFilter .= ")";
					array_push($whereCondArray, $spatialFilter);
				}
			} else {
				$spatialFilter = ' the_geom && ';
				//define spatial filter
				if (count(explode(',', $this->searchBbox)) == 4) {   //if searchBbox has 4 entries
					$spatialFilterCoords = explode(',', $this->searchBbox); //read out searchBbox
					//definition of the spatial filter
					$spatialFilter .= 'GeomFromText(\'POLYGON((' . $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . ','; //miny
					$spatialFilter .= $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[3] . ','; //maxy
					$spatialFilter .= $spatialFilterCoords[2]; //maxx
					$spatialFilter .= ' ' . $spatialFilterCoords[3] . ','; //maxy
					$spatialFilter .= $spatialFilterCoords[2]; //maxx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . ','; //miny
					$spatialFilter .= $spatialFilterCoords[0]; //minx
					$spatialFilter .= ' ' . $spatialFilterCoords[1] . '))\',4326)'; //miny
					#$spatialFilter .= ",the_geom)";
					array_push($whereCondArray, $spatialFilter);
				}
			}
		}
		//search filter for isopen - open data classification of the managed termsofuse
		//
		if (strtolower($this->searchResources) !== "wmc" && $this->restrictToOpenData) {
			array_push($whereCondArray, '(isopen = 1)');
		}
		//search filter for HVD classification 
		//
		if (strtolower($this->searchResources) == "dataset" && $this->restrictToHvd) {
			//FIX INSPIRE Category ids: [1,2,3]
			//FIX CUSTOM Category ids: [2,3,4]
			//{"hvd_inspire_cat": [1,2,3], "hvd_custom_cat": [3,4,5]}
			//array_push($whereCondArray, '(isopen = 1)');
			$e = new mb_exception("classes/class_metadata.php: inspire cats: " . json_encode($this->hvdInspireCats));
			$hvdFilter = "";
			foreach ($this->hvdInspireCats as $inspireCatId) {
 				$hvdFilter .= " md_inspire_cats like '%{" . $inspireCatId . "}%' OR";
			}
			foreach ($this->hvdCustomCats as $customCatId) {
				$hvdFilter .= " md_inspire_cats like '%{" . $customCatId . "}%' OR";
		   	}
			$hvdFilter = ltrim($hvdFilter, " ");
			//remove trailing " OR"
			$hvdFilter = preg_replace('/ OR$/', '$1', $hvdFilter);
			if ($hvdFilter && $hvdFilter !== "") {
				$hvdFilter = "(" . $hvdFilter . ")";
				array_push($whereCondArray, $hvdFilter);
			}
		}
		//search filter for md_topic_categories
		//
		if ($this->isoCategories != NULL) {
			$isoArray = explode(',', $this->isoCategories);
			$topicCond = "(";
			for ($i = 0; $i < count($isoArray); $i++) {
				if ($i == 0) {
					$topicCond .= "(md_topic_cats LIKE '%{" . $isoArray[$i] . "}%') ";
				} else {
					$topicCond .= "AND (md_topic_cats LIKE '%{" . $isoArray[$i] . "}%') ";
				}
			}
			$topicCond .= ")";
			array_push($whereCondArray, $topicCond);
		}
		//search filter for inspire_categories
		//
		if ($this->inspireThemes != NULL) {

			$inspireArray = explode(',', $this->inspireThemes);
			$inspireCond = "(";
			for ($i = 0; $i < count($inspireArray); $i++) {
				if ($i == 0) {
					$inspireCond .= "(md_inspire_cats LIKE '%{" . $inspireArray[$i] . "}%') ";
				} else {
					$inspireCond .= "AND (md_inspire_cats LIKE '%{" . $inspireArray[$i] . "}%') ";
				}
			}
			$inspireCond .= ")";
			array_push($whereCondArray, $inspireCond);
		}
		//search filter for custom_categories
		//
		if ($this->customCategories != NULL) {

			$customArray = explode(',', $this->customCategories);
			$customCond = "(";
			for ($i = 0; $i < count($customArray); $i++) {
				if ($i == 0) {
					$customCond .= "(md_custom_cats LIKE '%{" . $customArray[$i] . "}%') ";
				} else {
					$customCond .= "AND (md_custom_cats LIKE '%{" . $customArray[$i] . "}%') ";
				}
			}
			$customCond .= ")";
			array_push($whereCondArray, $customCond);
		}

		//date condition
		//if begin and end are set

		if ($this->regTimeBegin != NULL && $this->regTimeEnd != NULL) {
			$time = "(TO_TIMESTAMP(" . $this->searchResources . "_timestamp) BETWEEN '" . $this->regTimeBegin . "' AND '" . $this->regTimeEnd . "')";
			array_push($whereCondArray, $time);
			//only begin is set		
		}
		if ($this->regTimeBegin != NULL && $this->regTimeEnd == NULL) {
			$time = "(TO_TIMESTAMP(" . $this->searchResources . "_timestamp) > '" . $this->regTimeBegin . "')";
			array_push($whereCondArray, $time);
		}
		if ($this->regTimeBegin == NULL && $this->regTimeEnd != NULL) {
			$time = "(TO_TIMESTAMP(" . $this->searchResources . "_timestamp) < '" . $this->regTimeEnd . "')";
			array_push($whereCondArray, $time);
		}

		//filter for data actuality (only for datasets)
		if (strtolower($this->searchResources) === "dataset" || strtolower($this->searchResources) === "application") {
			if ($this->timeBegin != NULL && $this->timeEnd != NULL) {
				$time = "((to_timestamp('" . $this->timeBegin . "','YYYY-MM-DD'),to_timestamp('" . $this->timeEnd . "','YYYY-MM-DD')) OVERLAPS (timebegin,timeend))";
				array_push($whereCondArray, $time);
				//only begin is set		
			}
			if ($this->timeBegin != NULL && $this->timeEnd == NULL) {
				$time = "(timeend >= '" . $this->timeBegin . "')";
				array_push($whereCondArray, $time);
			}
			if ($this->timeBegin == NULL && $this->timeEnd != NULL) {
				$time = "(timeend <= '" . $this->timeEnd . "')";
				array_push($whereCondArray, $time);
			}
		}
		//department condition
		//TODO: generate filter for new sql check if at least some department is requested
		//generate array
		if ($this->registratingDepartments != NULL) {
			$dep = " department IN (" . $this->registratingDepartments . ") ";
			array_push($whereCondArray, $dep);
		}

		//resourceId conditions
		if ($this->resourceIds != NULL) {
			$resourceCondition = " " . $this->databaseIdColumnName . " IN (" . $this->resourceIds . ") ";
			array_push($whereCondArray, $resourceCondition);
		}

		// Creating the WHERE clause, based on a array
		if (count($whereCondArray) > 0) {
			$txt_whereCond = "";
			for ($index = 0; $index < sizeof($whereCondArray); $index++) {
				$array_element = $whereCondArray[$index];
				if ($isTextSearch == "true") {
					$txt_whereCond .= " AND " . $array_element;
				} else {
					if ($index > 0) {
						$txt_whereCond .= " AND " . $array_element;
					} else {
						$txt_whereCond .= " " . $array_element;
					}
				}
			}
			$whereStr .= $txt_whereCond;
		}
		//Add WHERE condition to search
		if ($whereStr !== '') {
			$whereStr = "WHERE " . $whereStr;
		}
		$sql .= $whereStr;
		//TODO ORDER BY in SQL - not necessary for counting things:
		$sql .= $this->orderBy;
		//Calculate Paging for OFFSET and LIMIT values:
		$offset = ((int) $this->maxResults) * ((int) $this->searchPages - 1);
		$limit = (int) $this->maxResults;
		//defining range for paging
		$sql .= " LIMIT " . $limit . " OFFSET " . $offset . "";
		//Print out search SQL term
		$e = new mb_notice("class_metadata.php: Search => SQL-Request of " . $this->searchResources . " service metadata: " . $sql . "");
		//parameter: searchId -> can be used global, searchResources -> is only one type per instance!!-> global,which categories -> can be defined global! $whereStr
		$n = $this->writeCategories($whereStr, $v, $t);
		//write counts to filesystem to avoid to many database connections
		//only write them, if searchId is given - problem: searches with same searchId's maybe get wrong information
		return array($sql, $v, $t, $n);
	}

	/** Function to write a json file which includes the categories of the search result for each searchResource - wms/wfs/wmc/georss, new: it should also count the keyword distribution of the searchResource ans save it as a special json file!

	 **/
	private function writeCategories($whereStr, $v, $t)
	{
		//generate count sql
		//generate count of all entries	
		if ($this->searchResources != 'application') {
			$sqlN = "SELECT count(" . $this->searchResources . "_id) from " . $this->searchView . " ";
		} else {
			$sqlN = "SELECT count(metadata_id) from " . $this->searchView . " ";
		}
		if ($whereStr != '') {
			$sqlN .= $whereStr;
		}
		//Get total number of results 
		$count = db_prep_query($sqlN, $v, $t);
		$n = db_fetch_all($count);
		#echo "<br>N: ".var_dump($n)."<br>";
		$n = $n[0]['count'];
		$e = new mb_notice("class_metadata.php: Search => SQL-Request of " . $this->searchResources . " service metadata N: " . $sqlN . " Number of found objects: " . $n);
		if ($this->searchId != 'dummysearch') { //searchId is not the default id! - it has been explicitly defined 
			//check if cat file already exists:
			//filename to search for:
			$filename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_cat.json";
			$keyFilename = $this->tempFolder . "/" . $this->searchId . "_" . $this->searchResources . "_keywords.json";
			//check filename exists in storage
			$admin = new administration();
			$fileExists = $admin->getFromStorage($filename, TMP_SEARCH_RESULT_STORAGE);
			if ($fileExists == false  or $this->resultTarget == 'debug') { //TODO at the moment the cat file will be overwritten - change this in production system
				//open category file for results
				$this->catJSON = new stdClass;
				$this->catJSON->searchMD = (object) array(
					'searchId' => $this->searchId,
					'n' => $n
				);
				//new: also generate a json object for the keyword distribution
				$this->keyJSON = new stdClass;
				$this->keyJSON->tagCloud = (object) array(
					'searchId' => $this->searchId,
					'maxFontSize' => $this->maxFontSize,
					'maxObjects' => $this->maxObjects,
					'title' => $this->keywordTitle,
					'tags' => array()
				);
				$this->inc = ($this->maxFontSize - $this->minFontSize) / $this->maxObjects; //maybe 10 or 5 or ...
				//generate the list of category counts
				$sqlCat = array();
				//generate the sql for the keyword count
				$sqlKeyword = "select keyword.keyword, COUNT(*) ";
				$sqlKeyword .= "FROM (select ";
				$sqlKeyword .= $this->databaseIdColumnName;
				//$sqlKeyword .= " FROM ".$this->searchView." WHERE ".$whereStr.") as a";
				if ($whereStr != '') {
					$sqlKeyword .= " FROM " . $this->searchView . " " . $whereStr . ") as a";
				} else {
					$sqlKeyword .= " FROM " . $this->searchView . ") as a";
				}
				$sqlKeyword .= " INNER JOIN " . $this->databaseTableName . "_keyword ON (";
				$sqlKeyword .= $this->databaseTableName . "_keyword.fkey_" . $this->databaseIdColumnName . " = a.";
				$sqlKeyword .= $this->databaseIdColumnName . ") ";
				$sqlKeyword .= "INNER JOIN keyword ON (keyword.keyword_id=" . $this->databaseTableName . "_keyword.fkey_keyword_id) WHERE (keyword.keyword NOTNULL AND keyword.keyword <> '') ";
				$sqlKeyword .= "GROUP BY keyword.keyword  ORDER BY COUNT DESC LIMIT  " . $this->maxObjects;
				//do sql select for keyword cloud
				$resKeyword = db_prep_query($sqlKeyword, $v, $t);
				$keywordCounts = db_fetch_all($resKeyword);

				if (count($keywordCounts) > 0) {
					$this->maxWeight = $keywordCounts[0]['count'];
					for ($j = 0; $j < count($keywordCounts); $j++) {
						if ($this->scale == 'linear') {
							//order in a linear scale desc
							$keywordCounts[$j]['count'] = $this->maxFontSize - ($j * $this->inc);
						} else {
							//set weight prop to count 
							$keywordCounts[$j]['count'] = $keywordCounts[$j]['count'] * $this->maxFontSize / $this->maxWeight;
						}
					}
					shuffle($keywordCounts);
					for ($j = 0; $j < count($keywordCounts); $j++) {
						$this->keyJSON->tagCloud->tags[$j]->title = $keywordCounts[$j]['keyword'];
						$this->keyJSON->tagCloud->tags[$j]->weight = $keywordCounts[$j]['count'];
						$paramValue = $this->getValueForParam('searchText', $this->searchURL);
						//delete resources part from query and set some new one
						$searchUrlKeywords = $this->delTotalFromQuery('searchResources', $this->searchURL);
						//append the resource parameter:
						$searchUrlKeywords .= '&searchResources=' . $this->searchResources;
						$e = new mb_notice("class_metadata.php: value " . $paramValue . " for searchText param found");
						$paramValue = urldecode($paramValue);
						if ($paramValue == false || $paramValue == '*') {
							$this->keyJSON->tagCloud->tags[$j]->url = $searchUrlKeywords . "&searchText=" . $keywordCounts[$j]['keyword'];
						} else {
							$this->keyJSON->tagCloud->tags[$j]->url = $this->addToQuery('searchText', $searchUrlKeywords, $keywordCounts[$j]['keyword'], $paramValue);
						}
					}
				}
				//encode json!
				$this->keyJSON = $this->json->encode($this->keyJSON);
				//write clouds to file
				$admin = new administration();
				$admin->putToStorage($keyFilename, $this->keyJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
				if ($this->resultTarget == 'debug') {
					echo "<br>DEBUG: show keywords: <br>" . $this->keyJSON . "<br><br>";
				}

				//check if categories are defined for the resource
				if ($this->resourceClasses != NULL) {
					$this->catJSON->searchMD->category = array();
					for ($i = 0; $i < count($this->resourceClasses); $i++) {
						//TODO: not to set the classification?
						$this->catJSON->searchMD->category[$i]->title = $this->resourceClassifications[$i]['title'];
						$sqlCat[$i] = "SELECT " . $this->resourceClassifications[$i]['tablename'];
						$sqlCat[$i] .= "." . $this->resourceClassifications[$i]['tablename'] . "_id, ";
						$sqlCat[$i] .= " " . $this->resourceClassifications[$i]['tablename'] . ".";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_code_";
						$sqlCat[$i] .= $this->languageCode . ", COUNT(*) FROM " . $this->searchView;

						//first join for connection table
						$sqlCat[$i] .= " INNER JOIN " . $this->resourceClassifications[$i]['relation_' . $this->searchResources];
						$sqlCat[$i] .= " ON (";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['relation_' . $this->searchResources] . ".fkey_";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['id_' . $this->searchResources] . "=" . $this->searchView;
						$sqlCat[$i] .= "." . $this->resourceClassifications[$i]['id_' . $this->searchResources];
						$sqlCat[$i] .= ") INNER JOIN ";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . " ON (";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . ".";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_id=";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['relation_' . $this->searchResources] . ".fkey_";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_id)";
						//the following is needed to filter the custom cats for those which should not be seen in the classification
						if ($this->resourceClassifications[$i]['title'] != $this->resourceClassifications[2]['title']) {
							if ($whereStr != '') {
								$sqlCat[$i] .= " " . $whereStr . " GROUP BY ";
							} else {
								$sqlCat[$i] .= " GROUP BY ";
							}
						} else {
							if ($whereStr != '') {
								$sqlCat[$i] .= " " . $whereStr . $this->whereStrCatExtension . " GROUP BY ";
							} else {
								$sqlCat[$i] .= " WHERE " . $this->whereStrCatExtension . " GROUP BY ";
							}
						}
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . ".";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_id,";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . ".";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_code_" . $this->languageCode . " ORDER BY ";
						$sqlCat[$i] .= $this->resourceClassifications[$i]['tablename'] . "_id";
						$sqlCategory = $sqlCat[$i];
						$sqlCategory = str_replace("WHERE  AND", "WHERE", $sqlCategory);
						//call sql for count of category
						$res = db_prep_query($sqlCategory, $v, $t);
						$e = new mb_notice("class_metadata: countCatsql: " . $sqlCategory);
						$categoryCounts = db_fetch_all($res);
						//if none found: $categoryCounts=false
						if ($categoryCounts) {
							//write results in json object
							if (count($categoryCounts) > 0) {
								$this->catJSON->searchMD->category[$i]->subcat = array();
								for ($j = 0; $j < count($categoryCounts); $j++) {
									$this->catJSON->searchMD->category[$i]->subcat[$j]->id = $categoryCounts[$j][$this->resourceClassifications[$i]['tablename'] . "_id"];
									$this->catJSON->searchMD->category[$i]->subcat[$j]->title = $categoryCounts[$j][$this->resourceClassifications[$i]['tablename'] . "_code_" . $this->languageCode];
									$this->catJSON->searchMD->category[$i]->subcat[$j]->count = $categoryCounts[$j]['count'];
									//delete requestParam for this category and for id - cause a new search is started from searchURL
									$filteredSearchString = $this->delTotalFromQuery('searchId', $this->searchURL);
									//uncomment the following line if a or category search is intended
									//TODO: maybe adopt this to do a and search and not a or like it is done now
									//check if category search was requested and rewrite the search url
									//get the value of the param as string or false if not set!
									$paramValue = $this->getValueForParam($this->resourceClassifications[$i]['requestName'], $filteredSearchString);
									$paramValue = urldecode($paramValue);
									if ($paramValue == false) {
										$filteredSearchString .= "&" . $this->resourceClassifications[$i]['requestName'] . "=" . $categoryCounts[$j][$this->resourceClassifications[$i]['tablename'] . "_id"];
									} else {
										$filteredSearchString = $this->addToQuery($this->resourceClassifications[$i]['requestName'], $filteredSearchString, $categoryCounts[$j][$this->resourceClassifications[$i]['tablename'] . "_id"], $paramValue);
									}
									$this->catJSON->searchMD->category[$i]->subcat[$j]->filterLink = $filteredSearchString;
								}
							}
						}
						$e = new mb_notice("class_metadata: countsql: " . $sqlCat[$i]);
					}
					//*********************************************************************
					//create a facet for publishing organizations
					$i = 3;
					switch ($this->languageCode) {
						case "de":
							$this->catJSON->searchMD->category[$i]->title = "Organisationen";
							break;
						case "en":
							$this->catJSON->searchMD->category[$i]->title = "Organizations";
							break;
						default:
							$this->catJSON->searchMD->category[$i]->title = "Organizations";
							break;
					}
					$sqlCat[$i] = "SELECT department AS id, COUNT(department) AS count, mb_group.mb_group_name AS title FROM " . $this->searchView . " INNER JOIN mb_group ON department = mb_group.mb_group_id";
					if ($this->resourceClassifications[$i]['title'] != $this->resourceClassifications[2]['title']) {
						if ($whereStr != '') {
							$sqlCat[$i] .= " " . $whereStr . " GROUP BY ";
						} else {
							$sqlCat[$i] .= " GROUP BY ";
						}
					} else {
						if ($whereStr != '') {
							$sqlCat[$i] .= " " . $whereStr . $this->whereStrCatExtension . " GROUP BY ";
						} else {
							$sqlCat[$i] .= " WHERE " . $this->whereStrCatExtension . " GROUP BY ";
						}
					}
					$sqlCat[$i] .= "department, mb_group.mb_group_name";
					$sqlCategory = $sqlCat[$i];
					//TODO solve problem
					$sqlCategory = str_replace("WHERE  AND", "WHERE", $sqlCategory);
					//call sql for count of category
					$res = db_prep_query($sqlCategory, $v, $t);
					$e = new mb_notice("class_metadata: countCatsql: " . $sqlCategory);
					$categoryCounts = db_fetch_all($res);
					//if none found: $categoryCounts=false
					if ($categoryCounts) {
						//write results in json object
						if (count($categoryCounts) > 0) {
							$this->catJSON->searchMD->category[$i]->subcat = array();
							for ($j = 0; $j < count($categoryCounts); $j++) {
								$this->catJSON->searchMD->category[$i]->subcat[$j]->id = $categoryCounts[$j]["id"];
								$this->catJSON->searchMD->category[$i]->subcat[$j]->title = $categoryCounts[$j]["title"];
								$this->catJSON->searchMD->category[$i]->subcat[$j]->count = $categoryCounts[$j]['count'];
								//delete requestParam for this category and for id - cause a new search is started from searchURL
								$filteredSearchString = $this->delTotalFromQuery('searchId', $this->searchURL);
								//uncomment the following line if a or category search is intended
								//$filteredSearchString = $this->delTotalFromQuery($this->resourceClassifications[$i]['requestName'],$filteredSearchString);
								//TODO: maybe adopt this to do a and search and not a or like it is done now
								//check if category search was requested and rewrite the search url
								//get the value of the param as string or false if not set!
								$paramValue = $this->getValueForParam($this->resourceClassifications[$i]['requestName'], $filteredSearchString);
								$paramValue = urldecode($paramValue);
								if ($paramValue == false) {
									$filteredSearchString .= "&" . $this->resourceClassifications[$i]['requestName'] . "=" . $categoryCounts[$j]["id"];
								} else {
									$filteredSearchString = $this->addToQuery($this->resourceClassifications[$i]['requestName'], $filteredSearchString, $categoryCounts[$j]["id"], $paramValue);
								}
								$this->catJSON->searchMD->category[$i]->subcat[$j]->filterLink = $filteredSearchString;
							}
						}
					}
					$e = new mb_notice("class_metadata: countsql: " . $sqlCat[$i]);
				}

				$this->catJSON = $this->json->encode($this->catJSON);
				//write categories files only when file is requested and the searchid was not used before!
				if ($this->resultTarget == 'file') {
					$admin = new administration();
					$admin->putToStorage($filename, $this->catJSON, TMP_SEARCH_RESULT_STORAGE, TMP_SEARCH_RESULT_MAX_AGE);
				}
				if ($this->resultTarget == 'debug') {
					echo "<br>DEBUG: show categories: <br>" . $this->catJSON . "<br><br>";
				}
			} else {
				$e = new mb_notice("class_metadata: " . $this->searchResources . "_class_file: " . $filename . " already exists - no new one is generated!");
			}
		} else {
			if ($this->resultTarget == 'debug') {
				echo "<br>DEBUG: Standard ID dummysearch was invoked - classifications won't be counted!<br>";
			}
			$e = new mb_notice("class_metadata: standard dummysearch was invoked - classifications won't be counted!");
		}
		return $n;
	}


	private function getPermissionValueForWFS($wfs_id, $wfs_conf_id)
	{
		//TODO: Set Email of owner into view for ressource - so it don't have to be searched?
		$return_permission = "";
		//get permission
		$admin = new administration();
		$myWFSconfs = $admin->getWfsConfByPermission($this->userId);
		$this->myWFSConfs = $myWFSconfs;
		for ($index = 0; $index < sizeof($this->myWFSConfs); $index++) {
			$array_element = $this->myWFSConfs[$index];
		}
		if (in_array($wfs_conf_id, $this->myWFSConfs)) {
			$return_permission = "true";
		} else {
			$sql = "SELECT wfs.wfs_id, mb_user.mb_user_email as email FROM wfs, mb_user where wfs.wfs_owner=mb_user.mb_user_id " . "and wfs.wfs_id=$1";
			$v = array($wfs_id);
			$t = array('i');
			$res = db_prep_query($sql, $v, $t);
			// get email
			$mail = "";
			while ($row = db_fetch_array($res)) {
				$mail = $row['email'];
				$return_permission = $mail;
			}
		}
		return $return_permission;
	}

	private function getMapUrlfromWMSId($wmsId)
	{
		$sql = "SELECT wms_getmap, wms_owsproxy FROM wms WHERE wms_id = $1";
		$v = array($wmsId);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		while ($row = db_fetch_array($res)) {
			$getMap = $row['wms_getmap'];
			$owsProxy = $row['wms_owsproxy'];
		}
		//hostname does not exist! - use hostname from parameter instead
		if ($owsProxy != null && $owsProxy != '') {
			//create dummy session - no one knows the user which requests this metadata!
			$sessionId = "00000000000000000000000000000000";
			$getMap = $this->protocol . "://" . $this->hostName . "/owsproxy/" . $sessionId . "/" . $owsProxy . "?";
		}
		return $getMap;
	}

	private function getInfofromLayerId($layerId)
	{
		$sql = "SELECT layer_wms.*, layer_style.legendurl, layer_style.legendurlformat FROM (SELECT layer_id, f_get_download_options_for_layer(layer_id) as layer_metadata, layer_minscale, layer_maxscale, wms_getlegendurl, wms_owsproxy FROM layer INNER JOIN wms ON layer.fkey_wms_id = wms.wms_id WHERE layer.layer_id = $1) as layer_wms LEFT OUTER JOIN layer_style ON layer_style.fkey_layer_id = layer_wms.layer_id";
		$v = array($layerId);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		while ($row = db_fetch_array($res)) {
			$getLegendUrl = $row['wms_getlegendurl'];
			$legendUrl = $row['legendurl'];
			$legendUrlFormat = $row['legendurlformat'];
			$owsProxy = $row['wms_owsproxy'];
			$minScale = $row['layer_minscale'];
			$maxScale = $row['layer_maxscale'];
			$downloadOptions = $row['layer_metadata'];
		}
		//hostname does not exist! - use hostname from parameter instead
		if ($owsProxy != null && $owsProxy != '' && $getLegendUrl != '' && $getLegendUrl != null) {
			$sessionId = "00000000000000000000000000000000";
			$getLegendUrlNew = $this->protocol . "://" . $this->hostName . "/owsproxy/" . $sessionId . "/" . $owsProxy . "?";
			//also let go legendurl thru owsproxy exchange first legendurl part with owsproxy part!
			$legendUrl = str_replace($getLegendUrl, $getLegendUrlNew, $legendUrl);
			$getLegendUrl = $getLegendUrlNew;
		}
		$returnArray['legendUrl'] = $legendUrl;
		$returnArray['getLegendGraphicUrl'] = $getLegendUrl;
		$returnArray['getLegendGraphicUrlFormat'] = $legendUrlFormat;
		$returnArray['minScale'] = $minScale;
		$returnArray['maxScale'] = $maxScale;
		$returnArray['downloadOptions'] = $downloadOptions;
		return $returnArray;
	}

	private function getInfofromFeaturetypeId($featuretypeId)
	{
		$admin = new administration();
		$sql = "SELECT wfs_id, wfs_version, wfs_getcapabilities, wfs_describefeaturetype, featuretype_name, wfs_owsproxy FROM wfs_featuretype INNER JOIN wfs ON wfs_featuretype.fkey_wfs_id = wfs.wfs_id WHERE wfs_featuretype.featuretype_id = $1";
		$v = array($featuretypeId);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		while ($row = db_fetch_array($res)) {
			$getCapabilitiesUrl = $row['wfs_getcapabilities'];
			$describeFeaturetypeUrl = $row['wfs_describefeaturetype'];
			$featuretypeName = $row['featuretype_name'];
			$owsProxy = $row['wfs_owsproxy'];
			$wfsVersion = $row['wfs_version'];
			$wfsId = $row['wfs_id'];
		}
		//if proxy is activated change request urls
		if ($owsProxy != null && $owsProxy != '') {
			$getCapabilitiesUrl = $this->protocol . "://" . $this->hostName . "/registry/wfs/" . $wfsId . "?SERVICE=WFS&VERSION=" . $wfsVersion . "&REQUEST=GetCapabilities";
			$describeFeaturetypeUrl = $this->protocol . "://" . $this->hostName . "/registry/wfs/" . $wfsId . "?SERVICE=WFS&VERSION=" . $wfsVersion . "&REQUEST=DescribeFeaturetype&typename=" . $featuretypeName;
		} else {
			$getCapabilitiesUrl = $admin->checkURL($getCapabilitiesUrl) . "SERVICE=WFS&VERSION=" . $wfsVersion . "&REQUEST=GetCapabilities";
			$describeFeaturetypeUrl = $admin->checkURL($describeFeaturetypeUrl) . "SERVICE=WFS&VERSION=" . $wfsVersion . "&REQUEST=DescribeFeaturetype&typename=" . $featuretypeName;
		}
		$returnArray['getCapabilitiesUrl'] = $getCapabilitiesUrl;
		$returnArray['describeFeaturetypeUrl'] = $describeFeaturetypeUrl;
		$returnArray['featuretypeName'] = $featuretypeName;
		$returnArray['owsProxy'] = $owsProxy;
		return $returnArray;
	}

	private function getPermissionValueForLayer($layerId, $wmsId)
	{
		//TODO: Set Email of owner into view for ressource - so it don't have to be searched?
		$return_permission = "";
		if (in_array($layerId, $this->accessableLayers)) {
			$return_permission = "true";
			return $return_permission;
		} else {
			$sql = "SELECT mb_user.mb_user_email as email FROM wms, mb_user WHERE wms.wms_owner=mb_user.mb_user_id";
			$sql .= " AND wms.wms_id=$1";
			$v = array($wmsId);
			$t = array('i');
			$res = db_prep_query($sql, $v, $t);
			$mail = "";
			while ($row = db_fetch_array($res)) {
				$mail = $row['email'];
				$return_permission = $mail;
			}
			return $return_permission;
		}
	}



	private function generateSearchStringArray()
	{
		$asstr = array();
		if ($this->searchText != "false") {
			$asstr = explode(",", $this->searchText);
			for ($i = 0; $i < count($asstr); $i++) {
				$asstr[$i] = ltrim($asstr[$i]);
				$asstr[$i] = rtrim($asstr[$i]);
			}
		} else {
			$asstr[0] = '%';
		}
		//check for single wildcard search
		$e = new mb_notice('class_metadata.php: searchText: ' . $this->searchText);
		if ((count($asstr) == 1) && (($asstr[0] == '*') || ($asstr[0] === 'false'))) {
			$asstr[0] = '%';
		}
		$e = new mb_notice('class_metadata.php: asstr[0]: ' . $asstr[0]);
		return $asstr;
	}

	//out of php doc - test if it is faster than normal array_search	
	private function fast_in_array($elem, $array)
	{
		$top = sizeof($array) - 1;
		$bot = 0;
		while ($top >= $bot) {
			$p = floor(($top + $bot) / 2);
			if ($array[$p] < $elem) $bot = $p + 1;
			elseif ($array[$p] > $elem) $top = $p - 1;
			else return TRUE;
		}
		return FALSE;
	}
	/*
	* filtering an array
	*/
	private function filter_by_value($array, $index, $value)
	{
		if (is_array($array) && count($array) > 0) {
			foreach (array_keys($array) as $key) {
				$temp[$key] = $array[$key][$index];
				if ($temp[$key] == $value) {
					$newarray[$key] = $array[$key];
				}
			}
		}
		return $newarray;
	}

	//function to get the parent of the given layer by crawling the layertree upwards
	private function getLayerParent($layerArray, $index)
	{
		//only layers of one service should be in $layerArray
		$layerIDKey = $layerArray['layer_id'][$index];
		$layerParentPos = $layerArray['layer_parent'][$index]; //get first parent object position
		if ($layerParentPos == '') {
			//root layer directly found
			return $index;
		}
		//Initialize index of layer parent - first it references the layer itself
		$layerParentIndex = $index;
		//loop to search higher parent objects - maybe this can be faster if the loop is not used over all sublayer elements! Do a while loop instead!
		$highestParentLayerNotFound = true;
		while ($highestParentLayerNotFound) {
			$layerParentIndexNew = array_search((string) $layerParentPos, $layerArray['layer_pos']);
			if ($layerParentIndexNew != false) {
				$layerParentIndex = $layerParentIndexNew;
				$layerParentPos = $layerArray['layer_parent'][$layerParentIndex];
				if ($layerParentPos == '') {
					$highestParentLayerNotFound = false;
					return $layerParentIndex; //a real root layer was found!
				}
			} else {
				$highestParentLayerNotFound = false; //no higher layer could be found
				return $layerParentIndex;
			}
		}
		return $layerParentIndex;
	}

	//function to write the child elements to the resulting wms object -> object is given by reference
	private function writeWMSChilds($layerIdArray, $rootLayerPos, $subLayers, &$servObject)
	{
		$childLayers = $this->filter_by_value($subLayers, 'layer_parent', $rootLayerPos); //the root layer position in the sublayer array was located before. In this step, all layers will be pulled out of sublayer, where root layer position is parent object
		$countsublayer = 0;
		//if child exists create a new layer array for these 
		if (count($childLayers) != 0) {
			$servObject->layer = array();
		}
		foreach ($childLayers as $child) {
			$servObject->layer[$countsublayer]->id = $child['layer_id'];
			$servObject->layer[$countsublayer]->title = $child['layer_title'];
			$servObject->layer[$countsublayer]->name = $child['layer_name'];
			$servObject->layer[$countsublayer]->abstract = $child['layer_abstract'];
			$servObject->layer[$countsublayer]->previewURL = $this->protocol . "://" . $this->hostName . "/mapbender/geoportal/mod_showPreview.php?resource=layer&id=" . $child['layer_id'];
			$servObject->layer[$countsublayer]->getCapabilitiesUrl = $this->protocol . "://" . $this->hostName . "/mapbender/php/wms.php?layer_id=" . $child['layer_id'] . "&INSPIRE=1&VERSION=1.1.1&SERVICE=WMS&REQUEST=GetCapabilities";
			$legendInfo = $this->getInfofromLayerId($servObject->layer[$countsublayer]->id);
			$servObject->layer[$countsublayer]->getLegendGraphicUrl = $legendInfo['getLegendGraphicUrl'];
			$servObject->layer[$countsublayer]->getLegendGraphicUrlFormat = $legendInfo['getLegendGraphicUrlFormat'];
			$servObject->layer[$countsublayer]->legendUrl = $legendInfo['legendUrl'];
			$servObject->layer[$countsublayer]->minScale = $legendInfo['minScale'];
			$servObject->layer[$countsublayer]->maxScale = $legendInfo['maxScale'];
			$downloadOptionsCs = str_replace("{", "", str_replace("}", "", str_replace("}{", ",", $legendInfo['downloadOptions'])));
			$downloadOptions = json_decode(getDownloadOptions(explode(',', $downloadOptionsCs), $this->protocol . "://" . $this->hostName . "/mapbender/"));
			$servObject->layer[$countsublayer]->downloadOptions = $downloadOptions;
			$servObject->layer[$countsublayer]->mdLink = $this->protocol . "://" . $this->hostName . "/mapbender/php/mod_showMetadata.php?languageCode=" . $this->languageCode . "&resource=layer&layout=tabs&id=" . $child['layer_id'];
			if ($child['layer_name'] == '') {
				$servObject->layer[$countsublayer]->loadable = 0;
			} else {
				$servObject->layer[$countsublayer]->loadable = 1;
			}
			//give info for inspire categories - not relevant for other services or instances of mapbender TODO: comment it if the mapbender installation is not used to generate inspire output
			if ($child['md_inspire_cats'] == '') {
				$servObject->layer[$countsublayer]->inspire = 0;
			} else {
				$servObject->layer[$countsublayer]->inspire = 1;
			}
			//get info about queryable or not
			if ($child['layer_queryable'] == 1) {
				$servObject->layer[$countsublayer]->queryable = 1;
			} else {
				$servObject->layer[$countsublayer]->queryable = 0;
			}

			$servObject->layer[$countsublayer]->loadCount = $child['load_count'];
			$servObject->layer[$countsublayer]->bbox = $child['bbox'];
			$servObject->layer[$countsublayer]->permission = $this->getPermissionValueForLayer($child['layer_id'], $child['wms_id']); //TODO: make this much faster!!!! - is done by collecting all accessable resources once. Maybe this has to be adopted if the count of the resources become higher
			//call this function itself - search sublayers in the layer object.
			$layerIdArray = $this->writeWMSChilds($layerIdArray, $child['layer_pos'], $subLayers, $servObject->layer[$countsublayer]); //TODO create a timeout condition !
			array_push($layerIdArray, $child['layer_id']); //child have been identified and recursively written 
			$countsublayer++;
		}
		return $layerIdArray;
	}

	private function hasConstraints($type, $id)
	{
		if ($type == "wms") {
			$sql = "SELECT wms.accessconstraints, wms.fees, wms.wms_network_access , wms.wms_pricevolume, wms.wms_proxylog, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id, termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink from wms LEFT OUTER JOIN";
			$sql .= "  wms_termsofuse ON  (wms.wms_id = wms_termsofuse.fkey_wms_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (wms_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where wms.wms_id = $1";
		}
		if ($type == "wfs") {
			$sql = "SELECT accessconstraints, fees, wfs_network_access , termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id ,termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink from wfs LEFT OUTER JOIN";
			$sql .= "  wfs_termsofuse ON  (wfs.wfs_id = wfs_termsofuse.fkey_wfs_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (wfs_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where wfs.wfs_id = $1";
		}
		if ($type == "dataset") {
			$sql = "SELECT constraints as accessconstraints, fees, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id ,termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink from mb_metadata LEFT OUTER JOIN";
			$sql .= "  md_termsofuse ON  (mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (md_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where mb_metadata.metadata_id = $1";
		}
		if ($type == "application") {
			$sql = "SELECT constraints as accessconstraints, fees, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id ,termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink from mb_metadata LEFT OUTER JOIN";
			$sql .= "  md_termsofuse ON  (mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (md_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where mb_metadata.metadata_id = $1";
		}

		$v = array();
		$t = array();
		array_push($t, "i");
		array_push($v, $id);
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);
		//TODO: use MAPBENDER_PATH!
		if ((isset($row[$type . '_proxylog']) & $row[$type . '_proxylog'] != 0) or strtoupper($row['accessconstraints']) != "NONE" or strtoupper($row['fees']) != "NONE" or isset($row['termsofuse_id'])) {
			//service has some constraints defined!
			//give symbol and true
			//termsofuse symbol or exclamation mark
			if (isset($row['termsofuse_id']) & $row['symbollink'] != "") {
				$symbolLink = $row['symbollink'];
			} else {
				$symbolLink = $this->protocol . "://" . $this->hostName . "/mapbender/img/icn_warn.png";
			}
			$hasConstraints = true;
		} else {
			$symbolLink = $this->protocol . "://" . $this->hostName . "/mapbender/img/icn_ok.png";
			$hasConstraints = false;
		}
		if (isset($row['termsofuse_id'])) {
			$termsOfUseId = $row['name']; //export license identifier to allow easier exchange with opendata portals
		} else {
			$termsOfUseId = false;
		}
		return array($hasConstraints, $symbolLink, $termsOfUseId);
	}

	//function to delete one of the comma separated values from a HTTP-GET request
	//
	private function delFromQuery($paramName, $queryString, $string, $queryArray, $queryList)
	{
		//check if if count searchArray = 1
		if (count($queryArray) == 1) {
			//remove request parameter from url by regexpr or replace
			$str2search = $paramName . "=" . $queryList;
			if ($paramName == "searchText") {
				$str2exchange = "searchText=*&";
			} else {
				$str2exchange = "";
			}
			$queryStringNew = str_replace($str2search, $str2exchange, $queryString);
			$queryStringNew = str_replace("&&", "&", $queryStringNew);
		} else {
			//there are more than one filter - reduce the filter  
			$objectList = "";
			for ($i = 0; $i < count($queryArray); $i++) {
				if ($queryArray[$i] != $string) {
					$objectList .= $queryArray[$i] . ",";
				}
			}
			//remove last comma
			$objectList = rtrim($objectList, ",");
			$str2search = $paramName . "=" . $queryList;
			$str2exchange = $paramName . "=" . $objectList;
			$queryStringNew = str_replace($str2search, $str2exchange, $queryString);
		}
		return $queryStringNew;
	}

	private function getValueForParam($paramName, $queryString)
	{
		parse_str($queryString, $allQueries);
		if (isset($allQueries[$paramName]) & $allQueries[$paramName] != '') {
			return $allQueries[$paramName];
		} else {
			return false;
		}
	}

	// function to add a new variable or complete parameter to a GET parameter query url 
	private function addToQuery($paramName, $queryString, $string, $queryList)
	{
		//test if string was part of query before, if so, don't extent the query
		//TODO: the strings come from json and so they are urlencoded! maybe we have to decode them to find the commata
		$queryListComma = urldecode($queryList);
		$queryListC = "," . $queryListComma . ",";
		$pattern = ',' . $string . ',';
		if (!preg_match($pattern, $queryListC)) {
			$queryListNew = $queryListC . $string;
			$queryListNew = ltrim($queryListNew, ',');
			$queryStringNew = str_replace($paramName . '=' . $queryList, $paramName . '=' . $queryListNew, $queryString);
			return $queryStringNew;
		} else {
			return $queryString;
		}
	}

	//for debugging purposes only
	private function logit($text)
	{
		if ($h = fopen("/tmp/class_metadata.log", "a")) {
			$content = $text . chr(13) . chr(10);
			if (!fwrite($h, $content)) {
				#exit;
			}
			fclose($h);
		}
	}

	// function to delete one GET parameter totally from a query url 
	private function delTotalFromQuery($paramName, $queryString)
	{
		$queryString = "&" . $queryString;
		//only delete totally if not searchText itself
		if ($paramName == "searchText") {
			$str2exchange = "searchText=*&";
		} else {
			$str2exchange = "";
		}
		$queryStringNew = preg_replace('/\b' . $paramName . '\=[^&]+&?/', $str2exchange, $queryString);
		$queryStringNew = ltrim($queryStringNew, '&');
		$queryStringNew = rtrim($queryStringNew, '&');
		return $queryStringNew;
	}
}
