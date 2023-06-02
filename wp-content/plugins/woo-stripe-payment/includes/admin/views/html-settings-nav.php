<?php

global $current_section;
$tabs       = apply_filters( 'wc_stripe_settings_nav_tabs', array() );
$last       = count( $tabs );
$idx        = 0;
$tab_active = false;
?>
<div class="wc-stripe-settings-logo">
    <img class="paymentplugins-logo" src="<?php echo stripe_wc()->assets_url() . 'img/paymentplugins.svg'; ?>"/>
    <span><?php esc_html_e( 'for', 'woo-stripe-payment' ) ?></span>
    <img src="<?php echo stripe_wc()->assets_url() . 'img/stripe_logo.svg'; ?>"/>
</div>
<div class="stripe-settings-nav">
	<?php foreach ( $tabs as $id => $tab ) : $idx ++ ?>
        <a class="nav-tab <?php if ( $current_section === $id || ( ! $tab_active && $last === $idx ) ) {
			echo 'nav-tab-active';
			$tab_active = true;
		} ?>"
           href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
<!--<div class="wc-stripe-docs">
    <a target="_blank" class="button button-secondary"
       href="<?php /*echo $this->get_stripe_documentation_url(); */ ?>"><?php /*esc_html_e( 'Documentation', 'woo-stripe-payment' ); */ ?></a>
</div>-->
