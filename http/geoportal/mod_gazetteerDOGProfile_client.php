<?php
# $Id: mod_gazetteerDOGProfile.php
# http://www.mapbender.org/GazetteerDOGProfile
# Copyright (C) 2009 OSGeo
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

require(dirname(__FILE__)."/../php/mb_validateSession.php");

include '../include/dyn_js.php';
include '../include/dyn_php.php';

$con = db_connect($DBSERVER,$OWNER,$PW);
db_select_db(DB,$con);
$sql = "SELECT e_target FROM gui_element WHERE e_id = 'gazetteerDOGProfile' AND fkey_gui_id = $1";
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
try {if(featuretypeGemeinden){}}catch(e) {featuretypeGemeinden = "dog:gemeinden";}
try {if(featuretypeStrassen){}}catch(e) {featuretypeStrassen = "dog:Strassen";}
try {if(featuretypeHauskoordinaten){}}catch(e) {featuretypeHauskoordinaten = "dog:Hauskoordinaten";}
try {if(searchAttrGemeinden){}}catch(e) {searchAttrGemeinden = "iso19112:geographicIdentifier";}
try {if(searchAttrStrassen){}}catch(e) {searchAttrStrassen = "dog:strassenschluessel,dog:strassenname";}
try {if(searchAttrHauskoordinaten){}}catch(e) {searchAttrHauskoordinaten = "iso19112:parent";}
try {if(gemeindenAttributes){}}catch(e) {gemeindenAttributes = "geographicIdentifier,parent,gemeindeschluessel";}
try {if(strassenAttributes){}}catch(e) {strassenAttributes = "geographicIdentifier,strassenname";}
try {if(hauskoordinatenAttributes){}}catch(e) {hauskoordinatenAttributes = "hausnummer,hausnummernzusatz";}
try {if(noResultMsg){}}catch(e) {noResultMsg = "No result.";}
try {if(serverErrorMsg){}}catch(e) {serverErrorMsg = "Server error.";}
try {if(markerImage){}}catch(e) {markerImage = "../img/marker_fett.gif";}

var geomArrayGemeinden = null;
var geomArrayStrassen = null;
var geomArrayHauskoordinaten = null;

var gazetteerStep = "searchGemeinden";
var searchString;
var searchFeaturetype;
var searchFor;
var showAttributeName;

var noResultMsg
var serverErrorMsg = "";

var gazetteerResultHighlight;
var highlightColor = "#cc33cc";

var currentMarker_mapframe1 = null;
var currentMarker_overview = null;
var permanentMarker = null;

var targetArray = targetString.split(",");
/**
 * get html content for gazetteer form
 */
function getHtmlForGazetteerForm () {
	mb_ajax_post("../geoportal/mod_gazetteerDOGProfile_server.php", {"command":"getHtml"}, function(jsCode, status) {
		if(status == 'success'){
			$("#gazetteerDOGProfile").html(jsCode);
		}
		else{
			$("#gazetteerDOGProfile").html("HTML could not be loaded.");
		}
	});
}

function normalizeString(str){
	str = str.replace(/\./g,'*');
    str = str.replace(/-/g,' ');
    str = str.toUpperCase();
    str = str.replace(/ß/g,'ß');
    str = str.replace(/Ä/g,'ä');
    str = str.replace(/Ö/g,'ö');
    str = str.replace(/Ü/g,'ü');
    
    return str;
} 

/**
 * search for Gemeinden in featuretype dog:gemeinden
 *
 */
