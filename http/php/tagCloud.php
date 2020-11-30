<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);
$pathToSearchScript = '/php/mod_callMetadata.php?';
$languageCode = 'de';
$maxFontSize = 40;
$minFontSize = 10;
$maxObjects = 10;
$outputFormat = 'html';
$hostName = $_SERVER['HTTP_HOST'];
$orderBy = "rank";
//read out information from database:

if (isset($_REQUEST["type"]) & $_REQUEST["type"] != "") {
	$testMatch = $_REQUEST["type"];	
 	if (!($testMatch == 'keywords' or $testMatch == 'topicCategories' or $testMatch == 'inspireCategories') ){ 
		//echo 'type: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>type</b> is not valid (keywords,topicCategories,inspireCategories).<br/>'; 
		die(); 		
 	}
	$type = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
	$testMatch = $_REQUEST["outputFormat"];	
 	if (!($testMatch == 'html' or $testMatch == 'json')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>outputFormat</b> is not valid (html or json).<br/>'; 
		die(); 		
 	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["scale"]) & $_REQUEST["scale"] != "") {
	$testMatch = $_REQUEST["scale"];	
 	if (!($testMatch == 'linear' or $testMatch == 'absolute')){ 
		//echo 'scale: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>scale</b> is not valid (linear, absolute).<br/>'; 
		die(); 		
 	}
	$scale = $testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["maxObjects"]) & $_REQUEST["maxObjects"] != "") {
	$testMatch = $_REQUEST["maxObjects"];	
 	if (!(($testMatch == '10') or ($testMatch == '15') or ($testMatch == 20) or ($testMatch == '25') or ($testMatch == '30') or ($testMatch == '35'))){ 
		//echo 'maxObjects: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>maxObjects</b> is not valid (10,15,20,25,30,35).<br/>'; 
		die(); 		
 	}
	$maxObjects = (integer)$testMatch;
	$testMatch = NULL;
}
if (isset($_REQUEST["maxFontSize"]) & $_REQUEST["maxFontSize"] != "") {
	$testMatch = $_REQUEST["maxFontSize"];	
 	if (!(($testMatch == '10') or ($testMatch == '20') or ($testMatch == '30') or ($testMatch == '40'))){ 
		//echo 'maxFontSize: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>maxFontSize</b> is not valid (10,20,30,40).<br/>'; 
		die(); 		
 	}
	$maxFontSize = (integer)$testMatch;
	$testMatch = NULL;
}
//
if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
	//validate to wms, wfs
	$testMatch = $_REQUEST["languageCode"];	
 	if (!($testMatch == 'de' or $testMatch == 'en' or  $testMatch == 'fr')){ 
		//echo 'languageCode: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>languageCode</b> is not valid (de,fr,en).<br/>'; 
		die(); 		
 	}
	$languageCode = $testMatch;
	$testMatch = NULL;
}

/*
if (isset($_REQUEST["hostName"]) & $_REQUEST["hostName"] != "") {
	//validate to some hosts
	$testMatch = $_REQUEST["hostName"];	
 	if (!($testMatch == 'www.geoportal.rlp' or $testMatch == 'www.geoportal.rlp.de' or  $testMatch == 'www.gdi-rp-dienste3.rlp.de' or  $testMatch == '10.7.101.151' or $testMatch == '10.7.101.252' )){ 
		echo 'hostName: <b>'.$testMatch.'</b> is not a valid server of gdi-rp.<br/>'; 
		die(); 		
 	}
	$hostName = $testMatch;
	$testMatch = NULL;
}
*/
if (isset($_REQUEST["hostName"]) & $_REQUEST["hostName"] != "") {
	//validate to some hosts
	$testMatch = $_REQUEST["hostName"];	
	//look for whitelist in mapbender.conf
	$HOSTNAME_WHITELIST_array = explode(",",HOSTNAME_WHITELIST);
	if (!in_array($testMatch,$HOSTNAME_WHITELIST_array)) {
		echo "Requested <b>hostName</b> not in whitelist! Please control your mapbender.conf.";
		$e = new mb_notice("Whitelist: ".HOSTNAME_WHITELIST);
		$e = new mb_notice($testMatch." not found in whitelist!");
		die(); 	
	}
	$hostName = $testMatch;
	$testMatch = NULL;
}


