var $toolbar = $(this);

var ToolbarApi = function (o) {
	var that = this;
	
	var $list = $("<ul id='carouselContainer' />").appendTo($toolbar);
	o.$target.each(function () {
		if (this === undefined) {
			return;
		}
		$list.append($(this).wrap("<li />").parent());
	});
};

$toolbar.mapbender(new ToolbarApi(options));

$(document).ready(function(){
        $('#carouselDiv_btn').click(function(){
		$('#carouselContainer').toggle();
		$('#carouselContainer').click(function(event){event.stopPropagation();});
		$('#carouselDiv_btn').toggleClass('carouselDiv_btn_Opened');
		$('.carouselDiv_btn_name').toggle();
	})
});
