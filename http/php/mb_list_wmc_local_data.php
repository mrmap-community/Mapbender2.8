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

//parse ajax params
//activateRegistratingGroupFilter
$activateRegistratingGroupFilter = $_REQUEST["activateRegistratingGroupFilter"];

$user_id = Mapbender::session()->get("mb_user_id");
//select mb_user_id from mb_user_mb_group inner join mb_user on mb_user_mb_group.fkey_mb_user_id = mb_user.mb_user_id where mb_user_mb_group.fkey_mb_group_id = 36;
if (defined("REGISTRATING_GROUP") && is_int(REGISTRATING_GROUP)) {
	$registratingGroupId = 	REGISTRATING_GROUP;
} else {
	$activateRegistratingGroupFilter = "0";
}

if ($activateRegistratingGroupFilter == "1") {
	$sql = 'select a.wmc_serial_id, a.wmc_local_data_public, a.wmc_title, a.wmc_timestamp, a.wmc_local_data_size,'.
       		' a.fkey_user_id, termsofuse.symbollink from mb_user_wmc a left join termsofuse on termsofuse.termsofuse_id'.
       		' = a.wmc_local_data_fkey_termsofuse_id where wmc_local_data_size::int > 2 and'.
       		'(fkey_user_id = $1 or (wmc_local_data_public = 1 AND fkey_user_id in ('.
		'select mb_user_id from mb_user_mb_group inner join mb_user on mb_user_mb_group.fkey_mb_user_id = mb_user.mb_user_id where mb_user_mb_group.fkey_mb_group_id = '.$registratingGroupId.
		')));';	
} else {
	$sql = 'select a.wmc_serial_id, a.wmc_local_data_public, a.wmc_title, a.wmc_timestamp, a.wmc_local_data_size,'.
       		' a.fkey_user_id, termsofuse.symbollink from mb_user_wmc a left join termsofuse on termsofuse.termsofuse_id'.
       		' = a.wmc_local_data_fkey_termsofuse_id where wmc_local_data_size::int > 2 and'.
       		'(fkey_user_id = $1 or wmc_local_data_public = 1);';
}

$v = array($user_id);
$t = array("i");
$res = db_prep_query($sql, $v, $t);
$wmcs = array();
while ($row = db_fetch_array($res)) {
    $wmc = array();
    $wmc[] = $row['wmc_serial_id'];
    $wmc[] = $row['wmc_title'];
    $wmc[] = $row['wmc_timestamp'];
    $wmc[] = $row['symbollink'];
    $wmc[] = $row['wmc_local_data_public'] == 1;
    $wmc[] = $row['wmc_local_data_size'];
    $wmc[] = $row['fkey_user_id'];
    $wmcs[] = $wmc;
}

header("Content-Type: application/json");

echo(json_encode($wmcs));
