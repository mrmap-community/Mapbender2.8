<?php
	//http://localhost/mapbender_trunk/plugins/mb_downloadFeedServer.php
	require_once dirname(__FILE__) . "/../../core/globalSettings.php";
	require_once dirname(__FILE__) . "/../classes/class_user.php";
	require_once dirname(__FILE__) . "/../classes/class_connector.php";
	require_once(dirname(__FILE__)."/../classes/class_json.php");
	require_once(dirname(__FILE__)."/../classes/class_gml2.php");
	require_once(dirname(__FILE__)."/../classes/class_georss_geometry.php");
	require_once(dirname(__FILE__)."/../classes/class_Uuid.php");
	require_once(dirname(__FILE__)."/../classes/class_iso19139.php");


	
	if (file_exists ( dirname ( __FILE__ ) . "/../../conf/excludeFromAtomFeedClient.json" )) {
	    $configObject = json_decode ( file_get_contents ( "../../conf/excludeFromAtomFeedClient.json" ) );
	}
	if (isset ( $configObject ) && isset ( $configObject->urls )) {
	    $urlsBlacklist = $configObject->urls;
	} else {
	    $urlsBlacklist = false;
	}	
	
/*	$ajaxResponse = new AjaxResponse($_POST);

	function abort ($message) {
		global $ajaxResponse;
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage($message);
		$ajaxResponse->send();
		die;
	}

	function getServiceFeedObjectFromUrl () {
		//define default resultObj
		$resultObj = array(
			"original_layer_title" => "",
			"original_layer_abstract" => "",
			"original_layer_keyword" => ""
		);
		return $resultObj;
	}
*/
class geoRSSEntry extends Feature{
	public function parse($entry, $itemsToImport) {
		$tag = $currentSibling->nodeName;
		if(in_array($tag, $importItems)){
			$this->properties[$tag] = $currentSibling->nodeValue;
		}
		else{
			switch ($tag) {
			case "georss:polygon":
				$this->geometry = new geoRSSPolygon();
				$this->geometry->targetEPSG = $this->targetEPSG;
				$this->geometry->parsePolygon($currentSibling);
				break;
			default:
				break;
			}
		}						
	}
}

//for debugging purposes only
function logit($text){
	 if($h = fopen(LOG_DIR."/inspire_download_feed.log","a")){
				$content = $text .chr(13).chr(10);
				if(!fwrite($h,$content)){
					#exit;
				}
				fclose($h);
			}	 	
}

function logDlsUsage ($link, $s_title, $datasetid) {
	$logId = isLinkAlreadyInDB($link);
	if ($logId != false) {
		#$e = new mb_exception("logId: ".$logId);
		//update the load_count for this log entry 
		$e = new mb_notice("existing inspire_dls_log link found - load count will be incremented");
		$sql = <<<SQL
UPDATE inspire_dls_log SET log_count = log_count + 1 WHERE log_id = $1
SQL;
		$v = array(
			$logId
		);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		return true;
	} else {
		//create new record cause service has not been invoced so far
		$sql = <<<SQL
INSERT INTO inspire_dls_log (createdate, link, linktype, service_title, datasetid, log_count) VALUES (now(), $1, 'ATOM', $2, $3, 1)
SQL;
		$v = array(
			$link,
			$s_title,
			$datasetid
		);
		$t = array('s','s','s');
		$res = db_prep_query($sql,$v,$t);
		return true;
	}
}

function isLinkAlreadyInDB($link){
	$sql = <<<SQL
SELECT log_id FROM inspire_dls_log WHERE link = $1 AND link <> '' ORDER BY lastchanged DESC
SQL;
	$v = array(
		$link
	);
	$t = array('s');
	$res = db_prep_query($sql,$v,$t);
	while ($row = db_fetch_array($res)){
		$logId[] = $row['log_id'];	
	}
	if (count($logId) > 0) {
		return $logId[0];
	} else {	
		return false;
	}
}
	
