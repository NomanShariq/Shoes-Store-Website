<?php


namespace PaymentPlugins\WooFunnels\Stripe\Upsell\PaymentGateways;

/**
 * Class BaseGateway
 *
 * @package PaymentPlugins\WooFunnels\Stripe\Upsell\PaymentGateways
 */
class BasePaymentGateway extends \WFOCU_Gateway {

	public $refund_supported = true;

	/**
	 * @var \WFOCU_Logger
	 */
	private $logger;

	private $client;

	private $payment;

	public function __construct( \WC_Stripe_Gateway $client, \WC_Stripe_Payment $payment, \WFOCU_Logger $logger ) {
		$this->client  = $client;
		$this->payment = $payment;
		$this->logger  = $logger;
		$this->initialize();
	}

	public static function get_instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static( \WC_Stripe_Gateway::load(), new \WC_Stripe_Payment_Intent( null, null ), WFOCU_Core()->log );
		}

		return $instance;
	}

	public function initialize() {
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return false|true|void
	 */
	public function process_charge( $order ) {
		$this->handle_client_error();
		$this->initialize_actions();
		try {
			$intent = isset( $_POST['_payment_intent'] ) ? $_POST['_payment_intent'] : null;
			// check if payment intent exists.
			if ( $intent ) {
				$intent = $this->client->paymentIntents->retrieve( $intent );
			} else {
				// If there is no customer ID, create one
				$user_id     = $order->get_customer_id();
				$customer_id = $order->get_meta( \WC_Stripe_Constants::CUSTOMER_ID );
				if ( ! $customer_id && ! $user_id ) {
					$this->create_stripe_customer( WC()->customer, $order );
				} elseif ( $user_id ) {
					$order->update_meta_data( \WC_Stripe_Constants::CUSTOMER_ID, wc_stripe_get_customer_id( $user_id ) );
					$order->save();
				}
				// create the payment intent
				$intent = $this->create_payment_intent( $order );
			}
			if ( $intent->status === \WC_Stripe_Constants::REQUIRES_PAYMENT_METHOD ) {
				$intent = $this->client->paymentIntents->update( $intent->id, [ 'payment_method' => $order->get_meta( \WC_Stripe_Constants::PAYMENT_METHOD_TOKEN ) ] );
				if ( is_wp_error( $intent ) ) {
					throw new \Exception( $intent->get_error_message(), 400 );
				}
			}
			if ( $intent->status === \WC_Stripe_Constants::REQUIRES_CONFIRMATION ) {
				$intent = $this->client->paymentIntents->confirm( $intent->id );
				if ( is_wp_error( $intent ) ) {
					throw new \Exception( $intent->get_error_message(), 400 );
				}
			}
			if ( $intent->status === \WC_Stripe_Constants::REQUIRES_ACTION ) {
				// send back response
				return \wp_send_json( [
					'success' => true,
					'data'    => [ 'redirect_url' => $this->get_payment_intent_redirect_url( $intent ) ]
				] );
			}
			$charge = $intent->charges->data[0];
			WFOCU_Core()->data->set( '_transaction_id', $charge->id );
			$this->update_payment_balance( $charge, $order );

			return $this->handle_result( true );
		} catch ( \Exception $e ) {
			$this->logger->log( sprintf( 'Error processing upsell. Reason: %s', $e->getMessage() ) );
			throw new \WFOCU_Payment_Gateway_Exception( $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	public function process_refund_offer( $order ) {
		$charge = isset( $_POST['txn_id'] ) ? $_POST['txn_id'] : false;
		$amount = isset( $_POST['amt'] ) ? round( $_POST['amt'], 2 ) : false;
		$mode   = wc_stripe_order_mode( $order );
		$result = $this->client->refunds->mode( $mode )->create( [
			'charge'   => $charge,
			'amount'   => wc_stripe_add_number_precision( $amount, $order->get_currency() ),
			'metadata' => array(
				'order_id'    => $order->get_id(),
				'created_via' => 'woocommerce'
			),
			'expand'   => stripe_wc()->advanced_settings->is_fee_enabled() ? [ 'charge.balance_transaction', 'charge.refunds.data.balance_transaction' ] : []
		] );
		if ( is_wp_error( $result ) ) {
			$this->logger->log( sprintf( 'Error refunding charge %s. Reason: %s', $charge, $result->get_error_message() ) );

			return false;
		} else {
			$this->logger->log( sprintf( 'Charge %s refunded n Stripe.', $charge ) );
			if ( isset( $result->charge->balance_transaction ) ) {
				$pb              = \WC_Stripe_Utils::get_payment_balance( $order );
				$payment_balance = \WC_Stripe_Utils::add_balance_transaction_to_order( $result->charge, $order );
				$pb->net         -= $payment_balance->refunded;
				$pb->save();
			}
		}

		return $result->id;
	}

	public function get_transaction_link( $transaction_id, $order_id ) {
		$order = wc_get_order( $order_id );
		$mode  = wc_stripe_order_mode( $order );
		$url   = 'https://dashboard.stripe.com/payments/%s';
		if ( $mode === 'test' ) {
			$url = 'https://dashboard.stripe.com/test/payments/%s';
		}

		return sprintf( $url, $transaction_id );
	}

	public function handle_client_error() {
		$package = WFOCU_Core()->data->get( '_upsell_package' );
		if ( $package && isset( $package['_client_error'] ) ) {
			$this->logger->log( sprintf( 'Stripe client error: %s', sanitize_text_field( $package['_client_error'] ) ) );
		}
	}

	/**
	 * @param \WC_Customer $customer
	 *
	 * @throws \Exception
	 */
	private function create_stripe_customer( \WC_Customer $customer, \WC_Order $order ) {
		$result = \WC_Stripe_Customer_Manager::instance()->create_customer( $customer );
		if ( ! is_wp_error( $result ) ) {
			$order->update_meta_data( \WC_Stripe_Constants::CUSTOMER_ID, $result->id );
			$order->save();

			// now that we have a customer created, attach the payment method
			$payment_method = $order->get_meta( \WC_Stripe_Constants::PAYMENT_METHOD_TOKEN );

			return $this->client->paymentMethods->attach( $payment_method, [ 'customer' => $result->id ] );
		}

		throw new \Exception( $result->get_error_message() );
	}

	private function create_payment_intent( \WC_Order $order ) {
		$package        = WFOCU_Core()->data->get( '_upsell_package' );
		$payment_method = $this->get_wc_gateway();
		$params         = array(
			'amount'               => wc_stripe_add_number_precision( $package['total'], $order->get_currency() ),
			'description'          => sprintf( __( '%1$s - Order %2$s - One Time offer', 'woo-stripe-payment' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() ),
			'payment_method'       => $order->get_meta( \WC_Stripe_Constants::PAYMENT_METHOD_TOKEN ),
			'confirmation_method'  => 'automatic', //$payment_method->get_confirmation_method( $order ),
			'capture_method'       => $payment_method->get_option( 'charge_type' ) === 'capture' ? 'automatic' : 'manual',
			'confirm'              => false,
			'payment_method_types' => [ $payment_method->get_payment_method_type() ],
			'customer'             => $order->get_meta( \WC_Stripe_Constants::CUSTOMER_ID )
		);
		$this->payment->add_order_metadata( $params, $order );
		$this->payment->add_order_currency( $params, $order );
		$this->payment->add_order_shipping_address( $params, $order );

		$params = apply_filters( 'wc_stripe_funnelkit_upsell_create_payment_intent', $params, $order, $this->client );

		$result = $this->client->mode( $order )->paymentIntents->create( $params );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message(), 400 );
		}

		return $result;
	}

	public function has_token( $order ) {
		$payment_token = $order->get_meta( \WC_Stripe_Constants::PAYMENT_METHOD_TOKEN );

		return ! empty( $payment_token );
	}

	/**
	 * @param \Stripe\PaymentIntent $intent
	 *
	 * @return string
	 */
	protected function get_payment_intent_redirect_url( \Stripe\PaymentIntent $intent ) {
		return sprintf( '#response=%s', rawurlencode( base64_encode(
			wp_json_encode( [
					'payment_intent' => $intent->id,
					'client_secret'  => $intent->client_secret
				]
			) ) ) );
	}

	public function initialize_actions() {
		if ( stripe_wc()->advanced_settings && stripe_wc()->advanced_settings->is_fee_enabled() ) {
			add_filter( 'wc_stripe_api_request_args', array( $this, 'add_balance_transaction' ), 10, 3 );
		}
	}

	public function add_balance_transaction( $args, $property, $method ) {
		if ( $property === 'paymentIntents' ) {
			if ( \in_array( $method, array( 'create', 'confirm', 'update', 'retrieve' ) ) ) {
				$data = null;
				switch ( $method ) {
					case 'create':
						$data = &$args[0];
						break;
					case 'update':
					case 'confirm':
					case 'retrieve':
						$data = &$args[1];
						break;
				}
				$data             = ! \is_array( $data ) ? array() : $data;
				$data['expand']   = ! isset( $data['expand'] ) ? array() : $data['expand'];
				$data['expand'][] = 'charges.data.balance_transaction';
			}
		}

		return $args;
	}

	/**
	 * @param \Stripe\Charge $charge
	 * @param \WC_Order      $order
	 *
	 * @return void
	 */
	public function update_payment_balance( $charge, $order ) {
		if ( $charge && isset( $charge->balance_transaction ) && is_object( $charge->balance_transaction ) ) {
			$order_behavior = WFOCU_Core()->funnels->get_funnel_option( 'order_behavior' );
			$use_main_order = $order_behavior === 'batching';
			// If this is a merged order, update the existing payment balance
			if ( $use_main_order ) {
				$payment_balance       = \WC_Stripe_Utils::create_payment_balance_from_balance_transaction( $charge->balance_transaction, $order );
				$payment_balance2      = \WC_Stripe_Utils::get_payment_balance( $order );
				$payment_balance2->net += $payment_balance->net;
				$payment_balance2->fee += $payment_balance->fee;
				$payment_balance2->save();
			} else {
				// This code is called if a new order is created for the Upsell
				add_action( 'wfocu_offer_new_order_created_' . $this->get_key(), function ( $order ) use ( $charge ) {
					$payment_balance = \WC_Stripe_Utils::create_payment_balance_from_balance_transaction( $charge->balance_transaction, $order );
					$payment_balance->save();
				} );
			}
		}
	}

	/**
	 * @param \WC_Order $order
	 * @param array     $charge_ids
	 *
	 * @return void
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function process_refund_success( $order, $charge_ids ) {
		$pb = \WC_Stripe_Utils::get_payment_balance( $order );
		foreach ( $charge_ids as $id ) {
			$charge = $this->client->mode( $order )->charges->retrieve( $id, [ 'expand' => [ 'balance_transaction' ] ] );
			if ( ! is_wp_error( $charge ) ) {
				$payment_balance = \WC_Stripe_Utils::add_balance_transaction_to_order( $charge, $order );
				$pb->net         += $payment_balance->net;
				$pb->fee         += $payment_balance->fee;
			}
		}
		$pb->save();
	}


}