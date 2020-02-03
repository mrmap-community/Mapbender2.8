<?php
/**
 * Package: mod_metadataCarouselTinySlider
 *
 * Description:
 * This module show metadata about the application(gui)/wmc combination in one div 
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License 
 * and Simplified BSD license.  
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
$e_id = 'applicationMetadata';

//include all element vars from the given element
include '../include/dyn_js.php';
include '../include/dyn_php.php';
?>
var applicationMetadata = function() {
    var that = this;
    this.id = options.id;
    this.initForm = function(obj) {
	/*if (obj == false) {
	    $('#app_metadata').hide();
	}*/
        //set new title
	if (obj.title == '') {
	     $('#app_metadata').hide();
	}
	$(document).attr("title", obj.title);
        $('#appMetadataLogo').find('img').attr('src', obj.organization.logo_path);
        $('#appMetadataTitle').text(obj.title);
	$('#appMetadataContainer').html(obj.abstract+'<br><a style="text-decoration: underline; color:unset;" href="'+obj.metadataUrl+'" target="_blank">Metadaten</a>'+'<hr>Kontakt:<hr>'+obj.organization.title+'<br>'+obj.organization.address+'<br><u>'+obj.organization.postcode+'</u> '+obj.organization.city+'<br>Telefon: '+obj.organization.telephone+'<br>Email: '+'<a style="text-decoration: underline; color:unset;" href="mailto:'+obj.organization.email+'">'+obj.organization.email+'</a>'
);
    }
}

Mapbender.events.init.register(function() {
    Mapbender.modules[options.id] = $.extend(new applicationMetadata(),Mapbender.modules[options.id]);	
});
