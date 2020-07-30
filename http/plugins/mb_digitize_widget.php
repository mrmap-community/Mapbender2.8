<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
?>
// <script language="JavaScript">
    var $digitize = $(this);

    var DigitizeApi = function(o) {

        var digitizeHtml = '<div title="<?php echo _mb("Sketch"); ?>">' +
            '<div  title="<?php echo _mb("Digitize new point"); ?>"class="digitize-image digitize-point"></div>' +
            '<div  title="<?php echo _mb("Digitize new line"); ?>"class="digitize-image digitize-line"></div>' +
            '<div  title="<?php echo _mb("Digitize new polygon"); ?>"class="digitize-image digitize-polygon"></div>' +
            '<div  title="<?php echo _mb("Attributes of collection"); ?>"class="digitize-image digitize-attributes editFeatureCollection"></div>' +
            '</div>';

        var editHtml = '<div title="<?php echo _mb("Edit feature"); ?>">' +
            '<div class="digitize-preview"></div>' +
            '<span>my polygon</span><br></br>' +
            '<div title="<?php echo _mb("Edit style"); ?>" class="digitize-image digitize-style"></div>' +
            '<div title="<?php echo _mb("Edit attributes"); ?>" class="digitize-image digitize-attributes"></div>' +
            // '<div class="digitize-image digitize-add"></div>' +
            '<div title="<?php echo _mb("Delete feature"); ?>" class="digitize-image digitize-remove"></div>' +
            '<div title="<?php echo _mb("Edit geometry"); ?>" class="digitize-image digitize-pencil"></div>' +
            '<fieldset class="fieldset-auto-width digitize-hidden">' +
            '<div title="<?php echo _mb("Move geometry"); ?>" class="digitize-image digitize-move"></div>' +
            '<div title="<?php echo _mb("Add vertex"); ?>" class="digitize-image digitize-add-vertex"></div>' +
            '<div title="<?php echo _mb("Move vertex"); ?>" class="digitize-image digitize-move-vertex"></div>' +
            '<div title="<?php echo _mb("Delete vertex"); ?>" class="digitize-image digitize-delete-vertex"></div></fieldset>' +
            '<div title="<?php echo _mb("Export geometry"); ?>" class="digitize-image digitize-export digitize-export-edit-dialog"></div>' +
            '</div>';
        var copyHtml = '<div title="<?php echo _mb("Copy feature"); ?>">' +
            '<div class="digitize-preview"></div>' +
            '<span>my polygon</span><br></br>' +
            '<div title="<?php echo _mb("Paste"); ?>" class="digitize-image digitize-paste bottom-right-20"></div>' +
            '</div>';
        var labelHtml = '<div title="<?php echo _mb("Feature\'s labelling"); ?>">' +
            '<div class="digitize-preview selfFeature"></div>' +
            '<span>my polygon</span><br></br><form>' +
            '<input id="label-0" type="radio" name="label" value=""><label for="label-0"><?php echo _mb("No labelling"); ?></label>' +
            '<fieldset class="labelling-common"><legend><?php echo _mb("Common attributes"); ?></legend></fieldset>' +
            '<fieldset class="labelling-exclusive"><legend><?php echo _mb("Exclusive attributes"); ?></legend></fieldset>' +
            '</form></div>';

        var labelCommonInput = function(num, val) {
            return '<div><input id="label-' + num + '" type="radio" name="label" value="' + val + '"><label for="label-' + num + '">' + val + '</label></div>';
        };
        var labelExclusiveInput = function(num, val) {
            return '<div><input id="label-' + num + '" type="radio" name="label" value="' + val + '"><label for="label-' + num + '">' + val + '</label><div class="featureList"><ul class="items"></ul></div></div>';
        };
        var labelExclusivePreview = function(id, title) {
            return '<li class="featureItem"><div id="' + id + '" class="digitize-preview"></div><span>' + title + '</span></li>';
        };
        var editAttributesHtml = '<div title="<?php echo _mb("Feature attributes"); ?>">' +
            '<div class="digitize-image digitize-style"></div>' +
            '<div class="digitize-preview"></div><br></br>' +
            // '<div class="attrAccordion">' +
            '<table><tr><td><?php echo _mb("Name"); ?></td><td><input type="text" name="name" value="<?php echo _mb("Name"); ?>"></input></td></tr>' +
            '<tr><td><?php echo _mb("Description"); ?></td><td><input type="text" name="description" value="<?php echo _mb("Description CDATA"); ?>"></input></td></tr>' +
            '</table><br></br>' +
            '<div title="<?php echo _mb("Add attribute"); ?>" class="digitize-image digitize-add"></div>' +
            '<div title="<?php echo _mb("Save object"); ?>" class="digitize-image digitize-save"></div>' +
            '</div>';

        var tableEditAttributesHtml = '<div title="<?php echo _mb("Feature attributes"); ?>">' +
            '<div title="<?php echo _mb("Edit style"); ?>" class="digitize-image digitize-style"></div>' +
            '<div class="digitize-preview"></div><br></br>' +
            '<div class="attrAccordion">' +
            '</div><br></br>' +
            '<div title="<?php echo _mb("Add attribute"); ?>" class="digitize-image digitize-add"></div>' +
            '<div title="<?php echo _mb("Save object"); ?>" class="digitize-image digitize-save"></div>' +
            '</div>';

        var editStyleHtml = '<div title="<?php echo _mb("Edit style"); ?>">' +
            '<div class="digitize-preview"></div>' +
            '<form id="digitize-marker-form"><label><input type="radio" name="marker-type" checked="checked" value="predefined"><?php echo _mb("Predefined"); ?></input></label>' +
            '<label><input type="radio" name="marker-type" value="custom"><?php echo _mb("Custom"); ?></input></label></form>' +
            '<table class="digitize-style-custom"><tr><td><?php echo _mb("Symbol"); ?>:</td><td><input type="text" name="marker-symbol" value=""></input></td></tr>' +
            '<tr><td><?php echo _mb("Symbol size"); ?>:</td><td><input type="text" name="marker-size" value=""></input></td></tr>' +
            '<tr><td><?php echo _mb("Line color"); ?>:</td><td><input type="text" name="stroke" value="#000000"></input></td></tr>' +
            '<tr><td><?php echo _mb("Line opacity"); ?>:</td><td><div class="opacity-slider" data-name="stroke-opacity"></div></td></tr>' +
            '<tr><td><?php echo _mb("Line width"); ?>:</td><td><input type="text" name="stroke-width" value="1"></input></td></tr>' +
            '<tr><td><?php echo _mb("Fill color"); ?>:</td><td><input type="text" name="fill" value="#ff0000"></input></td></tr>' +
            '<tr><td><?php echo _mb("Fill opacity"); ?>:</td><td><div class="opacity-slider" data-name="fill-opacity"></div></td></tr></table>' +
            '<table class="digitize-style-predefined"><tr><td><?php echo _mb("Symbol"); ?>:</td><td><input type="text" name="marker-symbol" value=""></input></td></tr>' +
            '<tr><td><?php echo _mb("Symbol size"); ?>:</td><td><select name="marker-size" value="medium"><option value="large"><?php echo _mb("large"); ?></option><option value="medium" selected="selected"><?php echo _mb("medium"); ?></option><option value="small"><?php echo _mb("small"); ?></option></select></td></tr>' +
            '<tr><td><?php echo _mb("Symbol color"); ?>:</td><td><input type="color" name="marker-color" value="#ffffff"></input></td></tr>' +
            '<tr><td><?php echo _mb("Line color"); ?>:</td><td><input type="color" name="stroke" value="#000000"></input></td></tr>' +
            '<tr><td><?php echo _mb("Line opacity"); ?>:</td><td><div class="opacity-slider" data-name="stroke-opacity"></div></td></tr>' +
            '<tr><td><?php echo _mb("Line width"); ?>:</td><td><input type="text" name="stroke-width" value="1"></input></td></tr>' +
            '<tr><td><?php echo _mb("Fill color"); ?>:</td><td><input type="color" name="fill" value="#ff0000"></input></td></tr>' +
            '<tr><td><?php echo _mb("Fill opacity"); ?>:</td><td><div class="opacity-slider" data-name="fill-opacity"></div></td></tr>' +
            '</table><br></br>' +
            '<div title="<?php echo _mb("Save styling"); ?>" class="digitize-image digitize-save"></div>' +
            '<button name="digitize-reset-style"><?php echo _mb("Reset"); ?></button>' +
            '</div>';

        var folderMenu = '<ul class="digitize-contextmenu">' +
            '<li><div class="digitize-image digitize-pencil"></div><?php echo _mb("Edit"); ?></li>' +
            '<li><div class="digitize-image digitize-zoomto"></div><?php echo _mb("Zoom to"); ?></li>' +
            '<li><div class="digitize-image digitize-add"></div><?php echo _mb("New"); ?></li>' +
            '<li><div class="digitize-image digitize-export"></div><?php echo _mb("Export"); ?></li>' +
            '<li><div class="digitize-image digitize-remove"></div><?php echo _mb("Delete"); ?></li>' +
            '<li><div class="digitize-image digitize-close"></div><?php echo _mb("Close"); ?></li>' +
            '</ul>';

        var geomMenu = '<ul class="digitize-contextmenu">' +
            '<li><div class="digitize-image digitize-pencil"></div><?php echo _mb("Edit"); ?></li>' +
            '<li><div class="digitize-image digitize-zoomto"></div><?php echo _mb("Zoom to"); ?></li>' +
            '<li><div class="digitize-image digitize-copy"></div><?php echo _mb("Kopieren"); ?></li>' +
            '<li><div class="digitize-image digitize-label"></div><?php echo _mb("Labelling"); ?></li>' +
            '<li><div class="digitize-image digitize-export"></div><?php echo _mb("Export"); ?></li>' +
            '<li><div class="digitize-image digitize-remove"></div><?php echo _mb("Delete"); ?></li>' +
            '<li><div class="digitize-image digitize-style"></div><?php echo _mb("Styling"); ?></li>' +
            '<li><div class="digitize-image digitize-close"></div><?php echo _mb("Close"); ?></li>' +
            '</ul>';

        var geomPartMenu = '<ul class="digitize-contextmenu">' +
            '<li><div class="digitize-image digitize-pencil"></div><?php echo _mb("Edit"); ?></li>' +
            '<li><div class="digitize-image digitize-zoomto"></div><?php echo _mb("Zoom to"); ?></li>' +
            '<li><div class="digitize-image digitize-export"></div><?php echo _mb("Export"); ?></li>' +
            '<li><div class="digitize-image digitize-remove"></div><?php echo _mb("Delete"); ?></li>' +
            '<li><div class="digitize-image digitize-close"></div><?php echo _mb("Close"); ?></li>' +
            '</ul>';

        var exportHtml = '<div id="export-dialog"><table><tbody>' +
            '<tr><td>KML:</td><td><label class="export-format-kml exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink kml" ' +
            'outputFormat="kml"><img src="../img/gnome/document-save.png"/></td></tr>' +
            '<tr><td>GPX:</td><td><label class="export-format-gpx exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink gpx" ' +
            'outputFormat="gpx"><img src="../img/gnome/document-save.png"/></td></tr>' +
            '<tr><td>GeoJson:</td><td><label class="export-format-geojson exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink geojson" ' +
            'outputFormat="geojson"><img src="../img/gnome/document-save.png"/></td></tr>' +
            '</tbody></table></div>';


        var digitizeDialog,
            editDialog,
            copyDialog,
            labelDialog,
            attributesDialog,
            editStyleDialog,
            icons = null,
            button,
            that = this,
            inProgress = false,
            title = o.title,
            digitizingFor = '',
            editedFeature = null,
            status = 'none';

        var btnelem = $('body').append('<img id="kml-digitizer-pseudo" style="display: none;"></img>').find('#kml-digitizer-pseudo');

        var btn = new Mapbender.Button({
            domElement: btnelem[0],
            name: 'kml-digitizer-pseudo',
            go: $.noop,
            stop: function() {
                if ($('#mapframe1').data('mb_digitize')) {
                    $('#mapframe1').data('mb_digitize').isPaused = true;
                }
            }
        });

        $('body > img').bind('click', function() {
            if (!inProgress) {
                return;
            }
            var active = $(this)[0].status == 1 && $(this)[0] != btnelem;

            var dig = $('#mapframe1').data('mb_digitize');

            if (active && dig) {
                dig.isPaused = false;
            }
        });
        var create = function() {
            $.ajax({
                url: '../extensions/makiicons/selection.json',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    icons = data;
                    var kml = $('#mapframe1').data('kml');
                    kml.icons = icons;
                }
            });

            //
            // Initialise digitize dialog
            //
            digitizeDialog = $(digitizeHtml);
            digitizeDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80]
            }).bind("dialogclose", function() {
                button.stop();
                $(this).find('.digitize-image').unbind('click');
                that.destroy();
            });
            editDialog = $(editHtml);
            editDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80]
            }).bind("dialogclose", function() {
                button.stop();
                $(this).find('.digitize-image').unbind('click');
                that.destroy();
                $('#kmlTree li.kmltree-selected').removeClass('kmltree-selected');
                attributesDialog.dialog('close');
                editStyleDialog.dialog('close');
            });
            copyDialog = $(copyHtml);
            copyDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80]
            }).bind("dialogclose", function() {
                button.stop();
                that.destroy();
                $('#kmlTree li.kmltree-selected').removeClass('kmltree-selected');
            });
            labelDialog = $(labelHtml);
            labelDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80]
            }).bind("dialogclose", function() {
                button.stop();
                that.destroy();
                labelDialog.find('input[name="label"]').unbind('click', function() {});
                $('#kmlTree li.kmltree-selected').removeClass('kmltree-selected');
            });
            attributesDialog = $(editAttributesHtml);
            attributesDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80],
                width: 400
            }).bind("dialogclose", function() {
                button.stop();
                $(this).find('.digitize-image').unbind('click');
                $(attributesDialog).html(editAttributesHtml);
            });

            attributesDialog.parent().find('a > span.ui-icon-closethick').bind('click', function() {
                if (status == 'created-new') {
                    status = 'none';
                    $('#mapframe1').data('mb_digitize').destroy();
                }
            });

            editStyleDialog = $(editStyleHtml);
            editStyleDialog.dialog({
                autoOpen: false,
                position: [o.$target.offset().left + 20, o.$target.offset().top + 80],
                width: 400,
                height: 260
            }).bind('dialogclose', function() {
                button.stop();
                $(this).find('input').unbind('change');

            });

            editStyleDialog.find('div.opacity-slider').slider({
                min: 0,
                max: 100,
                step: 1,
                value: 100
            });

            //
            // Initialise button
            //
            button = new Mapbender.Button({
                domElement: $digitize.get(0),
                over: o.src.replace(/_off/, "_over"),
                on: o.src.replace(/_off/, "_on"),
                off: o.src,
                name: o.id,
                go: that.activate,
                stop: that.deactivate
            });

            $('li').live('click', function() {
                if ($(this).children('.digitize-close').length === 1) {
                    $(this).parent().menu('destroy').remove();
                }
            });

            $('#mapframe1').bind('kml:loaded', function(evt, item) {
                var kml = $('#mapframe1').data('kml');
                kml.exportItem = exportItem;
                var url = item.url;
                $('li[title="' + url + '"] > a').die('contextmenu').live('contextmenu', contextmenuLayer);
                $('li[title="' + url + '"] > ul > li').die('contextmenu').live('contextmenu', contextmenuObject)
                    .die('click').live('click', function(e) {
                        if ($(e.srcElement).is('button,input')) {
                            return;
                        }
                        var idx = $(this).attr('idx');
                        var kml = $('#mapframe1').data('kml');
                        var url = $(this).parent().parent().attr('title');
                        kml.zoomToFeature(url, idx);
                        editObject($(this), null)(e);
                    });
                $('li[title="' + url + '"] > .digitize-menu-arrow').die('click').live('click', contextmenuLayer);
                $('li[title="' + url + '"] > ul > li > .digitize-menu-arrow').die('click').live('click', contextmenuObject);
            });
        };

        var exportItem = function(data) {
            var dlg = $(exportHtml).dialog({
                width: 330,
                height: 220
            });
            $(dlg).find('.exportDataLink').bind('click', function() {
                var outputformat = $(this).attr('outputformat');
                var content;
                if (outputformat === 'kml') {
                    content = tokml(data, {
                        simplestyle: true
                    });
                } else if (outputformat === 'gpx') {
                    content = togpx(data, {
                        simplestyle: true
                    });
                } else if (outputformat === 'geojson') {
                    if (data.type != "Feature") {
                        content = JSON.stringify(data);
                    } else {
                        var newFeatureJson = $.extend(true, {}, data);
                        var jsonFeatureHeader = {
                            "type": "FeatureCollection",
                            "features": [newFeatureJson]
                        };
                        content = JSON.stringify(jsonFeatureHeader);
                    }

                } else {
                    alert('The output format "' + outputformat + '" is not supported.');
                    return false;
                }
                var ua = window.navigator.userAgent;
                var msie = ua.indexOf("MSIE ");
                if ((msie > 0 && parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))) === 10) || !!ua.match(/Trident.*rv\:11\./)) {
                    var blob = new Blob([content], {
                        type: 'application/' + outputformat
                    });
                    window.navigator.msSaveBlob(blob, 'data.' + outputformat);
                } else {
                    var link = document.createElement("a");
                    link.setAttribute('href', 'data:application/octet-stream;charset=utf-8,' + encodeURIComponent(content));
                    link.setAttribute('download', 'myfeatures.' + outputformat);
                    $(dlg).append(link);
                    link.click();
                    $(dlg).remove(link);
                }
                return false;
            });
        };

        var editStyle = function($link, menu) {
            var classPrefix = icons.preferences.fontPref.prefix,
                iconList = [],
                search = [];

            $.each(icons.icons, function(i, v) {
                iconList.push(classPrefix + v.properties.name);
            });

            editStyleDialog.find('.digitize-style-predefined input[name="marker-symbol"]').fontIconPicker({
                source: iconList,
                hasSearch: false,
                emptyIcon: false
            });

            return function() {
                editStyleDialog.dialog('open');
                var idx = $link.attr('idx');
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var feature = kml._kmls[url].data.features[idx];
                var isline = false;
                var ispoint = false;


                kml.zoomToFeature(url, idx);

                if (feature.geometry.type.match(/point/i)) {
                    ispoint = true;
                    editStyleDialog.find('input[name*="fill"],input[name*="stroke"],.opacity-slider').parent().parent().css('display', 'none');
                    editStyleDialog.find('input[name*="marker"]').parent().parent().css('display', 'table-row');
                    if (feature.properties['marker-type'] === 'custom') {
                        editStyleDialog.find('.digitize-style-custom,form').css('display', 'block');
                        editStyleDialog.find('.digitize-style-predefined').css('display', 'none');
                        editStyleDialog.find('input[value="custom"]').attr('checked', 'checked');
                    } else {
                        editStyleDialog.find('.digitize-style-predefined,form').css('display', 'block');
                        editStyleDialog.find('.digitize-style-custom').css('display', 'none');
                        editStyleDialog.find('input[value="predefined"]').attr('checked', 'checked');
                    }
                }
                if (feature.geometry.type.match(/line/i)) {
                    isline = true;
                    editStyleDialog.find('input[name*="fill"],input[name*="marker"],.opacity-slider[data-name="fill-opacity"]').parent().parent().css('display', 'none');
                    editStyleDialog.find('input[name*="stroke"],.opacity-slider[data-name="stroke-opacity"]').parent().parent().css('display', 'table-row');
                    editStyleDialog.find('.digitize-style-custom').css('display', 'block');
                    editStyleDialog.find('.digitize-style-predefined,form').css('display', 'none');
                }
                if (feature.geometry.type.match(/polygon/i)) {
                    editStyleDialog.find('input[name*="fill"],input[name*="stroke"],.opacity-slider').parent().parent().css('display', 'table-row');
                    editStyleDialog.find('input[name*="marker"]').parent().parent().css('display', 'none');
                    editStyleDialog.find('.digitize-style-custom').css('display', 'block');
                    editStyleDialog.find('.digitize-style-predefined,form').css('display', 'none');
                }

                $('input[value="predefined"]').bind('click', function() {
                    if ($(this).val() == 'predefined') {
                        var cls = $('.digitize-style-predefined .selected-icon i').attr('class');
                        $('.digitize-style-predefined input[name="marker-symbol"]').val(cls).change();
                    }
                });

                var preview = editStyleDialog.find('.digitize-preview');
                preview.html('');
                preview = preview.get(0);
                kml.renderPreview(feature, preview);
                $.each(feature.properties, function(k, v) {
                    if (editStyleDialog.find('input[name="' + k + '"]').is(':radio')) {
                        editStyleDialog.find('input[value="' + k + '"]').attr('checked', 'checked');
                        return;
                    }
                    editStyleDialog.find('input[name="' + k + '"],select[name="' + k + '"]').val(v);
                    if (k === 'stroke-opacity') {
                        editStyleDialog.find('.opacity-slider[data-name="stroke-opacity"]').slider('value', v * 100);
                    }
                    if (k === 'fill-opacity') {
                        editStyleDialog.find('.opacity-slider[data-name="fill-opacity"]').slider('value', v * 100);
                    }
                    if (k === 'marker-symbol' && feature.properties['marker-type'] === 'predefined') {
                        editStyleDialog.find('input[name="marker-symbol"]').val('icon-' + v + '-24');
                    }
                });
                var cls = $('.digitize-style-predefined .selected-icon i').attr('class');
                $('.digitize-style-predefined input[name="marker-symbol"]').val(cls);
                editStyleDialog.find('input').change();

                editStyleDialog.find('form input').bind('click', function() {
                    editStyleDialog.find('.digitize-style-' + $(this).val()).css('display', 'block').siblings('table').css('display', 'none');
                });

                editStyleDialog.find('button[name="digitize-reset-style"]').bind('click', function() {
                    if (ispoint) {
                        editStyleDialog.find('form').css('display', 'block');
                        editStyleDialog.find('.digitize-style-custom').css('display', 'none');
                        editStyleDialog.find('.digitize-style-predefined').css('display', 'block');
                        editStyleDialog.find('.digitize-style-custom input[name="marker-symbol"]').val('../img/marker/red.png');
                        editStyleDialog.find('.digitize-style-custom input[name="marker-size"]').val(20);
                        editStyleDialog.find('.digitize-style-predefined input[name="marker-symbol"]').val('icon-airfield-24');
                        $('.digitize-style-predefined .selected-icon i').attr('class', 'icon-airfield-24');
                        editStyleDialog.find('.digitize-style-predefined input[name="marker-size"]').val('medium');
                        editStyleDialog.find('.digitize-style-predefined input[name="marker-color"]').spectrum('set', 'white');
                        editStyleDialog.find('input').change();
                    }
                    editStyleDialog.find('input[name="stroke"]').spectrum('set', '#555555');
                    editStyleDialog.find('.opacity-slider').slider('value', 100);
                    editStyleDialog.find('input[name="stroke-width"]').val(1);
                    editStyleDialog.find('input[name="fill"]').spectrum('set', '#555555');
                });

                editStyleDialog.find('input,select').bind('change', function() {
                    if (isline && $(this).attr('name').match(/fill/)) {
                        return;
                    }

                    if (!ispoint && $(this).attr('name').match(/marker/)) {
                        return;
                    }

                    if ($(this).attr('name').match(/marker-type/) && !$(this).get(0).checked) {
                        return;
                    }

                    if ($(this).attr('name') === 'stroke-width') {
                        var val = $(this).val();
                        if (!(!isNaN(parseFloat(val)) && isFinite(val)) || $(this).val() <= 0) {
                            $(this).css('background-color', 'red');
                            $(this).val(feature.properties['stroke-width']);
                        } else {
                            $(this).css('background-color', '');
                        }
                    }

                    feature.properties[$(this).attr('name')] = $(this).val();

                    if ($(this).attr('name') === 'marker-symbol' && editStyleDialog.find('input[name="marker-type"]').val() == 'predefined') {
                        var m = $(this).val().match(/^icon-(.+)-24$/);
                        if (m) {
                            feature.properties['marker-symbol'] = m[1];
                        }
                    }

                    kml.render();
                    var preview = editStyleDialog.find('.digitize-preview').html('').get(0);
                    kml.renderPreview(feature, preview);
                    preview = editDialog.find('.digitize-preview').html('').get(0);
                    kml.renderPreview(feature, preview);
                    preview = attributesDialog.find('.digitize-preview').html('').get(0);
                    kml.renderPreview(feature, preview);
                });
                $('.opacity-slider').slider('option', 'change', function() {
                    if (isline && $(this).attr('data-name') === 'fill-opacity') {
                        return;
                    }
                    feature.properties[$(this).attr('data-name')] = $(this).slider('value') / 100;
                    kml.render();
                    var preview = editStyleDialog.find('.digitize-preview').html('').get(0);
                    kml.renderPreview(feature, preview);
                });
                editStyleDialog.find('.digitize-save').bind('click', function() {
                    editStyleDialog.dialog('close');
                    feature.properties.updated = new Date().toISOString();
                    kml.refresh(url);
                });
                editStyleDialog.find('input[name="fill"]').spectrum({
                    showInput: true,
                    showInitial: true
                });
                editStyleDialog.find('input[name="stroke"]').spectrum({
                    showInput: true,
                    showInitial: true
                });
                editStyleDialog.find('input[name="marker-color"]').spectrum({
                    showInput: true,
                    showInitial: true
                });
                editStyleDialog.find('input').change();
                if (menu)
                    menu.menu('destroy').remove();
            };
        };

        var contextmenuObject = function() {
            var $link = $(this);
            if ($link.attr('idx') === undefined) {
                $link = $link.parent();
            }
            var menu = $(geomMenu);
            if ($('.digitize-contextmenu').length != 0) {
                return;
            }
            $(document.body).append(menu);
            var pos = $link.offset();
            menu.css({
                    position: 'absolute',
                    top: pos.top,
                    left: pos.left,
                    width: "120px"
                }).menu()
                .children().addClass('ui-menu-item')
                .hover(function() {
                        $(this).addClass('ui-state-hover');
                    },
                    function() {
                        $(this).removeClass('ui-state-hover');
                    });
            menu.children('li:has(.digitize-zoomto)').bind('click', function() {
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                kml.zoomToFeature(url, $link.attr('idx'));
                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-pencil)').bind('click', editObject($link, menu));
            menu.children('li:has(.digitize-copy)').bind('click', copyObject($link, menu));
            menu.children('li:has(.digitize-label)').bind('click', labelObject($link, menu));
            menu.children('li:has(.digitize-export)').bind('click', function() {
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var idx = $link.attr('idx');
                var data = kml._kmls[url];
                exportItem(data.data.features[idx]);
                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-remove)').bind('click', function() {
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var ids = [];
                var i = 0;
                $.each($link.siblings(), function(k, v) {
                    ids.push($(v).attr('idx'));
                    $(v).attr('idx', i++);
                });
                $link.remove();
                kml.reorderFeatures(url, ids);

                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-style)').bind('click', function() {
                editDialog.dialog('close');
                editStyle($link, menu)();
            });
            return false;
        };

        var editAttributes = function(feature, kml, url) {

            return function() {
                if ($(this).hasClass('editFeatureCollection')) {
                    var oldCollectionName;
                    if (feature.hasOwnProperty('@context')) {

                        oldCollectionName = feature['@context'].title;

                    } else {

                        oldCollectionName = feature.title;

                    }
                    var featureCollAttrDlg = $('<div id="featureCollAttrDlg"></div>').dialog({
                        title: "<?php echo _mb("Featurecollection attributes"); ?> ", // of "+ url,
                        width: 500,
                        position: {
                            my: "center",
                            at: "top",
                            of: window
                        },
                        close: function() {
                            $('#featureCollAttrDlg').dialog('destroy');
                            $('#featureCollAttrDlg').remove();
                        }
                    });

                    var featureCollectionContent = "<div class='digitize-image digitize-style'></div>" +
                        "<br><br>" +
                        "<div><table id='featureCollTbl'>" +
                        "</table></div>" +
                        "<br><br/>" +
                        "<div class='digitize-image digitize-add'></div>" +
                        "<div class='digitize-image digitize-save'></div>";
                    $('#featureCollAttrDlg').append(featureCollectionContent);
                    $.each(feature, function(index, val) {
                        if (index == 'uuid' || index == 'created' || index == 'updated') {
                            $('#featureCollTbl').append("<tr><td>" + index + "</td><td><input style='width:230px;' type='text' name='" + index + "' value='" + val + "' disabled /></td></tr>");
                        } else {
                            if (index == "features" || index == "type") {
                                return;
                            };
                            $('#featureCollTbl').append("<tr><td>" + index + "</td><td><input  style='width:230px;' type='text' name='" + index + "' value='" + val + "'/></td></tr>");
                        }
                    });
                    featureCollAttrDlg.find('.digitize-save').bind('click', function() {
                        featureCollAttrDlg.find('table input').each(function() {
                            var k = $(this).attr('name');
                            var v = $(this).val();
                            if (k) {
                                feature[k] = v;
                            }
                        });
                        feature.updated = new Date().toISOString();
                        // save the changed feature in a new object
                        if ($('#mapframe1').data('kml')._kmls[oldCollectionName].url != $('#mapframe1').data('kml')._kmls[oldCollectionName].data.title) {
                            $('#mapframe1').data('kml')._kmls[feature.title] = $('#mapframe1').data('kml')._kmls[oldCollectionName];
                            $('#mapframe1').data('kml')._kmls[feature.title].url = $('#mapframe1').data('kml')._kmls[feature.title].data.title;
                            kml.remove(url);
                            $("#kmlTree>li>ul>li[title='" + url + "']").remove();
                            url = $('#mapframe1').data('kml')._kmls[feature.title].url;
                            featureCollAttrDlg.dialog('close');
                            editDialog.dialog('close');
                            kml.refresh(url);
                        } else {
                            featureCollAttrDlg.dialog('close');
                            editDialog.dialog('close');
                            kml.refresh(url);
                        }
                    });

                    // add row
                    featureCollAttrDlg.find('.digitize-add').bind('click', function() {
                        var newRow = $('<tr><td><input type="text"></input></td><td><input class="newInputValue" name="" type="text"></input></td></tr>');
                        $(newRow).find('.newInputValue').bind('keyup', function() {
                            $(this).attr('name', $(this).val());
                        });
                        featureCollAttrDlg.find('table').append(newRow);
                        newRow.find('input').first().bind('change', function() {
                            newRow.find('input').last().attr('name', $(this).val());
                        });
                    });
                } else {
                    // instantiate the geometry objects from the defined schema in config
                    var featureCategoriesSchemaInstance = instantiate(options.featureCategoriesSchema);
                    // declarate the variables for later use
                    var schemaInstance;
                    var geomType;
                    //  differentiate between the geometry-type
                    switch (feature.geometry.type) {
                        case "Point":
                            schemaInstance = instantiate(options.pointAttributesSchema);
                            geomType = "Point"
                            break;
                        case "Polygon":
                            schemaInstance = instantiate(options.polygonAttributesSchema);
                            geomType = "Polygon"
                            break;
                        case "LineString":
                            schemaInstance = instantiate(options.polylineAttributesSchema);
                            geomType = "Polyline"
                            break;
                            //TODO: difference between polyline and linestring????
                        case "Polyline":
                            schemaInstance = instantiate(options.polylineAttributesSchema);
                            geomType = "Polyline"
                            break;
                    }
                    attributesDialog.dialog('open');
                    attributesDialog.find('*').unbind();
                    attributesDialog.html(tableEditAttributesHtml);
                    // create an object with the given categories from the categorySchema
                    var categories = {};
                    $.each(featureCategoriesSchemaInstance.categories, function(index, val) {
                        categories[index] = '<h3 style="text-align:center;"><?php echo _mb("'+val+'"); ?></h3><div><table class="ftr-data-tbl ' + val + ' ">';
                    });
                    // create div-element for the geometry properties
                    var geometryDiv = $('<div class= "geometry-div"></div>');
                    // map each property to one of the given categories
                    $.each(feature.properties, function(k, v) {
                        mapFeature(k, v, categories, schemaInstance, geomType, geometryDiv);
                    });
                    // append the before created html-elements to the accordion-menu
                    $.each(featureCategoriesSchemaInstance.categories, function(index, val) {
                        categories[index] += '</table></div>';
                        $('.attrAccordion').append(categories[index]);
                    });
                    // instantiate the accordion
                    $('.attrAccordion').accordion({
                        collapsible: false
                    });
                    // prepend the geometryDiv to the attribute dialog
                    attributesDialog.prepend(geometryDiv);

                    attributesDialog.find('.digitize-add').bind('click', function() {
                        var newRow = $('<tr><td><input style="width:81px" type="text"></input></td><td><input style="width:100px" type="text"></input></td></tr>');
                        attributesDialog.find('.ftr-data-tbl.Custom-Data').append(newRow);
                        newRow.find('input').first().bind('change', function() {
                            newRow.find('input').last().attr('name', $(this).val());
                        });
                    });

                    var $link = $('li[title="' + url + '"] > ul > li');

                    attributesDialog.find('.digitize-style').bind('click', editStyle($link, null));
                    attributesDialog.find('.digitize-save').bind('click', function() { //@TODO: save update to the matching feature collection
                        attributesDialog.find('table input').each(function() {
                            var k = $(this).attr('name');
                            var v = $(this).val();
                            if (k) {
                                feature.properties[k] = v;
                            }
                        });
                        feature.properties.updated = new Date().toISOString();
                        //get parent and change updated
                        if ($('#mapframe1').data('kml')._kmls[url].data.hasOwnProperty('updated')) {
                            $('#mapframe1').data('kml')._kmls[url].data.updated = new Date().toISOString();
                        }

                        attributesDialog.dialog('close');
                        editDialog.dialog('close');
                        kml.refresh(url);
                    });
                    // remove custom attributes
                    attributesDialog.find('.removeCustomFeatAttr').bind('click', function() {
                        var attrName = $(this).parent().parent().children(':first').html();
                        delete feature.properties[attrName];
                        $(this).parent().parent().remove();
                    });

                    var preview = attributesDialog.find('.digitize-preview').html('').get(0);
                    kml.renderPreview(feature, preview);
                }
            };
        };

        var editObject = function($link, menu) {
            return function(e) {
                editDialog.find('*').unbind();
                if ($link.hasClass('kmltree-selected')) {
                    editDialog.dialog('close');
                    return;
                }
                editStyleDialog.dialog('close');
                attributesDialog.dialog('close');
                $link.addClass('kmltree-selected').siblings().removeClass('kmltree-selected');
                var idx = $link.attr('idx');
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var feature = kml._kmls[url].data.features[idx];
                editDialog.find('span').text(feature.properties.name);
                editDialog.dialog('open');
                editDialog.find('.digitize-attributes').bind('click', editAttributes(feature, kml, url));
                editDialog.find('.digitize-export').bind('click', function() {
                    exportItem(feature);
                });
                editDialog.find('.digitize-pencil').bind('click', function() {
                    $(this).next().toggleClass('digitize-hidden');
                });

                editDialog.find('.digitize-remove').bind('click', function() {
                    if (confirm('<?php echo _mb("Do you really want to delete this feature? If you have no other copy all information will be lost. There is no backup option!"); ?>')) {
                        var kml = $('#mapframe1').data('kml');
                        var url = $link.parent().parent().attr('title');
                        var ids = [];
                        var i = 0;
                        $.each($link.siblings(), function(k, v) {
                            ids.push($(v).attr('idx'));
                            $(v).attr('idx', i++);
                        });
                        $link.remove();
                        kml.reorderFeatures(url, ids);
                        editDialog.dialog('close');
                    }
                });
                status = 'edit-' + feature.geometry.type.toLowerCase();
                if (status === 'edit-linestring') {
                    // TODO consolidate this
                    status = 'edit-line';
                }
                digitizingFor = url;
                editedFeature = feature;
                that.activate();

                editDialog.parent().find('a > span.ui-icon-closethick').bind('click', function() {
                    o.$target.mb_digitize('modeOff');
                });
                editDialog.find('.digitize-move').bind('click', function() {
                    o.$target.mb_digitize('moveMode');
                    $(this).addClass('active').siblings().removeClass('active');
                });
                editDialog.find('.digitize-style').bind('click', editStyle($link, menu));
                var point = false;
                if (feature.geometry.type.match(/point/i)) {
                    point = true;
                }
                editDialog.find('.digitize-add-vertex,.digitize-move-vertex,.digitize-delete-vertex').css('display', point ? 'none' : '');
                editDialog.find('.digitize-add-vertex').bind('click', function() {
                    o.$target.mb_digitize('addVertexMode');
                    $(this).addClass('active').siblings().removeClass('active');
                });
                editDialog.find('.digitize-move-vertex').bind('click', function() {
                    o.$target.mb_digitize('moveVertexMode');
                    $(this).addClass('active').siblings().removeClass('active');
                });
                editDialog.find('.digitize-delete-vertex').bind('click', function() {
                    o.$target.mb_digitize('deleteVertexMode');
                    $(this).addClass('active').siblings().removeClass('active');
                });
                var preview = editDialog.find('.digitize-preview').html('').get(0);
                kml.renderPreview(feature, preview);

                if (menu)
                    menu.menu('destroy').remove();
            };
        };

        var copyObject = function($link, menu) {
            return function(e) {
                copyDialog.find('*').unbind();
                if ($link.hasClass('kmltree-selected')) {
                    copyDialog.dialog('close');
                    return;
                }
                copyDialog.dialog('close');
                $link.addClass('kmltree-selected').siblings().removeClass('kmltree-selected');
                var idx = $link.attr('idx');
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var feature = kml._kmls[url].data.features[idx];

                copyDialog.find('span').text(feature.properties.name);
                copyDialog.dialog('open');

                copyDialog.find('.digitize-paste').bind('click', function() {
                    var extent = $('#mapframe1').mapbender().extent;
                    var mapcenter = Proj4js.transform(kml.targetProj, kml.wgs84, extent.center);
                    var copied = kml.copyFeature(feature, mapcenter);
                    var copied_idx = kml.addFeature(url, kml.translateFeature(copied, mapcenter));
                    kml.zoomToFeature(url, copied_idx);
                    copyDialog.dialog('close');
                    $link.removeClass('kmltree-selected');
                    $('#kmlTree li li[title="' + url + '"] [idx="' + copied_idx + '"]').click();
                });

                var preview = copyDialog.find('.digitize-preview').html('').get(0);
                kml.renderPreview(feature, preview);
                if (menu)
                    menu.menu('destroy').remove();
            };
        };

        var labelObject = function($link, menu) {
            return function(e) {
                labelDialog.find('*').unbind();
                if ($link.hasClass('kmltree-selected')) {
                    labelDialog.dialog('close');
                    return;
                }
                labelDialog.dialog('close');
                $link.addClass('kmltree-selected').siblings().removeClass('kmltree-selected');
                labelDialog.find('.labelling-common >div').remove();
                labelDialog.find('.labelling-exclusive >div').remove();
                var idx = $link.attr('idx');
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().parent().attr('title');
                var feature = kml._kmls[url].data.features[idx];
                var preview = labelDialog.find('.digitize-preview.selfFeature').html('').get(0);
                labelDialog.find('span').text(feature.properties.name);
                kml.renderPreview(feature, preview);
                for (var k in feature.properties) {
                    if (k.toLowerCase().indexOf('stroke') !== 0 && k.toLowerCase().indexOf('fill') !== 0) {
                        var features = [];
                        var labels = [];
                        var k_not = false;
                        for (var i = 0; i < kml._kmls[url].data.features.length; i++) {
                            if (i !== parseInt(idx)) {
                                var fi = kml._kmls[url].data.features[i];
                                if (fi.properties[k]) {
                                    features.push(fi);
                                } else {
                                    k_not = true;
                                }
                            }
                        }
                        var $exclDiv = $(labelDialog.find('.labelling-exclusive').get(0));
                        var num = labelDialog.find('input[name="label"]').length;
                        if (k_not) { // add axclusive
                            var $exclInput = $(labelExclusiveInput(num, k));
                            $exclDiv.append($exclInput);
                            var $items = $exclInput.find('.featureList .items');
                            for (var i = 0; i < features.length; i++) {
                                var a = labelExclusivePreview(k + i, features[i].properties.name);
                                var $item = $(labelExclusivePreview(k + i, features[i].properties.name));
                                $items.append($item);
                                kml.renderPreview(features[i], $item.find('.digitize-preview').html('').get(0));
                            }
                            $('.featureList .items', $exclInput).hide();
                            $('.featureList', $exclInput).bind('mouseover', function(e) {
                                $(this).find('.items').show();
                            });

                            $('.featureList', $exclInput).bind('mouseout', function(e) {
                                $(this).find('.items').hide();
                            });
                            $exclDiv.show();
                        } else { // add common
                            var $commonDiv = $(labelDialog.find('.labelling-common').get(0));
                            $commonDiv.append($(labelCommonInput(num, k)));
                            $commonDiv.show();
                        }
                    }
                }
                labelDialog.find('input[name="label"][value="' + (feature.label ? feature.label : "") + '"]').attr("checked", true);
                labelDialog.dialog('open');
                labelDialog.find('input[name="label"]').bind('click', function() {
                    feature.label = $(this).val();
                    feature.properties.updated = new Date().toISOString();
                    if ($('#mapframe1').data('kml')._kmls[url].data.hasOwnProperty('updated')) {
                        $('#mapframe1').data('kml')._kmls[url].data.updated = new Date().toISOString();
                    }
                    kml.refresh(url);
                });
                if (menu)
                    menu.menu('destroy').remove();
            };
        };

        var contextmenuLayer = function() {

            var $link;
            if ($(this).get(0).tagName == 'A') {
                $link = $(this);
            } else {
                $link = $(this).parent().find('a')[0];
                $link = $($link);
            }
            var menu = $(folderMenu);
            // check if the dialog is already open
            if ($('.digitize-contextmenu').length != 0) {
                return;
            }
            $(document.body).append(menu);
            var pos = $link.offset();
            menu.css({
                    position: 'absolute',
                    top: pos.top,
                    left: pos.left,
                    width: "120px"
                }).menu()
                .children().addClass('ui-menu-item')
                .hover(function() {
                        $(this).addClass('ui-state-hover');
                    },
                    function() {
                        $(this).removeClass('ui-state-hover');
                    });
            menu.children('li:has(.digitize-zoomto)').bind('click', function() {
                $link.click();
                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-remove)').bind('click', function() {
                $('#mapframe1').data('kml').remove($link.parent().attr('title'));
                $link.parent().remove();
                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-export)').bind('click', function() {
                var kml = $('#mapframe1').data('kml');
                var url = $link.parent().attr('title');
                var data = kml._kmls[url];
                exportItem(data.data);
                menu.menu('destroy').remove();
            });
            menu.children('li:has(.digitize-add,.digitize-pencil)').bind('click', function() {
                editDialog.dialog('close');
                attributesDialog.dialog('close');
                editStyleDialog.dialog('close');

                digitizeDialog.dialog('open');

                digitizeDialog.find('.digitize-point').bind('click', function() {
                    status = 'new-point';
                    $(this).addClass('active').siblings().removeClass('active');
                    digitizingFor = $link.parent().attr('title');
                    that.activate();
                });
                digitizeDialog.find('.digitize-line').bind('click', function() {
                    status = 'new-line';
                    $(this).addClass('active').siblings().removeClass('active');
                    digitizingFor = $link.parent().attr('title');
                    that.activate();
                });
                digitizeDialog.find('.digitize-polygon').bind('click', function() {
                    status = 'new-polygon';
                    $(this).addClass('active').siblings().removeClass('active');
                    digitizingFor = $link.parent().attr('title');
                    that.activate();
                });
                // get the featureCollection data
                var url = $link.parent().attr('title');
                var featureCollection = $('#mapframe1').data('kml')._kmls[url].data;
                var kml = $('#mapframe1').data('kml');
                digitizeDialog.find('.digitize-attributes').bind('click', editAttributes(featureCollection, kml, url));

                digitizeDialog.find('.digitize-remove').bind('click', function() {
                    var kml = $('#mapframe1').data('kml');
                    var url = $link.parent().attr('title');
                    var feat = $('li[title="' + url + '"] li.kmltree-selected');
                    var idx = feat.attr('idx');
                    var ids = [];
                    var i = 0;
                    $.each(feat.siblings(), function(k, v) {
                        ids.push($(v).attr('idx'));
                        $(v).attr('idx', i++);
                    });
                    feat.remove();
                    kml.reorderFeatures(url, ids);
                });
                menu.menu('destroy').remove();
            });
            return false;
        };

        var finishDigitize = function() {
            inProgress = false;
            that.deactivate();
            status = 'created-new';

            var kml = $('#mapframe1').data('kml');
            if (kml) {
                var digit = o.$target.data('mb_digitize');
                var pts = digit._digitizePoints;

                attributesDialog.html(editAttributesHtml);
                attributesDialog.dialog('open');
                attributesDialog.find('.digitize-add').bind('click', function() {
                    var newRow = $('<tr><td><input type="text"></input></td><td><input type="text"></input></td></tr>');
                    attributesDialog.find('table').append(newRow);
                    newRow.find('input').first().bind('change', function() {
                        newRow.find('input').last().attr('name', $(this).val());
                    });
                });
                attributesDialog.find('.digitize-save').bind('click', function() {
                    var attributes = {};
                    attributesDialog.find('table input').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            attributes[name] = $(this).val();
                        }
                    });
                    kml.addGeometry(pts, digitizingFor, attributes);
                    attributesDialog.find('.digitize-save').unbind('click');
                    attributesDialog.dialog('close');
                });
            }
        };

        var reinitializeDigitize = function() {
            inProgress = false;
            that.deactivate();
            that.activate();
        };

        var featureModified = function() {

            var kml = $('#mapframe1').data('kml');
            var digit = o.$target.data('mb_digitize');
            var geom = new Geometry();
            var geomType = editedFeature.geometry.type.toLowerCase();
            var pts = $.extend(true, {}, digit._digitizePoints);
            $.ajax({
                url: '../php/transformPoint.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    point_pos: JSON.stringify(pts),
                    targetProj: JSON.stringify(kml.targetProj),
                    currentProj: JSON.stringify(kml.wgs84)
                },
                success: function(data) {

                    $.each(data.coordinates, function(index, val) {
                        if (!$.isPlainObject(pts[index].pos)) {
                            pts[index].pos = {
                                x: pts[index].pos.x,
                                y: pts[index].pos.y
                            };
                        }
                        pts[index].pos = {
                            x: val[0],
                            y: val[1]
                        };
                        var newPoint = {
                            x: val[0],
                            y: val[1]
                        };
                        geom.addPoint(newPoint);
                    });
                    geom.geomType = geomType;
                    var multi = new MultiGeometry(geomType);
                    multi.add(geom);


                    if (status === 'edit-point') {
                        editedFeature.geometry.coordinates = [pts[0].pos.x, pts[0].pos.y];
                    } else if (status === 'edit-line') {
                        editedFeature.geometry.coordinates = [];
                        $.each(pts, function(_, v) {
                            editedFeature.geometry.coordinates.push([v.pos.x, v.pos.y]);
                        });
                    } else if (status === 'edit-polygon') {
                        editedFeature.geometry.coordinates = [
                            []
                        ];
                        $.each(pts, function(_, v) {
                            editedFeature.geometry.coordinates[0].push([v.pos.x, v.pos.y]);
                        });
                    }
                    // cloning the geoms to calculate the area or length
                    var modifiedGeom = $.extend(true, {}, geom);
                    var modifiedData = new MultiGeometry(geomType);

                    if (geomType == 'polygon') {
                        modifiedGeom.addPoint(geom.list[0]); // add first point as last point
                        modifiedData.add(modifiedGeom);
                    } else {
                        modifiedData.add(modifiedGeom);
                    }
                    if (status != 'edit-point') {
                        // calculate current area (polygon) or length(linestring)
                        $.ajax({
                            url: '../php/mod_CalculateAreaAndLength.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                geom_type: geomType,
                                wkt_geom: modifiedData.toText()
                            },
                            success: function(data) {
                                if (geom.geomType == 'polygon') {
                                    //TODO: use attributes area and length - not complex strings!
                                    editedFeature.properties['area'] = data[0];
                                    editedFeature.properties['boundary-length'] = data[1];
                                } else {
                                    editedFeature.properties['track-length'] = data[0];
                                }
                            },
                            complete: function() {
                                editedFeature.properties.updated = new Date().toISOString();
                                kml.refresh(digitizingFor);
                            }
                        });
                    } else {
                        editedFeature.properties.updated = new Date().toISOString();
                        kml.refresh(digitizingFor);
                    }
                }
            });
        };

        this.activate = function() {
            $('#kml-digitizer-pseudo').click();
            var mode = status.match(/(new|edit)-.+/);
            if (!mode) {
                return;
            };

            mode = mode[1];

            if (mode === 'new') {
                editDialog.dialog('close');
                attributesDialog.dialog('close');
                editStyleDialog.dialog('close');
                if (o.$target.size() > 0) {
                    o.type = status.match(/new-(.+)/)[1];
                    o.editedFeature = null;
                    o.$target
                        .mb_digitize(o)
                        .mb_digitize('modeOff')
                        .mb_digitize('startDigitizing')
                        .unbind('mb_digitizelastpointadded')
                        .unbind('mb_digitizereinitialize')
                        .bind("mb_digitizelastpointadded", finishDigitize)
                        .bind("mb_digitizereinitialize", reinitializeDigitize);
                }
            } else {
                if (o.$target.size() > 0) {
                    o.type = status.match(/edit-(.+)/)[1];
                    o.editedFeature = editedFeature;
                    o.$target
                        .mb_digitize(o)
                        .mb_digitize('modeOff')
                        .unbind('mb_digitizefeaturemodified')
                        .bind('mb_digitizefeaturemodified', featureModified);
                }
            }
            if (!inProgress) {
                inProgress = true;
            }
        };

        this.destroy = function() {
            if (o.$target.size() > 0) {
                o.$target.mb_digitize("destroy")
                    .unbind("mb_digitizelastpointadded", finishDigitize)
                    .unbind("mb_digitizereinitialize", reinitializeDigitize);
            }
            if (digitizeDialog.dialog("isOpen")) {
                digitizeDialog.dialog("close");
            }
            //remove digitized x and y values from print dialog
            $('input[name="digitized_x_values"]').val("");
            $('input[name="digitized_y_values"]').val("");
        };

        this.deactivate = function() {
            if (o.$target.size() > 0) {
                o.$target.mb_digitize("deactivate");
            }
        };

        this.closeEditDialog = function() {
            editDialog.dialog('close');
        };

        create();

        /**
         * mapFeature appends every property to a category
         * @param  {[type]} propertyKey   [description]
         * @param  {[type]} propertyValue [description]
         * @param  {[type]} categories    [description]
         * @param  {[type]} mappingSchema [description]
         * @return {[type]}               [description]
         */
        var mapFeature = function(propertyKey, propertyValue, categories, mappingSchema, geomType, geometryDiv) {

            if (propertyKey != "area" && propertyKey != "boundary-length" && propertyKey != "track-length") {
                // check if property exists in mappingSchema otherwise put it in custom-data
                if (mappingSchema[geomType].hasOwnProperty(propertyKey)) {
                    // first need to know the matching category
                    var featureCategory = mappingSchema[geomType][propertyKey]["category"];
                    // if property is part of Fix-Data, disable the input
                    if (featureCategory == "Fix-Data") {
                        categories[featureCategory] += '<tr><td>' + propertyKey + '</td><td><input disabled type="text" name="' + propertyKey + '" value="' + propertyValue + '"></input></td></tr>';
                        // else allow to edit the input
                    } else {
                        categories[featureCategory] += '<tr><td>' + propertyKey + '</td><td><input type="text" name="' + propertyKey + '" value="' + propertyValue + '"></input></td></tr>';
                    }
                } else {
                    // put property in custom-data because it doesn't belong to any category
                    categories["Custom-Data"] += '<tr><td>' + propertyKey + '</td><td><input type="text" name="' + propertyKey + '" value="' + propertyValue + '"></input></td></tr>';
                }
            } else {
                var header;
                // differentiate between the geometry-properties
                switch (propertyKey) {
                    case "area":
                        header = "<?php echo _mb("Area [m²]"); ?>";
                        break;
                    case "boundary-length":
                        header = "<?php echo _mb("Boundary length [m]"); ?>";
                        break;
                    case "track-length":
                        header = "<?php echo _mb("Length [m]"); ?>";
                        break;
                }
                // append the matchin geometry-property to the geometryDiv
                geometryDiv.append('<div><p class = " geometry-p "name="' + propertyKey + '">' + header + ' : ' + propertyValue + '</p></div>');
            }
        }
    };

    $digitize.mapbender(new DigitizeApi(options));