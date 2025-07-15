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

$json = file_get_contents("php://input");
// var_dump($json);die;
try {

    $json = json_decode($json);
    $epsg = explode(':', $_GET["targetEPSG"]);
    $epsg = $epsg[1];

    $gui_id = Mapbender::session()->get("mb_user_gui");
    $con = db_connect($DBSERVER,$OWNER,$PW);
    db_select_db(DB,$con);


    foreach($json as $feat) {
        if(preg_match('/point/i', $feat->geometry->type)) {
            $sql = "SELECT st_asgeojson(st_transform(st_setsrid(st_geomfromtext($1), 4326), $2::INT)) as geom";
            $v = array('POINT(' . $feat->geometry->coordinates[0] . ' ' . $feat->geometry->coordinates[1] . ')', $epsg);
            $t = array('s', 'i');
            $res = db_prep_query($sql,$v,$t);
            db_fetch_row($res);
            $geom = json_decode(db_result($res, 0, 'geom'));
            $feat->geometry = $geom;
        }
        if(preg_match('/linestring/i', $feat->geometry->type)) {
            $sql = "SELECT st_asgeojson(st_transform(st_setsrid(st_geomfromtext($1), 4326), $2::INT)) as geom";
            $geom = 'LINESTRING(';
            $coords = array();
            foreach($feat->geometry->coordinates as $coord) {
                $coords[] = $coord[0] . ' ' . $coord[1];
            }
            $geom = $geom . implode(',', $coords) . ')';
            $v = array($geom, $epsg);
            $t = array('s', 'i');
            $res = db_prep_query($sql,$v,$t);
            db_fetch_row($res);
            $geom = json_decode(db_result($res, 0, 'geom'));
            $feat->geometry = $geom;
        }
        //Ticket #8549: Added support for inner boundaries (holes) in polygons
        if(preg_match('/polygon/i', $feat->geometry->type)) {
            $sql = "SELECT st_asgeojson(st_transform(st_setsrid(st_geomfromtext($1), 4326), $2::INT)) as geom";
            $rings = array();
            foreach($feat->geometry->coordinates as $ring) {
                $coords = array();
                foreach($ring as $coord) {
                    $coords[] = $coord[0] . ' ' . $coord[1];
                }
                $rings[] = '(' . implode(',', $coords) . ')';
            }
            $geom = 'POLYGON(' . implode(',', $rings) . ')';
            $v = array($geom, $epsg);
            $t = array('s', 'i');
            $res = db_prep_query($sql, $v, $t);
            db_fetch_row($res);
            $geom = json_decode(db_result($res, 0, 'geom'));
            $feat->geometry = $geom;
        }
    }
    $json = json_encode($json);
    echo($json);

} catch (Exception $e) {
    echo($e);
    die;
}

?>
