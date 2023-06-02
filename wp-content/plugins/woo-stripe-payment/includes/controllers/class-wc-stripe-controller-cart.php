<?php

defined( 'ABSPATH' ) || exit();

/**
 * Controller class that perfors cart operations for client side requests.
 *
 * @author  PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Cart extends WC_Stripe_Rest_Controller {

	use WC_Stripe_Controller_Cart_Trait;
	use WC_Stripe_Controller_Frontend_Trait;

	protected $namespace = 'cart';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'shipping-method',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_method' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'shipping_method' => array( 'required' => true ),
					'payment_method'  => array( 'required' => true ),
				)
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'shipping-address',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_address' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'payment_method' => array( 'required' => true ),
					'address'        => array( 'required' => true, 'validate_callback' => array( $this, 'validate_shipping_address' ) )
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'add-to-cart',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product_id'     => array( 'required' => true ),
					'qty'            => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_quantity' ),
					),
					'payment_method' => array( 'required' => true ),
				),
			)
		);
		/**
		 *
		 * @since 3.0.6
		 */
		register_rest_route(
			$this->rest_uri(),
			'cart-calculation',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cart_calculation' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product_id'     => array( 'required' => true ),
					'qty'            => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_quantity' ),
					),
					'payment_method' => array( 'required' => true ),
				),
			)
		);
	}

	/**
	 *
	 * @param int             $qty
	 * @param WP_REST_Request $request
	 */
	public function validate_quantity( $qty, $request ) {
		if ( $qty == 0 ) {
			return $this->add_validation_error( new WP_Error( 'cart-error', __( 'Quantity must be greater than zero.', 'woo-stripe-payment' ) ) );
		}

		return true;
	}

	/**
	 * Update the shipping method chosen by the customer.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_shipping_method( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];

		wc_stripe_update_shipping_methods( $this->get_shipping_method_from_request( $request ) );

		$this->add_ready_to_calc_shipping();

		// if this request is coming from product page, add the item to the cart
		if ( 'product' == $request->get_param( 'page_id' ) ) {
			$this->empty_cart( WC()->cart );
			WC()->cart->add_to_cart( ...array_values( $this->get_add_to_cart_args( $request ) ) );
		}
		WC()->cart->calculate_totals();

		return rest_ensure_response(
			apply_filters(
				'wc_stripe_update_shipping_method_response',
				array(
					'data' => $gateway->get_update_shipping_method_response(
						array(
							'newData'          => array(
								'status'          => 'success',
								'total'           => array(
									'amount'  => wc_stripe_add_number_precision( WC()->cart->total ),
									'label'   => __( 'Total', 'woo-stripe-payment' ),
									'pending' => false,
								),
								'displayItems'    => $gateway->get_display_items(),
								'shippingOptions' => $gateway->get_formatted_shipping_methods(),
							),
							'shipping_methods' => WC()->session->get( 'chosen_shipping_methods', array() ),
						)
					),
				)
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_shipping_address( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		$address        = $request->get_param( 'address' );
		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
		try {
			wc_stripe_update_customer_location( $address );

			$this->add_ready_to_calc_shipping();

			if ( 'product' == $request->get_param( 'page_id' ) ) {
				$this->empty_cart( WC()->cart );
				WC()->cart->add_to_cart( ...array_values( $this->get_add_to_cart_args( $request ) ) );
			}
			WC()->cart->calculate_totals();

			if ( ! $this->has_shipping_methods( $gateway->get_shipping_packages() ) ) {
				throw new Exception( 'No valid shipping methods.' );
			}

			$response = rest_ensure_response(
				apply_filters(
					'wc_stripe_update_shipping_method_response',
					array(
						'data' => $gateway->get_update_shipping_address_response(
							array(
								'newData'         => array(
									'status'          => 'success',
									'total'           => array(
										'amount'  => wc_stripe_add_number_precision( WC()->cart->total ),
										'label'   => __( 'Total', 'woo-stripe-payment' ),
										'pending' => false,
									),
									'displayItems'    => $gateway->get_display_items(),
									'shippingOptions' => $gateway->get_formatted_shipping_methods(),
								),
								'address'         => $address,
								'shipping_method' => WC()->session->get( 'chosen_shipping_methods', array() )
							)
						),
					)
				)
			);
		} catch ( Exception $e ) {
			$response = new WP_Error(
				'address-error',
				$e->getMessage(),
				array(
					'status'  => 200,
					'newData' => array( 'status' => 'invalid_shipping_address' ),
				)
			);
		}

		return $response;
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function add_to_cart( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $gateway
		 */
		$gateway   = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
		$cart_args = $this->get_add_to_cart_args( $request );
		list( $product_id, $qty, $variation_id, $variation ) = array_values( $cart_args );

		$this->empty_cart( WC()->cart );

		if ( WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation ) == false ) {
			return new WP_Error( 'cart-error', $this->get_error_messages(), array( 'status' => 200 ) );
		} else {
			return rest_ensure_response(
				apply_filters(
					'wc_stripe_add_to_cart_response',
					array(
						'data' => $gateway->add_to_cart_response(
							array(
								'total'           => wc_format_decimal( WC()->cart->total, 2 ),
								'subtotal'        => wc_format_decimal( WC()->cart->subtotal, 2 ),
								'totalCents'      => wc_stripe_add_number_precision( WC()->cart->total ),
								'displayItems'    => $gateway->get_display_items( 'cart' ),
								'shippingOptions' => $gateway->get_formatted_shipping_methods(),
							)
						),
					),
					$gateway,
					$request
				)
			);
		}
	}

	/**
	 * Performs a cart calculation
	 *
	 * @param WP_REST_Request $request
	 */
	public function cart_calculation( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		$cart = clone WC()->cart;

		// clear cloned cart
		$this->empty_cart( $cart );

		$cart_args = $this->get_add_to_cart_args( $request );
		list( $product_id, $qty, $variation_id, $variation ) = array_values( $cart_args );

		if ( $cart->add_to_cart( $product_id, $qty, $variation_id, $variation ) ) {
			$cart->calculate_totals();

			$gateways = $this->get_supported_gateways();

			$response = rest_ensure_response(
				apply_filters(
					'wc_stripe_cart_calculation_response',
					array(
						'data' => array_reduce( $gateways, function ( $carry, $item ) use ( $cart ) {
							/**
							 *
							 * @var WC_Payment_Gateway_Stripe $item
							 */
							$carry[ $item->id ] = $item->add_to_cart_response(
								array(
									'total'           => wc_format_decimal( $cart->total, 2 ),
									'subtotal'        => wc_format_decimal( $cart->subtotal, 2 ),
									'totalCents'      => wc_stripe_add_number_precision( $cart->total ),
									'displayItems'    => $item->get_display_items_for_cart( $cart ),
									'shippingOptions' => $item->get_formatted_shipping_methods(),
								)
							);

							return $carry;
						}, [] ),
					),
					$gateways,
					$request
				)
			);
		} else {
			$response = new WP_Error( 'cart-error', $this->get_error_messages(), array( 'status' => 200 ) );
		}
		wc_clear_notices();

		return $response;
	}

	protected function get_error_messages() {
		return $this->get_messages( 'error' );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Rest_Controller::get_messages()
	 */
	protected function get_messages( $types = 'all' ) {
		$notices = wc_get_notices();
		$message = '';
		if ( $types !== 'all' ) {
			$types = (array) $types;
			foreach ( $notices as $type => $notice ) {
				if ( ! in_array( $type, $types ) ) {
					unset( $notices[ $type ] );
				}
			}
		}
		foreach ( $notices as $notice_by_type ) {
			if ( is_array( $notice_by_type ) ) {
				foreach ( $notice_by_type as $notice ) {
					$message .= sprintf( ' %s', $notice['notice'] );
				}
			} else {
				$message .= sprintf( ' %s', $notice_by_type );
			}
		}

		return trim( $message );
	}

	/**
	 * Return true if the provided packages have shipping methods.
	 *
	 * @param array $packages
	 */
	private function has_shipping_methods( $packages ) {
		foreach ( $packages as $i => $package ) {
			if ( ! empty( $package['rates'] ) ) {
				return true;
			}
		}

		return false;
	}

	private function filtered_body_params( $params, $filter_keys ) {
		$filter_keys = array_merge( array_filter( $filter_keys ), array( 'payment_method', 'currency', 'page_id' ) );

		return array_filter( $params, function ( $key ) use ( $filter_keys ) {
			return ! in_array( $key, $filter_keys, true );
		}, ARRAY_FILTER_USE_KEY );
	}

}
