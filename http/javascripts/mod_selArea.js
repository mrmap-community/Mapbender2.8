/**
 * Package: selArea
 *
 * Description:
 * Zoom by rectangle
 *
 * Files:
 *  - http/javascripts/mod_selArea.js
 *
 * SQL:
 * > <SQL for element>
 * >
 * > <SQL for element var>
 *
 * Help:
 * http://www.mapbender.org/SelArea1
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

var that = this;

Mapbender.events.init.register(function () {

    var box;
    var map = Mapbender.modules[options.target];

    var mouseup = function(e) {
        box.stop(e, function (extent) {
            if (typeof extent === "undefined") {
                return false;
            }
            if (extent.constructor === Mapbender.Extent) {
                var xt = map.calculateExtent(extent);
                map.setMapRequest();
            }
            else if (extent.constructor === Mapbender.Point) {
                map.setCenter(extent);
                map.setMapRequest();
            }
        });
        return false;
    };

    var mousedown = function (e) {
        box.start(e);
        return false;
    };

    var button = new Mapbender.Button({
        domElement: that,
        over: options.src.replace(/_off/, "_over"),
        on: options.src.replace(/_off/, "_on"),
        off: options.src,
        name: options.id,
        go: function () {
            if (!map) {
                new Mb_exception(options.id + ": " +
                                 options.target + " is not a map!");
                return;
            }

            box = new Mapbender.Box({
                target: options.target
            });
            $(map.getDomElement()).css(
                "cursor", "crosshair"
            ).mousedown(mousedown)
            .mouseup(mouseup);
        },
        stop: function () {
            $("#selArea1").removeClass("myOnClass");
	    if (!map) {
                return;
            }
            $(map.getDomElement())
            .css("cursor", "default")
            .unbind("mousedown", mousedown)
            .unbind("mouseup", mouseup);
            box = null;
        }
    });
});
