<?php

defined( 'ABSPATH' ) || exit();

if ( function_exists( 'WC' ) ) {
	$client       = WC_Stripe_Gateway::load();
	$api_settings = stripe_wc()->api_settings;
	foreach ( array( 'live', 'test' ) as $mode ) {
		$id = $api_settings->get_option( "webhook_id_{$mode}" );
		if ( $id ) {
			$webhook = $client->webhookEndpoints->retrieve( $id );
			if ( ! is_wp_error( $webhook ) && ! in_array( '*', $webhook->enabled_events, true ) ) {
				$events = array_values( array_unique( array_merge( $webhook->enabled_events, array( 'payment_intent.requires_action' ) ) ) );
				$client->webhookEndpoints->update( $id, array( 'enabled_events' => $events ) );
				wc_stripe_log_info( sprintf( 'Webhook %s updated during version 3.3.20 update.', $id ) );
			}
		}
	}
}