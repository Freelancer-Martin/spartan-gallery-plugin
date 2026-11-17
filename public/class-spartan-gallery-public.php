<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/public
 * @author     Freelancer Martin <developerforwebsites@gmail.com>
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery_Public {

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register front-end assets. Nothing is enqueued here unconditionally:
	 * Spartan_Gallery_Renderer::render() enqueues exactly what each gallery
	 * layout needs, so pages without a gallery load no plugin CSS/JS at all.
	 */
	public function enqueue_styles() {
		Spartan_Gallery_Renderer::register_assets();

		// When we can tell up front that the current page contains a gallery,
		// enqueue the base stylesheet now so it lands in <head> instead of
		// being printed late in the footer (avoids a flash of unstyled images).
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ( has_shortcode( $post->post_content, 'spartan_gallery' ) || has_shortcode( $post->post_content, 'spartan_album' ) || has_block( 'custom/gallery-block', $post ) || has_block( 'custom/album-block', $post ) ) ) {
				wp_enqueue_style( 'spartan-gallery' );
			}
		}
	}

	/**
	 * Kept for the loader hook; scripts are enqueued on demand by the renderer.
	 */
	public function enqueue_scripts() {}

	/**
	 * [spartan_gallery id="123"]
	 *
	 * `page_id` is accepted as an alias because older documentation used it.
	 */
	public function spartan_gallery_shortcode( $atts = array(), $content = null, $tag = '' ) {
		$atts = shortcode_atts(
			array(
				'id'      => '',
				'page_id' => '',
			),
			array_change_key_case( (array) $atts, CASE_LOWER ),
			$tag
		);

		$id = $atts['id'] ? $atts['id'] : $atts['page_id'];

		return Spartan_Gallery_Renderer::render( absint( $id ) );
	}

	/**
	 * [spartan_album ids="12,15,18" columns="3" titles="yes"]
	 *
	 * Without ids, every published gallery is listed.
	 */
	public function spartan_album_shortcode( $atts = array(), $content = null, $tag = '' ) {
		$atts = shortcode_atts(
			array(
				'ids'     => '',
				'columns' => 3,
				'titles'  => 'yes',
			),
			array_change_key_case( (array) $atts, CASE_LOWER ),
			$tag
		);

		return Spartan_Gallery_Renderer::render_album(
			Spartan_Gallery_Settings::parse_image_ids( $atts['ids'] ),
			array(
				'columns' => absint( $atts['columns'] ),
				'titles'  => ! in_array( strtolower( (string) $atts['titles'] ), array( 'no', 'false', '0', 'off' ), true ),
			)
		);
	}

	public function register_shortcodes() {
		add_shortcode( 'spartan_gallery', array( $this, 'spartan_gallery_shortcode' ) );
		add_shortcode( 'spartan_album', array( $this, 'spartan_album_shortcode' ) );
	}
}
