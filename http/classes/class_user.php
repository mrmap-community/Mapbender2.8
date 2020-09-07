<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License
# and Simplified BSD license.
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../classes/class_RPCEndpoint.php");
require_once(dirname(__FILE__)."/../classes/class_administration.php");
require_once(dirname(__FILE__)."/../classes/class_Uuid.php");
require_once(dirname(__FILE__)."/../../lib/spatial_security.php");

/**
 * A Mapbender user as described in the table mb_user.
 */
class User implements RPCObject{
	/**
	 * @var Integer The User ID
	 */
	var $id;
	var $name = "";
	// var $password = ""; // password is readonly,
	var $owner = 0;
	var $description ="";
	var $loginCount;
	var $email = "";
	var $phone ="";
	var $department ="";
	var $resolution = 72;
	var $organization ="";
	var $position = "";
	var $phone1 = "";
	var $fax = "";
	var $deliveryPoint ="";
	var $city ="";
	var $postalCode = null;
	var $country ="";
	var $url ="";
	var $realName = "";
	var $street = "";
	var $houseNumber = "";
	var $reference = "";
	var $forAttentionOf = "";
    var $validFrom = null;
    var $validTo = null;
    var $passwordTicket = "";
	var $firstName = "";
	var $lastName = "";
	var $academicTitle = "";
    var $activationKey = "";
	var $isActive = 'f';
	var $createDigest = 'f';
	var $preferredGui = '';
	//new 2020-03-18 for compatibility to older typo3 based portal systems
	var $wantsNewsletter = 'f';
	var $allowsSurvey = 'f';
	//var $wantsSpatialSuggest = "nein"; //TODO bad model - should be boolean
	var $wantsSpatialSuggest = 'f';
	//var $wantsGlossar = "nein"; //TODO bad model - should be boolean
	var $wantsGlossar = 'f';
	var $textSize = "textsize3";
	var $spatialSecurity = "";
	
  static $displayName = "User";
  static $internalName = "user";

	/**
	 * Constructor
	 * @param $userId Integer 	the ID of the user that	is represented by
	 * 							this object.
	 */
	public function __construct () {
		if (func_num_args() === 1) {
			$this->id = intval(func_get_arg(0));
		}
		else {
			$this->id = Mapbender::session()->get("mb_user_id");
			if ($this->id == '' || !isset($this->id)) {
				$this->id = (integer)PUBLIC_USER;
				$e = new mb_notice("class_user: no user_id found in session use PUBLIC_USER with id - ".PUBLIC_USER." - !");
			}
		}
		try{
			$this->load();
		}
		catch(Exception $E)	{
			new mb_exception($E->getMessage());
		}

		$this->returnObject = new stdClass(); // default object for returning information from function calls
	/*
	//maybe helpful: https://docs.ckan.org/en/ckan-2.7.3/api/
	{
	    "help": "Creates a package",
	    "success": false,
	    "error": {
	        "message": "Access denied",
	        "__type": "Authorization Error"
        	}
 	}
	//example for returned array - associated arrays are json objects!
	{
	    "help": "Creates a package",
	    "success": true,
	    "result": {
		[
	       	 	{"key1": "value1"},
			{"key2": "value1"},
        	],
		[
			{"key1": "value1"},
			{"key2": "value1"}
		]
 	}

	*/
	}


	/**
	 * @return String the ID of this user
	 */
	public function __toString () {
		return (string) $this->id;
	}


    /*
    * @return Assoc Array containing the fields to send to the user
    */
    public function getFields() {
        $result = array(
			"name" => $this->name,
			"password" =>  "*************",
			"owner" => $this->owner,
			"description" => $this->description,
			"loginCount" => $this->loginCount,
			"email" => $this->email,
			"phone" => $this->phone,
			"department" => $this->department,
			"resolution" => $this->resolution,
			"organization" => $this->organization,
			"position" => $this->position,
			"phone1" => $this->phone1,
			"fax" => $this->fax,
			"deliveryPoint" => $this->deliveryPoint,
			"city" => $this->city,
			"postalCode" => $this->postalCode,
			"country" => $this->country,
			"url" => $this->url,
			"realName" => $this->realName,
			"street" => $this->street,
			"houseNumber" => $this->houseNumber,
			"reference" => $this->reference,
			"forAttentionOf" => $this->forAttentionOf,
			"validFrom" => $this->validFrom,
			"validTo" => $this->validTo,
			"passwordTicket" => $this->passwordTicket,
			"firstName" => $this->firstName,
			"lastName" => $this->lastName,
			"academicTitle" => $this->academicTitle,
			"activationKey" => $this->activationKey,
			"isActive" => $this->isActive,
			"createDigest" => $this->createDigest,
			"preferredGui" => $this->preferredGui,
        	"textSize" => $this->textSize,
        	"wantsGlossar" => $this->wantsGlossar,
        	"wantsSpatialSuggest" => $this->wantsSpatialSuggest,
        	"allowsSurvey" => $this->allowsSurvey,
        	"wantsNewsletter" => $this->wantsNewsletter,
        	"spatialSecurity" => $this->spatialSecurity
        );
		return $result;
	}

	public function isPublic () {
		if (defined("PUBLIC_USER") && intval($this->id) === intval(PUBLIC_USER)) {
			return true;
		}
		return false;
	}

	public function getGroupsByUser () {
		$sql = "SELECT fkey_mb_group_id FROM mb_user_mb_group WHERE fkey_mb_user_id = $1";
		$v = array($this->id);
		$t = array("i");
		$result = db_prep_query($sql,$v,$t);

		$groupArray = array();
		while ($row = db_fetch_array($result)) {
			$groupArray[]= intval($row["fkey_mb_group_id"]);
		}
		return $groupArray;
	}

	public function create() {
		if ($this->name === "") {
			$e = new Exception("Can' t create user without name");
		}
		$uuid = new Uuid();

		$sql_user_create = "INSERT INTO mb_user (mb_user_name, uuid, activation_key, is_active) VALUES ( $1 , $2 , $3, $4)";
		$v = array($this->name, $uuid, md5($uuid), 'f');
		$t = array("s","s","s","b");

		db_begin();

		$insert_result = db_prep_query($sql_user_create, $v, $t);
		if($insert_result == false)	{
			db_rollback();
			throw new Exception("Could not insert new user");
		}

		$id = db_insertid($insert_result,'mb_user','mb_user_id');
		if ($id != 0) {
			$this->id = $id;
		}

		$commit_result = $this->commit();
		if($commit_result == false)	{
			try {
				db_rollback();
			}
			catch(Exception $E)	{
				throw new Exception("Could not set inital values of new user");
			}
		}
		db_commit();
        return true;
	}