if ($outputFormat == 'json'){
	$classJSON = new Mapbender_JSON;
}

if ($languageCode == 'en'){
	$pathToSearchScript = '/php/mod_callMetadata.php?languageCode=en&';
}



if ($type == 'keywords'){
	$sql = "select a.keyword, sum(a.count) from ("; 
	$sql .= "(select keyword, count(*) from keyword INNER JOIN  layer_keyword  ON (layer_keyword.fkey_keyword_id = keyword.keyword_id) GROUP BY keyword.keyword) union ";
	$sql .= "(select keyword, count(*) from keyword INNER JOIN  wmc_keyword  ON (wmc_keyword.fkey_keyword_id = keyword.keyword_id) GROUP BY keyword.keyword) union ";
	$sql .= "(select keyword, count(*) from keyword INNER JOIN  wfs_featuretype_keyword  ON (wfs_featuretype_keyword.fkey_keyword_id = keyword.keyword_id)";
	$sql .= " GROUP BY keyword.keyword)) as a WHERE a.keyword <> '' GROUP BY a.keyword ORDER BY sum DESC LIMIT $1";
	$showName = 'keyword';
}


if ($type == 'topicCategories' || $type == 'inspireCategories') {
	if ($type == 'topicCategories') { 
		$categoryFilter = "md_topic_category";
	} else {
		$categoryFilter = "inspire_category";
	}
	$sql = "select a.".$categoryFilter."_code_".$languageCode.", a.".$categoryFilter."_description_".$languageCode.", a.".$categoryFilter."_uri, a.".$categoryFilter."_id, a.".$categoryFilter."_symbol, sum(a.count) from (";
	$sql .= "(select ".$categoryFilter."_code_".$languageCode.",".$categoryFilter."_description_".$languageCode.",".$categoryFilter."_uri,".$categoryFilter."_id,".$categoryFilter."_symbol, count(*) from ".$categoryFilter." INNER JOIN  layer_".$categoryFilter."  ON (layer_".$categoryFilter.".fkey_".$categoryFilter."_id = ".$categoryFilter.".".$categoryFilter."_id) ";
	$sql .= " WHERE layer_".$categoryFilter.".fkey_layer_id IN (select layer_id from layer where layer_searchable = 1)";
	$sql .= " GROUP BY ".$categoryFilter.".".$categoryFilter."_code_".$languageCode.",".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " union ";
	$sql .= "(select ".$categoryFilter."_code_".$languageCode.",".$categoryFilter."_description_".$languageCode.",".$categoryFilter."_uri,".$categoryFilter."_id,".$categoryFilter."_symbol, count(*) from  ".$categoryFilter." INNER JOIN  wfs_featuretype_".$categoryFilter."  ON (wfs_featuretype_".$categoryFilter.".fkey_".$categoryFilter."_id = ".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " WHERE wfs_featuretype_".$categoryFilter.".fkey_featuretype_id IN (select featuretype_id from wfs_featuretype where featuretype_searchable = 1)";
	$sql .= " GROUP BY ".$categoryFilter.".".$categoryFilter."_code_".$languageCode.",".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " union ";
	$sql .= "(select ".$categoryFilter."_code_".$languageCode.",".$categoryFilter."_description_".$languageCode.",".$categoryFilter."_uri,".$categoryFilter."_id,".$categoryFilter."_symbol, count(*) from ".$categoryFilter." INNER JOIN  wmc_".$categoryFilter."  ON (wmc_".$categoryFilter.".fkey_".$categoryFilter."_id = ".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " WHERE wmc_".$categoryFilter.".fkey_wmc_serial_id IN (select wmc_serial_id from mb_user_wmc where wmc_public = 1)";		
	$sql .= " GROUP BY ".$categoryFilter.".".$categoryFilter."_code_".$languageCode.",".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " union ";
	$sql .= "(select ".$categoryFilter."_code_".$languageCode.",".$categoryFilter."_description_".$languageCode.",".$categoryFilter."_uri,".$categoryFilter."_id,".$categoryFilter."_symbol, count(*) from ".$categoryFilter." INNER JOIN  mb_metadata_".$categoryFilter."  ON (mb_metadata_".$categoryFilter.".fkey_".$categoryFilter."_id = ".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= " WHERE mb_metadata_".$categoryFilter.".fkey_metadata_id IN (select metadata_id from mb_metadata where searchable = true) ";
	$sql .= " GROUP BY ".$categoryFilter.".".$categoryFilter."_code_".$languageCode.",".$categoryFilter.".".$categoryFilter."_id)";
	$sql .= ") as a";
	$sql .= " WHERE a.".$categoryFilter."_code_".$languageCode." <> '' GROUP BY a.".$categoryFilter."_code_".$languageCode.", a.".$categoryFilter."_description_".$languageCode.", a.".$categoryFilter."_uri, a.".$categoryFilter."_id, a.".$categoryFilter."_symbol ";
 		$sql .= "ORDER BY ";
	if ($orderBy != "") {
		$sql .= "sum";
	}

	$sql .= " DESC LIMIT $1";
	$showName = $categoryFilter.'_code_'.$languageCode;
}
//$e = new mb_exception($sql);

