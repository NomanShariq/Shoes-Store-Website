<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.5
 * @package Stripe/Tokens
 * @author  Payment Plugins
 *
 */
class WC_Payment_Token_Stripe_ACH extends WC_Payment_Token_Stripe {

	use WC_Payment_Token_Payment_Method_Trait;

	protected $type = 'Stripe_ACH';

	protected $stripe_data = array(
		'bank_name'      => '',
		'routing_number' => '',
		'last4'          => '',
		'account_type'   => ''
	);

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Token_Stripe::details_to_props()
	 */
	public function details_to_props( $details ) {
		if ( isset( $details['us_bank_account'] ) ) {
			$bank = $details['us_bank_account'];
		} elseif ( isset( $details['ach_debit'] ) ) {
			// Plaid used this property
			$bank = $details['ach_debit'];
		} elseif ( $details instanceof \Stripe\BankAccount ) {
			$bank = $details;
		}
		$this->set_brand( $bank['bank_name'] );
		$this->set_bank_name( $bank['bank_name'] );
		$this->set_last4( $bank['last4'] );
		$this->set_routing_number( $bank['routing_number'] );
		$this->set_account_type( $bank['account_type'] );
	}

	public function get_bank_name( $context = 'view' ) {
		return $this->get_prop( 'bank_name', $context );
	}

	public function get_routing_number( $context = 'view' ) {
		return $this->get_prop( 'routing_number', $context );
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function get_account_type( $context = 'view' ) {
		return $this->get_prop( 'account_type', $context );
	}

	public function set_bank_name( $value ) {
		$this->set_prop( 'bank_name', $value );
	}

	public function set_routing_number( $value ) {
		$this->set_prop( 'routing_number', $value );
	}

	public function set_last4( $value ) {
		$this->set_prop( 'last4', $value );
	}

	public function set_account_type( $value ) {
		$this->set_prop( 'account_type', $value );
	}

	public function get_formats() {
		return apply_filters( 'wc_stripe_get_token_formats', array(
			'type_ending_in'    => array(
				'label'   => __( 'Type Ending In', 'woo-stripe-payment' ),
				'example' => 'Chase ending in 3434',
				'format'  => __( '{bank_name} ending in {last4}', 'woo-stripe-payment' ),
			),
			'name_masked_last4' => array(
				'label'   => __( 'Type Ending In', 'woo-stripe-payment' ),
				'example' => 'Chase **** 3434',
				'format'  => __( '{bank_name} **** {last4}', 'woo-stripe-payment' ),
			),
			'short_title'       => array(
				'label'   => __( 'Gateway Title', 'woo-stripe-payment' ),
				'example' => $this->get_basic_payment_method_title(),
				'format'  => '{short_title}'
			)
		), $this );
	}

	public function get_html_classes() {
		return 'wc-stripe-ach';
	}

	public function get_basic_payment_method_title() {
		return __( 'Bank Payment', 'woo-stripe-payment' );
	}

}
