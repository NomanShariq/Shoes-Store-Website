import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {useStripe, useElements} from "@stripe/react-stripe-js";
import {ensureErrorResponse, ensureSuccessResponse, getBillingDetailsFromAddress, StripeError, isNextActionRequired, getRoute, handleCardAction} from '../util';

export const useDeferredPaymentIntent = (
    {
        billingData,
        eventRegistration,
        responseTypes,
        name,
        shouldSavePayment,
        noticeContexts
    }
) => {
    const {onPaymentProcessing, onCheckoutAfterProcessingWithSuccess} = eventRegistration;
    const currentData = useRef({billingData});
    const paymentMethodData = useRef({});
    const stripe = useStripe();
    const elements = useElements();

    const getSuccessResponse = useCallback((paymentMethod, shouldSavePayment) => {
        const response = {
            meta: {
                paymentMethodData: {
                    [`${name}_token_key`]: paymentMethod,
                    [`${name}_save_source_key`]: shouldSavePayment,
                    ...paymentMethodData.current
                }
            }
        }
        return response;
    }, []);

    const addPaymentMethodData = useCallback((data) => {
        paymentMethodData.current = {...paymentMethodData.current, ...data};
    }, []);

    const createPaymentMethod = useCallback(async () => {
        const {billingData} = currentData.current;
        return await stripe.createPaymentMethod({
            elements,
            params: {
                billing_details: getBillingDetailsFromAddress(billingData)
            }
        });
    }, [stripe, elements]);

    const confirmPayment = useCallback(async () => {
        const {billingData} = currentData.current;
        return await stripe.confirmPayment({
            elements,
            confirmParams: {
                payment_method_data: {
                    billing_details: getBillingDetailsFromAddress(billingData)
                }
            },
            redirect: 'if_required'
        });
    }, [stripe, elements]);

    useEffect(() => {
        currentData.current.billingData = billingData;
    });

    useEffect(() => {
        const unsubscribe = onPaymentProcessing(async () => {

            try {
                let paymentMethod = null;
                //let result = await stripe.retrievePaymentIntent(clientSecret);
                let result = null;
                if (result?.paymentIntent?.status === 'requires_action') {
                    paymentMethod = result.paymentIntent.payment_method;
                    await confirmPayment();
                } else {
                    result = await createPaymentMethod();
                    if (result.error) {
                        throw new StripeError(result.error);
                    }
                    paymentMethod = result.paymentMethod.id;
                    currentData.current.paymentMethod = paymentMethod;
                }
                return ensureSuccessResponse(responseTypes, getSuccessResponse(paymentMethod, shouldSavePayment));
            } catch (error) {
                return ensureErrorResponse(responseTypes, error, {messageContext: noticeContexts.PAYMENTS});
            }
        });
        return () => unsubscribe();
    }, [
        onPaymentProcessing,
        createPaymentMethod,
        confirmPayment,
        shouldSavePayment
    ]);

    useEffect(() => {
        const unsubscribe = onCheckoutAfterProcessingWithSuccess(async ({redirectUrl}) => {
            return await handleCardAction({
                redirectUrl,
                responseTypes,
                name,
                savePaymentMethod: shouldSavePayment,
                data: {
                    [`${name}_token_key`]: currentData.current.paymentMethod
                }
            })
        });
        return () => unsubscribe();
    }, [
        onCheckoutAfterProcessingWithSuccess,
        confirmPayment,
        shouldSavePayment,
        name
    ]);

    return {
        createPaymentMethod,
        addPaymentMethodData
    }
}