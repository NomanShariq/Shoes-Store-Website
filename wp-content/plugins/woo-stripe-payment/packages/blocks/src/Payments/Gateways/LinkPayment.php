<?php

namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;

use PaymentPlugins\Blocks\Stripe\Assets\Api;
use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripePayment;
use PaymentPlugins\Stripe\Controllers\PaymentIntent;
use PaymentPlugins\Stripe\Link\LinkIntegration;

class LinkPayment extends AbstractStripePayment {

	protected $name = 'stripe_link_checkout';

	private $link;

	/**
	 * @var \PaymentPlugins\Stripe\Controllers\PaymentIntent
	 */
	private $payment_intent_ctrl;

	/**
	 * @var Api
	 */
	private $assets;

	public function __construct( LinkIntegration $link, Api $assets ) {
		$this->link       = $link;
		$this->assets_api = $assets;
	}

	public function initialize() {
		add_filter( 'wc_stripe_blocks_general_data', [ $this, 'add_stripe_params' ] );
	}

	public function is_active() {
		return $this->link->is_active();
	}

	public function add_stripe_params( $data ) {
		if ( $this->link->is_active() ) {
			$data['stripeParams']['betas'][] = 'link_autofill_modal_beta_1';
		}

		return $data;
	}

	public function get_payment_method_data() {
		return [
			'name'            => $this->name,
			'launchLink'      => $this->link->is_autoload_enabled(),
			'linkIconEnabled' => $this->link->is_icon_enabled(),
			'linkIcon'        => $this->link->is_icon_enabled()
				? \wc_stripe_get_template_html( "link/link-icon-{$this->link->get_settings()->get_option('link_icon')}.php" )
				: null
		];
	}

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-stripe-blocks-link', 'build/wc-stripe-link-checkout.js' );

		return [ 'wc-stripe-blocks-link' ];
	}

	protected function is_express_checkout_enabled() {
		return true;
	}

	public function set_payment_intent_controller( PaymentIntent $controller ) {
		$this->payment_intent_ctrl = $controller;
	}

}