import {useState, useEffect, useCallback, useMemo, useRef} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
    getRoute,
    getErrorMessage,
    getSelectedShippingOption,
    getBillingDetailsFromAddress,
    isAddressValid,
    isEmpty,
    StripeError
} from "../../util";
import {useStripe} from "@stripe/react-stripe-js";
import {toCartAddress} from "../util";

export const usePaymentsClient = (
    {
        merchantInfo,
        buildPaymentRequest,
        billing,
        shippingData,
        eventRegistration,
        canMakePayment,
        setErrorMessage,
        setPaymentMethod,
        exportedValues,
        onClick,
        onClose,
        getData
    }) => {
    const {environment} = getData();
    const [paymentsClient, setPaymentsClient] = useState();
    const [button, setButton] = useState(null);
    const currentBilling = useRef(billing);
    const currentShipping = useRef(shippingData);
    const {needsShipping} = shippingData;
    const stripe = useStripe();

    useEffect(() => {
        currentBilling.current = billing;
        currentShipping.current = shippingData;
    });

    const setAddressData = useCallback((paymentData) => {
        let billingAddress;
        if (paymentData?.paymentMethodData?.info?.billingAddress) {
            billingAddress = paymentData.paymentMethodData.info.billingAddress;
            if (isAddressValid(currentBilling.current.billingData, ['phone', 'email']) && isEmpty(currentBilling.current.billingData?.phone)) {
                billingAddress = {phoneNumber: billingAddress.phoneNumber};
            }
            exportedValues.billingData = currentBilling.current.billingData = toCartAddress(billingAddress, {email: paymentData.email});
        }
        if (paymentData?.shippingAddress) {
            exportedValues.shippingAddress = toCartAddress({...paymentData.shippingAddress, phoneNumber: billingAddress?.phoneNumber});
        }
    }, []);

    const removeButton = useCallback((parentElement) => {
        while (parentElement.firstChild) {
            parentElement.removeChild(parentElement.firstChild);
        }
    }, [button]);
    const handleClick = useCallback(async () => {
        onClick();
        try {
            let paymentData = await paymentsClient.loadPaymentData(buildPaymentRequest());

            // set the address data so it can be used during the checkout process
            setAddressData(paymentData);

            const data = JSON.parse(paymentData.paymentMethodData.tokenizationData.token);

            let result = await stripe.createPaymentMethod({
                type: 'card',
                card: {token: data.id},
                billing_details: getBillingDetailsFromAddress(currentBilling.current.billingData)
            });

            if (result.error) {
                throw new StripeError(result.error);
            }

            setPaymentMethod(result.paymentMethod.id);
        } catch (err) {
            if (err?.statusCode === "CANCELED") {
                onClose();
            } else {
                console.log(getErrorMessage(err));
                setErrorMessage(getErrorMessage(err));
            }
        }
    }, [
        stripe,
        paymentsClient,
        onClick,
        buildPaymentRequest
    ]);

    const createButton = useCallback(async () => {
        try {
            if (paymentsClient && stripe) {
                await canMakePayment;
                const button = paymentsClient.createButton({
                    onClick: handleClick,
                    ...getData('buttonStyle')
                });
                if (getData('buttonShape') === 'rect') {
                    button.querySelector('button')?.classList?.remove('new_style');
                }
                setButton(button);
            }
        } catch (err) {
            console.log(err);
        }
    }, [
        stripe,
        paymentsClient,
        handleClick
    ]);

    const paymentOptions = useMemo(() => {
        let options = {
            environment,
            merchantInfo,
            paymentDataCallbacks: {
                onPaymentAuthorized: () => Promise.resolve({transactionState: "SUCCESS"})
            }
        }
        if (needsShipping) {
            options.paymentDataCallbacks.onPaymentDataChanged = (paymentData) => {
                const shipping = currentShipping.current;
                const {shippingAddress: address, shippingOptionData} = paymentData;
                const selectedRates = getSelectedShippingOption(shippingOptionData.id);
                const shipping_method = ['default', 'shipping_option_unselected'].includes(shippingOptionData.id) ? null : shippingOptionData.id;
                return new Promise((resolve, reject) => {
                    apiFetch({
                        method: 'POST',
                        url: getRoute('payment/data'),
                        data: {
                            address: toCartAddress(address),
                            shipping_method,
                            page_id: null
                        }
                    }).then(response => {
                        if (response.code) {
                            resolve(response.data.data);
                        } else {
                            resolve(response.data.paymentRequestUpdate);
                        }
                    }).catch(response => {
                        resolve(response.data);
                    }).finally(() => {
                        if (shipping_method && shipping_method !== 'shipping_option_unselected') {
                            shipping.setSelectedRates(...selectedRates);
                        }
                    });
                });
            }
        }
        return options;
    }, [needsShipping]);

    useEffect(() => {
        setPaymentsClient(new google.payments.api.PaymentsClient(paymentOptions));
    }, [paymentOptions]);

    useEffect(() => {
        createButton();
    }, [createButton])

    return {
        button,
        removeButton
    };
}