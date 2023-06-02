<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Stripe_Rest_Controller' ) ) {
	return;
}

/**
 *
 * @since   3.0.0
 * @author  PaymentPlugins
 * @package Stripe/Controllers
 */
class WC_Stripe_Controller_Checkout extends WC_Stripe_Rest_Controller {

	use WC_Stripe_Controller_Frontend_Trait;

	protected $namespace = '';

	private $order_review = false;

	/**
	 *
	 * @var WC_Payment_Gateway_Stripe
	 */
	private $gateway = null;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'checkout',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_checkout' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'checkout/payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'  => array( 'required' => true ),
					'order_key' => array( 'required' => true )
				)
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'order-pay',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_order_pay' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'       => array( 'required' => true ),
					'order_key'      => array( 'required' => true ),
					'payment_method' => array( 'required' => true )
				)
			)
		);
	}

	/**
	 * Process the WC Order
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function process_checkout( $request ) {
		$this->actions();
		$checkout       = WC()->checkout();
		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $gateway
		 */
		$this->gateway = $gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];

		try {
			do_action( 'wc_stripe_rest_process_checkout', $request, $gateway );
			if ( ! is_user_logged_in() ) {
				$this->create_customer( $request );
			}
			// set the checkout nonce so no exceptions are thrown.
			$_REQUEST['_wpnonce'] = $_POST['_wpnonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

			if ( 'product' == $request->get_param( 'page_id' ) ) {
				$option = new WC_Stripe_Product_Gateway_Option( current( WC()->cart->get_cart() )['data'], $gateway );
				if ( $option->has_product() ) {
					$gateway->settings['charge_type'] = $option->get_option( 'charge_type' );
				}
			}
			$this->required_post_data();
			$checkout->process_checkout();
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}
		if ( wc_notice_count( 'error' ) > 0 ) {
			return $this->send_response( false );
		}

		return $this->send_response( true );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @since 3.2.3
	 */
	public function process_payment( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Indicator so we know payment is being processed after a 3DS authentication.
		wc_maybe_define_constant( WC_Stripe_Constants::PROCESSING_PAYMENT, true );
		try {
			$order_id = absint( $request->get_param( 'order_id' ) );
			$order    = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order ID.', 'woo-stripe-payment' ) );
			}
			if ( ! $order->key_is_valid( $request->get_param( 'order_key' ) ) ) {
				throw new Exception( __( 'Invalid order key.', 'woo-stripe-payment' ) );
			}
			/**
			 * @var WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			if ( empty( $_POST ) ) {
				$_POST = array_merge( $_POST, $request->get_json_params() );
			}
			$result = $payment_method->process_payment( $order_id );

			if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
				return $this->send_response( true, $result );
			}

			return $this->send_response( false, $result );
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return $this->send_response( false );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @since 3.1.8
	 * @return WP_REST_Response
	 */
	public function process_order_pay( $request ) {
		global $wp;

		/**
		 * Only set when the order pay is being processed via Ajax.
		 */
		wc_maybe_define_constant( WC_Stripe_Constants::PROCESSING_ORDER_PAY, true );

		/**
		 * @var \WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		$order_id       = absint( $request['order_id'] );
		$order_key      = $request['order_key'];
		$order          = wc_get_order( $order_id );
		try {
			if ( ! $order || ! hash_equals( $order_key, $order->get_order_key() ) ) {
				throw new Exception( __( 'You are not authorized to update this order.', 'woo-stripe-payment' ) );
			}
			$wp->set_query_var( 'order-pay', $order_id );
			$order->set_payment_method( $payment_method->id );
			$payment_method->payment_object->set_update_payment_intent( true );
			$response = array( 'success' => true, 'needs_confirmation' => false );
			$result   = $payment_method->payment_object->process_payment( $order );
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}
			if ( ! $result->complete_payment ) {
				if ( WC_Stripe_Utils::redirect_url_has_hash( $result->redirect ) ) {
					$response['needs_confirmation'] = true;
					$response['data']               = WC_Stripe_Utils::parse_url_hash( $result->redirect );
				} else {
					$response['redirect'] = $result->redirect;
				}
			}

			return rest_ensure_response( $response );
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return new WP_Error( 'order-pay-error', $this->get_error_messages(), array( 'status' => 200 ) );
		}
	}


	/**
	 *
	 * @param WP_REST_Request $request
	 */
	private function create_customer( $request ) {
		$create = WC()->checkout()->is_registration_required();
		// create an account for the user if it's required for things like subscriptions.
		if ( wcs_stripe_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$create = true;
		}
		if ( $create ) {
			$password = wp_generate_password();
			$username = $email = $request->get_param( 'billing_email' );
			$result   = wc_create_new_customer( $email, $username, $password );
			if ( $result instanceof WP_Error ) {
				// for email exists errors you want customer to either login or use a different email address.
				throw new Exception( $result->get_error_message() );
			}

			// log the customer in
			wp_set_current_user( $result );
			wc_set_customer_auth_cookie( $result );

			// As we are now logged in, cart will need to refresh to receive updated nonces
			WC()->session->set( 'reload_checkout', true );
		}
	}

	private function send_response( $success, $defaults = array() ) {
		$reload = WC()->session->get( 'reload_checkout', false );
		$data   = wp_parse_args( $defaults, array(
			'result'   => $success ? 'success' : 'failure',
			'messages' => $reload ? null : $this->get_error_messages(),
			'reload'   => $reload,
		) );
		unset( WC()->session->reload_checkout );

		return rest_ensure_response( $data );
	}

	public function validate_payment_method( $payment_method ) {
		$gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $gateways[ $payment_method ] ) ? true : new WP_Error( 'validation-error', 'Please choose a valid payment method.' );
	}

	private function actions() {
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_checkout_posted_data', array( $this, 'filter_posted_data' ) );
	}

	/**
	 *
	 * @param WC_Order                  $order
	 * @param WC_Payment_Gateway_Stripe $gateway
	 */
	public function set_stashed_cart( $order, $gateway ) {
		wc_stripe_restore_cart( WC()->cart );
	}

	/**
	 *
	 * @param array    $data
	 * @param WP_Error $errors
	 */
	public function after_checkout_validation( $data, $errors ) {
		if ( $errors->get_error_codes() ) {
			wc_stripe_log_info( sprintf( __CLASS__ . '::checkout errors: %s', print_r( $errors->get_error_codes(), true ) ) );
			wc_add_notice(
				apply_filters(
					'wc_stripe_after_checkout_validation_notice', __( 'Please review your order details then click Place Order.', 'woo-stripe-payment' ),
					$data,
					$errors
				),
				'notice'
			);
			wp_send_json(
				array(
					'result'   => 'success',
					'redirect' => $this->get_order_review_url(),
					'reload'   => false,
				),
				200
			);
		}
	}

	private function required_post_data() {
		if ( WC()->cart->needs_shipping() ) {
			$_POST['ship_to_different_address'] = true;
		}
		if ( wc_get_page_id( 'terms' ) > 0 ) {
			$_POST['terms'] = 1;
		}
	}

	private function get_order_review_url() {
		return add_query_arg(
			array(
				'_stripe_order_review' => rawurlencode( base64_encode( wp_json_encode( array(
					'payment_method' => $this->gateway->id,
					'payment_nonce'  => $this->gateway->get_payment_source(),
				) ) ) ),
			),
			wc_get_checkout_url()
		);
	}

	/**
	 *
	 * @param int      $order_id
	 * @param array    $posted_data
	 * @param WC_Order $order
	 */
	public function checkout_order_processed( $order_id, $posted_data, $order ) {
		if ( $this->order_review ) {
			wc_add_notice( __( 'Please review your order details then click Place Order.', 'woo-stripe-payment' ), 'notice' );
			wp_send_json(
				array(
					'result'   => 'success',
					'redirect' => $this->get_order_review_url(),
				),
				200
			);
		}
	}

	public function post_payment_processes( $order, $gateway ) {
		wc_stripe_restore_cart( WC()->cart );
		$data = WC()->session->get( 'wc_stripe_cart', array() );
		unset( $data['product_cart'] );
		WC()->session->set( 'wc_stripe_cart', $data );
	}

	/**
	 * @param $data
	 *
	 * @since 3.2.1
	 * @return mixed
	 */
	public function filter_posted_data( $data ) {
		if ( isset( $data['shipping_method'], $data['shipping_country'], $data['shipping_state'] ) ) {
			$data['shipping_state'] = wc_stripe_filter_address_state( $data['shipping_state'], $data['shipping_country'] );
		}

		return $data;
	}

}
