<?php

namespace Gelembjuk\WebApp\Request;

class AbstractRequest {
    const VALUE_TYPE_INT = 'int';
    const VALUE_TYPE_FLOAT = 'float';
    const VALUE_TYPE_STRING = 'string';
    const VALUE_TYPE_BOOL = 'bool';
    const VALUE_TYPE_OBJECT = 'object';
    const VALUE_TYPE_ARRAY = 'array';
    const VALUE_TYPE_ALPHA = 'alpha';
    const VALUE_TYPE_DATE = 'date';

    protected $data = [];
    protected $files = [];
    protected $priority_data = [];
    protected $headers = [];
    protected $data_body = "";

    protected $data_body_parsed = false;
    protected $data_stream = null;
    protected $data_stream_size = 0;
    protected $endpoint = "";
    protected $request_method = "GET";
    protected $present_format = null; // by default it is empty, it means - auto selection
    protected $cookies = [];
    protected $host = "";

    protected $customDataType = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function withData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function withFile(string $key, string $localPath, string $name, string $type, int $size)
    {
        $this->files[$key] = [
            "name" => $name,
            "type" => $type,
            "tmp_name" => $localPath,
            "error" => UPLOAD_ERR_OK,
            "size" => $size
        ];
        return $this;
    }

    public function withStream($stream)
    {
        $this->data_stream = $stream;
        return $this;
    }

    public function withStreamSize(int $size)
    {
        $this->data_stream_size = $size;
        return $this;
    }

    public function withDataBody(string $data)
    {
        $this->data_body = $data;
        return $this;
    }

    public function setPriorityData(array $data)
    {
        $this->priority_data = $data;
        return $this;
    }

