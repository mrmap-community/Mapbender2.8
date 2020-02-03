<?php
# $Id: 
# http://www.mapbender.org/index.php/
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

//script is invoked from cms to delete a mapbender user profile from the mapbender db

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");

$ajaxResponse = new AjaxResponse($_POST);
//$admin = new Administration();

function abort ($message) {
	global $ajaxResponse;
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage($message);
	$ajaxResponse->send();
	die();
}

function getUserFromSession()
{
    if (Mapbender::session()->get('mb_user_id')) {
        if ((integer) Mapbender::session()->get('mb_user_id') >= 0) {
            $foundUserId = (integer) Mapbender::session()->get('mb_user_id');
        } else {
            $foundUserId = false;
        }
    } else {
        $foundUserId = false;
    }
    return $foundUserId;
}

if (getUserFromSession() == (integer)PUBLIC_USER) {
	abort(_mb("The profile of the Public User cannot be deleted!"));
} 

if (getUserFromSession() == false) {
	abort(_mb("Your are not logged in and therefor your user profile cannot be deleted!"));
} 

//Message if some things have to be done before the profile can be deleted from mapbender database:
//initialize message
$message = "";

$sql = "SELECT count(a.service_id) from (SELECT wms_id AS service_id, 'wms' AS service_type FROM wms WHERE wms_owner = $1 UNION SELECT wfs_id AS service_id, 'wfs' as service_type from wfs WHERE wfs_owner = $1 LIMIT 1) AS a;";

$v = array(getUserFromSession());
$t = array('i');
$res = db_prep_query($sql,$v,$t);

while($row = db_fetch_array($res)){
	if ($row['count'] !== "0") {
		$message .= "\n"._mb("You are owner of registrated services - please delete them or give the ownership to another user.");
	}
}

$sql = "SELECT count(fkey_gui_id) FROM  (SELECT fkey_gui_id FROM gui_mb_user WHERE fkey_mb_user_id = $1 AND  mb_user_type = 'owner' LIMIT 1) AS a;";
$v = array(getUserFromSession());
$t = array('i');
$res = db_prep_query($sql,$v,$t);

while($row = db_fetch_array($res)){
	if ($row['count'] !== "0") {
		$message .= "\n"._mb("You are owner of guis/applications - please delete them or give the ownership to another user.");
	}
}

$sql = "SELECT count(fkey_wms_id) FROM mb_proxy_log INNER JOIN (SELECT wms_id FROM wms WHERE (wms_pricevolume NOTNULL AND wms_pricevolume <> 0) OR (wms_price_fi NOTNULL AND wms_price_fi <> 0)) as a ON a.wms_id = mb_proxy_log.fkey_wms_id WHERE fkey_mb_user_id = $1 LIMIT 1;";

$v = array(getUserFromSession());
$t = array('i');
$res = db_prep_query($sql,$v,$t);

while($row = db_fetch_array($res)){
	if ($row['count'] !== "0") {
		$message .= "\n"._mb("There are logged service accesses for this user profile. Please connect the service administrators for the billing first.");
	}
}

if ($message !=="") {
	$message = _mb("The current profile cannot be deleted for the following reasons: ").$message;
	abort($message);
}

switch ($ajaxResponse->getMethod()){
	case "deleteUserProfile" :
		$sql = "DELETE FROM mb_user WHERE mb_user_id = $1;";
		$v = array(getUserFromSession());
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		if ($res !== false) {
			$ajaxResponse->setSuccess(true);
			$ajaxResponse->setMessage(_mb("User with ID")." - ".getUserFromSession()." - "._mb("was deleted from the geoportal database")."!");
			$ajaxResponse->setResult("User deleted successfully!");
			$ajaxResponse->send();
		} else {
			abort(_mb("User with ID")." - ".getUserFromSession()." - "._mb("cannot be deleted from the geoportal database - something went wrong")."!");
		}	
	break;
	default :
		abort(_mb("Used request method is not allowed!"));	
	break;
}

?>
