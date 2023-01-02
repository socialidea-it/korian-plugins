<?php

namespace LeadpagesWP\Config;

class LpConfig
{
    private $config;

    public function __construct($env)
    {
        $filename = __DIR__ . '/'. $env . '.config.php';
        $this->config = require_once $filename;
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        throw new \Exception('Key "' . $key . '" not found.');
    }

    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }
}
