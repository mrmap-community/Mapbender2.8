<?php
# $Id: class_layer_monitor.php 7020 2010-10-04 14:23:22Z christoph $
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

class Metadata_load_count {

	function __construct () {
	}
	
	/**
	 * increments the load count in table "layer_load_count" for
	 * each layer in the WMC document by one. 
	 */
	function increment($metadata_id) {
		if (!is_numeric($metadata_id)) {
			return false;
		}

		//check if an entry exists for the current metadata id
		$sql = "SELECT COUNT(metadata_id) AS i FROM mb_metadata WHERE metadata_id = $1";
		$v = array($metadata_id);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);
		if (intval($row["i"]) === 0) {
			return false;
		}

		//check if an entry exists for the current metadata id
		$sql = "SELECT load_count FROM metadata_load_count WHERE fkey_metadata_id = $1";
		$v = array($metadata_id);
		$t = array('i');
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);

		//if yes, increment the load counter
		if ($row) {
			$currentCount = $row["load_count"];
			$sql = "UPDATE metadata_load_count SET load_count = $1 WHERE fkey_metadata_id = $2";
			$v = array(intval($currentCount + 1), $metadata_id);
			$t = array('i', 'i');
			$res = db_prep_query($sql, $v, $t);
		}
		//if no, insert a new row with current metadata id and load_count = 1
		else {
			$sql = "INSERT INTO metadata_load_count (fkey_metadata_id, load_count) VALUES ($1, 1)";
			$v = array($metadata_id);
			$t = array('i');
			$res = db_prep_query($sql, $v, $t);
		}
	}
	//function to increment more than one layer with one sql statement
	function incrementMultiMetadata($metadataIdArray) {
		if (!is_array($metadataIdArray) || count($metadataIdArray) == 0) {
			return false;
		}
		$metadataIdString = implode(",",$metadataIdArray);
		//check for existing entry in load count table, if not exist - insert zero value
		$sql = "SELECT fkey_metadata_id FROM metadata_load_count WHERE fkey_metadata_id IN (".$metadataIdString.")";
		$res = db_query($sql);
		$existingMetadataIds = array();
		while($row = db_fetch_array($res)) {
			array_push($existingMetadataIds, $row["fkey_metadata_id"]);
		}
		//check for existing metadata in mb_metadata table
		$sql = "SELECT metadata_id FROM mb_metadata WHERE metadata_id IN (".$metadataIdString.")";
		$res = db_query($sql);
		$existingMetadataIdsInMetadata = array();
		while($row = db_fetch_array($res)) {
			array_push($existingMetadataIdsInMetadata, $row["metadata_id"]);
		}
		//use only those who exists in mb_metadata table
		$metadataIdArray = array_intersect($metadataIdArray,$existingMetadataIdsInMetadata);
		//check for existing layers in layer table
		$notAlreadyExists = array_diff($metadataIdArray, $existingMetadataIds);
		//Insert those into table (initialize)
		foreach( $notAlreadyExists as $newMetadataId ) {
    			$insertMetadata[] = '('.$newMetadataId.',0)';
		}
		if (count($insertMetadata) > 0) {
			$sql = "INSERT INTO metadata_load_count (fkey_metadata_id,load_count) VALUES ".implode(',',$insertMetadata).";";
			$res = db_query($sql);
		}
		//increment load counts
		if (count($metadataIdArray) > 0) {
			$sql = "UPDATE metadata_load_count SET load_count = load_count+1 WHERE fkey_metadata_id in (".implode(',',$metadataIdArray).")";
			$res = db_query($sql);
			if (!$res) {
				$e = new mb_exception("class_metadata_monitor.php: Could not increment metadata load_count of metadata");
				return false;
			} else {
				$e = new mb_notice("class_metadata_monitor.php: Updated load_count of metadata");
				return true;
			}
		} else {
			$e = new mb_exception("class_metadata_monitor.php: No metadata found to increment load_count");
			return false;
		}

	}
}
?>
