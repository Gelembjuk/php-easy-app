<?php

namespace Gelembjuk\EasyApp\Exceptions;

class IntegrationException extends Exception {
    protected $defaultStatusCode = 503;

    protected function getDefaultMessage(): string 
    {
        return 'Integration Error';
    }
}