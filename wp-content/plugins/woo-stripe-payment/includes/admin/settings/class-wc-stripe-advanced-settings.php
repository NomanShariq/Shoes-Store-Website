<?php

defined( 'ABSPATH' ) || exit();

/**
 * @since 3.3.13
 */
class WC_Stripe_Advanced_Settings extends WC_Stripe_Settings_API {

	public function __construct() {
		$this->id        = 'stripe_advanced';
		$this->tab_title = __( 'Advanced Settings', 'woo-stripe-payment' );
		parent::__construct();
	}

	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_stripe_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'                  => array(
				'type'  => 'title',
				'title' => __( 'Advanced Settings', 'woo-stripe-payment' ),
			),
			'settings_description'   => array(
				'type'        => 'description',
				'description' => __( 'This section provides advanced settings that allow you to configure functionality that fits your business process.', 'woo-stripe-payment' )
			),
			'locale'                 => array(
				'title'       => __( 'Locale Type', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'site',
				'options'     => array(
					'auto' => __( 'Auto', 'woo-stripe-payment' ),
					'site' => __( 'Site Locale', 'woo-stripe-payment' )
				),
				'desc_tip'    => true,
				'description' => __( 'If set to "auto" Stripe will determine the locale to use based on the customer\'s browser/location settings. Site locale uses the Wordpress locale setting.',
					'woo-stripe-payment' )
			),
			'installments'           => array(
				'title'       => __( 'Installments', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => false,
				'description' => sprintf( __( 'If enabled, installments will be available for the credit card gateway. %1$s', 'woo-stripe-payment' ), $this->get_supported_countries_description() )
			),
			'statement_descriptor'   => array(
				'title'             => __( 'Statement Descriptor', 'woo-stripe-payment' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'Maximum of 22 characters. This value represents the full statement descriptor that your customer will see. If left blank, Stripe will use your account descriptor.',
					'woo-stripe-payment' ),
				'sanitize_callback' => function ( $value ) {
					if ( ! empty( $value ) && strlen( $value ) > 21 ) {
						$value = substr( $value, 0, 22 );
					}

					return WC_Stripe_Utils::sanitize_statement_descriptor( $value );
				}
			),
			'stripe_fee'             => array(
				'title'       => __( 'Display Stripe Fee', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, the Stripe fee will be displayed on the Order Details page. The fee and net payout are displayed in your Stripe account currency.',
					'woo-stripe-payment' )
			),
			'stripe_fee_currency'    => array(
				'title'             => __( 'Fee Display Currency', 'woo-stripe-payment' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'description'       => __( 'If enabled, the Stripe fee and payout will be displayed in the currency of the order. Stripe by default provides the fee and payout in the Stripe account\'s currency.',
					'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'stripe_fee' => true
					)
				)
			),
			'capture_status'         => array(
				'title'       => __( 'Capture Status', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'completed',
				'options'     => array(
					'completed'  => __( 'Completed', 'woo-stripe-payment' ),
					'processing' => __( 'Processing', 'woo-stripe-payment' ),
				),
				'desc_tip'    => true,
				'description' => __( 'For orders that are authorized, when the order is set to this status, it will trigger a capture.', 'woo-stripe-payment' ),
			),
			'refund_cancel'          => array(
				'title'       => __( 'Refund On Cancel', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, the plugin will process a payment cancellation or refund within Stripe when the order\'s status is set to cancelled.', 'woo-stripe-payment' )
			),
			'link_title'             => array(
				'type'  => 'title',
				'title' => __( 'Link Settings', 'woo-stripe-payment' ),
			),
			'link_enabled'           => array(
				'title'       => __( 'Faster Checkout With Link', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'description' => __( 'With Link enabled, Stripe will use your customer\'s email address to determine if they have used Stripe in the past. If yes, their payment info, billing and shipping information can be used to 
				auto-populate the checkout page resulting in higher conversion rates and less customer friction. If enabled, the Stripe payment form will be used because it\'s the only card form compatible with Link.', 'woo-stripe-payment' )
			),
			'link_email'             => array(
				'title'             => __( 'Move email field to top of page', 'woo-stripe-payment' ),
				'type'              => 'checkbox',
				'default'           => 'yes',
				'value'             => 'yes',
				'description'       => __( 'If enabled, the email field will be placed at the top of the checkout page. Link uses the email address so it\'s best to prioritize it.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'link_enabled' => true
					)
				)
			),
			'link_icon'              => array(
				'title'             => __( 'Show Link Icon', 'woo-stripe-payment' ),
				'type'              => 'select',
				'default'           => 'dark',
				'options'           => array(
					'light' => __( 'Light', 'woo-stripe-payment' ),
					'dark'  => __( 'Dark', 'woo-stripe-payment' ),
					'no'    => __( 'No Icon', 'woo-stripe-payment' ),
				),
				'description'       => __( 'Render the Link icon in the email field. This indicates to customers that Link is enabled.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'link_enabled' => true
					)
				)
			),
			'link_autoload'          => array(
				'title'             => __( 'Launch link on page load', 'woo-stripe-payment' ),
				'type'              => 'checkbox',
				'default'           => 'yes',
				'value'             => 'yes',
				'description'       => __( 'If enabled and the email address is already populated, the plugin will attempt to launch Link  on the checkout page.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'link_enabled' => true
					)
				)
			),
			'gdpr'                   => array(
				'title' => __( 'GDPR Settings', 'woo-stripe-payment' ),
				'type'  => 'title'
			),
			'customer_creation'      => array(
				'title'       => __( 'Customer Creation', 'woo-stripe-payment' ),
				'type'        => 'select',
				'default'     => 'account_creation',
				'options'     => array(
					'account_creation' => __( 'When account is created', 'woo-stripe-payment' ),
					'payment'          => __( 'When a customer ID is required', 'woo-stripe-payment' )
				),
				'description' => __( 'This option allows you to control when a Stripe customer object is created. The plugin can create a Stripe customer ID when 
				your customer creates an account with your store, or it can wait until the Stripe customer ID is required for things like payment on the checkout page.', 'woo-stripe-payment' )
			),
			'disputes'               => array(
				'title' => __( 'Dispute Settings', 'woo-stripe-payment' ),
				'type'  => 'title'
			),
			'dispute_created'        => array(
				'title'       => __( 'Dispute Created', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.created</strong> webhook event and set the order\'s status to on-hold by default.',
					'woo-stripe-payment' )
			),
			'dispute_created_status' => array(
				'title'             => __( 'Disputed Created Order Status', 'woo-stripe-payment' ),
				'type'              => 'select',
				'default'           => 'wc-on-hold',
				'options'           => wc_get_order_statuses(),
				'description'       => __( 'The status assigned to an order when a dispute is created.', 'woo-stripe-payment' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'dispute_created' => true
					)
				)
			),
			'dispute_closed'         => array(
				'title'       => __( 'Dispute Closed', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.closed</strong> webhook event and set the order\'s status back to the status before the dispute was opened.',
					'woo-stripe-payment' )
			),
			'reviews'                => array(
				'title' => __( 'Review Settings', 'woo-stripe-payment' ),
				'type'  => 'title'
			),
			'review_created'         => array(
				'title'       => __( 'Review Created', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, the plugin will listen for the <strong>review.created</strong> webhook event and set the order\'s status to on-hold by default.',
					'woo-stripe-payment' )
			),
			'review_closed'          => array(
				'title'       => __( 'Review Closed', 'woo-stripe-payment' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, the plugin will listen for the <strong>review.closed</strong> webhook event and set the order\'s status back to the status before the review was opened.',
					'woo-stripe-payment' )
			),
			'email_title'            => array(
				'type'  => 'title',
				'title' => __( 'Stripe Email Options', 'woo-stripe-payment' )
			),
			'email_enabled'          => array(
				'type'        => 'checkbox',
				'title'       => __( 'Email Receipt', 'woo-stripe-payment' ),
				'default'     => 'no',
				'description' => __( 'If enabled, an email receipt will be sent to the customer by Stripe when the order is processed.',
					'woo-stripe-payment' ),
			)
		);
	}

	public function process_admin_options() {
		parent::process_admin_options();
		if ( $this->is_active( 'link_enabled' ) ) {
			/**
			 * @var \WC_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()['stripe_cc'];
			$payment_method->update_option( 'form_type', 'payment' );
			wc_stripe_log_info( 'Stripe payment form enabled for Link integration compatibility' );
		}
	}

	public function is_fee_enabled() {
		return $this->is_active( 'stripe_fee' );
	}

	public function is_display_order_currency() {
		return $this->is_active( 'stripe_fee_currency' );
	}

	public function is_email_receipt_enabled() {
		return $this->is_active( 'email_enabled' );
	}

	public function is_refund_cancel_enabled() {
		return $this->is_active( 'refund_cancel' );
	}

	public function is_dispute_created_enabled() {
		return $this->is_active( 'dispute_created' );
	}

	public function is_dispute_closed_enabled() {
		return $this->is_active( 'dispute_closed' );
	}

	public function is_review_opened_enabled() {
		return $this->is_active( 'review_created' );
	}

	public function is_review_closed_enabled() {
		return $this->is_active( 'review_closed' );
	}

	public function get_supported_countries_description() {
		return sprintf( __( 'Supported Stripe account countries: %1$s. Supported currencies: %2$s', 'woo-stripe-payment' ),
			implode( ', ', \PaymentPlugins\Stripe\Installments\InstallmentController::get_supported_countries() ),
			implode( ', ', \PaymentPlugins\Stripe\Installments\InstallmentController::get_supported_currencies() ) );
	}

}
