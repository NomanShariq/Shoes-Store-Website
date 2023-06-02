<?php
/**
 * @version 3.3.5
 * @package Stripe/Templates
 */
?>
<button class="apple-pay-button <?php echo esc_attr( $style ) ?>"
        style="<?php echo '-apple-pay-button-style: ' . esc_attr( $button_type ) . '; -apple-pay-button-type:' . apply_filters( 'wc_stripe_applepay_button_type', esc_attr( $type ) ) ?>"></button>