<?php

/**
 * @version   Changed: ### 2015-02-23 14:00:42 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */

//import classes
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
//require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_Uuid.php");
require_once(dirname(__FILE__)."/../classes/class_kml_ows.php");
/**
 * publish the choosed data
 */
$admin = new administration();
//get data from session and request
$user_id = Mapbender::session()->get("mb_user_id");

//validate outputFormat
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	//validate to de, en, fr
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'kml' or $testMatch == 'gpx' or $testMatch == 'geojson')){ 
		echo 'Parameter <b>outputFormat</b> is not valid (kml, gpx, geojson).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}
//validate wmc_id
if (isset($_REQUEST["wmc_id"]) & $_REQUEST["wmc_id"] != "") {
	//validate to csv integer list
	$testMatch = $_REQUEST["wmc_id"];
	if ($testMatch == "current") {
	} else { 
		$pattern = '/^[0-9_]*$/';
		if (!preg_match($pattern,$testMatch)){ 
			echo 'Parameter <b>wmc_id</b> is not valid - no csv integer list!.<br/>'; 
			die(); 		
		}
	}
	$wmc_id = $testMatch;
	$wmc_serial_id = $wmc_id;
	$testMatch = NULL;
} else {
	echo "Mandatory parameter <b>wmc_id</b> is not set or empty!";
	die();
}

//$e = new mb_exception("php/mod_GetPublishedData.php: outputFormat: ".$outputFormat);
//create a WMC object from a WMC in the database
//$e = new mb_exception("php/mod_GetPublishedData.php: wmc_id: ".$wmc_id);
if ($wmc_id !== "current") {
	$xml = wmc::getDocumentWithPublicData($wmc_serial_id);
	$myWmc = new wmc();
	$myWmc->createFromXml($xml);
} else {
	//read wmc from session if available and fill the needed fields from wmc object
	$wmcDocSession = false;
	//check if wmc filename is in session - TODO only if should be loaded from session not else! (Module loadWMC)
	if(Mapbender::session()->get("mb_wmc")) {
   	    $wmc_filename = Mapbender::session()->get("mb_wmc");
    	    //$time_start = microtime();
    	    //load it from whereever it has been stored
    	    $wmcDocSession = $admin->getFromStorage($wmc_filename, TMP_WMC_SAVE_STORAGE);
	    $myWmc = new wmc();
	    $myWmc->createFromXml($wmcDocSession);
	} else {
	    $e = new mb_exception("php/mod_GetPublishedData.php: no wmc found in session!");
	}
}
//  Decode from JSON to array
foreach ($myWmc->generalExtensionArray as $key => &$value) {
    $value = json_decode($value, true);
}
// create and numerically indexed array
$kmls = array_values($myWmc->generalExtensionArray["KMLS"]);

$fileUuid = new Uuid();
//create the fileName
$file = "myDataCollection-".$fileUuid.".".$outputFormat;

if (isset($kmls[0]["data"]['@context'])) {
    $file = rawurldecode($kmls[0]["data"]['@context']['title'])."-".$fileUuid.'.'.$outputFormat;
}

if (sizeof($kmls) > 1) {
    // create a file for each featureCollection and push them in a zip
    if ($outputFormat == 'kml') {
	$geoJson = array(); 
	$fileCounter = 1;
	foreach ($kmls as $key => $value) {
 		 $geoJson[] = $kmls[$fileCounter-1]["data"];
		 $fileCounter +=1;
	}
	$dataFile = createFile($outputFormat, $geoJson, $fileUuid, sizeof($kmls));        
	$filename = "myDataCollection-".$fileUuid.".kml";
        $temp_kml = TMPDIR.$filename;
        // write the file
        file_put_contents($temp_kml, $dataFile);
        // set the headers to return a zip to the client
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($temp_kml));
    	header("Access-Control-Allow-Origin: *");
	readfile($temp_kml);
 	exec(escapeshellcmd('rm '.$temp_kml));
	die();
	/*header('Content-Type: text/xml');
	$e = new mb_exception($dataFile);
	echo (string)$dataFile;*/
    } else {
        $zip = new ZipArchive;
        $zipName = TMPDIR.'/myDataCollection-'.$fileUuid.'.zip';
        $fileName = "myDataCollection-'.$fileUuid.'.zip";
        if ($zip->open($zipName, ZIPARCHIVE::CREATE) === true) {
            // counting filenames
            $fileCounter = 1;
            // loop over every featureCollection
            foreach ($kmls as $key => $value) {
                $geoJson = $kmls[$fileCounter-1]["data"];
                $dataFile = createFile($outputFormat, $geoJson, $fileUuid, sizeof($kmls));
                $zip->addFromString('myFeatureCollection_'.$fileCounter.'.'.$outputFormat, $dataFile);
                // increment the counter
                $fileCounter +=1;
            }
            $zip->close();
        }
        // set the headers to return a zip to the client
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($zipName));
        readfile($zipName);
        //remove zip from harddisk
        exec(escapeshellcmd('rm '.TMPDIR.'/myDataCollection-'.$fileUuid.'.zip'));
        die;
    }
} else {
    //set headers to force the download for a single file
    header("Content-Disposition: attachment; filename=" . urlencode($file));
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Access-Control-Allow-Origin: *");
    //parse the geojson from the array
    $geoJson = $kmls[0]["data"];
    $dataFile = createFile($outputFormat, $geoJson, $fileUuid, 1);
    echo $dataFile;
}

