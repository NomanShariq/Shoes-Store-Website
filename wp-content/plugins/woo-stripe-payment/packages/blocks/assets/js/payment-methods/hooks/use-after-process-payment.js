import {useEffect} from '@wordpress/element'
import {useStripe} from "@stripe/react-stripe-js";
import {handleCardAction} from "../util";
import {useProcessCheckoutError} from "./use-process-checkout-error";

export const useAfterProcessingPayment = (
    {
        getData,
        eventRegistration,
        responseTypes,
        activePaymentMethod,
        shouldSavePayment = false,
        messageContext = null
    }) => {
    const stripe = useStripe();
    const {onCheckoutAfterProcessingWithSuccess, onCheckoutAfterProcessingWithError} = eventRegistration;
    useProcessCheckoutError({
        responseTypes,
        subscriber: onCheckoutAfterProcessingWithError,
        messageContext
    });
    useEffect(() => {
        let unsubscribeAfterProcessingWithSuccess = onCheckoutAfterProcessingWithSuccess(async ({redirectUrl}) => {
            if (getData('name') === activePaymentMethod) {
                //check if response is in redirect. If so, open modal
                return await handleCardAction({
                    redirectUrl,
                    responseTypes,
                    name: activePaymentMethod,
                    savePaymentMethod: shouldSavePayment
                });
            }
            return null;
        })
        return () => unsubscribeAfterProcessingWithSuccess()
    }, [
        stripe,
        responseTypes,
        onCheckoutAfterProcessingWithSuccess,
        activePaymentMethod,
        shouldSavePayment
    ]);
}