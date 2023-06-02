<?php

namespace PaymentPlugins\Stripe\WooCommercePreOrders;

class FrontendRequests {

	public function __construct() {
	}

	public function is_checkout_with_preorder_requires_tokenization() {
		return is_checkout() && ! is_checkout_pay_page()
		       && \WC_Pre_Orders_Cart::cart_contains_pre_order()
		       && \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() );
	}

	public function is_cart_with_preorder_requires_tokenization() {
		return is_cart() && \WC_Pre_Orders_Cart::cart_contains_pre_order()
		       && \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() );
	}

	public function is_order_pay_with_preorder_requires_tokenization() {
		if ( is_checkout_pay_page() ) {
			global $wp;
			$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );

			return $order && \WC_Pre_Orders_Order::order_contains_pre_order( $order )
			       && \WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order );
		}

		return false;
	}

}