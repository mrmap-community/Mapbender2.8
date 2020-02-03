<?php
# $Id: mod_setBackground.php 8538 2013-01-10 11:02:59Z apour $
# http://www.mapbender.org/index.php/mod_setBackground.php
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");
if(isset($_REQUEST["wms"])){
	$wms = $_REQUEST["wms"];
}
else{
	$wms = 0;
}
echo "var mod_setBackground_wms = ".$wms.";";
echo "var mod_setBackground_target = '".$e_target[0]."';";
?>
eventAfterLoadWMS.register(function () {
	mod_setBackground_init();
});

Mapbender.events.setBackgroundIsReady = new Mapbender.Event();

var mod_setBackground_active = false;

function mod_setBackground_init() {
	var map = Mapbender.modules[mod_setBackground_target];
	var setBackgroundSelectBox = document.setBackground.mod_setBackground_list;
    var selected = false;
    var firstHidden = false;

    /* REMOVE ALL OPTIONS FROM SELECTBOX */
    setBackgroundSelectBox.options.length = 0;
	
    /* ADD NEW OPTIONS TO SELECTBOX */
	for(var i=0; i<map.wms.length; i++) {
        var visibility = map.wms[i].gui_wms_visible;
        var title = map.wms[i].wms_title;
        
        /* IS BACKGROUND? */
		if(visibility == '0' || visibility == '2') {
            if(firstHidden === false) {
                firstHidden = i;
            }
        
            if(visibility == '0') {
                var newOption = new Option(title, i, false, false);
            } else {
                /* SELECTED */
                var newOption = new Option(title, i, false, true);
                selected = i;
            }
            
            /* ADD NEW BACKGROUND OPTION */
			setBackgroundSelectBox.options[setBackgroundSelectBox.length] = newOption;
		}
	}
    
    /* SET FIRST WMS AS OVERVIEW */
	if(Mapbender.modules['overview']) {
		Mapbender.modules['overview'].wms[0].gui_wms_visible = 1;
		setSingleMapRequest('overview', Mapbender.modules['overview'].wms[0].wms_id);
	}
	
    var newBackground = 0;
    
    if(selected === false && firstHidden !== false) {
        newBackground = firstHidden;
		map.wms[firstHidden].gui_wms_visible = 2;
    } else if(selected !== false) {
        newBackground = selected;
    }
    
    /* SET BACKGROUND */
    setBackgroundSelectBox.selectedIndex = newBackground;
    setSingleMapRequest(mod_setBackground_target,map.wms[newBackground].wms_id);
    mod_setBackground_active = newBackground;
    
    Mapbender.events.setBackgroundIsReady.trigger();
}

function mod_setBackground_change(obj) {
	var map = Mapbender.modules[mod_setBackground_target];
	map.wms[mod_setBackground_active].gui_wms_visible = 0;
	map.wms[obj.value].gui_wms_visible = 2;
	
	mod_setBackground_active = obj.value;
	zoom(mod_setBackground_target,true, 1.0); 
}
