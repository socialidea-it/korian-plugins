<?php
namespace LeadpagesWP\Bootstrap;

use LeadpagesWP\Admin\MetaBoxes\LeadpagesCreate;
use LeadpagesWP\Lib\AdminNotices;
use LeadpagesWP\models\LeadboxesModel;
use LeadpagesWP\models\LeadbarsModel;
use LeadpagesWP\Lib\LeadpagesCronJobs;
use LeadpagesWP\Admin\Factories\MetaBoxes;
use LeadpagesWP\Admin\Factories\SettingsPage;
use LeadpagesWP\Admin\MetaBoxes\LeadpageSlug;
use LeadpagesWP\Admin\MetaBoxes\LeadpageType;
use LeadpagesWP\models\LeadPagesPostTypeModel;
use LeadpagesWP\ServiceProviders\LeadboxesApi;
use LeadpagesWP\ServiceProviders\LeadbarsApi;
use LeadpagesWP\Admin\SettingsPages\Leadboxes;
use LeadpagesWP\Admin\SettingsPages\Leadbars;
use LeadpagesWP\Admin\Factories\CustomPostType;
use LeadpagesWP\Admin\MetaBoxes\LeadboxMetaBox;
use LeadpagesWP\Admin\MetaBoxes\LeadpageSelect;
use LeadpagesWP\Admin\TinyMCE\LeadboxTinyMCE;
use LeadpagesWP\Admin\SettingsPages\LeadpagesLoginPage;
use LeadpagesWP\ServiceProviders\WordPressLeadpagesAuth;
use LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType;
use LeadpagesWP\Admin\SettingsPages\LeadpagesLogoutPage;
use LeadpagesWP\Config\LpConfig;

class AdminBootstrap
{
    /**
     * @var \LeadpagesWP\ServiceProviders\LeadpagesLogin
     */
    private $login;

    public $isLoggedIn;

    /**
     * @var \LeadpagesWP\models\LeadPagesPostTypeModel
     */
    private $postTypeModel;

    /**
     * @var \LeadpagesWP\ServiceProviders\LeadboxesApi
     */
    private $leadboxesApi;

    /**
     * @var \LeadpagesWP\ServiceProviders\LeadbarsApi
     */
    private $leadbarsApi;

    /**
     * @var \LeadpagesWP\models\LeadboxesModel
     */
    private $leadboxesModel;

    /**
     * @var \LeadpagesWP\models\LeadbarsModel
     */
    private $leadbarsModel;

    /**
     * @var \LeadpagesWP\admin\SettingsPages\LeadboxTinyMCE
     */
    private $leadboxTinyMCE;

    public function __construct(
        WordPressLeadpagesAuth $login,
        LeadPagesPostTypeModel $postTypeModel,
        LeadboxesApi $leadboxesApi,
        LeadbarsApi $leadbarsApi,
        LeadboxesModel $leadboxesModel,
        LeadbarsModel $leadbarsModel,
        LeadboxTinyMCE $leadboxTinyMCE,
        LpConfig $config
    ) {
        $this->login = $login;
        $this->postTypeModel = $postTypeModel;
        $this->leadboxesApi = $leadboxesApi;
        $this->leadbarsApi = $leadbarsApi;
        $this->leadboxesModel = $leadboxesModel;
        $this->leadbarsModel = $leadbarsModel;
        $this->leadboxTinyMCE = $leadboxTinyMCE;
        $this->config = $config;

        $this->setupLogin();
        $this->setupLeadpages();
        $this->setupLeadboxes();
        $this->setupLeadbars();
        $this->setupAdminNotices();
        add_action('admin_enqueue_scripts', [$this, 'loadStyles']);
    }

    public function setupLogin()
    {
        if (!$this->login->isLoggedIn()) {
            // create login form page if user is not logged in
            SettingsPage::create(LeadpagesLoginPage::getName(), $this->config);

            // register hook to listen for admin post of login form
            $this->login->loginHook();
            return $this->login;
        }

        $this->isLoggedIn = true;
        $this->login->getToken();
        $this->login->getApiKey();
        return $this->login;
    }

