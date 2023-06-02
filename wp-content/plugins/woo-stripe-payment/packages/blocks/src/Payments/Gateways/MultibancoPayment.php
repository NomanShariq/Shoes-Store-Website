<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class MultibancoPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_multibanco';
}