<?php
defined( 'ABSPATH' ) || exit();

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/traits/wc-stripe-payment-token-traits.php' );

/**
 *
 * @since 3.0.0
 * @author PaymentPlugins
 * @package Stripe/Abstract
 *
 */
abstract class WC_Payment_Token_Stripe extends WC_Payment_Token {

	protected $has_expiration = false;

	protected $object_type = 'payment_token';

	/**
	 *
	 * @since 3.1.0
	 * @var string
	 */
	protected $stripe_payment_type;

	protected $extra_data = array(
		'format'      => '',
		'environment' => 'production',
		'brand'       => '',
		'customer_id' => '',
	);

	protected $stripe_data = array();

	/**
	 * The format of the payment method
	 *
	 * @var string
	 */
	protected $format = '';

	public function __construct( $token = '' ) {
		// use reflection to merge all extra data keys.
		$this->extra_data = array_merge( $this->extra_data, $this->get_stripe_data( $this ) );
		parent::__construct( $token );
	}

	/**
	 *
	 * @param mixed $details
	 */
	public abstract function details_to_props( $details );

	public function set_format( $value ) {
		$this->format = $value;
	}

	public function set_environment( $value ) {
		$this->set_prop( 'environment', $value );
	}

	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', $value );
	}

	public function get_format() {
		return $this->format;
	}

	public function get_environment() {
		return $this->get_prop( 'environment' );
	}

	public function get_customer_id() {
		return $this->get_prop( 'customer_id' );
	}

	/**
	 * Return a human readable representation of the payment method.
	 *
	 * @param string $format
	 *
	 * @retun string
	 */
	public function get_payment_method_title( $format = '' ) {
		$format = empty( $format ) ? $this->get_format() : $format;
		$format = $this->get_formats()[ $format ]['format'];
		$data   = $this->get_props_data();

		return apply_filters( 'wc_stripe_payment_method_title', str_replace( array_keys( $data ), $data, $format ), $this );
	}

	public function get_props_data() {
		$data = array();
		foreach ( $this->extra_data as $k => $v ) {
			if ( method_exists( $this, "get_{$k}" ) ) {
				$data[ '{' . $k . '}' ] = $this->{"get_$k"}();
			} else {
				$data[ '{' . $k . '}' ] = $this->get_prop( $k );
			}
		}
		$data['{short_title}'] = $this->get_basic_payment_method_title();

		return $data;
	}

	/**
	 * Returns an array of merged attributes comprised of the $stripe_data property.
	 *
	 * @param object $instance
	 */
	public function get_stripe_data( $instance ) {
		$data = array();
		try {
			$class = new ReflectionClass( $instance );
			$props = $class->getDefaultProperties();
			if ( isset( $props['stripe_data'] ) ) {
				$data = $props['stripe_data'];
			}
			if ( is_subclass_of( get_parent_class( $instance ), 'WC_Payment_Token_Stripe' ) ) {
				$data = array_merge( $this->get_stripe_data( get_parent_class( $instance ) ), $data );
			}

			return $data;
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Return a json array of data that represents the object.
	 *
	 * @return array
	 */
	public function to_json() {
		return apply_filters(
			'wc_stripe_get_' . $this->object_type . '_json',
			array(
				'id'      => $this->get_id(),
				'type'    => $this->get_type(),
				'token'   => $this->get_token(),
				'title'   => $this->get_payment_method_title(),
				'gateway' => $this->get_gateway_id(),
			)
		);
	}

	/**
	 * Return formats used by the token to display a human readable title.
	 */
	public abstract function get_formats();

	public function get_brand( $context = 'view' ) {
		return wc_get_credit_card_type_label( $this->get_prop( 'brand', $context ) );
	}

	public function set_brand( $value ) {
		$this->set_prop( 'brand', $value );
	}

	public function get_html_classes() {
		return '';
	}

	public function has_expiration() {
		return $this->has_expiration;
	}

	/**
	 *
	 * @since 3.1.0
	 */
	public abstract function delete_from_stripe();

	/**
	 *
	 * @since 3.1.0
	 */
	public abstract function save_payment_method();

	public static function token_exists( $token_id, $user_id ) {
		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s AND gateway_id LIKE %s AND user_id = %d", $token_id, '%stripe_%', $user_id ) );

		return $count > 0;
	}

	/**
	 * @return string
	 * @since 3.2.12
	 */
	public function get_basic_payment_method_title() {
		return '';
	}
}
