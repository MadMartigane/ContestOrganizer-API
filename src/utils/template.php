<?php

if (!defined('PROJECT_ROOT_PATH')) {
    require_once('../utils/403.php');
}

class ConfigTemplate {

    private string $templateContent;

    public function __construct () {
        $this->templateContent = file_get_contents(PROJECT_ROOT_PATH . 'config/template.php');
        message("TEMPLATE_CONTENT raw: " . $this->templateContent);

        if (!$this->templateContent) {
            throw new ErrorException('Unable to read "config/template.php"');
        }
    }

    public function toString() {
        return $this->templateContent;
    }
}

?>

