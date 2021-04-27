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

if (options.deactivateOnOpenDialog == "true") {
    options.deactivateOnOpenDialog = true;
} else {
    options.deactivateOnOpenDialog = false;
}

var that = this;

Mapbender.events.init.register(function () {
	
	var mb_panActive = false;
	var startPos, stopPos;
	var map = Mapbender.modules[options.target];


    var movestart = function (e) {
        mb_panActive = true;
        startPos = map.getMousePosition(e);
        stopPos = new Point(startPos);
        return false;
    };

    var move = function (e) {
        if (!mb_panActive) {
            return false;
        }
        stopPos = map.getMousePosition(e);
        var dif = stopPos.minus(startPos);
        map.moveMap(dif.x, dif.y);
        if (!$.browser.msie){
            return true;
        }
        return false;
    };

    var moveend = function (e) {
        if (!mb_panActive) {
            return false;
        }
        if (!map) {
            return false;
        }
        mb_panActive = false;
        var dif = stopPos.minus(startPos);
        var widthHeight = new Mapbender.Point(
            map.getWidth(),
            map.getHeight()
        );
        var center = widthHeight.times(0.5).minus(dif);
        var realCenter = map.convertPixelToReal(center);   
        map.moveMap();
        map.zoom(false, 1.0, realCenter);   
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
			$(map.getDomElement())
				.css("cursor", "move")
				.mousedown(movestart)
                .mousemove(move)
                .mouseup(moveend)
                .mouseleave(moveend);
		},
		stop: function () {
			$("#pan1").removeClass("myOnClass");
			if (!map) {
				return false;
			}
			$(map.getDomElement())
				.css("cursor", "default")
				.unbind("mousedown", movestart)
                .unbind("mousemove", move)
				.unbind("mouseup", moveend)
                .unbind("mouseleave", moveend);
			mb_panActive = false;
		}
	});
    
    // deactivate if ui-dialog opens and this behaviour is defined as element var:
    // deactivateOnOpenDialog
    if (options.deactivateOnOpenDialog == true) {
        $(document).bind('dialogopen', function () {
            button.stop()
        });
    }
});
