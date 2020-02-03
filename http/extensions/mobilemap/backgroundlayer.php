<?php
//Basic configuration of jquery mobile client
require_once(dirname(__FILE__)."/../../../conf/mobilemap.conf");
$js = "";
$js .= $constants;
for ($i = 0; $i < count($layer); $i++) {
	$js .= $layer[$i];
}
//headers - javascript
echo $js;
?>
