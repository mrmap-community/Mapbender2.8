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
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_ows_factory.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_featuretype.php");
/**
 * 
 * @return 
 * @param $xml String
 */
abstract class WfsFactory extends OwsFactory {
	
	/**
	 * Parses the capabilities document for the WFS 
	 * version number and returns it.
	 * 
	 * @return String
	 * @param $xml String
	 */
	private function getVersionFromXml ($xml) {

		$admin = new administration();
		$values = $admin->parseXml($xml);
		
		foreach ($values as $element) {
			if(strtoupper($element['tag']) == "WFS_CAPABILITIES" && $element['type'] == "open"){
				return $element['attributes'][version];
			}
		}
		throw new Exception("WFS version could not be determined from XML.");
	}

	protected function createFeatureTypeFromUrl () {
	}
	
	/**
	 * Retrieves the data of a WFS from the database and initiates the object.
	 *
	 * @return
	 * @param $id Integer
	 * @param $aWfs Wfs is being created by the subclass
	 */
	public function createFromDb ($id) {
		if (func_num_args() == 2) {
			$aWfs = func_get_arg(1);//set object to use given wfs object to be extented
		}
		else {
			return null;
		}
		/* 
		//From class_ows_factory.php : 
		if ($this->returnProxyUrls == true) {
			$e = new mb_exception("return proxy urls: true");
		}*/
		// WFS
		$sql = "SELECT * FROM wfs WHERE wfs_id = $1;";
		$v = array($id);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		$cnt = 0;
		while(db_fetch_row($res)){
			$hasOwsproxyUrl = false;
			$e = new mb_notice("class_wfs_factory: wfs_owsproxy: ".db_result($res, $cnt, "wfs_owsproxy"));
			if(db_result($res, $cnt, "wfs_owsproxy") != ''){
				$owsproxyUrl = OWSPROXY."/".session_id()."/".db_result($res, $cnt, "wfs_owsproxy")."?";
				$e = new mb_notice("class_wfs_factory: owsproxyURl: ".$owsproxyUrl);
				$hasOwsproxyUrl = true;
			}
	
			$aWfs->id = db_result($res, $cnt, "wfs_id");
			$aWfs->name = db_result($res, $cnt, "wfs_name");
			$aWfs->title = db_result($res, $cnt, "wfs_title");
			$aWfs->summary = db_result($res, $cnt, "wfs_abstract");
			$aWfs->electronicMailAddress = db_result($res, $cnt, "electronicmailaddress");
			$aWfs->providerName = db_result($res, $cnt, "providername");
			$aWfs->getCapabilities = db_result($res, $cnt, "wfs_getcapabilities");
			$aWfs->getCapabilitiesDoc = db_result($res, $cnt, "wfs_getcapabilities_doc");
			$aWfs->uploadUrl = db_result($res, $cnt, "wfs_upload_url");
			$aWfs->describeFeatureType = db_result($res, $cnt, "wfs_describefeaturetype");
			//TODO check why this switch is used and in which cases this is relevant
			if($this->returnProxyUrls == false){
				$aWfs->getFeature = db_result($res, $cnt, "wfs_getfeature");
				new mb_notice("class_wfs_factory.getFeature.url: don't use proxy");
			}
			else{
				$aWfs->getFeature = $owsproxyUrl;
			}
			new mb_notice("class_wfs_factory.getFeature.url: ".$aWfs->getFeature);
			if($this->returnProxyUrls == false){
				$aWfs->transaction = db_result($res, $cnt, "wfs_transaction");
			}
			else{
				$aWfs->transaction = $owsproxyUrl;
			}	
			$aWfs->fees = db_result($res, $cnt, "fees");
			$aWfs->accessconstraints = db_result($res, $cnt, "accessconstraints");
			$aWfs->owner = db_result($res, $cnt, "wfs_owner");
			$aWfs->timestamp = db_result($res, $cnt, "wfs_timestamp");
			$aWfs->timestamp_create = db_result($res, $cnt, "wfs_timestamp_create");
			$aWfs->wfs_network_access = db_result($res, $cnt, "wfs_network_access");
			$aWfs->wfs_max_features = db_result($res, $cnt, "wfs_max_features");
			$aWfs->inspire_annual_requests = db_result($res, $cnt, "inspire_annual_requests");
			$aWfs->fkey_mb_group_id = db_result($res, $cnt, "fkey_mb_group_id");
			$aWfs->wfs_license_source_note = db_result($res, $cnt, "wfs_license_source_note");
			$aWfs->uuid = db_result($res, $cnt, "uuid");
			if(db_result($res, $cnt, "wfs_username") != '' && db_result($res, $cnt, "wfs_auth_type") !='' && db_result($res, $cnt, "wfs_password") !=''){
				$e = new mb_notice("class_wfs_factory.php - createFromDb: wfs has all 3 auth strings not empty!");
				$aWfs->auth['username'] = db_result($res, $cnt, "wfs_username");
				$aWfs->auth['password'] = db_result($res, $cnt, "wfs_password");
				$aWfs->auth['auth_type'] = db_result($res, $cnt, "wfs_auth_type");
			} else {
				$aWfs->auth = false;
			}
			// pull outputFormats
			$sql_of = "SELECT * FROM wfs_output_formats WHERE fkey_wfs_id = $1";
			$v = array($aWfs->id);
			$t = array("i");
			$res_of = db_prep_query($sql_of, $v, $t);
			while($row = db_fetch_array($res_of)){
				$aWfs->wfsOutputFormatArray[] = $row["output_format"];
			}
			//pull stored query ids
			$sql_sq = "SELECT stored_query_id FROM wfs_conf WHERE fkey_wfs_id = $1";
			$v = array($aWfs->id);
			$t = array("i");
			$res_sq = db_prep_query($sql_sq, $v, $t);
			while($row = db_fetch_array($res_sq)){
			    $aWfs->storedQueriesArray[] = $row["stored_query_id"];
			}
			// Featuretypes
			$sql_fe = "SELECT * FROM wfs_featuretype WHERE fkey_wfs_id = $1 ORDER BY featuretype_id";
			$v = array($aWfs->id);
			$t = array("i");
			$res_fe = db_prep_query($sql_fe, $v, $t);
			$cnt_fe = 0;
			
			while($row = db_fetch_array($res_fe)){
				$this->featureTypeArray[count($this->featureTypeArray)] = new WfsFeatureType($aWfs);
				$fe_cnt = count($this->featureTypeArray)-1;
	
				$this->featureTypeArray[$fe_cnt]->uuid = $row["uuid"];
				$this->featureTypeArray[$fe_cnt]->id = $row["featuretype_id"];
	
				$this->featureTypeArray[$fe_cnt]->name = $row["featuretype_name"];
				$this->featureTypeArray[$fe_cnt]->title = $row["featuretype_title"];
				$this->featureTypeArray[$fe_cnt]->summary = $row["featuretype_abstract"];
				$this->featureTypeArray[$fe_cnt]->searchable = $row["featuretype_searchable"];
				$this->featureTypeArray[$fe_cnt]->inspire_download = $row["inspire_download"];
				$e = new mb_notice("class_wfs_factory: read from db FT inspire_download: ".$this->featureTypeArray[$fe_cnt]->inspire_download);
				$this->featureTypeArray[$fe_cnt]->srs = $row["featuretype_srs"];
				$latLonBbox = $row["featuretype_latlon_bbox"];
				$e = new mb_notice("class_wfs_factory: FT latlonbbox: ".$latLonBbox);
				$latLonBboxArray = explode(",", $latLonBbox);
				$this->featureTypeArray[$fe_cnt]->latLonBboxArray['minx'] = $latLonBboxArray[0];
				$this->featureTypeArray[$fe_cnt]->latLonBboxArray['miny'] = $latLonBboxArray[1];
				$this->featureTypeArray[$fe_cnt]->latLonBboxArray['maxx'] = $latLonBboxArray[2];
				$this->featureTypeArray[$fe_cnt]->latLonBboxArray['maxy'] = $latLonBboxArray[3];
	
	
				// Elements
				$sql_el = "SELECT * FROM wfs_element WHERE fkey_featuretype_id = $1 ORDER BY element_id";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array("i");
				$res_el = db_prep_query($sql_el, $v, $t);
				$cnt_el = 0;
				while(db_fetch_row($res_el)){
	
					$this->featureTypeArray[$fe_cnt]->addElement(
							db_result($res_el, $cnt_el, "element_name"),
							db_result($res_el, $cnt_el, "element_type"),
							db_result($res_el, $cnt_el, "element_id")
					);
					$cnt_el++;
				}
	
				// Crs
				$sql_crs = "SELECT epsg FROM wfs_featuretype_epsg WHERE fkey_featuretype_id = $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array("i");
				$res_crs = db_prep_query($sql_crs, $v, $t);
				$cnt_crs = 0;
				while(db_fetch_row($res_crs)){
	
					$this->featureTypeArray[$fe_cnt]->addCrs(
							db_result($res_crs, $cnt_crs, "epsg")
					);
					$cnt_crs++;
				}
				// outputFormats
				$sql_outputformats = "SELECT output_format FROM wfs_featuretype_output_formats WHERE fkey_featuretype_id = $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array("i");
				$res_outputformats = db_prep_query($sql_outputformats, $v, $t);
				$cnt_outputformats = 0;
				while(db_fetch_row($res_outputformats)){
					$this->featureTypeArray[$fe_cnt]->addOutputFormat(
							db_result($res_outputformats, $cnt_outputformats, "output_format")
					);
					$cnt_outputformats++;
				}
				
				//
				### read out keywords
				$sql = "SELECT keyword FROM keyword, wfs_featuretype_keyword WHERE keyword_id = fkey_keyword_id AND fkey_featuretype_id = $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array('i');
				$res_ft_keywords = db_prep_query($sql,$v,$t);
					
				$count_ft_keywords=0;
				while($row2 = db_fetch_array($res_ft_keywords)){
					$this->featureTypeArray[$fe_cnt]->featuretype_keyword[$count_ft_keywords]=$row2["keyword"];
					$count_ft_keywords++;
				}
				### read out wfs_featuretype_md_topic_category
				$sql = "SELECT fkey_md_topic_category_id FROM wfs_featuretype_md_topic_category WHERE fkey_featuretype_id =  $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array('i');
				$res_ft_md_topic_category = db_prep_query($sql,$v,$t);
								
				$count_ft_md_topic_category=0;
				while($row2 = db_fetch_array($res_ft_md_topic_category)){
					$this->featureTypeArray[$fe_cnt]->featuretype_md_topic_category_id[$count_ft_md_topic_category]=$row2["fkey_md_topic_category_id"];
					$count_ft_md_topic_category++;
				}
				### read out wfs_featuretype_inspire_category
				$sql = "SELECT fkey_inspire_category_id FROM wfs_featuretype_inspire_category WHERE fkey_featuretype_id =  $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array('i');
				$res_ft_inspire_category = db_prep_query($sql,$v,$t);
								
				$count_ft_inspire_category=0;
				while($row2 = db_fetch_array($res_ft_inspire_category)){
					$this->featureTypeArray[$fe_cnt]->featuretype_inspire_category_id[$count_ft_inspire_category]=$row2["fkey_inspire_category_id"];
					$count_ft_inspire_category++;
				}
				### read out wfs_featuretype_custom_category
				$sql = "SELECT fkey_custom_category_id FROM wfs_featuretype_custom_category WHERE fkey_featuretype_id =  $1";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array('i');
				$res_ft_custom_category = db_prep_query($sql,$v,$t);
								
				$count_ft_custom_category=0;
				while($row2 = db_fetch_array($res_ft_custom_category)){
					$this->featureTypeArray[$fe_cnt]->featuretype_custom_category_id[$count_ft_custom_category]=$row2["fkey_custom_category_id"];
					$count_ft_custom_category++;
				}
				
				// MetadataURLs
				$sql_metadata = "SELECT link, linktype, md_format FROM mb_metadata WHERE metadata_id IN ";
				$sql_metadata .= "(SELECT metadata_id FROM mb_metadata INNER JOIN (SELECT * from ows_relation_metadata WHERE ";
				$sql_metadata .= "fkey_featuretype_id = $1) as relation ON  mb_metadata.metadata_id = relation.fkey_metadata_id AND ";
				$sql_metadata .= "mb_metadata.origin = 'capabilities')";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array("i");
				$res_metadata = db_prep_query($sql_metadata, $v, $t);
				$cnt_metadata = 0;
				while(db_fetch_row($res_metadata)){
					$metadataURL->href = db_result($res_metadata, $cnt_metadata, "link");
					$metadataURL->type = db_result($res_metadata, $cnt_metadata, "linktype");
					$metadataURL->format = db_result($res_metadata, $cnt_metadata, "md_format");
					$this->featureTypeArray[$fe_cnt]->addMetadataUrl($metadataURL);
					$cnt_metadata++;
				}
	
				//Namespaces
				$sql_ns = "SELECT * FROM wfs_featuretype_namespace WHERE fkey_featuretype_id = $1 ORDER BY namespace";
				$v = array($this->featureTypeArray[$fe_cnt]->id);
				$t = array("i");
				$res_ns = db_prep_query($sql_ns, $v, $t);
				$cnt_ns = 0;
				while(db_fetch_row($res_ns)){
	
					$this->featureTypeArray[$fe_cnt]->addNamespace(
							db_result($res_ns, $cnt_ns, "namespace"),
							db_result($res_ns, $cnt_ns, "namespace_location")
					);
					$cnt_ns++;
				}
	
				$aWfs->addFeatureType($this->featureTypeArray[$fe_cnt]);
	
				$cnt_fe++;
			}
			$cnt++;
		}
		return $aWfs;
	}
}
?>
