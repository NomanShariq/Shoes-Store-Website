<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class FPXPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_fpx';
}