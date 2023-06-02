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
class WC_Payment_Gateway_Stripe_Multibanco extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Charge_Trait;

	public function __construct() {
		$this->local_payment_type = 'multibanco';
		$this->currencies         = array( 'EUR' );
		$this->countries          = array( 'PT' );
		$this->id                 = 'stripe_multibanco';
		$this->tab_title          = __( 'Multibanco', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Multibanco', 'woo-stripe-payment' );
		$this->method_description = __( 'Multibanco gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/multibanco.svg' );
		parent::__construct();
	}
}
