var $metadataLayerPreview = $(this);
$metadataLayerPreview.css('border', 'white');

var MetadataLayerPreviewApi = function(o) {

    var that = this;
    options = o || {};
    options.map = options.map || "";
    options.buttons = options.buttons || [];

    this.wmsId = null;

    var changeEpsgAndZoomToExtent = function(layer, map) {
        layer.gui_layer_visible = 1;
        var len = layer.layer_epsg.length;
        if (len === 0) {
            // could not zoom to extent
            return;
        }
        for (var j = 0; j < len; j++) {
            var currentEpsg = layer.layer_epsg[j];
            if (currentEpsg.epsg === map.epsg) {
                var newExtent = new Mapbender.Extent(
                    currentEpsg.minx,
                    currentEpsg.miny,
                    currentEpsg.maxx,
                    currentEpsg.maxy
                );
                map.calculateExtent(newExtent);
                return;
            }
        }
        // current SRS is not supported, switch to a supported SRS
        var newEpsg = layer.layer_epsg[0];
        var newExtent = new Mapbender.Extent(
            newEpsg.minx,
            newEpsg.miny,
            newEpsg.maxx,
            newEpsg.maxy
        );
        map.setSrs({
            srs: newEpsg.epsg,
            extent: newExtent
        });
    };

    // enable layer,. disabling all others
    this.layer = function(layer) {
        var layername = layer.layer_name;

        if (layername === undefined) {
            return 'currentlayername';
        }
        var map = $('#' + options.map).mapbender();
        var wms = map.wms[map.wms.length - 1];
        for (var i in wms.objLayer) {
            if (wms.objLayer[i].layer_name == layername) {
                changeEpsgAndZoomToExtent(wms.objLayer[i], map);
            } else {
                wms.objLayer[i].gui_layer_visible = 0;
            }
        }
        map.restateLayers(wms.wms_id);
        map.setMapRequest();
    };

    // set wms, throwing out all others
    this.wms = function(wmsid) {
        if (wmsid === undefined) {
            return 'currentwmsid';
        }

        $('#' + options.map).each(function() {
            var map = $(this).mapbender();
            if (!map) {
                return;
            }
            var wms = map.wms;
            for (var i = wms.length - 1; i > 0; i--) {
                delete wms[i];
                wms.length--;
            }
            mod_addWMSById_ajax('', wmsid, {
                zoomToExtent: 1
            });
        });
    };

    this.init = function(obj) {

        if (this.init.done !== true) {
            var $map = $('#' + options.map);
            $map.css('position', 'relative');
            $map.css('display', '');
            $map.css('top', '');
            $map.css('left', '');
            $('img', $map).css({
                'height': $map.css('height'),
                'width': $map.css('width')
            });

            var $target = $('#map');
            $target.append($map);

            $(options.toolbarUpper).each(function() {
                $("#" + this).css({
                    "position": "relative",
                    "display": "",
                    "top": "",
                    "left": ""
                }).appendTo($("#toolbar_upper"));
            });
            $(options.toolbarLower).each(function() {
                $("#" + this).css({
                    "position": "relative",
                    "display": "",
                    "top": "",
                    "left": ""
                }).appendTo($("#toolbar_lower"));
            });
            this.init.done = true;
        }

        if (typeof parseInt(obj, 10) == 'number') {
            that.wms(obj);
        }

    };
};

$metadataLayerPreview.mapbender(new MetadataLayerPreviewApi(options));

//console.log($metadataLayerPreview);


// upload an image to the preview fieldset
// submit the preview form
//$('#mb_md_layer').find('#previewImgForm').bind('submit', function(event) {
//    event.preventDefault();
//console.log('test');
//});


$metadataLayerPreview.bind('uploadFormReady', function(e, form) {
    var type;
    
    if ($('input[id="layer_id"]').val() == undefined) {
        type = 'wmc';
    } else {
        type = 'wms';
    }
     
    $('#previewUploadButton').bind('click', function (e) {
        if (type === 'wmc' && $('input[id="wmc_id"]').val() === "") {
            alert('Please choose a wmc before uploading an image');
        }
        else if (type === 'wms' && $('input[id="layer_id"]').val() === "") {
            alert('Please choose a layer before uploading an image');
        }
        else if ($('#previewImgForm input[type=file]').val() !== "") {
            fileUpload(form, '../php/mb_metadata_uploadLayerPreview.php', type, 'upload');
        }
    });
    
    $('#previewDeleteButton').bind('click', function () {
        fileUpload(form, '../php/mb_metadata_uploadLayerPreview.php', type, 'delete');
    });
    
    $('#previewReloadButton').bind('click', function () {
        fileUpload(form, '../php/mb_metadata_uploadLayerPreview.php', type, 'getImage');
    });
    
    $("iframe[name=upload_iframe]").bind("load", function () {
        var iframe = this;
        var body;
        if (iframe.contentDocument) {
            body = iframe.contentDocument.body;
        } else if (iframe.contentWindow) {
            body = iframe.contentWindow.document.body;
        } else if (iframe.document) {
            body = iframe.document.body;
        }
        if ($(body).find(".defaultPreview").length === 0) {
            $("#previewImgUpload .hasPreviewImage").css('display','inline-block');
        } else {
            $("#previewImgUpload .hasPreviewImage").css('display','none');
        }
        
        form.reset();
    });
    
});

$metadataLayerPreview.bind('fileChange', function(e, fileInput) {
    $(fileInput).bind('change', function() {
        var fr = new FileReader();
        fr.onload = function() { // file is loaded

            if (!fileInput.files[0].name.match(/\.(jpg|jpeg|png|gif)$/)) {
                $(fileInput).val("");
                alert('File is not an image. Please choose an image!');
                return;
            }
            var img = new Image();
            img.onload = function() {
                // check the file format
                // if ((img.width > 200 && img.height > 200) || (img.width < 200 && img.height < 200)) {
                if ((img.width != 200 || img.height != 200)) {
                    alert('Choosed image size doesn\'t fit. The image will be scaled to 200px * 200px.');
                    // fileInput.files[0] = img;
                } else if ($('#previewImgForm input[type=file]')[0].files[0].name.split('.').pop() == 'bmp') {

                    alert('Bitmaps are not supported. Please choose a valid filetype (jpg,png,gif)');
                }

            };

            img.src = fr.result; // is the data URL because called with readAsDataURL
        };
    });
});

function fileUpload(form, action_url, type, action) {

    $("#previewAction").val(action);
    $("#previewType").val(type);
    if (type === 'wms') {
        $('#previewSourceId').val($('input[id="layer_id"]').val());
    } else {
        $('#previewSourceId').val($('input[id="wmc_id"]').val());
    }
    
    form.submit();
}
