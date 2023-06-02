<?php

namespace PaymentPlugins\Stripe\Messages\BNPL;

class CartMessageController extends AbstractBNPLMessageController {

	protected function initialize() {
		add_action( 'woocommerce_cart_totals_after_order_total', [ $this, 'render_after_order_total' ] );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_after_checkout_button' ], 21 );
	}

	public function render_after_order_total() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'cart_location' ) === 'below_total';
		} );
		if ( $gateways ) {
			foreach ( $gateways as $gateway ) {
				$id = str_replace( '_', '-', $gateway->id );
				?>
                <tr id="wc-<?php echo $id ?>-cart-container" class="<?php echo $gateway->id ?>-cart-message-container">
                    <td colspan="2">
                        <div id="wc-<?php echo $id ?>-cart-msg"></div>
                    </td>
                </tr>
				<?php
			}
		}
	}

	public function render_after_checkout_button() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'cart_location' ) === 'below_checkout_button';
		} );
		if ( $gateways ) {
			foreach ( $gateways as $gateway ) {
				$id = str_replace( '_', '-', $gateway->id );
				?>
                <div id="wc-<?php echo $id ?>-cart-container" class="<?php echo $gateway->id ?>-cart-message-container">
                    <div id="wc-<?php echo $id ?>-cart-msg"></div>
                </div>
				<?php
			}
		}
	}

}