<?php

namespace Gelembjuk\WebApp\Request;

class PHPStandard extends AbstractRequest {
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->host = $_SERVER['HTTP_HOST'] ?? '';
        
        $this->endpoint = strtok($_SERVER["REQUEST_URI"] ?? '', '?');

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $this->data_stream_size = (int)$_SERVER['CONTENT_LENGTH'];
        }
    }
    public function get($key, $value_type, $default_value = null, $non_if_missing = false)
    {
        if (isset($_REQUEST[$key])) {
            $this->data[$key] = $_REQUEST[$key];
        }
        return parent::get($key, $value_type, $default_value, $non_if_missing);
    }
    public function set(string $key, $value)
    {
        $_REQUEST[$key] = $value;
        return $this;
    }
    public function getFile($key): ?array
    {
        $file = $_FILES[$key] ?? null;

        if ($file === null) {
            return null;
        }

        if ($file['error'] != 0) {
            return null;
        }

        return $file;
    }
    public function getFileInputStream($key)
    {
        $stream = parent::getFileInputStream($key);

        if ($stream !== null) {
            return $stream;
        }

        if (isset($_FILES[$key])) {
            // open file stream from tmp file
            return fopen($_FILES[$key]["tmp_name"], 'r');
        }
        return null;
    }
    public function getHeaderValue($key)
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        // else find in getallheaders()
        static $allHeaders;

        if ($allHeaders === null) {
            $allHeaders = getallheaders();
        }
        $key = strtolower($key);

        foreach ($allHeaders as $k => $v) {
            if (strtolower($k) == $key) {
                return $v;
            }
        }
        return null;
    }
    public function getCookieValue($key)
    {
        if (isset($this->cookies[$key])) {
            return $this->cookies[$key];
        }
        return $_COOKIE[$key] ?? null;
    }
    protected function getDataBodyParsed($format = "json")
    {
        if ($format == "json" && $this->request_method == "PUT") {
            $this->data_body = file_get_contents('php://input');
        }
        return parent::getDataBodyParsed($format);
    }
    
    public function getInputStream()
    {
        if ($this->data_stream !== null) {
            return $this->data_stream;
        }
        $this->data_stream = fopen('php://input', 'r');
        return $this->data_stream;
    }
    public function getScriptName(): string 
    {
        return $_SERVER['SCRIPT_NAME'] ?? '';
    }
}