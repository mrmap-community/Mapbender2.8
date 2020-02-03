/* ++++++++++++++++++++++++++++++++++++++++++ Openlayers JQuery +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  */

//Initialisierung
$(document).ready(function() {
	
    // Start with the map page
    if (window.location.hash && window.location.hash!='#mappage') {
        $.mobile.changePage($("#mappage"),pageTransition);
    }	
	
	//Openlayers Initialisierung (ngms_base.js)
	initmap();
	
	//Soll Zoomselector dargestellt werden?
	if(zoomSelect){
		$("#zoomscale").css('visibility','visible');
		//Selectmenue aus Array erzeugen
		createZoomselect();	
		//Scalelevel anzeigen
		map.events.register("moveend", null, displayZoom);
	}
	
	
	//neuer OpenLayers Control für Featureinfo
	clickCtrl = new OpenLayers.Control.Click();
	map.addControl( clickCtrl );
	clickCtrl.activate();	
	
	//Aktivierte Layer Checken
	checkLayers();
	
	//Events den einzelnen Werkzeugen zuweisen
	
	//Automatisches Wechseln der Ebenen
	$("#autolayerchange").change(function(){
        directLayerChange = this.value;
    });
	
	//Menü Buttons
	$("#layerbut").click(function(){
        $.mobile.changePage($("#layerpage"),pageTransition);
    });
	
	$("#searchbut").click(function(){
        $.mobile.changePage($("#searchpage"),pageTransition);
    });
	
	$("#helpbut").click(function(){
        $.mobile.changePage($("#helppage"),pageTransition);
    });
	
	$("#gearbut").click(function(){
        $.mobile.changePage($("#gearpage"),pageTransition);
    });
	
	$("#measurelinebut").click(function(){
		toggleMeasure('line');
    });
	
	$("#measurepolybut").click(function(){
        toggleMeasure('polygon');
    });
	
    // Mapzoom Buttons 
    $("#ovbut").click(function(){
        map.zoomToExtent(map.maxExtent);
    });
	
    $("#zoominbut").click(function(){
        map.zoomIn();
    });
	
    $("#zoomoutbut").click(function(){
        map.zoomOut();
    });
	
    	//Nachrichtenfenster schließen
    	$("#xheader").click(function(){
        	$('#markerhint').css('visibility','hidden');
			vector_marker.removeAllFeatures();
   	 });
	
	//Nachrichtenfenster schließen
	$("#mheader").click(function(){
        	$('#measurehint').css('visibility','hidden');
		toggleMeasure('off');
    	});
	
	//Suchbutton
	$('#searchformbut').click(function() {
			searchCall();
    });
	
	//Suchfeld
	$(document).on('keydown', '#searchfield', function(e) {
			if(e.keyCode === 13){
				searchCall();
			}
	});
	
	//zuück zur Karte-Button
	$(".mapbackbut").click(function(){
		$.mobile.changePage($("#mappage"),pageTransition);
		$('body').css('overflow', 'hidden');
    	});

	//Popup für erweitertes Popup-Menü initialisieren und öffnen
	$("#popupMenu").popup();
	$("#menubut").click(function(){	  
		$("#popupMenu").popup( "open" ); 	
    	});

	//Popup für Geolocation Dialog öffnen
	$("#popupMenu_gps").popup();
	$("#locatebut").click(function(){
		$("#popupMenu_gps").popup( "open");
		/*
		//Ausrichtung Popup links oben berechnen und mit hilfsdiv popuppos ausrichten
		var popupheight = $("#popupMenu_gps").height() / 2 + 68;
		$('#popuppos').css({top: popupheight+'px'});
		$("#popupMenu_gps").popup( "open", {x:0,y:0,positionTo:'#popuppos'});
		*/
    	});
	
	
	//GPS Status ändern
	$("#gpsstatus").change(function(){
        var mystatus = this.value;
		if(mystatus === "on"){
			startgpsWatch();
		}
		else{
			stopgpsWatch();
		}
   	 });

	//Geolocation Aktivieren
/*    $("#locatebut").click(function(){
		setMarkerhint(window.lang.convert('Meldung:'),window.lang.convert('bitte warten...'));
        var control = map.getControlsBy("id", "locate-control")[0];
        if (control.active) {
            control.getCurrentLocation();
        } else {
            control.activate();
        }
    });	*/


	//Baselayer einstellen
	$(".baselayer_check").click(function() {
		//alle zurücksetzen
		$(".baselayer_check").css("background-image","url(img/ico_basecheck.png)");
		$(this).css("background-image","url(img/ico_basecheck_active.png)");

		for (var i = 0; i < map.layers.length; i++) {		
			if($(this).attr("id") === map.layers[i].name){
				map.setBaseLayer(map.layers[i]);
			}
		}
		
		clearanimation();
		checktranspage();
	});
	
	//Overlays einstellen
	$(".layer_check").click(function() {
		for (var i = 0; i < map.layers.length; i++) {		
			if($(this).attr("id") === map.layers[i].name){
				var mylayerindex = i;
			}
		}		
		if(map.layers[mylayerindex].getVisibility()){
			map.layers[mylayerindex].setVisibility(false);
			$(this).css("background-image","url(img/ico_check.png)");
		}
		else{
			map.layers[mylayerindex].setVisibility(true);
			$(this).css("background-image","url(img/ico_check_active.png)");
		}
		
		clearanimation();
		checktranspage();	
	});
	
	//POI-Overlays einstellen
	$(".poi_check").click(function() {
		var activepoilayer = poilayer.params.LAYERS;
		var activepoiarray = [];
		//console.log("Vorher: " + activepoilayer);
		if(activepoilayer != ""){
			var activepoiarray = activepoilayer.split(',');
		}
		//wenn Layer schon im POI-Layer aktiv, Layer entfernen
		if(jQuery.inArray($(this).attr("id"), activepoiarray) != -1){
			for (var i = 0; i < activepoiarray.length; i++) {		
				if($(this).attr("id") === activepoiarray[i]){
					activepoiarray.splice(i,1); 
				}
			}
			$(this).css("background-image","url(img/ico_check.png)");
		}
		//sonst hinzufügen
		else{
			activepoiarray.push($(this).attr("id"));
			$(this).css("background-image","url(img/ico_check_active.png)");
		}

		activepoilayer = activepoiarray.join(',');
		//console.log("Nacher: " + activepoilayer);		
		poilayer.mergeNewParams({'layers': activepoilayer});
		//sichtbar machen, wenn mindestens 1 Layer aktiv
		if(activepoiarray.length > 0){
			poilayer.setVisibility(true);
		}		
		poilayer.redraw();
		
		clearanimation();		
		checktranspage();
		});
	
	
	//Query-Layersetzen
	$(".query_check").click(function() {
		//alle zurücksetzen
		$(".query_check").css("background-image","url(img/ico_checkop.png)");
		//angeklickten aktiv setzen
		$(this).css("background-image","url(img/ico_checkop_active.png)");	
		var qstr = $(this).attr("id");
		var qlayer = qstr.substring(0,qstr.length-6);
		$('#queryselect').val(qlayer);
		clearanimation();		
	});	
	
	//CSS Animationen Layersteuerung
	$(".query_check").touchstart(function() {
		$(this).css("background-color","#808080");
	});	
	
	$(".layer_check").touchstart(function() {
		$(this).css("background-color","#808080");
		$(this).css("color","#FFFFFF");
	});
	
	$(".baselayer_check").touchstart(function() {
		$(this).css("background-color","#808080");
		$(this).css("color","#FFFFFF");
	});
	
	//CSS Animationen Navigationsbuttons
	$(".navbuttons").touchstart(function() {
		$(this).css("border","2px solid #808080");		
	});
	
	$(".navbuttons").touchend(function() {
		$(this).css("border","2px solid " + toolColor);	
	});	
	
	//Suchbutton	
	$(document).on('touchstart', '.searchbutton', function(e) {
		$(this).css("border","2px solid #808080");
	});
	
	//Links bzw. Rechtshänder einstellen
	changeHand(defaultHand);
	
	//direkter Layerwechsel einstellen
	$("#autolayerchange").val(directLayerChange);
		
	//Adressbar verstecken
	hideAddressBar();
	
});

