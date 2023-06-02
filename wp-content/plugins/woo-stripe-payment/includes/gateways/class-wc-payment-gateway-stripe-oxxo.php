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
class WC_Payment_Gateway_Stripe_OXXO extends WC_Payment_Gateway_Stripe_Local_Payment {

	protected $payment_method_type = 'oxxo';

	public $synchronous = false;

	public $is_voucher_payment = true;

	use WC_Stripe_Local_Payment_Intent_Trait;

	public function __construct() {
		$this->local_payment_type = 'oxxo';
		$this->currencies         = array( 'MXN' );
		$this->countries          = array( 'MX' );
		$this->id                 = 'stripe_oxxo';
		$this->tab_title          = __( 'OXXO', 'woo-stripe-payment' );
		$this->method_title       = __( 'OXXO', 'woo-stripe-payment' );
		$this->method_description = __( 'OXXO gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = stripe_wc()->assets_url( 'img/oxxo.svg' );
		parent::__construct();
	}

	public function get_local_payment_settings() {
		return array_merge( parent::get_local_payment_settings(), array(
			'expiration_days' => array(
				'title'       => __( 'Expiration Days', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => '3',
				'options'     => array_reduce( range( 1, 7 ), function ( $carry, $item ) {
					$carry[ $item ] = sprintf( _n( '%s day', '%s days', $item, 'woo-stripe-payment' ), $item );

					return $carry;
				}, array() ),
				'desc_tip'    => true,
				'description' => __( 'The number of days before the OXXO voucher expires.', 'woo-stripe-payment' )
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
			'oxxo' => array(
				'expires_after_days' => $this->get_option( 'expiration_days', 3 )
			)
		);
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
					$link = isset( $payment_intent->next_action->oxxo_display_details->hosted_voucher_url ) ? $payment_intent->next_action->oxxo_display_details->hosted_voucher_url : null;
					if ( $link ) {
						$content .= '<p>' . sprintf( __( 'Please click %shere%s to view your OXXO voucher.', 'woo-stripe-payment' ), '<a href="' . $link . '" target="_blank">', '</a>' ) . '</p>';
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

}
