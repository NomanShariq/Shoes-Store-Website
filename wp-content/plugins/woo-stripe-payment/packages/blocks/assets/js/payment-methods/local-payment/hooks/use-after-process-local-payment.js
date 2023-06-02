import {useEffect, useRef} from '@wordpress/element';
import {useStripe} from "@stripe/react-stripe-js";
import {ensureErrorResponse, getBillingDetailsFromAddress, StripeError} from "../../util";

export const useAfterProcessLocalPayment = (
    {
        getData,
        billingData,
        eventRegistration,
        responseTypes,
        activePaymentMethod,
        confirmationMethod,
        getPaymentMethodArgs = () => ({})
    }
) => {
    const stripe = useStripe();
    const {onCheckoutAfterProcessingWithSuccess, onCheckoutAfterProcessingWithError} = eventRegistration;
    const currentBillingData = useRef(billingData);
    const currentPaymentMethodArgs = useRef(getPaymentMethodArgs);
    useEffect(() => {
        currentBillingData.current = billingData;
    }, [billingData]);

    useEffect(() => {
        currentPaymentMethodArgs.current = getPaymentMethodArgs;
    }, [getPaymentMethodArgs]);

    useEffect(() => {
        const unsubscribeAfterProcessingWithSuccess = onCheckoutAfterProcessingWithSuccess(async ({redirectUrl}) => {
            if (getData('name') === activePaymentMethod) {
                try {
                    let match = redirectUrl.match(/#response=(.+)/);
                    if (match) {
                        let {client_secret, return_url, ...order} = JSON.parse(window.atob(decodeURIComponent(match[1])));
                        let result = await stripe[confirmationMethod](client_secret, {
                            payment_method: {
                                billing_details: getBillingDetailsFromAddress(currentBillingData.current),
                                ...currentPaymentMethodArgs.current(currentBillingData.current)
                            },
                            return_url
                        });
                        if (result.error) {
                            throw new StripeError(result.error);
                        }
                        window.location = decodeURI(order.order_received_url);
                    }
                } catch (e) {
                    console.log(e);
                    return ensureErrorResponse(responseTypes, e.error);
                }
            }
        })
        return () => unsubscribeAfterProcessingWithSuccess();
    }, [
        stripe,
        onCheckoutAfterProcessingWithSuccess,
        onCheckoutAfterProcessingWithError
    ]);
}