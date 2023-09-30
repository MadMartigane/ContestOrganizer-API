<?php

// REQUIRES
require_once PROJECT_ROOT_PATH . "controllers/common.php";

// CONSTANTS


class Headers {
    private $supportedContentTypes = Array(
        "application/json"
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
            "Content-Type: " . $this->contentType
        );
    }
}

class Procedure {

    private $ctrl;
    private $data;
    private $headers;
    private $status;


    public function __construct($data) {
        $this->ctrl = new CommonController();
        $this->data = $data;
        $this->headers = new Headers();
    }

    public function print() {
        $this->ctrl->sendOutput(json_encode($this->data), $this->headers->toArray());
    }
}

class Procedures
{

    public function todo() {
        return new Procedure((object) Array(message => "This feature is not yet implemented." ));
    }

    public function ok($data) {
        return new Procedure($data);
    }

    public function firstConnection() {
        return $this->todo();
    }

}

?>

