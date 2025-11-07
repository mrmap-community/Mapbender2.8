/**
 * Package: sdi_download_widget
 *
 * Description:
 * Measure module with jQuery UI widget factory and RaphaelJS
 *
 * Files:
 *  - http/plugins/mb_download_widget.php
 *
 * Help:
 * 
 *
 * Maintainer:
 * http://www.mapbender.org/User:Armin_Retterath
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */
 
 /*
 * some todos: add fileidentifier to response of check_options, 
 * store polygon to global variable - done
 * test invocation of python scripts
 * show logged in user - otherwise the module must not work
 * rename python package to inspire-gpkg-manager
 * add links to metadata in html form
 * delete or actualize download form, when layertree is edited!
 * show loading symbol when calling server side script by ajax call
 * show selected area in html form (ha or skm)
 * test if layer should be invoked recursively to test coupling!
 */
 <?php 
 require_once dirname(__FILE__) . "/../classes/class_user.php";
 
 ?>
 var $sdi_download = $(this);
 
 var SdiDownloadApi = function (o) {
 
    var userLoggedIn = false;
 
 	var downloadDialog,
 		//digitizeDialog,
		button,
		that = this,
		inProgress = false,
		title = o.title,
		// from mb_digitize_widget.php
		icons = null,
        inProgress = false,
        digitizingFor = '',
        editedFeature = null,
        status = 'new-polygon',
        <?php 
        if (defined('GPKG_AREA_LIMIT') && GPKG_AREA_LIMIT !='') {
            $areaLimit = GPKG_AREA_LIMIT;
        } else {
            $areaLimit = "100";
        }
        echo 'areaLimit="' . $areaLimit . '";';
        ?>
        
        areaLimit = '<?php echo _mb("Dataset list"); ?>';
		defaultHtml = "<div title='" + title + "'></div>";
	    descriptionHtml = "<div id='description'><?php 
				echo nl2br(htmlentities(_mb("BETA: Module for download regional limited data as Geopackage. Actually the allowed region area is limited to ") . $areaLimit . _mb(" km2."), ENT_QUOTES, "UTF-8"));
			?><br><a style='' href='https://www.geopackage.org/' target='_blank'><img src='../img/geopackage-2.png' width='25' height='25'></a></div>";
		startDigitizeHtml = "<div id='start-digitize' ><?php 
				echo nl2br(htmlentities(_mb("Click in the map and digitize the area of interest."), ENT_QUOTES, "UTF-8"));
			?></div>";
	    userInfoHtml = "<div id='identity'><?php
                echo nl2br(htmlentities(_mb("Your logged in as") . ":", ENT_QUOTES, "UTF-8"));
            ?><div id='user-info'></div></div>";
        datasetListHeaderHtml = "<div id='dataset-list-header' ><b><?php 
				echo nl2br(htmlentities(_mb("List of available datasets") . ":", ENT_QUOTES, "UTF-8"));
			?></b></div>";
		spinnerHtml = "<div id='load-options-spinner' ><b><?php 
				echo nl2br(htmlentities(_mb("Check download options (may need some minutes)") . ":", ENT_QUOTES, "UTF-8"));
			?></b><br><img src='../img/indicator_wheel.gif'></div>";
	    // table of found dataset metadata information
        datasetListHtml = '<div id="dataset-list" title="<?php echo _mb("Dataset list"); ?>">' +
      	   '</div>';

   
 	var create = function () {
 		/*
		 Initialise download dialog
		*/
		downloadDialog = $(defaultHtml);
		downloadDialog.append($(userInfoHtml));
		downloadDialog.append($(descriptionHtml));
		downloadDialog.append($(startDigitizeHtml));
		downloadDialog.append($(datasetListHeaderHtml));
		downloadDialog.append($(spinnerHtml));
		downloadDialog.append($(datasetListHtml));
		downloadDialog.dialog({
			width: 370,
			autoOpen: false,
			position: [o.$target.offset().left+20, o.$target.offset().top+80]
		}).bind("dialogclose", function () {
			button.stop();
			$(this).find('.digitize-image').unbind('click');
			that.destroy();
		});

		/*
		 Initialize button
		*/
		button = new Mapbender.Button({
			domElement: $sdi_download.get(0),
			over: o.src.replace(/_off/, "_over"),
			on: o.src.replace(/_off/, "_on"),
			off: o.src,
			name: o.id,
			go: that.activate,
			stop: that.deactivate
		});
		checkSession();
		
 	};
 	
 	var reinitializeDigitize = function() {
        inProgress = false;
        that.deactivate();
        that.activate();
        if (!$('#dataset-list').length) {
			$('#dataset-list-header').hide();
		}
        checkSession();
    };
       
    //initialize area_of_interest geojeon polygon
    this.area_of_interest = {}; 
    
    var finishDigitize = function() {        
        status = 'created-new';
        //use digitize pane to pull points
        var digit = o.$target.data('mb_digitize');
        // problem : shallow copying: https://code.tutsplus.com/the-best-way-to-deep-copy-an-object-in-javascript--cms-39655a
        var pts = digit._digitizePoints;
        //console.log(pts);
        var ptsWgs84 = JSON.parse(JSON.stringify(pts));
        console.log(ptsWgs84);       
        // TODO: render permanent ;-) - the problem is, that the points are converted into 4326 - the geometry is altered!        
        var tp = pts.closedPolygon ? geomType.polygon : (pts.closedLine ? geomType.line : geomType.point);        
        var tpWgs84 = ptsWgs84.closedPolygon ? geomType.polygon : (ptsWgs84.closedLine ? geomType.line : geomType.point);       
        var geom = new Geometry();
        var geomWgs84 = new Geometry();      
        // get epsg from client
        var map = $('#mapframe1').mapbender();
        var srcProj = new Proj4js.Proj(map.getSrs());
        var multi = new MultiGeometry(geomType.polygon);
        var wgs84 = new Proj4js.Proj('EPSG:4326');        
        //var wgs84 = new Proj4js.Proj('EPSG:25832');    
    	/**
    	transform via postgis
    	**/
        // current and target crs are switched in the php function !
        
        $.ajax({
            url: '../php/transformPoint.php',
            type: 'POST',
            async: false,
            dataType: 'json',
            data: {
                point_pos: JSON.stringify(ptsWgs84),
                targetProj: JSON.stringify(srcProj),
                currentProj: JSON.stringify(wgs84)
            },
            success: function(data) {
                $.each(data.coordinates, function(index, val) {
                    //if (!$.isPlainObject(ptsWgs84[index].pos)) {
                    //    ptsWgs84[index].pos = {
                    //        x: ptsWgs84[index].pos.x,
                    //        y: ptsWgs84[index].pos.y
                    //    };
                    //} 
                    // console.log(ptsWgs84[index].pos);
                    ptsWgs84[index].pos = {
                        x: val[0],
                        y: val[1]
                    };
                });
            }
        });           
                
        for (var i = 0; i < pts.length; ++i) {
        	// old way via proj4js
            // var pt = Proj4js.transform(srcProj, wgs84, pts[i].pos);
            geomWgs84.addPoint(ptsWgs84[i].pos);
        }
        geomWgs84.geomType = tp;
        multi.add(geomWgs84);
        // add some properties to geometry object
        multi.e = new Wfs_element();
        multi.e.setElement('title', 'Area of interest');
        
        // store the object to variable
        that.area_of_interest = JSON.parse(multi.toString());
        
        /*
        * Calculate area and length
        */
        // cloning the geoms to calculate the area or length via postgis
        var modifiedGeom = $.extend(true, {}, geomWgs84);
        var modifiedData = new MultiGeometry('polygon');
        modifiedGeom.addPoint(geomWgs84.list[0]); // add first point as last point
        modifiedData.add(modifiedGeom);      
        multiWkt = multi.toText();
        var area = 0;
        // calculate current area (polygon) or length(linestring)
        $.ajax({
            url: '../php/mod_CalculateAreaAndLength.php',
            type: 'POST',
            async: false,
            dataType: 'json',
            data: {
                geom_type: 'polygon',
                wkt_geom: modifiedData.toText()
            },
            success: function(data) {
                //TODO: use attributes area and length - not complex strings!
                multi.e.setElement('area', data[0]);
                multi.e.setElement('boundary-length', data[1]);
                area = parseFloat(data[0]);
            }
        });
        if (area > <?php echo (integer)$areaLimit * 1000000;?>)  {
        	alert('<?php echo _mb("The selected area is greater than ") . $areaLimit . " km2";?> : ' + Math.round(area / 1000000) + ' <?php echo _mb("km2 - select a smaller one");?> ;-)');
        	that.destroy();       	
        } else {
            // store the object to variable
            that.area_of_interest = JSON.parse(multi.toString());
            		
            var mapObj = getMapObjByName('mapframe1');
            //get list of spatial_dataset_identifier, if some exists
            sdi_list = [];
            for (let indexWms = 0; indexWms < mapObj.wms.length; ++indexWms) {
        		const currentWms = mapObj.wms[indexWms];
        		for (let indexLayer = 0; indexLayer < currentWms.objLayer.length; ++indexLayer) {
        			const currentLayer = currentWms.objLayer[indexLayer];
        			if (((typeof currentLayer.layer_identifier != "undefined") && Array.isArray(currentLayer.layer_identifier) && (currentLayer.layer_identifier.length > 0)) && currentLayer.gui_layer_visible == 1) {
        				//console.log(parseInt(currentLayer.layer_uid));
        				for (let indexIdentifier = 0; indexIdentifier < currentLayer.layer_identifier.length; ++indexIdentifier) {
        				    //only add identifier that are not empty!
        				    if (currentLayer.layer_identifier[indexIdentifier].identifier != "") {
        				    	sdi_list.push(currentLayer.layer_identifier[indexIdentifier].identifier);   
        				    }
        				}
        			}
        			//console.log(currentLayer);
        		}
    		}
    		//console.log(sdi_list);
            //get list of layers db ids
            layer_ids = [];
            // TODO - does deeper hierachies exists?
            for (let indexWms = 0; indexWms < mapObj.wms.length; ++indexWms) {
        		const currentWms = mapObj.wms[indexWms];
        		for (let indexLayer = 0; indexLayer < currentWms.objLayer.length; ++indexLayer) {
        			const currentLayer = currentWms.objLayer[indexLayer];
        			if (((currentLayer.layer_uid != '') || (typeof currentLayer.layer_uid != "undefined")) && currentLayer.gui_layer_visible == 1) {
        				//console.log(parseInt(currentLayer.layer_uid));
        				if (!isNaN(parseInt(currentLayer.layer_uid))) {
        					layer_ids.push(parseInt(currentLayer.layer_uid));
        				}
        			}
        		}
    		}
			const sdi_list_filtered = sdi_list.filter(char => char !== '['); 
    		// load identifier and send them with an ajax call to python wrapper script
    		//console.log('http://localhost/mapbender/php/mod_getDatasetIdentifierByLayer.php?layerIds=' + layer_ids.join(','));
            fetch('../php/mod_getDatasetIdentifierByLayer.php?layerIds=' + layer_ids.join(','))
                .then((response) => response.json())
                	.then((data) => {
                		that.requestDownloadOptions(data.concat(sdi_list_filtered), JSON.parse(multi.toString()));
                	});
                	
            inProgress = false;
            that.deactivate();
        }
    };
    
    var checkSession = function() {
    	console.log("checkSession");
        $.ajax({
            url: '../php/mod_showLoggedInUser.php?outputFormat=json',
            type: 'POST',
            async: true,
            dataType: 'json',
            data: {
            },
            success: function(data) {
            	if (data.result.logged_in) {
            		$('#user-info').html('<i><b>' + data.result.username + '</b></i>');
            	} else {
            	    //alert('problem');
            		//$('#user-info').html('<i><b>' + data.result.username + '</b></i>' + '<br><?php echo _mb('The anonymous user is not allowed to download data!');?>');
            		//$('#user-info').show();
            		$('#start-digitize').html('<?php echo _mb('The anonymous user is not allowed to download data!');?>');
            	}
            	userLoggedIn = data.result.logged_in;
            }
        });
    };
    
   
    
    var validateAndSendForm = function () {
    	//alert('test');
    	//console.log('validate and send form');
    	// https://stackoverflow.com/questions/169506/obtain-form-input-fields-using-jquery
    	var $inputs = $('#dataset-export-gpkg-form :input');
        // not sure if you wanted this, but I thought I'd add it.
        // get an associative array of just the values.
        download_configuration = {}
        dataset_configuration = {}
        dataset_configuration['datasets'] = [];
        var someChecked = false;
        $inputs.each(function() {
            if ($(this).is(':checked')) {
                someChecked = true;
                // explode name
                let entryArray = this.name.split('_check_');
                entry = {}
                entry['resourceidentifier'] = entryArray[0];
                entry['type'] = entryArray[1];
                dataset_configuration['datasets'].push(entry);
            	// console.log(this.name + " - is checked");
            }
        });
        if (someChecked) {
        	// add actual geojson to configuration
        	download_configuration['area_of_interest'] = that.area_of_interest;
        	download_configuration['dataset_configuration'] = dataset_configuration;
            //console.log(JSON.stringify(dataset_configuration));
            //ajax call
            $.ajaxSetup({async:true});
			var req = new Mapbender.Ajax.Request({
				method: "generateCache",
				url: "../php/mod_inspireGpkg_server.php",
				callback: generateCacheCallback,
				parameters:{
					configuration: download_configuration
				}
			});
			req.send();
			$.ajaxSetup({async:true});
			alert('<?php echo _mb('Package creation startet - you will get an email with a download link, if the process is finished!');?>');
			that.destroy();
        } else {
        	console.log("<?php echo _mb('No option selected - please select some to create geopackage!');?>");
        }
    };
    
    var generateCacheCallback = function (obj, success, message) {
    	//console.log(JSON.stringify(obj));
    	//$('#load-options-spinner').hide();
		if (!success) {
			console.log("problem when invoking generateCache");
			alert(message);
			//reload form
			that.destroy();
			return;
		} else {
			//
			console.log("generateCache invoked successfully");
			// result is a list of download options
			// iterate over list and show table for select raster or vector!
			//console.log(obj);
			alert(message);
			// show table with checkboxes for download underlying data
			return;
		}
	};
    
    var createDownloadForm = function (data) {
    	//exchange start digitize with some other html
    	$('#start-digitize').hide();
    	$('#dataset-list-header').show();
    	formContainer = $(document.createElement('form')).attr({'id':'dataset-export-gpkg-form'}).appendTo('#dataset-list');
    	tableContainer = $(document.createElement('table')).appendTo(formContainer);
    	tableContainer.attr({'id':'dataset-list-table'});
    	tableContainer.attr({'border':'1'});
		tableContainer.attr({'rules':'rows'});
		tableContainer.attr({'width':'300'});
		rowContainer = $(document.createElement('tr')).appendTo(tableContainer);
		columnContainer = $(document.createElement('th')).appendTo(rowContainer);
		datasetTitle = $(document.createElement('b')).appendTo(columnContainer);
    	datasetTitle.append("<?php echo _mb('Dataset');?>");
    	columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    	rasterTitle = $(document.createElement('b')).appendTo(columnContainer);
    	rasterTitle.append("<?php echo _mb('Vector');?>");
    	columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    	vectorTitle = $(document.createElement('b')).appendTo(columnContainer);
    	vectorTitle.append("<?php echo _mb('Raster');?>");
    	columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    	serviceTitle = $(document.createElement('b')).appendTo(columnContainer);
    	serviceTitle.append("<?php echo _mb('Service Info');?>");	
    	for (dataset_id in data) {
    		rowContainer = $(document.createElement('tr')).appendTo(tableContainer);
    		columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    		if (typeof data[dataset_id]['title'] != "undefined") {
        		datasetLink = $(document.createElement('a')).appendTo(columnContainer);
        		datasetLink.attr({'target':'_blank'});
        		//datasetLink.attr({'href':'https://www.geoportal.rlp.de/mapbender/php/mod_iso19139ToHtml.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D' + data[dataset_id]['fileidentifier']});
        		//datasetLink.attr({'href':'https://www.geoportal.rlp.de/mapbender/php/mod_iso19139ToHtml.php?url=' + encodeURIComponent(data[dataset_id]['csw'] + '?request=GetRecordById&service=CSW&version=2.0.2&ElementSetName=full&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd&Id=' + data[dataset_id]['fileidentifier'])});
        		datasetLink.attr({'href':'https://www.geoportal.rlp.de/mapbender/php/mod_exportIso19139.php?url=' + encodeURIComponent(data[dataset_id]['csw'] + '?request=GetRecordById&service=CSW&version=2.0.2&ElementSetName=full&OUTPUTSCHEMA=http://www.isotc211.org/2005/gmd&Id=' + data[dataset_id]['fileidentifier']) + '&resolveCoupledResources=true'});
        		datasetTitle = $(document.createElement('i')).appendTo(datasetLink);
        		datasetTitle.append(data[dataset_id]['title']);
        		if (data[dataset_id]['error_messages'].length > 0) {
        			datasetLink.attr({'title': data[dataset_id]['error_messages'].join(' - ')});
        			datasetLink.attr({'style':'color: red;'});
        		} else {
        			datasetLink.attr({'title': data[dataset_id]['spatial_dataset_identifier']});
        		}
    		} else {
    			datasetInfo = $(document.createElement('div')).appendTo(columnContainer);
        		datasetInfo.attr({'style':'color: red;'});
        		//datasetInfo.attr({'href':'https://www.geoportal.rlp.de/mapbender/php/mod_iso19139ToHtml.php?url=https%3A%2F%2Fwww.geoportal.rlp.de%2Fmapbender%2Fphp%2Fmod_dataISOMetadata.php%3FoutputFormat%3Diso19139%26id%3D' + data[dataset_id]['fileidentifier']});
        		datasetInfoTitle = $(document.createElement('i')).appendTo(datasetInfo);
        		datasetInfoTitle.attr({'title':'<?php echo _mb("Dataset not found in catalogues (Regional, DE, EU)! Last checked CSW: " );?>' + data[dataset_id]['csw']});
        		datasetInfoTitle.append(data[dataset_id]['spatial_dataset_identifier']);
    		}
    		serviceType = [];
    		serviceTypes = ['raster', 'vector'];
    		for (service in data[dataset_id]['services']) {
    			if (data[dataset_id]['services'][service]['possible_dataset_type'] != '') {
    				serviceType.push(data[dataset_id]['services'][service]['possible_dataset_type']);
    			}
    		}
    		columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    		vectorCheck = $(document.createElement('input')).appendTo(this.columnContainer);
			vectorCheck.attr({'type':'checkbox'});
			vectorCheck.attr({'name':data[dataset_id]['spatial_dataset_identifier'] + '_check_vector'});
    		if (serviceType.includes('vector')) {
				
    		} else {
    			vectorCheck.attr({'disabled':'disabled'});
    		}
    		
    		columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    		rasterCheck = $(document.createElement('input')).appendTo(this.columnContainer);
			rasterCheck.attr({'type':'checkbox'});
			rasterCheck.attr({'name':data[dataset_id]['spatial_dataset_identifier'] + '_check_raster'});
    		if (serviceType.includes('raster') && !serviceType.includes('vector')) {
				
    		} else {
    			rasterCheck.attr({'disabled':'disabled'});
    		}
    		columnContainer = $(document.createElement('th')).appendTo(rowContainer);
    		if (typeof data[dataset_id]['services'] !== "undefined"  &&  data[dataset_id]['services'].length > 0) {
        		serviceCheck = $(document.createElement('img')).appendTo(this.columnContainer);
    			serviceCheck.attr({'src':'../img/server_map-ilink.png'});
    			serviceCheck.attr({'width':'20'});
    			serviceCheck.attr({'height':'20'});
    			//serviceCheck.attr({'onClick':'test = window.open("data:text/json,"' + encodeURIComponent(JSON.stringify(data[dataset_id]['services'])) + ', "_blank");test.focus();'});
    			//serviceCheck.attr({'onClick':'alert("' + JSON.stringify(data[dataset_id]['services']) + '")'});
    			//serviceCheck.attr({'onClick':'alert("' + JSON.stringify(data[dataset_id]['services']) + '")'});
    			serviceCheck.attr({'title': JSON.stringify(data[dataset_id]['services'])});
			}
    	}
    	submitContainer = $(document.createElement('input')).appendTo(formContainer);
    	submitContainer.attr({'type':'button'});
    	submitContainer.attr({'id':'form-submit-button'});
    	submitContainer.attr({'value':'<?php echo _mb('Create geopackage');?>'});
    	$("#" + "form-submit-button").click(
    		(function () {
				return function(){
					validateAndSendForm(); 
				}
 		    })()
    	);
    };
    
    var checkDownloadOptionsCallback = function (obj, success, message) {
    	// alert(JSON.stringify(message));
    	$('#load-options-spinner').hide();
    	$('#dataset-list-header').show();
		if (!success) {
			console.log("problem when invoking checkOptions");
			alert(message);
			return;
		} else {
			//
			console.log("check options invoked successfully");
			// result is a list of download options
			// iterate over list and show table for select raster or vector!
		    createDownloadForm(obj);
		    //set css 
			$('#dataset-list').find('a').css({
      			"cursor": "default",
      			"color": "blue",
      			"text-decoration": "underline",
  				"text-decoration-color": 'blue'
    		});
			console.log(obj);
			that.show_area_of_interest();
			// show table with checkboxes for download underlying data
			
			return;
		}
	};
	
    this.requestDownloadOptions = function (sdiArray, area_of_interest) {
    	console.log(sdiArray);
    	
    	var download_configuration = {}
    	download_configuration.area_of_interest = area_of_interest;
    	download_configuration.dataset_configuration = {};
    	download_configuration.dataset_configuration.datasets = [];
    	var uniqueSdiArray = sdiArray.filter((value, index, array) => array.indexOf(value) === index);
    	for (sdi in uniqueSdiArray) {
    	    entry = {};
    	    //delete empty identifier from list
    	    if (uniqueSdiArray[sdi] != "") {
    	        entry['resourceidentifier'] = uniqueSdiArray[sdi];
    		    download_configuration.dataset_configuration.datasets.push(entry);
    		}
    	}
    	$('#load-options-spinner').show();
    	$.ajaxSetup({async:true});
		var req = new Mapbender.Ajax.Request({
			method: "checkOptions",
			url: "../php/mod_inspireGpkg_server.php",
			callback: checkDownloadOptionsCallback,
			parameters:{
				configuration: download_configuration
			}
		});
		req.send();
		$.ajaxSetup({async:true});
    };
    
    //initialize area_of_interest geojeon polygon
    //this.area_of_interest = {};
    
    this.show_area_of_interest = function() {
    	console.log(JSON.stringify(that.area_of_interest));
    }
    
 	this.activate = function () {
 		console.log('activate');
		downloadDialog.dialog("open");
		/*if (!$('#dataset-list').length) {
			$('#dataset-list-header').hide();
		}*/
		$('#load-options-spinner').hide();
		checkSession();
		if(!$('#start-digitize').length){
			downloadDialog.append($(startDigitizeHtml));
		}
		$('#start-digitize').show();
		$('#dataset-list-header').hide();
		$('#identity').hide();
		
		//if dataset-list already opened
		if ($('#dataset-list-table').length) {
			$('#start-digitize').hide();
			$('#dataset-list-header').show();
		}
		if (userLoggedIn == false) {
			return;
		}
        var mode = status.match(/(new|edit)-.+/);
        if (!mode) {
            return;
        };
        mode = mode[1];
        if (mode === 'new') {
            if (o.$target.size() > 0) {
                o.type = status.match(/new-(.+)/)[1];
                o.editedFeature = null;
                o.$target
                    .mb_digitize(o)
                    .mb_digitize('modeOff')
                    .mb_digitize('startDigitizing')
                    .unbind('mb_digitizelastpointadded')
                    .unbind('mb_digitizereinitialize')
                    .bind("mb_digitizelastpointadded", finishDigitize)
                    .bind("mb_digitizereinitialize", reinitializeDigitize);
            }
        } else {
        	alert('Editing of features is not possible in this module!')
        }
        if (!inProgress) {
            inProgress = true;
        }
	};

	this.destroy = function () {
	    if (o.$target.size() > 0) {
            o.$target.mb_digitize("destroy")
                .unbind("mb_digitizelastpointadded", finishDigitize)
                .unbind("mb_digitizereinitialize", reinitializeDigitize);
        }
        /*if (digitizeDialog.dialog("isOpen")) {
            digitizeDialog.dialog("close");
        }*/
        $('#start-digitize').hide();
		$('#dataset-export-gpkg-form').remove();
		if (downloadDialog.dialog("isOpen")) {
			downloadDialog.dialog("close");
		}
		
		// new status for next digitize
		status = 'new-polygon';
	};
	
	this.deactivate = function () {
		if (o.$target.size() > 0) {
			console.log('deactivate');
			console.log(o.$target);	
			o.$target.mb_digitize("deactivate");
		}
		$('#start-digitize').hide();
	};
	
	this.closeEditDialog = function() {
        editDialog.dialog('close');
    };
 	
 	create();
 	//checkSession();
 };

$sdi_download.mapbender(new SdiDownloadApi(options));
 
