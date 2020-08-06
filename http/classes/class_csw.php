<?php
# $Id$
# http://www.mapbender.org/index.php/class_csw
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
require_once(dirname(__FILE__)."/class_connector.php");
require_once(dirname(__FILE__)."/class_user.php");
require_once(dirname(__FILE__)."/class_administration.php");
require_once(dirname(__FILE__)."/class_cache.php");

/**
 * CSW main class to hold catalog object
 * @author nazgul
 *
 */
class csw{
	var $cat_id;
	var $cat_title;
	var $cat_abstract;
	var $cat_version;
	
	var $cat_op_getcapabilities;
	var $cat_op_getrecords;
	var $cat_op_getrecordbyid;
	var $cat_op_describerecord;

	var $cat_getcapabilities_doc;
	
	var $cat_get_capabilities_values = array();
	var $cat_get_records_values = array();
	
	var $cat_op_values = array();
	
	var $cat_upload_url;
	var $fees;
	var $accessconstraints;
	var $contactperson;
	var $contactposition;
	var $contactorganization;
	var $address;
	var $city;
	var $stateorprovince;
	var $postcode;
	var $country;
	var $contactvoicetelephone;
	var $contactfacsimiletelephone;
	var $contactelectronicmailaddress;
	
	var $keywords = array();
	
	var $catowner;
	var $cattimestamp;
	var $providername;
	var $providersite;
	var $delivery='';
	
	//store catalog retrieval status
	var $cat_status;
	
	function __construct(){
		
	}
        
        /**
        * Old constructor to keep PHP downward compatibility
        */
        function csw(){
		self::__construct();
	}
	
	//Getters of common items
	function getCatVersion(){
		return $this->cat_version;	
	}
	
	/**
	 * 
	 * @param $request_type string getrecords,describerecords..
	 * @param $request_method string get,post,soap
	 * @return unknown_type
	 * @todo error check to see whether value is available in method
	 */
	function getURLValue($request_type,$request_method){
		return $this->cat_op_values[$request_type][$request_method];
	}
	
	
	public function getCatURL($type){
		
	}
	
