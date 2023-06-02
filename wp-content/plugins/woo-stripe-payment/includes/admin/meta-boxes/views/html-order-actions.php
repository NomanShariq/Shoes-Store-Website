<p>
	<b><?php esc_html_e( 'Charge Status', 'woo-stripe-payment' ); ?>:</b>&nbsp;<?php echo ucfirst( str_replace( '_', ' ', $status ) ); ?></p>
<?php

if ( $status === 'pending' && $order->get_meta( '_authorization_exp_at' ) ) :
	$date = new DateTime( '@' . $order->get_meta( '_authorization_exp_at' ) );
	?>
<p>
	<b><?php esc_html_e( 'Authorization Expires', 'woo-stripe-payment' ); ?>:</b>&nbsp;<?php echo date_format( $date, 'M d Y, h:i A e' ); ?>
<?php endif; ?>
<?php

switch ( $status ) {
	case 'succeeded':
	case 'faied':
		?>
		
<p><?php esc_html_e( 'There are no actions available at this time.', 'woo-stripe-payment' ); ?></p>
		<?php
		return;
}
$can_settle = $status === 'pending';
?>
<div id="wc-stripe-actions">
	<div class="wc-stripe-buttons-container">
		<?php if ( $can_settle ) : ?>
		<button type="button" class="button capture-charge"><?php esc_html_e( 'Capture Charge', 'woo-stripe-payment' ); ?></button>
		<?php endif; ?>
	</div>
	<div class="wc-order-data-row wc-order-capture-charge"
		style="display: none;">
		<div class="wc-order-capture-charge-container">
			<table class="wc-order-capture-charge">
				<tr>
					<td class="label"><?php esc_html_e( 'Total available to capture', 'woo-stripe-payment' ); ?>:</td>
					<td class="total"><?php echo wc_price( $order->get_total() ); ?></td>
				</tr>
				<tr>
					<td class="label"><?php esc_html_e( 'Amount To Capture', 'woo-stripe-payment' ); ?>:</td>
					<td class="total"><input type="text" id="worldpay_capture_amount"
						name="capture_amount" class="wc_input_price" />
						<div class="clear"></div></td>
				</tr>
			</table>
		</div>
		<div class="clear"></div>
		<div class="capture-actions">
			<button type="button" class="button button-primary do-api-capture"><?php esc_html_e( 'Capture', 'woo-stripe-payment' ); ?></button>
			<button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', 'woo-stripe-payment' ); ?></button>
		</div>
		<div class="clear"></div>
	</div>
</div>
