<?php
# $Id: mod_editSelf.php 9944 2018-08-10 12:30:18Z armin11 $
# http://www.mapbender.org/index.php/mod_editSelf.php
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

$e_id="editSelf";
require_once(dirname(__FILE__)."/../php/mb_validatePermission.php");

/*  
 * @security_patch irv done
 */ 
//security_patch_log(__FILE__,__LINE__);
$postvars = explode(",", "selected_user,name,firstname,lastname,academic_title,password,password_plain,v_password,description,email,phone,facsimile,street,housenumber,delivery_point,postal_code,city,organization,department,position,country,owner_name,owner_id,login_count,resolution,action");
foreach ($postvars as $value) {
   ${$value} = $_POST[$value];
}

require_once(dirname(__FILE__)."/../classes/class_user.php");
$editSelf = true;
$selected_user = Mapbender::session()->get("mb_user_id");

include "../../lib/editUser.php";
?>
