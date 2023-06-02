<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @since 3.1.2
 * @author PaymentPlugins
 * @package Stripe/Controllers
 *
 */
class WC_Stripe_Controller_Product_Data extends WC_Stripe_Rest_Controller {

	protected $namespace = 'product';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'gateway',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_gateway' ),
				'permission_callback' => array( $this, 'admin_permission_check' )
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'save',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save' ),
				'permission_callback' => array( $this, 'admin_permission_check' ),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function toggle_gateway( $request ) {
		$product        = wc_get_product( $request->get_param( 'product_id' ) );
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request->get_param( 'gateway_id' ) ];

		$option = new WC_Stripe_Product_Gateway_Option( $product, $payment_method );
		$option->set_option( 'enabled', ! $option->enabled() );
		$option->save();

		return rest_ensure_response( array( 'enabled' => $option->enabled() ) );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function save( $request ) {
		$gateways         = $request->get_param( 'gateways' );
		$charge_types     = $request->get_param( 'charge_types' );
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$product          = wc_get_product( $request->get_param( 'product_id' ) );
		$order            = array();
		$loop             = 0;
		foreach ( $gateways as $gateway ) {
			$order[ $gateway ] = $loop;
			$loop ++;
		}
		$product->update_meta_data( WC_Stripe_Constants::PRODUCT_GATEWAY_ORDER, $order );

		foreach ( $charge_types as $type ) {
			$option = new WC_Stripe_Product_Gateway_Option( $product, $payment_gateways[ $type['gateway'] ] );
			$option->set_option( 'charge_type', $type['value'] );
			$option->save();
		}
		$product->update_meta_data( '_stripe_button_position', $request->get_param( 'position' ) );

		$product->save();

		return rest_ensure_response( array( 'order' => $order ) );
	}
}
