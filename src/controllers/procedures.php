<?php

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

// REQUIRES
require_once PROJECT_ROOT_PATH . 'controllers/common.php';


class Headers {

    private $supportedContentTypes;
    private string $contentType;
    private string $http;
    private $additionnalHeaders;


    public function __construct() {

        // TODO SET A SUPPORTED HTTP HEADER LIST
        $this->supportedContentTypes = Array(
            'application/json'
        );
        $this->additionnalHeaders = Array();

        $this->setHttp();
        $this->setContentType();
    }

    public function setContentType(string $value = null) {
        if(in_array($value, $this->supportedContentTypes)) {
            $this->contentType = value;
        } else {
            $this->contentType = array_values($this->supportedContentTypes)[0];
        }
    }

    public function setHttp(string $http = null) {
        $this->http = is_null($http) ? '200 OK' : $http;
    }

    public function toArray() {

        if (!$this->contentType) {
            $this->setContentType();
        }

        if (!$this->http) {
            $this->setHttp();
        }

        return array_merge(Array(
            'Content-Type: ' . $this->contentType . ';charset=UTF-8',
            'HTTP/1.1 ' . $this->http
        ), $this->additionnalHeaders);
    }

    public function addCustom(string $header) {
        array_push($this->additionnalHeaders, $header);
    }
}

class Procedure {

    private $AVAILABLE_PROCEDURES;
    private $ctrl;
    private $data;
    private $headers;
    private $type;


    public function __construct(string $code, array|object $data) {

        $this->AVAILABLE_PROCEDURES = Array(
            (object) Array(
                'code' => 'OK',
                'type' => 'success',
                'http' => '200 OK'
            ),
            (object) Array(
                'code' => '500',
                'type' => 'error',
                'http' => '500 Internal Server Error',
                'defaultMessage' => 'Unhandled error.',
                'isDefault' => true
            ),
            (object) Array(
                'code' => 'NOT_IMPLEMENTED',
                'type' => 'error',
                'http' => '501 Not Implemented',
                'defaultMessage' => 'Not implemented function.',
                'isDefault' => true
            ),
            (object) Array(
                'code' => 'NOT_SUPPORTED',
                'type' => 'error',
                'http' => '400 Bad request',
                'defaultMessage' => 'Not supported request.'
            ),
            (object) Array(
                'code' => 'NOT_FOUND',
                'type' => 'error',
                'http' => '404 Not Found',
                'defaultMessage' => 'Resource not found.'
            ),
            (object) Array(
                'code' => 'UNAUTHORIZED',
                'type' => 'error',
                'http' => '401 Unauthorized',
                'defaultMessage' => 'You are not authorized to do this, please loggin first.'
            )
        );

        $this->ctrl = CommonController::getInstance();
        $this->headers = new Headers();
        $this->data = $data;

        $this->setType($code);
    }

    private function setType(string $code = null) {
        foreach ($this->AVAILABLE_PROCEDURES as $TYPE) {
            if ($TYPE->code === $code) {
                $this->type = $TYPE;
                return;
            }
        }

        foreach ($this->AVAILABLE_PROCEDURES as $TYPE) {
            if ($TYPE->isDefault) {
                $this->type = $TYPE;
                break;
            }
        }
    }

    public function print() {

        $this->headers->setHttp($this->type->http);

        $result = (object) Array(
            'procedure' => $this->type->code, // This code can trigger action on front side.
            'data' => null,
            'error' => null
        );

        if (!isset($this->data->message) && isset($this->type->defaultMessage)) {
            $this->data->message = $this->type->defaultMessage;
        }

        if ($this->type->type === "error") {
            $result->error = $this->data;
        } else {
            $result->data = $this->data;
        }

        $this->ctrl->sendOutput(json_encode($result), $this->headers->toArray());
    }
}

class Procedures
{

    private $PROCEDURE_MAPPING;

