import $ from 'jquery';
import wc_stripe from '@paymentplugins/wc-stripe';

const {credit_card: creditCard} = wc_stripe;

const addLinkIcon = () => {
    $('#billing_email').parent()?.addClass('stripe-link-icon-container');
    $('#billing_email').after($(wcStripeLinkParams.linkIcon));
}

$(() => {
    if (wcStripeLinkParams === 'undefined' || !wcStripeLinkParams?.elementOptions?.mode) {
        return false;
    }
    try {
        const stripe = creditCard.stripe;
        const link = stripe.linkAutofillModal(creditCard.elements);

        $(document.body).on('keyup', '[name="billing_email"]', (e) => {
            link.launch({email: e.currentTarget.value});
        });

        if (wcStripeLinkParams.launchLink) {
            link.launch({email: $('[name="billing_email"]').val()});
        }

        if (wcStripeLinkParams.linkIconEnabled) {
            addLinkIcon();
        }

        link.on('autofill', (event) => {
            const {shippingAddress = null, billingAddress} = event.value;
            // populate the address fields
            if (shippingAddress) {
                const address = {name: shippingAddress.name, ...shippingAddress.address};
                creditCard.populate_shipping_fields(address);
            }
            if (billingAddress) {
                const address = {name: billingAddress.name, ...billingAddress.address};
                creditCard.populate_billing_fields(address);
            }
            creditCard.fields.toFormFields();
            creditCard.set_payment_method(creditCard.gateway_id);
            creditCard.show_new_payment_method();
            creditCard.hide_save_card();
            if (shippingAddress) {
                creditCard.maybe_set_ship_to_different();
            }
            $('[name="terms"]').prop('checked', true);
            if (!creditCard.fields.required('billing_phone') || !creditCard.fields.isEmpty('billing_phone')) {
                creditCard.get_form().trigger('submit');
            }
        });
    } catch (error) {
        console.log(error);
    }
});