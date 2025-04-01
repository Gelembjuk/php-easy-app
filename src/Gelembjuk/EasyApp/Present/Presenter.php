<?php

namespace Gelembjuk\EasyApp\Present;

use Error;
use \Gelembjuk\EasyApp\Context as Context;
use \Gelembjuk\EasyApp\Response\Response as Response;
use \Gelembjuk\EasyApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\EasyApp\Response\StreamResponse as StreamResponse;
use \Gelembjuk\EasyApp\Response\DataResponse as DataResponse;
use \Gelembjuk\EasyApp\Response\NoContentResponse as NoContentResponse;
use \Gelembjuk\EasyApp\Response\RedirectResponse as RedirectResponse;

abstract class Presenter {
    /**
     * For presenting methods we send a response as argument to make "cast" to the response type
     */
    const DATA_TYPE_NULL = 'null';
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_STREAM = 'stream';
    const DATA_TYPE_ITERATOR = 'iterator';

    protected $format_identifier = null;
    
    protected \Gelembjuk\EasyApp\Context  $context;
    protected \Gelembjuk\EasyApp\Response\Response $response;
    protected \Psr\Log\LoggerInterface $logger;

    protected $settings = [];

    // prepared response data
    protected $headers = [];
    protected $cookies = [];
    protected $httpString = "";
    protected $httpStatusCode = 200;
    protected $data = null;
    protected $dataSize = 0;
    protected $dataType = self::DATA_TYPE_NULL;

    public function __construct(Context $context, Response $response)
    {
        $this->context = $context;
        $this->response = $response;
        $this->logger = $this->context->getLogger('presenter'); // Assuming you have a PSR-3 logger

        $this->settings = $context->getPresenterSettings($this->format_identifier);
    }

    public function withLogger(\Psr\Log\LoggerInterface $logger):static
    {
        $this->logger = $logger;
        return $this;
    }

    public function getHttpStatusCode():int
    {
        return $this->httpStatusCode;
    }
    public function getHttpString():string
    {
        return $this->httpString;
    }
    public function getHeaders():array
    {
        return $this->headers;
    }
    public function getData()
    {
        return $this->data;
    }
    public function getDataType():string
    {
        return $this->dataType;
    }
    public function checkDataType($type)
    {
        return $this->dataType == $type;
    }

    public function standardOutput()
    {
        header('HTTP/1.1 '.$this->getHttpString());

        foreach ($this->headers as $header) {
            header($header[0] . ': ' . $header[1]);
        }

        foreach ($this->cookies as $name => $cookie) {
            setcookie($name, 
                $cookie['value'] ?? '', 
                $cookie['expire'] ?? 0, 
                $cookie['path'] ?? '', 
                $cookie['domain'] ?? '', 
                $cookie['secure'] ?? false, 
                $cookie['httponly'] ?? false);
        }
        
        if ($this->dataType == self::DATA_TYPE_STRING) {
            echo $this->data;

            return;
        }

        if ($this->dataType == self::DATA_TYPE_STREAM) {
            // read till eof 
            $this->outputStream();
            return;
        }
        if ($this->dataType == self::DATA_TYPE_ITERATOR) {
            foreach ($this->data as $line) {
                echo $line;
                // flush output to the browser
                ob_flush();
                flush();
            }
            unset($this->data);
            return;
        }

        return ;
    }
    public function getContent(): string
    {
        if ($this->dataType == self::DATA_TYPE_STRING) {
            return $this->data;
        }

        if ($this->dataType == self::DATA_TYPE_STREAM) {
            // read till eof 
            // TODO. we can return a string with the content
            return '';
        }
        if ($this->dataType == self::DATA_TYPE_ITERATOR) {
            $response = '';
            foreach ($this->data as $line) {
                $response .= $line;
            }
            unset($this->data);
            return $response;
        }

        return '';
    }
    private function outputStream()
    {
        $blockSize = 4096;
        $sentBytes = 0;

        while (!feof($this->data)) {
            $data = fread($this->data, $blockSize);
            
            if (!$data) {
                break;
            }
            if ($this->dataSize > 0 && $sentBytes + strlen($data) > $this->dataSize) {
                $data = substr($data, 0, $this->dataSize - $sentBytes);
            }
            $sentBytes += strlen($data);
            echo $data;

            // flush output to the browser
            ob_flush();
            flush();

            if ($this->dataSize > 0 && $sentBytes >= $this->dataSize) {
                break;
            }
        }
        fclose($this->data);
    }

