<?php

//Basic configuration of mapserver client

require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");
require_once(dirname(__FILE__)."/../../../classes/class_connector.php");

$featureInfoRequestPart =  '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SERVICE=WMS&LAYERS='.$rquerylayer;
$featureInfoRequestPart .= '&QUERY_LAYERS='.$rquerylayer.'&WIDTH=101&HEIGHT=101&SRS=EPSG:'.$ggepsg;

//calculate BBOX from position
$coord = explode(',',$_GET["coord"]);
$bbox = (string)((double)$coord[0] - 50.0) .",".(string)((double)$coord[1] - 50.0) .",".(string)((double)$coord[0] + 50.0) .",".(string)((double)$coord[1] + 50.0); 


$featureInfoRequestPart .= '&BBOX='.$bbox.'&STYLES=&FORMAT=image/png';
$featureInfoRequestPart .= '&INFO_FORMAT=application/vnd.ogc.gml&EXCEPTIONS=application/vnd.ogc.se_inimage&X=51&Y=51&FEATURE_COUNT=1&';
$url = $dhmWmsFeatureInfoUrl.$featureInfoRequestPart;

//Request ausführen
// Open the Curl session
$featureInfoConnector = new connector($url);
//header("Content-Type: text/plain");
//header("Content-Type: application/json");

//Datenausgabe
$gml = $featureInfoConnector->file;

//Ergebnis parsen
try {
	//$xml = str_replace('xlink:href', 'xlinkhref', $xml);
	//http://forums.devshed.com/php-development-5/simplexml-namespace-attributes-problem-452278.html
	//http://www.leftontheweb.com/message/A_small_SimpleXML_gotcha_with_namespaces
	$gmlObject = new SimpleXMLElement($gml);

	if ($gmlObject === false) {
		foreach(libxml_get_errors() as $error) {
        		$e = new mb_exception($error->message);
    		}
		throw new Exception('Cannot parse GML from featureInfo in mobile Client!');
	}
}
catch (Exception $e) {
    	$e = new mb_exception($e->getMessage());
}	

if ($gmlObject !== false) {
	//read all relevant information an put them into the mapbender wfs object
	//xmlns="http://www.opengis.net/wfs"
	//Setup default namespace

	$gmlObject->registerXPathNamespace("gml", "http://www.opengis.net/gml");
	$gmlObject->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
	$gmlObject->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
	//$gmlObject->registerXPathNamespace("default", "http://www.opengis.net/wfs");
	//some debug
	//$e = new mb_notice("XML string from memory: ".$wfs11Cap->asXML());
	$x = $gmlObject->xpath('/msGMLOutput/mydhm_layer/mydhm_feature/value_0');
	$hoehe = $x[0];
	echo "Höhe: ~".$hoehe." [m]";
} else {
	echo "Kein Höhe gefunden!";
}
//HTML generieren

//Rückgabe

/*
//Mapfile laden
$map= ms_newMapObj($mapfilepath.'/dhm.map');

//Punkt erzeugen
$qPoint = ms_newPointObj();
$qPoint->setXY($coord[0]*1,$coord[1]*1);

//Layer festlegen
$layer = $map->getLayerByName($rquerylayer);

//Punktabfrage (Single)
@$datQuery = $layer->queryByPoint($qPoint, MS_SINGLE, 1);
 // UMRECHNUNG  Eingabe koordinatensystem -> WGS84
$projInObj = ms_newprojectionobj("init=epsg:$ggepsg");
$projOutObj = ms_newprojectionobj("init=epsg:4326");
$qPoint->project($projInObj, $projOutObj);
$WGS_X = round($qPoint->x,6);
$WGS_Y = round($qPoint->y,6);
print '<div id="dhmqueryId" >';
print ('<table border="0" cellspacing="0" cellpadding="1" class="normal">');

//Wenn Query erfolgreich
     if ($datQuery == MS_SUCCESS) {
         for ($j=0; $j<$layer->getNumResults(); $j++) {
            $result = $layer->getResult($j);			
			//Versionsüberprüfung	
			if (ms_GetVersionInt() < 50600){
				$layer->open(); 
			}
			
			//Versionsüberprüfung	
			if (ms_GetVersionInt() < 50600){
			$shpobj = $layer->getShape($result->tileindex,$result->shapeindex);
			}
			else{
			$shpobj = $layer->resultsGetShape($result->shapeindex,$result->tileindex);
			}
			
			$attr = $shpobj->values;

			//echo round($attr["value_0"],2);
			$hoehe = round($attr["value_0"],1);
			if ($hoehe <0){
			  print '<tr ><td colspan="3" ><strong>'.$maplang['rasterquery_nodata'].'</strong></td></tr>';
			}
			else{
			print '<tr><td>'.$maplang['rasterquery_h'].'</td><td><span class="hilite">'.$hoehe.'</span></td><td>m NN</td></tr>';
			}
			$shpobj->free(); 
			
			//Versionsüberprüfung		
			if (ms_GetVersionInt() < 50600){
				$layer->close();
			}
         }		
     }
	 else{
	    print '<tr ><td colspan="3" ><strong>'.$maplang['rasterquery_noh'].'</strong></td></tr>';
		//echo "Kein Wert verfügbar!";
	} 

print ('<tr bgcolor="#E6E6E6"><td colspan="3" >'.$maplang['rasterquery_gps'].'</td></tr>
  <tr>
    <td>Lat:</td>
    <td colspan="2">'.$WGS_Y.'</td>
  </tr>
  <tr>
    <td>Lon:</td>
    <td colspan="2">'.$WGS_X.'</td>
  </tr>
  <tr bgcolor="#E6E6E6">
    <td colspan="3" >'.$maplang['rasterquery_xy'].'</td>
  </tr>
  <tr>
    <td>X</td>
    <td colspan="2">'.round($coord[0],0).'</td>
  </tr>
  <tr>
    <td>Y</td>
    <td colspan="2">'.round($coord[1],0).'</td>
  </tr>
   <tr>
    <td colspan="3" align="left"><a href="javascript:void(0);"  onClick="javascript:zoompoint('.round($coord[0],0).','.round($coord[1],0).');" ><img src="'.$applicationurl.'/img/ico_zoomin.png"  border="0" > '.$maplang['geocode_result4'].'</a></td>
  </tr>
  <tr>
    <td colspan="3" align="right">');

print('	</td></tr></table></div>');
*/
?>
