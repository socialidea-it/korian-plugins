<?php

/**
 * A Guzzle-like HTTP client that wraps the Wordpress HTTP API.
 */

namespace LeadpagesWP\Lib\HttpClient;

use LeadpagesWP\Lib\HttpClient\Exceptions\HttpException;
use LeadpagesWP\Lib\HttpClient\Exceptions\RequestException;
use LeadpagesWP\Lib\HttpClient\Exceptions\RequestFailureException;
use LeadpagesWP\Lib\HttpClient\Exceptions\BadResponseException;
use LeadpagesWP\Lib\HttpClient\Exceptions\NotFoundException;
use LeadpagesWP\Lib\HttpClient\Exceptions\ClientException;
use LeadpagesWP\Lib\HttpClient\Exceptions\ServerException;

class Client
{
    private static $methods = ['delete', 'head', 'get', 'post', 'put'];
    private static $cookieDefaults = [
        'Name'     => null,
        'Value'    => null,
        'Domain'   => null,
        'Path'     => '/',
        'Max-Age'  => null,
        'Expires'  => null,
        'Secure'   => false,
        'Discard'  => false,
        'HttpOnly' => false
    ];

    public function __call($name, $args)
    {
        if (!in_array($name, static::$methods)) {
            throw new \InvalidArgumentException("Unknown function or method {$name}");
        }
        if (count($args) < 1) {
            throw new \InvalidArgumentException("Request method missing required URL argument");
        }

        $method = $name;
        $uri = $args[0];
        $opts = isset($args[1]) ? $args[1] : [];

        return $this->request($method, $uri, $opts);
    }

    private function call($method, $url, $options = [])
    {
        $options['method'] = strtoupper($method);

        $qs = '';
        if (array_key_exists('query', $options)) {
            $queryArgs = $options['query'];
            // query is not part of the WP HTTP API, so strip it here.
            unset($options['query']);
            $qs = http_build_query($queryArgs);
            $qs = $qs ? "?{$qs}" : '';
        }

        $uri = "{$url}{$qs}";
        return wp_remote_request($uri, $options);
    }

    public function request($method, $url, $options = [])
    {
        $response = $this->call($method, $url, $options);
        if (is_wp_error($response)) {
            throw new RequestFailureException($response);
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode >= 500) {
            throw new ServerException($response);
        }
        if ($statusCode == 404) {
            throw new NotFoundException($response);
        }
        if ($statusCode >= 400) {
            throw new ClientException($response);
        }
        return $response;
    }

    public function toWpResponse($response)
    {
        if (is_wp_error($response)) {
            return [
                'code' => $response->get_error_code(),
                'response' => $response->get_error_message(),
                'error' => true,
            ];
        }
        return [
            'code' => wp_remote_retrieve_response_code($response),
            'response' => wp_remote_retrieve_body($response),
            'error' => false,
        ];
    }

    /* Adapted from SetCookie::fromString from GuzzleHttp\Cookie\SetCookie.
     * https://github.com/guzzle/guzzle/blob/f221d7f3287e84e3b157f090d87f39449c6ee413/src/Cookie/SetCookie.php#L32
     *
     *
     * @param string $cookie Set-Cookie header string
     *
     * @return array A key-value array of cookies
     *
     */
    public function parseCookieString($cookie)
    {
        $data = static::$cookieDefaults;
        // Explode the cookie string using a series of semicolons
        $pieces = array_filter(array_map('trim', explode(';', $cookie)));
        // The name of the cookie (first kvp) must exist and include an equal sign.
        if (empty($pieces[0]) || !strpos($pieces[0], '=')) {
            return $data;
        }
        // Add the cookie pieces into the parsed data array
        foreach ($pieces as $part) {
            $cookieParts = explode('=', $part, 2);
            $key = trim($cookieParts[0]);
            $value = isset($cookieParts[1])
                ? trim($cookieParts[1], " \n\r\t\0\x0B")
                : true;
            // Only check for non-cookies when cookies have been found
            if (empty($data['Name'])) {
                $data['Name'] = $key;
                $data['Value'] = $value;
            } else {
                foreach (array_keys(static::$cookieDefaults) as $search) {
                    if (!strcasecmp($search, $key)) {
                        $data[$search] = $value;
                        continue 2;
                    }
                }
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
