<?php
# $Id: Stroke.php 9453 2016-05-11 13:52:38Z pschmidt $
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
 * Implementation of the Stroke-element
 *
 * @package sld_classes
 * @author Markus Krzyzanowski
 */
class Stroke
{
	//choice
	//GraphicFill and GraphicStroke are not supported by Mapserver
	//var $graphicfill = "";
	//var $graphicstroke = "";
	//end choice
	
	/**
	 * Array containing the CssParameter objects from the xml-scheme.
	 *
	 * @see CssParameter
	 * @var array
	 */
	var $cssparameters = array();
	
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
	 * creates the xml for this object and its child objects
	 *
	 * @param string $offset string used for formatting the output
	 * @return string containing the xml-fragment
	 */
	function generateXml($offset = "")
	{
		$temp = $offset."<Stroke>\n";
		//if ($this->graphicfill != "") $temp .= $this->graphicfill->generateXml($offset." ");
		//if ($this->graphicstroke != "") $temp .= $this->graphicstroke->generateXml($offset." ");
		foreach ($this->cssparameters as $cssparameter)
		{
			$temp .= $cssparameter->generateXml($offset." ");
		}
		$temp .= $offset."</Stroke>\n";
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
		$temp = $offset."<hr class=\"sep\">\n";
		$temp .= $offset."<table>\n";
		$temp .= $offset." <tr valign=\"top\">\n";
		$temp .= $offset."  <td style=\"width: 100px;\">\n";
		$temp .= $offset."   Stroke<br>\n";
		$temp .= $offset."   <input type=\"hidden\" name=\"".$id."\" value=\"stroke\">\n";
		$temp .= $offset."   <a class='edit' href=\"sld_function_handler.php?function=deletestroke&id=".$this->parent."\">l&ouml;schen</a>\n";
		$temp .= $offset."  </td>\n";
		$temp .= $offset."  <td valign=\"top\">\n";

//		if ($this->graphicfill == "" && $this->graphicstroke == "")
//		{
//			//Add new Graphicfill or Graphicstroke TODO!!!
//			$temp .= $offset."<select name=\"".$id."_newgraphicfillorgraphicstroke\">\n";
//			$temp .= $offset."<option value=\"graphicfill\">GraphicFill</option>\n";
//			$temp .= $offset."<option value=\"graphicstroke\">GraphicStroke</option>\n";
//			$temp .= $offset."</select>\n";
//			$temp .= $offset."<input type=\"button\" value=\"GraphicFill oder GraphicStroke hinzuf&uuml;gen\"";
//			$temp .= $offset." onClick=\"url='sld_function_handler.php?function=addgraphicfillorgraphicstroke&id=".$id."&graphicfillorgraphicstroke=';";
//			$temp .= $offset." url += ".$id."_newgraphicfillorgraphicstroke.value;";
//			$temp .= $offset." location.href = url;\"";
//			$temp .= $offset."><br>\n";
//		}
//		else if ($this->graphicfill != "")
//		{
//			$this->graphicfill->generateHtmlForm($id."_graphicfill");
//		}
//		else if ($this->graphicstroke != "")
//		{
//			$this->graphicstroke->generateHtmlForm($id."_graphicstroke");
//		}
		

		$cssparameter_id = 0;
		foreach ($this->cssparameters as $cssparameter)
		{
			$temp .= $cssparameter->generateHtmlForm($id."_cssparameter_".$cssparameter_id, $offset."      ");
			$cssparameter_id++;
		}

		
		$temp .= $offset."      <select name=\"".$id."_newcssparameter\">\n";
		//Some of the options are not supported by Mapserver
		$temp .= $offset."       <option value=\"stroke\">stroke</option>\n";
		$temp .= $offset."       <option value=\"stroke-width\">stroke-width</option>\n";
		$temp .= $offset."       <option value=\"stroke-dasharray\">stroke-dasharray</option>\n";
		$temp .= $offset."      </select>\n";

		
		$temp .= $offset."      <input type=\"button\" value=\"hinzuf&uuml;gen\"";
		//Javascript to make a http request
		$temp .= " onClick=\"url='sld_function_handler.php?function=addcssparameter&id=".$this->id."&cssparameter=';";
		$temp .= " url += ".$id."_newcssparameter.value;";
		$temp .= " location.href = url;\"";
		$temp .= ">\n";



		
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
//		if (isset($_REQUEST[$id."_graphicfill"]))
//		{
//			$this->graphicfill = new GraphicFill();
//			$this->graphicfill->generateObjectFromPost($id."_graphicfill");
//		}
//		if (isset($_REQUEST[$id."_graphicstroke"]))
//		{
//			$this->graphicstroke = new GraphicStroke();
//			$this->graphicstroke->generateObjectFromPost($id."_graphicstroke");
//		}
		$countCssParameters = 0;
		while (isset($_REQUEST[$id."_cssparameter_".$countCssParameters]))
		{
			$cssParameter = new CssParameter();
			$cssParameter->generateObjectFromPost($id."_cssparameter_".$countCssParameters);
			$this->cssparameters[] = $cssParameter;
			$countCssParameters++;
		}
	}
	
	/**
	 * Adds a new CssParameter to the array.
	 * @param string $cssParameter name attribute of the CssParameter that has to be created.
	 */
	function addCssParameter($cssParameter)
	{
		$newCssParameter = new CssParameter();
		$newCssParameter->name = $cssParameter;
		$this->cssparameters[] = $newCssParameter;
	}
	
	/**
	 * Deletes a CssParameter from the array at the given index.
	 * @param int $index index of the CssParameter that has to be deleted.
	 */
	function deleteCssParameter($index)
	{
		array_splice($this->cssparameters, $index, 1);
	}
}
?>