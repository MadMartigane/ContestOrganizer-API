<?php

// REQUIRES
require_once PROJECT_ROOT_PATH . 'controllers/common.php';


class Headers {

    private $supportedContentTypes;
    private string $contentType;
    private string $http;
    private $additionnalHeaders;


    public function __construct() {
        message('__construct new Headers…');

        // TODO SET A SUPPORTED HTTP HEADER LIST
        $this->supportedContentTypes = Array(
            'application/json'
        );
        $this->additionnalHeaders = Array();

        $this->setHttp();
        $this->setContentType();
    }

    public function setContentType(string $value = null) {
        message("setting content type to : " . $value);
        if(in_array($value, $this->supportedContentTypes)) {
            $this->contentType = value;
        } else {
            $this->contentType = array_values($this->supportedContentTypes)[0];
        }
        message("content type set to : " . $this->contentType);
    }

    public function setHttp(string $http = null) {
        $this->http = is_null($http) ? '200 OK' : $http;
        message("Headers->http set: " . $this->http);
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


    public function __construct(string $code, object $data) {

        message('__construct() new procedure ' . $code . ' with data: ' . json_encode($data));
        $this->AVAILABLE_PROCEDURES = Array(
            (object) Array(
                'code' => 'OK',
                'type' => 'success',
                'http' => '200 OK'
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
                'defaultMessage' => 'You are not authorized to do this, pleaes loggin first.'
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

        if (!isset($this->data->message) && $this->type->defaultMessage) {
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
            (object) Array('action' => 'list', 'subject' => 'tournament', 'procedure' => 'listTournament')
        );
    }

    private function findProcedureFromData($data) {
        $procedureConfig = null;
        message('call to findProcedureFromData().');

        foreach ($this->PROCEDURE_MAPPING as $value) {
            message('procedure candidate: ' . json_encode($value));
            if ($value->action === $data->action && $value->subject === $data->subject) {
                $procedureConfig = $value;
                break;
            }
        }

        return $procedureConfig;
    }

    public function getProcedureFromData($requestData) {
        message('call to getProcedureFromData().');
        $procedureConfig = $this->findProcedureFromData($requestData);

        message('found procedure config: ' . json_encode($procedureConfig));
        if (!$procedureConfig) {
            message('procedure NOT found.');
            return $this->error('NOT_FOUND', $requestData);
        }

        return $this->{$procedureConfig->procedure}($requestData);
    }

    public function error(string $code, object $requestData, string $customMessage = null) {
        if ($customMessage) {
            $requestData->message = $customMessage;
        }

        return new Procedure($code, $requestData,);
    }

    public function todo() {
        return new Procedure('TODO', (object) Array(message => 'This feature is not yet implemented.' ));
    }

    public function ok($requestData) {
        return new Procedure('OK', $requestData);
    }

    public function firstConnection() {
        return $this->todo();
    }

    public function listTournament($requestData) {
        message('call to listTournament()');

        $data = (object) array_merge(
            (array) $requestData,
            Array(
                'message' => 'Fake implementation of listTournament().'
            )
        );

        message('new procedure OK with data: ' . json_encode($data));
        return new Procedure('OK', $data);
    }
}

?>

