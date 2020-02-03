<?php
# $Id: geom2wfst.php 9774 2017-08-25 09:01:58Z armin11 $
# http://www.mapbender.org/index.php/geom2wfst.php
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

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__)."/../classes/class_mb_exception.php");
require_once(dirname(__FILE__)."/../classes/class_json.php");
require_once(dirname(__FILE__)."/../classes/class_universal_wfs_factory.php");
require_once(dirname(__FILE__)."/../classes/class_universal_gml_factory.php");
require_once(dirname(__FILE__)."/../classes/class_wfs_configuration.php");

$wfs_conf_id = $_POST["wfs_conf_id"];
$method = $_POST["method"];
$geoJson = $_POST["geoJson"];

function sendErrorMessage($data) {
	$resObj = array();
	$response = "error";
	$resObj["errorMessage"] = $data;
	$resObj["response"] = $response;
	
	header("Content-Type:application/x-json");
	$json = new Mapbender_JSON();
	echo $json->encode($resObj);
	die;
}


$wfsConf = WfsConfiguration::createFromDb($wfs_conf_id);
if (is_null($wfsConf)) {
	sendErrorMessage("Invalid WFS conf: " . $wfs_conf_id);
}

$myWfsFactory = new UniversalWfsFactory();
//don't return proxy urls cause the request should go directly to the server 
$myWfsFactory->returnProxyUrls = false;
$myWfs = $myWfsFactory->createFromDb($wfsConf->wfsId);

if (is_null($myWfs)) {
	sendErrorMessage("Invalid WFS: " . $wfsConf->wfsId);
}

$data = $myWfs->transaction($method, $wfsConf, $geoJson);

if (is_null($data)) {
	sendErrorMessage("WFS didn't return any data.");
}

$resObj = $myWfs->parseTransactionResponse($data);

header("Content-Type:application/x-json");
$json = new Mapbender_JSON();
echo $json->encode($resObj);
?>
