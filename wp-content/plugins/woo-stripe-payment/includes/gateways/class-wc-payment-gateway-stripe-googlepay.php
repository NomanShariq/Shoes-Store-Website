<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 *
 * @since   3.0.0
 * @author  PaymentPlugins
 * @package Stripe/Gateways
 */
class WC_Payment_Gateway_Stripe_GooglePay extends WC_Payment_Gateway_Stripe {

	use WC_Stripe_Payment_Intent_Trait;

	protected $payment_method_type = 'card';

	public function __construct() {
		$this->id                 = 'stripe_googlepay';
		$this->tab_title          = __( 'Google Pay', 'woo-stripe-payment' );
		$this->template_name      = 'googlepay.php';
		$this->token_type         = 'Stripe_GooglePay';
		$this->method_title       = __( 'Stripe Google Pay', 'woo-stripe-payment' );
		$this->method_description = __( 'Google Pay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->has_digital_wallet = true;
		parent::__construct();
		$this->icon = stripe_wc()->assets_url( 'img/' . $this->get_option( 'icon' ) . '.svg' );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wc_stripe_cart_checkout';
		$this->supports[] = 'wc_stripe_product_checkout';
		$this->supports[] = 'wc_stripe_banner_checkout';
		$this->supports[] = 'wc_stripe_mini_cart_checkout';
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-checkout',
			$scripts->assets_url( 'js/frontend/googlepay-checkout.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-checkout', $this->get_localized_params() );
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-product',
			$scripts->assets_url( 'js/frontend/googlepay-product.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-product', $this->get_localized_params() );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-cart',
			$scripts->assets_url( 'js/frontend/googlepay-cart.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-cart', $this->get_localized_params() );
	}

	public function enqueue_admin_scripts() {
		wp_register_script( 'gpay-external', stripe_wc()->scripts()->global_scripts['gpay'], array(), stripe_wc()->version(), true );
		wp_enqueue_script(
			'wc-stripe-gpay-admin',
			stripe_wc()->assets_url( 'js/admin/googlepay.js' ),
			array(
				'gpay-external',
				'wc-stripe-admin-settings',
			),
			stripe_wc()->version(),
			true
		);
	}

	public function get_localized_params() {
		$data = array_merge_recursive(
			parent::get_localized_params(),
			array(
				'environment'        => wc_stripe_mode() === 'test' ? 'TEST' : 'PRODUCTION',
				'merchant_id'        => wc_stripe_mode() === 'test' ? '' : $this->get_option( 'merchant_id' ),
				'merchant_name'      => $this->get_option( 'merchant_name' ),
				'processing_country' => WC()->countries ? WC()->countries->get_base_country() : wc_get_base_location()['country'],
				'button_color'       => 'black',
				'button_style'       => $this->get_option( 'button_style' ),
				'button_size_mode'   => 'fill',
				'button_locale'      => $this->get_payment_button_locale(),
				'button_shape'       => $this->get_option( 'button_shape', 'rect' ),
				'total_price_label'  => __( 'Total', 'woo-stripe-payment' ),
				'routes'             => array( 'payment_data' => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->googlepay->rest_uri( 'shipping-data' ) ) ),
				'messages'           => array( 'invalid_amount' => __( 'Please update you product quantity before using Google Pay.', 'woo-stripe-payment' ) )
			)
		);

		return $data;
	}

	protected function get_display_item_for_cart( $price, $label, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => wc_format_decimal( $price, 2 )
		);
	}

	protected function get_display_item_for_product( $product ) {
		return array(
			'label' => esc_attr( $product->get_name() ),
			'type'  => 'SUBTOTAL',
			'price' => wc_format_decimal( $product->get_price(), 2 )
		);
	}

	protected function get_display_item_for_order( $price, $label, $order, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => wc_format_decimal( $price, 2 )
		);
	}

	public function get_formatted_shipping_methods( $methods = array() ) {
		$methods = parent::get_formatted_shipping_methods( $methods );
		if ( empty( $methods ) ) {
			// GPay does not like empty shipping methods. Make a temporary one;
			$methods[] = array(
				'id'          => 'default',
				'label'       => __( 'Waiting...', 'woo-stripe-payment' ),
				'description' => __( 'loading shipping methods...', 'woo-stripe-payment' ),
			);
		}

		return $methods;
	}

	public function get_formatted_shipping_method( $price, $rate, $i, $package, $incl_tax ) {
		return array(
			'id'          => $this->get_shipping_method_id( $rate->id, $i ),
			'label'       => $this->get_formatted_shipping_label( $price, $rate, $incl_tax ),
			'description' => ''
		);
	}

	/**
	 * @param float            $price
	 * @param WC_Shipping_Rate $rate
	 * @param bool             $incl_tax
	 *
	 * @return string|void
	 */
	protected function get_formatted_shipping_label( $price, $rate, $incl_tax ) {
		$label = sprintf( '%s: %s %s', esc_attr( $rate->get_label() ), number_format( $price, 2 ), get_woocommerce_currency() );
		if ( $incl_tax ) {
			if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->inc_tax_or_vat();
			}
		} else {
			if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->ex_tax_or_vat();
			}
		}

		return $label;
	}

	/**
	 * Return a formatted shipping method label.
	 * <strong>Example</strong>&nbsp;5 Day shipping: 5 USD
	 *
	 * @param WC_Shipping_Rate $rate
	 *
	 * @return
	 * @deprecated
	 *
	 */
	public function get_shipping_method_label( $rate ) {
		$incl_tax = wc_stripe_display_prices_including_tax();
		$price    = $incl_tax ? $rate->cost + $rate->get_shipping_tax() : $rate->cost;

		return $this->get_formatted_shipping_label( $price, $rate, $incl_tax );
	}

	/**
	 * @param array $deps
	 * @param       $scripts
	 *
	 * @return array
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$deps[] = $scripts->get_handle( 'gpay' );
		}

		return $deps;
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'googlepay-checkout' ) );
	}

	/**
	 * @since 3.3.14
	 * @return mixed|void
	 */
	public function get_payment_button_locale() {
		$locale        = wc_stripe_get_site_locale();
		$button_locale = null;
		if ( 'auto' !== $locale ) {
			$button_locale = substr( $locale, 0, 2 );
			if ( ! in_array( $button_locale, $this->get_supported_button_locales() ) ) {
				$button_locale = null;
			}
		}

		return apply_filters( 'wc_stripe_googlepay_get_button_locale', $button_locale, $this );
	}

	/**
	 * @since 3.3.14
	 * @return mixed|void
	 */
	public function get_supported_button_locales() {
		return apply_filters( 'wc_stripe_googlepay_supported_button_locales',
			array(
				'en',
				'ar',
				'bg',
				'ca',
				'cs',
				'da',
				'de',
				'el',
				'es',
				'et',
				'fi',
				'fr',
				'hr',
				'id',
				'it',
				'ja',
				'ko',
				'ms',
				'nl',
				'no',
				'pl',
				'pt',
				'ru',
				'sk',
				'sl',
				'sr',
				'sv',
				'th',
				'tr',
				'uk',
				'zh'
			)
		);
	}

}
