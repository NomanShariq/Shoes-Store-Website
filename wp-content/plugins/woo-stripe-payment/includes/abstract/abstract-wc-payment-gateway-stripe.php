<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-payment-intent.php' );
require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-payment-charge.php' );
require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-payment-charge-local.php' );

require_once( WC_STRIPE_PLUGIN_FILE_PATH . 'includes/traits/wc-stripe-payment-traits.php' );

/**
 *
 * @since   3.0.0
 * @author  PaymentPlugins
 * @package Stripe/Abstract
 *
 */
abstract class WC_Payment_Gateway_Stripe extends WC_Payment_Gateway {

	use WC_Stripe_Settings_Trait;

	/**
	 *
	 * @var WC_Stripe_Payment
	 */
	public $payment_object;

	/**
	 * @var bool
	 * @since 3.1.8
	 */
	protected $has_digital_wallet = false;

	/**
	 *
	 * @var string
	 */
	public $token_key;

	/**
	 *
	 * @var string
	 */
	public $saved_method_key;

	/**
	 *
	 * @var string
	 */
	public $payment_type_key;

	/**
	 *
	 * @var string
	 */
	public $payment_intent_key;

	/**
	 *
	 * @var string
	 */
	public $save_source_key;

	/**
	 *
	 * @var string
	 */
	public $template_name;

	/**
	 *
	 * @var bool
	 */
	protected $checkout_error = false;

	/**
	 * Used to create an instance of a WC_Payment_Token
	 *
	 * @var string
	 */
	protected $token_type;

	/**
	 *
	 * @var WC_Stripe_Gateway
	 */
	public $gateway;

	/**
	 *
	 * @var WP_Error
	 */
	protected $wp_error;

	/**
	 *
	 * @var string
	 */
	public $payment_method_token = null;

	/**
	 *
	 * @var string
	 */
	protected $new_source_token = null;

	/**
	 * Is the payment method synchronous or asynchronous
	 *
	 * @var bool
	 */
	public $synchronous = true;

	/**
	 *
	 * @var array
	 */
	protected $post_payment_processes = array();

	/**
	 *
	 * @var bool
	 */
	public $processing_payment = false;

	/**
	 * @var WP_Error
	 */
	public $last_payment_error;

	/**
	 * @var bool @since 3.3.16
	 */
	public $is_voucher_payment = false;

	public function __construct() {
		$this->token_key          = $this->id . '_token_key';
		$this->saved_method_key   = $this->id . '_saved_method_key';
		$this->save_source_key    = $this->id . '_save_source_key';
		$this->payment_type_key   = $this->id . '_payment_type_key';
		$this->payment_intent_key = $this->id . '_payment_intent_key';
		$this->has_fields         = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title_text' );
		$this->description = $this->get_option( 'description' );
		$this->hooks();
		$this->init_supports();
		$this->gateway = WC_Stripe_Gateway::load();

		$this->payment_object = $this->get_payment_object();
	}

