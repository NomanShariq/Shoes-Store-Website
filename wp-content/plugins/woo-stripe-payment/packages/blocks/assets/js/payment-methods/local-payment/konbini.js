import {useEffect, useRef} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {ensureErrorResponse, getBillingDetailsFromAddress, getSettings, initStripe as loadStripe, isNextActionRequired, StripeError} from "../util";
import {PaymentMethod, PaymentMethodLabel} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";
import {Elements, useStripe} from "@stripe/react-stripe-js";
import {sprintf, __} from '@wordpress/i18n';

const getData = getSettings('stripe_konbini_data');

const KonbiniPaymentMethod = (props) => {
    return (
        <Elements stripe={loadStripe} options={getData('elementOptions')}>
            <PaymentMethodContent {...props}/>
        </Elements>
    )
}

const PaymentMethodContent = (props) => {
    const {eventRegistration, billing, activePaymentMethod} = props;
    const {emitResponse: {responseTypes}} = props;
    const {
        onCheckoutAfterProcessingWithSuccess
    } = eventRegistration;

    const usePhoneAsConfirmation = useRef(true);
    const currentData = useRef({billing, activePaymentMethod});
    const stripe = useStripe();

    useEffect(() => {
        currentData.current = {billing, activePaymentMethod};
    }, [
        billing,
        activePaymentMethod
    ]);

    useEffect(() => {
        const unsubscribe = onCheckoutAfterProcessingWithSuccess(async ({redirectUrl}) => {
            if (activePaymentMethod === getData('name')) {
                const {billingAddress} = currentData.current.billing;
                const usePhone = usePhoneAsConfirmation.current;
                try {
                    const args = isNextActionRequired(redirectUrl);
                    if (args) {
                        let {client_secret, return_url, ...order} = args;
                        let result = await stripe.confirmKonbiniPayment(client_secret, {
                            payment_method: {
                                billing_details: getBillingDetailsFromAddress(billingAddress),
                            },
                            payment_method_options: {
                                konbini: {
                                    confirmation_number: usePhone ? order.billing_phone : order.confirmation_number
                                }
                            },
                            return_url
                        });
                        if (result.error) {
                            throw new StripeError(result.error);
                        }
                        window.location = decodeURI(order.order_received_url);
                    }
                } catch (error) {
                    if (error?.error?.code === 'payment_intent_konbini_rejected_confirmation_number') {
                        usePhoneAsConfirmation.current = false;
                    }
                    return ensureErrorResponse(responseTypes, error);
                }
            }
        });
        return unsubscribe;
    }, [
        stripe,
        activePaymentMethod,
        onCheckoutAfterProcessingWithSuccess
    ]);

    return <Instructions/>
}

const Instructions = () => {
    return (
        <ol>
            <li dangerouslySetInnerHTML={{__html: sprintf(__('Click %1$s and you will be presented with your Konbini payment code and confirmation number.', 'woo-stripe-payment'), '<b>' + getData('placeOrderButtonLabel') + '</b>')}}/>
            <li>
                {sprintf(__('Your order email will contain a link to your Konbini voucher which has your payment code and confirmation number.', 'woo-stripe-payment'))}
            </li>
            <li>
                {sprintf(__('At the convenience store, provide the payment code and confirmation number to the payment machine or cashier.', 'woo-stripe-payment'))}
            </li>
            <li>
                {sprintf(__('After the payment is complete, keep the receipt for your records.', 'woo-stripe-payment'))}
            </li>
        </ol>
    )
}

if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'Konbini',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={KonbiniPaymentMethod}
            getData={getData}/>,
        edit: <PaymentMethod content={KonbiniPaymentMethod} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}