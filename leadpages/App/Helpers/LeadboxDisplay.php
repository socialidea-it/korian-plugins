<?php
namespace LeadpagesWP\Helpers;

/**
 * @todo Lot of refactoring needed
 */

use LeadpagesWP\models\LeadboxesModel;

trait LeadboxDisplay
{
    protected $postTypesForLeadboxes;
    protected $currentTimedLeadbox;
    protected $currentExitLeadbox;

    /**
     * Setup current state of timed and exit leadboxes
     *
     * @return self
     */
    public function setCurrentLeadboxes()
    {
        $leadboxes = LeadboxesModel::getLpSettings();
        $this->currentTimedLeadbox = LeadboxesModel::getCurrentTimedLeadbox($leadboxes);
        $this->currentExitLeadbox = LeadboxesModel::getCurrentExitLeadbox($leadboxes);
        return $this;
    }

    /**
     * Helper for timed leadbox dropdown
     *
     * @param string $leadboxId leadbox uui
     *
     * @return string selected=selected|''
     */
    protected function currentTimedLeadbox($leadboxId)
    {
        $this->setCurrentLeadboxes();
        if ($this->currentTimedLeadbox[0] == $leadboxId) {
            return 'selected="selected"';
        }
        return '';
    }

    /**
     * Helper for timed leadbox post type dropdown
     *
     * @param string $postType wp post type
     *
     * @return string checked=checked|''
     */
    protected function currentTimedLeadboxDisplayPostType($postType)
    {
        if ($this->currentTimedLeadbox[1] == 'posts') {
            $this->currentTimedLeadbox[1] = 'post';
        }

        return ($this->currentTimedLeadbox[1] == $postType) ?
            'checked="checked"' : '';
    }

    /**
     * Helper for current global exit leadbox helper
     *
     * @param string $leadboxId leadbox uuid
     *
     * @return string
     */
    protected function currentExitLeadbox($leadboxId)
    {
        return ($this->currentExitLeadbox[0] == $leadboxId) ?
            'selected="selected"' : '';
    }

    /**
     * Checked helper for exit leadbox post type
     *
     * @param string $postType post type
     *
     * @return string
     */
    protected function currentExitLeadboxDisplayPostType($postType)
    {
        return ($this->currentExitLeadbox[1] == $postType) ?
            'checked="checked"' : '';
    }

    /**
     * Create a dropdown of every timed leadbox
     *
     * @param mixed $data leadboxes list
     *
     * @return string
     */
    public function timedDropDown($data)
    {
        $leadboxes = $this->loadItems($data);
        $select = "
            <select name='lp_select_field_0' id='leadboxes-timed'>"
            . "<option name='none' value='none'"
            . $this->currentTimedLeadbox('none')
            . ">None</option>";

        foreach ($leadboxes['_items'] as $leadbox) {
            if (!isset($leadbox['publish_settings']['time'])) {
                continue;
            }

            $lb_time = $leadbox['publish_settings']['time'];
            $seconds = $lb_time['seconds'];

            if ($seconds > 0) {
                $views = $lb_time['views'];
                $lb_days = $lb_time['days'];
                $xor_hex_id = $leadbox['xor_hex_id'];

                $select .= "
                    <option value=\"{$xor_hex_id}\"
                        data-timeAppear=\"{$seconds}\"
                        data-pageView=\"{$views}\"
                        data-daysAppear=\"{$lb_days}\"
                        " . $this->currentTimedLeadbox($xor_hex_id) . "
                    >{$leadbox['name']}</option>";
            }
        }

        $select .= '
            </select>
            <span id="timed-leadbox-refresh" class="dashicons dashicons-image-rotate"></span>
            <div class="timed-loading ui-loading ui-loading--sm">
                <div class="ui-loading__dots ui-loading__dots--1"></div>
                <div class="ui-loading__dots ui-loading__dots--2"></div>
                <div class="ui-loading__dots ui-loading__dots--3"></div>
            </div>';

        return $select;
    }


