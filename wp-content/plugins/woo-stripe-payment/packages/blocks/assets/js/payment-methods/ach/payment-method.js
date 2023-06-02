import {useState} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {Elements} from '@stripe/react-stripe-js';
import {getSettings, initStripe as loadStripe} from '../util';
import {PaymentMethodLabel, PaymentMethod} from '../../components/checkout';
import SavedCardComponent from '../saved-card-component';
import {useCreateLinkToken, useInitializePlaid, useProcessPayment} from './hooks';
import {useProcessCheckoutError} from "../hooks";

const getData = getSettings('stripe_ach_data');

const ACHPaymentContent = (
    {
        eventRegistration,
        components,
        emitResponse,
        onSubmit,
        billing,
        ...props
    }) => {
    const {responseTypes} = emitResponse;
    const {
        onPaymentProcessing,
        onCheckoutAfterProcessingWithError,
        onCheckoutAfterProcessingWithSuccess
    } = eventRegistration;

    useProcessCheckoutError({
        responseTypes,
        subscriber: onCheckoutAfterProcessingWithError
    });


    useProcessPayment({
        onCheckoutAfterProcessingWithSuccess,
        responseTypes,
        paymentMethod: getData('name'),
        billingAddress: billing.billingData
    });
    return (
        <div className={'wc-stripe-ach__container'}>
            <Mandate text={getData('mandateText')}/>
        </div>
    )
}

const ACHComponent = (props) => {
    return (
        <Elements stripe={loadStripe}>
            <ACHPaymentContent {...props}/>
        </Elements>
    )
}

const Mandate = ({text}) => {
    return (
        <p className={'wc-stripe-ach__mandate'}>
            {text}
        </p>
    )
}

registerPaymentMethod({
    name: getData('name'),
    label: <PaymentMethodLabel title={getData('title')}
                               paymentMethod={getData('name')}
                               icons={getData('icons')}/>,
    ariaLabel: 'ACH Payment',
    canMakePayment: ({cartTotals}) => cartTotals.currency_code === 'USD',
    content: <PaymentMethod
        getData={getData}
        content={ACHComponent}/>,
    savedTokenComponent: <SavedCardComponent getData={getData}/>,
    edit: <ACHComponent/>,
    placeOrderButtonLabel: getData('placeOrderButtonLabel'),
    supports: {
        showSavedCards: getData('showSavedCards'),
        showSaveOption: false,
        features: getData('features')
    }
})