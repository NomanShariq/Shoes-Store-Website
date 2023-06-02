<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	$payment_methods = WC()->payment_gateways()->payment_gateways();
	if ( isset( $payment_methods['stripe_afterpay'] ) ) {
		/**
		 * @var \WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = $payment_methods['stripe_afterpay'];
		$title          = $payment_method->get_option( 'title_text' );
		if ( ! $title ) {
			$payment_method->update_option( 'title_text', $payment_method->method_title );
		}
	}
}