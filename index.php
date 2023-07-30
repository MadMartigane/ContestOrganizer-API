<?php

define("PROJECT_ROOT_PATH", __DIR__ . "/");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = explode( '/', $uri );


require PROJECT_ROOT_PATH . "controllers/common.php";

$data = (object) [
    "tets" => "hello",
    "uri" => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
];

$ctrl = new CommonController();
$ctrl->sendOutput(json_encode($data), array("Content-Type: application/json"));

?>

