<?php
include '../include/dyn_js.php';
?>
if(typeof(scaleContainerClosed)==='undefined' || scaleContainerClosed === 'true'){
        var scaleContainerClosed = true;
} else {
        var scaleContainerClosed = false;
};
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
        if(scaleContainerClosed===false){
                $('#scaleDiv').click();
        }
});
