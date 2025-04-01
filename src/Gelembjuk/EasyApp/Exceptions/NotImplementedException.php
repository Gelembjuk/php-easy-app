<?php

namespace Gelembjuk\EasyApp\Exceptions;

class NotImplementedException extends Exception {
    protected $defaultStatusCode = 501;

    protected function getDefaultMessage(): string 
    {
        return 'Not Implemented';
    }
}