    /**
     * Create a dropdown of every leadbox that has an exit time set on it
     *
     * @param mixed $data list of leadbox data
     *
     * @todo This needs refactored badly
     *
     * @return string
     */
    public function exitDropDown($data)
    {
        $leadboxes = $this->loadItems($data);
        $select = '
            <select name="lp_select_field_2" id="leadboxes-exit">'
            . '<option name="none" value="none" '
            . $this->currentExitLeadbox("none")
            . '>None</option>';

        foreach ($leadboxes['_items'] as $leadbox) {
            $exit_days = $leadbox['publish_settings']['exit']['days'] ?: 0;
            $xor_id = $leadbox['xor_hex_id'];
            $lb_name = $leadbox['name'];

            $select .= "<option value=\"{$xor_id}\" data-daysAppear=\"{$exit_days}\""
                    . $this->currentExitLeadbox($xor_id)
                    . ">{$lb_name}</option>";
        }

        $select .= '
            </select>
            <span id="exit-leadbox-refresh" class="dashicons dashicons-image-rotate"></span>
            <div class="exit-loading ui-loading ui-loading--sm">
                <div class="ui-loading__dots ui-loading__dots--1"></div>
                <div class="ui-loading__dots ui-loading__dots--2"></div>
                <div class="ui-loading__dots ui-loading__dots--3"></div>
            </div>';
        return $select;
    }

    /**
     * Reduced list of all post types for leadboxes to be on
     *
     * @return mixed
     */
    public function getPostTypesForLeadboxes()
    {
        $postTypes = get_post_types();
        $unneededTypes = ['attachment', 'revision', 'nav_menu_item'];
        $this->postTypesForLeadboxes = array_diff($postTypes, $unneededTypes);
        return $this->postTypesForLeadboxes;
    }

    /**
     * List of post types to ignore
     *
     * @return mixed
     */
    protected function disallowedPostTypes()
    {
        return [
          'leadpages_post',
            //woocomerce
          'product_variation',
          'shop_order',
          'shop_order_refund',
          'shop_coupon',
          'shop_webhook',
          'user_request',
          'oembed_cache',
          'custom_css',
          'customize_changeset',
        ];
    }

    /**
     * Generate radio buttons for timed buttons
     *
     * @return string html of timed leadbox list radio inputs
     */
    public function postTypesForTimedLeadboxes()
    {
        global $leadpagesApp;
        $this->getPostTypesForLeadboxes();

        $options = '
            <br />
            <input
                type="radio"
                id="timed_radio_all"
                name="leadboxes_timed_display_radio"
                value="all" '
            . $this->currentTimedLeadboxDisplayPostType('all')
            . '>
            <label for="timed_radio_all">
                Every WordPress page, including homepage, 404 and posts
            </label>
        ';

        foreach ($this->postTypesForLeadboxes as $postType) {
            if (in_array($postType, $this->disallowedPostTypes())) {
                continue;
            }
            $postTypeLabel = ucfirst($postType);
            $options .= '
                <br>
                <input type="radio"
                    id="timed_radio_' . $postType . '"
                    name="leadboxes_timed_display_radio"
                    value="' . $postType . '" '
                    . $this->currentTimedLeadboxDisplayPostType($postType)
                    . '> <label for="timed_radio_' . $postType . '">Display on ' . $postTypeLabel . '</label>';
        }

        return $options;
    }

    /**
     * Generate radio buttons for timed buttons
     *
     * @return string
     */
    public function postTypesForExitLeadboxes()
    {
        global $leadpagesApp;

        $this->getPostTypesForLeadboxes();
        $options = '<br><input type="radio" id="timed_radio_all" name="leadboxes_exit_display_radio" value="all" '
            . $this->currentExitLeadboxDisplayPostType('all')
            .'> <label for="exit_radio_all">Every WordPress page, including homepage, 404 and posts</label>';

        foreach ($this->postTypesForLeadboxes as $postType) {
            if (in_array($postType, $this->disallowedPostTypes())) {
                continue;
            }

            $postTypeLabel = ucfirst($postType);

            $options .= '<br><input type="radio" id="exit_radio_'.$postType.'" '
                . 'name="leadboxes_exit_display_radio" value="'.$postType.'" '
                . $this->currentExitLeadboxDisplayPostType($postType)
                .'> <label for="exit_radio_' . $postType . '">Display on '
                . $postTypeLabel
                . '</label>';
        }

        return $options;
    }

