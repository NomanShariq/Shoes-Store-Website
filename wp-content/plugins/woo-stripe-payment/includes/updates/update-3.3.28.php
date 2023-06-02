<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	$plaid_settings = get_option( 'woocommerce_stripe_plaid_settings', false );

	// only update if the plaid settings don't exist.
	if ( ! $plaid_settings ) {
		// ensure the update doesn't timeout
		wc_set_time_limit( 0 );
		$ach_gateway = WC()->payment_gateways()->payment_gateways()['stripe_ach'];
		if ( $ach_gateway ) {
			$plaid_settings = $ach_gateway->settings;

			$new_settings = [
				'enabled'           => $ach_gateway->get_option( 'enabled' ),
				'title_text'        => $ach_gateway->get_option( 'title_text' ),
				'description'       => $ach_gateway->get_option( 'description' ),
				'order_button_text' => $ach_gateway->get_option( 'order_button_text' ),
				'business_name'     => $ach_gateway->get_option( 'client_name' ),
				'method_format'     => $ach_gateway->get_option( 'method_format' ),
				'order_status'      => $ach_gateway->get_option( 'order_status' ),
				'fee'               => $ach_gateway->get_option( 'fee' )
			];

			// If plaid was enabled, add an option to the new settings
			if ( wc_string_to_bool( $ach_gateway->get_option( 'enabled' ) ) ) {
				$new_settings['plaid_enabled'] = 'yes';
			}

			// rename the stripe_ach settings to stripe_plaid
			update_option( 'woocommerce_stripe_plaid_settings', $plaid_settings );

			update_option( 'woocommerce_stripe_ach_settings', $new_settings );
		}
	}
}