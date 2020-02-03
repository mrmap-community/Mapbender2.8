<?php

//Basic configuration of mapserver client
require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");
require_once(dirname(__FILE__)."/../../../../conf/mapbender.conf");
// PHP Proxy 
// Erlaubte Hostnamen aus baseconfig
$mapbenderurl = $catalogueInterface;
//$mapbenderurl = "http://www.geoportal.rlp.de/mapbender/php/mod_callMetadata.php?";
//Url-Parameter
$urlparam = $_SERVER['QUERY_STRING'];
$url = $mapbenderurl.$urlparam;
$url = str_replace(" ",",",$url);
$url = str_replace("%20",",",$url);
//angefragtes Format GetMap
if ((isset($_GET['FORMAT'])) && ($_GET['FORMAT'] != "")) {
	$myformat = $_GET["FORMAT"];
}

	// Open the Curl session
	$session = curl_init($url);

	// If it's a POST, put the POST data in the body
	if ($_POST['yws_path']) {
		$postvars = '';
		while ($element = current($_POST)) {
			$postvars .= urlencode(key($_POST)).'='.urlencode($element).'&';
			next($_POST);
		}
		curl_setopt ($session, CURLOPT_POST, true);
		curl_setopt ($session, CURLOPT_POSTFIELDS, $postvars);
	}

	//use proxy if proxy is given in mapbender.conf - from class_connector.php
	$arURL = parse_url($url);
	$host = $arURL["host"];
	$NOT_PROXY_HOSTS_array = explode(",", NOT_PROXY_HOSTS);
	if(CONNECTION_PROXY != "" AND (in_array($host, $NOT_PROXY_HOSTS_array)!= true)){
		curl_setopt($session, CURLOPT_PROXY,CONNECTION_PROXY.":".CONNECTION_PORT);
		if(CONNECTION_PASSWORD != ""){
			curl_setopt ($session, CURLOPT_PROXYUSERPWD, CONNECTION_USER.':'.CONNECTION_PASSWORD);	
		}
	}

	// Don't return HTTP headers. Do return the contents of the call
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

	// Make the call
	$response = curl_exec($session);

	//Content-Type
	if($myformat == "image/jpeg"){
		header("Content-Type: image/jpeg");
	}
	
	//Datenausgabe
	echo $response;
	curl_close($session);


?>
