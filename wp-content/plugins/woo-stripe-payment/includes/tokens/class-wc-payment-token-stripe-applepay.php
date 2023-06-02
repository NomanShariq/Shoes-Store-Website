<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token_Stripe_CC' ) ) {
	return;
}

/**
 *
 * @author PaymentPlugins
 * @package Stripe/Tokens
 *
 */
class WC_Payment_Token_Stripe_ApplePay extends WC_Payment_Token_Stripe_CC {

	protected $type = 'Stripe_ApplePay';

	public function get_basic_payment_method_title() {
		return __( 'Apple Pay', 'woo-stripe-payment' );
	}
}
