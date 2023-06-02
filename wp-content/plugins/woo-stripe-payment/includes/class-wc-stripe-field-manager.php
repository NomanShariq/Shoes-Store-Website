<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Stripe/Classes
 * @author  Payment Plugins
 *
 */
class WC_Stripe_Field_Manager {

	private static $_cart_priority = 30;

	private static $_product_button_position;

	public static $_mini_cart_count = 0;

	public static function init() {
		add_action( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'output_banner_checkout_fields' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'before_add_to_cart' ) );
		add_action( 'init', array( __CLASS__, 'init_action' ) );
		add_action( 'woocommerce_review_order_after_order_total', array( __CLASS__, 'output_checkout_fields' ) );
		add_action( 'before_woocommerce_add_payment_method', array( __CLASS__, 'add_payment_method_fields' ) );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( __CLASS__, 'mini_cart_buttons' ), 5 );
	}

	public static function init_action() {
		self::$_cart_priority = apply_filters( 'wc_stripe_cart_buttons_order', 30 );
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'output_cart_fields' ), self::$_cart_priority );
	}

	public static function output_banner_checkout_fields() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
			if ( $gateway->supports( 'wc_stripe_banner_checkout' ) && $gateway->banner_checkout_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( $gateways ) {
			wc_stripe_get_template( 'checkout/checkout-banner.php', array( 'gateways' => $gateways ) );
		}
	}

	public static function output_checkout_fields() {
		if ( WC()->cart && wcs_stripe_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			stripe_wc()->data_api()->print_data( 'wc_stripe_cart_contains_subscription', true );
		}
		if ( WC()->cart && wc_stripe_pre_orders_active() && WC_Pre_Orders_Cart::cart_contains_pre_order() && WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Cart::get_pre_order_product() ) ) {
			stripe_wc()->data_api()->print_data( 'wc_stripe_preorder_exists', true );
		}
		do_action( 'wc_stripe_output_checkout_fields' );
	}

	public static function before_add_to_cart() {
		global $product;
		self::$_product_button_position = is_object( $product ) ? $product->get_meta( WC_Stripe_Constants::BUTTON_POSITION ) : null;
		if ( empty( self::$_product_button_position ) ) {
			self::$_product_button_position = 'bottom';
		}

		if ( 'bottom' == self::$_product_button_position ) {
			$action = 'woocommerce_after_add_to_cart_button';
		} else {
			$action = 'woocommerce_before_add_to_cart_button';
		}
		add_action( $action, array( __CLASS__, 'output_product_checkout_fields' ) );
	}

	public static function output_product_checkout_fields() {
		global $product;
		$gateways        = array();
		$ordering        = $product->get_meta( WC_Stripe_Constants::PRODUCT_GATEWAY_ORDER );
		$ordering        = ! $ordering ? array() : $ordering;
		$is_subscription = wcs_stripe_active() && WC_Subscriptions_Product::is_subscription( $product );
		$is_preorder     = wc_stripe_pre_orders_active() && WC_Pre_Orders_Product::product_is_charged_upon_release( $product );

		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wc_stripe_product_checkout' ) && ! $product->is_type( 'external' ) ) {
				if ( ( $is_subscription && ! $gateway->supports( 'subscriptions' ) ) || ( $is_preorder && ! $gateway->supports( 'pre-orders' ) ) ) {
					continue;
				}
				$option = new WC_Stripe_Product_Gateway_Option( $product, $gateway );
				if ( $option->enabled() ) {
					if ( isset( $ordering[ $gateway->id ] ) ) {
						$gateways[ $ordering[ $gateway->id ] ] = $gateway;
					} else {
						$gateways[] = $gateway;
					}
				}
			}
		}
		ksort( $gateways );

		if ( count( apply_filters( 'wc_stripe_product_payment_methods', $gateways, $product ) ) > 0 ) {
			wc_stripe_get_template(
				'product/payment-methods.php',
				array(
					'position' => self::$_product_button_position,
					'gateways' => $gateways
				)
			);
		}
	}

	public static function output_cart_fields() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wc_stripe_cart_checkout' ) && $gateway->cart_checkout_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( count( apply_filters( 'wc_stripe_cart_payment_methods', $gateways ) ) > 0 ) {
			wc_stripe_get_template(
				'cart/payment-methods.php',
				array(
					'gateways'   => $gateways,
					'after'      => self::$_cart_priority > 20,
					'cart_total' => WC()->cart->total,
				)
			);
		}
	}

	public static function mini_cart_buttons() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wc_stripe_mini_cart_checkout' ) && $gateway->mini_cart_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( count( apply_filters( 'wc_stripe_mini_cart_payment_methods', $gateways ) ) > 0 ) {
			wc_stripe_get_template(
				'mini-cart/payment-methods.php',
				array(
					'gateways' => $gateways
				)
			);
		}
	}

	/**
	 * @deprecated 3.1.8
	 */
	public static function change_payment_request() {
	}

	public static function add_payment_method_fields() {
		wc_stripe_hidden_field( 'billing_first_name', '', WC()->customer->get_first_name() );
		wc_stripe_hidden_field( 'billing_last_name', '', WC()->customer->get_last_name() );
	}

	/**
	 * @deprecated 3.1.8
	 */
	public static function pay_order_fields() {
		global $wp;
		$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
		self::output_required_fields( 'checkout', $order );
	}

	/**
	 * @param string   $page
	 * @param WC_Order $order
	 */
	public static function output_required_fields( $page, $order = null ) {
		if ( in_array( $page, array( 'cart', 'checkout' ) ) ) {
			if ( 'cart' === $page ) {
				self::output_fields( 'billing' );

				if ( WC()->cart->needs_shipping() ) {
					self::output_fields( 'shipping' );
				}
			}
		} elseif ( 'product' === $page ) {
			global $product;

			self::output_fields( 'billing' );

			if ( $product->needs_shipping() ) {
				self::output_fields( 'shipping' );
			}
		}
	}

	public static function output_fields( $prefix ) {
		$fields = WC()->checkout()->get_checkout_fields( $prefix );
		foreach ( $fields as $key => $field ) {
			printf( '<input type="hidden" id="%1$s" name="%1$s" value="%2$s"/>', $key, WC()->checkout()->get_value( $key ) );
		}
	}

	/**
	 * @param bool $needs_shipping
	 *
	 * @deprecated
	 */
	public static function output_needs_shipping( $needs_shipping ) {
	}

}

if ( ! is_admin() ) {
	WC_Stripe_Field_Manager::init();
}
