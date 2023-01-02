<?php
namespace LeadpagesWP\Lib;

use LeadpagesMetrics\WordPressEventEmail;

/**
 * Class Update
 *
 * @package Leadpages\Admin\Providers
 * Complete copy from old plugin. May need updated at some point
 */
class Update
{
    public function registerAutoUpdate()
    {
        // plugin update information
        add_filter('plugins_api', [&$this, 'updateInformation'], 9, 3);
        // exclude from official updates
        add_filter('http_request_args', [&$this, 'updatesExclude'], 5, 2);
        // check for update twice a day (same schedule as normal WP plugins)
        add_action('lp_check_event', [&$this, 'checkForUpdate']);
        add_filter('transient_update_plugins', [&$this, 'proCheckUpdate']);
        add_filter('site_transient_update_plugins', [&$this, 'proCheckUpdate']);
        // check and schedule next update
        if (!wp_next_scheduled('lp_check_event')) {
            wp_schedule_event(current_time('timestamp'), 'twicedaily', 'lp_check_event');
        }
    }

    /**
     * Remove cron on deactivation
     */
    public static function checkDeactivation()
    {
        wp_clear_scheduled_hook('lp_check_event');
    }

    /**
     * Get plugin
     *
     * @param string $index index of lp plugin?
     *
     * @return string plugin folder
     */
    public function pluginGet($index = 'Version')
    {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $pluginFolder = get_plugins('/leadpages');
        $hasPluginData= isset($pluginFolder)
            && array_key_exists('leadpages.php', $pluginFolder)
            && is_array($pluginFolder['leadpages.php'])
            && array_key_exists($index, $pluginFolder['leadpages.php']);

        if ($hasPluginData) {
            return $pluginFolder['leadpages.php'][$index];
        }
        return '';
    }

    /**
     * Exclude from WP updates
     *
     * @param mixed  $response data from request
     * @param string $url      update endpoint
     *
     * @return response data
     **/
    public static function updatesExclude($response, $url)
    {
        // Not a plugin update request. Bail immediately.
        if (0 !== strpos($url, 'http://api.wordpress.org/plugins/update-check')) {
            return $response;
        }

        $plugins = unserialize($response['body']['plugins']);
        if (isset($plugins->plugins['leadpages'])) {
            unset($plugins->plugins['leadpages']);
            unset($plugins->active[array_search('leadpages', $plugins->active)]);
        }

        $response['body']['plugins'] = serialize($plugins);
        return $response;
    }

    /**
     * Silent update check
     *
     * @return null
     */
    public function silentUpdateCheck()
    {
        // full response if possible is returned
        $result = self::checkForUpdate(true);
        $response = $result[0];
        if (false === $response) {
            self::showMessage(
                false,
                "Error while checking for update. Can't reach update server. Message: " . $result[1]
            );

            return;
        }

        if (isset($response->result) && $response->result == 'ko') {
            self::showMessage(false, $response->message);
            return;
        }

        $latest = $response->version;
        $url = $response->url;
        $current_version = self::pluginGet('Version');
        if ($current_version == $latest || version_compare($current_version, $latest, '>')) {
            return;
        }

        $plugin_file = 'leadpages/leadpages.php';
        $upgradeUrl = admin_url(
            'update.php?action=upgrade-plugin&amp;plugin=' . urlencode($plugin_file),
            'upgrade-plugin_' . $plugin_file
        );

        $message = 'There is a new version of the Leadpages plugin available!'
            .' ( ' . $latest . ' )<br>You can <a href="' . $upgradeUrl . '">update</a> '
            .' to the latest version automatically or <a href="' . $url . '">download</a>'
            .' the update and install it manually.';

        self::showMessage(true, $message);
    }

    /**
     * Check update
     *
     * @param string $option option?
     * @param bool   $cache  cache response
     *
     * @return option
     */
    public function proCheckUpdate($option, $cache = true)
    {
        $response = get_site_transient('leadpages_latest_version');
        if (!$response) {
            $result   = self::lbApiCall('update-check');
            $response = $result[0];
            if ($response === false) {
                return $option;
            }
        }

        $current_version = self::pluginGet('Version');
        if ($current_version == $response->version) {
            return $option;
        }

        if (version_compare($current_version, $response->version, '>')) {
            return $option;
        }

        $plugin_path = 'leadpages/leadpages.php';
        if (empty($option->response[$plugin_path])) {
            $option->response[$plugin_path] = new \stdClass();
        }

        $option->response[$plugin_path]->url         = self::pluginGet('AuthorURI');
        $option->response[$plugin_path]->slug        = 'leadpages';
        $option->response[$plugin_path]->package     = $response->url;
        $option->response[$plugin_path]->new_version = $response->version;
        $option->response[$plugin_path]->id          = "0";

        return $option;
    }

