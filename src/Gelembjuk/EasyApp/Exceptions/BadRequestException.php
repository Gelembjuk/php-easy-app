<?php

namespace Gelembjuk\WeEasyAppbApp\Exceptions;

class BadRequestException extends Exception {
    protected $defaultStatusCode = 400;

    protected function getDefaultMessage():string 
    {
        return 'Bad Request';
    }
}