<?php
# $Id: mb_listWMCs.php 1686 2007-09-26 09:05:01Z christoph $
# http://www.mapbender.org/index.php/mb_listWMCs.php
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
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
require_once(dirname(__FILE__) . "/../classes/class_wmc.php");

// user id in session
$user_id = Mapbender::session()->get("mb_user_id");
// create wmc object
$wmc = new wmc();
// get post parameter
$serial_id;
$wmc_serial_id = $_POST["id"];
// sql statement to get the wmc object and the serial_id
$sql = 'Select wmc from mb_user_wmc where wmc_serial_id = $1 and wmc_has_local_data = 1 and fkey_user_id = $2;';
$v = array($wmc_serial_id, $user_id);
$t = array("c", "i");
$res = db_prep_query($sql, $v, $t);
// fetch result
if ($row = db_fetch_array($res)) {
    $wmc->createFromXml($row['wmc']);
    $wmc->generalExtensionArray['KMLS'] = null;
    $wmc->generalExtensionArray['KMLORDER'] = null;
    $wmc->wmc_serial_id = $wmc_serial_id;
    $wmc->has_local_data = 0;
    $wmc->local_data_public = 0;
    $wmc->local_data_size = '0';
    $newWmcXml = $wmc->toXml();

    if (is_int(intval($wmc->wmc_serial_id))) {
        $wmc->update_existing($newWmcXml, $serial_id);
        // send sql to update the local-data flag
        $sql =  'UPDATE mb_user_wmc mb  SET wmc_has_local_data = 0 WHERE wmc_serial_id = $1';
        $v = array($wmc_serial_id);
        $t = array("s");

        $res = db_prep_query($sql, $v, $t);
        echo('{"success":true}');
    }
} else {
    echo('{"success":false}');
}
