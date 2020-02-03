var $geodataImport = $(this);
var importGeodataId;

var GeodataImportApi = function () {
	var that = this;
	var type;
	
	this.events = {
		"uploadComplete" : new Mapbender.Event()
	};
	
	this.events.uploadComplete.register(function () {
		$geodataImport.dialog("close");
	});
	
	var importUploadedFile = function(filename, Id, callback){
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_geodata_server.php",
			method: "store",
			parameters: {
				filename: filename,
				layerId: Id
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
				$geodataImport.dialog("close");
				if ($.isFunction(callback)) {
					callback(obj.id);
				}
			}
		});
		req.send();
	};

	$geodataImport.upload({
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
	        
	        	importUploadedFile(result.filename, that.importGeodataId, function (id) {
		        	that.events.uploadComplete.trigger({
						"type": type,
						"id": id
				});
			}); 
    		}
		}).dialog({
			title: 'Geodata Import',
			autoOpen: false,
			modal: true,
			width: 580
		});

	this.init = function (obj) {
		type = obj.type;
		$geodataImport.dialog("open");
		alert('test');
	};

	initGeodataImport = function (Id) {
		that.importGeodataId = Id;
		$geodataImport.dialog("open");
		return true;
	};	
};

$geodataImport.mapbender(new GeodataImportApi());
