<?php

//Basic configuration of mapserver client
require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");
require_once(dirname(__FILE__)."/../../../../conf/mapbender.conf");

// PHP Proxy 
// Erlaubte Hostnamen aus baseconfig
define ('MAPPROXY_HOSTNAME', $mapproxyurl);
define ('GETMAP_HOSTNAME',$getmapurl);
define ('GETFEATURE_HOSTNAME', $getfeatureurl);

//Url-Parameter
$path = $_SERVER['QUERY_STRING'];

//angefragtes Format GetMap
if ((isset($_GET['FORMAT'])) && ($_GET['FORMAT'] != "")) {
	$myformat = $_GET["FORMAT"];
	$url = GETMAP_HOSTNAME.$path;
}

//angefragtes Format Featureinfo
if ((isset($_GET['INFO_FORMAT'])) && ($_GET['INFO_FORMAT'] != "")) {
	$myformat = $_GET["INFO_FORMAT"];
	$url = GETFEATURE_HOSTNAME.$path;
}

//Url ändern falls URL-Variable cache mitgegeben wird
if ((isset($_GET['CACHE'])) && ($_GET['CACHE'] == "mapproxy")) {
	$url = MAPPROXY_HOSTNAME.$path;
}


if($useproxy){
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
	else if($myformat == "image/png"){
		header("Content-Type: image/png");
	}
	else if($myformat == "text/html"){
		header("Content-Type: text/html");
	}
	else {
		//header("Content-Type: text/plain");
	}
	
	//Datenausgabe
	echo $response;
	curl_close($session);
}
else{
	Header($url);
	exit(); 
}

?>
