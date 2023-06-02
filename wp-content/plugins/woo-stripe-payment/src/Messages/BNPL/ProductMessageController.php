<?php

namespace PaymentPlugins\Stripe\Messages\BNPL;

use PaymentPlugins\Stripe\Assets\AssetDataApi;

class ProductMessageController extends AbstractBNPLMessageController {


	protected function initialize() {
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_above_price' ], 8 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_below_price' ], 15 );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'render_below_add_to_cart' ], 5 );
	}

	public function render_above_price() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'product_location' ) === 'above_price';
		} );
		if ( $gateways ) {
			$this->render( $gateways );
		}
	}

	public function render_below_price() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'product_location' ) === 'below_price';
		} );
		if ( $gateways ) {
			$this->render( $gateways );
		}
	}

	public function render_below_add_to_cart() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'product_location' ) === 'below_add_to_cart';
		} );
		if ( $gateways ) {
			$this->render( $gateways );
		}
	}

	public function render( $gateways ) {
		foreach ( $gateways as $gateway ) {
			$id = str_replace( '_', '-', $gateway->id );
			?>
            <div id="wc-<?php echo $id ?>-product-msg" class="<?php echo $gateway->id ?>-product-message wc-stripe-bnpl-product-message"></div>
			<?php
		}
	}

}