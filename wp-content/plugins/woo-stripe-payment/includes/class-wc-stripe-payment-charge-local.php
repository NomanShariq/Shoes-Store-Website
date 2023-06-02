<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.1
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Payment_Charge_Local extends WC_Stripe_Payment_Charge {

	/**
	 *
	 * @param WC_Order $order
	 */
	public function process_payment( $order ) {

		/**
		 * If there is no order lock, then this is not being processed via a webhook
		 */
		if ( ! $this->payment_method->has_order_lock( $order ) ) {
			try {
				if ( ( $source_id = $this->payment_method->get_new_source_token() ) ) {
					// source was created client side.
					$source = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->retrieve( $source_id );
					$this->save_order_data( $source_id, $order );
					if ( is_wp_error( $source ) ) {
						return $source;
					}

					// update the source's metadata with the order id
					if ( 'pending' === $source->status ) {
						$source = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->update( $source_id, $this->payment_method->get_update_source_args( $order ) );
					}
				} else {
					if ( $this->payment_method->use_saved_source() ) {
						$source_id = $this->payment_method->get_saved_source_id();
						$source    = $source = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->retrieve( $source_id );
					} else {
						// create the source
						$args                         = $this->payment_method->get_source_args( $order );
						$args['metadata']['order_id'] = $order->get_id();
						$args['metadata']['created']  = time();
						$source                       = $this->gateway->sources->mode( wc_stripe_order_mode( $order ) )->create( $args );

					}
				}

				if ( is_wp_error( $source ) ) {
					// if the error was caused by attempting to update a chargeable source, this means the
					// source status changed in Stripe while plugin code was processing. Redirect to thank you page
					// and let webhook process the order.
					if ( false !== strpos( strtolower( $source->get_error_message() ), 'update of a chargeable source for single use not allowed' ) ) {

						return (object) array(
							'complete_payment' => false,
							'redirect'         => $this->payment_method->get_return_url( $order )
						);
					}
					throw new Exception( $source->get_error_message() );
				}
				$this->save_order_data( $source->id, $order );

				/**
				 * If source is chargeable, then proceed with processing it.
				 */
				if ( $source->status === 'chargeable' ) {
					$this->payment_method->set_order_lock( $order );
					$this->payment_method->set_new_source_token( $source->id );

					return $this->process_payment( $order );
				}

				return (object) array(
					'complete_payment' => false,
					'redirect'         => $this->payment_method->get_source_redirect_url( $source, $order ),
				);
			} catch ( Exception $e ) {
				return new WP_Error( 'source-error', $e->getMessage() );
			}
		} else {
			/**
			 * There is an order lock so this order is ready to be processed.
			 */
			return parent::process_payment( $order );
		}
	}

	/**
	 * @param string $source_id
	 * @param WC_Order $order
	 */
	private function save_order_data( $source_id, $order ) {
		$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
		$order->update_meta_data( WC_Stripe_Constants::SOURCE_ID, $source_id );
		$order->save();
	}
}
