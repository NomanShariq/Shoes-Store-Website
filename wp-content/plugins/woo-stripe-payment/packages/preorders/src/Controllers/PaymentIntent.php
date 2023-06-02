<?php

namespace PaymentPlugins\Stripe\WooCommercePreOrders\Controllers;

use PaymentPlugins\Stripe\WooCommercePreOrders\FrontendRequests;

class PaymentIntent {

	private $request;

	public function __construct( FrontendRequests $request ) {
		$this->request = $request;
		$this->initialize();
	}

	private function initialize() {
		add_filter( 'wc_stripe_create_setup_intent', [ $this, 'maybe_create_setup_intent' ] );
	}

	public function maybe_create_setup_intent( $bool ) {
		if ( $this->request->is_checkout_with_preorder_requires_tokenization() ) {
			$bool = true;
		} elseif ( $this->request->is_order_pay_with_preorder_requires_tokenization() ) {
			$bool = true;
		}

		return $bool;
	}

}