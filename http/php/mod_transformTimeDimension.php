<?php
# 
# http://www.mapbender.org/index.php/mod_transformTimeDimension.php
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
#
# Module to transform the wms time dimension extent element into a json configuration for an javascript timeline plugin via ajax calls
# See http://visjs.org/examples/timeline/dataHandling/loadExternalData.html

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
$objDateTime = new DateTime('NOW');
$maxEntries = 200;
$maxConcurrentEntries = 10;
$default = false;
$userValue = false;
$operation = "configureTimeline";
$kindOfExtent = "singleValue"; //interval, discreteValues, intervalWithDuration
$extent = $objDateTime->format($objDateTime::ATOM);
//http://stackoverflow.com/questions/21686539/regular-expression-for-full-iso-8601-date-syntax
//http://stackoverflow.com/questions/12756159/regex-and-iso8601-formated-datetime
function abort($message) {
	$result->result->error = false;
	$result->result->message = $message;
	header('Content-Type: application/json');
	echo json_encode($result);
	die();
}
$iso8601Pattern = '/^\d{4}(-\d\d(-\d\d(T\d\d:\d\d(:\d\d)?(\.\d+)?(([+-]\d\d:\d\d)|Z)?)?)?)?$/i';
$singleYearPattern = '/^\d{4}$/';
//$iso8601Pattern = '/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])|(?:[2468][048]|[13579][26])00)-02-29)T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:Z|[+-][01]\d:[0-5]\d)$/i';
if (isset($_REQUEST["default"]) & $_REQUEST["default"] != "") {
	$testMatch = urldecode($_REQUEST["default"]);
	if (!preg_match($iso8601Pattern,$testMatch) && $testMatch !== 'current'){
		abort("The value for the default parameter is not a valid iso8601 dateTime string or has the value 'current'."); 	
 	}
	$default = $testMatch;
}
if (isset($_REQUEST["operation"]) & $_REQUEST["operation"] != "") {
	$testMatch = urldecode($_REQUEST["operation"]);
	if (!$testMatch == 'snapToGrid' && !$testMatch == 'configureTimeline'){ 
		echo 'Parameter <b>operation</b> is not valid (snapToGrid,configureTimeline).<br/>'; 
		die(); 		
 	}
	$operation = $testMatch;
}	
if (isset($_REQUEST["userValue"]) & $_REQUEST["userValue"] != "") {
	$testMatch = urldecode($_REQUEST["userValue"]);
	if (!preg_match($iso8601Pattern,$testMatch)){
		abort("The value for the userValue parameter is not a valid iso8601 dateTime string."); 	
 	}
	$userValue = $testMatch;
}	
if (isset($_REQUEST["extent"]) & $_REQUEST["extent"] != "") {
	$testMatch = urldecode($_REQUEST["extent"]);
	//search for comma
	if (strpos($testMatch,',') !== false) {
		//found single discrete values
		$singleValues = explode(',',$testMatch);
		//test format of each value 
		foreach ($singleValues as $dateTime) {
			if (!preg_match($iso8601Pattern,$dateTime)){
				abort("The value for the extent parameter is not csv list of a valid iso8601 dateTime strings."); 	
 			}
		}
		$kindOfExtent = "discreteValues";
	} elseif (strpos($testMatch,'/') !== false) {
		//found interval with duration
		//extract values
		if (count(explode('/',$testMatch)) !== 3 ) {
			abort("The value for the extent parameter is not a valid iso8601 time duration string."); 
	
		} else {
			$singleValues = explode('/',$testMatch);
			//check the first 2 entries for iso8601 strings	
			for ($i=0; $i < 2; $i++) {
				if (!preg_match($iso8601Pattern,$singleValues[$i])){
					abort("The first two parts of the value for the extent parameter are not valid iso8601 dateTime strings."); 	
 				}
			}
			//check duration string
			$iso8601DurationPattern = '/^P(?=\w*\d)(?:\d+Y|Y)?(?:\d+M|M)?(?:\d+W|W)?(?:\d+D|D)?(?:T(?:\d+H|H)?(?:\d+M|M)?(?:\d+(?:\­.\d{1,2})?S|S)?)?$/';	
			if (!preg_match($iso8601DurationPattern,$singleValues[2])) {
				abort("The third part of the value for the extent parameter is not a valid iso8601 duration string."); 
			}
		}
		$kindOfExtent = "intervalWithDuration";
	} elseif (!preg_match($iso8601Pattern,$testMatch)) {

			abort("The value for the extent parameter is not a valid iso8601 dateTime string."); 
	}
	//everything is allright
	$extent = $testMatch;
	$testMatch = NULL;
}


