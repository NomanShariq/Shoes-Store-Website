<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	// if test account info is blank, load it
	/**
	 * @var \WC_Stripe_Account_Settings $settings
	 */
	$account_settings = stripe_wc()->account_settings;
	$account_id       = stripe_wc()->api_settings->get_account_id( 'test' );
	if ( ! $account_id ) {
		$account_settings->save_account_settings( null, 'test' );
	}
}