$v = array($maxObjects);
$t = array('i');
$res = db_prep_query($sql,$v,$t);
$tags = array();
$i = 0;
//max pixelsize

$inc = ($maxFontSize-$minFontSize)/$maxObjects;//maybe 10 or 5 or ...
$maxWeight = 0;

while($row = db_fetch_array($res)){
	if ((integer)$row['sum'] >= $maxWeight ) {
		$maxWeight = (integer)$row['sum'];
	} 
	if ($type == 'topicCategories') {
		$tags[$i] = array('weight'  =>$row['sum'], 'tagname' =>$row[$showName], 'url'=>MAPBENDER_PATH.$pathToSearchScript.'searchText=*&resultTarget=webclient&searchResources=dataset&resolveCoupledResources=true&outputFormat=json&isoCategories='.$row['md_topic_category_id'].'&languageCode='.$languageCode,'description'=>$row[$categoryFilter.'_description_'.$languageCode], 'info'=>$row[$categoryFilter.'_uri'], 'mbId'=>$row[$categoryFilter.'_id'], 'symbol'=>$row[$categoryFilter.'_symbol']);
	}
	if ($type == 'inspireCategories') {
		$tags[$i] = array('weight'  =>$row['sum'], 'tagname' =>$row[$showName], 'url'=>MAPBENDER_PATH.$pathToSearchScript.'searchText=*&resultTarget=webclient&searchResources=dataset&resolveCoupledResources=true&outputFormat=json&inspireCategories='.$row[$categoryFilter.'_id'].'&languageCode='.$languageCode, 'description'=>$row[$categoryFilter.'_description_'.$languageCode], 'info'=>$row[$categoryFilter.'_uri'], 'mbId'=>$row[$categoryFilter.'_id']);
	}

	if ($type == 'keywords') {
		$tags[$i] = array('weight'  =>$row['sum'], 'tagname' =>$row[$showName], 'url'=>MAPBENDER_PATH.$pathToSearchScript.'searchText='.$row[$showName].'&resultTarget=webclient&searchResources=dataset&resolveCoupledResources=true&outputFormat=json&languageCode='.$languageCode);
	}

	$i++;
}
//normalize the tag cloud with some max value for pixelsize or set them to linear scale!

for($i=0; $i<count($tags); $i++){
	switch ($scale) {
		case "linear":
			$tags[$i]['weight'] = $maxFontSize-($i*$inc);
			break;
		case "absolute":
			break;
		default:
			$tags[$i]['weight'] = $tags[$i]['weight']*$maxFontSize/$maxWeight;
			break;
	}
}

if ($outputFormat == 'html'){
	echo "<html>";
	echo "<title>Mapbender Tag Cloud</title>";
	echo "<style type=\"text/css\">";
	echo "#tagcloud{";
		echo "color: #dda0dd;";
		echo "font-family: Arial, verdana, sans-serif;";
		echo "width:650px;";
		echo "border: 1px solid black;";
		echo "text-align: center;";
	echo "}";

	echo "#tagcloud a{";
	echo "      color: #871e32;";
	echo "      text-decoration: none;";
	echo "      text-transform: capitalize;";
	echo "}";
	echo "</style>";
	echo "<body>";
	echo "</body>";
	echo "</html>";
	echo "<div id=\"tagcloud\">";
	/*** create a new tag cloud object ***/
	$tagCloud = new tagCloud($tags);
	echo $tagCloud -> displayTagCloud();
	echo "</div>";
	echo "</body>";
	echo "</html>";
}

