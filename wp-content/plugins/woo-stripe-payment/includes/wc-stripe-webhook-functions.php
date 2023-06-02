<?php

defined( 'ABSPATH' ) || exit();

/**
 * Processes the charge via webhooks for local payment methods like P24, EPS, etc.
 *
 * @param \Stripe\Source  $source
 * @param WP_REST_Request $request
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_process_source_chargeable( $source, $request ) {
	if ( isset( $source->metadata['order_id'] ) ) {
		$order = wc_get_order( wc_stripe_filter_order_id( $source->metadata['order_id'], $source ) );
	} else {
		// try finding order using source.
		$order = wc_stripe_get_order_from_source_id( $source->id );
	}
	if ( ! $order ) {
		/**
		 * If the order ID metadata is empty, it's possible the source became chargeable before
		 * the plugin had a chance to update the order ID. Schedule a cron job to execute in 60 seconds
		 * so the plugin can update the order ID and the charge can be processed.
		 */
		if ( empty( $source->metadata['order_id'] ) ) {
			if ( method_exists( WC(), 'queue' ) && ! doing_action( 'wc_stripe_retry_source_chargeable' ) ) {
				WC()->queue()->schedule_single( time() + MINUTE_IN_SECONDS, 'wc_stripe_retry_source_chargeable', array( $source->id ) );
			}
		} else {
			wc_stripe_log_error( sprintf( 'Could not create a charge for source %s. No order ID was found in your WordPress database.', $source->id ) );
		}

		return;
	}

	/**
	 *
	 * @var WC_Payment_Gateway_Stripe $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];

	// if the order has a transaction ID, then a charge has already been created.
	if ( $payment_method->has_order_lock( $order ) || ( $transaction_id = $order->get_transaction_id() ) ) {
		wc_stripe_log_info( sprintf( 'source.chargeable event received. Charge has already been created for order %s. Event exited.', $order->get_id() ) );

		return;
	}
	$payment_method->set_order_lock( $order );
	$payment_method->set_new_source_token( $source->id );
	$result = $payment_method->payment_object->process_payment( $order );

	if ( ! is_wp_error( $result ) && $result->complete_payment ) {
		$payment_method->payment_object->payment_complete( $order, $result->charge );
	}
}

/**
 * When the charge has succeeded, the order should be completed.
 *
 * @param \Stripe\Charge  $charge
 * @param WP_REST_Request $request
 *
 * @since   3.0.5
 * @package Stripe/Functions
 */
function wc_stripe_process_charge_succeeded( $charge, $request ) {
	// charges that belong to a payment intent can be  skipped
	// because the payment_intent.succeeded event will be called.
	if ( $charge->payment_intent ) {
		return;
	}
	$order = wc_get_order( wc_stripe_filter_order_id( $charge->metadata['order_id'], $charge ) );
	if ( ! $order ) {
		wc_stripe_log_error( sprintf( 'Could not complete payment for charge %s. No order ID %s was found in your WordPress database.', $charge->id, $charge->metadata['order_id'] ) );

		return;
	}

	/**
	 *
	 * @var WC_Payment_Gateway_Stripe $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
	/**
	 * Make sure the payment method is asynchronous because synchronous payments are handled via the source.chargeable event which processes the payment.
	 * This event is relevant for payment methods that receive a charge.succeeded event at some arbitrary amount of time
	 * after the source is chargeable.
	 */
	if ( $payment_method instanceof WC_Payment_Gateway_Stripe && ! $payment_method->synchronous ) {
		// If the order's charge status is not equal to charge status from Stripe, then complete_payment.
		if ( $order->get_meta( WC_Stripe_Constants::CHARGE_STATUS ) != $charge->status ) {
			// want to prevent plugin from processing capture_charge since charge has already been captured.
			remove_action( 'woocommerce_order_status_completed', 'wc_stripe_order_status_completed' );

			if ( stripe_wc()->advanced_settings->is_fee_enabled() ) {
				// retrieve the balance transaction
				$balance_transaction = WC_Stripe_Gateway::load()->mode( wc_stripe_order_mode( $order ) )->balanceTransactions->retrieve( $charge->balance_transaction );
				if ( ! is_wp_error( $balance_transaction ) ) {
					$charge->balance_transaction = $balance_transaction;
				}
			}
			// call payment complete so shipping, emails, etc are triggered.
			$payment_method->payment_object->payment_complete( $order, $charge );
			$order->add_order_note( __( 'Charge.succeeded webhook received. Payment has been completed.', 'woo-stripe-payment' ) );
		}
	}
}

