<?php
# $Id: class_layer_monitor.php 10183 2019-07-14 06:30:27Z armin11 $
# http://www.mapbender.org/index.php/
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
require_once(dirname(__FILE__)."/class_metadata_monitor.php");

class Layer_load_count {

	function __construct () {
	}
	
	/**
	 * increments the load count in table "layer_load_count" for
	 * each layer in the WMC document by one. 
	 */
	function increment($layer_id) {
		if (!is_numeric($layer_id)) {
			return false;
		}

		//check if an entry exists for the current layer id
		$sql = "SELECT COUNT(layer_id) AS i FROM layer WHERE layer_id = $1";
		$v = array($layer_id);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);
		if (intval($row["i"]) === 0) {
			return false;
		}

		//check if an entry exists for the current layer id
		$sql = "SELECT load_count FROM layer_load_count WHERE fkey_layer_id = $1";
		$v = array($layer_id);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);

		//if yes, increment the load counter
		if ($row) {
			$currentCount = $row["load_count"];
			$sql = "UPDATE layer_load_count SET load_count = $1 WHERE fkey_layer_id = $2";
			$v = array(intval($currentCount + 1), $layer_id);
			$t = array('i', 'i');
			$res = db_prep_query($sql, $v, $t);
		}
		//if no, insert a new row with current layer id and load_count = 1
		else {
			$sql = "INSERT INTO layer_load_count (fkey_layer_id, load_count) VALUES ($1, 1)";
			$v = array($layer_id);
			$t = array('i');
			$res = db_prep_query($sql, $v, $t);
		}
	}
	//function to increment more than one layer with one sql statement
	function incrementMultiLayers($layerIdArray) {
		if (!is_array($layerIdArray)) {
			return false;
		}
		$layerIdString = implode(",",$layerIdArray);
		//check for existing entry in load count table, if not exist - insert zero value
		$sql = "SELECT fkey_layer_id FROM layer_load_count WHERE fkey_layer_id IN (".$layerIdString.")";
		$res = db_query($sql);
		$existingLayerIds = array();
		while($row = db_fetch_array($res)) {
			array_push($existingLayerIds, $row["fkey_layer_id"]);
		}
		//check for existing layers in layer table
		$sql = "SELECT layer_id FROM layer WHERE layer_id IN (".$layerIdString.")";
		$res = db_query($sql);
		$existingLayerIdsInLayer = array();
		while($row = db_fetch_array($res)) {
			array_push($existingLayerIdsInLayer, $row["layer_id"]);
		}
		//use only those who exists in layer table
		$layerIdArray = array_intersect($layerIdArray,$existingLayerIdsInLayer);
		//filter out those layers which are defined in mapbender.conf not to be counted
		if (DEFINED("LAYERS_EXCLUDED_FROM_COUNTING")) {
			$arraysToExclude = explode(',',LAYERS_EXCLUDED_FROM_COUNTING);
			//delete this layers from array
			$layerIdArray = array_diff($layerIdArray, $arraysToExclude);
		}
		//check for existing layers in layer table - delete those
		$notAlreadyExists = array_diff($layerIdArray, $existingLayerIds);
		//Insert those into table (initialize)
		foreach( $notAlreadyExists as $newLayerId ) {
    			$insertLayer[] = '('.$newLayerId.',0)';
		}
		if (count($insertLayer) > 0) {
			$sql = "INSERT INTO layer_load_count (fkey_layer_id,load_count) VALUES ".implode(',',$insertLayer).";";
			$res = db_query($sql);
		}
		//increment load counts
		if (count($layerIdArray) > 0) {
			$sql = "UPDATE layer_load_count SET load_count = load_count+1 WHERE fkey_layer_id in (".implode(',',$layerIdArray).")";
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_layer_monitor.php: Could not increment layer load_count of layers");
				return false;
			} else {
				//update load_count for coupled metadata
				$sql = "SELECT fkey_metadata_id FROM ows_relation_metadata WHERE fkey_layer_id IN (".implode(',',$layerIdArray).")";
				$metadataMonitor = new Metadata_load_count();
				$res = db_query($sql);
				$coupledMetadata = array();
				while($row = db_fetch_array($res)) {
					array_push($coupledMetadata, $row["fkey_metadata_id"]);
				}
				//update load_count for them
				$metadataMonitor->incrementMultiMetadata($coupledMetadata);
				return true;
			}
		} else {
			$e = new mb_notice("class_layer_monitor.php: No layers found to increment load_count");
			return false;
		}
	}
}
?>
