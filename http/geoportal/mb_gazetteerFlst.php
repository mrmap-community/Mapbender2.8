<?php
/**
 * Package: mb_gazetteerFlst.php
 *
 * Description:
 * Gazetteer for Flurstücke
 *
 *
 * Files:
 *  - http/geoportal/mb_gazetteerFlst.php
 *  - http/geoportal/mb_gazetteerFlst_server.php
 *  - conf/gazetteerFlst.conf
 *
 * SQL:
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('<gui_id>','gazetteerFlst',2,1,'gazetteer for Flurstuecke','Flurstücksuche','div','','',1,1,1,1,4,'visibility:hidden;padding-left:10px;','<div class="ui-widget">
 * > 	<label for="GMK_NAME">Gemarkungsname:</label>
 * >	<input title="Pflichtfeld, bitte geben Sie mindestens 3 Anfangsbuchstaben im Feld Gemarkungsname ein, nach Auswahl wird die Gemarkungsnummer automatisch eingetragen." id="GMK_NAME" style="width:120px;"/><span title="Pflichtfeld, bitte geben Sie mindestens 3 Anfangsbuchstaben im Feld Gemarkungsname ein, nach Auswahl wird die Gemarkungsnummer automatisch eingetragen.">*</span>
 * > </div>
 * > <div class="ui-widget">
 * >	<label for="GMK_NR">Gemarkungsnummer:</label>
 * >	<input id="GMK_NR" style="width:120px;"/>
 * > </div>
 * > <div class="ui-widget">
 * >	<label style="width:180px;" for="FLUR_NR">Flurnummer:</label>
 * >	<select id="FLUR_NR">
 * >		<option value="">---</option>		
 * >	</select>
 * > </div>
 * > <div class="ui-widget">
 * >	<label style="width:180px;" for="FLZ">Zähler/Nenner</label>
 * >	<select id="FLZ">
 * >		<option value="">---</option>		
 * >	</select>
 * >	<select id="FLN">
 * >		<option value="">---</option>		
 * >	</select>
 * > </div>
 * ><div class="ui-widget">
 * >	<input disabled="disabled" style="margin-top:5px" type="button" value="Gehe zu" id="zoomToObj" />
 * >	<input style="margin-top:5px" type="button" value="Auswahl aufheben" id="removeSelection" />
 * > </div>','div','../geoportal/mb_gazetteerFlst.php','','mapframe1,overview','wz-graphics','');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('<gui_id>', 'gazetteerFlst', 'searchCss', '.ui-autocomplete-loading { background: white url(''../img/indicator_wheel.gif'') right center no-repeat; }', '' ,'text/css');
 *
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
include '../include/dyn_js.php';
include '../include/dyn_php.php';
$con = db_connect($DBSERVER,$OWNER,$PW);
db_select_db(DB,$con);
$sql = "SELECT e_target FROM gui_element WHERE e_id = 'gazetteerFlst' AND fkey_gui_id = $1";
$v = array($gui_id);
$t = array('s');
$res = db_prep_query($sql, $v, $t);
$cnt = 0;
while($row = db_fetch_array($res)){
	$e_target = $row["e_target"];
	$cnt++;
}
echo "var targetString = '" . $e_target . "';";
?>

var gazetteerResultHighlight;
var highlightColor = "#cc33cc";
var targetArray = targetString.split(",");
var styleProperties = {"position":"absolute", "top":"0px", "left":"0px", "z-index":70};



Mapbender.events.init.register(function () {

	requestGeometryHighlight = new Highlight(targetArray, "gazetteerFlst", styleProperties, 2);
	//lösche alle abhängigen Eingaben bei Klick in Feld gemarkungsschlüssel
	$("#GMK_NR").click(function () {
		$("#GMK_NR").val("");
		$("#GMK_NAME").val("");
		$("#FLUR_NR").empty().append("<option value=''>---</option>");
		$("#FLZ").empty().append("<option value=''>---</option>");
		$("#FLN").empty().append("<option value=''>---</option>");
		$("#zoomToObj").attr("disabled","true");
	});
	
	//lösche alle abhängigen Eingaben bei Klick in Feld gemarkungsname
	$("#GMK_NAME").click(function () {
		$("#GMK_NR").val("");
		$("#GMK_NAME").val("");
		$("#FLUR_NR").empty().append("<option value=''>---</option>");
		$("#FLZ").empty().append("<option value=''>---</option>");
		$("#FLN").empty().append("<option value=''>---</option>");
		$("#zoomToObj").attr("disabled","true");
	});
	
	//lösche alle Eingaben + Highlight
	$("#removeSelection").click(function () {
		$("#GMK_NR").val("");
		$("#GMK_NAME").val("");
		$("#FLUR_NR").empty().append("<option value=''>---</option>");
		$("#FLZ").empty().append("<option value=''>---</option>");
		$("#FLN").empty().append("<option value=''>---</option>");
		$("#zoomToObj").attr("disabled","true");
		delhighlight();
	});
	
	//autocomplete auf Gemarkungsschlüssel
	$("#GMK_NR").autocomplete({
		disabled: false,
		source: "../geoportal/mb_gazetteerFlst_server.php?command=getGmkNr",
		minLength: 3,
		select: function( event, ui ) {
			$("#GMK_NR").val(ui.item.value);
			$("#GMK_NAME").val(ui.item.gmkName);
			
			//selektiere Fluren
			getFluren();
		}
	});
	
	//autocomplete auf Gemarkung
	$("#GMK_NAME").autocomplete({
		disabled: false,
		source: "../geoportal/mb_gazetteerFlst_server.php?command=getGmkName",
		minLength: 3,
		select: function( event, ui ) {
			$("#GMK_NAME").val(ui.item.value);
			$("#GMK_NR").val(ui.item.gmkNr);
			
			//selektiere Fluren
			getFluren();
		}
	});	
	
	//auf Auswahl der Flur selektiere Flz aus WFS
	$("#FLUR_NR").change(function () {
		getFlz();
	});
	
	//auf Auswahl Flz selektiere Fln aus WFS
	$("#FLZ").change(function () {
		getFln();
	});
	
	//auf Klick Gehe zu zoome auf hinterlegte Geometrie
	$("#zoomToObj").click(function () {
		$.post("../geoportal/mb_gazetteerFlst_server.php", {
                command : "getGeomForFlst",
        		gmkNr : $("#GMK_NR").val(),
        		flurNr : $("#FLUR_NR").val(),
                flz : $("#FLZ").val(),
                fln : $("#FLN").val(),
                srs : Mapbender.modules.mapframe1.getSRS()
            }, function (jsCode, status) {
            if(jsCode) {
		var geoObj = jsCode;
		geomArrayFluren = new GeometryArray();
		geomArrayFluren.importGeoJSON(geoObj);	
		var currentGeom = geomArrayFluren.get(0);
        mb_repaintScale('mapframe1',currentGeom.get(0).get(0).x,currentGeom.get(0).get(0).y,"2500");
		requestGeometryHighlight.add(currentGeom);
		requestGeometryHighlight.paint();
        Mapbender.events.afterMapRequest.register(function () {
            requestGeometryHighlight.paint()
	        });
            }
        });	
    });
	
	function delhighlight(){
        if (requestGeometryHighlight !== null) {
		requestGeometryHighlight.clean();
		geomArrayFluren.empty(); 
	    delete geomArrayFluren;
		//delete requestGeometryHighlight;
		//delete currentGeom;
	};
    } 
	
	function getFluren() {
		$.post("../geoportal/mb_gazetteerFlst_server.php", {
                command : "getFluren",
        		gmkNr : $("#GMK_NR").val()
            }, function (jsCode, status) {
            if(jsCode) {	
            	if(typeof jsCode == "object") {
            		$("#FLUR_NR").empty();
            		for (var i = 0; i < jsCode.length; i++) {
						var optionVal = jsCode[i];
		                var optionName = jsCode[i];
		                var optionHtml = "<option value='" + optionVal + "'>" + optionName + "</option>";
		                $("#FLUR_NR").append(optionHtml);
				 	}
				 	
				 	getFlz();
            	}
            	else {
            		alert(jsCode);
                    return false;
            	}
           	}
        });	
	}
	
	function getFlz() {
		$.post("../geoportal/mb_gazetteerFlst_server.php", {
                command : "getFlz",
                gmkNr : $("#GMK_NR").val(),
                flurNr : $("#FLUR_NR").val()
            }, function (jsCode, status) {
            if(jsCode) {	
            	if(typeof jsCode == "object") {
            		$("#FLZ").empty();
            		for (var i = 0; i < jsCode.length; i++) {
						var optionVal = jsCode[i];
		                var optionName = jsCode[i];
		                var optionHtml = "<option value='" + optionVal + "'>" + optionName + "</option>";
		                $("#FLZ").append(optionHtml);
				 	}
				 	
				 	getFln();
            	}
            	else {
            		alert(jsCode);
                    return false;
            	}
           	}
        });
	}
	
	function getFln() {
		$.post("../geoportal/mb_gazetteerFlst_server.php", {
                command : "getFln",
                gmkNr : $("#GMK_NR").val(),
                flurNr : $("#FLUR_NR").val(),
                flz : $("#FLZ").val()
            }, function (jsCode, status) {
            if(jsCode) {	
            	if(typeof jsCode == "object") {
            		$("#FLN").empty();
            		for (var i = 0; i < jsCode.length; i++) {
            			if(jsCode[i].id) {
    						var optionVal = jsCode[i].id;
    		                var optionName = jsCode[i].id;
    		                var optionHtml = "<option value='" + optionVal + "'>" + optionName + "</option>";
    		                $("#FLN").append(optionHtml);
		                }
		                else {
		                	var optionHtml = "<option value=''>---</option>";
    		                $("#FLN").append(optionHtml);
		                }
				 	}
				 	$("#zoomToObj").removeAttr("disabled");
				}
            	else {
            		alert(jsCode);
                    return false;
            	}
           	}
        });
	}
	
	this.openSearchDialogFlst = function () {
		alert("öffnen");
	};
});


