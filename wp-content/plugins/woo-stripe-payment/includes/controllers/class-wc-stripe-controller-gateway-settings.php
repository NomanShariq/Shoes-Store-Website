<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Controllers
 * @author  PaymentPlugins
 *
 */
class WC_Stripe_Controller_Gateway_Settings extends WC_Stripe_Rest_Controller {

	protected $namespace = 'gateway-settings';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(), 'apple-domain', array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register_apple_domain' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			)
		);
		register_rest_route( $this->rest_uri(), 'create-webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_webhook' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			)
		);
		register_rest_route( $this->rest_uri(), 'delete-webhook', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'delete_webhook' ),
			'permission_callback' => array( $this, 'shop_manager_permission_check' )
		) );
		register_rest_route( $this->rest_uri(), 'connection-test',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'connection_test' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			)
		);
		register_rest_route( $this->rest_uri(), 'delete-connection',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'delete_connection' ),
				'permission_callback' => array( $this, 'shop_manager_permission_check' )
			)
		);
	}

	/**
	 * Register the site domain with Stripe for Apple Pay.
	 *
	 * @param WP_REST_Request $request
	 */
	public function register_apple_domain( $request ) {
		$gateway = WC_Stripe_Gateway::load();

		// try to add domain association file.
		if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '.well-known';
			$file = $path . DIRECTORY_SEPARATOR . 'apple-developer-merchantid-domain-association';
			if ( ! file_exists( $file ) ) {
				require_once( ABSPATH . '/wp-admin/includes/file.php' );
				if ( function_exists( 'WP_Filesystem' ) && ( WP_Filesystem() ) ) {
					/**
					 *
					 * @var WP_Filesystem_Base $wp_filesystem
					 */
					global $wp_filesystem;
					if ( ! $wp_filesystem->is_dir( $path ) ) {
						$wp_filesystem->mkdir( $path );
					}
					$contents = $wp_filesystem->get_contents( WC_STRIPE_PLUGIN_FILE_PATH . 'apple-developer-merchantid-domain-association' );
					$wp_filesystem->put_contents( $file, $contents, 0755 );
				}
			}
		}
		$server_name = ! empty( $request['hostname'] ) ? $request['hostname'] : $_SERVER['SERVER_NAME'];
		/**
		 * @since 3.3.9
		 */
		$server_name = apply_filters( 'wc_stripe_apple_pay_domain', $server_name );
		if ( strstr( $server_name, 'www.' ) ) {
			$server_name_2 = str_replace( 'www.', '', $server_name );
		} else {
			$server_name_2 = 'www.' . $server_name;
		}
		$domains = array( $server_name, $server_name_2 );
		try {
			$api_key = wc_stripe_get_secret_key( 'live' );
			if ( empty( $api_key ) ) {
				throw new Exception( __( 'You cannot register your domain until you have completed the Connect process on the API Settings page. A registered domain is not required when test mode is enabled.',
					'woo-stripe-payment' ) );
			}
			// fetch the Apple domains
			$registered_domains = $gateway->applePayDomains->mode( 'live' )->all( array( 'limit' => 50 ) );
			if ( ! is_wp_error( $registered_domains ) && $registered_domains ) {
				// loop through domains and delete if they match domain of site.
				foreach ( $registered_domains->data as $domain ) {
					if ( in_array( $domain->domain_name, $domains ) ) {
						$gateway->applePayDomains->mode( 'live' )->delete( $domain->id );
					}
				}
			}
			$failures = 0;
			foreach ( $domains as $domain ) {
				$result = $gateway->applePayDomains->mode( 'live' )->create( array( 'domain_name' => $domain ) );
				if ( is_wp_error( $result ) ) {
					$failures ++;
					if ( $failures > 1 ) {
						throw new Exception( $result->get_error_message() );
					}
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'domain-error', $e->getMessage(), array( 'status' => 200 ) );
		}

		return rest_ensure_response(
			array(
				'message' => sprintf(
					__(
						'Domain registered successfully. You can confirm in your Stripe Dashboard at https://dashboard.stripe.com/account/apple_pay.',
						'woo-stripe-payment'
					)
				),
			)
		);
	}

	/**
	 * Create a Stripe webhook for the site.
	 *
	 * @param WP_REST_Request $request
	 */
	public function create_webhook( $request ) {
		$url          = stripe_wc()->rest_api->webhook->rest_url( 'webhook' );
		$api_settings = stripe_wc()->api_settings;
		$env          = $request->get_param( 'environment' );
		$gateway      = WC_Stripe_Gateway::load( $env );
		$events       = array();
		// first fetch all webhooks
		$secret_key = wc_stripe_get_secret_key( $env );
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'webhook-error', __( 'You must configure your secret key before creating webhooks.', 'woo-stripe-payment' ),
				array(
					'status' => 200,
				)
			);
		}
		$webhooks = $gateway->webhookEndpoints->all( array( 'limit' => 100 ) );
		if ( ! is_wp_error( $webhooks ) ) {
			// validate that the webhook hasn't already been created.
			foreach ( $webhooks->data as $webhook ) {
				/**
				 * @var \Stripe\WebhookEndpoint $webhook
				 */
				if ( $webhook->url === $url ) {
					if ( ! $api_settings->get_option( "webhook_secret_{$env}", null ) ) {
						// get all of the events for this endpoint so they can be merged with the
						// new webhook that's created.
						$events = $webhook->enabled_events;
						$gateway->webhookEndpoints->delete( $webhook->id );
						$api_settings->delete_webhook_settings( $env );
					} else {
						return new WP_Error( 'webhook-error',
							__( 'There is already a webhook configured for this site. If you want to delete the webhook, login to your Stripe Dashboard.', 'woo-stripe-payment' ),
							array(
								'status' => 200,
							)
						);
					}
				}
			}
		}

		$webhook = $api_settings->create_webhook( $env, $events );
		if ( is_wp_error( $webhook ) ) {
			return new WP_Error( $webhook->get_error_code(), $webhook->get_error_message(), array( 'status' => 200 ) );
		} else {
			return rest_ensure_response(
				array(
					'message' => sprintf(
						__( 'Webhook created in Stripe for %s environment. You can test your webhook by logging in to the Stripe dashboard',
							'woo-stripe-payment' ),
						'live' ==
						$env ? __( 'Live', 'woo-stripe-payment' ) : __( 'Test', 'woo-stripe-payment' )
					),
					'secret'  => $webhook['secret'],
				)
			);
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function delete_webhook( $request ) {
		$api_settings = stripe_wc()->api_settings;
		$mode         = $request['mode'];
		$webhook_id   = $api_settings->get_webhook_id( $mode );
		if ( $webhook_id ) {
			$client = WC_Stripe_Gateway::load( $mode );
			$result = $client->webhookEndpoints->delete( $webhook_id );
			$api_settings->delete_webhook_settings( $mode );
			if ( is_wp_error( $result ) ) {
				return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 200 ) );
			}
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Perform a connection test
	 *
	 * @param WP_REST_Request $request
	 */
	public function connection_test( $request ) {
		$mode     = $request->get_param( 'mode' );
		$settings = stripe_wc()->api_settings;
		$api_keys = null;

		// capture all output to prevent JSON parse output errors.
		ob_start();
		try {
			if ( $mode === 'test' ) {
				// if test mode and keys not empty, save them so connect test uses most recently entered keys.
				$api_keys = array( $request->get_param( 'secret_key' ), $request->get_param( 'publishable_key' ) );

				if ( in_array( null, $api_keys ) ) {
					throw new Exception( sprintf( __( 'You must enter your API keys or connect the plugin before performing a connection test.',
						'woo-stripe-payment' ) ) );
				}
				$settings->settings['publishable_key_test'] = $settings->validate_text_field( 'publishable_key_test',
					$request->get_param( 'publishable_key' ) );
				$settings->settings['secret_key_test']      = $settings->validate_password_field( 'secret_key_test',
					$request->get_param( 'secret_key' ) );
			}

			// test the secret key
			$response = WC_Stripe_Gateway::load()->customers->mode( $mode )->all( array( 'limit' => 1 ) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( sprintf( __( 'Mode: %s. Invalid secret key. Please check your entry.', 'woo-stripe-payment' ), $mode ) );
			}

			// test the publishable key
			$response = wp_remote_post(
				'https://api.stripe.com/v1/payment_methods',
				array(
					'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					'body'    => array(
						'key'      => wc_stripe_get_publishable_key( $mode ),
						'type'     => 'card',
						'card'     => array(
							'number'    => '4242424242424242',
							'exp_month' => 12,
							'exp_year'  => 2030,
							'cvc'       => 314
						),
						'metadata' => array(
							'origin' => 'API Settings connection test'
						)
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				throw new Exception( sprintf( __( 'Mode: %s. Invalid publishable key. Please check your entry.', 'woo-stripe-payment' ), $mode ) );
			}
			if ( $response['response']['code'] == 401 ) {
				throw new Exception( sprintf( __( 'Mode: %s. Invalid publishable key. Please check your entry.', 'woo-stripe-payment' ), $mode ) );
			}

			// if test mode and keys are good, save them
			if ( $api_keys ) {
				update_option( $settings->get_option_key(), $settings->settings, 'yes' );
				do_action( 'wc_stripe_api_connection_test_success', $mode );
			}
			ob_get_clean();
		} catch ( Exception $e ) {
			return new WP_Error( 'connection-failure', $e->getMessage(), array( 'status' => 200 ) );
		}

		return rest_ensure_response( array(
			'message' => sprintf( __( 'Connection test to Stripe was successful. Mode: %s.', 'woo-stripe-payment' ), $mode )
		) );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function delete_connection( $request ) {
		stripe_wc()->api_settings->delete_account_settings();
		stripe_wc()->account_settings->delete_account_settings();

		return rest_ensure_response( array( 'success', true ) );
	}

	public function shop_manager_permission_check() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return new WP_Error( 'permission-error', __( 'You do not have permissions to access this resource.', 'woo-stripe-payment' ),
			array( 'status' => 403 ) );
	}

}
