/**
 * Package: mb_metadata_featuretype
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
 * http://www.mapbender.org/User:Christoph_Baudson
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

var $metadataFeaturetype = $(this);
var $metadataForm = $("<form>No featuretype selected.</form>").appendTo($metadataFeaturetype);

var MetadataFeaturetypeApi = function (o) {
	var that = this;
	var validator;
	var formReady = false;
	var wfsId;
	var featuretypeId;
	
	var disabledFields = [
		"featuretype_custom_category_id", 
		"featuretype_inspire_category_id", 
		"featuretype_md_topic_category_id", 
		"featuretype_keyword", 
		"featuretype_abstract", 
		"featuretype_title"
	];

	this.events = {
		initialized: new Mapbender.Event(),
		submit: new Mapbender.Event(),
		showOriginalFeaturetypeMetadata : new Mapbender.Event()
	};
		
	this.valid = function () {
		if (validator && validator.numberOfInvalids() > 0) {
			$metadataForm.valid();
			return false;
		}
		return true;
	};

	this.serialize = function (callback) {
		$metadataForm.submit();
		var data = null;
		if (this.valid()) {
			data = {
				featuretype: $metadataForm.easyform("serialize")
			};
		}
		if ($.isFunction(callback)) {
			callback(data);
		}
		return data !== null ? data.featuretype : data;
	};

	this.fillForm = function (obj) {
		$(disabledFields).each(function () {
			$("#" + this).removeAttr("disabled");
		});

		featuretypeId = obj.featuretype_id;

		$metadataForm.easyform("reset");
		
		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getResourceMetadata",
			parameters: {
				"resourceId": featuretypeId,
				"resourceType": "featuretype"
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$metadataForm.easyform("fill", obj);
				//delete entries of #featuretype_id_p if given
				$('#featuretype_id_p').children().remove();
				$('#featuretype_id_p').append('<a target=\"_blank\"href=\"../php/mod_showMetadata.php?resource=featuretype&layout=tabs&id='+featuretypeId+'\">Metadata Preview Featuretype '+featuretypeId+'</a>');
				//delete metadataURL entries
				$('.metadataEntry').remove();
				//fill MetadataURLs into metadata_selectbox_id
				that.fillMetadataURLs(obj);
				that.valid();
				that.enableResetButton();
			}
		});
		req.send();		
	};
	//function generate updated metadataUrl entries
	this.fillMetadataURLs = function (obj) {
		featuretypeId = obj.featuretype_id;
		//for size of md_metadata records:
		for (i=0;i<obj.md_metadata.metadata_id.length;i++) {
				if (obj.md_metadata.origin[i] == "capabilities") {
					if (obj.md_metadata.internal[i] == 1) {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/server_map-ilink.png' title='link to metadata from capabilities'/></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteInternalMetadataLinkage("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					} else {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/osgeo_graphics/geosilk/server_map.png' title='capabilities'/></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td></td></tr>").appendTo($("#metadataTable"));
					}
				}
				if (obj.md_metadata.origin[i] == "external") {
					if (obj.md_metadata.internal[i] == 1) {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/link-ilink.png' title='link to external linkage'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteInternalMetadataLinkage("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					} else {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/osgeo_graphics/geosilk/link.png' title='linkage'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteAddedMetadata("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					}
				}
				if (obj.md_metadata.origin[i] == "upload") {
					if (obj.md_metadata.internal[i] == 1) {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/up-ilink.png' title='link to external uploaded data'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteInternalMetadataLinkage("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					} else {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/button_blue_red/up.png' title='uploaded data'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteAddedMetadata("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					}
				}
				if (obj.md_metadata.origin[i] == "metador") {
					if (obj.md_metadata.internal[i] == 1) {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/edit-select-all-ilink.png' title='link to external edited metadata'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteInternalMetadataLinkage("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					} else {
						$("<tr class='metadataEntry'><td>"+obj.md_metadata.metadata_id[i]+"</td><td><img src='../img/gnome/edit-select-all.png' title='metadata'/><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"' target='_blank'>"+obj.md_metadata.uuid[i]+"</a></td><td><a href='../php/mod_dataISOMetadata.php?outputFormat=iso19139&id="+obj.md_metadata.uuid[i]+"&validate=true' target='_blank'>validate</a></td><td><img class='clickable' title='edit' src='../img/pencil.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",false);return false;'/></td><td><img class='clickable' title='delete' src='../img/cross.png' onclick='deleteAddedMetadata("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\");return false;'/></td></tr>").appendTo($("#metadataTable"));
					}
				}
		}
		$("<img class='metadataEntry clickable' title='new' src='../img/add.png' onclick='initMetadataAddon("+obj.md_metadata.metadata_id[i]+","+featuretypeId+",\"featuretype\",true);return false;'/>").appendTo($("#metadataTable"));
		
	}
	
	this.enableResetButton = function () {
		$("#resetIsoTopicCats").click(function () {
			$("#featuretype_md_topic_category_id option").removeAttr("selected");
		});
		$("#resetCustomCats").click(function () {
			$("#featuretype_custom_category_id option").removeAttr("selected");
		});
		$("#resetInspireCats").click(function () {
			$("#featuretype_inspire_category_id option").removeAttr("selected");
		});
	}
	
	this.fill = function (obj) {
		$metadataForm.easyform("fill", obj);
	};
	
	var showOriginalFeaturetypeMetadata = function () {
		that.events.showOriginalFeaturetypeMetadata.trigger({
			data : {
				wfsId : wfsId,
				featuretypeData : $metadataForm.easyform("serialize")
			}
		});
	};
	
	this.getWfsId = function() {
		return wfsId;
	}

	this.getFeaturetypeId = function() {
		return featuretypeId;
	}


	this.init = function (obj) {
		delete featuretypeId;
		//delete metadataURL entries
		$('.metadataEntry').remove();
		$metadataForm.easyform("reset");

		wfsId = obj;

		if (!wfsId) {
			return;
		}

		var formData = arguments.length >= 2 ? arguments[1] : undefined;

		if (!formReady) {
			$metadataForm.load("../plugins/mb_metadata_featuretype.php", function () {
				$metadataForm.find(".help-dialog").helpDialog();

				$metadataForm.find(".original-metadata-featuretype").bind("click", function() {
					showOriginalFeaturetypeMetadata();
				});	

				validator = $metadataForm.validate({
					submitHandler: function () {
						return false;
					}
				});

				that.events.initialized.trigger({
					wfsId: wfsId
				});
				formReady = true;
			});
			return;
		}
		$(disabledFields).each(function () {
			$("#" + this).attr("disabled", "disabled");
		});
		that.events.initialized.trigger({
			wfsId: wfsId
		});
	};
	
	Mapbender.events.localize.register(function () {
		that.valid();
		var formData = $metadataForm.easyform("serialize");
		formReady = false;
		that.init(wfsId, formData);
	});
	Mapbender.events.init.register(function () {
		that.valid();
	});
};

$metadataFeaturetype.mapbender(new MetadataFeaturetypeApi(options));
