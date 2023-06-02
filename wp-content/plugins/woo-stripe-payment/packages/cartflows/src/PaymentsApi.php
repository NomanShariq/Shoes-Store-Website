<?php


namespace PaymentPlugins\CartFlows\Stripe;


class PaymentsApi {

	public function __construct() {
		add_filter( 'cartflows_offer_supported_payment_gateways', array( $this, 'add_payment_gateways' ) );
		add_filter( 'wc_stripe_force_save_payment_method', array( $this, 'maybe_force_save_payment_method' ), 10, 3 );
		add_filter( 'cartflows_offer_js_localize', array( $this, 'enqueue_scripts' ) );
	}

	public function add_payment_gateways( $supported_gateways ) {
		$ids = array( 'stripe_cc', 'stripe_googlepay', 'stripe_applepay', 'stripe_payment_request' );
		foreach ( $ids as $id ) {
			$supported_gateways[ $id ] = array(
				'path'  => dirname( __FILE__ ) . '/PaymentGateways/BasePaymentGateway.php',
				'class' => '\PaymentPlugins\CartFlows\Stripe\PaymentGateways\BasePaymentGateway'
			);
		}

		return $supported_gateways;
	}

	/**
	 * @param $bool
	 * @param $order
	 * @param $payment_method
	 *
	 * @return bool
	 */
	public function maybe_force_save_payment_method( bool $bool, \WC_Order $order, \WC_Payment_Gateway_Stripe $payment_method ) {
		// validate that next step is an offer
		$checkout_id = wcf()->utils->get_checkout_id_from_post_data();
		$flow_id     = wcf()->utils->get_flow_id_from_post_data();
		if ( Main::cartflows_pro_enabled() && $checkout_id && $flow_id ) {
			$wcf_step_obj      = wcf_pro_get_step( $checkout_id );
			$next_step_id      = $wcf_step_obj->get_next_step_id();
			$wcf_next_step_obj = wcf_pro_get_step( $next_step_id );
			// todo eventually remove check for WC_Stripe_Payment_Intent so sources can be supported.
			if ( $next_step_id && $wcf_next_step_obj->is_offer_page() && ! $payment_method->use_saved_source() && $payment_method->payment_object instanceof \WC_Stripe_Payment_Intent ) {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * @param array $localize
	 */
	public function enqueue_scripts( $localize ) {
		if ( in_array( $localize['payment_method'], $this->get_payment_method_ids() ) ) {
			$localize['stripeData'] = array(
				'key'       => wc_stripe_get_publishable_key(),
				'accountId' => wc_stripe_get_account_id(),
				'version'   => stripe_wc()->version(),
				'mode'      => wc_stripe_mode(),
				'msg'       => __( 'Processing Order...', 'cartflows-pro' ),
				'timeout'   => 3000
			);
			// enqueue cartflows script
			$assets_url = plugin_dir_url( __DIR__ ) . 'build/';
			$assets     = require_once dirname( __DIR__ ) . '/build/wc-stripe-cartflows.asset.php';
			wp_enqueue_script( 'wc-stripe-cartflows', $assets_url . 'wc-stripe-cartflows.js', $assets['dependencies'], stripe_wc()->version(), true );
		}

		return $localize;
	}

	private function get_payment_method_ids() {
		/**
		 * @since 3.3.4
		 */
		return apply_filters( 'wc_stripe_cartflows_get_payment_method_ids', array(
			'stripe_cc',
			'stripe_applepay',
			'stripe_googlepay',
			'stripe_payment_request'
		) );
	}

}