<?php

/**
 * @version   Changed: ### 2015-05-04 07:58:43 UTC ###
 * @author    Raphael.Syed <raphael.syed@WhereGroup.com> http://WhereGroup.com
 */

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
// require_once(dirname(__FILE__) . "/../classes/class_wmc.php");
/**
 * unpublish the the given wmc
 */
// user_id
$user_id = Mapbender::session()->get("mb_user_id");
// create user object
$currentUser = new User($user_id);
// var_dump($currentUser->isPublic());
if ($currentUser->isPublic()) {
	echo(intval(true));

} else {
	echo(intval(false));
}
