/**
 * Package: mb_geodata_edit
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

var $geodataEdit = $(this);
var $geodataForm = $("<form>No dataset selected.</form>").appendTo($geodataEdit);

var GeodataEditApi = function (o) {
	var that = this;
	var validator;
	var formReady = false;
	var metadataId;
	
	this.events = {
		showOriginalGeodata : new Mapbender.Event(),
		submit: new Mapbender.Event()
	};

	this.valid = function () {
		if (validator && validator.numberOfInvalids() > 0) {
			$geodataForm.valid();
			return false;
		}
		return true;
	};
	
	this.serialize = function (callback) {
		$geodataForm.submit();
		var data = null;
		if (this.valid()) {
			data = {
				metadata: $geodataForm.easyform("serialize")
			};
		}
		if ($.isFunction(callback)) {
			callback(data);
		}
		return data !== null ? data.metadata : data;
	};
	
	// second optional parameter formData
	var fillForm = function (obj) {
		if (arguments.length >= 2) {
			$geodataForm.easyform("reset");
			$geodataForm.easyform("fill", arguments[1]);
			that.valid();
			return;
		}
		
		// get geodata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_geodata_server.php",
			method: "getGeodata",
			parameters: {
				"id": obj
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$geodataForm.easyform("reset");
				$geodataForm.easyform("fill", obj);
                // select mdContact option
                var select = document.getElementById('fkey_mb_group_id');
                select.childNodes.forEach(function(option) {
                    if (option.value === obj.fkey_mb_group_id) {
                        option.selected = true;
                    }
                });
				that.valid();
			}
		});
		req.send();		
	};

	this.fillMdContact = function(obj) {
		// get mdContact from server per fkey_mb_group_id
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_geodata_server.php",
			method: "getContactGeodata",
			parameters: {
				"id": obj
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				//fill form on a not so easy way ;-)
				for (var key in obj) {
					if (key == 'mb_group_title' || key == 'mb_group_address' || key == 'mb_group_postcode' || key == 'mb_group_city' || key == 'mb_group_logo_path' || key == 'mb_group_email' || key == 'mb_group_voicetelephone'){
						document.getElementById(key).value = obj[key];
					}
				}
			}
		});
		req.send();
	}	

	this.fill = function (obj) {
		$geodataForm.easyform("fill", obj);
	};
	
	var showOriginalGeodata = function () {
		that.events.showOriginalGeodata.trigger({
			data : {
				metadataId : metadataId,
				metadataData : $geodataForm.easyform("serialize")
			}
		});
	};
	
	this.init = function (obj) {
	
		metadataId = obj;
		
		if (!metadataId) {
			return;
		}
				
		var formData = arguments.length >= 2 ? arguments[1] : undefined;
		
		if (!formReady) {
			$geodataForm.load("../plugins/mb_geodata_edit.php", function () {
				$geodataForm.find(".help-dialog").helpDialog();
				$geodataForm.find(".original-geodata-metadata").bind("click", 					function() {
					showOriginalGeodata();
				});				
				validator = $geodataForm.validate({
					submitHandler: function () {
						return false;
					}
				});
				if (formData !== undefined) {
					fillForm(obj, formData);
				}
				else {
					fillForm(obj);
				}
				formReady = true;
			});
			return;
		}
		fillForm(obj);
	};
	
	Mapbender.events.localize.register(function () {
		that.valid();
		var formData = $geodataForm.easyform("serialize");
		formReady = false;
		that.init(metadataId, formData);
	});
	Mapbender.events.init.register(function () {
		that.valid();
	});
};

$geodataEdit.mapbender(new GeodataEditApi(options));
