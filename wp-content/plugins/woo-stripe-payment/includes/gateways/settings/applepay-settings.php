<?php
return array(
	'desc'                 => array(
		'type'        => 'description',
		'description' => sprintf( '<div class="wc-stripe-register-domain"><button class="button button-secondary api-register-domain">%s</button></div><p>%s</p>', __( 'Register Domain', 'woo-stripe-payment' ), sprintf( __( 'This plugin attemps to add the domain association file to your server automatically when you click the Register Domain button. If that fails due to file permssions, you must add the <strong>%1$s.well-known/apple-developer-merchantid-domain-association%2$s</strong> file to your domain  and register your domain within the Stripe Dashboard.', 'woo-stripe-payment' ), '<a href="https://stripe.com/files/apple-pay/apple-developer-merchantid-domain-association">', '</a>' ) ) .
		                 '<p>' .
		                 __( 'In order for Apple Pay to display, you must test with an iOS device and have a payment method saved in the Apple Wallet.', 'woo-stripe-payment' ) .
		                 '</p>',
	),
	'enabled'              => array(
		'title'       => __( 'Enabled', 'woo-stripe-payment' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Apple Pay payments through Stripe.', 'woo-stripe-payment' ),
	),
	'general_settings'     => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'woo-stripe-payment' ),
	),
	'title_text'           => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'woo-stripe-payment' ),
		'default'     => __( 'Apple Pay', 'woo-stripe-payment' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the Apple Pay gateway' ),
	),
	'description'          => array(
		'title'       => __( 'Description', 'woo-stripe-payment' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'woo-stripe-payment' ),
		'desc_tip'    => true,
	),
	'method_format'        => array(
		'title'       => __( 'Credit Card Display', 'woo-stripe-payment' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_payment_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'charge_type'          => array(
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
	'payment_sections'     => array(
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
	'order_status'         => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'woo-stripe-payment' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'woo-stripe-payment' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'woo-stripe-payment' ),
	),
	'button_section'       => array(
		'type'  => 'title',
		'title' => __( 'Button Settings', 'woo-stripe-payment' ),
	),
	'button_style'         => array(
		'type'        => 'select',
		'title'       => __( 'Button Design', 'woo-stripe-payment' ),
		'class'       => 'wc-enhanced-select',
		'default'     => 'apple-pay-button-black',
		'options'     => array(
			'apple-pay-button-black'           => __( 'Black Button', 'woo-stripe-payment' ),
			'apple-pay-button-white-with-line' => __( 'White With Black Line', 'woo-stripe-payment' ),
			'apple-pay-button-white'           => __( 'White Button', 'woo-stripe-payment' ),
		),
		'description' => __( 'This is the style for all Apple Pay buttons presented on your store.', 'woo-stripe-payment' ),
	),
	'button_type_checkout' => array(
		'title'   => __( 'Checkout button type', 'woo-stripe-payment' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-stripe-payment' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-stripe-payment' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-stripe-payment' )
		),
		'default' => 'plain',
	),
	'button_type_cart'     => array(
		'title'   => __( 'Cart button type', 'woo-stripe-payment' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-stripe-payment' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-stripe-payment' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-stripe-payment' )
		),
		'default' => 'plain',
	),
	'button_type_product'  => array(
		'title'   => __( 'Product button type', 'woo-stripe-payment' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'woo-stripe-payment' ),
			'buy'       => __( 'Buy with Apple Pay', 'woo-stripe-payment' ),
			'check-out' => __( 'Checkout with Apple Pay', 'woo-stripe-payment' )
		),
		'default' => 'buy',
	),
);