	/*
	*	@param	$changes JSON  keys and their values of what to change in the object
	*/
	public function change($changes) {
        //FIXME: validate input
        if($changes->owner) {
          $owner = User::byName($changes->owner);
        }
		$this->name = isset($changes->name) ? $changes->name : $this->name;
		$this->owner = isset($changes->owner) ? $owner->id : $this->owner;
		$this->description = isset($changes->description) ? $changes->description : $this->description;
		$this->loginCount = isset($changes->loginCount) ? $changes->loginCount : $this->loginCount;
		$this->email = isset($changes->email) ? $changes->email : $this->email;
		$this->phone = isset($changes->phone) ? $changes->phone : $this->phone;
		$this->department = isset($changes->department) ? $changes->department : $this->department;
		$this->resolution = isset($changes->resolution) ? $changes->resolution : $this->resolution;
		$this->organization = isset($changes->organization) ? $changes->organization : $this->organization;
		$this->position = isset($changes->position) ? $changes->position : $this->position;
		$this->phone1 = isset($changes->phone1) ? $changes->phone1 : $this->phone1;
		$this->facsimile = isset($changes->facsimile) ? $changes->facsimile : $this->facsimile;
		$this->deliveryPoint = isset($changes->deliveryPoint) ? $changes->deliveryPoint : $this->deliveryPoint;
		$this->city = isset($changes->city) ? $changes->city : $this->city;
		$this->postalCode = isset($changes->postalCode) ? $changes->postalCode : $this->postalCode;
		$this->country = isset($changes->country) ? $changes->country : $this->country;
		$this->url = isset($changes->url) ? $changes->url : $this->url;
		$this->id = isset($changes->id) ? $changes->id : $this->id;
		$this->realName = isset($changes->realName) ? $changes->realName : $this->realName;
		$this->street = isset($changes->street) ? $changes->street : $this->street;
		$this->houseNumber = isset($changes->houseNumber) ? $changes->houseNumber : $this->houseNumber;
		$this->reference = isset($changes->reference) ? $changes->reference : $this->reference;
		$this->forAttentionOf = isset($changes->forAttentionOf) ? $changes->forAttentionOf : $this->forAttentionOf;
		$this->validFrom = isset($changes->validFrom) ? $changes->validFrom : $this->validFrom;
		$this->validTo = isset($changes->validTo) ? $changes->validTo : $this->validTo;
		$this->passwordTicket = isset($changes->passwordTicket) ? $changes->passwordTicket : $this->passwordTicket;
		$this->firstName = isset($changes->firstName) ? $changes->firstName : $this->firstName;
		$this->lastName = isset($changes->lastName) ? $changes->lastName : $this->lastName;
		$this->academicTitle = isset($changes->academicTitle) ? $changes->academicTitle : $this->academicTitle;
		$this->activationKey = isset($changes->activationKey) ? $changes->activationKey : $this->activationKey;
		$this->isActive = isset($changes->isActive) ? $changes->isActive : $this->isActive;
		$this->createDigest = isset($changes->createDigest) ? $changes->createDigest : $this->createDigest;
		$this->preferredGui = isset($changes->preferredGui) ? $changes->preferredGui : $this->preferredGui;
		$this->textSize= isset($changes->textSize) ? $changes->textSize : $this->textSize;
		$this->wantsGlossar = isset($changes->wantsGlossar) ? $changes->wantsGlossar : $this->wantsGlossar;
		$this->wantsSpatialSuggest = isset($changes->wantsSpatialSuggest) ? $changes->wantsSpatialSuggest : $this->wantsSpatialSuggest;
		$this->allowsSurvey = isset($changes->allowsSurvey) ? $changes->allowsSurvey : $this->allowsSurvey;
		$this->wantsNewsletter = isset($changes->wantsNewsletter) ? $changes->wantsNewsletter : $this->wantsNewsletter;
		$this->spatialSecurity = isset($changes->spatialSecurity) ? $changes->spatialSecurity : $this->spatialSecurity;
		return true;
	}

	public function commit() {

		$sql_update = "UPDATE mb_user SET ".
			"mb_user_name = $1, ".
			"mb_user_owner = $2, ".
			"mb_user_description = $3, ".
			"mb_user_email = $4, ".
			"mb_user_phone = $5, ".
			"mb_user_department = $6, ".
			"mb_user_resolution = $7, ".
			"mb_user_organisation_name = $8, ".
			"mb_user_position_name = $9, ".
			"mb_user_phone1 = $10, ".
			"mb_user_facsimile = $11, ".
			"mb_user_delivery_point = $12, ".
			"mb_user_city = $13, ".
			"mb_user_postal_code = $14, ".
			"mb_user_country = $15, ".
			"mb_user_online_resource = $16, ".
		 	"mb_user_realname = $17, ".
			"mb_user_street = $18, ".
			"mb_user_housenumber = $19, ".
			"mb_user_reference =$20, ".
			"mb_user_for_attention_of = $21, ".
			"mb_user_valid_from = $22, ".
			"mb_user_valid_to = $23, ".
			"mb_user_password_ticket = $24, ".
			"mb_user_firstname = $25, " .
			"mb_user_lastname = $26, " .
			"mb_user_academictitle = $27, " .
			"mb_user_login_count = $28, " .
			"activation_key = $29, " .
			"is_active = $30, " .
			"create_digest = $31, " .
			"fkey_preferred_gui_id = $32, " .
			
			"mb_user_textsize = $33, " .
			"mb_user_allow_survey = $34, " .
			"mb_user_glossar_1 = $35, " .
			"mb_user_newsletter = $36, " .
			"mb_user_spatial_suggest_1 = $37 " .
			
			"WHERE mb_user_id = $38;";

		if ($this->isActive !== 't') {$this->isActive = 'f';}
		if ($this->createDigest !== 't') {$this->createDigest = 'f';}
		
		if ($this->wantsNewsletter !== 't') {$this->wantsNewsletter = 'f';}
		if ($this->wantsSpatialSuggest !== 't') {$this->cwantsSpatialSuggest = 'f';}
		if ($this->allowsSurvey !== 't') {$this->allowsSurvey = 'f';}
		if ($this->wantsGlossar !== 't') {$this->wantsGlossar = 'f';}
		
		$v = array(
			$this->name,
			is_numeric($this->owner) ? intval($this->owner) : null,
			$this->description !== "" ? $this->description : null,
			$this->email !== "" ? $this->email : null,
			$this->phone !== "" ? $this->phone : null,
			$this->department !== "" ? $this->department : null,
			is_numeric($this->resolution) ? intval($this->resolution) : null,
			$this->organization !== "" ? $this->organization : null,
			$this->position !== "" ? $this->position : null,
			$this->phone1 !== "" ? $this->phone1 : null,
			$this->fax !== "" ? $this->fax : null,
			$this->deliveryPoint !== "" ? $this->deliveryPoint : null,
			$this->city !== "" ? $this->city : null,
			is_numeric($this->postalCode) ? intval($this->postalCode) : null,
			$this->country !== "" ? $this->country : null,
			$this->url !== "" ? $this->url : null,
			$this->realName !== "" ? $this->realName : null,
			$this->street !== "" ? $this->street : null,
			$this->houseNumber !== "" ? $this->houseNumber : null,
			$this->reference !== "" ? $this->reference : null,
			$this->forAttentionOf !== "" ? $this->forAttentionOf : null,
			$this->validFrom,
			$this->validTo,
			$this->passwordTicket !== "" ? $this->passwordTicket : null,
			$this->firstName,
			$this->lastName,
			$this->academicTitle,
			is_numeric($this->loginCount) ? intval($this->loginCount) : 0,
			$this->activationKey !== "" ? $this->activationKey : null,
			$this->isActive,
			$this->createDigest,
			$this->preferredGui,
				
			$this->textSize !== "" ? $this->textSize : null,
			$this->allowsSurvey,
			$this->wantsGlossar,
			$this->wantsNewsletter,
			$this->wantsSpatialSuggest,	
				
			is_numeric($this->id) ? intval($this->id) : null,
		);

		$t = array(
			"s", "i", "s", "s", "s",
			"s", "i", "s", "s", "s",
			"s", "s", "s", "i", "s",
			"s", "s", "s", "s", "s",
			"s", "s", "s", "s", "s",
			"s", "s", "i", "s", "b",
			"b", "s", "s", "b", "b",
			"b", "b", "i"
		);

		$update_result = db_prep_query($sql_update,$v,$t);
		if(!$update_result)	{
			throw new Exception("Database error updating User");
			return false;
		}
		spatial_security\database_write("user", $this->id, $this->spatialSecurity);
		return true;
	}

