<?php


namespace PaymentPlugins\WooFunnels\Stripe\Upsell\PaymentGateways;


class CreditCardGateway extends BasePaymentGateway {

	protected $key = 'stripe_cc';

	public function initialize() {
		add_filter( 'wc_stripe_cc_show_save_source', [ $this, 'show_save_source' ] );
	}

	public function show_save_source( $bool ) {
		if ( $bool ) {
			$bool = ! $this->should_tokenize();
		}

		return $bool;
	}
}