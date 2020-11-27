<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");

class OwsMetadataUrl {
	var $urlArray;
        var $typeArray;
	var $formatArray;

//for wfs 1.x after LatLongBoundingBox
//problem: different layouts for different wfs versions!
/*<wfs:MetadataURL type="FGDC" format="text/xml">http:/... </wfs:MetadataURL>*/
//wfs 1.1.0
/*
<xsd:complexType name="MetadataURLType">
<xsd:simpleContent>
<xsd:extension base="xsd:string">
<xsd:attribute name="type" use="required">
<xsd:simpleType>
<xsd:restriction base="xsd:NMTOKEN">
<xsd:enumeration value="TC211"/>
<xsd:enumeration value="FGDC"/>
</xsd:restriction>
</xsd:simpleType>
</xsd:attribute>
<xsd:attribute name="format" use="required">
<xsd:simpleType>
<xsd:restriction base="xsd:NMTOKEN">
© OGC 2002 – All rights reserved
89<xsd:enumeration value="XML"/>
<xsd:enumeration value="SGML"/>
<xsd:enumeration value="TXT"/>
</xsd:restriction>
</xsd:simpleType>
</xsd:attribute>
</xsd:extension>
</xsd:simpleContent>
</xsd:complexType>
*/
//wfs 1.1.0
/*
<xsd:complexType name="MetadataURLType">
<xsd:simpleContent>
<xsd:extension base="xsd:string">
<xsd:attribute name="type" use="required">
<xsd:simpleType>
<xsd:restriction base="xsd:NMTOKEN">
<xsd:enumeration value="TC211"/>
<xsd:enumeration value="FGDC"/>
<xsd:enumeration value="19115"/>
<xsd:enumeration value="19139"/>
</xsd:restriction>
</xsd:simpleType>
</xsd:attribute>
<xsd:attribute name="format" use="required">
<xsd:simpleType>
<xsd:restriction base="xsd:NMTOKEN">
<xsd:enumeration value="text/xml"/>
<xsd:enumeration value="text/html"/>
<xsd:enumeration value="text/sgml"/>
<xsd:enumeration value="txt/plain"/>
</xsd:restriction>
</xsd:simpleType>
</xsd:attribute>
</xsd:extension>
</xsd:simpleContent>
</xsd:complexType>
*/
//wfs 2.0.2
/*<MetadataURL
            xlink:href="http://www.ogccatservice.com/csw.cgi?service=CSW&amp;version=2.0.0&amp;request=GetRecords&amp;constraintlanguage=CQL&amp;recordid=urn:uuid:4ee8b2d3-9409-4a1d-b26b-6782e4fa3d59"/>*/
/*
   <xsd:complexType name="FeatureTypeType">
      <xsd:sequence>
         <xsd:element name="Name" type="xsd:QName"/>
         <xsd:element ref="wfs:Title" minOccurs="0" maxOccurs="unbounded"/>
         <xsd:element ref="wfs:Abstract" minOccurs="0" maxOccurs="unbounded"/>
         <xsd:element ref="ows:Keywords" minOccurs="0" maxOccurs="unbounded"/>
         <xsd:choice>
            <xsd:sequence>
               <xsd:element name="DefaultCRS" type="xsd:anyURI"/>
               <xsd:element name="OtherCRS" type="xsd:anyURI"
                            minOccurs="0" maxOccurs="unbounded"/>
            </xsd:sequence>
            <xsd:element name="NoCRS">
               <xsd:complexType/>
            </xsd:element>
         </xsd:choice>
         <xsd:element name="OutputFormats" type="wfs:OutputFormatListType"
                      minOccurs="0"/>
         <xsd:element ref="ows:WGS84BoundingBox"
                      minOccurs="0" maxOccurs="unbounded"/>
         <xsd:element name="MetadataURL" type="wfs:MetadataURLType"
                      minOccurs="0" maxOccurs="unbounded"/>
         <xsd:element name="ExtendedDescription"
                      type="wfs:ExtendedDescriptionType" minOccurs="0"/>
      </xsd:sequence>
   </xsd:complexType>
   <xsd:complexType name="OutputFormatListType">
      <xsd:sequence maxOccurs="unbounded">
         <xsd:element name="Format" type="xsd:string"/>
      </xsd:sequence>
   </xsd:complexType>
   <xsd:complexType name="MetadataURLType">
      <xsd:attributeGroup ref="xlink:simpleLink"/>
      <xsd:attribute name="about" type="xsd:anyURI"/>
   </xsd:complexType>
*/

