<?php

namespace utils\common;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}


function sanitizeArgument ($argument) {
    return preg_replace("/\W/","", $argument);
}

function saveJsonOnFile(mixed $json, string $filePath) {
    $string = json_encode($json);
    message("Save $string");
    message("On file $filePath");
    $fileStream = fopen($filePath,'w');
    if (!$fileStream) {
        return (object) Array('type' => 'error', 'message' => "Unable to open file $filePath");
    }

    $written = fwrite($fileStream, $string);
    fclose($fileStream);
    if (!$written) {
        return (object) Array('type' => 'error', 'message' => "Unable to write on file $filePath");
    }

    return (object) Array('type' => 'success', 'message' => "Written bites $written");
}

function getPostData () {
    return json_decode(file_get_contents('php://input'), true);
}

?>

