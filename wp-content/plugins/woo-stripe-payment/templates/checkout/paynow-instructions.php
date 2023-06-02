<?php
/**
 * @version 3.3.38
 */

?>
<div class="wc-stripe-paynow-instructions">
    <ol>
        <li>
			<?php printf( esc_html__( 'Click %1$s and you will be shown a QR code.', 'woo-stripe-payment' ), '<b>' . esc_html( $button_text ) . '</b>' ) ?>
        </li>
        <li>
			<?php esc_html_e( 'Scan the QR code using an app from participating banks and participating non-bank financial institutions.', 'woo-stripe-payment' ) ?>
        </li>
        <li>
			<?php esc_html_e( 'The authentication process may take several moments. Once confirmed, you will be redirected to the order received page.', 'woo-stripe-payment' ) ?>
        </li>
    </ol>
</div>
