<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @param string $template_name
 * @param array  $args
 *
 * @since   3.0.0
 * @package Stripe/Functions
 *          Wrapper for wc_get_template that returns Stripe specfic templates.
 */
function wc_stripe_get_template( $template_name, $args = array() ) {
	wc_get_template( $template_name, $args, stripe_wc()->template_path(), stripe_wc()->default_template_path() );
}

/**
 *
 *
 * Wrapper for wc_get_template_html that returns Stripe specififc templates in an html string.
 *
 * @param string $template_name
 * @param array  $args
 *
 * @since   3.0.0
 * @return string
 * @package Stripe/Functions
 */
function wc_stripe_get_template_html( $template_name, $args = array() ) {
	return wc_get_template_html( $template_name, $args, stripe_wc()->template_path(), stripe_wc()->default_template_path() );
}

/**
 * Return true if WCS is active.
 *
 * @return boolean
 * @package Stripe/Functions
 */
function wcs_stripe_active() {
	return function_exists( 'wcs_is_subscription' );
}

/**
 *
 * @param WC_Payment_Gateway_Stripe $gateway
 *
 * @package Stripe/Functions
 */
function wc_stripe_token_field( $gateway ) {
	wc_stripe_hidden_field( $gateway->token_key, 'wc-stripe-token-field' );
}

/**
 *
 * @param WC_Payment_Gateway_Stripe $gateway
 *
 * @package Stripe/Functions
 */
function wc_stripe_payment_intent_field( $gateway ) {
	wc_stripe_hidden_field( $gateway->payment_intent_key, 'wc-stripe-payment-intent-field' );
}

/**
 *
 * @param string $id
 * @param string $class
 * @param string $value
 *
 * @package Stripe/Functions
 */
function wc_stripe_hidden_field( $id, $class = '', $value = '' ) {
	printf( '<input type="hidden" class="%1$s" id="%2$s" name="%2$s" value="%3$s"/>', $class, esc_attr( $id ), esc_attr( $value ) );
}

/**
 * Return the mode for the plugin.
 *
 * @return string
 * @package Stripe/Functions
 */
function wc_stripe_mode() {
	return apply_filters( 'wc_stripe_mode', stripe_wc()->api_settings->get_option( 'mode' ) );
}

function wc_stripe_test_mode() {
	return wc_stripe_mode() === 'test';
}

/**
 * Return the secret key for the provided mode.
 * If no mode given, the key for the active mode is returned.
 *
 * @param string $mode
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_get_secret_key( $mode = '' ) {
	$mode = empty( $mode ) ? wc_stripe_mode() : $mode;

	return apply_filters( 'wc_stripe_get_secret_key', stripe_wc()->api_settings->get_option( "secret_key_{$mode}" ), $mode );
}

/**
 * Return the publishable key for the provided mode.
 * If no mode given, the key for the active mode is returned.
 *
 * @param string $mode
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_get_publishable_key( $mode = '' ) {
	$mode = empty( $mode ) ? wc_stripe_mode() : $mode;

	return apply_filters( 'wc_stripe_get_publishable_key', stripe_wc()->api_settings->get_option( "publishable_key_{$mode}" ), $mode );
}

/**
 * Return the merchant's Stripe account.
 *
 * @since   3.1.4
 * @return string
 * @package Stripe/Functions
 */
function wc_stripe_get_account_id() {
	return apply_filters( 'wc_stripe_get_account_id', stripe_wc()->api_settings->get_account_id() );
}

/**
 * Return the stripe customer ID
 *
 * @param int    $user_id
 * @param string $mode
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_get_customer_id( $user_id = '', $mode = '' ) {
	$mode = empty( $mode ) ? wc_stripe_mode() : $mode;
	if ( $user_id === 0 ) {
		return '';
	}
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	/**
	 * @param string
	 * @param int    $user_id
	 * @param string $mode
	 *
	 * @since 3.2.12
	 */
	return apply_filters( 'wc_stripe_get_customer_id', get_user_option( "wc_stripe_customer_{$mode}", $user_id ), $user_id, $mode );
}

/**
 *
 * @param string $customer_id
 * @param int    $user_id
 * @param string $mode
 *
 * @package Stripe/Functions
 */
function wc_stripe_save_customer( $customer_id, $user_id, $mode = '' ) {
	$mode = empty( $mode ) ? wc_stripe_mode() : $mode;
	$key  = "wc_stripe_customer_{$mode}";
	update_user_option( $user_id, $key, apply_filters( 'wc_stripe_save_customer', $customer_id, $user_id, $mode ) );
}

/**
 * @param int    $user_id
 * @param string $mode
 * @param bool   $global
 *
 * @since 3.2.13
 */
function wc_stripe_delete_customer( $user_id, $mode = '', $global = false ) {
	$mode = empty( $mode ) ? wc_stripe_mode() : $mode;
	delete_user_option( $user_id, "wc_stripe_customer_{$mode}", $global );
}

/**
 *
 * @param int              $token_id
 * @param WC_Payment_Token $token
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_woocommerce_payment_token_deleted( $token_id, $token ) {
	if ( ! did_action( 'woocommerce_payment_gateways' ) ) {
		WC_Payment_Gateways::instance();
	}
	/**
	 * @since 3.3.22 - only trigger this action if the delete request is coming from the my account page.
	 */
	if ( is_account_page() ) {
		do_action( 'wc_stripe_payment_token_deleted_' . $token->get_gateway_id(), $token_id, $token );
	}
}

