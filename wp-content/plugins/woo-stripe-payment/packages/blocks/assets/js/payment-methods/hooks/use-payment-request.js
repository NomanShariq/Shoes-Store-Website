import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {getIntermediateAddress, getRoute} from '../util';
import isShallowEqual from '@wordpress/is-shallow-equal';
import {
    getDisplayItems,
    getShippingOptions,
    getSelectedShippingOption,
    isFieldRequired,
    toCartAddress as mapToCartAddress
} from "../util";
import apiFetch from "@wordpress/api-fetch";

const toCartAddress = mapToCartAddress();

export const usePaymentRequest = (
    {
        getData,
        onClose,
        stripe,
        billing,
        shippingData,
        setPaymentMethod,
        exportedValues,
        canPay
    }) => {

    const {shippingAddress, needsShipping, shippingRates} = shippingData;
    const {billingData, cartTotalItems, currency, cartTotal} = billing;
    const [paymentRequest, setPaymentRequest] = useState(null);
    const paymentRequestOptions = useRef({});
    const currentShipping = useRef(shippingData)
    const currentBilling = useRef(billing);

    useEffect(() => {
        currentShipping.current = shippingData;
        currentBilling.current = billing;
    }, [shippingData, billing]);

    useEffect(() => {
        if (stripe) {
            const options = {
                country: getData('countryCode'),
                currency: currency?.code.toLowerCase(),
                total: {
                    amount: cartTotal.value,
                    label: cartTotal.label,
                    pending: true
                },
                requestPayerName: true,
                requestPayerEmail: isFieldRequired('email', billingData.country),
                requestPayerPhone: isFieldRequired(needsShipping ? 'shipping-phone' : 'phone', billingData.country),
                requestShipping: needsShipping,
                displayItems: getDisplayItems(cartTotalItems, currency)
            }
            if (options.requestShipping) {
                options.shippingOptions = getShippingOptions(shippingRates);
            }
            paymentRequestOptions.current = options;
            const paymentRequest = stripe.paymentRequest(paymentRequestOptions.current);
            paymentRequest.canMakePayment().then(result => {
                if (canPay(result)) {
                    setPaymentRequest(paymentRequest);
                } else {
                    setPaymentRequest(null);
                }
            });
        }
    }, [
        stripe,
        cartTotal.value,
        billingData.country,
        shippingRates,
        cartTotalItems,
        currency.code
    ]);

    const onShippingAddressChange = useCallback(event => {
        const shipping = currentShipping.current;
        const {shippingAddress} = event;
        const intermediateAddress = toCartAddress(shippingAddress);
        apiFetch({
            method: 'POST',
            url: getRoute('shipping-address'),
            data: {
                address: intermediateAddress,
                payment_method: getData('name'),
                page_id: 'checkout'
            }
        }).then(response => {
            event.updateWith(response.data.newData);
            shipping.setShippingAddress({...shipping.shippingAddress, ...intermediateAddress});
        }).catch(error => {
            console.log(error);
        })
    }, []);

    const onShippingOptionChange = useCallback(event => {
        const {shippingOption} = event;
        const shipping = currentShipping.current;

        apiFetch({
            method: 'POST',
            url: getRoute('shipping-method'),
            data: {
                shipping_method: shippingOption.id,
                payment_method: getData('name'),
                page_id: null
            }
        }).then(response => {
            event.updateWith(response.data.newData);
            shipping.setSelectedRates(...getSelectedShippingOption(shippingOption.id))
        }).catch(error => {
            console.log(error);
        })
    }, []);

    const onPaymentMethodReceived = useCallback((paymentResponse) => {
        const {paymentMethod, payerName = null, payerEmail = null, payerPhone = null} = paymentResponse;
        // set address data
        let billingData = {payerName, payerEmail, payerPhone};
        if (paymentMethod?.billing_details.address) {
            billingData = toCartAddress(paymentMethod.billing_details.address, billingData);
        }
        exportedValues.billingData = billingData;

        if (paymentResponse.shippingAddress) {
            exportedValues.shippingAddress = toCartAddress(paymentResponse.shippingAddress, {payerPhone});
        }

        // set payment method
        setPaymentMethod(paymentMethod.id);
        paymentResponse.complete("success");
    }, []);

    useEffect(() => {
        if (paymentRequest) {
            if (paymentRequestOptions.current.requestShipping) {
                paymentRequest.on('shippingaddresschange', onShippingAddressChange);
                paymentRequest.on('shippingoptionchange', onShippingOptionChange);
            }
            paymentRequest.on('cancel', onClose);
            paymentRequest.on('paymentmethod', onPaymentMethodReceived);
        }
    }, [
        onClose,
        paymentRequest,
        onShippingAddressChange,
        onPaymentMethodReceived
    ]);

    return {paymentRequest};
}