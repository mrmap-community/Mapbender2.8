<?php
//show catalogue statistics
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
$languageCode = "de";

//e.g. tabs and their content
$html = '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$languageCode.'">';
$html .= '<body onload="getStatisticsFromServer(\'NUTS_1\');">';
$metadataStr .= '<head>' . 
		'<title>'._mb("").'</title>' . 
		'<meta name="description" content="'._mb("").'" xml:lang="'.$languageCode.'" />'.
		'<meta name="keywords" content="'._mb('').'" xml:lang="'.$languageCode.'" />'	.	
		'<meta http-equiv="cache-control" content="no-cache">'.
		'<meta http-equiv="pragma" content="no-cache">'.
		'<meta http-equiv="expires" content="0">'.
		'<meta http-equiv="content-language" content="'.$languageCode.'" />'.
		'<meta http-equiv="content-style-type" content="text/css" />'.
		'<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">' . 	
		'</head>';
$style = <<< HTML
<style>
#pie2 svg {
  display:block;
  margin: 0 auto;
}

*{overflow:hidden;}
.ui-widget-overlay {background: #000 none repeat scroll 0 0 !important;opacity: 0.70 !important;}
.buttons {
  text-align: center;
  }
button {
    background: #e6e6e6 none repeat scroll 0 0;
    border: 1px solid #d3d3d3;
    border-radius: 3px;
    color: #555555;
    margin: 0 10px 0 0;
    padding: 5px 10px 6px ;
    cursor:pointer;
}
button:hover {
    background: #dadada none repeat scroll 0 0;
    border: 1px solid #999999;
    color: #212121;
}
button:focus {
    background: #8AAFC9 none repeat scroll 0 0 ;
    border: 1px solid #999999;
    color: #ffffff;
}    
button:active {
    background: #dadada none repeat scroll 0 0 !important
    border: 1px solid #999999 !important;
    color: #212121 !important;
}
#pie svg {
  display:block;
  margin: 0 auto;
}

.outer {
  position: relative;
  justify-content: center;
}
.inner {
  margin-right:auto;
  margin-left:auto;
} 
</style>
HTML;
$html .= $style;
//css	
$html .= '<link type="text/css" href="../extensions/jquery-ui-1.8.1.custom/css/custom-theme/jquery-ui-1.8.5.custom.css" rel="Stylesheet" />';
$html .= $metadataStr;
$html .= '<div id="buttons" class="buttons">';
$html .= '<button class="pulldata" id="NUTS_1" value="NUTS_1">Land</button>';
$html .= '<button class="pulldata" id="NUTS_2" value="NUTS_2">Regierungsbezirk</button>';
$html .= '<button class="pulldata" id="NUTS_3" value="NUTS_3">Kreis</button>';
$html .= '<button class="pulldata" id="LAU_1" value="LAU_1">Kommunalverband</button>';
$html .= '<button class="pulldata" id="LAU_2" value="LAU_2">Kommune</button>';
$html .= '<button class="pulldata" id="other" value="other">Andere</button>';
$html .= '</div>';
$html .= '<div id="pie" class="inner"></div>';
$html .= '<div id="dialog" class="inner"><div class="buttons buttoncategory"><button class="pullcategory" id="buttoniso" value="iso">ISO Kategorien</button><button class="pullcategory" id="buttoninspire" value="inspire">EU INSPIRE Kategorien</button><button class="pullcategory" id="buttonopendata" value="opendata">OpenData</button></div><div id="pie2" class="inner"></div></div>';
//internal lib javascript part
$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-1.4.2.min.js"></script>';
$html .= '<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-ui-1.8.1.custom.min.js"></script>';
//external javascript part
$html .= '<script type="text/javascript" src="../extensions/d3.v3.min.js"></script>';
$html .= '<script type="text/javascript" src="../extensions/d3pie.min.js"></script>';

