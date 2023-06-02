<?php
/**
 * @version 3.1.7
 *
 * @var WC_Payment_Gateway_Stripe_Local_Payment $gateway
 */
?>
    <div id="wc_stripe_local_payment_<?php echo $gateway->id ?>" data-active="<?php echo $gateway->is_local_payment_available()?>">

    </div>
<?php if ( ( $desc = $gateway->get_local_payment_description() ) ): ?>
    <p class="wc-stripe-local-desc <?php echo $gateway->id ?>"><?php echo $desc ?></p>
<?php endif; ?>