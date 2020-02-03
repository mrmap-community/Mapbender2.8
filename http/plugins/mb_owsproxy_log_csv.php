<?php

require_once dirname(__FILE__) . "/../../core/globalSettings.php";

$LIMIT_INT = OWS_LOG_EXPORT_LIMIT;

if(isset($_REQUEST['userGuiId'])) {
    $id = (integer)$_REQUEST['userGuiId'];

    if($id === -1) {
        $html .= '<b>Bitte einen Benutzer auswählen</b>';
    } else {
        $result = db_prep_query(
            "SELECT fkey_gui_id FROM gui_mb_user WHERE fkey_mb_user_id = $1", 
            $id, 
            array('i'));        
    }

    $html .= "<table>";
    while($row = db_fetch_assoc($result)) {
        $html .= "<tr><td>" . $row['fkey_gui_id'] . "</td></tr>";
    }
    $html .= "</table>";

    die($html);
}

if($_REQUEST['action'] == "getForm"){
    //$e = new mb_exception($_REQUEST['serviceType']);
    switch ($_REQUEST['serviceType']) {
	case "wms":
   	    $sql = "SELECT DISTINCT u.mb_user_id, w.wms_id,w.wms_title,u.mb_user_name"
   			." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
   			." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1)"
   					." INNER JOIN mb_user u ON(m.fkey_mb_user_id = u.mb_user_id)"
   							." ORDER BY u.mb_user_name ASC, u.mb_user_id ";
	    break;
	case "wfs":
   	    $sql = "SELECT DISTINCT u.mb_user_id, w.wfs_id,w.wfs_title,u.mb_user_name"
   			." FROM mb_proxy_log AS m INNER JOIN wfs AS w ON"
   			." (m.fkey_wfs_id = w.wfs_id AND w.wfs_owner=$1)"
   					." INNER JOIN mb_user u ON(m.fkey_mb_user_id = u.mb_user_id)"
   							." ORDER BY u.mb_user_name ASC, u.mb_user_id ";
            break;	
    }
    $mb_user_id = $_SESSION['mb_user_id'];
   	$v = array($mb_user_id);
   	$t = array('i');
   	$users = array();
   	$services = array();
   	/*$sql = "SELECT DISTINCT u.mb_user_id, w.wms_id,w.wms_title,u.mb_user_name"
   			." FROM mb_proxy_log AS m INNER JOIN wms AS w ON"
   			." (m.fkey_wms_id = w.wms_id AND w.wms_owner=$1)"
   					." INNER JOIN mb_user u ON(m.fkey_mb_user_id = u.mb_user_id)"
   							." ORDER BY u.mb_user_name ASC, u.mb_user_id ";*/
   	$result = db_prep_query($sql,$v,$t);
   	while($row = db_fetch_assoc($result)){
   		$users[$row['mb_user_id']] = $row['mb_user_name'];
		switch ($_REQUEST['serviceType']) {
			case "wms":
				$services[$row['wms_id']] = $row['wms_title'];
				break;
			case "wfs":
				$services[$row['wfs_id']] = $row['wfs_title'];
				break;
		}
   	}
    


    $form = '<div class="form">
    <!--<input name="serviceType" id="serviceType" value="wms" type="hidden" />-->
    <input name="format" id="format" value="json" type="hidden" />
    <fieldset id="owsproxy-log-query" style="float:left;">
        <legend>OwsProxy Logs Abfrage</legend>
            <div class="field">
                <label for="serviceType">Service Typ:</label>
                <select name="serviceType" id="serviceType">';
		switch ($_REQUEST['serviceType']) {
			case "wms":
				$form .= '<option value="wms">WMS</option>
					  <option value="wfs">WFS</option>';
				break;
			case "wfs":
				$form .= '<option value="wfs">WFS</option>
					  <option value="wms">WMS</option>';
				break;
		}
    $form .=           '</select>
            </div>
            <div class="field">
                <label for="function">Abfrageart:</label>
                <select name="function" id="function">
                    <option value="getServiceLogs">getServiceLogs</option>
                    <option value="listServiceLogs">listServiceLogs</option>
                    <option value="getSum">getSum</option>
                    <!--option value="deleteServiceLogs">deleteServiceLogs</option-->
                </select>
            </div>
            <div class="field">
                <label for="listType">Listtyp:</label>
                <select name="listType" id="listType">
                    <option value="user">Benutzer</option>
                    <option value="service">Dienst</option>
                </select>
            </div>
            <div class="field">
                <label for="withContactData">mit Kontaktdaten:</label>
                <input name="withContactData" id="withContactData" type="checkbox"/>
            </div>
            <div class="field">
                <label for="userId">Benutzername:</label>
                <select name="userId" id="userId">
                    <option value=""></option>
                    <!--option value="-1">alle</option-->';
                    foreach ($users as $key => $value) {
                        $form .= '<option value="'.$key.'">'.$value.'</option>';
                    }

            $form .= '</select>
            </div>
            <div class="field">
                <label for="serviceId">Dienst:</label>
                <select name="serviceId" id="serviceId">
                    <option value=""></option>
                    <option value="-1">alle</option>';
                    foreach ($services as $key => $value) {
                        $form .= '<option value="'.$key.'">'.$value.'</option>';
                    }
            $form .= '</select>
            </div>
            <div class="field">
                <label for="timeFrom">Zeit von:</label>
                <input type="text" name="timeFrom" id="timeFrom"/>
            </div>
            <div class="field">
                <label for="timeTo">Zeit bis zu:</label>
                <input type="text" name="timeTo" id="timeTo"/>
            </div>
            <!--div class="field">
                <label for="csv">CSV</label>:
                <input type="radio" name="format" value="csv"  id="csv" />
                <label for="json">JSON</label>:
                <input type="radio" name="format" value="json" id="json" checked="checked" />
            </div-->
            <div class="field">
                <input type="button" value="Abfrage ausführen" id="button-logs-query"/>
            </div>
            <iframe id="csv-download" width="0" height="0" style="display:none"></iframe>
        </div>
    </fieldset>
    
    <fieldset style="margin-left: 200px;width: 300px;">
        <legend>User / GUI</legend>
            <div class="field">
                <label for="userId">Benutzername:</label>
                <select id="user_gui">
                    <option value=""></option>
                    <!--option value="-1">alle</option-->';

                    foreach ($users as $key => $value) {
                        $form .= '<option value="'.$key.'">'.$value.'</option>';
                    }

            $form .= '</select>
            </div>
            <div id="user_gui_result"></div>
    </fieldset><br/>

    <fieldset id="owsproxy-log-result" style="clear:both;">
        <legend id="queryResult" data-title="OwsProxy Logs Abfrageergebnis:" data-count="XXX Treffer" data-limit="die Ausgabe ist auf XXX Treffer begrenzt.">OwsProxy Logs Abfrageergebnis</legend>
        <div id="result-area">
        <div id="result-operation"><input type="button" value="Ergebnis als CSV laden" id="button-csv-download"/> <input type="button" value="Ergebnisdaten löschen" id="button-logs-delete"/></div>
        <div id="result"></div></div>
    </fieldset>';
            
    $jsonOutput = array("form"=>$form,"error"=>"","message"=>"");
    if(count($users) == 0 || count($services) == 0){
        $jsonOutput["message"] = "In der OwsProxy-Tabelle ist kein Datensatz vorhanden. Keine Abfrage ist möglich.";
    }
    header("ContentType: application/json");
    die(json_encode($jsonOutput));
} else {
    require_once dirname(__FILE__) . "/../classes/class_owsproxy_log.php";
    $function = isset($_REQUEST['function']) ? $_REQUEST['function'] : null;#getServiceLogs,deleteServiceLogs,listServiceLogs
    $listType = isset($_REQUEST['listType']) ? $_REQUEST['listType'] : null;#service,user

    $serviceType = isset($_REQUEST['serviceType']) ? $_REQUEST['serviceType'] : null;#wms, wfs

    $userId = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : null;#XXX
    $serviceId = isset($_REQUEST['serviceId']) ? $_REQUEST['serviceId'] : null;# XXX
    $timeFrom = isset($_REQUEST['timeFrom']) ? $_REQUEST['timeFrom'] : null;#
    $timeTo = isset($_REQUEST['timeTo']) ? $_REQUEST['timeTo'] : null;#
    $withContactData = isset($_REQUEST['withContactData']) ? $_REQUEST['withContactData'] : null;# 1,

    if ($serviceType === null) {
	$e = new mb_exception("Parameter serviceType is missing!");
        die ("Der Parameter 'serviceType' wurde nicht uebergeben.");
    }
    //begin serviceType handling**********
    $mb_user_id = $_SESSION['mb_user_id'];
    $export = "export dummy";
    switch ($serviceType) {
        case "wms":
	    $serviceLog = OwsLogCsv::create($mb_user_id, $function, $userId, $serviceId,
                $listType, $timeFrom, $timeTo, $withContactData, $serviceType);
	    $serviceLog->handle();
            switch ($_REQUEST['action']) {
                case "getCsv":            
		    header("Content-Type: text/csv; charset=".CHARSET);
            	    header("Content-Disposition: attachment; filename=csv_export.csv");
            	    header("Pragma: no-cache");
            	    header("Expires: 0");
		    $export = $serviceLog->getAsCsv();
                    break;
                case "getJson":
		    $export = json_encode($serviceLog->getAsArray($function));
                    break;
            }
            break;
        case "wfs":
	    $serviceLog = OwsLogCsv::create($mb_user_id, $function, $userId, $serviceId,
                $listType, $timeFrom, $timeTo, $withContactData, $serviceType);
	    $serviceLog->handle();
            switch ($_REQUEST['action']) {
                case "getCsv":            
		    header("Content-Type: text/csv; charset=".CHARSET);
            	    header("Content-Disposition: attachment; filename=csv_export.csv");
            	    header("Pragma: no-cache");
            	    header("Expires: 0");
		    $export = $serviceLog->getAsCsv();
                    break;
                case "getJson":
		    $export = json_encode($serviceLog->getAsArray($function));
                    break;
            }
            break;
        default:
	    die ("Der 'serviceType' ".$serviceType." ist nicht unterstuetzt.");
            break;
    }
    print $export;
    die();
    //end serviceType handling**********

//$e = new mb_exception("servicetype not supported");
   /* if($serviceType == "wms"){

        $mb_user_id = $_SESSION['mb_user_id'];
        if($_REQUEST['action'] == "getCsv"){
            $wmslog = OwsLogCsv::create($mb_user_id, $function, $userId, $serviceId,
                $listType, $timeFrom, $timeTo, $withContactData, $serviceType);
            $wmslog->handle();
            header("Content-Type: text/csv; charset=".CHARSET);
            header("Content-Disposition: attachment; filename=csv_export.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            $csv = $wmslog->getAsCsv();
            print $csv;
            die();
        } else if($_REQUEST['action'] == "getJson"){
            $wmslog = OwsLogCsv::create($mb_user_id, $function, $userId, $serviceId,
                $listType, $timeFrom, $timeTo, $withContactData, $serviceType);
            $wmslog->handle();
            print json_encode($wmslog->getAsArray($function));
            die();
        }
    } else {
	$e = new mb_exception("servicetype not supported");
        die ("Der 'serviceType'".$serviceType." ist nicht unterstuetzt.");
    }*/
    //end serviceType handling**********
}
?>
