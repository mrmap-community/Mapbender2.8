<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once dirname(__FILE__)."/../core/globalSettings.php";

/**
 * Normalizes the input data specified at http://www.mapbender.org/GET-Parameter
 */
class GetApi {
	private $layers = array();
	private $featuretypes = array();
	private $geoRSSFeeds = array();
	private $kml = array();
	private $geojson = array();
	private $wmc = array();
	private $zoom = array();
	private $geojsonzoom;
	private $geojsonzoomscale;	
        private $datasetid; //new parameter to find a layer with a corresponding identifier element - solves the INSPIRE data service coupling after retrieving the ows from a dataset search via CSW interface! Only relevant, if a WMS is given 
	
	/**
	 * @param array $input
	 */
	public function __construct ($input) {
		if (!is_array($input)) {
			return null;
		}
		foreach ($input as $key => $value) {
			switch ($key) {
				case "WMC":
					$this->wmc = $this->normalizeWmcInput($value);
					break;
				case "LAYER":
					$this->layers = $this->normalizeLayerInput($value);
					break;
				case "FEATURETYPE":
					$this->featuretypes = $this->normalizeFeaturetypeInput($value);
					break;
				case "GEORSS":
					$this->geoRSSFeeds = $this->normalizeGeoRSSInput($value);
					break;
				case "KML":
					$this->kml = $this->normalizeKmlInput($value);
					break;
				case "GEOJSON":
					$this->geojson = $this->normalizeGeojsonInput($value);
					break;
				case "GEOJSONZOOM":
					$this->geojsonzoom = $this->normalizeGeojsonZoomInput($value);
					break;
				case "GEOJSONZOOMOFFSET":
					$this->geojsonzoomoffset = $this->normalizeGeojsonZoomOffsetInput($value);
					break;
				case "ZOOM":
					$this->zoom = $this->normalizeZoomInput($value);
					break;
				case "DATASETID":
					$this->datasetid = $this->normalizeDatasetIdInput($value);
					break;
			}
		}
	}
	
	/**
	 * Returns an array of layer metadata
	 * @return array
	 */
	public function getLayers () {
		return $this->layers;	
	}

	/**
	 * Returns an array of featuretype metadata
	 * @return array
	 */
	public function getFeaturetypes () {
		return $this->featuretypes;
	}

	/*
	 * 
	 *
	 */
	public function getGeoRSSFeeds(){
		return $this->geoRSSFeeds;
	}

	/*
	 * 
	 *
	 */
	public function getKml(){
		return $this->kml;
	}

	/*
	 * 
	 *
	 */
	public function getGeojson(){
		return $this->geojson;
	}

	/*
	 * 
	 *
	 */
	public function getGeojsonZoom(){
		return $this->geojsonzoom;
	}

	/*
	 * 
	 *
	 */
	public function getGeojsonZoomOffset(){
		return $this->geojsonzoomoffset;
	}

	/*
	 * 
	 *
	 */
	public function getDatasetId(){
		return $this->datasetid;
	}

	/**
	 * Returns an array of zoom parameters
	 * @return array
	 */
	public function getZoom () {
		return $this->zoom;
	}

	/**
	 * Returns an array of wmc
	 * @return array
	 */
	public function getWmc () {
		return $this->wmc;
	}

	// for possible inputs see http://www.mapbender.org/GET-Parameter#WMC
	private function normalizeWmcInput ($input) {
		// assume WMC=12,13,14
		$inputArray = explode(",", $input);
		$input = array();
		$i = 0;
		foreach ($inputArray as $id) {
			if (is_numeric($id)) {
				$input[$i++]["id"] = $id;
			}
		}

// check if each layer has at least an id, if not, delete
		$i = 0;
		while ($i < count($input)) {
			if (!is_array($input[$i]) || !isset($input[$i]["id"]) || !is_numeric($input[$i]["id"])) {
				array_splice($input, $i, 1);
				continue;
			}
			$input[$i]["id"] = intval($input[$i]["id"]);
			$i++;
		}
		return $input;
	}

	// for possible inputs see http://www.mapbender.org/GET-Parameter#LAYER
	// for test cases, see http://www.mapbender.org/Talk:GET-Parameter#LAYER
	private function normalizeLayerInput ($input) {
		if (is_array($input)) {
			$keys = array_keys($input);
			$isSingleLayer = false;
			foreach ($keys as $key) {
				if (!is_numeric($key)) {
					$isSingleLayer = true;
					break;
				}
			}
			// LAYER[id]=12&LAYER[application]=something
			if ($isSingleLayer) {
				$input[0] = array();
				foreach ($keys as $key) {
					if (!is_numeric($key)) {
						$input[0][$key] = $input[$key];
						unset($input[$key]);
					}
				}
			}
			else {
				for ($i = 0; $i < count($input); $i++) {
					// assume LAYER[]=12&LAYER[]=13
					if (is_numeric($input[$i])) {
						$id = $input[$i];
						$input[$i] = array("id" => $id);
					}
					// else assume LAYER[0][id]=12&LAYER[0][application]=something
				}
			}
		}
		else {
			// assume LAYER=12,13,14
			$inputArray = explode(",", $input);
			$input = array();
			$i = 0;
			foreach ($inputArray as $id) {
				if (is_numeric($id)) {
					$input[$i++]["id"] = $id;
				}
			}
		}
		// check if each layer has at least an id, if not, delete
		$i = 0;
		while ($i < count($input)) {
			if (!is_array($input[$i]) || !isset($input[$i]["id"]) || !is_numeric($input[$i]["id"])) {
				array_splice($input, $i, 1);
				continue;
			}
			$input[$i]["id"] = intval($input[$i]["id"]);
			$i++;
		}
		return $input;
	}

