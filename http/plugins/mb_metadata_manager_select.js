/**
 * Package: mb_metadata_manager_select
 *
 * Description:
 *
 * Files:
 *
 * SQL:
 * 
 * Help:
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

if (typeof options.hierarchyLevel === "undefined") {
	options.hierarchyLevel = 'dataset';	
}

var hierarchyLevel = options.hierarchyLevel;

var $metadataManagerSelect = $(this);

var MetadataManagerSelectApi = function (o) {
	var table = null;
        var mainDivContainer;
	var tableContainer;
        var dataTable;
	var confirmSearchabilityMessage;
	var confirmExportMessage;
	var confirmDeleteMessage;
	var that = this;
	var dataToEdit;
	
	var fnGetSelected = function (oTableLocal){
		var aReturn = [];
		var aTrs = oTableLocal.fnGetNodes();
		for ( var i=0 ; i<aTrs.length ; i++ ){
			if ( $(aTrs[i]).hasClass('row_selected') ){
				aReturn.push( aTrs[i] );
			}
		}
		return aReturn;
	};

	//function to delete metadata from cache
	this.deleteMetadata = function(id) {
		//var checkDelete = confirm("Do you really want to delete this entry?");
		var checkDelete = confirm(that.confirmDeleteMessage);
		if(checkDelete) {
			var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_metadata_manager_server.php",
				method: "deleteMetadata",
				parameters: {
					"id": id
				},
				callback: function (obj, result, message) {
			
					$("<div></div>").text(message).dialog({
						modal: true
					});
					that.initTable();
				}
			});
			req.send();
		}
	};

	//function to toggle the searchability of metadata in mapbender catalogue
	this.toggleSearchability = function(id) {
		//var checkToggle = confirm("Do you really want to change the searchability?");
		var checkToggle = confirm(that.confirmSearchabilityMessage);
		if(checkToggle) {
			var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_metadata_manager_server.php",
				method: "toggleSearchability",
				parameters: {
					"id": id
				},
				callback: function (obj, result, message) {
			
					$("<div></div>").text(message).dialog({
						modal: true
					});
					that.initTable();
					//as from datatables 1.10 it will be easier - new function: .ajax.reload()
				}
			});
			req.send();
		}
	};

	//function to toggle the export of metadata from mapbender catalogue to external catalogues
	this.toggleExport = function(id) {
		//var checkToggle = confirm("Do you really want to change the export handling for this metadata?");
		var checkToggle = confirm(that.confirmExportMessage);
		if(checkToggle) {
			var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_metadata_manager_server.php",
				method: "toggleExport",
				parameters: {
					"id": id
				},
				callback: function (obj, result, message) {
					$("<div></div>").text(message).dialog({
						modal: true
					});
					that.initTable();
				}
			});
			req.send();
		}
	};

	/**
 	* @see http://stackoverflow.com/q/7616461/940217
	 * @return {number}
 	*/
	String.prototype.hashCode = function(){
  	  if (Array.prototype.reduce){
     	   return this.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);              
   	 } 
   	 var hash = 0;
   	 if (this.length === 0) return hash;
   	 for (var i = 0; i < this.length; i++) {
   	     var character  = this.charCodeAt(i);
      	  hash  = ((hash<<5)-hash)+character;
      	  hash = hash & hash; // Convert to 32bit integer
   	 }
   	 return hash;
	}

	this.generateUUID = function() {
    		var d = new Date().getTime();
    		if(window.performance && typeof window.performance.now === "function"){
       		 d += performance.now(); //use high-precision timer if available
    		}
    		var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
       		 var r = (d + Math.random()*16)%16 | 0;
        		d = Math.floor(d/16);
        		return (c=='x' ? r : (r&0x3|0x8)).toString(16);
    		});
    		return uuid;
	};

	this.initTable = function() {
		//initialize table object with jquery from ajax call to serverside script with translated headers!
		mainDivContainer = $("#mb_metadata_manager_select");
		//initialize size of table
		
		//get attribute from object?
		mainDivContainer.empty();
		tableContainer = $(document.createElement('table'));
		tableHeaderContainer = $(document.createElement('thead'));
		tableHeaderRowContainer = $(document.createElement('tr'));
		tableContainer.attr({'id':'metadataTable'});
		//add button to add new entry to metadata table
		if (hierarchyLevel == 'application') {
		    $("<img class='metadataEntry clickable' title='new' src='../img/gnome/newGui.png' onclick='initMetadata(null, null, \"application\", true);return false;'/>").appendTo($("#mb_metadata_manager_select"));
		} else {
		    $("<img class='metadataEntry clickable' title='new' src='../img/gnome/newGui.png' onclick='initMetadataAddon(null, null, \"metadata\", true);return false;'/>").appendTo($("#mb_metadata_manager_select"));
		}
		mainDivContainer.append(tableContainer);
		//alert("created");
		//call translations
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_manager_server.php",
			method: "getHeader",
			async: false,
			parameters: {
				"lang": "de",
				"hierarchyLevel": hierarchyLevel
			},
			callback: function (obj, result, message) {
				if (result) {
					for (var i in obj.header) { 
						content = '<td>' + obj.header[i] + '</td>';
						tableHeaderRowContainer.append(content);
					}
					tableContainer.append(tableHeaderContainer.append(tableHeaderRowContainer));
					//initialize table
					//define translations for confirm messages
					$.each(obj.translation, function(k, v) {
						that[k] = v;
					});
				} else {
					$("<div></div>").text(message).dialog({
						modal: true
					});
				}
				
			}
		});
		req.send();
		//fill table with incremental serverside selects
		var ReqId = new String(this.generateUUID()).hashCode();
    		dataTable = $('#metadataTable').dataTable( {
                        "bProcessing": true,
        		"bServerSide": true,
			"bOrdering": false,
			"columnDefs": [ {
				"targets": [0,1,2,3],
				"orderable": false
			} ],
			"bSort" : false,
			"fnServerParams": function ( aoData ) {
				var params = {}; //JSON.stringify(aoData);
				for(var i in aoData) {
					if (aoData[i].name == 'sEcho') {
						params[aoData[i].name] =  String(aoData[i].value);
					} else {
						params[aoData[i].name] =  aoData[i].value;
					}
				}
				params["hierarchyLevel"] = hierarchyLevel;				
      				aoData.push( { "name": "method", "value": "loadTableIncremental"} );
				aoData.push( { "name": "id", "value": ReqId} );
				aoData.push( { "name": "hierarchyLevel", "value": hierarchyLevel} );
				//not nice but usefull for mapbenders ajax handler
				aoData.push( { "name": "params", "value": JSON.stringify(params)} );
    			},
        		"sAjaxSource": "../plugins/mb_metadata_manager_server.php",
			"sAjaxDataProp": "result.data.aaData",
			"fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
      				oSettings.jqXHR = $.ajax( {
        				"dataType": 'json',
        				"type": "POST",
        				"url": sSource,
        				"data": aoData,
					"success": fnCallback
      				} );
			},
			"fnCreatedRow": function( nRow, aData, iDataIndex ) {
      				// define a metadataId to row
				var metadataId = aData[0];
				$(nRow).data("metadataId", metadataId);
    			},
			"fnDrawCallback": function( oSettings ) {
				// add functionality to delete button
				$(".deleteImg").click(function (e) {
					var id = $(this).parents("tr").data("metadataId");
					that.deleteMetadata(id);
					return false;
				});
				// add functionality to toogle export button
				$(".toggleExport").click(function (e) {
					var id = $(this).parents("tr").data("metadataId");
					that.toggleExport(id);
					return false;
				});
				// add functionality to toogle search button
				$(".toggleSearchability").click(function (e) {
					var id = $(this).parents("tr").data("metadataId");
					that.toggleSearchability(id);
					return false;
				});


				// add option to open metadata windows in a modal dialog
				$(".modalDialog").click(function (e) {
					var iframe = $('<iframe width="100%" height="100%" frameborder="0" scrolling="yes" style="min-width: 95%;height:100%;"></iframe>');
					iframe.attr('src', String($(this).attr('url')));
    					var dialog = $("<div></div>").append(iframe).dialog({
        					autoOpen: true,
        					modal: true,
        					resizable: false,
        					width: 600,
        					height: 400,
        					close: function () {
            						iframe.attr("src", "");
        					}
    					});
				});
				// change style to link optic
				$(".modalDialog").css("text-decoration", "underline");
				$(".modalDialog").css("text-decoration-color", "blue");
				$(".modalDialog").css("color", "blue");
				$(".modalDialog").css("cursor", "pointer");
    			},
			"bJQueryUI": true
    		} );
	};

	this.events = {
		selected: new Mapbender.Event()
	};
};

$metadataManagerSelect.mapbender(new MetadataManagerSelectApi(options));

$metadataManagerSelect.mapbender("initTable");
