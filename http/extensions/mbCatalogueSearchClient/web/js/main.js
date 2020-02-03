'use strict';
/* globals jQuery, Search, BASEDIR, setTimeout, L, document, window, $ */

/**
 * init
 */
var autocomplete,
    prepareAndSearch = null;
var maps = []; //used for "raemliche Eingrenzung"

/**
 * Searchfield simple search function
 * @type {Search}
 */
var search = new Search();
search.setBasedir(BASEDIR);

/**
 * Autocomplete feature for searchfield
 * @param search
 * @constructor
 */
var Autocomplete = function(search) {
    var self = this;
    var _search = null;
    var _minLength = 1;
    var _input = null;
    var _div = null;
    var _pos = 0;
    var KEYBOARD = {
        UP_ARROW: 38,
        DOWN_ARROW: 40,
        LEFT_ARROW: 37,
        RIGHT_ARROW: 39,
        ENTER: 13
    };

    this.init = function(search) {
        var self = this;
        _search = search;
        _input = jQuery('.-js-simple-search-field');
        _div = jQuery('.-js-simple-search-autocomplete');
        _div.on('click', self.onSelect);
        _input.on('keyup', function(e) {
            self.keyUp(e.keyCode);
        });
    };

    this.hide = function() {
        _div.removeClass('active');
        _pos = 0;
    };

    this.show = function(list) {
        _div.empty();
        for (var i = 0, len = list.length; i < len; i++) {
            var $row = jQuery('<div>' + list[i].keywordHigh + '</div>');
            $row.data('keyword', list[i].keyword);
            _div.append($row);
        }
        _div.addClass('active');
    };

    this.keyUp = function(keyCode) {
        if (keyCode === KEYBOARD.UP_ARROW) {
            this.nav(-1);
        }
        else if (keyCode === KEYBOARD.DOWN_ARROW) {
            this.nav(1);
        }
        else if (keyCode === KEYBOARD.ENTER) {
            if (_pos) {
                _div.find('div:nth-child(' + _pos + ')').click();
            } else {
                self.hide();
                prepareAndSearch();
            }
        }
        else  if (keyCode !== KEYBOARD.LEFT_ARROW && keyCode !== KEYBOARD.RIGHT_ARROW) {
            var term = _input.val().trim();
            _search.setParam('terms', term);
            setTimeout(function() {
                if (_search.getParam('terms') === term && term.length >= _minLength) {
                    _search.autocomplete();
                    _search.setParam('terms', '');
                } else if (term.length <= 1) {
                    self.hide();
                }
            }, _search.timeoutDelay);
        }
    };

    this.onSelect = function(e) {
        var el = jQuery(e.target);
        var keyword = el.data('keyword') ? el.data('keyword') : el.parent().data('keyword');
        if (keyword) {
            _input.val(keyword);
            self.hide();
            prepareAndSearch(true);
        }
    };

    this.nav = function(p) {
        var alldivs = _div.find('div');
        if (alldivs.length) {
            _pos = _pos + p;
            if (_pos < 1) {
                _pos = 0;
            } else if (_pos > alldivs.length) {
                _pos = alldivs.length;
            }
            var el = _div.find('div:nth-child(' + _pos + ')');
            _div.find('div').removeClass('active');
            el.addClass('active');
        }
    };

    this.init(search);
};

/**
 * Leaflet Map
 * @param $searchBbox
 * @param conf
 * @constructor
 */
function Map($searchBbox, conf) {
    var _map = null;
    var _$searchBbox = null;
    this.init = function(conf) {
        _$searchBbox = $searchBbox;
        _map = L.map(
            conf.mapId, {
                'center': new L.LatLng(conf.center.lat, conf.center.lon),
                'zoom': conf.zoom,
                'crs': L.CRS.EPSG4326
            }
        );
        L.tileLayer.wms(
            conf.wms.url, {
                'layers': conf.wms.layers,
                'format': conf.wms.format,
                'transparent': true
            }
        ).addTo(_map);
        _map.on('moveend', function() {
            _$searchBbox.val(_map.getBounds().toBBoxString());
        });
    };
    this.getBbox = function() {
        return _map.getBounds().toBBoxString();
    };
    this.init(conf);
}

/**
 * jQuery DOM Traversal and modify (controller/glue)
 *
 */
