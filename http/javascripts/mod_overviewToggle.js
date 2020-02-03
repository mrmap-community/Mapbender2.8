/**
 * Package: overviewToggle
 *
 * Description:
 * shows and hides the overview module with a jQuery animation
 * 
 * Files:
 *  - http/javascripts/mod_overviewToggle.js
 *
 * SQL:
 * > INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, 
 * > e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, 
 * > e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, 
 * > e_mb_mod, e_target, e_requires, e_url) VALUES ('<app_id>',
 * > 'overviewToggle',5,1,'','','div','','class="ui-widget-header ui-corner-all"',
 * > -1,-1,NULL ,NULL ,NULL ,
 * > 'display:none;height:24px;width:35px;vertical-align: middle;text-align:right',
 * > '<img style=''position:absolute;top:0px;left:0px'' src=''../img/ovtoggle.png'' /><span style=''margin-left: auto; margin-right: 0;'' class=''ui-icon ui-icon-triangle-1-e''></span>',
 * > 'div','../javascripts/mod_overviewToggle.js','','overview','','');
 * >
 * > INSERT INTO gui_element_vars (fkey_gui_id, fkey_e_id, var_name, 
 * > var_value, context, var_type) VALUES ('<app_id>', 'body', 
 * > 'overviewToggle_css', '../css/dialog/jquery-ui-1.7.2.custom.css', 
 * > '' ,'file/css');
 *
 * > INSERT INTO gui_element_vars (fkey_gui_id, fkey_e_id, var_name, 
 * > var_value, context, var_type) VALUES ('<app_id>', 'overviewToggle', 
 * > 'initialOpen', 'false', 
 * > 'default is false' ,'var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, 
 * > var_value, context, var_type) VALUES('<app_id>', 'overviewToggle', 'overviewToggle_position', 
 * > 'ur', 'define a position for the overviewToggle - ur and lr is possible or false' ,'var');
 * > 
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, 
 * > var_value, context, var_type) VALUES('<app_id>', 'overviewToggle', 
 * > 'overviewToggle_position_offset_top', '0', 'define an offset for the div tag' ,'var');
 * > 
 * Help:
 * http://www.mapbender.org/OverviewToggle
 *
 * Maintainer:
 * http://www.mapbender.org/User:Christoph_Baudson
 * 
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License 
 * and Simplified BSD license.  
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

if (!options.initialOpen || options.initialOpen == 'false') {
	options.initialOpen  = false;
}

var overview_visible = options.initialOpen;

var ovSwitchTarget = options.target[0];
var ovSwitchId = options.id;


if (!options.overviewToggle_position_offset_top) {
	var overviewToggle_position_offset_top = 0;
}else{
	var overviewToggle_position_offset_top = options.overviewToggle_position_offset_top;
}

if (!options.overviewToggle_position) {
	var overviewToggle_position  = false;
}else{
	var overviewToggle_position  = options.overviewToggle_position;
}

if (!options.target[1]) {
	var ovMapframeTarget = 'mapframe1';
}else{
	var ovMapframeTarget = options.target[1];
}

//▶
//◀

	var $ov = $("#" + ovSwitchTarget);
	var $this = $("#" + ovSwitchId);

	var $ovToggleButton = $("#" + ovSwitchId + " > span");
	var overviewInitialWidth = $ov.width();
	var overviewInitialHeight = $ov.height();
	var overviewInitialTop = parseInt($ov.css("top"), 10);
	var overviewLeft = parseInt($ov.css("left"), 10);
	var overviewInitialOuterWidth = $ov.outerWidth();
	
	var $mf = $("#" + ovMapframeTarget);
	var mapframeLeft = parseInt($mf.css("left"), 10);
	var mapframeHeight = parseInt($mf.css("height"), 10);
	var mapframeWidth = parseInt($mf.css("width"), 10);
	var mapframeTop = parseInt($mf.css("top"), 10);

if (overviewToggle_position == 'lr' || overviewToggle_position == 'ur'){
	eventAfterMapRequest.register(function () {
		mod_overviewToggleRefreshPosition();
	});
}


eventInit.register(function () {
	mod_overviewToggle();
});
	
function mod_overviewToggleRefreshPosition(){	
	if(mapframeWidth != parseInt($mf.css("width"), 10) && overviewToggle_position != false){
		mapframeWidth = parseInt($mf.css("width"), 10);
		mapframeHeight = parseInt($mf.css("height"), 10);
		if (overviewToggle_position == 'lr'){
			$ov.css({
				display:"block",
				top: (mapframeTop + mapframeHeight - overviewToggle_position_offset_top - overviewInitialHeight) + "px",
				left: (mapframeLeft + mapframeWidth - overviewInitialWidth) + "px",
				borderWidth: "0px",
				borderStyle: "solid",
				borderColor: "#176798"
			});		
		}else if (overviewToggle_position == 'ur'){
			$ov.css({
				display:"block",
				top: (mapframeTop + overviewToggle_position_offset_top) + "px",
				left: (mapframeLeft + mapframeWidth - overviewInitialWidth) + "px",
				borderWidth: "0px",
				borderStyle: "solid",
				borderColor: "#176798"
			});		
		}
	
		if (overview_visible == false){
			var leftovt = mapframeLeft + mapframeWidth - 35;
		}else{
			var leftovt = mapframeLeft + mapframeWidth - overviewInitialWidth;
		}

		if (overviewToggle_position == 'lr'){
			$this.css({
				display:"inline-block",
				left: leftovt + "px",
				left: leftovt + "px",
				top: (mapframeTop + mapframeHeight - 26 ) + "px",
				zIndex: $ov.css("zIndex")
			});
		}else if (overviewToggle_position == 'ur'){
			$this.css({
				display:"inline-block",
				left: leftovt + "px",
				top: (mapframeTop + overviewToggle_position_offset_top - 26) + "px",
				zIndex: $ov.css("zIndex")
			});
		}
	}
}	
	
	
function mod_overviewToggle(){	
	if (overviewToggle_position == 'lr'){
		$ov.css({
			display:"block",
			top: (mapframeTop + mapframeHeight - overviewToggle_position_offset_top - overviewInitialHeight) + "px",
			left: (mapframeLeft + mapframeWidth - overviewInitialWidth) + "px",
			borderWidth: "0px",
			borderStyle: "solid",
			borderColor: "#176798"
		});		
	}else if (overviewToggle_position == 'ur'){
		$ov.css({
			display:"block",
			top: (mapframeTop + overviewToggle_position_offset_top) + "px",
			left: (mapframeLeft + mapframeWidth - overviewInitialWidth) + "px",
			borderWidth: "0px",
			borderStyle: "solid",
			borderColor: "#176798"
		});		
	}else{
		$ov.css({
			display:"block",
			top: (overviewInitialTop + 26) + "px",
			borderWidth: "0px",
			borderStyle: "solid",
			borderColor: "#176798"
		});
	}
	
	
	if (!overview_visible || overview_visible == false) {
		$ov.css({
			display:"none",	
			left: overviewLeft + "px"
		});
	} else {
			$("#" + ovSwitchId).css('width',overviewInitialWidth+'px');
			$this.addClass("ui-corner-top").removeClass("ui-corner-all");
			$ovToggleButton.removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-w");
	}
	
	
	if (overviewToggle_position == 'lr'){
		if (!overview_visible || overview_visible == false) {
			var leftthis = mapframeLeft + mapframeWidth - 35;
		}else{
			var leftthis = mapframeLeft + mapframeWidth - overviewInitialWidth;
		}		
		$this.css({
			display:"inline-block",
			left: leftthis + "px",
			top: (mapframeTop + mapframeHeight - 26 ) + "px",
			zIndex: $ov.css("zIndex")
		});
	}else if (overviewToggle_position == 'ur'){
		if (!overview_visible || overview_visible == false) {
			var leftthis = mapframeLeft + mapframeWidth - 35;
		}else{
			var leftthis = mapframeLeft + mapframeWidth - overviewInitialWidth;
		}
		$this.css({
			display:"inline-block",
			left: leftthis + "px",
			top: (mapframeTop + overviewToggle_position_offset_top - 26)  + "px",
			zIndex: $ov.css("zIndex")
	
		});
	}else{
		$this.css({
			display:"inline-block",
			left: overviewLeft + "px",
			top: overviewInitialTop + "px",
			zIndex: $ov.css("zIndex")
		});	
	}	
	
	$this.mouseover(function () {
		this.style.cursor = "pointer";
	}).mouseout(function () {
		this.style.cursor = "default";
	}).click(function(){
		if(overview_visible){
			//
			// Hide
			//
			$ov.animate({
				height: 0
			}, "fast", "linear", function () {
				$ov.css({
					display: "none",
					width: "0px",
					borderWidth: "0px",
					borderStyle: "solid",
					borderColor: "#176798"					
				});

				if (overviewToggle_position != 'false' && overviewToggle_position != false){
					var leftovt = mapframeLeft + mapframeWidth - 35;
				}else{
					var leftovt = overviewLeft;
				}
					
				$this.animate({
					width: 35,
					left: leftovt
				}, "fast", "linear", function () {
					$this.removeClass("ui-corner-top").addClass("ui-corner-all");
					$ovToggleButton.addClass("ui-icon-triangle-1-e").removeClass("ui-icon-triangle-1-w");
				});
				overview_visible = false;
				options.initialOpen = false;
			});
		}
		else{
			//
			// Show
			//
			if (overviewToggle_position != 'false' && overviewToggle_position != false){
				var leftovt = mapframeLeft + mapframeWidth - overviewInitialWidth;
			}else{
				var leftovt = overviewLeft;
			}

			$this.animate({
				width: overviewInitialWidth,
				left: leftovt
			}, "fast", "linear", function () {
				$ov.css({
					display: "block",
					width: overviewInitialWidth,
					height: 0,
					left:leftovt,
					borderWidth: "1px",
					borderStyle: "solid",
					borderColor: "#176798"					
				});	
				$ov.animate({
					height: overviewInitialHeight
				}, "fast", "linear", function () {
					$this.addClass("ui-corner-top").removeClass("ui-corner-all");
					$ovToggleButton.removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-w");
				});
				overview_visible = true;
				options.initialOpen = true;
			});
		}
	});
};
