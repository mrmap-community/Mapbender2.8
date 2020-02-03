<?php
//Basic configuration of mapserver client
require_once(dirname(__FILE__)."/../../../conf/mobilemap.conf");
$js = "";
for ($i==0; count($layer);$i++) {
	$js .= layer[$i];
}
//header - javascript

echo $js;
?>
