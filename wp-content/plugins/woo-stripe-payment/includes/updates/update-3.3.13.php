<?php

defined( 'ABSPATH' ) || exit();

/**
 * @param \WC_Settings_API $settings
 * @param                  $key
 * @param                  $value
 */
function wc_stripe_update_option_3_3_13( $settings, $key, $value ) {
	if ( method_exists( $settings, 'update_option' ) ) {
		$settings->update_option( $key, $value );
	} else {
		$settings->settings[ $key ] = $value;
		update_option( $settings->get_option_key(), $settings->settings );
	}
}

// transfer email option to the advanced settings
wc_stripe_update_option_3_3_13( stripe_wc()->advanced_settings, 'email_enabled', get_option( 'woocommerce_stripe_email_receipt', 'no' ) );
delete_option( 'woocommerce_stripe_email_receipt' );

// Add the webhook ID to the plugin so the webhook can be updated with the new events
/**
 * @var \WC_Stripe_API_Settings $settings
 */
$settings = stripe_wc()->api_settings;
$client   = WC_Stripe_Gateway::load();
$url      = get_rest_url( null, '/wc-stripe/v1/webhook' );

foreach ( array( 'live', 'test' ) as $mode ) {
	$webhook_id = $settings->get_webhook_id( $mode );
	if ( ! $webhook_id ) {
		//webhook ID isn't in settings so fetch webhooks and find this plugin's webhook.
		// Once found, add it to the API Settings
		$webhooks = $client->mode( $mode )->webhookEndpoints->all( array( 'limit' => 50 ) );
		if ( ! is_wp_error( $webhooks ) ) {
			foreach ( $webhooks->data as $webhook ) {
				/**
				 * @var \Stripe\WebhookEndpoint $webhook
				 */
				if ( $webhook->url === $url ) {
					wc_stripe_update_option_3_3_13( $settings, "webhook_id_{$mode}", $webhook->id );
					$webhook_id = $webhook->id;
					wc_stripe_log_info( sprintf( 'Webhook %1$s saved in settings. Mode: %2$s', $webhook_id, $mode ) );
					break;
				}
			}
		} else {
			wc_stripe_log_error( sprintf( 'Error fetching webhooks during version 3.1.3 update. Reason: %s', $webhooks->get_error_message() ) );
		}
	}

	if ( $webhook_id ) {
		// update the webhook with all the new events
		$webhook = $client->mode( $mode )->webhookEndpoints->retrieve( $webhook_id );
		if ( ! is_wp_error( $webhook ) ) {
			if ( ! in_array( '*', $webhook->enabled_events ) ) {
				$webhook = $client->mode( $mode )->webhookEndpoints->update( $webhook_id, array(
					'url'            => $url,
					'enabled_events' => array_values( array_unique( array_merge( array(
						'charge.failed',
						'charge.succeeded',
						'source.chargeable',
						'payment_intent.succeeded',
						'charge.refunded',
						'charge.dispute.created',
						'charge.dispute.closed',
						'review.opened',
						'review.closed'
					), $webhook->enabled_events ) ) )
				) );
				if ( is_wp_error( $webhook ) ) {
					wc_stripe_log_error( sprintf( 'Error updating Stripe webhook. Mode: %1$s. Reason: %2$s', $mode, $webhook->get_error_message() ) );
				}
			}
		}
	}
}