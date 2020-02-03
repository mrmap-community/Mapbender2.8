<?php
# $Id$
# http://www.mapbender.org/index.php/class_propagateMetadata.php
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
require_once(dirname(__FILE__)."/class_cswClient.php");
require_once(dirname(__FILE__)."/class_iso19139.php");
/**
 * Class to propagate metadata from mapbender to connected metadata catalogues
 * @author armin11
 *
 */
class propagateMetadata {
	var $cswId;
	var $resourceType;
	//var $operation; // 'push' or 'delete'
	var $resourceIds = array();
	var $resourceUuids = array();
	var $active = false;
	var $absolutePath;
	var $cswClient;
	
	public function __construct() {
				if (defined("SYNC_CHANGES_TO_CSW") && SYNC_CHANGES_TO_CSW == true && defined("SYNC_CATALOGUE_ID") && is_int(SYNC_CATALOGUE_ID)) {
					$this->cswId = SYNC_CATALOGUE_ID;
					$this->active = true;
					$this->cswClient = new cswClient();
					$this->cswClient->cswId = $this->cswId;
					
				} else {
					$this->cswId = null;
					$this->active = false;
				}
				if (defined("MAPBENDER_PATH") && MAPBENDER_PATH !== "") {
					$this->absolutePath = MAPBENDER_PATH;
				} else {
					$this->absolutePath = "http://".$_SERVER['HTTP_HOST']."/mapbender";
				}
	}
	
	public function doPropagation($resourceType, $resourceIds, $operation, $resourceUuids=false) {
		if ($this->active == true) {
			//$e = new mb_exception("classes/class_propagateMetadata.php: try to propagate metadata!");
			switch ($resourceType) {
				case "layer":
					$mapbenderMetadataUrl = $this->absolutePath."/php/mod_layerISOMetadata.php?SERVICE=WMS&outputFormat=iso19139&Id=";
					$metadataIds = $resourceIds;
					break;
				case "metadata":
					$mapbenderMetadataUrl = $this->absolutePath."/php/mod_dataISOMetadata.php?outputformat=iso19139&id=";
					$metadataIds = $resourceUuids;
					break;
			}
			$isoMetadata = new Iso19139();
			switch ($operation) {
				case "push":
					foreach ($metadataIds as $id) {
						$metadata = $isoMetadata->createFromUrl($mapbenderMetadataUrl.$id);
						$e = new mb_exception("classes/class_propagateMetadata.php: try to push metadata for ".$resourceType." - ".$id);
						$result = $this->cswClient->pushRecord($metadata);
						$e = new mb_exception("classes/class_propagateMetadata.php: ".$result);
					}
					break;
				case "delete":
					$metadataIds = $resourceUuids;
					$e = new mb_exception("classes/class_propagateMetadata.php: count of array to delete: ".count($metadataIds));
					foreach ($metadataIds as $id) {
						
						$e = new mb_exception("classes/class_propagateMetadata.php: try to delete metadata for ".$resourceType." - ".$id);
						$result = $this->cswClient->deleteRecord($id);
						$e = new mb_exception("classes/class_propagateMetadata.php: ".$result);
					}
					break;
			}	
		} else {
			$e = new mb_exception("classes/class_propagateMetadata.php: Metadata propagation not yet configured!");
		}
		return true;	
	}


}
