import {useMemo, useEffect, useRef} from '@wordpress/element';
import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, initStripe as loadStripe, canMakePayment} from "../util";
import {useBreakpointWidth, useExpressBreakpointWidth} from '../hooks';
import {Elements, PaymentRequestButtonElement, useStripe} from "@stripe/react-stripe-js";
import {
    usePaymentRequest,
    useProcessPaymentIntent,
    useExportedValues,
    useAfterProcessingPayment,
    useStripeError
} from '../hooks';

const getData = getSettings('stripe_payment_request_data');

const PaymentRequestContent = (props) => {
    return (
        <div className='wc-stripe-payment-request-container'>
            <Elements stripe={loadStripe}>
                <PaymentRequestButton {...props}/>
            </Elements>
        </div>
    );
}

const PaymentRequestButton = (
    {
        getData,
        onClick,
        onClose,
        billing,
        shippingData,
        eventRegistration,
        emitResponse,
        onSubmit,
        activePaymentMethod,
        ...props
    }) => {
    const {onPaymentProcessing} = eventRegistration;
    const {responseTypes, noticeContexts} = emitResponse;
    const stripe = useStripe();
    const [error] = useStripeError();
    const canPay = (result) => result != null && !result.applePay;
    const exportedValues = useExportedValues();
    useExpressBreakpointWidth({payment_method: getData('name'), width: 375});
    const {setPaymentMethod} = useProcessPaymentIntent({
        getData,
        billing,
        shippingData,
        onPaymentProcessing,
        emitResponse,
        error,
        onSubmit,
        activePaymentMethod,
        exportedValues
    });
    useAfterProcessingPayment({
        getData,
        eventRegistration,
        responseTypes,
        activePaymentMethod,
        messageContext: noticeContexts.EXPRESS_PAYMENTS
    });
    const {paymentRequest} = usePaymentRequest({
        getData,
        onClose,
        stripe,
        billing,
        shippingData,
        eventRegistration,
        setPaymentMethod,
        exportedValues,
        canPay
    });

    const options = useMemo(() => {
        return {
            paymentRequest,
            style: {
                paymentRequestButton: getData('paymentRequestButton')
            }
        }
    }, [paymentRequest]);

    if (paymentRequest) {
        return (
            <PaymentRequestButtonElement options={options} onClick={onClick}/>
        )
    }
    return null;
}

const PaymentRequestEdit = ({getData, ...props}) => {
    const canvas = useRef();
    useEffect(() => {
        const scale = window.devicePixelRatio;
        canvas.current.width = 20 * scale;
        canvas.current.height = 20 * scale;
        let ctx = canvas.current.getContext('2d');
        ctx.scale(scale, scale);
        ctx.beginPath();
        ctx.arc(10, 10, 10, 0, 2 * Math.PI);
        ctx.fillStyle = '#986fff';
        ctx.fill();
    });
    return (
        <div className='payment-request-block-editor'>
            <div className={'icon-container'}>
                <span>Buy now</span>
                <canvas className='PaymentRequestButton-icon' ref={canvas}/>
                <i className={'payment-request-arrow'}></i>
            </div>
        </div>
    )
}

registerExpressPaymentMethod({
    name: getData('name'),
    canMakePayment: ({cartTotals}) => {
        if (getData('isAdmin')) {
            return true;
        }
        const {currency_code: currency, total_price} = cartTotals;
        return canMakePayment({
            country: getData('countryCode'),
            currency: currency.toLowerCase(),
            total: {
                label: getData('totalLabel'),
                amount: parseInt(total_price)
            }
        }, (result) => result != null && !result.applePay);
    },
    content: <PaymentRequestContent getData={getData}/>,
    edit: <PaymentRequestEdit getData={getData}/>,
    supports: {
        showSavedCards: getData('showSavedCards'),
        showSaveOption: getData('showSaveOption'),
        features: getData('features')
    }
});