<?php
# $Id: mod_treefolder2.php 2975 2008-09-18 12:58:42Z nimix $
# http://www.mapbender.org/index.php/Mod_treefolder2.php
# Copyright (C) 2007 Melchior Moos
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

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
include '../include/dyn_js.php';
?>
function openwindow (Adresse, width, height) {
	Fenster1 = window.open(Adresse, '<?php echo _mb("Information");?>', "width="+width+",height="+height+",left=100,top=100,scrollbars=yes,resizable=yes");
	Fenster1.focus();
}
<?php
	echo "mod_treeGDE_map = '".$e_target[0]."';";
?>
// <script language="JavaScript">
var msgObj = {};
msgObj.tooltipHandleWms = '<?php echo _mb("(De)activate this service");?>';
msgObj.tooltipLayerVisible = '<?php echo _mb("Toggles the visibility of this service");?>';
msgObj.tooltipLayerQuerylayer = '<?php echo _mb("Toggles the queryability of this service");?>';
msgObj.tooltipLayerContextMenu = '<?php echo _mb("Opens the layer context menu");?>';
msgObj.tooltipWmsContextMenu = '<?php echo _mb("Opens the WMS context menu");?>';
msgObj.tooltipRemoveWms = '<?php echo _mb("Removes the selected WMS");?>';
msgObj.tooltipMoveSelectionUp = '<?php echo _mb("Moves the selection up");?>';
msgObj.tooltipMoveSelectionDown = '<?php echo _mb("Moves the selection down");?>';
msgObj.tooltipMetadata = '<?php echo _mb("Show metadata");?>';
msgObj.tooltipDownload = '<?php echo _mb("Download dataset");?>';
msgObj.tooltipFeaturetypeCoupling = '<?php echo _mb("Coupled featuretypes");?>';

if (typeof(localizetree) === 'undefined')localizetree = 'false';

function localizeTree () {
	var treefolderTitleArray = [];
	var map = Mapbender.modules[mod_treeGDE_map];

	if (map === null) {
		return;
	}
	for(var i = 0; i < map.wms.length; i++) {
		var currentWms = map.wms[i];

		treefolderTitleArray.push({
			title : currentWms.objLayer.length > 0 ?
				currentWms.objLayer[0].gui_layer_title :
				currentWms.wms_title,
			layer : []
		});

		for (var j = 0; j < currentWms.objLayer.length; j++) {
			var currentLayer = currentWms.objLayer[j];
			treefolderTitleArray[treefolderTitleArray.length-1].layer.push({
				title : currentLayer.gui_layer_title
			});
		}
	}

	var req = new Mapbender.Ajax.Request({
		url: "../php/mod_treefolder2_l10n.php",
		method: "translateServiceData",
		parameters: {
			data: treefolderTitleArray
		},
		callback: function (obj, success, message) {
			var translatedTitleArray = obj;
			for (var i = 0; i < translatedTitleArray.length; i++) {
				var currentWms = map.wms[i];
				currentWms.wms_currentTitle = translatedTitleArray[i].title;

				for(var j = 0; j < currentWms.objLayer.length; j++) {
					var currentLayer = currentWms.objLayer[j];
					if (translatedTitleArray[i].layer.length > j) {
						currentLayer.layer_currentTitle = translatedTitleArray[i].layer[j].title;
					}
				}
			}
			reloadTree();
		}
	});
	req.send();
}
/*
eventInit.register(function () {
	localizeTree();
});
*/

if (localizetree == 'true') {
	eventLocalize.register(function () {
		localizeTree();
	});
}

var jst_container = "document.getElementById('treeContainer')";
var jst_image_folder = imagedir;
var jst_display_root = false;
var defaultTarget = 'examplemain';
var lock=false;
var lock_update=false;
var lock_check=false;
var lock_maprequest = false; //global var to prohibit multiple saving states as wmc to session when loading wmc 
var selectedMap=-1;
var selectedWMS=-1;
var selectedLayer=-1;
var initialized=false;
var errors = 0;
var state=Array();
var treeState = "";
<?php
//load structure
$sql = "SELECT * FROM gui_treegde WHERE fkey_gui_id = $1 AND NOT lft = 1 ORDER BY lft;";
$v = array(Mapbender::session()->get("mb_user_gui"));
$t = array("s");
$res = db_prep_query($sql, $v, $t);

//init tree converting arrays
$nr = array(); 			//array for nested sets numbers
$str = array();			//array for js array elements
$categories = array();	//array for wms folders
$path = array();		//stack for actual path elements
$rights = array();		//stack for rights of open elements

//build javascript data array for jsTree
while($row = db_fetch_array($res)){
	//push javascript array elements to a single array with lefts and rights
	$left = "['folder_".$row['id']."', ['".$row['my_layer_title']."', 'javascript:_foo()'],[";
	$right = "]],";
	array_push($nr, $row['lft']);
	array_push($str, $left);
	array_push($nr, $row['rgt']);
	array_push($str, $right);

	//finish all nodes that have no further childs
	while(count($rights) > 0 && $rights[count($rights)-1]<$row['lft']){
		array_pop($rights);
		array_pop($path);
	}

	//set path for each wms that is referenced in this folder
	array_push($rights, $row['rgt']);
	array_push($path, "folder_".$row['id']);
	if($row['wms_id']!=""){
		foreach(explode(",",$row['wms_id']) as $wms){
			array_push($categories, "'wms_".$wms."':\"root_id|".implode("|", $path)."\"");
		}
	}
}
//if we have a configured structure output it
if(count($str)>0){
	//order js array elements
	array_multisort($nr, $str);

	//output javascript vars
	$arrNodesStr = "[['root_id', ['Layer','javascript:_foo()'],[".implode("",$str)."]]];";
	$arrNodesStr = str_replace(array("[]", ",]"),array("","]"),$arrNodesStr);
	echo "var arrNodesStr = \"".$arrNodesStr."\";\n";
	echo "var categories = {".implode(",", $categories)."};\n";
}
else{
//if there is no structure take default
?>
var arrNodesStr = "[['root_id', ['Layer','javascript:_foo()']]];";
var categories = {};
<?php
}
?>
var arrNodes = eval(arrNodesStr);
function _foo(){selectedMap=-1;selectedWMS=-1;selectedLayer=-1}

// some defaults
if (typeof(reverse) === 'undefined' || reverse == 'false') {
	reverseWms = false;
} else { 
	reverseWms = true;
}
if (typeof(switchwms) === 'undefined')switchwms = 'true';
if (typeof(ficheckbox) === 'undefined')ficheckbox = 'false';
if (typeof(metadatalink) === 'undefined')metadatalink = 'false';
if (typeof(wmsbuttons) === 'undefined')wmsbuttons = 'false';
if (typeof(showstatus) === 'undefined')showstatus = 'false';
if (typeof(alerterror) === 'undefined')alerterror = 'false';
if (typeof(openfolder) === 'undefined')openfolder = 'false';
if (typeof(handlesublayer) === 'undefined')handlesublayer = 'false';
if (typeof(enlargetreewidth) === 'undefined') enlargetreewidth = 'false';
if (typeof(enlargetreewidthopacity) === 'undefined') enlargetreewidthopacity = 'false';
if (typeof(menu) === 'undefined')menu = '';
if (typeof(redirectToMetadataUrl) !== 'undefined' && redirectToMetadataUrl == "false") {
	redirectToMetadataUrl = false;
}
else {
	redirectToMetadataUrl = true;
}

