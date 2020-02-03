<?php
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License 
# and Simplified BSD license.  
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require_once(dirname(__FILE__)."/../../core/globalSettings.php");

class OwsLogCsv {
    private $mb_user_id;
    private $function;
    private $listType;
    private $userId;
    private $owsType;
    private $owsId;
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
    
    public static function create($mb_user_id, $function, $userId, $owsId, $listType, $timeFrom, $timeTo, $withContactData, $owsType){
        if($listType === null){
            return "Der Parameter 'listType' wurde nicht uebergeben.";
        } else if($listType != "service" && $listType != "user"){
            return "Der 'listType' ".$listType." ist nicht unterstuetzt.";
        }
        if(($listType == "service" && $owsId === null)
                || ($listType == "user" && $userId === null)){
            return "Parameter 'userId' oder/und 'owsId' wurde/n nicht uebergeben.";
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

        $owslogcsv = new OwsLogCsv();
        $owslogcsv->owsId = $owsId;
        $owslogcsv->owsType = $owsType;
        $owslogcsv->userId = $userId;
        $owslogcsv->timeFrom = $timeFrom;
        $owslogcsv->timeTo = $timeTo;
        $owslogcsv->mb_user_id = $mb_user_id;
        $owslogcsv->listType = $listType;
        $owslogcsv->function = $function;
        
        if($withContactData != null && strlen($withContactData) > 0){
            $owslogcsv->withContactData = $withContactData;
        }
        $owslogcsv->resultHeader = array();
        $owslogcsv->resultHeaderDisplay = array();
        $owslogcsv->resultData = array();
        $owslogcsv->resultDataDisplay = array();
        $owslogcsv->resultMessage = "";
        return $owslogcsv;
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
#function=getServiceLogs&listType=service&serviceType=wms& owsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&withContactData=1
#function=getServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
        if($this->listType == "service"){
            $selectColumns = " m.*,u.mb_user_id,u.mb_user_name";
		switch ($this->owsType) {
			case "wms":
           			$selectColumnsDisplay = " m.fkey_wms_id as wms_id,u.mb_user_id,u.mb_user_name, sum(m.pixel) as pixel,sum(m.price) as price";
				$groupByForDisplay = " GROUP BY m.fkey_wms_id, u.mb_user_id,u.mb_user_name  ORDER BY m.fkey_wms_id DESC";
				break;
			case "wfs":
           			$selectColumnsDisplay = " m.fkey_wfs_id as wfs_id,u.mb_user_id,u.mb_user_name, sum(m.features) as features,sum(m.price) as price";
				$groupByForDisplay = " GROUP BY m.fkey_wfs_id, u.mb_user_id,u.mb_user_name  ORDER BY m.fkey_wfs_id DESC";
				break;
		}

            $join = " INNER JOIN mb_user AS u ON (u.mb_user_id = m.fkey_mb_user_id)";
//            $innerjoinContactData = "";
            if($this->withContactData !== null && $this->withContactData == "1") {
                	$selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_department,u.mb_user_description"
                        .",u.mb_user_email,u.mb_user_phone,u.mb_user_street"
                        .",u.mb_user_housenumber,u.mb_user_postal_code"
                        .",u.mb_user_city";
			switch ($this->owsType) {
				case "wms":
           				$groupByForDisplay = " GROUP BY m.fkey_wms_id, u.mb_user_id,u.mb_user_name, u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wms_id DESC";
					break;
				case "wfs":
           				$groupByForDisplay = " GROUP BY m.fkey_wfs_id, u.mb_user_id,u.mb_user_name, u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wfs_id DESC";
					break;
			}
            }
            $v = array($this->mb_user_id, $this->timeFrom, $this->timeTo);
//            $v = array($this->owsId, $this->timeFrom, $this->timeTo, 9415);
            $t = array('i', "t", "t");
            $owsIdWhere = "";
            if($this->owsId !== null && intval($this->owsId)> -1){
                $v[] = $this->owsId;
                $t[] = "i";
		switch ($this->owsType) {
			case "wms":
           			$owsIdWhere = " AND m.fkey_wms_id = $".count($v);
				break;
			case "wfs":
           			$owsIdWhere = " AND m.fkey_wfs_id = $".count($v);
				break;
		}
            }
      	switch ($this->owsType) {
		case "wms":
           		$sql  = "SELECT".$selectColumns
                    	." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    	." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1"
                   	.$owsIdWhere." AND m.proxy_log_timestamp >= $2"
                    	." AND m.proxy_log_timestamp <= $3)".$join;
		                
            		$sqlDisplay  = "SELECT".$selectColumnsDisplay
           		." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
            		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1"
            		.$owsIdWhere." AND m.proxy_log_timestamp >= $2"
            		." AND m.proxy_log_timestamp <= $3)".$join
            		." ". $groupByForDisplay;
			break;
		case "wfs":
           		$sql  = "SELECT".$selectColumns
                    	." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                    	." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$1"
                   	.$owsIdWhere." AND m.proxy_log_timestamp >= $2"
                    	." AND m.proxy_log_timestamp <= $3)".$join;
		                
            		$sqlDisplay  = "SELECT".$selectColumnsDisplay
           		." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
            		." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$1"
            		.$owsIdWhere." AND m.proxy_log_timestamp >= $2"
            		." AND m.proxy_log_timestamp <= $3)".$join
            		." ". $groupByForDisplay;
			break;
	}    
            $result = db_prep_query($sql,$v,$t);
            $resultDisplay = db_prep_query($sqlDisplay,$v,$t);
            $this->readResult($result);
            $this->readResultDisplay($resultDisplay);
        } else if($this->listType == "user"){
		switch ($this->owsType) {
			case "wms":
           			$selectColumns = " m.*,w.wms_title,w.wms_version,w.wms_abstract";
            			$selectColumnsDisplay = " m.fkey_wms_id as wms_id,w.wms_title,w.wms_version,w.wms_abstract, sum(m.pixel) as pixel,sum(m.price) as price";
            			$groupByForDisplay = " GROUP BY m.fkey_wms_id,w.wms_title,w.wms_version,w.wms_abstract ORDER BY m.fkey_wms_id DESC";
				break;
			case "wfs":
           			$selectColumns = " m.*,w.wfs_title,w.wfs_version,w.wfs_abstract";
            			$selectColumnsDisplay = " m.fkey_wfs_id as wfs_id,w.wfs_title,w.wfs_version,w.wfs_abstract, sum(m.features) as features,sum(m.price) as price";
            			$groupByForDisplay = " GROUP BY m.fkey_wfs_id,w.wfs_title,w.wfs_version,w.wfs_abstract ORDER BY m.fkey_wfs_id DESC";
				break;
		}
            $join = "";
//            $innerjoinContactData = "";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_department,u.mb_user_description"
                        .",u.mb_user_email,u.mb_user_phone,u.mb_user_street"
                        .",u.mb_user_housenumber,u.mb_user_postal_code"
                        .",u.mb_user_city";
                $join .= " INNER JOIN mb_user AS u  ON (u.mb_user_id = m.fkey_mb_user_id)";
		switch ($this->owsType) {
			case "wms":
           			$groupByForDisplay = " GROUP BY m.fkey_wms_id, 		w.wms_title,w.wms_version,w.wms_abstract,u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wms_id DESC";
				break;
			case "wfs":
           			$groupByForDisplay = " GROUP BY m.fkey_wfs_id, 		w.wfs_title,w.wfs_version,w.wfs_abstract,u.mb_user_firstname,mb_user_lastname,u.mb_user_department,u.mb_user_description,u.mb_user_email,u.mb_user_phone,u.mb_user_street,u.mb_user_housenumber,u.mb_user_postal_code,u.mb_user_city ORDER BY m.fkey_wfs_id DESC";
				break;
		}
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
            $owsIdWhere = "";
            if($this->owsId !== null && strlen($this->owsId)> 0 && intval($this->owsId)> -1){
                $v[] = $this->owsId;
                $t[] = "i";
		switch ($this->owsType) {
			case "wms":
           			$owsIdWhere = " AND m.fkey_wms_id=$".count($v);
				break;
			case "wfs":
           			$owsIdWhere = " AND m.fkey_wfs_id=$".count($v);
				break;
		}
            }
            /* GUI end*/
		switch ($this->owsType) {
			case "wms":
           			$sql  = "SELECT ".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3"
                    		.$userWhere." AND m.proxy_log_timestamp >= $1"
                    		." AND m.proxy_log_timestamp <= $2".$owsIdWhere.")".$join;
		    
            			$sqlDisplay  = "SELECT".$selectColumnsDisplay
				." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3"
                    		.$userWhere." AND m.proxy_log_timestamp >= $1"
                    		." AND m.proxy_log_timestamp <= $2".$owsIdWhere.")".$join
            			." ". $groupByForDisplay;
				break;
			case "wfs":
           			$sql  = "SELECT ".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                    		." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$3"
                    		.$userWhere." AND m.proxy_log_timestamp >= $1"
                    		." AND m.proxy_log_timestamp <= $2".$owsIdWhere.")".$join;
		    
            			$sqlDisplay  = "SELECT".$selectColumnsDisplay
				." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                    		." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$3"
                    		.$userWhere." AND m.proxy_log_timestamp >= $1"
                    		." AND m.proxy_log_timestamp <= $2".$owsIdWhere.")".$join
            			." ". $groupByForDisplay;
				break;
		}
            $result = db_prep_query($sql,$v,$t);
            $resultDisplay = db_prep_query($sqlDisplay,$v,$t);
            $this->readResult($result);
            $this->readResultDisplay($resultDisplay);
        }
    }

    private function listServiceLogs(){
#function=listServiceLogs&listType=service&serviceType=wms& owsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&withContactData=1
#function=listServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=listServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&owsId=xyz
        if($this->listType == "service"){
            $selectColumns = " m.log_id,u.mb_user_id,u.mb_user_name,u.mb_user_department";
            $join = " INNER JOIN mb_user AS u  ON (u.mb_user_id = m.fkey_mb_user_id)";
            if($this->withContactData !== null && $this->withContactData == "1") {
                $selectColumns .= ",u.mb_user_firstname,mb_user_lastname"
                        .",u.mb_user_street,u.mb_user_housenumber"
                        .",u.mb_user_postal_code,u.mb_user_city";
            }
            $v = array($this->owsId, $this->timeFrom, $this->timeTo, $this->mb_user_id);
//            $v = array($this->owsId, $this->timeFrom, $this->timeTo, 9415);
            $t = array('i', "t", "t", "i");
		switch ($this->owsType) {
			case "wms":
           			$sql  = "SELECT".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                    		." AND m.fkey_wms_id = $1 AND  m.proxy_log_timestamp >= $2"
                    		." AND m.proxy_log_timestamp <= $3)".$join
                    		." GROUP BY m.log_id, ".$selectColumns
		    		. OwsLogCsv::$LIMIT_SQL . OwsLogCsv::$LIMIT_INT;
				break;
			case "wfs":
           			$sql  = "SELECT".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                    		." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$4"
                    		." AND m.fkey_wfs_id = $1 AND  m.proxy_log_timestamp >= $2"
                    		." AND m.proxy_log_timestamp <= $3)".$join
                    		." GROUP BY m.log_id, ".$selectColumns
		    		. OwsLogCsv::$LIMIT_SQL . OwsLogCsv::$LIMIT_INT;
				break;
		}
            $result = db_prep_query($sql,$v,$t);
            $this->readResult($result);
        } else if($this->listType == "user"){
		switch ($this->owsType) {
			case "wms":
           			$selectColumns = " m.log_id,w.wms_id,w.wms_title";
				break;
			case "wfs":
           			$selectColumns = " m.log_id,w.wfs_id,w.wfs_title";
				break;
		}
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
            $whereOws = "";
		switch ($this->owsType) {
			case "wms":
           			$sql  = "SELECT ".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                    		." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                    		." AND m.fkey_mb_user_id = $1 AND m.proxy_log_timestamp >= $2"
                    		." AND m.proxy_log_timestamp <= $3".$whereOws.")".$join
                    		." GROUP BY m.log_id, ".$selectColumns
		    		. OwsLogCsv::$LIMIT_SQL . OwsLogCsv::$LIMIT_INT;
				break;
			case "wfs":
           			$sql  = "SELECT ".$selectColumns
                    		." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                    		." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$4"
                    		." AND m.fkey_mb_user_id = $1 AND m.proxy_log_timestamp >= $2"
                    		." AND m.proxy_log_timestamp <= $3".$whereOws.")".$join
                    		." GROUP BY m.log_id, ".$selectColumns
		    		. OwsLogCsv::$LIMIT_SQL . OwsLogCsv::$LIMIT_INT;
				break;
		}
            $result = db_prep_query($sql,$v,$t);
            $this->readResult($result);
        }
    }

    private function deleteServiceLogs(){
#function=deleteServiceLogs&listType=service&serviceType=wms& owsId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=deleteServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12
#function=deleteServiceLogs&listType=user&   serviceType=wms&userId=xyz&timeFrom=2012-05-31T12:01&timeTo=2012-05-31T12:12&owsId=xyz
        if($this->listType == "service"){
//            $v = array($owsId, $timeFrom, $timeTo);
//            $t = array('i', "t", "t");
//            $sql = "DELETE FROM mb_proxy_log WHERE fkey_wms_id = $1"
//                    ." AND proxy_log_timestamp >= $2 AND proxy_log_timestamp <= $3";
            $v = array($this->timeFrom, $this->timeTo, $this->mb_user_id);
            $t = array("t", "t", "i");
		switch ($this->owsType) {
			case "wms":
           			$sql = "DELETE FROM mb_proxy_log"
                    		." WHERE log_id in("
                        	." SELECT m.log_id"
                        	." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                        	." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$3";
				break;
			case "wfs":
           			$sql = "DELETE FROM mb_proxy_log"
                    		." WHERE log_id in("
                        	." SELECT m.log_id"
                        	." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                        	." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$3";
				break;
		}
            $inOffset = 4;
    		foreach(explode(",",$this->owsId) as $ows) {
    			$v[] = trim($ows);
    			$t[] = "i";
    			$inParams[] = "$".$inOffset;
    			$inOffset++;
    		}
		switch ($this->owsType) {
			case "wms":
 				$sql .= " AND m.fkey_wms_id IN (" .implode(",",$inParams) .")";
				break;
			case "wfs":
 				$sql .= " AND m.fkey_wfs_id IN (" .implode(",",$inParams) .")";
				break;
		}
    		$sql .= " AND  m.proxy_log_timestamp >= $1"
                   ." AND m.proxy_log_timestamp <= $2)"
                    .")";
            $result = db_prep_query($sql,$v,$t);
            $resnum = pg_affected_rows($result);
            if($resnum > 0){
                $this->resultMessage = $resnum." Log-Datensaetze (DienstId :".$this->owsId.") wurden erfolgreich geloescht.";
            } else {
                $this->resultMessage = "Kein Log-Datensatz (DienstId: ".$this->owsId.") wurde geloescht.";
            }
        } else if($this->listType == "user"){
//            $v = array($userId, $timeFrom, $timeTo);
//            $t = array('i', "t", "t");
//            $sql = "DELETE FROM mb_proxy_log WHERE fkey_mb_user_id = $1"
//                    ." AND proxy_log_timestamp >= $2 AND proxy_log_timestamp <= $3";
//            if($owsId !== null && $owsId != "") {
//                $sql .= " AND fkey_wms_id = $4";
//                $v[] = $owsId;
//                $t[] = 'i';
//            }
            $v = array($this->userId, $this->timeFrom, $this->timeTo, $this->mb_user_id);
            $t = array('i', "t", "t", "i");
            $whereOws = "";
            if($this->owsId !== null && $this->owsId != "") {
		switch ($this->owsType) {
			case "wms":
 				$whereOws = " AND m.fkey_wms_id IN (";
				break;
			case "wfs":
 				$whereOws = " AND m.fkey_wfs_id IN (";
				break;
		}
                $inOffset = 5;
    			foreach(explode(",",$this->owsId) as $ows) {
    				$v[] = trim($ows);
    				$t[] = "i";
    				$inParams[] = "$".$inOffset;
    				$inOffset++;
    			}

                $whereOws .= implode(",",$inParams); 
    			
                $whereOws .= ")";
            }
		switch ($this->owsType) {
			case "wms":
 				$sql = "DELETE FROM mb_proxy_log"
                    		." WHERE log_id in("
                        	." SELECT m.log_id"
                        	." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
                        	." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$4"
                        	." AND m.fkey_mb_user_id = $1 AND  m.proxy_log_timestamp >= $2"
                        	." AND m.proxy_log_timestamp <= $3".$whereOws.")"
                    		.")";
				break;
			case "wfs":
 				$sql = "DELETE FROM mb_proxy_log"
                    		." WHERE log_id in("
                        	." SELECT m.log_id"
                        	." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
                        	." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$4"
                        	." AND m.fkey_mb_user_id = $1 AND  m.proxy_log_timestamp >= $2"
                        	." AND m.proxy_log_timestamp <= $3".$whereOws.")"
                    		.")";
				break;
		}
            $result = db_prep_query($sql,$v,$t);
            $resnum = pg_affected_rows($result);

            if($resnum > 0){
                if($this->owsId !== null && $this->owsId != ""){
                    $this->resultMessage = $resnum." Log-Datensaetze (DienstId :".$this->owsId.", UserId: ".$this->userId.") wurden erfolgreich geloescht.";
                } else {
                    $this->resultMessage = $resnum." Log-Datensaetze (UserId: ".$this->userId.") wurden erfolgreich geloescht.";
                }
            } else {
                if($this->owsId !== null && $this->owsId != ""){
                    $this->resultMessage = "Kein Log-Datensatz (DienstId: ".$this->owsId.", UserId: ".$this->userId.") wurde geloescht.";
                } else {
                    $this->resultMessage = "Kein Log-Datensatz (UserId: ".$this->userId.") wurde geloescht.";
                }
            }
        }
    }
    
    private function readResult($result){
    	$offsetPixel = null;
    	$offsetPrice = null;
    	$offsetFeatures = null;
    	$sumPixel = 0;
    	$sumPrice = 0;
    	$sumFeatures = 0;
    	if ($result != false) {
    		$num_fields = pg_num_fields($result);
    		for ( $i = 0; $i < $num_fields; $i++ ){
    			$field_name = pg_field_name($result , $i);
    			$this->resultHeader[] = $field_name;
    			// keep offsets of prixel and price row
    			if($field_name === "pixel")        $offsetPixel = $i;
    			else if($field_name === "price")   $offsetPrice = $i;
			else if($field_name === "features")   $offsetFeatures = $i;
    		}
    	}
    	$i = 0;
    	while($row = db_fetch_row($result)){
    		foreach( $row as $key=>$value){
    			$this->resultData[$i][] = $value;
    			    
    			if($key == $offsetPixel)        $sumPixel += $value;
    			else if($key == $offsetPrice)   $sumPrice += $value;
   			else if($key == $offsetFeatures)   $sumFeatures += $value;
    		}
    		$i++;
    	}
    
    	if(!is_null($offsetPixel) || !is_null($offsetPrice) || !is_null($offsetFeatures)) {
    		for($j=0;$j<$num_fields;$j++) {
    			if($j == $offsetPixel && !is_null($offsetPixel)) {
    				$this->resultData[$i][] = (string)$sumPixel;
    			}
    			else if($j == $offsetPrice && !is_null($offsetPrice)) {
    				$this->resultData[$i][] = (string)$sumPrice;
    			}
    			else if($j == $offsetFeatures && !is_null($offsetFeatures)) {
    				$this->resultData[$i][] = (string)$sumFeatures;
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
        $offsetFeatures = null;
        $sumPixel = 0;
        $sumPrice = 0;
    	$sumFeatures = 0;
        if ($result != false) {
            $num_fields = pg_num_fields($result);
            for ( $i = 0; $i < $num_fields; $i++ ){
                $field_name = pg_field_name($result , $i);
                $this->resultHeaderDisplay[] = $field_name;

                // keep offsets of prixel and price row
                if($field_name === "pixel")        $offsetPixel = $i;
                else if($field_name === "price")   $offsetPrice = $i;
                else if($field_name === "features")   $offsetFeatures = $i;
            }
        }
        $i = 0;
        while($row = db_fetch_row($result)){
        foreach( $row as $key=>$value){
    			$this->resultDataDisplay[$i][] = $value;
    			    
    			if($key == $offsetPixel)        $sumPixel += $value;
    			else if($key == $offsetPrice)   $sumPrice += $value;
    			else if($key == $offsetFeatures)   $sumFeatures += $value;
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
        		else if($j == $offsetFeatures && !is_null($offsetFeatures)) {
        			$this->resultDataDisplay[$i][] = (string)$sumFeatures;
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
            $header .= '"'.$colname.'"'.OwsLogCsv::$SEPARATOR_VALUE;
        }

        foreach($this->resultData as $row){
            $line = '';
            foreach( $row as $value){
                if ($value == null || $value == ""){
                    $value = "".OwsLogCsv::$SEPARATOR_VALUE;
                } else {
//                    if(CHARSET == 'UTF-8'){
//                        $value = utf8_encode($value);
//                    }
                    $value = str_replace('"', '""', $value);
                    $value = '"'.$value.'"'.OwsLogCsv::$SEPARATOR_VALUE;
                }
                $line .= $value;
            }
            $data .= trim($line).OwsLogCsv::$SEPARATOR_ROW;
        }
        return $header.OwsLogCsv::$SEPARATOR_ROW.$data;
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
    				"limit" => OwsLogCsv::$LIMIT_INT);
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
	            "limit" => OwsLogCsv::$LIMIT_INT);
    	}
    }
}
?>
