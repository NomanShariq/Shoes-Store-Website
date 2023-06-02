<?php

namespace PaymentPlugins\Stripe\WooCommerceSubscriptions;

class RetryManager {

	/**
	 * @var int|mixed
	 */
	private $retries = 0;

	/**
	 * @var int|mixed
	 */
	private $max_retries;

	public function __construct( $max_retries = 1 ) {
		$this->max_retries = $max_retries;
	}

	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * @param \WC_Order                                                                           $order
	 * @param \WC_Stripe_Gateway                                                                  $client
	 * @param \WP_Error|\PaymentPlugins\Stripe\WooCommerceSubscriptions\Controllers\PaymentIntent $result
	 * @param array                                                                               $params
	 *
	 * @return bool
	 */
	public function should_retry( $order, $client, $result, $params ) {
		$data = $result->get_error_data();
		if ( $this->has_retries() && isset( $data['param'], $params['customer'], $params['payment_method'] ) && 'payment_method' === $data['param'] ) {
			// check if the payment method's customer doesn't match the customer associated with the subscription
			$payment_method = $client->paymentMethods->retrieve( $params['payment_method'] );
			if ( ! is_wp_error( $payment_method ) ) {
				if ( $payment_method->customer !== $params['customer'] ) {
					$order->update_meta_data( \WC_Stripe_Constants::CUSTOMER_ID, $payment_method->customer );
					$order->save();
					$subscription = wc_get_order( $order->get_meta( '_subscription_renewal' ) );
					if ( $subscription ) {
						$subscription->update_meta_data( \WC_Stripe_Constants::CUSTOMER_ID, $payment_method->customer );
						$subscription->save();
					}
					wc_stripe_log_info( sprintf( 'Retrying payment for renewal order %s. Reason: %s', $order->get_id(), $result->get_error_message() ) );
					$this->retries += 1;

					return true;
				}
			}
		}

		return false;
	}

	private function has_retries() {
		return $this->retries < $this->max_retries;
	}

}