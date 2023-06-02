<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;

use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripePayment;

/**
 * Class PaymentRequest
 *
 * @package PaymentPlugins\Blocks\Stripe\Payments
 */
class PaymentRequest extends AbstractStripePayment {

	protected $name = 'stripe_payment_request';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-stripe-blocks-payment-request', 'build/wc-stripe-payment-request.js' );

		return array( 'wc-stripe-blocks-payment-request' );
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'paymentRequestButton' => array(
				'type'   => $this->payment_method->get_option( 'button_type' ),
				'theme'  => $this->payment_method->get_option( 'button_theme' ),
				'height' => $this->payment_method->get_button_height(),
			)
		), parent::get_payment_method_data() );
	}
}