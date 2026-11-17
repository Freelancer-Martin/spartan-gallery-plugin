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
	const SWIPER_LAYOUTS = array( 'slider', 'carousel', 'slideshow', 'cards', 'cube', 'hero' );

	/** Layouts where the hover effect setting applies. */
	const HOVER_LAYOUTS = array( 'grid', 'masonry', 'justified', 'mosaic', 'metro', 'polaroid', 'filmstrip', 'carousel' );

	/** Layouts made of independent tiles: these support filtering and "load more". */
	const COLLECTION_LAYOUTS = array( 'grid', 'masonry', 'justified', 'mosaic', 'polaroid' );

	/** Per-request counter so several galleries on one page get unique ids. */
	private static $instance = 0;

	/** Position of the tile being rendered, for "load more" (reset per gallery). */
	private static $item_index = 0;

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
			// Loaded on demand by GLightbox when a video is opened.
			'plyr' => array(
				'js'   => $url . 'vendor/plyr/plyr.min.js',
				'css'  => $url . 'vendor/plyr/plyr.css',
				'icon' => $url . 'vendor/plyr/plyr.svg',
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
		$ver = SPARTAN_GALLERY_VERSION;

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
		wp_add_inline_script( 'spartan-gallery', self::js_config(), 'before' );
	}

	/**
	 * Settings + translated strings for spartan-gallery-public.js.
	 */
	public static function js_config() {
		$a = self::asset_urls();
		$config = array(
			'plyr' => array(
				'js'   => add_query_arg( 'ver', '3.7.8', $a['plyr']['js'] ),
				'css'  => add_query_arg( 'ver', '3.7.8', $a['plyr']['css'] ),
				'icon' => $a['plyr']['icon'],
			),
			'i18n' => array(
				'download'  => __( 'Download image', 'spartan-gallery' ),
				'play'      => __( 'Play slideshow', 'spartan-gallery' ),
				'pause'     => __( 'Pause slideshow', 'spartan-gallery' ),
				'share'     => __( 'Share', 'spartan-gallery' ),
				'copyLink'  => __( 'Copy link', 'spartan-gallery' ),
				'copied'    => __( 'Link copied', 'spartan-gallery' ),
				/* translators: %d: image number */
				'image'     => __( 'Image %d', 'spartan-gallery' ),
				'previous'  => __( 'Previous', 'spartan-gallery' ),
				'next'      => __( 'Next', 'spartan-gallery' ),
			),
		);
		return 'window.spartanGalleryConfig = ' . wp_json_encode( $config ) . ';';
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
	 * The images a gallery shows: hand-picked IDs, or every image with the
	 * chosen Gallery Tag (newest/oldest/title/random, up to the limit).
	 *
	 * @param array  $settings  From Spartan_Gallery_Settings.
	 * @param string $image_ids Comma-separated IDs (used for manual galleries).
	 * @return WP_Post[]
	 */
	public static function gallery_attachments( array $settings, $image_ids ) {
		if ( 'tag' !== $settings['source'] ) {
			return self::get_attachments( Spartan_Gallery_Settings::parse_image_ids( $image_ids ) );
		}
		if ( ! $settings['source_tag'] || ! term_exists( (int) $settings['source_tag'], Spartan_Gallery_Taxonomy::TAXONOMY ) ) {
			return array();
		}

		$orders = array(
			'date_desc' => array( 'date', 'DESC' ),
			'date_asc'  => array( 'date', 'ASC' ),
			'title'     => array( 'title', 'ASC' ),
			'random'    => array( 'rand', 'ASC' ),
		);
		list( $orderby, $order ) = $orders[ $settings['source_order'] ];

		return get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => $settings['source_limit'],
				'orderby'        => $orderby,
				'order'          => $order,
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the whole point of a tag-based gallery.
					array(
						'taxonomy' => Spartan_Gallery_Taxonomy::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => (int) $settings['source_tag'],
					),
				),
			)
		);
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

		$collection   = in_array( $s['layout'], self::COLLECTION_LAYOUTS, true );
		$s['filter']  = $collection && $s['filter_enabled'];
		$s['paged']   = $collection && 'none' !== $s['pagination'];
		$s['tag_map'] = array();

		return $s;
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Render a saved gallery for the front end.
	 *
	 * @param int   $gallery_id Spartan Gallery post ID.
	 * @param array $overrides  Per-placement overrides; currently only 'layout'
	 *                          (from the shortcode's layout="" or the block).
	 * @return string HTML (empty string if the gallery doesn't exist or isn't viewable).
	 */
	public static function render( $gallery_id, array $overrides = array() ) {
		$gallery_id = absint( $gallery_id );
		$gallery    = $gallery_id ? get_post( $gallery_id ) : null;

		if ( ! $gallery || self::POST_TYPE !== $gallery->post_type ) {
			return '';
		}
		// Don't leak draft/private galleries to visitors.
		if ( 'publish' !== $gallery->post_status && ! current_user_can( 'read_post', $gallery_id ) ) {
			return '';
		}

		$settings = Spartan_Gallery_Settings::get( $gallery_id );
		if ( ! empty( $overrides['layout'] ) && in_array( $overrides['layout'], Spartan_Gallery_Settings::layouts(), true ) ) {
			$settings['image_layout_select'] = $overrides['layout'];
		}

		$attachments = self::gallery_attachments( $settings, get_post_meta( $gallery_id, 'spartan_gallery_uploaded_images', true ) );
		if ( empty( $attachments ) ) {
			return '<!-- spartan-gallery: no images -->';
		}

		$s = self::prepare( $settings );

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

		self::$item_index = 0;

		$filter_bar = '';
		if ( $s['filter'] ) {
			list( $s['tag_map'], $terms ) = Spartan_Gallery_Taxonomy::tags_for( wp_list_pluck( $attachments, 'ID' ) );
			$filter_bar = self::filter_bar( $terms );
			if ( '' === $filter_bar ) {
				$s['filter'] = false;
			}
		}

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
			case 'metro':
				$inner = self::render_metro( $attachments, $s, $uid );
				break;
			case 'filmstrip':
				$inner = self::render_filmstrip( $attachments, $s, $uid );
				break;
			case 'spotlight':
				$inner = self::render_spotlight( $attachments, $s, $uid );
				break;
			case 'cards':
				$inner = self::render_cards( $attachments, $s, $uid );
				break;
			case 'cube':
				$inner = self::render_cube( $attachments, $s, $uid );
				break;
			case 'hero':
				$inner = self::render_hero( $attachments, $s, $uid );
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
		if ( $s['protect_images'] ) {
			$classes[] = 'sg-protect';
		}
		if ( 'hero' === $s['layout'] && $s['hero_fullwidth'] ) {
			$classes[] = 'sg-fullwidth';
		}

		$data = '';
		if ( $s['protect_images'] ) {
			$data .= ' data-sg-protect';
		}
		if ( '' !== $s['watermark_text'] ) {
			$data .= ' data-sg-watermark="' . esc_attr( $s['watermark_text'] ) . '"';
		}
		if ( $s['filter'] ) {
			$data .= ' data-sg-filterable';
		}
		if ( $s['paged'] ) {
			$data .= ' data-sg-per-page="' . absint( $s['per_page'] ) . '" data-sg-paging="' . esc_attr( $s['pagination'] ) . '"';
		}
		if ( $s['lightbox'] ) {
			$data .= ' data-sg-lb-thumbs="' . ( $s['lightbox_thumbnails'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-lb-download="' . ( $s['lightbox_download'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-lb-slideshow="' . ( $s['lightbox_slideshow'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-delay="' . absint( $s['autoplay_delay'] ) . '"';
			$data .= ' data-sg-lb-share="' . ( $s['lightbox_share'] ? '1' : '0' ) . '"';
			$data .= ' data-sg-deeplink="' . ( $s['deep_linking'] ? '1' : '0' ) . '"';
		}

		$html = '';
		if ( '' !== trim( $s['add_extra_css'] ) ) {
			$html .= '<style>' . $s['add_extra_css'] . '</style>';
		}
		$html .= '<div class="spartan-gallery-box">';
		$html .= '<div id="' . esc_attr( $uid ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( self::container_style( $s ) ) . '"' . $data . '>';
		$html .= $filter_bar;
		$html .= $inner;
		if ( $s['paged'] && count( $attachments ) > $s['per_page'] ) {
			$html .= '<div class="sg-more-wrap"><button type="button" class="sg-more">' . esc_html__( 'Load more', 'spartan-gallery' ) . '</button></div>';
			// Without JavaScript there's nothing to reveal the rest, so show everything.
			$html .= '<noscript><style>#' . esc_attr( $uid ) . ' .sg-paged-out{display:block!important}#' . esc_attr( $uid ) . ' .sg-more-wrap{display:none}</style></noscript>';
		}
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * @param WP_Term[] $terms Tags used by this gallery's images.
	 */
	private static function filter_bar( $terms ) {
		if ( empty( $terms ) ) {
			return '';
		}
		$html  = '<div class="sg-filters" role="toolbar" aria-label="' . esc_attr__( 'Filter images', 'spartan-gallery' ) . '">';
		$html .= '<button type="button" class="sg-filter is-active" data-filter="*" aria-pressed="true">' . esc_html__( 'All', 'spartan-gallery' ) . '</button>';
		foreach ( $terms as $term ) {
			$html .= '<button type="button" class="sg-filter" data-filter="' . esc_attr( $term->slug ) . '" aria-pressed="false">' . esc_html( $term->name ) . '</button>';
		}
		return $html . '</div>';
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

		$full  = wp_get_attachment_image_src( $attachment->ID, 'full' );
		$href  = $full ? $full[0] : wp_get_attachment_url( $attachment->ID );
		$video = self::video_url( $attachment->ID );
		if ( $video ) {
			// The image stays as the tile / poster; the lightbox plays the video.
			$href   = $video;
			$class .= ' sg-has-video';
		}

		$attrs = ' href="' . esc_url( $href ) . '" class="' . esc_attr( $class . ' sg-lightbox' ) . '" data-gallery="' . esc_attr( $uid ) . '"';
		if ( $video ) {
			$attrs .= ' data-sg-thumb="' . esc_url( $full ? $full[0] : '' ) . '"';
		}

		// The title is off by default: WordPress fills it with the file name
		// ("IMG_1234") unless someone edits it in the Media Library.
		if ( $s['lightbox_title'] ) {
			$title = self::lightbox_text( $attachment->post_title );
			if ( '' !== $title ) {
				$attrs .= ' data-title="' . $title . '"';
			}
		}
		if ( $s['lightbox_captions'] ) {
			$desc = self::lightbox_text( $attachment->post_excerpt );
			if ( '' !== $desc ) {
				$attrs .= ' data-description="' . $desc . '"';
			}
		}

		return '<a' . $attrs . '>';
	}

	private static function link_close( $s ) {
		$mark = '' !== $s['watermark_text'] ? '<span class="sg-watermark" aria-hidden="true">' . esc_html( $s['watermark_text'] ) . '</span>' : '';
		return $mark . ( $s['lightbox'] ? '</a>' : '</span>' );
	}

	/**
	 * YouTube / Vimeo / .mp4 / .webm URL attached to an image (see the
	 * "Video URL" field), or '' when the image is just an image.
	 */
	public static function video_url( $attachment_id ) {
		$url = (string) get_post_meta( $attachment_id, '_sg_video_url', true );
		return self::is_video_url( $url ) ? $url : '';
	}

	public static function is_video_url( $url ) {
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return false;
		}
		return (bool) preg_match( '~^https?://((www\.|m\.)?youtube\.com/|youtu\.be/|(www\.|player\.)?vimeo\.com/)|\.(mp4|webm|ogv)(\?.*)?$~i', $url );
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
		$class = 'sg-item';
		if ( $s['paged'] && self::$item_index >= $s['per_page'] ) {
			$class .= ' sg-paged-out';
		}
		++self::$item_index;

		if ( $s['filter'] ) {
			$tags       = isset( $s['tag_map'][ $attachment->ID ] ) ? $s['tag_map'][ $attachment->ID ] : array();
			$item_attr .= ' data-sg-tags="' . esc_attr( implode( ' ', $tags ) ) . '"';
		}

		return '<div class="' . $class . '"' . $item_attr . '>'
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
	 * Metro tiles: a fixed rhythm of big, wide, tall and small tiles on a
	 * square-cell grid (sized with container query units, see the CSS).
	 */
	private static function render_metro( $attachments, $s, $uid ) {
		$cols = max( 3, $s['columns'] );
		$html = '<div class="sg-metro-wrap"><div class="sg-metro sg-cols-' . absint( $cols ) . '">';
		foreach ( $attachments as $attachment ) {
			$html .= self::item( $attachment, $s, $uid, array( 'sizes' => self::column_sizes( $cols / 2 ) ) );
		}
		return $html . '</div></div>';
	}

	/** One horizontally scrolling row (CSS scroll-snap) with arrow buttons. */
	private static function render_filmstrip( $attachments, $s, $uid ) {
		$html  = '<div class="sg-filmstrip" data-sg-filmstrip style="--sg-row-h:' . absint( $s['justified_row_height'] ) . 'px;">';
		$html .= '<button type="button" class="sg-strip-btn sg-strip-prev" aria-label="' . esc_attr__( 'Previous', 'spartan-gallery' ) . '">&#8249;</button>';
		$html .= '<div class="sg-strip-track" tabindex="0" role="region" aria-label="' . esc_attr__( 'Gallery', 'spartan-gallery' ) . '">';
		foreach ( $attachments as $attachment ) {
			$src = wp_get_attachment_image_src( $attachment->ID, $s['size'] );
			$ar  = ( $src && $src[1] && $src[2] ) ? max( 0.4, min( 3, $src[1] / $src[2] ) ) : 1.5;
			$html .= self::item( $attachment, $s, $uid, array(), ' style="--sg-ar:' . esc_attr( number_format( $ar, 4, '.', '' ) ) . ';"' );
		}
		$html .= '</div>';
		$html .= '<button type="button" class="sg-strip-btn sg-strip-next" aria-label="' . esc_attr__( 'Next', 'spartan-gallery' ) . '">&#8250;</button>';
		return $html . '</div>';
	}

	/** One large image plus a grid of thumbnails that switch it. */
	private static function render_spotlight( $attachments, $s, $uid ) {
		$main   = '';
		$thumbs = '';
		foreach ( $attachments as $i => $attachment ) {
			$main .= '<div class="sg-spotlight-panel" id="' . esc_attr( $uid . '-p' . $i ) . '"' . ( $i ? ' hidden' : '' ) . '>';
			$main .= self::link_open( $attachment, $s, $uid );
			$main .= self::image( $attachment, $s['size'], array( 'sizes' => '(max-width: 900px) 100vw, 66vw' ) );
			$main .= self::link_close( $s );
			$main .= '</div>';

			/* translators: %d: image number */
			$label   = sprintf( __( 'Show image %d', 'spartan-gallery' ), $i + 1 );
			$thumbs .= '<button type="button" class="sg-spotlight-thumb' . ( $i ? '' : ' is-active' ) . '" data-index="' . absint( $i ) . '" aria-controls="' . esc_attr( $uid . '-p' . $i ) . '" aria-pressed="' . ( $i ? 'false' : 'true' ) . '" aria-label="' . esc_attr( $label ) . '">';
			$thumbs .= self::image( $attachment, 'thumbnail', array( 'alt' => '' ) );
			$thumbs .= '</button>';
		}
		return '<div class="sg-spotlight" data-sg-spotlight style="' . esc_attr( self::ratio_style( $s, '4 / 3' ) ) . '"><div class="sg-spotlight-main">' . $main . '</div><div class="sg-spotlight-thumbs">' . $thumbs . '</div></div>';
	}

	/** Shared markup for the single-slide Swiper layouts (cards, cube, hero). */
	private static function simple_swiper( $attachments, $s, $uid, $class, $data_attr, $style, $slide_extra = null, $pagination = true ) {
		$html  = '<div class="' . esc_attr( $class ) . ' swiper" ' . $data_attr . self::swiper_data( $s ) . ' style="' . esc_attr( $style ) . '">';
		$html .= '<div class="swiper-wrapper">';
		foreach ( $attachments as $attachment ) {
			$html .= '<div class="swiper-slide">';
			$html .= self::link_open( $attachment, $s, $uid );
			$html .= self::image( $attachment, $s['size'], array( 'sizes' => 'hero' === $s['layout'] ? '100vw' : '(max-width: 600px) 90vw, 560px' ) );
			$html .= self::link_close( $s );
			if ( $slide_extra ) {
				$html .= call_user_func( $slide_extra, $attachment );
			}
			$html .= '</div>';
		}
		return $html . '</div>' . self::swiper_controls( $pagination ) . '</div>';
	}

	private static function render_cards( $attachments, $s, $uid ) {
		return '<div class="sg-cards-wrap">' . self::simple_swiper( $attachments, $s, $uid, 'sg-cards', 'data-sg-cards', self::ratio_style( $s, '3 / 4' ), null, false ) . '</div>';
	}

	private static function render_cube( $attachments, $s, $uid ) {
		return '<div class="sg-cube-wrap">' . self::simple_swiper( $attachments, $s, $uid, 'sg-cube', 'data-sg-cube', self::ratio_style( $s, '1 / 1' ) ) . '</div>';
	}

	private static function render_hero( $attachments, $s, $uid ) {
		$text = function ( $attachment ) use ( $s ) {
			if ( ! $s['hero_text'] ) {
				return '';
			}
			$title   = $s['lightbox_title'] ? trim( wp_strip_all_tags( $attachment->post_title ) ) : '';
			$caption = trim( wp_strip_all_tags( $attachment->post_excerpt ) );
			if ( '' === $title && '' === $caption ) {
				return '';
			}
			return '<div class="sg-hero-text">'
				. ( '' !== $title ? '<p class="sg-hero-title">' . esc_html( $title ) . '</p>' : '' )
				. ( '' !== $caption ? '<p class="sg-hero-caption">' . esc_html( $caption ) . '</p>' : '' )
				. '</div>';
		};
		return self::simple_swiper( $attachments, $s, $uid, 'sg-hero', 'data-sg-hero', '--sg-hero-h:' . absint( $s['hero_height'] ) . 'vh;', $text );
	}

	/* ------------------------------------------------------------------ */
	/* Albums                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * A grid of gallery covers. Clicking a cover opens that gallery's images
	 * in the lightbox.
	 *
	 * @param int[] $gallery_ids Empty = all published galleries.
	 * @param array $args        columns (1-6), titles (bool).
	 */
	public static function render_album( array $gallery_ids, array $args = array() ) {
		$args = wp_parse_args( $args, array( 'columns' => 3, 'titles' => true ) );
		$cols = max( 1, min( 6, absint( $args['columns'] ) ) );

		$query = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		);
		if ( $gallery_ids ) {
			$query['post__in'] = $gallery_ids;
			$query['orderby']  = 'post__in';
		} else {
			$query['orderby'] = array( 'menu_order' => 'ASC', 'date' => 'DESC' );
		}
		$galleries = get_posts( $query );

		$uid   = 'sg-album-' . ( ++self::$instance );
		$cards = '';

		foreach ( $galleries as $gallery ) {
			$settings    = Spartan_Gallery_Settings::get( $gallery->ID );
			$attachments = self::gallery_attachments( $settings, get_post_meta( $gallery->ID, 'spartan_gallery_uploaded_images', true ) );
			if ( empty( $attachments ) ) {
				continue;
			}

			$s                    = self::prepare( $settings );
			$s['lightbox']        = true;
			$group                = $uid . '-' . $gallery->ID;
			$cover                = array_shift( $attachments );
			$count                = count( $attachments ) + 1;

			$cards .= '<div class="sg-item sg-album-card">';
			$cards .= self::link_open( $cover, $s, $group, 'sg-album-cover' );
			$cards .= self::image( $cover, 'large', array( 'sizes' => self::column_sizes( $cols ) ) );
			if ( $args['titles'] ) {
				$cards .= '<span class="sg-album-meta"><span class="sg-album-title">' . esc_html( get_the_title( $gallery ) ) . '</span>';
				/* translators: %s: number of images */
				$cards .= '<span class="sg-album-count">' . esc_html( sprintf( _n( '%s photo', '%s photos', $count, 'spartan-gallery' ), number_format_i18n( $count ) ) ) . '</span></span>';
			}
			$cards .= '</a>';

			// The rest of the gallery: lightbox entries without visible tiles.
			foreach ( $attachments as $attachment ) {
				$thumb  = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
				$open   = self::link_open( $attachment, $s, $group );
				$extra  = false === strpos( $open, 'data-sg-thumb=' ) ? ' data-sg-thumb="' . esc_url( $thumb ? $thumb[0] : '' ) . '"' : '';
				$cards .= str_replace( '<a ', '<a hidden' . $extra . ' ', $open ) . '</a>';
			}
			$cards .= '</div>';
		}

		if ( '' === $cards ) {
			return '<!-- spartan-gallery: empty album -->';
		}

		self::enqueue_assets( 'album', true );

		return '<div class="spartan-gallery-box"><div id="' . esc_attr( $uid ) . '" class="spartan-gallery-wrapper sg-gallery sg-layout-album sg-hover-zoom" data-sg-lb-thumbs="1" data-sg-lb-download="0" data-sg-lb-slideshow="1" data-sg-delay="4">'
			. '<div class="sg-album sg-cols-' . $cols . '">' . $cards . '</div></div></div>';
	}

	/**
	 * Standalone HTML document for the admin live preview iframe.
	 */
	public static function preview_document( $markup ) {
		$a    = self::asset_urls();
		$ver  = rawurlencode( SPARTAN_GALLERY_VERSION );
		$head = '';
		foreach ( $a['css'] as $href ) {
			$head .= '<link rel="stylesheet" href="' . esc_url( add_query_arg( 'ver', $ver, $href ) ) . '" />'; // phpcs:ignore WordPress.WP.EnqueuedResources -- iframe srcdoc document, see below.
		}
		// This is a standalone HTML document for the admin preview iframe's
		// srcdoc, not a WordPress page, so the enqueue API isn't available here.
		// phpcs:disable WordPress.WP.EnqueuedResources
		$foot = '<script>' . self::js_config() . '</script>';
		foreach ( $a['js'] as $src ) {
			$foot .= '<script src="' . esc_url( add_query_arg( 'ver', $ver, $src ) ) . '"></script>';
		}
		// phpcs:enable WordPress.WP.EnqueuedResources

		return '<!doctype html><html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />'
			. $head
			. '<style>html,body{margin:0;padding:12px;background:#fff;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>'
			. '</head><body>' . $markup . $foot . '</body></html>';
	}
}
