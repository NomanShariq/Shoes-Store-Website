<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since  3.1.0
 * @author Payment Plugins
 *
 */
class WC_Stripe_Gateway_Conversion {

	public static function init() {
		add_filter( 'woocommerce_order_get_payment_method', array( __CLASS__, 'convert_payment_method' ), 10, 2 );
		add_filter( 'woocommerce_subscription_get_payment_method', array( __CLASS__, 'convert_payment_method' ), 10, 2 );
	}

	/**
	 *
	 * @param string   $payment_method
	 * @param WC_Order $order
	 */
	public static function convert_payment_method( $payment_method, $order ) {
		switch ( $payment_method ) {
			case 'stripe':
				// Another Stripe plugin is active, don't convert $payment_method as that could affect
				// checkout functionality.
				if ( did_action( 'woocommerce_checkout_order_processed' ) ) {
					return $payment_method;
				}
				$payment_method = 'stripe_cc';
				break;
		}

		return $payment_method;
	}

}

WC_Stripe_Gateway_Conversion::init();
