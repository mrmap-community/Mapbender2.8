<?php
class MapserverMapWeb
{
    public $imagepath;
    public $imageurl;
    public $metadata; //obj

    public $printElements = 	array(	"imagepath",
					"imageurl"
    );

    public function printText() {
	$printWeb = false;
	foreach($this->printElements as $element) {
		if ($this->{$element} != null) {
			$printWeb = true;
		}
	}
	if ($printWeb == true) {
		$text = "WEB\n";
		foreach ($this->printElements as $element) {
			if ($this->{$element} != null) {
				$text .= strtoupper($element)." ".$this->{$element}."\n";
			}
		}
		$text .= $this->metadata->printText();
		$text .= "END\n";
		return $text;
	}
    }

}
?>
