<?php
# $Id: mod_coords_div.php 10245 2019-09-13 10:26:42Z armin11 $
# http://www.mapbender.org/index.php/mod_coords_div.php
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
echo "var mod_showCoords_div_target = '".$e_target[0]."';";

include '../include/dyn_js.php';
?>
var displayTarget = displayTarget ? displayTarget : "dependentDiv";

var mod_showCoords_div_win = null;
var mod_showCoords_div_elName = "showCoords_div";
var mod_showCoords_div_frameName = "";
var mod_showCoords_div_img_on = new Image(); mod_showCoords_div_img_on.src = "<?php  echo preg_replace("/_off/","_on",$e_src);  ?>";
var mod_showCoords_div_img_off = new Image(); mod_showCoords_div_img_off.src = "<?php  echo $e_src;  ?>";
var mod_showCoords_div_img_over = new Image(); mod_showCoords_div_img_over.src = "<?php  echo preg_replace("/_off/","_over",$e_src);  ?>";
var mod_showCoords_div_fix = "";
var mod_showCoords_div_mapObj = null;

var clickx;
var clicky;

if (typeof useMapcode === 'undefined' || useMapcode == false) {
	var useMapcode = 'false';
} else {
	var useMapcode = 'true';
}

function init_mod_showCoords_div(ind){
	mod_showCoords_div_mapObj = getMapObjByName(mod_showCoords_div_target );
	
	mb_button[ind] = document.getElementById(mod_showCoords_div_elName);
	mb_button[ind].img_over = mod_showCoords_div_img_over.src;
	mb_button[ind].img_on = mod_showCoords_div_img_on.src;
	mb_button[ind].img_off = mod_showCoords_div_img_off.src;
	mb_button[ind].status = 0;
	mb_button[ind].elName = mod_showCoords_div_elName;
	mb_button[ind].fName = mod_showCoords_div_frameName;
	mb_button[ind].go = mod_showCoords_div_run;
	mb_button[ind].stop = mod_showCoords_div_disable;   
}
function mod_showCoords_div_run(){
	var $map = $(mod_showCoords_div_mapObj.getDomElement());
	$('#toolsContainer').hide() && $('a.toggleToolsContainer').removeClass('activeToggle');
	$map.bind("mousemove", mod_showCoords_div_display);
	$map.css("cursor", "crosshair");
	$map.bind("click", mod_showCoords_div_click);
}
function mod_showCoords_div_disable(){
	var $map = $(mod_showCoords_div_mapObj.getDomElement());
	$map.unbind("mousemove", mod_showCoords_div_display);
	$map.css("cursor", "default");
	$map.unbind("click", mod_showCoords_div_click);
	if(document.getElementById(displayTarget)){
		writeTag("",displayTarget,"");
	}
}
function mod_showCoords_div_click(e){
	var click = mod_showCoords_div_mapObj.getMousePosition(e);
	if (click === null) {
		return;
	}
	var pos = mod_showCoords_div_mapObj.convertPixelToReal(click);
	mod_showCoords_div_fix = "<?php  echo _mb('Selection'); ?>: " + pos.x + " / " +  pos.y;

	if (mod_showCoords_div_mapObj.epsg =='EPSG:4326' ||  mod_showCoords_div_mapObj.epsg=="EPSG:4258") {
		strDMS = dec2dms(pos.x) + " / " + dec2dms(pos.y);
		 mod_showCoords_div_fix += " <br> " + strDMS;
	}
	clickx = pos.x;
	clicky = pos.y;
	clickcrs = mod_showCoords_div_mapObj.epsg;
	mod_showCoord_write(pos.x,pos.y);
}
function mod_showCoords_div_display(e){
	var click = mod_showCoords_div_mapObj.getMousePosition(e);
	if (click === null) {
		return;
	}
	var pos = makeClickPos2RealWorldPos(mod_showCoords_div_target, click.x, click.y);
	mod_showCoord_write(pos[0],pos[1]);
}

function round(x, n) {
	if (n < 1 || n > 14) return false;
	var e = Math.pow(10, n);
	var k = (Math.round(x * e) / e).toString();
	if (k.indexOf('.') == -1) k += '.';
	k += e.toString().substring(1);
	return k.substring(0, k.indexOf('.') + n+1);
}

function dec2dms(x) {
	xD = Math.floor(x); //full deegrees
	xM = (x-xD) * 60; //decimal minutes
	xS = (xM-Math.floor(xM)) * 60;//decimal seconds
	return xD + "°" + Math.floor(xM) + "'" + round(xS,3) + "''";
}

function getMapcode(x,y) {
	this.transformProjection = function() {
	var parameters = {
		fromSrs: clickcrs,
		toSrs: "EPSG:4326"
	};
	parameters.x = x;
	parameters.y = y;
	var req = new Mapbender.Ajax.Request({
		url: "../php/mod_coordsLookup_server.php",
		method: "transform",
		async: false,
		parameters: parameters,
		callback: function (obj, success, message) {
			if (!success) {
				new Mapbender.Exception(message);
				return;
			}
			if (obj.points.length === 1) {
				var point = new Point(
					obj.points[0].x,
					obj.points[0].y
				);
				//call mapcode
				var results = master_encode(point.y, point.x, 'AAA');
				for (var i = 0; i < results.length; i++) {
					//$('.selectedcoords').append('<p>Mapcode '+results[i][0]+'</p>');
					alert('International Mapcode: '+results[i][0]);
				}
			}
		}
	});
	req.send();
	//invoke transformation
	}
	this.transformProjection();
}

function mod_showCoord_write(x,y){
	if(document.getElementById(displayTarget)){
		var str = "<div class='actualcoords'>" + x + " / " +  y;
		if (mod_showCoords_div_mapObj.epsg =='EPSG:4326' ||  mod_showCoords_div_mapObj.epsg=="EPSG:4258") {
			strDMS = dec2dms(x) + " / " + dec2dms(y);
			str += " <br> " + strDMS;//
		}
		if(mod_showCoords_div_fix != ""){
			//extract coordinates from mod_showCoords_div_fix
			
			str += "<div class='selectedcoords'>" + mod_showCoords_div_fix + "</div>";
			if (useMapcode == "true") {
				str += '<div class="selectedmapcode"><?php  echo _mb('Get Mapcode for this selection'); ?><img src="../img/up-ilink.png" onclick="getMapcode('+clickx+','+clicky+');"><a class="mapcodeanchor" target="_blank" href="http://www.mapcode.com"><img class="mapcodehelp" src="../img/help.png"></a></div>';
			}
		}
		str +=  "</div>";
		writeTag("",displayTarget, str);

//erweiterung close-button
		$(document.createElement('span')).attr({'id':'closeDivButton'}).appendTo(".actualcoords");
		$("#closeDivButton").attr({'style':'position:absolute;top:1px;right:1px;border:1px solid transparent;cursor:pointer;'});
		$("#closeDivButton").attr({'class':'ui-icon ui-icon-closethick'});
		$("#closeDivButton").bind("click", mod_showCoords_div_disable) && $("#showCoords_div").removeClass('myOnClass');
//		$("#closeDivButton").onclick = function(){
 //       writeTag("",displayTarget, "")};
	}
}

