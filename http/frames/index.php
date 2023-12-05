<?php
# $Id: index.php 10237 2019-09-06 08:52:38Z armin11 $
# http://www.mapbender.org/index.php/index.php
#
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

require_once("../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_gui.php");
require_once(dirname(__FILE__)."/../classes/class_cache.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
//compress js and css source - TODO compress js - it has problems :-(
require_once(dirname(__FILE__)."/../extensions/minify.php");
//require_once(dirname(__FILE__)."/../extensions/jsqueeze-master/src/JSqueeze.php");
//require_once(dirname(__FILE__)."/../extensions/JShrink-master/src/JShrink/Minifier.php");
use MatthiasMullie\Minify;
//use Patchwork\JSqueeze;
//initial configuration
$createGuiStartTime = microtime_float();
$representationType = "htmlComplete";
$withDebugInfo = false;
$encodingType = "base64";
$encodeResult = true;
$minify = true;
//new for geoportal.rlp - some guis has special functions - for normal mapbender installation this doesn't matter
if (Mapbender::session()->get("mb_user_gui") !== false) {
	Mapbender::session()->set("previous_gui",Mapbender::session()->get("mb_user_gui"));
}
Mapbender::session()->set("mb_user_gui",$gui_id);
//
// check if user is allowed to access current GUI; 
// if not, return to login screen
//
$e = new mb_notice("GUIs for which user ".Mapbender::session()->get("mb_user_id")." is authorized: ".json_encode(Mapbender::session()->get("mb_user_guis")));

if (!in_array($gui_id, Mapbender::session()->get("mb_user_guis"))) {
	$e = new mb_exception("mb_validateSession.php: User: " . Mapbender::session()->get("mb_user_id")  . " not allowed to access GUI " . $gui_id);
	session_write_close();
	header("Location: ".LOGIN);
	die();
}
//check http parameter integrationType (html/div)
if (isset($_REQUEST["representationType"]) & $_REQUEST["representationType"] != "") {
	$testMatch = $_REQUEST["representationType"];	
 	if (!($testMatch == 'htmlComplete' or $testMatch == 'htmlElement')){ 
		$resultObject->error->message = 'Parameter outputFormat is not valid (complete, htmlElement).'; 
		echo json_encode($resultObject);
		die();	
 	}
	$representationType = $testMatch;
	$testMatch = NULL;
}
//check http parameter integrationType (html/div)
if (isset($_REQUEST["withDebugInfo"]) & $_REQUEST["withDebugInfo"] != "") {
	$testMatch = $_REQUEST["withDebugInfo"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		$resultObject->error->message = 'Parameter outputFormat is not valid (true, false).'; 
		echo json_encode($resultObject);
		die();	
 	}
	switch ($testMatch) {
		case "true":
			$withDebugInfo = true;
			break;
	}
	$testMatch = NULL;
}
//check http parameter encodeResult (true/false)
if (isset($_REQUEST["encodeResult"]) & $_REQUEST["encodeResult"] != "") {
	$testMatch = $_REQUEST["encodeResult"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		$resultObject->error->message = 'Parameter outputFormat is not valid (true, false).'; 
		echo json_encode($resultObject);
		die();	
 	}
	switch ($testMatch) {
		case "true":
			$encodeResult = true;
			break;
		case "false":
			$encodeResult = false;
			break;
	}
	$testMatch = NULL;
}
//check http parameter minify (true/false)
if (isset($_REQUEST["minify"]) & $_REQUEST["minify"] != "") {
	$testMatch = $_REQUEST["minify"];	
 	if (!($testMatch == 'true' or $testMatch == 'false')){ 
		$resultObject->error->message = 'Parameter outputFormat is not valid (true, false).'; 
		echo json_encode($resultObject);
		die();	
 	}
	switch ($testMatch) {
		case "true":
			$minify = true;
			break;
		case "false":
			$minify = false;
			break;
	}
	$testMatch = NULL;
}
$linebreak = "\n";
if ($representationType == "htmlComplete") {
    $html = "";
    $html .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">".$linebreak;
    $html .= "<html>".$linebreak;
    $html .= "<head>".$linebreak;
    $html .= "<!-- Licensing: See the GNU General Public License for more details. http://www.gnu.org/copyleft/gpl.html or: mapbender/licence/ -->".$linebreak;
    $html .= "<meta http-equiv=\"cache-control\" content=\"no-cache\">".$linebreak;
    $html .= "<meta http-equiv=\"pragma\" content=\"no-cache\">".$linebreak;
    $html .= "<meta http-equiv=\"expires\" content=\"0\">".$linebreak;
    $html .= "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">".$linebreak;
    $html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".CHARSET."\">".$linebreak;
    $html .= "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\">".$linebreak;
    $html .= "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\">".$linebreak;
    $html .= "<title>".$gui_id." - presented by Mapbender</title>".$linebreak;
} else {
    $resultObject = new StdClass();
}

//check if element var for caching gui is set to true!
$sql = "SELECT * FROM gui_element_vars WHERE fkey_gui_id = $1 AND fkey_e_id = 'body' AND var_name='cacheGuiHtml'";
$v = array($gui_id);
$t = array('s');
$res = db_prep_query($sql,$v,$t);
$row = db_fetch_array($res);
//$e = new mb_notice("count row: ".count($row['var_name']));
if (count($row['var_name']) == 1) {
	$activatedGuiHtmlCache = $row['var_value'];
	if ($activatedGuiHtmlCache == 'true') {
		$activatedGuiHtmlCache = true;
	} else {
		$activatedGuiHtmlCache = false;
	}
} else {
	$activatedGuiHtmlCache = false;
}
//use cache is cache is activated
//instantiate cache if available
$cache = new Cache();
//define key name cache
$cacheKeyElementVars = 'guiElementVars_'.$gui_id;
/*if ($cache->isActive && $cache->cachedVariableExists("mapbender: " . $cacheKeyElementVars)) {
	$e = new mb_exception("frames/index.php: read elementVars from ".$cache->cacheType." cache!");
	$res = $cache->cachedVariableFetch("mapbender: " . $cacheKeyElementVars);
} else {*/
	//do sql instead
	$sql = "SELECT * FROM gui_element_vars WHERE fkey_e_id = 'body' AND fkey_gui_id = $1 and var_name='favicon' ORDER BY var_name";
	$v = array($gui_id);
	$t = array('s');
	$res = db_prep_query($sql,$v,$t);
	/*if ($cache->isActive) {
		$cache->cachedVariableAdd("mapbender: " . $cacheKeyElementVars,$res);
	}*/
//}//uncomment for cache
$cnt = 0;
if ($representationType == "htmlComplete") {
	while($row = db_fetch_array($res)){
		$html .= "<link rel=\"shortcut icon\" type=\"image/png\" href=\"".$row["var_value"]."\">".$linebreak;
	}
}
//reset CSS
if ($representationType == "htmlComplete") {
	$html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"../css/reset.css\">".$linebreak;
} else {
	//TODO add css file content to central css file in tmp folder
	$resultObject->cssFiles[] = "../css/reset.css";
}
if ($representationType == "htmlElement") {
	$resultObject->jsFiles = array();
	$resultObject->jsString = "";
}
//define new key name cache
$cacheKeyGuiCss = 'guiCss_'.$gui_id;
/*if ($cache->isActive && $cache->cachedVariableExists("mapbender: " . $cacheKeyGuiCss)) {
	$e = new mb_exception("frames/index.php: read guiCss from ".$cache->cacheType." cache!");
	$res = $cache->cachedVariableFetch("mapbender: " . $cacheKeyGuiCss);
} else {*/
	$sql = <<<SQL
	
SELECT DISTINCT e_id, e_element, var_value, var_name, var_type FROM gui_element, gui_element_vars 
WHERE 
		e_id = fkey_e_id 
		AND e_element <> 'iframe' 
		AND gui_element.fkey_gui_id = $1 
		AND gui_element_vars.fkey_gui_id = $1 
		AND var_type='file/css' 
	ORDER BY var_name

SQL;
	$v = array($gui_id);
	$t = array('s');
	$res = db_prep_query($sql,$v,$t);
	/*if ($cache->isActive) {
		$cache->cachedVariableAdd("mapbender: " . $cacheKeyGuiCss,$res);
	}*/
//}//for cache
if ($representationType !== "htmlComplete") {
	//CSS string
	$cssString = "";
	$connector = new connector();
	//$cssConnector->set('httpType','POST');
} else {
	$cssString = false;
}
if ($representationType == "htmlComplete") {
	$cnt = 0;
	while($row = db_fetch_array($res)){
	    $html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$row["var_value"]."\">".$linebreak;
	}
} else {
	$cnt = 0;
	while($row = db_fetch_array($res)){
	    $cssString .= getSourceCode($row["var_value"]);
	    $resultObject->cssFiles[] = $row["var_value"];
	}
	$cssString .= getSourceCode("../css/reset.css");
}
//inlined css elements from element vars - TODO they are commented out - why?
if ($representationType == "htmlComplete") {
	$html .= "<style type=\"text/css\">".$linebreak;
	$html .= "<!--".$linebreak;
}
$sql = <<<SQL
	
SELECT DISTINCT e_id, e_element, var_value, var_name, var_type FROM gui_element, gui_element_vars 
WHERE 
	e_id = fkey_e_id 
	AND e_element <> 'iframe' 
	AND gui_element.fkey_gui_id = $1 
	AND gui_element_vars.fkey_gui_id = $1 
	AND var_type = 'text/css' 
ORDER BY var_name

SQL;

$v = array($gui_id);
$t = array('s');
$res = db_prep_query($sql,$v,$t);
$cnt = 0;
while($row = db_fetch_array($res)){
        if ($representationType == "htmlComplete") {
	    $html .= $row["var_value"].$linebreak;
	}
}
if ($representationType == "htmlComplete") {
	$html .= "-->";
	$html .= "</style>".$linebreak;
	$html .= "<script type='text/javascript' src='../javascripts/core.php'></script>".$linebreak;
	$html .= "</head>".$linebreak;
} else {
	//TODO add javascript to json object! Look at basepath!
	$resultObject->jsString .= getSourceCode('../javascripts/core.php', 'js').$linebreak;
	$resultObject->jsFiles[] =  '../javascripts/core.php';
}
if (defined(LOAD_JQUERY_FROM_GOOGLE) && LOAD_JQUERY_FROM_GOOGLE) {
	if ($representationType == "htmlComplete") {
		$html .= "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>".$linebreak;
	} else {	    
		$resultObject->jsString .= getSourceCode('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js', 'js').$linebreak;
		$resultObject->jsFiles[] =  'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js';
	}
}
//cache complete application ;-) - in future only body object!!! not the css!!!
$cacheKeyGuiHtml = 'guiHtml_'.$gui_id;
//$e = new mb_notice("frames/index.php: activatedGuiHtmlCache: ". $activatedGuiHtmlCache);
if ($cache->isActive && $activatedGuiHtmlCache && $cache->cachedVariableExists("mapbender: " . $cacheKeyGuiHtml)) {
	//$e = new mb_notice("frames/index.php: read gui html from ".$cache->cacheType." cache!");
    $guiHtml = $cache->cachedVariableFetch("mapbender: " . $cacheKeyGuiHtml);
} else {
	$currentApplication = new gui($gui_id);
        $guiHtml = $currentApplication->toHtml();
	if ($cache->isActive) {
	    $cache->cachedVariableAdd("mapbender: " . $cacheKeyGuiHtml,$guiHtml);
	}
}
if ($representationType == "htmlComplete") {
	$html .= $guiHtml;
} else {
	$resultObject->htmlString = replaceBodyWithElement($guiHtml);
}
$mapPhpParameters = htmlentities($urlParameters, ENT_QUOTES, CHARSET);
$mapPhpParameters .= "&amp;".htmlentities($_SERVER["QUERY_STRING"]);
//TODO - validate further GET params - e.g. querylayers ... - do this also in index_ext.php!
//$e = new mb_exception("index.php: mapPhpParameters: ".$mapPhpParameters);
if ($representationType == "htmlComplete") {
	$html .= "<script type='text/javascript' src='../javascripts/map.php?".$mapPhpParameters."'></script>".$linebreak;
} else {
	//TODO add javascript to json object
	$resultObject->jsString .= getSourceCode('../javascripts/map.php?'.$mapPhpParameters, 'js').$linebreak;
	$resultObject->jsFiles[] =  "../javascripts/map.php?".$mapPhpParameters;
}
$timeToCreateGui = microtime_float() - $createGuiStartTime;
if ($representationType == "htmlComplete") {
	$html .= "</html>";
	//give back application and end
	echo $html;
	die();
} else {
	//TODO some tests for alternative php based compressors https://ourcodeworld.com/articles/read/350/how-to-minify-javascript-and-css-using-php
	/*$testjs = $resultObject->jsString;
	$minifier = new Minify\JS($testjs);
	$minifiedCode = \JShrink\Minifier::minify($testjs);
	echo $minifiedCode;
	die();
	$jz = new JSqueeze();*/
	// Retrieve the content of a JS file
	/*$minifiedJs = $jz->squeeze(
    		$testjs,
    		true,   // $singleLine
    		true,   // $keepImportantComments
    		false   // $specialVarRx
	);*/
	$minifier = new Minify\CSS($cssString);
	//echo $minifiedJs;
	//echo $minifier->minify();
	//check time to minify css
	if ($withDebugInfo == true) $beginCssMinify = microtime_float();
	if ($minify == true) {
	    $resultObject->cssString = $minifier->minify();
	} else {
	    $resultObject->cssString = $cssString;
	}
	if ($withDebugInfo == true) $timeToCssMinify = microtime_float() - $beginCssMinify;
	if ($encodeResult == true) $resultObject->cssString = base64_encode($resultObject->cssString);
	if ($withDebugInfo == true) $beginJsBaseEncode = microtime_float();
	if ($encodeResult == true) $resultObject->jsString = base64_encode($resultObject->jsString);
	if ($withDebugInfo == true) $timeToJsBaseEncode = microtime_float() - $beginJsBaseEncode;
	if ($withDebugInfo == true) $beginHtmlBaseEncode = microtime_float();
	if ($encodeResult == true) $resultObject->htmlString = base64_encode($resultObject->htmlString);
	if ($withDebugInfo == true) $timeToHtmlBaseEncode = microtime_float() - $beginHtmlBaseEncode;
	$resultObject->genTime = $timeToCreateGui;
	if ($withDebugInfo == true) {
	    $resultObject->timeToCssMinify = $timeToCssMinify;
	    $resultObject->timeToJsBaseEncode = $timeToJsBaseEncode;
	    $resultObject->timeToHtmlBaseEncode = $timeToHtmlBaseEncode;
	}
	if ($withDebugInfo == false) {
		unset($resultObject->cssFiles);
		unset($resultObject->jsFiles);
	}
	echo json_encode($resultObject);
}

function replaceBodyWithElement($html, $elementName = 'div') {
    //https://stackoverflow.com/questions/6892199/how-can-i-grab-the-entire-content-inside-body-tag-with-regex
    preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches);
    $result = "<".$elementName." id='body'>".$matches[1]."</".$elementName.">";
    //$result = "<".$elementName.">".$matches[1]."</".$elementName.">";
    //$e = new mb_exception($result);
    return $result;
}

function getSourceCode($path, $fileType = 'css') {
    global $connector;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $scheme = "https";
    } else {
	$scheme = "http";
    }
    $pathPrefix = $scheme.'://'.$_SERVER['HTTP_HOST'].parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $pathPrefix = pathinfo($pathPrefix);
    $pathPrefix = $pathPrefix['dirname']."/";
    if (substr($path, 0, 4) == 'http' || $fileType == 'js') {
	if ($fileType == 'js' && substr($path, 0, 4) !== 'http') {
	    //make absolute path, because script may be built dynamic!
	    $path = $pathPrefix . $path;
//$e = new mb_exception("jspath: ".$path);	
	}
        $connector->load($path);
//$e = new mb_exception("result: ".$connector->file);
        return $connector->file;
    } else {
        return file_get_contents($path);
    }
}

function microtime_float() {
    	list($usec, $sec) = explode(" ", microtime());
    	return ((float)$usec + (float)$sec);
}
?>
