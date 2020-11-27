<?php
# $Id: class_layer_monitor.php 791 2007-08-10 10:36:04Z baudson $
# http://www.mapbender.org/index.php/
# Copyright (C) 2002 CCGIS 
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

//require_once(dirname(__FILE__)."/../../conf/mapbender.conf");

require_once(dirname(__FILE__)."/../http/classes/class_connector.php");
require_once(dirname(__FILE__)."/../http/classes/class_mb_exception.php");
require_once(dirname(__FILE__)."/../http/extensions/DifferenceEngine.php");
require_once(dirname(__FILE__) . "/../http/classes/class_administration.php");
require_once(dirname(__FILE__) . "/../http/classes/class_universal_wfs_factory.php");

class Monitor {
	/**
	 *  1 = reachable and in sync with db
	 *  0 = reachable and out of sync with db
	 * -1 = unreachable
	 * -2 = monitoring in progress
	 * 
	 */
	var $result = -1;
	/**
	 * 1  = the get map request DEFINITELY returns a valid map image
	 * 0  = the WMS doesn't support XML error format. Who knows if the image is really a map?
	 * -1 = the get map request doesn't return an image
	 */
	var $returnsImage;
	var $comment = "";
	var $updated = "0";
	var $supportsXMLException = false;
	var $timestamp;
	var $timestamp_cap_begin;
	var $timestamp_cap_end;
	var $capabilitiesURL;
	var $mapURL;
	var $remoteXML;
	var $localXML;
	var $capabilitiesDiff;
	var $tmpDir = null;        
	var $serviceType; //WMS|WFS
	var $serviceId;
	var $feature_content;
	
