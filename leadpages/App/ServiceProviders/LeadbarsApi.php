<?php
namespace LeadpagesWP\ServiceProviders;

use LeadpagesWP\Lib\HttpClient\Client;
use LeadpagesWP\Leadpages\Leadbars;
use LeadpagesWP\Leadpages\LeadpagesLogin;
use LeadpagesWP\Helpers\LeadbarDisplay;

class LeadbarsApi extends Leadbars
{
    use LeadbarDisplay;

    /**
     * @var \LeadpagesWP\Lib\HttpClient\Client
     */
    private $client;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    private $login;

    public function __construct(Client $client, LeadpagesLogin $login)
    {
        parent::__construct($client, $login);
        $this->client = $client;
        $this->login = $login;
        add_action('wp_ajax_nopriv_allLeadbars', [$this, 'allLeadbars']);
        add_action('wp_ajax_allLeadbars', [$this, 'allLeadbars']);
    }

    /**
     * Function for ajax to call to generate dropdowns
     */
    public function allLeadbars()
    {
        $apiResponse = $this->getAllLeadbars();
        $data = json_decode($apiResponse['response'], true);
        echo json_encode($data);
        die();
    }
}

