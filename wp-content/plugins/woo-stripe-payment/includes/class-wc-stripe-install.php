<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Install {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'initialize' ) );
		add_filter( 'plugin_action_links_' . WC_STRIPE_PLUGIN_NAME, array( __CLASS__, 'plugin_action_links' ) );
		register_activation_hook( WC_STRIPE_PLUGIN_NAME, array( __CLASS__, 'install' ) );
	}

	public static function install() {
		if ( ! get_option( WC_Stripe_Constants::VERSION_KEY, false ) ) {
			update_option( WC_Stripe_Constants::INITIAL_INSTALL, 'yes' );
		}
		update_option( WC_Stripe_Constants::VERSION_KEY, stripe_wc()->version() );

		/**
		 * Schedule required actions. Actions are scheduled during install as they only need to be setup
		 * once.
		 */
		stripe_wc()->scheduled_actions();
	}

	public static function initialize() {
		if ( get_option( WC_Stripe_Constants::INITIAL_INSTALL, null ) === 'yes' ) {
			delete_option( WC_Stripe_Constants::INITIAL_INSTALL );
			wp_safe_redirect( admin_url( 'admin.php?page=wc-stripe-main' ) );
		}
	}

	/**
	 *
	 * @param array $links
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ), esc_html__( 'Settings', 'woo-stripe-payment' ) ),
			'docs'     => sprintf( '<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config">%s</a>', __( 'Documentation', 'woo-stripe-payment' ) ),
		);

		return array_merge( $action_links, $links );
	}

}

WC_Stripe_Install::init();
