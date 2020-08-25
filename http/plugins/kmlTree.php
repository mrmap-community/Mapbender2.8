<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
?>
// <script language="JavaScript">
/**
 * Package: kmlTree
 *
 * Description:
 * Module to load KML temporary in a tree
 *
 * Files:
 *  - mapbender/http/plugins/kmlTree.php
 *  - mapebnder/lib/mb.ui.displayKmlFeatures.js
 *  - mapbender/http/css/kmltree.css
 *
 * SQL:
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, e_title, e_element,
 * > e_src, e_attributes, e_left, e_top, e_width, e_height, e_z_index, e_more_styles, e_content,
 * > e_closetag, e_js_file, e_mb_mod, e_target, e_requires, e_url) VALUES('<app_id>',
 * > 'kmlTree',2,1,'Displays KML on the map','KML','ul','','',1,1,200,200,NULL ,
 * > 'visibility:visible','','ul','../plugins/kmlTree.php','../../lib/mb.ui.displayKmlFeatures.js',
 * > 'mapframe1','jq_ui_widget','http://www.mapbender.org/Loadkml');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type) VALUES('gui', 'kmlTree',
 * > 'buffer', '100', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('gui', 'kmlTree', 'styles', '../css/kmltree.css', '' ,'file/css');
 *
 * Help:
 * http://www.mapbender.org/Loadkml
 *
 * Maintainer:
 * http://www.mapbender.org/User:Armin_Retterath
 *
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

if (typeof window.DOMParser === "undefined") {
    window.DOMParser = function() {};

    window.DOMParser.prototype.parseFromString = function(str, contentType) {
        if (typeof ActiveXObject !== 'undefined') {
            var xmldata = new ActiveXObject('MSXML.DomDocument');
            xmldata.async = false;
            xmldata.loadXML(str);
            return xmldata;
        } else if (typeof XMLHttpRequest !== 'undefined') {
            var xmldata = new XMLHttpRequest;

            if (!contentType) {
                contentType = 'application/xml';
            }

            xmldata.open('GET', 'data:' + contentType + ';charset=utf-8,' + encodeURIComponent(str), false);

            if (xmldata.overrideMimeType) {
                xmldata.overrideMimeType(contentType);
            }

            xmldata.send(null);
            return xmldata.responseXML;
        }
    };

}

var $kmlTree = $(this);
var KmlTree = function(o) {
    $kmlTree.children().remove();
    $kmlTree.addClass('kmlTree');
    var $KMLfolder = $('<li class="open kml"><ul></ul></li>');
    $kmlTree.append($KMLfolder);

    $addButton = $('<button title="<?php echo _mb('add geodata');?> "class="add" name="addkml" value="addkml"></button>');

    var selectButton = $('<img title="<?php echo _mb('select features');?> "id="toggle-select-features" src="../img/osgeo_graphics/geosilk/cursor.png"></img>');

    $addButton.click(function() {
        if ($('#mySpatialData').dialog('isOpen') === true) {


            return;

        } else {
            var dlg = $('<div id="mySpatialData"></div>').dialog({
                "title": "<?php echo _mb("My spatial data");?>",
                width: 800,
                height: 420,
                close: function() {
                    $('#kml-load-tabs').tabs('destroy');
                    $(this).html('').dialog('destroy');
                    // $('#mySpatialData').dialog('destroy');
                    $('#mySpatialData').remove();
                }
            });
            var dlgcontent = '<div id="kml-load-tabs">' + '<ul><li><a class="icon icon-wmc" href="#kml-from-wmc"><?php echo _mb("Stored data");?></a></li>' + '<li><a class="icon icon-local" href="#kml-from-upload"><?php echo _mb("Upload");?></a></li>' + '<li><a class="icon icon-remote" href="#kml-from-url"><?php echo _mb("External source");?></a></li>' + '<li><a class="icon icon-new" href="#kml-new"><?php echo _mb("New");?></a></li></ul>' + '<div id="kml-from-wmc"><?php echo _mb("WMC");?></div>' + '<div id="kml-from-upload">' + '<iframe name="kml-upload-target" style="width: 0; height: 0; border: 0px;"></iframe>' + '<form action="../php/uploadKml.php" method="post" enctype="multipart/form-data" target="kml-upload-target">' + '<input type="file" name="kml"></input>' + '<input type="submit" class="upload" value="Upload"></input><br>' + '<?php echo _mb("You can upload local KML, GPX and geoJSON files here. The filename should have the typical file extension (.kml, .gpx or .geojson) and the size is limited to 250kb of data.");?>' + '</div>' + '</form>' + '<div id="kml-from-url">URL: <input class="kmlurl" /><button class="add" name="add" value="add"></button><br>' + '<?php echo _mb("You can give an url to a datafile which is located somewhere in the www. Only KML, geoJSON and GPX files are supported. The files will be validated before they are loaded into the mapviewer.");?>' + '</div>' + '<div id="kml-new">' + '<label><?php echo _mb("Title");?>: <input type="text" name="kml-new-title"></input></label>' + '<button class="add-kml"></button><br>' + '<?php echo _mb("Define a self speaking name for your new dataset collection.");?>' + '</div>' + '</div>';
            $('#kml-load-tabs').remove();
            $(dlg).append(dlgcontent);
            $.ajax({
                type: 'get',
                url: '../php/mb_list_wmc_local_data.php',
		data: {
                	activateRegistratingGroupFilter : options.activateRegistratingGroupFilter
                      },
                success: function(data) {
                    var origData = $.extend(true, {}, data);
                    $.each(data, function(_, v) {
                        v[2] = new Date(v[2] * 1000);
                        if (v[3]) {
                            v[3] = '<img src="' + v[3] + '"></img>';
                        }
                        if (v[4]) {
                            v[4] = '<img class="publishIcon" src="../img/osgeo_graphics/check.png"></img><img class="exportImage" src="../img/osgeo_graphics/geosilk/link22.png"></img>';
                        } else {
                            v[4] = '<img class="publishIcon" src="../img/button_digitize/geomRemove.png"></img>';
                        }
                        v[5] = Math.round(v[5] / 1024) + 'kb';
                    });

                    // add dialog for open links
                    $('#kml-from-wmc').html('<table class="display"></table>').find('table').dataTable({
                            aaData: data,
                            aoColumns: [{
                                sTitle: '<?php echo _mb("ID");?>'
                            }, {
                                sTitle: '<?php echo _mb("Title");?>'
                            }, {
                                sTitle: '<?php echo _mb("last changed");?>'
                            }, {
                                sTitle: '<?php echo _mb("License");?>'
                            }, {
                                sTitle: '<?php echo _mb("public");?>'
                            }, {
                                sTitle: '<?php echo _mb("size");?>'
                            }, {
                                sTitle: '<?php echo _mb("owner");?>'
                            }]
                        })
                        .find('tr').bind('dblclick', function() {
                            var id = $($(this).find('td')[0]).text();
                            $.ajax({
                                type: 'post',
                                url: '../php/mb_load_local_data.php',
                                data: {
                                    id: id
                                },
                                success: function(data) {
                                    var kml = $('#mapframe1').data('kml');
                                    $.each(data, function(url, json) {
                                        kml.addLayer(url, json.data);
                                    });
                                    $(dlg).dialog('destroy');
                                }
                            });
                        }).end()
                        .find('.exportImage').bind('click', function(event) { // add click event to the link image
                            // stop event propagation beacause the following bind has to catch the click events on the 'tr'
                            event.stopPropagation();
                            var dialog = $('#dataExportDialog');
                            if (dialog.dialog('isOpen') === true) {

                                dialog.dialog('close');
                                dialog.remove();
                                $(this).trigger('click');

                            } else {
                                var title = $(this).parent().siblings().eq(1).html();
                                var wmc_serial_id = $(this).parent().siblings().eq(0).html();
                                var outputFormat;

                                var dataExportDlg = $('<div id="dataExportDialog"></div>').dialog({
                                    title: "<?php echo _mb("Export my data");?> " + title,
                                    width: 250,
                                    height: 212,
                                    position: {
                                        my: "center",
                                        at: "top",
                                        of: window
                                    },
                                    close: function() {
                                        dialog.dialog('destroy');
                                        dialog.remove();
                                    }
                                });
                                // var exportHtmlsdfdsf = '<div id="exportHtml">' + '<form>' + '<label class="export-format-kml">KML<input type="radio" name="export-format" value="kml" checked="checked"></input></label>' + '<label class="export-format-kml">KML<input type="radio" name="export-format" value="kml" checked="checked"></input></label>' + '<label class="export-format-kml">KML<input type="radio" name="export-format" value="kml" checked="checked"></input></label><br><br>' +
                                //     '<img src="../img/osgeo_graphics/geosilk/link22.png"/>' + '<label class="export-format-gpx">GPX</label>' + '<label class="export-format-geojson">geoJSON</label><br></br>' +
                                //     '<a download="myfeatures.kml" href="#" class="digitize-image digitize-export" style="float: left;"></a>' + '</form>' + '</div>';
                                var exportHtml = '<div id="exportHtml"><table><tbody>' +
                                    '<tr><td>KML:</td><td><label class="export-format-kml exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink kml" wmcId="' + wmc_serial_id + '"outputFormat="kml"><img src="../img/osgeo_graphics/geosilk/link22.png"/></td></tr>' +
                                    '<tr><td>GPX:</td><td><label class="export-format-gpx exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink gpx" wmcId="' + wmc_serial_id + '"outputFormat="gpx"><img src="../img/osgeo_graphics/geosilk/link22.png"/></td></tr>' +
                                    '<tr><td>GeoJson:</td><td><label class="export-format-geojson exportDatasetIcon" style="padding-top:11px;"></label></td><td class="exportDataLink geojson" wmcId="' + wmc_serial_id + '"outputFormat="geojson"><img src="../img/osgeo_graphics/geosilk/link22.png"/></td></tr>' +
                                    '</tbody></table></div><iframe id="export-DataCollection" style="border:0;height:0; width:0;"></iframe>';
                                // append the context
                                $(dataExportDlg).append(exportHtml);
                                //export the data
                                $('.exportDatasetIcon').bind('click', function(event) {

                                    var exportClass = $(this).attr('class').toString().split(' ')[0];
                                    //getting the outputformat
                                    switch (exportClass) {
                                        case 'export-format-kml':

                                            outputFormat = 'kml';
                                            break;
                                        case 'export-format-gpx':

                                            outputFormat = 'gpx';
                                            break;
                                        case 'export-format-geojson':

                                            outputFormat = 'geojson';
                                            break;
                                    }
                                    $('#export-DataCollection').attr("src", "../php/mod_GetPublishedData.php?wmc_id=" + wmc_serial_id + "&outputFormat=" + outputFormat);


                                });

                                $('.exportDataLink').bind('click', function(event) {
                                    var format = $(this).attr('outputFormat');
                                    var exportDataLinkDlg = $('<div id="exportDataLinkDlg"></div>').dialog({
                                        "title": "<?php echo _mb("Link to your Dataset");?>",
                                        width: 350,
                                        height: 80,
                                        close: function() {
                                            $('#exportDataLinkDlg').dialog('destroy');
                                            $('#exportDataLinkDlg').remove();

                                        }
                                    });

                                    var exportDataLinkContent = '<div><label for="exportLinkInput">Link: </label><input id="exportLinkInput" size="35" type="text" value="http://' + window.location.hostname + '/mapbender/php/mod_GetPublishedData.php?wmc_id=' + wmc_serial_id + '&outputFormat=' + format + '"></div>';
                                    $(exportDataLinkDlg).append(exportDataLinkContent);
                                });
                            }
                        })
                        .end()
                        .find('.publishIcon').bind('click', function() {
                            // var id = $($(this).find('td')[0]).text();
                            var id = $(this).parent().parent().children(':first').html();
                            var d;
                            $.each(origData, function(_, val) {
                                if (val[0] == id) {
                                    d = val;
                                }
                            });
                            var dlg = '<div title="Options ' + d[1] + '">' +
                                '<ul class="kmltree-metadata-list">' + '<li class="kmltree-metadata-delete"><img style="vertical-align: middle;" src="../img/button_digitize/geomRemove.png"></img><?php echo _mb("Delete datacollection");?></li>';

                            if (d[4]) {
                                dlg += '<li class="kmltree-metadata-unpublish"><img style="vertical-align: middle;" src="../img/gnome/emblem-unreadable.png"></img><?php echo _mb("Withdraw publication");?></li>';
                            } else {
                                dlg += '<li class="kmltree-metadata-publish"><img style="vertical-align: middle;" src="../img/gnome/share.png"></img><?php echo _mb("Publish datacollection");?></li>';
                            }

                            dlg += '</ul></div>';

                            dlg = $(dlg).appendTo('body');

                            $(dlg).dialog({
                                create: function() {
                                    $(dlg).find('li.kmltree-metadata-delete').bind('click', function() {
                                        if (confirm('<?php echo _mb("Really delete spatial data set?");?>')) {
                                            $(dlg).dialog('destroy');
                                            $.ajax({
                                                type: 'post',
                                                url: '../php/mb_delete_local_data.php',
                                                data: {
                                                    id: id
                                                },
                                                success: function(data) {
                                                    if (arguments[1] == 'success') {

                                                        alert('<?php echo _mb("Deleting local data was succesfull");?>');
                                                        $('#mySpatialData').dialog('destroy');
                                                        $('#mySpatialData').remove();
                                                        $($addButton).trigger('click');
                                                    } else {
                                                        alert('<?php echo _mb("Problem when deleting local data");?>');

                                                    }

                                                }
                                            });
                                        }
                                    });

                                    $(dlg).find('li.kmltree-metadata-unpublish').bind('click', function() {
                                        if (confirm('<?php echo _mb("Really withdraw publication?");?>')) {
                                            $(dlg).dialog('destroy');
                                            $.ajax({
                                                url: '../php/mb_unpublish_wmc.php',
                                                type: 'POST',
                                                data: {
                                                    wmc_serial_id: id
                                                },

                                                success: function(data) {

                                                    $('#mySpatialData').dialog('destroy');
                                                    $('#mySpatialData').remove();
                                                    $($addButton).trigger('click');



                                                }


                                            });



                                        }
                                    });

                                    $(dlg).find('li.kmltree-metadata-publish').bind('click', function() {

                                        // check if the user is public(guest-user) or not
                                        var isPublic;
                                        $.ajax({
                                            url: '../php/mb_checkGuest.php',
                                            type: 'POST',
                                            success: function(data) {
                                                isPublic = data;
                                                if (isPublic == 1){

                        							alert('The Guest-User is not allowed to publish a WMC!'+
                        							      'If you want to use this function, please create an account.');
                        							return false;
                                                }
                                                var publishDialog;
                                                $(dlg).dialog('destroy');
                                                if ($('#wmcPublishConfirm').dialog('isOpen') === true) {


                                                    return;

                                                } else {

                                                    publishDialog = $('<div id="wmcPublishConfirm"></div>').dialog({
                                                        title: "<?php echo _mb("Publish datacollection");?> " + d[1],
                                                        width: 373,
                                                        height: 'auto',
                                                        position: {
                                                            my: "center",
                                                            at: "top",
                                                            of: window
                                                        },
                                                        close: function() {
                                                            // $('#kml-load-tabs').tabs('destroy');
                                                            // $(this).html('').dialog('destroy');
                                                            $('#wmcPublishConfirm').dialog('destroy');
                                                            $('#wmcPublishConfirm').remove();
                                                        }
                                                    });

                                                    var publishDlgContent = "<div style='font-size:16px;text-align:justify'><?php echo _mb("If you want to publish a datacollection, you first have to choose a license under which you want to distribute your data. Actually only OpenData compatible licences are supported in this application. Please read the licenses carefully before you activate this option. All of your data will be available for each person in the web in different formats and may be redistributed freely without any copyright.");?></div>" +
                                                        "<table style='margin-top:7px' ><tr><th id = 'publishDenied'><img src='../img/button_digitize/geomRemove.png'></img>  <?php echo _mb("No, I don't want to publish");?></th>" +
                                                        "<th style='width:16%;visibility:hidden'>empty</th>" +
                                                        "<th id = 'publishConfirmed'><img src='../img/osgeo_graphics/check.png'></img>  <?php echo _mb("Yes, I know what I am doing");?> </th></tr></table>";

                                                    $(publishDialog).append(publishDlgContent);


                                                }






                                                $(publishDialog).find('#publishConfirmed').bind('click', function() {
                                                    $(publishDialog).dialog('close');
                                                    var chooseLicenseDialog = $('<div id="chooseLicenseDialog"></div>').dialog({
                                                        title: "<?php echo _mb("Choose OpenData license for datacollection");?> " + d[1],
                                                        width: 720,
                                                        height: 150,
                                                        position: {
                                                            my: "center",
                                                            at: "top",
                                                            of: window
                                                        },
                                                        close: function() {

                                                            $('#chooseLicenseDialog').dialog('destroy');
                                                            $('#chooseLicenseDialog').remove();
                                                        }
                                                    });

                                                    var chooseLicenseDlgCont = "<div><table id='licenseTbl'style='border-collapse: collapse'>" +
                                                        "<tr><th><?php echo _mb("Licence");?></th>" +
                                                        "<th><?php echo _mb("Logo");?></th><th><?php echo _mb("Description");?></th><th><?php echo _mb("OpenData");?></th></tr>" +
                                                        "<tr><td><select id='licenseChooser name='license'>" +
                                                        "</select></td>" +
                                                        "<td id='licenseImg'></td>" +
                                                        "<td id='licenseDescription'></td>" +
                                                        "<td id='licenseOpen' nowrap></td></tr>" +
                                                        "<tr id='submitLicense' ><td style='border:none;cursor:pointer; nowrap'><img src='../img/osgeo_graphics/check.png' style='margin-top:10px'/> <span><?php echo _mb("Publish data");?></span></td></tr>" +
                                                        "</table></div>";

                                                    //  add options for the select box
                                                    $(chooseLicenseDialog).append(chooseLicenseDlgCont);

                                                    $.ajax({
                                                        url: '../php/mb_publish_wmc.php',
                                                        type: 'POST',
                                                        data: {
                                                            wmc_serial_id: id,
                                                            mode: 'getAllLicencesMode',
                                                            license: 'empty',
                                                            openData_only: options.openData_only,
                                                        },

                                                        success: function(data) {

                                                            for (var i = 0; i < data.length; i++) {

                                                                $('#licenseTbl select').append("<option>" + data[i].name + "</option>");
                                                            }

                                                        }

                                                    });


                                                    $.ajax({
                                                        url: '../php/mb_publish_wmc.php',
                                                        type: 'POST',
                                                        data: {
                                                            wmc_serial_id: id,
                                                            mode: 'getLicenseMode',
                                                            license: 'cc-by'
                                                        },

                                                        success: function(data) {

                                                            $('#licenseImg').html('<img src="' + data.symbollink + '" />');
                                                            $('#licenseDescription').html('<a href="' + data.description + '" />' + data.description + '</a>');
                                                            if (data.isopen == 1) {

                                                                $('#licenseOpen').html('<img src="../img/od_80x15_blue.png" />');
                                                            } else {

                                                                $('#licenseOpen').html('<span><?php echo _mb("No OpenData");?></span>');
                                                            }

                                                        }

                                                    });


                                                    $('#licenseTbl select').bind('change', function(event) {
                                                        $('#licenseImg').html('');
                                                        $('#licenseDescription').html('');

                                                        $.ajax({
                                                            url: '../php/mb_publish_wmc.php',
                                                            type: 'POST',
                                                            data: {
                                                                wmc_serial_id: id,
                                                                mode: 'getLicenseMode',
                                                                license: $('#licenseTbl select').val()
                                                            },

                                                            success: function(data) {

                                                                $('#licenseImg').html('<img src="' + data.symbollink + '" />');
                                                                $('#licenseDescription').html('<a href="' + data.description + '" />' + data.description + '</a>');
                                                                if (data.isopen == 1) {

                                                                    $('#licenseOpen').html('<img src="../img/od_80x15_blue.png" />');

                                                                } else {

                                                                    $('#licenseOpen').html('<span><?php echo _mb("No OpenData");?></span>');
                                                                }

                                                            }

                                                        });
                                                    });

                                                    $('#submitLicense').bind('click', function(event) {
                                                        //save the license from the choosed wmc

                                                        $.ajax({
                                                            url: '../php/mb_publish_wmc.php',
                                                            type: 'POST',
                                                            data: {
                                                                wmc_serial_id: id,
                                                                mode: 'saveLicenseMode',
                                                                license: $('#licenseTbl select').val()
                                                            },

                                                            success: function(data) {

                                                                $('#chooseLicenseDialog').dialog('destroy');
                                                                $('#chooseLicenseDialog').remove();
                                                                $('#mySpatialData').dialog('destroy');
                                                                $('#mySpatialData').remove();
                                                                $($addButton).trigger('click');




                                                            }

                                                        });
                                                    });

                                                });

                                                $(publishDialog).find('#publishDenied').bind('click', function() {
                                                    $(publishDialog).dialog('close');


                                                });
                                            }
                                        });




                                    });

                                }
                            });
                        });
                    // .bind('dblclick', function() {
                    //     var id = $($(this).find('td')[0]).text();
                    //     $.ajax({
                    //         type: 'post',
                    //         url: '../php/mb_load_local_data.php',
                    //         data: {
                    //             id: id
                    //         },
                    //         success: function(data) {
                    //             var kml = $('#mapframe1').data('kml');
                    //             $.each(data, function(url, json) {
                    //                 kml.addLayer(url, json.data);
                    //             });
                    //             $(dlg).dialog('destroy');
                    //         }
                    //     });
                    // });
			var allRows = $('#kml-from-wmc').find('tr');
			$.each(allRows, function(index, val) {
						if ( parseInt($(val).find('td:last').html()) ===  parseInt(Mapbender.userId)) {
							$(val).css('background-color','rgb(146, 232, 183)');
						}
			});
                }
            });
            $('#kml-load-tabs').tabs();
            $('#kml-load-tabs').find('button.add').bind('click', function() {
                $('#mapframe1').kml({ //TODO: what is happening?
                    url: $('#kml-load-tabs').find('.kmlurl').val()
                });
                // $(dlg).dialog('destroy');
            });
            $('#kml-load-tabs').find('button.add-kml').bind('click', function() {
                var kml = $('#mapframe1').data('kml');
                var title = $('#kml-load-tabs input[name="kml-new-title"]').val();
                var version = 'v1'
                if (title == '') {
                    return;
                } else if (!title.match(/^[a-zA-Z0-9äöüÄÖÜß_\- .\"]+$/)){
			alert("Allowed characters for title are: A-Z, a-z, 0-9, -, _, äÄ, öÖ, üÜ, ß, \"");
			return;
		}
                kml.addLayer(title, {
                    uuid: UUID.genV4().toString(),
                    created: new Date().toISOString(),
                    title: title,
                    updated: new Date().toISOString(),
                    version: version,
                    type: 'FeatureCollection',
                    features: []
                });
                $(dlg).dialog('destroy');
            });
	    //upload of remote files
            var ifr = $('iframe[name="kml-upload-target"]')[0];
            var onloadfun = function() {
                ifr.onload = null;
                var txt = $(this).contents().find('pre').text(); // result von php/uploadKML.php
		//alert(txt.length);
                var data;
		//returns geojson from kml (from internal parser) or geojson native - no parsing or exception - then gpx!
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    var xml = new DOMParser().parseFromString(txt, 'application/xml');
                    data = toGeoJSON.gpx(xml);
                }
		//alert(JSON.stringify(data).length);
                var kml = $('#mapframe1').data('kml');
                var name;
                // check the features for properties - old handling!!!!!
                //data = setFeatureAttr(data);

                if (data.hasOwnProperty('title')) {

                    name = data['title'];
                    kml.addLayer(name, data);
                } else {

                    name = $('#kml-from-upload input[type="file"]').val();

                    // test.replace(/\\/g,"/").split("/")
                    if (name.replace(/\\/g, "/").split("/")) {
                        // name = name.match(/[\\]([^\\]+)/g);
                        name = name.replace(/\\/g, "/").split("/");
                    }
                    name = name[name.length - 1];
                    kml.addLayer(name, data);
                }

                $(dlg).dialog('destroy');
            };
            $('#kml-from-upload form').bind('submit', function() {
		if ( $( "#kml-from-upload > form > input[type='file'] " ).val() === "") {
			return;
		}
                ifr.onload = onloadfun;
            });

        }
    });
    $KMLfolder.find('ul').before(selectButton);
    $KMLfolder.find("ul").before($addButton);

    var btn = new Mapbender.Button({
        domElement: selectButton[0],
        over: '../img/osgeo_graphics/geosilk/cursor_selected.png',
        on: '../img/osgeo_graphics/geosilk/cursor_selected.png',
        off: '../img/osgeo_graphics/geosilk/cursor.png',
        name: 'toggle-select-features',
        go: function() {
            var kml = $('#mapframe1').data('kml');
            kml.setQueriedLayer(true);
        },
        stop: function() {
            var kml = $('#mapframe1').data('kml');
            kml.setQueriedLayer(false);
        }
    });

    o.$target.bind('kml:loaded', function(e, obj) {
        var checked = obj.display ? 'checked="checked"' : '';
        title = obj.url;
        if (obj.refreshing) {
            $KMLfolder.find('ul li[title="' + title + '"]').remove();
        }
        abbrevTitle = title.length < 20 ? title : title.substr(0, 17) + "...";
        $kmlEntry = $('<li title="' + title + '" class="open"><button class="digitize-menu-arrow"></button><button class="toggle" name="toggle" value="toggle" ></button> <input type="checkbox"' + checked + '/><a href="#">' + abbrevTitle + '</a></li>');
        $KMLfolder.children("ul").append($kmlEntry);

        $kmlEntry.find("a").bind("click", (function(url) {
            return function() {
                $('#mapframe1').data('kml').zoomToLayer(url);
            };
        })(obj.url));

        $featureList = $("<ul />");
        $kmlEntry.append($featureList);
        var pointCount = 1;
        var polygonCount = 1;
        var linestringCount = 1;
        for (var i = obj.data.features.length - 1; i >= 0; --i) { //FIXME: for feature without the "type: FeatureCollection ",change functionallity: if (obj.data.type == "Feature") ...
            var multi = obj.data.features[i].geometry.type.match(/^Multi/i);
            var toggle = '';
            if (multi) {
                toggle = '<button class="toggle" name="toggle" value="toggle"></button>';
            }

            if (obj.data.features[i].properties.name) {

                title = obj.data.features[i].properties.name;

            } else if (obj.data.features[i].properties.title) {

                title = obj.data.features[i].properties.title;
            } else {

            	switch (obj.data.features[i].geometry.type){

            		case 'Point':
                		title = 'point_'+pointCount;
                		pointCount += 1;
                		break;
            		case 'Polygon':
            			title = 'polygon_'+polygonCount;
                		polygonCount += 1;
            			break;
            		case 'LineString':
            			title = 'linestring_'+linestringCount;
                		linestringCount += 1;
            			break;
            	}
                // title = 'Title undefined';

            }
            // title = obj.data.features[i].properties.name;


            abbrevTitle = title.length < 20 ? title : title.substr(0, 17) + "...";
            var displ = obj.data.features[i].display === true || obj.data.features[i].display === undefined;
            $feature = $('<li idx="' + i + '" title="' + title + '"><button class="digitize-menu-arrow"></button>' + toggle + '<input type="checkbox" ' + (displ ? 'checked="checked"' : '') + '/><div class="style-preview" style="width: 20px; height: 20px; display: inline;"></div><a href="#" >' + abbrevTitle + '</a></li>');
            $featureList.append($feature);

            var preview = $feature.find('.style-preview').get(0);
            $('#mapframe1').data('kml').renderPreview(obj.data.features[i], preview, 20);

            title = obj.data.features[i].properties.name;

            $feature.bind('mouseout', (function(jsonFeature) {
                return function() {
                    var map = o.$target.mapbender();
                    var g = new GeometryArray();
                    g.importGeoJSON(jsonFeature, false);
                    var feature = g.get(0);

                    if (feature.geomType != "point") {
                        var me = $kmlTree.mapbender();
                        me.resultHighlight.clean();
                        me.resultHighlight.paint();
                    }
                }
            })(obj.data.features[i]));
            $feature.bind('mouseover', (function(jsonFeature) {
                return function() {
                    var map = o.$target.mapbender();
                    var g = new GeometryArray();
                    g.importGeoJSON(jsonFeature, false);
                    var feature = g.get(0);

                    if (feature.geomType != "point") {
                        var me = $kmlTree.mapbender();
                        feature = feature.getBBox4();
                        me.resultHighlight = new Highlight(
                            [o.target],
                            "KmlTreeHighlight", {
                                "position": "absolute",
                                "top": "0px",
                                "left": "0px",
                                "z-index": 100
                            },
                            2);

                        me.resultHighlight.add(feature, "#00ff00");
                        me.resultHighlight.paint();
                    } else if (feature.geomType == "point") {

                    }

                };
            })(obj.data.features[i]));
        }

        $('button.digitize-layer', $kmlEntry).bind('click', function() {
            var active = $(this).toggleClass('active').hasClass('active');
            if (active) {
                $(this).parent().siblings().find('button.digitize-layer').removeClass('active');
            }
        });

        $('#kmlTree > li > ul').sortable({
            update: function() {
                var kml = $('#mapframe1').data('kml');
                var urls = [];
                $(this).children('li[title]').each(function(k, v) {
                    urls.push($(this).attr('title'));
                });
                kml.setOrder(urls);
            }
        });

        $('#kmlTree > li > ul > li > ul').sortable({
            update: function(evt, data) {
                var kml = $('#mapframe1').data('kml');
                var url = $(this).parent().attr('title');
                var ids = [];
                var i = $(this).children().length;
                $.each($(this).children(), function(k, v) {
                    ids.push($(v).attr('idx'));
                    $(v).attr('idx', --i);
                });
                kml.reorderFeatures(url, ids.reverse());
            }
        });

        $('input[type="checkbox"]', $kmlEntry).bind('click', function() {
            var idx = $(this).parent().attr('idx');

            if (idx === undefined) {
                if ($(this).attr('checked')) {
                    o.$target.kml('show', obj.url);
                } else {
                    o.$target.kml('hide', obj.url);
                }
            } else {
                var kml = $('#mapframe1').data('kml');
                if ($(this).attr('checked')) {
                    kml.showFeature(obj.url, idx);
                } else {
                    kml.hideFeature(obj.url, idx);
                }
            }
        });

        $("button.toggle", $kmlEntry).bind('click', function() {
            var parent = $(this).parent();
            if (parent.hasClass("open")) {
                parent.removeClass("open");
                parent.addClass("closed");
            } else {
                parent.removeClass("closed");
                parent.addClass("open");
            }

            // IE8 workaround to make style previews visible...
            parent.find('ul.ui-sortable li .rvml').removeClass('rvml').addClass('rvml');
        });
        $('#tabs_kmlTree').bind('click', function () {
            window.setTimeout(function () {
                // IE8 workaround to make style previews visible...
                $('#kmlTree').find('ul.ui-sortable li .rvml').removeClass('rvml').addClass('rvml');
            }, 1000);
        });
    });
    var setFeatureAttr = function (data) {
        var simpleStyleDefaults = {
            "title": "",
            "description": "",
            "marker-size": "medium",
            "marker-symbol": "",
            "marker-color": "7e7e7e",
            "stroke": "#555555",
            "stroke-opacity": 1.0,
            "stroke-width": 2,
            "fill": "#555555",
            "fill-opacity": 0.5
        };
        if (data.type == 'Feature') {
            if (Object.getOwnPropertyNames(data.properties).length === 0 || data.properties === null) {
                data.properties = simplyStyleDefaults;
            } else {
                $.each(simpleStyleDefaults, function (index, val) {
                    if (!data.properties.hasOwnProperty(index)) {
                        data.properties[index] = value;
                    }
                });
            }
        } else if (data.type == 'FeatureCollection') {
            // get all features in the featureCollection and set the default properties if they are not set
            $.each(data.features, function (index, val) {
                $.each(simpleStyleDefaults, function (prop, propVal) {
                    if (!val.properties.hasOwnProperty(prop)) {

                        //TODO: get index in this scope
                        data.features[index].properties[prop] = propVal;
                    }
                });
            });
        }
        return data;
    };
    //  'area', 'boundary-length', 'track-length'
    var featureInfoFilter = ['title', 'marker-size', 'marker-symbol', 'marker-color',
        'marker-offset-x', 'marker-offset-y', 'stroke', 'stroke-opacity',
        'stroke-width', 'fill', 'fill-opacity'];

    function escapeHTML(text) {
        return text
                .toString()
		.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#x27;')
                .replace(/\//g, '&#x2F;');
    }

    function createFeatureInfoContent(props) {
        var $table = $("<table>")
                .attr("border", 1);
        for (var key in props) {
            if (props.hasOwnProperty(key) && featureInfoFilter.indexOf(key) < 0) {
                $table
                    .append($("<tr>")
                        .append($("<td>").html(escapeHTML(key)))
                        .append($("<td>").html(escapeHTML(props[key]))))
            }
        }
        return $table.attr('outerHTML');
    }

    this.getFeatureInfos = function (click) {
        var map = Mapbender.modules.mapframe1;
        var kml = $('#mapframe1').data('kml');
        return kml.findFeaturesAtClick(click)
            .map(function (locator) {
                var kmlLayer = map.kmls[locator.url];
                return {
                    title: kmlLayer.data.title,
                    content: createFeatureInfoContent(kmlLayer.data.features[locator.id].properties)
                }
            });
    };
};

Mapbender.events.init.register(function() {
    $kmlTree.mapbender(new KmlTree(options));
});