if (typeof(datalink) === 'undefined')datalink = 'false';
if (typeof(featuretypeCoupling) === 'undefined')featuretypeCoupling = 'false';
if (typeof(metadataWidth) === 'undefined')metadataWidth = '500';
if (typeof(metadataHeight) === 'undefined')metadataHeight = '500';
if (typeof(activatedimension) === 'undefined')activatedimension = 'false';

var defaultMetadataUrl = '../php/mod_showMetadata.php?resource=layer&layout=tabs&';
if (redirectToMetadataUrl) {
	defaultMetadataUrl += 'redirectToMetadataUrl=1';
}
else {
	defaultMetadataUrl += 'redirectToMetadataUrl=0';
}

//menu elements
var menu_move_up = ['menu_move_up', ['<?php echo _mb("Move up");?>&nbsp;','javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];move_up(ids[0],ids[1],ids[2]);',,'up.svg']];
var menu_move_down = ['menu_move_down', ['<?php echo _mb("Move down");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];move_down(ids[0],ids[1],ids[2]);',,'down.svg']];
var menu_delete = ['menu_delete', ['<?php echo _mb("Remove");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];remove_wms(ids[0],ids[1],ids[2]);',,'trash.svg']];
var menu_opacity_down = ['menu_opacity_down', ['<?php echo _mb("Opacity down");?>&nbsp;','javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];opacity_down(ids[0],ids[1],ids[2]);',,'up.svg']];
var menu_opacity_up = ['menu_opacity_up', ['<?php echo _mb("Opacity up");?>&nbsp;','javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];opacity_up(ids[0],ids[1],ids[2]);',,'down.svg']];
var menu_metalink = ['menu_metalink', ['<?php echo _mb("Information");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];openwindow(defaultMetadataUrl + "&id="+parent.mb_mapObj[ids[0]].wms[ids[1]].objLayer[ids[2]].layer_uid'+','+metadataWidth+','+metadataHeight+');',,'info.svg']];
var menu_zoom = ['menu_zoom', ['<?php echo _mb("Zoom");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];zoomToLayer(ids[0],ids[1],ids[2]);',,'zoom.png']];
var menu_hide = ['menu_hide', ['<?php echo _mb("Hide menu");?>&nbsp;', 'javascript:hideMenu()',,'clear_white.svg']];
var menu_style = ['menu_style', ['<?php echo _mb("Change style");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];openStyleDialog(ids[0],ids[1],ids[2])',,'styling.svg']];
var menu_legend = ['menu_legend', ['<?php echo _mb("Legende öffnen");?>&nbsp;', 'javascript:var sd = "{@strData}";var ids=eval(sd.substr(0, sd.length-6))[1][7];openLegendHtml(ids[0],ids[1],ids[2])',,'legend_tree.png']];
//var menu_wms_switch = ['menu_zoom', ['<?php echo _mb("Zoom");?>&nbsp;', 'javascript:var sd = "{@strData}";eval(eval(sd.substr(0, sd.length-6))[1][1]);openwindow(defaultMetadataUrl + "&id="+parent.mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_uid'+','+metadataWidth+','+metadataHeight+');',,'info.png']];
//var menu_layer_switch = ['menu_zoom', ['Zjjj&nbsp;', 'javascript:var sd = "{@strData}";eval(eval(sd.substr(0, sd.length-6))[1][1]);openwindow(defaultMetadataUrl + "&id="+parent.mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_uid'+','+metadataWidth+','+metadataHeight+');',,'info.png']];
//var menu_info_switch = ['menu_zoom', ['Zmn&nbsp;', 'javascript:var sd = "{@strData}";eval(eval(sd.substr(0, sd.length-6))[1][1]);openwindow(defaultMetadataUrl + "&id="+parent.mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_uid'+','+metadataWidth+','+metadataHeight+');',,'info.png']];

//parent.eventMapRequestFailed.register(function(t){imgerror(t)});

eventAfterLoadWMS.register(reloadTree);

eventInit.register(registerInitMapCheck);

Mapbender.events.init.register(function () {
	$("#" + mod_treeGDE_map).mapbender().events.afterMoveWms.register(reloadTree);
});
eventInit.register(loadTree);
if(showstatus=='true'||alerterror=='true'){
	//eventAfterMapRequest.register(init_mapcheck);
	//init_mapcheck();
}
eventAfterMapRequest.register(updateScale);
eventAfterMapRequest.register(updateCheckState);

function registerInitMapCheck() {
	if(showstatus=='true'||alerterror=='true'){
		//console.log("Registrierung init_mapcheck an eventAfterMapRequest");
		eventAfterMapRequest.register(init_mapcheck);
	}
}

if (enlargetreewidth) {
    eventAfterInit.register(function(){

        var initialWidth = parseInt($('#treeGDE').css("width"));
        $('#treeGDE').bind("mouseenter", function(){
            $(this).css({
                'width': initialWidth + enlargetreewidth,
            });
		if (enlargetreewidthopacity) {
			$(this).css({
				'-moz-opacity': '1',
				'opacity': '1',
				'filter': 'alpha(opacity=100)'
            		});
		}
            $(this).mousewheel();
        });
        $('#treeGDE').bind("mouseleave", function(){
            $(this).css({
                'width': initialWidth,
            });
		if (enlargetreewidthopacity) {
			$(this).css({
				'-moz-opacity': '1',
				'opacity': '1',
				'filter': 'alpha(opacity=100)'
            		});
		}
        });
    });
}

if(wmsbuttons != "true")
	jst_highlight = false;

function select(i,ii,iii){
	//ignore if selected
	if(selectedMap==i && selectedWMS==ii && selectedLayer==iii)return;
	if(selectedMap==-1 && selectedWMS==-1 && selectedLayer==-1){
		selectedMap=i;
		selectedWMS=ii;
		selectedLayer=iii;
		return;
	}
	//scalehints
	var scale = parseInt(mb_getScale(mod_treeGDE_map));
	if(scale < parseInt(mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].gui_layer_minscale) && parseInt(mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].gui_layer_minscale) != 0){
		if(selectedLayer==0)
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id, '#999999');
		else
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id+"|"+ mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_id, '#999999');
	}
	else if(scale > parseInt( mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].gui_layer_maxscale) && parseInt( mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].gui_layer_maxscale) != 0){
		if(selectedLayer==0)
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id, '#999999');
		else
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id+"|"+ mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_id, '#999999');
	}
	else{
		if(selectedLayer==0)
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id, '');
		else
		   	setNodeColor(arrNodes[0][0]+"|wms_"+ mb_mapObj[selectedMap].wms[selectedWMS].wms_id+"|"+ mb_mapObj[selectedMap].wms[selectedWMS].objLayer[selectedLayer].layer_id, '');
	}

	selectedMap=i;
	selectedWMS=ii;
	selectedLayer=iii;
}

