<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class that manages customer creation and custom updates.
 *
 * @since   3.0.0
 * @package Stripe/Classes
 * @author  PaymentPlugins
 *
 */
class WC_Stripe_Customer_Manager {

	private static $_instance;

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'checkout_update_customer' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
	}

	/**
	 * Returns true if the plugin should create a Stripe customer if the user has an account
	 * with the store
	 *
	 * @since 3.3.38
	 * @return bool
	 */
	public function should_create_when_account_exists() {
		return stripe_wc()->advanced_settings->get_option( 'customer_creation' ) === 'account_creation';
	}

	/**
	 * Returns true if the plugin should create a Stripe customer when the payment is being processed.
	 *
	 * @since 3.3.38
	 * @return bool
	 */
	public function should_create_when_payment() {
		return stripe_wc()->advanced_settings->get_option( 'customer_creation' ) === 'payment';
	}

	/**
	 *
	 * @param WC_Customer $customer
	 * @param array       $data
	 */
	public function checkout_update_customer( $customer, $data ) {
		if ( $this->user_has_id( $customer ) ) {
			if ( $this->should_update_customer( $customer ) ) {
				$result = $this->update_customer( $customer );
				if ( is_wp_error( $result ) ) {
					// add info to log. This error isn't added to wc_add_notice because we don't want a user update to
					// interfere with payment being processed.
					wc_stripe_log_error( sprintf( __( 'Error saving customer. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ) );
				}
			}
		} else {
			if ( isset( $data['payment_method'] ) && strpos( $data['payment_method'], 'stripe_' ) !== false ) {
				// create the customer since this is a Stripe payment
				$response = $this->create_customer( $customer );
				if ( ! is_wp_error( $response ) ) {
					wc_stripe_save_customer( $response->id, $customer->get_id() );
				} else {
					wc_add_notice( sprintf( __( 'Error saving customer. Reason: %s', 'woo-stripe-payment' ), $response->get_error_message() ), 'error' );
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function create_customer( $customer, $mode = null ) {
		return WC_Stripe_Gateway::load( $mode )->customers->create( apply_filters( 'wc_stripe_customer_args', $this->get_customer_args( $customer ) ) );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	public function update_customer( $customer ) {
		return WC_Stripe_Gateway::load()->customers->update(
			wc_stripe_get_customer_id( $customer->get_id() ),
			apply_filters( 'wc_stripe_update_customer_args', $this->get_customer_args( $customer, 'update' ) )
		);
	}

	/**
	 * Check if the Stripe customer has been created for the WC user.
	 * If there is no Stripe customer then
	 * create one and save it to the WC user.
	 */
	public function wp_loaded() {
		$customer = WC()->customer;
		if ( $customer && $customer->get_id() ) {
			if ( ! $this->user_has_id( $customer ) ) {
				if ( $this->should_create_when_account_exists() ) {
					$response = $this->create_customer( $customer );
					if ( ! is_wp_error( $response ) ) {
						wc_stripe_save_customer( $response->id, $customer->get_id() );
					}
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	private function user_has_id( $customer ) {
		$id = wc_stripe_get_customer_id( $customer->get_id() );

		// this customer may have an ID from another plugin. Check that too.
		if ( empty( $id ) ) {
			$id = get_user_option( WC_Stripe_Constants::STRIPE_CUSTOMER_ID, $customer->get_id() );
			if ( ! empty( $id ) && is_string( $id ) ) {
				// validate that this customer exists in the Stripe gateway
				$response = WC_Stripe_Gateway::load()->customers->retrieve( $id );
				if ( ! is_wp_error( $response ) ) {
					// id exists so save customer ID to this plugin's format.
					wc_stripe_save_customer( $id, $customer->get_id() );

					// load this customer's payment methods
					$this->sync_payment_methods( $id, $customer->get_id() );
				} else {
					$id = '';
				}
			}
		}

		return ! empty( $id );
	}

	/**
	 * Syncs the WC database payment methods with the payment methods stored in Stripe.
	 *
	 * @param string $customer_id
	 * @param int    $user_id
	 *
	 * @since 3.1.0
	 */
	public static function sync_payment_methods( $customer_id, $user_id, $mode = '' ) {
		$payment_methods = WC_Stripe_Gateway::load()->paymentMethods->mode( $mode )->all( array(
			'customer' => $customer_id,
			'type'     => 'card',
		) );
		if ( ! is_wp_error( $payment_methods ) ) {
			foreach ( $payment_methods->data as $payment_method ) {
				/**
				 * @var \Stripe\PaymentMethod $payment_method
				 */
				if ( ! WC_Payment_Token_Stripe::token_exists( $payment_method->id, $user_id ) ) {
					$payment_gateways = WC()->payment_gateways()->payment_gateways();
					$gateway_id       = 'stripe_cc';
					if ( isset( $payment_method->card->wallet->type ) ) {
						switch ( $payment_method->card->wallet->type ) {
							case 'google_pay':
								$gateway_id = 'stripe_googlepay';
								break;
							case 'apple_pay':
								$gateway_id = 'stripe_applepay';
								break;
						}
					}
					/**
					 *
					 * @var WC_Payment_Gateway_Stripe_CC $payment_gateway
					 */
					$payment_gateway = $payment_gateways[ $gateway_id ];

					$token = $payment_gateway->get_payment_token( $payment_method->id, $payment_method );
					$token->set_environment( $payment_method->livemode ? 'live' : 'test' );
					$token->set_user_id( $user_id );
					$token->set_customer_id( $customer_id );
					$token->save();
				}
			}
		} else {
			wc_stripe_log_info( sprintf( 'Payment methods for customer %s could not be synced. Reason: %s', $customer_id, $payment_methods->get_error_message() ) );
		}
	}

	/**
	 * Returns true if the customer should be updated in Stripe.
	 *
	 * @param WC_Customer $customer
	 *
	 * @return bool
	 */
	private function should_update_customer( $customer ) {
		$changes = $customer->get_changes();
		if ( ! empty( $changes['billing'] ) ) {
			return array_intersect_key( $changes['billing'], array_flip( $this->get_attribute_keys() ) );
		}

		return false;
	}

	/**
	 * Returns an array of user attributes.
	 *
	 * @return array
	 */
	private function get_attribute_keys() {
		return apply_filters( 'wc_stripe_get_customer_attribute_keys', array(
			'first_name',
			'last_name',
			'email',
			'address_1',
			'address_2',
			'country',
			'state',
			'postcode'
		) );
	}

	/**
	 * Return an array of args used to create or update a customer.
	 *
	 * @param WC_Customer $customer
	 * @param string      $context
	 *
	 * @since 3.2.12
	 * @return array
	 */
	private function get_customer_args( $customer, $context = 'create' ) {
		$args = array(
			'email'   => $customer->get_email(),
			'name'    => sprintf( '%s %s', $customer->get_first_name(), $customer->get_last_name() ),
			'phone'   => $customer->get_billing_phone(),
			'address' => array(
				'city'        => $customer->get_billing_city(),
				'country'     => $customer->get_billing_country(),
				'line1'       => $customer->get_billing_address_1(),
				'line2'       => $customer->get_billing_address_2(),
				'postal_code' => $customer->get_billing_postcode(),
				'state'       => $customer->get_billing_state()
			)
		);
		if ( 'create' === $context ) {
			$args['metadata'] = array(
				'user_id'  => $customer->get_id(),
				'username' => $customer->get_username(),
				'website'  => get_site_url(),
			);
		}

		return $args;
	}

}

WC_Stripe_Customer_Manager::instance();
