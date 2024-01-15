/******************************************************************************
*
* MIT - Copyright 2022 https://github.com/mrmap-community/
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
* Software is furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
* WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
* $Id$
*
* Project:  https://github.com/mrmap-community/Mapbender2.8
* Purpose:  Package for uploading geometries into digitize guis
* Author:   Armin Retterath, armin.retterath@gmail.com
*
* Files
*  - http/plugins/mod_uploadGeometry.php
*  - http/plugins/mb_geometry_server.php
*
* SQL:
*
* INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('Geoportal-RLP','uploadGeometry',2,1,'upload','Upload geometry','div','../img/button_blue_red/zoomFull_off.png','',180,95,24,24,1000,'','','div','../plugins/mod_uploadGeometry.php','','mapframe1','','http://www.mapbender.org/index.php/GeometryImport');
* INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('Geoportal-RLP', 'uploadGeometry', 'digitizeId', 'digitize', '' ,'var');
*
*
******************************************************************************/

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
