<?php

namespace utils\common;

function sanitizeArgument ($argument) {
    return preg_replace("/\W/","", $argument);
}

?>

