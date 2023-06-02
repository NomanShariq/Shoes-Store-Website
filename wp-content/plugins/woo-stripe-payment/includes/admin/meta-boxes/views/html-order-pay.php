<p class="form-field form-field-wide">
	<button class="button button-secondary wc-stripe-pay-order"><?php esc_html_e( 'Pay for Order', 'woo-stripe-payment' ); ?></button>
			<?php echo wc_help_tip( __( 'Admins can process customer orders over the phone using this functionality.', 'woo-stripe-payment' ) ); ?>
</p>
<script type="text/template" id="tmpl-wc-stripe-modal-pay-order">
<div class="wc-backbone-modal">
	<div class="wc-backbone-modal-content">
		<section class="wc-backbone-modal-main" role="main">
			<header class="wc-backbone-modal-header">
				<h1><?php esc_html_e( 'Pay for Order', 'woocommerce' ); ?></h1>
				<button
					class="modal-close modal-close-link dashicons dashicons-no-alt">
					<span class="screen-reader-text">Close modal panel</span>
				</button>
			</header>
			<article>
				<form id="wc-stripe-pay-order-form">
					<input type="hidden" name="customer_id" value="{{{data.customer_id}}}"/>
					<input type="hidden" name="order_id" value="{{{data.order_id}}}"/>
					<div class="modal-wide option">
						<label><?php esc_html_e( 'Charge Type', 'woo-stripe-payment' ); ?></label>
						<select name="wc_stripe_charge_type" class="wc-select2">
                            <option value="capture"><?php esc_html_e( 'Capture', 'woo-stripe-payment' ); ?></option>
							<option value="authorize"><?php esc_html_e( 'Authorize', 'woo-stripe-payment' ); ?></option>
						</select>
					</div>
					<#if(data.payment_methods.length){#>					
						<div class="modal-wide">
							<input type="radio" value="token" name="payment_type" checked/>
							<label class=""><?php esc_html_e( 'Saved Cards', 'woo-stripe-payment' ); ?></label>
							<div class="token-container show_if_token hide_if_nonce">
								<select name="payment_token_id" class="wc-select2">
								<#_.each(data.payment_methods, function(method){#>
									<option value="{{{method.id}}}">{{{method.title}}}</option>
								<#})#>
								</select>
							</div>
						</div>
					<#}#>
					<div class="modal-wide">
						<input type="radio" value="nonce" name="payment_type" class="" <#if(!data.payment_methods.length){#>checked<#}#>/>
						<label class=""><?php esc_html_e( 'New Card', 'woo-stripe-payment' ); ?></label>
						<input type="hidden" name="payment_nonce"/>
						<div id="wc-stripe-card-container" class="wc-stripe-card-container show_if_nonce hide_if_token">
							<div id="card-element"></div>
						</div>
					<div>
				</form>
			</article>
			<footer>
				<div class="inner">
					<button id="pay-order" class="button button-primary button-large"><?php esc_html_e( 'Pay', 'woo-stripe-payment' ); ?></button>
				</div>
			</footer>
		</section>
	</div>
</div>
<div class="wc-backbone-modal-backdrop modal-close"></div>			
</script>
