import {useEffect, useState, useCallback, useMemo} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {
    initStripe as loadStripe,
    getSettings,
} from '../util';
import {Elements, CardElement, useStripe, useElements, CardNumberElement} from '@stripe/react-stripe-js';
import {PaymentMethodLabel, PaymentMethod} from '../../components/checkout';
import SavedCardComponent from '../saved-card-component';
import CustomCardForm from './components/custom-card-form';
import StripeCardForm from "./components/stripe-card-form";
import {Installments} from '../../components/checkout';
import PaymentElementComponent from './payment-element';
import {
    useProcessPaymentIntent,
    useAfterProcessingPayment,
    useSetupIntent,
    useStripeError
} from "../hooks";

const getData = getSettings('stripe_cc_data');

const CreditCardContent = (props) => {
    const [error, setError] = useState(false);
    useEffect(() => {
        loadStripe.catch(error => {
            setError(error);
        })
    }, [setError]);
    if (error) {
        throw new Error(error);
    }
    if (getData('isPaymentElement')) {
        return (
            <PaymentElementComponent {...props}/>
        )
    }
    return (
        <Elements stripe={loadStripe} options={getData('elementOptions')}>
            <CreditCardElement {...props}/>
        </Elements>
    );
};

const CreditCardElement = (
    {
        getData,
        billing,
        shippingData,
        emitResponse,
        eventRegistration,
        activePaymentMethod,
        shouldSavePayment
    }) => {
    const [error, setError] = useStripeError();
    const [formComplete, setFormComplete] = useState(false);
    const {onPaymentProcessing} = eventRegistration;
    const stripe = useStripe();
    const elements = useElements();
    const getPaymentMethodArgs = useCallback(() => {
        const elType = getData('customFormActive') ? CardNumberElement : CardElement;
        return {card: elements.getElement(elType)};
    }, [stripe, elements]);

    const {setupIntent, removeSetupIntent} = useSetupIntent({
        getData,
        cartTotal: billing.cartTotal,
        setError
    })

    const {getCreatePaymentMethodArgs, addPaymentMethodData} = useProcessPaymentIntent({
        getData,
        billing,
        shippingData,
        emitResponse,
        error,
        onPaymentProcessing,
        shouldSavePayment,
        setupIntent,
        removeSetupIntent,
        getPaymentMethodArgs,
        activePaymentMethod
    });
    useAfterProcessingPayment({
        getData,
        eventRegistration,
        responseTypes: emitResponse.responseTypes,
        activePaymentMethod,
        shouldSavePayment
    });

    const onChange = (event) => {
        if (event.error) {
            setError(event.error);
        } else {
            setError(false);
        }
    }

    const getPaymentMethod = useCallback(async () => {
        let paymentMethod = null;
        const result = await stripe.createPaymentMethod(getCreatePaymentMethodArgs());
        if (result?.paymentMethod?.id) {
            paymentMethod = result.paymentMethod.id;
        }
        return paymentMethod;
    }, [stripe, getCreatePaymentMethodArgs]);

    const Tag = getData('customFormActive') ? CustomCardForm : StripeCardForm;
    return (
        <div className='wc-stripe-card-container'>
            <Tag {...{getData, billing, onChange}} onComplete={setFormComplete}/>
            {getData('installmentsActive') && <Installments
                paymentMethodName={getData('name')}
                cardFormComplete={formComplete}
                addPaymentMethodData={addPaymentMethodData}
                getPaymentMethod={getPaymentMethod}/>}

        </div>
    );
}

registerPaymentMethod({
    name: getData('name'),
    label: <PaymentMethodLabel
        title={getData('title')}
        paymentMethod={getData('name')}
        icons={getData('icons')}/>,
    ariaLabel: 'Credit Cards',
    canMakePayment: () => loadStripe,
    content: <PaymentMethod content={CreditCardContent} getData={getData}/>,
    savedTokenComponent: <SavedCardComponent getData={getData} method={getData('isPaymentElement') ? 'confirmCardPayment' : 'handleCardAction'}/>,
    edit: <PaymentMethod content={CreditCardContent} getData={getData}/>,
    supports: {
        showSavedCards: getData('showSavedCards'),
        showSaveOption: true,
        features: getData('features')
    }
})