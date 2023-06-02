<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author  PaymentPlugins
 * @package Stripe/Classes
 * @property WC_Stripe_Rest_Controller  $order_actions
 * @property WC_Stripe_Rest_Controller  $cart
 * @property WC_Stripe_Rest_Controller  $checkout
 * @property WC_Stripe_Rest_Controller  $payment_intent
 * @property WC_Stripe_Rest_Controller  $googlepay
 * @property WC_Stripe_Rest_Controller  $settings
 * @property WC_Stripe_Rest_Controller  $webhook
 * @property WC_Stripe_Rest_Controller  $product_data
 * @property WC_Stripe_Rest_Controller  $source
 * @property \WC_Stripe_Rest_Controller $signup
 */
class WC_Stripe_Rest_API {

	/**
	 *
	 * @var array
	 */
	private $controllers = array();

	public function __construct() {
		$this->include_classes();
		add_action( 'wc_ajax_wc_stripe_frontend_request', array( $this, 'process_frontend_request' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_ajax_wc_stripe_admin_request', array( $this, 'process_frontend_request' ) );
	}

	/**
	 *
	 * @param string $key
	 *
	 * @return WC_Stripe_Rest_Controller
	 */
	public function __get( $key ) {
		$controller = isset( $this->controllers[ $key ] ) ? $this->controllers[ $key ] : '';
		if ( empty( $controller ) ) {
			wc_doing_it_wrong( __FUNCTION__,
				sprintf( __( '%1$s is an invalid controller name.', 'woo-stripe-payment' ), $key ),
				stripe_wc()->version );
		}

		return $controller;
	}

	public function __set( $key, $value ) {
		$this->controllers[ $key ] = $value;
	}

	private function include_classes() {
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-stripe-rest-controller.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-order-actions.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-payment-intent.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-cart.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-checkout.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-googlepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-payment-method.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-gateway-settings.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-webhook.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-product-data.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-source.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/controllers/class-wc-stripe-controller-signup.php';

		foreach ( $this->get_controllers() as $key => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$key} = new $class_name();
			}
		}
	}

	public function register_routes() {
		if ( self::is_rest_api_request() ) {
			foreach ( $this->controllers as $key => $controller ) {
				if ( is_callable( array( $controller, 'register_routes' ) ) ) {
					$controller->register_routes();
				}
			}
		}
	}

	public function get_controllers() {
		$controllers = array(
			'order_actions'  => 'WC_Stripe_Controller_Order_Actions',
			'checkout'       => 'WC_Stripe_Controller_Checkout',
			'cart'           => 'WC_Stripe_Controller_Cart',
			'payment_intent' => 'WC_Stripe_Controller_Payment_Intent',
			'googlepay'      => 'WC_Stripe_Controller_GooglePay',
			'payment_method' => 'WC_Stripe_Controller_Payment_Method',
			'settings'       => 'WC_Stripe_Controller_Gateway_Settings',
			'webhook'        => 'WC_Stripe_Controller_Webhook',
			'product_data'   => 'WC_Stripe_Controller_Product_Data',
			'source'         => 'WC_Stripe_Controller_Source',
			'signup'         => 'WC_Stripe_Controller_SignUp'
		);

		/**
		 * @param string[] $controllers
		 */
		return apply_filters( 'wc_stripe_api_controllers', $controllers );
	}

	/**
	 * @return string
	 */
	public function rest_url() {
		return stripe_wc()->rest_url();
	}

	/**
	 * @return string
	 */
	public function rest_uri() {
		return stripe_wc()->rest_uri();
	}

	/**
	 * @return bool
	 */
	public static function is_rest_api_request() {
		global $wp;
		if ( ! empty( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], stripe_wc()->rest_uri() ) !== false ) {
			return true;
		}
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], stripe_wc()->rest_uri() ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Return true if this is a WP rest request. This function is a wrapper for WC()->is_rest_api_request()
	 * if it exists.
	 *
	 * @since 3.2.7
	 * @return bool
	 */
	public static function is_wp_rest_request() {
		if ( function_exists( 'WC' ) && property_exists( WC(), 'is_rest_api_request' ) ) {
			return WC()->is_rest_api_request();
		}

		return ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) ) !== false;
	}

	/**
	 * @since 3.2.7
	 */
	public function process_frontend_request() {
		if ( isset( $_GET['path'] ) ) {
			global $wp;
			$wp->set_query_var( 'rest_route', sanitize_text_field( $_GET['path'] ) );
			rest_api_loaded();
		}
	}

	/**
	 * Return an endpoint for ajax requests that integrate with the WP Rest API.
	 *
	 * @param string $path
	 *
	 * @since 3.2.7
	 * @return string
	 */
	public static function get_endpoint( $path ) {
		if ( version_compare( WC()->version, '3.2.0', '<' ) ) {
			$endpoint = esc_url_raw( apply_filters( 'woocommerce_ajax_get_endpoint',
				add_query_arg( 'wc-ajax',
					'wc_stripe_frontend_request',
					remove_query_arg( array(
						'remove_item',
						'add-to-cart',
						'added-to-cart',
						'order_again',
						'_wpnonce'
					), home_url( '/', 'relative' ) ) ),
				'wc_stripe_frontend_request' ) );
		} else {
			$endpoint = WC_AJAX::get_endpoint( 'wc_stripe_frontend_request' );
		}

		return add_query_arg( 'path', '/' . trim( $path, '/' ), $endpoint );
	}

	public static function get_admin_endpoint( $path ) {
		$url = admin_url( 'admin-ajax.php' );

		return add_query_arg( array( 'action' => 'wc_stripe_admin_request', 'path' => '/' . trim( $path, '/' ) ), $url );
	}

}