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

        $this->appendHeader('Content-Length', strlen($jsonStr));

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

        $this->context->getLogger('error')->debug($jsonStr);

        $this->appendHeader('Content-Length', strlen($jsonStr));

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
        $dataToEncode = $response->getData();

        $attempt = 0;

        do {
            try {
                if (empty($dataToEncode)) {
                    $jsonStr = '{}';

                } else if ($this->isPretty()) {
                    $jsonStr = json_encode($dataToEncode, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                    
                } else {
                    $jsonStr = json_encode($dataToEncode, JSON_THROW_ON_ERROR);
                }
                // no exception, exit the loop
                break;
            } catch (\Throwable $e) {
                $dataToEncode = $this->utf8izeBrokenData($dataToEncode);
                $jsonStr = '{"error": "Error encoding data to JSON", "message": "'.$e->getMessage().'"}';
            }
        } while($attempt++ < 1);

        $this->appendHeader('Content-Length', strlen($jsonStr));

        $this->data = $jsonStr;
        $this->dataType = self::DATA_TYPE_STRING;
        return ;
    }
    private function utf8izeBrokenData($mixed) 
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8izeBrokenData($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }
}