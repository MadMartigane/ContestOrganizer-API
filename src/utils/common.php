<?php

namespace utils\common;

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}


function sanitizeArgument ($argument) {
    return preg_replace("/\W/","", $argument);
}

?>

