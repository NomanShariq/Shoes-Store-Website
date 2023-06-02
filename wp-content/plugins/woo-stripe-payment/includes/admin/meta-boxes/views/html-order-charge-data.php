<div class="transaction-data">
	<h3><?php esc_html_e( 'Transaction Data / Actions', 'woo-stripe-payment' ); ?></h3>
	<a href="#" class="do-stripe-transaction-view"
		data-order="<?php echo esc_attr($order->get_id()); ?>"></a>
</div>
<script type="text/template" id="tmpl-wc-stripe-view-transaction">
<div class="wc-backbone-modal">
	<div class="wc-backbone-modal-content wc-transaction-data">
		<section class="wc-backbone-modal-main" role="main">
			<header class="wc-backbone-modal-header">
				<h1>Transaction #{{ data.charge.id }}</h1>
				<button
					class="modal-close modal-close-link dashicons dashicons-no-alt">
					<span class="screen-reader-text">Close modal panel</span>
				</button>
			</header>
			<article class="wc-transaction-data-container">
				{{{ data.html }}}
			</article>
			<footer>
				<div class="inner">
					
				</div>
			</footer>
		</section>
	</div>
</div>
<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
<style>
#order_data .order_data_column .transaction-data a.disabled:before {
	content: '';
	background: url(<?php echo plugins_url('assets/images/wpspin.gif', WC_PLUGIN_FILE)?>)
		no-repeat center top;
	padding: 0px 10px;
}
</style>
