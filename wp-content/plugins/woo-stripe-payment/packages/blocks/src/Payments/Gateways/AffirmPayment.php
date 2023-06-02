<?php

namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;

class AffirmPayment extends \PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment {

	protected $name = 'stripe_affirm';

	public function get_payment_method_data() {
		$data = parent::get_payment_method_data();
		if ( WC()->cart ) {
			$currency = get_woocommerce_currency();
			$data     = array_merge( $data, [
				'cartTotals'     => [
					'value' => wc_stripe_add_number_precision( (float) WC()->cart->total, $currency )
				],
				'currency'       => $currency,
				'messageOptions' => [
					'logoColor' => $this->get_setting( "checkout_logo_color", 'primary' ),
					'fontColor' => $this->get_setting( "checkout_font_color", 'black' ),
					'fontSize'  => $this->get_setting( "checkout_font_size", '1em' ),
					'textAlign' => $this->get_setting( "checkout_text_align", 'start' ),
				]
			] );
		}

		return $data;
	}

}