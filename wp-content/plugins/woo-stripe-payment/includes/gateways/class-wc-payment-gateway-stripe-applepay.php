<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author PaymentPlugins
 *
 */
class WC_Payment_Gateway_Stripe_ApplePay extends WC_Payment_Gateway_Stripe {

	use WC_Stripe_Payment_Intent_Trait;

	protected $payment_method_type = 'card';

	public function __construct() {
		$this->id                 = 'stripe_applepay';
		$this->tab_title          = __( 'Apple Pay', 'woo-stripe-payment' );
		$this->template_name      = 'applepay.php';
		$this->token_type         = 'Stripe_ApplePay';
		$this->method_title       = __( 'Stripe Apple Pay', 'woo-stripe-payment' );
		$this->method_description = __( 'Apple Pay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->has_digital_wallet = true;
		parent::__construct();
		$this->icon = stripe_wc()->assets_url( 'img/applepay.svg' );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wc_stripe_cart_checkout';
		$this->supports[] = 'wc_stripe_product_checkout';
		$this->supports[] = 'wc_stripe_banner_checkout';
		$this->supports[] = 'wc_stripe_mini_cart_checkout';
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-product',
			$scripts->assets_url( 'js/frontend/applepay-product.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' )
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'applepay-product', $this->get_localized_params() );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-cart',
			$scripts->assets_url( 'js/frontend/applepay-cart.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' )
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'applepay-cart', $this->get_localized_params() );
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-checkout',
			$scripts->assets_url( 'js/frontend/applepay-checkout.js' ),
			array(
				$scripts->get_handle( 'wc-stripe' )
			),
			stripe_wc()->version(),
			true
		);
		$scripts->localize_script( 'applepay-checkout', $this->get_localized_params() );
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'messages' => array(
					'invalid_amount' => __( 'Please update you product quantity before using Apple Pay.', 'woo-stripe-payment' ),
					'choose_product' => __( 'Please select a product option before updating quantity.', 'woo-stripe-payment' ),
				),
				'button'   => wc_stripe_get_template_html(
					'applepay-button.php',
					array(
						'style'       => $this->get_option( 'button_style' ),
						'type'        => $this->get_button_type(),
						'button_type' => $this->get_applepay_button_style_type()
					)
				),
			)
		);
	}

	/**
	 * Returns the Apple Pay button type based on the current page.
	 *
	 * @return string
	 */
	protected function get_button_type() {
		if ( is_checkout() ) {
			return $this->get_option( 'button_type_checkout' );
		}
		if ( is_cart() ) {
			return $this->get_option( 'button_type_cart' );
		}
		if ( is_product() ) {
			return $this->get_option( 'button_type_product' );
		}
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'applepay-checkout' ) );
	}

	private function get_applepay_button_style_type() {
		$style = $this->get_option( 'button_style' );
		switch ( $style ) {
			case 'apple-pay-button-white':
				return 'white';
			case 'apple-pay-button-white-with-line':
				return 'white-outline';
			default:
				return 'black';
		}
	}
}
