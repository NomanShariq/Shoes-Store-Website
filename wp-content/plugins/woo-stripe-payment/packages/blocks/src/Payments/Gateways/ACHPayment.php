<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripePayment;

class ACHPayment extends AbstractStripePayment {

	protected $name = 'stripe_ach';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-stripe-blocks-ach', 'build/wc-stripe-ach.js' );

		return array( 'wc-stripe-blocks-ach' );
	}

	public function get_payment_method_icon() {
		return array(
			'id'  => $this->get_name(),
			'alt' => 'ACH Payment',
			'src' => $this->payment_method->icon
		);
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'businessName' => $this->payment_method->get_option( 'business_name' ),
			'mandateText'  => $this->payment_method->get_mandate_text()
		), parent::get_payment_method_data() );
	}

}