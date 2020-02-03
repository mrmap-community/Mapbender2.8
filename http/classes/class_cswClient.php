<?php
# $Id$
# http://www.mapbender.org/index.php/class_cswClient
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
require_once(dirname(__FILE__)."/class_csw.php");
require_once(dirname(__FILE__)."/class_iso19139.php");
/**
 * CSW client class to make requests to and handle results from catalogues
 * @author armin11
 *
 */
class cswClient {
	var $cswId;
	var $operationName;
	var $operationResult;
	var $additionalFilter;
	//var $operationStatus;
	var $operationSuccessful;
	var $operationException;
	var $operationExceptionText;
	
	public function __construct() {
		$this->cswId = null;
		$this->operation = null;	
		$this->result = null;
		$this->operationSuccessful = false;
	}
	
	public function doRequest($cswId, $operationName, $recordId=false, $record=false, $recordtype=false, $maxrecords=false, $startposition=false, $additionalFilter=false, $datasetId=false, $cswObject=false) {
		if ($cswId != false) {
			$this->cswId = $cswId;
			$csw = new csw();
			$csw->createCatObjFromDB($this->cswId);
		} else {
			$csw = $cswObject;
		}
//$e = new mb_exception("csw_client: cat_op_values: ".$csw->cat_op_values['getrecords']['post']['dflt']);
		$operationNameCsw = $operationName;
		//$e = new mb_exception($csw->cat_op_values[$operationName]['post']);
		//check for operation support
		switch (strtolower($operationName)) {
			case "getrecords":
				if (isset($csw->cat_op_values[$operationName]['get']) ||  isset($csw->cat_op_values[$operationName]['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Operation not supported by catalogue!");
					return false;
				}
				break;
			case "getrecordbyid":
				if (isset($csw->cat_op_values[$operationName]['get']) ||  isset($csw->cat_op_values[$operationName]['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Operation not supported by catalogue!");
					return false;
				}
				break;
			case "transactionupdate":
				if (isset($csw->cat_op_values["transaction"]['get']) ||  isset($csw->cat_op_values["transaction"]['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Operation not supported by catalogue!");
					return false;
				}
				break;
			case "transactioninsert":
				if (isset($csw->cat_op_values["transaction"]['get']) ||  isset($csw->cat_op_values["transaction"]['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Operation not supported by catalogue!");
					return false;
				}
				break;
			//wrapped operations for internal usage
			case "counthits":
				if (isset($csw->cat_op_values['getrecords']['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Needed operation not supported by catalogue!");
					return false;
				}
				break;
			case "getrecordsresolvecoupling":
				if (isset($csw->cat_op_values['getrecords']['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Needed operation not supported by catalogue!");
					return false;
				}
				break;
			case "getrecordspaging":				
				if (isset($csw->cat_op_values['getrecords']['post'])) {
					//all ok
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Needed operation not supported by catalogue!");
					return false;
				}
				break;
			default: 
				break;
		}
		/*if () {
		}*/
		$metadataRecordArray = explode("\n", $record->metadata, 2);
		$metadataRecordString = $metadataRecordArray[1];
		switch (strtolower($operationName)) {
			case "getrecordbyid":
				//define standard xml request
				//maybe from xsd?
				$postRequest = '<?xml version="1.0"?>';			
				$postRequest .= '<csw:GetRecordById xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" service="CSW" version="2.0.2" outputSchema="http://www.isotc211.org/2005/gmd">';
    				$postRequest .= '<csw:Id>'.$recordId.'</csw:Id>';
				$postRequest .= '</csw:GetRecordById>';
				break;
			case "getrecords":
				
				break;
			case "getrecordsresolvecoupling":
				$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
				//TODO check the following: - resultType="hits" seems to be default behaviour
				$postRequest .= '<csw:GetRecords service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0"  xmlns:gml="http://www.opengis.net/gml" ';	
				$postRequest .= 'outputSchema="http://www.isotc211.org/2005/gmd" ';				
				$postRequest .= 'resultType="results">';
    				$postRequest .= '<csw:Query typeNames="csw:Record">';
				$postRequest .= '<csw:ElementSetName>full</csw:ElementSetName>'; //full to get all queryables?
				if ($recordtype !== false) {
            				$postRequest .= '<csw:Constraint version="1.1.0">';
                			$postRequest .= '<ogc:Filter>';
					if ($additionalFilter !== false) {
						$postRequest .= '<ogc:And>';
					}
					$postRequest .= '<ogc:Or>';

                    			$postRequest .= '<ogc:PropertyIsEqualTo>';
                       			$postRequest .= '<ogc:PropertyName>OperatesOn</ogc:PropertyName>';
                        		$postRequest .= '<ogc:Literal>'.$datasetId.'</ogc:Literal>';
                    			$postRequest .= '</ogc:PropertyIsEqualTo>';

                    			$postRequest .= '<ogc:PropertyIsEqualTo>';
                       			$postRequest .= '<ogc:PropertyName>OperatesOn</ogc:PropertyName>';
                        		$postRequest .= '<ogc:Literal>'.$recordId.'</ogc:Literal>';
                    			$postRequest .= '</ogc:PropertyIsEqualTo>';

                    			/*$postRequest .= '<ogc:PropertyIsEqualTo>';
                       			$postRequest .= '<ogc:PropertyName>OperatesOnIdentifier</ogc:PropertyName>';
                        		$postRequest .= '<ogc:Literal>'.$datasetId.'</ogc:Literal>';
                    			$postRequest .= '</ogc:PropertyIsEqualTo>';*/

                    			/*$postRequest .= '<ogc:PropertyIsEqualTo>';
                       			$postRequest .= '<ogc:PropertyName>OperatesOnIdentifier</ogc:PropertyName>';
                        		$postRequest .= '<ogc:Literal>'.$recordId.'</ogc:Literal>';
                    			$postRequest .= '</ogc:PropertyIsEqualTo>';*/

					$postRequest .= '</ogc:Or>';
					if ($additionalFilter !== false) {
						$postRequest .= $additionalFilter;
					}
					if ($additionalFilter !== false) {
						$postRequest .= '</ogc:And>';
					}
                			$postRequest .= '</ogc:Filter>';
            				$postRequest .= '</csw:Constraint>';
				} else {
					if ($additionalFilter !== false) {
						$postRequest .= '<csw:Constraint version="1.1.0">';
                				$postRequest .= '<ogc:Filter>';
						$postRequest .= $additionalFilter;
						$postRequest .= '</ogc:Filter>';
            					$postRequest .= '</csw:Constraint>';
					}
				}
        			$postRequest .= '</csw:Query>';
    				$postRequest .= '</csw:GetRecords>';
				//alter operationName
				$operationNameCsw = 'getrecords';
				break;
			case "transactionupdate":
				$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
				$postRequest .= '<csw:Transaction service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gml="http://www.opengis.net/gml">';
    				$postRequest .= '<csw:Update>';
				
				$postRequest .= $metadataRecordString;
				$postRequest .= ' </csw:Update>';
				$postRequest .= '</csw:Transaction>';
				break;
			case "transactioninsert":
				$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
				$postRequest .= '<csw:Transaction service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gml="http://www.opengis.net/gml">';
    				$postRequest .= '<csw:Insert>';
				$postRequest .= $metadataRecordString;
				$postRequest .= ' </csw:Insert>';
				$postRequest .= '</csw:Transaction>';
				break;
			case "transactiondelete":
				$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
				$postRequest .= '<csw:Transaction service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gml="http://www.opengis.net/gml">';
    				$postRequest .= '<csw:Delete>';
            			$postRequest .= '<csw:Constraint version="1.1.0">';
                		$postRequest .= '<ogc:Filter>';
                    		$postRequest .= '<ogc:PropertyIsLike wildCard="%" singleChar="_" escapeChar="/">';
                       		$postRequest .= ' <ogc:PropertyName>apiso:Identifier</ogc:PropertyName>';
                        	$postRequest .= '<ogc:Literal>'.$recordId.'</ogc:Literal>';
                    		$postRequest .= '</ogc:PropertyIsLike>';
                		$postRequest .= '</ogc:Filter>';
            			$postRequest .= '</csw:Constraint>';
        			$postRequest .= '</csw:Delete>';
    				$postRequest .= '</csw:Transaction>';
				break;
				//wrapped operations for internal usage
			case "counthits":
				$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
				//TODO check the following: - resultType="hits" seems to be default behaviour
				$postRequest .= '<csw:GetRecords service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gml="http://www.opengis.net/gml" ';					
				$postRequest .= 'resultType="hits">';
    				$postRequest .= '<csw:Query typeNames="csw:Record">';
				$postRequest .= '<csw:ElementSetName>full</csw:ElementSetName>'; //full to get all queryables?
				if ($recordtype !== false) {
            				$postRequest .= '<csw:Constraint version="1.1.0">';
                			$postRequest .= '<ogc:Filter>';
					if ($additionalFilter !== false) {
						$postRequest .= '<ogc:And>';
					}
					if ($recordtype == 'spatialData') {
						$postRequest .= '<ogc:Or>';
						foreach (array('dataset','series','tile') as $spatiaDataRecordType) {
                    					$postRequest .= '<ogc:PropertyIsEqualTo>';
                       					$postRequest .= '<ogc:PropertyName>Type</ogc:PropertyName>';
                        				$postRequest .= '<ogc:Literal>'.$spatiaDataRecordType.'</ogc:Literal>';
                    					$postRequest .= '</ogc:PropertyIsEqualTo>';
						}
						$postRequest .= '</ogc:Or>';
					} else {
						$postRequest .= '<ogc:PropertyIsEqualTo>';
                       				$postRequest .= '<ogc:PropertyName>Type</ogc:PropertyName>';
                        			$postRequest .= '<ogc:Literal>'.$recordtype.'</ogc:Literal>';
                    				$postRequest .= '</ogc:PropertyIsEqualTo>';
					}
					if ($additionalFilter !== false) {
						$postRequest .= $additionalFilter;
					}
					if ($additionalFilter !== false) {
						$postRequest .= '</ogc:And>';
					}
                			$postRequest .= '</ogc:Filter>';
            				$postRequest .= '</csw:Constraint>';
				} else {
					if ($additionalFilter !== false) {
						$postRequest .= '<csw:Constraint version="1.1.0">';
                				$postRequest .= '<ogc:Filter>';
						$postRequest .= $additionalFilter;
						$postRequest .= '</ogc:Filter>';
            					$postRequest .= '</csw:Constraint>';
					}
				}
        			$postRequest .= '</csw:Query>';
    				$postRequest .= '</csw:GetRecords>';
				//alter operationName
				$operationNameCsw = 'getrecords';
					break;
			case "getrecordspaging":
				if ($recordtype !== false && $maxrecords !== false && $startposition !== false) {				
					$postRequest = '<?xml version="1.0" encoding="UTF-8"?>';
					$postRequest .= '<csw:GetRecords service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gml="http://www.opengis.net/gml" ';
					$postRequest .= 'maxRecords="'.$maxrecords.'" ';
					$postRequest .= 'startPosition="'.$startposition.'" ';
					$postRequest .= 'outputSchema="http://www.isotc211.org/2005/gmd" ';
					$postRequest .= 'resultType="results"';
					//TODO add other values if needed!
					$postRequest .= '>';
    					$postRequest .= '<csw:Query typeNames="csw:Record">';
					//$postRequest .= '<csw:ElementSetName>brief</csw:ElementSetName>';
					//$postRequest .= '<csw:ElementSetName>summary</csw:ElementSetName>';
					$postRequest .= '<csw:ElementSetName>full</csw:ElementSetName>';
					if ($recordtype !== false) {
            					$postRequest .= '<csw:Constraint version="1.1.0">';
                				$postRequest .= '<ogc:Filter>';
						if ($additionalFilter !== false) {
							$postRequest .= '<ogc:And>';
						}
						if ($recordtype == 'spatialData') {
							$postRequest .= '<ogc:Or>';
							foreach (array('dataset','series','tile') as $spatiaDataRecordType) {
                    						$postRequest .= '<ogc:PropertyIsEqualTo>';
                       						$postRequest .= '<ogc:PropertyName>Type</ogc:PropertyName>';
                        					$postRequest .= '<ogc:Literal>'.$spatiaDataRecordType.'</ogc:Literal>';
                    						$postRequest .= '</ogc:PropertyIsEqualTo>';
							}
							$postRequest .= '</ogc:Or>';
						} else {
							$postRequest .= '<ogc:PropertyIsEqualTo>';
                       					$postRequest .= '<ogc:PropertyName>Type</ogc:PropertyName>';
                        				$postRequest .= '<ogc:Literal>'.$recordtype.'</ogc:Literal>';
                    					$postRequest .= '</ogc:PropertyIsEqualTo>';
						}
						if ($additionalFilter !== false) {
							$postRequest .= $additionalFilter;
						}
						if ($additionalFilter !== false) {
							$postRequest .= '</ogc:And>';
						}
                				$postRequest .= '</ogc:Filter>';
            					$postRequest .= '</csw:Constraint>';
					} else {
						if ($additionalFilter !== false) {
							$postRequest .= '<csw:Constraint version="1.1.0">';
                					$postRequest .= '<ogc:Filter>';
							$postRequest .= $additionalFilter;
							$postRequest .= '</ogc:Filter>';
            						$postRequest .= '</csw:Constraint>';
						}
					}
        				$postRequest .= '</csw:Query>';
    					$postRequest .= '</csw:GetRecords>';				
					//alter operationName
					$operationNameCsw = 'getrecords';
				} else {
					$e = new mb_exception("classes/class_cswClient.php: Operation getrecordspaging needs more parameters!");
				}
					break;
			default: 
				break;
			
		}
		//$e = new mb_exception("postdata: ".$postRequest);
		//do request and return result
		//$e = new mb_exception($csw->cat_op_values[$operationName]['post']);
		if (strpos($operationNameCsw, "transaction") === false) {
			//$e = new mb_exception("test: ".$csw->cat_op_values[$operationNameCsw]['post']);
			if ($cswId != false) {
				$this->operationResult = $this->getResult($csw->cat_op_values[$operationNameCsw]['post'], $postRequest);
				//csw not from database but from capabilities!
			} else {
$e = new mb_exception(json_encode($csw->cat_op_values[$operationNameCsw]));
				$this->operationResult = $this->getResult($csw->cat_op_values[$operationNameCsw]['post']['dflt'], $postRequest);
			}
			//Also give back url of operation
			if ($cswId != false) {
				$this->operationUrl = $csw->cat_op_values[$operationNameCsw]['post'];
			} else {
				$this->operationUrl = $csw->cat_op_values[$operationNameCsw]['post']['dflt'];
			}
$e = new mb_exception($this->operationUrl);
		} else {
			$this->operationResult = $this->getResult($csw->cat_op_values["transaction"]['post'], $postRequest);
			//$this->operationUrl = $csw->cat_op_values[$operationNameCsw]['post'];
		}
		//$e = new mb_exception($this->operationResult);
		//
		//if (strpos($operationNameCsw, "getrecords") !== false) {
		//	$this->operationUrl = $csw->cat_op_values[$operationNameCsw]['get'];
		//}
		//$e = new mb_exception("testresponse: ".$this->operationResult);
		//parse response
		libxml_use_internal_errors(true);
		try {
			$cswResponseObject = simplexml_load_string($this->operationResult);
			if ($cswResponseObject === false) {
				foreach(libxml_get_errors() as $error) {
        				$err = new mb_exception("class_cswClient:".$error->message);
    				}
				throw new Exception("class_cswClient:".'Cannot parse CSW Response XML!');
				$this->operationResult = "Cannot parse CSW Response XML!";
				return false;
			}
		}
		catch (Exception $e) {
    			$err = new mb_exception("class_cswClient:".$e->getMessage());
			$this->operationResult = $e->getMessage();
			return false;
		}
		if ($cswResponseObject !== false) {
//$e = new mb_exception("classes/class_cswClient.php: parsing results was successfull!");
			$cswResponseObject->registerXPathNamespace("ows", "http://www.opengis.net/ows");
			$cswResponseObject->registerXPathNamespace("gml", "http://www.opengis.net/gml");
			$cswResponseObject->registerXPathNamespace("gmd", "http://www.isotc211.org/2005/gmd");
			$cswResponseObject->registerXPathNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");
			$cswResponseObject->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
			$cswResponseObject->registerXPathNamespace("gco", "http://www.isotc211.org/2005/gco");
			$cswResponseObject->registerXPathNamespace("srv", "http://www.isotc211.org/2005/srv");
			//$iso19139Xml->registerXPathNamespace("ogc", "http://www.opengis.net/ogc");
			$cswResponseObject->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
			$cswResponseObject->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
			//$cswResponseObject->registerXPathNamespace("default", "");
			$this->operationException = $cswResponseObject->xpath('/ows:ExceptionReport');
			if (is_array($this->operationException) && count($this->operationException) > 0 || !is_array($this->operationException)) {
				$err = new mb_exception("class_cswClient first exception that occured: ".$this->operationException[0]->asXML());
				$this->operationResult = "An ows exception occured!";
			} else {
				switch (strtolower($operationName)) {
					case "getrecordbyid":
						//try to handle metadata - count the returned records
						$metadataRecord = $cswResponseObject->xpath('/csw:GetRecordByIdResponse/gmd:MD_Metadata');
						if (is_array($metadataRecord) && count($metadataRecord) <> 1 || !is_array($metadataRecord)) {
							//$err = new mb_exception(count($metadataRecord));
							//$err = new mb_exception($metadataRecord[0]->asXml());
							//$err = new mb_exception("class_cswClient.php: More or less than one metadata returned for a getrecordby id response!");
							$this->operationResult = "More or less than one metadata returned for a getrecordby id response!";
							return false;
						} else {
							$this->operationSuccessful = true;
							//return $metadataRecord[0]->asXML();
							//return "<result>One metadata record with id ".$recordId." found in catalogue</result>";
							$this->operationResult = $cswResponseObject;
							//$e = new mb_exception($cswResponseObject->asXML());
							return true;
						}
						break;
					case "getrecords":
						$metadataRecord = $cswResponseObject->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
						//$e = new mb_exception(json_encode($metadataRecord));
						if (is_array($metadataRecord) && count($metadataRecord) <> 1 || !is_array($metadataRecord)) {
							$this->operationResult = "No result for counting metadata records via csw query!";
							return false;
						} else {
							$this->operationSuccessful = true;
							$this->operationResult = $metadataRecord[0];
							return true;
						}
						break;
					case "counthits":
						//$e = new mb_exception('count hits');
						$metadataRecord = $cswResponseObject->xpath('/csw:GetRecordsResponse/csw:SearchResults/@numberOfRecordsMatched');
						//$e = new mb_exception(json_encode($metadataRecord));
						if (is_array($metadataRecord) && count($metadataRecord) <> 1 || !is_array($metadataRecord)) {
							$this->operationResult = "No result for counting metadata records via csw query!";
							return false;
						} else {
							$this->operationSuccessful = true;
							$this->operationResult = $metadataRecord[0];
							return true;
						}
					case "getrecordsresolvecoupling":
//$e = new mb_exception("classes/class_cswClient.php: operation was getrecordsresolvecoupling");
						$metadataRecord = $cswResponseObject->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
						//$e = new mb_exception(json_encode($metadataRecord));
//$e = new mb_exception("classes/class_cswClient.php: type of metadataRecord: ".gettype($metadataRecord));
						if (is_array($metadataRecord) && count($metadataRecord) < 1 || !is_array($metadataRecord)) {
//$e = new mb_exception("classes/class_cswClient.php: no array found!");
							$this->operationResult = "No result for getrecordsresolvecoupling for metadata records via csw query!";
							$this->operationSuccessful = false;
							return false;
						} else {
//$e = new mb_exception("classes/class_cswClient.php: metadata array found!");
							$this->operationSuccessful = true;
							$this->operationResult = $cswResponseObject;
							return true;
						}
						break;
					case "getrecordspaging":
						$metadataRecord = $cswResponseObject->xpath('/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata');
						if (is_array($metadataRecord) && count($metadataRecord) < 1 || !is_array($metadataRecord)) {
							$this->operationResult = "No result for getrecordspaging for metadata records via csw query!";
							$this->operationSuccessful = false;
							return false;
						} else {
							$this->operationSuccessful = true;
							//give back all records in json
							$this->operationResult = $cswResponseObject;
//$e = new mb_exception("class_cswClient.php: returned xml result from csw operation getrecordspaging: ".$cswResponseObject->asXML());
							return true;
						}
						break;
				}
			}
			//handle expections if occur
			//count objects in response - if no object available
		}
		return $cswResponseObject->asXML();
	}

	public function pushRecord($isoMetadataObject, $auth=false) {
		//check if metadata already in catalogue
		if ($this->doRequest($this->cswId, 'getrecordbyid', $isoMetadataObject->fileIdentifier)) {
			//$err = new mb_exception("class_cswClient.php: do a transaction update");
			$response = $this->doRequest($this->cswId, 'transactionupdate', false, $isoMetadataObject);
		} else {
			//$err = new mb_exception("class_cswClient.php: do a transaction insert");
			$response = $this->doRequest($this->cswId, 'transactioninsert', false, $isoMetadataObject);
		}
		return $response;
	}

	private function getResult($url, $postData, $auth=false) {
			$cswInterfaceObject = new connector();
			$cswInterfaceObject->set('httpType','POST');
			$postData = stripslashes($postData);
//$e = new mb_exception("classes/class_cswClient.php: post xml: ".$postData);
			$dataXMLObject = new SimpleXMLElement($postData);
			$postData = $dataXMLObject->asXML();
			$cswInterfaceObject->set('curlSendCustomHeaders',true);
			$cswInterfaceObject->set('httpPostData', $postData);
			$cswInterfaceObject->set('httpContentType','text/xml');
			$cswInterfaceObject->load($url);
//$e = new mb_exception("classes/class_cswClient.php: result xml: ".$cswInterfaceObject->file);
			return $cswInterfaceObject->file;
	}
	//insert or update - depends if record with same fileidentifier already exists in catalog and if timestamp is not newer than the timestamp of the new record

	public function deleteRecord($fileIdentifier, $auth=false) {
		//check if exists before!
		if ($this->doRequest($this->cswId, 'getrecordbyid', $fileIdentifier, false)) {
			//$err = new mb_exception("class_cswClient.php: do a transaction delete");
			$response = $this->doRequest($this->cswId, 'transactiondelete', $fileIdentifier, false);
		} else {
			//$err = new mb_exception("class_cswClient.php: Don't delete object - cause it doen't exists!");
			$response = "<cswClient>Don't delete record ".$fileIdentifier." - cause it doen't exists!</cswClient>";
		}
		return $response;
	}

	private function getRecordsPage($catalogId, $page, $filter=false) {

	}
}
?>
