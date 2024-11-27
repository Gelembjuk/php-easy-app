<?php

namespace Gelembjuk\WebApp\Response;

class DataResponse extends Response implements \ArrayAccess   
{
    protected $data;
    protected $template;
    /**
     * @var string|null
     * 
     * Base template is optional. It is used to wrap the template with a base template.
     * To support this in the presenter, it must be additionally configured
     */
    protected ?string $baseTemplate;
    protected $completeResponse;

    public function __construct(array $data = [], $template = "", ?string $completeResponse = null, $httpCode = 0)
    {
        parent::__construct($httpCode);
        $this->data = $data;
        $this->template = $template;
        $this->completeResponse = $completeResponse;
    }

    public function getData(): array
    {
        return $this->data;
    }
    public function withCompleteResponse($completeResponse)
    {
        $this->completeResponse = $completeResponse;
        return $this;
    }
    public function withData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }
    public function getBaseTemplate():?string
    {
        return $this->baseTemplate;
    }
    public function hasBaseTemplate():bool
    {
        return !empty($this->baseTemplate);
    }
    public function hasCompleteResponse():bool
    {
        return $this->completeResponse !== null;
    }
    public function getCompleteResponse():?string
    {
        return $this->completeResponse;
    }

    public function withTemplate($template)
    {
        $this->template = $template;
        return $this;
    }
    public function withBaseTemplate(?string $baseTemplate)
    {
        $this->baseTemplate = $baseTemplate;
        return $this;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}