//Animationen der Buttons und Checkboxen zurücksetzen
var clearanimation = function(){
	$(".query_check").css("background-color","#808080");
	$(".layer_check, .baselayer_check, .poi_check").css("background-color","#FFFFFF");
	$(".layer_check, .baselayer_check, .poi_check").css("color","#000000");
}

//Direkter Wechsel zu Map von Layersteuerung
var checktranspage = function(){
		if(directLayerChange === "on"){
			$.mobile.changePage($("#mappage"),pageTransition);
		}
}
	
//Checken welche Layer an sind
var checkLayers = function(){
	//Baselayer prüfen + einstellen
	$(".baselayer_check").each(function(){
		for(var i=0;i<map.layers.length;i++){
		    if(map.layers[i].name === $(this).attr("id") && map.layers[i].visibility){
				//console.log('Aktivierte Baselayer:' + $(this).attr("id"));
				$(this).css("background-image","url(img/ico_basecheck_active.png)");
			}
		}
	});
	
	//Overlays einstellen
	$(".layer_check").each(function(){
		for(var i=0;i<map.layers.length;i++){
		    if(map.layers[i].name === $(this).attr("id") && map.layers[i].visibility){
				//console.log('Aktivierte Overlaylayer:' + $(this).attr("id"));
				$(this).css("background-image","url(img/ico_check_active.png)");
			}
		}
	});
	
	//POI-Overlays einstellen
	$(".poi_check").each(function(){
		var activepoilayer = poilayer.params.LAYERS;
		var activepoiarray = [];
		if(activepoilayer != ""){
			var activepoiarray = activepoilayer.split(',');
		}
		//wenn Layer schon im POI-Layer aktiv, Layer entfernen
		if(jQuery.inArray($(this).attr("id"), activepoiarray) != -1){
			$(this).css("background-image","url(img/ico_check.png)");
		}
	});
	
	/* */
	//Querylayer einstellen
	$(".query_check").each(function() {
		var qstr = $(this).attr("id");
		var qlayer = qstr.substring(0,qstr.length-6);
		    if(qlayer === $('#queryselect').val()){
				//console.log('Aktivierte Querylayer:' + qlayer);
				$(this).css("background-image","url(img/ico_checkop_active.png)");
			}	
	});
		
}

