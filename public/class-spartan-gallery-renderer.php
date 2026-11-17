<?php
/**
 * Front-end gallery renderer.
 *
 * Single place that turns a Spartan Gallery post into HTML. Used by the
 * [spartan_gallery] shortcode, the Gutenberg block and the admin live
 * preview, so all three always produce identical markup.
 *
 * Layouts:
 *   grid      - Even columns, cropped to a chosen aspect ratio (CSS grid).
 *   masonry   - Uncropped images packed into columns (Masonry, WP core).
 *   justified - Equal-height rows that fill the width (CSS flex).
 *   mosaic    - Grid with every few images shown twice as large (CSS grid).
 *   polaroid  - Tilted instant-photo cards (CSS).
 *   hexagon   - Honeycomb grid (CSS).
 *   slider    - 3D coverflow carousel (Swiper).
 *   carousel  - Several images side by side, swipeable (Swiper).
 *   slideshow - One large image with a thumbnail strip (Swiper).
 *   compare   - Before/after comparison of image pairs (vanilla JS).
 *
 * All layouts except compare can open images in a GLightbox lightbox.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/public
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Renderer {

	const POST_TYPE = 'spartan_gallery_type';

	/** Layouts built on Swiper. */
	const SWIPER_LAYOUTS = array( 'slider', 'carousel', 'slideshow' );

	/** Layouts where the hover effect setting applies. */
	const HOVER_LAYOUTS = array( 'grid', 'masonry', 'justified', 'mosaic', 'polaroid', 'carousel' );

	/** Per-request counter so several galleries on one page get unique ids. */
	private static $instance = 0;

	/** Back-compat wrapper; the schema lives in Spartan_Gallery_Settings. */
	public static function normalize_layout( $value ) {
		return Spartan_Gallery_Settings::normalize_layout( $value );
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                              */
	/* ------------------------------------------------------------------ */

	public static function asset_urls() {
		$url = plugin_dir_url( __FILE__ );
		return array(
			'css' => array(
				'spartan-gallery'           => $url . 'css/spartan-gallery-public.css',
				'spartan-gallery-hexagon'   => $url . 'css/hexagon-gallery-style.css',
				'spartan-gallery-swiper'    => $url . 'vendor/swiper/swiper-bundle.min.css',
				'spartan-gallery-swiper-ui' => $url . 'css/spartan-gallery-swiper.css',
				'spartan-gallery-glightbox' => $url . 'vendor/glightbox/glightbox.min.css',
			),
			'js'  => array(
				'imagesloaded'              => includes_url( 'js/imagesloaded.min.js' ),
				'masonry'                   => includes_url( 'js/masonry.min.js' ),
				'spartan-gallery-swiper'    => $url . 'vendor/swiper/swiper-bundle.min.js',
				'spartan-gallery-glightbox' => $url . 'vendor/glightbox/glightbox.min.js',
				'spartan-gallery'           => $url . 'js/spartan-gallery-public.js',
			),
		);
	}

	public static function register_assets() {
		$a   = self::asset_urls();
		$ver = PLUGIN_NAME_VERSION;

		wp_register_style( 'spartan-gallery', $a['css']['spartan-gallery'], array(), $ver );
		wp_register_style( 'spartan-gallery-hexagon', $a['css']['spartan-gallery-hexagon'], array( 'spartan-gallery' ), $ver );
		wp_register_style( 'spartan-gallery-swiper', $a['css']['spartan-gallery-swiper'], array(), '14.4.0' );
		wp_register_style( 'spartan-gallery-swiper-ui', $a['css']['spartan-gallery-swiper-ui'], array( 'spartan-gallery', 'spartan-gallery-swiper' ), $ver );
		wp_register_style( 'spartan-gallery-glightbox', $a['css']['spartan-gallery-glightbox'], array(), '3.3.1' );

		wp_register_script( 'spartan-gallery-swiper', $a['js']['spartan-gallery-swiper'], array(), '14.4.0', true );
		wp_register_script( 'spartan-gallery-glightbox', $a['js']['spartan-gallery-glightbox'], array(), '3.3.1', true );
		// Libraries are enqueued per layout; this script initializes whichever
		// of them are present on DOMContentLoaded, so it declares no hard deps.
		wp_register_script( 'spartan-gallery', $a['js']['spartan-gallery'], array(), $ver, true );
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
		if ( in_array( $layout, self::SWIPER_LAYOUTS, true ) ) {
			wp_enqueue_style( 'spartan-gallery-swiper-ui' );
			wp_enqueue_script( 'spartan-gallery-swiper' );
		}
		if ( $lightbox ) {
			wp_enqueue_style( 'spartan-gallery-glightbox' );
			wp_enqueue_script( 'spartan-gallery-glightbox' );
		}

		wp_enqueue_script( 'spartan-gallery' );
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Image attachments in the given order. One query (plus primed meta
	 * cache) instead of one get_post() per image.
	 *
	 * @param int[] $ids
	 */
	public static function get_attachments( array $ids ) {
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

	/**
	 * Derived values the layouts need, on top of the raw settings.
	 */
	private static function prepare( array $s ) {
		$s['layout'] = $s['image_layout_select'];

		$sizes   = array( 'fast' => 'medium_large', 'balanced' => 'large', 'full' => 'full' );
		$s['size'] = $sizes[ $s['image_quality'] ];

		$ratios       = Spartan_Gallery_Settings::aspect_ratios();
		$s['ratio']   = $ratios[ $s['grid_aspect_ratio'] ];
		$s['gap']     = $s['img_margin'];
		$s['columns'] = $s['gallery_columns'];

		// The compare layout's slider handle would fight with a lightbox click.
		$s['lightbox'] = $s['lightbox_enabled'] && 'compare' !== $s['layout'];

		return $s;
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Render a saved gallery for the front end.
	 *
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

		$ids         = Spartan_Gallery_Settings::parse_image_ids( get_post_meta( $gallery_id, 'spartan_gallery_uploaded_images', true ) );
		$attachments = self::get_attachments( $ids );
		if ( empty( $attachments ) ) {
			return '<!-- spartan-gallery: no images -->';
		}

		$settings = Spartan_Gallery_Settings::get( $gallery_id );
		$s        = self::prepare( $settings );

		self::enqueue_assets( $s['layout'], $s['lightbox'] );

		return self::render_markup( $gallery_id, $settings, $attachments );
	}

	/**
	 * Markup for any settings array (saved or unsaved). Does not enqueue.
	 *
	 * @param int       $gallery_id  Used for the element id only.
	 * @param array     $settings    From Spartan_Gallery_Settings::get()/from_request().
	 * @param WP_Post[] $attachments
	 */
	public static function render_markup( $gallery_id, array $settings, array $attachments ) {
		$s   = self::prepare( $settings );
		$uid = 'sg-' . absint( $gallery_id ) . '-' . ( ++self::$instance );

		// "Fast"/"Balanced" cap how large an image the browser may pick from
		// srcset; the lightbox always links the full-size original.
		$caps = array( 'fast' => 1024, 'balanced' => 2048 );
		$cap  = isset( $caps[ $s['image_quality'] ] ) ? $caps[ $s['image_quality'] ] : 0;
		$cap_filter = function () use ( $cap ) {
			return $cap;
		};
		if ( $cap ) {
			add_filter( 'max_srcset_image_width', $cap_filter, 99 );
		}

		switch ( $s['layout'] ) {
			case 'masonry':
				$inner = self::render_masonry( $attachments, $s, $uid );
				break;
			case 'justified':
				$inner = self::render_justified( $attachments, $s, $uid );
				break;
			case 'mosaic':
				$inner = self::render_mosaic( $attachments, $s, $uid );
				break;
			case 'polaroid':
				$inner = self::render_polaroid( $attachments, $s, $uid );
				break;
			case 'hexagon':
				$inner = self::render_hexagon( $attachments, $s, $uid );
				break;
			case 'slider':
				$inner = self::render_slider( $attachments, $s, $uid );
				break;
			case 'carousel':
				$inner = self::render_carousel( $attachments, $s, $uid );
				break;
			case 'slideshow':
				$inner = self::render_slideshow( $attachments, $s, $uid );
				break;
			case 'compare':
				$inner = self::render_compare( $attachments, $s, $uid );
				break;
			default:
				$inner = self::render_grid( $attachments, $s, $uid );
		}

		if ( $cap ) {
			remove_filter( 'max_srcset_image_width', $cap_filter, 99 );
		}

		$classes = array( 'spartan-gallery-wrapper', 'sg-gallery', 'sg-layout-' . $s['layout'] );
		if ( in_array( $s['layout'], self::HOVER_LAYOUTS, true ) ) {
			$classes[] = 'sg-hover-' . $s['hover_effect'];
		}

		$data = '';
		if ( $s['lightbox'] ) {
			$data .= ' data-sg-lb-thumbs="' . ( $s['lightbox_thumbnails'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-lb-download="' . ( $s['lightbox_download'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-lb-slideshow="' . ( $s['lightbox_slideshow'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-delay="' . absint( $s['autoplay_delay'] ) . '"';
		}

		$html = '';
		if ( '' !== trim( $s['add_extra_css'] ) ) {
			$html .= '<style>' . $s['add_extra_css'] . '</style>';
		}
		$html .= '<div class="spartan-gallery-box">';
		$html .= '<div id="' . esc_attr( $uid ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( self::container_style( $s ) ) . '"' . $data . '>';
		$html .= $inner;
		$html .= '</div></div>';

		return $html;
	}

	private static function container_style( $s ) {
		$css = '--sg-gap:' . absint( $s['gap'] ) . 'px;--sg-radius:' . absint( $s['image_radius'] ) . 'px;';

		if ( '' !== $s['container_width'] ) {
			$css .= 'width:' . $s['container_width'] . ';max-width:100%;';
		}
		if ( '' !== $s['container_height'] ) {
			$css .= 'height:' . $s['container_height'] . ';overflow:auto;';
		}
		if ( '' !== $s['container_background'] ) {
			$css .= 'background-color:' . $s['container_background'] . ';';
		}
		if ( $s['container_padding'] ) {
			$css .= 'padding:' . absint( $s['container_padding'] ) . 'px;';
		}
		if ( $s['border_width'] ) {
			$color = $s['border_color'] ? $s['border_color'] : '#dddddd';
			$css  .= 'border:' . absint( $s['border_width'] ) . 'px ' . $s['border_style'] . ' ' . $color . ';';
		}
		if ( $s['border_radius'] ) {
			$css .= 'border-radius:' . absint( $s['border_radius'] ) . 'px;';
		}

		return $css;
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

		if ( $s['lightbox_captions'] ) {
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

	/** Title overlay used by the "title" hover effect. */
	private static function hover_caption( $attachment, $s ) {
		if ( 'title' !== $s['hover_effect'] || ! in_array( $s['layout'], self::HOVER_LAYOUTS, true ) ) {
			return '';
		}
		$title = trim( wp_strip_all_tags( $attachment->post_title ) );
		return '' === $title ? '' : '<span class="sg-caption">' . esc_html( $title ) . '</span>';
	}

	private static function image( $attachment, $size, $attr = array() ) {
		$attr = wp_parse_args( $attr, array( 'class' => 'sg-img', 'decoding' => 'async' ) );
		return wp_get_attachment_image( $attachment->ID, $size, false, $attr );
	}

	/** A standard gallery tile: wrapper, link, image, optional hover caption. */
	private static function item( $attachment, $s, $uid, $img_attr = array(), $item_attr = '' ) {
		return '<div class="sg-item"' . $item_attr . '>'
			. self::link_open( $attachment, $s, $uid )
			. self::image( $attachment, $s['size'], $img_attr )
			. self::hover_caption( $attachment, $s )
			. self::link_close( $s )
			. '</div>';
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

	private static function ratio_style( $s, $fallback = '' ) {
		$ratio = $s['ratio'] ? $s['ratio'] : $fallback;
		return $ratio ? '--sg-ratio:' . $ratio . ';' : '';
	}

	private static function render_grid( $attachments, $s, $uid ) {
		$class = 'sg-grid sg-cols-' . $s['columns'] . ( $s['ratio'] ? '' : ' sg-grid--natural' );

		$html = '<div class="' . esc_attr( $class ) . '" style="' . esc_attr( self::ratio_style( $s ) ) . '">';
		foreach ( $attachments as $attachment ) {
			$html .= self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $s['columns'] ) ) );
		}
		return $html . '</div>';
	}

	private static function render_masonry( $attachments, $s, $uid ) {
		$html  = '<div class="sg-masonry sg-cols-' . absint( $s['columns'] ) . '" data-sg-masonry data-sg-gutter="' . absint( $s['gap'] ) . '">';
		$html .= '<div class="sg-masonry-sizer"></div>';
		foreach ( $attachments as $attachment ) {
			$html .= self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $s['columns'] ) ) );
		}
		return $html . '</div>';
	}

	private static function render_justified( $attachments, $s, $uid ) {
		$html = '<div class="sg-justified" style="--sg-row-h:' . absint( $s['justified_row_height'] ) . 'px;">';
		foreach ( $attachments as $attachment ) {
			$src = wp_get_attachment_image_src( $attachment->ID, $s['size'] );
			$ar  = ( $src && $src[1] && $src[2] ) ? $src[1] / $src[2] : 1;
			// Keep extreme panoramas/strips from taking over a whole row.
			$ar = max( 0.25, min( 4, $ar ) );

			$html .= self::item( $attachment, $s, $uid, array(), ' style="--sg-ar:' . esc_attr( number_format( $ar, 4, '.', '' ) ) . ';"' );
		}
		return $html . '</div>';
	}

	private static function render_mosaic( $attachments, $s, $uid ) {
		$cols = max( 3, $s['columns'] );
		$html = '<div class="sg-mosaic sg-cols-' . absint( $cols ) . '">';
		foreach ( $attachments as $attachment ) {
			$html .= self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $cols / 2 ) ) );
		}
		return $html . '</div>';
	}

	private static function render_polaroid( $attachments, $s, $uid ) {
		$html = '<div class="sg-polaroid sg-cols-' . absint( $s['columns'] ) . '">';
		foreach ( $attachments as $attachment ) {
			$html .= self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $s['columns'] ) ) );
		}
		return $html . '</div>';
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
		return $html . '</ul></div>';
	}

	private static function swiper_data( $s ) {
		return ' data-sg-autoplay="' . ( $s['autoplay'] ? absint( $s['autoplay_delay'] ) * 1000 : 0 ) . '"';
	}

	private static function swiper_controls( $pagination = true ) {
		return ( $pagination ? '<div class="swiper-pagination"></div>' : '' )
			. '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
	}

	private static function render_slider( $attachments, $s, $uid ) {
		$w    = $s['image_width'];
		$h    = $s['image_height'];
		$unit = $s['image_measure_value'];

		if ( '%' === $unit ) {
			// A percentage height has nothing to resolve against inside the
			// carousel, so keep the requested proportions via aspect-ratio.
			$slide_style = 'width:' . $w . '%;aspect-ratio:' . $w . ' / ' . $h . ';';
		} elseif ( 'vh' === $unit ) {
			$slide_style = 'width:' . $w . 'vw;height:' . $h . 'vh;';
		} else {
			$slide_style = 'width:' . $w . $unit . ';height:' . $h . $unit . ';';
		}

		$html  = '<div class="sg-slider swiper" data-sg-slider' . self::swiper_data( $s ) . '>';
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
		$html .= '</div>' . self::swiper_controls() . '</div>';

		return $html;
	}

	private static function render_carousel( $attachments, $s, $uid ) {
		$html  = '<div class="sg-carousel swiper" data-sg-carousel data-sg-cols="' . absint( $s['columns'] ) . '" data-sg-gutter="' . absint( $s['gap'] ) . '"' . self::swiper_data( $s ) . ' style="' . esc_attr( self::ratio_style( $s, '4 / 3' ) ) . '">';
		$html .= '<div class="swiper-wrapper">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="swiper-slide">' . self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $s['columns'] ) ) ) . '</div>';
		}
		$html .= '</div>' . self::swiper_controls() . '</div>';

		return $html;
	}

	private static function render_slideshow( $attachments, $s, $uid ) {
		$html  = '<div class="sg-slideshow" data-sg-slideshow' . self::swiper_data( $s ) . ' style="' . esc_attr( self::ratio_style( $s, '3 / 2' ) ) . '">';
		$html .= '<div class="swiper sg-slideshow-main"><div class="swiper-wrapper">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="swiper-slide">';
			$html .= self::link_open( $attachment, $s, $uid );
			$html .= self::image( $attachment, $s['size'], array( 'sizes' => '100vw' ) );
			$html .= self::link_close( $s );
			$html .= '</div>';
		}
		$html .= '</div>' . self::swiper_controls( false ) . '</div>';

		$html .= '<div class="swiper sg-slideshow-thumbs"><div class="swiper-wrapper">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="swiper-slide">' . self::image( $attachment, 'thumbnail', array( 'alt' => '' ) ) . '</div>';
		}
		$html .= '</div></div>';

		return $html . '</div>';
	}

	/**
	 * Images are paired in order: 1+2, 3+4, ... The first of each pair is
	 * "before". A trailing unpaired image is shown on its own.
	 */
	private static function render_compare( $attachments, $s, $uid ) {
		$html  = '<div class="sg-compare-list sg-cols-' . absint( min( 3, $s['columns'] ) ) . '">';
		$pairs = array_chunk( $attachments, 2 );

		foreach ( $pairs as $pair ) {
			if ( 1 === count( $pair ) ) {
				$html .= '<div class="sg-compare sg-compare--single">' . self::image( $pair[0], $s['size'] ) . '</div>';
				continue;
			}

			list( $before, $after ) = $pair;
			$src = wp_get_attachment_image_src( $before->ID, $s['size'] );
			$ar  = ( $src && $src[1] && $src[2] ) ? number_format( $src[1] / $src[2], 4, '.', '' ) : '1.5';

			$html .= '<div class="sg-compare" data-sg-compare style="--sg-pos:50%;aspect-ratio:' . esc_attr( $ar ) . ';">';
			$html .= self::image( $after, $s['size'], array( 'class' => 'sg-img sg-compare-after' ) );
			$html .= '<div class="sg-compare-before">' . self::image( $before, $s['size'] ) . '</div>';
			$html .= '<span class="sg-compare-label sg-compare-label--before">' . esc_html__( 'Before', 'spartan-gallery' ) . '</span>';
			$html .= '<span class="sg-compare-label sg-compare-label--after">' . esc_html__( 'After', 'spartan-gallery' ) . '</span>';
			$html .= '<span class="sg-compare-handle" aria-hidden="true"></span>';
			$html .= '<input type="range" class="sg-compare-range" min="0" max="100" value="50" aria-label="' . esc_attr__( 'Drag to compare before and after', 'spartan-gallery' ) . '" />';
			$html .= '</div>';
		}

		return $html . '</div>';
	}

	/**
	 * Standalone HTML document for the admin live preview iframe.
	 */
	public static function preview_document( $markup ) {
		$a    = self::asset_urls();
		$ver  = rawurlencode( PLUGIN_NAME_VERSION );
		$head = '';
		foreach ( $a['css'] as $href ) {
			$head .= '<link rel="stylesheet" href="' . esc_url( add_query_arg( 'ver', $ver, $href ) ) . '" />';
		}
		$foot = '';
		foreach ( $a['js'] as $src ) {
			$foot .= '<script src="' . esc_url( add_query_arg( 'ver', $ver, $src ) ) . '"></script>';
		}

		return '<!doctype html><html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />'
			. $head
			. '<style>html,body{margin:0;padding:12px;background:#fff;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>'
			. '</head><body>' . $markup . $foot . '</body></html>';
	}
}
