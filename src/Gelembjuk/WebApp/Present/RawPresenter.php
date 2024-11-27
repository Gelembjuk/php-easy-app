<?php

namespace Gelembjuk\WebApp\Present;

use \Gelembjuk\WebApp\Response\ErrorResponse as ErrorResponse;
use \Gelembjuk\WebApp\Response\DataResponse as DataResponse;
use \Gelembjuk\WebApp\Response\RedirectResponse as RedirectResponse;

class RawPresenter extends Presenter {
    const OUTPUT_FORMAT = "raw";

    protected $format_identifier = self::OUTPUT_FORMAT;
        
    protected function redirect(RedirectResponse $response)
    {
        $this->appendHeader('Content-Type', 'text/plain', false);
        $this->data = "Redirect: "+$response->getUrl();
        $this->dataType = self::DATA_TYPE_STRING;
        return;
    }

    protected function error(ErrorResponse $response)
    {
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(200);
        }
        $this->setHttpCodeAndString($response->getHttpCode(), "500 Internal Server Error");
        $this->appendHeader('Content-Type', 'text/plain', false);
        
        $data = 'Error: '.$response->getMessage()."\n";

        if ($this->context->config->traceErrors) {
            if ($response->getException() !== null) {
                foreach ($response->getException()->getTrace() as $trace) {
                    $data .= $trace['file'].' '.$trace['line']."\n";
                }
            }
        }

        $this->data = $data;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }

    protected function data(DataResponse $response)
    {
        if ($response->getHttpCode() == 0) {
            $response->withHttpCode(200);
        }

        $this->appendHeader('Content-Type', 'text/plain', false);

        if ($response->hasCompleteResponse()) {
            $this->data = $response->getCompleteResponse();
            $this->dataType = self::DATA_TYPE_STRING;
            return ;
        }

        $data = '';

        $array = $response->getData();
        $is_list = array_is_list($array);

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
            if ($is_list) {
                $data .= "$v\n";
            } else {
                $data .= "$k = $v\n";
            }
        }
        if ($response->getTemplate() != "") {
            $data .= "Data template: " . $response->getTemplate();
        }

        $this->data = $data;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }
}