function startGazetteer () {
	$("#gazetteerDOGHausnummer").empty();
	var styleProperties = {"position":"absolute", "top":"0px", "left":"0px", "z-index":100};
	gazetteerResultHighlight = new Highlight(targetArray, "gazetteerDOGProfileHighlight", styleProperties, 2);

	if(gazetteerStep == "searchStrassen"){
		searchFeaturetype = featuretypeStrassen;
		searchAttr = searchAttrStrassen;
//		var searchString1 = document.getElementById('selectResultsearchGemeinden').options[document.getElementById('selectResultsearchGemeinden').selectedIndex].value;
		var selectboxIndex = parseInt(document.getElementById('selectResultsearchGemeinden').value, 10);
		var showField = gemeindenAttributes.split(",");
		var searchString1 = geomArrayGemeinden.get(selectboxIndex).e.getElementValueByName(showField[2]);
		var searchString2 = document.getElementById('gazetteerDOGStrasse').value;
		if (searchString1 == "" || searchString2 == "") {
			alert("Bitte Ort und Straße angeben.");
			return false;
		}
		var singleSearchAttr = searchAttr.split(",");
		//searchFor = singleSearchAttr[0] + "=" + searchString1 + "|" + singleSearchAttr[1] + "=" + normalizeString(searchString2);
		searchFor = singleSearchAttr[0] + "=" + searchString1 + "|" + singleSearchAttr[1] + "=" + searchString2;
		showAttributeName = strassenAttributes;
	}
	else if(gazetteerStep == "searchHauskoordinaten") {
		searchFeaturetype = featuretypeHauskoordinaten;
		searchAttr = searchAttrHauskoordinaten;
//		searchString = document.getElementById('selectResultsearchStrassen').options[document.getElementById('selectResultsearchStrassen').selectedIndex].value;
		var selectboxIndex = parseInt(document.getElementById('selectResultsearchStrassen').value, 10);
		var showField = strassenAttributes.split(",");
		searchString = geomArrayStrassen.get(selectboxIndex).e.getElementValueByName(showField[0]);

		if (searchString == "") {
			alert("Bitte Straße angeben.");
			return false;
		}
		searchFor = searchAttr + "="  + searchString;
		showAttributeName = hauskoordinatenAttributes;
	}
	else{
		searchString = document.getElementById('gazetteerDOGOrtPlz').value;
		if(isNaN(searchString) == false) {
			if(searchString.length != 5) {
				alert("PLZ bitte vollständig eingeben.");
				return false;
			}
			searchFeaturetype = featuretypePLZ;
			searchAttr = searchAttrPLZ;
			searchFor = searchAttr + "="  + searchString;
			showAttributeName = plzAttributes;
			gazetteerStep = "searchPlz";
		}
		else{
			searchFeaturetype = featuretypeGemeinden;
			searchAttr = searchAttrGemeinden;
			//searchFor = searchAttr + "="  + normalizeString(searchString);
			searchFor = searchAttr + "="  + searchString;
			if (searchString == "") {
				alert("Bitte Ort/PLZ eingeben.");
				return false;
			}
			showAttributeName = gemeindenAttributes;
		}
	}

	var parameters = {
		"command" : gazetteerStep,
		"wfsUrl" : wfsUrl,
//		"authUserNameWfs" : authUserName,
//		"authUserPasswordWfs" : authUserPassword,
		"searchFeaturetype" : searchFeaturetype,
		"searchFor" : searchFor
	};

	document.getElementById("gazetteerProgressWheel").innerHTML = "<img src='../img/indicator_wheel.gif'></img>Suche läuft...";
	$.post("../geoportal/mod_gazetteerDOGProfile_server.php", parameters, function(jsCode, status) {
		document.getElementById("gazetteerProgressWheel").innerHTML = "";
		if(status == 'success'){
			try {
				if(typeof(jsCode) == 'string'){
					alert(jsCode);
					return false;
				}
				else if(typeof(jsCode) == 'object'){
					var geoObj = jsCode;
				}
				else{
					var geoObj = parent.$.parseJSON(jsCode);
				}
			}
			catch (exc) {
				alert("Invalid data returned from service.");
				return false;
			}
					
       		if (typeof geoObj === "undefined") {
				alert("Invalid data returned from service.");
			}
			else if (jsCode) {
				if (typeof(geoObj) == 'object') {
					if(gazetteerStep == "searchHauskoordinaten") {
						if(typeof geoObj.errorMessage != "undefined"){
							alert(noResultMsg);
							gazetteerStep = "searchHauskoordinaten";
						}
						else {
							geomArrayHauskoordinaten = new GeometryArray();
							geomArrayHauskoordinaten.importGeoJSON(geoObj);
							if(geomArrayHauskoordinaten.count() > 0) {
								$("#gazetteerDOGHausnummer").html(showHausnummern(geomArrayHauskoordinaten));
							}
							else {
								$("#gazetteerDOGHausnummer").show();
								$("#gazetteerDOGHausnummer").css("color","#000000");
								var resultHtml = "Hausnummern:<br>";
								resultHtml += $("#selectResultsearchStrassen option:selected").html() + "<br>";
								resultHtml += "Kein Ergebnis<br>";
								$("#gazetteerDOGHausnummer").html(resultHtml);
							}
						}
					}
					else if(gazetteerStep == "searchStrassen") {
						if(typeof geoObj.errorMessage != "undefined"){
							alert(noResultMsg);
							$("#gazetteerDOGStrasse").val("");
							$("#gazetteerDOGStrasse").focus();
							gazetteerStep = "searchStrassen";
						}
						else {
							geomArrayStrassen = new GeometryArray();
							geomArrayStrassen.importGeoJSON(geoObj);
							if(geomArrayStrassen.count() < 1) {
								alert(noResultMsg);
								$("#gazetteerDOGStrasse").focus();
								return;	
							}
							$("#gazetteerDOGStrasseField").empty();
							$("#gazetteerDOGStrasseField").html(showResult(geomArrayStrassen));
							//wenn nur ein Suchergebnis vorhanden, führe Zoom und startGazetteer direkt aus
							if(geomArrayStrassen.count() == 1) {
								zoomToStrassen(gazetteerStep, 0);
								gazetteerStep = "searchHauskoordinaten";
								startGazetteer();
							}
							else {
								gazetteerStep = "searchHauskoordinaten";
								$("#gazetteerDOGHausnummer").show();
								$("#gazetteerDOGHausnummer").css("color","#808080");
								var resultHtml = "Hausnummern:<br>";
								$("#gazetteerDOGHausnummer").html(resultHtml);
							}
						}
					}
					else {
						if(typeof geoObj.errorMessage != "undefined"){
							alert(noResultMsg);
							$("#gazetteerDOGOrtPlz").val("");
							$("#gazetteerDOGOrtPlz").focus();
							gazetteerStep = "searchGemeinden";
						}
						else {
							geomArrayGemeinden = new GeometryArray();
							geomArrayGemeinden.importGeoJSON(geoObj);
							if(geomArrayGemeinden.count() < 1) {
								alert(noResultMsg);
								$("#gazetteerDOGOrtPlz").focus();
								return;	
							}
							if(gazetteerStep == "searchPlz") {
								showResultPlz(geomArrayGemeinden);
							}
							else {
								$("#gazetteerDOGOrtField").empty();
								$("#gazetteerDOGOrtField").html(showResult(geomArrayGemeinden));
								//wenn nur ein Suchergebnis vorhanden, führe Zoom und setStrassenField direkt aus
								if(geomArrayGemeinden.count() == 1) {
									zoomToGemeinden(gazetteerStep, 0);
									setStrassenField();
									//$("#gazetteerDOGStrasse").focus();
								}
								gazetteerStep = "searchStrassen";
							}
						}
					}
				}
	       		else {
					alert(noResultMsg);
				}		
       		}
       	}
       	else {
       		alert(serverErrorMsg);
       	}
	},"json");

	return false;
}

