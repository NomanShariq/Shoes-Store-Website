<?php

namespace PaymentPlugins\Stripe\Products;

class ProductController {

	public function __construct() {
		$this->initialize();
	}

	public function initialize() {
		add_filter( 'woocommerce_available_variation', [ $this, 'add_variation_product_price' ] );
	}

	public function add_variation_product_price( $data ) {
		if ( isset( $data['display_price'] ) ) {
			$data['display_price_cents'] = wc_stripe_add_number_precision( $data['display_price'] );
		}

		return $data;
	}

}