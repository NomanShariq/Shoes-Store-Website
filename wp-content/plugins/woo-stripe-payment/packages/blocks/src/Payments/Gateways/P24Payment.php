<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class P24Payment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_p24';
}