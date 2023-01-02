<?php
namespace LeadpagesWP\Admin\MetaBoxes;

use TheLoop\Contracts\MetaBox;
use LeadpagesWP\Helpers\LeadboxDisplay;

class LeadboxMetaBox implements MetaBox
{
    use LeadboxDisplay;

    public static function getName()
    {
        return get_called_class();
    }

    public function defineMetaBox()
    {
        add_meta_box(
            "leadbox-select",
            "Page Specific Pop-up",
            [$this, "callback"],
            "",
            "side",
            "low",
            null
        );
    }

    public function callback($post, $box)
    {
        global $leadpagesApp;
        $apiResponse = $leadpagesApp['leadboxesApi']->getAllLeadboxes();
        $leadboxes = json_decode($apiResponse['response'], true);
        $pageSpecificList = $this->timedDropDownPageSpecific($leadboxes, $post);
        $exitList = $this->exitDropDownPageSpecific($leadboxes, $post);

        echo <<<HTML
            <p>
                Set a specific Pop-up to display on this page only.
                This will override any global Pop-ups that are setup.
            </p>

            <div id="pageSpecificLeadbox">
                <label><strong>Timed Pop-ups</strong></label>

                <br />
                $pageSpecificList
                <br />
                <label><strong>Exit Pop-ups</strong></label>
                <br />

                $exitList
            </div>
HTML;
    }

    public function removeMetaBoxFromLeadpagePostType()
    {
        remove_meta_box('leadbox-select', 'leadpages_post', 'side');
    }

    public function registerMetaBox()
    {
        add_action('add_meta_boxes', [$this, 'defineMetaBox']);
        add_action('add_meta_boxes', [$this, 'removeMetaBoxFromLeadpagePostType']);
    }
}
