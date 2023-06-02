<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @author  Payment Plugins
 * @package Stripe/Classes
 *
 */
class WC_Stripe_API_Settings extends WC_Stripe_Settings_API {

	public function __construct() {
		$this->id        = 'stripe_api';
		$this->tab_title = __( 'API Settings', 'woo-stripe-payment' );
		parent::__construct();
	}

	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_stripe_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'                => array(
				'type'  => 'title',
				'title' => __( 'API Settings', 'woo-stripe-payment' ),
			),
			'test_mode_keys'       => array(
				'type'        => 'description',
				'description' => __( 'When test mode is enabled you can manually enter your API keys or go through the connect process. Live mode requires that you click the Connect button.',
					'woo-stripe-payment' ),
			),
			'mode'                 => array(
				'type'        => 'select',
				'title'       => __( 'Mode', 'woo-stripe-payment' ),
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'test' => __( 'Test', 'woo-stripe-payment' ),
					'live' => __( 'Live', 'woo-stripe-payment' ),
				),
				'default'     => 'test',
				'desc_tip'    => true,
				'description' => __( 'The mode determines if you are processing test transactions or live transactions on your site. Test mode allows you to simulate payments so you can test your integration.',
					'woo-stripe-payment' ),
			),
			'account_id'           => array(
				'type'              => 'paragraph',
				'title'             => __( 'Account ID', 'woo-stripe-payment' ),
				'text'              => '',
				'class'             => '',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'This is your Stripe Account ID and serves as a unique identifier.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'live'
					)
				)
			),
			'account_id_test'      => array(
				'type'              => 'paragraph',
				'title'             => __( 'Account ID', 'woo-stripe-payment' ),
				'text'              => '',
				'class'             => '',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'This is your Stripe Account ID and serves as a unique identifier.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'stripe_connect'       => array(
				'type'        => 'stripe_connect',
				'title'       => __( 'Connect Stripe Account', 'woo-stripe-payment' ),
				'label'       => __( 'Click to Connect', 'woo-stripe-payment' ),
				'class'       => 'do-stripe-connect',
				'description' => __( 'We make it easy to connect Stripe to your site. Click the Connect button to go through our connect flow.', 'woo-stripe-payment' ),
			),
			'publishable_key_test' => array(
				'title'             => __( 'Test Publishable Key', 'woo-stripe-payment' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'Your publishable key is used to initialize Stripe assets.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'secret_key_test'      => array(
				'title'             => __( 'Test Secret Key', 'woo-stripe-payment' ),
				'type'              => 'password',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'Your secret key is used to authenticate requests to Stripe.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'connection_test_live' => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Connection Test', 'woo-stripe-payment' ),
				'label'             => __( 'Connection Test', 'woo-stripe-payment' ),
				'class'             => 'wc-stripe-connection-test live-mode button-secondary',
				'description'       => __( 'Click this button to perform a connection test. If successful, your site is connected to Stripe.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'live'
					)
				)
			),
			'connection_test_test' => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Connection Test', 'woo-stripe-payment' ),
				'label'             => __( 'Connection Test', 'woo-stripe-payment' ),
				'class'             => 'wc-stripe-connection-test test-mode button-secondary',
				'description'       => __( 'Click this button to perform a connection test. If successful, your site is connected to Stripe.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'webhook_button_test'  => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Create Webhook', 'woo-stripe-payment' ),
				'label'             => __( 'Create Webhook', 'woo-stripe-payment' ),
				'class'             => 'wc-stripe-create-webhook test-mode button-secondary',
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'webhook_button_live'  => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Create Webhook', 'woo-stripe-payment' ),
				'label'             => __( 'Create Webhook', 'woo-stripe-payment' ),
				'class'             => 'wc-stripe-create-webhook live-mode button-secondary',
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'live'
					)
				)
			),
			'webhook_url'          => array(
				'type'        => 'paragraph',
				'title'       => __( 'Webhook url', 'woo-stripe-payment' ),
				'class'       => 'wc-stripe-webhook',
				'text'        => stripe_wc()->rest_api->webhook->rest_url( 'webhook' ),
				'desc_tip'    => true,
				'description' => __( '<strong>Important:</strong> the webhook url is called by Stripe when events occur in your account, like a source becomes chargeable. Use the Create Webhook button or add the webhook manually in your Stripe account.',
					'woo-stripe-payment' ),
			),
			'webhook_secret_live'  => array(
				'type'              => 'password',
				'title'             => __( 'Live Webhook Secret', 'woo-stripe-payment' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s',
					'woo-stripe-payment' ),
					'<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/webhooks?id=configure-webhooks">',
					'</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'live' ) ),
			),
			'webhook_secret_test'  => array(
				'type'              => 'password',
				'title'             => __( 'Test Webhook Secret', 'woo-stripe-payment' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s',
					'woo-stripe-payment' ),
					'<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/webhooks?id=configure-webhooks">',
					'</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'test' ) ),
			),
			'debug_log'            => array(
				'title'       => __( 'Debug Log', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'desc_tip'    => true,
				'default'     => 'yes',
				'description' => __( 'When enabled, the plugin logs important errors and info that can help you troubleshoot potential issues.', 'woo-stripe-payment' ),
			),
		);
		if ( $this->get_option( 'account_id' ) ) {
			$this->form_fields['account_id']['text']            = $this->get_option( 'account_id' );
			$this->form_fields['stripe_connect']['description'] = sprintf( __( '%s Your Stripe account has been connected.',
				'woo-stripe-payment' ),
				'<span class="dashicons dashicons-yes stripe-connect-active"></span>' );
			$this->form_fields['stripe_connect']['active']      = true;
		} else {
			unset( $this->form_fields['account_id'] );
			// don't show the live connection test unless connect process has been completed.
			unset( $this->form_fields['connection_test_live'] );
		}
		if ( $this->get_option( 'account_id_test' ) ) {
			$this->form_fields['account_id_test']['text'] = $this->get_option( 'account_id_test' );
		} else {
			unset( $this->form_fields['account_id_test'] );
		}

		foreach ( array( 'test', 'live' ) as $mode ) {
			$webhook_id = $this->get_webhook_id( $mode );
			if ( $webhook_id ) {
				$this->form_fields["webhook_button_{$mode}"]['title']       = __( 'Delete Webhook', 'woo-stripe-payment' );
				$this->form_fields["webhook_button_{$mode}"]['label']       = __( 'Delete Webhook', 'woo-stripe-payment' );
				$this->form_fields["webhook_button_{$mode}"]['class']       .= ' wc-stripe-delete-webhook';
				$this->form_fields["webhook_button_{$mode}"]['description'] = sprintf( __( '%1$s Webhook created. ID: %2$s' ),
					'<span class="dashicons dashicons-yes stripe-webhook-created"></span>',
					$webhook_id );
			}
		}
	}

	public function generate_stripe_connect_html( $key, $data ) {
		$field_key           = $this->get_field_key( $key );
		$data                = wp_parse_args(
			$data,
			array(
				'class'       => '',
				'style'       => '',
				'description' => '',
				'desc_tip'    => false,
				'css'         => '',
				'active'      => false,
			)
		);
		$data['connect_url'] = $this->get_connect_url();
		if ( $data['active'] ) {
			$data['label'] = __( 'Delete Connection', 'woo-stripe-payment' );
			$data['class'] .= ' stripe-delete-connection';
		}
		ob_start();
		include stripe_wc()->plugin_path() . 'includes/admin/views/html-stripe-connect.php';

		return ob_get_clean();
	}

	public function admin_options() {
		// Check if user is being returned from Stripe Connect
		if ( isset( $_GET['_stripe_connect_nonce'] ) && wp_verify_nonce( $_GET['_stripe_connect_nonce'], 'stripe-connect' ) ) {
			if ( isset( $_GET['error'] ) ) {
				$error = json_decode( base64_decode( wc_clean( $_GET['error'] ) ) );
				if ( property_exists( $error, 'message' ) ) {
					$message = $error->message;
				} elseif ( property_exists( $error, 'raw' ) ) {
					$message = $error->raw->message;
				} else {
					$message = __( 'Please try again.', 'woo-stripe-payment' );
				}
				wc_stripe_log_error( sprintf( 'Error connecting to Stripe account. Reason: %s', $message ) );
				$this->add_error( sprintf( __( 'We were not able to connect your Stripe account. Reason: %s', 'woo-stripe-payment' ), $message ) );
			} elseif ( isset( $_GET['response'] ) ) {
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					?>
                    <div class="error inline notice-error is-dismissible">
                        <p><?php esc_html_e( 'Not authorized to perform this action. Required permission: manage_woocommerce', 'woo-stripe-payment' ) ?></p>
                    </div>
					<?php
				} else {
					$response = json_decode( base64_decode( $_GET['response'] ) );

					// save the token to the api settings
					$this->settings['account_id']    = $response->live->stripe_user_id;
					$this->settings['refresh_token'] = $response->live->refresh_token;

					$this->settings['secret_key_live']      = $response->live->access_token;
					$this->settings['publishable_key_live'] = $response->live->stripe_publishable_key;

					$this->settings['secret_key_test']      = $response->test->access_token;
					$this->settings['publishable_key_test'] = $response->test->stripe_publishable_key;

					update_option( $this->get_option_key(), $this->settings );

					delete_option( 'wc_stripe_connect_notice' );

					// create webhooks
					$this->create_webhooks();

					/**
					 * @param array                  $response
					 * @param WC_Stripe_API_Settings $this
					 *
					 * @since 3.1.6
					 */
					do_action( 'wc_stripe_connect_settings', $response, $this );

					$this->init_form_fields();
					?>
                    <div class="updated inline notice-success is-dismissible ">
                        <p>
							<?php esc_html_e( 'Your Stripe account has been connected to your WooCommerce store. You may now accept payments in Live and Test mode.', 'woo-stripe-payment' ) ?>
                        </p>
                    </div>
					<?php
				}
			}
		}
		parent::admin_options();
	}

	public function get_connect_url() {
		return \Stripe\OAuth::authorizeUrl( array(
			'response_type'  => 'code',
			'client_id'      => stripe_wc()->client_id,
			'stripe_landing' => 'login',
			'always_prompt'  => 'true',
			'scope'          => 'read_write',
			'state'          => base64_encode(
				wp_json_encode(
					array(
						'redirect' => add_query_arg( '_stripe_connect_nonce', wp_create_nonce( 'stripe-connect' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ) )
					)
				)
			)
		) );
	}

	public function localize_settings() {
		return parent::localize_settings(); // TODO: Change the autogenerated stub
	}

	public function delete_webhook_settings( $mode ) {
		unset( $this->settings["webhook_secret_{$mode}"], $this->settings["webhook_id_{$mode}"] );
		update_option( $this->get_option_key(), $this->settings );
	}

	/**
	 * @param string $mode
	 * @param array  $events
	 *
	 * @since 3.3.13
	 * @return bool|\Stripe\WebhookEndpoint
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function create_webhook( $mode, $events = array() ) {
		$client = WC_Stripe_Gateway::load();
		$url    = get_rest_url( null, '/wc-stripe/v1/webhook' );
		if ( ! in_array( '*', $events ) ) {
			$events = apply_filters(
				'wc_stripe_webhook_events',
				array_values( array_unique( array_merge( array(
					'charge.failed',
					'charge.succeeded',
					'charge.pending',
					'source.chargeable',
					'payment_intent.succeeded',
					'payment_intent.requires_action',
					'charge.refunded',
					'charge.dispute.created',
					'charge.dispute.closed',
					'review.opened',
					'review.closed'
				), $events ) ) )
			);
		}
		$webhook = $client->mode( $mode )->webhookEndpoints->create( array(
			'api_version'    => '2020-03-02',
			'url'            => $url,
			'enabled_events' => $events,
		) );
		if ( is_wp_error( $webhook ) ) {
			wc_stripe_log_error( sprintf( 'Error creating Stripe webhook. Mode: %1$s. Reason: %2$s', $mode, $webhook->get_error_message() ) );
		} else {
			$this->settings["webhook_secret_{$mode}"] = $webhook['secret'];
			$this->settings["webhook_id_{$mode}"]     = $webhook['id'];
			update_option( $this->get_option_key(), $this->settings );
		}

		return $webhook;;
	}

	private function create_webhooks() {
		foreach ( array( 'live', 'test' ) as $mode ) {
			$this->create_webhook( $mode );
		}
	}

	public function get_webhook_id( $mode ) {
		return $this->get_option( "webhook_id_{$mode}", null );
	}

	public function get_account_id( $mode = '' ) {
		if ( ! $mode ) {
			$mode = wc_stripe_mode();
		}

		return $mode === WC_Stripe_Constants::LIVE ? $this->get_option( 'account_id' ) : $this->get_option( 'account_id_test' );
	}

	public function delete_account_settings() {
		delete_option( $this->get_option_key() );
	}

}
