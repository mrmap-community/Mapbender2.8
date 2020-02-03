'use strict';
/* globals Storage, jQuery, autocomplete, document, $, window */

var Search = function() {
    Storage.apply(this, arguments);

    this.timeoutDelay = 300;
    this.searchUrl = null;
};

Search.prototype = {
    '__proto__': Storage.prototype,
    setBasedir: function(basedir) {
        this.searchUrl = basedir + 'server.php'; //WTF?
    },

    getAjaxDeferred: function() {
        var def = $.Deferred();
        function timeoutFunc() {
            if ( $.active === 0 ) {
                def.resolve();
            } else {
                window.setTimeout( timeoutFunc, 200 );
            }
        }
        timeoutFunc();
        return def;
    },

    hideLoadingAfterLoad: function() {
        this.getAjaxDeferred()
            .done( function() {
                $('#-js-loading').hide();
            });
    },

    showLoading: function() {
        $('#-js-loading').show();
    },

    autocomplete: function() {
        var self = this;
        if (this.searching) {
            return;
        }
        this.showLoading();
        jQuery.ajax({
            url: self.searchUrl,
            data: {
                'source': self.getParam('source'),
                'terms':  self.getParam('terms'),
                'type': 'autocomplete'
            },
            type: 'post',
            dataType: 'json',
            success: function(data) {
                self.parseAutocompleteResult(data);
                self.hideLoadingAfterLoad();
            }
        });
    },
    find: function() {
        var self = this;
        this.searching = true;
        var terms = this.getParam('terms');
        try {
            terms = terms.replace(new RegExp('[^a-zA-Z0-9]+', 'ug'), ' ');
        } catch (e) {
            // u doesn't work in IE
            terms = terms.replace(/[\.,/!@#$%^*()]/g, ' ');
        }
        terms = terms.split(' ');
        terms = terms.filter(function(val) {
            return val !== '';
        });
        terms = terms.join(',');
        if (this.keyword) {
            terms += ',' + this.keyword;
        }
        this.showLoading();
        jQuery.ajax({
            url: self.searchUrl,
            data: {
                'source': self.getParam('source'),
                'type': 'results',
                'terms': terms,
                'extended': self.getParam('extended'),
                'page-geoportal': self.getParam('pages'),
                'data-geoportal': self.getParam('data-id'),
                'keywords':  self.getParam('keywords'),
                'resources': self.getParam('resources')
            },
            type: 'post',
            dataType: 'json',
            success: function(data) {
                self.parseSearchResult(data);
                self.hideLoadingAfterLoad();
            }
        })
            .always(function() {
                self.searching = false;
            });
    },

    parseSearchResult: function(data) {
        var self = this;

        if (data === null) {
            return false;
        }

        if (typeof data.html !== 'undefined') {
            jQuery('.-js-content.active .-js-result').html(data.html.content);
        }

        // see if pagination was used than display the current resource the user has used the paginator
        var sPaginated = self.getParam('paginated');

        // if user has used the pagination we display the current resource body
        if (sPaginated === 'true') {
            var sResourceId = self.getParam('data-id');
            var sResourceBody = '.' + sResourceId + '.search--body';

            var $title = jQuery(sResourceBody)
                            .closest('.search-cat')
                            .find('.search-header')
                            .find('.source--title')
            ;
            $title.click(); //execute the accordion because of the icon
        }

        //set the paginator back to false
        self.setParam('paginated', false);

        $('.-js-resource').addClass('inactive');
        $('#geoportal-search-extended-what input').prop('checked', null);
        $.each(data.response, function(resource) {
            $('[data-resource=' + resource + ']').removeClass('inactive');
            var r = resource.charAt(0).toUpperCase() + resource.slice(1);
            $('#geoportal-checkResources' + r).prop('checked', true);
        });

        return undefined;
    },

    parseAutocompleteResult: function(data) {
        autocomplete.show(data.response.resultList);
    },
    parseQuery: function() {
        var self = this;
        var url = document.URL;
        var query = [];

        if (url.indexOf("?") !== -1) {
            url = url.substr(url.indexOf("?") + 1);
            url = url.split("&");

            for (var i = 0; i < url.length; i++) {
                var tmp = url[i].split("=");
                query[tmp[0]] = encodeURIComponent(tmp[1]);
            }
        }
        return query;
    },
    hide: function() {
        $('.-js-result').addClass("hide");
    },
    show: function() {
        $('.-js-result').removeClass("hide");
    }
};
