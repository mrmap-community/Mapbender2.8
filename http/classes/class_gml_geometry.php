<?php
# $Id: class_gml2.php 3099 2008-10-02 15:29:23Z nimix $
# http://www.mapbender.org/index.php/class_gml2.php
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

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_gml_feature_collection.php");
require_once(dirname(__FILE__)."/../classes/class_crs.php");

abstract class GmlGeometry {

	abstract public function toGml2 ();

	abstract public function toGml3 ();
	
	abstract public function toGeoJSON ();
	
	//abstract public function getBbox ();
	
	public $srs;
	
	public $latLonSrs = array(
		"urn:x-ogc:def:crs:EPSG:4326",
		"urn:x-ogc:def:crs:EPSG:4258",
		"urn:x-ogc:def:crs:EPSG:31466",
		"urn:x-ogc:def:crs:EPSG:31467",
		"urn:x-ogc:def:crs:EPSG:31468",
		"urn:x-ogc:def:crs:EPSG:31469"
	);
	
	public function isLatLonSrs ($geomSrs) {
		//use user defined $latLonSrsArray from file epsg.php for check 
		//require_once(dirname(__FILE__)."/../../core/epsg.php");
		//new 2019-07-29
		$crs = new Crs($geomSrs);
		//$crs->axisOrder > different types:'east,north' - ('lon,lat') or 'north,east' - ('lat,lon')
		//TODO: alter geometry classes to handle crs in a right way!
		//$pattern = '/urn/';
		/*if(preg_match($pattern, $geomSrs)) {
			if(in_array($geomSrs, $latLonSrsArray)) {
				return true;
			}
			else {
				return false;
			}	
		}*/
//$e = new mb_exception("classes/class_gml_geometry.php: axis order found: ".$crs->axisOrder);
		if($crs->axisOrder == 'north,east') {
			return true;
		} else {
			return false;
		}
	}
}
?>
