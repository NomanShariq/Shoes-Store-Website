<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.1.0
 * @author  PaymentPlugins
 * @package Stripe/Abstract
 *
 */
abstract class WC_Stripe_Payment {

	/**
	 *
	 * @var WC_Payment_Gateway_Stripe
	 */
	protected $payment_method;

	/**
	 *
	 * @var WC_Stripe_Gateway
	 */
	protected $gateway;

	/**
	 *
	 * @param WC_Payment_Gateway_Stripe $payment_method
	 * @param WC_Stripe_Gateway         $gateway
	 */
	public function __construct( $payment_method, $gateway ) {
		$this->payment_method = $payment_method;
		$this->gateway        = $gateway;
	}

	public function get_gateway() {
		return $this->gateway;
	}

	/**
	 * Process the payment for the order.
	 *
	 * @param WC_Order                  $order
	 * @param WC_Payment_Gateway_Stripe $payment_method
	 */
	public abstract function process_payment( $order );

	/**
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 *
	 * @return \Stripe\Charge
	 */
	public abstract function capture_charge( $amount, $order );

	/**
	 *
	 * @param WC_Order $order
	 */
	public abstract function void_charge( $order );

	/**
	 *
	 * @param \Stripe\Charge $charge
	 */
	public abstract function get_payment_method_from_charge( $charge );

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public abstract function add_order_payment_method( &$args, $order );

	/**
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 */
	public abstract function scheduled_subscription_payment( $amount, $order );

	/**
	 *
	 * @param WC_Order $order
	 */
	public abstract function process_pre_order_payment( $order );

	/**
	 * Return true if the charge can be voided.
	 *
	 * @param WC_Order $order
	 */
	public abstract function can_void_order( $order );

