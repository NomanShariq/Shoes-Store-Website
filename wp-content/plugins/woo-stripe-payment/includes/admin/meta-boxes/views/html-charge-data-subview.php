<?php
/**
 * @var \Stripe\Charge $charge
 */
?>
<?php if ( ! $order->has_status( 'cancelled' ) ) : ?>
	<?php if ( ( $charge->status === 'pending' && ! $charge->captured ) || ( $charge->status === 'succeeded' && ! $charge->captured ) ) : ?>
        <div class="charge-actions">
            <h2><?php esc_html_e( 'Actions', 'woo-stripe-payment' ); ?></h2>
            <div>
                <input type="text" class="wc_input_price" name="capture_amount"
                       value="<?php echo $order->get_total(); ?>"
                       placeholder="<?php esc_html_e( 'capture amount', 'woo-stripe-payment' ); ?>"/>
                <button class="button button-secondary do-api-capture"><?php esc_html_e( 'Capture', 'woo-stripe-payment' ); ?></button>
                <button class="button button-secondary do-api-cancel"><?php esc_html_e( 'Void', 'woo-stripe-payment' ); ?></button>
            </div>
        </div>
	<?php endif; ?>
<?php endif; ?>
<div class="data-container">
    <div class="charge-data column-6">
        <h3><?php esc_html_e( 'Charge Data', 'woo-stripe-payment' ); ?></h3>
        <div class="metadata">
            <label><?php esc_html_e( 'Mode', 'woo-stripe-payment' ); ?></label>:&nbsp;
			<?php $charge->livemode ? esc_html_e( 'Live', 'woo-stripe-payment' ) : esc_html_e( 'Test', 'woo-stripe-payment' ); ?>
        </div>
        <div class="metadata">
            <label><?php esc_html_e( 'Status', 'woo-stripe-payment' ); ?></label>:&nbsp;
			<?php echo $charge->status; ?>
        </div>
		<?php if ( ( $payment_intent_id = $order->get_meta( '_payment_intent_id', true ) ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Payment Intent', 'woo-stripe-payment' ); ?></label>:&nbsp;
				<?php echo $payment_intent_id; ?>
            </div>
		<?php endif; ?>
		<?php if ( isset( $charge->customer ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Customer', 'woo-stripe-payment' ); ?></label>:&nbsp;
				<?php echo $charge->customer; ?>
            </div>
		<?php endif; ?>
    </div>
    <div class="payment-data column-6">
        <h3><?php esc_html_e( 'Payment Method', 'woo-stripe-payment' ); ?></h3>
        <div class="metadata">
            <label><?php esc_html_e( 'Title', 'woo-stripe-payment' ); ?></label>:&nbsp;
			<?php echo $order->get_payment_method_title(); ?>
        </div>
        <div class="metadata">
            <label><?php esc_html_e( 'Type', 'woo-stripe-payment' ); ?></label>:&nbsp;
			<?php echo $charge->payment_method_details->type; ?>
        </div>
		<?php if ( isset( $charge->payment_method_details->card ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Exp', 'woo-stripe-payment' ); ?>:&nbsp;</label>
				<?php printf( '%02d / %s', $charge->payment_method_details->card->exp_month, $charge->payment_method_details->card->exp_year ); ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Fingerprint', 'woo-stripe-payment' ); ?>:&nbsp;</label>
				<?php echo $charge->payment_method_details->card->fingerprint; ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'CVC check', 'woo-stripe-payment' ); ?>:&nbsp;</label>
				<?php echo $charge->payment_method_details->card->checks->cvc_check; ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Postal check', 'woo-stripe-payment' ); ?>:&nbsp;</label>
				<?php echo $charge->payment_method_details->card->checks->address_postal_code_check; ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Street check', 'woo-stripe-payment' ); ?>:&nbsp;</label>
				<?php echo $charge->payment_method_details->card->checks->address_line1_check; ?>
            </div>
		<?php endif; ?>
    </div>
    <div class="payment-data column-6">
        <h3><?php esc_html_e( 'Risk Data', 'woo-stripe-payment' ); ?></h3>
		<?php if ( isset( $charge->outcome->risk_score ) ) { ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Score', 'woo-stripe-payment' ); ?></label>
				<?php echo $charge->outcome->risk_score; ?>
            </div>
		<?php } ?>
        <div class="metadata">
            <label><?php esc_html_e( 'Level', 'woo-stripe-payment' ); ?></label>
			<?php echo $charge->outcome->risk_level; ?>
        </div>
    </div>
</div>
