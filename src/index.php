<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

// REQUIRES
require_once PROJECT_ROOT_PATH . "controllers/procedures.php";

require_once "utils/common.php";
use utils\common\sanitizeArgument;

// CONSTANTS


// VARIABLES
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


$data = (object) [
    "action" => $requestAction,
    "subject" => $requestSubject,
    "option" => $requestOption,
    "uri" => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
];

$procedures = new Procedures();
$ok = $procedures->ok($data);
$ok->print();

?>

