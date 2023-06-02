<?php
/**
 * @version 3.3.38
 */

?>
<div id="wc_stripe_local_payment_<?php echo $gateway->id ?>" data-active="<?php echo $gateway->is_local_payment_available() ?>">
    <ol>
        <li><?php esc_html_e( 'Request your 6-digit code from your banking application.', 'woo-stripe-payment' ) ?></li>
        <li><?php printf( esc_html__( 'Enter the code into the input fields below. Click %1$s once you have entered the code.', 'woo-stripe-payment' ), '<b>' . $gateway->order_button_text . '</b>' ) ?></li>
        <li><?php esc_html_e( 'You will receive a notification on your mobile device asking you to authorize the payment.', 'woo-stripe-payment' ); ?></li>
    </ol>
    <div class="wc-stripe-blik-code-container">
        <p>
			<?php esc_html_e( 'Please enter your 6 digit BLIK code.', 'woo-stripe-payment' ) ?>
        </p>
        <div class="wc-stripe-blik-code">
			<?php foreach ( range( 0, 5 ) as $idx ): ?>
				<?php woocommerce_form_field( 'blik_code_' . $idx, array(
					'type'              => 'text',
					'maxlength'         => 1,
					'input_class'       => array( 'blik-code' ),
					'custom_attributes' => array( 'data-blik_index' => $idx )
				) ) ?>
			<?php endforeach; ?>
        </div>
    </div>
    <div class="blik-timer-container" style="display: none">
        <div>
            <p>
				<?php esc_html_e( 'Your transaction will expire in:', 'woo-stripe-payment' ) ?>
            </p>
            <span id="blik_timer"></span>
        </div>
    </div>
</div>

