// events
//  geojson:loaded      - a georssfeed was loaded from a server and is now available: function(url,geojson)
//  georss:error    - a georssfeed failed to load: function(url,error);

var originalI18nObject = {
    "labelLoadError": "Could not load Document",
    "labelName": "Name",
    "labelUntitled": "Untitled",
    "labelUrlBox": "Paste URL here",
    "sProcessing": "Processing...",
    "sLengthMenu": "Show _MENU_ entries",
    "sZeroRecords": "No matching records found",
    "sInfo": "SLowing _START_ to _END_ of _TOTAL_ entries",
    "sInfoEmpty": "Showing 0 to 0 of 0 entries",
    "sInfoFiltered": "(filtered from _MAX_ total entries)",
    "sInfoPostFix": "",
    "sSearch": "Search:",
    "sUrl": "",
    "oPaginate": {
        "sFirst": "First",
        "sPrevious": "Previous",
        "sNext": "Next",
        "sLast": "Last"
    }
};

var translatedI18nObject = Mapbender.cloneObject(originalI18nObject);
//var translatedI18nObject = originalI18nObject;

Proj4js.defs["EPSG:25832"] = "+proj=utm +zone=32 +ellps=GRS80 +units=m +no_defs";
Proj4js.defs["EPSG:31466"] = "+proj=tmerc +lat_0=0 +lon_0=6 +k=1 +x_0=2500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs";
Proj4js.defs["EPSG:31467"] = "+proj=tmerc +lat_0=0 +lon_0=9 +k=1 +x_0=3500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs";
Proj4js.defs["EPSG:31468"] = "+proj=tmerc +lat_0=0 +lon_0=12 +k=1 +x_0=4500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs";
Proj4js.defs["EPSG:31469"] = "+proj=tmerc +lat_0=0 +lon_0=15 +k=1 +x_0=5500000 +y_0=0 +ellps=bessel +datum=potsdam +units=m +no_defs";

