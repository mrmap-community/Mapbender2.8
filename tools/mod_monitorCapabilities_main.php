<?php
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

require_once dirname(__FILE__) ."/../core/globalSettings.php";
require_once dirname(__FILE__) ."/../http/classes/class_administration.php";
require_once dirname(__FILE__) ."/../tools/mod_monitorCapabilities_defineGetMapBbox.php";
require_once dirname(__FILE__) ."/../http/classes/class_bbox.php";

//require_once dirname(__FILE__) ."/../http/classes/class_universal_wfs_factory.php";
//require_once(dirname(__FILE__)."/../http/classes/class_mb_exception.php");
$wfsToExclude = array();
$wmsToExclude = array();

if (file_exists ( dirname ( __FILE__ ) . "/../conf/excludeFromMonitoring.json" )) {
	$configObject = json_decode ( file_get_contents ( "../conf/excludeFromMonitoring.json" ) );
}
if (isset ( $configObject ) && isset ( $configObject->wms ) && count($configObject->wms) > 0 ) {
	$wmsToExclude = $configObject->wms;
}
if (isset ( $configObject ) && isset ( $configObject->wfs ) && count($configObject->wfs) > 0 ) {
	$wfsToExclude = $configObject->wfs;
}

//do db close at the most reasonable point 
$admin = new administration();
$user = null;
$group = null;
$application = null;
//commandline
$cl = false;

//for debugging purposes only
	function logit($text){
	 	if($h = fopen("/tmp/class_monitoring_capabilities.log","a")){
					$content = $text .chr(13).chr(10);
					if(!fwrite($h,$content)){
						#exit;
					}
					fclose($h);
				}
	 	
	 }


function getTagsOutOfXML($reportFile,$tagsToReturn, $serviceType="wms") {
	if (file_exists($reportFile)) {
		$xml = simplexml_load_file($reportFile);
		if ($xml == false) {
			//$e = new mb_exception("/tools/mod_monitorCapabilities.php: could not open file: ".$reportFile);
		}
		foreach($tagsToReturn as $tagName) {
			$result[$tagName] = (string)$xml->{$serviceType}->$tagName;
		}
	} else {
		//$e = new mb_exception("/tools/mod_monitorCapabilities.php: could not find file: ".$reportFile);
	}
	return $result;
}
// invocation *************************************************************************************
// commandline
if ($_SERVER["argc"] > 0) {
	$cl = true;
	if ($_SERVER["argc"] > 1 && $_SERVER["argv"][1] !== "") {
		$param = $_SERVER["argv"][1];
		if (substr($param, 0,5) == "user:") {
			$user = substr($param, 5);
		}
		if (substr($param, 0,6) == "group:") {
			$group = substr($param, 6);
		}
	}
	else {
		echo _mb("Specify a user ID or a group ID to monitor.") . "\n\n";
		echo "php <script name> user:<user_id> \n\n";
		echo "php <script name> group:<group_id> \n\n";
		die;
	}
}
//browser
else if ($_GET['user'] || $_GET['group'] || $_GET['app']) {
	$user = $_GET['user'] ? intval($_GET['user']) : null;
	$group = $_GET['group'] ? intval($_GET['group']) : null;
}
else {
	echo _mb("Please specify a user ID or a group ID!") . " ";
	echo _mb("You can pass the GET arguments 'user' or 'group'!");
	die;
}

$br = "<br><br>";
if ($cl) {
	$br = "\n\n";
}

//$e = new mb_notice("mod_monitorCapabilities_main.php: group: ".$group);

$userIdArray = array();

//loop for doing the monitor for all registrating institutions ****************
if (!is_null($group)) {
	echo "monitoring " . $group;
	if (!is_numeric($group)) {
		echo _mb("Parameter 'group' must be numeric.");
		die;
	}
	//read out user who are subadmins - only their services are controlled - til now
	$sql = "SELECT DISTINCT fkey_mb_user_id FROM mb_user_mb_group WHERE " . 
		"fkey_mb_group_id = (SELECT mb_group_id FROM mb_group WHERE " . 
		"mb_group_id = $1)";
	$v = array($group);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	$userIdArray = array();
	while ($row = db_fetch_array($res)) {
		$userIdArray[] = $row["fkey_mb_user_id"];
	}
}
else if (!is_null($user)) {
	if (!is_numeric($user)) {
		echo _mb("Parameter 'user' must be numeric.");
		die;
	}
	$userIdArray = array($user);
}
else {
	if ($_SERVER["argc"] > 0) {
		echo _mb("Specify a user ID or a group ID to monitor.") . "\n\n";
		echo "php <script name> user:<user_id> \n\n";
		echo "php <script name> group:<group_id> \n\n";
	}
	else {
		echo _mb("Please specify a user ID or a group ID!") . " ";
		echo _mb("You can pass the GET arguments 'user' or 'group'!");
	}
	die;
}

