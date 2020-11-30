<?php
# $Id: class_owsPostQueryParser.php $
# http://www.mapbender.org/index.php/class_owsPostQueryParser.php
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

require_once(dirname(__FILE__)."/../../core/globalSettings.php");

/**
 * An class to parse OWS Post queries to extract the relevant parameters, post or post_xml musst be tested!
 */
 class OwsPostQueryParser {
	var $serviceType; //string
	var $serviceVersion; //string
	var $serviceRequestType; //string
	var $serviceResourceName; //string - layer name(s), featuretype name(s)
	var $parsingSuccessful; //boolean
	var $postType; //string - 'xml' or 'form'
	/**
	 * Constructor of the OwsPostQueryHandler
	 * 
	 */
	function __construct($postData){
		//parse xml via dom
		//$e = new mb_exception($postData);
		$this->parsingSuccessfull = false;
		$queryDomObject = new DOMDocument();
		libxml_use_internal_errors(true);
		try {
			$queryDomObject->loadXML($postData);
			if ($queryDomObject === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_owsPostQueryParser.php: ".$error->message);
    				}
				throw new Exception("class_owsPostQueryParser.php: ".'Cannot parse post query with dom!');
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_owsPostQueryParser.php: ".$e->getMessage());
		}
		if ($queryDomObject !== false) {
			$xpath = new DOMXPath($queryDomObject);
			//test if namespace is used in operation declaration
			$this->serviceRequestType = $queryDomObject->documentElement->tagName;
			//$e = new mb_exception($this->serviceRequestType);
			//explode this
			$explodedTagName = explode(':', $this->serviceRequestType);
			if (count($explodedTagName) == 2) {
				$hasNamespace = true;
			} else {
				$hasNamespace = false;
			}
			if ($hasNamespace) {
				$this->serviceRequestType = $explodedTagName[1];
				//get relevant namespace uri for first element (root)
				$rootNamespace = $queryDomObject->lookupNamespaceUri($explodedTagName[0]);
			} else {
				$rootNamespace = $queryDomObject->lookupNamespaceUri($queryDomObject->namespaceURI);
			}
			
			//$e = new mb_exception("class_owsPostQueryHandler.php: ".json_encode($rootNamespace));
			switch ($rootNamespace) {
				case "http://www.opengis.net/wfs":
				case "http://www.opengis.net/wfs/2.0":
					$this->serviceType = "WFS";
                                	break;
				//maybe 1.1.0 or 1.0.0
				//maybe 1.1.0 or 1.1.1 or 1.3.0 - does 1.1.0 and 1.1.1 support POST?
				case "http://www.opengis.net/wms": 
					$this->serviceType = "WMS";
                                	break;
			}
			//$e = new mb_exception("class_owsPostQueryHandler.php: ".$this->serviceRequestType);
			$this->serviceVersion = $queryDomObject->documentElement->getAttribute("version");
			if ($this->serviceType == "WFS") {
				//read out typename from wfs query as attribute
				//register namespace
				if ($hasNamespace) {
					$xpath->registerNamespace($explodedTagName[0], $rootNamespace); 
					$queryNodeList = $xpath->query('/'.$explodedTagName[0].':'.$this->serviceRequestType.'/'.$explodedTagName[0].':Query');
				} else {
					$xpath->registerNamespace('defaultns', $rootNamespace);
					$queryNodeList = $xpath->query('/defaultns:'.$this->serviceRequestType.'/defaultns:Query');
				}
				//array of requests that need typenames
				$typenameRequired = array('getfeature','describefeaturetype');
				//TODO: look for typenames only in getfeature requests!!!!!! https://github.com/qgis/QGIS/commit/ccb4c80f8a6d2bb179258f1ffec0dc9a447ca465
				if (in_array(strtolower($this->serviceRequestType), $typenameRequired)) {
					switch ($this->serviceVersion) {
						case "2.0.0":
                                                        if (strtolower($this->serviceRequestType) == 'describefeaturetype') {
							    $this->serviceResourceName = $queryNodeList->item(0)->getAttribute('typeName');
							} else {
							    $this->serviceResourceName = $queryNodeList->item(0)->getAttribute('typeNames');
							}
							break;
						case "2.0.2":
							if (strtolower($this->serviceRequestType) == 'describefeaturetype') {
							    $this->serviceResourceName = $queryNodeList->item(0)->getAttribute('typeName');
							} else {
							    $this->serviceResourceName = $queryNodeList->item(0)->getAttribute('typeNames');
							}
							break;
						default:
							$this->serviceResourceName = $queryNodeList->item(0)->getAttribute('typeName');
							break;
					}
				}
			}
			//check for getfeature request with given storedquery_id
			if ($this->serviceType == "WFS" && ($this->serviceVersion == "2.0.2" || $this->serviceVersion == "2.0.0") && strtolower($this->serviceRequestType) == 'getfeature') {
				if ($hasNamespace) {
					$xpath->registerNamespace($explodedTagName[0], $rootNamespace); 
					$storedQueryNodeList = $xpath->query('/'.$explodedTagName[0].':'.$this->serviceRequestType.'/'.$explodedTagName[0].':StoredQuery');
				} else {
					$xpath->registerNamespace('defaultns', $rootNamespace);
					$storedQueryNodeList = $xpath->query('/defaultns:'.$this->serviceRequestType.'/defaultns:StoredQuery');
				}
				//get id from stored query
				//$e = new mb_exception(json_encode($storedQueryNodeList->length));
				if ($storedQueryNodeList->length > 0) {
				    $this->serviceStoredQueryId = $storedQueryNodeList->item(0)->getAttribute('id');
				}
			}
			//validate extracted parameters
		}	
	}
}
?>
