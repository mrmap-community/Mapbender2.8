<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_user.php";
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";
require_once dirname(__FILE__) . "/../classes/class_administration.php";
require_once dirname(__FILE__) . "/../classes/class_ogr.php";

$ajaxResponse = new AjaxResponse($_POST);

function abort ($message) {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die();
}

function DOMNodeListObjectValuesToArray($domNodeList) {
	$iterator = 0;
	$array = array();
	foreach ($domNodeList as $item) {
    		$array[$iterator] = $item->nodeValue; // this is a DOMNode instance
    		// you might want to have the textContent of them like this
    		$iterator++;
	}
	return $array;
}

//NOTE: independend
function extractPolygonArray($domXpath, $path) {
	$polygonalExtentExterior = array();
	if ($domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList')) {
		//read posList
		$exteriorRingPoints = $domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList');
		$exteriorRingPoints = DOMNodeListObjectValuesToArray($exteriorRingPoints);
		if (count($exteriorRingPoints) > 0) {
			//poslist is only space separated
			$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
			for ($i = 0; $i <= count($exteriorRingPointsArray)/2-1; $i++) {
				$polygonalExtentExterior[$i]['x'] = $exteriorRingPointsArray[2*$i];
				$polygonalExtentExterior[$i]['y'] = $exteriorRingPointsArray[(2*$i)+1];
			}
		}
	} else {
		//try to read coordinates
		$exteriorRingPoints = $domXpath->query($path.'/gml:Polygon/gml:exterior/gml:LinearRing/gml:coordinates');
		$exteriorRingPoints = DOMNodeListObjectValuesToArray($exteriorRingPoints);
		if (count($exteriorRingPoints) > 0) {
			//two coordinates of one point are comma separated
			//problematic= ", " or " ," have to be deleted before
			$exteriorRingPoints[0] = str_replace(', ',',',str_replace(' ,',',',$exteriorRingPoints[0]));
			$exteriorRingPointsArray = explode(' ',$exteriorRingPoints[0]);
			for ($i = 0; $i <= count($exteriorRingPointsArray)-1;$i++) {
				$coords = explode(",",$exteriorRingPointsArray[$i]);
				$polygonalExtentExterior[$i]['x'] = $coords[0];
				$polygonalExtentExterior[$i]['y'] = $coords[1];
			}
		}
	}
	return $polygonalExtentExterior;
}
//NOTE: independend
function gml2wkt($gml) {
	//function to create wkt from given gml multipolygon
	//DOM
	$polygonalExtentExterior = array();
	$gmlObject = new DOMDocument();
	libxml_use_internal_errors(true);
	try {
		$gmlObject->loadXML($gml);
		if ($gmlObject === false) {
			foreach(libxml_get_errors() as $error) {
        			$err = new mb_exception("mb_metadata_server.php:".$error->message);
    			}
			throw new Exception("mb_metadata_server.php:".'Cannot parse GML!');
			return false;
		}
	}
	catch (Exception $e) {
    		$err = new mb_exception("mb_metadata_server.php:".$e->getMessage());
		return false;
	}
	//if parsing was successful
	if ($gmlObject !== false) {
		//read crs from gml
		$xpath = new DOMXPath($gmlObject);
		$xpath->registerNamespace('gml','http://www.opengis.net/gml');
		$MultiSurface = $xpath->query('/gml:MultiSurface');
		if ($MultiSurface->length == 1) { //test for DOM!
			$crs = $xpath->query('/gml:MultiSurface/@srsName');
			$crsArray = DOMNodeListObjectValuesToArray($crs);
			$crsId = end(explode(":",$crsArray[0]));
			//count surfaceMembers
			$numberOfSurfaces = count(DOMNodeListObjectValuesToArray($xpath->query('/gml:MultiSurface/gml:surfaceMember')));
			for ($k = 0; $k < $numberOfSurfaces; $k++) {
				$polygonalExtentExterior[] = extractPolygonArray($xpath, '/gml:MultiSurface/gml:surfaceMember['. (string)($k + 1) .']');
			}
		} else {
			$polygonalExtentExterior[0] = extractPolygonArray($xpath, '/');
		}
		$crs = $xpath->query('/gml:Polygon/@srsName');
		$crsArray = DOMNodeListObjectValuesToArray($crs);
		$crsId = end(explode(":",$crsArray[0]));
		if (!isset($crsId) || $crsId =="" || $crsId == NULL) {
			//set default to lonlat wgs84
			$crsId = "4326";
		}
		$mbMetadata = new Iso19139();
		$wkt = $mbMetadata->createWktPolygonFromPointArray($polygonalExtentExterior);
		return $wkt;
	}
}
//routines to do the ajax server side things
switch ($ajaxResponse->getMethod()) {
    case "importGeometry":
        $filename = $ajaxResponse->getParameter("filename");
        $e = new mb_exception('plugins/mb_geometry_server.php: filename: ' . json_encode($filename));
        $metadataId = $ajaxResponse->getParameter("metadataId");
        $gml = file_get_contents($filename);
        $e = new mb_exception('plugins/mb_geometry_server.php: uploaded file content: ' . $gml);
        if (!$gml){
            abort(_mb("Reading file ".json_encode($filename)." failed!"));
        }
        $wktPolygon = gml2wkt($gml);
        if ($wktPolygon) {
            //insert polygon into database
            $sql = <<<SQL
UPDATE mb_metadata SET bounding_geom = $2 WHERE metadata_id = $1
SQL;
            $v = array($metadataId, $wktPolygon);
            //$e = new mb_exception($metadataId);
            $t = array('i','POLYGON');
            $res = db_prep_query($sql,$v,$t);
            if (!$res) {
                abort(_mb("Problem while storing geometry into database!"));
            } else {
                //build new preview url if possible and give it back in ajax response
                
                $ajaxResponse->setMessage("Stored successfully geometry into database!");
                $ajaxResponse->setSuccess(true);
            }
        } else {
            abort(_mb("Converting GML to WKT failed!"));
        }
        //parse gml and extract multipolygon to wkt representation
        //push multipolygon into database
        break;
    case "transformGeometry":
        //delete path from filename
        $filename = str_replace('../tmp/', '', $ajaxResponse->getParameter("filename"));
        //read file from tmpdir instead
        $tmpDir = TMPDIR;
        $filenameGeometry = $tmpDir."/".$filename;
        $ogr = new Ogr();
        //for ascii files
        /*
        if($h = fopen($filenameGeometry, "r")){
            $geometry = fread($h, filesize($filenameGeometry));
            fclose($h);
            $e = new mb_exception('plugins/mb_geometry_server.php: geometry: ' . $geometry );
        }*/
        //if file is zipped, check files inside
        $file_parts = pathinfo($filenameGeometry);
        switch ( $file_parts['extension'] ) {
            case "zip":
                $za = new ZipArchive();
                $za->open($filenameGeometry);
                for( $i = 0; $i < $za->numFiles; $i++ ){
                    $stat = $za->statIndex( $i );
                    //$e = new mb_exception('plugins/mb_geometry_server.php: file in zip: ' . basename( $stat['name'] ) );
                    $file_parts = pathinfo( basename( $stat['name'] ) );
                    if (!in_array(strtolower($file_parts['extension']), array('shx', 'shp', 'qix', 'cpg', 'dbf', 'prj', 'qmd'))) {
                        abort('Found unexpected file in zip archive: ' . $file_parts['extension']);
                    }
                    if (strtolower($file_parts['extension']) == 'shp') {
                        $filenameGeometry = $tmpDir."/".basename( $stat['name'] );
                    }
                } 
                //unzip files
                $za->extractTo(TMPDIR . "/");
                $za->close();
                $identifiedFormat = "ESRI Shapefile";
                break;
            default:
                $identifiedFormat = $ogr->getFormat($filenameGeometry);
                break;
        }
        $e = new mb_exception('plugins/mb_geometry_server.php: identified format: ' . $identifiedFormat );
        if (in_array($identifiedFormat, array('GeoJSON', 'GML', 'ESRI Shapefile'))) {
            //format supported
            //transform it to geojson in requested crs
            $geometry = $ogr->transform($filenameGeometry, $identifiedFormat, 'GeoJSON', $ajaxResponse->getParameter("targetCrs"));
            if ($geometry) {
                ///$e = new mb_exception('plugins/mb_geometry_server.php: transformed geometry: ' . $geometry );
                //delete temporal file
                unlink($filenameGeometry);
                $ajaxResponse->setSuccess(true);
                $ajaxResponse->setMessage(_mb("Geometry has been transformed to GeoJSON!"));
                $resultObj["geometry"] = $geometry;
                $ajaxResponse->setResult($resultObj);
                $ajaxResponse->setSuccess(true);
                $ajaxResponse->send();
                die();
            } else {
                $e = new mb_exception('plugins/mb_geometry_server.php: geometry could not be transformed by ogr!');
                unlink($filenameGeometry);
                abort(_mb("Geometry could not be transformed by ogr!"));
            }
        } else {
            $e = new mb_exception('plugins/mb_geometry_server.php: delete file, cause it is not supported!');
            unlink($filenameGeometry);
            abort(_mb("Geometry not identified - file was deleted instantly!"));
        }
        break;
    default:
        $ajaxResponse->setSuccess(false);
        $ajaxResponse->setMessage(_mb("An unknown error occured."));
        break;
}
$ajaxResponse->send();
