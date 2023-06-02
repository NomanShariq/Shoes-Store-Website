<?php
/**
 * @version 3.0.9
 * 
 * @var WC_Payment_Gateway_Stripe[] $gateways
 */
?>
<div class="wc-stripe-cart-checkout-container" <?php if($cart_total == 0){?>style="display: none"<?php }?>>
	<ul class="wc_stripe_cart_payment_methods" style="list-style: none">
		<?php if($after):?>
				<li class="wc-stripe-payment-method or">
					<p class="wc-stripe-cart-or">
						&mdash;&nbsp;<?php esc_html_e('or', 'woo-stripe-payment')?>&nbsp;&mdash;
					</p>
				</li>
		<?php endif;?>
		<?php foreach($gateways as $gateway):?>
			<li
			class="wc-stripe-payment-method payment_method_<?php echo esc_attr($gateway->id)?>">
			<div class="payment-box">
					<?php $gateway->cart_fields()?>
            </div>
		</li>
		<?php endforeach;?>
		<?php if(!$after):?>
				<li class="wc-stripe-payment-method or">
					<p class="wc-stripe-cart-or">
						&mdash;&nbsp;<?php esc_html_e('or', 'woo-stripe-payment')?>&nbsp;&mdash;
					</p>
				</li>
		<?php endif;?>
	</ul>
</div>