//internal javascript part
$javascript = <<< JAVASCRIPT
 <script type="text/javascript">
	var pie = new d3pie("pie");
	var pie2 = new d3pie("pie2");
	var currentDepartment;
	d3.selectAll(".pulldata")
	.on("click", function(){
		if (pie !== undefined) {
			pie.destroy();
		}
		getStatisticsFromServer(this.value);
	});
	d3.selectAll(".pullcategory")
	.on("click", function(){
		$('#error').remove();
		if (pie2 !== undefined) {
			pie2.destroy();
		}
		getCategoriesFromServer(currentDepartment,this.value);
	});
	function getStatisticsFromServer(adminLevel) {
		$.getJSON('mod_showCatalogueStatistics_server.php?adminLevel='+adminLevel, null, function(data) { 
			if (data !== false) {
				var pie = new d3pie("pie", {
					size: {
						pieOuterRadius: "75%",
						canvasHeight: innerHeight * 0.9,
						canvasWidth: innerWidth * 0.9
					},
					data: {
						sortOrder: "value-asc",
						smallSegmentGrouping: {
							enabled: false,
							value: 2,
							valueType: "value",
							label: "Andere Organisationen",
							color: "#999999"
						},
						content: data
					},
					tooltips: {
    						enabled: true,
    						type: "caption"
  					},
					callbacks: {
						onClickSegment: function(data) {    
							$("#dialog").dialog({
								draggable: false,
								title: data.data.label,
      								height :innerHeight *0.95,
								width:innerWidth * 0.95,
      								modal: true,
      								buttons: {	
      								}
    							});
							$(".buttoncategory").css("display","block"); 
							getCategoriesFromServer(data.data.id,'iso');
							currentDepartment = data.data.id;
						}
					}
				});
			} else {
//		alert("Keine Daten für EU-Gebietsklassifikation  "+adminLevel+" gefunden!");
$("<div title='Hinweis'>Keine Daten für EU-Gebietsklassifikation  "+adminLevel+" gefunden!</div>").dialog({modal:true});
			}				
		});
	}

	function getCategoriesFromServer(registratingDepartments,categoryType) {
		if (pie2 !== undefined) {
			pie2.destroy();
		}
		$.getJSON('mod_showCatalogueStatistics_server.php?registratingDepartments='+registratingDepartments+'&categoryType='+categoryType, null, function(data) { 
			searchAll = "../../portal/nc/servicebereich/erweiterte-suche.html?cat=dienste&searchText=false&registratingDepartments="+currentDepartment;
			if (data !== false) {
				var pie2 = new d3pie("pie2", {
					size: {
						pieOuterRadius: "65%",
						canvasHeight: innerHeight * 0.80,
						canvasWidth: innerWidth * 0.80
					},
					data: {
						sortOrder: "value-asc",
						smallSegmentGrouping: {
							enabled: false,
							value: 2,
							valueType: "value",
							label: "Andere Kategorien",
							color: "#999999"
						},
						content: data
					},
					tooltips: {
    						enabled: true,
    						type: "caption"
  					},
					callbacks: {
						onClickSegment: function(data) {    
							//searchlink
							searchLink = "../../portal/nc/servicebereich/erweiterte-suche.html?cat=dienste&searchText=false&registratingDepartments="+currentDepartment+"&";
							switch (categoryType) {
								case "iso":
									searchLinkNew = searchLink+"isoCategories="+data.data.id;
								break;
								case "inspire":
									searchLinkNew = searchLink+"inspireThemes="+data.data.id;
								break;
								case "opendata":
 									if (data.data.id == "1") {
										searchLinkNew = searchLink+"restrictToOpenData=true";
									} else {
										//searchLinkNew = searchLink;
									}
								break;
								default:
									searchLinkNew = searchLink;
							}
							window.top.location.href = searchLinkNew;//alert(searchLinkNew);//(data.data.id,'iso');
							
						}
					}
				});
			} else {
				//alert("Keine weitere Kategorisierung vorhanden!");
				$("#dialog").append( "<p id=\"error\">Keine Kategorisierung von Datensätzen vorhanden!<br><a onclick=\"window.top.location.href = searchAll;\">Alle Datensätze anzeigen</a></p>" );
				if (pie2 !== undefined) {
					pie2.destroy();
				}
				
			}				
		});
	}
	$('#dialog').live("dialogclose", function(){
   		$('#error').remove();
		$(".buttoncategory").css("display","none"); 
	});
	$(".buttoncategory").css("display","none"); 
 </script>
JAVASCRIPT;
$html .= $javascript;
$html .= "</body></html>";
echo $html;
?>
