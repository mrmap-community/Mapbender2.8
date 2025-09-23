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
require_once(dirname(__FILE__)."/class_connector.php");
require_once(dirname(__FILE__)."/class_administration.php");
require_once(dirname(__FILE__)."/class_wfs.php");
require_once dirname(__FILE__) . "/class_Uuid.php";
require_once dirname(__FILE__) . "/class_iso19139.php";
//require_once dirname(__FILE__) . "//class_Uuid.php";

class WfsToDb {
	//check if metadata should be overwritten completly by caps or not. Default to overwrite all (keywords, categories, ...)
	var $overwrite = true;
	var $urlsToExclude = array();

	function __construct() {
		$this->urlsToExclude = $urlsToExclude;
		if (is_file(dirname(__FILE__) . "/../../conf/excludeHarvestMetadataUrls.conf")) {
			require_once(dirname(__FILE__) . "/../../conf/excludeHarvestMetadataUrls.conf");
			$this->urlsToExclude = $urlsToExclude;
		}
	}
	/**
	 * Inserts a new or updates an existing WFS. Replaces the old wfs2db function.
	 * 
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public function insertOrUpdate ($aWfs, $owner=false) {
		if (WfsToDb::exists($aWfs)) {
			return WfsToDb::update($aWfs);
		}
		return WfsToDb::insert($aWfs, $owner);
	}
	
	/**
	 * Inserts a new WFS into the database.
	 * 
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public static function insert ($aWfs, $owner=false) {
		db_begin();
		if (is_null($aWfs->alternate_title)) {
		    $aWfs->alternate_title = "";
		}
		$uuid = new Uuid();
		$sql = "INSERT INTO wfs (wfs_version, wfs_name, wfs_title, wfs_abstract, ";
		$sql .= "wfs_getcapabilities, wfs_getcapabilities_doc, wfs_upload_url, ";
		$sql .= "wfs_describefeaturetype, wfs_getfeature, wfs_transaction, fees, ";
		$sql .= "accessconstraints, ";
		$sql .= "individualname , positionname , providername , ";
		$sql .= "city , deliverypoint , administrativearea , ";
		$sql .= "postalcode , voice , facsimile , ";
		$sql .= "electronicmailaddress , country , ";
 		$sql .= "wfs_owner, wfs_timestamp, wfs_timestamp_create, uuid, wfs_username, wfs_password, wfs_auth_type, wfs_owsproxy, wfs_alternate_title) ";
		$sql .= "VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14 ,$15, $16, $17, $18, $19, $20, $21, $22, $23, $24, $25, $26, $27, $28, $29, $30, $31, $32)";
		if (!($aWfs->auth)) {
			$aWfs->auth['username'] = "";
			$aWfs->auth['password'] = "";
			$aWfs->auth['auth_type'] = "";
			$wfs_owsproxy = '';
		}
		if ($aWfs->auth['username'] != '' && $aWfs->auth['password'] != '' && $aWfs->auth['auth_type'] != '') {
			//set initial wfs_owsproxy hash
			$wfs_owsproxy = md5(microtime(1));
		}
		$wfsOwner = Mapbender::session()->get("mb_user_id");
		if ($owner !== false) {
			$wfsOwner = $owner;
		}
		$v = array(
			$aWfs->getVersion(), 
			$aWfs->name, 
			$aWfs->title, 
			$aWfs->summary, 
			$aWfs->getCapabilities, 
			$aWfs->getCapabilitiesDoc,
			$aWfs->uploadUrl, 
			$aWfs->describeFeatureType, 
			$aWfs->getFeature,
			$aWfs->transaction, 
			$aWfs->fees, 
			$aWfs->accessconstraints, 
			$aWfs->individualName,
			$aWfs->positionName,
			$aWfs->providerName,
			$aWfs->city,
			$aWfs->deliveryPoint,
			$aWfs->administrativeArea,
			$aWfs->postalCode,
			$aWfs->voice,
			$aWfs->facsimile,
			$aWfs->electronicMailAddress,
			$aWfs->country, 
			$wfsOwner, 
			strtotime("now"),
			strtotime("now"),
			$uuid,
			$aWfs->auth['username'],
			$aWfs->auth['password'],
			$aWfs->auth['auth_type'],
			$wfs_owsproxy,
		    $aWfs->alternate_title
		);
			
		$t = array('s', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 's', 'i', 'i','i','s','s','s','s','s','s');
	
		$res = db_prep_query($sql, $v, $t);
	
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS into database.");
			return false;
		}

		// set the WFS id
		$aWfs->id = db_insert_id($con, 'wfs', 'wfs_id');
		
		// Insert the WFS operations
		for ($i = 1; $i < count($aWfs->operationsArray); $i++) {
			$currentOp = $aWfs->operationsArray[$i];
			if (!WfsToDb::insertOperation($aWfs->id, $currentOp)) {
				db_rollback();
				return false;
			}
		}
		//TODO Insert outputFormats
		for ($i = 0; $i < count($aWfs->wfsOutputFormatArray); $i++) {
			$currentOutputFormat = $aWfs->wfsOutputFormatArray[$i];
			if (!WfsToDb::insertOutputFormat($aWfs->id, $currentOutputFormat)) {
				db_rollback();
				return false;
			}
		}

		// Insert the feature types
		for ($i = 0; $i < count($aWfs->featureTypeArray); $i++) {
			$currentFeatureType = $aWfs->featureTypeArray[$i];
			if (!WfsToDb::insertFeatureType($currentFeatureType)) {
				db_rollback();
				return false;
			}
		}
		db_commit();
		
		//This can only be done when WFS is already inserted into DB, because we need the db featuretype id for inserting
		// Insert the WFS StoredQueries as wfs confs into DB
		for ($i = 0; $i < count($aWfs->storedQueriesArray); $i++) {
			$currentStoredQuery = $aWfs->storedQueriesArray[$i];
			if (!WfsToDb::insertStoredQuery($aWfs->id, $currentStoredQuery)) {
				$e = new mb_exception("class_wfsToDb.php: StoredQuery could not be inserted into DB.");
			}
		}
		
		return true;		
	}
	
    /**
	 * Updates an existing WFS in the database.
	 *
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public static function update ($aWfs, $updateMetadataOnly=false) {
		//get some things out from database if not already given thru metadata editor: wfs_network_access
		//they don't come from the capabilities!
		if (!$updateMetadataOnly) {
			//read network_access from database
			$sql = "SELECT wfs_network_access, wfs_max_features, inspire_annual_requests, wfs_alternate_title from wfs WHERE wfs_id = $1 ";
			$v = array($aWfs->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			$row = db_fetch_assoc($res);
			$aWfs->wfs_network_access = $row["wfs_network_access"];
			$aWfs->wfs_max_features = $row["wfs_max_features"];
			$aWfs->inspire_annual_requests = $row["inspire_annual_requests"];
			$aWfs->alternate_title = $row["wfs_alternate_title"];
			//$e = new mb_notice("class_wms.php: wfs_network_access from database: ".$aWfs->wfs_network_access);
		}
		//if network access is either stored in database nor given thru object, set it too a default value 0
		if (!isset($aWfs->wfs_network_access) || ($aWfs->wfs_network_access == '')) {
			$aWfs->wfs_network_access = intval('0');
		}
		if (!($aWfs->auth)) {
			$aWfs->auth['username'] = "";
			$aWfs->auth['password'] = "";
			$aWfs->auth['auth_type'] = "";
		}
		//$e = new mb_exception("awfs_username: ".$aWfs->auth['username']);
		$admin = new administration();
		db_begin();
		if (is_null($aWfs->alternate_title)) {
		    $aWfs->alternate_title = "";
		}
		// update WFS
		if (!$updateMetadataOnly) {
			#Ticket 8490: wfs_license_source_note should not be updated by update without "overwrite"-flag (updateMetadaOnly)
			$e = new mb_notice("classes/class_wfsToDb.php: - function update - not from metadata editor");
			$sql = "UPDATE wfs SET wfs_version = $1, wfs_name = $2, wfs_getcapabilities = $3, wfs_getcapabilities_doc = $4, ";
			$sql .= "wfs_upload_url = $5, wfs_describefeaturetype = $6, wfs_getfeature = $7, ";
			$sql .= "wfs_transaction = $8, wfs_timestamp = $9, wfs_network_access = $10, fkey_mb_group_id = $11, ";
			$sql .=  "wfs_max_features = $12, inspire_annual_requests = $13, wfs_username = $14, wfs_password = $15, wfs_auth_type = $16, wfs_alternate_title = $18 ";
			$sql .= "WHERE wfs_id = $17";
			$v = array(
				$aWfs->getVersion(),
				$aWfs->name,
				$aWfs->getCapabilities,
				$aWfs->getCapabilitiesDoc,
				$aWfs->uploadUrl,
				$aWfs->describeFeatureType,
				$aWfs->getFeature,
				$aWfs->transaction,
				strtotime("now"),
				$aWfs->wfs_network_access,
				$aWfs->fkey_mb_group_id,
				$aWfs->wfs_max_features,
				$aWfs->inspire_annual_requests,
				$aWfs->auth['username'],
				$aWfs->auth['password'],
				$aWfs->auth['auth_type'],
				$aWfs->id,
			    $aWfs->alternate_title
			);
			$t = array('s','s','s','s','s','s','s','s','s','i','i','i','i','s','s','s','i','s','s');
			$e = new mb_notice("class_wfsToDb.php: UPDATING WFS " . $aWfs->id);
			$res = db_prep_query($sql, $v, $t);
			if (!$res) {
				$e = new mb_exception("Error while updating WFS in database.");
				db_rollback();
				return false;
			}
		} else {
			$e = new mb_exception("classes/class_wfsToDb.php: - function update - from metadata editor");
			//only update wfs elements that are given by metadata editor - noc technical things!
			$sql = "UPDATE wfs SET wfs_timestamp = $1, wfs_network_access = $2, fkey_mb_group_id = $3, ";
			$sql .=  "wfs_max_features = $4, inspire_annual_requests = $5, wfs_license_source_note = $7, wfs_alternate_title = $8 ";
			$sql .= "WHERE wfs_id = $6";
			$v = array(
				strtotime("now"),
				$aWfs->wfs_network_access,
				$aWfs->fkey_mb_group_id,
				$aWfs->wfs_max_features,
				$aWfs->inspire_annual_requests,
				$aWfs->id,
				$aWfs->wfs_license_source_note,
			    $aWfs->alternate_title
			);
			
			$t = array('s','i','i','i','i','i','s','s');
			$e = new mb_notice("class_wfsToDb.php: UPDATING WFS - metadata editor elements only - " . $aWfs->id);
			$res = db_prep_query($sql, $v, $t);
			if (!$res) {
				$e = new mb_exception("Error while updating WFS in database.");
				db_rollback();
				return false;
			}
		}
		//update following metadata only if intended - mapbender.conf
		if($aWfs->overwrite == true){
			$sql = "UPDATE wfs SET ";
			$sql .= "wfs_title  = $1 ,";
			$sql .= "wfs_abstract  = $2 ,";
			$sql .= "fees = $3, ";
			$sql .= "accessconstraints = $4, ";
			$sql .= "individualName = $5, ";
			$sql .= "positionname = $6, ";
			$sql .= "providername = $7, ";
			$sql .= "city = $8, ";
			$sql .= "deliverypoint = $9, ";
			$sql .= "administrativearea = $10, ";
			$sql .= "postalcode = $11, ";
			$sql .= "voice = $12, ";
			$sql .= "facsimile = $13, ";
			$sql .= "electronicmailaddress = $14, ";
			$sql .= "country = $15, ";
			$sql .= "wfs_network_access = $16, ";
			$sql .= "wfs_max_features = $17, ";
			$sql .= "fkey_mb_group_id = $18, ";
			$sql .= "wfs_license_source_note = $20, ";
			$sql .= "wfs_alternate_title = $21 ";
			$sql .= " WHERE wfs_id = $19";
			$v = array($aWfs->title,
					$aWfs->summary,
					$aWfs->fees,
					$aWfs->accessconstraints,
					$aWfs->individualName,
					$aWfs->positionName,
					$aWfs->providerName,
					$aWfs->city,
					$aWfs->deliveryPoint,
					$aWfs->administrativeArea,
					$aWfs->postalCode,
					$aWfs->voice,
					$aWfs->facsimile,
					$aWfs->electronicMailAddress,
					$aWfs->country,
					$aWfs->wfs_network_access,
					$aWfs->wfs_max_features,
					$aWfs->fkey_mb_group_id,
					$aWfs->id,
					$aWfs->wfs_license_source_note,
			        $aWfs->alternate_title
				);
			$t = array('s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','i','i','i','i','s','s');
			$res = db_prep_query($sql,$v,$t);
			if(!$res){
				db_rollback();
			}
		}

		// delete all metadata relations which come capabilities

        if (!$updateMetadataOnly) {
        		$sql = "DELETE FROM ows_relation_metadata WHERE fkey_featuretype_id IN " ;
        		$sql .= "(SELECT featuretype_id FROM wfs_featuretype WHERE fkey_wfs_id = $1)";
        		$sql .= " AND ows_relation_metadata.relation_type = 'capabilities'";
        		$v = array($aWfs->id);
        		$t = array("i");
        		$res = db_prep_query($sql,$v,$t);     
        		// delete and refill WFS operations
        		$sql = "DELETE FROM wfs_operation WHERE fkey_wfs_id = $1 ";
        		$v = array($aWfs->id);
        		$t = array('i');
        		$res = db_prep_query($sql,$v,$t);
        		if(!$res){
        			db_rollback();
        		}
        		for ($i = 0; $i < count($aWfs->operationsArray); $i++) {
        			$currentOp = $aWfs->operationsArray[$i];
        			if (!WfsToDb::insertOperation($aWfs->id, $currentOp)) {
        				db_rollback();
        				return false;
        			}
        		}
        }	
		// delete and refill WFS outputFormats
		$sql = "DELETE FROM wfs_output_formats WHERE fkey_wfs_id = $1 ";
		$v = array($aWfs->id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			db_rollback();
		}
		for ($i = 0; $i < count($aWfs->wfsOutputFormatArray); $i++) {
			$currentOutputFormat = $aWfs->wfsOutputFormatArray[$i];
			if (!WfsToDb::insertOutputFormat($aWfs->id, $currentOutputFormat)) {
				db_rollback();
				return false;
			}
		}

		if ($updateMetadataOnly) {
			# delete and refill wfs_termsofuse
			$sql = "DELETE FROM wfs_termsofuse WHERE fkey_wfs_id = $1 ";
			$v = array($aWfs->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			if(!$res){
				db_rollback();
			}
			WfsToDb::insertTermsOfUse($aWfs);
		}
	
		# update TABLE wfs_featuretype
		$oldFeatureTypeNameArray = array();
		$v = array($aWfs->id);
		$t = array('i');
		$c = 2;
		$sql = "SELECT featuretype_id, featuretype_name, featuretype_title, featuretype_abstract, inspire_download, featuretype_schema, featuretype_schema_problem FROM wfs_featuretype WHERE fkey_wfs_id = $1 AND NOT featuretype_name IN(";
		$e = new mb_notice("class_wfsToDb.php: WFS_UPDATE: count featuretypeArray: ".count($aWfs->featureTypeArray));	
		for($i=0; $i<count($aWfs->featureTypeArray); $i++){
			if($i>0){$sql .= ',';}
			$sql .= "$".$c;
			array_push($v,$aWfs->featureTypeArray[$i]->name);
			$e = new mb_notice("class_wfsToDb.php: WFS_UPDATE: old featuretype name added for read: ".$aWfs->featureTypeArray[$i]->name);
			array_push($t,'s');
			$c++;
		}
		$sql .= ")";
		$res = db_prep_query($sql,$v,$t);
		while ($row = db_fetch_array($res)) {
			$oldFeatureTypeNameArray[]= array(
				"id" => $row["featuretype_id"],
				"name" => $row["featuretype_name"],
				"title" => $row["featuretype_title"],
				"abstract" => $row["featuretype_abstract"]
				//"inspire_download" => $row["inspire_download"]
			);
		}
	
		$featureTypeNameArray = array();
	
		for ($i = 0; $i < count($aWfs->featureTypeArray); $i++) {
			$currentFeatureType = $aWfs->featureTypeArray[$i];
			array_push($featureTypeNameArray, $currentFeatureType);
			if (WfsToDb::featureTypeExists($currentFeatureType)) {
				// update existing WFS feature types
				$e = new mb_notice("class_wfsToDb.php: class_wfsToDb.php: FT exists");
				if (!WfsToDb::updateFeatureType($currentFeatureType,$updateMetadataOnly,$aWfs->overwrite)) {
					db_rollback();
					return false;
				}
			}
			else {
				$e = new mb_notice("class_wfsToDb.php: FT ne pas exists");
				// insert new feature types
				if (!WfsToDb::insertFeatureType($currentFeatureType)) {
					db_rollback();
					return false;
				}
			}
		}
	
		// delete obsolete WFS feature types
		$v = array($aWfs->id);
		$t = array("i");
		$sql = "DELETE FROM wfs_featuretype WHERE fkey_wfs_id = $1";
			$sql_in = "";
			for ($i = 0; $i < count($featureTypeNameArray); $i++) {
					if ($i > 0) {
					$sql_in .= ", ";
			}
			$sql_in .= "$" . ($i+2);
			array_push($v, $featureTypeNameArray[$i]->name);
			array_push($t, "s");
		}
		if ($sql_in !== "") {
			$sql .=  " AND featuretype_name NOT IN (" . $sql_in . ")";
		}
	
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("Error while deleting obsolete WFS feature types in database.");
			db_rollback();
			return false;
		}
		$e = new mb_notice("class_wfsToDb.php: Number of featuretypes not to delete: ".count($featureTypeNameArray));
		
		//if WFS has storedQueries, check them for insert and update
		if(count($aWfs->storedQueriesArray) > 0) {
			if (!$updateMetadataOnly) {
				$storedQueryIdArray = array();
				for ($i = 0; $i < count($aWfs->storedQueriesArray); $i++) {
					$currentStoredQuery = $aWfs->storedQueriesArray[$i];
					array_push($storedQueryIdArray, $currentStoredQuery);
					$storedQueryWfsConfId = WfsToDb::storedQueryExists($aWfs->id, $currentStoredQuery->description['Id']);
					if ($storedQueryWfsConfId && $storedQueryWfsConfId != "") {
						// update existing WFS stored query
						$e = new mb_notice("class_wfsToDb.php: Stored query exists - try to update");
						if (!WfsToDb::updateStoredQuery($storedQueryWfsConfId, $currentStoredQuery)) {
							//db_rollback();
							//return false;
						    $e = new mb_exception("class_wfsToDb.php: Stored query " . $currentStoredQuery->description['Id'] . " could not be updated - check configuration of wfs!");
						}
					} else {
						$e = new mb_exception("class_wfsToDb.php: Stored query ne pas exists - try to insert");
						// insert new WFS stored query
						if (!WfsToDb::insertStoredQuery($aWfs->id, $currentStoredQuery)) {
							//db_rollback();
							//return false;
						    $e = new mb_notice("class_wfsToDb.php: Stored query " . $currentStoredQuery->description['Id'] . " could not be inserted - check configuration of wfs!");
						}
					}
				}	
				// delete obsolete WFS stored queries
				$v = array($aWfs->id);
				$t = array("i");
				$sql = "DELETE FROM wfs_conf WHERE fkey_wfs_id = $1";
				$sql_in = "";
				for ($i = 0; $i < count($storedQueryIdArray); $i++) {
					if ($i > 0) {
						$sql_in .= ", ";
					}
					$sql_in .= "$" . ($i+2);
					array_push($v, $storedQueryIdArray[$i]->description['Id']);
					array_push($t, "s");
				}
				if ($sql_in !== "") {
					$sql .=  " AND stored_query_id NOT IN (" . $sql_in . ")";
				}
				$res = db_prep_query($sql,$v,$t);
				if (!$res) {
					$e = new mb_exception("Error while deleting obsolete WFS stored queries in database.");
					db_rollback();
					return false;
				}
				$e = new mb_notice("class_wfsToDb.php: Number of stored queries not to delete: ".count($storedQueryIdArray));
			}
		}			
		db_commit();
		return true;
	}
	
	
	/**
	 * Checks if a WFS exists in the database. 
	 * 
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public static function exists ($aWfs) {
		// temporary WFS do not have a numeric ID
		if (!is_numeric($aWfs->id)) {
			return false;
		}

		// if ID is numeric, check if it exists in the database
		$sql = "SELECT * FROM wfs WHERE wfs_id = $1;";
		$v = array($aWfs->id);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);	
		if ($row = db_fetch_array($res)) {
			return true;
		}
		return false;
	}
	
	/**
	 * Delete a WFS from the database. Also sets the WFS object to null.
	 * 
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public static function delete ($aWfs) {
		//first delete coupled metadata, cause there is no contraints in the database to do so
		$e = new mb_notice("Deleting MetadataURLs for wfs with id :".$aWfs->id);
		//WfsToDb::deleteFeatureTypeMetadataUrls($aWfs->id); //Not needed any more, cause the relations are deleted thru class_iso19139.php
		//then delete wfs itself
		$sql = "DELETE FROM wfs WHERE wfs_id = $1";
		$v = array($aWfs->id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if ($res) {
			$aWfs = null;
			return true;
		}
		return false;
	}
	
	/**
	 * Inserts the terms of use for a WFS in the database.
	 * 
	 * @return Boolean
	 * @param $aWfs Wfs
	 */
	public static function insertTermsOfUse ($aWfs) {
		if (!is_numeric($aWfs->wfs_termsofuse)) {
			return;
		}
		$sql ="INSERT INTO wfs_termsofuse (fkey_wfs_id, fkey_termsofuse_id) ";
		$sql .= " VALUES($1,$2)";
		$v = array($aWfs->id,$aWfs->wfs_termsofuse);
		$t = array('i','i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			$e = new mb_exception("Error while inserting WFS termsofuse into the database.");
			return false;
		}
		return true;
	}