	/**
	 * Perform post payment processes
	 *
	 * @param WC_Order       $order
	 * @param \Stripe\Charge $charge
	 *
	 * @since 3.1.7
	 */
	public function payment_complete( $order, $charge ) {
		WC_Stripe_Utils::add_balance_transaction_to_order( $charge, $order );
		$this->payment_method->save_order_meta( $order, $charge );
		if ( 'pending' === $charge->status ) {
			$order->update_status( apply_filters( 'wc_stripe_pending_charge_status', 'on-hold', $order, $this->payment_method ),
				sprintf( __( 'Charge %1$s is pending. Payment Method: %2$s. Payment will be completed once charge.succeeded webhook received from Stripe.', 'woo-stripe-payment' ),
					$order->get_transaction_id(),
					$order->get_payment_method_title() ) );
		} else {
			if ( $charge->captured ) {
				$order->payment_complete( $charge->id );
			} else {
				$order_status = $this->payment_method->get_option( 'order_status' );
				$order->update_status( apply_filters( 'wc_stripe_authorized_order_status', 'default' === $order_status ? 'on-hold' : $order_status, $order, $this->payment_method ) );
			}
			$order->add_order_note( sprintf( __( 'Order %1$s successful in Stripe. Charge: %2$s. Payment Method: %3$s', 'woo-stripe-payment' ),
				$charge->captured ? __( 'charge', 'woo-stripe-payment' ) : __( 'authorization', 'woo-stripe-payment' ),
				$order->get_transaction_id(),
				$order->get_payment_method_title() ) );
		}

		/**
		 * @since 3.3.6
		 */
		do_action( 'wc_stripe_order_payment_complete', $charge, $order );
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param float    $amount
	 *
	 * @throws Exception
	 */
	public function process_refund( $order, $amount = null ) {
		$charge = $order->get_transaction_id();
		try {
			if ( empty( $charge ) ) {
				throw new Exception( __( 'Transaction Id cannot be empty.', 'woo-stripe-payment' ) );
			}

			/**
			 * @param array
			 * @param WC_Stripe_Payment
			 * @param WC_Order $order
			 * @param float    $amount
			 *
			 * @since 3.2.10
			 */
			$args   = apply_filters( 'wc_stripe_refund_args', array(
				'charge'   => $charge,
				'amount'   => wc_stripe_add_number_precision( $amount, $order->get_currency() ),
				'metadata' => array(
					'order_id'    => $order->get_id(),
					'created_via' => 'woocommerce'
				),
				'expand'   => stripe_wc()->advanced_settings->is_fee_enabled() ? array( 'charge.balance_transaction', 'charge.refunds.data.balance_transaction' ) : array()
			), $this, $order, $amount );
			$result = $this->gateway->refunds->mode( wc_stripe_order_mode( $order ) )->create( $args );
			if ( ! is_wp_error( $result ) ) {
				WC_Stripe_Utils::add_balance_transaction_to_order( $result->charge, $order, true );

				/**
				 * @since 3.3.35
				 */
				do_action( 'wc_stripe_process_refund_success', $order );

				return true;
			}

			return $result;
		} catch ( Exception $e ) {
			return new WP_Error( 'refund-error', $e->getMessage() );
		}
	}

	/**
	 * @param WC_Order                  $order
	 * @param WC_Payment_Gateway_Stripe $payment_method
	 *
	 * @since 3.3.8
	 */
	public function process_zero_total_order( $order, $payment_method ) {
		$payment_method->save_zero_total_meta( $order );
		if ( 'capture' === $payment_method->get_option( 'charge_type' ) ) {
			$order->payment_complete();
		} else {
			$order_status = $payment_method->get_option( 'order_status' );
			$order->update_status( apply_filters( 'wc_stripe_authorized_order_status', 'default' === $order_status ? 'on-hold' : $order_status, $order, $payment_method ) );
		}
		WC()->cart->empty_cart();
		$this->destroy_session_data();

		return array(
			'result'   => 'success',
			'redirect' => $payment_method->get_return_url( $order ),
		);
	}

	/**
	 * Return a failed order response.
	 *
	 * @return array
	 */
	public function order_error() {
		wc_stripe_set_checkout_error();

		return array( 'result' => 'failure' );
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_general_order_args( &$args, $order ) {
		$this->add_order_amount( $args, $order );
		$this->add_order_currency( $args, $order );
		$this->add_order_description( $args, $order );
		$this->add_order_shipping_address( $args, $order );
		$this->add_order_metadata( $args, $order );
		$this->add_order_payment_method( $args, $order );
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_order_metadata( &$args, $order ) {
		$meta_data  = array(
			'gateway_id' => $order->get_payment_method(),
			'order_id'   => $order->get_id(),
			'user_id'    => $order->get_user_id(),
			'ip_address' => $order->get_customer_ip_address(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'unavailable',
			'partner'    => 'PaymentPlugins',
			'created'    => time()
		);
		$webhook_id = stripe_wc()->api_settings->get_option( 'webhook_id_' . wc_stripe_mode() );
		if ( $webhook_id ) {
			$meta_data['webhook_id'] = $webhook_id;
		}
		if ( has_action( 'woocommerce_order_number' ) ) {
			$meta_data['order_number'] = $order->get_order_number();
		}
		$length = count( $meta_data );

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			// Stripe limits metadata keys to 50 entries.
			if ( $length < 50 ) {
				/**
				 *
				 * @var WC_Order_Item_Product $item
				 */
				$key   = 'product_' . $item->get_product_id();
				$value = sprintf( '%s x %s', $item->get_name(), $item->get_quantity() );
				// Stripe limits key names to 40 chars and values to 500 chars
				if ( strlen( $key ) <= 40 && strlen( $value ) <= 500 ) {
					$meta_data[ $key ] = $value;
					$length ++;
				}
			}
		}
		$args['metadata'] = apply_filters( 'wc_stripe_order_meta_data', $meta_data, $order );
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_order_description( &$args, $order ) {
		$args['description'] = sprintf( __( 'Order %1$s from %2$s', 'woo-stripe-payment' ), $order->get_order_number(), get_bloginfo( 'name' ) );
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 * @param float    $amount
	 */
	public function add_order_amount( &$args, $order, $amount = null ) {
		$args['amount'] = wc_stripe_add_number_precision( $amount ? $amount : $order->get_total(), $order->get_currency() );
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_order_currency( &$args, $order ) {
		$args['currency'] = $order->get_currency();
	}

	/**
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_order_shipping_address( &$args, $order ) {
		if ( wc_stripe_order_has_shipping_address( $order ) ) {
			$args['shipping'] = array(
				'address' => array(
					'city'        => $order->get_shipping_city(),
					'country'     => $order->get_shipping_country(),
					'line1'       => $order->get_shipping_address_1(),
					'line2'       => $order->get_shipping_address_2(),
					'postal_code' => $order->get_shipping_postcode(),
					'state'       => $order->get_shipping_state(),
				),
				'name'    => $this->get_name_from_order( $order, 'shipping' ),
			);
		} else {
			$args['shipping'] = array();
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param string   $type
	 *
	 * @return string
	 */
	public function get_name_from_order( $order, $type ) {
		if ( $type === 'billing' ) {
			return sprintf( '%s %s', $order->get_billing_first_name(), $order->get_billing_last_name() );
		} else {
			return sprintf( '%s %s', $order->get_shipping_first_name(), $order->get_shipping_last_name() );
		}
	}

	/**
	 * @param WC_Order $order
	 * @param WP_Error $error
	 *
	 * @since 3.1.7
	 */
	public function add_payment_failed_note( $order, $error ) {
		$note = sprintf( __( 'Error processing payment. Reason: %s', 'woo-stripe-payment' ), $error->get_error_message() );

		/**
		 * @param string   $note
		 * @param WP_Error $error
		 *
		 */
		$note = apply_filters( 'wc_stripe_order_failed_note', $note, $error, $this->payment_method );
		$order->update_status( 'failed', $note );
	}

	/**
	 * @param $payment_method
	 *
	 * @since 3.3.3
	 */
	public function set_payment_method( $payment_method ) {
		$this->payment_method = $payment_method;
	}

	/**
	 * @since 3.3.20
	 */
	protected function get_payment_method_charge_type() {
		return $this->payment_method->get_option( 'charge_type' ) === 'capture' ? WC_Stripe_Constants::AUTOMATIC : WC_Stripe_Constants::MANUAL;
	}

	public function destroy_session_data() {
	}

}
