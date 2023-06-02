<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Token_Stripe' ) ) {
	return;
}

/**
 * @sin     3.0.0
 *
 * @author  PaymentPlugins
 * @package Stripe/Tokens
 *
 */
class WC_Payment_Token_Stripe_CC extends WC_Payment_Token_Stripe {

	use WC_Payment_Token_Payment_Method_Trait;

	protected $has_expiration = true;

	protected $type = 'Stripe_CC';

	protected $stripe_payment_type = 'payment_method';

	protected $stripe_data = array(
		'brand'         => '',
		'exp_month'     => '',
		'exp_year'      => '',
		'last4'         => '',
		'masked_number' => ''
	);

	public function details_to_props( $details ) {
		if ( isset( $details['type'] ) && $details['type'] === 'link' ) {
			$this->set_brand( 'link' );
			$this->set_format( 'short_title' );
		} else {
			if ( isset( $details['card'] ) ) {
				$card = $details['card'];
			}
			if ( $details instanceof \Stripe\Card ) {
				$card = $details;
			}
			$this->set_brand( $card['brand'] );
			$this->set_last4( $card['last4'] );
			$this->set_exp_month( $card['exp_month'] );
			$this->set_exp_year( $card['exp_year'] );
			$this->set_masked_number( sprintf( '********%s', $card['last4'] ) );
		}
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function get_masked_number( $context = 'view' ) {
		return $this->get_prop( 'masked_number', $context );
	}

	public function set_last4( $last4 ) {
		$this->set_prop( 'last4', $last4 );
	}

	public function set_masked_number( $value ) {
		$this->set_prop( 'masked_number', $value );
	}

	public function get_exp_year( $context = 'view' ) {
		return $this->get_prop( 'exp_year', $context );
	}

	public function set_exp_year( $year ) {
		$this->set_prop( 'exp_year', $year );
	}

	public function get_exp_month( $context = 'view' ) {
		return $this->get_prop( 'exp_month', $context );
	}

	public function set_exp_month( $month ) {
		$this->set_prop( 'exp_month', str_pad( $month, 2, '0', STR_PAD_LEFT ) );
	}

	public function get_html_classes() {
		return sprintf( '%s', str_replace( ' ', '', strtolower( $this->get_prop( 'brand' ) ) ) );
	}

	public function get_card_type( $context = 'view' ) {
		return $this->get_brand( $context );
	}

	public function get_formats() {
		return apply_filters( 'wc_stripe_get_token_formats', array(
			'type_ending_in'          => array(
				'label'   => __( 'Type Ending In', 'woo-stripe-payment' ),
				'example' => 'Visa ending in 1111',
				'format'  => __( '{brand} ending in {last4}', 'woo-stripe-payment' ),
			),
			'type_masked_number'      => array(
				'label'   => __( 'Type Masked Number', 'woo-stripe-payment' ),
				'example' => 'Visa ********1111',
				'format'  => '{brand} {masked_number}',
			),
			'type_dash_masked_number' => array(
				'label'   => __( 'Type Dash Masked Number', 'woo-stripe-payment' ),
				'example' => 'Visa - ********1111',
				'format'  => '{brand} - {masked_number}',
			),
			'type_last4'              => array(
				'label'   => __( 'Type Last 4', 'woo-stripe-payment' ),
				'example' => 'Visa 1111',
				'format'  => '{brand} {last4}',
			),
			'type_dash_last4'         => array(
				'label'   => __( 'Type Dash & Last 4', 'woo-stripe-payment' ),
				'example' => 'Visa - 1111',
				'format'  => '{brand} - {last4}',
			),
			'last4'                   => array(
				'label'   => __( 'Last Four', 'woo-stripe-payment' ),
				'example' => '1111',
				'format'  => '{last4}',
			),
			'card_type'               => array(
				'label'   => __( 'Card Type', 'woo-stripe-payment' ),
				'example' => 'Visa',
				'format'  => '{brand}',
			),
			'short_title'             => array(
				'label'   => __( 'Gateway Title', 'woo-stripe-payment' ),
				'example' => $this->get_basic_payment_method_title(),
				'format'  => '{short_title}'
			)
		), $this );
	}

	public function get_basic_payment_method_title() {
		if ( strtolower( $this->get_prop( 'brand' ) ) === 'link' ) {
			return __( 'Link by Stripe', 'woo-stripe-payment' );
		}

		return __( 'Credit Card', 'woo-stripe-payment' );
	}

	public function get_payment_method_title( $format = '' ) {
		if ( strtolower( $this->get_prop( 'brand' ) ) === 'link' ) {
			return $this->get_basic_payment_method_title();
		}

		return parent::get_payment_method_title( $format );
	}

}
