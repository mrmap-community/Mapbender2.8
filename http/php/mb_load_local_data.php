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

$user_id = Mapbender::session()->get("mb_user_id");

$wmc = new wmc();

$wmc_serial_id = $_POST["id"];

$form_target = $self;

$sql = 'select wmc from mb_user_wmc where wmc_serial_id = $1 and wmc_has_local_data = 1 and (fkey_user_id = $2 or wmc_local_data_public = 1);';

$v = array($wmc_serial_id, $user_id);
$t = array("i", "i");
$res = db_prep_query($sql, $v, $t);

header("Content-Type: application/json");

if ($row = db_fetch_array($res)) {
    $wmc->createFromXml($row['wmc']);
    $kmls = $wmc->generalExtensionArray['KMLS'];
    echo($kmls);
} else {
    echo('{}');
}
