<?php
# $Id: $
# http://www.mapbender.org/index.php/class_administration
# Copyright (C) 2002 CCGIS
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
require_once(dirname(__FILE__)."/../../../http/classes/class_owsPostQueryParser.php");

/**
 * simple class to handle the querystring and the params
 */
 
class QueryHandler{
	
	private $reqParams = array();
	private $reqParamsToLower = array();
	private $owsproxyServiceKey = 'wms';
	private $owsproxyServiceId;
	private $onlineResource;
    private $hasPost = false;
	private $serviceResourceName;
	public $req;
        public $reqMethod;
	/**
	 * Constructor of the QueryHandler
	 * 
	 */
	function __construct($postData, $request, $requestMethod){
		//${$this->typeNameParameter};
        $this->req = $request;
		$this->reqMethod = $requestMethod;
		$this->setRequestParams(array_keys($request));
		if ($postData !== false) {
			//parse post request for service / request / version parameters
			//$e = new mb_exception("owsproxy/http/classes/class_QueryHandler.php: postData: ".$postData);
			//try to parse it
			$postQueryParser = new OwsPostQueryParser($postData);
			//$e = new mb_exception($postQueryParser->serviceType);
			//overwrite default parameters with those from post if found
			if (isset($postQueryParser->serviceType) && $postQueryParser->serviceType !== '') {
				$this->reqParams['service'] = $postQueryParser->serviceType;
				$this->reqParamsToLower['service'] = $postQueryParser->serviceType;
			}
			if (isset($postQueryParser->serviceVersion) && $postQueryParser->serviceVersion !== '') {
				$this->reqParams['version'] = $postQueryParser->serviceVersion;
				$this->reqParamsToLower['version'] = $postQueryParser->serviceVersion;
			}			
			if (isset($postQueryParser->serviceRequestType) && $postQueryParser->serviceRequestType !== '') {
				$this->reqParams['request'] = $postQueryParser->serviceRequestType;
				$this->reqParamsToLower['request'] = $postQueryParser->serviceRequestType;
			}
			if (isset($postQueryParser->outputFormat) && $postQueryParser->outputFormat !== '') {
			    $this->reqParams['outputformat'] = $postQueryParser->outputFormat;
			    $this->reqParamsToLower['outputformat'] = $postQueryParser->outputFormat;
			}
			if (isset($postQueryParser->resultType) && $postQueryParser->resultType !== '') {
			    $this->reqParams['resultType'] = $postQueryParser->resultType;
			    $this->reqParamsToLower['resulttype'] = $postQueryParser->resultType;
			}
			if (isset($postQueryParser->serviceResourceName) && $postQueryParser->serviceResourceName !== '') {
				if ($this->reqParams['service'] == 'WFS') {
					switch ($this->reqParams['version']) {
						case "2.0.0":
							$this->reqParams['typenames'] = $postQueryParser->serviceResourceName;
							$this->reqParamsToLower['typenames'] = $postQueryParser->serviceResourceName;

							break;
						case "2.0.2":
							$this->reqParams['typenames'] = $postQueryParser->serviceResourceName;
							$this->reqParamsToLower['typenames'] = $postQueryParser->serviceResourceName;
							break;
						default:
							$this->reqParams['typename'] = $postQueryParser->serviceResourceName;
							$this->reqParamsToLower['typename'] = $postQueryParser->serviceResourceName;
							break;
					}
				}
			}
			if (isset($postQueryParser->serviceStoredQueryId) && $postQueryParser->serviceStoredQueryId !== '') {
				$this->reqParams['storedquery_id'] = $postQueryParser->serviceStoredQueryId;
				$this->reqParamsToLower['storedquery_id'] = $postQueryParser->serviceStoredQueryId;
			}
			$this->hasPost = true;
			
		}
		$notice = new mb_notice("const: querystring: ".$this->getQueryString());
	}
	