/**
 * Log the provided message in the WC logs directory.
 *
 * @param int    $level
 * @param string $message
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_log( $level, $message ) {
	if ( stripe_wc()->api_settings->is_active( 'debug_log' ) ) {
		$log = wc_get_logger();
		$log->log( $level, $message, array( 'source' => 'wc-stripe' ) );
	}
}

/**
 *
 * @param string $message
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_log_error( $message ) {
	wc_stripe_log( WC_Log_Levels::ERROR, $message );
}

/**
 *
 * @param string $message
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_log_info( $message ) {
	wc_stripe_log( WC_Log_Levels::INFO, $message );
}

/**
 * Return the mode that the order was created in.
 * Values can be <strong>live</strong> or <strong>test</strong>
 *
 * @param WC_Order|int $order
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_order_mode( $order ) {
	if ( ! is_object( $order ) ) {
		$order = wc_get_order( $order );
	}

	return $order->get_meta( WC_Stripe_Constants::MODE, true );
}

/**
 *
 * @param array $gateways
 *
 * @since   3.0.0
 * @package Stripe\Functions
 */
function wc_stripe_payment_gateways( $gateways ) {
	return array_merge( $gateways, stripe_wc()->payment_gateways() );
}

/**
 * Cancel the Stripe charge
 *
 * @param int      $order_id
 * @param WC_Order $order
 *
 * @package Stripe/Functions
 */
function wc_stripe_order_cancelled( $order_id, $order ) {
	if ( stripe_wc()->advanced_settings->is_refund_cancel_enabled() ) {
		$gateways = WC()->payment_gateways()->payment_gateways();
		/**
		 *
		 * @var WC_Payment_Gateway_Stripe $gateway
		 */
		$gateway = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;

		if ( $gateway && $gateway instanceof WC_Payment_Gateway_Stripe ) {
			$gateway->void_charge( $order );
		}
	}
}

/**
 *
 * @param int      $order_id
 * @param WC_Order $order
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_order_status_completed( $order_id, $order ) {
	$gateways = WC()->payment_gateways()->payment_gateways();
	/**
	 *
	 * @var WC_Payment_Gateway_Stripe $gateway
	 */
	$gateway = isset( $gateways[ $order->get_payment_method() ] ) ? $gateways[ $order->get_payment_method() ] : null;
	// @since 3.0.3 check added to ensure this is a Stripe gateway.
	if ( $gateway && $gateway instanceof WC_Payment_Gateway_Stripe && ! $gateway->processing_payment ) {
		if ( stripe_wc()->advanced_settings->get_option( 'capture_status', 'completed' ) === $order->get_status() ) {
			$gateway->capture_charge( $order->get_total(), $order );
		}
	}
}

/**
 *
 * @param   [] $address
 *
 * @since   3.0.0
 * @throws Exception
 * @package Stripe/Functions
 */
function wc_stripe_update_customer_location( $address ) {
	// address validation for countries other than US is problematic when using responses from payment sources like
	// Apple Pay.
	if ( $address['postcode'] && $address['country'] === 'US' && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
		throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
	} elseif ( $address['postcode'] ) {
		$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
	}

	if ( $address['country'] ) {
		WC()->customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
		WC()->customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
		// set the customer's address if it's in the $address array
		if ( ! empty( $address['address_1'] ) ) {
			WC()->customer->set_shipping_address_1( wc_clean( $address['address_1'] ) );
		}
		if ( ! empty( $address['address_2'] ) ) {
			WC()->customer->set_shipping_address_2( wc_clean( $address['address_2'] ) );
		}
		if ( ! empty( $address['first_name'] ) ) {
			WC()->customer->set_shipping_first_name( $address['first_name'] );
		}
		if ( ! empty( $address['last_name'] ) ) {
			WC()->customer->set_shipping_last_name( $address['last_name'] );
		}
	} else {
		WC()->customer->set_billing_address_to_base();
		WC()->customer->set_shipping_address_to_base();
	}

	WC()->customer->set_calculated_shipping( true );
	WC()->customer->save();

	do_action( 'woocommerce_calculated_shipping' );
}

/**
 *
 * @param   [] $methods
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_update_shipping_methods( $methods ) {
	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );

	foreach ( $methods as $i => $method ) {
		$chosen_shipping_methods[ $i ] = $method;
	}

	WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
}

/**
 * Return true if there are shipping packages that contain rates.
 *
 * @param array $packages
 *
 * @since   3.0.0
 * @return boolean
 * @package Stripe/Functions
 */
function wc_stripe_shipping_address_serviceable( $packages = array() ) {
	if ( $packages ) {
		foreach ( $packages as $package ) {
			if ( count( $package['rates'] ) > 0 ) {
				return true;
			}
		}
	}

	return false;
}

/**
 *
 * @param string   $page
 * @param WC_Order $order
 *
 * @since   3.0.0
 * @deprecated
 * @package Stripe/Functions
 */
function wc_stripe_get_display_items( $page = 'cart', $order = null ) {
	wc_deprecated_function( 'wc_stripe_get_display_items', '3.1.0', 'WC_Payment_Gateway_Stripe::get_display_items()' );

	return array();
}

/**
 *
 * @param WC_Order $order
 * @param array    $packages
 *
 * @since   3.0.0
 * @return mixed
 * @deprecated
 * @package Stripe/Functions
 */
function wc_stripe_get_shipping_options( $order = null, $packages = array() ) {
	$methods                 = array();
	$incl_tax                = wc_stripe_display_prices_including_tax();
	$ids                     = array();
	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
	$packages                = empty( $packages ) ? WC()->shipping()->get_packages() : $packages;
	foreach ( $packages as $i => $package ) {
		foreach ( $package['rates'] as $rate ) {
			/**
			 *
			 * @var WC_Shipping_Rate $rate
			 */
			$method = array(
				'id'     => sprintf( '%s:%s', $i, $rate->id ),
				'label'  => sprintf( '%s', esc_attr( $rate->get_label() ) ),
				'detail' => '',
				'amount' => wc_stripe_add_number_precision( $incl_tax ? $rate->cost + $rate->get_shipping_tax() : $rate->cost ),
			);
			if ( $incl_tax ) {
				if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
					$method['detail'] = WC()->countries->inc_tax_or_vat();
				}
			} else {
				if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
					$method['detail'] = WC()->countries->ex_tax_or_vat();
				}
			}
			$methods[] = $method;
			if ( isset( $chosen_shipping_methods[ $i ] ) && $chosen_shipping_methods[ $i ] === $rate->id ) {
				$ids[] = $method['id'];
			}
		}
		/**
		 * Sort the shipping methods so that the selected method is always first
		 * in the array.
		 */
		usort( $methods, function ( $a, $b ) use ( $ids ) {
			return in_array( $a['id'], $ids ) ? - 1 : 1;
		} );
	}

	/**
	 *
	 * @param array    $methods
	 * @param WC_Order $orer
	 */
	return apply_filters( 'wc_stripe_get_shipping_options', $methods, $order );
}

