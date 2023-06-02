<?php

defined( 'ABSPATH' ) || exit();

/**
 * Singleton class that handles plugin functionality like class loading.
 *
 * @since   3.0.0
 * @author  PaymentPlugins
 * @package Stripe/Classes
 *
 */
class WC_Stripe_Manager {

	public static $_instance;

	public static function instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 *
	 * @var string
	 */
	public $version = '3.3.39';

	/**
	 *
	 * @var WC_Stripe_Settings_API
	 */
	public $api_settings;

	/**
	 * @var \WC_Stripe_Account_Settings
	 * @since 3.1.7
	 */
	public $account_settings;

	/**
	 * @var \WC_Stripe_Advanced_Settings
	 * @since 3.3.13
	 */
	public $advanced_settings;

	/**
	 *
	 * @var WC_Stripe_Rest_API
	 */
	public $rest_api;

	/**
	 *
	 * @var string
	 */
	public $client_id = 'ca_Gp4vLOJiqHJLZGxakHW7JdbBlcgWK8Up';

	/**
	 * Test client id;
	 *
	 * @var string
	 */
	//public $client_id = 'ca_Gp4vL3V6FpTguYoZIehD5COPeI80rLpV';

	/**
	 *
	 * @var WC_Stripe_Frontend_Scripts
	 */
	private $scripts;

