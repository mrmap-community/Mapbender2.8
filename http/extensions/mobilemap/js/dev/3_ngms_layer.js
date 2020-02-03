//Vektorlayer
var vector_marker = new OpenLayers.Layer.Vector("Vector Layer", {});
//GPS Marker
var gps_marker = new OpenLayers.Layer.Vector("gps_marker", {                
	rendererOptions: {zIndexing: true}
});
//Baselayer
var atkis_praes_tms = new OpenLayers.Layer.TMS( "atkis_praes_tms",
        "http://www.gdi-rp-dienste2.rlp.de/mapcache/tms/",
        { 
		layername: 'test@UTM32',
		type: "jpg",
		serviceVersion:"1.0.0",
        gutter:0,
		buffer:0,
		isBaseLayer:true,
		transitionEffect:'resize',
        resolutions:[529.16666666670005270134,396.87500000000000000000,264.58333333330000414207,132.29166666669999585793,66.14583333330000414207,39.68750000000000000000,26.45833333330000058936,13.22916666669999941064,6.61458333329999970118,3.96875000000000000000,2.64583333330000014527,2.11666666670000003236,1.32291666670000007677,0.79375000000000000000,0.26458333330000001204,0.13229166670000001016],
        units: projUnits,
		projection: mapProj,
        sphericalMercator: false
        }
    );

var luftbilder = new OpenLayers.Layer.WMS( "luftbilder", 
	"http://geo4.service24.rlp.de/wms/dop40_geo4.fcgi?",
	{
	layers: "dop",
	format: "image/jpeg",
	transparent: "false",
	transitionEffect: 'resize'
	},
	{
	projection: mapProj,
	units: projUnits,
	singleTile: false,
	alwaysInRange: true,
	'isBaseLayer': true		
} );

var grenze_leer = new OpenLayers.Layer.WMS( "grenze_leer",
	"http://map1.naturschutz.rlp.de/service_basis/mod_wms/wms_getmap.php?mapfile=tk_rlp_gesamt&",
	{
	layers: "grenzen_land",
	format: "image/jpeg",
	transparent: "false",
	transitionEffect: 'resize'
	},
	{
	projection: mapProj,
	units: projUnits,
	singleTile: true,
	alwaysInRange: true,
	'isBaseLayer': true
} );


//Overlays (Test)

var likar = new OpenLayers.Layer.WMS( "likar",
	"http://geo4.service24.rlp.de/wms/lika_basis.fcgi?",
	{
	layers: "likar:likar",
	format: "image/png",
	transparent: "TRUE",
	transitionEffect: 'resize'
	},
	{
	projection: mapProj,
	units: projUnits,
	singleTile: true,
	//minScale: 0.1,
	//maxScale: 8000,
	'isBaseLayer': false,
	visibility: false,
	alwaysInRange: true
} );

var naturschutzgebiet = new OpenLayers.Layer.WMS( "naturschutzgebiet", //3
	"http://map1.naturschutz.rlp.de/service_lanis/mod_wms/wms_getmap.php?mapfile=naturschutzgebiet&",
	{
	layers: "naturschutzgebiet",
	format: "image/png",
	transparent: "TRUE",
	transitionEffect: 'resize'
	},
	{
	projection: mapProj,
	units: projUnits,
	opacity: 0.6,
	singleTile: true,
	'isBaseLayer': false,
	visibility: false,
	alwaysInRange: true
} );

//Layer hinzufügen
function addmyLayer(){//Layer hinzufügen
	map.addLayers([atkis_praes_tms, luftbilder, grenze_leer, likar, naturschutzgebiet, gps_marker, vector_marker]);	
}
