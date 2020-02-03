<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_user.php");

extract($_GET, EXTR_OVERWRITE);extract($_POST, EXTR_OVERWRITE);

function forgotten_password() {
	if(
		!isset($_REQUEST["Benutzername"]) || !isset($_REQUEST["EMail"]) || ($_REQUEST["Benutzername"] == 'guest') || 
		empty($_REQUEST["Benutzername"]) || empty($_REQUEST["EMail"]) || 
		!(bool)trim($_REQUEST["Benutzername"]) || !(bool)trim($_REQUEST["EMail"])
	) {
		return -1;
	}

	if(!USE_PHP_MAILING) {
		return -4;
	}

	$administration = new administration();
	define("USER_NAME", trim($_REQUEST["Benutzername"]));
	define("USER_EMAIL",trim($_REQUEST["EMail"]));

	if(
		!$administration->getUserIdByUserName(USER_NAME) || 
		USER_EMAIL != $administration->getEmailByUserId($administration->getUserIdByUserName(USER_NAME))
	) {
		return -2;
	}

	$new_password  = $administration->getRandomPassword();
	//change 06/2019 - store more secure passwords in database!
	$user = new User($administration->getUserIdByUserName(USER_NAME));
	$result = $user->setPasswordWithoutTicket($new_password);      
	if($result == false) {
		return -3;
	}
	$email_subject = "Neues Geoportal Passwort";
	$email_body    = sprintf("Ihr neues Geoportal Passwort lautet: %s",$new_password);

	if(!$administration->sendEmail(NULL,NULL,USER_EMAIL,USER_NAME,$email_subject,$email_body,$error_msg)) {
		return -4;
	}

	return 1;
}

$success = forgotten_password();
?>