/**
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_set_checkout_error() {
	add_action( 'woocommerce_after_template_part', 'wc_stripe_output_checkout_error' );
}

/**
 *
 * @param string $template_name
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_output_checkout_error( $template_name ) {
	if ( $template_name === 'notices/error.php' && is_ajax() ) {
		echo '<input type="hidden" id="wc_stripe_checkout_error" value="true"/>';
		remove_action( 'woocommerce_after_template_part', 'wc_stripe_output_checkout_error' );
		add_filter( 'wp_kses_allowed_html', 'wc_stripe_add_allowed_html', 10, 2 );
	}
}

/**
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_add_allowed_html( $tags, $context ) {
	if ( $context === 'post' ) {
		$tags['input'] = array(
			'id'    => true,
			'type'  => true,
			'value' => true,
		);
	}

	return $tags;
}

/**
 * Save WCS meta data when it's changed in the admin section.
 * By default WCS saves the
 * payment method title as the gateway title. This method saves the payment method title in
 * a human readable format suitable for the frontend.
 *
 * @param int     $post_id
 * @param WP_Post $post
 *
 * @package Stripe/Functions
 */
function wc_stripe_process_shop_subscription_meta( $post_id, $post ) {
	$subscription = wcs_get_subscription( $post_id );
	$gateway_id   = $subscription->get_payment_method();
	$gateways     = WC()->payment_gateways()->payment_gateways();
	if ( isset( $gateways[ $gateway_id ] ) ) {
		$gateway = $gateways[ $gateway_id ];
		if ( $gateway instanceof WC_Payment_Gateway_Stripe ) {
			$token = $gateway->get_token( $subscription->get_meta( WC_Stripe_Constants::PAYMENT_METHOD_TOKEN ), $subscription->get_customer_id() );
			if ( $token ) {
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->save();
			}
		}
	}
}

/**
 * Filter the WC payment gateways based on criteria specific to Stripe functionality.
 *
 * <strong>Example:</strong> on add payment method page, only show the CC gateway for Stripe.
 *
 * @param WC_Payment_Gateway[] $gateways
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_available_payment_gateways( $gateways ) {
	global $wp;
	if ( is_add_payment_method_page() && ! isset( $wp->query_vars['payment-methods'] ) ) {
		foreach ( $gateways as $gateway ) {
			if ( $gateway instanceof WC_Payment_Gateway_Stripe ) {
				if ( 'stripe_cc' !== $gateway->id ) {
					unset( $gateways[ $gateway->id ] );
				}
			}
		}
	}

	return $gateways;
}

/**
 *
 * @since   3.0.0
 * @return array
 * @package Stripe/Functions
 */
function wc_stripe_get_local_payment_params() {
	global $wp;
	$data     = array();
	$gateways = WC()->payment_gateways()->payment_gateways();
	foreach ( $gateways as $gateway ) {
		if ( $gateway instanceof WC_Payment_Gateway_Stripe_Local_Payment && $gateway->is_available() ) {
			$data['gateways'][ $gateway->id ] = $gateway->get_localized_params();
			if ( isset( $wp->query_vars['order-pay'] ) ) {
				$data['gateways'][ $gateway->id ]['order_id'] = $wp->query_vars['order-pay'];
			}
		}
	}
	$data['api_key'] = wc_stripe_get_publishable_key();

	return $data;
}

/**
 *
 * @param array $gateways
 *
 * @since   3.0.0
 * @return WC_Payment_Gateway[]
 * @package Stripe/Functions
 */
function wc_stripe_get_available_local_gateways( $gateways ) {
	foreach ( $gateways as $gateway ) {
		if ( $gateway instanceof WC_Payment_Gateway_Stripe_Local_Payment ) {
			if ( ! $gateway->is_local_payment_available() ) {
				unset( $gateways[ $gateway->id ] );
			}
		}
	}

	return $gateways;
}

/**
 *
 * @param string|int $key
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_set_idempotency_key( $key ) {
	global $wc_stripe_idempotency_key;
	$wc_stripe_idempotency_key = $key;
}

/**
 *
 * @since   3.0.0
 * @return mixed
 * @package Stripe/Functions
 */
function wc_stripe_get_idempotency_key() {
	global $wc_stripe_idempotency_key;

	return $wc_stripe_idempotency_key;
}

/**
 *
 * @param array $options
 *
 * @since   3.0.0
 * @return array
 * @package Stripe/Functions
 */
function wc_stripe_api_options( $options ) {
	$key = wc_stripe_get_idempotency_key();
	if ( $key ) {
		$options['idempotency_key'] = $key;
	}

	return $options;
}

/**
 *
 * @param string   $order_status
 * @param int      $order_id
 * @param WC_Order $order
 *
 * @since   3.0.0
 * <br/><strong>3.1.7</strong> - default $order argument of null added to prevent errors when 3rd party plugins trigger
 * action woocommerce_payment_complete_order_status and don't pass three arguments.
 * @package Stripe/Functions
 */
function wc_stripe_payment_complete_order_status( $order_status, $order_id, $order = null ) {
	if ( is_checkout() && $order && $order->get_payment_method() ) {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		if ( $gateway instanceof WC_Payment_Gateway_Stripe && 'default' !== $gateway->get_option( 'order_status', 'default' ) ) {
			$order_status = $gateway->get_option( 'order_status' );
		}
	}

	return $order_status;
}