	//-----------------------------------------------------------------------	
	//
	// PRIVATE
	//
	//-----------------------------------------------------------------------	
	
	private static function insertFeatureTypeNamespace ($aWfsId, $aWfsFeatureTypeId, $aWfsFeatureTypeNamespace) {
	    // only insert the namespace, if it does not already exists
	    // check first
	    $sql_check = "SELECT * FROM wfs_featuretype_namespace WHERE fkey_wfs_id=$1 AND " .
	   	    "fkey_featuretype_id = $2 AND namespace=$3 AND namespace_location=$4 ";
	    
	    $v = array(
	        $aWfsId,
	        $aWfsFeatureTypeId,
	        $aWfsFeatureTypeNamespace->name,
	        $aWfsFeatureTypeNamespace->value
	    );
	    $e = new mb_exception("classes/class_wfsToDb.php: look for namespaces in db: " . json_encode($v) . " - sql: " . $sql_check );
	    $t = array("i", "i", "s", "s");
	    $res_check = db_prep_query($sql_check, $v, $t);
	    
	    if ($res_check) {
	        $ftNamepace = db_fetch_array($res_check);
	        //$e = new mb_exception("Found namespace: $".$ftNamepace['namespace']."$");
	        //if (count($ftNamepace) > 0 && $ftNamepace['namespace'] != "") {
	        if (count($ftNamepace) > 0 && $ftNamepace != false) {
	           //$e = new mb_exception("Count ftNamespace: ".count($ftNamepace));
	           $e = new mb_notice("Namespace ".$aWfsFeatureTypeNamespace->name." already exists for featuretype ".$aWfsFeatureTypeId." - will not be added twice!");
	           // TODO: check why namespace maybe redefined in case of some inspire wfs???
	           return true;
	        }
	    }
	    
		$sql = "INSERT INTO wfs_featuretype_namespace (fkey_wfs_id, " . 
				"fkey_featuretype_id, namespace, namespace_location) " . 
				"VALUES ($1, $2, $3, $4);"; 

		$v = array(
			$aWfsId, 
			$aWfsFeatureTypeId, 
			$aWfsFeatureTypeNamespace->name, 
			$aWfsFeatureTypeNamespace->value
		);
		$t = array("i", "i", "s", "s");
		$e = new mb_notice("class_wfsToDb.php: INSERTING Featuretype Namespace for WFS-ID $aWfsId, FT: $aWfsFeatureTypeId, NS: $aWfsFeatureTypeNamespace->name");
		$res = db_prep_query($sql, $v, $t);

		if (!$res) {
			$e = new mb_exception("Error while inserting WFS feature type namespace into the database.");
			return false;
		}
		return true;
	}


