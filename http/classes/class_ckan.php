<?php
# http://www.mapbender.org/index.php/class_ckanPackage.php
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

class ckanPackage {
	var $id;
	var $name;
	var $title;
	var $resources;
	var $author;
	var $maintainer;
	var $maintainer_email;
	var $licence_id;
	var $tags;
	var $notes;
	var $extras;

	function __construct() {
		//initialize empty ckanPackage object
		//attributes which are handled by ckan itself
		//$this->id = "";
		//attributes which are handled by external datasources
		$this->name = "";
		$this->title = "";
		$this->resources = array();
		$this->author = "";
		$this->author_email = "";
		$this->maintainer = "";
		$this->maintainer_email = "";
		$this->licence_id = "";
		$this->tags = "";//list
		$this->notes = "";
		$this->extras = "";//{ Name-String: String, ... } }
	}

	public function toSomething ($xml) {
		
		return $xml;
	}
}
class ckanGroup {
	var $id;
	var $name;
	var $title;
	var $packages;

	function __construct() {
		//initialize empty ckanGroup object
		//attributes which are handled by ckan itself
		//$this->id = "";
		//attributes which are handled by external datasources
		$this->name = "";
		$this->title = "";
		$this->packages = "";//list of datasets(packages)
	}

	public function toSomething ($xml) {
		return $xml;
	}
}

?>
