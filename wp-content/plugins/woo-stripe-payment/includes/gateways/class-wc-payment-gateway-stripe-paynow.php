<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author  PaymentPlugins
 *
 */
class WC_Payment_Gateway_Stripe_PayNow extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'paynow';

	public function __construct() {
		$this->local_payment_type = 'paynow';
		$this->currencies         = array( 'SGD' );
		$this->countries          = array( 'SG' );
		$this->id                 = 'stripe_paynow';
		$this->tab_title          = __( 'PayNow', 'woo-stripe-payment' );
		$this->method_title       = __( 'PayNow', 'woo-stripe-payment' );
		$this->method_description = __( 'PayNow gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/paynow.svg' );
		parent::__construct();
	}

	public function get_local_payment_description() {
		$this->local_payment_description = wc_stripe_get_template_html( 'checkout/paynow-instructions.php', array( 'button_text' => $this->order_button_text ) );

		return parent::get_local_payment_description();
	}


}
