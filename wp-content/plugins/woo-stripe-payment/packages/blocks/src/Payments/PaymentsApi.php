<?php


namespace PaymentPlugins\Blocks\Stripe\Payments;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Registry\Container as Container;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use \PaymentPlugins\Blocks\Stripe\Assets\Api as AssetsApi;
use PaymentPlugins\Blocks\Stripe\Config;
use PaymentPlugins\Blocks\Stripe\Package;
use PaymentPlugins\Blocks\Stripe\Payments\Gateways\AffirmPayment;
use PaymentPlugins\Blocks\Stripe\Payments\Gateways\BlikPayment;
use PaymentPlugins\Blocks\Stripe\Payments\Gateways\KonbiniPayment;
use PaymentPlugins\Blocks\Stripe\Payments\Gateways\PayNowPayment;
use PaymentPlugins\Stripe\Controllers\PaymentIntent;
use PaymentPlugins\Stripe\Installments\InstallmentController;
use PaymentPlugins\Stripe\Link\LinkIntegration;

class PaymentsApi {

	private $container;

	private $config;

	private $assets_registry;

	/**
	 * @var Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry
	 */
	private $payment_method_registry;

	/**
	 * @var PaymentResult
	 */
	protected $payment_result;

	private $payment_methods = [];

	public function __construct( Container $container, Config $config, AssetDataRegistry $assets_registry ) {
		$this->container       = $container;
		$this->config          = $config;
		$this->assets_registry = $assets_registry;
		$this->add_payment_methods();
		$this->initialize();
	}

