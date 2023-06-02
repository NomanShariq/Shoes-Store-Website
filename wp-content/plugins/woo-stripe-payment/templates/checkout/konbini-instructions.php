<?php
/**
 * @version 3.3.38
 */

?>
<div class="wc-stripe-konbini-instructions">
    <ol>
        <li>
			<?php printf( esc_html__( 'Click %1$s and you will be presented with your Konbini payment code and confirmation number.', 'woo-stripe-payment' ), '<b>' . esc_html( $button_text ) . '</b>' ) ?>
        </li>
        <li>
			<?php esc_html_e( 'Your order email will contain a link to your Konbini voucher which has your payment code and confirmation number.', 'woo-stripe-payment' ) ?>
        </li>
        <li>
			<?php esc_html_e( 'At the convenience store, provide the payment code and confirmation number to the payment machine or cashier.', 'woo-stripe-payment' ) ?>
        </li>
        <li>
			<?php esc_html_e( 'After the payment is complete, keep the receipt for your records.', 'woo-stripe-payment' ) ?>
        </li>
    </ol>
</div>
