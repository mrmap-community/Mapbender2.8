$.widget("mapbender.mb_digitize", {
    options: {
        opacity: 0.6,
        digitizePointDiameter: 10,
        polygonFillSnapped: "#FC3",
        polygonFillDefault: "#FFF",
        polygonStrokeWidthDefault: 1,
        polygonStrokeWidthSnapped: 5,
        lineStrokeDefault: "#C9F",
        lineStrokeSnapped: "#F30",
        lineStrokeWidthDefault: 3,
        lineStrokeWidthSnapped: 5,
        pointFillSnapped: "#F90",
        pointFillDefault: "#CCF",
        pointStrokeDefault: "#FC3",
        pointStrokeSnapped: "#F30",
        pointStrokeWidthDefault: 2,
        type: null,
        editedFeature: null
    },
    _digitizePoints: [],
    _map: undefined,
    _srs: undefined,
    _polygonIsInvalid: false,
    _currentPolygonIsInvalid: false,
    _canvas: undefined,
    reinitializeProxy: null,
    addPointProxy: null,

    _isPolygon: function (pos) {

        var len = this._digitizePoints.length;

        var max = pos ? len - 1 : len - 2;

        if (pos && len < 2 || !pos && len < 3) {
            return false;
        }

        var posLocal = pos ? pos : this._digitizePoints[len - 1];

        var p0 = this._digitizePoints[0].pos;
        var pn = this._digitizePoints[max].pos;
        for (var i = 0; i < max; i++)  {
            var pi = this._digitizePoints[i].pos;
            var pj = this._digitizePoints[i + 1].pos;

            if (i > 0 && this._lineIntersect(
                pi.x, pi.y, pj.x, pj.y,
                p0.x, p0.y, posLocal.x, posLocal.y)
               ) {
                return false;
            }
            if (this._lineIntersect(
                pi.x, pi.y, pj.x, pj.y,
                pn.x, pn.y, posLocal.x, posLocal.y)
               ) {
                this._currentPolygonIsInvalid = true;
                return false;
            }
        }
        this._currentPolygonIsInvalid = false;
        return true;
    },
    _lineIntersect: function ( x1, y1, x2, y2, x3, y3, x4, y4 ) {
        var isOnSegment = function (xi, yi, xj, yj, xk, yk) {
            // changed <= to < so the segments are allowed to touch!
            return (xi < xk || xj < xk) &&
                (xk < xi || xk < xj) &&
                (yi < yk || yj < yk) &&
                (yk < yi || xk < yj);
        };

        var computeDirection = function (xi, yi, xj, yj, xk, yk) {
            var a = (xk - xi) * (yj - yi);
            var b = (xj - xi) * (yk - yi);
            return a < b ? -1 : a > b ? 1 : 0;
        };

        var e1 = computeDirection(x3, y3, x4, y4, x1, y1);
        var e2 = computeDirection(x3, y3, x4, y4, x2, y2);
        var e3 = computeDirection(x1, y1, x2, y2, x3, y3);
        var e4 = computeDirection(x1, y1, x2, y2, x4, y4);
        return (((e1 > 0 && e2 < 0) || (e1 < 0 && e2 > 0)) &&
                ((e3 > 0 && e4 < 0) || (e3 < 0 && e4 > 0))) ||
            (e1 === 0 && isOnSegment(x3, y3, x4, y4, x1, y1)) ||
            (e2 === 0 && isOnSegment(x3, y3, x4, y4, x2, y2)) ||
            (e3 === 0 && isOnSegment(x1, y1, x2, y2, x3, y3)) ||
            (e4 === 0 && isOnSegment(x1, y1, x2, y2, x4, y4));
    },
    _toRad: function (deg) {
        return deg * Math.PI/180;
    },
    _isPointSnapped: function (p1, p2) {
        return p1.dist(p2) <= this.options.digitizePointDiameter/2;
    },
    _isFirstPointSnapped: function (p) {
        if (this._digitizePoints.length > 0 ) {
            var pos0 = this._digitizePoints[0].mousePos;
            if (this._digitizePoints.length > 2 && this._isPointSnapped(pos0, p)) {
                return true;
            }
        }
        return false;
    },
    _isLastPointSnapped: function (p) {
        if (this._digitizePoints.length > 0 ) {
            var posn = this._digitizePoints[this._digitizePoints.length - 1].mousePos;
            if (this._digitizePoints.length > 1 && this._isPointSnapped(posn, p)) {
                return true;
            }
        }
        return false;
    },

    _draw: function (pos, drawOptions) {
        this._canvas.clear();

        var str_path = "";

        if(this.options.type) {
            var pts = [];

            switch(this.options.type) {
                case 'line':
                case 'polygon':
                for(i = 0; i < this._digitizePoints.length; ++i) {
                    var pt = this._digitizePoints[i].mousePos;
                    str_path += (i === 0) ? 'M' : 'L';
                    str_path += pt.x + ' ' + pt.y;
                }
                if(pos) {
                    str_path += (this._digitizePoints.length === 0) ? 'M' : 'L';
                    str_path += pos.mousePos.x + ' ' + pos.mousePos.y;
                }
                case 'point':
                for(var i = 0; i < this._digitizePoints.length; ++i) {
                    var pt = this._digitizePoints[i].mousePos;
                    pts.push(this._canvas.circle(pt.x, pt.y, this.options.digitizePointDiameter));
                }
                if(pos) {
                    pts.push(this._canvas.circle(pos.mousePos.x, pos.mousePos.y, this.options.digitizePointDiameter));
                }
                break;
            }

            for(i = 0; i < pts.length; ++i) {
                pts[i].attr({
                    fill: drawOptions && drawOptions.highlightFirst ?
                        this.options.pointFillSnapped : this.options.pointFillDefault,
                    "fill-opacity": this.options.opacity,
                    stroke: drawOptions.highlightFirst || drawOptions.highlightLast ?
                        this.options.pointStrokeSnapped: this.options.pointStrokeDefault,
                    "stroke-width": this.options.pointStrokeWidthDefault
                });
            }

            if(pts.length > 1) {
                if(this.options.type === 'line' || this.options.type === 'polygon') {
                    var line = this._canvas.path(str_path);

                    line.attr({
                        stroke: drawOptions && (drawOptions.highlightFirst || drawOptions.highlightLast) ?
                            this.options.lineStrokeSnapped : this.options.lineStrokeDefault,
                        "stroke-width": drawOptions && drawOptions.highlightLast ?
                            this.options.lineStrokeWidthSnapped : this.options.lineStrokeWidthDefault
                    })
                }

                if(this.options.type === 'polygon') {
                    var poly = this._canvas.path(str_path + 'Z');

                    poly.attr({
                        fill: drawOptions.highlightFirst ?
                            this.options.polygonFillSnapped : this.options.polygonFillDefault,
                        stroke: drawOptions.highlightFirst || drawOptions.highlightLast ?
                            this.options.lineStrokeSnapped: this.options.lineStrokeDefault,
                        "stroke-width": drawOptions.highlightFirst ?
                            this.options.polygonStrokeWidthSnapped : this.options.polygonStrokeWidthDefault,
                        opacity: this.options.opacity
                    });
                }
            }
            if(this.addingVertex) {
                var last;
                for(i = 0; i < this._digitizePoints.length; ++i) {
                    var pt = this._digitizePoints[i].mousePos;
                    if(last) {
                        var x = last.x + (pt.x - last.x) / 2;
                        var y = last.y + (pt.y - last.y) / 2;
                        this._canvas.circle(x, y, this.options.digitizePointDiameter)
                            .attr({
                                fill: this.options.pointFillSnapped,
                                "fill-opacity": this.options.opacity,
                                stroke: this.options.pointStrokeSnapped,
                                "stroke-width": this.options.pointStrokeWidthDefault
                            });
                    }
                    last = pt;
                }
            }
        }
    },

    _digitize: function (e) {
        var mousePos = this._map.getMousePosition(e);
        var firstPointSnapped = this._isFirstPointSnapped(mousePos)
                             && !this._polygonIsInvalid;
        var lastPointSnapped = this._isLastPointSnapped(mousePos);

        if(this.options.type === 'line') {
            firstPointSnapped = false;
        }

        var digitizeData = {
            pos: {
                mousePos: mousePos,
                pos: firstPointSnapped ?
                    this._digitizePoints[0].pos : lastPointSnapped ?
                    this._digitizePoints[this._digitizePoints.length - 1].pos :
                    this._map.convertPixelToReal(mousePos)
            }
        };

        this._trigger("update", null, digitizeData);

        this._draw(digitizeData.pos, {
            highlightFirst: firstPointSnapped,
            highlightLast: lastPointSnapped,
            drawPoints: true
        });
    },

    _reinitialize: function (e) {
        this.element
            .unbind("click", this.reinitializeProxy)
        this._trigger("reinitialize", e);
        return false;
    },

    _addLastPoint: function (e) {
        this._trigger("lastpointadded", e);

        this.element.unbind("click", this.addPointProxy)
            .unbind("mousemove", this._digitize)
            .css("cursor", "auto")
            .bind("click", this.reinitializeProxy);
    },

    _addPoint: function (e) {
        if(this.isPaused) {
            return;
        }

        var mousePos = this._map.getMousePosition(e);

        var len = this._digitizePoints.length;

        var data = {
            pos: {
                mousePos: mousePos,
                pos: this._map.convertPixelToReal(mousePos)
            }
        };

        this._trigger("pointadded", e, data);

        var firstPointSnapped = this._isFirstPointSnapped(mousePos);
        var lastPointSnapped = this._isLastPointSnapped(mousePos);

        if(this.options.type === 'line') {
            firstPointSnapped = false;
        }

        this._isPolygon(this._digitizePoints, data.pos);
        if (this._currentPolygonIsInvalid) {
            this._polygonIsInvalid = true;
        }
        this._currentPolygonIsInvalid = false;

        if (lastPointSnapped || firstPointSnapped) {
            this._draw(data.pos, {
                highlightFirst: firstPointSnapped,
                highlightLast: lastPointSnapped,
                drawPoints: false
            });
            this._digitizePoints.closedPolygon = this.options.type === 'polygon' && this._digitizePoints.length > 2;
            this._digitizePoints.closedLine = lastPointSnapped;
            this._addLastPoint(e);
        }
        else {
            this._digitizePoints.push(data.pos);

            lastPointSnapped = this._isLastPointSnapped(mousePos);
            this._draw(data.pos, {
                highlightFirst: firstPointSnapped,
                highlightLast: lastPointSnapped,
                drawPoints: true
            });
        }

        if(this.options.type === 'point') {
            this.element.unbind('click', this.addPointProxy)
                .unbind("mousemove", this._digitize)
                .css('cursor', 'auto')
                .bind('click', this.reinitializeProxy);
            this._trigger("lastpointadded", e);
        }

        return true;
    },

    _redraw: function () {
        if (!$(this.element).data("mb_digitize")) {
            return;
        }
        var len = this._digitizePoints.length;
        if (len === 0) {
            return;
        }
        for (var i = 0; i < len; i++) {
            var p = this._digitizePoints[i];
            p.mousePos = this._map.convertRealToPixel(p.pos);
        }
        if (this._digitizePoints.closedPolygon) {
            this._draw(undefined, {
                highlightFirst: true,
                highlightLast: false,
                drawPoints: false
            });
        }
        else if (this._digitizePoints.closedLine) {
            this._draw(undefined, {
                highlightFirst: false,
                highlightLast: true,
                drawPoints: false
            });
        }
        else {
            this._draw(undefined, {
                highlightFirst: false,
                highlightLast: false,
                drawPoints: false
            });
        }
    },

    _init: function () {
        this._digitizePoints = [];
        this._canvas.clear();

        if(this.options.editedFeature) {
            this._digitizePoints = this.coordinatesToDigitizePoints(this.options.editedFeature.geometry.coordinates);
            this._redraw();
        } else {
            this.startDigitizing();
        }
    },

    startDigitizing: function() {
        this.element
            .bind("click", this.addPointProxy)
            .bind("mousemove", $.proxy(this, "_digitize"))
            .css("cursor", "crosshair");
    },

    pointFromEvent: function(e) {
        var mousePos = this._map.getMousePosition(e);

        var kml = $('#mapframe1').data('kml');
        var pos = this._map.convertPixelToReal(mousePos);

        return {
            mousePos: mousePos,
            pos: pos
        };
    },

    startMoving: function(e) {
        this.moveStartPoint = this.pointFromEvent(e);
        this.originalDigitizePoints = this._digitizePoints;
        this.moving = true;
    },

    move: function(e) {
        if(this.moving) {
            var self = this;
            var pt = this.pointFromEvent(e);
            var diff = {
                mousePos: {
                    x: this.moveStartPoint.mousePos.x - pt.mousePos.x,
                    y: this.moveStartPoint.mousePos.y - pt.mousePos.y
                },
                pos: {
                    x: this.moveStartPoint.pos.x - pt.pos.x,
                    y: this.moveStartPoint.pos.y - pt.pos.y
                }
            };
            this._digitizePoints = [];
            $.each(this.originalDigitizePoints, function(_, v) {
                self._digitizePoints.push({
                    mousePos: {
                        x: v.mousePos.x - diff.mousePos.x,
                        y: v.mousePos.y - diff.mousePos.y
                    },
                    pos: {
                        x: v.pos.x - diff.pos.x,
                        y: v.pos.y - diff.pos.y
                    }
                });
            });
            this._redraw();
        }
    },

    stopMoving: function(e) {
        this.moving = false;
        this._trigger('featuremodified', e);
    },

    startVertexMoving: function(e) {

        var self = this;
        var pt = this.pointFromEvent(e);
        self.dualPoint = {};
        self.dualVertexMove = false;
        if ( self._isFirstPointSnapped(pt.mousePos) ) {
            self.dualVertexMove = true;
        } else if( self._isLastPointSnapped(pt.mousePos) ) {
            self.dualVertexMove = true;
        }

        this.vertexMoving = false;
        $.each(this._digitizePoints, function(k, v) {
            if(self._isPointSnapped(v.mousePos, pt.mousePos)) {
                self.vertexMoving = true;
                self.vertexMovingIndex = k;
                return;
            }
        });
    },

    vertexMove: function(e) {
        var self = this;
        var pt = this.pointFromEvent(e);
        if(this.vertexMoving) {
            this._digitizePoints[this.vertexMovingIndex] = pt;
            if (self.dualVertexMove) {
                this._digitizePoints[0] = pt;
            }
        }
        this._redraw();
    },

    stopVertexMoving: function(e) {
        var self = this;
        self.dualVertexMove = false;
        this.vertexMoving = false;
        this._trigger('featuremodified', e);
    },

    moveVertexMode: function() {
        this.modeOff();
        this.addingVertex = false;
        this.element
            // .unbind('mousedown').unbind('mousemove').unbind('mouseup')
            .bind('mousedown', this.startVertexMovingProxy)
            .bind('mousemove', this.vertexMoveProxy)
            .bind('mouseup', this.stopVertexMovingProxy)
            .css('cursor', 'crosshair');
    },

    addVertex: function(e) {
        var pt = this.pointFromEvent(e);
        var last;
        for(var i = 0; i < this._digitizePoints.length; ++i) {
            if(last) {
                var cur = new Mapbender.Point(
                    last.mousePos.x + (this._digitizePoints[i].mousePos.x - last.mousePos.x) / 2,
                    last.mousePos.y + (this._digitizePoints[i].mousePos.y - last.mousePos.y) / 2
                );

                if(this._isPointSnapped(cur, pt.mousePos)) {
                    this._digitizePoints.splice(i, 0, pt);
                    this.vertexMoving = true;
                    this.vertexMovingIndex = i;
                    return;
                }
            }
            last = this._digitizePoints[i];
        }
    },

    addVertexMode: function() {
        this.modeOff();
        this.addingVertex = true;
        this.element
            .bind('mousedown', $.proxy(this, 'addVertex'))
            .bind('mousemove', $.proxy(this, 'vertexMove'))
            .bind('mouseup', $.proxy(this, 'stopVertexMoving'))
            .css('cursor', 'crosshair');
        this._redraw();
    },

    deleteVertex: function(e) {
        var pt = this.pointFromEvent(e);
        var self = this;
        $.each(this._digitizePoints, function(k, v) {
            if(v && self._isPointSnapped(v.mousePos, pt.mousePos)) {
                self._digitizePoints.splice(k, 1);
            }
        });
        this._redraw();
        this._trigger('featuremodified', e);
        this.element.bind('mousedown', $.proxy(this, 'deleteVertex'));
    },

    deleteVertexMode: function() {
        this.modeOff();
        this.element
            .bind('mousedown', $.proxy(this, 'deleteVertex'))
            .css('cursor', 'crosshair');
    },

    moveMode: function() {
        this.modeOff();
        this.element
            .bind("mousedown", $.proxy(this, 'startMoving'))
            .bind("mousemove", $.proxy(this, 'move'))
            .bind('mouseup', $.proxy(this, 'stopMoving'))
            .css("cursor", "crosshair");
    },

    modeOff: function() {
        // this.element
        //     .unbind('mousedown').unbind('mousemove').unbind('mouseup').unbind('click', this.addPointProxy);
        this.element
            .unbind('mousedown',this.startVertexMovingProxy)
            .unbind('mousedown',this.addVertexProxy)
            .unbind('mousedown',this.deleteVertexProxy)
            .unbind('mousedown',this.startMovingProxy)
            .unbind('mousemove',this._digitizeProxy)
            .unbind('mousemove',this.vertexMoveProxy)
            .unbind('mousemove',this.moveProxy)
            .unbind('mouseup',this.stopVertexMovingProxy)
            .unbind('mouseup',this.stopMovingProxy)
            .unbind('click', this.addPointProxy);
        this.addingVertex = false;
        this._redraw();
        // this.deactivate();
        // this.destroy();
    },

    coordinatesToDigitizePoints: function(coords) {
        var map = $('#mapframe1').mapbender();
        var kml = $('#mapframe1').data('kml');
        if($.isArray(coords[0])) {
            var pts = [];
            $.each(coords, function(_, v) {
                if($.isArray(v[0])) {
                    $.each(v, function(_, v2) {
                        var pos = {x: v2[0], y: v2[1]};
                        Proj4js.transform(kml.wgs84, kml.targetProj, pos);
                        var mousePos = map.convertRealToPixel(pos);
                        pts.push({pos: pos, mousePos: mousePos});
                    });
                } else {
                    var pos = {x: v[0], y: v[1]};
                    Proj4js.transform(kml.wgs84, kml.targetProj, pos);
                    var mousePos = map.convertRealToPixel(pos);
                    pts.push({pos: pos, mousePos: mousePos});
                }
            });
            return pts;
        } else {
            var pos = {x: coords[0], y: coords[1]};
            Proj4js.transform(kml.wgs84, kml.targetProj, pos);
            var mousePos = map.convertRealToPixel(pos);
            return [{pos: pos, mousePos: mousePos}];
        }
    },

    _create: function () {
        this.reinitializeProxy = $.proxy(this._reinitialize, this);
        this.addPointProxy = $.proxy(this._addPoint, this);
        // mousedown handler
        this.startVertexMovingProxy = $.proxy(this.startVertexMoving, this);
        this.addVertexProxy = $.proxy(this.addVertex, this);
        this.deleteVertexProxy = $.proxy(this.deleteVertex, this);
        this.startMovingProxy = $.proxy(this.startMoving, this);
        // mousemove handler
        this._digitizeProxy = $.proxy(this._digitize, this);
        this.vertexMoveProxy = $.proxy(this.vertexMove, this);
        this.moveProxy = $.proxy(this.move, this);
        //mouseup handler
        this.stopVertexMovingProxy = $.proxy(this.stopVertexMoving, this);
        this.stopMovingProxy = $.proxy(this.stopMoving, this);



        this._digitizePoints = [];

        // ":maps" is a Mapbender selector which
        // checks if an element is a Mapbender map
        this.element = this.element.filter(":maps");

        if (!this.element.jquery || this.element.size() === 0) {
            $.error("This widget must be applied to a Mapbender map.");
        }

        this._map = this.element.mapbender();
        this._map.events.afterMapRequest.register($.proxy(this._redraw, this));
        this._srs = this._map.getSrs();

        this._$canvas = $("<div id='digitize_canvas' />").css({
            "z-index": 1000,
            "position": "absolute"
        }).appendTo(this.element);
        this._canvas = Raphael(this._$canvas.get(0), this._map.getWidth(), this._map.getHeight());
        mb_registerPanSubElement($(this._canvas.canvas).parent().get(0));
    },
    // the digitized geometry will be available, the events will be deleted
    deactivate: function () {
        this.element
            .unbind("click", this.addPointProxy)
            .unbind("mousemove", this._digitize)
            .unbind("click", this.reinitializeProxy)
            .css("cursor", "default");

    },
    // delete everything
    destroy: function () {
        this.deactivate();
        this._canvas.clear();
        this._digitizePoints = [];
        this._$canvas.remove();
        this._map.events.afterMapRequest.unregister($.proxy(this._redraw, this));

        $.Widget.prototype.destroy.apply(this, arguments); // default destroy
        $(this.element).data("mb_digitize", null);
    }
});
