<?php

namespace PaymentPlugins\Stripe\Messages\BNPL;

class CategoryMessageController extends AbstractBNPLMessageController {

	protected function initialize() {
		add_action( 'woocommerce_before_shop_loop', [ $this, 'initialize_loop' ] );
		add_action( 'woocommerce_shop_loop', [ $this, 'add_product_data' ] );
		add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'render_after_price' ], 20 );
		add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_after_add_to_cart' ], 15 );
		add_action( 'woocommerce_after_shop_loop', [ $this, 'end_loop' ] );
	}

	public function initialize_loop() {
		$this->supported_gateways = $this->get_supported_gateways();
		$this->asset_data->add( 'currency', get_woocommerce_currency() );
		$this->asset_data->add( 'products', [] );
		$this->asset_data->add( 'product_types', [ 'simple', 'variable', 'group' ] );
	}

	public function end_loop() {
		if ( $this->supported_gateways ) {
			$this->enqueue_scripts();
			if ( $this->asset_data->has_data() ) {
				$this->asset_data->print_data( 'wc_stripe_bnpl_shop_params', $this->asset_data->get_data() );
			}
		}
	}

	private function enqueue_scripts() {
		foreach ( $this->supported_gateways as $gateway ) {
			$gateway->enqueue_category_scripts( stripe_wc()->assets(), $this->asset_data );
		}
	}

	public function add_product_data() {
		global $product;
		if ( $product && ! empty( $this->supported_gateways ) ) {
			$data   = $this->asset_data->get( 'products' );
			$price  = wc_get_price_to_display( $product );
			$data[] = [
				'id'           => $product->get_id(),
				'price'        => $price,
				'price_cents'  => wc_stripe_add_number_precision( $price ),
				'product_type' => $product->get_type()
			];
			$this->asset_data->add( 'products', $data );
		}
	}

	public function render_after_price() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'shop_location' ) === 'below_price';
		} );
		if ( $gateways ) {
			$this->render( $gateways );
		}
	}

	public function render_after_add_to_cart() {
		$gateways = array_filter( $this->get_supported_gateways(), function ( $gateway ) {
			return $gateway->get_option( 'shop_location' ) === 'below_add_to_cart';
		} );
		if ( $gateways ) {
			$this->render( $gateways );
		}
	}

	public function render( $gateways ) {
		global $product;
		if ( $product && $gateways ) {
			foreach ( $gateways as $gateway ) {
				$gateway->enqueue_payment_method_styles();
				$id = $gateway->id . '-' . $product->get_id();
				?>
                <div class="wc-stripe-shop-message-container <?php echo $gateway->id ?>" id="wc-stripe-shop-message-<?php echo $id ?>"></div>
				<?php
			}
		}
	}

}