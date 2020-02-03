<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

class XpathWalker
{
    protected $xpath;
    protected $pointer;
    protected $created;

    public function __construct($xpath)
    {
        $this->xpath   = explode(XmlBuilder::SLASH, $xpath);
        $this->pointer = count($this->xpath) - 1;
        $this->added   = false;
    }

    public function getCurrent()
    {
        return implode(XmlBuilder::SLASH, array_slice($this->xpath, 0, $this->pointer + 1));
    }

    public function toRoot()
    {
        if ($this->pointer === 0) {
            return false;
        } else {
            $this->pointer--;
            return true;
        }
    }

    public function fromRoot()
    {
        if ($this->pointer === count($this->xpath) - 1) {
            return false;
        } else {
            $this->pointer++;
            return true;
        }
    }

    public function isLastNode()
    {
        return count($this->xpath) - 1 === $this->pointer;
    }

    public function getNext()
    {
        return !$this->isLastNode() ? $this->xpath[$this->pointer + 1] : null;
    }

    public function isNextLast()
    {
        return count($this->xpath) - 1 === $this->pointer + 1;
    }

    public function setAdded()
    {
        $this->added = true;
        return $this;
    }

    public function isAdded()
    {
        $this->added;
    }
}