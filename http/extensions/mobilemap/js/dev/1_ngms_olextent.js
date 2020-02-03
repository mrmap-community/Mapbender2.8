/* ++++++++++++++++++++++++++++++++++++++++++ Openlayers Extension +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  */
//Erweiterung OpenLayers - angepasster Click Event
OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
    defaultHandlerOptions: {
      'single': true,
      //'double': true,
      'pixelTolerance': 0,
      //'stopDouble': false,
	  'stopSingle': false      
    },

    initialize: function(options) {
      var opts = options || {};
      this.handlerOptions = OpenLayers.Util.applyDefaults(
        opts.handlerOptions || {},
        this.defaultHandlerOptions
      );
      OpenLayers.Control.prototype.initialize.apply(
        this,
        arguments
      );
      this.handler = new OpenLayers.Handler.Click(
        this,
        {
          'click': this.onClick
          //'dblclick': this.onDblClick
        },
        this.handlerOptions
      );
    },

    onClick: function( evt ) {
      // click function
      var lonlat = map.getLonLatFromViewPortPx(evt.xy);  
	  var querylayer = $('#queryselect').val();
	  var actuallang = $('#select-lang').val();
	  //Punkt erzeugen
	  var geompoint = new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat);
	  var geompoint1 = new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat);
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
	  
	  //Default Koordinatenabfrage / Rasterquery / RasterqueryWms
	  if(querylayer == "dhm"){
		  setMarkerhint(window.lang.convert('Meldung:'),window.lang.convert('bitte warten...'));
		  var featureurl = 'query/rasterquery.php?'
			+ 'coord=' + lonlat.lon + ', ' + lonlat.lat
			+ '&lang=' + actuallang;
		  loadFeature(featureurl);	
			//alert("Um eine Ebene abzufragen selektieren Sie bitte unter Abfrage --> Abfrageebene w\u00e4hlen die gew\u00fcnschte Ebenen aus!");
	  }
	  else if(querylayer == "dhmWms"){
		  setMarkerhint(window.lang.convert('Meldung:'),window.lang.convert('bitte warten...'));
		  var featureurl = 'query/rasterqueryWms.php?'
			+ 'coord=' + lonlat.lon + ', ' + lonlat.lat
			+ '&lang=' + actuallang;
		 // alert("lon: "+lonlat.lon+" - lat: ".lonlat.lat);
		  //loadFeature(featureurl);	
	//alert("Um eine Ebene abzufragen selektieren Sie bitte unter Abfrage --> Abfrageebene w\u00e4hlen die gew\u00fcnschte Ebenen aus!");
	  }
	  else if(querylayer == "pois"){
		var activepoilayer = poilayer.params.LAYERS;
	  	var featureurl = 'query/poiquery.php?'
					+ 'qx=' + lonlat.lon + '&qy=' + lonlat.lat 
					+ '&qlayer=' + activepoilayer
					+ '&qextent=' + map.getExtent().toBBOX()
					+ '&qsize=' + map.size.w + ' ' + map.size.h
					+ '&lang=' + actuallang;
		loadFeature(featureurl);
	  }
	  else{
	  //Get Feature Query
	  setMarkerhint(window.lang.convert('Meldung:'),window.lang.convert('bitte warten...'));
	  var featureurl = 'query/proxy.php?wms=SERVICE=WMS&REQUEST=getFeatureInfo&VERSION=1.1.1'
				+ '&mapfile='+ querylayer
				+ '&layers=' + querylayer + '&QUERY_LAYERS=' + querylayer
				+ '&SRS=' + featurequerySrc 
				+ '&BBOX=' + map.getExtent().toBBOX()
				+ '&WIDTH=' + map.size.w + '&HEIGHT=' + map.size.h
				+ '&X=' + evt.xy.x + '&Y=' + evt.xy.y
				+ '&INFO_FORMAT=text/html';
		 //alert('Klick auf Koordinate: ' + lonlat.lon + ', ' + lonlat.lat);
		 loadFeature(featureurl);		 
		 //alert(featureurl);
	   } 
    },
	
	/*
	    onDblClick: function( evt ) {
	      doubleClick funcktion
	      var lonlat = map.getLonLatFromViewPortPx(evt.xy);
	      //alert('Doppelklick auf Koordinate: ' + lonlat.lon + ', ' + lonlat.lat);
	    },
	*/
	
	//Abfrageebene darstellen
	showQuerylayer: function(){
	  var querylayer = $('#queryselect').val();
	  if(querylayer == "dhm" || querylayer == "dhmWms"){
	  setMarkerhint(window.lang.convert('Standardabfrage:'),window.lang.convert('Koordinaten + Hoehe'));
	  }
	  else{
	  setMarkerhint(window.lang.convert('aktuelle Abfrageebene:'), querylayer);
	  }
	},

    CLASS_NAME: "OpenLayers.Control.Click"
  }
);

