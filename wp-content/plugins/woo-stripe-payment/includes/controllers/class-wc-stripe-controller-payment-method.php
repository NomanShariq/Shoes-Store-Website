<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Controllers
 * @author PaymentPlugins
 * @since 3.0.0
 *
 */
class WC_Stripe_Controller_Payment_Method extends WC_Stripe_Rest_Controller {

	protected $namespace = 'payment-method';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'token',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'payment_method_from_token' ),
				'permission_callback' => '__return_true'
			)
		);
	}

	/**
	 * Creates a PaymentMethod from a Token.
	 * Use case for this controller would be if a token
	 * is provided on the client side, but PaymentIntent is desired instead of a Charge. The token must be converted to
	 * a PaymentMethod for use in a PaymentIntent.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response|mixed
	 */
	public function payment_method_from_token( $request ) {
		$result = WC_Stripe_Gateway::load()->paymentMethods->create(
			array(
				'type' => 'card',
				'card' => array( 'token' => $request->get_param( 'token' ) ),
			)
		);
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'payment-method', $result->get_error_message(), array( 'status' => 200 ) );
		}

		return rest_ensure_response( array( 'payment_method' => $result->jsonSerialize() ) );
	}
}