	/**
	 * Inserts a new WFS feature type crs into the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureTypeId Integer
	 * @param $aWfsFeatureTypeCrs String
	 */
	private static function insertFeatureTypeCrs ($aWfsFeatureTypeId, $aWfsFeatureTypeCrsString) {
		$sql = "INSERT INTO wfs_featuretype_epsg (fkey_featuretype_id, epsg) VALUES ($1, $2)";
		
		$v = array(
			$aWfsFeatureTypeId, 
			$aWfsFeatureTypeCrsString
		);
		$t = array("i", "s");
		
		$e = new mb_notice("class_wfsToDb.php: INSERTING Featuretype Crs (FT: $aWfsFeatureTypeId, Crs: $aWfsFeatureTypeCrsString");
		$res = db_prep_query($sql, $v, $t);
		
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS feature type crs into the database.");
			return false;
		}
		
		return true;
	}

	/**
	 * Inserts a new WFS feature type outputFormat into the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureTypeId Integer
	 * @param $aWfsFeatureTypeOutputFormat String
	 */
	private static function insertFeatureTypeOutputFormat ($aWfsFeatureTypeId, $aWfsFeatureTypeOutputFormatString) {
		$sql = "INSERT INTO wfs_featuretype_output_formats (fkey_featuretype_id, output_format) VALUES ($1, $2)";
		
		$v = array(
			$aWfsFeatureTypeId, 
			$aWfsFeatureTypeOutputFormatString
		);
		$t = array("i", "s");
		
		$e = new mb_notice("class_wfsToDb.php: INSERTING Featuretype outputFormat (FT: $aWfsFeatureTypeId, outputFormat: $aWfsFeatureTypeOutputFormatString");
		$res = db_prep_query($sql, $v, $t);
		
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS featuretype outputFormat into the database.");
			return false;
		}
		
		return true;
	}

	/**
	 * Inserts a new WFS feature type MetadataURL into the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureTypeId Integer
	 * @param $metadataUrl object
	 */
	private static function insertFeatureTypeMetadataUrl ($aWfsFeatureTypeId, $metadataUrl, $withParsing = false) {
		//function as defined in class wms!
		//origin 2 - set by mapbender metadata editor - new record
		//origin 3 - set by mapbender metadata editor - new linkage
		//harvest the record if some readable format is given - should this be adoptable?
		//parse the content if iso19139 is given
		//TODO: generate temporal uuid for inserting and getting the serial afterwards
		//delete old relations for this resource - only those which are from 'capabilities'
		$mbMetadata_1 = new Iso19139();
		$mbMetadata_1->deleteMetadataRelation("featuretype", $aWfsFeatureTypeId,"capabilities");
		//delete object?
		$mbMetadata = new Iso19139();
		$randomid = new Uuid();
		$mdOwner = Mapbender::session()->get("mb_user_id");
		$mbMetadata->randomId = $randomid;
		$mbMetadata->href = (string)$metadataUrl->href;
		$mbMetadata->format = $metadataUrl->format;
		$mbMetadata->type = $metadataUrl->type;
		$mbMetadata->origin = "capabilities";
		$mbMetadata->owner = $mdOwner;
		//following is not a good idea, but the call to $this->... makes problems?
		if (is_file(dirname(__FILE__) . "/../../conf/excludeHarvestMetadataUrls.json")) {
			$configObject = json_decode(file_get_contents("../../conf/excludeHarvestMetadataUrls.json"));
			//$e = new mb_exception("classes/class_WfsToDb.php: urlstoexclude from conf: ".json_encode($configObject->urls));
			$urlsToExclude = $configObject->urls;
		} else {
			$urlsToExclude = array();
		}
		$harvestMetadataUrl = true;
		foreach($urlsToExclude as $urlToExclude) {
			if (strpos($mbMetadata->href, $urlToExclude) !== false) {
				$e = new mb_exception("MetadataURL harvesting is excluded by conf!");
				$harvestMetadataUrl = false;
				break;
			}
		}
		if ($harvestMetadataUrl == true) {
			$result = $mbMetadata->insertToDB("featuretype",$aWfsFeatureTypeId, false, false, $harvestMetadataUrl);	
		}
		if ($result['value'] == false){
			$e = new mb_exception("Problem while storing metadata url from wfs to db");
			$e = new mb_exception($result['message']);
		} else {
			$e = new mb_notice("class_wfsToDb.php: Storing of metadata url from wfs to db was successful");
		}
		return true;
	}

	/**
	 * Inserts a new WFS feature type element into the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureTypeId Integer
	 * @param $aWfsFeatureTypeElement Object
	 */
	private static function insertFeatureTypeElement ($aWfsFeatureTypeId, $aWfsFeatureTypeElement) {
		$sql = "INSERT INTO wfs_element (fkey_featuretype_id, element_name, " . 
				"element_type) VALUES ($1, $2, $3)";
		
		$v = array(
			$aWfsFeatureTypeId, 
			$aWfsFeatureTypeElement->name, 
			$aWfsFeatureTypeElement->type
		);
		$t = array("i", "s", "s");
		
		$e = new mb_notice("class_wfsToDb.php: INSERTING Featuretype Element (FT: $aWfsFeatureTypeId, NS: $aWfsFeatureTypeElement->name");
		$res = db_prep_query($sql, $v, $t);
		
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS feature type element into the database.");
			return false;
		}
		
		// set the WFS feature type element ID
		$aWfsFeatureTypeElement->id = db_insert_id("", "wfs_element", "element_id");

		//
		//
		//ADD THIS FEATURETYPE TO WFS CONFIGURATIONS THAT USE THIS FEATURETYPE
		//
		//
		$sql = "SELECT wfs_conf_id FROM wfs_conf WHERE fkey_featuretype_id = $1";
		$v = array($aWfsFeatureTypeId);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			// no configuration exists for this featuretype, 
			// which is fine
			$e = new mb_notice("class_wfsToDb.php: No WFS conf found for this featuretype (Couldn't insert new feature type element in wfs_conf_element!)");
			return true;
		}
		while ($row = db_fetch_array($res)) {
			$wfsConfId = $row["wfs_conf_id"];
			
			// check if wfs conf element exists for this
			// featuretype element
			$sqlConfElement = "SELECT COUNT(wfs_conf_element_id) AS cnt FROM " . 
				"wfs_conf_element AS a, wfs_element AS b " . 
				"WHERE a.f_id = b.element_id AND " .
				"b.element_id = $1 AND a.fkey_wfs_conf_id = $2";
			$v = array($aWfsFeatureTypeElement->id, $wfsConfId);
			$t = array("i", "i");
			$resConfElement = db_prep_query($sqlConfElement, $v, $t);
			$rowConfElement = db_fetch_array($resConfElement);
			$count = $rowConfElement["cnt"];
			if ($count === "0") {
				$e = new mb_notice("class_wfsToDb.php: Inserting this feature type element (" . 
					$aWfsFeatureTypeElement->id . ") into WFS conf ($wfsConfId)");
				
				// Insert featuretype element in wfs_conf_element
				$sqlInsertConfElement = "INSERT INTO wfs_conf_element ";
				$sqlInsertConfElement .= "(fkey_wfs_conf_id, f_id,f_search,f_pos, f_style_id,";
				$sqlInsertConfElement .= "f_toupper, f_label, f_label_id, f_show,";
				$sqlInsertConfElement .= "f_respos, f_form_element_html, f_edit,";
				$sqlInsertConfElement .= "f_mandatory, f_auth_varname, f_show_detail, f_operator,";
				$sqlInsertConfElement .= "f_detailpos, f_min_input) VALUES";

				$sqlInsertConfElement .= "($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18)";

				$v = array($wfsConfId, $aWfsFeatureTypeElement->id,0,0,0,0,'',0,0,0,'',0,0,'',0,'',0,0);
				$t = array("i", "i","i", "i","i", "i","s","i","i","i","s","i","i","s","i","s","i","i");

				$resInsertConfElement = db_prep_query($sqlInsertConfElement, $v, $t);
				if (!$res) {
					$e = new mb_exception("Couldn't insert new feature type element in wfs_conf_element!");
					return false;
				}
			}
			else {
				$e = new mb_notice("class_wfsToDb.php: This feature type element (" . 
					$aWfsFeatureTypeElement->id . ") already exists in WFS conf ($wfsConfId)");

			}
		}
		
		return true;
	}

	/**
	 * Updates an existing WFS feature type element in the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureTypeId Integer
	 * @param $aWfsFeatureTypeElement Object
	 */
	private static function updateFeatureTypeElement ($aWfsFeatureTypeId, $aWfsFeatureTypeElement) {
		$sql = "UPDATE wfs_element SET element_type = $1 " . 
				"WHERE element_id = $2 AND fkey_featuretype_id = $3";

		$v = array(
			$aWfsFeatureTypeElement->type, 
			$aWfsFeatureTypeElement->id, 
			$aWfsFeatureTypeId
		);
		$t = array("s", "i", "i");

		#$e = new mb_notice("class_wfsToDb.php: UPDATING FT EL (FT: $aWfsFeatureTypeId, NS: $aWfsFeatureTypeElement->name");
		$res = db_prep_query($sql, $v, $t);

		if (!$res) {
			$e = new mb_exception("Error while updating WFS feature type element in the database.");
			return false;
		}
		
		return true;
	}
	
	/**
	 * Inserts a new WFS operation into the database.
	 *
	 * @return Boolean
	 * @param $aWfsOperation
	 */
	private static function insertOperation ($aWfsId, $aWfsOperation) {
		$sql = "INSERT INTO wfs_operation (fkey_wfs_id, op_name, " .
				"op_http_get, op_http_post) " .
				"VALUES($1, $2, $3, $4)";
	
		$v = array(
				$aWfsId,
				$aWfsOperation->name,
				$aWfsOperation->httpGet,
				$aWfsOperation->httpPost
		);
		$t = array('i','s','s','s');
	
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS operation into database.");
			return false;
		}
	
		return true;
	}
	
	/**
	 * Inserts a new WFS outputFormat into the database.
	 *
	 * @return Boolean
	 * @param $aWfsOutputFormat string
	 */
	private static function insertOutputFormat ($aWfsId, $aWfsOutputFormat) {
		$sql = "INSERT INTO wfs_output_formats (fkey_wfs_id, output_format) " .
				"VALUES($1, $2)";
	
		$v = array(
				$aWfsId,
				$aWfsOutputFormat
		);
		$t = array('i','s');
	
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS outputFormat into database.");
			return false;
		}
	
		return true;
	}


	/**
	 * Inserts a new WFS StoredQuery as wfs conf into the database.
	 *
	 * @return Boolean
	 * @param $aWfsStoredQuery 
	 */
	private static function insertStoredQuery ($aWfsId, $aWfsStoredQuery) {
		$wfsFtName = $aWfsStoredQuery->description['QueryExpressionText']['returnFeatureTypes'];
		//there may be more than one featuretype in the result. It is depending on the schema which is used. E.g. INSPIRE schemas often give back more than one featuretype. ps:ProtectedSites also may include gn:GeographicalName. Both are included in the returned result of a ListStoredQuery request.
		//the QueryExpressionText attribute returnFeatureTypes is extracted
		//check if blank is given in string
		if (strpos($wfsFtName, ' ') === false) {
			$wfsFtNameArray = array($wfsFtName);
		} else {
			$wfsFtNameArray = explode(' ', $wfsFtName);
		}
		//$typeReturnFT = gettype($wfsFtNameArray);
		//$e = new mb_exception($typeReturnFT);
		//$e = new mb_exception(json_encode($wfsFtNameArray));
		//$e = new mb_exception(json_encode($aWfsStoredQuery->returnFeaturetype));
		//try to read featuretype from ListStoredQuery Response
		if($wfsFtName == "") {
			$wfsFtNameArray = $aWfsStoredQuery->returnFeaturetype;
		}
		//if returnFeaturetype is not defined for storedQuery check for default storedQuery urn:ogc:def:query:OGC-WFS::GetFeatureById 
		if($wfsFtName == "") {
			if($aWfsStoredQuery->description['Id'] == 'urn:ogc:def:query:OGC-WFS::GetFeatureById') {
				//insert this default stored query for every existing featuretype
				$sql = "SELECT featuretype_id, featuretype_name FROM wfs_featuretype WHERE fkey_wfs_id = $1;";
				$v = array($aWfsId);
				$t = array("i");
				$res = db_prep_query($sql, $v, $t);
				if (!$res) {
					$e = new mb_exception("class_wfsToDb.php: Error getting related featuretype_id from DB.");
					return false;
				}
				while ($row = db_fetch_array($res)) {
					$ftId = $row["featuretype_id"];
					if($ftId && $ftId != "") {
						//insert this stored query as new wfs conf
						$insertWfsConf = WfsToDb::insertStoredQueryAsWfsConf($aWfsId, $aWfsStoredQuery, $ftId, $row["featuretype_name"]);
						return $insertWfsConf;
					}
				}
				
			}
			else {
				$e = new mb_exception("class_wfsToDb.php: StoredQuery ".$aWfsStoredQuery->description['Id']." does not have a  returnFeaturetype, cannot be matched with featuretype.");
				return false;
			}
		}
		else {
			//get Featuretype IDs for exposed featuretypes from capabilities using the returnFeaturetype names
			$wfsFtNameArray;
			$v = array();
			$t = array();
			$sql = "SELECT featuretype_id FROM wfs_featuretype WHERE fkey_wfs_id = $1 AND featuretype_name IN (";
			$v[] = $aWfsId;
			$t[] = "i";
			$k = 2;
			foreach($wfsFtNameArray as $wfsFtName) {
				$sql .= "$".$k.",";
				$v[] = $wfsFtName;
				$t[] = "s";
				$k++;
			}
			$sql = rtrim($sql, ",");
			$sql .= ")";
			$res = db_prep_query($sql, $v, $t);
			if (!$res) {
				$e = new mb_exception("class_wfsToDb.php: Error getting featuretype_id(s) from DB.");
				return false;
			}
			//maybe there are more than one featuretype
			while ($row = db_fetch_array($res)){
				$ftId = $row["featuretype_id"];
				if($ftId && $ftId != "") {
					//insert this stored query as new wfs conf
					$insertWfsConf = WfsToDb::insertStoredQueryAsWfsConf($aWfsId, $aWfsStoredQuery, $ftId);
					return $insertWfsConf;
				}	
			}
		}
	}
	
	/**
	 * Updates a WFS StoredQuery as wfs conf into the database.
	 *
	 * @return Boolean
	 * @param $aWfsConfId
	 * @param $aWfsStoredQuery
	 */
	private static function updateStoredQuery ($aWfsConfId, $aWfsStoredQuery) {
		// delete all query params of this WFS stored query
		$sql = "DELETE FROM wfs_stored_query_params WHERE ";
		$sql .= "fkey_wfs_conf_id = $1;";
		$v = array($aWfsConfId);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("Error while deleting WFS stored query params from the database.");
			return false;
		}
		
		if(gettype($aWfsStoredQuery->description['Parameter'][0]) == "array") {
			for ($i = 0; $i < count($aWfsStoredQuery->description['Parameter']); $i++) {
				$param = $aWfsStoredQuery->description['Parameter'][$i];
			
				$name = $param['name'];
				$type = $param['type'];
			
				$sql = "INSERT INTO wfs_stored_query_params (
						fkey_wfs_conf_id,
						stored_query_id,
						query_param_name,
						query_param_type
					) VALUES (
					$1, $2, $3, $4);";
				$v = array(
					$aWfsConfId, $aWfsStoredQuery->description['Id'], $param['name'], $param['type']
				);
				$t = array(
					"i", "s", "s", "s"
				);
				$res = db_prep_query($sql, $v, $t);
				if (!$res) {
					$e = new mb_exception("class_wfsToDb.php: StoredQuery Params Insert failed.");
					db_rollback();
					return false;
				}
			}
		}
		else {
			$param = $aWfsStoredQuery->description['Parameter'];
	
			if($param) {
				$name = $param['name'];
				$type = $param['type'];
			
				$sql = "INSERT INTO wfs_stored_query_params (
					fkey_wfs_conf_id,
					stored_query_id,
					query_param_name,
							query_param_type
					) VALUES (
						$1, $2, $3, $4);";
				$v = array(
					$aWfsConfId, $aWfsStoredQuery->description['Id'], $param['name'], $param['type']
				);
				$t = array(
					"i", "s", "s", "s"
				);
				$res = db_prep_query($sql, $v, $t);
				if (!$res) {
					$e = new mb_exception("class_wfsToDb.php: StoredQuery Params Insert failed.");
					db_rollback();
					return false;
				}
			}
		}
			
		return true;
	}
	
	private static function insertStoredQueryAsWfsConf ($aWfsId, $aWfsStoredQuery, $aFtId, $aFtName=null) {
		if($aWfsStoredQuery->description['Title'] && $aWfsStoredQuery->description['Title'] != "") {
			$title = $aWfsStoredQuery->description['Title']." ".$aFtName;
		}
		else {
			$title = $aWfsStoredQuery->description['Id']." ".$aFtName;
		}
			
		db_begin();
			
		$style = "body{
					 font-family:Verdana,Arial,sans-serif;
					 font-size: 12px;
					 line-height:2;
					}
		
					.a{
					 font-weight:bold;
					}
					.b{
					 font-family:Verdana,Arial,sans-serif;
					 font-size: 12px;
					 width:40px;
					}
		
					.c{
					 width:100px;
					}
		
					.d{
					 color:#808080;
				}";
		
		$sql = "INSERT INTO wfs_conf (
					wfs_conf_abstract,
					fkey_wfs_id,
					fkey_featuretype_id,
					g_label,
					g_button,
					wfs_conf_description,
					wfs_conf_type,
					stored_query_id,
					g_style,
					g_label_id,
					g_button_id
				) VALUES (
					$1, $2, $3, $4, 'OK', $5, 0, $6, $7, 'a', 'b');";
		$v = array(
				$title, $aWfsId, $aFtId,
				$aWfsStoredQuery->description['Id']." ".$aFtName, $title,
				$aWfsStoredQuery->description['Id'],
				$style
		);
		$t = array(
				"s", "i", "i", "s", "s", "s", "s"
		);
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("class_wfsToDb.php: StoredQuery Insert as WFS Conf failed.");
			db_rollback();
			return false;
		}
			
		//
		// get ID of this WFS conf
		//
		$sql = "SELECT max(wfs_conf_id) AS max_id FROM wfs_conf";
		$res = db_query($sql);
		if (!$res) {
			db_rollback();
			return false;
		}
		$row = db_fetch_array($res);
		$id = $row["max_id"];
		if (!$id) {
			db_rollback();
			return false;
		}
			
		if(gettype($aWfsStoredQuery->description['Parameter'][0]) == "array") {
			for ($i = 0; $i < count($aWfsStoredQuery->description['Parameter']); $i++) {
				$param = $aWfsStoredQuery->description['Parameter'][$i];
			
				$name = $param['name'];
				$type = $param['type'];
			
				$sql = "INSERT INTO wfs_stored_query_params (
						fkey_wfs_conf_id,
						stored_query_id,
						query_param_name,
						query_param_type
					) VALUES (
					$1, $2, $3, $4);";
				$v = array(
					$id, $aWfsStoredQuery->description['Id'], $param['name'], $param['type']
				);
				$t = array(
					"i", "s", "s", "s"
				);
				$res = db_prep_query($sql, $v, $t);
				if (!$res) {
					$e = new mb_exception("class_wfsToDb.php: StoredQuery Params Insert failed.");
					db_rollback();
					return false;
				}
			}
		}
		else {
			$param = $aWfsStoredQuery->description['Parameter'];
	
			if($param) {
				$name = $param['name'];
				$type = $param['type'];
			
				$sql = "INSERT INTO wfs_stored_query_params (
					fkey_wfs_conf_id,
					stored_query_id,
					query_param_name,
							query_param_type
					) VALUES (
						$1, $2, $3, $4);";
				$v = array(
					$id, $aWfsStoredQuery->description['Id'], $param['name'], $param['type']
				);
				$t = array(
					"i", "s", "s", "s"
				);
				$res = db_prep_query($sql, $v, $t);
				if (!$res) {
					$e = new mb_exception("class_wfsToDb.php: StoredQuery Params Insert failed.");
					db_rollback();
					return false;
				}
			}
		}
			
		//build wfs conf element object
		$sql = "SELECT * FROM wfs_element WHERE fkey_featuretype_id = $1 ORDER BY element_id";
		$v = array($aFtId);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		$cnt = 1;
		$featuretypeElementArray = array();
		while ($row = db_fetch_array($res)){
			$e = new mb_notice("class_wfsToDb.php: Inserting this feature type element (" .
										$aFtId . ") into WFS conf ($id)");
			
			//try to find the geom attr for insert
			$geomCheckArray = array("MultiPolygonPropertyType",
									"GeometryPropertyType",
									"MultiSurfacePropertyType",
									"PolygonPropertyType",
									"GeometryPropertyType",
									"SurfacePropertyType",
									"MultiLineStringPropertyType",
									"GeometryPropertyType",
									"MultiCurvePropertyType",
									"LineStringPropertyType",
									"GeometryPropertyType",
									"CurvePropertyType",
									"PointPropertyType",
									"MultiPointPropertyType"
			);
			if(in_array($row["element_type"], $geomCheckArray)) {
				$geomAttr = 1;
			}
			else {
				$geomAttr = 0;
			}
			
			// Insert featuretype element in wfs_conf_element
			$sqlInsertConfElement = "INSERT INTO wfs_conf_element ";
			$sqlInsertConfElement .= "(fkey_wfs_conf_id, f_id,f_search,f_pos, f_style_id,";
			$sqlInsertConfElement .= "f_toupper, f_label, f_label_id, f_show,";
			$sqlInsertConfElement .= "f_respos, f_form_element_html, f_edit,";
			$sqlInsertConfElement .= "f_mandatory, f_auth_varname, f_show_detail, f_operator,";
			$sqlInsertConfElement .= "f_detailpos, f_min_input, f_geom) VALUES";
			$sqlInsertConfElement .= "($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19)";
			
			$v = array($id,$row["element_id"],0,$cnt,"c",0,$row["element_name"].": ","d",1,$cnt,"",0,0,"",0,"","0","0",$geomAttr);
			$t = array("i", "i","i", "s","s", "i","s","s","i","s","s","i","i","s","i","s","s","s","i");
			
			$resInsertConfElement = db_prep_query($sqlInsertConfElement, $v, $t);
			if (!$res) {
				$e = new mb_exception("Couldn't insert new feature type element in wfs_conf_element!");
				return false;
			}
			
			$cnt++;
		}
			
		db_commit();
			
		return true;
	}
	

	/**
	 * Inserts a new WFS feature type into the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureType WfsFeatureType
	 */
	private static function insertFeatureType ($aWfsFeatureType) {
	    //$e = new mb_exception('classes/class_wfsToDb.php: insertFeaturetype: name: ' . $aWfsFeatureType->name);
		$uuid = new Uuid();
		$sql = "INSERT INTO wfs_featuretype (fkey_wfs_id, featuretype_name, " . 
				"featuretype_title, featuretype_abstract, featuretype_searchable, featuretype_srs, featuretype_latlon_bbox, uuid, inspire_download, featuretype_schema, featuretype_schema_problem) " . 
				"VALUES($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

		$v = array(
			$aWfsFeatureType->wfs->id,
			$aWfsFeatureType->name,
			$aWfsFeatureType->title,
			$aWfsFeatureType->summary,
			1, //default to allow search for a inserted featuretype (searchable)
			$aWfsFeatureType->srs,
			$aWfsFeatureType->latLonBboxArray['minx'].','.$aWfsFeatureType->latLonBboxArray['miny'].','.$aWfsFeatureType->latLonBboxArray['maxx'].','.$aWfsFeatureType->latLonBboxArray['maxy'],
			$uuid,
			0, //default not generate a INSPIRE Download Feed
			$aWfsFeatureType->schema,
			$aWfsFeatureType->schema_problem
		);
		$t = array('i','s','s','s','i','s','s','s','i','s','b');

		#$e = new mb_notice("class_wfsToDb.php: INSERTING Featuretype (FT: $aWfsFeatureType->name)");
		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("Error while inserting WFS feature type into database.");
			return false;	
		}

		// save the id of each featuretype
		$aWfsFeatureType->id = db_insert_id("", "wfs_featuretype", "featuretype_id");

		// insert feature type elements
		for ($i = 0; $i < count($aWfsFeatureType->elementArray); $i++) {
			$element = $aWfsFeatureType->elementArray[$i];
			if (!WfsToDb::insertFeatureTypeElement($aWfsFeatureType->id, $element)) {
				return false;	
			}
		}

		// insert feature type crs
		for ($i = 0; $i < count($aWfsFeatureType->crsArray); $i++) {
			$crs = $aWfsFeatureType->crsArray[$i];
			if (!WfsToDb::insertFeatureTypeCrs($aWfsFeatureType->id, $crs)) {
				return false;	
			}
		}

		// insert feature type outputFormats
		for ($i = 0; $i < count($aWfsFeatureType->featuretypeOutputFormatArray); $i++) {
			$outputFormat = $aWfsFeatureType->featuretypeOutputFormatArray[$i];
			if (!WfsToDb::insertFeatureTypeOutputFormat($aWfsFeatureType->id, $outputFormat)) {
				return false;	
			}
		}

		// insert feature type MetadataURL
		$e = new mb_notice("Number of metadataurls for featuretype ".$aWfsFeatureType->id." : ".count($aWfsFeatureType->metadataUrlArray));
		for ($i = 0; $i < count($aWfsFeatureType->metadataUrlArray); $i++) {
			$metadataUrl = $aWfsFeatureType->metadataUrlArray[$i];
			if (!WfsToDb::insertFeatureTypeMetadataUrl($aWfsFeatureType->id, $metadataUrl)) {
				return false;	
			}
		}
		// insert feature type namespaces
		for ($i = 0; $i < count($aWfsFeatureType->namespaceArray); $i++) {
			$namespace = $aWfsFeatureType->namespaceArray[$i];
			if (!WfsToDb::insertFeatureTypeNamespace($aWfsFeatureType->wfs->id, $aWfsFeatureType->id, $namespace)) {
				return false;
			}
		}
						
		return true;
	}
	
	/**
	 * Updates an existing WFS feature type in the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureType WfsFeatureType
	 */
	private static function updateFeatureType ($aWfsFeatureType, $updateMetadataOnly=false, $overwrite) {
		$aWfsFeatureType->id = WfsToDb::getFeatureTypeId($aWfsFeatureType);
		
		$sql = "SELECT featuretype_id, featuretype_searchable, inspire_download FROM wfs_featuretype WHERE fkey_wfs_id = $1 AND featuretype_name = $2";
		$v = array($aWfsFeatureType->wfs->id,$aWfsFeatureType->name);
		$t = array('i','s');
		$res = db_prep_query($sql,$v,$t);
		if($row = db_fetch_array($res)){
			$ft_id = $row['featuretype_id'];
		}
		else{
			db_rollback();
			$e = new mb_exception("Not found: ".$aWfsFeatureType->name);
			return;
		}

		//don't update title, abstract when not explicitly demanded thru $overwrite == true
		$sql = "UPDATE wfs_featuretype SET ";
		$sql .= "featuretype_searchable = $1,";
		$sql .= "featuretype_srs = $2, ";
		$sql .= "featuretype_latlon_bbox = $3, ";
		$sql .= "inspire_download = $4, ";
		$sql .= "featuretype_schema = $6, ";
		$sql .= "featuretype_schema_problem = $7 ";
		$sql .= "WHERE featuretype_id = $5";
		
		
		if (!$updateMetadataOnly) {
			//read inspire_download and featuretype_searchable from database if update from capabilities
			$aWfsFeatureType->inspire_download = $row["inspire_download"];
			$aWfsFeatureType->searchable = $row["featuretype_searchable"];
		}
		//if inspire_download, and featuretype_searchable is neither stored in database nor given thru object, set it too a default value 0
		if (!isset($aWfsFeatureType->inspire_download) || ($aWfsFeatureType->inspire_download =='')) {
			$aWfsFeatureType->inspire_download = intval('0');
		}
		if (!isset($aWfsFeatureType->searchable) || ($aWfsFeatureType->searchable =='')) {
			$aWfsFeatureType->searchable = intval('0');
		}
		
		$v = array(
			$aWfsFeatureType->searchable,
			$aWfsFeatureType->srs,
			$aWfsFeatureType->latLonBboxArray['minx'].','.$aWfsFeatureType->latLonBboxArray['miny'].','.$aWfsFeatureType->latLonBboxArray['maxx'].','.$aWfsFeatureType->latLonBboxArray['maxy'],
			$aWfsFeatureType->inspire_download,
			$aWfsFeatureType->id,
			$aWfsFeatureType->schema,
			$aWfsFeatureType->schema_problem
		);
		$t = array('s','s','s','i','i','s','b');

		$e = new mb_notice("class_wfsToDb.php: UPDATING Featuretype (FT: $aWfsFeatureType->id)");
		$e = new mb_notice("class_wfsToDb.php: UPDATING Featuretype (FT searchable: $aWfsFeatureType->searchable)");
		$e = new mb_notice("class_wfsToDb.php: UPDATING Featuretype (FT inspire_download: $aWfsFeatureType->inspire_download)");
		
		$res = db_prep_query($sql,$v,$t);
		
		if($overwrite){
			$e = new mb_notice("class_wfsToDb.php - overwrite has been activated");
			$sql = "UPDATE wfs_featuretype SET ";
			$sql .= "featuretype_title = $1, ";
			$sql .= "featuretype_abstract = $2 ";
			$sql .= "WHERE featuretype_id = $3";
				
			$v = array($aWfsFeatureType->title,$aWfsFeatureType->summary, $aWfsFeatureType->id);
			$t = array('s','s','i');
			$res = db_prep_query($sql,$v,$t);
		}
		
		if (!$res) {
			$e = new mb_exception("Error while updating WFS feature type in database.");
			return false;
		}
		
		// update existing WFS feature type elements
		$featureTypeElementNameArray = array();
		for ($i = 0; $i < count($aWfsFeatureType->elementArray); $i++) {
			$currentElement = $aWfsFeatureType->elementArray[$i];
			array_push($featureTypeElementNameArray, $currentElement);
			if (WfsToDb::featureTypeElementExists($aWfsFeatureType, $currentElement->name)) {
				if (!WfsToDb::updateFeatureTypeElement($aWfsFeatureType->id, $currentElement)) {
					return false;
				}
			}
			else {
				if (!WfsToDb::insertFeatureTypeElement($aWfsFeatureType->id, $currentElement)) {
					return false;
				}
			}
		}		
		
		// delete obsolete WFS featuretype elements
		$v = array($aWfsFeatureType->id);
		$t = array("i");
		$sql = "DELETE FROM wfs_element WHERE fkey_featuretype_id = $1";

		$sql_in = "";
		for ($i = 0; $i < count($featureTypeElementNameArray); $i++) {
			if ($i > 0) {
				$sql_in .= ", ";
			}
			$sql_in .= "$" . ($i+2);
			array_push($v, $featureTypeElementNameArray[$i]->name);
			array_push($t, "s");
		}
		if ($sql_in !== "")
		$sql .= " AND element_name NOT IN (" . $sql_in . ")";

		$res = db_prep_query($sql,$v,$t);
		if (!$res) {
			$e = new mb_exception("Error while deleting obsolete WFS featuretype element in database.");
			return false;
		}

		// delete all namespaces of this WFS feature type
		$sql = "DELETE FROM wfs_featuretype_namespace WHERE ";
		$sql .= "fkey_wfs_id = $1 AND fkey_featuretype_id = $2";
		$v = array(
			$aWfsFeatureType->wfs->id, 
			$aWfsFeatureType->id
		);
		$t = array("i", "i");
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("Error while deleting WFS feature type namespaces from the database.");
			return false;
		}		
		
		// insert feature type namespaces
		for ($i = 0; $i < count($aWfsFeatureType->namespaceArray); $i++) {
			$namespace = $aWfsFeatureType->namespaceArray[$i];
			if (!WfsToDb::insertFeatureTypeNamespace ($aWfsFeatureType->wfs->id, $aWfsFeatureType->id, $namespace)) {
				$e = new mb_exception("Error while inserting WFS feature type namespaces into the database.");
				return false;
			}
		}		
		
		// update categories for feature type
		if($overwrite){
			$types = array("md_topic", "inspire", "custom");
			foreach ($types as $cat) {
				$sql = "DELETE FROM wfs_featuretype_{$cat}_category WHERE fkey_featuretype_id = $1 AND fkey_metadata_id ISNULL";
				$v = array($aWfsFeatureType->id);
				$t = array('i');
				$res = db_prep_query($sql,$v,$t);
				if(!$res){
						$e = new mb_exception("Error while deleting old categories for WFS feature type in the database.");
						return false;
					}
			
				$attr = "featuretype_{$cat}_category_id";
				$k = $aWfsFeatureType->$attr;
			
				if($aWfsFeatureType->$attr && count($k) > 0) {
					for ($j = 0; $j < count($k); $j++) {
						if ($k[$j] != "") { 
							$sql = "INSERT INTO wfs_featuretype_{$cat}_category (fkey_featuretype_id, fkey_{$cat}_category_id) VALUES ($1, $2)";
							$v = array($aWfsFeatureType->id, $k[$j]);
							$t = array('i', 'i');
							$res = db_prep_query($sql,$v,$t);
							if(!$res){
								$e = new mb_exception("Error while inserting WFS feature type categories into the database.");
								return false;
							}
						}
					}
				}	
			}
		}
		//update CRS and MetadataURLs only if update is not started via Metadata Editor
		if (!$updateMetadataOnly) {
			//update CRS
			//delete supported CRS
			$sql = "DELETE FROM wfs_featuretype_epsg WHERE fkey_featuretype_id = $1";
			$v = array($aWfsFeatureType->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			//insert new supported CRS
			for ($i = 0; $i < count($aWfsFeatureType->crsArray); $i++) {
				$crs = $aWfsFeatureType->crsArray[$i];
				if (!WfsToDb::insertFeatureTypeCrs($aWfsFeatureType->id, $crs)) {
					return false;	
				}
			}
			//update MetadataURLs
			//delete old MetadataURLs from caps - TODO delete this, cause the new way is only to delete the relations and to hold the metadata entries
			/*$sql = <<<SQL

			DELETE FROM mb_metadata WHERE metadata_id IN (SELECT metadata_id FROM mb_metadata INNER JOIN (SELECT * from ows_relation_metadata WHERE fkey_featuretype_id = $1) as relation ON  mb_metadata.metadata_id = relation.fkey_metadata_id AND mb_metadata.origin = 'capabilities')

SQL;
			$v = array($aWfsFeatureType->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);*/
			// insert feature type MetadataURL from caps
			//$e = new mb_exception("metadataUrls: ".count($aWfsFeatureType->metadataUrlArray));
			for ($i = 0; $i < count($aWfsFeatureType->metadataUrlArray); $i++) {
				$metadataUrl = $aWfsFeatureType->metadataUrlArray[$i];
				//$e = new mb_exception("metadataUrl: ".json_encode($aWfsFeatureType->metadataUrlArray[$i]));
				if (!WfsToDb::insertFeatureTypeMetadataUrl($aWfsFeatureType->id, $metadataUrl)) {
					return false;	
				}
			}
			//delete and refill outputFormats
			$sql = "DELETE FROM wfs_featuretype_output_formats WHERE fkey_featuretype_id = $1";
			$v = array($aWfsFeatureType->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			//insert current outputFormats
			for ($i = 0; $i < count($aWfsFeatureType->featuretypeOutputFormatArray); $i++) {
				$outputFormat = $aWfsFeatureType->featuretypeOutputFormatArray[$i];
				if (!WfsToDb::insertFeatureTypeOutputFormat($aWfsFeatureType->id, $outputFormat)) {
					return false;	
				}
			}
		}
		if ($overwrite) {
			// update keywords
			$sql = "DELETE FROM wfs_featuretype_keyword WHERE fkey_featuretype_id = $1";
			$v = array($aWfsFeatureType->id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
		
			$k = $aWfsFeatureType->featuretype_keyword;

			for($j=0; $j<count($k); $j++){
				$keyword_id = "";
			
				while ($keyword_id == "") {
					$sql = "SELECT keyword_id FROM keyword WHERE UPPER(keyword) = UPPER($1)";
					$v = array($k[$j]);
					$t = array('s');
					$res = db_prep_query($sql,$v,$t);
					$row = db_fetch_array($res);
					//print_r($row);
					if ($row) {
						$keyword_id = $row["keyword_id"];
						$e = new mb_notice("class_wfsToDb.php: Keyword ".$k[$j]." already exists in table keyword in DB.");
					}
					else {
						$sql_insertKeyword = "INSERT INTO keyword (keyword)";
						$sql_insertKeyword .= "VALUES ($1)";
						$v1 = array($k[$j]);
						$t1 = array('s');
						$e = new mb_notice("class_wfsToDb.php: Inserting keyword ".$k[$j]." into table keyword in DB.");
						$res_insertKeyword = db_prep_query($sql_insertKeyword,$v1,$t1);
						if(!$res_insertKeyword){
							$e = new mb_exception("Error while inserting keywords into the database.");
							return false;	
						}
					}
				}

				// check if featuretype/keyword combination already exists
				$sql_fiKeywordExists = "SELECT * FROM wfs_featuretype_keyword WHERE fkey_featuretype_id = $1 AND fkey_keyword_id = $2";
				$v = array($aWfsFeatureType->id, $keyword_id);
				$t = array('i', 'i');
				$res_fiKeywordExists = db_prep_query($sql_fiKeywordExists, $v, $t);
				$row = db_fetch_array($res_fiKeywordExists);
				//print_r($row);
				if (!$row) {
					$sql1 = "INSERT INTO wfs_featuretype_keyword (fkey_keyword_id,fkey_featuretype_id)";
					$sql1 .= "VALUES ($1,$2)";
					$e = new mb_notice("class_wfsToDb.php: Inserting keyword id ".$keyword_id." for featuretype id ".$aWfsFeatureType->id." into DB.");
					$v1 = array($keyword_id,$aWfsFeatureType->id);
					$t1 = array('i','i');
					$res1 = db_prep_query($sql1,$v1,$t1);
					if(!$res1){
						$e = new mb_exception("Error while inserting wfs_featuretype_keywords into the database.");
						return false;
					}
				}
			}
		}
		//end of update keywords
		return true;		
	}
	
	/**
	 * Deletes an existing WFS feature type from the database.
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureType WfsFeatureType
	 */
	private static function deleteFeatureType ($aWfsFeatureType) {
		$sql = "DELETE FROM wfs_featuretype WHERE featuretype_id = $1 AND fkey_wfs_id = $2";
		$v = array($aWfsFeatureType->id, $aWfsFeatureType->wfs->id);
		$t = array('i', 'i');

		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("Error while deleting WFS feature type from database.");
			return false;
		}
		return true;
	}
	
	/**
	 * Deprecated: Deletes an implicitly coupled metadata of a wfs from the database.
	 * 
	 * 
	 * @return Boolean
	 * @param $wfsId integer
	 */
	private static function deleteFeatureTypeMetadataUrls ($wfsId) {
		$e = new mb_notice("class_wfsToDb.php: Deleting coupled WFS MetadataURLs from database.");
		$sql = <<<SQL

DELETE FROM mb_metadata WHERE metadata_id IN (SELECT metadata_id FROM mb_metadata INNER JOIN (SELECT * FROM ows_relation_metadata WHERE (internal IS NULL OR internal != 1) AND fkey_featuretype_id IN (SELECT fkey_featuretype_id FROM wfs_featuretype WHERE fkey_wfs_id = $1)) AS relation ON mb_metadata.metadata_id = relation.fkey_metadata_id WHERE mb_metadata.origin = 'capabilities')

SQL;
		$v = array($wfsId);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("Error while deleting coupled WFS MetadataURLs from database.");
			return false;
		}
		return true;
	}

	/**
	 * Checks if a featuretype exists in the database. It selects the rows
	 * that match the WFS id and the featuretype name.
	 * 
	 * If the featuretype is found the featuretype id is returned.
	 * 
	 * @return Integer
	 * @param $aWfsFeatureType WfsFeatureType
	 */
	private static function getFeatureTypeId ($aWfsFeatureType) {
		$sql = "SELECT featuretype_id FROM wfs_featuretype WHERE " . 
			"fkey_wfs_id = $1 AND featuretype_name = $2";
		$v = array(
			$aWfsFeatureType->wfs->id,
			$aWfsFeatureType->name
		);
		$t = array("i", "s");
		#$e = new mb_notice("class_wfsToDb.php: " .$sql . " " . print_r($v, true));
		$res = db_prep_query($sql, $v, $t);
		if ($row = db_fetch_array($res)) {
			return $row["featuretype_id"];
		}
		return null;
	}
	
	/**
	 * Checks if a featuretype exists in the database. 
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureType WfsFeatureType
	 */
	private static function featureTypeExists ($aWfsFeatureType) {
		if (WfsToDb::getFeatureTypeId($aWfsFeatureType) !== null) {
			return true;
		}
		return false;
	}
	
	/**
	 * Checks if a stored query exists in the database.
	 *
	 * @return Boolean
	 * @param $aWfsStoredQuery WfsStoredQuery
	 */
	private static function storedQueryExists ($aWfsId, $aWfsStoredQueryId) {
		$sql = "SELECT * FROM wfs_conf WHERE " .
				"fkey_wfs_id = $1 AND stored_query_id = $2";
		$v = array(
				$aWfsId,
				$aWfsStoredQueryId
		);
		$t = array("i", "s");
		$res = db_prep_query($sql, $v, $t);
		if ($row = db_fetch_array($res)) {
			return $row['wfs_conf_id'];
		}
		return false;
	}

	/**
	 * Gets the ID of a feature type element 
	 * 
	 * @return Integer
	 * @param $aWfsFeatureType WfsFeatureType
	 * @param $name WFS feature type element name
	 */
	private static function getFeatureTypeElementId ($aWfsFeatureType, $name) {
		$sql = "SELECT element_id FROM wfs_element WHERE " . 
			"fkey_featuretype_id = $1 AND element_name = $2";
		$v = array(
			$aWfsFeatureType->id,
			$name,
		);
		$t = array("i", "s");
		$res = db_prep_query($sql, $v, $t);
		if ($row = db_fetch_array($res)) {
			return $row["element_id"];
		}
		return null;
	}
	
	/**
	 * Checks if a featuretype element exists in the database. 
	 * 
	 * @return Boolean
	 * @param $aWfsFeatureType WfsFeatureType
	 * @param $name WFS feature type element name
	 */
	private static function featureTypeElementExists ($aWfsFeatureType, $name) {
		if (WfsToDb::getFeatureTypeElementId($aWfsFeatureType, $name) !== null) {
			return true;
		}
		return false;
	}
}
?>