var sortFunction = function (a, b) {
	if (a.title.toUpperCase() < b.title.toUpperCase() ) {
		return -1;
	}
	return 1;
}

var sortIntFunction = function (a, b) {
	if (a.title < b.title) {
		return -1;
	}
	return 1;
}

var sortHausnummernFunction = function (a, b) {
	if (a.showTitle.toUpperCase() > b.showTitle.toUpperCase()) {
		return -1;
	}
	return 1;
}

/**
 * show search results
 *
 */
function showResult(geomArray) {
	var selectHtml = "";
	var attributeNames = showAttributeName.split(",");
	if (geomArray != null && geomArray.count() > 0) {
		if(gazetteerStep == "searchStrassen") {
			selectHtml += "<select onchange=\"zoomToStrassen('"+gazetteerStep+"',this.value);startGazetteer();\" id='selectResult" + gazetteerStep + "'>";
			
			if(geomArray.count() > 1) {
				selectHtml += "<option value=''>" + geomArray.count() + " Treffer:</option>";
			}

			var optionArray = [];
			for (var i = 0 ; i < geomArray.count(); i ++) {
				optionArray.push({
					value: i,
					title: geomArray.get(i).e.getElementValueByName(attributeNames[0])
				})
			}

			optionArray.sort(sortFunction);

			for (var i = 0; i < optionArray.length; i++) {
				selectHtml += "<option value='" + optionArray[i].value + "'>";
				selectHtml += geomArray.get(i).e.getElementValueByName(attributeNames[1]);
				selectHtml += "</option>";
			}
		}
		else {
			selectHtml += "<select onchange=\"zoomToGemeinden('"+gazetteerStep+"', this.value);setStrassenField();\" id='selectResult" + gazetteerStep + "'>";
			if(geomArray.count() > 1) {
				selectHtml += "<option value=''>" + geomArray.count() + " Treffer:</option>";
			}
			
			var optionArray = [];
			for (var i = 0 ; i < geomArray.count(); i ++) {
				optionArray.push({
					value: i,
					title: geomArray.get(i).e.getElementValueByName(attributeNames[0]) +
						" (" + geomArray.get(i).e.getElementValueByName(attributeNames[1]) + ")"
				})
			}

			optionArray.sort(sortFunction);

			for (var i = 0; i < optionArray.length; i++) {
				selectHtml += "<option value='" + optionArray[i].value + "'>";
				selectHtml += optionArray[i].title;
				selectHtml += "</option>";
			}
		}

		selectHtml += "</select>";
		return selectHtml;
	}
	else {
		return false;
	}
}