    public function setupLeadpages()
    {
        if (!$this->isLoggedIn) {
            return;
        }

        add_filter('post_type_link', [$this, 'leadpagesPermalink'], 1, 2);
        CustomPostType::create(LeadpagesPostType::getName());
        MetaBoxes::create(LeadpagesCreate::getName());
        SettingsPage::create(LeadpagesLogoutPage::getName(), $this->config);
        add_action('admin_enqueue_scripts', [$this, 'loadJS']);

        // force Leadpages Post Type to one column
        add_filter(
            'get_user_option_screen_layout_leadpages_post',
            function () {
                return 1;
            }
        );

        remove_filter('get_user_option_meta-box-order_leadpages_post', [LeadpagesPostType::getName(), 'forceAllMetaboxesInMainColumn']);
        add_filter('get_user_option_meta-box-order_leadpages_post', [LeadpagesPostType::getName(), 'forceAllMetaboxesInMainColumn']);

        // setup hook for saving Leadpages Post Type
        $this->postTypeModel->save();
    }

    public function setupLeadboxes()
    {
        if (!$this->isLoggedIn) {
            return;
        }

        SettingsPage::create(Leadboxes::getName(), $this->config);
        Metaboxes::create(LeadboxMetaBox::getName());
        LeadboxesModel::init();
        LeadboxesModel::saveLeadboxMeta();
        $this->leadboxTinyMCE->init();
    }

    public function setupLeadbars()
    {
        if (!$this->isLoggedIn) {
            return;
        }

        SettingsPage::create(Leadbars::getName(), $this->config);
        LeadbarsModel::init();
    }

    public function loadJS()
    {
        global $leadpagesConfig;

        if ($leadpagesConfig['currentScreen'] == 'leadpages_post') {
            wp_enqueue_script('LeadpagesPostType', $leadpagesConfig['admin_assets'] . '/js/LeadpagesPostType.js?201802', ['jquery']);
            wp_enqueue_script('style-2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', ['jquery']);
        }

        wp_localize_script(
            'LeadpagesPostType',
            'ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'id'       => get_the_ID(),
            ]
        );
    }

    public function loadStyles()
    {
        global $leadpages_connector_plugin_version;
        global $leadpagesConfig;
        if ($leadpagesConfig['currentScreen'] == 'leadpages_post' || $leadpagesConfig['currentScreenAll']->base == 'toplevel_page_Leadboxes') {
            wp_enqueue_style('lp-lego', 'https://static.leadpages.net/lego/1.1.24/lego.min.css');
        }

        wp_enqueue_style('leadpages_admin_css_icons', 'https://static.leadpages.net/icons/v38/lp-icons.css', false, '1.0.0');
        wp_enqueue_style('lp-styles', $leadpagesConfig['admin_css'] . 'styles.css', [], $leadpages_connector_plugin_version);
        wp_enqueue_style('google-font', 'https://fonts.googleapis.com/css?family=Roboto:400,100,300,500,700');
        wp_enqueue_style('style-2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css');
    }

    protected function setupAdminNotices()
    {
        add_action("admin_notices", [AdminNotices::class, 'displayNotices']);

        if (get_option('permalink_structure') == '') {
            add_action('admin_notices', [AdminNotices::class, 'turnOnPermalinks']);
        }

        if ($this->login->isLoggedIn()) {
            return;
        }

        if (!isset($_GET['page']) || $_GET['page'] != "Leadpages") {
            add_action('admin_notices', [AdminNotices::class, 'notLoggedInToLeadpages']);
        }
    }


    public function leadpagesPermalink($url, $post)
    {
        if ('leadpages_post' == get_post_type($post)) {
            $url = str_replace('/leadpages_post/', '/', $url);
        }
        return $url;
    }
}
