<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once dirname(__FILE__)."/class_XpathWalker.php";

class XmlBuilder
{
    const BR_ST  = "[";
    const BR_END = "]";
    const DP     = ":";
    const DP_2   = "::";
    const AT     = "@";
    const SLASH  = '/';
    const TEXT   = 'text()';

    protected $doc;
    protected $xpath;
    protected $namespaces;

    public function __construct(DOMDocument $doc, $namespaces = array())
    {
        $this->doc        = $doc;
        $this->xpath      = new DOMXpath($this->doc);
        $this->xpath->registerNamespace("xs", "http://www.w3.org/2001/XMLSchema");
        $this->namespaces = array("xs" => "http://www.w3.org/2001/XMLSchema");
        $namespaceList    = $this->xpath->query("//namespace::*");
        foreach ($namespaceList as $namespaceNode) {
            $namespaces[$namespaceNode->localName] = $namespaceNode->nodeValue;
        }
        foreach ($namespaces as $prefix => $uri) {
            $this->namespaces[$prefix] = $uri;
            $this->xpath->registerNamespace($prefix, $uri);
        }
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function addValue(DOMNode $context, $xpathStr, $value)
    {
        $walker = new XpathWalker($xpathStr);
        // find a first existing node
        $node   = $this->findNode($context, $walker);
        if ($node === null) {
            return false;
        } elseif ($walker->isLastNode()) {
            return $this->setValue($node, $value);
        }
        $node = $this->createNode($context, $walker, $node, $value);
        // $walker->isLastNode() -> is only a last node
        if (!$walker->isAdded()) {
            return $this->setValue($node, $value);
        } else {
            return $walker->isAdded();
        }
    }

    private function findNode(DOMNode $context, XpathWalker $walker)
    {
        $node = null;
        while (!($node = $this->xpath->query($walker->getCurrent(), $context)->item(0))) {
            if (!$walker->toRoot()) {
                break;
            }
        }
        return $node;
    }

    private function createNode(DOMNode $context, XpathWalker $walker, DOMNode $node, $value)
    {
        while (!$walker->isLastNode()) {
            $this->addNode($context, $node, $walker, $value);
            $node = $this->xpath->query($walker->getCurrent(), $context)->item(0);
        }
        return $node;
    }

    private function setValue(DOMNode $node, $value)
    {
        if ($node->nodeType == XML_ATTRIBUTE_NODE) {
            $node->value = $this->getString($value); // TODO validate $value: is $value is not null...
        } else if ($node->nodeType == XML_TEXT_NODE) {
            $node->parentNode->nodeValue = $this->getString($value);
        } else if ($node->nodeType == XML_ELEMENT_NODE) {
            if (is_string($value)) {
                $node->nodeValue = $this->getString($value);
            } elseif ($value instanceof DOMAttr) {
                $node->setAttributeNode($this->doc->importNode($value, true));
            } elseif ($value instanceof DOMElement) {
                $node->appendChild($this->doc->importNode($value, true));
            } elseif ($value instanceof DOMNodeList) {
                foreach ($value as $element) {
                    if ($element instanceof DOMElement) {
                        $this->setValue($node, $element);
                    }
                }
            } else {
                throw new Exception('A value type is not implemented yet');
            }
        } else if ($node->nodeType == XML_CDATA_SECTION_NODE) {
            $node->parentNode->nodeValue = $value;
        } else {
            return false;
        }
        return true;
    }

    private function addNode(DOMNode $context, DOMElement $node, XpathWalker $walker, $value)
    {
        $nextChunk = $walker->getNext();
        if ($nextChunk === self::TEXT) { // text(), only last chunk
            $node->nodeValue = $this->getString($value);
            $walker->setAdded()->fromRoot();
        } elseif (strpos($nextChunk, self::AT) === 0) { // @, only last chunk
            $this->setAttribute($node, $walker, $nextChunk, $value);
            $walker->setAdded()->fromRoot();
        } elseif (strpos($nextChunk, self::BR_ST) !== false) { // [num] or [expression], break by expression
            $help = explode(self::BR_ST, $nextChunk);
            $int  = substr($help[1], 0, strpos($help[1], self::BR_END));
            if (ctype_digit($int)) {
                $num   = intval($int);
                $xpath = $walker->getCurrent();
                $i     = $num;
                for (; $i > 0; $i--) {
                    if ($this->xpath->query($xpath . self::BR_ST . $i . self::BR_END, $context)->item(0)) {
                        break;
                    }
                }
                for (; $i < $num + 1; $i++) {
                    $this->addElement($node, $help[0]);
                }
                $walker->fromRoot();
            }
        } elseif (strpos($nextChunk, self::DP_2) !== false) { // ::
            throw new Exception('A "next" "::" is not implemented yet');
        } elseif (strpos($nextChunk, self::DP) !== false) { // :, element
            $this->addElement($node, $nextChunk);
            $walker->fromRoot();
        } else {
            throw new Exception('A "next" type is not implemented yet');
        }
    }

    private function addElement(DOMElement $node, $xpathChunk)//, $value)
    {
        $help = explode(self::DP, $xpathChunk);
        $node->appendChild(new DOMElement($xpathChunk, '', $this->namespaces[$help[0]]));
    }

    private function setAttribute(DOMElement $node, XpathWalker $walker, $xpathChunk, $value)
    {
        $qualified = substr($xpathChunk, 1);
        $help      = explode(self::DP, $qualified);
        if (count($help) === 2) { // with prefix
            $node->setAttributeNS($this->namespaces[$help[0]], $qualified, $this->getString($value));
        } else {
            $node->setAttribute($qualified, $this->getString($value));
        }
    }

    private function getString($value)
    {
        return htmlspecialchars($value ? $value : '');
    }
}