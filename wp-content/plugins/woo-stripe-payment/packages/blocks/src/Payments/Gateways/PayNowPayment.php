<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class PayNowPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_paynow';

}