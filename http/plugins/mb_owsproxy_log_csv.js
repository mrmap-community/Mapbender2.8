/**
 * Package: mb_owsproxy_csv
 *
 * Description:
 *
 * Files:
 *
 * SQL:
 * 
 * Help:
 *
 * Maintainer:
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

var $owsproxyCsv = $(this);

var OwsProxyCsv = function (options) {
	var that = this;
    var lastRequestData = null;
    
    this.loadForm = function(serviceType) {
        $.ajax({
            url: "../plugins/mb_owsproxy_log_csv.php",
            type: "post",
	    async: false,
            dataType: "json",
            data: {action: "getForm",XDEBUG_SESSION_START:"netbeans-xdebug", serviceType: serviceType},
            success: function(data){
                if(data.error != "") {
                    alert(data.error);
                } else {
                    $owsproxyCsv.html(data.form);
                    $owsproxyCsv.find("fieldset#owsproxy-log-result").css("display", "none");
                    $owsproxyCsv.find("input#button-logs-query").bind("click", that.loadData);
                    $owsproxyCsv.find("input#button-csv-download").bind("click", that.loadCsvData);
                    $owsproxyCsv.find("input#button-logs-delete").bind("click", that.deleteData);
                    $owsproxyCsv.find("div.field select#listType").bind("change", that.initSelects);
		    $owsproxyCsv.find("div.field select#serviceType").bind("change", that.reloadServicesAndUsers);
                    $owsproxyCsv.find("div.field select#function").bind("change", that.initSelects);
                    that.initForm();
		    
                    if(data.message != ""){
                        $owsproxyCsv.find("input#button-logs-query").attr('disabled','disabled');
                        alert(data.message);
                    }
                }
            },
	    complete: function (data) {
      		//alert(serviceType);
		//$("div.field select#servicetype").val(serviceType).prop('selected', true);
		//$('#gate').val('Gateway 2').prop('selected', true);
     	    }
        });
    };
    this.loadCsvData = function(e){
        var a = 0;
        if (lastRequestData != null){
            that.loadCsv(lastRequestData);
        }
    }
    
    this.reloadServicesAndUsers = function(e){
	//alert($("div.field select#serviceType").val());
	that.loadForm($("div.field select#serviceType").val());
	//$("div.field select#servicetype").val($("div.field select#serviceType").val()).prop('selected', true);
    }

    this.deleteData = function(e){
    	if(lastRequestData.serviceId == -1) {
    		var delMsg = "Die Logeinträge ALLER vorhandenen Dienste aus der Liste werden gelöscht. Soll diese Aktion ausgeführt werden?";
    	}
    	else {
    	var delMsg = "Die Logeinträge werden gelöscht. Soll diese Aktion ausgeführt werden?";
    	}
    	var delConfirm = confirm(delMsg);
        if(delConfirm == true){
        var a = 0;
        if (lastRequestData != null){
            var parameter = lastRequestData;
            parameter['function'] = "deleteServiceLogs";
        		if(lastRequestData.serviceId == -1) {
        			var allServices = new Array();
        			$('#serviceId option').each(function(){
        				if(this.value != "" && this.value != -1) {
        					allServices.push(this.value);
        				}
        			});
        			parameter['serviceId'] = allServices.join(',');		 	
        		}	
            that.loadJson(parameter);
            } 
        }
    }
    
    this.loadData = function(e){
        if($("div.field input#timeFrom").val() == ""){
            alert("Bitte wählen Sie \""+$('div.field label[for="timeFrom"]').text()+"\" aus.");
            $('div.field label[for="timeFrom"]').focus();
            return;
        }
        
        if($("div.field input#timeTo").val() == ""){
            alert("Bitte wählen Sie "+$('div.field label[for="timeTo"]').text()+" aus.");
            $('div.field label[for="timeTo"]').focus();
            return;
        }
        var parameter = {
                serviceType: $("div.form select#serviceType").val(), //?
                "function": $("div.field select#function").val(),
                listType: $("div.field select#listType").val(),
                timeFrom: $("div.field input#timeFrom").val(),
                timeTo: $("div.field input#timeTo").val()
            };
        if($("div.field select#listType").val() == "service"){
            if($("div.field select#serviceId").val() == ""){
                alert("Bitte wählen Sie "+$('div.field label[for="serviceId"]').text()+" aus.");
                return;
            }
            parameter["serviceId"] = $("div.field select#serviceId").val();
        } else if($("div.field select#listType").val() == "user"){
            if($("div.field select#userId").val() == ""){
                alert("Bitte wählen Sie "+$('div.field label[for="userId"]').text()+" aus.");
                return;
            }
            parameter["userId"] = $("div.field select#userId").val();
        }
        
        if($("div.field select#function").val() == "getServiceLogs" ||
            $("div.field select#function").val() == "getSum"
        ){
            if($("div.field select#listType").val() == "service"){
                if($("div.field input#withContactData").attr('checked')){
                    parameter['withContactData'] = 1;
                }
            }
            if($("div.field select#listType").val() == "user"){
                parameter["serviceId"] = $("div.field select#serviceId").val();
                if($("div.field input#withContactData").attr('checked')){
                    parameter['withContactData'] = 1;
                }
            }
        } else if($("div.field select#function").val() == "listServiceLogs"){
            if($("div.field select#listType").val() == "service"){
                if($("div.field input#withContactData").attr('checked')){
                    parameter['withContactData'] = 1;
                }
            } else if($("div.field select#listType").val() == "user"
                    && $("div.field select#serviceId").val() != ""){
                parameter["serviceId"] = $("div.field select#serviceId").val();
            }
        } else if($("div.field select#function").val() == "deleteServiceLogs"){   
            if($("div.field select#listType").val() == "user"
                    && $("div.field select#serviceId").val() != ""){
                parameter["serviceId"] = $("div.field select#serviceId").val();
            }
        }
        
        
//        parameter["serviceType"] = $("div.form input#serviceType").val();
//        if($('div.field input[name="format"]:checked').val() == "csv"){
//            that.loadCsv(parameter);
//        } else if($('div.field input[name="format"]:checked').val() == "json"){
//        
//            console.log(parameter);
            that.loadJson(parameter);
//        }
        
    };
    
    this.loadJson = function(parameter){
        parameter['action'] = "getJson";
        parameter['XDEBUG_SESSION_START'] = "netbeans-xdebug"; // to delete
        $.ajax({
            url: "../plugins/mb_owsproxy_log_csv.php",
            type: "post",
            dataType: "json",
            data: parameter,
            async: false,
            success: function(data){
                //alert(data.header);
                lastRequestData = null;
                if(data.error != "") {
                    alert(data.error);
                } else {
//                    alert(data.header);
                    if(data.message != ""){
                        $owsproxyCsv.find("fieldset#owsproxy-log-result").css("display", "none");
                        alert(data.message);
                        self.location.reload();
                    } else {
                    	if(data.function == "getServiceLogs") {
                            var fs = $owsproxyCsv.find("#queryResult");
                            fs.text(fs.attr('data-title')+" "+(data.dataDisplay.length >= data.limit ? fs.attr('data-limit').replace("XXX", data.limit) : fs.attr('data-count').replace("XXX", data.dataDisplay.length)));
                            lastRequestData = parameter;
                            var result = '<table class="result"><tr class="result-header">';
                            var col_layer_num = -1;
                            for(var i = 0; i < data.headerDisplay.length; i++){
                                if(data.headerDisplay[i] == "layer_featuretype_list"){
                                    col_layer_num = i;
                                }
                                result += '<td>' + data.headerDisplay[i] + '</td>'
                            }
                            result += '</tr>';

                            // Summe hier berechnen!

                            for(i = 0; i < data.dataDisplay.length; i++){
                                result += '<tr>';
                                for(var j = 0; j < data.dataDisplay[i].length; j++){
                                    if(col_layer_num != -1 && col_layer_num == j && data.dataDisplay[i][j] != null){
                                        result += '<td>' + (data.dataDisplay[i][j].replace(/,/g, ", ")) + '</td>';
                                    } else {
                                        result += '<td>' + data.dataDisplay[i][j] + '</td>';
                                    }
                                }
                                result += '</tr>';
                            }
                            result += '</table>';
                    	}
                    	else {
                        var fs = $owsproxyCsv.find("#queryResult");
                        fs.text(fs.attr('data-title')+" "+(data.data.length >= data.limit ? fs.attr('data-limit').replace("XXX", data.limit) : fs.attr('data-count').replace("XXX", data.data.length)));
                        lastRequestData = parameter;
                        var result = '<table class="result"><tr class="result-header">';
                        var col_layer_num = -1;
                        for(var i = 0; i < data.header.length; i++){
                            if(data.header[i] == "layer_featuretype_list"){
                                col_layer_num = i;
                            }
                            result += '<td>' + data.header[i] + '</td>'
                        }
                        result += '</tr>';

                        // Summe hier berechnen!

                        for(i = 0; i < data.data.length; i++){
                            result += '<tr>';
                            for(var j = 0; j < data.data[i].length; j++){
                                if(col_layer_num != -1 && col_layer_num == j && data.data[i][j] != null){
                                    result += '<td>' + (data.data[i][j].replace(/,/g, ", ")) + '</td>';
                                } else {
                                    result += '<td>' + data.data[i][j] + '</td>';
                                }
                            }
                            result += '</tr>';
                        }
                        result += '</table>';
                    	} 
                        $('div#result').html(result);
                        $owsproxyCsv.find("fieldset#owsproxy-log-result").css("display", "block");
                    }
                    
                }
            }
        });
    };
    
    this.loadCsv = function(parameter){
        parameter['action'] = "getCsv";
        parameter['XDEBUG_SESSION_START'] = "netbeans-xdebug"; // to delete
        var get = "";
        for(var name in parameter){
            get += "&" + name + "=" + parameter[name];
        }
        $('iframe#csv-download').attr("src", "../plugins/mb_owsproxy_log_csv.php?"+get.substring(1));
//        $.download("../plugins/mb_owsproxy_log_csv.php", parameter, "post");
//        $('csv-download').src();
//        parameter['XDEBUG_SESSION_START'] = "netbeans-xdebug"; // to delete
//        $.ajax({
//            url: "../plugins/mb_owsproxy_log_csv.php",
//            type: "post",
//            dataType: "json",
//            data: parameter,
//            success: function(data){
////                if(data.error != "") {
////                    alert(data.error);
////                } else {
////                    alert(data.elements);
////                }
//            }
//        });
    };
	
	this.initSelects = function () {
		serviceType = $("div.field select#serviceType").val();
        $('div.field select#serviceId option[value=""]').attr('selected','selected');
        $('div.field select#userId option[value=""]').attr('selected','selected');
        $("div.field input#withContactData").removeAttr('checked');
        
        if($("div.field select#listType").val() == "user"){
            $("div.field select#serviceId").attr('disabled', "disabled");
//                $("div.field select#serviceId ").parent().addClass('field-disabled');
//                $("div.field select#userId ").parent().removeClass('field-disabled');
            $("div.field select#userId").removeAttr('disabled');
            $("div.field select#serviceId ").parent().css("display", "none");
            $("div.field select#userId ").parent().css("display", "block");
        } else if($("div.field select#listType").val() == "service"){
            $("div.field select#serviceId").removeAttr('disabled');
            $("div.field select#userId").attr('disabled', "disabled");
//                $("div.field select#serviceId ").parent().removeClass('field-disabled');
//                $("div.field select#userId ").parent().addClass('field-disabled');
            $("div.field select#serviceId ").parent().css("display", "block");
            $("div.field select#userId ").parent().css("display", "none");
        }
        
        $('div.field select#serviceId option[value="-1"]').attr("disabled","disabled");
        $('div.field select#userId option[value="-1"]').attr("disabled","disabled");

        
        if($("div.field select#listType").val() == "user"
                && $("div.field select#function").val() == "deleteServiceLogs"){
            $("div.field select#serviceId").removeAttr('disabled');
//            $("div.field select#serviceId ").parent().removeClass('field-disabled');
            $("div.field select#serviceId ").parent().css("display", "block");
        } else if($("div.field select#listType").val() == "user"
                && $("div.field select#function").val() == "listServiceLogs"){
            $("div.field select#serviceId").removeAttr('disabled');
//            $("div.field select#serviceId ").parent().removeClass('field-disabled');
            $("div.field select#serviceId ").parent().css("display", "none");
        } else if($("div.field select#listType").val() == "user"
                && ($("div.field select#function").val() == "getServiceLogs" ||
                    $("div.field select#function").val() == "getSum"
                )
        ){
            $("div.field select#serviceId").removeAttr('disabled');
//            $("div.field select#serviceId ").parent().removeClass('field-disabled');
            $("div.field select#serviceId ").parent().css("display", "block");
            $('div.field select#serviceId option[value="-1"]').removeAttr("disabled");
            $('div.field select#userId option[value="-1"]').removeAttr("disabled");
        }
        
        if($("div.field select#listType").val() == "service"
                && ($("div.field select#function").val() == "getServiceLogs" ||
                    $("div.field select#function").val() == "getSum"
                )
        ){
//            $("div.field input#withContactData").parent().removeClass('field-disabled');
            $("div.field input#withContactData").parent().css("display", "block");
            $('div.field select#serviceId option[value="-1"]').removeAttr("disabled");
            $('div.field select#userId option[value="-1"]').removeAttr("disabled");
        } else if($("div.field select#listType").val() == "service"
                && ($("div.field select#function").val() == "getServiceLogs" ||
                    $("div.field select#function").val() == "getSum"
                )
        ){
//            $("div.field input#withContactData").parent().removeClass('field-disabled');
            $("div.field input#withContactData").parent().css("display", "block");
        } else if($("div.field select#listType").val() == "user"
                && ($("div.field select#function").val() == "getServiceLogs" ||
                    $("div.field select#function").val() == "getSum"
                )
        ){
//            $("div.field input#withContactData").parent().removeClass('field-disabled');
            $("div.field input#withContactData").parent().css("display", "block");
        } else {
//            $("div.field input#withContactData").parent().addClass('field-disabled');
            $("div.field input#withContactData").parent().css("display", "none");
        }
        return serviceType;
	};
    
    
    this.initForm = function(e){
        
        $("div.field input#timeFrom").attr("readonly", "readonly");
        $("div.field input#timeTo").attr("readonly", "readonly");
        
        var serviceType = this.initSelects();
        // that.loadForm(serviceType);
	//$("div.field select#serviceType").bind("select",alert($("div.field select#serviceType").val()));//that.loadForm($("div.field select#serviceType").val())
        $(function() {
            $("div.field input#timeFrom").datetimepicker({
                dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
                monthNames: ["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],
                timeOnlyTitle: 'Zeit wählen',
                timeText: 'Zeit',
                hourText: 'Stunde',
                minuteText: 'Minute',
                currentText: 'Jetzt',
                closeText: 'Fertig',
                timeFormat: 'hh:mm tt',
                dateFormat: 'yy-mm-dd',
                ampm: false,
                onClose: function(dateText, inst) {
                    var endDateTextBox = $("div.field input#timeTo");
                    if (endDateTextBox.val() != '') {
                        var testStartDate = new Date(dateText);
                        var testEndDate = new Date(endDateTextBox.val());
                        if (testStartDate > testEndDate)
                            endDateTextBox.val(dateText);
                    }
                    else {
                        endDateTextBox.val(dateText);
                    }
                },
                onSelect: function (selectedDateTime){
                    var start = $(this).datetimepicker('getDate');
                    $("div.field input#timeTo").datetimepicker('option', 'minDate', new Date(start.getTime()));
                }
            });
            $("div.field input#timeTo").datetimepicker({
                dayNamesMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
                monthNames: ["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],
                timeOnlyTitle: 'Zeit wählen',
                timeText: 'Zeit',
                hourText: 'Stunde',
                minuteText: 'Minute',
                currentText: 'Jetzt',
                closeText: 'Fertig',
                timeFormat: 'hh:mm tt',
                dateFormat: 'yy-mm-dd',
                ampm: false,
                onClose: function(dateText, inst) {
                    var startDateTextBox = $("div.field input#timeFrom");
                    if (startDateTextBox.val() != '') {
                        var testStartDate = new Date(startDateTextBox.val());
                        var testEndDate = new Date(dateText);
                        if (testStartDate > testEndDate)
                            startDateTextBox.val(dateText);
                    }
                    else {
                        startDateTextBox.val(dateText);
                    }
                },
                onSelect: function (selectedDateTime){
                    var end = $(this).datetimepicker('getDate');
                    $("div.field input#timeFrom").datetimepicker('option', 'maxDate', new Date(end.getTime()) );
                }
            });
        });
        $("#ui-datepicker-div").css("display","none");
    };
    
	Mapbender.events.init.register(function () {
        that.loadForm('wms');
	});
};
$owsproxyCsv.mapbender(new OwsProxyCsv(options));


$(document).ready(function() {
    $('#user_gui').live('change', function() {
        $.ajax({
            url: "../plugins/mb_owsproxy_log_csv.php",
            type: "post",
            dataType: "html",
            data: {'userGuiId': $(this).val()},
            success: function(data) {
                $('#user_gui_result').html(data);
            }
        });

    });
});
