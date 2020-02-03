<?php
require_once dirname(__FILE__) . "/../php/mb_validateSession.php";
require_once dirname(__FILE__) . "/classes/factoryClasses.php";
$gui_id = Mapbender::session()->get("mb_user_gui");
//select all element_ids from database, if $_REQUEST['e_id'] is in this list - use this e_id for getting php_var
$sql = "SELECT e_id FROM gui_element WHERE fkey_gui_id = $1";
$v = array($gui_id);
$t = array("s");
$res = db_prep_query($sql, $v, $t);
$e_id = false;
while ($row = db_fetch_array($res)){
	if ($row['e_id'] == $_REQUEST['e_id']) {
		$e_id = $row['e_id'];
		break;	
	}
}

if ($e_id != false) {
	include dirname(__FILE__) . "/../include/dyn_php.php";
}
$pf = new mbPdfFactory();
$confFile = basename($_REQUEST["printPDF_template"]);
if (!preg_match("/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9]+)$/", $confFile) || 
	!file_exists($_REQUEST["printPDF_template"])) {

	$errorMessage = _mb("Invalid configuration file");
	echo htmlentities($errorMessage, ENT_QUOTES, CHARSET);
	$e = new mb_exception($errorMessage);
	die;
}

$pdf = $pf->create($_REQUEST["printPDF_template"]);

//element vars of print
$pdf->unlinkFiles = $unlink;
$pdf->logRequests = $logRequests;
$pdf->logType = $logType;

if (isset($printLegend)){
    $pdf->printLegend = $printLegend;
}else{
    $pdf->printLegend = 'true';
}

if (isset($legendColumns)){
    $pdf->legendColumns = $legendColumns;
}else{
    $pdf->legendColumns = '1';
}

$pdf->render();
try {
	$pdf->save();
}
catch (Exception $e) {
	new mb_exception($e->message);
	die;
}
if ($secureProtocol == "true"){
    print $pdf->returnAbsoluteUrl(true);
}else{
    print $pdf->returnAbsoluteUrl();
}
?>
