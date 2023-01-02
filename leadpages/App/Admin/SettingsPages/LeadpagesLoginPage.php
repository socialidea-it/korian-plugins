<?php
namespace LeadpagesWP\Admin\SettingsPages;

use TheLoop\Contracts\SettingsPage;

class LeadpagesLoginPage implements SettingsPage
{
    const TEMPLATE_FILE = __DIR__ . '/../../templates/login.php';

    public static function getName()
    {
        return get_called_class();
    }

    public function definePage()
    {
        add_menu_page(
            'leadpages',
            'Leadpages',
            'manage_options',
            'Leadpages',
            [$this, 'displayCallback'],
            'none'
        );
    }

    public function displayCallback()
    {
        if (isset($_GET['code'])) {
            $code = sanitize_text_field($_GET['code']);
            echo '<div class="notice notice-error is-dismissible">'
                . '<p>Login Failed Error Code: '
                . esc_html($code)
                . '</p></div>';
        }

        $this->loginPageHtml();
    }

    public function registerPage()
    {
        add_action('admin_menu', [$this, 'definePage']);
    }

    public function loginPageHtml()
    {
        if (file_exists(self::TEMPLATE_FILE)) {
            readfile(self::TEMPLATE_FILE);
        }
    }
}
