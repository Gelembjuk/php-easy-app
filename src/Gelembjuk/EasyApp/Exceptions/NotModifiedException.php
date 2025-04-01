<?php

namespace Gelembjuk\EasyApp\Exceptions;

class NotModifiedException extends Exception {
    protected $defaultStatusCode = 304;

    protected function getDefaultMessage(): string 
    {
        return 'Not Modified';
    }
}