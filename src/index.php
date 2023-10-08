<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

function exeptionHandler(Throwable $ex) {

    header_remove('Set-Cookie');
    header("HTTP/1.1 500 Internal Server Error", true);

    echo($ex->__toString());
}

set_exception_handler('exeptionHandler');

function message(string $log) {
    echo '<p>' . $log . '</p>';
}

message('requires…');
require_once PROJECT_ROOT_PATH . "controllers/common.php";
require_once PROJECT_ROOT_PATH . "controllers/procedures.php";

message('common…');
$common = new CommonController();
$data = $common->getUriSegmentsData();

message('procedures…');
$procedures = new Procedures();
$result = $procedures->getProcedureFromData($data);
$result->print();

?>
