<?php

namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways\Link;

class LinkPaymentGateway extends \WC_Payment_Gateway {

	public $id = 'stripe_link_checkout';

	public function __construct() {
		$this->supports = [];
	}

	public function is_available() {
		return true;
	}


}