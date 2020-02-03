
/**
 * The main NetGIS namespace. Should contain all other modules.
 * @namespace
 */
var netgis =
{			
};

netgis.client =
(
	function ()
	{
		"use strict";

		// Variables
		var customCallback;

		// Methods
		var load = function( target, callback )
		{
			var container = $( "#" + target );
			
			container.load
			(
				"./scripts/netgis/client.html",
				null,
				function( data, status )
				{
					if ( status === "success" )
					{
					}
				}
			);
	
			customCallback = callback;
			
			//container.find( ".navbar" ).toggleClass( "navbar-embedded", true );
		}
		
		var onDocReady = function()
		{
			$( "#client-loader" ).fadeOut();
			if ( customCallback ) customCallback();
		};

		// Public Interface
		var iface =
		{
			load: load,
			onDocReady: onDocReady
		};

		return iface;
	}
)();


// Entry Point
//console.info( "Loading Client..." );
//$( "#geoportal-container" ).load( "./scripts/netgis/client.html" );