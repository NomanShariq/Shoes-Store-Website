<?php
/**
 * @var $text  string
 * @var $title string
 * @version 3.3.34
 */

?>
<div class="wc-stripe-offsite-notice-container">
    <div class="wc-stripe-offsite-notice">
        <img src="<?php echo esc_url( stripe_wc()->assets_url( 'img/offsite.svg' ) ) ?>"/>
        <p><?php printf( esc_html__( 'After clicking "%1$s", you will be redirected to %2$s to complete your purchase securely.', 'woo-stripe-payment' ), esc_html( $text ), esc_html( $title ) ) ?></p>
    </div>
</div>
