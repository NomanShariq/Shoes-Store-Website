<?php
/**
 * @version 3.0.0
 * @var WC_Payment_Gateway_Stripe_CC $gateway
 */
?>
<div class="wc-stripe-round-form">
    <div class="fieldset">
        <div id="stripe-card-number" class="field empty"></div>
        <div id="stripe-exp" class="field empty third-width"></div>
        <div id="stripe-cvv" class="field empty third-width"></div>
		<?php if ( $gateway->postal_enabled() ): ?>
            <input id="stripe-postal-code" class="field empty third-width"
                   placeholder="94107" value="<?php echo esc_attr( WC()->checkout()->get_value( 'billing_postcode' ) ) ?>">
		<?php endif; ?>
    </div>
</div>
<style type="text/css">
    .wc-stripe-round-form .StripeElement {
        box-shadow: none !important;
    }

    .wc-stripe-round-form {
        padding: 10px 0;
        background-color: transparent;
    }

    #stripe-postal-code {
        line-height: 0;
    }

    .wc-stripe-round-form * {
        font-family: Quicksand, Open Sans, Segoe UI, sans-serif;
        font-size: 16px;
        font-weight: 600;
    }

    .wc-stripe-round-form .fieldset {
        margin: 15px;
        padding: 0;
        border-style: none;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-flow: row wrap;
        flex-flow: row wrap;
        -ms-flex-pack: justify;
    }

    .wc-stripe_cc-container .wc-stripe-round-form .field.StripeElement,
    .wc-stripe-round-form .field {
        position: relative;
        padding: 10px 20px 11px;
        background-color: #7488aa;
        border-radius: 20px;
        width: 100%;
    }

    .stripe-small .wc-stripe-round-form .field {
        width: 100% !important;
    }

    .wc-stripe-round-form .field:nth-child(3) {
        margin-left: 5px;
        margin-right: 5px;
    }

    .stripe-small .wc-stripe-round-form .field:nth-child(n+3) {
        margin-left: 0px;
    }

    .wc-stripe-round-form .field.half-width {
        width: calc(50% - (5px / 2));
    }

    .wc-stripe-round-form .field.third-width {
        width: calc(33% - (5px / 3));
    }

    .wc-stripe-round-form .field + .field {
        margin-top: 10px;
    }

    .wc-stripe-round-form .field.focused, .wc-stripe-round-form .field:focus {
        color: #424770;
        background-color: #f6f9fc;
    }

    .wc-stripe-round-form .field.invalid {
        background-color: #fa755a;
    }

    .wc-stripe-round-form .field.invalid.focused {
        background-color: #f6f9fc;
    }

    .wc-stripe-round-form .field.focused::-webkit-input-placeholder,
    .wc-stripe-round-form .field:focus::-webkit-input-placeholder {
        color: #cfd7df;
    }

    .wc-stripe-round-form .field.focused::-moz-placeholder,
    .wc-stripe-round-form .field:focus::-moz-placeholder {
        color: #cfd7df;
    }

    .wc-stripe-round-form .field.focused:-ms-input-placeholder,
    .wc-stripe-round-form .field:focus:-ms-input-placeholder {
        color: #cfd7df;
    }

    .wc-stripe-round-form input, .wc-stripe-round-form button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        outline: none;
        border-style: none;
    }

    .wc-stripe-round-form input {
        color: #fff;
    }

    .wc-stripe-round-form input::-webkit-input-placeholder {
        color: #9bacc8;
    }

    .wc-stripe-round-form input::-moz-placeholder {
        color: #9bacc8;
    }

    .wc-stripe-round-form input:-ms-input-placeholder {
        color: #9bacc8;
    }

    .wc-stripe-round-form button {
        display: block;
        width: calc(100% - 30px);
        height: 40px;
        margin: 0 15px;
        background-color: #fcd669;
        border-radius: 20px;
        color: #525f7f;
        font-weight: 600;
        text-transform: uppercase;
        cursor: pointer;
    }

    .wc-stripe-round-form button:active {
        background-color: #f5be58;
    }

    .wc-stripe-round-form .error svg .base {
        fill: #fa755a;
    }

    .wc-stripe-round-form .error svg .glyph {
        fill: #fff;
    }

    .wc-stripe-round-form .error .message {
        color: #fff;
    }

    .wc-stripe-round-form .success .icon .border {
        stroke: #fcd669;
    }

    .wc-stripe-round-form .success .icon .checkmark {
        stroke: #fff;
    }

    .wc-stripe-round-form .success .title {
        color: #fff;
    }

    .wc-stripe-round-form .success .message {
        color: #9cabc8;
    }

    .wc-stripe-round-form .success .reset path {
        fill: #fff;
    }
</style>