<?php
//some security tests for mapbender php scripts to prevent xss attacks
//TBD - extend them ;-)
if (strpos($_SERVER['PHP_SELF'],'<script>') !== false ) {
	echo "Mapbender invested a XSS attack - script stopped executing!";
	die();
}
//parse url
//get pathes and other things after script name that are not path related and kick them off!
//echo $_SERVER['PHP_SELF']."<br>";
//echo $_SERVER['REQUEST_URI']."<br>";
//echo $_SERVER['SCRIPT_NAME']."<br>";
//test ob php_self auf script_name ended!
//get last string 
$phpScriptName = end(explode("/", $_SERVER['SCRIPT_NAME']));
//echo $phpScriptName."<br>";
//echo json_encode(endsWith($_SERVER['PHP_SELF'], $phpScriptName))."<br>";
if (!endsWith($_SERVER['PHP_SELF'], $phpScriptName)) {
	echo "Mapbender invested a XSS attack - script stopped executing!";
	die();
}
#https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
function endsWith($haystack, $needle) {
    return substr($haystack,-strlen($needle))===$needle;
}
?>
