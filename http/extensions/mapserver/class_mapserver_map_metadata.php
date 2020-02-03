<?php
class MapserverMapMetadata
{
    public $ows_title;
    public $ows_srs;
    public $ows_abstract;
    public $ows_keywordlist;
    public $ows_extent;
    public $ows_onlineresource;
    public $ows_fees;
    public $ows_accessconstraints;
    public $ows_addresstype;
    public $ows_address;
    public $ows_city;
    public $ows_stateorprovince;
    public $ows_postcode;
    public $ows_country;
    public $ows_contactperson;
    public $ows_contactorganization;
    public $ows_contactposition;
    public $ows_contactelectronicmailaddress;
    public $ows_updatesequence;
    public $wms_contactfacsimiletelephones;
    public $wms_contactvoicetelephone;
    public $wms_enable_request;
    public $wms_encoding;
    public $wms_feature_info_mime_type;
    //public $wfs_title;
    //public $wfs_abstract;
    public $wfs_enable_request;//" "*"  # necessary
    public $wfs_maxfeatures;//" "1000"# example
    public $wfs_namespace_prefix;//" "baugb_offenlage"
    public $wfs_namespace_uri;//" "http://www.geoportal.rlp.de/baugb/offenlagen"
    public $wfs_encoding; //"UTF-8"
    public $wfs_getfeature_formatlist; //"OGRGML,CSV"

    public $printElements = 	array(	"ows_title",
					"ows_srs",
					"ows_abstract",
					"ows_keywordlist",
					"ows_extent",
    					"ows_onlineresource",
      					"ows_fees",
          				"ows_accessconstraints",
          				"ows_addresstype",
         				"ows_address",
         				"ows_city",
         				"ows_stateorprovince",
         				"ows_postcode",
         				"ows_country",
         				"ows_contactperson",
         				"ows_contactorganization",
         				"ows_contactposition", 						
					"ows_contactelectronicmailaddress",
					"ows_updatesequence",	 						
                                        "wms_contactfacsimiletelephones",
    					"wms_contactvoicetelephone",
    					"wms_enable_request",
    					"wms_encoding",
					"wms_feature_info_mime_type",
					//"wfs_title",
					//"wfs_abstract",
					"wfs_enable_request",
					"wfs_maxfeatures",
					"wfs_namespace_prefix",
					"wfs_namespace_uri",
					"wfs_encoding",
					"wfs_getfeature_formatlist",
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