    public function __construct() {

        $this->PROCEDURE_MAPPING = Array(
            (object) Array('action' => 'create', 'subject' => 'config', 'procedure' => 'newConfigTemplate'),
            (object) Array('action' => 'store', 'subject' => 'tournaments', 'procedure' => 'storeTournaments'),
            (object) Array('action' => 'list', 'subject' => 'tournaments', 'procedure' => 'listTournaments')
        );
    }

    private function findProcedureFromData($data) {
        $procedureConfig = null;

        foreach ($this->PROCEDURE_MAPPING as $value) {
            if ($value->action === $data->action && $value->subject === $data->subject) {
                $procedureConfig = $value;
                break;
            }
        }

        return $procedureConfig;
    }

    private function isValidTournament(mixed $tournament) {
        if (!$tournament || !$tournament->id || !$tournament->name || !is_array($tournament->grid)) {
            return false;
        }

        return true;
    }

    private function storeTournaments($requestData) {
        message('call to storeTournaments()');

        $postData = utils\common\getPostData();

        if (!$postData || !isset($postData['timestamp']) || !isset($postData['tournaments'])) {
            message('call to NOT_SUPPORTED: "Your posted data is not in supported format."');
            return $this->error('NOT_SUPPORTED', $requestData, 'Your posted data is not in supported format.');
        }

        $tournament1 = (object) $postData['tournaments'][0];
        message('First tournament: ', $tournament1);
        if (!$this->isValidTournament($tournament1)) {
            message('The first element of $postData is not a valid tournament.');
            return $this->error('NOT_SUPPORTED', $requestData, 'Your posted tournament(s) is not in supported format.');
        }

        $result = utils\common\saveJsonOnFile($postData, PROJECT_ROOT_PATH . "data/tournaments.json");
        if ($result->type === 'error') {
            return $this->error('500', $requestData, $result->message);
        }

        $data = (object) array_merge(
            (array) $requestData,
            Array(
                'message' => 'Tournaments saved.'
            )
        );

        message('new procedure OK with data: ' . json_encode($data));
        return new Procedure('OK', $data);
    }

    private function listTournaments($requestData) {
        if ($requestData->option) {
            return $this->error('NOT_SUPPORTED', $requestData);
        }

        $result = utils\common\readJsonFromFile(PROJECT_ROOT_PATH . "data/tournaments.json");
        if ($result->type === 'error') {
            return $this->error('500', $requestData, $result->message);
        }

        return new Procedure('OK', $result->data);
    }

    private function error(string $code, object $requestData = null, string $customMessage = null) {
        if (!isset($requestData)) {
            $requestData = (object) Array();
        }

        if ($customMessage) {
            $requestData->message = $customMessage;
        }

        return new Procedure($code, $requestData,);
    }

    private function todo() {
        return new Procedure('TODO', (object) Array(message => 'This feature is not yet implemented.' ));
    }

    private function ok($requestData) {
        return new Procedure('OK', $requestData);
    }

    private function firstConnection() {
        return $this->todo();
    }

    private function newConfigTemplate() {
        require_once(PROJECT_ROOT_PATH . "utils/template.php");

        $config = new ConfigTemplate();
        if ($config) {
            message('$config OK');
        } else {
            message('$config KO');
        }

        return new Procedure('OK', (object) Array('content' => $config->toString()));
    }

    public function getProcedureFromData($requestData) {
        message('call to getProcedureFromData().');
        $procedureConfig = $this->findProcedureFromData($requestData);

        message('found procedure config: ', $procedureConfig);
        if (!$procedureConfig) {
            message('procedure NOT found.');
            return $this->error('NOT_FOUND', $requestData);
        }

        if(!method_exists($this, $procedureConfig->procedure)) {
            message('procedure NOT configured.');
            return $this->error('NOT_IMPLEMENTED', $requestData, "Procedure is not well configured.");
        }

        return $this->{$procedureConfig->procedure}($requestData);
    }

}

?>

