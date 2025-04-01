<?php

namespace Gelembjuk\EasyApp\Exceptions;

class ConflictException extends Exception {
    protected $defaultStatusCode = 409;

    protected function getDefaultMessage(): string 
    {
        return 'Conflict';
    }
}