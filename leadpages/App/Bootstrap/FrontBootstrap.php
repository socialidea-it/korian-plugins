<?php
namespace LeadpagesWP\Bootstrap;

use LeadpagesWP\Leadpages\LeadpagesPages;
use LeadpagesWP\Admin\Factories\CustomPostType;
use LeadpagesWP\Front\ShortCodes\LeadboxShortCodes;
use LeadpagesWP\Front\Controllers\LeadboxController;
use LeadpagesWP\Front\Controllers\LeadbarController;
use LeadpagesWP\Front\Controllers\LeadpageController;
use LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType;
use LeadpagesWP\ServiceProviders\WordPressLeadpagesAuth;

class FrontBootstrap
{
    /**
     * @var \LeadpagesWP\Bootstrap\WordPressLeadpagesAuth
     */
    private $login;

    /**
     * @var \LeadpagesWP\Front\Controllers\LeadpageController
     */
    private $leadpageController;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesPages
     */
    private $pagesApi;

    /**
     * @var \LeadpagesWP\Front\Controllers\LeadboxController
     */
    private $leadboxController;

    /**
     * @var \LeadpagesWP\Front\Controllers\LeadbarController
     */
    private $leadbarController;

    /**
     * @var \LeadpagesWP\Front\ShortCodes\LeadboxShortCodes
     */
    private $leadboxShortCodes;

    /**
     * FrontBootstrap constructor.
     *
     * @param \LeadpagesWP\ServiceProviders\WordPressLeadpagesAuth $login
     * @param \LeadpagesWP\Front\Controllers\LeadpageController    $leadpageController
     * @param \LeadpagesWP\Leadpages\LeadpagesPages                $pagesApi
     * @param \LeadpagesWP\Front\Controllers\LeadboxController     $leadboxController
     * @param \LeadpagesWP\Front\ShortCodes\LeadboxShortCodes      $leadboxShortCodes
     * @param \LeadpagesWP\Front\Controllers\LeadbarController     $leadbarController
     */
    public function __construct(
        WordPressLeadpagesAuth $login,
        LeadpageController $leadpageController,
        LeadpagesPages $pagesApi,
        LeadboxController $leadboxController,
        LeadbarController $leadbarController,
        LeadboxShortCodes $leadboxShortCodes
    ) {
        $this->login              = $login;
        $this->pagesApi           = $pagesApi;
        $this->leadpageController = $leadpageController;
        $this->leadboxController  = $leadboxController;
        $this->leadbarController  = $leadbarController;
        $this->leadboxShortCodes = $leadboxShortCodes;

        if (!$this->login->isLoggedIn()) {
            return;
        }

        $this->setupLeadpages();
        add_filter('post_type_link', [$this, 'leadpagesPermalink'], 1, 2);
        add_filter('the_posts', [$this, 'displayLeadpage'], 1);
        add_filter('wp', [$this->leadpageController, 'displayWelcomeGate']);
        add_action('template_redirect', [$this->leadpageController, 'displayNotFoundPage']);
        add_action('wp', [$this->leadpageController, 'displayFrontPage']);
        add_action('wp', [$this, 'displayLeadboxes']);
        add_action('wp', [$this, 'displayLeadbar']);
        $this->leadboxShortCodes->addLeadboxesShortCode();
    }

    /**
     * Create leadpages custom post type from factory
     */
    public function setupLeadpages()
    {
        CustomPostType::create(LeadpagesPostType::getName());
    }

    /**
     * Display a leadpage if its not a homepage or a 404 page
     *
     * @param mixed $posts posts
     *
     * @return mixed
     */
    public function displayLeadpage($posts)
    {
        if (is_home() || @is_front_page() || is_search() || is_feed()) {
            return $posts;
        }

        $result = $this->leadpageController->normalPage();
        if ($result == false || is_404()) {
            return $posts;
        }
    }

    /**
     * Display leadboxes on normal and 404 pages
     */
    public function displayLeadboxes()
    {
        $init = is_404() ? 'initLeadboxes404' : 'initLeadboxes';
        add_action('get_footer', [$this->leadboxController, $init]);
    }

    /**
     * Display leadbars
     */
    public function displayLeadbar()
    {
        add_action('get_footer', [$this->leadbarController, 'initLeadbars']);
    }


    /**
     * Create url structure for leadpages post type
     * so it does not include leadpages_post in the url
     *
     * @param string $url  url
     * @param string $post post
     *
     * @return string
     */
    public function leadpagesPermalink($url, $post)
    {
        if ('leadpages_post' == get_post_type($post)) {
            $url = str_replace('/leadpages_post/', '/', $url);
        }
        return $url;
    }
}
