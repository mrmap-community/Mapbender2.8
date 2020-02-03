<?php
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
require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../php/mb_listGUIs.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<!-- 
Licensing: See the GNU General Public License for more details.
http://www.gnu.org/copyleft/gpl.html
or:
mapbender/licence/ 
-->
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">
<META http-equiv="Content-Style-Type" content="text/css">
<META http-equiv="Content-Script-Type" content="text/javascript">
<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset='.CHARSET.'">';	
?>
<title>Home</title>
<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/js/jquery-ui-1.8.1.custom.min.js"></script>
<script type="text/javascript" src="../extensions/jquery-ui-1.8.1.custom/development-bundle/ui/jquery.ui.tabs.js"></script>
<link rel="stylesheet" type="text/css" href="../extensions/jquery-ui-1.8.1.custom/development-bundle/themes/base/jquery.ui.all.css" />
<link rel="stylesheet" type="text/css" href="../extensions/jquery-ui-1.8.1.custom/development-bundle/themes/base/jquery.ui.tabs.css" />
<?php
$css_folder = "";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../css/" . $css_folder . "login.css\">";
echo "<link rel=\"shortcut icon\" href=\"../img/favicon.ico\">";
echo "<script type='text/javascript'>";
echo "<!--". chr(13).chr(10);
echo "function setFocus(){";
   echo "if(document.loginForm){";
      echo "document.loginForm.name.focus();";
   echo "}";
echo "}";
echo "// -->". chr(13).chr(10);
echo "</script>";
?>
<script type='text/javascript'>
$(document).ready(function () {
	$(function() {
		$("#guiListTabs").tabs({
			event: 'click'
		});
		
	});
});
</script>
<?php
echo "</head>";
echo "<body onload='setFocus()'>";
//get array of available guis
require_once(dirname(__FILE__)."/../php/mb_getGUIs.php");
$arrayGUIs = mb_getGUIs(Mapbender::session()->get("mb_user_id"));
mb_listGUIs($arrayGUIs);
?>
</body>
</html>