/**
 *
 * @param \Stripe\PaymentIntent $intent
 * @param WP_REST_Request       $request
 *
 * @since   3.1.0
 * @package Stripe/Functions
 */
function wc_stripe_process_payment_intent_succeeded( $intent, $request ) {
	$order = WC_Stripe_Utils::get_order_from_payment_intent( $intent );
	if ( ! $order ) {
		wc_stripe_log_info( sprintf( 'Could not complete payment_intent.succeeded event for payment_intent %s. No order ID %s was found in your WordPress database. 
		This typically happens when you have multiple webhooks setup for the same Stripe account. This order most likely originated from a different site.', $intent->id, isset( $intent->metadata->order_id ) ? $intent->metadata->order_id : '(No Order ID in metadata)' ) );

		return;
	}

	/**
	 * @var \WC_Payment_Gateway_Stripe $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];

	if ( $payment_method instanceof WC_Payment_Gateway_Stripe_Local_Payment || ( $payment_method instanceof WC_Payment_Gateway_Stripe && ! $payment_method->synchronous ) ) {
		if ( $payment_method->has_order_lock( $order ) || $order->get_date_completed() ) {
			wc_stripe_log_info( sprintf( 'payment_intent.succeeded event received. Intent has been completed for order %s. Event exited.', $order->get_id() ) );

			return;
		}

		$payment_method->set_order_lock( $order );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_INTENT, WC_Stripe_Utils::sanitize_intent( $intent->toArray() ) );
		$result = $payment_method->payment_object->process_payment( $order );
		if ( ! is_wp_error( $result ) && $result->complete_payment ) {
			$payment_method->payment_object->payment_complete( $order, $result->charge );
			$order->add_order_note( __( 'payment_intent.succeeded webhook received. Payment has been completed.', 'woo-stripe-payment' ) );
		}
	}
}

/**
 *
 * @param \Stripe\Charge  $charge
 * @param WP_REST_Request $request
 *
 * @since   3.1.1
 * @package Stripe/Functions
 */
function wc_stripe_process_charge_failed( $charge, $request ) {
	$order = wc_get_order( wc_stripe_filter_order_id( $charge->metadata['order_id'], $charge ) );

	if ( $order ) {
		$payment_methods = WC()->payment_gateways()->payment_gateways();
		if ( isset( $payment_methods[ $order->get_payment_method() ] ) ) {
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = $payment_methods[ $order->get_payment_method() ];
			// only update order status if this is an asynchronous payment method,
			// and there is no completed date on the order. If there is a complete date it
			// means payment_complete was called on the order at some point
			if ( ! $payment_method->synchronous && ! $order->get_date_completed() ) {
				$order->update_status( apply_filters( 'wc_stripe_charge_failed_status', 'failed' ), $charge->failure_message );
			}
		}
	}
}

/**
 * Function that processes the charge.refund webhook. If the refund is created in the Stripe dashboard, a
 * refund will be created in the WC system to keep WC and Stripe in sync.
 *
 * @param \Stripe\Charge $charge
 *
 * @since 3.2.15
 */
function wc_stripe_process_create_refund( $charge ) {
	$mode  = $charge->livemode ? 'live' : 'test';
	$order = null;
	// get the order ID from the charge
	$order = WC_Stripe_Utils::get_order_from_charge( $charge );
	try {
		if ( ! $order ) {
			throw new Exception( sprintf( 'Could not match order with charge %s.', $charge->id ) );
		}
		usort( $charge->refunds->data, function ( $a, $b ) {
			// sort so refund with most recent created timestamp is first
			return $a->created < $b->created ? 1 : - 1;
		} );
		$refund = $charge->refunds->data[0];

		/**
		 * @var \Stripe\Refund $refund
		 */
		// refund was not created via WC
		if ( ! isset( $refund->metadata['order_id'], $refund->metadata['created_via'] ) ) {
			$args = array(
				'amount'         => wc_stripe_remove_number_precision( $refund->amount, $order->get_currency() ),
				'order_id'       => $order->get_id(),
				'reason'         => $refund->reason,
				'refund_payment' => false
			);
			// if the order has been fully refunded, items should be re-stocked
			if ( $order->get_total() == ( $args['amount'] + $order->get_total_refunded() ) ) {
				$args['restock_items'] = true;
				$line_items            = array();
				foreach ( $order->get_items() as $item_id => $item ) {
					$line_items[ $item_id ] = array(
						'qty' => $item->get_quantity()
					);
				}
				$args['line_items'] = $line_items;
			}
			// create the refund
			$result = wc_create_refund( $args );

			// Update the refund in Stripe with metadata
			if ( ! is_wp_error( $result ) ) {
				$client = WC_Stripe_Gateway::load( $mode );
				$order->add_order_note( sprintf( __( 'Order refunded in Stripe. Amount: %s', 'woo-stripe-payment' ), $result->get_formatted_refund_amount() ) );
				$client->refunds->update( $refund->id, array(
					'metadata' => array(
						'order_id'    => $order->get_id(),
						'created_via' => 'stripe_dashboard'
					)
				) );
				if ( stripe_wc()->advanced_settings->is_fee_enabled() ) {
					// retrieve the charge but with expanded objects so fee and net can be calculated.
					$charge = $client->charges->retrieve( $charge->id, array( 'expand' => array( 'balance_transaction', 'refunds.data.balance_transaction' ) ) );
					if ( ! is_wp_error( $charge ) ) {
						WC_Stripe_Utils::add_balance_transaction_to_order( $charge, $order, true );
					}
				}
			} else {
				throw new Exception( $result->get_error_message() );
			}
		}
	} catch ( Exception $e ) {
		wc_stripe_log_error( sprintf( 'Error processing refund webhook. Error: %s', $e->getMessage() ) );
	}
}

/**
 * @param $source_id
 *
 * @throws \Stripe\Exception\ApiErrorException
 */
function wc_stripe_retry_source_chargeable( $source_id ) {
	$source = WC_Stripe_Gateway::load()->sources->retrieve( $source_id );
	if ( ! is_wp_error( $source ) ) {
		wc_stripe_log_info( sprintf( 'Processing source.chargeable via scheduled action. Source ID %s', $source_id ) );
		wc_stripe_process_source_chargeable( $source, null );
	}
}

/**
 * @param Stripe\Dispute $dispute
 */
function wc_stripe_charge_dispute_created( $dispute ) {
	if ( stripe_wc()->advanced_settings->is_dispute_created_enabled() ) {
		$order = wc_stripe_get_order_from_transaction( $dispute->charge );
		if ( ! $order ) {
			wc_stripe_log_info( sprintf( 'No order found for charge %s. Dispute %s', $dispute->charge, $dispute->id ) );
		} else {
			$current_status = $order->get_status();
			$message        = sprintf( __( 'A dispute has been created for charge %1$s. Dispute status: %2$s.', 'woo-stripe-payment' ), $dispute->charge, strtoupper( $dispute->status ) );
			$order->update_status( apply_filters( 'wc_stripe_dispute_created_order_status', stripe_wc()->advanced_settings->get_option( 'dispute_created_status', 'on-hold' ), $dispute, $order ),
				$message );

			// update the dispute with metadata that can be used later
			WC_Stripe_Gateway::load( wc_stripe_order_mode( $order ) )->disputes->update( $dispute->id, array(
				'metadata' => array(
					'order_id'          => $order->get_id(),
					'prev_order_status' => $current_status
				)
			) );
			// @todo send an email to the admin so they know a dispute was created
		}
	}
}

/**
 * @param Stripe\Dispute $dispute
 */
function wc_stripe_charge_dispute_closed( $dispute ) {
	if ( stripe_wc()->advanced_settings->is_dispute_closed_enabled() ) {
		if ( isset( $dispute->metadata['order_id'] ) ) {
			$order = wc_get_order( absint( $dispute->metadata['order_id'] ) );
		} else {
			$order = wc_stripe_get_order_from_transaction( $dispute->charge );
		}
		if ( ! $order ) {
			return wc_stripe_log_info( sprintf( 'No order found for charge %s. Dispute %s', $dispute->charge, $dispute->id ) );
		}
		$message = sprintf( __( 'Dispute %1$s has been closed. Result: %2$s.', 'woo-stripe-payment' ), $dispute->id, $dispute->status );
		switch ( $dispute->status ) {
			case 'won':
				//set the order's status back to what it was before the dispute
				if ( isset( $dispute->metadata['prev_order_status'] ) ) {
					$status = $dispute->metadata['prev_order_status'];
				} else {
					$status = $order->needs_processing() ? 'processing' : 'completed';
				}
				$order->update_status( $dispute->metadata['prev_order_status'], $message );
				break;
			case 'lost':
				$order->update_status( apply_filters( 'wc_stripe_dispute_closed_order_status', 'failed', $dispute, $order ), $message );
		}
	}
}

/**
 * @param Stripe\Review $review
 */
function wc_stripe_review_opened( $review ) {
	if ( stripe_wc()->advanced_settings->is_review_opened_enabled() ) {
		if ( isset( $review->charge ) ) {
			$order = wc_stripe_get_order_from_transaction( $review->charge );
		} else {
			// In some cases, Stripe does not provide the charge ID in the Review object.
			$pi = $review->payment_intent;
			if ( $pi ) {
				$payment_intent = WC_Stripe_Gateway::load()->mode( $review )->paymentIntents->retrieve( $pi );
				if ( ! is_wp_error( $payment_intent ) && isset( $payment_intent->metadata['order_id'] ) ) {
					$order = wc_get_order( $payment_intent->metadata['order_id'] );
				}
			}
		}
		if ( $order ) {
			$status = $order->get_status();
			$order->update_meta_data( WC_Stripe_Constants::PREV_STATUS, $status );
			$message = sprintf( __( 'A review has been opened for charge %1$s. Reason: %2$s.', 'woo-stripe-payment' ), $review->charge, strtoupper( $review->reason ) );
			$order->update_status( apply_filters( 'wc_stripe_review_opened_order_status', 'on-hold', $review, $order ), $message );
		}
	}
}

/**
 * @param Stripe\Review $review
 */
function wc_stripe_review_closed( $review ) {
	if ( stripe_wc()->advanced_settings->is_review_closed_enabled() ) {
		if ( isset( $review->charge ) ) {
			$order = wc_stripe_get_order_from_transaction( $review->charge );
		} else {
			// In some cases, Stripe does not provide the charge ID in the Review object.
			$pi = $review->payment_intent;
			if ( $pi ) {
				$payment_intent = WC_Stripe_Gateway::load()->mode( $review )->paymentIntents->retrieve( $pi );
				if ( ! is_wp_error( $payment_intent ) && isset( $payment_intent->metadata['order_id'] ) ) {
					$order = wc_get_order( $payment_intent->metadata['order_id'] );
				}
			}
		}
		if ( $order ) {
			$status = $order->get_meta( WC_Stripe_Constants::PREV_STATUS );
			if ( ! $status ) {
				$status = $order->needs_processing() ? 'processing' : 'completed';
			}
			$order->delete_meta_data( WC_Stripe_Constants::PREV_STATUS );
			$message = sprintf( __( 'A review has been closed for charge %1$s. Reason: %2$s.', 'woo-stripe-payment' ), $review->charge, strtoupper( $review->reason ) );
			$order->update_status( $status, $message );
		}
	}
}

/**
 * @param \Stripe\PaymentIntent $payment_intent
 */
function wc_stripe_process_requires_action( $payment_intent ) {
	if ( isset( $payment_intent->metadata['gateway_id'], $payment_intent->metadata['order_id'] ) ) {
		if ( in_array( $payment_intent->metadata['gateway_id'], array( 'stripe_oxxo', 'stripe_boleto', 'stripe_konbini' ), true ) ) {
			$order = WC_Stripe_Utils::get_order_from_payment_intent( $payment_intent );
			if ( ! $order ) {
				return;
			}
			/**
			 *
			 * @var WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $payment_intent->metadata['gateway_id'] ];
			if ( $payment_method ) {
				$payment_method->process_voucher_order_status( $order );
				wc_stripe_log_info( sprintf( 'Order status processed for Voucher payment. Order ID %s. Payment Intent %s', $order->get_id(), $payment_intent->id ) );
			}
		}
	}
}

/**
 * @param Stripe\Charge $charge
 */
function wc_stripe_process_charge_pending( $charge ) {
	if ( isset( $charge->metadata['gateway_id'], $charge->metadata['order_id'] ) ) {
		$payment_methods = WC()->payment_gateways()->payment_gateways();
		$payment_method  = $charge->metadata['gateway_id'];
		$payment_method  = isset( $payment_methods[ $payment_method ] ) ? $payment_methods[ $payment_method ] : null;
		if ( $payment_method && $payment_method instanceof \WC_Payment_Gateway_Stripe && ! $payment_method->synchronous ) {
			$order = wc_get_order( wc_stripe_filter_order_id( $charge->metadata['order_id'], $charge ) );
			if ( $order ) {
				// temporary check to prevent race conditions caused by status update also occurring in
				// class-wc-stripe-payment-intent.php line 89 at the same time as the webhook being received for ach payments
				if ( $payment_method->id !== 'stripe_ach' && ! $payment_method->has_order_lock( $order ) ) {
					$payment_method->set_order_lock( $order );
					$payment_method->payment_object->payment_complete( $order, $charge );
					$payment_method->release_order_lock( $order );
				}
			}
		}
	}
}
