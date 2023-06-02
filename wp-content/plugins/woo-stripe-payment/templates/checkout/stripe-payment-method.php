<?php
/**
 * @var WC_Payment_Gateway_Stripe $gateway
 * @version 3.3.19
 *
 */

?>
<?php if ( ( $desc = $gateway->get_description() ) ): ?>
    <div class="wc-stripe-gateway-desc<?php if ( $tokens ): ?> has_tokens<?php endif; ?>">
		<?php echo wpautop( wptexturize( $desc ) ) ?>
    </div>
<?php endif; ?>

<div class="wc-<?php echo $gateway->id ?>-container wc-stripe-gateway-container<?php if ( $tokens ): ?> has_tokens<?php endif; ?>">
	<?php if ( $tokens ): ?>
        <input type="radio" class="wc-stripe-payment-type"
               id="<?php echo $gateway->id ?>_use_new"
               name="<?php echo $gateway->payment_type_key ?>" value="new"/>
        <label for="<?php echo $gateway->id ?>_use_new"
               class="wc-stripe-label-payment-type"><?php echo $gateway->get_new_method_label() ?></label>
	<?php endif; ?>
    <div class="wc-<?php echo $gateway->id ?>-new-method-container"
		<?php if ( $tokens ): ?> style="display: none" <?php endif; ?>>
		<?php wc_stripe_get_template( 'checkout/' . $gateway->template_name, array( 'gateway' => $gateway ) ) ?>
    </div>
	<?php
	if ( $tokens ) :
		$gateway->saved_payment_methods( $tokens );
	endif;
	?>
	<?php if ( ( is_checkout() || is_checkout_pay_page() ) && $gateway->is_installment_available() ): ?>
		<?php wc_stripe_get_template( 'installment-plans.php', array( 'installments' => null ) ) ?>
	<?php endif ?>
</div>