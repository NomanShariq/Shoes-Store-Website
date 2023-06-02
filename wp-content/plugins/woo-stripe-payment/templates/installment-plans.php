<?php

/**
 * @var null|array $installments
 * @version 3.3.19
 */
if ( is_null( $installments ) ) {
	$installments = array( 'none' => array( 'text' => esc_html__( 'Fill out card form for eligibility.', 'woo-stripe-payment' ) ) );
}
?>
<div class="wc-stripe-installment-container">
    <label class="installment-label">
		<?php esc_html_e( 'Pay in installments:', 'woo-stripe-payment' ); ?>
        <div class="wc-stripe-installment-loader__container">
            <div class="wc-stripe-installment-loader" style="display: none">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </label>
	<?php woocommerce_form_field( WC_Stripe_Constants::INSTALLMENT_PLAN, array(
		'id'          => WC_Stripe_Constants::INSTALLMENT_PLAN,
		'type'        => 'select',
		'options'     => wp_list_pluck( $installments, 'text' ),
		'class'       => array( 'wc-stripe-installment-options' ),
		'input_class' => array( 'input-text' )
	), array_keys( $installments )[0] ) ?>
</div>
