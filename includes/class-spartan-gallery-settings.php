<?php
/**
 * Gallery settings schema.
 *
 * Single source of truth for every per-gallery option: its default, how it
 * is sanitized, and how values saved by older plugin versions are migrated.
 * Used by the admin save handler, the live preview and the front-end
 * renderer, so the three can never disagree.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/includes
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Settings {

	const DEFAULT_LAYOUT = 'grid';

	/**
	 * Layout values stored by versions before 1.5.0, mapped to their closest
	 * replacement so existing galleries keep working without re-saving.
	 */
	private static $legacy_layouts = array(
		'value-1' => 'masonry',   // Freewall Chaotic.
		'value-2' => 'justified', // Freewall Random.
		'value-3' => 'grid',      // Freewall Fixed.
		'value-4' => 'masonry',   // Freewall Pinterest.
		'value-5' => 'hexagon',
		'value-6' => 'slider',
	);

	/** Meta keys from before 1.6.0 that the schema replaces. Removed on save. */
	private static $legacy_keys = array(
		'display_original',
		'thumbnail_options',
		'thumbnail_size',
		'container_settings_on_off',
		'container_height_width_range',
		'container_background_color',
		'container_border',
	);

	public static function layouts() {
		return array( 'grid', 'masonry', 'justified', 'mosaic', 'polaroid', 'hexagon', 'slider', 'carousel', 'slideshow', 'compare' );
	}

	public static function aspect_ratios() {
		return array(
			'1-1'      => '1 / 1',
			'4-3'      => '4 / 3',
			'3-2'      => '3 / 2',
			'16-9'     => '16 / 9',
			'3-4'      => '3 / 4',
			'original' => '',
		);
	}

	/**
	 * type: int (min/max) | bool | enum (choices) | layout | length | color | css
	 */
	public static function fields() {
		return array(
			'image_layout_select'  => array( 'type' => 'layout', 'default' => self::DEFAULT_LAYOUT ),
			'image_quality'        => array( 'type' => 'enum', 'default' => 'balanced', 'choices' => array( 'fast', 'balanced', 'full' ) ),
			'gallery_columns'      => array( 'type' => 'int', 'default' => 3, 'min' => 1, 'max' => 6 ),
			'grid_aspect_ratio'    => array( 'type' => 'enum', 'default' => '1-1', 'choices' => array_keys( self::aspect_ratios() ) ),
			'justified_row_height' => array( 'type' => 'int', 'default' => 240, 'min' => 80, 'max' => 600 ),
			'img_margin'           => array( 'type' => 'int', 'default' => 10, 'min' => 0, 'max' => 100 ),
			'image_radius'         => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 40 ),
			'hover_effect'         => array( 'type' => 'enum', 'default' => 'zoom', 'choices' => array( 'none', 'zoom', 'fade', 'darken', 'title' ) ),
			'lightbox_enabled'     => array( 'type' => 'bool', 'default' => true ),
			'lightbox_title'       => array( 'type' => 'bool', 'default' => false ),
			'lightbox_captions'    => array( 'type' => 'bool', 'default' => true ),
			'lightbox_thumbnails'  => array( 'type' => 'bool', 'default' => true ),
			'lightbox_download'    => array( 'type' => 'bool', 'default' => false ),
			'lightbox_slideshow'   => array( 'type' => 'bool', 'default' => false ),
			'filter_enabled'       => array( 'type' => 'bool', 'default' => false ),
			'pagination'           => array( 'type' => 'enum', 'default' => 'none', 'choices' => array( 'none', 'button', 'scroll' ) ),
			'per_page'             => array( 'type' => 'int', 'default' => 12, 'min' => 1, 'max' => 200 ),
			'autoplay'             => array( 'type' => 'bool', 'default' => false ),
			'autoplay_delay'       => array( 'type' => 'int', 'default' => 4, 'min' => 1, 'max' => 30 ),
			'image_width'          => array( 'type' => 'int', 'default' => 300, 'min' => 1, 'max' => 2000 ),
			'image_height'         => array( 'type' => 'int', 'default' => 300, 'min' => 1, 'max' => 2000 ),
			'image_measure_value'  => array( 'type' => 'enum', 'default' => 'px', 'choices' => array( 'px', '%', 'vh', 'rem' ) ),
			'container_width'      => array( 'type' => 'length', 'default' => '' ),
			'container_height'     => array( 'type' => 'length', 'default' => '' ),
			'container_background' => array( 'type' => 'color', 'default' => '' ),
			'container_padding'    => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 200 ),
			'border_width'         => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 20 ),
			'border_style'         => array( 'type' => 'enum', 'default' => 'solid', 'choices' => array( 'solid', 'dashed', 'dotted', 'double' ) ),
			'border_color'         => array( 'type' => 'color', 'default' => '#dddddd' ),
			'border_radius'        => array( 'type' => 'int', 'default' => 0, 'min' => 0, 'max' => 50 ),
			'add_extra_css'        => array( 'type' => 'css', 'default' => '' ),
		);
	}

	public static function normalize_layout( $value ) {
		$value = (string) $value;
		if ( isset( self::$legacy_layouts[ $value ] ) ) {
			return self::$legacy_layouts[ $value ];
		}
		return in_array( $value, self::layouts(), true ) ? $value : self::DEFAULT_LAYOUT;
	}

	/**
	 * Sanitize one raw (already unslashed) value against its field definition.
	 */
	public static function sanitize( $key, $raw ) {
		$fields = self::fields();
		if ( ! isset( $fields[ $key ] ) ) {
			return null;
		}
		$f = $fields[ $key ];

		switch ( $f['type'] ) {
			case 'int':
				if ( ! is_numeric( $raw ) ) {
					return $f['default'];
				}
				return max( $f['min'], min( $f['max'], (int) $raw ) );

			case 'bool':
				return true === $raw || '1' === (string) $raw || 'on' === (string) $raw;

			case 'enum':
				return in_array( (string) $raw, $f['choices'], true ) ? (string) $raw : $f['default'];

			case 'layout':
				return self::normalize_layout( $raw );

			case 'length':
				$raw = strtolower( trim( (string) $raw ) );
				if ( preg_match( '/^\d+(\.\d+)?$/', $raw ) ) {
					return $raw . 'px';
				}
				return preg_match( '/^\d+(\.\d+)?(px|%|em|rem|vw|vh)$/', $raw ) ? $raw : '';

			case 'color':
				$color = sanitize_hex_color( (string) $raw );
				return $color ? $color : '';

			case 'css':
				return wp_strip_all_tags( (string) $raw );
		}

		return $f['default'];
	}

	/**
	 * All settings for a saved gallery, with defaults and legacy migration.
	 */
	public static function get( $post_id ) {
		$values = array();
		foreach ( self::fields() as $key => $f ) {
			if ( metadata_exists( 'post', $post_id, $key ) ) {
				$values[ $key ] = self::sanitize( $key, get_post_meta( $post_id, $key, true ) );
			} else {
				$legacy         = self::legacy_value( $post_id, $key );
				$values[ $key ] = null === $legacy ? $f['default'] : $legacy;
			}
		}
		return $values;
	}

	/**
	 * Settings from a submitted form (save handler and live preview).
	 * Unchecked checkboxes are absent from the request and mean "off".
	 *
	 * @param array $request Unslashed request data.
	 */
	public static function from_request( array $request ) {
		$values = array();
		foreach ( self::fields() as $key => $f ) {
			if ( 'bool' === $f['type'] ) {
				$values[ $key ] = ! empty( $request[ $key ] );
			} else {
				$values[ $key ] = isset( $request[ $key ] ) && ! is_array( $request[ $key ] )
					? self::sanitize( $key, $request[ $key ] )
					: $f['default'];
			}
		}
		return $values;
	}

	public static function save( $post_id, array $values ) {
		foreach ( self::fields() as $key => $f ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}
			$value = 'bool' === $f['type'] ? ( $values[ $key ] ? '1' : '' ) : $values[ $key ];
			update_post_meta( $post_id, $key, $value );
		}
		foreach ( self::$legacy_keys as $key ) {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Image IDs from the comma-separated hidden field / meta value.
	 */
	public static function parse_image_ids( $raw ) {
		return array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) ) );
	}

	/**
	 * Values a gallery saved before 1.6.0 stored under different keys.
	 *
	 * @return mixed|null null when there is nothing to migrate.
	 */
	private static function legacy_value( $post_id, $key ) {
		switch ( $key ) {
			case 'image_quality':
				if ( '1' === (string) get_post_meta( $post_id, 'display_original', true ) ) {
					return 'full';
				}
				$size = (string) get_post_meta( $post_id, 'thumbnail_size', true );
				if ( 'original' === $size ) {
					return 'full';
				}
				if ( in_array( $size, array( 'thumbnail', 'medium' ), true ) ) {
					return 'fast';
				}
				return null;

			case 'container_width':
			case 'container_height':
			case 'container_background':
			case 'border_width':
			case 'border_style':
			case 'border_color':
				// Old container styles only applied while their on/off switch was on.
				if ( '1' !== (string) get_post_meta( $post_id, 'container_settings_on_off', true ) ) {
					return null;
				}
				$dims   = (array) get_post_meta( $post_id, 'container_height_width_range', true );
				$border = (array) get_post_meta( $post_id, 'container_border', true );

				if ( 'container_width' === $key ) {
					return self::sanitize( $key, isset( $dims['width'] ) ? $dims['width'] : '' );
				}
				if ( 'container_height' === $key ) {
					return self::sanitize( $key, isset( $dims['height'] ) ? $dims['height'] : '' );
				}
				if ( 'container_background' === $key ) {
					return self::sanitize( $key, get_post_meta( $post_id, 'container_background_color', true ) );
				}
				if ( 'border_width' === $key ) {
					if ( empty( $border['border-style'] ) || 'none' === $border['border-style'] ) {
						return 0;
					}
					return self::sanitize( $key, isset( $border['border-top'] ) ? (int) $border['border-top'] : 0 );
				}
				if ( 'border_style' === $key ) {
					return self::sanitize( $key, isset( $border['border-style'] ) ? $border['border-style'] : '' );
				}
				return self::sanitize( $key, isset( $border['border-color'] ) ? $border['border-color'] : '' );
		}

		return null;
	}
}
