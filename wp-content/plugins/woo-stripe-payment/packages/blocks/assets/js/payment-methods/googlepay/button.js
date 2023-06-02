import {useRef, useEffect} from '@wordpress/element';
import {usePaymentsClient, usePaymentRequest} from './hooks';
import {
    useProcessPaymentIntent,
    useStripeError,
    useExportedValues,
    useExpressBreakpointWidth, useAfterProcessingPayment
} from '../hooks';
import {getSettings} from '@paymentplugins/stripe/util';

const {publishableKey} = getSettings('stripeGeneralData')();

const GooglePayButton = (
    {
        getData,
        setErrorMessage,
        billing,
        shippingData,
        canMakePayment,
        checkoutStatus,
        eventRegistration,
        activePaymentMethod,
        onClick,
        onClose,
        ...props
    }) => {
    const merchantInfo = {
        merchantId: getData('merchantId'),
        merchantName: getData('merchantName')
    };
    const [error, setError] = useStripeError();
    const buttonContainer = useRef();
    const {onSubmit, emitResponse} = props;
    const {onPaymentProcessing} = eventRegistration;
    const exportedValues = useExportedValues();
    const width = getData('buttonStyle').buttonType === 'long' ? 390 : 375;
    const {setPaymentMethod} = useProcessPaymentIntent({
        getData,
        billing,
        shippingData,
        onPaymentProcessing,
        emitResponse,
        error,
        exportedValues,
        onSubmit,
        checkoutStatus,
        activePaymentMethod
    });

    const buildPaymentRequest = usePaymentRequest({
        getData,
        publishableKey,
        merchantInfo,
        billing,
        shippingData
    })

    const {button, removeButton} = usePaymentsClient({
        merchantInfo,
        buildPaymentRequest,
        billing,
        shippingData,
        eventRegistration,
        canMakePayment,
        setErrorMessage,
        onSubmit,
        setPaymentMethod,
        exportedValues,
        onClick,
        onClose,
        getData
    });

    useAfterProcessingPayment({
        getData,
        eventRegistration,
        responseTypes: emitResponse.responseTypes,
        activePaymentMethod
    });

    useExpressBreakpointWidth({payment_method: getData('name'), width});

    useEffect(() => {
        if (button) {
            // prevent button duplicates
            removeButton(buttonContainer.current);
            buttonContainer.current.append(button);
        }
    }, [button]);

    return (
        <div className='wc-stripe-gpay-button-container' ref={buttonContainer}></div>
    )
}

export default GooglePayButton;