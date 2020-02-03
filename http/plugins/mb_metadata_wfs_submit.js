var $submit = $(this);

var MetadataSubmitApi = function () {
	var that = this;
	var formData = {};
	
	var serializeCallback = function (data) {
		if (data === null) {
			formData = null;
			return;
		}
		if (formData !== null) {
			if (formData.wfs && data.wfs) {
				formData.wfs = $.extend(formData.wfs, data.wfs);
			}
			else if (formData.featuretype && data.featuretype) {
				formData.featuretype = $.extend(formData.featuretype, data.featuretype);
			}
			else {
				formData = $.extend(formData, data);
			}
		}
	};
	
	this.enable = function () {
		$submit.find("input[type='submit']").removeAttr("disabled");
	};
	
	this.submit = function () {
		formData = {};
		this.events.submit.trigger({
			callback: serializeCallback
		});

		if (formData === null) {
			alert("Please complete or correct the data in the form.");
			return;
		}

		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "save",
			parameters: {
				"data": formData,
				"serviceType": "wfs"
			},
			callback: function (obj, result, message) {
				if (!result) {
					$("<div></div>").text(!message ? "An error occured." : message).dialog({
						modal: true
					});
					return;
				}
				Mapbender.modules.mb_md_featuretype_tree.init(Mapbender.modules.mb_md_featuretype.getWfsId());
				$("<div></div>").text(message).dialog({
					modal: true
				});

			}
		});
		req.send();			
	};
	
	this.events = {
		submit: new Mapbender.Event()
	};
	
	var init = function () {
		$submit.find("input[type='submit']").bind("click", function () {
			that.submit();
		});
	};

	init();
};

$submit.mapbender(new MetadataSubmitApi());
