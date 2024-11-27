<?php

namespace Gelembjuk\WebApp\Response;

class RedirectOrDataResponse extends DataResponse
{
    use RedirectResponseTrait;
    public function __construct(string $url, $message = "", array $data = [], string $template = "", $httpCode = 0)
    {
        parent::__construct($data, $template, null, $httpCode);
        $this->url = $url;
        $this->message = $message;
    }
}