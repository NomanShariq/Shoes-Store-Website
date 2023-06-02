<?php
/**
 * @version 3.0.0
 * @var WC_Payment_Gateway_Stripe_CC $gateway
 */
?>
<div class="wc-stripe-inline-form">
    <div class="row">
        <label for="stripe-card-number"><?php esc_html_e( 'Card Number', 'woo-stripe-payment' ) ?></label>
        <div id="stripe-card-number" class="input"></div>
    </div>
    <div class="row">
        <label for="stripe-exp"><?php esc_html_e( 'Exp Date', 'woo-stripe-payment' ) ?></label>
        <div id="stripe-exp" class="input"></div>
    </div>
    <div class="row">
        <label for="stripe-cvv"><?php esc_html_e( 'CVV', 'woo-stripe-payment' ) ?></label>
        <div id="stripe-cvv" class="input"></div>
    </div>
	<?php if ( $gateway->postal_enabled() ): ?>
        <div class="row">
            <label for="stripe-postal-code"><?php esc_html_e( 'ZIP', 'woo-stripe-payment' ) ?></label>
            <input type="text" id="stripe-postal-code" class="input empty" placeholder="78703"
                   value="<?php echo esc_attr( WC()->checkout()->get_value( 'billing_postcode' ) ) ?>"/>
        </div>
	<?php endif; ?>
</div>
<style type="text/css">
    .wc-stripe-inline-form {
        background-color: #fff;
        padding: 0;
    }

    .wc-stripe-inline-form #wc-stripe-card {
        top: 10px;
    }

    #stripe-card-number {
        position: relative;
    }

    .wc-stripe-inline-form * {
        font-family: Roboto, Open Sans, Segoe UI, sans-serif;
        font-size: 16px;
        font-weight: 500;
    }

    .payment_method_stripe_cc .wc-stripe-inline-form fieldset {
        margin: 0;;
        padding: 0;
        border-top: 1px solid #829fff;
        border-bottom: 1px solid #829fff;
    }

    .wc-stripe_cc-container .wc-stripe-inline-form .StripeElement {
        padding: 0;
    }

    .wc-stripe-inline-form .row {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-align: center;
        align-items: center;
        margin: 0 !important;
        flex-flow: row wrap;
        width: 100%;
    }

    .wc-stripe-inline-form .row {
        border-bottom: 1px solid #819efc;
    }

    .wc-stripe-inline-form label,
    .woocommerce-checkout .woocommerce-checkout #payment ul.payment_methods li .wc-stripe-inline-form label {
        width: 110px;
        min-width: 110px;
        padding: 11px 0;
        color: #91b5c1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin: 0;
    }

    .wc-stripe-inline-form input, .wc-stripe-inline-form button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        outline: none;
        border-style: none;
    }

    .wc-stripe-inline-form input:-webkit-autofill {
        -webkit-text-fill-color: #fce883;
        transition: background-color 100000000s;
        -webkit-animation: 1ms void-animation-out;
    }

    .wc-stripe-inline-form .StripeElement--webkit-autofill {
        background: transparent !important;
    }

    .wc-stripe-inline-form .StripeElement {
        width: 100%;
        padding: 11px 15px 11px 0;
        box-shadow: none;
    }

    .wc-stripe-inline-form input,
    .wc-stripe-inline-form .input {
        width: 100%;
        padding: 11px 15px 11px 0;
        background-color: transparent;
        -webkit-animation: 1ms void-animation-out;
        box-shadow: none;
        border: none;
        color: #819efc;
    }

    .wc-stripe-inline-form input:focus {
        color: #819efc;
    }

    .wc-stripe-inline-form input::-webkit-input-placeholder {
        color: #87bbfd;
    }

    .wc-stripe-inline-form input::-moz-placeholder {
        color: #87bbfd;
    }

    .wc-stripe-inline-form input:-ms-input-placeholder {
        color: #87bbfd;
    }

    .wc-stripe-inline-form button {
        display: block;
        width: calc(100% - 30px);
        height: 40px;
        margin: 40px 15px 0;
        background-color: #f6a4eb;
        box-shadow: 0 6px 9px rgba(50, 50, 93, 0.06), 0 2px 5px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 #ffb9f6;
        border-radius: 4px;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .wc-stripe-inline-form button:active {
        background-color: #d782d9;
        box-shadow: 0 6px 9px rgba(50, 50, 93, 0.06), 0 2px 5px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 #e298d8;
    }

    #stripe-postal-code:focus {
        background: transparent;
    }

    .wc-stripe-inline-form .error svg .base {
        fill: #fff;
    }

    .wc-stripe-inline-form .error svg .glyph {
        fill: #6772e5;
    }

    .wc-stripe-inline-form .error .message {
        color: #fff;
    }

    .wc-stripe-inline-form .success .icon .border {
        stroke: #87bbfd;
    }

    .wc-stripe-inline-form .success .icon .checkmark {
        stroke: #fff;
    }

    .wc-stripe-inline-form .success .title {
        color: #fff;
    }

    .wc-stripe-inline-form .success .message {
        color: #9cdbff;
    }

    .wc-stripe-inline-form .success .reset path {
        fill: #fff;
    }

    .stripe-small .wc-stripe-inline-form .row {
        flex-wrap: wrap;
    }

    @media screen and (max-width: 490px) {
        .wc-stripe-inline-form .row {
            flex-wrap: wrap;
        }

        .wc-stripe-inline-form label {
            width: 100%;
            display: none;
        }
    }
</style>