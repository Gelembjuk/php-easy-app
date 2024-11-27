<?php

namespace Gelembjuk\WebApp\Exceptions;

class InvalidArgumentException extends Exception {
    protected $defaultStatusCode = 422;
    protected $inputName = '';

    public function __construct($message = "", $inputName = '', $statusCode = 0, $code = 0) 
    {
        parent::__construct($message, $statusCode, $code);
        $this->inputName = $inputName;
    }
    public function getInputName() 
    {
        return $this->inputName;
    }
    protected function getDefaultMessage(): string 
    {
        return 'Invalid Argument';
    }
}