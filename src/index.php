<?php

define('PROJECT_ROOT_PATH', __DIR__ . '/');
define('ERROR_SEVERITY_LEVEL', Array(
    0 => "DEBUG",
    E_ERROR => "ERROR",
    E_WARNING => "WARNING",
    E_PARSE => "PARSE",
    E_NOTICE => "NOTICE",
    E_CORE_ERROR => "CORE_ERROR",
    E_CORE_WARNING => "CORE_WARNING",
    E_COMPILE_ERROR => "COMPILE_ERROR",
    E_COMPILE_WARNING => "COMPILE_WARNING",
    E_USER_ERROR => "USER_ERROR",
    E_USER_WARNING => "USER_WARNING",
    E_USER_NOTICE => "USER_NOTICE",
    E_STRICT => "STRICT",
    E_RECOVERABLE_ERROR => "RECOVERABLE_ERROR",
    E_DEPRECATED => "DEPRECATED",
    E_USER_DEPRECATED => "USER_DEPRECATED",
    E_ALL => "E_ALL"
));

require_once "utils/common.php";
use utils\common\sanitizeArgument;

function globalExeptionHandler(Throwable $ex) {

    header_remove('Set-Cookie');
    header("HTTP/1.1 500 Internal Server Error", true);
    header("Content-Type: application/json;charset=UTF-8");

    echo(json_encode((object) Array(
        'error' => $ex->__toString()
    )));
}
set_exception_handler('globalExeptionHandler');

function message(string $log, mixed $thing = '', $severity = 0) {
    $common = CommonController::getInstance();
    // $level = ERROR_SEVERITY_LEVEL[$severity] || $severity;
    $level = ERROR_SEVERITY_LEVEL[$severity];

    $additionnalLog = gettype($thing) === 'string' ? $thing : json_encode($thing);

    $message = "[$level] $log $additionnalLog";
    if ($common) {
        $common->addDebugMessage($message);
    } else {
        echo '<p>' . $message . '</p>';
    }
}

function genericErrorHandler($severity, $message, $filename, $lineno) {
    message("$message on $filename. Line $lineno", null, $severity);
}
set_error_handler('genericErrorHandler');


require_once PROJECT_ROOT_PATH . "controllers/common.php";
$common = CommonController::getInstance(); // Singleton
$data = $common->getUriSegmentsData();

require_once PROJECT_ROOT_PATH . "controllers/procedures.php";
$procedures = new Procedures();
$result = $procedures->getProcedureFromData($data);
$result->print();

?>


