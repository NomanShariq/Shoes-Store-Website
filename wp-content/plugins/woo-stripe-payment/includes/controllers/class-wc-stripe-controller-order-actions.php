<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author  PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Order_Actions extends WC_Stripe_Rest_Controller {

	use WC_Stripe_Controller_Frontend_Trait;

	protected $namespace = 'order~action';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'capture',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'capture' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'int',
						'validate_callback' => array( $this, 'validate_order_id' ),
					),
					'amount'   => array(
						'required' => true,
						'type'     => 'float',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'void',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'void' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'pay',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'customer-payment-methods',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'customer_payment_methods' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'customer_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'charge-view',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'charge_view' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
	}

	/**
	 * Return true if the order_id is a valid post.
	 *
	 * @param int $order_id
	 */
	public function validate_order_id( $order_id ) {
		return null !== wc_get_order( $order_id );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function capture( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );
		$amount   = $request->get_param( 'amount' );
		if ( ! is_numeric( $amount ) ) {
			return new WP_Error(
				'invalid_data',
				__( 'Invalid amount entered.', 'woo-stripe-payment' ),
				array(
					'success' => false,
					'status'  => 200,
				)
			);
		}
		try {
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$result  = $gateway->capture_charge( $amount, $order );
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'capture-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function void( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );
		/**
		 * When the order's status is set to cancelled, the wc_stripe_order_cancelled
		 * function is called, which voids the charge.
		 */
		$order->update_status( 'cancelled' );

		return rest_ensure_response( array() );
	}

	/**
	 * Process a payment as an admin.
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_payment( $request ) {
		$order_id     = $request->get_param( 'order_id' );
		$payment_type = $request->get_param( 'payment_type' );
		$order        = wc_get_order( $order_id );
		$use_token    = $payment_type === 'token';
		try {
			// perform some validations
			if ( $order->get_total() == 0 ) {
				if ( ! wcs_stripe_active() ) {
					throw new Exception( __( 'Order total must be greater than zero.', 'woo-stripe-payment' ) );
				} else {
					if ( ! wcs_order_contains_subscription( $order ) ) {
						throw new Exception( __( 'Order total must be greater than zero.', 'woo-stripe-payment' ) );
					}
				}
			}
			// update the order's customer ID if it has changed.
			if ( $order->get_customer_id() != $request->get_param( 'customer_id' ) ) {
				$order->set_customer_id( $request->get_param( 'customer_id' ) );
			}

			if ( $order->get_transaction_id() ) {
				throw new Exception( sprintf( __( 'This order has already been processed. Transaction ID: %1$s. Payment method: %2$s', 'woo-stripe-payment' ),
					$order->get_transaction_id(),
					$order->get_payment_method_title() ) );
			}
			if ( ! $use_token ) {
				// only credit card payments are allowed for one off payments as an admin.
				$payment_method = 'stripe_cc';
			} elseif ( $payment_type === 'token' ) {
				$token_id = intval( $request->get_param( 'payment_token_id' ) );
				$token    = WC_Payment_Tokens::get( $token_id );
				if ( $token->get_user_id() !== $order->get_customer_id() ) {
					throw new Exception( __( 'Order customer Id and payment method customer Id do not match.', 'woo-stripe-payment' ) );
				}
				$payment_method = $token->get_gateway_id();
			}
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
			// temporarily set the charge type of the gateway to whatever the admin has selected.
			$gateway->settings['charge_type'] = $request->get_param( 'wc_stripe_charge_type' );
			// set the payment gateway to the order.
			$order->set_payment_method( $gateway->id );
			$order->save();
			if ( ! $use_token ) {
				$_POST[ $gateway->token_key ] = $request->get_param( 'payment_nonce' );
			} else {
				$gateway->set_payment_method_token( $token->get_token() );
			}

			// set intent attribute off_session. Stripe requires confirm to be true to use off session.
			add_filter( 'wc_stripe_payment_intent_args', function ( $args ) {
				if ( isset( $args['setup_future_usage'] ) && $args['setup_future_usage'] === 'off_session' ) {
					$args['off_session'] = false;
				} else {
					$args['off_session'] = true;
				}
				$args['confirm'] = true;

				return $args;
			} );

			$result = $gateway->process_payment( $order_id );

			if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
				return rest_ensure_response( array( 'success' => true ) );
			} else {
				// create a new order since updates to the order were made during the process_payment call.
				$order = wc_get_order( $order_id );
				$order->update_status( 'pending' );

				return new WP_Error(
					'order-error',
					$this->get_error_messages(),
					array(
						'status'  => 200,
						'success' => false,
					)
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'order-error', '<div class="woocommerce-error">' . $e->getMessage() . '</div>', array( 'status' => 200 ) );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_payment_methods( $request ) {
		$customer_id = $request->get_param( 'customer_id' );
		$tokens      = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof WC_Payment_Gateway_Stripe ) {
				$tokens = array_merge( $tokens, WC_Payment_Tokens::get_customer_tokens( $customer_id, $gateway->id ) );
			}
		}

		return rest_ensure_response(
			array(
				'payment_methods' => array_map(
					function ( $payment_method ) {
						return $payment_method->to_json();
					},
					$tokens
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function charge_view( $request ) {
		$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		try {
			// fetch the charge so data is up to date.
			$charge = WC_Stripe_Gateway::load( wc_stripe_order_mode( $order ) )->charges->retrieve( $order->get_transaction_id() );

			if ( is_wp_error( $charge ) ) {
				throw new Exception( $charge->get_error_message() );
			}
			$order->update_meta_data( WC_Stripe_Constants::CHARGE_STATUS, $charge->status );
			$order->save();
			ob_start();
			include stripe_wc()->plugin_path() . 'includes/admin/meta-boxes/views/html-charge-data-subview.php';
			$html = ob_get_clean();

			return rest_ensure_response(
				array(
					'data' => array(
						'order_id'     => $order->get_id(),
						'order_number' => $order->get_order_number(),
						'order_total'  => $order->get_total(),
						'charge'       => $charge->jsonSerialize(),
						'html'         => $html,
					),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'charge-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param $request
	 */
	public function order_actions_permission_check( $request ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error(
				'permission-error',
				__( 'You do not have permissions to access this resource.', 'woo-stripe-payment' ),
				array(
					'status' => 403,
				)
			);
		}

		return true;
	}

}
