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
class WC_Payment_Gateway_Stripe_Sofort extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'sofort';

	public function __construct() {
		$this->synchronous        = false;
		$this->local_payment_type = 'sofort';
		$this->currencies         = array( 'EUR' );
		$this->countries          = $this->limited_countries = array( 'AT', 'BE', 'DE', 'ES', 'IT', 'NL' );
		$this->id                 = 'stripe_sofort';
		$this->tab_title          = __( 'Sofort', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Sofort', 'woo-stripe-payment' );
		$this->method_description = __( 'Sofort gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/sofort.svg' );
		parent::__construct();
	}

}
