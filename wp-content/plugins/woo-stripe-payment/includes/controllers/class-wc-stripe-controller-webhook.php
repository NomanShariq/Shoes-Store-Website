<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author  PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Webhook extends WC_Stripe_Rest_Controller {

	protected $namespace = '';

	private $secret;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true'
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'webhook',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_get_request' ),
				'permission_callback' => '__return_true'
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function webhook( $request ) {
		$payload         = $request->get_body();
		$json_payload    = json_decode( $payload, true );
		$mode            = $json_payload['livemode'] == true ? 'live' : 'test';
		$webhook_id_live = stripe_wc()->api_settings->get_option( 'webhook_id_live' );
		$webhook_id_test = stripe_wc()->api_settings->get_option( 'webhook_id_test' );
		$webhook_id      = "webhook_id_${mode}";
		$this->secret    = stripe_wc()->api_settings->get_option( 'webhook_secret_' . $mode );
		$header          = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
		try {
			// if the webhook ID exists and doesn't match the ID from the notification, then don't process. This will
			// happen if the Stripe account has multiple webhooks configured.
			if ( $$webhook_id && isset( $json_payload['data']['object']['metadata']['webhook_id'] ) && $$webhook_id !== $json_payload['data']['object']['metadata']['webhook_id'] ) {
				return rest_ensure_response( array() );
			}
			$event = \Stripe\Webhook::constructEvent( $payload, $header, $this->secret, apply_filters( 'wc_stripe_webhook_signature_tolerance', 600 ) );
			wc_stripe_log_info( sprintf( 'Webhook notification received: Event: %s', $event->type ) );
			$type = $event->type;
			$type = str_replace( '.', '_', $type );

			// allow functionality to hook in to the event action
			do_action( 'wc_stripe_webhook_' . $type, $event->data->object, $request, $event );

			return rest_ensure_response( apply_filters( 'wc_stripe_webhook_response', array(), $event, $request ) );
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			wc_stripe_log_error( sprintf( __( 'Invalid signature received. Verify that your webhook secret is correct. Error: %s', 'woo-stripe-payment' ), $e->getMessage() ) );

			return $this->send_error_response( __( 'Invalid signature received. Verify that your webhook secret is correct.', 'woo-stripe-payment' ), 401 );
		} catch ( Exception $e ) {
			wc_stripe_log_info( sprintf( __( 'Error processing webhook. Message: %s Exception: %s', 'woo-stripe-payment' ), $e->getMessage(), get_class( $e ) ) );

			return $this->send_error_response( $e->getMessage() );
		}
	}

	private function send_error_response( $message, $code = 400 ) {
		return new WP_Error( 'webhook-error', $message, array( 'status' => $code ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	public function handle_get_request( $request ) {
		return rest_ensure_response( [
			'message' => __( 'Stripe sends webhook notifications via the http POST method. You cannot test the webhook using a browser.', 'woo-stripe-payment' )
		] );
	}

}
