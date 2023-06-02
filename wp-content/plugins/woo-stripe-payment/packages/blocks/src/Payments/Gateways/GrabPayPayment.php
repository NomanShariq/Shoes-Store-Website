<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class GrabPayPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_grabpay';
}