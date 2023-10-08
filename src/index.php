<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

function exeptionHandler(Throwable $ex) {

    header_remove('Set-Cookie');
    header("HTTP/1.1 500 Internal Server Error", true);
    header("Content-Type: application/json;charset=UTF-8");

    echo(json_encode((object) Array(
        'error' => $ex->__toString()
    )));
}
set_exception_handler('exeptionHandler');

function message(string $log) {
    $common = CommonController::getInstance();

    if ($common) {
        $common->addDebugMessage($log);
    } else {
        echo '<p>' . $log . '</p>';
    }
}

require_once PROJECT_ROOT_PATH . "controllers/common.php";
$common = CommonController::getInstance(); // Singleton
$data = $common->getUriSegmentsData();

message('procedures…');
require_once PROJECT_ROOT_PATH . "controllers/procedures.php";
$procedures = new Procedures();
$result = $procedures->getProcedureFromData($data);
$result->print();

?>


