/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017
 */

/**
 * The NetGIS Menu module.
 * @namespace
 */
netgis.loader =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var dialog;
		var bar;
		
		// Private Methods
		var init = function()
		{
			dialog = $( "#loader-dialog" );
			bar = dialog.find( ".progress-bar" );
			
			netgis.events.on( netgis.events.LAYERS_LOADING, onLayersLoading );
		};
		
		var show = function()
		{
			dialog.modal
			(
				{
					backdrop: "static",
					keyboard: false,
					show: true
				}
			);
	
			bar.width( "100%" );
		};
		
		var hide = function()
		{
			dialog.modal( "hide" );
		};
		
		// Event Handlers
		$( document ).ready( init );
		
		var onLayersLoading = function( event )
		{
			if ( event.loading === true )
			{
				//show();
			}
			else
			{
				//hide();
			}
		};
		
		// Public Interface
		var iface =
		{
		};
		
		return iface;
	}
)();