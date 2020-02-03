<?php
class MapserverMapLegend
{
    public $imagecolor;
    public $status;
    public $keysize; 
    public $label; //obj

    public $printElements = 	array(	"imagecolor",
					"status",
					"keysize"
    );

    public function printText() {
	$printLegend = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printLegend = true;
		}
	}
	if ($printLegend == true) {
		$text = "LEGEND\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		$text .= $this->label->printText();
		$text .= "END\n";
		return $text;
	}
    }

}
?>
