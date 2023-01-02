<?php

namespace LeadpagesWP\Lib\HttpClient\Exceptions;

class HttpException extends \RuntimeException
{
    public $response;

    public function __construct($response, Exception $previous = null)
    {
        if (is_wp_error($response)) {
            $code = filter_var($response->get_error_code(), FILTER_VALIDATE_INT) ?: null;
            $message = $response->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($response) ?: null;
            $message = "Failed request [{$code}]: " . substr(wp_remote_retrieve_body($response), 0, 1000);
        }

        error_log($message);

        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
