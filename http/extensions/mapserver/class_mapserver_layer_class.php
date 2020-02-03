<?php
class MapserverLayerClass
{
    public $name;
    public $group;
    public $groupname;
    public $expression;
    public $printElements = 	array(	"name",
					"group",
					"groupname",
					"expression"
				);
    
    private $styles = array();
    private $labels = array();
	
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

    public function addLabel($obj, $key = null) {
    	if ($key == null) {
        	$this->labels[] = $obj;
    	}
    	else {
        	if (isset($this->labels[$key])) {
            	throw new KeyHasUseException("Key $key already in use.");
        	}
        	else {
            	$this->labels[$key] = $obj;
        	}
    	}
    }
 
    public function deleteLabel($key) {
    	if (isset($this->labels[$key])) {
        	unset($this->labels[$key]);
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }
 
    public function getLabel($key) {
    	if (isset($this->labels[$key])) {
        	return $this->labels[$key];
    	}
    	else {
        	throw new KeyInvalidException("Invalid key $key.");
    	}
    }

    public function labelKeys() {
    	return array_keys($this->labels);
    }
    
    public function labelKeyExists($key) {
    	return isset($this->labels[$key]);
    }

    public function printText() {
	$printClass = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printClass = true;
		}
	}
	if ($printClass == true) {
		$text = "CLASS\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		foreach ($this->labels as $label) {
			$text .= $label->printText();
		}
		foreach ($this->styles as $style) {
			$text .= $style->printText();
		}
		$text .= "END\n";
		return $text;
	}
    }

}
?>
