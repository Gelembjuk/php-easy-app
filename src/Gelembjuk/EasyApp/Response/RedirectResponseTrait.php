<?php

namespace Gelembjuk\EasyApp\Response;

trait RedirectResponseTrait
{
    protected $url;
    protected string $message;

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