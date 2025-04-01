<?php

namespace Gelembjuk\EasyApp\Response;

class RedirectResponse extends Response
{
    use RedirectResponseTrait;
    public function __construct($url, $message = "")
    {
        parent::__construct(302);
        $this->url = $url;
        $this->message = $message;
    }
}