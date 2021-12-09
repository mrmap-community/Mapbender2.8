<?php
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
require_once(dirname(__FILE__) . "/../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../http/classes/class_iso19139.php");
require_once(dirname(__FILE__) . "/../http/classes/class_Uuid.php");

$startTimeForAll = microtime(true);
$metadataClass = new Iso19139();
$injectRegistryUuid = false;

$debug = false;

if ($debug == true) {
    $metadataDir = str_replace("../../", "../", METADATA_DIR)."/test/";
} else {
    $metadataDir = str_replace("../../", "../", METADATA_DIR);
}

if (defined("MAPBENDER_REGISTRY_UUID") && MAPBENDER_REGISTRY_UUID != "") {
    $uuid = new Uuid(MAPBENDER_REGISTRY_UUID);
    $injectRegistryUuid = $uuid->isValid();
}
// load translation of inspire categories from database to allow
$sql_categories = "SELECT * FROM inspire_category";
$v = array();
$t = array();
$res_categories = db_prep_query($sql_categories,$v,$t);
$inspireCatArray = array();
while($row = db_fetch_array($res_categories)){
    //$mb_user_groups[$cnt_groups] = db_result($res_groups,$cnt_groups,"fkey_mb_group_id");
    $inspireCatArray[$row["inspire_category_code_en"]] = $row["inspire_category_code_de"];
}
$numberOfFile = 0;
if ($handle = opendir($metadataDir)) {
    //echo "Read files from temporary metadata folder:<br>";
    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) {
        $startTime = microtime(true);
        //check if file name begin with "mapbender";
        $pos = strpos($file, "mapbender");
        if ($pos !== false) {
            //delete file with unlink
            //unlink($metadataDir."/".$file); 
            //echo filesize($metadataDir."/".$file)."<br>";
            //read with class Iso19139()
            $h = fopen($metadataDir."/".$file, "r");
            $newKeywordsIndex = 0;
            logMessages($metadataDir."/".$file);
            if (filesize($metadataDir."/".$file) != 0) {
                $metadataXml = fread($h, filesize($metadataDir."/".$file));

				//repair https problems from komserv!!!!!
				$metadataXml = str_replace('https://standards.iso.org', 'http://standards.iso.org', $metadataXml);
				$metadataXml = str_replace('https://www.isotc211.org', 'http://www.isotc211.org', $metadataXml);
				$metadataXml = str_replace('https://www.w3.org', 'http://www.w3.org', $metadataXml);
				$metadataXml = str_replace('https://www.opengis.net', 'http://www.opengis.net', $metadataXml);
				$metadataXml = str_replace('https://schemas.opengis.net', 'http://schemas.opengis.net', $metadataXml);
				try {
					$metadataObject = $metadataClass->createMapbenderMetadataFromXML($metadataXml);
				} catch ( Exception $e ) {
					$err = new mb_exception ( "php/mod_qualifyPersistedMetadataXm.php:" . $e->getMessage () );
					logMessages("Problem when loading metadata xml into mapbender metadata object - problematic file: ".$metadataDir."/".$file);
					fclose($h); //close file for read
					continue;
				}

				logMessages("fileIdentifier: ".$metadataObject->fileIdentifier);
				logMessages("type: ".$metadataObject->hierarchyLevel);
				
				if (in_array('inspireidentifiziert', $metadataObject->keywords) && !in_array('Regional', $metadataObject->keywords) && !in_array('Lokal', $metadataObject->keywords) && !in_array('bplan', $metadataObject->keywords) && $metadataObject->hierarchyLevel == 'dataset') {
					//echo $metadataObject->title."<br>";
					//echo $metadataDir."/".$file." has keyword inspireidentifiziert!<br>";
					$keywordsArray[$newKeywordsIndex]->keyword = "Regional";
					$keywordsArray[$newKeywordsIndex]->thesaurusTitle = "Spatial scope";
					$keywordsArray[$newKeywordsIndex]->thesaurusPubDate = "2019-05-22";
					/*$newKeywordsIndex++;
					$keywordsArray[$newKeywordsIndex]->keyword = "Regional1";
					$keywordsArray[$newKeywordsIndex]->thesaurusTitle = "Spatial scope1";
					$keywordsArray[$newKeywordsIndex]->thesaurusPubDate = "2019-05-22";
							$e = new mb_exception("test3");*/
				}
				if (in_array('bplan', $metadataObject->keywords) && !in_array('Regional', $metadataObject->keywords) && !in_array('Lokal', $metadataObject->keywords) && $metadataObject->hierarchyLevel == 'dataset' && in_array('inspireidentifiziert', $metadataObject->keywords)) {
					$keywordsArray[$newKeywordsIndex]->keyword = "Lokal";
					$keywordsArray[$newKeywordsIndex]->thesaurusTitle = "Spatial scope";
					$keywordsArray[$newKeywordsIndex]->thesaurusPubDate = "2019-05-22";
				}
				//workaround for hesse
				if (in_array('mapbenderLocal', $metadataObject->keywords) && !in_array('bplan', $metadataObject->keywords) && !in_array('Regional', $metadataObject->keywords) && !in_array('Lokal', $metadataObject->keywords) && $metadataObject->hierarchyLevel == 'dataset' && in_array('inspireidentifiziert', $metadataObject->keywords)) {
					$keywordsArray[$newKeywordsIndex]->keyword = "Lokal";
					$keywordsArray[$newKeywordsIndex]->thesaurusTitle = "Spatial scope";
					$keywordsArray[$newKeywordsIndex]->thesaurusPubDate = "2019-05-22";
				}
				//logMessages("Actual keywords: ".json_encode($metadataObject->keywords));
				if ($injectRegistryUuid && !in_array($uuid, $metadataObject->keywords)) {  //add mapbender registry keyword
					$newKeywordsIndex++;
					$keywordsArray[$newKeywordsIndex]->keyword = $uuid;
					$keywordsArray[$newKeywordsIndex]->thesaurusTitle = "mapbender.2.registryId";
					$keywordsArray[$newKeywordsIndex]->thesaurusPubDate = "2019-10-30";
				}    
                //logMessages("count keywordsArray: ".count($keywordsArray)." - count metadataObject->keywords: ".count($metadataObject->keywords));
				if ($debug == true) {
				    $metadataXml = addKeywords($metadataXml, $keywordsArray, $inspireCatArray);
							logMessages("Keywords will be injected without test before - debug mode!");
				} else {
					if (count($keywordsArray) > 0 && count($metadataObject->keywords) > 0) {
					    $metadataXml = addKeywords($metadataXml, $keywordsArray, $inspireCatArray);
								logMessages("Keywords will be injected!");
					} else {
						//TODO inject keyword after some other element!
						logMessages("No keywords will be injected - file unaltered!");
					}
				}
				unset($keywordsArray);
				//debug
				//header("Content-type: text/xml");
				//echo $metadataXml;
				//die();
				//save xml to file	
			}
			fclose($h); //close file for read
			
			$metadataXml = exchangeLanguageAndDeletePolygon( $metadataXml );
			$metadataXml = str_replace('http://www.opengis.net/gml/3.2', 'http://www.opengis.net/gml', $metadataXml);
		    	$metadataXml = str_replace('http://www.opengis.net/gml', 'http://www.opengis.net/gml/3.2', $metadataXml);
			//open same file for write and insert xml into the file!
            		$writeHandle = fopen($metadataDir."/".$file, "w+");
			fwrite($writeHandle, $metadataXml);
			fclose($writeHandle);
			logMessages("Number of altered file: ".($numberOfFile + 1));
			$numberOfFile++;
			$timeToBuild = microtime(true) - $startTime;
            		logMessages("time to alter xml: ".$timeToBuild);
			//save xml to file
			//echo $metadataDir."/".$file." will be altered!<br>";
		} else {
				//echo "$file will not be altered!<br>";
		}
    }
    closedir($handle);
    $timeToBuildAll = microtime(true) - $startTimeForAll;
    logMessages("time to alter all xml: ".$timeToBuildAll);
}

