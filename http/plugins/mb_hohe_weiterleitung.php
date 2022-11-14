<?php
/*
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_connector.php");

if($_POST['action'] = 'getheigth')
{
$xyz = $_POST['stringxyz'];
$url = "http://localhost/mapbender/plugins/dtm.php";
$e = new connector();
$e->set("httpType","post");
$e->set("httpPostFieldsNumber",1);
$e->set("curlSendCustomHeaders",false);
$e->set("httpPostData","xyz=".urlencode($xyz));
$result = $e->load($url);	
echo $result;	
}
?>