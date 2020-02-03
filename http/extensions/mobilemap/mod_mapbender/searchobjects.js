// Dienst
function Service(serviceid, title, desc, getMapUrl, status, logoUrl, symbolLink, avail){
// Anzahl der Layer azeigen
	var service=$('<li>',{serviceid:serviceid, 'class':'service' , title:title, desc:desc, getMapURL:getMapUrl, status:status, logoUrl:logoUrl, symbolLink:symbolLink, avail:avail});
			
	var container = $('<div class="service_container"></div>');
	// Logo
 	container.append(
		$('<table style="margin: 0px; padding: 0px; float:left;">'
			+'<tr style="margin: 0px; padding: 0px;">'
				+'<td style="margin: 0px; padding: 0px; height:43px; width:55px; text-align: center; vertical-align: middle;">'
					+'<img style="max-height: 43px; max-width: 55px;  max-height: 43px;" src="'+logoUrl+'" class="service_logo" alt="Logo"/>'
				+'</td>'
			+'</tr>'
		+'</table>'));
	// Alle Layer entfernen
	container.append($('<div>', {'class':'icon layer_remove', text:' '}).click(function(){removeService($(this).parent().parent());}));
	// Alle Layer hinzufügen
	container.append($('<div>', {'class':'icon layer_add', text:' '}).click(function(){addService($(this).parent().parent());}));

	container.append('<div class="collapsible unselected" data-role="collapsible" data-collapsed="true" data-theme="a" data-content-theme="c" data-inset="true"><h3>'+title+'</h3><h3>'+title+'</h3><p>'+desc+'</p></div>');

	service.append(container)

	return service;
}


// Layer in den Hintergrundkarten
function BaseLayer(title, layerids){
	return $('<div>',{text:title, title:title, layerids:layerids, 'class':'baselayer base_unchecked'}).click(function(){ switchVisibility($(this)) });
}


// Layer im Suchergebnis
function ResultLayer(layerid, title, name, desc, previewUrl, queryable, getMapUrl, bbox){

	var layer=$('<div>',{layerid:layerid, name:name, 'class':'layer' , title:title, desc:desc, previewUrl:previewUrl, queryable:queryable, getMapURL:getMapUrl, bbox:bbox});

	layer.append($('<div>', {'class':('icon query_preview '+(queryable ? 'query_info': 'query_noinfo')), text:' '}));
	layer.append($('<div>', {'class':'layer_icon icon layer_add', text:' '}).click(function(){switchLayer($(this).parent());}));
		
	layer.append('<div class="collapsible unselected" data-role="collapsible" data-collapsed="true" data-theme="a" data-content-theme="c" data-inset="true"><h3>'+title+'</h3><h3>'+title+'</h3><p>'+desc+'</p></div>');

	return layer;
}


// Layer in der Auswahl
function OwnLayer(layerid, title, name, desc, servicetitle, servicedesc, previewUrl, queryable, getMapUrl, bbox, avail){

	var layer=$('<div>',{layerid:layerid, name:name, servicetitle:servicetitle, servicedesc:servicedesc, 'class':'layer' , title:title, desc:desc, previewUrl:previewUrl, queryable:queryable, getMapURL:getMapUrl, bbox:bbox, avail:avail});

	layer.append($('<div>', {'class':'layer_icon icon layer_remove', text:' '}).click(function(){removeLayer($(this).parent());}));

	// Abfragbarkeit
	if(queryable){
		layer.append($('<div>', {'class':'icon query_queryable query_unchecked', text:' '}).click(function(){query_check($(this))}));
	} else {
		layer.append($('<div>', {'class':'icon query_queryable query_noinfo', text:' '}));
	}	
	layer.append($('<div>', {'class':'icon layer_visibility layer_checked', text:' '}).click(function(){switchVisibility($(this).parent())}));

	// Layerpositionierung
	layer.append($('<div>', {'class':'icon move arrow_up', text:' '}).click(function(){up(layerid);}));
	
	// Collapsible
	var collaps=$('<div class="collapsible unselected" data-role="collapsible" data-collapsed="true" data-theme="a" data-content-theme="c" data-inset="true"></div>');
	collaps.append('<h3>'+title+'</h3>');
		// Inhalt
		var table = $('<table></table>');

		var col=$("<tr></tr>");

		var row1=$('<td class="layerinfo"></td>');
		
		if(previewUrl){
			row1.append($('<div>', {'class':'icon layer_preview', text:' '}).click(function(){zoomToBbox(bbox); $.mobile.changePage($("#mappage"),pageTransition);}));
		}
		row1.append('</br><p>'+avail+'%</p>');
		
		var row2=$("<td></td>");
		
		row2.append('<p><b>'+servicetitle+'</b></p><p>'+servicedesc+'</p>');
		row2.append('<p><b>'+title+'</b></p><p>'+desc+'</p>');

		col.append(row1);
		col.append(row2);

		table.append(col);

	collaps.append(table);
	
	//SP: Legende
	var legendurl = getMapUrl + 'service=wms&version=1.1.1&request=GetLegendGraphic&format=image/png&layer=' + name;
	collaps.append($('<br><p><b>Legende:</b></p>'));
	collaps.append($('<div class="legendcontainer" data-role="content"><img src="' + legendurl + '"></div>'));
	
	
	layer.append(collaps);

	return layer;

}

//SP: Feature Info Result
function FeatureResult(layername, position, featureurl, legendurl)
{	
	// Public variables
	this.layername = layername;
	this.position = position;
	this.url = featureurl;
	this.html = $('<div>', {'data-sort':position});

	// Collapsible
	var collaps = $('<div class="collapsible unselected" data-role="collapsible" data-collapsed="true" data-theme="a" data-content-theme="c" data-inline="true" data-inset="true"></div>');
	collaps.append('<h3>'+layername+'</h3>');
	
	// Legende
	collaps.append($('<div class="legendcontainer" data-role="content"><img src="' + legendurl + '"></div>'));

	// Button
	var button = $('<a data-role="button" data-icon="arrow-r" data-iconpos="right" target="_blank" href="'+featureurl+'">Info</a>');
	
	// Grid Layout
	var grid = $('<div class="ui-grid-a">');
	var block_left = $('<div class="ui-block-a">');
	block_left.append(collaps);
	var block_right = $('<div class="ui-block-b">');
	block_right.append(button);
	grid.append(block_left);
	grid.append(block_right);
	
	// Fertig
	this.html.append(grid);
}


