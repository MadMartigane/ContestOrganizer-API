<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

require_once "utils/common.php";
use utils\common\sanitizeArgument;

function exeptionHandler(Throwable $ex) {

    header_remove('Set-Cookie');
    header("HTTP/1.1 500 Internal Server Error", true);
    header("Content-Type: application/json;charset=UTF-8");

    echo(json_encode((object) Array(
        'error' => $ex->__toString()
    )));
}
set_exception_handler('exeptionHandler');

function message(string $log, mixed $thing = '') {
    $common = CommonController::getInstance();

    $additionnalLog = gettype($thing) === 'string' ? $thing : json_encode($thing);

    if ($common) {
        $common->addDebugMessage($log . $additionnalLog);
    } else {
        echo '<p>' . $log . $additionnalLog . '</p>';
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


