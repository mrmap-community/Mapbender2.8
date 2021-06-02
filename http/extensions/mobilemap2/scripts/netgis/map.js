/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017-2020
 */

/**
 * The NetGIS map module. Contains all map functionality.
 * Start with [init]{@link netgis.init}.
 * @namespace
 */
netgis.map =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var container;
		var map;
		var view;
		
		var geolocationHandler;
		var geolocationCoords;
		
		var interaction; //TODO: rename to current/active interaction
		var history;
		var historyIndex;
		var historyActive;
		
		var layerList;
		
		var popupContainer;
		var popupContent;
		var popupControls;
		var popupOverlay;
		
		var positionActive;
		var positionCenter;
		var positionOverlay;
		
		var measureSource;
		var measureFeature;
		var measureListener;
		var measureOverlay;
		var measureElement;
		var measureLayer;
		var measureContent;
		var measureClose;
		
		var resultOverlay;
		
		var backLayerHybrid;
		var backLayerAerial;
		
		var scaleBar;
		
		var interactions =
		{
			pan:			null,
			zoomBoxIn:		null,
			zoomBoxOut:		null,
			featureInfo:	null,
			measureLength:	null,
			measureArea:	null
		};
		
		// Private Methods
		
		/**
		 * Initialize the map client.
		 * @memberof netgis
		 */
		var init = function()
		{
			// Container
			container = document.getElementById( netgis.config.MAP_CONTAINER_ID );
			
			// Projections
			proj4.defs( netgis.config.MAP_PROJECTIONS );
			ol.proj.proj4.register( proj4 );
			
			// View
			view = new ol.View
			(
				{
					projection:	netgis.config.MAP_PROJECTION,
					//zoom:		netgis.config.INITIAL_ZOOM,
					resolution:	getResolutionFromScale( netgis.config.INITIAL_SCALE ),
					center:		[ netgis.config.INITIAL_CENTER_X, netgis.config.INITIAL_CENTER_Y ]
				}
			);
	
			// Map
			map = new ol.Map
			(
				{
					target:						container,
					view:						view,
					loadTilesWhileAnimating:	true,
					loadTilesWhileInteracting:	true,
					pixelRatio:					1.0,
					controls:					[],
					interactions:	
					[
						new ol.interaction.KeyboardPan(),
						new ol.interaction.KeyboardZoom(),
						new ol.interaction.DoubleClickZoom(),
						new ol.interaction.PinchZoom(),
						new ol.interaction.MouseWheelZoom()
					]
				}
			);
	
			// Background Layers
			var extent = netgis.config.MAP_EXTENT;
			var resolutions = [];
			var scales = netgis.config.MAP_SCALES;
			
			for ( var s = 0; s < scales.length; s++ )
				resolutions.unshift( getResolutionFromScale( scales[ s ] ) );
			
			var backSourceHybrid = new ol.source.TileImage
			(
				{
					crossOrigin:	null,
					projection:		view.getProjection(),
					tileGrid:		new ol.tilegrid.TileGrid
					(
						{
							extent: extent,
							origin: [ extent[ 0 ], extent[ 1 ] ],
							resolutions: resolutions
						}
					),
					tileUrlFunction: function( zxy )
					{
						if ( zxy === null ) return undefined;
						
						//return netgis.config.URL_BACKGROUND_HYBRID + "/" + zxy[ 0 ] + "/" + zxy[ 1 ] + "/" + zxy[ 2 ] + ".jpeg";
						// Hybrid layer has different grid coords
						var y = -zxy[ 2 ] - 1;
						return netgis.config.URL_BACKGROUND_HYBRID + "/" + zxy[ 0 ] + "/" + zxy[ 1 ] + "/" + y + ".jpeg";
					}
				}
			);
			
			backLayerHybrid = new ol.layer.Tile
			(
				{
					source:	backSourceHybrid,
					zIndex: -1
				}
			);
	
			map.addLayer( backLayerHybrid );
			
			var backSourceAerial = new ol.source.TileWMS
			(
				{
					url: netgis.config.URL_BACKGROUND_AERIAL + "?",
					params:
					{
						"LAYERS":		"rp_dop",
						"FORMAT":		"image/jpeg",
						"TRANSPARENT":	"false",
						"VERSION":		"1.1.1"
					},
					serverType: "mapserver"
				}
			);
	
			backLayerAerial = new ol.layer.Tile
			(
				{
					source:	backSourceAerial,
					zIndex: -2
				}
			);
	
			//createBackgroundLayers();
	
			// Geolocation
			positionOverlay = new ol.Overlay
			(
				{
					element: $( "#position-overlay" )[ 0 ],
					positioning: 'center-center'
				}
			);
	
			// Measure
			measureSource = new ol.source.Vector();
			
			var measureStyle = new ol.style.Style
			(
				{
					fill: new ol.style.Fill( { color: "rgba( 255, 0, 0, 0.25 )" } ),
					stroke: new ol.style.Stroke( { color: "#ff0000", width: 2 } ),
					image: new ol.style.Circle( { radius: 7, fill: new ol.style.Fill( { color: "rgba( 255, 0, 0, 0.25 )" } ) } )
				}
			);
			
			measureLayer = new ol.layer.Vector
			(
				{
					source: measureSource,
					style: measureStyle,
					zIndex: 1000
				}
			);
	
			map.addLayer( measureLayer );
			
			measureElement = $( "#measure-container" );
			measureElement.hide();
			measureContent = $( "#measure-content" );
			measureClose = $( "#measure-close" );
			measureClose.click( function() { clearMeasure(); } );
			
			measureOverlay = new ol.Overlay
			(
				{
					element: measureElement[ 0 ],
					positioning: "bottom-center"
				}
			);
	
			map.addOverlay( measureOverlay );
			
			// Search Result Overlay
			resultOverlay = new ol.Overlay
			(
				{
					element: $( "#result-overlay" )[ 0 ],
					positioning: 'center-center'
				}
			);
			
			// Interactions
			interactions.pan = new ol.interaction.DragPan
			(
				{
					condition: ol.events.condition.always
				}
			);
	
			interactions.zoomBoxIn = new ol.interaction.DragZoom
			(
				{
					condition: ol.events.condition.always
				}
			);
	
			interactions.zoomBoxOut = new ol.interaction.DragZoom
			(
				{
					condition:	ol.events.condition.always,
					out:		true
				}
			);
	
			interactions.featureInfo = new ol.interaction.Pointer
			(
				{
					//handleDownEvent:	function( event ) { /*this.dispatchEvent( event ); return true;*/ },
					//handleUpEvent:		function( event ) { onMapClick( event ); return false; }
					handleEvent: function( event ) { if ( event.type === "singleclick" ) { onMapClick( event ); } return true; }
				}
			);
	
			interactions.measureLength = new ol.interaction.Draw
			(
				{
					source: measureSource,
					type: "LineString",
					style: measureStyle
				}
			);
	
			interactions.measureLength.on( "drawstart", onMeasureStart );
			interactions.measureLength.on( "drawend", onMeasureEnd );
			
			interactions.measureArea = new ol.interaction.Draw
			(
				{
					source: measureSource,
					type: "Polygon",
					style: measureStyle
				}
			);
	
			interactions.measureArea.on( "drawstart", onMeasureStart );
			interactions.measureArea.on( "drawend", onMeasureEnd );
	
			// Popup
			popupContainer = $( "#popup-container" );
			popupContent = $( "#popup-content" );
			popupControls = $( "#popup-controls" );
			
			popupOverlay = new ol.Overlay
			(
				{
					element:			popupContainer[ 0 ],
					autoPan:			true,
					autoPanAnimation:	{ duration: 250 }
				}
			);
	
			map.addOverlay( popupOverlay );
	
			$( "#popup-closer" ).click( onPopupClose );
			
			// Scale Bar
			setScaleBarVisible( true );
			
			// Events
			netgis.events.on( netgis.events.LAYERS_LOADING, onLayersLoading );
			netgis.events.on( netgis.events.LAYER_TOGGLE, onLayerToggle );
			netgis.events.on( netgis.events.LAYER_REMOVE, onLayerRemove );
			netgis.events.on( netgis.events.LAYER_MOVE_UP, onLayerMoveUp );
			netgis.events.on( netgis.events.LAYER_MOVE_DOWN, onLayerMoveDown );
			netgis.events.on( netgis.events.LAYER_ZOOM, onLayerZoom );
			netgis.events.on( netgis.events.POSITION_TOGGLE, onPositionToggle );
			netgis.events.on( netgis.events.POSITION_CENTER, onPositionCenter );
			
			map.on( "moveend", onMapMoveEnd );
			if ( netgis.params.getInt( "withDigitize" ) === 1 ) map.on( "click", onDigitizeClick );
			
			// History
			history = [];
			historyIndex = -1;
			historyActive = true;
			
			// Initial Setup
			setInteraction( interactions.pan );
			addHistory( view.getCenter(), view.getZoom() );
			
			positionActive = false;
			positionCenter = false;
			
			map.updateSize();
			
			// Additional Size Update, just to be sure
			setTimeout( function() { map.updateSize(); }, 200 );
			
			// Parameters
			var center = getCenter();
			var zoom = getZoom();

			if ( netgis.params.get( "x" ) ) center[ 0 ] = netgis.params.getFloat( "x" );
			if ( netgis.params.get( "y" ) ) center[ 1 ] = netgis.params.getFloat( "y" );
			if ( netgis.params.get( "z" ) ) zoom = netgis.params.getFloat( "z" );

			setView( center, zoom );
			
			if ( netgis.params.getInt( "scale_bar" ) === 0 ) setScaleBarVisible( false );
		};
		
		var setScaleBarVisible = function( on )
		{
			if ( on )
			{
				scaleBar = new ol.control.ScaleLine();
				map.addControl( scaleBar );
				$( container ).find( ".ol-scale-line" ).addClass( "shadow-md" );
			}
			else
			{
				if ( scaleBar ) map.removeControl( scaleBar );
			}
		};
		
		var setInteraction = function( i )
		{
			// Clear Old Interaction
			map.removeInteraction( interaction );
			
			//TODO: hack to allow panning while measure drawing
			if ( interaction === interactions.measureLength || interaction === interactions.measureArea || interaction === interactions.featureInfo )
				map.removeInteraction( interactions.pan );
			if ( i === interactions.measureLength || i === interactions.measureArea || i === interactions.featureInfo )
				map.addInteraction( interactions.pan );
			
			// Set New Interaction
			map.addInteraction( i );
			interaction = i;
			
			// Setup
			switch ( interaction )
			{
				case interactions.pan:
					container.style.cursor = "move";
					break;
				case interactions.zoomBoxIn:
					container.style.cursor = "zoom-in";
					break;
				case interactions.zoomBoxOut:
					container.style.cursor = "zoom-out";
					break;
				case interactions.featureInfo:
				case interactions.measureLength:
				case interactions.measureArea:
					container.style.cursor = "crosshair";
					clearMeasure();
					break;
			}
		};
		
		/**
		 * Sets the map interaction mode to "pan".
		 * @memberof netgis
		 */
		var pan = function()
		{
			setInteraction( interactions.pan );
		};
		
		//TODO: remove all these interaction setters, just allow access to setInteraction and interactions
		
		/**
		 * Sets the map interaction mode to "zoom box in".
		 * @memberof netgis
		 */
		var zoomBoxIn = function()
		{
			setInteraction( interactions.zoomBoxIn );
		};
		
		/**
		 * Sets the map interaction mode to "zoom box out".
		 * @memberof netgis
		 */
		var zoomBoxOut = function()
		{
			setInteraction( interactions.zoomBoxOut );
		};
		
		var featureInfo = function()
		{
			setInteraction( interactions.featureInfo );
		};
		
		/**
		 * Zoom map by the given step amount.
		 * @param {Number} step Zoom steps to add
		 * @returns {undefined}
		 */
		var zoom = function( step )
		{
			view.animate( { zoom: view.getZoom() + step, duration: 500 } );
		};
		
		/**
		 * Sets the map interaction mode to "zoom in".
		 * @memberof netgis
		 */
		var zoomIn = function()
		{
			zoom( 1 );
		};
		
		/**
		 * Sets the map interaction mode to "zoom out".
		 * @memberof netgis
		 */
		var zoomOut = function()
		{
			zoom( -1 );
		};
		
		var viewFull = function()
		{
			viewExtent( 403960, 5468250, 595890, 5733150 );
		};
		
		var viewExtent = function( minx, miny, maxx, maxy )
		{
			view.fit( [ minx, miny, maxx, maxy ] );
			
			addHistory( view.getCenter(), view.getZoom() );
		};
		
		var viewGeolocation = function()
		{			
			if ( geolocationCoords && positionActive ) view.setCenter( geolocationCoords );
		};
		
		var updateGeolocation = function()
		{
			if ( geolocationCoords && positionActive ) positionOverlay.setPosition( geolocationCoords );
		};
		
		/**
		 * Sets the map view to the initial extent and zoom.
		 * @memberof netgis
		 */
		var viewHome = function()
		{			
			var center = [ netgis.config.INITIAL_CENTER_X, netgis.config.INITIAL_CENTER_Y ];
			var scale = netgis.config.INITIAL_SCALE;
			
			setViewScale( center, scale );
		};
		
		/**
		 * Go to the previous entry in the map view history.
		 * @memberof netgis
		 */
		var viewUndo = function()
		{			
			if ( historyIndex > 0 ) historyIndex--;
			
			var entry = history[ historyIndex ];
			
			historyActive = false;
			
			view.setCenter( entry.center );
			view.setZoom( entry.zoom );
		};
		
		/**
		 * Go to the next entry in the map view history.
		 * @memberof netgis
		 */
		var viewRedo = function()
		{
			historyIndex++;
			if ( historyIndex > history.length - 1 ) historyIndex = history.length - 1;
			
			var entry = history[ historyIndex ];
			
			historyActive = false;
			
			view.setCenter( entry.center );
			view.setZoom( entry.zoom );
		};
		
		/**
		 * Set the current map view.
		 * @memberof netgis
		 * @param {array} center Center coordinate pair [x,y] in main map projection
		 * @param {number} zoom Zoom level (0 = farthest, 18 = closest)
		 */
		var setView = function( center, zoom )
		{
			view.setCenter( center );
			view.setZoom( zoom );
			
			addHistory( center, zoom );
		};
		
		/**
		 * Set the current map view.
		 * @memberof netgis
		 * @param {array} center Center coordinate pair [x,y] in main map projection
		 * @param {number} scale Map scale (e.g. 10000 = 1:10000)
		 */
		var setViewScale = function( center, scale )
		{
			view.setCenter( center );
			view.setResolution( getResolutionFromScale( scale ) );
			
			addHistory( center, view.getZoom() );
		};
		
		var setScale = function( scale )
		{
			view.setResolution( getResolutionFromScale( scale ) );
			
			addHistory( view.getCenter(), view.getZoom() );
		};
		
		var getScale = function()
		{
			return getScaleFromResolution( view.getResolution() );
		};
		
		var getZoom = function()
		{
			return view.getZoom();
		};
		
		var getCenter = function()
		{
			return view.getCenter();
		};
		
		var addHistory = function( center, zoom )
		{
			// No duplicates at the end
			if ( history.length > 0 )
			{
				var last = history[ history.length - 1 ];
				if ( last.center[ 0 ] === center[ 0 ] && last.center[ 1 ] === center[ 1 ] && last.zoom === zoom ) return;
			}
			
			if ( historyIndex < history.length )
			{
				// Remove items to end
				if ( historyIndex > -1 )
				{
					history.length = historyIndex + 1;
				}
				
				// Stay at newest entry
				historyIndex = history.length;
			}
			
			// Add new entry
			var entry =
			{
				center: center,
				zoom:	zoom
			};
			
			history.push( entry );
			
			if ( history.length > netgis.config.MAX_HISTORY ) history.shift();
		};
		
		var getResolutionFromScale = function( scale )
		{
			var mpu = view ? ol.proj.Units.METERS_PER_UNIT[ view.getProjection().getUnits() ] : 1.0;
			var ipu = mpu * 39.3701; // inches per unit = 39.3701
			var dpi = 72;
			
			var res = 1 / ( normalizeScale( scale ) * ipu * dpi );
			
			return res;
		};

		var normalizeScale = function( scale )
		{
			return 1 < scale ? 1 / scale : scale;
		};
		
		var getScaleFromResolution = function( res )
		{
			var scale = 39.3701 * 72 * res;
			
			// Round
			scale = Math.round( scale );
			
			return scale;
		};
		
		var createLayerWms = function( url, layerName, index, opacity )
		{
			//var type = ol.source.TileWMS;
			
			var source;
			
			if ( url.search( /wmts/i ) > -1 && false )
			{
				/*
				var projection = view.getProjection(); //ol.proj.get('EPSG:25832'); //3857 //4326 //900913 EPSG:25832
				var projectionExtent = projection.getExtent();
				var size = ol.extent.getWidth(projectionExtent) / 256;
				var resolutions = new Array(14);
				var matrixIds = new Array(14);
				for (var z = 0; z < 14; ++z) {
				  // generate resolutions and matrixIds arrays for this WMTS
				  resolutions[z] = size / Math.pow(2, z);
				  matrixIds[z] = z;
				}
	  
				source = new ol.source.WMTS
				(
					{
						url: url,
						params:
						{
							"LAYER":		layerName,
							"FORMAT":		"image/png",
							"TRANSPARENT":	"true",
							"VERSION":		"1.1.1"
						},
						layer: layerName,
						format: 'image/png',
						matrixSet: "UTM32", //'g',
						tileGrid: new ol.tilegrid.WMTS({
							origin: ol.extent.getTopLeft(projectionExtent),
							resolutions: resolutions,
							matrixIds: matrixIds
						  })
					}
				);
				*/
			}
			else
			{
				//source = new ol.source.TileWMS
				source = new ol.source.ImageWMS
				(
					{
						url: url,
						params:
						{
							"LAYERS":		layerName,
							"FORMAT":		"image/png",
							"TRANSPARENT":	"true",
							"VERSION":		"1.1.1"
						},
						serverType: "mapserver"
					}
				);
			}
	
			//var layer = new ol.layer.Tile
			var layer = new ol.layer.Image
			(
				{
					source:	source,
					zIndex: index,
					opacity: opacity ? opacity : netgis.config.MAP_DEFAULT_OPACITY //NOTE: should be obsolete, default passed in anyway
				}
			);

			return layer;
		};
		
		var setBackgroundLayer = function( url, layerName )
		{
			//TODO: remove old background layer if exists
			
			//console.info( "BG:", url, layerName );
			
			map.addLayer( createLayerWms( url, layerName, 0, netgis.config.MAP_DEFAULT_OPACITY ) );
		};
		
		var requestFeatureInfo = function( url, layer )
		{
			$.get
			(
				"./scripts/proxy.php",
				{
					q: encodeURI( url )
				},
				function( data ) { onFeatureInfoResponse( data, layer ); }
			);
		};
		
		var updateSize = function()
		{
			map.updateSize();
		};
		
		var isPositionActive = function()
		{
			return positionActive;
		};
		
		var isPositionCenter = function()
		{
			return positionCenter;
		};
		
		var formatLength = function( lineGeom )
		{
			//var length = ol.Sphere.getLength( lineGeom );
			var length = lineGeom.getLength();
			var output;
			
			if ( length > 100 )
				output = ( Math.round( length / 1000 * 100 ) / 100 ) + " km";
			else
				output = ( Math.round( length * 100 ) / 100 ) + " m";
			
			return output;
		};
		
		var formatArea = function( polyGeom )
		{
			//var area = ol.Sphere.getArea( polyGeom );
			var area = polyGeom.getArea();
			var output;
			
			if ( area > 10000 )
				output = ( Math.round( area / 1000000 * 100 ) / 100 + " km<sup>2</sup>" );
			else
				output = ( Math.round( area * 100 ) / 100 ) + " m<sup>2</sup>";
			
			return output;
		};
		
		var clearMeasure = function()
		{
			measureSource.clear();
			measureElement.hide();
		};
		
		//TODO: testing
		var update = function()
		{
			// Clear
			map.getLayers().clear();
			
			var mapLayers = netgis.entities.get( [ netgis.component.MapLayer ] );
			
			for ( var l = 0; l < mapLayers.length; l++ )
			{
				mapLayers[ l ].remove( netgis.component.MapLayer );
			}
			
			// Add
			var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Active ] );
			
			for ( var l = 0; l < layers.length; l++ )
			{
				var layer = layers[ l ];
				
				// Get root service url
				var child = layer;
				var parent = null;
				var url = null;

				do
				{
					parent = child.components.parent ? child.components.parent.value : null;

					if ( parent.components.service ) url = parent.components.url.value;

					child = parent;
				}
				while ( ! url && parent );

				// Create WMS layer
				var name = layer.components.name.value;

				var index = layer.components.order.value; //layers.length - l; //TODO: real layer order?
				
				var opacity = layer.components.opacity ? layer.components.opacity.value : netgis.config.MAP_DEFAULT_OPACITY;

				var mapLayer = createLayerWms( url, name, index, opacity );

				layer.set( new netgis.component.MapLayer( mapLayer ) );
				
				map.addLayer( mapLayer );
			}
		};
		
		var createBackgroundLayers = function()
		{			
			if ( netgis.entities.find( netgis.component.MapLayer, "value", backLayerHybrid ).length > 0 ) return;
			
			var backGroup = netgis.entities.create
			(
				[
					new netgis.component.Layer( -1 ),
					new netgis.component.Title( "Hintergrund" )
				]
			);

			var backLayer = netgis.entities.create
			(
				[
					new netgis.component.Parent( backGroup ),
					new netgis.component.Layer( -1 ),
					new netgis.component.Title( "Hybrid" ),
					new netgis.component.MapLayer( backLayerHybrid ),
					new netgis.component.Active()
				]
			);
	
			netgis.entities.create
			(
				[
					new netgis.component.Parent( backGroup ),
					new netgis.component.Layer( -1 ),
					new netgis.component.Title( "Luftbild" ),
					new netgis.component.MapLayer( backLayerAerial )
				]
			);
	
			netgis.events.call( netgis.events.LAYER_TOGGLE, { id: backLayer.id } );
		};
		
		var showPopup = function( x, y, content )
		{
			// Clear
			popupContent.empty();
			popupControls.hide();
			
			popupContent.append( content );
			
			// Show Popup
			popupContainer.fadeIn( 200 );
			popupOverlay.setPosition( [ x, y ] );
		};
		
		var hidePopup = function()
		{
			popupContainer.fadeOut( 200 );
		};
		
		// Event Handlers
		$( document ).ready( init );
		
		var onMapMoveEnd = function( event )
		{
			var center = view.getCenter();
			var zoom = view.getZoom();
			var extent = view.calculateExtent( map.getSize() );
			
			// History
			if ( historyActive === true ) addHistory( center, zoom );
			
			historyActive = true;
			
			// Callback
			var params = 
			{
				center:		
				{
					x: center[ 0 ],
					y: center[ 1 ]
				},
				zoom:		zoom,
				extent:
				{
					minX: extent[ 0 ],
					minY: extent[ 1 ],
					maxX: extent[ 2 ],
					maxY: extent[ 3 ]
				}
			};
			
			netgis.events.call( netgis.events.MAP_MOVE, params );
		};
		
		var onMapClick = function( event )
		{
			popupContent.empty();
			popupControls.show();
			
			netgis.menu.clearSideContent();
			netgis.menu.toggleSideMenu( true );
			
			// KML / GeoRSS Feature Info
			var features = [];
			var pixel = map.getEventPixel( event.originalEvent );
			
			map.forEachFeatureAtPixel
			(
				pixel,
				function( feature )
				{
					features.push( feature );
				}
			);
	
			for ( var f = 0; f < features.length; f++ )
			{
				var feature = features[ f ];
				
				//TODO: use html template
							
				var panelId = "popup-panel-feature-" + f;

				var content = "<p></p>";

				content += "<div class='panel panel-primary'>";

				content += "<div class='panel-heading clickable' data-toggle='collapse' data-target='#" + panelId + "'>";
				content += "<h4 class='panel-title'>" + feature.get( "title" ) + "</h4>";
				content += "</div>";

				content += "<div id='" + panelId + "' class='panel-collapse collapse'>";
				content += "<div class='panel-body'>";
				content += feature.get( "name" );
				content += "</div>";
				content += "</div>";

				content += "</div>";

				content += "</div>";

				var element = $( content );
				element.find( ".panel-heading" ).click( function() { element.find( "#" + panelId ).collapse( "toggle" ); } );

				//popupContent.append( element );
				netgis.menu.addSideContent( element );
			}
			
			// WMS Feature Info
			var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Active, netgis.component.Queryable ] );
			
			//console.info( "QUERY:", layers );
			
			$.each
			(
				layers,
				function( key, layer )
				{
					var mapLayer = layerList[ layer.id ];
					
					if ( mapLayer )
					{
						// Do Query
						var url = mapLayer.getSource().getGetFeatureInfoUrl
						(
							event.coordinate,
							view.getResolution(),
							netgis.config.MAP_PROJECTION,
							{
								"INFO_FORMAT": "text/html" //"text/plain" //"text/javascript" //"application/json" //"text/html",
							}
						);
				
						if ( url )
						{
							//TODO: use html template
							
							var panelId = "popup-panel-" + key;
							
							var content = "<p></p>";
							
							content += "<div class='panel panel-primary'>";

							content += "<div class='panel-heading clickable' data-toggle='collapse' data-target='#" + panelId + "'>";
							content += "<h4 class='panel-title'>" + layer.components.title.value + "<span class='pull-right glyphicon glyphicon-share' title='In neuem Tab öffnen'></span></h4>";
							content += "</div>";
							
							var frame_url = url;
							
							if ( netgis.config.URL_FEATURE_INFO_PROXY && netgis.config.URL_FEATURE_INFO_PROXY.length > 0 )
								frame_url = netgis.config.URL_FEATURE_INFO_PROXY + "?q=" + encodeURIComponent( url );								

							content += "<div id='" + panelId + "' class='panel-collapse collapse'>";
							content += "<div class='panel-body'>";
							//content += "<iframe seamless width='280' height='100' src='" + frame_url + "'></iframe>";
							content += "<iframe seamless width='280' src='" + frame_url + "'></iframe>";
							content += "</div>";
							content += "</div>";

							content += "</div>";
							
							content += "</div>";
							
							var element = $( content );
							var heading = element.find( ".panel-heading" );
							
							heading.click( function() { element.find( "#" + panelId ).collapse( "toggle" ); } );
							heading.find( ".pull-right" ).click
							(
								function( evt )
								{
									evt.stopPropagation();
									window.open( url, "_blank" );
								}
							);
							
							//popupContent.append( element );
							netgis.menu.addSideContent( element );
						}
					}
				}
			);
			
			// Height Info
			var heightUrl = netgis.config.URL_HEIGHT_REQUEST + "&coord=" + event.coordinate.join( "," );
			
			if ( netgis.config.URL_HEIGHT_PROXY && netgis.config.URL_HEIGHT_PROXY.length > 0 )
				heightUrl = netgis.config.URL_HEIGHT_PROXY + "?q=" + encodeURIComponent( heightUrl );
			
			$.get
			(
				heightUrl,
				{
				},
				function( data )
				{
					var panelId = "popup-panel-height";

					var content = "<p></p>";

					content += "<div class='panel panel-primary'>";

					content += "<div class='panel-heading clickable' data-toggle='collapse' data-target='#" + panelId + "'>";
					content += "<h4 class='panel-title'>" + "Digitales Höhenmodell" + "</h4>";
					content += "</div>";

					content += "<div id='" + panelId + "' class='panel-collapse collapse'>";
					content += "<div class='panel-body'>";
					content += data;
					content += "</div>";
					content += "</div>";

					content += "</div>";

					content += "</div>";

					var element = $( content );
					element.find( ".panel-heading" ).click( function() { element.find( "#" + panelId ).collapse( "toggle" ); } );

					//popupContent.append( element );
					netgis.menu.addSideContent( element );
				}
			);
			
			// Show Popup
			popupContainer.fadeIn( 200 );
			popupOverlay.setPosition( event.coordinate );
			
			//TODO: use showPopup method
			
			// Go back to pan mode
			//if ( ! netgis.sidebar.isVisible() ) pan();
		};
		
		var onDigitizeClick = function( evt )
		{
			var coords = evt.coordinate;
			
			if ( netgis.params.get( "xID" ) )
			{
				var id = netgis.params.getString( "xID" );
				var element = $( "#" + id );
				element.val( coords[ 0 ] );
			}
			
			if ( netgis.params.get( "yID" ) )
			{
				var id = netgis.params.getString( "yID" );
				var element = $( "#" + id );
				element.val( coords[ 1 ] );
			}
			
			netgis.events.call( netgis.events.DIGITIZE_CLICK, { x: coords[ 0 ], y: coords[ 1 ] } );
		};
		
		var onPopupClose = function( event )
		{
			popupContainer.fadeOut( 200 );
			
			// Close Menu
			netgis.menu.toggleSideMenu( false );
			netgis.menu.clearSideContent();
			
			// Default Interaction
			pan();
		};
		
		var onFeatureInfoResponse = function( data, layer )
		{
			//console.info( "FEATURE INFO:", data );
			
			popupContent.append( data );
		};
		
		var onLayersLoading = function( params )
		{
			if ( params.loading === true )
			{
				layerList = {};
			   
				map.getLayers().clear();
				
				map.addLayer( measureLayer );
			}
			else
			{
				// Background Layers
				createBackgroundLayers();
				
				// GeoRSS Layers
				var georssEntities = netgis.entities.get( [ netgis.component.Layer, netgis.component.GeoRSS ] );
				
				for ( var g = 0; g < georssEntities.length; g++ )
				{
					var georss = georssEntities[ g ];
					var xml = georss.components.georss.data;
					
					var features = [];
					
					var entries = xml.find( "entry" );
					
					$.each
					(
						entries,
						function( i, e )
						{
							var entry = $( e );
							
							// Geometry
							var where = entry.find( "georss\\:where, where" ); //entry.find( "[nodeName='georss:where']" );
							var point = where.find( "gml\\:Point, Point" );
							var srsName = point.attr( "srsName" );
							var pos = point.find( "gml\\:pos, pos" );
							
							// Coords
							var coords = pos.text().split( " " );
							coords[ 0 ] = parseFloat( coords[ 0 ] );
							coords[ 1 ] = parseFloat( coords[ 1 ] );
							
							if ( srsName && srsName.length > 0 )
								coords = ol.proj.transform( coords, srsName, netgis.config.MAP_PROJECTION );
							else
								coords = ol.proj.fromLonLat( coords, netgis.config.MAP_PROJECTION );
							
							// Feature
							var feature = new ol.Feature
							(
								{
									geometry: new ol.geom.Point( coords )
								}
							);
					
							// Style
							var imageUrl = entry.find( "imageUrl" ).attr( "href" );
							var imageSize = entry.find( "imageSize" ).text();
							
							if ( imageUrl )
							{
								imageSize = imageSize ? parseInt( imageSize ) : 10;
								
								var style = new ol.style.Style
								(
									{
										image: new ol.style.Icon
										(
											{
												src: imageUrl,
												anchor: [ 0.5, 1.0 ],
												//size: [ imageSize, imageSize ]
											}
										)
									}
								);
						
								feature.setStyle( style );
							}
							
							// Properties
							feature.set( "title", "GeoRSS-Objekt" );
							
							var html = "";
							
							var title = entry.find( "title" ).text();							
							var link = entry.find( "link" ).attr( "href" );
							
							if ( title )
							{
								html += title;
							}
							
							if ( link )
							{
								if ( title ) html += "<br/>";
								html += "<a target='_blank' href='" + link + "'>" + link + "</a>";
							}
							
							feature.set( "name", html );
					
							// Done
							features.push( feature );
						}
					);
					
					var style = new ol.style.Style
					(
						{
							image: new ol.style.Circle
							(
								{
									radius: netgis.config.GEORSS_POINT_RADIUS,
									fill: new ol.style.Fill( { color: netgis.config.GEORSS_POINT_FILL_COLOR } ),
									stroke: new ol.style.Stroke( { color: netgis.config.GEORSS_POINT_STROKE_COLOR, width: netgis.config.GEORSS_POINT_STROKE_WIDTH } )
								}
							)
						}
					);
					
					var mapLayer = new ol.layer.Vector
					(
						{
							source: new ol.source.Vector
							(
								{
									features: features
								}
							),
							style: style,
							zIndex: 9999
						}
					);
			
					/*features = mapLayer.getSource().getFeatures();
					for ( var f = 0; f < features.length; f++ )
					{
						features[ f ].set( "title", "GeoRSS-Objekt" );
					}*/
			
					georss.set( new netgis.component.MapLayer( mapLayer ) );
				}
				
				// KML Layers
				var kmls = netgis.entities.get( [ netgis.component.Layer, netgis.component.KML ] );
				
				for ( var k = 0; k < kmls.length; k++ )
				{
					var kml = kmls[ k ];
					
					var mapLayer = new ol.layer.Vector
					(
						{
							source: new ol.source.Vector
							(
								{
									url:	kml.components.kml.url,
									format: new ol.format.KML( { extractStyles: false } )
								}
							),
							zIndex: 999,
							opacity: kml.components.opacity ? kml.components.opacity.value : netgis.config.MAP_DEFAULT_OPACITY,
							style: onKmlStyle
						}
					);
			
					// Set Features Title
					var features = mapLayer.getSource().getFeatures();
					
					for ( var f = 0; f < features.length; f++ )
					{
						features[ f ].set( "title", "Zeichnungsobjekt" );
					}
			
					kml.set( new netgis.component.MapLayer( mapLayer ) );
					
					//var hasStyle = mapLayer.getStyle() instanceof ol.style.Style.defaultFunction ? false : true;
				}
				
				// WMS Layers
				var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Name ] );
				
				for ( var l = 0; l < layers.length; l++ )
				{
					var layer = layers[ l ];
					
					var children = netgis.entities.find( netgis.component.Parent, "value", layer );
					var hasChildren = children.length > 0;
					
					// No group layers
					if ( hasChildren === false )
					{
						// Get root service url
						var child = layer;
						var parent = null;
						var url = layer.components.url ? layer.components.url.value : null; //null;
						
						//if ( url ) console.info( "URL LAYER:", layer );

						do
						{
							parent = child.components.parent ? child.components.parent.value : null;

							if ( parent.components.service ) url = parent.components.url.value;

							child = parent;
						}
						while ( ! url && parent );

						// Create WMS layer
						var name = layer.components.name.value;

						var index = layers.length - l; //TODO: real layer order?
						
						var opacity = layer.components.opacity ? layer.components.opacity.value : netgis.config.MAP_DEFAULT_OPACITY;

						//TODO: layer list deprecated because of map layer component?
						var mapLayer = layerList[ layer.id ] = createLayerWms( url, name, index, opacity );

						//TODO: create only layers without children (non-groups)
						layer.set( new netgis.component.MapLayer( mapLayer ) );
					}
				}
			}
		};
						
		var onLayerToggle = function( params )
		{		   
			var layer = netgis.entities.get( params.id );
			
			//console.info( "LAYER:", layer, layer.get( netgis.component.MapLayer ) );
			
			var mapLayer = layer.get( netgis.component.MapLayer ); //.value; //layerList[ layer.id ]
			
			if ( mapLayer && mapLayer.value )
				mapLayer = mapLayer.value;
			else
				return;

			var exists = map.getLayers().getArray().indexOf( mapLayer ) > -1 ? true : false;

			if ( layer.components.active )
			{
				if ( exists === false ) map.addLayer( mapLayer );
			}
			else
			{
				if ( exists === true ) map.removeLayer( mapLayer );
			}
		};
		
		var onLayerRemove = function( event )
		{
			//var exists = map.getLayers().getArray().indexOf( layerList[ event.id ] ) > -1 ? true : false;
			
			//if ( exists === true ) map.removeLayer( layerList[ event.id ] );
			
			var mapLayer = netgis.entities.get( event.id ).components.maplayer;
			
			if ( mapLayer ) map.removeLayer( mapLayer.value );
		};
		
		var onLayerMoveUp = function( event )
		{
			var layer = netgis.entities.get( event.id );
			var mapLayer = layer.get( netgis.component.MapLayer ).value;
			var index = mapLayer.getZIndex();
			
			// Find layer on top
			var layers = netgis.entities.get( [ netgis.component.MapLayer, netgis.component.Active ] );
			
			var targetIndex = 1000; //index;
			var targetLayer = null;
			
			for ( var l = 0; l < layers.length; l++ )
			{
				var otherLayer = layers[ l ].get( netgis.component.MapLayer ).value;
				
				if ( otherLayer === mapLayer ) continue;
				
				var otherIndex = otherLayer.getZIndex();
				
				if ( otherIndex > index )
					if ( otherIndex < targetIndex )
					{
						targetIndex = otherIndex;
						targetLayer = otherLayer;
					}
			}
			
			if ( targetLayer !== null )
			{
				mapLayer.setZIndex( targetIndex );
				targetLayer.setZIndex( index );
			}
		};
		
		var onLayerMoveDown = function( event )
		{
			var layer = netgis.entities.get( event.id );
			var mapLayer = layer.get( netgis.component.MapLayer ).value;
			var index = mapLayer.getZIndex();
			
			// Find layer on top
			var layers = netgis.entities.get( [ netgis.component.MapLayer, netgis.component.Active ] );
			
			var targetIndex = 0; //index;
			var targetLayer = null;
			
			for ( var l = 0; l < layers.length; l++ )
			{
				var otherLayer = layers[ l ].get( netgis.component.MapLayer ).value;
				
				if ( otherLayer === mapLayer ) continue;
				
				var otherIndex = otherLayer.getZIndex();
				
				if ( otherIndex < index )
					if ( otherIndex > targetIndex )
					{
						targetIndex = otherIndex;
						targetLayer = otherLayer;
					}
			}
			
			if ( targetLayer !== null )
			{
				mapLayer.setZIndex( targetIndex );
				targetLayer.setZIndex( index );

				//console.info( "MOVE DOWN:", index, targetIndex );
			}
		};
		
		var onLayerZoom = function( event )
		{
			var layer = netgis.entities.get( event.id );
			var extent = layer.get( netgis.component.Extent );
			
			if ( extent )
			{
				var min = ol.proj.fromLonLat( [ extent.minx, extent.miny ], netgis.config.MAP_PROJECTION );
				var max = ol.proj.fromLonLat( [ extent.maxx, extent.maxy ], netgis.config.MAP_PROJECTION );
				
				viewExtent( min[ 0 ], min[ 1 ], max[ 0 ], max[ 1 ] );
			}
			else
			{
				viewExtent( netgis.config.MAP_EXTENT[ 0 ], netgis.config.MAP_EXTENT[ 1 ], netgis.config.MAP_EXTENT[ 2 ], netgis.config.MAP_EXTENT[ 3 ] );
			}
		};
		
		var onPositionToggle = function( params )
		{
			positionActive = params.active;
			
			if ( positionActive )
				map.addOverlay( positionOverlay );
			else
				map.removeOverlay( positionOverlay );
			
			//geolocation.setTracking( positionActive );
			
			if ( positionActive )
			{
				if ( ! navigator.geolocation )
					netgis.events.call( netgis.events.POSITION_ERROR, {} );
				else
					geolocationHandler = navigator.geolocation.watchPosition( onGeolocationChange, onGeolocationError, { timeout: 10 * 1000 } );
			}
			else
			{
				navigator.geolocation.clearWatch( geolocationHandler );
			}
		};
		
		var onPositionCenter = function( params )
		{
			positionCenter = params.active;
		};
		
		var onGeolocationChange = function( event )
		{
			geolocationCoords = proj4( "EPSG:4326", netgis.config.MAP_PROJECTION, [ event.coords.longitude, event.coords.latitude ] );
			
			if ( positionActive ) updateGeolocation();
			if ( positionCenter ) viewGeolocation();
		};
		
		var onGeolocationError = function( event )
		{
			netgis.events.call( netgis.events.POSITION_ERROR, event );
		}
		
		var onMeasureStart = function( event )
		{
			measureSource.clear();
			
			measureFeature = event.feature;
			
			measureListener = measureFeature.getGeometry().on( "change", onMeasureChange );
			
			if ( measureFeature.getGeometry() instanceof ol.geom.Polygon )
			{
				measureOverlay.setPositioning( "center-center" );
				measureOverlay.setOffset( [ 0, 0 ] );
			}
			else
			{
				measureOverlay.setPositioning( "bottom-center" );
				measureOverlay.setOffset( [ 0, -12 ] );
			}
			
			measureOverlay.setPosition( event.coordinate );
			
			measureElement.show();
			measureClose.hide();
		};
		
		var onMeasureEnd = function( event )
		{
			measureFeature = null;
			
			ol.Observable.unByKey( measureListener );
			
			//measureOverlay.setOffset( [ 0, 0 ] );
			//measureElement.hide();
			measureClose.show();
		};
		
		var onMeasureChange = function( event )
		{
			var geom = event.target;
			
			if ( geom instanceof ol.geom.Polygon )
			{
				measureContent.html( formatArea( geom ) );
				measureOverlay.setPosition( geom.getInteriorPoint().getCoordinates() );
			}
			else
			{
				measureContent.html( formatLength( geom ) );
				measureOverlay.setPosition( geom.getLastCoordinate() );
			}
		};
		
		var onKmlStyle = function( feature )
		{
			if ( feature )
			{
				// Get attributes
				var props = feature.getProperties();

				// Add alpha value to colors
				var color;

				var stroke = props[ "fill" ];
				color = ol.color.asArray( stroke );
				color = color.slice();
				color[ 3 ] = props[ "stroke-opacity" ];
				stroke = color;

				var fill = props[ "fill" ];
				color = ol.color.asArray( fill );
				color = color.slice();
				color[ 3 ] = props[ "fill-opacity" ];
				fill = color;

				// Create style
				var style = new ol.style.Style
				(
					{
						stroke: new ol.style.Stroke
						(
							{
								color: stroke,
								width: props[ "stroke-width" ]
							}
						),
						fill: new ol.style.Fill
						(
							{
								color: fill
							}
						)
					}
				)

				return style;
			}
		};
		
		// Public Interface
		var iface =
		{
			init:				init,
			pan:				pan,
			zoomBoxIn:			zoomBoxIn,
			zoomBoxOut:			zoomBoxOut,
			zoomIn:				zoomIn,
			zoomOut:			zoomOut,
			viewFull:			viewFull,
			viewExtent:			viewExtent,
			viewGeolocation:	viewGeolocation,
			viewHome:			viewHome,
			viewUndo:			viewUndo,
			viewRedo:			viewRedo,
			featureInfo:		featureInfo,
			setView:			setView,
			setViewScale:		setViewScale,
			updateSize:			updateSize,
			getZoom:			getZoom,
			getCenter:			getCenter,
			isPositionActive:	isPositionActive,
			isPositionCenter:	isPositionCenter,
			setBackgroundLayer:	setBackgroundLayer,
			setScale:			setScale,
			getScale:			getScale,
			showPopup:			showPopup,
			hidePopup:			hidePopup,
			setScaleBarVisible:	setScaleBarVisible,
			
			setInteraction:		setInteraction,
			interactions:		interactions,
			
			update:				update
		};
		
		return iface;
	}
)();
