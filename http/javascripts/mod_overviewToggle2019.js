/**
 * Package: overviewToggle
 *
 * Description:
 * shows and hides the overview module with a jQuery animation
 * 
 * Files:
 *  - http/javascripts/mod_overviewToggle2019.js
 *
 * SQL:
 * > INSERT INTO gui_element (fkey_gui_id, e_id, e_pos, e_public, e_comment, 
 * > e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, 
 * > e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, 
 * > e_mb_mod, e_target, e_requires, e_url) VALUES ('<app_id>',
 * > 'overviewToggle',5,1,'','','div','','class="overviewToggleClosed"',
 * > -1,-1,NULL ,NULL ,NULL ,
 * > 'position:absolute;right:0px;bottom:20px;background-color:#EEE;border-top:2px solid #DDD;border-left:2px solid #DDD;border-bottom:2px solid #DDD;display:none;',
 * > '<svg width="17" height="18" viewBox="0 0 18 18" fill="none"  xmlns="http://www.w3.org/2000/svg">
<path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"/>
</svg>',
 * > 'div','../javascripts/mod_overviewToggle2019.js','','overview','','');
 * >
 * > INSERT INTO gui_element_vars (fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES ('<app_id>', 'overviewToggle',
 * > 'css', '.overviewToggleClosed svg {float: right;transform: rotate(-90deg);}.overviewToggleOpened svg {float: left;transform: rotate(90deg);margin-top: 42.5%;}.overviewToggleOpened, .overviewToggleClosed {color:#777;padding:5px;}.overviewToggleOpened:hover, .overviewToggleClosed:hover {color:#333;}
 * > '' ,'text/css');
 * >
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
			display:"flex",
			left: overviewLeft + "px",
			top: overviewInitialTop + "px",
			zIndex: $ov.css("zIndex")-1
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
			$ov.hide();
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
					
				$this.css({
					width: 17,
					height:18,
					left: leftovt
				});
				$this.removeClass("overviewToggleOpened").addClass("overviewToggleClosed");
				overview_visible = false;
				options.initialOpen = false;
			
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

			$this.css({
				width: overviewInitialWidth + 28,
				height: overviewInitialHeight + 11,
				left: leftovt
			});
			$ov.css({
				display: "block",
				width: overviewInitialWidth ,
				height: overviewInitialHeight,
				left:leftovt,
				borderWidth: "1px",
				borderStyle: "solid",
				borderColor: "#777"					
				});
				$this.addClass("overviewToggleOpened").removeClass("overviewToggleClosed");	
				overview_visible = true;
				options.initialOpen = true;
			
		}
	});
};
