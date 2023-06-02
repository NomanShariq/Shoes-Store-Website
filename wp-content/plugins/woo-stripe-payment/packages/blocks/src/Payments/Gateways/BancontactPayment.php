<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class BancontactPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_bancontact';

}