/** Inserts a new node after a given reference node. Basically it is the complement to the DOM specification's
 * insertBefore() function.
 * @param \DOMNode $newNode The node to be inserted.
 * @param \DOMNode $referenceNode The reference node after which the new node should be inserted.
 * @return \DOMNode The node that was inserted.
 * https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
 */
function insertAfter(\DOMNode $newNode, \DOMNode $referenceNode)
{
    if($referenceNode->nextSibling === null) {
        return $referenceNode->parentNode->appendChild($newNode);
    } else {
        return $referenceNode->parentNode->insertBefore($newNode, $referenceNode->nextSibling);
    }
}

function exchangeLanguageAndDeletePolygon($metadataXml) {
	// do parsing with dom, cause we want to alter the xml which have been parsed afterwards
	$metadataDomObject = new DOMDocument ();
	libxml_use_internal_errors ( true );
	try {
		$metadataDomObject->loadXML ( $metadataXml );
		if ($metadataDomObject === false) {
			foreach ( libxml_get_errors () as $error ) {
				$err = new mb_exception ( "php/mod_qualifyPersistedMetadataXml.php:" . $error->message );
			}
			throw new Exception ( "php/mod_qualifyPersistedMetadataXm.php:" . 'Cannot parse metadata with dom!' );
		}
	} catch ( Exception $e ) {
		$err = new mb_exception ( "php/mod_qualifyPersistedMetadataXm.php:" . $e->getMessage () );
	}
	if ($metadataDomObject !== false) {
		// importing namespaces
		$xpath = new DOMXPath ( $metadataDomObject );
		$rootNamespace = $metadataDomObject->lookupNamespaceUri ( $metadataDomObject->namespaceURI );
		$xpath->registerNamespace ( 'defaultns', $rootNamespace );
		// $e = new mb_exception($rootNamespace);
		// $xpath->registerNamespace('georss','http://www.georss.org/georss');
		$xpath->registerNamespace ( "csw", "http://www.opengis.net/cat/csw/2.0.2" );
		$xpath->registerNamespace ( "gml", "http://www.opengis.net/gml/3.2" );
		$xpath->registerNamespace ( "gco", "http://www.isotc211.org/2005/gco" );
		$xpath->registerNamespace ( "gmd", "http://www.isotc211.org/2005/gmd" );
		$xpath->registerNamespace ( "gts", "http://www.isotc211.org/2005/gts" );
		$xpath->registerNamespace ( "gmx", "http://www.isotc211.org/2005/gmx" );
		// $xpath->registerNamespace("srv", "http://www.isotc211.org/2005/srv");
		$xpath->registerNamespace ( "xsi", "http://www.w3.org/2001/XMLSchema-instance" );
		$xpath->registerNamespace ( "xlink", "http://www.w3.org/1999/xlink" );
		//
		$newLanguageCodeXml = '<?xml version="1.0" encoding="UTF-8"?><gmd:language xmlns:gmd="http://www.isotc211.org/2005/gmd"><gmd:LanguageCode codeList="http://www.loc.gov/standards/iso639-2/" codeListValue="ger">Deutsch</gmd:LanguageCode></gmd:language>';
		// exchange language information
		$contactDomObject = new DOMDocument ();
		$contactDomObject->loadXML ( $newLanguageCodeXml );
		$xpathInput = new DOMXpath ( $contactDomObject );
		$inputNodeList = $xpathInput->query ( '/gmd:language' );
		// $inputNode = $inputNodeList->item(0)->firstChild; responsible party
		$inputNode = $inputNodeList->item ( 0 );
		$languagePathToExchange = array (
				'//gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:language',
				'//gmd:MD_Metadata/gmd:language' 
		);
		foreach ( $languagePathToExchange as $languagePath ) {
			// get contact node or node list
			$languageNodeList = $xpath->query ( $languagePath );
			// test to delete all contact nodes more than one
			for($i = 0; $i < $languageNodeList->length; $i ++) {
				if ($i == 0) {
					$temp = $languageNodeList->item ( $i );
					$temp->parentNode->replaceChild ( $metadataDomObject->importNode ( $inputNode, true ), $temp );
				}
				if ($i > 0) {
					$temp = $languageNodeList->item ( $i ); // avoid calling a function twice
					$temp->parentNode->removeChild ( $temp );
				}
			}
		}
		//delete polygonal extents
		$xpathPolygon = "//gmd:MD_Metadata/gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent[gmd:EX_Extent/gmd:geographicElement/gmd:EX_BoundingPolygon]";
		$polygonNodeList = $xpath->query ( $xpathPolygon );
		foreach($polygonNodeList as $element){
			$element->parentNode->removeChild($element);
		}
	}
	return $metadataDomObject->saveXML ();
}