/**
 * Converts the amount to cents.
 * Stripe processes all requests in cents.
 *
 * @param float  $value
 * @param string $currency
 * @param string $round
 *
 * @since   3.0.0
 * @return number
 * @package Stripe/Functions
 */
function wc_stripe_add_number_precision( $value, $currency = '', $round = true ) {
	if ( ! is_numeric( $value ) ) {
		$value = 0;
	}
	/**
	 * @since 3.3.6 - Get the number of decimals that the amount should be rounded to.
	 * round before performing precision calculation
	 */
	$decimals       = wc_get_price_decimals();
	$value          = round( $value, $decimals );
	$currency       = empty( $currency ) ? get_woocommerce_currency() : $currency;
	$currencies     = wc_stripe_get_currencies();
	$exp            = isset( $currencies[ $currency ] ) ? $currencies[ $currency ] : 2;
	$cent_precision = pow( 10, $exp );
	$value          = $value * $cent_precision;
	$value          = $round ? round( $value, wc_get_rounding_precision() - $decimals ) : $value;

	if ( is_numeric( $value ) && floor( $value ) != $value ) {
		// there are some decimal points that need to be removed.
		$value = round( $value );
	}

	return $value;
}

/**
 * Remove precision from a number.
 *
 * @param        $value
 * @param string $currency
 * @param bool   $round
 *
 * @since 3.2.10
 */
function wc_stripe_remove_number_precision( $value, $currency = '', $round = true, $dp = null ) {
	$currency   = empty( $currency ) ? get_woocommerce_currency() : $currency;
	$currencies = wc_stripe_get_currencies();
	$exp        = isset( $currencies[ $currency ] ) ? $currencies[ $currency ] : 2;
	$number     = $value / pow( 10, $exp );

	return $round ? round( $number, $dp === null ? wc_get_price_decimals() : $dp ) : $number;
}

/**
 * Return an array of credit card forms.
 *
 * @since   3.0.0
 * @return mixed
 * @package Stripe/Functions
 */
function wc_stripe_get_custom_forms() {
	return apply_filters(
		'wc_stripe_get_custom_forms',
		array(
			'bootstrap'  => array(
				'template'       => 'cc-forms/bootstrap.php',
				'label'          => __( 'Bootstrap form', 'woo-stripe-payment' ),
				'cardBrand'      => stripe_wc()->assets_url( 'img/card_brand2.svg' ),
				'elementStyles'  => array(
					'base'    => array(
						'color'             => '#495057',
						'fontWeight'        => 300,
						'fontFamily'        => 'Roboto, sans-serif, Source Code Pro, Consolas, Menlo, monospace',
						'fontSize'          => '16px',
						'fontSmoothing'     => 'antialiased',
						'::placeholder'     => array(
							'color'    => '#fff',
							'fontSize' => '0px',
						),
						':-webkit-autofill' => array( 'color' => '#495057' ),
					),
					'invalid' => array(
						'color'         => '#E25950',
						'::placeholder' => array( 'color' => '#757575' ),
					),
				),
				'elementOptions' => array(
					'fonts' => array( array( 'cssSrc' => 'https://fonts.googleapis.com/css?family=Source+Code+Pro' ) ),
				),
			),
			'simple'     => array(
				'template'       => 'cc-forms/simple.php',
				'label'          => __( 'Simple form', 'woo-stripe-payment' ),
				'cardBrand'      => stripe_wc()->assets_url( 'img/card_brand2.svg' ),
				'elementStyles'  => array(
					'base'    => array(
						'color'             => '#32325D',
						'fontWeight'        => 500,
						'fontFamily'        => 'Source Code Pro, Consolas, Menlo, monospace',
						'fontSize'          => '16px',
						'fontSmoothing'     => 'antialiased',
						'::placeholder'     => array( 'color' => '#CFD7DF' ),
						':-webkit-autofill' => array( 'color' => '#32325D' ),
					),
					'invalid' => array(
						'color'         => '#E25950',
						'::placeholder' => array( 'color' => '#FFCCA5' ),
					),
				),
				'elementOptions' => array(
					'fonts' => array( array( 'cssSrc' => 'https://fonts.googleapis.com/css?family=Source+Code+Pro' ) ),
				),
			),
			'minimalist' => array(
				'template'       => 'cc-forms/minimalist.php',
				'label'          => __( 'Minimalist form', 'woo-stripe-payment' ),
				'cardBrand'      => stripe_wc()->assets_url( 'img/card_brand2.svg' ),
				'elementStyles'  => array(
					'base'    => array(
						'color'             => '#495057',
						'fontWeight'        => 300,
						'fontFamily'        => 'Roboto, sans-serif, Source Code Pro, Consolas, Menlo, monospace',
						'fontSize'          => '30px',
						'fontSmoothing'     => 'antialiased',
						'::placeholder'     => array(
							'color'    => '#fff',
							'fontSize' => '0px',
						),
						':-webkit-autofill' => array( 'color' => '#495057' ),
					),
					'invalid' => array(
						'color'         => '#495057',
						'::placeholder' => array( 'color' => '#495057' ),
					),
				),
				'elementOptions' => array(
					'fonts' => array( array( 'cssSrc' => 'https://fonts.googleapis.com/css?family=Source+Code+Pro' ) ),
				),
			),
			'inline'     => array(
				'template'       => 'cc-forms/inline.php',
				'label'          => __( 'Inline Form', 'woo-stripe-payment' ),
				'cardBrand'      => stripe_wc()->assets_url( 'img/card_brand.svg' ),
				'elementStyles'  => array(
					'base'    => array(
						'color'               => '#819efc',
						'fontWeight'          => 600,
						'fontFamily'          => 'Roboto, Open Sans, Segoe UI, sans-serif',
						'fontSize'            => '16px',
						'fontSmoothing'       => 'antialiased',
						':focus'              => array( 'color' => '#819efc' ),
						'::placeholder'       => array( 'color' => '#87BBFD' ),
						':focus::placeholder' => array( 'color' => '#CFD7DF' ),
						':-webkit-autofill'   => array( 'color' => '#819efc' ),
					),
					'invalid' => array( 'color' => '#f99393' ),
				),
				'elementOptions' => array(
					'fonts' => array( array( 'cssSrc' => 'https://fonts.googleapis.com/css?family=Roboto' ) ),
				),
			),
			'rounded'    => array(
				'template'       => 'cc-forms/round.php',
				'label'          => __( 'Rounded Form', 'woo-stripe-payment' ),
				'cardBrand'      => stripe_wc()->assets_url( 'img/card_brand.svg' ),
				'elementStyles'  => array(
					'base'    => array(
						'color'               => '#fff',
						'fontWeight'          => 600,
						'fontFamily'          => 'Quicksand, Open Sans, Segoe UI, sans-serif',
						'fontSize'            => '16px',
						'fontSmoothing'       => 'antialiased',
						':focus'              => array( 'color' => '#424770' ),
						'::placeholder'       => array( 'color' => '#9BACC8' ),
						':focus::placeholder' => array( 'color' => '#CFD7DF' ),
						':-webkit-autofill'   => array( 'color' => '#fff' ),
					),
					'invalid' => array(
						'color'         => '#fff',
						':focus'        => array( 'color' => '#FA755A' ),
						'::placeholder' => array( 'color' => '#FFCCA5' ),
					),
				),
				'elementOptions' => array(
					'fonts' => array( array( 'cssSrc' => 'https://fonts.googleapis.com/css?family=Quicksand' ) ),
				),
			),
		)
	);
}

