<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");

class OwsConstraints {	
	//initialize
	var $type;
	var $id;
	var $languageCode;
	var $withHeader;
	var $asTable;
	var $outputFormat;
	var $returnDirect;

	function __construct() {
		$this->type = "wms";
		$this->id = 1;
		$this->languageCode = "en";
		$this->withHeader = false;
		$this->asTable = false;
		$this->outputFormat = "html";
		$this->returnDirect = true;
		$this->accessLimitationCodes = array();
		$this->accessLimitationDescription = array();
		if (file_exists(dirname(__FILE__)."/../../conf/inspire_LimitationsOnPublicAccess.json")) {
			$configObject = json_decode(file_get_contents("../../conf/inspire_LimitationsOnPublicAccess.json"));
            $arrayLimitationOnPublicAccessCodes = array();
            $arrayLimitationOnPublicAccessDescriptions = array();
			foreach ($configObject->codelist as $accessconstraintsCodelist) {
				$arrayLimitationOnPublicAccessCodes[] = $accessconstraintsCodelist->code;
				$arrayLimitationOnPublicAccessDescriptions[$accessconstraintsCodelist->code] = $accessconstraintsCodelist->title->de.": ".$accessconstraintsCodelist->description->de;
			}
			$this->accessLimitationCodes = $arrayLimitationOnPublicAccessCodes;
			$this->accessLimitationDescription = $arrayLimitationOnPublicAccessDescriptions;
		}
	}
	
	function getRequestParameters() {
		if (isset($_REQUEST["id"]) & $_REQUEST["id"] != "") {
			//validate to integer 
			$testMatch = $_REQUEST["id"];
			$pattern = '/^[\d]*$/';		
 			if (!preg_match($pattern,$testMatch)){ 
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter id was no integer!";	
				return $returnObject;	
 			}
			$this->id = (integer)$testMatch;
			$testMatch = NULL;	
		}
		if (isset($_REQUEST["type"]) & $_REQUEST["type"] != "") {
			//validate to wms, wfs
			$testMatch = $_REQUEST["type"];	
 			if (!($testMatch == 'wms' or $testMatch == 'wfs'  or $testMatch == 'metadata')){
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter type was not wms or wfs!";
				return $returnObject;	
 			}
			$this->type = $testMatch;
			$testMatch = NULL;
		}
		if (isset($_REQUEST["languageCode"]) & $_REQUEST["languageCode"] != "") {
			//validate to wms, wfs
			$testMatch = $_REQUEST["languageCode"];	
 			if (!($testMatch == 'de' or $testMatch == 'en' or  $testMatch == 'fr')){ 
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter languageCode was not de, en or fr!";
				return $returnObject;	
 			}
			$this->languageCode = $testMatch;
			$testMatch = NULL;
		}
		if (isset($_REQUEST["withHeader"]) & $_REQUEST["withHeader"] != "") {
			//validate to wms, wfs
			$testMatch = $_REQUEST["withHeader"];	
 			if (!($testMatch == 'true' or $testMatch == 'false')){ 
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter withHeader was not true or false!";
				return $returnObject;
 			}
			if ($testMatch == 'true'){ 
				$this->withHeader = true;		
 			} else {
				$this->withHeader = false;
			}
			$testMatch = NULL;
		}
		if (isset($_REQUEST["asTable"]) & $_REQUEST["asTable"] != "") {
			//validate to wms, wfs
			$testMatch = $_REQUEST["asTable"];	
 			if (!($testMatch == 'true' or $testMatch == 'false')){ 
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter asTable was not true or false!"; 
				return $returnObject;	
 			}
			if ($testMatch == 'true'){ 
				$this->asTable = true;		
 			} else {
				$this->asTable = false;
			}
			$testMatch = NULL;
		}
		if (isset($_REQUEST["outputFormat"]) & $_REQUEST["outputFormat"] != "") {
			//validate to iso19139 or html
			$testMatch = $_REQUEST["outputFormat"];	
 			if (!($testMatch === 'iso19139') && !($testMatch === 'html')){ 
				$returnObject['success'] = false;
				$returnObject['message'] = "Parameter outputFormat was not iso19139 or html!";	
				return $returnObject;
 			}
			if ($testMatch == 'iso19139'){ 
				$this->asTable = false;
				$this->outputFormat = "iso19139";		
 			}
			$testMatch = NULL;
		}
		$returnObject['success'] = true;
		$returnObject['message'] = "HTTP REQUEST Parameters parsed successful!";
		return $returnObject;
	}
	
	function display_text($string) {
    		$string = preg_replace("#[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]#", "<a href=\"\\0\" target=_blank>\\0</a>", $string);   
    		$string = preg_replace("#^[_a-z0-9-]+(\.[_a-z0-9-]+)*@([0-9a-z](-?[0-9a-z])*\.)+[a-z]{2}([zmuvtg]|fo|me)?$#", "<a href=\"mailto:\\0\" target=_blank>\\0</a>", $string);   
    		$string = preg_replace("#\n#", "<br>", $string);
    		return $string;
	}  

