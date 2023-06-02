import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {
    getDefaultSourceArgs,
    ensureSuccessResponse,
    ensureErrorResponse,
    StripeError
} from "../../util";
import {useStripe, useElements} from "@stripe/react-stripe-js";
import {__} from '@wordpress/i18n';

export const useCreateSource = (
    {
        getData,
        billing,
        shippingAddress,
        onPaymentProcessing,
        responseTypes,
        getSourceArgs = false,
        element = false
    }) => {
    const [source, setSource] = useState(false);
    const [isValid, setIsValid] = useState(false);
    const currentValues = useRef({
        billing,
        shippingAddress,
    });
    const stripe = useStripe();
    const elements = useElements();
    useEffect(() => {
        currentValues.current = {
            billing,
            shippingAddress
        }
    });

    const getSourceArgsInternal = useCallback(() => {
        const {billing} = currentValues.current;
        const {cartTotal, currency, billingData} = billing;
        let args = getDefaultSourceArgs({
            type: getData('paymentType'),
            amount: cartTotal.value,
            billingData,
            currency: currency.code,
            returnUrl: getData('returnUrl')
        });
        if (getSourceArgs) {
            args = getSourceArgs(args, {billingData});
        }
        return args;
    }, []);

    const getSuccessData = useCallback((sourceId) => {
        return {
            meta: {
                paymentMethodData: {
                    [`${getData('name')}_token_key`]: sourceId
                }
            }
        }
    }, []);

    useEffect(() => {
        const unsubscribe = onPaymentProcessing(async () => {
            if (source) {
                return ensureSuccessResponse(responseTypes, getSuccessData(source.id));
            }
            // create the source
            try {
                let result;
                if (element) {
                    // validate the element
                    if (!isValid) {
                        throw __('Please enter your payment info before proceeding.', 'woo-stripe-payment');
                    }
                    result = await stripe.createSource(elements.getElement(element), getSourceArgsInternal());
                } else {
                    result = await stripe.createSource(getSourceArgsInternal());
                }
                if (result.error) {
                    throw new StripeError(result.error);
                }
                setSource(result.source);
                return ensureSuccessResponse(responseTypes, getSuccessData(result.source.id));
            } catch (err) {
                console.log(err);
                return ensureErrorResponse(responseTypes, err.error || err);
            }
        });
        return () => unsubscribe();
    }, [
        source,
        onPaymentProcessing,
        stripe,
        responseTypes,
        element,
        isValid,
        setIsValid
    ]);
    return {setIsValid};
}