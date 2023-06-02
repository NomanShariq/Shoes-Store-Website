<?php

defined( 'ABSPATH' ) || exit();

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-stripe-payment.php' );

/**
 *
 * @since   3.1.0
 *
 * @author  Payment Plugins
 * @package Stripe/Classes
 */
class WC_Stripe_Payment_Intent extends WC_Stripe_Payment {

	private $update_payment_intent = false;

	private $retry_count = 0;

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::process_payment()
	 */
	public function process_payment( $order ) {
		// first check to see if a payment intent can be used
		if ( ( $intent = $this->can_use_payment_intent( $order ) ) ) {
			if ( $this->can_update_payment_intent( $order, $intent ) ) {
				$intent = $this->gateway->paymentIntents->update( $intent['id'], $this->get_payment_intent_args( $order, false, $intent ) );
			}
		} else {
			$intent = $this->gateway->paymentIntents->create( $this->get_payment_intent_args( $order ) );
		}

		if ( is_wp_error( $intent ) ) {
			if ( $this->should_retry_payment( $intent, $order ) ) {
				return $this->process_payment( $order );
			} else {
				$this->add_payment_failed_note( $order, $intent );

				return $intent;
			}
		}

		// always update the order with the payment intent.
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, is_object( $intent->payment_method ) ? $intent->payment_method->id : $intent->payment_method );
		$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, WC_Stripe_Utils::sanitize_intent( $intent->toArray() ) );
		$order->save();

		if ( $intent->status === 'requires_confirmation' ) {
			$intent = $this->gateway->paymentIntents->confirm(
				$intent->id,
				apply_filters( 'wc_stripe_payment_intent_confirmation_args', $this->payment_method->get_payment_intent_confirmation_args( $intent, $order ), $intent, $order )
			);
			if ( is_wp_error( $intent ) ) {
				$this->post_payment_process_error_handling( $intent, $order );
				$this->add_payment_failed_note( $order, $intent );

				return $intent;
			}
		}

		// the intent was processed.
		if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {
			$charge = $intent->charges->data[0];
			if ( isset( $intent->setup_future_usage, $charge->payment_method_details ) && 'off_session' === $intent->setup_future_usage ) {
				if ( ! defined( WC_Stripe_Constants::PROCESSING_ORDER_PAY ) ) {
					$this->payment_method->save_payment_method( is_object( $intent->payment_method ) ? $intent->payment_method->id : $intent->payment_method, $order, $charge->payment_method_details );
				}
			}
			// remove metadata that's no longer needed
			$order->delete_meta_data( WC_Stripe_Constants::PAYMENT_INTENT );

			$this->destroy_session_data();

			return (object) array(
				'complete_payment' => true,
				'charge'           => $charge,
			);
		}
		if ( $intent->status === 'processing' ) {
			$this->destroy_session_data();
			$order->update_status( apply_filters( 'wc_stripe_charge_pending_order_status', 'on-hold', $intent->charges->data[0], $order ) );
			$this->payment_method->save_order_meta( $order, $intent->charges->data[0] );

			return (object) array(
				'complete_payment' => false,
				'redirect'         => $this->payment_method->get_return_url( $order ),
			);
		}
		if ( in_array( $intent->status, array( 'requires_action', 'requires_payment_method', 'requires_source_action', 'requires_source' ), true ) ) {
			/**
			 * Allow 3rd party code to alter the order status of an asynchronous payment method.
			 * The plugin uses the charge.pending event to set the order's status to on-hold.
			 */
			if ( ! $this->payment_method->synchronous ) {
				$status = apply_filters( 'wc_stripe_asynchronous_payment_method_order_status', 'pending', $order, $intent );
				if ( 'pending' !== $status ) {
					$order->update_status( $status );
				}
			}

			return (object) array(
				'complete_payment' => false,
				'redirect'         => $this->payment_method->get_payment_intent_checkout_url( $intent, $order ),
			);
		}
	}

	public function scheduled_subscription_payment( $amount, $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = trim( $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $order ) );

		if ( ( $customer = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $order ) ) ) {
			$args['customer'] = $customer;
		}

		$retry_mgr = \PaymentPlugins\Stripe\WooCommerceSubscriptions\RetryManager::instance();
		$intent    = $this->gateway->mode( $order )->paymentIntents->create( $args );
		if ( is_wp_error( $intent ) ) {
			if ( $retry_mgr->should_retry( $order, $this->gateway, $intent, $args ) ) {
				return $this->scheduled_subscription_payment( $amount, $order );
			}

			return $intent;
		} else {
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );

			$charge = $intent->charges->data[0];

			if ( in_array( $intent->status, array( 'succeeded', 'requires_capture', 'processing' ) ) ) {
				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::process_pre_order_payment()
	 */
	public function process_pre_order_payment( $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $order );

		if ( ( $customer = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $order ) ) ) {
			$args['customer'] = $customer;
		}

		$intent = $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $intent ) ) {
			return $intent;
		} else {
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $intent->id );

			$charge = $intent->charges->data[0];

			if ( in_array( $intent->status, array( 'succeeded', 'requires_capture', 'processing' ) ) ) {
				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 * Compares the order's saved intent to the order's attributes.
	 * If there is a delta, then the payment intent can be updated. The intent should
	 * only be updated if this is the checkout page.
	 *
	 * @param WC_Order $order
	 */
	public function can_update_payment_intent( $order, $intent = null ) {
		$result = true;
		if ( ! $this->update_payment_intent && ( defined( WC_Stripe_Constants::WOOCOMMERCE_STRIPE_ORDER_PAY ) || ! is_checkout() || defined( WC_Stripe_Constants::REDIRECT_HANDLER ) || defined( WC_Stripe_Constants::PROCESSING_PAYMENT ) ) ) {
			$result = false;
		} else {
			$intent = ! $intent ? $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT ) : $intent;
			if ( $intent ) {
				$order_hash  = implode(
					'_',
					array(
						wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() ),
						strtolower( $order->get_currency() ),
						$this->get_payment_method_charge_type(),
						wc_stripe_get_customer_id( $order->get_user_id() ),
						$this->payment_method->get_payment_method_from_request()
					)
				);
				$intent_hash = implode(
					'_',
					array(
						$intent['amount'],
						$intent['currency'],
						$intent['capture_method'],
						$intent['customer'],
						isset( $intent['payment_method']['id'] ) ? $intent['payment_method']['id'] : ''
					)
				);
				$result      = $order_hash !== $intent_hash || ! in_array( $this->payment_method->get_payment_method_type(), $intent['payment_method_types'] );
			}
		}

		return apply_filters( 'wc_stripe_can_update_payment_intent', $result, $intent, $order );;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_payment_intent_args( $order, $new = true, $intent = null ) {
		$this->add_general_order_args( $args, $order );

		$args['capture_method'] = $this->get_payment_method_charge_type();
		if ( ( $statement_descriptor = stripe_wc()->advanced_settings->get_option( 'statement_descriptor' ) ) ) {
			$args['statement_descriptor'] = WC_Stripe_Utils::sanitize_statement_descriptor( $statement_descriptor );
		}
		if ( $new ) {
			$args['confirmation_method'] = $this->payment_method->get_confirmation_method( $order );
			$args['confirm']             = false;
		} else {
			if ( $intent && $intent['status'] === 'requires_action' ) {
				unset( $args['capture_method'] );
			}
			if ( isset( $intent['payment_method']['type'] ) && $intent['payment_method']['type'] === 'link' ) {
				/**
				 * Unset the payment method so it's not updated by Stripe. We don't want to update the payment method
				 * if it exists because it already contains the Link mandate.
				 */
				unset( $args['payment_method'] );
			}
			if ( $intent && $intent->status === 'requires_action' ) {
				/**
				 * The statement_descriptor can't be updated when the intent's status is requires_action
				 */
				unset( $args['statement_descriptor'] );
			}
		}

		if ( stripe_wc()->advanced_settings->is_email_receipt_enabled() && ( $email = $order->get_billing_email() ) ) {
			$args['receipt_email'] = $email;
		}

		if ( ( $customer_id = wc_stripe_get_customer_id( $order->get_customer_id() ) ) ) {
			$args['customer'] = $customer_id;
		}

		if ( $this->payment_method->should_save_payment_method( $order )
		     || ( $this->payment_method->supports( 'add_payment_method' )
		          && apply_filters( 'wc_stripe_force_save_payment_method',
					false,
					$order,
					$this->payment_method ) )
		) {
			$args['setup_future_usage'] = 'off_session';
		}

		$args['payment_method_types'][] = $this->payment_method->get_payment_method_type();

		// if there is a payment method attached already, then ensure the payment_method_type
		// associated with that attached payment_method is included.
		if ( $intent && ! empty( $intent->payment_method ) && \is_array( $intent->payment_method_types ) ) {
			$args['payment_method_types'] = array_values( array_unique( array_merge( $args['payment_method_types'], $intent->payment_method_types ) ) );
		}

		$this->payment_method->add_stripe_order_args( $args, $order );

		/**
		 * @param array                    $args
		 * @param WC_Order                 $order
		 * @param WC_Stripe_Payment_Intent $this
		 */
		return apply_filters( 'wc_stripe_payment_intent_args', $args, $order, $this );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::capture_charge()
	 */
	public function capture_charge( $amount, $order ) {
		$payment_intent = $this->payment_method->get_order_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $order );
		if ( empty( $payment_intent ) ) {
			$charge         = $this->gateway->charges->mode( wc_stripe_order_mode( $order ) )->retrieve( $order->get_transaction_id() );
			$payment_intent = $charge->payment_intent;
			$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT_ID, $payment_intent );
			$order->save();
		}
		$params = apply_filters( 'wc_stripe_payment_intent_capture_args', array( 'amount_to_capture' => wc_stripe_add_number_precision( $amount, $order->get_currency() ) ), $amount, $order );

		$result = $this->gateway->mode( wc_stripe_order_mode( $order ) )->paymentIntents->capture( $payment_intent, $params );
		if ( ! is_wp_error( $result ) ) {
			return $result->charges->data[0];
		}

		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::void_charge()
	 */
	public function void_charge( $order ) {
		// fetch the intent and check its status
		$payment_intent = $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->retrieve( $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) );
		if ( is_wp_error( $payment_intent ) ) {
			return $payment_intent;
		}
		$statuses = array( 'requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action' );
		if ( 'canceled' !== $payment_intent->status ) {
			if ( in_array( $payment_intent->status, $statuses ) ) {
				return $this->gateway->paymentIntents->mode( wc_stripe_order_mode( $order ) )->cancel( $payment_intent->id );
			} elseif ( 'succeeded' === $payment_intent->status ) {
				return $this->process_refund( $order, $order->get_total() - $order->get_total_refunded() );
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::get_payment_method_from_charge()
	 */
	public function get_payment_method_from_charge( $charge ) {
		return $charge->payment_method;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::add_order_payment_method()
	 */
	public function add_order_payment_method( &$args, $order ) {
		$args['payment_method'] = $this->payment_method->get_payment_method_from_request();
		if ( empty( $args['payment_method'] ) ) {
			unset( $args['payment_method'] );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function can_use_payment_intent( $order ) {
		$intent         = $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT );
		$session_intent = (array) WC_Stripe_Utils::get_payment_intent_from_session();
		if ( $session_intent ) {
			if ( ! $intent || $session_intent['id'] !== $intent['id'] ) {
				$intent = $session_intent;
			}
		}
		$intent = $intent ? $this->gateway->paymentIntents->retrieve( $intent['id'], apply_filters( 'wc_stripe_payment_intent_retrieve_args', array( 'expand' => array( 'payment_method' ) ), $order, $intent['id'] ) ) : false;
		if ( $intent && ! is_wp_error( $intent ) ) {
			// If an intent is cancelled, then it's likely that it timed out and can't be used.
			if ( $intent->status === 'canceled' ) {
				$intent = false;
			} else {
				if ( \in_array( $intent->status, array( 'succeeded', 'requires_capture', 'processing' ) ) && ! defined( WC_Stripe_Constants::REDIRECT_HANDLER ) ) {
					/**
					 * If the status is succeeded, and the order ID on the intent doesn't match this checkout's order ID, we know this is
					 * a previously processed intent and so should not be used.
					 */
					if ( isset( $intent->metadata['order_id'] ) && $intent->metadata['order_id'] != $order->get_id() ) {
						$intent = false;
					}
				} elseif ( $intent['confirmation_method'] != $this->payment_method->get_confirmation_method( $order ) ) {
					$intent = false;
				}
			}

			// compare the active environment to the order's environment
			$mode = wc_stripe_order_mode( $order );
			if ( $mode && $mode !== wc_stripe_mode() ) {
				$intent = false;
			}
		} else {
			$intent = false;
		}

		return $intent;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Stripe_Payment::can_void_charge()
	 */
	public function can_void_order( $order ) {
		return $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID );
	}

	public function set_update_payment_intent( $bool ) {
		$this->update_payment_intent = $bool;
	}

	public function destroy_session_data() {
		WC_Stripe_Utils::delete_payment_intent_to_session();
	}

	/**
	 * @param \WP_Error $error
	 * @param \WC_Order $order
	 */
	public function should_retry_payment( $error, $order ) {
		$result      = false;
		$data        = $error->get_error_data();
		$delete_data = function () use ( $order ) {
			WC_Stripe_Utils::delete_payment_intent_to_session();
			$order->delete_meta_data( WC_Stripe_Constants::PAYMENT_INTENT );
		};
		if ( $this->retry_count < 1 ) {
			if ( $data && isset( $data['payment_intent'] ) ) {
				if ( isset( $data['payment_intent']['status'] ) ) {
					$result = in_array( $data['payment_intent']['status'], array( 'succeeded', 'requires_capture' ), true );
					if ( $result ) {
						$delete_data();
					}
				}
			} elseif ( isset( $data['code'] ) ) {
				if ( $data['code'] === 'resource_missing' ) {
					$delete_data();
					$result = true;
				}
			}
			if ( $result ) {
				$this->retry_count += 1;
			}
		}

		return $result;
	}

	/**
	 * @param \WP_Error $error
	 * @param \WC_Order $order
	 */
	public function post_payment_process_error_handling( $error, $order ) {
		$data = $error->get_error_data();
		if ( isset( $data['payment_intent'] ) ) {
			WC_Stripe_Utils::save_payment_intent_to_session( $data['payment_intent'], $order );
		}
	}

}