//Aufruf der Suchfunktion je nach globaler Einstellung
function searchCall(){
	if(searchMode ==='google'){
		codeAddress();
	}
	else if (searchMode ==='streetsearch'){
		searchStreet($('#searchfield').val());
	}
	else if (searchMode ==='mapbendersearch'){
		searchMapbender($('#searchfield').val());
	}
	else{
		alert('kein Suchmodus konfiguriert!');
	}
}

//Markerhint sichtbar machen
function setMarkerhint(xheader,xcontent){
	$('#xheader').html(xheader);
	$('#xcontent').html(xcontent);
	$('#markerhint').css('visibility','visible');
}

//Adressleiste verbergen
function hideAddressBar(){
  if(!window.location.hash){ 
	  if(document.height <= window.outerHeight + 10){
		  document.body.style.height = (window.outerHeight + 50) +'px';
		  setTimeout( function(){ window.scrollTo(0, 1); }, 50 );
	  }
	  else{
		  setTimeout( function(){ window.scrollTo(0, 1); }, 0 ); 
	  }
  }
} 

//Händigkeit ändern
function changeHand(h){
	//Linkshänder
	if(h === "l"){
	defaultHand = "l";
		$("#scaleline, #copyright, #zoomscale").css("left","auto").css("right","4px");
		$("#navbutgroup").css("right","auto").css("left","4px");					
	}
	//Rechtshänder (default)
	else{
	defaultHand = "r";
		$("#scaleline, #copyright, #zoomscale").css("right","auto").css("left","4px");		
		$("#navbutgroup").css("left","auto").css("right","4px");
	}
}
//Feature Info ändern
function changeFeatureInfo(h){
	//Popup
	if(h === "p"){
		//$("#scaleline, #copyright, #zoomscale").css("left","auto").css("right","4px");
		//$("#navbutgroup").css("right","auto").css("left","4px");					
	}
	//Neues Fenster (default)
	else{
		//$("#scaleline, #copyright, #zoomscale").css("right","auto").css("left","4px");		
		//$("#navbutgroup").css("left","auto").css("right","4px");
	}
}
//Sprache umstellen
function changeLanguage(lang,refresh){
	window.lang.change(lang);
	$.mobile.showPageLoadingMsg();
	$.ajax({
	  url: 'help/help_'+lang+'.html',
	  cache: true
	}).done(function(data) {
	  $("#helpdiv").html(data);
	  $.mobile.hidePageLoadingMsg();
	});
	if(refresh){
		$('#select-hand').selectmenu("refresh",true);
	}
	$('#select-lang').val(lang);
}

