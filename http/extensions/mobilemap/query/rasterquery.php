<?php

//Basic configuration of mapserver client
require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");

$coord = split(',',$_GET["coord"]);

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

?>
