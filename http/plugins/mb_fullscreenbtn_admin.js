$(document).ready(function(){
	if ( self !== top ) { 
		/* inside iFrame */
		$('#fullscreenbtn').click(function(){
		window.open(window.location.href,'Administrations Fenster','_parent','scrollable=yes,resizeabler=yes');	
		});
		$('.outsideIframe').css("display", "none");
		$('#fullscreenbtn').attr('title', 'Vollbild aktivieren');
	
	} else {
		/* outside iFrame */
		$('#fullscreenbtn').css('display', 'none');
	};
});

