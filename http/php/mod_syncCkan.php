<?php
// Display errors for demo
//@ini_set('error_reporting', E_ALL);
//@ini_set('display_errors', 'stdout');	
// Include class_ckanApi.php
require_once(dirname(__FILE__).'/../classes/class_ckanApi.php');
//require_once(dirname(__FILE__).'/../classes/class_ckan.php');
require_once(dirname(__FILE__).'/../classes/class_connector.php');
require_once(dirname(__FILE__).'/../../conf/ckan.conf');
// Create CKAN object
// Takes optional API key parameter. Required for POST and PUT methods.
//initial instantiation of api class
print time()."<br>";
if (defined("CKAN_SERVER_PORT") && CKAN_SERVER_PORT != '') {
	$serverUrl = CKAN_SERVER_IP.":".CKAN_SERVER_PORT;
} else {
	$serverUrl = CKAN_SERVER_IP;
}
DEFINE('SERVER_URL',$serverUrl);
$ckan = new ckanApi(API_KEY, CKAN_SERVER_IP);

$ckan->base_url='http://'.SERVER_URL.'/api/';

//echo "API-key: ".$ckan->api_key."<br>";
//echo "API-baseUrl: ".$ckan->base_url;
//get json objects from mapbender json interface
/*$mapbenderCkanUrl = "http://localhost/mapbender_trunk/php/mod_exportMapbenderLayer2CkanObjects.php";
$ckanObjectConnector = new connector($mapbenderCkanUrl);
$ckanObjects = json_decode($ckanObjectConnector->file);
header('Content-Type: application/json; charset='.CHARSET);
echo json_encode($ckanObjects);
*/
//first check if group with given name exists - if not create it in the ckan instance via action api, if it exists - update title and link to logo
$group = group_get(CKAN_GROUP_NAME);
if (!$group) {
	print "No group found, create it via action api!";
	$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
	$ckan->base_url='http://'.SERVER_URL.'/api/';
	
	//create new one
	$newGroup->name = CKAN_GROUP_NAME;
	$newGroup->title = CKAN_GROUP_TITLE;
	$newGroup->image_url = CKAN_GROUP_SYMBOL;
	//$newGroup->description = CKAN_GROUP_DESCRIPTION;
	try {
		$result = $ckan->action_group_create(json_encode($newGroup));
	}
	catch (Exception $e) {
		print '<p><strong>Caught exception: ' . $e->getMessage() . '</strong></p><br>';
	}
	if ($result->success) {
		print '<p>Group created successfully!</p><br>';
	}
	unset($ckan);
} else {
	print "Group <b>".$group->name."</b> found. Datasets will be pushed into this group.";
}
if (defined("CKAN_EXPORT_URL") && CKAN_EXPORT_URL != "") {
	$mapbenderCkanUrl = CKAN_EXPORT_URL;
} else {
	$mapbenderCkanUrl = "http://localhost/mb_trunk/php/mod_exportMapbenderLayer2CkanObjects.php";
}
//read ckan objects from source into variable
$ckanObjectConnector = new connector($mapbenderCkanUrl);
$ckanObjects = json_decode($ckanObjectConnector->file);
//build dataset array for source datasets
foreach ($ckanObjects->result as $dataset) {
	$mbDatasetArray[] =  $dataset->name;
}
print "<b>Mapbender datasets:</b><br>"; 
if (count($mbDatasetArray) == 0) {
	$mbDatasetArray = false;
}
if ($mbDatasetArray) {
	foreach ($mbDatasetArray as $dataset) {
		print $dataset."<br>";
	}
}
//die();
print "<b>ckan datasets:</b><br>"; 
//get old package names for defined group in ckan.conf file
//thru action api
$ckanDataset = group_get(CKAN_GROUP_NAME);
//$result = json_decode($resultDataset);
$ckanDatasetArray = array();
foreach ($ckanDataset->packages as $dataset) {
	$ckanDatasetArray[] = $dataset;
}
if ($ckanDatasetArray) {
	//debug output
	foreach ($ckanDatasetArray as $dataset) {
		print $dataset."<br>";
	}
}
//
//compare the arrays
//first those which can be deleted
if ($ckanDatasetArray && $mbDatasetArray) {
	$datasetToDelete = array_diff($ckanDatasetArray, $mbDatasetArray);
} else {
	if (!$ckanDatasetArray) {
		$datasetToDelete = false;
	}
	if (!$mbDatasetArray) {
		$datasetToDelete = $ckanDatasetArray;
	}
}
//show datasets to be deleted:
if ($datasetToDelete) {
	print "<b>ckan datasets to be deleted:</b><br>";
	//debug output
	foreach ($datasetToDelete as $dataset) {
		print $dataset."<br>";
	}
}
//check datasets to be updated:
if (!$ckanDatasetArray) {
	$datasetToUpdate = false;
} else {
	//get identical datasets
	$datasetToUpdate = array_intersect($mbDatasetArray,$ckanDatasetArray);
}