//function to find the nearest interval of array elements to a given array value
//returns a value, if one special element is found or returns the smallest interval 
//from the ordered array elements in which element lies in
function quicksearch($element, $searchArray) {
	$numberOfValues = count($searchArray);
	if ($numberOfValues == 2) {
		return $searchArray;
	}
	while ($numberOfValues > 2) {
		if ($element == $searchArray[ceil($numberOfValues / 2) - 1]) {
			return $element; //return value is no array!
		} else {
			if ($element >= $searchArray[ceil($numberOfValues / 2) - 1]) {
				$numberOfValues = count($searchArray);
				if ($numberOfValues == 2) {
					return $searchArrayNew;
				}
				$searchArrayNew = quicksearch($element, array_slice($searchArray, ceil($numberOfValues / 2) - 1));
			} else {
				$numberOfValues = count($searchArray);
				if ($numberOfValues == 2) {
					return $searchArray;
				}
				$searchArrayNew = quicksearch($element, array_slice($searchArray, 0, ceil($numberOfValues / 2)));
			}
			$numberOfValues = count($searchArrayNew);			
			if ($numberOfValues == 2) {
				return $searchArrayNew;
			}
		}
	}
	return $searchArrayNew;
}

function getNearestValue($userValue, $discreteValues) {
	$discreteDateTimes = array();
	$numberOfValues = count($discreteValues);
	$userValueDateTime = new DateTime($userValue);
	//transform discrete strings to time
	foreach ($discreteValues as $discreteValue) {
		$discreteDateTimes[] = new DateTime($discreteValue);
	}
	//do quicksearch
	//check if current value is less than first entry
	if ($userValueDateTime <= $discreteDateTimes[0]) {
		return ($discreteDateTimes[0]);
	} else {
		if ($userValueDateTime >= $discreteDateTimes[$numberOfValues-1]) {
			return ($discreteDateTimes[$numberOfValues-1]);
		} else {
			//do a quicksearch in array
			$result = quicksearch($userValueDateTime, $discreteDateTimes);
			if (is_array($result) && count($result) == 2) {
				//test the length of the interval
				$diffTime1 = abs(interval2Seconds($result[0]->diff($userValueDateTime)));
				$diffTime2 = abs(interval2Seconds($result[1]->diff($userValueDateTime)));
				if ($diffTime1 > $diffTime2) {
					return $result[1];
				} else {
					return $result[0];
				}
			} else {
				//return value itself
				return $result;
			}
		}
	}
}

//function to convert a php datetime intervall to seconds 
function interval2Seconds($duration) {
	if ($duration->days == false) {
		$seconds = $duration->s + $duration->i * 60 + $duration->h * 3600 + $duration->d * (3600*24) + $duration->m * (30.436875*3600*24) + $duration->y * (365*3600*24) ; 
	} else {
		$seconds = $duration->s + $duration->i * 60 + $duration->h * 3600 + $duration->days * (3600*24); 
	}
	return $seconds;
}

