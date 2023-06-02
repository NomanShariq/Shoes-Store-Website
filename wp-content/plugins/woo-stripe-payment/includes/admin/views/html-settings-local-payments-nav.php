<?php
global $current_section;
$tabs = apply_filters( 'wc_stripe_local_gateways_tab', array() );
ksort($tabs);
?>
<div class="wc-stripe-advanced-settings-nav local-gateways">
	<?php foreach ( $tabs as $id => $tab ) : ?>
		<a
		class="nav-link 
		<?php
		if ( $current_section === $id ) {
			echo 'nav-link-active';}
		?>
		"
		href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id ); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
</div>
<div class="clear"></div>
