<?php
# $Id: class_kml_geometry.php 1966 2008-01-15 08:25:15Z christoph $
# http://www.mapbender.org/index.php/class_wmc.php
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
require_once(dirname(__FILE__)."/../classes/class_RPCEndpoint.php");
require_once(dirname(__FILE__)."/../classes/class_user.php");
require_once(dirname(__FILE__)."/../classes/class_Uuid.php");

/**
 * A Mapbender user as described in the table mb_group.
 */
class Group implements RPCObject {
	/**
	 * @var Integer The Group ID
	 */
	protected $id;
	var $name;
	var $owner = 0;
	var $description ="";
	var $title;
	var $address;
	var $postcode;
	var $city;
	var $stateorprovince;
	var $country;
	var $voicetelephone;
	var $facsimiletelephone;
	var $email;
	var $logo_path;	
	var $homepage;
	var $uuid;
	var $timestamp;
	var $adminCode;
        var $ckanId;
	var $searchable;

    	static $displayName = "Group";
    	static $internalName = "group";

	/**
	 * Constructor
	 * @param groupId Integer the ID of the group that is represented by
	 * this object. If null, create an empty object
	 */
	public function __construct ($groupId) {
		//check if id is uuid or integer
		if (Uuid::isuuid($groupId)) {
			$this->uuid = $groupId;
		} else if (!is_numeric($groupId)) {
			return;
		}
		$this->id = $groupId;
		try{
			$this->load();
		}
		catch(Exception $e) {
			new mb_exception($e->getMessage());
			return;
		}
	}


	/**
	 * @return String the ID of this group
	 */
	public function __toString () {
		return (string) $this->id;
	}

	public function getId () {
		return $this->id;
	}

    /**
     * @return Assoc Array containing the fields to send to the user
     */
	public function getFields () {
		return array(
			"name" => $this->name,
			"owner" => $this->owner,
			"description" => $this->description,
			"title" => $this->title,
	        	"address" => $this->address,
	        	"postcode" => $this->postcode,
	        	"city" => $this->city,
	        	"stateorprovince" => $this->stateorprovince,
	        	"country" => $this->country,
	        	"voicetelephone" => $this->voicetelephone,
	        	"facsimiletelephone" => $this->facsimiletelephone,
	        	"email" => $this->email,
	       	 	"logo_path" => $this->logo_path,
			"homepage" => $this->homepage,
			"adminCode" => $this->adminCode,
			"uuid" => $this->uuid,
			"searchable" => $this->searchable
			//"ckanId" => $this->ckanId
		);
	}
	
	public function create() {
		if (is_null($this->name) || $this->name == "") {
			$e = new Exception("Can't create group without name");
		}
		
		db_begin();
		$uuid = new Uuid();
		$sql_group_create = "INSERT INTO mb_group (mb_group_name, uuid) VALUES ($1, $2)";
		$v = array($this->name, $uuid);
		$t = array("s","s");
		$insert_result = db_prep_query($sql_group_create, $v, $t);

		if (!$insert_result) {
			db_rollback();
			$e = new Exception("Could not insert new group");
			return false;
		}
		
		$id = db_insertid($insert_result,'mb_group','mb_group_id');
		if ($id != 0) {
			$this->id = $id;
		}
		
		$commit_result = $this->commit();
		if ($commit_result == false) {
			try {
				db_rollback();
			}
			catch (Exception $E)	{
				$newE = new Exception("Could not set inital values of new group");
				throw $newE;
			}
			return false;
		}
		
		db_commit();
		return true;
	}