	//XML to Persistance
	/**
	 * Called by admin function when adding catalog
	 * Create Catalog object from Getcapabilities XML
	 * @return unknown_type
	 * @param $url URL of getcapabilities request
	 */
	public function createCatObjFromXML($url) {
		$cache = new Cache();
		if (defined("CACHE_CSW_CAPS") && CACHE_CSW_CAPS == true) {
		   if ($cache->isActive && $cache->cachedVariableExists(md5($url))) {
		    //overwrite csw caps xml with content from cache
		    $data = $cache->cachedVariableFetch(md5($url));
		    $e = new mb_exception("classes/class_csw.php: Read CSW Object from cache - if something changed in external catalogue - apache restart is needed!!!");
		} else {
                    if ($cache->isActive) {
                        //load csw xml and write it to cache - don't resolve it twice!
			$x = new connector($url);
			$data = $x->file;
			$cache->cachedVariableAdd(md5($url), $data);
                    } else {
			$x = new connector($url);
			$data = $x->file;
		    }
		}
		} else {
		    $x = new connector($url);
		    $data = $x->file;
		}
		//handle non-availability of Internet
		if(!$data){
			$this->cat_status = false;
			$e = new mb_exception("class_csw: createCatObjFromXML: CSW " . $url . " could not be retrieved.");
			echo "Error: Unable to retrieve catalog XML. Please check your Network connection ";
			return false;
		}
		else {
			$this->cat_status = true;
		}
		
		//arrays to hold xml struct values and index
		$value_array = null;
		$index_array = null;
		
		//operational vars
		$op_type=null; //get-capabilities, getrecords ...
		$op_sub_type=null; //get,post,....
		$op_constraint=false;
		
		$this->cat_getcapabilities_doc = $data;
		$this->cat_upload_url = $url;
		$this->cat_id="";//Auto-assing catalog id
		//$e = new mb_exception($this->cat_getcapabilities_doc);
		//alter xml parsing to simple_xml with xpath
		try {
			$xml = str_replace('xlink:href', 'xlinkhref', $this->cat_getcapabilities_doc);
			#http://forums.devshed.com/php-development-5/simplexml-namespace-attributes-problem-452278.html
			#http://www.leftontheweb.com/message/A_small_SimpleXML_gotcha_with_namespaces
//test without replace:
			$xml = $this->cat_getcapabilities_doc;
			$csw202Cap = new SimpleXMLElement($xml);
			$namespaces = $csw202Cap->getNamespaces(true);
			if ($csw202Cap === false) {
				foreach(libxml_get_errors() as $error) {
        				$e = new mb_exception($error->message);
    				}
				throw new Exception('Cannot parse CSW 2.0.2 Capabilities!');
			}
		}
		catch (Exception $e) {
    			$e = new mb_exception($e->getMessage());
		}
//xmlns:ns2="http://www.w3.org/1999/xlink"
		if ($csw202Cap !== false) {
			//read all relevant information an put them into the mapbender csw object
			//xmlns="http://www.opengis.net/csw"
			//Setup default namespace
			$csw202Cap->registerXPathNamespace("ows", "http://www.opengis.net/ows");
			//$csw202Cap->registerXPathNamespace("ows", "http://www.opengis.net/ows");
			$csw202Cap->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			$csw202Cap->registerXPathNamespace("gmd", "http://www.isotc211.org/2005/gmd");
			$csw202Cap->registerXPathNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");
			$csw202Cap->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
			$csw202Cap->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
			$csw202Cap->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
			//$csw202Cap->registerXPathNamespace("default", "http://www.opengis.net/ows");
			$csw202Cap->registerXPathNamespace("inspire_ds", "http://inspire.ec.europa.eu/schemas/inspire_ds/1.0");
			$csw202Cap->registerXPathNamespace("inspire_com", "http://inspire.ec.europa.eu/schemas/common/1.0");
			$this->cat_version = $csw202Cap->xpath('/csw:Capabilities/@version');
			$this->cat_version = $this->cat_version[0];
			if ($this->cat_version == null) {
				$this->cat_version = "2.0.2";
			}
			//$e = new mb_exception($this->cat_version);
			//title part
			$this->cat_title = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:Title');
			$this->cat_title = $this->stripEndlineAndCarriageReturn($this->cat_title[0]);
			//abstract
			$this->cat_abstract = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:Abstract');
			$this->cat_abstract = $this->stripEndlineAndCarriageReturn($this->cat_abstract[0]);
			//fees
			$this->fees = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:fees');
			$this->fees = $this->stripEndlineAndCarriageReturn($this->fees[0]);
			//accessconstraints
			$this->accessconstraints = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:AccessConstraints');
			$this->accessconstraints = $this->stripEndlineAndCarriageReturn($this->accessconstraints[0]);
			//TODO: keywords
			//service provider
			$this->contactorganization = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:ServiceProvider/ows:ProviderName');
			$this->contactorganization = $this->stripEndlineAndCarriageReturn($this->contactorganization[0]);
			$this->contactperson = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:ServiceProvider/ows:ServiceContact/ows:IndividualName');
			$this->contactorganization = $this->stripEndlineAndCarriageReturn($this->contactperson[0]);
			$this->contactposition = $csw202Cap->xpath('/csw:Capabilities/ows:ServiceIdentification/ows:ServiceProvider/ows:ServiceContact/ows:PositionName');
			$this->contactorganization = $this->stripEndlineAndCarriageReturn($this->contactposition[0]);
			//
			/*$this->address
			$this->city
			$this->stateorprovince
			$this->postcode
			$this->country
			$this->contactvoicetelephone
			$this->contactfacsimiletelephone
			$this->contactelectronicmailaddress*/
			//for op_types
			$op_types = array("GetCapabilities","DescribeRecord","GetRecords","GetRecordById");
			foreach ($op_types as $op_type)  {
				$this->cat_op_values[mb_strtolower($op_type)]['get']['dflt'] = $csw202Cap->xpath('/csw:Capabilities/ows:OperationsMetadata/ows:Operation[@name="'.$op_type.'"]/ows:DCP/ows:HTTP/ows:Get/@xlink:href');
				$this->cat_op_values[mb_strtolower($op_type)]['get']['dflt'] = html_entity_decode($this->cat_op_values[mb_strtolower($op_type)]['get']['dflt'][0]);
				//$e = new mb_exception("class_csw: operationurl: ".$this->cat_op_values[mb_strtolower($op_type)]['get']['dflt']);
				$this->cat_op_values[mb_strtolower($op_type)]['post']['dflt'] = $csw202Cap->xpath('/csw:Capabilities/ows:OperationsMetadata/ows:Operation[@name="'.$op_type.'"]/ows:DCP/ows:HTTP/ows:Post/@xlink:href');
				$this->cat_op_values[mb_strtolower($op_type)]['post']['dflt'] = html_entity_decode($this->cat_op_values[mb_strtolower($op_type)]['post']['dflt'][0]);
				//extract constraints for this operation
				$constraints = $csw202Cap->xpath('/csw:Capabilities/ows:OperationsMetadata/ows:Operation[@name="'.$op_type.'"]/ows:Constraint[@name="PostEncoding"]/ows:Value');
				foreach ($constraints as $constraint) {
					$this->cat_op_values[mb_strtolower($op_type)]['post'][mb_strtolower($constraint)]=$this->cat_op_values[mb_strtolower($op_type)]['post'][$constraint];
					//$e = new mb_exception("class_csw: constraint: ".mb_strtolower($constraint));
				}
			}
			if (!isset($this->cat_title) || $this->cat_title == "") {
				$this->cat_title = "No title for CSW given!";
			}
			//$e = new mb_exception($this->cat_title);
		}
		
		//Success/Failure
		if(!$this->cat_title || $this->cat_title == ""){
			$this->cat_status = false;
			$e = new mb_exception("class_csw: createCatObjFromXML: CSW " . $url . " could not be loaded.");
			return false;
		}
		else{
			$this->cat_status = true;
			$e = new mb_notice("class_csw: createCatObjFromXML: CSW " . $url . " has been loaded successfully.");
			return true;
		}	
	}
	
