<?php


namespace PaymentPlugins\Blocks\Stripe\Payments\Gateways;


use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use PaymentPlugins\Blocks\Stripe\Assets\Api as AssetsApi;
use PaymentPlugins\Blocks\Stripe\Payments\AbstractStripeLocalPayment;

class WeChatPayment extends AbstractStripeLocalPayment {

	protected $name = 'stripe_wechat';

	public function __construct( AssetsApi $assets_api ) {
		parent::__construct( $assets_api );
	}

	public function init() {
		parent::init();
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'update_redirect_url' ), 1000, 2 );
	}

	public function get_payment_method_script_handles() {
		wp_enqueue_script( 'wc-stripe-qrcode', stripe_wc()->scripts()->assets_url( 'js/frontend/qrcode.js' ), array() );

		return parent::get_payment_method_script_handles();
	}

	public function get_payment_method_data() {
		return array_merge( parent::get_payment_method_data(), array(
			'qrSize' => $this->payment_method->get_option( 'qr_size' )
		) );
	}

	/**
	 * Update the redirect url for live mode so that the WC_Stripe_Redirect_Handler can process
	 * the live payment.
	 *
	 * @param PaymentContext $context
	 * @param PaymentResult $result
	 *
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function update_redirect_url( PaymentContext $context, PaymentResult $result ) {
		if ( $context->order->get_payment_method() === $this->name && wc_stripe_mode() === 'live' ) {
			/**
			 * @var \WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = $context->get_payment_method_instance();
			$source_id      = $context->order->get_meta( \WC_Stripe_Constants::SOURCE_ID );
			$source         = \WC_Stripe_Gateway::load( wc_stripe_order_mode( $context->order ) )->sources->retrieve( $source_id );
			$redirect       = add_query_arg( array(
				'source'        => $source_id,
				'client_secret' => $source->client_secret
			), $payment_method->get_local_payment_return_url( $context->order ) );
			$result->set_redirect_url( $redirect );
		}
	}
}