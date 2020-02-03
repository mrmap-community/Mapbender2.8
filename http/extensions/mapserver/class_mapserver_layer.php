<?php
class MapserverLayer
{
    public $name;
    public $group;
    public $type;
    public $connection;
    public $connectiontype;
    public $data;
    public $template;
    public $dump;
    public $tolerance;
    public $processing;
    public $status;
    public $labelitem;
    public $minscaledenom;
    public $maxscaledenom;
    public $labelminscaledenom;
    public $labelmaxscaledenom;
    public $metadata;
    public $projection;
    public $printElements = 	array(	"name",
					"group",
					"type",
					"connection",
					"connectiontype",
					"data",
					"template",
					"dump",
					"tolerance",
					"processing",
					"status",
					"labelitem",
					"minscaledenom",
					"maxscaledenom",
					"labelminscaledenom",
					"labelmaxscaledenom"
				);
    
    private $classes = array();
 
    public function addClass($obj, $key = null) {
    	if ($key == null) {
        	$this->classes[] = $obj;
    	}
    	else {
        	if (isset($this->classes[$key])) {
            	throw new KeyHasUseException("Key $key already in use.");
        	}
        	else {
            	$this->classes[$key] = $obj;
        	}
    	}
    }
 
    public function deleteClass($key) {
    	if (isset($this->classes[$key])) {
        	unset($this->classes[$key]);
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }
 
    public function getClass($key) {
    	if (isset($this->classes[$key])) {
        	return $this->classes[$key];
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }

    public function keys() {
    	return array_keys($this->classes);
    }
    
    public function keyExists($key) {
    	return isset($this->classes[$key]);
    }

    public function printText() {
	$printLayer = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printLayer = true;
		        break;
		}
	}
	if ($printLayer == true) {
		$text = "LAYER\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		if ($this->projection != null) {
			$text .= "PROJECTION\n";
			$text .= "  '".$this->projection."'\n";
			$text .= "END\n";
		}
		if ($this->metadata != null) {
			$text .= $this->metadata->printText();
		}
		foreach ($this->classes as $class) {
			$text .= $class->printText();
		}
		$text .= "END\n";
		return $text;
	}
    }
}
?>
