<?php

namespace PaymentPlugins\Stripe\Factories;

class OrderFactories {

	private $order;

	public function initialize( \WC_Order $order ) {
		$this->order = $order;
	}

}