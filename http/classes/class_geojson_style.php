<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");

class geojson_style {
	var $geojson;
	var $geojsonObject;
	var $defaultStyleJson;

	function __construct () {
		$this->defaultStyleJson = file_get_contents(dirname(__FILE__)."/../../conf/geoJsonDefaultStyle.json");
	}
	
	public function addDefaultStyles($geojson) {
		$this->geojson = $geojson;
		$this->geojsonObject = json_decode($this->geojson);
		$styleObject = json_decode($this->defaultStyleJson);
		foreach($this->geojsonObject->features as $feature) {
			$properties = $feature->properties;
			foreach ($styleObject->{$feature->geometry->type} as $name => $value) {
				//check for existence
				if (gettype($properties->{$name}) == "NULL") {
					$properties->{$name} = $value;
			
				}
			}
		}
		return json_encode($this->geojsonObject);
	}
}
?>
