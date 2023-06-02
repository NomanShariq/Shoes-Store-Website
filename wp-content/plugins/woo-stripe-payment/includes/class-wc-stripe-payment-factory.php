<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @since 3.1.1
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Payment_Factory {

	private static $classes = array(
		'charge'         => 'WC_Stripe_Payment_Charge',
		'payment_intent' => 'WC_Stripe_Payment_Intent',
		'local_charge'   => 'WC_Stripe_Payment_Charge_Local',
	);

	/**
	 *
	 * @param string $type
	 * @param WC_Payment_Gateway_Stripe $payment_method
	 * @param WC_Stripe_Gateway $gateway
	 */
	public static function load( $type, $payment_method, $gateway ) {
		$classes = apply_filters( 'wc_stripe_payment_classes', self::$classes );
		if ( ! isset( $classes[ $type ] ) ) {
			throw Exception( 'No class defined for type ' . $type );
		}
		$classname = $classes[ $type ];

		$args = func_get_args();

		if ( count( $args ) > 3 ) {
			$args     = array_slice( $args, 3 );
			$instance = new $classname( $payment_method, $gateway, ...$args );
		} else {
			$instance = new $classname( $payment_method, $gateway );
		}
		return $instance;
	}
}
