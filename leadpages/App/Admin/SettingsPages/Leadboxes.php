<?php
namespace LeadpagesWP\Admin\SettingsPages;

use TheLoop\Contracts\SettingsPage;
use LeadpagesWP\models\LeadboxesModel;
use LeadpagesWP\Helpers\LeadboxDisplay;
use TheLoop\ServiceContainer\ServiceContainerTrait;

class Leadboxes implements SettingsPage
{
    use ServiceContainerTrait;
    use LeadboxDisplay;

    public function __construct()
    {
        // TODO: Inject this if we get around to cleaning up the factory methods.
        global $leadpagesApp;
        $this->config = $leadpagesApp["config"];
    }

    public static function getName()
    {
        return get_called_class();
    }

    public function definePage()
    {
        global $leadpagesConfig;

        if (isset($_GET['page']) && $_GET['page'] == 'Leadboxes') {
            add_action(
                'admin_enqueue_scripts',
                [$this, 'leadboxScripts']
            );
        }

        add_submenu_page(
            'edit.php?post_type=leadpages_post',
            'leadboxes',
            'Pop-ups',
            'manage_options',
            'Leadboxes',
            [$this, 'displayCallback']
        );
    }

    public function displayCallback()
    {
        $appUrl = $this->config->get("LEADPAGES_URL");
        ?>

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
                                    Configure Pop-ups
                                </div>
                            </div>

                            <button id="leadpages-save" class="ui-btn">
                                Save
                                <div class="ui-loading ui-loading--sm ui-loading--inverted">
                                    <div class="ui-loading__dots ui-loading__dots--1"></div>
                                    <div class="ui-loading__dots ui-loading__dots--2"></div>
                                    <div class="ui-loading__dots ui-loading__dots--3"></div>
                                </div>
                            </button>
                        </div>
                        <hr>
                        <p>
                            Here you can setup timed and exit Pop-ups. If you want to place a Pop-up via link, button,
                            or image to any page, you need to copy and paste the HTML code you'll find in the Pop-up
                            publish interface <a href="<?php echo $appUrl . 'my-leadboxes' ?>" target="_blank">inside the
                                Leadpages™ application.</a>
                        </p>
                    </div>
                </div>

                <div class="leadbox-config-wrapper">
                    <div class="leadbox-container">
                        <h3 class="leadbox-header">
                            Timed Pop-up Configuration
                        </h3>

                        <p class="leadbox-body">
                            All Pop-ups with timed configuration are listed below. 
                            To edit settings for your Pop-ups please <a href="<?php echo $appUrl . 'my-leadboxes/' ?>"
                                                                          target="_blank">visit our application.</a>
                        </p>

                        <div class="timed-leadboxes"></div>
                        <div class="timed-leadbox-script">
                            <label class="ui-label">
                                Paste Drag & Drop Script
                            </label>

                            <textarea name="leadbox_timed_script" class="leadbox_timed_script" rows="5" cols="20"><?php echo LeadboxesModel::getB3Script('timed'); ?></textarea>
                        </div>
                        <div class="post-types-for-timed-leadbox"></div>
                        <div id="selected-leadbox-settings"></div>
                    </div>

                    <div class="leadbox-container">
                        <h3 class="leadbox-header">
                            Exit Pop-up Configuration
                        </h3>

                        <p class="leadbox-body">
                            All Pop-ups with exit configuration are listed below.
                            To edit settings for your Pop-ups please
                            <a href="<?php echo $appUrl . 'my-leadboxes/' ?>"
                                target="_blank">visit our application.</a>
                        </p>

                        <div class="exit-leadboxes"></div>
                        <div class="exit-leadbox-script">
                            <label class="ui-label">
                                Paste Drag and Drop Script
                            </label>

                            <textarea name="leadbox_exit_script" class="ui-textarea leadbox_exit_script" rows="5" cols="20"><?php echo LeadboxesModel::getB3Script('exit'); ?></textarea>
                        </div>
                        <div class="post-types-for-exit-leadbox"></div>
                        <div id="selected-exit-leadbox-settings"></div>
                    </div>
                </div>
                <input type="hidden" name="action" value="save_leadbox_options"/>
                <?php wp_nonce_field('save_leadbox_options'); ?>
            </form>
        </div>
        <?php
    }

    public function registerPage()
    {
        add_action('admin_menu', [$this, 'definePage']);
    }

    public function leadboxScripts()
    {
        global $leadpages_connector_plugin_version;
        global $leadpagesConfig;
        global $leadpagesApp;

        $apiResponse = $leadpagesApp['leadboxesApi']->getAllLeadboxes();
        $allLeadboxes = json_decode($apiResponse['response'], true);
        $leadboxes = $this->loadItems($allLeadboxes);

        wp_enqueue_script(
            'Leadboxes',
            $leadpagesConfig['admin_assets'] . '/js/Leadboxes.js',
            ['jquery'],
            $leadpages_connector_plugin_version
        );

        wp_localize_script(
            'Leadboxes',
            'leadboxes_object',
            [
                'ajax_url'                   => admin_url('admin-ajax.php'),
                'timedLeadboxes'             => $this->timedDropDown($leadboxes),
                'postTypesForTimedLeadboxes' => $this->postTypesForTimedLeadboxes(),
                'postTypesForExitLeadboxes'  => $this->postTypesForExitLeadboxes(),
                'exitLeadboxes'              => $this->exitDropDown($leadboxes),
            ]
        );
    }
}
