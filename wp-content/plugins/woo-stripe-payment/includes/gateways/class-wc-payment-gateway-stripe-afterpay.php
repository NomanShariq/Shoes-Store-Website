<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 * Class WC_Payment_Gateway_Stripe_Afterpay
 *
 * @since   3.3.1
 * @package Stripe/Gateways
 */
class WC_Payment_Gateway_Stripe_Afterpay extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'afterpay_clearpay';

	public function __construct() {
		$this->local_payment_type = 'afterpay_clearpay';
		$this->currencies         = array( 'AUD', 'CAD', 'NZD', 'GBP', 'USD', 'EUR' );
		$this->countries          = array( 'AU', 'CA', 'NZ', 'GB', 'US', 'FR', 'ES' );
		$this->id                 = 'stripe_afterpay';
		$this->tab_title          = __( 'Afterpay', 'woo-stripe-payment' );
		$this->method_title       = __( 'Afterpay', 'woo-stripe-payment' );
		$this->method_description = __( 'Afterpay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = '';
		parent::__construct();
		$this->template_name = 'afterpay.php';
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

	public function get_local_payment_settings() {
		$settings = wp_parse_args( array(
			'charge_type'                 => array(
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
			'payment_sections'            => array(
				'type'        => 'multiselect',
				'title'       => __( 'Payment Sections', 'woo-stripe-payment' ),
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'product'   => __( 'Product Page', 'woo-stripe-payment' ),
					'cart'      => __( 'Cart Page', 'woo-stripe-payment' ),
					'mini_cart' => __( 'Mini Cart', 'woo-stripe-payment' ),
					'shop'      => __( 'Shop/Category Page', 'woo-stripe-payment' )
				),
				'default'     => array( 'product', 'cart' ),
				'description' => __( 'These are the additional sections where the Afterpay messaging will be enabled. You can control individual products via the Edit product page.',
					'woo-stripe-payment' ),
			),
			'hide_ineligible'             => array(
				'title'       => __( 'Hide If Ineligible', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'value'       => 'yes',
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'If enabled, Afterpay won\'t show when the products in the cart are not eligible.', 'woo-stripe-payment' )
			),
			'checkout_styling'            => array(
				'type'  => 'title',
				'title' => __( 'Checkout Page Styling', 'woo-stripe-payments' )
			),
			'icon_checkout'               => array(
				'title'       => __( 'Icon', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'black-on-mint',
				'options'     => array(
					'black-on-mint'  => __( 'Black on mint', 'woo-stripe-payment' ),
					'black-on-white' => __( 'Black on white', 'woo-stripe-payment' ),
					'mint-on-black'  => __( 'Mint on black', 'woo-stripe-payment' ),
					'white-on-black' => __( 'White on black', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This is the icon style that appears next to the gateway on the checkout page.', 'woo-stripe-payment' ),
			),
			'intro_text_checkout'         => array(
				'title'   => __( 'Intro text', 'woo-stripe-payment' ),
				'type'    => 'select',
				'default' => 'In',
				'options' => array(
					'In'     => 'In',
					'Or'     => 'Or',
					'Pay'    => 'Pay',
					'Pay in' => 'Pay in'
				)
			),
			'modal_link_style_checkout'   => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'circled-info-icon',
				'options'     => array(
					'more-info-text'    => __( 'More info text', 'woo-stripe-payment' ),
					'circled-info-icon' => __( 'Circled info icon', 'woo-stripe-payment' ),
					'learn-more-text'   => __( 'Learn more text', 'woo-stripe-payment' ),
				),
				'description' => __( 'This is the style of the Afterpay info link.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'modal_theme_checkout'        => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'mint',
				'options'     => array(
					'mint'  => __( 'Mint', 'woo-stripe-payment' ),
					'white' => __( 'White', 'woo-stripe-payment' )
				),
				'description' => __( 'This is the theme color for the Afterpay info modal.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'show_interest_free_checkout' => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show interest free', 'woo-stripe-payment' ),
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'If enabled, the Afterpay message will contain the interest free text.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'cart_styling'                => array(
				'type'  => 'title',
				'title' => __( 'Cart Page Styling' )
			),
			'icon_cart'                   => array(
				'title'       => __( 'Icon', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'black-on-mint',
				'options'     => array(
					'black-on-mint'  => __( 'Black on mint', 'woo-stripe-payment' ),
					'black-on-white' => __( 'Black on white', 'woo-stripe-payment' ),
					'mint-on-black'  => __( 'Mint on black', 'woo-stripe-payment' ),
					'white-on-black' => __( 'White on black', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This is the icon style that appears next to the gateway on the checkout page.', 'woo-stripe-payment' ),
			),
			'intro_text_cart'             => array(
				'title'   => __( 'Intro text', 'woo-stripe-payment' ),
				'type'    => 'select',
				'default' => 'Or',
				'options' => array(
					'In'     => 'In',
					'Or'     => 'Or',
					'Pay'    => 'Pay',
					'Pay in' => 'Pay in'
				)
			),
			'modal_link_style_cart'       => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'circled-info-icon',
				'options'     => array(
					'more-info-text'    => __( 'More info text', 'woo-stripe-payment' ),
					'circled-info-icon' => __( 'Circled info icon', 'woo-stripe-payment' ),
					'learn-more-text'   => __( 'Learn more text', 'woo-stripe-payment' ),
				),
				'description' => __( 'This is the style of the Afterpay info link.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'modal_theme_cart'            => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'mint',
				'options'     => array(
					'mint'  => __( 'Mint', 'woo-stripe-payment' ),
					'white' => __( 'White', 'woo-stripe-payment' )
				),
				'description' => __( 'This is the theme color for the Afterpay info modal.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'show_interest_free_cart'     => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show interest free', 'woo-stripe-payment' ),
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'If enabled, the Afterpay message will contain the interest free text.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'cart_location'               => array(
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
			'product_styling'             => array(
				'type'  => 'title',
				'title' => __( 'Product Page Styling' )
			),
			'icon_product'                => array(
				'title'       => __( 'Icon', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'black-on-mint',
				'options'     => array(
					'black-on-mint'  => __( 'Black on mint', 'woo-stripe-payment' ),
					'black-on-white' => __( 'Black on white', 'woo-stripe-payment' ),
					'mint-on-black'  => __( 'Mint on black', 'woo-stripe-payment' ),
					'white-on-black' => __( 'White on black', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This is the icon style that appears next to the gateway on the checkout page.', 'woo-stripe-payment' ),
			),
			'intro_text_product'          => array(
				'title'   => __( 'Intro text', 'woo-stripe-payment' ),
				'type'    => 'select',
				'default' => 'Pay in',
				'options' => array(
					'In'     => 'In',
					'Or'     => 'Or',
					'Pay'    => 'Pay',
					'Pay in' => 'Pay in'
				)
			),
			'modal_link_style_product'    => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'circled-info-icon',
				'options'     => array(
					'more-info-text'    => __( 'More info text', 'woo-stripe-payment' ),
					'circled-info-icon' => __( 'Circled info icon', 'woo-stripe-payment' ),
					'learn-more-text'   => __( 'Learn more text', 'woo-stripe-payment' ),
				),
				'description' => __( 'This is the style of the Afterpay info link.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'modal_theme_product'         => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'mint',
				'options'     => array(
					'mint'  => __( 'Mint', 'woo-stripe-payment' ),
					'white' => __( 'White', 'woo-stripe-payment' )
				),
				'description' => __( 'This is the theme color for the Afterpay info modal.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'show_interest_free_product'  => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show interest free', 'woo-stripe-payment' ),
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'If enabled, the Afterpay message will contain the interest free text.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'product_location'            => array(
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
			'shop_styling'                => array(
				'type'  => 'title',
				'title' => __( 'Shop/Category Page Styling' )
			),
			'icon_shop'                   => array(
				'title'       => __( 'Icon', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'black-on-mint',
				'options'     => array(
					'black-on-mint'  => __( 'Black on mint', 'woo-stripe-payment' ),
					'black-on-white' => __( 'Black on white', 'woo-stripe-payment' ),
					'mint-on-black'  => __( 'Mint on black', 'woo-stripe-payment' ),
					'white-on-black' => __( 'White on black', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'This is the icon style that appears next to the gateway on the checkout page.', 'woo-stripe-payment' ),
			),
			'intro_text_shop'             => array(
				'title'   => __( 'Intro text', 'woo-stripe-payment' ),
				'type'    => 'select',
				'default' => 'Pay in',
				'options' => array(
					'In'     => 'In',
					'Or'     => 'Or',
					'Pay'    => 'Pay',
					'Pay in' => 'Pay in'
				)
			),
			'modal_link_style_shop'       => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'circled-info-icon',
				'options'     => array(
					'more-info-text'    => __( 'More info text', 'woo-stripe-payment' ),
					'circled-info-icon' => __( 'Circled info icon', 'woo-stripe-payment' ),
					'learn-more-text'   => __( 'Learn more text', 'woo-stripe-payment' ),
				),
				'description' => __( 'This is the style of the Afterpay info link.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'modal_theme_shop'            => array(
				'title'       => __( 'Modal link style', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'mint',
				'options'     => array(
					'mint'  => __( 'Mint', 'woo-stripe-payment' ),
					'white' => __( 'White', 'woo-stripe-payment' )
				),
				'description' => __( 'This is the theme color for the Afterpay info modal.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'show_interest_free_shop'     => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show interest free', 'woo-stripe-payment' ),
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'If enabled, the Afterpay message will contain the interest free text.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'shop_location'               => array(
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
		), parent::get_local_payment_settings() );

		// @todo maybe add this option back in a future version.
		//unset( $settings['title_text'] );

		if ( $this->is_restricted_account_country() ) {
			$account_country                           = stripe_wc()->account_settings->get_account_country( wc_stripe_mode() );
			$settings['specific_countries']['options'] = array( strtoupper( $account_country ) );
			unset( $settings['allowed_countries']['options']['all_except'] );
		}

		return $settings;
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script( 'afterpay-product', $scripts->assets_url( 'js/frontend/afterpay.js' ), array(
			$scripts->get_handle( 'wc-stripe' )
		) );
		$scripts->localize_script( 'afterpay-product', $this->get_localized_params( 'product' ) );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script( 'afterpay-cart', $scripts->assets_url( 'js/frontend/afterpay.js' ), array(
			$scripts->get_handle( 'wc-stripe' )
		) );
		$scripts->localize_script( 'afterpay-cart', $this->get_localized_params( 'cart' ) );
	}

	/**
	 * @param \PaymentPlugins\Stripe\Assets\AssetsApi    $assets_api
	 * @param \PaymentPlugins\Stripe\Assets\AssetDataApi $asset_data
	 *
	 * @return void
	 */
	public function enqueue_category_scripts( $assets_api, $asset_data ) {
		$assets_api->register_script( 'wc-stripe-afterpay-messaging', 'assets/build/afterpay-messaging.js' );
		$asset_data->add( $this->id, [
			'supportedCurrencies' => $this->currencies,
			'requiredParams'      => $this->get_required_parameters(),
			'msgOptions'          => $this->get_afterpay_message_options( 'shop' ),
			'hideIneligible'      => wc_string_to_bool( $this->get_option( 'hide_ineligible' ) ),
			'elementOptions'      => $this->get_element_options()
		] );
		wp_enqueue_script( 'wc-stripe-afterpay-messaging' );
	}

	public function product_fields() {
		$this->enqueue_frontend_scripts( 'product' );
		$this->output_display_items( 'product' );
	}

	public function cart_fields() {
		$this->enqueue_frontend_scripts( 'cart' );
		$this->output_display_items( 'cart' );
	}

	public function mini_cart_fields() {
		$this->output_display_items( 'cart' );
	}

	public function get_required_parameters() {
		return apply_filters( 'wc_stripe_afterpay_get_required_parameters', array(
			'AUD' => array( 'AU', 1, 2000 ),
			'CAD' => array( 'CA', 1, 2000 ),
			'NZD' => array( 'NZ', 1, 2000 ),
			'GBP' => array( 'GB', 1, 1000 ),
			'USD' => array( 'US', 1, 2000 ),
			'EUR' => array( [ 'FR', 'ES' ], 1, 1000 )
		), $this );
	}

	/**
	 * @param $currency
	 * @param $billing_country
	 * @param $total
	 *
	 * @return bool
	 */
	public function validate_local_payment_available( $currency, $billing_country, $total ) {
		$_available      = false;
		$account_country = stripe_wc()->account_settings->get_account_country( wc_stripe_mode() );
		// in test mode, the API keys might have been manually entered which
		// means the account settings 'country' value will be blank
		if ( empty( $account_country ) && wc_stripe_mode() === 'test' ) {
			$account_country = wc_get_base_location()['country'];
		}
		$params          = $this->get_required_parameters();
		$filtered_params = isset( $params[ $currency ] ) ? $params[ $currency ] : false;
		if ( $filtered_params ) {
			list( $country, $min_amount, $max_amount ) = $filtered_params;
			if ( ! is_array( $country ) ) {
				$country = array( $country );
			}
			// 1. Country associated with currency must match the Stripe account's registered country
			// 2. Stripe docs state the customer billing country must match the Stripe account country. This rule
			// only pertains to EUR. All currencies do not enforce this requirement.
			// https://stripe.com/docs/payments/afterpay-clearpay#collection-schedule
			$_available = in_array( $account_country, $country, true )
			              && ( $currency !== 'EUR' || ! $billing_country || $account_country === $billing_country )
			              && ( $min_amount <= $total && $total <= $max_amount );
		}

		return $_available;
	}

	public function get_icon() {
		return '';
	}

	public function get_localized_params( $context = 'checkout' ) {
		$params                      = parent::get_localized_params();
		$params['currencies']        = $this->currencies;
		$params['msg_options']       = $this->get_afterpay_message_options( $context );
		$params['supported_locales'] = $this->get_supported_locales();
		$params['requirements']      = $this->get_required_parameters();
		$params['hide_ineligible']   = $this->is_active( 'hide_ineligible' ) ? 'yes' : 'no';
		$params['locale']            = wc_stripe_get_site_locale();

		return $params;
	}

	public function get_supported_locales() {
		return apply_filters( 'wc_stripe_afterpay_supported_locales', array( 'en-US', 'en-CA', 'en-AU', 'en-NZ', 'en-GB', 'fr-FR', 'it-IT', 'es-ES' ) );
	}

	public function get_element_options( $options = array() ) {
		$locale = wc_stripe_get_site_locale();
		if ( ! in_array( $locale, $this->get_supported_locales() ) ) {
			$locale = 'auto';
		}
		$options['locale'] = $locale;

		return parent::get_element_options( $options ); // TODO: Change the autogenerated stub
	}

	public function get_afterpay_message_options( $context = 'checkout' ) {
		$options = array(
			'logoType'         => 'badge',
			'badgeTheme'       => $this->get_option( "icon_{$context}" ),
			'lockupTheme'      => 'black',
			'introText'        => $this->get_option( "intro_text_{$context}" ),
			'showInterestFree' => $this->is_active( "show_interest_free_{$context}" ),
			'modalTheme'       => $this->get_option( "modal_theme_{$context}" ),
			'modalLinkStyle'   => $this->get_option( "modal_link_style_{$context}" ),
			'isEligible'       => true
		);

		if ( in_array( $context, array( 'cart', 'checkout' ) ) ) {
			unset( $options['isEligible'] );
			$options['isCartEligible'] = true;
		}

		return apply_filters( 'wc_stripe_afterpay_message_options', $options, $context, $this );
	}

	public function get_payment_token( $method_id, $method_details = array() ) {
		/**
		 *
		 * @var WC_Payment_Token_Stripe_Local $token
		 */
		$token = parent::get_payment_token( $method_id, $method_details );
		$token->set_gateway_title( __( 'Afterpay', 'woo-stripe-payment' ) );

		return $token;
	}

	protected function get_payment_description() {
		$desc = '<p>' . __( 'Stripe accounts in the following countries can accept Afterpay payments with local currency settlement', 'woo-stripe-payment' ) . ': ' . implode( ',', $this->countries ) . '</p>';
		if ( ( $country = stripe_wc()->account_settings->get_account_country( wc_stripe_mode() ) ) ) {
			$params = $this->get_required_parameters();
			// get currency for country
			foreach ( $params as $currency => $param ) {
				$account_country = ! is_array( $param[0] ) ? array( $param[0] ) : $param[0];
				if ( in_array( $country, $account_country, true ) ) {
					$desc .= sprintf( __( 'Store currency must be %s for Afterpay to show because your Stripe account is registered in %s. This is a requirement of Afterpay.',
						'woo-stripe-payment' ),
						$currency,
						$country );
					if ( $this->is_restricted_account_country() ) {
						$desc .= __( 'You can accept payments from customers in the same country that you registered your Stripe account in.', 'woo-stripe-payment' );
					}

					return $desc;
				}
			}
		}

		$desc .= __( 'You can accept payments from customers in the same country that you registered your Stripe account in. Payments must also match the local 
			currency of the Stripe account country.', 'woo-stripe-payment' );

		return $desc;
	}

	public function enqueue_mini_cart_scripts( $scripts ) {
		if ( ! wp_script_is( $scripts->get_handle( 'mini-cart' ) ) ) {
			$scripts->enqueue_script( 'mini-cart',
				$scripts->assets_url( 'js/frontend/mini-cart.js' ),
				apply_filters( 'wc_stripe_mini_cart_dependencies', array( $scripts->get_handle( 'wc-stripe' ) ), $scripts ) );
		}
		$scripts->localize_script( 'mini-cart', $this->get_localized_params( 'cart' ), 'wc_' . $this->id . '_mini_cart_params' );
	}

	public function add_stripe_order_args( &$args, $order ) {
		if ( empty( $args['shipping'] ) ) {
			// This ensures digital products can be processed
			$args['shipping'] = array(
				'address' => array(
					'city'        => $order->get_billing_city(),
					'country'     => $order->get_billing_country(),
					'line1'       => $order->get_billing_address_1(),
					'line2'       => $order->get_billing_address_2(),
					'postal_code' => $order->get_billing_postcode(),
					'state'       => $order->get_billing_state(),
				),
				'name'    => $this->payment_object->get_name_from_order( $order, 'billing' ),
			);
		}
	}

	private function is_restricted_account_country() {
		$result          = false;
		$account_country = stripe_wc()->account_settings->get_account_country( wc_stripe_mode() );
		if ( $account_country ) {
			$params = $this->get_required_parameters();
			list( $countries ) = $params['EUR'];
			if ( in_array( $account_country, $countries, true ) ) {
				$result = true;
			}
		}

		return $result;
	}

}