function updateScale(){
	if(!initialized)return;
	myMapObj = getMapObjByName(mod_treeGDE_map);
	if(myMapObj){
		var scale = parseInt( myMapObj.getScale());
		for(var ii=0; ii< myMapObj.wms.length; ii++){
			for(var iii=1; iii< myMapObj.wms[ii].objLayer.length; iii++){
				if(scale < parseInt( myMapObj.wms[ii].objLayer[iii].gui_layer_minscale) && parseInt( myMapObj.wms[ii].objLayer[iii].gui_layer_minscale) != 0){
						if(iii==0)
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id, '#999999');
						else
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id+"|"+ myMapObj.wms[ii].objLayer[iii].layer_id, '#999999');
					}
				else if(scale > parseInt( myMapObj.wms[ii].objLayer[iii].gui_layer_maxscale) && parseInt( myMapObj.wms[ii].objLayer[iii].gui_layer_maxscale) != 0){
						if(iii==0)
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id, '#999999');
						else
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id+"|"+ myMapObj.wms[ii].objLayer[iii].layer_id, '#999999');
					}
					else{
						if(iii==0)
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id, '');
						else
					   	setNodeColor(arrNodes[0][0]+"|wms_"+ myMapObj.wms[ii].wms_id+"|"+ myMapObj.wms[ii].objLayer[iii].layer_id, '');
					}
				}
			}
		}
	}

function updateCheckState(){
	if(!initialized||lock_check)return;
	lock_check=true;
	var map = getMapObjByName(mod_treeGDE_map);
	for(var i=0; i< mb_mapObj.length; i++){
		var scale = parseInt( map.getScale());
		if( mb_mapObj[i].elementName == mod_treeGDE_map){
			for(var ii=0; ii< mb_mapObj[i].wms.length; ii++){
				for(var iii=1; iii< mb_mapObj[i].wms[ii].objLayer.length; iii++){
					if(! mb_mapObj[i].wms[ii].objLayer[iii].has_childs){
						path = arrNodes[0][0]+"|wms_"+ mb_mapObj[i].wms[ii].wms_id+"|"+ mb_mapObj[i].wms[ii].objLayer[iii].layer_id;
						checkNode(path, 0,  mb_mapObj[i].wms[ii].objLayer[iii].gui_layer_visible==='1'||mb_mapObj[i].wms[ii].objLayer[iii].gui_layer_visible===1, false);
						if(ficheckbox == 'true')
							checkNode(path, 1,  mb_mapObj[i].wms[ii].objLayer[iii].gui_layer_querylayer=='1', false);
					}
				}
			}
		}
	}
	lock_check=false;
}

function operaLoad(){
	initArray();
	renderTree();
	setTimeout('initWmsCheckboxen();updateScale();',100);
}

function loadTree(){
	if(wmsbuttons=='true'){
		var div = document.createElement("div");
		div.innerHTML = '<a href="javascript:move_up()"><img title="'+msgObj.tooltipMoveSelectionUp+'" src="'+imagedir+'/up.svg" alt="move up" style="position:relative;top:0px;left:0px;"/></a><a href="javascript:move_down()"><img title="'+msgObj.tooltipMoveSelectionDown+'" src="'+imagedir+'/down.svg" alt="move down" style="position:relative;top:0px;left:-3px"/></a><a href="javascript:remove_wms()"><img title="'+msgObj.tooltipRemoveWms+'" src="'+imagedir+'/delete_wms.png" alt="remove wms" style="position:relative;top:0px;left:-6px"/></a>';
		document.getElementById("treeGDE").appendChild(div);
	}
	var div = document.createElement("div");
	div.id = "treeContainer"
	document.getElementById("treeGDE").appendChild(div);

	if(window.opera){
		setTimeout('operaLoad()',200);
		return;
	}
	//console.log('load tree');
	initArray();
	renderTree();
	initWmsCheckboxen();
	updateScale();
}

function reloadTree(){
	if(!initialized) return;
	selectedMap=-1;
	selectedWMS=-1;
	selectedLayer=-1;
	initialized=false;
	arrNodes = eval(arrNodesStr)
	initArray();
	if(showstatus=='true'||alerterror=='true')
		//init_mapcheck();
	renderTree();
	if(window.opera)
		setTimeout('initWmsCheckboxen();updateScale();',100);
	else{
		initWmsCheckboxen();
		updateScale();
	}
	Mapbender.events.treeReloaded.trigger();
}

function imgerror(wms, path, imgObj){
	var map= getMapObjIndexByName(mod_treeGDE_map);
	//t.onerror=null;
	//t.onabort=null;
	if(state[wms]!=-1 && alerterror=='true'){
		state[wms]=-1;
		
		var errorMsg = '<?php echo _mb("Failed to load WMS");?> ' + mb_mapObj[map].wms[wms].objLayer[0].layer_currentTitle;
		$('<div id="imgErrorDialog">' + errorMsg + ':<iframe style="width:90%;height:90%;" src="' + imgObj.attr("src") + '"></iframe></div>').dialog();
		
		checkNode(path, 0, false);
		
		/*if(confirm('<?php echo _mb("Failed to load WMS");?> ' +
			mb_mapObj[map].wms[wms].objLayer[0].layer_currentTitle +
			'\n<?php echo _mb("Do you want to try to load it in a new window?");?>')) {
			window.open(imgObj.attr("src"),"");
		}
		*/
	}
	state[wms]=-1;
	errors++;
	if(showstatus=='true')
		setNodeImage(arrNodes[0][0]+"|wms_"+ mb_mapObj[map].wms[wms].wms_id, "alert.svg");
}