	public function remove() {

		$sql_user_remove = "DELETE FROM mb_user WHERE mb_user_id = $1";
		$v = array($this->id);
		$t = array("i");
		$result = db_prep_query($sql_user_remove,$v,$t);

		if($result == false) {
			$e = new mb_exception("Database error deleting user");
		}
		return true;
	}
    //TODO - check spatial security?
	public function load() {
		$sql_user = "SELECT * from mb_user WHERE mb_user_id = $1; ";
		$v = array($this->id);
		$t = array("i");
		$res_user = db_prep_query($sql_user,$v,$t);
		if ($row = db_fetch_array($res_user)) {
			$this->name = $row['mb_user_name'];
			$this->owner = $row['mb_user_owner'];
			$this->description	= $row['mb_user_description'];
			$this->loginCount = $row['mb_user_login_count'];
			$this->email = $row['mb_user_email'];
			$this->phone = $row['mb_user_phone'];
			$this->department = $row['mb_user_department'];
			$this->resolution = $row['mb_user_resolution'];
			$this->organization = $row['mb_user_organisation_name'];
			$this->position = $row['mb_user_position_name'];
			$this->phone1 = $row['mb_user_phone1'];
			$this->fax = $row['mb_user_facsimile'];
			$this->deliveryPoint = $row['mb_user_delivery_point'];
			$this->city = $row['mb_user_city'];
			$this->postalCode = $row['mb_user_postal_code'];
			$this->country = $row['mb_user_country'];
			$this->url = $row['mb_user_online_resource'];
			$this->realName = $row['mb_user_realname'];
			$this->street = $row['mb_user_street'];
			$this->houseNumber = $row['mb_user_housenumber'];
			$this->reference = $row['mb_user_reference'];
			$this->forAttentionOf = $row['mb_user_for_attention_of'];
			$this->validFrom = $row['mb_user_valid_from'];
			$this->validTo = $row['mb_user_valid_to'];
			$this->passwordTicket = $row['mb_user_password_ticket'];
			$this->activationKey = $row['activation_key'];
			switch ($row['is_active']) {
				case "t":
					$this->isActive = 't';
					break;
				case "f":
					$this->isActive = 'f';
					break;
				default:
					$this->isActive = 'f';
					break;
			}
			switch ($row['create_digest']) {
				case "t":
					$this->createDigest = 't';
					break;
				case "f":
					$this->createDigest = 'f';
					break;
				default:
					$this->createDigest = 'f';
					break;
			}
			$this->firstName = $row["mb_user_firstname"];
			$this->lastName = $row["mb_user_lastname"];
			$this->academicTitle = $row["mb_user_academictitle"];
			$this->preferredGui = $row["fkey_preferred_gui_id"];
			
			$this->textSize = $row["mb_user_textsize"];
			
			switch ($row['mb_user_glossar_1']) {
				case "t":
					$this->wantsGlossar = 't';
					break;
				case "f":
					$this->wantsGlossar = 'f';
					break;
				default:
					$this->wantsGlossar = 'f';
					break;
			}
			switch ($row['mb_user_spatial_suggest_1']) {
				case "t":
					$this->wantsSpatialSuggest = 't';
					break;
				case "f":
					$this->wantsSpatialSuggest = 'f';
					break;
				default:
					$this->wantsSpatialSuggest = 'f';
					break;
			}
			switch ($row['mb_user_newsletter']) {
				case "t":
					$this->wantsNewsletter = 't';
					break;
				case "f":
					$this->wantsNewsletter = 'f';
					break;
				default:
					$this->wantsNewsletter = 'f';
					break;
			}
			switch ($row['mb_user_allow_survey']) {
				case "t":
					$this->allowsSurvey = 't';
					break;
				case "f":
					$this->allowsSurvey = 'f';
					break;
				default:
					$this->allowsSurvey = 'f';
					break;
			}
			$this->spatialSecurity = spatial_security\database_read("user", $this->id);
		}
		else {
			 throw new Exception("no such User");
			 return false; //TODO why this not return false for spatial-security?
		}
		return true;
	}

	/*
	*	@param	$userId the Mapbender user id
	*	@param	$userTicket a user password ticket
	*/
	public function validUserPasswordTicket($userTicket) {
		$sql = "SELECT * FROM mb_user ";
		$sql .= "WHERE mb_user_id = $1 AND mb_user_password_ticket = $2";
	    	$v = array($this->id, $userTicket);
		$t = array("i","s");
		$res = db_prep_query($sql,$v,$t);
		if($row = db_fetch_array($res)){
			if($row['mb_user_password_ticket'] == '' || $row['mb_user_password_ticket'] != $userTicket) {
				return false;
			}
		}
		else {
			throw new Exception("Database error validating user ticket.");
			return false;
		}
		return true;
	}

	/*
	*	@param	$newPassword values of the new password
	*	@param	$newPassword Mapbender user id
	*	@param	$newPassword Mapbender user ticket
	*/
	public function setPassword($newPassword, $userTicket, $hashAlgo = 'MD5') {
		//set new password in db
		//new in 2019 - only set hashed password if create_digest is true!
		if ($this->createDigest == 't') {
			$sql = "UPDATE mb_user SET password = $1, mb_user_password_ticket = '', mb_user_digest_hash = $2, mb_user_digest = $3,";
			$sql .= " mb_user_aldigest = $4  WHERE mb_user_id = $5 AND mb_user_password_ticket = $6";
			$v = array(password_hash($newPassword, PASSWORD_BCRYPT), $hashAlgo, hash(strtolower($hashAlgo), $this->name.";".$this->email.":".REALM.":".$newPassword), hash(strtolower($hashAlgo), $this->name.":".REALM.":".$newPassword), $this->id, $userTicket);
			$t = array('s','s','s','s','i','s');
		} else {
			$sql = "UPDATE mb_user SET password = $1, mb_user_password_ticket = '' WHERE mb_user_id = $2 AND mb_user_password_ticket = $3";
			$v = array(password_hash($newPassword, PASSWORD_BCRYPT), $this->id, $userTicket);
			$t = array('s','i','s');
		}
		$update_result = db_prep_query($sql,$v,$t);
		if (!$update_result)	{
			throw new Exception("Database error updating user password");
			return false;
		}
		return true;
	}

	/*
	*	@param	$newPassword values of the new password - the class have to been invoked before to have a user->id !
	*/
	public function setPasswordWithoutTicket($newPassword, $hashAlgo = 'MD5') {
		//set new password in db
		//new in 2019 - only set hashed password if create_digest is true!
		if ($this->createDigest == 't') {
			$sql = "UPDATE mb_user SET password = $1, mb_user_password_ticket = '', mb_user_digest_hash = $2, mb_user_digest = $3,";
			$sql .= " mb_user_aldigest = $4  WHERE mb_user_id = $5";
			$v = array(password_hash($newPassword, PASSWORD_BCRYPT), $hashAlgo, hash(strtolower($hashAlgo), $this->name.";".$this->email.":".REALM.":".$newPassword), hash(strtolower($hashAlgo), $this->name.":".REALM.":".$newPassword), $this->id);
			$t = array('s','s','s','s','i');
		} else {
			$sql = "UPDATE mb_user SET password = $1, mb_user_password_ticket = '' WHERE mb_user_id = $2";
			$v = array(password_hash($newPassword, PASSWORD_BCRYPT), $this->id);
			$t = array('s','i');
		}
		$update_result = db_prep_query($sql,$v,$t);
		if (!$update_result)	{
			throw new Exception("Database error updating user password");
			return false;
		}
		return true;
	}


	public function setNewUserPasswordTicket () {
		$sql = "UPDATE mb_user SET mb_user_password_ticket = $1";
		$sql.=" WHERE mb_user_id = $2";
		$passwordTicket = substr(md5(uniqid(rand())),0,30);
		$v = array($passwordTicket,$this->id);
		$t = array('s','i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			$e= new mb_exception(1);
			throw new Exception("Error setting new user password ticket");
			return false; //TODO -check why false from spatial-security?
		}
		$this->passwordTicket = $passwordTicket;
		return true;
	}

	public function checkDjango(){
		if (defined("DJANGO_PORTAL") && DJANGO_PORTAL == true) {
			//TODO - get url from django!
			if($_SERVER["HTTPS"] != "on") {
				$loginRedirectUrl = "http://".$_SERVER['HTTP_HOST']."/login/";
				$activateRedirectUrl = "http://".$_SERVER['HTTP_HOST']."/activate/";
				$registerRedirectUrl = "http://".$_SERVER['HTTP_HOST']."/register/";
			} else {
				$loginRedirectUrl = "https://".$_SERVER['HTTP_HOST']."/login/";
				$activateRedirectUrl = "https://".$_SERVER['HTTP_HOST']."/activate/";
				$registerRedirectUrl = "https://".$_SERVER['HTTP_HOST']."/register/";
			}
		} else {
			$loginRedirectUrl = LOGIN;
			$activateRedirectUrl = MAPBENDER_PATH."/php/mod_activateUserAccount.php?activationKey=";
			$registerRedirectUrl = LOGIN;
		}
		return array($loginRedirectUrl,$activateRedirectUrl,$registerRedirectUrl);

	}

	public function sendUserLoginMail ($email = "",$name = "",$activation_key= "") {
		list($loginRedirectUrl,$activateRedirectUrl,$registerRedirectUrl) = $this->checkDjango();
		$e = new mb_exception("send email function!");
		$admin = new administration();

		if (defined("DJANGO_PORTAL") && DJANGO_PORTAL == true) {
			$userMessage = _mb("Activation mail for Geoportal")."\n";
			$userMessage .= _mb("Your login name is").": ".$name."\n";
			$userMessage .= _mb("Please activate your account by click on following link").": \n";
			$userMessage .= $activateRedirectUrl.$activation_key."\n";
			$e = new mb_exception("sending email to name=".$name."  email=".$email." key=".$activation_key);
			if(!$admin->sendEmail("", "", $email, $name, utf8_decode(_mb("Your Geoportal account")), utf8_decode($userMessage), $error_msg)) {
				return _mb("Registry data could not be send. Please check mail address.");
				$e = new mb_exception("MAIL FAIL!");
			}
			return _mb("Registry data has been sent successfully.");
			$e = new mb_exception("MAIL SUCCESS!");

		}else {

			$userMessage = _mb("Activation mail for Mapbender Geoportal")."\n";
			$userMessage .= _mb("Your login name is").": ".$this->name."\n";
			$userMessage .= _mb("Please activate your account by click on following link").": \n";
			$mbUrl = MAPBENDER_PATH."/";
			$userMessage .= $mbUrl."php/mod_activateUserAccount.php?activationKey=".$this->activationKey."\n";
			$userMessage .= _mb("Follow this link to login to Mapbender").": \n";
			$userMessage .= LOGIN."\n";
			$userMail = $admin->getEmailByUserId($this->id);
			if(!$admin->sendEmail("", "", $userMail, $this->name, utf8_decode(_mb("Your Mapbender Geoportal account")), utf8_decode($userMessage), $error_msg)) {
				return _mb("Registry data could not be send. Please check mail address.");
				$e = new mb_exception("MAIL FAIL!");
			}
			return _mb("Registry data has been sent successfully.");
			$e = new mb_exception("MAIL SUCCESS!");
		}
	}

