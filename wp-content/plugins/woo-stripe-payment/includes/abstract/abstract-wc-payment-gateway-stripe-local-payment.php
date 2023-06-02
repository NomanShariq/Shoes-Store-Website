<?php

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 * Local payment method classes should extend this abstract class
 *
 * @package Stripe/Abstract
 * @author  Payment Plugins
 *
 */
abstract class WC_Payment_Gateway_Stripe_Local_Payment extends WC_Payment_Gateway_Stripe {

	protected $tab_title = '';

	/**
	 * Currencies this gateway accepts
	 *
	 * @var array
	 */
	public $currencies = array();

	public $local_payment_type = '';

	public $countries = array();

	/**
	 * @var array
	 * @since 3.2.10
	 */
	public $limited_countries = array();

	protected $local_payment_description = '';

	public $token_type = 'Stripe_Local';

	public function __construct() {
		$this->template_name = 'local-payment.php';
		parent::__construct();

		if ( ! isset( $this->form_fields['method_format'] ) ) {
			$this->settings['method_format'] = 'gateway_title';
		}
		if ( ! isset( $this->form_fields['charge_type'] ) ) {
			$this->settings['charge_type'] = 'capture';
		}

		$this->settings['order_status'] = 'default';
		$this->order_button_text        = $this->get_option( 'order_button_text' );
	}

	public function hooks() {
		parent::hooks();
		add_filter( 'wc_stripe_local_gateway_tabs', array( $this, 'local_gateway_tab' ) );
		remove_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_filter( 'wc_stripe_local_gateways_tab', array( $this, 'admin_nav_tab' ) );
	}

	/**
	 * @param WC_Stripe_Frontend_Scripts $scripts
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_local_payment_scripts();
	}

	/**
	 *
	 * @param \Stripe\Source $source
	 * @param WC_Order       $order
	 */
	public function get_source_redirect_url( $source, $order ) {
		return $source->redirect->url;
	}

	public function output_settings_nav() {
		parent::output_settings_nav();
		include stripe_wc()->plugin_path() . 'includes/admin/views/html-settings-local-payments-nav.php';
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_stripe_form_fields_' . $this->id, $this->get_local_payment_settings() );
	}

	public function init_supports() {
		$this->supports = array( 'tokenization', 'products', 'refunds' );
	}

