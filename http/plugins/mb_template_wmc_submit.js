var $submit = $(this);

var MetadataSubmitApi = function () {
	var that = this;
	var formData = {};
	
//	var serializeCallback = function (data) {
//		if (data === null) {
//			formData = null;
//			return;
//		}
//		if (formData !== null) {
//			formData = $.extend(formData, data);
//		}
//	};
	
	this.enable = function () {
		$submit.find("input[type='submit']").removeAttr("disabled");
	};
    
    this.getFormData = function(clear) {
        formData = {};
        formData.wmc_id = $("fieldset.wmc-template input#wmc_id").val();
        formData.elements = {};
        $("fieldset.wmc-template fieldset").each(function(idx, fset) {
            var target = $(fset).attr("data-target");
            formData.elements[target] = [];
            var num = 0;
            $(fset).find("p").each(function(idx1, p_el) {
                var type = $(p_el).attr("data-type");
                formData.elements[target][num] = {}
                formData.elements[target][num][type] = {};
                $(p_el).find("input").each(function(idx2, inp) {
                    var name = $(inp).attr("name");
                    if(clear) {
                        $(inp).val("");
                        formData.elements[target][num][type][name] = "";
                    } else {
                        formData.elements[target][num][type][name] = $(inp).val();
                    }
                    num++;
                });
            });
        });
        return formData;
    }
	
	this.submit = function (clear) {
        if (!this.checkWmcId()){
            return;
        }
		formData = this.getFormData(clear);
		// get metadata from server
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_template_wmc_server.php",
			method: "save",
			parameters: {
				"data": $.toJSON(formData)
			},
			callback: function (obj, result, message) {
				if (!result) {
					$("<div></div>").text(!message ? "An error occured." : message).dialog({
						modal: true
					});
					return;
				}
				$("<div></div>").text(message).dialog({
					modal: true
				});

			}
		});
		req.send();			
	};
    
    this.checkWmcId = function(){
        if($("fieldset.wmc-template input#wmc_id").val() == "") {
            return false;
        } else{
            return true;
        }
    };
	
	this.events = {
		submit: new Mapbender.Event()
	};
	
	var init = function () {
		$submit.find("input[type='submit']").bind("click", function () {
			that.submit(false);
		});
        $submit.find("input[type='button']").bind("click", function () {
			that.submit(true);
		});
	};

	init();
};

$submit.mapbender(new MetadataSubmitApi());
