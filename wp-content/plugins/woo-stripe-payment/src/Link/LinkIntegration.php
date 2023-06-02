<?php

namespace PaymentPlugins\Stripe\Link;

use PaymentPlugins\Stripe\Assets\AssetDataApi;
use PaymentPlugins\Stripe\Assets\AssetsApi;
use PaymentPlugins\Stripe\Controllers\PaymentIntent;

class LinkIntegration {

	const DATA_KEY = 'wcStripeLinkParams';

	/**
	 * @var \WC_Stripe_Advanced_Settings
	 */
	private $settings;

	/**
	 * @var \WC_Stripe_Account_Settings
	 */
	private $account_settings;

	/**
	 * @var \PaymentPlugins\Stripe\Assets\AssetsApi
	 */
	private $assets;

	/**
	 * @var \PaymentPlugins\Stripe\Assets\AssetDataApi
	 */
	private $data_api;

	/**
	 * @var bool
	 */
	private $enabled;

	private $supported_countries = [
		'AT',
		'BE',
		'BG',
		'CA',
		'CH',
		'CY',
		'CZ',
		'DE',
		'DK',
		'EE',
		'ES',
		'FI',
		'FR',
		'GB',
		'GI',
		'GR',
		'HR',
		'HU',
		'IE',
		'IT',
		'JP',
		'LI',
		'LT',
		'LU',
		'LV',
		'MT',
		'MX',
		'NL',
		'NO',
		'PL',
		'PT',
		'RO',
		'SE',
		'SI',
		'SK',
		'US'
	];

	private $supported_payment_methods = [ 'stripe_cc' ];

	private static $instance;

	public function __construct( \WC_Stripe_Advanced_Settings $settings, \WC_Stripe_Account_Settings $account_settings, AssetsApi $assets, AssetDataApi $data_api ) {
		self::$instance         = $this;
		$this->settings         = $settings;
		$this->account_settings = $account_settings;
		$this->assets           = $assets;
		$this->data_api         = $data_api;
		$this->enabled          = $settings->is_active( 'link_enabled' );
		if ( $this->is_active() ) {
			$this->initialize();
		}
	}

	public static function get_instance() {
		return self::$instance;
	}

	public static function instance() {
		return self::$instance;
	}

	protected function initialize() {
		$this->register_assets();
		add_action( 'wp_print_scripts', [ $this, 'enqueue_scripts' ], 5 );
		add_filter( 'wc_stripe_localize_script_wc-stripe', [ $this, 'add_script_params' ], 10, 2 );
		add_filter( 'wc_stripe_payment_intent_args', [ $this, 'add_payment_method_type' ], 10, 2 );
		add_filter( 'woocommerce_checkout_fields', [ $this, 'add_billing_email_priority' ] );
		add_filter( 'wc_stripe_payment_intent_confirmation_args', [ $this, 'add_confirmation_args' ], 10, 3 );
	}

	public function is_active() {
		return $this->enabled && $this->is_valid_account_country();
	}

	private function register_assets() {
		$this->assets->register_script( 'wc-stripe-link-checkout', 'assets/build/link-checkout.js', [ 'wc-stripe-external', 'wc-stripe-credit-card' ] );
	}

	private function is_valid_account_country() {
		return \in_array( $this->account_settings->get_account_country( wc_stripe_mode() ), $this->supported_countries );
	}


	/**
	 * @param null|\WC_Order $order
	 *
	 * @return bool|mixed
	 */
	public function can_process_link_payment( $order = null ) {
		if ( $order ) {
			return \in_array( $order->get_payment_method(), $this->supported_payment_methods, true )
			       && \in_array( $this->account_settings->get_account_country( wc_stripe_order_mode( $order ) ), $this->supported_countries );
		} else {
			return is_checkout()
			       && WC()->cart
			       && WC()->cart->needs_payment();
		}
	}

	public function enqueue_scripts() {
		if ( $this->can_process_link_payment() ) {
			$icon = $this->settings->get_option( 'link_icon', 'dark' );
			$this->data_api->print_data( self::DATA_KEY, [
				'launchLink'      => $this->is_autoload_enabled(),
				'linkIconEnabled' => $this->is_icon_enabled(),
				'linkIcon'        => $this->is_autoload_enabled() ? wc_stripe_get_template_html( "link/link-icon-{$icon}.php" ) : null,
				'elementOptions'  => array_merge( PaymentIntent::instance()->get_element_options(), [
					'currency' => strtolower( get_woocommerce_currency() ),
					'amount'   => wc_stripe_add_number_precision( WC()->cart->total )
				] )
			] );
			wp_enqueue_script( 'wc-stripe-link-checkout' );
		}
	}

	public function add_script_params( $data, $name ) {
		if ( $name === 'wc_stripe_params_v3' ) {
			$data['stripeParams']['betas'][] = 'link_autofill_modal_beta_1';
		}

		return $data;
	}

	/**
	 * @param array     $params
	 * @param \WC_Order $order
	 */
	public function add_payment_method_type( $params, $order ) {
		if ( $this->can_process_link_payment( $order ) ) {
			$params['payment_method_types'][] = 'link';
		}

		return $params;
	}

	public function add_billing_email_priority( $fields ) {
		if ( $this->settings->is_active( 'link_email' ) ) {
			if ( isset( $fields['billing']['billing_email'] ) ) {
				$fields['billing']['billing_email']['priority'] = 1;
			}
		}

		return $fields;
	}

	public function is_autoload_enabled() {
		return $this->settings->is_active( 'link_autoload' );
	}

	public function is_icon_enabled() {
		return 'no' !== $this->settings->get_option( 'link_icon', 'dark' );
	}

	/**
	 * @param array                 $args
	 * @param \Stripe\PaymentIntent $intent
	 * @param \WC_Order             $order
	 *
	 * @return void
	 */
	public function add_confirmation_args( $args, $intent, $order ) {
		if ( isset( $intent->payment_method->type ) ) {
			if ( $intent->payment_method->type === 'link' ) {
				$args['mandate_data'] = [
					'customer_acceptance' => [
						'type'   => 'online',
						'online' => [
							'ip_address' => $order->get_customer_ip_address(),
							'user_agent' => $order->get_customer_user_agent()
						]
					]
				];
			}
		}

		return $args;
	}

	public function get_settings() {
		return $this->settings;
	}

}