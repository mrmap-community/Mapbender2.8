<?php
class MapserverMapOutputFormat
{
    public $name;
    public $driver;
    public $imagetype;
    public $mimetype;
    public $imagemode;
    public $extension;
    public $formatoptions = array();

    public $printElements = 	array(	"name",
					"driver",
					"imagetype",
					"mimetype",
					"imagemode",
					"extension"
				);

    public function printText() {
	$printOutputFormat = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printOutputFormat = true;
		        break;
		}
	}
	if ($printOutputFormat == true) {
		$text = "OUTPUTFORMAT\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		foreach ($this->formatoptions as $formatoption) {
			$text .= "FORMATOPTION"." ".$formatoption."\n";
		}
		$text .= "END\n";
		return $text;
	}
    }
}
?>
