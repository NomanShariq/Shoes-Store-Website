<?php

class WC_Stripe_Controller_SignUp extends WC_Stripe_Rest_Controller {

	protected $namespace = 'admin/signup';

	private $api_url = 'https://crm.paymentplugins.com/v1/contacts';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(), 'contact', array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register_contact' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
				'args'                => array(
					'firstname' => array(
						'required',
						'validate_callback' => function ( $value ) {
							return strlen( $value ) > 0;
						}
					),
					'email'     => array(
						'required',
						'validate_callback' => function ( $value ) {
							return strlen( $value ) > 0 && is_email( $value );
						}
					)
				)
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function register_contact( $request ) {
		$data   = array(
			'email'      => $request['email'],
			'attributes' => array(
				'firstname'      => $request['firstname'],
				'website'        => get_site_url(),
				'plugin'         => 'stripe',
				'account_active' => false
			)
		);
		$result = wp_safe_remote_post( $this->api_url, array(
			'method'      => 'POST',
			'timeout'     => 30,
			'httpversion' => 1,
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json'
			),
			'body'        => wp_json_encode( $data ),
			'cookies'     => array()
		) );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'contact-error', $result->get_error_message(), array( 'status' => 200 ) );
		}
		if ( wp_remote_retrieve_response_code( $result ) !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $result ), true );

			return new WP_Error( 'contact-error', $body['message'], array( 'status' => 200 ) );
		}
		update_option( 'wc_stripe_admin_signup', true, false );

		return rest_ensure_response( array( 'success' => true, 'message' => __( 'It\'s on its way! Please check your emails.', 'woo-stripe-payment' ) ) );
	}

}