//Variable for result object
$result->data = array();
//define default timezone:
date_default_timezone_set('UTC');
//what do we need further for timeline:
$fullYearExtent = false;
//min, max, if point is slideable, if some point is already selected -> userValue
if ($operation == 'snapToGrid') {
	//check for discrete values - if they exists - call a function to find next value - like quicksearch
	if (strpos($extent,'/') === false) { //no intervall found in extent
		$discreteValues = explode(',',$extent);
		if (preg_match($singleYearPattern,$interval[0])) {
			$fullYearExtent = true;
			//$e = new mb_exception("single year pattern for snapping");
			//set to middle of the year
			//$interval[0] = $interval[0]."-07-02";
			//$interval[1] = $interval[1]."-07-02";
		}
		//ordered??? - define discrete values to be ordered !!	
		$newValue = getNearestValue($userValue, $discreteValues);
		//$e = new mb_exception("newtime: ".$newTime->format('c'));
		if ($fullYearExtent == true) {
			$result->data[0]->value = $newValue->format('Y');
		} else {
			$result->data[0]->value = $newValue->format('c');
		}
		$result->result->error = false;
		$result->result->message = "All done";
	} else {
		$interval = explode('/',$extent);
		$startTime = new DateTime($interval[0]);
		//check full year extent for snapping
		if (preg_match($singleYearPattern,$interval[0])) {
			$fullYearExtent = true;
			//$e = new mb_exception("single year pattern for snapping");
			//set to middle of the year
			$interval[0] = $interval[0]."-07-02";
			//$interval[1] = $interval[1]."-07-02";
		}
		//$e = new mb_exception("default timezone: ".date_default_timezone_get());
		$timezone = $startTime->getTimezone();
		//timezone from starttime:	
		//test if timezone was found in starttime:
		//$e = new mb_exception("timezone found in starttime: ".$timezone->getName());
		//$e = new mb_exception("start timezone: ".$startTime->getTimezone());
		$defaultTimeZone = timezone_open('UTC');
		//$e = new mb_exception("timezone from starttime: ".date_default_timezone_get($timezone));
		//problem - if no timezone is set use default GMT!!
		//$endTime = new DateTime($interval[1]);
		$duration = new DateInterval($interval[2]);
		$userValueDateTime = new DateTime($userValue);
		$diffTime = $startTime->diff($userValueDateTime); //creates php datetime interval object
		$diffTimeSeconds = $diffTime->s + $diffTime->i * 60 + $diffTime->h * 3600 + $diffTime->days * (3600*24);
		//$e = new mb_exception("days: ".$diffTime->days);
		$seconds = $duration->s + $duration->i * 60 + $duration->h * 3600 + $duration->d * (3600*24)  + $duration->m * (30.436875*3600*24) + $duration->y * (365*3600*24);
		//$e = new mb_exception("durationdays: ".$duration->d);
		$fullDiffInSteps = round($diffTimeSeconds / $seconds);
		/*$e = new mb_exception("start: ".$interval[0]);
		$e = new mb_exception("wish to select: ".$userValue);
		$e = new mb_exception("steps: ".$fullDiffInSteps);
		$e = new mb_exception("seconds: ".$seconds);*/
		$dateIntervalNew = new DateInterval('PT'.$fullDiffInSteps * $seconds.'S');
		$newTime = $startTime->add($dateIntervalNew);
		//$newTime = $newTime->setTimezone($timezone);
		$newTime = $newTime->setTimezone($defaultTimeZone);
		//$e = new mb_exception("newtime: ".$newTime->format('c'));
		if ($fullYearExtent == true) {
			$result->data[0]->value = $newTime->format('Y');
		} else {
			$result->data[0]->value = $newTime->format('c');
		}
		$result->result->error = false;
		$result->result->message = "All done";
	}
} else {
	switch ($kindOfExtent) {
		case "intervalWithDuration":
			//calculate discrete points
			$interval = explode('/',$extent);
			//check extent for single year entries
			if (preg_match($singleYearPattern,$interval[0]) && preg_match($singleYearPattern,$interval[1])) {
				$fullYearExtent = true;
				//$e = new mb_exception("single year pattern");
				//set to middle of the year
				$interval[0] = $interval[0]."-07-02";
				$interval[1] = $interval[1]."-07-02";
			}
			//parse one single year as date of the  
			//$e = new mb_exception("extent representation of starttime: ".$interval[0]);
			$startTime = new DateTime($interval[0]);
			//$e = new mb_exception("date representation of starttime: ".$startTime->format('c'));
			$endTime = new DateTime($interval[1]);
			$duration = new DateInterval($interval[2]);
			$timezone = $startTime->getTimezone();
			//$e = new mb_exception("timezone of starttime: ".$timezone->getName());
			$diffTime = $startTime->diff($endTime); //creates php datetime interval object
			$diffTimeSeconds = $diffTime->s + $diffTime->i * 60 + $diffTime->h * 3600 + $diffTime->days * (3600*24);
			//Problem: DateInterval cannot be directly converted into seconds - only days are possible
			//days are only given, if interval has more than some days - if not given, use d attribute
			if ($duration->days == false) {
				$seconds = $duration->s + $duration->i * 60 + $duration->h * 3600 + $duration->d * (3600*24) + $duration->m * (30.436875*3600*24) + $duration->y * (365*3600*24) ; 
			} else {
				$seconds = $duration->s + $duration->i * 60 + $duration->h * 3600 + $duration->days * (3600*24); 
			}
			$numberOfDiscreteValues = $diffTimeSeconds / $seconds;
			if ($numberOfDiscreteValues > $maxEntries) {
				//abort("Number of possible discrete values: ".$numberOfDiscreteValues." exeeds max allowed number for visualization of timeline: ".$maxEntries."!");
				//use the default value or the userValue if given
				if ($userValue !== false || $default !== false) {
					if ($userValue !== false) {
						$result->data[0]->id = 0;
						$result->data[0]->content = $userValue;
						$result->data[0]->start = $userValue;
					} else {
						$result->data[0]->id = 0;
						$result->data[0]->content = $default;
						if ($default == 'current') {
							$result->data[0]->start = $endTime->format('c');
							if ($fullYearExtent == true) {
								$result->data[0]->content = $endTime->format('Y');
							} else {
								$result->data[0]->content = $endTime->format('c');
							}
						} else {
							$result->data[0]->start = $default;
						}
					}
					//set options to make a moving of value possible
					$result->options->editable->updateTime = true;
					//$result->options->configure = true;
					//$result->options->timeAxis->scale = 'minute';
					//$result->options->timeAxis->step = 5;
					//better do snapping in callback function!
				} else {					
					//set options to make a moving of value possible
					$result->options->editable->updateTime = true;
				}
			} else {
				//below max entries
				//check for $maxConcurrentEntries;
				$result->options->zoomMax = $seconds*$maxConcurrentEntries*1000;	
				$result->data[0]->id = 0;
				if ($fullYearExtent == true) {
					$result->data[0]->content = $startTime->format('Y');
				} else {
					$result->data[0]->content = $startTime->format('c');
				}
				$result->data[0]->start = $startTime->format('c');
				for ($i=1; $i < $numberOfDiscreteValues+1; $i++) {
					$time = $startTime->add($duration);
					//$time->setTimezone($timezone);
					$result->data[$i]->id = $i;
					if ($fullYearExtent == true) {
						$result->data[$i]->content = $time->format('Y');
						$result->data[$i]->start = $time->format('c');
					} else {
						$result->data[$i]->content = $time->format('c');
						$result->data[$i]->start = $time->format('c');
					}
				}	
				//$result->options->editable->updateTime = false;
				//$result->options->editable->updateTime = false;
				$result->options->editable = false;
			}	
			//re-initialize starttime because in case of a interval the time is incremented in the else loop before	
			$oldStartTime = new DateTime($interval[0]); 
			$result->options->min = $oldStartTime->format('c');
			$result->options->max = $endTime->format('c');
				
			$result->result->error = false;
			$result->result->message = "All done";
			break;
		case "discreteValues":
			//$e = new mb_exception("discrete values");
			if (count(explode(',',$extent)) > $maxEntries) {
				$extentArray = explode(',',$extent);
				//abort("Number of discrete values: ".count(explode(',',$extent))." exeeds max allowed number for visualization of timeline: ".$maxEntries."!");
				//todo
				//use the default value or the userValue if given
				if ($userValue !== false || $default !== false) {
					if ($userValue !== false) {
						$result->data[0]->id = 0;
						$result->data[0]->content = $userValue;
						$result->data[0]->start = $userValue;
					} else {
						$result->data[0]->id = 0;
						$result->data[0]->content = $default;
						if ($default == 'current') {
							$result->data[0]->start = $endTime->format('c');
							if ($fullYearExtent == true) {
								$result->data[0]->content = $endTime->format('Y');
							} else {
								$result->data[0]->content = $endTime->format('c');
							}
						} else {
							$result->data[0]->start = $default;
						}
					}
					//set options to make a moving of value possible
					$result->options->editable->updateTime = true;
					//$result->options->configure = true;
					//$result->options->timeAxis->scale = 'minute';
					//$result->options->timeAxis->step = 5;
					//better do snapping in callback function!
				} else {
					//set options to make a moving of value possible
					$result->options->editable->updateTime = true;
				}
			} else {
				$extentArray = explode(',',$extent);
				for ($i=0; $i < count($extentArray); $i++) {
					$result->data[$i]->id = $i;
					$result->data[$i]->content = $extentArray[$i];
					$result->data[$i]->start = $extentArray[$i];
				}
				$result->options->editable = false;
			}
			//use first and last entry as borders
			//$result->options->min = $extentArray[0];
			//$result->options->max = $extentArray[count($extentArray) - 1];
			//$result->options->editable = false;
			$result->result->error = false;
			$result->result->message = "All done";
			break;
		case "singleValue":
				//push json values
				echo $extent;
			break;
	} 
}

header('Content-Type: application/json');
echo json_encode($result);
?>
