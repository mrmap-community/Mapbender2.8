
/** Entity */
netgis.Entity = function Entity()
{
	// Generate ID
	this.id = ( + new Date() ).toString( 16 ) + ( Math.random() * 100000000 | 0 ).toString( 16 ) + netgis.Entity.prototype.count;
	
	// Counter
	netgis.Entity.prototype.count++;
	
	// Component Data
	this.components = {};
	
	return this;
};

netgis.Entity.prototype.count = 0;

//NOTE: deprecated ?
/** Add Component */
netgis.Entity.prototype.add = function( component )
{
	this.components[ component.name ] = component;
	
	return this;
};

/** Remove Component */
netgis.Entity.prototype.remove = function( component )
{
	// Allow component name function or string
	var name = component;
	
	if ( typeof component === "function" )
	{
		name = component.prototype.name;
	}
	
	delete this.components[ name ];
	
	return this;
};

netgis.Entity.prototype.get = function( component )
{
	// Allow component name function or string
	var name = component;
	
	if ( typeof component === "function" )
	{
		name = component.prototype.name;
	}
	
	return this.components[ name ];
};

//NOTE: what's the difference between this and "add" ?
netgis.Entity.prototype.set = function( component )
{
	var name = component.name;
	
	if ( typeof component === "function" )
	{
		name = component.prototype.name;
	}
	
	this.components[ name ] = component;
	
	return this;
};

netgis.Entity.prototype.toggle = function( component )
{
	// Allow component name function or string
	var name = component.prototype.name;
	
	if ( typeof this.components[ name ] === "undefined" )
		this.add( new component() );
	else
		this.remove( component );
	
	return this;
};

/** Print to console */
netgis.Entity.prototype.print = function print()
{
	console.info( JSON.stringify( this, null, 4 ) );
	
	return this;
}