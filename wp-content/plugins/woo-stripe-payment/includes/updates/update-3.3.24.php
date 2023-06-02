<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	$client       = WC_Stripe_Gateway::load();
	$api_settings = stripe_wc()->api_settings;
	foreach ( array( 'live', 'test' ) as $mode ) {
		$id = $api_settings->get_option( "webhook_id_{$mode}" );
		if ( $id ) {
			$webhook = $client->mode( $mode )->webhookEndpoints->retrieve( $id );
			if ( ! is_wp_error( $webhook ) && ! in_array( '*', $webhook->enabled_events, true ) ) {
				$events = array_values( array_unique( array_merge( $webhook->enabled_events, array( 'charge.pending' ) ) ) );
				$client->mode( $mode )->webhookEndpoints->update( $id, array( 'enabled_events' => $events ) );
				wc_stripe_log_info( sprintf( 'Mode: %s. charge.pending event added to webhook %s in version 3.3.24 update.', $mode, $id ) );
			}
		}
	}
}