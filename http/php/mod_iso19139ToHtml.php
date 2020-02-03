<?php
//http://localhost/mapbender_trunk/php/mod_iso19139ToHtml.php?url=http%3A%2F%2Fwww.geoportal.rlp.de%2Fmetadata%2Fdtk5.xml
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_iso19139.php";
//show html from a given url
$url = urldecode($_REQUEST['url']);
$mbMetadata = new Iso19139();
$mbMetadata->readFromUrl($url);
$html = $mbMetadata->transformToHtml('tabs','de');
header("Content-type: text/html; charset=UTF-8");
echo $html;
?>
