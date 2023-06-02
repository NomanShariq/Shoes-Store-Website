<?php

defined( 'ABSPATH' ) || exit();

/**
 * @since 3.1.3
 *
 * @property int    $fee
 * @property int    $net
 * @property int    $refunded
 * @property string $currency
 */
class WC_Stripe_Payment_Balance {

	private $data = array();

	private $order;

	/**
	 * @param WC_Order $order
	 */
	public function __construct( $order ) {
		$this->order = $order;
		$this->data  = array(
			'currency' => $order->get_meta( WC_Stripe_Constants::STRIPE_CURRENCY ),
			'fee'      => (float) $order->get_meta( WC_Stripe_Constants::STRIPE_FEE ),
			'net'      => (float) $order->get_meta( WC_Stripe_Constants::STRIPE_NET ),
			'refunded' => 0
		);
	}

	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	public function __set( $name, $value ) {
		$this->set_prop( $name, $value );
	}

	public function __get( $name ) {
		if ( method_exists( $this, 'get_' . $name ) ) {
			return $this->{'get_' . $name}();
		}

		return $this->get_prop( $name );
	}

	private function set_prop( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	private function get_prop( $key, $default = '' ) {
		if ( ! isset( $this->data[ $key ] ) ) {
			$this->data[ $key ] = $default;
		}

		return $this->data[ $key ];
	}

	/**
	 * @return mixed
	 */
	public function get_fee() {
		return $this->get_prop( 'fee', 0 );
	}

	/**
	 * @return mixed
	 */
	public function get_net() {
		return $this->get_prop( 'net', 0 );
	}

	public function get_refunded() {
		return $this->get_prop( 'refunded', 0 );
	}

	/**
	 * @return mixed
	 */
	public function get_currency() {
		return $this->get_prop( 'currency' );
	}

	public function to_array() {
		return $this->data;
	}

	/**
	 * @since 3.3.35
	 * @return void
	 */
	public function save() {
		$this->update_meta_data( true );
	}

	public function update_meta_data( $save = false ) {
		if ( $this->order ) {
			$this->order->update_meta_data( WC_Stripe_Constants::STRIPE_CURRENCY, $this->currency );
			$this->order->update_meta_data( WC_Stripe_Constants::STRIPE_FEE, $this->fee );
			$this->order->update_meta_data( WC_Stripe_Constants::STRIPE_NET, $this->net );
			if ( $save ) {
				$this->order->save();
			}
		}
	}

}