if ($outputFormat == 'json'){
	$tagCloudJSON = new stdClass;
	$tagCloudJSON->tagCloud = (object) array(
		'maxFontSize' => $maxFontSize, 
		'maxObjects' => $maxObjects,
		'tags' => array()
	);
	//shuffle($tags); - only for html view - not for json!
	for($i=0; $i<count($tags);$i++){
    		$tagCloudJSON->tagCloud->tags[$i]->title = $tags[$i]['tagname'];
		$tagCloudJSON->tagCloud->tags[$i]->url = $tags[$i]['url'];
		$tagCloudJSON->tagCloud->tags[$i]->weight = $tags[$i]['weight'];
		$tagCloudJSON->tagCloud->tags[$i]->id = $tags[$i]['mbId'];
		//$tagCloudJSON->tagCloud->tags[$i]->symbol = $tags[$i]['symbol'];

		//if ($type == 'inspireCategories') {
                switch ($type) {
                    case "inspireCategories":
			$tagCloudJSON->tagCloud->tags[$i]->info = $tags[$i]['info'];		
			$tagCloudJSON->tagCloud->tags[$i]->inspireThemeId = end(explode('/', $tagCloudJSON->tagCloud->tags[$i]->info));
			$tagCloudJSON->tagCloud->tags[$i]->description = $tags[$i]['description'];
			//symbol
			//$tagCloudJSON->tagCloud->tags[$i]->symbolUrl = MAPBENDER_PATH."/img/INSPIRE-themes-icons-master/svg/".$tagCloudJSON->tagCloud->tags[$i]->inspireThemeId.".svg";
			$symbolFilePath = dirname(__FILE__)."/../img/INSPIRE-themes-icons-master/svg/".$tagCloudJSON->tagCloud->tags[$i]->inspireThemeId."_simple.svg";
			$tagCloudJSON->tagCloud->tags[$i]->inlineSvg = file_get_contents($symbolFilePath);
			$tagCloudJSON->tagCloud->tags[$i]->keepColor = true;
			break;
	 	    case "topicCategories":
			$tagCloudJSON->tagCloud->tags[$i]->info = $tags[$i]['info'];
			//$tagCloudJSON->tagCloud->tags[$i]->inspireThemeId = end(explode('/', $tagCloudJSON->tagCloud->tags[$i]->info));
			$tagCloudJSON->tagCloud->tags[$i]->description = $tags[$i]['description'];
			//symbol
			//$tagCloudJSON->tagCloud->tags[$i]->symbolUrl = MAPBENDER_PATH."/img/ISOTopicThemes/". $tags[$i]['symbol'].".svg";
			$symbolFilePath = dirname(__FILE__)."/../img/ISOTopicThemes/". $tags[$i]['symbol'].".svg";
			$tagCloudJSON->tagCloud->tags[$i]->inlineSvg = file_get_contents($symbolFilePath);
			$tagCloudJSON->tagCloud->tags[$i]->keepColor = false;
			break;
		}
   	 }
#echo "json";
	$tagCloudJSON = $classJSON->encode($tagCloudJSON);
	header("Content-Type: application/json");
	echo $tagCloudJSON;
}

class tagCloud{

/*** the array of tags ***/
private $tagsArray;


public function __construct($tags){
 /*** set a few properties ***/
 $this->tagsArray = $tags;
}

/**
 *
 * Display tag cloud
 *
 * @access public
 *
 * @return string
 *
 */
public function displayTagCloud(){
 $ret = '';
 shuffle($this->tagsArray);
 foreach($this->tagsArray as $tag)
    {
    $ret.='<a style="font-size: '.$tag['weight'].'px;" href="'.$tag['url'].'" title="'.$tag['tagname'].'">'.$tag['tagname'].'</a>'."\n";
    }
 return $ret;
}


} /*** end of class ***/






?>
