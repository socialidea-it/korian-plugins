<?php

namespace LeadpagesWP\Leadpages;

use LeadpagesWp\Lib\HttpClient\Client;
use LeadpagesWP\Lib\HttpClient\Exceptions\NotFoundException;
use LeadpagesWP\Lib\HttpClient\Exceptions\HttpException;
use LeadpagesWP\Leadpages\LeadpagesLogin;

class Leadbars
{
    const LEADBARS_URL = 'https://api.leadpages.io/content/v1/leadbars/';

    /**
     * @var LeadpagesWp\Lib\HttpClient\Client
     */
    private $client;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    private $login;

    private $headers;

    public function __construct(
        Client $client,
        LeadpagesLogin $login
    ) {
        $this->client = $client;
        $this->login = $login;
        $this->login->getApiKey();

        $this->headers = [
            'headers' => ['Authorization' => 'Bearer '. $this->login->apiKey],
            'sslcertificates' => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
            'timeout' => 10,
        ];
    }

    public function getAllLeadbars()
    {
        try {
            $response = $this->client->get(self::LEADBARS_URL, $this->headers);
            $response = $this->client->toWpResponse($response);
        } catch (HttpException $e) {
            $response = $this->client->toWpResponse($e->response);
        }

        return $response;
    }

    public function buildEmbed($id, $domain = '')
    {
        return
            '<script src="https://static.leadpages.net/leadbars/current/embed.js" '
                .' data-bar="' . $id . '"'
                .' data-bar-domain="'. $domain . '"'
                .' async defer></script>';
    }

    public function getEmbed($id)
    {
        try {
            $url = $this->buildSingleLeadbarUrl($id);
            $response = $this->client->get($url, $this->headers);

            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body, true);

            return $this->buildEmbed($id, $body['content']['publicationDomain']);
        } catch (HttpException $e) {
            return$this->client->toWpResponse($e->response);
        }
    }

    public function buildSingleLeadbarUrl($id)
    {
        return self::LEADBARS_URL . '/' . $id;
    }
}