/**
 *
 * @param WC_Order $order
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_order_has_shipping_address( $order ) {
	if ( method_exists( $order, 'has_shipping_address' ) ) {
		return $order->has_shipping_address();
	} else {
		return $order->get_shipping_address_1() || $order->get_shipping_address_2();
	}
}

/**
 *
 * @since   3.0.0
 * @package Stripe/Functions
 */
function wc_stripe_display_prices_including_tax() {
	$cart = WC()->cart;
	if ( $cart && method_exists( $cart, 'display_prices_including_tax' ) ) {
		return $cart->display_prices_including_tax();
	}
	if ( $cart && is_callable( array( $cart, 'get_tax_price_display_mode' ) ) ) {
		return 'incl' == $cart->get_tax_price_display_mode() && ( WC()->customer && ! WC()->customer->is_vat_exempt() );
	}

	return 'incl' == $cart->tax_display_cart && ( WC()->customer && ! WC()->customer->is_vat_exempt() );
}

/**
 * Return true if the WC pre-orders plugin is active
 *
 * @since   3.0.1
 * @package Stripe/Functions
 */
function wc_stripe_pre_orders_active() {
	return class_exists( 'WC_Pre_Orders' );
}

/**
 *
 * @param string $source_id
 *
 * @since   3.0.5
 * @package Stripe/Functions
 */
function wc_stripe_get_order_from_source_id( $source_id ) {
	if ( \PaymentPlugins\Stripe\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$order_ids = wc_get_orders( [
			'type'       => 'shop_order',
			'limit'      => 1,
			'return'     => 'ids',
			'meta_query' => [
				[
					'key'   => WC_Stripe_Constants::SOURCE_ID,
					'value' => $source_id
				]
			]
		] );
		$order_id  = ! empty( $order_ids ) ? $order_ids[0] : null;
	} else {
		global $wpdb;
		$order_id
			= $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = %s AND meta.meta_key = %s AND meta.meta_value = %s LIMIT 1",
			'shop_order',
			WC_Stripe_Constants::SOURCE_ID,
			$source_id ) );
	}

	return wc_get_order( $order_id );
}

/**
 *
 * @param string $transaction_id
 *
 * @since   3.0.5
 * @return WC_Order|WC_Refund|boolean|WC_Order_Refund
 * @package Stripe/Functions
 */
function wc_stripe_get_order_from_transaction( $transaction_id ) {
	if ( \PaymentPlugins\Stripe\Utilities\FeaturesUtil::is_custom_order_tables_enabled() ) {
		$order_ids = wc_get_orders( [
			'type'           => 'shop_order',
			'limit'          => 1,
			'return'         => 'ids',
			'transaction_id' => $transaction_id
		] );
		$order_id  = ! empty( $order_ids ) ? $order_ids[0] : null;
	} else {
		global $wpdb;
		$order_id
			= $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id WHERE posts.post_type = %s AND meta.meta_key = %s AND meta.meta_value = %s LIMIT 1",
			'shop_order',
			'_transaction_id',
			$transaction_id ) );
	}

	return wc_get_order( $order_id );
}

/**
 * Stash the WC cart contents in the session and empty it's contents.
 * If $product_cart is true, add the stashed product(s)
 * to the cart.
 *
 * @param WC_Cart $cart
 * @param bool    $product_cart
 *
 * @since   3.0.6
 * @todo    Maybe empty cart silently so actions are not triggered that cause session data to be removed
 *       from 3rd party plugins.
 *
 * @package Stripe/Functions
 */
function wc_stripe_stash_cart( $cart, $product_cart = true ) {
	$data         = WC()->session->get( 'wc_stripe_cart', array() );
	$data['cart'] = $cart->get_cart_for_session();
	WC()->session->set( 'wc_stripe_cart', $data );
	$cart->empty_cart( false );
	if ( $product_cart && isset( $data['product_cart'] ) ) {
		// if there are args, map them to the request
		if ( isset( $data['request_params'] ) ) {
			foreach ( $data['request_params'] as $key => $value ) {
				$_REQUEST[ $key ] = $value;
			}
		}
		foreach ( $data['product_cart'] as $cart_item ) {
			$cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'] );
		}
	}
}

