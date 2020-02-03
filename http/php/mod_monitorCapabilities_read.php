<?php
# $Id: mod_monitorCapabilities_read.php 1283 2007-10-25 15:20:25Z baudson $
# http://www.mapbender.org/index.php/Monitor_Capabilities
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
$serviceType = "WMS";
$e_id = "monitor_results";
$gui_id = $_REQUEST["guiID"];
//validate parameters
if (isset($_REQUEST["serviceType"]) & $_REQUEST["serviceType"] != "") {
	$testMatch = $_REQUEST["serviceType"];	
 	if (!($testMatch == 'wms' or $testMatch == 'wfs')){ 
		//echo 'outputFormat: <b>'.$testMatch.'</b> is not valid.<br/>'; 
		echo 'Parameter <b>serviceType</b> is not valid (wms, wfs).<br/>'; 
		die(); 		
 	}
	$serviceType = $testMatch;
	$testMatch = NULL;
}
$serviceType= strtoupper($serviceType);
//TODO: Check wether request parameters cannot be found! Since this is not handled, update will not be available!
require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");
require_once(dirname(__FILE__)."/../classes/class_wms.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
<!--<link type="text/css" href="../extensions/jquery-ui-1.8.16.custom/css/ui-lightness/jquery-ui-1.8.16.custom.css" rel="Stylesheet" />-->
<link type="text/css" href="../extensions/jquery-ui-1.12.1.custom/jquery-ui.min.css" rel="Stylesheet" />
<link type="text/css" href="../extensions/jquery-ui-1.12.1.custom/jquery-ui.structure.min.css" rel="Stylesheet" />
<link type="text/css" href="../extensions/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css" rel="Stylesheet" />
</head>
<body>
<script src="../extensions/jquery-1.12.0.min.js"></script>
<!--<script src="../extensions/jquery-ui-1.8.16.custom/js/jquery-ui-1.8.16.custom.min.js"></script>-->
<script src="../extensions/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>

<!--<script src="https://code.jquery.com/ui/1.11.3/jquery-ui.min.js"
			  integrity="sha256-xI/qyl9vpwWFOXz7+x/9WkG5j/SVnSw21viy8fWwbeE="
			  crossorigin="anonymous"></script>-->
<script>
		window.onload = function() {
				// add option to open metadata windows in a modal dialog
				$(".modalDialog").click(function (e) {
					var iframe = $('<iframe width="100%" height="100%" frameborder="0" scrolling="yes" style="min-width: 95%;height:100%;"></iframe>');
					iframe.attr('src', String($(this).attr('url')));
    					var dialog = $("<div title=''metadata'></div>").append(iframe).dialog({
        					//autoOpen: true,
        					modal: true,
        					resizable: true,
        					width: 650,
        					height: 400,
        					close: function () {
            						iframe.attr("src", "");
        					}
    					});
				});
				// change style to link optic
				//$(".modalDialog").css("text-decoration", "underline");
				//$(".modalDialog").css("text-decoration-color", "blue");
				//$(".modalDialog").css("color", "blue");
				//$(".modalDialog").css("cursor", "pointer");
		};
</script>
<?php
$admin = new administration();
$user = new User();
switch ($serviceType) {
	case "WMS":
		$checkboxes = intval($_POST['cbs']);
		for ($i=0; $i < $checkboxes; $i++) {
			echo $i;
			if (!isset($_POST['cb'.$i]) || 
				!isset($_POST['upl_id'.$i])
			) {
				continue;
			}	
			$upd_wmsid = intval($_POST['cb'.$i]);
			$upload_id = intval($_POST['upl_id'.$i]);
			if ($upd_wmsid) {
				// get upload URL
				$sql = "SELECT wms_upload_url, wms_owner FROM wms WHERE wms_id = $1";
				$v = array($upd_wmsid);
						$t = array("i");
				$res = db_prep_query($sql, $v, $t);
				$row = db_fetch_array($res);
				$uploadUrl = $row["wms_upload_url"];
				$wmsOwner = $row["wms_owner"];
				if ($wmsOwner !== $user->id) {
					echo "<br>Skipped: " . $upd_wmsid . "<br>";
					continue;
				}
						// update WMS from upload URL
						$mywms = new wms();
				$result = $mywms->createObjFromXML($uploadUrl);
						if (!$result['success']) {
			    		//echo $result['message']; //do nothing first - TODO give negative result!
					    		//die();
				} else {
					$mywms->optimizeWMS();
					echo "<br />";  
					if (!MD_OVERWRITE) {
								$mywms->overwrite=false;
					} 
					//possibility to see update information in georss and/or twitter channel
					if(empty($_POST['twitter_news'])) {
						$mywms->twitterNews = false;
					}
					if(empty($_POST['rss_news'])) {
						$mywms->setGeoRss = false;
					}	
					$mywms->updateObjInDB($upd_wmsid);
					echo "<br>Updated: " . $upd_wmsid . "<br>";
				}
				/*
				// start new monitoring for this WMS
				$now = time();
				$sql = "UPDATE mb_monitor SET status = '-2', status_comment = 'Monitoring is still in progress...', " . 
				"timestamp_begin = $1, timestamp_end = $2 WHERE upload_id = $3 AND fkey_wms_id = $4";
				$v = array($now, $now, $upload_id, $upd_wmsid);
				$t = array('s', 's', 's', 'i');
				$res = db_prep_query($sql,$v,$t);

				$currentFilename = "wms_monitor_report_" . $upload_id . "_" . 
				$upd_wmsid . "_" . $wmsOwner . ".xml";		
				$exec = PHP_PATH . "php5 ../../tools/mod_monitorCapabilities_write.php " . 
				$currentFilename. " 0";
				echo exec(escapeshellcmd($exec));
				*/
			}
			echo "<br>Please note: The updated services need to be monitored again in order to update the database.<br><br>";
		}
		break;
	case "WFS":
		$checkboxes = intval($_POST['cbs']);
		for ($i=0; $i < $checkboxes; $i++) {
			echo $i;
			if (!isset($_POST['cb'.$i]) || 
				!isset($_POST['upl_id'.$i])
			) {
				continue;
			}	
			$upd_wfsid = intval($_POST['cb'.$i]);
			$upload_id = intval($_POST['upl_id'.$i]);
			if ($upd_wfsid) {
				// get upload URL
				$sql = "SELECT wfs_upload_url, wfs_owner FROM wfs WHERE wfs_id = $1";
				$v = array($upd_wfsid);
				$t = array("i");
				$res = db_prep_query($sql, $v, $t);
				$row = db_fetch_array($res);
				$uploadUrl = $row["wfs_upload_url"];
				$wfsOwner = $row["wfs_owner"];
				if ($wfsOwner !== $user->id) {
					echo "<br>Skipped: " . $upd_wfsid . "<br>";
					continue;
				}
				$id = $upd_wfsid;
				$url = $uploadUrl;
				//get authentication information from db
				$sql = "SELECT wfs_auth_type, wfs_username, wfs_password from wfs WHERE wfs_id = $1 ";
				$v = array($id);
				$t = array('i');
				$res = db_prep_query($sql,$v,$t);
				$row = db_fetch_assoc($res);
				$auth['auth_type'] = $row["wfs_auth_type"];
				$auth['username'] = $row["wfs_username"];
				$auth['password'] = $row["wfs_password"];
				$wfsFactory = new UniversalWfsFactory();
				if ($auth['auth_type'] =='') {
					$auth = false;
				}
				$myWfs = $wfsFactory->createFromUrl($url, $auth);
				//if (!MD_OVERWRITE) {
				//if($obj->overwrite_md) {
				//	$myWfs->overwrite = true;
				//} else {
					$myWfs->overwrite=false;
				//}
				$myWfs->id = $id;

					echo "<br>Updated: " . $upd_wfsid . "<br>";
				/*
				// start new monitoring for this WMS
				$now = time();
				$sql = "UPDATE mb_monitor SET status = '-2', status_comment = 'Monitoring is still in progress...', " . 
				"timestamp_begin = $1, timestamp_end = $2 WHERE upload_id = $3 AND fkey_wms_id = $4";
				$v = array($now, $now, $upload_id, $upd_wmsid);
				$t = array('s', 's', 's', 'i');
				$res = db_prep_query($sql,$v,$t);

				$currentFilename = "wms_monitor_report_" . $upload_id . "_" . 
				$upd_wmsid . "_" . $wmsOwner . ".xml";		
				$exec = PHP_PATH . "php5 ../../tools/mod_monitorCapabilities_write.php " . 
				$currentFilename. " 0";
				echo exec(escapeshellcmd($exec));
				*/
			}
			echo "<br>Please note: The updated services need to be monitored again in order to update the database.<br><br>";
		}
		break;
}

//$e = new mb_exception("mod_monitorCapabilities_read.php: userId: ".$_SESSION["mb_user_id"]);
switch ($serviceType) {
	case "WMS":
		$sql = "SELECT mb_wms_availability.* FROM mb_wms_availability, wms " . 
			"WHERE mb_wms_availability.fkey_wms_id = wms.wms_id AND wms.wms_owner = $1";
		$res = db_prep_query($sql, array($_SESSION["mb_user_id"]), array("i"));
		break;
	case "WFS":
		$sql = "SELECT mb_wfs_availability.* FROM mb_wfs_availability, wfs " . 
			"WHERE mb_wfs_availability.fkey_wfs_id = wfs.wfs_id AND wfs.wfs_owner = $1";
		$res = db_prep_query($sql, array($_SESSION["mb_user_id"]), array("i"));
		break;
}


$wms = array();
$wms_id = array();

$wfs = array();
$wfs_id = array();

$upload_id = array();
$avg_response_time = array();
$comment = array();
$upload_url = array();
$updated = array();
$status = array();

while($row = db_fetch_array($res)){
	switch ($serviceType) {
		case "WMS":
			$serviceId = $row["fkey_wms_id"];
			$wms[] = $serviceId;
			$wms_id[$serviceId] = $serviceId;
			$mapurl[$serviceId] = $row["map_url"];
			$image[$serviceId] = $row["image"];
			break;
		case "WFS":
			$serviceId = $row["fkey_wfs_id"];
			$wfs[] = $serviceId;
			$wfs_id[$serviceId] = $serviceId;
			break;
	}
	$status[$serviceId] = $row["last_status"];
	$comment[$serviceId] = $row["status_comment"];
	$average_resp_time[$serviceId] = $row["average_resp_time"];
	$upload_url[$serviceId] = $row["upload_url"];
	$updated[$serviceId] = $row["fkey_upload_id"];
	$upload_id[$serviceId] = $row["fkey_upload_id"];
	$cap_diff[$serviceId] = $row["cap_diff"];
	$percentage[$serviceId] = $row["availability"];
	$total[$serviceId] = $row["monitor_count"];
}


$newArray = $status;
if ($_GET['sortby']) {
	if ($_GET['sortby'] == "wms") {
		$newArray = $wms_id;
		asort($newArray);
	}
	if ($_GET['sortby'] == "wfs") {
		$newArray = $wfs_id;
		asort($newArray);
	}
	elseif ($_GET['sortby'] == "status") {
		$newArray = $status;
		asort($newArray);
	}
	elseif ($_GET['sortby'] == "avgresp") {
		$newArray = $avg_response_time;
		asort($newArray);
	}
	elseif ($_GET['sortby'] == "avail") {
		$newArray = $percentage;
		arsort($newArray);
	}
	elseif ($_GET['sortby'] == "last") {
		$newArray = $upload_id;
		arsort($newArray);
	}
	elseif ($_GET['image'] == "last") {
		$newArray = $image;
		arsort($newArray);
	}
}

$script = $_SERVER["SCRIPT_NAME"]."?serviceType=".$_REQUEST['serviceType']."&guiID=".$gui_id."&";

$str = "<span style='font-size:30'>monitoring results</span><hr><br>\n";
$str .= "<form name = 'form1' method='post' action='".$script."sortby=".$_GET['sortby']."'>\n\t";
$str .= "\n\t<input type=submit value='update selected Service'>\n";
$str .= "\n\t<input type=button onclick=\"window.location.href='".$script."sortby=".$_GET['sortby']."'\" value='refresh'>\n<br/><br/>\n	";
$str .= "<table cellpadding=10 cellspacing=0 border=0>";
switch ($serviceType) {
	case "WMS":
		$str .= "<tr bgcolor='#dddddd'><th></th><th align='left'><a href='".$script."sortby=wms'>wms</a></th>";
		break;
	case "WFS":
		$str .= "<tr bgcolor='#dddddd'><th></th><th align='left'><a href='".$script."sortby=wfs'>wfs</a></th>";
		break;
}
$str .= "<th align='left' colspan = 2><a href='".$script."sortby=status'>current status</a></th>";
switch ($serviceType) {
	case "WMS":
		$str .= "<th align='left'><a href='".$script."sortby=image'>image</a></th>";
		break;
	case "WFS":
		//
		break;
}
$str .= "<th align='left'><a href='".$script."sortby=avgresp'>avg. response time</a></th>";
$str .= "<th align='left'><a href='".$script."sortby=avail'>overall availability</a></th><th></th><th>Diff</th></tr>";
$cnt = 0;
foreach ($newArray as $k => $value) {
	$img = "stop.bmp";
	if ($status[$k]==0) $img = "wait.bmp";
	elseif ($status[$k]==1) $img = "go.bmp";

	if ($updated[$k] == "0" && $status[$k] == 0) $fill = "checked"; else $fill = "disabled";
//switch ($serviceType) {
//	case "WMS":
		if (fmod($cnt, 2) == 1) {
			$str .= "\n\t\t<tr bgcolor='#e6e6e6'>";
		}
		else {
			$str .= "\n\t\t<tr bgcolor='#f0f0f0'>";
		}
//		break;
//}
switch ($serviceType) {
	case "WMS":
		$str .= "\n\t\t\t<td><input name='cb".$cnt."' value='" . $wms_id[$k] . "' type=checkbox ".$fill." /><input type=hidden name='upl_id".$cnt."' value='".$upload_id[$k]."'></td>";
		$str .= "\n\t\t\t<td valign='top'><b><a url='../php/mod_showMetadata.php?resource=wms&layout=tabs&id=" . $wms_id[$k] . "' class='modalDialog'>" . $wms_id[$k] . "</a></b><br>" . $admin->getWmsTitleByWmsId($wms_id[$k]) . "</td>";
		break;
	case "WFS":
		$str .= "\n\t\t\t<td><input name='cb".$cnt."' value='" . $wfs_id[$k] . "' type=checkbox ".$fill." /><input type=hidden name='upl_id".$cnt."' value='".$upload_id[$k]."'></td>";
		$str .= "\n\t\t\t<td valign='top'><b><a url='../php/mod_showMetadata.php?resource=wfs&layout=tabs&id=" . $wfs_id[$k] . "' class='modalDialog'>" . $wfs_id[$k] . "</a></b><br>" . $admin->getWfsTitleByWfsId($wfs_id[$k]) . "</td>";
		break;
}
	$str .= "\n\t\t\t<td valign='top'><a href='".$upload_url[$k]."' target=_blank><img title='Connect to service' border=0 src = '../img/trafficlights/". $img. "'></a></td>";

	$str .= "\n\t\t\t<td valign='top'>" . $comment[$k] . "<br><div style='font-size:12'>".date("F j, Y, G:i:s", $upload_id[$k])."</div></td>";
	//$str .= "\n\t\t\t<td valign='top'>" . $comment[$k] . "<br><div style='font-size:12'>".$upload_id[$k]."</div></td>";
if ($serviceType == "WMS") {
	$str .= "\n\t\t\t<td valign='top'>";
	$str .= "<table bgcolor='black' border=1 cellspacing=1 cellpadding=0><tr><td height=20 width=20 align=center valign=middle bgcolor='";
	if ($image[$k] == -1) {
		$str .= "red";
	}
	elseif ($image[$k] == 0) {
		$str .= "yellow";
	}
	elseif ($image[$k] == 1) {
		$str .= "green";
	}

	if ($image[$k] != -1) {
		$str .= "'><a href='".$mapurl[$k]."'>o</a></td></tr></table></td>";
	}
	else {
		$str .= "'><a href='".$mapurl[$k]."'>x</a></td></tr></table></td>";
	}
}
	$str .= "\n\t\t\t<td valign='top' align = 'left'>";
	if ($avg_response_time[$k] == NULL) {
		$str .= "n/a";
	}
	else {
		$str .= $avg_response_time[$k] . " s";
	}
	$str .= "</td>";
	$str .= "\n\t\t\t<td valign='top'><b>" . $percentage[$k] . " %</b>&nbsp;&nbsp;<span style='font-size:12'>(" . $total[$k] . " cycles)</span><br>";
	$str .= "<table bgcolor='black' border=1 cellspacing=1 cellpadding=0><tr>";
	$val = $percentage[$k];
	for ($i=0; $i<10; $i++) {
		if ($val>=10) {
			$str .= "<td height=10 width='10' bgcolor='red'></td>";
			$val-=10;
		}
		elseif($val>0){
			$str .= "<td height=10 width='" . round($val) . "' bgcolor='red'></td>";
			if (round($val) < 10) {
				$str .= "<td height=10 width='" . (9-round($val)) . "' bgcolor='white'></td>";
			}
			$val=-1;
		}
		else {
			$str .= "<td height=10 width='10' bgcolor='white'></td>";
		}
	}
	$str .= "</tr></table></td>";
switch ($serviceType) {
	case "WMS":
		$str .= "\n\t\t<td><input type=button value='details' onclick=\"var newWindow = window.open('../php/mod_monitorCapabilities_read_single.php?serviceType=wms&id=".$wms_id[$k]."','wms','width=500,height=700,scrollbars');newWindow.focus();\"></td>";
		$str .= "\n\t\t\t<td>";	
		if ($cap_diff[$k] != "" && $status[$k] == 0)
			$str .= "<input type=button value='show' onclick=\"var newWindow = window.open('../php/mod_showCapDiff.php?serviceType=wms&id=".$wms_id[$k]."','Caps Diff','width=700,height=300,scrollbars');newWindow.focus();\">";
		break;
	case "WFS":
		$str .= "\n\t\t<td><input type=button value='details' onclick=\"var newWindow = window.open('../php/mod_monitorCapabilities_read_single.php?serviceType=wfs&id=".$wfs_id[$k]."','wfs','width=500,height=700,scrollbars');newWindow.focus();\"></td>";
		$str .= "\n\t\t\t<td>";	
		if ($cap_diff[$k] != "" && $status[$k] == 0)
			$str .= "<input type=button value='show' onclick=\"var newWindow = window.open('../php/mod_showCapDiff.php?serviceType=wfs&id=".$wfs_id[$k]."','Caps Diff','width=700,height=300,scrollbars');newWindow.focus();\">";
		break;
}

	$str .= "</td></tr>";
	$cnt++;
}
$str .= "\n\t</table>\n\t<br/><input type=hidden name=cbs value='".$cnt."'>\n</form>";
echo $str;
?>
</body></html>
