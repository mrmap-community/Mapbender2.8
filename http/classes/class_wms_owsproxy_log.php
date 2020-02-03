<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once(dirname(__FILE__)."/../../core/globalSettings.php");

class WmsOwsLogCsv {
    private $mb_user_id;
    private $function;
    private $listType;
    private $userId;
    private $wmsId;
    private $timeFrom;
    private $timeTo;
    private $withContactData;
    
    private $resultHeader;
    private $resultHeaderDisplay;
    private $resultData;
    private $resultDataDisplay;
    private $resultMessage;
    
    
    private static $SEPARATOR_VALUE = "\t";
    private static $SEPARATOR_ROW = "\n";
    
    private static $LIMIT_INT = OWS_LOG_EXPORT_LIMIT;
    
    private static $LIMIT_SQL = " ORDER BY m.log_id DESC LIMIT ";
    
    private function __construct() {
    }
    
    public static function create($mb_user_id, $function, $userId, $wmsId, $listType, $timeFrom, $timeTo, $withContactData){
        if($listType === null){
            return "Der Parameter 'listType' wurde nicht uebergeben.";
        } else if($listType != "service" && $listType != "user"){
            return "Der 'listType' ".$listType." ist nicht unterstuetzt.";
        }
        if(($listType == "service" && $wmsId === null)
                || ($listType == "user" && $userId === null)){
            return "Parameter 'userId' oder/und 'wmsId' wurde/n nicht uebergeben.";
        }
        
        if ($timeFrom === null || $timeTo === null){
            return "Parameter 'timeFrom' oder/und 'timeTo' wurde/n nicht uebergeben.";
        }

        if($function == null
                 || ($function != "getServiceLogs"
                 && $function != "listServiceLogs"
                 && $function != "getSum"
                 && $function != "deleteServiceLogs")){
            return "Der Parameter 'function' wurde nicht uebergeben bzw. ist nicth unterstützt";
        }

        $wmslogcsv = new WmsOwsLogCsv();
        $wmslogcsv->wmsId = $wmsId;
        $wmslogcsv->userId = $userId;
        $wmslogcsv->timeFrom = $timeFrom;
        $wmslogcsv->timeTo = $timeTo;
        $wmslogcsv->mb_user_id = $mb_user_id;
        $wmslogcsv->listType = $listType;
        $wmslogcsv->function = $function;
        
        if($withContactData != null && strlen($withContactData) > 0){
            $wmslogcsv->withContactData = $withContactData;
        }
        $wmslogcsv->resultHeader = array();
        $wmslogcsv->resultHeaderDisplay = array();
        $wmslogcsv->resultData = array();
        $wmslogcsv->resultDataDisplay = array();
        $wmslogcsv->resultMessage = "";
        return $wmslogcsv;
    }
    
    public function handle(){
        if($this->function == "getServiceLogs"){
            $this->getServiceLogs();
        } else if($this->function == "listServiceLogs"){
            $this->listServiceLogs();
        } else if($this->function == "deleteServiceLogs"){
            $this->deleteServiceLogs();
        } else if($this->function == "getSum"){
            $this->getSum();
        }
    }

    private function getSum() {
        $this->getServiceLogs();

        if(!empty($this->resultData)) {
            $rowCount = count($this->resultData[0]);
            $maxRows = (count($this->resultData) -1);
            $offset = array();
            $data = array();

            for($i=0;$i<$rowCount;$i++) {
                if($this->resultHeader[$i] == 'price')
                    $data[] = array('price', $this->resultData[$maxRows][$i]);
                else if($this->resultHeader[$i] == 'pixel')
                    $data[] = array('pixel', $this->resultData[$maxRows][$i]);
            }

            
        }

        $this->resultHeader = array();
        $this->resultData = $data;
    }

