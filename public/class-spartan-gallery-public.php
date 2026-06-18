<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       developerforwebsites@gmail.com
 * @since      1.0.0
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
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
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		//add_action('init', [$this, 'register_block']);

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Spartan_Gallery_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Spartan_Gallery_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	  	wp_enqueue_style( $this->plugin_name . 'public', plugin_dir_url( __FILE__ ) . 'css/spartan-gallery-public.css', array(), $this->version, 'all' );

		wp_enqueue_style( $this->plugin_name . 'freewall-main', plugin_dir_url( __FILE__ ) .  'css/freewall-main.css', array(), $this->version, 'all' );

		wp_enqueue_style( $this->plugin_name . 'hexagon', plugin_dir_url( __FILE__ ) .  'css/hexagon-gallery-style.css', array(), $this->version, 'all' );

		wp_enqueue_style( $this->plugin_name . 'gallery-3d-slider', plugin_dir_url( __FILE__ ) .  'css/gallery-3d-slider.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Spartan_Gallery_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Spartan_Gallery_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name . 'smartphoto', plugin_dir_url( __FILE__ ) . 'js/jquery-smartphoto.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/spartan-gallery-public.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name . 'freewall-min', plugin_dir_url( __FILE__ ) . 'js/freewall.min.js' ,array( 'jquery' ), $this->version, true );

		wp_enqueue_script( $this->plugin_name . 'jquery-responsiveGallery', plugin_dir_url( __FILE__ ) . 'js/jquery.responsiveGallery.js' ,array( 'jquery' ), $this->version, true );

		wp_enqueue_script( $this->plugin_name . 'modernizr.custom', plugin_dir_url( __FILE__ ) . 'js/modernizr.custom.js' ,array( 'jquery' ), $this->version, true );

		wp_enqueue_script( $this->plugin_name . 'flipster-min', plugin_dir_url( __FILE__ ) . 'js/flispter.min.js' ,array( 'jquery' ), $this->version, true );

	}



	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
  	public function spartan_gallery_post_type( $id )
    {

         $args = array(
           'post_type'   => 'attachment',
           'posts_per_page' => -1,
           'post_parent' => $id,
           'post_status' => 'inherit',
           'meta_query'  => array(
             array(
               'key'     => 'spartan_gallery',
               'compare' => '='
             )
           )

         );

      

       return $args;

  }


  /**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function spartan_gallery_shortcode($atts = [], $content = null, $tag = '')
  	{
		    

			// normalize attribute keys, lowercase
			$atts = array_change_key_case((array)$atts, CASE_LOWER);

			///global $spartan_gallery_options;

			// override default attributes with user attributes
			$wporg_atts = shortcode_atts( [ 'id' => '' ], $atts, $tag );

			// start output
			$shortcode_container = '';

			$shortcode_container .= '<div class="spartan-gallery-box">';

			$spartan_gallery_post_type = $this->spartan_gallery_post_type( $wporg_atts['id'] );

			$my_meta_options = get_post_meta( $spartan_gallery_post_type['post_parent'] , 'image_layout_select', true ); // array

			$get_all_post_attachements = get_post_meta( $spartan_gallery_post_type['post_parent'] , 'spartan_gallery_uploaded_images', true ); 

			

			$post_guids = array();
			if ( ! empty( $get_all_post_attachements ) ) {
				foreach ( explode( ',', $get_all_post_attachements ) as $post_id ) {
					$post = get_post( absint( $post_id ) );
					if ( $post ) {
						$post_guids[] = $post;
					}
				}
			}

			if ( empty( $post_guids ) ) {
				return '<!-- spartan-gallery: no images -->';
			}

			$attachments = $post_guids;

		
			switch ( $my_meta_options ) {
				case 'value-1':
				$shortcode_container .= Freewall_Chaotic_Container::display_chaotic_html_container( $attachments, $spartan_gallery_post_type['post_parent'] ); //galley images post id and gallery id
				break;
				case 'value-2':
				$shortcode_container .= Freewall_Random_Container::display_random_container( $attachments, $spartan_gallery_post_type['post_parent'] );
				break;
				case 'value-3':
				$shortcode_container .= Freewall_Fixed_Img_Container::display_fixed_img_container( $attachments, $spartan_gallery_post_type['post_parent'] );
				break;
				case 'value-4':
				$shortcode_container .= Freewall_Pinterest_Container::display_pinterest_container( $attachments, $spartan_gallery_post_type['post_parent'] );
				break;
				case 'value-5':
				$shortcode_container .= Spartan_Gallery_Hexagon::hexagon_html_container( $attachments, $spartan_gallery_post_type['post_parent'] );
				break;
				case 'value-6':
				$shortcode_container .= ThreeD_Image_Slider::ThreeDImageSlidet_Html_container( $attachments, $spartan_gallery_post_type['post_parent'] );
				break;
			}

			// end box
			$shortcode_container .= '</div>';

			//$CSSClass = new Spartan_Gallery_Generated_CSS_Output();
			//echo $CSSClass->output_php_Css($spartan_gallery_post_type['post_parent']);

			// return output
			return $shortcode_container;

			

    }



	/**
		 * Registers all shortcodes at once
		 *
		 * @return [type] [description]
		 */
	public function register_shortcodes()
    {

		add_shortcode( 'spartan_gallery', array( $this, 'spartan_gallery_shortcode' ) );

	}



}