var displayFeatures = {
    options: {
        url: "",
        position: 'right',
        autoOpen: true,
        autoDisplay: true
    },
    wgs84: new Proj4js.Proj('EPSG:4326'),
    targetProj: new Proj4js.Proj('EPSG:25832'),
    _kmls: {},
    cache: {},
    kmlOrder: [],
    _popup: null,
    creatingPhase: true,
    selectedFeatures: [],
    selectionDialog: '<div id="selection-dialog" title="Selected features">' + '<ul id="selected-features-list">' + '</ul>' + '<div class="digitize-image digitize-remove"></div>' + '<div class="digitize-image digitize-export"></div>' + '</div>',

    _create: function() {
        var self = this,
            o = this.options;
        this.element.mapbender().events.afterMapRequest.register(function() {
            self.render();
        });

        var delta = 2;
        var lastX, lastY;
        var box;

        this.element.bind('mousedown', function(e) {
            if (!self.queriedLayer) return;
            lastX = e.clientX;
            lastY = e.clientY;
            box = new Mapbender.Box({
                target: 'mapframe1'
            });
            box.start(e);
            $('#mapframe1').css("cursor", "crosshair")
        });
            
        $('#mapframe1').mouseup(function(e) {
            if (!self.queriedLayer) return;
            var matchedIds;
            var extent = box.stop(e);
            $('#mapframe1').css("cursor", "default");
            if (Math.abs(lastX - e.clientX) <= delta && Math.abs(lastY - e.clientY) <= delta) {
                // click
                matchedIds = self.findFeaturesAtClick(e);
            } else {
                // drag
                if (typeof extent === "undefined") {
                    return;
                }

                matchedIds = self.findFeaturesInExtent(extent);
            }
            self.updateSelectedFeatures(matchedIds, e.ctrlKey);
            return false;
        });

        self.element.bind('kml:loaded', function(event, obj) {
            if (o.autoOpen) {
                self.render();
            }
        });
        self.element.bind('kml:error', function(event, message) {
            alert(message);
        });

        var kmls = mb_getWmcExtensionData('KMLORDER');
        if (kmls) {
            this.kmlOrder = JSON.parse(kmls);
        }
        kmls = mb_getWmcExtensionData('KMLS');
        if (kmls) {
            kmls = JSON.parse(kmls);
            this._kmls = kmls;
            for (var k in this.kmlOrder) {
                kmls[this.kmlOrder[k]].loadedOnStartup = true;
                self.element.trigger('kml:loaded', kmls[this.kmlOrder[k]]);
            }
        }
        this.creatingPhase = false;
        this.render();
        // // save the eventHandlers
        // var kmlEventHandlers = $.extend(true,{}, $('#mapframe1').data('events'));
        // var kmlEventHandlersObj = {kmlEventHandlers: kmlEventHandlers};
        // $.extend($('#mapframe1').data(), kmlEventHandlersObj);
    },

    _init: function() {
        var self = this,
            o = this.options;
        this._popup = $('<div></div>').dialog({
            autoOpen: false,
            height: 500,
            width: 500
        });
        if (o.url) {
            self._load(o.url);
        }
    },
    
    /**
     * Check if the point lies inside the box.
     * @param {type} point
     * @param {type} box
     * @returns {Boolean}
     */
    pointInBox: function (point, box) {
        if (point.x >= box.minx && point.x <= box.maxx 
                && point.y >= box.miny && point.y <= box.maxy) {
            return true;
        } else {
            return false;
        }
    },

    /**
     * Checks if value x is between y1 and y2. It works also if y2 < y1.
     * @param {type} x
     * @param {type} y1
     * @param {type} y2
     * @returns {Number}
     */
    valueIsBetween: function (x, y1, y2) {
        return (y1 - x) * (y2 - x) <= 0;
    },

    /**
     * calculates the Y value of line for a given X.
     * @param {type} line
     * @param {type} y
     * @returns {Number}
     */
    lineYAtX: function (line, x) {
        return line.y2 + (x - line.x2) * (line.y1 - line.y2) / (line.x1 - line.x2) 
    },

    /**
     * calculates the X value of line for a given Y.
     * @param {type} line
     * @param {type} y
     * @returns {Number}
     */
    lineXAtY: function (line, y) {
        return line.x2 + (y - line.y2) * (line.x1 - line.x2) / (line.y1 - line.y2) 
    },

    /**
     * First checks if line is completely contained inside of the box. If not it checks
     * if one of the sides of the box intersects with the line.
     * @param {type} line
     * @param {type} box
     * @returns {Boolean}
     */
    lineIntersectsBox: function (line, box) {
        if (this.pointInBox({ x: line.x1, y: line.y1 }, box)
                || this.pointInBox({ x: line.x1, y: line.y1 }, box)) {
            return true;
        }

        if (this.valueIsBetween(box.minx, line.x1, line.x2)) {
            var y = this.lineYAtX(line, box.minx);
            if (y >= box.miny && y <= box.maxy) {
                return true;
            }
        }

        if (this.valueIsBetween(box.maxx, line.x1, line.x2)) {
            var y = this.lineYAtX(line, box.maxx);
            if (y >= box.miny && y <= box.maxy) {
                return true;
            }
        }

        if (this.valueIsBetween(box.miny, line.y1, line.y2)) {
            var x = this.lineXAtY(line, box.miny);
            if (x >= box.minx && x <= box.maxx) {
                return true;
            }
        }

        if (this.valueIsBetween(box.maxy, line.y1, line.y2)) {
            var x = this.lineXAtY(line, box.maxy);
            if (x >= box.minx && x <= box.maxx) {
                return true;
            }
        }

        return false;
    },
   
    getLines: function (points, connect) {
        lines = [];
        for (var i = 0; i < points.length - 1; i ++) {
            lines.push({
                x1: points[i][0],
                y1: points[i][1],
                x2: points[i + 1][0],
                y2: points[i + 1][1]
            });
        }
        if (connect) {
            lines.push({
                x1: points[points.length - 1][0],
                y1: points[points.length - 1][1],
                x2: points[0][0],
                y2: points[0][1]
            })
        }
        return lines;
    },
   
    lineStringIntersectsBox: function (lineStringPoints, box) {
        var self = this;
        return this.getLines(lineStringPoints).some(function (line) {
            return self.lineIntersectsBox(line, box);
        });
    },

    /**
     * Checks whether a polygon intersects a box.
     * On the one hand it checks if any of the lines on the polygon ring intersect
     * with the box and on the other hand it checks how many times a ray cast outwards
     * from the middle of the box intersects with one of the lines. If it intersects
     * an odd number of times it lays inside the polygon.
     * @param {type} polygon
     * @param {type} box
     * @returns {Boolean}
     */
    polygonIntersectsBox: function (polygonRings, box) {
        var rayPoint = {
            x: box.minx + (box.maxx - box.minx) / 2,
            y: box.miny + (box.maxy - box.miny) / 2
        };
        var rayIntersections = 0;

        var self = this;
        var lines = polygonRings.reduce(function (lines, ring) {
            Array.prototype.push.apply(lines, self.getLines(ring, true));
            return lines;
        }, [])

        for (var i = 0; i < lines.length; i++) {
            if (this.lineIntersectsBox(lines[i], box)) {
                return true;
            }
            if (this.valueIsBetween(rayPoint.y, lines[i].y1, lines[i].y2)) {
                var x = this.lineXAtY(lines[i], rayPoint.y);
                if (x >= rayPoint.x) {
                    rayIntersections++;
                }
            }
        }

        if (rayIntersections % 2 === 1) {
            return true;
        } else {
            return false;
        }
    },
   
    boxInBox: function (boxA, boxB) {
         return this.pointInBox({ x: boxA.minx, y: boxA.miny }, boxB) &&
              this.pointInBox({ x: boxA.maxx, y: boxA.maxy }, boxB);
    },
    
    
    /**
     * Checks whether the geometry of the feature intersects with the box
     * @param {type} feature
     * @param {type} box
     * @returns {Boolean}
     */
    featureIntersectsBox: function (feature, box) {
        var geometryType = feature.geometry.type.toLowerCase();
        switch (geometryType) {
            case 'point':
                return this.pointInBox({
                    x: feature.geometry.coordinates[0],
                    y: feature.geometry.coordinates[1]
                }, box);
            case 'linestring':
                return this.lineStringIntersectsBox(feature.geometry.coordinates, box);
            case 'polygon':
                return this.polygonIntersectsBox(feature.geometry.coordinates, box);
            default:
              throw new Error('feature geometry type not supported');  
        }
    },
    
    featureInBox: function (feature, box) {
        if (feature.geometry.type.toLowerCase() === 'point') {
            return this.pointInBox({
                x: feature.geometry.coordinates[0],
                y: feature.geometry.coordinates[1]
            }, box);
        } else {
            var arrayBox = this.getBbox(feature);
            return this.boxInBox({
                minx: arrayBox[0],
                miny: arrayBox[1],
                maxx: arrayBox[2],
                maxy: arrayBox[3]
            }, box);
        }
    },
    
    
    
    /**
     * Finds all features that intersect with a buffer around a point or a given box.
     * @param {type} posOrBox
     * @returns {Array|displayFeatures.findIntersectingFeatures.ids}
     */
    findFeaturesAtClick: function (e) {
        var map = this.element.mapbender();
        var pos = map.getMousePosition(e);
        
        var min = {
            x: pos.x - 20,
            y: pos.y + 20 // screen y is in opposite direction of map y
        };
        var max = {
            x: pos.x + 20,
            y: pos.y - 20
        };
        
        min = map.convertPixelToReal(min);
        max = map.convertPixelToReal(max);
        
        min = Proj4js.transform(this.targetProj, this.wgs84, min);
        max = Proj4js.transform(this.targetProj, this.wgs84, max);
        
        var box = {
            minx: min.x,
            miny: min.y,
            maxx: max.x,
            maxy: max.y
        };
        
        var self = this;
        var matches = [];
        for (var kmlId in this._kmls) {
            if (this._kmls.hasOwnProperty(kmlId)) {
                var kml = this._kmls[kmlId];
                matches = kml.data.features.reduce(function (matches, feature, index) {
                    if (self.featureIntersectsBox(feature, box)) {
                        matches.push({
                            url: kml.url,
                            id: index
                        });
                    }
                    return matches;
                }, matches);
            }
        }
        return matches;
    },

    findFeaturesInExtent: function(extent) {
        var min = Proj4js.transform(this.targetProj, this.wgs84, {
            x: extent.minx,
            y: extent.miny
        });
        var max = Proj4js.transform(this.targetProj, this.wgs84, {
            x: extent.maxx,
            y: extent.maxy
        });
        
        var box = {
            minx: min.x,
            miny: min.y,
            maxx: max.x,
            maxy: max.y
        };
        
        var matches = [];
        var self = this;
        for (var kmlId in this._kmls) {
            if (this._kmls.hasOwnProperty(kmlId)) {
                var kml = this._kmls[kmlId];
                matches = kml.data.features.reduce(function (matches, feature, index) {
                    if (self.featureInBox(feature, box)) {
                        matches.push({
                            url: kml.url,
                            id: index
                        });
                    }
                    return matches;
                }, matches);
            }
        }
        return matches;
    },

    updateSelectedFeatures: function(ids, append) {
        var self = this;

        if (ids.length == 1 && !append && self.selectedFeatures.length == 0) {
            $('li[title="' + ids[0].url + '"] li[idx="' + ids[0].id + '"]').click();
        } else {
            if (!append) {
                self.selectedFeatures = [];
            }
            $.each(ids, function(_, v) {
                var contained = false;
                $.each(self.selectedFeatures, function(_, sel) {
                    if (v.url == sel.url && v.id == sel.id) contained = true;
                });
                if (!contained) {
                    self.selectedFeatures.push(v);
                }
            });
            $('.kmltree-selected').removeClass('kmltree-selected');
            // console.log( self.selectedFeatures );


            $('#selection-dialog').dialog('destroy').remove();

            if (self.selectedFeatures.length == 0) {
                Mapbender.modules.digitize_widget.closeEditDialog();
                // self.markFeatureInLayerTree();
            }

            if (self.selectedFeatures.length > 1) {
                Mapbender.modules.digitize_widget.closeEditDialog();
                // $.each(self.selectedFeatures, function(_, v) {
                //     console.log( _, v );
                //     $('li[title="' + v.url + '"] li[idx="' + v.id + '"]').addClass('kmltree-selected');
                // });
                self.markFeatureInLayerTree();
                var dlg = $(self.selectionDialog);
                var list = dlg.find('#selected-features-list')
                    .html('');
                $.each(self.selectedFeatures, function(_, v) {
                    var feat = self._kmls[v.url].data.features[v.id];
                    var title = feat.properties.name;
                    list.append('<li><div style="width: 20px; height: 20px; display: inline;" class="style-preview"></div><span>' + title + '</span></li>');
                    var node = list.find('li div.style-preview').last()[0];
                    self.renderPreview(feat, node);
                });
                dlg.dialog({
                    close: function() {
                        self.selectedFeatures = [];
                        $('.kmltree-selected').removeClass('kmltree-selected');
                        $(this).dialog('destroy').remove();
                    }
                });
                dlg.find('.digitize-export').bind('click', function() {
                    var data = {
                        type: 'FeatureCollection',
                        features: []
                    };
                    $.each(self.selectedFeatures, function(_, v) {
                        var feat = self._kmls[v.url].data.features[v.id];
                        data.features.push(feat);
                        self.exportItem(data);
                    });
                    dlg.dialog('close');
                });
                dlg.find('.digitize-remove').bind('click', function() {
                    if (confirm('Do you really want to remove all these objects?')) {
                        var urls = [];
                        $.each(self.selectedFeatures, function(_, v) {
                            if ($.inArray(v.url, urls) == -1) {
                                urls.push(v.url);
                            }
                            $('#kmlTree li[title="' + v.url + '"] li[idx="' + v.id + '"]').remove();
                        });
                        $.each(urls, function(_, url) {
                            var ids = [];
                            $('#kmlTree li[title="' + url + '"] li[idx]').each(function() {
                                ids.push($(this).attr('idx'));
                            });
                            self.reorderFeatures(url, ids);
                        });

                        dlg.dialog('close');
                    }
                });
            }
        }
    },

    zoomToFeature: function(url, idx) {
        var map = $('#mapframe1').mapbender();
        var item = this._kmls[url];

        var bbox = this.getBbox(item.data.features[idx]);
        var bufferx = (bbox[2] - bbox[0]) * 0.2;
        var buffery = (bbox[3] - bbox[1]) * 0.2;
        var min = Proj4js.transform(this.wgs84, this.targetProj, {
            x: bbox[0] - bufferx,
            y: bbox[1] - buffery
        });
        var max = Proj4js.transform(this.wgs84, this.targetProj, {
            x: bbox[2] + bufferx,
            y: bbox[3] + buffery
        });

        map.calculateExtent(
            new Mapbender.Extent(min.x, min.y, max.x, max.y)
        );
        map.setMapRequest();
    },

    markFeatureInLayerTree: function(){
        var self = this;
        $.each(self.selectedFeatures, function(_, v) {
            $('li[title="' + v.url + '"] li[idx="' + v.id + '"]').addClass('kmltree-selected');
        });
    },

    zoomToLayer: function(url) {
        var bbox = this.getLayerBbox(url);
        if (!bbox) {
            return;
        }
        var map = $('#mapframe1').mapbender();

        var min = Proj4js.transform(this.wgs84, this.targetProj, {
            x: bbox[0],
            y: bbox[1]
        });
        var max = Proj4js.transform(this.wgs84, this.targetProj, {
            x: bbox[2],
            y: bbox[3]
        });

        map.calculateExtent(new Mapbender.Extent(min.x, min.y, max.x, max.y));
        map.zoom(true, 0.99999999);
    },

    showFeature: function(url, idx) {
        this._kmls[url].data.features[idx].display = true;
        this.cache[url] = null;
        this.render();
    },

    hideFeature: function(url, idx) {
        this._kmls[url].data.features[idx].display = false;
        this.cache[url] = null;
        this.render();
    },

    setQueriedLayer: function(url) {
        this.queriedLayer = url;
    },
    
    addFeature: function(url, feature) {
        if(!feature){
            return;
        }
        this.cache = {};
        var itm = this._kmls[url];
        feature.properties.created = feature.properties.updated = new Date().toISOString();
        feature.properties.uuid = UUID.genV4().toString();
        itm.data.features.push(feature);
        $('#mapframe1').data('kml').element.trigger('kml:loaded', {
            type: "geojson",
            data: itm.data,
            url: itm.url,
            display: itm.display,
            refreshing: true,
        });
        return itm.data.features.length - 1;
    },
    translateFeature: function(feature, newCentroid){
        var fc = this.getCentroid(feature);
        var dx = newCentroid.x - fc.x;
        var dy = newCentroid.y - fc.y;
        switch (feature.geometry.type.toLowerCase()) {
            case 'point':
                feature.geometry.coordinates[0] = feature.geometry.coordinates[0] + dx;
                feature.geometry.coordinates[1] = feature.geometry.coordinates[1] + dy;
                return feature;
            case 'linestring':
                var coords = feature.geometry.coordinates;
                for(var j = 0; j < coords.length; j++){
                    coords[j][0] = coords[j][0] + dx;
                    coords[j][1] = coords[j][1] + dy;
                }
                return feature;

            case 'polygon':
                coords = feature.geometry.coordinates;
                for(var i = 0; i < coords.length; i++){
                    for(var j = 0; j < coords[i].length; j++){
                        coords[i][j][0] = coords[i][j][0] + dx;
                        coords[i][j][1] = coords[i][j][1] + dy;
                    }
                }
                return feature;
        }
        return undefined;
    },
    
    
    copyFeature: function(feature) {
        var temp = $.extend(true, {}, feature);
        temp.properties.name = temp.properties.name + '_copied';
        temp.properties.created = feature.properties.updated = temp.properties.uuid = null;
        this.fixupFeature(temp);
        return temp;
    },

    addGeometry: function(pts, url, attributes) {
        this.cache = {};
        var $map = $(this.element).mapbender();
        var self = this;
        var tp = pts.closedPolygon ? geomType.polygon : (pts.closedLine ? geomType.line : geomType.point);
        var itm = this._kmls[url];

        var geom = new Geometry();
        var multi = new MultiGeometry(tp);

        for (var i = 0; i < pts.length; ++i) {
            var pt = Proj4js.transform(this.targetProj, this.wgs84, pts[i].pos);
            geom.addPoint(pt);
        }
        geom.geomType = tp;
        multi.add(geom);
        multi.e = new Wfs_element();


        var icon = multi.e.getElementValueByName("Mapbender:icon");
        multi.e.setElement('title', 'title');
        multi.e.setElement('name', 'name');

        // cloning the geoms to calculate the area or length
        var modifiedGeom = $.extend(true, {}, geom);
        var modifiedData = new MultiGeometry(tp);
            // add geometry: proof if polygon or linestring and add area or length
        for(var k_ in attributes){
            multi.e.setElement(k_, attributes[k_]);
        }

        if (geom.geomType != 'point') {

            if (geom.geomType == 'polygon') {

                modifiedGeom.addPoint(geom.list[0]); // add first point as last point
                modifiedData.add(modifiedGeom);

            } else {

                modifiedData.add(modifiedGeom);
            }
            // calculate current area (polygon) or length(linestring)
            $.ajax({
                url: '../php/mod_CalculateAreaAndLength.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    geom_type: modifiedData.geomType,
                    wkt_geom: modifiedData.toText(),
                },
                success: function(data) {

                    if (geom.geomType == 'polygon') {

                        multi.e.setElement('area', data[0]);
                        multi.e.setElement('boundary-length', data[1]);

                    } else {

                        multi.e.setElement('track-length', data[0]);
                    }

                },
                complete: function() {

                    multi.e.setElement("stroke", "#555555"); //@TODO: get the attributes from the config! Don't hardcode it!
                    multi.e.setElement("stroke-opacity", 1.0);
                    multi.e.setElement("stroke-width", 2);
                    if (geom.geomType == 'polygon') {

                        multi.e.setElement("fill", "#555555");
                        multi.e.setElement("fill-opacity", 0.5);

                    }

                    var feat = JSON.parse(multi.toString());
                    itm.data.features.push(feat);

                    $('#mapframe1').data('kml').element.trigger('kml:loaded', {
                        type: "geojson",
                        data: itm.data,
                        url: itm.url,
                        display: itm.display,
                        refreshing: true,
                    });
                    $map.setMapRequest();

                }
            });


        } else {
            if (icon == "false" || icon === false) {
                multi.e.setElement("iconOffsetX", -10);
                multi.e.setElement("iconOffsetY", -34);
                // multi.e.setElement("icon", "marker");
            }
            multi.e.setElement("marker-size", 34); // default value 'medium, small, large' is not allowed from canvas
            multi.e.setElement("marker-symbol", "marker");
            multi.e.setElement("marker-color", "#7e7e7e");

            var feat = JSON.parse(multi.toString());
            itm.data.features.push(feat);

            $('#mapframe1').data('kml').element.trigger('kml:loaded', {
                type: "geojson",
                data: itm.data,
                url: itm.url,
                display: itm.display,
                refreshing: true,
            });
            $map.setMapRequest();


        }
    },

    refresh: function(url) {
        this.cache = {};
        var $map = $(this.element).mapbender();
        var itm = this._kmls[url];
        if (!itm) {
            return;
        }
        this.element.trigger('kml:loaded', {
            type: "geojson",
            data: itm.data,
            url: itm.url,
            display: itm.display,
            refreshing: true
        });
        $map.setMapRequest();
    },
    //loading off remote data
    _load: function(url) {
        var self = this,
        o = this.options;
        var epsg = $(self.element).mapbender().epsg;
        epsg = epsg.split(":")[1];
        if (self._kmls[o.url]) {
            //not adding feed twiced
            return;
        }

        this.kmlOrder.push(o.url);

        $.ajax({
            url: '../php/kmlToGeoJSON.php',
            data: {
                url: o.url,
                targetEPSG: 'EPSG:4326'
            },
            type: 'POST',
            success: function(data, textStatus, xhr) {
                var json_result;

                if (!data) {
                    self.element.trigger('kml:error', "request returned no data");
                } else if (data.errorMessage) {

                    self.element.trigger('kml:error', data.errorMessage);
                } else {
                    //listen to gpx files
                    try {
                        json_string = JSON.parse(data);
                        json_result = JSON.parse(data);
                    } catch (e) {

                        var xml = new DOMParser().parseFromString(data, 'application/xml');
                        json_result = toGeoJSON.gpx(xml);
                    }
                    var kml = $('#mapframe1').data('kml');
                    var name;
                    if (json_result.hasOwnProperty('title')) {

                        name = json_result['title'];
                        kml.addLayer(name, json_result);
                    } else {

                        name = o.url;
                        if (name.match(/\w*\W(?:kml|gpx|geojson)$/)) {
                            name = name.match(/\w*\W(?:kml|gpx|geojson)$/)[0];
                        } else {

                            name = 'ImportFeatures';
                        }
                        kml.addLayer(name, json_result);
                    }
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                self.element.trigger('kml:error', "Problem talking to server: " + errorThrown);
            }
        });
    },

    addLayer: function(url, data) {
        if ($.inArray(url, this.kmlOrder) !== -1) {
            alert('Not adding ' + url + ', a layer with that name is already loaded.');
            return;
        }
        this.kmlOrder.push(url);
        this._kmls[url] = {
            type: "geojson",
            data: data,
            url: url,
            display: true
        };
        this.zoomToLayer(url);
        this.element.trigger('kml:loaded', {
            type: "geojson",
            data: data,
            url: url,
            display: true
        });
    },

    setOrder: function(order) {
        this.kmlOrder = order;
        this.render();
    },

    reorderFeatures: function(url, ids) {
        var itm = this._kmls[url];
        var list = [];
        $.each(ids, function(k, v) {
            list.push(itm.data.features[v]);
        });
        itm.data.features = list;
        this.cache[url] = {};
        var $map = $(this.element).mapbender();
        $map.setMapRequest();
    },

    show: function(url) {
        this._kmls[url].display = true;
        var $map = $(this.element).mapbender();
        var extent = $map.getExtentInfos();
        $map.calculateExtent(extent);
        $map.setMapRequest();
        $(this.element).mapbender().setMapRequest();
    },

    hide: function(url) {
        this._kmls[url].display = false;
        var $map = $(this.element).mapbender();
        var extent = $map.getExtentInfos();
        $map.calculateExtent(extent);
        $map.setMapRequest();
    },

    remove: function(url) {
        delete this._kmls[url];
        this.kmlOrder.splice(this.kmlOrder.indexOf(url), 1);
        delete this.cache[url];
        var $map = $(this.element).mapbender();
        var extent = $map.getExtentInfos();
        $map.calculateExtent(extent);
        $map.setMapRequest();
    },

    getLayerBbox: function(url) {
        var self = this;
        var itm = this._kmls[url];
        var bbox ;
        if (itm.data.type == "FeatureCollection") {

            if (itm.data.features.length == 0) {
                return false;
            }
            bbox = this.getBbox(itm.data.features[0]);
            $.each(itm.data.features, function(_, v) {
                var newbox = self.getBbox(v);
                bbox[0] = Math.min(bbox[0], newbox[0]);
                bbox[1] = Math.min(bbox[1], newbox[1]);
                bbox[2] = Math.max(bbox[2], newbox[2]);
                bbox[3] = Math.max(bbox[3], newbox[3]);
            });

        } else if (itm.data.type == "Feature") {

            bbox = this.getBbox(itm.data);

        }
        return bbox;
    },
    
    getCentroid: function(feature){
        var bbox = this.getBbox(feature);
        return bbox ? {x: (bbox[0] + bbox[2]) / 2, y: (bbox[1] + bbox[3]) / 2} : undefined;
    },

    getBbox: function(feature) {
        switch (feature.geometry.type.toLowerCase()) {
            case 'point':
                var minx = feature.geometry.coordinates[0] - 0.001;
                var miny = feature.geometry.coordinates[1] - 0.001;
                return [minx, miny, minx + 0.002, miny + 0.002];

            case 'linestring':
                var coords = feature.geometry.coordinates;
                minx = coords[0][0];
                miny = coords[0][1];
                var maxx = minx;
                var maxy = miny;
                $.each(coords, function(_, v) {
                    minx = Math.min(minx, v[0]);
                    miny = Math.min(miny, v[1]);
                    maxx = Math.max(maxx, v[0]);
                    maxy = Math.max(maxy, v[1]);
                });
                return [minx, miny, maxx, maxy];

            case 'polygon':
                coords = feature.geometry.coordinates;
                minx = coords[0][0][0];
                miny = coords[0][0][1];
                maxx = minx;
                maxy = miny;
                $.each(coords, function(_, v) {
                    $.each(v, function(_, v2) {
                        minx = Math.min(minx, v2[0]);
                        miny = Math.min(miny, v2[1]);
                        maxx = Math.max(maxx, v2[0]);
                        maxy = Math.max(maxy, v2[1]);
                    });
                });
                return [minx, miny, maxx, maxy];
        }
        return undefined;
    },

    renderPoint: function(canvas, feature) {
        var pt = {
            x: feature.geometry.coordinates[0],
            y: feature.geometry.coordinates[1]
        };
        if (isNaN(pt.x) || isNaN(pt.y)) return;
        if (!feature.preview) {
            pt = $('#mapframe1').mapbender().convertRealToPixel(pt);
        }
        if (isNaN(pt.x) || isNaN(pt.y)) return;
        // is this the right place and way to fix it?
        if (feature.properties['icon']) {
            feature.properties['marker-symbol'] = feature.properties['icon'];
            feature.properties['marker-type'] = 'custom';
            delete feature.properties['icon'];
        }
        if (feature.properties['iconOffsetX']) {
            feature.properties['marker-offset-x'] = feature.properties['iconOffsetX'];
            delete feature.properties['iconOffsetX'];
        }
        if (feature.properties['iconOffsetY']) {
            feature.properties['marker-offset-y'] = feature.properties['iconOffsetY'];
            delete feature.properties['iconOffsetY'];
        }
        if (feature.properties['marker-type'] === 'custom') {
            var size = 32;
            if (feature.properties['marker-size']) {

                if (feature.properties['marker-size'] === 'large') {
                    size = 64;
                }
                else if (feature.properties['marker-size'] === 'small') {
                    size = 16;
                }
                else if (feature.properties['marker-size'] === 'medium') {
                    size = size;
                }
                else {

                    size = feature.properties['marker-size'];

                }
            }
            var offx = 0,
                offy = 0;
            if (feature.preview) {
                size = 20;
            } else {
                offx = feature.properties['marker-offset-x'] || 0;
                offy = feature.properties['marker-offset-y'] || 0;
                offx = parseInt(offx);
                offy = parseInt(offy);
            }
            var img = canvas.image(feature.properties['marker-symbol'], pt.x + offx, pt.y + offy, size, size).node;
            if (img.setAttributeNS) {
                img.setAttributeNS(null, "preserveAspectRatio", "xMidYMid meet");
            }
        } else {
            var size = 32;
            if (feature.properties['marker-size']) {
                if (feature.properties['marker-size'] === 'large') {
                    size = 64;
                }
                if (feature.properties['marker-size'] === 'small') {
                    size = 16;
                }
            }
            if (feature.preview) {
                size = 20;
            }

            if (!this.icons) {
                window.setTimeout($.proxy(function() {
                    this.renderPoint(canvas, feature);
                }, this), 100);
                return;
            }

            $.each(this.icons.icons, function(_, v) {
                if (v.properties.name === (feature.properties['marker-symbol'] + '-24')) {
                    $.each(v.icon.paths, function(_, p) {
                        var raph = Raphael();
                        var tmp = raph.path(p);
                        var box = tmp.getBBox();
                        tmp.remove();
                        $(raph.canvas).remove();
                        var path = canvas.path(p);

                        var fac;
                        if (box.width > box.height) {
                            fac = size / box.width;
                        } else {
                            fac = size / box.height;
                        }

                        if (feature.preview) {
                            fac = fac * 0.7;
                        }

                        // center icon on 0/0, then scale to size, then translate to actual point
                        // for preview, just start at 0/0
                        if (feature.preview) {
                            path.translate(-box.x, -box.y);
                        } else {
                            path.translate(-box.x - box.width / 2, -box.y - box.height / 2);
                        }
                        // if you get a chrome error 'Invalid value for <path> attribute transform="  "' see:
                        // http://forum.wakanda.org/showthread.php?6753-error-raphael-min.js-7&p=31624&viewfull=1
                        path.scale(fac, fac, 0, 0);
                        if (!feature.preview) {
                            path.translate(pt.x, pt.y);
                        }

                        path.attr('fill', feature.properties['marker-color']);
                        path.attr('stroke', 'black');
                    });
                }
            });
        }
    },

    renderLine: function(canvas, feature) {
        var map = $('#mapframe1').mapbender();
        var self = this;
        var path;
        $.each(feature.geometry.coordinates, function(_, v) {
            var pt = {
                x: v[0],
                y: v[1]
            };
            if (!feature.preview) {
                pt = map.convertRealToPixel(pt);
            }
            if (isNaN(pt.x) || isNaN(pt.y)) return;
            if (!path) {
                path = 'M' + pt.x + ' ' + pt.y;
            } else {
                path += 'L' + pt.x + ' ' + pt.y;
            }
        });

        canvas.path(path).attr(feature.properties);
    },

    renderPolygon: function(canvas, feature) {
        var map = $('#mapframe1').mapbender();
        var self = this;
        var path;
        $.each(feature.geometry.coordinates[0], function(_, v) {
            var pt = {
                x: v[0],
                y: v[1]
            };
            if (!feature.preview) {
                pt = map.convertRealToPixel(pt);
            }
            if (isNaN(pt.x) || isNaN(pt.y)) return;
            if (!path) {
                path = 'M' + pt.x + ' ' + pt.y;
            } else {
                path += 'L' + pt.x + ' ' + pt.y;
            }
        });
        canvas.path(path + 'Z').attr(feature.properties);
    },
    renderLabel: function(canvas, feature) {
        var label;
        if(feature.label && (label=feature.properties[feature.label])){
            var map = $('#mapframe1').mapbender();
            var self = this;
            var path;
            var centroid = this.getCentroid(feature);
            var pt = map.convertRealToPixel(centroid);
            canvas.text(pt.x, pt.y, label);
        }
    },
    
    renderFeature: function(canvas) {
        var self = this;
        return function(_, feature) {
            try {
                if (feature.display === false) {
                    return;
                }
                switch (feature.geometry.type.toLowerCase()) {
                    case 'point':
                        self.renderPoint(canvas, feature);
                        break;
                    case 'linestring':
                        self.renderLine(canvas, feature);
                        break;
                    case 'polygon':
                        self.renderPolygon(canvas, feature);
                        break;
                };
                self.renderLabel(canvas, feature);
            } catch (e) {
                //console && console.log('Problem rendering feature', feature, e)
            }
        }
    },

    renderPreview: function(feature, target, size) {
        var canvas;
        if (size) {
            canvas = Raphael(target, size, size);
        } else {
            canvas = Raphael(target, 20, 20);
            size = 20;
        }

        var feat = {
            geometry: {
                type: feature.geometry.type
            },
            properties: feature.properties,
            preview: true
        };

        var min = size / 8;
        var max = size - size / 8;
        switch (feature.geometry.type.toLowerCase()) {
            case 'point':
                feat.geometry.coordinates = [0, 0];
                break;
            case 'linestring':
                feat.geometry.coordinates = [
                    [min, max],
                    [max, min]
                ];
                break;
            case 'polygon':
                feat.geometry.coordinates = [
                    [
                        [min, min],
                        [min, max],
                        [max, max],
                        [max, min]
                    ]
                ];
                break;
        };

        this.renderFeature(canvas)(null, feat);
    },

    render: function() {
        if (this.creatingPhase) {
            return;
        }
        var target = $('#kml-rendering-pane');
        var map = $('#mapframe1').mapbender();
        this.targetProj = new Proj4js.Proj(map.getSrs());
        var self = this;
        if (target.length == 0) {
            $('#mapframe1').append('<div id="kml-rendering-pane" style="position: absolute; top: 0px; left: 0px; z-index: 80;"></div>');
        }
        target = $('#kml-rendering-pane').html('').get(0);
        var canvas = Raphael(target, map.getWidth(), map.getHeight());

        var order = this.kmlOrder.slice(0);
        order.reverse();

        $.each(order, function(_, url) {
            var item = self._kmls[url];
            if (!item) {
                return;
            }
            var feats = item.data.features;

            if (!map.getSrs().match(/:4326/)) {
                if (!self.cache[url]) {
                    self.cache[url] = {};
                }

                feats = self.cache[url][map.getSrs()];
                if (!feats) {
                    // check if feature or featureCollection
                    // feature: item.data = item.data.features;
                    if (item.data.type != "Feature") {

                        $.each(item.data.features, function(_, v) {
                            self.fixupFeature(v);
                        });
                        $.ajax({
                            url: '../php/transformgeojson.php?targetEPSG=' + map.getSrs(),
                            type: 'POST',
                            data: JSON.stringify(item.data.features),
                            success: function(data) {
                                if (!$.isArray(data)) {
                                    data = JSON.parse(data);
                                }
                                self.cache[url][map.getSrs()] = data;
                                self.render();
                            }
                        });
                        return;

                    } else {

                        self.fixupFeature(item.data);

                        $.ajax({
                            url: '../php/transformgeojson.php?targetEPSG=' + map.getSrs(),
                            type: 'POST',
                            data: JSON.stringify(item.data),
                            success: function(data) {
                                if (!$.isArray(data)) {
                                    data = JSON.parse(data);
                                }
                                self.cache[url][map.getSrs()] = data;
                                self.render();
                            }
                        });
                        return;
                    }
                }
            }

            if (item.display && feats) {
                $.each(feats, $.proxy(self, self.renderFeature(canvas)));
            }
        });
    },

    fixupFeature: function(feat) {
        if (feat.geometry.type === 'Polygon') {
            $.each(feat.geometry.coordinates, function(_, coords) {
                if (coords[0][0] !== coords[coords.length - 1][0] || coords[0][1] !== coords[coords.length - 1][1]) {
                    coords.push(coords[0]);
                }
            });
        }

        if (!feat.properties.uuid) {
            feat.properties.uuid = UUID.genV1().toString();
        }
        if (!feat.properties.updated) {
            feat.properties.updated = new Date().toISOString();
        }
        if (!feat.properties.created) {
            feat.properties.created = new Date().toISOString();
        }
    }

};

var displayKML = $.extend({}, displayFeatures, {
    _endpointURL: "../php/kml2transformedgeojson.php",
    _eventNamespace: "kml"
});
$.widget('ui.kml', displayKML);
$.widget('ui.geojson', displayKML);
