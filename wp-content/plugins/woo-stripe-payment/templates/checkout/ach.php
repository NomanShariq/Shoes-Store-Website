<?php
/**
 * @version 3.0.5
 * @var WC_Payment_Gateway_Stripe_ACH $gateway
 */
wc_stripe_hidden_field ( $gateway->metadata_key );
?>
<div id="wc-stripe-plaid-container">
	<?php if('sandbox' === $gateway->get_plaid_environment()):?>
	<p><?php esc_html_e('sandbox testing credentials', 'woo-stripe-payment')?>:</p>
	<p><strong><?php esc_html_e('username', 'woo-stripe-payment')?></strong>:&nbsp;user_good</p>
	<p><strong><?php esc_html_e('password', 'woo-stripe-payment')?></strong>:&nbsp;pass_good</p>
	<p><strong><?php esc_html_e('pin', 'woo-stripe-payment')?></strong>:&nbsp;credential_good&nbsp;(<?php esc_html_e('when required', 'woo-stripe-payment')?>)</p>
	<?php endif;?>
</div>