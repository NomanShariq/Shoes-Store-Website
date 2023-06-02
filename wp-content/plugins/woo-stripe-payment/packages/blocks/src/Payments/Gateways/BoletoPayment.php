<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class BoletoPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_boleto';
}