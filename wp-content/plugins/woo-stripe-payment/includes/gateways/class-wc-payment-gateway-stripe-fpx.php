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
class WC_Payment_Gateway_Stripe_FPX extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'fpx';

	public function __construct() {
		$this->local_payment_type = 'fpx';
		$this->currencies         = array( 'MYR' );
		$this->countries          = array( 'MY' );
		$this->id                 = 'stripe_fpx';
		$this->tab_title          = __( 'FPX', 'woo-stripe-payment' );
		$this->method_title       = __( 'FPX', 'woo-stripe-payment' );
		$this->method_description = __( 'FPX gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/fpx.svg' );
		parent::__construct();
	}

	public function get_element_params() {
		$params                      = parent::get_element_params();
		$params['accountHolderType'] = 'individual';

		return $params;
	}
}
