<?php
# $Id: class_wmc.php 10369 2019-12-13 12:41:15Z armin11 $
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

require_once(dirname(__FILE__) . "/../classes/class_wms.php");
require_once(dirname(__FILE__) . "/../classes/class_wfs_conf.php");
require_once(dirname(__FILE__) . "/../classes/class_layer_monitor.php");
require_once(dirname(__FILE__) . "/../classes/class_point.php");
require_once(dirname(__FILE__) . "/../classes/class_bbox.php");
require_once(dirname(__FILE__) . "/../classes/class_json.php");
require_once(dirname(__FILE__) . "/../classes/class_map.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
require_once(dirname(__FILE__) . "/../classes/class_wmcToXml.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
require_once(dirname(__FILE__) . "/../classes/class_Uuid.php");

/**
 * Implementation of a Web Map Context Document, WMC 1.1.0
 *
 * Use cases:
 *
 * Instantiation (1) create a WMC object from a WMC XML document
 * 		$myWmc = new wmc();
 * 		$myWmc->createFromXml($xml);
 *
 *    If you want to create a WMC object from a WMC in the database
 *    	$xml = wmc::getDocument($wmcId);
 * 		$myWmc = new wmc();
 * 		$myWmc->createFromXml($xml);
 *
 *
 * Instantiation (2) create a WMC from the client side
 * 		$myWmc = new wmc();
 * 		$myWmc->createFromJs($mapObject, $generalTitle, $extensionData);
 *
 * 	  	(creates a WMC from the JS data and then creates an object from that WMC)
 *
 * Output (1) (do Instantiation first) Load a WMC into client
 * 		This will return an array of JS statements
 *
 * 		$myWmc->toJavaScript();
 *
 * Output (2) (do Instantiation first) Merge with another WMC, then load
 *
 * 		$myWmc->merge($anotherWmcXml);
 * 		$myWmc->toJavaScript();
 *
 */
class wmc {
/**
 * Representing the main map in a map application
 * @var Map
 */
	var $mainMap;

	/**
	 * Representing an (optional) overview map in a map application
	 * @var Map
	 */
	var $overviewMap;

	/**
	 * @var Array
	 */
	var $generalExtensionArray = array();

	/**
	 * The XML representation of this WMC.
	 * @var String
	 */
	var $xml;

	// constants
	var $saveWmcAsFile = false;
	var $extensionNamespace = "mapbender";
	var $extensionNamespaceUrl = "http://www.mapbender.org/context";

	// set in constructor
	var $wmc_id;
	var $userId;
	var $timestamp;
	var $public;
        var $local_data_public = 0;
        var $has_local_data = 0;
        var $local_data_size = '0';
	var $uuid; 

	// set during parsing
	var $wmc_version;
	var $wmc_name;
	var $wmc_title;
	var $wmc_abstract;
	var $wmc_srs;
	var $wmc_extent;
	var $wmc_keyword = array();
	var $wmc_contactposition;
	var $wmc_contactvoicetelephone;
	var $wmc_contactemail;
	var $wmc_contactfacsimiletelephone;
	var $wmc_contactperson;
	var $wmc_contactorganization;
	var $wmc_contactaddresstype;
	var $wmc_contactaddress;
	var $wmc_contactcity;
	var $wmc_contactstateorprovince;
	var $wmc_contactpostcode;
	var $wmc_contactcountry;
	var $wmc_logourl;
	var $wmc_logourl_format;
	var $wmc_logourl_type;
	var $wmc_logourl_width;
	var $wmc_logourl_height;
	var $wmc_descriptionurl;
	var $wmc_descriptionurl_format;
	var $wmc_descriptionurl_type;

	var $inspireCats;
	var $isoTopicCats;
	var $customCats;

	public function __construct () {
		$this->userId = Mapbender::session()->get("mb_user_id");
		$this->timestamp = time();
		$this->uuid = new Uuid();

	}

	// ---------------------------------------------------------------------------
	// INSTANTIATION
	// ---------------------------------------------------------------------------

	/**
	 * Parses the XML string and instantiates the WMC object.
	 *
	 * @param $xml String
	 */
	public function createFromXml ($xml) {
		return $this->createObjFromWMC_xml($xml);
	}

	/**
	 * Loads a WMC from the database.
	 *
	 * @param integer $wmc_id the ID of the WMC document in the database table "mb_user_wmc"
	 */
	function createFromDb($wmcId) {
		$doc = wmc::getDocument($wmcId);
		if ($doc === false) {
			return false;
		}
		$this->createObjFromWMC_xml($doc);
		$sql = "SELECT * from (SELECT wmc_timestamp, wmc_title, wmc_public, srs, minx, miny, maxx, maxy, wmc_has_local_data, wmc_local_data_size, wmc_local_data_public, uuid, fkey_user_id " .
			"FROM mb_user_wmc WHERE wmc_serial_id = $1 AND (fkey_user_id = $2 OR wmc_public = 1)) as wmc_data INNER JOIN mb_user ON wmc_data.fkey_user_id = mb_user.mb_user_id";
		$v = array($wmcId, Mapbender::session()->get("mb_user_id"));
		$t = array("i", "i");

		$res = db_prep_query($sql,$v,$t);
		if(db_error()) { return false; }
		if($row = db_fetch_assoc($res)) {
			$this->wmc_id = $wmcId;
			$this->timestamp = $row['wmc_timestamp'];
			$this->title = $row['wmc_title'];
			$this->public = $row['wmc_public'];
			$this->wmc_srs = $row['srs'];
			$this->wmc_extent->minx = $row['minx'];
			$this->wmc_extent->miny = $row['miny'];
			$this->wmc_extent->maxx = $row['maxx'];
			$this->wmc_extent->maxy = $row['maxy'];
            $this->local_data_public = $row['wmc_local_data_public'];
            $this->local_data_size = $row['wmc_local_data_size'];
            $this->has_local_data = $row['wmc_has_local_data'];
	    	$this->uuid = $row['uuid'];
			$this->wmc_contactperson = $row['mb_user_name'];
			$this->wmc_contactemail = $row['mb_user_email'];
			return true;
		}
		return false;
	}

	public function createFromApplication ($appId) {
	// get the map objects "overview" and "mapframe1"
		$this->mainMap = map::selectMainMapByApplication($appId);
		
		//$e = new mb_exception("classes/class_wmc.php: mainMap from application: ".implode(",",$this->mainMap->toJavaScript()));
		$this->overviewMap = map::selectOverviewMapByApplication($appId);

		// a  WFS is basically just a vectorlayer, and a WFSconf is just a configured WFS,
		// so it makes sense to attach the WFSCONFIDstring to to the mapobject
		// this clearly needs a better solution though...
		try{
			$ev = new ElementVar($appId,"mapframe1","wfsConfIdString");
			$this->generalExtensionArray['WFSCONFIDSTRING'] = $ev->value;
		}catch(Exception $E){
			// ... exceptions are a terrible way to do this, but I am not going to rewrite the ElementVar class
			$this->generalExtensionArray['WFSCONFIDSTRING'] = "";
		}
		$this->createXml();
		$this->saveAsFile();
	}

	/**
	 * Creates a WMC object from a JS map object {@see map_obj.js}
	 *
	 * @param object $mapObject a map object
	 * @param integer $user_id the ID of the current user
	 * @param string $generalTitle the desired title of the WMC
	 * @param object $extensionData data exclusive to Mapbender, which will be
	 * 								mapped into the extension part of the WMC
	 */
	public function createFromJs($mapObject, $generalTitle, $extensionData, $id=null) {
		if (count($mapObject) > 2) {
			$e = new mb_exception("Save WMC only works for two concurrent map frames (overview plus main) at the moment.");
		}
		// set extension data
		$this->generalExtensionArray = $extensionData;

		if ($id) {
		//set id
			$this->wmc_id = $id;
		}

		// set title
		$this->wmc_title = $generalTitle;
        	if($mapObject[0]->kmls) {
            		$this->has_local_data = true;
            		$this->local_data_size = strlen(json_encode($mapObject[0]->kmls));
            		if(defined('MAX_WMC_LOCAL_DATA_SIZE')) {
                		if($this->local_data_size > MAX_WMC_LOCAL_DATA_SIZE) {
                    			$this->has_local_data = false;
                    			$this->local_data_size = '0';
                    			unset($mapObject[0]->kmls);
                    			unset($mapObject[0]->kmlOrder);
                		}
            		}
        	}

		// create the map objects
		for ($i = 0; $i < count($mapObject); $i++) {
			$currentMap = new Map();
			$currentMap->createFromJs($mapObject[$i]);

			if (isset($mapObject[$i]->isOverview)) {
				$this->overviewMap = $currentMap;
			}
			else {
				$this->mainMap = $currentMap;
			}
		}

		// create XML
		$this->createXml();

		$this->saveAsFile();
		return true;
	}

	// ---------------------------------------------------------------------------
	// DATABASE FUNCTIONS
	// ---------------------------------------------------------------------------
	public function getPublicWmcIds () {
		$sql = "SELECT wmc_serial_id FROM mb_user_wmc ";
		$sql .= "WHERE wmc_public = 1 GROUP BY wmc_serial_id";
		$res_wmc = db_query($sql);

		$wmcArray = array();
		while($row = db_fetch_array($res_wmc)) {
			array_push($wmcArray, $row["wmc_serial_id"]);
		}
		return $wmcArray;
	}

	public function getAccessibleWmcs ($user) {
		$wmcArray = array();

		// get WMC ids
		$wmcOwnerArray = $user->getWmcByOwner();

		$publicWmcIdArray = $this->getPublicWmcIds();

		return array_keys( array_flip(array_merge($wmcOwnerArray, $publicWmcIdArray)));
	}

	public function selectByUser ($user, $showPublic=0) {
		$wmcArray = array();

		// get WMC ids
		$wmcOwnerArray = $user->getWmcByOwner();
		if ($showPublic==1) {
			$publicWmcIdArray = self::getPublicWmcIds();
			$wmcIdArray = array_keys( array_flip(array_merge($wmcOwnerArray, $publicWmcIdArray)));
		} else {
			$publicWmcIdArray = array();
			$wmcIdArray=$wmcOwnerArray;
		}
		// get WMC data
		$v = array();
		$t = array();
		$wmcIdList = "";

		for ($i = 0; $i < count($wmcIdArray); $i++) {
			if ($i > 0) {
				$wmcIdList .= ",";
			}
			$wmcIdList .= "$".($i+1);
			array_push($v, $wmcIdArray[$i]);
			array_push($t, 's');
		}

		if ($wmcIdList !== "") {
			$sql = "SELECT DISTINCT wmc_serial_id, wmc_title, wmc_timestamp, wmc_timestamp_create, wmc_public, abstract FROM mb_user_wmc ";
			$sql .= "WHERE wmc_serial_id IN (" . $wmcIdList . ") ";
			$sql .=	"ORDER BY wmc_timestamp DESC";

			$res = db_prep_query($sql, $v, $t);
			while($row = db_fetch_assoc($res)) {
				$currentResult = array();
				$currentResult["id"] = $row["wmc_serial_id"];
				$currentResult["abstract"] = $row["abstract"];
				$currentResult["title"] = administration::convertIncomingString($row["wmc_title"]);
				$currentResult["timestamp"] = date("M d Y H:i:s", $row["wmc_timestamp"]);
				$currentResult["timestamp_create"] = date("M d Y H:i:s", $row["wmc_timestamp_create"]);
				$currentResult["isPublic"] = $row["wmc_public"] == 1? true: false;
				$currentResult["disabled"] = ((
					in_array($currentResult["id"], $publicWmcIdArray) &&
					!in_array($currentResult["id"], $wmcOwnerArray)) || $user->isPublic()) ?
					true : false;

				// get categories
				$currentResult["categories"] = $this->getCategoriesById($currentResult["id"], $user);
				$currentResult["keywords"] = $this->getKeywordsById($currentResult["id"], $user);
				array_push($wmcArray, $currentResult);
			}
		}
		return $wmcArray;
	}

	private function getKeywordsById ($id, $user) {
		$wmcArray = $this->getAccessibleWmcs($user);
		if (!in_array($id, $wmcArray)) {
			return array();
		}

		$keywordArray = array();

		$sql = "SELECT DISTINCT k.keyword FROM keyword AS k, wmc_keyword AS w " .
			"WHERE w.fkey_keyword_id = k.keyword_id AND w.fkey_wmc_serial_id = $1";
		$v = array($id);
		$t = array("s");
		$res = db_prep_query($sql, $v, $t);
		while ($row = db_fetch_array($res)) {
			$keywordArray[]= $row["keyword"];
		}

		return $keywordArray;
	}

	private function getCategoriesById ($id, $user) {
		$wmcArray = $this->getAccessibleWmcs($user);
		if (!in_array($id, $wmcArray)) {
			return array();
		}

		$categoryArray = array();

		$sql = "SELECT DISTINCT t.md_topic_category_id FROM " .
			"md_topic_category AS t, wmc_md_topic_category AS w " .
			"WHERE w.fkey_md_topic_category_id = t.md_topic_category_id " .
			"AND w.fkey_wmc_serial_id = $1";
		$v = array($id);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		while ($row = db_fetch_array($res)) {
			$categoryArray[]= $row["md_topic_category_id"];
		}
		return $categoryArray;
	}

	private function compareWms ($a, $b) {
		if ($a["id"] === $b["id"]) return 0;
		return -1;
	}
	public function getAllWms () {
		$wmsArray = $this->mainMap->getWmsArray();
		$resultObj = array();
		$usedIds = array();
		for ($i = 0; $i < count($wmsArray); $i++) {
			if (in_array($wmsArray[$i]->wms_id, $usedIds)) {
				continue;
			}
			$resultObj[]= array(
				"title" => $wmsArray[$i]->wms_title,
				"id" => is_null($wmsArray[$i]->wms_id) ? null : intval($wmsArray[$i]->wms_id),
				"index" => $i
			);
			$usedIds[]= $wmsArray[$i]->wms_id;
		}
		return $resultObj;
	}

	public function getWmsWithoutId () {
		$wmsArray = $this->getAllWms();
		$resultObj = array();

		for ($i = 0; $i < count($wmsArray); $i++) {
			if (is_numeric($wmsArray[$i]["id"]) && $wmsArray[$i]["id"] !== 0) {
				continue;
			}
			$resultObj[]= array(
				"title" => $wmsArray[$i]["title"],
				"id" => $wmsArray[$i]["id"],
				"index" => $i
			);
		}
		return $resultObj;
	}

	public function getWmsWithId () {
		return array_values(array_udiff(
			$this->getAllWms(),
			$this->getWmsWithoutId(),
			array("wmc", "compareWms")
		));
	}

	public function getValidWms () {
		$inv = $this->getInvalidWms();
		$withId = $this->getWmsWithId();
		return array_values(array_udiff(
			$withId,
			$inv,
			array("wmc", "compareWms")
		));
	}

	public function getInvalidWms () {
		$resultObj = array();
		$wmsArray = $this->getWmsWithId();
		//changes to allow only unique entries for registrated wms ids
		$wmsIdArray = array();
		foreach ($wmsArray as $wms) {
			$wmsIdArray[] = $wms['id'];
		}
		$wmsIdString = rtrim(',',$wmsIdString);
		if (count($wmsArray) > 0) {
			$sql = "SELECT COUNT(wms_id), wms_id FROM wms WHERE wms_id IN (".implode(',', $wmsIdArray).") GROUP BY wms_id";
			$res = db_query($sql);
			$notExistingLookupArray = array();
			while($row = db_fetch_assoc($res)) {
				$notExistingLookupArray[$row['wms_id']] = intval($row['count']);
			}
		}
		for ($i = 0; $i < count($wmsArray); $i++) {
			if ($notExistingLookupArray[$wmsArray[$i]["id"]] === 0) {
				$resultObj[]= array(
					"title" => $wmsArray[$i]["title"],
					"id" => intval($wmsArray[$i]["id"]),
					"index" => $wmsArray[$i]["index"]
				);
			}
		}
		return $resultObj;
	}

	public function getWmsWithPermission ($user) {
		$wmsArray = $this->getValidWms();
		$resultObj = array();

		for ($i = 0; $i < count($wmsArray); $i++) {
			$currentWmsId = intval($wmsArray[$i]["id"]);

			if ($user->isWmsAccessible($currentWmsId)) {
				$resultObj[]= array(
					"title" => $wmsArray[$i]["title"],
					"id" => intval($currentWmsId),
					"index" => $wmsArray[$i]["index"]
				);
			}
		}
		return $resultObj;
	}

	public function getWmsWithoutPermission ($user) {
		return array_values(array_udiff(
		$this->getValidWms(),
		$this->getWmsWithPermission($user),
		array("wmc", "compareWms")
		));
	}

	public function getAvailableWms ($user) {
		return array_values(array_udiff(
		$this->getWmsWithPermission($user),
		$this->getUnavailableWms(),
		array("wmc", "compareWms")
		));
	}

	public function getUnavailableWms ($user) {
		$wmsArray = $this->getWmsWithPermission($user);
		$resultObj = array();
		for ($i = 0; $i < count($wmsArray); $i++) {
			$currentWmsId = $wmsArray[$i]["id"];
			$sql = "SELECT last_status FROM mb_wms_availability WHERE fkey_wms_id = $1";
			$v = array($currentWmsId);
			$t = array("i");
			$res = db_prep_query($sql, $v, $t);
			$statusRow = db_fetch_row($res);
			$status = intval($statusRow[0]);
			if (isset($status) && $status == -1) {
				$resultObj[]= array(
					"title" => $wmsArray[$i]["title"],
					"id" => $currentWmsId,
					"index" => $wmsArray[$i]["index"]
				);
			}
		}
		return $resultObj;
	}
	
	/*
	 * Get a list of all wms, that have the last monitoring status of -1 - don't use permissions for this !
	 */
	public function getAllUnavailableWms () {
	    $validWmsArray = $this->getValidWms();
	    $resultObj = array();
	    if (count($validWmsArray) > 0) {
		$sql = "select wms_id, wms_title, availability from wms inner join mb_wms_availability on wms.wms_id = mb_wms_availability.fkey_wms_id where wms_id in (";
		$wmsIdArray = []; 
		foreach ($validWmsArray as $validWms)  {
		    //$e = new mb_exception("javascripts/map.php: validWms".json_decode(json_encode($validWms))->id);
		    $wmsIdArray[] = json_decode(json_encode($validWms))->id;
		}
		$sql .= implode($wmsIdArray, ',');
		$sql .= ") and last_status = '-1'";   
	    } else {
	        $sql = "select wms_id, wms_title, availability from wms inner join mb_wms_availability on wms.wms_id = mb_wms_availability.fkey_wms_id where last_status = '-1'";
	    }
	    $res = db_query($sql);
            while($row = db_fetch_assoc($res)) {
                $resultObj[]= array(
                    "title" => $row["wms_title"],
                    "id" => $row["wms_id"],
                    "availability" => row["availability"],
                    "index" => 0
                );  
            }
            return $resultObj;
        }
	
	/*
	* function to update the information about wms in a mapbender wmc object and stores it in the database
	* actually layer names and getmap urls are updated by the given mapbender layer id, also the dataurl entries are created or
	* if a layer has related metadata for which download options are available
	* 
	* @return WMC as XML or false.
	*/
	public function updateUrlsFromDb() {
		$startTime = microtime();
		//declare xpath to pull all layer with given ids from stored wmc docs
		$query_mbLayerId = "/wmc:ViewContext/wmc:LayerList/wmc:Layer/wmc:Extension/mapbender:layer_id";
		//$query_mbLayerLegend = "/wmc:ViewContext/wmc:LayerList/wmc:Layer/wmc:StyleList/";
		/*
			<StyleList>
				<Style current="1">
					<Name>default</Name>
					<Title>1</Title>
					<LegendURL width="" height="" format="">
						<OnlineResource xlink:type="simple" xlink:href="https://gislksim.service24.rlp.de/ArcGIS/services/erneuerbare_energien/BuergerGIS_Erneuerbare_Energien/MapServer/WMSServer?request=GetLegendGraphic%26version=1.1.1%26format=image/png%26layer=1"/>
					</LegendURL>
				</Style>
			</StyleList>
		*/
		//parse xml with php simple xml and handle errors
		libxml_use_internal_errors(true);
		try {
			$WMCDoc = simplexml_load_string($this->toXml());
			if ($WMCDoc === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_wmc:".$error->message);
    				}
				throw new Exception("class_wmc:".'Cannot parse WMC XML!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_wmc:".$e->getMessage());
			return false;
		}
		//register relevant namespaces
		$WMCDoc->registerXPathNamespace("wmc","http://www.opengis.net/context");
		$WMCDoc->registerXPathNamespace("mapbender","http://www.mapbender.org/context");
		$WMCDoc->registerXPathNamespace("xlink","http://www.w3.org/1999/xlink");
		//pull out List of layer objects
		$layerIdList = $WMCDoc->xpath($query_mbLayerId);
		//for styles / legend
		//$layerLegendUrlList = $WMCDoc->xpath($query_mbLayerLegend);
		$e = new mb_notice(count($layerIdList));
		//Select current layer and wms information with one SQL select query!
		$v = array();
		$t = array();
		$layerIds = array();
		$sql = "SELECT layer_id, layer_title, f_get_layer_featuretype_coupling(array[ layer_id ], TRUE) as featuretypecoupling, f_get_download_options_for_layer(layer_id) AS downloadoptions, layer_name, fkey_wms_id, wms_timestamp, wms_getmap, wms_getlegendurl, wms_owsproxy FROM layer, wms WHERE layer.fkey_wms_id = wms.wms_id and layer_id in (";
		$i = 0;
		//generate csv list of layer_ids
		$layerList = "";
		foreach($layerIdList as $layerIdObject) {
			//use only integer layer ids
			if (is_int((integer)$layerIdObject) && $layerIdObject != null && $layerIdObject != "") {
				if($i > 0){$layerList .= ",";}
				$layerList .= "$".($i + 1);
				array_push($v,$layerIdObject);
				array_push($t,'i');
				$i++;
			}
		}
		$i = 0;
		$sql = $sql.$layerList;
		$sql .= ")";
		$res = db_prep_query($sql,$v,$t);
		$e = new mb_notice("class_wmc: sql to pull current wms and layer information from database: ".$sql);
		//pull all styles of layer with ids from mapbender registry
		$sql = "SELECT * FROM layer_style WHERE fkey_layer_id IN (";
		$sql .= $layerList;
		$sql .= ")";
		$resStyle = db_prep_query($sql,$v,$t);
		//get result as array
		$style = array();
		while($row = db_fetch_array($resStyle)) {
			$style[$row["fkey_layer_id"]][$row["name"]] [$row["legendurlformat"]] = $row["legendurl"];
			//$e = new mb_notice($row["fkey_layer_id"] . " : " . $row["name"]. " - legendurl: ".$row["legendurl"]." - format: ".$row["legendurlformat"]);
		}
		//pull all information about dimension - first only this information, that makes sense
		$sql = "SELECT * FROM layer_dimension WHERE fkey_layer_id IN (";
		$sql .= $layerList;
		$sql .= ") AND (name = 'time' AND units = 'ISO8601')";
		//$sql .= ") AND (name = 'time' or name = 'elevation')";
		//following attributes should be exchanged for time:
		$attributeNames = array('unitSymbol', 'default', 'multipleValues', 'nearestValue', 'current', 'extent');
		$resAttributes = db_prep_query($sql,$v,$t);
		//get result as array
		$dimension = array();
		while($row = db_fetch_array($resAttributes)) {
			foreach ($attributeNames as $attributeName) {
				$dimension[$row["fkey_layer_id"]][$row["name"]] [$attributeName] = $row[strtolower($attributeName)];
				//$e = new mb_exception($row["fkey_layer_id"] . " : " . $row["name"]. " - attributeName: ".$attributeName." - value from db: ".$row[strtolower($attributeName)]);	
			}
		}
		//for each found layer
		while($row = db_fetch_array($res)){
			$wmsId = $row["fkey_wms_id"];
			$layerId = $row["layer_id"];
			$layerTitle = addslashes($row["layer_title"]);
			$e = new mb_notice("class_wmc.php - updateUrlsInDb - following layer will be processed: ".$layerId);
			$layerName = $row["layer_name"];
			//xpath to pull a special wmc layer object from simple xml object
			$queryPath = "/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']";
			//Some help for get and set attributes: http://stackoverflow.com/questions/2956601/change-xml-node-element-value-in-php-and-save-file
			//Condition for secured and unsecured mapbender wms
			if (isset($row["wms_owsproxy"]) && $row["wms_owsproxy"] != '') {
				//set relevant wms urls to owsproxy urls
				$wmsowsproxy = $row["wms_owsproxy"];
				$owsproxyurl = OWSPROXY."/".session_id()."/".$wmsowsproxy."?";
				$wmsGetMapUrl = $owsproxyurl;
				$wmsGetLegendUrl = $owsproxyurl;
			} else {
				//service is not secured - exchange urls with the latest ones from database
				$wmsGetMapUrl = $row["wms_getmap"];
				$wmsGetLegendUrl = $row["wms_getlegendurl"];
				//Exchange the given styles for each layer
				//TODO: Exchange the style urls on a right way! Use the layer_style table for this!
			}
			//Loop over found layerObject - normally only one single layer is there. Alter the information in the simple xml object
			foreach($WMCDoc->xpath($queryPath) as $layer ) {
				$e = new mb_notice("class_wmc: exchange old layer name : ".$layer->Name." with new layer name: ".$layerName);
				$e = new mb_notice("class_wmc: exchange old layer name : ".$layer->Name." with new layer name: ".$layerName);
  				$layer->Name = $layerName;
				$e = new mb_notice("class_wmc: exchange old getmap url : ".$layer->Server->OnlineResource->attributes('xlink', true)->href." with new getmap url: ".$wmsGetMapUrl);
 				$layer->Server->OnlineResource->attributes('xlink', true)->href = $wmsGetMapUrl;
				//title
				$layer->Title = $layerTitle;
				//check if layer has available download options
				if (defined("SHOW_INSPIRE_DOWNLOAD_IN_TREE") && SHOW_INSPIRE_DOWNLOAD_IN_TREE == true && $row["downloadoptions"] != ""){
					if (defined("MAPBENDER_PATH") && MAPBENDER_PATH != "") {
						$downloadOptionsUrl = MAPBENDER_PATH."/php/mod_getDownloadOptions.php?outputFormat=html&id=".str_replace('{','',str_replace('}','',str_replace('}{',',',$row["downloadoptions"])));
					} else {
						//relative path - it's not such a good choice ;-)
						$downloadOptionsUrl = "../php/mod_getDownloadOptions.php?outputFormat=html&id=".str_replace('{','',str_replace('}','',str_replace('}{',',',$row["downloadoptions"])));
					}
					//check if layer has a DataURL element
					if (count($layer->DataURL) > 0) {
						//found DataURL element
						$e = new mb_notice("class_wmc.php - updateUrlsFromDb - found existing DataURL Element!");
						//check if OnlineResource is already filled
						if ($layer->DataURL->OnlineResource) {
							$e = new mb_notice("class_wmc.php - updateUrlsInDb - found existing DataURL->OnlineResource Element!");
							$e = new mb_notice("class_wmc.php - updateUrlsInDb - update existing link to DataURL->OnlineResource!");
							$layer->DataURL->OnlineResource->attributes('xlink', true)->href = $downloadOptionsUrl;
						}
					} else {
						//create dataurl and fill it - maybe with dom?
						$e = new mb_notice("class_wmc.php - updateUrlsInDb - create new DataURL Element!");
						//$layerXML = $layer->asXML();
						//$dom_layer = dom_import_string($layerXML);

						/*$dom_layer = dom_import_simplexml($layer);
						$dom = new DOMDocument('1.0');
						$dom_layer = $dom->importNode($dom_layer, true);
						$dom_layer = $dom->appendChild($dom_sxe);*/
						//TODO: help http://stackoverflow.com/questions/3361036/php-simplexml-insert-node-at-certain-position
						//cause its a f... sequence in wmc standard - DataURL is after Abstract but Abstract is optional .... if <Abstract> exists - item 5 on the other hand item 4
						//
						/*
						<xs:complexType name="LayerType">
							<xs:sequence>
								<xs:element name="Server" type="context:ServerType" minOccurs="1" maxOccurs="1"/>
								<xs:element name="Name" type="xs:string" minOccurs="1" maxOccurs="1"/>
								<xs:element name="Title" type="xs:string" minOccurs="1" maxOccurs="1"/>
								<xs:element name="Abstract" type="xs:string" minOccurs="0" maxOccurs="1"/>
								<xs:element name="DataURL" type="context:URLType" minOccurs="0" maxOccurs="1"/>
								<xs:element name="MetadataURL" type="context:URLType" minOccurs="0" maxOccurs="1"/>
								<xs:element name="SRS" type="xs:string" minOccurs="0" maxOccurs="1"/>
								<xs:element name="DimensionList" type="context:DimensionListType” minOccurs="0" maxOccurs="1"/>
								<xs:element name="FormatList" type="context:FormatListType" minOccurs="0" maxOccurs="1"/>
								<xs:element name="StyleList" type="context:StyleListType" minOccurs="0" maxOccurs="1"/>
								<xs:element ref="sld:MinScaleDenominator" minOccurs="0" maxOccurs="1"/>
								<xs:element ref="sld:MaxScaleDenominator" minOccurs="0" maxOccurs="1"/>
								<xs:element name="Extension" type="context:ExtensionType" minOccurs="0" maxOccurs="1"/>
							</xs:sequence>

						*/
						//check if Abstract exists - if it does DataURL came after Abstract, if not DataURL will be after Title Element
						if (count($layer->Abstract) > 0) {
							$e = new mb_notice("class_wmc.php - updateUrlsInDb - Abstract found!");
							//put it after Abstract Element
							//New element to be inserted
							$insert = new SimpleXMLElement('<DataURL></DataURL>');
							//<OnlineResource xmlns:xlink="http://www.w3.org/1999/xlink" xlink:type="simple" xlink:href="'.$downloadOptionsUrl.'"/>
							// Get the Abstract element for this layer
							$target = current($WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']/wmc:Abstract"));
							// Insert the new element after the last nodeA
							$this->simplexml_insert_after($insert, $target);
							$layer->DataURL->addChild('OnlineResource');
							if (!is_null($layer->DataURL->OnlineResource)) {
								$layer->DataURL->OnlineResource->addAttribute('xlink:type', 'simple','http://www.w3.org/1999/xlink');
								$layer->DataURL->OnlineResource->addAttribute('xlink:href', $downloadOptionsUrl,'http://www.w3.org/1999/xlink');
							}
						} else {
							//put it after Title Element
							$insert = new SimpleXMLElement("<DataURL></DataURL>");
							//<OnlineResource xlink:type='simple' xlink:href='".$downloadOptionsUrl."'/>
							// Get the Abstract element for this layer
							$target = current($WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']/wmc:Title"));
							// Insert the new element after the last nodeA
							$this->simplexml_insert_after($insert, $target);
							$layer->DataURL->addChild('OnlineResource');
							if (!is_null($layer->DataURL->OnlineResource)) {
								$layer->DataURL->OnlineResource->addAttribute('xlink:type', 'simple','http://www.w3.org/1999/xlink');
								$layer->DataURL->OnlineResource->addAttribute('xlink:href', $downloadOptionsUrl,'http://www.w3.org/1999/xlink');
							}
						}
						$e = new mb_notice("class_wmc.php - updateUrlsInDb new DataURL XML : ".$layer->asXML());
					}

				}
				if (defined("SHOW_INSPIRE_DOWNLOAD_IN_TREE") && SHOW_INSPIRE_DOWNLOAD_IN_TREE == true && $row["downloadoptions"] == ""){
					$e = new mb_notice("class_wmc.php - updateUrlsInDb: empty INSPIRE download option found!");
					//delete an given DataURL element, cause INSPIRE options should be used
					if (count($layer->DataURL) > 0) {
						$e = new mb_notice("class_wmc.php - updateUrlsInDb: unset DataURL element cause there is no INSPIRE download option!");
						unset($layer->DataURL);
					}
				}
				//show attribute table if layer_featuretype_coupling exists and this is defined in mapbender.conf
				if (defined("SHOW_COUPLED_FEATURETYPES_IN_TREE") && SHOW_COUPLED_FEATURETYPES_IN_TREE == true) {
					//put it in layer extension layer_featuretype_coupling
					if ($row['featuretypecoupling'] !== "[]") {
//$e = new mb_exception("class_wmc.php: updateUrlsFromDb: featuretypecoupling:".$row['featuretypecoupling']." - layer: ".(integer)$layerId);
						//$resultOfXpath = reset($WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']/wmc:DimensionList/wmc:Dimension[@name=\"time\" and @units=\"ISO8601\"]/@".$attributeName));
						//$resultOfXpath->{0} = $dimension[(integer)$layerId]["time"][$attributeName];
					}
				}
				//Help for problem with xlink:href attributes: http://php.net/manual/de/class.simplexmlelement.php!!!!!
				//exchange legend urls
				$layerDoc = simplexml_load_string($layer->saveXML());
				if (isset($row["wms_owsproxy"]) && $row["wms_owsproxy"] != '' && !is_null($row["wms_owsproxy"])) {
					if($layer->StyleList->Style->LegendURL->OnlineResource){
						$arURL = parse_url($layer->StyleList->Style->LegendURL->OnlineResource->attributes('xlink', true)->href);
						$query = $arURL["query"];
						$url = $wmsGetLegendUrl . $query;
						$layer->StyleList->Style->LegendURL->OnlineResource->attributes('xlink', true)->href = $url;
					}
				} else {
					foreach($layerDoc->xpath('/Layer/StyleList/Style[@current="1"]') as $styleObject) {
						//only one current style is possible!
						//if old legendurl was given, exchange it with new from database
						if($styleObject->LegendURL->OnlineResource){
							//check mimetype
							if (isset($styleObject->LegendURL->attributes()->format) && isset($style[(integer)$layerId][(string)$styleObject->Name][(string)$styleObject->LegendURL->attributes()->format])) {
								$mimeType = (string)$styleObject->LegendURL->attributes()->format;

							} else {
								$mimeType = 'image/png';
							}
							$e = new mb_notice("class_wmc: mimetype for legendurl: ".$mimeType);
							if (isset($style[(integer)$layerId][(string)$styleObject->Name][$mimeType]) && $style[(integer)$layerId][(string)$styleObject->Name][$mimeType] != '') {
								$e = new mb_notice("class_wmc: exchange old legendurl url : ".$layer->StyleList->Style->LegendURL->OnlineResource->attributes('xlink', true)->href." with new legendurl: ".$style[(integer)$layerId][(string)$styleObject->Name][$mimeType]);
								$layer->StyleList->Style->LegendURL->OnlineResource->attributes('xlink', true)->href = $style[(integer)$layerId][(string)$styleObject->Name][$mimeType];
							}

						}
					}
				}
				foreach($layerDoc->xpath('/Layer/DimensionList/Dimension[@name="time" and @units="ISO8601"]') as $dimensionObject) {
					foreach ($attributeNames as $attributeName) {
						//$e = new mb_exception("set ".$attributeName." attribute of dimension object to ".$dimension[(integer)$layerId]["time"][$attributeName]);
						$resultOfXpath = reset($WMCDoc->xpath("/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']/wmc:DimensionList/wmc:Dimension[@name=\"time\" and @units=\"ISO8601\"]/@".$attributeName));
						$resultOfXpath->{0} = $dimension[(integer)$layerId]["time"][$attributeName];
						
					}
				}
			}
		}
		$updatedWMC = $WMCDoc->saveXML();
		$e = new mb_notice($updatedWMC);
		if (is_int($this->wmc_id)) {
			$this->update_existing($updatedWMC, $this->wmc_id);
		}
		$endTime = microtime();
		$e = new mb_notice((string)($endTime - $startTime));
		return $updatedWMC;
	}

	public function removeUnaccessableLayers($wmcXml) {
		$currentUser = new User(Mapbender::session()->get("mb_user_id"));
		//declare xpath to pull all layer with given ids from stored wmc docs
		$query_mbLayerId = "/wmc:ViewContext/wmc:LayerList/wmc:Layer/wmc:Extension/mapbender:layer_id";
		libxml_use_internal_errors(true);
		try {
			$WMCDoc = simplexml_load_string($wmcXml);
			if ($WMCDoc === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_wmc:".$error->message);
    				}
				throw new Exception("class_wmc:".'Cannot parse WMC XML!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_wmc:".$e->getMessage());
			return false;
		}
		//register relevant namespaces
		$WMCDoc->registerXPathNamespace("wmc","http://www.opengis.net/context");
		$WMCDoc->registerXPathNamespace("mapbender","http://www.mapbender.org/context");
		$WMCDoc->registerXPathNamespace("xlink","http://www.w3.org/1999/xlink");
		//pull out List of layer objects
		$layerIdList = $WMCDoc->xpath($query_mbLayerId);
		$e = new mb_notice(count($layerIdList));
		//remove layers without permisssion
		$checkLayerPermission = true;
		//check for accessible layers with ids
		if ($checkLayerPermission && gettype($layerIdList) == "array" && count($layerIdList) > 0) {
			$allowedLayerArray = $currentUser->getAccessableLayers($layerIdList);
		}
		if (gettype($allowedLayerArray) == "array" && count($allowedLayerArray) > 0) {
			
		} else {
			$allowedLayerArray = array();
			$allowedLayerArray[] = 0;
		}
		//iterate over all layers with id and remove layer with an id that is not in $allowedLayerArray!
		foreach($layerIdList as $layerId) {
			$queryPath = "/wmc:ViewContext/wmc:LayerList/wmc:Layer[wmc:Extension/mapbender:layer_id='".(integer)$layerId."']";
			foreach($WMCDoc->xpath($queryPath) as $layer) {
				if (in_array($layerId, $allowedLayerArray)) {
					$e = new mb_notice("user is allowed to access layer ".$layerId);
				} else {
					$e = new mb_notice("user is not allowed to access layer ".$layerId);
    					unset($layer[0][0]);
				}
			}
		}
		return $WMCDoc->saveXML();
	}

	/**
	 * Stores this WMC in the database. The WMC has to be instantiated first, see above.
	 *
	 * @return mixed[] an assoc array with attributes "success" (boolean) and "message" (String).
	 */
	public function insert ($overwrite) {
		$result = array();

		if ($this->userId && $this->xml && $this->wmc_title) {
			try {
				$user = new user($this->userId);
			}
			catch (Exception $E) {
				$errMsg = "Error while saving WMC document " . $this->wmc_title . "': Invalid UserId";
				$result["success"] = false;
				$result["message"] = $errMsg;
				$e = new mb_exception("mod_insertWMCIntoDB: " . $errMsg);
				return $result;
			}

			$overwrite  = ($user->isPublic())? false: $overwrite;

			//put keywords into Document
			try {
				$WMCDoc = DOMDocument::loadXML($this->toXml());
			}
			catch (Exception $E) {
				new mb_exception("WMC XML is broken.");
			}

			$xpath = new DOMXPath($WMCDoc);
			$xpath->registerNamespace("wmc","http://www.opengis.net/context");
			$xpath->registerNamespace("mapbender","http://www.mapbender.org/context");
			$xpath->registerNamespace("xlink","http://www.w3.org/1999/xlink");

			$query_KeywordList = "/wmc:ViewContext/wmc:General/wmc:KeywordList";
			$query_general = "/wmc:ViewContext/wmc:General";
			$DocKeywordLists = $xpath->query($query_KeywordList);
			// we just use a single <general> element

			$NewKeywordList = $WMCDoc->createElementNS('http://opengis.net/context', 'wmc:KeywordList');
			$WMCDoc->appendChild($NewKeywordList);

			foreach($this->wmc_keyword as $keyword) {
				$Keyword = $WMCDoc->createElementNS('http://opengis.net/context', 'wmc:Keyword', $keyword);
				$NewKeywordList->appendChild($Keyword);
			}

			$generalList = $xpath->query($query_general);
			$general = $generalList->item(0);

			if($DocKeywordLists->item(0)) {
				$tmpNode = $WMCDoc->importNode($DocKeywordLists->item(0),true);
				$general->replaceChild($NewKeywordList,$tmpNode);
			}
			else {
				$tmpNode = $WMCDoc->importNode($NewKeywordList,true);
				$general->appendChild($tmpNode);
			}
			$this->xml  = $WMCDoc->saveXML();

			db_begin();

			if($overwrite) {

				$findsql = "SELECT fkey_user_id,wmc_title,wmc_timestamp, wmc_serial_id FROM mb_user_wmc WHERE fkey_user_id = $1 AND wmc_serial_id = $2 ORDER BY wmc_timestamp DESC LIMIT 1;";
				$v = array($this->userId, $this->wmc_id);
				$t = array("i","i");

				$res = db_prep_query($findsql,$v,$t);
				if (db_error()) {
					$errMsg = "Error while saving WMC document '" . $this->wmc_title . "': " . db_error();
					$result["success"] = false;
					$result["message"] = $errMsg;
					$e = new mb_exception("mod_insertWMCIntoDB: " . $errMsg);
					return $result;
				}

				if($row = db_fetch_row($res)) {
					$sql = "UPDATE mb_user_wmc SET wmc = $1, wmc_timestamp = $2, abstract = $3, srs = $4, minx = $5, miny = $6,".
						" maxx = $7, maxy = $8, wmc_title = $9, wmc_has_local_data = $13, ".
                        "wmc_local_data_public = $14, wmc_local_data_size = $15 WHERE fkey_user_id = $10 AND wmc_serial_id=$11 AND wmc_timestamp = $12;";
					$v = array($this->xml, time(), $this->wmc_abstract, $this->wmc_srs, $this->wmc_extent->minx, $this->wmc_extent->miny,
                    $this->wmc_extent->maxx, $this->wmc_extent->maxy ,administration::convertOutgoingString($this->wmc_title), $this->userId, $this->wmc_id,$row[2],
                    $this->has_local_data, $this->local_data_public, $this->local_data_size);
					$t = array("s", "s","s","s","i","i","i","i", "s", "i", "i","s", "i", "i", "s");
					$res = db_prep_query($sql, $v, $t);
					// need the database Id
					$wmc_DB_ID = $row[3];
					$delsqlCustomTopic = "DELETE FROM wmc_custom_category WHERE fkey_wmc_serial_id = $1;";
					$delvCustomTopic = array($wmc_DB_ID);
					$deltCustomTopic = array("s");
					db_prep_query($delsqlCustomTopic, $delvCustomTopic,$deltCustomTopic);

					$delsqlInspireTopic = "DELETE FROM wmc_inspire_category WHERE fkey_wmc_serial_id = $1;";
					$delvInspireTopic= array($wmc_DB_ID);
					$deltInspireTopic = array("s");
					db_prep_query($delsqlInspireTopic, $delvInspireTopic,$deltInspireTopic);

					$delsql = "DELETE FROM wmc_md_topic_category WHERE fkey_wmc_serial_id = $1;";
					$delv = array($wmc_DB_ID);
					$delt = array("s");
					db_prep_query($delsql, $delv,$delt);

					$delkwsql = "DELETE FROM wmc_keyword WHERE fkey_wmc_serial_id = $1;";
					$delkwv = array($wmc_DB_ID);
					$delkwt = array("s");
					db_prep_query($delkwsql, $delkwv,$delkwt);
				}
				else {
					$sql = "SELECT max(wmc_serial_id) AS i FROM mb_user_wmc";
					$res = db_query($sql);
					$row = db_fetch_assoc($res);
					$wmc_DB_ID_new = intval($row["i"])+1;

					$sql = "INSERT INTO mb_user_wmc (" .
						"wmc_id, fkey_user_id, wmc, wmc_title, wmc_public, wmc_timestamp, wmc_timestamp_create, " .
						"abstract, srs, minx, miny, maxx, maxy, wmc_serial_id, wmc_has_local_data, wmc_local_data_public, wmc_local_data_size, uuid".
						") VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18);";
					$v = array(time(), $this->userId, $this->xml, administration::convertOutgoingString($this->wmc_title), $this->isPublic()?1:0,time(),time(),
                    $this->wmc_abstract, $this->wmc_srs, $this->wmc_extent->minx,  $this->wmc_extent->miny, $this->wmc_extent->maxx, $this->wmc_extent->maxy, $wmc_DB_ID_new,
                    $this->has_local_data, $this->local_data_public, $this->local_data_size, $this->uuid);
					$t = array("s", "i", "s", "s", "i", "s","s", "s","s","i","i","i", "i", "i", "i", "i", "s", "s");
					$res = db_prep_query($sql, $v, $t);

					//$sql = "SELECT max(wmc_serial_id) AS i FROM mb_user_wmc";
					//$res = db_query($sql);
					//$row = db_fetch_assoc($res);
					//$wmc_DB_ID = intval($row["i"]);
				}
			}
			//if overwrite = false
			else {
				$sql = "SELECT max(wmc_serial_id) AS i FROM mb_user_wmc";
				$res = db_query($sql);
				$row = db_fetch_assoc($res);
				$wmc_DB_ID_new = intval($row["i"])+1;

				$sql = "INSERT INTO mb_user_wmc (" .
					"wmc_id, fkey_user_id, wmc, wmc_title, wmc_public, wmc_timestamp, wmc_timestamp_create, " .
					"abstract, srs, minx, miny, maxx, maxy, wmc_serial_id, wmc_has_local_data, wmc_local_data_public, wmc_local_data_size, uuid".
					") VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18);";
				//$e = new mb_exception($sql);
				$v = array(time(), $this->userId, $this->xml, administration::convertOutgoingString($this->wmc_title), $this->isPublic()?1:0, time(),time(),
                $this->wmc_abstract, $this->wmc_srs, $this->wmc_extent->minx,  $this->wmc_extent->miny, $this->wmc_extent->maxx, $this->wmc_extent->maxy, $wmc_DB_ID_new,
                $this->has_local_data, $this->local_data_public, $this->local_data_size, $this->uuid);
				$t = array("s", "i", "s", "s", "i", "s","s", "s","s","i","i","i", "i", "i", "i", "i", "s", "s");
				$res = db_prep_query($sql, $v, $t);


			}

			if (db_error()) {
				$errMsg = "Error while saving WMC document '" . $this->wmc_title . "': " . db_error();
				$result["success"] = false;
				$result["message"] = $errMsg;
				$e = new mb_exception("mod_insertWMCIntoDB: " . $errMsg);
			}
			else {
			// because the wmc id is created each time a wmc is instantiated $this->wmc_id has nothing to do with the database wmc_id
			// this is some duct tape to fix it :-(
			// see also above where wmc_DB_ID is defined if we need to update
				if(!isset($wmc_DB_ID_new)) { $wmc_DB_ID_new = $this->wmc_id; }

				// update keywords
				foreach($this->wmc_keyword as $keyword) {

				// if a keyword does not yet exist, create it
					$keywordExistsSql = "SELECT keyword FROM keyword WHERE keyword = $1";
					$keywordCreateSql = "INSERT INTO keyword (keyword) VALUES($1);";
					$v = array($keyword);
					$t = array("s");
					$res = db_prep_query($keywordExistsSql,$v,$t);
					if(db_num_rows($res) == 0) {
						$res = db_prep_query($keywordCreateSql,$v,$t);
						if($a = db_error()) {
						}
					}

					$keywordsql = <<<SQL
INSERT INTO wmc_keyword (fkey_keyword_id,fkey_wmc_serial_id)
	SELECT keyword.keyword_id,$1 FROM keyword
	WHERE keyword = $2 AND keyword.keyword_id NOT IN (
		SELECT fkey_keyword_id FROM wmc_keyword WHERE fkey_wmc_serial_id = $3
	)
SQL;
					$v = array($wmc_DB_ID_new, $keyword,$wmc_DB_ID_new);
					$t = array("s","s","s");
					$res = db_prep_query($keywordsql, $v, $t);
					if($a = db_error()) {
					}
				}

				// update iso topic categories
				$this->isoTopicCats = $this->isoTopicCats? $this->isoTopicCats: array();
				foreach($this->isoTopicCats as $catId) {

					$catSql = "INSERT INTO wmc_md_topic_category (fkey_wmc_serial_id, fkey_md_topic_category_id) VALUES ($1,$2)";
					$v = array($wmc_DB_ID_new, $catId);
					$t = array("s","s");
					$res = db_prep_query($catSql, $v, $t);

				}

				// update inspire categories
				$this->inspireCats = $this->inspireCats? $this->inspireCats: array();
				foreach($this->inspireCats as $catId) {

					$catSql = "INSERT INTO wmc_inspire_category (fkey_wmc_serial_id, fkey_inspire_category_id) VALUES ($1,$2)";
					$v = array($wmc_DB_ID_new, $catId);
					$t = array("s","s");
					$res = db_prep_query($catSql, $v, $t);

				}

				// update custom categories
				$this->customCats = $this->customCats? $this->customCats: array();
				foreach($this->customCats as $catId) {

					$catSql = "INSERT INTO wmc_custom_category (fkey_wmc_serial_id, fkey_custom_category_id) VALUES ($1,$2)";
					$v = array($wmc_DB_ID_new, $catId);
					$t = array("s","s");
					$res = db_prep_query($catSql, $v, $t);

				}

				$result["success"] = true;
				$msg = "WMC document '" . $this->wmc_title . "' has been saved.";
				$result["message"] = $msg;
				$e = new mb_notice("mod_insertWMCIntoDB: WMC  '" . $this->wmc_title . "' saved successfully.");
			}
		}
		else {
			$result["success"] = false;
			$errMsg = "missing parameters (user_id: ".$this->userId.", title: " . $this->wmc_title . ")";
			$result["message"] = $errMsg;
			$e = new mb_exception("mod_insertWMCIntoDB: " . $errMsg .")");
		}
		db_commit();
		return $result;
	}

    	/*
    	* overwrites an exact version of a wmc in the database
    	*/
	public function update_existing($xml,$id) {
		$sql = "UPDATE mb_user_wmc SET wmc = $1 WHERE wmc_serial_id = $2";
		$v = array($xml,$id);
		$t = array("s","s");
		$res = db_prep_query($sql,$v,$t);
		if(db_error()) { $e = new mb_exception("There was an error saving an updated WMC"); }
	}

	/**
	 * deletes a {@link http://www.mapbender.org/index.php/WMC WMC}
	 * entry specified by wmc_id and user_id
	 *
	 * @param	integer		the user_id
	 * @param	string		the wmc_id
	 * @return	boolean		Did the query run successful?
	 */
	public static function delete ($wmcId, $userId) {
		if (!isset($userId) || $userId === null) {
			$userId = Mapbender::session()->get("mb_user_id");
		}

		try {
			$user = new user($userId);
		} catch (Exception $E) {
			return $false;
		}

		if($user->isPublic()) {
			return $false;
		}

		$sql = "DELETE FROM mb_user_wmc ";
		$sql .= "WHERE fkey_user_id = $1 AND wmc_serial_id = $2";
		$v = array($userId, $wmcId);
		$t = array('i', 's');
		$res = db_prep_query($sql, $v, $t);
		if ($res) {
			return true;
		}
		return false;
	}

	/**
	 * Returns a WMC document
	 * @return String|boolean The document if it exists; else false
	 * @param $id String the WMC id
	 */
	public static function getDocument ($id) {
		$sql = "SELECT wmc FROM mb_user_wmc WHERE wmc_serial_id = $1 AND " .
			"(fkey_user_id = $2 OR wmc_public = 1)";
		$v = array($id, Mapbender::session()->get("mb_user_id"));
		$t = array('s', 'i');
		$res = db_prep_query($sql,$v,$t);
		$row = db_fetch_array($res);
		if ($row) {
			$xmlDoc = new DOMDocument();
   			$xmlDoc->loadXML($row["wmc"]);
			$xmlDoc->encoding = 'UTF-8';
			$xmlDoc->preserveWhiteSpace = false;
			$xmlDoc->formatOutput = true;	
			return $xmlDoc->saveXML();
		}
		return false;
	}

	/**
	 * Returns the whole WMC document with public local data (KML)
	 * @return String|boolean The document if it exists; else false
	 * @param $id String the WMC id
	 */
	public static function getDocumentWithPublicData ($id) {
		$sql = "SELECT wmc FROM mb_user_wmc WHERE wmc_serial_id = $1 AND " .
			"wmc_local_data_public = 1";
		$v = array($id);
		$t = array('s');
		$res = db_prep_query($sql,$v,$t);
		$row = db_fetch_array($res);
		if ($row) {
			return $row["wmc"];
		}
		return false;
	}

	/**
	 * Returns a WMC document
	 * @return String|boolean The document if it exists; else false
	 * @param $id String the WMC id
	 */
	public static function getDocumentByTitle ($title) {
		$sql = "SELECT wmc FROM mb_user_wmc WHERE wmc_title = $1 AND " .
			"(fkey_user_id = $2 OR wmc_public = 1)";
		$v = array($title, Mapbender::session()->get("mb_user_id"));
		$t = array('s', 'i');
		$res = db_prep_query($sql,$v,$t);
		$row = db_fetch_array($res);
		if ($row) {
			return $row["wmc"];
		}
		return false;
	}

    /*
    * sets the WMC's public flag
    * @param $public boolean wether access should be public
    */
	public function setPublic($public) {
		$currentUser = new User(Mapbender::session()->get("mb_user_id"));
		if ($currentUser->isPublic()) {
			return false;
		}
		$wmcId = $this->wmc_id;
		$public = $public ? 1 :0;
		$sql = "UPDATE mb_user_wmc SET wmc_public = $1 WHERE wmc_serial_id = $2 AND fkey_user_id = $3;";
		$v = array($public,$wmcId, $currentUser->id);
		$t = array("i","s","i");
		$res = db_prep_query($sql,$v,$t);
		if(db_error()) {
			return false;
		}
		return true;
	}
    /*
    * increments the wmc_load_count if it has been set before
    * @param $wmc_id wmc_serial_id
    */
	public function incrementWmcLoadCount() {
		$wmcId = $this->wmc_id;
		//check for public else return false
		if ($this->isPublic()) {
			//check if a load_count has been set before
			//if not been set, set it to 1
			//else increment it
			$sql = "SELECT load_count FROM wmc_load_count where fkey_wmc_serial_id = $1;";
			$v = array($wmcId);
			$t = array("i");
			$res = db_prep_query($sql,$v,$t);
			if(db_error()) {
				return false;
			}
			$row = db_fetch_array($res);
			if ($row) {
				$e = new mb_notice("class_wmc: incrementWmcLoadCount found entry increment should be performed");
				$count = $row['load_count'];
				$count++;
				$sql = "UPDATE wmc_load_count SET load_count = $2 WHERE fkey_wmc_serial_id = $1;";
				$v = array($wmcId,$count);
				$t = array("i","i");
				$res = db_prep_query($sql,$v,$t);
			} else {
				$e = new mb_exception("class_wmc: incrementWmcLoadCount dont found entry - new should be set to 1");
				$sql = "INSERT INTO wmc_load_count (fkey_wmc_serial_id,load_count) VALUES ($1, $2);";
				$v = array($wmcId,1);
				$t = array("i","i");
				$res = db_prep_query($sql,$v,$t);
			}
			return true;
		}
		return false;
	}
    /*
    * test if the given wmc is public
    *
    */
	public function isPublic() {
		$wmcId = $this->wmc_id;
		$sql = "SELECT wmc_serial_id FROM mb_user_wmc ";
		$sql .= "WHERE wmc_serial_id = $1 AND wmc_public = 1;";
		$v = array($wmcId);
		$t = array("i");
		$res = db_prep_query($sql,$v,$t);
		$row = db_fetch_array($res);
		if (isset($row['wmc_serial_id']) && $row['wmc_serial_id'] != '') {
			$e = new mb_notice("class_wmc: isPublic is true");
			return true;
		}
		$e = new mb_notice("class_wmc: isPublic is false");
		return false;
	}

	// ---------------------------------------------------------------------------
	// GETTER FUNCTIONS
	// ---------------------------------------------------------------------------

	/**
	 * @return string the title of the WMC.
	 */
	public function getTitle() {
		return $this->wmc_title;
	}

	private function getLayerWithoutIdArray () {
		$layerWithoutWmsIdArray = array();
		$layerWithoutLayerIdArray = array();

		// check if WMS IDs exist
		$wmsArray = $this->mainMap->getWmsArray();
		for ($i = 0; $i < count($wmsArray); $i++) {
			$currentWms = $wmsArray[$i];
			if (!is_numeric($currentWms[$currentId])) {
				array_push($layerWithoutWmsIdArray, $currentId);
			}
		}

		// check if layer IDs exist TODO: no layerIdArray given!
		for ($i = 0; $i < count($layerIdArray); $i++) {
			$currentId = $layerIdArray[$i];
			if (!is_numeric($this->wmc_layer_id[$currentId])) {
				array_push($layerWithoutLayerIdArray, $currentId);
			}
		}
		$noIdArray = array_unique(array_merge($layerWithoutWmsIdArray, $layerWithoutLayerIdArray));
		return $noIdArray;
	}

    public function hasLocalData() {
        return $this->has_local_data;
    }

    public function isLocalDataPublic() {
        return $this->local_data_public;
    }

    public function getLocalDataSize() {
        return $this->local_data_size;
    }

    public function setHasLocalData($has_local_data) {
        $this->has_local_data = $has_local_data;
        return $this;
    }

    public function setLocalDataPublic($local_data_public) {
        $this->local_data_public = $local_data_public;
        return $this;
    }

    public function setLocalDataSize($local_data_size) {
        $this->local_data_size = $local_data_size;
        return $this;
    }

	// ---------------------------------------------------------------------------
	// OUTPUT FUNCTIONS
	// ---------------------------------------------------------------------------

	/**
	 * Wrapper function, returns XML at the moment
	 * @return String
	 */
	public function __toString() {
		return $this->toXml();
	}

	/**
	 * Returns the XML document if available
	 *
	 * @return String The XML document; if unavailable, null is returned.
	 */
	public function toXml () {
	//		if (!$this->xml) {
		$this->createXml();
		//		}
		return $this->xml;
	}

	private function incrementLoadCount ($wms) {
		// counts how often a layer has been loaded
		$monitor = new Layer_load_count();
		foreach ($wms->objLayer as $l) {
			$monitor->increment($l->layer_uid);
		}
	}

	private function incrementLayerLoadCount ($layerIdArray) {
		/*$layerIdString = implode(",",$layerIdArray);
		$sql = "UPDATE layer_load_count SET load_count = load_count+1 WHERE fkey_layer_id in (".$layerIdString.")";
		$res = db_query($sql);
		if (!$res) {
			$e = new mb_exception("class_wmc.php: Could not increment layer load_count of layers in wmc!");
			return false;
		} else {
			$e = new mb_notice("class_wmc.php: Updated load_count of layers in wmc!");
			return true;
		}*/
		if (is_array($layerIdArray) && count($layerIdArray) > 0) {
			$monitor = new Layer_load_count();
			$monitor->incrementMultiLayers($layerIdArray);
			return true;
		} else {
			return false;
		}
	}

	//http://stackoverflow.com/questions/3361036/php-simplexml-insert-node-at-certain-position
	private function simplexml_insert_after(SimpleXMLElement $insert, SimpleXMLElement $target) {
    		$target_dom = dom_import_simplexml($target);
    		$insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
    		if ($target_dom->nextSibling) {
        		return $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
    		} else {
        		return $target_dom->parentNode->appendChild($insert_dom);
    		}
	}

	public function extentToJavaScript() {
		return $this->mainMap->extentToJavaScript();
	}

	public function wmsToJavaScript() {
		$e = new mb_notice("class_wmc.php: create wms array from main map");
		$wmsArray = $this->mainMap->getWmsArray();

		$wmcJsArray = array();
		$e = new mb_notice("class_wmc.php: iterate over wms array");
		$e = new mb_notice("class_wmc.php: count of wms array: ".count($wmsArray));

		for ($i = 0; $i < count($wmsArray); $i++) {
			$currentWms = $wmsArray[$i];
			//$e = new mb_exception("class_wmc.php: createJsObjFromWMS number: ".$i);
			$wmcJsArray[] = $currentWms->createJsObjFromWMS_();
			//$this->incrementLoadCount($currentWms);
		}
		$e = new mb_notice("class_wmc.php: wmcJsArray created!");
		return $wmcJsArray;
	}

	public function featuretypeConfToJavaScript() {
		$wfsConfIds = $this->generalExtensionArray['WFSCONFIDSTRING'];
		//new mb_notice("app AAAA idstr $wfsConfIds");
		$featuretypeConfs = array();
		$featuretypeConfArray = is_string($wfsConfIds) ?
			explode(",", $wfsConfIds) : array();
		for ($i = 0; $i < count($featuretypeConfArray); $i++) {
 			$wfsconf = new WfsConf();
			$featuretypeConf = $wfsconf->getWfsConfFromDb($featuretypeConfArray[$i]);
			array_push($featuretypeConfs,$featuretypeConf);
		}
		$featuretypeConfObj = new Mapbender_JSON();
		$featuretypeConfObj = $featuretypeConfObj->encode($featuretypeConfs);
		return $featuretypeConfObj;
	}

	public function removeWms ($wmsIndexArray) {
		$wmsArray = $this->mainMap->getWmsArray();

		// remove WMS from overview map
		if (!is_null($this->overviewMap)) {
			$ovIndices = array();
			$ovWmsArray = $this->overviewMap->getWmsArray();
			for ($i = 0; $i < count($ovWmsArray); $i++) {
				for ($j = 0; $j < count($wmsArray); $j++) {
					if ($ovWmsArray[$i]->equals($wmsArray[$j]) && in_array($j, $wmsIndexArray)) {
						$ovIndices[]= $i;
						break;
					}
				}
			}
			$this->overviewMap->removeWms($ovIndices);
		}

		// remove WMS from main map
		$this->mainMap->removeWms($wmsIndexArray);
	}

	/**
	 * Returns an array of JavaScript statements
	 *
	 * @return String[]
	 */
	public function toJavaScript () {
		$skipWmsArray = array();
		if (func_num_args() === 1) {
			if (!is_array(func_get_arg(0))) {
				throw new Exception("Invalid argument, must be array.");
			}
			$skipWmsArray = func_get_arg(0);
		}

		// will contain the JS code to create the maps
		// representing the state stored in this WMC
		$wmcJsArray = array();

		// set general extension data
		if (count($this->generalExtensionArray) > 0) {
			$json = new Mapbender_JSON();
			array_push($wmcJsArray, "restoredWmcExtensionData = " . $json->encode($this->generalExtensionArray) . ";");
		}

		// reset WMS data
		array_push($wmcJsArray, "wms = [];");
		array_push($wmcJsArray, "wms_layer_count = 0;");
		// add WMS for main map frame
		$wmsArray = $this->mainMap->getWmsArray();
		// find the WMS in the main map which is equal to the WMS
		// in the overview map
		$overviewWmsIndex = null;
		$ovWmsArray = array();
		if ($this->overviewMap !== null) {
			$ovWmsArray = $this->overviewMap->getWmsArray();
			$overviewWmsIndex = 0;
			for ($i = 0; $i < count($ovWmsArray); $i++) {
				for ($j = 0; $j < count($wmsArray); $j++) {
					if ($ovWmsArray[$i]->equals($wmsArray[$j]) && !in_array($j, $skipWmsArray)) {
						$overviewWmsIndex = $j;
						$wmsIndexOverview = $i;
						break;
					}
				}
			}
		}
		// for all wms...
		$layerIdArray = array();
		for ($i = 0; $i < count($wmsArray); $i++) {
			if (in_array($i, $skipWmsArray)) {
				continue;
			}
			//get all layer_uid from mapObject!
			$layer = $wmsArray[$i]->objLayer;
			for ($j = 0; $j < count($layer); $j++) {
				$layerIdArray[] = (integer)$layer[$j]->layer_uid;
			}
			array_push($wmcJsArray, $wmsArray[$i]->createJsObjFromWMS_());
			//$this->incrementLoadCount($wmsArray[$i]);
		}
		//$e = new mb_exception(microtime()."class_wmc.php:toJavaScript(): layerIdArray ".json_encode($layerIdArray));
		// delete existing map objects...
		//		array_push($wmcJsArray, "mb_mapObj = [];");

		// .. and add the overview map (if exists) and set map request
		if ($this->overviewMap !== null) {
			$wmcJsArray = array_merge(
				$wmcJsArray,
				$this->overviewMap->toJavaScript(
				"{wms:wms,wmsIndexOverview:" . $overviewWmsIndex . "}"
				)
			);
		}
		// .. and add main map ..
		$wmcJsArray = array_merge(
			$wmcJsArray,
			$this->mainMap->toJavaScript(
			"{wms:wms,wmsIndexOverview:null}"
			)
		);
		// set visibility of ov map WMS (may be different from main)
		if ($this->overviewMap !== null) {
			for ($i = 0; $i < count($ovWmsArray[$wmsIndexOverview]->objLayer); $i++) {
				$visStr = "try { Mapbender.modules['".$this->overviewMap->getFrameName().
					//					"'].wms[" .$wmsIndexOverview . "].handleLayer(" .
					// The above doesn't work.
					// But there is only one WMS in the overview anyway! The index 0 is hard wired for now.
					"'].wms[0].handleLayer(" .
					"'" . $ovWmsArray[$wmsIndexOverview]->objLayer[$i]->layer_name . "', " .
					"'visible', " .
					($ovWmsArray[$wmsIndexOverview]->objLayer[$i]->gui_layer_visible ? 1 : 0) . ")} catch (e) {};";
				array_push($wmcJsArray, $visStr);
			}
			array_push($wmcJsArray, "try { Mapbender.modules['".$this->overviewMap->getFrameName().
				"'].restateLayers(" . $ovWmsArray[$wmsIndexOverview]->wms_id . ");} catch (e) {};");
		}
		//increment the load count for known layers - TODO: Why here? -layer will be counted more than once!!!!
		$this->incrementLayerLoadCount(array_unique($layerIdArray));
		// .. request the map

		array_push($wmcJsArray, "lock_maprequest = true;");
		array_push($wmcJsArray, "eventAfterLoadWMS.trigger();"); //TODO: Why? Reload tree? Other way to do this?
		array_push($wmcJsArray, "lock_maprequest = false;");
		array_push($wmcJsArray, "Mapbender.modules['".$this->mainMap->getFrameName().
			"'].setMapRequest();");
		if ($this->overviewMap !== null) {
			array_push($wmcJsArray, "try {Mapbender.modules['".$this->overviewMap->getFrameName().
				"'].setMapRequest()} catch (e) {};");
		}
		//?initializeWms()
		//eventAfterLoadWMS.register(reloadTree);
		return $wmcJsArray;
	}

	// ------------------------------------------------------------------------
	// manipulation
	// ------------------------------------------------------------------------
	/**
	 * Merges this WMC with another WMC.
	 * The settings of the other WMC overwrite the settings of this WMC.
	 *
	 * @return void
	 * @param $xml2 Object
	 */
	public function merge ($xml2) {
		$someWmc = new wmc();
		$someWmc->createFromXml($xml2);

		$this->mainMap->merge($someWmc->mainMap);
		if (isset($this->overviewMap) && isset($someWmc->overviewMap)) {
			$this->overviewMap->merge($someWmc->overviewMap);
		}
	}

	/**
	 * Appends the layers of another WMC to this WMC.
	 *
	 * @return void
	 * @param $xml2 Object
	 */
	public function append ($xml2) {
		$someWmc = new wmc();
		$someWmc->createFromXml($xml2);

		$this->mainMap->append($someWmc->mainMap);
		if (isset($this->overviewMap) && isset($someWmc->overviewMap)) {
		// There is only one WMS in the overview map; merge, not append
			$this->overviewMap->merge($someWmc->overviewMap);
		}
	}

	/**
	 * Adds a WMS to this WMC
	 *
	 * @return
	 */
	public function appendWmsArray ($wmsArray) {
		return $this->mainMap->appendWmsArray($wmsArray);
	}

	/**
	 * Merges a WMS into this WMC
	 *
	 * @return
	 */
	public function mergeWmsArray ($wmsArray) {
		if (func_num_args() > 1) {
			$options = func_get_arg(1);
			return $this->mainMap->mergeWmsArray($wmsArray, $options);
		}
		return $this->mainMap->mergeWmsArray($wmsArray);
	}

	//for debugging purposes only
	private function logit($text){
	 	if($h = fopen("/tmp/class_wmc.log","a")){
					$content = $text .chr(13).chr(10);
					if(!fwrite($h,$content)){
						#exit;
					}
					fclose($h);
				}
	 	
	 }

	// ---------------------------------------------------------------------------
	// private functions
	// ---------------------------------------------------------------------------

	/**
	 * Loads a WMC from an actual WMC XML document.
	 * Uses WMS class.
	 *
	 * @param string $data the data from the XML file
	 */
	protected function createObjFromWMC_xml($data) {
	// store xml
		$this->xml = $data;
		//$wmcXml = simplexml_load_string(mb_utf8_encode($data));
		//if ($wmcXml) {
		//	$e = new mb_exception("class_wmc.php: parsing wmc successfully");
		//}
		//$e = new mb_exception("class_wmc.php: data: ".$data);
		//$this->logit($data);
		$values = administration::parseXml($data);
		if (!$values) {
			throw new Exception("WMC document could not be parsed.");
		}
		//
		// Local variables that indicate which section of the WMC
		// is currently parsed.
		//
		$extension = false;		$general = false;		$layerlist = false;
		$layer = false;  		$layer_dimensionlist = false;         $formatlist = false;	$layer_dataurl = false;
		$layer_metadataurl = false; 	$stylelist = false;		//$layer_featuretype_coupling = false;

		//
		// reset WMC data
		//
		$this->mainMap = new Map();
		$this->overviewMap = null;
		$this->generalExtensionArray = array();

		$layerlistArray = array();
		$layerlistArray["main"] = array();
		$layerlistArray["overview"] = array();
		//parse WMC per simpleXML and use xpath instead of old bib
		//$wmcXml = new SimpleXMLElement($data);


		//$wmcXml = simplexml_load_string(mb_utf8_encode($data));
		//echo (";");
		//print_r($wmcXml.";");
		//if ($wmcXml) {
		//	$e = new mb_exception("class_wmc.php: parsing wmc successfully");
		//}
		//print_r($ViewContext);
		//$wmcXml->registerXPathNamespace('standard','http://www.opengis.net/context');
		//$title = $wmcXml->xpath("/ViewContext/General/Title");
		//$wmcXml->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		//$wmcXml->registerXPathNamespace('mapbender', 'http://www.mapbender.org/context');
		//$wmcXml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
		//$wmcXml->registerXPathNamespace('wmc', 'http://opengis.net/context');
		//$e = new mb_exception("class_wmc.php: data: ".mb_utf8_encode($data));
		//$this->wmc_id = $wmcXml->xpath('/ViewContext/@id');
		//$this->wmc_version = $wmcXml->xpath('/ViewContext/@version');
		//$title = $wmcXml->xpath('/standard:ViewContext/standard:Title');
		//var_dump($title);

		//$e = new mb_exception("class_wmc.php: parsing wmc by xpath: title: ".$title[0]);
		//$e = new mb_exception("class_wmc.php: parsing wmc by xpath: wmc_version: ".$this->wmc_version);
        //$e = new mb_exception("");
        //$e = new mb_exception("class_wmc.php: createObjFromWMC_xml: ".$data);

		foreach ($values as $element) {
			$tag = strtoupper(administration::sepNameSpace($element['tag']));
			$tagLowerCase = administration::sepNameSpace($element['tag']);
			$type = $element['type'];
			$attributes = $element['attributes'];
			$value = mb_utf8_decode(html_entity_decode($element['value']));

			if ($tag == "VIEWCONTEXT" && $type == "open") {
				$this->wmc_id = $attributes["id"];
				$this->wmc_version = $attributes["version"];
			}
			if ($tag == "GENERAL" && $type == "open") {
				$general = true;
			}
			if ($tag == "LAYERLIST" && $type == "open") {
				$layerlist = true;
			}
			if ($general) {
				if ($tag == "WINDOW") {
					$this->mainMap->setWidth($attributes["width"]);
					$this->mainMap->setHeight($attributes["height"]);
				}
				if ($tag == "BOUNDINGBOX") {
					$bbox = new Mapbender_bbox($attributes["minx"], $attributes["miny"], $attributes["maxx"], $attributes["maxy"], $attributes["SRS"]);
					$this->mainMap->setExtent($bbox);
				}
				if ($tag == "NAME") {
					$this->wmc_name = $value;
				}
				if ($tag == "TITLE") {
					$this->wmc_title = $value;
				}
				if ($tag == "ABSTRACT") {
					$this->wmc_abstract = $value;
				}
				if ($tag == "CONTACTINFORMATION" && $type == "open") {
					$contactinformation = true;
				}
				if ($contactinformation) {
					if ($tag == "CONTACTPOSITION") {
						$this->wmc_contactposition = $value;
					}
					if ($tag == "CONTACTVOICETELEPHONE") {
						$this->wmc_contactvoicetelephone = $value;
					}
					if ($tag == "CONTACTFACSIMILETELEPHONE") {
						$this->wmc_contactfacsimiletelephone = $value;
					}
					if ($tag == "CONTACTELECTRONICMAILADDRESS") {
						$this->wmc_contactemail = $value;
					}
					if ($tag == "CONTACTPERSONPRIMARY" && $type == "open") {
						$contactpersonprimary = true;
					}
					if ($contactpersonprimary) {
						if ($tag == "CONTACTPERSON") {
							$this->wmc_contactperson = $value;
						}
						if ($tag == "CONTACTORGANIZATION") {
							$this->wmc_contactorganization = $value;;
						}
						if ($tag == "CONTACTPERSONPRIMARY" && $type == "close") {
							$contactpersonprimary = false;
						}
					}
					if ($tag == "CONTACTADDRESS" && $type == "open") {
						$contactaddress = true;
					}
					if ($contactaddress) {
						if ($tag == "ADDRESSTYPE") {
							$this->wmc_contactaddresstype = $value;
						}
						if ($tag == "ADDRESS") {
							$this->wmc_contactaddress = $value;
						}
						if ($tag == "CITY") {
							$this->wmc_contactcity = $value;
						}
						if ($tag == "STATEORPROVINCE") {
							$this->wmc_contactstateorprovince = $value;
						}
						if ($tag == "POSTCODE") {
							$this->wmc_contactpostcode = $value;
						}
						if ($tag == "COUNTRY") {
							$this->wmc_contactcountry = $value;
						}
						if ($tag == "CONTACTADDRESS" && $type == "close") {
							$contactaddress = false;
						}
					}
				}
				if ($tag == "LOGOURL" && $type == "open") {
					$logourl = true;
					$this->wmc_logourl_width = $attributes["width"];
					$this->wmc_logourl_height = $attributes["height"];
					$this->wmc_logourl_format = $attributes["format"];
				}
				if ($logourl) {
					if ($tag == "LOGOURL" && $type == "close") {
						$logourl = false;
					}
					if ($tag == "ONLINERESOURCE") {
						$this->wmc_logourl_type = $attributes["xlink:type"];
						$this->wmc_logourl = $attributes["xlink:href"];
					}
				}
				if ($tag == "DESCRIPTIONURL" && $type == "open") {
					$descriptionurl = true;
					$this->wmc_descriptionurl_format = $attributes["format"];
				}
				if ($descriptionurl) {
					if ($tag == "DESCRIPTIONURL" && $type == "close") {
						$descriptionurl = false;
					}
					if ($tag == "ONLINERESOURCE") {
						$this->wmc_descriptionurl_type = $attributes["xlink:type"];
						$this->wmc_descriptionurl = $attributes["xlink:href"];
					}
				}
				if ($tag == "KEYWORDLIST" && $type == "open") {
					$keywordlist = true;
				}
				if ($keywordlist) {
					if ($tag == "KEYWORDLIST" && $type == "close") {
						$keywordlist = false;
						$cnt_keyword = -1;
					}
					if ($tag == "KEYWORD") {
						$cnt_keyword++;
						$this->wmc_keyword[$cnt_keyword] = $value;
					}
				}
				if ($tag == "EXTENSION" && $type == "close") {
					$generalExtension = false;
					//
					// After the general extension tag is closed,
					// we have all necessary information to CREATE
					// the map objects that are contained in this
					// WMC.
					//
					$this->setMapData();
				}
				if ($generalExtension) {
					if ($value !== "") {
						if (isset($this->generalExtensionArray[$tag])) {
							if (!is_array($this->generalExtensionArray[$tag])) {
								$firstValue = $this->generalExtensionArray[$tag];
								$this->generalExtensionArray[$tag] = array();
								array_push($this->generalExtensionArray[$tag], $firstValue);
							}
							array_push($this->generalExtensionArray[$tag], $value);
						}
						else {
							$this->generalExtensionArray[$tag] = $value;
						}
					}
				}
				if ($tag == "EXTENSION" && $type == "open") {
					$generalExtension = true;
				}
				if ($tag == "GENERAL" && $type == "close") {
					$general = false;
				}
			}
			if ($layerlist) {
				if ($tag == "LAYERLIST" && $type == "close") {
					$layerlist = false;
				}
				if ($tag == "LAYER" && $type == "open") {
				//
				// The associative array currentLayer holds all
				// data of the currently processed layer.
				// The data will be set in the classes' WMS
				// object when the layer tag is closed.
				//
					$currentLayer = array();

					$currentLayer["queryable"] = $attributes["queryable"];
					if ($attributes["hidden"] == "1") {
						$currentLayer["visible"] = 0;
					}
					else {
						$currentLayer["visible"] = 1;
					}
					$currentLayer["format"] = array();
					$currentLayer["style"] = array();
					$currentLayer["dimension"] = array();
					//$currentLayer["layer_metadataurl"] = array();
					//$currentLayer["layer_dataurl"] = array();
					$layer = true;
				}
				if ($layer) {
					if ($tag == "LAYER" && $type == "close") {

					//
					// After a layer tag is closed,
					// we have all necessary information to CREATE
					// a layer object and append it to the WMS object
					//
/*if (isset($currentLayer["extension"]["LAYER_FEATURETYPE_COUPLING"]) && $currentLayer["extension"]["LAYER_FEATURETYPE_COUPLING"] !== "") {
	$e = new mb_exception("class_wmc.php: found layer_featuretype_coupling: ".$currentLayer["extension"]["LAYER_FEATURETYPE_COUPLING"]);
}*/
						if (isset($currentLayer["extension"]["OVERVIEWHIDDEN"])) {
							array_push($layerlistArray["overview"], $currentLayer);
						}
						$modifiedLayer = $currentLayer;
						unset($modifiedLayer["extension"]["OVERVIEWHIDDEN"]);
						array_push($layerlistArray["main"], $modifiedLayer);
						$layer = false;
					}
					//check debug
					if ($layer_dimensionlist) {
						if ($tag == "DIMENSION") {
							//main problem: currentLayer is not an object but an array :-(
							$dimensionIndex = count($currentLayer['dimension']);
							if ($dimensionIndex <= 0) {
								$dimensionIndex = 0;
								$dimensionAttributes = array();
							}
							foreach (array_keys($attributes) as $attribute) {
								$dimensionAttributes[$attribute] = $attributes[$attribute];
							}
							array_push($currentLayer['dimension'], $dimensionAttributes);
						}
						if ($tag == "DIMENSIONLIST" && $type == "close") {
							$layer_dimensionlist = false;
						}
					}
					if ($formatlist) {
						if ($tag == "FORMAT") {
							array_push($currentLayer["format"], array("current" => $attributes["current"], "name" => $value));
							if ($attributes["current"] == "1") {
								$currentLayer["formatIndex"] = count($currentLayer["format"]) - 1;
							}
						}
						if ($tag == "FORMATLIST" && $type == "close") {
							$formatlist = false;
						}
					}
					elseif ($layer_metadataurl) {
						if ($tag == "ONLINERESOURCE") {
							$currentLayer["layer_metadataurl"] = $attributes["xlink:href"];
						}
						if ($tag == "METADATAURL" && $type == "close") {
							$layer_metadataurl = false;
						}
					}
					elseif ($layer_dataurl) {
						if ($tag == "ONLINERESOURCE") {
							$currentLayer["layer_dataurl"] = $attributes["xlink:href"];
						}
						if ($tag == "DATAURL" && $type == "close") {
							$layer_dataurl = false;
						}
					}
					elseif ($stylelist) {
						if ($style) {
							$index = count($currentLayer["style"]) - 1;
							if ($tag == "STYLE" && $type == "close") {
								$style = false;
							}
							if ($tag == "SLD" && $type == "open") {
								$sld = true;
							}
							if ($sld) {
								if ($tag == "SLD" && $type == "close") {
									$sld = false;
								}
								if ($tag == "ONLINERESOURCE") {
									$currentLayer["style"][$index]["sld_type"] = $attributes["xlink:type"];
									$currentLayer["style"][$index]["sld_url"] = $attributes["xlink:href"];
								}
								if ($tag == "TITLE") {
									$currentLayer["style"][$index]["sld_title"] = $value;
								}
							}
							else {
								if ($tag == "NAME") {
									$currentLayer["style"][$index]["name"] = $value ? $value : "default";
								}
								if ($tag == "TITLE") {
									$currentLayer["style"][$index]["title"] = $value ? $value : "default";
								}
								if ($legendurl) {
									if ($tag == "LEGENDURL" && $type == "close") {
										$legendurl = false;
									}
									if ($tag == "ONLINERESOURCE") {
										$currentLayer["style"][$index]["legendurl_type"] = $attributes["xlink:type"];
										$currentLayer["style"][$index]["legendurl"] = $attributes["xlink:href"];
										//$e = new mb_exception('class_wmc: legendurl onlineresource xlink:href: '.$attributes["xlink:href"]);
									}
								}
								if ($tag == "LEGENDURL" && $type == "open") {
									$legendurl = true;
									$currentLayer["style"][$index]["legendurl_width"] = $attributes["width"];
									$currentLayer["style"][$index]["legendurl_height"] = $attributes["height"];
									$currentLayer["style"][$index]["legendurl_format"] = $attributes["format"];
								}
							}
						}
						if ($tag == "STYLE" && $type == "open") {
							$style = true;
							array_push($currentLayer["style"], array("current" => $attributes["current"]));
							if ($attributes["current"] == "1") {
								$currentLayer["styleIndex"] = count($currentLayer["style"]) - 1;
							}
						}
						if ($tag == "STYLELIST" && $type == "close") {
							$stylelist = false;
						}
					}
					else {
						if ($tag == "SERVER" && $type == "open") {
							$server = true;
							$currentLayer["service"] = $attributes["service"];
							$currentLayer["version"] = $attributes["version"];
							$currentLayer["wms_title"] = $attributes["title"];
						}
						if ($server) {
							if ($tag == "SERVER" && $type == "close") {
								$server = false;
							}
							if ($tag == "ONLINERESOURCE") {
								$currentLayer["url"] = $attributes["xlink:href"];
							}
						}
						if ($tag == "NAME") {
							$currentLayer["name"] = $value;
						}
						if ($tag == "TITLE") {
							$currentLayer["title"] = $value;
						}
						if ($tag == "ABSTRACT") {
							$currentLayer["abstract"] = $value;
						}
						if ($tag == "SRS") {
							$currentLayer["epsg"] = explode(" ", $value);
						}
						if ($tag == "EXTENSION" && $type == "close") {
							$extension = false;
						}
						if ($extension == true) {
						//
/*if ($tag == "LAYER_FEATURETYPE_COUPLING" && $currentLayer["extension"][$tag] !== null) {							//if ($value !== "") {
	$e = new mb_exception("classes/class_wmc.php: createObjFromWMC_xml: layer extension tag:  ".$tag." - value: ".json_encode($currentLayer["extension"][$tag]));	
}*/
							if (isset($currentLayer["extension"][$tag])) {
								if (!is_array($currentLayer["extension"][$tag])) {
									$firstValue = $currentLayer["extension"][$tag];
									$currentLayer["extension"][$tag] = array();
									array_push($currentLayer["extension"][$tag], $firstValue);
								}
								array_push($currentLayer["extension"][$tag], $value);
							}
							else {
								$currentLayer["extension"][$tag] = $value;
							}
						//							}
						}
						if ($tag == "EXTENSION" && $type == "open") {
							$currentLayer["extension"] = array();
							$extension = true;
						}
						if ($tag == "METADATAURL" && $type == "open") {
							$layer_metadataurl = true;
						}
						if ($tag == "DATAURL" && $type == "open") {
							$layer_dataurl = true;
						}
						if ($tag == "DIMENSIONLIST" && $type == "open") {
							//$e = new mb_exception("class-wmc.php: found dimensionlist tag!");
							$layer_dimensionlist = true;
						}
						if ($tag == "FORMATLIST" && $type == "open") {
							$formatlist = true;
						}
						if ($tag == "STYLELIST" && $type == "open") {
							$stylelist = true;
						}
					}
				}
			}
		}

		// set WMS data

		$layerlistCompleteArray = array_merge($layerlistArray["main"], $layerlistArray["overview"]);

		for ($i = 0; $i < count($layerlistCompleteArray); $i++) {
			$this->setLayerData($layerlistCompleteArray[$i]);
		}

		$wmsArr = $this->mainMap->getWmsArray();
		for ($i = 0; $i < count($wmsArr); $i++) {
			$wmsArr[$i]->updateAllOwsProxyUrls();
		}
		return true;
	}

	/**
	 * Saves the current WMC in the log folder.
	 *
	 * @return string the filename of the WMC document.
	 */
	private function saveAsFile() {
		if ($this->saveWmcAsFile) {
			$filename = "wmc_" . date("Y_m_d_H_i_s") . ".xml";
			$logfile = "../tmp/" . $filename;

			if($h = fopen($logfile,"a")) {
				$content = $this->xml;
				if(!fwrite($h,$content)) {
					$e = new mb_exception("class_wmc.php: failed to write wmc.");
					return false;
				}
				fclose($h);
			}
			$e = new mb_notice("class_wmc: saving WMC as file " . $filename . "; You can turn this behaviour off in class_wmc.php");
			return $filename;
		}
		return null;
	}

	/**
	 * Called during WMC parsing; sets the data of a single layer.
	 *
	 * @return
	 * @param $currentLayer Array an associative array with layer data
	 */
	private function setLayerData ($currentLayer) {
		$currentMap = $this->mainMap;
		$currentMapIsOverview = false;

		if (isset($currentLayer["extension"]["OVERVIEWHIDDEN"])) {
			$currentMap = $this->overviewMap;
			$currentMapIsOverview = true;
		}

		if (is_null($currentMap)) {
			$e = new mb_exception('class_wmc.php: setLayerData: $currentMap is null. Aborting.');
			return null;
		}

		$wmsArray = $currentMap->getWmsArray();

		//
		// check if current layer belongs to an existing WMS.
		// If yes, store the index of this WMS in $wmsIndex.
		// If not, set the value to null.
		//
		$wmsIndex = null;

		// find last WMS with the same online resource
		for ($i = count($wmsArray) - 1; $i >= 0; $i--) {
			if (isset($currentLayer["url"]) &&
				$currentLayer["url"] == $wmsArray[$i]->wms_getmap) {
				$wmsIndex = $i;
				break;
			}
		}

		// Even if this WMS has been found before it could still
		// be a duplicate! We would have to create a new WMS and
		// not append this layer to that WMS.
		// For the overview layer we never add a new wms.
		// check if this layer is an overview layer. If yes, skip this layer.
		if ($wmsIndex !== null && !$currentMapIsOverview) {

		// check if this WMS has a layer equal to the current layer.
		// If yes, this is a new WMS. If not, append this layer
		// to the existing WMS.
			$matchingWmsLayerArray = $this->wmsArray[$wmsIndex]->objLayer;

			for ($i = 0; $i < count($matchingWmsLayerArray); $i++) {
				if ($matchingWmsLayerArray[$i]->layer_name == $currentLayer["name"]) {

				// by re-setting the index to null, a new WMS will be
				// added below.
					$wmsIndex = null;
					break;
				}
			}
		}

		// if yes, create a new WMS ...
		if ($wmsIndex === null) {
			$wmsIndex = 0;
			$wms = new wms();

			//
			// set WMS data
			//
			$wms->wms_id = $currentLayer["extension"]["WMS_ID"]; // TO DO: how about WMS without ID?
			$wms->wms_version = $currentLayer["version"];
			$wms->wms_title = $currentLayer["wms_title"];
			$wms->wms_abstract = $currentLayer["abstract"];
			$wms->wms_getmap = $currentLayer["url"];
			$wms->wms_getfeatureinfo = $currentLayer["url"]; // TODO : Add correct data

			$styleIndex = $currentLayer["styleIndex"];
			$wms->wms_getlegendurl = $currentLayer["style"][$styleIndex]["legendurl"];

			$wms->wms_filter = ""; // TODO : Add correct data

			$formatIndex = $currentLayer["formatIndex"];
			$wms->gui_wms_mapformat = $currentLayer["format"][$formatIndex]["name"];

			$wms->gui_wms_featureinfoformat = "text/html"; // TODO : Add correct data
			if($currentLayer["version"] == '1.3.0' || $currentLayer["version"] == '1.0.0'){
				$wms->gui_wms_exceptionformat = "XML"; // TODO : Add correct data
			}else{
				$wms->gui_wms_exceptionformat = "application/vnd.ogc.se_xml"; // TODO : Add correct data
			}
			$wms->gui_wms_epsg = $this->mainMap->getEpsg();
			$wms->gui_wms_visible = $currentLayer["extension"]["WMS_VISIBLE"];
			$wms->gui_wms_opacity = $currentLayer["extension"]["GUI_WMS_OPACITY"];
			$wms->gui_wms_sldurl = $currentLayer["style"][$styleIndex]["sld_url"];

			//things for dimension
			$wms->gui_wms_dimension_time = false;
			$wms->gui_wms_dimension_elevation = false;
			
			
			$wms->wms_srs = $currentLayer["epsg"];
			$wms->gui_epsg = $currentLayer["epsg"];
			//
			// set data formats
			//
			for ($i = 0; $i < count($currentLayer["format"]); $i++) {
				array_push($wms->data_type, "map");
				array_push($wms->data_format, $currentLayer["format"][$i]["name"]);
			}

			// add WMS
			array_push($wmsArray, $wms);

			// the index of the WMS we just added
			$wmsIndex = count($wmsArray) - 1;
		}

		// add layer to existing WMS ...
		$currentWms = $wmsArray[$wmsIndex];
		$currentWms->newLayer($currentLayer, null);
		$currentMap->setWmsArray($wmsArray);
		return true;
	}

	/**
	 * Called during WMC parsing; sets the maps within a WMC.
	 *
	 * @return
	 */
	private function setMapData () {
		if ($this->generalExtensionArray["OV_WIDTH"] &&
			$this->generalExtensionArray["OV_HEIGHT"] &&
			$this->generalExtensionArray["OV_FRAMENAME"] &&
			$this->generalExtensionArray["OV_MINX"] &&
			$this->generalExtensionArray["OV_MINY"] &&
			$this->generalExtensionArray["OV_MAXX"] &&
			$this->generalExtensionArray["OV_MAXY"] &&
			$this->generalExtensionArray["OV_SRS"]) {

			$this->overviewMap = new Map();
			$this->overviewMap->setWidth(
				// this should not be an array, but sometimes it is.
				// I can't find the reason at the moment, consider
				// this a workaround
				is_array($this->generalExtensionArray["OV_WIDTH"]) ?
				$this->generalExtensionArray["OV_WIDTH"][0] :
				$this->generalExtensionArray["OV_WIDTH"]
			);
			$this->overviewMap->setHeight(
				// this should not be an array, but sometimes it is.
				// I can't find the reason at the moment, consider
				// this a workaround
				is_array($this->generalExtensionArray["OV_HEIGHT"]) ?
				$this->generalExtensionArray["OV_HEIGHT"][0] :
				$this->generalExtensionArray["OV_HEIGHT"]
			);
			$this->overviewMap->setFrameName(
				// this should not be an array, but sometimes it is.
				// I can't find the reason at the moment, consider
				// this a workaround
				is_array($this->generalExtensionArray["OV_FRAMENAME"]) ?
				$this->generalExtensionArray["OV_FRAMENAME"][0] :
				$this->generalExtensionArray["OV_FRAMENAME"]
			);
			$this->overviewMap->setIsOverview(true);

			if (is_array($this->generalExtensionArray["OV_SRS"])) {
				$this->generalExtensionArray["OV_SRS"] = $this->generalExtensionArray["OV_SRS"][0];
				$this->generalExtensionArray["OV_MINX"] = $this->generalExtensionArray["OV_MINX"][0];
				$this->generalExtensionArray["OV_MINY"] = $this->generalExtensionArray["OV_MINY"][0];
				$this->generalExtensionArray["OV_MAXX"] = $this->generalExtensionArray["OV_MAXX"][0];
				$this->generalExtensionArray["OV_MAXY"] = $this->generalExtensionArray["OV_MAXY"][0];
			}
			$bbox = new Mapbender_bbox($this->generalExtensionArray["OV_MINX"], $this->generalExtensionArray["OV_MINY"], $this->generalExtensionArray["OV_MAXX"], $this->generalExtensionArray["OV_MAXY"], $this->generalExtensionArray["OV_SRS"]);
			$this->overviewMap->setExtent($bbox);
		}
		if ($this->generalExtensionArray["EPSG"] &&
			$this->generalExtensionArray["MINX"] &&
			$this->generalExtensionArray["MINY"] &&
			$this->generalExtensionArray["MAXX"] &&
			$this->generalExtensionArray["MAXY"]) {

			$mainEpsgArray = array();
			$mainMinXArray = array();
			$mainMinYArray = array();
			$mainMaxXArray = array();
			$mainMaxYArray = array();
			if (!is_array($this->generalExtensionArray["EPSG"])) {
				$mainEpsgArray[0] = $this->generalExtensionArray["EPSG"];
				$mainMinXArray[0] = $this->generalExtensionArray["MINX"];
				$mainMinYArray[0] = $this->generalExtensionArray["MINY"];
				$mainMaxXArray[0] = $this->generalExtensionArray["MAXX"];
				$mainMaxYArray[0] = $this->generalExtensionArray["MAXY"];
			}
			else {
				$mainEpsgArray = $this->generalExtensionArray["EPSG"];
				$mainMinXArray = $this->generalExtensionArray["MINX"];
				$mainMinYArray = $this->generalExtensionArray["MINY"];
				$mainMaxXArray = $this->generalExtensionArray["MAXX"];
				$mainMaxYArray = $this->generalExtensionArray["MAXY"];
			}

			for ($i=0; $i < count($mainEpsgArray); $i++) {
				$box = new Mapbender_bbox(
					floatval($mainMinXArray[$i]), floatval($mainMinYArray[$i]),
					floatval($mainMaxXArray[$i]), floatval($mainMaxYArray[$i]),
					$mainEpsgArray[$i]
				);
				$this->mainMap->addZoomFullExtent($box);
			}
		}

		if ($this->generalExtensionArray["MAIN_FRAMENAME"]) {
			$this->mainMap->setFrameName(
				// this should not be an array, but sometimes it is.
				// I can't find the reason at the moment, consider
				// this a workaround
				is_array($this->generalExtensionArray["MAIN_FRAMENAME"]) ?
				$this->generalExtensionArray["MAIN_FRAMENAME"][0] :
				$this->generalExtensionArray["MAIN_FRAMENAME"]
			);
		}
		else {
			$this->mainMap->setFrameName("mapframe1");
		}
		unset($this->generalExtensionArray["OV_WIDTH"]);
		unset($this->generalExtensionArray["OV_HEIGHT"]);
		unset($this->generalExtensionArray["OV_MINX"]);
		unset($this->generalExtensionArray["OV_MINY"]);
		unset($this->generalExtensionArray["OV_MAXX"]);
		unset($this->generalExtensionArray["OV_MAXY"]);
		unset($this->generalExtensionArray["OV_SRS"]);
		unset($this->generalExtensionArray["OV_FRAMENAME"]);
		unset($this->generalExtensionArray["MINX"]);
		unset($this->generalExtensionArray["MINY"]);
		unset($this->generalExtensionArray["MAXX"]);
		unset($this->generalExtensionArray["MAXY"]);
		unset($this->generalExtensionArray["EPSG"]);
		unset($this->generalExtensionArray["MAIN_FRAMENAME"]);
		return true;
	}

	/**
	 * Creates a WMC document (XML) from the current object
	 *
	 * @return String XML
	 */
	private function createXml() {
		$wmcToXml = new WmcToXml($this);
		$this->xml = $wmcToXml->getXml();
	}
}

/**
 * @deprecated
 */
function mb_utf8_encode ($str) {
//	if(CHARSET=="UTF-8") return utf8_encode($str);
	return $str;
}

/**
 * @deprecated
 */
function mb_utf8_decode ($str) {
//	if(CHARSET=="UTF-8") return utf8_decode($str);
	return $str;
}
?>
