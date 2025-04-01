<?php

namespace Gelembjuk\EasyApp;

class Config {
    public $applicationEndpointPrefix = '';

    public $traceErrors = false;

    public function __construct(bool $readEnv = false, string $envFilePath = "", string $jsonFilePath = "") 
    {
        // first read json file. It can overwrite properties of this class
        if ($jsonFilePath) {
            $this->readJSON($jsonFilePath);
        }
        // read env file. It can overwrite properties of this class
        if (!empty($envFilePath)) {
            $this->readEnvFile($envFilePath);
        }
        if ($readEnv) {
            $this->readEnv();
        }
    }
    private function readJSON($jsonFilePath) 
    {
        if (!file_exists($jsonFilePath)) {
            return;
        }
        
        $json = file_get_contents($jsonFilePath);
        $data = json_decode($json, true);

        if (is_array($data)) {
            foreach($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
    private function readEnvFile($envFilePath) 
    {
        if (!file_exists($envFilePath)) {
            return;
        }

        $env = parse_ini_file($envFilePath);
        
        if (is_array($env)) {
            $allProperties = get_object_vars($this);
            
            foreach($env as $key => $value) {
                foreach ($allProperties as $property => $v) {
                    
                    if (strtolower($key) == strtolower($property)) {
                        $this->$property = $value;
                    }
                }
            }
        }
    }
    private function readEnv() 
    {
        if (is_array($_ENV)) {
            foreach($_ENV as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
}