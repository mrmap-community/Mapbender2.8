<?php
class MapserverLayerMetadata
{
    public $ows_title;
    public $ows_srs;
    public $ows_abstract;
    public $ows_keywordlist;
    public $ows_extent;
    public $gml_featureid;
    public $gml_include_items;
    //public $wfs_enable_request;
    public $gml_geometries;
    public $gml_geometry_type;
    public $gml_id_alias;
    public $ows_metadataurl_format;
    public $ows_metadataurl_href;
    public $ows_metadataurl_type;

    public $printElements = 	array(	"ows_title",
					"ows_srs",
					"ows_abstract",
					"ows_keywordlist",
					"ows_extent",
					"ows_metadataurl_format",
					"ows_metadataurl_href",
					"ows_metadataurl_type",
					"gml_featureid",
					"gml_include_items",
					"gml_geometries",
					"gml_geometry_type",
					"gml_id_alias",
				);
    
    public function printText() {
	$printMetadata = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printMetadata = true;
		}
	}
	if ($printMetadata == true) {
    		$text = "METADATA\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		$text .= "END\n";
	}
	return $text;
    }
}
?>
