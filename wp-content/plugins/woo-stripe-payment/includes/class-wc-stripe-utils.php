<?php

/**
 * @since 3.3.13
 */
class WC_Stripe_Utils {

	/**
	 * @param WC_Order $data
	 */
	public static function display_fee( $order ) {
		return self::display_amount( 'fee', $order );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function display_net( $order ) {
		return self::display_amount( 'net', $order );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private static function display_amount( $type, $order ) {
		$payment_balance = self::get_payment_balance( $order );
		if ( $payment_balance && isset( $payment_balance->{$type}, $payment_balance->currency ) && is_numeric( $payment_balance->{$type} ) ) {
			if ( $type === 'fee' ) {
				$amount = - 1 * $payment_balance->fee;
			} else {
				$amount = $payment_balance->net;
			}

			return wc_price( $amount, array( 'currency' => $payment_balance->currency ) );
		}

		return '';
	}

	/**
	 * @param \Stripe\BalanceTransaction $balance_transaction
	 * @param \WC_Order                  $order
	 *
	 * @return void
	 */
	public static function create_payment_balance_from_balance_transaction( $balance_transaction, $order ) {
		$display_order_currency    = stripe_wc()->advanced_settings->is_display_order_currency();
		$exchange_rate             = $balance_transaction->exchange_rate === null ? 1 : $balance_transaction->exchange_rate;
		$net                       = $display_order_currency ? wc_format_decimal( $balance_transaction->net / $exchange_rate, 4 ) : $balance_transaction->net;
		$fee                       = $display_order_currency ? wc_format_decimal( $balance_transaction->fee / $exchange_rate, 4 ) : $balance_transaction->fee;
		$currency                  = $display_order_currency ? $order->get_currency() : strtoupper( $balance_transaction->currency );
		$payment_balance           = new WC_Stripe_Payment_Balance( $order );
		$payment_balance->currency = $currency;
		$payment_balance->fee      = wc_stripe_remove_number_precision( $fee, $currency, true, 4 );
		$payment_balance->net      = wc_stripe_remove_number_precision( $net, $currency, true, 4 );

		return $payment_balance;
	}

	/**
	 * @param \Stripe\Charge $charge
	 * @param \WC_Order      $order
	 * @param bool           $save
	 */
	public static function add_balance_transaction_to_order( $charge, $order, $save = false ) {
		if ( isset( $charge->balance_transaction ) && is_object( $charge->balance_transaction ) ) {
			$display_order_currency = stripe_wc()->advanced_settings->is_display_order_currency();
			$balance_transaction    = $charge->balance_transaction;
			$exchange_rate          = $balance_transaction->exchange_rate === null ? 1 : $balance_transaction->exchange_rate;
			$amount_refunded        = $display_order_currency ? $charge->amount_refunded : $charge->amount_refunded * $exchange_rate;
			// the balance_transaction_net already has the fee deducted from it.
			$net                       = $display_order_currency ? $balance_transaction->net / $exchange_rate : $balance_transaction->net;
			$net                       = wc_format_decimal( $net - $amount_refunded, 4 );
			$fee                       = $display_order_currency ? wc_format_decimal( $balance_transaction->fee / $exchange_rate, 4 ) : $balance_transaction->fee;
			$currency                  = $display_order_currency ? $order->get_currency() : strtoupper( $balance_transaction->currency );
			$payment_balance           = new WC_Stripe_Payment_Balance( $order );
			$payment_balance->currency = $currency;
			$payment_balance->fee      = wc_stripe_remove_number_precision( $fee, $currency, true, 4 );
			$payment_balance->net      = wc_stripe_remove_number_precision( $net, $currency, true, 4 );
			$payment_balance->refunded = wc_stripe_remove_number_precision( $amount_refunded, $currency, true, 4 );
			if ( $charge->refunds->count() > 0 ) {
				foreach ( $charge->refunds->data as $refund ) {
					/**
					 * @var \Stripe\Refund $refund
					 */
					if ( is_object( $refund->balance_transaction ) ) {
						self::update_balance_transaction( $refund->balance_transaction, $order, false, $payment_balance );
					}
				}
			}
			$payment_balance->update_meta_data( $save );

			return $payment_balance;
		}
	}

	/**
	 * @param \Stripe\BalanceTransaction $balance_transaction
	 * @param \WC_Order                  $order
	 */
	public static function update_balance_transaction( $balance_transaction, $order, $save = false, $payment_balance = null ) {
		if ( $balance_transaction->reporting_category === 'partial_capture_reversal' ) {
			$payment_balance = $payment_balance ? $payment_balance : self::get_payment_balance( $order );
			if ( $payment_balance ) {
				$exchange_rate          = $balance_transaction->exchange_rate === null ? 1 : $balance_transaction->exchange_rate;
				$display_order_currency = stripe_wc()->advanced_settings->is_display_order_currency() && $payment_balance->currency !== strtoupper( $balance_transaction->currency );
				$currency               = $display_order_currency ? $order->get_currency() : strtoupper( $balance_transaction->currency );
				// fee is negative here since it's a reversal, that's why for net we subtract and for fee we add.
				$fee                  = $display_order_currency ? $balance_transaction->fee / $exchange_rate : $balance_transaction->fee;
				$fee                  = wc_stripe_remove_number_precision( $fee, $currency, true, 4 );
				$payment_balance->net = $payment_balance->net - $fee;
				$payment_balance->fee = $payment_balance->fee + $fee;
				$payment_balance->update_meta_data( $save );
			}
		}
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return \WC_Stripe_Payment_Balance|null
	 */
	public static function get_payment_balance( $order ) {
		return new WC_Stripe_Payment_Balance( $order );
	}

	/**
	 * @param $value
	 *
	 * @since 3.3.14
	 * @return string
	 */
	public static function sanitize_statement_descriptor( $value ) {
		return trim( str_replace( array( '<', '>', '\\', '\'', '"', '*' ), '', $value ) );
	}

	/**
	 * Sanitizes intent data before it's stored.
	 *
	 * @param \Stripe\PaymentIntent|\Stripe\SetupIntent $intent
	 */
	public static function sanitize_intent( $intent ) {
		return $intent;
	}

	public static function get_payment_intent_from_session() {
		$session        = WC()->session;
		$payment_intent = null;
		if ( $session ) {
			$payment_intent = WC()->session->get( \WC_Stripe_Constants::PAYMENT_INTENT, null );
			if ( $payment_intent ) {
				$payment_intent = (object) $payment_intent;
			}
		}

		return $payment_intent;
	}

	public static function save_payment_intent_to_session( $payment_intent, $order = null ) {
		if ( WC()->session ) {
			$data = \is_array( $payment_intent ) ? $payment_intent : $payment_intent->toArray();
			WC()->session->set( \WC_Stripe_Constants::PAYMENT_INTENT, $data );
			$order_id = WC()->session->get( 'order_awaiting_payment', null );
			if ( $order || $order_id ) {
				$order = ! $order ? wc_get_order( absint( $order_id ) ) : $order;
				if ( $order ) {
					$order->update_meta_data( \WC_Stripe_Constants::PAYMENT_INTENT, $data );
					$order->save();
				}
			}
		}
	}

	public static function delete_payment_intent_to_session() {
		if ( WC()->session ) {
			unset( WC()->session->{\WC_Stripe_Constants::PAYMENT_INTENT} );
		}
	}

	public static function redirect_url_has_hash( $url ) {
		return preg_match( '/^#response=(.*)/', $url );
	}

	public static function parse_url_hash( $url ) {
		preg_match( '/^#response=(.*)/', $url, $matches );

		return json_decode( base64_decode( rawurldecode( $matches[1] ) ) );
	}

	public static function is_setup_intent( $intent ) {
		return self::is_intent_type( 'seti_', $intent );
	}

	public static function is_payment_intent( $intent ) {
		return self::is_intent_type( 'pi_', $intent );
	}

	private static function is_intent_type( $prefix, $intent ) {
		if ( ! $intent ) {
			return false;
		}
		if ( is_object( $intent ) && isset( $intent->id ) ) {
			return strpos( $intent->id, $prefix ) !== false;
		} elseif ( is_array( $intent ) && isset( $intent['id'] ) ) {
			return strpos( $intent['id'], $prefix ) !== false;
		}

		return false;
	}

	public static function is_intent_mode_equal( $intent, $mode = null ) {
		if ( $intent ) {
			$intent = (object) $intent;
			$mode   = ! $mode ? wc_stripe_mode() : $mode;
			if ( $mode === WC_Stripe_Constants::TEST ) {
				return ! $intent->livemode;
			}

			return $intent->livemode;
		}

		return false;
	}

	public static function validate_account_access( $betas = array(), $mode = 'test' ) {
		$betas  = array_merge( array( '2020-08-27' ), $betas );
		$result = wp_remote_post(
			'https://api.stripe.com/v1/payment_methods',
			array(
				'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				'body'    => array(
					'key'             => wc_stripe_get_publishable_key( $mode ),
					'_stripe_version' => sprintf( implode( ';', array_fill( 0, count( $betas ), '%s' ) ), ...$betas ),
					'type'            => 'card',
					'card'            => array(
						'number'    => '4242424242424242',
						'exp_month' => 12,
						'exp_year'  => 2030,
						'cvc'       => 314
					),
					'metadata'        => array(
						'origin' => 'API Settings connection test'
					)
				),
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$body = json_decode( wp_remote_retrieve_body( $result ), true );
			if ( isset( $body['error']['message'] ) && strpos( $body['error']['message'], 'server_side_confirmation_beta=v1' ) !== false ) {
				return false;
			}
		}

		return true;
	}

	public static function get_order_from_payment_intent( $payment_intent ) {
		global $wpdb;

		if ( isset( $payment_intent->metadata->order_id ) ) {
			$order = wc_get_order( wc_stripe_filter_order_id( $payment_intent->metadata->order_id, $payment_intent ) );
			if ( $order && $order->get_meta( WC_Stripe_Constants::PAYMENT_INTENT_ID ) === $payment_intent->id ) {
				return $order;
			}
		}

		if ( \PaymentPlugins\Stripe\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
			$order_ids = wc_get_orders( [
				'type'       => 'shop_order',
				'limit'      => 1,
				'return'     => 'ids',
				'meta_query' => [
					[
						'key'   => WC_Stripe_Constants::PAYMENT_INTENT_ID,
						'value' => $payment_intent->id
					]
				]
			] );
			$order_id  = ! empty( $order_ids ) ? $order_ids[0] : null;
		} else {
			$order_id =
				$wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts AS posts LEFT JOIN $wpdb->postmeta AS postmeta ON posts.ID = postmeta.post_id WHERE posts.post_type = %s AND postmeta.meta_key = %s AND postmeta.meta_value = %s LIMIT 1",
					'shop_order',
					WC_Stripe_Constants::PAYMENT_INTENT_ID,
					$payment_intent->id ) );
		}

		if ( $order_id ) {
			return wc_get_order( $order_id );
		}


		return null;
	}

	public static function get_order_from_charge( $charge ) {
		if ( isset( $charge->metadata['order_id'] ) ) {
			$order = wc_get_order( absint( $charge->metadata['order_id'] ) );
		} else {
			// charge didn't have order ID for whatever reason, so get order from charge ID
			$order = wc_stripe_get_order_from_transaction( $charge->id );
		}

		return $order;
	}

}