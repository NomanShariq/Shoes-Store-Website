<?php

namespace PaymentPlugins\Stripe\Messages\BNPL;

use PaymentPlugins\Stripe\Assets\AssetDataApi;

abstract class AbstractBNPLMessageController {

	protected $gateway_ids = [];

	protected $supported_gateways;

	protected $asset_data;

	public function __construct( $gateway_ids = array() ) {
		$this->gateway_ids = $gateway_ids;
		$this->asset_data  = new AssetDataApi();
		$this->initialize();
	}

	abstract protected function initialize();

	protected function get_supported_gateways() {
		if ( ! $this->supported_gateways ) {
			$payment_gateways         = WC()->payment_gateways()->payment_gateways();
			$ordering                 = (array) get_option( 'woocommerce_gateway_order' );
			$sort                     = 999;
			$this->supported_gateways = array_reduce( $this->gateway_ids, function ( $gateways, $id ) use ( $payment_gateways, $ordering, &$sort ) {
				$gateway = isset( $payment_gateways[ $id ] ) ? $payment_gateways[ $id ] : null;
				if ( $gateway && $gateway instanceof \WC_Payment_Gateway_Stripe_Local_Payment ) {
					if ( wc_string_to_bool( $gateway->get_option( 'enabled' ) ) ) {
						if ( \in_array( 'shop', $gateway->get_option( 'payment_sections' ) ) ) {
							if ( isset( $ordering[ $id ] ) && \is_numeric( $ordering[ $id ] ) ) {
								$gateways[ $ordering[ $id ] ] = $gateway;
							} else {
								$gateways[ $sort ] = $gateway;
								$sort ++;
							}
						}
					}
				}

				return $gateways;
			}, [] );

			ksort( $this->supported_gateways );
		}

		return $this->supported_gateways;
	}

}