    private function getServiceLogs(){
#function=getServiceLogs&listType=service&serviceType=wms& wmsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&withContactData=1
#function=getServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
        if($this->listType == "service"){
            $selectColumns = " m.*,u.mb_user_id,u.mb_user_name";
            $selectColumnsDisplay = " m.fkey_wms_id as wms_id,u.mb_user_id,u.mb_user_name, sum(m.pixel) as pixel,sum(m.price) as price";
            $groupByForDisplay = " GROUP BY m.fkey_wms_id, u.mb_user_id,u.mb_user_name  ORDER BY m.fkey_wms_id DESC";
            $join = " INNER JOIN mb_user AS u ON (u.mb_user_id = m.fkey_mb_user_id)";
//            $innerjoinContactData = "";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_department,u.mb_user_description"
                        .",u.mb_user_email,u.mb_user_phone,u.mb_user_street"
                        .",u.mb_user_housenumber,u.mb_user_postal_code"
                        .",u.mb_user_city";
                $groupByForDisplay = " GROUP BY m.fkey_wms_id, u.mb_user_id,u.mb_user_name, u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wms_id DESC";
            }
            $v = array($this->mb_user_id, $this->timeFrom, $this->timeTo);
//            $v = array($this->wmsId, $this->timeFrom, $this->timeTo, 9415);
            $t = array('i', "t", "t");
            $wmsIdWhere = "";
            if($this->wmsId !== null && intval($this->wmsId)> -1){
                $v[] = $this->wmsId;
                $t[] = "i";
                $wmsIdWhere = " AND m.fkey_wms_id = $".count($v);
            }
            
            $sql  = "SELECT".$selectColumns
                    ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1"
                    .$wmsIdWhere." AND m.proxy_log_timestamp >= $2"
                    ." AND m.proxy_log_timestamp <= $3)".$join;
		                
            $sqlDisplay  = "SELECT".$selectColumnsDisplay
            ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
            		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1"
            		.$wmsIdWhere." AND m.proxy_log_timestamp >= $2"
            				." AND m.proxy_log_timestamp <= $3)".$join
            				." ". $groupByForDisplay;
            
            $result = db_prep_query($sql,$v,$t);
            $resultDisplay = db_prep_query($sqlDisplay,$v,$t);
            $this->readResult($result);
            $this->readResultDisplay($resultDisplay);
        } else if($this->listType == "user"){
            $selectColumns = " m.*,w.wms_title,w.wms_version,w.wms_abstract";
            $selectColumnsDisplay = " m.fkey_wms_id as wms_id,w.wms_title,w.wms_version,w.wms_abstract, sum(m.pixel) as pixel,sum(m.price) as price";
            $groupByForDisplay = " GROUP BY m.fkey_wms_id,w.wms_title,w.wms_version,w.wms_abstract ORDER BY m.fkey_wms_id DESC";
            $join = "";
//            $innerjoinContactData = "";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_department,u.mb_user_description"
                        .",u.mb_user_email,u.mb_user_phone,u.mb_user_street"
                        .",u.mb_user_housenumber,u.mb_user_postal_code"
                        .",u.mb_user_city";
                $join .= " INNER JOIN mb_user AS u  ON (u.mb_user_id = m.fkey_mb_user_id)";
                $groupByForDisplay = " GROUP BY m.fkey_wms_id, w.wms_title,w.wms_version,w.wms_abstract,u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wms_id DESC";
            }            
            
            $v = array($this->timeFrom, $this->timeTo, $this->mb_user_id);
            $t = array("t", "t", "i");
            
            /* GUI start*/
            if(intval($this->userId) == -1){ // all users
                $userWhere = "";
            } else {
                $v[] = $this->userId;
                $t[] = "i";
                $userWhere = " AND m.fkey_mb_user_id = $".count($v);
            }
            $wmsIdWhere = "";
            if($this->wmsId !== null && strlen($this->wmsId)> 0 && intval($this->wmsId)> -1){
                $v[] = $this->wmsId;
                $t[] = "i";
                $wmsIdWhere = " AND m.fkey_wms_id=$".count($v);
            }
            /* GUI end*/
            $sql  = "SELECT ".$selectColumns
                    ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3"
                    .$userWhere." AND m.proxy_log_timestamp >= $1"
                    ." AND m.proxy_log_timestamp <= $2".$wmsIdWhere.")".$join;
		    
