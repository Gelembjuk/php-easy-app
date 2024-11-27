<?php

namespace Gelembjuk\WebApp\Present;

use \Gelembjuk\WebApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\WebApp\Response\DataResponse as DataResponse;
use \Gelembjuk\WebApp\Response\RedirectResponse as RedirectResponse;

class JSONPresenter extends Presenter {
    const OUTPUT_FORMAT = "json";

    protected $format_identifier = self::OUTPUT_FORMAT;

    private function isPretty():bool 
    {
        return $this->settings['pretty'] ?? false;
    }
        
    protected function redirect(RedirectResponse $response)
    {
        // There is no redirect for JSON. We just return the URL
        $this->appendHeader('Content-Type', 'application/json');
        
        $data = ['redirect_url' => $response->getUrl()];

        if ($this->isPretty()) {
            $jsonStr = json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $jsonStr = json_encode($data);
        }

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }

    protected function error(ErrorResponse $response)
    {
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(500);
        }
        $this->setHttpCodeAndString($response->getHttpCode(), "500 Internal Server Error");
        $this->appendHeader('Content-Type', 'application/json');
        
        $jsonData = ['error' => $response->getMessage()];

        if ($this->context->config->traceErrors) {
            if ($response->getException() !== null) {
                $jsonData['traceback'] = $response->getException()->getTrace();
            }
        }

        if ($this->isPretty()) {
            $jsonStr = json_encode($jsonData, JSON_PRETTY_PRINT);
        } else {
            $jsonStr = json_encode($jsonData);
        }

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }

    protected function data(DataResponse $response)
    {
        $this->setHttpCodeAndString($response->getHttpCode(), "200 Ok");
        $this->appendHeader('Content-Type', 'application/json');

        if ($response->hasCompleteResponse()) {
            $this->data = $response->getCompleteResponse();
            $this->dataType = self::DATA_TYPE_STRING;
            return ;
        }
        if (empty($response->getData())) {
            $jsonStr = '{}';

        } else if ($this->isPretty()) {
            $jsonStr = json_encode($response->getData(), JSON_PRETTY_PRINT);
            
        } else {
            $jsonStr = json_encode($response->getData());
        }

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }
}