/**
 * Package: mb_metadata_wmc_edit
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

var $metadataEdit = $(this);
var $metadataForm = $("<form>No WMC selected.</form>").appendTo($metadataEdit);

var MetadataEditApi = function(o) {
    var that = this;
    var validator;
    var formReady = false;
    var wmcId;

    this.events = {
        showOriginalMetadata: new Mapbender.Event(),
        submit: new Mapbender.Event()
    };

    this.valid = function() {
        if (validator && validator.numberOfInvalids() > 0) {
            $metadataForm.valid();
            return false;
        }
        return true;
    };

    this.serialize = function(callback) {
        $metadataForm.submit();
        var data = null;
        if (this.valid()) {
            data = {
                wmc: $metadataForm.easyform("serialize")
            };
        }
        if ($.isFunction(callback)) {
            callback(data);
        }
        return data !== null ? data.wmc : data;
    };

    // second optional parameter formData
    var fillForm = function(obj) {

        if (arguments.length >= 2) {
            $metadataForm.easyform("reset");
            $metadataForm.easyform("fill", arguments[1]);
            that.valid();
            return;
        }

        // get metadata from server
        var req = new Mapbender.Ajax.Request({
            url: "../plugins/mb_metadata_wmc_server.php",
            method: "getWmcMetadata",
            parameters: {
                "id": obj
            },
            callback: function(obj, result, message) {
                if (!result) {
                    return;
                }
                $metadataForm.easyform("reset");
                $metadataForm.easyform("fill", obj);
                that.valid();
                that.enableResetButton();
                that.enableShowMetadataLink();
                // check for preview
             	if (obj.hasPreview === true) {
             		$("#previewImgUpload .hasPreviewImage").css('display','inline-block');
             	} 
             	else {
             		$("#previewImgUpload .hasPreviewImage").css('display','none');
             	}
            }
        });
        req.send();
    };

    this.enableResetButton = function() {
        $("#resetIsoTopicCats").click(function() {
            $("#isoTopicCats option").removeAttr("selected");
        });
        $("#resetCustomCats").click(function() {
            $("#customCats option").removeAttr("selected");
        });
        $("#resetInspireCats").click(function() {
            $("#inspireCats option").removeAttr("selected");
        });
    }

    this.enableShowMetadataLink = function() {
        var linkHref = "../php/mod_showMetadata.php?languageCode=" + Mapbender.languageId + "&resource=wmc&id=" + wmcId;
        $("#wmc_showMetadata").attr("href", linkHref);
    }

    this.fill = function(obj) {
        $metadataForm.easyform("fill", obj);
    };

    var showOriginalMetadata = function() {
        that.events.showOriginalMetadata.trigger({
            data: {
                wmcId: wmcId,
                wmcData: $metadataForm.easyform("serialize")
            }
        });
    };

    this.init = function(obj) {
        wmcId = obj;

        var formData = arguments.length >= 2 ? arguments[1] : undefined;

        if (!formReady) {
            $metadataForm.empty().append($metadataEdit.children("div")).children().show();
            $metadataForm.find(".help-dialog").helpDialog();
            $metadataForm.find(".original-metadata-wmc").bind("click", function() {
                showOriginalMetadata();
            });
            validator = $metadataForm.validate({
                submitHandler: function() {
                    return false;
                }
            });
            if (formData !== undefined) {
                fillForm(obj, formData);
            } else {
                fillForm(obj);
            }
            formReady = true;
            return;
        }
        fillForm(obj);
    };

    Mapbender.events.localize.register(function() {
        if (!wmcId) {
            return;
        }
        that.valid();
        var formData = $metadataForm.easyform("serialize");
        formReady = false;
        that.init(wmcId, formData);
    });
    Mapbender.events.init.register(function() {
        that.valid();
        $("<div></div>").insertAfter($metadataForm).hide().load("../plugins/mb_metadata_wmc_edit.php", function() {


            // add tabs to preview fieldset
            $('#previewTabs').tabs();
            // add an event-handler to the form -> interaction with the form can be handled somewhere else --> mb_metadata_layerPreview.js
            $('#mb_md_wmc_preview').trigger('uploadFormReady', $('#previewImgForm'));
            $('#mb_md_wmc_preview').trigger('fileChange', $('#previewImgForm input[type=file]'));


        });
        var loadwmc = $("#loadwmc").mapbender();
        var map = $("#mapframe1").mapbender();
        var width = map.getWidth();
        var height = map.getHeight();
        loadwmc.events.loaded.register(function() {
            map.setDimensions(width, height);
        });
    });
};

$metadataEdit.mapbender(new MetadataEditApi(options));
