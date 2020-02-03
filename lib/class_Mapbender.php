<?php
/*
 * Created on 24.06.2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once(dirname(__FILE__)."/class_Mapbender_session.php");
require_once(dirname(__FILE__)."/class_Singleton.php");
 
 class Mapbender extends Singleton{
 	
 	protected function __construct() {
 	}
 
 	public static function session() {	
 		return Mapbender_session::singleton();
 	}
 	
 	public static function singleton() {
        	return parent::singleton(__CLASS__);
    	}
	
	public static function postgisAvailable () {
		$sql = "Select postgis_full_version()";
		$res = db_query($sql);
		if ($row = db_fetch_array($res)) {
			return true;
		}
		return false;
	}

	public static function parseGuiRequestParameter($parameter) {
		if (is_array($parameter)) {	
			if (count($parameter) == 1) {
				return $parameter[0];
			} else {
				$e = new mb_exception("php/mb_validateSession.php: REQUEST parameter is an array with length > 1!");
				return false;
			}
		} else {
			if (is_string($parameter)) {
				return $parameter;
			} else {
				$e = new mb_exception("php/mb_validateSession.php: REQUEST parameter is neither an array with length 1 nor a string!");
				return false;
			}
		}
	}

 }
?>
