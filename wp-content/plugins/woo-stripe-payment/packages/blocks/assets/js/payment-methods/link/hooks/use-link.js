import {useEffect, useState, useRef} from '@wordpress/element';
import {useStripe, useElements} from "@stripe/react-stripe-js";
import {
    toCartAddress as mapToCartAddress,
    versionCompare,
    ensureSuccessResponse,
    ensureErrorResponse,
    getBillingDetailsFromAddress,
    DEFAULT_BILLING_ADDRESS,
    DEFAULT_SHIPPING_ADDRESS, getSettings
} from '../../util';

const generalData = getSettings('stripeGeneralData');

const toCartAddress = mapToCartAddress();

export const useLink = (
    {
        email,
        eventRegistration,
        onClick,
        onSubmit,
        activePaymentMethod,
        responseTypes,
        ...props
    }) => {
    const [link, setLink] = useState();
    const stripe = useStripe();
    const elements = useElements();
    const currentData = useRef();
    const linkData = useRef();
    const {onPaymentProcessing} = eventRegistration;
    useEffect(() => {
        currentData.current = {onClick, onSubmit, email}
    });

    useEffect(() => {
        if (stripe && elements && !link) {
            setLink(stripe?.linkAutofillModal(elements));
        }
    }, [
        stripe,
        elements,
        link
    ]);

    useEffect(() => {
        if (link) {
            link.launch({email});
        }
    }, [link, email]);

    useEffect(() => {
        if (link) {
            link.on('autofill', event => {
                linkData.current = event;
                currentData.current.onSubmit();

            });
            link.on('authenticated', event => {
                currentData.current.onClick();
            })
        }
    }, [link]);

    useEffect(() => {
        const unsubscribe = onPaymentProcessing(async () => {
            if (activePaymentMethod !== 'stripe_link_checkout') {
                return null;
            }
            const response = {meta: {}};
            const {shippingAddress = null, billingAddress = null} = linkData.current.value;
            const version = generalData('blocksVersion');
            let billing_details;
            if (billingAddress) {
                const address = toCartAddress({...billingAddress.address, recipient: billingAddress.name});
                billing_details = getBillingDetailsFromAddress(address);
                if (versionCompare(version, '9.5.0', '<')) {
                    response.meta.billingData = address;
                } else {
                    response.meta.billingAddress = {
                        ...DEFAULT_BILLING_ADDRESS,
                        ...address,
                        email: currentData.current.email
                    };
                }
            }
            if (shippingAddress) {
                const address = toCartAddress({...shippingAddress.address, recipient: shippingAddress.name});
                if (versionCompare(version, '9.5.0', '<')) {
                    response.meta.shippingData = {address};
                } else {
                    response.meta.shippingAddress = {
                        ...DEFAULT_SHIPPING_ADDRESS,
                        ...address
                    }
                }
            }
            // update the payment intent
            try {
                const result = await stripe.createPaymentMethod({
                    elements,
                    params: {
                        billing_details
                    }
                });
                if (result.error) {
                    throw result.error;
                }
                response.meta.paymentMethodData = {
                    stripe_cc_token_key: result.paymentMethod.id,
                    stripe_cc_save_source_key: false,
                }
                return ensureSuccessResponse(responseTypes, response);
            } catch (error) {
                console.log(error);
                return ensureErrorResponse(responseTypes, error);
            }
        });

        return () => unsubscribe();
    }, [
        onPaymentProcessing,
        stripe,
        elements,
        activePaymentMethod
    ]);

    return link;
}