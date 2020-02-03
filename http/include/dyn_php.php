<?php
# $Id: dyn_php.php 9944 2018-08-10 12:30:18Z armin11 $
# $Header: /cvsroot/mapbender/mapbender/http/classes/class_wfs.php,v 1.15 2006/03/09 13:55:46 uli_rothstein Exp $
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

if(isset($gui_id))
{
	$sql = "SELECT * FROM gui_element_vars WHERE fkey_gui_id = $1 and fkey_e_id = $2 and var_type='php_var' ORDER BY var_name";
	$v = array($gui_id, $e_id);
	$t = array('s', 's');
   	$res = db_prep_query($sql,$v,$t);

        // there used to be a echo "\n"; here, but that doesn't make any common sense (problem with return value of printFactory.php)
	//echo "\n";
	while($row = db_fetch_array($res))
	{
		//$e = new mb_exception("found element var: ".$row["var_name"]." - ".$row["var_value"]);
		if (preg_match("/\w+\[\d+\]/", $row["var_name"])) {
			$varname = mb_substr($row["var_name"], 0, mb_strpos($row["var_name"], "["));	
			array_push(${$varname}, stripslashes($row["var_value"])); //php7!!!
		}
		else {
			${$row["var_name"]} = stripslashes($row["var_value"]);
		}
	}
}
?>
