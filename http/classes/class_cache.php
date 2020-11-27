<?php
# $Id: class_cache.php  2013-07-30 11:30:35Z armin11 $
# http://www.mapbender.org/index.php/class_cache.php
# 
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

/**
 * A class for using a variable cache such as apc or mechached.
 * In the first version only some functions from apc are provided.
 */
class Cache {
	var $cacheType;
	var $isActive; # boolean

	/**
	 * @constructor
	 * @param String url the URL that will be loaded (optional)
	 */
	public function __construct() {
		$this->isActive = false;
		if (DEFINED("MAPBENDER_VARIABLE_CACHE") && MAPBENDER_VARIABLE_CACHE) {
			if (DEFINED("MAPBENDER_CACHE_TYPE") && MAPBENDER_CACHE_TYPE != "") {
				$this->cacheType = MAPBENDER_CACHE_TYPE;
				switch ($this->cacheType) {
					case 'apc':
					case 'apcu':
						$this->isActive = true;
					break;
				}
			}
		}
	}

	final public function cachedVariableFetch($key) {
		switch ($this->cacheType) {
			case "apc":
				return apc_fetch($key);
			break;
			case "apcu":
				return apcu_fetch($key);
			break;
			default:
				return false;
			break;
		}
	}

	final public function cachedVariableExists($key) {
		switch ($this->cacheType) {
			case "apc":
				//to allow older versions of apc - e.g. 3.1.3 which is used by debian squeeze
       				if (function_exists('apc_exists')) {
					return apc_exists($key);
				} else {
					return (boolean)apc_fetch($key);
				}
			break;
			//for php 5.5+ - debian 8 onwards
			case "apcu":
				return (boolean)apcu_fetch($key);
			break;
			default:
				return false;
			break;
		}
	}
	
	final public function cachedVariableCreationTime($key) {
		switch ($this->cacheType) {
			case "apc":
				$cache = apc_cache_info('user');
				if (empty($cache['cache_list'])) {
       					return false;
    				}
				foreach ($cache['cache_list'] as $entry) {
        				if ($entry['info'] != $key) {
            					continue;
        				}
        				return $entry['creation_time'];
				}
				return false;
			break;
			case "apcu":
				$cache = apcu_cache_info('user');
				if (empty($cache['cache_list'])) {
       					return false;
    				}
				foreach ($cache['cache_list'] as $entry) {
        				if ($entry['info'] != $key) {
            					continue;
        				}
        				return $entry['creation_time'];
				}
				return false;
			break;
			default:
				return false;
			break;
		}
		return false;
	}

	final public function cachedVariableAdd($key, $value, $ttl = 0) {
		switch ($this->cacheType) {
			case "apc":
				return apc_add($key, $value, $ttl);
			break;
			case "apcu":
				return apcu_add($key, $value, $ttl);
			break;
			default:
				return false;
			break;
		}
	}

	final public function cachedVariableDelete($key) {
		switch ($this->cacheType) {
			case "apc":
				return apc_delete($key);
			break;
			case "apcu":
				return apcu_delete($key);
			break;
			default:
				return false;
			break;
		}
	}


}
?>
