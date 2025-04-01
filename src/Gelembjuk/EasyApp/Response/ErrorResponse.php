<?php

namespace Gelembjuk\EasyApp\Response;

class ErrorResponse extends Response
{
    protected $message;
    protected $exception;

    public function __construct(string $message, \Throwable $exception = null, int $httpCode = 0)
    {
        parent::__construct($httpCode);
        $this->message = $message;
        $this->exception = $exception;

        if ($exception !== null && (empty($message))) {
            $this->message = $message . ' - ' . $exception->getMessage();
        }

        if ($exception !== null && $httpCode === 0 && $exception instanceof \Gelembjuk\EasyApp\Exceptions\Exception) {
            $this->withHttpCode($exception->getStatusCode());
        }
    }
    public function getHttpCode(): int
    {
        if ($this->httpCode > 0) {
            return $this->httpCode;
        }
        if ($this->exception !== null && $this->exception instanceof \Gelembjuk\EasyApp\Exceptions\Exception) {
            return $this->exception->getDefaulStatusCode();
        }
        return 0;
    }
    public function getMessage()
    {
        return $this->message;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}