	public function hooks() {
		add_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_stripe_settings_checkout_' . $this->id, array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_payment_methods_list_item', array( $this, 'payment_methods_list_item' ), 10, 2 );
		add_action( 'wc_stripe_payment_token_deleted_' . $this->id, array( $this, 'delete_payment_method' ), 10, 2 );
		add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'subscription_payment_meta' ), 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_payment' ) );

		/**
		 * @since 3.1.8
		 */
		add_filter( 'wc_stripe_mini_cart_dependencies', array( $this, 'get_mini_cart_dependencies' ), 10, 2 );
	}

	public function init_supports() {
		$this->supports = array(
			'tokenization',
			'products',
			'subscriptions',
			'add_payment_method',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer',
		);
	}

	public function init_form_fields() {
		$this->form_fields = include stripe_wc()->plugin_path() . 'includes/gateways/settings/' . str_replace( array( 'stripe_', '_' ), array(
				'',
				'-'
			), $this->id ) . '-settings.php';
		$this->form_fields = apply_filters( 'wc_stripe_form_fields_' . $this->id, $this->form_fields );
	}

	/**
	 * @deprecated 3.3.18
	 */
	public function get_method_formats() {
		return $this->get_payment_method_formats();
	}

	public function get_payment_method_formats() {
		$class_name = 'WC_Payment_Token_' . $this->token_type;
		$formats    = array();
		if ( class_exists( $class_name ) ) {
			/**
			 *
			 * @var WC_Payment_Token_Stripe
			 */
			$token = new $class_name();

			$formats = $token->get_formats();
		}

		return $formats;
	}

	public function enqueue_admin_scripts() {
	}

	public function payment_fields() {
		$this->enqueue_frontend_scripts();
		wc_stripe_token_field( $this );
		wc_stripe_payment_intent_field( $this );
		$this->output_display_items( 'checkout' );
		wc_stripe_get_template(
			'checkout/stripe-payment-method.php',
			array(
				'gateway' => $this,
				'tokens'  => is_add_payment_method_page() ? null
					: \PaymentPlugins\Stripe\Utilities\PaymentMethodUtils::sort_by_default( $this->get_tokens() ),
			)
		);
	}

	/**
	 * Output the product payment fields.
	 */
	public function product_fields() {
		global $product;
		$this->enqueue_frontend_scripts( 'product' );
		$this->output_display_items( 'product' );
		wc_stripe_get_template(
			'product/' . $this->template_name,
			array(
				'gateway' => $this,
				'product' => $product,
			)
		);
	}

	public function cart_fields() {
		$this->enqueue_frontend_scripts( 'cart' );
		$this->output_display_items( 'cart' );
		wc_stripe_get_template( 'cart/' . $this->template_name, array( 'gateway' => $this ) );
	}

	public function mini_cart_fields() {
		$this->output_display_items( 'cart' );
		wc_stripe_get_template( 'mini-cart/' . $this->template_name, array( 'gateway' => $this ) );
	}

	/**
	 * Enqueue scripts needed by the gateway on the frontend of the WC shop.
	 *
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_frontend_scripts( $page = '' ) {
		if ( $page ) {
			if ( 'product' === $page ) {
				$this->enqueue_product_scripts( stripe_wc()->scripts() );
			} elseif ( 'cart' === $page ) {
				$this->enqueue_cart_scripts( stripe_wc()->scripts() );
			} elseif ( 'checkout' === $page ) {
				$this->enqueue_checkout_scripts( stripe_wc()->scripts() );
			} elseif ( 'mini_cart' === $page ) {
				$this->enqueue_mini_cart_scripts( stripe_wc()->scripts() );
			} else {
				$this->enqueue_frontend_scripts();
			}
		} else {
			if ( is_add_payment_method_page() ) {
				$this->enqueue_add_payment_method_scripts( stripe_wc()->scripts() );
			}
			if ( is_checkout() ) {
				$this->enqueue_checkout_scripts( stripe_wc()->scripts() );
			}
			if ( is_cart() ) {
				$this->enqueue_cart_scripts( stripe_wc()->scripts() );
			}
			if ( is_product() ) {
				$this->enqueue_product_scripts( stripe_wc()->scripts() );
			}
		}
		if ( ! empty( stripe_wc()->scripts()->enqueued_scripts ) ) {
			$this->enqueue_payment_method_styles();
		}
	}

	/**
	 * @since 3.3.37
	 * @return void
	 */
	public function enqueue_payment_method_styles() {
		wp_enqueue_style( stripe_wc()->scripts()->prefix . 'styles', stripe_wc()->scripts()->assets_url( 'css/stripe.css' ), array(), stripe_wc()->version() );
		wp_style_add_data( stripe_wc()->scripts()->prefix . 'styles', 'rtl', 'replace' );
	}

	/**
	 * Enqueue scripts needed by the gateway on the checkout page.
	 *
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_checkout_scripts( $scripts ) {
	}

	/**
	 * Enqueue scripts needed by the gateway on the add payment method page.
	 *
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_add_payment_method_scripts( $scripts ) {
		$this->enqueue_checkout_scripts( $scripts );
	}

	/**
	 * Enqueue scripts needed by the gateway on the cart page.
	 *
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_cart_scripts( $scripts ) {
	}

	/**
	 * Enqueue scripts needed by the gateway on the product page.
	 *
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_product_scripts( $scripts ) {
	}

	/**
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 *
	 * @since 3.1.8
	 */
	public function enqueue_mini_cart_scripts( $scripts ) {
		if ( ! wp_script_is( $scripts->get_handle( 'mini-cart' ) ) ) {
			$scripts->enqueue_script( 'mini-cart',
				$scripts->assets_url( 'js/frontend/mini-cart.js' ),
				apply_filters( 'wc_stripe_mini_cart_dependencies', array( $scripts->get_handle( 'wc-stripe' ) ), $scripts ) );
		}
		$scripts->localize_script( 'mini-cart', $this->get_localized_params(), 'wc_' . $this->id . '_mini_cart_params' );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_payment()
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $this->is_change_payment_method_request() && wcs_is_subscription( $order ) ) {
			return $this->process_change_payment_method_request( $order );
		}

		do_action( 'wc_stripe_before_process_payment', $order, $this->id );

		if ( wc_notice_count( 'error' ) > 0 ) {
			return $this->get_order_error();
		}
		$this->processing_payment = true;

		if ( $this->order_contains_pre_order( $order ) && $this->pre_order_requires_tokenization( $order ) ) {
			return $this->process_pre_order( $order );
		}

		// if order total is zero, then save meta but don't process payment.
		if ( $order->get_total() == 0 ) {
			return $this->process_zero_total_order( $order );
		}

		$result = $this->payment_object->process_payment( $order, $this );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $this->is_active( 'generic_error' ) ? $this->get_generic_error( $result ) : $result->get_error_message(), 'error' );

			return $this->get_order_error( $result );
		}

		if ( $result->complete_payment ) {
			WC()->cart->empty_cart();
			$this->payment_object->payment_complete( $order, $result->charge );
			$this->trigger_post_payment_processes( $order, $this );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} else {
			return array(
				'result'   => 'success',
				'redirect' => $result->redirect,
			);
		}
	}

	/**
	 *
	 * @return array
	 */
	public function get_localized_params() {
		$data = array(
			'gateway_id'            => $this->id,
			'api_key'               => wc_stripe_get_publishable_key(),
			'saved_method_selector' => '[name="' . $this->saved_method_key . '"]',
			'token_selector'        => '[name="' . $this->token_key . '"]',
			'messages'              => array(
				'terms'          => __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ),
				'required_field' => __( 'Please fill out all required fields.', 'woo-stripe-payment' )
			),
			'routes'                => array(
				'create_payment_intent'       => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'payment-intent' ) ),
				'order_create_payment_intent' => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'order/payment-intent' ) ),
				'setup_intent'                => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'setup-intent' ) ),
				'sync_intent'                 => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'sync-payment-intent' ) ),
				'add_to_cart'                 => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'add-to-cart' ) ),
				'cart_calculation'            => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'cart-calculation' ) ),
				'shipping_method'             => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'shipping-method' ) ),
				'shipping_address'            => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'shipping-address' ) ),
				'checkout'                    => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->checkout->rest_uri( 'checkout' ) ),
				'checkout_payment'            => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->checkout->rest_uri( 'checkout/payment' ) ),
				'order_pay'                   => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->checkout->rest_uri( 'order-pay' ) ),
				'base_path'                   => WC_Stripe_Rest_API::get_endpoint( '%s' )
			),
			'rest_nonce'            => wp_create_nonce( 'wp_rest' ),
			'banner_enabled'        => $this->banner_checkout_enabled(),
			'currency'              => get_woocommerce_currency(),
			'total_label'           => __( 'Total', 'woo-stripe-payment' ),
			'country_code'          => wc_get_base_location()['country'],
			'user_id'               => get_current_user_id(),
			'description'           => $this->get_description(),
			'elementOptions'        => $this->get_element_options()
		);
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$data['order_id']  = absint( $wp->query_vars['order-pay'] );
			$data['order_key'] = isset( $_GET['key'] ) ? $_GET['key'] : '';
		}

		return $data;
	}

	/**
	 * Save the Stripe data to the order.
	 *
	 * @param WC_Order       $order
	 * @param \Stripe\Charge $charge
	 */
	public function save_order_meta( $order, $charge ) {
		/**
		 *
		 * @var WC_Payment_Token_Stripe $token
		 */
		$token = $this->get_payment_token( $this->get_payment_method_from_charge( $charge ), $charge->payment_method_details );
		$order->set_transaction_id( $charge->id );
		$order->set_payment_method_title( $token->get_payment_method_title() );
		$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
		$order->update_meta_data( WC_Stripe_Constants::CHARGE_STATUS, $charge->status );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );

		// if WCS is active and there are subscriptions in the order, save meta data
		if ( wcs_stripe_active() && wcs_order_contains_subscription( $order ) ) {
			foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
				$subscription->set_transaction_id( $charge->id );
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
				$subscription->update_meta_data( WC_Stripe_Constants::CHARGE_STATUS, $charge->status );
				$subscription->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
				$subscription->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, wc_stripe_get_customer_id( $order->get_user_id() ) );
				$subscription->save();
			}
		}
		/**
		 * @param WC_Order                  $order
		 * @param WC_Payment_Gateway_Stripe $this
		 * @param \Stripe\Charge            $charge
		 *
		 * @since 3.2.7
		 */
		do_action( 'wc_stripe_save_order_meta', $order, $this, $charge );

		$order->save();
	}

	/**
	 * Given a charge object, return the ID of the payment method used for the charge.
	 *
	 * @param \Stripe\Charge $charge
	 *
	 * @since 3.0.6
	 */
	public function get_payment_method_from_charge( $charge ) {
		return $this->payment_object->get_payment_method_from_charge( $charge );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::add_payment_method()
	 */
	public function add_payment_method() {
		$user_id = get_current_user_id();
		try {
			if ( ! is_user_logged_in() ) {
				throw new Exception( __( 'User must be logged in.', 'woo-stripe-payment' ) );
			}

			$customer_id = wc_stripe_get_customer_id( $user_id );

			if ( empty( $customer_id ) ) {
				$customer_id = $this->create_customer( $user_id );
			}

			$result = $this->create_payment_method( $this->get_new_source_token(), $customer_id );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}
			$result->set_user_id( $user_id );
			$result->save();
			WC_Payment_Tokens::set_users_default( $user_id, $result->get_id() );

			unset( WC()->session->{WC_Stripe_Constants::PAYMENT_INTENT} );
			do_action( 'wc_stripe_add_payment_method_success', $result );

			return array(
				'result'   => 'success',
				'redirect' => wc_get_account_endpoint_url( 'payment-methods' ),
			);
		} catch ( Exception $e ) {
			wc_add_notice( sprintf( __( 'Error saving payment method. Reason: %s', 'woo-stripe-payment' ), $e->getMessage() ), 'error' );

			return array( 'result' => 'error' );
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_refund()
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order  = wc_get_order( $order_id );
		$result = $this->payment_object->process_refund( $order, $amount );

		if ( ! is_wp_error( $result ) ) {
			$order->add_order_note(
				sprintf(
					__( 'Order refunded in Stripe. Amount: %s', 'woo-stripe-payment' ),
					wc_price(
						$amount,
						array(
							'currency' => $order->get_currency(),
						)
					)
				)
			);
		}

		return $result;
	}

	/**
	 * Captures the charge for the Stripe order.
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 */
	public function capture_charge( $amount, $order ) {
		$result = $this->gateway->charges->mode( wc_stripe_order_mode( $order ) )->retrieve( $order->get_transaction_id() );

		if ( is_wp_error( $result ) ) {
			return;
		} else {
			if ( ! $result->captured ) {
				$result = $this->payment_object->capture_charge( $amount, $order );

				if ( ! is_wp_error( $result ) ) {
					remove_action( 'woocommerce_order_status_completed', 'wc_stripe_order_status_completed' );
					WC_Stripe_Utils::add_balance_transaction_to_order( $result, $order, true );
					if ( isset( $result->refunds->data[0] ) ) {
						$balance_transaction = $this->gateway->balanceTransactions->retrieve( $result->refunds->data[0]->balance_transaction );
						if ( ! is_wp_error( $balance_transaction ) ) {
							WC_Stripe_Utils::update_balance_transaction( $balance_transaction, $order, true );
						}
					}
					$order->payment_complete();
					$order->add_order_note( sprintf( __( 'Order amount captured in Stripe. Amount: %s', 'woo-stripe-payment' ),
							wc_price( $amount, array( 'currency' => $order->get_currency(), ) ) )
					);
				} else {
					$order->add_order_note( sprintf( __( 'Error capturing charge in Stripe. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ) );
				}
			}
		}

		return $result;
	}

	/**
	 * Void the Stripe charge.
	 *
	 * @param WC_Order $order
	 */
	public function void_charge( $order ) {
		// @3.1.1 - check added so errors aren't encountered if the order can't be voided
		if ( ! $this->payment_object->can_void_order( $order ) ) {
			return;
		}
		$result = $this->payment_object->void_charge( $order );

		if ( is_wp_error( $result ) ) {
			$order->add_order_note( sprintf( __( 'Error voiding charge. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ) );
		} else {
			$order->add_order_note( __( 'Charge voided in Stripe.', 'woo-stripe-payment' ) );
		}
	}

	/**
	 * Return the \Stripe\Charge object
	 *
	 * @param String $charge_id
	 * @param String $mode
	 *
	 * @return WP_Error|\Stripe\Charge
	 */
	public function retrieve_charge( $charge_id, $mode = '' ) {
		return $this->gateway->charges->mode( $mode )->retrieve( $charge_id );
	}

	/**
	 *
	 * @param string             $method_id
	 * @param \Stripe\Card|array $method_details
	 */
	public function get_payment_token( $method_id, $method_details = null ) {
		$class_name = 'WC_Payment_Token_' . $this->token_type;
		if ( class_exists( $class_name ) ) {
			/**
			 *
			 * @var WC_Payment_Token_Stripe $token
			 */
			$token = new $class_name( '', $method_details );
			$token->set_token( $method_id );
			$token->set_gateway_id( $this->id );
			$token->set_format( $this->get_option( 'method_format' ) );
			$token->set_environment( wc_stripe_mode() );
			if ( $method_details ) {
				$token->details_to_props( $method_details );
			}

			return $token;
		}
	}

	/**
	 * Return a failed order response.
	 *
	 * @param WP_Error $error
	 *
	 * @return array
	 */
	public function get_order_error( $error = null ) {
		wc_stripe_set_checkout_error();
		$this->last_payment_error = $error;
		do_action( 'wc_stripe_process_payment_error', $error, $this );

		return array( 'result' => WC_Stripe_Constants::FAILURE, 'redirect' => '' );
	}

	/**
	 * Return the payment source the customer has chosen to use.
	 * This can be a saved source
	 * or a one time use source.
	 */
	public function get_payment_source() {
		if ( $this->use_saved_source() ) {
			return $this->get_saved_source_id();
		} else {
			if ( $this->payment_method_token ) {
				return $this->payment_method_token;
			}

			return $this->get_new_source_token();
		}
	}

	/**
	 * Returns the payment method the customer wants to use.
	 * This can be a saved payment method
	 * or a new payment method.
	 */
	public function get_payment_method_from_request() {
		return $this->get_payment_source();
	}

	public function get_payment_intent_id() {
		return ! empty( $_POST[ $this->payment_intent_key ] ) ? wc_clean( $_POST[ $this->payment_intent_key ] ) : '';
	}

	/**
	 * Return true of the customer is using a saved payment method.
	 */
	public function use_saved_source() {
		return ( ! empty( $_POST[ $this->payment_type_key ] ) && wc_clean( $_POST[ $this->payment_type_key ] ) === 'saved' ) || $this->payment_method_token
		       || ( ! empty( $_POST["wc-{$this->id}-payment-token"] ) );
	}

	/**
	 *
	 * @deprecated
	 *
	 */
	public function get_new_source_id() {
		return $this->get_new_source_token();
	}

	public function get_new_source_token() {
		return null != $this->new_source_token ? $this->new_source_token : ( ! empty( $_POST[ $this->token_key ] ) ? wc_clean( $_POST[ $this->token_key ] ) : '' );
	}

	public function get_saved_source_id() {
		// Check if Blocks are being used
		if ( ! empty( $_POST["wc-{$this->id}-payment-token"] ) ) {
			$token = WC_Payment_Tokens::get( wc_clean( $_POST["wc-{$this->id}-payment-token"] ) );

			return $token->get_token();
		}
		if ( ! empty( $_POST[ $this->saved_method_key ] ) && ! empty( $_POST[ $this->payment_type_key ] ) && 'saved' == $_POST[ $this->payment_type_key ] ) {
			return wc_clean( $_POST[ $this->saved_method_key ] );
		}

		return $this->payment_method_token;
	}

	/**
	 * Create a customer in the stripe gateway.
	 *
	 * @param int $user_id
	 *
	 * @throws Exception
	 */
	public function create_customer( $user_id ) {
		$customer = WC()->customer;
		$response = WC_Stripe_Customer_Manager::instance()->create_customer( $customer );
		if ( ! is_wp_error( $response ) ) {
			wc_stripe_save_customer( $response->id, $user_id );
		} else {
			throw new Exception( $response->get_error_message() );
		}

		return $response->id;
	}

	/**
	 * Creates a payment method in Stripe.
	 *
	 * @param string $id
	 *          payment method id
	 * @param string $customer_id
	 *          WC Stripe customer ID
	 *
	 * @return WC_Payment_Token_Stripe|WP_Error
	 */
	public function create_payment_method( $id, $customer_id ) {
		$token = $this->get_payment_token( $id );
		$token->set_customer_id( $customer_id );

		$result = $token->save_payment_method();

		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			$token->set_token( $result->id );
			$token->details_to_props( $result );

			return $token;
		}
	}

	/**
	 *
	 * @param array                      $item
	 * @param WC_Payment_Token_Stripe_CC $payment_token
	 */
	public function payment_methods_list_item( $item, $payment_token ) {
		if ( $payment_token->get_type() === $this->token_type && $this->id === $payment_token->get_gateway_id() ) {
			$item['method']['last4'] = $payment_token->get_last4();
			$item['method']['brand'] = ucfirst( $payment_token->get_brand() );
			if ( $payment_token->has_expiration() ) {
				$item['expires'] = sprintf( '%s / %s', $payment_token->get_exp_month(), $payment_token->get_exp_year() );
			} else {
				$item['expires'] = __( 'n/a', 'woo-stripe-payment' );
			}
			$item['wc_stripe_method'] = true;
		}

		return $item;
	}

	/**
	 *
	 * @param string                  $token_id
	 * @param WC_Payment_Token_Stripe $token
	 */
	public function delete_payment_method( $token_id, $token ) {
		$token->delete_from_stripe();
	}

	public function saved_payment_methods( $tokens = array() ) {
		wc_stripe_get_template(
			'payment-methods.php',
			array(
				'tokens'  => $tokens,
				'gateway' => $this,
			)
		);
	}

	public function get_new_method_label() {
		return __( 'New Card', 'woo-stripe-payment' );
	}

	public function get_saved_methods_label() {
		return __( 'Saved Cards', 'woo-stripe-payment' );
	}

	/**
	 * Return true if shipping is needed.
	 * Shipping is based on things like if the cart or product needs shipping.
	 *
	 * @return bool
	 */
	public function get_needs_shipping() {
		if ( is_checkout() || is_cart() ) {
			global $wp;
			if ( wcs_stripe_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				return false;
			}
			// return false if this is the order pay page. Gateways that have payment sheets don't need
			// to make any changes to the order.
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				return false;
			}

			return WC()->cart->needs_shipping();
		}
		if ( is_product() ) {
			global $product;

			return is_a( $product, 'WC_Product' ) && $product->needs_shipping();
		}
	}

	/**
	 * Return true of the payment method should be saved.
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function should_save_payment_method( $order ) {
		$bool = false;
		if ( ! $this->use_saved_source() && ! $this->is_processing_scheduled_payment() ) {
			if ( wcs_stripe_active() && $this->supports( 'subscriptions' ) ) {
				if ( wcs_order_contains_subscription( $order ) || wcs_order_contains_renewal( $order ) ) {
					$bool = true;
				}
			}
			if ( ! empty( $_POST[ $this->save_source_key ] ) ) {
				$bool = true;
			}
		}

		/**
		 * @param bool                      $bool
		 * @param WC_Order                  $order
		 * @param WC_Payment_Gateway_Stripe $this
		 *
		 * @since 3.3.12
		 */
		return apply_filters( 'wc_stripe_should_save_payment_method', $bool, $order, $this );
	}

	/**
	 * Returns true if the save payment method checkbox can be displayed.
	 *
	 * @return boolean
	 */
	public function show_save_source() {
		return false;
	}

	/**
	 * Returns a formatted array of items for display in the payment gateway's payment sheet.
	 *
	 * @param stirng $page
	 *
	 * @return []
	 */
	public function get_display_items( $page = 'checkout', $order = null ) {
		global $wp;
		$items = array();
		if ( in_array( $page, array( 'cart', 'checkout' ) ) ) {
			$items = $this->get_display_items_for_cart( WC()->cart );
		} elseif ( 'order_pay' === $page ) {
			$order = ! is_null( $order ) ? $order : wc_get_order( absint( $wp->query_vars['order-pay'] ) );
			$items = $this->get_display_items_for_order( $order );
		} elseif ( 'product' === $page ) {
			global $product;
			$items = array( $this->get_display_item_for_product( $product ) );
		}

		/**
		 * @param array         $items
		 * @param WC_Order|null $order
		 * @param string        $page
		 */
		return apply_filters( 'wc_stripe_get_display_items', $items, $order, $page );
	}

	/**
	 * Returns a formatted array of shipping methods for display in the payment gateway's
	 * payment sheet.
	 *
	 * @param bool $encode
	 *
	 * @return array
	 * @deprecated
	 */
	public function get_shipping_methods() {
		return $this->get_formatted_shipping_methods();
	}

	/**
	 * Return true if product page checkout is enabled for this gateway
	 *
	 * @return bool
	 */
	public function product_checkout_enabled() {
		return in_array( 'product', $this->get_option( 'payment_sections', array() ) );
	}

	/**
	 * Return true if cart page checkout is enabled for this gateway
	 *
	 * @return bool
	 */
	public function cart_checkout_enabled() {
		return in_array( 'cart', $this->get_option( 'payment_sections', array() ) );
	}

	/**
	 * Return true if mini-cart checkout is enabled for this gateway
	 *
	 * @since 3.1.8
	 * @return bool
	 */
	public function mini_cart_enabled() {
		return in_array( 'mini_cart', $this->get_option( 'payment_sections', array() ) );
	}

	/**
	 * Return true if checkout page banner is enabled for this gateway
	 *
	 * @return bool
	 */
	public function banner_checkout_enabled() {
		global $wp;

		return empty( $wp->query_vars['order-pay'] ) && $this->supports( 'wc_stripe_banner_checkout' ) && in_array( 'checkout_banner', $this->get_option( 'payment_sections', array() ) );
	}

	/**
	 * Decorate the response with data specific to the gateway.
	 *
	 * @param [] $data
	 */
	public function add_to_cart_response( $data ) {
		return $data;
	}

	/**
	 * Decorate the update shipping method reponse with data.
	 *
	 * @param [] $data
	 */
	public function get_update_shipping_method_response( $data ) {
		return $data;
	}

	/**
	 * Decorate the update shipping address respond with data.
	 *
	 * @param [] $data
	 */
	public function get_update_shipping_address_response( $data ) {
		return apply_filters( 'wc_stripe_update_shipping_address_response', $data );
	}

	/**
	 * Save the customer's payment method.
	 * If the payment method has already been saved to the customer
	 * then simply return true.
	 *
	 * @param string   $id
	 * @param WC_Order $order
	 * @param
	 *
	 * @return WP_Error|bool
	 */
	public function save_payment_method( $id, $order, $payment_details = null ) {
		$mode        = wc_stripe_order_mode( $order );
		$user_id     = $order->get_customer_id();
		$customer_id = wc_stripe_get_customer_id( $user_id, $mode );
		if ( ! $customer_id ) {
			$response = WC_Stripe_Customer_Manager::instance()->create_customer( new WC_Customer( $user_id, ! $user_id ), $mode );
			if ( ! is_wp_error( $response ) ) {
				$payment_details = null;
				$customer_id     = $response->id;
				if ( $user_id ) {
					wc_stripe_save_customer( $customer_id, $user_id, $mode );
				} else {
					$order->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $customer_id );
				}
			}
		}
		if ( $payment_details ) {
			$token = $this->get_payment_token( $id, $payment_details );
			$token->set_customer_id( $customer_id );
		} else {
			$token = $this->create_payment_method( $id, $customer_id );
			if ( is_wp_error( $token ) ) {
				$this->wp_error = $token;
				$order->add_order_note( sprintf( __( 'Attempt to save payment method failed. Reason: %s', 'woo-stripe-payment' ), $token->get_error_message() ) );

				return $token;
			}
		}
		$token->set_user_id( $user_id );
		if ( $user_id && strtolower( $token->get_brand() ) !== 'link' ) {
			$token->save();
		}

		// set token value so it can be used for other processes.
		$this->payment_method_token = $token->get_token();

		return true;
	}

	/**
	 * Set an error on the order.
	 * This error is used on the frontend to alert customer's to a failed payment method save.
	 *
	 * @param WC_Order $order
	 * @param WP_Error $error
	 *
	 * @deprecated
	 */
	public function set_payment_save_error( $order, $error ) {
		if ( wcs_stripe_active() && wcs_order_contains_subscription( $order ) ) {
			$message = __( 'We were not able to save your payment method. To prevent billing issues with your subscription, please add a payment method to the subscription.', 'woo-stripe-payment' );
		} else {
			$message = sprintf( __( 'We were not able to save your payment method. Reason: %s', 'woo-stripe-payment' ), $error->get_error_message() );
		}
		$order->update_meta_data( '_wc_stripe_order_error', $message );
		$order->save();
	}

	/**
	 *
	 * @param string $token_id
	 * @param int    $user_id
	 *
	 * @return null|WC_Payment_Token_Stripe_CC
	 */
	public function get_token( $token_id, $user_id ) {
		$tokens = WC_Payment_Tokens::get_tokens( array( 'user_id' => $user_id, 'gateway_id' => $this->id, 'limit' => 20 ) );
		foreach ( $tokens as $token ) {
			if ( $token_id === $token->get_token() ) {
				return $token;
			}
		}

		return null;
	}

	/**
	 *
	 * @param array           $payment_meta
	 * @param WC_Subscription $subscription
	 */
	public function subscription_payment_meta( $payment_meta, $subscription ) {
		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				WC_Stripe_Constants::PAYMENT_METHOD_TOKEN => array(
					'value' => $this->get_order_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $subscription ),
					'label' => __( 'Payment Method Token', 'woo-stripe-payment' ),
				),
				WC_Stripe_Constants::CUSTOMER_ID          => array(
					'value' => $this->get_order_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $subscription ),
					'label' => __( 'Stripe Customer ID', 'woo-stripe-payment' ),
				),
			),
		);

		return $payment_meta;
	}

	/**
	 *
	 * @param float    $amount
	 * @param WC_Order $order
	 */
	public function scheduled_subscription_payment( $amount, $order ) {
		$this->processing_payment = true;

		$result = $this->payment_object->scheduled_subscription_payment( $amount, $order );

		if ( is_wp_error( $result ) ) {
			$order->update_status( 'failed' );
			$order->add_order_note( sprintf( __( 'Recurring payment for order failed. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ) );

			return;
		}

		$this->save_order_meta( $order, $result->charge );

		// set the payment method token that was used to process the renewal order.
		$this->payment_method_token = $order->get_meta( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN );

		if ( $result->complete_payment ) {
			if ( $result->charge->captured ) {
				if ( $result->charge->status === 'pending' ) {
					// pending status means this is an asynchronous payment method.
					$order->update_status( apply_filters( 'wc_stripe_renewal_pending_order_status', 'on-hold', $order, $this, $result->charge ), __( 'Renewal payment initiated in Stripe. Waiting for the payment to clear.', 'woo-stripe-payment' ) );
				} else {
					WC_Stripe_Utils::add_balance_transaction_to_order( $result->charge, $order );
					$order->payment_complete( $result->charge->id );
					$order->add_order_note( sprintf( __( 'Recurring payment captured in Stripe. Payment method: %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
				}
			} else {
				$order->update_status( apply_filters( 'wc_stripe_authorized_renewal_order_status', 'on-hold', $order, $this ),
					sprintf( __( 'Recurring payment authorized in Stripe. Payment method: %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
			}
		} else {
			$order->update_status( 'pending', sprintf( __( 'Customer must manually complete payment for payment method %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
		}
	}

	/**
	 * Return true if this request is to change the payment method of a WC Subscription.
	 *
	 * @return bool
	 */
	public function is_change_payment_method_request() {
		return wcs_stripe_active() && did_action( 'woocommerce_subscriptions_pre_update_payment_method' );
	}

	/**
	 * Sets the ID of a payment token.
	 *
	 * @param string $id
	 */
	public function set_payment_method_token( $id ) {
		$this->payment_method_token = $id;
	}

	public function set_new_source_token( $token ) {
		$this->new_source_token = $token;
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @deprecated
	 *
	 */
	public function get_order_description( $order ) {
		return sprintf( __( 'Order %1$s from %2$s', 'woo-stripe-payment' ), $order->get_order_number(), get_bloginfo( 'name' ) );
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function process_zero_total_order( $order ) {
		// save payment method if necessary
		if ( ! defined( WC_Stripe_Constants::PROCESSING_PAYMENT ) && $this->should_save_payment_method( $order ) ) {
			$result = $this->save_payment_method( $this->get_new_source_token(), $order );
			if ( is_wp_error( $result ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );

				return $this->get_order_error();
			}
		} else {
			$this->payment_method_token = $this->get_saved_source_id();

			return $this->payment_object->process_zero_total_order( $order, $this );
		}

		return $this->payment_object->process_zero_total_order( $order, $this );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function process_pre_order( $order ) {
		$token = null;
		// maybe save payment method
		if ( ! $this->use_saved_source() ) {
			// if user not logged in, create a Stripe customer that won't be assigned to a user.
			if ( ! $order->get_customer_id() ) {
				$customer = WC_Stripe_Customer_Manager::instance()->create_customer( WC()->customer );
				if ( is_wp_error( $customer ) ) {
					return wc_add_notice( $customer->get_error_message(), 'error' );
				}
				$order->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $customer->id );
				$result = $token = $this->create_payment_method( $this->get_new_source_token(), $customer->id );
			} else {
				$result = $this->save_payment_method( $this->get_new_source_token(), $order );
			}
			if ( is_wp_error( $result ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );

				return $this->get_order_error();
			}
		} else {
			$this->payment_method_token = $this->get_saved_source_id();
		}
		WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
		$this->save_zero_total_meta( $order, $token );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function save_zero_total_meta( $order, $token = null ) {
		$token = ! $token ? $this->get_token( $this->payment_method_token, $order->get_user_id() ) : $token;
		$order->set_payment_method_title( $token->get_payment_method_title( $this->get_option( 'method_format' ) ) );
		$order->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
		$order->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
		if ( $order->get_customer_id() ) {
			$order->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, wc_stripe_get_customer_id( $order->get_user_id() ) );
		}
		if ( wcs_stripe_active() && wcs_order_contains_subscription( $order ) ) {
			foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
				/**
				 *
				 * @var WC_Subscription $subscription
				 */
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->update_meta_data( WC_Stripe_Constants::MODE, wc_stripe_mode() );
				$subscription->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
				$subscription->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, wc_stripe_get_customer_id( $order->get_user_id() ) );
				$subscription->save();
			}
		}
		/**
		 * @param WC_Order                  $order
		 * @param WC_Payment_Gateway_Stripe $this
		 * @param null                      $charge
		 *
		 * @since 3.2.7
		 */
		do_action( 'wc_stripe_save_order_meta', $order, $this, null );

		$order->save();
	}

	/**
	 * Pre orders can't be mixed with regular products.
	 *
	 * @param WC_Order $order
	 */
	protected function order_contains_pre_order( $order ) {
		return wc_stripe_pre_orders_active() && WC_Pre_Orders_Order::order_contains_pre_order( $order );
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @return boolean
	 */
	protected function pre_order_requires_tokenization( $order ) {
		return WC_Pre_Orders_Order::order_requires_payment_tokenization( $order );
	}

	/**
	 * Sets a lock on the order.
	 * Default behavior is a 2 minute lock.
	 *
	 * @param WC_Order|int $order
	 */
	public function set_order_lock( $order ) {
		$order_id = ( is_object( $order ) ? $order->get_id() : $order );
		set_transient( 'stripe_lock_order_' . $order_id, $order_id, apply_filters( 'wc_stripe_set_order_lock', 2 * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Removes the lock on the order
	 *
	 * @param WC_Order|int $order
	 */
	public function release_order_lock( $order ) {
		delete_transient( 'stripe_lock_order_' . ( is_object( $order ) ? $order->get_id() : $order ) );
	}

	/**
	 * Returns true of the order has been locked.
	 * If the lock exists and is greater than current time
	 * method returns true;
	 *
	 * @param WC_Order|int $order
	 */
	public function has_order_lock( $order ) {
		$lock = get_transient( 'stripe_lock_order_' . ( is_object( $order ) ? $order->get_id() : $order ) );

		return $lock !== false;
	}

	public function set_post_payment_process( $callback ) {
		$this->post_payment_processes[] = $callback;
	}

	/**
	 *
	 * @param WC_Order                  $order
	 * @param WC_Payment_Gateway_Stripe $gateway
	 */
	public function trigger_post_payment_processes( $order, $gateway ) {
		foreach ( $this->post_payment_processes as $callback ) {
			call_user_func_array( $callback, func_get_args() );
		}
	}

	public function validate_payment_sections_field( $key, $value ) {
		if ( empty( $value ) ) {
			$value = array();
		}

		return $value;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function process_pre_order_payment( $order ) {
		$this->processing_payment = true;

		$result = $this->payment_object->process_pre_order_payment( $order );

		if ( is_wp_error( $result ) ) {
			$order->update_status( 'failed' );
			$order->add_order_note( sprintf( __( 'Pre-order payment for order failed. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ) );
		} else {
			if ( $result->complete_payment ) {
				$this->save_order_meta( $order, $result->charge );

				if ( $result->charge->captured ) {
					if ( $result->charge->status === 'pending' ) {
						$order->update_status( apply_filters( 'wc_stripe_pending_preorder_order_status', 'on-hold', $order, $this ), __( 'Pre-order payment initiated in Stripe. Waiting for the payment to clear.', 'woo-stripe-payment' ) );
					} else {
						WC_Stripe_Utils::add_balance_transaction_to_order( $result->charge, $order );
						$order->payment_complete( $result->charge->id );
						$order->add_order_note( sprintf( __( 'Pre-order payment captured in Stripe. Payment method: %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
					}
				} else {
					$order->update_status( apply_filters( 'wc_stripe_authorized_preorder_order_status', 'on-hold', $order, $this ),
						sprintf( __( 'Pre-order payment authorized in Stripe. Payment method: %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
				}
			} else {
				$order->update_status( 'pending', sprintf( __( 'Customer must manually complete payment for payment method %s', 'woo-stripe-payment' ), $order->get_payment_method_title() ) );
			}
		}
	}

	/**
	 * Given a meta key, see if there is a value for that key in another plugin.
	 * This acts as a lazy conversion
	 * method for merchants that have switched to our plugin from other plugins.
	 *
	 * @param string   $meta_key
	 * @param WC_Order $order
	 * @param string   $context
	 *
	 * @since 3.1.0
	 */
	public function get_order_meta_data( $meta_key, $order, $context = 'view' ) {
		$value = $order->get_meta( $meta_key, true, $context );
		// value is empty so check metadata from other plugins
		if ( empty( $value ) ) {
			$keys = array();
			switch ( $meta_key ) {
				case WC_Stripe_Constants::PAYMENT_METHOD_TOKEN:
					$keys = array( WC_Stripe_Constants::SOURCE_ID );
					break;
				case WC_Stripe_Constants::CUSTOMER_ID:
					$keys = array( WC_Stripe_Constants::STRIPE_CUSTOMER_ID );
					break;
				case WC_Stripe_Constants::PAYMENT_INTENT_ID:
					$keys = array( WC_Stripe_Constants::STRIPE_INTENT_ID );
			}
			if ( $keys ) {
				$meta_data = $order->get_meta_data();
				if ( $meta_data ) {
					$keys       = array_intersect( wp_list_pluck( $meta_data, 'key' ), $keys );
					$array_keys = array_keys( $keys );
					if ( ! empty( $array_keys ) ) {
						$value = $meta_data[ current( $array_keys ) ]->value;
						$order->update_meta_data( $meta_key, $value );
						$order->save();
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Gateways can override this method to add attributes to the Stripe object before it's
	 * sent to Stripe.
	 *
	 * @param array    $args
	 * @param WC_Order $order
	 */
	public function add_stripe_order_args( &$args, $order ) {
	}

	/**
	 *
	 * @param WP_Error $result
	 *
	 * @since 3.1.1
	 */
	public function get_generic_error( $result = null ) {
		$messages = wc_stripe_get_error_messages();
		if ( isset( $messages["{$this->id}_generic"] ) ) {
			return $messages["{$this->id}_generic"];
		}

		return null != $result ? $result->get_error_message() : __( 'Cannot process payment', 'woo-stripe-payment' );
	}

	/**
	 *
	 * @since 3.1.2
	 */
	private function get_payment_section_description() {
		return sprintf( __( 'Increase your conversion rate by offering %1$s on your Product and Cart pages, or at the top of the checkout page. <br/><strong>Note:</strong> you can control which products display %s by going to the product edit page.',
			'woo-stripe-payment' ),
			$this->get_method_title() );
	}

	/**
	 * Outputs fields required by Google Pay to render the payment wallet.
	 *
	 * @param string $page
	 * @param array  $data
	 */
	public function output_display_items( $page = 'checkout', $data = array() ) {
		global $wp;
		$data = wp_parse_args( $data, array(
			'items'            => $this->has_digital_wallet ? $this->get_display_items( $page ) : array(),
			'shipping_options' => $this->has_digital_wallet ? $this->get_formatted_shipping_methods() : array(),
			'total'            => wc_format_decimal( WC()->cart->total, 2 ),
			'total_cents'      => wc_stripe_add_number_precision( WC()->cart->total, get_woocommerce_currency() ),
			'currency'         => get_woocommerce_currency(),
			'installments'     => array( 'enabled' => $this->is_installment_available() )
		) );
		if ( in_array( $page, array( 'checkout', 'cart' ) ) ) {
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				$order                  = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
				$page                   = 'order_pay';
				$data['needs_shipping'] = false;
				$data['items']          = $this->has_digital_wallet ? $this->get_display_items( $page, $order ) : array();
				$data['total']          = $order->get_total();
				$data['total_cents']    = wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() );
				$data['currency']       = $order->get_currency();
				$data['pre_order']      = $this->order_contains_pre_order( $order );
				$data['order']          = array( 'id' => $order->get_id(), 'key' => $order->get_order_key() );
			} else {
				$data['needs_shipping'] = WC()->cart->needs_shipping();
				if ( 'checkout' === $page && is_cart() ) {
					$page = 'cart';
				} elseif ( is_add_payment_method_page() ) {
					$page = 'add_payment_method';
				}
			}
		} elseif ( 'product' === $page ) {
			global $product;
			$price                  = wc_get_price_to_display( $product );
			$data['needs_shipping'] = $product->needs_shipping();
			$data['product']        = array(
				'id'          => $product->get_id(),
				'price'       => $price,
				'price_cents' => wc_stripe_add_number_precision( $price, get_woocommerce_currency() ),
				'variation'   => false
			);
		}
		/**
		 * @param array                     $data
		 * @param string                    $page
		 * @param WC_Payment_Gateway_Stripe $this
		 *
		 * @since 3.1.8
		 */
		$data = wp_json_encode( apply_filters( 'wc_stripe_output_display_items', $data, $page, $this ) );
		$data = function_exists( 'wc_esc_json' ) ? wc_esc_json( $data ) : _wp_specialchars( $data, ENT_QUOTES, 'UTF-8', true );
		printf( '<input type="hidden" class="%1$s" data-gateway="%2$s"/>', "woocommerce_{$this->id}_gateway_data {$page}-page", $data );
	}

	/**
	 * @param array $deps
	 * @param       $scripts
	 *
	 * @since 3.1.8
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		return $deps;
	}

	/**
	 * @since 3.2.0
	 * @return array
	 */
	public function get_shipping_packages() {
		$packages = WC()->shipping()->get_packages();
		if ( empty( $packages ) && wcs_stripe_active() && WC_Subscriptions_Cart::cart_contains_free_trial() ) {
			// there is a subscription with a free trial in the cart. Shipping packages will be in the recurring cart.
			WC_Subscriptions_Cart::set_calculation_type( 'recurring_total' );
			$count = 0;
			if ( isset( WC()->cart->recurring_carts ) ) {
				foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
					foreach ( $recurring_cart->get_shipping_packages() as $i => $base_package ) {
						$packages[ $recurring_cart_key . '_' . $count ] = WC_Subscriptions_Cart::get_calculated_shipping_for_package( $base_package );
					}
					$count ++;
				}
			}
			WC_Subscriptions_Cart::set_calculation_type( 'none' );
		}

		return $packages;
	}

	/**
	 * @param WC_Cart $cart
	 * @param array   $items
	 *
	 * @since 3.2.1
	 * @return array
	 */
	public function get_display_items_for_cart( $cart, $items = array() ) {
		$incl_tax = wc_stripe_display_prices_including_tax();
		foreach ( $cart->get_cart() as $cart_item ) {
			/**
			 *
			 * @var WC_Product $product
			 */
			$product = $cart_item['data'];
			$qty     = $cart_item['quantity'];
			$label   = $qty > 1 ? sprintf( '%s X %s', $product->get_name(), $qty ) : $product->get_name();
			$price   = $incl_tax ? wc_get_price_including_tax( $product, array( 'qty' => $qty ) ) : wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) );
			$items[] = $this->get_display_item_for_cart( $price, $label, 'product', $cart_item, $cart );
		}
		if ( $cart->needs_shipping() ) {
			$price   = $incl_tax ? $cart->shipping_total + $cart->shipping_tax_total : $cart->shipping_total;
			$items[] = $this->get_display_item_for_cart( $price, __( 'Shipping', 'woo-stripe-payment' ), 'shipping' );
		}
		foreach ( $cart->get_fees() as $fee ) {
			$price   = $incl_tax ? $fee->total + $fee->tax : $fee->total;
			$items[] = $this->get_display_item_for_cart( $price, $fee->name, 'fee', $fee, $cart );
		}
		if ( 0 < $cart->discount_cart ) {
			$price   = - 1 * abs( $incl_tax ? $cart->discount_cart + $cart->discount_cart_tax : $cart->discount_cart );
			$items[] = $this->get_display_item_for_cart( $price, __( 'Discount', 'woo-stripe-payment' ), 'discount', $cart );
		}
		if ( ! $incl_tax && wc_tax_enabled() ) {
			$items[] = $this->get_display_item_for_cart( $cart->get_taxes_total(), __( 'Tax', 'woo-stripe-payment' ), 'tax', $cart );
		}

		return $items;
	}

	/**
	 * @param WC_Order $order
	 * @param array    $items
	 *
	 * @since 3.2.1
	 * @return array
	 */
	protected function get_display_items_for_order( $order, $items = array() ) {
		foreach ( $order->get_items() as $item ) {
			$qty     = $item->get_quantity();
			$label   = $qty > 1 ? sprintf( '%s X %s', $item->get_name(), $qty ) : $item->get_name();
			$items[] = $this->get_display_item_for_order( $item->get_subtotal(), $label, $order, 'item', $item );
		}
		if ( 0 < $order->get_shipping_total() ) {
			$items[] = $this->get_display_item_for_order( $order->get_shipping_total(), __( 'Shipping', 'woo-stripe-payment' ), $order, 'shipping' );
		}
		if ( 0 < $order->get_total_discount() ) {
			$items[] = $this->get_display_item_for_order( - 1 * $order->get_total_discount(), __( 'Discount', 'woo-stripe-payment' ), $order, 'discount' );
		}
		if ( 0 < $order->get_fees() ) {
			$fee_total = 0;
			foreach ( $order->get_fees() as $fee ) {
				$fee_total += $fee->get_total();
			}
			$items[] = $this->get_display_item_for_order( $fee_total, __( 'Fees', 'woo-stripe-payment' ), $order, 'fee' );
		}
		if ( 0 < $order->get_total_tax() ) {
			$items[] = $this->get_display_item_for_order( $order->get_total_tax(), __( 'Tax', 'woocommerce' ), $order, 'tax' );
		}

		return $items;
	}

	/**
	 * @param float  $price
	 * @param string $label
	 * @param string $type
	 * @param mixed  ...$args
	 *
	 * @since 3.2.1
	 * @return array
	 */
	protected function get_display_item_for_cart( $price, $label, $type, ...$args ) {
		return array(
			'label'   => $label,
			'pending' => false,
			'amount'  => wc_stripe_add_number_precision( $price )
		);
	}

	/**
	 * @param float    $price
	 * @param string   $label
	 * @param WC_Order $order
	 * @param string   $type
	 * @param mixed    ...$args
	 */
	protected function get_display_item_for_order( $price, $label, $order, $type, ...$args ) {
		return array(
			'label'   => $label,
			'pending' => false,
			'amount'  => wc_stripe_add_number_precision( $price, $order->get_currency() )
		);
	}

	/**
	 * @param WC_Product $product
	 *
	 * @since 3.2.1
	 *
	 * @return array
	 */
	protected function get_display_item_for_product( $product ) {
		return array(
			'label'   => esc_attr( $product->get_name() ),
			'pending' => true,
			'amount'  => wc_stripe_add_number_precision( $product->get_price() )
		);
	}

	/**
	 * @param array $methods
	 * @param       $sort
	 *
	 * @since 3.2.1
	 * @return array
	 */
	public function get_formatted_shipping_methods( $methods = array() ) {
		if ( wcs_stripe_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
			return $methods;
		} else {
			$methods        = array();
			$chosen_methods = array();
			$packages       = $this->get_shipping_packages();
			$incl_tax       = wc_stripe_display_prices_including_tax();
			foreach ( WC()->session->get( 'chosen_shipping_methods', array() ) as $i => $id ) {
				$chosen_methods[] = $this->get_shipping_method_id( $id, $i );
			}
			foreach ( $packages as $i => $package ) {
				foreach ( $package['rates'] as $rate ) {
					$price     = $incl_tax ? $rate->cost + $rate->get_shipping_tax() : $rate->cost;
					$methods[] = $this->get_formatted_shipping_method( $price, $rate, $i, $package, $incl_tax );
				}
			}

			/**
			 * Sort shipping methods so the selected method is first in the array.
			 */
			usort( $methods, function ( $method ) use ( $chosen_methods ) {
				foreach ( $chosen_methods as $id ) {
					if ( in_array( $id, $method, true ) ) {
						return - 1;
					}
				}

				return 1;
			} );
		}

		/**
		 * @param array $methods
		 *
		 * @since 3.3.0
		 */
		return apply_filters( 'wc_stripe_get_formatted_shipping_methods', $methods, $this );
	}

	/**
	 * @param float            $price
	 * @param WC_Shipping_Rate $rate
	 * @param string           $i
	 * @param array            $package
	 * @param bool             $incl_tax
	 *
	 * @since 3.2.1
	 * @return array
	 */
	public function get_formatted_shipping_method( $price, $rate, $i, $package, $incl_tax ) {
		$method = array(
			'id'     => $this->get_shipping_method_id( $rate->id, $i ),
			'label'  => $this->get_formatted_shipping_label( $price, $rate, $incl_tax ),
			'detail' => '',
			'amount' => wc_stripe_add_number_precision( $price )
		);
		if ( $incl_tax ) {
			if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
				$method['detail'] = WC()->countries->inc_tax_or_vat();
			}
		} else {
			if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$method['detail'] = WC()->countries->ex_tax_or_vat();
			}
		}

		return $method;
	}

	/**
	 * @param string $id
	 * @param string $index
	 *
	 * @return mixed
	 */
	protected function get_shipping_method_id( $id, $index ) {
		return sprintf( '%s:%s', $index, $id );
	}

	/**
	 * @param float            $price
	 * @param WC_Shipping_Rate $rate
	 * @param bool             $incl_tax
	 *
	 * @since 3.2.1
	 */
	protected function get_formatted_shipping_label( $price, $rate, $incl_tax ) {
		return sprintf( '%s', esc_attr( $rate->get_label() ) );
	}

	/**
	 * Returns true if a scheduled subscription payment is being processed.
	 *
	 * @since 3.2.3
	 * @return bool
	 */
	protected function is_processing_scheduled_payment() {
		return doing_action( 'woocommerce_scheduled_subscription_payment_' . $this->id );
	}

	/**
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 *
	 * @since 3.2.5
	 * @return bool
	 */
	public function has_enqueued_scripts( $scripts ) {
		return false;
	}

	public function get_transaction_url( $order ) {
		if ( wc_stripe_order_mode( $order ) === 'test' ) {
			$this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s';
		} else {
			$this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * @param WC_Subscription $subscription
	 *
	 * @since 3.2.13
	 * @return array
	 */
	protected function process_change_payment_method_request( $subscription ) {
		if ( ! $this->use_saved_source() ) {
			$result = $this->save_payment_method( $this->get_new_source_token(), $subscription );
			if ( is_wp_error( $result ) ) {
				wc_add_notice( sprintf( __( 'Error saving payment method for subscription. Reason: %s', 'woo-stripe-payment' ), $result->get_error_message() ), 'error' );

				return array( 'result' => 'error' );
			}
		} else {
			$this->payment_method_token = $this->get_saved_source_id();
		}
		$token = $this->get_token( $this->payment_method_token, $subscription->get_user_id() );
		// update the meta data needed by the gateway to process a subscription payment.
		$subscription->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $this->payment_method_token );
		$subscription->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $token->get_customer_id() );
		if ( $token ) {
			$subscription->set_payment_method_title( $token->get_payment_method_title() );
		}
		$subscription->save();

		return array( 'result' => 'success', 'redirect' => wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * @param WC_Subscription $subscription
	 * @param WC_Order        $order
	 */
	public function update_failing_payment_method( $subscription, $order ) {
		if ( ( $token = $this->get_token( $order->get_meta( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN ), $order->get_customer_id() ) ) ) {
			$subscription->update_meta_data( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$subscription->update_meta_data( WC_Stripe_Constants::CUSTOMER_ID, $token->get_customer_id() );
			$subscription->set_payment_method_title( $token->get_payment_method_title( $this->get_option( 'method_format' ) ) );
			$subscription->save();
		}
	}

	/**
	 * @param array $options
	 *
	 * @since 3.3.10
	 * @return mixed|void
	 */
	public function get_element_options( $options = array() ) {
		$options = array_merge( array( 'locale' => wc_stripe_get_site_locale() ), $options );

		return apply_filters( 'wc_stripe_get_element_options', $options, $this );
	}

	public function is_installment_available() {
		return false;
	}

}
