var $toolbar = $(this);

var ToolbarApi = function (o) {
	var that = this;
	
	var $list = $("<ul id='toolsContainer' />").appendTo($toolbar);
	o.$target.each(function () {
		if (this === undefined) {
			return;
		}
		$list.append($(this).wrap("<li />").parent());
	});
};

$toolbar.mapbender(new ToolbarApi(options));

$(document).ready(function(){ $("#toolsContainer").hide(); });
$(document).ready(function(){
         $('.toggleToolsContainer').click(function(){
                $('#toolsContainer').toggle() && $('a.toggleToolsContainer').toggleClass('activeToggle');
		$('#tree2Container').hide() && $('.toggleLayerTree').removeClass('activeToggle'); })
});
