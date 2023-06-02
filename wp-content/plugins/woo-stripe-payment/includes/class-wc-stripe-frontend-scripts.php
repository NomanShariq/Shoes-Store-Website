<?php

defined( 'ABSPATH' ) || exit();

/**
 * Handles scrip enqueuement and output of params needed by the plugin.
 *
 * @package Stripe/Classes
 * @author  PaymentPlugins
 */
class WC_Stripe_Frontend_Scripts {

	public $prefix = 'wc-stripe-';

	public $registered_scripts = array();

	public $enqueued_scripts = array();

	public $localized_scripts = array();

	public $localized_data = array();

	public $global_scripts = array(
		'external' => 'https://js.stripe.com/v3/',
		'gpay'     => 'https://pay.google.com/gp/p/js/pay.js'
	);

	public $assets_api;

	public function __construct( \PaymentPlugins\Stripe\Assets\AssetsApi $assets_api ) {
		$this->assets_api = $assets_api;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ), 6 );
	}

	/**
	 * Enqueue all frontend scripts needed by the plugin
	 */
	public function enqueue_scripts() {
		// register global scripts
		foreach ( $this->global_scripts as $handle => $src ) {
			$this->register_script( $handle, $src );
		}

		$this->register_script( 'form-handler', $this->assets_url( 'js/frontend/form-handler.js' ), array( 'jquery' ) );

		// register scripts that aren't part of gateways
		$this->register_script( 'wc-stripe', $this->assets_url( 'js/frontend/wc-stripe.js' ),
			array(
				'jquery',
				$this->get_handle( 'external' ),
				'woocommerce',
				$this->get_handle( 'form-handler' )
			) );

		// mini cart is not relevant on cart and checkout page.
		if ( ! is_checkout() && ! is_cart() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( $gateway instanceof WC_Payment_Gateway_Stripe && $gateway->is_available() && $gateway->mini_cart_enabled() ) {
					$gateway->enqueue_frontend_scripts( 'mini_cart' );
				}
			}
		}

		if ( function_exists( 'wp_add_inline_script' ) ) {
			wp_add_inline_script( $this->get_handle( 'wc-stripe' ), '(function(){
				if(window.navigator.userAgent.match(/MSIE|Trident/)){
					var script = document.createElement(\'script\');
					script.setAttribute(\'src\', \'' . $this->assets_url( 'js/frontend/promise-polyfill.min.js' ) . '\');
					document.head.appendChild(script);
				}
			}());' );
		}
	}

	public function localize_scripts() {
		$account_id = wc_stripe_get_account_id();
		$this->localize_script( 'wc-stripe',
			array(
				'api_key'      => wc_stripe_get_publishable_key(),
				'account'      => $account_id,
				'page'         => $this->get_page_id(),
				'version'      => stripe_wc()->version(),
				'mode'         => wc_stripe_mode(),
				'stripeParams' => array(
					'stripeAccount' => $account_id,
					'apiVersion'    => '2022-08-01',
					'betas'         => array()
				)
			),
			'wc_stripe_params_v3'
		);
		$this->localize_script( 'form-handler',
			array(
				'no_results' => __(
					'No matches found',
					'woo-stripe-payment'
				),
			)
		);
		$this->localize_script( 'wc-stripe', wc_stripe_get_error_messages(), 'wc_stripe_messages' );
		$this->localize_script( 'wc-stripe', wc_stripe_get_checkout_fields(), 'wc_stripe_checkout_fields' );

		// don't need to call localize_scripts twice.
		if ( doing_action( 'wp_print_scripts' ) ) {
			remove_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
		}
	}

	public function enqueue_checkout_scripts() {
		$this->enqueue_local_payment_scripts();
	}

	public function enqueue_local_payment_scripts() {
		if ( ! in_array( $this->get_handle( 'local-payment' ), $this->enqueued_scripts ) ) {
			$data = wc_stripe_get_local_payment_params();
			// only enqueue local payment script if there are local payment gateways that have been enabled.
			if ( ! empty( $data['gateways'] ) ) {
				$this->enqueue_script(
					'local-payment',
					$this->assets_url( 'js/frontend/local-payment.js' ),
					array(
						$this->get_handle( 'external' ),
						$this->get_handle( 'wc-stripe' ),
					)
				);
				$this->localize_script( 'local-payment', $data );
			}
		}
	}

	public function register_script( $handle, $src, $deps = array(), $version = '', $footer = true ) {
		$version                    = empty( $version ) && null !== $version ? stripe_wc()->version() : $version;
		$this->registered_scripts[] = $this->get_handle( $handle );
		wp_register_script( $this->get_handle( $handle ), $src, $deps, $version, $footer );
	}

	public function enqueue_script( $handle, $src = '', $deps = array(), $version = '', $footer = true ) {
		$handle  = $this->get_handle( $handle );
		$version = empty( $version ) && null !== $version ? stripe_wc()->version() : $version;
		if ( ! in_array( $handle, $this->registered_scripts ) ) {
			$this->register_script( $handle, $src, $deps, $version, $footer );
		}
		$this->enqueued_scripts[] = $handle;
		wp_enqueue_script( $handle );
	}

	/**
	 *
	 * @param string $handle
	 * @param array  $data
	 * @param string $object_name
	 */
	public function localize_script( $handle, $data, $object_name = '' ) {
		$handle = $this->get_handle( $handle );
		if ( wp_script_is( $handle, 'registered' ) ) {
			$name = str_replace( $this->prefix, '', $handle );
			if ( ! $object_name ) {
				$object_name = str_replace( '-', '_', $handle ) . '_params';
			}
			if ( ! in_array( $object_name, $this->localized_data ) ) {
				$data = apply_filters( 'wc_stripe_localize_script_' . $name, $data, $object_name );
				if ( $data ) {
					$this->localized_scripts[] = $handle;
					$this->localized_data[]    = $object_name;
					wp_localize_script( $handle, $object_name, $data );
				}
			}
		}
	}

	public function get_handle( $handle ) {
		return strpos( $handle, $this->prefix ) === false ? $this->prefix . $handle : $handle;
	}

	/**
	 *
	 * @param string $uri
	 */
	public function assets_url( $uri = '' ) {
		// if minification scripts required, convert the uri to its min format.
		// don't minify scripts in the build directory
		if ( strpos( $uri, 'build/' ) !== 0 ) {
			$uri = ( ( $min = $this->get_min() ) ) ? preg_replace( '/([\w-]+)(\.(?<!min\.)(js|css))$/', '$1' . $min . '$2', $uri ) : $uri;
		}

		return untrailingslashit( stripe_wc()->assets_url( $uri ) );
	}

	public function get_min() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	private function get_page_id() {
		return wc_stripe_get_current_page();
	}

	public function print_footer_scripts() {
		if ( is_checkout() && ! isset( $wp->query_vars['order_pay'] ) && ! is_order_received_page() && ! did_action( 'wc_stripe_blocks_enqueue_styles' ) ) {
			$available_gateways = array_keys( WC()->payment_gateways()->get_available_payment_gateways() );
			$gateways           = array_filter( WC()->payment_gateways()->payment_gateways(), function ( $gateway ) use ( $available_gateways ) {
				return $gateway instanceof WC_Payment_Gateway_Stripe && $gateway->is_available() && ( ! in_array( $gateway->id, $available_gateways ) || ! $gateway->has_enqueued_scripts( $this ) );
			} );
			// If there are entries in the $gateways array that means some plugin filtered out the gateway.
			// It still needs to output its scripts
			foreach ( $gateways as $gateway ) {
				/**
				 * @var WC_Payment_Gateway_Stripe $gateway
				 */
				$gateway->enqueue_frontend_scripts( 'checkout' );
			}
		}
	}

}
