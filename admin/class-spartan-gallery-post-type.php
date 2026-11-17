<?php
/**
 * Use namespace to avoid conflict
 */
namespace PostType;
/**
 * Class Event
 * @package PostType
 *
 * Use actual name of post type for
 * easy readability.
 *
 * Potential conflicts removed by namespace
 */
class SpartnaGalleryPostType {
    /**
     * @var string
     *
     * Set post type params
     */
    private $type               = 'spartan_gallery_type';
    private $slug               = 'spartan_gallery';
    private $name               = 'Spartan Gallerys';
    private $singular_name      = 'Spartan Gallery';
    /**
     * Register post type
     */
    public function register() {
        $labels = array(
            'name'                  => __( 'Spartan Galleries', 'spartan-gallery' ),
            'singular_name'         => __( 'Spartan Gallery', 'spartan-gallery' ),
            'add_new'               => __( 'Add New Gallery', 'spartan-gallery' ),
            'add_new_item'          => __( 'Add New Gallery', 'spartan-gallery' ),
            'edit_item'             => __( 'Edit Gallery', 'spartan-gallery' ),
            'new_item'              => __( 'New Gallery', 'spartan-gallery' ),
            'all_items'             => __( 'All Galleries', 'spartan-gallery' ),
            'view_item'             => __( 'View Gallery', 'spartan-gallery' ),
            'search_items'          => __( 'Search Galleries', 'spartan-gallery' ),
            'not_found'             => __( 'No galleries found.', 'spartan-gallery' ),
            'not_found_in_trash'    => __( 'No galleries found in Trash.', 'spartan-gallery' ),
            'parent_item_colon'     => '',
            'menu_name'             => __( 'Spartan Gallery', 'spartan-gallery' ),
        );
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array( 'slug' => $this->slug ),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => true,
            'menu_position'         => 8,
            'supports'              => array( 'title', /*'editor' ,  'excerpt', 'author', 'thumbnail' */),
            'yarpp_support'         => true,
            'show_in_rest'          => true,  // This is crucial for REST API access
            //'rest_base' => 'spartan-gallery-type', // The custom endpoint name (for REST API)
        );
        register_post_type( $this->type, $args );
    }
    /**
     * @param $columns
     * @return mixed
     *
     * Choose the columns you want in
     * the admin table for this post
     */
    public function set_columns($columns) {
        // Set/unset post type table columns here
        return $columns;
    }
    /**
     * @param $column
     * @param $post_id
     *
     * Edit the contents of each column in
     * the admin table for this post
     */
    public function edit_columns($column, $post_id) {
        // Post type table column content code here
    }
    /**
     * Event constructor.
     *
     * When class is instantiated
     */
    public function __construct() {
        // Register the post type
        add_action('init', array($this, 'register'));
        // Admin set post columns
        add_filter( 'manage_edit-'.$this->type.'_columns',        array($this, 'set_columns'), 10, 1) ;
        // Admin edit post columns
        add_action( 'manage_'.$this->type.'_posts_custom_column', array($this, 'edit_columns'), 10, 2 );
    }
}
/**
 * Instantiate class, creating post type
 */
new SpartnaGalleryPostType();
