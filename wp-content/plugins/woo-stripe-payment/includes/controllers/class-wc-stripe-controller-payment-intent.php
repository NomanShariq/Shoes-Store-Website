<?php

defined( 'ABSPATH' ) || exit();

/**
 * Controller which handles Payment Intent related actions such as creation.
 *
 * @author  PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Payment_Intent extends WC_Stripe_Rest_Controller {

	protected $namespace = '';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'setup-intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => array(
					$this,
					'create_setup_intent',
				),
				'args'                => array(
					'payment_method' => array(
						'required' => true
					)
				)
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'sync-payment-intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_payment_intent' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'      => array( 'required' => true ),
					'client_secret' => array( 'required' => true ),
				),
			)
		);
		register_rest_route( $this->rest_uri(), 'payment-intent', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_payment_intent_from_cart' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'payment_method'    => array( 'required' => true ),
				'payment_method_id' => array( 'required' => true )
			),
		) );
		register_rest_route( $this->rest_uri(), 'order/payment-intent', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_payment_intent_from_order' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'order_id'          => array( 'required' => true ),
				'order_key'         => array( 'required' => true ),
				'payment_method'    => array( 'required' => true ),
				'payment_method_id' => array( 'required' => true )
			),
		) );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function create_setup_intent( $request ) {
		/**
		 * @var WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		$params         = array( 'usage' => 'off_session', 'payment_method_types' => [ $payment_method->get_payment_method_type() ] );
		// @3.3.12 - check if 3DS is being forced
		if ( $payment_method->is_active( 'force_3d_secure' ) ) {
			$params['payment_method_options']['card']['request_three_d_secure'] = 'any';
		}
		$intent = $payment_method->payment_object->get_gateway()->setupIntents->create( $params );
		try {
			if ( is_wp_error( $intent ) ) {
				throw new Exception( $intent->get_error_message() );
			}

			return rest_ensure_response( array( 'intent' => array( 'client_secret' => $intent->client_secret ) ) );
		} catch ( Exception $e ) {
			return new WP_Error(
				'payment-intent-error',
				sprintf( __( 'Error creating payment intent. Reason: %s', 'woo-stripe-payment' ), $e->getMessage() ),
				array(
					'status' => 200,
				)
			);
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function sync_payment_intent( $request ) {
		try {
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order id provided', 'woo-stripe-payment' ) );
			}

			$intent = WC_Stripe_Gateway::load()->paymentIntents->retrieve( $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) );

			if ( ! hash_equals( $intent->client_secret, $request->get_param( 'client_secret' ) ) ) {
				throw new Exception( __( 'You are not authorized to update this order.', 'woo-stripe-payment' ) );
			}

			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, WC_Stripe_Utils::sanitize_intent( $intent->toArray() ) );
			$order->save();

			return rest_ensure_response( array( 'success' => true ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'payment-intent-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function create_payment_intent_from_cart( $request ) {
		try {
			$payment_intent = WC_Stripe_Utils::get_payment_intent_from_session();
			$order_id       = absint( WC()->session->get( 'order_awaiting_payment' ) );
			$order          = $order_id ? wc_get_order( $order_id ) : null;
			$result         = $this->create_payment_intent( $request, $payment_intent, $order );
			WC_Stripe_Utils::save_payment_intent_to_session( $result->payment_intent, $order );

			return rest_ensure_response( $result );
		} catch ( \Exception $e ) {
			return new WP_Error( 'payment-intent-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function create_payment_intent_from_order( $request ) {
		$order = wc_get_order( absint( $request['order_id'] ) );

		try {
			if ( ! $order || ! hash_equals( $order->get_order_key(), $request['order_key'] ) ) {
				throw new Exception( __( 'You are not authorized to update this order.', 'woo-stripe-payment' ) );
			}
			$payment_intent = $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT );
			$result         = $this->create_payment_intent( $request, $payment_intent, $order );

			return rest_ensure_response( $result );
		} catch ( Exception $e ) {
			return new WP_Error( 'payment-intent-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	private function create_payment_intent( $request, $payment_intent = null, $order = null ) {
		/**
		 * @var \WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		$params         = $this->get_create_payment_intent_params( $request, $payment_method, $order );
		if ( $payment_intent ) {
			$payment_intent = $payment_method->gateway->paymentIntents->retrieve( $payment_intent->id );
			if ( is_wp_error( $payment_intent ) || in_array( $payment_intent->status, array( 'succeeded', 'requires_capture' ) )
			     || $params['confirmation_method'] !== $payment_intent->confirmation_method
			) {
				WC_Stripe_Utils::delete_payment_intent_to_session();

				return $this->create_payment_intent( $request );
			}
			unset( $params['confirmation_method'] );
			$payment_intent = $payment_method->gateway->paymentIntents->update( $payment_intent->id, $params );
		} else {
			$payment_intent = $payment_method->gateway->paymentIntents->create( $params );
		}

		if ( is_wp_error( $payment_intent ) ) {
			throw new Exception( $payment_intent->get_error_message() );
		}

		$response     = array( 'payment_intent' => $payment_intent );
		$installments = array();
		if ( $payment_intent->payment_method_options->card->installments->enabled ) {
			$installments = \PaymentPlugins\Stripe\Installments\InstallmentFormatter::from_plans( $payment_intent->payment_method_options->card->installments->available_plans, $payment_intent->amount, $payment_intent->currency );
		}
		$response['installments_html'] = wc_stripe_get_template_html( 'installment-plans.php', array( 'installments' => $installments ) );
		$response['installments']      = $installments;

		return (object) $response;
	}

	/**
	 * @param \WP_REST_Request           $request
	 * @param \WC_Payment_Gateway_Stripe $payment_method
	 * @param null|\WC_Order             $order
	 */
	private function get_create_payment_intent_params( $request, $payment_method, $order = null ) {
		$params = array(
			'payment_method'         => $request['payment_method_id'],
			'confirmation_method'    => $payment_method->get_confirmation_method(),
			'payment_method_types'   => [ $payment_method->get_payment_method_type() ],
			'payment_method_options' => array( 'card' => array( 'installments' => array( 'enabled' => $payment_method->installments->is_available( $order ) ) ) )
		);
		if ( $order ) {
			$params['amount']   = wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() );
			$params['currency'] = $order->get_currency();
			if ( $order->get_customer_id() && ( ( $customer_id = wc_stripe_get_customer_id( $order->get_customer_id() ) ) ) ) {
				$params['customer'] = $customer_id;
			}
		} else {
			$currency           = get_woocommerce_currency();
			$total              = WC()->cart->total;
			$params['amount']   = wc_stripe_add_number_precision( $total, $currency );
			$params['currency'] = $currency;
			if ( is_user_logged_in() && ( ( $customer_id = wc_stripe_get_customer_id( get_current_user_id() ) ) ) ) {
				$params['customer'] = $customer_id;
			}
		}

		return $params;
	}

}
