<?php

//Basic configuration of mapserver client
require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");
require_once(dirname(__FILE__)."/../../../../conf/mapbender.conf");
// PHP Proxy 
// Erlaubte Hostnamen aus baseconfig
$mapbenderurl = $wmcInterface;
//$mapbenderurl = "http://www.geoportal.rlp.de/mapbender/php/mod_exportWmc2Json.php?";

//Url-Parameter
$urlparam = $_SERVER['QUERY_STRING'];
$url = $mapbenderurl.$urlparam ;

// Open the Curl session
$session = curl_init($url);

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
	
//Datenausgabe
echo $response;
curl_close($session);


?>
