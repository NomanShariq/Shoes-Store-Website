<?php
defined( 'ABSPATH' ) || exit();

// update webhooks to include payment_intent.succeeded
$environments = array( 'live', 'test' );

foreach ( $environments as $env ) {
	$webhook_id = stripe_wc()->api_settings->get_option( "webhook_id_{$env}" );
	if ( $webhook_id ) {
		$gateway = WC_Stripe_Gateway::load( $env );

		// fetch webhook so we can merge existing events with the new payment_intent.succeeded event
		$webhook = $gateway->webhookEndpoints->retrieve( $webhook_id );
		if ( ! is_wp_error( $webhook ) ) {
			$events   = $webhook['enabled_events'];
			$events[] = 'payment_intent.succeeded';

			$result = $gateway->webhookEndpoints->update( $webhook_id, array( 'enabled_events' => $events ) );
			wc_stripe_log_info( "Webhook {$webhook_id} updated." );
		}
	}
}

update_option( 'wc_stripe_connect_notice', 'yes' );

// send email
ob_start();
WC_Emails::instance()->email_header( __( 'Stripe For WooCommerce Update', 'woo-stripe-payment' ) );
?>
    <p><?php esc_html_e( 'Greetings from Payment Plugins,', 'woo-stripe-payment' ); ?></p>
    <p><?php esc_html_e( 'At Stripe\'s request, we have updated Stripe for WooCommerce to use the new Stripe Connect integration. This new integration offers even more security. Stripe is requesting that all merchants switch.', 'woo-stripe-payment' ); ?></p>
    <p><?php printf( __( 'Click %1$shere%2$s to be redirected to your Stripe API settings page then click the <strong>Click to Connect</strong> button.', 'woo-stripe-payment' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ) . '" target="_blank">', '</a>' ); ?>


    <p><?php esc_html_e( 'Kind Regards,', 'woo-stripe-payment' ); ?></p>
    <p><?php esc_html_e( 'Payment Plugins' ); ?></p>
<?php
WC_Emails::instance()->email_footer();
$content           = ob_get_clean();
$settings          = get_option( 'woocommerce_new_order_settings', array( 'recipient' => get_option( 'admin_email', '' ) ) );
$email             = new WC_Email();
$email->email_type = 'html';
add_filter(
	'woocommerce_email_from_address',
	function ( $from ) {
		return 'support@paymentplugins.com';
	}
);
add_filter(
	'woocommerce_email_from_name',
	function ( $name ) {
		return 'Payment Plugins';
	}
);
$email->send( $settings['recipient'], __( 'Stripe For WooCommerce Update', 'woo-stripe-payment' ), $content, $email->get_headers(), array() );
