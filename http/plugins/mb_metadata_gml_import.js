var $gmlImport = $(this);
var importGmlMetadataId;

var GmlImportApi = function () {
	var that = this;
	var type;
	
	this.events = {
		"uploadComplete" : new Mapbender.Event()
	};
	
	this.events.uploadComplete.register(function () {
		$gmlImport.dialog("close");
	});
	
	var importUploadedFile = function(filename, metadataId, callback){
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "importGmlAddon",
			parameters: {
				filename: filename,
				"metadataId": metadataId
			},
			callback: function (obj, result, message, errorCode) {				
				if (!result) {
					switch (errorCode) {
						case -1002:
							alert("file: "+filename+"has problems: "+message);
							break;
						default:
							alert(message);
							return;
					}
				}
				alert(message);
				$gmlImport.dialog("close");
				//alter the url to the overview image to force reload!
				//alert($("#extent_preview").attr('src'));
				//show delete button
				$("#delete_existing_polygon").css("display","block");
				//alert($("#extent_preview").attr('src'));
				urlstring = $("#extent_preview").attr('src')+"&time="+ new Date().getTime();
				$("#extent_preview").attr('src',urlstring);
				//invoke external script from mb_metadata_showMetadataAddon.js
				//that.fillLayerForm(layerId);
				//Mapbender.modules.mb_md_showMetadataAddon.fillLayerForm(layerId);
				if ($.isFunction(callback)) {
					callback(obj.id);
				}
			}
		});
		req.send();
	};

	$gmlImport.upload({
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
	        
	        importUploadedFile(result.filename, that.importGmlMetadataId, function (id) {
		        that.events.uploadComplete.trigger({
					"type": type,
					"id": id
				});
			}); 
    	}
	}).dialog({
		title: 'GML Import',
		autoOpen: false,
		modal: false,
		width: 580
	});


	this.init = function (obj) {
		type = obj.type;
		$gmlImport.dialog("open");
	};

	initGmlImport = function (metadataId) {
		that.importGmlMetadataId = metadataId;
		$gmlImport.dialog("open");
		return true;
	};
	
};

$gmlImport.mapbender(new GmlImportApi());
