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
class WC_Payment_Gateway_Stripe_Giropay extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'giropay';

	public function __construct() {
		$this->local_payment_type = 'giropay';
		$this->currencies         = array( 'EUR' );
		$this->countries          = array( 'DE' );
		$this->id                 = 'stripe_giropay';
		$this->tab_title          = __( 'Giropay', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Giropay', 'woo-stripe-payment' );
		$this->method_description = __( 'Giropay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/giropay.svg' );
		parent::__construct();
	}

}
