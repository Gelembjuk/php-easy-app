<?php

namespace Gelembjuk\WebApp\Response;

class Response
{
    protected $httpCode = 0;
    protected $presenter;
    protected $headers = [];
    protected $cookies = [];

    public function __construct(int $httpCode = 0)
    {
        $this->httpCode = $httpCode;
        $this->presenter = null;
        $this->headers = [];
    }
    public function hasPresenter(): bool
    {
        return !empty($this->presenter);
    }
    public function getPresenter(): ?string
    {
        return $this->presenter;
    }

    public function withPresenter(string $presenter): self
    {
        $this->presenter = $presenter;
        return $this;
    }
    public function getCookies(): array
    {
        return $this->cookies;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withHeader(string $header, string $value): self
    {
        $this->headers[] = [$header, $value];
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function withCookies(array $cookies): self
    {
        $this->cookies = $cookies;
        return $this;
    }

    public function withCookie(string $name, array $info): self
    {
        $this->cookies[$name] = $info;
        return $this;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function withHttpCode(int $httpCode): self
    {
        $this->httpCode = $httpCode;
        return $this;
    }
}