function isFileIdentifierAlreadyInDB($uuid){
	$sql = <<<SQL
SELECT log_id, createdate FROM inspire_dls_log WHERE dls_service_uuid = $1 AND dls_service_uuid <> '' ORDER BY lastchanged DESC
SQL;
	if (!isset($uuid) || $uuid == '') {
		$e = new mb_exception("plugins/mb_downloadFeedServer.php:"."Empty or no fileIdentifier found in the inspire_dls_log table! No log entry will be updated");
		return false;
	}
	$v = array(
		$uuid
	);
	$t = array('s');
	$res = db_prep_query($sql,$v,$t);
	$logId = array();
	while ($row = db_fetch_array($res)){
		$logId[] = $row['log_id'];	
	}
	if (count($logId) > 0) {
		return $logId;
	} else {	
		return false;
	}
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

function DOMNodeListObjectValuesToHTML($domNodeList) {
	$iterator = 0;
	$array = array();
	//$dom = new DOMDocument;
	foreach ($domNodeList as $item) {
    		$array[$iterator] = $item->saveXML(); // this is a DOMNode instance
    		// you might want to have the textContent of them like this
    		$iterator++;
	}
	return $array;
}

function DOMNodeListObjectAttributes($domNodeList) {
	$attributes = array();
	foreach($domNodeList->attributes as $attribute_name => $attribute_node)
	{
  		/** @var  DOMNode    $attribute_node */
  		$attributes[$attribute_name] = $attribute_node->nodeValue;
	}
	return $attributes;
}

switch ($_REQUEST['method']) {
	case "getServiceFeedObjectFromUrl" :
		$serviceFeedUrl = htmlspecialchars_decode($_REQUEST['url']);//htmlspecialchars_decode is done to prohibit xss vulnerability of the client, which allows url as a get parameter
        //secure client by use of blacklist
        //TODO: give back clean json - so that the client can generate a usefull message!
		if ($urlsBlacklist != false) {
		    foreach ($urlsBlacklist as $urlPart) {
		        if (strpos($serviceFeedUrl, $urlPart) !== false) {
		            $e = new mb_exception("http/plugins/mb_downloadFeedServer.php:".'Found blacklist entry in downloadfeed url!');
		            return false;
		        }
		    }
		}	
		$logUrl = date("F j, Y, g:i a",time())." - ".$serviceFeedUrl;
		#$e = new mb_exception("inspire: ".$logUrl);
		logit($logUrl);
		//get feed from remote server
		$feedConnector = new connector($serviceFeedUrl);
		$feedConnector->set("timeOut", "5");
		$feedFile = $feedConnector->file;
		//parse content
		//$e = new mb_exception($feedFile);
		libxml_use_internal_errors(true);
		//DOM
		$feedXML = new DOMDocument();
		try {
			//$feedXML = simplexml_load_string($feedFile);
			//alternative dom parsing
			$feedXML->loadXML($feedFile);
			
			if ($feedXML === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("downloadFeedServer.php:".$error->message);
    				}
				throw new Exception("downloadFeedServer.php:".'Cannot parse Feed!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("downloadFeedServer.php:".$e->getMessage());
			return false;
		}
		//$e = new mb_exception($feedXML->file);
		if ($feedXML != false) {
			//$xml->loadXML($feedFile);
			$xpath = new DOMXPath($feedXML);
			$rootNamespace = $feedXML->lookupNamespaceUri($feedXML->namespaceURI);
			$xpath->registerNamespace('defaultns', $rootNamespace); 
			$xpath->registerNamespace('georss','http://www.georss.org/georss');
			$xpath->registerNamespace('inspire_dls','http://inspire.ec.europa.eu/schemas/inspire_dls/1.0');
			
			$title = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:title');
			//$content = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:content');
			$rights = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:rights');
			$feedRights = $xpath->query('/defaultns:feed/defaultns:rights');
			$summary = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:summary');
			$feedSummary = $xpath->query('/defaultns:feed/defaultns:subtitle');
			$date = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:date');
			$code = $xpath->query('/defaultns:feed/defaultns:entry/inspire_dls:spatial_dataset_identifier_code');
			$namespace = $xpath->query('/defaultns:feed/defaultns:entry/inspire_dls:spatial_dataset_identifier_namespace');
			$polygon = $xpath->query('/defaultns:feed/defaultns:entry/georss:polygon');
			$bbox = $xpath->query('/defaultns:feed/defaultns:entry/georss:box');
			$metadataLink = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:link[contains(@rel,\'describedby\')]/@href');
			//for hybrid implementation
			$capabilitiesLink = $xpath->query('/defaultns:feed/defaultns:link[contains(@rel,\'related\')]/@href');
			$datasetFeedLink = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:link[contains(@rel,\'alternate\') and contains(@type,\'application/atom+xml\')]/@href');
			//new feature collection
			$featureCollection = new FeatureCollection;
			//node values entry level			
			$titleArray = DOMNodeListObjectValuesToArray($title);
			$contentArray = DOMNodeListObjectValuesToArray($content);
			$rightsArray = DOMNodeListObjectValuesToArray($rights);
			$summaryArray = DOMNodeListObjectValuesToArray($summary);
			$dateArray = DOMNodeListObjectValuesToArray($date);
			$codeArray = DOMNodeListObjectValuesToArray($code);
			$namespaceArray = DOMNodeListObjectValuesToArray($namespace);
			$bboxArray = DOMNodeListObjectValuesToArray($bbox);
			$polygonArray = DOMNodeListObjectValuesToArray($polygon);
			//node values feed level
			$feedRightsArray = DOMNodeListObjectValuesToArray($feedRights);
			$feedSummaryArray = DOMNodeListObjectValuesToArray($feedSummary);
			//node attributes entry level				
			$metadataLinkArray = DOMNodeListObjectValuesToArray($metadataLink);
			$datasetFeedLinkArray = DOMNodeListObjectValuesToArray($datasetFeedLink);
			//node attributes feed level
			$capLinkArray = DOMNodeListObjectValuesToArray($capabilitiesLink);
			//for each titled entry element
			for ($i=0; $i<=(count($titleArray)-1); $i++) {			
				$feature = new Feature;
				$uuid = new Uuid;
				$feature->fid = $uuid;		
				if ($rightsArray[0] == "") {
					$rightsArray = $feedRightsArray[0];
				}
				if ($summaryArray[0] == "") {
					$summaryArray = (array)$feedSummaryArray[0];
				}
				$feature->properties["title"] = $titleArray[$i];
				//$feature->properties["content"] = $contentArray[0];
				$feature->properties["summary"] = $summaryArray[$i];
				$feature->properties["rights"] = $rightsArray[$i];
				$feature->properties["date"] = $dateArray[$i];
				$feature->properties["code"] = $codeArray[$i];
				$feature->properties["namespace"] = $namespaceArray[$i];
				$metadataObject = new Iso19139();
				$metadataObject->createFromUrl($metadataLinkArray[$i]);
				$feature->properties["fileIdentifier"] = (string)$metadataObject->fileIdentifier;
				$feature->properties["metadataLink"] = $metadataLinkArray[$i];
				$feature->properties["capabilitiesLink"] = $capLinkArray[0];
				$feature->properties["datasetFeedLink"] = $datasetFeedLinkArray[$i];
				//check if polygon is given			
				if (isset($polygonArray[$i]) && $polygonArray[$i] != '') {
					$feature->geometry = new geoRSSPolygon();
					$feature->geometry->parsePolygon($polygonArray[$i]);
				} else { //maybe bbox is given 
					if (isset($bboxArray[$i]) && $bboxArray[$i] != '') {
						$feature->geometry = new geoRSSBox();
						$feature->geometry->targetEPSG = '4326';
						$feature->geometry->parseBox($bboxArray[$i]);
					} else {
						//set dummy extent - maybe the one of the first feed
						$feature->geometry = null;
						
					}
					
				}
				//$feature->geometry->targetEPSG = "EPSG:4326";
				if (isset($feature->geometry) && $feature->geometry!==false) {
					$featureCollection->addFeature($feature);
					$e = new mb_notice("Feature added to collection!");
				}
			}
		}
		//log usage
		//logDlsUsage ($link, $s_title, $datasetid)
		logDlsUsage ($serviceFeedUrl, $feature->properties["title"], $feature->properties["namespace"]."#".$feature->properties["code"]);
		echo $featureCollection->toGeoJSON();
		break;
	case "getDatasetFeedObjectFromUrl" :
		$datasetFeedUrl = $_REQUEST['url'];
		//test url
		//get feed from remote server
		$feedConnector = new connector($datasetFeedUrl);
		$feedConnector->set("timeOut", "5");
		$feedFile = $feedConnector->file;
		//$mbMetadata = $this->createMapbenderMetadataFromXML($xml);
		//parse content
		//$e = new mb_exception($feedFile);
		//DOM
		$feedXML = new DOMDocument();
		libxml_use_internal_errors(true);
		try {
			//$feedXML = simplexml_load_string($feedFile);
			$feedXML->loadXML($feedFile);
			if ($feedXML === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("downloadFeedServer.php:".$error->message);
    				}
				throw new Exception("downloadFeedServer.php:".'Cannot parse Feed!');
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("downloadFeedServer.php:".$e->getMessage());
			return false;
		}
		//$e = new mb_exception($feedXML->file);
		if ($feedXML != false) {
			//$xml->loadXML($feedFile);
			$xpath = new DOMXPath($feedXML);
			$rootNamespace = $feedXML->lookupNamespaceUri($feedXML->namespaceURI);
			$xpath->registerNamespace('defaultns', $rootNamespace); 
			$xpath->registerNamespace('georss','http://www.georss.org/georss');
			$xpath->registerNamespace('inspire_dls','http://inspire.ec.europa.eu/schemas/inspire_dls/1.0');
			$title = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:title');
			$content = $xpath->query('/defaultns:feed/defaultns:entry/defaultns:content');
			$polygon = $xpath->query('/defaultns:feed/defaultns:entry/georss:polygon');
			$bbox = $xpath->query('/defaultns:feed/defaultns:entry/georss:box');

			$titleArray = DOMNodeListObjectValuesToArray($title);
			$contentArray = DOMNodeListObjectValuesToArray($content);
			$polygonArray = DOMNodeListObjectValuesToArray($polygon);
			$bboxArray = DOMNodeListObjectValuesToArray($bbox);
			//node attributes entry level				
			$metadataLinkArray = DOMNodeListObjectValuesToArray($metadataLink);
			$datasetFeedLinkArray = DOMNodeListObjectValuesToArray($datasetFeedLink);
			//new feature collection
			$featureCollection = new FeatureCollection;
			//$e = new mb_exception("dataset feeds entries: ".count($titleArray));
			for ($i=0; $i<=(count($titleArray)-1); $i++) {
				$feature = new Feature;
				$uuid = new Uuid;
				$feature->fid = $uuid;
				$links = $xpath->query('/defaultns:feed/defaultns:entry['.(string)($i + 1).']/defaultns:link[contains(@rel,\'alternate\') or contains(@rel,\'section\')]');
				$linksArray = DOMNodeListObjectValuesToArray($links);
				//example
    				/* <link rel="alternate" href="http://map1.naturschutz.rlp.de/service_lanis/mod_wfs/wfs_getmap.php?mapfile=naturschutzgebiet&amp;SERVICE=WFS&amp;REQUEST=GetFeature&amp;VERSION=1.1.0&amp;typeName=naturschutzgebiet&amp;maxFeatures=520&amp;srsName=EPSG:25832" type="application/gml+xml" hreflang="de" title="Naturschutzgebiete im CRS EPSG:25832 -  - Teil 1 von 1" bbox="48.9451980590054 6.1773996352971 50.9453010559041 8.48990058899502"/> */
				//read it and give it back as string (or xml)

				//need attributes href and bbox of this element! Test old system
				//$linksArray = DOMNodeListObjectValuesToHTML($links);
					
				//$e = new mb_exception("Count of found links: ".count($linksArray));
				
				//$e = new mb_exception("linksArray[0]: ".$linksArray[0]);
				//echo var_dump($links->item(0));
				//die();
				$feature->properties["title"] = $titleArray[$i];
				$feature->properties["content"] = $contentArray[$i];
				//$feature->properties["metadataLink"] = $mdLinkArray["href"];
				//$feature->properties["datasetFeedLink"] = $dsLinkArray["href"];
				//$feature->properties["entry"] = array();			
				if (isset($polygonArray[$i]) && $polygonArray[$i] != '') {
					$feature->geometry = new geoRSSPolygon();
					$feature->geometry->parsePolygon($polygonArray[$i]);
				} else { //maybe bbox is given 
					if (isset($bboxArray[$i]) && $bboxArray[$i] != '') {
						$feature->geometry = new geoRSSBox();
						$feature->geometry->targetEPSG = '4326';
						$feature->geometry->parseBox($bboxArray[$i]);
					} else {
						//set dummy extent - maybe the one of the first feed
						$feature->geometry = null;
					}
				}
				//extract all links
				for ($j=0; $j<=($links->length-1); $j++) {
					$feature->properties["link"][$j]->{"@attributes"} = DOMNodeListObjectAttributes($links->item($j));
				}
				//$link = $feedXML->xpath('/defaultns:feed/defaultns:entry/defaultns:link[contains(@rel,\'section\')]/@bbox');
				//check if polygon is given
				
				//$feature->geometry->targetEPSG = "EPSG:4326";
				//set geometry to null if geometry is not given!
				
				//if (isset($feature->geometry) && $feature->geometry!==false) {
				$featureCollection->addFeature($feature);
				$e = new mb_notice("Feature added to collection!");
				//}
				/*$titleArray = (array)$title[$i];
				$linkArray = (array)$link[$i];
				$linkArray = $linkArray["@attributes"];
				$bbox = $linkArray["bbox"];
				//$e = new mb_exception($bbox);
				$feature->properties["title"] = $titleArray[0];
				//$feature->properties["metadataLink"] = $mdLinkArray["href"];
				//$feature->properties["datasetFeedLink"] = $dsLinkArray["href"];
				$feature->geometry = new geoRSSPolygon();
				$feature->geometry->parsePolygon($bbox);
				//$feature->geometry->targetEPSG = "EPSG:4326";
				if (isset($feature->geometry) && $feature->geometry!==false) {
					$featureCollection->addFeature($feature);
					$e = new mb_notice("Feature added to collection!");
				}*/
			}
		}
		
		echo $featureCollection->toGeoJSON();
		break;
	default:
		echo json_encode("Kein Treffer!");
		break;
}

?>
