<?php
require_once(dirname(__FILE__) . "/../../../../conf/mobilemap2.conf");
require_once(dirname(__FILE__) . "/../../../classes/class_connector.php");

//calculate BBOX from position
$UNSAFE_coord = explode(',', $_GET["coord"]);
$bbox = (string) ((float) $UNSAFE_coord[0] - 50.0) . "," . (string) ((float) $UNSAFE_coord[1] - 50.0) . "," . (string) ((float) $UNSAFE_coord[0] + 50.0) . "," . (string) ((float) $UNSAFE_coord[1] + 50.0);

$featureInfoRequestPart = '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SERVICE=WMS&LAYERS=' . MOBILEMAP2_HEIGHT_REQUEST_INFO_LAYER;
$featureInfoRequestPart .= '&QUERY_LAYERS=' . MOBILEMAP2_HEIGHT_REQUEST_INFO_LAYER . '&WIDTH=101&HEIGHT=101&SRS=EPSG:' . MOBILEMAP2_HEIGHT_REQUEST_EPSG;
$featureInfoRequestPart .= '&BBOX=' . $bbox . '&STYLES=&FORMAT=image/png';
$featureInfoRequestPart .= '&INFO_FORMAT=application/vnd.ogc.gml&EXCEPTIONS=application/vnd.ogc.se_inimage&X=51&Y=51&FEATURE_COUNT=1&';

$url = MOBILEMAP2_HEIGHT_REQUEST_INFO_URL . $featureInfoRequestPart;
$featureInfoConnector = new connector($url);
$gml = $featureInfoConnector->file;

try {
    $gmlObject = new SimpleXMLElement($gml);
if ($gmlObject === false) {
    foreach (libxml_get_errors() as $error) {
        $e = new mb_exception($error->message);
    }
        throw new Exception('Cannot parse GML from featureInfo in mobile Client!');
    }
} catch (Exception $e) {
    $e = new mb_exception($e->getMessage());
}

if ($gmlObject !== false) {
    $gmlObject->registerXPathNamespace("gml", "http://www.opengis.net/gml");
    $gmlObject->registerXPathNamespace("xlink", "http://www.w3.org/1999/xlink");
    $gmlObject->registerXPathNamespace("xsi", "http://www.w3.org/2001/XMLSchema-instance");
    $x = $gmlObject->xpath('/msGMLOutput/mydhm_layer/mydhm_feature/value_0');
    $hoehe = $x[0];
    echo "Höhe: ~" . $hoehe . " [m]";
} else {
    echo "Kein Höhe gefunden!";
}