    protected function appendHeader($name, $value, $replaceIfExists = true)
    {
        if ($replaceIfExists) {
            foreach ($this->headers as $key => $header) {
                if (strtolower($header[0]) == strtolower($name)) {
                    if ($value === null || $value === false or $value === '') {
                        unset($this->headers[$key]);
                        return;
                    }
                    $this->headers[$key] = [$name, $value];
                    return;
                }
            }
        }
        // if exists do nothing
        foreach ($this->headers as $key => $header) {
            if (strtolower($header[0]) == strtolower($name)) {
                return ;
            }
        }
        
        $this->headers[] = [$name, $value];
    }

    public function buildOutput(): static
    {
        foreach ($this->response->getHeaders() as $key => $header) {
            if (is_array($header)) {
               $this->appendHeader($header[0], $header[1]);
            } else {
                $this->appendHeader($key, $header);
            }
        }
        $this->cookies = $this->response->getCookies();

        $this->present();

        if ($this->httpString == "") {
            $this->setHttpCodeAndString(200, "200 Ok");
        }
        return $this;
    }

    protected function present()
    {
        if ($this->response instanceof StreamResponse) {
            $this->stream($this->response);
            return;
        }

        if ($this->response instanceof DataResponse) {
            // this includes also RedirectOrDataResponse
            $this->data($this->response);
            return;
        }

        if ($this->response instanceof RedirectResponse) {
            $this->redirect($this->response);
            return;
        }

        if ($this->response instanceof NoContentResponse) {
            $this->noContent($this->response);
            return;
        }

        if (!($this->response instanceof ErrorResponse)) {
            $this->response = new ErrorResponse("Unknown Response format", null, 500);
        }
        // this includes also RedirectOrErrorResponse
        $this->error($this->response);
    }

    abstract protected function redirect(RedirectResponse $response);

    abstract protected function data(DataResponse $response);

    abstract protected function error(ErrorResponse $response);

    protected function noContent(NoContentResponse $response)
    {
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(204);
        }
        $this->setHttpCodeAndString($response->getHttpCode(), "204 No Content");
        // data is still null and type is null
    }

    protected function stream(StreamResponse $response)
    {
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(200);
        }

        $this->appendHeader("Content-Type", $response->getContentType());

        if ($response->getSize() !== null && $response->getSize() > 0) {
            $this->appendHeader("Content-Length", (string)$response->getSize());
        }

        if ($response->getFilename() !== null) {
            $this->appendHeader("Content-Disposition", 'attachment; filename="' . $response->getFilename() . '"', false);
        }
        $this->appendHeader("Content-Transfer-Encoding", 'binary', false);

        $this->setHttpCodeAndString($response->getHttpCode(), "200 Ok");

        $this->data = $response->getStream();
        $this->dataType = self::DATA_TYPE_STREAM;
        $this->dataSize = $response->getSize();
        return;
    }
    protected function setHttpCodeAndString(int $code, string $default = "200 Ok")
    {
        $this->httpStatusCode = $code;
        $this->httpString = $this->buildHttpString($code, $default);
    }
    protected function buildHttpString(int $code, string $default = "200 Ok"): string
    {
        switch ($code) {
            case 200:
                return "200 OK";
            case 201:
                return "201 Created";
            case 204:
                return "204 No Content";
            case 400:
                return "400 Bad Request";
            case 401:
                return "401 Unauthorized";
            case 403:
                return "403 Forbidden";
            case 404:
                return "404 Not Found";
            case 405:
                return "405 Method Not Allowed";
            case 416:
                return "416 Requested Range Not Satisfiable";
            case 422:
                return "422 Unprocessable Entity";
            case 500:
                return "500 Internal Server Error";
            case 503:
                return "503 Service Unavailable";
            default:
                return $default;
        }
    }
}