<?php

namespace Gelembjuk\EasyApp\Exceptions;

class UnauthorizedException extends Exception {
    protected $defaultStatusCode = 401;

    protected function getDefaultMessage(): string 
    {
        return 'Unauthorized';
    }
}