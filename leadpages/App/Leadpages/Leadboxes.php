<?php

namespace LeadpagesWP\Leadpages;

use LeadpagesWp\Lib\HttpClient\Client;
use LeadpagesWP\Lib\HttpClient\Exceptions\NotFoundException;
use LeadpagesWP\Lib\HttpClient\Exceptions\HttpException;
use LeadpagesWP\Leadpages\LeadpagesLogin;
use LeadpagesWP\Config\LpConfig;

class Leadboxes
{
    /**
     * @var LeadpagesWp\Lib\HttpClient\Client
     */
    private $client;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    private $login;

    public $response;

    /**
     * @property string leadboxesUrl
     */
    public $leadboxesUrl;

    public $certFile;

    public function __construct(Client $client, LeadpagesLogin $login, LpConfig $config)
    {
        $this->client = $client;
        $this->login = $login;
        $this->login->getApiKey();
        $this->leadboxesUrl = $config->get("POPUPS_API_URL") . "leadboxes";
        $this->certFile = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
    }

    public function getAllLeadboxes()
    {
        try {
            $response = $this->client->get(
                $this->leadboxesUrl,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->login->apiKey,
                    ],
                    'sslcertificates' => $this->certFile,
                    'timeout' => 10,
                ]
            );
            $response = $this->client->toWpResponse($response);
        } catch (HttpException $e) {
            $response = $this->client->toWpResponse($e->response);
        }

        return $response;
    }


    public function getSingleLeadboxEmbedCode($id, $type)
    {
        try {
            $url = $this->buildSingleLeadboxUrl($id, $type);
            $response = $this->client->get(
                $url,
                [
                    'headers' => ['Authorization' => 'Bearer '. $this->login->apiKey],
                    'sslcertificates' => $this->certFile,
                ]
            );

            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body, true);
            $response = [
                'code' => 200,
                'response' => json_encode(['embed_code' => $body['_items']['publish_settings']['embed_code']]),
                'error' => false,
            ];
        } catch (HttpException $e) {
            $response = $this->client->toWpResponse($e->response);
        }

        return $response;
    }

    public function buildSingleLeadboxUrl($id, $type)
    {
        $queryParams = http_build_query(['popup_type' => $type]);
        return $this->leadboxesUrl . '/' . $id . '?' . $queryParams;
    }
}
