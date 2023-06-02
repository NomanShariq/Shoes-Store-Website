<?php
/**
 * @version 3.13.8
 *
 * @var WC_Payment_Gateway_Stripe_Local_Payment $gateway
 */
?>
<div id="wc_stripe_local_payment_<?php echo esc_attr( $gateway->id ) ?>" data-active="<?php echo $gateway->is_local_payment_available() ?>">
	<?php woocommerce_form_field( 'wc_stripe_boleto_tax_id', array(
		'type'        => 'text',
		'label'       => __( 'CPF / CNPJ' ),
		'placeholder' => __( 'Enter your CPF/CNPJ', 'woo-stripe-payment' ),
		'required'    => true
	) ) ?>
	<?php if ( wc_stripe_mode() === 'test' ): ?>
        <div class="wc-stripe-boleto__description">
            <p><?php esc_html_e( 'Test mode values', 'woo-stripe-payment' ) ?></p>
            <div>
                <label>CPF:</label>&nbsp;<span>000.000.000-00</span>
            </div>
            <div>
                <label>CNPJ:</label>&nbsp;<span>00.000.000/0000-00</span>
            </div>
        </div>
	<?php else: ?>
        <div class="wc-stripe-boleto__description">
            <p><?php esc_html_e( 'Accepted formats', 'woo-stripe-payment' ) ?></p>
            <div>
                <label>CPF:</label>&nbsp;<span><?php esc_html_e( 'XXX.XXX.XXX-XX or XXXXXXXXXXX', 'woo-stripe-payment' ) ?></span>
            </div>
            <div>
                <label>CNPJ:</label>&nbsp;<span><?php esc_html_e( 'XX.XXX.XXX/XXXX-XX or XXXXXXXXXXXXXX', 'woo-stripe-payment' ) ?></span>
            </div>
        </div>
	<?php endif; ?>
</div>