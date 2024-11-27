<?php

namespace Gelembjuk\WebApp\Exceptions;

class UnauthorizedException extends Exception {
    protected $defaultStatusCode = 401;

    protected function getDefaultMessage(): string 
    {
        return 'Unauthorized';
    }
}