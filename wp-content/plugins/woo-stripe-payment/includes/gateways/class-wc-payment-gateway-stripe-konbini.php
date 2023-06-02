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
class WC_Payment_Gateway_Stripe_Konbini extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'konbini';

	public $synchronous = false;

	public $is_voucher_payment = true;

	use WC_Stripe_Local_Payment_Intent_Trait {
		get_payment_intent_checkout_params as get_payment_intent_checkout_params_v1;
	}

	public function __construct() {
		$this->local_payment_type = 'konbini';
		$this->currencies         = array( 'JPY' );
		$this->countries          = $this->limited_countries = array( 'JP' );
		$this->id                 = 'stripe_konbini';
		$this->tab_title          = __( 'Konbini', 'woo-stripe-payment' );
		$this->method_title       = __( 'Konbini', 'woo-stripe-payment' );
		$this->method_description = __( 'Konbini gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/konbini.svg' );
		parent::__construct();
	}

	public function get_local_payment_settings() {
		return array_merge( parent::get_local_payment_settings(), array(
			'expiration_days' => array(
				'title'       => __( 'Expiration Days', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => '3',
				'options'     => array_reduce( range( 0, 60 ), function ( $carry, $item ) {
					$carry[ $item ] = sprintf( _n( '%s day', '%s days', $item, 'woo-stripe-payment' ), $item );

					return $carry;
				}, array() ),
				'desc_tip'    => true,
				'description' => __( 'The number of days before the Boleto voucher expires.', 'woo-stripe-payment' )
			),
			'email_link'      => array(
				'title'       => __( 'Voucher Link In Email', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, the voucher link will be included in the order on-hold email sent to the customer.', 'woo-stripe-payment' )
			)
		) );
	}

	public function add_stripe_order_args( &$args, $order ) {
		$args['payment_method_options'] = array(
			'konbini' => array(
				'confirmation_number' => $this->sanitize_confirmation_number( $order->get_billing_phone() ),
				'expires_after_days'  => $this->get_option( 'expiration_days', 3 )
			)
		);
	}

	/**
	 * @param $value
	 *
	 * @since 3.3.38
	 * @return array|string|string[]|null
	 */
	private function sanitize_confirmation_number( $value ) {
		return preg_replace( '/[^\d]/', '', $value );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function process_voucher_order_status( $order ) {
		if ( $this->is_active( 'email_link' ) ) {
			add_filter( 'woocommerce_email_additional_content_customer_on_hold_order', array( $this, 'add_customer_voucher_email_content' ), 10, 2 );
		}
		$order->update_status( 'on-hold' );
	}

	/**
	 * @param string    $content
	 * @param \WC_Order $order
	 */
	public function add_customer_voucher_email_content( $content, $order ) {
		if ( $order && $order->get_payment_method() === $this->id ) {
			if ( ( $intent_id = $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) ) ) {
				$payment_intent = $this->gateway->mode( $order )->paymentIntents->retrieve( $intent_id );
				if ( ! is_wp_error( $payment_intent ) ) {
					$link = isset( $payment_intent->next_action->konbini_display_details->hosted_voucher_url ) ? $payment_intent->next_action->konbini_display_details->hosted_voucher_url : null;
					if ( $link ) {
						$content .= '<p>' . sprintf( __( 'Please click %shere%s to view your Konbini voucher.', 'woo-stripe-payment' ), '<a href="' . $link . '" target="_blank">', '</a>' ) . '</p>';
					}
				}
			}
		}

		return $content;
	}

	/**
	 * @param null $order
	 *
	 * @return string
	 */
	public function get_return_url( $order = null ) {
		if ( $this->processing_payment && $order ) {
			return add_query_arg( array(
				WC_Stripe_Constants::VOUCHER_PAYMENT => $this->id,
				'order-id'                           => $order->get_id(),
				'order-key'                          => $order->get_order_key()
			), wc_get_checkout_url() );
		}

		return parent::get_return_url( $order );
	}

	protected function get_payment_intent_checkout_params( $intent, $order, $type ) {
		$params                        = $this->get_payment_intent_checkout_params_v1( $intent, $order, $type );
		$params['billing_phone']       = $this->sanitize_confirmation_number( $order->get_billing_phone() );
		$params['confirmation_number'] = rand( 10000000000, 99999999999 );

		return $params;
	}

	public function get_local_payment_description() {
		$this->local_payment_description = wc_stripe_get_template_html( 'checkout/konbini-instructions.php', array( 'button_text' => $this->order_button_text ) );

		return parent::get_local_payment_description();
	}

	public function validate_local_payment_available( $currency, $billing_country, $total ) {
		return 120 <= $total && $total <= 300000;
	}

}
