<?php
namespace TheLoop\Providers;

use TheLoop\Contracts\HttpClient;

class WordPressHttpClient implements HttpClient
{
    public $url;
    public $args = [];

    public function get($url)
    {
        return wp_remote_get($url, $this->args);
    }

    public function post($url)
    {
        return wp_remote_post($url, $this->args);
    }

    public function patch($url)
    {
        // TODO: Implement patch() method.
    }

    public function delete($url)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Set args. ha
     *
     * @param array $args args
     *
     * return $this
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    public function getArgs()
    {
        return $this->args;
    }
}
