<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

class WC_Payment_Gateway_Stripe_Affirm extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'affirm';

	public $max_amount = 30001;

	public function __construct() {
		$this->local_payment_type = 'affirm';
		$this->currencies         = array( 'USD' );
		$this->countries          = array( 'US' );
		$this->limited_countries  = array( 'US' );
		$this->id                 = 'stripe_affirm';
		$this->tab_title          = __( 'Affirm', 'woo-stripe-payment' );
		$this->method_title       = __( 'Affirm', 'woo-stripe-payment' );
		$this->method_description = __( 'Affirm gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/affirm.svg' );
		parent::__construct();
		$this->template_name = 'affirm.php';
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wc_stripe_cart_checkout';
		$this->supports[] = 'wc_stripe_product_checkout';
		$this->supports[] = 'wc_stripe_mini_cart_checkout';
	}

	public function get_order_button_text( $text ) {
		return __( 'Complete Order', 'woo-stripe-payment' );
	}

	public function is_local_payment_available() {
		if ( parent::is_local_payment_available() ) {
			return WC()->cart && $this->get_order_total() >= 50;
		}

		return false;
	}

	public function cart_fields() {
		$this->enqueue_frontend_scripts( 'cart' );
		$this->output_display_items( 'cart' );
	}

	public function product_fields() {
		$this->enqueue_frontend_scripts( 'product' );
		$this->output_display_items( 'product' );
	}

	public function enqueue_checkout_scripts( $scripts ) {
		parent::enqueue_checkout_scripts( $scripts );
		$scripts->assets_api->register_script( 'wc-stripe-affirm-checkout', 'assets/build/affirm-messaging.js' );
		wp_enqueue_script( 'wc-stripe-affirm-checkout' );
	}

	/**
	 * @param \WC_Stripe_Frontend_Scripts $scripts
	 *
	 * @return void
	 */
	public function enqueue_cart_scripts( $scripts ) {
		$scripts->assets_api->register_script( 'wc-stripe-affirm-cart', 'assets/build/affirm-messaging.js' );
		wp_enqueue_script( 'wc-stripe-affirm-cart' );
		$this->enqueue_payment_method_styles();
		$scripts->localize_script( 'wc-stripe-affirm-cart', $this->get_localized_params( 'cart' ) );
	}

	/**
	 * @param \WC_Stripe_Frontend_Scripts $scripts
	 *
	 * @return void
	 */
	public function enqueue_product_scripts( $scripts ) {
		$scripts->assets_api->register_script( 'wc-stripe-affirm-product', 'assets/build/affirm-messaging.js' );
		wp_enqueue_script( 'wc-stripe-affirm-product' );
		$scripts->localize_script( 'wc-stripe-affirm-product', $this->get_localized_params( 'product' ) );
	}

	/**
	 * @param \PaymentPlugins\Stripe\Assets\AssetsApi    $assets_api
	 * @param \PaymentPlugins\Stripe\Assets\AssetDataApi $asset_data
	 *
	 * @return void
	 */
	public function enqueue_category_scripts( $assets_api, $asset_data ) {
		$assets_api->register_script( 'wc-stripe-affirm-category', 'assets/build/affirm-messaging.js' );
		$asset_data->add( $this->id, array(
			'messageOptions'      => array(
				'logoColor' => $this->get_option( "shop_logo_color", 'primary' ),
				'fontColor' => $this->get_option( "shop_font_color", 'black' ),
				'fontSize'  => $this->get_option( "shop_font_size", '1em' ),
				'textAlign' => $this->get_option( "shop_text_align", 'start' ),
			),
			'supportedCurrencies' => $this->currencies
		) );
		wp_enqueue_script( 'wc-stripe-affirm-category' );
	}

	public function get_local_payment_settings() {
		return array_merge( parent::get_local_payment_settings(), array(
			'charge_type'         => array(
				'type'        => 'select',
				'title'       => __( 'Charge Type', 'woo-stripe-payment' ),
				'default'     => 'capture',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'capture'   => __( 'Capture', 'woo-stripe-payment' ),
					'authorize' => __( 'Authorize', 'woo-stripe-payment' ),
				),
				'desc_tip'    => true,
				'description' => __( 'This option determines whether the customer\'s funds are captured immediately or authorized and can be captured at a later date.',
					'woo-stripe-payment' ),
			),
			'payment_sections'    => array(
				'type'        => 'multiselect',
				'title'       => __( 'Payment Sections', 'woo-stripe-payment' ),
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'product' => __( 'Product Page', 'woo-stripe-payment' ),
					'cart'    => __( 'Cart Page', 'woo-stripe-payment' ),
					'shop'    => __( 'Shop/Category Page', 'woo-stripe-payment' )
				),
				'default'     => array( 'cart' ),
				'desc_tip'    => true,
				'description' => __( 'These are the sections where the Affirm messaging will be enabled.',
					'woo-stripe-payment' ),
			),
			'checkout_styling'    => array(
				'type'  => 'title',
				'title' => __( 'Checkout Message Styling', 'woo-stripe-payments' )
			),
			'checkout_logo_color' => array(
				'title'       => __( 'Logo Color', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'primary',
				'options'     => array(
					'primary' => __( 'Primary', 'woo-stripe-payment' ),
					'black'   => __( 'Black', 'woo-stripe-payment' ),
					'white'   => __( 'White', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm logo that appears in the messaging.', 'woo-stripe-payment' )
			),
			'checkout_font_color' => array(
				'title'       => __( 'Font Color', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => 'black',
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm message font. Valid values are hex color codes or values like red, blue, green, etc.', 'woo-stripe-payment' )
			),
			'checkout_font_size'  => array(
				'title'       => __( 'Font Size', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '1em',
				'desc_tip'    => true,
				'description' => __( 'The size of the Affirm message font. Valid values are in px, em, rem', 'woo-stripe-payment' )
			),
			'checkout_text_align' => array(
				'title'       => __( 'Font Alignment', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'start',
				'options'     => array(
					'start'  => __( 'Start', 'woo-stripe-payment' ),
					'end'    => __( 'End', 'woo-stripe-payment' ),
					'center' => __( 'Center', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The alignment of the Affirm message.', 'woo-stripe-payment' )
			),
			'cart_styling'        => array(
				'type'  => 'title',
				'title' => __( 'Cart Message Styling', 'woo-stripe-payments' )
			),
			'cart_logo_color'     => array(
				'title'       => __( 'Logo Color', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'primary',
				'options'     => array(
					'primary' => __( 'Primary', 'woo-stripe-payment' ),
					'black'   => __( 'Black', 'woo-stripe-payment' ),
					'white'   => __( 'White', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm logo that appears in the messaging.', 'woo-stripe-payment' )
			),
			'cart_font_color'     => array(
				'title'       => __( 'Font Color', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => 'black',
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm message font. Valid values are hex color codes or values like red, blue, green, etc.', 'woo-stripe-payment' )
			),
			'cart_font_size'      => array(
				'title'       => __( 'Font Size', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '1em',
				'desc_tip'    => true,
				'description' => __( 'The size of the Affirm message font. Valid values are in px, em, rem', 'woo-stripe-payment' )
			),
			'cart_text_align'     => array(
				'title'       => __( 'Font Alignment', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'start',
				'options'     => array(
					'start'  => __( 'Start', 'woo-stripe-payment' ),
					'end'    => __( 'End', 'woo-stripe-payment' ),
					'center' => __( 'Center', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The alignment of the Affirm message.', 'woo-stripe-payment' )
			),
			'cart_location'       => array(
				'title'       => __( 'Message Location', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'below_total',
				'options'     => array(
					'below_total'           => __( 'Below Total', 'woo-stripe-payment' ),
					'below_checkout_button' => __( 'Below Checkout Button', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This option controls the location in which the messaging for the payment method will appear.', 'woo-stripe-payment' )
			),
			'product_styling'     => array(
				'type'  => 'title',
				'title' => __( 'Product Message Styling', 'woo-stripe-payments' )
			),
			'product_logo_color'  => array(
				'title'       => __( 'Logo Color', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'primary',
				'options'     => array(
					'primary' => __( 'Primary', 'woo-stripe-payment' ),
					'black'   => __( 'Black', 'woo-stripe-payment' ),
					'white'   => __( 'White', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm logo that appears in the messaging.', 'woo-stripe-payment' )
			),
			'product_font_color'  => array(
				'title'       => __( 'Font Color', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => 'black',
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm message font. Valid values are hex color codes or values like red, blue, green, etc.', 'woo-stripe-payment' )
			),
			'product_font_size'   => array(
				'title'       => __( 'Font Size', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '1em',
				'desc_tip'    => true,
				'description' => __( 'The size of the Affirm message font. Valid values are in px, em, rem', 'woo-stripe-payment' )
			),
			'product_text_align'  => array(
				'title'       => __( 'Font Alignment', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'start',
				'options'     => array(
					'start'  => __( 'Start', 'woo-stripe-payment' ),
					'end'    => __( 'End', 'woo-stripe-payment' ),
					'center' => __( 'Center', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The alignment of the Affirm message.', 'woo-stripe-payment' )
			),
			'product_location'    => array(
				'title'       => __( 'Message Location', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'below_price',
				'options'     => array(
					'above_price'       => __( 'Above Price', 'woo-stripe-payment' ),
					'below_price'       => __( 'Below Price', 'woo-stripe-payment' ),
					'below_add_to_cart' => __( 'Below Add to Cart', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This option controls the location in which the messaging for the payment method will appear.', 'woo-stripe-payment' )
			),
			'shop_styling'        => array(
				'type'  => 'title',
				'title' => __( 'Shop/Category Message Styling', 'woo-stripe-payments' )
			),
			'shop_logo_color'     => array(
				'title'       => __( 'Logo Color', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'primary',
				'options'     => array(
					'primary' => __( 'Primary', 'woo-stripe-payment' ),
					'black'   => __( 'Black', 'woo-stripe-payment' ),
					'white'   => __( 'White', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm logo that appears in the messaging.', 'woo-stripe-payment' )
			),
			'shop_font_color'     => array(
				'title'       => __( 'Font Color', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => 'black',
				'desc_tip'    => true,
				'description' => __( 'The color of the Affirm message font. Valid values are hex color codes or values like red, blue, green, etc.', 'woo-stripe-payment' )
			),
			'shop_font_size'      => array(
				'title'       => __( 'Font Size', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '1em',
				'desc_tip'    => true,
				'description' => __( 'The size of the Affirm message font. Valid values are in px, em, rem', 'woo-stripe-payment' )
			),
			'shop_text_align'     => array(
				'title'       => __( 'Font Alignment', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'start',
				'options'     => array(
					'start'  => __( 'Start', 'woo-stripe-payment' ),
					'end'    => __( 'End', 'woo-stripe-payment' ),
					'center' => __( 'Center', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'The alignment of the Affirm message.', 'woo-stripe-payment' )
			),
			'shop_location'       => array(
				'title'       => __( 'Shop/Category Location', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'below_price',
				'options'     => array(
					'below_price'       => __( 'Below Price', 'woo-stripe-payment' ),
					'below_add_to_cart' => __( 'Below Add to Cart', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This option controls the location in which the messaging for the payment method will appear.', 'woo-stripe-payment' )
			),
		) );
	}

	public function get_icon() {
		return '<div id="wc-stripe-affirm-message-container"></div>';
	}

	public function get_localized_params( $context = 'checkout' ) {
		return array_merge( parent::get_localized_params(), array(
			'messageOptions'      => array(
				'logoColor' => $this->get_option( "{$context}_logo_color", 'primary' ),
				'fontColor' => $this->get_option( "{$context}_font_color", 'black' ),
				'fontSize'  => $this->get_option( "{$context}_font_size", '1em' ),
				'textAlign' => $this->get_option( "{$context}_text_align", 'start' ),
			),
			'supportedCurrencies' => $this->currencies
		) );
	}

	public function get_payment_description() {
		$desc = parent::get_payment_description();

		return $desc . ' ' . sprintf( __( 'and cart/product total is between %1$s and %2$s.', 'woo-stripe-payment' ),
				wc_price( 50, array( 'currency' => 'USD' ) ),
				wc_price( 30000, array( 'currency' => 'USD' ) ) );
	}

}