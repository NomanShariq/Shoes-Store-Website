<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe_Local_Payment' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author  PaymentPlugins
 *
 */
class WC_Payment_Gateway_Stripe_Alipay extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'alipay';

	public function __construct() {
		$this->local_payment_type = 'alipay';
		$this->currencies         = array( 'AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY', 'SGD', 'USD', 'CNY', 'NZD', 'MYR' );
		$this->id                 = 'stripe_alipay';
		$this->tab_title          = __( 'Alipay', 'woo-stripe-payment' );
		$this->template_name      = 'local-payment.php';
		$this->token_type         = 'Stripe_Local';
		$this->method_title       = __( 'Alipay', 'woo-stripe-payment' );
		$this->method_description = __( 'Alipay gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/alipay.svg' );
		parent::__construct();
	}

	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields['allowed_countries']['default'] = 'all';
	}

	/**
	 * @param string $currency
	 * @param string $billing_country
	 *
	 * @return bool
	 */
	public function validate_local_payment_available( $currency, $billing_country ) {
		$country          = stripe_wc()->account_settings->get_option( 'country' );
		$default_currency = stripe_wc()->account_settings->get_option( 'default_currency' );
		if ( empty( $country ) && wc_stripe_mode() === 'test' ) {
			$country          = wc_get_base_location()['country'];
			$default_currency = $currency;
		}
		// https://stripe.com/docs/payments/alipay/accept-a-payment?platform=web#supported-currencies
		// Currency must be one of the allowed values
		if ( in_array( $currency, $this->currencies ) ) {
			// If CNY, it doesn't matter what the billing or default country is.
			if ( $currency === 'CNY' ) {
				return true;
			}
			// If merchant's country is any of the following, currency must be EUR
			if ( in_array( $country, array( 'AT', 'BE', 'BG', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'NO', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH' ) ) ) {
				return $currency === 'EUR';
			} else {
				// For all other countries, Alipay is available if the currency matches the
				// Stripe account default currency
				return $currency === $default_currency;
			}
		}

		return false;
	}

	protected function get_payment_description() {
		return __( 'Gateway will appear when store currency is CNY, or currency matches merchant\'s 
					default Stripe currency. For merchants located in DK, NO, SE, & CH, currency must be EUR.', 'woo-stripe-payment' );
	}

}

