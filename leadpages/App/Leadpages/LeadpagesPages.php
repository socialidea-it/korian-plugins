<?php

namespace LeadpagesWP\Leadpages;

use LeadpagesWP\Lib\HttpClient\Client;
use LeadpagesWP\Lib\HttpClient\Exceptions\NotFoundException;
use LeadpagesWP\Lib\HttpClient\Exceptions\HttpException;

use LeadpagesWP\Leadpages\LeadpagesLogin;
use LeadpagesWp\Config\LpConfig;

class LeadpagesPages
{
    /**
     * @var \LeadpagesWP\Lib\HttpClient\Client
     */
    private $client;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    private $login;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    public $response;

    public $certFile;


    public function __construct(Client $client, LeadpagesLogin $login, LpConfig $config)
    {
        $this->client = $client;
        $this->login = $login;
        $this->config = $config;
        $this->pagesUrl = $this->config->get("PAGES_API_URL") . "pages";
        $this->certFile = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
    }

    /**
     * Base function get call get users pages
     *
     * @param bool $cursor default false
     *
     * @return array
     */
    public function getPages($cursor = false)
    {
        $queryArray = ['pageSize' => 200];
        if ($cursor) {
            $queryArray['cursor'] = $cursor;
        }

        try {
            $response = $this->client->get(
                $this->pagesUrl,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '. $this->login->apiKey,
                    ],
                    'sslcertificates' => $this->certFile,
                    'query' => $queryArray,
                    'timeout' => 10,
                ]
            );
            $response = $this->client->toWpResponse($response);
        } catch (HttpException $e) {
            $response = $this->client->toWpResponse($e->response);
        }

        return $response;
    }

    /**
     * Recursive function to get all of a users pages
     *
     * @param array $returnResponse
     * @param bool  $cursor
     *
     * @return mixed
     */
    public function getAllUserPages($returnResponse = [], $cursor = false)
    {
        if (empty($this->login->apiKey)) {
            $this->login->getApiKey();
        }

        //get & parse response
        $response = $this->getPages($cursor);
        $response = json_decode($response['response'], true);

        $appUrl = $this->config->get('LEADPAGES_URL');
        if (empty($returnResponse) && empty($response['_items'])) {
            echo '
                <p><strong>You appear to have no Leadpages created yet.</strong></p>
                <p> Please login to
                <a href="' . $appUrl .'" target="_blank">Leadpages</a>
                and create a Leadpage to continue.</p>';
            die();
        }

        $items = $response['_items'];
        $meta = $response['_meta'];

        // if we have more pages,
        // add these pages to returnResponse
        // and pass it back into this method to run again
        if ($meta['hasMore'] == 'true') {
            $returnResponse[] = $items;
            $nextCursor = $meta['nextCursor'];
            return $this->getAllUserPages($returnResponse, $nextCursor);
        }

        // once we run out of hasMore pages
        // return the response with all pages returned
        $returnResponse[] = $response['_items'];

        /**
         * For recursive and compatibility with other functions
         * needed all items to be under one array under _items array
         */
        if (!empty($returnResponse)) {
            $pages = ['_items' => []];
            foreach ($returnResponse as $subarray) {
                $pages['_items'] = array_merge($pages['_items'], $subarray);
            }

            // strip out unpublished pages
            // sort pages asc by name
            return $this->sortPages($this->stripB3NonPublished($pages));
        }
    }

    /**
     * Remove non published B3 pages
     *
     * @param mixed $pages list of pages
     *
     * @return mixed
     */
    public function stripB3NonPublished($pages)
    {
        foreach ($pages['_items'] as $index => $page) {
            if ($page['isBuilderThreePage'] && !$page['isBuilderThreePublished']) {
                unset($pages['_items'][$index]);
            }
        }

        return $pages;
    }

    /**
     * Sort pages in alphabetical user
     *
     * @param mixed $pages list of pages
     *
     * @return mixed
     */
    public function sortPages($pages)
    {
        usort(
            $pages['_items'],
            function ($a, $b) {
                return strcmp(strtolower($a["name"]), strtolower($b["name"]));
            }
        );

        return $pages;
    }

    /**
     * Get the url to download the page url from
     *
     * @param string $pageId page id
     *
     * @return array
     */
    public function getSinglePageDownloadUrl($pageId)
    {
        try {
            $response = $this->client->get(
                $this->pagesUrl . '/' . $pageId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer '. $this->login->apiKey,
                    ],
                    'sslcertificates' => $this->certFile,
                ]
            );

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $url = $body['_meta']['publishUrl'];
            $responseText = ['url' => $url];

            $response = [
                'code' => '200',
                'response' => json_encode($responseText),
                'error' => false
            ];
        } catch (NotFoundException $e) {
            $response = [
                'code' => wp_remote_retrieve_response_code($e->response),
                'response' => "
                    Your Leadpage could not be found!
                    Please make sure it is published in your Leadpages Account <br />
                    <br />
                    Support Info:<br />
                    <strong>Page id:</strong> {$pageId} <br />
                    <strong>Page url:</strong> {$this->PagesUrl}/{$pageId}",
                'error' => true,
            ];
        } catch (HttpException $e) {
            $response = $this->client->toWpResponse($e->response);
            $message = "Something went wrong, please contact Leadpages support ({$response['response']})";
            $response['response'] = $message;
        }

        return $response;
    }

    /**
     * Get url for page,
     * then use a get request to get the html for the page
     *
     * @param string $pageId  Leadpages Page id not wordpress post_id
     * @param bool   $isRetry true downgrades to http
     *
     * @todo refactor this!! kill retry downgrade
     * @todo replace with a single call to get the html
     *
     * @return mixed
     */
    public function downloadPageHtml($pageId, $isRetry = false)
    {
        if (is_null($this->login->apiKey)) {
            $this->login->apiKey = $this->login->getApiKey();
        }

        $response = $this->getSinglePageDownloadUrl($pageId);

        if ($response['error']) {
            return $response;
        }

        $responseArray = json_decode($response['response'], true);
        $url = $responseArray['url'];

        if ($isRetry) {
            $url = str_replace('https:', 'http:', $url);
        }

        $options = [
            'sslverify' => !$isRetry ? $this->certFile : false,
        ];

        foreach ($_COOKIE as $index => $value) {
            if ($index === 'variation') {
                $cookie = new \WP_Http_Cookie($index);
                $cookie->name = $index;
                $cookie->value = $value;
                $options['cookies'] = [$cookie];
            }
        }

        try {
            $html = $this->client->get($url, $options);

            $cookieHeader = wp_remote_retrieve_header($html, 'Set-Cookie');
            $splitTestCookie = $this->getPageSplitTestCookie($cookieHeader);

            $response = $this->client->toWpResponse($html);
            if (count($splitTestCookie) > 0) {
                $response['splitTestCookie'] = $splitTestCookie;
            }
        } catch (ClientException $e) {
            $response = $this->client->toWpResponse($response);
        } catch (RequestException $e) {
            $response = $this->client->toWpResponse($response);
            if (!$isRetry) {
                $response = $this->downloadPageHtml($pageId, true);
                error_log("Retrying fetching landing page, downgrading to http for pageId: [{$pageId}]");
            }
        } catch (RequestFailureException $e) {
            $response = $this->client->toWpResponse($response);
        }

        return $response;
    }

    /**
     * Return an array containing the splittest cookie, if present.
     *
     * @param string $cookieHeader The cookie header
     *
     * @return array
     */
    public function getPageSplitTestCookie($cookieHeader)
    {
        // Cookies can be an array, from two set-cookie headers.
        if (is_array($cookieHeader)) {
            $cookieHeader = implode('; ', $cookieHeader);
        }
        $cookieArray = [];
        $cookie = $this->client->parseCookieString($cookieHeader);
        if ($cookie['Name'] === 'variation') {
            $cookieArray = $cookie;
        }
        return $cookieArray;
    }
}