function addKeywords($metadataXml, $keywordsArray, $inspireCategoriesArray=false) {
    //logMessages("function addKeywords");
    //parse XML part
	//do parsing with dom, cause we want to alter the xml which have been parsed afterwards
	$metadataDomObject = new DOMDocument();
	$metadataDomObject->preserveWhiteSpace = false;
	$metadataDomObject->formatOutput = true;
	libxml_use_internal_errors(true);
	try {
		$metadataDomObject->loadXML($metadataXml);
		if ($metadataDomObject === false) {
			foreach(libxml_get_errors() as $error) {
        			logMessages("php/mod_qualifyPersistedMetadataXml.php: ".$error->message);
    			}
			throw new Exception("php/mod_qualifyPersistedMetadataXml.php:".'Cannot parse metadata with dom!');
		}
	}
	catch (Exception $e) {
    		logMessages("php/mod_qualifyPersistedMetadataXml.php: ".$e->getMessage());
	}
	if ($metadataDomObject !== false) {
		//importing namespaces
		$xpath = new DOMXPath($metadataDomObject);
		$rootNamespace = $metadataDomObject->lookupNamespaceUri($metadataDomObject->namespaceURI);
		$xpath->registerNamespace('defaultns', $rootNamespace); 
		//$xpath->registerNamespace('georss','http://www.georss.org/georss');
		$xpath->registerNamespace("csw", "http://www.opengis.net/cat/csw/2.0.2");
		$xpath->registerNamespace("gml", "http://www.opengis.net/gml/3.2");
		$xpath->registerNamespace("gco", "http://www.isotc211.org/2005/gco");
		$xpath->registerNamespace("gmd", "http://www.isotc211.org/2005/gmd");
		$xpath->registerNamespace("gts", "http://www.isotc211.org/2005/gts");
        $xpath->registerNamespace("xsi", "http://www.w3c.org/2001/XMLSchema-instance");
		//$xpath->registerNamespace("srv", "http://www.isotc211.org/2005/srv");
		$xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
		
        // Qualify schemaLocation ***********************************************************************************************************************************************
        // extract attribute schemaLocation - alter it if it has only one uri entry for gmd!
        $MD_MetadataNodeList = $xpath->query("//gmd:MD_Metadata[@xsi:schemaLocation = 'http://www.isotc211.org/2005/gmd']");
        if ($MD_MetadataNodeList->item(0) != null) {
            $MD_MetadataNodeList->item(0)->setAttribute('xsi:schemaLocation', 'http://www.isotc211.org/2005/gmd http://schemas.opengis.net/csw/2.0.2/profiles/apiso/1.0.0/apiso.xsd');
            logMessages("schemaLocation attribute extended!!!!");
        } else {
            logMessages("schemaLocation attribute is not http://www.isotc211.org/2005/gmd. Nothing will be done!");
        }
        $MD_MetadataNodeList = $xpath->query("//gmd:MD_Metadata[@xsi:schemaLocation = 'http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd']");
        if ($MD_MetadataNodeList->item(0) != null) {
            $MD_MetadataNodeList->item(0)->setAttribute('xsi:schemaLocation', 'http://www.isotc211.org/2005/gmd http://schemas.opengis.net/csw/2.0.2/profiles/apiso/1.0.0/apiso.xsd');
            logMessages("schemaLocation attribute extended!!!!");
        } else {
            logMessages("schemaLocation attribute is not http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd. Nothing will be done!");
        }
        // Qualify schemaLocation end *******************************************************************************************************************************************

        // ****************************************************************************************
        // 2021-07-01 - new for translation of inspire themes and missing mandatory spatialRepresentationType
        // ****************************************************************************************
        if ($inspireCategoriesArray != false) {
            $inspireCategoryNodeList = $xpath->query("//gmd:MD_Metadata/gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword[../gmd:thesaurusName/gmd:CI_Citation/gmd:title/gco:CharacterString='GEMET - INSPIRE themes, version 1.0']/gco:CharacterString");
            foreach ($inspireCategoryNodeList as $inspireCategoryKeyword) {
                if (array_key_exists($inspireCategoryKeyword->nodeValue, $inspireCategoriesArray)) {
                    logMessages("Exchange " . $inspireCategoryKeyword->nodeValue . " with " . $inspireCategoriesArray[$inspireCategoryKeyword->nodeValue]);
                    $newElement = $metadataDomObject->createTextNode($inspireCategoriesArray[$inspireCategoryKeyword->nodeValue]);
                    $gco__character_string = $metadataDomObject->createElement('gco:CharacterString');
                    $newElement = $metadataDomObject->createTextNode($inspireCategoriesArray[$inspireCategoryKeyword->nodeValue]);
                    $gco__character_string->appendChild($newElement);
                    $inspireCategoryKeyword->parentNode->replaceChild($gco__character_string, $inspireCategoryKeyword);
                }
            }
        }
        // first check, if hierachyLevel is dataset or series or tile
        $hierarchyLevelNodeList = $xpath->query('//gmd:MD_Metadata/gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue');
        if ($hierarchyLevelNodeList->length == 1) {
            if (in_array($hierarchyLevelNodeList->item(0)->nodeValue, array(
                "dataset",
                "series",
                "tile"
            ))) {
                // if gmd:spatialRepresentationType does not exists - add it to the metadata rercord for dataset metadata ;-)
                $spatialRepresentationTypeNodeList = $xpath->query("//gmd:MD_Metadata/gmd:identificationInfo/*/gmd:spatialRepresentationType");
                if ($spatialRepresentationTypeNodeList->length == 0) {
                    logMessages("No spatialRepresentationType found - add a dummy one ;-)");
                    // add it after last <gmd:resourceConstraints> node
                    $resourceConstraintsNodeList = $xpath->query("//gmd:MD_Metadata/gmd:identificationInfo/*/gmd:resourceConstraints");
                    if ($resourceConstraintsNodeList->length > 0) {
                        $spatialRepresentationXMLSnippet = '<?xml version="1.0" encoding="UTF-8"?><gmd:spatialRepresentationType xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink"><gmd:MD_SpatialRepresentationTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_SpatialRepresentationTypeCode" codeListValue="grid" /></gmd:spatialRepresentationType>';
                        $spatialRepresentationDomObject = new DOMDocument();
                        $spatialRepresentationDomObject->loadXML($spatialRepresentationXMLSnippet);
                        $xpathspatialRepresentation = new DOMXpath($spatialRepresentationDomObject);
                        $spatialRepresentationNodeList = $xpathspatialRepresentation->query('/gmd:spatialRepresentationType');
                        insertAfter($metadataDomObject->importNode($spatialRepresentationNodeList->item(0), true), $resourceConstraintsNodeList->item(($resourceConstraintsNodeList->length) - 1));
                    }
                } else {
                    logMessages("spatialRepresentationType found - don't add one!");
                }
            }
        }
        // ****************************************************************************************
        
        //check for empty keyword
        //check for right date formats
        //check for empty use constraints
        //check for empty responsible party elements
        //check for wrong format description - name, version, specification
        //check ... - maybe include it into class_iso19139 - this will be easier!

		//inspire specific keywords
		//https://webgate.ec.europa.eu/fpfis/wikis/display/InspireMIG/Spatial+scope+code+list
		//problem: gmx namespace does not exists !!!!! - have to be declared before!!!!!!
		/*$keywordXMLSnippet = '<?xml version="1.0" encoding="UTF-8"?><gmd:descriptiveKeywords xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink"><gmd:MD_Keywords><gmd:keyword><gmx:Anchor xlink:href="http://inspire.ec.europa.eu/metadata-codelist/SpatialScope/national">Regional</gmx:Anchor></gmd:keyword><gmd:thesaurusName><gmd:CI_Citation><gmd:title><gmx:Anchor xlink:href="http://inspire.ec.europa.eu/metadata-codelist/SpatialScope">Spatial scope</gmx:Anchor></gmd:title><gmd:date><gmd:CI_Date><gmd:date><gco:Date>2019-05-22</gco:Date></gmd:date><gmd:dateType><gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication">publication</gmd:CI_DateTypeCode></gmd:dateType></gmd:CI_Date></gmd:date></gmd:CI_Citation></gmd:thesaurusName></gmd:MD_Keywords></gmd:descriptiveKeywords>';*/
		$descriptiveKeywordsNodeList = $xpath->query('//gmd:MD_Metadata/gmd:identificationInfo//gmd:descriptiveKeywords');
		//data and service identification!
		//$arrayDescriptiveKeywordsNodeList = (array)$descriptiveKeywordsNodeList;			
		foreach ($keywordsArray as $keyword) {
			$keywordXMLSnippet ='<?xml version="1.0" encoding="UTF-8"?><gmd:descriptiveKeywords xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink">
    <gmd:MD_Keywords>
        <gmd:keyword>
            <gco:CharacterString>'.$keyword->keyword.'</gco:CharacterString>
        </gmd:keyword>
	<gmd:thesaurusName>
	    <gmd:CI_Citation>
		<gmd:title>
		    <gco:CharacterString>'.$keyword->thesaurusTitle.'</gco:CharacterString>
		</gmd:title>
		<gmd:date>
		    <gmd:CI_Date>
			<gmd:date>
			    <gco:Date>'.$keyword->thesaurusPubDate.'</gco:Date>
			</gmd:date>
			<gmd:dateType>
			    <gmd:CI_DateTypeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication">publication</gmd:CI_DateTypeCode>
			</gmd:dateType>
		    </gmd:CI_Date>
		</gmd:date>
	    </gmd:CI_Citation>
	</gmd:thesaurusName>
    </gmd:MD_Keywords>
</gmd:descriptiveKeywords>';
			//count old resourceConstraints elements
			//TODO - if this is empty - create a new entry
			//if (!empty($arrayResourceConstraintsNodeList)) {	
//logMessages("Count of existing descriptiveKeywordsNodeList: ".count($descriptiveKeywordsNodeList));
			if (count($descriptiveKeywordsNodeList) > 0) {
				//$e = new mb_exception("list is not empty!");
				//load xml from constraint generator
				$keywordDomObject = new DOMDocument();
				$keywordDomObject->loadXML($keywordXMLSnippet);
				$xpathKeyword = new DOMXpath($keywordDomObject);
				$keywordNodeList = $xpathKeyword->query('/gmd:descriptiveKeywords');
				//insert new keyword before first old constraints node
				//for ($i = ($keywordNodeList->length)-1; $i >= 0; $i--) {
					$descriptiveKeywordsNodeList->item(0)->parentNode->insertBefore($metadataDomObject->importNode($keywordNodeList->item(0), true), $descriptiveKeywordsNodeList->item(0));
				//}
				//delete all resourceConstraints from original xml document 
				/*for ($i = 0; $i <  $resourceConstraintsNodeList->length; $i++) {
    						$temp = $resourceConstraintsNodeList->item($i); //avoid calling a function twice
    						$temp->parentNode->removeChild($temp);
				}*/			
			}//end for descriptiveKeywordsNodeList
			
		} //end - foreach keyword in array
		//if keyword was injected - the metadata dateStamp has to be altered - fictive 1 day will be added to mark the difference!
		$dateNodeList = $xpath->query('//gmd:MD_Metadata/gmd:dateStamp/gco:Date');
		if ($dateNodeList->length > 0) {
			$dateStamp = $dateNodeList->item(0)->nodeValue;
			//$date = new DateTime($dateStamp);
			$date = new DateTime('NOW');
			//add one day
			//$date->add(new DateInterval('P1D'));
			$date = new DateTime('NOW');
			$dateNew = date_format($date, 'Y-m-d');
			$fragment = $metadataDomObject->createElementNS('http://www.isotc211.org/2005/gco', 'gco:Date', $dateNew);
			$dateNodeList->item(0)->parentNode->replaceChild($fragment, $dateNodeList->item(0)); 
		} else {
			//try to find dateTime instead
			$dateTimeNodeList = $xpath->query('//gmd:MD_Metadata/gmd:dateStamp/gco:DateTime');
			$dateTimeStamp = $dateTimeNodeList->item(0)->nodeValue;
			//$date = new DateTime($dateStamp);
			//add one day
			//$date->add(new DateInterval('P1D'));
			$date = new DateTime('NOW');
			$dateTimeNew = date_format($date, 'Y-m-d\TH:i:s');
			$fragment = $metadataDomObject->createElementNS('http://www.isotc211.org/2005/gco', 'gco:DateTime', $dateTimeNew);
			$dateTimeNodeList->item(0)->parentNode->replaceChild($fragment, $dateTimeNodeList->item(0));
		}
		//if topiccategory is given in xml - try to exchange its value with the translation from translation array
		if ($topicCategoriesArray != false) {
		    $topicCategoryNodeList = $xpath->query('//gmd:MD_Metadata/gmd:identificationInfo//gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword[gmd:thesaurusName/gmd:CI_Citation/gmd:title/gco:CharacterString=\'GEMET - INSPIRE themes, version 1.0\']]/gco:CharacterString');
		    //$fragment = $metadataDomObject->createElementNS('http://www.isotc211.org/2005/gco', 'gmd:keyword', $dateTimeNew);
		
		}	
		//repair urls for transfer options
		$downloadNodeList = $xpath->query('//gmd:MD_Metadata/gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL');
		if ($downloadNodeList->length > 0) {
		    $oldUri = $downloadNodeList->item(0)->nodeValue;
		    $newUri = str_replace(PHP_EOL, null, $oldUri);
		    $newUri = str_replace(" ", "", $newUri);
		    $fragment = $metadataDomObject->createElement('gmd:URL');
		    $textNode = $metadataDomObject->createTextNode($newUri);
		    $fragment->appendChild($textNode);
		    $downloadNodeList->item(0)->parentNode->replaceChild($fragment, $downloadNodeList->item(0));
		}
	} //end for parsing xml successfully
	return $metadataDomObject->saveXML();
}


function logMessages($message) {
    if (php_sapi_name() === 'cli' OR defined('STDIN')) {
        echo __FILE__.": ".$message."\n";
    } else {
        $e = new mb_exception(__FILE__.": ".$message);
    }
}
?>
