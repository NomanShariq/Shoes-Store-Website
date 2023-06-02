<?php
/**
 * @var WC_Payment_Gateway_Stripe_CC $gateway
 * @version 3.3.11
 *
 */

?>
<?php if ( $gateway->is_custom_form_active() ): ?>
    <div id="wc-stripe-cc-custom-form">
		<?php wc_stripe_get_template( $gateway->get_custom_form_template(), [ 'gateway' => $gateway ] ) ?>
    </div>
<?php else: ?>
    <div id="wc-stripe-card-element" class="<?php echo $gateway->get_option( 'form_type' ) ?>-type"></div>
<?php endif; ?>
<?php if ( $gateway->show_save_source() ): ?>
    <div class="wc-stripe-save-source"
	     <?php if ( ! is_user_logged_in() && ! WC()->checkout()->is_registration_required() ): ?>style="display:none"<?php endif ?>>
        <label class="checkbox">
            <input type="checkbox" id="<?php echo $gateway->save_source_key ?>" name="<?php echo $gateway->save_source_key ?>" value="yes"/>
            <span class="save-source-checkbox"></span>
        </label>
        <label class="save-source-label"><?php esc_html_e( 'Save Card', 'woo-stripe-payment' ) ?></label>
    </div>
<?php endif; ?>