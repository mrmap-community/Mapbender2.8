/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017
 */

/**
 * Map event types.
 * @namespace
 * @name events
 * @memberof netgis
 */
/*
netgis.events =
{
	/** Called after the map has moved to a new view ("map-move"). *
	MAP_MOVE:		"map-move",
	/** Called after a request box has been drawn on the map ("request-box"). *
	REQUEST_BOX:	"request-box",
	/** Called after the request box has been cleared ("request-clear"). *
	REQUEST_CLEAR:	"request-clear"
};
*/

netgis.events =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var callbacks;
		var log;
		
		// Private Methods
		var init = function()
		{
			callbacks = [];
			
			log = false;
		};
		
		/**
		 * Subscribe to a [map event]{@link netgis.events}.
		 * @memberof netgis
		 * @param {string|netgis.events} event Map event to subscribe to
		 * @param {function} callback Callback function to handle the event
		 */
		var on = function( event, callback )
		{
			if ( ! callbacks[ event ] )
			{
				callbacks[ event ] = [];
			}
			
			callbacks[ event ].push( callback );
		};
		
		/**
		 * Un-subscribe from a [map event]{@link netgis.events}.
		 * @memberof netgis
		 * @param {string|netgis.events} event Map event to unsubscribe from
		 * @param {function} [callback] Unsubscribe this callback or all
		 */
		var off = function( event, callback )
		{
			if ( callbacks[ event ] )
			{
				if ( callback )
				{
					// Remove Specific Callback
					for ( var i = 0; i < callbacks[ event ].length; i++ )
					{
						if ( callbacks[ event ][ i ] === callback )
						{
							callbacks[ event ].splice( i, 1 );
							break;
						}
					}
					
					if ( callbacks[ event ].length < 1 ) callbacks[ event ] = null;
				}
				else
				{
					// Remove All Callbacks
					callbacks[ event ] = null;
				}
			}
		};
		
		var call = function( event, params )
		{
			if ( log ) console.info( "EVENT:", event, params );
			
			if ( callbacks[ event ] )
			{
				for ( var i = 0; i < callbacks[ event ].length; i++ )
				{
					callbacks[ event ][ i ]( params );
				}
			}
		};
		
		var setLogging = function( on )
		{
			log = on;
		};
		
		// Event Handlers
		$( document ).ready( init );
		
		// Public Interface
		var iface =
		{
			on:			on,
			off:		off,
			call:		call,
			setLogging:	setLogging,
			
			/** Called after the map has moved to a new view ("map-move"). */
			MAP_MOVE:			"map-move",
			/** Called after a request box has been drawn on the map ("request-box"). */
			REQUEST_BOX:		"request-box",
			/** Called after the request box has been cleared ("request-clear"). */
			REQUEST_CLEAR:		"request-clear",
			
			LAYERS_LOADING:		"layers-loading",
			LAYER_TOGGLE:		"layer-toggle",
			LAYER_REMOVE:		"layer-remove",
			LAYER_MOVE_UP:		"layer-move-up",
			LAYER_MOVE_DOWN:	"layer-move-down",
			LAYER_ZOOM:			"layer-zoom",
			
			POSITION_TOGGLE:	"position-toggle",
			POSITION_CENTER:	"position-center",
			POSITION_ERROR:		"position-error",
			
			DIGITIZE_CLICK:		"digitize-click"
		};
		
		return iface;
	}
)();