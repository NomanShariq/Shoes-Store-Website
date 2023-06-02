<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 * Class WC_Payment_Gateway_Stripe_Klarna
 *
 */
class WC_Payment_Gateway_Stripe_Klarna extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'klarna';

	private $supported_locales = array(
		'de-AT',
		'en-AT',
		'da-DK',
		'en-DK',
		'fi-FI',
		'sv-FI',
		'en-FI',
		'de-DE',
		'en-DE',
		'nl-NL',
		'en-NL',
		'nb-NO',
		'en-NO',
		'sv-SE',
		'en-SE',
		'en-GB',
		'en-US',
		'es-US',
		'nl-BE',
		'fr-BE',
		'en-BE',
		'es-ES',
		'en-ES',
		'it-IT',
		'en-IT',
		'fr-FR',
		'en-FR',
		'en-IE'
	);

	use WC_Stripe_Local_Payment_Intent_Trait;

	public function __construct() {
		$this->local_payment_type = 'klarna';
		$this->currencies         = array( 'EUR', 'SEK', 'NOK', 'DKK', 'GBP', 'USD' );
		$this->countries          = $this->limited_countries = array( 'US', 'AT', 'FI', 'DE', 'NL', 'DK', 'NO', 'SE', 'GB', 'BE', 'ES', 'IT', 'FR', 'IE' );
		$this->id                 = 'stripe_klarna';
		$this->tab_title          = __( 'Klarna', 'woo-stripe-payment' );
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Klarna', 'woo-stripe-payment' );
		$this->method_description = __( 'Klarna gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		parent::__construct();
		$this->template_name = 'klarna-v2.php';
		$this->icon          = stripe_wc()->assets_url( 'img/' . $this->get_option( 'icon' ) . '.svg' );
	}

	public function get_required_parameters() {
		return apply_filters( 'wc_stripe_klarna_get_required_parameters', array(
			'USD' => array( 'US' ),
			'EUR' => array( 'AT', 'BE', 'DE', 'ES', 'FI', 'FR', 'GR', 'IE', 'IT', 'NL' ),
			'DKK' => array( 'DK' ),
			'NOK' => array( 'NO' ),
			'SEK' => array( 'SE' ),
			'GBP' => array( 'GB' ),
		), $this );
	}

	/**
	 * @param string $currency
	 * @param string $billing_country
	 * @param float  $total
	 *
	 * @return bool
	 */
	public function validate_local_payment_available( $currency, $billing_country, $total ) {
		if ( $billing_country ) {
			$params = $this->get_required_parameters();

			return isset( $params[ $currency ] ) && in_array( $billing_country, $params[ $currency ] ) !== false;
		}

		return false;
	}

	public function add_stripe_order_args( &$args, $order ) {
		$args['payment_method_options'] = array(
			'klarna' => array(
				'preferred_locale' => $this->get_formatted_locale_from_order( $order )
			)
		);
	}

	/**
	 * Returns a formatted locale based on the billing country for the order.
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_formatted_locale_from_order( $order ) {
		$country = $order->get_billing_country();
		switch ( $country ) {
			case 'US':
				$locale = 'en-US';
				break;
			case 'GB':
				$locale = 'en-GB';
				break;
			case 'AT':
				$locale = 'de-AT';
				break;
			case 'BE':
				$locale = 'fr-BE';
				break;
			case 'DK':
				$locale = 'da-DK';
				break;
			case 'NO':
				$locale = 'nb-NO';
				break;
			case 'SE':
				$locale = 'sv-SE';
				break;
			default:
				$locale = strtolower( $country ) . '-' . strtoupper( $country );
		}
		if ( ! in_array( $locale, $this->supported_locales, true ) ) {
			$locale = 'en-US';
		}

		return $locale;
	}

	public function get_local_payment_settings() {
		return wp_parse_args(
			array(
				'charge_type' => array(
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
				'icon'        => array(
					'title'       => __( 'Icon', 'woo-stripe-payment' ),
					'type'        => 'select',
					'options'     => array(
						'klarna'      => __( 'Black text', 'woo-stripe-payment' ),
						'klarna_pink' => __( 'Pink background black text', 'woo-stripe-payment' )
					),
					'default'     => 'klarna_pink',
					'desc_tip'    => true,
					'description' => __( 'This is the icon style that appears next to the gateway on the checkout page.', 'woo-stripe-payment' ),
				)
			),
			parent::get_local_payment_settings()
		);
	}

}
