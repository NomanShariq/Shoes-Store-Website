<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.1.0
 * @author  Payment Plugins
 * @package Stripe/Trait
 */
trait WC_Stripe_Payment_Intent_Trait {

	public function get_payment_object() {
		return WC_Stripe_Payment_Factory::load( 'payment_intent', $this, WC_Stripe_Gateway::load() );
	}

	public function get_payment_method_type() {
		return $this->payment_method_type;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_confirmation_method( $order = null ) {
		return 'manual';
	}

	/**
	 *
	 * @param \Stripe\PaymentIntent $intent
	 * @param WC_Order              $order
	 */
	public function get_payment_intent_checkout_url( $intent, $order, $type = 'intent' ) {
		global $wp;

		// rand is used to generate some random entropy so that window hash events are triggered.
		$args = array(
			'type'          => $type,
			'client_secret' => $intent->client_secret,
			'order_id'      => $order->get_id(),
			'order_key'     => $order->get_order_key(),
			'gateway_id'    => $this->id,
			'status'        => $intent->status,
			'pm'            => $intent->payment_method,
			'entropy'       => rand( 0, 999999 )
		);
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {
			$args['save_method'] = ! empty( $_POST[ $this->save_source_key ] );
		}

		return sprintf( '#response=%s', rawurlencode( base64_encode( wp_json_encode( $args ) ) ) );
	}

	/**
	 * @param \Stripe\PaymentIntent $intent
	 * @param WC_Order              $order
	 */
	public function get_payment_intent_confirmation_args( $intent, $order ) {
		return array();
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array|void
	 */
	public function handle_setup_intent_for_order( $order ) {
		if ( defined( WC_Stripe_Constants::PROCESSING_PAYMENT ) ) {
			$result = $this->save_payment_method( $this->get_new_source_token(), $order );
			if ( is_wp_error( $result ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );

				return $this->get_order_error();
			}
			// The setup intent ID is no longer needed so remove it from the order
			$order->delete_meta_data( WC_Stripe_Constants::SETUP_INTENT_ID );
		} else {
			$setup_intent        = $this->get_payment_intent_id();
			$save_payment_method = $this->should_save_payment_method( $order );
			// if setup intent exists then it was created client side.
			// attempt to save the payment method
			if ( $setup_intent && $save_payment_method ) {
				$result = $this->save_payment_method( $this->get_new_source_token(), $order );
				if ( is_wp_error( $result ) ) {
					wc_add_notice( $result->get_error_message(), 'error' );

					return $this->get_order_error();
				}
			} elseif ( ! $setup_intent && $save_payment_method ) {
				// A new payment method is being used but there's no setup intent provided
				// by client. Create one here
				$result = $this->does_order_require_action( $order, $this->get_new_source_token() );
				if ( is_wp_error( $result ) ) {
					wc_add_notice( sprintf( __( 'Error processing payment. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ), 'error' );

					return $this->get_order_error();
				} elseif ( $result ) {
					return $result;
				} else {
					$this->save_payment_method( $this->get_new_source_token(), $order );
				}
			} else {
				$this->payment_method_token = $this->get_saved_source_id();
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array|void
	 */
	public function process_zero_total_order( $order ) {
		$result = $this->handle_setup_intent_for_order( $order );
		if ( $result && isset( $result['result'] ) ) {
			return $result;
		}

		return $this->payment_object->process_zero_total_order( $order, $this );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function process_pre_order( $order ) {
		$token        = null;
		$setup_intent = $this->get_payment_intent_id();
		if ( defined( WC_Stripe_Constants::PROCESSING_PAYMENT ) ) {
			$token = $this->create_payment_method( $this->get_new_source_token(), $order->get_meta( WC_Stripe_Constants::CUSTOMER_ID ) );
		} else {
			if ( ! $this->use_saved_source() ) {
				if ( ! $order->get_customer_id() ) {
					$customer = WC_Stripe_Customer_Manager::instance()->create_customer( WC()->customer );
					if ( is_wp_error( $customer ) ) {
						return wc_add_notice( $customer->get_error_message(), 'error' );
					}
					$order->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $customer->id );
				} else {
					$order->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, wc_stripe_get_customer_id( $order->get_customer_id() ) );
				}
				$order->save();
				if ( ! $setup_intent ) {
					$result = $this->does_order_require_action( $order, $this->get_new_source_token() );
					if ( $result ) {
						if ( is_wp_error( $result ) ) {
							wc_add_notice( $result->get_error_message(), 'error' );
							$result = $this->get_order_error();
						}

						return $result;
					}
				}
				$token = $this->create_payment_method( $this->get_new_source_token(), $order->get_meta( WC_Stripe_Constants::CUSTOMER_ID ) );
			} else {
				$this->payment_method_token = $this->get_saved_source_id();
			}
		}
		if ( is_wp_error( $token ) ) {
			return wc_add_notice( $token->get_error_message(), 'error' );
		}
		WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
		$this->save_zero_total_meta( $order, $token );
		$this->payment_object->destroy_session_data();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * @param WC_Order $order
	 * @param string   $payment_method
	 *
	 * @return array
	 */
	private function does_order_require_action( $order, $payment_method ) {
		if ( ( $intent_id = $order->get_meta( WC_Stripe_Constants::SETUP_INTENT_ID ) ) ) {
			$params = array_filter( array(
				'payment_method'       => $payment_method,
				'payment_method_types' => [ $this->get_payment_method_type() ]
			) );
			$intent = $this->gateway->setupIntents->update( $intent_id, $params );
		} else {
			$params = array(
				'confirm'              => false,
				'usage'                => 'off_session',
				'metadata'             => array(
					'gateway_id' => $this->id,
					'order_id'   => $order->get_id()
				),
				'payment_method_types' => [ $this->get_payment_method_type() ]
			);
			if ( $payment_method ) {
				$params['payment_method'] = $payment_method;
				$params['confirm']        = true;
			}
			$this->add_stripe_order_args( $params, $order );
			$intent = $this->payment_object->get_gateway()->setupIntents->create( apply_filters( 'wc_stripe_setup_intent_params', $params, $order, $this ) );
		}
		if ( is_wp_error( $intent ) ) {
			return $intent;
		}
		$order->update_meta_data( WC_Stripe_Constants::SETUP_INTENT_ID, $intent->id );
		$order->save();

		if ( in_array( $intent->status, array(
			'requires_action',
			'requires_payment_method',
			'requires_source_action',
			'requires_source',
			'requires_confirmation'
		), true )
		) {
			return array(
				'result'   => 'success',
				'redirect' => $this->get_payment_intent_checkout_url( $intent, $order, 'setup_intent' ),
			);
		} elseif ( $intent->status === 'succeeded' ) {
			$this->payment_method_token = $intent->payment_method;
			// The setup intent ID is no longer needed so remove it from the order
			$order->delete_meta_data( WC_Stripe_Constants::SETUP_INTENT_ID );

			return false;
		}
	}

	/**
	 * @since 3.3.32
	 * @return false
	 */
	public function is_deferred_intent_creation() {
		return false;
	}

}

/**
 *
 * @since   3.1.0
 * @author  Payment Plugins
 * @package Stripe/Trait
 */
trait WC_Stripe_Payment_Charge_Trait {

	public function get_payment_object() {
		return WC_Stripe_Payment_Factory::load( 'charge', $this, WC_Stripe_Gateway::load() );
	}

}

/**
 *
 * @since   3.1.0
 * @author  Payment Plugins
 * @package Stripe/Trait
 */
trait WC_Stripe_Local_Payment_Charge_Trait {

	public function get_payment_object() {
		return WC_Stripe_Payment_Factory::load( 'local_charge', $this, WC_Stripe_Gateway::load() );
	}

}

/**
 *
 * @since   3.1.0
 * @author  Payment Plugins
 * @package Stripe/Trait
 *
 */
trait WC_Stripe_Local_Payment_Intent_Trait {

	use WC_Stripe_Payment_Intent_Trait;

	/**
	 *
	 * @param \Stripe\PaymentIntent $secret
	 * @param WC_Order              $order
	 * @param string                $type
	 */
	public function get_payment_intent_checkout_url( $intent, $order, $type = 'payment_intent' ) {
		// rand is used to generate some random entropy so that window hash events are triggered.
		return sprintf(
			'#response=%s',
			rawurlencode( base64_encode(
				wp_json_encode( $this->get_payment_intent_checkout_params( $intent, $order, $type ) )
			) )
		);
	}

	protected function get_payment_intent_checkout_params( $intent, $order, $type ) {
		return array(
			'type'               => $type,
			'client_secret'      => $intent->client_secret,
			'gateway_id'         => $this->id,
			'order_id'           => $order->get_id(),
			'order_key'          => $order->get_order_key(),
			'return_url'         => $this->get_local_payment_return_url( $order ),
			'order_received_url' => $this->get_return_url( $order ),
			'entropy'            => rand(
				0,
				999999
			),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_confirmation_method( $order = null ) {
		return 'automatic';
	}

	/**
	 * @param \Stripe\PaymentIntent $intent
	 * @param WC_Order              $order
	 */
	public function get_payment_intent_confirmation_args( $intent, $order ) {
		return array(
			'return_url' => $this->get_local_payment_return_url( $order )
		);
	}

}
