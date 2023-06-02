<?php

namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways\Link;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;

class Controller {

	public function __construct() {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateway' ] );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'update_order' ] );
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'payment_with_context' ), 10 );
	}

	public function initialize() {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateway' ] );
	}

	public function add_payment_gateway( $gateways ) {
		if ( ! is_admin() && $this->is_rest_request() ) {
			$gateways['stripe_link_checkout'] = new LinkPaymentGateway();
		}

		return $gateways;
	}

	/**
	 * @param \WC_Order $order
	 */
	public function update_order( $order ) {
		if ( $order->get_payment_method() === 'stripe_link_checkout' ) {
			$order->set_payment_method( 'stripe_cc' );
		}
	}

	private function is_rest_request() {
		if ( method_exists( WC(), 'is_rest_api_request' ) ) {
			return WC()->is_rest_api_request();
		}

		return false;
	}

	public function payment_with_context( PaymentContext $context ) {
		if ( $context->get_payment_method_instance() instanceof LinkPaymentGateway ) {
			$context->set_payment_method( 'stripe_cc' );
		}
	}

}