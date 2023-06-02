<?php

return array(
	'desc'              => array(
		'type'        => 'description',
		'description' => sprintf( '<p>%s %s</p>', __( 'For US customers only.', 'woo-stripe-payment' ),
			sprintf( __( 'Read through our %1$sdocumentation%2$s to configure ACH payments', 'woo-stripe-payment' ),
				'<a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config/#/stripe_ach">',
				'</a>' ) )
	),
	'enabled'           => array(
		'title'       => __( 'Enabled', 'woo-stripe-payment' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept ACH payments through Stripe.', 'woo-stripe-payment' ),
	),
	'general_settings'  => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'woo-stripe-payment' ),
	),
	'title_text'        => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'woo-stripe-payment' ),
		'default'     => __( 'ACH Payment', 'woo-stripe-payment' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the ACH gateway' ),
	),
	'description'       => array(
		'title'       => __( 'Description', 'woo-stripe-payment' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'order_button_text' => array(
		'title'       => __( 'Order Button Text', 'woo-stripe-payment' ),
		'type'        => 'text',
		'default'     => __( 'Bank Payment', 'woo-stripe-payment' ),
		'description' => __( 'The text on the Place Order button that displays when the gateway is selected on the checkout page.', 'woo-stripe-payment' ),
		'desc_tip'    => true
	),
	'business_name'     => array(
		'type'        => 'text',
		'title'       => __( 'Business Name', 'woo-stripe-payment' ),
		'default'     => get_bloginfo( 'name' ),
		'description' => __( 'The name that appears in the ACH mandate.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'method_format'     => array(
		'title'       => __( 'ACH Display', 'woo-stripe-payment' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'order_status'      => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-stripe-payment' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-stripe-payment' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.',
			'woo-stripe-payment' ),
	),
	'fee'               => array(
		'title'       => __( 'ACH Fee', 'woo-stripe-payment' ),
		'type'        => 'ach_fee',
		'class'       => '',
		'value'       => '',
		'default'     => array(
			'type'    => 'none',
			'taxable' => 'no',
			'value'   => '0',
		),
		'options'     => array(
			'none'    => __( 'None', 'woo-stripe-payment' ),
			'amount'  => __( 'Amount', 'woo-stripe-payment' ),
			'percent' => __( 'Percentage', 'woo-stripe-payment' ),
		),
		'desc_tip'    => true,
		'description' => __( 'You can assign a fee to the order for ACH payments. Amount is a static amount and percentage is a percentage of the cart amount.', 'woo-stripe-payment' ),
	),
);
