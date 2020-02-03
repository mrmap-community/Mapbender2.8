<?php
# $Id: mod_createJSObjFromXML.php 9520 2016-06-10 10:30:18Z pschmidt $
# http://www.mapbender.org/index.php/mod_createJSObjFromXML.php
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

require_once(dirname(__FILE__) . "/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_wms.php");
require_once(dirname(__FILE__) . "/../classes/class_administration.php");
#require_once(dirname(__FILE__)."/../classes/class_mb_exception.php");

$capabilitiesURL = $_REQUEST['caps'];
$noHtml = intval($_GET["noHtml"]);
$output = "";
$charset = CHARSET;
$mywms = new wms();
$caps = $capabilitiesURL;
$caps = html_entity_decode($_REQUEST['caps']);
$result = $mywms->createObjFromXML($caps);

if (!$result['success']) {

    $output .= "try {" .
        "Mapbender.Modules.dialogManager.openDialog({" .
        "content: '" . $result['message'] . "<br><br><b>" . $capabilitiesURL .
        "', modal: false, effectShow: 'puff'});" .
        "} catch (e) {" .
        "var errorcall = '" . json_encode($result['massage']) . "|" . $capabilitiesURL . "';" .
        "}";
}

else {
    // Setzt das geledene WMS in Sichtbar
    $mywms->gui_wms_visible = 1;
    for($i=0; $i<count($mywms->objLayer); $i++){
        $mywms->objLayer[$i]->gui_layer_visible = 1;
    }
    if ($noHtml) {
        $output .=  $mywms->createJsObjFromWMS_(false);
    } else {
        $output .= $mywms->createJsObjFromWMS_(true);
    }
}
$js = administration::convertOutgoingString($output);
unset($output);

if ($noHtml) {

	echo $js;
}