//Abfrageebene darstellen
function showQuerylayer(){
	  var querylayer = $('#queryselect').val();
	  if(querylayer == "dhm" ||  querylayer == "dhmWms"){
	   setMarkerhint(window.lang.convert('Standardabfrage:'),window.lang.convert('Koordinaten + Hoehe'));
	  }
	  else{
	  setMarkerhint(window.lang.convert('aktuelle Abfrageebene:'), querylayer);
	  }
}

//Ajax Aufruf
function loadFeature(myurl){
	//showLoader();
	$.ajax({
		type: 'GET',
		url: myurl,
		//data: {layers:mylayers,imgsize:myimgsize},
		success: function(ergebnis){
					//alert(myurl);
					if(ergebnis){
						//alert(ergebnis);
						if(ergebnis.length < 5){
							ergebnis = window.lang.convert('Kein Ergebnis!');
						}
						setMarkerhint(window.lang.convert('Abfrageergebnis:'),ergebnis);
						//go to other window and set content
						//$.mobile.changePage($("#featureinforesult"),pageTransition);
						//$("#fi_contentdiv").val(ergebnis);
					}
				}
		});
}

//Erweiterung OpenLayers - Sclaeline vgl. ScaleLine.js
OpenLayers.Control.ScaleLine = OpenLayers.Class(OpenLayers.Control, {
    maxWidth: 100,
    topOutUnits: "km",
    topInUnits: "m",
    bottomOutUnits: "mi",
    bottomInUnits: "ft",
    eTop: null,
    eBottom:null,
    geodesic: false,
    draw: function() {
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        if (!this.eTop) {
            // stick in the top bar
            this.eTop = document.createElement("div");
            this.eTop.className = this.displayClass + "Top";
            var theLen = this.topInUnits.length;
            this.div.appendChild(this.eTop);
            if((this.topOutUnits == "") || (this.topInUnits == "")) {
                this.eTop.style.visibility = "hidden";
            } else {
                this.eTop.style.visibility = "visible";
            }

            // and the bottom bar
            this.eBottom = document.createElement("div");
            this.eBottom.className = this.displayClass + "Bottom";
            this.div.appendChild(this.eBottom);
            if((this.bottomOutUnits == "") || (this.bottomInUnits == "")) {
                this.eBottom.style.visibility = "hidden";
            } else {
                this.eBottom.style.visibility = "visible";
            }
        }
        this.map.events.register('moveend', this, this.update);
        this.update();
        return this.div;
    },

    getBarLen: function(maxLen) {
        // nearest power of 10 lower than maxLen
        var digits = parseInt(Math.log(maxLen) / Math.log(10));
        var pow10 = Math.pow(10, digits);
        
        // ok, find first character
        var firstChar = parseInt(maxLen / pow10);

        // right, put it into the correct bracket
        var barLen;
        if(firstChar > 5) {
            barLen = 5;
        } else if(firstChar > 2) {
            barLen = 2;
        } else {
            barLen = 1;
        }

        // scale it up the correct power of 10
        return barLen * pow10;
    },

    update: function() {
        var res = this.map.getResolution();
        if (!res) {
            return;
        }

        var curMapUnits = this.map.getUnits();
        var inches = OpenLayers.INCHES_PER_UNIT;

        // convert maxWidth to map units
        var maxSizeData = this.maxWidth * res * inches[curMapUnits];
        var geodesicRatio = 1;
        if(this.geodesic === true) {
            var maxSizeGeodesic = (this.map.getGeodesicPixelSize().w ||
                0.000001) * this.maxWidth;
            var maxSizeKilometers = maxSizeData / inches["km"];
            geodesicRatio = maxSizeGeodesic / maxSizeKilometers;
            maxSizeData *= geodesicRatio;
        }

        // decide whether to use large or small scale units     
        var topUnits;
        var bottomUnits;
        if(maxSizeData > 100000) {
            topUnits = this.topOutUnits;
            bottomUnits = this.bottomOutUnits;
        } else {
            topUnits = this.topInUnits;
            bottomUnits = this.bottomInUnits;
        }

        // and to map units units
        var topMax = maxSizeData / inches[topUnits];
        var bottomMax = maxSizeData / inches[bottomUnits];

        // now trim this down to useful block length
        var topRounded = this.getBarLen(topMax);
        var bottomRounded = this.getBarLen(bottomMax);

        // and back to display units
        topMax = topRounded / inches[curMapUnits] * inches[topUnits];
        bottomMax = bottomRounded / inches[curMapUnits] * inches[bottomUnits];

        // and to pixel units
        var topPx = topMax / res / geodesicRatio;
        var bottomPx = bottomMax / res / geodesicRatio;
        
        // now set the pixel widths
        // and the values inside them
        
        if (this.eBottom.style.visibility == "visible"){
            this.eBottom.style.width = Math.round(bottomPx) + "px"; 
            //this.eBottom.innerHTML = bottomRounded + " " + bottomUnits ;
        }
            
        if (this.eTop.style.visibility == "visible"){
            this.eTop.style.width = Math.round(topPx) + "px";
            this.eTop.innerHTML = topRounded + " " + topUnits;
        }
        
    }, 

    CLASS_NAME: "OpenLayers.Control.ScaleLine"
});