function checkComplete(wms, map, img, first){
	var ind= getMapObjIndexByName(mod_treeGDE_map);
	if (mb_mapObj[ind].wms[wms].mapURL == false ||
		!mb_mapObj[ind].getDomElement().ownerDocument.getElementById(map) ||
		mb_mapObj[ind].getDomElement().ownerDocument.getElementById(map).complete) {

		if(state[wms]!=-1){
			for(var i=1;i< mb_mapObj[ind].wms[wms].objLayer.length;i++){
				if(mb_mapObj[ind].wms[wms].objLayer[i].gui_layer_visible===1||mb_mapObj[ind].wms[wms].objLayer[i].gui_layer_visible==="1"){
					state[wms]=1;
					if(showstatus=='true')
						setNodeImage(img);
						//Removes the previous Maprequest image from dom
						var prevlastRequestDiv = $("#"+mod_treeGDE_map+"_maps > div:last").prev().attr("id");
						var prevWmsgid=prevlastRequestDiv+"_map_"+wms;
						var $prevWmsgid=$("#"+prevWmsgid);
						$prevWmsgid.remove();	
					break;
				}
			}
		}
	}
	else{
		if(first){
			state[wms]=0;
			$("#" + map).error(function () {
				imgerror(wms, img, $(this));
			});
			//$(map).onabort=imgerror;

			if(showstatus=='true')
				setNodeImage(img, "clock.svg");
		}

		if(state[wms]!=-1)
			setTimeout('checkComplete('+wms+', "'+map+'", "'+img+'");',100);
	}
}

// mb_registerWmsLoadErrorFunctions("window.frames['treeGDE'].imgerror();");

function init_mapcheck(){
	if(!initialized)return;
	errors = 0;
	var ind =  getMapObjIndexByName(mod_treeGDE_map);
	if(! mb_mapObj[ind]||!initialized){
		setTimeout("init_mapcheck();",100);
		return;
	}
	for(var wms=0;wms< mb_mapObj[ind].wms.length;wms++){		
		var lastRequestDiv = $("#"+mod_treeGDE_map+"_maps > div:last" ).attr("id");
		var wmsimgid=lastRequestDiv+"_map_"+wms;	
		if( mb_mapObj[ind].getDomElement().ownerDocument.getElementById(wmsimgid)){
			checkComplete(wms, wmsimgid, arrNodes[0][0]+'|wms_'+ mb_mapObj[ind].wms[wms].wms_id, true);
		}
	}
}

function local_handleSelectedLayer(mapObj,wms_id,layername,type,status){
	if(lock_update||lock_check)return;
	var ind =  getMapObjIndexByName(mapObj);
	for(var i=0; i< mb_mapObj[ind].wms.length; i++){
		if( mb_mapObj[ind].wms[i].wms_id == wms_id){
			mb_mapObj[ind].wms[i].handleLayer(layername, type, status);
			break;
		}
	}
}

