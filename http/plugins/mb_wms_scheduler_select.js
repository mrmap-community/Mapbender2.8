/**
 * Package: mb_wms_scheduler_select
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

var $wmsSchedulerSelect = $(this);
$wmsSchedulerSelect.prepend("<img src='../img/indicator_wheel.gif'>");

var WmsSchedulerSelectApi = function (o) {
	var table = null;
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
	
	//function to initialize form to edit scheduling parameters for update a specific wms
	this.initEditForm = function(schedulerId) {
			//get infos to edit from db via new ajax request
			var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_wms_scheduler_server.php",
				method: "getWmsSchedulerEdit",
				parameters: {
					"id": schedulerId
				},
				callback: function (obj, result, message) {
					if (!result) {
						return;
					}
					
					//build editor
					var editFormHtml = "<div id='scheduleEditor'>";
					editFormHtml += "<form id='edit_scheduler_form'>";
					//wms information
					editFormHtml += "<fieldset><legend>WMS Title:</legend>" + obj.wms_id+" : "+obj.wms_title + "</fieldset>";
					editFormHtml += "<fieldset><legend>Update interval:</legend>";
					editFormHtml += "<select id='scheduler_interval'>";
					editFormHtml += "<option value='1 day'>1 day</option>";
					editFormHtml += "<option value='7 days'>1 week</option>";
					editFormHtml += "<option value='1 mon'>1 month</option>";
					editFormHtml += "<option value='1 year'>1 year</option>";
					editFormHtml += "</select>";
					editFormHtml += "</fieldset>";
					editFormHtml += "<fieldset><legend>Notify per Mail:</legend>";
					editFormHtml += "<input type='checkbox' id='scheduler_mail'/>";
					editFormHtml += "</fieldset>";
					editFormHtml += "<fieldset><legend>Publish via RSS/Twitter:</legend>";
					editFormHtml += "<input type='checkbox' id='scheduler_publish'/>";
					editFormHtml += "</fieldset>";
					editFormHtml += "<fieldset><legend>Make new layer searchable:</legend>";
					editFormHtml += "<input type='checkbox' id='scheduler_searchable'/>";
					editFormHtml += "</fieldset>";
					editFormHtml += "<fieldset><legend>Overwrite edited metadata:</legend>";
					editFormHtml += "<input type='checkbox' id='scheduler_overwrite'/>";
					editFormHtml += "</fieldset>";
					editFormHtml += "<fieldset><legend>Overwrite edited layer categories:</legend>";
					editFormHtml += "<input type='checkbox' id='scheduler_overwrite_categories'/>";
					editFormHtml += "</fieldset>";
					editFormHtml += "</form>";
					editFormHtml += "</div>";

					$editEntryDialog = $(editFormHtml).dialog({
						title : "Scheduler editor", 
						autoOpen : false,  
						draggable : false,
						modal : true,
						width : 600,
						//position : [600, 75],
						buttons: {
							"close": function() {
								$('#scheduleEditor').remove();
							},
							"save": function() {
								//read infos from form
								//var editFormData = $('#edit_scheduler_form').serialize();
								if ($('#scheduler_publish').attr("checked") == true) {
									scheduler_publish = 1;
								} else {
									scheduler_publish = 0;
								}
								if ($('#scheduler_searchable').attr("checked") == true) {
									scheduler_searchable = 1;
								} else {
									scheduler_searchable = 0;
								}
								if ($('#scheduler_mail').attr("checked") == true) {
									scheduler_mail = 1;
								} else {
									scheduler_mail = 0;
								}
								if ($('#scheduler_overwrite').attr("checked") == true) {
									scheduler_overwrite = 1;
								} else {
									scheduler_overwrite = 0;
								}
								if ($('#scheduler_overwrite_categories').attr("checked") == true) {
									scheduler_overwrite_categories = 1;
								} else {
									scheduler_overwrite_categories = 0;
								}
								data = {
									scheduler_interval: $('#scheduler_interval').val(),
									scheduler_publish: scheduler_publish,
									wms_id: obj.wms_id,
									scheduler_searchable: scheduler_searchable,
									scheduler_mail:  scheduler_mail,
									scheduler_overwrite: scheduler_overwrite,
									scheduler_overwrite_categories: scheduler_overwrite_categories
								};
								//push infos to server
								that.updateWmsSchedule(schedulerId, data);
								//kill form
								$editEntryDialog.dialog("close");
								$('#scheduleEditor').remove();
								
							}
						},
						close: function() {
							$('#scheduleEditor').remove();
						}
					});
					$editEntryDialog.dialog("open");
					
					$('#scheduler_interval option[value="'+obj.scheduler_interval+'"]').attr({'selected':'selected'});
					if (obj.scheduler_mail == 1) {
						$("#scheduler_mail").attr({'checked':'checked'});
					}
					
					if (obj.scheduler_publish == 1) {
						$("#scheduler_publish").attr({'checked':'checked'});
					}
	
					if (obj.scheduler_searchable == 1) {
						$("#scheduler_searchable").attr({'checked':'checked'});
					}

					if (obj.scheduler_overwrite == 1) {
						$("#scheduler_overwrite").attr({'checked':'checked'});
					}

					if (obj.scheduler_overwrite_categories == 1) {
						$("#scheduler_overwrite_categories").attr({'checked':'checked'});
					}
				}
			});
			req.send();			
	}

	//function init form to add new scheduling for update own wms
	this.initAddForm = function() {
		var addFormHtml = "<div id='scheduleEditor'>";
		addFormHtml += "<form id='edit_scheduler_form'>";
		addFormHtml += "<fieldset><legend>WMS</legend><select id='scheduler_wms'></select></fieldset>";
		addFormHtml += "<fieldset><legend>Update interval:</legend>";
		addFormHtml += "<select id='scheduler_interval'>";
		addFormHtml += "<option value='1 day'>1 day</option>";
		addFormHtml += "<option value='7 days'>1 week</option>";
		addFormHtml += "<option value='1 mon'>1 month</option>";
		addFormHtml += "<option value='1 year'>1 year</option>";
		addFormHtml += "</select>";
		addFormHtml += "</fieldset>";
		addFormHtml += "<fieldset><legend>Notify per Mail:</legend>";
		addFormHtml += "<input type='checkbox' id='scheduler_mail'/>";
		addFormHtml += "</fieldset>";
		addFormHtml += "<fieldset><legend>Publish via RSS/Twitter:</legend>";
		addFormHtml += "<input type='checkbox' id='scheduler_publish'/>";
		addFormHtml += "</fieldset>";
		addFormHtml += "<fieldset><legend>Make new layer searchable:</legend>";
		addFormHtml += "<input type='checkbox' id='scheduler_searchable'/>";
		addFormHtml += "</fieldset>";
		addFormHtml += "<fieldset><legend>Overwrite edited metadata:</legend>";
		addFormHtml += "<input type='checkbox' id='scheduler_overwrite'/>";
		addFormHtml += "</fieldset>";
		addFormHtml += "<fieldset><legend>Overwrite edited layer categories:</legend>";
		addFormHtml += "<input type='checkbox' id='scheduler_overwrite_categories'/>";
		addFormHtml += "</fieldset>";
		addFormHtml += "</form>";
		addFormHtml += "</div>";

		$addNewEntryDialog = $(addFormHtml).dialog({
			title : "Scheduler editor", 
			autoOpen : false,  
			draggable : false,
			modal : true,
			width : 600,
			//position : [600, 75],
			buttons: {
				"close": function() {
					$('#scheduleEditor').remove();
				},
				"save": function() {
					//read infos from form
					//var editFormData = $('#edit_scheduler_form').serialize();
					if ($('#scheduler_publish').attr("checked") == true) {
						scheduler_publish = 1;
					} else {
						scheduler_publish = 0;
					}
					if ($('#scheduler_searchable').attr("checked") == true) {
						scheduler_searchable = 1;
					} else {
						scheduler_searchable = 0;
					}
					if ($('#scheduler_mail').attr("checked") == true) {
						scheduler_mail = 1;
					} else {
						scheduler_mail = 0;
					}
					if ($('#scheduler_overwrite').attr("checked") == true) {
						scheduler_overwrite = 1;
					} else {
						scheduler_overwrite = 0;
					}
					if ($('#scheduler_overwrite_categories').attr("checked") == true) {
						scheduler_overwrite_categories = 1;
					} else {
						scheduler_overwrite_categories = 0;
					}

					data = {
						scheduler_interval: $('#scheduler_interval').val(),
						scheduler_publish: scheduler_publish,
						wms_id: $("#scheduler_wms").val(),
						scheduler_searchable: scheduler_searchable,
						scheduler_mail:  scheduler_mail,
						scheduler_overwrite: scheduler_overwrite,
						scheduler_overwrite_categories: scheduler_overwrite_categories
					};
					//push infos to server
					that.insertWmsSchedule(data);
					//kill form
					$addNewEntryDialog.dialog("close");
					$('#scheduleEditor').remove();
					
				}
			},
			close: function() {
				$('#scheduleEditor').remove();
			},
			open: function() {
				var userWms = that.getUserWms();
			}
		});
		$addNewEntryDialog.dialog("open");
	};
	
	this.getUserWms = function () {
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_wms_scheduler_server.php",
			method: "getUserWms",
			parameters: {},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$("#scheduler_wms").empty();
				var emptyOption = '<option value="">---</option>';
				$("#scheduler_wms").append(emptyOption);
				for ( var i=0 ; i<obj.length ; i++ ) {
					var optionVal = obj[i].wmsId;
	                var optionName = obj[i].wmsId + " : " + obj[i].wmsTitle;
	                var optionHtml = "<option value='" + optionVal + "'>" + optionName + "</option>";
	                $("#scheduler_wms").append(optionHtml);
				}
			}
		});
		req.send();
	};

	//function to delete scheduling of wms update
	this.deleteWmsSchedule = function(id) {
		var checkDelete = confirm("Do you really want to delete this entry?");
		if(checkDelete) {
			var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_wms_scheduler_server.php",
				method: "deleteWmsSchedule",
				parameters: {
					"id": id
				},
				callback: function (obj, result, message) {
					/*if (!result) {
						return;
					}*/
					
					$("<div></div>").text(message).dialog({
						modal: true
					});
					
					that.reloadTable();
				}
			});
			req.send();
		}
	};
	//function to add scheduling of wms update
	this.addWmsSchedule = function() {
		that.initAddForm();
	};

	//function to update scheduling parameters of wms update
	this.updateWmsSchedule = function(schedulerId, data) {
		var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_wms_scheduler_server.php",
				method: "updateWmsSchedule",
				parameters: {
					"schedulerId": schedulerId,
					"data": data
				},
				callback: function (obj, result, message) {
					/*if (!result) {
						return;
					}*/
					
					$("<div></div>").text(message).dialog({
						modal: true
					});
					
					that.reloadTable();
				}
		});
		req.send();
	};
	
	//function to insert scheduling parameters of wms update
	this.insertWmsSchedule = function(data) {
		var req = new Mapbender.Ajax.Request({
				url: "../plugins/mb_wms_scheduler_server.php",
				method: "insertWmsSchedule",
				parameters: {
					"data": data
				},
				callback: function (obj, result, message) {
					/*if (!result) {
						return;
					}*/
					
					$("<div></div>").text(message).dialog({
						modal: true
					});
					
					that.reloadTable();
				}
		});
		req.send();
	};
	
	this.reloadTable = function () {
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_wms_scheduler_server.php",
			method: "getWmsScheduler",
			parameters: {
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				//delete old entries
				table.fnClearTable();

				var aoColumns = [];
				for (var i in obj.header) {
					if (obj.header[i] === "Scheduler ID") {
						continue;
					}
					aoColumns.push({"sTitle": obj.header[i]});
				}
				
				// add rows
				for (var j in obj.data) {
					var data = obj.data[j];
					var schedulerId = data[0];
					data.shift();
					var index = table.fnAddData(data);
					var rowNode = table.fnGetNodes(index[0]);
					$(rowNode).data("schedulerId", schedulerId);
				}
				
				
				
				// add functionality to delete button
				$(".deleteImg").click(function (e) {
					var id = $(this).parents("tr").data("schedulerId");
					that.deleteWmsSchedule(id);
					return false;
				});
			}
		});
		req.send();
	};


	this.getData = function () {
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_wms_scheduler_server.php",
			method: "getWmsScheduler",
			parameters: {
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$wmsSchedulerSelect.find("img").remove();
				
				var aoColumns = [];
				for (var i in obj.header) {
					if (obj.header[i] === "Scheduler ID") {
						continue;
					}
					aoColumns.push({"sTitle": obj.header[i]});
				}

				// initialize datatables
				table = $wmsSchedulerSelect.find("table").dataTable({
					"aoColumns": aoColumns,
					"bJQueryUI": true
				});
				
				//add button to add new entries to dataTables
				$("#mb_wms_scheduler_select").append("<input type='button' id='newScheduleEntry' value='New entry'>");
				$("#newScheduleEntry").click(that.addWmsSchedule);
				
				// add rows
				for (var j in obj.data) {
					var data = obj.data[j];
					var schedulerId = data[0];
					data.shift();
					var index = table.fnAddData(data);
					var rowNode = table.fnGetNodes(index[0]);
					$(rowNode).data("schedulerId", schedulerId);
				}
				
				// make rows selectable
				$wmsSchedulerSelect.find("tbody").click(function (e) {

					$(table.fnSettings().aoData).each(function (){
						$(this.nTr).removeClass('row_selected');
					});

					$(e.target.parentNode).addClass('row_selected');

					var selectedRow = fnGetSelected(table);
					id = $(selectedRow).data("schedulerId");
					//start editor
					that.initEditForm(id);	
				});
				
				// add functionality to delete button
				$(".deleteImg").click(function (e) {
					var id = $(this).parents("tr").data("schedulerId");
					that.deleteWmsSchedule(id);
					return false;
				});
			}
		});
		req.send();
	};
	
	this.events = {
		selected: new Mapbender.Event()
	};
};

$wmsSchedulerSelect.mapbender(new WmsSchedulerSelectApi(options));

$wmsSchedulerSelect.mapbender("getData");
