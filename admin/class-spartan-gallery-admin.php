<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       developerforwebsites@gmail.com
 * @since      1.0.0
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/admin
 * @author     Freelancer Martin <developerforwebsites@gmail.com>
 */

 if ( ! defined( 'WPINC' ) ) {

     die;

 }


class Spartan_Gallery_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		// Hook into plugin activation
		add_action( 'admin_init', array($this,'spartan_gallery_check_and_install_redux' ));

		add_action( 'plugins_loaded', array($this,'spartan_gallery_load_redux' ));
		

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/spartan-gallery-admin.css', array(), $this->version, 'all' );

		//wp_enqueue_style( $this->plugin_name . 'admin-styles', plugin_dir_url( __FILE__ ) . 'css/popup.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/spartan-gallery-admin.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name . 'popup.js', plugin_dir_url( __FILE__ ) . 'js/popup.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script(   $this->plugin_name . 'gallery-block-js', plugin_dir_url(__FILE__) . 'js/block.js', ['wp-blocks', 'wp-element', 'wp-editor'], filemtime(plugin_dir_path(__FILE__) . 'js/block.js') );

	}


	public function spartan_gallery_check_and_install_redux() 
	{
		// Ensure WordPress is loaded
		if ( ! function_exists( 'wp_generate_password' ) ) {
			error_log( 'WordPress not fully loaded. Aborting Redux installation.' );
			return;
		}
	
		if ( ! class_exists( 'ReduxFramework' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	
			// Get plugin information for Redux Framework
			$plugin_slug = 'redux-framework';
			$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );
	
			if ( is_wp_error( $plugin_info ) ) {
				error_log( 'Error fetching Redux Framework plugin info: ' . $plugin_info->get_error_message() );
				return;
			}
	
			$download_link = $plugin_info->download_link;
	
			$upgrader = new Plugin_Upgrader();
			$installed = $upgrader->install( $download_link );
	
			if ( $installed ) {
				$plugin_file = WP_PLUGIN_DIR . "/$plugin_slug/$plugin_slug.php";
				if ( file_exists( $plugin_file ) ) {
					activate_plugin( $plugin_file );
				} else {
					error_log( 'Redux Framework installation failed: Plugin file not found.' );
				}
			} else {
				error_log( 'Redux Framework installation failed: Unable to download or install the plugin.' );
			}
		}
	}

	// Load Redux Framework after all plugins are loaded
	public function spartan_gallery_load_redux() {
		// Check if Redux Framework is installed and active
		if ( class_exists( 'ReduxFramework' ) ) 
		{
			
			// Path to Redux plugin's main file
			$redux_plugin_file = WP_PLUGIN_DIR . '/redux-framework/redux-framework.php';
			
			if ( file_exists( $redux_plugin_file ) ) {
				include_once $redux_plugin_file;
	
				// Now include your Redux configuration file
				//require_once plugin_dir_path( __FILE__ ) . 'redux-config.php';
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spartan-gallery-options.php';
			} else {
				error_log( 'Redux Framework plugin is not installed or not found in the plugins directory.' );
			}
		}
	}
	
	
	

}
