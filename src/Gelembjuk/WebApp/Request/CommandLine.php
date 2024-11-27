<?php

namespace Gelembjuk\WebApp\Request;

class CommandLine extends AbstractRequest {
    public function __construct()
    {
        parent::__construct();

        global $argv;

        if (empty($argv)) {
            $argv = [];
        }
        // read all arguments.
        foreach($argv as $arg) {

            if (preg_match('/^--method=(.*)$/', $arg, $matches)) {
                $this->request_method = $matches[1];

            } elseif (preg_match('/^--endpoint=(.*)$/', $arg, $matches)) {
                $this->endpoint = $matches[1];

            } elseif (preg_match('/^--header=(.*)$/', $arg, $matches)) {
                $header = explode(':', $matches[1]);
                $this->headers[$header[0]] = trim($header[1]);
                
            } elseif (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $this->data[$matches[1]] = $matches[2];
            }
        }
    }
    public function getScriptName(): string 
    {
        global $argv;
        return $argv[0] ?? '';
    }
    
}