	private function normalizeFeaturetypeInput ($input) {
		if (is_array($input)) {
			$keys = array_keys($input);
			$isSingleFeaturetype = false;
			foreach ($keys as $key) {
				if (!is_numeric($key)) {
					$isSingleFeaturetype = true;
					break;
				}
			}
			// FEATURETYPE[id]=12&FEATURETYPE[active]=something
			if ($isSingleFeaturetype) {
				$input[0] = array();
				foreach ($keys as $key) {
					if (!is_numeric($key)) {
						$input[0][$key] = $input[$key];
						unset($input[$key]);
					}
				}
			}
			else {
				for ($i = 0; $i < count($input); $i++) {
					// assume FEATURETYPE[]=12&FEATURETYPE[]=13
					if (is_numeric($input[$i])) {
						$id = $input[$i];
						$input[$i] = array("id" => $id);
					}
				}
			}
		}
		else {
			// assume FEATURETYPE=12,13,14
			$inputArray = explode(",", $input);
			$input = array();
			$i = 0;
			foreach ($inputArray as $id) {
				if (is_numeric($id)) {
					$input[$i++]["id"] = $id;
				}
			}
		}
		// check if each featuretype has at least an id, if not, delete
		$i = 0;
		while ($i < count($input)) {
			if (!is_array($input[$i]) || !isset($input[$i]["id"]) || !is_numeric($input[$i]["id"])) {
				array_splice($input, $i, 1);
				continue;
			}
			$input[$i]["id"] = intval($input[$i]["id"]);
			$i++;
		}
		return $input;
	}

	private function normalizeZoomInput($input){
		$inputArray = explode(",", $input);
		$input = array();
		switch (count($inputArray)) {
			case 3:
				if (is_numeric($inputArray[0]) && is_numeric($inputArray[1]) && is_numeric($inputArray[2])) {
					return $inputArray;
				} else {
					$e = new mb_exception("lib/class_GetApi.php: found non numeric value in zoom parameter!");
				}
				break;
			case 4:
				//check if last element begins with epsg: - then it will be the case, that zoom to coordinate with scale and special epsg is requested
				if (strpos(strtolower($inputArray[3], "epsg:") === 0 ) && is_numeric($inputArray[0]) && is_numeric($inputArray[1]) && is_numeric($inputArray[2])) {
					//extract epsg code ...
					//create point object with scale ...
					return $inputArray;
				} else {
					//read out coordinates - have to be numeric values
					$i = 0;
					foreach ($inputArray as $id) {
						if (is_numeric($id)) {
							//do nothing
						} else {
							$e = new mb_exception("lib/class_GetApi.php: found non numeric value in zoom parameter: at pos ".$i." : ".$id);
							return false;	
						}
						$i++;
					}
					return $inputArray;
				}
				break;
			case 5:
				//read out coordinates and epsg
				for ($i=0; $i < 4; $i++) {
					if (is_numeric($inputArray[$i])) {
						//do nothing
					} else {
						$e = new mb_exception("lib/class_GetApi.php: found non numeric value in zoom parameter: at pos ".$i." : ".$inputArray[$i]);
						return false;	
					}
				}
				//check 5th value
				//check for EPSG:XXXXX
				$pattern = '/^EPSG:\d{1,6}$/';		
 				if (!preg_match($pattern,$inputArray[4])){ 
					$e = new mb_exception("lib/class_GetApi.php: found not allowed value for epsg code for zoom parameter: ".$inputArray[4]);
					return false;	
 				}
				return $inputArray;
				break;
			default:
				//count doesn't match
				return false;
				break;
		}
		/*$i = 0;
		foreach ($inputArray as $id) {
			if (is_numeric($id)) {
				$input[$i++]["id"] = $id;
			}
		}*/

		//return is_array($input) ? $input : array($input);
	}

	private function normalizeGeoRSSInput($input){
		return is_array($input) ? $input : array($input);
	}

	private function normalizeKmlInput($input){
		return is_array($input) ? $input : array($input);
	}

	private function normalizeGeojsonInput($input){
		return is_array($input) ? $input : array($input);
	}

	private function normalizeGeojsonZoomInput($input){
		if ($input == 'true') {
			//$e = new mb_exception("set geojsonzoom to true");
			return 'true';
		} else {
			//$e = new mb_exception("set geojsonzoom to false");
			return 'false';
		}
	}

	private function normalizeGeojsonZoomOffsetInput($input){
		//check input for integer value
		$offset = false;
		$testMatch = $input;
		$pattern = '/^[\d]*$/';		
 		if (preg_match($pattern,$testMatch)){ 	
			$offset = $testMatch;
 		}
		return $offset;
	}

	private function normalizeDatasetIdInput($input){
		$datasetId = false;
		$testMatch = $input;
		$pattern = '/^(?!\s*$).+/';		
 		if (preg_match($pattern,$testMatch)){ 	
			$e = new mb_exception("lib/classGetApi.php: Get parameter DATASETID has whitespaces - will be set to false!");
 		} else {
                        $datasetId = $testMatch;
		}
		return $datasetId;
	}
}

?>