/**
 *
 * @param WC_Cart $cart
 * @param array   $params
 *
 * @since   3.0.6
 * @package Stripe/Functions
 */
function wc_stripe_stash_product_cart( $cart, $params = array() ) {
	$data                   = WC()->session->get( 'wc_stripe_cart', array() );
	$data['product_cart']   = $cart->get_cart_for_session();
	$data['request_params'] = $params;
	WC()->session->set( 'wc_stripe_cart', $data );
	WC()->cart->set_session();
}

/**
 *
 * @param WC_Cart $cart
 *
 * @since   3.0.6
 * @package Stripe/Functions
 */
function wc_stripe_restore_cart( $cart ) {
	$data                = WC()->session->get( 'wc_stripe_cart', array( 'cart' => array() ) );
	$cart->cart_contents = $data['cart'];
	$cart->set_session();
}

/**
 *
 * @since   3.0.6
 * @package Stripe/Functions
 */
function wc_stripe_restore_cart_after_product_checkout() {
	wc_stripe_restore_cart( WC()->cart );
	$cart_contents = array();
	foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
		$cart_item['data']     = wc_get_product( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
		$cart_contents[ $key ] = $cart_item;
	}
	WC()->cart->cart_contents = $cart_contents;
	WC()->cart->calculate_totals();
}

/**
 *
 * @param WC_Payment_Token[] $tokens
 * @param int                $user_id
 * @param string             $gateway_id
 *
 * @since   3.1.0
 * @return WC_Payment_Token[]
 * @package Stripe/Functions
 */
function wc_stripe_get_customer_payment_tokens( $tokens, $user_id, $gateway_id ) {
	foreach ( $tokens as $idx => $token ) {
		if ( $token instanceof WC_Payment_Token_Stripe ) {
			$mode = wc_stripe_mode();
			if ( $token->get_environment() != $mode ) {
				unset( $tokens[ $idx ] );
			}
		}
	}

	return $tokens;
}

/**
 *
 * @param array $labels
 *
 * @since   3.1.0
 * @return string
 * @package Stripe/Functions
 */
function wc_stripe_credit_card_labels( $labels ) {
	if ( ! isset( $labels['amex'] ) ) {
		$labels['amex'] = __( 'Amex', 'woocommerce' );
	}

	return $labels;
}

/**
 * Return an array of Stripe error messages.
 *
 * @since   3.1.1
 * @package Stripe/Functions
 */