function showHausnummern(geomArray) {
	var resultHtml = "";
	var attributeNames = showAttributeName.split(",");
	if (geomArray != null && geomArray.count() > 0) {
		var spanArray = [];

		for (var i = 0 ; i < geomArray.count(); i ++) {
			var zusatz = geomArray.get(i).e.getElementValueByName(attributeNames[1])?geomArray.get(i).e.getElementValueByName(attributeNames[1]) : "";
			var hsnrTitle = geomArray.get(i).e.getElementValueByName(attributeNames[0]) + zusatz;
							
			spanArray.push({
				value: i,
				title: parseInt(geomArray.get(i).e.getElementValueByName(attributeNames[0]), 10), 
				showTitle: hsnrTitle, 
				htmlOpen: "<span style='cursor:pointer;padding:2px;' " +
						  "	onclick=\"setResult('click',"+i+");\" " +
						  " onmouseover=\"setResult('over',"+i+");\"  " +
						  " onmouseout=\"setResult('out',"+i+");\">",
				htmlClose: "</span> "
			})
		}

		spanArray.sort(sortHausnummernFunction);
		spanArray.sort(sortIntFunction);

		$("#gazetteerDOGHausnummer").show();
		$("#gazetteerDOGHausnummer").css("color","#000000");
		resultHtml += "Hausnummern:<br>";
		resultHtml += $("#selectResultsearchStrassen option:selected").html() + "<br>";
		
		if(geomArray.count() < 1) {
			resultHtml += "Kein Ergebnis";
		}
			
		for (var i = 0 ; i < spanArray.length; i ++) {
			resultHtml += spanArray[i].htmlOpen;
			resultHtml += spanArray[i].showTitle;
			resultHtml += spanArray[i].htmlClose;
		}
		return resultHtml;
	}
	else {
		return false;
	}
}

function showResultPlz(geomArray) {
	var attributeNames = showAttributeName.split(",");
	if (geomArray != null && geomArray.count() == 1) {
		$("#gazetteerDOGOrtPlz").val(geomArray.get(0).e.getElementValueByName(attributeNames[0]));
		mb_repaintScale(targetArray[0],geomArray.get(0).get(0).get(0).x,geomArray.get(0).get(0).get(0).y,"20000");
		return true;
	}
	else {
		return false;
	}
}

function zoomToGemeinden(gazetteerStep, index) {
//	var searchResultIndex = parseInt(document.getElementById("selectResult"+gazetteerStep).selectedIndex)-1;
	if (index === "") {
		return;
	}
	var searchResultIndex = parseInt(index, 10);
	var currentGeom = geomArrayGemeinden.get(searchResultIndex);
	mb_repaintScale(targetArray[0],currentGeom.get(0).get(0).x,currentGeom.get(0).get(0).y,"20000");
}

function zoomToStrassen(gazetteerStep, index) {
//	var searchResultIndex = parseInt(document.getElementById("selectResult"+gazetteerStep).selectedIndex)-1;
	if (index === "") {
		return;
	}
	var searchResultIndex = parseInt(index, 10);
	var currentGeom = geomArrayStrassen.get(searchResultIndex);
	mb_repaintScale(targetArray[0],currentGeom.get(0).get(0).x,currentGeom.get(0).get(0).y,"2000");
}

/*
* event -> {over || out || click}
* geom -> commaseparated coordinates x1,y1,x2,y2 ...
*/
function setResult(event, index) {
	var currentGeom = geomArrayHauskoordinaten.get(index);

	if (event == "over") {
		setMarker("mapframe1", currentGeom.get(0).get(0).x, currentGeom.get(0).get(0).y);
	}
	else if (event == "out"){
		delMarker("mapframe1");
	}
	else if (event == "click"){
		mb_repaintScale(targetArray[0],currentGeom.get(0).get(0).x,currentGeom.get(0).get(0).y,"2000");
		
		//setze zusätzlich einen permanenten Marker
		setMarker("mapframe1", currentGeom.get(0).get(0).x, currentGeom.get(0).get(0).y, "permanent");
	}
	return true;
}

