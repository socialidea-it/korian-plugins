<?php
namespace LeadpagesWP\Front\ShortCodes;

class LeadboxShortCodes
{
    /**
     * Displays the Leadboxes from shortcodes on front-end.
     */
    public function displayLeadboxes($atts)
    {
        global $leadpagesApp;

        $atts = shortcode_atts(['leadbox_id' => ''], $atts);
        $leadboxId = $atts['leadbox_id'];

        $leadbox = $leadpagesApp['leadboxesApi']->getSingleLeadboxEmbedCode($leadboxId, '');
        $leadbox = json_decode($leadbox['response']);

        return $leadbox->embed_code;
    }

    public function addLeadboxesShortCode()
    {
        add_shortcode('leadpages_leadbox', [$this, 'displayLeadboxes']);
    }
}
