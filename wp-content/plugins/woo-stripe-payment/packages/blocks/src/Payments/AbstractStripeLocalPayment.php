<?php


namespace PaymentPlugins\Blocks\Stripe\Payments;

/**
 * Class AbstractLocalStripePayment
 *
 * @package PaymentPlugins\Blocks\Stripe\Payments
 */
abstract class AbstractStripeLocalPayment extends AbstractStripePayment {

	public function get_payment_method_script_handles() {
		if ( ! wp_script_is( 'wc-stripe-block-local-payment', 'registered' ) && ! is_checkout() ) {
			$this->assets_api->register_script( 'wc-stripe-block-local-payment', 'build/wc-stripe-local-payment.js' );
		}

		return array( 'wc-stripe-block-local-payment' );
	}

	public function get_payment_method_data() {
		return array(
			'name'                  => $this->name,
			'title'                 => $this->payment_method->get_title(),
			'description'           => $this->payment_method->get_description(),
			'icon'                  => $this->get_payment_method_icon(),
			'features'              => $this->get_supported_features(),
			'placeOrderButtonLabel' => \esc_html( $this->payment_method->order_button_text ),
			'allowedCountries'      => $this->payment_method->get_option( 'allowed_countries' ),
			'exceptCountries'       => $this->payment_method->get_option( 'except_countries', array() ),
			'specificCountries'     => $this->payment_method->get_option( 'specific_countries', array() ),
			'countries'             => $this->payment_method->limited_countries,
			'currencies'            => $this->payment_method->currencies,
			'paymentElementOptions' => $this->payment_method->get_element_params(),
			'elementOptions'        => $this->payment_method->get_element_options(),
			'isAdmin'               => is_admin(),
			'returnUrl'             => $this->get_source_return_url(),
			'paymentType'           => $this->payment_method->local_payment_type,
			'locale'                => str_replace( '_', '-', substr( get_locale(), 0, 5 ) )
		);
	}

	protected function get_payment_method_icon() {
		return array(
			'id'  => $this->get_name(),
			'alt' => '',
			'src' => $this->payment_method->icon
		);
	}

	protected function get_source_return_url() {
		return add_query_arg( array(
			'_stripe_local_payment' => $this->name
		), wc_get_checkout_url() );
	}

}