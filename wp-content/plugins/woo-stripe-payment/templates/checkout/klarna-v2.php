<?php

/**
 * @var WC_Payment_Gateway_Stripe_Klarna $gateway
 * @version 3.3.13
 *
 */
$payment_options = $gateway->get_option( 'payment_categories' );
?>
<?php if ( wc_stripe_mode() === 'test' ): ?>
    <div class="wc-stripe-klarna__testmode">
        <label><?php esc_html_e( 'Test mode sms', 'woo-stripe-payment' ); ?>:</label>&nbsp;<span>123456</span>
    </div>
<?php endif; ?>
<div id="wc_stripe_local_payment_<?php echo $gateway->id ?>" data-active="<?php echo $gateway->is_local_payment_available() ?>">

</div>