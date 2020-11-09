<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
//Get list of makiicons for digitizing objects and showing svg layer on map

?>
    var $iconset = $(this);

    var IconApi = function(o) {
        //try to read icons directly from json
        <?php 
            if (file_exists(__DIR__ . '/../extensions/makiicons/selection.json')) {
                echo "var icons = ".file_get_contents(__DIR__ . '/../extensions/makiicons/selection.json' ).";\n";
            } else {
                echo "alert('No icon file found!')"."\n";
            }
        ?>
	//try to append the icons to the target which is defined in the element
        if (o.target !== 'undefined') {
            $selectorString = "#"+o.target;
            var iconsDom = $($selectorString).data('icons', icons);
            //alert("stored icons into $(#target).data('icons')");
	} else {
            alert("mb_icon.php: No target defined to add icon json to!");
	}
    };
    $iconset.mapbender(new IconApi(options));
