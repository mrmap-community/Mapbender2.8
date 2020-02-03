<?php
class MapserverMap
{
    public $name;
    public $size;
    public $units;
    public $symbolset;
    public $fontset;
    public $extent;
    public $projection; //extra
    public $imagecolor;
    public $imagequality;

    public $outputformat = array();

    public $legend; //extra
    public $web; //extra obj

    public $metadata;
    public $layers = array();
    public $printElements = 	array(	"name",
					"size",
					"units",
					"symbolset",
					"fontset",
					"extent",
					"imagecolor",
					"imagequality"
					
				);
 
    public function addLayer($obj, $key = null) {
    	if ($key == null) {
        	$this->layers[] = $obj;
    	}
    	else {
        	if (isset($this->layers[$key])) {
            	throw new KeyHasUseException("Key $key already in use.");
        	}
        	else {
            	$this->layers[$key] = $obj;
        	}
    	}
    }
 
    public function deleteLayer($key) {
    	if (isset($this->layers[$key])) {
        	unset($this->layers[$key]);
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }
 
    public function getLayer($key) {
    	if (isset($this->layers[$key])) {
        	return $this->layers[$key];
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }

    public function layerKeys() {
    	return array_keys($this->layers);
    }
    
    public function layerKeyExists($key) {
    	return isset($this->layers[$key]);
    }

    public function addOutputFormat($obj, $key = null) {
    	if ($key == null) {
        	$this->outputformat[] = $obj;
    	}
    	else {
        	if (isset($this->outputformat[$key])) {
            	throw new KeyHasUseException("Key $key already in use.");
        	}
        	else {
            	$this->outputformat[$key] = $obj;
        	}
    	}
    }
 
    public function deleteOutputFormat($key) {
    	if (isset($this->outputformat[$key])) {
        	unset($this->outputformat[$key]);
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }
 
    public function getOutputFormat($key) {
    	if (isset($this->outputformat[$key])) {
        	return $this->outputformat[$key];
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }

    public function outputFormatKeys() {
    	return array_keys($this->outputformat);
    }
    
    public function outputFormatKeyExists($key) {
    	return isset($this->outputformat[$key]);
    }

    public function printText() {
	$printMap = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printMap = true;
		        break;
		}
	}
	if ($printMap == true) {
		$text = "MAP\n";
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
		foreach ($this->outputformat as $outputformat) {
			$text .= $outputformat->printText();
		}
		if ($this->legend != null) {
			$text .= $this->legend->printText();
		}
		if ($this->web != null) {
			$text .= $this->web->printText();
		}
		foreach ($this->layers as $layer) {
			$text .= $layer->printText();
		}
		$text .= "END\n";
		return $text;
	}
    }

}
?>
