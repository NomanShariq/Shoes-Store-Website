<?php

namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripePayment;

class GooglePayPayment extends AbstractStripePayment {

	protected $name = 'stripe_googlepay';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_external_script( 'wc-stripe-gpay-external', 'https://pay.google.com/gp/p/js/pay.js', array(), null );
		$this->assets_api->register_script( 'wc-stripe-blocks-googlepay', 'build/wc-stripe-googlepay.js', array( 'wc-stripe-gpay-external' ) );

		return array( 'wc-stripe-blocks-googlepay' );
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'icon'              => $this->get_payment_method_icon(),
			'editorIcons'       => array(
				'long'  => $this->assets_api->get_asset_url( 'assets/img/gpay_button_buy_black.svg' ),
				'short' => $this->assets_api->get_asset_url( 'assets/img/gpay_button_black.svg' )
			),
			'merchantId'        => $this->get_merchant_id(),
			'merchantName'      => $this->payment_method->get_option( 'merchant_name' ),
			'totalPriceLabel'   => __( 'Total', 'woo-stripe-payment' ),
			'buttonStyle'       => array(
				'buttonColor'    => $this->payment_method->get_option( 'button_color' ),
				'buttonType'     => $this->payment_method->get_option( 'button_style' ),
				'buttonSizeMode' => 'fill',
				'buttonLocale'   => $this->payment_method->get_payment_button_locale()
			),
			'buttonShape'       => $this->payment_method->get_option( 'button_shape', 'rect' ),
			'environment'       => $this->get_google_pay_environment(),
			'processingCountry' => WC()->countries ? WC()->countries->get_base_country() : wc_get_base_location()['country']
		), parent::get_payment_method_data() );
	}

	protected function get_payment_method_icon() {
		$icon = $this->payment_method->get_option( 'icon' );

		return array(
			'id'  => "{$this->name}_icon",
			'alt' => '',
			'src' => stripe_wc()->assets_url( "img/{$icon}.svg" )
		);
	}

	private function get_merchant_id() {
		return 'test' === wc_stripe_mode() ? '' : $this->payment_method->get_option( 'merchant_id' );
	}

	private function get_google_pay_environment() {
		return wc_stripe_mode() === 'test' ? 'TEST' : 'PRODUCTION';
	}

}