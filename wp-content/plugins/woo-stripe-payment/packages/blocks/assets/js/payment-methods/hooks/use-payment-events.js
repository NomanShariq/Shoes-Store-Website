import {useEffect, useCallback, useRef, useState} from '@wordpress/element';
import {hasShippingRates} from '../util';

export const usePaymentEvents = (
    {
        billing,
        shippingData,
        eventRegistration
    }) => {
    const {onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess} = eventRegistration;
    const currentBilling = useRef(billing);
    const currentShipping = useRef(shippingData);
    const [handler, setHandler] = useState(null);
    const [paymentEvents, setPaymentEvent] = useState({
        onShippingChanged: false
    });
    const addPaymentEvent = useCallback((name, handler, execute = false) => {
        if (execute) {
            setHandler({[name]: handler});
        } else {
            setPaymentEvent({...paymentEvents, [name]: handler});
        }
    }, [paymentEvents, setPaymentEvent]);
    const removePaymentEvent = useCallback((name) => {
        if (paymentEvents[name]) {
            delete paymentEvents[name];
            setPaymentEvent(paymentEvents);
        }
    }, [paymentEvents]);

    const onShippingChanged = useCallback(() => {
        const shipping = currentShipping.current;
        const billing = currentBilling.current;
        if (paymentEvents.onShippingChanged && !shipping.isSelectingRate && !shipping.shippingRatesLoading) {
            const handler = paymentEvents.onShippingChanged;
            let success = true;
            if (!hasShippingRates(shipping.shippingRates)) {
                success = false;
            }
            handler(success, {
                billing,
                shipping
            });
            removePaymentEvent('onShippingChanged');
        }
    }, [paymentEvents, removePaymentEvent]);

    useEffect(() => {
        currentBilling.current = billing;
        currentShipping.current = shippingData;
    });

    useEffect(() => {
        if (handler) {
            if (handler.onShippingChanged) {
                handler.onShippingChanged(true, {
                    billing: currentBilling.current,
                    shipping: currentShipping.current
                })
                setHandler(null);
            }
        }
    }, [handler]);

    useEffect(() => {
        const unsubscribeShippingRateSuccess = onShippingRateSuccess(onShippingChanged);
        const unsubscribeShippingRateSelectSuccess = onShippingRateSelectSuccess(onShippingChanged);
        const unsubscribeShippingRateFail = onShippingRateFail(({hasInvalidAddress, hasError}) => {
            if (paymentEvents.onShippingChanged) {
                const handler = paymentEvents.onShippingChanged;
                handler(false);
                removePaymentEvent('onShippingChanged');
            }
        });

        return () => {
            unsubscribeShippingRateSuccess();
            unsubscribeShippingRateFail();
            unsubscribeShippingRateSelectSuccess();
        }
    }, [
        paymentEvents,
        onShippingRateSuccess,
        onShippingRateFail,
        onShippingRateSelectSuccess
    ]);

    return {addPaymentEvent, removePaymentEvent};
}