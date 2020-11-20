<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";

class CustomCategory {
	
	public function __construct() {
		//mandatory
	}
	
 	public function readFromDb($idFilter = false, $languageCode = "en", $showHidden = false, $outputFormat = "assocArray", $originIdFilter = false) {
 		$sql = "SELECT custom_category_id AS id, custom_category_key AS key, custom_category_code_".$languageCode." AS name, custom_category_description_".$languageCode." as description, custom_category_parent_key AS parent_key FROM custom_category";
 		if ($showHidden == false || (is_array($originIdFilter) && count($originIdFilter) > 0) || $idFilter != false) {
 			//some where clause is needed
 			//build array with where conditions
 			$whereArray = array();
 			if ($showHidden == false) {
 				$whereArray[] = "custom_category_hidden is null";
 			}
 			if (is_array($originIdFilter) && count($originIdFilter) > 0) {
 				$whereArray[] = "fkey_custom_category_origin_id IN (".implode($originIdFilter, ',').")";
 			}
 			if (is_array($oidFilter) && count($idFilter) > 0) {
 				$whereArray[] = "custom_category_id IN (".implode($idFilter, ',').")";
 			}
 		}
 		$whereArray[] = "deletedate IS NULL";
 		if (count($whereArray) > 1) {
 			$whereCondition = implode(" AND ", $whereArray);
 		}
 		$sql .= " WHERE ".$whereCondition;
 		//$e = new mb_exception($sql);
 		$res = db_query($sql);
 		$customCategory = array();
 		$customCategories = array();
 		while ($row = db_fetch_assoc($res)) {
 			$customCategory['id'] = $row['id'];
 			$customCategory['key'] = $row['key'];
 			if (is_null($row['parent_key'])) {
 				$row['parent_key'] = "";
 			}
 			$customCategory['parent_key'] = $row['parent_key'];
 			$customCategory['name'] = $row['name'];
 			$customCategory['description'] = $row['description'];
 			$customCategories[] = $customCategory;
 		}
 		//$e = new mb_exception(json_encode($customCategories));
 		return $customCategories;
	}
	/*
	 * Function to build structured data from database to fill e.g. a jstree structuretree
	 * https://www.jstree.com/docs/json/
	 */
	public function buildStructure($customCategories) {
		$resultObject = array();
		foreach($customCategories as $customCategory) {
			//use id as key - but therefor we need also the id for the parents
			$entry['id'] = $customCategory['id'];
			$entry['key'] = $customCategory['key'];
			$entry['text'] = $customCategory['description'];
			if ($customCategory['parent_key'] == "") {
				$entry['parent'] = "#";
			} else {
				//get id from parent fo hierarchy
				$parentCategories = $this->filter_by_value($customCategories, 'key', $customCategory['parent_key']);
				//$e = new mb_exception("number of found rows for parent: ".$customCategory['parent_key']." - ".count($parentCategories));
				foreach ($parentCategories as $parentCategory){
					$entry['parent'] = $parentCategory['id'];
				}
			}
			$resultObject[] = $entry;
		}
		return $resultObject;
	}
	/*
	 * Function to filter a multidimensional array by value - see php doc
	 */
	public function filter_by_value ($array, $index, $value){
		if(is_array($array) && count($array)>0) {
			foreach(array_keys($array) as $key){
				$temp[$key] = $array[$key][$index];
				if ($temp[$key] == $value){
					$newarray[$key] = $array[$key];
				}
			}
		}
		return $newarray;
	}
}
?>