//#####Suchfunktionen###########################################################################
//Geocoding via Google, Adresse Geocodieren
function codeAddress() {	
	adressgeocoder = new google.maps.Geocoder();
	var searchaddress = document.getElementById('searchfield').value;
	adressgeocoder.geocode( { 'address': searchaddress}, function(results, status) {
	  if (status === google.maps.GeocoderStatus.OK) {
		//console.log(results[0]);
		//console.log("Anzahl Objekte:" + results[0].formatted_address);
		var outofborder = "no";
		for (i = 0; i < results[0].address_components.length; i++) {
			//console.log("Objekte:" + results[0].address_components[i].long_name);
		  if(results[0].address_components[i].long_name === googleGeocodeAdmin){
			outofborder = "ok";
		  }
		}
		if(outofborder != "ok"){
			alert("Adresse liegt nicht in "+googleGeocodeAdmin+"!");
		}
		else{
			var mysadr = results[0].formatted_address
			var myslat = results[0].geometry.location.lat(); 
			var myslng = results[0].geometry.location.lng();
			//Anzeige in Google Maps
			var mylatlng = new google.maps.LatLng(myslat, myslng);
			var myOptions = {
			  zoom: 11,
			  center: mylatlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP
			}
			var gmap = new google.maps.Map(document.getElementById("mygooglemap"), myOptions);
			var marker = new google.maps.Marker({
				map: gmap,
				position: mylatlng
			});

			//Link generieren
			var mygglink = window.lang.convert('Suchergebnis: ')+mysadr+'  <br><div class="searchbutton" onClick="zoomtosearchpoint(\''+mysadr+'\','+myslng+','+myslat+')" >'+ window.lang.convert('Zoom auf Ergebnis in Karte') + '</div>';
			//var mygglink = 'Suchergebnis in Google Maps: <a href="http://maps.google.de/maps?f=q&source=s_q&geocode=&q='+myslat+','+myslng+'&t=h&ie=UTF8&ll='+myslat+','+myslng+'&spn=0.009542,0.015407&z=16&iwloc=near" target="_blank"> '+mysadr+'</a>';
			$('#mygooglelink').html(mygglink);
			
			}
	  } else {
		alert("Geocode nicht erfolgreich, Fehler: " + searchaddress + "  " + status);
	  }
	});
}

//Strassensuche aus Datenbank
function searchStreet(item){
	$.mobile.showPageLoadingMsg();
	$('#search_results').empty();
	var searchUrl = 'mod_streetsearch/street_full.php?lang=de';
	searchUrl += '&searchfield=' + item;
	$.getJSON(searchUrl, function(data){
	if(data.totalResultsCount === "++"){
		var output = '<li data-role="list-divider">' + window.lang.convert('Zu viele Datensätze! Bitte schränken Sie Ihre Suche ein!') + '</li>';
		$('#search_results').append(output);
		$('#search_results').listview('refresh');
	}
	else{
		var output = '<li data-role="list-divider"> ' + data.totalResultsCount + ' ' + window.lang.convert('Suchergebnisse') + '</li>';
		$('#search_results').append(output);
		$.each(data.geonames, function(index, place){
			output = '';
			//output += '<li><a href="javascript:void(0);" onClick="zoomtosearchpoint(\''+place.title1+'\','+place.x+','+place.y+');" ><h2>' + place.title1 + '</h2>';
			output += '<li><a href="javascript:void(0);" onClick="searchHsn(\'' + place.strid + '\');" ><h2>' + place.title1 + '</h2>';
			output += '<p>' + place.title2 + '</p></a></li>';			
			$('#search_results').append(output);			
		});
		
		$('#search_results').listview('refresh');	
		//Hiliten
		var o ={words:$('input[name="searchfield"]').val()};
		highlight("search_results",  o);
	}
	$.mobile.hidePageLoadingMsg();		
	});
}

