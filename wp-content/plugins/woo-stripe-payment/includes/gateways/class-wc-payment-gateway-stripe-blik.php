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
class WC_Payment_Gateway_Stripe_BLIK extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'blik';

	use WC_Stripe_Local_Payment_Intent_Trait;

	public function __construct() {
		$this->local_payment_type = 'blik';
		$this->currencies         = array( 'PLN' );
		$this->countries          = array( 'PL' );
		$this->id                 = 'stripe_blik';
		$this->tab_title          = __( 'BLIK', 'woo-stripe-payment' );
		$this->method_title       = __( 'BLIK', 'woo-stripe-payment' );
		$this->method_description = __( 'BLIK gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/blik.svg' );
		parent::__construct();
		$this->template_name = 'blik.php';
	}

	public function validate_fields() {
		foreach ( range( 0, 5 ) as $idx ) {
			$code = isset( $_POST[ 'blik_code_' . $idx ] ) ? $_POST[ 'blik_code_' . $idx ] : null;
			if ( ! $code || strlen( $code ) === 0 ) {
				wc_add_notice( __( 'Please provide your 6 digit BLIK code.', 'woo-stripe-payment' ), 'error' );

				return false;
			}
		}

		return true;
	}

	public function get_payment_intent_confirmation_args( $intent, $order ) {
		$code = '';
		foreach ( range( 0, 5 ) as $idx ) {
			$code .= wc_clean( $_POST[ 'blik_code_' . $idx ] );
		}

		return array(
			'payment_method_options' => array(
				'blik' => array(
					'code' => $code
				)
			)
		);
	}

}
