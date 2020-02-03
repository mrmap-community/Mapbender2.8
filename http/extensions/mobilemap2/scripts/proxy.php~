<?php

$query = urldecode( $_GET[ "q" ] );

// Open the Curl session
$session = curl_init( $query );

// Don't return HTTP headers. Do return the contents of the call
curl_setopt( $session, CURLOPT_HEADER, false );
curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

// Make the call
$response = curl_exec( $session );
	
//Datenausgabe
echo $response;

curl_close( $session );
