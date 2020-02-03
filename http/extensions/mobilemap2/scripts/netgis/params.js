/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2019
 */

/**
 * The NetGIS Parameters module (for runtime settings, not launch config!).
 * @namespace
 */
netgis.params =
(
	function ()
	{
		"use strict";

		// Variables
		var getParams;

		// Methods
		var init = function()
		{
			// Get Parameters
			var url = window.location.search.substr( 1 );
			url = url.split( "&" );
			
			getParams = {};
			
			for ( var i = 0; i < url.length; i++ )
			{
				var p = url[ i ].split( "=" );
				getParams[ p[ 0 ].toLowerCase() ] = p[ 1 ];
			}
		};
		
		var get = function( key )
		{
			return getParams[ key.toLowerCase() ];
		};
		
		var getString = function( key )
		{
			//TODO: check string output, otherwise same as raw get
			return getParams[ key.toLowerCase() ];
		};
		
		var getInt = function( key )
		{
			return parseInt( getParams[ key.toLowerCase() ] );
		};
		
		var getFloat = function( key )
		{
			return parseFloat( getParams[ key.toLowerCase() ] );
		};
		
		// Entry Point
		init();
		
		// Public Interface
		var iface =
		{
			get:		get,
			getString:	getString,
			getInt:		getInt,
			getFloat:	getFloat
		};

		return iface;
	}
)();