	/**
	 * Return an array of form fields for the gateway.
	 *
	 * @return array
	 */
	public function get_local_payment_settings() {
		return array(
			'desc'               => array(
				'type'        => 'description',
				'description' => array( $this, 'get_payment_description' ),
			),
			'enabled'            => array(
				'title'       => __( 'Enabled', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => sprintf( __( 'If enabled, your site can accept %s payments through Stripe.', 'woo-stripe-payment' ), $this->get_method_title() ),
			),
			'general_settings'   => array(
				'type'  => 'title',
				'title' => __( 'General Settings', 'woo-stripe-payment' ),
			),
			'title_text'         => array(
				'type'        => 'text',
				'title'       => __( 'Title', 'woo-stripe-payment' ),
				'default'     => $this->get_method_title(),
				'desc_tip'    => true,
				'description' => sprintf( __( 'Title of the %s gateway' ), $this->get_method_title() ),
			),
			'description'        => array(
				'title'       => __( 'Description', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
				'desc_tip'    => true,
			),
			'order_button_text'  => array(
				'title'       => __( 'Order Button Text', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => $this->get_order_button_text( $this->method_title ),
				'description' => __( 'The text on the Place Order button that displays when the gateway is selected on the checkout page.', 'woo-stripe-payment' ),
				'desc_tip'    => true
			),
			'allowed_countries'  => array(
				'title'    => __( 'Selling location(s)', 'woocommerce' ),
				'desc'     => __( 'This option lets you limit which countries you are willing to sell to.', 'woocommerce' ),
				'default'  => 'specific',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select wc-stripe-allowed-countries',
				'css'      => 'min-width: 350px;',
				'desc_tip' => true,
				'options'  => array(
					'all'        => __( 'Sell to all countries', 'woocommerce' ),
					'all_except' => __( 'Sell to all countries, except for&hellip;', 'woocommerce' ),
					'specific'   => __( 'Sell to specific countries', 'woocommerce' ),
				),
			),
			'except_countries'   => array(
				'title'             => __( 'Sell to all countries, except for&hellip;', 'woocommerce' ),
				'type'              => 'multi_select_countries',
				'css'               => 'min-width: 350px;',
				'options'           => $this->limited_countries,
				'default'           => array(),
				'desc_tip'          => true,
				'description'       => __( 'When the billing country matches one of these values, the payment method will be hidden on the checkout page.', 'woo-stripe-payment' ),
				'custom_attributes' => array( 'data-show-if' => array( 'allowed_countries' => 'all_except' ) ),
				'sanitize_callback' => function ( $value ) {
					return is_array( $value ) ? $value : array();
				}
			),
			'specific_countries' => array(
				'title'             => __( 'Sell to specific countries', 'woocommerce' ),
				'type'              => 'multi_select_countries',
				'css'               => 'min-width: 350px;',
				'options'           => $this->limited_countries,
				'default'           => $this->countries,
				'desc_tip'          => true,
				'description'       => __( 'When the billing country matches one of these values, the payment method will be shown on the checkout page.', 'woo-stripe-payment' ),
				'custom_attributes' => array( 'data-show-if' => array( 'allowed_countries' => 'specific' ) ),
				'sanitize_callback' => function ( $value ) {
					return is_array( $value ) ? $value : array();
				}
			)
		);
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'local_payment_type' => $this->local_payment_type,
				'return_url'         => add_query_arg(
					array(
						'key'                   => wp_create_nonce( 'local-payment' ),
						'_stripe_local_payment' => $this->id,
					),
					wc_get_checkout_url()
				),
				'element_params'     => $this->get_element_params(),
				'routes'             => array(
					'delete_order_source' => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->checkout->rest_uri( 'order/source' ) ),
					'update_source'       => WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->source->rest_uri( 'update' ) )
				)
			)
		);
	}

	public function get_element_params() {
		return array(
			'style' => array(
				'base'    => array(
					'padding'       => '10px 12px',
					'color'         => '#32325d',
					'fontFamily'    => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
					'fontSmoothing' => 'antialiased',
					'fontSize'      => '16px',
					'::placeholder' => array( 'color' => '#aab7c4' ),
				),
				'invalid' => array( 'color' => '#fa755a' ),
			),
		);
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public function get_source_args( $order ) {
		$args = array(
			'type'                 => $this->local_payment_type,
			'amount'               => wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() ),
			'currency'             => $order->get_currency(),
			'statement_descriptor' => sprintf( __( 'Order %s', 'woo-stripe-payment' ), $order->get_order_number() ),
			'owner'                => $this->get_source_owner_args( $order ),
			'redirect'             => array( 'return_url' => $this->get_local_payment_return_url( $order ) ),
		);

		/**
		 * @param $args
		 *
		 * @since 3.1.8
		 */
		return apply_filters( 'wc_stripe_get_source_args', $args );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @retun array
	 * @since 3.2.4
	 */
	public function get_update_source_args( $order ) {
		return array(
			'owner'    => $this->get_source_owner_args( $order ),
			'metadata' => array(
				'order_id' => $order->get_id(),
				'created'  => time(),
			),
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	protected function get_source_owner_args( $order ) {
		$owner = array(
			'name'    => $this->payment_object->get_name_from_order( $order, 'billing' ),
			'address' => array(
				'city'        => $order->get_billing_city(),
				'country'     => $order->get_billing_country(),
				'line1'       => $order->get_billing_address_1(),
				'line2'       => $order->get_billing_address_2(),
				'postal_code' => $order->get_billing_postcode(),
				'state'       => $order->get_billing_state(),
			)
		);
		if ( ( $email = $order->get_billing_email() ) ) {
			$owner['email'] = $email;
		}
		if ( ( $phone = $order->get_billing_phone() ) ) {
			$owner['phone'] = $phone;
		}

		return $owner;
	}

	/**
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function get_local_payment_return_url( $order ) {
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$url = $order->get_checkout_payment_url();
		} else {
			$url = wc_get_checkout_url();
		}

		return add_query_arg(
			array(
				'key'                   => $order->get_order_key(),
				'order_id'              => $order->get_id(),
				'_stripe_local_payment' => $this->id,
			),
			$url
		);
	}

	public function is_local_payment_available() {
		global $wp;
		$_available = false;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$order           = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
			$currency        = $order->get_currency();
			$billing_country = $order->get_billing_country();
			$total           = $order->get_total();
		} else {
			$currency        = get_woocommerce_currency();
			$customer        = WC()->customer;
			$billing_country = $customer ? $customer->get_billing_country() : null;
			$total           = WC()->cart ? WC()->cart->total : 0;
			if ( ! $billing_country ) {
				$billing_country = WC()->countries->get_base_country();
			}
		}
		if ( in_array( $currency, $this->currencies ) ) {
			$type = $this->get_option( 'allowed_countries' );
			if ( 'all_except' === $type ) {
				$_available = ! in_array( $billing_country, $this->get_option( 'except_countries', array() ) );
			} elseif ( 'specific' === $type ) {
				$_available = in_array( $billing_country, $this->get_option( 'specific_countries', array() ) );
			} else {
				$_available = ! $this->limited_countries || in_array( $billing_country, $this->limited_countries );
			}
		}
		if ( $_available && method_exists( $this, 'validate_local_payment_available' ) ) {
			$_available = $this->validate_local_payment_available( $currency, $billing_country, $total );
		}

		/**
		 * @param array
		 * @param WC_Payment_Gateway_Stripe_Local_Payment
		 *
		 * @since 3.2.10
		 */
		return apply_filters( 'wc_stripe_local_payment_available', $_available, $this );
	}

	public function get_payment_token( $method_id, $method_details = array() ) {
		/**
		 *
		 * @var WC_Payment_Token_Stripe_Local $token
		 */
		$token = parent::get_payment_token( $method_id, $method_details );
		$token->set_gateway_title( $this->title );

		return $token;
	}

	/**
	 * Return a description for (for admin sections) describing the required currency & or billing country(s).
	 *
	 * @return string
	 */
	protected function get_payment_description() {
		$desc = '';
		if ( $this->currencies ) {
			$desc .= sprintf( __( 'Gateway will appear when store currency is <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->currencies ) );
		}
		if ( 'all_except' === $this->get_option( 'allowed_countries' ) ) {
			$desc .= sprintf( __( ' & billing country is not <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->get_option( 'except_countries' ) ) );
		} elseif ( 'specific' === $this->get_option( 'allowed_countries' ) ) {
			$desc .= sprintf( __( ' & billing country is <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->get_option( 'specific_countries' ) ) );
		} else {
			if ( $this->limited_countries ) {
				$desc .= sprintf( __( ' & billing country is <strong>%s</strong>', 'woo-stripe-payment' ), implode( ', ', $this->limited_countries ) );
			}
		}

		return $desc;
	}

	/**
	 * Return a description of the payment method.
	 */
	public function get_local_payment_description() {
		return apply_filters( 'wc_stripe_local_payment_description', $this->local_payment_description, $this );
	}

	/**
	 *
	 * @param string $text
	 *
	 * @since 3.1.3
	 * @return string
	 */
	public function get_order_button_text( $text ) {
		return apply_filters( 'wc_stripe_order_button_text', sprintf( __( 'Pay with %s', 'woo-stripe-payment' ), $text ), $this );
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'local-payment' ) );
	}

	public function get_stripe_documentation_url() {
		return 'https://docs.paymentplugins.com/wc-stripe/config/#/stripe_local_gateways';
	}

}