if ($datasetToUpdate) {
	print "<b>ckan datasets to update:</b><br>";
	foreach ($datasetToUpdate as $dataset) {
		print $dataset."<br>";
	}
} else {
	print "<b>No datasets to update!</b><br>";
}
//get datasets to be created
if ($ckanDatasetArray && $mbDatasetArray) {
	$datasetToCreate = array_diff($mbDatasetArray, $ckanDatasetArray);
} else {
	if (!$ckanDatasetArray) {
		$datasetToCreate = $mbDatasetArray;
	}
	if (!$mbDatasetArray) {
		$datasetToCreate = false;
	}
}

if ($datasetToCreate) {
	print "<b>Mapbender datasets to be created for the first time:</b><br>";
	foreach ($datasetToCreate as $dataset) {
		print $dataset."<br>";
	}
	print "<br>";
} else {
	print "<b>No datasets to create!</b><br>";
}

//identify which are identical and which are new and which are lost
//first delete the orphaned
foreach ($datasetToDelete as $datasetName) {
	$result = package_delete($datasetName,CKAN_API_UPDATE);
	if ($result) {
		print "Package ".$datasetName." deleted successfully!<br>";
	} else {
		print "Package ".$datasetName." could not be deleted!<br>";
	}
}
if ($datasetToUpdate) {
	print "<b>Update of datasets</b><br>";
}
//second update the identical ones
foreach ($datasetToUpdate as $datasetName) {
	//get dataset from object
	$index = array_search($datasetName,$mbDatasetArray);
	if (is_int($index)) {
		$newPackage = $ckanObjects->result[$index];
		//change group field if not the action api is requested!
		if (CKAN_API_UPDATE == 2 || CKAN_API_UPDATE == 1) {
			for ($i=0;$i < count($newPackage->groups);$i++) {
				$newPackage->groups[$i] = $newPackage->groups[$i]->name;
			}
			for ($i=0;$i < count($newPackage->tags);$i++) {
				$newPackage->tags[$i] = $newPackage->tags[$i]->name;
			}
		}
		//update it with action api
		$result = package_update ($newPackage,CKAN_API_UPDATE);
		if ($result) {
			print "Dataset ".$datasetName." successfully updated!<br>";
			//print json_encode($result);
		} else {
			print "Could not update dataset ".$datasetName."!<br>";
		}
	} 
}
//second create the new ones 
foreach ($datasetToCreate as $datasetName) {
	//get dataset from object
	$index = array_search($datasetName,$mbDatasetArray);
	if (is_int($index)) {
		$newPackage = $ckanObjects->result[$index];
		//create it with action api
		$result = package_create ($newPackage,CKAN_API_CREATE);
		if ($result) {
			print "Dataset ".$datasetName." successfully created!<br>";
			//print json_encode($result);
		} else {
			print "Could not create dataset ".$datasetName."!<br>";
		}
	} 
}

print time()."<br>";

//functions for interacting with ckan apis
function package_get ($packageName) {
	try {
		$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
		$ckan->base_url='http://'.SERVER_URL.'/api/';
		$idArray = array ('id'=>$packageName);
		$resultDataset = $ckan->action_package_show(json_encode($idArray));
		if ($resultDataset->success) {
			return $resultDataset->result;
		} else {
			return false;
		}
	}
	catch (Exception $e)
	{
		$error = new mb_exception('mod_syncCkan.php: Caught exception: '.$e->getMessage());
		return false;
	}
}

function group_get ($groupName) {
	try {
		$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
		$ckan->base_url='http://'.SERVER_URL.'/api/1/';
		$resultDataset = $ckan->get_group_entity($groupName);
		return $resultDataset;
	}
	catch (Exception $e)
	{
		$error = new mb_exception('mod_syncCkan.php: Caught exception: '.$e->getMessage());
		return false;
	}
}

