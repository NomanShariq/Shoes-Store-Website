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
class WC_Payment_Gateway_Stripe_P24 extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'p24';

	public function __construct() {
		$this->local_payment_type = 'p24';
		$this->currencies         = array( 'EUR', 'PLN' );
		$this->countries          = array( 'PL' );
		$this->id                 = 'stripe_p24';
		$this->tab_title          = __( 'Przelewy24', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Przelewy24', 'woo-stripe-payment' );
		$this->method_description = __( 'P24 gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/p24.svg' );
		parent::__construct();
	}
}
