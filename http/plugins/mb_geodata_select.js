/**
 * Package: mb_geodata_select
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
 * http://www.mapbender.org/User:Armin_Retterath
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

var $geodataSelect = $(this);
$geodataSelect.prepend("<img src='../img/indicator_wheel.gif'>");

var GeodataSelectApi = function (o) {
	var table = null;
	var that = this;

	var fnGetSelected = function (oTableLocal){
		var aReturn = [];
		var aTrs = oTableLocal.fnGetNodes();
		
		for ( var i=0 ; i<aTrs.length ; i++ ){
			if ( $(aTrs[i]).hasClass('row_selected') ){
				aReturn.push( aTrs[i] );
			}
		}
		return aReturn;
	};
	
	this.getData = function () {
		var req = new Mapbender.Ajax.Request({
			url: "../plugins/mb_geodata_server.php",
			method: "getGeodata",
			parameters: {
			},
			callback: function (obj, result, message) {
				if (!result) {
					return;
				}
				$geodataSelect.find("img").remove();
				
				var aoColumns = [];
				for (var i in obj.header) {
					if (obj.header[i] === "Geodata ID") {
						continue;
					}
					aoColumns.push({"sTitle": obj.header[i]});
				}

				// initialize datatables
				table = $geodataSelect.find("table").dataTable({
					"aoColumns": aoColumns,
					"bJQueryUI": true
				});
				
				// add rows
				for (var j in obj.data) {
					var data = obj.data[j];
					var wmsId = data[0];
					data.shift();
					var index = table.fnAddData(data);
					var rowNode = table.fnGetNodes(index[0]);
					$(rowNode).data("wmsId", wmsId);
				}
				
				// make rows selectable
				$geodataSelect.find("tbody").click(function (e) {
					$(table.fnSettings().aoData).each(function (){
						$(this.nTr).removeClass('row_selected');
					});
					$(e.target.parentNode).addClass('row_selected');
					var selectedRow = fnGetSelected(table);
					$geodataSelect.fadeOut(function () {
						that.events.selected.trigger({
							wmsId: $(selectedRow).data("wmsId")
						});
						$geodataSelect.show();
					});
				});
			}
		});
		req.send();
	};
	
	this.events = {
		selected: new Mapbender.Event()
	};
};

$geodataSelect.mapbender(new GeodataSelectApi(options));

$geodataSelect.mapbender("getData");