function zoomToLayer(j,k,l){
	if(!j&&!k&&!l){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	var my= mb_mapObj[j].wms[k].objLayer[l].layer_epsg;
	for (var i=0; i < my.length;i++) {
		if(my[i]["epsg"]== mb_mapObj[j].epsg){
			mb_calculateExtent(mod_treeGDE_map,my[i]["minx"],my[i]["miny"],my[i]["maxx"],my[i]["maxy"]);
			var arrayExt =  mb_mapObj[j].extent.toString().split(",");
			mb_repaint(mod_treeGDE_map,arrayExt[0],arrayExt[1],arrayExt[2],arrayExt[3]);
			//mb_repaint(mod_treeGDE_map,my[i]["minx"],my[i]["miny"],my[i]["maxx"],my[i]["maxy"]);
			break;
		}
	}
}

function openLegendHtml(j,k,l){
	if(!j && !k&& !l){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	var my= mb_mapObj[j].wms[k].objLayer[l];
	var legendWindow = window.open("../metadata/"+my.layer_name+".html", '<?php echo _mb("Legend");?>', "width=800,height=800,left=100,top=100,scrollbars=yes,resizable=no");
	legendWindow.focus();
}

function openStyleDialog(j,k,l){
	if(!j && !k&& !l){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	/*console.log("openStyleDialog - j: " + j);
	console.log("openStyleDialog - k: " + k);
	console.log("openStyleDialog - l: " + l);*/
	var my= mb_mapObj[j].wms[k].objLayer[l];
	var dialogHtml = "<select id='styleSelect'>";
	for (var i=0;i < my.layer_style.length;i++) {
		dialogHtml += "<option value='" + my.layer_style[i].name + "'";
		if(my.layer_style[i].name == my.gui_layer_style) {
			dialogHtml += " selected";
		}
		dialogHtml += ">" + my.layer_style[i].title + "</option>";
	}
	dialogHtml += "</select>";
	//reinitialize changeStyleDialog
    if ($("#changeStyleDialog").length == 1) {
    	$("#changeStyleDialog").remove();
    }
	if(my.layer_style.length > 1) {
		$("<div id='changeStyleDialog' title='<?php echo _mb('Change layer style');?>'><?php echo _mb('Please select a style');?>: </div>").dialog(
			{
				bgiframe: true,
				autoOpen: true,
				modal: false,
				buttons: {
					"<?php echo _mb('Close');?>": function(){
						$(this).dialog('close').remove();
					}
				}
			}
		);
		$(dialogHtml).appendTo("#changeStyleDialog");
		$("#styleSelect").change(function() {
			my.gui_layer_style = this.options[this.selectedIndex].value;
			Mapbender.modules[mod_treeGDE_map].setMapRequest();
		});
	}
	else {
		alert("<?php echo _mb('There is no different style available for this layer.');?>");
	}

}

//---begin------------- opacity --------------------

var opacityIncrement = 20;

function opacity_up(j, k, l) {
	handleOpacity(j, k, opacityIncrement);
}

function opacity_down(j, k, l) {
	handleOpacity(j, k, -opacityIncrement);
}

function handleOpacity(mapObj_id, wms_id, increment) {
	var opacity =  mb_mapObj[mapObj_id].wms[wms_id].gui_wms_mapopacity*100 + increment;
	mb_mapObj[mapObj_id].wms[wms_id].setOpacity(opacity);
	reloadTree();
	Mapbender.modules[mod_treeGDE_map].setMapRequest();
}

//---end------------- opacity --------------------

function move_up(j,k,l){
	if(isNaN(j)&&isNaN(k)&&isNaN(l)){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	if(j==-1||k==-1||l==-1){
		alert("<?php echo _mb('You have to select the WMS you want to move up!');?> ")
		return;
	}
	var lid= mb_mapObj[j].wms[k].objLayer[l].layer_id;
	if(! mb_mapObj[j].move( mb_mapObj[j].wms[k].wms_id, lid, reverseWms)){
	//if(! mb_mapObj[j].move( mb_mapObj[j].wms[k].wms_id,lid,(reverse=="true")?false:true)){
		alert("<?php echo _mb('Illegal move operation');?>");
		return;
	}
	treeState = getState();
	 mb_mapObj[j].zoom(true, 1.0);
	 mb_execloadWmsSubFunctions();
	//find layer and select
	for(k=0;k< mb_mapObj[j].wms.length;k++){
		for(l=0;l< mb_mapObj[j].wms[k].objLayer.length;l++){
			if( mb_mapObj[j].wms[k].objLayer[l].layer_id==lid){
				select(j,k,l);
				if(l!=0)
					selectNode(String(lid));
				else
					selectNode("wms_"+String( mb_mapObj[j].wms[k].wms_id));
			}
		}
	}
}

function move_down(j,k,l){
	if(isNaN(j)&&isNaN(k)&&isNaN(l)){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	if(j==-1||k==-1||l==-1){
		alert("<?php echo _mb('You have to select the WMS you want to move down!');?>")
		return;
	}
	var lid= mb_mapObj[j].wms[k].objLayer[l].layer_id;
	if(! mb_mapObj[j].move( mb_mapObj[j].wms[k].wms_id,lid, reverseWms)){
		alert("<?php echo _mb('Illegal move operation');?>");
		return;
	}
	treeState = getState();
	 mb_mapObj[j].zoom(true, 1.0);
	 mb_execloadWmsSubFunctions();
	//find layer and select
	for(k=0;k< mb_mapObj[j].wms.length;k++){
		for(l=0;l< mb_mapObj[j].wms[k].objLayer.length;l++){
			if( mb_mapObj[j].wms[k].objLayer[l].layer_id==lid){
				select(j,k,l);
				if(l!=0)
					selectNode(String(lid));
				else
					selectNode("wms_"+String( mb_mapObj[j].wms[k].wms_id));
			}
		}
	}
}

function remove_wms(j,k,l){
	if(isNaN(j)&&isNaN(k)&&isNaN(l)){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	if(j==-1||k==-1||l==-1){
		alert("<?php echo _mb('You have to select the WMS you want to delete!');?>")
		return;
	}
	if(l!=0){
		alert("<?php echo _mb('It is not possible to delete a single layer, please select a WMS!');?>")
		return;
	}
	var visibleWMS=0;
	for(var i=0;i< mb_mapObj[j].wms.length;i++)
		if( mb_mapObj[j].wms[i].gui_wms_visible==='1'|| mb_mapObj[j].wms[i].gui_wms_visible===1)
			visibleWMS++;
	if(visibleWMS<=1){
		alert ("<?php echo _mb('Last WMS can not be removed.');?>");
		return;
	}
	if(confirm('<?php echo _mb("Are you sure you want to remove");?>' + ' "'+ mb_mapObj[j].wms[k].objLayer[l].layer_currentTitle+'"?')){
  		 mb_mapObjremoveWMS(j,k);
		 mb_mapObj[j].zoom(true, 1.0);
		 lock_maprequest = true; //done to prohibit save wmc for each wms
		 mb_execloadWmsSubFunctions();
		 lock_maprequest = false;
	}
}

function updateParent(path){
	if(lock_check)return;
	var reset_lock=!lock_update;
	lock_update=true;
	var state=getChildrenCheckState(path, 0);
	//enableCheckbox(path, (state!=-1)); //3rd state
	checkNode(path, 0, (state==1));
	if(state==0 && showstatus=='true' && path.split(jst_delimiter[0]).length == 2){
		setTimeout('setNodeImage("'+path+'", "eye_off.svg");', 100);
	}
	else{
		setTimeout('setNodeImage("'+path+'", "verticaldots.svg");', 100);
	}
	if(reset_lock){
		lock_update=false;
	}
	handleSelectedWMS(path, true);
}

function handleSelectedWMS(path){
	//console.log("handleSelectedWMS path: " + path);
	if(lock_update){
		//console.log("lock update: " + lock_update);
		return;
	}
	var t = path.split("|");
	//path always begin with root_id|wms_{wms_id}|... 
	var wms_id = t[1].substr(4);
	//console.log("handleSelectedWMS wms_id: " + wms_id);
	var reset_lock=!lock_check;
	var ind =  getMapObjIndexByName(mod_treeGDE_map);
	//console.log("handleSelectedWMS ind: " + ind);
	var wms =  getWMSIndexById(mod_treeGDE_map, wms_id);
	//console.log("handleSelectedWMS wms: " + wms);
	var layername =  mb_mapObj[ind].wms[wms].objLayer[0].layer_name;
	var bChk = IsChecked(path, 0);
	// in this case, only the root layer visibility/querylayer
	// needs to be adjusted, without cascading the changes to
	// its children
	if (arguments.length === 2 && arguments[1]) {
		var l = mb_mapObj[ind].wms[wms].getLayerByLayerName(layername);
		l.gui_layer_visible = bChk ? 1 : 0;
		l.gui_layer_querylayer = bChk ? 1 : 0;

		mb_restateLayers(mod_treeGDE_map,wms_id);
		if (!lock_maprequest) {
			setSingleMapRequest(mod_treeGDE_map,wms_id);
		}		
		return;
	}
	mb_mapObj[ind].wms[wms].handleLayer(layername,"visible",bChk?"1":"0");
	mb_mapObj[ind].wms[wms].handleLayer(layername,"querylayer",bChk?"1":"0");
	lock_check=true;
	checkChildren(path, 0, bChk);
	if(ficheckbox)checkChildren(path, 1, bChk);
	if(bChk==false && showstatus=='true'){
		setTimeout('setNodeImage("'+path+'", "eye_off.svg");', 100);
	}
	else{
		setTimeout('setNodeImage("'+path+'", "verticaldots.svg");', 100);
	}
	if(reset_lock)
	{
		mb_restateLayers(mod_treeGDE_map,wms_id);
		if (!lock_maprequest) {
			setSingleMapRequest(mod_treeGDE_map,wms_id);
		}
		lock_check=false;
	}
}

function handleSelection(path, box){
	if(lock_update)return;
	var reset_lock=!lock_check;
	lock_check=true;
	var bChk = IsChecked(path, box);
//	enableCheckbox(path, 0, true);
	checkChildren(path, box, bChk);
	if(reset_lock){
		//find wms id from path
		var t = path.split("|");
		for(var i=1;t[i].indexOf("wms_")!=0;i++){}
		var wms_id = t[i].substr(4);
		//set maprequest
		 mb_restateLayers(mod_treeGDE_map,wms_id);
		if(box==0)
			 setSingleMapRequest(mod_treeGDE_map,wms_id);
		lock_check=false;
	}
}

function setDimensionUserValue(j,k,l,dimensionIndex,userValue) {
	if(!j && !k&& !l){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	var my = mb_mapObj[j].wms[k].objLayer[l];
	var myWms = mb_mapObj[j].wms[k];
	//delete all userValues from other layers of this wms
	for (var k=0;k< myWms.objLayer.length;k++) {
		for (var i=0;i< myWms.objLayer[k].layer_dimension.length;i++) {
			myWms.objLayer[k].layer_dimension[i].userValue = "";
		}
	}
	my.layer_dimension[dimensionIndex].userValue = userValue;
	//openfolder = true;
	treeState = getState();
	reloadTree();
}

//function for buggy servers which do not support real iso8601 patterns for UTC :-(
function makeDateTimeBetter(dateTimeIso) {
	return dateTimeIso.replace('+00:00','Z');
}

function snapToDiscreteValue(userValue,extent) {
	//call it via sync ajax call :-(
	transformUrl = '../php/mod_transformTimeDimension.php?operation=snapToGrid&userValue='+encodeURIComponent(userValue);
	var response = $.ajax({
		url: transformUrl,
		type: "POST",
        	async: false,
		data: {
			'extent':extent,
		},
    		success: function (data) {
			return data;
		},
		error: function() {
			alert("An error occured!");
		}
	}).responseText;
	var jsonValue = JSON.parse(response);
	return jsonValue.data[0].value;
}

function openDimensionSelectHtml(j,k,l,dimensionIndex) {
	if(!j && !k&& !l){
		j=selectedMap;
		k=selectedWMS;
		l=selectedLayer;
	}
	$("#selectDimensionDialog").remove();
	var my = mb_mapObj[j].wms[k].objLayer[l];
	var myWms = mb_mapObj[j].wms[k];
	//extract/calculate discrete values for time!
	var userValue = mb_mapObj[j].wms[k].objLayer[l].layer_dimension[dimensionIndex].userValue; //not already defined in mapobj!!
	var extent = mb_mapObj[j].wms[k].objLayer[l].layer_dimension[dimensionIndex].extent;
	var dimdefault = mb_mapObj[j].wms[k].objLayer[l].layer_dimension[dimensionIndex].default;
	var dialogHtml = "<div id='timeline'></div>";
	$("<div id='selectDimensionDialog' title='<?php echo _mb('Select layer dimension');?>'><?php echo _mb('Please select a value for TIME. One single element can be dragged on timeline after selection. Scale may be altered by scrolling.');?>: </div>").dialog(
		{
			bgiframe: true,
			autoOpen: true,
			modal: false,
			width: 400,
			closeOnEscape: false,
			open: function(event, ui) { $( "div[aria-labelledby='ui-dialog-title-selectDimensionDialog']" ).children().children(".ui-dialog-titlebar-close").hide()}, //hide closing x - but only for element with special id!!!
			buttons: {
				"<?php echo _mb('Close');?>": function(){
					$('selectDimensionDialog').remove();
					$(this).dialog('close').remove();
					//delete container, items, timeline
				}
			}
		}
	);	
	$(dialogHtml).appendTo("#selectDimensionDialog");
	//fill timeline into div
	if (userValue !== undefined && userValue !== false && userValue !== "" && userValue !== "undefined") {
		userValue = makeDateTimeBetter(userValue);
		transformUrl = '../php/mod_transformTimeDimension.php?userValue='+encodeURIComponent(userValue)+'&default='+encodeURIComponent(dimdefault);
	} else {
		transformUrl = '../php/mod_transformTimeDimension.php?default='+encodeURIComponent(dimdefault);
	}
	  $.ajax({
		url: transformUrl,
		type: "POST",
		data: {
			'extent': extent,
		},
    		success: function (data) {
      			// hide the "loading..." message
     			//document.getElementById('loading').style.display = 'none';

      			// DOM element where the Timeline will be attached
     			var container = document.getElementById('timeline');

      			// Create a DataSet (allows two way data-binding)
      			var items = new vis.DataSet(data.data);

      			// Configuration for the Timeline
      			var options = data.options;

			//check if onclick or onmove should be enabled
			if (data.options.editable.updateTime !== 'undefined' && data.options.editable.updateTime == true) {
				options.onMove = function (item,callback) {
					//calculate new value for item start based on snapping to period from extent
					item.start = new Date(snapToDiscreteValue(item.start.toISOString(),extent));
					item.content = item.start.toISOString();
					myWms.gui_wms_dimension_time = makeDateTimeBetter(item.start.toISOString());
					setDimensionUserValue(j,k,l,dimensionIndex,myWms.gui_wms_dimension_time);
					Mapbender.modules[mod_treeGDE_map].setSingleMapRequest(mod_treeGDE_map,mb_mapObj[j].wms[k].wms_id);
					callback(item);
				};
			} else {
				options.editable = false;
				
			}
      			// Create a Timeline
      			var timeline = new vis.Timeline(container, items, options);
			//if (data.options.editable.updateTime == 'undefined' || data.options.editable.updateTime == false) {
				timeline.on('select', function (properties) {
					if (properties.event.type == "tap") {
						myWms.gui_wms_dimension_time = makeDateTimeBetter(timeline.itemsData._data[timeline.getSelection()].content);
						setDimensionUserValue(j,k,l,dimensionIndex,myWms.gui_wms_dimension_time);
						Mapbender.modules[mod_treeGDE_map].setSingleMapRequest(mod_treeGDE_map,mb_mapObj[j].wms[k].wms_id);
					}
				});
			//}	
    		},
    		error: function (err) {
      			//console.log('Error', err);
      			if (err.status === 0) {
        			alert('Failed to load json for configuration of timeline from server.\nPlease contact your portal administrator!');
      			}
      			else {
        			alert('Failed to load json from server.');
      			}
    		}
  	});
}

function initArray(){
	var parentObj = "";
	var controls="";
	if( mb_mapObj.length > 0){
		for(var i=0; i< mb_mapObj.length; i++){
			if( mb_mapObj[i].elementName == mod_treeGDE_map){
				for(var ii=0; ii< mb_mapObj[i].wms.length; ii++){
					if( mb_mapObj[i].wms[ii].gui_wms_visible === '1' ||  mb_mapObj[i].wms[ii].gui_wms_visible === 1){
						for(var iii=0; iii< mb_mapObj[i].wms[ii].objLayer.length; iii++){
							var temp =  mb_mapObj[i].wms[ii].objLayer[iii];
							if( mb_mapObj[i].wms[ii].objLayer[iii].layer_parent == ""){
								if(!temp.gui_layer_selectable == '1' && !temp.gui_layer_queryable == '1')
									continue;

								parentNode = arrNodes[0][0];
								if(eval("categories.wms_"+ mb_mapObj[i].wms[ii].wms_id) !== undefined)
									parentNode = eval("categories.wms_"+ mb_mapObj[i].wms[ii].wms_id);
								else
									eval("categories['wms_"+ mb_mapObj[i].wms[ii].wms_id+"'] = parentNode");

								var c_menu="[";
								if(reverseWms==true){
									if(menu.indexOf("wms_down")!=-1 && ii!= mb_mapObj[i].wms.length-1)c_menu+="menu_move_up,";
									if(menu.indexOf("wms_up")!=-1 && parentObj!="")c_menu+="menu_move_down,";
								}
								else{
									if(menu.indexOf("wms_up")!=-1 && parentObj!="")c_menu+="menu_move_up,";
									if(menu.indexOf("wms_down")!=-1 && ii!= mb_mapObj[i].wms.length-1)c_menu+="menu_move_down,";
								}
								if(menu.indexOf("remove")!=-1)c_menu+="menu_delete,";
//								if(menu.indexOf("wms_switch")!=-1)c_menu+="menu_wms_switch,";
								if(menu.indexOf("opacity_up")!=-1 && parseFloat( mb_mapObj[i].wms[ii].gui_wms_mapopacity) < 1)c_menu+="menu_opacity_up,";
								if(menu.indexOf("opacity_down")!=-1 && parseFloat( mb_mapObj[i].wms[ii].gui_wms_mapopacity) > 0)c_menu+="menu_opacity_down,";
								if(menu.indexOf("hide")!=-1)c_menu+="menu_hide";
								c_menu+="]";
								controls='';
								if(switchwms=='true')controls='<INPUT type="checkbox" title="' + msgObj.tooltipHandleWms + '"  onclick="handleSelectedWMS(\''+parentNode+'|wms_'+ mb_mapObj[i].wms[ii].wms_id+'\');" />';
								if(metadatalink == 'true'){
									controls+='<a class="metadata_link" href="'+defaultMetadataUrl + '&id='+temp.layer_uid+'"'+' target=\'_blank\' onclick="metadata_window = window.open(this.href,\'Metadata\',\'Width=700, Height=550,scrollbars=yes,menubar=yes,toolbar=yes\'); metadata_window.focus(); return false;"><img alt="'+msgObj.tooltipMetadata+'" title="'+msgObj.tooltipMetadata+'" src="'+imagedir+'/info.svg" /></a>';
								}
								addNode(parentNode,["wms_"+ mb_mapObj[i].wms[ii].wms_id,[temp.layer_currentTitle,((metadatalink=='true'&&wmsbuttons != 'true')?('javascript:openwindow(\"'+ defaultMetadataUrl + '&id='+temp.layer_uid+'\",'+metadataWidth+','+metadataHeight+');'):"javascript:select("+i+","+ii+","+iii+");"),,,temp.layer_currentTitle,eval(c_menu),controls,[i,ii,iii]]],false,false,reverseWms);
								parentObj = parentNode+"|wms_"+ mb_mapObj[i].wms[ii].wms_id;
							}
							if( mb_mapObj[i].wms[ii].objLayer[iii].layer_parent && (handlesublayer=="true"|| mb_mapObj[i].wms[ii].objLayer[iii].layer_parent=="0")){
								var parentLayer = "";
								var j = iii;
								while( mb_mapObj[i].wms[ii].objLayer[j].layer_parent!="0"){
									//find parent
									for(var jj=0; jj <  mb_mapObj[i].wms[ii].objLayer.length; jj++){
										if( mb_mapObj[i].wms[ii].objLayer[jj].layer_pos==parseInt( mb_mapObj[i].wms[ii].objLayer[j].layer_parent)){
											j=jj;
											break;
										}
									}
									parentLayer = "|" +  mb_mapObj[i].wms[ii].objLayer[j].layer_id + parentLayer;
								}
								if(temp.gui_layer_selectable == '1' || temp.gui_layer_queryable == '1'){
									var c_menu="[";
									if(reverseWms==true){
										if(menu.indexOf("layer_down")!=-1 && iii!= mb_mapObj[i].wms[ii].objLayer.length-1)c_menu+="menu_move_up,";
										if(menu.indexOf("layer_up")!=-1 && iii!=1)c_menu+="menu_move_down,";
									}
									else{
										if(menu.indexOf("layer_up")!=-1 && iii!=1)c_menu+="menu_move_up,";
										if(menu.indexOf("layer_down")!=-1 && iii!= mb_mapObj[i].wms[ii].objLayer.length-1)c_menu+="menu_move_down,";
									}
									if(menu.indexOf("metainfo")!=-1)c_menu+="menu_metalink,";
									if(menu.indexOf("zoom")!=-1 && temp.layer_epsg.length>0)c_menu+="menu_zoom,";
//									if(menu.indexOf("layer_switch")!=-1)c_menu+="menu_layer_switch,";
//									if(menu.indexOf("info_switch")!=-1)c_menu+="menu_info_switch,";
									if(menu.indexOf("hide")!=-1)c_menu+="menu_hide,";
									if(menu.indexOf("change_style")!=-1)c_menu+="menu_style,";
									if(menu.indexOf("legend")!=-1)c_menu+="menu_legend";
									c_menu+="]";

									controls = [];
									controls.push('<input type="checkbox"  title="' + msgObj.tooltipLayerVisible + '" ');
									if(temp.layer_name=="")
										controls.push('style="display:none;" ');
									if(temp.gui_layer_visible==='1' ||temp.gui_layer_visible===1){
										controls.push('checked ');
									}
									if(temp.gui_layer_selectable!='1')
										controls.push('disabled ');
									controls.push("onclick=\"local_handleSelectedLayer('"+mod_treeGDE_map+"','"+ mb_mapObj[i].wms[ii].wms_id+"','"+temp.layer_name+"','visible',this.checked?1:0);");
									if(ficheckbox == 'false')
										controls.push("local_handleSelectedLayer('"+mod_treeGDE_map+"','"+ mb_mapObj[i].wms[ii].wms_id+"','"+temp.layer_name+"','querylayer',this.checked?1:0);");
									controls.push("handleSelection('"+parentObj+parentLayer+"|"+temp.layer_id+"', 0);");
									controls.push("updateParent('"+parentObj+parentLayer+"');\" />");
									if(ficheckbox == 'true'){
										controls.push('<input type="checkbox" title="' + msgObj.tooltipLayerQuerylayer + '" ');
										if(temp.gui_layer_querylayer=='1')
											controls.push('checked ');
										if(temp.gui_layer_queryable!='1')
											controls.push('disabled ');
										controls.push("onclick=\"local_handleSelectedLayer('"+mod_treeGDE_map+"','"+ mb_mapObj[i].wms[ii].wms_id+"','"+temp.layer_name+"','querylayer',this.checked?1:0);");
										controls.push("handleSelection('"+parentObj+parentLayer+"|"+temp.layer_id+"', 1);\" />");
									}
									if(wmsbuttons == 'true'&&metadatalink == 'true'){
										controls.push('<a href="'+defaultMetadataUrl + '&id='+temp.layer_uid+'"'+' target=\'_blank\' onclick="metadata_window = window.open(this.href,\'Metadata\',\'Width=700, Height=550,scrollbars=yes,menubar=yes,toolbar=yes\');metadata_window.focus(); return false;"><img alt="'+msgObj.tooltipMetadata+'" title="'+msgObj.tooltipMetadata+'" src="'+imagedir+'/info.svg" /></a>');
									}
									if(datalink == 'true' && mb_mapObj[i].wms[ii].objLayer[iii].layer_dataurl != ''){
										controls.push('<a href="'+mb_mapObj[i].wms[ii].objLayer[iii].layer_dataurl+'"'+' target=\'_blank\' onclick="download_window = window.open(this.href,\'Download Data\',\'Width=450, Height=350,scrollbars=yes,menubar=yes,toolbar=yes\'); download_window.focus(); return false;"><img class="treegde2019" width="18" height="18" alt="'+msgObj.tooltipDownload+'" title="'+msgObj.tooltipDownload+'" src="'+imagedir+'/download.svg" /></a>');
									//
}
//if (typeof mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling == 'undefined') {				
//alert(mb_mapObj[i].wms[ii].objLayer[iii].layer_title+': '+JSON.stringify(mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling));
//}
									if(featuretypeCoupling == 'true' && mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling != ''){
//alert(mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling);
										//controls.push('<a href="'+mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling+'"'+' target=\'_blank\' onclick="featuretype_window = window.open(this.href,\'Featuretype Data\',\'Width=450, Height=350,scrollbars=yes,menubar=yes,toolbar=yes\'); featuretype_window.focus(); return false;"><img width="18" height="18" alt="'+msgObj.tooltipFeaturetypeCoupling+'" title="'+msgObj.tooltipFeaturetypeCoupling+'" src="'+imagedir+'/../gnome/accessories-dictionary.png" /></a>');
										controls.push('<img width="18" height="18" coupling="'+btoa(mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling)+'" onclick="alert(atob(this.getAttribute(\'coupling\')))" alt="'+msgObj.tooltipFeaturetypeCoupling+'" title="'+msgObj.tooltipFeaturetypeCoupling+'" src="'+imagedir+'/../osgeo_graphics/geosilk/application_view_columns.png" />');
										//controls.push('<img width="18" height="18" onclick="alert('+mb_mapObj[i].wms[ii].objLayer[iii].layer_featuretype_coupling+');" alt="'+msgObj.tooltipFeaturetypeCoupling+'" title="'+msgObj.tooltipFeaturetypeCoupling+'" src="'+imagedir+'/../osgeo_graphics/geosilk/application_view_columns.png" />');
									}
									//dimension buttons
									if (activatedimension == 'true') {
										timeDimensionAvailable = false;
										timeDimensionIndex = false;
										elevationDimensionAvailable = false;
										elevationDimensionIndex = false;
										//check for existence of time and/or elevation name attribute in dimension
										for (dimensionIndex = 0; dimensionIndex < mb_mapObj[i].wms[ii].objLayer[iii].layer_dimension.length; ++dimensionIndex) {
											switch (mb_mapObj[i].wms[ii].objLayer[iii].layer_dimension[dimensionIndex].name) {
												case "time":
													timeDimensionAvailable = true;
													timeDimensionIndex = dimensionIndex;
													timeUserValue = mb_mapObj[i].wms[ii].objLayer[iii].layer_dimension[dimensionIndex].userValue;
													break;
												case "elevation":
													elevationDimensionAvailable = true;
													elevationDimensionIndex = dimensionIndex;
													elevationUserValue = mb_mapObj[i].wms[ii].objLayer[iii].layer_dimension[dimensionIndex].userValue;
													break;
											} 
										}
										if (timeDimensionAvailable === true) {
											text = JSON.stringify(mb_mapObj[i].wms[ii].objLayer[iii].layer_dimension[timeDimensionIndex]);
											controls.push('<img onclick="openDimensionSelectHtml('+i+','+ii+','+iii+','+timeDimensionIndex+');" width="16" height="16" alt="'+msgObj.tooltipTimeDimension+'" title="Zeitpunkt wählen" style="cursor:pointer;margin:8px 5px -5px 0px" src="'+imagedir+'/clock.svg" />');	
											if (timeUserValue !== undefined && timeUserValue !== false && timeUserValue !== "" && timeUserValue !== "undefined") {
											controls.push('<b>'+timeUserValue+'</b>');
											//set gui_wms_dimension_time to value of layer - only one layer of a wms should have the userValue defined - if more have this, the last one will overwrite all earlier!
											mb_mapObj[i].wms[ii].gui_wms_dimension_time = timeUserValue;
											}
										}
									}
						
									var groupedImageStyle ='';
									if (temp.has_childs == true){
										groupedImageStyle ='verticaldots.svg';
									}
									else{
										groupedImageStyle ='verticaldots.svg';
									}
									addNode(parentObj + parentLayer, [temp.layer_id,[temp.layer_currentTitle,((metadatalink=='true'&&wmsbuttons != 'true')?('javascript:openwindow(\"'+ defaultMetadataUrl + '&id='+temp.layer_uid+'\",'+metadataWidth+','+metadataHeight+');'):"javascript:select("+i+","+ii+","+iii+");"),,((c_menu!='[]'&&temp.layer_name!="")?groupedImageStyle:null),temp.layer_currentTitle,eval(c_menu),controls.join(""),[i,ii,iii]]],false,false,false);
								}
							}
						}
					}
				}
			}
		}
	}
	initialized=true;
}

//https://stackoverflow.com/questions/4253367/how-to-escape-a-json-string-containing-newline-characters-using-javascript
// implement JSON stringify serialization
JSON.stringify = JSON.stringify || function (obj) {
    var t = typeof (obj);
    if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object
        var n, v, json = [], arr = (obj && obj.constructor == Array);
        for (n in obj) {
            v = obj[n]; t = typeof(v);
            if (t == "string") v = '"'+v+'"';
            else if (t == "object" && v !== null) v = JSON.stringify(v);
            json.push((arr ? "" : '"' + n + '":') + String(v));
        }
        var rawString = (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
       return rawString;
    }
};
function escape (key, val) {
    if (typeof(val)!="string") return val;

    var replaced = encodeURIComponent(val);
    return replaced;
}

JSON.stringifyEscaped = function(obj){
    return JSON.stringify(obj,escape);
}

function initWmsCheckboxen(){
	var hidden=0;
	if( mb_mapObj.length > 0){
		for(var i=0; i< mb_mapObj.length; i++){
			if( mb_mapObj[i].elementName == mod_treeGDE_map){
				for(var ii=0; ii< mb_mapObj[i].wms.length; ii++){
					if( mb_mapObj[i].wms[ii].gui_wms_visible === '1' ||  mb_mapObj[i].wms[ii].gui_wms_visible === 1){
						for(var iii=0; iii< mb_mapObj[i].wms[ii].objLayer.length; iii++){
							var temp =  mb_mapObj[i].wms[ii].objLayer[iii];
							if( mb_mapObj[i].wms[ii].objLayer[iii].layer_parent == ""){
								updateParent(arrNodes[0][0]+"|wms_"+ mb_mapObj[i].wms[ii].wms_id);
							}
						}
					}
					else if(ii<= parseInt(openfolder, 10)+hidden)
						hidden++;
				}
				closeAll();
				var openFolderIndex = parseInt(openfolder, 10) + hidden;
				if(treeState!='') {
					setState(treeState);
				}
				else if(openfolder!=='false' && openFolderIndex < mb_mapObj[i].wms.length && openFolderIndex >= 0) {

					setState(arrNodes[0][0]+"|wms_"+ mb_mapObj[i].wms[ openFolderIndex].wms_id);
				}
			}
		}
	}
}
