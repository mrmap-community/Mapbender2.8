<?php
require_once(dirname(__FILE__)."/../../../../conf/mobilemap.conf");
require_once(dirname(__FILE__)."/../../../../conf/mapbender.conf");
// PHP Proxy 
// Erlaubter Hostname
//$getjsonurl = 'http://www.geoportal.rlp.de/mapbender/geoportal/gaz_geom_mobile.php?';

//Url-Parameter
$path = $_SERVER['QUERY_STRING'];
$url = $getjsonurl.$path;
$useproxy = true;


if($useproxy){
	// Open the Curl session
	$session = curl_init($url);

	// Don't return HTTP headers. Do return the contents of the call
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

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

	// Make the call
	$response = curl_exec($session);
	
	//header("Content-Type: text/plain");
	//header("Content-Type: application/json");

	//Datenausgabe
	echo $response;
	curl_close($session);
}
else{
	Header($url);
	exit(); 
}

?>
