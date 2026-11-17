<?php
/**
 * The "spartan_gallery_type" post type and its admin list columns.
 *
 * Galleries are shown through the shortcode / blocks, not as pages of
 * their own, so the post type is not public: no empty single pages, no
 * archive, nothing added to search results or the XML sitemap. It stays
 * in the REST API (for the block editor) and the admin UI.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Post_Type {

	const TYPE = 'spartan_gallery_type';

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function register() {
		$labels = array(
			'name'               => __( 'Spartan Galleries', 'spartan-gallery' ),
			'singular_name'      => __( 'Spartan Gallery', 'spartan-gallery' ),
			'add_new'            => __( 'Add New Gallery', 'spartan-gallery' ),
			'add_new_item'       => __( 'Add New Gallery', 'spartan-gallery' ),
			'edit_item'          => __( 'Edit Gallery', 'spartan-gallery' ),
			'new_item'           => __( 'New Gallery', 'spartan-gallery' ),
			'all_items'          => __( 'All Galleries', 'spartan-gallery' ),
			'view_item'          => __( 'View Gallery', 'spartan-gallery' ),
			'search_items'       => __( 'Search Galleries', 'spartan-gallery' ),
			'not_found'          => __( 'No galleries found.', 'spartan-gallery' ),
			'not_found_in_trash' => __( 'No galleries found in Trash.', 'spartan-gallery' ),
			'menu_name'          => __( 'Spartan Gallery', 'spartan-gallery' ),
		);

		register_post_type(
			self::TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true, // The block editor lists galleries through the REST API.
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'hierarchical'        => false,
				'capability_type'     => 'post',
				'menu_position'       => 8,
				'menu_icon'           => 'dashicons-format-gallery',
				'supports'            => array( 'title' ),
			)
		);
	}

	public function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['sg_shortcode'] = __( 'Shortcode', 'spartan-gallery' );
				$new['sg_layout']    = __( 'Layout', 'spartan-gallery' );
				$new['sg_images']    = __( 'Images', 'spartan-gallery' );
			}
		}
		return $new;
	}

	public function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'sg_shortcode':
				echo '<code style="user-select:all">[spartan_gallery id="' . absint( $post_id ) . '"]</code>';
				break;

			case 'sg_layout':
				$settings = Spartan_Gallery_Settings::get( $post_id );
				$layouts  = Spartan_Gallery_Options::layouts();
				$layout   = $settings['image_layout_select'];
				echo esc_html( isset( $layouts[ $layout ] ) ? $layouts[ $layout ][0] : $layout );
				break;

			case 'sg_images':
				$settings = Spartan_Gallery_Settings::get( $post_id );
				if ( 'tag' === $settings['source'] ) {
					$term = $settings['source_tag'] ? get_term( (int) $settings['source_tag'], Spartan_Gallery_Taxonomy::TAXONOMY ) : null;
					/* translators: %s: Gallery Tag name */
					echo esc_html( sprintf( __( 'Tag: %s', 'spartan-gallery' ), $term && ! is_wp_error( $term ) ? $term->name : '—' ) );
				} else {
					echo esc_html( number_format_i18n( count( Spartan_Gallery_Settings::parse_image_ids( get_post_meta( $post_id, 'spartan_gallery_uploaded_images', true ) ) ) ) );
				}
				break;
		}
	}
}

new Spartan_Gallery_Post_Type();