function wc_stripe_get_error_messages() {
	return apply_filters(
		'wc_stripe_get_error_messages',
		array(
			'stripe_cc_generic'                                   => __( 'There was an error processing your credit card.', 'woo-stripe-payment' ),
			'incomplete_number'                                   => __( 'Your card number is incomplete.', 'woo-stripe-payment' ),
			'incomplete_expiry'                                   => __( 'Your card\'s expiration date is incomplete.', 'woo-stripe-payment' ),
			'incomplete_cvc'                                      => __( 'Your card\'s security code is incomplete.', 'woo-stripe-payment' ),
			'incomplete_zip'                                      => __( 'Your card\'s zip code is incomplete.', 'woo-stripe-payment' ),
			'incorrect_number'                                    => __( 'The card number is incorrect. Check the card\'s number or use a different card.', 'woo-stripe-payment' ),
			'incorrect_cvc'                                       => __( 'The card\'s security code is incorrect. Check the card\'s security code or use a different card.', 'woo-stripe-payment' ),
			'incorrect_zip'                                       => __( 'The card\'s ZIP code is incorrect. Check the card\'s ZIP code or use a different card.', 'woo-stripe-payment' ),
			'invalid_number'                                      => __( 'The card number is invalid. Check the card details or use a different card.', 'woo-stripe-payment' ),
			'invalid_characters'                                  => __( 'This value provided to the field contains characters that are unsupported by the field.', 'woo-stripe-payment' ),
			'invalid_cvc'                                         => __( 'The card\'s security code is invalid. Check the card\'s security code or use a different card.', 'woo-stripe-payment' ),
			'invalid_expiry_month'                                => __( 'The card\'s expiration month is incorrect. Check the expiration date or use a different card.', 'woo-stripe-payment' ),
			'invalid_expiry_year'                                 => __( 'The card\'s expiration year is incorrect. Check the expiration date or use a different card.', 'woo-stripe-payment' ),
			'invalid_number'                                      => __( 'The card number is invalid. Check the card details or use a different card.', 'woo-stripe-payment' ),
			'incorrect_address'                                   => __( 'The card\'s address is incorrect. Check the card\'s address or use a different card.', 'woo-stripe-payment' ),
			'expired_card'                                        => __( 'The card has expired. Check the expiration date or use a different card.', 'woo-stripe-payment' ),
			'card_declined'                                       => __( 'The card has been declined.', 'woo-stripe-payment' ),
			'invalid_expiry_year_past'                            => __( 'Your card\'s expiration year is in the past.', 'woo-stripe-payment' ),
			'account_number_invalid'                              => __( 'The bank account number provided is invalid (e.g., missing digits). Bank account information varies from country to country. We recommend creating validations in your entry forms based on the bank account formats we provide.',
				'woo-stripe-payment' ),
			'amount_too_large'                                    => __( 'The specified amount is greater than the maximum amount allowed. Use a lower amount and try again.', 'woo-stripe-payment' ),
			'amount_too_small'                                    => __( 'The specified amount is less than the minimum amount allowed. Use a higher amount and try again.', 'woo-stripe-payment' ),
			'authentication_required'                             => __( 'The payment requires authentication to proceed. If your customer is off session, notify your customer to return to your application and complete the payment. If you provided the error_on_requires_action parameter, then your customer should try another card that does not require authentication.',
				'woo-stripe-payment' ),
			'balance_insufficient'                                => __( 'The transfer or payout could not be completed because the associated account does not have a sufficient balance available. Create a new transfer or payout using an amount less than or equal to the account\'s available balance.',
				'woo-stripe-payment' ),
			'bank_account_declined'                               => __( 'The bank account provided can not be used to charge, either because it is not verified yet or it is not supported.', 'woo-stripe-payment' ),
			'bank_account_exists'                                 => __( 'The bank account provided already exists on the specified Customer object. If the bank account should also be attached to a different customer, include the correct customer ID when making the request again.',
				'woo-stripe-payment' ),
			'bank_account_unusable'                               => __( 'The bank account provided cannot be used for payouts. A different bank account must be used.', 'woo-stripe-payment' ),
			'bank_account_unverified'                             => __( 'Your Connect platform is attempting to share an unverified bank account with a connected account.', 'woo-stripe-payment' ),
			'bank_account_verification_failed'                    => __( 'The bank account cannot be verified, either because the microdeposit amounts provided do not match the actual amounts, or because verification has failed too many times.',
				'woo-stripe-payment' ),
			'card_decline_rate_limit_exceeded'                    => __( 'This card has been declined too many times. You can try to charge this card again after 24 hours. We suggest reaching out to your customer to make sure they have entered all of their information correctly and that there are no issues with their card.',
				'woo-stripe-payment' ),
			'charge_already_captured'                             => __( 'The charge you\'re attempting to capture has already been captured. Update the request with an uncaptured charge ID.', 'woo-stripe-payment' ),
			'charge_already_refunded'                             => __( 'The charge you\'re attempting to refund has already been refunded. Update the request to use the ID of a charge that has not been refunded.',
				'woo-stripe-payment' ),
			'charge_disputed'                                     => __( 'The charge you\'re attempting to refund has been charged back. Check the disputes documentation to learn how to respond to the dispute.',
				'woo-stripe-payment' ),
			'charge_exceeds_source_limit'                         => __( 'This charge would cause you to exceed your rolling-window processing limit for this source type. Please retry the charge later, or contact us to request a higher processing limit.',
				'woo-stripe-payment' ),
			'charge_expired_for_capture'                          => __( 'The charge cannot be captured as the authorization has expired. Auth and capture charges must be captured within seven days.',
				'woo-stripe-payment' ),
			'charge_invalid_parameter'                            => __( 'One or more provided parameters was not allowed for the given operation on the Charge. Check our API reference or the returned error message to see which values were not correct for that Charge.',
				'woo-stripe-payment' ),
			'email_invalid'                                       => __( 'The email address is invalid (e.g., not properly formatted). Check that the email address is properly formatted and only includes allowed characters.',
				'woo-stripe-payment' ),
			'idempotency_key_in_use'                              => __( 'The idempotency key provided is currently being used in another request. This occurs if your integration is making duplicate requests simultaneously.',
				'woo-stripe-payment' ),
			'invalid_charge_amount'                               => __( 'The specified amount is invalid. The charge amount must be a positive integer in the smallest currency unit, and not exceed the minimum or maximum amount.',
				'woo-stripe-payment' ),
			'invalid_source_usage'                                => __( 'The source cannot be used because it is not in the correct state (e.g., a charge request is trying to use a source with a pending, failed, or consumed source). Check the status of the source you are attempting to use.',
				'woo-stripe-payment' ),
			'missing'                                             => __( 'Both a customer and source ID have been provided, but the source has not been saved to the customer. To create a charge for a customer with a specified source, you must first save the card details.',
				'woo-stripe-payment' ),
			'postal_code_invalid'                                 => __( 'The ZIP code provided was incorrect.', 'woo-stripe-payment' ),
			'processing_error'                                    => __( 'An error occurred while processing the card. Try again later or with a different payment method.', 'woo-stripe-payment' ),
			'card_not_supported'                                  => __( 'The card does not support this type of purchase.', 'woo-stripe-payment' ),
			'call_issuer'                                         => __( 'The card has been declined for an unknown reason.', 'woo-stripe-payment' ),
			'card_velocity_exceeded'                              => __( 'The customer has exceeded the balance or credit limit available on their card.', 'woo-stripe-payment' ),
			'currency_not_supported'                              => __( 'The card does not support the specified currency.', 'woo-stripe-payment' ),
			'do_not_honor'                                        => __( 'The card has been declined for an unknown reason.', 'woo-stripe-payment' ),
			'fraudulent'                                          => __( 'The payment has been declined as Stripe suspects it is fraudulent.', 'woo-stripe-payment' ),
			'generic_decline'                                     => __( 'The card has been declined for an unknown reason.', 'woo-stripe-payment' ),
			'incorrect_pin'                                       => __( 'The PIN entered is incorrect. ', 'woo-stripe-payment' ),
			'insufficient_funds'                                  => __( 'The card has insufficient funds to complete the purchase.', 'woo-stripe-payment' ),
			'empty_element'                                       => __( 'Please select a payment method before proceeding.', 'woo-stripe-payment' ),
			'empty_element_sepa_debit'                            => __( 'Please enter your IBAN before proceeding.', 'woo-stripe-payment' ),
			'empty_element_ideal'                                 => __( 'Please select a bank before proceeding', 'woo-stripe-payment' ),
			'incomplete_iban'                                     => __( 'The IBAN you entered is incomplete.', 'woo-stripe-payment' ),
			'incomplete_boleto_tax_id'                            => __( 'Please enter a valid CPF / CNPJ', 'woo-stripe-payment' ),
			'test_mode_live_card'                                 => __( 'Your card was declined. Your request was in test mode, but you used a real credit card. Only test cards can be used in test mode.',
				'woo-stripe-payment' ),
			'server_side_confirmation_beta'                       => __( 'You do not have permission to use the PaymentElement card form. Please send a request to https://support.stripe.com/ and ask for the "server_side_confirmation_beta" to be added to your account.', 'woo-stripe-payment' ),
			'phone_required'                                      => __( 'Please provide a billing phone number.', 'woo-stripe-payment' ),
			'ach_instant_only'                                    => __( 'Your payment could not be processed at this time because your bank account does not support instant verification.', 'woo-stripe-payment' ),
			'payment_intent_konbini_rejected_confirmation_number' => __( 'The confirmation number was rejected by Konbini. Please try again.', 'woo-stripe-payment' ),
			'payment_intent_payment_attempt_expired'              => __( 'The payment attempt for this payment method has expired. Please try again.', '' )
		)
	);
}

