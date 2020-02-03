<?php

/* 
 *  Copyright (C) 2017 WhereGroup
 * 
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2, or (at your option)
 *  any later version.
 * 
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * Description of class_VersionSelector
 *
 * @author Tobias Rieck tobias.rieck@benndorf.de
 */
class VersionSelector {
    function __construct() {
//            print "VersionSelector-Constructor<br />\n";
	}

	// provides the environment dependent classname for $ClassName
	public static function GetVersioned($ClassName){
            $phpversion = phpversion();

            if (is_null($ClassName) | $ClassName === ""){
                return ["", $phpversion];
            }

            if (strpos($phpversion, "5.") === 0) {
                return ["Version5".$ClassName, "php5path", $phpversion];
            }
            else if (strpos($phpversion, "7.") === 0) {
                return ["Version7".$ClassName, "php7path", $phpversion];
            }
	}
	
	// provides a new environment dependent object of class $ClassName
	public static function GetVersionedInstance($ClassName){
            $versioninfo = VersionSelector::GetVersioned($ClassName);
            return new $versioninfo[0];
	}
	
	// provides the environment dependent classname, if committed, the
        // methodname and the call parameters of a static function call as input
        // arrays for the php internal function call_user_func_array
	// string parameters in $StaticCall have to be enclosed in single quotes
        // and special characters to be escaped by backslash.
	public static function GetParams($StaticCall){
		
            $exploded = explode("::", $StaticCall);
            if (count($exploded) > 1) {
            $classname = $exploded[0];
            $classname = VersionSelector::GetVersioned($classname)[0];
            $method = $exploded[1];
            }
            else {
            $method = $exploded[0];
            }

            $params = preg_split('/\(/', $method, 2, PREG_SPLIT_DELIM_CAPTURE);
            $params = trim($params[1]);
            $params = substr($params, 0, strlen($params) - 1);

            $method = explode("(", $method)[0];
            $parameters = str_getcsv($params, ",", "'");
            if (is_null($classname)) {
                return array($method, $parameters);
            }
            else {
                return array(array($classname, $method), $parameters);
            }
	}
	
	// provides the result of a static function call by calling the environment
	// dependent class's static function with the given parameters
	public static function GetStaticResult($StaticCall){
            return call_user_func_array(VersionSelector::GetParams($StaticCall)[0], VersionSelector::GetParams($StaticCall)[1]);
	}
	
	// the non-static version of GetParams. Probably only for development
	// purposes
	public function GetParamsForStatic($StaticCall){

            $exploded = explode("::", $StaticCall);
            if (count($exploded) > 1) {
                $classname = $exploded[0];
                $classname = VersionSelector::GetVersioned($classname)[0];
                $method = $exploded[1];
            }
            else {
                $method = $exploded[0];
            }

            $params = preg_split('/\(/', $method, 2, PREG_SPLIT_DELIM_CAPTURE);
            $params = trim($params[1]);
            $params = substr($params, 0, strlen($params) - 1);

            $method = explode("(", $method)[0];
            $parameters = str_getcsv($params, ",", "'");
            if (is_null($classname)) {
                return array($method, $parameters);
            }
            else {
                return array(array($classname, $method), $parameters);
            }
	}
}
