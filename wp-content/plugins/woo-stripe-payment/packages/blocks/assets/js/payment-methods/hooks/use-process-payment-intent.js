import {useEffect, useState, useCallback, useRef} from '@wordpress/element';
import {useStripe} from '@stripe/react-stripe-js';
import {
    getSettings,
    versionCompare,
    ensureSuccessResponse,
    ensureErrorResponse,
    getBillingDetailsFromAddress,
    StripeError,
    DEFAULT_BILLING_ADDRESS,
    DEFAULT_SHIPPING_ADDRESS
} from '../util';

const generalData = getSettings('stripeGeneralData');

export const useProcessPaymentIntent = (
    {
        getData,
        billing,
        shippingData,
        onPaymentProcessing,
        emitResponse,
        error,
        onSubmit,
        activePaymentMethod,
        paymentType = 'card',
        setupIntent = null,
        removeSetupIntent = null,
        shouldSavePayment = false,
        exportedValues = {},
        getPaymentMethodArgs = () => ({})
    }) => {
    const {billingData} = billing;
    const {shippingAddress} = shippingData;
    const {responseTypes} = emitResponse;
    const [paymentMethod, setPaymentMethod] = useState(null);
    const stripe = useStripe();
    const currentPaymentMethodArgs = useRef(getPaymentMethodArgs);
    const paymentMethodData = useRef({});
    useEffect(() => {
        currentPaymentMethodArgs.current = getPaymentMethodArgs;
    }, [getPaymentMethodArgs]);

    const addPaymentMethodData = useCallback((data) => {
        paymentMethodData.current = {...paymentMethodData.current, ...data};
    }, []);

    const getCreatePaymentMethodArgs = useCallback(() => {
        const args = {
            type: paymentType,
            billing_details: getBillingDetailsFromAddress(exportedValues?.billingData ? exportedValues.billingData : billingData)
        }
        return {...args, ...currentPaymentMethodArgs.current()};
    }, [billingData, paymentType, getPaymentMethodArgs]);

    const getSuccessResponse = useCallback((paymentMethodId, shouldSavePayment) => {
        const response = {
            meta: {
                paymentMethodData: {
                    [`${getData('name')}_token_key`]: paymentMethodId,
                    [`${getData('name')}_save_source_key`]: shouldSavePayment,
                    ...paymentMethodData.current
                }
            }
        }
        const version = generalData('blocksVersion');
        if (exportedValues?.billingData) {
            if (versionCompare(version, '9.5.0', '<')) {
                response.meta.billingData = {
                    ...DEFAULT_BILLING_ADDRESS,
                    ...exportedValues.billingData
                };
            } else {
                response.meta.billingAddress = {
                    ...DEFAULT_BILLING_ADDRESS,
                    ...exportedValues.billingData
                };
            }
        }
        if (exportedValues?.shippingAddress) {
            if (versionCompare(version, '9.5.0', '<')) {
                response.meta.shippingData = {address: exportedValues.shippingAddress};
            } else {
                response.meta.shippingAddress = {
                    ...DEFAULT_SHIPPING_ADDRESS, ...exportedValues.shippingAddress
                }
            }
        }
        return response;
    }, [billingData, shippingAddress]);

    useEffect(() => {
        if (paymentMethod && typeof paymentMethod === 'string') {
            onSubmit();
        }
    }, [paymentMethod, onSubmit]);

    useEffect(() => {
        const unsubscribeProcessingPayment = onPaymentProcessing(async () => {
            if (activePaymentMethod !== getData('name')) {
                return null;
            }
            let [result, paymentMethodId] = [null, null];
            try {
                if (error) {
                    throw new StripeError(error);
                }
                if (setupIntent) {
                    result = await stripe.confirmCardSetup(setupIntent.client_secret, {
                        payment_method: getCreatePaymentMethodArgs()
                    });
                    if (result.error) {
                        throw new StripeError(result.error);
                    }
                    paymentMethodId = result.setupIntent.payment_method;
                    removeSetupIntent();
                } else {
                    // payment method has already been created.
                    if (paymentMethod) {
                        paymentMethodId = paymentMethod;
                    } else {
                        //create the payment method
                        result = await stripe.createPaymentMethod(getCreatePaymentMethodArgs());
                        if (result.error) {
                            throw new StripeError(result.error);
                        }
                        paymentMethodId = result.paymentMethod.id;
                    }
                }
                return ensureSuccessResponse(responseTypes, getSuccessResponse(paymentMethodId, shouldSavePayment));
            } catch (e) {
                console.log(e);
                setPaymentMethod(null);
                return ensureErrorResponse(responseTypes, e.error);
            }

        });
        return () => unsubscribeProcessingPayment();
    }, [
        paymentMethod,
        billingData,
        onPaymentProcessing,
        stripe,
        setupIntent,
        activePaymentMethod,
        shouldSavePayment
    ]);
    return {
        setPaymentMethod,
        getCreatePaymentMethodArgs,
        addPaymentMethodData
    };
}