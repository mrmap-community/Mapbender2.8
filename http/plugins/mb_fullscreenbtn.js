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
		$('#fullscreenbtn').click(function(){
                        window.open('../../portal/karten.html','_parent','');
                });
		$('.insideIframe').css("display", "none");
		$('#fullscreenbtn').attr('title', 'Vollbild verlassen');
	};
});

