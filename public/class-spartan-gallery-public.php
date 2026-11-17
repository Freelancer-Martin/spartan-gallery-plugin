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
			if ( $post && ( has_shortcode( $post->post_content, 'spartan_gallery' ) || has_block( 'custom/gallery-block', $post ) ) ) {
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

	public function register_shortcodes() {
		add_shortcode( 'spartan_gallery', array( $this, 'spartan_gallery_shortcode' ) );
	}
}