	function __construct($reportFile, $autoUpdate, $tmpDir, $serviceType="WMS") {
		$this->tmpDir = $tmpDir;
		$this->serviceType = $serviceType;
		$this->reportFile = $tmpDir.$reportFile;
		//$this->reportFile = $reportFile;
		switch ($this->serviceType) {
			case "WMS":
				$this->serviceId = $this->getTagOutOfXML($this->reportFile,'wms_id',$this->serviceType);
				break;
			case "WFS":
				$this->serviceId = $this->getTagOutOfXML($this->reportFile,'wfs_id',$this->serviceType);
				break;
		}
		$this->uploadId = $this->getTagOutOfXML($this->reportFile,'upload_id',$this->serviceType);
		$this->autoUpdate = $autoUpdate;
		$e=new mb_notice("Monitor Report File: ".$this->reportFile);
		$e=new mb_notice("Service ID: ".$this->serviceId);
		$this->capabilitiesURL = urldecode($this->getTagOutOfXML($this->reportFile,'getcapurl',$this->serviceType));//read out from xml
		$e=new mb_notice("GetCapURL: ".$this->capabilitiesURL);
		set_time_limit(TIME_LIMIT);
		$this->timestamp = microtime(TRUE);
		//get authentication info for service
		$admin = new administration();
		switch ($this->serviceType) {
			case "WMS":
				$auth = $admin->getAuthInfoOfWMS($this->serviceId);
				break;
			case "WFS":
				$auth = $admin->getAuthInfoOfWFS($this->serviceId);
				break;
		}
		if ($auth['auth_type']==''){
			unset($auth);
		}
		if ($this->capabilitiesURL) {
			$this->timestamp_cap_begin=microtime(TRUE);//ok
			$capObject = new connector();
			if (defined("CAP_MONITORING_TIMEOUT") && CAP_MONITORING_TIMEOUT !== "") {
				$capObject->set("timeOut", CAP_MONITORING_TIMEOUT);
			}
			if (isset($auth)) {
				$capObject->load($this->capabilitiesURL,$auth);
			} else {
				$capObject->load($this->capabilitiesURL);
			}
			$this->remoteXML = $capObject->file;
			//encode all into utf-8 to compare them - this is done in too when storing the caps into the database after parsing the caps
			$this->remoteXML = $admin->char_encode($this->remoteXML);
			$this->timestamp_cap_end=microtime(TRUE);
			//read local copy out of xml
			$this->localXML = urldecode($this->getTagOutOfXML($this->reportFile,'getcapdoclocal',$this->serviceType));
			// service unreachable
			if (!$this->remoteXML) {
				$this->result = -1;
				$this->comment = "Connection failed.";
			}
			/*
			 * result available;
			 * no local copy of capabilities file,
			 * so it has to be updated anyway
			 */
			elseif (!$this->localXML) {
				$this->result = 0;
			}
			/*
			 * service available;
			 * check if local copy is different
			 * to remote capabilties document
			 */
			else {
				//First do a simple check if <WMT_MS_Capabilities version="1.1 is part of the remote Cap Dokument
				/*
				 * compare to local capabilities document
				 */
				// capabilities files match
				if ($this->localXML == $this->remoteXML) {
					$this->result = 1;
					$this->comment = "Service is stable.";
					//$e=new mb_exception("Compare ok - Docs ident");
				}
				// capabilities files don't match
				else {
					//check i a capabilities document was send, if not give an error
					$searchStringWms  = 'WMT_MS_Capabilities';
					$searchStringWfs  = 'WFS_Capabilities';
					$posWms = strpos($this->remoteXML, $searchStringWms);
					$posWfs = strpos($this->remoteXML, $searchStringWfs);
					if ($posWms === false && $posWfs === false) {
    						$this->result = -1;
						$this->comment = "Invalid getCapabilities request/document or service exception.";
					}
					else {
						$this->result = 0;
						$this->comment = "Service is not up to date.";
						$localXMLArray = explode("\n", $this->localXML);
						$remoteXMLArray = explode("\n", $this->remoteXML);
						$this->capabilitiesDiff = $this->outputDiffHtml($localXMLArray,$remoteXMLArray);
					}
				}
			}
			/*
			 * if the SERVICE is available,
			 * 1) get a map image
			 * 2) update the local backup of the capabilities doc if necessary
			 */
			if ($this->result != -1) {
				switch ($this->serviceType) {
					case "WMS":
						$this->mapURL = urldecode($this->getTagOutOfXML($this->reportFile,'getmapurl',$this->serviceType));
						break;
					case "WFS":
						$wfsFactory = new UniversalWfsFactory();
						$wfs = $wfsFactory->createFromDb($this->getTagOutOfXML($this->reportFile,'wfs_id',$this->serviceType));
						$featureInfoArray = array();
						foreach($wfs->featureTypeArray as $featureType) {
							//$e = new mb_exception("ft name: ".$featureType->name);
							//$e = new mb_exception("wfs version: ".$wfs->getVersion());
							$featureInfo->featureTypeName = $featureType->name;
							$feature = $wfs->getFeature($featureType->name, null, null, null, null, 1);
							//*************************************************************************
							//count features - part from http_auth/index.php - TODO - maybe included in wfs class
							libxml_use_internal_errors(true);
							try {
								$featureCollectionXml = simplexml_load_string($feature);
								if ($featureCollectionXml === false) {
									foreach(libxml_get_errors() as $error) {
        									//$err = new mb_exception("/lib/class_Monitor.php:".$error->message);
    									}
									throw new Exception("/lib/class_Monitor.php:".'Cannot parse featureCollection XML!');
									//TODO give error message
								}
							}
							catch (Exception $e) {
    								//$err = new mb_exception("/lib/class_Monitor.php:".$e->getMessage());
								//TODO give error message
							}
							if ($featureCollectionXml !== false) {
								//$featureCollectionXml->registerXPathNamespace("gmd", "http://www.isotc211.org/2005/gmd");
								$featureCollectionXml->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
								if ($wfs->getVersion() == '2.0.0' || $wfs->getVersion() == '2.0.2') {
									$featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs/2.0");
								} else {
									$featureCollectionXml->registerXPathNamespace("wfs", "http://www.opengis.net/wfs");
								}
								$featureCollectionXml->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
								$featureCollectionXml->registerXPathNamespace("gml", "http://www.opengis.net/gml");
								$featureCollectionXml->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
								$featureCollectionXml->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
								$featureCollectionXml->registerXPathNamespace("default", "");
        							//preg_match('@version=(?P<version>\d\.\d\.\d)&@i', strtolower($url), $wfs->getVersion());
       								if (!$wfs->getVersion()) {
									$e = new mb_notice("/lib/class_Monitor.php: No version for wfs request given in reqParams!");
								}
								switch ($wfs->getVersion()) {
                                    case "1.1.0": // fall through
                                    case "1.0.0":
										//get # of features from counting features
										$numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/gml:featureMember');
										$numberOfFeatures = count($numberOfFeatures);
										break;
                                    //for wfs 2.0 - don't count features
									default:
										//get # of features from attribut
										$numberOfFeatures = $featureCollectionXml->xpath('//wfs:FeatureCollection/@numberReturned');
										$numberOfFeatures = $numberOfFeatures[0];
										break;
								}
								//$e = new mb_exception("/lib/class_Monitor.php: ".$numberOfFeatures." delivered features from wfs.");
								if ($numberOfFeatures == "1") {
									$featureInfo->getFeature = 1;
								} else {
									$featureInfo->getFeature = 0;
								}	
							} else {
								$featureInfo->getFeature = -1;
							}
							$featureInfoArray[] = $featureInfo;
								//*************************************************************************
						}
						$this->feature_content = json_encode($featureInfoArray);
						//$e = new mb_exception("/lib/class_Monitor.php: feature_content: ".$this->feature_content);
						break;
				}
			if (isset($auth)) {
					switch ($this->serviceType) {
						case "WMS":
							if ($this->isImage($this->mapURL,$auth)) {
								$this->returnsImage = 1;
							} else {
								$this->returnsImage = -1;
							}
							break;
						case "WFS":

							break;
					}
			} else {
				switch ($this->serviceType) {
					case "WMS":
						if ($this->isImage($this->mapURL)) {
							$this->returnsImage = 1;
						} else {
							$this->returnsImage = -1;
						}
						break;
					case "WFS":

						break;
				}
			}
				//Check for valid XML - validate it again wms 1.1.1 -some problems occur?
				#$dtd = "../schemas/capabilities_1_1_1.dtd";
				#$dom = new domDocument;
				#$dom->loadXML($this->remoteXML);
				#if (!$dom->validate($dtd)) {
					#$this->result = -1;
					#$this->comment = "Invalid getCapabilities request/document or service exception.";
				#}
					#else {
					#$this->comment = "WMS is not up to date but valid!";
				#}


				/*
				 * if the local backup of the capabilities document
				 * is deprecated, update the local backup
				 */
				#if ($this->result == 0) {
					//$mywms = new wms();
		
					/* 
					 * if the capabilities document is valid,
					 * update it OR mark it as "not up to date"
					 */ 
					#if ($this->localXML==) {//check validation of capabilities document
						#if ($this->autoUpdate) {
							#$mywms->updateObjInDB($this->wmsId);
							#$this->updated = "1";
							#$this->comment = "WMS has been updated.";
							
						#}
						#else {
						#	$this->comment = "WMS is not up to date.";
						#}
					#}
					// capabilities document is invalid
					#else {
					#	$this->result = -1;
					#	$this->comment = "Invalid getCapabilities request/document or service exception.";
					#}    
				#}
			}
		}
		else {
			$this->result = -1;
			$this->comment = "Invalid upload URL.";
		}
		#$e = new mb_notice("class_monitor: constructor: result = " . $this->result);
		#$e = new mb_notice("class_monitor: constructor: comment = " . $this->comment);
		#$e = new mb_notice("class_monitor: constructor: returnsImage = " . $this->returnsImage);
	}
	
