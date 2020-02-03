/**
 * Package: mb_metadata_featuretypeTree
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

var $metadataFeaturetypeTree = $(this);

var MetadataFeaturetypeTreeApi = function (o) {
	var that = this;

	var instanceId = "choose";

	var createFolder = function (set) {
		return {
			attr: {
				data: $.toJSON(set.attr)
			},
			data: set.attr.featuretype_name,
			state: "closed",
			children: []
		};
	};

	var createLeaf = function (set) {
		if (set.attr.layer_coupling != 0) {
			//create html annex to title element
			annex = set.attr.featuretype_name+" - "+set.attr.featuretype_coupling+" x coupled";
		} else {
			annex = set.attr.featuretype_name;
		}
		//following things can be done to show icons in the title element - problem: if the span is clicked, the checkbox is set an no layer is selected - TODO should be fixed 
		/*leafTitle = "<span>";
		if (set.attr.layer_coupling != 0) {
			//create html annex to title element
			leafTitle = leafTitle+"<img src=\"../img/osgeo_graphics/geosilk/link.png\" title=\"coupled\">";
			//annex = set.attr.layer_title;
		}
		if (set.attr.inspire_cats != 0) {
			leafTitle = leafTitle+"<img width=\"15\" height=\"15\" src=\"../img/inspire_tr_36.png\" title=\"INSPIRE classified\">";
		}
		leafTitle = leafTitle+set.attr.layer_title+"</span>";*/
		return {
			attr: {
				data: $.toJSON(set.attr)
			},
			data: {
				title: annex
			}
		};
	};



/*	
	var createLeaf = function (set) {
		return {
			attr: {
				data: $.toJSON(set.attr)
			},
			data: {
				title: set.attr.featuretype_name
			}
		};
	};
*/	
	var toJsTreeJson = function (nestedSets) {
		if (!nestedSets.length && nestedSets.length !== 0) {
			new Mapbender.Exception("Nested sets not an array.");
			return [];
		}
		var right = null;
		if (arguments.length === 2) {
			right = arguments[1];
		}
		var children = [];
		while (nestedSets.length > 0) {
			set = nestedSets.shift();
			if (typeof set.left != "number" || typeof set.right != "number") {
				new Mapbender.Exception("Left or right not set.");
				return [];
			}

			// is a different subtree, go back
			if (right !== null && right < set.right) {
				nestedSets.unshift(set);
				return children;
			}
			// is a leaf
			else if (set.right - set.left === 1) {
				children.push(createLeaf(set));
			}
			// is a folder
			else {
				var node = createFolder(set);
				var nodeChildren = toJsTreeJson(nestedSets, set.right);
				children.push($.extend(node, {
					children: nodeChildren
				}));
			}
		}
		return children;
	};

	var checkFeaturetype = function () {
		$("#" + instanceId).find("li").each(function () {
			var metadata = $(this).metadata({
				type: "attr",
				name: "data"
			});
			if (metadata && metadata.featuretype_searchable) {
				$("#" + instanceId).jstree("check_node", this);
			}
		});
	};

/*	var initTree = function (nestedSets) {
		var jsTreeData = toJsTreeJson(nestedSets);
		jsTreeData[0].state = "open";
		
		var jsTree = $.tree.reference(instanceId);
		if (jsTree !== null) {
			jsTree.destroy();
		}
		
		$("#" + instanceId).tree({
			ui: {
//				theme_path: "../extensions/jsTree.v.0.9.9a2/themes/checkbox/style.css",
//				theme_name: "checkbox"
				theme_path: "../extensions/jsTree.v.0.9.9a2/themes/default/style.css",
				theme_name: "default"
			},
			plugins: {
//				checkbox: {			
//				}
			},
			data : { 
				type : "json",
				opts : {
					static : jsTreeData
				}
			},
			callback: {
				onselect: function (node, treeObj) {
					var data = $(node).metadata({
						type: "attr",
						name: "data"
					});
					that.events.selected.trigger({
						"featuretype": data
					});
				}
			}
		});
	};
*/
	var initTree = function (nestedSets) {
		var jsTreeData = toJsTreeJson(nestedSets);	
		jsTreeData[0].state = "open";
		
		$("#" + instanceId).jstree("destroy");
		
		$.jstree._themes = "../extensions/jsTree.v.1.0rc/themes/";
		
		$("#" + instanceId).empty().jstree({ 
			"json_data" : {
				"data" : jsTreeData
			},
            "checkbox" :{
                "check_recursively": false
            },
 	    "core" : {"html_titles" : true},

			"plugins" : [ "themes", "json_data", "ui", "checkbox" ]
		});
		
		$("#" + instanceId).bind("before.jstree", function (evt, data) {
			if (data.func === "change_state" && data.args[0].tagName.toUpperCase() === "A") {
				$("#" + instanceId).find("a").removeClass("jstree-clicked");
				$(data.args[0]).parent().children("a").addClass("jstree-clicked");
				var metadata = $(data.args[0]).parent().metadata({
					type: "attr",
					name: "data"
				});
				that.events.selected.trigger({
					"featuretype": metadata
				});
									
				evt.stopImmediatePropagation();
				return false;
			}
		});
		checkFeaturetype();
	};



	this.serialize = function (callback) {
		var data = {
			wfs: {
				"featuretype_searchable": []
			}
		};

		var $checked = $("#" + instanceId).jstree("get_checked");
		$checked.each(function () {
			var metadata = $(this).metadata({
				type: "attr",
				name: "data"
			});
			data.wfs.featuretype_searchable.push(metadata.featuretype_id);
		});
		
		if ($.isFunction(callback)) {
			callback(data);
		}
		return data;
	};	


	this.events = {
		selected: new Mapbender.Event()
	};

	this.init = function (obj) {
		// get featuretypes from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_metadata_server.php",
			method: "getFeaturetypeByWfs",
			parameters: {
				"id": obj
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				initTree(obj.nestedSets);
			}
		});
		req.send();		
	};
};

$metadataFeaturetypeTree.mapbender(new MetadataFeaturetypeTreeApi(options));