    /**
     * Page Specific Leadbox methods
     *
     * @param mixed $data leadbox data
     * @param mixed $post post data
     *
     * @return string select + options
     */
    public function timedDropDownPageSpecific($data, $post)
    {
        $leadboxes = $this->loadItems($data);
        $select = '<select name="pageTimedLeadbox" id="leadboxes-timed">'
            . '<option name="select" value="select"'
            . $this->currentTimedLeadboxPageSpecific("select", $post->ID)
            . ">Use Global Pop-up</option>"
            . '<option name="none" value="none"'
            . $this->currentTimedLeadboxPageSpecific("none", $post->ID)
            . ">None</option>";

        foreach ($leadboxes['_items'] as $leadbox) {
            $name = $leadbox['name'];
            $time = $leadbox['publish_settings']['time'];
            $xor_id = $leadbox['xor_hex_id'];

            if (isset($time) && $time['seconds'] > 0) {
                $select .= "
                    <option value=\"{$xor_id}\"
                        data-timeAppear=\"{$time['seconds']}\"
                        data-pageView=\"{$time['views']}\"
                        data-daysAppear=\"{$time['days']}\""
                        . $this->currentTimedLeadboxPageSpecific($xor_id, $post->ID)
                        .">{$name}</option>";
            }
        }
        $select .="</select>";
        return $select;
    }

    /**
     * Helper for timed leadbox dropdown
     *
     * @param string $leadboxId leadbox uui
     * @param int    $postId    wp post id
     *
     * @return string selected=selected|''
     */
    protected function currentTimedLeadboxPageSpecific($leadboxId, $postId)
    {
        $pageSpecificLeadbox = get_post_meta($postId, 'pageTimedLeadbox', true);
        return ($pageSpecificLeadbox == $leadboxId) ?
            'selected="selected"' : '';
    }

    /**
     * Page specific exit leadbox dropdown
     *
     * @param mixed $data leadbox items from api
     * @param mixed $post wp post object
     *
     * @return string html select dropdown
     */
    public function exitDropDownPageSpecific($data, $post)
    {
        $leadboxes = $this->loadItems($data);
        $select = '<select name="pageExitLeadbox" id="leadboxes-exit">'
            . '<option name="select" value="select" '
            . $this->currentExitLeadboxPageSpecific("select", $post->ID)
            . ">Use Global Pop-up</option>"
            . '<option name="none" value="none" '
            . $this->currentExitLeadboxPageSpecific("none", $post->ID)
            .">None</option>";

        foreach ($leadboxes['_items'] as $leadbox) {
            $lb_name = $leadbox['name'];
            $days = isset($leadbox['publish_settings']['exit']['days'])
                ? $leadbox['publish_settings']['exit']['days'] : 0;
            $xor_id = $leadbox['xor_hex_id'];

            $select .= "<option value=\"{$xor_id}\" data-daysAppear=\"{$days}\""
                . $this->currentExitLeadboxPageSpecific($xor_id, $post->ID)
                .">{$lb_name}</option>";
        }
        $select .= "</select>";
        return $select;
    }

    /**
     * Helper for exit leadbox dropdown
     *
     * @param string $leadboxId leadbox uui
     * @param int    $postId    wp post id
     *
     * @return string selected=selected|''
     */
    protected function currentExitLeadboxPageSpecific($leadboxId, $postId)
    {
        $pageSpecificLeadbox = get_post_meta($postId, 'pageExitLeadbox', true);
        return ($pageSpecificLeadbox == $leadboxId) ?
            'selected="selected"' : '';
    }

    /**
     * Loop over leadboxes using array filter and only return leadboxes
     * that actually have embed code
     *
     * @param mixed $leadbox leadbox
     *
     * @return mixed|null
     */
    public function filterLeadpageGeneratedLeadboxes($leadbox)
    {
        if (!empty($leadbox['publish_settings']['embed'])) {
            return $leadbox;
        }
        return null;
    }

    /**
     * Load leadbox data from response
     *
     * @param mixed $data leadbox data
     *
     * @return mixed filtered data
     */
    public function loadItems($data)
    {
        $items = isset($data["_items"]) ? $data["_items"] : [];
        $leadboxes['_items'] = array_filter($items, [$this, 'filterLeadpageGeneratedLeadboxes']);

        $leadboxes["_items"][] = [
          'public_url' => '',
          'publish_settings' => [
            'link'   => [],
            'embed'  => '',
            'legacy' => '',
            'exit'   => [
              'days' => 2
            ],
            'time'   => [
              'seconds' => '1',
              'days'    => '0',
              'views'   => ''
            ]
          ],
          'name' => 'Paste Drag & Drop Pop-up',
          'xor_hex_id' => 'ddbox'
        ];

        return $leadboxes;
    }
}
