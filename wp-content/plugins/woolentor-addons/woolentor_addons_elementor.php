<?php
/**
 * Plugin Name: ShopLentor – WooCommerce Builder for Elementor & Gutenberg
 * Description: An all-in-one WooCommerce solution to create a beautiful WooCommerce store.
 * Plugin URI:  https://woolentor.com/
 * Version:     2.5.8
 * Author:      HasThemes
 * Author URI:  https://hasthemes.com/plugins/woolentor-pro/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woolentor
 * Domain Path: /languages
 * WC tested up to: 7.5.1
 * Elementor tested up to: 3.11.5
 * Elementor Pro tested up to: 3.11.7
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'WOOLENTOR_VERSION', '2.5.8' );
define( 'WOOLENTOR_ADDONS_PL_ROOT', __FILE__ );
define( 'WOOLENTOR_ADDONS_PL_URL', plugins_url( '/', WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_ADDONS_PL_PATH', plugin_dir_path( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_ADDONS_DIR_URL', plugin_dir_url( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_PLUGIN_BASE', plugin_basename( WOOLENTOR_ADDONS_PL_ROOT ) );
define( 'WOOLENTOR_TEMPLATE', trailingslashit( WOOLENTOR_ADDONS_PL_PATH . 'includes/templates' ) );

// Required File
require_once ( WOOLENTOR_ADDONS_PL_PATH.'includes/base.php' );
\WooLentor\woolentor();

/**
 * Gutenbarge Blocks
 */
require_once ( WOOLENTOR_ADDONS_PL_PATH.'woolentor-blocks/woolentor-blocks.php' );

// Compatible With WooCommerce Custom Order Tables
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );