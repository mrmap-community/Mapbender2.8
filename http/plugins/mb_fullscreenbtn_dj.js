$(document).ready(function(){
	if (typeof options.parent_uri == 'undefined') {
		parent_uri = '../../portal/karten.html?mb_user_myGui='+Mapbender.gui_id;
	} else {
		parent_uri = options.parent_uri;
	}
	//alert(parent_uri);
	if ( self !== top ) { 
		//console.log(JSON.stringify(window.location.href));
		/* inside iFrame */
		$('#fullscreenbtn').click(function(){
		//window.open('../../mapbender/frames/index.php','_parent','');
		//window.open(window.location.href,'_parent','');
		// need the URL without parameters or the sessionWMC is overwritten again
        window.open(location.origin + location.pathname,'_parent','');
		});
		$('.outsideIframe').css("display", "none");
		$('#fullscreenbtn').attr('title', 'Vollbild aktivieren');
	
	} else {
		/* outside iFrame */
		if (django == "true") {
			$('#fullscreenbtn').click(function(){
                        window.open('../../map?gui_id='+Mapbender.gui_id,'_parent','');
                	});
			$('.insideIframe').css("display", "none");
			$('#fullscreenbtn').attr('title', 'Vollbild verlassen');
		
		} else {
			$('#fullscreenbtn').click(function(){
                        window.open(parent_uri,'_parent','');
                	});
			$('.insideIframe').css("display", "none");
			$('#fullscreenbtn').attr('title', 'Vollbild verlassen');
		}
		if (typeof options.parent_uri != 'undefined') {
			$('#fullscreenbtn').click(function(){
                        window.open(parent_uri,'_parent','');
                	});
			$('.insideIframe').css("display", "none");
			$('#fullscreenbtn').attr('title', 'Vollbild verlassen')
		}
		};
});



