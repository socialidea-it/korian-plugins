<?php
namespace LeadpagesWP\Lib;

class AdminNotices
{
    public static function getName()
    {
        return get_called_class();
    }

    /**
     * Notice for if a user is not logged in
     */
    public static function notLoggedInToLeadpages()
    {
        $loginUrl = admin_url()."?page=Leadpages";
        $message = "
            <p>You are not logged into Leadpages. Your pages will not work until you login</p>
            <a class='notice_login_link' href='{$loginUrl}'>Login to Leadpages</a>";
        echo self::displayNotice($message, "warning");
    }

    /**
     * Notice to let user know they need to turn on permalinks
     */
    public static function turnOnPermalinks()
    {
        echo self::displayNotice(
            'LeadPages plugin needs '
            . '<a style="text-decoration: underline"'
            . '    href="options-permalink.php">permalinks</a> enabled!'
        );
    }


    public static function displayNotice($message = "", $level = "error", $isDismissable = true)
    {
        return _e(
            '<div class="notice notice-' . $level . ' is-dismissible">'
            . '<p>'
            .  $message
            . '</p></div>', 'leadpages');
    }

    public static function displayNotices()
    {
        global $leadpagesConfig;
        if ($leadpagesConfig["currentScreen"] !== "leadpages_post") {
            return;
        }

        $notices = get_option("leadpages_admin_notices", []);
        if (!empty($notices)) {
            foreach ($notices as $notice) {
                echo self::displayNotice($notice["message"], $notice["level"]);
            }
        }
        self::clearNotices();
    }

    public static function clearNotices()
    {
        update_option("leadpages_admin_notices", []);
    }
}