function package_update ($package, $apiVersion) {
	//check if package already exists
	$existingPackage = package_get ($package->name);
	if ($existingPackage) {
		switch ($apiVersion) {
			case 3:
				//update it ;-)
				$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
				$ckan->base_url='http://'.SERVER_URL.'/api/';
				try {
					$result = $ckan->action_package_update(json_encode($package));
				}
				catch (Exception $e) {
					$error = new mb_exception('mod_syncCkan.php: update dataset http_code:'.$e->getMessage());
				}
				if ($result->success) {
					$result = $result->result;
				} else {
					$error = new mb_exception('mod_syncCkan.php: error when trying to update dataset!');
					$result = false;
				}
				return $result;
			break;
			case 1:	
				//update it with api v1 ;-)
				$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
				$ckan->base_url='http://'.SERVER_URL.'/api/1/';
				try {
					$result = $ckan->put_package_entity($package->name,json_encode($package));
				}
				catch (Exception $e) {
					$error = new mb_exception('mod_syncCkan.php: update dataset http_code:'.$e->getMessage());
					return false;
				}
				return $result;
			break;
			case 2:	
				//update it with api v2 ;-)
				$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
				$ckan->base_url='http://'.SERVER_URL.'/api/2/';
				$error = new mb_exception('mod_syncCkan.php: '.$package->groups[0]);
				try {
					$result = $ckan->post_package_register($existingPackage->id,json_encode($package));
				}
				catch (Exception $e) {
					$error = new mb_exception('mod_syncCkan.php: update dataset http_code:'.$e->getMessage());
					return false;
				}
				return $result;
			break;
		}
	} else {
		$error = new mb_exception('mod_syncCkan.php: No package found to update!');
		return false;
	}	
}

function package_create ($package,$apiVersion) {
	//check if package already exists
	$existingPackage = package_get ($package->name);
	if ($existingPackage) {
		//check if existing was deleted
		$error = new mb_exception('mod_syncCkan.php: There is already an existing package with name: '.$package->name);
		if ($existingPackage->status == 'deleted') {
			$error = new mb_exception('mod_syncCkan.php: with status \'deleted\'');
		} else {
			$error = new mb_exception('mod_syncCkan.php: with status \'active\'');
		}	
		return false;
	} else {
		//package insertion
		$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
		switch ($apiVersion) {
			case 1:
				$ckan->base_url='http://'.SERVER_URL.'/api/1/';
				try {
					$resultDataset = $ckan->post_package_register(json_encode($package));
					if ($resultDataset->success) {
						$result = $resultDataset->result;
					} else {
						$error = new mb_exception('mod_syncCkan.php: error when trying to create dataset!');
						$result = false;
					}
				}
				catch (Exception $e) {
					$error = new mb_exception('mod_syncCkan.php: http_code:'.$e->getMessage());
					$result = $e->getMessage();
				}
			break;
			case 3:
				$ckan->base_url='http://'.SERVER_URL.'/api/';
				try {
					$resultDataset = $ckan->action_package_create(json_encode($package));
					if ($resultDataset->success) {
						$result = $resultDataset->result;
					} else {
						$error = new mb_exception('mod_syncCkan.php: error when trying to create dataset!');
						$result = false;
					}
				}
				catch (Exception $e) {
					$error = new mb_exception('mod_syncCkan.php: http_code:'.$e->getMessage());
				}
			break;
		}
		if ($result) {
			return $result;
		} else {
			return false;
		}
	}
		
}

function get_packages_by_group($groupname) {
	//by action api
	$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
	$ckan->base_url='http://'.SERVER_URL.'/api/';
	$datasetNames = array();
	try {
		$groupArray = array ('id'=>$groupname);
		$result = $ckan->action_group_package_show(json_encode($groupArray));
		if ($result->success) {
			foreach ($result->result as $dataset) {
				$datasetNames[] = $dataset->name;
			}
		} else {
			return false;
		}
	}
	catch (Exception $e) {
		$error = new mb_exception('mod_syncCkan.php: get_packages_by_group: http_code:'.$e->getMessage());
	}
	return $datasetNames;
}

function get_packages_by_group2($groupname) {
	//by action api
	unset($ckanApi);
	$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
	$ckan->set_base_url();
	$e = new mb_exception("testckan: ".SERVER_URL);
	$ckan->base_url='http://'.SERVER_URL.'/api/rest';
	$e = new mb_exception("testckan: ".$ckan->base_url);
	$datasetNames = array();
	try {
		$result = $ckan->get_group_by_name($groupname);
	}
	catch (Exception $e) {
		$error = new mb_exception('mod_syncCkan.php: get_packages_by_group: http_code:'.$e->getMessage());
		return false;
	}
	foreach ($result->packages as $dataset) {
		$datasetNames[] = $dataset;
	}
	return $datasetNames;
}

