<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class SofortPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_sofort';
}