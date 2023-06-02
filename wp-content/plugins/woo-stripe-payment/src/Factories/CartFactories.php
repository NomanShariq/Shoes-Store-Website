<?php

namespace PaymentPlugins\Stripe\Factories;

use PaymentPlugins\Stripe\Factories\Cart\PaymentIntentFactory;

/**
 * @property \PaymentIntentFactory $paymentIntent;
 */
class CartFactories extends AbstractFactoryStore {

	private $mappings = [
		'paymentIntent' => PaymentIntentFactory::class
	];

	function get_factory_class( $name ) {
		return isset( $this->mappings[ $name ] ) ? $this->mappings[ $name ] : null;
	}

	function initialize( $args ) {
		// TODO: Implement initialize() method.
	}

}