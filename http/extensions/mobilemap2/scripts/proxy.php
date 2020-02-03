<?php

require_once(dirname(__FILE__)."/../../../../conf/mapbender.conf");
require_once(dirname(__FILE__)."/../../../../http/classes/class_connector.php");
$query = urldecode( $_GET[ "q" ] );

/*
// Open the Curl session
$session = curl_init( $query );

//use proxy if proxy is given in mapbender.conf - from class_connector.php
$arURL = parse_url($query);
$host = $arURL["host"];
$NOT_PROXY_HOSTS_array = explode(",", NOT_PROXY_HOSTS);
if(CONNECTION_PROXY != "" AND (in_array($host, $NOT_PROXY_HOSTS_array)!= true)){
	curl_setopt($session, CURLOPT_PROXY,CONNECTION_PROXY.":".CONNECTION_PORT);
	if(CONNECTION_PASSWORD != ""){
		curl_setopt ($session, CURLOPT_PROXYUSERPWD, CONNECTION_USER.':'.CONNECTION_PASSWORD);	
	}
}

// Don't return HTTP headers. Do return the contents of the call
curl_setopt( $session, CURLOPT_HEADER, false );
curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

// Make the call
$response = curl_exec( $session );*/

$getfeatureUrlObject = parse_url($query);

$getFeatureInfoUrlQueryParams = explode('&', $getfeatureUrlObject['query']);


$mandatoryGetFeatureInfoRequestParameters = array('VERSION','REQUEST','QUERY_LAYERS','X','Y','BBOX','WIDTH','HEIGHT');

$numberOfMandatoryFields = 0;

foreach ($getFeatureInfoUrlQueryParams as $getFeatureInfoUrlQueryParam) {
    $qP = explode('=', $getFeatureInfoUrlQueryParam);
    $qP = $qP[0];
    if (in_array($qP, $mandatoryGetFeatureInfoRequestParameters)) {
        $numberOfMandatoryFields++;
    }
}

if ($numberOfMandatoryFields == 8) {
    // is ok - most params are given
} else {
    echo "No accepted REQUEST!";
    die();
}

$connector = new connector();
$result = $connector->load($query);	
//Datenausgabe
echo $result;

//curl_close( $session );
?>