	/**
	 * 
	 */
	#function toString() {
		#$str = "";
		#$str .= "wmsid: " . $this->wmsId . "\nupload_id: " . $this->uploadId . "\n";
		#$str .= "autoupdate: " . $this->autoUpdate . "\n";
		#$str .= "result: " . $this->result . "\ncomment: " . $this->comment . "\n";
		#$str .= "timestamp: " . $this->timestamp . " (".date("F j, Y, G:i:s", $this->timestamp).")\n";
		#$str .= "getCapabilities URL: " . $this->capabilitiesURL . "\nupdated: " . $this->updated . "\n\n";
		#$str .= "getMap URL: " . $this->mapURL . "\nis image: " . $this->returnsImage . "\n\n";
		#$str .= "-------------------------------------------------------------------\n";
		#$str .= "remote XML:\n\n" . $this->remoteXML . "\n\n";
		#$str .= "-------------------------------------------------------------------\n";
		#$str .= "local XML:\n\n" . $this->localXML . "\n\n";
		#$str .= "-------------------------------------------------------------------\n";
		#return (string) $str;
	#}

	/**
	 * Update database
	 */
	function updateInDB() {
		switch ($this->serviceType) {
			case "WMS":
				$sql = "UPDATE mb_monitor SET updated = $1, status = $2, image = $3, status_comment = $4, upload_url = $5, timestamp_end = $6, map_url = $7 , timestamp_begin = $10 WHERE upload_id = $8 AND fkey_wms_id=$9";
				$v = array($this->updated, $this->result, $this->returnsImage, $this->comment, $this->capabilitiesURL, $this->timestamp_cap_end, $this->mapURL, $this->uploadId, $this->serviceId, $this->timestamp_cap_begin);
				$t = array('s', 'i', 'i', 's', 's', 's', 's', 's', 'i','s');
				$res = db_prep_query($sql,$v,$t);	
				break;	
			case "WFS":
				$sql = "UPDATE mb_monitor SET updated = $1, status = $2, feature_content = $3, status_comment = $4, upload_url = $5, timestamp_end = $6, feature_urls = $7 , timestamp_begin = $10 WHERE upload_id = $8 AND fkey_wfs_id=$9";
				$v = array($this->updated, $this->result, "feature_content - json", $this->comment, $this->capabilitiesURL, $this->timestamp_cap_end, "feature_urls - json", $this->uploadId, $this->serviceId, $this->timestamp_cap_begin);
				$t = array('s', 'i', 's', 's', 's', 's', 's', 's', 'i','s');
				$res = db_prep_query($sql,$v,$t);
				break;
		}	
	}
	/**
	 * Update xml
	 */
	function updateInXMLReport() {
		//create text for diff
		$difftext = "<html>\n";
		$difftext .= "<head>\n";
		$difftext .= "<title>Mapbender - monitor diff results</title>\n";
		$difftext .= "<meta http-equiv=\"cache-control\" content=\"no-cache\">\n";
		$difftext .= "<meta http-equiv=\"pragma\" content=\"no-cache\">\n";
		$difftext .= "<meta http-equiv=\"expires\" content=\"0\">\n";
		$difftext .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset='.CHARSET.'\">";	
		$difftext .= "<style type=\"text/css\">\n";
		$difftext .= "* {font-family: Arial, \"Courier New\", monospace; font-size: small;}\n";
		$difftext .= ".diff-context {background: #eee;}\n";
		$difftext .= ".diff-deletedline {background: #eaa;}\n";
		$difftext .= ".diff-addedline {background: #aea;}\n";
		$difftext .= ".diff-blockheader {background: #ccc;}\n";
		$difftext .= "</style>\n";
		$difftext .= "</head>\n";
		$difftext .= "<body>\n";
		$difftext .= "<table cellpadding=3 cellspacing=0 border=0>\n";
		$difftext .= "<tr><td align='center' colspan='2'>Local</td><td align='center' colspan='2'>Remote</td></tr>\n";		
		$difftext .= $this->capabilitiesDiff;
		$difftext .= "\n\t</table>\n\t";
		$difftext .= "</body></html>";
		//write to report
		$xml=simplexml_load_file($this->reportFile);	
		switch ($this->serviceType) {
			case "WMS":
				$xml->wms->image=$this->returnsImage;
				$xml->wms->status=$this->result;
				$xml->wms->getcapduration = intval(($this->timestamp_cap_end-$this->timestamp_cap_begin)*1000);
				$xml->wms->getcapdocremote = rawurlencode($this->remoteXML);
				$xml->wms->getcapdiff = rawurlencode($difftext);
				$xml->wms->comment=$this->comment;
				$xml->wms->getcapbegin=$this->timestamp_cap_begin;
				$xml->wms->getcapend=$this->timestamp_cap_end;
				break;
			case "WFS":
				//$xml->wfs->image=$this->returnsImage;
				$xml->wfs->status=$this->result;
				$xml->wfs->getcapduration = intval(($this->timestamp_cap_end-$this->timestamp_cap_begin)*1000);
				$xml->wfs->getcapdocremote = rawurlencode($this->remoteXML);
				$xml->wfs->getcapdiff = rawurlencode($difftext);
				$xml->wfs->comment=$this->comment;
				$xml->wfs->getcapbegin=$this->timestamp_cap_begin;
				$xml->wfs->getcapend=$this->timestamp_cap_end;
				$xml->wfs->feature_content=$this->feature_content;
				break;
		}
		$xml->asXML($this->reportFile);
	}
	/*
	 * Checks if the mapUrl returns an image or an exception
	 */
	function isImage($url) {
		#$headers = get_headers($url, 1);#controll this function TODO
		//$e = new mb_notice("class_monitor: isImage: map URL is " . $url);
		#$e = new mb_notice("class_monitor: isImage: Content-Type is " . $headers["Content-Type"]);
		#if (preg_match("/xml/", $headers["Content-Type"])) {
		#	return false;
		#}
		if (func_num_args() == 2) { //new for HTTP Authentication
        	    	$auth = func_get_arg(1);
			$imgObject = new connector($url, $auth);
		}
		else {
			$imgObject = new connector($url);
		}
		$image = $imgObject->file;
		//write images to tmp folder
		$imageName=$this->tmpDir."/"."monitor_getmap_image_".md5(uniqid()).".png";
		$fileMapImg = fopen($imageName, 'w+');
		$bytesWritten = fwrite($fileMapImg, $image);
		fclose($fileMapImg);
		//$e = new mb_notice("class_monitor: isImage: path: ".$imageName);
		//$e = new mb_notice("class_monitor: isImage: Content-Type is " . mime_content_type($image));
		//$e = new mb_notice("class_monitor: isImage: Content-Type (file) is " . mime_content_type($imageName));
		if (mime_content_type($imageName)=="image/png") {
			return true;
		}
		return false;
	}

	/*
	 * Checks if the getfeature url returns at minimum one feature
	 */
	function hasFeatures($url) {
		if (func_num_args() == 2) { //new for HTTP Authentication
        	    	$auth = func_get_arg(1);
			$featureCollectionObject = new connector($url, $auth);
		}
		else {
			$featureCollectionObject = new connector($url);
		}
		$featureCollection = $featureCollectionObject->file;
		//parse gml content
		
	}

	/**
	 * Returns the objects out of the xml file
 	 */
       private function getTagOutOfXML($reportFile,$tagName,$serviceType) {
		$xml=simplexml_load_file($reportFile);
		$result=(string)$xml->{strtolower($serviceType)}->$tagName;
		return $result;
	}
	/*
	* creates a html diff of the xml documents
	*/
	private function outputDiffHtml($localXMLArray,$remoteXMLArray) {
		$diffObj = new Diff($localXMLArray,$remoteXMLArray);
		$dft = new TableDiffFormatter();
		return $dft->format($diffObj);
	}			
}
