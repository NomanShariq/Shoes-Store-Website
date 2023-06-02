<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since  3.1.0
 * @author Payment Plugins
 */
trait WC_Stripe_Controller_Cart_Trait {

	/**
	 * Method that hooks in to the woocommerce_cart_ready_to_calc_shipping filter.
	 * Purpose is to ensure
	 * true is returned so shipping packages are calculated. Some 3rd party plugins and themes return false
	 * if the current page is the cart because they don't want to display the shipping calculator.
	 *
	 * @since 3.1.0
	 */
	public function add_ready_to_calc_shipping() {
		add_filter(
			'woocommerce_cart_ready_to_calc_shipping',
			function ( $show_shipping ) {
				return true;
			},
			1000
		);
	}

	/**
	 * @param WP_Rest_Request $request
	 *
	 * @since 3.1.8
	 * @return array
	 * @throws Exception
	 */
	private function get_shipping_method_from_request( $request ) {
		if ( ( $method = $request->get_param( 'shipping_method' ) ) ) {
			if ( ! preg_match( '/^(?P<index>[\w]+)\:(?P<id>.+)$/', $method, $shipping_method ) ) {
				throw new Exception( __( 'Invalid shipping method format. Expected: index:id', 'woo-stripe-payment' ) );
			}

			return array( $shipping_method['index'] => $shipping_method['id'] );
		}

		return array();
	}

	/**
	 * @param array           $address
	 * @param WP_REST_Request $request
	 */
	public function validate_shipping_address( $address, $request ) {
		if ( isset( $address['state'], $address['country'] ) ) {
			$address['state']   = wc_stripe_filter_address_state( $address['state'], $address['country'] );
			$request['address'] = $address;
		}

		return true;
	}

	/**
	 * Return an array of arguments used to add a product to the cart.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 3.3.39
	 * @return array
	 */
	protected function get_add_to_cart_args( $request ) {
		$session_args = WC()->session ? WC()->session->get( WC_Stripe_Constants::CART_ARGS, [ 'product_id' => 0 ] )
			: [ 'product_id' => 0 ];
		$args         = array(
			'product_id'   => $request->get_param( 'product_id' ),
			'qty'          => $request->get_param( 'qty' ),
			'variation_id' => $request->get_param( 'variation_id' )
		);
		$variation    = array();
		if ( $request->get_param( 'variation_id' ) ) {
			foreach ( $request->get_params() as $key => $value ) {
				if ( 'attribute_' === substr( $key, 0, 10 ) ) {
					$variation[ sanitize_title( wp_unslash( $key ) ) ] = wp_unslash( $value );
				}
			}
		}
		$args['variation'] = $variation;

		if ( isset( $session_args['product_id'] ) && $args['product_id'] === $session_args['product_id'] ) {
			array_walk( $args, function ( &$item, $key ) use ( $session_args ) {
				if ( ! $item && ! empty( $session_args[ $key ] ) ) {
					$item = $session_args[ $key ];
				}
			} );
		}
		WC()->session->set( WC_Stripe_Constants::CART_ARGS, $args );

		return $args;
	}

}

/**
 * Trait WC_Stripe_Controller_Frontend_Trait
 *
 * @since 3.1.8
 */
trait WC_Stripe_Controller_Frontend_Trait {

	/**
	 * @var WP_REST_Request
	 * @since 3.2.5
	 */
	private $request;

	protected function cart_includes() {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		wc_load_cart();
		// loads cart from session
		WC()->cart->get_cart();
		WC()->payment_gateways();
	}

	protected function frontend_includes() {
		WC()->frontend_includes();
		wc_load_cart();
		WC()->cart->get_cart();
		WC()->payment_gateways();
	}

	/**
	 * @param $request
	 *
	 * @since 3.2.2
	 * @return bool|WP_Error
	 */
	public function validate_rest_nonce( $request ) {
		if ( ! isset( $request['wp_rest_nonce'] ) || ! wp_verify_nonce( $request['wp_rest_nonce'], 'wp_rest' ) ) {
			return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie nonce is invalid' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * @param \WC_Cart $cart
	 *
	 * @return void
	 */
	protected function empty_cart( $cart ) {
		foreach ( $cart->get_cart() as $key => $item ) {
			unset( $cart->cart_contents[ $key ] );
		}
	}

	protected function get_supported_gateways( $context = 'product' ) {
		return array_filter( WC()->payment_gateways()->payment_gateways(), function ( $gateway ) use ( $context ) {
			return $gateway instanceof WC_Payment_Gateway_Stripe
			       && $gateway->supports( "wc_stripe_{$context}_checkout" )
			       && wc_string_to_bool( $gateway->get_option( 'enabled' ) );
		} );
	}

}
