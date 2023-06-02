<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class SepaPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_sepa';

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'mandate' => $this->payment_method->get_local_payment_description()
		), parent::get_payment_method_data() );
	}
}