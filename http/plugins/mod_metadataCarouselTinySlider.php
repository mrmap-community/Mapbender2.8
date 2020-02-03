<?php
/**
 * Package: mod_metadataCarouselTinySlider
 * package from 
 * https://github.com/ganlanyuan/tiny-slider
 *
 * Description:
 * This modul generates a div with a dynamic slider to allow the selection
 * of single loadable metadata resources - wmc, ...
 * The module shoudl be invoked thru a button click. The button may be a mb_button.js module
 * see also mod_WMSpreferencesDiv or overview toogle
 * 
 * 
 * Files:
 *  - http/plugins/mod_metadataCarouselTinySlider.php
 *
 * SQL:
 * resources/db/module_metadataCarousel.sql
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License 
 * and Simplified BSD license.  
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
$e_id = 'metadataCarouselTinySlider';

//include all element vars from the given element
include '../include/dyn_js.php';
include '../include/dyn_php.php';

//if we need some other elements fom the database - do the stuff
//echo "var mod_gui_wms_visible = '".$vis."';";

?>

options.loadMessage = "<?php echo _mb('Load theme');?>";

var myIsNumeric = Number.isInteger || function(value) {
  return typeof value === 'number' && 
    isFinite(value) && 
    Math.floor(value) === value;
};


//load options from element vars - if not already be done before
//element_vars we need
// 1. script name of the searchInterface to use
// 2. slidesPerSide - number of tiles to show together
// 3. maxResults - number of objects to get from the searchInterface with one call (should be a factor of 2. )
// 4. resource type - wmc/layer
// 5. list of resource ids to filter {[1,2,3]}
// 6. size of images, ...
// 7. allowResize - like in loadwmc cause a function is borrowed from there

options.resourceFilter = "[3,11,12]";

options.resourceFilter = "[]";

if (options.allowResize == "true") {
    options.allowResize = true;
} else {
    options.allowResize = false;
}

if(myIsNumeric(options.maxResults)) {
} else {
    options.maxResults = 6;
}
//debugging - TODO alter later on
options.maxResults = 6;
if(myIsNumeric(options.slidesPerSide)) {   
} else {
    options.slidesPerSide = 3;
}

if (typeof options.searchUrl == 'undefined') {
    options.searchUrl = "../php/mod_callMetadata.php?";
} else {
    //alert(options.searchUrl);
}
//for debugging purposes
//options.searchUrl = "../php/mod_callMetadataRemote.php?";



var metadataCarouselTinySlider = function() {
    var that = this;
    this.id = options.id; //id of the upper div tag from mapbender element
    this.resourceFilter = JSON.parse(options.resourceFilter);

    if (this.resourceFilter.length > 0){
        this.resourceFilterString = "&resourceIds="+this.resourceFilter.join(',');
    } else {
        this.resourceFilterString = "";
    }

    this.initForm = function() {
        this.tinySliderContainer = $('#metadataCarouselTinySlider');
	this.tinySliderContainer.addClass('slider-container');

    //example from tiny-slider
    /*<ul class="controls" id="customize-controls" aria-label="Carousel Navigation" tabindex="0">
        <li class="prev" data-controls="prev" aria-controls="customize" tabindex="-1">
            <i class="fas fa-angle-left fa-5x"></i>
        </li>
        <li class="next" data-controls="next" aria-controls="customize" tabindex="-1">
            <i class="fas fa-angle-right fa-5x"></i>          
        </li>
    </ul>*/

	this.controlsContainer = $(document.createElement('ul')).appendTo(this.tinySliderContainer);
        this.controlsContainer.addClass('controls');
	this.controlsContainer.attr('id', 'customize-controls');
	this.controlsContainer.attr('aria-label', 'Carousel Navigation');
	this.controlsContainer.attr('tabindex', '0');

	this.prevContainer = $(document.createElement('li')).appendTo(this.controlsContainer);
	this.prevContainer.addClass('prev');

	this.prevContainer.attr('data-controls', 'prev');
	this.prevContainer.attr('aria-controls', 'customize');
	this.prevContainer.attr('tabindex', '-1');

	this.prevTitleContainer = $(document.createElement('i')).appendTo(this.prevContainer);
	this.prevTitleContainer.addClass('fas');
	this.prevTitleContainer.addClass('fa-angle-right');
	this.prevTitleContainer.addClass('fa-5x');

	this.nextContainer = $(document.createElement('li')).appendTo(this.controlsContainer);
	this.nextContainer.addClass('next');

	this.nextContainer.attr('data-controls', 'next');
	this.nextContainer.attr('aria-controls', 'customize');
	this.nextContainer.attr('tabindex', '-1');

	this.nextTitleContainer = $(document.createElement('i')).appendTo(this.nextContainer);
	this.nextTitleContainer.addClass('fas');
	this.nextTitleContainer.addClass('fa-angle-right');
	this.nextTitleContainer.addClass('fa-5x');

	this.sliderContainer = $(document.createElement('div')).appendTo(this.tinySliderContainer);
	this.sliderContainer.addClass('my-slider');

        //hide during initialization
        //$('#' + options.id).hide();

	//add event for loaded wmc like done in javascripts/mod_loadwmc.js
	/*this.events = {
		loaded: new Mapbender.Event()
	};*/


        //add 3 dummy slides to container identified by class dummy
        this.sliderContainer.append('<div class="slider-item dummy"><div class="card"><img src="https://www.geoportal.rlp.de/mapbender/geoportal/mod_showPreview.php?resource=wmc&id=2506" alt=""><h2>Titel 1</h2><p class="card_description">Loresm ipsum dolor sit amet consectetur adipisicing elit. Dignissimos, voluptas!</p></div></div><div class="slider-item dummy"><div class="card"><img src="https://www.geoportal.rlp.de/mapbender/geoportal/mod_showPreview.php?resource=wmc&id=2506" alt=""><h2>Titel 2</h2><p class="card_description">Loresm ipsum dolor sit amet consectetur adipisicing elit. Dignissimos, voluptas!</p></div></div><div class="slider-item dummy"><div class="card"><img src="https://www.geoportal.rlp.de/mapbender/geoportal/mod_showPreview.php?resource=wmc&id=2506" alt=""><h2>Titel 3</h2><p class="card_description">Loresm ipsum dolor sit amet consectetur adipisicing elit. Dignissimos, voluptas!</p></div></div>');
    }
    //end of function initForm

    var targetName = options.target;
    var maxResults = options.maxResults;
    var currentPage = 0;
    var maxPages = 1; //set default to 1 page 
    var numberOfResults = 0;
    var searchUrl = options.searchUrl;
    var slidesPerSide = options.slidesPerSide;

    //first init form
    that.initForm();

    this.loadMore = function(){
that.removeEvent();
        //alert("actual_loaded_page: "+ currentPage +" - max pages: "+maxPages); //1-10 (100)
        //hide further loading button while loading more data
        //$('.show_more_button').hide();
        //load next page
        $.ajax({url: searchUrl+"searchText=*&searchResources=wmc&searchPages="+(parseInt(currentPage) + parseInt(1))+"&maxResults="+maxResults+that.resourceFilterString, async: false, success: function(result){
            result.wmc.srv.forEach(that.addElementToSlider);
            //increase global var currentPage
            currentPage = parseInt(currentPage) + parseInt(1);
            //unbind all old click events
            $('img.load_image').unbind('click');
            //reinitialize click event
            $('img.load_image').click(function(){
                var $this = $(this);
                resourceId = $this.attr("resourceid");
                resourceTitle = $this.attr("resourceTitle");
                alert(options.loadMessage + ": " + resourceTitle);
                that.executeJavaScript({method:"loadWmc", parameters:{id:resourceId}});
            });
        }});
        //alert("current page - to goto afterwards!: "+currentPage);
        that.mainSlider.goTo(parseInt(currentPage * slidesPerSide) + 1);
        that.addEvent();
    }

    //invoke from plugins folder
    //import {tns} from '../../extensions/tiny-slider-master/src/tiny-slider.js';
    //this is done in mapbender database - reference to the js file in the extension folder

    //initialize tiny-slider
    this.mainSlider = $('.slider-container');
    that.mainSlider = tns({
        container: '.my-slider',
        items: slidesPerSide,
        slideBy: 'page',
        autoplay: false,
        mouseDrag: true,
        loop: false,
	controlsText: ['<svg class="direction" width="17" height="18" viewBox="0 0 18 18" fill="none"  xmlns="http://www.w3.org/2000/svg"><path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"/></svg>','<svg class="direction" width="17" height="18" viewBox="0 0 18 18" fill="none"  xmlns="http://www.w3.org/2000/svg"><path d="M16.0142 11.6191L14.6042 13.0291L9.01416 7.43914L3.42416 13.0291L2.01416 11.6191L9.01416 4.61914L16.0142 11.6191Z" stroke="currentColor" stroke-width="2"/></svg>'],
    });



    this.fillInitialMetadata = function() {
        //initialize slider from first request to search interface
        var initialResult = $.ajax({url: searchUrl+"searchText=*&searchResources=wmc&maxResults="+maxResults+that.resourceFilterString, async: false, success: function(result){
	//var initialResult = $.ajax({url: searchUrl+"searchText=*&searchResources=wmc&maxResults="+maxResults+that.resourceFilterString, async: true, success: function(result){
            if (result.valid) 
                return true;
            else 
                return false;
        }});
        var initialSearchJson = JSON.parse(initialResult.responseText);
        //extract number of currrent page, rpp and max results
        currentPage = initialSearchJson.wmc.md.p;
        numberOfResults = initialSearchJson.wmc.md.nresults;
        maxPages = Math.ceil(numberOfResults / maxResults);
        //remove temporary main slider content
        $("div.my-slider").html("");
        JSON.parse(initialResult.responseText).wmc.srv.forEach(that.addElementToSlider);
        //initialize load on click event for initial items
        $('img.load_image').click(function(){
            var $this = $(this);
            resourceId = $this.attr("resourceId"); 
            resourceTitle = $this.attr("resourceTitle"); 
            alert(options.loadMessage + ": " + resourceTitle);
            that.loadWmcById(resourceId);
        });
    }

    this.addElementToSlider = function(item) {
        that.mainSlider.destroy();	
        $("div.my-slider").append('<div class="slider-item" resourceId="'+item.id+'" title="' + item.title + ' - ' + item.abstract + '"><div class="card"><span class="load-count">'+item.loadCount+'</span><img class="load_image" src="../img/osgeo_graphics/document-send-symbolic.symbolic.png" title="Load" resourceId="'+item.id+'" resourceTitle="'+item.title+'"><img src="'+item.previewURL+'" alt=""></div></div>');
        $(".dummy").remove();
        that.mainSlider = that.mainSlider.rebuild();  
        //add events again! 
        //that.addEvent();
    }

    this.loadWmcById = function(wmcId){
       //alert(options.loadMessage + ": " + wmcId);
       //Mapbender.modules.loadwmc.executeJavaScript({method:"loadWmc", parameters:{id:wmcId}});
       that.executeJavaScript({method:"loadWmc", parameters:{id:wmcId}});
    }

    this.addEvent = function(){
        //add event 
        this.customizedFunction = function (info, eventName) {
            // direct access to info object
            //console.log(info.event.type, info.container.id);
            //if on last page ... - try to reload further data via ajax call
	    if ((Math.ceil((that.mainSlider.getInfo().displayIndex / slidesPerSide)) == that.mainSlider.getInfo().pages)) {
            // TODO - check if on last side of all database results && Math.ceil(parseInt(numberOfResults) / slidesPerSide))
                //alert("On last page and some more elements are available!");
                that.loadMore();
            }
        }    
        // bind function to event
        that.mainSlider.events.on('indexChanged', that.customizedFunction);
    }

    this.removeEvent = function(){
        that.mainSlider.events.off('indexChanged', that.customizedFunction);
    }
    //wait some time?
    //setTimeout(that.fillInitialMetadata(), 3000);
        //workaround
	var kml = $('#mapframe1').data('kml');
    that.fillInitialMetadata(); 
    that.addEvent();