	/**
	 *	@param	$changes JSON  keys and their values of what to change in the object
	 */
	public function change($changes) {
        //FIXME: validate input

		$this->name = isset($changes->name) ? $changes->name : $this->name;
		$this->owner = isset($changes->owner) ? $changes->owner : $this->owner;
		$this->description = isset($changes->description) ? $changes->description : $this->description;
		$this->id = isset($changes->id) ? $changes->id : $this->id;
     		$this->title = isset($changes->title) ? $changes->title : $this->title;
		$this->address = isset($changes->address) ? $changes->address : $this->address;
		$this->postcode = isset($changes->postcode) ? $changes->postcode : $this->postcode;
		$this->city = isset($changes->city) ? $changes->city : $this->city;
		$this->stateorprovince = isset($changes->stateorprovince) ? $changes->stateorprovince : $this->stateorprovince;
		$this->country = isset($changes->country) ? $changes->country : $this->country;
		$this->voicetelephone = isset($changes->voicetelephone) ? $changes->voicetelephone : $this->voicetelephone;
		$this->facsimiletelephone = isset($changes->facsimiletelephone) ? $changes->facsimiletelephone : $this->facsimiletelephone;
		$this->email = isset($changes->email) ? $changes->email : $this->email;
		$this->logo_path = isset($changes->logo_path) ? $changes->logo_path : $this->logo_path;
		$this->homepage = isset($changes->homepage) ? $changes->homepage : $this->homepage;
		$this->adminCode = isset($changes->adminCode) ? $changes->adminCode : $this->adminCode;
		$this->searchable = isset($changes->searchable) ? $changes->searchable : $this->searchable;
		return true;
	}

	public function commit() {

		$sql_update = "UPDATE mb_group SET ".
			"mb_group_name = $1, ".
			"mb_group_owner = $2, ".
			"mb_group_description = $3, ".
			"mb_group_title = $4, ".
			"mb_group_address = $5, ".
			"mb_group_postcode = $6, ".
			"mb_group_city = $7, ".
			"mb_group_stateorprovince = $8, ".
			"mb_group_country = $9, ".
			"mb_group_voicetelephone = $10, ".
			"mb_group_facsimiletelephone = $11, ".
			"mb_group_email = $12, ".
			"mb_group_logo_path = $13, ".
			"mb_group_homepage = $14, ".
			"mb_group_admin_code = $15, ".
			"searchable = $16 ".
			"WHERE mb_group_id = $17 ";

			$v = array(
				$this->name,
				$this->owner,
				$this->description,
				$this->title,
				$this->address,
				$this->postcode,
				$this->city,
				$this->stateorprovince,
				$this->country,
				$this->voicetelephone,
				$this->facsimiletelephone,
				$this->email,
				$this->logo_path,
				$this->homepage,
				$this->adminCode,
				$this->searchable,
				$this->id
			);

			$t = array(
				"s", "i", "s", "s", "s",
				"i", "s", "s", "s", "s", 
				"s", "s", "s", "s", "s", "b", "i"
			);

			$update_result = db_prep_query($sql_update,$v,$t);
			if(!$update_result)	{
				throw new Exception("Database error updating Group");
			}

		return true;
	}

	public function remove() {

        //throw new Exception("I AM   : ". $this->id);
        $sql_group_remove = "DELETE FROM mb_group WHERE mb_group_id = $1";
		$v = array($this->id);
		$t = array("i");
		$result = db_prep_query($sql_group_remove,$v,$t);
		if($result == false)
		{
			throw new Exception("Database error deleting group");
		}
		return true;
	}

	public function exists() {
		$sql_group = "SELECT group_id from mb_group WHERE mb_group_id = $1; ";
		$v = array($this->id);
		$t = array("i");
		$res_group = db_prep_query($sql_group,$v,$t);
		if ($row = db_fetch_array($res_group)) {
			return true;
		}
		return false;
	}

