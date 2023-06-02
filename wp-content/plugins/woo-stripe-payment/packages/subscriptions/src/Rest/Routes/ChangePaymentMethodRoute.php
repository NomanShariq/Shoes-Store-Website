<?php

namespace PaymentPlugins\Stripe\WooCommerceSubscriptions\Rest\Routes;

class ChangePaymentMethodRoute extends \WC_Stripe_Rest_Controller {

	protected $namespace = 'subscriptions';

	public function register_routes() {
		register_rest_route( $this->rest_uri(), 'setup-intent', [
			'methods'             => \WP_REST_Server::CREATABLE,
			'permission_callback' => '__return_true',
			'callback'            => [ $this, 'create_setup_intent' ],
			'args'                => [ 'payment_method' => [ 'required' => true ] ]
		] );
	}

	public function create_setup_intent( \WP_REST_Request $request ) {
		try {
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );

			// validate the order key
			if ( ! $order->key_is_valid( $request->get_param( 'order_key' ) ) ) {
				throw new Exception( __( 'Invalid order key.', 'woo-stripe-payment' ) );
			}
			/**
			 * @var \WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
			$args           = [
				'confirm'              => false,
				'usage'                => 'off_session',
				'payment_method_types' => [ $payment_method->get_payment_method_type() ],
				'customer'             => wc_stripe_get_customer_id( $order->get_customer_id() ),
				'metadata'             => [
					'gateway_id' => $payment_method->id,
					'order_id'   => $order->get_id()
				],
			];
			$setup_intent   = $payment_method->gateway->setupIntents->create( $args );
			if ( is_wp_error( $setup_intent ) ) {
				throw new \Exception( $setup_intent->get_error_message() );
			}

			return \rest_ensure_response( [ 'setup_intent' => $setup_intent ] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'setup-intent-error', $e->getMessage(), [ 'status' => 200 ] );
		}
	}

}