<?php
require_once(dirname(__FILE__) . "/../core/globalSettings.php");
require_once(dirname(__FILE__) . "/../http/classes/class_user.php");

$resultObject->success = false;

header("Content-type: application/json");

if (isset($_REQUEST["userId"]) & $_REQUEST["userId"] != "") {
    $testMatch = $_REQUEST["userId"];
    $pattern = '/^[0-9]*$/';
    if (!preg_match($pattern,$testMatch)){
        $resultObject->error->message = 'Parameter userId is not valid (integer).';
        echo json_encode($resultObject);
        die();
    }
    $userId = (integer)$testMatch;
    $testMatch = NULL;
} else {
    $resultObject->error->message = 'Parameter userId is not valid (integer).';
    echo json_encode($resultObject);
    die();
}

if (!in_array(Mapbender::session()->get("mb_user_id"), array(1, 5299))) {
    $resultObject->error->message = 'Requesting user is not allowed to read information.';
    echo json_encode($resultObject);
    die();
} else {
    $user = new User($userId);
    $userInfo = $user->getFields();
    if (empty($userInfo['name']) && empty($userInfo['email'])) {
        $resultObject->error->message = "No user found for this ID!";
        $resultObject->success = false;
        echo json_encode($resultObject);
        die();
    } else {
        $resultObject->result = $userInfo;
        $resultObject->success = true;
        echo json_encode($resultObject);
        die();
    }
}

?>