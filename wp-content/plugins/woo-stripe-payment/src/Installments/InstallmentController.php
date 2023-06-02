<?php

namespace PaymentPlugins\Stripe\Installments;

use PaymentPlugins\Stripe\Installments\Filters\CurrencyFilter;
use PaymentPlugins\Stripe\Installments\Filters\OrderTotalFilter;
use PaymentPlugins\Stripe\Installments\Filters\PreOrdersFilter;
use PaymentPlugins\Stripe\Installments\Filters\SubscriptionFilter;

class InstallmentController {

	private $filters = [];

	/**
	 * @var \WC_Stripe_Advanced_Settings
	 */
	private $advanced_settings;

	/**
	 * @var \WC_Stripe_Account_Settings
	 */
	private $account_settings;

	/**
	 * @var \PaymentPlugins\Stripe\Installments\InstallmentFormatter
	 */
	private $formatter;

	private $active;

	public static function instance() {
		static $instance;
		if ( $instance === null ) {
			$instance = new self( stripe_wc()->advanced_settings, stripe_wc()->account_settings, new InstallmentFormatter() );
		}

		return $instance;
	}

	/**
	 * @param $advanced_settings
	 * @param $account_settings
	 * @param $formatter
	 */
	public function __construct( $advanced_settings, $account_settings, $formatter ) {
		$this->advanced_settings = $advanced_settings;
		$this->account_settings  = $account_settings;
		$this->formatter         = $formatter;
		$this->active            = wc_string_to_bool( $this->advanced_settings->get_option( 'installments' ) );
		$this->initialize();
	}

	private function initialize() {
		if ( $this->active ) {
			add_action( 'wc_stripe_save_order_meta', [ $this, 'add_order_meta' ], 10, 3 );
			add_filter( 'woocommerce_get_order_item_totals', [ $this, 'add_order_item_total' ], 10, 2 );
			add_filter( 'wc_stripe_can_update_payment_intent', [ $this, 'can_update_payment_intent' ], 10, 2 );
			add_filter( 'wc_stripe_payment_intent_confirmation_args', [ $this, 'add_confirmation_args' ], 10, 2 );
		}
	}

	public function is_available( $order = null ) {
		if ( $this->active ) {
			if ( $order !== null ) {
				if ( is_int( $order ) ) {
					$order = wc_get_order( $order );
				}
				$filters = $this->order_filters_factory( $order );
			} else {
				$filters = $this->cart_filters_factory();
			}
			$is_available = true;
			foreach ( $filters as $filter ) {
				if ( ! $filter->is_available() ) {
					return false;
				}
			}

			return apply_filters( 'wc_stripe_installments_is_available', $is_available );
		}

		return false;
	}

	/**
	 * @return \PaymentPlugins\Stripe\Installments\Filters\CurrencyFilter[]
	 */
	private function cart_filters_factory() {
		$currency = get_woocommerce_currency();

		return [
			new CurrencyFilter( $currency, $this->account_settings->get_account_country( wc_stripe_mode() ) ),
			new OrderTotalFilter( WC()->cart ? WC()->cart->total : 0, $currency ),
			new SubscriptionFilter( WC()->cart, null ),
			new PreOrdersFilter( WC()->cart, null )
		];
	}

	private function order_filters_factory( \WC_Order $order ) {
		$currency = $order->get_currency();

		return [
			new CurrencyFilter( $currency, $this->account_settings->get_account_country( wc_stripe_order_mode( $order ) ) ),
			new OrderTotalFilter( $order->get_total(), $currency ),
			new SubscriptionFilter( null, $order ),
			new PreOrdersFilter( null, $order )
		];
	}

	/**
	 * @param \WC_Order                  $order
	 * @param \WC_Payment_Gateway_Stripe $payment_method
	 * @param \Stripe\Charge             $charge
	 */
	public function add_order_meta( $order, $payment_method, $charge ) {
		if ( ! empty( $charge->payment_method_details->card->installments->plan ) ) {
			$plan = $charge->payment_method_details->card->installments->plan;
			$order->update_meta_data( \WC_Stripe_Constants::INSTALLMENT_PLAN, $this->formatter->format_plan_id( $plan ) );
		}
	}

	/**
	 * @param [] $rows
	 * @param \WC_Order $order
	 */
	public function add_order_item_total( $rows, $order ) {
		$plan = $order->get_meta( \WC_Stripe_Constants::INSTALLMENT_PLAN );
		if ( $plan ) {
			$amount                                         = wc_stripe_add_number_precision( $order->get_total(), $order->get_currency() );
			$rows[ \WC_Stripe_Constants::INSTALLMENT_PLAN ] = [
				'label' => __( 'Installments:', 'woo-stripe-payment' ),
				'value' => $this->formatter->format_plan( $this->formatter->parse_plan_from_id( $plan, true ), $amount, $order->get_currency() )
			];
		}

		return $rows;
	}

	/**
	 * @param [] $intent
	 * @param \WC_Order $order
	 */
	public function can_update_payment_intent( $can_update, $intent ) {
		if ( ! $can_update && ! empty( $intent['payment_method_options']['card']['installments']['enabled'] ) ) {
			$can_update = true;
		}

		return $can_update;
	}

	/**
	 * @param                      $args
	 * @param Stripe\PaymentIntent $intent
	 */
	public function add_confirmation_args( $args, $intent ) {
		if ( ! empty( $intent->payment_method_options->card->installments->available_plans ) ) {
			$plan_id = isset( $_POST[ \WC_Stripe_Constants::INSTALLMENT_PLAN ] ) ? wc_clean( $_POST[ \WC_Stripe_Constants::INSTALLMENT_PLAN ] ) : null;
			if ( $this->formatter->is_valid_plan( $plan_id ) ) {
				$args['payment_method_options'] = [ 'card' => [ 'installments' => [ 'plan' => $this->formatter->parse_plan_from_id( $plan_id ) ] ] ];
			}
		}

		return $args;
	}

	public static function get_supported_countries() {
		$filter = new CurrencyFilter( 0, null );

		return $filter->get_supported_countries();
	}

	public static function get_supported_currencies() {
		$filter = new CurrencyFilter( 0, null );

		return $filter->get_supported_currencies();
	}

}