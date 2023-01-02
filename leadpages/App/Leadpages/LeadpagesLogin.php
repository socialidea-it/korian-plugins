<?php
namespace LeadpagesWP\Leadpages;

use LeadpagesWp\Lib\HttpClient\Client;
use LeadpagesWP\Lib\HttpClient\Exceptions\ClientException;
use LeadpagesWP\Leadpages\Contracts\LeadpagesToken;
use LeadpagesWP\Config\LpConfig;
use LeadpagesWP\Lib\HttpClient\Exceptions\HttpException;

abstract class LeadpagesLogin implements LeadpagesToken
{
    protected $client;
    public $response;
    public $keyUrl;
    public $loginurl;

    /**
     * Token label that should be used to reference the token in the database
     * for consistency across platforms and upgrades easier
     *
     * @var string
     */
    public $tokenLabel = 'leadpages_security_token';

    public $apiKeyLabel = 'leadpages_api_key';

    public $token;

    public $apiKey;

    public $certFile;

    public function __construct(Client $client, LpConfig $config )
    {
        $this->keyUrl = $config->get("ACCOUNT_API_URL") . 'keys';
        $this->loginurl = $config->get("ACCOUNT_API_URL") . 'sessions';
        $this->client = $client;
        $this->certFile = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
    }

    protected function hashUserNameAndPassword($username, $password)
    {
        return base64_encode($username . ':' . $password);
    }

    /**
     * Get user information
     *
     * @param string $username user
     * @param string $password pass
     *
     * @return array|null
     */
    public function getUser($username, $password)
    {
        $authHash = $this->hashUserNameAndPassword($username, $password);
        $body = json_encode(['clientType' => 'wp-plugin']);
        try {
            $response = $this->client->post(
                $this->loginurl,
                [
                    'headers' => ['Authorization' => 'Basic ' . $authHash],
                    'sslcertificates' => $this->certFile,
                    'body' => $body, //wp-plugin value makes session not expire
                ]
            );
            $this->response = wp_remote_retrieve_body($response);
        } catch (ApiException $e) {
            $this->response = json_encode($this->client->toWpResponse($e->response));
        } catch (HttpException $e) {
            if ($e->response['response']['code'] == 401) {
              $this->response = json_encode([
                'code' => "Username/password incorrect",
                'error' => true
              ]);
            } else {
              $this->response = json_encode([
                'code' => $e->response['response']['code'],
                'error' => true
              ]);
            }
        }

        return $this;
    }

    /**
     * Create an API key for account
     *
     * @return string|boolean JSON encode key or false
     */
    public function createApiKey()
    {
        if (!isset($this->token)) {
            return false;
        }

        $authHeader = 'LP-Security-Token';
        if (stripos($this->token, 'lp ') === 0) {
            $authHeader = 'Authorization';
        }

        try {
            $response = $this->client->post(
                $this->keyUrl,
                [
                    'headers' => [
                        $authHeader => $this->token,
                        'Content-Type' => 'application/json',
                    ],
                    'sslcertificates' => $this->certFile,
                    'body' => json_encode(['label' => 'wordpress-plugin']),
                ]
            );

            $body = json_decode(wp_remote_retrieve_body($response), true);

            $value = false;
            if (array_key_exists('value', $body)) {
                $value = $body['value'];
            }
        } catch (ClientException $e) {
            $value = false;
        }

        return $value;
    }

    /**
     * Parse response for call to Leadpages Login. If response does
     * not contain a error we will return a response with
     * HttpResponseCode and Message
     *
     * @param bool $deleteTokenOnFail default false
     *
     * @return string json encoded response for client to handle
     */
    public function parseResponse($deleteTokenOnFail = false)
    {
        $responseArray = json_decode($this->response, true);
        if (isset($responseArray['error']) && $responseArray['error']) {
            // token should be unset assumed to be no longer valid
            unset($this->token);
            // delete token from data store if param is passed
            if ($deleteTokenOnFail) {
                $this->deleteToken();
            }
            return $this->response;
        }
        $this->token = $responseArray['securityToken'];
        return 'success';
    }

    public function getLeadpagesResponse()
    {
        return $this->response;
    }

    /**
     * Set response property. (testing)
     *
     * @param mixed $response response
     *
     * @return LeadpagesLogin
     */
    public function setLeadpagesResponse($response)
    {
        $this->response = $response;
        return $this;
    }
}