	/**
	 * Get catalog object from DB
	 * @param $cat_id
	 * @return unknown_type
	 */
	public function createCatObjFromDB($cat_id)
	{
		$sql = "select * from cat where cat_id = $1";
		$v = array($cat_id);
		$t = array('i');
		$res = db_prep_query($sql,$v,$t);
		while($row = db_fetch_array($res)){
			$this->cat_id = $row['cat_id'];
			$this->cat_version = $row['cat_version'];
			$this->cat_abstract = administration::convertIncomingString($this->stripEndlineAndCarriageReturn($row['cat_abstract']));
			$this->cat_title = administration::convertIncomingString($this->stripEndlineAndCarriageReturn($row['cat_title']));
			$this->cat_upload_url = $row['cat_upload_url'];
			$this->cat_getcapabilities_doc = $row['cat_getcapabilities_doc'];
			$this->cat_id = $row['cat_id'];

			//Get op values
			$sql = "select * from cat_op_conf where fk_cat_id=$1";
			$v = array($cat_id);
			$t = array('i');
			$res = db_prep_query($sql,$v,$t);
			while($subrow = db_fetch_array($res)){
				$this->cat_op_values[$subrow['param_type']][$subrow['param_name']]=$subrow['param_value'];
			}
		}
	}
	
	/**
	 * Write catalog object to persistent storage
	 * @param $gui
	 * @return unknown_type
	 */
	public function setCatObjToDB($gui)
	{
		global $con;
		
		$admin = new administration();//to char_encode XML
		db_begin();
		
		# INSERT INTO TABLE cat - auto insert cat_id
		$sql = "INSERT INTO cat( ";
        $sql .= "cat_version, cat_title, cat_abstract, ";  
        $sql .= "cat_upload_url, fees, accessconstraints, providername, providersite, "; 
        $sql .= "individualname, positionname, voice, facsimile, deliverypoint, "; 
        $sql .= "city, administrativearea, postalcode, country, electronicmailaddress, "; 
        $sql .= "cat_getcapabilities_doc, cat_owner, cat_timestamp) ";
    	$sql .= "VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17,$18,$19,$20,$21)";
    	
		$v = array($this->cat_version,$this->cat_title,$this->cat_abstract,
			$this->cat_upload_url,$this->fees,$this->accessconstraints,$this->providername,$this->providersite,
			$this->contactperson, $this->contactposition, $this->contactvoicetelephone,$this->contactfacsimiletelephone,$this->delivery,
			$this->city,$this->address,$this->postcode,$this->country,$this->contactelectronicmailaddress,
			$admin->char_encode($this->cat_getcapabilities_doc),
			$_SESSION['mb_user_id'],strtotime("now"));
			
		$t = array('s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','s','i','i');
		
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			db_rollback();
		}
		
		$cat_insert_id = db_insert_id($con,'cat', 'cat_id');
		
		//GUI_CAT 
		$sql ="INSERT INTO gui_cat (fkey_gui_id, fkey_cat_id) ";
		$sql .= "VALUES($1,$2)";
		$v = array($gui,$cat_insert_id);
		$t = array('s','i');
		$res = db_prep_query($sql,$v,$t);
		if(!$res){
			db_rollback();	
		}
		
		//Insert operational values into cat_op_conf
		//CAT_OP_CONF
		
		
		foreach ($this->cat_op_values as $op_category=>$op_name_array){
			foreach($op_name_array as $op_type=>$op_value_array){
				foreach($op_value_array as $op_sub_type=>$value){
					$op_type_value = $op_type;
					if($op_sub_type != 'dflt'){
						//If not dflt, then it is either soap or xml - store this info as post_xml etc
						$op_type_value .= '_'.$op_sub_type;
					}
					if(!isset($value)){
						$value='';
					}
					//Store values
					$sql = " INSERT INTO cat_op_conf(fk_cat_id, param_type, param_name, param_value) " ;
	    			$sql .= " VALUES ($1, $2, $3, $4)";
	    			$v = array($cat_insert_id,$op_category,$op_type_value,$value);
					$t = array('i','s','s','s');
					$res = db_prep_query($sql,$v,$t);
					if(!$res){
						db_rollback();	
					}
				}
			}
		}
		
		
		//Commit Changes
		db_commit();
		
		$this->cat_id = $cat_insert_id;
	}
	
	public function displayCatalog(){
		echo "Your Catalog Has Been Successfully Added <br />";
		echo "Catalog Details: <br/>";
		echo "Id: " . $this->cat_id . " <br />";
		echo "Version: " . $this->cat_version . " <br />";
		echo "Title: " . $this->cat_title . " <br />";
		echo "Abstract: " . $this->cat_abstract . " <br />";
		
	} 
	
	/**
	 * Function to handle whitespace and carriage returns
	 * Inspired by WMS code
	 * @param $string
	 * @return unknown_type
	 */
	function stripEndlineAndCarriageReturn($string) {
	  	return preg_replace("/\n/", "", preg_replace("/\r/", " ", $string));
	}
	

}
?>
