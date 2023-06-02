<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Stripe_Shortcodes
 * @since 3.2.15
 */
class WC_Stripe_Shortcodes {

	public static function init() {
		$shortcodes = array(
			'wc_stripe_payment_buttons' => array( 'WC_Stripe_Shortcodes', 'payment_buttons' ),
		);

		foreach ( $shortcodes as $key => $function ) {
			add_shortcode( $key, apply_filters( 'wc_stripe_shortcode_function', $function ) );
		}
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	public static function payment_buttons( $atts ) {
		$method  = '';
		$wrapper = array(
			'class' => 'wc-stripe-shortcode'
		);
		if ( is_product() ) {
			$method           = 'output_product_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wc-stripe-shortcode-product-buttons';
		} else if ( ! is_null( WC()->cart ) && ( is_cart() || ( isset( $atts['page'] ) && 'cart' === $atts['page'] ) ) ) {
			$method           = 'output_cart_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wc-stripe-shortcode-cart-buttons';
		}
		if ( ! $method ) {
			return '';
		}
		include_once stripe_wc()->plugin_path() . 'includes/shortcodes/class-wc-stripe-shortcode-payment-buttons.php';

		return WC_Shortcodes::shortcode_wrapper( array( 'WC_Stripe_Shortcode_Payment_Buttons', $method ), $atts, $wrapper );
	}
}

WC_Stripe_Shortcodes::init();