<?php

namespace PaymentPlugins\Blocks\Stripe\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use PaymentPlugins\Blocks\Stripe\Payments\PaymentsApi;

class SchemaController {

	private $extend_schema;

	private $payments_api;

	public function __construct( ExtendSchema $extend_schema, PaymentsApi $payments_api ) {
		$this->extend_schema = $extend_schema;
		$this->payments_api  = $payments_api;
		//add_action( 'init', [ $this, 'initialize' ], 20 );
	}

	public function initialize() {
		foreach ( $this->payments_api->get_payment_methods() as $payment_method ) {
			if ( $payment_method->is_active() ) {
				$data = $payment_method->get_endpoint_data();
				if ( ! empty( $data ) ) {
					if ( $data instanceof EndpointData ) {
						$data = $data->to_array();
					}
					$this->extend_schema->register_endpoint_data( $data );
				}
			}
		}
	}

}