//Hausnummernsuche aus Datenbank
function searchHsn(item){
	$.mobile.showPageLoadingMsg();
	$('#search_results').empty();
	var searchUrl = 'mod_streetsearch/street_hsn.php?lang=de';
	searchUrl += '&strid=' + item;
	$.getJSON(searchUrl, function(data){
		var output = '<li data-role="list-divider" ><a href="javascript:void(0);" onClick="zoomtosearchpoint(\'' + data.street + '\',' + data.streetx + ',' + data.streety + ');" ><h2>' + data.street + '</h2>';
		output += '<p>' + data.totalResultsCount + ' ' + window.lang.convert('Hausnummern') +  '</p></a></li>';	
		$('#search_results').append(output);
		$.each(data.geonames, function(index, place){
			output = '';
			output += '<li><a href="javascript:void(0);" onClick="zoomtosearchpoint(\'' + data.street + ' '+place.title1 + '\',' + place.x + ',' + place.y + ');" ><h2>Nr. ' + place.title1 + '</h2>';
			output += '<p>' + place.title2 + '</p></a></li>';			
			$('#search_results').append(output);			
		});
		
		$('#search_results').listview('refresh');	
		$.mobile.hidePageLoadingMsg();		
	});
}	

//Mapbendersuche via Service
function searchMapbender(item){
	$.mobile.showPageLoadingMsg();
	$('#search_results').empty();
	var searchUrl = mapbendersearchurl+searchEPSG;
	searchUrl += '&searchText=' + item;
	$.getJSON(searchUrl, function(data){
	if(data.totalResultsCount > 500){
		var output = '<li data-role="list-divider" >' + window.lang.convert('Zu viele Datensätze! Bitte schränken Sie Ihre Suche ein!') + '</li>';
		$('#search_results').append(output);
		$('#search_results').listview('refresh');
	}
	else{
		var output = '<li data-role="list-divider" > ' + data.totalResultsCount + ' ' + window.lang.convert('Suchergebnisse') + '</li>';
		$('#search_results').append(output);
		$.each(data.geonames, function(index, place){
		//Mittelpunktkoordinaten
		var myx = (parseInt(place.minx) + parseInt(place.maxx)) / 2;
		var myy = (parseInt(place.miny) + parseInt(place.maxy)) / 2;
			output = '';
		//Ergebnisse die nur auf Bounds zoomen soll	
		if(place.category === 'gemeinde_neu' || place.category === 'Gemeinde' || place.category === 'verbandsgemeinde' || place.category === 'kreis' || place.category === 'Kreis'){
			output += '<li><a href="javascript:void(0);" onClick="zoomtoextent(\''+place.title+'\','+place.minx+','+place.miny+','+place.maxx+','+place.maxy+');" ><h2>' + place.title + '</h2>';
		}
		else {			
			output += '<li><a href="javascript:void(0);" onClick="zoomtosearchpoint(\''+place.title+'\','+myx+','+myy+');" ><h2>' + place.title + '</h2>';
		}
			output += '<p>' + place.category + '</p></a></li>';			
			$('#search_results').append(output);			
		});
		
		$('#search_results').listview('refresh');	
		//Hiliten
		var o ={words:$('input[name="searchfield"]').val()};
		highlight("search_results",  o);
	}
	$.mobile.hidePageLoadingMsg();		
	});
}

