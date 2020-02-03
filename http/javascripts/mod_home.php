<?php
# $Id: mod_home.php 10204 2019-08-09 08:30:03Z armin11 $
# http://www.mapbender.org/index.php/mod_home
# Copyright (C) 2002 CCGIS
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
?>
function mod_home_init(){
<?php
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
echo "var url = '".str_replace('login.php', 'home.php', LOGIN)."';";
//echo "var name = '".urlencode(Mapbender::session()->get("mb_user_name"))."';";
//echo "var pw = '".Mapbender::session()->get("mb_user_password")."';";
?>	
	document.location.href = url;	
}
