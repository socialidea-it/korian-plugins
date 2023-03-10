<?php
namespace LeadpagesWP\Front\Controllers;

use LeadpagesWP\Leadpages\LeadpagesPages;
use LeadpagesWP\Helpers\LeadpageType;
use LeadpagesMetrics\LeadpagesErrorEvent;
use LeadpagesWP\Helpers\PasswordProtected;
use LeadpagesWP\models\LeadPagesPostTypeModel;

class LeadpageController
{
    public $postPassword;

    /**
     * @var \LeadpagesWP\models\LeadPagesPostTypeModel
     */
    private $leadpagesModel;

    /**
     * @var \LeadpagesWP\Front\Controllers\NotFoundController
     */
    private $notfound;

    /**
     * @var \LeadpagesWP\Front\Controllers\WelcomeGateController
     */
    private $welcomeGate;

    /**
     * @var \LeadpagesWP\Leadpages\LeadpagesPages
     */
    private $pagesApi;

    /**
     * @var \LeadpagesWP\Helpers\PasswordProtected
     */
    private $passwordChecker;

    public function __construct(
        NotFoundController $notfound,
        WelcomeGateController $welcomeGate,
        LeadPagesPostTypeModel $leadpagesModel,
        LeadpagesPages $pagesApi,
        PasswordProtected $passwordChecker
    ) {
        $this->leadpagesModel = $leadpagesModel;
        $this->notfound = $notfound;
        $this->welcomeGate = $welcomeGate;
        $this->pagesApi = $pagesApi;
        $this->passwordChecker = $passwordChecker;
    }

    /**
     * Check to see if current page is front page and if so see if a front
     * leadpage exists to display it
     *
     * @param mixed $posts posts
     *
     * @return mixed
     */
    public function displayFrontPage($posts)
    {
        // woocommerce ajax uses root url as controller, pass-through
        $isWcAjax = !empty($_GET["wc-ajax"]);
        if ($isWcAjax) {
            return $posts;
        }

        if (!is_front_page()) {
            return $posts;
        }

        // Check for published Leadpage as homepage
        $post = LeadpageType::getFrontLeadpage();
        if ($post <= 0) {
            return $posts;
        }

        // if the post does not exist remove the option
        // from the db
        $postExists = self::checkLeadpagePostExists($post);
        if (!$postExists) {
            self::deleteOrphanPost('leadpages_front_page_id');
            return $posts;
        }

        $pageId = $this->leadpagesModel->getLeadpagePageId($post);
        if ($pageId == '') {
            return $posts;
        }

        $getCache = get_post_meta($post, 'cache_page', true);
        if ($getCache == 'true') {
            $html = $this->leadpagesModel->getCacheForPage($pageId);
            if (empty($html)) {
                $apiResponse = $this->pagesApi->downloadPageHtml($pageId);
                $html = $apiResponse['response'];
                $this->leadpagesModel->setCacheForPage($pageId);
            }
        } else {
            // no cache download html
            $apiResponse = $this->pagesApi->downloadPageHtml($pageId);
            if ($apiResponse['error']) {
                return $posts;
            }
            $html = $apiResponse['response'];
        }

        LeadpageType::renderHtml($html);
        LeadpageType::preventDefault();
    }

    /**
     * Display WelcomeGate Page
     *
     * @param mixed $posts posts
     *
     * @return mixed
     */
    public function displayWelcomeGate($posts)
    {
        if (is_home() || @is_front_page()) {
            return $this->welcomeGate->displayWelcomeGate($posts);
        }
        return $posts;
    }

    /**
     * Display a normal lead page if page type is a leadpage
     */
    public function displayNotFoundPage()
    {
        $this->notfound->displayNotFoundPage();
    }

    /**
     * Echos a normal Leadpage type html if the post type is leadpages_post
     */
    public function normalPage()
    {
        // get page uri
        $requestedPage = $this->parseRequest();
        if (false == $requestedPage) {
            return false;
        }

        // get post from database including meta data
        $post = LeadPagesPostTypeModel::getAllPosts($requestedPage[0]);
        if ($post == false
            || isset($post['leadpages_post_type'])
            && $post['leadpages_post_type'] == 'nf'
        ) {
            return false;
        }

        //ensure we have the leadpages page id
        if (isset($post['leadpages_page_id'])) {
            $pageId = $post['leadpages_page_id'];
        } elseif (isset($post['leadpages_my_selected_page'])) {
            $pageId = $this->leadpagesModel->getPageByXORId($post['post_id'], $post['leadpages_my_selected_page']);
        } else {
            return false;
        }

        if (empty($pageId)) {
            return false;
        }

        // check cache
        $getCache = get_post_meta($post['post_id'], 'cache_page', true);

        if ($getCache == 'true') {
            $html = $this->leadpagesModel->getCacheForPage($pageId);
            // failsafe incase the cache is not set for some reason
            // get html and set cache
            if (empty($html)) {
                $apiResponse = $this->pagesApi->downloadPageHtml($pageId);
                $html = $apiResponse['response'];
                $this->leadpagesModel->setCacheForPage($pageId);
            }
        } else {
            $apiResponse = $this->pagesApi->downloadPageHtml($pageId);

            if ($apiResponse['error']) {
                return;
            }

            $html = $apiResponse['response'];

            if (isset($apiResponse['splitTestCookie'])) {
                $cookie = $apiResponse['splitTestCookie'];
                setcookie(
                    $cookie['Name'],
                    $cookie['Value'],
                    $cookie['Expires']
                );
            }
        }

        LeadpageType::renderHtml($html);
        LeadpageType::preventDefault();
    }

    public function parseRequest()
    {
        // get current url
        $current = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // calculate the path
        $part = substr($current, strlen(home_url()));
        if (!empty($part) && $part[0] == '/') {
            $part = substr($part, 1);
        }

        // strip parameters
        $real = explode('?', $part);
        $tokens = explode('/', $real[0]);
        $permalinkStructure = $this->cleanPermalinkForLeadpage();
        $tokens = array_diff($tokens, $permalinkStructure);

        foreach ($tokens as $index => $token) {
            if (empty($token)) {
                unset($tokens[$index]);
            } else {
                //decode url entities such as %20 for space
                $tokens[$index] = urldecode($token);
            }
        }
        $tokens = array_values($tokens);
        $newTokens[0] = implode('/', $tokens);

        return $newTokens;
    }

    /**
     * Check if wp post exists by id
     *
     * Example from: https://gist.github.com/tommcfarlin/50d593e7ae63fe03a6bb#file-post-exists-php
     *
     * @param string $postId wp post id
     *
     * @return boolean
     */
    public static function checkLeadpagePostExists($postId)
    {
        return is_string(get_post_status($postId));
    }

    public function cleanPermalinkForLeadpage()
    {
        $permalinkStructure = explode('/', get_option('permalink_structure'));
        foreach ($permalinkStructure as $key => $value) {
            if (empty($value) || strpos($value, '%') !== false) {
                unset($permalinkStructure[$key]);
            }
        }
        return $permalinkStructure;
    }

    /**
     * @param string $postType Post type of leadpages_front_page_id or welcome gate
     */
    public static function deleteOrphanPost($postType)
    {
        delete_option($postType);
    }
}
