<?php
namespace LeadpagesWP\models;

use LeadpagesWP\Helpers\Security;

class LeadbarsModel
{
    public static function init()
    {
        add_action(
            'admin_post_save_leadbar_options',
            [
                get_called_class(),
                'saveGlobalLeadbars'
            ]
        );
    }

    public static function saveGlobalLeadbars()
    {
        Security::checkAdminRefererStatic('save_leadbar_options');
        $leadbar_id = $_POST['active-alert-bar-id'];

        global $leadpagesApp;
        $leadbar_embed = $leadpagesApp['leadbarsApi']->getEmbed($leadbar_id);
        $globalLeadbars = [
            'global_leadbar_id' => $leadbar_id,
            'global_leadbar_embed' => $leadbar_embed,
        ];

        static::updateLeadbarOption($globalLeadbars);
        wp_redirect(admin_url() . 'edit.php?post_type=leadpages_post&page=AlertBars');
    }

    protected static function updateLeadbarOption($data)
    {
        update_option('lp_bar_settings', $data);
    }

    public static function getLpBarSettings()
    {
        $data = get_option('lp_bar_settings', []);
        if (is_wp_error($data)) {
            error_log('Error: Failed to get Alert Bar settings');
            return [];
        }

        return $data;
    }

    public static function getActiveAlertBarId()
    {
        $data = self::getLpBarSettings();
        $hasGlobalId = is_array($data)
            && array_key_exists('global_leadbar_id', $data);

        return $hasGlobalId ? $data['global_leadbar_id'] : '';
    }
}
