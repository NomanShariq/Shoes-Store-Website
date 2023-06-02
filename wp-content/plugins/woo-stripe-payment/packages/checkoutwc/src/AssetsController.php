<?php

namespace PaymentPlugins\CheckoutWC\Stripe;

class AssetsController {

	private $version;

	private $path;

	private $assets_path;

	public function __construct( $version, $assets_path ) {
		$this->version     = $version;
		$this->assets_path = $assets_path;
		$this->initialize();
	}

	private function initialize() {
		add_action( 'cfw_payment_request_buttons', [ $this, 'enqueue_styles' ] );
	}

	public function enqueue_styles() {
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof \WC_Payment_Gateway_Stripe ) {
				if ( $gateway->supports( 'wc_stripe_banner_checkout' ) && $gateway->banner_checkout_enabled() ) {
					wp_enqueue_style( 'wc-stripe-checkoutwc-style', $this->assets_path . 'build/styles.css', [], $this->version );
					break;
				}
			}
		}
	}

}