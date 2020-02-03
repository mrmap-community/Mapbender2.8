var $previewImport = $(this);
var importPreviewMetadataId;

var PreviewImportApi = function () {
	var that = this;
	var type;
	
	this.events = {
		"uploadComplete" : new Mapbender.Event()
	};
	
	this.events.uploadComplete.register(function () {
		$previewImport.dialog("close");
	});
	
	var importUploadedFile = function(filename, metadataId, callback){
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "importPreview",
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
				$previewImport.dialog("close");
				//show delete button
				$("#delete_existing_preview").css("display","block");
				getPreviewUrl(metadataId); //will also activate preview image in editor
				if ($.isFunction(callback)) {
					callback(obj.id);
				}
			}
		});
		req.send();
	};

	$previewImport.upload({
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
	        
	        importUploadedFile(result.filename, that.importPreviewMetadataId, function (id) {
		        that.events.uploadComplete.trigger({
					"type": type,
					"id": id
				});
			}); 
    	}
	}).dialog({
		title: 'Preview import',
		autoOpen: false,
		modal: false,
		width: 580
	});


	this.init = function (obj) {
		type = obj.type;
		$previewImport.dialog("open");
	};

	initPreviewImport = function (metadataId) {
		that.importPreviewMetadataId = metadataId;
		$previewImport.dialog("open");
		return true;
	};
	
};

$previewImport.mapbender(new PreviewImportApi());