/**
 * Return an array of Stripe currencies where the value of each
 * currency is the curency multiplier.
 *
 * @since   3.1.2
 * @return mixed
 * @package Stripe/Functions
 */
function wc_stripe_get_currencies() {
	return apply_filters(
		'wc_stripe_get_currencies',
		array(
			'BHD' => 3,
			'BIF' => 0,
			'CLP' => 0,
			'DJF' => 0,
			'GNF' => 0,
			'IQD' => 3,
			'JOD' => 3,
			'JPY' => 0,
			'KMF' => 0,
			'KRW' => 0,
			'KWD' => 3,
			'LYD' => 3,
			'MGA' => 0,
			'OMR' => 3,
			'PYG' => 0,
			'RWF' => 0,
			'TND' => 3,
			'UGX' => 0,
			'VND' => 0,
			'VUV' => 0,
			'XAF' => 0,
			'XOF' => 0,
			'XPF' => 0,
		)
	);
}

/**
 * Function that triggers a filter on the order id.
 * Allows 3rd parties to
 * convert the order_id from the metadata of the Stripe object.
 *
 * @param int                 $order_id
 * @param \Stripe\ApiResource $object
 *
 * @since   3.1.3
 * @package Stripe/Functions
 */
function wc_stripe_filter_order_id( $order_id, $object ) {
	return apply_filters( 'wc_stripe_filter_order_id', $order_id, $object );
}

/**
 * Removes order locks that have expired so the options table does not get cluttered with transients.
 *
 * @since    3.1.6
 * @package  Stripe/Functions
 */
function wc_stripe_remove_order_locks() {
	global $wpdb;

	// this operation could take some time, ensure it completes.
	wc_set_time_limit();

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_stripe_lock_order_%', time() ) );
	if ( $results ) {
		foreach ( $results as $result ) {
			// call delete_transient so Wordpress can fire all it's transient actions.
			delete_transient( substr( $result->option_name, strlen( '_transient_timeout_' ) ) );
		}
	}
}

/**
 * Returns an array of checkout fields needed to complete an order.
 *
 * @since 3.1.8
 * @return array
 */
function wc_stripe_get_checkout_fields() {
	global $wp;
	$fields = array();
	$order  = false;
	if ( ! empty( $wp->query_vars['order-pay'] ) ) {
		$order = wc_get_order( absint( ( $wp->query_vars['order-pay'] ) ) );
	}
	foreach ( array( 'billing', 'shipping' ) as $key ) {
		if ( ( $field_set = WC()->checkout()->get_checkout_fields( $key ) ) ) {
			$fields = array_merge( $fields, $field_set );
		}
	}
	// loop through fields and assign their value to the field.
	array_walk( $fields, function ( &$field, $key ) use ( $order ) {
		if ( $order ) {
			if ( is_callable( array( $order, "get_{$key}" ) ) ) {
				$field['value'] = $order->{"get_{$key}"}();
			} else {
				$field['value'] = WC()->checkout()->get_value( $key );
			}
		} else {
			$field['value'] = WC()->checkout()->get_value( $key );
		}
		/**
		 * Some 3rd party plugins hook in to WC filters and alter the expected
		 * type for required. This ensures it's converted back to a boolean.
		 */
		if ( isset( $field['required'] ) && ! is_bool( $field['required'] ) ) {
			$field['required'] = (bool) $field['required'];
		}
	} );
	do_action( 'wc_stripe_after_get_checkout_fields', $fields );

	return $fields;
}

/**
 * Filters a state value, making sure the abbreviated state value recognized by WC is returned.
 * Example: Texas = TX
 *
 * @param string $state
 * @param string $country
 *
 * @since 3.2.1
 * @return string
 *
 */
function wc_stripe_filter_address_state( $state, $country ) {
	$states = WC()->countries ? WC()->countries->get_states( $country ) : array();
	if ( ! empty( $states ) && is_array( $states ) && ! isset( $states[ $state ] ) ) {
		$state_keys = array_flip( array_map( 'strtoupper', $states ) );
		if ( isset( $state_keys[ strtoupper( $state ) ] ) ) {
			$state = $state_keys[ strtoupper( $state ) ];
		}
	}

	return $state;
}

/**
 * @since 3.2.3
 * @retun string
 */
function wc_stripe_get_current_page() {
	global $wp;
	if ( is_product() ) {
		return 'product';
	}
	if ( is_cart() ) {
		return 'cart';
	}
	if ( is_checkout() ) {
		if ( ! empty( $wp->query_vars['order-pay'] ) ) {
			if ( wcs_stripe_active() && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				return 'change_payment_method';
			}

			return 'order_pay';
		}

		return 'checkout';
	}
	if ( is_add_payment_method_page() ) {
		return 'add_payment_method';
	}

	return '';
}

/**
 * @since 3.3.14
 * @return mixed|void
 */
function wc_stripe_get_site_locale() {
	if ( stripe_wc()->advanced_settings && stripe_wc()->advanced_settings->get_option( 'locale', 'auto' ) !== 'auto' ) {
		if ( ( $locale = get_locale() ) ) {
			$locale = str_replace( '_', '-', substr( $locale, 0, 5 ) );
		} else {
			$locale = 'auto';
		}
	} else {
		$locale = 'auto';
	}

	return apply_filters( 'wc_stripe_get_site_locale', $locale );
}