	/**
	 *
	 * @var array
	 */
	private $payment_gateways;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'woocommerce_init', array( $this, 'woocommerce_dependencies' ) );
		add_action( 'woocommerce_blocks_loaded', array( '\PaymentPlugins\Blocks\Stripe\Package', 'init' ) );
		$this->includes();
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * Return the url for the plugin assets.
	 *
	 * @return string
	 */
	public function assets_url( $uri = '' ) {
		$url = WC_STRIPE_ASSETS . $uri;
		if ( ! preg_match( '/(\.js)|(\.css)|(\.svg)|(\.png)/', $uri ) ) {
			return trailingslashit( $url );
		}

		return $url;
	}

	/**
	 * Return the dir path for the plugin.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return WC_STRIPE_PLUGIN_FILE_PATH;
	}

	public function plugins_loaded() {
		load_plugin_textdomain( 'woo-stripe-payment', false, dirname( WC_STRIPE_PLUGIN_NAME ) . '/i18n/languages' );

		/**
		 * Version 4.5.4 of the WooCommerce Stripe Gateway plugin also includes a function named wc_stripe so don't include if that plugin
		 * is installed to prevent conflicts.
		 */
		if ( ! function_exists( 'wc_stripe' ) ) {
			if ( ( defined( 'WC_STRIPE_VERSION' ) && version_compare( WC_STRIPE_VERSION, '4.5.4', '<' ) )
			     || ! in_array( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
					(array) get_option( 'active_plugins', array() ),
					true )
			        && ! ( is_admin() && ! isset( $_GET['activate'], $_GET['plugin'] ) )
			) {
				/**
				 * Returns the global instance of the WC_Stripe_Manager.
				 *
				 * @return WC_Stripe_Manager
				 * @deprecated 3.2.8
				 * @package    Stripe/Functions
				 */
				function wc_stripe() {
					if ( function_exists( 'wc_deprecated_function' ) ) {
						wc_deprecated_function( 'wc_stripe', '3.2.8', 'stripe_wc' );
					}

					return stripe_wc();
				}
			}
		}

		\PaymentPlugins\CartFlows\Stripe\Main::init();
		\PaymentPlugins\WooFunnels\Stripe\Main::init();
		\PaymentPlugins\CheckoutWC\Stripe\Main::init();
		\PaymentPlugins\Stripe\WooCommerceSubscriptions\Package::init();
		\PaymentPlugins\Stripe\WooCommercePreOrders\Package::init();
		\PaymentPlugins\Stripe\GermanMarket\Package::init();

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			add_action( 'before_woocommerce_init', function () {
				try {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_path() . 'stripe-payments.php', true );
				} catch ( \Exception $e ) {
				}
			} );
		}
	}

	/**
	 * Function that is hooked in to the WordPress init action.
	 */
	public function init() {
	}

	public function includes() {
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-constants.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-install.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-update.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-rest-api.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-gateway.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-payment-balance.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-utils.php';

		if ( is_admin() ) {
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-menus.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-welcome.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-support.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-assets.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-settings.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/meta-boxes/class-wc-stripe-admin-order-metaboxes.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/meta-boxes/class-wc-stripe-admin-meta-box-product-data.php';
		}
	}

	/**
	 * Function that is hooked in to the WordPress admin_init action.
	 */
	public function admin_init() {
	}

	public function woocommerce_dependencies() {
		// load functions
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/wc-stripe-functions.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/wc-stripe-webhook-functions.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/wc-stripe-hooks.php';

		// traits
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/traits/wc-stripe-settings-trait.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/traits/wc-stripe-controller-traits.php';

		// load factories
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-payment-factory.php';

		// load gateways
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-payment-gateway-stripe.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-payment-gateway-stripe-local-payment.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-cc.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-applepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-googlepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-ach.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-payment-request.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-ideal.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-p24.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-klarna.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-giropay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-eps.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-multibanco.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-sepa.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-sofort.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-wechat.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-bancontact.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-fpx.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-alipay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-becs.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-grabpay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-afterpay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-boleto.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-oxxo.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-affirm.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-blik.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-konbini.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/gateways/class-wc-payment-gateway-stripe-paynow.php';

		// tokens
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-payment-token-stripe.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-cc.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-applepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-googlepay.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-local-payment.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-ach.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-sepa.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/tokens/class-wc-payment-token-stripe-becs.php';

		// main classes
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-frontend-scripts.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-field-manager.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-rest-api.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-customer-manager.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-gateway-conversions.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-redirect-handler.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-product-gateway-option.php';

		// settings
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/abstract/abstract-wc-stripe-settings.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/settings/class-wc-stripe-api-settings.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/settings/class-wc-stripe-advanced-settings.php';
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/settings/class-wc-stripe-account-settings.php';

		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-api-request-filter.php';

		// shortcodes
		include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/class-wc-stripe-shortcodes.php';

		if ( is_admin() ) {
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-notices.php';
			include_once WC_STRIPE_PLUGIN_FILE_PATH . 'includes/admin/class-wc-stripe-admin-user-edit.php';
		}

		$this->payment_gateways = apply_filters(
			'wc_stripe_payment_gateways',
			array(
				'WC_Payment_Gateway_Stripe_CC',
				'WC_Payment_Gateway_Stripe_ApplePay',
				'WC_Payment_Gateway_Stripe_GooglePay',
				'WC_Payment_Gateway_Stripe_Payment_Request',
				'WC_Payment_Gateway_Stripe_Afterpay',
				'WC_Payment_Gateway_Stripe_Affirm',
				'WC_Payment_Gateway_Stripe_ACH',
				'WC_Payment_Gateway_Stripe_Ideal',
				'WC_Payment_Gateway_Stripe_P24',
				'WC_Payment_Gateway_Stripe_Klarna',
				'WC_Payment_Gateway_Stripe_Bancontact',
				'WC_Payment_Gateway_Stripe_Giropay',
				'WC_Payment_Gateway_Stripe_EPS',
				'WC_Payment_Gateway_Stripe_Multibanco',
				'WC_Payment_Gateway_Stripe_Sepa',
				'WC_Payment_Gateway_Stripe_Sofort',
				'WC_Payment_Gateway_Stripe_WeChat',
				'WC_Payment_Gateway_Stripe_FPX',
				'WC_Payment_Gateway_Stripe_BECS',
				'WC_Payment_Gateway_Stripe_Alipay',
				'WC_Payment_Gateway_Stripe_GrabPay',
				'WC_Payment_Gateway_Stripe_Boleto',
				'WC_Payment_Gateway_Stripe_OXXO',
				'WC_Payment_Gateway_Stripe_BLIK',
				'WC_Payment_Gateway_Stripe_Konbini',
				'WC_Payment_Gateway_Stripe_PayNow'
			)
		);

		$api_class      = apply_filters( 'wc_stripe_rest_api_class', 'WC_Stripe_Rest_API' );
		$this->rest_api = new $api_class();

		if ( $this->is_request( 'frontend' ) && class_exists( 'WC_Stripe_Frontend_Scripts' ) ) {
			$this->scripts();
		}

		// allow other plugins to provide their own settings classes.
		$setting_classes = apply_filters( 'wc_stripe_setting_classes', array(
			'api_settings'      => 'WC_Stripe_API_Settings',
			'account_settings'  => 'WC_Stripe_Account_Settings',
			'advanced_settings' => 'WC_Stripe_Advanced_Settings'
		) );
		foreach ( $setting_classes as $id => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$id} = new $class_name();
			}
		}

		new WC_Stripe_API_Request_Filter( $this->advanced_settings );

		new \PaymentPlugins\Stripe\Link\LinkIntegration( $this->advanced_settings, $this->account_settings, $this->assets(), $this->data_api() );
		new \PaymentPlugins\Stripe\Controllers\PaymentIntent( WC_Stripe_Gateway::load(), [ 'stripe_cc' ] );
		new \PaymentPlugins\Stripe\Messages\MessageController();
		new \PaymentPlugins\Stripe\Products\ProductController();
		new \PaymentPlugins\Stripe\Messages\BNPL\CategoryMessageController( [ 'stripe_affirm', 'stripe_afterpay' ] );
		new \PaymentPlugins\Stripe\Messages\BNPL\ProductMessageController( [ 'stripe_affirm', 'stripe_afterpay' ] );
		new \PaymentPlugins\Stripe\Messages\BNPL\CartMessageController( [ 'stripe_affirm', 'stripe_afterpay' ] );
	}

	/**
	 * Return the plugin template path.
	 */
	public function template_path() {
		return 'woo-stripe-payment';
	}

	/**
	 * Return the plguins default directory path for template files.
	 */
	public function default_template_path() {
		return WC_STRIPE_PLUGIN_FILE_PATH . 'templates/';
	}

	/**
	 *
	 * @return string
	 */
	public function rest_uri() {
		return 'wc-stripe/v1/';
	}

	/**
	 *
	 * @return string
	 */
	public function rest_url() {
		return get_rest_url( null, $this->rest_uri() );
	}

	/**
	 *
	 * @return WC_Stripe_Frontend_Scripts
	 */
	public function scripts() {
		if ( is_null( $this->scripts ) ) {
			$this->scripts = new WC_Stripe_Frontend_Scripts( $this->assets() );
		}

		return $this->scripts;
	}

	public function assets() {
		static $assets;
		if ( is_null( $assets ) ) {
			$assets = new \PaymentPlugins\Stripe\Assets\AssetsApi(
				WC_STRIPE_PLUGIN_FILE_PATH,
				trailingslashit( plugins_url( dirname( WC_STRIPE_PLUGIN_NAME ) ) ),
				$this->version()
			);
		}

		return $assets;
	}

	public function data_api() {
		static $data_api;
		if ( is_null( $data_api ) ) {
			$data_api = new \PaymentPlugins\Stripe\Assets\AssetDataApi();
		}

		return $data_api;
	}

	public function payment_gateways() {
		return $this->payment_gateways;
	}

	/**
	 * Schedule actions required by the plugin
	 *
	 * @since 3.1.6
	 */
	public function scheduled_actions() {
		if ( function_exists( 'WC' ) ) {
			if ( method_exists( WC(), 'queue' ) && ! WC()->queue()->get_next( 'wc_stripe_remove_order_locks' ) ) {
				WC()->queue()->schedule_recurring( strtotime( 'today midnight' ), DAY_IN_SECONDS, 'wc_stripe_remove_order_locks' );
			}
		}
	}

	/**
	 * @param string $type
	 *
	 * @since 3.1.9
	 * @return bool
	 */
	public function is_request( $type ) {
		if ( ! did_action( 'before_woocommerce_init' ) ) {
			return false;
		}
		switch ( $type ) {
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! WC_Stripe_Rest_API::is_wp_rest_request();
			default:
				return true;
		}
	}

}

/**
 * Returns the global instance of the WC_Stripe_Manager. This function replaces
 * the wc_stripe function as of version 3.2.8
 *
 * @since   3.2.8
 * @return WC_Stripe_Manager
 * @package Stripe/Functions
 */
function stripe_wc() {
	return WC_Stripe_Manager::instance();
}


// load singleton
stripe_wc();
