<?php

require_once "utils/common.php";
use utils\common\sanitizeArgument;

class CommonController {

    /**
     * __call magic method.
     */
    public function __call($name, $arguments) {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }

    /**
     * Get URI elements.
     *
     * @return array
     */
    protected function getUriSegments() {
        return explode( '/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }

    /**
     * Get querystring params.
     *
     * @return array
     */
    protected function getQueryStringParams() {
        return parse_str($_SERVER['QUERY_STRING'], $query);
    }

    /**
     * Send API output.
     *
     * @param mixed $data
     * @param string $httpHeader
     */
    public function sendOutput(string $code, $httpHeaders=array()) {

        header_remove('Set-Cookie');

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }

        echo $code;
        exit;
    }

    public function getUriSegmentsData() {

        $uri = $this->getUriSegments();
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

            if (!$requestAction) {
                $requestAction = utils\common\sanitizeArgument($value);
                continue;
            }

            if (!$requestSubject) {
               $requestSubject = utils\common\sanitizeArgument($value);
               continue;
            }

            if (!$requestOption) {
                $requestOption = utils\common\sanitizeArgument($value);
                continue;
            }

        }

        return (object) [
            "action" => $requestAction,
            "subject" => $requestSubject,
            "option" => $requestOption,
            "uri" => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        ];
    }
}

?>

