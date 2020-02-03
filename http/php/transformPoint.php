<?php
# http://www.mapbender.org/index.php/Administration
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

header("Content-Type: application/json");

$point_pos = $_POST["point_pos"];
$targetProj = json_decode($_POST["targetProj"], true);
$currentProj = json_decode($_POST["currentProj"], true);

// Return the multipoints and use it on the client-side
try {
    $multipoint = "";
    $points_obj = json_decode($point_pos, true);
    $last_item = end($points_obj);
    foreach ($points_obj as $key => $value) {
        $multipoint .= '(';
        $multipoint .= $value["pos"]["x"];
        $multipoint .= ' ';
        $multipoint .= $value["pos"]["y"];
        $multipoint .= '),';
    }

    $multipoint = 'MULTIPOINT (' . rtrim($multipoint, ',') . ')';
    // var_dump($point_pos);die;
    // die('<pre>' . print_r($multipoint, 1) . '</pre>');
    $targetProjSricCode = intval($targetProj["srsProjNumber"]);
    $currentProjSricCode = intval($currentProj["srsProjNumber"]);
    $sql = "SELECT ST_AsGeoJson(ST_Transform(ST_GeomFromText($1,$2),$3::int)) As target_geom";
    $v = array($multipoint, intval($targetProjSricCode), intval($currentProjSricCode));
    $t = array('s', 'i', 'i');
    $res = db_prep_query($sql, $v, $t);
    db_fetch_row($res);
    $geom = db_result($res, 0, 'target_geom');
    // @TODO: Test the sql
    // die('<pre>' . print_r($geom, 1) . '</pre>');
    echo($geom);
    // $geom = json_decode(db_result($res, 0, 'geom'));
    // $feat->geometry = $geom;

    // transform point json to wkt MULTIPOINT
} catch (Exception $e) {
    echo($e);
    die;
}
