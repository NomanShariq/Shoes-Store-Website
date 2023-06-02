<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class GiropayPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_giropay';

}