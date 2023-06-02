import {useEffect, useRef, useState, useCallback} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {
    getSettings,
    initStripe as loadStripe,
    getDefaultSourceArgs,
    isAddressValid,
    StripeError,
    isTestMode,
    ensureSuccessResponse,
    getErrorMessage,
    storeInCache,
    getFromCache,
    deleteFromCache
} from "../util";
import {PaymentMethodLabel, PaymentMethod} from "../../components/checkout";
import {canMakePayment} from "./local-payment-method";
import {Elements} from "@stripe/react-stripe-js";
import {useValidateCheckout} from "./hooks";
import {__} from '@wordpress/i18n';
//import QRCode from 'QRCode';
import {useStripe} from "@stripe/react-stripe-js";
import {useStripeError} from "../hooks";

const getData = getSettings('stripe_wechat_data');

const WeChatComponent = (props) => {
    return (
        <Elements stripe={loadStripe}>
            <WeChatPaymentMethod {...props}/>
        </Elements>
    )
}

const WeChatPaymentMethod = (
    {
        getData,
        billing,
        shippingData,
        emitResponse,
        eventRegistration,
        components
    }) => {
    const size = parseInt(getData('qrSize'));
    const {responseTypes} = emitResponse;
    const {onPaymentProcessing, onCheckoutAfterProcessingWithSuccess} = eventRegistration;
    const {ValidationInputError} = components;
    const {isValid, setIsValid} = useValidateCheckout({
        subscriber: eventRegistration.onPaymentProcessing,
        responseTypes: emitResponse.responseTypes,
        msg: __('Please scan your QR code to continue with payment.', 'woo-stripe-payment')
    });

    const {source, error, deleteSourceFromStorage} = useCreateSource({
        getData,
        billing,
        responseTypes,
        subscriber: onPaymentProcessing
    })

    /**
     * delete the source from storage once payment is successful.
     * If test mode, redirect to the Stripe test url.
     * If live mode, redirect to the return Url.
     */
    useEffect(() => {
        const unsubscribe = onCheckoutAfterProcessingWithSuccess(() => {
            deleteSourceFromStorage();
            return ensureSuccessResponse(responseTypes);
        });
        return () => unsubscribe();
    }, [
        source,
        onCheckoutAfterProcessingWithSuccess,
        deleteSourceFromStorage
    ]);

    useEffect(() => {
        if (source) {
            setIsValid(true);
        }
    }, [source]);

    if (source) {
        return (
            <QRCodeComponent text={source.wechat.qr_code_url} width={size} height={size}/>
        );
    } else if (error) {
        return (
            <div className='wechat-validation-error'>
                <ValidationInputError errorMessage={getErrorMessage(error)}/>
            </div>
        );
    } else {
        // if billing address is not valid
        if (!isAddressValid(billing.billingData)) {
            return __('Please fill out all the required fields in order to complete the WeChat payment.', 'woo-stripe-payment');
        }
    }
    return null;
}

const QRCodeComponent = (
    {
        text,
        width = 128,
        height = 128,
        colorDark = '#424770',
        colorLight = '#f8fbfd',
        correctLevel = QRCode.CorrectLevel.H
    }) => {
    const el = useRef();
    useEffect(() => {
        new QRCode(el.current, {
            text,
            width,
            height,
            colorDark,
            colorLight,
            correctLevel
        })
    }, [el]);
    return (
        <>
            <div id='wc-stripe-block-qrcode' ref={el}></div>
            {isTestMode() && <p>
                {__('Test mode: Click the Place Order button to proceed.', 'woo-stripe-payment')}
            </p>}
            {!isTestMode() && <p>
                {__('Scan the QR code using your WeChat app. Once scanned click the Place Order button.', 'woo-stripe-payment')}
            </p>}
        </>
    )
}

const useCreateSource = (
    {
        getData,
        billing,
        responseTypes,
        subscriber
    }) => {
    const stripe = useStripe();
    const [error, setError] = useStripeError();
    const [source, setSource] = useState(getFromCache('wechat:source'));
    const createSourceTimeoutId = useRef(null);
    const {cartTotal, billingData, currency} = billing;

    useEffect(() => {
        const unsubscribe = subscriber(() => {
            return ensureSuccessResponse(responseTypes, {
                meta: {
                    paymentMethodData: {
                        [`${getData('name')}_token_key`]: source.id
                    }
                }
            })
        });
        return () => unsubscribe();
    }, [source, subscriber]);

    const createSource = useCallback(async () => {
        // validate the billing fields. If valid, create the source.
        try {
            if (!error && isAddressValid(billingData)) {
                let result = await stripe.createSource(getDefaultSourceArgs({
                    type: getData('paymentType'),
                    amount: cartTotal.value,
                    billingData,
                    currency: currency.code,
                    returnUrl: getData('returnUrl')
                }));
                if (result.error) {
                    throw new StripeError(result.error);
                }
                setSource(result.source);
                storeInCache('wechat:source', result.source);
            }
        } catch (err) {
            console.log('error: ', err);
            setError(err.error);
        }
    }, [
        stripe,
        source,
        cartTotal.value,
        billingData,
        currency,
        error
    ]);
    const deleteSourceFromStorage = useCallback(() => {
        deleteFromCache('wechat:source');
    }, []);

    useEffect(() => {
        if (stripe && !source) {
            // if there is an existing request, cancel it.
            clearTimeout(createSourceTimeoutId.current);
            createSourceTimeoutId.current = setTimeout(createSource, 1000);
        }
    }, [
        stripe,
        source,
        createSource
    ]);

    return {source, setSource, error, deleteSourceFromStorage};
}


if (getData()) {
    registerPaymentMethod({
        name: getData('name'),
        label: <PaymentMethodLabel
            title={getData('title')}
            paymentMethod={getData('name')}
            icons={getData('icon')}/>,
        ariaLabel: 'WeChat',
        canMakePayment: canMakePayment(getData),
        content: <PaymentMethod content={WeChatComponent} getData={getData}/>,
        edit: <PaymentMethod content={WeChatComponent} getData={getData}/>,
        supports: {
            showSavedCards: false,
            showSaveOption: false,
            features: getData('features')
        }
    })
}
