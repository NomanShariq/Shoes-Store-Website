<?php

namespace PaymentPlugins;

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.1.0
 * @author PaymentPlugins
 *
 */
class WC_Stripe_Admin_Notices {

	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function notices() {
		$messages = array();
		foreach ( static::get_notices() as $key => $notice ) {
			if ( $notice['callback']() ) {
				$screen    = get_current_screen();
				$screen_id = $screen ? $screen->id : '';
				ob_start();
				echo '<div class="notice notice-info woocommerce-message"><p>' . $notice['message']() . '</p></div>';
				$message = ob_get_clean();
				if ( strstr( $screen_id, 'wc-settings' ) ) {
					$messages[] = $message;
				} else {
					echo $message;
				}
			}
		}
		// in WC 4.0 admin notices don't show on the WC Settings pages so adding this workaround.
		if ( $messages ) {
			wp_localize_script( 'wc-stripe-admin-settings', 'wc_stripe_admin_notices', $messages );
		}
	}

	public static function get_notices() {
		return array(
			'connect_notice' => array(
				'callback' => function () {
					return ( ! isset( $_GET['_stripe_connect_nonce'] ) &&
					         ( ! stripe_wc()->api_settings->get_option( 'account_id' ) && get_option( 'wc_stripe_connect_notice', 'no' ) == 'yes' ) );
				},
				'message'  => function () {
					wp_enqueue_style( 'wc-stripe-styles', stripe_wc()->assets_url( 'css/admin/admin.css' ), array(), stripe_wc()->version() );

					return sprintf(
						__(
							'At Stripe\'s request we have updated how the Stripe for WooCommerce
                                    plugin integrates with Stripe. This new integration offers even more security and 
                                    Stripe is requesting that all merchants switch. %1$sUpdate Integration%2$s',
							'woo-stripe-payment'
						),
						' <a href = "' . stripe_wc()->api_settings->get_connect_url() . '" class = "stripe-connect light-blue do-stripe-connect"><span>', '</span></a>'
					);
				},
			),
		);
	}
}

\PaymentPlugins\WC_Stripe_Admin_Notices::init();