    /*
    * @return Array of Users
    * @param $filter UNUSED! string that must be contained in the username
    */
	public static function getList($filter) {
		//FIXME: optimize
		$name = $filter->name ? $filter->name : null;
		$owner = $filter->owner && is_numeric($filter->owner) ? intval($filter->owner) : null;
		$users = Array();
		$sql_userlist = "SELECT mb_user_id FROM mb_user";
		$andConditions = array();
		$v = array();
		$t = array();
		if (!is_null($name)) {
			$v[]= $name;
			$t[]= "s";
	  		$andConditions[]= "mb_user_name LIKE $" . count($v);
		}
		if (!is_null($owner)) {
			$v[]= $owner;
			$t[]= "i";
	  		$andConditions[]= "mb_user_owner = $" . count($v);
		}
		if (count($andConditions) > 0) {
			$sql_userlist .= " WHERE " . implode("AND", $andConditions);
		}
		$sql_userlist .= " ORDER BY mb_user_name";
      		$res_users = db_prep_query($sql_userlist, $v, $t);

	        while($row = db_fetch_array($res_users)) {
		    try {
		        $users[] = new User($row['mb_user_id']);
		    }
		    catch(Exception $E) {
		        continue;
		        //FIXME: should catch some errors here
		    }
	        }
	        return $users;
        }

    /*
    * tries to initialize a userobject by Name
    * @return A user Object
    * @param $name the name of the user to find
    */

    public static function byName($name) {

      if($name == null) { return new User(null); }

      $sql_user = "SELECT mb_user_id FROM mb_user WHERE mb_user_name = '$name'";
      $res_user = db_query($sql_user);
      if($row = db_fetch_array($res_user))
      {
        return  new User($row['mb_user_id']);
      }
      return null;

    }

