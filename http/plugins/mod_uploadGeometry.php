/**
 * Package: uploadGeometry
 *
 * Description:
 * The user enters a coordinate tuple and selects the corresponding SRS 
 * from a select box. After submitting this form, Mapbender transforms
 * the coordinate tuple to the current SRS and zooms to the location.
 * 
 * Files:
 *  - http/javascripts/mod_uploadGeometry.php
 *  - http/php/mod_uploadGeometry_server.php
 *
 * SQL:
 *
 * Help:
 * http://www.mapbender.org/uploadGeometry.php
 *
 * Maintainer:
 * http://www.mapbender.org/User:Armin_Retterath
 * 
 * Parameters:
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License 
 * and Simplified BSD license.  
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

<?php
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
include '../include/dyn_js.php';
?>

var $upload_geometry = $(this);
 
var UploadGeometryApi = function(options) {
	var that = this;
	this.buildForm = function() {
		//Container elements - if needed
	};
	
	this.initForm = function() {
		//default
		$upload_geometry.upload({
			size: 10,
			timeout: 20000,
			url: "../plugins/jq_upload.php",
			callback: function(result,stat,msg){
				if(!result){ 
					alert(msg);
					return;
				}
	        	var uploadResultName = result.filename;
	        	var uploadResultOrigName = result.origFilename;
	            //file type check - see server side: xml, geoson, gml, shapezip - see mapbender.conf
	            //transform file to geojson in current epsg and push it to digitize module - if exists ;-)
	            //get current epsg
	            var actualTargetProjection = Mapbender.modules[options.target].getSRS();
	            //console.log("CRS: " + actualTargetProjection);
	            that.transformGeometry(uploadResultName, actualTargetProjection);
	            //console.log("transformGeometry invoked");
    		}
		});
	};
	
	this.transformGeometry = function(filename, targetCrs) {
		console.log("start transformation for: " + filename);
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_geometry_server.php",
			method: "transformGeometry",
			parameters: {
				filename: filename,
				targetCrs: targetCrs
			},
			callback: function (obj, success, message, errorCode) {	
				/*console.log(arguments[0]);//obj
				console.log(arguments[1]);//success
				console.log(arguments[2]);//message
				console.log(arguments[3]);//errorCode*/
				/*console.log("obj: " + obj);
				console.log("message: " + message);
				console.log("success: " + success);
				console.log("errorCode: " + errorCode);*/
				if (!success) {
					switch (errorCode) {
						case -1002:
							console.log("file: " + filename + " has problems: " + message);
							break;
						case -1:
							alert(message);
							break;
						default:
							alert(message);
							break;
					}
				} else {
				    //give back geometry object 
					//console.log("geometry: " + obj.geometry);
					that.pushGeometryToDigitize(obj.geometry);
				}
			}
		});
		req.send();
  	}
  	
  	this.pushGeometryToDigitize = function(geometry) {
  	    geometryJson = JSON.parse(geometry);
  	    //delete properties from geometries to allow storing them via wfs-t
  	    for (let i = geometryJson.features.length - 1; i >= 0; i--) {
    		geometryJson.features[i].properties = {};
    		//Actually only simple polygons are supported!!!! 
    		/*if (geometryJson.features[i].geometry.type == 'MultiPolygon') {
    			geometryJson.features[i].geometry.type = 'Polygon';
    		}*/
  		}
  	    window.frames[options.digitizeId].appendGeometryArrayFromGeojson(geometryJson);
  	    alert(JSON.stringify(geometryJson));
  	    alert('Geometries are pushed to digitize list.');
  	}
  	
	//this.buildForm();
	this.initForm();

};

Mapbender.events.init.register(function() {
	//alert(JSON.stringify(options));
	//Mapbender.modules[options.id] = $.extend(new UploadGeometry(),Mapbender.modules[options.id]);	
	$upload_geometry.mapbender(new UploadGeometryApi(options));
});
