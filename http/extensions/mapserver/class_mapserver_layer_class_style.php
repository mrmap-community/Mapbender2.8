<?php
class MapserverLayerClassStyle
{
    public $color;
    public $symbol;
    public $size;
    public $outlinecolor;
    public $width;
    public $angle;
    public $pattern;
    public $geomtransform;
    public $offset;

    public $printElements = 	array(	"color",
					"symbol",
					"size",
					"outlinecolor",
					"width",
					"angle",
					"pattern",
					"geomtransform",
					"offset"

				);

    public function printText() {
	$printStyle = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printStyle = true;
			break;
		}
	}
	if ($printStyle == true) {
    		$text = "STYLE\n";
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