//Openlayers Loading Control
OpenLayers.Control.LoadingPanel = OpenLayers.Class(OpenLayers.Control, {

    counter: 0,

    maximized: false,

    visible: true,

    initialize: function(options) {
         OpenLayers.Control.prototype.initialize.apply(this, [options]);
    },

    setVisible: function(visible) {
        this.visible = visible;
        if (visible) {
            OpenLayers.Element.show(this.div);
        } else {
            OpenLayers.Element.hide(this.div);
        }
    },

    getVisible: function() {
        return this.visible;
    },


    hide: function() {
        this.setVisible(false);
    },


    show: function() {
        this.setVisible(true);
    },

    toggle: function() {
        this.setVisible(!this.getVisible());
    },

    addLayer: function(evt) {
        if (evt.layer) {
            evt.layer.events.register('loadstart', this, this.increaseCounter);
            evt.layer.events.register('loadend', this, this.decreaseCounter);
        }
    },

    setMap: function(map) {
        OpenLayers.Control.prototype.setMap.apply(this, arguments);
        this.map.events.register('preaddlayer', this, this.addLayer);
        for (var i = 0; i < this.map.layers.length; i++) {
            var layer = this.map.layers[i];
            layer.events.register('loadstart', this, this.increaseCounter);
            layer.events.register('loadend', this, this.decreaseCounter);
        }
    },

    increaseCounter: function() {
        this.counter++;
        if (this.counter > 0) { 
            if (!this.maximized && this.visible) {
                this.maximizeControl(); 
            }
        }
    },

    decreaseCounter: function() {
        if (this.counter > 0) {
            this.counter--;
        }
        if (this.counter == 0) {
            if (this.maximized && this.visible) {
                this.minimizeControl();
            }
        }
    },

    draw: function () {
        OpenLayers.Control.prototype.draw.apply(this, arguments);
        return this.div;
    },
     
    minimizeControl: function(evt) {
        this.div.style.display = "none"; 
        this.maximized = false;
    
        if (evt != null) {
            OpenLayers.Event.stop(evt);
        }
    },

    maximizeControl: function(evt) {
        this.div.style.display = "block";
        this.maximized = true;
    
        if (evt != null) {
            OpenLayers.Event.stop(evt);
        }
    },

    destroy: function() {
        if (this.map) {
            this.map.events.unregister('preaddlayer', this, this.addLayer);
            if (this.map.layers) {
                for (var i = 0; i < this.map.layers.length; i++) {
                    var layer = this.map.layers[i];
                    layer.events.unregister('loadstart', this, 
                        this.increaseCounter);
                    layer.events.unregister('loadend', this, 
                        this.decreaseCounter);
                }
            }
        }
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },     

    CLASS_NAME: "OpenLayers.Control.LoadingPanel"

});

