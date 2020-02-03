<?php
# $Id:
# http://www.mapbender.org/index.php
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
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
//require_once(dirname(__FILE__)."/../classes/class_user.php");
$returnObject = new stdClass();
if (defined("DJANGO_PORTAL") && DJANGO_PORTAL == true) {
	if($_SERVER["HTTPS"] != "on") {
		$loginRedirectUrl = "http://".$_SERVER['HTTP_HOST']."/login/";
		$registerRedirectUrl = "http://".$_SERVER['HTTP_HOST']."/register/";
	} else {
		$loginRedirectUrl = "https://".$_SERVER['HTTP_HOST']."/login/";
		$registerRedirectUrl = "https://".$_SERVER['HTTP_HOST']."/register/";
	}
} else {
	$loginRedirectUrl = LOGIN;
	$registerRedirectUrl = $loginRedirectUrl;
}

if (isset($_REQUEST["activationKey"]) & $_REQUEST["activationKey"] != "") {
	$testMatch = $_REQUEST["activationKey"];
	$pattern = '/^([a-z]|[0-9])*$/';
 	if (!preg_match($pattern,$testMatch)){
		echo 'Parameter <b>activationKey</b> is not valid.<br/>';
		die();
 	}
	$activationKey = $testMatch;
	$testMatch = NULL;
}
//select account
$sql = "SELECT mb_user_id FROM mb_user WHERE activation_key = $1";
$v = array($activationKey);
$t = array('s');
$res = db_prep_query($sql, $v, $t);
if(db_numrows($res) == 0){
	$e = new mb_exception("php/mod_activateUserAccount.php: user with requested activation_key not found in mapbender database!");
	$returnObject->success = false;
	$returnObject->help = "mod_activateUserAccount.php";
	$returnObject->error->message = "php/mod_activateUserAccount.php: user with requested activation_key not found in mapbender database!";
	$returnObject->error->{__type} = "Object not found";
	header('Content-Type: application/json');
	echo json_encode($this->returnObject);
	//redirect to register
sleep(5);
	header("Location: ".$registerRedirectUrl);
	die();
} else {
	$row = db_fetch_assoc($res);
	if ($row['is_active'] == 't') {
		$e = new mb_exception("php/mod_activateUserAccount.php: User account already activated!");
		$returnObject->success = false;
		$returnObject->help = "mod_activateUserAccount.php";
		$returnObject->error->message = "php/mod_activateUserAccount.php: User account already activated!";
		$returnObject->error->{__type} = "Object not found";
		header('Content-Type: application/json');
		echo json_encode($this->returnObject);
		//redirect to login?
sleep(5);
		header("Location: ".$loginRedirectUrl);
		die();
	} else {

		$sql = "UPDATE mb_user SET is_active = true WHERE activation_key = $1";
		$v = array($activationKey);
		$t = array('s');
		$res = db_prep_query($sql, $v, $t);
		if (!$res) {
			$e = new mb_exception("php/mod_activateUserAccount.php: Could not activate user account!");
			$returnObject->success = false;
			$returnObject->help = "mod_activateUserAccount.php";
			$returnObject->error->message = "php/mod_activateUserAccount.php: Could not activate user account!";
			$returnObject->error->{__type} = "Object not found";
			header('Content-Type: application/json');
			echo json_encode($this->returnObject);
			die();
		} else {

			$sql = "UPDATE mb_user SET mb_user_login_count = 0 WHERE activation_key = $1";
			$v = array($activationKey);
			$t = array('s');
			$res = db_prep_query($sql,$v,$t);

			$e = new mb_exception("php/mod_activateUserAccount.php: User account successfully activated!");
			$returnObject->success = true;
			unset($returnObject->error);
			$returnObject->help = "mod_activateUserAccount.php";
			//redirect to login page
sleep(5);
			header("Location: ".$loginRedirectUrl);
			die();
		}
	}
}
?>