	public function load() {
		//check if uuid or id is given, give id preference
		if (isset($this->id) || isset($this->uuid)) {
			if (isset($this->id) && is_numeric($this->id)) {
				$sql_group = "SELECT * from mb_group WHERE mb_group_id = $1; ";
				$v = array($this->id);
				$t = array("i");
			} else {
				$sql_group = "SELECT * from mb_group WHERE uuid = $1; ";
				$v = array($this->uuid);
				$t = array("s");
			}
			$res_group = db_prep_query($sql_group,$v,$t);
			if($row = db_fetch_array($res_group)){

				$this->name = $row['mb_group_name'];

            			//FIXME: needs checking
            			$this->owner = $row['mb_group_owner'];
           			$this->description = $row['mb_group_description'];
            			$this->title = $row["mb_group_title"];
            			$this->address = $row["mb_group_address"];
            			$this->postcode = $row["mb_group_postcode"];
            			$this->city = $row["mb_group_city"];
            			$this->stateorprovince = $row["mb_group_stateorprovince"];
            			$this->country = $row["mb_group_country"];
            			$this->voicetelephone = $row["mb_group_voicetelephone"];
            			$this->facsimiletelephone = $row["mb_group_facsimiletelephone"];
            			$this->email = $row["mb_group_email"];
            			$this->logo_path = $row["mb_group_logo_path"];
	    			$this->homepage = $row["mb_group_homepage"];
				$this->uuid = $row["uuid"];
				$this->adminCode = $row["mb_group_admin_code"];
				$this->timestamp = $row["timestamp"];
				$this->ckanId = $row["mb_group_ckan_uuid"];
				$this->searchable = $row["searchable"];
				
			} else {
			 	throw new Exception("Group with ID " . $this->id . " does not exist.");
			}
			return true;
		} else {
			throw new Exception("Neither id nor uuid is given to select group.");
		}
	}