/**
 * converts a kml-file into a gpx-file
 * @param  string $u the file location
 * @return the gpx-file
 */
function kml_to_gpx($u)
{
    $u_parts = pathinfo($u); //array of url parts
    $u_ext = strtoupper($u_parts['extension']);
    if ($u_ext== "KML") {
        $dom_kml = new DOMDocument();
        $dom_kml->load($u);

        $dom_gpx = new DOMDocument('1.0', 'UTF-8');
        $dom_gpx->formatOutput = true;

        //root node
        $gpx = $dom_gpx->createElement('gpx');
        $gpx = $dom_gpx->appendChild($gpx);

        $gpx_version = $dom_gpx->createAttribute('version');
        $gpx->appendChild($gpx_version);
        $gpx_version_text = $dom_gpx->createTextNode('1.0');
        $gpx_version->appendChild($gpx_version_text);

        $gpx_creator = $dom_gpx->createAttribute('creator');
        $gpx->appendChild($gpx_creator);
        $gpx_creator_text = $dom_gpx->createTextNode('http://thydzik.com');
        $gpx_creator->appendChild($gpx_creator_text);

        $gpx_xmlns_xsi = $dom_gpx->createAttribute('xmlns:xsi');
        $gpx->appendChild($gpx_xmlns_xsi);
        $gpx_xmlns_xsi_text = $dom_gpx->createTextNode('http://www.w3.org/2001/XMLSchema-instance');
        $gpx_xmlns_xsi->appendChild($gpx_xmlns_xsi_text);

        $gpx_xmlns = $dom_gpx->createAttribute('xmlns');
        $gpx->appendChild($gpx_xmlns);
        $gpx_xmlns_text = $dom_gpx->createTextNode('http://www.topografix.com/GPX/1/0');
        $gpx_xmlns->appendChild($gpx_xmlns_text);

        $gpx_xsi_schemaLocation = $dom_gpx->createAttribute('xsi:schemaLocation');
        $gpx->appendChild($gpx_xsi_schemaLocation);
        $gpx_xsi_schemaLocation_text = $dom_gpx->createTextNode('http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd');
        $gpx_xsi_schemaLocation->appendChild($gpx_xsi_schemaLocation_text);

        $gpx_url = $dom_gpx->createElement('url');
        $gpx_url = $gpx->appendChild($gpx_url);
        $gpx_url_text = $dom_gpx->createTextNode($u_parts['dirname']);
        $gpx_url->appendChild($gpx_url_text);

        $gpx_time = $dom_gpx->createElement('time');
        $gpx_time = $gpx->appendChild($gpx_time);
        $gpx_time_text = $dom_gpx->createTextNode(utcdate());
        $gpx_time->appendChild($gpx_time_text);

        // placemarks
        $names = array();
        foreach ($dom_kml->getElementsByTagName('Placemark') as $placemark) {
            // var_dump('sdafsdaf');
            foreach ($placemark->getElementsByTagName('name') as $name) {
                $name  = $name->nodeValue;
                //check if the key exists
                if (array_key_exists($name, $names)) {
                    //increment the value
                    ++$names[$name];
                    $name = $name." ({$names[$name]})";
                } else {
                    $names[$name] = 0;
                }
            }
            //description
            foreach ($placemark->getElementsByTagName('description') as $description) {
                $description  = $description->nodeValue;
            }
            foreach ($placemark->getElementsByTagName('Point') as $point) {
                foreach ($point->getElementsByTagName('coordinates') as $coordinates) {
                    //add the marker
                    $coordinate = $coordinates->nodeValue;
                    $coordinate = str_replace(" ", "", $coordinate);//trim white space
                    $latlng = explode(",", $coordinate);

                    if (($lat = $latlng[1]) && ($lng = $latlng[0])) {
                        $gpx_wpt = $dom_gpx->createElement('wpt');
                        $gpx_wpt = $gpx->appendChild($gpx_wpt);

                        $gpx_wpt_lat = $dom_gpx->createAttribute('lat');
                        $gpx_wpt->appendChild($gpx_wpt_lat);
                        $gpx_wpt_lat_text = $dom_gpx->createTextNode($lat);
                        $gpx_wpt_lat->appendChild($gpx_wpt_lat_text);

                        $gpx_wpt_lon = $dom_gpx->createAttribute('lon');
                        $gpx_wpt->appendChild($gpx_wpt_lon);
                        $gpx_wpt_lon_text = $dom_gpx->createTextNode($lng);
                        $gpx_wpt_lon->appendChild($gpx_wpt_lon_text);

                        $gpx_time = $dom_gpx->createElement('time');
                        $gpx_time = $gpx_wpt->appendChild($gpx_time);
                        $gpx_time_text = $dom_gpx->createTextNode(utcdate());
                        $gpx_time->appendChild($gpx_time_text);

                        $gpx_name = $dom_gpx->createElement('name');
                        $gpx_name = $gpx_wpt->appendChild($gpx_name);
                        $gpx_name_text = $dom_gpx->createTextNode($name);
                        $gpx_name->appendChild($gpx_name_text);

                        $gpx_desc = $dom_gpx->createElement('desc');
                        $gpx_desc = $gpx_wpt->appendChild($gpx_desc);
                        $gpx_desc_text = $dom_gpx->createTextNode($description);
                        $gpx_desc->appendChild($gpx_desc_text);

                        //$gpx_url = $dom_gpx->createElement('url');
                        //$gpx_url = $gpx_wpt->appendChild($gpx_url);
                        //$gpx_url_text = $dom_gpx->createTextNode($ref);
                        //$gpx_url->appendChild($gpx_url_text);

                        $gpx_sym = $dom_gpx->createElement('sym');
                        $gpx_sym = $gpx_wpt->appendChild($gpx_sym);
                        $gpx_sym_text = $dom_gpx->createTextNode('Waypoint');
                        $gpx_sym->appendChild($gpx_sym_text);
                    }
                }
            }
            foreach ($placemark->getElementsByTagName('LineString') as $lineString) {
                foreach ($lineString->getElementsByTagName('coordinates') as $coordinates) {
                    //add the new track
                    $gpx_trk = $dom_gpx->createElement('trk');
                    $gpx_trk = $gpx->appendChild($gpx_trk);

                    $gpx_name = $dom_gpx->createElement('name');
                    $gpx_name = $gpx_trk->appendChild($gpx_name);
                    $gpx_name_text = $dom_gpx->createTextNode($name);
                    $gpx_name->appendChild($gpx_name_text);

                    $gpx_trkseg = $dom_gpx->createElement('trkseg');
                    $gpx_trkseg = $gpx_trk->appendChild($gpx_trkseg);

                    $coordinates = $coordinates->nodeValue;
                    $coordinates = preg_split("/[\s\r\n]+/", $coordinates); //split the coords by new line
                    foreach ($coordinates as $coordinate) {
                        $latlng = explode(",", $coordinate);

                        if (($lat = $latlng[1]) && ($lng = $latlng[0])) {
                            $gpx_trkpt = $dom_gpx->createElement('trkpt');
                            $gpx_trkpt = $gpx_trkseg->appendChild($gpx_trkpt);

                            $gpx_trkpt_lat = $dom_gpx->createAttribute('lat');
                            $gpx_trkpt->appendChild($gpx_trkpt_lat);
                            $gpx_trkpt_lat_text = $dom_gpx->createTextNode($lat);
                            $gpx_trkpt_lat->appendChild($gpx_trkpt_lat_text);

                            $gpx_trkpt_lon = $dom_gpx->createAttribute('lon');
                            $gpx_trkpt->appendChild($gpx_trkpt_lon);
                            $gpx_trkpt_lon_text = $dom_gpx->createTextNode($lng);
                            $gpx_trkpt_lon->appendChild($gpx_trkpt_lon_text);

                            $gpx_time = $dom_gpx->createElement('time');
                            $gpx_time = $gpx_trkpt->appendChild($gpx_time);
                            $gpx_time_text = $dom_gpx->createTextNode(utcdate());
                            $gpx_time->appendChild($gpx_time_text);
                        }
                    }
                }
            }
            foreach ($placemark->getElementsByTagName('Polygon') as $lineString) {
                foreach ($lineString->getElementsByTagName('coordinates') as $coordinates) {
                    //add the new track
                    $gpx_trk = $dom_gpx->createElement('trk');
                    $gpx_trk = $gpx->appendChild($gpx_trk);

                    $gpx_name = $dom_gpx->createElement('name');
                    $gpx_name = $gpx_trk->appendChild($gpx_name);
                    $gpx_name_text = $dom_gpx->createTextNode($name);
                    $gpx_name->appendChild($gpx_name_text);

                    $gpx_trkseg = $dom_gpx->createElement('trkseg');
                    $gpx_trkseg = $gpx_trk->appendChild($gpx_trkseg);

                    $coordinates = $coordinates->nodeValue;
                    $coordinates = preg_split("/[\s\r\n]+/", $coordinates); //split the coords by new line
                    foreach ($coordinates as $coordinate) {
                        $latlng = explode(",", $coordinate);

                        if (($lat = $latlng[1]) && ($lng = $latlng[0])) {
                            $gpx_trkpt = $dom_gpx->createElement('trkpt');
                            $gpx_trkpt = $gpx_trkseg->appendChild($gpx_trkpt);

                            $gpx_trkpt_lat = $dom_gpx->createAttribute('lat');
                            $gpx_trkpt->appendChild($gpx_trkpt_lat);
                            $gpx_trkpt_lat_text = $dom_gpx->createTextNode($lat);
                            $gpx_trkpt_lat->appendChild($gpx_trkpt_lat_text);

                            $gpx_trkpt_lon = $dom_gpx->createAttribute('lon');
                            $gpx_trkpt->appendChild($gpx_trkpt_lon);
                            $gpx_trkpt_lon_text = $dom_gpx->createTextNode($lng);
                            $gpx_trkpt_lon->appendChild($gpx_trkpt_lon_text);

                            $gpx_time = $dom_gpx->createElement('time');
                            $gpx_time = $gpx_trkpt->appendChild($gpx_time);
                            $gpx_time_text = $dom_gpx->createTextNode(utcdate());
                            $gpx_time->appendChild($gpx_time_text);
                        }
                    }
                }
            }
        }
        header("Content-Type: text/xml");
        return $dom_gpx->saveXML();
    }
}

