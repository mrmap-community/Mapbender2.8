<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_owsConstraints.php"); 
$constraints = new OwsConstraints();
$result = $constraints->getRequestParameters();
//$constraints->returnDirect = false;
$constraints->returnDirect = true;
if (!$result['success']) {
	echo $result['message'];
	die();
}
$result = $constraints->getDisclaimer();
?>
