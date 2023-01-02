<?php
namespace LeadpagesWP\ServiceProviders;

use LeadpagesWP\Leadpages\Leadboxes;
use LeadpagesWP\Leadpages\LeadpagesLogin;
use LeadpagesWP\Lib\HttpClient\Client;
use LeadpagesWP\Helpers\LeadboxDisplay;
use LeadpagesWP\Config\LpConfig;

class LeadboxesApi extends Leadboxes
{
    use LeadboxDisplay;

    /**
     * @var \LeadpagesWP\Lib\HttpClient\Client
     */
    private $client;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesLogin
     */
    private $login;

    public function __construct(Client $client, LeadpagesLogin $login, LpConfig $config)
    {
        parent::__construct($client, $login, $config);
        $this->client = $client;
        $this->login = $login;
        add_action('wp_ajax_nopriv_allLeadboxesAjax', [$this, 'allLeadboxesAjax']);
        add_action('wp_ajax_allLeadboxesAjax', [$this, 'allLeadboxesAjax']);
    }

    /**
     * Function for ajax to call to generate dropdowns
     *
     * @return none
     */
    public function allLeadboxesAjax()
    {
        $apiResponse = $this->getAllLeadboxes();
        $allLeadBoxes = json_decode($apiResponse['response'], true);
        $data = [
          'timedLeadboxes' => $this->timedDropDown($allLeadBoxes),
          'exitLeadboxes' => $this->exitDropDown($allLeadBoxes),
        ];

        die(json_encode($data));
    }
}
