<?php
/**
 * Plugin Name: Leadpages Connector
 * Plugin URI: https://www.leadpages.net
 * Description: Connect your Leadpages account to your WordPress site to import your Landing Pages, Pop-ups & Alert Bars
 * Author: Leadpages
 * Version: 2.3.13
 * Author URI: http://leadpages.net
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/c3.php')) {
    include_once __DIR__ . '/c3.php';
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Config/App.php';
require_once __DIR__ . '/Framework/ServiceContainer/ServiceContainer.php';
require_once __DIR__ . '/App/Config/RegisterProviders.php';

use LeadpagesMetrics\ActivationEvent;
use LeadpagesMetrics\DeactivationEvent;
use LeadpagesWP\Lib\LeadpagesCronJobs;
use LeadpagesWP\Lib\Update;

$leadpages_connector_plugin_version = '2.3.13';

define('REQUIRED_PHP_VERSION', 5.4);

/**
 * Check PHP Version for plugin to make sure its compatible
 */

checkPHPVersion($leadpages_connector_plugin_version);

/*
  |--------------------------------------------------------------------------
  | Store events when when plugin is activated and deactivated
  |--------------------------------------------------------------------------
  */
register_activation_hook(__FILE__, function () {
    LeadpagesCronJobs::unregisterCronJobs();
    $activationEvent = new ActivationEvent();
    $activationEvent->storeEvent();
});

register_deactivation_hook(__FILE__, function () {
    // remove cron task upon deactivation
    Update::checkDeactivation();

    $deactivationEvent = new DeactivationEvent();
    $deactivationEvent->storeEvent();
});

/**
 * Remove cronjobs from previous plugin versions <2.1.6.4
 *
 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/upgrader_process_complete
 */
add_action('upgrader_process_complete', 'upgrade_plugin_handler', 10, 2);

function upgrade_plugin_handler($upgrader_object, $options) {
    global $leadpagesApp;

    $current_plugin_path_name = plugin_basename(__FILE__);
    if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
        foreach ($options['plugins'] as $each_plugin) {
            if ($each_plugin == $current_plugin_path_name) {
                LeadpagesCronJobs::unregisterCronJobs();
                $leadpagesApp['leadpagesLogin']->checkAndCreateApiKey();
            }
        }
    }
}

/**
 * Register Auto Update
 */
require_once __DIR__ . '/App/Lib/Update.php';
$update = new Update();
$update->registerAutoUpdate();
$update->scheduleCacheUpdates();

/**
 * Admin Bootstrap
 */

if (is_admin() || is_network_admin()) {
    $adminBootstrap = $leadpagesApp['adminBootstrap'];
}

function getScreen()
{
    global $leadpagesConfig;
    $screen = get_current_screen();
    $leadpagesConfig['currentScreen'] = $screen->post_type;
    $leadpagesConfig['currentScreenAll'] = $screen;
}

add_action('current_screen', 'getScreen');


/**
 * Front Bootstrap
 */
$isPreview = isset($_GET['preview']);
$isSitemapRequest = preg_match('/sitemap(-+([a-zA-Z0-9_-]+))?\.xml$/', $_SERVER['REQUEST_URI']);
if (!is_admin() && !is_network_admin() && !$isPreview && !$isSitemapRequest) {
    $frontBootstrap = $leadpagesApp['frontBootstrap'];
}


// Check PHP VERSION BEFORE ANYTHING
function checkPHPVersion($plugin_version)
{
    if (version_compare(PHP_VERSION, REQUIRED_PHP_VERSION, '<')) {
        $activePlugins = get_option('active_plugins', true);
        foreach ($activePlugins as $key => $plugin) {
            if ($plugin == 'leadpages/leadpages.php') {
                unset($activePlugins[$key]);
            }
        }

        update_option('active_plugins', $activePlugins);

        wp_die('<p>The <strong>Leadpages&reg;</strong> plugin version ' . $plugin_version . ' requires php version <strong> ' . REQUIRED_PHP_VERSION . ' </strong> or greater.</p>
    <p>You are currently using <strong>' . PHP_VERSION . '</strong></p>
    <p>Please use plugin version 1.2', 'Plugin Activation Error', ['back_link' => true]);
    }
}

function isApiKeyCreated()
{
    global $leadpagesApp;
    if (! get_option('leadpages_api_key')) {
        $leadpagesApp['leadpagesLogin']->checkAndCreateApiKey();
    }
}

function version24Update()
{
    global $leadpages_connector_plugin_version;
    if (get_option('leadpages_24_update') != true) {
        updateLeadpagesToPublished();
        updatePostNamesToTitle();
        update_option('leadpages_24_update', true);
    }
}

function updateLeadpagesToPublished()
{
    global $wpdb;
    $query = <<<BOQ
        UPDATE {$wpdb->prefix}posts
        SET post_status = 'publish'
        WHERE post_type = 'leadpages_post'
         AND post_status = 'draft'
BOQ;
    $wpdb->get_results($query);
}

function updatePostNamesToTitle()
{
    global $wpdb;
    $posts = $wpdb->get_results("
       SELECT *
       FROM {$wpdb->prefix}posts
       WHERE post_type = 'leadpages_post'
    ");

    foreach ($posts as $post) {
        $title = $post->post_title;
        if ($title !== '/') {
            $title = ltrim($title, '/');
        }

        $post_update_name = [
          'post_name' => $title,
        ];

        $wpdb->update("{$wpdb->prefix}posts", $post_update_name, ['ID' => $post->ID]);
    }
}

add_action('admin_init', 'version24Update');
add_action('wp_loaded', 'isApiKeyCreated');

add_filter('custom_menu_order', 'lpMenuOrder');
function lpMenuOrder($menu_ord) {
    global $submenu;
    $index = 'edit.php?post_type=leadpages_post';
    if (array_key_exists($index, $submenu)) {
        $lpMenu = $submenu[$index];
        $submenu[$index] = [
            $lpMenu[5],  // Landing Pages
            $lpMenu[12], // Pop-ups
            $lpMenu[13], // Alert Bars
            $lpMenu[11], // Log out
        ];
    }
    return $menu_ord;
}

function lpAddAdminToast($message = "", $level = "error")
{
    $key = "leadpages_admin_notices";

    $notices = get_option($key, []);
    $notices[] = ["message" => $message, "level" => $level];

    update_option($key, array_slice($notices, -3, 3));
}
