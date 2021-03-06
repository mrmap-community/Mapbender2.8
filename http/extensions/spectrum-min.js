! function(window, $, undefined) {
    function paletteTemplate(p, color, className, tooltipFormat) {
        for (var html = [], i = 0; i < p.length; i++) {
            var current = p[i];
            if (current) {
                var tiny = tinycolor(current),
                    c = tiny.toHsl().l < .5 ? "sp-thumb-el sp-thumb-dark" : "sp-thumb-el sp-thumb-light";
                c += tinycolor.equals(color, current) ? " sp-thumb-active" : "";
                var formattedString = tiny.toString(tooltipFormat || "rgb"),
                    swatchStyle = rgbaSupport ? "background-color:" + tiny.toRgbString() : "filter:" + tiny.toFilter();
                html.push('<span title="' + formattedString + '" data-color="' + tiny.toRgbString() + '" class="' + c + '"><span class="sp-thumb-inner" style="' + swatchStyle + ';" /></span>')
            } else {
                var cls = "sp-clear-display";
                html.push('<span title="No Color Selected" data-color="" style="background-color:transparent;" class="' + cls + '"></span>')
            }
        }
        return "<div class='sp-cf " + className + "'>" + html.join("") + "</div>"
    }

    function hideAll() {
        for (var i = 0; i < spectrums.length; i++) spectrums[i] && spectrums[i].hide()
    }

    function instanceOptions(o, callbackContext) {
        var opts = $.extend({}, defaultOpts, o);
        return opts.callbacks = {
            move: bind(opts.move, callbackContext),
            change: bind(opts.change, callbackContext),
            show: bind(opts.show, callbackContext),
            hide: bind(opts.hide, callbackContext),
            beforeShow: bind(opts.beforeShow, callbackContext)
        }, opts
    }

    function spectrum(element, o) {
        function applyOptions() {
            if (opts.showPaletteOnly && (opts.showPalette = !0), opts.palette) {
                palette = opts.palette.slice(0), paletteArray = $.isArray(palette[0]) ? palette : [palette], paletteLookup = {};
                for (var i = 0; i < paletteArray.length; i++)
                    for (var j = 0; j < paletteArray[i].length; j++) {
                        var rgb = tinycolor(paletteArray[i][j]).toRgbString();
                        paletteLookup[rgb] = !0
                    }
            }
            container.toggleClass("sp-flat", flat), container.toggleClass("sp-input-disabled", !opts.showInput), container.toggleClass("sp-alpha-enabled", opts.showAlpha), container.toggleClass("sp-clear-enabled", allowEmpty), container.toggleClass("sp-buttons-disabled", !opts.showButtons), container.toggleClass("sp-palette-disabled", !opts.showPalette), container.toggleClass("sp-palette-only", opts.showPaletteOnly), container.toggleClass("sp-initial-disabled", !opts.showInitial), container.addClass(opts.className).addClass(opts.containerClassName), reflow()
        }

        function initialize() {
            function palletElementClick(e) {
                return e.data && e.data.ignore ? (set($(this).attr("data-color")), move()) : (set($(this).attr("data-color")), move(), updateOriginalInput(!0), hide()), !1
            }
            if (IE && container.find("*:not(input)").attr("unselectable", "on"), applyOptions(), shouldReplace && boundElement.after(replacer).hide(), allowEmpty || clearButton.hide(), flat) boundElement.after(container).hide();
            else {
                var appendTo = "parent" === opts.appendTo ? boundElement.parent() : $(opts.appendTo);
                1 !== appendTo.length && (appendTo = $("body")), appendTo.append(container)
            }
            updateSelectionPaletteFromStorage(), offsetElement.bind("click.spectrum touchstart.spectrum", function(e) {
                disabled || toggle(), e.stopPropagation(), $(e.target).is("input") || e.preventDefault()
            }), (boundElement.is(":disabled") || opts.disabled === !0) && disable(), container.click(stopPropagation), textInput.change(setFromTextInput), textInput.bind("paste", function() {
                setTimeout(setFromTextInput, 1)
            }), textInput.keydown(function(e) {
                13 == e.keyCode && setFromTextInput()
            }), cancelButton.text(opts.cancelText), cancelButton.bind("click.spectrum", function(e) {
                e.stopPropagation(), e.preventDefault(), hide("cancel")
            }), clearButton.attr("title", opts.clearText), clearButton.bind("click.spectrum", function(e) {
                e.stopPropagation(), e.preventDefault(), isEmpty = !0, move(), flat && updateOriginalInput(!0)
            }), chooseButton.text(opts.chooseText), chooseButton.bind("click.spectrum", function(e) {
                e.stopPropagation(), e.preventDefault(), isValid() && (updateOriginalInput(!0), hide())
            }), draggable(alphaSlider, function(dragX, dragY, e) {
                currentAlpha = dragX / alphaWidth, isEmpty = !1, e.shiftKey && (currentAlpha = Math.round(10 * currentAlpha) / 10), move()
            }, dragStart, dragStop), draggable(slider, function(dragX, dragY) {
                currentHue = parseFloat(dragY / slideHeight), isEmpty = !1, opts.showAlpha || (currentAlpha = 1), move()
            }, dragStart, dragStop), draggable(dragger, function(dragX, dragY, e) {
                if (e.shiftKey) {
                    if (!shiftMovementDirection) {
                        var oldDragX = currentSaturation * dragWidth,
                            oldDragY = dragHeight - currentValue * dragHeight,
                            furtherFromX = Math.abs(dragX - oldDragX) > Math.abs(dragY - oldDragY);
                        shiftMovementDirection = furtherFromX ? "x" : "y"
                    }
                } else shiftMovementDirection = null;
                var setSaturation = !shiftMovementDirection || "x" === shiftMovementDirection,
                    setValue = !shiftMovementDirection || "y" === shiftMovementDirection;
                setSaturation && (currentSaturation = parseFloat(dragX / dragWidth)), setValue && (currentValue = parseFloat((dragHeight - dragY) / dragHeight)), isEmpty = !1, opts.showAlpha || (currentAlpha = 1), move()
            }, dragStart, dragStop), initialColor ? (set(initialColor), updateUI(), currentPreferredFormat = preferredFormat || tinycolor(initialColor).format, addColorToSelectionPalette(initialColor)) : updateUI(), flat && show();
            var paletteEvent = IE ? "mousedown.spectrum" : "click.spectrum touchstart.spectrum";
            paletteContainer.delegate(".sp-thumb-el", paletteEvent, palletElementClick), initialColorContainer.delegate(".sp-thumb-el:nth-child(1)", paletteEvent, {
                ignore: !0
            }, palletElementClick)
        }

        function updateSelectionPaletteFromStorage() {
            if (localStorageKey && window.localStorage) {
                try {
                    var oldPalette = window.localStorage[localStorageKey].split(",#");
                    oldPalette.length > 1 && (delete window.localStorage[localStorageKey], $.each(oldPalette, function(i, c) {
                        addColorToSelectionPalette(c)
                    }))
                } catch (e) {}
                try {
                    selectionPalette = window.localStorage[localStorageKey].split(";")
                } catch (e) {}
            }
        }

        function addColorToSelectionPalette(color) {
            if (showSelectionPalette) {
                var rgb = tinycolor(color).toRgbString();
                if (!paletteLookup[rgb] && -1 === $.inArray(rgb, selectionPalette))
                    for (selectionPalette.push(rgb); selectionPalette.length > maxSelectionSize;) selectionPalette.shift();
                if (localStorageKey && window.localStorage) try {
                    window.localStorage[localStorageKey] = selectionPalette.join(";")
                } catch (e) {}
            }
        }

        function getUniqueSelectionPalette() {
            var unique = [];
            if (opts.showPalette)
                for (i = 0; i < selectionPalette.length; i++) {
                    var rgb = tinycolor(selectionPalette[i]).toRgbString();
                    paletteLookup[rgb] || unique.push(selectionPalette[i])
                }
            return unique.reverse().slice(0, opts.maxSelectionSize)
        }

        function drawPalette() {
            var currentColor = get(),
                html = $.map(paletteArray, function(palette, i) {
                    return paletteTemplate(palette, currentColor, "sp-palette-row sp-palette-row-" + i, opts.preferredFormat)
                });
            updateSelectionPaletteFromStorage(), selectionPalette && html.push(paletteTemplate(getUniqueSelectionPalette(), currentColor, "sp-palette-row sp-palette-row-selection", opts.preferredFormat)), paletteContainer.html(html.join(""))
        }

        function drawInitial() {
            if (opts.showInitial) {
                var initial = colorOnShow,
                    current = get();
                initialColorContainer.html(paletteTemplate([initial, current], current, "sp-palette-row-initial", opts.preferredFormat))
            }
        }

        function dragStart() {
            (0 >= dragHeight || 0 >= dragWidth || 0 >= slideHeight) && reflow(), container.addClass(draggingClass), shiftMovementDirection = null, boundElement.trigger("dragstart.spectrum", [get()])
        }

        function dragStop() {
            container.removeClass(draggingClass), boundElement.trigger("dragstop.spectrum", [get()])
        }

        function setFromTextInput() {
            var value = textInput.val();
            if (null !== value && "" !== value || !allowEmpty) {
                var tiny = tinycolor(value);
                tiny.ok ? (set(tiny), updateOriginalInput(!0)) : textInput.addClass("sp-validation-error")
            } else set(null), updateOriginalInput(!0)
        }

        function toggle() {
            visible ? hide() : show()
        }

        function show() {
            var event = $.Event("beforeShow.spectrum");
            return visible ? void reflow() : (boundElement.trigger(event, [get()]), void(callbacks.beforeShow(get()) === !1 || event.isDefaultPrevented() || (hideAll(), visible = !0, $(doc).bind("click.spectrum", hide), $(window).bind("resize.spectrum", resize), replacer.addClass("sp-active"), container.removeClass("sp-hidden"), reflow(), updateUI(), colorOnShow = get(), drawInitial(), callbacks.show(colorOnShow), boundElement.trigger("show.spectrum", [colorOnShow]))))
        }

        function hide(e) {
            if ((!e || "click" != e.type || 2 != e.button) && visible && !flat) {
                visible = !1, $(doc).unbind("click.spectrum", hide), $(window).unbind("resize.spectrum", resize), replacer.removeClass("sp-active"), container.addClass("sp-hidden");
                var colorHasChanged = !tinycolor.equals(get(), colorOnShow);
                colorHasChanged && (clickoutFiresChange && "cancel" !== e ? updateOriginalInput(!0) : revert()), callbacks.hide(get()), boundElement.trigger("hide.spectrum", [get()])
            }
        }

        function revert() {
            set(colorOnShow, !0)
        }

        function set(color, ignoreFormatChange) {
            if (tinycolor.equals(color, get())) return void updateUI();
            var newColor, newHsv;
            !color && allowEmpty ? isEmpty = !0 : (isEmpty = !1, newColor = tinycolor(color), newHsv = newColor.toHsv(), currentHue = newHsv.h % 360 / 360, currentSaturation = newHsv.s, currentValue = newHsv.v, currentAlpha = newHsv.a), updateUI(), newColor && newColor.ok && !ignoreFormatChange && (currentPreferredFormat = preferredFormat || newColor.format)
        }

        function get(opts) {
            return opts = opts || {}, allowEmpty && isEmpty ? null : tinycolor.fromRatio({
                h: currentHue,
                s: currentSaturation,
                v: currentValue,
                a: Math.round(100 * currentAlpha) / 100
            }, {
                format: opts.format || currentPreferredFormat
            })
        }

        function isValid() {
            return !textInput.hasClass("sp-validation-error")
        }

        function move() {
            updateUI(), callbacks.move(get()), boundElement.trigger("move.spectrum", [get()])
        }

        function updateUI() {
            textInput.removeClass("sp-validation-error"), updateHelperLocations();
            var flatColor = tinycolor.fromRatio({
                h: currentHue,
                s: 1,
                v: 1
            });
            dragger.css("background-color", flatColor.toHexString());
            var format = currentPreferredFormat;
            1 > currentAlpha && (0 !== currentAlpha || "name" !== format) && ("hex" === format || "hex3" === format || "hex6" === format || "name" === format) && (format = "rgb");
            var realColor = get({
                format: format
            }),
                displayColor = "";
            if (previewElement.removeClass("sp-clear-display"), previewElement.css("background-color", "transparent"), !realColor && allowEmpty) previewElement.addClass("sp-clear-display");
            else {
                var realHex = realColor.toHexString(),
                    realRgb = realColor.toRgbString();
                if (rgbaSupport || 1 === realColor.alpha ? previewElement.css("background-color", realRgb) : (previewElement.css("background-color", "transparent"), previewElement.css("filter", realColor.toFilter())), opts.showAlpha) {
                    var rgb = realColor.toRgb();
                    rgb.a = 0;
                    var realAlpha = tinycolor(rgb).toRgbString(),
                        gradient = "linear-gradient(left, " + realAlpha + ", " + realHex + ")";
                    IE ? alphaSliderInner.css("filter", tinycolor(realAlpha).toFilter({
                        gradientType: 1
                    }, realHex)) : (alphaSliderInner.css("background", "-webkit-" + gradient), alphaSliderInner.css("background", "-moz-" + gradient), alphaSliderInner.css("background", "-ms-" + gradient), alphaSliderInner.css("background", "linear-gradient(to right, " + realAlpha + ", " + realHex + ")"))
                }
                displayColor = realColor.toString(format)
            }
            opts.showInput && textInput.val(displayColor), opts.showPalette && drawPalette(), drawInitial()
        }

        function updateHelperLocations() {
            var s = currentSaturation,
                v = currentValue;
            if (allowEmpty && isEmpty) alphaSlideHelper.hide(), slideHelper.hide(), dragHelper.hide();
            else {
                alphaSlideHelper.show(), slideHelper.show(), dragHelper.show();
                var dragX = s * dragWidth,
                    dragY = dragHeight - v * dragHeight;
                dragX = Math.max(-dragHelperHeight, Math.min(dragWidth - dragHelperHeight, dragX - dragHelperHeight)), dragY = Math.max(-dragHelperHeight, Math.min(dragHeight - dragHelperHeight, dragY - dragHelperHeight)), dragHelper.css({
                    top: dragY + "px",
                    left: dragX + "px"
                });
                var alphaX = currentAlpha * alphaWidth;
                alphaSlideHelper.css({
                    left: alphaX - alphaSlideHelperWidth / 2 + "px"
                });
                var slideY = currentHue * slideHeight;
                slideHelper.css({
                    top: slideY - slideHelperHeight + "px"
                })
            }
        }

        function updateOriginalInput(fireCallback) {
            var color = get(),
                displayColor = "",
                hasChanged = !tinycolor.equals(color, colorOnShow);
            color && (displayColor = color.toString(currentPreferredFormat), addColorToSelectionPalette(color)), isInput && boundElement.val(displayColor), colorOnShow = color, fireCallback && hasChanged && (callbacks.change(color), boundElement.trigger("change", [color]))
        }

        function reflow() {
            dragWidth = dragger.width(), dragHeight = dragger.height(), dragHelperHeight = dragHelper.height(), slideWidth = slider.width(), slideHeight = slider.height(), slideHelperHeight = slideHelper.height(), alphaWidth = alphaSlider.width(), alphaSlideHelperWidth = alphaSlideHelper.width(), flat || (container.css("position", "absolute"), container.offset(getOffset(container, offsetElement))), updateHelperLocations(), opts.showPalette && drawPalette(), boundElement.trigger("reflow.spectrum")
        }

        function destroy() {
            boundElement.show(), offsetElement.unbind("click.spectrum touchstart.spectrum"), container.remove(), replacer.remove(), spectrums[spect.id] = null
        }

        function option(optionName, optionValue) {
            return optionName === undefined ? $.extend({}, opts) : optionValue === undefined ? opts[optionName] : (opts[optionName] = optionValue, void applyOptions())
        }

        function enable() {
            disabled = !1, boundElement.attr("disabled", !1), offsetElement.removeClass("sp-disabled")
        }

        function disable() {
            hide(), disabled = !0, boundElement.attr("disabled", !0), offsetElement.addClass("sp-disabled")
        }
        var opts = instanceOptions(o, element),
            flat = opts.flat,
            showSelectionPalette = opts.showSelectionPalette,
            localStorageKey = opts.localStorageKey,
            theme = opts.theme,
            callbacks = opts.callbacks,
            resize = throttle(reflow, 10),
            visible = !1,
            dragWidth = 0,
            dragHeight = 0,
            dragHelperHeight = 0,
            slideHeight = 0,
            slideWidth = 0,
            alphaWidth = 0,
            alphaSlideHelperWidth = 0,
            slideHelperHeight = 0,
            currentHue = 0,
            currentSaturation = 0,
            currentValue = 0,
            currentAlpha = 1,
            palette = [],
            paletteArray = [],
            paletteLookup = {}, selectionPalette = opts.selectionPalette.slice(0),
            maxSelectionSize = opts.maxSelectionSize,
            draggingClass = "sp-dragging",
            shiftMovementDirection = null,
            doc = element.ownerDocument,
            boundElement = (doc.body, $(element)),
            disabled = !1,
            container = $(markup, doc).addClass(theme),
            dragger = container.find(".sp-color"),
            dragHelper = container.find(".sp-dragger"),
            slider = container.find(".sp-hue"),
            slideHelper = container.find(".sp-slider"),
            alphaSliderInner = container.find(".sp-alpha-inner"),
            alphaSlider = container.find(".sp-alpha"),
            alphaSlideHelper = container.find(".sp-alpha-handle"),
            textInput = container.find(".sp-input"),
            paletteContainer = container.find(".sp-palette"),
            initialColorContainer = container.find(".sp-initial"),
            cancelButton = container.find(".sp-cancel"),
            clearButton = container.find(".sp-clear"),
            chooseButton = container.find(".sp-choose"),
            isInput = boundElement.is("input"),
            isInputTypeColor = isInput && inputTypeColorSupport && "color" === boundElement.attr("type"),
            shouldReplace = isInput && !flat,
            replacer = shouldReplace ? $(replaceInput).addClass(theme).addClass(opts.className).addClass(opts.replacerClassName) : $([]),
            offsetElement = shouldReplace ? replacer : boundElement,
            previewElement = replacer.find(".sp-preview-inner"),
            initialColor = opts.color || isInput && boundElement.val(),
            colorOnShow = !1,
            preferredFormat = opts.preferredFormat,
            currentPreferredFormat = preferredFormat,
            clickoutFiresChange = !opts.showButtons || opts.clickoutFiresChange,
            isEmpty = !initialColor,
            allowEmpty = opts.allowEmpty && !isInputTypeColor;
        initialize();
        var spect = {
            show: show,
            hide: hide,
            toggle: toggle,
            reflow: reflow,
            option: option,
            enable: enable,
            disable: disable,
            set: function(c) {
                set(c), updateOriginalInput()
            },
            get: get,
            destroy: destroy,
            container: container
        };
        return spect.id = spectrums.push(spect) - 1, spect
    }

    function getOffset(picker, input) {
        var extraY = 0,
            dpWidth = picker.outerWidth(),
            dpHeight = picker.outerHeight(),
            inputHeight = input.outerHeight(),
            doc = picker[0].ownerDocument,
            docElem = doc.documentElement,
            viewWidth = docElem.clientWidth + $(doc).scrollLeft(),
            viewHeight = docElem.clientHeight + $(doc).scrollTop(),
            offset = input.offset();
        return offset.top += inputHeight, offset.left -= Math.min(offset.left, offset.left + dpWidth > viewWidth && viewWidth > dpWidth ? Math.abs(offset.left + dpWidth - viewWidth) : 0), offset.top -= Math.min(offset.top, offset.top + dpHeight > viewHeight && viewHeight > dpHeight ? Math.abs(dpHeight + inputHeight - extraY) : extraY), offset
    }

    function noop() {}

    function stopPropagation(e) {
        e.stopPropagation()
    }

    function bind(func, obj) {
        var slice = Array.prototype.slice,
            args = slice.call(arguments, 2);
        return function() {
            return func.apply(obj, args.concat(slice.call(arguments)))
        }
    }

    function draggable(element, onmove, onstart, onstop) {
        function prevent(e) {
            e.stopPropagation && e.stopPropagation(), e.preventDefault && e.preventDefault(), e.returnValue = !1
        }

        function move(e) {
            if (dragging) {
                if (IE && document.documentMode < 9 && !e.button) return stop();
                var touches = e.originalEvent.touches,
                    pageX = touches ? touches[0].pageX : e.pageX,
                    pageY = touches ? touches[0].pageY : e.pageY,
                    dragX = Math.max(0, Math.min(pageX - offset.left, maxWidth)),
                    dragY = Math.max(0, Math.min(pageY - offset.top, maxHeight));
                hasTouch && prevent(e), onmove.apply(element, [dragX, dragY, e])
            }
        }

        function start(e) {
            {
                var rightclick = e.which ? 3 == e.which : 2 == e.button;
                e.originalEvent.touches
            }
            rightclick || dragging || onstart.apply(element, arguments) !== !1 && (dragging = !0, maxHeight = $(element).height(), maxWidth = $(element).width(), offset = $(element).offset(), $(doc).bind(duringDragEvents), $(doc.body).addClass("sp-dragging"), hasTouch || move(e), prevent(e))
        }

        function stop() {
            dragging && ($(doc).unbind(duringDragEvents), $(doc.body).removeClass("sp-dragging"), onstop.apply(element, arguments)), dragging = !1
        }
        onmove = onmove || function() {}, onstart = onstart || function() {}, onstop = onstop || function() {};
        var doc = element.ownerDocument || document,
            dragging = !1,
            offset = {}, maxHeight = 0,
            maxWidth = 0,
            hasTouch = "ontouchstart" in window,
            duringDragEvents = {};
        duringDragEvents.selectstart = prevent, duringDragEvents.dragstart = prevent, duringDragEvents["touchmove mousemove"] = move, duringDragEvents["touchend mouseup"] = stop, $(element).bind("touchstart mousedown", start)
    }

    function throttle(func, wait, debounce) {
        var timeout;
        return function() {
            var context = this,
                args = arguments,
                throttler = function() {
                    timeout = null, func.apply(context, args)
                };
            debounce && clearTimeout(timeout), (debounce || !timeout) && (timeout = setTimeout(throttler, wait))
        }
    }
    var defaultOpts = {
        beforeShow: noop,
        move: noop,
        change: noop,
        show: noop,
        hide: noop,
        color: !1,
        flat: !1,
        showInput: !1,
        allowEmpty: !1,
        showButtons: !0,
        clickoutFiresChange: !1,
        showInitial: !1,
        showPalette: !1,
        showPaletteOnly: !1,
        showSelectionPalette: !0,
        localStorageKey: !1,
        appendTo: "body",
        maxSelectionSize: 7,
        cancelText: "cancel",
        chooseText: "choose",
        clearText: "Clear Color Selection",
        preferredFormat: !1,
        className: "",
        containerClassName: "",
        replacerClassName: "",
        showAlpha: !1,
        theme: "sp-light",
        palette: [
            ["#ffffff", "#000000", "#ff0000", "#ff8000", "#ffff00", "#008000", "#0000ff", "#4b0082", "#9400d3"]
        ],
        selectionPalette: [],
        disabled: !1
    }, spectrums = [],
        IE = !! /msie/i.exec(window.navigator.userAgent),
        rgbaSupport = function() {
            function contains(str, substr) {
                return !!~("" + str).indexOf(substr)
            }
            var elem = document.createElement("div"),
                style = elem.style;
            return style.cssText = "background-color:rgba(0,0,0,.5)", contains(style.backgroundColor, "rgba") || contains(style.backgroundColor, "hsla")
        }(),
        inputTypeColorSupport = function() {
            var colorInput = $("<input type='color' value='!' />")[0];
            return "color" === colorInput.type && "!" !== colorInput.value
        }(),
        replaceInput = ["<div class='sp-replacer'>", "<div class='sp-preview'><div class='sp-preview-inner'></div></div>", "<div class='sp-dd'>&#9660;</div>", "</div>"].join(""),
        markup = function() {
            var gradientFix = "";
            if (IE)
                for (var i = 1; 6 >= i; i++) gradientFix += "<div class='sp-" + i + "'></div>";
            return ["<div class='sp-container sp-hidden'>", "<div class='sp-palette-container'>", "<div class='sp-palette sp-thumb sp-cf'></div>", "</div>", "<div class='sp-picker-container'>", "<div class='sp-top sp-cf'>", "<div class='sp-fill'></div>", "<div class='sp-top-inner'>", "<div class='sp-color'>", "<div class='sp-sat'>", "<div class='sp-val'>", "<div class='sp-dragger'></div>", "</div>", "</div>", "</div>", "<div class='sp-clear sp-clear-display'>", "</div>", "<div class='sp-hue'>", "<div class='sp-slider'></div>", gradientFix, "</div>", "</div>", "<div class='sp-alpha'><div class='sp-alpha-inner'><div class='sp-alpha-handle'></div></div></div>", "</div>", "<div class='sp-input-container sp-cf'>", "<input class='sp-input' type='text' spellcheck='false'  />", "</div>", "<div class='sp-initial sp-thumb sp-cf'></div>", "<div class='sp-button-container sp-cf'>", "<a class='sp-cancel' href='#'></a>", "<button type='button' class='sp-choose'></button>", "</div>", "</div>", "</div>"].join("")
        }(),
        dataID = "spectrum.id";
    $.fn.spectrum = function(opts) {
        if ("string" == typeof opts) {
            var returnValue = this,
                args = Array.prototype.slice.call(arguments, 1);
            return this.each(function() {
                var spect = spectrums[$(this).data(dataID)];
                if (spect) {
                    var method = spect[opts];
                    if (!method) throw new Error("Spectrum: no such method: '" + opts + "'");
                    "get" == opts ? returnValue = spect.get() : "container" == opts ? returnValue = spect.container : "option" == opts ? returnValue = spect.option.apply(spect, args) : "destroy" == opts ? (spect.destroy(), $(this).removeData(dataID)) : method.apply(spect, args)
                }
            }), returnValue
        }
        return this.spectrum("destroy").each(function() {
            var options = $.extend({}, opts, $(this).data()),
                spect = spectrum(this, options);
            $(this).data(dataID, spect.id)
        })
    }, $.fn.spectrum.load = !0, $.fn.spectrum.loadOpts = {}, $.fn.spectrum.draggable = draggable, $.fn.spectrum.defaults = defaultOpts, $.spectrum = {}, $.spectrum.localization = {}, $.spectrum.palettes = {}, $.fn.spectrum.processNativeColorInputs = function() {
        inputTypeColorSupport || $("input[type=color]").spectrum({
            preferredFormat: "hex6"
        })
    },
    function() {
        function tinycolor(color, opts) {
            if (color = color ? color : "", opts = opts || {}, "object" == typeof color && color.hasOwnProperty("_tc_id")) return color;
            var rgb = inputToRGB(color),
                r = rgb.r,
                g = rgb.g,
                b = rgb.b,
                a = rgb.a,
                roundA = mathRound(100 * a) / 100,
                format = opts.format || rgb.format;
            return 1 > r && (r = mathRound(r)), 1 > g && (g = mathRound(g)), 1 > b && (b = mathRound(b)), {
                ok: rgb.ok,
                format: format,
                _tc_id: tinyCounter++,
                alpha: a,
                getAlpha: function() {
                    return a
                },
                setAlpha: function(value) {
                    a = boundAlpha(value), roundA = mathRound(100 * a) / 100
                },
                toHsv: function() {
                    var hsv = rgbToHsv(r, g, b);
                    return {
                        h: 360 * hsv.h,
                        s: hsv.s,
                        v: hsv.v,
                        a: a
                    }
                },
                toHsvString: function() {
                    var hsv = rgbToHsv(r, g, b),
                        h = mathRound(360 * hsv.h),
                        s = mathRound(100 * hsv.s),
                        v = mathRound(100 * hsv.v);
                    return 1 == a ? "hsv(" + h + ", " + s + "%, " + v + "%)" : "hsva(" + h + ", " + s + "%, " + v + "%, " + roundA + ")"
                },
                toHsl: function() {
                    var hsl = rgbToHsl(r, g, b);
                    return {
                        h: 360 * hsl.h,
                        s: hsl.s,
                        l: hsl.l,
                        a: a
                    }
                },
                toHslString: function() {
                    var hsl = rgbToHsl(r, g, b),
                        h = mathRound(360 * hsl.h),
                        s = mathRound(100 * hsl.s),
                        l = mathRound(100 * hsl.l);
                    return 1 == a ? "hsl(" + h + ", " + s + "%, " + l + "%)" : "hsla(" + h + ", " + s + "%, " + l + "%, " + roundA + ")"
                },
                toHex: function(allow3Char) {
                    return rgbToHex(r, g, b, allow3Char)
                },
                toHexString: function(allow3Char) {
                    return "#" + this.toHex(allow3Char)
                },
                toHex8: function() {
                    return rgbaToHex(r, g, b, a)
                },
                toHex8String: function() {
                    return "#" + this.toHex8()
                },
                toRgb: function() {
                    return {
                        r: mathRound(r),
                        g: mathRound(g),
                        b: mathRound(b),
                        a: a
                    }
                },
                toRgbString: function() {
                    return 1 == a ? "rgb(" + mathRound(r) + ", " + mathRound(g) + ", " + mathRound(b) + ")" : "rgba(" + mathRound(r) + ", " + mathRound(g) + ", " + mathRound(b) + ", " + roundA + ")"
                },
                toPercentageRgb: function() {
                    return {
                        r: mathRound(100 * bound01(r, 255)) + "%",
                        g: mathRound(100 * bound01(g, 255)) + "%",
                        b: mathRound(100 * bound01(b, 255)) + "%",
                        a: a
                    }
                },
                toPercentageRgbString: function() {
                    return 1 == a ? "rgb(" + mathRound(100 * bound01(r, 255)) + "%, " + mathRound(100 * bound01(g, 255)) + "%, " + mathRound(100 * bound01(b, 255)) + "%)" : "rgba(" + mathRound(100 * bound01(r, 255)) + "%, " + mathRound(100 * bound01(g, 255)) + "%, " + mathRound(100 * bound01(b, 255)) + "%, " + roundA + ")"
                },
                toName: function() {
                    return 0 === a ? "transparent" : hexNames[rgbToHex(r, g, b, !0)] || !1
                },
                toFilter: function(secondColor) {
                    var hex8String = "#" + rgbaToHex(r, g, b, a),
                        secondHex8String = hex8String,
                        gradientType = opts && opts.gradientType ? "GradientType = 1, " : "";
                    if (secondColor) {
                        var s = tinycolor(secondColor);
                        secondHex8String = s.toHex8String()
                    }
                    return "progid:DXImageTransform.Microsoft.gradient(" + gradientType + "startColorstr=" + hex8String + ",endColorstr=" + secondHex8String + ")"
                },
                toString: function(format) {
                    var formatSet = !! format;
                    format = format || this.format;
                    var formattedString = !1,
                        hasAlphaAndFormatNotSet = !formatSet && 1 > a && a > 0,
                        formatWithAlpha = hasAlphaAndFormatNotSet && ("hex" === format || "hex6" === format || "hex3" === format || "name" === format);
                    return "rgb" === format && (formattedString = this.toRgbString()), "prgb" === format && (formattedString = this.toPercentageRgbString()), ("hex" === format || "hex6" === format) && (formattedString = this.toHexString()), "hex3" === format && (formattedString = this.toHexString(!0)), "hex8" === format && (formattedString = this.toHex8String()), "name" === format && (formattedString = this.toName()), "hsl" === format && (formattedString = this.toHslString()), "hsv" === format && (formattedString = this.toHsvString()), formatWithAlpha ? this.toRgbString() : formattedString || this.toHexString()
                }
            }
        }

        function inputToRGB(color) {
            var rgb = {
                r: 0,
                g: 0,
                b: 0
            }, a = 1,
                ok = !1,
                format = !1;
            return "string" == typeof color && (color = stringInputToObject(color)), "object" == typeof color && (color.hasOwnProperty("r") && color.hasOwnProperty("g") && color.hasOwnProperty("b") ? (rgb = rgbToRgb(color.r, color.g, color.b), ok = !0, format = "%" === String(color.r).substr(-1) ? "prgb" : "rgb") : color.hasOwnProperty("h") && color.hasOwnProperty("s") && color.hasOwnProperty("v") ? (color.s = convertToPercentage(color.s), color.v = convertToPercentage(color.v), rgb = hsvToRgb(color.h, color.s, color.v), ok = !0, format = "hsv") : color.hasOwnProperty("h") && color.hasOwnProperty("s") && color.hasOwnProperty("l") && (color.s = convertToPercentage(color.s), color.l = convertToPercentage(color.l), rgb = hslToRgb(color.h, color.s, color.l), ok = !0, format = "hsl"), color.hasOwnProperty("a") && (a = color.a)), a = boundAlpha(a), {
                ok: ok,
                format: color.format || format,
                r: mathMin(255, mathMax(rgb.r, 0)),
                g: mathMin(255, mathMax(rgb.g, 0)),
                b: mathMin(255, mathMax(rgb.b, 0)),
                a: a
            }
        }

        function rgbToRgb(r, g, b) {
            return {
                r: 255 * bound01(r, 255),
                g: 255 * bound01(g, 255),
                b: 255 * bound01(b, 255)
            }
        }

        function rgbToHsl(r, g, b) {
            r = bound01(r, 255), g = bound01(g, 255), b = bound01(b, 255);
            var h, s, max = mathMax(r, g, b),
                min = mathMin(r, g, b),
                l = (max + min) / 2;
            if (max == min) h = s = 0;
            else {
                var d = max - min;
                switch (s = l > .5 ? d / (2 - max - min) : d / (max + min), max) {
                    case r:
                        h = (g - b) / d + (b > g ? 6 : 0);
                        break;
                    case g:
                        h = (b - r) / d + 2;
                        break;
                    case b:
                        h = (r - g) / d + 4
                }
                h /= 6
            }
            return {
                h: h,
                s: s,
                l: l
            }
        }

        function hslToRgb(h, s, l) {
            function hue2rgb(p, q, t) {
                return 0 > t && (t += 1), t > 1 && (t -= 1), 1 / 6 > t ? p + 6 * (q - p) * t : .5 > t ? q : 2 / 3 > t ? p + (q - p) * (2 / 3 - t) * 6 : p
            }
            var r, g, b;
            if (h = bound01(h, 360), s = bound01(s, 100), l = bound01(l, 100), 0 === s) r = g = b = l;
            else {
                var q = .5 > l ? l * (1 + s) : l + s - l * s,
                    p = 2 * l - q;
                r = hue2rgb(p, q, h + 1 / 3), g = hue2rgb(p, q, h), b = hue2rgb(p, q, h - 1 / 3)
            }
            return {
                r: 255 * r,
                g: 255 * g,
                b: 255 * b
            }
        }

        function rgbToHsv(r, g, b) {
            r = bound01(r, 255), g = bound01(g, 255), b = bound01(b, 255);
            var h, s, max = mathMax(r, g, b),
                min = mathMin(r, g, b),
                v = max,
                d = max - min;
            if (s = 0 === max ? 0 : d / max, max == min) h = 0;
            else {
                switch (max) {
                    case r:
                        h = (g - b) / d + (b > g ? 6 : 0);
                        break;
                    case g:
                        h = (b - r) / d + 2;
                        break;
                    case b:
                        h = (r - g) / d + 4
                }
                h /= 6
            }
            return {
                h: h,
                s: s,
                v: v
            }
        }

        function hsvToRgb(h, s, v) {
            h = 6 * bound01(h, 360), s = bound01(s, 100), v = bound01(v, 100);
            var i = math.floor(h),
                f = h - i,
                p = v * (1 - s),
                q = v * (1 - f * s),
                t = v * (1 - (1 - f) * s),
                mod = i % 6,
                r = [v, q, p, p, t, v][mod],
                g = [t, v, v, q, p, p][mod],
                b = [p, p, t, v, v, q][mod];
            return {
                r: 255 * r,
                g: 255 * g,
                b: 255 * b
            }
        }

        function rgbToHex(r, g, b, allow3Char) {
            var hex = [pad2(mathRound(r).toString(16)), pad2(mathRound(g).toString(16)), pad2(mathRound(b).toString(16))];
            return allow3Char && hex[0].charAt(0) == hex[0].charAt(1) && hex[1].charAt(0) == hex[1].charAt(1) && hex[2].charAt(0) == hex[2].charAt(1) ? hex[0].charAt(0) + hex[1].charAt(0) + hex[2].charAt(0) : hex.join("")
        }

        function rgbaToHex(r, g, b, a) {
            var hex = [pad2(convertDecimalToHex(a)), pad2(mathRound(r).toString(16)), pad2(mathRound(g).toString(16)), pad2(mathRound(b).toString(16))];
            return hex.join("")
        }

        function flip(o) {
            var flipped = {};
            for (var i in o) o.hasOwnProperty(i) && (flipped[o[i]] = i);
            return flipped
        }

        function boundAlpha(a) {
            return a = parseFloat(a), (isNaN(a) || 0 > a || a > 1) && (a = 1), a
        }

        function bound01(n, max) {
            isOnePointZero(n) && (n = "100%");
            var processPercent = isPercentage(n);
            return n = mathMin(max, mathMax(0, parseFloat(n))), processPercent && (n = parseInt(n * max, 10) / 100), math.abs(n - max) < 1e-6 ? 1 : n % max / parseFloat(max)
        }

        function clamp01(val) {
            return mathMin(1, mathMax(0, val))
        }

        function parseIntFromHex(val) {
            return parseInt(val, 16)
        }

        function isOnePointZero(n) {
            return "string" == typeof n && -1 != n.indexOf(".") && 1 === parseFloat(n)
        }

        function isPercentage(n) {
            return "string" == typeof n && -1 != n.indexOf("%")
        }

        function pad2(c) {
            return 1 == c.length ? "0" + c : "" + c
        }

        function convertToPercentage(n) {
            return 1 >= n && (n = 100 * n + "%"), n
        }

        function convertDecimalToHex(d) {
            return Math.round(255 * parseFloat(d)).toString(16)
        }

        function convertHexToDecimal(h) {
            return parseIntFromHex(h) / 255
        }

        function stringInputToObject(color) {
            color = color.replace(trimLeft, "").replace(trimRight, "").toLowerCase();
            var named = !1;
            if (names[color]) color = names[color], named = !0;
            else if ("transparent" == color) return {
                r: 0,
                g: 0,
                b: 0,
                a: 0,
                format: "name"
            };
            var match;
            return (match = matchers.rgb.exec(color)) ? {
                r: match[1],
                g: match[2],
                b: match[3]
            } : (match = matchers.rgba.exec(color)) ? {
                r: match[1],
                g: match[2],
                b: match[3],
                a: match[4]
            } : (match = matchers.hsl.exec(color)) ? {
                h: match[1],
                s: match[2],
                l: match[3]
            } : (match = matchers.hsla.exec(color)) ? {
                h: match[1],
                s: match[2],
                l: match[3],
                a: match[4]
            } : (match = matchers.hsv.exec(color)) ? {
                h: match[1],
                s: match[2],
                v: match[3]
            } : (match = matchers.hex8.exec(color)) ? {
                a: convertHexToDecimal(match[1]),
                r: parseIntFromHex(match[2]),
                g: parseIntFromHex(match[3]),
                b: parseIntFromHex(match[4]),
                format: named ? "name" : "hex8"
            } : (match = matchers.hex6.exec(color)) ? {
                r: parseIntFromHex(match[1]),
                g: parseIntFromHex(match[2]),
                b: parseIntFromHex(match[3]),
                format: named ? "name" : "hex"
            } : (match = matchers.hex3.exec(color)) ? {
                r: parseIntFromHex(match[1] + "" + match[1]),
                g: parseIntFromHex(match[2] + "" + match[2]),
                b: parseIntFromHex(match[3] + "" + match[3]),
                format: named ? "name" : "hex"
            } : !1
        }
        var trimLeft = /^[\s,#]+/,
            trimRight = /\s+$/,
            tinyCounter = 0,
            math = Math,
            mathRound = math.round,
            mathMin = math.min,
            mathMax = math.max,
            mathRandom = math.random;
        tinycolor.fromRatio = function(color, opts) {
            if ("object" == typeof color) {
                var newColor = {};
                for (var i in color) color.hasOwnProperty(i) && (newColor[i] = "a" === i ? color[i] : convertToPercentage(color[i]));
                color = newColor
            }
            return tinycolor(color, opts)
        }, tinycolor.equals = function(color1, color2) {
            return color1 && color2 ? tinycolor(color1).toRgbString() == tinycolor(color2).toRgbString() : !1
        }, tinycolor.random = function() {
            return tinycolor.fromRatio({
                r: mathRandom(),
                g: mathRandom(),
                b: mathRandom()
            })
        }, tinycolor.desaturate = function(color, amount) {
            amount = 0 === amount ? 0 : amount || 10;
            var hsl = tinycolor(color).toHsl();
            return hsl.s -= amount / 100, hsl.s = clamp01(hsl.s), tinycolor(hsl)
        }, tinycolor.saturate = function(color, amount) {
            amount = 0 === amount ? 0 : amount || 10;
            var hsl = tinycolor(color).toHsl();
            return hsl.s += amount / 100, hsl.s = clamp01(hsl.s), tinycolor(hsl)
        }, tinycolor.greyscale = function(color) {
            return tinycolor.desaturate(color, 100)
        }, tinycolor.lighten = function(color, amount) {
            amount = 0 === amount ? 0 : amount || 10;
            var hsl = tinycolor(color).toHsl();
            return hsl.l += amount / 100, hsl.l = clamp01(hsl.l), tinycolor(hsl)
        }, tinycolor.darken = function(color, amount) {
            amount = 0 === amount ? 0 : amount || 10;
            var hsl = tinycolor(color).toHsl();
            return hsl.l -= amount / 100, hsl.l = clamp01(hsl.l), tinycolor(hsl)
        }, tinycolor.complement = function(color) {
            var hsl = tinycolor(color).toHsl();
            return hsl.h = (hsl.h + 180) % 360, tinycolor(hsl)
        }, tinycolor.triad = function(color) {
            var hsl = tinycolor(color).toHsl(),
                h = hsl.h;
            return [tinycolor(color), tinycolor({
                h: (h + 120) % 360,
                s: hsl.s,
                l: hsl.l
            }), tinycolor({
                h: (h + 240) % 360,
                s: hsl.s,
                l: hsl.l
            })]
        }, tinycolor.tetrad = function(color) {
            var hsl = tinycolor(color).toHsl(),
                h = hsl.h;
            return [tinycolor(color), tinycolor({
                h: (h + 90) % 360,
                s: hsl.s,
                l: hsl.l
            }), tinycolor({
                h: (h + 180) % 360,
                s: hsl.s,
                l: hsl.l
            }), tinycolor({
                h: (h + 270) % 360,
                s: hsl.s,
                l: hsl.l
            })]
        }, tinycolor.splitcomplement = function(color) {
            var hsl = tinycolor(color).toHsl(),
                h = hsl.h;
            return [tinycolor(color), tinycolor({
                h: (h + 72) % 360,
                s: hsl.s,
                l: hsl.l
            }), tinycolor({
                h: (h + 216) % 360,
                s: hsl.s,
                l: hsl.l
            })]
        }, tinycolor.analogous = function(color, results, slices) {
            results = results || 6, slices = slices || 30;
            var hsl = tinycolor(color).toHsl(),
                part = 360 / slices,
                ret = [tinycolor(color)];
            for (hsl.h = (hsl.h - (part * results >> 1) + 720) % 360; --results;) hsl.h = (hsl.h + part) % 360, ret.push(tinycolor(hsl));
            return ret
        }, tinycolor.monochromatic = function(color, results) {
            results = results || 6;
            for (var hsv = tinycolor(color).toHsv(), h = hsv.h, s = hsv.s, v = hsv.v, ret = [], modification = 1 / results; results--;) ret.push(tinycolor({
                h: h,
                s: s,
                v: v
            })), v = (v + modification) % 1;
            return ret
        }, tinycolor.readability = function(color1, color2) {
            var a = tinycolor(color1).toRgb(),
                b = tinycolor(color2).toRgb(),
                brightnessA = (299 * a.r + 587 * a.g + 114 * a.b) / 1e3,
                brightnessB = (299 * b.r + 587 * b.g + 114 * b.b) / 1e3,
                colorDiff = Math.max(a.r, b.r) - Math.min(a.r, b.r) + Math.max(a.g, b.g) - Math.min(a.g, b.g) + Math.max(a.b, b.b) - Math.min(a.b, b.b);
            return {
                brightness: Math.abs(brightnessA - brightnessB),
                color: colorDiff
            }
        }, tinycolor.readable = function(color1, color2) {
            var readability = tinycolor.readability(color1, color2);
            return readability.brightness > 125 && readability.color > 500
        }, tinycolor.mostReadable = function(baseColor, colorList) {
            for (var bestColor = null, bestScore = 0, bestIsReadable = !1, i = 0; i < colorList.length; i++) {
                var readability = tinycolor.readability(baseColor, colorList[i]),
                    readable = readability.brightness > 125 && readability.color > 500,
                    score = 3 * (readability.brightness / 125) + readability.color / 500;
                (readable && !bestIsReadable || readable && bestIsReadable && score > bestScore || !readable && !bestIsReadable && score > bestScore) && (bestIsReadable = readable, bestScore = score, bestColor = tinycolor(colorList[i]))
            }
            return bestColor
        };
        var names = tinycolor.names = {
            aliceblue: "f0f8ff",
            antiquewhite: "faebd7",
            aqua: "0ff",
            aquamarine: "7fffd4",
            azure: "f0ffff",
            beige: "f5f5dc",
            bisque: "ffe4c4",
            black: "000",
            blanchedalmond: "ffebcd",
            blue: "00f",
            blueviolet: "8a2be2",
            brown: "a52a2a",
            burlywood: "deb887",
            burntsienna: "ea7e5d",
            cadetblue: "5f9ea0",
            chartreuse: "7fff00",
            chocolate: "d2691e",
            coral: "ff7f50",
            cornflowerblue: "6495ed",
            cornsilk: "fff8dc",
            crimson: "dc143c",
            cyan: "0ff",
            darkblue: "00008b",
            darkcyan: "008b8b",
            darkgoldenrod: "b8860b",
            darkgray: "a9a9a9",
            darkgreen: "006400",
            darkgrey: "a9a9a9",
            darkkhaki: "bdb76b",
            darkmagenta: "8b008b",
            darkolivegreen: "556b2f",
            darkorange: "ff8c00",
            darkorchid: "9932cc",
            darkred: "8b0000",
            darksalmon: "e9967a",
            darkseagreen: "8fbc8f",
            darkslateblue: "483d8b",
            darkslategray: "2f4f4f",
            darkslategrey: "2f4f4f",
            darkturquoise: "00ced1",
            darkviolet: "9400d3",
            deeppink: "ff1493",
            deepskyblue: "00bfff",
            dimgray: "696969",
            dimgrey: "696969",
            dodgerblue: "1e90ff",
            firebrick: "b22222",
            floralwhite: "fffaf0",
            forestgreen: "228b22",
            fuchsia: "f0f",
            gainsboro: "dcdcdc",
            ghostwhite: "f8f8ff",
            gold: "ffd700",
            goldenrod: "daa520",
            gray: "808080",
            green: "008000",
            greenyellow: "adff2f",
            grey: "808080",
            honeydew: "f0fff0",
            hotpink: "ff69b4",
            indianred: "cd5c5c",
            indigo: "4b0082",
            ivory: "fffff0",
            khaki: "f0e68c",
            lavender: "e6e6fa",
            lavenderblush: "fff0f5",
            lawngreen: "7cfc00",
            lemonchiffon: "fffacd",
            lightblue: "add8e6",
            lightcoral: "f08080",
            lightcyan: "e0ffff",
            lightgoldenrodyellow: "fafad2",
            lightgray: "d3d3d3",
            lightgreen: "90ee90",
            lightgrey: "d3d3d3",
            lightpink: "ffb6c1",
            lightsalmon: "ffa07a",
            lightseagreen: "20b2aa",
            lightskyblue: "87cefa",
            lightslategray: "789",
            lightslategrey: "789",
            lightsteelblue: "b0c4de",
            lightyellow: "ffffe0",
            lime: "0f0",
            limegreen: "32cd32",
            linen: "faf0e6",
            magenta: "f0f",
            maroon: "800000",
            mediumaquamarine: "66cdaa",
            mediumblue: "0000cd",
            mediumorchid: "ba55d3",
            mediumpurple: "9370db",
            mediumseagreen: "3cb371",
            mediumslateblue: "7b68ee",
            mediumspringgreen: "00fa9a",
            mediumturquoise: "48d1cc",
            mediumvioletred: "c71585",
            midnightblue: "191970",
            mintcream: "f5fffa",
            mistyrose: "ffe4e1",
            moccasin: "ffe4b5",
            navajowhite: "ffdead",
            navy: "000080",
            oldlace: "fdf5e6",
            olive: "808000",
            olivedrab: "6b8e23",
            orange: "ffa500",
            orangered: "ff4500",
            orchid: "da70d6",
            palegoldenrod: "eee8aa",
            palegreen: "98fb98",
            paleturquoise: "afeeee",
            palevioletred: "db7093",
            papayawhip: "ffefd5",
            peachpuff: "ffdab9",
            peru: "cd853f",
            pink: "ffc0cb",
            plum: "dda0dd",
            powderblue: "b0e0e6",
            purple: "800080",
            red: "f00",
            rosybrown: "bc8f8f",
            royalblue: "4169e1",
            saddlebrown: "8b4513",
            salmon: "fa8072",
            sandybrown: "f4a460",
            seagreen: "2e8b57",
            seashell: "fff5ee",
            sienna: "a0522d",
            silver: "c0c0c0",
            skyblue: "87ceeb",
            slateblue: "6a5acd",
            slategray: "708090",
            slategrey: "708090",
            snow: "fffafa",
            springgreen: "00ff7f",
            steelblue: "4682b4",
            tan: "d2b48c",
            teal: "008080",
            thistle: "d8bfd8",
            tomato: "ff6347",
            turquoise: "40e0d0",
            violet: "ee82ee",
            wheat: "f5deb3",
            white: "fff",
            whitesmoke: "f5f5f5",
            yellow: "ff0",
            yellowgreen: "9acd32"
        }, hexNames = tinycolor.hexNames = flip(names),
            matchers = function() {
                var CSS_INTEGER = "[-\\+]?\\d+%?",
                    CSS_NUMBER = "[-\\+]?\\d*\\.\\d+%?",
                    CSS_UNIT = "(?:" + CSS_NUMBER + ")|(?:" + CSS_INTEGER + ")",
                    PERMISSIVE_MATCH3 = "[\\s|\\(]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")\\s*\\)?",
                    PERMISSIVE_MATCH4 = "[\\s|\\(]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")\\s*\\)?";
                return {
                    rgb: new RegExp("rgb" + PERMISSIVE_MATCH3),
                    rgba: new RegExp("rgba" + PERMISSIVE_MATCH4),
                    hsl: new RegExp("hsl" + PERMISSIVE_MATCH3),
                    hsla: new RegExp("hsla" + PERMISSIVE_MATCH4),
                    hsv: new RegExp("hsv" + PERMISSIVE_MATCH3),
                    hex3: /^([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,
                    hex6: /^([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,
                    hex8: /^([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/
                }
            }();
        window.tinycolor = tinycolor
    }(), $(function() {
        $.fn.spectrum.load && $.fn.spectrum.processNativeColorInputs()
    })
}(window, jQuery);
