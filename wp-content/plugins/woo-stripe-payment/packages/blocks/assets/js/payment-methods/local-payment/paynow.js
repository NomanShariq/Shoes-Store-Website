import {useEffect, useRef} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {PaymentMethod, PaymentMethodLabel} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";
import {ensureErrorResponse, getBillingDetailsFromAddress, getSettings, initStripe as loadStripe, isNextActionRequired, StripeError} from "../util";
import {Elements, useStripe} from "@stripe/react-stripe-js";
import {__, sprintf} from '@wordpress/i18n';

const getData = getSettings('stripe_paynow_data');

const PayNowPaymentMethod = (props) => {
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
                try {
                    const args = isNextActionRequired(redirectUrl);
                    if (args) {
                        let {client_secret, return_url, ...order} = args;
                        let result = await stripe.confirmPayNowPayment(client_secret, {
                            payment_method: {
                                billing_details: getBillingDetailsFromAddress(billingAddress),
                            },
                            return_url
                        });
                        if (result.error) {
                            throw new StripeError(result.error);
                        }
                        if (result.paymentIntent.status === 'requires_action') {
                            throw __('PayNow payment cancelled.', 'woo-stripe-payment');
                        }
                        if (result.paymentIntent.status === 'requires_payment_method') {
                            throw __('PayNow payment expired. Please try again.', 'woo-stripe-payment');
                        }
                        window.location = decodeURI(order.order_received_url);
                    }
                } catch (error) {
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
            <li dangerouslySetInnerHTML={{__html: sprintf(__('Click %1$s and you will be shown a QR code.', 'woo-stripe-payment'), '<b>' + getData('placeOrderButtonLabel') + '</b>')}}/>
            <li>
                {sprintf(__('Scan the QR code using an app from participating banks and participating non-bank financial institutions.', 'woo-stripe-payment'))}
            </li>
            <li>
                {sprintf(__('The authentication process may take several moments. Once confirmed, you will be redirected to the order received page.', 'woo-stripe-payment'))}
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
        ariaLabel: 'PayNow',
        placeOrderButtonLabel: getData('placeOrderButtonLabel'),
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod
            content={PayNowPaymentMethod}
            getData={getData}/>,
        edit: <PaymentMethod content={PayNowPaymentMethod} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}