	/**
	 * set all query parameter-keys and -values to lowerCase
	 * so that they could be handled caseinsensitive
	 * 
	 * set another array with original keys and values
	 *
	 * @param string[] the keys of all query parameters
	 * @return string[] an associative array with request parameters keys (tolowercase) and values (tolower)
	 */
	function setRequestParams($keys){
       		for($i=0; $i<count($keys); $i++){
           		//SZ, 30.11.2007, writing REQUEST parameter values into local variable
          	 	//as key will be modified
          		$reqValue = $this->req[$keys[$i]];
          		if(strpos($keys[$i], "?") === 0){
                       	    	$keys[$i] = substr($keys[$i],1);
          	 	}
          		$this->reqParams[strtolower($keys[$i])] = $reqValue;
          	 	$this->reqParamsToLower[strtolower($keys[$i])] = $reqValue;
          		if($keys[$i] == $this->owsproxyServiceKey){
            	  	 	$this->owsproxyServiceId = $this->req[$keys[$i]];
            	  	 	$notice = new mb_notice("owsId: ".$this->owsproxyServiceId);
           		}
       		}
   	}
	
	/**
	 * checks is a request param is part of the original request
	 * 
	 * @param string request key
	 * @return boolean true if it is part of original request
	 */
	function isValidParam($key){
		if($key == 'sid'){
			return false;
		}
		else if($key == $this->owsproxyServiceKey){
			return false;
		}
		else if($key == ini_get("session.name")){
			return false;
		}
		else if($key == 'request' && $this->reqParams[$key] == 'external'){
			return false;
		}
		else if($key == 'layer_id'){ //for request to restful layer proxy - id would become part of the url!
			return false;
		}
		else{
			return true;
		}
	}
	/** 
	 * gets the request params
	 * 
	 * @return request params
	 */
	function getRequestParams(){
		return $this->reqParamsToLower;
	}
	/**
	 * modifies the layers
	 */
	 function setParam($param,$value){
		$mykeys = array_keys($this->reqParams);
		for($i=0; $i<count($mykeys);$i++){
			if(strtolower($mykeys[$i]) == strtolower($param)){
				$this->reqParams[$mykeys[$i]] = $value;
				$n = new mb_notice("QueryHandler: setParam: ".serialize($this->reqParams));
			}
		}
	 }
	 /**
	  * gets the original query string
	  * 
	  * @return string original query string
	  */
	  function getQueryString(){
		$mykeys = array_keys($this->reqParams);
		$cnt = 0;
		for($i=0; $i<count($mykeys);$i++){
			if($this->isValidParam($mykeys[$i])){	
				if($cnt > 0){ 
					$qstring .= "&"; 
				}
				$qstring .= $mykeys[$i]."=".rawurlencode(stripslashes($this->reqParams[$mykeys[$i]]));
				$cnt++;
			}
		}
		$notice = new mb_notice("getQueryString() : " . $qstring);
		return $qstring;
	  }
	 /**
	  * get the POST representation of the query
	  * 
	  * @return string POST representation for the query
	  */
	  function getPostQueryString(){
		$postQueryArray = array();
		$mykeys = array_keys($this->reqParams);
		$cnt = 0;
		for($i=0; $i<count($mykeys);$i++){
		    if($this->isValidParam($mykeys[$i])){	
		        $postQueryArray[$mykeys[$i]] = $this->reqParams[$mykeys[$i]];
		    }
		}
		$qstring = http_build_query($postQueryArray);
		$notice = new mb_notice("getPostQueryString() : " . $qstring);
		return $qstring;
	  }
	  /**
	   * gets the original request with url and query string
	   * 
	   * @return string request
	   */
	   function getRequest(){
	   		$req = $this->onlineResource.$this->getConjunctionCharacter($this->onlineResource).$this->getQueryString();
	   		$notice = new mb_notice("onlineResource:". $req);
	   		return $req;	
	   }
	   /**
	    * gets the conjunction character between url and query string
	    */
	    function getConjunctionCharacter($url){
			if(strpos($url,"?")){ 
				if(strpos($url,"?") == strlen($url)){ 
				$cchar = "";
				}else if(strpos($url,"&") == strlen($url)){
					$cchar = "";
				}else{
					$cchar = "&";
				}
			}
			if(strpos($url,"?") === false){
				$cchar = "?";
			} 
			return $cchar;  
		}
		function getOwsproxyServiceId(){
			return $this->owsproxyServiceId;
		}
		function setOnlineResource($url){
			$this->onlineResource = $url;
		}
}

?>
