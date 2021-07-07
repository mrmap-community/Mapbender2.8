/**
 * Package: savewmc
 *
 * Description:
 * save workspace as WMC
 *
 * Files:
 *  - http/javascripts/mod_savewmc.php
 *  - http/javascripts/mod_savewmc.js
 *  - http/php/mod_savewmc_server.php
 *
 * SQL:
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment,
 * > e_title, e_element, e_src, e_attributes, e_left, e_top, e_width,
 * > e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file,
 * > e_mb_mod, e_target, e_requires, e_url) VALUES('<gui_id>','savewmc',2,1,
 * > 'save workspace as WMC','Save workspace as web map context document',
 * > 'img','../img/button_blink_red/wmc_save_off.png','',870,60,24,24,1,'',
 * > '','','mod_savewmc.php','','mapframe1','jq_ui_dialog,lzw_compression',
 * > 'http://www.mapbender.org/index.php/SaveWMC');
 * >
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title,
 * > e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index,
 * > e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, e_requires,
 * > e_url) VALUES('gui1','lzw_compression',3,1,'','','','','',NULL ,NULL ,NULL ,NULL ,
 * > NULL ,'','','','','lzw.js','','','');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value,
 * > context, var_type) VALUES('<gui_id>', 'savewmc', 'overwrite', '1', '',
 * > 'var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value,
 * > context, var_type) VALUES('<gui_id>', 'savewmc', 'saveInSession', '1',
 * > '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value,
 * > context, var_type) VALUES('<gui_id>', 'savewmc', 'lzwCompressed', 'false',
 * > '' ,'var');
 *
 * Help:
 * http://www.mapbender.org/index.php/SaveWMC
 *
 * Maintainer:
 * http://www.mapbender.org/User:Christoph_Baudson
 *
 *
 * Parameters:
 *
 * overwrite     - *[optional]* if set to 1, a WMC document is overwritten
 * 					if a WMC with the given name already exists
 *
 * saveInSession - *[optional]* if set to 1, the state of the client is
 * 					stored in the session after each map request in
 * 					the target map
 *
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

//
// init element_vars
//
var overwrite = options.overwrite || false;

var saveInSession = typeof options.saveInSession === "undefined" ?
	0 : options.saveInSession;

var lzwCompressed = typeof options.lzwCompressed === "undefined" ?
	false : options.lzwCompressed;

var browserCompatibilityMode = typeof options.browserCompatibilityMode === "undefined" ?
	0 : options.browserCompatibilityMode;

var userAgent = navigator.userAgent;

var pattern1=/Chrome/gi;
var pattern2=/Konqueror/gi;
var pattern3=/Opera/gi;
var pattern4=/Firefox/gi;
//alert(userAgent.match(pattern));
if (userAgent.match(pattern1) || userAgent.match(pattern2) || userAgent.match(pattern3) || userAgent.match(pattern4)) {
	//alert("Identified Browser don't support beforeunload sufficiently - the application will be slower than normal!");
	browserCompatibilityMode = 1;
}
//for all save by afterMapRequest
browserCompatibilityMode = 1;


function pausecomp(millis) { //http://www.sean.co.uk/a/webdesign/javascriptdelay.shtm
	var date = new Date();
	var curDate = null;
	do { curDate = new Date(); }
	while(curDate-date < millis);
}


if (typeof originalI18nObj !== "object") {
	var originalI18nObj = {};
}
if (typeof translatedI18nObj !== "object") {
	var translatedI18nObj = {};
}
//alert("javascripts/mod_savewmc.js: saveInSession: "+saveInSession);
//
// button behaviour
//
var $this = $(this);

String.prototype.hashCode = function() {

    if (Array.prototype.reduce) {
        return this.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);
    } else {

        var hash = 0, i, chr, len;
        if (this.length == 0) return hash;
        for (i = 0, len = this.length; i < len; i++) {
        chr   = this.charCodeAt(i);
        hash  = ((hash << 5) - hash) + chr;
        hash |= 0; // Convert to 32bit integer
        }
        return hash;
    }
};

var SaveWmcApi = function () {
	var that = this;

	this.extensionData = {};

	this.overwrite = overwrite;
	this.saveInSession = saveInSession;
	this.lzwCompressed = lzwCompressed;
	this.events = {
		saved: new Mapbender.Event()
	};

	this.setExtensionData = function (obj) {
		if (typeof obj === "object") {
			$.extend(this.extensionData, obj);
		}
		return this;
	};

	this.save = function (obj) {
		if (typeof obj !== "object") {
			new Mapbender.Exception("Invalid parameters.");
			return this;
		}
		if (obj.session === true) {
			sendMapDataToServer("session", this.saveInSession, function(result, status) {Mapbender.lastStoredMapsetHash = result.mapHash;}, lzwCompressed);
			return this;
		}
		if (typeof obj.attributes === "object" && typeof obj.callback === "function") {
			sendMapDataToServer(obj.attributes, 0, obj.callback, lzwCompressed);
		}
		return this;
	};

	var sendMapDataToServer = function (attributes, storeInSession, callbackFunction, beLzwCompressed) {
		var extensionDataString = "";
		if (that.extensionData !== null) {
			extensionDataString = $.toJSON(that.extensionData);
		}

		if (storeInSession == 1 && browserCompatibilityMode == 0) {
			//alert('AJAX will be set to asyncron!');
			$.ajaxSetup({async:false});
		}

		//
		// WORKAROUND....cannot serialize map object,
		// as it contains a jQuery collection, which is
		// cyclic.
		// Removing the $target from the map object before
		// serialization, and re-appending it afterwards
		//
		var $target = [];
		for (var i = 0; i < mb_mapObj.length; i++) {
			$target.push(mb_mapObj[i].$target);
			delete mb_mapObj[i].$target;
		}
            var kml = $('#mapframe1').data('kml');
            if(kml) {
                mb_mapObj[0].kmls = kml._kmls;
		//regenerate kml order before save it
		mb_mapObj[0].kmlOrder = Object.keys(mb_mapObj[0].kmls);
                //mb_mapObj[0].kmlOrder = kml.kmlOrder;
                var json = JSON.stringify(kml._kmls);
                if(Mapbender.options && Mapbender.options.MAX_WMC_LOCAL_DATA_SIZE && json && json.length > Mapbender.options.MAX_WMC_LOCAL_DATA_SIZE) {
                    alert('The maximum local data size is ' + Math.round(Mapbender.options.MAX_WMC_LOCAL_DATA_SIZE / 1024) + 'kb, your data is ' + Math.round(json.length / 1024) + 'kb, data will NOT be saved.');
                    mb_mapObj[0].kmls = null;
                    mb_mapObj[0].kmlOrder = null;
                }
            }
		//*****
		var mapObjWithoutHistory = mb_mapObj;
		//set history and hash itself to null to identify same object and store the hash in a special field
		mapObjWithoutHistory[0].mb_MapHistoryObj = null;
		that.mapObjHashWithoutHistory = $.toJSON(mapObjWithoutHistory).hashCode();
		//*****
		//send mapObj without
	    	var mapObjectToSend = $.toJSON(mb_mapObj);
		//if compression is demanded see http://rosettacode.org/wiki/LZW_compression#JavaScript
		if (beLzwCompressed == 'true') { //
			mapObjectToSend=LZWCompress(mapObjectToSend);
			//alert(LZWDecompress(mapObjectToSend));
		}
		// actual save request
		var req = new Mapbender.Ajax.Request({
	        url: "../php/mod_savewmc_server.php",
	        method: "saveWMC",
			parameters : {
			  saveInSession:storeInSession,
			  attributes:attributes,
			  overwrite:overwrite,
			  extensionData:extensionDataString,
			  lzwCompressed:beLzwCompressed,
			  mapObject:mapObjectToSend
			},
	        callback: function(result, status, message) {
				//new 2016 - check hashed mapset of mapframe1 against last storage
				result.mapHash = that.mapObjHashWithoutHistory;
				callbackFunction(result, status, message);
				that.events.saved.trigger();
			}
	    });
	    //new 2016 - check hashed mapset of mapframe1 against last storage
	    if (Mapbender.lastStoredMapsetHash !== that.mapObjHashWithoutHistory || storeInSession !== 1) {
	    	req.send();
	    } else {
		//alert("Configuration already saved before: "+Mapbender.lastStoredMapsetHash);
	    }

		//
		// reversal of above WORKAROUND
		//
		for (i = 0; i < mb_mapObj.length; i++) {
			mb_mapObj[i].$target = $target[i];
		}
	};

	var localize = function () {
		//
		// buttons
		//
		$("#" + options.id + "_saveWMCForm").dialog('option', 'buttons', getButtons());

		// title
		$("#" + options.id + "_saveWMCForm").dialog('option', 'title', translatedI18nObj.title);
		//
		// form
		//
		var $form = $("#" + options.id + "_saveWMCForm > form > fieldset");
		$form.children("label").each(function () {
			var forId = $(this).attr("for");
			switch (forId) {
				case options.id + "_wmctype" :
					$(this).text(translatedI18nObj.labelNewOrOverwrite).next()
						.children().eq(0).text(translatedI18nObj.labelNewWmc);
					break;
				case options.id + "_wmcname" :
					$(this).text(translatedI18nObj.labelName);
					break;
				case options.id + "_wmcabstract" :
					$(this).text(translatedI18nObj.labelAbstract);
					break;
				case options.id + "_wmckeywords" :
					$(this).text(translatedI18nObj.labelKeywords);
					break;
			}
		});
		var $legend = $form.next().children("legend");
		$legend.text(translatedI18nObj.labelCategories);
	};

	this.getExistingWmcData = function (callback) {
		// get WMC data from server
		var req = new Mapbender.Ajax.Request({
			url: "../php/mod_loadwmc_server.php",
			method: "getWmc",
			callback: function(obj, result, message){
				if (!result) {
					new Mb_exception(message);
					return;
				}
				if (typeof callback === "function") {
					callback(obj.wmc);
				}
			}
		});
		req.send();
	};

	var createSelectBoxForExistingWmcs = function (obj) {
		(function () {
			$select = $("#" + options.id + "_wmctype").empty();
			$select.removeAttr("disabled");
			var select = "<option value=''>" + translatedI18nObj.labelNewWmc + "</option>";
			if (typeof obj === "object" && obj.length) {
				for (var i = 0; i < obj.length; i++) {
					var wmc = obj[i];
					if (wmc.disabled) {
						continue;
					}

					select += "<option value='" + wmc.id + "'>" +
						wmc.title + "</option>";
				}
			}

			$select.html(select).change(function () {
				//
				// reset fields if new wmc is saved
				//
				if (this.value === "") {
					$("#" + options.id + "_wmc_id").val("");
					$("#" + options.id + "_wmcname").val("");
					$("#" + options.id + "_wmcabstract").val("");
					$("#" + options.id + "_wmckeywords").val("");
					$("input[id^='" + options.id + "_wmcIsoTopicCategory_']").removeAttr("checked");
					return false;
				}
				//
				// set fields according to wmc
				//
				for (var i = 0; i < obj.length; i++) {
					var wmc = obj[i];
					if (wmc.id === this.value) {
						$("#" + options.id + "_wmc_id").val(wmc.id);
						$("#" + options.id + "_wmcname").val(wmc.title);
						$("#" + options.id + "_wmcabstract").val(wmc.abstract);
						$("#" + options.id + "_wmckeywords").val(wmc.keywords.join(","));
						$("input[id^='" + options.id + "_wmcIsoTopicCategory_']").removeAttr("checked");
						for (var j = 0; j < wmc.categories.length; j++) {
							var cat = wmc.categories[j];
							$("#" + options.id + "_wmcIsoTopicCategory_" + cat).attr("checked", "checked");
						}
						return false;
					}
				}
			});
		})();
	};

	var getButtons = function () {
		var buttonObj = {};
		buttonObj[translatedI18nObj.labelSave] = function() {
			var isoTopicCat = {};
			var regExp = new RegExp(options.id + "_");
			$(".wmcIsoTopicCategory:checkbox").each(function(){
				if(!!$(this).attr('checked')) {
					isoTopicCat[$(this).attr('id').replace(regExp, "")] = true;
				}
			});
			var attributes = {};
			attributes.wmc_id 	= $("#" + options.id + "_wmc_id").val();
			attributes.title 	= $("#" + options.id + "_wmcname").val();
			attributes.abstract = $("#" + options.id + "_wmcabstract").val();
			attributes.keywords = $("#" + options.id + "_wmckeywords").val();
			attributes.isoTopicCat = isoTopicCat;

			if(attributes.title != ""){
				if(!attributes.title.match(/^[a-zA-Z0-9äöüÄÖÜß_\- \"]+$/)){
                               		alert("Allowed characters for title are: A-Z, a-z, 0-9, -, _, äA, öO, üU, ß, \"");
                               		return;
                        	}
			}else{
				alert("Please fill out title!");
				return;
			}

			if(attributes.abstract != ""){
				if(!attributes.abstract.match(/^[a-zA-Z0-9äöüÄÖÜß_\- \"§&()?:,=./\s\\r\\n\\t]+$/)){
                               		alert("Allowed characters for abstract are: A-Z, a-z, 0-9, -, _, äA, öO, üU, ß, \", , , =, /, &, §, (), : , .");
                               		return;
				}
			}


			if(attributes.keywords != ""){
				if(!attributes.keywords.match(/^[a-zA-Z0-9äöüÄÖÜß_\-, \"]+$/)){
                               		alert("Allowed characters for keywords are: A-Z, a-z, 0-9, -, _,, äA, öO, üU, ß, \"");
                               		return;
				}
			}

			if (!!attributes.title) {
				sendMapDataToServer(attributes, 0, (function(result, status, message) {
					alert(message);
					$("#" + options.id + "_saveWMCForm form")[0].reset();
				}));
			}
			else{
				//perfom validation
			}
			$(this).dialog('close');
		};
		buttonObj[translatedI18nObj.labelCancel] = function() {
			$("#" + options.id + "_saveWMCForm form")[0].reset();
			$(this).dialog('close');
		};
		return buttonObj;
	};

	var mod_savewmc = function () {
		that.getExistingWmcData(function (obj) {
			createSelectBoxForExistingWmcs(obj);
			$("#" + options.id + "_saveWMCForm").dialog('open');
		});
	};

	//
	// constructor
	//

	$this.click(function () {
		$('#savewmc').toggleClass('myOnClass');
		$('#toolsContainer').hide() && $('a.toggleToolsContainer').removeClass('activeToggle');
		$('#tree2Container').hide() && $('a.toggleLayerTree').removeClass('activeToggle');
		mod_savewmc();
	}).mouseover(function () {
		if (options.src) {
			this.src = options.src.replace(/_off/, "_over");
		}
		$('#savewmc').addClass('myOverClass');
	}).mouseout(function () {
		if (options.src) {
			this.src = options.src;
		}
		$('#savewmc').removeClass('myOverClass');
	});
	Mapbender.events.afterInit.register(function(){
		//check if wmc should be saved into session
		if (saveInSession === 1) {
			//if onbeforeunload should be supported use it!
			if (browserCompatibilityMode === 0) {
				//options.$target.each(function () {
				var supportsOnbeforeunload = true; //TODO: The problem is the time for a job on onunload - there is not much. Therefor only simple things work - not saving a huge amount of data thru ajax
				/*for (var prop in window) {
    					if (prop === 'onbeforeunload') {
    						supportsOnbeforeunload = true;
    						break;
    					}
				}*/
				//alert("Support of onBeforeUnload: "+supportsOnbeforeunload+" Browser:"+navigator.userAgent);
				if (supportsOnbeforeunload) {
					//$(window).bind('beforeunload', function(){//after hint in web http://stackoverflow.com/questions/4376596/jquery-unload-or-beforeunload
					window.onbeforeunload = function(e){//after hint in web http://stackoverflow.com/questions/4376596/jquery-unload-or-beforeunload
						var e = e || window.event;
						//alert("Write WMC to session - onBeforeUnload!");
						if (!window.resetSession) {
							that.save({
								session : true
							});
							//pausecomp(1000); //hope that fix the synro problem
							//alert("onbeforeunload: no reset of session stored wmc requested - wmc will be saved into session!");
							alert(translatedI18nObj.labelSaveInSession);
						}
												// For IE and Firefox
  						//if (e) {
    						//	e.returnValue = 'Any string';
  						//}
  						// For Safari
  						//return 'Any string';
					};
				} else {
					$(window).bind('unload', function(){
						//alert("Write WMC to session - onUnload!");
						if (!window.resetSession) {
							that.save({
								session : true
							});
							//pausecomp(1000);
							//alert("onunload: no reset of session stored wmc requested - wmc will be saved into session!");
							alert(translatedI18nObj.labelSaveInSession);
						}
					});
				}
		} else {
			//alert("Your are in a browser compatibility mode - this make the application slow!");
			// hack to attach the eventhandler after all initial wms have been added to the map
			setTimeout(function(){
				options.$target.each(function () {
					$(this).mapbender().events.afterMapRequest.register(function () {
						if (!window.resetSession) {
							//if (!prohibitSaveWmc) {
								that.save({
									session : true
								});
							//}
							//alert('afterMapRequest Saving!');
						}
					});
				});
			},3500);
		}
	}
	});

	Mapbender.events.init.register(function () {
		var t = translatedI18nObj;
		var savewmcHtml = '<div id="' + options.id + '_saveWMCForm" ' +
			'title="' + translatedI18nObj.title + '">' +
			'<style> fieldset label { display: block; }</style>' +
			wmcSaveFormHtml +
			'</div>';

		var $saveWmcDialog = $(savewmcHtml).dialog({
			bgiframe: true,
			autoOpen: false,
			height: 400,
			width: 400,
			modal: false,
			beforeclose: function (event, ui) {
				try {
					$saveWmcDialog.parent().effect("transfer", {
						to: $this
					}, 300);
				}
				catch (exc) {
					new Mb_warning("jq_ui_effect is missing.");
				}
			},
			close: function() {$('#savewmc').toggleClass('myOnClass')},
			buttons: getButtons()
		});
		// set uuid
	});

//	Mapbender.events.localize.register(function () {
if(Mapbender.modules.i18n){
		Mapbender.modules.i18n.queue(options.id, originalI18nObj, function (translatedObj) {
			if (typeof translatedObj !== "object") {
				return;
			}
			translatedI18nObj = translatedObj;
//			try {
//				localize();
//			}
//			catch (exc) {
//				new Mapbender.Warning("Error when translating: " . exc.message);
//			}
		});
}
//	});
};

$this.mapbender(new SaveWmcApi());
