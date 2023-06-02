<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Stripe/Tokens
 * @author  PaymentPlugins
 *
 */
class WC_Payment_Token_Stripe_Local extends WC_Payment_Token_Stripe {

	use WC_Payment_Token_Source_Trait;

	protected $type = 'Stripe_Local';

	protected $stripe_data = array( 'gateway_title' => '' );

	public function details_to_props( $details ) {
	}

	public function set_gateway_title( $value ) {
		$this->set_prop( 'gateway_title', $value );
	}

	public function get_gateway_title( $context = 'view' ) {
		return $this->get_prop( 'gateway_title', $context );
	}

	public function get_formats() {
		return apply_filters( 'wc_stripe_get_local_token_formats', array(
			'gateway_title' => array(
				'label'   => __( 'Gateway Title', 'woo-stripe-payment' ),
				'example' => $this->get_brand(),
				'format'  => '{gateway_title}',
			)
		), $this );
	}

	public function get_html_classes() {
		return $this->get_gateway_id();
	}

}
