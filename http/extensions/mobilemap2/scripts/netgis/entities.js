/*
 * NetGIS WebGIS Client
 * 
 * (c) Sebastian Pauli, NetGIS, 2017
 */

/**
 * The entities module.
 * @namespace
 */
netgis.entities =
(
	function()
	{
		"use strict";
		
		// Private Variables
		var entities = [];
		
		// Private Methods
		var create = function( components, addToFront )
		{
			var entity = new netgis.Entity();
			
			if ( components )
			{
				for ( var c = 0; c < components.length; c++ )
				{
					entity.set( components[ c ] );
				}
			}
			
			if ( addToFront )
				entities.unshift( entity );
			else
				entities.push( entity );
			
			return entity;
		};
		
		var destroy = function( id )
		{
			for ( var e = 0; e < entities.length; e++ )
			{
				if ( entities[ e ].id === id )
				{
					entities.splice( e, 1 );
					return;
				}
			}
		};
		
		var getAll = function()
		{
			return entities;
		};
		
		/** Get entities by id or component array */
		var get = function( filter )
		{
			// Single ID
			if ( typeof filter === "string" ) return getById( filter );
			
			// Multiple Components
			if ( filter.length ) return entities.filter( componentsFilter( filter ) );
			
			// Single Component
			return entities.filter( componentsFilter( [ filter ] ) ); //TODO: not working?
		};
		
		var componentsFilter = function( components )
		{
			return function( entity )
			{
				var count = 0;
				
				for ( var c = 0; c < components.length; c++ )
				{
					if ( entity.get( components[ c ] ) ) count++;
				}
				
				if ( count === components.length ) return true;
				
				return false;
			};
		};
		
		var getById = function( id )
		{
			for ( var e = 0; e < entities.length; e++ )
			{
				if ( entities[ e ].id === id ) return entities[ e ];
			}
			
			return null;
		};
		
		var find = function( component, key, value )
		{
			return entities.filter( findFilter( component, key, value ) );
		};
		
		var findFilter = function( component, key, value )
		{
			return function( entity )
			{
				var c = entity.get( component );
				
				if ( c && c[ key ] === value ) return true;
				
				return false;
			};
		};
		
		var sorted = function( array, component, key, descending )
		{
			var clone = array.slice();
			
			return clone.sort( sortCompare( component, key, descending ) );
		};
		
		var sortCompare = function( component, key, descending )
		{
			return function( a, b )
			{
				var va = a.get( component );
				var vb = b.get( component );
				
				if ( va && vb )
				{
					if ( descending )
						return vb[ key ] - va[ key ];
					else
						return va[ key ] - vb[ key ];
				}
				
				return 0;
			};
		};
		
		//TODO: get sorted entities
		
		//TODO: export to table (new html page tab?) for debug output of all entities / components
		
		// Event Handlers
		
		// Public Interface
		var iface =
		{
			create:		create,
			destroy:	destroy,
			getAll:		getAll,
			get:		get,
			find:		find,
			sorted:		sorted
		};
		
		return iface;
	}
)();