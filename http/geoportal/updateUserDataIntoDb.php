<?php
#  
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

#Script which is included by a typo3 script to register the users
	require_once(dirname(__FILE__)."/../../core/globalSettings.php");
	require_once(dirname(__FILE__)."/../classes/class_administration.php");
	//alter handling of script to use the mapbenders user class
	/*
	 * begin of refactoring
	 */
	require_once(dirname(__FILE__)."/../classes/class_user.php");
	if (defined("PORTAL_ADMIN_USER_ID") && PORTAL_ADMIN_USER_ID != "" ) {
		$mb_user_owner = PORTAL_ADMIN_USER_ID;
	} else {
		$mb_user_owner = "1"; //default to mapbenders root user
	}
	//test for running the script directly from browser 
	//$mb_user_description = "testdescription 1";
	//$mb_user_password = "test23";
	//list of variables from calling application typo3s portal.php script
	/*
	 * $mb_user_name, $mb_user_password, $mb_user_description, $mb_user_email, $mb_user_phone, 
	 * $mb_user_organisation_name, $mb_user_position_name, $mb_user_city, $mb_user_postal_code,
	 * $Textsize, $Glossar, $mb_user_spatial_suggest, $mb_user_allow_survey, $mb_user_newsletter,
	 * 
	 */
	//mapping of values 
	if ($mb_user_newsletter == "ja") {$mb_user_newsletter = "t";} else {$mb_user_newsletter = "f";}
	if ($mb_user_allow_survey == "ja") {$mb_user_allow_survey = "t";} else {$mb_user_allow_survey = "f";}
	//check that the current user is not the anonymous one (guest)
	//build user object from information in the current session
	$user = new User(); //if no id is given, object will be instantiated from current session
	if ($user->isPublic() == false) {
		$variableMapping = array(
				//"mb_user_name" => "name",
				"mb_user_description" => "description",
				"mb_user_email" => "email",
				"mb_user_phone" => "phone",
				"mb_user_organisation_name" => "organization",
				"mb_user_position_name" => "position",
				"mb_user_city" => "city",
				"mb_user_postal_code" => "postalCode",
				//"Textsize" => "textSize",
				//"Glossar" => "wantsGlossar",
				//"mb_user_spatial_suggest" => "wantsSpatialSuggest",
				"mb_user_allow_survey" => "allowsSurvey",
				"mb_user_newsletter" => "wantsNewsletter"
		);
		foreach ($variableMapping as $key => $value) {
			if (isset(${$key}) && ${$key} != '') {
				$user->{$value} = ${$key};
			}
		}
		//save elements
		$result = $user->commit();
		if ($result == false) {
			$e = new mb_exception("geoportal/updateUserIntoDb.php: An error occured while try to save user data in database!");
		} else {
			$e = new mb_exception("geoportal/updateUserIntoDb.php: The user alter his password via the html form!");
			//alter password if new one is given
			$user->createDigest = 't';
			if (isset($mb_user_password) && $mb_user_password != '') {
				$user->setPasswordWithoutTicket($mb_user_password);
			}
			//UPDATE of the SESSION VARS
			if ($mb_user_newsletter == "t") {$mb_user_newsletter = "ja";} else {$mb_user_newsletter = "nein";}
			if ($mb_user_allow_survey == "t") {$mb_user_allow_survey = "ja";} else {$mb_user_allow_survey = "nein";}
			$_SESSION["mb_user_email"] = $mb_user_email;
			$_SESSION["mb_user_department"] = $mb_user_department;
			$_SESSION["mb_user_organisation_name"] = $mb_user_organisation_name;
			$_SESSION["mb_user_position_name"] = $mb_user_position_name;
			$_SESSION["mb_user_phone"] = $mb_user_phone;
			//$_SESSION["Textsize"] = $Textsize;
			//$_SESSION["Glossar"] = $Glossar;
			//$_SESSION["mb_user_spatial_suggest"] = $mb_user_spatial_suggest;
			$_SESSION["mb_user_newsletter"] = $mb_user_newsletter;
			$_SESSION["mb_user_allow_survey"] = $mb_user_allow_survey;
			$_SESSION["mb_user_description"]= $mb_user_description;
			$_SESSION["mb_user_city"]= $mb_user_city;
			$_SESSION["mb_user_postal_code"]= $mb_user_postal_code;
		}
	} else {
	    //send error message
		$e = new mb_exception("geoportal/updateUserIntoDb.php: The public user is not allowed to alter his attributes!");
	}
	/*
	 * end of refactoring
	 */
?>