    /*
    * @return transform group into other representation
    * @param outputFormat string "iso19139", "rdf", "ckan"
    */
    function export($outputFormat, $givenRole = false) {
	$mappingHash = array(
		//name
		array(	groupAttribute => "name",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:organisationName/gco:CharacterString",
			deleteElementPath => false,
			ckanName => "name"
		),
		//email
		array(	groupAttribute => "email",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress/gco:CharacterString",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:electronicMailAddress",
			ckanName => "department_email"
		),
		//deliveryPoint
		array(	groupAttribute => "address",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint/gco:CharacterString",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint",
			ckanName => "department_address"
		),
		//administrativeArea
		array(	groupAttribute => "adminCode",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:administrativeArea/gmd:Country",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:administrativeArea",
			ckanName => false
		),
		//postalCode
		array(	groupAttribute => "postcode",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode/gco:CharacterString",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode",
			ckanName => "department_postcode"
		),
		//country
		array(	groupAttribute => "country",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country/gmd:Country",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:country",
			ckanName => false
		),
		//city
		array(	groupAttribute => "city",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city/gco:CharacterString",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city",
			ckanName => "department_city"
		),
		//voicetelephone
		array(	groupAttribute => "voicetelephone",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:voice",
			ckanName => false
		),
		//facsimiletelephone
		array(	groupAttribute => "facsimiletelephone",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:phone/gmd:CI_Telephone/gmd:facsimile",
			ckanName => false
		),
		//onlineResource
		array(	groupAttribute => "homepage",
			iso19139Path => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource/gmd:CI_OnlineResource/gmd:linkage/gmd:URL",
			deleteElementPath => "/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource",
			ckanName => false
		),
		//created
		array(	groupAttribute => "timestamp",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => "created"
		),
		//description
		array(	groupAttribute => "description",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => "description"
		),
		//title
		array(	groupAttribute => "title",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => array("title", "title_long")
		),
		//uuid
		array(	groupAttribute => "uuid",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => false
		),
		//ckan uuid
		array(	groupAttribute => "ckanId",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => "id"
		),
		//logo
		array(	groupAttribute => "logo_path",
			iso19139Path => false,
			deleteElementPath => false,
			ckanName => array("image_display_url", "image_url")
		)
	);
	switch ($outputFormat) {
		case "iso19139":
			$e = new mb_exception("try to export group: ".$this->name);
			//build xml snippet via dom!
			//read template
			//load xml from constraint generator
			$contactDomObject = new DOMDocument();
			$contactDomObject->load(dirname(__FILE__) . "/../geoportal/metadata_templates/mb_group_contact.xml");
			$xpathContact = new DOMXpath($contactDomObject);
			//$rootNamespace = $contactDomObject->lookupNamespaceUri($contactDomObject->namespaceURI);
			$xpathContact->registerNamespace("mb", "http://www.mapbender.org/metadata/groupcontact");

			$xpathContact->registerNamespace("gco", "http://www.isotc211.org/2005/gco");
			$xpathContact->registerNamespace("gmd", "http://www.isotc211.org/2005/gmd");


			for($a = 0; $a < count($mappingHash); $a++) {
				if (isset($this->{$mappingHash[$a]['groupAttribute']}) && $this->{$mappingHash[$a]['groupAttribute']} !== "" && $mappingHash[$a]['iso19139Path'] !== false) {
					$xpathContact->query($mappingHash[$a]['iso19139Path'])->item(0)->nodeValue = $this->{$mappingHash[$a]['groupAttribute']};
				} else {
					if ($mappingHash[$a]['deleteElementPath'] !== false) {
						//delete default element from xml!
						$temp = $xpathContact->query($mappingHash[$a]['deleteElementPath'])->item(0);
    						$temp->parentNode->removeChild($temp);
					}
				}
			}
			//if neither voice nor fax is given, delete the complete phone object!
			if ((!isset($this->facsimiletelephone) && !isset($this->voicetelephone)) || ($this->voicetelephone == '' && $this->facsimiletelephone == '')) {
				$temp = $xpathContact->query('/mb:groupcontact/gmd:CI_ResponsibleParty/gmd:contactInfo/gmd:CI_Contact/gmd:phone')->item(0);
    				$temp->parentNode->removeChild($temp);
			}
			$XML = $contactDomObject->saveXML();
	 		return $XML;
			break;
		case "rdf":
			break;
		case "ckan":
			/*
			{"users": [{"email_hash": "da6f68f26df3d76063a9ef78b90208a1", "about": null, "capacity": "admin", "name": "admin", "created": "2016-05-21T18:47:35.299335", "openid": null, "sysadmin": false, "activity_streams_email_notifications": false, "state": "active", "number_of_edits": 2, "display_name": "test", "fullname": "test", "id": "f78e4baf-ce48-4aa7-bee9-ae4069262e24", "number_created_packages": 0}, {"email_hash": "ac3c638a25dcdff84c0b6b6ac2a44164", "about": null, "capacity": "admin", "name": "lepin2001", "created": "2016-06-01T08:16:32.508740", "openid": null, "sysadmin": false, "activity_streams_email_notifications": false, "state": "active", "number_of_edits": 153, "display_name": "lepin2001", "fullname": "", "id": "04eb8d47-e167-4285-9456-1febe291be89", "number_created_packages": 1}], "display_name": "\u6843\u5712\u5e02\u653f\u5e9c\u5e02\u9577\u5ba4", "description": "", "image_display_url": "", "package_count": 0, "created": "2016-06-03T08:12:16.804838", "name": "000001", "is_organization": true, "state": "active", "extras": [], "image_url": "", "groups": [], "type": "organization", "title": "\u6843\u5712\u5e02\u653f\u5e9c\u5e02\u9577\u5ba4", "revision_id": "9a847865-477d-46ee-b3ef-407b547623f7", "num_followers": 0, "id": "000001", "tags": [], "approval_status": "approved"}}
			*/
			$jsonOutput = new stdClass();
			//display_name
			//description
			//image_display_url
			//created
			//name
			//is_organization
			//state
			//image_url
			//type
			//title
			//id
			//approval_status
			for($a = 0; $a < count($mappingHash); $a++) {
				if (isset($this->{$mappingHash[$a]['groupAttribute']}) && $this->{$mappingHash[$a]['groupAttribute']} !== "" && $mappingHash[$a]['ckanName'] !== false) {
					if ($mappingHash[$a]['ckanName'] == 'name') {
						$jsonOutput->{$mappingHash[$a]['ckanName']} = $this->specialCharsToSlug(str_replace('-','_',str_replace(' ','_',strtolower($this->{$mappingHash[$a]['groupAttribute']}))));
					} else {
						if (is_array($mappingHash[$a]['ckanName'])) {
							foreach($mappingHash[$a]['ckanName'] as $ckanAttributeName) {
								$jsonOutput->{$ckanAttributeName} = $this->{$mappingHash[$a]['groupAttribute']};
							}
						} else {
							$jsonOutput->{$mappingHash[$a]['ckanName']} = $this->{$mappingHash[$a]['groupAttribute']};
						}
					}
				}
			}
			
			$jsonOutput->state = "active";
			$jsonOutput->is_organization = true;
			$jsonOutput->type = "organization";
			$json = json_encode($jsonOutput);
			return $json;
			break;
		default: 
			return false;
			break;
	}
    }

public function specialCharsToSlug($string) {
    $string = str_replace("ä", "ae", $string);
    $string = str_replace("ü", "ue", $string);
    $string = str_replace("ö", "oe", $string);
    $string = str_replace("Ä", "Ae", $string);
    $string = str_replace("Ü", "Ue", $string);
    $string = str_replace("Ö", "Oe", $string);
    $string = str_replace("ß", "ss", $string);
    $string = str_replace("´", "", $string);
    return $string;
}

