<?php 
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
require_once(dirname(__FILE__) . "/../core/globalSettings.php");
//require_once(dirname(__FILE__) . "/../http/classes/class_iso19139.php");
//require_once(dirname(__FILE__) . "/../http/classes/class_Uuid.php");

$forceUpdate = false;

$con = db_connect(DBSERVER,OWNER,PW);
db_select_db(DB,$con);

$sql = "SELECT wms_id, wms_getcapabilities_doc, wms_version ";
$sql .= "FROM wms";

$v = array();
$t = array();
$res = db_prep_query($sql,$v,$t);
logMessages(date('Y-m-d - H:i:s', time()));
while($row = db_fetch_array($res)){
    //logMessages("WMS with id: " . $row['wms_id'] . " (" .$row['wms_version'] . ")");    
    if ($row['wms_getcapabilities_doc'] != "") {
        //logMessages("Capabilities Document found!");
    } else {
        logMessages("WMS " . $row['wms_id'] . " - No Capabilities Document in database!");
    }
    
    
    $capabilitiesDomObject = new DOMDocument ();
    libxml_use_internal_errors ( true );
    try {
        
        $capabilitiesXmlObject = simplexml_load_string( $row['wms_getcapabilities_doc'] ); 
        //$capabilitiesDomObject->loadXML ( $row['wms_getcapabilities_doc'] );
        if ($capabilitiesXmlObject === false) {
            foreach ( libxml_get_errors () as $error ) {
                logMessages( "php/mod_qualifyWmsLayerNames.php:" . $error->message );
            }
            throw new Exception ( "php/mod_qualifyWmsLayerNames.php:" . 'Cannot parse metadata with simplexml!' );
        }
    } catch ( Exception $e ) {
        logMessages( "php/mod_qualifyWmsLayerNames.php:" . $e->getMessage () );
    }
    if ($capabilitiesXmlObject !== false) {
        $layerCount = 0;
        foreach ($capabilitiesXmlObject->Capability->Layer as $layer) {
            if (isset($layer->Name) && $layer->Name != "" && $layer->Name != null) {
                //logMessages("Root Layer - Title: " . $layer->Title . " - Name: ".$layer->Name);
            } else {
                logMessages("********************************************************************");
                logMessages("WMS " . $row['wms_id'] . " - Root Layer - Title: " . $layer->Title . " - No name given in Capabilities - only category!");
                logMessages("********************************************************************");
                
                $title = $layer->Title;
                //select layer with title and wms_id from db and check how many are given ;-) - if only one - the name can be exchanged without any problems
                $sql2 = "SELECT layer_id, layer_name from layer where layer_title = $1 and fkey_wms_id = $2";
                $v2 = array($title, $row['wms_id']);
                $t2 = array("s","i");
                $res2 = db_prep_query($sql2,$v2,$t2);
                $countLayerWithTitle = 0;
                while($row2 = db_fetch_array($res2)){
                    logMessages("Found layer name in db: " . $row2['layer_name'] . " - layer_id: " .$row2['layer_id']);
                    $countLayerWithTitle++;
                }
                if ($countLayerWithTitle > 1) {
                    logMessages("More than one layer with title found - repair by hand!");
                } else {
                    if ($countLayerWithTitle == 1 && $forceUpdate == true) {
                        //repair layer name
                        $sql3 = "update layer set layer_name = 'unnamed_layer:' || md5( $1 ) where layer_title = $1 and fkey_wms_id = $2";
                        $v3 = array($title, $row['wms_id']);
                        $t3 = array("s","i");
                        $res3 = db_prep_query($sql3,$v3,$t3);
                        logMessages("Updated layer name to " . "unnamed_layer:" . md5($title) . " - WMS " . $row['wms_id']);
                    }
                }
                
            }
            getLayerAttributesRecursive($layer, $row['wms_id'], $forceUpdate);  
        } 
    }   
}

function getLayerAttributesRecursive($layer, $wmsId, $forceUpdate) { 
    foreach ($layer->Layer as $childLayer) {
        if (isset($childLayer->Name) && $childLayer->Name != "" && $childLayer->Name != null) {
            //logMessages("Title: " . $childLayer->Title . " - Name: ".$childLayer->Name);
        } else {
            logMessages("********************************************************************");
            logMessages("WMS " . $wmsId . " - Title: " . $childLayer->Title . " - No name given in Capabilities - only category!");
            logMessages("********************************************************************");
            $title = $childLayer->Title;
            $con = db_connect(DBSERVER,OWNER,PW);
            db_select_db(DB,$con);
            //logMessages("test1");
            //select layer with title and wms_id from db and check how many are given ;-) - if only one - the name can be exchanged without any problems
            $sql2 = "SELECT layer_name, layer_id from layer where layer_title = $1 and fkey_wms_id = $2";
            $v2 = array($title, $wmsId);
            $t2 = array("s","i");
            $res = db_prep_query($sql2,$v2,$t2);  
            $countLayerWithTitle = 0;
            while($row2 = db_fetch_array($res2)){
                logMessages("Found layer name in db:  " . $row2['layer_name'] . " - layer_id: " . $row2['layer_id']); 
                $countLayerWithTitle++;
            }
            if ($countLayerWithTitle > 1) {
                logMessages("More than one layer with title found - repair by hand!");
            } else {
                if ($countLayerWithTitle == 1 && $forceUpdate == true) {
                    //repair layer name
                    $sql3 = "update layer set layer_name = 'unnamed_layer:' || md5( $1 ) where layer_title = $1 and fkey_wms_id = $2";
                    $v3 = array($title, $wmsId);
                    $t3 = array("s","i");
                    $res3 = db_prep_query($sql3,$v3,$t3);
                    logMessages("Updated layer name to " . "unnamed_layer:" . md5($title) . " - WMS " . $wmsId);
                }
            }
           
        }
        getLayerAttributesRecursive($childLayer, $wmsId, $forceUpdate);
    }
    return true;
}


function logMessages($message) {
    if (php_sapi_name() === 'cli' OR defined('STDIN')) {
        //echo __FILE__.": ".$message."\n";
        echo $message."\n";
    } else {
        $e = new mb_exception(__FILE__.": ".$message);
    }
}
?>