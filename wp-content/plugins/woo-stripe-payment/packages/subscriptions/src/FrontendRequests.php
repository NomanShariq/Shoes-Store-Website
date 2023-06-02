<?php

namespace PaymentPlugins\Stripe\WooCommerceSubscriptions;

class FrontendRequests {

	public function __construct() {
	}

	public function is_change_payment_method() {
		return \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
	}

	public function is_checkout_with_free_trial() {
		if ( WC()->cart ) {
			return is_checkout() && ! is_checkout_pay_page() && \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->total == 0;
		}

		return false;
	}

	public function is_order_pay_with_free_trial() {
		if ( is_checkout_pay_page() ) {
			global $wp;
			$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );

			return $order && wcs_order_contains_subscription( $order ) && $order->get_total() == 0;
		}

		return false;
	}

}