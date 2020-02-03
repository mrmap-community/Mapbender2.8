<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");

//GET parameter: searchText, maxResults
$maxResults = 10;
$timeBegin = microtime(1000000);
//validate parameter
if (isset($_REQUEST["searchText"]) & $_REQUEST["searchText"] != "") {
	$test="(SELECT\s[\w\*\)\(\,\s]+\sFROM\s[\w]+)| (UPDATE\s[\w]+\sSET\s[\w\,\'\=]+)| (INSERT\sINTO\s[\d\w]+[\s\w\d\)\(\,]*\sVALUES\s\([\d\w\'\,\)]+)| (DELETE\sFROM\s[\d\w\'\=]+)";
	//validate to csv integer list
	$testMatch = $_REQUEST["searchText"];
	$pattern = '/(\%27)|(\')|(\-\-)|(\")|(\%22)/';		
 	if (preg_match($pattern,$testMatch)){
		//echo 'searchText: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>searchText</b> is not valid.<br/>'; 
		die(); 		
 	}
	$searchText = $testMatch;
        $searchText = str_replace('<','{<}',$searchText);
 	$searchText = str_replace('>','{>}',$searchText);
	$testMatch = NULL;
	if ($searchText ==='false') {
		$searchText ='*';
	}
}
if (isset($_REQUEST["maxResults"]) & $_REQUEST["maxResults"] != "") {
	//validate integer to 100 - not more
	$testMatch = $_REQUEST["maxResults"];
	//give max 99 entries - more will be to slow
	$pattern = '/^([0-9]{0,1})([0-9]{1})$/';		
 	if (!preg_match($pattern,$testMatch)){ 
		//echo 'maxResults: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>maxResults</b> is not valid (integer < 99).<br/>'; 
		die(); 		
 	}
	$maxResults = (integer)$testMatch;
	$testMatch = NULL;
	if ($maxResults > 25) {
		echo 'Parameter <b>maxResults</b> is not valid (integer < 25).<br/>'; 
		die();
	}
}
//do the sql select
/*$sql = "SELECT keyword FROM keyword_search_view WHERE keyword_ts @@ to_tsquery('german', $1) || keyword_upper LIKE $2 ORDER BY keyword LIMIT $3";
$t = array('s', 's', 'i');
//$normSearch = str_replace("ä","AE",strtoupper($searchText));*/

$normSearch = str_replace('ß', 'SS', str_replace('Ü', 'UE', str_replace('Ä', 'AE', strtoupper(str_replace('Ö', 'OE', mb_strtoupper($searchText))))));

$sql = "SELECT keyword, keyword_upper FROM keyword_search_view WHERE keyword_upper LIKE $1 ORDER BY keyword LIMIT $2";
$t = array('s', 'i');
//$e = new mb_exception($normSearch);
//$v = array($searchText, $normSearch."%", $maxResults);

$v = array("%".$normSearch."%", $maxResults);
$resultList = array();
$res = db_prep_query($sql,$v,$t);

header('Content-type: application/json; charset=utf-8');
$i = 0;
while($row = db_fetch_array($res)){
	$resultList[$i]['keyword'] = trim($row['keyword']);
	//find pos of searchText in keyword - lowercase
	$posOfString = strpos(mb_strtolower($row['keyword_upper']), mb_strtolower($searchText));
	$lengthOfSearchtext = strlen($searchText);
//$e = new mb_exception($lengthOfSearchtext);
//$e = new mb_exception(gettype($searchText));	
	$lengthOfKeyword = count($row['keyword']);
	$resultList[$i]['keywordHigh'] = trim(substr($row['keyword'], 0, $posOfString)."<b>".substr($row['keyword'], $posOfString, $lengthOfSearchtext)."</b>".substr($row['keyword'], ($posOfString + $lengthOfSearchtext)));
	//$resultList[$i]['keywordHigh'] = str_replace($searchText, "<b>".$searchText."</b>", $row['keyword']);
	$i++;
}
//
$ids = array_column($resultList, 'keyword');
$ids = array_unique($ids);
$resultList = array_filter($resultList, function ($key, $value) use ($ids) {
    return in_array($value, array_keys($ids));
}, ARRAY_FILTER_USE_BOTH);

$result->results = $i;
$timeEnd = microtime(1000000);
$timeDiff = $timeEnd - $timeBegin;
$result->time = $timeDiff;
$result->resultList = $resultList;
echo json_encode($result);
?>
