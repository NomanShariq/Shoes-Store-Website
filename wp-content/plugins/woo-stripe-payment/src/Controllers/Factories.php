<?php

namespace PaymentPlugins\Stripe\Controllers;

use PaymentPlugins\Stripe\Factories\CartFactories;
use PaymentPlugins\Stripe\Factories\OrderFactories;

class Factories {

	public $order;

	public $cart;

	public function __construct( CartFactories $cart_factories, OrderFactories $order_factories ) {
		$this->cart  = $cart_factories;
		$this->order = $order_factories;
	}

}