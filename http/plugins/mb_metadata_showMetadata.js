var $showMetadataDiv = $(this);
var $metadataForm = $("<form></form>").appendTo($showMetadataDiv);
var $metadataPopup = $("<div></div>");
var $metadataUploadPopup = $("<div></div>");

var ShowMetadataApi = function() {
	var that = this;
	var resourceId;
	var resourceType;
	var metadataId;
   
	//Function, which pulls the metadata out off the mapbender registry and give a possibility to edit the record or link
	this.valid = function () {
		if (validator && validator.numberOfInvalids() > 0) {
			$metadataForm.valid();
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
				$metadataForm.easyform("reset");
				$metadataForm.easyform("fill", obj);
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
			method: "getOwnedApplicationMetadata",
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
			method: "getMetadata",
			parameters: {
				"metadataId": metadataId
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$metadataForm.easyform("reset");
				$metadataForm.easyform("fill", obj);
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
				//new for applications
				if (obj.fkey_gui_id != null) {
				    $(".gui_selectbox").val(obj.fkey_gui_id);
				}
				if (obj.fkey_wmc_serial_id != null) {
				    $(".wmc_selectbox").val(obj.fkey_wmc_serial_id);
				}
				if (obj.fkey_mapviewer_id != null) {
				    $(".default_viewer_selectbox").val(obj.fkey_mapviewer_id);
				}
		 /*<input type="radio" id="app_type_external" name="application_type" value="app_type_external"checked="checked"><label for="app_type_external"><?php echo _mb("External application");?></label><br>
		 <input type="radio" id="app_type_gui" name="application_type" value="app_type_gui"><label for="app_type_gui"><?php echo _mb("Mapbender GUI");?></label><br>
		 <input type="radio" id="app_type_gui_wmc"*/
				if (obj.fkey_gui_id != null) {
				    if (obj.fkey_wmc_serial_id != null) {
				    	//generate link
						$("#app_type_gui_wmc").attr('checked', true);
						that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
						//hide other forms
						$('#gui_select_fieldset').css('display', 'block');
				        $('#wmc_select_fieldset').css('display', 'block');
				        $('#default_viewer_fieldset').css('display', 'block');
                        $('#address_link_fieldset').css('display', 'none');
				        $('#link').removeClass("required");
				        $('#fkey_wmc_serial_id').addClass("required");
				        $('#fkey_gui_id').addClass("required");
				    } else {
						$("#app_type_gui").attr('checked', true);
						that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), false);
						//hide other forms
						$('#gui_select_fieldset').css('display', 'block');
						$('#default_viewer_fieldset').css('display', 'block');
				        $('#wmc_select_fieldset').css('display', 'none');
                                        $('#address_link_fieldset').css('display', 'none');
				        $('#link').removeClass("required");
				        $('#fkey_wmc_serial_id').removeClass("required");
				        $('#fkey_gui_id').addClass("required");
				    }
				} else {
					$("#app_type_external").attr('checked', true);
					$('#gui_select_fieldset').css('display', 'none');
				    $('#wmc_select_fieldset').css('display', 'none');
                    $('#address_link_fieldset').css('display', 'block');
					//if (obj.link != '') {
					    $("#link").val(obj.link);
					//} else {			
					//    $("#link").attr('');
					//}
					if (isURL($("#link").val())) {
				            $("#preview_link").text($("#link").val());
				            $("#preview_link").attr("href",$("#link").val());
				    	} else {
				            $("#preview_link").text('currently not set');
				            $("#preview_link").removeAttr("href");
					}	
					//fill preview
					//hide other forms
				}
				if (obj.preview_image == '{localstorage}') {
				    $("#preview_type_upload").attr('checked', true);
				    $("#preview_image_fieldset_url").css('display', 'none');
				    $("#preview_image_fieldset_upload").css('display', 'block');
				    //exchange im src attr with preview image generator script
				    getPreviewUrl(metadataId);
				    $("#delete_existing_preview").css("display","block");
				}
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
				if (obj.fkey_mb_group_id !== "0") {
					//alert(JSON.stringify(obj));
					that.fillSelectGroup(obj.fkey_mb_group_id);
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

	this.fillMapviewerPreviewUrl = function(mapviewerId, guiId=false, wmcId=false) {
            //alert('fillMapviewerPreviewUrl');
	    //get mapviewer preview from server
	    if (mapviewerId == '' || mapviewerId == null || $("#app_type_external").is(":checked")) {
		alert('No mapviewerId set - set url empty!');
		$("#preview_link").text("No api url defined til now!");
	        $("#preview_link").attr("href",'');
	    } else {
		//alert('Get url from class_administration!');
	    var req = new Mapbender.Ajax.Request({
		url: "../plugins/mb_metadata_server.php",
		method: "getMapviewerUrl",
		parameters: {
			"mapviewerId": mapviewerId,
			"guiId": guiId,
			"wmcId": wmcId,
		},
		callback: function (obj, result, message) {
			if (!result) {
				return;
			}
			if (obj.mapviewer_url) {
			    $("#preview_link").text(obj.mapviewer_url);
			    $("#preview_link").attr("href",obj.mapviewer_url);
			}
		}
	    });		
            req.send();
	}
	}	
        this.fillSelectGroup = function(obj) {

            $('#fkey_mb_group_id option[value="'+obj+'"]').attr('selected', 'selected');
        }
        
    /*
     * 
     */
    this.selectPredefinedAccessConstraints = function(obj) {
    	//set value of textfield accessconstraints_md to selected value of inspire constraints dropdown list
    	alert("mb_metadata_showMetadata.js - selectPredefinedAccessConstraints invoked");
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
				} else {
					$('#licence_symbol_md').attr('src', '');
					$('#licence_descriptionlink_md').attr('href', '');
					$('#licence_descriptionlink_md').text('');
					$('#open_symbol_md').attr('src', '');
					$('#license_info_md').css('display', 'none');
				}
			}
		});
		req.send();
	}	

	this.insertAddedMetadata = function(resourceId, resourceType, data){
		// push metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "insertMetadata",
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
					//first this means it will be an application!
					//that.fillResourceForm(resourceId, resourceType);
					Mapbender.modules.mb_metadata_manager_select.initTable();
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
			method: "updateMetadata",
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
					//that.fillResourceForm(resourceId, resourceType);
					Mapbender.modules.mb_metadata_manager_select.initTable();
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
			method: "deleteMetadata",
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
		$metadataPopup.append($metadataForm);
		$metadataPopup.dialog({
			title : "Metadata Editor for "+resourceType, 
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
						if ($metadataForm.valid() != true) {
							alert("Form not valid - please check your input!"); //TODO use translations and make a php file from this
							return;
						}
					}
//alert("showForm - save form: resourceType: "+resourceType);
					var formData = $metadataForm.easyform("serialize");
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
		$metadataPopup.dialog("open");
	};
	
	initUploadForm = function (resourceId, resourceType) {
		$metadataPopup.dialog("close");
		//TODO: problem - when invoking it first - mb_metadata.php - there is no information about the resourceType and its id. Some error occurs - but hidden ;-)
		initXmlImport(resourceId, resourceType);
		that.fillResourceForm(resourceId, resourceType);
	}
	initUploadPreviewForm = function (metadataId) {
		initPreviewImport(metadataId);
	}
	
	getPreviewUrl = function (metadataId){
	    	var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getPreviewUrl",
			parameters: {
				"metadataId": metadataId
			},
			callback: function (obj, result, message) {
				if (!result) {
					alert(message);
					return;
				}
				//alert(obj.preview_url);	
				//set src of preview image to preview url
				$("#image_preview").attr('src',obj.preview_url);
				urlstring = $("#image_preview").attr('src')+"&time="+ new Date().getTime();
				$("#image_preview").attr('src',urlstring);
				$("#image_preview").css("display","block"); 
			}
		});
		req.send();	
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

	isURL = function (str) {
  		var pattern = new RegExp('^((ft|htt)ps?:\\/\\/)?'+ // protocol
  		'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name and extension
  		'((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
  		'(\\:\\d+)?'+ // port
  		'(\\/[-a-z\\d%@_.~+&:]*)*'+ // path
  		'(\\?[;&a-z\\d%@_.,~+&:=-]*)?'+ // query string
  		'(\\#[-a-z\\d_]*)?$','i'); // fragment locator
  		return pattern.test(str);
	}

	initUploadGmlForm = function (metadataId) {
		//don't show possibility if metadata was not created before - only afterwards!
		initGmlImport(metadataId);
	}

	deletePreview = function (metadataId) {
		//invoke server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "deletePreview",
			parameters: {
				"metadataId": metadataId
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}	
				//hide delete image
				$("#delete_existing_preview").css("display","none"); 				
				$("#image_preview").attr('src','');
				$("#image_preview").css("display","none");
				//update preview
				$("<div></div>").text(message).dialog({
					modal: true
				});
			}
		});
		req.send();	
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
		$metadataPopup.dialog("close");
		if (resourceType == 'application') {
		    	$metadataForm.load("../plugins/mb_metadataApplication.php", function () {
				//push infos to help dialogs
				$metadataForm.find(".help-dialog").helpDialog();
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
				//disable options which are not needed
 				$('#inspire_categories_fieldset').css('display', 'none');


				if ($("#app_type_external").is(":checked")) {
				    $('#gui_select_fieldset').css('display', 'none');
				    $('#wmc_select_fieldset').css('display', 'none');
				    $('#default_viewer_fieldset').css('display', 'none');
				    $('#link').addClass("required");
				    $('#fkey_gui_id').removeClass("required");
				    $('#fkey_wmc_serial_id').removeClass("required");
				}
				if ($("#app_type_gui").is(":checked")) {
				    $('#wmc_select_fieldset').css('display', 'none');
                                    $('#address_link_fieldset').css('display', 'none');
				    $('#link').removeClass("required");
				    $('#fkey_wmc_serial_id').removeClass("required");
				    $('#fkey_gui_id').addClass("required");
				}
				if ($("#app_type_gui_wmc").is(":checked")) {
                                    $('#address_link_fieldset').css('display', 'none');
				    $('#link').removeClass("required");
				    $('#fkey_gui_id').addClass("required");
				    $('#fkey_wmc_serial_id').addClass("required");
				}

				if ($("#preview_type_external").is(":checked")) {
                                    $('#preview_image_fieldset_upload').css('display', 'none');
				    $('#preview_image_fieldset_url').css('display', 'block');	
				}
				if ($("#preview_type_upload").is(":checked")) {
                                    $('#preview_image_fieldset_upload').css('display', 'block');
				    $('#preview_image_fieldset_url').css('display', 'none');
				}

				$("#uploadImage").click(function () {
					initUploadForm(resourceId, resourceType);
					//initUploadForm(layerId);
				});
				$("#uploadPreviewImg").click(function () {
					initUploadPreviewForm(metadataId);
				});
				$("#delete_existing_preview").click(function () {
					deletePreview(metadataId);
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

				if ($('#preview_image').val() == '{localstorage}') {
				    $('#preview_type_upload').attr('checked', 'checked');
				}

				$('#fkey_wmc_serial_id').change(function () {
					//build url
					//check if gui_id already set before - else alert
					if ($('#fkey_gui_id').val() !== '') {
					    //internalAppUrl = $('#mainDiv').attr('mapbender_url') + 'gui_id=' + $('#fkey_gui_id').val() + '&WMC=' + $('#fkey_wmc_serial_id').val();
					    //$("#preview_link").text(internalAppUrl);
					    //$("#preview_link").attr("href",internalAppUrl);
					    that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
					} else {
					    alert('Before you can select a WMC, you first have to select a GUI from the list above!');
					    //unset wmc again ;-)
					    $('#fkey_wmc_serial_id option[value=""]').attr('selected', 'selected');
					}
					//generate linkage 
					that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
				});
				$('#fkey_gui_id').change(function () {
					if ($('#fkey_gui_id').val() !== '') {
					    //internalAppUrl = $('#mainDiv').attr('mapbender_url') + 'gui_id=' + $('#fkey_gui_id').val();
					    //$("#preview_link").text(internalAppUrl);
					    //$("#preview_link").attr("href",internalAppUrl);
                                            that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
					} else {
					    $("#preview_link").text("currently not set");
					    $("#preview_link").removeAttr("href");
					}
	
				});
				$('#fkey_mapviewer_id').change(function () {
					if ($('#fkey_mapviewer_id').val() !== '') {
                                            that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
					} else {
					    $("#preview_link").text("currently not set");
					    $("#preview_link").removeAttr("href");
					}
	
				});
				$("[name='preview_type']").change(function () {
				    if ($("#preview_type_external").is(":checked")) {
					$('#preview_image_fieldset_upload').css('display', 'none');
					$('#preview_image_fieldset_url').css('display', 'block');
				    }
				    if ($("#preview_type_upload").is(":checked")) {
					$('#preview_image_fieldset_upload').css('display', 'block');
					$('#preview_image_fieldset_url').css('display', 'none');
				    }
				});

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
				//change of application type
				$("[name='application_type']").change(function () {
					if ($("#app_type_external").is(":checked")) {				    
						$('#gui_select_fieldset').css('display', 'none');
				    		$('#wmc_select_fieldset').css('display', 'none');
						$('#default_viewer_fieldset').css('display', 'none');
						$('#address_link_fieldset').css('display', 'block');
						if (isURL($("#link").val())) {
				        	    $("#preview_link").text($("#link").val());
				        	    $("#preview_link").attr("href",$("#link").val());
				    		} else {
				        	    $("#preview_link").text('currently not set');
				        	    $("#preview_link").removeAttr("href");
						}
						//set values of other fields to null
						$('#fkey_wmc_serial_id').val("");
						$('#fkey_gui_id').val("");
						$('#link').addClass("required");
						$('#fkey_gui_id').removeClass("required");
						$('#fkey_wmc_serial_id').removeClass("required");

					}
					if ($("#app_type_gui").is(":checked")) {				    
						$('#gui_select_fieldset').css('display', 'block');
$('#default_viewer_fieldset').css('display', 'block');
				    		$('#wmc_select_fieldset').css('display', 'none');
						$('#address_link_fieldset').css('display', 'none');
						$('#fkey_wmc_serial_id').val("");
						$('#link').val("");
						$('#link').removeClass("required");
						$('#fkey_wmc_serial_id').removeClass("required");
						$('#fkey_gui_id').addClass("required");
						//$("#preview_link").text('currently not set');
				        	//$("#preview_link").removeAttr("href","");
						that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
						
					}
					if ($("#app_type_gui_wmc").is(":checked")) {				    
						$('#gui_select_fieldset').css('display', 'block');
				    		$('#wmc_select_fieldset').css('display', 'block');
$('#default_viewer_fieldset').css('display', 'block');
						$('#address_link_fieldset').css('display', 'none');
						$('#link').val("");
						$('#link').removeClass("required");
						$('#fkey_gui_id').addClass("required");
						$('#fkey_wmc_serial_id').addClass("required");
						//$("#preview_link").text('currently not set');
				        	//$("#preview_link").removeAttr("href","");
						that.fillMapviewerPreviewUrl($('#fkey_mapviewer_id').val(), $('#fkey_gui_id').val(), $('#fkey_wmc_serial_id').val());
					}
				});
				//change of url - when write it to field - make link, if link is found by expr
				//
				//$("#link").onclick(alert('test'));
				$("#link")[0].oninput = function(){
				    if (isURL($("#link").val())) {
				        $("#preview_link").text($("#link").val());
				        $("#preview_link").attr("href",$("#link").val());
				    } else {
				        $("#preview_link").text('currently not set');
				        $("#preview_link").removeAttr("href");
				    }
                                };
				/*$("#link").on('input',function(e) {
				    //alert($("#link").val());
 				    $("#preview_link").text( $(e.target).val() );
				    //alert($("#link").val());
				    //$("#preview_link").text($("#link").val());
				    //$("#preview_link").attr("href",$("#link").val());
				});*/
				//$("#link").trigger("input");
				//enable reset buttons for categories
				enableResetButtonMd();
				//alert($("#uploadImage").attr('onclick')); //there has been a bigger problem when setting an onclick attribut with jquery :-(
				//TODO: make the fields resizable 
				//$( "#abstract" ).resizable({ minWidth: 75 });
			});
		} else {
			$metadataForm.load("../plugins/mb_metadataAddon.php", function () {
				//push infos to help dialogs
				$metadataForm.find(".help-dialog").helpDialog();
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
		
	}
	initMetadata = function(metadataId, resourceId, resourceType, isNew) {
		//close old window and load form	
		that.init(metadataId, resourceId, resourceType, isNew);
	}
};

$showMetadataDiv.mapbender(new ShowMetadataApi());
