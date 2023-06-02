<?php

namespace PaymentPlugins\Blocks\Stripe\Payments;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use \PaymentPlugins\Blocks\Stripe\Assets\Api as AssetsApi;

/**
 * @property \WC_Payment_Gateway_Stripe $payment_method
 */
abstract class AbstractStripePayment extends AbstractPaymentMethodType {

	protected $assets_api;

	/**
	 * @var \WC_Payment_Gateway_Stripe
	 */
	protected $payment_gateway;

	public function __construct( AssetsApi $assets_api ) {
		$this->assets_api = $assets_api;
		$this->init();
	}

	public function __get( $key ) {
		switch ( $key ) {
			case 'payment_method':
				return $this->payment_method();
		}
	}

	protected function payment_method() {
		if ( ! $this->payment_gateway ) {
			$payment_methods = WC()->payment_gateways()->payment_gateways();

			$this->payment_gateway = isset( $payment_methods[ $this->get_name() ] ) ? $payment_methods[ $this->get_name() ] : null;
			/**
			 * It's possible that some 3rd party code has unset the payment gateway using the
			 * woocommerce_payment_gateways filter. To prevent null exceptions, ensure this variable
			 * is never null
			 */
			if ( ! $this->payment_gateway ) {
				$this->payment_gateway = new MagicPaymentMethod( $this->get_name() );
			}
		}

		return $this->payment_gateway;
	}

	protected function init() {
		add_filter( 'woocommerce_saved_payment_methods_list', array( $this, 'transform_payment_method_type' ), 99 );
	}

	public function initialize() {
		$this->settings = get_option( "woocommerce_{$this->name}_settings", [] );
	}


	public function is_active() {
		return wc_string_to_bool( $this->get_setting( 'enabled', 'no' ) );
	}

	public function get_payment_method_script_handles() {
		return array();
	}

	public function get_payment_method_data() {
		return array(
			'name'                  => $this->get_name(),
			'title'                 => $this->get_setting( 'title_text' ),
			'showSaveOption'        => \in_array( 'tokenization', $this->get_supported_features() ),
			'showSavedCards'        => \in_array( 'tokenization', $this->get_supported_features() ),
			'features'              => $this->get_supported_features(),
			'expressCheckout'       => $this->is_express_checkout_enabled(),
			'cartCheckoutEnabled'   => $this->is_cart_checkout_enabled(),
			'countryCode'           => wc_get_base_location()['country'],
			'totalLabel'            => __( 'Total', 'woo-stripe-payment' ),
			'isAdmin'               => is_admin(),
			'icons'                 => $this->get_payment_method_icon(),
			'placeOrderButtonLabel' => \esc_html( $this->get_setting( 'order_button_text' ) ),
			'description'           => $this->get_setting( 'description' )
		);
	}

	public function get_supported_features() {
		return $this->payment_method->supports;
	}

	/**
	 * Blocks only recognize payment tokens of type 'cc' therefore it's necessary to map
	 * the 'stripe_cc' list entry to 'cc'.
	 *
	 * @param $list
	 *
	 * @return mixed
	 */
	public function transform_payment_method_type( $list ) {
		if ( isset( $list[ $this->get_name() ] ) ) {
			if ( isset( $list['cc'] ) ) {
				foreach ( $list[ $this->get_name() ] as $entry ) {
					$list['cc'][] = $entry;
				}
			} else {
				$list['cc'] = $list[ $this->get_name() ];
			}
			unset( $list[ $this->get_name() ] );
		}

		return $list;
	}

	/**
	 * Return true if the express checkout option is enabled for the payment method.
	 *
	 * @return bool
	 */
	protected function is_express_checkout_enabled() {
		return \in_array( 'checkout_banner', $this->get_setting( 'payment_sections', [] ), true );
	}

	protected function is_cart_checkout_enabled() {
		return \in_array( 'cart', $this->get_setting( 'payment_sections', [] ), true );
	}

	/**
	 * @param \PaymentPlugins\Blocks\Stripe\Assets\Api $style_api
	 */
	public function enqueue_payment_method_styles( $style_api ) {
	}

	protected function get_payment_method_icon() {
		return array();
	}

	public function get_endpoint_data() {
		return [];
	}

}