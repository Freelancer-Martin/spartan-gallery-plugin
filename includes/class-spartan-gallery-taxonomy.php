<?php
/**
 * "Gallery Tags" for images, used by the filterable gallery option.
 *
 * Registered on attachments, so the field appears in the Media Library's
 * attachment details (including inside the "Add / Manage Images" dialog)
 * and as Media > Gallery Tags.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/includes
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Taxonomy {

	const TAXONOMY = 'sg_image_tag';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		register_taxonomy(
			self::TAXONOMY,
			'attachment',
			array(
				'labels'                => array(
					'name'          => __( 'Gallery Tags', 'spartan-gallery' ),
					'singular_name' => __( 'Gallery Tag', 'spartan-gallery' ),
					'menu_name'     => __( 'Gallery Tags', 'spartan-gallery' ),
					'search_items'  => __( 'Search Gallery Tags', 'spartan-gallery' ),
					'all_items'     => __( 'All Gallery Tags', 'spartan-gallery' ),
					'edit_item'     => __( 'Edit Gallery Tag', 'spartan-gallery' ),
					'add_new_item'  => __( 'Add New Gallery Tag', 'spartan-gallery' ),
					'not_found'     => __( 'No gallery tags found.', 'spartan-gallery' ),
					'separate_items_with_commas' => __( 'Separate tags with commas, e.g. Weddings, Portraits', 'spartan-gallery' ),
				),
				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_admin_column'     => true,
				'show_in_rest'          => true,
				'hierarchical'          => false,
				'rewrite'               => false,
				'query_var'             => false,
				// Attachments have post_status "inherit", which the default
				// counter ignores.
				'update_count_callback' => '_update_generic_term_count',
			)
		);
	}

	/**
	 * Tags of the given attachments in one query.
	 *
	 * @param int[] $ids
	 * @return array{0: array<int,string[]>, 1: WP_Term[]} [ attachment id => slugs, terms used (sorted by name) ]
	 */
	public static function tags_for( array $ids ) {
		if ( empty( $ids ) || ! taxonomy_exists( self::TAXONOMY ) ) {
			return array( array(), array() );
		}

		$terms = wp_get_object_terms(
			$ids,
			self::TAXONOMY,
			array(
				'fields'  => 'all_with_object_id',
				'orderby' => 'name',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array( array(), array() );
		}

		$map  = array();
		$used = array();
		foreach ( $terms as $term ) {
			$map[ (int) $term->object_id ][] = $term->slug;
			$used[ $term->term_id ]          = $term;
		}

		return array( $map, array_values( $used ) );
	}
}

Spartan_Gallery_Taxonomy::init();
