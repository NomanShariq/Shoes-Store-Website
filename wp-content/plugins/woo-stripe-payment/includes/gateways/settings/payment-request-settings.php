<?php
return array(
	'desc'             => array(
		'type'        => 'description',
		'description' => __(
			'The PaymentRequest gateway uses your customer\'s browser to render payment options like Google Pay and Microsoft Pay. You can either use the Google Pay gateway for example, or this gateway.
						The difference is this gateway uses Stripe\'s PaymentRequest Button rather than render a Google Pay specific button.',
			'woo-stripe-payment'
		),
	),
	'enabled'          => array(
		'title'       => __( 'Enabled', 'woo-stripe-payment' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Apple Pay payments through Stripe.', 'woo-stripe-payment' ),
	),
	'general_settings' => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'woo-stripe-payment' ),
	),
	'title_text'       => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'woo-stripe-payment' ),
		'default'     => __( 'Browser Payments', 'woo-stripe-payment' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the credit card gateway' ),
	),
	'description'      => array(
		'title'       => __( 'Description', 'woo-stripe-payment' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'method_format'    => array(
		'title'       => __( 'Credit Card Display', 'woo-stripe-payment' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'charge_type'      => array(
		'type'        => 'select',
		'title'       => __( 'Charge Type', 'woo-stripe-payment' ),
		'default'     => 'capture',
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'capture'   => __( 'Capture', 'woo-stripe-payment' ),
			'authorize' => __( 'Authorize', 'woo-stripe-payment' ),
		),
		'desc_tip'    => true,
		'description' => __( 'This option determines whether the customer\'s funds are captured immediately or authorized and can be captured at a later date.', 'woo-stripe-payment' ),
	),
	'payment_sections' => array(
		'type'        => 'multiselect',
		'title'       => __( 'Payment Sections', 'woo-stripe-payment' ),
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'product'         => __( 'Product Page', 'woo-stripe-payment' ),
			'cart'            => __( 'Cart Page', 'woo-stripe-payment' ),
			'mini_cart'       => __( 'Mini Cart', 'woo-stripe-payment' ),
			'checkout_banner' => __( 'Top of Checkout', 'woo-stripe-payment' ),
		),
		'default'     => array( 'product', 'cart' ),
		'description' => $this->get_payment_section_description(),
	),
	'order_status'     => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-stripe-payment' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-stripe-payment' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'woo-stripe-payment' ),
	),
	'button_section'   => array(
		'type'  => 'title',
		'title' => __( 'Button Settings', 'woo-stripe-payment' ),
	),
	'button_type'      => array(
		'type'        => 'select',
		'title'       => __( 'Type', 'woo-stripe-payment' ),
		'options'     => array(
			'default' => __( 'default', 'woo-stripe-payment' ),
			// 'donate' => __ ( 'donate', 'woo-stripe-payment' ),
			'buy'     => __( 'buy', 'woo-stripe-payment' ),
		),
		'default'     => 'buy',
		'desc_tip'    => true,
		'description' => __( 'This defines the type of button that will display.', 'woo-stripe-payment' ),
	),
	'button_theme'     => array(
		'type'        => 'select',
		'title'       => __( 'Theme', 'woo-stripe-payment' ),
		'options'     => array(
			'dark'          => __( 'dark', 'woo-stripe-payment' ),
			'light'         => __( 'light', 'woo-stripe-payment' ),
			'light-outline' => __( 'light-outline', 'woo-stripe-payment' ),
		),
		'default'     => 'dark',
		'desc_tip'    => true,
		'description' => __( 'This defines the color scheme for the button.', 'woo-stripe-payment' ),
	),
	'button_height'    => array(
		'type'        => 'text',
		'title'       => __( 'Height', 'woo-stripe-payment' ),
		'default'     => '40',
		'desc_tip'    => true,
		'description' => __( 'The height of the button. Max height is 64', 'woo-stripe-payment' ),
	),
);
