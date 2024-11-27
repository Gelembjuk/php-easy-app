<?php

namespace Gelembjuk\WebApp\Response;

class LocalFileResponse extends StreamResponse {
    protected $filePath;

    public function __construct(
        $filePath,
        $contentType = "application/octet-stream",
        $filename = null,
        $headers = [],
        $httpCode = 0
    ) 
    {
        $size = filesize($filePath);
        parent::__construct(null, $contentType, $filename, $size, $headers, $httpCode);
        $this->filePath = $filePath;
    }
    public function getStream()
    {
        return fopen($this->filePath, 'r');
    }
}