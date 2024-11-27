<?php

namespace Gelembjuk\WebApp\Response;

class StreamResponse extends Response
{
    protected $stream;
    protected $contentType;
    protected $filename;
    protected $size;
    protected $headers;

    public function __construct(
        $stream,
        $contentType = "application/octet-stream",
        $filename = null,
        $size = null,
        $headers = [],
        $httpCode = 0
    ) {
        parent::__construct($httpCode);
        
        $this->stream = $stream;
        $this->contentType = $contentType;
        $this->filename = $filename;
        $this->size = $size;
        $this->headers = $headers;
        
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function withSize($size)
    {
        $this->size = $size;
        return $this;
    }

    public function getStream()
    {
        return $this->stream;
    }
}