function package_delete($packageName,$apiVersion) {
	//check if package already exists
	$existingPackage = package_get ($packageName);
	if ($existingPackage) {
		//check if existing was deleted
		$error = new mb_exception('mod_syncCkan.php: Following package will be deleted: '.$package->name);
		if ($existingPackage->state == 'deleted') {
			$error = new mb_exception('mod_syncCkan.php: package was deleted already nothing to be done!');
			return true;
		} else {
			$error = new mb_exception('mod_syncCkan.php: Start deleting it!');
			switch ($apiVersion) {
				case 3:
					//update the name and status of package to delete:
					$existingPackage->state = "deleted";
					$existingPackage->name = $existingPackage->name."_".time();
					//some special elements for rhineland palatinate dataset validation:
					/*Validation error: "{'__type': 'Validation Error', 'point_of_contact': [u'Missing value'], 'content_type': [u'Missing value'], 'point_of_contact_free_address': [u'At least one value must be specified'], 'point_of_contact_email': [u'At least one value must be specified'], 'other_terms_of_use': [u'Missing value'], 'point_of_contact_url': [u'At least one value must be specified']}"*/ 
					//- don't help delete it with api 2 
					//
					//$existingPackage->point_of_contact = "dummy";
					//$existingPackage->content_type = "dummy";
					//$existingPackage->point_of_contact_free_address = "dummy";
					//$existingPackage->point_of_contact_email = "dummy";
					//$existingPackage->other_terms_of_use = "dummy";
					//$existingPackage->point_of_contact_url = "http://www.geoportal.rlp.de";
					try {
						$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
						$ckan->base_url='http://'.SERVER_URL.'/api/';
						$result = $ckan->action_package_update(json_encode($existingPackage));
					}
					catch (Exception $e) {
						$error = new mb_exception('mod_syncCkan.php: get_packages_by_group: http_code:'.$e->getMessage());
					}
					if ($result->success) {
						return true;
					} else {
						return false;
					}
				break;
				case 2:
					//the existing package was called by action api therefor group will be defined as dict and be flattened before update
					//TODO: check if api 2 uses dict for group or not!
					for ($i=0;$i < count($existingPackage->groups);$i++) {
						//$error = new mb_exception('mod_syncCkan.php: groups['.$i.']->name old:'.$existingPackage->groups[$i]->name);
						
						$existingPackage->groups[$i] = $existingPackage->groups[$i]->name;
						//$error = new mb_exception('mod_syncCkan.php: groups['.$i.'] new:'.$existingPackage->groups[$i]);
					}
					for ($i=0;$i < count($existingPackage->tags);$i++) {
						$existingPackage->tags[$i] = $existingPackage->tags[$i]->name;
					}
					$package->point_of_contact = "dummy";
					$package->content_type = "dummy";
					$package->point_of_contact_free_address = "dummy";
					$package->point_of_contact_email = "dummy@dummy.de";
					$package->point_of_contact_url = "http://test.de";
					$package->other_terms_of_use = "dummy";
					//$package->extras = null;
					//update the name and status of package to delete:
					$package->state = "deleted";
					$package->name = $existingPackage->name."_".time();
					//update it with api v2 ;-)
					$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
					$ckan->base_url='http://'.SERVER_URL.'/api/2/';
					try {
						$result = $ckan->post_package_register($existingPackage->id,json_encode($package));
					}
					catch (Exception $e) {
						$error = new mb_exception('mod_syncCkan.php: update dataset http_code:'.$e->getMessage());
						return false;
					}
					return $result;
				break;
				case 1:	
					//update it with api v1 ;-)
					for ($i=0;$i < count($existingPackage->groups);$i++) {	
						$existingPackage->groups[$i] = $existingPackage->groups[$i]->name;
					}
					for ($i=0;$i < count($existingPackage->tags);$i++) {	
						$existingPackage->tags[$i] = $existingPackage->tags[$i]->name;
					}
					//some rp specials for deleting non conformant datasets - which maybe generated by hand!
					/*2013-02-23 21:37:57,704 ERROR [ckan.controllers.api] Validation error: "{'point_of_contact': [u'Missing value'], 'content_type': [u'Missing value'], 'point_of_contact_free_address': [u'At least one value must be specified'], 'point_of_contact_email': [u'At least one value must be specified'], 'point_of_contact_url': [u'At least one value must be specified']}"*/

					//create complete dummy object:
					
					$package->point_of_contact = "dummy";
					$package->content_type = "dummy";
					$package->point_of_contact_free_address = "dummy";
					$package->point_of_contact_email = "dummy@dummy.de";
					$package->point_of_contact_url = "http://test.de";
					$package->other_terms_of_use = "dummy";
					//$package->extras = null;
					//update the name and status of package to delete:
					$package->state = "deleted";
					$package->name = $existingPackage->name."_".time();
					$ckan = new ckanApi(API_KEY,CKAN_SERVER_IP);
					$ckan->base_url='http://'.SERVER_URL.'/api/1/';
					try {
						$result = $ckan->put_package_entity($packageName,json_encode($package));
					}
					catch (Exception $e) {
						$error = new mb_exception('mod_syncCkan.php: update dataset http_code:'.$e->getMessage());
						return false;
					}
					return $result;
				break;
			}
		}	
	} else {
		$error = new mb_exception('mod_syncCkan.php: No existing package found, deleting not needed!');
		return true;
	}
}

function create_dummy_object() {
	return $dummyObject;
}
?>
