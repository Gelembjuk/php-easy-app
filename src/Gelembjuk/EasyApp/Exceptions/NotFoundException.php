<?php

namespace Gelembjuk\EasyApp\Exceptions;

class NotFoundException extends Exception {
    protected $defaultStatusCode = 404;

    protected function getDefaultMessage(): string 
    {
        return 'Not Found';
    }
}