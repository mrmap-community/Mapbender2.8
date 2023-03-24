<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_wms_factory.php";
require_once dirname(__FILE__) . "/../classes/class_wms.php";
require_once dirname(__FILE__) . "/../classes/class_connector.php";
require_once dirname(__FILE__) . "/../classes/class_administration.php";
require_once (dirname ( __FILE__ ) . "/class_cache.php");

/**
 * Creates WMS < 1.2 objects from a capabilities documents.
 * 
 * @return Wms
 */
class Wms_1_1_1_Factory extends WmsFactory {
	/**
	 * Creates WMS < 1.2 objects from a capabilities documents.
	 * 
	 * @return Wms
	 * @param $xml String
	 */
	public function createFromXml ($xml, $auth=false) {
	}
	
	public function createFromDb ($id) {
	    $e = new mb_notice("classes/class_wms_1_1_1_factory.php: number of args: " . func_num_args() . " method invoked from parent (class_wms_factory)!");
		$appId = func_num_args() >= 2 ? 
			func_get_arg(1) : null;

		$myWms = new wms();
		if (!is_null($appId)) {
			return parent::createFromDb($id, $myWms, $appId);
		}
		return parent::createFromDb($id, $myWms);
	}
	
	//what does this function do?
	//
	public function createLayerFromDb ($id) {
		$wmsId = func_num_args() >= 2 ? 
			func_get_arg(1) : null;
		$appId = func_num_args() >= 3 ? 
			func_get_arg(2) : null;
		//
		// get WMS of this layer
	    //
		$cache = new Cache ();
		$e = new mb_notice('classes/class_wms_1_1_1_factory.php function createLayerFromDb: found wmsId: ' . $wmsId);
		$start = microtime(true);
		//try to read wms_obj fom cache if already given
		if ($cache->isActive && defined("CACHE_TIME_WMS_LAYER") && is_int(CACHE_TIME_WMS_LAYER)) {
		    if ($cache->cachedVariableExists ( 'mapbender: wms_obj_cache_' . $wmsId . '_' . md5($appId) ) != false) {
		        $e = new mb_notice("classes/class_wms_1_1_1_factory.php: Read existing wms obj with id " . $wmsId . " from cache!");
		        $myWms = $cache->cachedVariableFetch ( 'mapbender: wms_obj_cache_' . $wmsId . '_' . md5($appId) );
		    } else {
		        //try to read wms obj from db
		        $e = new mb_notice("classes/class_wms_1_1_1_factory.php: Read existing wms obj with id " . $wmsId . " from database!");
		        $returnObject = $this->createFromDb($wmsId, $appId);
		        if ($cache->isActive && defined("CACHE_TIME_WMS_LAYER") && is_int(CACHE_TIME_WMS_LAYER)) {
		            $e = new mb_notice("classes/class_wms_1_1_1_factory.php: Write wms obj with id " . $wmsId . " to database!");
		            $cache->cachedVariableAdd ( 'mapbender: wms_obj_cache_' . $wmsId . '_' . md5($appId), $returnObject, CACHE_TIME_WMS_LAYER );
		        }
		        $myWms = $returnObject;
		    }
		}
		$e = new mb_notice('classes/class_wms_1_1_1_factory.php function createLayerFromDb: wms object ready!');
		if (is_null($myWms)) {
			return null;
		}
		$time_elapsed_secs = microtime(true) - $start;
		$e = new mb_notice('classes/class_wms_1_1_1_factory.php function createLayerFromDb: time for createFromDb: ' . $time_elapsed_secs);
		$e = new mb_notice('classes/class_wms_1_1_1_factory.php function createLayerFromDb: after create wms');
		//
		// delete all layers apart from the one mentioned (but keep parents and children) better to use recursive creation
		//
		// Find layers that have both parents and children for testing:
		// SELECT DISTINCT q.layer_id, q.layer_pos, q.layer_parent FROM layer q, layer r WHERE r.layer_parent <> '' AND q.layer_pos = CAST(r.layer_parent AS numeric) and q.layer_parent = '0' and q.fkey_wms_id = r.fkey_wms_id
		$currentLayer = $myWms->getLayerById($id);
		$keep = array();
		$parents = $currentLayer->getParents();
		foreach ($parents as $parent) {
			$keep[]= $parent->layer_uid;
		}
		$keep[]= $currentLayer->layer_uid;
		//TODO: delete the following at each possible position - it is wrong !!! -> $children = $currentLayer->getChildren();
		//$children = $currentLayer->getChildren();
		//new way:
		$admin = new administration();
		$sublayers = $admin->getSubLayers($id);
		foreach ($sublayers as $subLayerId) {
			$keep[] = $subLayerId;
		}
		//
		// 2. delete layers not for keeping
		//
		$i = 0;
		while ($i < count($myWms->objLayer)) {
			$l = $myWms->objLayer[$i];
			if (in_array($l->layer_uid, $keep)) {
				$i++;
				continue;
			}
			// delete layer
			array_splice($myWms->objLayer, $i, 1);
		}
		$time_elapsed_after_recursion_secs = microtime(true) - $start;
		$e = new mb_notice('classes/class_wms_1_1_1_factory.php: time for whole createLayerFromD: ' . $time_elapsed_after_recursion_secs);
		return $myWms;
	}
}
?>
