<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/class_iso19139.php");

class QualityReport {
	var $metadataType;
	var $inspireInteroperability;

	public function __construct () {
		$this->metadataType = 'dataset';
		$this->inspireInteroperability = 'f';
	}

	public function getIso19139Representation($metadataType, $inspireInteroperability, $legislation_group = false) {
		$iso19139 = new iso19139();
		//actualize relevant legislation!!!!! after initializing they have been set to dataset!!!!!
		$iso19139->hierarchyLevel = $metadataType;
                $iso19139->inspireRegulations = $iso19139->getRelevantInspireRegulations();
		$regulations = $iso19139->inspireRegulations;
		//load xml snippet from filesystem as template
		$reportDomObject = new DOMDocument();
		$reportDomObject->load(dirname(__FILE__) . "/../geoportal/metadata_templates/mb_dataqualityreport.xml");
		$xpathReport = new DOMXpath($reportDomObject);
		//$reportNodeList = $xpathLicense->query('/mb:dataqualityreport/gmd:report');
		$xpathReport->registerNamespace("mb", "http://www.mapbender.org/metadata/dataqualityreport");
		$xpathReport->registerNamespace("gco", "http://www.isotc211.org/2005/gco");
		$xpathReport->registerNamespace("gmd", "http://www.isotc211.org/2005/gmd");
		$xpathReport->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
		//clone report node and get parent
		$report = $xpathReport->query('/mb:dataqualityreport/gmd:report')->item(0);
		$parent = $report->parentNode;
		//check inspire_interoperability
		switch ($inspireInteroperability) {
			case "t":
				//create one part for each regulation with pass to true
				foreach ($regulations as $regulation) {
					$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:title/gco:CharacterString')->item(0)->nodeValue = $regulation['name'];
					$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date')->item(0)->nodeValue = $regulation['date']->format('Y-m-d');
					$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:pass/gco:Boolean')->item(0)->nodeValue = "true";
					//clone node and add if afterwards
					$reportNew = $report->cloneNode(true);
					$parent->appendChild($reportNew);
				}
				//delete first (template) entry
				$parent->removeChild($report);
				break;
			default:
				foreach ($regulations as $regulation) {
					$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:title/gco:CharacterString')->item(0)->nodeValue = $regulation['name'];
					$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:specification/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date/gco:Date')->item(0)->nodeValue = $regulation['date']->format('Y-m-d');
					if ($regulation['type'] == 'metadata') {
						$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:pass/gco:Boolean')->item(0)->nodeValue = "true";
					} else {
						$xpathReport->query('/mb:dataqualityreport/gmd:report/gmd:DQ_DomainConsistency/gmd:result/gmd:DQ_ConformanceResult/gmd:pass/gco:Boolean')->item(0)->nodeValue = "false";
					}
					//clone node and add if afterwards
					$reportNew = $report->cloneNode(true);
					$parent->appendChild($reportNew);
				}
				//delete first (template) entry
				$parent->removeChild($report);
				break;
		}	
		$XML = $reportDomObject->saveXML();
	 	return $XML;
	}
}

?>
