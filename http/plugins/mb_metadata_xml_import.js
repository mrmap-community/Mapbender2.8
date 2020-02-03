var $xmlImport = $(this);
var importXmlResourceId;
var importXmlResourceType;
var XmlImportApi = function () {
	var that = this;
	var type;
	
	this.events = {
		"uploadComplete" : new Mapbender.Event()
	};
	
	this.events.uploadComplete.register(function () {
		$xmlImport.dialog("close");
	});
	
	//var importUploadedFile = function(filename, layerId, callback){
	var importUploadedFile = function(filename, resourceId, resourceType, callback){
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "importXmlAddon",
			parameters: {
				filename: filename,
				"resourceId": resourceId,
				"resourceType": resourceType
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
				$xmlImport.dialog("close");
				if (resourceType !== 'metadata') {
					//invoke external script from mb_metadata_showMetadataAddon.js
					Mapbender.modules.mb_md_showMetadataAddon.fillResourceForm(resourceId, resourceType);
				} else {
					Mapbender.modules.mb_metadata_manager_select.initTable();
				}
			
				if ($.isFunction(callback)) {
					callback(obj.id);
				}
			}
		});
		req.send();
	};

	$xmlImport.upload({
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
	        
	        importUploadedFile(result.filename, that.importXmlResourceId, that.importXmlResourceType, function (id) {
		        that.events.uploadComplete.trigger({
					"type": type,
					"id": id
				});
			}); 
    	}
	}).dialog({
		title: 'XML Import',
		autoOpen: false,
		modal: true,
		width: 580
	});

	this.init = function (obj) {
		type = obj.type;
		$xmlImport.dialog("open");
	};

	initXmlImport = function (resourceId, resourceType) {
		that.importXmlResourceId = resourceId;
		that.importXmlResourceType = resourceType;
		$xmlImport.dialog("open");
		return true;
	};
	
};

$xmlImport.mapbender(new XmlImportApi());
