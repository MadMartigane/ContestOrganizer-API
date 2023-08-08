<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

require_once "utils/common.php";
use utils\common\sanitizeArgument;


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = explode( '/', $uri );

$requestSubject = null;
$requestAction = null;
$requestOption = null;
$indexReached = false;

foreach ($uri as &$value) {
    if ($value == "index.php") {
        $indexReached = true;
        continue;
    }

    if (!$indexReached) { continue; }

    if (!$requestSubject) {
        $requestSubject = utils\common\sanitizeArgument($value);
        continue;
    }

    if (!$requestAction) {
        $requestAction = utils\common\sanitizeArgument($value);
        continue;
    }

    if (!$requestOption) {
        $requestOption = utils\common\sanitizeArgument($value);
        continue;
    }

}


require PROJECT_ROOT_PATH . "controllers/common.php";

$data = (object) [
    "action" => $requestAction,
    "subject" => $requestSubject,
    "option" => $requestOption,
    "uri" => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
];

$ctrl = new CommonController();
$ctrl->sendOutput(json_encode($data), array("Content-Type: application/json"));

?>

