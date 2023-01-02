<?php
namespace LeadpagesWP\Front\Controllers;

use LeadpagesWP\models\LeadbarsModel;
use LeadpagesWP\ServiceProviders\LeadbarsApi;

/**
 * Class LeadbarController
 *
 * @package LeadpagesWP\Front\Controllers
 */

class LeadbarController
{
    /**
     * @var
     */
    protected $postType;

    /**
     * @var
     */
    private $leadbarApi;

    /**
     * LeadbarController constructor.
     *
     * @param \LeadpagesWP\ServiceProviders\LeadbarsApi $leadbarApi
     */
    public function __construct(LeadbarsApi $leadbarApi)
    {
        $this->leadbarApi = $leadbarApi;
        $this->globalLeadbar = $this->getLeadbarEmbed();
    }

    public function initLeadbars()
    {
        global $post;
        if (empty($post) || !$this->existsGlobalLeadbar()) {
            return;
        }

        $this->addEmbedToContent();
    }

    public function existsGlobalLeadbar()
    {
        $bar_settings = LeadbarsModel::getLpBarSettings();
        return !empty($bar_settings['global_leadbar_id']);
    }

    public function getLeadbarEmbed()
    {
        if (!$this->existsGlobalLeadbar()) {
            return '';
        }
        $bar_settings = LeadbarsModel::getLpBarSettings();
        return $bar_settings['global_leadbar_embed'];
    }

    public function addEmbedToContent()
    {
        $leadbarEmbed = $this->getLeadbarEmbed();
        echo $leadbarEmbed;
    }
}
