<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              developerforwebsites@gmail.com
 * @since             1.0.0
 * @package           Spartan_Gallery
 *
 * @wordpress-plugin
 * Plugin Name:       Spartan Gallery
 * Description:       A responsive, multipurpose gallery plugin with sixteen layouts, a built-in lightbox with video and sharing, tag filters, load more, albums, live preview, Gutenberg blocks, and per-gallery styling options. No jQuery on the front end.
 * Version:           1.8.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Freelancer Martin
 * Author URI:        https://freelancermartin.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       spartan-gallery
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version (SemVer).
 */
define( 'SPARTAN_GALLERY_VERSION', '1.8.0' );


/**
 * Show the Getting Started page once, right after activation.
 */
function spartan_gallery_welcome_activate() {
	set_transient( 'spartan_gallery_welcome_redirect', true, 60 );
}
register_activation_hook( __FILE__, 'spartan_gallery_welcome_activate' );

function spartan_gallery_welcome_deactivate() {
	delete_transient( 'spartan_gallery_welcome_redirect' );
}
register_deactivation_hook( __FILE__, 'spartan_gallery_welcome_deactivate' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-spartan-gallery-activator.php
 */
function activate_spartan_gallery() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-spartan-gallery-activator.php';
	Spartan_Gallery_Activator::activate();
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-spartan-gallery-deactivator.php
 */
function deactivate_spartan_gallery() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-spartan-gallery-deactivator.php';
	Spartan_Gallery_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_spartan_gallery' );
register_deactivation_hook( __FILE__, 'deactivate_spartan_gallery' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-spartan-gallery.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_spartan_gallery() {

	$plugin = new Spartan_Gallery();
	$plugin->run();

}
run_spartan_gallery();
