<?php

namespace PaymentPlugins\Stripe\Messages;

class MessageController {

	public function __construct() {
		$this->initialize();
	}

	private function initialize() {
		add_filter( 'wc_stripe_api_get_wp_error', [ $this, 'filter_error_message' ] );
	}

	/**
	 * @param \WP_Error $error
	 */
	public function filter_error_message( $error ) {
		if ( $error ) {
			$data = $error->get_error_data();
			if ( $data && isset( $data['code'] ) && $this->is_frontend_request() ) {
				$code = $data['code'];
				if ( isset( $data['param'] ) ) {
					$code = $code . ':' . $data['param'];
				}
				if ( $this->has_code( $code ) ) {
					$message = $this->get_messages()[ $code ];
					if ( \is_callable( $message ) ) {
						$message = $message( $error, $data );
					}
					$error = new \WP_Error( $code, $message, $data );
				}
			}
		}

		return $error;
	}

	private function is_frontend_request() {
		return ! is_admin() || defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' );
	}

	private function has_code( $key ) {
		return array_key_exists( $key, $this->get_messages() );
	}

	private function get_messages() {
		static $messages = null;
		if ( ! $messages ) {
			$messages = [
				'resource_missing:customer'       => function ( $error, $data ) {
					if ( current_user_can( 'manage_woocommerce' ) ) {
						return sprintf( '%s. %s', $error->get_error_message(), __( 'This customer ID does not exist in your Stripe account. To resolve, navigate to the Edit Profile page in the WordPress Admin and delete the user\'s Stripe customer ID.', 'woo-stripe-payment' ) );
					}

					return sprintf( '%s. %s', $error->get_error_message(), __( 'This customer ID does not exist in the merchant\'s Stripe account. Please contact us and we\'ll update your account.', 'woo-stripe-payment' ) );
				},
				'resource_missing:payment_method' => function ( $error, $data ) {
					if ( current_user_can( 'manage_woocommerce' ) ) {
						return sprintf( '%s. %s', $error->get_error_message(),
							__( 'This payment method does not exist in your Stripe account. This usually happens when you change the Stripe account the plugin is connected to. To resolve, you can remove the Stripe customer ID on the Edit Profile page in the WordPress Admin.',
								'woo-stripe-payment' ) );
					}

					return sprintf( '%s. %s', $error->get_error_message(), __( 'This payment method does not exist in the merchant\'s Stripe account. Please contact us and we\'ll update your account.', 'woo-stripe-payment' ) );
				}
			];
			$messages = apply_filters( 'wc_stripe_get_api_error_messages', $messages );
		}

		return $messages;
	}

}