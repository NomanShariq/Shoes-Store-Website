<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class EPSPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_eps';
}