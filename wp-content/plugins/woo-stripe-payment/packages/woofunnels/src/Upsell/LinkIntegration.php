<?php

namespace PaymentPlugins\WooFunnels\Stripe\Upsell;

class LinkIntegration {

	public function __construct() {
		add_filter( 'wc_stripe_funnelkit_upsell_create_payment_intent', [ $this, 'add_payment_intent_params' ], 10, 3 );
	}

	/**
	 * @param array              $params
	 * @param \WC_Order          $order
	 * @param \WC_Stripe_Gateway $client
	 *
	 * @return void
	 */
	public function add_payment_intent_params( $params, $order, $client ) {
		if ( $order->get_payment_method() === 'stripe_cc' ) {
			if ( \PaymentPlugins\Stripe\Link\LinkIntegration::instance()->is_active() ) {
				$payment_intent = $client->mode( $order )->paymentIntents->retrieve( $order->get_meta( \WC_Stripe_Constants::PAYMENT_INTENT_ID ) );
				if ( ! is_wp_error( $payment_intent ) ) {
					$params['payment_method_types'] = array_values( array_unique( array_merge( $params['payment_method_types'], $payment_intent->payment_method_types ) ) );
				}
			}
		}

		return $params;
	}

}