            $sqlDisplay  = "SELECT".$selectColumnsDisplay
					." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3"
                    .$userWhere." AND m.proxy_log_timestamp >= $1"
                    ." AND m.proxy_log_timestamp <= $2".$wmsIdWhere.")".$join
            		." ". $groupByForDisplay;
            $result = db_prep_query($sql,$v,$t);
            $resultDisplay = db_prep_query($sqlDisplay,$v,$t);
            $this->readResult($result);
            $this->readResultDisplay($resultDisplay);
        }
    }

    private function listServiceLogs(){
#function=listServiceLogs&listType=service&serviceType=wms& wmsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&withContactData=1
#function=listServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=listServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&wmsId=xyz
        if($this->listType == "service"){
            $selectColumns = " m.log_id,u.mb_user_id,u.mb_user_name,u.mb_user_department";
            $join = " INNER JOIN mb_user AS u  ON (u.mb_user_id = m.fkey_mb_user_id)";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_street,u.mb_user_housenumber"
                        .",u.mb_user_postal_code,u.mb_user_city";
            }
            $v = array($this->wmsId, $this->timeFrom, $this->timeTo, $this->mb_user_id);
//            $v = array($this->wmsId, $this->timeFrom, $this->timeTo, 9415);
            $t = array('i', "t", "t", "i");
            $sql  = "SELECT".$selectColumns
                    ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                    ." AND m.fkey_wms_id = $1 AND  m.proxy_log_timestamp >= $2"
                    ." AND m.proxy_log_timestamp <= $3)".$join
                    ." GROUP BY m.log_id, ".$selectColumns
		    . WmsOwsLogCsv::$LIMIT_SQL . WmsOwsLogCsv::$LIMIT_INT;
            
            $result = db_prep_query($sql,$v,$t);
            $this->readResult($result);
        } else if($this->listType == "user"){
            $selectColumns = " m.log_id,w.wms_id,w.wms_title";
            $join = "";
//            $innerjoinContactData = "";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_department,u.mb_user_description"
                        .",u.mb_user_email,u.mb_user_phone,u.mb_user_street"
                        .",u.mb_user_housenumber,u.mb_user_postal_code"
                        .",u.mb_user_city";
                $join .= " INNER JOIN mb_user AS u  ON (u.mb_user_id = m.fkey_mb_user_id)";
            }
            $v = array($this->userId, $this->timeFrom, $this->timeTo, $this->mb_user_id);