//Hiliten von Such-Term
function highlight(id, options) {
  var o = {
    words: '',
    caseSensitive: false,
    wordsOnly: true,
    template: '$1<span class="highlight">$2</span>$3'
  }, pattern;
  $.extend(true, o, options || {});
 
  if (o.words.length == 0) { return; }
  pattern = new RegExp('(>[^<.]*)(' + o.words + ')([^<.]*)', o.caseSensitive ? "" : "ig");
 
  $('#'+id).each(function() {
    var content = $(this).html();
    if (!content) return;
    $(this).html(content.replace(pattern, o.template));
    });
}




//Zoom auf Punkt aus Ajax Request, z.B. Rasterquery
function zoompoint(myslng,myslat){
	var geocodepoint = new OpenLayers.LonLat(myslng,myslat);
	var geompoint = new OpenLayers.Geometry.Point(myslng,myslat);
	var geompoint1 = new OpenLayers.Geometry.Point(myslng,myslat);
	//console.log(transpoint.lon, transpoint.lat)
	vector_marker.removeAllFeatures();
	vector_marker.addFeatures([
		new OpenLayers.Feature.Vector(
			geompoint,
			{},
			olSearchSymbol
		),
		new OpenLayers.Feature.Vector(
			geompoint1,
			{},
			olFeaturequerySymbol
		)
	]);	
	map.setCenter(geocodepoint,getZoomlevel());	
}

//auf Geocodierten Punkt in Karte zoomen
function zoomtosearchpoint(mysadr,myslng,myslat){
	setMarkerhint(window.lang.convert('Suchergebnis: '), mysadr);
	var geocodepoint = new OpenLayers.LonLat(myslng,myslat);	
	//Bei Google Geocoding Koordinaten transformieren
	if(searchMode === "google") {
		var transpoint = geocodepoint.transform(wgs84Proj,mapProj);
	}
	//Bei Strassensuche direkt die Koordinaten verwenden.
	else if (searchMode === "streetsearch" || searchMode === "mapbendersearch") {
		var transpoint = geocodepoint;
		
	}	
	var geompoint = new OpenLayers.Geometry.Point(transpoint.lon, transpoint.lat);
	var geompoint1 = new OpenLayers.Geometry.Point(transpoint.lon, transpoint.lat);
	//console.log(transpoint.lon, transpoint.lat)
	vector_marker.removeAllFeatures();
	vector_marker.addFeatures([
		new OpenLayers.Feature.Vector(
			geompoint,
			{},
			olSearchSymbol
		),
		new OpenLayers.Feature.Vector(
			geompoint1,
			{},
			olFeaturequerySymbol
		)
	]);	
	map.setCenter(transpoint,getZoomlevel());
	$.mobile.changePage($("#mappage"),pageTransition);
}

//auf Extend in Karte zoomen
function zoomtoextent(mysadr,minx,miny,maxx,maxy){
	var myextent = new OpenLayers.Bounds(minx, miny, maxx, maxy);
	if(mysadr !== ""){
		setMarkerhint(window.lang.convert('Suchergebnis: '), mysadr);
	}
	map.zoomToExtent(myextent);	
	$.mobile.changePage($("#mappage"),pageTransition);
}

//aktuellen Zoomlevel ermitteln (Falls voreingestellter Level kleiner als aktueller wird aktueller verwendet)
function getZoomlevel(){
	var actualzoomlevel = Math.round(map.zoom.toFixed(4));
	if (actualzoomlevel > searchZoom){
		var myzoomlevel = actualzoomlevel;
	}
	else{
		var myzoomlevel = searchZoom;
	}
	return myzoomlevel;
}

//Maßstabs-control mit Werten füllen
function createZoomselect() {
	$.each(myscales, function(index, value) {
		//console.log(index + ': ' + value); 
		$('#selectzoom').append($("<option/>", {
			value: index,
			text: "1:" + value
		}));			
	});
	//Selectmenü initialisieren, sonst Bug bei erstem Refresh
	$("#selectzoom").selectmenu();
	displayZoom();
}