    /**
     * Check for update
     *
     * @param bool $full defaults false
     *
     * @return bool
     */
    public function checkForUpdate($full = false)
    {
        if (defined('WP_INSTALLING')) {
            return false;
        }

        $result = self::lbApiCall('update-check');
        //echo '<pre>'; print_r($result);die();
        $response = $result[0];
        if ($full === true) {
            return $result;
        }

        // error?
        if ($response === false) {
            return [false, $result[1]];
        }

        $currentVersion = self::pluginGet('Version');
        if ($currentVersion == $response->version) {
            return false;
        }

        if (version_compare($currentVersion, $response->version, '>')) {
            return [true, 'You have the latest version!'];
        }

        return [$response->version, 'There is a newer version!'];
    }

    public function updateInformation($false, $action, $args)
    {
        // Check if this plugins API is about this plugin
        if (!isset($args->slug)) {
            return false;
        }

        if ($args->slug != 'leadpages') {
            return $false;
        }
        $result = self::lbApiCall('info');
        $response = $result[0];
        if ($response === false) {
            return $false;
        }

        $response->slug = 'leadpages';
        $response->plugin_name = 'leadpages';
        return $response;
    }

    public function lbApiCall($service)
    {
        global $leadpagesConfig;
        $licence_key = 'upUbSkfvYbd74rYnAl5hWczFlGbnYLCp';
        $url = $leadpagesConfig['update_url'] . '/service/leadpages/' . $service . '/';
        $current_ver = self::pluginGet('Version');
        $response = wp_remote_post(
            $url,
            [
                'method'      => 'POST',
                'timeout'     => 70,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => [],
                'body'        => [
                    'version'     => $current_ver,
                    'licence_key' => $licence_key,
                    'php_version' => PHP_VERSION,
                    'email_address' => WordPressEventEmail::getEventEmail(),
                ],
                'cookies' => []
            ]
        );

        if (is_wp_error($response)) {
            return [false, $response->get_error_message()];
        }

        $code_char = '5';
        if (isset($response['response']['code'])) {
            $code_char = substr($response['response']['code'], 0, 1);
        }

        if ($code_char == '5' || $code_char == '4') {
            return [false, $response['response']['message']];
        }

        $res = json_decode($response['body'], true);
        if (!is_array($res)) {
            return [false, 'Unexpected response. Failed to decode JSON.'];
        }

        if (isset($res['result']) && $res['result'] == 'ko') {
            return [false, $res['message']];
        }

        $r = new \stdClass;
        foreach ($res as $key => $val) {
            $r->$key = $val;
        }

        if ($service == 'update-check') {
            set_site_transient('leadpages_latest_version', $r, 60 * 60 * 12);
        }

        return [$r, 'Everything is good!'];
    }

    private static $message = false;

    public function showMessage($notError, $message)
    {
        self::$message = $message;
        add_action(
            'admin_notices',
            [&$this, $notError ? 'showMessageCallback' : 'showErrorMessage']
        );
    }

    public function showMessageCallback()
    {
        echo '<div id="message" class="updated">';
        echo '<p><strong>' . self::$message . '</strong></p></div>';
    }

    public function showErrorMessage()
    {
        echo '<div id="message" class="error">';
        echo '<p><strong>' . self::$message . '</strong></p></div>';
    }


    public function scheduleCacheUpdates()
    {
        add_action('lp_cache_updates', [$this, 'updateNonSplittTestedPagesCache']);

        if (!wp_next_scheduled('lp_cache_updates')) {
            wp_schedule_event(current_time('timestamp'), 'twicedaily', 'lp_cache_updates');
        }
    }

    /**
     * If a page is from the old plugin and has a leadpages_split_test post meta option and its false
     * Update the cache post meta data to true
     */
    public function updateNonSplittTestedPagesCache()
    {
        global $wpdb;
        //get all Leadpages Post Types
        $posts = $wpdb->get_results(
            "SELECT ID
            FROM {$wpdb->prefix}posts
            WHERE post_type = 'leadpages_post'"
        );

        $query = "
            SELECT
                pm.post_id,
                pm.meta_key,
                pm.meta_value
            FROM
                {$wpdb->prefix}postmeta as pm
            WHERE
                pm.meta_key = 'leadpages_split_test'
        ";

        $results = $wpdb->get_results($query);
        foreach ($results as $row) {
            if ($row->meta_value == false) {
                update_post_meta($row->post_id, 'cache_page', 'true');
            }
        }
    }
}
