<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://freelancermartin.com
 * @since      1.0.0
 *
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Spartan_Gallery
 * @subpackage Spartan_Gallery/includes
 * @author     Freelancer Martin <developerforwebsites@gmail.com>
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Spartan_Gallery {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Spartan_Gallery_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SPARTAN_GALLERY_VERSION' ) ) {
			$this->version = SPARTAN_GALLERY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'spartan-gallery';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_wp_head_links();
		//$this->define_admin_important_classes();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Spartan_Gallery_Loader. Orchestrates the hooks of the plugin.
	 * - Spartan_Gallery_i18n. Defines internationalization functionality.
	 * - Spartan_Gallery_Admin. Defines all hooks for the admin area.
	 * - Spartan_Gallery_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spartan-gallery-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spartan-gallery-i18n.php';



		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-add-gallery-gutenberg-block.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spartan-gallery-post-type.php';



		/**
		 * The class responsible for the Spartan Gallery options metabox (native
		 * WordPress replacement for the previous Redux Framework panel).
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spartan-gallery-options.php';

		/**
		 * Video URL field, duplicate action and bulk image details.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spartan-gallery-admin-tools.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spartan-gallery-welcome-dashboard.php';


		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-spartan-gallery-public.php';

		/**
		 * Per-gallery settings schema (defaults, sanitizing, legacy migration).
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spartan-gallery-settings.php';

		/**
		 * "Gallery Tags" taxonomy on images (filterable galleries).
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spartan-gallery-taxonomy.php';

		/**
		 * Renders galleries (all layouts) for the shortcode and the block.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-spartan-gallery-renderer.php';








		$this->loader = new Spartan_Gallery_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Spartan_Gallery_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	 private function define_wp_head_links()
	 {
		 //code....
 	 }



	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Spartan_Gallery_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Spartan_Gallery_i18n();

		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		// Admin features register their own hooks (options metabox, admin
		// tools, blocks, Getting Started page); see the classes in admin/.

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Spartan_Gallery_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );


	}

/*
	private function define_admin_important_classes()
	{
		new BatchConvert( $this->get_plugin_name(), $this->get_version() );

	}
*/
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Spartan_Gallery_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