	function getDisclaimer() {
		$htmlHeader = array();
		//define header texts
		$htmlHeader['discHeader'] = _mb('Terms of use');
		$htmlHeader['discPrivacyHeader'] = _mb('Note on protection of privacy');
		$htmlHeader['accessConstraintsHeader'] = _mb('Constraints on public access');
		$htmlHeader['feesHeader'] = _mb('Information about costs/fees/licences');
		$htmlHeader['licences'] = '<b>'._mb('Licence').':</b><br>';
		$htmlHeader['networkAccess'] = _mb('This Service is <b>not available via www</b> but only in special networks. Possibly you get further information about the network availability in the following paragraph.<br>');
		$htmlHeader['logInformation'] = _mb('The access on this service is logged <b>user-related</b> by the provider. The logging is done to support automated settlement based on a contract ');
		$htmlHeader['logInformation'] .= _mb('or to fulfill legal standards.<br><b>If you do not agree on this - please don\'t use this service!</b><br>');
		$htmlHeader['logInformation'] .= _mb('If you have further questions, please contact the provider under ');

		$htmlHeader['priceInformation'][0] = _mb('The provider have defined a charge of <b>');
		$htmlHeader['priceInformation'][1] = _mb(' (euro)cent per megapixel</b> ');
		$htmlHeader['priceInformation'][2] = _mb(' for retrieved picture data. The retrieving of a typical map with a standardized resolution of 600x400 px will cost <b>');

		$htmlHeader['priceWfsInformation'][1] = _mb(' (euro)cent </b> ');
		$htmlHeader['priceWfsInformation'][2] = _mb(' for each retrieved feature. The download of 1000 features will cost <b>');

		$htmlHeader['priceInformation'][3] = _mb(' euro</b>. For information about possible discounts please contact ');




		$htmlHeader['noInformation'] = _mb('No informations about terms of use are available!');
		Mapbender::session()->set("mb_lang",$this->languageCode);
	
		if ($this->type == "wms") {
			$sql = "SELECT wms_id, wms_id as resource_id, wms.accessconstraints, wms.fees, wms.wms_network_access, wms.wms_pricevolume, wms_license_source_note as source_note, wms.wms_proxylog, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id, termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink, termsofuse.isopen, source_required from wms LEFT OUTER JOIN";
			$sql .= "  wms_termsofuse ON  (wms.wms_id = wms_termsofuse.fkey_wms_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (wms_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where wms.wms_id = $1";
		}
		if ($this->type == "wfs") {
			$sql = "SELECT wfs_id, wfs_id as resource_id, accessconstraints, fees, wfs_network_access, wfs_proxylog, wfs_pricevolume, wfs_license_source_note as source_note, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id ,termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink, termsofuse.isopen, source_required  from wfs LEFT OUTER JOIN";
			$sql .= "  wfs_termsofuse ON  (wfs.wfs_id = wfs_termsofuse.fkey_wfs_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (wfs_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where wfs.wfs_id = $1";	
		}
		if ($this->type == "metadata") {
			$sql = "SELECT metadata_id, metadata_id as resource_id, constraints as accessconstraints, md_license_source_note as source_note, fees, termsofuse.name,";
			$sql .= " termsofuse.termsofuse_id ,termsofuse.symbollink, termsofuse.description,termsofuse.descriptionlink, termsofuse.isopen, source_required  from mb_metadata LEFT OUTER JOIN";
			$sql .= "  md_termsofuse ON  (mb_metadata.metadata_id = md_termsofuse.fkey_metadata_id) LEFT OUTER JOIN termsofuse ON";
			$sql .= " (md_termsofuse.fkey_termsofuse_id=termsofuse.termsofuse_id) where mb_metadata.metadata_id = $1";	
		}
		$v = array();
		$t = array();
		array_push($t, "i");
		array_push($v, $this->id);
		$res = db_prep_query($sql,$v,$t);
		$row = db_fetch_array($res);
		if (!isset($row['resource_id'])) {
			$resultObject['success'] = false;
			$resultObject['message'] = $this->type."-resource with this id is not known!";
			return $resultObject;
		}
		//get email adress of responsible person for resource:
		if ($this->type == "wms") {
			$sql = "SELECT mb_user_email FROM wms LEFT OUTER JOIN mb_user ON  (wms_owner = mb_user.mb_user_id) WHERE wms_id=$1";
		}
		if ($this->type == "wfs") {
			$sql = "SELECT mb_user_email FROM wfs LEFT OUTER JOIN mb_user ON  (wfs_owner = mb_user.mb_user_id) WHERE wfs_id=$1";
		}
		if ($this->type == "metadata") {
			$sql = "SELECT mb_user_email FROM mb_metadata LEFT OUTER JOIN mb_user ON  (fkey_mb_user_id = mb_user.mb_user_id) WHERE metadata_id=$1";
		}
		$v = array();
		$t = array();
		array_push($t, "i");
		array_push($v, $this->id);
		$res = db_prep_query($sql,$v,$t);
		$rowOwner = db_fetch_array($res);
		//define conditions for generating disclaimer based on service information
		//if logged - only services,
		//if accessconstraints exists,
		//if fees exists,
		//if licences are defined,
		//if network access is restricted - only services
		if ((isset($row[$this->type.'_proxylog']) & $row[$this->type.'_proxylog'] != 0) or strtoupper($row['accessconstraints']) != "NONE" or strtoupper($row['fees']) != "NONE" or isset($row['termsofuse_id']) or (isset($row[$this->type.'_network_access']) & $row[$this->type.'_network_access'] != 0)) {
			$html = "";
			//generate text for json object if restrictions exists
			if ($this->withHeader && $this->outputFormat != "iso19139") {
				$html .= "<h1>".$htmlHeader['discHeader']."</h1>";
			}
			if ($this->asTable) {
				$tableBegin =  "<table>\n";
				$t_a = "\t<tr>\n\t\t<th>\n\t\t\t";
				$t_b = "\n\t\t</th>\n\t\t<td>\n\t\t\t";
				$t_c = "\n\t\t</td>\n\t</tr>\n";
				$tableEnd = "</table>\n";
				$html .= $tableBegin;
				if (isset($row[$this->type.'_proxylog']) & $row[$this->type.'_proxylog'] != 0 )  {
					$discPrivacy = $htmlHeader['logInformation'];
					$discPrivacy .= "<a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a>";
					$html .= $t_a.$htmlHeader['discPrivacyHeader'].$t_b.$discPrivacy.$t_c;
				}
				if ((strtoupper($row['accessconstraints']) != "NONE" & (str_replace(" ", "", $row['accessconstraints']) != "")) or (isset($row[$this->type.'_network_access']) & $row[$this->type.'_network_access'] != 0) ) {
					$accessConstraintsHeader = $htmlHeader['accessConstraintsHeader'];
					if (isset($row[$this->type.'_network_access']) & $row[$this->type.'_network_access'] != 0) {
						$accessConstraints = $htmlHeader['networkAccess'];
					}
					else {
						$accessConstraints = "";
					}
					$accessConstraints .= $this->display_text($row['accessconstraints']);
					$html .= $t_a.$htmlHeader['accessConstraintsHeader'].$t_b.$accessConstraints.$t_c;
				}
				if (isset($row['termsofuse_id']) or (strtoupper($row['fees']) != "NONE" & (str_replace(" ", "", $row['fees']) != "")) or ($this->type == "wms" & isset($row['wms_pricevolume']) & $row['wms_pricevolume'] != 0) or ($this->type == "wfs" & isset($row['wfs_pricevolume']) & $row['wfs_pricevolume'] != 0)) {
					$feesPart = $t_a.$htmlHeader['feesHeader'].$t_b;
					if (isset($row['termsofuse_id'])) {
						$fees = $htmlHeader['licences'];
						#$fees .= $row['name']."<br>";
						$fees .= "<a href='".$row['descriptionlink']."' target=_blank><img style='border: none;' src='".$row['symbollink']."' ".$row['name']."></a><br>";
						if (isset($row['isopen']) && $row['isopen'] == "1") {
							//show opendata symbol
							$fees .= "<br><img src='../img/od_80x15_blue.png' /><br>";
						}
						$fees .= $row['description']."<br>";
						if ($row['source_note'] !== "" && isset($row['source_note'])) {
							$fees .= _mb("Source note").": ".$row['source_note']."<br>";
						}
						$feesPart .= $fees;
					} else {
						if (isset($row['fees']) & ((strtoupper($row['fees']) != 'NONE') or ($row['fees'] != ''))) {
							$fees = $this->display_text($row['fees']);
							$feesPart .= $fees;
						}
					}
					if ($this->type == "wms" & isset($row['wms_pricevolume']) & $row['wms_pricevolume'] != 0) {
						$priceExample = (integer)$row['wms_pricevolume']*400*600/100000000;
						$priceInformation = $htmlHeader['priceInformation'][0].(integer)$row['wms_pricevolume'];
						$priceInformation .= $htmlHeader['priceInformation'][1].$htmlHeader['priceInformation'][2].$priceExample.$htmlHeader['priceInformation'][3]." <a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a><br>";	
						$feesPart .= "<br>".$priceInformation.$t_c;
					} else {
						$feesPart .= $t_c;
					}
					if ($this->type == "wfs" & isset($row['wfs_pricevolume']) & $row['wfs_pricevolume'] != 0) {
						$priceExample = (integer)$row['wfs_pricevolume']*1000/100;
						$priceInformation = $htmlHeader['priceInformation'][0].(integer)$row['wfs_pricevolume'];
						$priceInformation .= $htmlHeader['priceWfsInformation'][1].$htmlHeader['priceWfsInformation'][2].$priceExample.$htmlHeader['priceInformation'][3]." <a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a><br>";	
						$feesPart .= "<br>".$priceInformation.$t_c;
					} else {
						$feesPart .= $t_c;
					}
				}
				$html .= $feesPart.$tableEnd;
				if ($this->returnDirect) {
					echo $html;
					die();
				} else {
					return $html;
				}
			} else {
				$discPrivacy = "";		
				$fees = "";
				$accessConstraints = "";	
				//information is given in the standard way - not as a html table
				if (isset($row[$this->type.'_proxylog']) & $row[$this->type.'_proxylog'] != 0 )  {
					$discPrivacy = "<h2>".$htmlHeader['discPrivacyHeader']."</h2>".$htmlHeader['logInformation'];
					$discPrivacy .= "<a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a>";
					$html .= $discPrivacy."<br>";
				}
				if ((strtoupper($row['accessconstraints']) != "NONE" & (str_replace(" ", "", $row['accessconstraints']) != "")) or (isset($row[$this->type.'_network_access']) & $row[$this->type.'_network_access'] != 0) ) {
					$accessConstraintsHeader = $htmlHeader['accessConstraintsHeader'];
					if (isset($row[$this->type.'_network_access']) & $row[$this->type.'_network_access'] != 0) {
						$accessConstraints .= $htmlHeader['networkAccess'];
					}
					else {
						$accessConstraints .= "";
					}
					$accessConstraints .= "<h2>".$htmlHeader['accessConstraintsHeader']."</h2>".$this->display_text($row['accessconstraints']);
					$html .= $accessConstraints."<br>";
				}
				if (isset($row['termsofuse_id']) or (strtoupper($row['fees']) != "NONE" & (str_replace(" ", "", $row['fees']) != "")) or ($this->type == "wms" & isset($row['wms_pricevolume']) & $row['wms_pricevolume'] != 0) or ($this->type == "wfs" & isset($row['wfs_pricevolume']) & $row['wfs_pricevolume'] != 0)) {
					$fees .= "<h2>".$htmlHeader['feesHeader']."</h2>";
					if (isset($row['termsofuse_id'])) {
						$fees .= $htmlHeader['licences'];
						$fees .= "<a href='".$row['descriptionlink']."' target=_blank><img src='".$row['symbollink']."' ".$row['name']."></a><br>";
						if (isset($row['isopen']) && $row['isopen'] == "1") {
							//show opendata symbol
							$fees .= "<br><img src='../mapbender/img/od_80x15_blue.png' alt='opendata symbol'/><br>";
						}
						$fees .= $row['description']."<br>";
						if ($row['source_note'] !== "" && isset($row['source_note'])) {
							$fees .= _mb("Source note").": ".$row['source_note']."<br>";
						}
					} else {
						if (isset($row['fees']) & ((strtoupper($row['fees']) != 'NONE') or ($row['fees'] != ''))) {
							$fees .= $this->display_text($row['fees']);
						}
					}
					if ($this->type == "wms" & isset($row['wms_pricevolume']) & $row['wms_pricevolume'] != 0) {
						$priceExample = (integer)$row['wms_pricevolume']*400*600/100000000;
						$priceInformation = $htmlHeader['priceInformation'][0].(integer)$row['wms_pricevolume'];
						$priceInformation .= $htmlHeader['priceInformation'][1].$htmlHeader['priceInformation'][2].$priceExample.$htmlHeader['priceInformation'][3]." <a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a><br>";
						$fees .= $priceInformation."<br>";
					}
					if ($this->type == "wfs" & isset($row['wfs_pricevolume']) & $row['wfs_pricevolume'] != 0) {
						$priceExample = (integer)$row['wfs_pricevolume']*1000/100;
						$priceInformation = $htmlHeader['priceInformation'][0].(integer)$row['wfs_pricevolume'];
						$priceInformation .= $htmlHeader['priceWfsInformation'][1].$htmlHeader['priceWfsInformation'][2].$priceExample.$htmlHeader['priceInformation'][3]." <a href=\"mailto:".$rowOwner['mb_user_email']."\">".$rowOwner['mb_user_email']."</a><br>";	
						$feesPart .= "<br>".$priceInformation.$t_c;
					} else {
						$feesPart .= $t_c;
					}
					$html .= $fees."<br>";
				}
				if ($this->outputFormat != "iso19139") {
					if ($this->returnDirect) {
						echo $html;
						die();
					} else {
						return $html;
					}
				} else {
					$constraints = $discPrivacy.$accessConstraints;
					//$this->generateXmlOutput($fees,$constraints);
					$XML = $this->generateXmlOutputGdiDe($row['fees'], $row['accessconstraints'], $row['source_note'], $row['name'], $row['descriptionlink'], $row['symbollink'], $row['description'], $row['source_required']);
					if (!$this->returnDirect) {
						return $XML;
					} else {
						header("Content-type: application/xhtml+xml; charset=UTF-8");
						echo $XML;
						die();
					}
				}
			}
		} else {
			if ($this->outputFormat == "iso19139") {
				//$this->generateXmlOutput(false,false);
				$XML = $this->generateXmlOutputGdiDe(false,false,false,false,false,false,false,false);
				if (!$this->returnDirect) {
					return $XML;
				} else {
					header("Content-type: application/xhtml+xml; charset=UTF-8");
					echo $XML;
					die();
				}
			} else {
				if ($this->returnDirect) {
					echo "free";
					die();
				} else {
					return "free";
				}
			}
		}
	}
    /*
    * function to build the iso xml snippet for metadata about access and licensing 
    */
	function generateXmlOutputGdiDe($fees, $accessConstraints, $sourceNote, $licenseName, $licenseUrl, $licenseSymbolUrl, $licenseDescription, $licenseRequireSource) {
		$predefinedLicenseFound = false;
		//$e = new mb_exception("name of license: ".$licenseName);
		if (isset($licenseName) && $licenseName !== "" && $licenseName !== false) {
			$predefinedLicenseFound = true;
			//$e = new mb_exception("license found");
			$license_id = $licenseName;
			$license_name = $licenseDescription;
			$license_link = $licenseUrl;
			$license_require_source = $licenseRequireSource;
			if ($license_require_source == 't') {
				if (isset($sourceNote) && $sourceNote !==  "") {
					$license_source = $sourceNote;
				} else {
					$license_source = "Source note required by license, but not given!";
				}
			} else {
				$license_source = false;
			}
		}
		$predefinedLicenseText = "";
		if ($predefinedLicenseFound == true) {
			//generate json string (id, name , url, quelle) - see german standard gdi-de
			$jsonLicense = new stdClass();
			$jsonLicense->id = $license_id;
			$jsonLicense->name = $license_name;
			$jsonLicense->url = $license_link;
			$predefinedLicenseText = _mb('License').": ".$license_id." - ".$license_name." - ".$license_link;
			if ($license_source !== false) {
				$jsonLicense->quelle = $license_source;
				$predefinedLicenseText .= " - "._mb("Source note").": ".$license_source;
			}
		}
		//build xml snippet via dom!
		$iso19139 = new DOMDocument('1.0');
		$iso19139->encoding = 'UTF-8';
		$iso19139->preserveWhiteSpace = false;
		$iso19139->formatOutput = true;
		//generate own constraints object for collecting resourceConstraints
		$constraints = $iso19139->createElementNS('http://www.mapbender.org/metadata/constraints','mb:constraints');
		//define namespaces for possible elements in resourceConstraints
		$constraints->setAttribute("xmlns:gmd", "http://www.isotc211.org/2005/gmd");
		$constraints->setAttribute("xmlns:gco", "http://www.isotc211.org/2005/gco");
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
			switch(INSPIRE_METADATA_SPEC) {
				case "2.0.1":
					$constraints->setAttribute("xmlns:gmx", "http://www.isotc211.org/2005/gmx");
					$constraints->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
					break;
			}
		}
		//define resourceConstraints field
		$resourceConstraints=$iso19139->createElement("gmd:resourceConstraints");
		$MD_LegalConstraints=$iso19139->createElement("gmd:MD_LegalConstraints");
		/*
		 * 2.3.7 Conditions applying to access and use
		 */
		//$e = new mb_exception("class_owsConstraints.php: INSPIRE_METADATA_SPEC:".INSPIRE_METADATA_SPEC);
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
			switch(INSPIRE_METADATA_SPEC) {
				case "2.0.1":
					$conditionsElementName = "otherConstraints";
					break;
				case "1.3":
						$conditionsElementName = "useLimitation"; 
						break;
			}
		} else {
			$conditionsElementName = "useLimitation";
		}
		$useLimitation=$iso19139->createElement("gmd:".$conditionsElementName);
		$useLimitation_cs=$iso19139->createElement("gco:CharacterString");
		//check if useLimitations are stored at mb_metadata table level (maybe they are inherited from services)
		//for more information see http://www.geoportal.de/SharedDocs/Downloads/DE/GDI-DE/Dokumente/Architektur_GDI_DE_Konventionen_Metadaten_v1_1_1.pdf?__blob=publicationFile - part 3
		//if so, give them (fees from capabilities should be included in useLimitations, accessconstraints from capabilities should be included in a separate accessConstraints element!
		//if some fees are given and a predefined license is selected, give a combination of both!
		//$e = new mb_exception("fees:".$fees);
		if (isset($fees) && $fees !== '' && $fees !== false && strtoupper($fees) !== "NONE") {
			if ($predefinedLicenseText != "") {
				$useLimitationTextString = $fees." - ".$predefinedLicenseText;
			} else {
				$useLimitationTextString = $fees;
			}
		} else {
			if ($predefinedLicenseText != "") {
				$useLimitationTextString = $predefinedLicenseText;
			} else {
				$useLimitationTextString = $fees;
			}
		}
		//All information about the license and costs, ... are now concatenated in the freetextfield 
		//$e = new mb_exception($useLimitationTextString);
		switch(strtolower($useLimitationTextString)) {
			case "none":
				$useLimitationTextString = "no conditions to access and use";
				$useLimitationTextString_de = "Es gelten keine Bedingungen";
				break;
			case "":
				$useLimitationTextString = "conditions to access and use unknown";
				$useLimitationTextString_de = "Bedingungen unbekannt";
				break;
		}
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
			switch(INSPIRE_METADATA_SPEC) {
				case "2.0.1":
					//if ($useLimitationTextString != "") {
						/*
						 * <gmx:Anchor
xlink:href="http://inspire.ec.europa.eu/metadata-codelist/
ConditionsApplyingToAccessAndUse/noConditionsApply">
No conditions apply to access and use
</gmx:Anchor>
						 */
						$useConstraints=$iso19139->createElement("gmd:useConstraints");
						$MD_RestrictionCode=$iso19139->createElement("gmd:MD_RestrictionCode");
						$MD_RestrictionCode->setAttribute("codeList","http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_RestrictionCode");
						$MD_RestrictionCode->setAttribute("codeListValue","otherRestrictions");
						$useConstraints->appendChild($MD_RestrictionCode);
						$MD_LegalConstraints->appendChild($useConstraints);
					//} else {
						
					//}
					break;
			}
		}
		if (in_array($useLimitationTextString, array("conditions to access and use unknown", "no conditions to access and use"))) {
			$useLimitationText = $iso19139->createTextNode($useLimitationTextString_de);
		} else {
			$useLimitationText = $iso19139->createTextNode($useLimitationTextString);
		}
 		//TODO: Mapping of constraints between OWS/registry and INSPIRE 
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC == "2.0.1") {
			switch ($useLimitationTextString) {
				case "conditions to access and use unknown":
					$otherConstraintsAnchor = $iso19139->createElement("gmx:Anchor");
					$otherConstraintsAnchor->setAttribute("xlink:href","http://inspire.ec.europa.eu/metadata-codelist/ConditionsApplyingToAccessAndUse/conditionsUnknown");
					$otherConstraintsAnchor->appendChild($useLimitationText);
					$useLimitation->appendChild($otherConstraintsAnchor);
					break;
				case "no conditions to access and use":
					$otherConstraintsAnchor = $iso19139->createElement("gmx:Anchor");
					$otherConstraintsAnchor->setAttribute("xlink:href","http://inspire.ec.europa.eu/metadata-codelist/ConditionsApplyingToAccessAndUse/noConditionsApply");
					$otherConstraintsAnchor->appendChild($useLimitationText);
					$useLimitation->appendChild($otherConstraintsAnchor);
					break;
				default:
					
					$useLimitation_cs->appendChild($useLimitationText);
					$useLimitation->appendChild($useLimitation_cs);
					break;
			}
			if ($predefinedLicenseFound == true) {
				//$e = new mb_exception("predefined license found");
				$useLimitation2=$iso19139->createElement("gmd:".$conditionsElementName);
				$otherConstraints_cs=$iso19139->createElement("gco:CharacterString");
				//copy from above
				$otherConstraintsText = $iso19139->createTextNode(json_encode($jsonLicense, JSON_UNESCAPED_SLASHES));
				$otherConstraints_cs->appendChild($otherConstraintsText);
				$useLimitation2->appendChild($otherConstraints_cs);
			
			}
		} else {
			$useLimitation_cs->appendChild($useLimitationText);
			$useLimitation->appendChild($useLimitation_cs);
			if ($predefinedLicenseFound == true) {
				//$e = new mb_exception("predefined license found");
				$useLimitation2=$iso19139->createElement("gmd:".$conditionsElementName);
				$otherConstraints_cs=$iso19139->createElement("gco:CharacterString");
				//copy from above
				$otherConstraintsText = $iso19139->createTextNode(json_encode($jsonLicense, JSON_UNESCAPED_SLASHES));
				$otherConstraints_cs->appendChild($otherConstraintsText);
				$useLimitation2->appendChild($otherConstraints_cs);
				
			}
		}
		$MD_LegalConstraints->appendChild($useLimitation);
		if (isset($useLimitation2)) {
			$MD_LegalConstraints->appendChild($useLimitation2);
		}
		$resourceConstraints->appendChild($MD_LegalConstraints);
		$constraints->appendChild($resourceConstraints);
	
		//Also add useConstraints field with same content for compatibility with ISO19115 and GDI-DE
		/*if ($predefinedLicenseFound == true) {
			//TODO useConstraints for license/otherConstraints
			if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
				switch(INSPIRE_METADATA_SPEC) {
					case "2.0.1":
						$restrictionCodelist = "http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_RestrictionCode";
						break;
					case "1.3":
						$restrictionCodelist = "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_RestrictionCode";
						break;
				}
			} else {
				$restrictionCodelist = "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_RestrictionCode";
			}
			$resourceConstraints=$iso19139->createElement("gmd:resourceConstraints");
			$MD_LegalConstraints=$iso19139->createElement("gmd:MD_LegalConstraints");

			$useConstraints=$iso19139->createElement("gmd:useConstraints");
			$MD_RestrictionCode=$iso19139->createElement("gmd:MD_RestrictionCode");
			$MD_RestrictionCode->setAttribute("codeList", $restrictionCodelist);
			$MD_RestrictionCode->setAttribute("codeListValue", "license");
			$useConstraints->appendChild($MD_RestrictionCode);
			$MD_LegalConstraints->appendChild($useConstraints);
		
			//and otherConstraints for text
			$useConstraints=$iso19139->createElement("gmd:useConstraints");
			$MD_RestrictionCode=$iso19139->createElement("gmd:MD_RestrictionCode");
			$MD_RestrictionCode->setAttribute("codeList", $restrictionCodelist);
			$MD_RestrictionCode->setAttribute("codeListValue", "otherRestrictions");
			$useConstraints->appendChild($MD_RestrictionCode);
			$MD_LegalConstraints->appendChild($useConstraints);

			//text element
			$otherConstraints=$iso19139->createElement("gmd:otherConstraints");
			$otherConstraints_cs=$iso19139->createElement("gco:CharacterString");
			//copy from above
			$otherConstraintsText = $iso19139->createTextNode($useLimitationTextString);
			$otherConstraints_cs->appendChild($otherConstraintsText);
			$otherConstraints->appendChild($otherConstraints_cs);
			$MD_LegalConstraints->appendChild($otherConstraints);
			//json representation
			$otherConstraints=$iso19139->createElement("gmd:otherConstraints");
			$otherConstraints_cs=$iso19139->createElement("gco:CharacterString");
			//copy from above
			$otherConstraintsText = $iso19139->createTextNode(json_encode($jsonLicense));
			$otherConstraints_cs->appendChild($otherConstraintsText);
			$otherConstraints->appendChild($otherConstraints_cs);
			$MD_LegalConstraints->appendChild($otherConstraints);
			$resourceConstraints->appendChild($MD_LegalConstraints);
			$constraints->appendChild($resourceConstraints);
		}*/
        //2.3.6 Limitations on public access
		//$this->accessLimitationCodes
		$resourceConstraints=$iso19139->createElement("gmd:resourceConstraints");
		$MD_LegalConstraints=$iso19139->createElement("gmd:MD_LegalConstraints");
		$accessConstraintsXml=$iso19139->createElement("gmd:accessConstraints");
		$MD_RestrictionCode=$iso19139->createElement("gmd:MD_RestrictionCode");
		$accessConstraintExists = isset($accessConstraints) && $accessConstraints !== '' && strtoupper($accessConstraints) !== 'NONE' && $accessConstraints !== false;
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
			switch(INSPIRE_METADATA_SPEC) {
				case "2.0.1":
					$accessLimitationCodeList = "http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_RestrictionCode";
					/*
					 * Maybe there is an error in the TG Metadata 2.0.1? - TODO: check the right way to implement noLimitations
					 */
					//if ($accessConstraintExists) {
						$limitationOnPublicAccess = "otherRestrictions";
					//} else {
					//	$limitationOnPublicAccess = "noLimitations";
					//}
					break;
				case "1.3":
					$accessLimitationCodeList = "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_RestrictionCode";		
					$limitationOnPublicAccess = "otherRestrictions";
					$MD_RestrictionCodeText=$iso19139->createTextNode($limitationOnPublicAccess);
					break;
			}
		} else {
			$accessLimitationCodeList = "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_RestrictionCode";
			$limitationOnPublicAccess = "otherRestrictions";
			$MD_RestrictionCodeText=$iso19139->createTextNode($limitationOnPublicAccess);
		}
		$MD_RestrictionCode->setAttribute("codeList", $accessLimitationCodeList);
		$MD_RestrictionCode->setAttribute("codeListValue", $limitationOnPublicAccess);
		
		$otherConstraints=$iso19139->createElement("gmd:otherConstraints");
		$otherConstraints_cs=$iso19139->createElement("gco:CharacterString");
		if (defined("INSPIRE_METADATA_SPEC") && INSPIRE_METADATA_SPEC != "") {
			switch(INSPIRE_METADATA_SPEC) {
				case "2.0.1":
					if ($accessConstraintExists) {
						//$e = new mb_exception(json_encode($this->accessLimitationCodes));
						//$e = new mb_exception($accessConstraints);
						if (in_array($accessConstraints, $this->accessLimitationCodes)) {
							//$e = new mb_exception(json_encode($this->accessLimitationCodes));
							$accessLimitationAnchor = $iso19139->createElement("gmx:Anchor");
							$accessLimitationAnchor->setAttribute("xlink:href","http://inspire.ec.europa.eu/metadata-codelist/LimitationsOnPublicAccess/".$accessConstraints);
							$accessLimitationAnchorText=$iso19139->createTextNode($this->accessLimitationDescription[$accessConstraints]);
							$accessLimitationAnchor->appendChild($accessLimitationAnchorText);
							$otherConstraints->appendChild($accessLimitationAnchor);
						} else {
							$otherConstraintsText = $iso19139->createTextNode($accessConstraints);
							$otherConstraints_cs->appendChild($otherConstraintsText);
							$otherConstraints->appendChild($otherConstraints_cs);
						}
						if (isset($MD_RestrictionCodeText)) {
							$MD_RestrictionCode->appendChild($MD_RestrictionCodeText);
						}
					} else {
						//add anchor
						$accessLimitationAnchor = $iso19139->createElement("gmx:Anchor");
						$accessLimitationAnchor->setAttribute("xlink:href","http://inspire.ec.europa.eu/metadata-codelist/LimitationsOnPublicAccess/noLimitations");
						//$accessLimitationText = $iso19139->createTextNode("No limitations on public access");
						$accessLimitationText = $iso19139->createTextNode("Es gelten keine Zugriffsbeschränkungen");
						$accessLimitationAnchor->appendChild($accessLimitationText);
						$otherConstraints->appendChild($accessLimitationAnchor);
						if (isset($MD_RestrictionCodeText)) {
							$MD_RestrictionCode->appendChild($MD_RestrictionCodeText);
						}
					}
					break;
				case "1.3":
					if ($accessConstraintExists) {
						$otherConstraintsText=$iso19139->createTextNode($accessConstraints);
					} else {
						$otherConstraintsTextString = "no constraints"; //INSPIRE
						$otherConstraintsText=$iso19139->createTextNode($otherConstraintsTextString);
					
					}
					$otherConstraints_cs->appendChild($otherConstraintsText);
					$otherConstraints->appendChild($otherConstraints_cs);
					if (isset($MD_RestrictionCodeText)) {
						$MD_RestrictionCode->appendChild($MD_RestrictionCodeText);
					}
					break;
			}
		} else {
			if ($accessConstraintExists) {
				$otherConstraintsText=$iso19139->createTextNode($accessConstraints);
			} else {
				$otherConstraintsTextString = "no constraints"; //INSPIRE
				$otherConstraintsText=$iso19139->createTextNode($otherConstraintsTextString);
				
			}
			$otherConstraints_cs->appendChild($otherConstraintsText);
			$otherConstraints->appendChild($otherConstraints_cs);
			if (isset($MD_RestrictionCodeText)) {
				$MD_RestrictionCode->appendChild($MD_RestrictionCodeText);
			}
		}
			
		$accessConstraintsXml->appendChild($MD_RestrictionCode);
		
		$MD_LegalConstraints->appendChild($accessConstraintsXml);
		$MD_LegalConstraints->appendChild($otherConstraints);
		$resourceConstraints->appendChild($MD_LegalConstraints);
		$constraints->appendChild($resourceConstraints);
		$test = $iso19139->appendChild($constraints);
		$XML = $iso19139->saveXML();
	 	return $XML;

		
	}

	function generateXmlOutput($fees,$serviceconstraints) {
		//example from INSPIRE Metadata TG 29.10.2013 if no accessconstraints exists:
		/*
		<gmd:resourceConstraints>
			<gmd:MD_LegalConstraints>
				<gmd:accessConstraints>
					<gmd:MD_RestrictionCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_RestrictionCode" codeListValue="otherRestrictions">otherRestrictions
					</gmd:MD_RestrictionCode>
				</gmd:accessConstraints>
				<gmd:otherConstraints>
					<gco:CharacterString>no limitations</gco:CharacterString>
				</gmd:otherConstraints>
			</gmd:MD_LegalConstraints>
		</gmd:resourceConstraints>
		<gmd:resourceConstraints>
			<gmd:MD_SecurityConstraints>
				<gmd:classification>
					<gmd:MD_ClassificationCode codeList="./resources/codeList.xml#MD_ClassificationCode" codeListValue="unclassified">unclassified</gmd:MD_ClassificationCode>
				</gmd:classification>
			</gmd:MD_SecurityConstraints>
		</gmd:resourceConstraints>
		*/
		//part for use limitations
		/* - for none: no conditions apply
		<gmd:resourceConstraints>
			<gmd:MD_Constraints>
				<gmd:useLimitation>
					<gco:CharacterString>Reproduction for non-commercial purposes is authorised, provided the source is acknowledged. Commercial use is not permitted without prior written consent of the JRC. Reports, articles, papers, scientific and non-scientific works of any form, including tables, maps, or any other kind of output, in printed or electronic form, based in whole or in part on the data
supplied, must contain an acknowledgement of the form: Data re-used from the European Drought Observatory (EDO) http://edo.jrc.ec.europa.eu The SPI data were created as part of JRC's research activities. Although every care has been taken in preparing and testing the data, JRC cannot guarantee that the data are correct; neither does JRC accept any liability whatsoever for any error, missing data or omission in the data, or for any loss or damage arising from its use. The JRC will not be responsible for any direct or indirect use which might be made of the data. The JRC does not provide any assistance or support in using the data</gco:CharacterString>
				</gmd:useLimitation>
			</gmd:MD_Constraints>
		</gmd:resourceConstraints>
		*/
		//generate iso constraints part for integration into metadata xml
		//via dom!
		$iso19139Doc = new DOMDocument('1.0');
		$iso19139Doc->encoding = 'UTF-8';
		$iso19139Doc->preserveWhiteSpace = false;
		$iso19139Doc->formatOutput = true;
		//generate own constraints object for collecting resourceConstraints
		$constraints = $iso19139Doc->createElementNS('http://www.mapbender.org/metadata/constraints','mb:constraints');
		$constraints->setAttribute("xmlns:gmd", "http://www.isotc211.org/2005/gmd");
		$constraints->setAttribute("xmlns:gco", "http://www.isotc211.org/2005/gco");
		$resourceConstraints = $iso19139Doc->createElement('gmd:resourceConstraints');
		//build up all things which are needed
		$MD_LegalConstraints = $iso19139Doc->createElement("gmd:MD_LegalConstraints");
		$accessConstraints = $iso19139Doc->createElement("gmd:accessConstraints");
		$MD_RestrictionCode = $iso19139Doc->createElement("gmd:MD_RestrictionCode");
		$MD_RestrictionCode->setAttribute("codeList", "http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_RestrictionCode");
		$MD_RestrictionCode->setAttribute("codeListValue", "otherRestriction");
		$MD_RestrictionCodeText = $iso19139Doc->createTextNode("otherRestrictions");
		$otherConstraints = $iso19139Doc->createElement("gmd:otherConstraints");
		$CharacterString = $iso19139Doc->createElement("gco:CharacterString");
		if (isset($accessConstraints) && $accessConstraints !== "") {
			$CharacterStringText = $iso19139Doc->createCDATASection($serviceconstraints);
		} else {
			$CharacterStringText = $iso19139Doc->createTextNode("no limitations");
		}
		$CharacterString->appendChild($CharacterStringText);
		$otherConstraints->appendChild($CharacterString);
		//build structure
		$MD_RestrictionCode->appendChild($MD_RestrictionCodeText);
		$accessConstraints->appendChild($MD_RestrictionCode);
		$MD_LegalConstraints->appendChild($accessConstraints);
		$MD_LegalConstraints->appendChild($otherConstraints);
		$resourceConstraints->appendChild($MD_LegalConstraints);
		//resourceConstraints for security - classification
		$resourceConstraintsSec = $iso19139Doc->createElement('gmd:resourceConstraints');
		$MD_SecurityConstraints = $iso19139Doc->createElement('gmd:MD_SecurityConstraints');
		$classification = $iso19139Doc->createElement('gmd:classification');
		$MD_ClassificationCode = $iso19139Doc->createElement('gmd:MD_ClassificationCode');
		$MD_ClassificationCode->setAttribute("codeList", "./resources/codeList.xml#
MD_ClassificationCode");
		$MD_ClassificationCode->setAttribute("codeListValue", "unclassified");
		$MD_ClassificationCodeText = $iso19139Doc->createTextNode("unclassified");
		$MD_ClassificationCode->appendChild($MD_ClassificationCodeText);
		$classification->appendChild($MD_ClassificationCode);
		$MD_SecurityConstraints->appendChild($classification);
		$resourceConstraintsSec->appendChild($MD_SecurityConstraints);
		//part for uselimitation
		//resourceConstraints for useLimitations
		$resourceConstraintsUseL = $iso19139Doc->createElement('gmd:resourceConstraints');
		$MD_Constraints = $iso19139Doc->createElement('gmd:MD_Constraints');
		$useLimitation = $iso19139Doc->createElement('gmd:useLimitation');
		$CharacterString = $iso19139Doc->createElement("gco:CharacterString");
		if ($fees && $fees != "") {
			$CharacterStringText = $iso19139Doc->createCDATASection($fees);
		} else {
			$CharacterStringText = $iso19139Doc->createTextNode("no conditions apply");
		}
		$CharacterString->appendChild($CharacterStringText);
		$useLimitation->appendChild($CharacterString);
		$MD_Constraints->appendChild($useLimitation);
		$resourceConstraintsUseL->appendChild($MD_Constraints);
		$constraints->appendChild($iso19139Doc->createComment("use limitations"));
		$resourceConstraintsUseL = $constraints->appendChild($resourceConstraintsUseL);
		$constraints->appendChild($iso19139Doc->createComment("general access constraints"));
		$resourceConstraints = $constraints->appendChild($resourceConstraints);
		$constraints->appendChild($iso19139Doc->createComment("security constraints - not needed - maybe there for declaring a simple classification system"));
		//TODO: not now!
		//$resourceConstraintsSec = $constraints->appendChild($resourceConstraintsSec);
 		$iso19139 = $iso19139Doc->appendChild($constraints);
		$XML = $iso19139Doc->saveXML();
		if (!$this->returnDirect) {
			return $XML;
		} else {
			header("Content-type: application/xhtml+xml; charset=UTF-8");
			echo $XML;
			die();
		}
	}
}
?>
