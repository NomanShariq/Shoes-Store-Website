<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.2.4
 * @package Stripe/Tokens
 * @author  PaymentPlugins
 *
 */
class WC_Payment_Token_Stripe_Becs extends WC_Payment_Token_Stripe_Local {

	use WC_Payment_Token_Payment_Method_Trait;

	protected $type = 'Stripe_Becs';

	protected $stripe_data = array(
		'bsb_number' => '',
		'last4'      => '',
		'mandate'    => ''
	);

	public function details_to_props( $details ) {
		if ( isset( $details['au_becs_debit'] ) ) {
			$this->set_last4( $details['au_becs_debit']['last4'] );
			$this->set_bsb_number( $details['au_becs_debit']['bsb_number'] );
			$this->set_mandate( $details['au_becs_debit']['mandate'] );
		}
	}

	public function set_last4( $value ) {
		$this->set_prop( 'last4', $value );
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function set_bsb_number( $value ) {
		$this->set_prop( 'bsb_number', $value );
	}

	public function get_bsb_number( $context = 'view' ) {
		return $this->get_prop( 'bsb_number', $context );
	}

	public function set_mandate( $value ) {
		$this->set_prop( 'mandate', $value );
	}

	public function get_mandate( $context = 'view' ) {
		return $this->get_prop( 'mandate', $context );
	}

	public function get_brand( $context = 'view' ) {
		return __( 'BECS', 'woo-stripe-payment' );
	}

	public function get_formats() {
		return wp_parse_args( array(
			'type_ending_last4' => array(
				'label'   => __( 'Gateway Title', 'woo-stripe-payment' ),
				'example' => 'BECS ending in 0005',
				'format'  => __( '{brand} ending in {last4}', 'woo-stripe-payment' ),
			),
			'type_last4'        => array(
				'label'   => __( 'Type Last 4', 'woo-stripe-payment' ),
				'example' => 'BECS 1111',
				'format'  => '{brand} {last4}',
			),
		), parent::get_formats() );
	}

}