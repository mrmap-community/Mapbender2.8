<?php
# $Id: GraphicStroke.php 9453 2016-05-11 13:52:38Z pschmidt $
# http://www.mapbender.org/index.php/SLD
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

/**
 * Implementation of the GraphicStroke-element
 *
 * @package sld_classes
 * @author Markus Krzyzanowski
 */
class GraphicStroke
{
	/**
	 * The Graphic object from the xml-scheme.
	 *
	 * @see Graphic
	 * @var object
	 */
	var $graphic = "";
	
	/**
	 * Index identifying the object in the $_SESSION("sld_objects") array.
	 * @var int
	 */
	var $id = "";
	
	/**
	 * Index identifying the object's parent object in the $_SESSION("sld_objects") array.
	 * @var int
	 */
	var $parent = "";
	
	/**
	 * Default constructor that automatically creates a Graphic object.
	 */
	function GraphicStroke()
	{
		$this->graphic = new Graphic();
	}
	
	/**
	 * creates the xml for this object and its child objects
	 *
	 * @param string $offset string used for formatting the output
	 * @return string containing the xml-fragment
	 */
	function generateXml($offset = "")
	{
		$temp = $offset."<GraphicStroke>\n";
		if ($this->graphic != "") $temp .= $this->graphic->generateXml($offset." ");
		$temp .= $offset."</GraphicStroke>\n";
		return $temp;
	}
	
	/**
	 * creates the html-form-fragment for this object
	 *
	 * @param $id string containing a prefix that should be used to identify this
	 * object's html fields. This must be done, so that the generateObjectFromPost(...)
	 * function can address the fields belonging to this object in the http-post.
	 *
	 * @param string $offset string used for formatting the output
	 * @return string containing the html-form-fragment
	 */
	function generateHtmlForm($id, $offset = "")
	{
		$temp = "";
		$temp .= $offset."<table style=\"border: 1px solid black;\">\n";
		$temp .= $offset." <tr valign=\"top\">\n";
		$temp .= $offset."  <td>\n";
		$temp .= $offset."   GraphicStroke\n";
		$temp .= $offset."   <input type=\"hidden\" name=\"".$id."\" value=\"graphicstroke\">\n";
		$temp .= $offset."  </td>\n";
		$temp .= $offset."  <td>\n";
		
		if ($this->graphic != "") $temp .= $this->graphic->generateHtmlForm($id."_graphic", $offset."   ");
		
		$temp .= $offset."  </td>\n";
		$temp .= $offset." </tr>\n";
		$temp .= $offset."</table>\n";
		return $temp;
	}
	
	/**
	 * populates the member fields of a new object from the data in the http-post-request
	 * to rebuild the object after the submission of the html-form.
	 *
	 * creates its own child objects from the post parameters and calls their
	 * generateObjectFromPost(...) function
	 *
	 * @param string $id string that contains a prefix for the html-form-fields
	 * that is common to all of the fields belonging to this object
	 */
	function generateObjectFromPost($id = "")
	{
		if (isset($_REQUEST[$id."_graphic"]))
		{
			$this->graphic = new Graphic();
			$this->graphic->generateObjectFromPost($id."_graphic");
		}
	}
}
?>