<?php

namespace Gelembjuk\WebApp\Present;

class Builder {
    private static $defaultOutputFormat = HTMLPresenter::OUTPUT_FORMAT;

    public static function setDefaultOutputFormat(string $outputFormat) 
    {
        self::$defaultOutputFormat = $outputFormat;
    }
    public static function createPresenter(
        \Gelembjuk\WebApp\Context $context, 
        \Gelembjuk\WebApp\Response\Response $response, 
        string $outputFormat): Presenter 
    {
        // Factory method to create presenter based on the output format
        // HTML is default format

        if ($outputFormat == "") {
            $outputFormat = self::$defaultOutputFormat;
        }

        if ($outputFormat == HTMLPresenter::OUTPUT_FORMAT) {
            return new HTMLPresenter($context, $response);
        }
        
        if ($outputFormat == JSONPresenter::OUTPUT_FORMAT) {
            return new JSONPresenter($context, $response);
        }
        
        if ($outputFormat == XMLPresenter::OUTPUT_FORMAT) {
            return new XMLPresenter($context, $response);
        }
        
        if ($outputFormat == RawPresenter::OUTPUT_FORMAT) {
            return new RawPresenter($context, $response);
        }
        
        throw new \Exception("Unknown output format: " . $outputFormat);
    }
}