<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author  PaymentPlugins
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Update {

	private static $updates
		= array(
			'3.0.7'  => 'update-3.0.7.php',
			'3.1.0'  => 'update-3.1.0.php',
			'3.1.1'  => 'update-3.1.1.php',
			'3.1.6'  => 'update-3.1.6.php',
			'3.1.7'  => 'update-3.1.7.php',
			'3.2.8'  => 'update-3.2.8.php',
			'3.3.13' => 'update-3.3.13.php',
			'3.3.14' => 'update-3.3.14.php',
			'3.3.19' => 'update-3.3.19.php',
			'3.3.20' => 'update-3.3.20.php',
			'3.3.21' => 'update-3.3.21.php',
			'3.3.23' => 'update-3.3.23.php',
			'3.3.24' => 'update-3.3.24.php',
			'3.3.28' => 'update-3.3.28.php',
			'3.3.34' => 'update-3.3.34.php'
		);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'update' ) );
	}

	/**
	 * Performs an update on the plugin if required.
	 */
	public static function update() {
		// if option is not set, make the default version 3.0.6.
		$current_version = get_option( WC_Stripe_Constants::VERSION_KEY, '3.0.6' );

		// if database version is less than plugin version, an update might be required.
		if ( version_compare( $current_version, stripe_wc()->version(), '<' ) ) {
			foreach ( self::$updates as $version => $path ) {
				/*
				 * If the current version is less than the version in the loop, then perform upgrade.
				 */
				if ( version_compare( $current_version, $version, '<' ) ) {
					$file = stripe_wc()->plugin_path() . 'includes/updates/' . $path;
					if ( file_exists( $file ) ) {
						include $file;
					}
					$current_version = $version;
					update_option( WC_Stripe_Constants::VERSION_KEY, $current_version );
					add_action(
						'admin_notices',
						function () use ( $current_version ) {
							$message = sprintf( __( 'Thank you for updating Stripe for WooCommerce to version %1$s.', 'woo-stripe-payment' ), $current_version );
							if ( ( $text = self::get_messages( $current_version ) ) ) {
								$message .= ' ' . $text;
							}
							printf( '<div class="notice notice-success is-dismissible"><p>%1$s</p></div>', $message );
						}
					);
				}
			}
			// save latest version.
			update_option( WC_Stripe_Constants::VERSION_KEY, stripe_wc()->version() );
		}
	}

	public static function get_messages( $version ) {
		$messages = array();

		return isset( $messages[ $version ] ) ? $messages[ $version ] : false;
	}

}

WC_Stripe_Update::init();
