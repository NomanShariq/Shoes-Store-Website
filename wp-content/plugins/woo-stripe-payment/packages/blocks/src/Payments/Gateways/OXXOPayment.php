<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class OXXOPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_oxxo';
}