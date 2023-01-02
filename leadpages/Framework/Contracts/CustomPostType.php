<?php
namespace TheLoop\Contracts;

abstract class CustomPostType
{
    private $labels = [];
    private $args = [];

    public $postTypeNamePlural = 'POSTTYPE_NAME_PLURAL';
    public $postTypeName       = 'POSTTYPE_NAME';

    /**
     * Define the labels for your custom post type here
     *
     * @return null
     */
    public function defineLabels()
    {
        $this->labels = [
            'name'               => _x($this->postTypeNamePlural, 'post type general name'),
            'singular_name'      => _x($this->postTypeName, 'post type singular name'),
            'add_new'            => _x($this->postTypeName, 'leadpage'),
            'add_new_item'       => __('Add New '.$this->postTypeName),
            'edit_item'          => __('Edit '.$this->postTypeName),
            'new_item'           => __('New '.$this->postTypeName),
            'view_item'          => __('View '.$this->postTypeNamePlurarl),
            'search_items'       => __('Search '.$this->postTypeNamePlurarl),
            'not_found'          => __('Nothing found'),
            'not_found_in_trash' => __('Nothing found in Trash'),
            'parent_item_colon'  => '',
        ];
    }

    /**
     * Setup your custom post type here
     * and run register post type hook
     *
     * @return null
     */
    public function registerPostType()
    {
        $this->args = [
            'labels'               => $this->labels,
            'description'          => '',
            'public'               => true,
            'publicly_queryable'   => true,
            'show_ui'              => true,
            'query_var'            => true,
            'menu_icon'            => '',
            'capability_type'      => 'page',
            'menu_position'        => null,
            'rewrite'              => [
                'with_front'       => false,
                'slug'             => '/'
            ],
            'can_export'           => false,
            'hierarchical'         => true,
            'has_archive'          => true,
            'supports'             => [],
        ];

        register_post_type($this->postTypeName, $this->args);
    }

    /**
     * Run definelabels function, add init action with registerPostType method
     * add any other added functionality needed for post type
     *
     * @return null
     */
    public function buildPostType()
    {
        $this->defineLabels();
        add_action('init', [$this, 'registerPostType']);
    }
}
