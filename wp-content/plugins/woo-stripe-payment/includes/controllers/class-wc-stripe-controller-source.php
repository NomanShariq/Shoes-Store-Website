<?php
defined( 'ABSPATH' ) || exit();

/**
 * Controller class that perfors cart operations for client side requests.
 *
 * @author PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Source extends WC_Stripe_Rest_Controller {

	protected $namespace = 'source';

	public function register_routes() {
		register_rest_route( $this->rest_uri(), 'update', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'update_source' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'source_id'     => array( 'required' => true ),
				'client_secret' => array( 'required' => true ),
				'updates'       => array( 'required' => true ),
				'gateway_id'    => array( 'required', true )
			)
		) );
		register_rest_route(
			$this->rest_uri(), 'order/source', array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_order_source' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function update_source( $request ) {

		try {
			/**
			 * @var WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];

			// fetch the source and check client token and status
			$source = $payment_method->payment_object->get_gateway()->sources->retrieve( $request['source_id'] );

			if ( is_wp_error( $source ) ) {
				throw new Exception( __( 'Error updating source.', 'woo-stripe-payment' ) );
			}
			if ( $source->status !== 'chargeable' ) {
				if ( ! hash_equals( $source->client_secret, $request['client_secret'] ) ) {
					throw new Exception( __( 'You do not have permission to update this source.', 'woo-stripe-payment' ) );
				}
				//update the source
				$updates = $request['updates'];
				if ( WC()->cart ) {
					$updates['amount'] = wc_stripe_add_number_precision( WC()->cart->total, strtoupper( $source->currency ) );
					if ( 'stripe_klarna' === $payment_method->id ) {
						unset( $updates['source_order']['items'] );
						/**
						 * @var WC_Payment_Gateway_Stripe_Klarna $payment_method
						 */
						$payment_method->add_klarna_line_items_from_cart( $updates, WC()->cart, strtoupper( $source->currency ) );
					}
				}
				$source = $payment_method->payment_object->get_gateway()->sources->update( $request['source_id'], $updates );
				if ( is_wp_error( $source ) ) {
					throw new Exception( __( 'Error updating source.', 'woo-stripe-payment' ) );
				}
			}

			return rest_ensure_response( array( 'source' => $source->toArray() ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'source-error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	/**
	 * Deletes a source from an order if the order exists.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 * @since 3.1.7
	 */
	public function delete_order_source( $request ) {
		$order_id = WC()->session->get( 'order_awaiting_payment', null );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			$order->delete_meta_data( WC_Stripe_Constants::SOURCE_ID );
			$order->save();
		}

		return rest_ensure_response( array( 'success' => true ) );
	}
}