    /*
    * new 2019 - tries to initialize a userobject from a register form and store it in the mapbender database
    * @return An json string. The information of the mb_user table is in resultObject->result if the registration was successful.
    * To get the assoc array do following: $userArray = json_decode(json_encode($returnObject->result), JSON_OBJECT_AS_ARRAY);
    * @params:
    * {"user_attributes": {"mbUserName": {"mapbenderDbColumn":"mb_user_name", "mandatory": true, "type": "string", "default": null}}, {"mbUserEmail", {"mapbenderDbColumn":"mb_user_email", "mandatory": true, "type": "string", "default": null}}, {"mbUserOrganization", {"mapbenderDbColumn":"mb_user_organization_name", "mandatory": false, "type": "string", "default": null}}, {"mbUserDepartment", {"mapbenderDbColumn":"mb_user_department", "mandatory": false, "type": "string", "default": null}}, {"mbUserPhone", {"mapbenderDbColumn":"mb_user_phone", "mandatory": false, "type": "string", "default": null}}, {"mbUserNewsletter", {"mapbenderDbColumn":"mb_user_newsletter", "mandatory": true, "type": "boolean", "default": false}}, {"mbUserAllowSurvey", {"mapbenderDbColumn":"mb_user_allow_survey", "mandatory": true, "type": "boolean", "default": false}}, {"timestampDsgvoAccepted", {"mapbenderDbColumn":"timestamp_dsgvo_accepted", "mandatory": true, "type": "string", "default": null}}, {"mbUserHashAlgo", {"mapbenderDbColumn":"mb_user_digest_hash", "mandatory": false, "type": "string", "default": "MD5"}}}
    *
    */
    public function selfRegisterNewUser($mbUserName, $mbUserEmail, $mbUserPassword, $mbUserOrganization, $mbUserDepartment, $mbUserPhone, $mbUserNewsletter=false, $mbUserAllowSurvey=false, $timestampDsgvoAccepted=0, $mbUserHashAlgo = 'MD5') {
	//check if user with name already exists - if so return false
	$sql = "SELECT * FROM mb_user WHERE mb_user_name = $1";
 	$v = array($mbUserName); // wird in unserer Lösung immer md5 genutzt?
	$t = array('s');
 	$res = db_prep_query($sql, $v, $t);
	if(db_numrows($res) == 0){
		//$userAlreadyExists = false;
	} else {
		$e = new mb_exception("classes/class_user.php: user with name ".$mbUserName." already exists in mapbender database! Will not registered twice!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:selfRegisterNewUser()";
		$this->returnObject->error->message = "User with name: ".$mbUserName." alreadyRegistrated!";
		$this->returnObject->error->{__type} = "Content already exists";
		return json_encode($this->returnObject);
	}
	//mb_user_owner
	$uuid = new Uuid();
	//Check mapbender.conf for central portal admin user id
	if (defined("PORTAL_ADMIN_USER_ID") && PORTAL_ADMIN_USER_ID != "" ) {
		$mb_user_owner = PORTAL_ADMIN_USER_ID;
	} else {
		$mb_user_owner = "1"; //default to mapbenders root user
	}
	//Check mapbender.conf for anonymous group
	if (defined("PUBLIC_GROUP") && PUBLIC_GROUP != "" ) {
		$publicGroupId = PUBLIC_GROUP;
	} else {
		$publicGroupId = "22"; //default to mapbenders default public group
		$e = new mb_notice("classes/class_user.php: No PUBLIC_GROUP defined in mapbender.conf - assume it is 22!");
	}
	//TODO: use other algorithms for hashing password with digest autentification! - see https://github.com/curl/curl/commit/2b5b37cb9109e7c2e6bfa5ebf54016aff8a1fb48 and https://bugzilla.mozilla.org/show_bug.cgi?id=472823
	$sql = "INSERT INTO mb_user (mb_user_name, mb_user_email, mb_user_organisation_name, mb_user_department, mb_user_phone, ";
	$sql .= "mb_user_newsletter, mb_user_allow_survey, timestamp_dsgvo_accepted, activation_key, is_active, ";
	$sql .= "mb_user_owner, password, mb_user_digest, mb_user_aldigest, mb_user_digest_hash, uuid, mb_user_password) VALUES ";
	$sql.= "($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17)";
	//define hard coded values
	$mb_user_uuid = $uuid;
	$mb_user_activation_key = md5($uuid);
	$password = password_hash($mbUserPassword, PASSWORD_BCRYPT);
	$mb_user_digest_hash = $mbUserHashAlgo;
	$timestampDsgvoAccepted = 1; //bigint!
	switch($mb_user_digest_hash) {
		case "MD5":
			$mb_user_digest = hash(strtolower($mb_user_digest_hash), $mbUserName.";".$mbUserEmail.":".REALM.":".$mbUserPassword);
			$mb_user_aldigest = hash(strtolower($mb_user_digest_hash), $mbUserName.":".REALM.":".$mbUserPassword);
			//TODO deactivate in production
			//$mb_user_password = hash(strtolower($mb_user_digest_hash), $mbUserPassword);
			$mb_user_password = "";
			break;
		default:
			$mb_user_digest = hash(strtolower($mb_user_digest_hash), $mbUserName.";".$mbUserEmail.":".REALM.":".$mbUserPassword);
			$mb_user_aldigest = hash(strtolower($mb_user_digest_hash), $mbUserName.":".REALM.":".$mbUserPassword);
			//TODO deactivate in production
			//$mb_user_password = hash(strtolower($mb_user_digest_hash), $mbUserPassword);
			$mb_user_password = "";
			break;
	}
	if ($mbUserNewsletter == false) {
		$mbUserNewsletter = 'f';
	} else {
		$mbUserNewsletter = 't';
	}
	if ($mbUserAllowSurvey == false) {
		$mbUserAllowSurvey = 'f';
	} else {
		$mbUserAllowSurvey = 't';
	}
	$v = array($mbUserName, $mbUserEmail, $mbUserOrganization, $mbUserDepartment, $mbUserPhone, $mbUserNewsletter, $mbUserAllowSurvey, $timestampDsgvoAccepted, $mb_user_activation_key, 'f', (integer)$mb_user_owner, $password, $mb_user_digest, $mb_user_aldigest, $mb_user_digest_hash, $uuid, $mb_user_password);
	$t = array('s','s','s','s','s','b','b','i','s','b','i','s','s','s','s','s','s');
	$res = db_prep_query($sql, $v, $t);
	if (!$res) {
		$e = new mb_exception("classes/class_user.php: An error occured when trying to insert user '".$mbUserName."' into mapbender mb_user table!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:selfRegisterNewUser()";
		$this->returnObject->error->message = "An error occured when trying to insert user '".$mbUserName."' into mapbender mb_user table!";
		$this->returnObject->error->{__type} = "Database exception";
		return json_encode($this->returnObject);
	}
	//get id from user with initial uuid
	$sql = "SELECT * FROM mb_user WHERE uuid = $1";
	$v = array($mb_user_uuid);
	$t = array('s');
	$res = db_prep_query($sql, $v, $t);
	//Important for json encode/decode: fetch assoc as associated array!
	$row = db_fetch_assoc($res);
	//insert user in to public group!
	$sql = "INSERT INTO mb_user_mb_group (fkey_mb_user_id, fkey_mb_group_id) VALUES ($1, $2)";
	$v = array($row['mb_user_id'], $publicGroupId);
	$t = array('i', 'i');
	$res = db_prep_query($sql, $v, $t);
	if (!$res) {
		$e = new mb_exception("classes/class_user.php: An error occured when trying to insert user '".$row['mb_user_id']."' into group '".$publicGroupId."' of mapbender mb_group table!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:selfRegisterNewUser()";
		$this->returnObject->error->message = "An error occured when trying to insert user '".$row['mb_user_id']."' into group '".$publicGroupId."' of mapbender mb_group table!";
		$this->returnObject->error->{__type} = "Database exception";
		return json_encode($this->returnObject);
	}
	//return result
	$this->returnObject->success = true;
	$this->returnObject->help = "class_user.php:selfRegisterNewUser()";
	$this->returnObject->result = json_decode(json_encode($row));
	return json_encode($this->returnObject);
    }

    /*
    * new 2019 - authenticate against mb_user table
    * @return An json string. The information of the mb_user table is in resultObject->result if the registration was successful.
    * To get the assoc array do following: $userArray = json_decode(json_encode($returnObject->result), JSON_OBJECT_AS_ARRAY);
    * @params: $userName, $userPassword
    *
    */
    public function authenticateUserByName($mbUserName, $userPassword, $mbUserHashAlgo = 'MD5') {
	$sql = "SELECT * FROM mb_user WHERE mb_user_name = $1";
	$v = array($mbUserName);
	$t = array('s');
	$res = db_prep_query($sql, $v, $t);
	if(db_numrows($res) == 0){
		$e = new mb_notice("classes/class_user.php: "."No account for user ".$mbUserName. " found in mapbender database!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:authenticateUserByName()";
		$this->returnObject->error->message = "No account for user with name: ".$mbUserName." found in mapbender database!";
		$this->returnObject->error->{__type} = "Object not found";
		return json_encode($this->returnObject);
	}
	$row = db_fetch_array($res);
	$mbUserEmail = $row['mb_user_email'];
	list($loginRedirectUrl,$activateRedirectUrl,$registerRedirectUrl) = $this->checkDjango();
	//check all
	//first login on new system, set (salt - maybe later - and ) new password when password column is empty, delete old unsecure md5 hash
	//Test if account has already been activated by the user
	if ($row['is_active'] == "f"){
		//$URLAdd="?status=notactive";
		//TODO - use right URL!- from mapbender.conf

		$e = new mb_notice("classes/class_user.php: "."Account for user ".$mbUserName. " is not activated til now - redirect to activation!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:authenticateUserByName()";
		$this->returnObject->error->message = "Account for user with name: ".$mbUserName." has not been activated til now. Please activate the account to allow authentication: ".$activateRedirectUrl;
		$this->returnObject->error->{__type} = "Method not possible";
		return json_encode($this->returnObject);
	} else if ($row['is_active'] == "t" or $row['is_active'] == ""){ //maybe for older users which are registrated before 06/2019
		//change password only, if secure password not already given !!!!!!
//$e = new mb_exception("classes/class_user.php: "."New - secure - password: ".$row['password']);
		if($row['password'] == "" || $row['password'] == null){
			$e = new mb_notice("classes/class_user.php: "."New bcrypt(ed) password not set - will be set now for user: ".$userName. "!");
			$e = new mb_notice("classes/class_user.php: "."First check old password if this one exists!");
			if($row['mb_user_password'] == hash(strtolower($mbUserHashAlgo), $userPassword)){
				//generate bcrypt hash
				$sql = "UPDATE mb_user SET password = $1 WHERE mb_user_id = $2";
				$newCryptedPassword = password_hash($userPassword, PASSWORD_BCRYPT);
				$v = array($newCryptedPassword, $row['mb_user_id']);
				$t = array('s','i');
				$res = db_prep_query($sql,$v,$t);
				// delete old hashed passwords (mostly md5)
				if ($row['create_digest'] == 'f') {
					$sql = "UPDATE mb_user SET mb_user_password = $1 , mb_user_digest = $2, mb_user_aldigest = $3 WHERE mb_user_id = $4";
					$v = array('','','',$row['mb_user_id']);
					$t = array('s','s','s','i');
					$res = db_prep_query($sql,$v,$t);
					$row['mb_user_password'] = '';
					$row['mb_user_digest'] = '';
					$row['mb_user_aldigest'] = '';
				} else {
					$sql = "UPDATE mb_user SET mb_user_password = $1 WHERE mb_user_id = $2";
					$v = array('',$row['mb_user_id']);
					$t = array('s','i');
					$res = db_prep_query($sql,$v,$t);
					$row['mb_user_password'] = '';
				}
				$e = new mb_notice("classes/class_user.php: "."New password stored in db, old md5 password deleted for user: ".$userName. "!");
				//save new crypted password in array
				$row['password'] = $newCryptedPassword;
				$this->returnObject->success = true;
				unset($this->returnObject->error);
				$this->returnObject->help = "class_user.php:authenticateUserByName()";
				$this->returnObject->result = json_decode(json_encode($row));
				return json_encode($this->returnObject);
			} else {
				$e = new mb_exception("classes/class_user.php: "."Could not authenticate user ".$mbUserName. " with old password - either the password is wrong or the hash algo differs!");
				$this->returnObject->success = false;
				unset($this->returnObject->result);
				$this->returnObject->help = "class_user.php:authenticateUserByName()";
				$this->returnObject->error->message = "Could not authenticate user ".$mbUserName. " with old password - either the password is wrong or the hash algo differs!";
				$this->returnObject->error->{__type} = "Access denied";
				return json_encode($this->returnObject);
			}
		} else {
			$e = new mb_notice("classes/class_user.php: "."New - secure - password version found for user: ".$mbUserName. "! Authenticate against this one!");
//$e = new mb_exception("classes/class_user.php: "."New - secure - password: ".$row['password']);
			// salt is included in the hashed password
			$salt = $row['password'];
			if (password_verify($userPassword, $salt)) {
				//delete all old unsecure passwords if given!!!
				$e = new mb_notice("classes/class_user.php: "."Try to delete all unsecure passwords!");
				if ($row['create_digest'] == 'f') {
					$e = new mb_notice("classes/class_user.php: "."Try to delete all unsecure passwords!");
					$sql = "UPDATE mb_user SET mb_user_password = $1 , mb_user_digest = $2, mb_user_aldigest = $3 WHERE mb_user_id = $4";
					$v = array('','','',$row['mb_user_id']);
					$t = array('s','s','s','i');
					$res = db_prep_query($sql,$v,$t);
				} else {
					$sql = "UPDATE mb_user SET mb_user_password = $1, mb_user_digest = $2, mb_user_aldigest = $3 WHERE mb_user_id = $4";
					$v = array('',hash(strtolower($mbUserHashAlgo), $mbUserName.";".$mbUserEmail.":".REALM.":".$userPassword),hash(strtolower($mbUserHashAlgo), $mbUserName.":".REALM.":".$userPassword),$row['mb_user_id']);
					$t = array('s','s','s','i');
					$res = db_prep_query($sql,$v,$t);
				}
				//successful login
				$sql = "UPDATE mb_user SET mb_user_login_count = 0 WHERE mb_user_name = $1";
				$v = array($mbUserName);
				$t = array('s');
				$res = db_prep_query($sql,$v,$t);

				$sql = "SELECT * FROM mb_user WHERE mb_user_name = $1";
				$v = array($mbUserName);
				$t = array('s');
				$res = db_prep_query($sql,$v,$t);
				$row = db_fetch_assoc($res);
				$this->returnObject->success = true;
				unset($this->returnObject->error);
				$this->returnObject->help = "class_user.php:authenticateUserByName()";
				$this->returnObject->result = json_decode(json_encode($row));
				return json_encode($this->returnObject);
			}
		}

		# name = true ; pw = false tree
		# check if user has to be locked
		if($row['mb_user_login_count'] > 3){

			$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';

			$sql = "UPDATE mb_user SET is_active = False WHERE mb_user_name = $1";
			$v = array($mbUserName);
			$t = array('s');
			$res = db_prep_query($sql,$v,$t);
			$activation_key = substr(str_shuffle($permitted_chars), 0, 50);

			$sql = "UPDATE mb_user SET activation_key = $1 WHERE mb_user_name = $2";
			$v = array($activation_key,$mbUserName);
			$t = array('s','s');
			$res = db_prep_query($sql,$v,$t);

			$e = new mb_exception("classes/class_user.php: "."Password failed third time for ".$mbUserName. ". Account is now locked! Reactivation Mail was sent!");
			$this->returnObject->success = false;
			unset($this->returnObject->result);
			$this->returnObject->help = "class_user.php:authenticateUserByName()";
			$this->returnObject->error->message = "Password failed third time for ".$mbUserName. ". Account is now locked! Reactivation Mail was sent!";
			$this->returnObject->error->{__type} = "Access denied";
			# send reactivationmail
			$e = new mb_exception("sending email to name=".$row['mb_user_name']."  email=".$row['mb_user_email']." key=".$activation_key);
			if (defined("DJANGO_PORTAL") && DJANGO_PORTAL == true) {
				$e = new mb_exception("Mail Content: ".$activateRedirectUrl.$activation_key." Account is now locked! Reactivation Mail was sent!");
				$this->sendUserLoginMail($row['mb_user_email'],$row['mb_user_name'],$activation_key);
			}else{
				$this->sendUserLoginMail();
				$e = new mb_exception("Mail Content: ".$activateRedirectUrl." Account is now locked! Reactivation Mail was sent!");
			}

			return json_encode($this->returnObject);

		}else{

			$sql = "UPDATE mb_user SET mb_user_login_count = mb_user_login_count + 1 WHERE mb_user_name = $1";
			$v = array($mbUserName);
			$t = array('s');
			$res = db_prep_query($sql,$v,$t);

			$e = new mb_exception("classes/class_user.php: "."Account for activated user ".$mbUserName. " could not be authenticated with given password!");
			$this->returnObject->success = false;
			unset($this->returnObject->result);
			$this->returnObject->help = "class_user.php:authenticateUserByName()";
			$this->returnObject->error->message = "Account for activated user with name: ".$mbUserName." could not be authenticated with given password!";
			$this->returnObject->error->{__type} = "Access denied";
			return json_encode($this->returnObject);

		}


	} else {
		$e = new mb_exception("classes/class_user.php: "."Account for user ".$mbUserName. " (not active!) could not be authenticated with given password!");
		$this->returnObject->success = false;
		unset($this->returnObject->result);
		$this->returnObject->help = "class_user.php:authenticateUserByName()";
		$this->returnObject->error->message = "Account for user (not active!) with name: ".$mbUserName." could not be authenticated with given password!";
		$this->returnObject->error->{__type} = "Access denied";
		return json_encode($this->returnObject);
	}
    }

	/**
	 * Returns an array of application IDs that the user is allowed to access.
	 *
	 * @return Array an array of application IDs
	 * @param $ignorePublic boolean whether or not to ignore
	 * public applications (?)
         * @param $categoryFilter false or id of category to filter guis - only return those guis, that are in that category
	 */
	public function getApplicationsByPermission ($ignorePublic = false, $categoryFilter = false) {
		$mb_user_id = $this->id;
		$arrayGuis = array();
		$mb_user_groups = array();
		//exchange for the new role system - there are roles which don't include permissions explicitly
		$sql_groups = "SELECT fkey_mb_group_id FROM ";
		$sql_groups .= "(SELECT * from mb_user_mb_group left join mb_role on ";
		$sql_groups .= " mb_user_mb_group.mb_user_mb_group_type = mb_role.role_id ";
		$sql_groups .= " WHERE mb_role.role_exclude_auth != 1)  AS mb_user_mb_group WHERE fkey_mb_user_id = $1 ";
		//$sql_groups = "SELECT fkey_mb_group_id FROM mb_user_mb_group WHERE fkey_mb_user_id = $1 ";
		$v = array($mb_user_id);
		$t = array("i");
		$res_groups = db_prep_query($sql_groups,$v,$t);
		$cnt_groups = 0;
		while($row = db_fetch_array($res_groups)){
			$mb_user_groups[$cnt_groups] = $row["fkey_mb_group_id"];
			$cnt_groups++;
		}
		if($cnt_groups > 0){
			$v = array();
			$t = array();
			$sql_g = "SELECT gui.gui_id FROM gui JOIN gui_mb_group ";
			$sql_g .= " ON gui.gui_id = gui_mb_group.fkey_gui_id WHERE gui_mb_group.fkey_mb_group_id IN (";
			for($i=0; $i<count($mb_user_groups);$i++){
				if($i > 0){$sql_g .= ",";}
				$sql_g .= "$".strval($i+1);
				array_push($v,$mb_user_groups[$i]);
				array_push($t,"i");
				$numberOfGroups = $i;
			}
			$sql_g .= ") GROUP BY gui.gui_id";

                	if ($categoryFilter != false) {
		    	    $sql_g = "SELECT fkey_gui_id as gui_id from gui_gui_category as gui_id WHERE fkey_gui_id IN "."(".$sql_g.") AND fkey_gui_category_id = $".strval($numberOfGroups+2);
		    	    array_push($v, $categoryFilter);
		    	    array_push($t, "i");
			}
			$res_g = db_prep_query($sql_g,$v,$t);
			while($row = db_fetch_array($res_g)){
				array_push($arrayGuis,$row["gui_id"]);
			}
		}

		$sql_guis = "SELECT gui.gui_id FROM gui JOIN gui_mb_user ON gui.gui_id = gui_mb_user.fkey_gui_id";
		$sql_guis .= " WHERE (gui_mb_user.fkey_mb_user_id = $1) ";
		if (!isset($ignorePublic) OR $ignorePublic== false){
			$sql_guis .= " AND gui.gui_public = 1 ";
		}
		$sql_guis .= " GROUP BY gui.gui_id";
                $v = array($mb_user_id);
		$t = array("i");
                if ($categoryFilter != false) {
		    $sql_guis = "SELECT fkey_gui_id as gui_id from gui_gui_category as gui_id WHERE fkey_gui_id IN "."(".$sql_guis.") AND fkey_gui_category_id = $2";
		    array_push($v, $categoryFilter);
		    array_push($t, "i");
		}
		$res_guis = db_prep_query($sql_guis,$v,$t);
		$guis = array();
		while($row = db_fetch_array($res_guis)){
			if(!in_array($row['gui_id'],$arrayGuis)){
				array_push($arrayGuis,$row["gui_id"]);
			}
		}
		return $arrayGuis;
	}

	public function filterApplicationsForWmcApi ($guiArray) {
	   $sql = "SELECT gui_id, gui_public FROM gui INNER JOIN gui_element WHERE gui_id in (";
	   //TODO - if needed for application metadata editor!
	   $sql .= ");";

	}

	public function getOwnedWfs () {
		$sql = "SELECT wfs_id FROM wfs WHERE wfs_owner = $1";
		$res = db_prep_query($sql, array($this->id), array("i"));
		$wfsIdArray = array();
		while ($row = db_fetch_array($res)) {
			$wfsIdArray[]= $row["wfs_id"];
		}
		return $wfsIdArray;
	}

	public function getWfsByPermission () {
		$wfsArray = array();
		$appArray = $this->getApplicationsByPermission();
		if (is_array($appArray) && count($appArray) > 0) {
			$v = array();
			$t = array();
			$sql = "SELECT DISTINCT fkey_wfs_id FROM gui_wfs WHERE fkey_gui_id IN (";
			for ($i = 0; $i < count($appArray); $i++) {
				if($i > 0) {
					$sql .= ",";
				}
				$sql .= "$".strval($i+1);

				array_push($v, $appArray[$i]);
				array_push($t, "s");
			}
			$sql .= ") ORDER BY fkey_wfs_id";

			$res = db_prep_query($sql,$v,$t);
			while($row = db_fetch_array($res)){
				$wfsArray[]= intval($row['fkey_wfs_id']);
			}
		}
		return $wfsArray;
	}

	public function getWfsConfByWfsOwner () {
		$wfsConfIdArray = array();

		$sql = "SELECT * FROM wfs_conf, wfs WHERE wfs.wfs_owner = $1 AND " .
			"wfs_conf.fkey_wfs_id = wfs.wfs_id ORDER BY wfs_conf.wfs_conf_id";
		$v = array($this->id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		while($row = db_fetch_array($res)){
			$wfsConfIdArray[]= $row["wfs_conf_id"];
		}
		return $wfsConfIdArray;
	}
	/** check if feature typenames of a WFS requests are accessible - that means, that
	 *  a wfs_conf for each featuretype exists and the user has access to a gui in which this wfs_conf
         *  is integrated
	 * @params String typenames [csv], Integer wfsId
	 * @return boolean
	 * TODO!!!!!!
	 */
	public function areFeaturetypesAccessible ($typenames, $wfsId) {
		$array_guis = $this->getApplicationsByPermission();
		if (count($array_guis) == 0) {
			return false;
		}
		$v = array();
		$t = array();
		//Example sql: select featuretype_id from wfs_featuretype where fkey_wfs_id in (340, 341, 342) and featuretype_name in ('AXE_ROUTE','AX_Flurstueck') and featuretype_id in (select fkey_featuretype_id from wfs_conf where wfs_conf_id in (select fkey_wfs_conf_id from gui_wfs_conf where gui_id in ('testgui')));
		$sql = "SELECT featuretype_id FROM wfs_featuretype WHERE fkey_wfs_id = $1 ";
		$v[0] = $wfsId;
		$t[0] = 'i';
		$sql .= "AND featuretype_name IN (";
		$c = 2;
		$featuretypeArray = explode(",", $typenames);
		$numberOfFeaturetypes = count($featuretypeArray);
		//test for string or array?
		for ($i = 0; $i < $numberOfFeaturetypes; $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $featuretypeArray[$i]);
			array_push($t, 's');
		}
		$sql .= ") AND featuretype_id IN (SELECT fkey_featuretype_id FROM wfs_conf WHERE wfs_conf_id IN (SELECT fkey_wfs_conf_id FROM gui_wfs_conf WHERE fkey_gui_id IN (";
		for ($i = 0; $i < count($array_guis); $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $array_guis[$i]);
			array_push($t, 's');
		}
		$sql .= ")))";
		//for debug purposes:
		//$e = new mb_exception($sql);
		/*foreach ($v as $variable) {
			$e = new mb_exception($variable);
		}
		foreach ($t as $type) {
			$e = new mb_exception($type);
		}*/
		//$e = new mb_exception($sql);
		$res = db_prep_query($sql,$v,$t);
		//check if number of results is equal to number of requested typenames
		$row = db_fetch_all($res);
		if (count($row) == 1 && $row[0]['featuretype_id'] == null) {
			//
			new mb_exception("classes/classe_user.php: No wfs_conf found for the requested featuretype - access not allowed if security proxy is activated!");
			return false;
		}
		//$e = new mb_exception("featuretype_id[0]: '".$row[0]['featuretype_id']."' - ".count($row));
		if (count($row) !== $numberOfFeaturetypes) {
			new mb_exception("classes/classe_user.php: Number of requested featuretypes are not equal to found wfs_confs!");
			return false;
		} else {
			return true;
		}
	}

	/** identifies the IDs of WFS confs where the user is owner
	 *
	 * @param Array appIdArray [optional] restrict to certain applications
	 * @return integer[] the IDs of the wfs_conf-table
	 */
	public function getWfsConfByPermission () {
		$userid = $this->id;
	 	$guisByPer = array();
//	 	1.
		$adm = new administration();
	 	$guisByPer = $adm->getGuisByPermission($userid, true);

		if (func_num_args() === 1) {
			$arg1 = func_get_arg(0);
			if (!is_array($arg1)) {
				$arg1 = array($arg1);
			}

			$appIdArray = $arg1;
			$guisByPer = array_intersect($guisByPer, $appIdArray);
			$guisByPer = array_keys(array_flip($guisByPer));
		}

//		$e = new mb_exception(serialize($guisByPer));

//	 	2.
		$ownWFSconfs = array();
		if(count($guisByPer)>0){
			$v = array();
			$t = array();
			$sql = "SELECT wfs_conf.wfs_conf_id  FROM gui_wfs_conf, wfs_conf " .
					"where wfs_conf.wfs_conf_id = gui_wfs_conf.fkey_wfs_conf_id " .
					"and gui_wfs_conf.fkey_gui_id IN(";
			for($i=0; $i<count($guisByPer); $i++){
				if($i>0){ $sql .= ",";}
				$sql .= "$".strval($i+1);

				array_push($v, $guisByPer[$i]);
				array_push($t, "s");
			}
			$sql .= ") GROUP BY wfs_conf.wfs_conf_id ORDER BY wfs_conf.wfs_conf_id";

			$res = db_prep_query($sql,$v,$t);
			$i=0;
			while($row = db_fetch_array($res)){
				$ownWFSconfs[$i] = intval($row['wfs_conf_id']);
				$i++;
			}
		}
		return $ownWFSconfs;
	}

	/**
	 * Returns all WMCs that this user owns
	 *
	 * @return integer[] an array of WMC ids; ids from table mb_user_wmc
	 */
	public function getWmcByOwner () {
		$sql = "SELECT wmc_serial_id FROM mb_user_wmc ";
		$sql .= "WHERE fkey_user_id = $1 GROUP BY wmc_serial_id";
		$v = array($this->id);
		$t = array("i");
		$res_wmc = db_prep_query($sql, $v, $t);

  		$wmcArray = array();
		while($row = db_fetch_array($res_wmc)){
			array_push($wmcArray, $row["wmc_serial_id"]);
		}
		return $wmcArray;
	}

      	/**
	 * Returns all WMCs with some further information that this user owns
	 *
	 * @return obj an array of WMC information; ids, titles, abstracts from table mb_user_wmc
	 */
	public function getWmcInfoByOwner ($ignorePublic = false) {
		$sql = "SELECT wmc_serial_id, wmc_title, abstract FROM mb_user_wmc ";
		$sql .= "WHERE fkey_user_id = $1 GROUP BY wmc_serial_id, wmc_title, abstract";
		if ($ignorePublic == true) {
			$sql .= " AND wmc_public = 1";
		}
		$v = array($this->id);
		$t = array("i");
		$res_wmc = db_prep_query($sql, $v, $t);
  		$wmcArray = array();
		while($row = db_fetch_array($res_wmc)){
			$wmcObj = new stdClass();
			$wmcObj->wmc_serial_id = $row["wmc_serial_id"];
			$wmcObj->wmc_title = $row["wmc_title"];
			$wmcObj->abstract = $row["abstract"];
			array_push($wmcArray, $wmcObj);
		}
		return $wmcArray;
	}

	public function isLayerAccessible ($layerId) {
		$array_guis = $this->getApplicationsByPermission();
		$v = array();
		$t = array();
		$sql = "SELECT * FROM gui_layer WHERE fkey_gui_id IN (";
		$c = 1;
		for ($i = 0; $i < count($array_guis); $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $array_guis[$i]);
			array_push($t, 's');
		}
		$sql .= ") AND fkey_layer_id = $".$c." AND gui_layer_status = 1";
		array_push($v,$layerId);
		array_push($t,'i');
		$res = db_prep_query($sql,$v,$t);

		return ($row = db_fetch_array($res)) ? true : false;
	}

	public function getAccessableLayers ($layerIdArray) {
		if (gettype($layerIdArray) !== "array" || (gettype($layerIdArray) == "array" && count($layerIdArray) == 0) ) {
			return array(0);
		}
		$array_guis = $this->getApplicationsByPermission();
		$v = array();
		$t = array();
		$sql = "SELECT DISTINCT fkey_layer_id FROM gui_layer WHERE fkey_gui_id IN (";
		$c = 1;
		for ($i = 0; $i < count($array_guis); $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $array_guis[$i]);
			array_push($t, 's');
		}
		//
		$sql .= ") AND fkey_layer_id IN (";
		$j = 0;
		//remove empty entries from $layerIdArray
		$layerIdArray = array_filter($layerIdArray);
		foreach ($layerIdArray as $layerId) {
			if ($j > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			$j++;
			array_push($v, $layerId);
			array_push($t, 'i');
		}
		$sql .= ") AND gui_layer_status = 1";
		$res = db_prep_query($sql,$v,$t);
		$allowedLayerIdArray = array();
		while ($row = db_fetch_array($res)) {
			$allowedLayerIdArray[] = $row["fkey_layer_id"];
		}
		return $allowedLayerIdArray;
	}

	public function isWmsAccessible ($wms_id) {
		$array_guis = $this->getApplicationsByPermission();
		$v = array();
		$t = array();
		$sql = "SELECT * FROM gui_wms WHERE fkey_gui_id IN (";
		$c = 1;
		for ($i = 0; $i < count($array_guis); $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $array_guis[$i]);
			array_push($t, 's');
		}
		$sql .= ") AND fkey_wms_id = $" . $c;
		array_push($v, $wms_id);
		array_push($t, 'i');
		$res = db_prep_query($sql, $v, $t);
		return ($row = db_fetch_array($res)) ? true : false;
	}

	public function getOwnedMetadata ($type = false) {
		if ($type == 'application') {
$sql = "SELECT metadata_id FROM mb_metadata WHERE type = 'application' AND fkey_mb_user_id = $1";
} else {
		$sql = "SELECT metadata_id FROM mb_metadata WHERE fkey_mb_user_id = $1";
}
		$res = db_prep_query($sql, array($this->id), array("i"));
		$metadataIdArray = array();
		while ($row = db_fetch_array($res)) {
			$metadataIdArray[]= $row["metadata_id"];
		}
		return $metadataIdArray;
	}

	public function getOwnedWms () {
		$sql = "SELECT wms_id FROM wms WHERE wms_owner = $1";
		$res = db_prep_query($sql, array($this->id), array("i"));
		$wmsIdArray = array();
		while ($row = db_fetch_array($res)) {
			$wmsIdArray[]= $row["wms_id"];
		}
		return $wmsIdArray;
	}

	public function getOwnedWmsScheduler () {
		$sql = "SELECT scheduler_id FROM scheduler, wms WHERE wms.wms_id = scheduler.fkey_wms_id AND wms.wms_owner = $1";
		$res = db_prep_query($sql, array($this->id), array("i"));
		$wmsSchedulerIdArray = array();
		while ($row = db_fetch_array($res)) {
			$wmsSchedulerIdArray[]= $row["scheduler_id"];
		}
		return $wmsSchedulerIdArray;
	}


	public function getOwnedGeodata () {
		$sql = "SELECT metadata_id FROM mb_metadata WHERE fkey_mb_user_id = $1";
		$res = db_prep_query($sql, array($this->id), array("i"));
		$geodataIdArray = array();
		while ($row = db_fetch_array($res)) {
			$e = new mb_exception("metadata_id: ".$row["metadata_id"]);
			$geodataIdArray[]= $row["metadata_id"];
		}
		return $geodataIdArray;
	}

	public function isWmsOwner ($wms_id) {
		// first get guis which deploy this wms.
        $sql = "SELECT fkey_gui_id FROM gui_wms WHERE fkey_wms_id = $1 GROUP BY fkey_gui_id";
		$v = array($wms_id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);

		$gui = array();
		while($row = db_fetch_array($res)){
			$gui[] = $row["fkey_gui_id"];
		}

        if (count($gui) === 0) {
        	return false;
		}
		$v = array();
		$t = array();
		$c = 1;
		$sql = "(SELECT mb_user.mb_user_id FROM mb_user JOIN gui_mb_user ";
		$sql .= "ON mb_user.mb_user_id = gui_mb_user.fkey_mb_user_id ";
		$sql .= " WHERE gui_mb_user.mb_user_type = 'owner'";
		$sql .= " AND gui_mb_user.fkey_gui_id IN (";
		for ($i = 0; $i < count($gui); $i++) {
			if ($i > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $gui[$i]);
			array_push($t, 's');
		}
		$sql .= ") GROUP BY mb_user.mb_user_id";
		$sql .= ") UNION (";
		$sql .= "SELECT mb_user.mb_user_id FROM gui_mb_group JOIN mb_user_mb_group ON  mb_user_mb_group.fkey_mb_group_id = gui_mb_group.fkey_mb_group_id  JOIN mb_user ";
		$sql .= "ON mb_user.mb_user_id = mb_user_mb_group.fkey_mb_user_id ";
		$sql .= " WHERE gui_mb_group.mb_group_type = 'owner'";
		$sql .= " AND gui_mb_group.fkey_gui_id IN (";

		for ($j = 0; $j < count($gui); $j++) {
			if ($j > 0) {
				$sql .= ",";
			}
			$sql .= "$".$c;
			$c++;
			array_push($v, $gui[$i]);
			array_push($t, 's');
		}
		$sql .= ") GROUP BY mb_user.mb_user_id)";

		$res = db_prep_query($sql,$v,$t);

		$user = array();
		while($row = db_fetch_array($res)){
			$user[] = intval($row["mb_user_id"]);
		}
		if (in_array($this->id, $user))	{
            return true;
        }
		return false;
	}

	private function addSingleSubscription ($id, $serviceType = "WMS") {
		if (!is_numeric($id)) {
			$e = new mb_exception("class_user.php: addSingleSubscription: ".$serviceType." Id is not a number.");
			return false;
		}
		$id = intval($id);
		if ($this->cancelSingleSubscription($id)) {
			switch ($serviceType) {
				case "WMS":
					$sql = "INSERT INTO mb_user_abo_ows (fkey_mb_user_id, fkey_wms_id) VALUES ($1, $2)";
					break;
				case "WFS":
					$sql = "INSERT INTO mb_user_abo_ows (fkey_mb_user_id, fkey_wfs_id) VALUES ($1, $2)";
					break;
			}
			$v = array($this->id, $id);
			$t = array('i', 'i');
			$res = db_prep_query($sql, $v, $t);
			return ($res) ? true : false;
		}
		return false;
	}

	private function cancelSingleSubscription ($id, $serviceType = "WMS") {
		if (!is_numeric($id)) {
			$e = new mb_exception("class_user.php: cancelSingleSubscription: ".$serviceType." Id not a number.");
			return false;
		}
		$id = intval($id);
		switch ($serviceType) {
			case "WMS":
				$sql = "DELETE FROM mb_user_abo_ows WHERE fkey_wms_id = $1 " .
					"AND fkey_mb_user_id = $2";
				break;
			case "WFS":
				$sql = "DELETE FROM mb_user_abo_ows WHERE fkey_wfs_id = $1 " .
					"AND fkey_mb_user_id = $2";
				break;
		}
		$v = array($id, $this->id);
		$t = array('i', 'i');
		$res = db_prep_query($sql, $v, $t);
		return ($res) ? true : false;
	}

	public function addSubscription ($services, $serviceType = "WMS") {
		if (is_array($services)) {
			foreach ($services as $serviceId) {
				$this->addSingleSubscription($serviceId, $serviceType);
			}
		}
		else {
			$this->addSingleSubscription($services, $serviceType);
		}
	}

	public function cancelSubscription ($services, $serviceType = "WMS") {
		if (is_array($services)) {
			foreach ($services as $serviceId) {
				$this->cancelSingleSubscription($serviceId, $serviceType);
			}
		}
		else {
			$this->cancelSingleSubscription($services, $serviceType);
		}
	}

	public function hasSubscription ($serviceId, $serviceType = "WMS") {
		if (!is_numeric($serviceId)) {
			$e = new mb_exception("class_user.php: cancelSingleSubscription: ".$serviceType." Id not a number.");
			return false;
		}
		$id = intval($serviceId);
		switch ($serviceType) {
			case "WMS":
  				$sql = "SELECT * FROM mb_user_abo_ows WHERE fkey_wms_id = $1 AND " .
					"fkey_mb_user_id = $2 LIMIT 1";
				break;
			case "WFS":
   				$sql = "SELECT * FROM mb_user_abo_ows WHERE fkey_wfs_id = $1 AND " .
					"fkey_mb_user_id = $2 LIMIT 1";
				break;
		}
		$v = array($id, $this->id);
		$t = array('i', 'i');
		$res = db_prep_query($sql, $v, $t);
		$row = db_fetch_array($res);
		switch ($serviceType) {
			case "WMS":
				if (!isset($row['fkey_wms_id'])) {
	        			return false;
				}
				break;
			case "WFS":
   				if (!isset($row['fkey_wfs_id'])) {
	        			return false;
				}
				break;
		}
        return true;
	}

	public function isValid () {
		if (!is_null($this->name) && $this->name !== "") {
			return true;
		}
		return false;
	}

}
?>
