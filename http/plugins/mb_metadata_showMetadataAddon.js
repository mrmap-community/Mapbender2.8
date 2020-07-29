var $showMetadataAddonDiv = $(this);
var $metadataAddonForm = $("<form></form>").appendTo($showMetadataAddonDiv);
var $metadataAddonPopup = $("<div></div>");
var $metadataUploadPopup = $("<div></div>");

var ShowMetadataAddonApi = function() {
	var that = this;
	var resourceId;
	var resourceType;
	var metadataId;
   
	//Function, which pulls the metadata out off the mapbender registry and give a possibility to edit the record or link
	this.valid = function () {
		if (validator && validator.numberOfInvalids() > 0) {
			$metadataAddonForm.valid();
			return false;
		}
		return true;
	};

	this.getInitialResourceMetadata = function(metadataId, resourceId, resourceType){
		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getInitialResourceMetadata",
			parameters: {
				"resourceId": resourceId,
				"metadataId": metadataId,
				"resourceType": resourceType,
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$metadataAddonForm.easyform("reset");
				$metadataAddonForm.easyform("fill", obj);
				$('#license_source_md').css("display","none");
				if (obj.md_termsofuse !== "0") {
					//alert(JSON.stringify(obj));
					that.fillLicence(obj.md_termsofuse);
				}
			}
		});
		req.send();	
	}

	getOwnedMetadata = function (){
		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getOwnedMetadata",
			parameters: {
				//"layerId": Mapbender.modules.mb_md_layer.getLayerId()
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				//fill options for of internal linkages
				$("#internal_relation").empty();
				var emptyOption = '<option value="">---</option>';
				$("#internal_relation").append(emptyOption);
				for ( var i=0 ; i<obj.length ; i++ ) {
					var optionVal = obj[i].metadataId;
	                		var optionName = obj[i].metadataId + " : " + obj[i].metadataTitle;
	                		var optionHtml = "<option value='" + optionVal + "'>" + optionName + "</option>";
	                		$("#internal_relation").append(optionHtml);
				}				

			}
		});
		req.send();	
	}		

	this.getAddedMetadata = function(metadataId){
		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getMetadataAddon",
			parameters: {
				"metadataId": metadataId
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$metadataAddonForm.easyform("reset");
				$metadataAddonForm.easyform("fill", obj);
				
				//enable link element to edit link!
				$("#link").removeAttr("disabled");

				switch (obj.origin) {
					case "external":
						$("#metadataUrlEditor").css("display","block");
						$("#link_editor").css("display","block");
    						break;
  					case "metador":
						$("#metadataUrlEditor").css("display","block");
						$("#simple_metadata_editor").css("display","block");
    						break;
  					case "capabilities":
						$("#metadataUrlEditor").css("display","block");
    						$("#simple_metadata_editor").css("display","block");
    						break;
					//new - uploaded metadata maybe edited - but some information will be lost :-(
					case "upload":
						$("#metadataUrlEditor").css("display","block");
						$("#simple_metadata_editor").css("display","block");
    						break;		
					default:
    						break;
				}
				//select the right list entries:
				$(".format_selectbox").val(obj.format); 				
				$(".charset_selectbox").val(obj.inspire_charset);
				$(".ref_system_selectbox").val(obj.ref_system);
				$(".cyclic_selectbox").val(obj.update_frequency);
				urlstring = obj.overview_url+"&time="+ new Date().getTime();
				$("#extent_preview").attr('src',urlstring);
				$(".radioRes").filter('[value='+obj.spatial_res_type+']').attr('checked', true);
				if (obj.has_polygon) {
					$("#delete_existing_polygon").css("display","block"); 
				} else {
					$("#delete_existing_polygon").css("display","none"); 
				}
				$(".termsofuse_selectbox").val(obj.md_termsofuse);
				//show symbol and link for predefined termsofuse
				if (obj.md_termsofuse !== "0") {
					//alert(JSON.stringify(obj));
					that.fillLicence(obj.md_termsofuse);
				}
				if ($("#check_overwrite_responsible_party").is(":checked")) {
					$('#label_responsible_party_name').css('display', 'block');
					$('#responsible_party_name').css('display', 'block');
					$('#label_responsible_party_email').css('display', 'block');
					$('#responsible_party_email').css('display', 'block');
				}

			}
		});
		req.send();	
	}	
    /*
     * 
     */
	this.selectPredefinedAccessConstraints = function(selectedIndex) {
		//set value of textfield accessconstraints_md to selected value of inspire constraints dropdown list
		//alert("mb_metadata_showMetadataAddon.js - selectPredefinedAccessConstraints invoked: "+selectedIndex);
		//alert("Access constraints");
		//get selected index and write it to textfield
		
		if (selectedIndex != '0') {
			if ($("#accessconstraints").length > 0) {
				$("#accessconstraints").val(selectedIndex);
			}
			$("#accessconstraints_md").val(selectedIndex);
		} else {
			if ($("#accessconstraints").length > 0) {
				$("#accessconstraints").val("NONE");
			}
			$("#accessconstraints_md").val("NONE");
		}
	}
	//Show more information about the licences of the metadata 
	this.fillLicence = function(obj) {
		// get licence information from server per termsofuse_id
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getLicenceInformation",
			parameters: {
				"id": obj
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				if (obj.termsofuse_id) {
					//alert(JSON.stringify(obj));
					$('#licence_symbol_md').attr('src', obj.symbollink);
					$('#licence_descriptionlink_md').text(obj.description);
					$('#licence_descriptionlink_md').attr('href', obj.descriptionlink);
					if (obj.isopen == 1) {
						$('#open_symbol_md').attr('src', '../img/od_80x15_blue.png');
						$('#open_symbol_md').css("display","block");
					} else {
						$('#open_symbol_md').attr('src', '');
						$('#open_symbol_md').css("display","none");
					}
					if (obj.source_required == 1) {
						$('#license_source_md').css("display","block");
					} else {
						$('#license_source_md').css("display","none");
					}
					$('#license_info_md').css('display', 'block');
					if (obj.termsofuse_id == '0') {
						$('#license_info_md').css('display', 'none');
					}
				} else {
					$('#licence_symbol_md').attr('src', '');
					$('#licence_descriptionlink_md').attr('href', '');
					$('#licence_descriptionlink_md').text('');
					$('#open_symbol_md').attr('src', '');
					/*$('#license_info_md').css('display', 'none');
					$('#licence_symbol_md').css('display', 'none');
					$('#open_symbol_md').css('display', 'none');
					$('#licence_descriptionlink_md').css('display', 'none');*/
				}
			}
		});
		req.send();
	}	

	this.insertAddedMetadata = function(resourceId, resourceType, data){
		// push metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "insertMetadataAddon",
			parameters: {
				"resourceId": resourceId,
				"resourceType": resourceType,
				"data": data
			},
			callback: function (obj, result, message) {
				$("<div></div>").text(message).dialog({
					modal: true
				});
				if (resourceType !== 'metadata') {
					//update resource form to show edited data
					that.fillResourceForm(resourceId, resourceType);
				} else {
					Mapbender.modules.mb_metadata_manager_select.initTable();
				}
			}
		});
		req.send();	
	}

	this.updateAddedMetadata = function(metadataId, resourceId, resourceType, data){
		// push metadata to server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "updateMetadataAddon",
			parameters: {
				"metadataId": metadataId,
				"resourceId": resourceId,
				"resourceType": resourceType,
				"data": data
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$("<div></div>").text(message).dialog({
					modal: true
				});
				if (resourceType !== 'metadata') {
					//update resource form to show edited data
					that.fillResourceForm(resourceId, resourceType);
				} else {
					Mapbender.modules.mb_metadata_manager_select.initTable();
				}
			}
		});
		req.send();	
	}	

	//function to fill resource form with changed metadata entries TODO: this function is defined in mb_metadata_layer[featuretype].js before but it cannot be called - maybe s.th. have to be changed
	this.fillResourceForm = function (resourceId, resourceType) {
		//get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getResourceMetadata",
			parameters: {
				"resourceId": resourceId,
				"resourceType": resourceType
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				//delete metadataURL entries
				$('.metadataEntry').remove();
				//fill MetadataURLs into metadata_selectbox_id - notice: the name of the module is fix!
				switch (resourceType) {
					case "layer":
						Mapbender.modules.mb_md_layer.fillMetadataURLs(obj);
						//reload layer tree for showing symbols
						Mapbender.modules.mb_md_layer_tree.init(Mapbender.modules.mb_md_layer.getWmsId());
					break;
					case "featuretype":
						//TODO
						//fill MetadataURLs into metadata_selectbox_id - notice: the name of the module is fix!
						Mapbender.modules.mb_md_featuretype.fillMetadataURLs(obj);
						//reload featuretype tree for showing symbols
						Mapbender.modules.mb_md_featuretype_tree.init(Mapbender.modules.mb_md_featuretype.getWfsId());
					break;
				}
			}
		});
		req.send();		
	};
	
	deleteAddedMetadata = function(metadataId, resourceId, resourceType){
		// push metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "deleteMetadataAddon",
			parameters: {
				"metadataId": metadataId,
				"resourceId": resourceId,
				"resourceType": resourceType
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}	
				//delete metadataURL entries
				$('.metadataEntry').remove();
				//fill MetadataURLs into metadata_selectbox_id
				//update resource form to show edited data
				that.fillResourceForm(resourceId, resourceType);
				$("<div></div>").text(message).dialog({
					modal: true
				});
			}
		});
		req.send();	
	}		
	
	deleteInternalMetadataLinkage = function(metadataId, resourceId, resourceType){
		// push metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "deleteInternalMetadataLinkage",
			parameters: {
				"metadataId": metadataId,
				"resourceType": resourceType,
				"resourceId": resourceId
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}	
				//delete metadataURL entries
				$('.metadataEntry').remove();
				//fill MetadataURLs into metadata_selectbox_id
				//update resource form to show edited data
				that.fillResourceForm(resourceId, resourceType);
				$("<div></div>").text(message).dialog({
					modal: true
				});
			}
		});
		req.send();	
	}			

	this.showForm = function (metadataId, resourceId, resourceType, isNew) {
		$metadataAddonPopup.append($metadataAddonForm);
		$metadataAddonPopup.dialog({
			title : "Metadata Editor", 
			autoOpen : false, 
			draggable : true,
			modal : true,
			width : 700,
			position : [600, 75],
			buttons: {
				"close": function() {
					$(this).dialog('close');
				},
				"save": function() {
					//get data from form
					//supress validation for the link only way
					//example $("#myform").validate().element( "#myselect" );
					//$("#myform").validate({
					// ignore: ".ignore"
					//})
					if ($("#addonChooser").css("display") == "block") {
						//don't allow saving but do something else
						return;
						
					}
					if ($("#simple_metadata_editor").css("display") == "block") {
						//validate form before send it!
						if ($metadataAddonForm.valid() != true) {
							alert("Form not valid - please check your input!"); //TODO use translations and make a php file from this
							return;
						}
					}
					var formData = $metadataAddonForm.easyform("serialize");
					if (!isNew) {
						that.updateAddedMetadata(metadataId, resourceId, resourceType, formData);
					} else {
						that.insertAddedMetadata(resourceId, resourceType, formData);	
					}
					//$('#mb_md_layer_tree').refresh;
					$(this).dialog('close');
				}
			},
			close: function() {
				//what to do when the dialog is closed
			}
		});
		$metadataAddonPopup.dialog("open");
	};
	
	initUploadForm = function (resourceId, resourceType) {
		$metadataAddonPopup.dialog("close");
		//TODO: problem - when invoking it first - mb_metadata_addon.php - there is no information about the resourceType and its id. Some error occurs - but hidden ;-)
		initXmlImport(resourceId, resourceType);
		that.fillResourceForm(resourceId, resourceType);
	}

 	enableResetButtonMd = function() {
        	$("#resetIsoTopicCatsMd").click(function() {
            		$("#md_md_topic_category_id option").removeAttr("selected");
        	});
        	$("#resetCustomCatsMd").click(function() {
            		$("#md_custom_category_id option").removeAttr("selected");
        	});
       	 	$("#resetInspireCatsMd").click(function() {
            		$("#md_inspire_category_id option").removeAttr("selected");
        	});
   	}

	initUploadGmlForm = function (metadataId) {
		//$metadataAddonPopup.dialog("close");
		//don't show possibility if metadata was not created before - only afterwards!
		initGmlImport(metadataId);
	}

	deleteGmlPolygon = function (metadataId) {
		//invoke server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "deleteGmlPolygon",
			parameters: {
				"metadataId": metadataId
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}	
				//hide delete image
				$("#delete_existing_polygon").css("display","none"); 
				//update preview
				urlstring = $("#extent_preview").attr('src')+"&time="+ new Date().getTime();
				$("#extent_preview").attr('src',urlstring);
				$("<div></div>").text(message).dialog({
					modal: true
				});
			}
		});
		req.send();	
	}			

	this.init = function (metadataId, resourceId, resourceType, isNew) {
		$metadataAddonPopup.dialog("close");
		$metadataAddonForm.load("../plugins/mb_metadata_addon.php", function () {
			//push infos to help dialogs
			$metadataAddonForm.find(".help-dialog").helpDialog();
			//initialize datepicker
			$('.hasdatepicker').datepicker({dateFormat:'yy-mm-dd', yearRange: '1900:2050', buttonImageOnly: true, changeYear: true,
constraintInput: true});
			//first get json
			if (!isNew) {
				that.getAddedMetadata(metadataId);
			} else {
				//show chooser
				$("#metadataUrlEditor").css("display","block"); 
				$("#addonChooser").css("display","block");
				//get initial values (title/abstract)
				that.getInitialResourceMetadata(metadataId, resourceId, resourceType);
			}
			that.showForm(metadataId, resourceId, resourceType, isNew);
			//if add only dataset metadata, the reference to other datasets is not possible!
			if (resourceType == 'metadata') {
				$("#internalLinkage").css("display","none")
			} 
			$("#uploadImage").click(function () {
				initUploadForm(resourceId, resourceType);
				//initUploadForm(layerId);
			});
			$("#uploadgmlimage").click(function () {
				initUploadGmlForm(metadataId);
			});
			$("#delete_existing_polygon").click(function () {
				deleteGmlPolygon(metadataId);
			});
			$('#label_responsible_party_name').css('display', 'none');
			$('#responsible_party_name').css('display', 'none');
			$('#label_responsible_party_email').css('display', 'none');
			$('#responsible_party_email').css('display', 'none');
			$("#check_overwrite_responsible_party").change(function () {
				if ($("#check_overwrite_responsible_party").is(":checked")) {
					$('#label_responsible_party_name').css('display', 'block');
					$('#responsible_party_name').css('display', 'block');
					$('#label_responsible_party_email').css('display', 'block');
					$('#responsible_party_email').css('display', 'block');
				} else {
					$('#label_responsible_party_name').css('display', 'none');
					$('#responsible_party_name').css('display', 'none');
					$('#label_responsible_party_email').css('display', 'none');
					$('#responsible_party_email').css('display', 'none');
				}
			});
			//enable reset buttons for categories
			enableResetButtonMd();
			//alert($("#uploadImage").attr('onclick')); //there has been a bigger problem when setting an onclick attribut with jquery :-(
			//TODO: make the fields resizable 
			//$( "#abstract" ).resizable({ minWidth: 75 });
		});
	}
	initMetadataAddon = function(metadataId, resourceId, resourceType, isNew) {
		//close old window and load form	
		that.init(metadataId, resourceId, resourceType, isNew);
	}
};

$showMetadataAddonDiv.mapbender(new ShowMetadataAddonApi());
