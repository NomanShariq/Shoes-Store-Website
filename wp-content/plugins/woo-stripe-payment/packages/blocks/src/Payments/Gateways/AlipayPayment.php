<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class AlipayPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_alipay';
}