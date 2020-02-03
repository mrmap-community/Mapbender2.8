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

$ajaxResponse = new AjaxResponse($_POST);
if($ajaxResponse->getMethod() != "getheigth") {
	$ajaxResponse->setSuccess(false);
	$ajaxResponse->setMessage("method invalid");
	$ajaxResponse->send();
	exit;
}

$json = new Mapbender_JSON();
$xyz = $ajaxResponse->getParameter('stringxyz');
$url = "http://localhost/mapbender/plugins/dgm.php";
$e = new connector();
$e->set("httpType","post");
$e->set("httpPostFieldsNumber",1);
$e->set("curlSendCustomHeaders",false);
$e->set("httpPostData","xyz=".urlencode($xyz));
$result = $e->load($url);
$ajaxResponse->setMessage($result);
$ajaxResponse->setSuccess(true);
$ajaxResponse->send();
?>