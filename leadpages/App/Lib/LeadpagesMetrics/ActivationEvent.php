<?php
namespace LeadpagesMetrics;

use LeadpagesWP\Lib\HttpClient\Client;
use LeadpagesMetrics\WordPressEventEmail;
use LeadpagesMetrics\Traits\ActiveInstallUpdate;

class ActivationEvent extends Events
{
    use ActiveInstallUpdate;

    protected $event = 'activated';

    public function buildUrl()
    {
        return $this->eventUrl . 'activation/';
    }

    public function storeEvent($body = [])
    {
        $this->buildClient();
        $url = $this->buildUrl();
        $headers = [
          'Content-Type' => 'application/json'
        ];

        $body = [
            "email_address" => WordPressEventEmail::getEventEmail(),
        ];

        $body = $this->buildBodyJson($body);

        try {
            $response = $this->client->post(
                $url,
                [
                    'headers' => $headers,
                    'body'    => $body
                ]
            );
            ActiveInstallUpdate::incrementActiveInstalls($this->client, $this->eventUrl);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }

        update_option('lp-response', $body);
    }
}