	private function initialize() {
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_methods' ) );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'enqueue_checkout_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'enqueue_cart_data' ) );
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'payment_with_context' ), 10, 2 );
		add_action( 'wc_stripe_blocks_enqueue_styles', array( $this, 'enqueue_payment_styles' ) );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_styles' ] );
	}

	private function add_payment_methods() {
		$this->container->register( Gateways\CreditCardPayment::class, function ( Container $container ) {
			$instance = new Gateways\CreditCardPayment( $container->get( AssetsApi::class ) );
			$instance->set_installments( InstallmentController::instance() );
			$instance->set_payment_intent_controller( PaymentIntent::instance() );

			return $instance;
		} );
		$this->container->register( Gateways\GooglePayPayment::class, function ( Container $container ) {
			return new Gateways\GooglePayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\ApplePayPayment::class, function ( Container $container ) {
			return new Gateways\ApplePayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\PaymentRequest::class, function ( Container $container ) {
			return new Gateways\PaymentRequest( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\IdealPayment::class, function ( Container $container ) {
			return new Gateways\IdealPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\P24Payment::class, function ( Container $container ) {
			return new Gateways\P24Payment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\BancontactPayment::class, function ( Container $container ) {
			return new Gateways\BancontactPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\GiropayPayment::class, function ( Container $container ) {
			return new Gateways\GiropayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\EPSPayment::class, function ( Container $container ) {
			return new Gateways\EPSPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\MultibancoPayment::class, function ( Container $container ) {
			return new Gateways\MultibancoPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\SepaPayment::class, function ( Container $container ) {
			return new Gateways\SepaPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\SofortPayment::class, function ( Container $container ) {
			return new Gateways\SofortPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\WeChatPayment::class, function ( Container $container ) {
			return new Gateways\WeChatPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\FPXPayment::class, function ( Container $container ) {
			return new Gateways\FPXPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\BECSPayment::class, function ( Container $container ) {
			return new Gateways\BECSPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\GrabPayPayment::class, function ( Container $container ) {
			return new Gateways\GrabPayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\AlipayPayment::class, function ( Container $container ) {
			return new Gateways\AlipayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\KlarnaPayment::class, function ( Container $container ) {
			return new Gateways\KlarnaPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\ACHPayment::class, function ( Container $container ) {
			return new Gateways\ACHPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\AfterpayPayment::class, function ( Container $container ) {
			return new Gateways\AfterpayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\BoletoPayment::class, function ( Container $container ) {
			return new Gateways\BoletoPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\OXXOPayment::class, function ( Container $container ) {
			return new Gateways\OXXOPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\LinkPayment::class, function ( $container ) {
			$instance = new Gateways\LinkPayment( LinkIntegration::instance(), $container->get( AssetsApi::class ) );
			$instance->set_payment_intent_controller( PaymentIntent::instance() );

			return $instance;
		} );
		$this->container->register( Gateways\AffirmPayment::class, function ( Container $container ) {
			return new AffirmPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\BlikPayment::class, function ( Container $container ) {
			return new BlikPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\KonbiniPayment::class, function ( Container $container ) {
			return new KonbiniPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( Gateways\PayNowPayment::class, function ( Container $container ) {
			return new PayNowPayment( $container->get( AssetsApi::class ) );
		} );
	}

	/**
	 * Register all payment methods used by the plugin.
	 *
	 * @param PaymentMethodRegistry $registry
	 */
	public function register_payment_methods( PaymentMethodRegistry $registry ) {
		//$payment_gateways              = WC()->payment_gateways()->payment_gateways();
		$this->payment_method_registry = $registry;
		$payment_methods               = array(
			Gateways\CreditCardPayment::class,
			Gateways\GooglePayPayment::class,
			Gateways\ApplePayPayment::class,
			Gateways\PaymentRequest::class,
			Gateways\IdealPayment::class,
			Gateways\P24Payment::class,
			Gateways\BancontactPayment::class,
			Gateways\GiropayPayment::class,
			Gateways\EPSPayment::class,
			Gateways\MultibancoPayment::class,
			Gateways\SepaPayment::class,
			Gateways\SofortPayment::class,
			Gateways\WeChatPayment::class,
			Gateways\FPXPayment::class,
			Gateways\BECSPayment::class,
			Gateways\GrabPayPayment::class,
			Gateways\AlipayPayment::class,
			Gateways\KlarnaPayment::class,
			Gateways\ACHPayment::class,
			Gateways\AfterpayPayment::class,
			Gateways\BoletoPayment::class,
			Gateways\OXXOPayment::class,
			Gateways\LinkPayment::class,
			Gateways\AffirmPayment::class,
			Gateways\BlikPayment::class,
			Gateways\KonbiniPayment::class,
			Gateways\PayNowPayment::class
		);

		foreach ( $payment_methods as $clazz ) {
			$this->add_payment_method_to_registry( $clazz, $registry );
		}
	}

	/**
	 * @param                       $clazz
	 * @param PaymentMethodRegistry $registry
	 */
	private function add_payment_method_to_registry( $clazz, $registry ) {
		$instance = $this->container->get( $clazz );
		$registry->register( $instance );
		$this->payment_methods[] = $instance;
	}

	/**
	 * @param \PaymentPlugins\Blocks\Stripe\Assets\Api $style_api
	 */
	public function enqueue_payment_styles( $style_api ) {
		foreach ( $this->payment_method_registry->get_all_registered() as $payment_method ) {
			if ( $payment_method instanceof AbstractStripePayment ) {
				$payment_method->enqueue_payment_method_styles( $style_api );
			}
		}
	}

	public function enqueue_editor_styles() {
		if ( wp_script_is( 'wc-checkout-block', 'registered' ) || wp_script_is( 'wc-cart-block', 'registered' ) ) {
			Package::container()->get( AssetsApi::class )->enqueue_style();
		}
	}

	public function enqueue_checkout_data() {
		$this->enqueue_data( 'checkout' );
	}

	public function enqueue_cart_data() {
		$this->enqueue_data( 'cart' );
	}

	private function enqueue_data( $page ) {
		if ( ! $this->assets_registry->exists( 'stripeGeneralData' ) ) {
			$this->assets_registry->add( 'stripeGeneralData', apply_filters( 'wc_stripe_blocks_general_data', array(
				'page'           => $page,
				'mode'           => wc_stripe_mode(),
				'publishableKey' => wc_stripe_get_publishable_key(),
				'stripeParams'   => [
					'stripeAccount' => wc_stripe_get_account_id(),
					'apiVersion'    => '2020-08-27',
					'betas'         => []
				],
				'version'        => $this->config->get_version(),
				'blocksVersion'  => \Automattic\WooCommerce\Blocks\Package::get_version(),
				'routes'         => array(
					'process/payment'       => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->checkout->rest_uri( 'checkout/payment' ) ),
					'create/setup_intent'   => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'setup-intent' ) ),
					'create/payment_intent' => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'payment-intent' ) ),
					'sync/intent'           => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->payment_intent->rest_uri( 'sync-payment-intent' ) ),
					'update/source'         => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->source->rest_uri( 'update' ) ),
					'payment/data'          => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->googlepay->rest_uri( 'shipping-data' ) ),
					'shipping-address'      => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'shipping-address' ) ),
					'shipping-method'       => \WC_Stripe_Rest_API::get_endpoint( stripe_wc()->rest_api->cart->rest_uri( 'shipping-method' ) )
				),
				'assetsUrl'      => stripe_wc()->assets_url()
			) ) );
		}
		if ( ! $this->assets_registry->exists( 'stripeErrorMessages' ) ) {
			$this->assets_registry->add( 'stripeErrorMessages', wc_stripe_get_error_messages() );
		}

		if ( ! $this->assets_registry->exists( 'stripePaymentData' ) ) {
			$payment_data = array();
			if ( WC()->cart && wc_stripe_pre_orders_active() && \WC_Pre_Orders_Cart::cart_contains_pre_order() && \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() ) ) {
				$payment_data['pre_order'] = true;
			}
			if ( WC()->cart && wcs_stripe_active() && \WC_Subscriptions_Cart::cart_contains_subscription() ) {
				$payment_data['subscription'] = true;
			}
			$this->assets_registry->add( 'stripePaymentData', $payment_data );
		}
	}

	public function payment_with_context( PaymentContext $context, PaymentResult $result ) {
		$this->payment_result = $result;
		add_action( 'wc_stripe_process_payment_error', array( $this, 'process_payment_error' ) );
	}

	/**
	 * @param WP_Error $error |null
	 */
	public function process_payment_error( $error ) {
		if ( $this->payment_result && $error ) {
			// add the error to the payment result
			$this->payment_result->set_payment_details( array(
				'stripeErrorMessage' => $error->get_error_message()
			) );
		}
	}

	/**
	 * @return \PaymentPlugins\Blocks\Stripe\Payments\AbstractStripePayment[]
	 */
	public function get_payment_methods() {
		return $this->payment_methods;
	}

}