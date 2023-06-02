<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author PaymentPlugins
 *
 */
class WC_Payment_Gateway_Stripe_GrabPay extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'grabpay';

	use WC_Stripe_Local_Payment_Intent_Trait;

	public function __construct() {
		$this->local_payment_type = 'grabpay';
		$this->currencies         = array( 'SGD', 'MYR' );
		$this->countries          = array( 'MY', 'SG' );
		$this->id                 = 'stripe_grabpay';
		$this->tab_title          = __( 'GrabPay', 'woo-stripe-payment' );
		$this->method_title       = __( 'GrabPay', 'woo-stripe-payment' );
		$this->method_description = __( 'GrabPay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/grabpay.svg' );
		parent::__construct();
	}
}