if (count($userIdArray) === 0) {
	echo _mb("No user found for the given parameters.");

	die;
}
// invocation *************************************************************************************

$user_id_all = $userIdArray;
echo $br ."Count of registrating users: " . count($user_id_all) . $br;
//$e = new mb_exception("mod_monitorCapabilities_main.php: count of group members: ".count($user_id_all));
//delete all temporary files (.xml and .png) from last monitoring
array_map('unlink', glob(dirname(__FILE__)."/tmp/*.xml"));
array_map('unlink', glob(dirname(__FILE__)."/tmp/*.png"));
// loop for serviceType
//define service types which should be monitored
$serviceTypes = array('WMS','WFS');
//for testing

//$serviceTypes = array('WMS');
foreach ($serviceTypes as $serviceType) {

$time_array = array();

for ($iz = 0; $iz < count($user_id_all); $iz++) {
    //$e = new mb_exception("/tools/mod_monitorCapabilities_main.php: - initialize monitoring for userid: ".$user_id_all[$iz]);
    //foreach ($serviceTypes as $serviceType) {
	$userid = $user_id_all[$iz];
	//get all owned services
	switch ($serviceType) {
		case "WMS":
			$service_id_own = $admin->getWmsByWmsOwner($userid);
			//remove services not to monitor
			$service_id_own = array_diff($service_id_own, $wmsToExclude);
			break;
		case "WFS":
			$service_id_own = $admin->getWfsByWfsOwner($userid);
			//remove services not to monitor
			$service_id_own = array_diff($service_id_own, $wfsToExclude);
			break;
	}
	
	//initialize monitoring processes
	echo "Starting monitoring cycle...$br";
	echo $serviceType." services are requested for availability.$br"; 
	echo "Capabilities documents are requested and compared to the infos in the service db.$br";
	//$e = new mb_notice("mod_monitorCapabilities_main.php: monitoring for user: ".$userid);

	//new: time user-monitoring cycle must stored in array
	$time_array[$userid] = strval(time());
	//wait 2 seconds to give enough time between to different users the time can differ also for one user!
	sleep(1);
	$time = $time_array[$userid];
	for ($k=0; $k<count($service_id_own); $k++) {
		//get relevant data out of registry
		switch ($serviceType) {
			case "WMS":
				$sql = "SELECT wms_upload_url as service_upload_url, wms_getcapabilities_doc as service_capabilities_doc, " . 
				"wms_version as service_version, wms_getcapabilities as service_getcapabilities, wms_getmap as service_getcontent  FROM wms " . 
				"WHERE wms_id = $1";
				break;
			case "WFS":
				$sql = "SELECT wfs_upload_url as service_upload_url, wfs_getcapabilities_doc as service_capabilities_doc, " . 
				"wfs_version as service_version, wfs_getcapabilities as service_getcapabilities, wfs_getfeature as service_getcontent FROM wfs " . 
				"WHERE wfs_id = $1";
				break;
		}
		$v = array($service_id_own[$k]);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		$someArray = db_fetch_array($res);
		$url = $someArray['service_upload_url'];
		$capDoc = $someArray['service_capabilities_doc'];
		$version = $someArray['service_version'];
		$capabilities = $someArray['service_getcapabilities'];
		$getcontent = $someArray['service_getcontent'];
		switch ($serviceType) {
			case "WMS":
				$getMapUrl = getMapRequest($service_id_own[$k], $version, $getcontent);
				break;
		}
		// for the case when there is no upload url - however - we need the 
		// url to the capabilities file - depends on the service type
   		if (!$url || $url == "") {
			$capabilities=$admin->checkURL($capabilities);
			switch ($serviceType) {
				case "WMS":
					if ($version == "1.0.0" ) {
						$url = $capabilities . "REQUEST=capabilities&WMTVER=1.0.0";
					} else {
						$url = $capabilities . "REQUEST=GetCapabilities&" . 
							"SERVICE=WMS&VERSION=" . $version;
					}
					break;
				case "WFS":
					$url = $capabilities . "REQUEST=GetCapabilities&" . 
							"SERVICE=WFS&VERSION=" . $version;
					break;
			}		
   		}
		//$url is the url to the service which should be monitored in this cycle
		//initialize monitoriung in db (set status=-2)
		echo "initialize monitoring for user: " . $userid . 
			" ".$serviceType.": " . $service_id_own[$k] . $br;
		//$e = new mb_exception("/tools/mod_monitorCapabilities_main.php: ".$serviceType.": ".$service_id_own[$k]);
		switch ($serviceType) {
			case "WMS":
				$sql = "INSERT INTO mb_monitor (upload_id, fkey_wms_id, " . 
					"status, status_comment, timestamp_begin, timestamp_end, " . 
					"upload_url, updated)";
				$sql .= "VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";

				$v = array(
					$time,
					$service_id_own[$k],
					"-2",
					"Monitoring is still in progress...", 
					time(),
					"0",
					$url,
					"0"
				);
				$t = array('s', 'i', 's', 's', 's', 's', 's', 's');
				$res = db_prep_query($sql,$v,$t);
				break;
			case "WFS":
				$sql = "INSERT INTO mb_monitor (upload_id, fkey_wfs_id, " . 
					"status, status_comment, timestamp_begin, timestamp_end, " . 
					"upload_url, updated)";
				$sql .= "VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";

				$v = array(
					$time,
					$service_id_own[$k],
					"-2",
					"Monitoring is still in progress...", 
					time(),
					"0",
					$url,
					"0"
				);
				$t = array('s', 'i', 's', 's', 's', 's', 's', 's');
				$res = db_prep_query($sql,$v,$t);
				break;
		}

		// Decode orig capabilities out of db cause they are converted before 
		// saving them while upload
		//$capDoc=$admin->char_decode($capDoc);
		// do the next to exchange the update before by another behavior! - 
		// look in class_monitor.php !
		$currentFilename = strtolower($serviceType)."_monitor_report_" . $time . "_" . 
			$service_id_own[$k] . "_" . $userid . ".xml";
 		$report = fopen(dirname(__FILE__)."/tmp/".$currentFilename,"a");
		//$e = new mb_notice("mod_monitorCapabilities_main.php: currentFilename: ".dirname(__FILE__)."/tmp/".$currentFilename);
		$lb = chr(13).chr(10);

		fwrite($report,"<monitorreport>".$lb);
		switch ($serviceType) {
			case "WMS":
				fwrite($report,"<wms>".$lb);
				fwrite($report,"<wms_id>".$service_id_own[$k]."</wms_id>".$lb);
				break;
			case "WFS":
				fwrite($report,"<wfs>".$lb);
				fwrite($report,"<wfs_id>".$service_id_own[$k]."</wfs_id>".$lb);
				break;
		}
		fwrite($report,"<upload_id>".$time."</upload_id>".$lb);
		fwrite($report,"<getcapbegin></getcapbegin>".$lb);
		fwrite($report,"<getcapurl>".urlencode($url)."</getcapurl>".$lb);
		fwrite($report,"<getcapdoclocal>".urlencode($capDoc)."</getcapdoclocal>".$lb);
		fwrite($report,"<getcapdocremote></getcapdocremote>".$lb);
		fwrite($report,"<getcapdiff></getcapdiff>".$lb);
		fwrite($report,"<getcapend></getcapend>".$lb);
		fwrite($report,"<getcapduration></getcapduration>".$lb);
		switch ($serviceType) {
			case "WMS":
				fwrite($report,"<getmapurl>".urlencode($getMapUrl)."</getmapurl>".$lb);
				break;
			case "WFS":
				//get list of featuretype names and other information
				/*$wfsFactory = new UniversalWfsFactory();
				$wfs = $wfsFactory->createFromDb($service_id_own[$k]);
				foreach($wfs->featureTypeArray as $featureType) {
					$e = new mb_exception("ft name: ".$featureType->name);
					//$feature = $wfs->getFeature($featureType->name, null, null, null, null, 1);
					//$e = new mb_exception($feature);
				}*/
				fwrite($report,"<feature_content></feature_content>".$lb);
				break;
		}
		fwrite($report,"<status>-2</status>".$lb);
		switch ($serviceType) {
			case "WMS":
				fwrite($report,"<image></image>".$lb);
				break;
			case "WFS":
				
				break;
		}
		fwrite($report,"<comment>Monitoring in progress...</comment>".$lb);
		fwrite($report,"<timeend></timeend>".$lb);
		switch ($serviceType) {
			case "WMS":
				fwrite($report,"</wms>".$lb);
				break;
			case "WFS":
				fwrite($report,"</wfs>".$lb);
				break;
		}
		fwrite($report,"</monitorreport>".$lb);
		fclose($report);
		// start of the monitoring processes on shell 
		// (maybe problematic for windows os)
		//$e = new mb_notice("mod_monitorCapabilities_main.php: php call: ".$exec);
		if (defined("CAP_MONITORING_WAITSTATE") && CAP_MONITORING_WAITSTATE !== "") {
			usleep((integer)CAP_MONITORING_WAITSTATE);
		}
		switch ($serviceType) {
			case "WMS":
				$exec = PHP_PATH . "php " . dirname(__FILE__) . "/mod_monitorCapabilities_write.php " . 
					$currentFilename ." ".$serviceType." 0 > /dev/null &";
				/*
		 		* @security_patch exec done
		 		* Added escapeshellcmd()
		 		*/
   				#exec(escapeshellcmd($exec));TODO what goes wrong here?
				exec($exec);
				break;
			case "WFS":
				$exec = PHP_PATH . "php " . dirname(__FILE__) . "/mod_monitorCapabilities_write.php " . 
					$currentFilename ." ".$serviceType." 0 > /dev/null &";
				exec($exec);
				break;
		}
	}
	echo "Monitoring start cycle for user: ".$userid." has ended. " . 
		"(Altogether: " . count($service_id_own) . " ".$serviceType." monitorings started).$br";

    //}
}
//set time limit (mapbender.conf)
set_time_limit(TIME_LIMIT);
// wait until all monitoring processes are finished - each single monitoring has the same limit!!!!
echo "please wait " . TIME_LIMIT . " seconds for the monitoring to finish...$br";
sleep(TIME_LIMIT);
//when time limit has ended: begin to collect results for every registrating user
for ($iz = 0; $iz < count($user_id_all); $iz++) {
    //logit("/tools/mod_monitorCapabilities.php - collect info from xml for user: ".$user_id_all[$iz]);
    //$e = new mb_exception("/tools/mod_monitorCapabilities.php - collect info from xml for user: ".$user_id_all[$iz]);
    //loop for serviceType - reinitialize all things from earlier serviceType
    //foreach ($serviceTypes as $serviceType) {
	// when time limit has ended: begin to collect results for every 
	// registrating user
	$problemOWS = array();//define array with id's of problematic wms
	$commentProblemOWS = array();
	$userid = $user_id_all[$iz];
	//get the old upload_id from the monitoring to identify it in the database
	$time = $time_array[$userid];	
	//get all owned services
	
	switch ($serviceType) {
		case "WMS":
			$service_id_own = $admin->getWmsByWmsOwner($userid);
			$tagsToReturn = array("status", "comment", "getcapdiff", "image", "getcapbegin", "getcapend", "getmapurl");
			break;
		case "WFS":
			$service_id_own = $admin->getWfsByWfsOwner($userid);
			$tagsToReturn = array("status", "comment", "getcapdiff", "getcapbegin", "getcapend", "feature_content");
			break;
	}
	for ($k = 0; $k < count($service_id_own); $k++) {
		$monitorFile = dirname(__FILE__)."/tmp/".strtolower($serviceType)."_monitor_report_" . $time . "_" . 
			$service_id_own[$k] . "_".$userid.".xml";
		
		$tags = getTagsOutOfXML($monitorFile, $tagsToReturn, strtolower($serviceType));
		$status = $tags['status'];
		$status_comment = $tags['comment'];
		$cap_diff = $tags['getcapdiff'];
		$timestamp_begin = $tags['getcapbegin'];
		$timestamp_end = $tags['getcapend'];
		//logit("try to update ".$serviceType." with id ".$service_id_own[$k]);
		switch ($serviceType) {
			case "WMS":
				$map_url = rawurldecode($tags['getmapurl']);
				$image = rawurldecode($tags['image']);
				break;
			case "WFS":
				//TODO
				$feature_content = $tags['feature_content'];
				break;
		}
		switch ($serviceType) {
			case "WMS":
				$sql = "UPDATE mb_monitor SET updated = $1, status = $2, " . 
					"image = $3, status_comment = $4, timestamp_end = $5, " . 
					"map_url = $6 , timestamp_begin = $7, cap_diff = $8 " . 
					"WHERE upload_id = $9 AND fkey_wms_id=$10 ";

				// check if status = -2 return new comment and status -1, 
				// push into problematic array
				if ($status == '-1' or $status == '-2') {
					$status_comment = "Monitoring process timed out.";
					$status = '-1';
					array_push($problemOWS,$service_id_own[$k]);
					array_push($commentProblemOWS,$status_comment);
				} 
				$v = array(
					'0', 
					intval($status), 
					intval($image), 
					$status_comment, 
					(string)intval($timestamp_end), 
					$map_url, 
					(string)intval($timestamp_begin), 
					$cap_diff,
					(string)$time, 
					$service_id_own[$k]
				);
				$t = array('s', 'i', 'i', 's', 's', 's', 's', 's','s','s');
				$res = db_prep_query($sql,$v,$t);
				break;
			case "WFS":
				$sql = "UPDATE mb_monitor SET updated = $1, status = $2, " . 
					"feature_content = $3, status_comment = $4, timestamp_end = $5, " . 
					"feature_urls = $6 , timestamp_begin = $7, cap_diff = $8 " . 
					"WHERE upload_id = $9 AND fkey_wfs_id=$10 ";

				// check if status = -2 return new comment and status -1, 
				// push into problematic array
				if ($status == '-1' or $status == '-2') {
					$status_comment = "Monitoring process timed out.";
					$status = '-1';
					array_push($problemOWS,$service_id_own[$k]);
					array_push($commentProblemOWS,$status_comment);
				} 
				$v = array(
					'0', 
					intval($status), 
					$feature_content, 
					$status_comment, 
					(string)intval($timestamp_end), 
					"feature_urls - json", 
					(string)intval($timestamp_begin), 
					$cap_diff,
					(string)$time, 
					$service_id_own[$k]
				);
				$t = array('s', 'i', 's', 's', 's', 's', 's', 's','s','s');
				$res = db_prep_query($sql,$v,$t);
				break;
		}
	}
	$body = "";
	echo "\nmonitoring info in db for user: ".$userid."\n";
	//loop for single monitor requests that has problems
	for ($i=0; $i < count($problemOWS); $i++) {
		switch ($serviceType) {
			case "WMS":
				$body .= $br . $admin->getWmsTitleByWmsId($problemOWS[$i]) . 
					" (" . $problemOWS[$i] . "): " . $commentProblemOWS[$i] . $br;
				break;
			case "WFS":
				$body .= $br . $admin->getWfsTitleByWfsId($problemOWS[$i]) . 
					" (" . $problemOWS[$i] . "): " . $commentProblemOWS[$i] . $br;
				break;
		}
	}
	unset($problemOWS);
	unset($commentProblemOWS);
	//end of loop for single monitor requests
	// Send an email to the user if body string exists
	if ($body) {
		$error_msg = "";
		if ($admin->getEmailByUserId($userid)) {
			$admin->sendEmail(
				MAILADMIN, 
				MAILADMINNAME, 
				$admin->getEmailByUserId($userid), 
				$user, 
				"Mapbender monitoring report " . date("F j, Y, G:i:s", $time), 
				utf8_decode($body), 
				$error_msg
			);
		}
		else {
			$error_msg = "Email address of user '" . 
				$admin->getUserNameByUserId($userid) . 
				"' unknown!\n";
		}
		if ($error_msg) {
			echo "\n ERROR: " . $error_msg;
		}
	}
    //}
}
}
?>
