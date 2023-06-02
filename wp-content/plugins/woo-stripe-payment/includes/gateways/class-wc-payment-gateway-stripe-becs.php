<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class WC_Payment_Gateway_Stripe_Becs
 *
 * @since   3.1.7
 * @package Stripe/Gateways
 * @author  PaymentPlugins
 */
class WC_Payment_Gateway_Stripe_BECS extends WC_Payment_Gateway_Stripe_Local_Payment {

	use WC_Stripe_Local_Payment_Intent_Trait;

	protected $payment_method_type = 'au_becs_debit';

	public $synchronous = false;

	public $token_type = 'Stripe_Becs';

	public function __construct() {
		$this->local_payment_type = 'au_becs_debit';
		$this->currencies         = array( 'AUD' );
		$this->countries          = array( 'AU' );
		$this->id                 = 'stripe_becs';
		$this->tab_title          = __( 'BECS', 'woo-stripe-payment' );
		$this->method_title       = __( 'BECS', 'woo-stripe-payment' );
		$this->method_description = __( 'BECS direct debit gateway that integrates with your Stripe account.', 'woo-stripe-payment' );
		$this->icon               = '';
		parent::__construct();

		$this->local_payment_description = sprintf(
			__(
				'By providing your bank account details and confirming this payment, you agree to this 
		Direct Debit Request and the %1$sDirect Debit Request service agreement%2$s, and authorise Stripe Payments Australia Pty Ltd ACN 160 180 343 Direct 
		Debit User ID number 507156 ("Stripe") to debit your account through the Bulk Electronic Clearing System (BECS) on behalf of %3$s 
		(the "Merchant") for any amounts separately communicated to you by the Merchant. You certify that you are either an account holder or an 
		authorised signatory on the account listed above.',
				'woo-stripe-payment'
			)
			, '<a href="https://stripe.com/au-becs-dd-service-agreement/legal" target="_blank">', '</a>', $this->get_option( 'company_name' ) );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'subscriptions';
		$this->supports[] = 'subscription_cancellation';
		$this->supports[] = 'multiple_subscriptions';
		$this->supports[] = 'subscription_reactivation';
		$this->supports[] = 'subscription_suspension';
		$this->supports[] = 'subscription_date_changes';
		$this->supports[] = 'subscription_payment_method_change_admin';
		$this->supports[] = 'subscription_amount_changes';
		$this->supports[] = 'subscription_payment_method_change_customer';
		$this->supports[] = 'pre-orders';
	}

	public function get_local_payment_settings() {
		return array_merge( parent::get_local_payment_settings(), array(
			'company_name'  => array(
				'title'       => __( 'Company Name', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => get_bloginfo( 'name' ),
				'description' => __( 'The company name that appears in the BECS mandate text.', 'woo-stripe-payment' )
			),
			'method_format' => array(
				'title'       => __( 'Payment Method Display', 'woo-stripe-payment' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
				'default'     => 'type_ending_last4',
				'desc_tip'    => true,
				'description' => __( 'This option allows you to customize how the payment method will display for your customers on orders, subscriptions, etc.' ),
			),
		) );
	}

	public function get_new_method_label() {
		return __( 'New Account', 'woo-stripe-payment' );
	}

	public function get_saved_methods_label() {
		return __( 'Saved Accounts', 'woo-stripe-payment' );
	}

}