    /*
    * @return Array of Groups
    * @param $filter UNUSED! AssocArray, valid keys "id","name". Use SQL's % and _ to perform simple matching
    */
    public static function getList($filter) {

		$name = $filter->name ? $filter->name : null;
		$id = $filter->id && is_numeric($filter->id) ? 
			intval($filter->id) : null;
		$owner = $filter->owner && is_numeric($filter->owner) ? 
			intval($filter->owner) : null;
		
		$groups = Array();
		$sql_grouplist = "SELECT mb_group_id FROM mb_group";
	  
		$andConditions = array();
		$v = array();
		$t = array();

		if (!is_null($name)) {
			$v[]= $name;
			$t[]= "s";
	  		$andConditions[]= "mb_group_name LIKE $" . count($v);
		}

		if (!is_null($id)) {
			$v[]= $id;
			$t[]= "i";
	  		$andConditions[]= "mb_group_id = $" . count($v);
		}
		
		if (!is_null($owner)) {
			$v[]= $owner;
			$t[]= "i";
	  		$andConditions[]= "mb_group_owner = $" . count($v);
		}
		
		if (count($andConditions) > 0) {
			$sql_grouplist .= " WHERE " . implode("AND", $andConditions);
		}
		
		$sql_grouplist .= " ORDER BY mb_group_name";

		$res_groups = db_prep_query($sql_grouplist,$v,$t);
		
		while ($row = db_fetch_array($res_groups)) {
			try {
				$groups[] = new Group($row['mb_group_id']);
			}
			catch (Exception $E) {
				continue;
				//FIXME: should catch some errors here
			}
		}
		return $groups;
	}

    /*
    * tries to initialize a Groupobject by Name
    * @return A group Object
    * @param $name the name of the group to find
    */

    public static function byName($name) {

		if (is_null($name)) { 
			return new Group(null); 
		}

		$sql_group = "SELECT mb_group_id FROM mb_group WHERE mb_group_name = $1";
		$res_group = db_prep_query($sql_group, array($name), array("s"));
		
		if ($row = db_fetch_array($res_group)) {
			return new Group($row['mb_group_id']);
		}
		return null;
    }
	
	public function isValid () {
		if (!is_null($this->name)) {
			return true;
		}
//		new mb_warning("Group with ID " . $this->id . " does not exist.");
		return false;
	}
	
	public static function getGroupsByUser ($id) {
		$user = new User($id);
		if (!$user->isValid()) {
			new mb_exception("User ID " . $id . " invalid.");
			return array();		
		}
		$groups = $user->getGroupsByUser();
		if (!is_array($groups)) {
			new mb_notice("User " . $id . " is not member in any group.");
			return array();
		}
		return $groups;
	}
	
	public function getUser () {
		if (!$this->isValid()) {
			return array();
		}
		$sql = "SELECT fkey_mb_user_id FROM mb_user_mb_group WHERE fkey_mb_group_id = $1";
		$v = array($this->id);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		$users = array();
		while ($row = db_fetch_assoc($res)) {
			$users[]= new User($row["fkey_mb_user_id"]);
		}
		return $users;
	}

	public function getOwner () {
		if (!$this->isValid()) {
			return null;
		}
		$sql = "SELECT mb_group_owner FROM mb_group WHERE mb_group_id = $1";
		$v = array($this->id);
		$t = array("i");
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_assoc($res);
		$owner = new User(intval($row["mb_group_owner"]));
		return $owner;
	}

	}
?>
