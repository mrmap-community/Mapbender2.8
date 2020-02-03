/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017-2019
 */

/**
 * The NetGIS Menu module.
 * @namespace
 */
netgis.menu =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var navbarContainer;
		var layerMenu;
		var searchInput;
		var searchPanel;
		var searchList;
		var searchResults;
		var searchQuery;
		//var searchSpinner;
		var searchButton;
		var searchMessage;
		var scaleItems;
		var scaleCustom;
		var scaleDivider;
		var scaleValue;
		
		var sideMenu;
		var sideMenuContent;
		
		var legendPanel;
		var geolocationDialog;
		
		// Private Methods
		var init = function()
		{
			navbarContainer = $( "#navbar-container" );
			layerMenu = $( "#layer-menu" );
			searchInput = $( "#search-input" );
			searchPanel = $( "#search-panel" );
			searchList = $( "#search-list" );
			//searchSpinner = $( "#search-spinner" );
			searchButton = $( "#search-button" );
			searchMessage = $( "#search-message" );
			legendPanel = $( "#legend-panel" );
			geolocationDialog = $( "#geolocation-dialog" );
			
			// Side Menu
			sideMenu = $( "#side-menu" );
			sideMenuContent = sideMenu.find( "#side-menu-content" );
			
			// Scales
			var scaleMenu = $( "#scale-menu" );
			var scales = netgis.config.MAP_SCALES;
			
			//TODO: use html template
			for ( var s = 0; s < scales.length; s++ )
			{
				var item = "<li data-scale='" + scales[ s ] + "'>";
				item += "<a href='#' class='btn-scale'>";
				item += "<span class='icon-left glyphicon glyphicon-unchecked'></span>";
				item += "1 : " + scales[ s ];
				item += "</a>";
				item += "</li>";
				
				scaleMenu.append( item );
			}
			
			scaleItems = scaleMenu.find( ".btn-scale" );
			scaleItems.click( onScaleClick );
			
			scaleCustom = scaleMenu.find( "#scale-custom" );
			scaleDivider = scaleMenu.find( "#scale-divider" );
			scaleValue = scaleCustom.find( ".scale-value" );
			
			onMapMove( null );
			
			// Search
			searchInput.val( "" );
			
			// Info Links
			if ( netgis.config.URL_USAGE_TERMS )
			{
				var usageTerms = $( "#usage-terms-link" );
				usageTerms.attr( "href", netgis.config.URL_USAGE_TERMS );
			}
			
			// Events
			$( ".navbar-brand" ).click( onBrandClick );
			
			$( ".tool-view-full" ).click( function( event ) { netgis.map.viewFull(); event.preventDefault(); } );
			$( ".tool-pan" ).click( function( event ) { netgis.map.pan(); event.preventDefault(); } );
			$( ".tool-zoom-box-in" ).click( function( event ) { netgis.map.zoomBoxIn(); event.preventDefault(); } );
			$( ".tool-zoom-box-out" ).click( function( event ) { netgis.map.zoomBoxOut(); event.preventDefault(); } );
			$( ".tool-view-undo" ).click( function( event ) { netgis.map.viewUndo(); event.preventDefault(); } );
			$( ".tool-view-redo" ).click( function( event ) { netgis.map.viewRedo(); event.preventDefault(); } );
			
			$( ".tool-feature-info" ).click( function( event ) { netgis.map.featureInfo(); event.preventDefault(); } );
			$( ".tool-measure-length" ).click( function( event ) { netgis.map.setInteraction( netgis.map.interactions.measureLength ); event.preventDefault(); } );
			$( ".tool-measure-area" ).click( function( event ) { netgis.map.setInteraction( netgis.map.interactions.measureArea ); event.preventDefault(); } );
			
			$( ".tool-zoom-in" ).click( netgis.map.zoomIn );
			$( ".tool-zoom-out" ).click( netgis.map.zoomOut );
			
			$( ".btn-position-active" ).click( onPositionActiveClick );
			$( ".btn-position-center" ).click( onPositionCenterClick );
			$( ".btn-position-zoom" ).click( onPositionZoomClick );
			
			$( "#legend-dialog" ).on( "show.bs.modal", onLegendShow );
			
			layerMenu.parent().on( "hidden.bs.dropdown", onLayerMenuHidden );
			
			searchInput.change( onSearchChange );
			searchInput.keyup( onSearchKey );
			searchButton.click( function() { searchPlace( searchInput.val() ); } );
			
			netgis.events.on( netgis.events.LAYERS_LOADING, onLayersLoading );
			netgis.events.on( netgis.events.LAYER_TOGGLE, onLayerToggle );
			netgis.events.on( netgis.events.LAYER_REMOVE, onLayerRemove );
			
			netgis.events.on( netgis.events.POSITION_TOGGLE, onPositionToggle );
			netgis.events.on( netgis.events.POSITION_CENTER, onPositionCenter );
			netgis.events.on( netgis.events.POSITION_ERROR, onPositionError );
			
			netgis.events.on( netgis.events.MAP_MOVE, onMapMove );
			
			// Parameters
			if ( netgis.params.getInt( "menu_title" ) === 0 ) setTitleVisible( false );
			if ( netgis.params.getInt( "layer_menu" ) === 0 ) setLayerMenuVisible( false );
			if ( netgis.params.getInt( "tool_menu" ) === 0 ) setToolMenuVisible( false );
			if ( netgis.params.getInt( "position_menu" ) === 0 ) setPositionMenuVisible( false );
			if ( netgis.params.getInt( "scale_menu" ) === 0 ) setScaleMenuVisible( false );
			if ( netgis.params.getInt( "info_menu" ) === 0 ) setInfoMenuVisible( false );
			if ( netgis.params.getInt( "search_bar" ) === 0 ) setSearchBarVisible( false );
			if ( netgis.params.getInt( "zoom_bar" ) === 0 ) setZoomBarVisible( false );
			if ( netgis.params.getInt( "feature_info" ) === 0 ) setFeatureInfoButtonVisible( false );
			if ( netgis.params.getInt( "legend_button" ) === 0 ) setLegendButtonVisible( false );
		};
		
		var setTitleVisible = function( on )
		{
			if ( on )
			{
				$( "#menu-title" ).show();
			}
			else
			{
				$( "#menu-title" ).hide();
			}
		};
		
		var setLayerMenuVisible = function( on )
		{
			if ( on )
			{
				$( "#layer-menu-container" ).show();
			}
			else
			{
				$( "#layer-menu-container" ).hide();
			}
		};
		
		var setToolMenuVisible = function( on )
		{
			if ( on )
			{
				$( "#tool-menu-container" ).show();
			}
			else
			{
				$( "#tool-menu-container" ).hide();
			}
		};
		
		var setPositionMenuVisible = function( on )
		{
			if ( on )
			{
				$( "#geolocation-menu-container" ).show();
			}
			else
			{
				$( "#geolocation-menu-container" ).hide();
			}
		};
		
		var setScaleMenuVisible = function( on )
		{
			if ( on )
			{
				$( "#scale-menu-container" ).show();
			}
			else
			{
				$( "#scale-menu-container" ).hide();
			}
		};
		
		var setInfoMenuVisible = function( on )
		{
			if ( on )
			{
				$( "#info-menu-container" ).show();
			}
			else
			{
				$( "#info-menu-container" ).hide();
			}
		};
		
		var setSearchBarVisible = function( on )
		{
			if ( on )
			{
				$( "#search-bar-container" ).show();
			}
			else
			{
				$( "#search-bar-container" ).hide();
			}
		};
		
		var setZoomBarVisible = function( on )
		{
			if ( on )
			{
				$( "#zoom-bar-container" ).show();
			}
			else
			{
				$( "#zoom-bar-container" ).hide();
			}
		};
		
		var setFeatureInfoButtonVisible = function( on )
		{
			if ( on )
			{
				var e = $( "#feature-info-button" );
				e.show();
				e.parent().toggleClass( "btn-group-vertical", true );
			}
			else
			{
				var e = $( "#feature-info-button" );
				e.hide();
				e.parent().toggleClass( "btn-group-vertical", false );
			}
		};
		
		var setLegendButtonVisible = function( on )
		{
			if ( on )
			{
				var e = $( "#legend-button" );
				e.show();
				e.parent().toggleClass( "btn-group-vertical", true );
			}
			else
			{
				var e = $( "#legend-button" );
				e.hide();
				e.parent().toggleClass( "btn-group-vertical", false );
			}
		};
		
		var update = function()
		{
			// Clear
			layerMenu.find( ".divider" ).nextAll().remove();
			
			// Build
			var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title ] );
				
			for ( var l = 0; l < layers.length; l++ )
			{
				var layer = layers[ l ];

				var item =
				{
					id:		layer.id,
					title:	layer.components.title.value
				};

				// Group or Child Item
				var hasChildren = false;

				for ( var i = 0; i < layers.length; i++ )
				{
					var child = layers[ i ];

					var parent = child.components.parent ? child.components.parent.value : null;

					if ( parent && layer === parent )
					{
						hasChildren = true;
						break;
					}
				}

				if ( hasChildren )
				{
					layerMenu.loadTemplate( $( "#menu-group-template" ), item, { append: true } );
				}
				else
				{
					layerMenu.loadTemplate( $( "#menu-layer-template" ), item, { append: true } );
				}
			}

			// Append children to parent groups
			layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title, netgis.component.Parent ] );

			for ( var l = 0; l < layers.length; l++ )
			{
				var layer = layers[ l ];

				var parent = layer.components.parent ? layer.components.parent.value : null;

				if ( parent && ! parent.components.service )
				{
					var childElement = layerMenu.find( "#" + layer.id );
					var parentElement = layerMenu.find( "#" + parent.id );

					childElement.detach();
					parentElement.find( ".child-container" ).append( childElement );
				}
			}

			//TODO: optimize - insert into DOM only once

			//layerMenu.find( ".btn-layer" ).click( onLayerClick );
			layerMenu.find( ".btn-group" ).click( onGroupClick );

			layerMenu.find( ".collapser" ).click( function() { $( this ).next().collapse( "toggle" ); } );
		};
		
		var searchPlace = function( query )
		{
			//layerResultsList.empty();
			//layerResultsCount.text( "0" );
			
			toggleSideMenu( false );
			
			query = encodeURIComponent( $.trim( query ) );
			
			if ( query.length < 2 ) return;
			
			if ( query === searchQuery ) return;
			
			searchButton.find( "i" ).toggleClass( "hidden" );
			
			/*
			$.getJSON
			(
				//"http://www.geoportal.rlp.de/mapbender/extensions/mobilemap/mod_mapbender/search_proxy.php?languageCode=de&resultTarget=web&maxResults=40&searchText=Naturschutz"
				"http://www.geoportal.rlp.de/mapbender/extensions/mobilemap/mod_mapbender/search_proxy.php",
				{
					languageCode: "de",
					resultTarget: "web",
					maxResults: 40,
					searchText: query
				},
				onSearchResponse
			);
			*/
		   
			var maxResults = 5;
			
			var url = netgis.config.URL_SEARCH_REQUEST + "?outputFormat=json&resultTarget=web&searchEPSG=25832&maxResults=" + maxResults + "&maxRows=" + maxResults + "&searchText=" + query + "&featureClass=P&style=full&name_startsWith=" + query;
			
			if ( netgis.config.URL_SEARCH_PROXY && netgis.config.URL_SEARCH_PROXY.length > 0 )
			{
				$.getJSON
				(
					netgis.config.URL_SEARCH_PROXY,
					{
						q: encodeURI( url )
					},
					onSearchResponse
				);
			}
			else
			{
				$.getJSON
				(
					url,
					{
					},
					onSearchResponse
				);
			}
	
			searchQuery = query;
		};
		
		var toggleSideMenu = function( on )
		{
			sideMenu.toggleClass( "visible", on );
		};
		
		var clearSideContent = function( content )
		{
			sideMenuContent.empty();
		};
		
		var addSideContent = function( item )
		{
			sideMenuContent.append( item );
		};
		
		// Event Handlers
		$( document ).ready( init );
		
		var onBrandClick = function( event )
		{
			location.reload();
			
			event.preventDefault();
		}
		
		var onLayersLoading = function( params )
		{
			if ( params.loading )
			{
				//layerMenu.empty();
				
				layerMenu.find( ".divider" ).nextAll().remove();
			}
			else
			{			   
				var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title ] );
				
				for ( var l = 0; l < layers.length; l++ )
				{
					var layer = layers[ l ];
					
					var item =
					{
						id:		layer.id,
						title:	layer.components.title.value
					};
					
					// Group or Child Item
					var hasChildren = false;
					
					for ( var i = 0; i < layers.length; i++ )
					{
						var child = layers[ i ];
						
						var parent = child.components.parent ? child.components.parent.value : null;
						
						if ( parent && layer === parent )
						{
							hasChildren = true;
							break;
						}
					}
					
					if ( hasChildren )
					{
						layerMenu.loadTemplate( $( "#menu-group-template" ), item, { append: true } );
					}
					else
					{
						layerMenu.loadTemplate( $( "#menu-layer-template" ), item, { append: true } );
					}
				}
				
				// Append children to parent groups
				layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title, netgis.component.Parent ] );
				
				for ( var l = 0; l < layers.length; l++ )
				{
					var layer = layers[ l ];
					
					var parent = layer.components.parent ? layer.components.parent.value : null;
					
					if ( parent && ! parent.components.service )
					{
						var childElement = layerMenu.find( "#" + layer.id );
						var parentElement = layerMenu.find( "#" + parent.id );
						
						childElement.detach();
						parentElement.children( ".child-container" ).append( childElement );
					}
				}
				
				//TODO: optimize - insert into DOM only once
				
				layerMenu.find( ".btn-layer" ).click( onLayerClick );
				layerMenu.find( ".btn-group" ).click( onGroupClick );
				
				layerMenu.find( ".collapser" ).click( function() { $( this ).next().collapse( "toggle" ); } );
			}
		};
		
		var onLayerClick = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			var id = $( this ).data( "id" );
			
			var layer = netgis.entities.get( id );
			
			layer.toggle( netgis.component.Active );
			
			netgis.events.call( netgis.events.LAYER_TOGGLE, { id: id } );
		};
		
		var onGroupClick = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			var target = $( event.target );
			
			if ( target.hasClass( "icon-left" ) )
			{
				var id = target.parent().data( "id" );
				var layer = netgis.entities.get( id );
				
				layer.toggle( netgis.component.Active );
				
				var active = layer.components.active ? true : false;
				
				// Toggle child layers
				var children = netgis.entities.find( netgis.component.Parent, "value", layer );
				
				for ( var c = 0; c < children.length; c++ )
				{
					var child = children[ c ];
					
					if ( active === true )
						child.set( new netgis.component.Active() );
					else
						child.remove( netgis.component.Active );
					
					netgis.events.call( netgis.events.LAYER_TOGGLE, { id: child.id } );
				}
			}
			else
			{
				var self = $( this );
				var childContainer = self.next( "ul" );
				var item = self.parent();
				var list = item.parent();

				list.find( "ul" ).not( childContainer ).toggle( false );
				childContainer.toggle();
			}
		};
		
		var onLayerToggle = function( params )
		{
			var item = layerMenu.find( "#" + params.id );
			var layer = netgis.entities.get( params.id );

			var active = layer.components.active ? true : false;

			var icon = item.find( ".icon-left" );
			
			icon.toggleClass( "glyphicon-check", active );
			icon.toggleClass( "glyphicon-unchecked", ! active );
			
			// Toggle Parent
			var parent = layer.components.parent ? layer.components.parent.value : null;
			
			if ( parent )
			{
				var parentItem = layerMenu.find( "#" + parent.id );
				
				var parentChildren = netgis.entities.find( netgis.component.Parent, "value", parent );
				
				var parentActive = false;
				
				for ( var c = 0; c < parentChildren.length; c++ )
				{
					if ( parentChildren[ c ].components.active )
					{
						parentActive = true;
						break;
					}
				}
				
				if ( parentActive === true )
					parent.set( new netgis.component.Active() );
				else
					parent.remove( netgis.component.Active );
				
				var parentIcon = parentItem.children( "a" ).find( ".icon-left" );
				
				parentIcon.toggleClass( "glyphicon-check", parentActive );
				parentIcon.toggleClass( "glyphicon-unchecked", ! parentActive );
				
				//TODO: recurse for parent parents etc.
				
				//netgis.events.call( netgis.events.LAYER_TOGGLE, { id: parent.id } );
				
				// Toggle Parent Parent
				var parent2 = parent.components.parent ? parent.components.parent.value : null;

				if ( parent2 )
				{
					var parent2Item = layerMenu.find( "#" + parent2.id );

					var parent2Children = netgis.entities.find( netgis.component.Parent, "value", parent2 );

					var parent2Active = false;

					for ( var c = 0; c < parent2Children.length; c++ )
					{
						if ( parent2Children[ c ].components.active )
						{
							parent2Active = true;
							break;
						}
					}

					if ( parent2Active === true )
						parent2.set( new netgis.component.Active() );
					else
						parent2.remove( netgis.component.Active );

					var parent2Icon = parent2Item.children( "a" ).find( ".icon-left" );

					parent2Icon.toggleClass( "glyphicon-check", parent2Active );
					parent2Icon.toggleClass( "glyphicon-unchecked", ! parent2Active );
					
					// Toggle Parent Parent
					var parent3 = parent.components.parent2 ? parent.components.parent2.value : null;

					if ( parent3 )
					{
						var parent3Item = layerMenu.find( "#" + parent3.id );

						var parent3Children = netgis.entities.find( netgis.component.Parent, "value", parent3 );

						var parent3Active = false;

						for ( var c = 0; c < parent3Children.length; c++ )
						{
							if ( parent3Children[ c ].components.active )
							{
								parent3Active = true;
								break;
							}
						}

						if ( parent3Active === true )
							parent3.set( new netgis.component.Active() );
						else
							parent3.remove( netgis.component.Active );

						var parent3Icon = parent3Item.children( "a" ).find( ".icon-left" );

						parent3Icon.toggleClass( "glyphicon-check", parent3Active );
						parent3Icon.toggleClass( "glyphicon-unchecked", ! parent3Active );
					}
				}
			}
		};
		
		var onLayerRemove = function( event )
		{
			var item = layerMenu.find( "#" + event.id );
			
			item.remove();
		};
		
		var onLayerMenuHidden = function( event )
		{
			// Hide sub menus too
			layerMenu.find( "ul" ).toggle( false );
		};
		
		var onPositionActiveClick = function( event )
		{
			netgis.events.call( netgis.events.POSITION_TOGGLE, { active: ! netgis.map.isPositionActive() } );
			
			event.preventDefault();
			event.stopPropagation();
		};
		
		var onPositionCenterClick = function( event )
		{
			netgis.events.call( netgis.events.POSITION_CENTER, { active: ! netgis.map.isPositionCenter() } );
			
			event.preventDefault();
			event.stopPropagation();
		};
		
		var onPositionZoomClick = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			netgis.map.viewGeolocation();
		};
		
		var onPositionToggle = function( params )
		{
			var icon = $( ".btn-position-active" ).find( ".icon-left" );
			
			icon.toggleClass( "glyphicon-check", params.active );
			icon.toggleClass( "glyphicon-unchecked", ! params.active );
		};
		
		var onPositionCenter = function( params )
		{
			var icon = $( ".btn-position-center" ).find( ".icon-left" );
			
			icon.toggleClass( "glyphicon-check", params.active );
			icon.toggleClass( "glyphicon-unchecked", ! params.active );
		};
		
		var onPositionError = function( event )
		{
			geolocationDialog.modal( "show" );
			
			netgis.events.call( netgis.events.POSITION_TOGGLE, { active: false } );
		};
		
		var onScaleClick = function( event )
		{
			var scale = $( this ).parent().data( "scale" );
			
			netgis.map.setScale( parseInt( scale ) );
			
			event.preventDefault();
		};
		
		var onSearchChange = function( event )
		{
			//console.info( "SEARCH:", layerSearchInput.val() );
		};
		
		var onSearchKey = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			switch ( event.which )
			{
				case 8: // Backspace
					break;
				case 13: // Enter
					searchPlace( searchInput.val() );
					break;
			}
		};
		
		var onSearchResponse = function( data, status, xhr )
		{
			// Clear
			searchList.children().first().nextAll().remove();
			
			// Add
			searchResults = data.geonames;
			
			if ( searchResults.length > 0 )
			{
				searchPanel.collapse( "show" );
				searchMessage.hide();
			}
			else
			{
				searchPanel.collapse( "show" );
				searchMessage.show();
			}
			
			for ( var r = 0; r < searchResults.length; r++ )
			{
				var result = searchResults[ r ];
				
				var title = result.title;
				/*
				var regex = new RegExp( searchQuery, "gi" );
				
				title = title.replace( regex, "<b>" + searchQuery + "</b>" );
				*/
				/*			
				var queryPos = title.search( regex );
				
				while ( queryPos > -1 ) //if ( queryPos > -1 )
				{
					title = title.substr( 0, queryPos ) + "!" + title.substr( queryPos );
					
					queryPos = title.search( regex );
				}
				*/
				
				var item =
				{
					index:		r,
					title:		title
				};
				
				searchList.loadTemplate( $( "#search-result-template" ), item, { append: true } );
			}
			
			searchList.children( ".search-result" ).click( onSearchResultClick );
			
			searchButton.find( "i" ).toggleClass( "hidden" );
		};
		
		var onSearchResultClick = function( event )
		{
			event.stopPropagation();
			event.preventDefault();
			
			var item = $( this );
			var index = item.data( "index" );
			var result = searchResults[ index ];
			
			if ( result )
			{
				if ( result.category === "str" || result.category === "haus" )
					netgis.map.showPopup( ( parseFloat( result.minx ) + parseFloat( result.maxx ) ) / 2, ( parseFloat( result.miny ) + parseFloat( result.maxy ) ) / 2, result.title );
				else
					netgis.map.hidePopup();
				
				netgis.map.viewExtent( result.minx, result.miny, result.maxx, result.maxy );
				
				if ( netgis.config.MIN_SEARCH_SCALE && netgis.map.getScale() < netgis.config.MIN_SEARCH_SCALE )
					netgis.map.setScale( netgis.config.MIN_SEARCH_SCALE );
				
				searchPanel.collapse( "hide" );
				$( ".navbar-collapse" ).collapse( "hide" ); //TODO: menu.hide()
			}
		};
		
		var onLegendShow = function( event )
		{
			// http://map1.naturschutz.rlp.de/service_lanis/mod_wms/wms_getmap.php?mapfile=wms_naturschutz_rlp&service=wms&version=1.1.1&request=GetLegendGraphic&format=image/png&layer=vogelschutzgebiet
			
			// Clear
			legendPanel.empty();
			
			// Add
			var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title, netgis.component.Legend, netgis.component.Name, netgis.component.Active ] );
			
			for ( var l = 0; l < layers.length; l++ )
			{
				var layer = layers[ l ];
				
				var url = layer.components.legend.url;
				
				// Append parameters if not already given in url string
				if ( url.search( /GetLegendGraphic/i ) === -1 )
				{
					url += "service=wms";
					url += "&version=1.1.1";
					url += "&request=GetLegendGraphic";
					url += "&format=" + layer.components.legend.format;
					url += "&layer=" + layer.components.name.value;
				}
				
				var item =
				{
					title:	layer.components.title.value,
					url:	url
				};
				
				legendPanel.loadTemplate( $( "#legend-item-template" ), item, { append: true } );
			}
		};
		
		var onMapMove = function( evt )
		{
			var scale = netgis.map.getScale();
			
			var found = false;
			
			scaleItems.each
			(
				function( key, value )
				{
					var item  = $( value ).parent();
					var icon = item.find( ".glyphicon" );
					
					var checked = false;
					
					if ( scale === item.data( "scale" ) )
					{
						var checked = true;
						found = true;
					}
					
					icon.toggleClass( "glyphicon-check", checked );
					icon.toggleClass( "glyphicon-unchecked", ! checked );
				}
			);
	
			if ( found === true )
			{
				scaleCustom.hide();
				scaleDivider.hide();
			}
			else
			{
				scaleValue.text( scale );
				scaleCustom.data( "scale", scale );
				
				scaleCustom.show();
				scaleDivider.show();
			}
		};
		
		// Public Interface
		var iface =
		{
			update: update,
			toggleSideMenu: toggleSideMenu,
			clearSideContent: clearSideContent,
			addSideContent: addSideContent,
			
			setTitleVisible: setTitleVisible,
			setLayerMenuVisible: setLayerMenuVisible,
			setToolMenuVisible: setToolMenuVisible,
			setPositionMenuVisible: setPositionMenuVisible,
			setScaleMenuVisible: setScaleMenuVisible,
			setInfoMenuVisible: setInfoMenuVisible,
			setSearchBarVisible: setSearchBarVisible,
			setZoomBarVisible: setZoomBarVisible,
			setFeatureInfoButtonVisible: setFeatureInfoButtonVisible,
			setLegendButtonVisible: setLegendButtonVisible
		};
		
		return iface;
	}
)();