
/** Entity Components */
netgis.component = {};

/** Name */
netgis.component.Name = function( value )
{
	value = value || "";
	this.value = value;
	
	return this;
};

netgis.component.Name.prototype.name = "name";

/** Layer */
netgis.component.Layer = function( id )
{
	id = id || 0;
	this.id = id;
	
	return this;
};

netgis.component.Layer.prototype.name = "layer";

/** URL */
netgis.component.Url = function( value )
{
	value = value || "";
	this.value = value;
	
	return this;
};

netgis.component.Url.prototype.name = "url";

/** Title */
netgis.component.Title = function( value )
{
	value = value || "";
	this.value = value;
	
	return this;
};

netgis.component.Title.prototype.name = "title";

/** Active */
netgis.component.Active = function()
{
	return this;
};

netgis.component.Active.prototype.name = "active";

/** Parent */
netgis.component.Parent = function( value )
{
	this.value = value;
	
	return this;
};

netgis.component.Parent.prototype.name = "parent";

/** Position */
netgis.component.Position = function( value )
{
	this.value = value;
	
	return this;
};

netgis.component.Position.prototype.name = "position";

/** Service */
netgis.component.Service = function( id )
{
	this.id = id;
	
	return this;
};

netgis.component.Service.prototype.name = "service";

/** Queryable */
netgis.component.Queryable = function()
{
	return this;
};

netgis.component.Queryable.prototype.name = "queryable";

/** Map Layer */
netgis.component.MapLayer = function( value )
{
	this.value = value;
	
	return this;
};

netgis.component.MapLayer.prototype.name = "maplayer";

/** Order */
netgis.component.Order = function( value )
{
	this.value = value;
	
	return this;
};

netgis.component.Order.prototype.name = "order";

/** Legend */
netgis.component.Legend = function( url, format )
{
	this.url = url;
	this.format = format;
	
	return this;
};

netgis.component.Legend.prototype.name = "legend";

/** KML */
netgis.component.KML = function( url )
{
	this.url = url;
	
	return this;
};

netgis.component.KML.prototype.name = "kml";

/** GeoRSS */
netgis.component.GeoRSS = function( data )
{
	this.data = data;
	
	return this;
}

netgis.component.GeoRSS.prototype.name = "georss";

/** Extent */
netgis.component.Extent = function( minx, miny, maxx, maxy )
{
	this.minx = minx;
	this.miny = miny;
	this.maxx = maxx;
	this.maxy = maxy;
	
	return this;
};

netgis.component.Extent.prototype.name = "extent";

/** Opacity */
netgis.component.Opacity = function( value )
{
	this.value = value;
	
	return this;
};

netgis.component.Opacity.prototype.name = "opacity";
