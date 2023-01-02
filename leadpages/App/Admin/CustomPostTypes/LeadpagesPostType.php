<?php
namespace LeadpagesWP\Admin\CustomPostTypes;

use TheLoop\Contracts\CustomPostType;
use LeadpagesWP\Helpers\LeadpageType;
use LeadpagesWP\models\LeadPagesPostTypeModel;

class LeadpagesPostType extends CustomPostType
{
    private $labels = [];
    private $args  = [];
    public $postTypeName = 'leadpages_post';

    public static function getName()
    {
        return get_called_class();
    }

    public function defineLabels()
    {
        $this->labels = [
            'name'               => _x('Landing Pages', 'post type general name'),
            'singular_name'      => _x('Landing Page', 'post type singular name'),
            'menu_name'          => __('Leadpages'),
            'all_items'          => __('Landing Pages'),
            'add_new'            => _x('Add New', 'leadpage'),
            'add_new_item'       => __('Add New Leadpage'),
            'edit_item'          => __('Edit Leadpage'),
            'new_item'           => __('New Leadpage'),
            'view_item'          => __('View Leadpages'),
            'search_items'       => __('Search Leadpages'),
            'not_found'          => __('Nothing found'),
            'not_found_in_trash' => __('Nothing found in Trash'),
            'parent_item_colon'  => '',
        ];
    }

    public function registerPostType()
    {
        $this->args = [
            'labels'               => $this->labels,
            'description'          => 'Allows you to have Leadpages on your WordPress site.',
            'public'               => true,
            'publicly_queryable'   => true,
            'show_ui'              => true,
            'query_var'            => false,
            'menu_icon'            => '',
            'capability_type'      => 'page',
            'menu_position'        => 10000,
            'can_export'           => false,
            'hierarchical'         => true,
            'has_archive'          => false,
            'supports'             => [],
        ];

        register_post_type($this->postTypeName, $this->args);
        remove_post_type_support($this->postTypeName, 'editor');
        remove_post_type_support($this->postTypeName, 'title');

        add_action(
            'add_meta_boxes',
            function () {
                remove_meta_box('submitdiv', $this->postTypeName, 'side');
            }
        );

        if (is_admin()) {
            add_filter('postUpdatedMessages', [$this, 'postUpdatedMessages']);
        }
    }

    public function buildPostType()
    {
        $this->defineLabels();
        add_action('init', [$this, 'registerPostType'], 5);
        $this->addColumns();
    }

    public function defineColumns($columns)
    {
        return [
            'cb' => $columns['cb'],
            $this->postTypeName.'_name' => __('Name', 'leadpages'),
            $this->postTypeName.'_type' => __('Type', 'leadpages'),
            $this->postTypeName.'_path' => __('Url', 'leadpages'),
            'date'                      => __('Date', 'leadpages'),
        ];
    }

    public function populateColumns($column)
    {
        $id = get_the_ID();
        $this->populateNameColumn($column, $id);
        $this->populatePathColumn($column, $id);
        $this->populateTypeColumn($column, $id);
    }

    private function populateNameColumn($column, $id)
    {
        if ($this->postTypeName.'_name' == $column) {
            $url    = get_edit_post_link($id);
            $post_name = get_post_meta($id, 'leadpages_name', true);
            $name = $post_name;
            if ($name == '') {
                $name = get_the_title($id);
            }
            echo '<strong><a href="' . $url . '">' . $name . '</a></strong>';
        }
    }

    private function populatePathColumn($column, $id)
    {
        $path = home_url();
        if ($this->postTypeName.'_path' == $column) {
            if (LeadpageType::isFrontPage($id)) {
                $blogId = get_current_blog_id();
                $url = get_home_url($blogId);
                echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            } elseif (LeadpageType::isNotFoundPage($id)) {
                $characters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                $length       = 10;
                for ($i = 0; $i < $length; $i ++) {
                    $randomString .= $characters[ rand(0, strlen($characters) - 1) ];
                }
                $url = site_url() . '/random-test-url-' . $randomString;
                echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            } else {
                if ($path == '') {
                    echo '<strong style="color:#ff3300">Missing path!</strong> <i>Page is not active</i>';
                } else {
                    $url = $path .'/'.trim(LeadpagesPostTypeModel::getMetaPagePath($id), '/');
                    echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                }
            }
        }
    }

