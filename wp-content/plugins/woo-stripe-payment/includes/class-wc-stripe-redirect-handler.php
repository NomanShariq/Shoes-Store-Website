<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author  PaymentPlugins
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Redirect_Handler {

	public static function init() {
		add_action( 'wp', array( __CLASS__, 'local_payment_redirect' ) );
		add_action( 'get_header', array( __CLASS__, 'maybe_restore_cart' ), 100 );
	}

	/**
	 * Check if this request is for a local payment redirect.
	 */
	public static function local_payment_redirect() {
		if ( isset( $_GET['_stripe_local_payment'] ) ) {
			self::process_redirect();
		} elseif ( isset( $_GET[ WC_Stripe_Constants::VOUCHER_PAYMENT ], $_GET['order-id'] ) ) {
			self::process_voucher_redirect();
		}
	}

	/**
	 */
	public static function process_redirect() {
		if ( isset( $_GET['source'] ) ) {
			$result        = WC_Stripe_Gateway::load()->sources->retrieve( wc_clean( $_GET['source'] ) );
			$client_secret = isset( $_GET['client_secret'] ) ? $_GET['client_secret'] : '';
		} else {
			$result        = WC_Stripe_Gateway::load()->paymentIntents->retrieve( wc_clean( $_GET['payment_intent'] ) );
			$client_secret = isset( $_GET['payment_intent_client_secret'] ) ? $_GET['payment_intent_client_secret'] : '';
		}
		if ( is_wp_error( $result ) ) {
			wc_add_notice( sprintf( __( 'Error retrieving payment source. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ), 'error' );
		} elseif ( ! hash_equals( $client_secret, $result->client_secret ) ) {
			wc_add_notice( __( 'This request is invalid. Please try again.', 'woo-stripe-payment' ), 'error' );
		} else {
			define( WC_Stripe_Constants::REDIRECT_HANDLER, true );
			$order_id = null;
			if ( isset( $result->metadata['order_id'] ) ) {
				$order_id = $result->metadata['order_id'];
			} else {
				if ( isset( $_GET['key'], $_GET['order_id'] ) ) {
					$order = wc_get_order( absint( $_GET['order_id'] ) );
					if ( $order && ! $order->key_is_valid( $_GET['key'] ) ) {
						wc_stripe_log_info( 'Invalid order key provided while processing redirect.' );
					} else {
						$order_id = absint( $_GET['order_id'] );
					}
				}
			}
			$order = wc_get_order( wc_stripe_filter_order_id( $order_id, $result ) );
			if ( ! $order ) {
				return;
			}

			/**
			 *
			 * @var WC_Payment_Gateway_Stripe_Local_Payment $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$redirect       = $payment_method->get_return_url( $order );

			if ( in_array( $result->status, array( 'requires_action', 'pending' ) ) ) {
				if ( $result->status === 'pending' ) {
					$order->update_status( 'on-hold' );
				} else {
					return;
				}
			} elseif ( in_array( $result->status, array( 'requires_payment_method', 'failed' ) ) ) {
				wc_add_notice( __( 'Payment authorization failed. Please select another payment method.', 'woo-stripe-payment' ), 'error' );
				wc_stripe_log_info( sprintf( 'User cancelled their payment and has been redirected to the checkout page. Payment Method: %s. Order ID: %s', $payment_method->id, $order->get_id() ) );
				if ( $result instanceof \Stripe\PaymentIntent ) {
					$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, WC_Stripe_Utils::sanitize_intent( $result->toArray() ) );
				} else {
					$order->delete_meta_data( WC_Stripe_Constants::SOURCE_ID );
				}
				$order->update_status( 'pending' );

				return;
			} elseif ( 'chargeable' === $result->status ) {
				if ( ! $payment_method->has_order_lock( $order ) && ! $order->get_transaction_id() ) {
					$payment_method->set_order_lock( $order );
					$payment_method->set_new_source_token( $result->id );
					$result = $payment_method->process_payment( $order_id );
					// we don't release the order lock so there aren't conflicts with the source.chargeable webhook
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			} elseif ( in_array( $result->status, array( 'succeeded', 'requires_capture' ) ) ) {
				if ( ! $payment_method->has_order_lock( $order ) ) {
					$payment_method->set_order_lock( $order );
					$result = $payment_method->process_payment( $order_id );
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			} elseif ( $result->status === 'processing' && isset( $result->charges->data ) ) {
				if ( ! $payment_method->has_order_lock( $order ) ) {
					$payment_method->set_order_lock( $order );
					$payment_method->payment_object->payment_complete( $order, $result->charges->data[0] );
					WC_Stripe_Utils::delete_payment_intent_to_session();
					$payment_method->release_order_lock( $order );
				}
				// if this isn't the checkout page, then skip redirect
				if ( ! is_checkout() ) {
					return;
				}
			}
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	public static function maybe_restore_cart() {
		global $wp;
		if ( isset( $wp->query_vars['order-received'] ) && isset( $_GET['wc_stripe_product_checkout'] ) ) {
			add_action( 'woocommerce_cart_emptied', 'wc_stripe_restore_cart_after_product_checkout' );
		}
	}

	private static function process_voucher_redirect() {
		$payment_method = wc_clean( $_GET['_stripe_voucher_payment'] );
		/**
		 * @var \WC_Payment_Gateway_Stripe $payment_method
		 */
		$payment_methods = WC()->payment_gateways()->payment_gateways();
		$payment_method  = $payment_methods[ $payment_method ];
		$order           = wc_get_order( absint( wc_clean( $_GET['order-id'] ) ) );
		$order_key       = isset( $_GET['order-key'] ) ? wc_clean( wp_unslash( $_GET['order-key'] ) ) : '';
		if ( $order && hash_equals( $order->get_order_key(), $order_key ) ) {
			if ( method_exists( $payment_method, 'process_voucher_order_status' ) ) {
				$payment_method->process_voucher_order_status( $order );
				wp_safe_redirect( $payment_method->get_return_url( $order ) );
				exit();
			}
		}
	}

}

WC_Stripe_Redirect_Handler::init();
