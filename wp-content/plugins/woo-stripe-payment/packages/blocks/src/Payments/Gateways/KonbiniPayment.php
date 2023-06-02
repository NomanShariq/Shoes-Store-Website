<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class KonbiniPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_konbini';

}