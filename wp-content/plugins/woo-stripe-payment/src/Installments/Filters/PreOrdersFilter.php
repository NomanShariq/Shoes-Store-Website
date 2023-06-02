<?php

namespace PaymentPlugins\Stripe\Installments\Filters;

class PreOrdersFilter extends AbstractFilter {

	/**
	 * @var \WC_Cart|null
	 */
	private $cart;

	/**
	 * @var \WC_Order|null
	 */
	private $order;

	/**
	 * @param \WC_Cart|null  $cart
	 * @param \WC_Order|null $order
	 */
	public function __construct( $cart, $order ) {
		$this->cart  = $cart;
		$this->order = $order;
	}

	function is_available() {
		$is_available = true;
		if ( wc_stripe_pre_orders_active() ) {
			if ( $this->cart ) {
				if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
					$product      = \WC_Pre_Orders_Cart::get_pre_order_product();
					$is_available = \WC_Pre_Orders_Product::product_is_charged_upon_release( $product );
				}
			} elseif ( $this->order ) {
				$is_available = ! ( \WC_Pre_Orders_Order::order_contains_pre_order( $this->order ) && \WC_Pre_Orders_Order::order_requires_payment_tokenization( $this->order ) );
			}
		}

		return $is_available;
	}

}