//copied from javascripts/mod_loadwmc.js because it will not available, if loadwmc window has been closed somewhen!!!!
	this.executeJavaScript = function (args) {
		var req = new Mapbender.Ajax.Request({
			url: "../php/mod_loadwmc_server.php",
			method: args.method,
			parameters: args.parameters,
			callback: function (obj, result, message) {
				if (!result) {
					new Mapbender.Warning(message);
					return;
				}
				try {
					if (args.method === "deleteWmc" || args.method === "setWMCPublic"){
						return;
					}
					//things that have been done to load wmc
					if (obj.javascript && typeof(obj.javascript) == "object") {
						for (var j = 0; j < obj.javascript.length; j++) {
							//TODO: prohibit multiple maprequests when load wmc, cause on maprequests the wmc maybe stored to session :-(
							//alert("Statement: " + obj.javascript[j]);
							//eventAfterLoadWMS.trigger(); -- load the each wms again and saves the wmc to session for each wms - prohibit this behaviour!!!! - Done by global lock_maprequest in class_wmc.php line 1220+!!
							//console.log("Statement: " + obj.javascript[j]);
							eval(obj.javascript[j]);                                           
						}

						if (options.allowResize == true) {
						    if (Mapbender.modules.resizeMapsize) {
						        //alert("Module resizeMapsize is available!");
						        try {$('#resizeMapsize').trigger("click");} catch (e) {alert(e)};
						    }
						} else {
						    //alert("allowResize not defined");
						}
						if (args.method === "loadWmc" || args.method === 'loadWmcFromFile') {
                                                    var kml = $('#mapframe1').data('kml');
                                                    if(kml) {
                                                        try {
                                                            $.each(kml.kmlOrder, function(_, v) {
                                                                $('li[title="' + v + '"]').unbind().find('*').unbind();
                                                                $('li[title="' + v + '"]').remove();
                                                            });
                                                            kml._kmls = JSON.parse(restoredWmcExtensionData.KMLS);
                                                            kml.cache = {};
                                                            kml.kmlOrder = JSON.parse(restoredWmcExtensionData.KMLORDER);
                                                            kml.render();
                                                            for(var k in kml._kmls) {
                                                                kml.element.trigger('kml:loaded', kml._kmls[k]);
                                                            }
                                                        } catch(e) {
                                                            // just ignore the exception for now
                                                        }
                                                    }
						    //following will not work, because event is not defined here
						    //that.events.loaded.trigger({
						    //		extensionData: restoredWmcExtensionData
						    //});
                                                //following copied from wfsConTree.js - because there it is only called via loadwmc module, which is not available in new GUI types (>2019 fullcreen with new layout. wfsConfTree.js / wfsConfTree_single.js triggers the loadwmc module!)
						/*load wfs confs via ajax*/
                                                if (restoredWmcExtensionData && restoredWmcExtensionData.WFSCONFIDSTRING) {
							var req = Mapbender.Ajax.Request({
								url: 	"../php/mod_wfs_conf_server.php",
								method:	"getWfsConfsFromId",
								parameters: {
									wfsConfIdString: restoredWmcExtensionData.WFSCONFIDSTRING
								},
								callback: function(result,success,message){
                                                                        //alert("try to reset wfsConfTree");
									//alert(JSON.stringify(Mapbender.modules.wfsConfTree));
									if (Mapbender.modules.wfsConfTree) {
									    Mapbender.modules.wfsConfTree.reset(result);
									}
								}
							});
							req.send();
						}
                                                /*end load wfs confs via ajax*/
						}
					}
					that.hide();
					new Mapbender.Notice(args.message);
				}
				catch (e) {
					new Mapbender.Exception(e.message);
				}
			}
		});
		req.send();
	};
}
//register object in mapbender!


Mapbender.events.init.register(function() {
    Mapbender.modules[options.id] = $.extend(new metadataCarouselTinySlider(),Mapbender.modules[options.id]);	
});