//            $v = array($this->userId, $this->timeFrom, $this->timeTo, 9415);
            $t = array('i', "t", "t", "i");
            $whereWms = "";
            $sql  = "SELECT ".$selectColumns
                    ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                    ." AND m.fkey_mb_user_id = $1 AND m.proxy_log_timestamp >= $2"
                    ." AND m.proxy_log_timestamp <= $3".$whereWms.")".$join
                    ." GROUP BY m.log_id, ".$selectColumns
		    . WmsOwsLogCsv::$LIMIT_SQL . WmsOwsLogCsv::$LIMIT_INT;
            $result = db_prep_query($sql,$v,$t);
            $this->readResult($result);
        }
    }

    private function deleteServiceLogs(){
#function=deleteServiceLogs&listType=service&serviceType=wms& wmsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=deleteServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=deleteServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&wmsId=xyz
        if($this->listType == "service"){
//            $v = array($wmsId, $timeFrom, $timeTo);
//            $t = array('i', "t", "t");
//            $sql = "DELETE FROM mb_proxy_log WHERE fkey_wms_id = $1"
//                    ." AND proxy_log_timestamp >= $2 AND proxy_log_timestamp <= $3";
            $v = array($this->timeFrom, $this->timeTo, $this->mb_user_id);
            $t = array("t", "t", "i");
            $sql = "DELETE FROM mb_proxy_log"
                    ." WHERE log_id in("
                        ." SELECT m.log_id"
                        ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                        ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3";
            $inOffset = 4;
    		foreach(explode(",",$this->wmsId) as $wms) {
    			$v[] = trim($wms);
    			$t[] = "i";
    			$inParams[] = "$".$inOffset;
    			$inOffset++;
    		}
    		$sql .= " AND m.fkey_wms_id IN (" .implode(",",$inParams) .")";
    		$sql .= " AND  m.proxy_log_timestamp >= $1"
                   ." AND m.proxy_log_timestamp <= $2)"
                    .")";
            $result = db_prep_query($sql,$v,$t);
            $resnum = pg_affected_rows($result);
            if($resnum > 0){
                $this->resultMessage = $resnum." Log-Datensaetze (DienstId :".$this->wmsId.") wurden erfolgreich geloescht.";
            } else {
                $this->resultMessage = "Kein Log-Datensatz (DienstId: ".$this->wmsId.") wurde geloescht.";
            }
        } else if($this->listType == "user"){
//            $v = array($userId, $timeFrom, $timeTo);
//            $t = array('i', "t", "t");
//            $sql = "DELETE FROM mb_proxy_log WHERE fkey_mb_user_id = $1"
//                    ." AND proxy_log_timestamp >= $2 AND proxy_log_timestamp <= $3";
//            if($wmsId !== null && $wmsId != "") {
//                $sql .= " AND fkey_wms_id = $4";
//                $v[] = $wmsId;
//                $t[] = 'i';
//            }
            $v = array($this->userId, $this->timeFrom, $this->timeTo, $this->mb_user_id);
            $t = array('i', "t", "t", "i");
            $whereWms = "";
            if($this->wmsId !== null && $this->wmsId != "") {
                $whereWms = " AND m.fkey_wms_id IN (";
                $inOffset = 5;
    			foreach(explode(",",$this->wmsId) as $wms) {
    				$v[] = trim($wms);
    				$t[] = "i";
    				$inParams[] = "$".$inOffset;
    				$inOffset++;
    			}
                $whereWms .= implode(",",$inParams); 
    			
                $whereWms .= ")";
            }
            $sql = "DELETE FROM mb_proxy_log"
                    ." WHERE log_id in("
                        ." SELECT m.log_id"
                        ." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                        ." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                        ." AND m.fkey_mb_user_id = $1 AND  m.proxy_log_timestamp >= $2"
                        ." AND m.proxy_log_timestamp <= $3".$whereWms.")"
                    .")";
            $result = db_prep_query($sql,$v,$t);
            $resnum = pg_affected_rows($result);
            if($resnum > 0){
                if($this->wmsId !== null && $this->wmsId != ""){
                    $this->resultMessage = $resnum." Log-Datensaetze (DienstId :".$this->wmsId.", UserId: ".$this->userId.") wurden erfolgreich geloescht.";
                } else {
                    $this->resultMessage = $resnum." Log-Datensaetze (UserId: ".$this->userId.") wurden erfolgreich geloescht.";
                }
            } else {
                if($this->wmsId !== null && $this->wmsId != ""){
                    $this->resultMessage = "Kein Log-Datensatz (DienstId: ".$this->wmsId.", UserId: ".$this->userId.") wurde geloescht.";
                } else {
                    $this->resultMessage = "Kein Log-Datensatz (UserId: ".$this->userId.") wurde geloescht.";
                }
            }
        }
    }
    
    private function readResult($result){
    	$offsetPixel = null;
    	$offsetPrice = null;
    	$sumPixel = 0;
    	$sumPrice = 0;
    	if ($result != false) {
    		$num_fields = pg_num_fields($result);
    		for ( $i = 0; $i < $num_fields; $i++ ){
    			$field_name = pg_field_name($result , $i);
    			$this->resultHeader[] = $field_name;
    			    
    			// keep offsets of prixel and price row
    			if($field_name === "pixel")        $offsetPixel = $i;
    			else if($field_name === "price")   $offsetPrice = $i;
    		}
    	}
    	$i = 0;
    	while($row = db_fetch_row($result)){
    		foreach( $row as $key=>$value){
    			$this->resultData[$i][] = $value;
    			    
    			if($key == $offsetPixel)        $sumPixel += $value;
    			else if($key == $offsetPrice)   $sumPrice += $value;
    		}
    
    		$i++;
    	}
    
    	if(!is_null($offsetPixel) || !is_null($offsetPrice)) {
    		for($j=0;$j<$num_fields;$j++) {
    			if($j == $offsetPixel && !is_null($offsetPixel)) {
    				$this->resultData[$i][] = (string)$sumPixel;
    			}
    			else if($j == $offsetPrice && !is_null($offsetPrice)) {
    				$this->resultData[$i][] = (string)$sumPrice;
    			}
    			else {
    				$this->resultData[$i][] = '---';
    			}
    		}
    	}
    }
    
    private function readResultDisplay($result){
        $offsetPixel = null;
        $offsetPrice = null;
        $sumPixel = 0;
        $sumPrice = 0;
        if ($result != false) {
            $num_fields = pg_num_fields($result);
            for ( $i = 0; $i < $num_fields; $i++ ){
                $field_name = pg_field_name($result , $i);
                $this->resultHeaderDisplay[] = $field_name;

                // keep offsets of prixel and price row
                if($field_name === "pixel")        $offsetPixel = $i;
                else if($field_name === "price")   $offsetPrice = $i;
            }
        }
        $i = 0;
        while($row = db_fetch_row($result)){
        foreach( $row as $key=>$value){
    			$this->resultDataDisplay[$i][] = $value;
    			    
    			if($key == $offsetPixel)        $sumPixel += $value;
    			else if($key == $offsetPrice)   $sumPrice += $value;
    		}

            $i++;
        }
        
        if(!is_null($offsetPixel) || !is_null($offsetPrice)) {
        	for($j=0;$j<$num_fields;$j++) {
        		if($j == $offsetPixel && !is_null($offsetPixel)) {
        			$this->resultDataDisplay[$i][] = (string)$sumPixel;
        		}
        		else if($j == $offsetPrice && !is_null($offsetPrice)) {
        			$this->resultDataDisplay[$i][] = (string)$sumPrice;
        		}
        		else {
        			$this->resultDataDisplay[$i][] = '---';
        		}
        	}
        }
    }
    
    public function getAsCsv(){
        $header = "";
        $data = "";
        foreach($this->resultHeader as $colname){
            $header .= '"'.$colname.'"'.WmsOwsLogCsv::$SEPARATOR_VALUE;
        }

        foreach($this->resultData as $row){
            $line = '';
            foreach( $row as $value){
                if ($value == null || $value == ""){
                    $value = "".WmsOwsLogCsv::$SEPARATOR_VALUE;
                } else {
//                    if(CHARSET == 'UTF-8'){
//                        $value = utf8_encode($value);
//                    }
                    $value = str_replace('"', '""', $value);
                    $value = '"'.$value.'"'.WmsOwsLogCsv::$SEPARATOR_VALUE;
                }
                $line .= $value;
            }
            $data .= trim($line).WmsOwsLogCsv::$SEPARATOR_ROW;
        }
        return $header.WmsOwsLogCsv::$SEPARATOR_ROW.$data;
    }
    
    public function getAsArray($function=null) {
    	if($function == "getServiceLogs") {
    		return array(
    				"function"=> $this->function,
    				"header"=> $this->resultHeader,
    				"headerDisplay"=> $this->resultHeaderDisplay,
    				//"data" => $this->resultData,
    				"dataDisplay" => $this->resultDataDisplay,
    				"message" => $this->resultMessage,
    				"error" => "",
    				"limit" => WmsOwsLogCsv::$LIMIT_INT);
    	}
    	else {
	        return array(
	            "function"=> $this->function,
	            "header"=> $this->resultHeader,
	        	"headerDisplay"=> $this->resultHeaderDisplay,
	            "data" => $this->resultData,
	        	"dataDisplay" => $this->resultDataDisplay,
	            "message" => $this->resultMessage,
	            "error" => "",
	            "limit" => WmsOwsLogCsv::$LIMIT_INT);
    	}
    }
}
?>