function newSearch() {
	if(geomArrayGemeinden !== null) {
		if(geomArrayGemeinden.count()>0) {
 			geomArrayGemeinden.empty();
 		}
	}
	if(geomArrayStrassen !== null) {
		if(geomArrayStrassen.count()>0) {
 			geomArrayStrassen.empty();
 		}
	}
	if(geomArrayHauskoordinaten !== null) {
 		if(geomArrayHauskoordinaten.count()>0) {
 			geomArrayHauskoordinaten.empty();
 		}
	}
	getHtmlForGazetteerForm();
	gazetteerStep = "searchGemeinden";
	delMarker();
}

function setStrassenField () {
	$("#gazetteerDOGHausnummer").empty();
	$("#gazetteerDOGStrasseField").empty();
	document.getElementById("gazetteerDOGStrasseField").innerHTML = '<input value="Straße" id="gazetteerDOGStrasse" class="gazetteerDOG" type="text" name="gazetteerDOGStrasse"><input type="submit" name="DOGGazetteerSearchButton" id="DOGGazetteerSearchButton" value="Suche">';
	gazetteerStep = "searchStrassen";
	//$("#gazetteerDOGStrasse").css("color", "#808080");
	$("#gazetteerDOGStrasse").focus(function () {
		$("#gazetteerDOGStrasse").val("");
		$("#gazetteerDOGStrasse").css("color", "#000000");
	});
}

function delMarker() {
	var frameName;
	if (arguments.length === 1) {
		frameName = arguments[0];
		if (frameName == "overview") {
			if (currentMarker_overview !== null) {
				currentMarker_overview.remove();
			}
		}
		else if (frameName === "mapframe1") {
			if (currentMarker_mapframe1 !== null) {
				currentMarker_mapframe1.remove();
			}		
		}
		else if (frameName === "permanent") {
			if (permanentMarker !== null) {
				permanentMarker.remove();
			}		
		}
		return;
	}
	if (currentMarker_overview !== null) {
		currentMarker_overview.remove();
	}
	if (currentMarker_mapframe1 !== null) {
		currentMarker_mapframe1.remove();
	}		
	if (permanentMarker !== null) {
		permanentMarker.remove();
	}		
}	

function setMarker(frameName,x,y) {	
   var scale = mb_getScale(frameName);

	 if (scale < 5001) {
	 	var width  = 20;
	 	var height = 20;
	 }
	 if (scale >= 5001 && scale < 25001) {
	 	var width  = 10;
	 	var height = 10;
	 }
	 if (scale > 25001) {
	 	var width  = 5;
	 	var height = 5;
	 }

	if (arguments.length === 4 && arguments[3] === "permanent") {
		if (permanentMarker !== null) {
			permanentMarker.remove();
		}
		permanentMarker = new Mapbender.Marker(new Mapbender.Point(x, y), Mapbender.modules.mapframe1, {
			img: {
				url: markerImage,
				width: width,
				height: height,
				offset: new Mapbender.Point(-parseInt(width / 2, 10), -parseInt(height / 2, 10))
			}
		});
	}
	else {
		if (frameName == "overview") {
			if (currentMarker_overview !== null) {
				currentMarker_overview.remove();
			}
			currentMarker_overview = new Mapbender.Marker(new Mapbender.Point(x, y), Mapbender.modules.overview, {
				img: {
					url: "../img/redball.gif",
					width: width,
					height: height,
					offset: new Mapbender.Point(-parseInt(width / 2, 10), -parseInt(height / 2, 10))
				}
			});
		}
		else {
			if (currentMarker_mapframe1 !== null) {
				currentMarker_mapframe1.remove();
			}
			currentMarker_mapframe1 = new Mapbender.Marker(new Mapbender.Point(x, y), Mapbender.modules.mapframe1, {
				img: {
					url: "../img/marker_fett.gif",
					width: width,
					height: height,
					offset: new Mapbender.Point(-parseInt(width / 2, 10), -parseInt(height / 2, 10))
				}
			});
		}
	}
}

mb_registerInitFunctions("getHtmlForGazetteerForm()");
