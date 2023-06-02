<?php

namespace PaymentPlugins;

class WC_Stripe_Admin_Meta_Box_Product_Data {

	private static $_gateways = array();

	private static $_options = array();

	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'output_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
	}

	public static function product_data_tabs( $tabs ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$tabs['stripe'] = array(
				'label'    => __( 'Stripe Settings', 'woo-stripe-payment' ),
				'target'   => 'stripe_product_data',
				'class'    => array( 'hide_if_external' ),
				'priority' => 100,
			);
		}

		return $tabs;
	}

	public static function output_panel() {
		global $product_object;

		self::init_gateways( $product_object );
		if ( current_user_can( 'manage_woocommerce' ) ) {
			include 'views/html-product-data.php';
		}
	}

	private static function init_gateways( $product ) {
		$order = $product->get_meta( \WC_Stripe_Constants::PRODUCT_GATEWAY_ORDER );
		$order = ! $order ? array() : $order;
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway->supports( 'wc_stripe_product_checkout' ) ) {
				if ( isset( $order[ $gateway->id ] ) ) {
					self::$_gateways[ $order[ $gateway->id ] ] = $gateway;
				} else {
					self::$_gateways[] = $gateway;
				}
				self::$_options[ $gateway->id ] = new \WC_Stripe_Product_Gateway_Option( $product, $gateway );
			}
		}
		ksort( self::$_gateways );
	}

	private static function get_product_option( $gateway_id ) {
		return self::$_options[ $gateway_id ];
	}

	private static function get_payment_gateways() {
		$gateways = array();
		foreach ( self::$_gateways as $gateway ) {
			$gateways[ $gateway->id ] = $gateway;
		}

		return $gateways;
	}

	/**
	 *
	 * @param \WC_Product $product
	 */
	public static function save( $product ) {
		// only update the settings if something has been changed.
		if ( empty( $_POST['wc_stripe_update_product'] ) ) {
			return;
		}
		$loop  = 0;
		$order = array();
		self::init_gateways( $product );
		$payment_gateways = self::get_payment_gateways();

		if ( isset( $_POST['stripe_gateway_order'] ) ) {
			foreach ( $_POST['stripe_gateway_order'] as $i => $gateway ) {
				$order[ $gateway ] = $loop;
				if ( isset( $_POST['stripe_capture_type'] ) ) {
					self::get_product_option( $gateway )->set_option( 'charge_type', wc_clean( $_POST['stripe_capture_type'][ $i ] ) );
					self::get_product_option( $gateway )->save();
				}
				$loop ++;
			}
		}
		if ( isset( $_POST['_stripe_button_position'] ) ) {
			$product->update_meta_data( '_stripe_button_position', wc_clean( $_POST['_stripe_button_position'] ) );
		}
		$product->update_meta_data( \WC_Stripe_Constants::PRODUCT_GATEWAY_ORDER, $order );
	}

}

\PaymentPlugins\WC_Stripe_Admin_Meta_Box_Product_Data::init();
