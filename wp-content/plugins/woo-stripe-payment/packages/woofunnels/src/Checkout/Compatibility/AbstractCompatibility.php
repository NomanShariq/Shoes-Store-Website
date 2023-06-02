<?php

namespace PaymentPlugins\WooFunnels\Stripe\Checkout\Compatibility;

abstract class AbstractCompatibility {

	private $settings;

	protected $id;

	/**
	 * @var \WC_Payment_Gateway_Stripe
	 */
	protected $payment_gateway;

	public function __construct( $payment_gateway ) {
		$this->payment_gateway = $payment_gateway;
		$this->initialize();
	}

	protected function initialize() {
	}

	public function handle_checkout_page_found() {
		$this->settings = \WFACP_Common::get_page_settings( \WFACP_Common::get_id() );
	}

	public function get_payment_gateway() {
		return $this->payment_gateway;
	}

	public function is_enabled() {
		return wc_string_to_bool( $this->payment_gateway->get_option( 'enabled' ) );
	}

	/**
	 * @return false
	 */
	public function is_express_enabled() {
		return $this->payment_gateway->banner_checkout_enabled();
	}

	public function render_express_button() {
	}

}