/**
 * create a utcdate
 * @return utcdate objekt]
 */
function utcdate()
{
        return gmdate("Y-m-d\Th:i:s\Z");
}

function createFile($outputFormat, $geoJson, $fileUuid, $numberOfKmls)
{
    // handle the different outputformat
    if ($outputFormat == 'geojson') {
        // create the json-file
        $geoJsonFile = TMPDIR.'/myDataCollection-'.$fileUuid.'.geojson';
        // put the contents to the created file
        file_put_contents($geoJsonFile, json_encode($geoJson));
        // create a string from the created file
        $fileString = file_get_contents(TMPDIR.'/myDataCollection-'.$fileUuid.'.geojson');
        // return the string
        return $fileString;
        die;

    } elseif ($outputFormat == 'gpx') {
        //convert geojson to kml
        //create the geojson-file temporary
        $temp_geojson = TMPDIR.'/myDataCollection-'.$fileUuid.'.geojson';
        // write the file
        file_put_contents($temp_geojson, json_encode($geoJson));
        //transform the geojson to kml
        $unique = TMPDIR.'/myDataCollection-'.$fileUuid;
        $fGeojson = $unique.".geojson";
        $fKml = $unique.".kml";
	//TODO: Define this in mapbender.conf or other configuration file!
        $pathOgr = '/usr/bin/ogr2ogr';
        // execute ogr2ogr
        $exec = $pathOgr.' -f KML '.$fKml.' '.$fGeojson;
        exec(escapeshellcmd($exec));
        //enter location of KML file here
        $kml = TMPDIR."/myDataCollection-".$fileUuid.".kml";
        // create gpx from kml
        return kml_to_gpx($kml);
        die;
    } elseif ($outputFormat == 'kml') {
	//convert geojson to kml via classes
	//initialize new kml objekt
	//$e = new mb_exception("test kml export");
	$mergedKml = new Kml();
	if ($numberOfKmls > 1) {
		$kmlArray = array();
		foreach ($geoJson as $collection) {
			$kmlObj = new Kml();
			//$e = new mb_exception(json_encode($collection));
			$kmlObj->parseGeoJSON(json_encode($collection));
			//$e = new mb_exception($kml->__toString());
			$kmlArray[] = $kmlObj->__toString();
		}
		return $mergedKml->mergeKMLDocuments($kmlArray);
	} else {
		//$kmlArray = array();
		$kmlObj = new Kml();
		$kmlObj->parseGeoJSON(json_encode($geoJson));
        	// create the json-file
        	$kmlFile = TMPDIR.'/myDataCollection-'.$fileUuid.'.kml';
		$kmlString = $kmlObj->__toString();
        	// put the contents to the created file
        	file_put_contents($kmlFile, $kmlString);
        	// return the string
		$fileString = file_get_contents(TMPDIR.'/myDataCollection-'.$fileUuid.'.kml');
		return $fileString;
		die;
	}
	die();
        //convert geojson to kml
        //create the geojson-file temporary
        $temp_geojson = TMPDIR.'/myDataCollection-'.$fileUuid.'.geojson';
        // write the file
        file_put_contents($temp_geojson, json_encode($geoJson));
        //transform the file to kml
        $unique = TMPDIR.'/myDataCollection-'.$fileUuid;
        $fGeojson = $unique.".geojson";
        $fKml = $unique.".kml";
	//TODO: Define this in mapbender.conf or other configuration file!
        $pathOgr = '/usr/bin/ogr2ogr';
        //execute ogr2ogr to transfrom json to kml
        $exec = $pathOgr.' -f KML '.$fKml.' '.$fGeojson;
        exec(escapeshellcmd($exec));
        // create string from kml-file
        $fileString = file_get_contents(TMPDIR.'/myDataCollection-'.$fileUuid.'.kml');
        //return the string
        return $fileString;
        die;
	
    }
}
