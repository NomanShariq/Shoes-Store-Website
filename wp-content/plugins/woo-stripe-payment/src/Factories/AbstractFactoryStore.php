<?php

namespace PaymentPlugins\Stripe\Factories;

abstract class AbstractFactoryStore {

	private $factories = [];

	abstract function initialize( $args );

	public function __get( string $name ) {
		$clazz = $this->get_factory_class( $name );
		if ( ! isset( $this->factories[ $name ] ) && $clazz ) {
			$this->factories[ $name ] = $this->create_factory( $clazz );
		}

		return $this->factories[ $name ];
	}

	abstract function get_factory_class( $name );

	protected function create_factory( $clazz ) {
		return new $clazz();
	}

}