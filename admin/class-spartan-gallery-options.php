<?php
/**
 * Spartan Gallery Options metabox.
 *
 * Native WordPress replacement for the previous Redux Framework options panel.
 * Renders the same sections/fields and writes to the exact same post_meta keys
 * the rest of the plugin (public/html-cointainer-outputs/*, spartan-gallery-styles.php)
 * already reads, so no other file needed to change.
 *
 * @package Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Options {

	const POST_TYPE = 'spartan_gallery_type';
	const NONCE_ACTION = 'spartan_gallery_options_save';
	const NONCE_NAME   = 'spartan_gallery_options_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function add_meta_box() {
		add_meta_box(
			'spartan-gallery-options',
			esc_html__( 'Spartan Gallery Options', 'spartan-gallery' ),
			array( __CLASS__, 'render' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
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
		wp_enqueue_style(
			'spartan-gallery-options',
			plugin_dir_url( __FILE__ ) . 'css/spartan-gallery-options.css',
			array( 'wp-color-picker' ),
			PLUGIN_NAME_VERSION
		);

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_script( 'jquery-ui-spinner' );
		wp_enqueue_script( 'wp-color-picker' );

		$cm_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

		wp_enqueue_script(
			'spartan-gallery-options',
			plugin_dir_url( __FILE__ ) . 'js/spartan-gallery-options.js',
			array( 'jquery', 'jquery-ui-slider', 'jquery-ui-spinner', 'wp-color-picker', 'media-editor' ),
			PLUGIN_NAME_VERSION,
			true
		);

		wp_localize_script(
			'spartan-gallery-options',
			'sgOptionsData',
			array(
				'codeEditor' => $cm_settings,
				'mediaTitle' => esc_html__( 'Select gallery images', 'spartan-gallery' ),
				'mediaButton' => esc_html__( 'Use selected images', 'spartan-gallery' ),
			)
		);
	}

	private static function meta( $post_id, $key, $default = '' ) {
		// metadata_exists(), not an emptiness check: update_post_meta() legitimately
		// stores '' when a checkbox is unchecked, and that must NOT be treated the
		// same as "never saved, show the default" or unchecked switches could never
		// actually be turned off.
		if ( ! metadata_exists( 'post', $post_id, $key ) ) {
			return $default;
		}
		return get_post_meta( $post_id, $key, true );
	}

	private static function tabs() {
		return array(
			'layout'    => array( esc_html__( 'Gallery Layout Options', 'spartan-gallery' ), 'dashicons-layout' ),
			'upload'    => array( esc_html__( 'Upload Images', 'spartan-gallery' ), 'dashicons-format-gallery' ),
			'image'     => array( esc_html__( 'Image Options', 'spartan-gallery' ), 'dashicons-admin-generic' ),
			'lightbox'  => array( esc_html__( 'Lightbox', 'spartan-gallery' ), 'dashicons-visibility' ),
			'container' => array( esc_html__( 'Gallery Container Settings', 'spartan-gallery' ), 'dashicons-admin-appearance' ),
			'css'       => array( esc_html__( 'Insert Custom CSS', 'spartan-gallery' ), 'dashicons-editor-code' ),
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
		$active = ( 'layout' === $id );
		echo '<div class="sg-options-panel' . ( $active ? ' active' : '' ) . '" data-panel="' . esc_attr( $id ) . '">';
		echo '<h3 class="sg-options-section-title">' . esc_html( $title ) . '</h3>';
		if ( $desc ) {
			echo '<p class="sg-options-section-desc">' . wp_kses_post( $desc ) . '</p>';
		}
		echo '<table class="form-table sg-options-table"><tbody>';
	}

	private static function section_close() {
		echo '</tbody></table></div>';
	}

	/**
	 * @param string   $label
	 * @param string   $for     Input id for the <label>.
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
			echo '<p class="description">' . wp_kses_post( $desc ) . '</p>';
		}
		echo '</td></tr>';
	}

	public static function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$gallery_id = $post->ID;

		echo '<div class="sg-options-wrap">';
		self::render_nav( 'layout' );
		echo '<div class="sg-options-content">';

		// Section 1: Gallery Layout Options.
		self::section_open( 'layout', esc_html__( 'Gallery Layout Options', 'spartan-gallery' ) );
		$layout  = Spartan_Gallery_Renderer::normalize_layout( self::meta( $gallery_id, 'image_layout_select', Spartan_Gallery_Renderer::DEFAULT_LAYOUT ) );
		$layouts = array(
			'grid'      => array( esc_html__( 'Grid', 'spartan-gallery' ), 'layout-grid.svg' ),
			'masonry'   => array( esc_html__( 'Masonry', 'spartan-gallery' ), 'layout-masonry.svg' ),
			'justified' => array( esc_html__( 'Justified', 'spartan-gallery' ), 'layout-justified.svg' ),
			'hexagon'   => array( esc_html__( 'Hexagon', 'spartan-gallery' ), 'layout-hexagon.svg' ),
			'slider'    => array( esc_html__( '3D Slider', 'spartan-gallery' ), 'layout-slider.svg' ),
		);
		echo '<tr><td colspan="2">';
		echo '<div class="sg-image-select">';
		foreach ( $layouts as $value => $data ) {
			list( $label, $file ) = $data;
			$img_url = plugins_url( 'img/' . $file, __FILE__ );
			$checked = checked( $layout, $value, false );
			echo '<label class="sg-image-select-option' . ( $layout === $value ? ' selected' : '' ) . '">';
			echo '<input type="radio" name="image_layout_select" value="' . esc_attr( $value ) . '" ' . $checked . ' />';
			echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $label ) . '" />';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '</label>';
		}
		echo '</div>';
		echo '</td></tr>';

		self::row_open( esc_html__( 'Columns', 'spartan-gallery' ), 'gallery_columns', array( 'grid', 'masonry' ) );
		$columns = absint( self::meta( $gallery_id, 'gallery_columns', 3 ) );
		echo '<select id="gallery_columns" name="gallery_columns">';
		for ( $i = 1; $i <= 6; $i++ ) {
			echo '<option value="' . esc_attr( $i ) . '" ' . selected( $columns, $i, false ) . '>' . esc_html( $i ) . '</option>';
		}
		echo '</select>';
		self::row_close( esc_html__( 'Automatically reduced to 3 columns on tablets and 2 on phones.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Image aspect ratio', 'spartan-gallery' ), 'grid_aspect_ratio', array( 'grid' ) );
		$ratio  = self::meta( $gallery_id, 'grid_aspect_ratio', '1-1' );
		$ratios = array(
			'1-1'      => esc_html__( 'Square (1:1)', 'spartan-gallery' ),
			'4-3'      => esc_html__( 'Landscape (4:3)', 'spartan-gallery' ),
			'3-2'      => esc_html__( 'Landscape (3:2)', 'spartan-gallery' ),
			'16-9'     => esc_html__( 'Widescreen (16:9)', 'spartan-gallery' ),
			'3-4'      => esc_html__( 'Portrait (3:4)', 'spartan-gallery' ),
			'original' => esc_html__( 'Original (no cropping)', 'spartan-gallery' ),
		);
		echo '<select id="grid_aspect_ratio" name="grid_aspect_ratio">';
		foreach ( $ratios as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $ratio, $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		self::row_close( esc_html__( 'Images are cropped from the center to fit.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Row height (PX)', 'spartan-gallery' ), 'justified_row_height', array( 'justified' ) );
		$row_height = self::meta( $gallery_id, 'justified_row_height', 240 );
		echo '<input type="text" class="sg-spinner" id="justified_row_height" name="justified_row_height" value="' . esc_attr( $row_height ) . '" data-min="80" data-max="600" data-step="10" />';
		self::row_close( esc_html__( 'Target height of each row. Rows stretch slightly so they always fill the full width.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Gap between images (PX)', 'spartan-gallery' ), 'img_margin', array( 'grid', 'masonry', 'justified' ) );
		$img_margin = self::meta( $gallery_id, 'img_margin', 20 );
		echo '<input type="text" class="sg-spinner" id="img_margin" name="img_margin" value="' . esc_attr( $img_margin ) . '" data-min="0" data-max="100" data-step="1" />';
		self::row_close();

		self::section_close();

		// Section 2: Upload Images.
		self::section_open( 'upload', esc_html__( 'Upload Images', 'spartan-gallery' ) );
		$uploaded_ids = self::meta( $gallery_id, 'spartan_gallery_uploaded_images', '' );
		self::row_open( esc_html__( 'Upload', 'spartan-gallery' ) );
		echo '<input type="hidden" id="spartan_gallery_uploaded_images" name="spartan_gallery_uploaded_images" value="' . esc_attr( $uploaded_ids ) . '" />';
		echo '<div id="sg-gallery-preview" class="sg-gallery-preview">';
		if ( ! empty( $uploaded_ids ) ) {
			foreach ( explode( ',', $uploaded_ids ) as $att_id ) {
				$att_id = absint( $att_id );
				if ( ! $att_id ) {
					continue;
				}
				$thumb = wp_get_attachment_image_src( $att_id, 'thumbnail' );
				if ( $thumb ) {
					echo '<span class="sg-gallery-thumb" data-id="' . esc_attr( $att_id ) . '"><img src="' . esc_url( $thumb[0] ) . '" /><a href="#" class="sg-gallery-remove" title="' . esc_attr__( 'Remove', 'spartan-gallery' ) . '">&times;</a></span>';
				}
			}
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="sg-gallery-select">' . esc_html__( 'Select / Manage Images', 'spartan-gallery' ) . '</button></p>';
		self::row_close();
		self::section_close();

		// Section 3: Image Options.
		self::section_open( 'image', esc_html__( 'Image Options', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Display Adjustable Image', 'spartan-gallery' ), 'display_original' );
		$display_original = self::meta( $gallery_id, 'display_original', '' );
		echo '<label class="sg-switch"><input type="checkbox" id="display_original" name="display_original" value="1" ' . checked( $display_original, '1', false ) . ' /> ' . esc_html__( 'Enabled', 'spartan-gallery' ) . '</label>';
		self::row_close( esc_html__( 'Use the full-size original upload. When this is on, "Display thumbnail size" is ignored.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Slide width', 'spartan-gallery' ), 'image_width', array( 'slider' ) );
		$image_width = self::meta( $gallery_id, 'image_width', 150 );
		echo '<div class="sg-slider" id="image_width_slider" data-target="image_width" data-min="0" data-max="2000" data-step="1"></div>';
		echo '<input type="text" class="sg-slider-value" id="image_width" name="image_width" value="' . esc_attr( $image_width ) . '" readonly="readonly" />';
		self::row_close();

		self::row_open( esc_html__( 'Slide height', 'spartan-gallery' ), 'image_height', array( 'slider' ) );
		$image_height = self::meta( $gallery_id, 'image_height', 150 );
		echo '<div class="sg-slider" id="image_height_slider" data-target="image_height" data-min="0" data-max="2000" data-step="1"></div>';
		echo '<input type="text" class="sg-slider-value" id="image_height" name="image_height" value="' . esc_attr( $image_height ) . '" readonly="readonly" />';
		self::row_close();

		self::row_open( esc_html__( 'Slide size unit', 'spartan-gallery' ), 'image_measure_value', array( 'slider' ) );
		$measure = self::meta( $gallery_id, 'image_measure_value', 'px' );
		echo '<select id="image_measure_value" name="image_measure_value">';
		foreach ( array( 'px' => 'px', '%' => '%', 'vh' => 'vh/vw', 'rem' => 'rem' ) as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $measure, $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		self::row_close();

		self::row_open( esc_html__( 'Display thumbnail size', 'spartan-gallery' ), 'thumbnail_options' );
		$thumb_opt = self::meta( $gallery_id, 'thumbnail_options', '1' );
		echo '<label class="sg-switch"><input type="checkbox" id="thumbnail_options" name="thumbnail_options" value="1" ' . checked( $thumb_opt, '1', false ) . ' /> ' . esc_html__( 'Enabled', 'spartan-gallery' ) . '</label>';
		self::row_close( esc_html__( 'Use one of the resized copies WordPress generates (faster). Pick the size below.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Select Thumbnail Size', 'spartan-gallery' ), 'thumbnail_size' );
		$thumb_size = self::meta( $gallery_id, 'thumbnail_size', 'medium_large' );
		$thumb_sizes = array(
			'thumbnail'    => esc_html__( 'Thumbnail (default 150px x 150px max)', 'spartan-gallery' ),
			'medium'       => esc_html__( 'Medium resolution (default 300px x 300px max)', 'spartan-gallery' ),
			'medium_large' => esc_html__( 'Medium Large resolution (default 768px x 0px max)', 'spartan-gallery' ),
			'large'        => esc_html__( 'Large resolution (default 1024px x 1024px max)', 'spartan-gallery' ),
			'original'     => esc_html__( 'Original image resolution (unmodified)', 'spartan-gallery' ),
		);
		echo '<select id="thumbnail_size" name="thumbnail_size">';
		foreach ( $thumb_sizes as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $thumb_size, $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		self::row_close();


		self::section_close();

		// Section 4: Lightbox.
		self::section_open( 'lightbox', esc_html__( 'Lightbox', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Open images in a lightbox', 'spartan-gallery' ), 'lightbox_enabled' );
		$lightbox_on = self::meta( $gallery_id, 'lightbox_enabled', '1' );
		echo '<label class="sg-switch"><input type="checkbox" id="lightbox_enabled" name="lightbox_enabled" value="1" ' . checked( $lightbox_on, '1', false ) . ' /> ' . esc_html__( 'Enabled', 'spartan-gallery' ) . '</label>';
		self::row_close( esc_html__( 'Clicking an image opens a full-screen viewer with swipe, keyboard and zoom support.', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Show title and caption', 'spartan-gallery' ), 'lightbox_captions' );
		$captions_on = self::meta( $gallery_id, 'lightbox_captions', '1' );
		echo '<label class="sg-switch"><input type="checkbox" id="lightbox_captions" name="lightbox_captions" value="1" ' . checked( $captions_on, '1', false ) . ' /> ' . esc_html__( 'Enabled', 'spartan-gallery' ) . '</label>';
		self::row_close( esc_html__( 'Uses each image\'s Title and Caption from the Media Library.', 'spartan-gallery' ) );

		self::section_close();

		// Section 5: Gallery Container Settings.
		self::section_open( 'container', esc_html__( 'Gallery Container Settings', 'spartan-gallery' ) );

		self::row_open( esc_html__( 'Turn settings On/Off', 'spartan-gallery' ), 'container_settings_on_off' );
		$container_on = self::meta( $gallery_id, 'container_settings_on_off', '' );
		echo '<label class="sg-switch"><input type="checkbox" id="container_settings_on_off" name="container_settings_on_off" value="1" ' . checked( $container_on, '1', false ) . ' /> ' . esc_html__( 'Enabled', 'spartan-gallery' ) . '</label>';
		self::row_close();

		self::row_open( esc_html__( 'Container dimensions (Width/Height)', 'spartan-gallery' ) );
		$dims = self::meta( $gallery_id, 'container_height_width_range', array() );
		$dims = wp_parse_args(
			is_array( $dims ) ? $dims : array(),
			array( 'width' => '200px', 'height' => '' )
		);
		list( $w_num, $w_unit ) = self::split_length( $dims['width'], '200', 'px' );
		list( $h_num, $h_unit ) = self::split_length( $dims['height'], '', 'px' );
		echo '<div class="sg-dimensions">';
		echo '<span><label>' . esc_html__( 'Width', 'spartan-gallery' ) . '</label> <input type="text" name="container_width_num" value="' . esc_attr( $w_num ) . '" size="6" /> ';
		echo self::unit_select( 'container_width_unit', $w_unit );
		echo '</span> <span><label>' . esc_html__( 'Height', 'spartan-gallery' ) . '</label> <input type="text" name="container_height_num" value="' . esc_attr( $h_num ) . '" size="6" /> ';
		echo self::unit_select( 'container_height_unit', $h_unit );
		echo '</span>';
		echo '</div>';
		self::row_close();

		self::row_open( esc_html__( 'Background Color', 'spartan-gallery' ), 'container_background_color' );
		$container_bg = self::meta( $gallery_id, 'container_background_color', '#FFFFFF' );
		echo '<input type="text" class="sg-color-field" id="container_background_color" name="container_background_color" value="' . esc_attr( $container_bg ) . '" data-default-color="#FFFFFF" />';
		self::row_close();

		self::row_open( esc_html__( 'Border Option', 'spartan-gallery' ) );
		$border = self::meta( $gallery_id, 'container_border', array() );
		$border = wp_parse_args(
			is_array( $border ) ? $border : array(),
			array(
				'border-color'  => '#000000',
				'border-style'  => 'none',
				'border-top'    => '',
				'border-right'  => '',
				'border-bottom' => '',
				'border-left'   => '',
			)
		);
		echo '<div class="sg-border">';
		echo '<span><label>' . esc_html__( 'Style', 'spartan-gallery' ) . '</label> <select name="container_border[border-style]">';
		foreach ( array( 'none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' ) as $style ) {
			echo '<option value="' . esc_attr( $style ) . '" ' . selected( $border['border-style'], $style, false ) . '>' . esc_html( $style ) . '</option>';
		}
		echo '</select></span> ';
		echo '<span><label>' . esc_html__( 'Color', 'spartan-gallery' ) . '</label> <input type="text" class="sg-color-field" name="container_border[border-color]" value="' . esc_attr( $border['border-color'] ) . '" data-default-color="#000000" /></span>';
		echo '<div class="sg-border-widths">';
		$border_sides = array(
			'border-top'    => __( 'Top', 'spartan-gallery' ),
			'border-right'  => __( 'Right', 'spartan-gallery' ),
			'border-bottom' => __( 'Bottom', 'spartan-gallery' ),
			'border-left'   => __( 'Left', 'spartan-gallery' ),
		);
		foreach ( $border_sides as $side => $side_label ) {
			echo '<span><label>' . esc_html( $side_label ) . '</label> <input type="text" name="container_border[' . esc_attr( $side ) . ']" value="' . esc_attr( $border[ $side ] ) . '" size="6" placeholder="e.g. 1px" /></span>';
		}
		echo '</div>';
		echo '</div>';
		self::row_close( esc_html__( 'Only applies when "Turn settings On/Off" above is enabled.', 'spartan-gallery' ) );

		self::section_close();

		// Section 6: Insert Custom CSS.
		self::section_open( 'css', esc_html__( 'Insert Custom CSS', 'spartan-gallery' ) );
		self::row_open( esc_html__( 'CSS Code', 'spartan-gallery' ), 'add_extra_css' );
		$extra_css = self::meta( $gallery_id, 'add_extra_css', '' );
		echo '<textarea id="add_extra_css" name="add_extra_css" rows="10" class="large-text code">' . esc_textarea( $extra_css ) . '</textarea>';
		self::row_close( esc_html__( 'Paste your CSS code here. The gallery container HTML class is .spartan-gallery-wrapper', 'spartan-gallery' ) );
		self::section_close();

		echo '</div>'; // .sg-options-content
		echo '</div>'; // .sg-options-wrap
	}

	private static function split_length( $value, $default_num, $default_unit ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array( $default_num, $default_unit );
		}
		if ( preg_match( '/^(-?[0-9.]+)(px|em|rem|%|vh|vw)?$/', $value, $m ) ) {
			return array( $m[1], isset( $m[2] ) ? $m[2] : $default_unit );
		}
		return array( $default_num, $default_unit );
	}

	private static function unit_select( $name, $selected_unit ) {
		$out = '<select name="' . esc_attr( $name ) . '">';
		foreach ( array( 'px', 'em', 'rem', '%' ) as $unit ) {
			$out .= '<option value="' . esc_attr( $unit ) . '" ' . selected( $selected_unit, $unit, false ) . '>' . esc_html( $unit ) . '</option>';
		}
		$out .= '</select>';
		return $out;
	}

	public static function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$layout = isset( $_POST['image_layout_select'] ) ? sanitize_text_field( wp_unslash( $_POST['image_layout_select'] ) ) : '';
		update_post_meta( $post_id, 'image_layout_select', Spartan_Gallery_Renderer::normalize_layout( $layout ) );

		$columns = isset( $_POST['gallery_columns'] ) ? absint( $_POST['gallery_columns'] ) : 3;
		update_post_meta( $post_id, 'gallery_columns', ( $columns >= 1 && $columns <= 6 ) ? $columns : 3 );

		$ratio = isset( $_POST['grid_aspect_ratio'] ) ? sanitize_text_field( wp_unslash( $_POST['grid_aspect_ratio'] ) ) : '1-1';
		update_post_meta( $post_id, 'grid_aspect_ratio', in_array( $ratio, Spartan_Gallery_Renderer::aspect_ratio_keys(), true ) ? $ratio : '1-1' );

		$row_height = isset( $_POST['justified_row_height'] ) ? absint( $_POST['justified_row_height'] ) : 240;
		update_post_meta( $post_id, 'justified_row_height', max( 80, min( 600, $row_height ) ) );

		update_post_meta( $post_id, 'lightbox_enabled', isset( $_POST['lightbox_enabled'] ) ? '1' : '' );
		update_post_meta( $post_id, 'lightbox_captions', isset( $_POST['lightbox_captions'] ) ? '1' : '' );

		if ( isset( $_POST['spartan_gallery_uploaded_images'] ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', wp_unslash( $_POST['spartan_gallery_uploaded_images'] ) ) ) );
			update_post_meta( $post_id, 'spartan_gallery_uploaded_images', implode( ',', $ids ) );
		}

		update_post_meta( $post_id, 'display_original', isset( $_POST['display_original'] ) ? '1' : '' );
		update_post_meta( $post_id, 'thumbnail_options', isset( $_POST['thumbnail_options'] ) ? '1' : '' );
		update_post_meta( $post_id, 'container_settings_on_off', isset( $_POST['container_settings_on_off'] ) ? '1' : '' );

		update_post_meta( $post_id, 'image_width', isset( $_POST['image_width'] ) ? min( 2000, absint( $_POST['image_width'] ) ) : 150 );
		update_post_meta( $post_id, 'image_height', isset( $_POST['image_height'] ) ? min( 2000, absint( $_POST['image_height'] ) ) : 150 );
		update_post_meta( $post_id, 'img_margin', isset( $_POST['img_margin'] ) ? min( 100, absint( $_POST['img_margin'] ) ) : 20 );

		$measure = isset( $_POST['image_measure_value'] ) ? sanitize_text_field( wp_unslash( $_POST['image_measure_value'] ) ) : 'px';
		update_post_meta( $post_id, 'image_measure_value', in_array( $measure, array( 'px', '%', 'vh', 'rem' ), true ) ? $measure : 'px' );

		$thumb_size = isset( $_POST['thumbnail_size'] ) ? sanitize_text_field( wp_unslash( $_POST['thumbnail_size'] ) ) : 'medium_large';
		update_post_meta( $post_id, 'thumbnail_size', in_array( $thumb_size, array( 'thumbnail', 'medium', 'medium_large', 'large', 'original' ), true ) ? $thumb_size : 'medium_large' );

		$container_bg = isset( $_POST['container_background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['container_background_color'] ) ) : '';
		update_post_meta( $post_id, 'container_background_color', $container_bg ? $container_bg : '#FFFFFF' );

		$w_num  = isset( $_POST['container_width_num'] ) ? sanitize_text_field( wp_unslash( $_POST['container_width_num'] ) ) : '';
		$w_unit = isset( $_POST['container_width_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['container_width_unit'] ) ) : 'px';
		$h_num  = isset( $_POST['container_height_num'] ) ? sanitize_text_field( wp_unslash( $_POST['container_height_num'] ) ) : '';
		$h_unit = isset( $_POST['container_height_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['container_height_unit'] ) ) : 'px';
		update_post_meta(
			$post_id,
			'container_height_width_range',
			array(
				'width'  => ( '' !== $w_num && is_numeric( $w_num ) ) ? $w_num . $w_unit : '',
				'height' => ( '' !== $h_num && is_numeric( $h_num ) ) ? $h_num . $h_unit : '',
			)
		);

		if ( isset( $_POST['container_border'] ) && is_array( $_POST['container_border'] ) ) {
			$raw = wp_unslash( $_POST['container_border'] );
			$style_allowed = array( 'none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' );
			$border_color  = isset( $raw['border-color'] ) ? sanitize_hex_color( $raw['border-color'] ) : '';
			update_post_meta(
				$post_id,
				'container_border',
				array(
					'border-color'  => $border_color ? $border_color : '#000000',
					'border-style'  => isset( $raw['border-style'] ) && in_array( $raw['border-style'], $style_allowed, true ) ? $raw['border-style'] : 'none',
					'border-top'    => isset( $raw['border-top'] ) ? sanitize_text_field( $raw['border-top'] ) : '',
					'border-right'  => isset( $raw['border-right'] ) ? sanitize_text_field( $raw['border-right'] ) : '',
					'border-bottom' => isset( $raw['border-bottom'] ) ? sanitize_text_field( $raw['border-bottom'] ) : '',
					'border-left'   => isset( $raw['border-left'] ) ? sanitize_text_field( $raw['border-left'] ) : '',
				)
			);
		}

		if ( isset( $_POST['add_extra_css'] ) ) {
			update_post_meta( $post_id, 'add_extra_css', wp_strip_all_tags( wp_unslash( $_POST['add_extra_css'] ) ) );
		}
	}
}

Spartan_Gallery_Options::init();
