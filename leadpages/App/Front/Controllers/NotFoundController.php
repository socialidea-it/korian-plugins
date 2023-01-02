<?php
namespace LeadpagesWP\Front\Controllers;

use LeadpagesWP\Leadpages\LeadpagesPages;
use LeadpagesWP\Helpers\LeadpageType;
use LeadpagesWP\models\LeadPagesPostTypeModel;

class NotFoundController
{
    protected $notFoundPageId;
    protected $notFoundPageUrl;

    /**
     * @var
     */
    private $postTypeModel;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesPages
     */
    private $pagesApi;

    public function __construct(LeadPagesPostTypeModel $postTypeModel, LeadpagesPages $pagesApi)
    {
        $this->postTypeModel = $postTypeModel;
        $this->pagesApi = $pagesApi;
    }

    protected function notFoundPageExists()
    {
        $this->notFoundPageId = LeadpageType::get404Leadpage();
        $postExists = LeadpageController::checkLeadpagePostExists($this->notFoundPageId);
        // if the post does not exist remove the option from the db
        if (!$postExists) {
            LeadpageController::deleteOrphanPost('leadpages_404_page_id');
            return false;
        }

        if (!$this->notFoundPageId) {
            return false;
        }

        return true;
    }

    protected function getNotFoundPageUrl()
    {
        $this->notFoundPageUrl = get_post_meta($this->notFoundPageId, 'leadpages_slug', true);
        return $this->notFoundPageUrl;
    }

    public function displayNotFoundPage()
    {
        if ($this->notFoundPageExists() && is_404()) {
            $pageId = $this->postTypeModel->getLeadpagePageId($this->notFoundPageId);

            //check for cache
            $getCache = get_post_meta($this->notFoundPageId, 'cache_page', true);
            if ($getCache == "true") {
                $html = $this->postTypeModel->getCacheForPage($pageId);
                if (empty($html)) {
                    $apiResponse = $this->pagesApi->downloadPageHtml($pageId);
                    $html = $apiResponse['response'];
                    $this->postTypeModel->setCacheForPage($pageId);
                }
            } else {
                // no cache download html
                $apiResponse = $this->pagesApi->downloadPageHtml($pageId);
                $html = $apiResponse['response'];
            }

            LeadpageType::renderHtml($html, 404);
            LeadpageType::preventDefault();
        }
    }
}
