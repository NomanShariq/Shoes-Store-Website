<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class KlarnaPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_klarna';

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'requiredParams' => $this->payment_method->get_required_parameters()
		), parent::get_payment_method_data() );
	}

}