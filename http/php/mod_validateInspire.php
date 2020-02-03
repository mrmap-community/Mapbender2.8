<?php
//function to validate an resource against the inspire vaildator
// Copyright (C) 2002 CCGIS 
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//TODO - make a class of it - with url, and return mimetype as attributes
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");

function validateInspire($xml) {
	//$validatorUrl = 'http://localhost/mapbender_trunk/geoportal/log_requests.php';
	//$validatorUrl = 'http://inspire-geoportal.ec.europa.eu/GeoportalProxyWebServices/resources/INSPIREResourceTester';
	if (defined("INSPIRE_VALIDATOR_URL") && INSPIRE_VALIDATOR_URL != '') {
		$validatorUrl = INSPIRE_VALIDATOR_URL;
		$urlParts = parse_url($validatorUrl);
		$inspireHost = $urlParts['host'];
	} else {
		echo "No validation service defined! Please check your mapbender.conf";
		die();
	}
	$eol = "\r\n";
	$data = '';
	$mime_boundary=md5(time());
	$data .= '--' . $mime_boundary . $eol;
	$data .= 'Content-Disposition: form-data; name="resourceRepresentation"' .$eol.$eol;
	//example
	//$data .= "http://map1.naturschutz.rlp.de/service_lanis/mod_wms/wms_getmap.php?mapfile=naturschutzgebiet&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
	$data .= $xml;
	$data .= $eol;
	$data .= '--' . $mime_boundary . '--' .$eol;
	$data .= $eol; // finish with two eol's!!
	$header = "";
	$header .= 'Content-Type: multipart/form-data; boundary='.$mime_boundary.$eol;
	$header .= 'Content-Length: '.strlen($data).$eol;
	//$data = http_build_query($data);
	//check proxy connections
	if (defined("CONNECTION_PROXY") &&  CONNECTION_PROXY != "") {
                if (defined("CONNECTION_USER") && CONNECTION_USER != "") {
                	$auth = base64_encode('$CONNECTION_USER:$CONNECTION_PASSWORD');
                	$header .= 'Proxy-Authorization: Basic '.$auth;
                }
		$context_options = array (
        			'http' => array (
					'proxy' => "tcp://".CONNECTION_PROXY.":".CONNECTION_PORT,
            				'method' => 'POST',
                                        'request_fulluri' => true,
            				'header' => $header,
            				'content' => $data
            				)
        			);
	} else {
		$context_options = array (
        			'http' => array (
            				'method' => 'POST',
            				'header' => $header,
            				'content' => $data
            				)
        			);
	}
	$context = stream_context_create($context_options);
	$response = @file_get_contents($validatorUrl, FILE_TEXT, $context);
	header("Content-type: text/html; charset=UTF-8");
	$repl = str_replace('/schemas/altova/inspireResource.css', 'http://inspire-geoportal.ec.europa.eu/schemas/altova/inspireResource.css', trim($response));
	$repl = str_replace("/documentation/", "http://".$inspireHost."/documentation/", $repl);
	echo $repl;
}

function validateInspireXml($xml){
	//$validatorUrl = 'http://localhost/mapbender_trunk/geoportal/log_requests.php';
	//$validatorUrl = 'http://inspire-geoportal.ec.europa.eu/GeoportalProxyWebServices/resources/INSPIREResourceTester';
	if (defined("INSPIRE_VALIDATOR_URL") && INSPIRE_VALIDATOR_URL != '') {
		$validatorUrl = INSPIRE_VALIDATOR_URL;
	} else {
		echo "No validation service defined! Please check your mapbender.conf";
		die();
	}
	$eol = "\r\n";
	$data = '';
	$mime_boundary=md5(time());
	$data .= '--' . $mime_boundary . $eol;
	$data .= 'Content-Disposition: form-data; name="resourceRepresentation"' .$eol.$eol;
	//example
	//$data .= "http://map1.naturschutz.rlp.de/service_lanis/mod_wms/wms_getmap.php?mapfile=naturschutzgebiet&REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS";
	$data .= $xml;
	$data .= $eol;
	$data .= '--' . $mime_boundary . '--'. $eol;
	$data .= $eol; // finish with two eol's!!
	$header = "";
	$header .= 'Accept: application/xml'.$eol;
	$header .= 'Content-Type: multipart/form-data; boundary='.$mime_boundary.$eol;
	$header .= 'Content-Length: '.strlen($data).$eol;
	//$data = http_build_query($data);
	//check proxy connections
	if (defined("CONNECTION_PROXY") &&  CONNECTION_PROXY != "") {
		if (defined("CONNECTION_USER") && CONNECTION_USER != "") {
                	$auth = base64_encode('$CONNECTION_USER:$CONNECTION_PASSWORD');
                	$header .= 'Proxy-Authorization: Basic '.$auth;
                }
		$context_options = array (
        			'http' => array (
					'proxy' => "tcp://".CONNECTION_PROXY.":".CONNECTION_PORT,
            				'method' => 'POST',
            				'header' => $header,
            				'content' => $data
            				)
        			);
	} else {
		$context_options = array (
        			'http' => array (
            				'method' => 'POST',
            				'header' => $header,
            				'content' => $data
            				)
        			);
	}
	$context = stream_context_create($context_options);
	$response = @file_get_contents($validatorUrl, FILE_TEXT, $context);

        //Simple XML seems a bit too picky so we massage the response a bit
        $repl = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '', trim($response));
        $repl = str_replace("\n", '', $repl); // remove new lines
        $repl = str_replace("\r", '', $repl); // remove carriage returns
        $repl = trim($repl);

        $xml = simplexml_load_string($repl);

        $resource = $xml->getName();

        $namespaces = $xml->getNameSpaces(true);
        //$key_inspire_geoportal_namespace = array_search('http://inspire.ec.europa.eu/schemas/geoportal/1.0', $namespaces);
        //$namespaces[$key_inspire_geoportal_namespace];

        $ns_inspire_geoportal = "http://inspire.ec.europa.eu/schemas/geoportal/1.0";
        $ns_inspire_common = "http://inspire.ec.europa.eu/schemas/common/1.0";

        foreach ($xml->children($ns_inspire_geoportal) as $child) {
            $nodeName = $child->getName();
            if ($nodeName == "ResourceReportResource") {
                //There were issues 
                print("Issues were found");
                foreach ($child->children($ns_inspire_geoportal) as $errorNode) {
                    $errorNodeName = $errorNode->getName();
                    if ($errorNodeName == "InspireValidationErrors") {
                        foreach ($errorNode->children($ns_inspire_geoportal) as $validationErrorNode) {
                            $validationErrorNodeName = $validationErrorNode->getName();
                            if ($validationErrorNodeName == "ValidationError") {
                                foreach ($validationErrorNode->children($ns_inspire_geoportal) as $geoportalErrorNode) {
                                    $geoportalErrorNodeName = $geoportalErrorNode->getName();
                                    if ($geoportalErrorNodeName == "GeoportalExceptionMessage") {
                                        foreach ($geoportalErrorNode->children($ns_inspire_geoportal) as $geoportalMessageNode) {
                                            $geoportalMessageNodeName = $geoportalMessageNode->getName();
                                            if ($geoportalMessageNodeName == "Message") {
                                                $message = (string) $geoportalMessageNode;
                                                print($message);
                                                print("<BR>");
                                            }
                                        }
                                    }
                                }
                                //
                            }
                        }
                    }
                }
                break;
            } else {
                print("The resource has no validation issues and has been recognized as a: " . $nodeName);
                break;
            }
        }
}
?>
