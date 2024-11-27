<?php

namespace Gelembjuk\WebApp\Response;

trait RedirectResponseTrait
{
    protected $url;
    protected $message;

    public function getUrl()
    {
        return $this->url;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getRedirectResponse(): RedirectResponse
    {
        return new RedirectResponse($this->url, $this->message);
    }
}