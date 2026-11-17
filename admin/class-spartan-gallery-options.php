<?php
/**
 * Spartan Gallery Options metabox + live preview.
 *
 * Fields, defaults and sanitizing come from Spartan_Gallery_Settings; this
 * class only lays them out, saves them and serves the live preview.
 *
 * @package Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Options {

	const POST_TYPE      = 'spartan_gallery_type';
	const NONCE_ACTION   = 'spartan_gallery_options_save';
	const NONCE_NAME     = 'spartan_gallery_options_nonce';
	const PREVIEW_ACTION = 'spartan_gallery_preview';

	/** Layouts grouped for the admin (value => [label, preview svg, description]). */
	private static function layouts() {
		return array(
			'grid'      => array( __( 'Grid', 'spartan-gallery' ), __( 'Even columns, cropped to one shape.', 'spartan-gallery' ) ),
			'masonry'   => array( __( 'Masonry', 'spartan-gallery' ), __( 'Uncropped, packed into columns.', 'spartan-gallery' ) ),
			'justified' => array( __( 'Justified', 'spartan-gallery' ), __( 'Equal-height rows, full width.', 'spartan-gallery' ) ),
			'mosaic'    => array( __( 'Mosaic', 'spartan-gallery' ), __( 'Mix of large and small tiles.', 'spartan-gallery' ) ),
			'polaroid'  => array( __( 'Polaroid', 'spartan-gallery' ), __( 'Tilted instant-photo cards.', 'spartan-gallery' ) ),
			'hexagon'   => array( __( 'Hexagon', 'spartan-gallery' ), __( 'Honeycomb grid.', 'spartan-gallery' ) ),
			'slider'    => array( __( '3D Slider', 'spartan-gallery' ), __( '3D coverflow carousel.', 'spartan-gallery' ) ),
			'carousel'  => array( __( 'Carousel', 'spartan-gallery' ), __( 'Several images side by side, swipeable.', 'spartan-gallery' ) ),
			'slideshow' => array( __( 'Slideshow', 'spartan-gallery' ), __( 'One large image with thumbnails.', 'spartan-gallery' ) ),
			'compare'   => array( __( 'Before / After', 'spartan-gallery' ), __( 'Drag to compare image pairs (1+2, 3+4…).', 'spartan-gallery' ) ),
		);
	}

	/**
	 * One-click starting points. Only the listed fields are changed.
	 */
	private static function presets() {
		return array(
			'portfolio'   => array(
				'label'  => __( 'Portfolio', 'spartan-gallery' ),
				'values' => array( 'image_layout_select' => 'grid', 'gallery_columns' => 3, 'grid_aspect_ratio' => '4-3', 'img_margin' => 16, 'hover_effect' => 'title', 'image_radius' => 4, 'lightbox_enabled' => true, 'lightbox_captions' => true, 'lightbox_thumbnails' => true ),
			),
			'instagram'   => array(
				'label'  => __( 'Instagram', 'spartan-gallery' ),
				'values' => array( 'image_layout_select' => 'grid', 'gallery_columns' => 3, 'grid_aspect_ratio' => '1-1', 'img_margin' => 4, 'hover_effect' => 'darken', 'image_radius' => 0, 'lightbox_enabled' => true ),
			),
			'products'    => array(
				'label'  => __( 'Products', 'spartan-gallery' ),
				'values' => array( 'image_layout_select' => 'grid', 'gallery_columns' => 4, 'grid_aspect_ratio' => '1-1', 'img_margin' => 20, 'hover_effect' => 'zoom', 'image_radius' => 8, 'lightbox_enabled' => true, 'lightbox_thumbnails' => true ),
			),
			'photography' => array(
				'label'  => __( 'Photography', 'spartan-gallery' ),
				'values' => array( 'image_layout_select' => 'justified', 'justified_row_height' => 280, 'img_margin' => 6, 'hover_effect' => 'fade', 'image_quality' => 'full', 'lightbox_enabled' => true, 'lightbox_thumbnails' => true, 'lightbox_download' => true, 'lightbox_slideshow' => true ),
			),
			'story'       => array(
				'label'  => __( 'Slideshow', 'spartan-gallery' ),
				'values' => array( 'image_layout_select' => 'slideshow', 'grid_aspect_ratio' => '16-9', 'autoplay' => true, 'autoplay_delay' => 5, 'lightbox_enabled' => true ),
			),
		);
	}

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_' . self::PREVIEW_ACTION, array( __CLASS__, 'ajax_preview' ) );
	}

	public static function add_meta_boxes() {
		add_meta_box( 'spartan-gallery-options', esc_html__( 'Spartan Gallery Options', 'spartan-gallery' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'spartan-gallery-preview', esc_html__( 'Live Preview', 'spartan-gallery' ), array( __CLASS__, 'render_preview_box' ), self::POST_TYPE, 'normal', 'default' );
	}

	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'spartan-gallery-options', plugin_dir_url( __FILE__ ) . 'css/spartan-gallery-options.css', array( 'wp-color-picker' ), PLUGIN_NAME_VERSION );

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-spinner' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'wp-color-picker' );

		$cm_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

		wp_enqueue_script(
			'spartan-gallery-options',
			plugin_dir_url( __FILE__ ) . 'js/spartan-gallery-options.js',
			array( 'jquery', 'jquery-ui-spinner', 'jquery-ui-sortable', 'wp-color-picker', 'media-editor' ),
			PLUGIN_NAME_VERSION,
			true
		);

		$presets = array();
		foreach ( self::presets() as $key => $preset ) {
			$presets[ $key ] = $preset['values'];
		}

		wp_localize_script(
			'spartan-gallery-options',
			'sgOptionsData',
			array(
				'codeEditor'     => $cm_settings,
				'mediaTitle'     => esc_html__( 'Select gallery images', 'spartan-gallery' ),
				'mediaButton'    => esc_html__( 'Use selected images', 'spartan-gallery' ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'previewAction'  => self::PREVIEW_ACTION,
				'previewNonce'   => wp_create_nonce( self::PREVIEW_ACTION ),
				'presets'        => $presets,
				'copied'         => esc_html__( 'Copied!', 'spartan-gallery' ),
				'copy'           => esc_html__( 'Copy', 'spartan-gallery' ),
				'removeLabel'    => esc_html__( 'Remove', 'spartan-gallery' ),
				'previewFailed'  => esc_html__( 'Preview could not be loaded.', 'spartan-gallery' ),
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Field helpers                                                       */
	/* ------------------------------------------------------------------ */

	private static function tabs() {
		return array(
			'layout'    => array( __( 'Layout', 'spartan-gallery' ), 'dashicons-layout' ),
			'upload'    => array( __( 'Images', 'spartan-gallery' ), 'dashicons-format-gallery' ),
			'style'     => array( __( 'Image Style', 'spartan-gallery' ), 'dashicons-admin-customizer' ),
			'browse'    => array( __( 'Filter & Loading', 'spartan-gallery' ), 'dashicons-filter' ),
			'lightbox'  => array( __( 'Lightbox', 'spartan-gallery' ), 'dashicons-visibility' ),
			'container' => array( __( 'Container', 'spartan-gallery' ), 'dashicons-admin-appearance' ),
			'css'       => array( __( 'Custom CSS', 'spartan-gallery' ), 'dashicons-editor-code' ),
		);
	}

	private static function render_nav( $active ) {
		echo '<div class="sg-options-nav">';
		foreach ( self::tabs() as $id => $tab ) {
			list( $label, $icon ) = $tab;
			$class = 'sg-options-nav-item' . ( $id === $active ? ' active' : '' );
			echo '<button type="button" class="' . esc_attr( $class ) . '" data-panel="' . esc_attr( $id ) . '">';
			echo '<span class="dashicons ' . esc_attr( $icon ) . '"></span>';
			echo '<span class="sg-nav-label">' . esc_html( $label ) . '</span>';
			echo '</button>';
		}
		echo '</div>';
	}

	private static function section_open( $id, $title, $desc = '' ) {
		echo '<div class="sg-options-panel' . ( 'layout' === $id ? ' active' : '' ) . '" data-panel="' . esc_attr( $id ) . '">';
		echo '<h3 class="sg-options-section-title">' . esc_html( $title ) . '</h3>';
		if ( $desc ) {
			echo '<p class="sg-options-section-desc">' . esc_html( $desc ) . '</p>';
		}
		echo '<table class="form-table sg-options-table"><tbody>';
	}

	private static function section_close() {
		echo '</tbody></table></div>';
	}

	/**
	 * @param string[] $layouts Only show this row while one of these layouts is selected (empty = always).
	 */
	private static function row_open( $label, $for = '', $layouts = array() ) {
		echo '<tr' . ( $layouts ? ' data-sg-layouts="' . esc_attr( implode( ' ', $layouts ) ) . '"' : '' ) . '><th scope="row">';
		if ( $for ) {
			echo '<label for="' . esc_attr( $for ) . '">' . esc_html( $label ) . '</label>';
		} else {
			echo esc_html( $label );
		}
		echo '</th><td>';
	}

	private static function row_close( $desc = '' ) {
		if ( $desc ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
		}
		echo '</td></tr>';
	}

	private static function select( $key, $value, array $options ) {
		echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
		foreach ( $options as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( (string) $value, (string) $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	private static function number( $key, $value, $unit = '' ) {
		$f = Spartan_Gallery_Settings::fields()[ $key ];
		echo '<input type="text" class="sg-spinner" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" data-min="' . esc_attr( $f['min'] ) . '" data-max="' . esc_attr( $f['max'] ) . '" data-step="1" />';
		if ( $unit ) {
			echo ' <span class="sg-unit">' . esc_html( $unit ) . '</span>';
		}
	}

	private static function toggle( $key, $value, $label = '' ) {
		echo '<label class="sg-switch"><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( (bool) $value, true, false ) . ' /> ' . esc_html( $label ? $label : __( 'Enabled', 'spartan-gallery' ) ) . '</label>';
	}

	private static function color( $key, $value, $default ) {
		echo '<input type="text" class="sg-color-field" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	}

	/* ------------------------------------------------------------------ */
	/* Metabox                                                             */
	/* ------------------------------------------------------------------ */

	public static function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$id = $post->ID;
		$v  = Spartan_Gallery_Settings::get( $id );

		$shortcode = '[spartan_gallery id="' . absint( $id ) . '"]';
		echo '<div class="sg-shortcode-bar">';
		echo '<span class="dashicons dashicons-shortcode" aria-hidden="true"></span> ';
		echo '<label for="sg-shortcode">' . esc_html__( 'Shortcode', 'spartan-gallery' ) . '</label> ';
		echo '<input type="text" id="sg-shortcode" class="code" readonly="readonly" value="' . esc_attr( $shortcode ) . '" size="' . esc_attr( strlen( $shortcode ) + 2 ) . '" /> ';
		echo '<button type="button" class="button" id="sg-copy-shortcode">' . esc_html__( 'Copy', 'spartan-gallery' ) . '</button>';
		echo '<span class="sg-shortcode-hint">' . esc_html__( 'Paste it into any page or post, or use the "Spartan Gallery Block" in the block editor. To show several galleries as cover cards, use [spartan_album] or the "Spartan Album" block.', 'spartan-gallery' ) . '</span>';
		echo '</div>';

		echo '<div class="sg-options-wrap">';
		self::render_nav( 'layout' );
		echo '<div class="sg-options-content">';

		self::render_layout_panel( $v );
		self::render_upload_panel( $id );
		self::render_style_panel( $v );
		self::render_browse_panel( $v );
		self::render_lightbox_panel( $v );
		self::render_container_panel( $v );

		self::section_open( 'css', __( 'Custom CSS', 'spartan-gallery' ) );
		self::row_open( __( 'CSS Code', 'spartan-gallery' ), 'add_extra_css' );
		echo '<textarea id="add_extra_css" name="add_extra_css" rows="10" class="large-text code">' . esc_textarea( $v['add_extra_css'] ) . '</textarea>';
		self::row_close( __( 'The gallery container has the class .spartan-gallery-wrapper (plus .sg-layout-grid, .sg-layout-masonry, …).', 'spartan-gallery' ) );
		self::section_close();

		echo '</div>'; // .sg-options-content
		echo '</div>'; // .sg-options-wrap
	}

	private static function render_layout_panel( $v ) {
		self::section_open( 'layout', __( 'Layout', 'spartan-gallery' ) );

		echo '<tr><td colspan="2">';
		echo '<div class="sg-presets"><span class="sg-presets-label">' . esc_html__( 'Quick start:', 'spartan-gallery' ) . '</span> ';
		foreach ( self::presets() as $key => $preset ) {
			echo '<button type="button" class="button sg-preset" data-preset="' . esc_attr( $key ) . '">' . esc_html( $preset['label'] ) . '</button> ';
		}
		echo '</div>';

		echo '<div class="sg-image-select">';
		foreach ( self::layouts() as $value => $data ) {
			list( $label, $desc ) = $data;
			$selected = $v['image_layout_select'] === $value;
			echo '<label class="sg-image-select-option' . ( $selected ? ' selected' : '' ) . '" title="' . esc_attr( $desc ) . '">';
			echo '<input type="radio" name="image_layout_select" value="' . esc_attr( $value ) . '" ' . checked( $selected, true, false ) . ' />';
			echo '<img src="' . esc_url( plugins_url( 'img/layout-' . $value . '.svg', __FILE__ ) ) . '" alt="" />';
			echo '<span class="sg-layout-name">' . esc_html( $label ) . '</span>';
			echo '<span class="sg-layout-desc">' . esc_html( $desc ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
		echo '</td></tr>';

		self::row_open( __( 'Columns', 'spartan-gallery' ), 'gallery_columns', array( 'grid', 'masonry', 'mosaic', 'polaroid', 'carousel', 'compare' ) );
		self::select( 'gallery_columns', $v['gallery_columns'], array_combine( range( 1, 6 ), range( 1, 6 ) ) );
		self::row_close( __( 'Automatically reduced to 3 on tablets and 2 on phones.', 'spartan-gallery' ) );

		self::row_open( __( 'Image shape', 'spartan-gallery' ), 'grid_aspect_ratio', array( 'grid', 'carousel', 'slideshow' ) );
		self::select(
			'grid_aspect_ratio',
			$v['grid_aspect_ratio'],
			array(
				'1-1'      => __( 'Square (1:1)', 'spartan-gallery' ),
				'4-3'      => __( 'Landscape (4:3)', 'spartan-gallery' ),
				'3-2'      => __( 'Landscape (3:2)', 'spartan-gallery' ),
				'16-9'     => __( 'Widescreen (16:9)', 'spartan-gallery' ),
				'3-4'      => __( 'Portrait (3:4)', 'spartan-gallery' ),
				'original' => __( 'Original (no cropping)', 'spartan-gallery' ),
			)
		);
		self::row_close( __( 'Images are cropped from the center to fit.', 'spartan-gallery' ) );

		self::row_open( __( 'Row height', 'spartan-gallery' ), 'justified_row_height', array( 'justified' ) );
		self::number( 'justified_row_height', $v['justified_row_height'], 'px' );
		self::row_close( __( 'Rows stretch slightly so they always fill the full width.', 'spartan-gallery' ) );

		self::row_open( __( 'Gap between images', 'spartan-gallery' ), 'img_margin', array( 'grid', 'masonry', 'justified', 'mosaic', 'polaroid', 'carousel', 'slideshow', 'compare' ) );
		self::number( 'img_margin', $v['img_margin'], 'px' );
		self::row_close();

		self::row_open( __( 'Slide size', 'spartan-gallery' ), 'image_width', array( 'slider' ) );
		echo '<span class="sg-inline-fields">';
		echo '<label>' . esc_html__( 'Width', 'spartan-gallery' ) . ' ';
		self::number( 'image_width', $v['image_width'] );
		echo '</label> <label>' . esc_html__( 'Height', 'spartan-gallery' ) . ' ';
		self::number( 'image_height', $v['image_height'] );
		echo '</label> ';
		self::select( 'image_measure_value', $v['image_measure_value'], array( 'px' => 'px', '%' => '%', 'vh' => 'vw / vh', 'rem' => 'rem' ) );
		echo '</span>';
		self::row_close();

		self::row_open( __( 'Autoplay', 'spartan-gallery' ), 'autoplay', array( 'slider', 'carousel', 'slideshow' ) );
		self::toggle( 'autoplay', $v['autoplay'] );
		self::row_close( __( 'Pauses while the visitor hovers over the gallery.', 'spartan-gallery' ) );

		self::row_open( __( 'Slide delay', 'spartan-gallery' ), 'autoplay_delay' );
		self::number( 'autoplay_delay', $v['autoplay_delay'], __( 'seconds', 'spartan-gallery' ) );
		self::row_close( __( 'Used by autoplay and by the lightbox slideshow button.', 'spartan-gallery' ) );

		self::section_close();
	}

	private static function render_upload_panel( $id ) {
		self::section_open( 'upload', __( 'Images', 'spartan-gallery' ), __( 'Drag thumbnails to change the order. Title, caption and Gallery Tags are edited in the Media Library: click "Add / Manage Images", then click an image.', 'spartan-gallery' ) );
		$uploaded_ids = implode( ',', Spartan_Gallery_Settings::parse_image_ids( get_post_meta( $id, 'spartan_gallery_uploaded_images', true ) ) );

		echo '<tr><td colspan="2">';
		echo '<input type="hidden" id="spartan_gallery_uploaded_images" name="spartan_gallery_uploaded_images" value="' . esc_attr( $uploaded_ids ) . '" />';
		echo '<div id="sg-gallery-preview" class="sg-gallery-preview">';
		foreach ( Spartan_Gallery_Settings::parse_image_ids( $uploaded_ids ) as $att_id ) {
			$thumb = wp_get_attachment_image_src( $att_id, 'thumbnail' );
			if ( $thumb ) {
				echo '<span class="sg-gallery-thumb" data-id="' . esc_attr( $att_id ) . '"><img src="' . esc_url( $thumb[0] ) . '" alt="" /><a href="#" class="sg-gallery-remove" title="' . esc_attr__( 'Remove', 'spartan-gallery' ) . '">&times;</a></span>';
			}
		}
		echo '</div>';
		echo '<p><button type="button" class="button button-primary" id="sg-gallery-select">' . esc_html__( 'Add / Manage Images', 'spartan-gallery' ) . '</button></p>';
		echo '</td></tr>';
		self::section_close();
	}

	private static function render_style_panel( $v ) {
		self::section_open( 'style', __( 'Image Style', 'spartan-gallery' ) );

		self::row_open( __( 'Image quality', 'spartan-gallery' ), 'image_quality' );
		self::select(
			'image_quality',
			$v['image_quality'],
			array(
				'fast'     => __( 'Fast – smaller files, quickest loading', 'spartan-gallery' ),
				'balanced' => __( 'Balanced – sharp on most screens (recommended)', 'spartan-gallery' ),
				'full'     => __( 'Full size – original uploads, largest files', 'spartan-gallery' ),
			)
		);
		self::row_close( __( 'Each visitor still gets a size that fits their screen. The lightbox always shows the original.', 'spartan-gallery' ) );

		self::row_open( __( 'Hover effect', 'spartan-gallery' ), 'hover_effect', Spartan_Gallery_Renderer::HOVER_LAYOUTS );
		self::select(
			'hover_effect',
			$v['hover_effect'],
			array(
				'zoom'   => __( 'Zoom in', 'spartan-gallery' ),
				'fade'   => __( 'Fade', 'spartan-gallery' ),
				'darken' => __( 'Darken', 'spartan-gallery' ),
				'title'  => __( 'Darken + show title', 'spartan-gallery' ),
				'none'   => __( 'None', 'spartan-gallery' ),
			)
		);
		self::row_close();

		self::row_open( __( 'Rounded corners', 'spartan-gallery' ), 'image_radius' );
		self::number( 'image_radius', $v['image_radius'], 'px' );
		self::row_close();

		self::section_close();
	}

	private static function render_browse_panel( $v ) {
		$layouts = Spartan_Gallery_Renderer::COLLECTION_LAYOUTS;

		self::section_open( 'browse', __( 'Filter & Loading', 'spartan-gallery' ), __( 'For Grid, Masonry, Justified, Mosaic and Polaroid layouts.', 'spartan-gallery' ) );

		self::row_open( __( 'Filter buttons', 'spartan-gallery' ), 'filter_enabled', $layouts );
		self::toggle( 'filter_enabled', $v['filter_enabled'], __( 'Show tag buttons above the gallery', 'spartan-gallery' ) );
		self::row_close( __( 'Give images "Gallery Tags" in the Media Library (e.g. Weddings, Portraits). One button appears per tag used in this gallery, plus "All".', 'spartan-gallery' ) );

		self::row_open( __( 'Loading', 'spartan-gallery' ), 'pagination', $layouts );
		self::select(
			'pagination',
			$v['pagination'],
			array(
				'none'   => __( 'Show all images at once', 'spartan-gallery' ),
				'button' => __( '"Load more" button', 'spartan-gallery' ),
				'scroll' => __( 'Load more automatically while scrolling', 'spartan-gallery' ),
			)
		);
		self::row_close( __( 'Big galleries open faster when only the first images are shown.', 'spartan-gallery' ) );

		self::row_open( __( 'Images per batch', 'spartan-gallery' ), 'per_page', $layouts );
		self::number( 'per_page', $v['per_page'] );
		self::row_close();

		echo '<tr data-sg-layouts="hexagon slider carousel slideshow compare"><td colspan="2"><p class="description">' . esc_html__( 'Filtering and "load more" are not available for the selected layout.', 'spartan-gallery' ) . '</p></td></tr>';

		self::section_close();
	}

	private static function render_lightbox_panel( $v ) {
		self::section_open( 'lightbox', __( 'Lightbox', 'spartan-gallery' ), __( 'Full-screen viewer with swipe, keyboard and zoom. Not used by the Before / After layout.', 'spartan-gallery' ) );

		self::row_open( __( 'Open images in a lightbox', 'spartan-gallery' ), 'lightbox_enabled' );
		self::toggle( 'lightbox_enabled', $v['lightbox_enabled'] );
		self::row_close();

		self::row_open( __( 'Show title and caption', 'spartan-gallery' ), 'lightbox_captions' );
		self::toggle( 'lightbox_captions', $v['lightbox_captions'] );
		self::row_close();

		self::row_open( __( 'Thumbnail strip', 'spartan-gallery' ), 'lightbox_thumbnails' );
		self::toggle( 'lightbox_thumbnails', $v['lightbox_thumbnails'] );
		self::row_close();

		self::row_open( __( 'Download button', 'spartan-gallery' ), 'lightbox_download' );
		self::toggle( 'lightbox_download', $v['lightbox_download'] );
		self::row_close();

		self::row_open( __( 'Slideshow button', 'spartan-gallery' ), 'lightbox_slideshow' );
		self::toggle( 'lightbox_slideshow', $v['lightbox_slideshow'] );
		self::row_close( __( 'Advances automatically using the "Slide delay" from the Layout tab.', 'spartan-gallery' ) );

		self::section_close();
	}

	private static function render_container_panel( $v ) {
		self::section_open( 'container', __( 'Container', 'spartan-gallery' ), __( 'Everything here is optional; leave fields empty for a full-width gallery without a frame.', 'spartan-gallery' ) );

		self::row_open( __( 'Width', 'spartan-gallery' ), 'container_width' );
		echo '<input type="text" id="container_width" name="container_width" value="' . esc_attr( $v['container_width'] ) . '" placeholder="100%" size="8" />';
		self::row_close( __( 'e.g. 800px or 80%. Empty = full width.', 'spartan-gallery' ) );

		self::row_open( __( 'Height', 'spartan-gallery' ), 'container_height' );
		echo '<input type="text" id="container_height" name="container_height" value="' . esc_attr( $v['container_height'] ) . '" placeholder="auto" size="8" />';
		self::row_close( __( 'Empty = as tall as the images need. A fixed height adds a scrollbar.', 'spartan-gallery' ) );

		self::row_open( __( 'Background', 'spartan-gallery' ), 'container_background' );
		self::color( 'container_background', $v['container_background'], '' );
		self::row_close();

		self::row_open( __( 'Inner spacing', 'spartan-gallery' ), 'container_padding' );
		self::number( 'container_padding', $v['container_padding'], 'px' );
		self::row_close();

		self::row_open( __( 'Border', 'spartan-gallery' ), 'border_width' );
		echo '<span class="sg-inline-fields">';
		self::number( 'border_width', $v['border_width'], 'px' );
		echo ' ';
		self::select(
			'border_style',
			$v['border_style'],
			array(
				'solid'  => __( 'Solid', 'spartan-gallery' ),
				'dashed' => __( 'Dashed', 'spartan-gallery' ),
				'dotted' => __( 'Dotted', 'spartan-gallery' ),
				'double' => __( 'Double', 'spartan-gallery' ),
			)
		);
		echo ' ';
		self::color( 'border_color', $v['border_color'], '#dddddd' );
		echo '</span>';
		self::row_close( __( '0 = no border.', 'spartan-gallery' ) );

		self::row_open( __( 'Rounded corners', 'spartan-gallery' ), 'border_radius' );
		self::number( 'border_radius', $v['border_radius'], 'px' );
		self::row_close();

		self::section_close();
	}

	public static function render_preview_box( $post ) {
		echo '<div class="sg-preview-toolbar">';
		echo '<span class="sg-preview-devices" role="group" aria-label="' . esc_attr__( 'Preview width', 'spartan-gallery' ) . '">';
		foreach ( array( 'desktop' => array( 'dashicons-desktop', __( 'Desktop', 'spartan-gallery' ) ), 'tablet' => array( 'dashicons-tablet', __( 'Tablet', 'spartan-gallery' ) ), 'mobile' => array( 'dashicons-smartphone', __( 'Phone', 'spartan-gallery' ) ) ) as $device => $d ) {
			echo '<button type="button" class="button sg-preview-device' . ( 'desktop' === $device ? ' active' : '' ) . '" data-device="' . esc_attr( $device ) . '" aria-pressed="' . ( 'desktop' === $device ? 'true' : 'false' ) . '" title="' . esc_attr( $d[1] ) . '"><span class="dashicons ' . esc_attr( $d[0] ) . '"></span><span class="screen-reader-text">' . esc_html( $d[1] ) . '</span></button>';
		}
		echo '</span>';
		echo '<button type="button" class="button" id="sg-preview-refresh"><span class="dashicons dashicons-update"></span> ' . esc_html__( 'Refresh', 'spartan-gallery' ) . '</button>';
		echo '<span class="sg-preview-status" aria-live="polite"></span>';
		echo '</div>';
		echo '<div class="sg-preview-stage"><iframe id="sg-preview-frame" title="' . esc_attr__( 'Gallery preview', 'spartan-gallery' ) . '"></iframe></div>';
		echo '<p class="description">' . esc_html__( 'Updates as you change settings. Nothing is saved until you click Update.', 'spartan-gallery' ) . '</p>';
	}

	/* ------------------------------------------------------------------ */
	/* Save + preview                                                      */
	/* ------------------------------------------------------------------ */

	public static function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( get_post_type( $post_id ) !== self::POST_TYPE || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$request = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field is sanitized by Spartan_Gallery_Settings.

		Spartan_Gallery_Settings::save( $post_id, Spartan_Gallery_Settings::from_request( $request ) );

		if ( isset( $request['spartan_gallery_uploaded_images'] ) ) {
			update_post_meta( $post_id, 'spartan_gallery_uploaded_images', implode( ',', Spartan_Gallery_Settings::parse_image_ids( $request['spartan_gallery_uploaded_images'] ) ) );
		}
	}

	/**
	 * Render the gallery from the unsaved form values for the preview iframe.
	 */
	public static function ajax_preview() {
		check_ajax_referer( self::PREVIEW_ACTION, 'sg_preview_nonce' );

		$post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
		if ( ! $post_id || self::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}

		$request     = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- each field is sanitized by Spartan_Gallery_Settings.
		$settings    = Spartan_Gallery_Settings::from_request( $request );
		$ids         = Spartan_Gallery_Settings::parse_image_ids( isset( $request['spartan_gallery_uploaded_images'] ) ? $request['spartan_gallery_uploaded_images'] : '' );
		$attachments = Spartan_Gallery_Renderer::get_attachments( $ids );

		if ( empty( $attachments ) ) {
			$markup = '<p style="color:#646970;text-align:center;padding:40px 0;">' . esc_html__( 'Add images in the "Images" tab to see a preview.', 'spartan-gallery' ) . '</p>';
		} else {
			$markup = Spartan_Gallery_Renderer::render_markup( $post_id, $settings, $attachments );
		}

		wp_send_json_success( array( 'html' => Spartan_Gallery_Renderer::preview_document( $markup ) ) );
	}
}

Spartan_Gallery_Options::init();
