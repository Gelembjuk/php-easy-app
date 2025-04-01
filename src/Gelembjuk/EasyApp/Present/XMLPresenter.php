<?php

namespace Gelembjuk\EasyApp\Present;

use \Gelembjuk\EasyApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\EasyApp\Response\DataResponse as DataResponse;
use \Gelembjuk\EasyApp\Response\RedirectResponse as RedirectResponse;

class XMLPresenter extends Presenter {
    const OUTPUT_FORMAT = "xml";

    protected $format_identifier = self::OUTPUT_FORMAT;
        
    protected function redirect(RedirectResponse $response)
    {
        // There is no redirect for JSON. We just return the URL
        $this->appendHeader('Content-Type', 'application/xml');
        
        $data = ['redirect_url' => $response->getUrl()];

        // TODO. Replace with XML
        $jsonStr = json_encode($data);
        
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
        $this->appendHeader('Content-Type', 'application/xml');
        
        $jsonData = ['error' => $response->getMessage()];

        if ($this->context->config->traceErrors) {
            if ($response->getException() !== null) {
                $jsonData['traceback'] = $response->getException()->getTrace();
            }
        }
        // TODO. Replace with XML
        $jsonStr = json_encode($jsonData);

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }

    protected function data(DataResponse $response)
    {
        $this->setHttpCodeAndString($response->getHttpCode(), "200 Ok");
        $this->appendHeader('Content-Type', 'application/XML');

        if ($response->hasCompleteResponse()) {
            $this->data = $response->getCompleteResponse();
            $this->dataType = self::DATA_TYPE_STRING;
            return ;
        }
        // TODO. Replace with XML
        $jsonStr = json_encode($response->getData());

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }
}