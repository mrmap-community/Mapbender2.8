/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2018
 */

/**
 * The NetGIS Layer Manager module.
 * @namespace
 */
netgis.manager =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var layerContainer;
		var layerList; //TODO: rename to layerListAll
		var layerCount;
		var layerListMap;
		var layerResultsPanel;
		var layerResultsList;
		var layerResultsCount;
		var layerResultsAlert;
		var searchButton;
		
		var layerSearchInput;
		
		var searchResults;
		
		// Private Methods
		var init = function()
		{
			layerContainer = $( "#layer-container" );
			layerList = $( "#layer-list-all" );
			layerListMap = $( "#layer-list-map" );
			layerSearchInput = $( "#layer-search-input" );
			layerCount = $( "#layer-count" );
			layerResultsPanel = $( "#layer-results" );
			layerResultsList = $( "#layer-results-list" );
			layerResultsCount = $( "#layer-results-count" );
			layerResultsAlert = $( "#layer-results-alert" );
			searchButton = $( "#layer-search-button" );
			
			layerSearchInput.val( "" );
			
			// Events
			netgis.events.on( netgis.events.LAYERS_LOADING, onLayersLoading );
			netgis.events.on( netgis.events.LAYER_TOGGLE, onLayerToggle );
			netgis.events.on( netgis.events.LAYER_REMOVE, onLayerRemove );
			netgis.events.on( netgis.events.LAYER_MOVE_UP, onLayerMoveUp );
			netgis.events.on( netgis.events.LAYER_MOVE_DOWN, onLayerMoveDown );
			
			//TODO: find better place for this general ui solution
			var collapses = $( ".collapse" ); //container.find( ".panel-collapse" );
			collapses.on( "show.bs.collapse", onPanelExpand );
			collapses.on( "hide.bs.collapse", onPanelCollapse );
			
			layerSearchInput.change( onLayerSearchChange );
			layerSearchInput.keyup( onLayerSearchKey );
			searchButton.click( function() { searchLayers( layerSearchInput.val() ); } );
			
			$( "#layer-dialog" ).on( "show.bs.modal", function( event ) { $( ".navbar-collapse" ).collapse( "hide" ); } );  //TODO: menu.hide()
			
			//$( ".navbar-collapse" ).collapse( "hide" );
			
			// Testing
			//searchLayers( "Natur" );
		};
		
		var searchLayers = function( query )
		{
			layerResultsList.empty();
			layerResultsCount.text( "0" );
			
			query = encodeURIComponent( $.trim( query ) );
			
			if ( query.length < 2 ) return;
			
			searchButton.find( "i" ).toggleClass( "hidden" );
		   
			var url = netgis.config.URL_LAYERS_REQUEST + "?languageCode=de&resultTarget=web&maxResults=40&searchText=" + query;
		   
			if ( netgis.config.URL_LAYERS_PROXY && netgis.config.URL_LAYERS_PROXY.length > 0 )
			{
				$.getJSON
				(
					netgis.config.URL_LAYERS_PROXY,
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
		};
		
		var updateMapLayers = function()
		{
			//TODO: inefficient approach, rebuilds list on every toggle
			
			layerListMap.empty();
		   
			var activeLayers = netgis.entities.get( [ netgis.component.MapLayer, netgis.component.Title, netgis.component.Active ] );
			
			for ( var l = 0; l < activeLayers.length; l++ )
			{
				var activeLayer = activeLayers[ l ];
				
				var children = netgis.entities.find( netgis.component.Parent, "value", activeLayer );
				var hasChildren = children.length > 0;
				
				if ( hasChildren === false )
				{
					// Add to list
					var data =
					{
						id:		activeLayer.id,
						title:	activeLayer.components.title.value
					};

					layerListMap.loadTemplate( $( "#map-layer-template" ), data, { append: true } );
				}
			}
			
			layerListMap.find( ".btn-layer" ).click( onLayerClick );
			layerListMap.find( ".btn-up" ).click( onLayerUpClick );
			layerListMap.find( ".btn-down" ).click( onLayerDownClick );
			layerListMap.find( ".btn-zoom" ).click( onLayerZoomClick );
			
			updateMapLayerOrder();
		};
		
		var updateMapLayerOrder = function()
		{
			layerListMap.find( ".btn-up" ).show();
			layerListMap.find( ".btn-down" ).show();
			
			layerListMap.children( ":first" ).find( ".btn-up" ).hide();
			layerListMap.children( ":last" ).find( ".btn-down" ).hide();
		};
		
		// Event Handlers
		$( document ).ready( init );
		
		var onPanelExpand = function( event )
		{
			var collapser = $( event.target ).prev();
			var icon = collapser.find( ".icon-right" );
			
			icon.toggleClass( "glyphicon-chevron-down", false );
			icon.toggleClass( "glyphicon-chevron-up", true );
		};
		
		var onPanelCollapse = function( event )
		{
			var collapser = $( event.target ).prev();
			var icon = collapser.find( ".icon-right" );
			
			icon.toggleClass( "glyphicon-chevron-down", true );
			icon.toggleClass( "glyphicon-chevron-up", false );
		};
		
		var onLayersLoading = function( params )
		{
			var count = 0;
			
			if ( params.loading )
			{
				layerList.empty();
			}
			else
			{
				var layers = netgis.entities.get( [ netgis.component.Layer, netgis.component.Title ] );
				
				for ( var l = 0; l < layers.length; l++ )
				{
					var layer = layers[ l ];
					
					var data =
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
						layerList.loadTemplate( $( "#layer-group-template" ), data, { append: true } );
					}
					else
					{
						layerList.loadTemplate( $( "#layer-item-template" ), data, { append: true } );
						count++;
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
						var childElement = layerList.find( "#" + layer.id );
						var parentElement = layerList.find( "#" + parent.id );
						
						childElement.detach();
						parentElement.children( ".child-container" ).append( childElement );
					}
				}
				
				//TODO: optimize - insert into DOM only once
				
				layerList.find( ".btn-layer" ).click( onLayerClick );
				layerList.find( ".btn-group" ).click( onGroupClick );
				
				layerList.find( ".btn-remove" ).click( onLayerRemoveClick );
				layerList.find( ".btn-remove-group" ).click( onGroupRemoveClick );
			}
			
			layerCount.text( count );
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
				$( this ).next().collapse( "toggle" );
			}
		};
		
		var onLayerToggle = function( params )
		{
			var item = layerList.find( "#" + params.id );
			var layer = netgis.entities.get( params.id );

			var active = layer.components.active ? true : false;

			var icon = item.find( ".icon-left" );

			icon.toggleClass( "glyphicon-check", active );
			icon.toggleClass( "glyphicon-unchecked", ! active );
			
			// Toggle Parent
			var parent = layer.components.parent ? layer.components.parent.value : null;
			
			if ( parent )
			{
				var parentItem = layerList.find( "#" + parent.id );
				
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
				
				// Toggle Parent Parent
				var parent2 = parent.components.parent ? parent.components.parent.value : null;

				if ( parent2 )
				{
					var parent2Item = layerList.find( "#" + parent2.id );

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
						var parent3Item = layerList.find( "#" + parent3.id );

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
			
			// Add to map layers		   		
			updateMapLayers();
		};
		
		var onGroupRemoveClick = function( evt )
		{
			evt.preventDefault();
			evt.stopPropagation();
			
			var item = $( evt.target ).closest( "[data-id]" );
			var children = item.next( ".child-container" ).find( ".btn-layer" );
			
			for ( var i = 0; i < children.length; i++ )
			{
				var child = $( children[ i ] );
				var id = child.data( "id" );
				
				netgis.events.call( netgis.events.LAYER_REMOVE, { id: id } );
			}
		};
		
		var onLayerRemoveClick = function( event )
		{
			event.stopPropagation();
			event.preventDefault();
			
			var id = $( event.target ).closest( "[data-id]" ).data( "id" );
			
			netgis.events.call( netgis.events.LAYER_REMOVE, { id: id } );
		};
		
		var onLayerRemove = function( event )
		{
			var item = layerList.find( "#" + event.id );
			
			item.fadeOut( 300, function() { item.remove(); } );
			
			//TODO: hack to get layer count
			var isService = item.find( ".btn-layer" ).length === 0;
			var hasChildren = item.find( ".btn-group" ).length > 0;
			
			if ( hasChildren === false && isService === false )
				layerCount.text( parseInt( layerCount.text() ) - 1 );
			
			updateMapLayers();
		};
		
		var onLayerUpClick = function( event )
		{
			event.stopPropagation();
			event.preventDefault();
			
			var id = $( event.target ).closest( "[data-id]" ).data( "id" );
			
			netgis.events.call( netgis.events.LAYER_MOVE_UP, { id: id } );
		};
		
		var onLayerDownClick = function( event )
		{
			event.stopPropagation();
			event.preventDefault();
			
			var id = $( event.target ).closest( "[data-id]" ).data( "id" );
			
			netgis.events.call( netgis.events.LAYER_MOVE_DOWN, { id: id } );
			
			//TODO: bug when moving background layer down
		};
		
		var onLayerZoomClick = function( event )
		{
			event.stopPropagation();
			event.preventDefault();
			
			var id = $( event.target ).closest( "[data-id]" ).data( "id" );
			
			netgis.events.call( netgis.events.LAYER_ZOOM, { id: id } );
		};
		
		var onLayerMoveUp = function( event )
		{			
			var item = layerListMap.find( "#" + event.id );
			var target = item.prev( "[id]" );
			
			// Can move up
			if ( target.length > 0 )
			{
				item.insertBefore( target );
			}
			
			updateMapLayerOrder();
		};
		
		var onLayerMoveDown = function( event )
		{			
			var item = layerListMap.find( "#" + event.id );
			var target = item.next( "[id]" );
			
			// Can move up
			if ( target.length > 0 )
			{
				item.insertAfter( target );
			}
			
			updateMapLayerOrder();
		};
		
		//TODO: testing, may be deprecated
		var onLayerMoveUp01 = function( event )
		{
			var item = layerList.find( "#" + event.id );
			var target = item.prev( "[id]" );
			var layer = netgis.entities.get( event.id );
			
			// Move out of group
			if ( target.length === 0 )
			{
				target = item.parent().parent(); //item.closest( ".hover-group" ); //item.parents ?
				layer.set( new netgis.component.Parent( netgis.entities.get( target.attr( "id" ) ) ) );
				console.info( "MOVE OUT OF GROUP" );
				item.insertBefore( target );
				return;
			}
			
			// Move into group
			var container = target.find( ".child-container" );
			
			if ( container.length > 0 )
			{
				var expanded = container.hasClass( "in" ); //container.attr( "aria-expanded" );
				
				if ( expanded )
				{
					container.append( item );
					layer.set( new netgis.component.Parent( netgis.entities.get( target.attr( "id" ) ) ) );
					console.info( "MOVE INTO GROUP" );
					return;
				}
			}
			
			item.insertBefore( target );
		};
		
		var onLayerSearchChange = function( event )
		{
			//console.info( "SEARCH:", layerSearchInput.val() );
		};
		
		var onLayerSearchKey = function( event )
		{
			//console.info( "SEARCH KEY:", layerSearchInput.val(), event.which );
			
			switch ( event.which )
			{
				case 8: // Backspace
					break;
				case 13: // Enter
					searchLayers( layerSearchInput.val() );
					break;
			}
		};
		
		var onSearchResponse = function( data, status, xhr )
		{
			//console.info( "SEARCH RESPONSE:", status, data );
			
			searchResults = data.wms.srv;
			
			if ( searchResults.length > 0 )
			{
				layerResultsPanel.collapse( "show" );
			}
			
			var total = data.wms.md.nresults;
			
			if ( total > 40 )
			{
				layerResultsAlert.toggleClass( "hidden", false );
				layerResultsAlert.find( "#layer-results-total" ).text( total );
			}
			else
			{
				layerResultsAlert.toggleClass( "hidden", true );
			}
			
			var children;
			
			function recursive( layer )
			{
				if ( layer && layer.layer )
				{
					for ( var c = 0; c < layer.layer.length; c++ )
						recursive( layer.layer[ c ] );
				}
				else if ( layer )
				{
					children.push( layer );
				}
				
				return children.length;
			}
			
			for ( var l = 0; l < searchResults.length; l++ )
			{
				var layer = searchResults[ l ];
				
				// Get children recursively
				children = [];
				var count = recursive( layer );
				
				var rows = "";
				for ( var c = 0; c < children.length; c++ )
				{
					var child = "<tr class='layer-result-child' data-layer-id='" + children[ c ].id + "'>";
					child += "<td>";
					child += children[ c ].title;
					child += "<span class='btn-add pull-right glyphicon glyphicon-plus'></span>";
					child += "</td>";
					child += "</tr>";
					
					rows += child;
				}
				
				// Add to list
				var item =
				{
					index:		l,
					id:			layer.id,
					title:		layer.title + " (" + count + ")",
					count:		count,
					details:	layer.abstract,
					rows:		rows
				};

				layerResultsList.loadTemplate( $( "#layer-result-template" ), item, { append: true } );
			}
			
			//TODO: add click handler for layer result child
			
			layerResultsCount.text( searchResults.length );
			
			layerResultsList.find( ".list-group-item-heading" ).click( onResultClick );
			layerResultsList.find( ".layer-result-child" ).click( onResultChildClick );
			layerResultsList.find( ".collapser" ).click( onResultDetailsClick );
			
			searchButton.find( "i" ).toggleClass( "hidden" );
		};
		
		var onResultDetailsClick = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			var target = $( this );
			target.prev().collapse( "toggle" );
			target.children( ".glyphicon" ).toggleClass( "glyphicon-triangle-bottom glyphicon-triangle-top" );
		};
		
		var onResultClick = function( event )
		{
			event.preventDefault();
			event.stopPropagation();
			
			netgis.events.call( netgis.events.LAYERS_LOADING, { loading: true } );
			
			var item = $( this );
			var index = item.parent().data( "index" );
			
			var result = searchResults[ index ];
				
			// Service Group Layer
			var service = result;
			var serviceEntity = netgis.layers.createService( service, true );

			// Service Layers
			for ( var i = 0; i < service.layer.length; i++ )
			{
				var layer = service.layer[ i ];

				var layerEntity = netgis.layers.createLayer( layer, serviceEntity, true );
				
				//TODO: recursive layer adding, move to layers module

				// Child Layers
				if ( layer.layer )
				{
					for ( var j = 0; j < layer.layer.length; j++ )
					{
						var child = layer.layer[ j ];

						var childEntity = netgis.layers.createLayer( child, layerEntity, true );
						
						if ( child.layer )
						{
							for ( var k = 0; k < child.layer.length; k++ )
							{
								var child2 = child.layer[ k ];

								var child2Entity = netgis.layers.createLayer( child2, childEntity, true );
								
								if ( child2.layer )
								{
									for ( var m = 0; m < child2.layer.length; m++ )
									{
										var child3 = child2.layer[ m ];

										var child3Entity = netgis.layers.createLayer( child3, child2Entity, true );
									}
								}
							}
						}
					}
				}
			}
			
			netgis.events.call( netgis.events.LAYERS_LOADING, { loading: false } );
			
			layerResultsPanel.collapse( "hide" );
			layerContainer.collapse( "show" );
		};
		
		var onResultChildClick = function( evt )
		{
			evt.preventDefault();
			evt.stopPropagation();
			
			netgis.events.call( netgis.events.LAYERS_LOADING, { loading: true } );
			
			var item = $( this );
			var index = item.closest( ".list-group-item" ).data( "index" );
			
			var result = searchResults[ index ];
			
			// Service Group Layer
			var service = result;
			var serviceEntity = netgis.layers.createService( service, true );
			
			// Child Layer
			var id = parseInt( item.data( "layer-id" ) );
			
			// Service Layers
			for ( var i = 0; i < service.layer.length; i++ )
			{
				var layer = service.layer[ i ];
				
				var layerEntity = serviceEntity;
				
				if ( parseInt( layer.id ) === id )
				{
					layerEntity = netgis.layers.createLayer( layer, serviceEntity, true );
					break;
				}
				
				//TODO: recursive layer adding, move to layers module

				// Child Layers
				if ( layer.layer )
				{
					for ( var j = 0; j < layer.layer.length; j++ )
					{
						var child = layer.layer[ j ];
						
						var childEntity = layerEntity;
						
						if ( parseInt( child.id ) === id )
						{
							childEntity = netgis.layers.createLayer( child, layerEntity, true );
							break;
						}
						
						if ( child.layer )
						{
							for ( var k = 0; k < child.layer.length; k++ )
							{
								var child2 = child.layer[ k ];
								
								var child2Entity = childEntity;
								
								if ( parseInt( child2.id ) === id )
								{
									child2Entity = netgis.layers.createLayer( child2, childEntity, true );
									break;
								}
								
								if ( child2.layer )
								{
									for ( var m = 0; m < child2.layer.length; m++ )
									{
										var child3 = child2.layer[ m ];
										
										var child3Entity = child2Entity;
										
										if ( parseInt( child3.id ) === id )
										{
											child3Entity = netgis.layers.createLayer( child3, child2Entity, true );
											break;
										}
									}
									
								}
							}
							
						}
					}
					
				}
				
			}
			
			netgis.events.call( netgis.events.LAYERS_LOADING, { loading: false } );
			
			layerResultsPanel.collapse( "hide" );
			layerContainer.collapse( "show" );
		};
		
		// Public Interface
		var iface =
		{
		};
		
		return iface;
	}
)();