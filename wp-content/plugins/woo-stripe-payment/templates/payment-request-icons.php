<?php
/**
 * @version 3.2.8
 */
?>
<div class="wc-stripe-paymentRequest-icons-container">
    <img class="wc-stripe-paymentRequest-icon gpay"
         src="<?php echo esc_url( stripe_wc()->assets_url( 'img/googlepay_round_outline.svg' ) ) ?>" style="display: none"/>
    <img class="wc-stripe-paymentRequest-icon microsoft-pay"
         src="<?php echo esc_url( stripe_wc()->assets_url( 'img/microsoft_pay.svg' ) ) ?>" style="display: none"/>
</div>