    public function withHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }

    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function withEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getEndpoint()
    {
        if (empty($this->endpoint) || $this->endpoint == '/') {
            return null;
        }

        return $this->endpoint;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function withRequestMethod(string $request_method)
    {
        $this->request_method = $request_method;
        return $this;
    }

    public function getRequestMethod()
    {
        return $this->request_method;
    }

    public function withPresentFormat(string $present_format)
    {
        $this->present_format = $present_format;
        return $this;
    }

    public function getPresentFormat()
    {
        return $this->present_format;
    }

    public function getInt(string $key, $defaultValue = null)
    {
        return $this->get($key, self::VALUE_TYPE_INT, $defaultValue);
    }

    public function getFloat(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_FLOAT);
    }

    public function getString(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_STRING);
    }

    public function getBool(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_BOOL);
    }

    public function getObject(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_OBJECT);
    }

    public function getArray(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_ARRAY);
    }

    public function getAlpha(string $key)
    {
        return $this->get($key, self::VALUE_TYPE_ALPHA);
    }

    public function get($key, $value_type, $default_value = null, $non_if_missing = false)
    {
        if (isset($this->priority_data[$key])) {
            return $this->cleanInput($this->priority_data[$key], $value_type);
        }

        if (!$this->data_body_parsed && ($this->request_method == "POST" || $this->request_method == "PUT")) {
            $this->parseDataBody();
        }

        $val = isset($this->data[$key]) ? $this->data[$key] : null;

        if ($val === null) {
            if ($non_if_missing) {
                return null;
            }
            if ($default_value !== null) {
                return $default_value;
            }
            return $this->getDefaultInput($value_type);
        }

        return $this->cleanInput($val, $value_type);
    }

    public function getDataBody()
    {
        return $this->data_body;
    }
    protected function parseDataBody()
    {
        if ($this->data_body_parsed) {
            return;
        }

        if (empty($this->data_body)) {
            return;
        }

        // detect format by headers
        $format = $this->getHeaderValue("Content-Type") == "application/json" ? "json" : "form";

        $data = $this->getDataBodyParsed($format);
        $this->data = array_merge($this->data, $data);
        $this->data_body_parsed = true;
    }
    protected function getDataBodyParsed($format = "json")
    {
        if ($format == "json") {
            try {
                return json_decode($this->data_body, true);
            } catch (\Exception $e) {
                return [];
            }
        }

        if ($format == "form") {
            // not sure if we need this for PHP, maybe later
            throw new \Exception("Not implemented");
        }

        return [];
    }

    public function getInputStream()
    {
        if ($this->data_stream !== null) {
            return $this->data_stream;
        }
        $stream = fopen(sprintf('data://text/plain,%s', $this->data_body), 'r');
        return $stream;
    }

    public function getInputStreamSize()
    {
        if ($this->data_stream_size > 0) {
            return $this->data_stream_size;
        }

        return strlen($this->data_body);
    }
    public function getFile($key): ?array
    {
        return $this->files[$key] ?? null;
    }
    public function getFileInputStream($key)
    {
        if (isset($this->files[$key])) {
            // open file stream from tmp file
            return fopen($this->files[$key]["tmp_name"], 'r');
        }
        return null;
    }

    public function getClientIp()
    {
        $ip = $this->getHeaderValue("X-Forwarded-For");

        if (empty($ip)) {
            $ip = $this->getHeaderValue("REMOTE_ADDR");
        }

        return $ip;
    }
    public function getUserAgent(): string
    {
        return $this->getHeaderValue("User-Agent") ?? '';
    }

    protected function parseCookie()
    {
        if (!empty($this->cookies)) {
            return;
        }

        $cookie_str = $this->getHeaderValue("Cookie");

        if (empty($cookie_str)) {
            $this->cookies = [];
            return;
        }

        $cookiesObj = [];
        parse_str(str_replace('; ', '&', $cookie_str), $cookiesObj);
        $this->cookies = $cookiesObj;
    }

    public function getCookieValue($key)
    {
        $this->parseCookie();
        return $this->cookies[$key] ?? null;
    }

    public function getHeaderValue($key)
    {
        return $this->headers[$key] ?? null;
    }

    protected function cleanInput($input, $input_type)
    {
        // it is custom data type handler
        if (isset($this->customDataType[$input_type])) {
            return call_user_func($this->customDataType[$input_type], $input);
        }
        if ($input_type == self::VALUE_TYPE_OBJECT) {
            return $input;
        }

        if ($input_type == self::VALUE_TYPE_ARRAY) {
            if (!is_array($input)) {
                if ($input || $input === 0 || $input === "0") {
                    return [$input];
                }
                return [];
            }
            return $input;
        }

        if (is_array($input)) {
            if (count($input) == 0) {
                return $this->getDefaultInput($input_type);
            }
            $input = $input[0];
        }

        switch ($input_type) {
            case self::VALUE_TYPE_INT:
                return (int)$input;
            case self::VALUE_TYPE_FLOAT:
                return (float)$input;
            case self::VALUE_TYPE_STRING:
                return (string)$input;
            case self::VALUE_TYPE_BOOL:
                if ($input === "y") {
                    return true;
                }
                return filter_var($input, FILTER_VALIDATE_BOOLEAN);
            case self::VALUE_TYPE_ALPHA:
                return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
            default:
                return $input;
        }
    }

    protected function getDefaultInput($input_type)
    {
        switch ($input_type) {
            case self::VALUE_TYPE_OBJECT:
                return null;
            case self::VALUE_TYPE_ARRAY:
                return [];
            case self::VALUE_TYPE_INT:
                return 0;
            case self::VALUE_TYPE_FLOAT:
                return 0.0;
            case self::VALUE_TYPE_BOOL:
                return false;
            case self::VALUE_TYPE_ALPHA:
            case self::VALUE_TYPE_STRING:
                return "";
            default:
                return "";
        }
    }
    /**
     * Register custom data type
     * 
     * @param string $type
     * @param callable $callback function($input) : mixed
     */
    public function registerCustomDataType($type, $callback)
    {
        $this->customDataType[$type] = $callback;
    }
    public function getScriptName(): string 
    {
        return '';
    }
}