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
		//$e = new mb_exception("classes/class_geojson_style.php: before iterating geojson: " . $this->geojson);
		foreach($this->geojsonObject->features as $feature) {
		    $e = new mb_exception("classes/class_geojson_style.php: geometry type: " . $feature->geometry->type);
		    //$e = new mb_exception("classes/class_geojson_style.php: feature: " . json_encode($feature));
			$properties = $feature->properties;
			foreach ($styleObject->{$feature->geometry->type} as $name => $value) {
			    //$e = new mb_exception("classes/class_geojson_style.php: geometry type: " . $feature->geometry->type);
				//check for existence
				if (gettype($properties->{$name}) == "NULL") {
				    $properties->{$name} = $value;
				    //overwrite default title with gml_id if this is set
				    if ($name == 'title' && $properties->{'gml_id'} != NULL) {
				        $properties->{$name} = $properties->{'gml_id'};
				    }
				}
			}
		}
		return json_encode($this->geojsonObject);
	}
}
?>
