/* ++++++++++++++++++++++++++++++++++++++++++ Openlayers Base +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++  */
// initialize map when page ready
var initmap = function () {
	var mapOptions = {
        theme: null,
        projection: mapProj,
        units: projUnits,
		//'tileSize': new OpenLayers.Size(320,320),
        maxExtent: mymapbounds,		
		maxScale: mymaxscale,
		minScale: myminscale,
		numZoomLevels: myzoomlevels,
		scales: myscales,		
        controls: [
            new OpenLayers.Control.Attribution(),
			new OpenLayers.Control.Navigation({zoomWheelEnabled: true}),
			new OpenLayers.Control.KeyboardDefaults(),
            new OpenLayers.Control.TouchNavigation({
                dragPanOptions: {
                    interval: 10,
                    enableKinetic: true
                }
            }),
			new OpenLayers.Control.ScaleLine({
				div: document.getElementById("scaleline"),geodesic:false,maxWidth:100,topOutUnits:"km",topInUnits:"m",bottomOutUnits:"mi",bottomInUnits:"ft",eTop:null,eBottom:null				
			}),			
			new OpenLayers.Control.LoadingPanel({})
        ]
    }
	
	//Map erzeugen
    map = new OpenLayers.Map('map',mapOptions);	
	//Layer hinzufügen
	addmyLayer();

	//Auf Extent zoomen
   	//
	if (myzoombounds !== "off") {
		map.zoomToExtent(myzoombounds);
	}
	else {
		map.zoomToExtent(map.maxExtent);
	}
	
	//map.zoomToMaxExtent();
	//map.setCenter(new OpenLayers.LonLat(739108, 6403856),10);
	
 /*   var geolocate = new OpenLayers.Control.Geolocate({
        id: 'locate-control',
        geolocationOptions: {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 7000
        }
    });
	map.addControl(geolocate);
	
*/
/*    geolocate.events.register("locationupdated", this, function(e) {
		
        vector_marker.removeAllFeatures();
        vector_marker.addFeatures([
            new OpenLayers.Feature.Vector(
                e.point,
                {},
			olGpsSymbol
            ),
            new OpenLayers.Feature.Vector(
                OpenLayers.Geometry.Polygon.createRegularPolygon(
                    new OpenLayers.Geometry.Point(e.point.x, e.point.y),
                    e.position.coords.accuracy / 2,
                    50,
                    0
                ),
                {},
                olGpscircleStyle
            )
        ]);

		if(e.point.x > map.maxExtent.left && e.point.x < map.maxExtent.right && e.point.y > map.maxExtent.bottom && e.point.y < map.maxExtent.top){
			setMarkerhint(window.lang.convert('Positionsgenauigkeit:'),'~ ' + e.position.coords.accuracy + ' Meter');
			map.zoomToExtent(vector_marker.getDataExtent());
		}
		else{
			alert(window.lang.convert('Die ermittelte Position liegt außerhalb des darstellbaren Kartenausschnitts!'));
			$('#markerhint').css('visibility','hidden');
		}
    });*/
	
	//default Style für Zeich- / Messfunktion)
	var style = new OpenLayers.Style();
	style.addRules([
		new OpenLayers.Rule({symbolizer: sketchSymbolizers})
	]);
	var styleMap = new OpenLayers.StyleMap({"default": style});	
	
	//Messcontrol
	measureControls = {
		line: new OpenLayers.Control.Measure(
			OpenLayers.Handler.Path, {
				persist: true,
				handlerOptions: {
					layerOptions: {
						styleMap: styleMap
					}
				}
			}
		),
		polygon: new OpenLayers.Control.Measure(
			OpenLayers.Handler.Polygon, {
				persist: true,
				handlerOptions: {
					layerOptions: {
						styleMap: styleMap
					}
				}
			}
		)
	};
	
	var control;
	for(var key in measureControls) {
		control = measureControls[key];
		control.events.on({
			"measure": handleMeasurements,
			"measurepartial": handleMeasurements
		});
		map.addControl(control);
	}
	
};