	public function __construct () {
		
	}

	public function getOwsRepresentation($urlArray, $typeArray, $formatArray, $service = 'wfs', $version = '1.1.0') {
		$this->urlArray = $urlArray;
		$this->typeArray = $typeArray;
		$this->formatArray = $formatArray;
		//load xml snippet from filesystem as template
		$metadataUrlDomObject = new DOMDocument();
		switch ($service) {
			case 'wfs':
				switch ($version) {
					case "1.0.0":
					case "1.1.0":
						$template = "mb_ows_wfs_1.x_metadataurl.xml";
						break;
					case "2.0.0":
					case "2.0.2":
						$template = "mb_ows_wfs_2.x_metadataurl.xml";
						break;
				}
				break;
			case 'wms':
				$template = "mb_ows_metadataurl.xml";
				break;
		}
		//$metadataUrlDomObject->load(dirname(__FILE__) . "/../geoportal/metadata_templates/mb_ows_metadataurl.xml");
		$metadataUrlDomObject->load(dirname(__FILE__) . "/../geoportal/metadata_templates/".$template);
		$xpathMetadataUrl = new DOMXpath($metadataUrlDomObject);
		//$reportNodeList = $xpathLicense->query('/mb:dataqualityreport/gmd:report');
		$xpathMetadataUrl->registerNamespace("mb", "http://www.mapbender.org/ows/metadataurl");
		$xpathMetadataUrl->registerNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
		$xpathMetadataUrl->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
		//clone report node and get parent
		$MetadataUrl = $xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0);
		$parent = $MetadataUrl->parentNode;
		//add one entry foreach found element
		$i = 0;
		foreach ($this->urlArray as $url) {
			//$e = new mb_exception($url);
			//TODO: For wms:
			//$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("type", $this->typeArray[$i]);
			//$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL/Format')->item(0)->nodeValue = $this->formatArray[$i];
			//$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL/OnlineResource')->item(0)->setAttribute("xlink:href", $url);
			//For wfs 2.x
			switch ($service) {
				case 'wfs':
					switch ($version) {
						case "1.1.0":
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("type", $this->typeArray[$i]);
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("format", $this->formatArray[$i]);
							$textNode = $metadataUrlDomObject->createTextNode($this->urlArray[$i]);
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->nodeValue = "";
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->appendChild($textNode);


//$e = new mb_exception("class_owsMetadataUrl: 1.1.0: value: ".$this->urlArray[$i]);

							break;
						case "1.0.0":
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("type", $this->typeArray[$i]);
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("format", $this->formatArray[$i]);
							$textNode = $metadataUrlDomObject->createTextNode($this->urlArray[$i]);
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->nodeValue = "";
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->appendChild($textNode);
							break;
						case "2.0.0":
						case "2.0.2":
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");
							$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("xlink:href", $this->urlArray[$i]);
							break;
					}
					break;
				case 'wms':
					$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL')->item(0)->setAttribute("type", $this->typeArray[$i]);
					$textNode = $metadataUrlDomObject->createTextNode($this->formatArray[$i]);
					$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL/Format')->item(0)->nodeValue = "";
					$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL/Format')->item(0)->nodeValue->appendChild($textNode);

					$xpathMetadataUrl->query('/mb:metadataurl/MetadataURL/OnlineResource')->item(0)->setAttribute("xlink:href", $this->urlArray[$i]);
					break;
			}
			//clone node and add if afterwards
			$MetadataUrlNew = $MetadataUrl->cloneNode(true);
			$parent->appendChild($MetadataUrlNew);
			$i++;
		}
		//delete first (template) entry
		$parent->removeChild($MetadataUrl);
		$XML = $metadataUrlDomObject->saveXML();
	 	return $XML;
	}
}

?>
