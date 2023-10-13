<?php

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class CommonController {

    private static $instance = null;
    private array $debugMessages;

    private function __construct() {
        $this->debugMessages = Array();
    }

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
     * @param array $httpHeaders
     */
    public function sendOutput(string $code, array $httpHeaders=array()) {

        header_remove('Set-Cookie');

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }

        if (count($this->debugMessages)) {
            $data = json_decode($code);

            $data->debug = $this->debugMessages;
            $code = json_encode($data);
        }

        echo $code;
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

    public function addDebugMessage(string $message) {
        array_push($this->debugMessages, $message);
    }

    public function getDebugMessages() {
        return $this->debugMessages;
    }

    // The object is created from within the class itself
    // only if the class has no instance.
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new CommonController();
        }

        return self::$instance;
    }

}

?>

