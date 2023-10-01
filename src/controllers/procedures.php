<?php

// REQUIRES
require_once PROJECT_ROOT_PATH . 'controllers/common.php';


class Headers {

    private $supportedContentTypes = Array(
        'application/json'
    );
    private $contentType;


    public function setContentType(string $value = null) {
        if(in_array($value, $this->supportedContentTypes)) {
            $this->contentType = value;
        } else {
            $this->contentType = array_values($this->supportedContentTypes)[0];
        }
    }

    public function toArray() {
        if (!$this->contentType) {
            $this->setContentType();
        }

        return Array(
            'Content-Type: ' . $this->contentType
        );
    }
}

class Procedure {
    // CONSTANTS
    private $AVAILABLE_PROCEDURE_TYPES;
    private $ctrl;
    private $data;
    private $headers;
    private $type;


    public function __construct(string $type, $data) {

        $this->AVAILABLE_PROCEDURE_TYPES = Array(
            (object) Array('name' => 'ok', 'code' => 200, 'default' => true),
            (object) Array('name' => 'todo', 'code' => 200),
            (object) Array('name' => 'installation', 'code' => 200)
        );

        $this->ctrl = new CommonController();
        $this->data = $data;
        $this->headers = new Headers();

        $this->setType($type);
    }

    private function setType(string $type) {
        foreach ($this->AVAILABLE_PROCEDURE_TYPES as $TYPE) {
            if ($TYPE->name === $type) {
                $this->type = $TYPE;
                return;
            }
        }

        foreach ($this->AVAILABLE_PROCEDURE_TYPES as $TYPE) {
            if ($TYPE->default) {
                $this->type = $TYPE;
                break;
            }
        }
    }

    public function print() {
        $result = (object) Array(
            'procedure' => $this->type->name,
            'data' => $this->data,
            'error' => Array()
        );

        $this->ctrl->sendOutput(json_encode($result), $this->headers->toArray());
    }
}

class Procedures
{

    public function todo() {
        return new Procedure('todo', (object) Array(message => 'This feature is not yet implemented.' ));
    }

    public function ok($data) {
        return new Procedure('ok', $data);
    }

    public function firstConnection() {
        return $this->todo();
    }

}

?>

