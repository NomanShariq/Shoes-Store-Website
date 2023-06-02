<?php

$page    = isset( $_GET['page'] ) ? $_GET['page'] : '';
$section = isset( $_GET['section'] ) ? $_GET['section'] : '';
?>
<div class="navigation">
    <div class="wc-stripe-main__logo">
        <img src="<?php echo stripe_wc()->assets_url( 'img/paymentplugins.svg' ) ?>"/>
    </div>
    <div class="navigation-tabs">
        <a class="<?php if ( $page === 'wc-stripe-main' && ! $section ) { ?>active<?php } ?>" href="<?php echo admin_url( 'admin.php?page=wc-stripe-main' ) ?>">
			<?php esc_html_e( 'Main Page', 'woo-stripe-payment' ) ?>
        </a>
        <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe_api' ) ?>">
			<?php esc_html_e( 'Settings', 'woo-stripe-payment' ) ?>
        </a>
        <a target="_blank" href="https://docs.paymentplugins.com/wc-stripe/config">
			<?php esc_html_e( 'Documentation', 'woo-stripe-payment' ) ?>
        </a>
        <a class="<?php if ( $section === 'support' ) { ?>active<?php } ?>" href="<?php echo admin_url( 'admin.php?page=wc-stripe-main&section=support' ) ?>">
			<?php esc_html_e( 'Support', 'woo-stripe-payment' ) ?>
        </a>
    </div>
    <div class="stripe-logo">
        <a target="_blank" href="https://stripe.com">
            <img src="<?php echo stripe_wc()->assets_url( 'img/stripe_logo.svg' ) ?>"/>
        </a>
    </div>
</div>