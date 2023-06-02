<?php

defined( 'ABSPATH' ) || exit();

/**
 * Load the test mode account details
 */
if ( function_exists( 'WC' ) ) {
	$account_settings = stripe_wc()->account_settings;
	if ( $account_settings ) {
		$account_settings->save_account_settings( null, WC_Stripe_Constants::TEST );
	}
}