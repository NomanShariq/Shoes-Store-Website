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
class WC_Payment_Gateway_Stripe_Ideal extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'ideal';

	use WC_Stripe_Local_Payment_Intent_Trait;

	public function __construct() {
		$this->local_payment_type = 'ideal';
		$this->currencies         = array( 'EUR' );
		$this->countries          = array( 'NL' );
		$this->id                 = 'stripe_ideal';
		$this->tab_title          = __( 'iDEAL', 'woo-stripe-payment' );
		$this->method_title       = __( 'iDEAL', 'woo-stripe-payment' );
		$this->method_description = __( 'Ideal gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/ideal.svg' );
		parent::__construct();
	}
}
