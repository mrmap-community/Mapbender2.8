var $selectScale = $(this);

var SelectScaleApi = function () {
	var that = this;
	
	this.set = function (scale) {
        // IE call this with empty scale for some convoluted reason
        if(!scale){ return; }
        options.$target.mapbender().repaintScale(null,null,scale);
        //Bugfix for IE9: Set selected scale option explicitly to avoid an empty option value in IE9
        $selectScale.children("option[value='" + scale  + "']").attr('selected', 'true');
	};

	var init = function () {
		$selectScale.change(function () {
			that.set(this.value);
		});
		
		Mapbender.events.init.register(function () {
			options.$target.mapbender(function () {
				var map = this;
				map.events.afterMapRequest.register(function () {
					var scale = map.getScale();
					$selectScale.children("option").eq(0).html("1 : " + scale);
					$selectScale.children("option").eq(0).attr("selected", "true");
						
				});
			});
		});
	};
	init();
};

$selectScale.mapbender(new SelectScaleApi());
