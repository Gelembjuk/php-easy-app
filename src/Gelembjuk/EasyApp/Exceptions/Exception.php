<?php

namespace Gelembjuk\EasyApp\Exceptions;

class Exception extends \Exception {
    protected $statusCode = 0;
    protected $defaultStatusCode = 500;

    public function __construct($message = "", $statusCode = 0, $code = 0) 
    {
        $this->statusCode = $statusCode;

        if (empty($message)) {
            $message = $this->getDefaultMessage();
        }

        parent::__construct($message, $code);
    }

    protected function getDefaultMessage():string 
    {
        return 'Internal Server Error';
    }

    public function getStatusCode() 
    {
        return $this->statusCode;
    }

    public function getDefaulStatusCode() 
    {
        return $this->defaultStatusCode;
    }
}