    private function populateTypeColumn($column, $id)
    {
        $activeNotice = '<span style="font-style: italic; color:red;">'
            . __(' Active', 'leadpages').'</span>';

        if ($this->postTypeName.'_type' == $column) {
            $type = LeadPagesPostTypeModel::getMetaPageType($id);
            if (empty($type)) {
                $type = 'lp';
            }

            $activePage = null;
            $labels = [
                'lp' => 'Landing Page',
                'fp' => 'Homepage',
                'wg' => 'Welcome Gate',
                'nf' => '404 Page',
            ];

            switch ($type) {
                case 'fp':
                    $activePage = LeadpageType::getFrontLeadpage();
                    break;

                case 'wg':
                    $activePage = LeadpageType::getWelcomeGate();
                    break;

                case 'nf':
                    $activePage = LeadpageType::get404Leadpage();
                    break;
            }

            echo $labels[$type];
            if ($type !== 'lp' && $activePage == $id) {
                echo $activeNotice;
            }
        }
    }

    public function addColumns()
    {
        add_filter('manage_edit-'.$this->postTypeName.'_columns', [&$this, 'defineColumns']);
        add_action('manage_pages_custom_column', [$this, 'populateColumns']);
    }


    /*
    *  @description: messages for saving a field group
    *  @since 1.0.0
    *  @created: 23/06/12
    */

    public function postUpdatedMessages($messages)
    {
        global $post;
        if ($post->post_type != 'leadpages_post') {
            return $messages;
        }
        $leadpageSlug = get_post_meta($post->ID, 'leadpages_slug', true);

        $url = get_site_url().'/'.$leadpageSlug;
        $messages['leadpages_post'] = [
          0 => '', // Unused. Messages start at index 1.
          1 => sprintf(__('Leadpage updated. %s', 'Leadpages'), "<a href=\"{$url}\" target='_blank'>View Leadpage</a>"),
          2 => sprintf(__('Leadpage updated. %s', 'Leadpages'), "<a href=\"{$url}\" target='_blank'>View Leadpage</a>"),
          3 => __('Leadpage deleted.', 'Leadpages'),
          4 => sprintf(__('Leadpage updated. %s', 'Leadpages'), "<a href=\"{$url}\" target='_blank'>View Leadpage</a>"),
            /* translators: %s: date and time of the revision */
          5 => isset($_GET['revision']) ? sprintf(__('Field group restored to revision from %s', 'Leadpages'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
          6 => sprintf(__('Leadpage Published. %s', 'Leadpages'), "<a href=\"{$url}\" target='_blank'>View Leadpage</a>"),
          7 => __('Leadpage saved.', 'Leadpages'),
          8 => __('Leadpage submitted.', 'Leadpages'),
          9 => __('Leadpage scheduled for.', 'Leadpages'),
          10 => __('Leadpage draft updated.', 'Leadpages'),
        ];

        return $messages;
    }

    /**
     * @todo param is likely unused or used incorrectly
     */
    public static function forceAllMetaboxesInMainColumn($order = null)
    {
        return [
            'normal' => join(
                ",",
                [
                    'postexcerpt',
                    'formatdiv',
                    'trackbacksdiv',
                    'tagsdiv-post_tag',
                    'categorydiv',
                    'postimagediv',
                    'postcustom',
                    'commentstatusdiv',
                    'slugdiv',
                    'authordiv',
                    'submitdiv',
                ]
            ),
            'side'     => '',
            'advanced' => '',
        ];
    }
}
