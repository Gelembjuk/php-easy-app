<?php

namespace Gelembjuk\EasyApp\Exceptions;

class InvalidArgumentException extends Exception {
    protected $defaultStatusCode = 422;
    protected $inputName = '';
    protected $reasonCode = '';

    public function __construct($message = "", $inputName = '', $statusCode = 0, $code = 0, $reason_code = "")
    {
        parent::__construct($message, $statusCode, $code);
        $this->inputName = $inputName;
        $this->reasonCode = $reason_code;
    }
    public function setReasonCode(string $reason_code)
    {
        $this->reasonCode = $reason_code;
    }
    public function getInputName()
    {
        return $this->inputName;
    }
    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }
    protected function getDefaultMessage(): string 
    {
        return 'Invalid Argument';
    }
}