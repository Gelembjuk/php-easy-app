<?php

namespace Gelembjuk\EasyApp\Request;

class CommandLine extends AbstractRequest {
    public function __construct($default_method = 'GET')
    {
        parent::__construct();

        $this->request_method = $default_method;

        global $argv;

        if (empty($argv)) {
            $argv = [];
        }
        // read all arguments.
        foreach($argv as $index => $arg) {

            if (preg_match('/^--method=(.*)$/', $arg, $matches)) {
                $this->request_method = $matches[1];

            } elseif (preg_match('/^--endpoint=(.*)$/', $arg, $matches)) {
                $this->endpoint = $matches[1];

            } elseif (preg_match('/^--action_method=(.*)$/', $arg, $matches)) {
                $this->action_method = $matches[1];

            } elseif (preg_match('/^--header=(.*)$/', $arg, $matches)) {
                $header = explode(':', $matches[1]);
                $this->headers[$header[0]] = trim($header[1]);
                
            } elseif (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $this->data[$matches[1]] = $matches[2];

            } elseif ($index == 1) {
                $this->data['command'] = $arg;
            }
        }
    }
    public function getScriptName(): string 
    {
        global $argv;
        return $argv[0] ?? '';
    }
    
}