<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

try {
    require_once PROJECT_ROOT_PATH . "controllers/common.php";
    require_once PROJECT_ROOT_PATH . "controllers/procedures.php";

    $common = new CommonController();
    $data = $common->getUriSegmentsData();

    $procedures = new Procedures();
    $ok = $procedures->ok($data);
    $ok->print();
} catch (Exception $ex) {

    header_remove('Set-Cookie');
    header("HTTP/1.1 400 Bad Request", true);
    // header("HTTP/1.1 500 Internal Server Error", true);

    echo($ex->errorMessage());
}

?>