//Aktuellen Maßstab für Select-Control einstellen
function displayZoom() {
	var myindex = Math.round(map.zoom.toFixed(4));
	//$("#zoomscale").html("1:"+myscales[Math.round(map.zoom.toFixed(4))]);
	$("#selectzoom").val(''+ myindex +'');
	//console.log(myindex);
	//Refresh des Selectmenüs?	
	$("#selectzoom").selectmenu('refresh',true);	
}

//Maßstab aus Select ändern
function changeScale(i){
	map.zoomTo(i);
	checktranspage();		
	//Adressbar verstecken
	hideAddressBar();
}

//Messunktion
function handleMeasurements(event) {
	var geometry = event.geometry;
	var units = event.units;
	var order = event.order;
	var measure = event.measure;
	var element = document.getElementById('measureoutput');
	var out = "";
	if (order == 1) {
		out += window.lang.convert('Entfernung: ') + "<strong>" + measure.toFixed(2) + "</strong> " + units;
	} else {
		out += window.lang.convert('Fläche: ') + "<strong>" + measure.toFixed(2) + "</strong> " + units + "<sup>2</" + "sup>";
	}
	element.innerHTML = out;
}

//Messfunktion aktivieren
function toggleMeasure(c){
	clickCtrl.deactivate();
	$("#popupMenu").popup( "close" ); 
	measureControls['line'].deactivate();
	measureControls['polygon'].deactivate();
	if (c === 'line'){
		$('#measurehint').css('visibility','visible');
		measureControls[c].activate();
		$('#measureoutput').html(window.lang.convert('Entfernungsmessung aktiv!'));
	}
	else if (c === 'polygon'){
		$('#measurehint').css('visibility','visible');
		measureControls[c].activate();
		$('#measureoutput').html(window.lang.convert('Flächenmessung aktiv!'));
	}
	else {
		clickCtrl.activate();
	}
}

//Mapsize auf vollen Contentbereich skalieren.
function setmapsize(){
	window.scrollTo(0,0);
	var winhigh = $.mobile.getScreenHeight(); //Get available screen height, not including any browser chrome
	var headhigh = $('[data-role="header"]').first().outerHeight(); //Get height of first page's header
	var foothigh = $('[data-role="footer"]').first().outerHeight(); //Get height of first page's header
	var $content=$('[data-role="content"]');
	var contentpaddingwidth=parseInt($content.css("padding-left").replace("px", ""))+parseInt($('[data-role="content"]').css("padding-right").replace("px", ""));
	var contentpaddingheight=parseInt($content.css("padding-top").replace("px", ""))+parseInt($('[data-role="content"]').css("padding-bottom").replace("px", ""));
	winhigh = winhigh - headhigh - foothigh - contentpaddingheight; 
	winwide = $(document).width(); //Get width of document
	winwide = winwide - contentpaddingwidth; 
	$content.css('width',winwide + 'px').css('height',winhigh + 'px'); //Change div to maximum visible area
	$("#map").css('width',winwide + 'px').css('height',winhigh + 'px'); //Change div to maximum visible area
}


function checkZindex(){
	var layers = map.layers;
	for(var ii=0,len=layers.length; ii<len;ii++) {
	//console.log("name:"+layers[ii].name+" zindex:"+layers[ii].div.style.zIndex);
		if(layers[ii].name === "tk_grau"){		  
		  alert("name:"+layers[ii].name+" zindex:"+layers[ii].div.style.zIndex);
		  }
		  if(layers[ii].name === "Vector Layer"){
		  alert("name:"+layers[ii].name+" zindex:"+layers[ii].div.style.zIndex);
		  }
	} 
}

//GPS Start Watch Funktion
var startgpsWatch = function(){
	if(navigator.geolocation){
		$('#gpsmessage').html('<div id="gpsmessagebox">start watching...</div>');
		gpswatch = navigator.geolocation.watchPosition(gpsokCallback, gpsfailCallback, gpsOptions);
	}
}

