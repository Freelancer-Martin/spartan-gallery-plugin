<?php
/**
 * Front-end gallery renderer.
 *
 * Single place that turns a Spartan Gallery post into HTML. Used by the
 * [spartan_gallery] shortcode and the Gutenberg block so both always produce
 * identical markup.
 *
 * Layouts:
 *   grid      - CSS grid, fixed column count, cropped to a chosen aspect ratio.
 *   masonry   - Columns of uncropped images, packed with Masonry (WP core).
 *   justified - Rows of equal height that fill the full width (pure CSS).
 *   hexagon   - Honeycomb grid (pure CSS).
 *   slider    - 3D coverflow carousel (Swiper).
 *
 * All layouts can open images in a GLightbox lightbox.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/public
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Renderer {

	const POST_TYPE      = 'spartan_gallery_type';
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

	private static $aspect_ratios = array(
		'1-1'      => '1 / 1',
		'4-3'      => '4 / 3',
		'3-2'      => '3 / 2',
		'16-9'     => '16 / 9',
		'3-4'      => '3 / 4',
		'original' => '',
	);

	/** Per-request counter so several galleries on one page get unique ids. */
	private static $instance = 0;

	public static function layouts() {
		return array( 'grid', 'masonry', 'justified', 'hexagon', 'slider' );
	}

	public static function aspect_ratio_keys() {
		return array_keys( self::$aspect_ratios );
	}

	public static function normalize_layout( $value ) {
		$value = (string) $value;
		if ( isset( self::$legacy_layouts[ $value ] ) ) {
			return self::$legacy_layouts[ $value ];
		}
		return in_array( $value, self::layouts(), true ) ? $value : self::DEFAULT_LAYOUT;
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                              */
	/* ------------------------------------------------------------------ */

	public static function register_assets() {
		$url = plugin_dir_url( __FILE__ );
		$ver = PLUGIN_NAME_VERSION;

		wp_register_style( 'spartan-gallery', $url . 'css/spartan-gallery-public.css', array(), $ver );
		wp_register_style( 'spartan-gallery-hexagon', $url . 'css/hexagon-gallery-style.css', array( 'spartan-gallery' ), $ver );
		wp_register_style( 'spartan-gallery-swiper', $url . 'vendor/swiper/swiper-bundle.min.css', array(), '14.4.0' );
		wp_register_style( 'spartan-gallery-slider', $url . 'css/gallery-3d-slider.css', array( 'spartan-gallery', 'spartan-gallery-swiper' ), $ver );
		wp_register_style( 'spartan-gallery-glightbox', $url . 'vendor/glightbox/glightbox.min.css', array(), '3.3.1' );

		wp_register_script( 'spartan-gallery-swiper', $url . 'vendor/swiper/swiper-bundle.min.js', array(), '14.4.0', true );
		wp_register_script( 'spartan-gallery-glightbox', $url . 'vendor/glightbox/glightbox.min.js', array(), '3.3.1', true );
		// Libraries are enqueued per layout; this script initializes whichever
		// of them are present on DOMContentLoaded, so it declares no hard deps.
		wp_register_script( 'spartan-gallery', $url . 'js/spartan-gallery-public.js', array(), $ver, true );
	}

	public static function enqueue_assets( $layout, $lightbox ) {
		// Rendering can happen before wp_enqueue_scripts (REST block renders,
		// do_shortcode() in a template header, ...).
		if ( ! wp_style_is( 'spartan-gallery', 'registered' ) ) {
			self::register_assets();
		}

		wp_enqueue_style( 'spartan-gallery' );

		if ( 'hexagon' === $layout ) {
			wp_enqueue_style( 'spartan-gallery-hexagon' );
		}
		if ( 'masonry' === $layout ) {
			wp_enqueue_script( 'masonry' ); // Bundled with WordPress core, depends on imagesloaded.
		}
		if ( 'slider' === $layout ) {
			wp_enqueue_style( 'spartan-gallery-slider' );
			wp_enqueue_script( 'spartan-gallery-swiper' );
		}
		if ( $lightbox ) {
			wp_enqueue_style( 'spartan-gallery-glightbox' );
			wp_enqueue_script( 'spartan-gallery-glightbox' );
		}

		wp_enqueue_script( 'spartan-gallery' );
	}

	/* ------------------------------------------------------------------ */
	/* Settings                                                            */
	/* ------------------------------------------------------------------ */

	private static function meta_int( $id, $key, $default, $min, $max ) {
		$value = metadata_exists( 'post', $id, $key ) ? absint( get_post_meta( $id, $key, true ) ) : $default;
		if ( $value < $min ) {
			$value = $default;
		}
		return min( $max, $value );
	}

	private static function meta_flag( $id, $key, $default ) {
		if ( ! metadata_exists( 'post', $id, $key ) ) {
			return $default;
		}
		return '1' === (string) get_post_meta( $id, $key, true );
	}

	public static function settings( $id ) {
		$thumb_size = (string) get_post_meta( $id, 'thumbnail_size', true );
		if ( '' === $thumb_size ) {
			$thumb_size = 'medium_large';
		}
		$use_full = self::meta_flag( $id, 'display_original', false ) || 'original' === $thumb_size;

		$ratio = (string) get_post_meta( $id, 'grid_aspect_ratio', true );
		if ( ! isset( self::$aspect_ratios[ $ratio ] ) ) {
			$ratio = '1-1';
		}

		$unit = (string) get_post_meta( $id, 'image_measure_value', true );
		if ( ! in_array( $unit, array( 'px', '%', 'vh', 'rem' ), true ) ) {
			$unit = 'px';
		}

		return array(
			'layout'       => self::normalize_layout( get_post_meta( $id, 'image_layout_select', true ) ),
			'size'         => $use_full ? 'full' : $thumb_size,
			'columns'      => self::meta_int( $id, 'gallery_columns', 3, 1, 6 ),
			'gap'          => self::meta_int( $id, 'img_margin', 20, 0, 100 ),
			'ratio'        => $ratio,
			'row_height'   => self::meta_int( $id, 'justified_row_height', 240, 80, 600 ),
			'lightbox'     => self::meta_flag( $id, 'lightbox_enabled', true ),
			'captions'     => self::meta_flag( $id, 'lightbox_captions', true ),
			'slide_width'  => self::meta_int( $id, 'image_width', 150, 1, 2000 ),
			'slide_height' => self::meta_int( $id, 'image_height', 150, 1, 2000 ),
			'slide_unit'   => $unit,
			'extra_css'    => wp_strip_all_tags( (string) get_post_meta( $id, 'add_extra_css', true ) ),
		);
	}

	/**
	 * Image attachments of a gallery, in the order chosen in the admin.
	 * One query (plus primed meta cache) instead of one get_post() per image.
	 */
	public static function get_attachments( $id ) {
		$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, 'spartan_gallery_uploaded_images', true ) ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
				'no_found_rows'  => true,
			)
		);

		return array_values( array_filter( $posts, 'wp_attachment_is_image' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * @param int $gallery_id Spartan Gallery post ID.
	 * @return string HTML (empty string if the gallery doesn't exist or isn't viewable).
	 */
	public static function render( $gallery_id ) {
		$gallery_id = absint( $gallery_id );
		$gallery    = $gallery_id ? get_post( $gallery_id ) : null;

		if ( ! $gallery || self::POST_TYPE !== $gallery->post_type ) {
			return '';
		}
		// Don't leak draft/private galleries to visitors.
		if ( 'publish' !== $gallery->post_status && ! current_user_can( 'read_post', $gallery_id ) ) {
			return '';
		}

		$attachments = self::get_attachments( $gallery_id );
		if ( empty( $attachments ) ) {
			return '<!-- spartan-gallery: no images -->';
		}

		$s   = self::settings( $gallery_id );
		$uid = 'sg-' . $gallery_id . '-' . ( ++self::$instance );

		self::enqueue_assets( $s['layout'], $s['lightbox'] );

		switch ( $s['layout'] ) {
			case 'masonry':
				$inner = self::render_masonry( $attachments, $s, $uid );
				break;
			case 'justified':
				$inner = self::render_justified( $attachments, $s, $uid );
				break;
			case 'hexagon':
				$inner = self::render_hexagon( $attachments, $s, $uid );
				break;
			case 'slider':
				$inner = self::render_slider( $attachments, $s, $uid );
				break;
			default:
				$inner = self::render_grid( $attachments, $s, $uid );
		}

		$html = '';
		if ( '' !== trim( $s['extra_css'] ) ) {
			$html .= '<style>' . $s['extra_css'] . '</style>';
		}
		$html .= '<div class="spartan-gallery-box">';
		$html .= '<div id="' . esc_attr( $uid ) . '" class="spartan-gallery-wrapper sg-gallery sg-layout-' . esc_attr( $s['layout'] ) . '" style="' . Spartan_Gallery_Generated_CSS_Output::output_php_Css( $gallery_id ) . '">';
		$html .= $inner;
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * GLightbox renders data-title/data-description with innerHTML, so the
	 * value must still be HTML-escaped *after* the browser decodes the
	 * attribute. htmlspecialchars(..., true) double-encodes on purpose.
	 */
	private static function lightbox_text( $text ) {
		$text = trim( wp_strip_all_tags( (string) $text ) );
		return htmlspecialchars( esc_html( $text ), ENT_QUOTES, get_bloginfo( 'charset' ), true );
	}

	/**
	 * Opening tag that wraps an image: a lightbox link, or a plain span when
	 * the lightbox is turned off.
	 */
	private static function link_open( $attachment, $s, $uid, $class = '' ) {
		$class = trim( 'sg-link ' . $class );

		if ( ! $s['lightbox'] ) {
			return '<span class="' . esc_attr( $class ) . '">';
		}

		$full = wp_get_attachment_image_src( $attachment->ID, 'full' );
		$href = $full ? $full[0] : wp_get_attachment_url( $attachment->ID );

		$attrs = ' href="' . esc_url( $href ) . '" class="' . esc_attr( $class . ' sg-lightbox' ) . '" data-gallery="' . esc_attr( $uid ) . '"';

		if ( $s['captions'] ) {
			$title = self::lightbox_text( $attachment->post_title );
			$desc  = self::lightbox_text( $attachment->post_excerpt );
			if ( '' !== $title ) {
				$attrs .= ' data-title="' . $title . '"';
			}
			if ( '' !== $desc ) {
				$attrs .= ' data-description="' . $desc . '"';
			}
		}

		return '<a' . $attrs . '>';
	}

	private static function link_close( $s ) {
		return $s['lightbox'] ? '</a>' : '</span>';
	}

	private static function image( $attachment, $size, $attr = array() ) {
		$attr = wp_parse_args( $attr, array( 'class' => 'sg-img', 'decoding' => 'async' ) );
		return wp_get_attachment_image( $attachment->ID, $size, false, $attr );
	}

	/** `sizes` attribute matching the responsive column breakpoints in the CSS. */
	private static function column_sizes( $columns ) {
		return sprintf(
			'(max-width: 600px) %1$dvw, (max-width: 900px) %2$dvw, %3$dvw',
			floor( 100 / min( $columns, 2 ) ),
			floor( 100 / min( $columns, 3 ) ),
			floor( 100 / $columns )
		);
	}

	private static function render_grid( $attachments, $s, $uid ) {
		$ratio = self::$aspect_ratios[ $s['ratio'] ];
		$style = '--sg-gap:' . $s['gap'] . 'px;' . ( $ratio ? '--sg-ratio:' . $ratio . ';' : '' );
		$class = 'sg-grid sg-cols-' . $s['columns'] . ( $ratio ? '' : ' sg-grid--natural' );

		$html = '<div class="' . esc_attr( $class ) . '" style="' . esc_attr( $style ) . '">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="sg-item">';
			$html .= self::link_open( $attachment, $s, $uid );
			$html .= self::image( $attachment, $s['size'], array( 'sizes' => self::column_sizes( $s['columns'] ) ) );
			$html .= self::link_close( $s );
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	private static function render_masonry( $attachments, $s, $uid ) {
		$html  = '<div class="sg-masonry sg-cols-' . absint( $s['columns'] ) . '" style="--sg-gap:' . absint( $s['gap'] ) . 'px;" data-sg-masonry data-sg-gutter="' . absint( $s['gap'] ) . '">';
		$html .= '<div class="sg-masonry-sizer"></div>';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="sg-item">';
			$html .= self::link_open( $attachment, $s, $uid );
			$html .= self::image( $attachment, $s['size'], array( 'sizes' => self::column_sizes( $s['columns'] ) ) );
			$html .= self::link_close( $s );
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	private static function render_justified( $attachments, $s, $uid ) {
		$html = '<div class="sg-justified" style="--sg-gap:' . absint( $s['gap'] ) . 'px;--sg-row-h:' . absint( $s['row_height'] ) . 'px;">';
		foreach ( $attachments as $attachment ) {
			$src = wp_get_attachment_image_src( $attachment->ID, $s['size'] );
			$ar  = ( $src && $src[1] && $src[2] ) ? $src[1] / $src[2] : 1;
			// Keep extreme panoramas/strips from taking over a whole row.
			$ar = max( 0.25, min( 4, $ar ) );

			$html .= '<div class="sg-item" style="--sg-ar:' . esc_attr( number_format( $ar, 4, '.', '' ) ) . ';">';
			$html .= self::link_open( $attachment, $s, $uid );
			$html .= self::image( $attachment, $s['size'] );
			$html .= self::link_close( $s );
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	private static function render_hexagon( $attachments, $s, $uid ) {
		$html = '<div class="sg-hexagon"><ul>';
		foreach ( $attachments as $attachment ) {
			$html .= '<li class="hex">';
			$html .= self::link_open( $attachment, $s, $uid, 'hexIn' );
			$html .= self::image( $attachment, $s['size'], array( 'sizes' => '(max-width: 1200px) 50vw, 33vw' ) );
			$html .= self::link_close( $s );
			$html .= '</li>';
		}
		$html .= '</ul></div>';

		return $html;
	}

	private static function render_slider( $attachments, $s, $uid ) {
		$w    = $s['slide_width'];
		$h    = $s['slide_height'];
		$unit = $s['slide_unit'];

		if ( '%' === $unit ) {
			// A percentage height has nothing to resolve against inside the
			// carousel, so keep the requested proportions via aspect-ratio.
			$slide_style = 'width:' . $w . '%;aspect-ratio:' . $w . ' / ' . $h . ';';
		} elseif ( 'vh' === $unit ) {
			$slide_style = 'width:' . $w . 'vw;height:' . $h . 'vh;';
		} else {
			$slide_style = 'width:' . $w . $unit . ';height:' . $h . $unit . ';';
		}

		$html  = '<div class="sg-slider swiper" data-sg-slider>';
		$html .= '<div class="swiper-wrapper">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="swiper-slide" style="' . esc_attr( $slide_style ) . '">';
			$html .= self::link_open( $attachment, $s, $uid );
			// No forced loading="lazy": browsers can misjudge visibility of
			// 3D-transformed slides and leave the front slide blank.
			$html .= self::image( $attachment, $s['size'] );
			$html .= self::link_close( $s );
			$html .= '</div>';
		}
		$html .= '</div>';
		$html .= '<div class="swiper-pagination"></div>';
		$html .= '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
		$html .= '</div>';

		return $html;
	}
}
