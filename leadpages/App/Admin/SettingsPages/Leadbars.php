<?php
namespace LeadpagesWP\Admin\SettingsPages;

use TheLoop\Contracts\SettingsPage;
use LeadpagesWP\models\LeadbarsModel;
use LeadpagesWP\Helpers\LeadbarDisplay;
use TheLoop\ServiceContainer\ServiceContainerTrait;
use LeadpagesWP\Admin\Helpers\AlertBarList;

class Leadbars implements SettingsPage
{
    use ServiceContainerTrait;
    use LeadbarDisplay;

    public static function getName()
    {
        return get_called_class();
    }

    public function definePage()
    {
        global $leadpagesConfig;
        add_submenu_page(
            'edit.php?post_type=leadpages_post',
            'leadbars',
            'Alert Bars',
            'manage_options',
            'AlertBars',
            [$this, 'displayCallback']
        );
    }

    public function displayCallback()
    {
        $this->listTable = new AlertBarList('hello');
        $this->listTable->prepare_items();
    ?>
    <div class="wrap">
        <div id="leadbox-configure">
            <form action="admin-post.php" method="post">
                <div class="leadpages-edit-wrapper">
                    <div id="leadpages-header-wrapper">
                        <div id="leadbox_header" class="flex flex--xs-between flex--xs-middle">
                            <div class="ui-title-nav" aria-controls="navigation">
                                <div class="ui-title-nav__img">
                                    <i class="lp-icon lp-icon--alpha">leadpages_mark</i>
                                </div>

                                <div class="ui-title-nav__content">
                                    Configure Alert Bars
                                </div>
                            </div>

                            <button id="leadpages-save" class="ui-btn">Save</button>
                        </div>

                        <hr>

                        <p>
                            Set a Global Leadpages Alert Bar.  To create or edit Alert Bars, you must use the
                            <a href="https://my.leadpages.net/#/my-conversion-tools/bars" target="_blank">
                                Leadpages™ application</a>.
                        </p>
                    </div>

                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php $this->listTable->display() ?>
                    <input type="hidden" name="action" value="save_leadbar_options"/>
                    <?php wp_nonce_field('save_leadbar_options'); ?>
                </form>
            </div>
        </div>
    </div>

    <?php
    }

    public function registerPage()
    {
        add_action('admin_menu', [$this, 'definePage']);
    }
}
