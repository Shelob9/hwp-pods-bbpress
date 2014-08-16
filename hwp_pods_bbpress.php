<?php
/**
 * Plugin Name: HWP Pods bbPress
 * Plugin URI:  http://wordpress.org/plugins
 * Description: The best WordPress extension ever made!
 * Version:     0.1.0
 * Author:      Josh Pollock
 * Author URI:  
 * License:     GPLv2+
 * Text Domain: hwp_bpp
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 Josh Pollock (email : jpollock412@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'HWP_BPP_VERSION', '0.1.0' );
define( 'HWP_BPP_URL',     plugin_dir_url( __FILE__ ) );
define( 'HWP_BPP_PATH',    dirname( __FILE__ ) . '/' );
define( 'HWP_BPP_ASSETS_URL', HWP_BPP_URL.'/assets/' );
define( 'HWP_BPP_CLASS_PATH', HWP_BPP_PATH.'includes/classes/' );

//todo set this false if not defined.
define( 'HWP_BPP_DEV', true );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function hwp_bpp_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'hwp_bpp' );
	load_textdomain( 'hwp_bpp', WP_LANG_DIR . '/hwp_bpp/hwp_bpp-' . $locale . '.mo' );
	load_plugin_textdomain( 'hwp_bpp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Activate the plugin
 */
function hwp_bpp_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	hwp_bpp_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hwp_bpp_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function hwp_bpp_deactivate() {

}
register_deactivation_hook( __FILE__, 'hwp_bpp_deactivate' );

// Wireup actions
add_action( 'init', 'hwp_bpp_init' );
add_action( 'plugins_loaded', 'hwp_bpp_load_classes' );
//add_action( 'bbp_post_request', function() { wp_die( print_r2( $_POST )); });

// Wireup filters

// Wireup shortcodes


/**
 * Load classes if dependent classes exist
 */
function hwp_bpp_load_classes() {
	if ( defined( 'PODS_VERSION' ) && function_exists( 'bbPress' ) ) {
		include_once( 'includes/classes/pods_bbpress.php' );
		$GLOBALS[ 'hwp_bpp_pods_bbpress' ] = new pods_bbpress();
	}


}

/**
 * Extends the 'topic' post type from bbPress and/or adds fields to it.
 *
 * @since 0.0.1
 *
 * @param bool $meta_storage Optional. Whether to use meta storage, the default or table storage.
 * @param bool $create Optional. Whether to create the Pod, the default, or not.
 * @param bool $add_fields Optional. If false, the default, no fields will be added. Fields should be specified using the 'hwp_bpp_topic_fields' filter.
 *
 * @return
 */
function hwp_bbp_setup_pods( $meta_storage = true, $create = true, $add_fields = false ) {
	if ( defined( 'PODS_VERSION' ) ) {
		include( HWP_BPP_CLASS_PATH.'hwp_bpp_configure_pods.php' );
		return new hwp_bpp_configure_pods( $meta_storage, $create, $add_fields );
	}

}

