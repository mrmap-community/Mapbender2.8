<?php
# $Id: mod_key.php 8657 2013-06-14 08:59:26Z schaef $
# http://www.mapbender.org/index.php/mod_key.php
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
echo "var mod_key_map = '".$e_target[0]."';";

$sql = "SELECT e_id,e_element FROM gui_element WHERE (e_element = 'body' or e_element = 'iframe') AND fkey_gui_id = $1 AND e_public = 1";
$v = array($gui_id);
$t = array('s');
$res = db_prep_query($sql, $v, $t);
$cnt = 0;
while($row = db_fetch_array($res)){
    $ids[$cnt] = $row["e_id"];
    $elements[$cnt] = $row["e_element"];
    $cnt++;
}

echo "var mb_key_elements = new Array(";
for($i=0; $i < count($elements); $i++){
    if($i > 0){
        echo ",";
    }
    echo "'".$elements[$i]."'";
}
echo ");";
echo "var mb_key_ids = new Array(";
for($i=0; $i < count($ids); $i++){
    if($i > 0){ echo ",";}
    echo "'".$ids[$i]."'";
}
echo ");";
?>
//if(ie){
   mb_registerInitFunctions('mod_key_init()'); 
//}
function mod_key_init(){
    for(var i=0; i<mb_key_elements.length; i++){
        if(mb_key_elements[i] == "body"){
            $('body').focus();
            $(document).bind('keydown', function(e) {
                var tag = e.target.tagName.toLowerCase();
                if (tag != 'input' && tag != 'textarea') {
                    mod_key_Keyhandler(e);
                }
            });
        }
        else {
            $(document.getElementById(mb_key_ids[i]).contentWindow.document).keydown(function(e) {
                var currentKeyId = mb_key_ids[i]; 
                var tag = e.target.tagName.toLowerCase();
                if (tag != 'input' && tag != 'textarea') {
                    mod_key_Keyhandler(e, currentKeyId);
                }
            });
        } 
    }  
}
function mod_key_Keyhandler(event, frameName){
    if(frameName){
        var code = eval("window.frames['"+frameName+"'].event.keyCode");
        focus();
    }
    else{
        var code = event.keyCode;
    }
    //console.log(code);
    if(code == 187 || code == 107 || code == 171){
        zoom(mod_key_map,true, '2.0');
    }
    if(code == 189 || code == 109 || code == 173){
        zoom(mod_key_map,false, '2.0');
    }
    if(code == 32 || code == 13){
        setMapRequest(mod_key_map);
    }
    if(code == 37){
        mb_panMap(mod_key_map,"W");
    }
    if(code == 38){
        mb_panMap(mod_key_map,"N");
    }
    if(code == 39){
        mb_panMap(mod_key_map,"E");
    }
    if(code == 40){
        mb_panMap(mod_key_map,"S");
    }
}