jQuery(document).ready(function() {

    var resources = {
        wms: true,
        wfs: true,
        wmc: true,
        dataset: true
    };

    var fixDateFormat = function(val) {
        var ms = val.match(/(\d\d).(\d\d).(\d\d\d\d)/);
        if (ms) {
            return ms[3] + '-' + ms[2] + '-' + ms[1];
        }
        return null;
    };

    var fixDateFormats = function(items) {
        items.regTimeBegin = [fixDateFormat(items.regTimeBegin[0])];
        items.regTimeEnd = [fixDateFormat(items.regTimeEnd[0])];
        items.timeBegin = [fixDateFormat(items.timeBegin[0])];
        items.timeEnd = [fixDateFormat(items.timeEnd[0])];
    };

    /**
     * Function that does the search
     * @param fromField
     */
    prepareAndSearch  = function(fromField, noPageReset) {
        var $current  = jQuery('.-js-content.active');
        var reslist = [];
        var keywords  = [];
        var terms     = [];
        var $farea    = $current.find('.-js-result .-js-filterarea');

        var prepareTerm = function(terms) {
           return terms.trim();
        };

        search.hide();

        search.setParam('source', $current.attr('data-source'));
        var extended = $current.find('.-js-extended-search-form').serializeArray();
        var toEncode = {};
        $.each(extended, function(_, item) {
            if (toEncode[item.name]) {
                toEncode[item.name].push(item.value);
            } else {
                toEncode[item.name] = [item.value];
            }
        });
        fixDateFormats(toEncode);

        var rs = [];
        $.each(resources, function(res, send) {
            if(send) {
                rs.push(res);
                reslist.push(res);
            }
        });

        extended = '&resolveCoupledResources=true&searchResources=' + rs.join(',');
        $.each(toEncode, function(key, values) {
            extended += '&' + key + '=' + values.join(',');
        });
        if (search.getParam('maxResults')) {
            extended += '&maxResults=' + search.getParam('maxResults');;
        }

        extended = encodeURIComponent(extended);
        search.setParam('extended', extended);

        if ($farea.length) {
            $farea.find('.-js-keyword').each(function() {
                keywords.push($(this).text());
            });

            $farea.find('.-js-term').each(function() {
                var term = $(this).text();
                terms.push(prepareTerm(term));
            });
        }

        search.setParam('resources', JSON.stringify(reslist));
        var $input = jQuery('.-js-simple-search-field');

        var fieldTerms = prepareTerm($input.val());
        search.setParam('terms', fieldTerms);
        search.setParam('keywords', '');

        if (!noPageReset) {
            search.setParam('pages', 1);
        }
        search.find();
        jQuery('.-js-simple-search-autocomplete').removeClass('active');
        search.show();
    };

    /**
     * Start search if search button was clicked
     */
    // start search if search button clicked
    jQuery(document).on("click", '.-js-search-start', function() {
        prepareAndSearch(true); // search and render
    });

    /**
     *  Hide autocomplete form if body, outside was clicked
     */
    jQuery(document).on("click", 'body', function() {
        var $autocompleteSelect = jQuery('.-js-simple-search-autocomplete');

        if( $autocompleteSelect.hasClass('active') === true) {
            $autocompleteSelect.removeClass('active');
        }
    });

    /**
     * Open and clode form of the extended search
     * @extendedSearch
     */
    jQuery(document).on('click', '.-js-extended-search-header', function() {
        $('.-js-extended-search-header .accordion').toggleClass('closed').toggleClass('open');
        var $this = jQuery(this);
        var $parent = $this.parent().find('.-js-search-extended');

        if ($this.hasClass('active')) {
            // reset form ?
            $this.removeClass('active');
            $parent.removeClass('active');
        } else {
            $this.addClass('active');
            $parent.addClass('active');
        }
    });

    $(document).on('click', '.-js-show-facets', function() {
        $('.-js-show-facets .accordion').toggleClass('closed').toggleClass('open');
        if ($('.-js-show-facets .accordion').hasClass('open')) {
            $('.-js-facets').show();
        } else {
            $('.-js-facets').hide();
        }
    });

    $(document).on('click', '[data-name="ISO 19115"] li', function() {
        var id = $(this).data('id');
        $('#geoportal-isoCategories option[value=' + id + ']').prop('selected', true);
        prepareAndSearch();
    });

    $(document).on('click', '[data-name=INSPIRE] li', function() {
        var id = $(this).data('id');
        $('#geoportal-inspireThemes option[value=' + id + ']').prop('selected', true);
        prepareAndSearch();
    });

    $(document).on('click', '[data-name=Sonstige] li', function() {
        var id = $(this).data('id');
        $('#geoportal-customCategories option[value=' + id + ']').prop('selected', true);
        prepareAndSearch();
    });

    /**
     * Navigates through tabs in extended search form
     * @extendedSearch
     */
    jQuery(document).on("click", ".-js-tabs .-js-tab-item", function() {
        var $this = jQuery(this);
        $this.parent().find('> .-js-tab-item').removeClass('active');
        $this.addClass('active');
        var $content = jQuery('#' + $this.attr('data-id'));
        $content.parent().find('> .-js-content').removeClass('active');
        $content.addClass('active');
        search.setParam('source', $content.attr('data-source'));
    });

    /**
     * Resets selectioned themes in extended search
     * @extendedSearch
     */
    jQuery(document).on("click", ".-js-reset-select", function() {
        var target = '#' + jQuery(this).attr('data-target');
        jQuery(target).prop('selectedIndex', -1); //set select to no selection
    });

    /**
     * Show and hide map in extended search form
     * @extendedSearch
     */
    jQuery(document).on("click", '[name="searchBbox"]', function() {

        if (!mapConf) {
            return;
        }

        var $this = jQuery(this);
        var $form = $this.parents('form:first');
        var search = $form.attr('data-search');

        if ($this.prop('checked')) {
            $form.find('div.map-wrapper').append(jQuery('<div id="' + search + '-map" class="map"></div>'));
            maps[search] = new Map($this, mapConf[search]);
            $this.val(maps[search].getBbox());
            jQuery('#' + search + '-searchTypeBbox-intersects').click();
        }
        else {
            $form.find('#' + search + '-map').remove();
            delete(maps[search]);
            $this.val('');
        }
    });

    /**
     * Applies datepicker functionality for every date input field in
     * @extendedSearch
     */
    jQuery('input.-js-datepicker').each(function() {
        $(this).Zebra_DatePicker({
            show_icon: true,
            offset:[-177,120],
            format: 'd-m-Y',
            lang_clear_date:'Datum löschen',
            show_select_today:"Heute",
            days_abbr:['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
            months:['Januar', 'Februar', 'März', 'April','Mai', 'Juni', 'Juli', 'August','September','Oktober','November','Dezember']
        });
    });

    //show and hide keywords / schlagwortsuche in results
    jQuery(document).on('click', '.keywords.-js-keywords', function(e) {
        e.preventDefault();

        var $this = $(this);
        var $container = $this.find('.keywords--container');

        if( $container.hasClass('hide') ) {
            $container.removeClass('hide');
        }
        else {
            $container.addClass('hide');
        }
    });

    // pagination handler for getting to next or previous page
    jQuery(document).on('click', '.pager .-js-pager-item', function() {
        search.setParam('data-id', jQuery(this).parent().attr('data-id'));
        search.setParam('pages', jQuery(this).attr('data-page'));
        search.setParam('previousPage', search.getParam('pages', 1)); //alternatevly we can use .-js-pager-item .active
        search.setParam('paginated', true);
        prepareAndSearch(undefined, true);
    });

    jQuery(document).on("click", ".-js-keyword", function() {
        var $self = jQuery(this);
        var keyword = $self.text();
        search.keyword = keyword;
        prepareAndSearch();
    });

    $(document).on('change', '#geoportal-search-extended-what input', function() {
        var v = $(this).val();
        resources[v] = $(this).is(':checked');
        $('[data-resource=' + v + ']').click();
    });

    /**
     * Activates, deactivates resources
     */
    jQuery(document).on("click", ".-js-filterarea .-js-resource", function() {
        var $self = jQuery(this);

        if ($self.hasClass('inactive')) {
            $self.removeClass('inactive');
        } else {
            $self.addClass('inactive');
        }

        var v = $self.data('resource');
        var active = !$self.hasClass('inactive');
        resources[v] = active;
        v = v.charAt(0).toUpperCase() + v.slice(1);
        $('#geoportal-checkResources' + v).prop('checked', active);
        
        prepareAndSearch();
    });

    jQuery(document).on("click", ".-js-filterarea .-js-keywords span", function() {
        var $this = jQuery(this);
        if ($.trim($this.text()) === '') {
            return;
        }
        var $searchField = jQuery('input.-js-simple-search-field');
        var searchValue = $searchField.val();
        var text = $.trim($this.text());
        if (search.keyword === text) {
            search.keyword = null;
        }

        searchValue = searchValue.replace($.trim($this.text()), '');
        $searchField.val($.trim(searchValue));

        var id = $this.parents('[data-id]').data('id');
        var opt = $('#' + id).find('option').filter(':contains(' + $.trim($this.text()) + ')')
                .filter(function() {
                    return $.trim($(this).text()) === $.trim($this.text());
                }).prop('selected', null);
        if (id === 'geoportal-searchBbox') {
            $('#geoportal-searchBbox').prop('checked', null);
        }
        if (id.match(/time/i)) {
            $('#' + id).val('');
        }

        $this.remove();
        prepareAndSearch();
    });

    /**
     * Show and Hide (toggle) results in resources/categories e.g. dataset, services, modules, mapsummary
     */
    jQuery(document).on("click", '.search-header .-js-title', function(e) {
        var $this = jQuery(this);
        $this.parents('.search-cat').siblings('.search-cat').find('.search--body').addClass('hide');
        $this.parents('.search-cat').find('.search--body').toggleClass('hide');
        $('.search-cat').each(function() {
            if ($(this).find('.search--body').hasClass('hide')) {
                $(this).find('.accordion').removeClass('open').addClass('closed');
            } else {
                $(this).find('.accordion').removeClass('closed').addClass('open');
            }
        });
    });

    $(document).on('change', '#geoportal-maxResults', function() {
        search.setParam('maxResults', $(this).val());
    });
    
    search.setParam('source', jQuery('.-js-content.active').attr('data-source'));
    autocomplete = new Autocomplete(search);

    // Avoid `console` errors in browsers that lack a console.
    (function() {
        var method;
        var methods = [
            'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
            'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
            'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
            'timeStamp', 'trace', 'warn'
        ];
        var length = methods.length;
        var console = (window.console = window.console || {});

        while (length--) {
            method = methods[length];

            // Only stub undefined methods.
            if (!console[method]) {
                console[method] = $.noop;
            }
        }
    }());
});
