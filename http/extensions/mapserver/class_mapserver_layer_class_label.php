<?php
class MapserverLayerClassLabel
{
    public $partials;
    public $encoding;
    public $type;
    public $font;
    public $size;
    public $color;
    public $outlinecolor;
    public $position;
    public $mindistance;
    public $angle;
    public $buffer;

    public $styles = array();


    public $printElements = 	array(	"partials",
					"encoding",
					"type",
					"font",
					"size",
					"color",
					"outlinecolor",
					"position",
					"mindistance",
					"angle",
					"buffer"
				);

   public function addStyle($obj, $key = null) {
    	if ($key == null) {
        	$this->styles[] = $obj;
    	}
    	else {
        	if (isset($this->styles[$key])) {
            	throw new KeyHasUseException("Key $key already in use.");
        	}
        	else {
            	$this->styles[$key] = $obj;
        	}
    	}
    }
 
    public function deleteStyle($key) {
    	if (isset($this->styles[$key])) {
        	unset($this->styles[$key]);
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }
 
    public function getStyle($key) {
    	if (isset($this->styles[$key])) {
        	return $this->styles[$key];
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }

    public function styleKeys() {
    	return array_keys($this->styles);
    }
    
    public function styleKeyExists($key) {
    	return isset($this->styles[$key]);
    }





    public function printText() {
	$printLabel = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printLabel = true;
			break;
		}
	}
	if ($printLabel == true) {
    		$text = "LABEL\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		foreach ($this->styles as $style) {
			$text .= $style->printText();
		}
		$text .= "END\n";
	}
	return $text;
    }
}
?>
