<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class BECSPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_becs';

	public function get_payment_method_data() {
		return array_merge( parent::get_payment_method_data(), [ 'mandate' => $this->payment_method->get_local_payment_description() ] );
	}

}