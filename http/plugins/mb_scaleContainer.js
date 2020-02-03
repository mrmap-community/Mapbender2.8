var $toolbar = $(this);

var ToolbarApi = function (o) {
	var that = this;
	
	var $list = $("<ul id='scaleContainer' />").appendTo($toolbar);
	o.$target.each(function () {
		if (this === undefined) {
			return;
		}
		$list.append($(this).wrap("<li />").parent());
	});
};

$toolbar.mapbender(new ToolbarApi(options));

$(document).ready(function(){
         $('#scaleDiv').click(function(){
		$('#scaleContainer').toggle();
		$('#scaleContainer').click(function(event){event.stopPropagation();});
		$('#scaleDiv').toggleClass('scaleDivOpened');  
	 })
});
