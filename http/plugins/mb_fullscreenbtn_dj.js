$(document).ready(function(){
	if ( self !== top ) { 
		/* inside iFrame */
		$('#fullscreenbtn').click(function(){
		window.open('../../mapbender/frames/index.php?','_parent','');	
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
                        window.open('../../portal/karten.html?mb_user_myGui='+Mapbender.gui_id,'_parent','');
                	});
			$('.insideIframe').css("display", "none");
			$('#fullscreenbtn').attr('title', 'Vollbild verlassen');
		}
		};
});