//GPS Stop Watch  Funktion
var stopgpsWatch = function(){
	navigator.geolocation.clearWatch(gpswatch);
	$('#gpsmessage').html("");
	$("#gpsinfo").css('visibility','hidden');
	gps_marker.removeAllFeatures();
	$("#activePosition").val("");
}

//GPS Erfolgs Callback
var gpsokCallback = function(position){
	var gpsmsg = '' +
	'Lat: ' + position.coords.latitude + "<br>" +
	'Lon: ' + position.coords.longitude + "<br>" +
	'Genauigkeit: ~' + Math.round(position.coords.accuracy) + ' m';
	
	/*var gpszoombut = '<br>' + 
	'<a href="#" id="zoomGpspoint" data-role="button" data-icon="arrow-r" data-iconpos="right" data-inline="true"  data-mini="true" >Zoom auf Position</a>' + 
	'<br><br>' +
	'<a href="#" id="searchGpspoint" data-role="button" data-icon="search" data-iconpos="right" data-inline="true"  data-mini="true" >Suche in der Nähe</a>' +
	'<br><br>';*/

	var gpszoombut = '<br>' + 
	'<a href="#" id="zoomGpspoint" data-role="button" data-icon="arrow-r" data-iconpos="right" data-inline="true"  data-mini="true" >Zoom auf Position</a>' + 
	'<br><br>';

	$('#gpsmessage').html(gpszoombut);
	$('#gpsinfo').html('<strong>Positionierung aktiv</strong><br>' + gpsmsg);
	$("#gpsinfo").css('visibility','visible');
	
	
	var wgspoint = new OpenLayers.LonLat(position.coords.longitude,position.coords.latitude);	
	var transpoint = wgspoint.transform(wgs84Proj,mapProj);
	
	//Zoombutton erzeugen
	$("#zoomGpspoint").bind( "click", function(event, ui) {
		map.setCenter(transpoint,getZoomlevel());
	});
	$("#zoomGpspoint").button();
	
	//Suchbutton erzeugen
	$("#searchGpspoint").bind( "click", function(event, ui) {
		$.mobile.changePage($("#searchpage2"),pageTransition);
		$("#searchSort").val('sortdist1');
		$('#searchSort').selectmenu('refresh');
		$('#search_results_poi').empty();
		$('#searchfield_poi').empty();
	});
	$("#searchGpspoint").button();
	
	$("#activePosition").val(Math.round(transpoint.lon) + "," + Math.round(transpoint.lat));
	
	//Wenn Standoert innerhalb Maxextent
	if(transpoint.lon > map.maxExtent.left && transpoint.lon < map.maxExtent.right && transpoint.lat > map.maxExtent.bottom && transpoint.lat < map.maxExtent.top){
	
		var geompoint = new OpenLayers.Geometry.Point(transpoint.lon, transpoint.lat);
		var geompoint1 = new OpenLayers.Geometry.Point(transpoint.lon, transpoint.lat);

		gps_marker.removeAllFeatures();
		gps_marker.addFeatures([
			new OpenLayers.Feature.Vector(
				geompoint,
				{},
			olGpsSymbol
			),
			new OpenLayers.Feature.Vector(
				OpenLayers.Geometry.Polygon.createRegularPolygon(
					new OpenLayers.Geometry.Point(transpoint.lon, transpoint.lat),
					position.coords.accuracy / 2,
					50,
					0
				),
				{},
				olGpscircleStyle
			)
		]);
		
		//Immer im center halten
		if($('#gpscenter').val() === "on"){
			map.setCenter(transpoint);
		}	
	}
	
	else{
		alert(window.lang.convert('Die ermittelte Position liegt außerhalb des darstellbaren Kartenausschnitts!'));
		$("#gpsstatus").val('off');
		$("#gpsstatus").slider('refresh');
		stopgpsWatch();
	}
	
};

//GPS Fehler Callback
var gpsfailCallback = function(e){
	var msg = 'Fehler ' + e.code + ': ' + e.message;
	//console.log(msg);
};

//GPS Optionen
var gpsOptions = {
	enableHighAccuracy: true,
	timeout: 5000,
	maximumAge: 0
};
