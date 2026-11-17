<?php
/**
 * Gutenberg blocks: "Spartan Gallery Block" and "Spartan Album".
 *
 * Both are dynamic (rendered by PHP). In the editor they show a live
 * ServerSideRender preview; enqueue_editor_preview_assets() loads the
 * front-end styles/scripts so the preview looks like the real page.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Gallery_Block {

	const SCRIPT = 'spartan-gallery-block';

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_preview_assets' ) );
	}

	public function register_block() {
		$file = plugin_dir_path( __FILE__ ) . 'js/block.js';
		wp_register_script(
			self::SCRIPT,
			plugin_dir_url( __FILE__ ) . 'js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-server-side-render' ),
			file_exists( $file ) ? (string) filemtime( $file ) : PLUGIN_NAME_VERSION,
			true
		);
		wp_set_script_translations( self::SCRIPT, 'spartan-gallery', plugin_dir_path( dirname( __FILE__ ) ) . 'languages' );

		$layouts = array();
		foreach ( Spartan_Gallery_Options::layouts() as $value => $data ) {
			$layouts[] = array(
				'value' => $value,
				'label' => $data[0],
			);
		}
		wp_localize_script(
			self::SCRIPT,
			'sgBlockData',
			array(
				'layouts' => $layouts,
				'editUrl' => admin_url( 'post.php?action=edit&post=' ),
			)
		);

		register_block_type(
			'custom/gallery-block',
			array(
				'api_version'     => 2,
				'editor_script'   => self::SCRIPT,
				'render_callback' => array( $this, 'render_gallery_block' ),
				'attributes'      => array(
					'galleryId' => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'layout'    => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		register_block_type(
			'custom/album-block',
			array(
				'api_version'     => 2,
				'editor_script'   => self::SCRIPT,
				'render_callback' => array( $this, 'render_album_block' ),
				'attributes'      => array(
					'galleryIds' => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'columns'    => array(
						'type'    => 'integer',
						'default' => 3,
					),
					'showTitles' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Gallery CSS/JS inside the block editor so previews look like the front
	 * end. The script runs in "editor mode": layouts initialize, but there is
	 * no lightbox, deep linking or click-through.
	 */
	public function enqueue_editor_preview_assets() {
		if ( ! is_admin() ) {
			return;
		}
		Spartan_Gallery_Renderer::register_assets();

		foreach ( array( 'spartan-gallery', 'spartan-gallery-hexagon', 'spartan-gallery-swiper-ui' ) as $style ) {
			wp_enqueue_style( $style );
		}
		wp_add_inline_style( 'spartan-gallery', '.sg-gallery a, .sg-gallery .sg-compare-range { pointer-events: none; }' );

		wp_enqueue_script( 'masonry' );
		wp_enqueue_script( 'spartan-gallery-swiper' );
		wp_add_inline_script( 'spartan-gallery', 'window.spartanGalleryEditor = true;', 'before' );
		wp_enqueue_script( 'spartan-gallery' );

		// ServerSideRender swaps markup in after load: initialize new galleries.
		wp_add_inline_script(
			'spartan-gallery',
			'(function(){var t;new MutationObserver(function(){clearTimeout(t);t=setTimeout(function(){if(window.SpartanGallery){window.SpartanGallery.init();}},120);}).observe(document.documentElement,{childList:true,subtree:true});})();'
		);
	}

	public function render_gallery_block( $attributes ) {
		if ( empty( $attributes['galleryId'] ) ) {
			return '';
		}

		$overrides = array();
		if ( ! empty( $attributes['layout'] ) ) {
			$overrides['layout'] = sanitize_key( $attributes['layout'] );
		}

		return Spartan_Gallery_Renderer::render( absint( $attributes['galleryId'] ), $overrides );
	}

	// No galleries chosen = all galleries.
	public function render_album_block( $attributes ) {
		$ids = isset( $attributes['galleryIds'] ) && is_array( $attributes['galleryIds'] ) ? array_filter( array_map( 'absint', $attributes['galleryIds'] ) ) : array();

		return Spartan_Gallery_Renderer::render_album(
			array_values( $ids ),
			array(
				'columns' => isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3,
				'titles'  => ! isset( $attributes['showTitles'] ) || (bool) $attributes['showTitles'],
			)
		);
	}
}

new Gallery_Block();
