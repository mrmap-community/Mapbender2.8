<?php
# $Id: globalSettings.php 10056 2019-02-17 20:41:01Z armin11 $
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

ob_start();

//
// constants
//
require_once(dirname(__FILE__)."/../core/system.php");
//
// initiates the session-handling
//
if (defined("SESSION_NAME") && is_string(SESSION_NAME)) {
	session_name(SESSION_NAME);
        session_start();
} else {
        session_start();
}

//
// Basic Mapbender classes, for session handling etc.
//
require_once dirname(__FILE__)."/../lib/class_Mapbender.php";

//
// define LC_MESSAGES if unknown (for Windows platforms)
// 
if (!defined("LC_MESSAGES")) define("LC_MESSAGES", LC_CTYPE);

//
// I18n wrapper function, gettext
//
require_once dirname(__FILE__) . "/../core/i18n.php";
require_once dirname(__FILE__) . "/../http/classes/class_locale.php";
$localeObj = new Mb_locale(Mapbender::session()->get("mb_lang"));

//
// globally used includes (due to PHP Version changes)
//
require_once dirname(__FILE__) . "/../http/php/wrappers/includes.php";

//
// sets a public user session if defined in mapbender.conf
//
if (defined("PUBLIC_USER_AUTO_CREATE_SESSION") && PUBLIC_USER_AUTO_CREATE_SESSION) {
    if (defined("PUBLIC_USER") && is_numeric(PUBLIC_USER)) {
        //try to read a mb_user_name from session
        $mb_user_name = Mapbender::session()->get("mb_user_name");
	$e = new mb_notice("mb_user_name from session: ".$mb_user_name);
        if(!isset($mb_user_name) || $mb_user_name == "" || $mb_user_name == false) {
	    $e = new mb_notice("No mb_user_name found in SESSION - initialize PUBLIC_USER SESSION");
	    $isAuthenticated = getUserData(PUBLIC_USER);
	    if($isAuthenticated != false){  
		    Mapbender::session()->set("mb_user_id", $isAuthenticated["mb_user_id"]);
		    Mapbender::session()->set("mb_user_name", $isAuthenticated["mb_user_name"]);
		    Mapbender::session()->set("mb_user_ip", $_SERVER['REMOTE_ADDR']);
       		    Mapbender::session()->set("HTTP_HOST", $_SERVER["HTTP_HOST"]);
                    if (defined("PUBLIC_USER_DEFAULT_SRS") && PUBLIC_USER_DEFAULT_SRS !=="") {
                        Mapbender::session()->set("epsg", PUBLIC_USER_DEFAULT_SRS);
		    }
		    Mapbender::session()->set("mb_myBBOX", "");
                    if (defined("PUBLIC_USER_DEFAULT_GUI") && PUBLIC_USER_DEFAULT_GUI !=="") {
                        Mapbender::session()->set("mb_user_gui", PUBLIC_USER_DEFAULT_GUI);
		    }
		    Mapbender::session()->set("layer_preview", 0);
		    Mapbender::session()->set("mb_user_spatial_suggest", 'nein');
	    }
	    require_once(dirname(__FILE__)."/../http/php/mb_getGUIs.php");
	    $arrayGUIs = mb_getGUIs($isAuthenticated["mb_user_id"]);
	    Mapbender::session()->set("mb_user_guis", $arrayGUIs);
        }
    }
}
//debug
/*foreach ($_COOKIE as $key => $value) {
    if ($key == "MAPBENDER") {
        $e = new mb_exception("core/globalSettinmgs.php: cookie name: ".$key."cookie value: ".$value);
    }
}*/

function getUserData ($userId){
	$con = db_connect(DBSERVER,OWNER,PW);
	db_select_db(DB,$con);
	$sql = "SELECT * FROM mb_user WHERE mb_user_id = $1";
	$v = array($userId);
	$t = array('i');
	$res = db_prep_query($sql,$v,$t);
	if($row = db_fetch_array($res)){
		return $row;	
	} else {
		return false;
	}
}

?>
