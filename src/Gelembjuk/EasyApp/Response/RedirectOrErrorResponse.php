<?php

namespace Gelembjuk\EasyApp\Response;

class RedirectOrErrorResponse extends ErrorResponse
{
    use RedirectResponseTrait;
    public function __construct(string $url, string $message, \Exception $exception = null, int $httpCode = 0)
    {
        parent::__construct($message, $exception, $httpCode);
        $this->url = $url;
    }
}