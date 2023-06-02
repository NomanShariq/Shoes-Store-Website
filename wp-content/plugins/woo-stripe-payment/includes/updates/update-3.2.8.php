<?php
/**
 * This update replaces use of wc_stripe function with stripe_wc. In version 4.5.4 of the WooCommerce
 * Stripe Payment Gateway plugin, a function named wc_stripe was introduced which creates a critical error
 * due to function having already been declared.
 */
require_once( ABSPATH . 'wp-admin/includes/file.php' );
WP_Filesystem();
/**
 *
 * @var WP_Filesystem_Base $wp_filesystem
 */
global $wp_filesystem;

// check to see if the following files exist
$templates = array( 'card-icons.php', 'payment-request-icons.php', 'cc-forms/minimalist.php' );

if ( $wp_filesystem ) {
	foreach ( $templates as $template_name ) {
		$args = array(
			trailingslashit( stripe_wc()->template_path() ) . $template_name,
			$template_name
		);
		if ( ( $template = locate_template( $args ) ) ) {
			// template exists so replace wc_stripe with stripe_wc function
			if ( ( $contents = $wp_filesystem->get_contents( $template ) ) ) {
				$contents = str_replace( 'wc_stripe', 'stripe_wc', $contents );
				if ( $wp_filesystem->put_contents( $template, $contents ) ) {
					wc_stripe_log_info( sprintf( 'template %s replaced function wc_stripe with stripe_wc', $template_name ) );
				}
			}
		}
	}
}