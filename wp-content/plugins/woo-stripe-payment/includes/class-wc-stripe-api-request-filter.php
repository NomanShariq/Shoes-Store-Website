<?php

defined( 'ABSPATH' ) || exit();

/**
 * @since 3.3.13
 */
class WC_Stripe_API_Request_Filter {

	private $advanced_settings;

	/**
	 * @param \WC_Stripe_Advanced_Settings $advanced_settings
	 */
	public function __construct( $advanced_settings ) {
		$this->advanced_settings = $advanced_settings;
		$this->initialize();
	}

	private function initialize() {
		add_filter( 'wc_stripe_payment_intent_args', array( $this, 'expand_payment_intent_properties' ) );
		if ( $this->advanced_settings->is_fee_enabled() ) {
			add_filter( 'wc_stripe_payment_intent_confirmation_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wc_stripe_payment_intent_retrieve_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wc_stripe_payment_intent_capture_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wc_stripe_charge_order_args', array( $this, 'expand_balance_transaction_for_charge' ) );
		}
	}

	public function expand_balance_transaction( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$args['expand']   = isset( $args['expand'] ) ? $args['expand'] : array();
		$args['expand'][] = 'charges.data.balance_transaction';

		return $args;
	}

	public function expand_payment_intent_properties( $args ) {
		$args['expand']   = isset( $args['expand'] ) ? $args['expand'] : array();
		$args['expand'][] = 'payment_method';
		if ( $this->advanced_settings->is_fee_enabled() ) {
			$args = $this->expand_balance_transaction( $args );
		}

 		return $args;
	}

	public function expand_balance_transaction_for_charge( $args ) {
		$args['expand']   = isset( $args['expand'] ) ? $args['expand'] : array();
		$args['expand'][] = 'balance_transaction';

		return $args;
	}

}