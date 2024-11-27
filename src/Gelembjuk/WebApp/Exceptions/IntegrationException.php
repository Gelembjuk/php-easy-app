<?php

namespace Gelembjuk\WebApp\Exceptions;

class IntegrationException extends Exception {
    protected $defaultStatusCode = 503;

    protected function getDefaultMessage(): string 
    {
        return 'Integration Error';
    }
}