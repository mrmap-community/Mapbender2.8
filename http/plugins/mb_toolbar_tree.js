var $toolbar = $(this);

var ToolbarApi = function (o) {
	var that = this;
	
	var $list = $("<ul id='tree2Container' />").appendTo($toolbar);
	o.$target.each(function () {
		if (this === undefined) {
			return;
		}
		$list.append($(this).wrap("<li />").parent());
	});
};

$toolbar.mapbender(new ToolbarApi(options));

$(document).ready(function(){ $("#tree2Container").hide(); });
$(document).ready(function(){
         $('.toggleLayerTree').click(function(){
		$('#tree2Container').toggle() && $('a.toggleLayerTree').toggleClass('activeToggle');
		$('#toolsContainer').hide() && $('a.toggleToolsContainer').removeClass('activeToggle');
	 })
});
