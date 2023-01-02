<?php
namespace LeadpagesWP\models;

use LeadpagesWP\Helpers\Security;

class LeadboxesModel
{
    public $currentLeadboxes = '';

    public static function init()
    {
        add_action('admin_post_save_leadbox_options', [get_called_class(), 'saveGlobalLeadboxes']);
    }

    public static function saveGlobalLeadboxes()
    {
        Security::checkAdminRefererStatic('save_leadbox_options');
        global $leadpagesApp;

        $timedSelect = $_POST['lp_select_field_0'];
        $exitSelect = $_POST['lp_select_field_2'];

        $timedRadio = $_POST['leadboxes_timed_display_radio'] ?: '';
        $exitRadio = $_POST['leadboxes_exit_display_radio'] ?: '';

        $b3TimedLeadbox = self::checkIfB3GlobalTimedLeadbox($_POST);
        $b3ExitLeadbox = self::checkIfB3GlobalExitLeadbox($_POST);

        $jsTimed = new \stdClass();
        if (!empty($b3TimedLeadbox)) {
            $jsTimed->embed_code = str_replace(PHP_EOL, '', $b3TimedLeadbox);
        } elseif ($timedSelect === 'none') {
            $jsTimed->embed_code = '';
            unset($timedRadio);
        } else {
            $response = $leadpagesApp['leadboxesApi']->getSingleLeadboxEmbedCode($timedSelect, 'timed');
            $jsTimed = json_decode($response['response']);
        }

        $jsExit = new \stdClass();
        if (!empty($b3ExitLeadbox)) {
            $jsExit->embed_code = $b3ExitLeadbox;
        } elseif ($exitSelect === 'none') {
            $jsExit->embed_code = '';
            unset($exitRadio);
        } else {
            $response = $leadpagesApp['leadboxesApi']->getSingleLeadboxEmbedCode($exitSelect, 'exit');
            $jsExit = json_decode($response['response']);
        }

        $globalLeadboxes = [
            'lp_select_field_0'             => sanitize_text_field($timedSelect),
            'leadboxes_timed_display_radio' => sanitize_text_field($timedRadio),
            'leadboxes_timed_js'            => $jsTimed,
            'lp_select_field_2'             => sanitize_text_field($exitSelect),
            'leadboxes_exit_display_radio'  => sanitize_text_field($exitRadio),
            'leadboxes_exit_js'             => $jsExit,
        ];

        static::updateLeadboxOption($globalLeadboxes);
        wp_redirect(admin_url() . 'edit.php?post_type=leadpages_post&page=Leadboxes');
    }

    public static function checkIfB3GlobalTimedLeadbox($data)
    {
        if (!empty($data['leadbox_timed_script'])
            && $data['lp_select_field_0'] == 'ddbox'
        ) {
            return stripslashes($data['leadbox_timed_script']);
        }
        return null;
    }

    public static function checkIfB3GlobalExitLeadbox($data)
    {
        if (!empty($data['leadbox_exit_script'])
            && $data['lp_select_field_2'] == 'ddbox'
        ) {
            return stripslashes($data['leadbox_exit_script']);
        }
        return null;
    }

    protected static function updateLeadboxOption($data)
    {
        update_option('lp_settings', $data);
        return $data;
    }

    public static function getLpSettings()
    {
        $data = get_option('lp_settings');
        if (!is_wp_error($data)) {
            return $data;
        }
        return [];
    }

    public static function getB3Script($type)
    {
        $isTimed = $type == 'timed';
        $isExit = $type == 'exit';

        $leadboxes = self::getLpSettings();

        if ($isTimed && $leadboxes['lp_select_field_0'] == 'ddbox'
            && $leadboxes['leadboxes_timed_js']
        ) {
            return $leadboxes['leadboxes_timed_js']->embed_code;
        }

        if ($isExit && $leadboxes['lp_select_field_2'] == 'ddbox'
            && $leadboxes['leadboxes_exit_js']
        ) {
            return $leadboxes['leadboxes_exit_js']->embed_code;
        }
        return "";
    }

    public static function getCurrentTimedLeadbox($leadboxes)
    {
        $currentTimedLeadbox = ['none', 'none'];
        if (isset($leadboxes['lp_select_field_0']) && $leadboxes['lp_select_field_0'] != 'none') {
            $currentTimedLeadbox = [
                $leadboxes['lp_select_field_0'],
                $leadboxes['leadboxes_timed_display_radio'],
                $leadboxes['leadboxes_timed_js']->embed_code
            ];
        }
        return $currentTimedLeadbox;
    }

    public static function getCurrentExitLeadbox($leadboxes)
    {
        $currentExitLeadbox = ['none', 'none'];
        if (isset($leadboxes['lp_select_field_2']) && $leadboxes['lp_select_field_2'] != 'none') {
            $currentExitLeadbox = [
                $leadboxes['lp_select_field_2'],
                $leadboxes['leadboxes_exit_display_radio'],
                $leadboxes['leadboxes_exit_js']->embed_code
            ];
        }
        return $currentExitLeadbox;
    }

    public static function savePageSpecificLeadboxes($post_id, $post)
    {
        if (isset($_POST['pageTimedLeadbox'])) {
            $timedLeadboxId = sanitize_text_field($_POST['pageTimedLeadbox']);
            self::savePageSpecificTimedLeadbox($post_id, $timedLeadboxId);
        }

        if (isset($_POST['pageExitLeadbox'])) {
            $exitLeadboxId = sanitize_text_field($_POST['pageExitLeadbox']);
            self::savePageSpecificExitLeadbox($post_id, $exitLeadboxId);
        }
    }

    public static function saveLeadboxMeta()
    {
        add_action(
            'edit_post',
            [
                get_called_class(),
                'savePageSpecificLeadboxes'
            ],
            999,
            2
        );
    }

    /**
     * Store timed leadbox by page id
     *
     * @param string $postId    wp post id
     * @param string $leadboxId leadbox uuid
     *
     * @return null|string leadbox id
     */
    public static function savePageSpecificTimedLeadbox($postId, $leadboxId)
    {
        return self::savePageSpecificLeadbox('pageTimedLeadbox', $postId, $leadboxId);
    }

    /**
     * Save exit leadbox on wp post by id
     *
     * @param string $postId    wp post id
     * @param string $leadboxId leadbox uuid
     *
     * @return null|string
     */
    public static function savePageSpecificExitLeadbox($postId, $leadboxId)
    {
        return self::savePageSpecificLeadbox('pageExitLeadbox', $postId, $leadboxId);
    }

    /**
     * Store leadbox by leadbox type & page id
     *
     * @param string $type      pageExitLeadbox|pageTimedLeadbox
     * @param string $postId    wp post id
     * @param string $leadboxId leadbox uuid
     *
     * @return null|string leadbox id
     */
    public static function savePageSpecificLeadbox($type, $postId, $leadboxId)
    {
        $types = ['pageExitLeadbox', 'pageTimedLeadbox'];
        if (!in_array($type, $types)) {
            return null;
        }

        if (empty($leadboxId) || empty($postId)) {
            return null;
        }

        // if switched back to select delete the post meta
        // so global leadboxes will display again
        if ($leadboxId == 'select') {
            delete_post_meta($postId, $type);
            return '';
        }

        update_post_meta($postId, $type, $leadboxId);
        return $leadboxId;
    }
}
