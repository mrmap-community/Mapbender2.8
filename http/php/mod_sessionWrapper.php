<?php
require_once(dirname(__FILE__) . "/../../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../classes/class_user.php");
$hostName = $_SERVER['HTTP_HOST'];
$operation = "get";
$key = "mb_user_id";
$value = null;
$allowedOperations = array("get", "set");
$allowedKeys = array("mb_user_id", "GML", "dsgvo", "preferred_gui");

$resultObj['result'] = '';
$resultObj['success'] = false;
$resultObj['message'] = 'no message';

//if (!($hostName == '127.0.0.1')) {
if (!($hostName == 'localhost' or $hostName == '127.0.0.1')) {
    $resultObj['message'] = 'hostName not allowed - only local connections possible (localhost,127.0.0.1)';
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    die();
}

if (isset($_REQUEST["sessionId"]) & $_REQUEST["sessionId"] != "") {
    //echo "<br>Requested sessionId: ".$_REQUEST["sessionId"]."<br>";
} else {
    $resultObj['message'] = 'No sessionId given - please give parameter!';
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    die();
}
$existSession = Mapbender::session()->storageExists($_REQUEST["sessionId"]);
if ($existSession) {
    $e = new mb_notice("storage exists");
    //grabb session!
    session_id($_REQUEST["sessionId"]);
} else {
    $e = new mb_exception("storage does not exist!");
    $resultObj['message'] = 'Requested session does not exists on server - please use existing identifier!';
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    die();
}
//parse operation
if (isset($_REQUEST["operation"]) & $_REQUEST["operation"] != "") {
    $testMatch = $_REQUEST["operation"];
    if (!in_array($testMatch, $allowedOperations)) {
        $resultObj['message'] = 'Parameter operation is not valid ' . implode(',', $allowedOperations);
        $resultObj['result'] = null;
        echo json_encode($resultObj);
        die();
    }
    $operation = $testMatch;
    $testMatch = NULL;
} else {
    $resultObj['message'] = "Parameter operation not set - please set either " . implode(' or ', $allowedOperations);
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    die();
}
//parse operation
if (isset($_REQUEST["key"]) & $_REQUEST["key"] != "") {
    $testMatch = $_REQUEST["key"];
    if (!in_array($testMatch, $allowedKeys)) {
        $resultObj['message'] = 'Parameter key is not valid ' . implode(',', $allowedKeys);
        $resultObj['result'] = null;
        echo json_encode($resultObj);
        die();
    }
    $key = $testMatch;
    $testMatch = NULL;
} else {
    $resultObj['message'] = 'Parameter key not set - please set either ' . implode(' or ', $allowedKeys);
    $resultObj['result'] = null;
    echo json_encode($resultObj);
    die();
}
switch ($operation) {
    case "get":
        $resultObj['success'] = true;
        $resultObj['message'] = 'Extracted session variable successfully!';
        $resultObj['result']->key = $key;
        $resultObj['result']->value = Mapbender::session()->get($key);
        echo json_encode($resultObj);
        die();
        break;
    case "set":
        switch ($key) {
            case "GML":
                //validate gml!
                //parse operation
                if (isset($_REQUEST["value"]) & $_REQUEST["value"] != "") {
                    $testMatch = $_REQUEST["value"];
                    /*if (!in_array($testMatch, $allowedKeys)){
                           $resultObj['message'] = 'Parameter key is not valid '.implode(',', $allowedKeys);
                           $resultObj['result'] = null;
                           echo json_encode($resultObj);
                       die();
                    }*/
                    $value = urldecode($testMatch);
                    $testMatch = NULL;
                } else {
                    $resultObj['message'] = 'Parameter value for key ' . $key . ' not given!';
                    $resultObj['result'] = null;
                    echo json_encode($resultObj);
                    die();
                }
                if ($value == 'dummyPolygon') {
                    $bbox = "6,48,8,51";
                    $newBbox = explode(",", $bbox);
                    $GML = '<FeatureCollection xmlns:gml="http://www.opengis.net/gml"><boundedBy><Box srsName="EPSG:4326">';
                    $GML .= "<coordinates>" . $newBbox[0] . "," . $newBbox[1] . " " . $newBbox[2];
                    $GML .= "," . $newBbox[3] . "</coordinates></Box>";
                    $GML .= '</boundedBy><featureMember><gemeinde><title>BBOX</title><the_geom><MultiPolygon srsName="EPSG:';
                    $GML .= "4326" . '"><polygonMember><Polygon><outerBoundaryIs><LinearRing><coordinates>';
                    $GML .= $newBbox[0] . "," . $newBbox[1] . " " . $newBbox[2] . ",";
                    $GML .= $newBbox[1] . " " . $newBbox[2] . "," . $newBbox[3] . " ";
                    $GML .= $newBbox[0] . "," . $newBbox[3] . " " . $newBbox[0] . "," . $newBbox[1];
                    $GML .= "</coordinates></LinearRing></outerBoundaryIs></Polygon></polygonMember></MultiPolygon></the_geom></gemeinde></featureMember></FeatureCollection>";
                    Mapbender::session()->set('GML', $GML);
                    $resultObj['success'] = true;
                    $resultObj['message'] = 'Dummy GML MultiPolygon written into session!';
                    $resultObj['result'] = null;
                    echo json_encode($resultObj);
                    die();
                }
                Mapbender::session()->set('GML', $value);
                $resultObj['success'] = true;
                $resultObj['message'] = 'GML written into session!';
                $resultObj['result'] = null;
                echo json_encode($resultObj);
                die();
                break;
            case "dsgvo":
                if (isset($_REQUEST["value"]) & $_REQUEST["value"] != "") {
                    $testMatch = $_REQUEST["value"];
                    $value = urldecode($testMatch);
                    if ($value === "true") {
						Mapbender::session()->set($key, "yes");
						$resultObj['success'] = true;
						$resultObj['message'] = 'Set dsgvo to yes!';
						$resultObj['result'] = null;
						echo json_encode($resultObj);
						die();
					} else {
						Mapbender::session()->set($key, "no");
						$resultObj['success'] = true;
						$resultObj['message'] = 'Set dsgvo to no!';
						$resultObj['result'] = null;
						echo json_encode($resultObj);
						die();
					}
                }
                break;
            case "preferred_gui":
                if (isset($_REQUEST["value"]) & $_REQUEST["value"] != "") {
                    $testMatch = $_REQUEST["value"];
                    $value = urldecode($testMatch);
                    Mapbender::session()->set($key, $value);
                    $resultObj['success'] = true;
                    $resultObj['message'] = 'Set preferred_gui to' . $value . '!';
                    $resultObj['result'] = null;
                    echo json_encode($resultObj);
                    die();
                }
            #		break;
            default:
                $resultObj['message'] = 'Not allowed to set key: ' . $key . ' via http!';
                $resultObj['result'] = null;
                echo json_encode($resultObj);
                break;
        }
        break;
}
?>
